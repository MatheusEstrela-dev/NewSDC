# PMDA — Plano de Implementação do Módulo (Plano Municipal de Defesa Agropecuária)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recomendado) ou superpowers:executing-plans para implementar este plano fase-a-fase. Steps usam checkbox (`- [ ]`) para tracking.

**Goal:** Portar o módulo PMDA do legado (`sdc` + `gestaocedec`) para `app/Modules/Pmda/` no NewSDC, em 6 fases sequenciais que entregam valor isoladamente (Fundação → Core → Comunidades/Representantes → Pontos/ISS → Mensagens/Anexos/Histórico → Análise CEDEC/Dashboard/Integração TDAP).

**Architecture:** DDD personalizado leve, fluxo `Request → DTO → Controller → Service → Model` (espelha `app/Modules/Pae` e `app/Modules/Decretacoes`; **sem** Domain/Infrastructure/Repository). Service é o único com regra de negócio e acesso ao Eloquent, estendendo `app/Modules/Shared/BaseService.php`. Frontend Inertia + Vue 3 Atomic Design. Máquina de estados consolidada num único enum `PmdaStatus`.

**Tech Stack:** Laravel 12 / PHP 8.3 (FrankenPHP/Octane), PostgreSQL (`localhost:5433`, db `sdc`) com `varchar`+`CHECK` para enums, Inertia.js + Vue 3 + Tailwind + Ziggy, Spatie Permissions, Spatie Media Library (anexos), Pest/PHPUnit.

**Spec de referência:** `docs/superpowers/specs/2026-06-15-pmda-module-design.md`
**Plano-molde:** `docs/superpowers/plans/2026-05-11-tdap-migration.md`

**Fluxo de branches:** cada fase sai de `feat/pmda-<fase>` criado a partir da última `dev`; fecha e mergeia antes da próxima.

---

## Estrutura de Arquivos (visão geral)

```
app/Modules/Pmda/
├── Controllers/   PmdaPlanoController, ComunidadeController, RepresentanteController,
│                  PlanoPontoController, MensagemController, AnexoController,
│                  PmdaAnaliseController, PmdaDashboardController
├── Requests/      Store/Update{Plano,Comunidade,Representante,Mensagem}Request, EnviarAnaliseRequest, AprovarRequest
├── DTOs/          PmdaPlanoDTO, ComunidadeDTO, RepresentanteDTO, MensagemDTO, AnaliseDTO
├── Services/      PmdaPlanoService, PmdaCopiaService, ComunidadeService, RepresentanteService,
│                  PlanoPontoService, MensagemService, AnexoService, PmdaAnaliseService, PmdaDashboardService
├── Models/        PmdaPlano, PmdaComunidade, PmdaRepresentante, PmdaPlanoPonto,
│                  PmdaMensagem, PmdaAnexo, PmdaAlteracao, PmdaComentario
├── Resources/     PmdaPlanoResource, PmdaPlanoListResource, ComunidadeResource, RepresentanteResource, MensagemResource
├── Enums/         PmdaStatus
├── Observers/     PmdaPlanoObserver (gera protocolo, recalcula status)
├── Mail/          PmdaAprovadoMail, PmdaPedidoAlteracaoMail  [Fase 5]
└── PmdaServiceProvider.php

resources/js/
├── Pages/Pmda/{Index,Create,Edit,Show}.vue, Pmda/Analise/{Index,Show}.vue, Pmda/Dashboard.vue
├── Templates/Pmda/{PmdaCrudTemplate,PmdaDetailTemplate}.vue
├── Components/{Atoms,Molecules,Sections,Organisms}/Pmda/*
└── composables/pmda/{usePmda,useComunidades,useAnalise}.js

database/migrations/  2026_06_15_*_create_pmda_*_table.php
routes/modules/pmda.php
config/permissions.php  (bloco PMDA)
config/app.php          (PmdaServiceProvider)
.claude/pmda/main.py    (módulo do kernel)
tests/Feature/Pmda/*
```

---

## Fase 0 — Fundação do Módulo

**Branch:** `feat/pmda-fundacao` (a partir de `dev`).

### Modelo de negócio
Plantar o esqueleto do módulo: provider, rotas, permissões, item de menu, módulo do kernel `.claude`. Nenhuma migration. Sem este passo as próximas fases nascem sem fundação de namespace/rota/permissão.

### Backend

- [ ] **Step 0.1: Criar estrutura de pastas do módulo**

```bash
cd C:\Users\x24679188\Documents\Github\NewSDC\SDC
mkdir -p app/Modules/Pmda/{Controllers,Requests,DTOs,Services,Models,Resources,Enums,Observers,Mail}
```

- [ ] **Step 0.2: Criar `app/Modules/Pmda/PmdaServiceProvider.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda;

use Illuminate\Support\ServiceProvider;

class PmdaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services como singletons (stateless). Preenchidos fase a fase:
        // $this->app->singleton(\App\Modules\Pmda\Services\PmdaPlanoService::class);
    }

    public function boot(): void
    {
        // Observers registrados na Fase 1:
        // \App\Modules\Pmda\Models\PmdaPlano::observe(\App\Modules\Pmda\Observers\PmdaPlanoObserver::class);
    }
}
```

- [ ] **Step 0.3: Registrar o provider em `config/app.php`**

Em `config/app.php`, no array `providers`, logo após a linha `App\Modules\Decretacoes\DecretacoesServiceProvider::class,` (atual linha 172), adicionar:

```php
        App\Modules\Pmda\PmdaServiceProvider::class,
```

- [ ] **Step 0.4: Criar arquivo de rotas `routes/modules/pmda.php`**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('pmda')->name('pmda.')->group(function () {
    // Endpoints adicionados em cada fase.
});
```

- [ ] **Step 0.5: Incluir as rotas em `routes/web.php`**

Em `routes/web.php`, junto aos outros `require __DIR__ . '/modules/...'` dentro do grupo `auth` (próximo à linha 155, onde está o `pae.php`), adicionar:

```php
    require __DIR__ . '/modules/pmda.php';
