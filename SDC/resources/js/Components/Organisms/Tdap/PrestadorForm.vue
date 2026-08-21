<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <section class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="cnpj" value="CNPJ *" />
          <TextInput
            id="cnpj"
            v-model="cnpjMascarado"
            type="text"
            inputmode="numeric"
            class="mt-1 block w-full font-mono"
            placeholder="00.000.000/0000-00"
            maxlength="18"
            autocomplete="off"
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
    </section>

    <section class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Contato</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="tel1" value="Telefone Principal" />
          <TextInput
            id="tel1"
            v-model="tel1Mascarado"
            type="text"
            inputmode="numeric"
            class="mt-1 block w-full"
            placeholder="(00) 00000-0000"
            maxlength="16"
          />
          <InputError :message="form.errors.tel1" class="mt-2" />
        </div>
        <div>
          <InputLabel for="tel2" value="Telefone Secundário" />
          <TextInput
            id="tel2"
            v-model="tel2Mascarado"
            type="text"
            inputmode="numeric"
            class="mt-1 block w-full"
            placeholder="(00) 00000-0000"
            maxlength="16"
          />
          <InputError :message="form.errors.tel2" class="mt-2" />
        </div>
      </div>
    </section>

    <section class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Endereço</h3>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="md:col-span-4">
          <InputLabel for="endereco" value="Logradouro" />
          <TextInput id="endereco" v-model="form.endereco" type="text" class="mt-1 block w-full" maxlength="200" />
          <InputError :message="form.errors.endereco" class="mt-2" />
        </div>
        <div class="md:col-span-2">
          <InputLabel for="cep" value="CEP" />
          <TextInput
            id="cep"
            v-model="cepMascarado"
            type="text"
            inputmode="numeric"
            class="mt-1 block w-full"
            placeholder="00000-000"
            maxlength="9"
          />
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
          <select
            id="uf"
            v-model="form.uf"
            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
          >
            <option value="">—</option>
            <option v-for="opcao in ufs" :key="opcao.value" :value="opcao.value">{{ opcao.value }}</option>
          </select>
          <InputError :message="form.errors.uf" class="mt-2" />
        </div>
      </div>
    </section>

    <section class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Outros</h3>
      <div class="space-y-4">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.ativo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm text-slate-700 dark:text-slate-300">Prestador ativo</span>
        </label>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Somente prestadores ativos aparecem na seleção de lotes e cronogramas.
        </p>
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
    </section>

    <div class="flex items-center justify-end gap-3">
      <SecondaryButton type="button" @click="$emit('cancel')">Cancelar</SecondaryButton>
      <PrimaryButton type="submit" :disabled="form.processing">
        {{ form.processing ? 'Salvando...' : submitLabel }}
      </PrimaryButton>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { apenasDigitos, cep as mascaraCep, cnpj as mascaraCnpj } from '@/utils/inputMasks';
import { applyPhoneMask } from '@/utils/phoneMask';

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  ufs: { type: Array, default: () => [] },
});

defineEmits(['submit', 'cancel']);

/**
 * Campo mascarado sobre um campo de digitos.
 *
 * O `form` guarda SEMPRE digitos puros -- e o que o backend valida
 * (`size:14`, `digits_between:10,11`, `digits:8`) e grava. A mascara existe so
 * na leitura do input.
 *
 * A versao anterior tentava mascarar escrevendo em `event.target.value` num
 * `@input` que caia como listener extra no <input>: o v-model interno do
 * TextInput ja havia gravado o valor cru, e o re-render seguinte desfazia a
 * mascara. Resultado: mascara piscando e digitos com pontuacao colada indo para
 * o backend. Com getter/setter a unica fonte de verdade e o form.
 */
function campoMascarado(campo, mascara, maxDigitos) {
  return computed({
    get: () => mascara(props.form[campo] ?? ''),
    set: (valor) => {
      props.form[campo] = apenasDigitos(valor).slice(0, maxDigitos);
    },
  });
}

const cnpjMascarado = campoMascarado('cnpj', mascaraCnpj, 14);
const tel1Mascarado = campoMascarado('tel1', applyPhoneMask, 11);
const tel2Mascarado = campoMascarado('tel2', applyPhoneMask, 11);
const cepMascarado = campoMascarado('cep', mascaraCep, 8);
</script>
