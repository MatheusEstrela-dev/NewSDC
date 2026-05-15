<template>
  <Head title="TDAP — Cronogramas" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Cronogramas de Fornecimento"
      description="Ordens operacionais de entrega de água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.cronogramas.create')">
          <PrimaryButton>Novo Cronograma</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Ativos</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ estatisticas.ativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Rascunhos</p>
        <p class="text-2xl font-semibold text-amber-600">{{ estatisticas.rascunhos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Encerrados</p>
        <p class="text-2xl font-semibold text-slate-400">{{ estatisticas.encerrados }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Volume ativo (m³)</p>
        <p class="text-2xl font-semibold text-blue-600">{{ Number(estatisticas.volume_ativo_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:0,maximumFractionDigits:0}) }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <input
          v-model="filtroSearch"
          type="text"
          placeholder="Buscar numero/empenho..."
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm md:col-span-2"
          @keyup.enter="aplicarFiltros"
        />
        <select v-model="filtroEstado" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Estado</option>
          <option value="rascunho">Rascunho</option>
          <option value="ativo">Ativo</option>
          <option value="encerrado">Encerrado</option>
        </select>
        <select v-model="filtroAta" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Ata</option>
          <option v-for="a in atas" :key="a.id" :value="a.id">{{ a.numero }}</option>
        </select>
        <PrimaryButton @click="aplicarFiltros">Filtrar</PrimaryButton>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Vigência</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ata / Lote</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município / Prestador</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Volume (m³)</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Caminhões</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="c in cronogramas.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono">
              <Link :href="route('tdap.cronogramas.show', c.id)" class="text-blue-600 hover:text-blue-800 font-semibold">{{ c.numero }}</Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}</td>
            <td class="px-4 py-3 text-sm">
              <p class="font-mono">{{ c.ata_numero }}</p>
              <p class="text-xs text-slate-500 font-mono">{{ c.lote_numero }}</p>
            </td>
            <td class="px-4 py-3 text-sm">
              <p>{{ c.municipio_nome }}<span v-if="c.municipio_uf" class="text-slate-400">/{{ c.municipio_uf }}</span></p>
              <p class="text-xs text-slate-500">{{ c.prestador_nome }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(c.volume_contratado_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm text-center">{{ c.caminhoes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <EstadoBadge :estado="c.estado" />
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.cronogramas.show', c.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit && c.estado === 'rascunho'" :href="route('tdap.cronogramas.edit', c.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="cronogramas.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum cronograma cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="cronogramas.meta && cronogramas.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ cronogramas.meta.current_page }} de {{ cronogramas.meta.last_page }} ({{ cronogramas.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in cronogramas.meta.links || []"
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
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cronogramas:  { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, rascunhos: 0, encerrados: 0, volume_ativo_m3: 0 }) },
  atas:         { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canAtivar:    { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroEstado = ref(props.filtros.estado ?? '');
const filtroAta    = ref(props.filtros.ata_id ?? '');

function aplicarFiltros() {
  router.get(route('tdap.cronogramas.index'), {
    search: filtroSearch.value || undefined,
    estado: filtroEstado.value || undefined,
    ata_id: filtroAta.value || undefined,
  }, { preserveState: true, replace: true });
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
