<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Retencao;

use App\Support\Database\ConexaoGeo;
use App\Support\Database\Geo\DominioGeo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;

/**
 * Retencao das leituras silver (INMET e Cemaden): fica `retencao_silver_meses`
 * no Postgres; o que passa disso vai para Parquet e sai do banco.
 *
 * - Banco dedicado (tabela particionada por mes): exporta a particao vencida,
 *   DETACH e DROP. Custo de metadado, sem DELETE nem vacuum.
 * - sdc (tabela sem particao): exporta o mes e remove por medido_em em lotes.
 *
 * O descarte so acontece depois da exportacao confirmada, e o mes e a unidade
 * nos dois modos, entao o Parquet e identico independente de onde rodou.
 */
final class RetencaoSilver
{
    private const TABELAS = ['leituras_inmet', 'leituras_cemaden'];

    private const LOTE_EXPORTACAO = 20000;

    private const LOTE_REMOCAO = 5000;

    /** Particoes futuras mantidas prontas no banco dedicado. */
    private const MESES_A_FRENTE = 2;

    public function __construct(private readonly ArquivadorParquetSilver $arquivador) {}

    /**
     * @return list<array{tabela: string, mes: string, linhas: int, arquivo: string|null}>
     */
    public function executar(?CarbonInterface $agora = null): array
    {
        $db = ConexaoGeo::conexao();
        $corte = CarbonImmutable::instance($agora ?? now())->utc()->startOfMonth()
            ->subMonths((int) config('medalhao.retencao_silver_meses', 12));

        $resumo = [];

        foreach (self::TABELAS as $nome) {
            $tabela = DominioGeo::Meteorologia->tabela('silver', $nome);
            $particionada = $this->particionada($db, $tabela);

            foreach ($this->mesesVencidos($db, $tabela, $nome, $particionada, $corte) as $mes) {
                $resumo[] = $this->descartarMes($db, $tabela, $nome, $mes, $particionada);
            }
        }

        // Repoe as particoes futuras junto da poda: uma rotina diaria so.
        if ($this->particionada($db, DominioGeo::Meteorologia->tabela('silver', self::TABELAS[0]))) {
            $db->selectOne(sprintf('SELECT %s.garantir_particoes(?)', DominioGeo::Meteorologia->silver()), [self::MESES_A_FRENTE]);
        }

        return $resumo;
    }

    /**
     * @return array{tabela: string, mes: string, linhas: int, arquivo: string|null}
     */
    private function descartarMes(Connection $db, string $tabela, string $nome, CarbonImmutable $mes, bool $particionada): array
    {
        $de = $mes->format('Y-m-d H:i:sP');
        $ate = $mes->addMonth()->format('Y-m-d H:i:sP');

        $total = (int) $db->selectOne(
            "SELECT count(*) AS n FROM {$tabela} WHERE medido_em >= ? AND medido_em < ?",
            [$de, $ate],
        )->n;

        $arquivo = null;

        if ($total > 0) {
            $gravado = $this->arquivador->arquivar(
                $nome,
                $mes->format('Y-m'),
                $this->colunas($db, $tabela),
                $this->lotes($db, $tabela, $de, $ate),
            );

            // Contagem do arquivo diferente da tabela: nada e descartado.
            if ($gravado['linhas'] !== $total) {
                throw new \RuntimeException("Parquet de {$nome} {$mes->format('Y-m')} tem {$gravado['linhas']} linha(s), a tabela tem {$total}.");
            }

            $arquivo = $gravado['arquivo'];
        }

        // So chega aqui com o Parquet confirmado (ou mes sem linhas).
        $particionada
            ? $this->soltarParticao($db, $tabela, $nome, $mes)
            : $this->removerEmLotes($db, $tabela, $de, $ate);

        return ['tabela' => $tabela, 'mes' => $mes->format('Y-m'), 'linhas' => $total, 'arquivo' => $arquivo];
    }

