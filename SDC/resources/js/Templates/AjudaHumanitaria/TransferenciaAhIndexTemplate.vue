<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Transferências entre Depósitos"
      description="Movimentação de material entre unidades, migrada do sistema anterior."
      :icon="ArrowsRightLeftIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Button
            variant="success"
            size="md"
            :icon="DownloadIcon"
            icon-position="left"
            @click="mostrarModalExport = true"
          >
            <span class="hidden sm:inline">Exportar</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <ExportCsvModal
      :show="mostrarModalExport"
      module-name="Transferências"
      @close="mostrarModalExport = false"
      @export="exportar"
    />

    <TransferenciaAhStatsCards :statistics="estatisticas" @filter="$emit('filtrar', $event)" />

    <TransferenciaAhFiltersSection
      :filters="filtros"
      :depositos="depositos"
      :opcoes-status="opcoesStatus"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <ListContainer
      title="Transferências"
      :icon="ArrowsRightLeftIcon"
      :count="transferencias.meta?.total ?? 0"
      icon-class="text-blue-500"
    >
      <ListEmptyState
        v-if="!transferencias.data.length"
        :icon="ArrowsRightLeftIcon"
        title="Nenhuma transferência encontrada"
        helper="Ajuste o depósito, a situação ou o período."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <SortableHeader coluna="codigo" v-bind="ordenacaoUi" @ordenar="ordenar">Código</SortableHeader>
              <!-- Trajeto e origem e destino juntos, cada um em outra tabela:
                   ordenar por ele exigiria join na consulta que serve o CSV. -->
              <SortableHeader>Trajeto</SortableHeader>
              <SortableHeader coluna="saida" direcao-inicial="desc" v-bind="ordenacaoUi" @ordenar="ordenar">Saída</SortableHeader>
              <SortableHeader coluna="chegada" direcao-inicial="desc" v-bind="ordenacaoUi" @ordenar="ordenar">Chegada</SortableHeader>
              <SortableHeader coluna="motorista" v-bind="ordenacaoUi" @ordenar="ordenar">Motorista</SortableHeader>
              <SortableHeader coluna="situacao" v-bind="ordenacaoUi" @ordenar="ordenar">Situação</SortableHeader>
              <SortableHeader classe="text-center">Itens</SortableHeader>
              <SortableHeader classe="text-right">Ações</SortableHeader>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="linha in transferencias.data"
              :key="linha.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
            >
              <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                {{ linha.codigo_legado }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-900 dark:text-white">{{ linha.origem_sigla }}</span>
                <span class="mx-2 text-slate-400">&rarr;</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ linha.destino_sigla }}</span>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ formatarDataHora(linha.saiu_em) }}
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ formatarDataHora(linha.chegou_em) }}
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-xs truncate" :title="linha.motorista">
                {{ linha.motorista || '—' }}
              </td>
              <td class="px-4 py-3">
                <Badge :variant="linha.status_cor">{{ linha.status_label }}</Badge>
              </td>
              <td class="px-4 py-3 text-center tabular-nums text-slate-600 dark:text-slate-300">
                {{ linha.itens }}
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end">
                  <ActionButton
                    module="humanitaria"
                    resource="saldo"
                    size="sm"
                    :actions="[
                      { action: 'view', handler: () => $emit('ver', linha.id) },
                    ]"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ListContainer>

    <div v-if="transferencias.meta" class="mt-6">
      <Pagination :pagination="transferencias.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import SortableHeader from '@/Components/Molecules/Table/SortableHeader.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import TransferenciaAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/TransferenciaAhFiltersSection.vue';
import TransferenciaAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/TransferenciaAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  transferencias: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({ sort: 'saida', direction: 'desc' }) },
});

const emit = defineEmits(['filtrar', 'pagina', 'ver', 'exportar', 'ordenar']);

const mostrarModalExport = ref(false);

// O backend fala sort/direction; o SortableHeader fala ordenadoPor/direcao.
const ordenacaoUi = computed(() => ({
  ordenadoPor: props.ordenacao?.sort ?? '',
  direcao: props.ordenacao?.direction ?? 'desc',
}));

function ordenar(payload) {
  emit('ordenar', payload);
}

function formatarDataHora(iso) {
  if (!iso) return '—';

  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}
</script>
