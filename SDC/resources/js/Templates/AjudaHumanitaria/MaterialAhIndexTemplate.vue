<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Catálogo de Materiais"
      description="Itens que o município pode pedir e que o estoque movimenta."
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Button
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="$emit('novo')"
          >
            <span class="hidden sm:inline">Novo material</span>
          </Button>

          <Button
            variant="success"
            size="md"
            :icon="DownloadIcon"
            icon-position="left"
            @click="$emit('exportar')"
          >
            <span class="hidden sm:inline">Exportar</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <MaterialAhStatsCards :statistics="estatisticas" @filter="$emit('filtrar', $event)" />

    <MaterialAhFiltersSection
      :filters="filtros"
      :unidades="unidades"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <ListContainer
      title="Materiais"
      :icon="ArchiveBoxIcon"
      :count="materiais.meta?.total ?? 0"
      icon-class="text-blue-500"
    >
      <ListEmptyState
        v-if="!materiais.data.length"
        :icon="ArchiveBoxIcon"
        title="Nenhum material encontrado"
        helper="Ajuste a busca, a unidade ou a situação."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <SortableHeader coluna="codigo" v-bind="ordenacaoUi" @ordenar="ordenar">Código</SortableHeader>
              <SortableHeader coluna="nome" v-bind="ordenacaoUi" @ordenar="ordenar">Material</SortableHeader>
              <SortableHeader coluna="unidade" v-bind="ordenacaoUi" @ordenar="ordenar">Unidade</SortableHeader>
              <!-- Saldo total e soma vinda de withSum: ordenar por ele pediria
                   subconsulta na mesma consulta que serve o CSV. -->
              <SortableHeader classe="text-right">Saldo total</SortableHeader>
              <SortableHeader coluna="disponivel" v-bind="ordenacaoUi" @ordenar="ordenar">Pedido</SortableHeader>
              <SortableHeader classe="text-right">Ações</SortableHeader>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="linha in materiais.data"
              :key="linha.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
            >
              <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                {{ linha.codigo_legado || '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900 dark:text-white">{{ linha.nome }}</div>
                <div
                  v-if="linha.descricao"
                  class="max-w-md truncate text-xs text-slate-500 dark:text-slate-400"
                  :title="linha.descricao"
                >
                  {{ linha.descricao }}
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ linha.unidade_medida }}
              </td>
              <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">
                <span
                  v-if="linha.saldo_total"
                  class="font-medium text-slate-900 dark:text-white"
                >{{ formatar(linha.saldo_total) }}</span>
                <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                <div v-if="linha.depositos_com_saldo" class="text-xs text-slate-500 dark:text-slate-400">
                  {{ linha.depositos_com_saldo }}
                  {{ linha.depositos_com_saldo === 1 ? 'depósito' : 'depósitos' }}
                </div>
              </td>
              <td class="px-4 py-3">
                <Badge :variant="linha.disponivel_para_pedido ? 'success' : 'neutral'">
                  {{ linha.disponivel_para_pedido ? 'Disponível' : 'Indisponível' }}
                </Badge>
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end">
                  <ActionButton
                    module="humanitaria"
                    resource="materiais"
                    size="sm"
                    :actions="[
                      { action: 'edit', aliasOverride: 'manage', handler: () => $emit('editar', linha) },
                      { action: 'delete', aliasOverride: 'manage', handler: () => $emit('excluir', linha) },
                    ]"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ListContainer>

    <div v-if="materiais.meta" class="mt-6">
      <Pagination :pagination="materiais.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import SortableHeader from '@/Components/Molecules/Table/SortableHeader.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MaterialAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/MaterialAhFiltersSection.vue';
import MaterialAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/MaterialAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  materiais: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  unidades: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({ sort: 'nome', direction: 'asc' }) },
});

const emit = defineEmits(['filtrar', 'pagina', 'exportar', 'ordenar', 'novo', 'editar', 'excluir']);

// O backend fala sort/direction; o SortableHeader fala ordenadoPor/direcao.
const ordenacaoUi = computed(() => ({
  ordenadoPor: props.ordenacao?.sort ?? '',
  direcao: props.ordenacao?.direction ?? 'asc',
}));

function ordenar(payload) {
  emit('ordenar', payload);
}

const numero = new Intl.NumberFormat('pt-BR');

function formatar(valor) {
  return numero.format(valor);
}

</script>
