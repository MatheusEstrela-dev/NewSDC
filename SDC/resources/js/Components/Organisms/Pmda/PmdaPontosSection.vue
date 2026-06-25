<template>
  <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60">
    <header class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Pontos de Captação</h2>
      <span class="text-xs text-slate-500 dark:text-slate-400">{{ pontos.length }} vinculado(s)</span>
    </header>

    <!-- Vincular ponto disponivel do municipio -->
    <form class="flex flex-wrap items-end gap-2" @submit.prevent="vincular">
      <label class="flex-1 min-w-[220px]">
        <span class="mb-1 block text-xs text-slate-600 dark:text-slate-400">Ponto disponível no município</span>
        <select v-model="form.ponto_id" class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <option value="">Selecione…</option>
          <option v-for="p in naoVinculados" :key="p.id" :value="p.id">
            {{ p.nome }} ({{ p.capacidade }} m³)
          </option>
        </select>
      </label>
      <button
        type="submit"
        :disabled="!form.ponto_id || form.processing"
        class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
      >
        Vincular
      </button>
    </form>

    <p v-if="!pontos.length" class="py-3 text-center text-sm text-slate-400">Nenhum ponto vinculado a este PMDA.</p>

    <ul v-else class="divide-y divide-slate-100 rounded-md border border-slate-100 dark:divide-slate-800 dark:border-slate-700/50">
      <li v-for="p in pontos" :key="p.id" class="flex items-center justify-between px-3 py-2 text-sm">
        <span class="text-slate-700 dark:text-slate-300">{{ p.nome }} <span class="text-slate-400">— {{ p.capacidade }} m³</span></span>
        <button type="button" class="text-xs text-red-600 hover:underline" @click="desvincular(p.id)">Desvincular</button>
      </li>
    </ul>

    <p v-if="!disponiveis.length" class="text-xs text-amber-600 dark:text-amber-400">
      Nenhum ponto de captação cadastrado para este município.
    </p>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  planoId: { type: [Number, String], required: true },
  pontos: { type: Array, default: () => [] },
  disponiveis: { type: Array, default: () => [] },
});

const form = useForm({ ponto_id: '' });

const naoVinculados = computed(() => {
  const vinculadosIds = new Set(props.pontos.map((p) => p.id));
  return props.disponiveis.filter((p) => !vinculadosIds.has(p.id));
});

function vincular() {
  form.post(route('pmda.planos.pontos.store', props.planoId), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function desvincular(pontoId) {
  router.delete(route('pmda.planos.pontos.destroy', [props.planoId, pontoId]), { preserveScroll: true });
}
</script>
