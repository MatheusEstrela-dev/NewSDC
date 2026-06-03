<template>
  <div class="animate-fade-in-up">
    <RatAttachmentsSection
      :anexos="anexos"
      :upload-error="uploadError"
      @upload="$emit('upload', $event)"
      @remove="$emit('remove', $event)"
    />
  </div>
</template>

<script setup>
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';

defineProps({
  anexos: {
    type: Array,
    default: () => [],
  },
  uploadError: {
    type: String,
    default: null,
  },
});

defineEmits(['upload', 'remove']);
</script>
