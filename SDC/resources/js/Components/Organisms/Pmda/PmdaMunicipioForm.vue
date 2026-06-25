<template>
  <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/60" @submit.prevent="submit">
    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Informações do Município / Prefeitura</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Prefeito(a)</span>
        <TextInput v-model="form.nome_prefeito" :maxlength="110" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Tel. Prefeitura</span>
        <TextInput v-model="form.tel_prefeitura" :maxlength="20" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">E-mail Prefeitura</span>
        <TextInput v-model="form.email_prefeitura" type="email" :maxlength="110" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Tel. Prefeito</span>
        <TextInput v-model="form.tel_prefeito" :maxlength="20" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Cel. Prefeito</span>
        <TextInput v-model="form.cel_prefeito" :maxlength="20" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">CEP</span>
        <TextInput v-model="form.cep" :maxlength="10" />
      </div>
      <div class="lg:col-span-2">
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Endereço</span>
        <TextInput v-model="form.endereco" :maxlength="150" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Bairro</span>
        <TextInput v-model="form.bairro" :maxlength="60" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">População urbana</span>
        <TextInput v-model="form.populacao" type="number" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">População rural</span>
        <TextInput v-model="form.pop_rural" type="number" />
      </div>
      <div>
        <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Área (km²)</span>
        <TextInput v-model="form.area" type="number" />
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
