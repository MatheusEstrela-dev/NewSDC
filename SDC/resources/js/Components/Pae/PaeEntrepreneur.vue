<template>
  <div
    class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 max-w-4xl mx-auto animate-fade-in-up"
  >
    <div class="px-6 py-4 border-b border-slate-700 bg-slate-800/50">
      <h3 class="text-lg font-semibold text-white">Dados do Empreendedor</h3>
      <p class="text-sm text-slate-400">Informações de cadastro da empresa responsável.</p>
    </div>
    <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <FormField label="Razão Social" v-model="localData.razaoSocial" />
        <FormField label="CNPJ" v-model="localData.cnpj" />
      </div>
      <div>
        <FormField label="Endereço Completo" v-model="localData.endereco" />
      </div>

      <div class="border-t border-slate-700 pt-6">
        <h4 class="text-base font-semibold text-white mb-4 flex items-center gap-2">
          <UsersIcon class="w-4 h-4 text-blue-500" />
          Responsável Técnico
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <FormField label="Nome Completo" v-model="localData.responsavelTecnico.nome" />
          <FormField label="CPF" v-model="localData.responsavelTecnico.cpf" />
        </div>
      </div>

      <div class="flex justify-end pt-4">
        <button
          type="submit"
          class="text-white bg-blue-600 hover:bg-blue-500 focus:ring-4 focus:ring-blue-500/30 font-medium rounded-lg text-sm px-8 py-3 transition-all shadow-lg hover:shadow-blue-500/20"
        >
          Salvar Dados
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from './FormField.vue';
import UsersIcon from '../Icons/UsersIcon.vue';

const props = defineProps({
  empreendedor: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['save']);

const localData = ref({
  razaoSocial: props.empreendedor.razaoSocial || '',
  cnpj: props.empreendedor.cnpj || '',
  endereco: props.empreendedor.endereco || '',
  responsavelTecnico: {
    nome: props.empreendedor.responsavelTecnico?.nome || '',
    cpf: props.empreendedor.responsavelTecnico?.cpf || '',
  },
});

watch(
  () => props.empreendedor,
  (newVal) => {
    localData.value = {
      razaoSocial: newVal.razaoSocial || '',
      cnpj: newVal.cnpj || '',
      endereco: newVal.endereco || '',
      responsavelTecnico: {
        nome: newVal.responsavelTecnico?.nome || '',
        cpf: newVal.responsavelTecnico?.cpf || '',
      },
    };
  },
  { deep: true }
);

function handleSubmit() {
  emit('save', localData.value);
}
</script>