    /**
     * @return list<CarbonImmutable> primeiros dias dos meses inteiramente antes do corte, do mais antigo
     */
    private function mesesVencidos(Connection $db, string $tabela, string $nome, bool $particionada, CarbonImmutable $corte): array
    {
        $meses = $particionada
            ? $this->mesesDasParticoes($db, $tabela, $nome)
            : array_map(
                static fn (object $l): string => (string) $l->mes,
                $db->select(
                    "SELECT DISTINCT to_char(medido_em AT TIME ZONE 'UTC', 'YYYY-MM') AS mes FROM {$tabela} WHERE medido_em < ? ORDER BY 1",
                    [$corte->format('Y-m-d H:i:sP')],
                ),
            );

        $vencidos = [];
        foreach ($meses as $mes) {
            $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$mes}-01 00:00:00", 'UTC');

            // Mes vence quando termina no corte ou antes.
            if ($inicio->addMonth() <= $corte) {
                $vencidos[] = $inicio;
            }
        }

        usort($vencidos, static fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        return $vencidos;
    }

    /** @return list<string> 'YYYY-MM' das particoes nomeadas <tabela>_YYYY_MM (a default fica de fora) */
    private function mesesDasParticoes(Connection $db, string $tabela, string $nome): array
    {
        $filhas = $db->select(
            'SELECT c.relname FROM pg_inherits i JOIN pg_class c ON c.oid = i.inhrelid WHERE i.inhparent = to_regclass(?)',
            [$tabela],
        );

        $meses = [];
        foreach ($filhas as $filha) {
            if (preg_match('/^'.preg_quote($nome, '/').'_(\d{4})_(\d{2})$/', $filha->relname, $m) === 1) {
                $meses[] = "{$m[1]}-{$m[2]}";
            }
        }

        return $meses;
    }

    private function particionada(Connection $db, string $tabela): bool
    {
        $tipo = $db->selectOne('SELECT relkind::text AS k FROM pg_class WHERE oid = to_regclass(?)', [$tabela]);

        return $tipo !== null && $tipo->k === 'p';
    }

    private function soltarParticao(Connection $db, string $tabela, string $nome, CarbonImmutable $mes): void
    {
        $particao = DominioGeo::Meteorologia->silver().'.'.$nome.'_'.$mes->format('Y_m');

        $db->transaction(static function () use ($db, $tabela, $particao): void {
            $db->statement("ALTER TABLE {$tabela} DETACH PARTITION {$particao}");
            $db->statement("DROP TABLE {$particao}");
        });
    }

    /** Sem particao: remove por lotes para nao segurar lock nem inflar o WAL de uma vez. */
    private function removerEmLotes(Connection $db, string $tabela, string $de, string $ate): void
    {
        do {
            $removidas = $db->affectingStatement(
                "DELETE FROM {$tabela} WHERE ctid IN (SELECT ctid FROM {$tabela} WHERE medido_em >= ? AND medido_em < ? LIMIT ".self::LOTE_REMOCAO.')',
                [$de, $ate],
            );
        } while ($removidas > 0);
    }

    /** @return iterable<list<object>> */
    private function lotes(Connection $db, string $tabela, string $de, string $ate): iterable
    {
        $ultimoId = 0;

        do {
            $linhas = $db->select(
                "SELECT * FROM {$tabela} WHERE medido_em >= ? AND medido_em < ? AND id > ? ORDER BY id LIMIT ".self::LOTE_EXPORTACAO,
                [$de, $ate, $ultimoId],
            );

            if ($linhas !== []) {
                $ultimoId = (int) end($linhas)->id;
                yield $linhas;
            }
        } while (count($linhas) === self::LOTE_EXPORTACAO);
    }

    /** @return array<string, string> */
    private function colunas(Connection $db, string $tabela): array
    {
        $colunas = $db->select(
            'SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
            explode('.', $tabela),
        );

        return array_column(array_map(static fn (object $c): array => (array) $c, $colunas), 'data_type', 'column_name');
    }
}
