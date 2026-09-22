<template>
  <Badge :cor="faixaAtual.cor" :size="size" :title="faixaAtual.rotulo" :aria-label="faixaAtual.rotulo">
    {{ mostrarRotulo ? faixaAtual.rotulo : faixaAtual.sigla }}
  </Badge>
</template>

<script setup>
/**
 * Badge de faixa de atividade do Ranking.
 *
 * Envolve o Badge como PmdaStatusBadge e StatusBadge fazem: a receita de pill
 * (fundo, texto, borda, dark mode) mora num lugar so, e aqui fica apenas o mapa
 * de dominio faixa -> cor da paleta.
 *
 * ATENCAO DE NOMENCLATURA: o modulo Medalhao deste repo tem bronze/silver/gold
 * como CAMADAS DE DADO da arquitetura medallion (ArquivadorBronze,
 * NormalizadorSilver). Nao tem relacao nenhuma com estas faixas, que sao nivel
 * de atividade do participante no placar. Mesmas palavras, dominios diferentes.
 *
 * Classes Tailwind nunca sao montadas aqui: o mapa guarda NOME de cor, e o Badge
 * resolve para literais. Interpolar (`bg-${cor}-100`) nao entraria no CSS final,
 * porque o Tailwind varre literais no codigo-fonte.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

/**
 * Cores escolhidas dentro da paleta que o Badge aceita:
 *   bronze   -> orange       (cobre)
 *   prata    -> slate-forte  (cinza mais denso; o slate simples fica reservado
 *                             para o estado sem faixa, senao os dois se confundem)
 *   ouro     -> amber
 *   diamante -> cyan
 */
const FAIXAS = {
  bronze: { rotulo: 'Bronze', sigla: 'B', cor: 'orange' },
  prata: { rotulo: 'Prata', sigla: 'P', cor: 'slate-forte' },
  ouro: { rotulo: 'Ouro', sigla: 'O', cor: 'amber' },
  diamante: { rotulo: 'Diamante', sigla: 'D', cor: 'cyan' },
};

// Faixa ausente, nula ou desconhecida nao pode quebrar a tela: o placar pode vir
// antes da apuracao do periodo fechar.
const SEM_FAIXA = { rotulo: 'Em apuração', sigla: '—', cor: 'slate' };

const props = defineProps({
  /** bronze | prata | ouro | diamante. Qualquer outro valor cai em "Em apuração". */
  faixa: {
    type: String,
    default: null,
  },

  /** Repassado ao Badge: sm | md | lg | pill. */
  size: {
    type: String,
    default: 'md',
  },

  /**
   * Falso exibe so a inicial da faixa, para coluna estreita de tabela. O rotulo
   * completo continua acessivel via title/aria-label.
   */
  mostrarRotulo: {
    type: Boolean,
    default: true,
  },
});

const faixaAtual = computed(() => FAIXAS[String(props.faixa ?? '').toLowerCase()] ?? SEM_FAIXA);
</script>
