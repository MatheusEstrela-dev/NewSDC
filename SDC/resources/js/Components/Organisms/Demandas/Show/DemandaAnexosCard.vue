<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100"><PaperClipIcon class="h-5 w-5" /> Anexos ({{ anexos.length }})</h2>
    <ul v-if="anexos.length" class="space-y-2">
      <li v-for="a in anexos" :key="a.id" class="flex items-center justify-between gap-2 text-sm">
        <a v-if="a.disponivel" :href="a.url" class="min-w-0 truncate text-orange-600 hover:underline dark:text-orange-400">{{ a.nome_original }}</a>
        <span v-else class="min-w-0 truncate text-slate-500 dark:text-slate-400">{{ a.nome_original }} <span class="italic">(arquivo indisponível)</span></span>
        <span class="shrink-0 text-xs text-slate-500">{{ formatarBytes(a.tamanho_bytes) }}</span>
      </li>
    </ul>
    <p v-else class="text-sm text-slate-500">Nenhum arquivo anexado a esta demanda.</p>
    <form v-if="podeAnexar" class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50" @submit.prevent="enviar">
      <input ref="entrada" type="file" :accept="ACEITOS" class="min-w-0 flex-1 text-sm" @change="form.arquivo = $event.target.files[0] ?? null" />
      <Button type="submit" variant="primary" size="sm" :disabled="!form.arquivo || form.processing">Anexar</Button>
      <p v-if="form.errors.arquivo" class="w-full text-xs text-red-500">{{ form.errors.arquivo }}</p>
    </form>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import { formatarBytes } from '@/Support/demandasFormat';

const props = defineProps({
  demandaId: { type: Number, required: true },
  anexos: { type: Array, default: () => [] },
  podeAnexar: { type: Boolean, default: true },
});

const ACEITOS = '.png,.jpg,.jpeg,.pdf,.xlsx,.xls,.csv,.txt';
const entrada = ref(null);
const form = useForm({ arquivo: null });

function enviar() {
  form.post(route('demandas.attachments.store', props.demandaId), {
    forceFormData: true, preserveScroll: true,
    onSuccess: () => { form.reset(); if (entrada.value) entrada.value.value = ''; },
  });
}
</script>
