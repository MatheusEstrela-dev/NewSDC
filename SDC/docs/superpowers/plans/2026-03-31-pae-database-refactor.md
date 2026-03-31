# PAE Database Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refatorar o banco de dados do módulo PAE para que os campos de todas as 4 abas do formulário RAT sejam corretamente persistidos, substituindo `pae_form_itens` (única tabela com discriminador de categoria) por `pae_form_apontamentos` e `pae_form_conclusao` separadas, e adicionando `municipio_id`, `pae_empnto_id` e `pae_protocolo_id` (integer) em `pae_forms`.

**Architecture:** Edição direta nas migrations principais (regra consolidação). Dois novos Models (`PaeFormApontamento`, `PaeFormConclusaoItem`) substituem `PaeFormItem`. DTO, Service, Controller e composable Vue atualizados para cobrir todos os campos do frontend.

**Tech Stack:** Laravel 11, Eloquent, Inertia.js, Vue 3 Composition API, PHPUnit DatabaseTransactions

---

## Mapa de Arquivos

| Ação    | Arquivo                                                                      |
|---------|------------------------------------------------------------------------------|
| Editar  | `database/migrations/2026_02_12_130001_create_pae_forms.php`                 |
| Editar  | `database/migrations/2026_02_12_130429_create_pae_form_itens_table.php`      |
| Criar   | `app/Modules/Pae/Models/PaeFormApontamento.php`                              |
| Criar   | `app/Modules/Pae/Models/PaeFormConclusaoItem.php`                            |
| Editar  | `app/Modules/Pae/Models/PaeForm.php`                                         |
| Deletar | `app/Modules/Pae/Models/PaeFormItem.php`                                     |
| Editar  | `app/Modules/Pae/DTOs/PaeFormInfoGeraisDTO.php`                              |
| Editar  | `app/Modules/Pae/Services/PaeFormularioService.php`                          |
| Editar  | `app/Modules/Pae/Controllers/PaeFormularioController.php`                    |
| Editar  | `resources/js/composables/pae/usePaeFormulario.js`                           |
| Criar   | `tests/Feature/Pae/PaeFormularioControllerTest.php`                          |

---

## Task 1: Editar migration `pae_forms`

**Files:**
- Modify: `database/migrations/2026_02_12_130001_create_pae_forms.php`

- [ ] **Step 1: Substituir o conteúdo completo da migration**

Substituir o conteúdo do arquivo por:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pae_forms')) {
            Schema::create('pae_forms', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('pae_protocolo_id')->nullable();
                $table->uuid('uuid_publico')->unique();
                $table->string('status', 50)->default('RASCUNHO');

                $table->string('barragem_nome', 255)->nullable();
                $table->string('emp_responsavel_nome', 255)->nullable();
                $table->string('coord_pae_nome', 255)->nullable();
                $table->string('coord_pae_email', 255)->nullable();
                $table->string('coord_mun_def_civ', 255)->nullable();
                $table->string('coord_mun_compdec', 255)->nullable();
                $table->string('metodo_construtivo', 100)->nullable();
                $table->integer('num_zas')->nullable();
                $table->smallInteger('nivel_emergencia')->nullable();

                $table->text('objetivo')->nullable();
                $table->text('contexto')->nullable();

                $table->unsignedBigInteger('municipio_id')->nullable();
                $table->unsignedBigInteger('pae_empnto_id')->nullable();

                $table->foreignId('created_by')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                $table->foreignId('updated_by')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('pae_protocolo_id', 'fk_forms_protocolo')
                      ->references('id')
                      ->on('pae_protocolos')
                      ->onDelete('set null');

                $table->foreign('municipio_id', 'fk_forms_municipio')
                      ->references('id')
                      ->on('municipios')
                      ->onDelete('set null');

                $table->foreign('pae_empnto_id', 'fk_forms_empnto')
                      ->references('id')
                      ->on('pae_empntos')
                      ->onDelete('set null');

                $table->index('pae_protocolo_id', 'idx_forms_protocolo');
                $table->index('municipio_id', 'idx_forms_municipio');
                $table->index('pae_empnto_id', 'idx_forms_empnto');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_forms');
    }
};
```

- [ ] **Step 2: Confirmar que não há referência a `protocolo_id` no restante do código**

```bash
grep -r "protocolo_id" c:\Users\x24679188\Documents\Github\NewSDC\SDC\app --include="*.php" -l
```

Esperado: apenas `PaeForm.php` e arquivos que já vamos editar. Se aparecer outro, anotar.

---

## Task 2: Editar migration `pae_form_itens` — criar duas tabelas

**Files:**
- Modify: `database/migrations/2026_02_12_130429_create_pae_form_itens_table.php`

- [ ] **Step 1: Substituir conteúdo completo da migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pae_form_apontamentos')) {
            Schema::create('pae_form_apontamentos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pae_form_id')
                      ->constrained('pae_forms')
                      ->onDelete('cascade');

                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('pae_form_apontamentos')
                      ->onDelete('cascade');

                $table->string('status', 50)->default('CONFORME');
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->timestamp('updated_at')
                      ->useCurrent()
                      ->useCurrentOnUpdate();

                $table->index('pae_form_id', 'idx_apontamentos_form');
                $table->index('parent_id', 'idx_apontamentos_parent');
            });
        }

        if (!Schema::hasTable('pae_form_conclusao')) {
            Schema::create('pae_form_conclusao', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pae_form_id')
                      ->constrained('pae_forms')
                      ->onDelete('cascade');

                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('pae_form_conclusao')
                      ->onDelete('cascade');

                $table->string('status', 50)->default('CONFORME');
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->timestamp('updated_at')
                      ->useCurrent()
                      ->useCurrentOnUpdate();

                $table->index('pae_form_id', 'idx_conclusao_form');
                $table->index('parent_id', 'idx_conclusao_parent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_form_conclusao');
        Schema::dropIfExists('pae_form_apontamentos');
    }
};
```

