<template>
  <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <nav
      class="text-sm text-slate-400 flex items-center space-x-2 w-full sm:w-auto overflow-x-auto whitespace-nowrap pb-2 sm:pb-0"
    >
      <Link :href="route('dashboard')" class="hover:text-blue-400 transition-colors">Início</Link>
      <ChevronRightIcon class="w-3.5 h-3.5" />
      <Link href="#" class="hover:text-blue-400 transition-colors">Empreendimentos</Link>
      <ChevronRightIcon class="w-3.5 h-3.5" />
      <span class="text-slate-200 font-medium px-2 py-0.5 bg-slate-800 rounded-md border border-slate-700">
        Gestão de PAE
      </span>
    </nav>

    <Link
      :href="route('dashboard')"
      class="flex items-center bg-slate-700 hover:bg-slate-600 text-white font-medium py-2 px-4 rounded-lg shadow-md border border-slate-600 hover:border-slate-500 transition-all duration-200 w-full sm:w-auto justify-center"
    >
      <ArrowLeftIcon class="w-4 h-4 mr-2" />
      Voltar
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ChevronRightIcon from '../Icons/ChevronRightIcon.vue';
import ArrowLeftIcon from '../Icons/ArrowLeftIcon.vue';
</script>

