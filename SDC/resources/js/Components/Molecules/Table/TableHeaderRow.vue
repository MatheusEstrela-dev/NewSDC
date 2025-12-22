<template>
  <thead class="bg-slate-900/50">
    <TableRow :hover="false" :clickable="false">
      <slot />
    </TableRow>
  </thead>
</template>

<script setup>
import TableRow from '../../Atoms/Table/TableRow.vue';
</script>

