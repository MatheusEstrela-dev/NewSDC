# Ajuda Humanitaria (MAH) - Fase 2c parte 1: Autorizacao e anexos - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Preparar o terreno da camada HTTP: permissoes, policy de escopo por municipio e anexos do pedido. Nao inclui controllers nem rotas, que sao a parte 2.

**Architecture:** Controllers finos que injetam services por construtor e devolvem `Inertia::render` ou `RedirectResponse`, no padrao de `AtaController` do Tdap. A autorizacao e defendida em duas camadas: `can:` na rota, para a permissao funcional, e `PedidoAhPolicy`, para o escopo por municipio e regiao. Nenhum controller contem regra de negocio.

**Tech Stack:** PHP 8.3, Laravel 12, Inertia, spatie/laravel-permission, spatie/laravel-medialibrary, PHPUnit 11.

## Contexto: o que as fases anteriores deixaram pronto

- **Fase 1**: enums, workflow com quatro guardas, tres specifications, dez models, schema aplicado
- **Fase 2a**: quatro repositories, `config/ajuda-humanitaria.php`
- **Fase 2b**: cinco DTOs e seis services, com o fluxo completo exercitavel sem HTTP

Esta fase **nao** cria telas Vue. Isso e a fase 3, que tambem remove o mock.

## Decisoes de escopo

| Tema | Decisao |
| --- | --- |
| Local da Policy | `app/Policies/PedidoAhPolicy.php`, acompanhando as 16 policies existentes, registrada no array `$policies` do `AuthServiceProvider`. Decisao do usuario, contra a alternativa de manter no modulo |
| Padrao de Policy | Classe pura com `before()` proprio, como `CisternaPolicy`. Nao estende `BasePolicy`, que exige `HierarchyServiceInterface` no construtor e resolve outro problema |
| Mailable | Nao ha. O unico e-mail previsto na spec era a aprovacao de agendamento, cortada na fase 2b por falta de lastro no legado |
| Municipio do usuario | Vem de `orgaoPrincipal.municipio_id`, com a mesma cadeia de fallback ja usada por `PlanConController::store` |

## Global Constraints

- Todo arquivo PHP novo comeca com `<?php`, linha em branco, `declare(strict_types=1);`
- Proibido emoji em codigo; sem acento em identificadores, apenas em string de exibicao
- **Arquivos de teste nao entram em commit.** Regra permanente do usuario
- Controllers **nao** contem regra de negocio: delegam a services e traduzem o resultado em resposta HTTP
- Toda rota declara `->name(...)` antes de `->middleware('can:...')`, e rotas literais vem antes de `/{id}`
- Prefixo de rota: `ajuda-humanitaria.`; prefixo de permissao: `humanitaria.` — a divergencia e preexistente no projeto e deve ser mantida
- Requests usam `authorize(): bool` com `$this->user()?->can(...) ?? false`
- Testes com banco usam `DatabaseTransactions`, nunca `RefreshDatabase`
- Nunca rodar `migrate:fresh`, `migrate:refresh` nem `db:wipe`
- Commits em gitmoji, escopo `ajuda-humanitaria`, sem trailer `Co-Authored-By`

### Runner de teste

Mesmo bloco das fases anteriores; nos passos, `TESTAR` o designa.

```powershell
$php = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
$ext = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\ext"
Set-Location "C:\Users\x24679188\Documents\Github\NewSDC\SDC"
$dot = @{}
Get-Content .env | Where-Object { $_ -match '^\s*DB_(USERNAME|PASSWORD|DATABASE)\s*=' } | ForEach-Object {
    $par = $_ -split '=', 2
    $dot[$par[0].Trim()] = $par[1].Trim().Trim('"')
}
$env:APP_CONFIG_CACHE = "$env:TEMP\sem-cache-newsdc.php"
$env:DB_CONNECTION = "pgsql"; $env:DB_HOST = "127.0.0.1"; $env:DB_PORT = "5434"
$env:DB_DATABASE = $dot['DB_DATABASE']
$env:DB_USERNAME = $dot['DB_USERNAME']
$env:DB_PASSWORD = $dot['DB_PASSWORD']
& $php -d "extension_dir=$ext" -d "extension=php_pgsql.dll" -d "extension=php_pdo_pgsql.dll" `
    vendor/bin/phpunit @args
