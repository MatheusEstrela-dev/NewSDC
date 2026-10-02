<?php

declare(strict_types=1);

namespace App\Support\Permissoes;

/** O que uma sincronizacao gravou, ou gravaria quando simulada. */
final class RelatorioDeSincronizacao
{
    /**
     * @param  list<string>  $permissoesCriadas
     * @param  list<string>  $permissoesCompletadas  ja existiam; so colunas vazias foram preenchidas
     * @param  list<string>  $permissoesExcluidas  do config, mas excluidas a mao: nao sao restauradas
     * @param  list<string>  $cargosCriados
     * @param  array<string, list<string>>  $concessoesNovas  cargo => slugs concedidos agora
     */
    public function __construct(
        public readonly bool $simulado,
        public readonly array $permissoesCriadas,
        public readonly array $permissoesCompletadas,
        public readonly array $permissoesExcluidas,
        public readonly array $cargosCriados,
        public readonly array $concessoesNovas,
    ) {}

    public function totalPermissoesCriadas(): int
    {
        return count($this->permissoesCriadas);
    }

    public function totalCargosCriados(): int
    {
        return count($this->cargosCriados);
    }

    public function totalConcessoesNovas(): int
    {
        return array_sum(array_map('count', $this->concessoesNovas));
    }

    public function semMudancas(): bool
    {
        return $this->permissoesCriadas === []
            && $this->permissoesCompletadas === []
            && $this->cargosCriados === []
            && $this->concessoesNovas === [];
    }
}
