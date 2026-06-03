# API REST PAE — Empreendimentos (CRUD + Swagger)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Substituir o stub mock do `EmpreendimentoController` (Api/V1/Pae) por uma implementação real conectada ao Model `PaeEmpnto`, com Resource, FormRequests, Service e Swagger alinhado aos campos reais do DB/Front.

**Architecture:** Camadas SOLID — Controller (HTTP/Swagger) -> FormRequest (validação) -> Service (regra de negócio) -> Eloquent Model `PaeEmpnto` + relações (`Municipio`, `PaeEmpdor`, `PaeProtocolo` latest). Resource transforma para o contrato JSON `{ "data": {...} }` consumido por integradores externos. Trait `ApiResponseTrait` padroniza envelope.

**Tech Stack:** Laravel 11 + Sanctum (Bearer Token) + L5-Swagger + Eloquent + PHPUnit/Pest.

**Branch:** `feat/pae-api-empreendimentos` — partindo de `dev` (limpo). Merge ao final com `--no-ff`.

**Política de commits (conforme memória do usuário):**
- Um único commit por fase (não granular por step)
- Sem trailer `Co-Authored-By`
- Mensagens em PT-BR seguindo padrão do projeto (`feat(pae-api): ...`)

**Política de testes (conforme memória do usuário):**
- Testes Feature são escritos para **validação local** durante TDD
- **Não entram nos commits** finais — após cada fase, validar local e fazer `git add` apenas dos arquivos fonte
- Mantemos os arquivos de teste no working tree durante todo o trabalho como rede de segurança

**Pré-requisito de ambiente:** Decidido pelo usuário — trazer para a feat branch as WIPs já em andamento sobre o módulo PAE Anexos/Formulário e Docker dev:

- `SDC/app/Modules/Pae/Requests/StorePaeFormAnexoRequest.php` (novo)
- `SDC/app/Modules/Pae/Services/PaeFormularioService.php` (M)
- `SDC/app/Modules/Pae/Controllers/PaeFormularioController.php` (M)
- `SDC/routes/modules/pae.php` (M)
- `SDC/resources/js/Components/Pae/PaeFormAnexos.vue` (M)
- `SDC/resources/js/Components/Pae/PaeForm.vue` (M)
- `SDC/resources/js/Composables/pae/usePaeFormulario.js` (M)
- `SDC/tests/Feature/Pae/PaeFormularioControllerTest.php` (M)
- `SDC/docker/compose.dev.yml` (M)

Arquivos do working tree **NÃO** trazidos (fora do escopo desta feat — ficam de fora do `git add`):
- `SDC/resources/js/Pages/Pae.vue`
- `SDC/resources/js/ziggy.js` (regenerado automaticamente quando rotas mudam)
- Untracked exploratórios: `SDC/screenshot.cjs`, `SDC/test.cjs`, `SDC/test2.cjs`, `SDC/test_dashboard.cjs`
- Untracked exploratórios: `SDC/tests/Feature/Middleware/`, `SDC/tests/Feature/Octane/`, `SDC/tests/load/`

Esses ficam no working tree de `dev` original (após retornar) ou são tratados em outra branch.

---

## File Structure

**Novos arquivos:**

| Path | Responsabilidade |
|------|------------------|
| `SDC/app/Modules/Pae/Resources/EmpreendimentoResource.php` | Transforma `PaeEmpnto` + relações em JSON do contrato API |
| `SDC/app/Modules/Pae/Requests/StoreEmpreendimentoRequest.php` | Validação POST |
| `SDC/app/Modules/Pae/Requests/UpdateEmpreendimentoRequest.php` | Validação PUT/PATCH |
| `SDC/app/Modules/Pae/Services/EmpreendimentoApiService.php` | Lógica de CRUD (list paginado, find, create, update, delete) |
| `SDC/database/factories/Pae/PaeEmpntoFactory.php` | Factory para testes |
| `SDC/tests/Feature/Pae/Api/EmpreendimentoApiTest.php` | Testes Feature dos 5 endpoints (NÃO COMMITAR) |

**Arquivos a modificar:**

| Path | Mudança |
|------|---------|
| `SDC/app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php` | Trocar `App\Models\Empreendimento` -> `PaeEmpnto`; substituir mocks por chamadas ao Service; ajustar `authorizeResource`; atualizar anotações `@OA\Schema` para campos reais |
| `SDC/app/Modules/Pae/Models/PaeEmpnto.php` | Adicionar relação `latestProtocolo()` (hasOne ordered por created_at desc) — se não existir |
| `SDC/routes/api.php` | Verificar/garantir `Route::apiResource('empreendimentos', EmpreendimentoController::class)` dentro de `prefix('v1')->prefix('pae')` com `auth:sanctum` |

