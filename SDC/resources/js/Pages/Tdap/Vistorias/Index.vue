<template>
  <Head title="TDAP — Vistorias" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Vistorias de Veículos"
      description="Inspeções técnicas dos caminhões-tanque (vigência 12 meses)"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.vistorias.create')">
          <PrimaryButton>Nova Vistoria</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Aprovadas</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ estatisticas.aprovadas }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Vigentes</p>
        <p class="text-2xl font-semibold text-blue-600">{{ estatisticas.vigentes }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Expiradas</p>
        <p class="text-2xl font-semibold text-amber-600">{{ estatisticas.expiradas }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Reprovadas</p>
        <p class="text-2xl font-semibold text-red-600">{{ estatisticas.reprovadas }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <input v-model="filtroSearch" type="text" placeholder="Buscar vistoriador, edital, ficha..." class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm md:col-span-2" @keyup.enter="aplicarFiltros" />
        <select v-model="filtroCaminhao" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Caminhão</option>
          <option v-for="c in caminhoes" :key="c.id" :value="c.id">{{ c.placa }}</option>
        </select>
        <select v-model="filtroParecer" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Parecer</option>
          <option v-for="p in pareceres" :key="p.value" :value="p.value">{{ p.label }}</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="filtroVigente" @change="aplicarFiltros" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          Apenas vigentes
        </label>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Data</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Caminhão</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Vistoriador</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ficha</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Parecer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Vigência</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="v in vistorias.data" :key="v.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3">{{ fmtDate(v.data) }}</td>
            <td class="px-4 py-3">
              <Link :href="route('tdap.vistorias.show', v.id)" class="font-mono font-semibold text-blue-600 hover:text-blue-800">{{ v.caminhao_placa }}</Link>
              <p v-if="v.caminhao_modelo" class="text-xs text-slate-500">{{ v.caminhao_modelo }}</p>
            </td>
            <td class="px-4 py-3">
              <p>{{ v.nome }}</p>
              <p v-if="v.prestador_nome" class="text-xs text-slate-500">{{ v.prestador_nome }}</p>
            </td>
            <td class="px-4 py-3 font-mono text-xs">{{ v.ficha || '—' }}</td>
            <td class="px-4 py-3">
              <span :class="v.parecer === 'aprovada'
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ v.parecer_label }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span v-if="v.esta_vigente" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-blue-500"></span>
                Vigente
              </span>
              <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                Expirada
              </span>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <Link :href="route('tdap.vistorias.show', v.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.vistorias.edit', v.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="vistorias.data.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-slate-400">Nenhuma vistoria cadastrada.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="vistorias.meta && vistorias.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">Página {{ vistorias.meta.current_page }} de {{ vistorias.meta.last_page }} ({{ vistorias.meta.total }} registros)</p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in vistorias.meta.links || []"
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
  vistorias:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total:0,aprovadas:0,reprovadas:0,vigentes:0,expiradas:0 }) },
  caminhoes:    { type: Array, default: () => [] },
  pareceres:    { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch   = ref(props.filtros.search ?? '');
const filtroCaminhao = ref(props.filtros.placa_id ?? '');
const filtroParecer  = ref(props.filtros.parecer ?? '');
const filtroVigente  = ref(Boolean(props.filtros.vigente));

function aplicarFiltros() {
  router.get(route('tdap.vistorias.index'), {
    search:   filtroSearch.value || undefined,
    placa_id: filtroCaminhao.value || undefined,
    parecer:  filtroParecer.value || undefined,
    vigente:  filtroVigente.value ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
