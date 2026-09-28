<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-slate-100">Assunto</h2>
    <FilterField :model-value="assuntoId ?? ''" label="" type="select" :options="assuntos" :disabled="!podeEditar"
      @update:model-value="(v) => salvar({ assunto_id: v || null, campos_customizados: {} })" />
    <div v-if="campos.length" class="mt-4 grid gap-3 sm:grid-cols-2">
      <CampoDinamico v-for="c in campos" :key="c.label" :campo="c" :model-value="valores[c.label]" :disabled="!podeEditar"
        :erro="erros[`campos_customizados.${c.label}`]"
        @update:model-value="(v) => (valores[c.label] = v)" @blur="salvarCampos" />
    </div>
  </section>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import CampoDinamico from '@/Components/Molecules/Demandas/CampoDinamico.vue';
import { useDemandaAutosave } from '@/Composables/demandas';

const props = defineProps({
  demandaId: { type: Number, required: true },
  assuntoId: { type: Number, default: null },
  assuntos: { type: Array, default: () => [] },
  campos: { type: Array, default: () => [] },
  valoresIniciais: { type: Object, default: () => ({}) },
  podeEditar: { type: Boolean, default: false },
});

const valores = reactive({ ...props.valoresIniciais });
watch(() => props.valoresIniciais, (v) => Object.assign(valores, v));
const erros = computed(() => usePage().props.errors ?? {});
const { salvar } = useDemandaAutosave(props.demandaId);

function salvarCampos() {
  const somenteDoAssunto = Object.fromEntries(props.campos.map((c) => [c.label, valores[c.label] ?? (c.tipo === 'checkbox' ? false : '')]));
  salvar({ campos_customizados: somenteDoAssunto });
}
</script>