**Arquivos NÃO tocados (escopo limitado):**
- `SDC/routes/modules/pae.php` (rotas web continuam intactas)
- `SDC/app/Modules/Pae/Controllers/Pae*Controller.php` (controllers web)
- `SDC/resources/js/**` (frontend Vue)
- Qualquer migration (usuário decidiu: sem migration, só campos reais)

---

## Phase 0: Setup da Branch + commit preparatório das WIPs

### Task 0.1: Criar feat branch a partir de dev

**Files:** N/A (operação git)

- [ ] **Step 1: Conferir branch atual e working tree**

Run:
```powershell
git status --short
git branch --show-current
```

Expected: branch `dev`, com as modificações WIP listadas (PaeFormularioController, PaeFormularioService, PaeForm.vue, PaeFormAnexos.vue, usePaeFormulario.js, pae.php, PaeFormularioControllerTest.php, compose.dev.yml) + untracked StorePaeFormAnexoRequest.php.

- [ ] **Step 2: Criar e fazer checkout da feat branch**

Working tree migra automaticamente para a nova branch (são mudanças não commitadas).

Run:
```powershell
git switch -c feat/pae-api-empreendimentos
git status --short
```

Expected: branch `feat/pae-api-empreendimentos` ativa, mesmo status de mudanças preservado.

### Task 0.2: Commit preparatório das WIPs do PAE/Anexos

**Files:** Stage explícito dos 9 arquivos listados no pré-requisito (e nada além).

- [ ] **Step 1: Stage seletivo (não usar `git add .`)**

Run:
```powershell
git add SDC/app/Modules/Pae/Requests/StorePaeFormAnexoRequest.php
git add SDC/app/Modules/Pae/Services/PaeFormularioService.php
git add SDC/app/Modules/Pae/Controllers/PaeFormularioController.php
git add SDC/routes/modules/pae.php
git add SDC/resources/js/Components/Pae/PaeFormAnexos.vue
git add SDC/resources/js/Components/Pae/PaeForm.vue
git add SDC/resources/js/Composables/pae/usePaeFormulario.js
git add SDC/tests/Feature/Pae/PaeFormularioControllerTest.php
git add SDC/docker/compose.dev.yml
git status --short
```

Expected verificação: linhas com `A ` ou `M ` apenas para os 9 arquivos acima; arquivos como `Pae.vue`, `ziggy.js`, `screenshot.cjs`, `test*.cjs`, pastas `tests/Feature/Middleware`, `tests/Feature/Octane`, `tests/load` permanecem como `M ` ou `??` (não staged) — esse é o estado esperado.

- [ ] **Step 2: Inspecionar o diff staged uma última vez**

Run:
```powershell
git diff --cached --stat
```

Expected: 9 arquivos no resumo, contagem +/- compatível com o "+130 -65" reportado pelo IDE (margem aceitável por causa do novo arquivo untracked).

- [ ] **Step 3: Commit preparatório (mensagem agrupada, sem Co-Authored-By)**

Run:
```powershell
git commit -m "feat(pae): refatora upload de anexos do formulario e ajusta compose dev"
git log --oneline -1
```

Expected: 1 commit head `feat(pae): refatora upload de anexos do formulario e ajusta compose dev` em `feat/pae-api-empreendimentos`.

NOTA: O teste `PaeFormularioControllerTest.php` foi incluído no commit por ser uma alteração relacionada a um fluxo já existente (não é fixture do trabalho desta feat de API). A política "testes locais não-commitáveis" da memória aplica aos testes que escrevermos para validar o novo CRUD da API (Phases 1 e 4) — não aos testes já versionados do projeto.

---

## Phase 1: Resource + Factory + Relação `latestProtocolo`

**Objetivo:** Ter um shape JSON estável e testável antes de tocar Service/Controller.

### Task 1.1: Criar PaeEmpntoFactory

**Files:**
- Create: `SDC/database/factories/Pae/PaeEmpntoFactory.php`

- [ ] **Step 1: Escrever o factory**

```php
<?php

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeEmpdor;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeEmpntoFactory extends Factory
{
    protected $model = PaeEmpnto::class;

    public function definition(): array
    {
        return [
            'nome' => 'Barragem ' . $this->faker->lastName(),
            'status' => 'OPERACAO',
            'municipio_id' => Municipio::query()->inRandomOrder()->first()?->id ?? Municipio::factory(),
            'pae_empdor_id' => PaeEmpdor::factory(),
            'm_construcao' => $this->faker->randomElement(['Alteamento a Montante', 'Alteamento a Jusante', 'Linha de Centro']),
            'material' => 'Rejeitos',
            'finalidade' => 'Contencao de Rejeitos',
            'volume' => $this->faker->randomFloat(2, 1000, 50000000),
            'pop_zas' => $this->faker->numberBetween(0, 5000),
            'orgao_fisc' => 'ANM',
            'coordenador' => $this->faker->name(),
            'tel_coordenador' => $this->faker->phoneNumber(),
            'email_coord' => $this->faker->safeEmail(),
            'user_update' => 1,
        ];
    }
}
```

