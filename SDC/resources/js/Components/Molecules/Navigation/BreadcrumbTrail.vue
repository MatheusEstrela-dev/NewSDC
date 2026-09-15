<template>
  <!--
    `w-max` para o trilho ter a largura do proprio conteudo dentro do container
    que rola. Sem isso o `flex` se ajustaria a largura disponivel e os segmentos
    se comprimiriam em vez de rolar.
  -->
  <nav class="flex w-max items-center bg-white dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700/40 rounded-lg shadow-sm overflow-hidden">
    <NavSegment :isLast="items.length === 0">
      <Link :href="route('dashboard')" class="flex items-center">
        <HomeIcon class="w-3.5 h-3.5 text-slate-500 hover:text-blue-600 dark:text-slate-500 dark:hover:text-white cursor-pointer transition-colors" />
      </Link>
    </NavSegment>

    <NavSegment
      v-for="(item, index) in items"
      :key="index"
      :isLast="index === items.length - 1"
    >
      <div class="flex items-center gap-2">
        <Link 
          v-if="item.route && index !== items.length - 1" 
          :href="route(item.route, item.params)"
        >
          <NavLabel
            :text="item.label || item"
            :active="false"
          />
        </Link>
        <NavLabel
          v-else
          :text="item.label || item"
          :active="index === items.length - 1"
        />
      </div>
    </NavSegment>
  </nav>
</template>

<script setup>
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import NavSegment from '@/Components/Atoms/Navigation/NavSegment.vue';
import NavLabel from '@/Components/Atoms/Navigation/NavLabel.vue';

defineProps({
  items: {
    type: Array,
    default: () => []
  }
});
</script>
