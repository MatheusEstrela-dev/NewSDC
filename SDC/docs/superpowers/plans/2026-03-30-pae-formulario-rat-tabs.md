# PAE Formulário RAT — Refatoração das Abas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refatorar a aba "Formulário PAE" do `PaeForm.vue` para conter 4 sub-abas do RAT (Informações Gerais, Objetivo e Contexto, Apontamentos Técnicos, Conclusão) com salvamento parcial por aba via Inertia, mantendo o design system existente com suporte dark/light.

**Architecture:** `PaeForm.vue` torna-se orquestrador que instancia `usePaeFormulario` e renderiza `PaeFormTabs` + 4 organisms de sub-aba. O composable `usePaeFormulario` centraliza todo o state reativo e os métodos de save/lista dinâmica. Cada organism é independente, recebe apenas seu slice de dados e emite eventos para o orquestrador.

**Tech Stack:** Vue 3 (script setup, Composition API), Inertia.js (`@inertiajs/vue3`), Tailwind CSS (dark/light), composables pattern DDD.

**Spec:** `docs/superpowers/specs/2026-03-30-pae-formulario-rat-tabs-design.md`

---

## File Map

| Ação | Arquivo |
|---|---|
| **CRIAR** | `resources/js/composables/pae/usePaeFormulario.js` |
| **CRIAR** | `resources/js/Components/Pae/PaeFormTabs.vue` |
| **CRIAR** | `resources/js/Components/Pae/PaeFormInfoGerais.vue` |
| **CRIAR** | `resources/js/Components/Pae/PaeFormObjetivoContexto.vue` |
| **CRIAR** | `resources/js/Components/Pae/PaeFormApontamentos.vue` |
| **CRIAR** | `resources/js/Components/Pae/PaeFormConclusao.vue` |
| **MODIFICAR** | `resources/js/Components/Pae/PaeForm.vue` |
| **MODIFICAR** | `resources/js/composables/pae/index.js` |
| **MODIFICAR** | `resources/js/Pages/Pae.vue` |

---

## Task 1: Composable `usePaeFormulario.js`

**Files:**
- Create: `resources/js/composables/pae/usePaeFormulario.js`
- Modify: `resources/js/composables/pae/index.js`

Este composable é a fundação de todo o formulário. Centraliza state, inicialização com dados do empreendimento, métodos de lista dinâmica e saves via Inertia.

- [ ] **Step 1.1: Criar o composable**

Criar `resources/js/composables/pae/usePaeFormulario.js`:

