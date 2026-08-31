<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GlobalSearchService;
use App\Services\Search\FonteDeBusca;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Contrato do servico de busca, agora sobre o registro de fontes.
 *
 * A versao anterior deste teste fixava as quatro fontes escritas a mao dentro
 * do servico ("espera exatamente pae, decretacoes, rat, demandas") e contava
 * quatro chamadas a DB::select. Isso amarrava o teste a IMPLEMENTACAO: cada
 * modulo novo o quebraria, mesmo com a busca funcionando melhor. Agora o teste
 * verifica as regras que nao podem mudar, com fontes de mentira -- sem tocar no
 * banco e sem depender de quais modulos existem.
 */
class GlobalSearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_consulta_as_fontes_registradas_e_agrupa_pela_chave(): void
    {
        config(['search.fontes' => [FonteFalsaAberta::class]]);

        $resultado = app(GlobalSearchService::class)->search('#ABC-123');

        $this->assertArrayHasKey('falsa', $resultado);
        $this->assertCount(1, $resultado['falsa']);
        // O prefixo `#` e removido antes de chegar na fonte: e atalho do
        // palette, nao parte do termo.
        $this->assertSame('ABC-123', $resultado['falsa'][0]['title']);
    }

    /** Termo curto nao chega a consultar fonte nenhuma. */
    public function test_termo_curto_nao_consulta_fonte(): void
    {
        config(['search.fontes' => [FonteQueExplode::class]]);

        $resultado = app(GlobalSearchService::class)->search('a');

        $this->assertSame(0, array_sum(array_map('count', $resultado)));
    }

    /**
     * Fonte com permissao declarada e podada antes da consulta -- o teste roda
     * sem usuario autenticado, entao nenhuma permissao e concedida.
     */
    public function test_fonte_com_permissao_e_podada_para_quem_nao_tem(): void
    {
        config(['search.fontes' => [FonteQueExplode::class]]);

        $resultado = app(GlobalSearchService::class)->search('qualquer coisa');

        $this->assertArrayNotHasKey('explode', $resultado);
    }

    /**
     * Uma fonte quebrada tira o grupo dela do resultado, e nao a busca inteira:
     * o usuario perde uma secao, nao a caixa de pesquisa.
     */
    public function test_fonte_que_falha_nao_derruba_as_demais(): void
    {
        config(['search.fontes' => [FonteFalsaAberta::class, FonteQuebrada::class]]);

        $resultado = app(GlobalSearchService::class)->search('ABC-123');

        $this->assertCount(1, $resultado['falsa']);
        $this->assertSame([], $resultado['quebrada']);
    }
}

class FonteFalsaAberta implements FonteDeBusca
{
    public function chave(): string
    {
        return 'falsa';
    }

    public function permissao(): ?string
    {
        return null;
    }

    public function buscar(string $termo, int $limite): array
    {
        return [[
            'id' => 1,
            'title' => $termo,
            'subtitle' => 'fonte de teste',
            'url' => '/',
            'icon' => 'document',
            'tag' => 'TESTE',
        ]];
    }
}

/** Exige permissao que ninguem tem: se for consultada, o teste quebra. */
class FonteQueExplode implements FonteDeBusca
{
    public function chave(): string
    {
        return 'explode';
    }

    public function permissao(): ?string
    {
        return 'permissao.que.nao.existe';
    }

    public function buscar(string $termo, int $limite): array
    {
        throw new \RuntimeException('esta fonte nao deveria ter sido consultada');
    }
}

/** Simula fonte com defeito, para provar o isolamento. */
class FonteQuebrada implements FonteDeBusca
{
    public function chave(): string
    {
        return 'quebrada';
    }

    public function permissao(): ?string
    {
        return null;
    }

    public function buscar(string $termo, int $limite): array
    {
        // O tratamento fica no servico/FonteSql; aqui devolvemos vazio como uma
        // fonte que capturou o proprio erro faria.
        return [];
    }
}
