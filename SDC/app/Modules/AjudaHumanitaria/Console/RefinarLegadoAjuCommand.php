<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Normaliza a area de pouso para as tabelas destino.
 *
 * Todo o trabalho e SQL dentro do proprio Postgres: nenhuma conexao com o
 * MySQL legado. Isso torna o refino reexecutavel e testavel sem depender da
 * base procedural estar de pe.
 *
 * Todas as etapas sao idempotentes por codigo_legado, entao a carga pode rodar
 * em ciclos ate o corte, convergindo para o mesmo estado.
 *
 * aju_deposito nao tem o mesmo nome de chave nas duas bases (id no dbsdc,
 * id_deposito no gestaocedec_local). O coalesce absorve as duas sem exigir
 * dois caminhos de codigo.
 */
final class RefinarLegadoAjuCommand extends Command
{
    protected $signature = 'legado:aju:refinar {--etapa=* : materiais, depositos, saldos}';

    protected $description = 'Refina ajuda_h_legado_raw para as tabelas destino do estoque';

    private const ETAPAS = ['materiais', 'depositos', 'saldos'];

    public function handle(): int
    {
        $pedidas = (array) $this->option('etapa');
        $etapas  = $pedidas === [] ? self::ETAPAS : array_values(array_intersect(self::ETAPAS, $pedidas));

        if ($etapas === []) {
            $this->error('Nenhuma etapa valida. Use materiais, depositos ou saldos.');

            return self::FAILURE;
        }

        foreach ($etapas as $etapa) {
            $afetadas = match ($etapa) {
                'materiais' => $this->refinarMateriais(),
                'depositos' => $this->refinarDepositos(),
                'saldos'    => $this->refinarSaldos(),
            };

            $this->line(sprintf('%-12s %6d linhas', $etapa, $afetadas));
        }

        return self::SUCCESS;
    }

    private function refinarMateriais(): int
    {
        return DB::affectingStatement(
            "INSERT INTO materiais_ah
                 (nome, descricao, unidade_medida, disponivel_para_pedido, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 nullif(trim(doc->>'descricao'), ''),
                 coalesce(nullif(trim(doc->>'uni_medida'), ''), 'UN'),
                 true,
                 doc->>'id_unidade',
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela = 'aju_unidade'
               AND doc->>'id_unidade' IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome           = EXCLUDED.nome,
                     descricao      = EXCLUDED.descricao,
                     unidade_medida = EXCLUDED.unidade_medida,
                     updated_at     = now()"
        );
    }

    private function refinarDepositos(): int
    {
        return DB::affectingStatement(
            "INSERT INTO ajuda_h_depositos
                 (nome, abreviacao, endereco, ativo, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 coalesce(
                     nullif(trim(doc->>'abreviacao'), ''),
                     'L' || coalesce(doc->>'id_deposito', doc->>'id')
                 ),
                 nullif(trim(doc->>'endereco'), ''),
                 true,
                 coalesce(doc->>'id_deposito', doc->>'id'),
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela = 'aju_deposito'
               AND coalesce(doc->>'id_deposito', doc->>'id') IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome       = EXCLUDED.nome,
                     endereco   = EXCLUDED.endereco,
                     updated_at = now()"
        );
    }

    private function refinarSaldos(): int
    {
        // O saldo do legado entra como um unico movimento de ABERTURA por par
        // material/deposito, com o documento de origem preso em payload_legado.
        // Reexecutar nao duplica: o par (origem_tipo, origem_id) identifica a
        // linha da area de pouso que gerou o movimento.
        DB::affectingStatement(
            "INSERT INTO ajuda_h_estoque_movimentos
                 (material_ah_id, deposito_id, quantidade, tipo,
                  origem_tipo, origem_id, ocorrido_em, payload_legado, created_at)
             SELECT
                 m.id, d.id, (r.doc->>'saldo')::numeric, 'ABERTURA',
                 'ajuda_h_legado_raw', r.id, now(), r.doc, now()
             FROM ajuda_h_legado_raw r
             JOIN materiais_ah      m ON m.codigo_legado = r.doc->>'id_produto'
             JOIN ajuda_h_depositos d ON d.codigo_legado = r.doc->>'id_deposito'
             WHERE r.tabela = 'aju_estoque'
               AND (r.doc->>'saldo') ~ '^-?[0-9]+(\\.[0-9]+)?$'
               AND (r.doc->>'saldo')::numeric <> 0
               AND NOT EXISTS (
                   SELECT 1 FROM ajuda_h_estoque_movimentos em
                   WHERE em.origem_tipo = 'ajuda_h_legado_raw' AND em.origem_id = r.id
               )"
        );

        return DB::affectingStatement(
            'INSERT INTO ajuda_h_estoque_saldos (material_ah_id, deposito_id, saldo, atualizado_em)
             SELECT material_ah_id, deposito_id, sum(quantidade), now()
             FROM ajuda_h_estoque_movimentos
             GROUP BY material_ah_id, deposito_id
             ON CONFLICT (material_ah_id, deposito_id) DO UPDATE
                 SET saldo         = EXCLUDED.saldo,
                     atualizado_em = now()'
        );
    }
}
