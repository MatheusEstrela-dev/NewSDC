<template>
  <nav class="flex items-center bg-slate-800/30 border border-slate-700/40 rounded-lg shadow-sm overflow-hidden">
    <NavSegment :isLast="items.length === 0">
      <HomeIcon class="w-3.5 h-3.5 text-slate-500 hover:text-white cursor-pointer transition-colors" />
    </NavSegment>

    <NavSegment
      v-for="(item, index) in items"
      :key="index"
      :isLast="index === items.length - 1"
    >
      <div class="flex items-center gap-2">
        <NavLabel
          :text="item"
          :active="index === items.length - 1"
        />
      </div>
    </NavSegment>
  </nav>
</template>

<script setup>
import { HomeIcon } from '@heroicons/vue/24/outline';
import NavSegment from '@/Components/Atoms/Navigation/NavSegment.vue';
import NavLabel from '@/Components/Atoms/Navigation/NavLabel.vue';

defineProps({
  items: {
    type: Array,
    default: () => []
  }
});
</script>
