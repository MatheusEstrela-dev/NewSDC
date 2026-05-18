<template>
  <thead class="bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
    <TableRow :hover="false" :clickable="false">
      <slot />
    </TableRow>
  </thead>
</template>

<script setup>
import TableRow from '../../Atoms/Table/TableRow.vue';
</script>
