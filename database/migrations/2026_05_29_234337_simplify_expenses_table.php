<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop method if exists
        if (Schema::hasColumn('expenses', 'method')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('method');
            });
        }

        // Drop foreign key if exists
        $foreignKeys = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = 'expenses' AND COLUMN_NAME = 'bank_account_id' 
             AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        if (!empty($foreignKeys)) {
            Schema::table('expenses', function (Blueprint $table) use ($foreignKeys) {
                $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
            });
        }

        // Final step: change column and add new foreign key
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable(false)->change();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->string('method')->after('amount')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable()->change();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
        });
    }
};
