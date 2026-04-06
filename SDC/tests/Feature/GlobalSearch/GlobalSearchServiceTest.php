<?php

declare(strict_types=1);

namespace Tests\Feature\GlobalSearch;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Rat\Models\Rat;
use App\Modules\Demandas\Models\Task;
use App\Modules\Demandas\Enums\TaskStatus;
use App\Modules\Demandas\Enums\TipoTask;
use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\Prioridade;
use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    use DatabaseTransactions;

    private GlobalSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GlobalSearchService::class);
        Cache::tags(['global_search'])->flush();

        $user = User::factory()->create();
        Auth::login($user);
    }

    public function test_returns_decretacao_by_protocolo_prefix(): void
    {
        Processo::create([
            'n_protocolo_fide' => 'MG-F-3101607-12300-20251231',
            'data_entrada'     => now(),
            'processo'         => 'Federal',
            'tipo_desastre'    => 'SE',
        ]);

        $results = $this->service->search('MG-F-3101607');

        $this->assertNotEmpty($results['decretacoes']);
        $this->assertEquals('MG-F-3101607-12300-20251231', $results['decretacoes'][0]['title']);
        $this->assertEquals('scale', $results['decretacoes'][0]['icon']);
        $this->assertEquals('DECRETO', $results['decretacoes'][0]['tag']);
    }

    public function test_returns_rat_by_protocolo_prefix(): void
    {
        Rat::create([
            'protocolo'    => 'RAT-2025-00042',
            'status'       => 'rascunho',
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ]);

        $results = $this->service->search('RAT-2025');

        $this->assertNotEmpty($results['rat']);
        $this->assertEquals('RAT-2025-00042', $results['rat'][0]['title']);
        $this->assertEquals('document', $results['rat'][0]['icon']);
    }

    public function test_returns_demanda_by_titulo(): void
    {
        Task::create([
            'protocolo'  => 'SDC-2025-000001',
            'tipo'       => TipoTask::INCIDENTE->value,
            'titulo'     => 'Problema com sistema de alertas',
            'status'     => TaskStatus::ABERTA->value,
            'impacto'    => Impacto::MEDIO->value,
            'urgencia'   => Urgencia::MEDIA->value,
            'prioridade' => Prioridade::MEDIA->value,
        ]);

        $results = $this->service->search('alertas');

        $this->assertNotEmpty($results['demandas']);
        $this->assertStringContainsString('alertas', strtolower($results['demandas'][0]['title']));
    }

    public function test_returns_pae_by_num_protocolo_prefix(): void
    {
        PaeProtocolo::create([
            'num_protocolo' => 'PAE-2025-0099',
            'status'        => 'analise',
            'sei_numero'    => '1234567',
        ]);

        $results = $this->service->search('PAE-2025');

        $this->assertNotEmpty($results['pae']);
        $this->assertEquals('PAE-2025-0099', $results['pae'][0]['title']);
    }

    public function test_limits_to_5_results_per_module(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Processo::create([
                'n_protocolo_fide' => "LIMIT-TEST-{$i}",
                'data_entrada'     => now(),
                'processo'         => 'Federal',
                'tipo_desastre'    => 'SE',
            ]);
        }

        $results = $this->service->search('LIMIT-TEST');

        $this->assertCount(5, $results['decretacoes']);
    }

    public function test_caches_result_in_redis(): void
    {
        Cache::tags(['global_search'])->flush();

        $this->service->search('CACHE-HIT-TEST');

        $key = 'global_search:' . md5('cache-hit-test');
        $this->assertTrue(Cache::store('redis')->tags(['global_search'])->has($key));
    }

    public function test_returns_empty_arrays_when_no_match(): void
    {
        $results = $this->service->search('ZZZNOTEXISTS999');

        $this->assertEmpty($results['decretacoes']);
        $this->assertEmpty($results['rat']);
        $this->assertEmpty($results['demandas']);
        $this->assertEmpty($results['pae']);
    }
}
