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
}
