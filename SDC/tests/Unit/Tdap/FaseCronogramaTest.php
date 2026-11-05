<?php

declare(strict_types=1);

namespace Tests\Unit\Tdap;

use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Enums\FaseCronograma;
use PHPUnit\Framework\TestCase;

class FaseCronogramaTest extends TestCase
{
    public function test_grafo_da_tramitacao(): void
    {
        $this->assertSame(FaseCronograma::EmAnalise, FaseCronograma::Elaboracao->destino(AcaoTramite::Enviar));
        $this->assertSame(FaseCronograma::Aprovado, FaseCronograma::EmAnalise->destino(AcaoTramite::Aprovar));
        $this->assertSame(FaseCronograma::Elaboracao, FaseCronograma::EmAnalise->destino(AcaoTramite::Devolver));
        $this->assertSame(FaseCronograma::EmExecucao, FaseCronograma::Aprovado->destino(AcaoTramite::Ativar));
        $this->assertSame(FaseCronograma::EmAnalise, FaseCronograma::Aprovado->destino(AcaoTramite::Devolver));
    }

    public function test_acoes_que_nao_se_aplicam_a_fase(): void
    {
        $this->assertNull(FaseCronograma::Elaboracao->destino(AcaoTramite::Aprovar));
        $this->assertNull(FaseCronograma::Elaboracao->destino(AcaoTramite::Devolver));
        $this->assertNull(FaseCronograma::EmAnalise->destino(AcaoTramite::Ativar));
        $this->assertSame([], FaseCronograma::EmExecucao->acoesPermitidas());
    }

    public function test_cada_fase_ativa_tem_um_responsavel_com_permissao(): void
    {
        foreach ([FaseCronograma::Elaboracao, FaseCronograma::EmAnalise, FaseCronograma::Aprovado] as $fase) {
            $this->assertNotNull($fase->permissao());
            foreach ($fase->acoesPermitidas() as $acao) {
                $this->assertNotNull($fase->permissaoPara($acao), "{$fase->value}/{$acao->value} sem permissao");
            }
            $this->assertNotSame([], $fase->acoesPermitidas());
        }
        $this->assertNull(FaseCronograma::EmExecucao->permissao());
    }
}
