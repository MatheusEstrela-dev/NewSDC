<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- icon-image, e nao icon: moduleIcon devolve a URL de um SVG, e a prop
         icon espera um componente Vue para <component :is>. -->
    <PageHeader
      title="Estoque de Ajuda Humanitária"
      description="Saldo de material por depósito, apurado pelo livro de movimentações."
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <ViewModeToggle v-model="modoVisualizacao" />

          <!-- Tela de consulta: a unica acao e levar o recorte atual para fora.
               Nao ha criar nem editar, porque saldo so muda por movimentacao. -->
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
      module-name="Estoque"
      @close="mostrarModalExport = false"
      @export="exportar"
    />

    <EstoqueAhStatsCards :statistics="estatisticas" @filter="$emit('filtrar', $event)" />

    <EstoqueAhFiltersSection
      :filters="filtros"
      :depositos="depositos"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <EstoqueAhGrid v-if="modoVisualizacao === 'grid'" :saldos="saldos.data" />

    <ListContainer
      v-else
      title="Saldo por depósito"
      :icon="ArchiveBoxIcon"
      :count="saldos.meta?.total ?? 0"
      icon-class="text-emerald-500"
    >
      <ListEmptyState
        v-if="!saldos.data.length"
        :icon="ArchiveBoxIcon"
        title="Nenhum saldo encontrado"
        helper="Ajuste o depósito ou o nome do material."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th scope="col" class="px-4 py-3 font-medium">Depósito</th>
              <th scope="col" class="px-4 py-3 font-medium">Material</th>
              <th scope="col" class="px-4 py-3 font-medium text-right">Saldo</th>
              <th scope="col" class="px-4 py-3 font-medium">Unidade</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="linha in saldos.data"
              :key="`${linha.deposito_id}-${linha.material_id}`"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-900 dark:text-white">{{ linha.sigla }}</span>
                <span class="ml-2 text-slate-500 dark:text-slate-400">{{ linha.deposito }}</span>
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ linha.material }}</td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900 dark:text-white tabular-nums">
                {{ formatar(linha.saldo) }}
              </td>
              <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ linha.unidade }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </ListContainer>

    <!-- Fora do ListContainer e com respiro proprio: dentro dele a paginacao
         encostava na ultima linha da tabela. Mesmo arranjo da listagem de
         beneficiarios. -->
    <div v-if="saldos.meta" class="mt-6">
      <Pagination :pagination="saldos.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import EstoqueAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/EstoqueAhFiltersSection.vue';
import EstoqueAhGrid from '@/Components/Organisms/AjudaHumanitaria/EstoqueAhGrid.vue';
import EstoqueAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/EstoqueAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  saldos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['filtrar', 'pagina', 'exportar']);

// Tabela como padrao: saldo se compara melhor em coluna alinhada do que em
// cartao. A grade existe para leitura rapida em tela pequena.
const modoVisualizacao = ref('table');

const mostrarModalExport = ref(false);

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}

const numero = new Intl.NumberFormat('pt-BR');

/** O saldo e numeric(14,3); a casa decimal so aparece quando existe de fato. */
function formatar(valor) {
  return numero.format(valor);
}
</script>
