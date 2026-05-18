<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { ArrowLeftIcon, NewspaperIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

defineProps({
  noticias: {
    type: Array,
    default: () => [],
  },
  fontes: {
    type: Array,
    default: () => [],
  },
});

const handleVoltar = () => {
  router.visit(route('plantao.index'));
};
</script>

<template>
  <div class="noticias-container">
    <PageHeader
      title="Buscar Noticias"
      description="Web scraping de portais de noticias"
      :icon="NewspaperIcon"
      variant="gradient"
      icon-class="text-slate-600 dark:text-slate-300"
    >
      <template #actions>
        <Button
          variant="secondary"
          size="md"
          :icon="ArrowLeftIcon"
          icon-position="left"
          @click="handleVoltar"
        >
          <span class="hidden sm:inline">Voltar ao Plantao</span>
          <span class="sm:hidden">Voltar</span>
        </Button>
      </template>
    </PageHeader>

    <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-800">
      <NewspaperIcon class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" />
      <h3 class="mt-4 text-lg font-semibold text-slate-700 dark:text-slate-200">
        Modulo em construcao
      </h3>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        Em breve voce podera buscar noticias automaticamente em portais externos.
      </p>
    </div>
  </div>
</template>
