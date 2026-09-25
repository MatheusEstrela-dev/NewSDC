<template>
  <div class="corte flex items-center" :class="[`corte--${faixa}`, alinharColunas ? '' : 'flex-wrap gap-3']" data-faixa-corte>
    <!-- Na tabela o icone ocupa a largura da coluna Posicao (w-24 menos o
         padding da celula): o texto comeca na coluna Participante, alinhado
         aos nomes, em todas as faixas. -->
    <span v-if="alinharColunas || faixa === 'diamante' || !conceito" class="flex shrink-0 items-center" :class="alinharColunas ? 'w-24' : ''">
      <img v-if="faixa === 'diamante'" src="/imgs/diamante.png" class="h-9 w-9 drop-shadow-sm" alt="Faixa Diamante" />
      <RankingFaixaBadge v-else-if="!conceito" :faixa="faixa" size="sm" />
    </span>
    <div class="min-w-0">
      <p class="corte__nome text-sm font-extrabold uppercase tracking-wider">{{ conceito?.nome ?? 'Em apuração' }}</p>
      <p class="text-xs text-slate-600 dark:text-slate-300">
        <span v-if="conceito">{{ conceito.lema }} · </span>{{ descricao }}
      </p>
    </div>
  </div>
</template>

<script setup>
/**
 * Cabecalho de grupo da classificacao: marca onde comeca uma faixa, com o lema
 * e o corte de pontos. Os limiares chegam do controller
 * (config('ranking.faixas')); o fallback local repete FaixaRanking.
 *
 * So o Diamante leva icone: as demais faixas ja sao marcadas pelas medalhas
 * das linhas. O cabecalho mostra sempre o NOME DA FAIXA, nunca o participante.
 */
import { computed } from 'vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';

const LIMIARES_PADRAO = {
  bronze: { min: 0, max: 299 },
  prata: { min: 300, max: 699 },
  ouro: { min: 700, max: 6999 },
  diamante: { min: 7000, max: null },
};

const CONCEITOS = {
  diamante: { nome: 'Diamante', lema: 'Patamar de elite, alcançado por poucos' },
  ouro: { nome: 'Ouro', lema: 'Referência em entregas no período' },
  prata: { nome: 'Prata', lema: 'Participação consistente' },
  bronze: { nome: 'Bronze', lema: 'Início da jornada: cada entrega conta' },
};

const props = defineProps({
  faixa: { type: String, default: null },
  limiares: { type: Object, default: null },
  // Tabela: reserva a coluna Posicao para o icone. Card (celular): compacto.
  alinharColunas: { type: Boolean, default: false },
});

const formatador = new Intl.NumberFormat('pt-BR');
const conceito = computed(() => CONCEITOS[props.faixa] ?? null);

const descricao = computed(() => {
  const limiar = (props.limiares ?? LIMIARES_PADRAO)[props.faixa] ?? LIMIARES_PADRAO[props.faixa];
  if (!limiar) return 'Faixa em apuração';
  const min = Number(limiar.min ?? 0);
  if (limiar.max === null || limiar.max === undefined) return `a partir de ${formatador.format(min)} pontos`;
  if (min === 0) return `até ${formatador.format(Number(limiar.max))} pontos`;
  return `de ${formatador.format(min)} a ${formatador.format(Number(limiar.max))} pontos`;
});
</script>

<style scoped>
.corte { --faixa-cor: 100 116 139; }
.corte--diamante { --faixa-cor: 56 189 248; }
.corte--ouro { --faixa-cor: 245 158 11; }
.corte--prata { --faixa-cor: 148 163 184; }
.corte--bronze { --faixa-cor: 194 120 63; }
.corte__nome { color: rgb(var(--faixa-cor)); }
</style>
