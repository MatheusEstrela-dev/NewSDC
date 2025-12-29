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
        if (Schema::hasTable('rats')) {
            Schema::table('rats', function (Blueprint $table) {
                $table->foreignId('orgao_emissor_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('orgaos')
                    ->nullOnDelete()
                    ->comment('Órgão que emitiu o RAT');

                $table->index('orgao_emissor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rats')) {
            Schema::table('rats', function (Blueprint $table) {
                $table->dropForeign(['orgao_emissor_id']);
                $table->dropColumn('orgao_emissor_id');
            });
        }
    }
};
