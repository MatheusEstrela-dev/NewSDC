# RAT Create — Refatoração Atomic Design — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refatorar o método CREATE do módulo RAT para respeitar Atomic Design, criando a camada Template ausente, extraindo Sections nomeadas e limpando a Page de handlers inline.

**Architecture:** Bottom-up — Átomos → Moléculas → Sections → Organism → Template → Page. Cada passo é compilável independentemente. `RatForm.vue` original permanece até o Passo 4 ser validado. Tabs 2-6 não são tocadas.

**Tech Stack:** Vue 3 Composition API, Inertia.js, Tailwind CSS, Vite, Laravel, `just npm-build`

**Spec:** `docs/superpowers/specs/2026-03-12-rat-create-atomic-design.md`

---

## Chunk 1: Átomos, Moléculas e Sections

---

### Task 1: ToggleInput (Átomo)

**Files:**
- Create: `resources/js/Components/Atoms/Input/ToggleInput.vue`

- [ ] **Step 1: Criar o átomo ToggleInput**

```vue
<!-- resources/js/Components/Atoms/Input/ToggleInput.vue -->
<template>
  <button
    type="button"
    :class="[
      'w-11 h-6 flex items-center rounded-full transition-all duration-200',
      'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white',
      'dark:focus:ring-offset-slate-900 focus:ring-blue-500',
      modelValue ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700',
    ]"
    :aria-checked="modelValue"
    role="switch"
    @click="$emit('update:modelValue', !modelValue)"
  >
    <span
      :class="[
        'w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200',
        modelValue ? 'translate-x-5' : 'translate-x-0.5',
      ]"
    />
  </button>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['update:modelValue']);
</script>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Atoms/Input/ToggleInput.vue
git commit -m "feat(rat): add ToggleInput atom"
```

---

### Task 2: ToggleField (Molécula) e RatFormActions (Molécula)

**Files:**
- Create: `resources/js/Components/Molecules/Form/ToggleField.vue`
- Create: `resources/js/Components/Molecules/Rat/RatFormActions.vue`

- [ ] **Step 1: Criar ToggleField**

```vue
<!-- resources/js/Components/Molecules/Form/ToggleField.vue -->
<template>
  <div class="flex items-center justify-between p-3 sm:p-4 rounded-lg bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-700/50">
    <div class="flex gap-2 sm:gap-3 items-start">
      <div v-if="icon" class="bg-purple-500/20 p-1.5 sm:p-2 rounded-lg flex-shrink-0">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" />
      </div>
      <div>
        <p class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200">
          {{ label }}
        </p>
        <p v-if="description" class="text-xs text-slate-500 mt-0.5 hidden sm:block">
          {{ description }}
        </p>
      </div>
    </div>
    <ToggleInput
      :model-value="modelValue"
      class="flex-shrink-0 ml-2 sm:ml-4"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';

defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
});

defineEmits(['update:modelValue']);
</script>
```

- [ ] **Step 2: Criar RatFormActions**

```vue
<!-- resources/js/Components/Molecules/Rat/RatFormActions.vue -->
<template>
  <div v-if="!viewOnly" class="rat-actions-footer">
    <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 pt-3 pb-6 sm:px-6 sm:pt-4 sm:pb-6">
      <Button
        variant="warning"
        size="sm"
        :loading="loading"
        @click="$emit('save-draft')"
      >
        Salvar
      </Button>
      <Button
        variant="primary"
        size="sm"
        :icon="CheckCircleIcon"
        :loading="loading"
        @click="$emit('finalize')"
      >
        <span class="hidden sm:inline">Finalizar RAT</span>
        <span class="sm:hidden">Finalizar</span>
      </Button>
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

defineProps({
  viewOnly: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save-draft', 'finalize']);
</script>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Molecules/Form/ToggleField.vue
git add resources/js/Components/Molecules/Rat/RatFormActions.vue
git commit -m "feat(rat): add ToggleField molecule and RatFormActions molecule"
```

---

### Task 3: Sections — RatAtendimentoSection, RatNaturezaSection, RatConfigSection

**Files:**
- Create: `resources/js/Components/Rat/Sections/RatAtendimentoSection.vue`
- Create: `resources/js/Components/Rat/Sections/RatNaturezaSection.vue`
- Create: `resources/js/Components/Rat/Sections/RatConfigSection.vue`

> Estas sections extraem o conteúdo que estava inline em `RatForm.vue`. Cada uma usa v-model no objeto `dadosGerais` completo e emite `update:modelValue` com o objeto atualizado.

- [ ] **Step 1: Criar RatAtendimentoSection**

