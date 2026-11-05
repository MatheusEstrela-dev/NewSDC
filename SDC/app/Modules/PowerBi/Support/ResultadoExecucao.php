<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

/** Resultado de uma execucao de materializacao, por tabela. */
final class ResultadoExecucao
{
    /** @var array<string, ResultadoGravacao> */
    private array $tabelas = [];

    public function registrar(string $tabela, ResultadoGravacao $r): self
    {
        $this->tabelas[$tabela] ??= new ResultadoGravacao();
        $this->tabelas[$tabela]->somar($r);

        return $this;
    }

    public function lidas(): int
    {
        return array_sum(array_map(fn (ResultadoGravacao $r): int => $r->lidas, $this->tabelas));
    }

    public function gravadas(): int
    {
        return array_sum(array_map(fn (ResultadoGravacao $r): int => $r->gravadas, $this->tabelas));
    }

    public function inalteradas(): int
    {
        return array_sum(array_map(fn (ResultadoGravacao $r): int => $r->inalteradas(), $this->tabelas));
    }

    public function removidas(): int
    {
        return array_sum(array_map(fn (ResultadoGravacao $r): int => $r->removidas, $this->tabelas));
    }

    /** @return array<string, array{lidas: int, gravadas: int, inalteradas: int, removidas: int}> */
    public function porTabela(): array
    {
        return array_map(fn (ResultadoGravacao $r): array => [
            'lidas' => $r->lidas,
            'gravadas' => $r->gravadas,
            'inalteradas' => $r->inalteradas(),
            'removidas' => $r->removidas,
        ], $this->tabelas);
    }
}
