# Desastres Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar a tela de edicao de dados de desastres no NewSDC com paridade funcional completa ao legado SDC, mantendo UX NewSDC e Atomic Design.

**Architecture:** Decomposicao Atomic Design em Atoms (CurrencyInput, ProtocoloFideInput), composable (useDesastreMask), Molecules (DesastreCampoField, DesastreTotalBadge), Organisms (DesastreAccordion, MunicipioDesastreSection) e atualizacao slim do Template. Backend recebe delta minimo: validacao de tipos expandida no DesastreDataRequest.

**Tech Stack:** Vue 3 (Composition API, `<script setup>`), Inertia.js, Tailwind CSS, Heroicons, PHP 8.2, Laravel 10, PHPUnit.

---

## Mapa de Arquivos

| Arquivo | Acao |
|---------|------|
| `app/Modules/Decretacoes/Requests/DesastreDataRequest.php` | Modificar — adicionar radio, select, textarea ao `in:` |
| `tests/Unit/Decretacoes/Requests/DesastreDataRequestTest.php` | Criar — testes de validacao |
| `resources/js/composables/ui/useDesastreMask.js` | Criar — funcoes de mascara puras |
| `resources/js/Components/Atoms/Input/CurrencyInput.vue` | Criar |
| `resources/js/Components/Atoms/Input/ProtocoloFideInput.vue` | Criar |
| `resources/js/Components/Molecules/Decretacoes/DesastreCampoField.vue` | Criar |
| `resources/js/Components/Molecules/Decretacoes/DesastreTotalBadge.vue` | Criar |
| `resources/js/Components/Organisms/Decretacoes/DesastreAccordion.vue` | Criar |
| `resources/js/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue` | Criar |
| `resources/js/Templates/Decretacoes/ProcessoDesastresEditTemplate.vue` | Modificar — substituir corpo |

---

## Task 1: Backend — Expandir validacao de tipos de campo

**Files:**
- Modify: `app/Modules/Decretacoes/Requests/DesastreDataRequest.php:44`
- Create: `tests/Unit/Decretacoes/Requests/DesastreDataRequestTest.php`

- [ ] **Step 1.1: Criar o teste de validacao**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Decretacoes\Requests;