```

- [ ] **Step 0.6: Adicionar bloco de permissões em `config/permissions.php`**

No array `modules`, após o bloco `'TDAP' => [ ... ]`, colar (formato real, igual a PAE/TDAP):

```php
        'PMDA' => [
            // PMDA - Plano Municipal de Defesa Agropecuaria
            'Dashboard' => [
                'view' => 'pmda.dashboard.view',
            ],
            'Planos' => [
                'view'   => 'pmda.planos.view',
                'create' => 'pmda.planos.create',
                'edit'   => 'pmda.planos.edit',
                'delete' => 'pmda.planos.delete',
                'copiar' => 'pmda.planos.copiar',
                'export' => 'pmda.planos.export',
            ],
            'Comunidades' => [
                'view'   => 'pmda.comunidades.view',
                'create' => 'pmda.comunidades.create',
                'edit'   => 'pmda.comunidades.edit',
                'delete' => 'pmda.comunidades.delete',
            ],
            'Representantes' => [
                'view'   => 'pmda.representantes.view',
                'create' => 'pmda.representantes.create',
                'edit'   => 'pmda.representantes.edit',
                'delete' => 'pmda.representantes.delete',
            ],
            'Pontos' => [
                'view'   => 'pmda.pontos.view',
                'create' => 'pmda.pontos.create',
                'edit'   => 'pmda.pontos.edit',
                'delete' => 'pmda.pontos.delete',
            ],
            'Analise' => [
                'view'            => 'pmda.analise.view',
                'enviar'          => 'pmda.analise.enviar',
                'aprovar'         => 'pmda.analise.aprovar',
                'arquivar'        => 'pmda.analise.arquivar',
                'pedir_alteracao' => 'pmda.analise.pedir_alteracao',
            ],
            'Mensagens' => [
                'view'   => 'pmda.mensagens.view',
                'create' => 'pmda.mensagens.create',
            ],
            'Anexos' => [
                'view'   => 'pmda.anexos.view',
                'create' => 'pmda.anexos.create',
                'delete' => 'pmda.anexos.delete',
            ],
        ],
