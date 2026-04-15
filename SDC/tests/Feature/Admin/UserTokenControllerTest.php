<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['cpf' => '00000000001']);
        $this->admin->assignRole('super-admin');

        $this->target = User::factory()->create(['cpf' => '00000000002']);
    }

    public function test_admin_pode_gerar_token_para_usuario(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.permissions.users.tokens.store', $this->target), [
            'name'       => 'Swagger Dev',
            'expires_in' => '30d',
        ]);

        $response->assertRedirect(route('admin.permissions.users.show', $this->target));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $this->target->id,
            'tokenable_type' => User::class,
            'name'           => 'Swagger Dev',
        ]);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.permissions.users.tokens.store', $this->target), []);

        $response->assertSessionHasErrors(['name', 'expires_in']);
    }

    public function test_store_valida_expires_in_invalido(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.permissions.users.tokens.store', $this->target), [
            'name'       => 'Test',
            'expires_in' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['expires_in']);
    }

    public function test_usuario_sem_permissao_nao_pode_gerar_token(): void
    {
        $viewer = User::factory()->create(['cpf' => '00000000003']);
        $viewer->assignRole('viewer');
        $this->actingAs($viewer);

        $response = $this->post(route('admin.permissions.users.tokens.store', $this->target), [
            'name'       => 'Test',
            'expires_in' => '7d',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_pode_revogar_token(): void
    {
        $this->actingAs($this->admin);

        $token = $this->target->createToken('Para Revogar');

        $response = $this->delete(route('admin.permissions.users.tokens.destroy', [
            'user'    => $this->target->id,
            'tokenId' => $token->accessToken->id,
        ]));

        $response->assertRedirect(route('admin.permissions.users.show', $this->target));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_destroy_retorna_404_para_token_de_outro_usuario(): void
    {
        $this->actingAs($this->admin);
        $outro = User::factory()->create(['cpf' => '00000000004']);
        $token = $outro->createToken('Alheio');

        $response = $this->delete(route('admin.permissions.users.tokens.destroy', [
            'user'    => $this->target->id,
            'tokenId' => $token->accessToken->id,
        ]));

        $response->assertNotFound();
    }
}
