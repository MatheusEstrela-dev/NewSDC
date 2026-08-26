<?php

declare(strict_types=1);

namespace Tests\Feature\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ViaturaCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_cria_viatura_com_enums_convertidos(): void
    {
        $viatura = Viatura::factory()->create([
            'placa' => 'QMV-2241',
            'prefixo' => 'SW4',
            'nivel_combustivel' => NivelCombustivel::QUARTO_3,
        ]);

        $fresh = Viatura::findOrFail($viatura->id);

        $this->assertInstanceOf(NivelCombustivel::class, $fresh->nivel_combustivel);
        $this->assertSame(NivelCombustivel::QUARTO_3, $fresh->nivel_combustivel);
        $this->assertInstanceOf(StatusViatura::class, $fresh->status);
        $this->assertSame('QMV-2241', $fresh->placa);
    }

    public function test_scope_ativas_ignora_viatura_inativa(): void
    {
        Viatura::factory()->create(['ativo' => true]);
        Viatura::factory()->create(['ativo' => false]);

        $this->assertSame(1, Viatura::ativas()->count());
    }

    public function test_service_lista_filtrando_por_status(): void
    {
        Viatura::factory()->count(2)->create();
        Viatura::factory()->emManutencao()->create();

        $service = app(\App\Modules\Plantao\Services\ViaturaService::class);

        $todas = $service->list([], 50);
        $manutencao = $service->list(['status' => StatusViatura::MANUTENCAO->value], 50);

        $this->assertSame(3, $todas->total());
        $this->assertSame(1, $manutencao->total());
    }

    public function test_service_estatisticas_contam_por_status(): void
    {
        Viatura::factory()->count(2)->create();
        Viatura::factory()->emManutencao()->create();

        $stats = app(\App\Modules\Plantao\Services\ViaturaService::class)->getStatistics();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['disponiveis']);
        $this->assertSame(1, $stats['indisponiveis']);
    }

    public function test_dto_expoe_percentual_para_o_gauge(): void
    {
        $viatura = Viatura::factory()->create([
            'nivel_combustivel' => \App\Modules\Plantao\Enums\NivelCombustivel::QUARTO_3,
            'hodometro_atual' => 112799,
        ]);

        $dto = \App\Modules\Plantao\DTOs\ViaturaListDTO::fromModel($viatura);

        $this->assertSame(75, $dto->combustivel_percentual);
        $this->assertSame('3/4', $dto->combustivel_label);
        $this->assertSame(112799, $dto->hodometro);
    }
}
