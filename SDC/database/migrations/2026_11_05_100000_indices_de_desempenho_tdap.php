<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indices de desempenho do TDAP (auditoria de 2026-11-05).
 *
 * 1) tdap_crono_viagens.data_aprovacao, PARCIAL (so aprovadas e nao
 *    excluidas): e exatamente o recorte do Export BI do Power BI, que filtra
 *    por periodo de aprovacao e antes varria a tabela inteira.
 *
 * 2) Trigram (pg_trgm, GIN) nas expressoes que as buscas usam --
 *    `UPPER(col) LIKE '%x%'` nao usa indice B-tree. A expressao do indice tem
 *    de ser IDENTICA a da query (ver scopeBuscar de Cronograma, Caminhao e
 *    Prestador); se a busca mudar, o indice tem de mudar junto.
 *
 * CONCURRENTLY: cria sem bloquear escrita na tabela -- seguro em producao
 * mesmo em horario de uso. Exige rodar FORA de transacao, dai o
 * $withinTransaction = false. IF NOT EXISTS deixa a migration reexecutavel
 * se uma tentativa anterior caiu no meio.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /** @var array<string, string> nome => "tabela USING metodo (expressao) [WHERE ...]" */
    private const BTREE = [
        'tdap_crono_viagens_aprovadas_data_aprovacao_idx' =>
            'tdap_crono_viagens (data_aprovacao) WHERE validado = 1 AND deleted_at IS NULL',
    ];

    /** @var array<string, string> */
    private const TRIGRAM = [
        'tdap_cronogramas_numero_trgm_idx'        => 'tdap_cronogramas USING gin (UPPER(numero) gin_trgm_ops)',
        'tdap_caminhoes_placa_trgm_idx'           => 'tdap_caminhoes USING gin (UPPER(placa) gin_trgm_ops)',
        'tdap_caminhoes_placa_normalizada_trgm_idx' => "tdap_caminhoes USING gin (REPLACE(REPLACE(UPPER(placa), '-', ''), ' ', '') gin_trgm_ops)",
        'tdap_prestadores_nome_trgm_idx'          => 'tdap_prestadores USING gin (LOWER(nome) gin_trgm_ops)',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::BTREE as $nome => $definicao) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$nome} ON {$definicao}");
        }

        // pg_trgm e habilitada em 2026_05_05_000001; num banco sem a extensao
        // (permissao negada ao criar) a busca continua funcionando, so sem indice.
        $temTrgm = DB::selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'pg_trgm'") !== null;
        if (! $temTrgm) {
            return;
        }

        foreach (self::TRIGRAM as $nome => $definicao) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$nome} ON {$definicao}");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::BTREE + self::TRIGRAM) as $nome) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$nome}");
        }
    }
};
