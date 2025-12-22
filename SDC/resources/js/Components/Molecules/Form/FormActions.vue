<template>
  <div :class="actionsClasses">
    <slot>
      <Button
        v-if="showCancel"
        variant="secondary"
        @click="$emit('cancel')"
      >
        {{ cancelLabel }}
      </Button>
      <Button
        v-if="showSubmit"
        :variant="submitVariant"
        :loading="loading"
        :disabled="disabled"
        @click="$emit('submit')"
      >
        {{ submitLabel }}
      </Button>
    </slot>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from '../../Atoms/Button/Button.vue';

const props = defineProps({
  showCancel: {
    type: Boolean,
    default: true,
  },
  showSubmit: {
    type: Boolean,
    default: true,
  },
  cancelLabel: {
    type: String,
    default: 'Cancelar',
  },
  submitLabel: {
    type: String,
    default: 'Salvar',
  },
  submitVariant: {
    type: String,
    default: 'primary',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  align: {
    type: String,
    default: 'right',
    validator: (value) => ['left', 'center', 'right'].includes(value),
  },
});

defineEmits(['cancel', 'submit']);

const actionsClasses = computed(() => {
  const base = 'flex gap-3';
  const alignClasses = {
    left: 'justify-start',
    center: 'justify-center',
    right: 'justify-end',
  };
  
  return `${base} ${alignClasses[props.align]}`;
});
</script>

