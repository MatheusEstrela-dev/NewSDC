<?php

declare(strict_types=1);

namespace Tests\Unit\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusViatura;
use PHPUnit\Framework\TestCase;

class NivelCombustivelTest extends TestCase
{
    public function test_percentual_de_cada_nivel(): void
    {
        $this->assertSame(0, NivelCombustivel::VAZIO->percentual());
        $this->assertSame(25, NivelCombustivel::QUARTO_1->percentual());
        $this->assertSame(50, NivelCombustivel::QUARTO_2->percentual());
        $this->assertSame(75, NivelCombustivel::QUARTO_3->percentual());
        $this->assertSame(100, NivelCombustivel::QUARTO_4->percentual());
    }

    public function test_label_usa_a_notacao_de_quartos_do_relatorio(): void
    {
        $this->assertSame('0/4', NivelCombustivel::VAZIO->label());
        $this->assertSame('3/4', NivelCombustivel::QUARTO_3->label());
        $this->assertSame('4/4', NivelCombustivel::QUARTO_4->label());
    }

    public function test_periodo_tem_label_curto_para_o_relatorio(): void
    {
        $this->assertSame('06h às 16h', PeriodoPlantao::DIURNO->labelCurto());
        $this->assertSame('16h às 02h', PeriodoPlantao::NOTURNO->labelCurto());
    }

    public function test_periodo_label_completo_reflete_a_operacao_real(): void
    {
        $this->assertSame('06:00hs as 16:00hs', PeriodoPlantao::DIURNO->label());
        $this->assertSame('16:00hs as 02:00hs', PeriodoPlantao::NOTURNO->label());
    }

    public function test_status_viatura_define_quem_pode_sair(): void
    {
        $this->assertTrue(StatusViatura::DISPONIVEL->podeSair());
        $this->assertFalse(StatusViatura::EM_TRANSITO->podeSair());
        $this->assertFalse(StatusViatura::MANUTENCAO->podeSair());
        $this->assertFalse(StatusViatura::CEDIDA->podeSair());
        $this->assertFalse(StatusViatura::INDISPONIVEL->podeSair());
    }

    public function test_status_viatura_define_quem_esta_em_condicoes(): void
    {
        $this->assertTrue(StatusViatura::DISPONIVEL->emCondicoes());
        $this->assertTrue(StatusViatura::EM_TRANSITO->emCondicoes());
        $this->assertFalse(StatusViatura::MANUTENCAO->emCondicoes());
        $this->assertFalse(StatusViatura::CEDIDA->emCondicoes());
        $this->assertFalse(StatusViatura::INDISPONIVEL->emCondicoes());
    }
}
