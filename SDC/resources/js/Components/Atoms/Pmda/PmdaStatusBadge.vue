<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="colorClass"
  >
    {{ label }}
  </span>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  colorClass: { type: String, default: 'bg-slate-100 text-slate-800' },
});
</script>
