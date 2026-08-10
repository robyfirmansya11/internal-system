<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Kasbon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KasbonSeeder extends Seeder
{
    /**
     * Total data yang akan dibuat.
     */
    protected int $total = 1000;

    /**
     * Ukuran batch insert.
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

        $financeManagerIds = User::where('jabatan', Kasbon::LEVEL2_JABATAN)
            ->pluck('id')
            ->all();

        if (empty($financeManagerIds)) {
            $this->command->warn(
                "Tidak ada user dengan jabatan '".Kasbon::LEVEL2_JABATAN."'. Fallback ke user random untuk approval level 2."
            );
            $financeManagerIds = $userIds;
        }

        $keteranganOptions = [
            'Kasbon untuk keperluan operasional lapangan.',
            'Kasbon untuk pembelian kebutuhan kantor mendadak.',
            'Kasbon perjalanan dinas mendesak.',
            'Kasbon untuk keperluan darurat keluarga.',
            'Kasbon untuk biaya perbaikan kendaraan operasional.',
            'Kasbon untuk pembayaran vendor sementara.',
            'Kasbon untuk keperluan acara perusahaan.',
        ];

        $bankOptions = ['BCA', 'Mandiri', 'BNI', 'BRI'];

        $rows = [];

        for ($i = 1; $i <= $this->total; $i++) {

            $tanggal = Carbon::now()->subDays(fake()->numberBetween(1, 180));

            $jumlahDana = fake()->numberBetween(100, 20000) * 1000; // kelipatan 1.000

            $userId = fake()->randomElement($userIds);
            $atasanId = fake()->randomElement($userIds);
            $financeManagerId = fake()->randomElement($financeManagerIds);

            // Distribusi status: 15% Submitted, 20% Pending (Atasan),
            // 20% Pending (Finance), 35% Approved, 10% Rejected.
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

            $createdAt = (clone $tanggal)->subDays(fake()->numberBetween(0, 3));
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
                    'Alasan pengajuan kurang jelas.',
                    'Nominal terlalu besar untuk kebutuhan yang diajukan.',
                    'Sudah ada kasbon lain yang belum diselesaikan.',
                    'Tidak sesuai kebijakan kasbon perusahaan.',
                    'Perlu dilengkapi dokumen pendukung terlebih dahulu.',
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
                'tanggal' => $tanggal->toDateString(),
                'keterangan' => fake()->randomElement($keteranganOptions),
                'jumlah_dana' => $jumlahDana,
                'terbilang' => static::terbilang((int) $jumlahDana).' Rupiah',
                'informasi_transfer' => fake()->randomElement($bankOptions).' - '
                    .fake()->numerify('##########').' a.n. '.fake()->name(),
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
                DB::table('kasbons')->insert($rows);
                $rows = [];
                $this->command->info("Inserted {$i} / {$this->total} records...");
            }
        }

        if (! empty($rows)) {
            DB::table('kasbons')->insert($rows);
        }

        $this->command->info("Selesai! {$this->total} data Kasbon berhasil dibuat.");
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
