# PAE — Numero do Protocolo no Header Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exibir o numero do protocolo PAE (ex: `10.04.2026.003`) e o stepper de status no cabecalho da pagina `/pae/protocolo`, propagando os dados do backend ate os componentes Vue.

**Architecture:** O backend passa um objeto `protocolo` (`id`, `num_protocolo`, `status`) via Inertia props. A `Pae.vue` recebe e distribui para `PaeHeader` (exibe numero + badge) e `PaeBreadcrumb` (stepper de status ja existente). O redirect apos criacao vai direto ao formulario em vez da lista.

**Tech Stack:** Laravel 11 + Inertia.js + Vue 3 (Composition API) + Tailwind CSS + PHPUnit (feature tests)

---

## Mapa de Arquivos

| Arquivo | Acao | Responsabilidade |
|---|---|---|
| `app/Modules/Pae/Services/PaeFormularioService.php` | Modificar | Expor `pae_protocolo_id` em `formatForView` |
| `app/Modules/Pae/Controllers/PaeFormularioController.php` | Modificar | Resolver e passar `protocolo` ao Inertia |
| `app/Modules/Pae/Controllers/PaeProtocoloController.php` | Modificar | Redirect apos store para o formulario |
| `resources/js/Pages/Pae.vue` | Modificar | Aceitar prop `protocolo`, repassar para filhos, incluir `PaeBreadcrumb` |
| `resources/js/Components/Pae/PaeHeader.vue` | Modificar | Exibir `num_protocolo` e badge de status |
| `tests/Feature/Pae/PaeFormularioControllerTest.php` | Modificar | Adicionar teste para prop `protocolo` no show |

---

## Task 1: PaeFormularioService — expor pae_protocolo_id

**Files:**
- Modify: `app/Modules/Pae/Services/PaeFormularioService.php:110-128`

- [ ] **Step 1: Adicionar `pae_protocolo_id` ao array de `formatForView`**

Arquivo: `app/Modules/Pae/Services/PaeFormularioService.php`

Localizar o metodo `formatForView` (linha ~100). O array retornado termina em `'status' => $form->status`. Adicionar uma linha antes de fechar o array:

```php
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
            'pae_protocolo_id'        => $form->pae_protocolo_id,
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
```

- [ ] **Step 2: Commit**

```bash
cd SDC
git add app/Modules/Pae/Services/PaeFormularioService.php
git commit -m "feat(pae): expor pae_protocolo_id em formatForView"
```

---

## Task 2: PaeFormularioController — resolver e passar protocolo

**Files:**
- Modify: `app/Modules/Pae/Controllers/PaeFormularioController.php:25-55`

- [ ] **Step 1: Escrever o teste antes de alterar o controller**

Arquivo: `tests/Feature/Pae/PaeFormularioControllerTest.php`

Adicionar apos os metodos existentes:

```php
public function test_show_com_protocolo_id_retorna_prop_protocolo(): void
{
    $perm = Permission::firstOrCreate(
        ['name' => 'pae.empreendimentos.view', 'guard_name' => 'web']
    );
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo($perm);

    $protocolo = \App\Modules\Pae\Models\PaeProtocolo::create([
        'num_protocolo' => '13.04.2026.001',
        'status'        => 'novo',
        'user_id'       => $user->id,
        'created_by'    => $user->id,
        'dt_entrada'    => now()->toDateString(),
        'arquivado'     => false,
    ]);

    $response = $this->actingAs($user)
        ->get("/pae/protocolo?protocolo_id={$protocolo->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('Pae')
        ->has('protocolo')
        ->where('protocolo.id', $protocolo->id)
        ->where('protocolo.num_protocolo', '13.04.2026.001')
        ->where('protocolo.status', 'novo')
    );
}

public function test_show_sem_protocolo_id_retorna_protocolo_null(): void
{
    $perm = Permission::firstOrCreate(
        ['name' => 'pae.empreendimentos.view', 'guard_name' => 'web']
    );
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo($perm);

    $response = $this->actingAs($user)->get('/pae/protocolo');

    $response->assertInertia(fn ($page) => $page
        ->component('Pae')
        ->where('protocolo', null)
    );
}
```

- [ ] **Step 2: Rodar os testes para confirmar que falham**

```bash
cd SDC
php artisan test tests/Feature/Pae/PaeFormularioControllerTest.php --filter="test_show_com_protocolo_id_retorna_prop_protocolo|test_show_sem_protocolo_id_retorna_protocolo_null"
```

Esperado: FAIL — `protocolo` nao existe nas props Inertia ainda.

- [ ] **Step 3: Alterar `PaeFormularioController::show`**

Arquivo: `app/Modules/Pae/Controllers/PaeFormularioController.php`

Substituir o metodo `show` inteiro:

