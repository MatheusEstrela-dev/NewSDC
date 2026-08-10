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
              <th scope="col" class="px-4 py-3 font-medium">Código</th>
              <th scope="col" class="px-4 py-3 font-medium">Trajeto</th>
              <th scope="col" class="px-4 py-3 font-medium">Saída</th>
              <th scope="col" class="px-4 py-3 font-medium">Chegada</th>
              <th scope="col" class="px-4 py-3 font-medium">Motorista</th>
              <th scope="col" class="px-4 py-3 font-medium">Situação</th>
              <th scope="col" class="px-4 py-3 font-medium text-center">Itens</th>
              <th scope="col" class="px-4 py-3 font-medium text-right">Ações</th>
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
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import TransferenciaAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/TransferenciaAhFiltersSection.vue';
import TransferenciaAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/TransferenciaAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  transferencias: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['filtrar', 'pagina', 'ver', 'exportar']);

const mostrarModalExport = ref(false);

function formatarDataHora(iso) {
  if (!iso) return '—';

  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}
</script>
