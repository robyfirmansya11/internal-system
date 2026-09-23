<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\RegisterSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class RegisterLetterController extends Controller
{
    public function companies() {
        return response()->json(Company::orderBy('nama')->get(['id','nama']));
    }
    public function index(Request $request) {
        $records = RegisterSurat::with(['company','user'])->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn ($record) => $this->format($record, $request));
        return response()->json($records);
    }
    public function show(Request $request, int $id) {
        return response()->json($this->format(RegisterSurat::with(['company','user'])->findOrFail($id), $request));
    }
    public function store(Request $request) {
        $department = $request->user()->departments()->first();
        abort_unless($department, 422, 'Akun belum memiliki department.');
        $data = $this->validated($request);
        $path = $request->file('lampiran_surat')->store('register-surat', 'public');
        abort_unless($path, 500, 'Lampiran gagal disimpan.');
        try {
            $record = RegisterSurat::create(array_merge($data, [
                'user_id' => $request->user()->id, 'department_id' => $department->id, 'lampiran_surat' => $path,
            ]));
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            if ($e instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['no_surat' => 'Nomor surat sudah digunakan.']);
            }
            throw $e;
        }
        return response()->json(['data' => $this->format($record, $request)], 201);
    }
    public function update(Request $request, int $id) {
        $path = null;
        try {
            $record = DB::transaction(function () use ($request, $id, &$path) {
                $record = RegisterSurat::lockForUpdate()->findOrFail($id);
                abort_unless($this->canEdit($record, $request), 403);
                $data = $this->validated($request, $record);
                unset($data['lampiran_surat']);
                if ($request->hasFile('lampiran_surat')) {
                    $path = $request->file('lampiran_surat')->store('register-surat', 'public');
                    abort_unless($path, 500, 'Lampiran gagal disimpan.');
                    $data['lampiran_surat'] = $path;
                }
                // Preserve old files: legacy Filament uploads may share filenames.
                $record->update($data);
                return $record;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            if ($e instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['no_surat' => 'Nomor surat sudah digunakan.']);
            }
            throw $e;
        }
        return response()->json(['data' => $this->format($record, $request)]);
    }
    public function destroy(Request $request, int $id) {
        return DB::transaction(function () use ($request, $id) {
            $record = RegisterSurat::lockForUpdate()->findOrFail($id);
            abort_unless((int) $record->user_id === (int) $request->user()->id, 403);
            $record->delete();
            return response()->json(['message' => 'Surat dihapus.']);
        });
    }
    private function validated(Request $request, ?RegisterSurat $record = null): array {
        return $request->validate([
            'company_id' => ['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
            'tanggal_surat' => ['required','date_format:Y-m-d'],
            'no_surat' => ['required','string','max:255',Rule::unique('register_surats','no_surat')->ignore($record?->id)],
            'ditujukan' => ['required','string','max:255'],
            'keterangan' => ['nullable','string','max:5000'],
            'lampiran_surat' => [$record && $record->lampiran_surat ? 'nullable' : 'required','file','mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx','max:10240'],
        ]);
    }
    private function canEdit(RegisterSurat $record, Request $request): bool {
        $user = $request->user();
        return (int) $record->user_id === (int) $user->id || $user->isAdmin() || $user->isSuperuser();
    }
    private function format(RegisterSurat $record, Request $request): array {
        return array_merge($record->only(['id','company_id','no_surat','ditujukan','keterangan']), [
            'tanggal_surat' => $record->tanggal_surat?->format('Y-m-d'),
            'company_name' => $record->company?->nama, 'created_by' => $record->user?->name,
            'has_attachment' => (bool) $record->lampiran_surat,
            'can_edit' => $this->canEdit($record, $request),
            'can_delete' => (int) $record->user_id === (int) $request->user()->id,
        ]);
    }
}
