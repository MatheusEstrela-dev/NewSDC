<template>
  <TableRow :hover="hover" :clickable="clickable" :striped="striped" @click="$emit('click', $event)">
    <slot />
  </TableRow>
</template>

<script setup>
import TableRow from '../../Atoms/Table/TableRow.vue';

defineProps({
  hover: {
    type: Boolean,
    default: true,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
  striped: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['click']);
</script>

