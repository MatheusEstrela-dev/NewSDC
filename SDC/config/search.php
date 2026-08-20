<?php

declare(strict_types=1);

use App\Services\Search\Fontes\FonteCisternaBeneficiarios;
use App\Services\Search\Fontes\FonteDecretacoes;
use App\Services\Search\Fontes\FonteDemandas;
use App\Services\Search\Fontes\FonteMunicipios;
use App\Services\Search\Fontes\FonteOrgaos;
use App\Services\Search\Fontes\FontePae;
use App\Services\Search\Fontes\FonteRat;

return [

    /*
    |--------------------------------------------------------------------------
    | Fontes da busca global
    |--------------------------------------------------------------------------
    |
    | Ordem = prioridade. As primeiras sao consultadas primeiro e, se o
    | orcamento de tempo estourar, as ultimas ficam de fora daquela busca --
    | entao o que resolve a maioria das pesquisas vem na frente.
    |
    | Acrescentar modulo e acrescentar linha aqui. Antes, cada fonte era uma
    | closure escrita dentro do GlobalSearchService, e por isso catorze modulos
    | nunca entraram: mexer no nucleo para cada um custava caro demais.
    |
    | Toda fonte listada aqui PRECISA ter indice trigram nas colunas que
    | pesquisa. Sem ele, o `ILIKE '%termo%'` vira varredura completa e o custo
    | cresce junto com a tabela.
    |
    */
    'fontes' => [
        FonteCisternaBeneficiarios::class,
        FontePae::class,
        FonteRat::class,
        FonteDemandas::class,
        FonteDecretacoes::class,
        FonteOrgaos::class,
        FonteMunicipios::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites
    |--------------------------------------------------------------------------
    */

    // Linhas por fonte. O palette mostra poucas por grupo; trazer mais so
    // aumenta payload e tempo de ordenacao.
    'limite_por_fonte' => 7,

    // Teto de tempo da busca inteira, em milissegundos. Ao ultrapassar, as
    // fontes restantes sao puladas e a busca devolve o que ja tem. Com o banco
    // crescendo, e o que impede uma tabela lenta de segurar a caixa inteira.
    //
    // E rede de seguranca, NAO meta de latencia. Medido em estado quente: 14 a
    // 65ms para as sete fontes. O primeiro valor testado, 250ms, era baixo
    // demais -- a conexao fria da primeira busca apos o boot do worker sozinha
    // levou 324ms e derrubava fontes sem que nada estivesse errado.
    //
    // Quando este teto disparar, o aviso no log e o sinal de que alguma fonte
    // passou a custar caro e precisa de indice.
    'orcamento_ms' => 1000,

    // Cache do resultado. Curto de proposito: a busca precisa refletir registro
    // criado ha pouco, e a invalidacao e por TTL.
    'cache_ttl' => 60,
];
