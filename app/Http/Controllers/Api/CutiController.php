<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormCuti;
use App\Models\KuotaCuti;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CutiController extends Controller
{
    /**
     * List semua pengajuan cuti milik user yang login.
     */
    public function index(Request $request)
    {
        $cutis = FormCuti::where('user_id', $request->user()->id)
            ->with(['department'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => $this->formatCuti($c));

        return response()->json($cutis);
    }

    /**
     * Detail satu pengajuan cuti.
     */
    public function show(Request $request, $id)
    {
        $cuti = FormCuti::where('user_id', $request->user()->id)
            ->with(['department', 'manager', 'hrd', 'rejector'])
            ->findOrFail($id);

        return response()->json($this->formatCuti($cuti, detail: true));
    }

    /**
     * Cek kuota cuti user yang login.
     */
    public function quota(Request $request)
    {
        $user = $request->user();
        $tahun = now()->year;

        $kuota = KuotaCuti::where('user_id', $user->id)
            ->where('tahun', $tahun)
            ->first();

        return response()->json([
            'tahun' => $tahun,
            'kuota_tahunan' => $kuota?->kuota_tahunan ?? 0,
            'cuti_terpakai' => $kuota?->cuti_terpakai ?? 0,
            'sisa_cuti' => $kuota?->sisa_cuti ?? 0,
        ]);
    }

    /**
     * Buat pengajuan cuti baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'jenis_cuti' => 'required|string|in:Cuti Tahunan,Cuti Haid,Cuti Khusus,Cuti Melahirkan,Cuti Keguguran,Cuti Sakit,Cuti Besar',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:500',
            'lampiran' => 'required_if:jenis_cuti,Cuti Sakit|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();
        $department = $user->departments->first();

        if (! $department) {
            return response()->json([
                'message' => 'User belum memiliki department.',
            ], 422);
        }

        $tanggalMulai = Carbon::parse($request->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai);

        // Hitung hari kalender — samakan dengan Flutter & Filament (bukan hari kerja saja)
        $jumlahHari = $tanggalMulai->diffInDays($tanggalSelesai) + 1;
        $tahun = $tanggalMulai->year;

        // Validasi overlap — samakan dengan CreateFormCuti.php (hanya exclude Rejected)
        $overlap = FormCuti::where('user_id', $user->id)
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
                    ->orWhereBetween('tanggal_selesai', [$request->tanggal_mulai, $request->tanggal_selesai])
                    ->orWhere(function ($q2) use ($request) {
                        $q2->where('tanggal_mulai', '<=', $request->tanggal_mulai)
                            ->where('tanggal_selesai', '>=', $request->tanggal_selesai);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Tanggal cuti bertabrakan dengan pengajuan yang sudah ada.',
            ], 422);
        }

        // Validasi limit hari per jenis cuti — samakan dengan CreateFormCuti.php
        $jenis = $request->jenis_cuti;

        $errorJenis = match (true) {
            $jenis === 'Cuti Haid' && $jumlahHari > 2 => 'Cuti Haid maksimal 2 hari.',
            $jenis === 'Cuti Khusus' && ($jumlahHari < 1 || $jumlahHari > 3) => 'Cuti Khusus hanya 1–3 hari.',
            $jenis === 'Cuti Melahirkan' && $jumlahHari > 90 => 'Cuti Melahirkan maksimal 90 hari.',
            $jenis === 'Cuti Keguguran' && $jumlahHari > 45 => 'Cuti Keguguran maksimal 45 hari.',
            default => null,
        };

        if ($errorJenis) {
            return response()->json(['message' => $errorJenis], 422);
        }

        // Validasi kuota (khusus Cuti Tahunan & Cuti Haid)
        $jenisPotongKuota = ['Cuti Tahunan', 'Cuti Haid'];

        if (in_array($jenis, $jenisPotongKuota, true)) {
            $kuota = KuotaCuti::where('user_id', $user->id)
                ->where('tahun', $tahun)
                ->first();

            if (! $kuota || $kuota->sisa_cuti < $jumlahHari) {
                return response()->json([
                    'message' => 'Kuota cuti tidak mencukupi. Sisa: '.($kuota?->sisa_cuti ?? 0)." hari, dibutuhkan: {$jumlahHari} hari.",
                ], 422);
            }
        }

        // Upload lampiran
        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('cuti/lampiran', 'public');
        }

        if ($user->jabatan === FormCuti::LEVEL2_JABATAN && ! $user->profile?->atasan) {
            return response()->json([
                'message' => 'Pengajuan cuti HRD memerlukan atasan langsung. Silakan hubungi administrator.',
            ], 422);
        }

        $cuti = FormCuti::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'tahun' => $tahun,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlahHari,
            'jenis_cuti' => $jenis,
            'alasan' => $request->alasan,
            'lampiran' => $lampiranPath,
            'status' => 'Pending Approval',
            'approval_level' => FormCuti::initialApprovalLevel($user),
        ]);

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => $this->formatCuti($cuti),
        ], 201);
    }

    /**
     * Cancel pengajuan cuti.
     */
    public function cancel(Request $request, $id)
    {
        $cuti = FormCuti::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (! $cuti->canBeCancelledBy($request->user())) {
            return response()->json([
                'message' => 'Pengajuan tidak dapat dibatalkan.',
            ], 422);
        }

        $cuti->cancel($request->user());

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dibatalkan.',
        ]);
    }

    /**
     * Format response cuti.
     */
    private function formatCuti(FormCuti $cuti, bool $detail = false): array
    {
        $data = [
            'id' => $cuti->id,
            'jenis_cuti' => $cuti->jenis_cuti,
            'tanggal_mulai' => $cuti->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $cuti->tanggal_selesai?->format('Y-m-d'),
            'formatted_mulai' => $cuti->tanggal_mulai?->format('d M Y'),
            'formatted_selesai' => $cuti->tanggal_selesai?->format('d M Y'),
            'jumlah_hari' => $cuti->jumlah_hari,
            'alasan' => $cuti->alasan,
            'status' => $cuti->status,
            'approval_level' => $cuti->approval_level,
            'created_at' => $cuti->created_at?->format('d M Y'),
        ];

        if ($detail) {
            $data['manager'] = $cuti->manager?->name;
            $data['hrd'] = $cuti->hrd?->name;
            $data['rejected_note'] = $cuti->rejected_note;
            $data['cancelled_at'] = $cuti->cancelled_at?->format('d M Y, H:i');
        }

        return $data;
    }
}
