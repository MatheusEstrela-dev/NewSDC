<template>
  <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-4" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-gray-800">COMPDEC — Coordenadoria Municipal de Proteção e Defesa Civil</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Coordenador(a)</span>
        <input v-model="form.compdec_coordenador" type="text" maxlength="110" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">E-mail</span>
        <input v-model="form.compdec_email" type="email" maxlength="110" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Telefone</span>
        <input v-model="form.compdec_tel" type="text" maxlength="20" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Decreto de criação</span>
        <input v-model="form.compdec_decreto" type="text" maxlength="50" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Lei de criação</span>
        <input v-model="form.compdec_lei" type="text" maxlength="50" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
    </div>

    <div class="flex justify-end">
      <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
        Salvar
      </button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

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
