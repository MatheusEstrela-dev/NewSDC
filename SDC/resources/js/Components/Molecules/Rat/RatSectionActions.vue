<template>
  <div v-if="!viewOnly" class="rat-actions-footer">
    <div class="flex items-center justify-center gap-3 flex-wrap">
      <!-- Botão Salvar -->
      <Button
        variant="default"
        size="sm"
        :loading="loading"
        @click="$emit('save')"
        class="px-4 sm:px-6"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Salvar
      </Button>

      <!-- Botão Finalizar RAT (apenas se permitido) -->
      <Button
        v-if="showFinalize"
        variant="primary"
        size="sm"
        :icon="CheckCircleIcon"
        :loading="loading"
        @click="$emit('finalize')"
        class="px-4 sm:px-6"
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
  showFinalize: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['save', 'finalize']);
</script>