- [ ] **Step 2: Garantir `HasFactory` no model PaeEmpnto**

Modify `SDC/app/Modules/Pae/Models/PaeEmpnto.php` — incluir trait `HasFactory` se ausente. Implementar `newFactory()` apontando para a factory acima caso a auto-resolução por namespace não encontre:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaeEmpnto extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeEmpntoFactory::new();
    }
    // ...
}
```

### Task 1.2: Adicionar relação `latestProtocolo` no PaeEmpnto

**Files:**
- Modify: `SDC/app/Modules/Pae/Models/PaeEmpnto.php`

- [ ] **Step 1: Adicionar método de relação**

```php
public function latestProtocolo()
{
    return $this->hasOne(\App\Modules\Pae\Models\PaeProtocolo::class, 'pae_empnto_id')
        ->latestOfMany('created_at');
}
```

(`hasMany(PaeProtocolo)` já existe — só estamos adicionando o atalho `latestOfMany` para evitar N+1.)

### Task 1.3: Criar EmpreendimentoResource

**Files:**
- Create: `SDC/app/Modules/Pae/Resources/EmpreendimentoResource.php`

- [ ] **Step 1: Escrever o teste do Resource (TDD)**

Create `SDC/tests/Feature/Pae/Api/EmpreendimentoResourceTest.php` (NÃO COMMITAR):

```php
<?php

namespace Tests\Feature\Pae\Api;

use App\Http\Resources\Pae\EmpreendimentoResource;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeEmpdor;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpreendimentoResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_serializa_campos_basicos_do_empreendimento(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Itabirito', 'uf' => 'MG']);
        $empdor = PaeEmpdor::factory()->create(['nome' => 'Vale S/A', 'cnpj' => '33592510000154']);
        $emp = PaeEmpnto::factory()->create([
            'nome' => 'Barragem Sul Superior',
            'municipio_id' => $municipio->id,
            'pae_empdor_id' => $empdor->id,
            'status' => 'OPERACAO',
        ]);

        $payload = (new EmpreendimentoResource($emp->load(['municipio', 'empdor', 'latestProtocolo'])))
            ->resolve();

        $this->assertSame($emp->id, $payload['id']);
        $this->assertSame('Barragem Sul Superior', $payload['nome']);
        $this->assertSame('OPERACAO', $payload['status']);
        $this->assertSame(['id' => $municipio->id, 'nome' => 'Itabirito', 'uf' => 'MG'], $payload['municipio']);
        $this->assertSame('Vale S/A', $payload['empreendedor']['nome']);
        $this->assertArrayHasKey('coordenador', $payload);
        $this->assertNull($payload['ultimo_protocolo']);
    }

    public function test_resource_inclui_ultimo_protocolo_quando_existir(): void
    {
        $emp = PaeEmpnto::factory()->create();
        PaeProtocolo::factory()->for($emp, 'paeEmpnto')->create([
            'num_protocolo' => '2024.10.15.0081',
            'status' => 'EM_ANALISE',
        ]);

        $payload = (new EmpreendimentoResource($emp->fresh(['latestProtocolo'])))->resolve();

        $this->assertSame('2024.10.15.0081', $payload['ultimo_protocolo']['num_protocolo']);
        $this->assertSame('EM_ANALISE', $payload['ultimo_protocolo']['status']);
    }
}
```

- [ ] **Step 2: Rodar o teste — esperar FALHA (resource não existe)**

Run:
```powershell
cd SDC; php artisan test --filter=EmpreendimentoResourceTest
```

Expected: Erro de classe não encontrada ou Resource ausente.

- [ ] **Step 3: Implementar o Resource**

Create `SDC/app/Modules/Pae/Resources/EmpreendimentoResource.php`:

```php
<?php

