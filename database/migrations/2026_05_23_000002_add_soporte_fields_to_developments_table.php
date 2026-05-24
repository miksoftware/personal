<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            // Link adicionales to a parent development
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('developments')
                ->onDelete('set null');

            // Soporte mensual fields
            $table->decimal('monthly_fee', 10, 2)->nullable()->after('amount');
            $table->unsignedTinyInteger('contract_months')->nullable()->after('monthly_fee');
        });
    }

    public function down(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'monthly_fee', 'contract_months']);
        });
    }
};