```js
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const OBJETIVO_DEFAULT = 'Analisar os requisitos necessários para a aprovação da Segunda Seção do Plano de Ação de Emergência, relativos à competência do órgão Estadual de Proteção e Defesa Civil, expressa no Decreto Estadual n. 48.078, de 05 de novembro de 2020 e notificar o empreendedor sobre as inconsistências observadas para devida correção.';

const CONTEXTUALIZACAO_DEFAULT = 'O PAE é analisado conforme a Resolução GMG n. 83/2024, além da legislação estadual e federal vigentes. Após a sua aprovação, será emitido o Certificado de Conformidade do Plano de Ação de Emergência (CCPAE) pelo Coordenador Estadual de Defesa Civil de Minas Gerais.\n\nA emissão do CCPAE está vinculada à análise de um cenário hipotético, no qual os detalhes específicos serão descritos em um relatório relacionado à estrutura analisada. A barragem poderá ser vistoriada a qualquer tempo pelos órgãos fiscalizadores federais e estaduais e caso sejam constatadas irregularidades previstas em legislação, o CCPAE poderá ser revogado.';

/**
 * Composable do formulário RAT do PAE
 * Single Responsibility: State e operações do formulário de análise técnica
 */
export function usePaeFormulario(empreendimento = {}, formulario = null) {
  const saving = ref(false);
  let nextId = 10;

  function makeId() {
    return nextId++;
  }

  // State das 4 seções
  const infoGerais = ref({
    barragem:                formulario?.barragem                ?? empreendimento?.nome            ?? '',
    municipio_id:            formulario?.municipio_id             ?? empreendimento?.municipio_id    ?? '',
    coordenador_pae:         formulario?.coordenador_pae          ?? empreendimento?.coordenador     ?? '',
    email:                   formulario?.email                    ?? empreendimento?.email_coord     ?? '',
    coordenador_mun_def_civ: formulario?.coordenador_mun_def_civ  ?? '',
    coordenador_mun_compdec: formulario?.coordenador_mun_compdec  ?? '',
    empreendedor_res:        formulario?.empreendedor_res         ?? empreendimento?.empreendedor?.nome ?? '',
    metodo_construtivo:      formulario?.metodo_construtivo       ?? empreendimento?.m_construcao   ?? '',
    numero_zas:              formulario?.numero_zas               ?? empreendimento?.pop_zas         ?? '',
    nivel_emergencia:        formulario?.nivel_emergencia         ?? '',
  });

  const objetivoContexto = ref({
    objetivo:          formulario?.objetivo         ?? OBJETIVO_DEFAULT,
    contextualizacao:  formulario?.contextualizacao ?? CONTEXTUALIZACAO_DEFAULT,
  });

  const apontamentos = ref(
    formulario?.apontamentos?.length
      ? formulario.apontamentos
      : [{ id: makeId(), text: '', children: [] }]
  );

  const conclusao = ref(
    formulario?.conclusao?.length
      ? formulario.conclusao
      : [{ id: makeId(), text: '', children: [] }]
  );

  // Saves parciais via Inertia
  function saveInfoGerais(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/infogerais`, infoGerais.value, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveObjetivoContexto(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/objetivo`, objetivoContexto.value, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveApontamentos(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/aptecnico`, { apontamentos: apontamentos.value }, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveConclusao(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/conclusao`, { conclusao: conclusao.value }, {
      onFinish: () => { saving.value = false; },
    });
  }

  function finalizarRelatorio(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/finalizar`, { conclusao: conclusao.value }, {
      onFinish: () => { saving.value = false; },
    });
  }

  // Gestão de lista dinâmica (apontamentos e conclusao compartilham a mesma lógica)
  function getList(section) {
    return section === 'apontamentos' ? apontamentos : conclusao;
  }

  function addItem(section) {
    getList(section).value.push({ id: makeId(), text: '', children: [] });
  }

  function removeItem(section, index) {
    const list = getList(section).value;
    if (list.length > 1) list.splice(index, 1);
  }

  function addSubItem(section, itemIndex) {
    getList(section).value[itemIndex].children.push({ id: makeId(), text: '' });
  }

  function removeSubItem(section, itemIndex, subIndex) {
    getList(section).value[itemIndex].children.splice(subIndex, 1);
  }

  return {
    saving,
    infoGerais,
    objetivoContexto,
    apontamentos,
    conclusao,
    saveInfoGerais,
    saveObjetivoContexto,
    saveApontamentos,
    saveConclusao,
    finalizarRelatorio,
    addItem,
    removeItem,
    addSubItem,
    removeSubItem,
  };
}
```

- [ ] **Step 1.2: Exportar do index**

Editar `resources/js/composables/pae/index.js`:

```js
/**
 * PAE composables - Plano de Acao Emergencial
 */
export * from './usePae';
export * from './usePaeFormulario';
```

- [ ] **Step 1.3: Verificar no browser**

Abrir o DevTools → Console. Importar o composable e confirmar que não há erros de parse:
```
No errors on console
```

- [ ] **Step 1.4: Commit**

```bash
git add resources/js/composables/pae/usePaeFormulario.js resources/js/composables/pae/index.js
git commit -m "feat(pae): composable usePaeFormulario com state e saves parciais"
```

---

## Task 2: `PaeFormTabs.vue` — Navegação interna das sub-abas

**Files:**
- Create: `resources/js/Components/Pae/PaeFormTabs.vue`

Molecule que renderiza a navegação das 4 sub-abas do RAT. Segue o padrão visual idêntico ao `PaeTabs.vue` existente.

- [ ] **Step 2.1: Criar o componente**

Criar `resources/js/Components/Pae/PaeFormTabs.vue`:

```vue
<template>
  <div class="bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Seções do formulário RAT">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('tab-change', tab.id)"
        :class="getTabClass(tab.id)"
        type="button"
      >
        <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
        <span class="whitespace-nowrap">{{ tab.label }}</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
