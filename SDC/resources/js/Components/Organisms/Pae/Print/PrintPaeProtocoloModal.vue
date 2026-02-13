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
  protocolo: {
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
  return `Protocolo PAE - ${props.protocolo?.protocoloNumero || 'N/A'}`;
});

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
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Protocolo PAE"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="protocolo" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="PLANO DE ACAO DE EMERGENCIA - PAE"
          :numero="protocolo.protocoloNumero"
          label-numero="PROTOCOLO"
        />

        <div class="card-body p-0">
          <PrintSection titulo="DADOS DO PROTOCOLO">
            <table class="bos-table">
              <tbody>
                <tr>
                  <td class="field-label" width="18%">NUMERO DO PROTOCOLO</td>
                  <td class="field-value" width="32%">{{ protocolo.protocoloNumero || '' }}</td>
                  <td class="field-label" width="20%">SITUACAO</td>
                  <td class="field-value" width="30%">{{ protocolo.situacao || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">DATA DE ENTRADA</td>
                  <td class="field-value">{{ protocolo.dataEntrada || '' }}</td>
                  <td class="field-label">LIMITE DE ANALISE</td>
                  <td class="field-value">{{ protocolo.limiteAnalise || '' }}</td>
                </tr>
              </tbody>
            </table>
          </PrintSection>

          <PrintSection titulo="DADOS DO EMPREENDEDOR">
            <table class="bos-table">
              <tbody>
                <tr>
                  <td class="field-label" width="18%">EMPREENDEDOR</td>
                  <td class="field-value" colspan="3">{{ protocolo.empreendedor || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">ESTRUTURA</td>
                  <td class="field-value" colspan="3">{{ protocolo.estrutura || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">CNPJ</td>
                  <td class="field-value">{{ protocolo.cnpj || '' }}</td>
                  <td class="field-label">TELEFONE</td>
                  <td class="field-value">{{ protocolo.telefone || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">EMAIL</td>
                  <td class="field-value" colspan="3">{{ protocolo.email || '' }}</td>
                </tr>
              </tbody>
            </table>
          </PrintSection>

          <PrintSection titulo="LOCALIZACAO">
            <table class="bos-table">
              <tbody>
                <tr>
                  <td class="field-label" width="18%">MUNICIPIO</td>
                  <td class="field-value" width="32%">{{ protocolo.municipio || '' }}</td>
                  <td class="field-label" width="20%">UF</td>
                  <td class="field-value" width="30%">{{ protocolo.uf || 'MG' }}</td>
                </tr>
                <tr>
                  <td class="field-label">ENDERECO</td>
                  <td class="field-value" colspan="3">{{ protocolo.endereco || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">LATITUDE</td>
                  <td class="field-value">{{ protocolo.latitude || '' }}</td>
                  <td class="field-label">LONGITUDE</td>
                  <td class="field-value">{{ protocolo.longitude || '' }}</td>
                </tr>
              </tbody>
            </table>
          </PrintSection>

          <PrintSection titulo="ANALISE">
            <table class="bos-table">
              <tbody>
                <tr>
                  <td class="field-label" width="18%">ANALISTA RESPONSAVEL</td>
                  <td class="field-value" colspan="3">{{ protocolo.analista || '' }}</td>
                </tr>
                <tr>
                  <td class="field-label">DATA DA ANALISE</td>
                  <td class="field-value">{{ protocolo.dataAnalise || '' }}</td>
                  <td class="field-label">PRAZO</td>
                  <td class="field-value">{{ protocolo.prazo || '' }}</td>
                </tr>
                <tr v-if="protocolo.observacoes">
                  <td class="field-label">OBSERVACOES</td>
                  <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ protocolo.observacoes }}</td>
                </tr>
              </tbody>
            </table>
          </PrintSection>

          <template v-if="protocolo.analises && protocolo.analises.length > 0">
            <PrintSection titulo="HISTORICO DE ANALISES">
              <template v-for="(analise, index) in protocolo.analises" :key="index">
                <PrintSection :titulo="`ANALISE No ${index + 1}`" :is-subsection="true">
                  <table class="bos-table">
                    <tbody>
                      <tr>
                        <td class="field-label" width="18%">DATA</td>
                        <td class="field-value" width="32%">{{ formatDateTime(analise.data) }}</td>
                        <td class="field-label" width="20%">ANALISTA</td>
                        <td class="field-value" width="30%">{{ analise.analista || '' }}</td>
                      </tr>
                      <tr>
                        <td class="field-label">PARECER</td>
                        <td class="field-value" colspan="3">{{ analise.parecer || '' }}</td>
                      </tr>
                      <tr v-if="analise.observacoes">
                        <td class="field-label">OBSERVACOES</td>
                        <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ analise.observacoes }}</td>
                      </tr>
                    </tbody>
                  </table>
                </PrintSection>
              </template>
            </PrintSection>
          </template>

          <PrintRecibo
            :numero-documento="protocolo.protocoloNumero"
            tipo-documento="Protocolo PAE"
            :criado-por="protocolo.criadoPor || 'Sistema'"
            :data-criacao="protocolo.dataCriacao"
          />
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-gray-600 dark:text-gray-400">
      Nenhum protocolo selecionado
    </div>
  </BasePrintModal>
</template>
