<template>
  <Head title="TDAP — Processos" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Processos TDAP"
      description="Workflow de habilitação até liquidação"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link :href="route('tdap.processos.swimlanes')">
          <SecondaryButton>Ver Swimlanes</SecondaryButton>
        </Link>
        <Link v-if="canCreate" :href="route('tdap.processos.create')">
          <PrimaryButton>Novo Processo</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Abertos</p>
        <p class="text-2xl font-semibold text-blue-600">{{ estatisticas.abertos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Em execução</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ estatisticas.em_execucao }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Encerrados</p>
        <p class="text-2xl font-semibold text-slate-400">{{ estatisticas.encerrados }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input v-model="filtroSearch" type="text" placeholder="Buscar número..." class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @keyup.enter="aplicarFiltros" />
        <select v-model="filtroEstado" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Estado</option>
          <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
        </select>
        <select v-model="filtroSwimlane" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Swimlane</option>
          <option v-for="s in swimlanes" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <PrimaryButton @click="aplicarFiltros">Filtrar</PrimaryButton>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Estado</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Swimlane</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Município</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Aberto</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="p in processos.data" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 font-mono font-semibold">
              <Link :href="route('tdap.processos.show', p.id)" class="text-blue-600 hover:text-blue-800">{{ p.numero }}</Link>
            </td>
            <td class="px-4 py-3"><EstadoProcessoBadge :estado="p.estado" /></td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ p.swimlane_label }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ p.municipio_nome }}<span v-if="p.municipio_uf" class="text-slate-400">/{{ p.municipio_uf }}</span></td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ fmtDate(p.aberto_em) }}</td>
            <td class="px-4 py-3 text-right">
              <Link :href="route('tdap.processos.show', p.id)" class="text-blue-600 hover:text-blue-800 text-sm">Abrir</Link>
            </td>
          </tr>
          <tr v-if="processos.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhum processo aberto.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="processos.meta && processos.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">Página {{ processos.meta.current_page }} de {{ processos.meta.last_page }} ({{ processos.meta.total }} registros)</p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in processos.meta.links || []"
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
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import EstadoProcessoBadge from '@/Components/Organisms/Tdap/EstadoProcessoBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  processos:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total:0, abertos:0, em_execucao:0, encerrados:0 }) },
  estados:      { type: Array, default: () => [] },
  swimlanes:    { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canTransitar: { type: Boolean, default: false },
});

const filtroSearch   = ref(props.filtros.search ?? '');
const filtroEstado   = ref(props.filtros.estado ?? '');
const filtroSwimlane = ref(props.filtros.swimlane ?? '');

function aplicarFiltros() {
  router.get(route('tdap.processos.index'), {
    search:   filtroSearch.value || undefined,
    estado:   filtroEstado.value || undefined,
    swimlane: filtroSwimlane.value || undefined,
  }, { preserveState: true, replace: true });
}

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('pt-BR');
}
</script>
