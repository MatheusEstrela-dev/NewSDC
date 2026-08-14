<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Area de pouso da carga vinda do modulo CISTERNA do legado `sdc`.
 *
 * Mesma abordagem de `ajuda_h_legado_raw`: cada linha das tabelas
 * sinc_cisterna* do MySQL entra aqui como documento jsonb, sem normalizacao.
 * Isso permite extrair tudo com um unico comando, sem decidir schema antes de
 * olhar o dado, e refazer a extracao sem reimportar -- o refino subsequente e
 * SQL sobre o documento, testavel dentro do proprio Postgres, sem o MySQL no
 * circuito.
 *
 * Entra na etapa de banco, junto do dominio, e nao na etapa de ETL: a
 * homologacao comeca pela carga crua, antes de existir service ou tela.
 *
 * Volume esperado, medido no dump de producao (spec 4.6.1): 8.105 linhas de
 * sinc_cisterna, 885 de sinc_cisterna_com, 858 / 856 / 675 das tres tabelas de
 * relatorio, mais lotes, ordens de servico e notificacoes. Cerca de 11,4 mil
 * documentos.
 *
 * `pk_legado` e varchar, nao bigint: o legado tem tabela com PK nao numerica, e
 * um varchar aceita qualquer origem sem a extracao precisar saber o tipo.
 *
 * Tabela transitoria. Depois do corte, ela e a conexao legada saem juntas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('cisterna_legado_raw')) {
            return;
        }

        Schema::create('cisterna_legado_raw', function (Blueprint $table): void {
            $table->id();
            $table->string('tabela', 64)->comment('Nome da tabela sinc_cisterna* de origem');
            $table->string('pk_legado', 64)->comment('Chave primaria da linha na origem');
            $table->jsonb('doc')->comment('A linha inteira do legado, como veio');
            $table->timestampTz('extraido_em')->useCurrent();

            $table->unique(['tabela', 'pk_legado'], 'cisterna_legado_raw_origem_unique');
            $table->index('tabela', 'cisterna_legado_raw_tabela_idx');
        });

        // jsonb_path_ops: menor e mais rapido que o GIN padrao para consulta de
        // contencao, que e o uso aqui (o refino pergunta pelas chaves do doc).
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_legado_raw_doc_idx '
            .'ON cisterna_legado_raw USING gin (doc jsonb_path_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cisterna_legado_raw');
    }
};
