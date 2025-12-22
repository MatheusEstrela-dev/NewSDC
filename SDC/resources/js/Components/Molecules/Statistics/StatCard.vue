<template>
  <div :class="cardClasses">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <Text size="sm" color="muted" weight="medium" class="mb-1">
          {{ title }}
        </Text>
        <Heading :level="2" color="white" class="mb-0">
          {{ formattedValue }}
        </Heading>
        <Text v-if="subtitle" size="xs" color="muted" class="mt-1">
          {{ subtitle }}
        </Text>
      </div>

      <div v-if="icon" :class="iconContainerClasses">
        <component :is="icon" class="w-6 h-6" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import Text from '../../Atoms/Typography/Text.vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [Number, String],
    required: true,
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  variant: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'success', 'warning', 'danger'].includes(value),
  },
  subtitle: {
    type: String,
    default: '',
  },
  formatNumber: {
    type: Boolean,
    default: true,
  },
});

const variantAccentClasses = {
  info: 'bg-cyan-500/15 text-cyan-300 ring-1 ring-cyan-500/25',
  success: 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/25',
  warning: 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/25',
  danger: 'bg-red-500/15 text-red-300 ring-1 ring-red-500/25',
};

const variantBorderClasses = {
  info: 'border-cyan-500/25',
  success: 'border-emerald-500/25',
  warning: 'border-amber-500/25',
  danger: 'border-red-500/25',
};

const cardClasses = computed(() => {
  const base =
    'rounded-xl border bg-slate-900/60 backdrop-blur-sm px-5 py-4 transition-all duration-200';
  return [base, variantBorderClasses[props.variant]].filter(Boolean).join(' ');
});

const iconContainerClasses = computed(() => {
  return `p-3 rounded-lg ${variantAccentClasses[props.variant]}`;
});

const formattedValue = computed(() => {
  if (props.formatNumber && typeof props.value === 'number') {
    return props.value.toLocaleString('pt-BR');
  }
  return props.value;
});
</script>

