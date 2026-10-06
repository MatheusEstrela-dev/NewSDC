<template>
  <ProgressoBar
    :percentual="percentual"
    :rotulo="rotulo"
    :title="titulo"
    :sem-dados="semPrevisao"
    :concluido="concluido"
    :atrasado="atrasado"
  />
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
 *
 * `diasRestantes` e Cronograma::dias_restantes (ate dt_final_efetiva, ja com a
 * prorrogacao): negativo = prazo vencido.
 */
import { computed } from 'vue';
import ProgressoBar from '@/Components/Atoms/Progress/ProgressoBar.vue';

const props = defineProps({
  previstas: { type: Number, default: 0 },
  realizadas: { type: Number, default: 0 },
  /** Assinado: negativo = prazo vencido, null = sem data final. */
  diasRestantes: { type: Number, default: null },
});

const semPrevisao = computed(() => !props.previstas || props.previstas <= 0);

const concluido = computed(() => !semPrevisao.value && props.realizadas >= props.previstas);

const atrasado = computed(() => props.diasRestantes !== null && props.diasRestantes < 0);

const percentual = computed(() => {
  if (semPrevisao.value) return 0;

  return Math.round(Math.min(props.realizadas, props.previstas) / props.previstas * 100);
});

// O motivo da cor vai escrito: barra vermelha sem dizer "prazo vencido" parece
// so execucao baixa.
const situacao = computed(() => {
  if (concluido.value) return ' · concluído';
  if (atrasado.value) return ' · prazo vencido';
  return '';
});

const rotulo = computed(() => (semPrevisao.value
  ? 'Sem previsão'
  : `${percentual.value}% · ${props.realizadas}/${props.previstas} viagens${situacao.value}`));

const titulo = computed(() => {
  if (semPrevisao.value) return 'Nenhum caminhão alocado com viagens previstas';

  const execucao = `${props.realizadas} viagem(ns) realizada(s) de ${props.previstas} prevista(s)`;

  return atrasado.value && !concluido.value
    ? `${execucao} · prazo encerrado há ${Math.abs(props.diasRestantes)} dia(s)`
    : execucao;
});
</script>
