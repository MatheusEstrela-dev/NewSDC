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
  recebimento: {
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
  return `TDPA Recebimento - ${props.recebimento?.numero_recebimento || 'N/A'}`;
});
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Recebimento TDPA"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="recebimento" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="TDPA - GESTÃO DE DEPÓSITO"
          :numero="recebimento.numero_recebimento"
          label-numero="RECEBIMENTO"
        />

        <div class="card-body p-0">
          <PrintSection titulo="DADOS DO TRANSPORTE">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">PLACA DO VEÍCULO</td>
                <td class="field-value" width="30%">{{ recebimento.placa_veiculo || '' }}</td>
                <td class="field-label" width="20%">TRANSPORTADORA</td>
                <td class="field-value" width="30%">{{ recebimento.transportadora || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">MOTORISTA</td>
                <td class="field-value">{{ recebimento.motorista_nome || '' }}</td>
                <td class="field-label">DOCUMENTO (RG/CPF)</td>
                <td class="field-value">{{ recebimento.motorista_documento || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DOCA DE DESCARGA</td>
                <td class="field-value" colspan="3">{{ recebimento.doca_descarga || '' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="DOCUMENTAÇÃO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">NOTA FISCAL</td>
                <td class="field-value" width="30%">{{ recebimento.nota_fiscal || '' }}</td>
                <td class="field-label" width="20%">ORDEM DE COMPRA</td>
                <td class="field-value" width="30%">{{ recebimento.ordem_compra_id || '' }}</td>
              </tr>
              <tr>
                <td class="field-label">DATA CHEGADA</td>
                <td class="field-value">{{ formatDateTime(recebimento.data_chegada) }}</td>
                <td class="field-label">STATUS</td>
                <td class="field-value">{{ recebimento.status || '' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="ITENS RECEBIDOS">
            <table class="bos-table w-full">
              <thead>
                <tr>
                  <th class="field-label text-left">PRODUTO</th>
                  <th class="field-label text-center">LOTE</th>
                  <th class="field-label text-center">VALIDADE</th>
                  <th class="field-label text-center">QTD NOTA</th>
                  <th class="field-label text-center">QTD CONFERIDA</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in recebimento.itens" :key="index">
                  <td class="field-value">{{ item.product?.nome || item.product_name || 'Item ' + (index + 1) }}</td>
                  <td class="field-value text-center">{{ item.numero_lote || '-' }}</td>
                  <td class="field-value text-center">{{ formatDate(item.data_validade) || '-' }}</td>
                  <td class="field-value text-center">{{ item.quantidade_nota || 0 }}</td>
                  <td class="field-value text-center">{{ item.quantidade_conferida || 0 }}</td>
                </tr>
                <tr v-if="!recebimento.itens || recebimento.itens.length === 0">
                  <td colspan="5" class="field-value text-center p-2">Nenhum item registrado</td>
                </tr>
              </tbody>
            </table>
          </PrintSection>

          <PrintSection titulo="OBSERVAÇÕES / AVARIAS" v-if="recebimento.observacoes">
             <table class="bos-table">
                <tr>
                   <td class="field-value" style="white-space: pre-wrap; min-height: 50px;">{{ recebimento.observacoes }}</td>
                </tr>
             </table>
          </PrintSection>

          <PrintRecibo
            :numero-documento="recebimento.numero_recebimento"
            tipo-documento="Recebimento de Material"
            :criado-por="recebimento.criado_por?.name || 'Sistema'"
            :data-criacao="recebimento.created_at"
          />
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
