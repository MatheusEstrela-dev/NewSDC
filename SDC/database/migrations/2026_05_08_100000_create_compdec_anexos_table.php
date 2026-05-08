<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F3 - Anexos Legais COMPDEC (spec § 4.6).
 *
 * Tabela de leis, decretos, portarias e regimentos que sustentam a
 * estrutura legal do orgao de defesa civil. Cada registro pode ter
 * um arquivo associado via Spatie Media Library (collection
 * 'anexo_arquivo': PDF/DOC/DOCX/ODT, max 2 MB).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_anexos')) {
            return;
        }

        Schema::create('compdec_anexos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orgao_id')
                ->constrained('compdec_orgaos')
                ->cascadeOnDelete();

            $table->string('tipo', 20)
                ->comment('lei|decreto|portaria|regimento|outros');

            $table->string('titulo', 255);
            $table->text('descricao')->nullable();
            $table->string('numero', 60)->nullable()
                ->comment('GREEN: numero do ato (ex: 12345/2024)');

            $table->date('data_emissao')->nullable();
            $table->date('data_validade')->nullable()
                ->comment('Null = sem validade definida (vigencia indeterminada)');

            $table->string('legacy_arquivo', 255)->nullable()
                ->comment('Path no storage legado durante transicao ETL');

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original em com_anexo para idempotencia ETL');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['orgao_id', 'tipo', 'data_validade']);
            $table->index('legacy_id');
        });

        // CHECK constraint para enum textual (PG)
        DB::statement(
            "ALTER TABLE compdec_anexos ADD CONSTRAINT compdec_anexos_tipo_check "
            ."CHECK (tipo IN ('lei', 'decreto', 'portaria', 'regimento', 'outros'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('compdec_anexos');
    }
};
