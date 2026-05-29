<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('license_id')->nullable()->after('development_id')->constrained()->onDelete('set null');
            $table->string('license_payment_type')->nullable()->after('license_id'); // 'mensualidad' | 'instalacion'
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['license_id']);
            $table->dropColumn(['license_id', 'license_payment_type']);
        });
    }
};
