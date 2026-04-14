<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Modules\Dashboard\DTOs\DashboardStatsDTO;
use PHPUnit\Framework\TestCase;

class DashboardStatsDTOTest extends TestCase
{
    private function makeDTO(array $overrides = []): DashboardStatsDTO
    {
        $defaults = [
            'ratAbertas'         => 10,
            'paeEmAnalise'       => 5,
            'decretosAprovados'  => 20,
            'demandasConcluidas' => 15,
            'ratTrend'           => 10.0,
            'paeTrend'           => -5.0,
            'decretoTrend'       => 0.0,
            'demandaTrend'       => 25.0,
            'moduleDistribution' => [],
            'barData6M'          => [],
            'barData12M'         => [],
            'sparklines'         => [],
        ];

        return new DashboardStatsDTO(...array_merge($defaults, $overrides));
    }

    public function test_dto_e_criado_com_valores_corretos(): void
    {
        $dto = $this->makeDTO(['ratAbertas' => 42]);

        $this->assertSame(42, $dto->ratAbertas);
        $this->assertSame(5, $dto->paeEmAnalise);
    }

    public function test_to_array_contem_todas_as_chaves(): void
    {
        $dto  = $this->makeDTO();
        $keys = array_keys($dto->toArray());

        foreach (['ratAbertas', 'paeEmAnalise', 'decretosAprovados', 'demandasConcluidas',
                  'ratTrend', 'paeTrend', 'decretoTrend', 'demandaTrend',
                  'moduleDistribution', 'barData6M', 'barData12M', 'sparklines'] as $key) {
            $this->assertContains($key, $keys, "Chave '{$key}' ausente em toArray()");
        }
    }

    public function test_trend_negativo_e_preservado(): void
    {
        $dto = $this->makeDTO(['paeTrend' => -12.5]);

        $this->assertSame(-12.5, $dto->paeTrend);
    }
}
