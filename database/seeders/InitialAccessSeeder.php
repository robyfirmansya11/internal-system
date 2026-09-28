<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialAccessSeeder extends Seeder
{
    public function run(): void
    {
        $departmentIds = collect(['IT', 'HRD', 'Finance, Accounting & Tax Department', 'Legal'])
            ->mapWithKeys(function (string $name): array {
                $department = Department::withTrashed()->firstOrCreate(['nama_department' => $name]);

                if ($department->trashed()) {
                    $department->restore();
                }

                return [$name => $department->id];
            });

        $accounts = [
            'roby.firmansyah@insys.com' => ['name' => 'Roby Firmansyah', 'level' => Role::Superadmin, 'jabatan' => 'IT', 'departments' => ['IT'], 'atasan' => null],
            'irene.novita@insys.com' => ['name' => 'Irene Novita', 'level' => Role::Admin, 'jabatan' => 'HRD', 'departments' => ['HRD'], 'atasan' => 'ida.sumarsih@insys.com'],
            'summer.geng@insys.com' => ['name' => 'Summer Geng', 'level' => Role::Superuser, 'jabatan' => 'Finance Manager', 'departments' => ['Finance, Accounting & Tax Department'], 'atasan' => null],
            'ida.sumarsih@insys.com' => ['name' => 'Ida Sumarsih', 'level' => Role::Superuser, 'jabatan' => 'Manager', 'departments' => ['IT', 'HRD', 'Legal'], 'atasan' => null],
            'simon.willy@insys.com' => ['name' => 'Simon Willy', 'level' => Role::User, 'jabatan' => 'Staff', 'departments' => ['Finance, Accounting & Tax Department'], 'atasan' => 'summer.geng@insys.com'],
            'audri.sesmita@insys.com' => ['name' => 'Audri Sesmita', 'level' => Role::User, 'jabatan' => 'Staff', 'departments' => ['Legal'], 'atasan' => 'ida.sumarsih@insys.com'],
            'mega.susanti@insys.com' => ['name' => 'Mega Susanti', 'level' => Role::User, 'jabatan' => 'Staff', 'departments' => ['Finance, Accounting & Tax Department'], 'atasan' => 'summer.geng@insys.com'],
        ];

        $createdCredentials = [];

        foreach ($accounts as $email => $account) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $password = Str::password(20, symbols: false);
                $user = new User([
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $createdCredentials[] = [$email, $password];
            }

            $user->fill([
                'name' => $account['name'],
                'level' => $account['level'],
                'jabatan' => $account['jabatan'],
            ]);
            $user->save();

            $user->departments()->sync(
                collect($account['departments'])->mapWithKeys(fn (string $department, int $index): array => [
                    $departmentIds[$department] => [
                        'jabatan' => $account['jabatan'],
                        'is_primary' => $index === 0,
                    ],
                ])->all()
            );
        }

        foreach ($accounts as $email => $account) {
            $user = User::where('email', $email)->firstOrFail();
            $managerId = $account['atasan']
                ? User::where('email', $account['atasan'])->value('id')
                : null;

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['atasan_id' => $managerId, 'status_karyawan' => 'Active']
            );
        }

        $this->command?->info('Initial departments and users are ready.');

        if ($createdCredentials !== []) {
            $this->command?->warn('Temporary passwords — distribute them privately and change them after first login.');
            $this->command?->table(['Email', 'Temporary password'], $createdCredentials);
        }
    }
}
