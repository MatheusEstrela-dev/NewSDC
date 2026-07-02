<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  // Payload retornado por GET pmda.planos.ficha
  dados: { type: Object, default: null },
});

defineEmits(['close']);

const ficha = computed(() => props.dados?.ficha ?? {});
const ativos = computed(() => props.dados?.equipe_ativos ?? []);
const anteriores = computed(() => props.dados?.equipe_anteriores ?? []);
const anexos = computed(() => props.dados?.anexos ?? []);

const municipioLabel = computed(() => {
  const m = props.dados?.municipio;
  const uf = props.dados?.uf;
  return m ? (uf ? `${m} / ${uf}` : m) : '—';
});

const documentTitle = computed(() => `Ficha COMPDEC - ${props.dados?.protocolo ?? 'N/A'}`);

// Coordenador: membro ativo com funcao COORDENADOR, senao primeiro ativo.
const coordenador = computed(() => {
  const c = ativos.value.find((m) => (m.funcao_label || '').toUpperCase().includes('COORDENADOR'));
  return c ?? ativos.value[0] ?? null;
});

function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString('pt-BR');
}

function dash(v) {
  return v === null || v === undefined || v === '' ? '—' : v;
}
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Ficha COMPDEC"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="dados" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="FICHA COMPDEC"
          :numero="dados.protocolo"
          label-numero="PROTOCOLO"
        />

        <div class="card-body p-0">
          <PrintSection titulo="IDENTIFICACAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">MUNICIPIO</td>
                <td class="field-value" width="30%">{{ municipioLabel }}</td>
                <td class="field-label" width="20%">ORGAO COMPDEC</td>
                <td class="field-value" width="30%">{{ dash(ficha.orgao_nome) }}</td>
              </tr>
              <tr>
                <td class="field-label">COORDENADOR MUNICIPAL</td>
                <td class="field-value">{{ dash(coordenador?.nome) }}</td>
                <td class="field-label">CONTATO COORDENADOR</td>
                <td class="field-value">{{ dash(coordenador?.celular || coordenador?.telefone) }}</td>
              </tr>
              <tr>
                <td class="field-label">E-MAIL</td>
                <td class="field-value">{{ dash(ficha.email || coordenador?.email) }}</td>
                <td class="field-label">TELEFONE</td>
                <td class="field-value">{{ dash(ficha.telefone) }}</td>
              </tr>
              <tr>
                <td class="field-label">ENDERECO</td>
                <td class="field-value" colspan="3">{{ dash(ficha.endereco) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="EQUIPE COMPDEC">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="4%">#</td>
                <td class="field-label" width="24%">NOME</td>
                <td class="field-label" width="14%">CPF</td>
                <td class="field-label" width="16%">FUNCAO</td>
                <td class="field-label" width="13%">TELEFONE</td>
                <td class="field-label" width="13%">CELULAR</td>
                <td class="field-label" width="16%">EMAIL</td>
              </tr>
              <tr v-for="(m, i) in ativos" :key="m.id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(m.nome) }}</td>
                <td class="field-value">{{ dash(m.cpf) }}</td>
                <td class="field-value">{{ dash(m.funcao_label) }}</td>
                <td class="field-value">{{ dash(m.telefone) }}</td>
                <td class="field-value">{{ dash(m.celular) }}</td>
                <td class="field-value">{{ dash(m.email) }}</td>
              </tr>
              <tr v-if="ativos.length === 0">
                <td class="field-value text-center" colspan="7">Nenhum membro ativo cadastrado.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection v-if="anteriores.length > 0" titulo="AGENTES / COORDENADORES ANTERIORES">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="4%">#</td>
                <td class="field-label" width="24%">NOME</td>
                <td class="field-label" width="14%">CPF</td>
                <td class="field-label" width="16%">FUNCAO</td>
                <td class="field-label" width="13%">TELEFONE</td>
                <td class="field-label" width="13%">CELULAR</td>
                <td class="field-label" width="16%">EMAIL</td>
              </tr>
              <tr v-for="(m, i) in anteriores" :key="m.id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(m.nome) }}</td>
                <td class="field-value">{{ dash(m.cpf) }}</td>
                <td class="field-value">{{ dash(m.funcao_label) }}</td>
                <td class="field-value">{{ dash(m.telefone) }}</td>
                <td class="field-value">{{ dash(m.celular) }}</td>
                <td class="field-value">{{ dash(m.email) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="ANEXO LEIS E DECRETOS">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="40%">ATO LEGAL</td>
                <td class="field-label" width="20%">NUMERO</td>
                <td class="field-label" width="20%">DATA</td>
                <td class="field-label" width="20%">SITUACAO</td>
              </tr>
              <tr>
                <td class="field-value">Lei de Criacao do COMPDEC</td>
                <td class="field-value">{{ dash(ficha.lei_criacao_numero) }}</td>
                <td class="field-value">{{ dash(formatDate(ficha.lei_criacao_data)) }}</td>
                <td class="field-value">{{ ficha.nao_possui_lei ? 'Nao possui' : 'Possui' }}</td>
              </tr>
              <tr>
                <td class="field-value">Decreto de Regulamentacao da Lei</td>
                <td class="field-value">{{ dash(ficha.decreto_numero) }}</td>
                <td class="field-value">{{ dash(formatDate(ficha.decreto_data)) }}</td>
                <td class="field-value">{{ ficha.nao_possui_decreto ? 'Nao possui' : 'Possui' }}</td>
              </tr>
              <tr>
                <td class="field-value">Portaria de Nomeacao do Coordenador</td>
                <td class="field-value">{{ dash(ficha.portaria_numero) }}</td>
                <td class="field-value">{{ dash(formatDate(ficha.portaria_data)) }}</td>
                <td class="field-value">{{ ficha.nao_possui_portaria ? 'Nao possui' : 'Possui' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="DOCUMENTOS ANEXADOS">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="4%">#</td>
                <td class="field-label" width="16%">DATA</td>
                <td class="field-label" width="14%">VALIDADE</td>
                <td class="field-label" width="26%">TIPO DOC.</td>
                <td class="field-label" width="26%">NOME ARQUIVO</td>
                <td class="field-label" width="14%">DESCRICAO</td>
              </tr>
              <tr v-for="(a, i) in anexos" :key="a.id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(a.created_at_formatado) }}</td>
                <td class="field-value">{{ dash(formatDate(a.data_validade)) }}</td>
                <td class="field-value">{{ dash(a.tipo_label) }}</td>
                <td class="field-value">{{ dash(a.arquivo_nome_original || a.arquivo_nome) }}</td>
                <td class="field-value">{{ dash(a.descricao) }}</td>
              </tr>
              <tr v-if="anexos.length === 0">
                <td class="field-value text-center" colspan="6">Nenhum documento anexado.</td>
              </tr>
            </table>
          </PrintSection>

          <div style="padding: 8px 10px; font-size: 8px; color: #555; border: 1px solid #000; border-top: none;">
            Documento gerado em {{ new Date().toLocaleString('pt-BR') }} pelo Sistema Integrado de Defesa Civil - CEDEC-MG.
          </div>
        </div>
      </div>
    </div>

    <div v-else class="py-12 text-center text-gray-600 dark:text-gray-400">
      Nenhuma ficha carregada.
    </div>
  </BasePrintModal>
</template>
