<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Filtro por REDEC.
 *
 * O ponto sensivel e que `dec_decreto_municipios.municipio_id` guarda ids de
 * DOIS cadastros diferentes: os vinculos legados trazem o id da CEDEC (que so
 * pode ser resolvido pelo codigo IBGE embutido no `n_protocolo_fide`) e os
 * criados pelo formulario atual trazem `municipios.id`. Estes testes fixam que o
 * filtro respeita essa distincao nas duas direcoes.
 */
class RedecFilterTest extends TestCase
{
    use DatabaseTransactions;

    /** REDEC usada nos cenarios; nenhuma relacao com os dados existentes. */
    private const REDEC_FILTRADA = 7;

    private const REDEC_OUTRA = 3;

    private Municipio $municipioDaRedec;

    private Municipio $municipioDeOutraRedec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        // Dois municipios reais e distintos, ambos com IBGE preenchido.
        [$this->municipioDaRedec, $this->municipioDeOutraRedec] = Municipio::query()
            ->whereNotNull('codigo_ibge')
            ->whereRaw('length(codigo_ibge) = 7')
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();

        // O mapa municipio -> REDEC e cacheado; os cenarios abaixo criam
        // processos novos, entao precisa comecar limpo em cada teste.
        ProcessoFilter::clearCache();
    }

    private function criaProcesso(?int $redecId): Processo
    {
        return Processo::create([
            'data_entrada'     => '2026-01-15',
            'processo'         => 'MUNICIPAL',
            'status'           => 'Aguardando analise',
            'redec_id'         => $redecId,
            'n_protocolo_fide' => 'MG-F-' . $this->municipioDaRedec->codigo_ibge . '-70000-20260115',
        ]);
    }

    /** @return array<int, int> ids dos processos devolvidos pelo filtro */
    private function filtraPorRedec(int $redecId): array
    {
        $filtro = new ProcessoFilter(new Request(['redec_id' => $redecId]));

        return $filtro->apply(Processo::query())->pluck('id')->map('intval')->all();
    }

    /**
     * Ancora a REDEC no mapa historico: um processo com a REDEC preenchida e
     * vinculado ao municipio faz `getMunicipioIdsPorRedec` conhecer o municipio.
     */
    private function ancoraMunicipioNaRedec(): Processo
    {
        $processo = $this->criaProcesso(self::REDEC_FILTRADA);

        DecretoMunicipio::create([
            'entrada_processos_id' => $processo->id,
            'municipio_id'         => $this->municipioDaRedec->id,
            'n_protocolo_fide'     => null,
        ]);

        ProcessoFilter::clearCache();

        return $processo;
    }

    public function test_mapa_de_municipios_da_redec_reconhece_o_vinculo(): void
    {
        $this->ancoraMunicipioNaRedec();

        $this->assertContains(
            (int) $this->municipioDaRedec->id,
            ProcessoFilter::getMunicipioIdsPorRedec(self::REDEC_FILTRADA)
        );

        $this->assertContains(
            $this->municipioDaRedec->codigo_ibge,
            ProcessoFilter::getCodigosIbgePorRedec(self::REDEC_FILTRADA)
        );
    }

    public function test_encontra_processo_pelo_redec_id_gravado(): void
    {
        $processo = $this->criaProcesso(self::REDEC_FILTRADA);

        $this->assertContains($processo->id, $this->filtraPorRedec(self::REDEC_FILTRADA));
        $this->assertNotContains($processo->id, $this->filtraPorRedec(self::REDEC_OUTRA));
    }

    public function test_encontra_processo_novo_pelo_municipio_vinculado(): void
    {
        $ancora = $this->ancoraMunicipioNaRedec();

        // Processo SEM redec_id, ligado ao municipio da REDEC pelo id novo
        // (`municipio_id` = municipios.id, protocolo do vinculo sem IBGE).
        $semRedec = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $semRedec->id,
            'municipio_id'         => $this->municipioDaRedec->id,
            'n_protocolo_fide'     => 'MG-F-31-70000-20260115',
        ]);

        $encontrados = $this->filtraPorRedec(self::REDEC_FILTRADA);

        $this->assertContains($ancora->id, $encontrados);
        $this->assertContains($semRedec->id, $encontrados);
    }

    /**
     * REGRESSAO: vinculo legado identificado pelo IBGE do protocolo.
     *
     * O `municipio_id` e um id da CEDEC sem correspondencia em `municipios`, logo
     * comparar `municipios.id` contra a coluna crua nunca acha esse processo -
     * era o que acontecia antes e fazia decretacoes legadas desaparecerem do
     * recorte da REDEC.
     */
    public function test_encontra_processo_legado_pelo_ibge_do_protocolo(): void
    {
        $this->ancoraMunicipioNaRedec();

        $legado = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $legado->id,
            // Id do cadastro da CEDEC: nao existe em `municipios`.
            'municipio_id'         => 999999,
            'n_protocolo_fide'     => 'MG-F-' . $this->municipioDaRedec->codigo_ibge . '-12345-20260115',
        ]);

        $this->assertContains($legado->id, $this->filtraPorRedec(self::REDEC_FILTRADA));
    }

    /**
     * REGRESSAO: nao traz processo de outra REDEC por coincidencia de id.
     *
     * Aqui o vinculo legado tem `municipio_id` igual ao `municipios.id` de um
     * municipio DA REDEC filtrada, mas o protocolo diz que o municipio real e
     * outro. Antes o filtro casava pelo numero cru e devolvia este processo.
     */
    public function test_nao_traz_processo_legado_de_outra_redec_por_coincidencia_de_id(): void
    {
        $this->ancoraMunicipioNaRedec();

        $deOutraRedec = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $deOutraRedec->id,
            // Numericamente igual a um municipios.id da REDEC filtrada...
            'municipio_id'         => $this->municipioDaRedec->id,
            // ...mas o protocolo aponta para um municipio de outra regiao, e o
            // protocolo e quem manda quando traz um IBGE de 7 digitos.
            'n_protocolo_fide'     => 'MG-F-' . $this->municipioDeOutraRedec->codigo_ibge . '-54321-20260115',
        ]);

        $this->assertNotContains($deOutraRedec->id, $this->filtraPorRedec(self::REDEC_FILTRADA));
    }

    public function test_filtro_por_municipio_resolve_os_dois_espacos_de_id(): void
    {
        $novo = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $novo->id,
            'municipio_id'         => $this->municipioDaRedec->id,
            'n_protocolo_fide'     => 'MG-F-31-70000-20260115',
        ]);

        $legado = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $legado->id,
            'municipio_id'         => 999999,
            'n_protocolo_fide'     => 'MG-F-' . $this->municipioDaRedec->codigo_ibge . '-12345-20260115',
        ]);

        $outro = $this->criaProcesso(null);
        DecretoMunicipio::create([
            'entrada_processos_id' => $outro->id,
            'municipio_id'         => 999999,
            'n_protocolo_fide'     => 'MG-F-' . $this->municipioDeOutraRedec->codigo_ibge . '-12345-20260115',
        ]);

        $filtro = new ProcessoFilter(new Request(['municipio_id' => $this->municipioDaRedec->id]));
        $encontrados = $filtro->apply(Processo::query())->pluck('id')->map('intval')->all();

        $this->assertContains($novo->id, $encontrados);
        $this->assertContains($legado->id, $encontrados);
        $this->assertNotContains($outro->id, $encontrados);
    }

    public function test_vinculo_removido_nao_conta_para_a_redec(): void
    {
        $this->ancoraMunicipioNaRedec();

        $comVinculoRemovido = $this->criaProcesso(null);
        $vinculo = DecretoMunicipio::create([
            'entrada_processos_id' => $comVinculoRemovido->id,
            'municipio_id'         => $this->municipioDaRedec->id,
            'n_protocolo_fide'     => 'MG-F-31-70000-20260115',
        ]);
        $vinculo->delete();

        $this->assertNotContains($comVinculoRemovido->id, $this->filtraPorRedec(self::REDEC_FILTRADA));
    }
}
