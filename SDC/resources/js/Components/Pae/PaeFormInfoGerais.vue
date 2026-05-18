<template>
  <PaeCard title="1. Informações Gerais do Relatório">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
      <FormField
        label="Barragem"
        v-model="local.barragem"
        placeholder="Nome da Barragem"
      />
      <FormSelect
        label="Município"
        v-model="local.municipio_id"
        :options="municipioOptions"
        placeholder="Selecione um município"
      />
      <FormField
        label="Coordenador do PAE"
        v-model="local.coordenador_pae"
        placeholder="Nome do coordenador (empreendedor)"
      />
      <FormField
        label="Email"
        type="email"
        v-model="local.email"
        placeholder="email@empreendedor.com"
      />
      <FormField
        label="Coordenador Municipal de Defesa Civil"
        v-model="local.coordenador_mun_def_civ"
        placeholder="Nome do Coordenador Municipal"
      />
      <FormField
        label="Coordenador Municipal (Compdec)"
        v-model="local.coordenador_mun_compdec"
        placeholder="Nome do Coordenador Municipal Compdec"
      />
      <FormField
        label="Empreendedor Responsável"
        v-model="local.empreendedor_res"
        placeholder="Nome do Empreendedor"
      />
      <FormSelect
        label="Método Construtivo"
        v-model="local.metodo_construtivo"
        :options="metodosConstrutivos"
        placeholder="Selecione um método"
      />
      <FormField
        label="Número de ZAS"
        v-model="local.numero_zas"
        type="number"
        placeholder="Número de ZAS"
      />
      <FormSelect
        label="Nível de Emergência"
        v-model="local.nivel_emergencia"
        :options="niveisEmergencia"
        placeholder="Selecione um nível"
      />
    </div>

    <div v-if="!viewOnly" class="flex justify-end mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save', local)"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Informações Gerais
      </button>
    </div>
  </PaeCard>
</template>

<script setup>
import { reactive, watch } from 'vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  municipios: {
    type: Object,
    default: () => ({}),
  },
  saving: {
    type: Boolean,
    default: false,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save']);

const local = reactive({ ...props.modelValue });

watch(() => props.modelValue, (val) => Object.assign(local, val), { deep: true });

const municipioOptions = Object.entries(props.municipios).map(([value, label]) => ({ value, label }));

const metodosConstrutivos = [
  { value: 'Jusante',         label: 'Jusante' },
  { value: 'Montante',        label: 'Montante' },
  { value: 'Etapa única',     label: 'Etapa única' },
  { value: 'Linha de Centro', label: 'Linha de Centro' },
];

const niveisEmergencia = [
  { value: '0', label: 'Sem Emergência' },
  { value: '1', label: 'Alerta' },
  { value: '2', label: 'Nível 1' },
  { value: '3', label: 'Nível 2' },
  { value: '4', label: 'Nível 3' },
];
</script>
