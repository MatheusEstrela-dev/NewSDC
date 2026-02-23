<script setup>
import { computed } from 'vue';

const props = defineProps({
  numeroDocumento: {
    type: String,
    default: 'N/A',
  },
  tipoDocumento: {
    type: String,
    default: 'Documento',
  },
  criadoPor: {
    type: String,
    default: 'Sistema',
  },
  dataCriacao: {
    type: String,
    default: null,
  },
  diasAlteracao: {
    type: Number,
    default: 30,
  },
});

const dataLimiteAlteracao = computed(() => {
  const baseDate = props.dataCriacao ? new Date(props.dataCriacao) : new Date();
  baseDate.setDate(baseDate.getDate() + props.diasAlteracao);
  return baseDate.toLocaleDateString('pt-BR');
});
</script>

<template>
  <div>
    <div class="section-title">RECIBO DA AUTORIDADE A QUE SE DESTINA</div>

    <div class="subsection-title">DESTINATARIO / RECIBO</div>

    <table class="bos-table">
      <tr>
        <td class="field-value recibo-text" style="padding: 8px; text-align: justify; line-height: 1.3; font-size: 9px;">
          Recebi o "{{ tipoDocumento }}" de Numero <strong>{{ numeroDocumento }}</strong> para conhecimento e providencias, bem como as informacoes que, existindo, estejam descritas ou assinaladas neste documento.
        </td>
      </tr>
    </table>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="15%">DIGITADOR:</td>
        <td class="field-value" width="35%"></td>
        <td class="field-label" width="15%">CRIADO POR:</td>
        <td class="field-value">{{ criadoPor }}</td>
      </tr>
    </table>

    <table class="bos-table mb-3">
      <tr>
        <td class="field-value" style="font-size: 9px; color: #666;">
          Registro sujeito a alteracoes ate o dia {{ dataLimiteAlteracao }}
        </td>
      </tr>
    </table>

    <table class="bos-table">
      <tr>
        <td class="field-label" width="20%">ASSINATURA:</td>
        <td class="field-value" style="text-align: center; padding-top: 15px;">
          ___________________________________________________
        </td>
      </tr>
    </table>
  </div>
</template>
