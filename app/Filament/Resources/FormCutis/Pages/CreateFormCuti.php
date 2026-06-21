<?php

namespace App\Filament\Resources\FormCutis\Pages;

use App\Enums\Role;
use App\Filament\Resources\FormCutis\FormCutiResource;
use App\Models\FormCuti;
use App\Models\KuotaCuti;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateFormCuti extends CreateRecord
{
    protected static string $resource = FormCutiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $departmentId = $user->departments()->value('departments.id');

        if (! $departmentId) {
            Notification::make()
                ->title('Gagal')
                ->body('User tidak memiliki department.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi overlap
        $overlap = FormCuti::where('user_id', $user->id)
            ->where('status', '!=', 'Rejected')
            ->where(function ($query) use ($data) {
                $query
                    ->whereBetween('tanggal_mulai', [
                        $data['tanggal_mulai'],
                        $data['tanggal_selesai'],
                    ])
                    ->orWhereBetween('tanggal_selesai', [
                        $data['tanggal_mulai'],
                        $data['tanggal_selesai'],
                    ])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('tanggal_mulai', '<=', $data['tanggal_mulai'])
                            ->where('tanggal_selesai', '>=', $data['tanggal_selesai']);
                    });
            })
            ->exists();

        if ($overlap) {
            Notification::make()
                ->title('Tanggal Bertabrakan')
                ->body('Tanggal cuti bertabrakan dengan pengajuan cuti lain yang sudah ada.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi jenis cuti
        $jenis = $data['jenis_cuti'] ?? null;
        $jumlahHari = (int) ($data['jumlah_hari'] ?? 0);

        $errorJenis = match (true) {
            $jenis === 'Cuti Haid' && $jumlahHari > 2 => 'Cuti Haid maksimal 2 hari.',
            $jenis === 'Cuti Khusus' && ($jumlahHari < 1 || $jumlahHari > 3) => 'Cuti Khusus hanya 1–3 hari.',
            $jenis === 'Cuti Melahirkan' && $jumlahHari > 90 => 'Cuti Melahirkan maksimal 3 bulan (90 hari).',
            $jenis === 'Cuti Keguguran' && $jumlahHari > 45 => 'Cuti Keguguran maksimal 1.5 bulan (45 hari).',
            default => null,
        };

        if ($errorJenis) {
            Notification::make()
                ->title('Validasi Gagal')
                ->body($errorJenis)
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi kuota
        $butuhKuota = in_array($jenis, ['Cuti Tahunan', 'Cuti Haid'], true);

        if ($butuhKuota) {
            $kuota = KuotaCuti::where('user_id', $user->id)
                ->where('tahun', $data['tahun'])
                ->first();

            if (! $kuota) {
                Notification::make()
                    ->title('Kuota Tidak Ditemukan')
                    ->body('Kuota cuti untuk tahun ini tidak ditemukan.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            if ($kuota->sisa_cuti < $jumlahHari) {
                Notification::make()
                    ->title('Kuota Tidak Mencukupi')
                    ->body("Sisa kuota: {$kuota->sisa_cuti} hari, dibutuhkan: {$jumlahHari} hari.")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $atasan = $user->profile?->atasan;
        $selfApprove = ! $atasan || $atasan->id === $user->id;

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'status' => 'Pending Approval',
            'approval_level' => $selfApprove ? 2 : 1,
            'approved_by_manager' => $selfApprove ? $user->id : null,
            'approved_manager_at' => $selfApprove ? now() : null,
        ]);
    }

    /*   protected function validateOverlap(array $data, int $userId): void
      {
          $exists = FormCuti::where('user_id', $userId)
              ->where('status', '!=', 'Rejected')
              ->where(function ($query) use ($data) {
                  $query
                      ->whereBetween('tanggal_mulai', [
                          $data['tanggal_mulai'],
                          $data['tanggal_selesai'],
                      ])
                      ->orWhereBetween('tanggal_selesai', [
                          $data['tanggal_mulai'],
                          $data['tanggal_selesai'],
                      ])
                      ->orWhere(function ($q) use ($data) {
                          $q->where('tanggal_mulai', '<=', $data['tanggal_mulai'])
                              ->where('tanggal_selesai', '>=', $data['tanggal_selesai']);
                      });
              })
              ->exists();

          if ($exists) {
              throw ValidationException::withMessages([
                  'tanggal_mulai' => 'Tanggal cuti bertabrakan dengan pengajuan cuti lain yang sudah ada.',
              ]);
          }
      }

      protected function validateJenisCuti(array $data): void
      {
          $jenis = $data['jenis_cuti'] ?? null;
          $jumlahHari = (int) ($data['jumlah_hari'] ?? 0);

          match ($jenis) {
              'Cuti Haid' => throw_if(
                  $jumlahHari > 2,
                  ValidationException::withMessages([
                      'jumlah_hari' => 'Cuti Haid maksimal 2 hari.',
                  ])
              ),
              'Cuti Khusus' => throw_if(
                  $jumlahHari < 1 || $jumlahHari > 3,
                  ValidationException::withMessages([
                      'jumlah_hari' => 'Cuti Khusus hanya 1–3 hari.',
                  ])
              ),
              'Cuti Melahirkan' => throw_if(
                  $jumlahHari > 90,
                  ValidationException::withMessages([
                      'jumlah_hari' => 'Cuti Melahirkan maksimal 3 bulan (90 hari).',
                  ])
              ),
              'Cuti Keguguran' => throw_if(
                  $jumlahHari > 45,
                  ValidationException::withMessages([
                      'jumlah_hari' => 'Cuti Keguguran maksimal 1.5 bulan (45 hari).',
                  ])
              ),
              default => null,
          };
      }

      protected function validateKuota(array $data, int $userId): void
      {
          $jenisCuti = $data['jenis_cuti'] ?? null;
          $jumlahHari = (int) ($data['jumlah_hari'] ?? 0);
          $tahun = $data['tahun'] ?? null;

          $butuhKuota = in_array($jenisCuti, ['Cuti Tahunan', 'Cuti Haid'], true);

          if (! $butuhKuota) {
              return;
          }

          $kuota = KuotaCuti::where('user_id', $userId)
              ->where('tahun', $tahun)
              ->first();

          if (! $kuota) {
              throw ValidationException::withMessages([
                  'tahun' => 'Kuota cuti untuk tahun ini tidak ditemukan.',
              ]);
          }

          if ($kuota->sisa_cuti < $jumlahHari) {
              throw ValidationException::withMessages([
                  'jumlah_hari' => "Sisa kuota tidak mencukupi. Sisa: {$kuota->sisa_cuti} hari, dibutuhkan: {$jumlahHari} hari.",
              ]);
          }
      } */

    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = auth()->user();

        if ($record->isWaitingAtasan()) {
            $atasan = $user->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('Pengajuan Cuti Baru')
                    ->body("{$user->name} mengajukan cuti.")
                    ->icon('heroicon-o-calendar-days')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            $hrds = User::where('level', Role::Admin)
                ->where('jabatan', 'HRD')
                ->get();

            foreach ($hrds as $hrd) {
                Notification::make()
                    ->title('Pengajuan Cuti Baru')
                    ->body("{$user->name} mengajukan cuti.")
                    ->icon('heroicon-o-calendar-days')
                    ->sendToDatabase($hrd);
            }

        } elseif ($record->isApproved()) {
            Notification::make()
                ->title('Cuti Disetujui Otomatis')
                ->success()
                ->sendToDatabase($user);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
