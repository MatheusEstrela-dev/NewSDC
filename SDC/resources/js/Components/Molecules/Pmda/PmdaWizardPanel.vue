<script setup>
import ArrowRightIcon from '@/Components/Icons/ArrowRightIcon.vue';

defineProps({
  step: { type: Number, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: [Object, Function], required: true },
  nextLabel: { type: String, default: 'Salvar e Avançar' },
  saving: { type: Boolean, default: false },
  hideNext: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);
</script>

<template>
  <div class="pmda-wizard-card">
    <header class="pmda-step-head">
      <div class="pmda-step-icon">
        <component :is="icon" />
      </div>
      <div>
        <h2 class="pmda-step-title">{{ title }}</h2>
        <p v-if="subtitle" class="pmda-step-sub">{{ subtitle }}</p>
      </div>
    </header>

    <div class="pmda-step-body">
      <slot />
    </div>

    <footer class="pmda-step-foot">
      <!-- Footer customizavel (ex.: etapa Anexos com Revisar/Enviar) -->
      <slot name="footer">
        <span></span>
        <button
          v-if="!hideNext"
          type="button"
          class="pmda-btn-next"
          :disabled="saving"
          @click="$emit('next')"
        >
          {{ nextLabel }}
          <ArrowRightIcon />
        </button>
      </slot>
    </footer>
  </div>
</template>
