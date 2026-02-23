<script setup>
import { computed } from 'vue';

const props = defineProps({
  agentes: {
    type: Array,
    default: () => [],
  },
});

const agentesPares = computed(() => {
  const pares = [];
  for (let i = 0; i < props.agentes.length; i += 2) {
    pares.push({
      primeiro: props.agentes[i],
      segundo: props.agentes[i + 1] || null,
    });
  }
  return pares;
});
</script>

<template>
  <div>
    <div class="section-title">SERVIDORES INTEGRANTES</div>

    <template v-if="agentes.length > 0">
      <table class="bos-table">
        <template v-for="(par, index) in agentesPares" :key="index">
          <tr>
            <td class="field-label" width="20%">MATRICULA/MASP</td>
            <td class="field-value" width="30%">{{ par.primeiro?.masp || par.primeiro?.matricula || '' }}</td>
            <td class="field-label" width="20%">MATRICULA/MASP</td>
            <td class="field-value" width="30%">{{ par.segundo?.masp || par.segundo?.matricula || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">NOME COMPLETO</td>
            <td class="field-value">{{ par.primeiro?.nome_completo || '' }}</td>
            <td class="field-label">NOME COMPLETO</td>
            <td class="field-value">{{ par.segundo?.nome_completo || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">PG/CARGO</td>
            <td class="field-value">{{ par.primeiro?.pg_cargo || '' }}</td>
            <td class="field-label">PG/CARGO</td>
            <td class="field-value">{{ par.segundo?.pg_cargo || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">CORPORACAO</td>
            <td class="field-value">{{ par.primeiro?.corporacao || '' }}</td>
            <td class="field-label">CORPORACAO</td>
            <td class="field-value">{{ par.segundo?.corporacao || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">ORGAO</td>
            <td class="field-value">{{ par.primeiro?.orgao || '' }}</td>
            <td class="field-label">ORGAO</td>
            <td class="field-value">{{ par.segundo?.orgao || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">UNIDADE</td>
            <td class="field-value">{{ par.primeiro?.unidade || '' }}</td>
            <td class="field-label">UNIDADE</td>
            <td class="field-value">{{ par.segundo?.unidade || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">FUNCAO</td>
            <td class="field-value">{{ par.primeiro?.funcao || '' }}</td>
            <td class="field-label">FUNCAO</td>
            <td class="field-value">{{ par.segundo?.funcao || '' }}</td>
          </tr>
          <tr>
            <td class="field-label">CONDUTOR</td>
            <td class="field-value">{{ par.primeiro?.is_condutor ? 'SIM' : 'NAO' }}</td>
            <td class="field-label">CONDUTOR</td>
            <td class="field-value">{{ par.segundo ? (par.segundo.is_condutor ? 'SIM' : 'NAO') : '' }}</td>
          </tr>
        </template>
      </table>
    </template>

    <table v-else class="bos-table">
      <tr>
        <td class="field-value text-center" style="padding: 20px;">
          Nenhum servidor integrante cadastrado
        </td>
      </tr>
    </table>
  </div>
</template>
