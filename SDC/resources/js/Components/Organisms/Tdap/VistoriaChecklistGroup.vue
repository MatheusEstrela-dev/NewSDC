<template>
  <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ titulo }}</h3>
        <p v-if="descricao" class="text-xs text-slate-500 mt-0.5">{{ descricao }}</p>
      </div>
      <div class="flex items-center gap-3 text-xs">
        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
          OK: {{ checadosCount }}/{{ itens.length }}
        </span>
        <button
          v-if="!readonly"
          type="button"
          class="text-xs text-blue-600 hover:text-blue-800"
          @click="marcarTodos"
        >
          Marcar todos
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div
        v-for="item in itens"
        :key="item"
        class="rounded-lg border border-slate-200 dark:border-slate-700/40 p-3"
        :class="form[item] ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-500/30' : 'bg-slate-50 dark:bg-slate-800/40'"
      >
        <label class="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            :checked="!!form[item]"
            :disabled="readonly"
            class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
            @change="$emit('update', item, $event.target.checked)"
          />
          <span class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ rotulo(item) }}</span>
        </label>
        <textarea
          v-if="!readonly"
          :value="form[`${item}_obs`] ?? ''"
          @input="$emit('update-obs', item, $event.target.value)"
          rows="1"
          maxlength="500"
          placeholder="Observação (opcional)"
          class="mt-2 block w-full text-xs border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
        />
        <p v-else-if="form[`${item}_obs`]" class="mt-2 text-xs text-slate-600 dark:text-slate-400 italic">
          {{ form[`${item}_obs`] }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  titulo: { type: String, required: true },
  descricao: { type: String, default: '' },
  itens: { type: Array, required: true },     // ['documento','para_choque_d',...]
  form: { type: Object, required: true },     // tem chaves item + item_obs
  readonly: { type: Boolean, default: false },
  rotulos: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update', 'update-obs']);

const checadosCount = computed(() => props.itens.filter(i => !!props.form[i]).length);

function rotulo(item) {
  if (props.rotulos[item]) return props.rotulos[item];
  // Converte snake_case em "Snake Case"
  return item
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase());
}

function marcarTodos() {
  props.itens.forEach(i => emit('update', i, true));
}
</script>
