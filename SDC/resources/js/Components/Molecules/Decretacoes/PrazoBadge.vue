<template>
  <Badge
    v-if="diasRestantes !== null"
    :cor="cor"
    size="pill"
    class="whitespace-nowrap"
    :title="tooltipText"
  >
    <ClockIcon class="w-2.5 h-2.5 sm:w-3 sm:h-3 mr-0.5 sm:mr-1" />
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Prazo de vigencia do processo, em dias restantes.
 *
 * Cor explicita e nao variant semantico: o modulo distingue 'critico' (laranja) de
 * 'vencido' (vermelho), e usa green em vez do emerald da semantica. Mapear tudo em
 * success/warning/danger apagaria essa gradacao, que aqui carrega informacao.
 *
 * A receita de pill vem do Badge; antes estava escrita a mao neste arquivo.
 *
 * Nota sobre 'alerta': o mapa antigo usava amber no light e yellow no dark. O amber
 * no light foi escolha deliberada de contraste (o yellow-700 rende pouco sobre
 * branco); o yellow no dark era so o resto do par original. Unificado em amber, que
 * preserva a intencao e elimina a divergencia entre os dois temas.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';
import {
  formatarData,
  rotuloDiasRestantes,
  situacaoVigencia,
} from '@/Composables/decretacoes/useVigencia';

const props = defineProps({
  // Dias restantes assinados: negativo = vencido, 0 = vence hoje, null = sem vigencia
  diasRestantes: {
    type: Number,
    default: null,
  },
  dataVencimento: {
    type: String,
    default: null,
  },
});

const CORES = {
  sem_vigencia: 'slate',
  vencido: 'red',
  critico: 'orange',
  alerta: 'amber',
  vigente: 'green',
};

const label = computed(() => rotuloDiasRestantes(props.diasRestantes));

const cor = computed(() => CORES[situacaoVigencia(props.diasRestantes)] ?? 'slate');

const tooltipText = computed(() => {
  if (!props.dataVencimento) return '';

  const data = formatarData(props.dataVencimento);
  if (!data) return '';

  return props.diasRestantes !== null && props.diasRestantes < 0
    ? `Venceu em: ${data}`
    : `Vence em: ${data}`;
});
</script>
