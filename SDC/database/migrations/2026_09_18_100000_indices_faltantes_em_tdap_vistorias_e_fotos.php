<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indices que faltavam no submodulo Frota + Vistorias.
 *
 * Nenhuma linha de dado e tocada aqui -- so indice.
 *
 * 1. FK SEM INDICE. No Postgres, criar uma foreign key NAO cria indice na
 *    tabela que referencia. As tres FKs abaixo sao `ON DELETE SET NULL`, e o
 *    banco precisa varrer a tabela inteira a cada DELETE em `users` para achar
 *    as linhas a atualizar. `tdap_vistorias.user_id` ja tinha (criado a mao com
 *    sufixo `_fkidx`); `tdap_vistoria_fotos.uploaded_by` e
 *    `tdap_historicos.user_id` nao.
 *
 * 2. ORDENACAO DA LISTAGEM DE VISTORIAS. VistoriaService::listar ordena por
 *    `data DESC, id DESC` sem filtro obrigatorio de placa ou parecer, e nenhum
 *    dos dois compostos existentes serve: em `(placa_id, data)` e
 *    `(parecer, data)` a coluna lider e outra. Com 116 linhas isso nao doi;
 *    o indice entra agora porque o volume so cresce e o custo dele e uma
 *    consulta de DDL.
 *
 * `CREATE INDEX IF NOT EXISTS` e idempotente e dispensa a checagem manual em
 * `pg_indexes` que as migrations vizinhas fazem. Sem CONCURRENTLY: as tabelas
 * sao pequenas (116 e 0 linhas) e CONCURRENTLY nao roda dentro da transacao
 * que o migrator abre.
 */
return new class extends Migration
{
    /** @var array<string, string> nome do indice => definicao */
    private const INDICES = [
        'tdap_vistoria_fotos_uploaded_by_index' => 'CREATE INDEX IF NOT EXISTS tdap_vistoria_fotos_uploaded_by_index ON tdap_vistoria_fotos (uploaded_by)',
        'tdap_historicos_user_id_index'         => 'CREATE INDEX IF NOT EXISTS tdap_historicos_user_id_index ON tdap_historicos (user_id)',
        'tdap_vistorias_data_index'             => 'CREATE INDEX IF NOT EXISTS tdap_vistorias_data_index ON tdap_vistorias (data DESC, id DESC)',
    ];

    public function up(): void
    {
        // Indices parciais e `IF NOT EXISTS` sao especificos do Postgres, que e
        // o banco do projeto. Em outro driver a migration nao faz nada, em vez
        // de estourar -- mesma guarda da migration de CHECK das vistorias.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::INDICES as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::INDICES) as $indice) {
            DB::statement("DROP INDEX IF EXISTS {$indice}");
        }
    }
};
