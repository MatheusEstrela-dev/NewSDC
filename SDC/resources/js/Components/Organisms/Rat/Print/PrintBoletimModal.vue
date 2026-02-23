<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import BoletimDadosGerais from './Sections/BoletimDadosGerais.vue';
import BoletimHistorico from './Sections/BoletimHistorico.vue';
import BoletimRecursos from './Sections/BoletimRecursos.vue';
import BoletimServidores from './Sections/BoletimServidores.vue';
import BoletimEnvolvidos from './Sections/BoletimEnvolvidos.vue';
import BoletimVistoria from './Sections/BoletimVistoria.vue';
import BoletimRecibo from './Sections/BoletimRecibo.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  ocorrencia: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const documentTitle = computed(() => {
  return `Boletim de Ocorrencia - ${props.ocorrencia?.numero_bos || 'N/A'}`;
});

const dadosGerais = computed(() => {
  return props.ocorrencia?.dados_gerais || props.ocorrencia?.dadosGerais || null;
});

const envolvidos = computed(() => {
  return props.ocorrencia?.envolvidos || [];
});

const recursos = computed(() => {
  return props.ocorrencia?.recursos || [];
});

const vistoria = computed(() => {
  return props.ocorrencia?.vistoria || null;
});

const agentes = computed(() => {
  const allAgentes = [];
  recursos.value.forEach((recurso) => {
    if (recurso?.componentesGuarnicao) {
      allAgentes.push(...recurso.componentesGuarnicao);
    }
  });
  return allAgentes;
});
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Boletim de Ocorrencia"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="ocorrencia" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="BOLETIM DE OCORRENCIA SIMPLIFICADO"
          :numero="ocorrencia.numero_bos || 'N/A'"
          label-numero="BOS"
        />

        <div class="card-body p-0">
          <BoletimDadosGerais :dados="dadosGerais" />
          <BoletimHistorico :historico="ocorrencia.historico" />
          <BoletimRecursos :recursos="recursos" />
          <BoletimServidores :agentes="agentes" />
          <BoletimEnvolvidos :envolvidos="envolvidos" />
          <BoletimVistoria v-if="vistoria" :vistoria="vistoria" />
          <BoletimRecibo :ocorrencia="ocorrencia" />
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-gray-600 dark:text-gray-400">
      Nenhuma ocorrencia selecionada
    </div>
  </BasePrintModal>
</template>
