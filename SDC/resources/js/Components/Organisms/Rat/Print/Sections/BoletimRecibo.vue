<script setup>
import { computed } from 'vue';

const props = defineProps({
  ocorrencia: {
    type: Object,
    required: true,
  },
});

const dataLimiteAlteracao = computed(() => {
  if (!props.ocorrencia?.created_at) {
    const date = new Date();
    date.setDate(date.getDate() + 30);
    return date.toLocaleDateString('pt-BR');
  }
  const date = new Date(props.ocorrencia.created_at);
  date.setDate(date.getDate() + 30);
  return date.toLocaleDateString('pt-BR');
});
</script>

<template>
  <div>
    <div class="section-title">RECIBO DA AUTORIDADE A QUE SE DESTINA OU SEU AGENTE / AUXILIAR POLICIAL OU RECIBO DO RESPONSAVEL CIVIL</div>

    <div class="subsection-title">DESTINATARIO / RECIBO 1</div>

    <table class="bos-table">
      <tr>
        <td class="field-value recibo-text" style="padding: 8px; text-align: justify; line-height: 1.3; font-size: 9px;">
          Recebi o "Boletim de Ocorrencia" de Numero BO <strong>{{ ocorrencia.numero_bos || 'XXXX' }}</strong> para conhecimento e providencias, bem como as pessoas, materiais, objetos, animais, substancias e/ ou documentos que, existindo, estejam descritos ou assinalados neste documento.
        </td>
      </tr>
    </table>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="15%">DIGITADOR:</td>
        <td class="field-value" width="35%"></td>
        <td class="field-label" width="15%">CRIADO POR:</td>
        <td class="field-value">{{ ocorrencia.creator?.name || 'Sistema' }}</td>
      </tr>
    </table>

    <table class="bos-table mb-3">
      <tr>
        <td class="field-value text-muted small" style="font-size: 9px; color: #666;">
          Registro sujeito a alteracoes ate o dia {{ dataLimiteAlteracao }}
        </td>
      </tr>
    </table>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="20%">ASSINATURA:</td>
        <td class="field-value signature-line" style="text-align: center; padding-top: 15px;">
          ___________________________________________________
        </td>
      </tr>
    </table>
  </div>
</template>