```

- [ ] **Step 0.7: Sincronizar permissões e limpar config cache**

Run (no container indicado pelo kernel):
```bash
docker exec newsdc_frankenphp_local php artisan config:clear
docker exec newsdc_frankenphp_local php artisan permission:cache-reset
```
(Se houver seeder/command de sync de permissões — confirmar `php artisan db:seed --class=PermissionSeeder` ou equivalente; só rodar o que existir.)

- [ ] **Step 0.8: Criar módulo do kernel `.claude/pmda/main.py`**

Espelhar o contrato dos outros módulos `.claude/*/main.py` (atributo `MODULE` com `name`, `purpose`, `triggers`, `read_order`, `actions`, e funções `collect`/`render`/`main`). Conteúdo mínimo:

```python
#!/usr/bin/env python3
"""Modulo .claude do PMDA: contexto e gatilhos para o kernel."""

MODULE = {
    "name": "pmda",
    "purpose": "Plano Municipal de Defesa Agropecuaria: planos, comunidades, representantes, pontos de captacao, analise CEDEC e integracao TDAP.",
    "triggers": ["pmda", "plano municipal", "defesa agropecuaria", "comunidade", "representante", "captacao"],
    "read_order": [
        "docs/superpowers/specs/2026-06-15-pmda-module-design.md",
        "docs/superpowers/plans/2026-06-15-pmda-module.md",
    ],
    "actions": [
        {"slug": "spec", "description": "Abrir o spec de design do PMDA", "command": "cat SDC/docs/superpowers/specs/2026-06-15-pmda-module-design.md"},
        {"slug": "plano", "description": "Abrir o plano de implementacao do PMDA", "command": "cat SDC/docs/superpowers/plans/2026-06-15-pmda-module.md"},
    ],
}


def collect(repo_root, app_root, claude_root):
    return {"name": MODULE["name"], "purpose": MODULE["purpose"], "files": []}


def render(data):
    return f"# {data['name']}\n{data['purpose']}"


def main():
    print(render({"name": MODULE["name"], "purpose": MODULE["purpose"]}))


if __name__ == "__main__":
    main()
```

- [ ] **Step 0.9: Verificar detecção do kernel**

Run:
```bash
python .claude/kernel.py --detect "modelar modulo pmda comunidades"
```
Expected: saída inclui `### pmda` nos "Modulos relevantes".

### Frontend

- [ ] **Step 0.10: Criar pastas Atomic Design + Templates base**

```bash
mkdir -p resources/js/Pages/Pmda resources/js/Templates/Pmda
mkdir -p resources/js/Components/{Atoms,Molecules,Sections,Organisms}/Pmda
mkdir -p resources/js/composables/pmda
```

Criar `resources/js/Templates/Pmda/PmdaCrudTemplate.vue`:
```vue
<template>
  <div class="p-4 sm:p-6">
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ title }}</h1>
      <slot name="actions" />
    </header>
    <slot name="filters" />
    <slot />
    <footer class="mt-4"><slot name="pagination" /></footer>
  </div>
</template>
<script setup>defineProps({ title: { type: String, required: true } });</script>
```

Criar `resources/js/Templates/Pmda/PmdaDetailTemplate.vue`:
```vue
<template>
  <div class="p-4 sm:p-6 space-y-6">
    <header class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">{{ title }}</h1>
      <slot name="actions" />
    </header>
    <slot name="iss" />
    <slot name="municipio" />
    <slot name="pontos" />
    <slot name="comunidades" />
    <slot name="representantes" />
    <slot name="acoes" />
    <slot name="anexos" />
  </div>
</template>
<script setup>defineProps({ title: { type: String, required: true } });</script>
```

- [ ] **Step 0.11: Inserir item no menu lateral**

Em `resources/js/Layouts/AuthenticatedLayout.vue` (ou o `Sidebar` que ele usa), adicionar entrada `PMDA` apontando para `route('pmda.planos.index')`, com guard de permissão `pmda.planos.view` (seguir o padrão de gating dos outros itens do menu).

### Verificação de fim de fase

- [ ] `docker exec newsdc_frankenphp_local php artisan route:list --name=pmda` lista só o prefixo `/pmda` (sem filhas).
- [ ] `python .claude/kernel.py --detect "pmda"` casa o módulo `pmda`.
- [ ] `npm run build` compila sem erro de import.
- [ ] **Commit:**
```bash
git add app/Modules/Pmda routes/modules/pmda.php routes/web.php config/app.php config/permissions.php resources/js/Templates/Pmda resources/js/Layouts .claude/pmda
git commit -m "✨ feat(pmda): fundacao do modulo (provider, rotas, permissoes, kernel)"
```

---

## Fase 1 — PMDA Core (Plano + Máquina de Estados)

**Branch:** `feat/pmda-core` (a partir de `dev`).

### Modelo de negócio
O **Plano** (`pmda_planos`) é o agregado raiz. Nasce em `RASCUNHO`, recebe `protocolo` único (`{id}{YYYYMMDD}`), pertence a um município. Máquina de estados unificada (`PmdaStatus`). Regra de cópia: `data > 2021-04-03` **e** `status ∉ {RASCUNHO,COMPLETO,EM_ANALISE,APROVADO}`; e não criar novo se já existir `RASCUNHO` do mesmo município.

### DB

- [ ] **Step 1.1: Migration `pmda_planos`**

Criar `database/migrations/2026_06_15_000001_create_pmda_planos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_planos', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 30)->nullable()->unique();
            $table->unsignedBigInteger('municipio_id');
            $table->string('status', 20)->default('RASCUNHO')->index();
            $table->timestamp('data')->nullable();           // data de criacao do plano
            $table->text('acoes')->nullable();
            $table->unsignedInteger('qtd_caminhao')->nullable();
            $table->unsignedInteger('pop_at_municipio')->nullable();
            $table->boolean('pedido_altera')->default(false);
            $table->boolean('alterar_com')->default(false);
            $table->string('resp_homolog', 100)->nullable();
            $table->timestamp('dt_analise')->nullable();
            $table->timestamp('dt_ultima_alteracao')->nullable();
            $table->timestamp('data_aprov')->nullable();
            $table->string('resp_estado', 100)->nullable();
            $table->timestamp('dt_estado')->nullable();
            // Dados ISS/Municipio (confirmar quais migram vs derivam de municipios — spec §6 Q1)
            $table->boolean('cobra_iss')->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->decimal('aliquota_iss', 5, 2)->nullable();
            $table->string('resp_cob_iss', 30)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('municipio_id')->references('id')->on('municipios');
        });

        DB::statement(
            "ALTER TABLE pmda_planos ADD CONSTRAINT chk_pmda_status CHECK (status IN ".
            "('RASCUNHO','COMPLETO','EM_ANALISE','APROVADO','ATENDIDO','ARQUIVADO','ANULADO','CANCELADO','ENCERRADO'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_planos');
    }
};
```

- [ ] **Step 1.2: Rodar a migration**

Run: `docker exec newsdc_frankenphp_local php artisan migrate`
Expected: `Migrated: ...create_pmda_planos_table`.

### Backend — Enum (TDD)

- [ ] **Step 1.3: Escrever teste do enum `PmdaStatus`**

Criar `tests/Unit/Modules/Pmda/PmdaStatusTest.php`:
```php
<?php

namespace Tests\Unit\Modules\Pmda;

use App\Modules\Pmda\Enums\PmdaStatus;
use Tests\TestCase;

class PmdaStatusTest extends TestCase
{
    public function test_transicao_valida_rascunho_para_completo(): void
    {
        $this->assertTrue(PmdaStatus::RASCUNHO->podeTransicionarPara(PmdaStatus::COMPLETO));
    }

    public function test_transicao_invalida_rascunho_para_aprovado(): void
    {
        $this->assertFalse(PmdaStatus::RASCUNHO->podeTransicionarPara(PmdaStatus::APROVADO));
    }

    public function test_estados_que_permitem_copia(): void
    {
        $this->assertFalse(PmdaStatus::EM_ANALISE->permiteCopia());
        $this->assertTrue(PmdaStatus::CANCELADO->permiteCopia());
    }
}
```

- [ ] **Step 1.4: Rodar o teste e confirmar que falha**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PmdaStatusTest`
Expected: FAIL (`Class "App\Modules\Pmda\Enums\PmdaStatus" not found`).

- [ ] **Step 1.5: Implementar o enum `PmdaStatus`**

Criar `app/Modules/Pmda/Enums/PmdaStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Enums;

enum PmdaStatus: string
{
    case RASCUNHO   = 'RASCUNHO';
    case COMPLETO   = 'COMPLETO';
    case EM_ANALISE = 'EM_ANALISE';
    case APROVADO   = 'APROVADO';
    case ATENDIDO   = 'ATENDIDO';
    case ARQUIVADO  = 'ARQUIVADO';
    case ANULADO    = 'ANULADO';
    case CANCELADO  = 'CANCELADO';
    case ENCERRADO  = 'ENCERRADO';

    /** Transições permitidas (origem => destinos). */
    public function transicoes(): array
    {
        return match ($this) {
            self::RASCUNHO   => [self::COMPLETO, self::ANULADO, self::CANCELADO],
            self::COMPLETO   => [self::RASCUNHO, self::EM_ANALISE, self::ANULADO, self::CANCELADO],
            self::EM_ANALISE => [self::APROVADO, self::RASCUNHO, self::ARQUIVADO, self::ANULADO, self::CANCELADO],
            self::APROVADO   => [self::ATENDIDO, self::CANCELADO],
            default          => [], // ATENDIDO, ARQUIVADO, ANULADO, CANCELADO, ENCERRADO sao terminais
        };
    }

    public function podeTransicionarPara(PmdaStatus $destino): bool
    {
        return in_array($destino, $this->transicoes(), true);
    }

    public function permiteCopia(): bool
    {
        return ! in_array($this, [self::RASCUNHO, self::COMPLETO, self::EM_ANALISE, self::APROVADO], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'Em Edição',
            self::COMPLETO   => 'Completo',
            self::EM_ANALISE => 'Em Análise',
            self::APROVADO   => 'Aprovado',
            self::ATENDIDO   => 'Atendido',
            self::ARQUIVADO  => 'Arquivado',
            self::ANULADO    => 'Anulado',
            self::CANCELADO  => 'Cancelado',
            self::ENCERRADO  => 'Encerrado',
        };
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'bg-slate-100 text-slate-800',
            self::COMPLETO   => 'bg-blue-100 text-blue-800',
            self::EM_ANALISE => 'bg-indigo-100 text-indigo-800',
            self::APROVADO   => 'bg-green-100 text-green-800',
            self::ATENDIDO   => 'bg-emerald-100 text-emerald-800',
            self::ARQUIVADO  => 'bg-yellow-100 text-yellow-800',
            self::ANULADO, self::CANCELADO => 'bg-red-100 text-red-800',
            self::ENCERRADO  => 'bg-gray-200 text-gray-700',
        };
    }
}
```

- [ ] **Step 1.6: Rodar o teste e confirmar que passa**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PmdaStatusTest`
Expected: PASS (3 testes).

