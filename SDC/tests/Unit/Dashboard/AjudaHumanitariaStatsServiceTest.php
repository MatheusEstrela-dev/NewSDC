<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Modules\AjudaHumanitaria\Models\Auxilio;
use App\Modules\AjudaHumanitaria\Services\AjudaHumanitariaStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjudaHumanitariaStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AjudaHumanitariaStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AjudaHumanitariaStatsService();
    }

    public function test_retorna_zero_quando_nao_ha_auxilios(): void
    {
        $this->assertSame(0, $this->service->getTotal());
    }

    public function test_conta_total_de_auxilios(): void
    {
        Auxilio::factory()->count(7)->create();

        $this->assertSame(7, $this->service->getTotal());
    }

    public function test_retorna_contagens_mensais_dos_ultimos_n_meses(): void
    {
        Auxilio::factory()->create(['created_at' => now()->subMonths(0)]);
        Auxilio::factory()->create(['created_at' => now()->subMonths(1)]);
        Auxilio::factory()->create(['created_at' => now()->subMonths(1)]);

        $result = $this->service->getMonthlyCounts(3);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('label', $result[0]);
        $this->assertArrayHasKey('value', $result[0]);
    }
}
