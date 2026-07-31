<?php

declare(strict_types=1);

namespace Tests\Unit\Decretacoes;

use App\Modules\Decretacoes\Support\Vigencia;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Regra de vigencia do decreto municipal (prazo padrao de 180 dias).
 */
class VigenciaTest extends TestCase
{
    private Carbon $hoje;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoje = Carbon::create(2026, 7, 30);
    }

    public function test_prazo_ausente_ou_invalido_assume_180_dias(): void
    {
        $this->assertSame(180, Vigencia::prazo(null));
        $this->assertSame(180, Vigencia::prazo(''));
        $this->assertSame(180, Vigencia::prazo(0));
        $this->assertSame(180, Vigencia::prazo('nao numerico'));
        $this->assertSame(90, Vigencia::prazo('90'));
        $this->assertSame(365, Vigencia::prazo(365));

        $this->assertTrue(Vigencia::usouPrazoPadrao(null));
        $this->assertFalse(Vigencia::usouPrazoPadrao(180));
    }

    public function test_vencimento_e_publicacao_mais_prazo(): void
    {
        $this->assertSame('2026-06-30', Vigencia::vencimento('2026-01-01', null)->toDateString());
        $this->assertSame('2026-04-01', Vigencia::vencimento('2026-01-01', 90)->toDateString());
        $this->assertNull(Vigencia::vencimento(null, 180));
        $this->assertNull(Vigencia::vencimento('', 180));
    }

    public function test_dias_restantes_e_assinado(): void
    {
        // Vence em 2026-12-28 (30/07/2026 + 151 dias)
        $this->assertSame(151, Vigencia::diasRestantes('2026-07-01', 180, $this->hoje));

        // Vencido: negativo indica quantos dias faz que expirou
        $this->assertSame(-395, Vigencia::diasRestantes('2025-01-01', 180, $this->hoje));

        // Sem data de publicacao nao existe vigencia calculada
        $this->assertNull(Vigencia::diasRestantes(null, 180, $this->hoje));
    }

    public function test_dia_do_vencimento_ainda_e_vigente(): void
    {
        $publicacao = $this->hoje->copy()->subDays(180)->toDateString();

        $this->assertSame(0, Vigencia::diasRestantes($publicacao, 180, $this->hoje));
        $this->assertTrue(Vigencia::isVigente($publicacao, 180, $this->hoje));
        $this->assertFalse(Vigencia::isVencido($publicacao, 180, $this->hoje));
        $this->assertTrue(Vigencia::isProximoVencer($publicacao, 180, $this->hoje));
    }

    public function test_dia_seguinte_ao_vencimento_esta_vencido(): void
    {
        $publicacao = $this->hoje->copy()->subDays(181)->toDateString();

        $this->assertSame(-1, Vigencia::diasRestantes($publicacao, 180, $this->hoje));
        $this->assertFalse(Vigencia::isVigente($publicacao, 180, $this->hoje));
        $this->assertTrue(Vigencia::isVencido($publicacao, 180, $this->hoje));
        $this->assertFalse(Vigencia::isProximoVencer($publicacao, 180, $this->hoje));
    }

    public function test_janela_de_proximo_vencer_e_de_30_dias(): void
    {
        $dentro = $this->hoje->copy()->subDays(180 - 30)->toDateString();  // vence em 30 dias
        $fora   = $this->hoje->copy()->subDays(180 - 31)->toDateString();  // vence em 31 dias

        $this->assertTrue(Vigencia::isProximoVencer($dentro, 180, $this->hoje));
        $this->assertFalse(Vigencia::isProximoVencer($fora, 180, $this->hoje));
    }

    public function test_sem_publicacao_nao_conta_como_vencido(): void
    {
        $this->assertTrue(Vigencia::isVigente(null, null, $this->hoje));
        $this->assertFalse(Vigencia::isVencido(null, null, $this->hoje));
        $this->assertFalse(Vigencia::isProximoVencer(null, null, $this->hoje));
    }

    public function test_sql_vencimento_aplica_coalesce_do_prazo_padrao(): void
    {
        $this->assertSame(
            "(data_publicacao_mg + (COALESCE(prazo_vigencia, 180) || ' days')::interval)::date",
            Vigencia::sqlVencimento()
        );
    }
}
