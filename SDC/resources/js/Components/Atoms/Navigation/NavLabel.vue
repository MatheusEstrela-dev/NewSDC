<template>
  <span
    :class="[
      'text-[13px] tracking-tight transition-all whitespace-nowrap',
      active
        ? 'text-white font-bold'
        : 'text-slate-400 hover:text-slate-200 cursor-pointer'
    ]"
  >
    {{ text }}
  </span>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    required: true
  },
  active: {
    type: Boolean,
    default: false
  }
});
</script>
