<template>
  <Head title="TDAP — Caminhões" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Caminhões-Tanque"
      description="Frota dos prestadores autorizados a transportar água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.caminhoes.create')">
          <PrimaryButton>Novo Caminhão</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Ativos</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ estatisticas.ativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Inativos</p>
        <p class="text-2xl font-semibold text-slate-400">{{ estatisticas.inativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Capacidade total (m³)</p>
        <p class="text-2xl font-semibold text-blue-600">{{ Number(estatisticas.capacidade_total_m3 || 0).toFixed(2) }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="flex flex-col md:flex-row gap-3">
        <input
          v-model="filtroSearch"
          type="text"
          placeholder="Buscar por placa, marca ou modelo..."
          class="flex-1 border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @keyup.enter="aplicarFiltros"
        />
        <select
          v-model="filtroPrestador"
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @change="aplicarFiltros"
        >
          <option value="">Todos os prestadores</option>
          <option v-for="p in prestadores" :key="p.id" :value="p.id">{{ p.nome }}</option>
        </select>
        <select
          v-model="filtroAtivo"
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @change="aplicarFiltros"
        >
          <option value="">Status</option>
          <option value="1">Ativos</option>
          <option value="0">Inativos</option>
        </select>
        <PrimaryButton @click="aplicarFiltros">Filtrar</PrimaryButton>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Placa</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Prestador</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Marca / Modelo</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Capacidade (m³)</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="c in caminhoes.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono font-semibold text-slate-900 dark:text-slate-100">
              <Link :href="route('tdap.caminhoes.show', c.id)" class="hover:text-blue-600">{{ c.placa }}</Link>
            </td>
            <td class="px-4 py-3 text-sm">
              <p class="text-slate-900 dark:text-slate-100">{{ c.prestador_nome }}</p>
              <p class="text-xs text-slate-500 font-mono">{{ c.prestador_cnpj }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              <span v-if="c.marca || c.modelo">{{ c.marca }} {{ c.modelo }}<span v-if="c.ano" class="text-slate-400"> ({{ c.ano }})</span></span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300 font-mono">{{ Number(c.capacidade_m3).toFixed(2) }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="c.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ c.ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.caminhoes.show', c.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.caminhoes.edit', c.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="caminhoes.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhum caminhão cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="caminhoes.meta && caminhoes.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ caminhoes.meta.current_page }} de {{ caminhoes.meta.last_page }} ({{ caminhoes.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in caminhoes.meta.links || []"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 text-sm rounded border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-700 dark:text-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  caminhoes:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, inativos: 0, capacidade_total_m3: 0 }) },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch    = ref(props.filtros.search ?? '');
const filtroPrestador = ref(props.filtros.prestador_id ?? '');
const filtroAtivo     = ref(props.filtros.ativo ?? '');

function aplicarFiltros() {
  router.get(route('tdap.caminhoes.index'), {
    search:        filtroSearch.value || undefined,
    prestador_id:  filtroPrestador.value || undefined,
    ativo:         filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  }, { preserveState: true, replace: true });
}
</script>
