<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Log do refino do legado CISTERNA para o dominio novo.
 *
 * Mesma forma de `compdec_etl_log`. Registra as quatro acoes, nao apenas as
 * falhas: `skipped` por idempotencia e `updated` por reprocesso sao o que
 * permite auditar uma carga de 8 mil beneficiarios e dizer, linha a linha, o
 * que aconteceu com cada uma.
 *
 * Sem FK para o dominio de proposito: a tabela registra justamente as linhas
 * que NAO conseguiram virar registro, e uma FK impediria o registro do erro.
 *
 * O que se espera encontrar aqui depois da carga, ja previsto pela analise do
 * dump (spec 4.6):
 *   - ~485 beneficiarios importados como Duplicado, tombstone que o legado
 *     ja usava com aprovado=5
 *   - 4 registros nao importados por CPF apontando para pessoas diferentes,
 *     erro de digitacao que exige correcao na origem (notas 5.1)
 *   - 65 vistorias de fornecedor e 17 de CEDEC descartadas como reenvio do
 *     mesmo formulario
 *   - ~178 linhas de rel_compdec ignoradas por serem placeholder vazio criado
 *     como efeito colateral pelo legado
 *   - as fotos do imovel de 5.808 beneficiarios registradas apenas como link
 *     do Google Drive, porque nao ha arquivo local (notas 5.6)
 *
 * Tabela transitoria: cai depois da validacao em producao, como a
 * compdec_etl_log.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cisterna_etl_log')) {
            return;
        }

        Schema::create('cisterna_etl_log', function (Blueprint $table): void {
            $table->id();

            $table->string('recurso', 40)
                ->comment('comunidades|lotes|os|beneficiarios|vistorias|itens|notificacoes|midia');

            $table->string('tabela', 64)->comment('Tabela sinc_cisterna* de origem');
            $table->string('pk_legado', 64)->comment('Chave da linha na origem');
            $table->unsignedBigInteger('new_id')->nullable()->comment('Id gerado no dominio novo');

            $table->string('acao', 20)
                ->comment('inserted|updated|skipped|error');

            $table->text('motivo')->nullable();
            $table->jsonb('payload_legado')->nullable()
                ->comment('Linha de origem, para reprocessar sem voltar ao MySQL');

            $table->timestampTz('created_at')->useCurrent();

            $table->index(['recurso', 'acao'], 'cisterna_etl_log_recurso_acao_idx');
            $table->index(['tabela', 'pk_legado'], 'cisterna_etl_log_origem_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE cisterna_etl_log ADD CONSTRAINT cisterna_etl_log_acao_check "
                ."CHECK (acao IN ('inserted', 'updated', 'skipped', 'error'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cisterna_etl_log');
    }
};
