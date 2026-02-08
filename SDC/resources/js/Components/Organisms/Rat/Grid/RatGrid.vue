<template>
  <div>
    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
      <p class="mt-4 text-slate-600 dark:text-slate-400">Carregando...</p>
    </div>

    <div v-else-if="rats.length === 0" class="p-12 text-center">
      <DocumentTextIcon class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-4" />
      <h4 class="text-lg font-semibold text-slate-600 dark:text-slate-400">Nenhum RAT encontrado</h4>
      <p class="text-sm text-slate-500 dark:text-slate-500 mt-2">Tente ajustar os filtros de busca</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <RatCard
        v-for="rat in rats"
        :key="rat.id"
        :rat="rat"
        @view="$emit('view', $event)"
        @print="$emit('print', $event)"
        @edit="$emit('edit', $event)"
        @attachments="$emit('attachments', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>

  </div>
</template>

<script setup>
import RatCard from '@/Components/Molecules/Rat/RatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';

defineProps({
  rats: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    default: null,
  },
});

defineEmits(['view', 'print', 'edit', 'attachments', 'delete']);
</script>
