<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Area de pouso da carga vinda do sistema procedural gestaocedec.
 *
 * Cada linha das tabelas aju_* do MySQL entra aqui como documento jsonb, sem
 * normalizacao. Isso permite extrair as tabelas com um unico comando, sem
 * decidir schema antes de olhar o dado, e refazer a extracao sem reimportar:
 * o refino subsequente e SQL sobre o documento, testavel dentro do proprio
 * Postgres, sem o MySQL no circuito.
 *
 * Tabela transitoria. Depois do corte, ela e a conexao legada saem juntas.
 *
 * btree_gist e pre-requisito das constraints EXCLUDE das fases seguintes
 * (vigencia de decreto, slot de agendamento). citext atende login e e-mail
 * case-insensitive sem LOWER() em todo WHERE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        Schema::create('ajuda_h_legado_raw', function (Blueprint $table): void {
            $table->id();
            $table->string('tabela', 64)->comment('Nome da tabela aju_* de origem');
            $table->string('pk_legado', 64)->comment('Chave primaria da linha na origem');
            $table->jsonb('doc')->comment('A linha inteira do legado, como veio');
            $table->timestampTz('extraido_em')->useCurrent();

            $table->unique(['tabela', 'pk_legado'], 'ajuda_h_legado_raw_origem_unique');
            $table->index('tabela', 'ajuda_h_legado_raw_tabela_idx');
        });

        DB::statement(
            'CREATE INDEX ajuda_h_legado_raw_doc_idx ON ajuda_h_legado_raw USING gin (doc jsonb_path_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ajuda_h_legado_raw');
    }
};
