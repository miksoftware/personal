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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['recibido', 'entregado']); // recibido (me prestaron), entregado (yo presté)
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('loan_date');
            $table->enum('status', ['pendiente', 'devuelto', 'canjeado'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
