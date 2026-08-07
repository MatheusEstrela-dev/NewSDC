<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Liberações de Material"
      description="Histórico de material liberado aos municípios, migrado do sistema anterior."
      :icon="ClipboardDocumentListIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Consulta de historico: nao ha criar nem editar. A liberacao nasce
               de uma movimentacao de estoque, nao de um formulario. -->
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
      module-name="Liberações"
      @close="mostrarModalExport = false"
      @export="exportar"
    />

    <LiberacaoAhStatsCards :statistics="estatisticas" @filter="$emit('filtrar', $event)" />

    <LiberacaoAhFiltersSection
      :filters="filtros"
      :depositos="depositos"
      :opcoes-status="opcoesStatus"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <ListContainer
      title="Liberações"
      :icon="ClipboardDocumentListIcon"
      :count="liberacoes.meta?.total ?? 0"
      icon-class="text-blue-500"
    >
      <ListEmptyState
        v-if="!liberacoes.data.length"
        :icon="ClipboardDocumentListIcon"
        title="Nenhuma liberação encontrada"
        helper="Ajuste o depósito, a situação ou o período."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th scope="col" class="px-4 py-3 font-medium">Código</th>
              <th scope="col" class="px-4 py-3 font-medium">Município</th>
              <th scope="col" class="px-4 py-3 font-medium">Depósito</th>
              <th scope="col" class="px-4 py-3 font-medium">Beneficiário</th>
              <th scope="col" class="px-4 py-3 font-medium">Liberação</th>
              <th scope="col" class="px-4 py-3 font-medium">Situação</th>
              <th scope="col" class="px-4 py-3 font-medium text-center">Recibos</th>
              <th scope="col" class="px-4 py-3 font-medium text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="linha in liberacoes.data"
              :key="linha.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
            >
              <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                {{ linha.codigo_legado }}
              </td>
              <td class="px-4 py-3 text-slate-900 dark:text-white whitespace-nowrap">
                {{ linha.municipio }}
                <span v-if="linha.uf" class="text-slate-400 dark:text-slate-500">/{{ linha.uf }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ linha.sigla }}</span>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-xs truncate" :title="linha.beneficiario">
                {{ linha.beneficiario || '—' }}
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ formatarData(linha.data_libera) }}
              </td>
              <td class="px-4 py-3">
                <Badge :variant="linha.status_cor">{{ linha.status_label }}</Badge>
              </td>
              <td class="px-4 py-3 text-center tabular-nums text-slate-600 dark:text-slate-300">
                {{ linha.recibos }}
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

    <div v-if="liberacoes.meta" class="mt-6">
      <Pagination :pagination="liberacoes.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import LiberacaoAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/LiberacaoAhFiltersSection.vue';
import LiberacaoAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/LiberacaoAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  liberacoes: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['filtrar', 'pagina', 'ver', 'exportar']);

const mostrarModalExport = ref(false);

function formatarData(iso) {
  if (!iso) return '—';

  // Monta a data em horario local a partir das partes: new Date('2022-03-08')
  // e interpretado como UTC e volta um dia atras em fuso negativo.
  const [ano, mes, dia] = iso.split('-');

  return `${dia}/${mes}/${ano}`;
}

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}
</script>
