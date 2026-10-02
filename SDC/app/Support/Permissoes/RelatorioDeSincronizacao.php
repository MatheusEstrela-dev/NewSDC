<?php

declare(strict_types=1);

namespace App\Support\Permissoes;

/** O que uma sincronizacao gravou, ou gravaria quando simulada. */
final class RelatorioDeSincronizacao
{
    /**
     * @param  bool  $somenteNovas  modo automatico: so concede par cuja permissao ou cargo nasceu nesta execucao
     * @param  list<string>  $permissoesCriadas
     * @param  list<string>  $permissoesCompletadas  ja existiam; so colunas vazias foram preenchidas
     * @param  list<string>  $permissoesExcluidas  do config, mas excluidas a mao: nao sao restauradas
     * @param  list<string>  $cargosCriados
     * @param  list<string>  $cargosCompletados  cargo com o mesmo nome e slug vazio, que recebeu o slug
     * @param  array<string, string>  $cargosEmConflito  slug do config => motivo; nome ja usado por outro slug, sem concessao
     * @param  array<string, list<string>>  $concessoesNovas  cargo => slugs concedidos agora
     * @param  array<string, list<string>>  $concessoesPendentes  cargo => slugs do config sem concessao que o modo somente novas nao concede
     */
    public function __construct(
        public readonly bool $simulado,
        public readonly bool $somenteNovas,
        public readonly array $permissoesCriadas,
        public readonly array $permissoesCompletadas,
        public readonly array $permissoesExcluidas,
        public readonly array $cargosCriados,
        public readonly array $cargosCompletados,
        public readonly array $cargosEmConflito,
        public readonly array $concessoesNovas,
        public readonly array $concessoesPendentes,
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
        return self::contar($this->concessoesNovas);
    }

    public function totalConcessoesPendentes(): int
    {
        return self::contar($this->concessoesPendentes);
    }

    /** Conflito e pendencia nao contam: nada foi gravado por causa deles. */
    public function semMudancas(): bool
    {
        return $this->permissoesCriadas === []
            && $this->permissoesCompletadas === []
            && $this->cargosCriados === []
            && $this->cargosCompletados === []
            && $this->concessoesNovas === [];
    }

    /** @param  array<string, list<string>>  $porCargo */
    private static function contar(array $porCargo): int
    {
        return array_sum(array_map('count', $porCargo));
    }
}
