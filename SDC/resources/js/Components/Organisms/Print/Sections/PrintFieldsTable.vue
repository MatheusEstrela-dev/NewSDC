<script setup>
defineProps({
  fields: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <table class="bos-table">
    <tr v-for="(row, rowIndex) in fields" :key="rowIndex">
      <template v-for="(field, fieldIndex) in row" :key="fieldIndex">
        <td
          class="field-label"
          :width="field.labelWidth || '20%'"
        >
          {{ field.label }}
        </td>
        <td
          class="field-value"
          :width="field.valueWidth || '30%'"
          :colspan="field.colspan || 1"
        >
          {{ field.value || '' }}
        </td>
      </template>
    </tr>
  </table>
</template>
