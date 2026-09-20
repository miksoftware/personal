<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add role to users table if not present
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('password');
            });
        }

        // Identify the initial admin user ID (or fallback to 1)
        $firstUser = DB::table('users')->orderBy('id')->first();
        if ($firstUser) {
            DB::table('users')->where('id', $firstUser->id)->update(['role' => 'admin']);
            $adminId = $firstUser->id;
        } else {
            $adminId = 1;
        }

        // 2. Add user_id foreign key to all tenant entities and associate existing data
        $tenantTables = [
            'clients',
            'bank_accounts',
            'incomes',
            'expenses',
            'loans',
            'credits',
            'licenses',
            'developments',
            'payments',
            'credit_payments',
            'bank_account_adjustments',
        ];

        foreach ($tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
                });

                // Assign any existing records to the administrator so no data is lost
                DB::table($tableName)->whereNull('user_id')->update(['user_id' => $adminId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tenantTables = [
            'bank_account_adjustments',
            'credit_payments',
            'payments',
            'developments',
            'licenses',
            'credits',
            'loans',
            'expenses',
            'incomes',
            'bank_accounts',
            'clients',
        ];

        foreach ($tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
