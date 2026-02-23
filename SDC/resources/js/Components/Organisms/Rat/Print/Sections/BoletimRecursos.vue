<script setup>
defineProps({
  recursos: {
    type: Array,
    default: () => [],
  },
});

function formatDateTime(date, time) {
  if (!date) return '';
  const d = new Date(date);
  let result = d.toLocaleDateString('pt-BR');
  if (time) {
    const t = new Date(time);
    result += ' ' + t.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  return result;
}
</script>

<template>
  <div>
    <div class="section-title">RECURSOS EMPREGADOS</div>

    <template v-if="recursos.length > 0">
      <template v-for="(recurso, index) in recursos" :key="index">
        <div v-if="recursos.length > 1" class="subsection-title">
          RECURSO No {{ index + 1 }}
        </div>

        <table class="bos-table">
          <tr>
            <td class="field-label" width="20%">TIPO DE RECURSO</td>
            <td class="field-value" width="30%">{{ recurso?.r_tipo_recurso || recurso?.recurso_tipo || '' }}</td>
            <td class="field-label" width="20%">CATEGORIA</td>
            <td class="field-value" width="30%">{{ recurso?.r_categoria || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">ORGAO RESPONSAVEL</td>
            <td class="field-value">{{ recurso?.r_orgao_responsavel || recurso?.viatura_orgao || '' }}</td>
            <td class="field-label">IDENTIFICACAO</td>
            <td class="field-value">{{ recurso?.r_identificacao || recurso?.viatura_placa || recurso?.viatura_prefixo || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">DESCRICAO DO RECURSO</td>
            <td class="field-value" colspan="3">{{ recurso?.viatura_descricao || recurso?.r_descricao || recurso?.recurso_descricao || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">DATA/HORA SAIDA</td>
            <td class="field-value">
              {{ formatDateTime(recurso?.r_data_saida, recurso?.r_hora_saida) }}
            </td>
            <td class="field-label">DATA/HORA CHEGADA</td>
            <td class="field-value">
              {{ formatDateTime(recurso?.r_data_chegada, recurso?.r_hora_chegada) }}
            </td>
          </tr>
          <tr>
            <td class="field-label">KM PERCORRIDO</td>
            <td class="field-value">{{ recurso?.r_km_percorrido || '' }}</td>
            <td class="field-label">QUANTIDADE</td>
            <td class="field-value">{{ recurso?.r_quantidade || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">LOCAL DE ORIGEM</td>
            <td class="field-value">{{ recurso?.r_origem || '' }}</td>
            <td class="field-label">LOCAL DE DESTINO</td>
            <td class="field-value">{{ recurso?.r_destino || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">CAPACIDADE/POTENCIA</td>
            <td class="field-value">{{ recurso?.r_capacidade || '' }}</td>
            <td class="field-label">CONDICAO DO RECURSO</td>
            <td class="field-value">{{ recurso?.r_condicao || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">OPERADOR/RESPONSAVEL</td>
            <td class="field-value">{{ recurso?.r_operador_responsavel || '' }}</td>
            <td class="field-label">CONTATO DE EMERGENCIA</td>
            <td class="field-value">{{ recurso?.r_contato || '' }}</td>
          </tr>
          <tr v-if="recurso?.viatura_tipo">
            <td class="field-label">TIPO VIATURA</td>
            <td class="field-value">{{ recurso?.viatura_tipo || '' }}</td>
            <td class="field-label">PLACA</td>
            <td class="field-value">{{ recurso?.viatura_placa || '' }}</td>
          </tr>
        </table>
      </template>
    </template>

    <table v-else class="bos-table">
      <tr>
        <td class="field-value text-center" style="padding: 20px;">
          Nenhum recurso empregado cadastrado
        </td>
      </tr>
    </table>
  </div>
</template>
