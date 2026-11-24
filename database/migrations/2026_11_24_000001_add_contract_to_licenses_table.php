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
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('contract_path')->nullable()->after('is_free');
            $table->string('contract_original_name')->nullable()->after('contract_path');
            $table->timestamp('contract_uploaded_at')->nullable()->after('contract_original_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn([
                'contract_path',
                'contract_original_name',
                'contract_uploaded_at',
            ]);
        });
    }
};