namespace App\Http\Resources\Pae;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpreendimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'status' => $this->status,
            'mina' => $this->mina,
            'metodo_construtivo' => $this->m_construcao,
            'material' => $this->material,
            'finalidade' => $this->finalidade,
            'volume' => $this->volume !== null ? (float) $this->volume : null,
            'populacao_zas' => $this->pop_zas,
            'orgao_fiscalizador' => $this->orgao_fisc,
            'municipio' => $this->whenLoaded('municipio', fn () => [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome,
                'uf' => $this->municipio->uf,
            ]),
            'empreendedor' => $this->whenLoaded('empdor', fn () => [
                'id' => $this->empdor->id,
                'nome' => $this->empdor->nome,
                'cnpj' => $this->empdor->cnpj,
            ]),
            'coordenador' => [
                'nome' => $this->coordenador,
                'telefone' => $this->tel_coordenador,
                'email' => $this->email_coord,
            ],
            'coordenador_substituto' => [
                'nome' => $this->coordenador_sub,
                'telefone' => $this->tel_coordenador_sub,
                'email' => $this->email_coord_sub,
            ],
            'ultimo_protocolo' => $this->whenLoaded('latestProtocolo', function () {
                if (! $this->latestProtocolo) {
                    return null;
                }
                return [
                    'id' => $this->latestProtocolo->id,
                    'num_protocolo' => $this->latestProtocolo->num_protocolo,
                    'sigibar' => $this->latestProtocolo->sigibar,
                    'status' => $this->latestProtocolo->status,
                    'dt_entrada' => optional($this->latestProtocolo->dt_entrada)->toDateString(),
                    'ccpae_vencimento' => optional($this->latestProtocolo->ccpae_venc)->toDateString(),
                ];
            }),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Rodar testes — esperar PASS**

Run:
```powershell
cd SDC; php artisan test --filter=EmpreendimentoResourceTest
```

Expected: 2 testes passing.

### Task 1.4: Commit da Fase 1

- [ ] **Step 1: Stage e commit (sem o arquivo de teste)**

Run:
```powershell
git add SDC/app/Modules/Pae/Resources/EmpreendimentoResource.php SDC/database/factories/Pae/PaeEmpntoFactory.php SDC/app/Modules/Pae/Models/PaeEmpnto.php
git status --short
git commit -m "feat(pae-api): adiciona Resource, Factory e relacao latestProtocolo para Empreendimento"
```

Expected: 1 commit, sem o arquivo `tests/Feature/Pae/Api/EmpreendimentoResourceTest.php`.

---

## Phase 2: FormRequests (validação)

### Task 2.1: StoreEmpreendimentoRequest

**Files:**
- Create: `SDC/app/Modules/Pae/Requests/StoreEmpreendimentoRequest.php`

- [ ] **Step 1: Escrever teste (TDD)**

Adicionar a `SDC/tests/Feature/Pae/Api/EmpreendimentoApiTest.php` (NÃO COMMITAR):

```php
<?php

namespace Tests\Feature\Pae\Api;

use App\Modules\Pae\Models\PaeEmpdor;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmpreendimentoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_post_exige_campos_obrigatorios(): void
    {
        $this->authenticate();

        $resp = $this->postJson('/api/v1/pae/empreendimentos', []);

        $resp->assertStatus(422)
             ->assertJsonValidationErrors(['nome', 'municipio_id', 'pae_empdor_id']);
    }

    public function test_post_cria_empreendimento_valido(): void
    {
        $this->authenticate();
        $municipio = Municipio::factory()->create();
        $empdor = PaeEmpdor::factory()->create();

        $payload = [
            'nome' => 'Barragem Teste',
            'municipio_id' => $municipio->id,
            'pae_empdor_id' => $empdor->id,
            'status' => 'OPERACAO',
            'm_construcao' => 'Alteamento a Jusante',
            'material' => 'Rejeitos',
            'volume' => 1500000.50,
            'pop_zas' => 200,
        ];

        $resp = $this->postJson('/api/v1/pae/empreendimentos', $payload);

        $resp->assertStatus(201)
             ->assertJsonPath('data.nome', 'Barragem Teste')
             ->assertJsonPath('data.municipio.id', $municipio->id);

        $this->assertDatabaseHas('pae_empntos', [
            'nome' => 'Barragem Teste',
            'municipio_id' => $municipio->id,
        ]);
    }
}
```

- [ ] **Step 2: Rodar — esperar FALHA (rota retorna mock, validação não existe)**

Run:
```powershell
cd SDC; php artisan test --filter=EmpreendimentoApiTest::test_post_exige_campos_obrigatorios
```

Expected: FAIL — recebe 200/201 com mock em vez de 422.

- [ ] **Step 3: Implementar StoreEmpreendimentoRequest**

```php
<?php

namespace App\Http\Requests\Pae\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpreendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:OPERACAO,DESATIVADA,CONSTRUCAO,DESCOMISSIONAMENTO'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'pae_empdor_id' => ['required', 'integer', 'exists:pae_empdors,id'],
            'pae_coordenador_id' => ['nullable', 'integer'],
            'regiao_id' => ['nullable', 'integer'],
            'm_construcao' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'finalidade' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'pop_zas' => ['nullable', 'integer', 'min:0'],
            'orgao_fisc' => ['nullable', 'string', 'max:255'],
            'coordenador' => ['nullable', 'string', 'max:255'],
            'tel_coordenador' => ['nullable', 'string', 'max:50'],
            'email_coord' => ['nullable', 'email', 'max:255'],
            'mina' => ['nullable', 'string', 'max:255'],
            'coordenador_sub' => ['nullable', 'string', 'max:255'],
            'tel_coordenador_sub' => ['nullable', 'string', 'max:50'],
            'email_coord_sub' => ['nullable', 'email', 'max:255'],
        ];
    }
}
```