const props = defineProps({
  activeTab: {
    type: Number,
    default: 1,
  },
  tabs: {
    type: Array,
    required: true,
  },
});

defineEmits(['tab-change']);

function getTabClass(tabId) {
  const base =
    'px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg font-medium text-xs sm:text-sm transition-all duration-200 flex items-center gap-1.5 sm:gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 cursor-pointer select-none';

  if (props.activeTab === tabId) {
    return `${base} text-blue-400 bg-blue-500/10 border-b-2 border-blue-400`;
  }

  return `${base} text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700/50`;
}
</script>
```

- [ ] **Step 2.2: Commit**

```bash
git add resources/js/Components/Pae/PaeFormTabs.vue
git commit -m "feat(pae): molecule PaeFormTabs para navegacao das sub-abas do RAT"
```

---

## Task 3: `PaeFormInfoGerais.vue` — Sub-aba 1

**Files:**
- Create: `resources/js/Components/Pae/PaeFormInfoGerais.vue`

Organism da aba "Informações Gerais". Grid 2 colunas com `FormField` e `FormSelect`. Dados pré-populados via prop `modelValue` (objeto `infoGerais`).

- [ ] **Step 3.1: Criar o componente**

Criar `resources/js/Components/Pae/PaeFormInfoGerais.vue`:

```vue
<template>
  <PaeCard title="1. Informações Gerais do Relatório">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
      <FormField
        label="Barragem"
        v-model="local.barragem"
        placeholder="Nome da Barragem"
      />
      <FormSelect
        label="Município"
        v-model="local.municipio_id"
        :options="municipioOptions"
        placeholder="Selecione um município"
      />
      <FormField
        label="Coordenador do PAE"
        v-model="local.coordenador_pae"
        placeholder="Nome do coordenador (empreendedor)"
      />
      <FormField
        label="Email"
        type="email"
        v-model="local.email"
        placeholder="email@empreendedor.com"
      />
      <FormField
        label="Coordenador Municipal de Defesa Civil"
        v-model="local.coordenador_mun_def_civ"
        placeholder="Nome do Coordenador Municipal"
      />
      <FormField
        label="Coordenador Municipal (Compdec)"
        v-model="local.coordenador_mun_compdec"
        placeholder="Nome do Coordenador Municipal Compdec"
      />
      <FormField
        label="Empreendedor Responsável"
        v-model="local.empreendedor_res"
        placeholder="Nome do Empreendedor"
      />
      <FormSelect
        label="Método Construtivo"
        v-model="local.metodo_construtivo"
        :options="metodosConstrutivos"
        placeholder="Selecione um método"
      />
      <FormField
        label="Número de ZAS"
        v-model="local.numero_zas"
        placeholder="Número de ZAS"
      />
      <FormSelect
        label="Nível de Emergência"
        v-model="local.nivel_emergencia"
        :options="niveisEmergencia"
        placeholder="Selecione um nível"
      />
    </div>

    <div class="flex justify-end mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save', local)"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Informações Gerais
      </button>
    </div>
  </PaeCard>
</template>

<script setup>
import { reactive, watch } from 'vue';
import FormField from './FormField.vue';
import FormSelect from './FormSelect.vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  municipios: {
    type: Object,
    default: () => ({}),
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save']);

const local = reactive({ ...props.modelValue });

watch(() => props.modelValue, (val) => Object.assign(local, val), { deep: true });

const municipioOptions = Object.entries(props.municipios).map(([value, label]) => ({ value, label }));

const metodosConstrutivos = [
  { value: 'Jusante',        label: 'Jusante' },
  { value: 'Montante',       label: 'Montante' },
  { value: 'Etapa única',    label: 'Etapa única' },
  { value: 'Linha de Centro', label: 'Linha de Centro' },
];

