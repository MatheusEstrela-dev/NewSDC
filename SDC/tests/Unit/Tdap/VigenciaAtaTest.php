<?php

declare(strict_types=1);

namespace Tests\Unit\Tdap;

use App\Modules\Tdap\Enums\SituacaoAta;
use App\Modules\Tdap\Support\VigenciaAta;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Regra de vigencia da Ata de Registro de Precos (TDAP).
 *
 * Teste puro: `$hoje` e injetado, entao nao dependemos do relogio real nem do
 * banco — nao ha risco de o teste passar hoje e quebrar amanha.
 */
class VigenciaAtaTest extends TestCase
{
    private Carbon $hoje;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoje = Carbon::create(2026, 8, 12);
    }

    /** Atalho: classifica uma ata ligada com as datas informadas. */
    private function situacao(mixed $inicio, mixed $final, bool $ativo = true): SituacaoAta
    {
        return VigenciaAta::situacao($ativo, $inicio, $final, $this->hoje);
    }

    public function test_ata_dentro_da_janela_esta_vigente(): void
    {
        $this->assertSame(
            SituacaoAta::Vigente,
            $this->situacao('2026-01-01', '2026-12-31')
        );
    }

    public function test_dia_inicial_e_dia_final_contam_como_vigentes(): void
    {
        // Bordas inclusivas: dt_inicio = hoje e dt_final = hoje ainda vigoram.
        $this->assertSame(SituacaoAta::Vigente, $this->situacao('2026-08-12', '2026-12-31'));
        $this->assertSame(SituacaoAta::Vigente, $this->situacao('2026-01-01', '2026-08-12'));

        // E vencer hoje significa zero dia restante, nao um dia negativo.
        $this->assertSame(0, VigenciaAta::diasRestantes('2026-08-12', $this->hoje));
    }

    public function test_dia_seguinte_ao_final_esta_vencida(): void
    {
        $this->assertSame(SituacaoAta::Vencida, $this->situacao('2026-01-01', '2026-08-11'));
        $this->assertSame(-1, VigenciaAta::diasRestantes('2026-08-11', $this->hoje));
    }

    public function test_ata_que_ainda_nao_comecou_esta_agendada(): void
    {
        $this->assertSame(SituacaoAta::Agendada, $this->situacao('2026-08-13', '2027-08-13'));

        // O dia anterior ao inicio ainda e agendada; o proprio dia ja e vigente.
        $this->assertSame(SituacaoAta::Vigente, $this->situacao('2026-08-12', '2027-08-13'));
    }

    public function test_flag_ativo_falso_tem_precedencia_sobre_as_datas(): void
    {
        // Mesmo dentro do prazo, ata desligada na mao e Inativa.
        $this->assertSame(SituacaoAta::Inativa, $this->situacao('2026-01-01', '2026-12-31', ativo: false));
        // E fora do prazo tambem: Inativa vence Vencida.
        $this->assertSame(SituacaoAta::Inativa, $this->situacao('2020-01-01', '2020-12-31', ativo: false));
    }

    public function test_datas_ausentes_nao_tornam_a_ata_vencida(): void
    {
        // Sem data nao existe prazo a expirar (mesma escolha de Decretacoes).
        $this->assertSame(SituacaoAta::Vigente, $this->situacao(null, null));
        $this->assertSame(SituacaoAta::Vigente, $this->situacao('2026-01-01', null));
        $this->assertSame(SituacaoAta::Vigente, $this->situacao(null, '2026-12-31'));

        $this->assertNull(VigenciaAta::diasRestantes(null, $this->hoje));
        $this->assertNull(VigenciaAta::diasRestantes('', $this->hoje));
    }

    public function test_dias_restantes_e_assinado(): void
    {
        $this->assertSame(19, VigenciaAta::diasRestantes('2026-08-31', $this->hoje));
        $this->assertSame(-224, VigenciaAta::diasRestantes('2025-12-31', $this->hoje));
    }

    public function test_hora_do_dia_nao_afeta_a_comparacao(): void
    {
        // Comparacao e por DIA: 23:59 do dia final ainda e vigente.
        $hojeTarde = Carbon::create(2026, 8, 12, 23, 59, 59);

        $this->assertSame(
            SituacaoAta::Vigente,
            VigenciaAta::situacao(true, '2026-08-12', '2026-08-12', $hojeTarde)
        );
        $this->assertSame(0, VigenciaAta::diasRestantes('2026-08-12', $hojeTarde));
    }

    public function test_janela_de_alerta_de_vencimento_e_de_30_dias(): void
    {
        $this->assertSame(30, VigenciaAta::JANELA_PROXIMO_VENCER_DIAS);

        // 30 dias entra no alerta; 31 nao.
        $this->assertTrue(VigenciaAta::isProximaVencer(true, '2026-01-01', '2026-09-11', $this->hoje));
        $this->assertFalse(VigenciaAta::isProximaVencer(true, '2026-01-01', '2026-09-12', $this->hoje));

        // Vence hoje: ainda vigente, logo ainda alerta.
        $this->assertTrue(VigenciaAta::isProximaVencer(true, '2026-01-01', '2026-08-12', $this->hoje));
    }

    public function test_alerta_de_vencimento_so_vale_para_ata_vigente(): void
    {
        // Vencida nao alerta "vence em X dias" — ja venceu.
        $this->assertFalse(VigenciaAta::isProximaVencer(true, '2026-01-01', '2026-08-11', $this->hoje));
        // Agendada nao alerta — nem comecou.
        $this->assertFalse(VigenciaAta::isProximaVencer(true, '2026-08-20', '2026-08-25', $this->hoje));
        // Inativa nao alerta.
        $this->assertFalse(VigenciaAta::isProximaVencer(false, '2026-01-01', '2026-08-20', $this->hoje));
    }

    public function test_aceita_carbon_alem_de_string_sem_mutar_o_original(): void
    {
        $final = Carbon::create(2026, 8, 31, 14, 30);

        $this->assertSame(SituacaoAta::Vigente, $this->situacao(Carbon::create(2026, 1, 1), $final));
        $this->assertSame(19, VigenciaAta::diasRestantes($final, $this->hoje));

        // O Carbon recebido nao pode ser truncado por efeito colateral.
        $this->assertSame('14:30', $final->format('H:i'));
        // Nem a referencia de hoje.
        $this->assertSame('2026-08-12 00:00:00', $this->hoje->format('Y-m-d H:i:s'));
    }

    public function test_data_invalida_e_tratada_como_ausente(): void
    {
        $this->assertSame(SituacaoAta::Vigente, $this->situacao('nao é data', 'lixo'));
        $this->assertNull(VigenciaAta::diasRestantes('nao é data', $this->hoje));
    }
}
