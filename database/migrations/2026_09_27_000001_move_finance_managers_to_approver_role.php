<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance Managers approve financial documents; they are not routine
     * HR/Finance Operations processors. Preserve existing accounts while
     * moving the position to the management approver role.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('jabatan', 'Finance Manager')
            ->where('level', Role::Admin->value)
            ->update([
                'level' => Role::Superuser->value,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('jabatan', 'Finance Manager')
            ->where('level', Role::Superuser->value)
            ->update([
                'level' => Role::Admin->value,
                'updated_at' => now(),
            ]);
    }
};
