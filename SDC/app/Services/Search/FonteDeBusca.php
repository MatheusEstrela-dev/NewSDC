<?php

declare(strict_types=1);

namespace App\Services\Search;

/**
 * Uma fonte da busca global.
 *
 * Existe para o servico central parar de conhecer os modulos um a um. Antes,
 * `GlobalSearchService::runSearch()` tinha quatro closures escritas a mao: PAE,
 * Decretacoes, RAT e Demandas. Acrescentar modulo exigia editar o nucleo, e foi
 * assim que catorze modulos ficaram de fora -- inclusive os dois maiores
 * conjuntos de dados do sistema.
 *
 * Cada fonte responde por: qual permissao a habilita, como consulta e como o
 * resultado vira linha do palette.
 */
interface FonteDeBusca
{
    /**
     * Chave do grupo no resultado. E o que o CommandPalette usa para agrupar.
     */
    public function chave(): string;

    /**
     * Slug que o usuario precisa ter para esta fonte ser consultada, ou null
     * quando a fonte e aberta a qualquer autenticado.
     *
     * Filtrar por permissao ANTES de consultar resolve dois problemas de uma
     * vez: nao vaza registro de modulo que a pessoa nao acessa, e nao paga a
     * consulta de quem nao veria o resultado. Com o banco crescendo, essa poda
     * e o que segura o custo da busca.
     */
    public function permissao(): ?string;

    /**
     * Linhas ja no contrato do palette:
     * ['id', 'title', 'subtitle', 'url', 'icon', 'tag'].
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscar(string $termo, int $limite): array;
}
