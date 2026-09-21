<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use App\Modules\Ranking\Enums\EscopoPlacar;
use InvalidArgumentException;

/**
 * Filtros de uma consulta de placar, JA AUTORIZADOS pelo chamador.
 *
 * Este DTO nao decide quem pode ver o que: quando ele chega ao
 * LeaderboardQuery a autorizacao ja foi resolvida na borda (policy, request
 * form, controller). Por isso ele nao carrega usuario autenticado, orgao ativo
 * nem papel - carregar qualquer um desses aqui convidaria o service a tomar
 * decisao de acesso em cache, que e exatamente o que nao pode acontecer.
 *
 * Todos os campos sao dados de consulta puros e, juntos, formam a chave de
 * cache do placar: escopo, periodo, modulo, geracao e a pagina pedida.
 */
final readonly class FiltroPlacar
{
    /** Teto defensivo de pagina: evita varredura completa disfarcada de paginacao. */
    public const POR_PAGINA_MAXIMO = 100;

    /** Fallback usado apenas quando o chamador nao passa config('ranking.placar.por_pagina'). */
    public const POR_PAGINA_PADRAO = 25;

    /**
     * @param EscopoPlacar $escopo       Dimensao do placar (usuario, orgao, municipio).
     * @param string       $periodoChave Chave estavel do periodo: 'mes:2026-09', 'ano:2026', 'acumulado'.
     * @param string       $modulo       'all' para o total; nome do modulo para a contribuicao isolada.
     * @param int          $pagina       1-based.
     * @param int          $porPagina    Entre 1 e POR_PAGINA_MAXIMO.
     * @param int          $geracao      Geracao ativa da projecao; a reconstrucao publica outra.
     */
    public function __construct(
        public EscopoPlacar $escopo,
        public string $periodoChave,
        public string $modulo = 'all',
        public int $pagina = 1,
        public int $porPagina = self::POR_PAGINA_PADRAO,
        public int $geracao = 1,
    ) {
        if (trim($periodoChave) === '') {
            throw new InvalidArgumentException('A chave do periodo e obrigatoria.');
        }

        // 'all' e um valor explicito, nunca string vazia nem null: o total e uma
        // linha como qualquer outra na projecao e precisa de chave comparavel.
        if (trim($modulo) === '') {
            throw new InvalidArgumentException('O modulo e obrigatorio; use "all" para o total.');
        }

        if ($pagina < 1) {
            throw new InvalidArgumentException('A pagina do placar comeca em 1.');
        }

        if ($porPagina < 1 || $porPagina > self::POR_PAGINA_MAXIMO) {
            throw new InvalidArgumentException(
                'O tamanho de pagina deve estar entre 1 e ' . self::POR_PAGINA_MAXIMO . '.'
            );
        }

        if ($geracao < 1) {
            throw new InvalidArgumentException('A geracao da projecao comeca em 1.');
        }
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    public function totalPorModulo(): bool
    {
        return $this->modulo === 'all';
    }

    /**
     * Mesmo recorte, outra pagina. Util para varrer o placar sem remontar o
     * filtro campo a campo - e sem que a varredura possa trocar escopo,
     * periodo, modulo ou geracao por engano.
     */
    public function naPagina(int $pagina): self
    {
        return new self(
            escopo: $this->escopo,
            periodoChave: $this->periodoChave,
            modulo: $this->modulo,
            pagina: $pagina,
            porPagina: $this->porPagina,
            geracao: $this->geracao,
        );
    }

    /**
     * Identidade do RECORTE, sem a pagina. Serve de prefixo de cache e de
     * rotulo em log; nao contem nada sobre quem consultou.
     */
    public function assinaturaRecorte(): string
    {
        return implode(':', [
            $this->escopo->value,
            $this->periodoChave,
            $this->modulo,
            'g' . $this->geracao,
        ]);
    }
}