---

## Task 3: Aplicar migrations

- [ ] **Step 1: Rodar migrate:fresh para recriar todas as tabelas**

```bash
cd c:\Users\x24679188\Documents\Github\NewSDC\SDC
php artisan migrate:fresh --seed
```

Esperado: todas as tabelas criadas sem erros. Verificar que `pae_forms`, `pae_form_apontamentos`, `pae_form_conclusao` existem e `pae_form_itens` NÃO existe.

- [ ] **Step 2: Confirmar estrutura via tinker**

```bash
php artisan tinker
>>> Schema::getColumnListing('pae_forms')
```

Esperado: array contendo `municipio_id`, `pae_empnto_id`, `pae_protocolo_id` (sem `protocolo_id`).

```bash
>>> Schema::getColumnListing('pae_form_apontamentos')
```

Esperado: `id`, `pae_form_id`, `parent_id`, `status`, `ordem`, `conteudo`, `updated_at`.

---

## Task 4: Criar model `PaeFormApontamento`

**Files:**
- Create: `app/Modules/Pae/Models/PaeFormApontamento.php`

- [ ] **Step 1: Criar o arquivo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;

class PaeFormApontamento extends Model
{
    public $timestamps = false;

    protected $table = 'pae_form_apontamentos';

    protected $fillable = [
        'pae_form_id',
        'parent_id',
        'status',
        'ordem',
        'conteudo',
    ];

    public function form()
    {
        return $this->belongsTo(PaeForm::class, 'pae_form_id');
    }
}
```

---

## Task 5: Criar model `PaeFormConclusaoItem`

**Files:**
- Create: `app/Modules/Pae/Models/PaeFormConclusaoItem.php`

- [ ] **Step 1: Criar o arquivo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;

class PaeFormConclusaoItem extends Model
{
    public $timestamps = false;

    protected $table = 'pae_form_conclusao';

    protected $fillable = [
        'pae_form_id',
        'parent_id',
        'status',
        'ordem',
        'conteudo',
    ];

    public function form()
    {
        return $this->belongsTo(PaeForm::class, 'pae_form_id');
    }
}
```

---

## Task 6: Atualizar model `PaeForm` + deletar `PaeFormItem`

**Files:**
- Modify: `app/Modules/Pae/Models/PaeForm.php`
- Delete: `app/Modules/Pae/Models/PaeFormItem.php`

