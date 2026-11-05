<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\DefinicaoTabela;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\MaterializadorBase;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatExportBIService;
use App\Support\Database\ConexaoPowerBi;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * bi_rat anonimizado (ocorrencias + recursos, vistorias e envolvidos_agregado).
 *
 * Incremental por (atividade_em, id), onde atividade_em e a maior data entre a
 * RAT e seus relatos (ver RatExportBIService::consultaIncremental): editar so
 * um relato nao muda o updated_at da RAT, mas muda a atividade. Como RAT e
 * pequena e a edicao de um relato nao muda o hash do pai, as filhas de toda RAT
 * relida sao comparadas por hash com as gravadas; so as que mudaram sao apagadas
 * e reinseridas (na mesma transacao do pai). O pai so e regravado se o hash mudou. RAT excluida na fonte sai pelo Reconciliador.
 */
final class RatOcorrenciasMaterializador extends MaterializadorBase
{
    private const PAI = 'ocorrencias';

    private const FILHAS = [
        'recursos' => 'recursos',
        'vistorias' => 'vistoria',
        'envolvidos_agregado' => 'envolvidos',
    ];

    public function __construct(
        GravadorBi $gravador,
        private readonly RatExportBIService $service,
        private readonly RatAnonimizador $anonimizador,
    ) {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'rat';
    }

    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao
    {
        $resultado = new ResultadoExecucao();
        $estado = $cursor->estado(self::PAI);
        $desde = $janela->inferior($estado);

        $cursor->emTransacao(function () use ($janela, $cursor, $desde, $resultado): void {
            $ultimo = null;

            foreach ($this->lotes($desde, $janela->superior) as $modelos) {
                $pacotes = $this->pacotes($modelos);

                $resultado->registrar(self::PAI, $this->gravador->gravar(
                    $this->definicao(self::PAI),
                    array_column($pacotes, 'ocorrencia')
                ));

                $ids = array_map(fn (array $p): string => (string) $p['ocorrencia']['id'], $pacotes);
                foreach (self::FILHAS as $tabela => $chave) {
                    $definicao = $this->definicao($tabela);
                    $linhas = $this->linhasDaFilha($pacotes, $chave);
                    $alterados = $this->paisAlterados($definicao, $ids, $linhas);

                    $resultado->registrar($tabela, $this->gravador->gravarFilhas(
                        $definicao,
                        $alterados,
                        array_filter($linhas, fn (array $l): bool => in_array($l[$definicao->colunaPai], $alterados, true))
                    ));
                }

                $ultimo = $modelos->last();
            }

            $cursor->avancar(
                self::PAI,
                $ultimo === null ? null : $this->atividade($ultimo),
                $ultimo === null ? null : (string) $ultimo->id,
                $janela
            );
        });

        return $resultado;
    }

    public function linhasAoVivo(string $tabela): iterable
    {
        $chave = $tabela === self::PAI ? 'ocorrencia' : (self::FILHAS[$tabela] ?? null);
        if ($chave === null) {
            throw new \InvalidArgumentException("Tabela {$tabela} fora do catalogo de rat.");
        }

        foreach ($this->lotes(null, null) as $modelos) {
            $pacotes = $this->pacotes($modelos);
            yield from $tabela === self::PAI ? array_column($pacotes, 'ocorrencia') : $this->linhasDaFilha($pacotes, $chave);
        }
    }

    public function chavesDaFonte(string $tabela): iterable
    {
        if ($tabela !== self::PAI) {
            return parent::chavesDaFonte($tabela);
        }

        return $this->ids();
    }

    /** @return Generator<int, array{id: string}> */
    private function ids(): Generator
    {
        $consulta = RatOcorrencia::on(ConexaoPowerBi::FONTE)->select('id');
        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'id') as $o) {
            yield ['id' => (string) $o->id];
        }
    }

    /**
     * Keyset por (atividade_em, id) ate esgotar; cada lote ja vem com eager load.
     *
     * @return Generator<int, Collection<int, RatOcorrencia>>
     */
    private function lotes(?Carbon $desde, ?Carbon $ate): Generator
    {
        $tamanho = max(1, (int) config('powerbi.lote', 1000));
        $apos = null;

        while (true) {
            $modelos = $this->service->consultaIncremental($desde, $apos, $tamanho, $ate, ConexaoPowerBi::FONTE)->get();
            if ($modelos->isEmpty()) {
                return;
            }

            yield $modelos;

            if ($modelos->count() < $tamanho) {
                return;
            }
            $ultimo = $modelos->last();
            $desde = $this->atividade($ultimo);
            $apos = (string) $ultimo->id;
        }
    }

    private function atividade(RatOcorrencia $o): Carbon
    {
        return Carbon::parse((string) $o->getAttribute('atividade_em'), (string) config('app.timezone'));
    }

    /**
     * @param Collection<int, RatOcorrencia> $modelos
     * @return list<array<string, mixed>>
     */
    private function pacotes(Collection $modelos): array
    {
        $linhas = $this->service->normalizarOcorrencias($modelos);

        $pacotes = [];
        foreach ($modelos->values() as $i => $o) {
            $pacotes[] = $this->anonimizador->pacote($o, $linhas[$i]);
        }

        return $pacotes;
    }

    /**
     * Pais cujo conjunto de filhas difere do gravado (compara hash por chave);
     * evita apagar e reinserir filhas que nao mudaram (e manter materializado_em).
     *
     * @param list<string> $ids
     * @param list<array<string, mixed>> $linhas
     * @return list<string>
     */
    private function paisAlterados(DefinicaoTabela $d, array $ids, array $linhas): array
    {
        $novo = $atual = array_fill_keys($ids, []);
        foreach ($linhas as $l) {
            $novo[$l[$d->colunaPai]][$d->chaveDe($l)] = $this->gravador->hash($d, $l);
        }

        $pk = implode(', ', $d->pk);
        foreach (array_chunk($ids, 5000) as $grupo) {
            $in = implode(', ', array_fill(0, count($grupo), '?'));
            $gravadas = ConexaoPowerBi::escrita()->select(
                "SELECT {$pk}, encode(hash, 'hex') AS h FROM {$d->alvo()} WHERE {$d->colunaPai} IN ({$in})",
                $grupo
            );
            foreach ($gravadas as $g) {
                $atual[$g->{$d->colunaPai}][$d->chaveDe((array) $g)] = $g->h;
            }
        }

        return array_values(array_filter($ids, function (string $id) use ($novo, $atual): bool {
            ksort($novo[$id]);
            ksort($atual[$id]);

            return $novo[$id] !== $atual[$id];
        }));
    }

    /**
     * @param list<array<string, mixed>> $pacotes
     * @return list<array<string, mixed>>
     */
    private function linhasDaFilha(array $pacotes, string $chave): array
    {
        $linhas = [];
        foreach ($pacotes as $p) {
            $item = $p[$chave];
            if ($item === null) {
                continue;
            }
            // vistoria e uma linha so; recursos e envolvidos sao listas.
            array_push($linhas, ...(array_is_list($item) ? $item : [$item]));
        }

        return $linhas;
    }
}
