<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Card 1: Dados Gerais -->
      <PaeCard title="1. Dados Gerais do Empreendimento">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <FormField label="Nome do Empreendimento" :value="empreendimento.nome" readonly />
          <FormField label="Tipo" :value="empreendimento.tipo" readonly />
          <FormField label="Município" :value="empreendimento.municipio" readonly />
          <FormField
            label="Coordenadas (Lat/Long)"
            :value="empreendimento.coordenadas ? `${empreendimento.coordenadas.lat}, ${empreendimento.coordenadas.lng}` : 'N/A'"
            readonly
            class="font-mono text-xs"
          />
        </div>
      </PaeCard>

      <!-- Card 2: Dados do PAE -->
      <PaeCard title="2. Dados do PAE (Plano de Ação de Emergência)">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <FormField
            label="Protocolo SDC"
            v-model="localData.protocolo"
            icon="DocumentTextIcon"
            class="font-mono tracking-wide"
          />
          <FormSelect
            label="Status do PAE"
            v-model="localData.status"
            :options="statusOptions"
            icon="CheckCircleIcon"
          />
          <FormField label="Data de Emissão" type="date" v-model="localData.dataEmissao" />
          <FormField
            label="Próximo Vencimento"
            type="date"
            v-model="localData.proximoVencimento"
            :class="{ 'border-l-4 border-yellow-500': isNearExpiration }"
          />
        </div>
      </PaeCard>
    </div>

    <!-- Coluna Lateral -->
    <div class="lg:col-span-1 space-y-6">
      <!-- Card 3: Documentos -->
      <PaeDocumentsCard :documents="documents" @upload="handleUpload" @remove="handleRemove" />

      <!-- Card 4: Ações -->
      <PaeActionsCard
        @save="handleSave"
        @save-draft="handleSaveDraft"
        @archive="handleArchive"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FormField from './FormField.vue';
import FormSelect from './FormSelect.vue';
import PaeActionsCard from './PaeActionsCard.vue';
import PaeCard from './PaeCard.vue';
import PaeDocumentsCard from './PaeDocumentsCard.vue';

const props = defineProps({
  empreendimento: {
    type: Object,
    required: true,
  },
  documents: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['save', 'save-draft', 'archive', 'upload', 'remove']);

const localData = ref({
  protocolo: props.empreendimento.protocolo || '',
  status: props.empreendimento.status || 'aprovado',
  dataEmissao: props.empreendimento.dataEmissao || '',
  proximoVencimento: props.empreendimento.proximoVencimento || '',
});

const statusOptions = [
  { value: 'aprovado', label: 'Aprovado' },
  { value: 'analise', label: 'Em Análise' },
  { value: 'pendente', label: 'Pendente de Correção' },
  { value: 'vencido', label: 'Vencido' },
];

const isNearExpiration = computed(() => {
  if (!localData.value.proximoVencimento) return false;
  const vencimento = new Date(localData.value.proximoVencimento);
  const hoje = new Date();
  const diffDays = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
  return diffDays <= 30 && diffDays > 0;
});

watch(
  () => props.empreendimento,
  (newVal) => {
    localData.value = {
      protocolo: newVal.protocolo || '',
      status: newVal.status || 'aprovado',
      dataEmissao: newVal.dataEmissao || '',
      proximoVencimento: newVal.proximoVencimento || '',
    };
  },
  { deep: true }
);

function handleSave() {
  emit('save', localData.value);
}

function handleSaveDraft() {
  emit('save-draft', localData.value);
}

function handleArchive() {
  emit('archive');
}

function handleUpload(files) {
  emit('upload', files);
}

function handleRemove(id) {
  emit('remove', id);
}
</script>

