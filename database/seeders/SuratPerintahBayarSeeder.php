<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuratPerintahBayarSeeder extends Seeder
{
    /**
     * Total data yang akan dibuat.
     */
    protected int $total = 1000;

    /**
     * Ukuran batch insert (supaya tidak berat sekali insert 1000 baris).
     */
    protected int $chunkSize = 200;

    public function run(): void
    {
        $companyIds = Company::pluck('id')->all();
        $departmentIds = Department::pluck('id')->all();
        $userIds = User::pluck('id')->all();

        if (empty($companyIds) || empty($departmentIds) || empty($userIds)) {
            $this->command->error(
                'Seeder dibatalkan: pastikan tabel companies, departments, dan users sudah terisi data terlebih dahulu.'
            );

            return;
        }

        $financeManagerIds = User::where('jabatan', SuratPerintahBayar::LEVEL2_JABATAN)
            ->pluck('id')
            ->all();

        if (empty($financeManagerIds)) {
            $this->command->warn(
                "Tidak ada user dengan jabatan '".SuratPerintahBayar::LEVEL2_JABATAN."'. Fallback ke user random untuk approval level 2."
            );
            $financeManagerIds = $userIds;
        }

        $pembayaranTahapOptions = ['Tahap 1', 'Tahap 2', 'Tahap 3', 'Full Payment', 'DP 50%'];

        $rows = [];

        for ($i = 1; $i <= $this->total; $i++) {

            $tanggalPenagihan = Carbon::now()->subDays(fake()->numberBetween(1, 180));
            $tanggalJatuhTempo = (clone $tanggalPenagihan)->addDays(fake()->numberBetween(14, 30));

            $jumlah = fake()->numberBetween(500, 50000) * 1000; // kelipatan 1.000
            $ppn = round($jumlah * 0.11);
            $pph = round($jumlah * fake()->randomElement([0, 0.015, 0.02]));
            $admin = fake()->numberBetween(0, 10) * 5000;
            $jumlahTotal = $jumlah + $ppn - $pph + $admin;

            $userId = fake()->randomElement($userIds);
            $atasanId = fake()->randomElement($userIds);
            $financeManagerId = fake()->randomElement($financeManagerIds);

            // Distribusi status supaya dashboard punya variasi data:
            // 15% Submitted, 20% Pending (Atasan), 20% Pending (Finance),
            // 35% Approved, 10% Rejected.
            $roll = fake()->numberBetween(1, 100);

            $status = 'Submitted';
            $approvalLevel = 0;
            $approvedByManager = null;
            $approvedManagerAt = null;
            $approvedBy = null;
            $approvedAt = null;
            $rejectedBy = null;
            $rejectedAt = null;
            $rejectedNote = null;

            $createdAt = (clone $tanggalPenagihan)->subDays(fake()->numberBetween(0, 3));
            $updatedAt = clone $createdAt;

            if ($roll <= 15) {
                // Submitted — belum diajukan sama sekali ke atasan
                $status = 'Submitted';
                $approvalLevel = 0;
            } elseif ($roll <= 35) {
                // Pending — menunggu Atasan (level 1)
                $status = 'Pending Approval';
                $approvalLevel = 1;
                $updatedAt = (clone $createdAt)->addHours(fake()->numberBetween(1, 48));
            } elseif ($roll <= 55) {
                // Pending — Atasan sudah approve, menunggu Finance Manager (level 2)
                $status = 'Pending Approval';
                $approvalLevel = 2;
                $approvedByManager = $atasanId;
                $approvedManagerAt = (clone $createdAt)->addHours(fake()->numberBetween(1, 48));
                $updatedAt = clone $approvedManagerAt;
            } elseif ($roll <= 90) {
                // Approved — lolos semua tahap
                $status = 'Approved';
                $approvalLevel = 3;
                $approvedByManager = $atasanId;
                $approvedManagerAt = (clone $createdAt)->addHours(fake()->numberBetween(1, 48));
                $approvedBy = $financeManagerId;
                $approvedAt = (clone $approvedManagerAt)->addHours(fake()->numberBetween(1, 72));
                $updatedAt = clone $approvedAt;
            } else {
                // Rejected — 50:50 ditolak di level Atasan atau level Finance
                $status = 'Rejected';
                $approvalLevel = -1;
                $rejectedNote = fake()->randomElement([
                    'Dokumen pendukung tidak lengkap.',
                    'Nominal tidak sesuai dengan PO.',
                    'Invoice sudah pernah dibayarkan sebelumnya.',
                    'Perlu revisi informasi transfer dana.',
                    'Tidak sesuai anggaran departemen.',
                ]);

                if (fake()->boolean()) {
                    // Ditolak oleh Atasan (sebelum sempat approve)
                    $rejectedBy = $atasanId;
                    $rejectedAt = (clone $createdAt)->addHours(fake()->numberBetween(1, 48));
                } else {
                    // Atasan sudah approve dulu, baru ditolak Finance Manager
                    $approvedByManager = $atasanId;
                    $approvedManagerAt = (clone $createdAt)->addHours(fake()->numberBetween(1, 48));
                    $rejectedBy = $financeManagerId;
                    $rejectedAt = (clone $approvedManagerAt)->addHours(fake()->numberBetween(1, 72));
                }

                $updatedAt = clone $rejectedAt;
            }

            $rows[] = [
                'company_id' => fake()->randomElement($companyIds),
                'user_id' => $userId,
                'department_id' => fake()->randomElement($departmentIds),
                'tanggal_penagihan' => $tanggalPenagihan->toDateString(),
                'tanggal_jatuhtempo' => $tanggalJatuhTempo->toDateString(),
                'no_invoice' => 'INV-'.$tanggalPenagihan->format('Ym').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'jumlah' => $jumlah,
                'ppn' => $ppn,
                'pph' => $pph,
                'admin' => $admin,
                'jumlah_total' => $jumlahTotal,
                'terbilang' => static::terbilang((int) $jumlahTotal).' Rupiah',
                'pembayaran_tahap' => fake()->randomElement($pembayaranTahapOptions),
                'jumlah_lampiran' => (string) fake()->numberBetween(1, 5),
                'keterangan' => fake()->sentence(8),
                'informasi_transfer' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']).' - '
                    .fake()->numerify('##########').' a.n. '.fake()->company(),
                'lampiran' => null,
                'status' => $status,
                'approval_level' => $approvalLevel,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'approved_by_manager' => $approvedByManager,
                'approved_manager_at' => $approvedManagerAt,
                'rejected_by' => $rejectedBy,
                'rejected_at' => $rejectedAt,
                'rejected_note' => $rejectedNote,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'deleted_at' => null,
            ];

            if (count($rows) >= $this->chunkSize) {
                DB::table('surat_perintah_bayars')->insert($rows);
                $rows = [];
                $this->command->info("Inserted {$i} / {$this->total} records...");
            }
        }

        if (! empty($rows)) {
            DB::table('surat_perintah_bayars')->insert($rows);
        }

        $this->command->info("Selesai! {$this->total} data Surat Perintah Bayar berhasil dibuat.");
    }

    /**
     * Konversi angka ke terbilang (mengikuti gaya penulisan yang sama
     * dengan PerjalananDinasForm::terbilang() di modul lain).
     */
    protected static function terbilang(int $angka): string
    {
        $angka = abs($angka);
        $words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven'];

        if ($angka < 12) {
            return $words[$angka];
        } elseif ($angka < 20) {
            return static::terbilang($angka - 10).'teen';
        } elseif ($angka < 100) {
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            return $tens[(int) ($angka / 10)].($angka % 10 ? ' '.static::terbilang($angka % 10) : '');
        } elseif ($angka < 1000) {
            return static::terbilang((int) ($angka / 100)).' Hundred'.($angka % 100 ? ' '.static::terbilang($angka % 100) : '');
        } elseif ($angka < 1000000) {
            return static::terbilang((int) ($angka / 1000)).' Thousand'.($angka % 1000 ? ' '.static::terbilang($angka % 1000) : '');
        } elseif ($angka < 1000000000) {
            return static::terbilang((int) ($angka / 1000000)).' Million'.($angka % 1000000 ? ' '.static::terbilang($angka % 1000000) : '');
        }

        return '';
    }
}