- [ ] **Step 1: Substituir conteúdo de `PaeForm.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaeForm extends Model
{
    use SoftDeletes;

    protected $table = 'pae_forms';

    protected $fillable = [
        'pae_protocolo_id',
        'uuid_publico',
        'status',
        'barragem_nome',
        'emp_responsavel_nome',
        'coord_pae_nome',
        'coord_pae_email',
        'coord_mun_def_civ',
        'coord_mun_compdec',
        'metodo_construtivo',
        'num_zas',
        'nivel_emergencia',
        'objetivo',
        'contexto',
        'municipio_id',
        'pae_empnto_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $form) {
            if (empty($form->uuid_publico)) {
                $form->uuid_publico = (string) Str::uuid();
            }
        });
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'pae_protocolo_id');
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(PaeFormApontamento::class, 'pae_form_id');
    }

    public function conclusao(): HasMany
    {
        return $this->hasMany(PaeFormConclusaoItem::class, 'pae_form_id');
    }
}
```

- [ ] **Step 2: Deletar `PaeFormItem.php`**

```bash
rm "c:\Users\x24679188\Documents\Github\NewSDC\SDC\app\Modules\Pae\Models\PaeFormItem.php"
```

- [ ] **Step 3: Verificar que nenhuma outra classe importa `PaeFormItem`**

```bash
grep -r "PaeFormItem" c:\Users\x24679188\Documents\Github\NewSDC\SDC\app --include="*.php"
```

Esperado: zero resultados.

---

## Task 7: Atualizar `PaeFormInfoGeraisDTO`

**Files:**
- Modify: `app/Modules/Pae/DTOs/PaeFormInfoGeraisDTO.php`

- [ ] **Step 1: Substituir conteúdo completo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

