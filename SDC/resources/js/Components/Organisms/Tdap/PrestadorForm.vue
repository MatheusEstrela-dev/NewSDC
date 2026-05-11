<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="cnpj" value="CNPJ *" />
          <TextInput
            id="cnpj"
            v-model="form.cnpj"
            type="text"
            class="mt-1 block w-full"
            placeholder="00.000.000/0000-00"
            maxlength="18"
            @input="onCnpjInput"
            required
          />
          <InputError :message="form.errors.cnpj" class="mt-2" />
        </div>
        <div>
          <InputLabel for="nome" value="Razão Social *" />
          <TextInput
            id="nome"
            v-model="form.nome"
            type="text"
            class="mt-1 block w-full"
            maxlength="150"
            required
          />
          <InputError :message="form.errors.nome" class="mt-2" />
        </div>
        <div>
          <InputLabel for="representante" value="Representante Legal" />
          <TextInput
            id="representante"
            v-model="form.representante"
            type="text"
            class="mt-1 block w-full"
            maxlength="150"
          />
          <InputError :message="form.errors.representante" class="mt-2" />
        </div>
        <div>
          <InputLabel for="email" value="E-mail *" />
          <TextInput
            id="email"
            v-model="form.email"
            type="email"
            class="mt-1 block w-full"
            maxlength="150"
            required
          />
          <InputError :message="form.errors.email" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Contato</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="tel1" value="Telefone Principal" />
          <TextInput id="tel1" v-model="form.tel1" type="text" class="mt-1 block w-full" maxlength="20" />
          <InputError :message="form.errors.tel1" class="mt-2" />
        </div>
        <div>
          <InputLabel for="tel2" value="Telefone Secundário" />
          <TextInput id="tel2" v-model="form.tel2" type="text" class="mt-1 block w-full" maxlength="20" />
          <InputError :message="form.errors.tel2" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Endereço</h3>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="md:col-span-4">
          <InputLabel for="endereco" value="Logradouro" />
          <TextInput id="endereco" v-model="form.endereco" type="text" class="mt-1 block w-full" maxlength="200" />
          <InputError :message="form.errors.endereco" class="mt-2" />
        </div>
        <div class="md:col-span-2">
          <InputLabel for="cep" value="CEP" />
          <TextInput id="cep" v-model="form.cep" type="text" class="mt-1 block w-full" maxlength="9" />
          <InputError :message="form.errors.cep" class="mt-2" />
        </div>
        <div class="md:col-span-2">
          <InputLabel for="bairro" value="Bairro" />
          <TextInput id="bairro" v-model="form.bairro" type="text" class="mt-1 block w-full" maxlength="100" />
          <InputError :message="form.errors.bairro" class="mt-2" />
        </div>
        <div class="md:col-span-3">
          <InputLabel for="cidade" value="Cidade" />
          <TextInput id="cidade" v-model="form.cidade" type="text" class="mt-1 block w-full" maxlength="100" />
          <InputError :message="form.errors.cidade" class="mt-2" />
        </div>
        <div>
          <InputLabel for="uf" value="UF" />
          <TextInput id="uf" v-model="form.uf" type="text" class="mt-1 block w-full uppercase" maxlength="2" />
          <InputError :message="form.errors.uf" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Outros</h3>
      <div class="space-y-4">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.ativo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm text-slate-700 dark:text-slate-300">Prestador ativo</span>
        </label>
        <div>
          <InputLabel for="observacoes" value="Observações" />
          <textarea
            id="observacoes"
            v-model="form.observacoes"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            rows="3"
            maxlength="2000"
          />
          <InputError :message="form.errors.observacoes" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <SecondaryButton type="button" @click="$emit('cancel')">Cancelar</SecondaryButton>
      <PrimaryButton type="submit" :disabled="form.processing">
        {{ form.processing ? 'Salvando...' : submitLabel }}
      </PrimaryButton>
    </div>
  </form>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

const props = defineProps;

function onCnpjInput(event) {
  let digits = (event.target.value || '').replace(/\D+/g, '').slice(0, 14);
  if (digits.length > 12) digits = `${digits.slice(0,2)}.${digits.slice(2,5)}.${digits.slice(5,8)}/${digits.slice(8,12)}-${digits.slice(12)}`;
  else if (digits.length > 8) digits = `${digits.slice(0,2)}.${digits.slice(2,5)}.${digits.slice(5,8)}/${digits.slice(8)}`;
  else if (digits.length > 5) digits = `${digits.slice(0,2)}.${digits.slice(2,5)}.${digits.slice(5)}`;
  else if (digits.length > 2) digits = `${digits.slice(0,2)}.${digits.slice(2)}`;
  event.target.value = digits;
}
</script>