### Task 2.2: UpdateEmpreendimentoRequest

**Files:**
- Create: `SDC/app/Modules/Pae/Requests/UpdateEmpreendimentoRequest.php`

- [ ] **Step 1: Implementar (mesmas regras do Store, mas todas opcionais via `sometimes`)**

```php
<?php

namespace App\Http\Requests\Pae\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpreendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:OPERACAO,DESATIVADA,CONSTRUCAO,DESCOMISSIONAMENTO'],
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'pae_empdor_id' => ['sometimes', 'integer', 'exists:pae_empdors,id'],
            'pae_coordenador_id' => ['sometimes', 'nullable', 'integer'],
            'regiao_id' => ['sometimes', 'nullable', 'integer'],
            'm_construcao' => ['sometimes', 'nullable', 'string', 'max:255'],
            'material' => ['sometimes', 'nullable', 'string', 'max:255'],
            'finalidade' => ['sometimes', 'nullable', 'string', 'max:255'],
            'volume' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pop_zas' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'orgao_fisc' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coordenador' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tel_coordenador' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_coord' => ['sometimes', 'nullable', 'email', 'max:255'],
            'mina' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coordenador_sub' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tel_coordenador_sub' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_coord_sub' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }
}
```

(Os FormRequests não são testados isoladamente — eles serão validados pelos testes do Controller na Phase 4.)

---

## Phase 3: Service Layer

### Task 3.1: Criar EmpreendimentoApiService

**Files:**
- Create: `SDC/app/Modules/Pae/Services/EmpreendimentoApiService.php`

- [ ] **Step 1: Implementar o Service**

```php
<?php

namespace App\Modules\Pae\Services;

use App\Modules\Pae\Models\PaeEmpnto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmpreendimentoApiService
{
    private const DEFAULT_PER_PAGE = 15;
    private const RELATIONS_LIST = ['municipio', 'empdor', 'latestProtocolo'];

    public function listPaginated(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return PaeEmpnto::query()
            ->with(self::RELATIONS_LIST)
            ->when($filters['municipio_id'] ?? null, fn ($q, $v) => $q->where('municipio_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nome', 'like', "%{$v}%"))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById(int $id): PaeEmpnto
    {
        return PaeEmpnto::with(self::RELATIONS_LIST)->findOrFail($id);
    }

    public function create(array $data): PaeEmpnto
    {
        $emp = PaeEmpnto::create($data + ['user_update' => auth()->id() ?? 0]);
        return $emp->load(self::RELATIONS_LIST);
    }

    public function update(PaeEmpnto $emp, array $data): PaeEmpnto
    {
        $emp->fill($data + ['user_update' => auth()->id() ?? $emp->user_update]);
        $emp->save();
        return $emp->load(self::RELATIONS_LIST);
    }

    public function delete(PaeEmpnto $emp): void
    {
        $emp->delete();
    }
}
```

**Razão de não isolar testes do Service:** Os testes Feature do Controller (Phase 4) já exercitam todas as branches do Service via HTTP. DRY.

---

## Phase 4: Controller real + Swagger alinhado

### Task 4.1: Atualizar Controller para usar PaeEmpnto e Service

**Files:**
- Modify: `SDC/app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php`

- [ ] **Step 1: Escrever testes Feature completos (TDD)**

Estender `SDC/tests/Feature/Pae/Api/EmpreendimentoApiTest.php` com:

