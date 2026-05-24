<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            // Billing cycle for soporte contracts (mensual, trimestral, semestral, anual)
            $table->string('billing_cycle')->nullable()->after('contract_months');
        });
    }

    public function down(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
};
