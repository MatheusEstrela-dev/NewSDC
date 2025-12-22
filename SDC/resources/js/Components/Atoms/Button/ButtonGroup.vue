<template>
  <div :class="groupClasses">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  orientation: {
    type: String,
    default: 'horizontal',
    validator: (value) => ['horizontal', 'vertical'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const groupClasses = computed(() => {
  const base = 'inline-flex';
  const orientationClass = props.orientation === 'horizontal' ? 'flex-row' : 'flex-col';
  const gapClass = {
    sm: 'gap-1',
    md: 'gap-2',
    lg: 'gap-3',
  }[props.size];
  
  return `${base} ${orientationClass} ${gapClass}`;
});
</script>

