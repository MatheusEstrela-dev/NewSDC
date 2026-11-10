<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Contracts;

use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\DefinicaoTabela;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\ResultadoExecucao;

/**
 * Materializa as tabelas bi_* de UM modulo a partir da fonte (sdc).
 *
 * Registro: classe em config('powerbi.materializadores')[modulo]; catalogo em
 * config('powerbi.catalogo')[modulo]. Estenda MaterializadorBase.
 */
interface Materializador
{
    /** Slug do modulo (mesmo do schema bi_<modulo> e da rota /bi/{modulo}/...). */
    public function modulo(): string;

    /**
     * Catalogo de tabelas do modulo.
     *
     * @return array<string, DefinicaoTabela> nome da tabela => definicao
     */
    public function tabelas(): array;

    /**
     * Le a fonte dentro da janela e grava. Contrato: gravar e avancar o cursor
     * na MESMA transacao ($cursor->emTransacao). Ler SEMPRE da fonte (ver
     * ConsultaNaFonte::redirecionar para builders de services de export).
     */
    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao;

    /**
     * Linhas no formato do service de export AO VIVO (mesmas chaves das colunas
     * do catalogo). Base do --verificar (paridade por hash).
     *
     * @return iterable<array<string, mixed>>
     */
    public function linhasAoVivo(string $tabela): iterable;

    /**
     * Chaves (colunas da PK) que existem hoje na fonte; base do Reconciliador.
     *
     * @return iterable<array<string, mixed>>
     */
    public function chavesDaFonte(string $tabela): iterable;
}