```

**Linha de base antes desta fase: 289 testes, 829 assercoes, 1 erro e 4 falhas**, todas pre-existentes em `PaeFormularioControllerTest`, `ProcessoStoreFlashTest` e `PlanConUploadTest` (3). Qualquer falha alem dessas cinco e regressao.

## Ordem de execucao

- **Onda 1**, paralelizavel: Task 1 (permissoes), Task 3 (anexos), Task 5 (resources)
- **Onda 2**, apos a onda 1: Task 2 (policy), Task 4 (requests)
- **Onda 3**, apos a onda 2: Tasks 6, 7, 8 (controllers)
- **Onda 4**, apos a onda 3: Task 9 (rotas)

A Task 9 fica sozinha por escrever o arquivo de rotas inteiro, que referencia os tres grupos de controller.

---

### Task 1: Permissoes

**Files:**
- Modify: `config/permissions.php`
- Test: `tests/Feature/AjudaHumanitaria/PermissoesMahTest.php`

**Interfaces:**
- Consumes: nada
- Produces: 16 slugs `humanitaria.*` no bloco `AJUDA_HUMANITARIA`, distribuidos entre os perfis

O bloco atual, nas linhas 155 a 164, tem um unico grupo `Beneficiarios`, do mock. Ele **permanece** nesta fase: a remocao e da fase 3, quando as telas do mock sairem. Os grupos novos sao acrescentados ao lado.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/PermissoesMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PermissoesMahTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function slugProvider(): array
    {
        return array_map(static fn (string $slug): array => [$slug], [
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.create',
            'humanitaria.pedidos.edit',
            'humanitaria.pedidos.delete',
            'humanitaria.pedidos.print',
            'humanitaria.pedidos.export',
            'humanitaria.pedidos.tramitar',
            'humanitaria.pedidos.parecer',
            'humanitaria.pedidos.liberar_itens',
            'humanitaria.pedidos.anexos',
            'humanitaria.prestacao.view',
            'humanitaria.prestacao.lancar',
            'humanitaria.prestacao.homologar',
            'humanitaria.materiais.manage',
            'humanitaria.parametros.manage',
            'humanitaria.saldo.view',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function todosOsSlugs(): array
    {
        $slugs = [];

        foreach (config('permissions.modules', []) as $grupos) {
            foreach ($grupos as $acoes) {
                foreach ($acoes as $slug) {
                    $slugs[] = $slug;
                }
            }
        }

        return $slugs;
    }

    #[DataProvider('slugProvider')]
    public function test_slug_esta_declarado_no_config(string $slug): void
    {
        $this->assertContains($slug, $this->todosOsSlugs());
    }

    public function test_bloco_do_mock_continua_intacto(): void
    {
        $this->assertNotNull(
            config('permissions.modules.AJUDA_HUMANITARIA.Beneficiarios.view'),
            'A remocao do mock e da fase 3, nao desta.'
        );
    }

    public function test_admin_cobre_tudo_por_curinga(): void
    {
        $this->assertContains('humanitaria.*', config('permissions.role_permissions.admin', []));
    }

    public function test_compdec_opera_o_proprio_pedido(): void
    {
        $operator = config('permissions.role_permissions.operator', []);

        foreach ([
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.create',
            'humanitaria.pedidos.edit',
            'humanitaria.pedidos.anexos',
            'humanitaria.pedidos.tramitar',
            'humanitaria.prestacao.view',
            'humanitaria.prestacao.lancar',
        ] as $slug) {
            $this->assertContains($slug, $operator, "operator precisa de {$slug}");
        }
    }

    public function test_compdec_nao_analisa_nem_homologa(): void
    {
        $operator = config('permissions.role_permissions.operator', []);

        foreach ([
            'humanitaria.pedidos.parecer',
            'humanitaria.pedidos.liberar_itens',
            'humanitaria.prestacao.homologar',
            'humanitaria.materiais.manage',
            'humanitaria.parametros.manage',
        ] as $slug) {
            $this->assertNotContains($slug, $operator, "operator nao pode ter {$slug}");
        }
    }

    public function test_analista_emite_parecer_e_libera_itens(): void
    {
        $analyst = config('permissions.role_permissions.analyst', []);

        $this->assertContains('humanitaria.pedidos.parecer', $analyst);
        $this->assertContains('humanitaria.pedidos.liberar_itens', $analyst);
        $this->assertContains('humanitaria.saldo.view', $analyst);
        $this->assertNotContains('humanitaria.prestacao.homologar', $analyst);
    }

    public function test_diretor_homologa(): void
    {
        $manager = config('permissions.role_permissions.manager', []);

        $this->assertContains('humanitaria.prestacao.homologar', $manager);
        $this->assertContains('humanitaria.materiais.manage', $manager);
        $this->assertContains('humanitaria.parametros.manage', $manager);
    }

    public function test_viewer_apenas_consulta(): void
    {
        $viewer = config('permissions.role_permissions.viewer', []);

        $this->assertContains('humanitaria.pedidos.view', $viewer);
        $this->assertContains('humanitaria.prestacao.view', $viewer);
        $this->assertNotContains('humanitaria.pedidos.create', $viewer);
        $this->assertNotContains('humanitaria.pedidos.tramitar', $viewer);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=PermissoesMahTest
```
Esperado: FAIL nos slugs novos.

