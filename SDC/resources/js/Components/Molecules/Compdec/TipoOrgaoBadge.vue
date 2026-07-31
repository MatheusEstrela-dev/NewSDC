<template>
  <span :class="badgeClasses">
    <component :is="iconComponent" class="w-3 h-3 inline-block mr-1" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import BuildingIcon from '../../Icons/BuildingIcon.vue';
import BuildingOfficeIcon from '../../Icons/BuildingOfficeIcon.vue';

const props = defineProps({
  tipo: {
    type: String,
    required: true,
  },
});

const config = {
  cedec: {
    label: 'CEDEC',
    classes: 'bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-500/30',
    icon: BuildingOfficeIcon,
  },
  redec: {
    label: 'REDEC',
    classes: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/30',
    icon: BuildingOfficeIcon,
  },
  compdec: {
    label: 'COMPDEC',
    classes: 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-500/30',
    icon: BuildingIcon,
  },
};

const label = computed(() => config[props.tipo]?.label || (props.tipo || '').toUpperCase());
const iconComponent = computed(() => config[props.tipo]?.icon || BuildingIcon);
const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    config[props.tipo]?.classes || 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/20',
  ].join(' ');
});
</script>
