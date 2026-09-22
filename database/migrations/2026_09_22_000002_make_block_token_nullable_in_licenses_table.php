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
        if (Schema::hasTable('licenses') && Schema::hasColumn('licenses', 'block_token')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->string('block_token')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('licenses') && Schema::hasColumn('licenses', 'block_token')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->string('block_token')->nullable(false)->change();
            });
        }
    }
};