- [ ] **Step 3: Acrescentar os grupos ao bloco `AJUDA_HUMANITARIA`**

Em `config/permissions.php`, dentro de `'AJUDA_HUMANITARIA' => [`, **depois** do grupo `Beneficiarios` existente, acrescentar:

```php
            'Pedidos' => [
                'view' => 'humanitaria.pedidos.view',
                'create' => 'humanitaria.pedidos.create',
                'edit' => 'humanitaria.pedidos.edit',
                'delete' => 'humanitaria.pedidos.delete',
                'print' => 'humanitaria.pedidos.print',
                'export' => 'humanitaria.pedidos.export',
                'tramitar' => 'humanitaria.pedidos.tramitar',
                'parecer' => 'humanitaria.pedidos.parecer',
                'liberar_itens' => 'humanitaria.pedidos.liberar_itens',
                'attachments' => 'humanitaria.pedidos.anexos',
            ],
            'PrestacaoContas' => [
                'view' => 'humanitaria.prestacao.view',
                'lancar' => 'humanitaria.prestacao.lancar',
                'homologar' => 'humanitaria.prestacao.homologar',
            ],
            'Configuracao' => [
                'materiais' => 'humanitaria.materiais.manage',
                'parametros' => 'humanitaria.parametros.manage',
                'saldo' => 'humanitaria.saldo.view',
            ],
```

- [ ] **Step 4: Distribuir pelos perfis**

`admin` ja cobre tudo pelo curinga `humanitaria.*`; nao mexer.

Em `manager`, **depois** do bloco de cinco linhas `humanitaria.beneficiarios.*`:

```php
            // Ajuda Humanitaria MAH - analise, decisao e configuracao
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.create',
            'humanitaria.pedidos.edit',
            'humanitaria.pedidos.print',
            'humanitaria.pedidos.export',
            'humanitaria.pedidos.tramitar',
            'humanitaria.pedidos.parecer',
            'humanitaria.pedidos.liberar_itens',
            'humanitaria.pedidos.anexos',
            'humanitaria.prestacao.view',
            'humanitaria.prestacao.lancar',
            'humanitaria.prestacao.homologar',
            'humanitaria.materiais.manage',
            'humanitaria.parametros.manage',
            'humanitaria.saldo.view',
```

Em `analyst`, depois do bloco `humanitaria.beneficiarios.*`:

```php
            // Ajuda Humanitaria MAH - analise tecnica, sem homologar
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.create',
            'humanitaria.pedidos.edit',
            'humanitaria.pedidos.print',
            'humanitaria.pedidos.export',
            'humanitaria.pedidos.tramitar',
            'humanitaria.pedidos.parecer',
            'humanitaria.pedidos.liberar_itens',
            'humanitaria.pedidos.anexos',
            'humanitaria.prestacao.view',
            'humanitaria.saldo.view',
```

Em `operator`, o perfil do COMPDEC, depois do bloco `humanitaria.beneficiarios.*`:

```php
            // Ajuda Humanitaria MAH - o municipio abre, instrui e presta contas
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.create',
            'humanitaria.pedidos.edit',
            'humanitaria.pedidos.print',
            'humanitaria.pedidos.tramitar',
            'humanitaria.pedidos.anexos',
            'humanitaria.prestacao.view',
            'humanitaria.prestacao.lancar',
```

Em `viewer`, depois do bloco `humanitaria.beneficiarios.*`:

```php
            'humanitaria.pedidos.view',
            'humanitaria.pedidos.print',
            'humanitaria.prestacao.view',
```

O perfil `user` nao recebe nada de `humanitaria`, como ja acontece hoje.