- [ ] **Step 1.7: Commit do enum**
```bash
git add app/Modules/Pmda/Enums/PmdaStatus.php tests/Unit/Modules/Pmda/PmdaStatusTest.php
git commit -m "✨ feat(pmda): enum PmdaStatus com maquina de estados"
```

### Backend — Model, DTO, Observer

- [ ] **Step 1.8: Model anêmico `PmdaPlano`**

Criar `app/Modules/Pmda/Models/PmdaPlano.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmdaPlano extends Model
{
    use SoftDeletes;

    protected $table = 'pmda_planos';

    protected $fillable = [
        'protocolo', 'municipio_id', 'status', 'data', 'acoes', 'qtd_caminhao',
        'pop_at_municipio', 'pedido_altera', 'alterar_com', 'resp_homolog', 'dt_analise',
        'dt_ultima_alteracao', 'data_aprov', 'resp_estado', 'dt_estado',
        'cobra_iss', 'num_lei_iss', 'aliquota_iss', 'resp_cob_iss', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'          => PmdaStatus::class,
        'data'            => 'datetime',
        'dt_analise'      => 'datetime',
        'data_aprov'      => 'datetime',
        'dt_estado'       => 'datetime',
        'dt_ultima_alteracao' => 'datetime',
        'pedido_altera'   => 'boolean',
        'alterar_com'     => 'boolean',
        'cobra_iss'       => 'boolean',
        'aliquota_iss'    => 'decimal:2',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class, 'municipio_id');
    }
}
```
> Confirmar o FQCN real do model de município (ex.: `App\Models\Municipio` ou `App\Modules\Compdec\Models\Municipio`) com `grep -rn "class Municipio" app/` antes de fixar o import.

- [ ] **Step 1.9: Observer que gera o protocolo**

Criar `app/Modules/Pmda/Observers/PmdaPlanoObserver.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Observers;

use App\Modules\Pmda\Models\PmdaPlano;

class PmdaPlanoObserver
{
    public function creating(PmdaPlano $plano): void
    {
        if (empty($plano->data)) {
            $plano->data = now();
        }
    }

    public function created(PmdaPlano $plano): void
    {
        if (empty($plano->protocolo)) {
            // Formato legado: {id}{YYYYMMDD}
            $plano->protocolo = $plano->id . $plano->data->format('Ymd');
            $plano->saveQuietly();
        }
    }
}
```

Registrar em `PmdaServiceProvider::boot()`:
```php
\App\Modules\Pmda\Models\PmdaPlano::observe(\App\Modules\Pmda\Observers\PmdaPlanoObserver::class);
```

- [ ] **Step 1.10: DTO `PmdaPlanoDTO`**

Criar `app/Modules/Pmda/DTOs/PmdaPlanoDTO.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\DTOs;

readonly class PmdaPlanoDTO
{
    public function __construct(
        public int $municipioId,
        public int $userId,
        public ?string $acoes = null,
        public ?int $qtdCaminhao = null,
        public ?int $popAtMunicipio = null,
        public ?bool $cobraIss = null,
        public ?string $numLeiIss = null,
        public ?float $aliquotaIss = null,
        public ?string $respCobIss = null,
    ) {}

    public static function fromArray(array $data, int $userId, int $municipioId): self
    {
        return new self(
            municipioId: $municipioId,
            userId: $userId,
            acoes: $data['acoes'] ?? null,
            qtdCaminhao: isset($data['qtd_caminhao']) ? (int) $data['qtd_caminhao'] : null,
            popAtMunicipio: isset($data['pop_at_municipio']) ? (int) $data['pop_at_municipio'] : null,
            cobraIss: isset($data['cobra_iss']) ? (bool) $data['cobra_iss'] : null,
            numLeiIss: $data['num_lei_iss'] ?? null,
            aliquotaIss: isset($data['aliquota_iss']) ? (float) $data['aliquota_iss'] : null,
            respCobIss: $data['resp_cob_iss'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'municipio_id'     => $this->municipioId,
            'created_by'       => $this->userId,
            'acoes'            => $this->acoes,
            'qtd_caminhao'     => $this->qtdCaminhao,
            'pop_at_municipio' => $this->popAtMunicipio,
            'cobra_iss'        => $this->cobraIss,
            'num_lei_iss'      => $this->numLeiIss,
            'aliquota_iss'     => $this->aliquotaIss,
            'resp_cob_iss'     => $this->respCobIss,
        ], static fn ($v) => $v !== null);
    }
}
```

### Backend — Service (TDD com a regra de cópia)

- [ ] **Step 1.11: Teste do `PmdaPlanoService` (criação + regra de rascunho único)**

Criar `tests/Feature/Pmda/PmdaPlanoServiceTest.php`:
```php
<?php

namespace Tests\Feature\Pmda;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Services\PmdaPlanoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PmdaPlanoServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_criar_gera_plano_em_rascunho_com_protocolo(): void
    {
        $service = app(PmdaPlanoService::class);
        $plano = $service->criar(municipioId: $this->municipioId(), userId: 1, data: []);

        $this->assertEquals(PmdaStatus::RASCUNHO, $plano->status);
        $this->assertNotEmpty($plano->protocolo);
        $this->assertStringStartsWith((string) $plano->id, $plano->protocolo);
    }

    public function test_nao_cria_segundo_rascunho_no_mesmo_municipio(): void
    {
        $service = app(PmdaPlanoService::class);
        $mun = $this->municipioId();
        $service->criar($mun, 1, []);

        $this->expectException(\DomainException::class);
        $service->criar($mun, 1, []);
    }

    private function municipioId(): int
    {
        // Helper: cria/recupera um municipio de teste (ajustar ao factory/seed real do projeto)
        return \App\Models\Municipio::query()->value('id') ?? \App\Models\Municipio::create(['nome' => 'Teste'])->id;
    }
}
```
> Ajustar `municipioId()` ao factory real de município do projeto (`grep -rn "Municipio::factory\|class Municipio"`).

