<template>
  <ListContainer
    :title="title"
    :icon="CubeIcon"
    :subtitle="subtitle"
    :count="total ?? cisternas.length"
  >
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Código</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Nome</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden md:table-cell">Município</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden sm:table-cell">Tipo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Status</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden lg:table-cell">Capacidade</th>
            <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right whitespace-nowrap w-32 min-w-32">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr
            v-for="cisterna in cisternas"
            :key="cisterna.id"
            class="table-row-solid transition-colors group"
          >
            <td class="px-4 py-3">
              <div class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-sm whitespace-nowrap">
                {{ cisterna.codigo || '-' }}
              </div>
              <div class="text-xs text-slate-500 md:hidden truncate max-w-[120px]">
                {{ municipioLabel(cisterna) }}
              </div>
            </td>

            <td class="px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm max-w-[160px] sm:max-w-[220px] truncate">
                {{ cisterna.nome || '-' }}
              </div>
              <div class="text-xs text-slate-500 sm:hidden mt-1">
                <TipoCisternaBadge :tipo="cisterna.tipo" size="sm" />
              </div>
            </td>

            <td class="px-4 py-3 hidden md:table-cell">
              <div class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm max-w-[180px] truncate">
                {{ municipioLabel(cisterna) }}
              </div>
            </td>

            <td class="px-4 py-3 hidden sm:table-cell">
              <TipoCisternaBadge :tipo="cisterna.tipo" />
            </td>

            <td class="px-4 py-3">
              <StatusCisternaBadge :status="cisterna.status" />
            </td>

            <td class="px-4 py-3 hidden lg:table-cell text-slate-600 dark:text-slate-300 text-xs sm:text-sm">
              {{ capacidadeLabel(cisterna.capacidade_litros) }}
            </td>

            <td class="table-actions-cell px-4 py-3 w-32 min-w-32" @click.stop>
              <div class="flex items-center justify-end">
                <ActionButton
                  module="cisternas"
                  resource=""
                  size="sm"
                  :actions="[
                    { action: 'view',   handler: () => $emit('show', cisterna) },
                    { action: 'edit',   handler: () => $emit('edit', cisterna),   allowed: canEdit },
                    { action: 'delete', handler: () => $emit('delete', cisterna), allowed: canDelete },
                  ]"
                />
              </div>
            </td>
          </tr>

          <tr v-if="cisternas.length === 0">
            <td colspan="7" class="p-0">
              <ListEmptyState
                :icon="CubeIcon"
                title="Nenhuma cisterna cadastrada"
              />
            </td>
          </tr>
        </tbody>
      </table>
  </ListContainer>
</template>

<script setup>
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import StatusCisternaBadge from '@/Components/Molecules/Cisterna/StatusCisternaBadge.vue';
import TipoCisternaBadge from '@/Components/Molecules/Cisterna/TipoCisternaBadge.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

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