Nota sobre `tramitar` no `operator`: o COMPDEC precisa dela para enviar o proprio pedido a analise, que e a transicao 0 para 1. Quem impede o municipio de despachar o processo alem disso e a `PedidoAhPolicy` da Task 2, nao a permissao.

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=PermissoesMahTest
```
Esperado: PASS, 22 testes.

- [ ] **Step 6: Sincronizar as permissoes no banco**

```powershell
# mesmas variaveis do bloco TESTAR, trocando phpunit por artisan
& $php artisan db:seed --class=RolesAndPermissionsSeeder --force
```
O seeder usa `updateOrCreate` e `syncPermissions`, entao e idempotente. Confirme na saida que as permissoes novas foram criadas.

- [ ] **Step 7: Commit**

```
🔒 security(ajuda-humanitaria): permissoes do processo MAH

Dezesseis slugs em tres grupos: pedidos, prestacao de contas e
configuracao. Distribuidos de modo que o COMPDEC abra e instrua o proprio
pedido, o analista emita parecer e libere quantidades, e so o diretor
homologue.

O bloco do mock permanece: a remocao e da fase 3, junto com as telas.

A permissao de tramitar vai ate o COMPDEC porque ele precisa enviar o
proprio pedido a analise; o limite do que ele pode despachar e da policy,
nao da permissao.
```

---

### Task 2: Escopo por municipio e PedidoAhPolicy

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Support/MunicipioDoUsuario.php`
- Create: `app/Policies/PedidoAhPolicy.php`
- Modify: `app/Providers/AuthServiceProvider.php`
- Test: `tests/Feature/AjudaHumanitaria/PedidoAhPolicyTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `PedidoAh`, permissoes da Task 1
- Produces:
  - `MunicipioDoUsuario::resolver(User $user): ?int`
  - `PedidoAhPolicy` com `before`, `viewAny`, `view`, `create`, `update`, `delete`, `tramitar`, `parecer`, `liberarItens`, `verPrestacao`, `lancarEntrega`, `homologar`

`MunicipioDoUsuario` existe para nao duplicar a cadeia de fallback que `PlanConController::store` usa: `orgaoPrincipal`, depois o orgao marcado como principal no pivot, depois o unico orgao vinculado.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/PedidoAhPolicyTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PedidoAhPolicyTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioA;
    private int $municipioB;

    protected function setUp(): void
    {
        parent::setUp();

        $ids = DB::table('municipios')->orderBy('id')->limit(2)->pluck('id')->all();

        if (count($ids) < 2) {
            $this->markTestSkipped('Banco de desenvolvimento com menos de dois municipios.');
        }

        [$this->municipioA, $this->municipioB] = array_map('intval', $ids);
    }

    private function pedidoDo(int $municipioId): PedidoAh
    {
        return PedidoAh::create([
            'numero' => random_int(900_000, 999_999), 'ano' => 2079,
            'municipio_id' => $municipioId, 'pop_atendida' => 10,
            'esforcos_realizados' => 'x', 'status' => StatusPedidoAh::EdicaoCompdec,
            'data_entrada_sistema' => now(),
        ]);
    }

    private function usuarioComPermissoes(array $slugs, ?int $municipioId = null): User
    {
        $user = User::factory()->create();

        foreach ($slugs as $slug) {
            $user->givePermissionTo($slug);
        }

        if ($municipioId !== null) {
            $orgaoId = DB::table('compdec_orgaos')->where('municipio_id', $municipioId)->value('id');

            if ($orgaoId === null) {
                $orgaoId = DB::table('compdec_orgaos')->insertGetId([
                    'municipio_id' => $municipioId,
                    'nome' => 'Orgao de teste',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $user->forceFill(['orgao_principal_id' => $orgaoId])->save();
        }

        return $user->fresh();
    }

    public function test_resolver_devolve_municipio_do_orgao_principal(): void
    {
        $user = $this->usuarioComPermissoes([], $this->municipioA);

        $this->assertSame($this->municipioA, MunicipioDoUsuario::resolver($user));
    }

    public function test_resolver_devolve_nulo_sem_orgao(): void
    {
        $user = $this->usuarioComPermissoes([]);

        $this->assertNull(MunicipioDoUsuario::resolver($user));
    }

    public function test_sem_permissao_nao_ve_nada(): void
    {
        $user = $this->usuarioComPermissoes([], $this->municipioA);

        $this->assertFalse($user->can('viewAny', PedidoAh::class));
        $this->assertFalse($user->can('view', $this->pedidoDo($this->municipioA)));
    }

    public function test_usuario_do_municipio_ve_o_proprio_pedido(): void
    {
        $user = $this->usuarioComPermissoes(['humanitaria.pedidos.view'], $this->municipioA);

        $this->assertTrue($user->can('view', $this->pedidoDo($this->municipioA)));
    }

    public function test_usuario_do_municipio_nao_ve_pedido_de_outro(): void
    {
        $user = $this->usuarioComPermissoes(['humanitaria.pedidos.view'], $this->municipioA);

        $this->assertFalse(
            $user->can('view', $this->pedidoDo($this->municipioB)),
            'RN-24: COMPDEC so enxerga o proprio municipio.'
        );
    }

    public function test_usuario_sem_municipio_ve_todos(): void
    {
        $user = $this->usuarioComPermissoes(['humanitaria.pedidos.view']);

        $this->assertTrue(
            $user->can('view', $this->pedidoDo($this->municipioA)),
            'Usuario do CEDEC nao tem municipio vinculado e enxerga o estado inteiro.'
        );
    }

    public function test_edicao_exige_permissao_escopo_e_status(): void
    {
        $user = $this->usuarioComPermissoes(['humanitaria.pedidos.edit'], $this->municipioA);

        $emEdicao = $this->pedidoDo($this->municipioA);
        $this->assertTrue($user->can('update', $emEdicao));

        $emAnalise = $this->pedidoDo($this->municipioA);
        $emAnalise->update(['status' => StatusPedidoAh::AnaliseDlog]);
        $this->assertFalse($user->can('update', $emAnalise));

        $this->assertFalse($user->can('update', $this->pedidoDo($this->municipioB)));
    }

    public function test_parecer_e_liberacao_exigem_permissao_propria(): void
    {
        $doMunicipio = $this->usuarioComPermissoes(['humanitaria.pedidos.view'], $this->municipioA);
        $analista    = $this->usuarioComPermissoes([
            'humanitaria.pedidos.parecer',
            'humanitaria.pedidos.liberar_itens',
        ]);

        $pedido = $this->pedidoDo($this->municipioA);

        $this->assertFalse($doMunicipio->can('parecer', $pedido));
        $this->assertFalse($doMunicipio->can('liberarItens', $pedido));
        $this->assertTrue($analista->can('parecer', $pedido));
        $this->assertTrue($analista->can('liberarItens', $pedido));
    }

    public function test_prestacao_e_negada_ao_perfil_redec(): void
    {
        $redec = $this->usuarioComPermissoes(['humanitaria.prestacao.view']);
        $redec->assignRole('redec');

        $this->assertFalse(
            $redec->fresh()->can('verPrestacao', $this->pedidoDo($this->municipioA)),
            'RN-20: REDEC nao acessa prestacao de contas.'
        );
    }

    public function test_homologacao_exige_permissao_dedicada(): void
    {
        $lancador   = $this->usuarioComPermissoes(['humanitaria.prestacao.lancar']);
        $homologador = $this->usuarioComPermissoes(['humanitaria.prestacao.homologar']);

        $pedido = $this->pedidoDo($this->municipioA);

        $this->assertFalse($lancador->can('homologar', $pedido));
        $this->assertTrue($homologador->can('homologar', $pedido));
    }
}
```

