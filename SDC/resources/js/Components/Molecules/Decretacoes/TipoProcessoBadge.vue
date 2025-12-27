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
    validator: (value) => ['MUNICIPAL', 'ESTADUAL'].includes(value),
  },
});

const config = {
  MUNICIPAL: {
    label: 'Municipal',
    classes: 'bg-blue-500/20 text-blue-300 border border-blue-500/20',
    icon: BuildingIcon,
  },
  ESTADUAL: {
    label: 'Estadual',
    classes: 'bg-purple-500/20 text-purple-300 border border-purple-500/20',
    icon: BuildingOfficeIcon,
  },
};

const label = computed(() => config[props.tipo]?.label || props.tipo);
const iconComponent = computed(() => config[props.tipo]?.icon || BuildingIcon);
const badgeClasses = computed(() => {
  return [
    'px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold inline-block whitespace-nowrap',
    config[props.tipo]?.classes || 'bg-slate-500/20 text-slate-300 border border-slate-500/20',
  ].join(' ');
});
</script>