```vue
<!-- resources/js/Components/Rat/Sections/RatAtendimentoSection.vue -->
<template>
  <RatCollapsibleSection
    section-id="atendimento"
    title="Atendimento"
    subtitle="Data e horário do fato e atividades"
    icon-class="rat-section-icon-default"
    :default-expanded="true"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </template>
    <div class="rat-grid-3">
      <FormField
        label="Data/Hora do Fato"
        type="datetime-local"
        :model-value="modelValue.data_fato"
        required
        @update:model-value="emit('update:modelValue', { ...modelValue, data_fato: $event })"
      />
      <FormField
        label="Início da Atividade"
        type="datetime-local"
        :model-value="modelValue.data_inicio_atividade"
        required
        @update:model-value="emit('update:modelValue', { ...modelValue, data_inicio_atividade: $event })"
      />
      <FormField
        label="Término da Atividade"
        type="datetime-local"
        :model-value="modelValue.data_termino_atividade"
        @update:model-value="emit('update:modelValue', { ...modelValue, data_termino_atividade: $event })"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      data_fato: '',
      data_inicio_atividade: '',
      data_termino_atividade: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);
</script>
```

- [ ] **Step 2: Criar RatNaturezaSection**

```vue
<!-- resources/js/Components/Rat/Sections/RatNaturezaSection.vue -->
<template>
  <RatCollapsibleSection
    section-id="natureza"
    title="Natureza da Ocorrência"
    subtitle="Classificação COBRADE e identificação da operação"
    icon-class="rat-section-icon-success"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </template>
    <div class="rat-grid-2">
      <FormSelect
        label="Classificacao COBRADE"
        :model-value="modelValue.nat_cobrade_id"
        :options="cobradeOptions"
        placeholder="Selecione a classificacao..."
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_cobrade_id: $event })"
      />
      <FormField
        label="Nome da Operacao (Opcional)"
        :model-value="modelValue.nat_nome_operacao"
        placeholder="Ex: Operação Chuvas de Verão"
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_nome_operacao: $event })"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      nat_cobrade_id: '',
      nat_nome_operacao: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const cobradeOptions = [
  { value: '1', label: '1.3.2.1.0 - Tempestade Local' },
  { value: '2', label: '1.2.1.0.0 - Inundação' },
  { value: '3', label: '1.1.3.3.1 - Deslizamento de Planície' },
];
</script>
```

- [ ] **Step 3: Criar RatConfigSection**

```vue
<!-- resources/js/Components/Rat/Sections/RatConfigSection.vue -->
<template>
  <RatCollapsibleSection
    section-id="configuracoes"
    title="Configurações do RAT"
    subtitle="Configurações gerais e unidade responsável"
    icon-class="rat-section-icon-purple"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </template>
    <div class="space-y-4 sm:space-y-5">
      <ToggleField
        :model-value="modelValue.tem_vistoria"
        label="Realizou Vistoria Imobiliária?"
        description="Habilita a aba de vistoria técnica"
        :icon="ClipboardIcon"
        @update:model-value="onToggleVistoria"
      />
      <FormField
        label="Unidade Responsavel"
        :model-value="unidade"
        readonly
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ tem_vistoria: false }),
  },
  unidade: {
    type: String,
    default: 'COMPDEC - Municipio Modelo/MG',
  },
});

const emit = defineEmits(['update:modelValue']);

function onToggleVistoria(value) {
  emit('update:modelValue', { ...props.modelValue, tem_vistoria: value });
}
</script>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Rat/Sections/RatAtendimentoSection.vue
git add resources/js/Components/Rat/Sections/RatNaturezaSection.vue
git add resources/js/Components/Rat/Sections/RatConfigSection.vue
git commit -m "feat(rat): add RatAtendimentoSection, RatNaturezaSection, RatConfigSection"
```

---

## Chunk 2: Organism e Template

---

### Task 4: RatDadosGeraisForm (Organism)

**Files:**
- Create: `resources/js/Components/Rat/RatDadosGeraisForm.vue`
- Keep (não deletar ainda): `resources/js/Components/Rat/RatForm.vue`

> `RatForm.vue` só será removido após validação visual do build. Criar o novo arquivo em paralelo.

- [ ] **Step 1: Criar RatDadosGeraisForm.vue**

