<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Providers\RouteServiceProvider;

class AuthAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckUserActive::class);
    }

    /**
     * Item 1: Verificar se usuarios nao autenticados (guests) sao bloqueados em rotas protegidas
     */
    public function test_guest_cannot_access_protected_routes()
    {
        $protectedRoutes = [
            '/dashboard',
            '/admin',
            '/profile',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);

            $response->assertStatus(302);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Item 2: Confirmar se o redirecionamento apos o login respeita o destino esperado
     */
    public function test_authenticated_user_is_redirected_to_home_after_login()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Item 3: Validar se o logout invalida a sessao e remove o acesso as areas restritas
     */
    public function test_user_logout_invalidates_session()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_cannot_access_protected_routes_after_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
