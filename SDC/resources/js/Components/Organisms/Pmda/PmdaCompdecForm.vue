<template>
  <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">COMPDEC — Coordenadoria Municipal de Proteção e Defesa Civil</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Coordenador(a)</span>
        <TextInput v-model="form.compdec_coordenador" :maxlength="110" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">E-mail</span>
        <TextInput v-model="form.compdec_email" type="email" :maxlength="110" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Telefone</span>
        <TextInput v-model="form.compdec_tel" :maxlength="20" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Decreto de criação</span>
        <TextInput v-model="form.compdec_decreto" :maxlength="50" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Lei de criação</span>
        <TextInput v-model="form.compdec_lei" :maxlength="50" />
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
  compdec_coordenador: props.plano.compdec_coordenador ?? '',
  compdec_email: props.plano.compdec_email ?? '',
  compdec_tel: props.plano.compdec_tel ?? '',
  compdec_decreto: props.plano.compdec_decreto ?? '',
  compdec_lei: props.plano.compdec_lei ?? '',
});

function submit() {
  form.put(route('pmda.planos.update', props.plano.id), { preserveScroll: true });
}
</script>
