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
                ->title('Department Not Found')
                ->body('Your account is not assigned to any department. Please contact the HR or System Administrator.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi overlap
        $overlap = FormCuti::where('user_id', $user->id)
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
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
                ->title('Overlapping Leave Request')
                ->body('The selected leave dates overlap with an existing leave request.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validasi jenis cuti
        $jenis = $data['jenis_cuti'] ?? null;
        $jumlahHari = (int) ($data['jumlah_hari'] ?? 0);

        $errorJenis = match (true) {
            $jenis === 'Cuti Haid' && $jumlahHari > 2 => 'Menstrual Leave is limited to a maximum of 2 days.',
            $jenis === 'Cuti Khusus' && ($jumlahHari < 1 || $jumlahHari > 3) => 'Special Leave can only be requested for 1 to 3 days.',
            $jenis === 'Cuti Melahirkan' && $jumlahHari > 90 => 'Maternity Leave is limited to a maximum of 90 days.',
            $jenis === 'Cuti Keguguran' && $jumlahHari > 45 => 'Miscarriage Leave is limited to a maximum of 45 days.',
            default => null,
        };

        if ($errorJenis) {
            Notification::make()
                ->title('Validation Failed')
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
                    ->title('Leave Balance Not Found')
                    ->body('No leave balance has been assigned for the selected year.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            if ($kuota->sisa_cuti < $jumlahHari) {
                Notification::make()
                    ->title('Insufficient Leave Balance')
                    ->body("Available balance: {$kuota->sisa_cuti} day(s). Requested: {$jumlahHari} day(s).")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        if ($user->jabatan === FormCuti::LEVEL2_JABATAN && ! $user->profile?->atasan) {
            Notification::make()
                ->title('Manager Not Found')
                ->body('HRD leave requests must be approved by a direct manager. Please assign a manager first.')
                ->danger()
                ->send();

            $this->halt();
        }

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'status' => 'Pending Approval',
            'approval_level' => FormCuti::initialApprovalLevel($user),
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
                    ->title('New Leave Request')
                    ->body("{$user->name} has submitted a leave request.")
                    ->icon('heroicon-o-calendar-days')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingFinanceManager()) {
            $financeManagers = User::where('jabatan', FormCuti::FINANCE_MANAGER_JABATAN)->get();

            foreach ($financeManagers as $financeManager) {
                Notification::make()
                    ->title('New Leave Request')
                    ->body("{$user->name}'s leave request requires Finance Manager approval.")
                    ->icon('heroicon-o-calendar-days')
                    ->sendToDatabase($financeManager);
            }
        } elseif ($record->isWaitingAdmin()) {
            $hrds = User::where('level', Role::Admin)
                ->where('jabatan', 'HRD')
                ->get();

            foreach ($hrds as $hrd) {
                Notification::make()
                    ->title('New Leave Request')
                    ->body("{$user->name} has submitted a leave request.")
                    ->icon('heroicon-o-calendar-days')
                    ->sendToDatabase($hrd);
            }

        } elseif ($record->isApproved()) {
            Notification::make()
                ->title('Leave Request Approved')
                ->success()
                ->sendToDatabase($user);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