```php
public function show(Request $request): Response
{
    $formulario = null;
    $protocolo  = null;

    if ($request->filled('formulario_id')) {
        $form = PaeForm::with(['apontamentos', 'conclusao'])
            ->findOrFail($request->integer('formulario_id'));
        $formulario = $this->service->formatForView($form);
    } elseif ($request->filled('protocolo_id')) {
        $form = PaeForm::with(['apontamentos', 'conclusao'])
            ->where('pae_protocolo_id', $request->integer('protocolo_id'))
            ->first();

        if ($form) {
            $formulario = $this->service->formatForView($form);
        } else {
            $prot = PaeProtocolo::find($request->integer('protocolo_id'));
            if ($prot) {
                $formulario = [
                    'pae_protocolo_id' => $prot->id,
                ];
            }
        }
    }

    // Resolver protocolo a partir do formulario ou do query param
    $protocoloId = $request->integer('protocolo_id')
        ?: ($formulario['pae_protocolo_id'] ?? null);

    if ($protocoloId) {
        $prot = PaeProtocolo::find($protocoloId);
        if ($prot) {
            $protocolo = [
                'id'            => $prot->id,
                'num_protocolo' => $prot->num_protocolo,
                'status'        => $prot->status?->value,
            ];
        }
    }

    return Inertia::render('Pae', [
        'municipios' => Municipio::orderBy('nome')->pluck('nome', 'id'),
        'formulario' => $formulario,
        'protocolo'  => $protocolo,
    ]);
}
```

- [ ] **Step 4: Rodar os testes para confirmar que passam**

```bash
php artisan test tests/Feature/Pae/PaeFormularioControllerTest.php
```

Esperado: todos PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Pae/Controllers/PaeFormularioController.php \
        tests/Feature/Pae/PaeFormularioControllerTest.php
git commit -m "feat(pae): controller passa protocolo para Inertia em show"
```

---

## Task 3: PaeProtocoloController — redirect apos store

**Files:**
- Modify: `app/Modules/Pae/Controllers/PaeProtocoloController.php:76-79`

- [ ] **Step 1: Alterar o redirect em `store`**

Arquivo: `app/Modules/Pae/Controllers/PaeProtocoloController.php`

Localizar o metodo `store`. Substituir apenas a linha de redirect:

```php
// ANTES
return redirect()->route('pae.protocolos.index')
    ->with('success', "Protocolo {$protocolo->num_protocolo} criado com sucesso.");

// DEPOIS
return redirect()->route('pae.index', ['protocolo_id' => $protocolo->id])
    ->with('success', "Protocolo {$protocolo->num_protocolo} criado com sucesso.");
```

- [ ] **Step 2: Commit**

```bash
git add app/Modules/Pae/Controllers/PaeProtocoloController.php
git commit -m "feat(pae): redirect apos criacao vai para formulario com protocolo_id"
```

---

## Task 4: PaeHeader.vue — exibir numero e badge

**Files:**
- Modify: `resources/js/Components/Pae/PaeHeader.vue`

- [ ] **Step 1: Adicionar prop `protocolo` e bloco de exibicao**

Substituir o conteudo completo do arquivo:

```vue
<template>
  <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 md:mb-8 gap-4">
    <div class="flex-1 min-w-0">
      <div v-if="protocolo" class="flex items-center gap-2 mb-2">
        <span class="text-xs font-mono font-semibold text-slate-400 uppercase tracking-wider">
          #{{ protocolo.num_protocolo }}
        </span>
        <span
          v-if="protocolo.status"
          :class="['px-2 py-0.5 rounded text-xs font-bold border', statusClass]"
        >
          {{ statusLabel }}
        </span>
      </div>
      <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight break-words">Ficha do Empreendimento</h1>
      <p class="text-base sm:text-lg md:text-xl text-blue-400 font-light mt-1 flex flex-wrap items-center gap-2">
        <span class="break-words">{{ empreendimento.nome }}</span>
        <span
          :class="[
            'px-2 py-0.5 rounded text-xs font-bold border whitespace-nowrap',
            getNivelEmergenciaClass(empreendimento.nivelEmergencia),
          ]"
        >
          Nível de Emergência {{ empreendimento.nivelEmergencia }}
        </span>
      </p>
    </div>
    <div class="text-left md:text-right flex-shrink-0">
      <span class="text-xs text-slate-500 uppercase tracking-wider font-bold block mb-1">
        Última Atualização
      </span>
      <span class="text-sm text-slate-600 dark:text-slate-300 font-mono break-all md:break-normal">{{ lastUpdate }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDateTime } from '../../utils/dateFormatter';

const STATUS_LABELS = {
    novo:               'Novo',
    entrada_processo:   'Entrada do Processo',
    criacao_sdc:        'Criação no SDC',
    gerenciamento:      'Gerenciamento',
    notificacao:        'Notificação',
    analise:            'Análise',
    aprovado:           'Aprovado',
    reprovado:          'Reprovado',
    ccpae:              'CCPAE',
    ativo_3_anos:       'Ativo (3 anos)',
    suspenso:           'Suspenso',
    revogado:           'Revogado',
    esperar_tratativa:  'Aguardando Tratativa',
    dilacao:            'Dilatação',
};

