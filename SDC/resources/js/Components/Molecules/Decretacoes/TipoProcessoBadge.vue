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
    // Validator removed to allow other types (fallback logic exists)
  },
});

const config = {
  MUNICIPAL: {
    label: 'Municipal',
    classes: 'bg-blue-100 text-blue-700 border border-blue-300 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30',
    icon: BuildingIcon,
  },
  ESTADUAL: {
    label: 'Estadual',
    classes: 'bg-purple-100 text-purple-700 border border-purple-300 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30',
    icon: BuildingOfficeIcon,
  },
};

const label = computed(() => config[props.tipo]?.label || props.tipo);
const iconComponent = computed(() => config[props.tipo]?.icon || BuildingIcon);
const badgeClasses = computed(() => {
  return [
    'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap',
    config[props.tipo]?.classes || 'bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:border-slate-500/30',
  ].join(' ');
});
</script>
