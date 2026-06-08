<template>
  <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
    <TableRow :hover="false" :clickable="false">
      <slot />
    </TableRow>
  </thead>
</template>

<script setup>
import TableRow from '../../Atoms/Table/TableRow.vue';
</script>

