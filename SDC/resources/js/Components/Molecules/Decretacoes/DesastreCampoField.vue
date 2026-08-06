<template>
  <div>
    <label
      v-if="campo.tipo !== 'radio'"
      class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1"
    >
      {{ campo.titulo }}
    </label>

    <!-- Radio -->
    <RadioInput
      v-if="campo.tipo === 'radio'"
      :model-value="valorRadio"
      :value="MARCADO"
      :name="`radio-item-${itemId}-${municipioId}`"
      :label="campo.titulo"
      @update:model-value="emit('update:valor', $event)"
    />

    <!-- Select -->
    <SelectInput
      v-else-if="campo.tipo === 'select'"
      :model-value="campo.valor ?? ''"
      :options="selectOptions"
      size="sm"
      @update:model-value="emit('update:valor', $event)"
    />

    <!-- Currency -->
    <CurrencyInput
      v-else-if="campo.tipo === 'currency'"
      :model-value="campo.valor"
      size="sm"
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
import { formatNumber } from '@/Composables/ui/useDesastreMask';
import { MARCADO, NAO_MARCADO } from '@/Composables/decretacoes/useDesastreRadio';

// Chaves normalizadas (minusculas, sem acento): os titulos cadastrados no banco
// vem acentuados ("População do município atingida"), e comparar com a string
// crua deixava o select sem nenhuma opcao.
const SELECT_OPTIONS_MAP = {
  'populacao do municipio atingida': ['0 a 5%', '5 a 10%', '10 a 20%', 'Mais de 20%'],
  'area atingida': ['Ate 40%', 'Mais de 40%'],
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

function normaliza(texto) {
  return String(texto ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();
}

const selectOptions = computed(() => {
  const base = SELECT_OPTIONS_MAP[normaliza(props.campo.titulo)] ?? [];
  const atual = props.campo.valor;

  if (atual === null || atual === undefined || atual === '') {
    return base;
  }

  // A base legada guarda o rotulo completo ("DE 0% A 5% DA POPULACAO AFETADA")
  // e valores fora da lista. Sem manter o valor atual como opcao, o select
  // abriria vazio e o proximo salvamento apagaria o dado historico.
  return base.includes(String(atual)) ? base : [String(atual), ...base];
});

// Convencao gravada no banco: '1' no campo marcado e '0' no irmao (nao o id do
// campo). Comparar como string faz o radio aparecer marcado ao reabrir o
// processo, ja que `valor` volta do banco como texto.
const valorRadio = computed(() => {
  const valor = props.campo.valor;

  return valor === null || valor === undefined ? NAO_MARCADO : String(valor);
});

function handleNumberInput(value) {
  emit('update:valor', formatNumber(value));
}
</script>