```php
public function test_index_lista_paginada_com_relacoes(): void
{
    $this->authenticate();
    \App\Modules\Pae\Models\PaeEmpnto::factory()->count(3)->create();

    $resp = $this->getJson('/api/v1/pae/empreendimentos');

    $resp->assertStatus(200)
         ->assertJsonStructure([
             'data' => [
                 '*' => ['id', 'nome', 'status', 'municipio', 'empreendedor', 'created_at']
             ],
             'meta' => ['current_page', 'total', 'per_page'],
             'links',
         ]);
}

public function test_show_retorna_404_quando_nao_existe(): void
{
    $this->authenticate();

    $this->getJson('/api/v1/pae/empreendimentos/99999')
         ->assertStatus(404);
}

public function test_show_retorna_empreendimento_existente(): void
{
    $this->authenticate();
    $emp = \App\Modules\Pae\Models\PaeEmpnto::factory()->create(['nome' => 'XYZ']);

    $this->getJson("/api/v1/pae/empreendimentos/{$emp->id}")
         ->assertStatus(200)
         ->assertJsonPath('data.nome', 'XYZ');
}

public function test_put_atualiza_campos_parciais(): void
{
    $this->authenticate();
    $emp = \App\Modules\Pae\Models\PaeEmpnto::factory()->create(['nome' => 'Original']);

    $this->putJson("/api/v1/pae/empreendimentos/{$emp->id}", ['nome' => 'Atualizado'])
         ->assertStatus(200)
         ->assertJsonPath('data.nome', 'Atualizado');

    $this->assertDatabaseHas('pae_empntos', ['id' => $emp->id, 'nome' => 'Atualizado']);
}

public function test_delete_remove_via_soft_delete(): void
{
    $this->authenticate();
    $emp = \App\Modules\Pae\Models\PaeEmpnto::factory()->create();

    $this->deleteJson("/api/v1/pae/empreendimentos/{$emp->id}")
         ->assertStatus(204);

    $this->assertSoftDeleted('pae_empntos', ['id' => $emp->id]);
}

public function test_index_sem_autenticacao_retorna_401(): void
{
    $this->getJson('/api/v1/pae/empreendimentos')
         ->assertStatus(401);
}
```

- [ ] **Step 2: Rodar — esperar FALHAS**

Run:
```powershell
cd SDC; php artisan test --filter=EmpreendimentoApiTest
```

Expected: múltiplas falhas (mocks atuais não batem com o contrato esperado).

- [ ] **Step 3: Reescrever o Controller**

Substituir conteúdo de `SDC/app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php`. Pontos:
- `use App\Modules\Pae\Models\PaeEmpnto;` (remover `App\Models\Empreendimento`)
- Remover `authorizeResource()` do construtor (não há Policy implementada para PaeEmpnto; manter no escopo seria expandir o trabalho)
- Substituir Request por FormRequests
- Substituir mocks por chamadas ao Service e Resource
- Manter TODAS as anotações `@OA\*` por enquanto — Schema será ajustado em Task 4.2

```php
<?php

namespace App\Http\Controllers\Api\V1\Pae;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pae\Api\StoreEmpreendimentoRequest;
use App\Http\Requests\Pae\Api\UpdateEmpreendimentoRequest;
use App\Http\Resources\Pae\EmpreendimentoResource;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmpreendimentoController extends Controller
{
    public function __construct(private readonly EmpreendimentoApiService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->listPaginated(
            filters: $request->only(['municipio_id', 'status', 'search']),
            perPage: (int) $request->input('per_page', 15),
        );

        return EmpreendimentoResource::collection($paginator)
            ->response()
            ->setStatusCode(200);
    }

    public function show(int $id): JsonResponse
    {
        $emp = $this->service->findById($id);
        return (new EmpreendimentoResource($emp))
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreEmpreendimentoRequest $request): JsonResponse
    {
        $emp = $this->service->create($request->validated());
        return (new EmpreendimentoResource($emp))
            ->additional(['message' => 'Empreendimento criado com sucesso'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEmpreendimentoRequest $request, int $id): JsonResponse
    {
        $emp = $this->service->findById($id);
        $emp = $this->service->update($emp, $request->validated());
        return (new EmpreendimentoResource($emp))
            ->additional(['message' => 'Empreendimento atualizado com sucesso'])
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(int $id): Response
    {
        $emp = $this->service->findById($id);
        $this->service->delete($emp);
        return response()->noContent();
    }
}
```

NOTA: as anotações `@OA\*` no topo do controller serão mantidas mas o Schema "Empreendimento" será ajustado em Task 4.2.

- [ ] **Step 4: Garantir registro da rota apiResource em `routes/api.php`**

Verificar e, se necessário, ajustar para:

```php
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('pae')->name('api.v1.pae.')->group(function () {
        Route::apiResource('empreendimentos', \App\Http\Controllers\Api\V1\Pae\EmpreendimentoController::class);
    });
});
```

- [ ] **Step 5: Rodar testes — esperar PASS**

Run:
```powershell
cd SDC; php artisan test --filter=EmpreendimentoApiTest
```

Expected: 8 testes passing (2 do Resource Test + 6 do Api Test = 8).

### Task 4.2: Alinhar `@OA\Schema` aos campos reais

**Files:**
- Modify: `SDC/app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php` (apenas docblock)

- [ ] **Step 1: Substituir o Schema "Empreendimento"**

