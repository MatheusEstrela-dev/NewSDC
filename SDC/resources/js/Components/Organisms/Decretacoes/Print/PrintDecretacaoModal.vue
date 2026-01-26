<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import PrintRecibo from '@/Components/Organisms/Print/Sections/PrintRecibo.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  processo: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('pt-BR');
}

function formatDateTime(date) {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const documentTitle = computed(() => {
  return `Processo Decretacao - ${props.processo?.n_protocolo_fide || 'N/A'}`;
});

const subtitulo = computed(() => {
  const tipo = props.processo?.processo || 'PROCESSO';
  return `RECONHECIMENTO DE DESASTRE - ${tipo}`;
});
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Processo de Decretacao"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="processo" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          :subtitulo="subtitulo"
          :numero="processo.n_protocolo_fide"
          label-numero="PROTOCOLO FIDE"
        />

        <div class="card-body p-0">
          <PrintSection titulo="DADOS DO PROCESSO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">PROTOCOLO FIDE</td>
                <td class="field-value" width="30%">{{ processo.n_protocolo_fide || '' }}</td>
                <td class="field-label" width="20%">TIPO DE PROCESSO</td>
                <td class="field-value" width="30%">{{ processo.processo || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DATA DE ENTRADA</td>
                <td class="field-value">{{ formatDate(processo.data_entrada) }}</td>
                <td class="field-label">STATUS</td>
                <td class="field-value">{{ processo.status || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DATA DE VENCIMENTO</td>
                <td class="field-value">{{ formatDate(processo.data_vencimento) }}</td>
                <td class="field-label">DIAS RESTANTES</td>
                <td class="field-value">{{ processo.dias_restantes || '' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="DADOS DO DESASTRE">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">TIPO DE DESASTRE</td>
                <td class="field-value" colspan="3">{{ processo.tipo_desastre_nome || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">COBRADE</td>
                <td class="field-value">{{ processo.tipo_desastre_cobrade || '' }}</td>
                <td class="field-label">DATA DO DESASTRE</td>
                <td class="field-value">{{ formatDate(processo.data_desastre) }}</td>
              </tr>
              <tr v-if="processo.descricao_desastre">
                <td class="field-label">DESCRICAO</td>
                <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ processo.descricao_desastre }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="ANALISE">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">ANALISTA RESPONSAVEL</td>
                <td class="field-value" colspan="3">{{ processo.analista || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DATA DA ANALISE</td>
                <td class="field-value">{{ formatDateTime(processo.data_analise) }}</td>
                <td class="field-label">PARECER</td>
                <td class="field-value">{{ processo.parecer || '' }}</td>
              </tr>
              <tr v-if="processo.observacoes">
                <td class="field-label">OBSERVACOES</td>
                <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ processo.observacoes }}</td>
              </tr>
            </table>
          </PrintSection>

          <template v-if="processo.municipios && processo.municipios.length > 0">
            <PrintSection titulo="MUNICIPIOS AFETADOS">
              <table class="bos-table">
                <tr>
                  <td class="field-label" width="5%">No</td>
                  <td class="field-label" width="35%">MUNICIPIO</td>
                  <td class="field-label" width="20%">CODIGO IBGE</td>
                  <td class="field-label" width="20%">POPULACAO AFETADA</td>
                  <td class="field-label" width="20%">DANOS</td>
                </tr>
                <tr v-for="(municipio, index) in processo.municipios" :key="municipio.id">
                  <td class="field-value text-center">{{ index + 1 }}</td>
                  <td class="field-value">{{ municipio.nome || '' }}</td>
                  <td class="field-value">{{ municipio.codigo_ibge || '' }}</td>
                  <td class="field-value">{{ municipio.populacao_afetada || '' }}</td>
                  <td class="field-value">{{ municipio.danos || '' }}</td>
                </tr>
              </table>
            </PrintSection>
          </template>

          <template v-if="processo.documentos && processo.documentos.length > 0">
            <PrintSection titulo="DOCUMENTOS ANEXADOS">
              <table class="bos-table">
                <tr>
                  <td class="field-label" width="5%">No</td>
                  <td class="field-label" width="45%">DOCUMENTO</td>
                  <td class="field-label" width="25%">TIPO</td>
                  <td class="field-label" width="25%">DATA</td>
                </tr>
                <tr v-for="(doc, index) in processo.documentos" :key="doc.id">
                  <td class="field-value text-center">{{ index + 1 }}</td>
                  <td class="field-value">{{ doc.nome || '' }}</td>
                  <td class="field-value">{{ doc.tipo || '' }}</td>
                  <td class="field-value">{{ formatDate(doc.data) }}</td>
                </tr>
              </table>
            </PrintSection>
          </template>

          <PrintRecibo
            :numero-documento="processo.n_protocolo_fide"
            tipo-documento="Processo de Decretacao"
            :criado-por="processo.criado_por || 'Sistema'"
            :data-criacao="processo.data_entrada"
          />
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-gray-600 dark:text-gray-400">
      Nenhum processo selecionado
    </div>
  </BasePrintModal>
</template>
