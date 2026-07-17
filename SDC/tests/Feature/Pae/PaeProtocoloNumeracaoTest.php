<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaeProtocoloNumeracaoTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): PaeProtocoloService
    {
        return app(PaeProtocoloService::class);
    }

    public function test_gera_numero_no_formato_diario_com_versao(): void
    {
        $num = $this->service()->gerarNumProtocolo();

        $hoje = now()->format('d.m.Y');
        $this->assertMatchesRegularExpression(
            '/^' . preg_quote($hoje, '/') . '-\d{4}-001$/',
            $num
        );
    }

    public function test_sequencial_diario_incrementa(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0007-001']);

        $this->assertSame($hoje . '-0008-001', $this->service()->gerarNumProtocolo());
    }

    public function test_ignora_formato_antigo_no_calculo(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '.015']);

        $this->assertSame($hoje . '-0001-001', $this->service()->gerarNumProtocolo());
    }

    public function test_versoes_relacionadas_nao_afetam_sequencial_diario(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0002-001']);
        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0002-003']);

        $this->assertSame($hoje . '-0003-001', $this->service()->gerarNumProtocolo());
    }
}
