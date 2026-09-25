<template>
  <article class="flex flex-col rounded-xl border bg-white p-4 shadow-sm dark:bg-slate-800" :class="item.liberado ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-slate-200 dark:border-slate-700'" data-item-catalogo>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <Badge :cor="TIPOS_ITEM[item.tipo]?.cor ?? 'slate'" size="sm">{{ TIPOS_ITEM[item.tipo]?.rotulo ?? item.tipo }}</Badge>
      <div class="flex flex-wrap gap-1">
        <Badge v-if="item.demonstracao" cor="violet" size="sm">Demonstração</Badge>
        <Badge cor="slate" size="sm">v{{ item.versao }}</Badge>
      </div>
    </div>

    <h3 class="mt-3 text-base font-bold text-slate-900 dark:text-slate-100">{{ item.titulo }}</h3>
    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ item.descricao }}</p>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Libera a partir de</dt>
        <dd class="mt-1"><RankingFaixaBadge :faixa="item.faixa_minima" size="sm" /></dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Custo</dt>
        <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ item.custo_pontos > 0 ? `${numero(item.custo_pontos)} pontos` : 'Só pela faixa' }}</dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Disponível</dt>
        <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ disponibilidade }}</dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Instrumento</dt>
        <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ rotuloInstrumento(item.instrumento) }}</dd>
      </div>
    </dl>

    <p class="mt-4 rounded-lg px-3 py-2 text-xs font-semibold" :class="item.liberado ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-50 text-slate-600 dark:bg-slate-900/60 dark:text-slate-300'" data-item-liberacao>
      {{ item.liberado ? 'Liberado pela faixa do seu município' : `Requer faixa ${rotuloFaixa(item.faixa_minima)} na temporada fechada` }}
    </p>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400">
      <span>{{ item.unidade_responsavel }}</span>
      <div class="flex gap-2">
        <Link v-if="podeGerenciar" :href="route('resgate.catalogo.propostas.nova', { item: item.codigo })" :class="LINK" data-item-gerenciar>Propor mudança</Link>
        <Link v-if="podeGerenciar && item.tipo === 'bem_permanente'" :href="route('resgate.catalogo.unidades.nova', { item: item.codigo })" :class="LINK" data-item-unidade>Cadastrar unidade</Link>
      </div>
    </div>
  </article>
</template>

<script setup>
/**
 * Card de item do catalogo de resgate: tipo, faixa que libera, custo,
 * disponibilidade e se o municipio do usuario ja alcancou a faixa (na
 * temporada fechada). Sem botao de resgatar: a Fase 2 so publica o catalogo.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { Link } from '@inertiajs/vue3';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import { TIPOS_ITEM, rotuloFaixa, rotuloInstrumento } from '@/Support/resgateCatalogo';

const props = defineProps({
  item: { type: Object, required: true },
  podeGerenciar: { type: Boolean, default: false },
});

const LINK = 'rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700';

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));

const disponibilidade = computed(() => {
  if (props.item.tipo === 'bem_permanente') return `${numero(props.item.disponivel)} de ${numero(props.item.unidades_total)} unidades`;
  return props.item.disponivel === null ? 'Sem limite' : `${numero(props.item.disponivel)} vagas/unidades`;
});
</script>
