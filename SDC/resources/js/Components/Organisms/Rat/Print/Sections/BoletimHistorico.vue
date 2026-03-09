<script setup>
const props = defineProps({
  historico: {
    type: [String, Array],
    default: null,
  },
});

function buildHistoricoText(val) {
  if (Array.isArray(val)) {
    return val
      .map(h => (typeof h === 'object' ? (h.descricao || h.observacao || JSON.stringify(h)) : String(h)))
      .filter(Boolean)
      .join('\n');
  }
  return typeof val === 'string' ? val : '';
}

const historicoText = buildHistoricoText(props.historico);
</script>

<template>
  <div>
    <div class="section-title">HISTORICO DA OCORRENCIA / ATIVIDADE</div>

    <table class="bos-table">
      <tr>
        <td class="field-value historico-text" style="min-height: 150px; padding: 20px; vertical-align: top;">
          {{ historicoText || 'NAO DESCRITO' }}
        </td>
      </tr>
    </table>
  </div>
</template>

<style scoped>
.historico-text {
  min-height: 100px;
  padding: 8px;
  text-align: justify;
  white-space: pre-wrap;
  font-size: 9px;
  line-height: 1.3;
}
</style>