readonly class PaeFormInfoGeraisDTO
{
    public function __construct(
        public int $userId,
        public ?string $barragem = null,
        public ?string $empreendedorRes = null,
        public ?string $coordenadorPae = null,
        public ?string $email = null,
        public ?string $coordenadorMunDefCiv = null,
        public ?string $coordenadorMunCompdec = null,
        public ?string $metodoConstrutivo = null,
        public ?int $numeroZas = null,
        public ?int $nivelEmergencia = null,
        public ?int $municipioId = null,
        public ?int $paeEmpntoId = null,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            barragem: $data['barragem'] ?? null,
            empreendedorRes: $data['empreendedor_res'] ?? null,
            coordenadorPae: $data['coordenador_pae'] ?? null,
            email: $data['email'] ?? null,
            coordenadorMunDefCiv: $data['coordenador_mun_def_civ'] ?? null,
            coordenadorMunCompdec: $data['coordenador_mun_compdec'] ?? null,
            metodoConstrutivo: $data['metodo_construtivo'] ?? null,
            numeroZas: isset($data['numero_zas']) ? (int) $data['numero_zas'] : null,
            nivelEmergencia: isset($data['nivel_emergencia']) ? (int) $data['nivel_emergencia'] : null,
            municipioId: isset($data['municipio_id']) ? (int) $data['municipio_id'] : null,
            paeEmpntoId: isset($data['pae_empnto_id']) ? (int) $data['pae_empnto_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'barragem_nome'        => $this->barragem,
            'emp_responsavel_nome' => $this->empreendedorRes,
            'coord_pae_nome'       => $this->coordenadorPae,
            'coord_pae_email'      => $this->email,
            'coord_mun_def_civ'    => $this->coordenadorMunDefCiv,
            'coord_mun_compdec'    => $this->coordenadorMunCompdec,
            'metodo_construtivo'   => $this->metodoConstrutivo,
            'num_zas'              => $this->numeroZas,
            'nivel_emergencia'     => $this->nivelEmergencia,
            'municipio_id'         => $this->municipioId,
            'pae_empnto_id'        => $this->paeEmpntoId,
            'updated_by'           => $this->userId,
        ];
    }
}
```

---

## Task 8: Refatorar `PaeFormularioService`

**Files:**
- Modify: `app/Modules/Pae/Services/PaeFormularioService.php`

- [ ] **Step 1: Substituir conteúdo completo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO;
use App\Modules\Pae\DTOs\PaeFormObjetivoDTO;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormApontamento;
use App\Modules\Pae\Models\PaeFormConclusaoItem;
use App\Modules\Shared\BaseService;

class PaeFormularioService extends BaseService
{
    public function findById(int $id): ?PaeForm
    {
        return PaeForm::with(['apontamentos', 'conclusao'])->find($id);
    }

    public function create(PaeFormInfoGeraisDTO $dto): PaeForm
    {
        return PaeForm::create(
            array_merge($dto->toArray(), [
                'status'     => 'RASCUNHO',
                'created_by' => $dto->userId,
            ])
        );
    }

    public function updateInfoGerais(PaeForm $form, PaeFormInfoGeraisDTO $dto): void
    {
        $form->update($dto->toArray());
    }

    public function updateObjetivoContexto(PaeForm $form, PaeFormObjetivoDTO $dto): void
    {
        $form->update($dto->toArray());
    }

    public function updateApontamentos(PaeForm $form, array $itens, User $user): void
    {
        $this->syncApontamentos($form, $itens);
        $form->update(['updated_by' => $user->id]);
    }

    public function updateConclusao(PaeForm $form, array $itens, User $user): void
    {
        $this->syncConclusao($form, $itens);
        $form->update(['updated_by' => $user->id]);
    }

    public function finalizar(PaeForm $form, User $user): void
    {
        $form->update([
            'status'     => 'FINALIZADO',
            'updated_by' => $user->id,
        ]);
    }

    public function formatForView(PaeForm $form): array
    {
        $apontamentos = $form->relationLoaded('apontamentos')
            ? $form->apontamentos
            : $form->apontamentos()->get();

        $conclusao = $form->relationLoaded('conclusao')
            ? $form->conclusao
            : $form->conclusao()->get();

        return [
            'id'                      => $form->id,
            'barragem'                => $form->barragem_nome,
            'municipio_id'            => $form->municipio_id,
            'pae_empnto_id'           => $form->pae_empnto_id,
            'empreendedor_res'        => $form->emp_responsavel_nome,
            'coordenador_pae'         => $form->coord_pae_nome,
            'email'                   => $form->coord_pae_email,
            'coordenador_mun_def_civ' => $form->coord_mun_def_civ,
            'coordenador_mun_compdec' => $form->coord_mun_compdec,
            'metodo_construtivo'      => $form->metodo_construtivo,
            'numero_zas'              => $form->num_zas,
            'nivel_emergencia'        => $form->nivel_emergencia,
            'objetivo'                => $form->objetivo,
            'contextualizacao'        => $form->contexto,
            'apontamentos'            => $this->buildTree($apontamentos),
            'conclusao'               => $this->buildTree($conclusao),
            'status'                  => $form->status,
        ];
    }

    private function syncApontamentos(PaeForm $form, array $itens): void
    {
        $form->apontamentos()->delete();

        $ordem = 0;
        foreach ($itens as $item) {
            $pai = PaeFormApontamento::create([
                'pae_form_id' => $form->id,
                'conteudo'    => $item['text'] ?? '',
                'ordem'       => $ordem++,
                'status'      => 'CONFORME',
            ]);

            foreach ($item['children'] ?? [] as $filho) {
                PaeFormApontamento::create([
                    'pae_form_id' => $form->id,
                    'parent_id'   => $pai->id,
                    'conteudo'    => $filho['text'] ?? '',
                    'ordem'       => $ordem++,
                    'status'      => 'CONFORME',
                ]);
            }
        }
    }

    private function syncConclusao(PaeForm $form, array $itens): void
    {
        $form->conclusao()->delete();

        $ordem = 0;
        foreach ($itens as $item) {
            $pai = PaeFormConclusaoItem::create([
                'pae_form_id' => $form->id,
                'conteudo'    => $item['text'] ?? '',
                'ordem'       => $ordem++,
                'status'      => 'CONFORME',
            ]);

            foreach ($item['children'] ?? [] as $filho) {
                PaeFormConclusaoItem::create([
                    'pae_form_id' => $form->id,
                    'parent_id'   => $pai->id,
                    'conteudo'    => $filho['text'] ?? '',
                    'ordem'       => $ordem++,
                    'status'      => 'CONFORME',
                ]);
            }
        }
    }

    private function buildTree($itens): array
    {
        return $itens->whereNull('parent_id')
            ->sortBy('ordem')
            ->values()
            ->map(function ($item) use ($itens) {
                return [
                    'id'       => $item->id,
                    'text'     => $item->conteudo,
                    'children' => $itens->where('parent_id', $item->id)
                        ->sortBy('ordem')
                        ->map(fn($f) => ['id' => $f->id, 'text' => $f->conteudo])
                        ->values()
                        ->all(),
                ];
            })->all();
    }
}
```

---

## Task 9: Atualizar `PaeFormularioController`

**Files:**
- Modify: `app/Modules/Pae/Controllers/PaeFormularioController.php`

