<template>
  <div class="space-y-4 sm:space-y-6">
    <PageHeader
      title="Movimentações de Estoque"
      description="Extrato do razão: todo lançamento que formou o saldo de cada depósito."
      :icon="ArrowsRightLeftIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Consulta pura: o razão é append-only, e corrigir lançamento é
               lançar o oposto pela operação que o originou. -->
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
      module-name="Movimentações"
      @close="mostrarModalExport = false"
      @export="exportar"
    />

    <MovimentoAhStatsCards :statistics="estatisticas" @filter="filtrarPorCartao" />

    <MovimentoAhFiltersSection
      :filters="filtros"
      :depositos="depositos"
      :opcoes-tipo="opcoesTipo"
      @filter-change="$emit('filtrar', $event)"
      @filter-reset="$emit('filtrar', {})"
    />

    <ListContainer
      title="Lançamentos"
      :icon="ArrowsRightLeftIcon"
      :count="movimentos.meta?.total ?? 0"
      icon-class="text-blue-500"
    >
      <ListEmptyState
        v-if="!movimentos.data.length"
        :icon="ArrowsRightLeftIcon"
        title="Nenhum lançamento encontrado"
        helper="Ajuste o depósito, o tipo ou o período."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <SortableHeader coluna="ocorrido" direcao-inicial="desc" v-bind="ordenacaoUi" @ordenar="ordenar">
                Data
              </SortableHeader>
              <SortableHeader coluna="tipo" v-bind="ordenacaoUi" @ordenar="ordenar">Tipo</SortableHeader>
              <!-- Material e depósito vivem em outra tabela: ordenar por eles
                   exigiria join na consulta que também serve o CSV. -->
              <SortableHeader>Material</SortableHeader>
              <SortableHeader>Depósito</SortableHeader>
              <SortableHeader coluna="quantidade" direcao-inicial="desc" classe="text-right" v-bind="ordenacaoUi" @ordenar="ordenar">
                Quantidade
              </SortableHeader>
              <SortableHeader classe="px-3">Origem</SortableHeader>
              <SortableHeader classe="px-3">Registrado por</SortableHeader>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="linha in movimentos.data"
              :key="linha.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
            >
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                <div>{{ formatarData(linha.ocorrido_em) }}</div>
                <div class="text-xs text-slate-400 dark:text-slate-500">{{ formatarHora(linha.ocorrido_em) }}</div>
              </td>
              <td class="px-4 py-3">
                <Badge :variant="linha.tipo_cor">{{ linha.tipo_label }}</Badge>
              </td>
              <td class="px-4 py-3 text-slate-900 dark:text-white max-w-[13rem] truncate" :title="linha.material">
                {{ linha.material || '—' }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-slate-700 dark:text-slate-200">{{ linha.sigla || '—' }}</span>
              </td>
              <!-- O sinal carrega o sentido do movimento; a cor repete isso
                   para quem varre a coluna sem ler número por número. -->
              <td
                class="px-4 py-3 text-right tabular-nums whitespace-nowrap font-semibold"
                :class="linha.quantidade < 0
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-emerald-700 dark:text-emerald-400'"
              >
                {{ formatarQuantidade(linha.quantidade) }}
                <span class="font-normal text-slate-500 dark:text-slate-400">{{ linha.unidade }}</span>
              </td>
              <td class="px-3 py-3 text-slate-600 dark:text-slate-300 max-w-[9rem] truncate" :title="linha.origem">
                <Link
                  v-if="linha.origem_url"
                  :href="linha.origem_url"
                  class="text-blue-600 hover:underline dark:text-blue-400"
                >
                  {{ linha.origem }}
                </Link>
                <span v-else>{{ linha.origem || '—' }}</span>
              </td>
              <td
                class="px-3 py-3 text-slate-600 dark:text-slate-300 max-w-[9rem] truncate"
                :title="linha.registrado_por"
              >
                {{ linha.registrado_por || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </ListContainer>

    <div v-if="movimentos.meta" class="mt-6">
      <Pagination :pagination="movimentos.meta" @page-change="$emit('pagina', $event)" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import SortableHeader from '@/Components/Molecules/Table/SortableHeader.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MovimentoAhFiltersSection from '@/Components/Organisms/AjudaHumanitaria/MovimentoAhFiltersSection.vue';
import MovimentoAhStatsCards from '@/Components/Organisms/AjudaHumanitaria/MovimentoAhStatsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  movimentos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesTipo: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({ sort: 'ocorrido', direction: 'desc' }) },
});

const emit = defineEmits(['filtrar', 'pagina', 'exportar', 'ordenar']);

const mostrarModalExport = ref(false);

// O backend fala sort/direction; o SortableHeader fala ordenadoPor/direcao.
const ordenacaoUi = computed(() => ({
  ordenadoPor: props.ordenacao?.sort ?? '',
  direcao: props.ordenacao?.direction ?? 'desc',
}));

function ordenar(payload) {
  emit('ordenar', payload);
}

/**
 * O cartao escolhe apenas o sentido. Sem juntar ao recorte atual, clicar em
 * "Saidas" perderia o deposito e o periodo que o usuario ja tinha montado.
 */
function filtrarPorCartao(recorte) {
  emit('filtrar', { ...props.filtros, ...recorte });
}

const numero = new Intl.NumberFormat('pt-BR');

/** O sinal positivo aparece: sem ele, entrada e saida se parecem demais. */
function formatarQuantidade(valor) {
  const formatado = numero.format(valor);

  return valor > 0 ? '+' + formatado : formatado;
}

function formatarData(iso) {
  if (!iso) return '—';

  return new Date(iso).toLocaleDateString('pt-BR');
}

function formatarHora(iso) {
  if (!iso) return '';

  return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function exportar(escopo) {
  emit('exportar', escopo);
  mostrarModalExport.value = false;
}
</script>
