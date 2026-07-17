<template>
  <ListContainer
    :title="title"
    :subtitle="subtitle"
    :count="total"
    :icon="BuildingOffice2Icon"
    icon-class="text-blue-500"
  >
    <table class="w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50">
        <tr>
          <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Município</th>
          <th class="hidden px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 sm:table-cell">Código IBGE</th>
          <th v-if="showSituacao" class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Situação</th>
          <th v-if="showDataAtualizacao" class="hidden px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 md:table-cell">Atualização</th>
          <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
        <tr v-for="municipio in municipios" :key="municipio.id" class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/30">
          <td class="px-4 py-3">
            <p class="font-medium text-slate-800 dark:text-slate-100">{{ municipio.nome }}</p>
            <p class="mt-0.5 text-xs text-slate-500 sm:hidden">IBGE {{ municipio.codigoIbge || '—' }}</p>
          </td>
          <td class="hidden px-4 py-3 font-mono text-sm text-slate-600 dark:text-slate-300 sm:table-cell">{{ municipio.codigoIbge || '—' }}</td>
          <td v-if="showSituacao" class="px-4 py-3"><PlanConStatusBadge :situacao="municipio.situacaoPlano" /></td>
          <td v-if="showDataAtualizacao" class="hidden px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ formatDate(municipio.dataUltimaAtualizacao) }}</td>
          <td class="px-4 py-3" @click.stop>
            <div class="flex justify-end">
              <ActionButton module="plancon" resource="municipios" size="sm" :actions="[{ action: 'view', handler: () => emit('view', municipio) }]" />
            </div>
          </td>
        </tr>
        <tr v-if="municipios.length === 0">
          <td :colspan="columnCount" class="p-0">
            <ListEmptyState :icon="BuildingOffice2Icon" :title="emptyTitle" helper="Não há registros para os filtros informados." />
          </td>
        </tr>
      </tbody>
    </table>
  </ListContainer>
</template>

<script setup>
import { computed } from 'vue';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PlanConStatusBadge from '@/Components/Molecules/PlanCon/PlanConStatusBadge.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';

const props = defineProps({
  municipios: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  title: { type: String, default: 'Municípios' },
  subtitle: { type: String, default: '' },
  emptyTitle: { type: String, default: 'Nenhum município encontrado' },
  showSituacao: { type: Boolean, default: false },
  showDataAtualizacao: { type: Boolean, default: false },
});
const emit = defineEmits(['view']);
const columnCount = computed(() => 3 + Number(props.showSituacao) + Number(props.showDataAtualizacao));
function formatDate(value) { return value ? new Date(value).toLocaleDateString('pt-BR') : '—'; }
</script>