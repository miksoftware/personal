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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('linked_client_id')
                ->nullable()
                ->after('is_active')
                ->constrained('clients')
                ->nullOnDelete();
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->string('standard_billing_cycle')->nullable()->after('next_setup_billing_date');
            $table->decimal('standard_monthly_fee', 10, 2)->nullable()->after('standard_billing_cycle');
            $table->decimal('standard_setup_fee', 10, 2)->nullable()->after('standard_monthly_fee');
            $table->date('standard_next_setup_billing_date')->nullable()->after('standard_setup_fee');
        });

        Schema::create('license_user_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('custom_client_name');
            $table->timestamps();

            $table->unique(['license_id', 'user_id']);
        });

        // Auto-link any existing SaaS user whose name matches an admin's reseller client uniquely
        $nonAdminUsers = DB::table('users')
            ->where('role', '!=', 'admin')
            ->whereNull('linked_client_id')
            ->get(['id', 'name']);

        foreach ($nonAdminUsers as $user) {
            $needle = mb_strtolower(trim((string) $user->name));
            if ($needle === '') {
                continue;
            }

            $matches = DB::table('clients')
                ->whereRaw('LOWER(name) LIKE ?', [$needle . '%'])
                ->get(['id', 'user_id']);

            if ($matches->count() === 1 && (int) $matches->first()->user_id !== (int) $user->id) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['linked_client_id' => $matches->first()->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_user_overrides');

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn([
                'standard_billing_cycle',
                'standard_monthly_fee',
                'standard_setup_fee',
                'standard_next_setup_billing_date',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['linked_client_id']);
            $table->dropColumn('linked_client_id');
        });
    }
};