```php
/**
 * @OA\Schema(
 *     schema="Empreendimento",
 *     type="object",
 *     title="Empreendimento PAE",
 *     description="Empreendimento (barragem) cadastrado no modulo PAE",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
 *     @OA\Property(property="status", type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"}, example="OPERACAO"),
 *     @OA\Property(property="mina", type="string", nullable=true, example="Mina Sul"),
 *     @OA\Property(property="metodo_construtivo", type="string", nullable=true, example="Alteamento a Jusante"),
 *     @OA\Property(property="material", type="string", nullable=true, example="Rejeitos"),
 *     @OA\Property(property="finalidade", type="string", nullable=true, example="Contencao de Rejeitos"),
 *     @OA\Property(property="volume", type="number", format="float", nullable=true, example=12500000.50),
 *     @OA\Property(property="populacao_zas", type="integer", nullable=true, example=1500),
 *     @OA\Property(property="orgao_fiscalizador", type="string", nullable=true, example="ANM"),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer", example=123),
 *         @OA\Property(property="nome", type="string", example="Itabirito"),
 *         @OA\Property(property="uf", type="string", example="MG")
 *     ),
 *     @OA\Property(property="empreendedor", type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="nome", type="string", example="Vale S/A"),
 *         @OA\Property(property="cnpj", type="string", example="33592510000154")
 *     ),
 *     @OA\Property(property="coordenador", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="telefone", type="string", nullable=true),
 *         @OA\Property(property="email", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="coordenador_substituto", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="telefone", type="string", nullable=true),
 *         @OA\Property(property="email", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="ultimo_protocolo", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=42),
 *         @OA\Property(property="num_protocolo", type="string", example="2024.10.15.0081"),
 *         @OA\Property(property="sigibar", type="string", example="SIG-001"),
 *         @OA\Property(property="status", type="string", example="EM_ANALISE"),
 *         @OA\Property(property="dt_entrada", type="string", format="date", example="2024-10-15"),
 *         @OA\Property(property="ccpae_vencimento", type="string", format="date", example="2025-10-15")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
```

- [ ] **Step 2: Atualizar `@OA\RequestBody` do POST**

```php
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nome","municipio_id","pae_empdor_id"},
 *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
 *             @OA\Property(property="status", type="string", enum={"OPERACAO","DESATIVADA","CONSTRUCAO","DESCOMISSIONAMENTO"}),
 *             @OA\Property(property="municipio_id", type="integer", example=123),
 *             @OA\Property(property="pae_empdor_id", type="integer", example=5),
 *             @OA\Property(property="m_construcao", type="string", example="Alteamento a Jusante"),
 *             @OA\Property(property="material", type="string", example="Rejeitos"),
 *             @OA\Property(property="finalidade", type="string", example="Contencao de Rejeitos"),
 *             @OA\Property(property="volume", type="number", format="float", example=12500000.50),
 *             @OA\Property(property="pop_zas", type="integer", example=1500),
 *             @OA\Property(property="orgao_fisc", type="string", example="ANM"),
 *             @OA\Property(property="coordenador", type="string"),
 *             @OA\Property(property="tel_coordenador", type="string"),
 *             @OA\Property(property="email_coord", type="string", format="email"),
 *             @OA\Property(property="mina", type="string"),
 *             @OA\Property(property="coordenador_sub", type="string"),
 *             @OA\Property(property="tel_coordenador_sub", type="string"),
 *             @OA\Property(property="email_coord_sub", type="string", format="email")
 *         )
 *     ),
```

- [ ] **Step 3: Atualizar `@OA\RequestBody` do PUT (campos opcionais)**

Mesma estrutura do POST mas sem `required={...}` e com observação de que todos campos são opcionais.

- [ ] **Step 4: Remover Parameters de filtro do `index` que não existem**

No `@OA\Get` do `index`, manter apenas `page`, `per_page`, `municipio_id`, `status`, `search` (que o Service realmente trata). Remover quaisquer outros que estavam no stub.

- [ ] **Step 5: Regenerar a documentação Swagger**

Run:
```powershell
cd SDC; php artisan l5-swagger:generate
```

Expected: regenera `storage/api-docs/api-docs.json` sem erros de parse.

- [ ] **Step 6: Smoke manual via browser**

Acessar `https://localhost:19444/api/documentation#/PAE`. Verificar:
1. Schema `Empreendimento` mostra os campos novos (mina, metodo_construtivo, etc).
2. POST RequestBody pede `pae_empdor_id` em vez de `tipo/latitude/longitude`.
3. Try-it-out com token Bearer no `GET /api/v1/pae/empreendimentos` retorna 200.

### Task 4.3: Commit da Fase 4

- [ ] **Step 1: Stage e commit**

