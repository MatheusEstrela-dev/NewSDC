<template>
  <form class="space-y-2" @submit.prevent="enviar">
    <textarea v-model="form.conteudo" rows="3" maxlength="10000" placeholder="Escreva um comentário"
      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
    <p v-if="form.errors.conteudo" class="text-xs text-red-500">{{ form.errors.conteudo }}</p>
    <div class="flex items-center justify-between gap-2">
      <label v-if="podeInterno" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
        <input v-model="form.interno" type="checkbox" class="rounded" /> Interno (não visível ao solicitante)
      </label>
      <Button type="submit" variant="primary" size="sm" :disabled="form.processing || !form.conteudo.trim()">Comentar</Button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  demandaId: { type: Number, required: true },
  podeInterno: { type: Boolean, default: false },
});

const form = useForm({ conteudo: '', interno: false });

function enviar() {
  form.post(route('demandas.comments.store', props.demandaId), { preserveScroll: true, onSuccess: () => form.reset() });
}
</script>
