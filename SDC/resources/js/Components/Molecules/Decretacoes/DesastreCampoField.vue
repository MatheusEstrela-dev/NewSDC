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
