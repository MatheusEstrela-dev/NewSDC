# PAE Issue #54 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corrigir os bugs da issue #54 do PAE: numeração/versionamento do protocolo, abertura com dados carregados, abas condicionais por delegação, modo visualização read-only, histórico de anexos, botão relacionar e ciclo de notificações 1/2/3 com e-mail e suspensão automática.

**Architecture:** Correções cirúrgicas no frontend Vue/Inertia + novas peças de backend no módulo `app/Modules/Pae` (models finos, `PaeNotificacaoService`, Mailable, Command agendado). Numeração segue o padrão do RAT (transação + advisory lock). Status de análise é derivado do protocolo (accessor). REST API V1 espelha as ações de notificação.

**Tech Stack:** Laravel 11 (PostgreSQL/pgsql, Spatie Permission, Sanctum), Inertia + Vue 3 `<script setup>`, Tailwind, PHPUnit (`DatabaseTransactions`).

**Spec:** `SDC/docs/superpowers/specs/2026-07-17-pae-bugs-issue54-design.md`

## Global Constraints

- Escopo: somente `NewSDC/SDC`. Nada no legado gestaocedec.
- Sem emojis dentro de código (regra 2 do usuário). Gitmoji apenas em mensagens de commit (regra 11), formato `<emoji> tipo(pae): descricao pt-BR`.
- Alterações de schema consolidadas na migration principal `2026_02_12_130000_create_pae_protocolos_table.php` (regra 9). Em dev, recriar com `php artisan migrate:fresh --seed` ou aplicar coluna manualmente.
- Formato do número: `dd.mm.aaaa-XXXX-VVV` (XXXX = sequencial diário 4 dígitos; VVV = versão 3 dígitos, `001` na criação). Números antigos `dd.mm.aaaa.NNN` permanecem válidos.
- Notificações: 3 ciclos de 30 dias (`PaeNotificacaoService::PRAZO_DIAS = 30`, `MAX_CICLOS = 3`); 3ª vencida → `SUSPENSO`.
- E-mail: `email_coord` (to) + `email_coord_sub` (cc) de `pae_empntos`; sem e-mail → apenas timeline.
- Working dir dos comandos: `c:\Users\x24679188\Documents\Github\NewSDC\SDC`.
- Branches: criar `feat/pae-54-protocolo`, `feat/pae-54-abas-visualizacao`, `feat/pae-54-notificacoes` a partir de `pae-protocolo-anexos`; ao concluir cada grupo, merge de volta em `pae-protocolo-anexos` com `🔀 merge(pae): ...`.
- Rodar testes com: `php artisan test tests/Feature/Pae --compact` (ou `--filter=NomeDoTeste`).

---

## Branch 1: `feat/pae-54-protocolo` (Tasks 1–3)

Antes da Task 1: `git checkout pae-protocolo-anexos && git pull && git checkout -b feat/pae-54-protocolo`

### Task 1: Schema — coluna `protocolo_origem_id` + relações no model

**Files:**
- Modify: `database/migrations/2026_02_12_130000_create_pae_protocolos_table.php:36`
- Modify: `app/Modules/Pae/Models/PaeProtocolo.php`

**Interfaces:**
- Produces: coluna `pae_protocolos.protocolo_origem_id` (nullable FK self), `PaeProtocolo::origem(): BelongsTo`, `PaeProtocolo::versoes(): HasMany`, fillable `protocolo_origem_id`.

- [ ] **Step 1: Adicionar coluna na migration principal**

Em `database/migrations/2026_02_12_130000_create_pae_protocolos_table.php`, logo após a linha do `pae_empnto_id` (linha 36), adicionar:

```php
                // Protocolo pai (versionamento: 001 -> 002 -> ...)
                $table->foreignId('protocolo_origem_id')->nullable()->constrained('pae_protocolos')->onDelete('set null');
```

- [ ] **Step 2: Atualizar o model**

Em `app/Modules/Pae/Models/PaeProtocolo.php`:
1. Adicionar `'protocolo_origem_id',` ao `$fillable` (após `'pae_empnto_id',`).
2. Adicionar as relações após o método `usuario()`:

```php
    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'protocolo_origem_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(self::class, 'protocolo_origem_id');
    }
```

- [ ] **Step 3: Aplicar o schema no banco dev**

