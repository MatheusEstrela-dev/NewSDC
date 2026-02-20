<template>
  <div class="beneficiarios-container">
    <!-- Header -->
    <div class="mb-4 md:mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div class="flex-1 min-w-0">
        <Heading :level="2" color="default" class="mb-1 md:mb-2 text-xl md:text-2xl">
          Beneficiários
        </Heading>
        <Text size="sm" color="muted" class="hidden sm:block">
          Gestão de beneficiários e famílias afetadas por desastres
        </Text>
      </div>

      <!-- Botão Novo Beneficiário -->
      <button
        @click="$emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md self-start md:self-auto"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="hidden sm:inline">Novo Beneficiário</span>
        <span class="sm:hidden">Novo</span>
      </button>
    </div>

    <!-- Statistics Cards -->
    <BeneficiarioStatsCards
      :statistics="statistics"
      @filter="handleStatFilter"
      class="mb-6"
    />

    <!-- Grid de Beneficiários -->
    <BeneficiarioGrid
      :beneficiarios="beneficiarios"
      :loading="loading"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="(id) => $emit('view', id)"
      @edit="(id) => $emit('edit', id)"
      @delete="(id) => $emit('delete', id)"
    />

    <!-- Desktop: Tabela (somente quando selecionada e não mobile) -->
    <div v-else-if="viewMode === 'table' && !isMobile" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Nome</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">CPF</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Contato</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider w-40 min-w-40">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="beneficiario in beneficiarios" :key="beneficiario.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900 dark:text-white">{{ beneficiario.nome }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.cpf || '—' }}</td>
            <td class="px-4 py-3">
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                beneficiario.status === 'ATIVO' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                beneficiario.status === 'EM_ABRIGO' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300'
              ]">
                {{ beneficiario.status?.replace('_', ' ') || 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.telefone || '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ beneficiario.municipio?.nome || beneficiario.municipio || '—' }}</td>
            <td class="px-4 py-3 text-right w-40 min-w-40">
              <div class="flex justify-end">
                <TableActions
                  :show-view="true"
                  :show-print="true"
                  :show-edit="canEdit"
                  :show-attachments="false"
                  :show-delete="canDelete"
                  size="sm"
                  @view="$emit('view', beneficiario.id)"
                  @print="handlePrint(beneficiario.id)"
                  @edit="$emit('edit', beneficiario.id)"
                  @delete="$emit('delete', beneficiario.id)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!beneficiarios || beneficiarios.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Nenhum beneficiário encontrado
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-6">
      <div class="flex items-center justify-between px-6 py-4 bg-slate-900/60 dark:bg-slate-900/60 bg-white rounded-lg border border-slate-700/30 dark:border-slate-700/30 border-slate-200">
        <p class="text-sm text-slate-400 dark:text-slate-400 text-slate-600">
          Mostrando {{ pagination.from || 0 }} a {{ pagination.to || 0 }} de {{ pagination.total || 0 }} resultados
        </p>
        <!-- TODO: Adicionar componente de paginação -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import BeneficiarioStatsCards from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioStatsCards.vue';
import BeneficiarioGrid from '@/Components/Organisms/AjudaHumanitaria/BeneficiarioGrid.vue';

const props = defineProps({
  beneficiarios: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    required: true,
  },
  pagination: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: true,
  },
  canDelete: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter']);

const handleStatFilter = (filter) => {
  emit('filter', filter);
};
</script>
