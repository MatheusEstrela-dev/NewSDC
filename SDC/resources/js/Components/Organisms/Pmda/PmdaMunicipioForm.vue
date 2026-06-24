<template>
  <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-4" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-gray-800">Informações do Município / Prefeitura</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Prefeito(a)</span>
        <input v-model="form.nome_prefeito" type="text" maxlength="110" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Tel. Prefeitura</span>
        <input v-model="form.tel_prefeitura" type="text" maxlength="20" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">E-mail Prefeitura</span>
        <input v-model="form.email_prefeitura" type="email" maxlength="110" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Tel. Prefeito</span>
        <input v-model="form.tel_prefeito" type="text" maxlength="20" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Cel. Prefeito</span>
        <input v-model="form.cel_prefeito" type="text" maxlength="20" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">CEP</span>
        <input v-model="form.cep" type="text" maxlength="10" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block lg:col-span-2">
        <span class="mb-1 block text-xs text-gray-600">Endereço</span>
        <input v-model="form.endereco" type="text" maxlength="150" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Bairro</span>
        <input v-model="form.bairro" type="text" maxlength="60" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">População urbana</span>
        <input v-model="form.populacao" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">População rural</span>
        <input v-model="form.pop_rural" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm" />
      </label>
      <label class="block">
        <span class="mb-1 block text-xs text-gray-600">Área (km²)</span>
        <input v-model="form.area" type="number" step="0.01" min="0" class="w-full rounded-md border-gray-300 text-sm" />
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
  nome_prefeito: props.plano.nome_prefeito ?? '',
  tel_prefeitura: props.plano.tel_prefeitura ?? '',
  email_prefeitura: props.plano.email_prefeitura ?? '',
  tel_prefeito: props.plano.tel_prefeito ?? '',
  cel_prefeito: props.plano.cel_prefeito ?? '',
  cep: props.plano.cep ?? '',
  endereco: props.plano.endereco ?? '',
  bairro: props.plano.bairro ?? '',
  populacao: props.plano.populacao ?? '',
  pop_rural: props.plano.pop_rural ?? '',
  area: props.plano.area ?? '',
});

function submit() {
  form.put(route('pmda.planos.update', props.plano.id), { preserveScroll: true });
}
</script>
