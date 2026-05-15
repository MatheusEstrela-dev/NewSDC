<template>
  <Head title="TDAP — Lotes" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Lotes de Fornecimento"
      description="Subdivisões das atas por município e prestador"
      :icon="MapIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.lotes.create')">
          <PrimaryButton>Novo Lote</PrimaryButton>
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
        <p class="text-sm text-slate-500">Volume contratado (m³)</p>
        <p class="text-2xl font-semibold text-blue-600">{{ Number(estatisticas.volume_total_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Valor total (R$)</p>
        <p class="text-2xl font-semibold text-blue-600">{{ Number(estatisticas.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="flex flex-col md:flex-row gap-3">
        <select
          v-model="filtroAta"
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @change="aplicarFiltros"
        >
          <option value="">Todas as atas</option>
          <option v-for="a in atas" :key="a.id" :value="a.id">{{ a.numero }}</option>
        </select>
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
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Lote</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ata</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Prestador</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Volume (m³)</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Valor total (R$)</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="l in lotes.data" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono">
              <Link :href="route('tdap.lotes.show', l.id)" class="text-blue-600 hover:text-blue-800">{{ l.numero }}</Link>
              <span v-if="l.nome" class="block text-xs text-slate-500">{{ l.nome }}</span>
            </td>
            <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300">{{ l.ata_numero }}</td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ l.municipio_nome }}<span v-if="l.municipio_uf" class="text-slate-400">/{{ l.municipio_uf }}</span></td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ l.prestador_nome }}</td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(l.qtd_agua_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(l.valor_total).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="l.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ l.ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.lotes.show', l.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.lotes.edit', l.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="lotes.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum lote cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="lotes.meta && lotes.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ lotes.meta.current_page }} de {{ lotes.meta.last_page }} ({{ lotes.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in lotes.meta.links || []"
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
import MapIcon from '@/Components/Icons/MapIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  lotes:        { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, volume_total_m3: 0, valor_total: 0 }) },
  atas:         { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroAta       = ref(props.filtros.ata_id ?? '');
const filtroPrestador = ref(props.filtros.prestador_id ?? '');
const filtroAtivo     = ref(props.filtros.ativo ?? '');

function aplicarFiltros() {
  router.get(route('tdap.lotes.index'), {
    ata_id:       filtroAta.value || undefined,
    prestador_id: filtroPrestador.value || undefined,
    ativo:        filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  }, { preserveState: true, replace: true });
}
</script>