- [ ] **Step 1: Substituir conteúdo completo**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO;
use App\Modules\Pae\DTOs\PaeFormObjetivoDTO;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Services\PaeFormularioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaeFormularioController extends Controller
{
    public function __construct(
        private readonly PaeFormularioService $service
    ) {}

    public function show(Request $request): Response
    {
        $formulario = null;

        if ($request->filled('formulario_id')) {
            $form = PaeForm::with(['apontamentos', 'conclusao'])
                ->findOrFail($request->integer('formulario_id'));
            $formulario = $this->service->formatForView($form);
        }

        return Inertia::render('Pae', [
            'municipios' => Municipio::orderBy('nome')->pluck('nome', 'id'),
            'formulario' => $formulario,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dto = PaeFormInfoGeraisDTO::fromArray(
            $this->validateInfoGerais($request),
            $request->user()->id
        );

        $form = $this->service->create($dto);

        return redirect()->route('pae.index', ['formulario_id' => $form->id])
            ->with('success', 'Informações gerais salvas.');
    }

    public function updateInfoGerais(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $dto = PaeFormInfoGeraisDTO::fromArray(
            $this->validateInfoGerais($request),
            $request->user()->id
        );

        $this->service->updateInfoGerais($paeForm, $dto);

        return back()->with('success', 'Informações gerais atualizadas.');
    }

    public function updateObjetivoContexto(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $dto = PaeFormObjetivoDTO::fromArray(
            $request->validate([
                'objetivo'         => ['nullable', 'string'],
                'contextualizacao' => ['nullable', 'string'],
            ]),
            $request->user()->id
        );

        $this->service->updateObjetivoContexto($paeForm, $dto);

        return back()->with('success', 'Objetivo e contextualização atualizados.');
    }

    public function updateApontamentos(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $request->validate(['apontamentos' => ['nullable', 'array']]);

        $this->service->updateApontamentos($paeForm, $request->input('apontamentos', []), $request->user());

        return back()->with('success', 'Apontamentos salvos.');
    }

    public function updateConclusao(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $request->validate(['conclusao' => ['nullable', 'array']]);

        $this->service->updateConclusao($paeForm, $request->input('conclusao', []), $request->user());

        return back()->with('success', 'Conclusão salva.');
    }

    public function finalizar(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $this->service->finalizar($paeForm, $request->user());

        return back()->with('success', 'Relatório finalizado.');
    }

    private function validateInfoGerais(Request $request): array
    {
        return $request->validate([
            'barragem'                => ['nullable', 'string', 'max:255'],
            'empreendedor_res'        => ['nullable', 'string', 'max:255'],
            'coordenador_pae'         => ['nullable', 'string', 'max:255'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'coordenador_mun_def_civ' => ['nullable', 'string', 'max:255'],
            'coordenador_mun_compdec' => ['nullable', 'string', 'max:255'],
            'metodo_construtivo'      => ['nullable', 'string', 'max:100'],
            'numero_zas'              => ['nullable', 'integer'],
            'nivel_emergencia'        => ['nullable', 'integer'],
            'municipio_id'            => ['nullable', 'integer', 'exists:municipios,id'],
            'pae_empnto_id'           => ['nullable', 'integer', 'exists:pae_empntos,id'],
        ]);
    }
}
```

---

## Task 10: Atualizar composable `usePaeFormulario.js`

**Files:**
- Modify: `resources/js/composables/pae/usePaeFormulario.js`

- [ ] **Step 1: Adicionar `pae_empnto_id` ao estado `infoGerais`**

Substituir o bloco `infoGerais` (linhas 20–31) por:

```js
const infoGerais = ref({
    barragem:                formulario?.barragem                ?? empreendimento?.nome               ?? '',
    municipio_id:            formulario?.municipio_id             ?? empreendimento?.municipio_id       ?? '',
    pae_empnto_id:           formulario?.pae_empnto_id            ?? empreendimento?.id                 ?? '',
    coordenador_pae:         formulario?.coordenador_pae          ?? empreendimento?.coordenador        ?? '',
    email:                   formulario?.email                    ?? empreendimento?.email_coord        ?? '',
    coordenador_mun_def_civ: formulario?.coordenador_mun_def_civ  ?? '',
    coordenador_mun_compdec: formulario?.coordenador_mun_compdec  ?? '',
    empreendedor_res:        formulario?.empreendedor_res         ?? empreendimento?.empreendedor?.nome ?? '',
    metodo_construtivo:      formulario?.metodo_construtivo       ?? empreendimento?.m_construcao      ?? '',
    numero_zas:              formulario?.numero_zas               ?? empreendimento?.pop_zas            ?? '',
    nivel_emergencia:        formulario?.nivel_emergencia         ?? '',
});
```

---

## Task 11: Escrever e rodar testes de feature

**Files:**
- Create: `tests/Feature/Pae/PaeFormularioControllerTest.php`

- [ ] **Step 1: Criar o arquivo de teste**

```php
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
        $this->assertNotNull($municipio, 'Precisa de pelo menos um município no banco.');

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

    public function test_update_apontamentos_persiste_na_tabela_correta(): void
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

    public function test_update_conclusao_persiste_na_tabela_correta(): void
    {
        $form = PaeForm::factory()->create(['status' => 'RASCUNHO']);

        $this->actingAsAnalista()
            ->put("/pae/formulario/{$form->id}/conclusao", [
                'conclusao' => [
                    ['text' => 'Conclusão 1', 'children' => []],
                ],
            ]);

        $this->assertDatabaseHas('pae_form_conclusao', [
            'pae_form_id' => $form->id,
            'conteudo'    => 'Conclusão 1',
        ]);

        $this->assertDatabaseMissing('pae_form_apontamentos', ['pae_form_id' => $form->id]);
    }

    public function test_format_for_view_retorna_municipio_id(): void
    {
        $municipio = Municipio::first();
        $form = PaeForm::factory()->create(['municipio_id' => $municipio->id]);

        $response = $this->actingAsAnalista()
            ->get("/pae?formulario_id={$form->id}");

        $response->assertInertia(fn ($page) =>
            $page->where('formulario.municipio_id', $municipio->id)
        );
    }
}
```

- [ ] **Step 2: Criar factory `PaeForm` se não existir**

Verificar se existe:

```bash
find "c:\Users\x24679188\Documents\Github\NewSDC\SDC\database\factories" -name "PaeForm*"
```

Se não existir, criar `database/factories/PaeFormFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Pae\Models\PaeForm;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeFormFactory extends Factory
{
    protected $model = PaeForm::class;

    public function definition(): array
    {
        return [
            'status'       => 'RASCUNHO',
            'barragem_nome' => $this->faker->words(3, true),
        ];
    }
}
```

Adicionar `HasFactory` ao model `PaeForm` se não tiver:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaeForm extends Model
{
    use HasFactory, SoftDeletes;
    // ...
}
```

- [ ] **Step 3: Rodar os testes**

```bash
cd c:\Users\x24679188\Documents\Github\NewSDC\SDC
php artisan test tests/Feature/Pae/PaeFormularioControllerTest.php --verbose
```

Esperado: 4 testes passando.

- [ ] **Step 4: Commit**

```bash
git add \
  database/migrations/2026_02_12_130001_create_pae_forms.php \
  database/migrations/2026_02_12_130429_create_pae_form_itens_table.php \
  app/Modules/Pae/Models/PaeFormApontamento.php \
  app/Modules/Pae/Models/PaeFormConclusaoItem.php \
  app/Modules/Pae/Models/PaeForm.php \
  app/Modules/Pae/DTOs/PaeFormInfoGeraisDTO.php \
  app/Modules/Pae/Services/PaeFormularioService.php \
  app/Modules/Pae/Controllers/PaeFormularioController.php \
  resources/js/composables/pae/usePaeFormulario.js \
  tests/Feature/Pae/PaeFormularioControllerTest.php \
  docs/superpowers/specs/2026-03-31-pae-database-refactor-design.md \
  docs/superpowers/plans/2026-03-31-pae-database-refactor.md
git commit -m "refactor(pae): separar pae_form_itens em apontamentos/conclusao, adicionar municipio_id e pae_empnto_id"
```

---

## Self-Review

**Spec coverage:**
- [x] `municipio_id` adicionado em migration, DTO, service, controller
- [x] `pae_empnto_id` adicionado em migration, DTO, service, controller, composable
- [x] `protocolo_id` string → `pae_protocolo_id` integer FK
- [x] `pae_form_itens` substituída por `pae_form_apontamentos` + `pae_form_conclusao`
- [x] `syncItens()` → `syncApontamentos()` + `syncConclusao()`
- [x] `formatForView()` retorna `municipio_id`
- [x] `show()` usa `with(['apontamentos', 'conclusao'])` em vez de `with('itens')`
- [x] Frontend composable inclui `pae_empnto_id`

**Sem TBDs, sem placeholders. Código completo em cada task.**
