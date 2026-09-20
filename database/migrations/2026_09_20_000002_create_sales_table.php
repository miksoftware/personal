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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('category')->default('equipo'); // equipo, accesorio, servicio, otro
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('warranty')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('sale_date');
            $table->string('status')->default('pendiente'); // pendiente, pagado
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('payment_method')->nullable(); // efectivo, nequi, bancolombia, transferencia
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'sale_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('sale_id')->nullable()->after('license_id')->constrained('sales')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'sale_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['sale_id']);
                $table->dropColumn('sale_id');
            });
        }

        Schema::dropIfExists('sales');
    }
};