const niveisEmergencia = [
  { value: '0', label: 'Sem Emergência' },
  { value: '1', label: 'Alerta' },
  { value: '2', label: 'Nível 1' },
  { value: '3', label: 'Nível 2' },
  { value: '4', label: 'Nível 3' },
];
</script>
```

- [ ] **Step 3.2: Commit**

```bash
git add resources/js/Components/Pae/PaeFormInfoGerais.vue
git commit -m "feat(pae): organism PaeFormInfoGerais - sub-aba 1 do RAT"
```

---

## Task 4: `PaeFormObjetivoContexto.vue` — Sub-aba 2

**Files:**
- Create: `resources/js/Components/Pae/PaeFormObjetivoContexto.vue`

Organism com dois `PaeCard` empilhados: Objetivo e Contextualização. Textareas full-width com texto default pré-preenchido.

- [ ] **Step 4.1: Criar o componente**

Criar `resources/js/Components/Pae/PaeFormObjetivoContexto.vue`:

```vue
<template>
  <div class="space-y-6">
    <PaeCard title="2. Objetivo">
      <textarea
        v-model="local.objetivo"
        rows="6"
        placeholder="Descreva o objetivo da análise..."
        class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-4 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
      />
    </PaeCard>

    <PaeCard title="3. Contextualização">
      <textarea
        v-model="local.contextualizacao"
        rows="8"
        placeholder="Contextualize a análise técnica..."
        class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-4 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
      />
    </PaeCard>

    <div class="flex justify-end">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save', local)"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Objetivo e Contexto
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save']);

const local = reactive({ ...props.modelValue });

