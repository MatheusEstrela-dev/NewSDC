<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\PowerBi\Support\ConsultaNaFonte;
use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\MaterializadorBase;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use Illuminate\Database\Query\Builder;

/**
 * Materializa em estrategia completa (staging + merge) todas as tabelas do
 * catalogo marcadas como completa; as demais ficam com a subclasse. Serve a
 * modulos cujas linhas embutem dimensoes (nome de municipio, deposito etc.)
 * que mudam sem tocar o updated_at da linha principal.
 */
abstract class MaterializadorCompletoBase extends MaterializadorBase
{
    /** Linhas da tabela no formato do catalogo, lidas da fonte de BI. */
    abstract protected function linhas(string $tabela): iterable;

    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao
    {
        $resultado = new ResultadoExecucao();

        foreach ($this->tabelas() as $nome => $definicao) {
            if (! $definicao->completa()) {
                continue;
            }
            $gravacao = $cursor->emTransacao(function () use ($definicao, $nome, $janela, $cursor) {
                $r = $this->gravador->gravarCompleta($definicao, $this->linhas($nome));
                $cursor->avancar($nome, null, null, $janela);

                return $r;
            });
            $resultado->registrar($nome, $gravacao);
        }

        return $resultado;
    }

    public function linhasAoVivo(string $tabela): iterable
    {
        return $this->linhas($tabela);
    }

    /**
     * Le uma consulta do service pela fonte de BI, em lotes por chave, e mapeia
     * cada linha com $mapa.
     *
     * @param callable(object): array<string, mixed> $mapa
     * @return iterable<array<string, mixed>>
     */
    protected function lerPorId(Builder $consulta, string $colunaId, string $alias, callable $mapa): iterable
    {
        $consulta = ConsultaNaFonte::redirecionar($consulta);

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), $colunaId, $alias) as $linha) {
            yield $mapa($linha);
        }
    }
}
