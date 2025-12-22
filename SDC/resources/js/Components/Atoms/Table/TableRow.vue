<template>
  <tr :class="rowClasses" @click="handleClick">
    <slot />
  </tr>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  hover: {
    type: Boolean,
    default: true,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
  striped: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['click']);

function handleClick(event) {
  if (props.clickable) {
    emit('click', event);
  }
}

const rowClasses = computed(() => {
  const base = 'border-b border-slate-700/50 transition-colors duration-150';
  const hoverClass = props.hover ? 'hover:bg-slate-800/30' : '';
  const clickableClass = props.clickable ? 'cursor-pointer' : '';
  const stripedClass = props.striped ? 'bg-slate-900/20' : '';
  
  return [
    base,
    hoverClass,
    clickableClass,
    stripedClass,
  ].filter(Boolean).join(' ');
});
</script>