Nota: o teste depende de `User::factory()` e da role `redec`. Confirme que ambos existem antes de rodar; se a role `redec` nao existir no banco, troque por qualquer role presente em `config('permissions.roles')` e ajuste a policy correspondentemente, relatando a mudanca.

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=PedidoAhPolicyTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario" not found`.

- [ ] **Step 3: Implementar o resolvedor de municipio**

`app/Modules/AjudaHumanitaria/Support/MunicipioDoUsuario.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Support;

use App\Models\User;

/**
 * Municipio ao qual o usuario esta vinculado, ou null quando ele opera em
 * ambito estadual.
 *
 * A cadeia de fallback reproduz a de PlanConController::store, unico lugar do
 * projeto que ja resolvia isso: orgao principal, depois o orgao marcado como
 * principal no pivot, depois o unico orgao vinculado.
 *
 * Null nao e erro: usuarios do CEDEC nao tem orgao municipal e enxergam o
 * estado inteiro. Quem trata a ausencia como impedimento e o fluxo de
 * abertura de pedido, nao a leitura.
 */
final class MunicipioDoUsuario
{
    public static function resolver(User $user): ?int
    {
        $user->loadMissing('orgaoPrincipal');

        $orgao = $user->orgaoPrincipal
            ?? $user->orgaos()->wherePivot('is_principal', true)->first()
            ?? ($user->orgaos()->count() === 1 ? $user->orgaos()->first() : null);

        $municipioId = $orgao?->municipio_id;

        return $municipioId !== null ? (int) $municipioId : null;
    }
}
```

- [ ] **Step 4: Implementar a policy**

