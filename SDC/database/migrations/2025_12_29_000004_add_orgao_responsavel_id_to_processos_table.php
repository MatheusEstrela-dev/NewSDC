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
        if (Schema::hasTable('processos')) {
            Schema::table('processos', function (Blueprint $table) {
                $table->foreignId('orgao_responsavel_id')
                    ->nullable()
                    ->after('municipio_id')
                    ->constrained('orgaos')
                    ->nullOnDelete()
                    ->comment('COMPDEC responsável pelo processo');

                $table->index('orgao_responsavel_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('processos')) {
            Schema::table('processos', function (Blueprint $table) {
                $table->dropForeign(['orgao_responsavel_id']);
                $table->dropColumn('orgao_responsavel_id');
            });
        }
    }
};
