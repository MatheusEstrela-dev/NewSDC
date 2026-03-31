<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormApontamento;
use App\Modules\Pae\Models\PaeFormConclusaoItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeFormularioControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'pae.empreendimentos.view',
        'pae.empreendimentos.create',
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

    public function test_store_persiste_municipio_id(): void
    {
        $municipio = Municipio::first();
        $this->assertNotNull($municipio, 'Precisa de pelo menos um municipio no banco.');

        $this->actingAsAnalista()
            ->post('/pae/formulario', [
                'barragem'     => 'Barragem Teste',
                'municipio_id' => $municipio->id,
            ]);

        $this->assertDatabaseHas('pae_forms', [
            'barragem_nome' => 'Barragem Teste',
            'municipio_id'  => $municipio->id,
        ]);
    }

    public function test_update_apontamentos_usa_tabela_dedicada(): void
    {
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put("/pae/formulario/{$form->id}/aptecnico", [
                'apontamentos' => [
                    ['text' => 'Item principal', 'children' => [
                        ['text' => 'Sub-item 1.1'],
                    ]],
                ],
            ]);

        $this->assertDatabaseHas('pae_form_apontamentos', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Item principal',
            'parent_id'   => null,
        ]);

        $this->assertDatabaseHas('pae_form_apontamentos', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Sub-item 1.1',
        ]);

        $this->assertDatabaseMissing('pae_form_conclusao', ['pae_form_id' => $form->id]);
    }

    public function test_update_conclusao_usa_tabela_dedicada(): void
    {
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put("/pae/formulario/{$form->id}/conclusao", [
                'conclusao' => [
                    ['text' => 'Conclusao 1', 'children' => []],
                ],
            ]);

        $this->assertDatabaseHas('pae_form_conclusao', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Conclusao 1',
        ]);

        $this->assertDatabaseMissing('pae_form_apontamentos', ['pae_form_id' => $form->id]);
    }
}
