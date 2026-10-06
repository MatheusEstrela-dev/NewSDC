<template>
  <TdapPainel
    rotulo="Cobertura"
    titulo="Distribuição por região"
    subtitulo="Viagens programadas dos cronogramas ativos, por mesorregião"
    :link-href="linkCronogramas"
    link-rotulo="Detalhes"
  >
    <ListEmptyState
      v-if="cobertura.regioes.length === 0"
      :icon="MapIcon"
      title="Nenhuma viagem programada"
      helper="Os cronogramas ativos ainda não têm caminhões com viagens previstas."
    />

    <div v-else class="flex p-5">
      <DonutDistribuicao :itens="itens" rotulo-total="Viagens previstas" />
    </div>

    <template #rodape>
      <dl class="grid grid-cols-2 divide-x divide-slate-200 dark:divide-slate-700/40">
        <div class="pr-4" :title="`${cobertura.viagens_realizadas} de ${cobertura.viagens_previstas} viagens previstas`">
          <dt class="text-xs text-slate-500 dark:text-slate-400">Execução das viagens</dt>
          <dd class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">
            {{ cobertura.percentual_execucao === null ? '—' : `${cobertura.percentual_execucao}%` }}
          </dd>
        </div>
        <div class="pl-4">
          <dt class="text-xs text-slate-500 dark:text-slate-400">Municípios atendidos no mês</dt>
          <dd class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">
            {{ cobertura.municipios_atendidos_mes }} de {{ cobertura.municipios }}
          </dd>
        </div>
      </dl>
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Participacao das mesorregioes nas viagens programadas (TdapDashboardService::cobertura),
 * no donut da Visao Geral (DonutDistribuicao). O servidor ja agrupa as menores
 * em "Outras regioes" e fecha os percentuais em 100; aqui so se desenha.
 */
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import DonutDistribuicao from '@/Components/Molecules/Statistics/DonutDistribuicao.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';

const props = defineProps({
  cobertura: { type: Object, required: true },
  /** Destino do "Detalhes"; vazio quando a pessoa nao pode abrir Cronogramas. */
  linkCronogramas: { type: String, default: '' },
});

// Uma cor por fatia, na ordem do servidor (maior participacao primeiro).
const CORES = ['#3b82f6', '#22d3ee', '#34d399', '#a78bfa'];

const itens = computed(() => props.cobertura.regioes.map((r, i) => ({
  name: r.regiao,
  value: r.viagens_previstas,
  percent: r.percentual,
  color: CORES[i],
  title: `${r.municipios} município(s) · ${r.viagens_previstas.toLocaleString('pt-BR')} viagens previstas`,
})));
</script>