```vue
<!-- resources/js/Components/Rat/RatDadosGeraisForm.vue -->
<template>
  <fieldset
    :disabled="viewOnly"
    style="border: none; padding: 0; margin: 0; min-width: 0;"
    :class="['space-y-4 sm:space-y-6 rat-form-content', viewOnly ? 'pb-10' : '']"
  >
    <RatAtendimentoSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatCommunicationSection
      v-model="localData.comunicacao"
    />

    <RatNaturezaSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatConfigSection
      :model-value="localData.dadosGerais"
      @update:model-value="onConfigUpdate"
    />

    <RatLocationSection
      v-model="localData.local"
    />

    <RatAddressSection
      v-model="localData.endereco"
      @location-updated="handleLocationUpdated"
    />

    <RatFormActions
      :view-only="viewOnly"
      @save-draft="$emit('save-draft', localData)"
      @finalize="$emit('finalize', localData)"
    />
  </fieldset>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatAtendimentoSection from './Sections/RatAtendimentoSection.vue';
import RatCommunicationSection from './Sections/RatCommunicationSection.vue';
import RatNaturezaSection from './Sections/RatNaturezaSection.vue';
import RatConfigSection from './Sections/RatConfigSection.vue';
import RatLocationSection from './Sections/RatLocationSection.vue';
import RatAddressSection from './Sections/RatAddressSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  rat: {
    type: [Object, null],
    default: () => null,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save', 'save-draft', 'cancel', 'finalize', 'update:tem-vistoria', 'update:formData']);

const localData = ref({
  dadosGerais: {
    data_fato: props.rat?.dados_gerais?.data_fato || '',
    data_inicio_atividade: props.rat?.dados_gerais?.data_inicio_atividade || '',
    data_termino_atividade: props.rat?.dados_gerais?.data_termino_atividade || '',
    nat_cobrade_id: props.rat?.dados_gerais?.nat_cobrade_id || '',
    nat_nome_operacao: props.rat?.dados_gerais?.nat_nome_operacao || '',
    tem_vistoria: props.rat?.tem_vistoria || false,
  },
  comunicacao: {
    data_comunicacao: props.rat?.comunicacao?.data_comunicacao || '',
    tipo_solicitacao: props.rat?.comunicacao?.tipo_solicitacao || '',
    telefone_contato: props.rat?.comunicacao?.telefone_contato || '',
    nome_solicitante: props.rat?.comunicacao?.nome_solicitante || '',
  },
  local: {
    pais_id: props.rat?.local?.pais_id || '1',
    uf: props.rat?.local?.uf || '',
    municipio_id: props.rat?.local?.municipio_id || '',
  },
  endereco: {
    cep: props.rat?.endereco?.cep || '',
    logradouro: props.rat?.endereco?.logradouro || '',
    numero: props.rat?.endereco?.numero || '',
    complemento: props.rat?.endereco?.complemento || '',
    bairro: props.rat?.endereco?.bairro || '',
    km: props.rat?.endereco?.km || '',
    cruzamento: props.rat?.endereco?.cruzamento || '',
    ponto_referencia: props.rat?.endereco?.ponto_referencia || '',
    tipo_localizacao: props.rat?.endereco?.tipo_localizacao || '',
    latitude: props.rat?.endereco?.latitude || null,
    longitude: props.rat?.endereco?.longitude || null,
  },
});

function onConfigUpdate(dadosGerais) {
  const hadVistoria = localData.value.dadosGerais.tem_vistoria;
  localData.value.dadosGerais = dadosGerais;
  if (dadosGerais.tem_vistoria !== hadVistoria) {
    emit('update:tem-vistoria', dadosGerais.tem_vistoria);
  }
}

function handleLocationUpdated({ uf }) {
  if (uf) localData.value.local.uf = uf;
}

watch(
  () => props.rat,
  (newVal) => {
    if (newVal?.dados_gerais) {
      localData.value.dadosGerais = {
        ...localData.value.dadosGerais,
        ...newVal.dados_gerais,
        tem_vistoria: newVal.tem_vistoria ?? localData.value.dadosGerais.tem_vistoria,
      };
    }
  },
  { deep: true }
);

watch(
  localData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
.rat-form-content {
  padding-bottom: 0.5rem;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Rat/RatDadosGeraisForm.vue
git commit -m "feat(rat): add RatDadosGeraisForm organism (replaces RatForm)"
```

---

### Task 5: RatFormLayout (Template)

**Files:**
- Create: `resources/js/Components/Rat/Templates/RatFormLayout.vue`

- [ ] **Step 1: Criar RatFormLayout.vue**

