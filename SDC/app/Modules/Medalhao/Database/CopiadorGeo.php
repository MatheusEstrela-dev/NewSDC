<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Database;

use App\Support\Database\Geo\DominioGeo;
use Illuminate\Database\Connection;

/**
 * Copia tabelas do geoespacial entre dois bancos, em lotes e de forma
 * idempotente: o destino recebe upsert pela chave do catalogo, entao repetir a
 * copia (ou rodar um delta com --desde) nunca duplica nem sobrescreve igual.
 *
 * As colunas copiadas sao as que existem nos dois lados e nao sao geradas
 * (ex.: referencia.municipios.geom), o que tolera deriva pequena de schema.
 */
final class CopiadorGeo
{
    public function __construct(
        private readonly Connection $origem,
        private readonly Connection $destino,
        private readonly int $lote = 1000,
    ) {
        // Mesma representacao de timestamptz nos dois lados: a verificacao compara texto.
        foreach ([$origem, $destino] as $conexao) {
            $conexao->statement("SET TIME ZONE 'UTC'");
        }
    }

    /**
     * Linhas que a copia moveria, sem escrever nada.
     *
     * @param array{tabela: string, chave: list<string>, alteracao: string, particionada: bool} $spec
     */
    public function contar(array $spec, ?string $desde): int
    {
        [$filtro, $bindings] = $this->filtroDesde($spec, $desde);

        return (int) $this->origem->selectOne("SELECT count(*) AS n FROM {$spec['tabela']} WHERE {$filtro}", $bindings)->n;
    }

    /**
     * @param array{tabela: string, chave: list<string>, alteracao: string, particionada: bool} $spec
     * @param callable(int): void|null $aoAvancar recebe o total copiado ate agora
     * @return int linhas lidas da origem (inseridas ou ja iguais)
     */
    public function copiar(array $spec, ?string $desde, ?callable $aoAvancar = null): int
    {
        $tabela = $spec['tabela'];
        $colunas = $this->colunasComuns($tabela);
        [$filtro, $bindings] = $this->filtroDesde($spec, $desde);

        if ($spec['particionada']) {
            $this->garantirParticoes($tabela, $filtro, $bindings);
        }

        $lista = implode(', ', $colunas);
        $total = 0;
        $ultimoId = 0;

        do {
            $linhas = $this->origem->select(
                "SELECT {$lista} FROM {$tabela} WHERE id > ? AND {$filtro} ORDER BY id LIMIT {$this->lote}",
                [$ultimoId, ...$bindings],
            );

            if ($linhas === []) {
                break;
            }

            $this->gravarLote($spec, $colunas, $linhas);

            $total += count($linhas);
            $ultimoId = (int) end($linhas)->id;

            if ($aoAvancar !== null) {
                $aoAvancar($total);
            }
        } while (count($linhas) === $this->lote);

        $this->ajustarSequence($tabela);

        return $total;
    }

    /**
     * Contagem, faixa de id e hash do conteudo (so colunas comuns) nos dois lados.
     * O hash e a soma dos hashes de linha: independe de ordem e nao estoura memoria.
     *
     * @param array{tabela: string, chave: list<string>, alteracao: string, particionada: bool} $spec
     * @return array{origem: array<string, string>, destino: array<string, string>, ok: bool}
     */
    public function verificar(array $spec): array
    {
        $tabela = $spec['tabela'];
        $linha = 'ROW('.implode(', ', $this->colunasComuns($tabela)).')::text';
        $sql = "SELECT count(*) AS contagem, min(id) AS id_min, max(id) AS id_max, "
            ."COALESCE(sum(hashtextextended({$linha}, 0)::numeric), 0) AS hash FROM {$tabela}";

        $origem = (array) $this->origem->selectOne($sql);
        $destino = (array) $this->destino->selectOne($sql);

        return [
            'origem' => array_map('strval', $origem),
            'destino' => array_map('strval', $destino),
            'ok' => array_map('strval', $origem) === array_map('strval', $destino),
        ];
    }

    /** Matviews gold dependem das tabelas copiadas; sem isso o mapa nasce vazio. */
    public function atualizarMatviews(): void
    {
        foreach (EsquemaGeo::matviews() as $nome => $def) {
            $this->destino->statement("REFRESH MATERIALIZED VIEW {$def['schema']}.{$nome}");
        }
    }

    /**
     * @return list<string>
     */
    private function colunasComuns(string $tabela): array
    {
        $nas = fn (Connection $c): array => array_column($c->select(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND is_generated = 'NEVER'
             ORDER BY ordinal_position",
            explode('.', $tabela),
        ), 'column_name');

        return array_values(array_intersect($nas($this->destino), $nas($this->origem)));
    }

