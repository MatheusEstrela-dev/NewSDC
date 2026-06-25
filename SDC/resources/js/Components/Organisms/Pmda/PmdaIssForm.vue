<template>
  <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Informações ISS</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.cobra_iss" type="checkbox" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
        Município cobra ISS
      </label>

      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Nº da Lei do ISS</span>
        <TextInput v-model="form.num_lei_iss" :maxlength="30" />
      </div>

      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Alíquota ISS (%)</span>
        <TextInput v-model="form.aliquota_iss" type="number" />
      </div>

      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Responsável pela cobrança</span>
        <TextInput v-model="form.resp_cob_iss" :maxlength="30" />
      </div>
    </div>

    <div class="flex justify-end">
      <Button variant="success" size="sm" :disabled="form.processing" @click="submit">Salvar</Button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({ plano: { type: Object, required: true } });

const form = useForm({
  cobra_iss: props.plano.cobra_iss ?? false,
  num_lei_iss: props.plano.num_lei_iss ?? '',
  aliquota_iss: props.plano.aliquota_iss ?? '',
  resp_cob_iss: props.plano.resp_cob_iss ?? '',
});

function submit() {
  form.put(route('pmda.planos.update', props.plano.id), { preserveScroll: true });
}
</script>
