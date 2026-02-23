<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import PrintFieldsTable from '@/Components/Organisms/Print/Sections/PrintFieldsTable.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  beneficiario: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close']);

const documentTitle = computed(() => {
  return `Ficha de Beneficiário - ${props.beneficiario?.nome_responsavel || 'N/A'}`;
});

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('pt-BR');
};

const formatCpf = (cpf) => {
  if (!cpf) return '—';
  const cleaned = cpf.replace(/\D/g, '');
  if (cleaned.length !== 11) return cpf;
  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

const getEndereco = (b) => {
  if (b.endereco_rua) {
    return `${b.endereco_rua}${b.endereco_numero ? ', ' + b.endereco_numero : ''}${b.endereco_bairro ? ' - ' + b.endereco_bairro : ''}`;
  }
  return '—';
};

const formatSituacao = (situacao) => {
  const labels = {
    'IDOSO': 'Idoso',
    'CRIANCA': 'Criança',
    'GESTANTE': 'Gestante',
    'DEFICIENCIA': 'PcD',
    'DOENCA_CRONICA': 'Doença Crônica',
    'VULNERABILIDADE_SOCIAL': 'Vulnerabilidade Social',
  };
  return labels[situacao] || situacao;
};

const dadosPessoais = computed(() => {
  if (!props.beneficiario) return [];
  return [
    { label: 'Nome', value: props.beneficiario.nome_responsavel || props.beneficiario.nome || '—' },
    { label: 'CPF', value: formatCpf(props.beneficiario.cpf) },
    { label: 'Telefone', value: props.beneficiario.telefone || '—' },
    { label: 'Status', value: props.beneficiario.status || '—' },
    { label: 'Município', value: props.beneficiario.municipio?.nome || '—' },
    { label: 'Endereço', value: getEndereco(props.beneficiario) },
    { label: 'Família', value: `${props.beneficiario.membros_familia_count || 0} membros` },
  ];
});
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Imprimir Ficha do Beneficiário"
    :document-title="documentTitle"
    :loading="loading"
    @close="$emit('close')"
  >
    <div v-if="beneficiario" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          subtitulo="FICHA DE BENEFICIÁRIO - AJUDA HUMANITÁRIA"
          :numero="`#${beneficiario.id}`"
          label-numero="ID"
        />

        <div class="card-body p-0">
          <PrintSection title="DADOS DO BENEFICIÁRIO">
            <PrintFieldsTable :fields="dadosPessoais" />
          </PrintSection>

          <PrintSection title="VULNERABILIDADES" v-if="beneficiario.situacoes_vulnerabilidade?.length">
            <div class="p-2 flex flex-wrap gap-2">
              <span 
                v-for="sit in beneficiario.situacoes_vulnerabilidade" 
                :key="sit"
                class="px-2 py-1 border border-black rounded text-xs"
              >
                {{ formatSituacao(sit) }}
              </span>
            </div>
          </PrintSection>

          <PrintSection title="RECIBO">
            <div class="text-xs p-2">
              <p class="mb-4">
                Declaro ter recebido as informações cadastrais do beneficiário acima identificado.
              </p>
              <div class="grid grid-cols-2 gap-8 mt-8">
                <div class="text-center">
                  <div class="border-t border-black pt-2">
                    <p>Assinatura do Responsável</p>
                  </div>
                </div>
                <div class="text-center">
                  <div class="border-t border-black pt-2">
                    <p>Assinatura do Agente</p>
                  </div>
                </div>
              </div>
            </div>
          </PrintSection>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-gray-600 dark:text-gray-400">
      Nenhum beneficiário selecionado
    </div>
  </BasePrintModal>
</template>
