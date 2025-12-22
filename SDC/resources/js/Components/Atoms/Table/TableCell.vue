<template>
  <td v-bind="attrs" :class="[cellClasses, attrs.class]">
    <slot />
  </td>
</template>

<script setup>
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();

const props = defineProps({
  align: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'center', 'right'].includes(value),
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const alignClasses = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
};

const paddingClasses = {
  sm: 'px-2 py-1.5',
  md: 'px-4 py-3',
  lg: 'px-6 py-4',
};

const cellClasses = computed(() => {
  return [
    alignClasses[props.align],
    paddingClasses[props.padding],
    'text-slate-300',
  ].join(' ');
});
</script>

