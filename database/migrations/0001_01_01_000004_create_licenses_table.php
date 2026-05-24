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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('block_token');
            $table->string('status'); // 'activa', 'suspendida', 'vencida'
            $table->string('billing_cycle'); // 'mensual', 'trimestral', 'semestral', 'anual'
            $table->decimal('monthly_fee', 10, 2);
            $table->date('next_billing_date');
            $table->boolean('is_free')->default(false); // 5th license business rule
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
