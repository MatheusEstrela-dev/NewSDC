<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpatiePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_view_pae_empreendimentos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'cpf' => fake()->numerify('###########'),
        ]);
        $user->assignRole('viewer');

        Sanctum::actingAs($user, [], 'sanctum');

        $this->getJson('/api/v1/pae/empreendimentos')
            ->assertOk();
    }

    public function test_user_cannot_delete_pae_empreendimentos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'cpf' => fake()->numerify('###########'),
        ]);
        $user->assignRole('user');

        Sanctum::actingAs($user, [], 'sanctum');

        $this->deleteJson('/api/v1/pae/empreendimentos/1')
            ->assertForbidden();
    }
}


