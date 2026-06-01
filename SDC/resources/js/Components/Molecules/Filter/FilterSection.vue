<template>
  <CardBase variant="default" padding="sm" class="overflow-hidden mb-2">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 mb-2 pb-2 text-left group transition-colors duration-300
             border-b border-slate-200 dark:border-slate-700/30
             hover:border-primary-300 dark:hover:border-primary-500/30"
      @click="isCollapsed = !isCollapsed"
      :aria-expanded="(!isCollapsed).toString()"
    >
      <div class="flex items-center gap-2">
        <FunnelIcon class="w-5 h-5 transition-colors duration-300 group-hover:scale-110 transform
                           text-slate-600 dark:text-slate-400
                           group-hover:text-primary-600 dark:group-hover:text-primary-400" />
        <Heading :level="5" class="transition-colors duration-300
                                   group-hover:text-primary-600 dark:group-hover:text-primary-400">
          {{ title }}
        </Heading>
      </div>
      <ChevronDownIcon
        class="w-5 h-5 transition-colors duration-300 transform
               text-slate-600 dark:text-slate-400
               group-hover:text-primary-600 dark:group-hover:text-primary-400"
        :class="isCollapsed ? '-rotate-90' : 'rotate-0'"
      />
    </button>

    <Transition
      enter-active-class="transition-all duration-300 ease-out overflow-hidden"
      enter-from-class="opacity-0 -translate-y-2 max-h-0"
      enter-to-class="opacity-100 translate-y-0 max-h-[32rem]"
      leave-active-class="transition-all duration-200 ease-in overflow-hidden"
      leave-from-class="opacity-100 translate-y-0 max-h-[32rem]"
      leave-to-class="opacity-0 -translate-y-2 max-h-0"
    >
      <div v-show="!isCollapsed" :class="gridClasses">
        <slot />
      </div>
    </Transition>
  </CardBase>
</template>

<script setup>
import { computed, ref, Transition, onMounted } from 'vue';
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
    default: true,
  },
});

const getInitialCollapsed = () => {
  if (typeof window !== 'undefined' && window.innerWidth < 768) {
    return true;
  }
  return props.defaultCollapsed;
};

const isCollapsed = ref(getInitialCollapsed());

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

