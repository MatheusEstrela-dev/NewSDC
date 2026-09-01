<template>
  <div class="pedidos-ah-container">
    <PageHeader
      title="Pedidos de Ajuda Humanitária"
      description="Solicitações de material dos municípios ao CEDEC"
      :icon="HeartIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="$emit('create')"
          >
            <span>Novo Pedido</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <PedidoAhStatsCards
      class="mt-6"
      :estatisticas="estatisticas"
      @filtrar="(f) => $emit('filtrar', f)"
    />

    <PedidoAhFiltersSection
      class="mt-6"
      :filtros="filtrosLocais"
      :opcoes-status="opcoesStatus"
      @filtro-alterado="aoAlterarFiltro"
      @aplicar="aplicar"
      @limpar="limpar"
    />

    <ListContainer title="Lista de Pedidos" :icon="DocumentTextIcon" :count="linhas.length">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead>
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th class="px-4 py-3">Número</th>
              <th class="px-4 py-3">Município</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">População</th>
              <th class="px-4 py-3">Decreto</th>
              <th class="px-4 py-3">Entrada</th>
              <th class="px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr
              v-for="linha in linhas"
              :key="linha.id"
              class="text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60"
            >
              <td class="px-4 py-3 font-semibold">{{ linha.identificador }}</td>
              <td class="px-4 py-3">{{ linha.municipio?.nome ?? '—' }}</td>
              <td class="px-4 py-3">
                <PedidoAhStatusBadge
                  :status="linha.status"
                  :label="linha.status_label"
                  :cor="linha.status_cor"
                />
              </td>
              <td class="px-4 py-3">{{ formatarNumero(linha.pop_atendida) }}</td>
              <td class="px-4 py-3">{{ linha.numero_decreto ?? '—' }}</td>
              <td class="px-4 py-3">{{ formatarData(linha.data_entrada_sistema) }}</td>
              <td class="table-actions-cell w-40 min-w-40 px-4 py-3 text-right">
                <div class="flex justify-end">
                  <ActionButton
                    module="humanitaria"
                    resource="pedidos"
                    size="sm"
                    :actions="[
                      { action: 'view', handler: () => $emit('view', linha.id) },
                    ]"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="!linhas.length">
              <td colspan="7" class="p-0">
                <ListEmptyState title="Nenhum pedido encontrado" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ListContainer>

    <div v-if="paginacao" class="mt-6">
      <Pagination
        :pagination="paginacao"
        @page-change="(page) => emit('filtrar', { ...filtrosLocais, page })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PedidoAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/PedidoAhFiltersSection.vue';
import PedidoAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/PedidoAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  pedidos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({}) },
  opcoesStatus: { type: Array, default: () => [] },
  canCreate: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'view', 'filtrar']);

const linhas = computed(() => props.pedidos?.data ?? []);

// O Pagination do projeto espera o objeto de paginacao cru do Laravel, que a
// collection do Inertia expoe em meta.
const paginacao = computed(() => props.pedidos?.meta ?? null);

const filtrosLocais = reactive({
  search: props.filtros?.search ?? '',
  status: props.filtros?.status ?? '',
  ano: props.filtros?.ano ?? '',
});

function aoAlterarFiltro({ campo, valor }) {
  filtrosLocais[campo] = valor;
}

function aplicar() {
  emit('filtrar', { ...filtrosLocais });
}

function limpar() {
  filtrosLocais.search = '';
  filtrosLocais.status = '';
  filtrosLocais.ano = '';
  emit('filtrar', {});
}

function formatarNumero(valor) {
  return typeof valor === 'number' ? valor.toLocaleString('pt-BR') : '—';
}

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}
</script>
