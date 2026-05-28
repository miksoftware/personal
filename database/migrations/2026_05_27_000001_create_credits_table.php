<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->string('creditor_name');               // Nombre del acreedor/proveedor
            $table->string('description');                  // Qué se compró (ej. "Portátil ASUS")
            $table->decimal('total_amount', 12, 2);         // Monto total del crédito
            $table->string('status')->default('activo');     // 'activo' | 'pagado' | 'cancelado'
            $table->date('credit_date');                     // Fecha de adquisición del crédito
            $table->text('notes')->nullable();               // Notas adicionales
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
