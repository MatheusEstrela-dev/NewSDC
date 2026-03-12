<template>
  <div v-if="!viewOnly" class="rat-actions-footer">
    <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 pt-3 pb-6 sm:px-6 sm:pt-4 sm:pb-6">
      <Button
        variant="warning"
        size="sm"
        :loading="loading"
        @click="$emit('save-draft')"
      >
        Salvar
      </Button>
      <Button
        variant="primary"
        size="sm"
        :icon="CheckCircleIcon"
        :loading="loading"
        @click="$emit('finalize')"
      >
        <span class="hidden sm:inline">Finalizar RAT</span>
        <span class="sm:hidden">Finalizar</span>
      </Button>
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

defineProps({
  viewOnly: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save-draft', 'finalize']);
</script>