`app/Policies/PedidoAhPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Escopo do processo MAH.
 *
 * A permissao responde o que o usuario pode fazer; esta policy responde sobre
 * qual registro. As duas se somam: a rota exige o slug, a policy exige o
 * escopo.
 *
 * RN-24: quem tem municipio vinculado so enxerga o proprio. Quem nao tem opera
 * em ambito estadual e enxerga tudo — e o caso do CEDEC.
 * RN-20: o perfil REDEC nao acessa prestacao de contas.
 */
class PedidoAhPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('humanitaria.pedidos.view');
    }

    public function view(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.view')
            && $this->noEscopo($user, $pedido);
    }

    public function create(User $user): bool
    {
        return $user->can('humanitaria.pedidos.create');
    }

    /**
     * Edicao exige, alem da permissao e do escopo, que o pedido ainda esteja
     * com o municipio. A mesma regra vale no PedidoAhService; aqui ela existe
     * para a interface nao oferecer o botao.
     */
    public function update(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.edit')
            && $this->noEscopo($user, $pedido)
            && $pedido->status === StatusPedidoAh::EdicaoCompdec;
    }

    public function delete(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.delete')
            && $this->noEscopo($user, $pedido)
            && $pedido->status === StatusPedidoAh::EdicaoCompdec;
    }

    public function tramitar(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.tramitar')
            && $this->noEscopo($user, $pedido);
    }

    public function parecer(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.parecer')
            && $this->noEscopo($user, $pedido);
    }

    public function liberarItens(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.liberar_itens')
            && $this->noEscopo($user, $pedido);
    }

    public function anexos(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.anexos')
            && $this->noEscopo($user, $pedido);
    }

    /** RN-20. */
    public function verPrestacao(User $user, PedidoAh $pedido): bool
    {
        if ($user->hasRole('redec')) {
            return false;
        }

        return $user->can('humanitaria.prestacao.view')
            && $this->noEscopo($user, $pedido);
    }

    public function lancarEntrega(User $user, PedidoAh $pedido): bool
    {
        return $this->verPrestacao($user, $pedido)
            && $user->can('humanitaria.prestacao.lancar');
    }

    public function homologar(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.prestacao.homologar')
            && $this->noEscopo($user, $pedido);
    }

    /**
     * RN-24. Usuario sem municipio vinculado opera em ambito estadual.
     */
    private function noEscopo(User $user, PedidoAh $pedido): bool
    {
        $municipioDoUsuario = MunicipioDoUsuario::resolver($user);

        if ($municipioDoUsuario === null) {
            return true;
        }

        return $municipioDoUsuario === (int) $pedido->municipio_id;
    }
}
```

Atencao ao `lancarEntrega`: ele exige `verPrestacao`, que ja embute a negativa ao REDEC. Nao repita a checagem de role.

- [ ] **Step 5: Registrar a policy**

Em `app/Providers/AuthServiceProvider.php`, acrescentar ao array `$policies`:

```php
        \App\Modules\AjudaHumanitaria\Models\PedidoAh::class => \App\Policies\PedidoAhPolicy::class,
```

- [ ] **Step 6: Rodar e confirmar que passa**

```
TESTAR --filter=PedidoAhPolicyTest
```
Esperado: PASS, 10 testes.

- [ ] **Step 7: Commit**

```
🔒 security(ajuda-humanitaria): escopo por municipio do processo MAH

PedidoAhPolicy separa o que o usuario pode fazer, que e da permissao, de
sobre qual registro, que e do escopo.

RN-24: quem tem municipio vinculado enxerga apenas o proprio; quem nao tem
opera em ambito estadual, caso do CEDEC. RN-20: REDEC nao acessa prestacao
de contas.

MunicipioDoUsuario centraliza a cadeia de fallback de orgao que so existia
inline no PlanConController.
```

---

### Task 3: Anexos do pedido

**Files:**
- Modify: `app/Modules/AjudaHumanitaria/Models/PedidoAh.php`
- Create: `app/Modules/AjudaHumanitaria/Services/AnexoPedidoService.php`
- Test: `tests/Feature/AjudaHumanitaria/AnexoPedidoServiceTest.php`

**Interfaces:**
- Consumes: `spatie/laravel-medialibrary`, `config('ajuda-humanitaria.disk')` e `upload_limits.anexo_pedido`
- Produces:
  - `PedidoAh implements HasMedia`, constante `MEDIA_ANEXOS`, `registerMediaCollections()`
  - `AnexoPedidoService::anexar(int $pedidoId, UploadedFile $arquivo): array` devolvendo `[?Media, ?string]`
  - `AnexoPedidoService::remover(int $mediaId): bool`
  - `AnexoPedidoService::listar(int $pedidoId): array`

