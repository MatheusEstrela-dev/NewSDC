<template>
  <section ref="raiz" class="rounded-xl border border-emerald-300 bg-white p-5 shadow-sm dark:border-emerald-700/50 dark:bg-slate-900/60">
    <template v-if="podeResolver">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-emerald-600 dark:text-emerald-400"><CheckCircleIcon class="h-5 w-5" /> Resolver chamado</h2>
      <label class="block text-sm text-slate-700 dark:text-slate-200">Data/hora de abertura
        <input v-model="form.aberta_em" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      </label>
      <p v-if="erroAbertura" class="mt-1 text-xs text-red-500">{{ erroAbertura }}</p>
      <label class="mt-4 block text-sm text-slate-700 dark:text-slate-200">Data/hora de fechamento
        <input v-model="form.resolvida_em" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      </label>
      <p v-if="erroFechamento" class="mt-1 text-xs text-red-500">{{ erroFechamento }}</p>
      <Button class="mt-4 w-full" variant="success" size="md" :disabled="form.processing || !podeEnviar" @click="enviar">Confirmar resolução</Button>
    </template>
    <template v-else-if="podeReabrir">
      <p class="text-sm text-slate-600 dark:text-slate-300">Resolvido em {{ formatarDataHora(demanda.resolvido_em) }}.</p>
      <Button class="mt-3 w-full" variant="secondary" size="md" @click="reabrir">Reabrir chamado</Button>
    </template>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import { useDemandaResolucao } from '@/Composables/demandas';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  demanda: { type: Object, required: true },
  podeResolver: { type: Boolean, default: false },
  podeReabrir: { type: Boolean, default: false },
});

const raiz = ref(null);
const {
  form, erroAbertura, erroFechamento, podeEnviar, enviar,
} = useDemandaResolucao(props.demanda);

function reabrir() {
  router.post(route('demandas.reabrir', props.demanda.id), {}, { preserveScroll: true });
}

defineExpose({ focar: () => raiz.value?.scrollIntoView({ behavior: 'smooth', block: 'center' }) });
</script>
