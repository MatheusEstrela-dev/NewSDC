<script setup>
defineProps({
  titulo: {
    type: String,
    required: true,
  },
  isSubsection: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div>
    <div :class="isSubsection ? 'subsection-title' : 'section-title'">
      {{ titulo }}
    </div>
    <slot />
  </div>
</template>