- [ ] **Step 1.12: Rodar e confirmar falha**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PmdaPlanoServiceTest`
Expected: FAIL (service inexistente).

- [ ] **Step 1.13: Implementar `PmdaPlanoService`**

Criar `app/Modules/Pmda/Services/PmdaPlanoService.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class PmdaPlanoService extends BaseService
{
    public function criar(int $municipioId, int $userId, array $data): PmdaPlano
    {
        $existeRascunho = PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->where('status', PmdaStatus::RASCUNHO->value)
            ->exists();

        if ($existeRascunho) {
            throw new \DomainException('Já existe um PMDA em edição para este município.');
        }

        return PmdaPlano::create(array_merge($data, [
            'municipio_id' => $municipioId,
            'status'       => PmdaStatus::RASCUNHO,
            'created_by'   => $userId,
        ]));
    }

    public function atualizar(PmdaPlano $plano, array $data, int $userId): PmdaPlano
    {
        $plano->update(array_merge($data, [
            'updated_by'          => $userId,
            'dt_ultima_alteracao' => now(),
        ]));

        return $plano->refresh();
    }

    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        return $this->paginate($query, $perPage);
    }
}
```

Registrar singleton em `PmdaServiceProvider::register()`:
```php
$this->app->singleton(\App\Modules\Pmda\Services\PmdaPlanoService::class);
```
> Validar a assinatura real de `applyFilters`/`paginate` em `app/Modules/Shared/BaseService.php` e ajustar a chamada se necessário.

- [ ] **Step 1.14: Rodar e confirmar que passa**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PmdaPlanoServiceTest`
Expected: PASS.

- [ ] **Step 1.15: `PmdaCopiaService` (TDD da regra de cópia)**

Teste em `tests/Feature/Pmda/PmdaCopiaServiceTest.php`:
```php
<?php

namespace Tests\Feature\Pmda;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Services\PmdaCopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PmdaCopiaServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_nao_copia_plano_anterior_a_2021_04_03(): void
    {
        $plano = $this->plano(PmdaStatus::CANCELADO, '2021-01-01');
        $this->expectException(\DomainException::class);
        app(PmdaCopiaService::class)->copiar($plano, userId: 1);
    }

    public function test_nao_copia_status_em_analise(): void
    {
        $plano = $this->plano(PmdaStatus::EM_ANALISE, '2024-01-01');
        $this->expectException(\DomainException::class);
        app(PmdaCopiaService::class)->copiar($plano, userId: 1);
    }

    public function test_copia_cria_novo_rascunho(): void
    {
        $plano = $this->plano(PmdaStatus::CANCELADO, '2024-01-01');
        $copia = app(PmdaCopiaService::class)->copiar($plano, userId: 1);

        $this->assertEquals(PmdaStatus::RASCUNHO, $copia->status);
        $this->assertNotEquals($plano->id, $copia->id);
    }

    private function plano(PmdaStatus $status, string $data): PmdaPlano
    {
        $mun = \App\Models\Municipio::query()->value('id') ?? \App\Models\Municipio::create(['nome' => 'T'])->id;
        return PmdaPlano::create(['municipio_id' => $mun, 'status' => $status, 'data' => $data]);
    }
}
```

Implementar `app/Modules/Pmda/Services/PmdaCopiaService.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use Illuminate\Support\Carbon;

class PmdaCopiaService
{
    private const DATA_MINIMA_COPIA = '2021-04-03';

    public function copiar(PmdaPlano $origem, int $userId): PmdaPlano
    {
        if ($origem->data->lte(Carbon::parse(self::DATA_MINIMA_COPIA))) {
            throw new \DomainException('PMDA anterior a 03/04/2021 não pode ser copiado.');
        }
        if (! $origem->status->permiteCopia()) {
            throw new \DomainException('Status atual não permite cópia.');
        }

        $copia = $origem->replicate(['protocolo', 'status', 'data', 'data_aprov', 'dt_analise']);
        $copia->status     = PmdaStatus::RASCUNHO;
        $copia->data       = now();
        $copia->protocolo  = null; // regerado pelo Observer
        $copia->created_by = $userId;
        $copia->save();

        // Fase 2 estende este service para duplicar comunidades + representantes.
        return $copia;
    }
}
```
Registrar singleton no provider. Rodar `--filter=PmdaCopiaServiceTest` → PASS. Commit.

### Backend — Requests, Resources, Controller, Rotas

- [ ] **Step 1.16: FormRequests**

`app/Modules/Pmda/Requests/StorePmdaPlanoRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
        ];
    }
}
```

`app/Modules/Pmda/Requests/UpdatePmdaPlanoRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'acoes'            => ['nullable', 'string'],
            'qtd_caminhao'     => ['nullable', 'integer', 'min:0'],
            'pop_at_municipio' => ['nullable', 'integer', 'min:0'],
            'cobra_iss'        => ['nullable', 'boolean'],
            'num_lei_iss'      => ['nullable', 'string', 'max:30'],
            'aliquota_iss'     => ['nullable', 'numeric', 'between:0,99.99'],
            'resp_cob_iss'     => ['nullable', 'string', 'max:30'],
        ];
    }
}
```

- [ ] **Step 1.17: Resources**

`PmdaPlanoListResource` e `PmdaPlanoResource` em `app/Modules/Pmda/Resources/`. Exemplo do List:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'protocolo'     => $this->protocolo,
            'municipio'     => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'status'        => $this->status->value,
            'status_label'  => $this->status->getLabel(),
            'status_color'  => $this->status->getColorClass(),
            'data'          => $this->data?->toIso8601String(),
            'pode_copiar'   => $this->status->permiteCopia(),
        ];
    }
}
```

- [ ] **Step 1.18: Controller fino `PmdaPlanoController`**

`app/Modules/Pmda/Controllers/PmdaPlanoController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Requests\StorePmdaPlanoRequest;
use App\Modules\Pmda\Requests\UpdatePmdaPlanoRequest;
use App\Modules\Pmda\Resources\PmdaPlanoListResource;
use App\Modules\Pmda\Resources\PmdaPlanoResource;
use App\Modules\Pmda\Services\PmdaCopiaService;
use App\Modules\Pmda\Services\PmdaPlanoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PmdaPlanoController extends Controller
{
    public function __construct(
        private readonly PmdaPlanoService $service,
        private readonly PmdaCopiaService $copia,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Pmda/Index', [
            'planos' => PmdaPlanoListResource::collection(
                $this->service->listar($request->only(['municipio_id', 'status']))
            ),
            'filtros' => $request->only(['municipio_id', 'status']),
        ]);
    }

    public function store(StorePmdaPlanoRequest $request): RedirectResponse
    {
        $plano = $this->service->criar(
            municipioId: (int) $request->validated('municipio_id'),
            userId: (int) $request->user()->id,
            data: [],
        );

        return to_route('pmda.planos.edit', $plano->id)->with('success', 'PMDA criado.');
    }

    public function edit(PmdaPlano $plano): Response
    {
        return Inertia::render('Pmda/Edit', [
            'plano' => new PmdaPlanoResource($plano->load('municipio')),
        ]);
    }

    public function update(UpdatePmdaPlanoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->atualizar($plano, $request->validated(), (int) $request->user()->id);

        return back()->with('success', 'PMDA atualizado.');
    }

    public function copiar(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $copia = $this->copia->copiar($plano, (int) $request->user()->id);

        return to_route('pmda.planos.edit', $copia->id)->with('success', 'Cópia criada.');
    }
}
```

- [ ] **Step 1.19: Rotas em `routes/modules/pmda.php`**

```php
use App\Modules\Pmda\Controllers\PmdaPlanoController;

