<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeFormularioGatingTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'pae.empreendimentos.view',
        'pae.empreendimentos.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function actingAsAnalista(): static
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSIONS);

        return $this->actingAs($user);
    }

    private function formComProtocolo(?int $analistaId): PaeForm
    {
        $protocolo = PaeProtocolo::factory()->create([
            'analista_atual_id' => $analistaId,
        ]);

        return PaeForm::create([
            'pae_protocolo_id' => $protocolo->id,
            'status'           => 'RASCUNHO',
        ]);
    }

    public function test_bloqueia_objetivo_sem_analista_delegado(): void
    {
        $form = $this->formComProtocolo(null);

        $this->actingAsAnalista()
            ->put(route('pae.formulario.objetivo', $form), ['objetivo' => 'x'])
            ->assertSessionHasErrors('protocolo');
    }

    public function test_permite_objetivo_com_analista_delegado(): void
    {
        $analista = User::factory()->create();
        $form = $this->formComProtocolo($analista->id);

        $this->actingAsAnalista()
            ->put(route('pae.formulario.objetivo', $form), ['objetivo' => 'x'])
            ->assertSessionDoesntHaveErrors('protocolo');
    }

    public function test_formulario_avulso_sem_protocolo_nao_e_bloqueado(): void
    {
        $form = PaeForm::create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put(route('pae.formulario.objetivo', $form), ['objetivo' => 'x'])
            ->assertSessionDoesntHaveErrors('protocolo');
    }

    public function test_edit_renderiza_pagina_pae_com_props(): void
    {
        $analista = User::factory()->create(['name' => 'Analista Teste']);
        $form = $this->formComProtocolo($analista->id);

        $this->actingAsAnalista()
            ->get(route('pae.protocolo.edit', $form->pae_protocolo_id))
            ->assertInertia(fn ($page) => $page
                ->component('Pae')
                ->where('protocolo.analista_atual_id', $analista->id)
                ->where('protocolo.analista_nome', 'Analista Teste')
                ->where('readOnly', false)
            );
    }

    public function test_show_readonly_via_query(): void
    {
        $form = $this->formComProtocolo(null);

        $this->actingAsAnalista()
            ->get(route('pae.index', ['protocolo_id' => $form->pae_protocolo_id, 'readonly' => 1]))
            ->assertInertia(fn ($page) => $page
                ->component('Pae')
                ->where('readOnly', true)
            );
    }
}
