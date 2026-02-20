<template>
  <Link
    :href="href"
    :class="cardClasses"
  >
    <div class="flex items-center gap-4">
      <div :class="iconContainerClasses">
        <component :is="icon" class="w-6 h-6" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-slate-200 dark:text-slate-200">
          <span v-html="formattedTitle"></span>
        </p>
      </div>
      <ChevronRightIcon class="w-5 h-5 text-slate-400 dark:text-slate-400 transition-transform group-hover:translate-x-1" />
    </div>
  </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  href: {
    type: String,
    required: true,
  },
  icon: {
    type: [Object, Function],
    default: () => DocumentTextIcon,
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'success', 'warning'].includes(value),
  },
  highlightWord: {
    type: String,
    default: '',
  },
});

const variantStyles = {
  default: {
    icon: 'bg-slate-700/50 dark:bg-slate-700/50 text-slate-300 dark:text-slate-300',
    border: 'border-slate-700/50 dark:border-slate-700/50 hover:border-slate-600 dark:hover:border-slate-600',
    highlight: 'text-amber-400',
  },
  success: {
    icon: 'bg-emerald-500/15 dark:bg-emerald-500/15 text-emerald-400 dark:text-emerald-400',
    border: 'border-emerald-500/25 dark:border-emerald-500/25 hover:border-emerald-500/50 dark:hover:border-emerald-500/50',
    highlight: 'text-emerald-400',
  },
  warning: {
    icon: 'bg-amber-500/15 dark:bg-amber-500/15 text-amber-400 dark:text-amber-400',
    border: 'border-amber-500/25 dark:border-amber-500/25 hover:border-amber-500/50 dark:hover:border-amber-500/50',
    highlight: 'text-amber-400',
  },
};

const cardClasses = computed(() => {
  const base = 'group flex items-center rounded-xl border backdrop-blur-sm px-4 py-4 transition-all duration-300 hover:shadow-lg hover:scale-[1.01] bg-slate-800/60 dark:bg-slate-800/60 hover:bg-slate-800/80 dark:hover:bg-slate-800/80 cursor-pointer';
  return [base, variantStyles[props.variant].border].join(' ');
});

const iconContainerClasses = computed(() => {
  return `p-2.5 rounded-lg ${variantStyles[props.variant].icon}`;
});

const formattedTitle = computed(() => {
  if (!props.highlightWord) {
    return props.title;
  }

  const regex = new RegExp(`(${props.highlightWord})`, 'gi');
  return props.title.replace(regex, `<span class="${variantStyles[props.variant].highlight} font-semibold">$1</span>`);
});
</script>
