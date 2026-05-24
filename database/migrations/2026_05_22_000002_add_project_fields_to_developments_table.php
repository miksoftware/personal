<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            $table->string('type')->default('mejora')->after('client_id'); // 'mejora' | 'proyecto'
            $table->date('started_at')->nullable()->after('paid_at');
            $table->date('estimated_end_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('developments', function (Blueprint $table) {
            $table->dropColumn(['type', 'started_at', 'estimated_end_at']);
        });
    }
};