Route::prefix('pmda')->name('pmda.')->group(function () {
    Route::prefix('planos')->name('planos.')->group(function () {
        Route::get('/', [PmdaPlanoController::class, 'index'])->name('index')->middleware('can:pmda.planos.view');
        Route::post('/', [PmdaPlanoController::class, 'store'])->name('store')->middleware('can:pmda.planos.create');
        Route::get('/{plano}/edit', [PmdaPlanoController::class, 'edit'])->name('edit')->middleware('can:pmda.planos.edit');
        Route::put('/{plano}', [PmdaPlanoController::class, 'update'])->name('update')->middleware('can:pmda.planos.edit');
        Route::post('/{plano}/copiar', [PmdaPlanoController::class, 'copiar'])->name('copiar')->middleware('can:pmda.planos.copiar');
    });
});
```
Adicionar binding `Route::model('plano', \App\Modules\Pmda\Models\PmdaPlano::class);` no topo se o binding implícito não resolver pelo nome `plano`.

- [ ] **Step 1.20: Teste de Feature do Controller (permissão + criação via HTTP)**

`tests/Feature/Pmda/PmdaPlanoControllerTest.php` cobrindo: usuário sem `pmda.planos.create` recebe 403; com permissão, `POST /pmda/planos` persiste em `pmda_planos` e redireciona para edit. Seguir o padrão de `tests/Feature/Pae/PaeFormularioControllerTest.php` (helper `actingAsAnalista` criando as Permissions e `givePermissionTo`). Rodar → PASS.

### Frontend (Atomic Design)

- [ ] **Step 1.21: Atom `PmdaStatusBadge.vue`**

`resources/js/Components/Atoms/Pmda/PmdaStatusBadge.vue` — recebe `status` + `label` + `color`, renderiza badge. Sem estado.

- [ ] **Step 1.22: Organism `PmdaPlanosTable.vue`**

Recebe `planos` (paginado), renderiza tabela com protocolo, município, `PmdaStatusBadge`, e ActionButtons (`edit`, `copiar` quando `pode_copiar`). Emite `copiar`/`edit`. Usa `ActionButton` com `module="pmda"`.

- [ ] **Step 1.23: Page `Pages/Pmda/Index.vue`** (sem HTML estrutural)
```vue
<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaCrudTemplate from '@/Templates/Pmda/PmdaCrudTemplate.vue';
import PmdaPlanosTable from '@/Components/Organisms/Pmda/PmdaPlanosTable.vue';

defineOptions({ layout: AuthenticatedLayout });
defineProps({ planos: Object, filtros: Object });

function copiar(id) { router.post(route('pmda.planos.copiar', id)); }
</script>

<template>
  <Head title="PMDA — Planos" />
  <PmdaCrudTemplate title="Planos Municipais de Defesa Agropecuária">
    <PmdaPlanosTable :planos="planos" @copiar="copiar" @edit="(id) => router.visit(route('pmda.planos.edit', id))" />
  </PmdaCrudTemplate>
