<?php

declare(strict_types=1);

namespace Tests\Unit\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaSnapshot;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class RelatorioPassagemServiceTest extends TestCase
{
    private function plantaoFake(): Plantao
    {
        $plantao = new Plantao([
            'plantonista_nome' => 'Sgt Leandro',
            'plantonista_saida_nome' => 'Sgt Deivison',
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::NOTURNO->value,
            'localizacao' => 'Predio Alterosas',
            'ocorrencias_destaque' => null,
        ]);

        $a = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-2241',
            'hodometro' => 112799,
            'nivel_combustivel' => NivelCombustivel::QUARTO_3->value,
            'alteracoes' => null,
            'ultimo_condutor_nome' => 'Sgt Egidio',
            'anotacao' => 'Exclusiva Sobreaviso',
            'em_condicoes' => true,
        ]);

        $b = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-2245',
            'hodometro' => 103798,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'alteracoes' => null,
            'ultimo_condutor_nome' => 'Sgt Mello',
            'anotacao' => null,
            'em_condicoes' => true,
        ]);

        $fora = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-9999',
            'hodometro' => 1,
            'nivel_combustivel' => NivelCombustivel::VAZIO->value,
            'em_condicoes' => false,
        ]);

        $plantao->setRelation('snapshots', new Collection([$a, $b, $fora]));

        return $plantao;
    }

    public function test_cabecalho_usa_o_label_curto_do_periodo(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString(
            'Serviço de Plantão (25/08/2026 - 16h às 02h)',
            $texto
        );
        $this->assertStringContainsString('Assumido por: Sgt Leandro', $texto);
        $this->assertStringContainsString('Saindo de serviço: Sgt Deivison', $texto);
    }

    public function test_anotacao_sai_entre_parenteses_e_ausente_nao_deixa_sobra(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('SW4 - QMV-2241 (Exclusiva Sobreaviso)', $texto);
        $this->assertStringContainsString("SW4 - QMV-2245\n", $texto);
    }

    public function test_alteracao_vazia_renderiza_sem_alteracoes(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Alterações: Sem alterações', $texto);
    }

    public function test_hodometro_sai_sem_separador_de_milhar(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        // O relatorio praticado hoje escreve 112799, nao 112.799.
        $this->assertStringContainsString('Hodômetro: 112799', $texto);
        $this->assertStringNotContainsString('112.799', $texto);
    }

    public function test_viatura_fora_de_condicoes_nao_entra_na_listagem(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringNotContainsString('QMV-9999', $texto);
    }

    public function test_sem_ocorrencia_renderiza_nao_houve(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Não houve.', $texto);
        $this->assertStringNotContainsString('Ocorrências ou ações de destaque', $texto);
    }

    public function test_com_ocorrencia_renderiza_o_cabecalho_do_bloco(): void
    {
        $plantao = $this->plantaoFake();
        $plantao->ocorrencias_destaque = 'COLISAO ENTRE ONIBUS E CAMINHAO.';

        $texto = app(RelatorioPassagemService::class)->renderizar($plantao);

        $this->assertStringContainsString(
            'Ocorrências ou ações de destaque do turno anterior:',
            $texto
        );
        $this->assertStringContainsString('COLISAO ENTRE ONIBUS E CAMINHAO.', $texto);
        $this->assertStringNotContainsString('Não houve.', $texto);
    }

    public function test_sem_plantonista_de_saida_omite_a_linha(): void
    {
        $plantao = $this->plantaoFake();
        $plantao->plantonista_saida_nome = null;

        $texto = app(RelatorioPassagemService::class)->renderizar($plantao);

        $this->assertStringNotContainsString('Saindo de serviço:', $texto);
    }

    public function test_rodape_traz_contatos_e_link_do_config(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Contatos para abastecimento com Diesel (RMBH):', $texto);
        $this->assertStringContainsString('3 BBM: 031 3490-5531', $texto);
        $this->assertStringContainsString('app.powerbi.com/view', $texto);
        $this->assertStringContainsString('DTT: saida de viaturas', $texto);
        $this->assertStringContainsString('Plantão GMG: saida de viaturas', $texto);
    }
}
