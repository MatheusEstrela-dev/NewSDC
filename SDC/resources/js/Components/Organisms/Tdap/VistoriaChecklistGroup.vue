<template>
  <CollapsibleSection
    namespace="tdap-vistoria"
    :section-id="sectionId"
    :title="titulo"
    :subtitle="descricao"
    :status-text="`OK: ${checadosCount}/${itens.length}`"
    :tom="checadosCount === itens.length ? 'success' : 'info'"
    :expandido-por-padrao="expandidoPorPadrao"
    :sanfona-no-mobile="true"
  >
    <div class="flex items-center justify-end gap-3 text-xs mb-3">
      <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
        OK: {{ checadosCount }}/{{ itens.length }}
      </span>
      <button
        v-if="!readonly && itens.length"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
        :class="todosMarcados
          ? 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800'
          : 'border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 dark:border-blue-500/40 dark:text-blue-300 dark:bg-blue-900/20 dark:hover:bg-blue-900/40'"
        :aria-pressed="todosMarcados"
        @click="alternarTodos"
      >
        <svg v-if="todosMarcados" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L8.94 10l-4.72 4.72a.75.75 0 1 0 1.06 1.06L10 11.06l4.72 4.72a.75.75 0 1 0 1.06-1.06L11.06 10l4.72-4.72a.75.75 0 0 0-1.06-1.06L10 8.94 5.28 4.22Z" />
        </svg>
        <svg v-else class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
        </svg>
        {{ todosMarcados ? 'Desmarcar todos' : 'Marcar todos' }}
      </button>
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
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';

const props = defineProps({
  /** Identificador da secao dentro do namespace 'tdap-vistoria'. */
  sectionId: { type: String, required: true },
  titulo: { type: String, required: true },
  descricao: { type: String, default: '' },
  expandidoPorPadrao: { type: Boolean, default: true },
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

const todosMarcados = computed(
  () => props.itens.length > 0 && checadosCount.value === props.itens.length,
);

/** Alterna: se tudo ja esta marcado, desmarca; senao marca so os pendentes. */
function alternarTodos() {
  const novoValor = !todosMarcados.value;
  props.itens.forEach(i => {
    if (!!props.form[i] !== novoValor) emit('update', i, novoValor);
  });
}
</script>
