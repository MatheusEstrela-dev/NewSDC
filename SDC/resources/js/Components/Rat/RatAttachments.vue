<template>
  <div class="animate-fade-in-up">
    <RatAttachmentsSection
      :rat-id="ratId"
      :imagens="imagens"
    />
  </div>
</template>

<script setup>
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';

defineProps({
  ratId: {
    type: String,
    default: null,
  },
  imagens: {
    type: Array,
    default: () => [],
  },
});
</script>