watch(() => props.modelValue, (val) => Object.assign(local, val), { deep: true });
</script>
```

- [ ] **Step 4.2: Commit**

```bash
git add resources/js/Components/Pae/PaeFormObjetivoContexto.vue
git commit -m "feat(pae): organism PaeFormObjetivoContexto - sub-aba 2 do RAT"
```

---

## Task 5: `PaeFormApontamentos.vue` — Sub-aba 3

**Files:**
- Create: `resources/js/Components/Pae/PaeFormApontamentos.vue`

Organism com lista dinâmica de apontamentos técnicos. Cada item tem badge numerado azul, textarea, sub-items com badge cyan e botões de adicionar/remover.

- [ ] **Step 5.1: Criar o componente**

Criar `resources/js/Components/Pae/PaeFormApontamentos.vue`:

```vue
<template>
  <div class="space-y-4">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
      <span class="w-1 h-6 bg-blue-500 rounded-full flex-shrink-0" />
      4. Apontamentos Técnicos Observados
    </h3>

    <div class="space-y-4">
      <div
        v-for="(item, index) in items"
        :key="item.id"
        class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4"
      >
        <div class="flex gap-3">
          <!-- Badge numerado -->
          <div class="flex-shrink-0 w-7 h-7 rounded-md bg-blue-600 text-white flex items-center justify-center text-sm font-bold">
            {{ index + 1 }}
          </div>

          <div class="flex-1 space-y-3">
            <!-- Textarea do item principal -->
            <textarea
              v-model="item.text"
              rows="3"
              :placeholder="`Digite o apontamento técnico ${index + 1}...`"
              class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
            />

            <!-- Sub-items -->
            <div v-if="item.children.length" class="ml-4 space-y-2">
              <div
                v-for="(child, childIndex) in item.children"
                :key="child.id"
                class="flex items-start gap-2"
              >
                <span class="flex-shrink-0 mt-2.5 px-1.5 py-0.5 rounded text-xs font-bold bg-cyan-600 text-white">
                  {{ index + 1 }}.{{ childIndex + 1 }}
                </span>
                <textarea
                  v-model="child.text"
                  rows="2"
                  :placeholder="`Sub-item ${index + 1}.${childIndex + 1}...`"
                  class="flex-1 bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
                />
                <button
                  type="button"
                  @click="$emit('remove-sub', index, childIndex)"
                  class="flex-shrink-0 mt-2 text-red-500/50 hover:text-red-500 transition-colors"
                  :title="`Remover sub-item ${index + 1}.${childIndex + 1}`"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>

            <!-- Botão adicionar sub-item -->
            <button
              type="button"
              @click="$emit('add-sub', index)"
              class="flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Adicionar Sub-item ({{ index + 1 }}.x)
            </button>
          </div>

          <!-- Botão remover item -->
          <button
            type="button"
            @click="$emit('remove-item', index)"
            :disabled="items.length === 1"
            class="flex-shrink-0 text-red-500/50 hover:text-red-500 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
            title="Remover apontamento"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>

      <!-- Botão adicionar item -->
      <button
        type="button"
        @click="$emit('add-item')"
        class="w-full py-4 flex items-center justify-center gap-2 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-blue-500/50 dark:hover:border-blue-500/50 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-400 font-medium text-sm transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Adicionar Apontamento (4.x)
      </button>
    </div>

    <div class="flex justify-end pt-2">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save')"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Apontamentos Técnicos
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    required: true,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save', 'add-item', 'remove-item', 'add-sub', 'remove-sub']);
</script>
```

- [ ] **Step 5.2: Commit**

```bash
git add resources/js/Components/Pae/PaeFormApontamentos.vue
git commit -m "feat(pae): organism PaeFormApontamentos - sub-aba 3 do RAT com lista dinamica"
```

---

## Task 6: `PaeFormConclusao.vue` — Sub-aba 4

**Files:**
- Create: `resources/js/Components/Pae/PaeFormConclusao.vue`

Idêntico ao `PaeFormApontamentos.vue` na estrutura. Diferenças: título "5. Conclusão", badge verde no item principal, badge teal no sub-item, e botão extra "Finalizar Relatório".

- [ ] **Step 6.1: Criar o componente**

Criar `resources/js/Components/Pae/PaeFormConclusao.vue`:

```vue
<template>
  <div class="space-y-4">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
      <span class="w-1 h-6 bg-green-500 rounded-full flex-shrink-0" />
      5. Conclusão
    </h3>

    <div class="space-y-4">
      <div
        v-for="(item, index) in items"
        :key="item.id"
        class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4"
      >
        <div class="flex gap-3">
          <!-- Badge numerado verde -->
          <div class="flex-shrink-0 w-7 h-7 rounded-md bg-green-600 text-white flex items-center justify-center text-sm font-bold">
            {{ index + 1 }}
          </div>

          <div class="flex-1 space-y-3">
            <textarea
              v-model="item.text"
              rows="3"
              :placeholder="`Digite a conclusão ${index + 1}...`"
              class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
            />

            <div v-if="item.children.length" class="ml-4 space-y-2">
              <div
                v-for="(child, childIndex) in item.children"
                :key="child.id"
                class="flex items-start gap-2"
              >
                <span class="flex-shrink-0 mt-2.5 px-1.5 py-0.5 rounded text-xs font-bold bg-teal-600 text-white">
                  {{ index + 1 }}.{{ childIndex + 1 }}
                </span>
                <textarea
                  v-model="child.text"
                  rows="2"
                  :placeholder="`Sub-item ${index + 1}.${childIndex + 1}...`"
                  class="flex-1 bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
                />
                <button
                  type="button"
                  @click="$emit('remove-sub', index, childIndex)"
                  class="flex-shrink-0 mt-2 text-red-500/50 hover:text-red-500 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>

            <button
              type="button"
              @click="$emit('add-sub', index)"
              class="flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Adicionar Sub-item ({{ index + 1 }}.x)
            </button>
          </div>

          <button
            type="button"
            @click="$emit('remove-item', index)"
            :disabled="items.length === 1"
            class="flex-shrink-0 text-red-500/50 hover:text-red-500 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>

      <button
        type="button"
        @click="$emit('add-item')"
        class="w-full py-4 flex items-center justify-center gap-2 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-green-500/50 dark:hover:border-green-500/50 rounded-xl text-slate-500 dark:text-slate-400 hover:text-green-400 font-medium text-sm transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Adicionar Conclusão (5.x)
      </button>
    </div>

    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save')"
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Conclusão
      </button>
      <button
        type="button"
        :disabled="saving"
        @click="$emit('finalizar')"
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Finalizar Relatório
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    required: true,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save', 'finalizar', 'add-item', 'remove-item', 'add-sub', 'remove-sub']);
</script>
```

- [ ] **Step 6.2: Commit**

```bash
git add resources/js/Components/Pae/PaeFormConclusao.vue
git commit -m "feat(pae): organism PaeFormConclusao - sub-aba 4 do RAT com finalizar"
```

---

## Task 7: Refatorar `PaeForm.vue` como orquestrador

**Files:**
- Modify: `resources/js/Components/Pae/PaeForm.vue`

Substitui o conteúdo atual (cards de empreendimento + dados do PAE) pelo orquestrador das 4 sub-abas. Instancia `usePaeFormulario`, define `tabConfig` com ícones SVG inline, e delega renders para cada organism.

- [ ] **Step 7.1: Ler o arquivo atual antes de modificar**

Confirmar conteúdo atual em `resources/js/Components/Pae/PaeForm.vue` (já lido no contexto — 136 linhas com cards de empreendimento/protocolo/documentos/ações).

- [ ] **Step 7.2: Substituir o conteúdo**

Reescrever `resources/js/Components/Pae/PaeForm.vue` completo:

```vue
<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <PaeFormTabs
      :active-tab="activeSubTab"
      :tabs="tabConfig"
      @tab-change="activeSubTab = $event"
    />

    <div v-if="activeSubTab === 1">
      <PaeFormInfoGerais
        :model-value="formulario.infoGerais"
        :municipios="municipios"
        :saving="formulario.saving"
        @save="handleSaveInfoGerais"
      />
    </div>

    <div v-else-if="activeSubTab === 2">
      <PaeFormObjetivoContexto
        :model-value="formulario.objetivoContexto"
        :saving="formulario.saving"
        @save="handleSaveObjetivo"
      />
    </div>

    <div v-else-if="activeSubTab === 3">
      <PaeFormApontamentos
        :items="formulario.apontamentos"
        :saving="formulario.saving"
        @save="handleSaveApontamentos"
        @add-item="formulario.addItem('apontamentos')"
        @remove-item="(i) => formulario.removeItem('apontamentos', i)"
        @add-sub="(i) => formulario.addSubItem('apontamentos', i)"
        @remove-sub="(i, j) => formulario.removeSubItem('apontamentos', i, j)"
      />
    </div>

    <div v-else-if="activeSubTab === 4">
      <PaeFormConclusao
        :items="formulario.conclusao"
        :saving="formulario.saving"
        @save="handleSaveConclusao"
        @finalizar="handleFinalizar"
        @add-item="formulario.addItem('conclusao')"
        @remove-item="(i) => formulario.removeItem('conclusao', i)"
        @add-sub="(i) => formulario.addSubItem('conclusao', i)"
        @remove-sub="(i, j) => formulario.removeSubItem('conclusao', i, j)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePaeFormulario } from '@/composables/pae/usePaeFormulario';
