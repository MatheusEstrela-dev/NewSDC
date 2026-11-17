<script setup>
/**
 * Historico do fluxo do curso (Area do Gestor, issue #86): cards em timeline,
 * no espirito da serie historica do TDAP (CronogramaHistoricoModal), mas
 * paginado no servidor (GestaoCursoConsulta::historico) em vez de carregar
 * tudo de uma vez.
 */
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import SecaoRecolhivel from '@/Components/Molecules/Treinamento/SecaoRecolhivel.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  // Paginador "cru" do Laravel (GestaoCursoConsulta::historico): data +
  // current_page/last_page/total na raiz, nao em `meta`.
  historico: { type: Object, required: true },
});

const eventos = computed(() => props.historico?.data ?? []);

const paginacao = computed(() => ({
  current_page: props.historico?.current_page ?? 1,
  last_page: props.historico?.last_page ?? 1,
  per_page: props.historico?.per_page ?? 15,
  total: props.historico?.total ?? 0,
  from: props.historico?.from ?? null,
  to: props.historico?.to ?? null,
}));

const CORES_POR_VARIANT = {
  danger: 'bg-red-600/90 text-white',
  warning: 'bg-amber-500/90 text-white',
  success: 'bg-emerald-600/90 text-white',
  info: 'bg-sky-600/90 text-white',
};

function corDoMarcador(variant) {
  return CORES_POR_VARIANT[variant] ?? 'bg-slate-500 text-white';
}

function formatarDataHora(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleString('pt-BR');
}

function irParaPagina(pagina) {
  router.get(route('treinamentos.gestor.show', props.treinamentoId), { historico_pagina: pagina }, {
    preserveScroll: true,
    preserveState: true,
    only: ['historico'],
  });
}
</script>

<template>
  <SecaoRecolhivel titulo="Histórico" :total="paginacao.total" namespace="treinamento-gestor" secao-id="historico">
    <Text v-if="eventos.length === 0" size="sm" color="muted">Nenhum evento registrado ainda.</Text>

    <ol v-else class="relative ml-4 space-y-5 border-l border-slate-200 pl-6 dark:border-slate-700/60">
      <li v-for="evento in eventos" :key="evento.id" class="relative">
        <span
          class="absolute -left-[1.9rem] flex h-7 w-7 items-center justify-center rounded-full"
          :class="corDoMarcador(evento.variant)"
        >
          <ClockIcon class="h-3.5 w-3.5" />
        </span>

        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-800/60">
          <div class="mb-1 flex flex-wrap items-center gap-2">
            <Badge :variant="evento.variant" size="sm">{{ evento.tipo_label }}</Badge>
            <Text size="xs" color="muted">{{ formatarDataHora(evento.created_at) }}</Text>
          </div>
          <Text size="sm" class="text-slate-700 dark:text-slate-300">{{ evento.descricao }}</Text>
          <Text size="xs" color="muted" class="mt-1">{{ evento.autor_nome || 'Sistema' }}</Text>
        </div>
      </li>
    </ol>

    <Pagination v-if="paginacao.last_page > 1" :pagination="paginacao" @page-change="irParaPagina" />
  </SecaoRecolhivel>
</template>