use App\Modules\Decretacoes\Requests\DesastreDataRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DesastreDataRequestTest extends TestCase
{
    private function makeRequest(array $overrides = []): array
    {
        return array_merge([
            'municipios' => [
                [
                    'id' => 1,
                    'n_protocolo_fide' => null,
                    'categorias' => [
                        [
                            'id' => 1,
                            'desastres' => [
                                [
                                    'id' => 1,
                                    'descricao' => null,
                                    'items' => [
                                        [
                                            'id' => 1,
                                            'campos' => [
                                                [
                                                    'id' => 1,
                                                    'titulo' => 'Quantidade',
                                                    'valor' => '100',
                                                    'tipo' => 'number',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new DesastreDataRequest();
        return Validator::make($data, $request->rules());
    }

    public function test_tipo_number_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'number';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_currency_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'currency';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_radio_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'radio';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_select_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'select';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_textarea_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'textarea';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_text_e_valido(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'text';

        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_invalido_falha(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'checkbox';

        $this->assertTrue($this->validate($data)->fails());
    }

    public function test_valor_nulo_e_aceito(): void
    {
        $data = $this->makeRequest();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['valor'] = null;

        $this->assertFalse($this->validate($data)->fails());
    }
}
```

- [ ] **Step 1.2: Rodar o teste para confirmar falha**

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/SDC
php artisan test tests/Unit/Decretacoes/Requests/DesastreDataRequestTest.php
```

Esperado: `test_tipo_radio_e_valido`, `test_tipo_select_e_valido`, `test_tipo_textarea_e_valido` falham com validation error.

- [ ] **Step 1.3: Atualizar a regra de validacao**

No arquivo `app/Modules/Decretacoes/Requests/DesastreDataRequest.php`, linha 44, substituir:

```php
'municipios.*.categorias.*.desastres.*.items.*.campos.*.tipo' => 'required|string|in:number,currency,text',
```

por:

```php
'municipios.*.categorias.*.desastres.*.items.*.campos.*.tipo' => 'required|string|in:number,currency,text,radio,select,textarea',
```

- [ ] **Step 1.4: Rodar o teste para confirmar aprovacao**

```bash
php artisan test tests/Unit/Decretacoes/Requests/DesastreDataRequestTest.php
```

Esperado: todos os 8 testes passam.

- [ ] **Step 1.5: Commit**

```bash
git add app/Modules/Decretacoes/Requests/DesastreDataRequest.php
git add tests/Unit/Decretacoes/Requests/DesastreDataRequestTest.php
git commit -m "feat(decretacoes): expandir tipos de campo aceitos na validacao"
```

---

## Task 2: Composable — useDesastreMask

**Files:**
- Create: `resources/js/composables/ui/useDesastreMask.js`

- [ ] **Step 2.1: Criar o composable**

```js
// resources/js/composables/ui/useDesastreMask.js

/**
 * Funcoes puras de mascara para campos de desastre.
 * Sem estado reativo — pode ser importado diretamente.
 */

/**
 * Formata numero float como BRL sem simbolo.
 * Ex: 1234.5 -> "1.234,50"
 */
export function formatCurrency(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = typeof value === 'number' ? value : parseFloat(String(value).replace(/\./g, '').replace(',', '.'));
    if (isNaN(num)) return '';
    return num.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Formata string numerica com separador de milhar.
 * Ex: "1234" -> "1.234"
 */
export function formatNumber(value) {
    if (value === null || value === undefined || value === '') return '';
    const digits = String(value).replace(/\D/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Aplica mascaras a todos os campos currency/number de um array de municipios.
 * Muta o array in-place (chamado em onMounted).
 */
export function formatOnLoad(municipios) {
    if (!Array.isArray(municipios)) return;

    municipios.forEach((municipio) => {
        (municipio.categorias ?? []).forEach((categoria) => {
            (categoria.desastres ?? []).forEach((desastre) => {
                (desastre.items ?? []).forEach((item) => {
                    (item.campos ?? []).forEach((campo) => {
                        if (campo.tipo === 'currency' && campo.valor != null) {
                            const raw = String(campo.valor).replace(/\D/g, '');
                            campo.valor = formatCurrency(parseFloat(raw) / 100);
                        } else if (campo.tipo === 'number' && campo.valor != null) {
                            campo.valor = formatNumber(String(campo.valor));
                        }
                    });
                });
            });
        });
    });
}
```

- [ ] **Step 2.2: Commit**

```bash
git add resources/js/composables/ui/useDesastreMask.js
git commit -m "feat(decretacoes): composable useDesastreMask com mascaras BRL e number"
```

---

## Task 3: Atom — CurrencyInput

**Files:**
- Create: `resources/js/Components/Atoms/Input/CurrencyInput.vue`

- [ ] **Step 3.1: Criar o componente**

```vue
<!-- resources/js/Components/Atoms/Input/CurrencyInput.vue -->
<template>
  <div class="relative flex items-center">
    <span class="absolute left-3 text-sm text-slate-400 dark:text-slate-500 select-none">R$</span>
    <input
      type="text"
      :value="modelValue"
      :disabled="disabled"
      :placeholder="placeholder"
      :class="[
        'atom-input atom-input-md w-full pl-9',
        disabled ? 'atom-input-disabled' : (modelValue ? 'atom-input-filled' : 'atom-input-normal'),
      ]"
      @keyup="handleKeyup"
      @blur="$emit('blur', $event)"
    />
  </div>
</template>

<script setup>
import { formatCurrency } from '@/composables/ui/useDesastreMask';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: '0,00',
  },
});

const emit = defineEmits(['update:modelValue', 'blur']);

function handleKeyup(event) {
  const raw = event.target.value.replace(/\D/g, '');
  const formatted = raw ? formatCurrency(parseFloat(raw) / 100) : '';
  emit('update:modelValue', formatted);
}
</script>
```

- [ ] **Step 3.2: Verificar renderizacao manual**

Abrir o formulario em `localhost:19444/decretacoes/{id}/desastres/edit` e confirmar que campos currency renderizam com prefixo R$.

- [ ] **Step 3.3: Commit**

```bash
git add resources/js/Components/Atoms/Input/CurrencyInput.vue
git commit -m "feat(atoms): CurrencyInput com mascara BRL"
```

---

## Task 4: Atom — ProtocoloFideInput

**Files:**
- Create: `resources/js/Components/Atoms/Input/ProtocoloFideInput.vue`

- [ ] **Step 4.1: Criar o componente**

```vue
<!-- resources/js/Components/Atoms/Input/ProtocoloFideInput.vue -->
<template>
  <div>
    <input
      type="text"
      :value="modelValue"
      :class="[
        'atom-input atom-input-md w-full',
        modelValue ? 'atom-input-filled' : 'atom-input-normal',
      ]"
      placeholder="MG-F-XXXXXXX-XXXXX-XXXXXXXX"
      maxlength="25"
      @input="handleInput"
    />
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Formato esperado: MG-F-XXXXXXX-XXXXX-XXXXXXXX
    </p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

function handleInput(event) {
  const digits = event.target.value.replace(/\D/g, '').substring(0, 20);
  let formatted = 'MG-F-';
  if (digits.length > 0) formatted += digits.substring(0, 7);
  if (digits.length > 7) formatted += '-' + digits.substring(7, 12);
  if (digits.length > 12) formatted += '-' + digits.substring(12, 20);
  emit('update:modelValue', formatted);
}
</script>
```

- [ ] **Step 4.2: Commit**

```bash
git add resources/js/Components/Atoms/Input/ProtocoloFideInput.vue
git commit -m "feat(atoms): ProtocoloFideInput com mascara MG-F"
```

---

## Task 5: Molecule — DesastreCampoField

**Files:**
- Create: `resources/js/Components/Molecules/Decretacoes/DesastreCampoField.vue`

- [ ] **Step 5.1: Criar o componente**

```vue
<!-- resources/js/Components/Molecules/Decretacoes/DesastreCampoField.vue -->
<template>
  <div>
    <label
      v-if="campo.tipo !== 'radio'"
      class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1"
    >
      {{ campo.titulo }}
    </label>

    <!-- Radio -->
    <RadioInput
      v-if="campo.tipo === 'radio'"
      :model-value="campo.valor"
      :value="campo.id"
      :name="`radio-item-${itemId}-${municipioId}`"
      :label="campo.titulo"
      @update:model-value="emit('update:valor', $event)"
    />

    <!-- Select -->
    <SelectInput
      v-else-if="campo.tipo === 'select'"
      :model-value="campo.valor"
      :options="selectOptions"
      size="sm"
      @update:model-value="emit('update:valor', $event)"
    />

    <!-- Currency -->
    <CurrencyInput
      v-else-if="campo.tipo === 'currency'"
      :model-value="campo.valor"
      @update:model-value="emit('update:valor', $event)"
    />

    <!-- Number -->
    <TextInput
      v-else-if="campo.tipo === 'number'"
      :model-value="campo.valor"
      type="text"
      size="sm"
      @update:model-value="handleNumberInput"
    />

    <!-- Textarea -->
    <textarea
      v-else-if="campo.tipo === 'textarea'"
      :value="campo.valor"
      rows="3"
      class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
      @input="emit('update:valor', $event.target.value)"
    />

    <!-- Text (default) -->
    <TextInput
      v-else
      :model-value="campo.valor"
      size="sm"
      @update:model-value="emit('update:valor', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import RadioInput from '@/Components/Atoms/Input/RadioInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import CurrencyInput from '@/Components/Atoms/Input/CurrencyInput.vue';
import { formatNumber } from '@/composables/ui/useDesastreMask';

const SELECT_OPTIONS_MAP = {
  'Populacao do municipio atingida': ['0 a 5%', '5 a 10%', '10 a 20%', 'Mais de 20%'],
  'Area atingida': ['Ate 40%', 'Mais de 40%'],
};

const props = defineProps({
  campo: {
    type: Object,
    required: true,
  },
  itemId: {
    type: Number,
    required: true,
  },
  municipioId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:valor']);

const selectOptions = computed(() => SELECT_OPTIONS_MAP[props.campo.titulo] ?? []);

function handleNumberInput(value) {
  emit('update:valor', formatNumber(value));
}
</script>
```

- [ ] **Step 5.2: Commit**

```bash
git add resources/js/Components/Molecules/Decretacoes/DesastreCampoField.vue
git commit -m "feat(molecules): DesastreCampoField com switch de todos os tipos"
```

---

## Task 6: Molecule — DesastreTotalBadge

**Files:**
- Create: `resources/js/Components/Molecules/Decretacoes/DesastreTotalBadge.vue`

- [ ] **Step 6.1: Criar o componente**

```vue
<!-- resources/js/Components/Molecules/Decretacoes/DesastreTotalBadge.vue -->
<template>
  <div class="flex flex-wrap items-center gap-1">
    <Badge
      v-for="(valor, titulo) in totals"
      :key="titulo"
      variant="info"
      size="sm"
    >
      {{ titulo }}: <strong class="ml-1">{{ valor }}</strong>
    </Badge>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { formatCurrency, formatNumber } from '@/composables/ui/useDesastreMask';

const props = defineProps({
  desastre: {
    type: Object,
    required: true,
  },
});

const totals = computed(() => {
  const acc = {};
  const types = {};

  (props.desastre.items ?? []).forEach((item) => {
    (item.campos ?? []).forEach((campo) => {
      if (campo.tipo !== 'number' && campo.tipo !== 'currency') return;

      if (!(campo.titulo in acc)) {
        acc[campo.titulo] = 0;
        types[campo.titulo] = campo.tipo;
      }

      const raw = String(campo.valor ?? '0').replace(/\D/g, '');
      if (!raw) return;

      if (campo.tipo === 'currency') {
        acc[campo.titulo] += parseFloat(raw) / 100;
      } else {
        acc[campo.titulo] += parseInt(raw, 10);
      }
    });
  });

  const result = {};
  Object.keys(acc).forEach((titulo) => {
    result[titulo] = types[titulo] === 'currency'
      ? formatCurrency(acc[titulo])
      : formatNumber(String(acc[titulo]));
  });

  return result;
});
</script>
```

- [ ] **Step 6.2: Commit**

```bash
git add resources/js/Components/Molecules/Decretacoes/DesastreTotalBadge.vue
git commit -m "feat(molecules): DesastreTotalBadge com soma reativa de campos"
```

---

## Task 7: Organism — DesastreAccordion

**Files:**
- Create: `resources/js/Components/Organisms/Decretacoes/DesastreAccordion.vue`

- [ ] **Step 7.1: Criar o componente**

```vue
<!-- resources/js/Components/Organisms/Decretacoes/DesastreAccordion.vue -->
<template>
  <div class="border border-slate-200 dark:border-slate-700/50 rounded-lg overflow-hidden">
    <!-- Header colapsavel -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <span class="font-semibold text-sm text-slate-700 dark:text-slate-200 truncate">
          {{ desastre.titulo }}
        </span>
        <DesastreTotalBadge :desastre="localDesastre" />
      </div>
      <ChevronDownIcon
        :class="['w-4 h-4 text-slate-400 transition-transform shrink-0 ml-2', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Body -->
    <div v-show="isExpanded" class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-4">
      <!-- Informacao -->
      <p v-if="desastre.informacao" class="text-xs text-slate-500 dark:text-slate-400 italic">
        {{ desastre.informacao }}
      </p>

      <!-- Descricao -->
      <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
          Descricao do Desastre
        </label>
        <textarea
          v-model="localDesastre.descricao"
          rows="3"
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
          @input="emitUpdate"
        />
      </div>

      <!-- Tabela de itens -->
      <div v-if="localDesastre.items && localDesastre.items.length > 0" class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-slate-100 dark:bg-slate-800/50">
              <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 w-1/3 border-b border-slate-200 dark:border-slate-700/50">
                Item
              </th>
              <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/50">
                Campos
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, iIndex) in localDesastre.items"
              :key="item.id"
              class="border-b border-slate-100 dark:border-slate-700/30 last:border-0"
            >
              <td class="px-3 py-3 align-top">
                <p class="font-medium text-slate-700 dark:text-slate-300 text-xs">{{ item.titulo }}</p>
                <p v-if="item.observacao" class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                  {{ item.observacao }}
                </p>
              </td>
              <td class="px-3 py-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  <DesastreCampoField
                    v-for="(campo, fIndex) in item.campos"
                    :key="campo.id"
                    :campo="campo"
                    :item-id="item.id"
                    :municipio-id="municipioId"
                    @update:valor="updateCampo(iIndex, fIndex, $event)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-center py-4">
        <Text size="xs" color="muted" class="italic">Nenhum dado registrado para este item.</Text>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import Text from '@/Components/Atoms/Typography/Text.vue';
import DesastreTotalBadge from '@/Components/Molecules/Decretacoes/DesastreTotalBadge.vue';
import DesastreCampoField from '@/Components/Molecules/Decretacoes/DesastreCampoField.vue';

const props = defineProps({
  desastre: {
    type: Object,
    required: true,
  },
  municipioId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:desastre']);

const isExpanded = ref(false);
const localDesastre = ref(JSON.parse(JSON.stringify(props.desastre)));

watch(() => props.desastre, (val) => {
  localDesastre.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

function updateCampo(iIndex, fIndex, valor) {
  localDesastre.value.items[iIndex].campos[fIndex].valor = valor;
  emitUpdate();
}

function emitUpdate() {
  emit('update:desastre', JSON.parse(JSON.stringify(localDesastre.value)));
}
</script>
```

- [ ] **Step 7.2: Commit**

```bash
git add resources/js/Components/Organisms/Decretacoes/DesastreAccordion.vue
git commit -m "feat(organisms): DesastreAccordion colapsavel com badges e tabela de campos"
```

---

## Task 8: Organism — MunicipioDesastreSection

**Files:**
- Create: `resources/js/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue`

- [ ] **Step 8.1: Criar o componente**

```vue
<!-- resources/js/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue -->
<template>
  <div class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden">
    <!-- Header do Municipio -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3">
        <MapPinIcon class="w-5 h-5 text-primary-500 shrink-0" />
        <span class="font-semibold text-slate-800 dark:text-slate-200">
          {{ municipio.nome || municipio.p_nome || `Municipio ${municipio.id}` }}
        </span>
        <span v-if="localMunicipio.n_protocolo_fide" class="text-xs text-slate-400 hidden sm:inline">
          ({{ localMunicipio.n_protocolo_fide }})
        </span>
      </div>
      <ChevronDownIcon
        :class="['w-5 h-5 text-slate-400 transition-transform shrink-0', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Conteudo do Municipio -->
    <div v-show="isExpanded" class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-6">
      <!-- Protocolo FIDE -->
      <div class="max-w-sm">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
          N. Protocolo FIDE
        </label>
        <ProtocoloFideInput
          :model-value="localMunicipio.n_protocolo_fide"
          @update:model-value="localMunicipio.n_protocolo_fide = $event; emitUpdate()"
        />
      </div>

      <!-- Categorias -->
      <div
        v-for="categoria in localMunicipio.categorias"
        :key="categoria.id"
        class="space-y-3"
      >
        <!-- Header da Categoria (nao colapsavel) -->
        <div class="flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700/50">
          <div class="w-1 h-5 bg-primary-500 rounded-full"></div>
          <h4 class="text-sm font-semibold text-primary-600 dark:text-primary-400">
            {{ categoria.titulo }}
          </h4>
        </div>

        <!-- Desastres -->
        <div class="space-y-2 pl-3">
          <DesastreAccordion
            v-for="(desastre, dIndex) in categoria.desastres"
            :key="desastre.id"
            :desastre="desastre"
            :municipio-id="municipio.id"
            @update:desastre="updateDesastre(categoria.id, dIndex, $event)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { MapPinIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import ProtocoloFideInput from '@/Components/Atoms/Input/ProtocoloFideInput.vue';
import DesastreAccordion from '@/Components/Organisms/Decretacoes/DesastreAccordion.vue';

const props = defineProps({
  municipio: {
    type: Object,
    required: true,
  },
  mIndex: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:municipio']);

const isExpanded = ref(props.mIndex === 0);
const localMunicipio = ref(JSON.parse(JSON.stringify(props.municipio)));

watch(() => props.municipio, (val) => {
  localMunicipio.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

function updateDesastre(categoriaId, dIndex, updatedDesastre) {
  const cat = localMunicipio.value.categorias.find((c) => c.id === categoriaId);
  if (cat) {
    cat.desastres[dIndex] = updatedDesastre;
    emitUpdate();
  }
}

function emitUpdate() {
  emit('update:municipio', JSON.parse(JSON.stringify(localMunicipio.value)));
}
</script>
```

- [ ] **Step 8.2: Commit**

```bash
git add resources/js/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue
git commit -m "feat(organisms): MunicipioDesastreSection colapsavel com protocolo FIDE e categorias"
```

---

## Task 9: Template — Atualizar ProcessoDesastresEditTemplate

**Files:**
- Modify: `resources/js/Templates/Decretacoes/ProcessoDesastresEditTemplate.vue`

- [ ] **Step 9.1: Substituir o conteudo do template**

Substituir o arquivo completo por:

```vue
<!-- resources/js/Templates/Decretacoes/ProcessoDesastresEditTemplate.vue -->
<template>
  <div class="processo-desastres-edit-template">
    <!-- Header -->
    <div class="page-header mb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
          <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 dark:text-amber-400" />
        </div>
        <div>
          <Heading level="h1" size="2xl">Editar Dados do Desastre</Heading>
          <Text size="sm" color="muted" class="mt-1">
            Atualize os dados de danos e prejuizos por municipio
          </Text>
        </div>
      </div>
    </div>

    <!-- Resumo do Processo -->
    <div class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Protocolo</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.n_protocolo_fide || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Tipo de Desastre</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.tipo_desastre_nome || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">COBRADE</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.tipo_desastre_cobrade || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Status</Text>
          <StatusBadge :status="processo?.status" />
        </div>
      </div>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="handleSubmit">
      <!-- Municipios -->
      <div class="space-y-4 mb-6">
        <MunicipioDesastreSection
          v-for="(municipio, mIndex) in localMunicipios"
          :key="municipio.id"
          :municipio="municipio"
          :m-index="mIndex"
          @update:municipio="localMunicipios[mIndex] = $event"
        />
      </div>

      <!-- Empty State -->
      <div
        v-if="!localMunicipios || localMunicipios.length === 0"
        class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-12 text-center"
      >
        <MapPinIcon class="w-16 h-16 text-slate-400 mx-auto mb-4" />
        <Heading level="h3" color="muted">Nenhum municipio vinculado</Heading>
        <Text size="sm" color="muted" class="mt-2">
          Adicione municipios ao processo antes de editar os dados de desastres
        </Text>
      </div>

      <!-- Acoes -->
      <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700/50">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          @click="$emit('cancel')"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 rounded-lg transition-colors flex items-center gap-2"
        >
          <ArrowPathIcon v-if="form.processing" class="w-4 h-4 animate-spin" />
          Salvar Alteracoes
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { ExclamationTriangleIcon, MapPinIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import MunicipioDesastreSection from '@/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue';
import { formatOnLoad } from '@/composables/ui/useDesastreMask';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  form: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const localMunicipios = ref(JSON.parse(JSON.stringify(props.municipios)));

watch(() => props.municipios, (val) => {
  localMunicipios.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

onMounted(() => {
  formatOnLoad(localMunicipios.value);
});

function handleSubmit() {
  props.form.municipios = localMunicipios.value;
  emit('submit');
}
</script>

<style scoped>
.processo-desastres-edit-template {
  @apply w-full;
}
</style>
```

- [ ] **Step 9.2: Testar o fluxo completo**

1. Abrir `localhost:19444/decretacoes`
2. Clicar em um processo existente → "Editar Dados do Desastre"
3. Verificar:
   - Municipios colapsaveis, primeiro aberto
   - Protocolo FIDE aceita mascara MG-F-XXXXXXX-XXXXX-XXXXXXXX
   - Categorias com header azul fixo
   - Desastres colapsaveis com badges de total
   - Campos currency mostram R$ e formatam no keyup
   - Campos number formatam com separador de milhar
   - Botao "Salvar Alteracoes" submete sem erro 422

- [ ] **Step 9.3: Commit final**

```bash
git add resources/js/Templates/Decretacoes/ProcessoDesastresEditTemplate.vue
git commit -m "feat(decretacoes): tela de edicao de desastres com Atomic Design completo"
```

---

## Self-Review

**Cobertura do spec:**
- [x] Municipios colapsaveis, primeiro aberto — Task 8 (`isExpanded = ref(mIndex === 0)`)
- [x] Protocolo FIDE com mascara — Tasks 4 + 8
- [x] Categorias header fixo (nao colapsavel) — Task 8
- [x] Desastres colapsaveis com badges reativos — Tasks 6 + 7
- [x] Textarea descricao por desastre — Task 7
- [x] Tipos: radio, select, currency, number, text, textarea — Task 5
- [x] Mascaras no load e no keyup — Tasks 2 + 3 + 9 (`formatOnLoad` em `onMounted`)
- [x] Select com opcoes por `campo.titulo` — Task 5 (`SELECT_OPTIONS_MAP`)
- [x] Submit via Inertia `form.post()` — Task 9 (sem alteracao no `ProcessoDesastresEdit.vue`)
- [x] UX NewSDC dark mode Tailwind — todos os templates usam classes `dark:` e `primary-`
- [x] Atomic Design — sem logica de negocio em atoms

**Consistencia de tipos:**
- `DesastreCampoField` emite `update:valor` (string) — `DesastreAccordion` recebe em `updateCampo(iIndex, fIndex, valor)`
- `DesastreAccordion` emite `update:desastre` (Object) — `MunicipioDesastreSection` recebe em `updateDesastre`
- `MunicipioDesastreSection` emite `update:municipio` (Object) — Template atualiza `localMunicipios[mIndex]`
- `formatOnLoad` muta array in-place — chamado em `onMounted` com `localMunicipios.value`

**Placeholders:** nenhum TBD encontrado.
