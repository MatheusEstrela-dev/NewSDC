<template>
  <Head title="TDAP — Novo Processo" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Novo Processo TDAP"
      description="Abre o processo em estado RASCUNHO (swimlane Fomentação)"
      :icon="TruckIcon"
    />

    <form @submit.prevent="submit" class="space-y-6">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="numero" value="Número do processo *" />
            <TextInput id="numero" v-model="form.numero" type="text" class="mt-1 block w-full uppercase" maxlength="20" placeholder="Ex: TDAP-2026-001" required />
            <InputError :message="form.errors.numero" class="mt-2" />
          </div>
          <div>
            <InputLabel for="municipio_id" value="Município *" />
            <select id="municipio_id" v-model="form.municipio_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
              <option :value="null">Selecione...</option>
              <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nome }}<span v-if="m.uf">/{{ m.uf }}</span></option>
            </select>
            <InputError :message="form.errors.municipio_id" class="mt-2" />
          </div>
        </div>
        <div class="mt-4">
          <InputLabel for="contexto" value="Contexto / observações iniciais" />
          <textarea id="contexto" v-model="contextoTxt" rows="4" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" placeholder="Detalhes da demanda (origem, urgência, justificativa)..." />
          <InputError :message="form.errors.contexto" class="mt-2" />
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <SecondaryButton type="button" @click="cancelar">Cancelar</SecondaryButton>
        <PrimaryButton type="submit" :disabled="form.processing">
          {{ form.processing ? 'Abrindo...' : 'Abrir Processo' }}
        </PrimaryButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

defineProps({
  municipios: { type: Array, default: () => [] },
});

const form = useForm({
  numero: '',
  municipio_id: null,
  contexto: null,
});

const contextoTxt = ref('');

watch(contextoTxt, (v) => {
  form.contexto = v ? { observacao_inicial: v } : null;
});

function submit() { form.post(route('tdap.processos.store')); }
function cancelar() { router.visit(route('tdap.processos.index')); }
</script>