    /**
     * @param array{tabela: string, chave: list<string>, alteracao: string, particionada: bool} $spec
     * @return array{0: string, 1: list<string>}
     */
    private function filtroDesde(array $spec, ?string $desde): array
    {
        return $desde === null
            ? ['TRUE', []]
            : ["({$spec['alteracao']}) >= ?", [$desde]];
    }

    /**
     * @param array{tabela: string, chave: list<string>, alteracao: string, particionada: bool} $spec
     * @param list<string> $colunas
     * @param list<object> $linhas
     */
    private function gravarLote(array $spec, array $colunas, array $linhas): void
    {
        $linhaPlaceholder = '('.implode(', ', array_fill(0, count($colunas), '?')).')';
        $valores = implode(', ', array_fill(0, count($linhas), $linhaPlaceholder));
        $bindings = [];

        foreach ($linhas as $linha) {
            foreach ($colunas as $coluna) {
                $bindings[] = $linha->{$coluna};
            }
        }

        $chave = implode(', ', $spec['chave']);
        $lista = implode(', ', $colunas);
        // id nunca e atualizado: em chave natural o destino mantem o seu.
        $atualizaveis = array_values(array_diff($colunas, $spec['chave'], ['id']));

        if ($atualizaveis === []) {
            $conflito = "ON CONFLICT ({$chave}) DO NOTHING";
        } else {
            $set = implode(', ', array_map(static fn (string $c): string => "{$c} = EXCLUDED.{$c}", $atualizaveis));
            $atual = implode(', ', array_map(static fn (string $c): string => "t.{$c}", $atualizaveis));
            $novo = implode(', ', array_map(static fn (string $c): string => "EXCLUDED.{$c}", $atualizaveis));
            // So reescreve a linha que de fato mudou: repetir a copia nao gera dead tuples.
            $conflito = "ON CONFLICT ({$chave}) DO UPDATE SET {$set} WHERE ROW({$atual}) IS DISTINCT FROM ROW({$novo})";
        }

        $this->destino->statement(
            "INSERT INTO {$spec['tabela']} AS t ({$lista}) VALUES {$valores} {$conflito}",
            $bindings,
        );
    }

    /**
     * O destino particiona por mes: cada mes presente na origem precisa de
     * particao antes do insert, senao a linha cairia na particao default.
     *
     * @param list<string> $bindings
     */
    private function garantirParticoes(string $tabela, string $filtro, array $bindings): void
    {
        $faixa = $this->origem->selectOne(
            "SELECT min(medido_em) AS de, max(medido_em) AS ate FROM {$tabela} WHERE {$filtro}",
            $bindings,
        );

        if ($faixa->de === null) {
            return;
        }

        $funcao = DominioGeo::Meteorologia->silver().'.criar_particao_mensal';
        $nome = explode('.', $tabela)[1];

        $mes = (new \DateTimeImmutable($faixa->de, new \DateTimeZone('UTC')))->modify('first day of this month')->setTime(0, 0);
        $fim = new \DateTimeImmutable($faixa->ate, new \DateTimeZone('UTC'));

        for (; $mes <= $fim; $mes = $mes->modify('+1 month')) {
            $this->destino->selectOne("SELECT {$funcao}(?, ?::date)", [$nome, $mes->format('Y-m-d')]);
        }
    }

    /**
     * Leva a sequence do destino ao maior entre os ids copiados e a sequence da
     * origem, para o proximo insert nao colidir nem reaproveitar id.
     */
    private function ajustarSequence(string $tabela): void
    {
        $sequenceDestino = $this->destino->selectOne("SELECT pg_get_serial_sequence(?, 'id') AS s", [$tabela])->s;

        if ($sequenceDestino === null) {
            return;
        }

        $maxDestino = (int) $this->destino->selectOne("SELECT COALESCE(max(id), 0) AS m FROM {$tabela}")->m;

        $sequenceOrigem = $this->origem->selectOne("SELECT pg_get_serial_sequence(?, 'id') AS s", [$tabela])->s;
        $ultimaOrigem = $sequenceOrigem === null
            ? 0
            : (int) $this->origem->selectOne("SELECT CASE WHEN is_called THEN last_value ELSE 0 END AS v FROM {$sequenceOrigem}")->v;

        $valor = max($maxDestino, $ultimaOrigem);

        $this->destino->selectOne('SELECT setval(?, ?, ?)', [$sequenceDestino, max($valor, 1), $valor > 0]);
    }
}
