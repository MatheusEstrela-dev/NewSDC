<template>
  <div class="space-y-4">
    <!-- Loading -->
    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-400 dark:border-primary-400 border-primary-600"></div>
      <p class="mt-4 text-slate-400 dark:text-slate-400 text-slate-600">Carregando processos...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="processos.length === 0" class="p-12 text-center">
      <DocumentIcon class="w-16 h-16 text-slate-600 dark:text-slate-600 text-slate-400 mx-auto mb-4" />
      <Heading :level="3" color="muted">Nenhum processo encontrado</Heading>
      <Text size="sm" color="muted" class="mt-2">
        Tente ajustar os filtros de busca ou crie um novo processo
      </Text>
    </div>

    <!-- Grid de Cards -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
      <ProcessoCard
        v-for="processo in processos"
        :key="processo.id"
        :processo="processo"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @click="handleView(processo.id)"
        @view="handleView"
        @edit="handleEdit"
      />
    </div>
  </div>
</template>

<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import DocumentIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ProcessoCard from '@/Components/Molecules/Decretacoes/ProcessoCard.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  processos: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const handleView = (id) => {
  router.visit(`/decretacoes/${id}`);
};

const handleEdit = (id) => {
  router.visit(`/decretacoes/${id}/edit`);
};
</script>