</template>
```

- [ ] **Step 1.24: Page `Pages/Pmda/Edit.vue`** — monta `PmdaDetailTemplate` com a aba ISS (Organism `IssForm` simples nesta fase; demais abas preenchidas nas fases 2-3).

### Verificação de fim de fase

- [ ] `docker exec newsdc_frankenphp_local php artisan test --filter=Pmda` verde.
- [ ] Criar PMDA pela UI gera protocolo e abre a edição.
- [ ] Tentar criar 2º rascunho no mesmo município retorna erro tratado.
- [ ] Botão "Copiar" só aparece quando `pode_copiar` é true.
- [ ] **Commit + merge** do `feat/pmda-core` na `dev`.

---

## Fase 2 — Comunidades + Representantes

**Branch:** `feat/pmda-comunidades` (a partir de `dev`).

### Modelo de negócio
Cada **comunidade** do plano referencia `comunidade_id`, `municipio_id`, `ponto_id`, coordenadas, trechos (km) e `pop_atendida`. Cada comunidade exige **3 representantes**. O plano só atinge `COMPLETO` com ≥1 comunidade e 3 representantes por comunidade. Comunidade não pode estar em dois planos com status ativo (`RASCUNHO,COMPLETO,EM_ANALISE,APROVADO,ATENDIDO`).

### DB
- [ ] **Step 2.1: Migration `pmda_comunidades`** — `id`, `pmda_plano_id` (FK cascade), `comunidade_id`, `municipio_id`, `ponto_id` (nullable), `latitude`/`longitude` (string), `trecho_pav` (decimal 8,2), `trecho_n_pav` (decimal 8,2), `pop_atendida` (int), timestamps, softDeletes. Unique `(pmda_plano_id, comunidade_id)`.
- [ ] **Step 2.2: Migration `pmda_representantes`** — `id`, `pmda_comunidade_id` (FK cascade), `nome`, `tel`, `email` (nullable), `cpf` (nullable), `whatsapp` (nullable), timestamps, softDeletes.

### Backend
- [ ] **Step 2.3: Models** `PmdaComunidade` (relations: `plano` belongsTo, `representantes` hasMany), `PmdaRepresentante` (`comunidade` belongsTo). Adicionar em `PmdaPlano`: `comunidades(): HasMany`.
- [ ] **Step 2.4: DTOs** `ComunidadeDTO`, `RepresentanteDTO` (readonly + fromArray/toArray).
- [ ] **Step 2.5: `ComunidadeService`** com método `adicionar` que valida duplicidade de comunidade em planos ativos (lança `DomainException`) e, ao final, chama `recalcularStatus`.
- [ ] **Step 2.6 (TDD): regra de auto-COMPLETO.** Teste: plano com 1 comunidade e 3 representantes → após `recalcularStatus`, status vira `COMPLETO`; com 2 representantes → continua `RASCUNHO`. Implementar `PmdaPlanoService::recalcularStatus(PmdaPlano $plano)`:
```php
public function recalcularStatus(PmdaPlano $plano): void
{
    if (in_array($plano->status, [PmdaStatus::EM_ANALISE, PmdaStatus::APROVADO, PmdaStatus::ATENDIDO], true)) {
        return; // nao mexe em planos ja submetidos
    }
    $totComunidades = $plano->comunidades()->count();
    $totRepresentantesOk = $plano->comunidades()
        ->withCount('representantes')->get()
        ->every(fn ($c) => $c->representantes_count >= 3);

    $novo = ($totComunidades > 0 && $totRepresentantesOk) ? PmdaStatus::COMPLETO : PmdaStatus::RASCUNHO;
    if ($plano->status !== $novo) {
        $plano->update(['status' => $novo]);
    }
}
```
- [ ] **Step 2.7: `RepresentanteService`** (CRUD; ao salvar/remover chama `recalcularStatus`).
- [ ] **Step 2.8: Controllers + Requests + Resources + Rotas** (`pmda.comunidades.*`, `pmda.representantes.*` com middleware `can:`).
- [ ] **Step 2.9: Estender `PmdaCopiaService`** para duplicar comunidades + representantes na cópia (completa o TODO da Fase 1).

### Frontend
- [ ] **Step 2.10:** Organisms `ComunidadesTable.vue`, `ComunidadeForm.vue`, `RepresentantesInline.vue` (3 linhas obrigatórias); Section `ComunidadesSection.vue` no `PmdaDetailTemplate`; composable `useComunidades.js`. Badge de progresso "x/3 representantes".

### Verificação
- [ ] Testes `--filter=Comunidade` e `--filter=Representante` verdes.
- [ ] Adicionar 1 comunidade + 3 reps muda status para `COMPLETO` na UI.
- [ ] Comunidade duplicada em plano ativo bloqueada com mensagem.
- [ ] Commit + merge na `dev`.

---

## Fase 3 — Pontos de Captação + Dados ISS/Município/COMPDEC

**Branch:** `feat/pmda-pontos` (a partir de `dev`).

### Modelo de negócio
Reaproveita a tabela `pmda_pontos` (já existe, criada para o TDAP — validar schema antes, spec §6 Q2). O plano vincula pontos de captação (`pmda_plano_ponto`) e consolida dados de ISS, Município e COMPDEC (abas do formulário legado).

### DB
- [ ] **Step 3.1:** Validar schema atual de `pmda_pontos` (`\d pmda_pontos` no psql `localhost:5433/sdc`); ajustar colunas se faltarem (`nome`, `tipo`, `latitude`, `longitude`, `capacidade`, `municipio_id`).
- [ ] **Step 3.2: Migration `pmda_plano_ponto`** — pivot `id`, `pmda_plano_id` (FK cascade), `ponto_id` (FK `pmda_pontos`), timestamps. Unique `(pmda_plano_id, ponto_id)`.

### Backend
- [ ] **Step 3.3: Model `PmdaPlanoPonto`** + relation `pontos()` belongsToMany em `PmdaPlano`.
- [ ] **Step 3.4: `PlanoPontoService`** (vincular/desvincular ponto).
- [ ] **Step 3.5:** Estender `UpdatePmdaPlanoRequest`/`PmdaPlanoService` para os campos de Município/COMPDEC que ficam em `pmda_planos` (confirmar §6 Q1 contra `municipios` para não duplicar — só persistir no plano o que for específico do plano).
- [ ] **Step 3.6:** Controller `PlanoPontoController` + rotas `pmda.pontos.*`.

### Frontend
- [ ] **Step 3.7:** Sections `PontosSection.vue` (mapa Leaflet com pontos), `IssForm.vue`, `MunicipioForm.vue`, `CompdecForm.vue` no `PmdaDetailTemplate`. Reusar componente de mapa existente do projeto se houver (`grep -rn "leaflet" resources/js`).

### Verificação
- [ ] Vincular/desvincular ponto persiste em `pmda_plano_ponto`.
- [ ] Validação ISS (alíquota 0-99.99, lei obrigatória quando `cobra_iss`) testada.
- [ ] Commit + merge na `dev`.

---

## Fase 4 — Mensagens, Anexos, Histórico, Comentários

**Branch:** `feat/pmda-mensagens` (a partir de `dev`).

### Modelo de negócio
Comunicação CEDEC↔município (`pmda_mensagens`, status 0 não lida/1 lida), anexos via **Spatie Media Library**, auditoria de edições (`pmda_alteracoes`) e comentários internos (`pmda_comentarios`).

### DB
- [ ] **Step 4.1: Migration `pmda_mensagens`** — `id`, `pmda_plano_id` (FK), `usuario_id`, `municipio_id`, `msg` (text), `status` (smallint default 0), `tp_mensagem` (string), `protocolo` (string), `dt_envio`, `dt_leitura` (nullable), timestamps.
- [ ] **Step 4.2: Migration `pmda_alteracoes`** — `id`, `pmda_plano_id` (FK), `editor` (string), `alteracao` (text), `dt_alteracao`, timestamps.
- [ ] **Step 4.3: Migration `pmda_comentarios`** — `id`, `pmda_plano_id` (FK), `texto` (text), `created_by`, timestamps.
- [ ] **Step 4.4: Anexos:** usar Spatie Media Library no `PmdaPlano` (implementar `HasMedia` + `InteractsWithMedia`, coleção `anexos`). Confirmar se há tabela `media` no projeto (`\dt media`); se não, publicar a migration do pacote.

### Backend
- [ ] **Step 4.5: `MensagemService`** (`enviar`, `marcarLida`) + Model `PmdaMensagem`.
- [ ] **Step 4.6: `AnexoService`** (`anexar`, `remover` via Media Library; validar mimes jpg/png/pdf/doc/docx/xls/xlsx, máx 2MB — espelha legado).
- [ ] **Step 4.7: Histórico** — Observer/serviço que grava em `pmda_alteracoes` nas edições relevantes (preferir o trait de auditoria existente — §6 Q3; `grep -rn "LogsModelChanges\|trait.*Audit" app/`).
- [ ] **Step 4.8:** Controllers `MensagemController`, `AnexoController` + rotas `pmda.mensagens.*`, `pmda.anexos.*`.

### Frontend
- [ ] **Step 4.9:** Organisms `MensagensThread.vue`, `AnexosUploader.vue`, `HistoricoTimeline.vue`; Sections correspondentes no detalhe.

### Verificação
- [ ] Enviar mensagem e marcá-la lida (status 0→1, `dt_leitura` preenchida) testado.
- [ ] Upload de anexo válido e rejeição de mime/tamanho inválido testados.
- [ ] Edição grava linha em `pmda_alteracoes`.
- [ ] Commit + merge na `dev`.

---

## Fase 5 — Análise/Aprovação CEDEC + Dashboard + Integração TDAP

**Branch:** `feat/pmda-analise` (a partir de `dev`).

### Modelo de negócio
CEDEC analisa PMDAs `EM_ANALISE`, e transiciona para `APROVADO` (grava `data_aprov`, `resp_homolog`), `ARQUIVADO`, ou volta a `RASCUNHO` com `pedido_altera=true`. PMDA `APROVADO` fica disponível para gerar cronograma TDAP. Envio (`EM_ANALISE`) é ação do COMPDEC sobre plano `COMPLETO`.

### Backend
- [ ] **Step 5.1: `PmdaAnaliseService`** com métodos `enviar`, `aprovar`, `arquivar`, `pedirAlteracao` — cada um valida a transição via `PmdaStatus::podeTransicionarPara()` (lança `DomainException` se inválida) e grava os campos de auditoria (`resp_homolog`, `dt_analise`, `data_aprov`, `resp_estado`, `dt_estado`, `pedido_altera`).
- [ ] **Step 5.2 (TDD): transições.** Testes: `COMPLETO --enviar--> EM_ANALISE` ok; `RASCUNHO --enviar-->` falha; `EM_ANALISE --aprovar--> APROVADO` grava `data_aprov`; `EM_ANALISE --pedirAlteracao--> RASCUNHO` com `pedido_altera=true`; transição inválida lança exceção.
```php
public function aprovar(PmdaPlano $plano, int $userId, string $respNome): PmdaPlano
{
    $this->transicionar($plano, PmdaStatus::APROVADO);
    $plano->update([
        'data_aprov'   => now(),
        'resp_homolog' => $respNome,
        'updated_by'   => $userId,
    ]);
    return $plano->refresh();
}

