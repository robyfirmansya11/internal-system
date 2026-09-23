<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentApplicationController extends Controller
{
    public function companies()
    {
        return response()->json(Company::orderBy('nama')->get(['id', 'nama']));
    }

    public function index(Request $request)
    {
        $records = SuratPerintahBayar::where('user_id', $request->user()->id)->with('company')
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn ($record) => $this->format($record, $request));
        return response()->json($records);
    }

    public function show(Request $request, int $id)
    {
        $record = SuratPerintahBayar::where('user_id', $request->user()->id)->with('company')->findOrFail($id);
        return response()->json($this->format($record, $request));
    }

    public function store(Request $request)
    {
        $money = ['required', 'numeric', 'min:0', 'max:999999999999', 'regex:/^\d{1,12}(\.\d{1,2})?$/'];
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'no_invoice' => ['required', 'string', 'max:255'],
            'customer' => ['required', 'string', 'max:255'],
            'tanggal_penagihan' => ['required', 'date_format:Y-m-d'],
            'tanggal_jatuhtempo' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_penagihan'],
            'jumlah' => $money, 'pph' => $money, 'admin' => $money,
            'pembayaran_tahap' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'jumlah_lampiran' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:5000'],
            'informasi_transfer' => ['nullable', 'string', 'max:5000'],
            'lampiran' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $user = $request->user();
        $department = $user->departments()->first();
        if (! $department) {
            return response()->json(['message' => 'User belum memiliki department.'], 422);
        }
        $amount = $this->minor((string) $data['jumlah']);
        $withholding = $this->minor((string) $data['pph']);
        $admin = $this->minor((string) $data['admin']);
        // Match the existing Filament VAT field: 11%, rounded to whole Rupiah.
        $vat = intdiv($amount * 11 + 5000, 10000);
        $rawTotal = $amount + $vat * 100 + $admin - $withholding;
        $total = intdiv($rawTotal + 50, 100);
        if ($amount <= 0 || $rawTotal <= 0 || $total <= 0 || $total > 999999999999) {
            return response()->json(['message' => 'Nominal dan total tagihan harus lebih dari nol, total maksimal Rp 999.999.999.999.'], 422);
        }
        $isFinanceManager = $user->jabatan === SuratPerintahBayar::LEVEL2_JABATAN;
        $isManager = $user->isSuperuser();
        $atasan = $user->profile?->atasan;
        $skipManager = ! $atasan || $atasan->id === $user->id;
        $level = $isFinanceManager ? 3 : (($isManager || $skipManager) ? 2 : 1);
        $selfManager = $isFinanceManager || (! $isManager && $skipManager);

        $path = $request->file('lampiran')->store('surat-perintah-bayar', 'public');
        if (! $path) abort(500, 'Lampiran gagal disimpan.');
        try {
            $record = DB::transaction(function () use ($data, $user, $department, $amount, $withholding, $admin, $vat, $total, $level, $selfManager, $isFinanceManager, $atasan, $path) {
                $record = SuratPerintahBayar::create(array_merge($data, [
                    'user_id' => $user->id, 'department_id' => $department->id,
                    'jumlah' => $amount / 100, 'pph' => $withholding / 100, 'admin' => $admin / 100,
                    'ppn' => $vat, 'jumlah_total' => $total,
                    'terbilang' => ucwords((new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($total)).' Rupiah',
                    'lampiran' => $path,
                    'status' => $isFinanceManager ? 'Approved' : 'Pending Approval',
                    'approval_level' => $level,
                    'approved_by_manager' => $selfManager ? $user->id : null,
                    'approved_manager_at' => $selfManager ? now() : null,
                    'approved_by' => $isFinanceManager ? $user->id : null,
                    'approved_at' => $isFinanceManager ? now() : null,
                ]));
                $recipients = $isFinanceManager ? collect([$user]) : ($level === 1
                    ? collect([$atasan]) : User::where('jabatan', SuratPerintahBayar::LEVEL2_JABATAN)->get());
                foreach ($recipients as $recipient) {
                    Notification::make()->title($isFinanceManager ? 'Automatically Approved' : 'New Payment Application Letter')
                        ->body("{$user->name} submitted a Payment Application Letter.")
                        ->icon('heroicon-o-document-text')->sendToDatabase($recipient);
                }
                return $record;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
        return response()->json(['message' => 'Pengajuan pembayaran berhasil dikirim.', 'data' => $this->format($record, $request)], 201);
    }

    public function cancel(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $record = SuratPerintahBayar::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
            if (! $record->cancel($request->user())) {
                return response()->json(['message' => 'Pengajuan ini tidak dapat dibatalkan.'], 422);
            }
            return response()->json(['message' => 'Pengajuan dibatalkan.', 'data' => $this->format($record, $request)]);
        });
    }

    private function minor(string $value): int
    {
        $parts = explode('.', $value);
        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    protected function format(SuratPerintahBayar $record, Request $request): array
    {
        $data = $record->only(['id', 'company_id', 'no_invoice', 'customer', 'jumlah', 'ppn', 'pph', 'admin',
            'jumlah_total', 'terbilang', 'pembayaran_tahap', 'jumlah_lampiran', 'keterangan', 'informasi_transfer',
            'status', 'approval_level', 'rejected_note']);
        return array_merge($data, [
            'created_by' => $record->user?->name,
            'can_approve' => $record->isPending() && (
                ($request->user()->isSuperuser() && $record->isWaitingAtasan() && $record->isValidAtasan($request->user()))
                || ($request->user()->jabatan === SuratPerintahBayar::LEVEL2_JABATAN && $record->isWaitingAdmin() && $record->user_id !== $request->user()->id)
            ),
            'company_name' => $record->company?->nama,
            'tanggal_penagihan' => $record->tanggal_penagihan?->format('Y-m-d'),
            'tanggal_jatuhtempo' => $record->tanggal_jatuhtempo?->format('Y-m-d'),
            'has_attachment' => (bool) $record->lampiran,
            'can_cancel' => $record->canBeCancelledBy($request->user()),
        ]);
    }
}
