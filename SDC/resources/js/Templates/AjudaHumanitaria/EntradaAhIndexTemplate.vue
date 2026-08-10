<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Entradas de Material"
      description="Recebimentos nos depósitos, migrados do sistema anterior."
      :icon="UploadIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Button variant="success" size="md" :icon="DownloadIcon" icon-position="left" @click="mostrarModalExport = true">
            <span class="hidden sm:inline">Exportar</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <ExportCsvModal
      :show="mostrarModalExport"
      module-name="Entradas"
      @close="mostrarModalExport = false"
      @export="exportar"
    />

    <EntradaAhStatsCards :statistics="estatisticas" @filter="$emit('filtrar', $event)" />

    <EntradaAhFiltersSection
      :filters="filtros"
      :depositos="depositos"
      :fontes="fontes"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <ListContainer
      title="Entradas"
      :icon="UploadIcon"
      :count="entradas.meta?.total ?? 0"
      icon-class="text-blue-500"
    >
      <ListEmptyState
        v-if="!entradas.data.length"
        :icon="UploadIcon"
        title="Nenhuma entrada encontrada"
        helper="Ajuste o depósito, a fonte ou o período."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th scope="col" class="px-4 py-3 font-medium">Código</th>
              <th scope="col" class="px-4 py-3 font-medium">Depósito</th>
              <th scope="col" class="px-4 py-3 font-medium">Recebido</th>
              <th scope="col" class="px-4 py-3 font-medium">Nota fiscal</th>
              <th scope="col" class="px-4 py-3 font-medium">Fonte</th>
              <th scope="col" class="px-4 py-3 font-medium text-right">Quantidade</th>
              <th scope="col" class="px-4 py-3 font-medium">Situação</th>
              <th scope="col" class="px-4 py-3 font-medium text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr v-for="linha in entradas.data" :key="linha.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ linha.codigo_legado }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-900 dark:text-white">{{ linha.sigla }}</span>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ formatarData(linha.recebido_em) }}
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ linha.nota_fiscal || '—' }}</td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-xs truncate" :title="linha.fonte">
                {{ linha.fonte || '—' }}
              </td>
              <td
                class="px-4 py-3 text-right font-semibold tabular-nums"
                :class="linha.quantidade < 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'"
              >
                {{ numero.format(linha.quantidade) }}
              </td>
              <td class="px-4 py-3">
                <Badge v-if="linha.cancelado" variant="danger">Cancelada</Badge>
                <Badge v-else-if="linha.quantidade < 0" variant="warning">Correção</Badge>
                <Badge v-else variant="success">Ativa</Badge>
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end">
                  <ActionButton
                    module="humanitaria"
                    resource="saldo"
                    size="sm"
                    :actions="[{ action: 'view', handler: () => $emit('ver', linha.id) }]"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ListContainer>

    <div v-if="entradas.meta" class="mt-6">
      <Pagination :pagination="entradas.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import UploadIcon from '@/Components/Icons/UploadIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import EntradaAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/EntradaAhFiltersSection.vue';
import EntradaAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/EntradaAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  entradas: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  fontes: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['filtrar', 'pagina', 'ver', 'exportar']);

const numero = new Intl.NumberFormat('pt-BR');
const mostrarModalExport = ref(false);

function formatarData(iso) {
  if (!iso) return '—';

  const [ano, mes, dia] = iso.slice(0, 10).split('-');

  return `${dia}/${mes}/${ano}`;
}

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}
</script>