```vue
<!-- resources/js/Components/Rat/Templates/RatFormLayout.vue -->
<template>
  <div class="rat-container">
    <RatHeader
      :rat="rat"
      :last-update="lastUpdate"
      :view-only="viewOnly"
      :is-create="isCreate"
    />

    <RatTabs
      :active-tab="activeTab"
      :tabs="tabConfig"
      @tab-change="$emit('tab-change', $event)"
    >
      <template #default="slotProps">
        <slot v-bind="slotProps" />
      </template>
    </RatTabs>
  </div>
</template>

<script setup>
import RatHeader from '@/Components/Rat/RatHeader.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';

defineProps({
  rat: {
    type: Object,
    required: true,
  },
  tabConfig: {
    type: Array,
    default: () => [],
  },
  activeTab: {
    type: Number,
    default: 1,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  isCreate: {
    type: Boolean,
    default: false,
  },
  lastUpdate: {
    type: String,
    default: null,
  },
});

defineEmits(['tab-change']);
</script>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Rat/Templates/RatFormLayout.vue
git commit -m "feat(rat): add RatFormLayout template"
```

---

## Chunk 3: Composable, Page e Build

---

### Task 6: Mover handlers de array para useRat

**Files:**
- Modify: `resources/js/composables/useRat.js`

> Adicionar as funções de manipulação de array que hoje estão inline na Page. A interface pública existente (salvarRat, salvarRascunho, finalizarRat, cancelarRat) não muda.

- [ ] **Step 1: Adicionar handlers de array no useRat**

Adicionar ao final do bloco de retorno do `useRat`, após `cancelarRat`:

```js
// Recursos
function adicionarRecurso(recurso) {
    if (!Array.isArray(recursos.value)) recursos.value = [];
    recursos.value.push(recurso);
}
function removerRecurso(id) {
    if (!Array.isArray(recursos.value)) return;
    const i = recursos.value.findIndex(r => r.id === id);
    if (i > -1) recursos.value.splice(i, 1);
}
function atualizarRecursos(data) {
    recursos.value = data;
}

// Envolvidos
function adicionarEnvolvido(e) {
    envolvidos.value.push(e);
}
function removerEnvolvido(id) {
    const i = envolvidos.value.findIndex(e => e.id === id);
    if (i > -1) envolvidos.value.splice(i, 1);
}
function atualizarEnvolvidos(data) {
    envolvidos.value = Array.isArray(data) ? data : [];
}

// Vistoria
function atualizarVistoria(data) {
    vistoria.value = { ...vistoria.value, ...data };
}

// Historico
function adicionarObservacao(obs) {
    if (!Array.isArray(historico.value)) historico.value = [];
    historico.value.unshift({ id: Date.now(), ...obs, created_at: new Date().toISOString() });
}
function atualizarHistorico(data) {
    historico.value = data;
}

// Anexos
function adicionarAnexo(anexo) {
    if (!anexos.value) anexos.value = [];
    anexos.value.push(anexo);
}
function removerAnexo(id) {
    if (!anexos.value) return;
    const i = anexos.value.findIndex(a => a.id === id);
    if (i > -1) anexos.value.splice(i, 1);
}
function atualizarAnexos(data) {
    anexos.value = data;
}
```

Atualizar o `return` do composable para incluir todas as novas funções:

```js
return {
    rat,
    recursos,
    envolvidos,
    vistoria,
    historico,
    anexos,
    tabs,
    salvarRat,
    salvarRascunho,
    finalizarRat,
    cancelarRat,
    adicionarRecurso,
    removerRecurso,
    atualizarRecursos,
    adicionarEnvolvido,
    removerEnvolvido,
    atualizarEnvolvidos,
    atualizarVistoria,
    adicionarObservacao,
    atualizarHistorico,
    adicionarAnexo,
    removerAnexo,
    atualizarAnexos,
};
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useRat.js
git commit -m "feat(rat): move array handlers into useRat composable"
```

---

### Task 7: Refatorar RatCreate.vue (Page)

**Files:**
- Modify: `resources/js/Pages/Rat/RatCreate.vue`
- Delete: `resources/js/Components/Rat/RatForm.vue` (após este passo)

- [ ] **Step 1: Substituir o conteúdo de RatCreate.vue**