Run:
```powershell
git add SDC/app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php SDC/app/Modules/Pae/Requests/StoreEmpreendimentoRequest.php SDC/app/Modules/Pae/Requests/UpdateEmpreendimentoRequest.php SDC/app/Modules/Pae/Services/EmpreendimentoApiService.php SDC/routes/api.php SDC/storage/api-docs/api-docs.json
git status --short
git commit -m "feat(pae-api): implementa CRUD real de Empreendimentos com Service, FormRequests e Swagger alinhado ao DB"
```

Expected: 1 commit. Confirmar que `tests/Feature/Pae/Api/*.php` NÃO está staged.

---

## Phase 5: Validação final e PR

### Task 5.1: Rodar suite completa local

- [ ] **Step 1: Suite completa**

Run:
```powershell
cd SDC; php artisan test
```

Expected: nenhuma regressão. Se algum teste pré-existente quebrar, investigar antes de prosseguir.

### Task 5.2: Push e PR

- [ ] **Step 1: Push da feat branch**

Run:
```powershell
git push -u origin feat/pae-api-empreendimentos
```

- [ ] **Step 2: Abrir PR para `dev` com merge `--no-ff`**

```powershell
gh pr create --base dev --title "feat(pae-api): CRUD de Empreendimentos com Swagger alinhado ao DB" --body @'
## Resumo
- Substitui stub mock por implementacao real do CRUD de Empreendimentos PAE
- Adiciona EmpreendimentoResource, FormRequests, EmpreendimentoApiService
- Atualiza @OA\Schema para refletir campos reais do PaeEmpnto (sem migrations)
- Swagger regenerado em /api/documentation#/PAE

## Como testar
- [ ] Acessar /api/documentation, validar Schema "Empreendimento"
- [ ] Try-it-out GET /api/v1/pae/empreendimentos com token Bearer
- [ ] POST com payload minimo (nome, municipio_id, pae_empdor_id) -> 201
- [ ] POST sem campos -> 422 com mensagens em PT
- [ ] PUT parcial -> 200
- [ ] DELETE -> 204 (soft delete)

## Notas
- Sem migrations: contrato JSON alinhado SOMENTE aos campos existentes no DB e Front
- Testes Feature foram validados localmente; nao incluidos no commit conforme politica do projeto
- Merge final esperado: "Create a merge commit" (no GitHub) para preservar --no-ff
'@
```

Expected: PR criada com a base `dev`.

---

## Resumo de Commits Esperados

1. `feat(pae): refatora upload de anexos do formulario e ajusta compose dev` (Phase 0 — WIPs preparatórias)
2. `feat(pae-api): adiciona Resource, Factory e relacao latestProtocolo para Empreendimento` (Phase 1)
3. `feat(pae-api): implementa CRUD real de Empreendimentos com Service, FormRequests e Swagger alinhado ao DB` (Phase 4 — agrupa Phases 2, 3 e 4)

(Phases 2 e 3 são preparatórias e não geram commit isolado — atomicidade lógica fica no commit da Phase 4 quando o Controller passa a usar tudo.)

---

## Checklist de Self-Review

- [x] Spec coverage: todos os 5 endpoints (GET list, GET show, POST, PUT, DELETE) têm task + teste
- [x] Sem placeholders: todo código está completo nas tasks
- [x] Type consistency: `PaeEmpnto`, `EmpreendimentoResource`, `EmpreendimentoApiService`, `StoreEmpreendimentoRequest`, `UpdateEmpreendimentoRequest` usados consistentemente
- [x] Sem emojis no código (regra de ouro 2)
- [x] DRY/SOLID (regra de ouro 4): Service isolado, Resource único, FormRequests separados por operação
- [x] Sem trailer Co-Authored-By (memória)
- [x] Commits agrupados por fase (memória)
- [x] Testes locais, não commitáveis (memória)
- [x] Branch `feat/...` partindo de `dev` (memória)
- [x] Merge `--no-ff` ao final (memória)
- [x] Sem migration (decisão explícita do usuário)
- [x] Mapeamento alinhado ao Front + DB real (decisão explícita do usuário)

---

## Riscos e Mitigações

| Risco | Mitigação |
|-------|-----------|
| `App\Models\Empreendimento` referenciado em outros lugares | Phase 4 Step 3 remove o uso. Grep antes do commit final para confirmar nenhum outro arquivo referencia esse Model fantasma. |
| Policy esperada pelo `authorizeResource` | Removido na Task 4.1 Step 3. Se autorização granular for desejada, criar PaeEmpntoPolicy em PR separado. |
| Conflito de namespace/factory auto-discovery | Task 1.1 Step 2 já força `newFactory()` no Model. |
| Documentação Swagger desatualizada em cache | Task 4.2 Step 5 regenera explicitamente. |
| `latestOfMany` não disponível em versão antiga do Laravel | Laravel 11 suporta nativamente; se falhar, fallback para `->hasMany()->latest()->limit(1)` em closure. |
