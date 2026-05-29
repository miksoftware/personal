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
        Schema::table('credits', function (Blueprint $table) {
            $table->enum('type', ['proveedor', 'personal'])->default('proveedor')->after('client_id');
            $table->decimal('installment_value', 12, 2)->nullable()->after('total_amount');
            $table->integer('total_installments')->nullable()->after('installment_value');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn(['type', 'installment_value', 'total_installments']);
        });
    }
};
