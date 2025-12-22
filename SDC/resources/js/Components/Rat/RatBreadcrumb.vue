<template>
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center text-sm">
      <Link 
        :href="route('dashboard')" 
        class="text-slate-400 hover:text-blue-400 transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Início
      </Link>
      <ChevronRightIcon class="w-4 h-4 mx-2 text-slate-600" />
      <Link 
        :href="route('rat.index')" 
        class="text-slate-400 hover:text-blue-400 transition-colors"
      >
        RAT
      </Link>
      <ChevronRightIcon class="w-4 h-4 mx-2 text-slate-600" />
      <span class="text-white font-medium">{{ isEdit ? 'Editar RAT' : 'Novo RAT' }}</span>
    </nav>

    <!-- Back Button -->
    <Link
      :href="route('rat.index')"
      class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-lg border border-slate-700 hover:border-slate-600 transition-all duration-200"
    >
      <ArrowLeftIcon class="w-4 h-4" />
      Voltar
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ChevronRightIcon from '../Icons/ChevronRightIcon.vue';
import ArrowLeftIcon from '../Icons/ArrowLeftIcon.vue';

const page = usePage();
const isEdit = computed(() => {
  return page.props.rat?.id !== null && page.props.rat?.id !== undefined;
});
</script>

