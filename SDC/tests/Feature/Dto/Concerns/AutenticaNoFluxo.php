<?php

declare(strict_types=1);

namespace Tests\Feature\Dto\Concerns;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\SessionVersionService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Autenticacao dos testes de fluxo da camada DTO (HTTP -> Request -> DTO ->
 * Service -> banco).
 *
 * O CheckUserActive compara `users.session_version` com a versao gravada na
 * sessao no login. Dar permissao ou papel ao usuario sobe essa versao. O
 * `Tests\TestCase::actingAs()` ja carimba a versao; `entrarComo()` repete o
 * carimbo de forma explicita e tira o CSRF, para o teste medir o DTO e nao o
 * middleware.
 *
 * Os testes rodam no Postgres de dev com DatabaseTransactions: valores unicos
 * devem levar sufixo aleatorio para nao colidir com o acervo migrado.
 */
trait AutenticaNoFluxo
{
    /**
     * Usuario com exatamente estas permissoes (criadas se nao existirem).
     *
     * @param  list<string>  $permissoes
     */
    protected function usuarioComPermissoes(array $permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($permissoes);

        return $user->fresh();
    }

    /** Usuario com o papel super-admin (ignora policies de escopo). */
    protected function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        return $user->fresh();
    }

    /**
     * Autentica sem CSRF e com a versao de sessao em dia.
     */
    protected function entrarComo(User $user): static
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($user);
        $this->withSession([SessionVersionService::SESSION_KEY => (int) $user->fresh()->session_version]);

        return $this;
    }

    /** Sufixo para colunas unicas (nome, codigo, patrimonio...). */
    protected function sufixo(int $tamanho = 6): string
    {
        return strtoupper(substr(bin2hex(random_bytes(8)), 0, $tamanho));
    }

    /** legacy_id improvavel no acervo migrado (que usa ids baixos). */
    protected function legacyIdDeTeste(): int
    {
        return random_int(900_000_000, 999_999_999);
    }
}
