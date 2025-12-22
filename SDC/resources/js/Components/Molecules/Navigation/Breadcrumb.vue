<template>
  <nav :class="navClasses" aria-label="Breadcrumb">
    <ol class="flex items-center">
      <li v-for="(item, index) in items" :key="index" class="flex items-center">
        <component
          :is="item.href ? 'Link' : 'span'"
          :href="item.href"
          :class="getItemClasses(item, index === items.length - 1)"
        >
          <component v-if="item.icon && index === 0" :is="item.icon" class="w-4 h-4 mr-1" />
          {{ item.label }}
        </component>
        <ChevronRightIcon
          v-if="index < items.length - 1"
          class="w-4 h-4 mx-2 text-slate-600"
        />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ChevronRightIcon from '../../Icons/ChevronRightIcon.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
});

function getItemClasses(item, isLast) {
  const base = 'flex items-center text-sm transition-colors';
  if (isLast) {
    return `${base} text-white font-medium`;
  }
  if (item.href) {
    return `${base} text-slate-400 hover:text-blue-400`;
  }
  return `${base} text-slate-400`;
}

const navClasses = computed(() => {
  return 'flex items-center';
});
</script>

