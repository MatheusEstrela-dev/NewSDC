<script setup>
defineProps({
  historico: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <div>
    <div class="section-title">HISTORICO DA OCORRENCIA / ATIVIDADE</div>

    <table class="bos-table">
      <tr>
        <td class="field-value historico-text" style="min-height: 150px; padding: 20px; vertical-align: top;">
          {{ historico || 'NAO DESCRITO' }}
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
