<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A unicidade da placa so existia na validacao, nunca no banco.
 *
 * `tdap_caminhoes.placa` tinha apenas `->index()`. A regra vivia no
 * AbstractCaminhaoRequest (`Rule::unique('tdap_caminhoes','placa')`), entao
 * qualquer caminho que nao fosse o formulario -- import, seeder,
 * `Caminhao::create()` direto, ou duas requisicoes concorrentes -- gravava
 * duplicata sem resistencia. O CNPJ do prestador ja tinha ganhado indice
 * parcial em 2026_08_21_100000; a placa ficou para tras.
 *
 * ESCOPO DA UNICIDADE: (prestador_id, placa), nao `placa` sozinha.
 * Levantamento da base antes de decidir: 15 placas duplicadas, 30 linhas. Em 14
 * dos 15 casos e a MESMA placa em prestadores DIFERENTES -- caminhao que trocou
 * de empresa entre contratos, situacao legitima que um unique global proibiria.
 * O 15o caso e duplicacao real (duplo-clique no formulario: duas linhas do
 * prestador 11, criadas com 3 segundos de diferenca).
 *
 * SANEAMENTO: aquele unico par nao da para resolver apagando uma linha, porque
 * os dados ficaram repartidos entre as duas -- a mais antiga levou a vistoria, a
 * mais nova levou as 6 alocacoes em cronograma. Consolidamos na que tem as
 * alocacoes (mexer em vinculo de cronograma mudaria numero pago) e repontamos a
 * vistoria para ela antes de excluir a outra.
 *
 * O saneamento e generico de proposito: nao cita ids fixos, porque dev,
 * homologacao e producao nao tem os mesmos. Ele resolve QUALQUER par
 * (prestador_id, placa) duplicado pelo mesmo criterio -- mantem o registro com
 * mais alocacoes, empatando pelo mais antigo.
 *
 * `down()` derruba o indice mas NAO desfaz o saneamento: nao da para saber quais
 * vistorias foram repontadas depois de outros movimentos na tabela, e recriar
 * duplicata seria voltar a ter o defeito. Mesmo criterio da migration de CNPJ.
 *
 * Idempotente: pode rodar duas vezes sem efeito colateral.
 */
return new class extends Migration
{
    private const INDICE = 'tdap_caminhoes_placa_por_prestador_unq';

    public function up(): void
    {
        $this->consolidarDuplicatasDoMesmoPrestador();

        if (! $this->indiceExiste()) {
            DB::statement(
                'CREATE UNIQUE INDEX '.self::INDICE.
                ' ON tdap_caminhoes (prestador_id, placa) WHERE deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE);
    }

    /**
     * Funde duplicatas de (prestador_id, placa) num registro so.
     *
     * Vencedor = o que tem mais alocacoes em cronograma; empate resolve pelo
     * menor id (o mais antigo). Vistorias e alocacoes dos perdedores sao
     * repontadas para o vencedor, e os perdedores saem por soft delete -- nao
     * por DELETE: as FKs sao `restrictOnDelete` e, mais importante, historico de
     * frota nao se apaga.
     */
    private function consolidarDuplicatasDoMesmoPrestador(): void
    {
        $duplicatas = DB::select(<<<'SQL'
            SELECT prestador_id, placa, ARRAY_AGG(id ORDER BY id) AS ids
              FROM tdap_caminhoes
             WHERE deleted_at IS NULL
             GROUP BY prestador_id, placa
            HAVING COUNT(*) > 1
        SQL);

        foreach ($duplicatas as $grupo) {
            $ids = $this->idsDoGrupo($grupo->ids);

            if (count($ids) < 2) {
                continue;
            }

            $vencedor = $this->escolherVencedor($ids);
            $perdedores = array_values(array_diff($ids, [$vencedor]));

            DB::table('tdap_vistorias')->whereIn('placa_id', $perdedores)->update([
                'placa_id'   => $vencedor,
                'updated_at' => now(),
            ]);

            DB::table('tdap_crono_caminhoes')->whereIn('caminhao_id', $perdedores)->update([
                'caminhao_id' => $vencedor,
                'updated_at'  => now(),
            ]);

            DB::table('tdap_caminhoes')->whereIn('id', $perdedores)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * O registro que fica: mais alocacoes primeiro, empate pelo mais antigo.
     *
     * @param  array<int, int>  $ids
     */
    private function escolherVencedor(array $ids): int
    {
        $row = DB::selectOne(<<<'SQL'
            SELECT c.id
              FROM tdap_caminhoes c
              LEFT JOIN tdap_crono_caminhoes cc
                     ON cc.caminhao_id = c.id AND cc.deleted_at IS NULL
             WHERE c.id = ANY(?)
             GROUP BY c.id
             ORDER BY COUNT(cc.id) DESC, c.id ASC
             LIMIT 1
        SQL, ['{'.implode(',', $ids).'}']);

        return (int) ($row->id ?? $ids[0]);
    }

    /**
     * O driver do Postgres devolve ARRAY_AGG como string `{1,2}`.
     *
     * @return array<int, int>
     */
    private function idsDoGrupo(mixed $agregado): array
    {
        if (is_array($agregado)) {
            return array_map('intval', $agregado);
        }

        $cru = trim((string) $agregado, '{}');

        return $cru === '' ? [] : array_map('intval', explode(',', $cru));
    }

    private function indiceExiste(): bool
    {
        return DB::table('pg_indexes')
            ->where('tablename', 'tdap_caminhoes')
            ->where('indexname', self::INDICE)
            ->exists();
    }
};
