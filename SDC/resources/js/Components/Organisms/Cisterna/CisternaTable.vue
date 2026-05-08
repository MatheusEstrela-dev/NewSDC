<template>
  <div class="bg-white dark:bg-slate-800/50 rounded-lg sm:rounded-xl shadow-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden">
    <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-slate-200 dark:border-slate-700/50 flex justify-between items-center bg-slate-50 dark:bg-slate-800/70">
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base truncate">
          <CubeIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary-400 flex-shrink-0" />
          <span class="truncate">{{ title }}</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5 hidden sm:block">{{ subtitle }}</p>
      </div>
      <span class="bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 text-xs font-bold px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-blue-200 dark:border-blue-500/30 flex-shrink-0 ml-2">
        {{ total ?? cisternas.length }}
      </span>
    </div>

    <div class="overflow-x-auto -mx-px">
      <table class="w-full text-sm">
        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Codigo</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Nome</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden md:table-cell">Municipio</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden sm:table-cell">Tipo</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Status</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden lg:table-cell">Capacidade</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right whitespace-nowrap w-32 min-w-32">Acoes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
          <tr
            v-for="cisterna in cisternas"
            :key="cisterna.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors group"
          >
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <div class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-sm whitespace-nowrap">
                {{ cisterna.codigo || '-' }}
              </div>
              <div class="text-xs text-slate-500 md:hidden truncate max-w-[120px]">
                {{ municipioLabel(cisterna) }}
              </div>
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <div class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm max-w-[160px] sm:max-w-[220px] truncate">
                {{ cisterna.nome || '-' }}
              </div>
              <div class="text-xs text-slate-500 sm:hidden mt-1">
                <TipoCisternaBadge :tipo="cisterna.tipo" size="sm" />
              </div>
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden md:table-cell">
              <div class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm max-w-[180px] truncate">
                {{ municipioLabel(cisterna) }}
              </div>
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden sm:table-cell">
              <TipoCisternaBadge :tipo="cisterna.tipo" />
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <StatusCisternaBadge :status="cisterna.status" />
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden lg:table-cell text-slate-600 dark:text-slate-300 text-xs sm:text-sm">
              {{ capacidadeLabel(cisterna.capacidade_litros) }}
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 w-32 min-w-32" @click.stop>
              <div class="flex items-center justify-end">
                <TableActions
                  :show-view="true"
                  :show-print="false"
                  :show-edit="canEdit"
                  :show-attachments="false"
                  :show-delete="canDelete"
                  :show-options="false"
                  size="sm"
                  @view="$emit('show', cisterna)"
                  @edit="$emit('edit', cisterna)"
                  @delete="$emit('delete', cisterna)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="cisternas.length === 0">
            <td colspan="7" class="px-6 py-12 text-center">
              <CubeIcon class="w-12 h-12 text-slate-600 mx-auto mb-3" />
              <p class="text-slate-400 font-medium">Nenhuma cisterna cadastrada</p>
              <p class="text-slate-500 text-sm mt-1">Tente ajustar os filtros de busca</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import StatusCisternaBadge from '@/Components/Molecules/Cisterna/StatusCisternaBadge.vue';
import TipoCisternaBadge from '@/Components/Molecules/Cisterna/TipoCisternaBadge.vue';

defineProps({
  title: { type: String, default: 'Cisternas cadastradas' },
  subtitle: { type: String, default: 'Cadastro e acompanhamento' },
  cisternas: { type: Array, default: () => [] },
  total: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

defineEmits(['show', 'edit', 'delete']);

function formatLitros(value) {
  return Number(value).toLocaleString('pt-BR');
}

function capacidadeLabel(value) {
  if (!value) return '-';
  return `${formatLitros(value)} L`;
}

function municipioLabel(cisterna) {
  const municipio = cisterna?.municipio;
  if (!municipio) return '-';
  return `${municipio.nome ?? '-'}${municipio.uf ? ' - ' + municipio.uf : ''}`;
}
</script>
