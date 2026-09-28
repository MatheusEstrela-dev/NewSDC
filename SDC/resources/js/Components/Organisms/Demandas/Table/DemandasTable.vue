<template>
  <div v-if="isDesktop" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th v-for="col in COLUNAS" :key="col" scope="col" :class="TH">{{ col }}</th>
            <th scope="col" class="table-actions-head w-16 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr v-for="d in demandas" :key="d.id" class="table-row-solid transition-colors">
            <td :class="TD_FORTE">#{{ d.legado_id ?? d.id }}</td>
            <td :class="TD"><span class="block max-w-[14rem] truncate" :title="d.titulo">{{ d.titulo }}</span></td>
            <td :class="TD"><span class="block max-w-[10rem] truncate">{{ d.assunto?.nome ?? '—' }}</span></td>
            <td :class="TD">{{ d.assunto?.categoria ?? '—' }}</td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.criado_por?.name ?? '—' }}</span></td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.solicitante?.name ?? '—' }}</span></td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.atribuido_para?.name ?? '—' }}</span></td>
            <td :class="TD"><DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" /></td>
            <td :class="TD"><DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" :itil="d.prioridade_itil" /></td>
            <td :class="TD">{{ formatarDataHora(d.created_at) }}</td>
            <td :class="TD">{{ formatarDataHora(d.resolvido_em) }}</td>
            <td class="table-actions-cell whitespace-nowrap px-3 py-2 text-right">
              <ActionButton action="view" module="demandas" resource="chamados" :allowed="true" :show-label="false" size="sm" tooltip-text="Abrir demanda" @click="$emit('abrir', d.id)" />
            </td>
          </tr>
          <tr v-if="demandas.length === 0">
            <td :colspan="COLUNAS.length + 1" class="px-3 py-10">
              <ListEmptyState title="Nenhuma demanda encontrada" helper="Ajuste os filtros ou use Limpar para ver todas." />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div v-else class="space-y-3">
    <article v-for="d in demandas" :key="d.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60" @click="$emit('abrir', d.id)">
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">#{{ d.legado_id ?? d.id }} · {{ d.protocolo }}</p>
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ d.titulo }}</h3>
        </div>
        <DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" />
      </header>
      <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
        <div><dt class="text-slate-500">Assunto</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ d.assunto?.nome ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Prioridade</dt><dd><DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" /></dd></div>
        <div><dt class="text-slate-500">Solicitante</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ d.solicitante?.name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Abertura</dt><dd class="text-slate-800 dark:text-slate-200">{{ formatarDataHora(d.created_at) }}</dd></div>
      </dl>
    </article>
    <ListEmptyState v-if="demandas.length === 0" title="Nenhuma demanda encontrada" helper="Ajuste os filtros ou use Limpar para ver todas." />
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({ demandas: { type: Array, required: true } });
defineEmits(['abrir']);

const { isDesktop } = useMobile();
const COLUNAS = ['ID', 'Título', 'Assunto', 'Categoria', 'Quem abriu', 'Solicitante', 'Responsável', 'Status', 'Prioridade', 'Abertura', 'Fechamento'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100';
</script>