import PaeFormTabs from './PaeFormTabs.vue';
import PaeFormInfoGerais from './PaeFormInfoGerais.vue';
import PaeFormObjetivoContexto from './PaeFormObjetivoContexto.vue';
import PaeFormApontamentos from './PaeFormApontamentos.vue';
import PaeFormConclusao from './PaeFormConclusao.vue';

const props = defineProps({
  empreendimento: {
    type: Object,
    default: () => ({}),
  },
  municipios: {
    type: Object,
    default: () => ({}),
  },
  formulario: {
    type: Object,
    default: null,
  },
});

const activeSubTab = ref(1);

const formulario = usePaeFormulario(props.empreendimento, props.formulario);

const tabConfig = [
  {
    id: 1,
    label: 'Informações Gerais',
    icon: {
      template: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`,
    },
  },
  {
    id: 2,
    label: 'Objetivo e Contexto',
    icon: {
      template: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>`,
    },
  },
  {
    id: 3,
    label: 'Apontamentos Técnicos',
    icon: {
      template: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`,
    },
  },
  {
    id: 4,
    label: 'Conclusão',
    icon: {
      template: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    },
  },
];

const formularioId = props.formulario?.id ?? props.empreendimento?.formulario_id ?? null;

function handleSaveInfoGerais() {
  formulario.saveInfoGerais(formularioId);
}

function handleSaveObjetivo() {
  formulario.saveObjetivoContexto(formularioId);
}

function handleSaveApontamentos() {
  formulario.saveApontamentos(formularioId);
}

function handleSaveConclusao() {
  formulario.saveConclusao(formularioId);
}

function handleFinalizar() {
  formulario.finalizarRelatorio(formularioId);
}
</script>
```

- [ ] **Step 7.3: Verificar no browser**

Navegar até a página do PAE. A aba "Formulário PAE" deve mostrar as 4 sub-abas. Trocar entre elas e verificar que os campos aparecem. Sem erros no console.

- [ ] **Step 7.4: Commit**

```bash
git add resources/js/Components/Pae/PaeForm.vue
git commit -m "feat(pae): refatora PaeForm.vue como orquestrador das 4 sub-abas do RAT"
```

---

## Task 8: Atualizar `Pae.vue` para passar as novas props

**Files:**
- Modify: `resources/js/Pages/Pae.vue`

Adicionar as props `municipios` e `formulario` ao `defineProps` do `Pae.vue` e repassá-las para `PaeForm`.

- [ ] **Step 8.1: Adicionar props ao defineProps**

No `Pae.vue`, localizar o bloco `defineProps` e adicionar:

```js
const props = defineProps({
  empreendimento: { type: Object, default: () => ({}) },
  historyEvents:  { type: Array,  default: () => [] },
  committeeMembers: { type: Array, default: () => [] },
  empreendedor:   { type: Object, default: () => ({}) },
  documents:      { type: Array,  default: () => [] },
  atas:           { type: Array,  default: () => [] },
  lastUpdate:     { type: String, default: null },
  // Novas props para o formulário RAT:
  municipios:     { type: Object, default: () => ({}) },
  formulario:     { type: Object, default: null },
});
```

- [ ] **Step 8.2: Repassar para PaeForm no template**

Localizar o uso de `<PaeForm` no template e adicionar as novas props:

```html
<div v-if="Number(activeTab) === 1">
  <PaeForm
    :empreendimento="empreendimento"
    :municipios="props.municipios"
    :formulario="props.formulario"
    @save="handleSave"
    @save-draft="handleSaveDraft"
    @archive="handleArchive"
    @upload="handleUpload"
    @remove="handleRemove"
  />
</div>
```

- [ ] **Step 8.3: Verificar no browser**

A página do PAE deve carregar sem erros. A aba "Formulário PAE" mostra as 4 sub-abas. Testar troca de sub-abas, edição de campos e botões de salvar (os saves vão falhar com 404 até o backend estar implementado — esperado).

- [ ] **Step 8.4: Commit**

```bash
git add resources/js/Pages/Pae.vue
git commit -m "feat(pae): Pae.vue repassa props municipios e formulario para PaeForm"
```

---

## Self-Review

**Spec coverage:**
- [x] `usePaeFormulario.js` com state, inicialização pré-populada, saves parciais, lista dinâmica → Task 1
- [x] `PaeFormTabs.vue` molecule com padrão visual idêntico ao existente → Task 2
- [x] Aba 1 Informações Gerais — todos os 10 campos com selects corretos → Task 3
- [x] Aba 2 Objetivo e Contexto — textareas com texto default → Task 4
- [x] Aba 3 Apontamentos — lista dinâmica, add/remove item e sub-item → Task 5
- [x] Aba 4 Conclusão — lista dinâmica + botão Finalizar → Task 6
- [x] `PaeForm.vue` refatorado como orquestrador → Task 7
- [x] `Pae.vue` com novas props `municipios` e `formulario` → Task 8
- [x] Dark/light mode: todas as classes têm variante `dark:` → Tasks 3-7
- [x] Saving state: spinner + disabled em todos os botões de salvar → Tasks 3-6
- [x] Props `formulario` para modo edição (dados existentes) → Task 1 + 8

**Sem placeholders:** verificado — todos os steps têm código completo.

**Consistência de tipos:**
- `usePaeFormulario` retorna `saving` (ref Boolean) — usado como `:saving="formulario.saving"` → correto
- `addItem/removeItem/addSubItem/removeSubItem` recebem `section` string → chamados com `'apontamentos'` e `'conclusao'` → correto
- `formularioId` derivado de `formulario?.id ?? empreendimento?.formulario_id` — passado para todos os saves → correto
