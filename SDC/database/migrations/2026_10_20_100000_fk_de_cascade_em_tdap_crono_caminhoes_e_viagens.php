<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recria as duas FKs de cascade da cadeia cronograma -> alocacao -> viagem.
 *
 * As migrations 2026_05_14_140002 e 140003 declaram
 * `tdap_crono_caminhoes.cronograma_id` e `tdap_crono_viagens.crono_caminhao_id`
 * com ON DELETE CASCADE, mas as duas constraints nunca chegaram ao banco. Mesma
 * causa das FKs de ponto de captacao e de municipio (2026_09_18_*): o dump de
 * producao traz a FK e, no mesmo arquivo, linhas que a violam; o pg_restore
 * aborta o ADD CONSTRAINT, registra o erro e segue. Sem a FK, apagar um
 * cronograma deixa alocacoes e viagens para tras.
 *
 * ESTADO ENCONTRADO (dev, recarga do dump de producao, 2026-10-20):
 *   - 188 alocacoes ativas de 17 cronogramas que nao existem (ids 2 a 39,
 *     criadas entre jul e ago/2025), sem nenhuma viagem individual;
 *   - 303 viagens ativas de 56 alocacoes que nao existem, 206 delas aprovadas.
 *     Os pais existiam quando as viagens foram aprovadas: foram apagados
 *     depois, por DELETE fora da aplicacao (ela so faz soft delete).
 *
 * NADA E APAGADO SEM COPIA. As orfas saem das tabelas vivas para
 * `tdap_orfaos_arquivados`, com a linha inteira em jsonb -- registro de
 * entrega de agua tem efeito financeiro e pode ser preciso reconstitui-lo.
 * jsonb, e nao uma copia da estrutura, para que coluna nova nas tabelas vivas
 * nao quebre o arquivo. Para devolver uma linha:
 *   INSERT INTO tdap_crono_viagens
 *   SELECT (jsonb_populate_record(NULL::tdap_crono_viagens, dados)).*
 *     FROM tdap_orfaos_arquivados WHERE tabela = 'tdap_crono_viagens' AND ...
 * (o pai precisa voltar antes, ou a FK recusa).
 *
 * Para a aplicacao nada muda: todas as consultas chegam a essas linhas por
 * JOIN com o pai, entao as orfas ja eram invisiveis.
 *
 * Generica de proposito: nenhum id fixo, porque dev, homologacao e producao
 * nao tem os mesmos. Idempotente: pode rodar duas vezes sem efeito colateral,
 * inclusive em banco que ja tem as FKs.
 */
return new class extends Migration
{
    private const ARQUIVO = 'tdap_orfaos_arquivados';

    /**
     * Ordem importa: alocacoes antes de viagens. A viagem de uma alocacao
     * arquivada fica orfa nesse momento e cai no segundo passo.
     */
    private const FKS = [
        'tdap_crono_caminhoes_cronograma_id_foreign' => [
            'tabela' => 'tdap_crono_caminhoes',
            'coluna' => 'cronograma_id',
            'pai' => 'tdap_cronogramas',
        ],
        'tdap_crono_viagens_crono_caminhao_id_foreign' => [
            'tabela' => 'tdap_crono_viagens',
            'coluna' => 'crono_caminhao_id',
            'pai' => 'tdap_crono_caminhoes',
        ],
    ];

    public function up(): void
    {
        $this->criarArquivo();

        foreach (self::FKS as $fk => $ref) {
            $this->arquivarOrfas($ref['tabela'], $ref['coluna'], $ref['pai']);

            if (! $this->fkExiste($fk)) {
                DB::statement(
                    "ALTER TABLE {$ref['tabela']} ADD CONSTRAINT {$fk}".
                    " FOREIGN KEY ({$ref['coluna']}) REFERENCES {$ref['pai']} (id)".
                    ' ON UPDATE CASCADE ON DELETE CASCADE'
                );
            }
        }
    }

    /**
     * Derruba so as constraints. NAO devolve as orfas nem apaga o arquivo:
     * eram referencias quebradas, e recoloca-las seria reintroduzir o defeito.
     * Mesmo criterio das migrations de ponto de captacao e de municipio.
     */
    public function down(): void
    {
        foreach (array_reverse(self::FKS, true) as $fk => $ref) {
            DB::statement("ALTER TABLE {$ref['tabela']} DROP CONSTRAINT IF EXISTS {$fk}");
        }
    }

    private function criarArquivo(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tdap_orfaos_arquivados (
                id                 bigserial   PRIMARY KEY,
                tabela             varchar(60) NOT NULL,
                registro_id        bigint      NOT NULL,
                coluna             varchar(60) NOT NULL,
                referencia_ausente bigint      NULL,
                dados              jsonb       NOT NULL,
                arquivado_em       timestamptz NOT NULL DEFAULT now()
            )
        SQL);

        // Sem UNIQUE de proposito: com ele, um conflito obrigaria a escolher
        // entre abortar e descartar a linha ja apagada da tabela viva.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS tdap_orfaos_arquivados_tabela_registro_index'.
            ' ON '.self::ARQUIVO.' (tabela, registro_id)'
        );

        DB::statement(
            'COMMENT ON TABLE '.self::ARQUIVO.' IS '.
            "'Linhas de tdap_crono_caminhoes/tdap_crono_viagens retiradas por referenciar pai inexistente "
            ."(migration 2026_10_20_100000). dados = linha integra em jsonb.'"
        );
    }

    /**
     * Move, num unico comando, as linhas cujo pai nao existe. Inclui as
     * soft-deleted: a FK vale para todas as linhas, nao so as visiveis.
     */
    private function arquivarOrfas(string $tabela, string $coluna, string $pai): void
    {
        DB::statement(<<<SQL
            WITH movidas AS (
                DELETE FROM {$tabela} t
                 WHERE t.{$coluna} IS NOT NULL
                   AND NOT EXISTS (SELECT 1 FROM {$pai} p WHERE p.id = t.{$coluna})
                RETURNING t.*
            )
            INSERT INTO tdap_orfaos_arquivados (tabela, registro_id, coluna, referencia_ausente, dados)
            SELECT '{$tabela}', m.id, '{$coluna}', m.{$coluna}, to_jsonb(m)
              FROM movidas m
        SQL);
    }

    private function fkExiste(string $fk): bool
    {
        return DB::table('pg_constraint')
            ->where('conname', $fk)
            ->where('contype', 'f')
            ->exists();
    }
};
