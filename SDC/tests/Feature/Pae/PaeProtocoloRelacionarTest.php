<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeProtocoloRelacionarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function base(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'num_protocolo' => now()->format('d.m.Y') . '-0001-001',
        ]);
    }

    public function test_relacionar_cria_versao_seguinte(): void
    {
        $base = $this->base();
        $user = User::factory()->create();

        $novo = app(PaeProtocoloService::class)->relacionar($base, $user);

        $this->assertSame(
            now()->format('d.m.Y') . '-0001-002',
            $novo->num_protocolo
        );
        $this->assertSame($base->id, $novo->protocolo_origem_id);
        $this->assertSame($base->pae_empnto_id, $novo->pae_empnto_id);
        $this->assertSame('novo', $novo->status->value);
        $this->assertDatabaseHas('pae_timeline', [
            'protocolo_id' => $base->id,
            'evento'       => 'relacionamento',
        ]);
        $this->assertDatabaseHas('pae_timeline', [
            'protocolo_id' => $novo->id,
            'evento'       => 'criacao',
        ]);
    }

    public function test_relacionar_rejeita_formato_antigo(): void
    {
        $base = PaeProtocolo::factory()->create(['num_protocolo' => '01.01.2025.003']);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(PaeProtocoloService::class)->relacionar($base, $user);
    }

    public function test_rota_relacionar_exige_permissao_e_redireciona(): void
    {
        Permission::firstOrCreate(['name' => 'pae.protocolos.create', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.create');

        $base = $this->base();

        $this->actingAs($user)
            ->post(route('pae.protocolo.relacionar', $base))
            ->assertRedirect();

        $this->assertDatabaseHas('pae_protocolos', [
            'protocolo_origem_id' => $base->id,
        ]);
    }
}