private function transicionar(PmdaPlano $plano, PmdaStatus $destino): void
{
    if (! $plano->status->podeTransicionarPara($destino)) {
        throw new \DomainException("Transição {$plano->status->value} → {$destino->value} não permitida.");
    }
    $plano->update(['status' => $destino]);
}
```
- [ ] **Step 5.3: Mailables** `PmdaAprovadoMail`, `PmdaPedidoAlteracaoMail` (disparados em `aprovar`/`pedirAlteracao`; espelhar `CronogramaAtivadoMail` do TDAP).
- [ ] **Step 5.4: Controllers** `PmdaAnaliseController` (painel CEDEC, ações) + `PmdaDashboardController` (KPIs); rotas `pmda.analise.*`, `pmda.dashboard.view`.
- [ ] **Step 5.5: Integração TDAP** — expor query `PmdaPlanoService::aprovadosParaTdap(int $municipioId)` (planos `APROVADO`), consumida pelo módulo Tdap onde hoje há `CronogramaController@pmda` (legado). Confirmar o ponto de consumo atual no `app/Modules/Tdap/` e ligar via service (sem acoplar models entre módulos — Tdap chama o service público do Pmda).

### Frontend
- [ ] **Step 5.6:** `Pages/Pmda/Analise/Index.vue` (fila de análise CEDEC), `Analise/Show.vue` (homologação com botões aprovar/arquivar/pedir alteração gated por `pmda.analise.*`), `Pages/Pmda/Dashboard.vue` (KPIs: planos por status, aprovados no mês). Organisms `PainelAnaliseTable`, `HomologacaoForm`, `KpiCard`.

### Verificação
- [ ] Fluxo completo: criar → completar → enviar → aprovar gera `data_aprov` e e-mail.
- [ ] Transição inválida bloqueada (teste verde).
- [ ] PMDA aprovado aparece na listagem consumida pelo cronograma TDAP.
- [ ] `docker exec newsdc_frankenphp_local php artisan test --filter=Pmda` totalmente verde.
- [ ] Commit + merge na `dev`.

---

## Self-Review (cobertura do spec)

- **§2.1 Entidades** → Models nas Fases 1-4 (planos, comunidades, representantes, plano_ponto, mensagens, anexos, alteracoes, comentarios). ✔
- **§2.2 Campos do plano** → migration 1.1 + extensões Fase 3 (ISS/Município). Q1 sinalizada como step de confirmação. ✔
- **§2.3 Máquina de estados** → enum `PmdaStatus` (1.5) + transições testadas (1.3, 5.2). ✔
- **§2.4 Regra de cópia** → `PmdaCopiaService` + testes (1.15), duplicação de filhos na Fase 2 (2.9). ✔
- **§2.5 Protocolo** → Observer (1.9) + teste (1.11). ✔
- **§2.6 Comunidades/Representantes (3/comunidade)** → Fase 2 (2.5-2.7). ✔
- **§2.7 Mensagens/Anexos/Histórico/Comentários** → Fase 4. ✔
- **§3 Arquitetura (Request→DTO→Controller→Service→Model)** → estrutura de pastas Fase 0 + camadas nas Fases 1-5. ✔
- **§4 Permissões (slugs)** → bloco PMDA (0.6) + middleware `can:` em cada rota. ✔
- **§5 Fases** → 6 fases (0-5) cobertas. ✔
- **§6 Questões abertas** → tratadas como steps de confirmação (1.8 município FQCN, 3.1 pmda_pontos, 4.7 trait auditoria, 1.13 BaseService). ✔

**Questões abertas que dependem do ambiente (resolver no 1º step de cada fase):**
1. FQCN real do model `Municipio` (Fase 1, Step 1.8).
2. Schema atual de `pmda_pontos` (Fase 3, Step 3.1).
3. Trait de auditoria equivalente ao `LogsModelChanges` (Fase 4, Step 4.7).
4. Assinatura real de `BaseService::applyFilters/paginate` (Fase 1, Step 1.13).
5. Existência da tabela `media` do Spatie (Fase 4, Step 4.4).
6. Nome do container Docker / comando de teste (kernel sugere `newsdc_frankenphp_local`; confirmar com `docker ps`).
