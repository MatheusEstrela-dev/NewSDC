<template>
  <ProgressoBar :percentual="percentual" :rotulo="rotulo" :variant="variant" :title="titulo" />
</template>

<script setup>
/**
 * Execucao do cronograma em viagens, na mesma barra de VistoriaProgressoBar
 * (Tdap/Frota): realizadas / previstas, e nao so o numero cru -- 8 de 13 diz
 * mais em uma barra que em duas colunas separadas.
 *
 * `previstas`/`realizadas` vem prontos do backend
 * (Cronograma::viagens_previstas / viagens_realizadas, CronogramaIndexResource):
 * previstas e a soma de `num_viagens` dos caminhoes alocados, realizadas conta
 * so CronoViagem com `validado = APROVADA` -- mesmo criterio de
 * CronoCaminhaoService::recalcularEntregas.
 */
import { computed } from 'vue';
import ProgressoBar from '@/Components/Atoms/Progress/ProgressoBar.vue';

const props = defineProps({
  previstas: { type: Number, default: 0 },
  realizadas: { type: Number, default: 0 },
});

const semPrevisao = computed(() => !props.previstas || props.previstas <= 0);

const percentual = computed(() => {
  if (semPrevisao.value) return 0;

  return Math.round(Math.min(props.realizadas, props.previstas) / props.previstas * 100);
});

const variant = computed(() => {
  if (semPrevisao.value) return 'neutral';
  if (percentual.value >= 100) return 'success';
  if (percentual.value >= 50) return 'warning';

  return 'danger';
});

const rotulo = computed(() => (semPrevisao.value
  ? 'Sem previsão'
  : `${percentual.value}% · ${props.realizadas}/${props.previstas} viagens`));

const titulo = computed(() => (semPrevisao.value
  ? 'Nenhum caminhão alocado com viagens previstas'
  : `${props.realizadas} viagem(ns) realizada(s) de ${props.previstas} prevista(s)`));
</script>
