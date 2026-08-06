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
            <span class="hidden sm:inline">Novo Pedido</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Cartoes por fase do processo -->
    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-6">
      <button
        v-for="cartao in cartoes"
        :key="cartao.chave"
        type="button"
        class="rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-blue-400 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500"
        @click="$emit('filtrar-fase', cartao.chave)"
      >
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ cartao.rotulo }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">
          {{ estatisticas?.[cartao.chave] ?? 0 }}
        </p>
      </button>
    </div>

    <!-- Filtros -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Buscar</label>
          <input
            v-model="filtrosLocais.search"
            type="text"
            placeholder="Número, decreto ou município"
            class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            @keyup.enter="aplicar"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Status</label>
          <select
            v-model="filtrosLocais.status"
            class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            @change="aplicar"
          >
            <option value="">Todos</option>
            <option v-for="opcao in opcoesStatus" :key="opcao.value" :value="opcao.value">
              {{ opcao.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Ano</label>
          <input
            v-model="filtrosLocais.ano"
            type="number"
            placeholder="Ex.: 2026"
            class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            @keyup.enter="aplicar"
          />
        </div>

        <div class="flex items-end gap-2">
          <Button variant="primary" size="md" class="flex-1" @click="aplicar">Filtrar</Button>
          <Button variant="secondary" size="md" class="flex-1" @click="limpar">Limpar</Button>
        </div>
      </div>
    </div>

    <!-- Listagem -->
    <ListContainer class="mt-6" title="Lista de Pedidos" :count="linhas.length">
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
              <td class="px-4 py-3">{{ linha.pop_atendida?.toLocaleString('pt-BR') ?? '—' }}</td>
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
      <Pagination :pagination="paginacao" @page-change="(page) => emit('filtrar', { ...filtrosLocais, page })" />
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  pedidos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({}) },
  opcoesStatus: { type: Array, default: () => [] },
  canCreate: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'view', 'filtrar', 'filtrar-fase']);

const linhas = computed(() => props.pedidos?.data ?? []);

// O Pagination do projeto espera o objeto de paginacao cru do Laravel, nao os
// links. A collection do Inertia expoe isso em meta.
const paginacao = computed(() => props.pedidos?.meta ?? null);

const cartoes = [
  { chave: 'total', rotulo: 'Total' },
  { chave: 'em_edicao', rotulo: 'Em edição' },
  { chave: 'em_analise', rotulo: 'Em análise' },
  { chave: 'em_atendimento', rotulo: 'Em atendimento' },
  { chave: 'atendidos', rotulo: 'Atendidos' },
  { chave: 'finalizados', rotulo: 'Finalizados' },
];

const filtrosLocais = reactive({
  search: props.filtros?.search ?? '',
  status: props.filtros?.status ?? '',
  ano: props.filtros?.ano ?? '',
});

function aplicar() {
  emit('filtrar', { ...filtrosLocais });
}

function limpar() {
  filtrosLocais.search = '';
  filtrosLocais.status = '';
  filtrosLocais.ano = '';
  emit('filtrar', {});
}

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}
</script>
