<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Models\User;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Testes de Feature para o RatController (index, create/store, show, destroy).
 *
 * Usa DatabaseTransactions — cada teste é revertido ao final,
 * sem impactar o banco real.
 */
class RatControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // APP_ENV=local no .env do container derruba runningUnitTests(),
        // por isso desabilitamos CSRF explicitamente nos testes.
        $this->withoutMiddleware(VerifyCsrfToken::class);
        // Limpa cache do Spatie para que permissões criadas/revertidas não causem
        // foreign-key violation quando o auto-increment não é revertido no MySQL.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // -----------------------------------------------------------------------
    // Autenticação: usuário reutilizado em todos os testes
    // -----------------------------------------------------------------------

    /** Lista de permissões usadas neste test case. */
    private const PERMISSIONS = [
        'rat.protocolos.view',
        'rat.protocolos.create',
        'rat.protocolos.edit',
        'rat.protocolos.delete',
        'rat.protocolos.export',
        'rat.protocolos.finalize',
    ];

    private function actingAsAdmin(): static
    {
        // Garante que as permissões existem (cria se ausentes)
        foreach (self::PERMISSIONS as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        app()['cache']->forget('spatie.permission.cache');

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSIONS);

        return $this->actingAs($user);
    }

    private function makeRat(array $attrs = []): Rat
    {
        return Rat::create(array_merge([
            'protocolo'    => 'RAT-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'status'       => Status::RASCUNHO->value,
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ], $attrs));
    }

    // -----------------------------------------------------------------------
    // GET /rat (index)
    // -----------------------------------------------------------------------

    public function test_index_retorna_200_para_usuario_autenticado(): void
    {
        $this->actingAsAdmin()
            ->get(route('rat.index'))
            ->assertStatus(200);
    }

    public function test_index_redireciona_nao_autenticado(): void
    {
        // Inertia responde com 401 (JSON) ou 302 (redirect) dependendo do contexto
        $response = $this->get(route('rat.index'));
        $this->assertContains($response->status(), [302, 401]);
    }

    public function test_index_aceita_filtro_status_valido(): void
    {
        $this->actingAsAdmin()
            ->get(route('rat.index', ['status' => 'rascunho']))
            ->assertStatus(200);
    }

    // -----------------------------------------------------------------------
    // POST /rat (store — cria novo RAT em branco)
    // -----------------------------------------------------------------------

    public function test_store_cria_rat_e_redireciona(): void
    {
        $contagemAntes = Rat::count();

        $response = $this->actingAsAdmin()->post(route('rat.store'));

        $this->assertSame($contagemAntes + 1, Rat::count());
        $response->assertRedirect();
    }

    public function test_store_cria_rat_com_status_rascunho(): void
    {
        $this->actingAsAdmin()->post(route('rat.store'));

        $ultimo = Rat::latest()->first();
        $this->assertSame(Status::RASCUNHO->value, $ultimo->status);
    }

    public function test_store_cria_protocolo_com_formato_correto(): void
    {
        $this->actingAsAdmin()->post(route('rat.store'));

        $ultimo = Rat::latest()->first();
        $this->assertMatchesRegularExpression(
            '/^RAT-\d{4}-\d{5}$/',
            $ultimo->protocolo
        );
    }

    // -----------------------------------------------------------------------
    // GET /rat/{id}/edit (show)
    // -----------------------------------------------------------------------

    public function test_show_retorna_200_para_rat_existente(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->get(route('rat.edit', $rat->id))
            ->assertStatus(200);
    }

    public function test_show_retorna_404_para_id_inexistente(): void
    {
        $this->actingAsAdmin()
            ->get(route('rat.edit', 'uuid-nao-existe-12345'))
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // DELETE /rat/{id}
    // -----------------------------------------------------------------------

    public function test_destroy_remove_rat_e_redireciona(): void
    {
        $rat = $this->makeRat();
        $id  = $rat->id;

        $this->actingAsAdmin()
            ->delete(route('rat.destroy', $id))
            ->assertRedirect(route('rat.index'));

        $this->assertNull(Rat::find($id));
    }

    public function test_destroy_exibe_mensagem_de_sucesso(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->delete(route('rat.destroy', $rat->id))
            ->assertSessionHas('success');
    }
}
