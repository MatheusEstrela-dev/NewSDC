<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F4 - Plano de Contingencia COMPDEC (spec § 4.7).
 *
 * Cada orgao COMPDEC tem N planos versionados, sendo no maximo 1 ativo
 * por vez (enforce no banco via partial unique index). O plano ativo
 * fica refletido em compdec_orgaos.tem_plano_contingencia via Observer.
 *
 * Fluxo:
 *   - upload cria plano em status pendente
 *   - "ativar" marca este como ativo e desativa outros (Observer)
 *   - "aprovar" registra aprovado_em + aprovado_por (User CEDEC)
 *   - permission aprovar e separada de edit
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_planos_contingencia')) {
            return;
        }

        Schema::create('compdec_planos_contingencia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orgao_id')
                ->constrained('compdec_orgaos')
                ->cascadeOnDelete();

            $table->string('versao', 40)
                ->comment('Ex: v1.0, 2024.1, etc');

            $table->unsignedBigInteger('tamanho_bytes')->nullable()
                ->comment('Cache do tamanho do arquivo Spatie para evitar HEAD requests');

            $table->text('observacoes')->nullable();

            $table->boolean('ativo')->default(false);

            $table->timestamp('aprovado_em')->nullable();
            $table->foreignId('aprovado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('legacy_arquivo', 255)->nullable()
                ->comment('Path no storage legado (com_plano_upload) durante transicao ETL');

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original em com_plano_upload para idempotencia ETL');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['orgao_id', 'ativo', 'created_at']);
            $table->index('legacy_id');
        });

        // PARTIAL UNIQUE: 1 plano ativo por orgao (PG-only)
        DB::statement(
            'CREATE UNIQUE INDEX compdec_planos_unico_ativo_por_orgao '
            .'ON compdec_planos_contingencia (orgao_id) '
            .'WHERE ativo = true AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS compdec_planos_unico_ativo_por_orgao');
        Schema::dropIfExists('compdec_planos_contingencia');
    }
};
