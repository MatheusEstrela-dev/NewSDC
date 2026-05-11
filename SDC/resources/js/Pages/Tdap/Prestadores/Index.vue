<template>
  <Head title="TDAP — Prestadores" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Prestadores TDAP"
      description="Empresas contratadas para transporte de água potável"
      :icon="BuildingIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.prestadores.create')">
          <PrimaryButton>Novo Prestador</PrimaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="flex flex-col md:flex-row gap-3">
        <input
          v-model="filtroSearch"
          type="text"
          placeholder="Buscar por nome, CNPJ ou e-mail..."
          class="flex-1 border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @keyup.enter="aplicarFiltros"
        />
        <select
          v-model="filtroAtivo"
          class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          @change="aplicarFiltros"
        >
          <option value="">Todos os status</option>
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
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">CNPJ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Razão Social</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Cidade/UF</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Caminhões</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="p in prestadores.data" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-slate-100">{{ p.cnpj_formatado }}</td>
            <td class="px-4 py-3 text-sm">
              <Link :href="route('tdap.prestadores.show', p.id)" class="text-blue-600 hover:text-blue-800 font-medium">{{ p.nome }}</Link>
              <p class="text-xs text-slate-500">{{ p.email }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              <span v-if="p.cidade || p.uf">{{ p.cidade }}<span v-if="p.uf">/{{ p.uf }}</span></span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ p.caminhoes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="p.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ p.ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.prestadores.show', p.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.prestadores.edit', p.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="prestadores.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhum prestador cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="prestadores.meta && prestadores.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ prestadores.meta.current_page }} de {{ prestadores.meta.last_page }} ({{ prestadores.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in prestadores.meta.links || []"
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
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestadores:  { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, inativos: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroAtivo  = ref(props.filtros.ativo ?? '');

function aplicarFiltros() {
  router.get(route('tdap.prestadores.index'), {
    search: filtroSearch.value || undefined,
    ativo:  filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  }, { preserveState: true, replace: true });
}
</script>