```vue
<!-- resources/js/Pages/Rat/RatCreate.vue -->
<template>
  <Head title="Novo RAT" />

  <RatFormLayout
    :rat="emptyRat"
    :tab-config="tabConfig"
    :active-tab="currentActiveTab"
    :is-create="true"
    :last-update="null"
    :view-only="false"
    @tab-change="tabs.setActiveTab"
  >
    <template #default="{ activeTab }">
      <div v-if="Number(activeTab) === 1">
        <RatDadosGeraisForm
          :rat="null"
          :view-only="false"
          @save-draft="salvarRascunho"
          @finalize="finalizarRat"
          @update:tem-vistoria="temVistoria = $event"
          @update:form-data="currentFormData = $event"
        />
      </div>

      <div v-else-if="Number(activeTab) === 2">
        <RatResources
          :recursos="recursos"
          :view-only="false"
          @add="adicionarRecurso"
          @remove="removerRecurso"
          @update="atualizarRecursos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 3">
        <RatInvolved
          :envolvidos="envolvidos"
          :view-only="false"
          @add="adicionarEnvolvido"
          @remove="removerEnvolvido"
          @update="atualizarEnvolvidos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 4">
        <RatInspection
          :vistoria="vistoria"
          :view-only="false"
          @update="atualizarVistoria"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 5">
        <RatHistory
          :events="historico"
          :view-only="false"
          @add-observation="adicionarObservacao"
          @update="atualizarHistorico"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 6">
        <RatAttachments
          :rat-id="null"
          :anexos="anexos"
          :view-only="false"
          @add="adicionarAnexo"
          @remove="removerAnexo"
          @update="atualizarAnexos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>
    </template>
  </RatFormLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import RatDadosGeraisForm from '@/Components/Rat/RatDadosGeraisForm.vue';
import RatFormLayout from '@/Components/Rat/Templates/RatFormLayout.vue';
import RatAttachments from '@/Components/Rat/RatAttachments.vue';
import RatHistory from '@/Components/Rat/RatHistory.vue';
import RatInspection from '@/Components/Rat/RatInspection.vue';
import RatInvolved from '@/Components/Rat/RatInvolved.vue';
import RatResources from '@/Components/Rat/RatResources.vue';
import { useRat } from '@/Composables/useRat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import '../../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

const emptyRat = { id: null, protocolo: null, status: 'rascunho' };

const {
  rat,
  recursos,
  envolvidos,
  vistoria,
  historico,
  anexos,
  tabs,
  salvarRascunho,
  finalizarRat,
  adicionarRecurso,
  removerRecurso,
  atualizarRecursos,
  adicionarEnvolvido,
  removerEnvolvido,
  atualizarEnvolvidos,
  atualizarVistoria,
  adicionarObservacao,
  atualizarHistorico,
  adicionarAnexo,
  removerAnexo,
  atualizarAnexos,
} = useRat({ rat: null, recursos: [], envolvidos: [], vistoria: {}, historico: [], anexos: [], activeTab: 1 });

const temVistoria = ref(false);
const currentFormData = ref({ dadosGerais: {}, comunicacao: {}, local: {}, endereco: {} });

const currentActiveTab = computed(() => {
  const t = tabs.activeTab;
  return Number(typeof t === 'object' && t !== null && 'value' in t ? t.value : t);
});

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: recursos.value?.length || null },
  { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: envolvidos.value?.length || null },
  { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Histórico', icon: ClockIcon },
  { id: 6, label: 'Anexos', icon: PaperClipIcon },
]);
</script>
```

- [ ] **Step 2: Remover RatForm.vue (agora substituído)**

```bash
git rm resources/js/Components/Rat/RatForm.vue
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Rat/RatCreate.vue
git commit -m "feat(rat): refactor RatCreate to thin Page using RatFormLayout + RatDadosGeraisForm"
```

---

### Task 8: Build e Verificação

- [ ] **Step 1: Executar build**

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/SDC && just npm-build
```

Expected: `built in Xs` sem erros. Warnings de HTML (`<tr>` sem `<tbody>`) em outros arquivos são pré-existentes e aceitáveis.

- [ ] **Step 2: Verificar contagem de linhas da Page**

```bash
wc -l resources/js/Pages/Rat/RatCreate.vue
```

Expected: abaixo de 60 linhas.

- [ ] **Step 3: Confirmar que RatForm.vue foi removido**

```bash
ls resources/js/Components/Rat/RatForm.vue 2>/dev/null && echo "EXISTE - remover" || echo "OK - removido"
```

Expected: `OK - removido`

- [ ] **Step 4: Commit final**

```bash
git add -A
git commit -m "feat(rat): atomic design refactor CREATE complete - Template + Organism + Sections extracted"
```

---

## Critérios de Sucesso

- `RatCreate.vue` abaixo de 60 linhas
- Nenhum handler inline de array na Page
- Nenhum `<button>` bruto no Organism (substituído por `RatFormActions` + `ToggleField`)
- `just npm-build` sem erros
- Funcionalidade de criar RAT (salvar rascunho + finalizar) operacional
