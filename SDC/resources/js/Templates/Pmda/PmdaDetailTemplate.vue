<template>
  <div class="p-4 sm:p-6 space-y-6">
    <header class="flex items-center justify-between">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h1>
      <slot name="actions" />
    </header>
    <slot name="iss" />
    <slot name="municipio" />
    <slot name="pontos" />
    <slot name="comunidades" />
    <slot name="representantes" />
    <slot name="acoes" />
    <slot name="anexos" />
  </div>
</template>

<script setup>
defineProps({ title: { type: String, required: true } });
</script>
