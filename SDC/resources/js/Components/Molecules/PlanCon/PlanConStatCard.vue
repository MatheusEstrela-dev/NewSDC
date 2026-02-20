<template>
  <div :class="cardClasses">
    <div class="flex items-start justify-between gap-2 sm:gap-4">
      <div class="min-w-0 flex-1">
        <Text size="sm" color="muted" weight="medium" class="mb-0.5 sm:mb-1 text-xs sm:text-sm leading-tight">
          {{ title }}
        </Text>
        <Heading :level="2" class="mb-0 text-xl sm:text-2xl md:text-3xl">
          {{ formattedValue }}
        </Heading>
        <Text v-if="percentage !== null" size="xs" color="muted" class="mt-0.5 sm:mt-1">
          {{ percentage }}% do total
        </Text>
      </div>

      <div v-if="icon" :class="iconContainerClasses">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
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
  percentage: {
    type: Number,
    default: null,
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  variant: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'success', 'warning', 'danger', 'primary'].includes(value),
  },
  formatNumber: {
    type: Boolean,
    default: true,
  },
});

const variantAccentClasses = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 text-cyan-300 dark:text-cyan-300 ring-1 ring-cyan-500/25',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 text-emerald-300 dark:text-emerald-300 ring-1 ring-emerald-500/25',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 text-amber-300 dark:text-amber-300 ring-1 ring-amber-500/25',
  danger: 'bg-red-500/15 dark:bg-red-500/15 text-red-300 dark:text-red-300 ring-1 ring-red-500/25',
  primary: 'bg-blue-500/15 dark:bg-blue-500/15 text-blue-300 dark:text-blue-300 ring-1 ring-blue-500/25',
};

const variantBorderClasses = {
  info: 'border-cyan-500/25 dark:border-cyan-500/25',
  success: 'border-emerald-500/25 dark:border-emerald-500/25',
  warning: 'border-amber-500/25 dark:border-amber-500/25',
  danger: 'border-red-500/25 dark:border-red-500/25',
  primary: 'border-blue-500/25 dark:border-blue-500/25',
};

const cardClasses = computed(() => {
  const base =
    'rounded-lg sm:rounded-xl border backdrop-blur-sm px-3 py-3 sm:px-4 sm:py-4 md:px-5 md:py-4 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] bg-slate-900/60 dark:bg-slate-900/60 hover:bg-slate-900/80 dark:hover:bg-slate-900/80';
  return [base, variantBorderClasses[props.variant]].filter(Boolean).join(' ');
});

const iconContainerClasses = computed(() => {
  return `p-1.5 sm:p-2 md:p-3 rounded-md sm:rounded-lg ${variantAccentClasses[props.variant]}`;
});

const formattedValue = computed(() => {
  if (props.formatNumber && typeof props.value === 'number') {
    return props.value.toLocaleString('pt-BR');
  }
  return props.value;
});
</script>
