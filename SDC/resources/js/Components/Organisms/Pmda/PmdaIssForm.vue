<template>
  <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-4" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-gray-800">Informações ISS</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="form.cobra_iss" type="checkbox" class="rounded border-gray-300" />
        Município cobra ISS
      </label>

      <div>
        <span class="mb-1 block text-sm text-gray-600">Nº da Lei do ISS</span>
        <input v-model="form.num_lei_iss" type="text" maxlength="30" class="w-full rounded-md border-gray-300 text-sm" />
      </div>

      <div>
        <span class="mb-1 block text-sm text-gray-600">Alíquota ISS (%)</span>
        <input v-model="form.aliquota_iss" type="number" step="0.01" min="0" max="99.99" class="w-full rounded-md border-gray-300 text-sm" />
      </div>

      <div>
        <span class="mb-1 block text-sm text-gray-600">Responsável pela cobrança</span>
        <input v-model="form.resp_cob_iss" type="text" maxlength="30" class="w-full rounded-md border-gray-300 text-sm" />
      </div>
    </div>

    <div class="flex justify-end">
      <button
        type="submit"
        :disabled="form.processing"
        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
      >
        Salvar
      </button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  plano: { type: Object, required: true },
});

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
