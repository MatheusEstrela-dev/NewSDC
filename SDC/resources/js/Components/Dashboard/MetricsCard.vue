<template>
  <div
    class="bg-white rounded-xl p-5 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 hover:border-blue-200 hover:shadow-lg transition-all duration-300 group cursor-default relative overflow-hidden"
  >
    <!-- Decoration -->
    <div
      class="absolute right-0 top-0 w-24 h-24 bg-slate-50 rounded-bl-full -mr-4 -mt-4 transition-colors group-hover:bg-blue-50/50"
    ></div>

    <div class="relative z-10">
      <div class="flex justify-between items-start mb-4">
        <div
          :class="[
            metric.color,
            'w-10 h-10 rounded-lg flex items-center justify-center text-white shadow-md group-hover:scale-110 transition-transform duration-300',
          ]"
        >
          <IconComponent class="w-5 h-5" />
        </div>
        <span
          v-if="showTrend"
          class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-full border border-slate-100 group-hover:border-blue-100 group-hover:text-blue-400 transition-colors"
        >
          {{ trend }}
        </span>
      </div>

      <div>
        <p class="text-3xl font-bold text-slate-800 mt-1 tracking-tight">
          {{ metric.val }}
        </p>
        <p class="text-sm font-medium text-slate-500">{{ metric.label }}</p>
      </div>

      <button
        v-if="showAction"
        @click.prevent="$emit('view-details', metric)"
        class="mt-4 text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 group/link opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0"
      >
        Analisar dados
        <ArrowRightIcon class="w-3 h-3 transition-transform group-hover/link:translate-x-1" />
      </button>
    </div>
  </div>
</template>

<script setup>
import ArrowRightIcon from '../Icons/ArrowRightIcon.vue';
import PencilIcon from '../Icons/PencilIcon.vue';
import ClockIcon from '../Icons/ClockIcon.vue';
import CheckIcon from '../Icons/CheckIcon.vue';
import CheckBadgeIcon from '../Icons/CheckBadgeIcon.vue';

const props = defineProps({
  metric: {
    type: Object,
    required: true,
  },
  showTrend: {
    type: Boolean,
    default: true,
  },
  trend: {
    type: String,
    default: '+2%',
  },
  showAction: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['view-details']);

// Mapeia ícones para componentes
const iconComponents = {
  pencil: PencilIcon,
  clock: ClockIcon,
  check: CheckIcon,
  'check-badge': CheckBadgeIcon,
};

const IconComponent = iconComponents[props.metric.icon] || CheckIcon;
</script>

