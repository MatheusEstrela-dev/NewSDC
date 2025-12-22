<template>
  <label :for="forId" :class="labelClasses">
    <slot />
    <span v-if="required" class="text-red-400 ml-1">*</span>
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  forId: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  color: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'muted'].includes(value),
  },
});

const sizeClasses = {
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-base',
};

const colorClasses = {
  default: 'text-slate-300',
  muted: 'text-slate-500',
};

const weightClasses = 'font-medium';

const labelClasses = computed(() => {
  return [
    sizeClasses[props.size],
    colorClasses[props.color],
    weightClasses,
    'block',
  ].join(' ');
});
</script>

