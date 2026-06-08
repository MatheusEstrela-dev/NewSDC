<script setup>
import { computed } from 'vue';

const props = defineProps({
  dados: {
    type: Object,
    default: null,
  },
});

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

const municipioNome = computed(() => {
  if (!props.dados) return '';
  // Prioridade: nome resolvido > campo nome explícito > fallback para código IBGE
  return props.dados.local_municipio_nome || props.dados.municipio?.nome || props.dados.municipio || props.dados.local_municipio || '';
});

const cobradeInfo = computed(() => {
  if (!props.dados) return '';
  if (props.dados.cobrade) {
    return `${props.dados.cobrade.codigo} - ${props.dados.cobrade.descricao}`;
  }
  return props.dados.nat_cobrade_id || '';
});
</script>

<template>
  <div>
    <div class="section-title">DADOS GERAIS</div>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="18%">DATA/HORA DO FATO</td>
        <td class="field-value" width="32%">
          {{ formatDateTime(dados?.data_fato) }}
        </td>
        <td class="field-label" width="20%">DATA/HORA TERMINO ATIVIDADE</td>
        <td class="field-value" width="30%">
          {{ formatDateTime(dados?.data_termino_atividade) }}
        </td>
      </tr>
      <tr>
        <td class="field-label">CODIGO DA OCORRENCIA</td>
        <td class="field-value">{{ dados?.nat_codigo || '' }}</td>
        <td class="field-label">COBRADE</td>
        <td class="field-value">{{ cobradeInfo }}</td>
      </tr>
      <tr>
        <td class="field-label">DATA/HORA DA COMUNICACAO</td>
        <td class="field-value">
          {{ formatDateTime(dados?.data_comunicacao) }}
        </td>
        <td class="field-label">COMO FOI SOLICITADO</td>
        <td class="field-value">{{ dados?.com_ocorrencia_atendimento || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">MUNICIPIO</td>
        <td class="field-value">{{ municipioNome }}</td>
        <td class="field-label">PAIS</td>
        <td class="field-value">{{ dados?.local_pais || 'Brasil' }}</td>
      </tr>
      <tr>
        <td class="field-label">ESTADO/UF</td>
        <td class="field-value">{{ dados?.local_estadouf || 'MG' }}</td>
        <td class="field-label">CEP</td>
        <td class="field-value">{{ dados?.local_cep || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">LOGRADOURO 1</td>
        <td class="field-value" colspan="3">{{ dados?.local_logradoura_1 || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">BAIRRO</td>
        <td class="field-value">{{ dados?.local_bairro || '' }}</td>
        <td class="field-label">COMPLEMENTO</td>
        <td class="field-value">{{ dados?.local_complemento || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">NUMERO</td>
        <td class="field-value">{{ dados?.local_numero || '' }}</td>
        <td class="field-label">KM</td>
        <td class="field-value">{{ dados?.local_km || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">CRUZAMENTO</td>
        <td class="field-value">{{ dados?.local_cruzamento || '' }}</td>
        <td class="field-label">PONTO DE REFERENCIA</td>
        <td class="field-value">{{ dados?.local_ponto_referencia || '' }}</td>
      </tr>
      <tr>
        <td class="field-label">TIPO DE LOCALIZACAO</td>
        <td class="field-value" colspan="3">{{ dados?.local_ocorrencia_tipo || '' }}</td>
      </tr>
    </table>
  </div>
</template>