RN-22: apenas PDF, no maximo 2 MB. Diferente das demais collections do projeto, esta **nao** e `singleFile`: o pedido aceita varios documentos, como no legado, que gravava em `pedido/{id}`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/AnexoPedidoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Services\AnexoPedidoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AnexoPedidoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AnexoPedidoService $servico;
    private PedidoAh $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('ajuda-humanitaria.disk'));

        $this->servico = app(AnexoPedidoService::class);

        $municipioId = DB::table('municipios')->value('id');
        if ($municipioId === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }

        $this->pedido = PedidoAh::create([
            'numero' => 940_101, 'ano' => 2078, 'municipio_id' => (int) $municipioId,
            'pop_atendida' => 10, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::EdicaoCompdec, 'data_entrada_sistema' => now(),
        ]);
    }

    public function test_anexa_pdf(): void
    {
        $arquivo = UploadedFile::fake()->create('decreto.pdf', 500, 'application/pdf');

        [$media, $erro] = $this->servico->anexar($this->pedido->id, $arquivo);

        $this->assertNull($erro);
        $this->assertNotNull($media);
        $this->assertSame('decreto.pdf', $media->name);
    }

    public function test_recusa_arquivo_que_nao_e_pdf(): void
    {
        $arquivo = UploadedFile::fake()->create('planilha.xlsx', 100, 'application/vnd.ms-excel');

        [$media, $erro] = $this->servico->anexar($this->pedido->id, $arquivo);

        $this->assertNull($media);
        $this->assertStringContainsString('PDF', (string) $erro);
    }

    public function test_recusa_arquivo_acima_do_limite(): void
    {
        $limiteKb = (int) (config('ajuda-humanitaria.upload_limits.anexo_pedido') / 1024);
        $arquivo  = UploadedFile::fake()->create('grande.pdf', $limiteKb + 64, 'application/pdf');

        [$media, $erro] = $this->servico->anexar($this->pedido->id, $arquivo);

        $this->assertNull($media);
        $this->assertNotNull($erro);
    }

    public function test_pedido_aceita_varios_anexos(): void
    {
        foreach (['a.pdf', 'b.pdf', 'c.pdf'] as $nome) {
            $this->servico->anexar(
                $this->pedido->id,
                UploadedFile::fake()->create($nome, 50, 'application/pdf'),
            );
        }

        $this->assertCount(
            3,
            $this->servico->listar($this->pedido->id),
            'Diferente das demais collections do projeto, esta nao e singleFile.'
        );
    }

    public function test_lista_traz_nome_tamanho_e_url(): void
    {
        $this->servico->anexar(
            $this->pedido->id,
            UploadedFile::fake()->create('oficio.pdf', 120, 'application/pdf'),
        );

        $lista = $this->servico->listar($this->pedido->id);

        $this->assertSame(['id', 'nome', 'tamanho', 'url', 'criado_em'], array_keys($lista[0]));
        $this->assertSame('oficio.pdf', $lista[0]['nome']);
    }

    public function test_remove_anexo(): void
    {
        [$media, ] = $this->servico->anexar(
            $this->pedido->id,
            UploadedFile::fake()->create('remover.pdf', 50, 'application/pdf'),
        );

        $this->assertTrue($this->servico->remover($media->id));
        $this->assertCount(0, $this->servico->listar($this->pedido->id));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=AnexoPedidoServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\AnexoPedidoService" not found`.

- [ ] **Step 3: Tornar `PedidoAh` portador de midia**

Em `app/Modules/AjudaHumanitaria/Models/PedidoAh.php`, acrescentar os imports:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
```

Trocar a declaracao da classe por:

```php
class PedidoAh extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_ANEXOS = 'anexos_pedido';
```

E acrescentar, ao final da classe:

```php
    /**
     * RN-22: anexos do pedido, somente PDF.
     *
     * Nao e singleFile, ao contrario das demais collections do projeto: o
     * legado grava varios documentos por pedido, em pedido/{id}.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ANEXOS)
            ->useDisk((string) config('ajuda-humanitaria.disk', 'local'))
            ->acceptsMimeTypes(['application/pdf']);
    }
```

- [ ] **Step 4: Implementar o servico**

`app/Modules/AjudaHumanitaria/Services/AnexoPedidoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Anexos do pedido (RN-22): PDF de ate 2 MB.
 *
 * A validacao acontece aqui, e nao apenas no FormRequest, porque a regra e do
 * dominio do modulo e precisa valer tambem quando o anexo vier de importacao
 * ou de comando.
 */
final class AnexoPedidoService
{
    /**
     * @return array{0: ?Media, 1: ?string}
     */
    public function anexar(int $pedidoId, UploadedFile $arquivo): array
    {
        if ($arquivo->getClientMimeType() !== 'application/pdf'
            && strtolower((string) $arquivo->getClientOriginalExtension()) !== 'pdf') {
            return [null, 'Apenas arquivos PDF são aceitos.'];
        }

        $limite = (int) config('ajuda-humanitaria.upload_limits.anexo_pedido', 2 * 1024 * 1024);

        if ($arquivo->getSize() > $limite) {
            $limiteMb = round($limite / 1024 / 1024, 1);

            return [null, "Arquivo acima do limite de {$limiteMb} MB."];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);

        $media = $pedido
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(PedidoAh::MEDIA_ANEXOS);

        return [$media, null];
    }

    public function remover(int $mediaId): bool
    {
        return (bool) Media::findOrFail($mediaId)->delete();
    }

    /**
     * @return array<int, array{id: int, nome: string, tamanho: int, url: string, criado_em: ?string}>
     */
    public function listar(int $pedidoId): array
    {
        return PedidoAh::findOrFail($pedidoId)
            ->getMedia(PedidoAh::MEDIA_ANEXOS)
            ->map(static fn (Media $media): array => [
                'id'        => (int) $media->id,
                'nome'      => (string) $media->name,
                'tamanho'   => (int) $media->size,
                'url'       => $media->getUrl(),
                'criado_em' => $media->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
```

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=AnexoPedidoServiceTest
```
Esperado: PASS, 6 testes.

Se `getUrl()` falhar por o disco `local` nao ter URL publica, ajuste `config('ajuda-humanitaria.disk')` para um disco com `url` definido em `config/filesystems.php`, ou troque `getUrl()` por uma rota assinada de download — e relate a mudanca.

- [ ] **Step 6: Commit**

```
✨ feat(ajuda-humanitaria): anexos do pedido MAH

PedidoAh passa a portar midia via Media Library, com collection restrita a
PDF e limite de 2 MB, reproduzindo a RN-22.

A collection nao e singleFile, ao contrario das demais do projeto: o
legado grava varios documentos por pedido.

A validacao fica no servico, e nao so no FormRequest, porque a regra
precisa valer tambem fora do ciclo de requisicao.
```

---

## Verificacao desta parte

1. `TESTAR tests/Feature/AjudaHumanitaria` passa
2. `artisan db:seed --class=RolesAndPermissionsSeeder` cria os dezesseis slugs novos
3. A policy nega acesso a pedido de outro municipio e nega prestacao ao REDEC
4. O pedido aceita varios PDFs e recusa outros tipos e tamanhos
5. Suite completa mantem exatamente as cinco falhas pre-existentes
6. Nenhum arquivo do mock foi removido

## Regras cobertas nesta parte

RN-20, RN-22, RN-23, RN-24.

## O que fica para a parte 2

A parte 2 (`2026-08-06-ajuda-humanitaria-fase2c2-controllers.md`, a escrever) cobre
requests, resources, controllers e rotas, fechando RN-04, RN-05 e RN-25:

| Conteudo | Regras |
| --- | --- |
| Form Requests de pedido, item, parecer, transicao, entrega e anexo | RN-04, RN-05 |
| Resources de index e detalhe, mais os de item, parecer, tramite e prestacao | - |
| `PedidoAhController` | RN-04, RN-05 |
| `ItemPedidoController`, `ParecerController`, `TramitacaoController`, `PrestacaoContaController`, `AnexoPedidoController` | RN-08, RN-10, RN-12, RN-17, RN-19, RN-22 |
| `MaterialAhController`, `ParametroAhController`, `SaldoMaterialController` | RN-07, RN-16, RN-25 |
| `routes/modules/ajuda-humanitaria.php` reescrito | RN-23 |

Ela e escrita depois desta parte estar aplicada, por uma razao concreta: os
controllers invocam abilities da `PedidoAhPolicy`, e escrever chamadas a
`$this->authorize('...')` antes de a policy existir e assinar contrato com uma
classe ainda nao construida. Com a parte 1 aplicada, a parte 2 e escrita contra
codigo real.

Depois das duas partes, as 25 regras do catalogo estao implementadas. Resta a
fase 3: telas Vue em Atomic Design e remocao do mock.
