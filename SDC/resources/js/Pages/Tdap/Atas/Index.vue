<template>
  <Head title="TDAP — Atas" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Atas de Registro de Preços"
      description="Contratos-pai que autorizam o fornecimento de água potável"
      :icon="CalendarIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.atas.create')">
          <PrimaryButton>Nova Ata</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Ativas</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ estatisticas.ativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Vigentes</p>
        <p class="text-2xl font-semibold text-blue-600">{{ estatisticas.vigentes }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Encerradas</p>
        <p class="text-2xl font-semibold text-slate-400">{{ estatisticas.encerradas }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="flex flex-col md:flex-row gap-3">
        <input
          v-model="filtroSearch"
          type="text"
          placeholder="Buscar por número ou histórico..."
          class="flex-1 border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @keyup.enter="aplicarFiltros"
        />
        <select
          v-model="filtroAtivo"
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @change="aplicarFiltros"
        >
          <option value="">Todas</option>
          <option value="1">Ativas</option>
          <option value="0">Inativas</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="filtroVigente" @change="aplicarFiltros" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          Apenas vigentes
        </label>
        <PrimaryButton @click="aplicarFiltros">Filtrar</PrimaryButton>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Vigência</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Lotes</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="a in atas.data" :key="a.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono font-semibold text-slate-900 dark:text-slate-100">
              <Link :href="route('tdap.atas.show', a.id)" class="hover:text-blue-600">{{ a.numero }}</Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              {{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ a.lotes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <span v-if="a.vigente" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Vigente</span>
              <span v-else-if="a.ativo" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Ativa</span>
              <span v-else class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Inativa</span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.atas.show', a.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.atas.edit', a.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="atas.data.length === 0">
            <td colspan="5" class="px-4 py-12 text-center text-slate-400">Nenhuma ata cadastrada.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="atas.meta && atas.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ atas.meta.current_page }} de {{ atas.meta.last_page }} ({{ atas.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in atas.meta.links || []"
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
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  atas:         { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, vigentes: 0, encerradas: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroAtivo  = ref(props.filtros.ativo ?? '');
const filtroVigente = ref(Boolean(props.filtros.vigente));

function aplicarFiltros() {
  router.get(route('tdap.atas.index'), {
    search:  filtroSearch.value || undefined,
    ativo:   filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
    vigente: filtroVigente.value ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

function formatDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
