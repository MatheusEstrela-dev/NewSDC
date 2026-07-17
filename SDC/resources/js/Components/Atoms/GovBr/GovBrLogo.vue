<template>
  <span
    class="inline-flex items-baseline whitespace-nowrap font-black leading-none tracking-normal"
    :class="sizeClass"
    aria-label="gov.br"
  >
    <span :class="blueClass">g</span><span class="text-[#ffcd07]">o</span><span :class="blueClass">v</span><span :class="dotClass">.</span><span class="text-[#168821]">b</span><span class="text-[#ffcd07]">r</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md', 'lg'].includes(value),
  },
  inverted: {
    type: Boolean,
    default: false,
  },
});

const sizeClass = computed(() => ({
  sm: 'text-[1.35rem]',
  md: 'text-[1.75rem]',
  lg: 'text-[2.25rem]',
}[props.size]));

const dotClass = computed(() => props.inverted ? 'text-white' : 'text-[#1351b4]');
const blueClass = computed(() => props.inverted ? 'text-white' : 'text-[#1351b4]');
</script>
