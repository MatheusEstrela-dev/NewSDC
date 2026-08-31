<?php

declare(strict_types=1);

namespace Tests\Unit\Tdap;

use App\Modules\Tdap\Enums\SituacaoAta;
use PHPUnit\Framework\TestCase;

/**
 * Contrato do enum de situacao da Ata.
 *
 * Estes testes travam os `value` do enum: eles viajam para o front (badge,
 * filtro e query string), entao renomear um caso e mudanca de contrato.
 */
class SituacaoAtaTest extends TestCase
{
    public function test_valores_do_enum_sao_o_contrato_com_o_front(): void
    {
        $this->assertSame('agendada', SituacaoAta::Agendada->value);
        $this->assertSame('vigente', SituacaoAta::Vigente->value);
        $this->assertSame('vencida', SituacaoAta::Vencida->value);
        $this->assertSame('inativa', SituacaoAta::Inativa->value);
    }

    public function test_cada_caso_tem_label_e_cor(): void
    {
        foreach (SituacaoAta::cases() as $caso) {
            $this->assertNotSame('', $caso->label());
            $this->assertContains(
                $caso->cor(),
                ['info', 'success', 'warning', 'danger', 'neutral'],
                "Cor do caso {$caso->value} precisa existir no mapa de badges do front."
            );
        }
    }

    public function test_vencida_usa_cor_de_alerta_e_vigente_cor_positiva(): void
    {
        $this->assertSame('danger', SituacaoAta::Vencida->cor());
        $this->assertSame('success', SituacaoAta::Vigente->cor());
    }

    public function test_atalhos_de_leitura(): void
    {
        $this->assertTrue(SituacaoAta::Vigente->isVigente());
        $this->assertFalse(SituacaoAta::Vencida->isVigente());

        $this->assertTrue(SituacaoAta::Vencida->isVencida());
        $this->assertFalse(SituacaoAta::Inativa->isVencida());
    }

    public function test_options_devolve_o_formato_do_select_do_front(): void
    {
        $options = SituacaoAta::options();

        $this->assertCount(count(SituacaoAta::cases()), $options);

        foreach ($options as $option) {
            // SelectInput.vue espera exatamente {value, label}.
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotNull(SituacaoAta::tryFrom($option['value']));
        }
    }

    public function test_valor_desconhecido_nao_resolve_para_um_caso(): void
    {
        // Base do filtro tolerante em Ata::scopeSituacao: lixo nao filtra nada.
        $this->assertNull(SituacaoAta::tryFrom('encerrada'));
        $this->assertNull(SituacaoAta::tryFrom(''));
    }
}