const STATUS_CLASSES = {
    novo:               'bg-slate-500/10 text-slate-400 border-slate-500/20',
    entrada_processo:   'bg-blue-500/10 text-blue-400 border-blue-500/20',
    criacao_sdc:        'bg-blue-500/10 text-blue-400 border-blue-500/20',
    gerenciamento:      'bg-blue-500/10 text-blue-300 border-blue-500/20',
    notificacao:        'bg-amber-500/10 text-amber-400 border-amber-500/20',
    analise:            'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
    aprovado:           'bg-green-500/10 text-green-400 border-green-500/20',
    reprovado:          'bg-red-500/10 text-red-400 border-red-500/20',
    ccpae:              'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    ativo_3_anos:       'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    suspenso:           'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    revogado:           'bg-red-700/10 text-red-400 border-red-700/20',
    esperar_tratativa:  'bg-orange-500/10 text-orange-400 border-orange-500/20',
    dilacao:            'bg-orange-500/10 text-orange-400 border-orange-500/20',
};

const props = defineProps({
    empreendimento: {
        type: Object,
        required: true,
    },
    lastUpdate: {
        type: String,
        default: null,
    },
    protocolo: {
        type: Object,
        default: null,
    },
});

const lastUpdate = computed(() => {
    return props.lastUpdate || formatDateTime(new Date());
});

const statusLabel = computed(() =>
    props.protocolo?.status ? (STATUS_LABELS[props.protocolo.status] ?? props.protocolo.status) : ''
);

const statusClass = computed(() =>
    props.protocolo?.status ? (STATUS_CLASSES[props.protocolo.status] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20') : ''
);

function getNivelEmergenciaClass(nivel) {
    const classes = {
        1: 'bg-red-500/10 text-red-400 border-red-500/20',
        2: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        3: 'bg-green-500/10 text-green-400 border-green-500/20',
    };
    return classes[nivel] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
}
</script>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Pae/PaeHeader.vue
git commit -m "feat(pae): PaeHeader exibe num_protocolo e badge de status"
```

---

## Task 5: Pae.vue — prop protocolo + PaeBreadcrumb

**Files:**
- Modify: `resources/js/Pages/Pae.vue`

- [ ] **Step 1: Adicionar prop `protocolo`, repassar para filhos e incluir PaeBreadcrumb**

`PaeBreadcrumb` ja existe e ja aceita `situacao` (string do status) e `numProtocolo` (string). O componente renderiza o stepper de workflow do protocolo.

Substituir o conteudo completo do arquivo:

```vue
<template>
    <div>
        <Head title="Gestão de PAE" />

        <div class="pae-container">
            <PaeHeader
                :empreendimento="empreendimento"
                :last-update="props.lastUpdate"
                :protocolo="props.protocolo"
            />

            <PaeBreadcrumb
                v-if="props.protocolo"
                :situacao="props.protocolo.status"
                :num-protocolo="props.protocolo.num_protocolo"
            />

            <PaeForm
                :empreendimento="empreendimento"
                :municipios="props.municipios"
                :formulario="props.formulario"
            />
        </div>
    </div>
</template>

<script setup>
import PaeBreadcrumb from '@/Components/Pae/PaeBreadcrumb.vue';
import PaeForm from '@/Components/Pae/PaeForm.vue';
import PaeHeader from '@/Components/Pae/PaeHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import '../../css/pages/pae/pae.css';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    empreendimento: {
        type: Object,
        default: () => ({}),
    },
    lastUpdate: {
        type: String,
        default: null,
    },
    municipios: {
        type: Object,
        default: () => ({}),
    },
    formulario: {
        type: Object,
        default: null,
    },
    protocolo: {
        type: Object,
        default: null,
    },
});

const empreendimento = computed(() => props.empreendimento ?? {});
</script>

<style scoped>
.pae-container {
    @apply w-full min-h-screen bg-transparent;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Pae.vue
git commit -m "feat(pae): Pae.vue propaga protocolo para header e breadcrumb"
```

---

## Task 6: Verificacao Final

- [ ] **Step 1: Rodar toda a suite de testes PAE**

```bash
cd SDC
php artisan test tests/Feature/Pae/
```

Esperado: todos PASS.

- [ ] **Step 2: Build do frontend**

```bash
npm run build 2>&1 | tail -20
```

Esperado: sem erros de compilacao.

- [ ] **Step 3: Verificar fluxo no browser**

Abrir `https://newsdc2027.azurewebsites.net/pae` (lista de protocolos).
Clicar em "Novo Protocolo", preencher e salvar.
Esperado: redirecionamento para `/pae/protocolo?protocolo_id={id}` com o numero visivel no cabecalho (ex: `#13.04.2026.001`) e o stepper exibindo o status atual.

Abrir um protocolo existente pela lista via edit.
Esperado: mesmo comportamento — numero e stepper visiveis.

- [ ] **Step 4: Commit final se necessario**

```bash
git add -p
git commit -m "chore(pae): ajustes pos-verificacao protocolo header"
```
