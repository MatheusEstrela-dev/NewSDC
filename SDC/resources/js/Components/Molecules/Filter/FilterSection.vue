<template>
  <CardBase variant="default" padding="lg">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-700/30 text-left"
      @click="isCollapsed = !isCollapsed"
      :aria-expanded="(!isCollapsed).toString()"
    >
      <div class="flex items-center gap-2">
        <FunnelIcon class="w-5 h-5 text-slate-400" />
        <Heading level="5" color="default">
          {{ title }}
        </Heading>
      </div>
      <ChevronDownIcon
        class="w-5 h-5 text-slate-400 transition-transform duration-200"
        :class="isCollapsed ? '-rotate-90' : 'rotate-0'"
      />
    </button>

    <div v-show="!isCollapsed" :class="gridClasses">
      <slot />
    </div>
  </CardBase>
</template>

<script setup>
import { computed, ref } from 'vue';
import CardBase from '../../Atoms/Card/CardBase.vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import FunnelIcon from '../../Icons/FunnelIcon.vue';
import ChevronDownIcon from '../../Icons/ChevronDownIcon.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Filtros de Pesquisa',
  },
  columns: {
    type: Number,
    default: 4,
    validator: (value) => [2, 3, 4, 5, 6].includes(value),
  },
  defaultCollapsed: {
    type: Boolean,
    default: false,
  },
});

const isCollapsed = ref(props.defaultCollapsed);

const gridClasses = computed(() => {
  const gridCols = {
    2: 'grid grid-cols-1 md:grid-cols-2 gap-4',
    3: 'grid grid-cols-1 md:grid-cols-3 gap-4',
    4: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4',
    5: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4',
    6: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4',
  };
  
  return gridCols[props.columns] || gridCols[4];
});
</script>

