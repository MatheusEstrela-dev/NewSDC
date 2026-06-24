<template>
  <div class="p-4 sm:p-6">
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ title }}</h1>
      <slot name="actions" />
    </header>
    <slot name="filters" />
    <slot />
    <footer class="mt-4"><slot name="pagination" /></footer>
  </div>
</template>

<script setup>
defineProps({ title: { type: String, required: true } });
</script>
