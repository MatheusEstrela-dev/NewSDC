<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeNotificacaoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function service(): PaeNotificacaoService
    {
        return app(PaeNotificacaoService::class);
    }

    private function protocoloDelegado(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'status'            => 'notificacao',
            'analista_atual_id' => User::factory()->create()->id,
        ]);
    }

    public function test_emitir_cria_analise_e_notificacao_ciclo_1(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        $notificacao = $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-1']);

        $this->assertDatabaseHas('pae_analises', ['pae_protocolo_id' => $protocolo->id]);
        $this->assertSame('SEI-1', $notificacao->num_sei);
        $this->assertSame(now()->toDateString(), $notificacao->dt_notificacao->toDateString());
        $this->assertDatabaseHas('pae_timeline', [
            'protocolo_id' => $protocolo->id,
            'evento'       => 'notificacao',
        ]);
    }

    public function test_emitir_bloqueia_sem_analista(): void
    {
        $protocolo = PaeProtocolo::factory()->create(['analista_atual_id' => null]);

        $this->expectException(ValidationException::class);

        $this->service()->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']);
    }

    public function test_emitir_bloqueia_com_ciclo_aberto(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-1']);

        $this->expectException(ValidationException::class);

        $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-2']);
    }

    public function test_emitir_bloqueia_apos_3_ciclos(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        foreach ([1, 2, 3] as $ciclo) {
            $n = $this->service()->emitir($protocolo, $user, ['num_sei' => "SEI-{$ciclo}"]);
            $this->service()->registrarDevolutiva($n, $user, now()->toDateString());
        }

        $this->expectException(ValidationException::class);

        $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-4']);
    }

    public function test_devolutiva_fecha_ciclo(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        $n = $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-1']);
        $this->service()->registrarDevolutiva($n, $user, now()->toDateString());

        $this->assertNotNull($n->fresh()->dt_devolutiva);
    }

    public function test_devolutiva_bloqueia_ciclo_ja_fechado(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        $n = $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-1']);
        $this->service()->registrarDevolutiva($n, $user, now()->toDateString());

        $this->expectException(ValidationException::class);

        $this->service()->registrarDevolutiva($n->fresh(), $user, now()->toDateString());
    }

    public function test_listar_por_protocolo_retorna_ciclos(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-1']);

        $lista = $this->service()->listarPorProtocolo($protocolo);

        $this->assertCount(1, $lista);
        $this->assertSame(1, $lista[0]['ciclo']);
        $this->assertSame(
            now()->addDays(30)->toDateString(),
            $lista[0]['prazo_final']
        );
        $this->assertFalse($lista[0]['vencida']);
    }

    public function test_rota_web_emitir(): void
    {
        Permission::firstOrCreate(['name' => 'pae.protocolos.edit', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.edit');

        $protocolo = $this->protocoloDelegado();

        $this->actingAs($user)
            ->post(route('pae.protocolo.notificacoes.store', $protocolo), [
                'num_sei' => 'SEI-WEB-1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pae_notificacoes', ['num_sei' => 'SEI-WEB-1']);
    }
}