Run: `php artisan migrate:fresh --seed` (banco de dev). Se inviável, aplicar manualmente:
`php artisan tinker --execute="Schema::table('pae_protocolos', function ($t) { $t->foreignId('protocolo_origem_id')->nullable()->constrained('pae_protocolos')->onDelete('set null'); });"`
Expected: coluna existe (`php artisan tinker --execute="var_dump(Schema::hasColumn('pae_protocolos','protocolo_origem_id'));"` imprime `bool(true)`).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_02_12_130000_create_pae_protocolos_table.php app/Modules/Pae/Models/PaeProtocolo.php
git commit -m "🗃️ db(pae): coluna protocolo_origem_id e relacoes de versionamento no protocolo"
```

### Task 2: Numeração `dd.mm.aaaa-XXXX-001` (TDD)

**Files:**
- Test: `tests/Feature/Pae/PaeProtocoloNumeracaoTest.php` (create)
- Modify: `app/Modules/Pae/Services/PaeProtocoloService.php:63-82`
- Modify: `app/Modules/Pae/Services/PaeFormularioService.php:191-197` (remover duplicata, DRY)

**Interfaces:**
- Consumes: `PaeProtocolo` (Task 1).
- Produces: `PaeProtocoloService::gerarNumProtocolo(): string` no formato novo; `PaeFormularioService` passa a injetar `PaeProtocoloService` no construtor (`public function __construct(private readonly PaeProtocoloService $protocoloService)`).

- [ ] **Step 1: Escrever testes que falham**

Criar `tests/Feature/Pae/PaeProtocoloNumeracaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaeProtocoloNumeracaoTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): PaeProtocoloService
    {
        return app(PaeProtocoloService::class);
    }

    public function test_gera_numero_no_formato_diario_com_versao(): void
    {
        $num = $this->service()->gerarNumProtocolo();

        $hoje = now()->format('d.m.Y');
        $this->assertMatchesRegularExpression(
            '/^' . preg_quote($hoje, '/') . '-\d{4}-001$/',
            $num
        );
    }

    public function test_sequencial_diario_incrementa(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0007-001']);

        $this->assertSame($hoje . '-0008-001', $this->service()->gerarNumProtocolo());
    }

    public function test_ignora_formato_antigo_no_calculo(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '.015']);

        $this->assertSame($hoje . '-0001-001', $this->service()->gerarNumProtocolo());
    }

    public function test_versoes_relacionadas_nao_afetam_sequencial_diario(): void
    {
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0002-001']);
        PaeProtocolo::factory()->create(['num_protocolo' => $hoje . '-0002-003']);

        $this->assertSame($hoje . '-0003-001', $this->service()->gerarNumProtocolo());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeProtocoloNumeracaoTest.php --compact`
Expected: FAIL (formato atual é `d.m.Y.NNN`).

- [ ] **Step 3: Reescrever `gerarNumProtocolo()` no `PaeProtocoloService`**

Substituir o método `gerarNumProtocolo()` (linhas 63–82) por:

```php
    public function gerarNumProtocolo(): string
    {
        return DB::transaction(function () {
            $this->travarSequencialProtocolo();

            $hoje = now()->format('d.m.Y');

            return sprintf('%s-%04d-001', $hoje, $this->proximoSequencialDoDia($hoje));
        });
    }

    private function travarSequencialProtocolo(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT pg_advisory_xact_lock(hashtext('pae_protocolo_seq'))");
        }
    }

    private function proximoSequencialDoDia(string $hoje): int
    {
        $max = 0;

        PaeProtocolo::withTrashed()
            ->where('num_protocolo', 'like', $hoje . '-%')
            ->pluck('num_protocolo')
            ->each(function (string $num) use (&$max) {
                if (preg_match('/^\d{2}\.\d{2}\.\d{4}-(\d{4})-\d{3}$/', $num, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return $max + 1;
    }
```

- [ ] **Step 4: Remover a duplicata no `PaeFormularioService` (DRY)**

Em `app/Modules/Pae/Services/PaeFormularioService.php`:
1. Adicionar construtor logo após a declaração da classe:

```php
    public function __construct(
        private readonly PaeProtocoloService $protocoloService
    ) {}
```

2. Apagar o método privado `gerarNumProtocolo()` (linhas 191–197).
3. Em `finalizar()`, trocar `'num_protocolo'  => $this->gerarNumProtocolo(),` por `'num_protocolo'  => $this->protocoloService->gerarNumProtocolo(),`.

- [ ] **Step 5: Rodar testes**

Run: `php artisan test tests/Feature/Pae/PaeProtocoloNumeracaoTest.php tests/Feature/Pae/PaeFormularioControllerTest.php --compact`
Expected: PASS (numeração nova + regressão do formulário ok).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Pae/Services/PaeProtocoloService.php app/Modules/Pae/Services/PaeFormularioService.php tests/Feature/Pae/PaeProtocoloNumeracaoTest.php
git commit -m "✨ feat(pae): numeracao de protocolo dd.mm.aaaa-XXXX-001 com lock e sequencial diario"
```

### Task 3: `relacionar()` — versão 002+ com rota e botão

**Files:**
- Test: `tests/Feature/Pae/PaeProtocoloRelacionarTest.php` (create)
- Modify: `app/Modules/Pae/Services/PaeProtocoloService.php`
- Modify: `app/Modules/Pae/Controllers/PaeProtocoloController.php`
- Modify: `routes/modules/pae.php`
- Modify: `resources/js/Components/Atoms/Button/ActionButton.vue:114-228` (mapas)
- Modify: `resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`
- Modify: `resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`
- Modify: `resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`

**Interfaces:**
- Consumes: `gerarNumProtocolo()` privates da Task 2 (`travarSequencialProtocolo()`), coluna `protocolo_origem_id` (Task 1).
- Produces: `PaeProtocoloService::relacionar(PaeProtocolo $base, User $user): PaeProtocolo`; rota `POST /pae/protocolo/{paeProtocolo}/relacionar` name `pae.protocolo.relacionar`; ação `relate` no ActionButton.

- [ ] **Step 1: Escrever testes que falham**

Criar `tests/Feature/Pae/PaeProtocoloRelacionarTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeProtocoloRelacionarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function base(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'num_protocolo' => now()->format('d.m.Y') . '-0001-001',
        ]);
    }

    public function test_relacionar_cria_versao_seguinte(): void
    {
        $base = $this->base();
        $user = User::factory()->create();

        $novo = app(PaeProtocoloService::class)->relacionar($base, $user);

        $this->assertSame(
            now()->format('d.m.Y') . '-0001-002',
            $novo->num_protocolo
        );
        $this->assertSame($base->id, $novo->protocolo_origem_id);
        $this->assertSame($base->pae_empnto_id, $novo->pae_empnto_id);
        $this->assertSame('novo', $novo->status->value);
        $this->assertDatabaseHas('pae_timeline', [
            'protocolo_id' => $base->id,
            'evento'       => 'relacionamento',
        ]);
        $this->assertDatabaseHas('pae_timeline', [
            'protocolo_id' => $novo->id,
            'evento'       => 'criacao',
        ]);
    }

    public function test_relacionar_rejeita_formato_antigo(): void
    {
        $base = PaeProtocolo::factory()->create(['num_protocolo' => '01.01.2025.003']);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(PaeProtocoloService::class)->relacionar($base, $user);
    }

    public function test_rota_relacionar_exige_permissao_e_redireciona(): void
    {
        Permission::firstOrCreate(['name' => 'pae.protocolos.create', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.create');

        $base = $this->base();

        $this->actingAs($user)
            ->post(route('pae.protocolo.relacionar', $base))
            ->assertRedirect();

        $this->assertDatabaseHas('pae_protocolos', [
            'protocolo_origem_id' => $base->id,
        ]);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeProtocoloRelacionarTest.php --compact`
Expected: FAIL ("Call to undefined method ... relacionar" / rota inexistente).

- [ ] **Step 3: Implementar `relacionar()` no service**

Adicionar em `PaeProtocoloService` (após `atribuir()`):

```php
    public function relacionar(PaeProtocolo $base, User $user): PaeProtocolo
    {
        return DB::transaction(function () use ($base, $user) {
            $this->travarSequencialProtocolo();

            $prefixo = $this->prefixoVersionavel($base->num_protocolo);

            $novo = PaeProtocolo::create([
                'num_protocolo'    => sprintf('%s-%03d', $prefixo, $this->proximaVersao($prefixo)),
                'status'           => PaeProtocoloStatus::NOVO->value,
                'user_id'          => $user->id,
                'created_by'       => $user->id,
                'dt_entrada'       => now()->toDateString(),
                'pae_empnto_id'    => $base->pae_empnto_id,
                'empnto_search'    => $base->empnto_search,
                'protocolo_origem_id' => $base->id,
            ]);

            $this->registrarTimeline(
                $base,
                'relacionamento',
                "Versao {$novo->num_protocolo} criada a partir deste protocolo.",
                $user
            );
            $this->registrarTimeline(
                $novo,
                'criacao',
                "Protocolo criado como versao relacionada de {$base->num_protocolo}.",
                $user
            );

            return $novo;
        });
    }

    private function prefixoVersionavel(string $numProtocolo): string
    {
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4}-\d{4})-\d{3}$/', $numProtocolo, $m)) {
            return $m[1];
        }

        throw ValidationException::withMessages([
            'protocolo' => 'Somente protocolos no formato dd.mm.aaaa-XXXX-VVV podem ser relacionados.',
        ]);
    }

    private function proximaVersao(string $prefixo): int
    {
        $max = 0;

        PaeProtocolo::withTrashed()
            ->where('num_protocolo', 'like', $prefixo . '-%')
            ->pluck('num_protocolo')
            ->each(function (string $num) use (&$max) {
                if (preg_match('/-(\d{3})$/', $num, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return $max + 1;
    }
```

- [ ] **Step 4: Controller + rota**

Em `PaeProtocoloController`, adicionar após `assign()`:

```php
    public function relacionar(Request $request, PaeProtocolo $paeProtocolo): \Illuminate\Http\RedirectResponse
    {
        $novo = $this->service->relacionar($paeProtocolo, $request->user());

        return redirect()->route('pae.index', ['protocolo_id' => $novo->id])
            ->with('success', "Protocolo {$novo->num_protocolo} criado como versao relacionada.");
    }
```

Em `routes/modules/pae.php`, após a rota `protocolos.status`:

```php
    Route::post('/protocolo/{paeProtocolo}/relacionar', [PaeProtocoloController::class, 'relacionar'])
        ->name('protocolo.relacionar')
        ->middleware('can:pae.protocolos.create');
```

- [ ] **Step 5: Rodar testes**

Run: `php artisan test tests/Feature/Pae/PaeProtocoloRelacionarTest.php --compact`
Expected: PASS.

- [ ] **Step 6: Ação `relate` no ActionButton e telas**

Em `resources/js/Components/Atoms/Button/ActionButton.vue`, adicionar a chave `relate` em cada mapa (manter ordem alfabética não é exigido; inserir após `duplicate`):
- `ActionIcons`: `relate: markRaw(DocumentDuplicateIcon),`
- `ActionLabels`: `relate: 'Relacionar',`
- `ActionVariants`: `relate: 'secondary',`
- `ActionIconVariants`: `relate: 'secondary',`
- `ActionMenuIconClasses`: `relate: 'text-indigo-400',`
- `ACTION_ALIAS`: `relate: 'create',`

Em `PaeProtocolosTable.vue`: adicionar na lista `:actions` (após `assign`):

```js
                    { action: 'relate', placement: 'menu', handler: () => $emit('relate', protocolo.id), allowed: canCreate },
```

Adicionar prop `canCreate: { type: Boolean, default: false }` e `'relate'` ao `defineEmits`.

Em `PaeProtocoloCard.vue`: mesma entrada `relate` na lista `:actions` (com `allowed: canCreate`), mesma prop `canCreate` e emit `'relate'`.

Em `PaeProtocolosIndexTemplate.vue`:
1. Passar `:can-create="canCreate"` para `PaeProtocolosTable` e para o grid/cards (seguir o padrão de `:can-edit`), e ouvir `@relate="handleRelate"` em ambos.
2. Adicionar handler junto de `handleEdit` (linha ~499):

```js
function handleRelate(id) {
  if (!confirm('Criar nova versao relacionada deste protocolo?')) return;
  router.post(route('pae.protocolo.relacionar', id));
}
```

(Verificar se o template já recebe prop `canCreate` da página; `PaeProtocolosIndex.vue` recebe `canCreate` do controller `index()` — repassar até a tabela.)

- [ ] **Step 7: Build e verificação manual**

Run: `npm run build`
Expected: build sem erros.

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Pae/Services/PaeProtocoloService.php app/Modules/Pae/Controllers/PaeProtocoloController.php routes/modules/pae.php resources/js/Components/Atoms/Button/ActionButton.vue resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue tests/Feature/Pae/PaeProtocoloRelacionarTest.php
git commit -m "✨ feat(pae): acao relacionar cria versao 002+ do protocolo com referencia ao pai"
```

- [ ] **Step 9: Merge do grupo 1**

```bash
git checkout pae-protocolo-anexos
git merge --no-ff feat/pae-54-protocolo -m "🔀 merge(pae): numeracao nova e versionamento de protocolo (issue #54)"
```

---

## Branch 2: `feat/pae-54-abas-visualizacao` (Tasks 4–6)

Antes da Task 4: `git checkout pae-protocolo-anexos && git checkout -b feat/pae-54-abas-visualizacao`

### Task 4: Guard backend de abas + abertura com dados (controller/props)

**Files:**
- Test: `tests/Feature/Pae/PaeFormularioGatingTest.php` (create)
- Modify: `app/Modules/Pae/Services/PaeFormularioService.php`
- Modify: `app/Modules/Pae/Controllers/PaeFormularioController.php`

**Interfaces:**
- Consumes: `PaeForm->protocolo` relation (já existe: `PaeForm::protocolo()`), `PaeFormularioService` (Task 2 já injetou `PaeProtocoloService`).
- Produces: `PaeFormularioService::assertAbasLiberadas(PaeForm $form): void`; prop Inertia `protocolo` com chaves `id, num_protocolo, status, analista_atual_id, analista_nome, arquivado, analise_status`; prop `readOnly: bool`; `edit()` renderiza `'Pae'`.

- [ ] **Step 1: Escrever testes que falham**

Criar `tests/Feature/Pae/PaeFormularioGatingTest.php`:

```php
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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeFormularioGatingTest.php --compact`
Expected: FAIL (sem guard, `edit` renderiza `PaeEdit`, sem prop `readOnly`).

- [ ] **Step 3: Guard no service**

Em `PaeFormularioService`, adicionar método privado (perto de `ensureAnexoBelongsToForm`):

```php
    public function assertAbasLiberadas(PaeForm $form): void
    {
        if (! $form->pae_protocolo_id) {
            return; // fluxo avulso de criacao de formulario segue liberado
        }

        $form->loadMissing('protocolo');

        if (! $form->protocolo?->analista_atual_id) {
            throw ValidationException::withMessages([
                'protocolo' => 'Delegue o protocolo a um analista antes de editar esta secao.',
            ]);
        }
    }
```

Adicionar `use Illuminate\Validation\ValidationException;` no topo do arquivo.

Chamar `$this->assertAbasLiberadas($form);` como primeira linha de: `updateObjetivoContexto()`, `updateApontamentos()`, `updateConclusao()` e `finalizar()` (em `finalizar`, o guard só age quando há protocolo — formulário avulso continua podendo finalizar e gerar protocolo).

- [ ] **Step 4: Controller — `edit()` renderiza `Pae` + props enriquecidas**

Em `PaeFormularioController`:

1. Extrair helper privado no fim da classe:

```php
    private function protocoloProps(PaeProtocolo $prot): array
    {
        $prot->loadMissing('analistaAtual:id,name');

        return [
            'id'                => $prot->id,
            'num_protocolo'     => $prot->num_protocolo,
            'status'            => $prot->status?->value,
            'analista_atual_id' => $prot->analista_atual_id,
            'analista_nome'     => $prot->analistaAtual?->name,
            'arquivado'         => (bool) $prot->arquivado,
            'analise_status'    => $prot->analise_status,
        ];
    }
```

(`analise_status` é o accessor criado na Task 8; até lá retorna `null` — adicionar o accessor stub na Task 4 evita erro: ver Step 5.)

2. Em `show()`: substituir o bloco que monta `$protocolo` (linhas 63–72) por:

```php
        if ($protocoloId) {
            $prot = PaeProtocolo::find($protocoloId);
            if ($prot) {
                $protocolo = $this->protocoloProps($prot);
            }
        }
```

E no `Inertia::render('Pae', [...])` adicionar `'readOnly' => $request->boolean('readonly'),`.

3. Em `edit()`: trocar `Inertia::render('PaeEdit', [...])` por:

```php
        return Inertia::render('Pae', [
            'protocolo'  => $this->protocoloProps($paeProtocolo),
            'municipios' => Municipio::catalogo()->pluck('nome', 'id'),
            'formulario' => $this->service->formatForView($form),
            'readOnly'   => $request->boolean('readonly'),
        ]);
```

Ajustar assinatura para `public function edit(Request $request, PaeProtocolo $paeProtocolo)`.

- [ ] **Step 5: Stub do accessor `analise_status`**

Em `app/Modules/Pae/Models/PaeProtocolo.php`, adicionar (implementação completa na Task 8 usa exatamente esta lógica — já deixar final):

```php
    public function getAnaliseStatusAttribute(): ?string
    {
        $concluida = in_array($this->status, [
            PaeProtocoloStatus::APROVADO,
            PaeProtocoloStatus::REPROVADO,
            PaeProtocoloStatus::CCPAE,
            PaeProtocoloStatus::ATIVO_3_ANOS,
            PaeProtocoloStatus::REVOGADO,
        ], true);

        if ($concluida) {
            return 'concluida';
        }

        $emAndamento = $this->analista_atual_id
            && in_array($this->status, [
                PaeProtocoloStatus::NOTIFICACAO,
                PaeProtocoloStatus::ANALISE,
            ], true);

        return $emAndamento ? 'em_andamento' : null;
    }
```

- [ ] **Step 6: Rodar testes**

Run: `php artisan test tests/Feature/Pae/PaeFormularioGatingTest.php tests/Feature/Pae/PaeFormularioControllerTest.php --compact`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Pae/Services/PaeFormularioService.php app/Modules/Pae/Controllers/PaeFormularioController.php app/Modules/Pae/Models/PaeProtocolo.php tests/Feature/Pae/PaeFormularioGatingTest.php
git commit -m "🐛 fix(pae): edit renderiza pagina existente, props do protocolo completas e guard de abas por delegacao"
```

### Task 5: Frontend — abas condicionais e read-only real

**Files:**
- Modify: `resources/js/Pages/Pae.vue`
- Modify: `resources/js/Components/Pae/PaeForm.vue`
- Modify: `resources/js/Components/Pae/PaeFormInfoGerais.vue`
- Modify: `resources/js/Components/Pae/PaeFormObjetivoContexto.vue`
- Modify: `resources/js/Components/Pae/PaeFormApontamentos.vue`
- Modify: `resources/js/Components/Pae/PaeFormConclusao.vue`
- Modify: `resources/js/Components/Pae/PaeFormAnexos.vue`

**Interfaces:**
- Consumes: props `protocolo` (com `analista_atual_id`) e `readOnly` do controller (Task 4).
- Produces: abas filtradas por delegação; componentes de aba com prop `readOnly` que OCULTA botões de ação.

- [ ] **Step 1: `Pae.vue` repassa props**

No template (linhas 11–15), trocar por:

```vue
            <PaeForm
                :empreendimento="empreendimento"
                :municipios="props.municipios"
                :formulario="props.formulario"
                :protocolo="props.protocolo"
                :read-only="props.readOnly"
            />
```

Adicionar ao `defineProps`: `readOnly: { type: Boolean, default: false },`.

- [ ] **Step 2: Abas condicionais em `PaeForm.vue`**

1. Renomear a constante `tabConfig` para `todasAsAbas` (mesmo conteúdo).
2. Adicionar computed + watch (importar `watch` de vue):

```js
const abasLiberadas = computed(() => {
  // Sem protocolo vinculado (fluxo avulso) todas as abas ficam liberadas.
  if (!props.protocolo?.id) return true;
  return !!props.protocolo?.analista_atual_id;
});

const tabConfig = computed(() =>
  abasLiberadas.value
    ? todasAsAbas
    : todasAsAbas.filter((tab) => [1, 4].includes(tab.id))
);

watch(tabConfig, (tabs) => {
  if (!tabs.some((tab) => tab.id === activeSubTab.value)) {
    activeSubTab.value = 1;
  }
}, { immediate: true });
```

- [ ] **Step 3: Read-only oculta botões nos 5 componentes de aba**

Nenhum dos 5 componentes declara `readOnly` hoje (o `PaeForm` já passa `:read-only="readOnly"`). Em cada um:

1. Adicionar ao `defineProps`:

```js
  readOnly: {
    type: Boolean,
    default: false,
  },
```

2. Ocultar controles de escrita com `v-if="!readOnly"` (não apenas `disabled`):
   - `PaeFormInfoGerais.vue`, `PaeFormObjetivoContexto.vue`: botão de salvar/continuar (rodapé do form).
   - `PaeFormApontamentos.vue`, `PaeFormConclusao.vue`: botões salvar/finalizar, "adicionar item", "adicionar subitem" e ícones de remover.
   - `PaeFormAnexos.vue`: envolver o `<form ...>` de upload inteiro com `v-if="!readOnly"` e o botão "Remover" da lista com `v-if="!readOnly"` (manter Visualizar/Baixar).
   - Inputs/textareas/selects: adicionar `:disabled="readOnly"` (ou incluir `readOnly` na condição de disabled existente).

- [ ] **Step 4: Verificação**

Run: `grep -l "readOnly" resources/js/Components/Pae/PaeFormInfoGerais.vue resources/js/Components/Pae/PaeFormObjetivoContexto.vue resources/js/Components/Pae/PaeFormApontamentos.vue resources/js/Components/Pae/PaeFormConclusao.vue resources/js/Components/Pae/PaeFormAnexos.vue`
Expected: os 5 arquivos listados.

Run: `npm run build`
Expected: build sem erros.

Verificação manual (dev server): abrir protocolo sem analista → só 2 abas; atribuir analista → 5 abas; abrir com `?readonly=1` → sem botões.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Pae.vue resources/js/Components/Pae/PaeForm.vue resources/js/Components/Pae/PaeFormInfoGerais.vue resources/js/Components/Pae/PaeFormObjetivoContexto.vue resources/js/Components/Pae/PaeFormApontamentos.vue resources/js/Components/Pae/PaeFormConclusao.vue resources/js/Components/Pae/PaeFormAnexos.vue
git commit -m "✨ feat(pae): abas liberadas somente apos delegacao e modo visualizacao sem botoes"
```

### Task 6: Histórico de anexos (incluindo removidos)

**Files:**
- Test: acrescentar caso em `tests/Feature/Pae/PaeFormularioControllerTest.php`
- Modify: `app/Modules/Pae/Services/PaeFormularioService.php` (`formatForView`/`formatAnexos`)
- Modify: `app/Modules/Pae/Controllers/PaeFormularioController.php` (eager load com trashed)
- Modify: `resources/js/Components/Pae/PaeFormAnexos.vue`
- Modify: `resources/js/Components/Pae/PaeForm.vue` (repasse do histórico)

**Interfaces:**
- Consumes: `PaeFormAnexo` SoftDeletes + relação `uploader`.
- Produces: `formatForView()['anexos']` = somente ativos (formato atual); nova chave `formatForView()['anexos_historico']` = todos (ativos + removidos) com `{id, nome_original, tamanho_formatado, descricao, enviado_por, created_at, removido, deleted_at}`.

- [ ] **Step 1: Teste que falha**

Adicionar ao final de `tests/Feature/Pae/PaeFormularioControllerTest.php`:

```php
    public function test_formatforview_inclui_historico_de_anexos_com_removidos(): void
    {
        Storage::fake('pae');

        $form = PaeForm::create(['status' => 'RASCUNHO']);
        $uploader = User::factory()->create(['name' => 'Uploader Teste']);

        $ativo = $form->anexos()->create([
            'nome_original' => 'ativo.pdf',
            'nome_arquivo'  => 'a.pdf',
            'mime_type'     => 'application/pdf',
            'tamanho_bytes' => 100,
            'path'          => 'x/a.pdf',
            'disk'          => 'pae',
            'uploaded_by'   => $uploader->id,
        ]);
        $removido = $form->anexos()->create([
            'nome_original' => 'removido.pdf',
            'nome_arquivo'  => 'r.pdf',
            'mime_type'     => 'application/pdf',
            'tamanho_bytes' => 100,
            'path'          => 'x/r.pdf',
            'disk'          => 'pae',
            'uploaded_by'   => $uploader->id,
        ]);
        $removido->delete();

        $dados = app(\App\Modules\Pae\Services\PaeFormularioService::class)
            ->formatForView($form->fresh());

        $this->assertCount(1, $dados['anexos']);
        $this->assertCount(2, $dados['anexos_historico']);

        $historico = collect($dados['anexos_historico']);
        $this->assertTrue($historico->firstWhere('id', $removido->id)['removido']);
        $this->assertFalse($historico->firstWhere('id', $ativo->id)['removido']);
        $this->assertSame('Uploader Teste', $historico->firstWhere('id', $ativo->id)['enviado_por']);
    }
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeFormularioControllerTest.php --filter=historico --compact`
Expected: FAIL (chave `anexos_historico` inexistente).

- [ ] **Step 3: Backend**

Em `PaeFormularioService::formatForView()`, trocar o bloco `$anexos = ...` por:

```php
        $anexos = $form->anexos()
            ->withTrashed()
            ->with('uploader:id,name')
            ->get();

        $ativos = $anexos->whereNull('deleted_at');
```

Usar `'anexos' => $this->formatAnexos($ativos),` e adicionar logo abaixo:

```php
            'anexos_historico'        => $this->formatHistoricoAnexos($anexos),
```

Adicionar o método privado:

```php
    private function formatHistoricoAnexos($anexos): array
    {
        return $anexos
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (PaeFormAnexo $anexo) => [
                'id'                => $anexo->id,
                'nome_original'     => $anexo->nome_original,
                'tamanho_formatado' => $anexo->tamanho_formatado,
                'descricao'         => $anexo->descricao,
                'enviado_por'       => $anexo->uploader?->name,
                'created_at'        => $anexo->created_at?->format('d/m/Y H:i'),
                'removido'          => $anexo->trashed(),
                'deleted_at'        => $anexo->deleted_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }
```

Nos eager loads do controller (`show()` linhas 35 e 44, `edit()` linha 83) nada muda: `formatForView` agora consulta os anexos por conta própria (a relação carregada deixa de ser usada para anexos — remover `'anexos'` dos `with([...])` dos três pontos para não duplicar query).

- [ ] **Step 4: Frontend**

Em `PaeForm.vue`: passar `:historico="rat.anexosHistorico ?? props.formulario?.anexos_historico ?? []"` para `PaeFormAnexos` (o composable `usePaeFormulario` expõe `anexos` a partir do prop `formulario`; usar fallback direto do prop é suficiente — `:historico="props.formulario?.anexos_historico ?? []"`).

Em `PaeFormAnexos.vue`:
1. Nova prop:

```js
  historico: {
    type: Array,
    default: () => [],
  },
```

2. Após o bloco da lista de anexos (fecha na linha 142), adicionar seção:

```vue
      <div v-if="historico.length" class="border-t border-slate-200 pt-5 dark:border-slate-700">
        <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">
          Historico de arquivos anexados
        </h4>
        <ul class="space-y-2">
          <li
            v-for="item in historico"
            :key="`hist-${item.id}`"
            class="flex flex-col gap-1 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-slate-700 dark:bg-slate-900/40 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ item.nome_original }}</span>
              <span class="ml-2 text-slate-500 dark:text-slate-400">{{ item.tamanho_formatado }}</span>
            </div>
            <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
              <span v-if="item.enviado_por">por {{ item.enviado_por }}</span>
              <span>{{ item.created_at }}</span>
              <span
                v-if="item.removido"
                class="rounded-full bg-red-500/10 px-2 py-0.5 font-semibold text-red-500 dark:text-red-400"
              >
                Removido {{ item.deleted_at ? `em ${item.deleted_at}` : '' }}
              </span>
            </div>
          </li>
        </ul>
      </div>
```

- [ ] **Step 5: Rodar testes + build**

Run: `php artisan test tests/Feature/Pae/PaeFormularioControllerTest.php --compact && npm run build`
Expected: PASS + build ok.

- [ ] **Step 6: Commit e merge do grupo 2**

```bash
git add app/Modules/Pae/Services/PaeFormularioService.php app/Modules/Pae/Controllers/PaeFormularioController.php resources/js/Components/Pae/PaeFormAnexos.vue resources/js/Components/Pae/PaeForm.vue tests/Feature/Pae/PaeFormularioControllerTest.php
git commit -m "✨ feat(pae): historico de arquivos anexados na aba de anexos, incluindo removidos"
git checkout pae-protocolo-anexos
git merge --no-ff feat/pae-54-abas-visualizacao -m "🔀 merge(pae): abas por delegacao, visualizacao read-only e historico de anexos (issue #54)"
```

---

## Branch 3: `feat/pae-54-notificacoes` (Tasks 7–10)

Antes da Task 7: `git checkout pae-protocolo-anexos && git checkout -b feat/pae-54-notificacoes`

### Task 7: Models + `PaeNotificacaoService` (emitir/devolutiva) + rotas web

**Files:**
- Create: `app/Modules/Pae/Models/PaeAnalise.php`
- Create: `app/Modules/Pae/Models/PaeNotificacao.php`
- Create: `app/Modules/Pae/Services/PaeNotificacaoService.php`
- Create: `app/Modules/Pae/Requests/EmitirNotificacaoRequest.php`
- Create: `app/Modules/Pae/Requests/RegistrarDevolutivaRequest.php`
- Create: `app/Modules/Pae/Controllers/PaeNotificacaoController.php`
- Create: `database/factories/Pae/PaeAnaliseFactory.php`
- Create: `database/factories/Pae/PaeNotificacaoFactory.php`
- Modify: `routes/modules/pae.php`
- Test: `tests/Feature/Pae/PaeNotificacaoTest.php` (create)

**Interfaces:**
- Consumes: tabelas `pae_analises`/`pae_notificacoes` (migrations já existentes), `PaeProtocoloService::changeStatus()`, `PaeEmpnto.email_coord/email_coord_sub`.
- Produces:
  - `PaeAnalise` (fillable `pae_protocolo_id, user_id, status, parecer, situacao, obs, tipo, anexo`; relações `protocolo()`, `notificacoes()` ordenadas, `usuario()`)
  - `PaeNotificacao` (fillable `num_sei, user_id, pae_analise_id, dt_notificacao, prorrogacao, dt_devolutiva, obs`; casts date; relações `analise()`, `usuario()`)
  - `PaeNotificacaoService::PRAZO_DIAS = 30`, `MAX_CICLOS = 3`
  - `emitir(PaeProtocolo $protocolo, User $user, array $dados, bool $automatica = false): PaeNotificacao`
  - `registrarDevolutiva(PaeNotificacao $notificacao, User $user, string $dtDevolutiva): PaeNotificacao`
  - `listarPorProtocolo(PaeProtocolo $protocolo): array` (cada item: `id, ciclo, num_sei, dt_notificacao, prazo_final, dt_devolutiva, vencida, obs`)
  - Rotas: `POST /pae/protocolo/{paeProtocolo}/notificacoes` name `pae.protocolo.notificacoes.store`; `POST /pae/notificacoes/{paeNotificacao}/devolutiva` name `pae.notificacoes.devolutiva` — ambas `can:pae.protocolos.edit`.
  - O e-mail é disparado dentro de `emitir()` via método privado `enviarEmail()` — nesta task deixar o método criado porém com corpo só de timeline (Mailable entra na Task 8; testes desta task não cobrem e-mail).

- [ ] **Step 1: Models**

`app/Modules/Pae/Models/PaeAnalise.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeAnalise extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_analises';

    protected $fillable = [
        'pae_protocolo_id',
        'user_id',
        'status',
        'parecer',
        'situacao',
        'obs',
        'tipo',
        'anexo',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeAnaliseFactory::new();
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'pae_protocolo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(PaeNotificacao::class, 'pae_analise_id')
            ->orderBy('dt_notificacao')
            ->orderBy('id');
    }
}
```

`app/Modules/Pae/Models/PaeNotificacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeNotificacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_notificacoes';

    protected $fillable = [
        'num_sei',
        'user_id',
        'pae_analise_id',
        'dt_notificacao',
        'prorrogacao',
        'dt_devolutiva',
        'obs',
    ];

    protected $casts = [
        'dt_notificacao' => 'date',
        'dt_devolutiva'  => 'date',
        'prorrogacao'    => 'boolean',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeNotificacaoFactory::new();
    }

    public function analise(): BelongsTo
    {
        return $this->belongsTo(PaeAnalise::class, 'pae_analise_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

- [ ] **Step 2: Factories**

`database/factories/Pae/PaeAnaliseFactory.php`:

```php
<?php

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeAnaliseFactory extends Factory
{
    protected $model = PaeAnalise::class;

    public function definition(): array
    {
        return [
            'pae_protocolo_id' => PaeProtocolo::factory(),
            'status'           => 'EM_ANDAMENTO',
            'parecer'          => '',
        ];
    }
}
```

`database/factories/Pae/PaeNotificacaoFactory.php`:

```php
<?php

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeNotificacaoFactory extends Factory
{
    protected $model = PaeNotificacao::class;

    public function definition(): array
    {
        return [
            'pae_analise_id' => PaeAnalise::factory(),
            'num_sei'        => 'SEI-' . $this->faker->numerify('####.######/####-##'),
            'dt_notificacao' => now()->toDateString(),
            'prorrogacao'    => false,
        ];
    }
}
```

- [ ] **Step 3: Testes que falham**

Criar `tests/Feature/Pae/PaeNotificacaoTest.php`:

```php
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
```

- [ ] **Step 4: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeNotificacaoTest.php --compact`
Expected: FAIL (classes inexistentes).

- [ ] **Step 5: Service**

`app/Modules/Pae/Services/PaeNotificacaoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Shared\BaseService;
use Illuminate\Validation\ValidationException;

class PaeNotificacaoService extends BaseService
{
    public const PRAZO_DIAS = 30;
    public const MAX_CICLOS = 3;

    public function __construct(
        private readonly PaeProtocoloService $protocoloService
    ) {}

    public function emitir(PaeProtocolo $protocolo, User $user, array $dados, bool $automatica = false): PaeNotificacao
    {
        $this->assertPodeEmitir($protocolo);

        $analise = PaeAnalise::firstOrCreate(
            ['pae_protocolo_id' => $protocolo->id],
            [
                'user_id' => $protocolo->analista_atual_id,
                'status'  => 'EM_ANDAMENTO',
                'parecer' => '',
            ]
        );

        $ciclo = $analise->notificacoes()->count() + 1;

        if ($ciclo > self::MAX_CICLOS) {
            throw ValidationException::withMessages([
                'notificacao' => 'Limite de ' . self::MAX_CICLOS . ' notificacoes atingido para este protocolo.',
            ]);
        }

        if ($analise->notificacoes()->whereNull('dt_devolutiva')->exists()) {
            throw ValidationException::withMessages([
                'notificacao' => 'Existe uma notificacao com prazo em aberto. Registre a devolutiva antes de emitir outra.',
            ]);
        }

        $notificacao = $analise->notificacoes()->create([
            'num_sei'        => $dados['num_sei'],
            'user_id'        => $user->id,
            'dt_notificacao' => now()->toDateString(),
            'prorrogacao'    => false,
            'obs'            => $dados['obs'] ?? null,
        ]);

        $origem = $automatica ? 'automaticamente por vencimento do ciclo anterior' : "por {$user->name}";
        $this->registrarTimeline(
            $protocolo,
            'notificacao',
            "Notificacao {$ciclo} emitida {$origem}. SEI {$notificacao->num_sei}. Prazo de " . self::PRAZO_DIAS . ' dias para devolutiva.',
            $user
        );

        $this->enviarEmail($protocolo, $notificacao, $ciclo, $user);

        return $notificacao;
    }

    public function registrarDevolutiva(PaeNotificacao $notificacao, User $user, string $dtDevolutiva): PaeNotificacao
    {
        if ($notificacao->dt_devolutiva) {
            throw ValidationException::withMessages([
                'devolutiva' => 'Este ciclo de notificacao ja possui devolutiva registrada.',
            ]);
        }

        $notificacao->update(['dt_devolutiva' => $dtDevolutiva]);

        $protocolo = $notificacao->analise?->protocolo;
        if ($protocolo) {
            $this->registrarTimeline(
                $protocolo,
                'notificacao',
                "Devolutiva registrada para a notificacao SEI {$notificacao->num_sei} em " .
                    now()->parse($dtDevolutiva)->format('d/m/Y') . '.',
                $user
            );
        }

        return $notificacao->fresh();
    }

    public function processarVencimentos(): int
    {
        $processadas = 0;

        $analises = PaeAnalise::query()
            ->whereHas('notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->with(['notificacoes', 'protocolo.analistaAtual', 'protocolo.usuario', 'protocolo.empreendimento'])
            ->get();

        foreach ($analises as $analise) {
            $protocolo = $analise->protocolo;

            if (! $protocolo || $protocolo->arquivado || $protocolo->status->isTerminal()) {
                continue;
            }

            if ($protocolo->status === PaeProtocoloStatus::SUSPENSO) {
                continue;
            }

            $ultima = $analise->notificacoes->last();

            if (! $ultima || $ultima->dt_devolutiva) {
                continue;
            }

            $vencida = $ultima->dt_notificacao
                ->copy()
                ->addDays(self::PRAZO_DIAS)
                ->isBefore(now()->startOfDay());

            if (! $vencida) {
                continue;
            }

            $autor = $protocolo->analistaAtual ?? $protocolo->usuario;
            $ciclo = $analise->notificacoes->count();

            if ($ciclo >= self::MAX_CICLOS) {
                $this->protocoloService->changeStatus(
                    $protocolo,
                    PaeProtocoloStatus::SUSPENSO,
                    $autor,
                    'Suspenso automaticamente: 3a notificacao vencida sem devolutiva.'
                );
            } else {
                $this->emitir(
                    $protocolo,
                    $autor,
                    [
                        'num_sei' => $ultima->num_sei,
                        'obs'     => 'Emitida automaticamente: ciclo ' . $ciclo . ' vencido sem devolutiva.',
                    ],
                    true
                );
            }

            $processadas++;
        }

        return $processadas;
    }

    public function listarPorProtocolo(PaeProtocolo $protocolo): array
    {
        $analise = PaeAnalise::with('notificacoes')
            ->where('pae_protocolo_id', $protocolo->id)
            ->first();

        if (! $analise) {
            return [];
        }

        return $analise->notificacoes
            ->values()
            ->map(fn (PaeNotificacao $n, int $i) => [
                'id'             => $n->id,
                'ciclo'          => $i + 1,
                'num_sei'        => $n->num_sei,
                'dt_notificacao' => $n->dt_notificacao->toDateString(),
                'prazo_final'    => $n->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->toDateString(),
                'dt_devolutiva'  => $n->dt_devolutiva?->toDateString(),
                'vencida'        => ! $n->dt_devolutiva
                    && $n->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->isBefore(now()->startOfDay()),
                'obs'            => $n->obs,
            ])
            ->all();
    }

    private function assertPodeEmitir(PaeProtocolo $protocolo): void
    {
        if (! $protocolo->analista_atual_id) {
            throw ValidationException::withMessages([
                'notificacao' => 'Delegue o protocolo a um analista antes de emitir notificacoes.',
            ]);
        }

        if ($protocolo->arquivado || $protocolo->status->isTerminal()) {
            throw ValidationException::withMessages([
                'notificacao' => 'Protocolo arquivado ou encerrado nao recebe notificacoes.',
            ]);
        }
    }

    private function enviarEmail(PaeProtocolo $protocolo, PaeNotificacao $notificacao, int $ciclo, User $user): void
    {
        // Envio real do e-mail implementado com o Mailable PaeNotificacaoMail (Task 8).
        $empnto = $protocolo->empreendimento;

        if (! $empnto?->email_coord) {
            $this->registrarTimeline(
                $protocolo,
                'notificacao',
                'Empreendimento sem e-mail de coordenador cadastrado: notificacao registrada apenas no sistema.',
                $user
            );
        }
    }

    private function registrarTimeline(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento'       => $evento,
            'descricao'    => $descricao,
            'user_id'      => $user->id,
        ]);
    }
}
```

- [ ] **Step 6: FormRequests + Controller + rotas**

`app/Modules/Pae/Requests/EmitirNotificacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirNotificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'num_sei' => ['required', 'string', 'max:110'],
            'obs'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

`app/Modules/Pae/Requests/RegistrarDevolutivaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDevolutivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dt_devolutiva' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
```

`app/Modules/Pae/Controllers/PaeNotificacaoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\EmitirNotificacaoRequest;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\RedirectResponse;

class PaeNotificacaoController extends Controller
{
    public function __construct(
        private readonly PaeNotificacaoService $service
    ) {}

    public function store(EmitirNotificacaoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $notificacao = $this->service->emitir($paeProtocolo, $request->user(), $request->validated());

        return back()->with('success', "Notificacao SEI {$notificacao->num_sei} emitida com prazo de 30 dias.");
    }

    public function devolutiva(RegistrarDevolutivaRequest $request, PaeNotificacao $paeNotificacao): RedirectResponse
    {
        $this->service->registrarDevolutiva(
            $paeNotificacao,
            $request->user(),
            $request->validated()['dt_devolutiva']
        );

        return back()->with('success', 'Devolutiva registrada com sucesso.');
    }
}
```

Em `routes/modules/pae.php` (topo: adicionar `use App\Modules\Pae\Controllers\PaeNotificacaoController;`), após a rota `protocolo.relacionar`:

```php
    Route::post('/protocolo/{paeProtocolo}/notificacoes', [PaeNotificacaoController::class, 'store'])
        ->name('protocolo.notificacoes.store')
        ->middleware('can:pae.protocolos.edit');

    Route::post('/notificacoes/{paeNotificacao}/devolutiva', [PaeNotificacaoController::class, 'devolutiva'])
        ->name('notificacoes.devolutiva')
        ->middleware('can:pae.protocolos.edit');
```

- [ ] **Step 7: Rodar testes**

Run: `php artisan test tests/Feature/Pae/PaeNotificacaoTest.php --compact`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Pae/Models/PaeAnalise.php app/Modules/Pae/Models/PaeNotificacao.php app/Modules/Pae/Services/PaeNotificacaoService.php app/Modules/Pae/Requests/EmitirNotificacaoRequest.php app/Modules/Pae/Requests/RegistrarDevolutivaRequest.php app/Modules/Pae/Controllers/PaeNotificacaoController.php database/factories/Pae/PaeAnaliseFactory.php database/factories/Pae/PaeNotificacaoFactory.php routes/modules/pae.php tests/Feature/Pae/PaeNotificacaoTest.php
git commit -m "✨ feat(pae): ciclo de notificacoes 1/2/3 com emissao manual, devolutiva e regras de 30 dias"
```

### Task 8: Mailable + Command agendado + suspensão automática

**Files:**
- Create: `app/Mail/PaeNotificacaoMail.php`
- Create: `resources/views/emails/pae_notificacao.blade.php`
- Create: `app/Console/Commands/VerificarNotificacoesPae.php`
- Modify: `app/Modules/Pae/Services/PaeNotificacaoService.php` (completar `enviarEmail()`)
- Modify: `app/Modules/Pae/Enums/PaeProtocoloStatus.php` (transição `NOTIFICACAO → SUSPENSO`)
- Modify: `routes/console.php`
- Test: `tests/Feature/Pae/PaeNotificacaoCommandTest.php` (create)

**Interfaces:**
- Consumes: `PaeNotificacaoService::processarVencimentos()` e `emitir()` (Task 7).
- Produces: `PaeNotificacaoMail` (construtor com primitivos: `string $protocoloNumero, string $empreendimentoNome, int $ciclo, string $numSei, string $dtNotificacao, string $prazoFinal`); command `pae:verificar-notificacoes`; enum aceita `NOTIFICACAO → SUSPENSO`.

- [ ] **Step 1: Testes que falham**

Criar `tests/Feature/Pae/PaeNotificacaoCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Mail\PaeNotificacaoMail;
use App\Models\User;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaeNotificacaoCommandTest extends TestCase
{
    use DatabaseTransactions;

    private function protocoloDelegadoComEmail(): PaeProtocolo
    {
        $empnto = PaeEmpnto::factory()->create([
            'email_coord'     => 'coord@example.com',
            'email_coord_sub' => 'sub@example.com',
        ]);

        return PaeProtocolo::factory()->create([
            'status'            => 'notificacao',
            'analista_atual_id' => User::factory()->create()->id,
            'pae_empnto_id'     => $empnto->id,
        ]);
    }

    public function test_emitir_envia_email_para_coordenador(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();

        app(PaeNotificacaoService::class)
            ->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']);

        Mail::assertQueued(PaeNotificacaoMail::class, function (PaeNotificacaoMail $mail) {
            return $mail->hasTo('coord@example.com') && $mail->hasCc('sub@example.com');
        });
    }

    public function test_ciclo_vencido_emite_proxima_automaticamente(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);

        $this->travelTo(now()->subDays(31), function () use ($service, $protocolo) {
            $service->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']);
        });

        $this->artisan('pae:verificar-notificacoes')->assertSuccessful();

        $this->assertSame(2, \App\Modules\Pae\Models\PaeNotificacao::whereHas(
            'analise',
            fn ($q) => $q->where('pae_protocolo_id', $protocolo->id)
        )->count());
    }

    public function test_terceiro_ciclo_vencido_suspende_protocolo(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);
        $user = User::factory()->create();

        $this->travelTo(now()->subDays(95), fn () => $service->emitir($protocolo, $user, ['num_sei' => 'SEI-1']));
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 2 (retroativo, dt = hoje-0... ver nota)

        // Forcar vencimento dos ciclos 2 e 3 ajustando as datas diretamente:
        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 3

        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // suspende

        $this->assertSame('suspenso', $protocolo->fresh()->status->value);
    }

    public function test_comando_e_idempotente_no_mesmo_dia(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);

        $this->travelTo(now()->subDays(31), fn () => $service->emitir($protocolo, User::factory()->create(), ['num_sei' => 'SEI-1']));

        $this->artisan('pae:verificar-notificacoes');
        $this->artisan('pae:verificar-notificacoes');

        $this->assertSame(2, \App\Modules\Pae\Models\PaeNotificacao::whereHas(
            'analise',
            fn ($q) => $q->where('pae_protocolo_id', $protocolo->id)
        )->count());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/PaeNotificacaoCommandTest.php --compact`
Expected: FAIL (Mailable e command inexistentes).

- [ ] **Step 3: Mailable + blade**

`app/Mail/PaeNotificacaoMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notificacao do ciclo PAE (1/2/3) enviada ao coordenador do empreendimento.
 * Recebe primitivos (nao models) — mesmo padrao do UserOnboardingMail.
 */
class PaeNotificacaoMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $protocoloNumero,
        public string $empreendimentoNome,
        public int $ciclo,
        public string $numSei,
        public string $dtNotificacao,
        public string $prazoFinal,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "PAE {$this->protocoloNumero} - Notificacao {$this->ciclo} de 3",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pae_notificacao',
        );
    }
}
```

`resources/views/emails/pae_notificacao.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Notificacao PAE</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #14213d; margin: 0; padding: 24px; background-color: #f4f6fb;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px; border: 1px solid #e2e8f0;">
        <h2 style="margin-top: 0; color: #14213d;">Notificacao {{ $ciclo }} de 3 — Protocolo PAE</h2>

        <p>Prezado(a) Coordenador(a) do empreendimento <strong>{{ $empreendimentoNome }}</strong>,</p>

        <p>
            Informamos a emissao da <strong>notificacao {{ $ciclo }}</strong> referente ao protocolo
            <strong>{{ $protocoloNumero }}</strong> (SEI {{ $numSei }}), emitida em {{ \Carbon\Carbon::parse($dtNotificacao)->format('d/m/Y') }}.
        </p>

        <p style="background: #fff4e5; border-left: 4px solid #fca311; padding: 12px 16px;">
            O prazo para devolutiva e de <strong>30 dias</strong>, encerrando em
            <strong>{{ \Carbon\Carbon::parse($prazoFinal)->format('d/m/Y') }}</strong>.
            @if ($ciclo >= 3)
                Esta e a ultima notificacao: a ausencia de devolutiva acarretara a suspensao do protocolo.
            @endif
        </p>

        <p>Em caso de duvidas, responda ao processo SEI indicado acima.</p>

        <p style="margin-bottom: 0;">Coordenadoria Estadual de Defesa Civil - CEDEC/MG</p>
    </div>
</body>
</html>
```

- [ ] **Step 4: Completar `enviarEmail()` no service**

Substituir o corpo de `enviarEmail()` por:

```php
    private function enviarEmail(PaeProtocolo $protocolo, PaeNotificacao $notificacao, int $ciclo, User $user): void
    {
        $protocolo->loadMissing('empreendimento');
        $empnto = $protocolo->empreendimento;

        if (! $empnto?->email_coord) {
            $this->registrarTimeline(
                $protocolo,
                'notificacao',
                'Empreendimento sem e-mail de coordenador cadastrado: notificacao registrada apenas no sistema.',
                $user
            );

            return;
        }

        $mail = Mail::to($empnto->email_coord);

        if ($empnto->email_coord_sub) {
            $mail->cc($empnto->email_coord_sub);
        }

        $mail->queue(new PaeNotificacaoMail(
            protocoloNumero: $protocolo->num_protocolo,
            empreendimentoNome: $empnto->nome ?? '',
            ciclo: $ciclo,
            numSei: $notificacao->num_sei,
            dtNotificacao: $notificacao->dt_notificacao->toDateString(),
            prazoFinal: $notificacao->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->toDateString(),
        ));
    }
```

Adicionar `use App\Mail\PaeNotificacaoMail;` e `use Illuminate\Support\Facades\Mail;` no topo.

- [ ] **Step 5: Enum + Command + schedule**

Em `PaeProtocoloStatus::getAllowedTransitions()`, trocar o case `NOTIFICACAO`:

```php
            self::NOTIFICACAO => [
                self::ANALISE,
                self::SUSPENSO,
            ],
```

`app/Console/Commands/VerificarNotificacoesPae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Console\Command;

class VerificarNotificacoesPae extends Command
{
    protected $signature = 'pae:verificar-notificacoes';

    protected $description = 'Processa vencimentos dos ciclos de notificacao PAE (30 dias): emite a proxima notificacao ou suspende o protocolo apos a terceira.';

    public function handle(PaeNotificacaoService $service): int
    {
        $processadas = $service->processarVencimentos();

        $this->info("Notificacoes PAE processadas: {$processadas}.");

        return self::SUCCESS;
    }
}
```

Em `routes/console.php`, após o schedule existente:

```php
Schedule::command('pae:verificar-notificacoes')
    ->dailyAt('03:00')
    ->onOneServer()
    ->runInBackground();
```

- [ ] **Step 6: Rodar testes**

Run: `php artisan test tests/Feature/Pae/PaeNotificacaoCommandTest.php tests/Feature/Pae/PaeNotificacaoTest.php --compact`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Mail/PaeNotificacaoMail.php resources/views/emails/pae_notificacao.blade.php app/Console/Commands/VerificarNotificacoesPae.php app/Modules/Pae/Services/PaeNotificacaoService.php app/Modules/Pae/Enums/PaeProtocoloStatus.php routes/console.php tests/Feature/Pae/PaeNotificacaoCommandTest.php
git commit -m "✨ feat(pae): e-mail ao coordenador, scheduler diario e suspensao automatica apos 3a notificacao vencida"
```

### Task 9: API REST V1 de notificações (Sanctum + Swagger)

**Files:**
- Create: `app/Http/Controllers/Api/V1/Pae/NotificacaoController.php`
- Create: `app/Http/Resources/Pae/PaeNotificacaoResource.php`
- Modify: `routes/api.php` (dentro do grupo `pae` existente, linhas 116+)
- Test: `tests/Feature/Pae/Api/NotificacaoApiTest.php` (create)

**Interfaces:**
- Consumes: `PaeNotificacaoService` (Task 7).
- Produces: `GET /api/v1/pae/protocolos/{paeProtocolo}/notificacoes` (200 `{data: [...]}`), `POST .../notificacoes` (201), `POST /api/v1/pae/notificacoes/{paeNotificacao}/devolutiva` (200). Erros de regra → 422 (ValidationException padrão Laravel).

- [ ] **Step 1: Teste que falha**

Criar `tests/Feature/Pae/Api/NotificacaoApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test tests/Feature/Pae/Api/NotificacaoApiTest.php --compact`
Expected: FAIL (404 rotas inexistentes).

- [ ] **Step 3: Resource + Controller + rotas**

`app/Http/Resources/Pae/PaeNotificacaoResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Pae;

use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaeNotificacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $prazoFinal = $this->dt_notificacao
            ->copy()
            ->addDays(PaeNotificacaoService::PRAZO_DIAS);

        return [
            'id'             => $this->id,
            'num_sei'        => $this->num_sei,
            'dt_notificacao' => $this->dt_notificacao->toDateString(),
            'prazo_final'    => $prazoFinal->toDateString(),
            'dt_devolutiva'  => $this->dt_devolutiva?->toDateString(),
            'vencida'        => ! $this->dt_devolutiva && $prazoFinal->isBefore(now()->startOfDay()),
            'obs'            => $this->obs,
        ];
    }
}
```

`app/Http/Controllers/Api/V1/Pae/NotificacaoController.php` (anotações Swagger seguem o estilo do `EmpreendimentoController` do mesmo diretório — conferir se usa atributos `#[OA\...]` ou docblocks `@OA\` e replicar):

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\EmitirNotificacaoRequest;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\JsonResponse;

class NotificacaoController extends Controller
{
    public function __construct(
        private readonly PaeNotificacaoService $service
    ) {}

    public function index(PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json([
            'data' => $this->service->listarPorProtocolo($paeProtocolo),
        ]);
    }

    public function store(EmitirNotificacaoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        $notificacao = $this->service->emitir($paeProtocolo, $request->user(), $request->validated());

        return response()->json([
            'data' => new \App\Http\Resources\Pae\PaeNotificacaoResource($notificacao),
        ], 201);
    }

    public function devolutiva(RegistrarDevolutivaRequest $request, PaeNotificacao $paeNotificacao): JsonResponse
    {
        $notificacao = $this->service->registrarDevolutiva(
            $paeNotificacao,
            $request->user(),
            $request->validated()['dt_devolutiva']
        );

        return response()->json([
            'data' => new \App\Http\Resources\Pae\PaeNotificacaoResource($notificacao),
        ]);
    }
}
```

Em `routes/api.php`, dentro do grupo `Route::prefix('pae')` existente (após as rotas de empreendimentos), adicionar (import no topo: `use App\Http\Controllers\Api\V1\Pae\NotificacaoController;`):

```php
        Route::get('protocolos/{paeProtocolo}/notificacoes', [NotificacaoController::class, 'index'])
            ->name('protocolos.notificacoes.index')
            ->middleware('can:pae.protocolos.view');

        Route::post('protocolos/{paeProtocolo}/notificacoes', [NotificacaoController::class, 'store'])
            ->name('protocolos.notificacoes.store')
            ->middleware('can:pae.protocolos.edit');

        Route::post('notificacoes/{paeNotificacao}/devolutiva', [NotificacaoController::class, 'devolutiva'])
            ->name('notificacoes.devolutiva')
            ->middleware('can:pae.protocolos.edit');
```

- [ ] **Step 4: Rodar testes**

Run: `php artisan test tests/Feature/Pae/Api/NotificacaoApiTest.php --compact`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/Pae/NotificacaoController.php app/Http/Resources/Pae/PaeNotificacaoResource.php routes/api.php tests/Feature/Pae/Api/NotificacaoApiTest.php
git commit -m "✨ feat(pae): API REST v1 de notificacoes do protocolo com resources e permissoes"
```

### Task 10: Painel de notificações + status de análise na UI

**Files:**
- Create: `resources/js/Components/Organisms/Pae/Protocolos/PaeNotificacoesPanel.vue`
- Modify: `resources/js/Pages/Pae.vue`
- Modify: `app/Modules/Pae/Controllers/PaeFormularioController.php` (prop `notificacoes`)
- Modify: `app/Modules/Pae/Services/PaeProtocoloService.php` (`list()` já traz `analistaAtual`; nada a mudar — conferir)
- Modify: `resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue` (status de análise na listagem)
- Modify: `app/Modules/Pae/Models/PaeProtocolo.php` (appends)

**Interfaces:**
- Consumes: `listarPorProtocolo()` (Task 7), accessor `analise_status` (Task 4), rotas web de notificação (Task 7).
- Produces: prop Inertia `notificacoes: array`; painel visível quando `protocolo.analista_atual_id` preenchido; listagem exibe "Analise em andamento/concluida".

- [ ] **Step 1: Backend — prop `notificacoes` + appends**

Em `PaeProtocolo.php`, adicionar:

```php
    protected $appends = ['analise_status'];
```

Em `PaeFormularioController`:
1. Injetar o service no construtor:

```php
    public function __construct(
        private readonly PaeFormularioService $service,
        private readonly PaeNotificacaoService $notificacaoService
    ) {}
```

(import `use App\Modules\Pae\Services\PaeNotificacaoService;`)

2. Em `show()`: no `Inertia::render('Pae', [...])`, adicionar:

```php
            'notificacoes' => isset($prot) && $prot ? $this->notificacaoService->listarPorProtocolo($prot) : [],
```

(garantir que `$prot` do bloco do protocolo esteja acessível; se necessário, guardar `$protModel` numa variável no mesmo escopo.)

3. Em `edit()`: adicionar `'notificacoes' => $this->notificacaoService->listarPorProtocolo($paeProtocolo),`.

- [ ] **Step 2: Componente `PaeNotificacoesPanel.vue`**

Criar `resources/js/Components/Organisms/Pae/Protocolos/PaeNotificacoesPanel.vue`:

```vue
<template>
  <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800/50">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Analise e Notificacoes</h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          <span
            v-if="statusLabel"
            :class="protocolo.analise_status === 'concluida'
              ? 'font-semibold text-emerald-600 dark:text-emerald-400'
              : 'font-semibold text-indigo-600 dark:text-indigo-400'"
          >
            {{ statusLabel }}
          </span>
          <span v-if="protocolo.analista_nome"> — Analista: {{ protocolo.analista_nome }}</span>
        </p>
      </div>

      <button
        v-if="!readOnly && podeEmitir"
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-500"
        @click="showEmitir = !showEmitir"
      >
        Emitir notificacao
      </button>
    </div>

    <form
      v-if="showEmitir && !readOnly"
      class="mt-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40 sm:flex-row sm:items-end"
      @submit.prevent="emitir"
    >
      <div class="flex-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">Numero SEI</label>
        <input
          v-model="formEmitir.num_sei"
          type="text"
          required
          maxlength="110"
          class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
        />
      </div>
      <div class="flex-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">Observacao (opcional)</label>
        <input
          v-model="formEmitir.obs"
          type="text"
          maxlength="1000"
          class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
        />
      </div>
      <button
        type="submit"
        :disabled="enviando"
        class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-500 disabled:opacity-60"
      >
        {{ enviando ? 'Enviando...' : 'Confirmar' }}
      </button>
    </form>

    <div v-if="notificacoes.length" class="mt-4 space-y-2">
      <div
        v-for="n in notificacoes"
        :key="n.id"
        class="flex flex-col gap-2 rounded-lg border border-slate-200 px-4 py-3 text-xs dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-center gap-3">
          <span class="rounded-full bg-blue-500/10 px-2 py-0.5 font-semibold text-blue-600 dark:text-blue-300">
            Ciclo {{ n.ciclo }}/3
          </span>
          <span class="text-slate-700 dark:text-slate-300">SEI {{ n.num_sei }}</span>
          <span class="text-slate-500 dark:text-slate-400">
            Emitida {{ formatarData(n.dt_notificacao) }} — prazo {{ formatarData(n.prazo_final) }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <span
            v-if="n.dt_devolutiva"
            class="rounded-full bg-emerald-500/10 px-2 py-0.5 font-semibold text-emerald-600 dark:text-emerald-400"
          >
            Devolutiva {{ formatarData(n.dt_devolutiva) }}
          </span>
          <span
            v-else-if="n.vencida"
            class="rounded-full bg-red-500/10 px-2 py-0.5 font-semibold text-red-500 dark:text-red-400"
          >
            Prazo vencido
          </span>
          <span
            v-else
            class="rounded-full bg-amber-500/10 px-2 py-0.5 font-semibold text-amber-600 dark:text-amber-400"
          >
            Aguardando devolutiva
          </span>

          <form
            v-if="!readOnly && !n.dt_devolutiva"
            class="flex items-center gap-1"
            @submit.prevent="registrarDevolutiva(n)"
          >
            <input
              v-model="devolutivas[n.id]"
              type="date"
              :max="hoje"
              required
              class="rounded-lg border border-slate-300 bg-white p-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            />
            <button
              type="submit"
              class="rounded-lg border border-emerald-500/40 px-2 py-1.5 font-semibold text-emerald-600 transition-colors hover:bg-emerald-500/10 dark:text-emerald-400"
            >
              Registrar devolutiva
            </button>
          </form>
        </div>
      </div>
    </div>

    <p v-else class="mt-4 text-xs text-slate-500 dark:text-slate-400">
      Nenhuma notificacao emitida para este protocolo.
    </p>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  protocolo: {
    type: Object,
    required: true,
  },
  notificacoes: {
    type: Array,
    default: () => [],
  },
  readOnly: {
    type: Boolean,
    default: false,
  },
});

const showEmitir = ref(false);
const enviando = ref(false);
const formEmitir = reactive({ num_sei: '', obs: '' });
const devolutivas = reactive({});

const hoje = new Date().toISOString().slice(0, 10);

const statusLabel = computed(() => ({
  em_andamento: 'Analise em andamento',
  concluida: 'Analise concluida',
}[props.protocolo?.analise_status] ?? null));

const podeEmitir = computed(() =>
  props.notificacoes.length < 3
  && !props.notificacoes.some((n) => !n.dt_devolutiva)
);

function emitir() {
  enviando.value = true;
  router.post(
    route('pae.protocolo.notificacoes.store', props.protocolo.id),
    { ...formEmitir },
    {
      preserveScroll: true,
      onFinish: () => {
        enviando.value = false;
        showEmitir.value = false;
        formEmitir.num_sei = '';
        formEmitir.obs = '';
      },
    }
  );
}

function registrarDevolutiva(notificacao) {
  router.post(
    route('pae.notificacoes.devolutiva', notificacao.id),
    { dt_devolutiva: devolutivas[notificacao.id] },
    { preserveScroll: true }
  );
}

function formatarData(iso) {
  if (!iso) return '';
  const [ano, mes, dia] = iso.split('-');
  return `${dia}/${mes}/${ano}`;
}
</script>
```

- [ ] **Step 3: Integrar em `Pae.vue`**

Adicionar entre `PaeHeader` e `PaeForm`:

```vue
            <PaeNotificacoesPanel
                v-if="props.protocolo?.analista_atual_id"
                :protocolo="props.protocolo"
                :notificacoes="props.notificacoes ?? []"
                :read-only="props.readOnly"
            />
```

Imports/props: `import PaeNotificacoesPanel from '@/Components/Organisms/Pae/Protocolos/PaeNotificacoesPanel.vue';` e prop `notificacoes: { type: Array, default: () => [] },`.

- [ ] **Step 4: Status de análise na listagem**

Em `PaeProtocolosIndexTemplate.vue`, no mapeamento da linha (~336), adicionar:

```js
    analiseStatus: p.analise_status ?? null,
```

Em `PaeProtocolosTable.vue`, na célula Analista (linhas 32–34), abaixo do nome:

```vue
              <div
                v-if="protocolo.analiseStatus"
                class="mt-0.5 text-xs font-medium"
                :class="protocolo.analiseStatus === 'concluida' ? 'text-emerald-500' : 'text-indigo-400'"
              >
                {{ protocolo.analiseStatus === 'concluida' ? 'Analise concluida' : 'Analise em andamento' }}
              </div>
```

- [ ] **Step 5: Build + verificação manual**

Run: `npm run build`
Expected: sem erros.

Verificação manual: protocolo delegado exibe painel; emitir notificação cria ciclo 1; registrar devolutiva fecha ciclo; listagem mostra status de análise.

- [ ] **Step 6: Rodar suite PAE completa + commit + merge do grupo 3**

Run: `php artisan test tests/Feature/Pae --compact`
Expected: PASS em todos.

```bash
git add resources/js/Components/Organisms/Pae/Protocolos/PaeNotificacoesPanel.vue resources/js/Pages/Pae.vue app/Modules/Pae/Controllers/PaeFormularioController.php app/Modules/Pae/Models/PaeProtocolo.php resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue
git commit -m "✨ feat(pae): painel de analise e notificacoes no protocolo e status derivado na listagem"
git checkout pae-protocolo-anexos
git merge --no-ff feat/pae-54-notificacoes -m "🔀 merge(pae): notificacoes 1/2/3, e-mail, suspensao automatica e API v1 (issue #54)"
```

---

## Verificação final (na `pae-protocolo-anexos`)

- [ ] `php artisan test tests/Feature/Pae --compact` → todos PASS
- [ ] `npm run build` → sem erros
- [ ] Fluxo manual completo: criar protocolo (número novo) → abrir (2 abas) → atribuir analista (5 abas + painel) → emitir notificação (timeline + e-mail em log/mailpit) → devolutiva → relacionar (versão 002) → visualizar read-only (sem botões) → histórico de anexos com removido
- [ ] Sem scripts ad-hoc de teste no stage (`git status`) — regra 10 do usuário
