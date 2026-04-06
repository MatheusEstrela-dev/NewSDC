<?php

declare(strict_types=1);

namespace Tests\Feature\GlobalSearch;

use App\Models\User;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSearchControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::tags(['global_search'])->flush();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/global-search?q=test');

        $response->assertStatus(401);
    }

    public function test_query_shorter_than_3_chars_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search?q=ab');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }

    public function test_missing_query_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }

    public function test_valid_query_returns_200_with_correct_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search?q=abc');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'query',
                     'results' => [
                         'decretacoes',
                         'rat',
                         'demandas',
                         'pae',
                     ],
                 ]);
    }

    public function test_returns_matching_decretacao(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Processo::create([
            'n_protocolo_fide' => 'MG-F-9999999-00000-20251231',
            'data_entrada'     => now(),
            'processo'         => 'Federal',
            'tipo_desastre'    => 'SE',
        ]);

        $response = $this->actingAs($user)->getJson('/api/global-search?q=MG-F-9999999');

        $response->assertStatus(200)
                 ->assertJsonPath('results.decretacoes.0.title', 'MG-F-9999999-00000-20251231')
                 ->assertJsonPath('results.decretacoes.0.tag', 'DECRETO');
    }
}
