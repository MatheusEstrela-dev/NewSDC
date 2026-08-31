<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Higiene de indices em torno de municipios.
 *
 * municipios e uma tabela de dimensao pequena: 853 linhas, 344 kB. Ela cabe
 * inteira em shared_buffers e nao e, por si, gargalo de leitura. O custo real
 * esta em duas coisas que esta migration corrige.
 *
 * 1. Indice duplicado. codigo_ibge tinha btree simples E btree unique, 40 kB
 *    cada, cobrindo exatamente as mesmas consultas. O simples e mantido a cada
 *    INSERT e UPDATE sem nunca ser a melhor escolha do planejador, ja que o
 *    unique atende tudo que ele atenderia. Sai.
 *
 * 2. Coluna filha de FK sem indice. O Postgres nao indexa automaticamente a
 *    coluna que aponta para a chave estrangeira, so o lado referenciado. Com 21
 *    tabelas apontando para municipios, cada DELETE ou UPDATE de chave em
 *    municipios precisa provar que nao restou filho em cada uma delas; onde
 *    falta indice, isso e varredura completa. Hoje as tabelas envolvidas sao
 *    pequenas e o efeito e imperceptivel, mas o custo cresce junto com elas.
 *
 * As tabelas ajuda_h_depositos e ajuda_h_fornecedores recebem o mesmo indice na
 * propria migration de criacao, e nao aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS municipios_codigo_ibge_index');

        DB::statement(
            'CREATE INDEX IF NOT EXISTS pedido_ah_agendamentos_municipio_idx
                ON pedido_ah_agendamentos (municipio_id)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS pedido_ah_agendamentos_municipio_idx');

        DB::statement(
            'CREATE INDEX IF NOT EXISTS municipios_codigo_ibge_index ON municipios (codigo_ibge)'
        );
    }
};
