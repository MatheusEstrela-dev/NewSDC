<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import QrCodeIcon from '@/Components/Icons/QrCodeIcon.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ReservaFormModal from '@/Components/Organisms/Plantao/ReservaFormModal.vue';
import ReservasTable from '@/Components/Organisms/Plantao/ReservasTable.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { Link } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
  reservas: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({ status: [] }),
  },
  // Ja mapeado para {value, label} pelo ReservaIndexController.
  viaturas: {
    type: Array,
    default: () => [],
  },
  agenteAtualId: {
    type: Number,
    default: null,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canManage: {
    type: Boolean,
    default: false,
  },
  canScan: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['filter', 'cancelar']);

const localFiltros = reactive({
  status: props.filters?.status ?? '',
  viatura_id: props.filters?.viatura_id ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    Object.assign(localFiltros, {
      status: novos?.status ?? '',
      viatura_id: novos?.viatura_id ?? '',
    });
  },
);

const showFormModal = ref(false);

const onSaved = () => {
  showFormModal.value = false;
  emit('filter', { ...props.filters });
};

const aplicarFiltros = () => {
  emit('filter', {
    ...props.filters,
    status: localFiltros.status || undefined,
    viatura_id: localFiltros.viatura_id || undefined,
  });
};

const limparFiltros = () => {
  Object.assign(localFiltros, { status: '', viatura_id: '' });
  emit('filter', {});
};
</script>

<template>
  <div class="reservas-container">
    <PageHeader
      title="Reserva de Viaturas"
      description="Agende a viatura e retire a chave pelo QR Code"
      :icon="CalendarIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
    >
      <!-- Sem container proprio: o PageHeader ja da o flex-wrap ao slot. -->
      <template #actions>
        <!-- Ciano: mesma cor da acao `qrcode` do ActionButton. -->
        <Link v-if="canScan" :href="route('plantao.chave.scan')">
          <Button variant="info" size="md" :icon="QrCodeIcon" icon-position="left" aria-label="Ler chave">
            <span class="hidden sm:inline">Ler chave</span>
          </Button>
        </Link>

        <Button
          v-if="canCreate"
          variant="primary"
          size="md"
          :icon="PlusIcon"
          icon-position="left"
          @click="showFormModal = true"
        >
          <span>Nova Reserva</span>
        </Button>
      </template>
    </PageHeader>

    <!--
      Quem nao tem `manage` so enxerga as proprias reservas: o recorte e feito
      no ReservaIndexController, e o aviso existe para que a lista curta nao
      pareca erro de filtro.
    -->
    <p
      v-if="!canManage"
      class="mb-4 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400"
    >
      Mostrando apenas as suas reservas.
    </p>

    <CollapsibleSection
      namespace="plantao"
      section-id="reservas-filtros"
      title="Filtros de pesquisa"
      :icon="FunnelIcon"
      tom="neutro"
      class="mb-6"
      :expandido-por-padrao="false"
    >
      <form class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="aplicarFiltros">
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Situacao</span>
          <select
            v-model="localFiltros.status"
            class="w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">Todas</option>
            <option v-for="opt in filterOptions.status" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </label>

        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Viatura</span>
          <select
            v-model="localFiltros.viatura_id"
            class="w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">Todas</option>
            <option v-for="opt in viaturas" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </label>

        <div class="flex items-end gap-2 lg:col-span-2">
          <button
            type="button"
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click="limparFiltros"
          >
            Limpar
          </button>
          <button
            type="submit"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            Pesquisar
          </button>
        </div>
      </form>
    </CollapsibleSection>

    <ReservasTable
      :reservas="reservas"
      :agente-atual-id="agenteAtualId"
      :can-manage="canManage"
      @cancelar="(id) => emit('cancelar', id)"
    />

    <Pagination
      v-if="pagination"
      :pagination="pagination"
      @page-change="(page) => emit('filter', { ...filters, page })"
    />

    <ReservaFormModal
      :show="showFormModal"
      :viaturas="viaturas"
      @close="showFormModal = false"
      @saved="onSaved"
    />
  </div>
</template>

<style scoped>
.reservas-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
