<?php

declare(strict_types=1);

namespace Tests\Feature\Pae\Api;

use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificacaoApiTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioComPermissao(string ...$perms): User
    {
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perms);

        return $user;
    }

    private function protocoloDelegado(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'status'            => 'notificacao',
            'analista_atual_id' => User::factory()->create()->id,
        ]);
    }

    public function test_index_lista_notificacoes(): void
    {
        $user = $this->usuarioComPermissao('pae.protocolos.view');
        $protocolo = $this->protocoloDelegado();

        app(PaeNotificacaoService::class)->emitir($protocolo, $user, ['num_sei' => 'SEI-API-1']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/pae/protocolos/{$protocolo->id}/notificacoes")
            ->assertOk()
            ->assertJsonPath('data.0.num_sei', 'SEI-API-1')
            ->assertJsonPath('data.0.ciclo', 1);
    }

    public function test_store_emite_notificacao(): void
    {
        $user = $this->usuarioComPermissao('pae.protocolos.edit');
        $protocolo = $this->protocoloDelegado();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pae/protocolos/{$protocolo->id}/notificacoes", [
                'num_sei' => 'SEI-API-2',
            ])
            ->assertCreated()
            ->assertJsonPath('data.num_sei', 'SEI-API-2');
    }

    public function test_store_retorna_422_sem_analista(): void
    {
        $user = $this->usuarioComPermissao('pae.protocolos.edit');
        $protocolo = PaeProtocolo::factory()->create(['analista_atual_id' => null]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pae/protocolos/{$protocolo->id}/notificacoes", [
                'num_sei' => 'SEI-API-3',
            ])
            ->assertUnprocessable();
    }

    public function test_devolutiva_fecha_ciclo(): void
    {
        $user = $this->usuarioComPermissao('pae.protocolos.edit');
        $protocolo = $this->protocoloDelegado();

        $notificacao = app(PaeNotificacaoService::class)
            ->emitir($protocolo, $user, ['num_sei' => 'SEI-API-4']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pae/notificacoes/{$notificacao->id}/devolutiva", [
                'dt_devolutiva' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.dt_devolutiva', now()->toDateString());
    }
}
