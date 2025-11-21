<template>
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
      @click.self="close"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100 ring-1 ring-white/20"
      >
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
          <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-1.5 rounded-md text-blue-600">
              <DocumentTextIcon class="w-5 h-5" />
            </div>
            <h3 class="text-lg font-bold text-slate-800">{{ title }}</h3>
          </div>
          <button
            @click="close"
            class="text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-full p-1 transition-all"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="p-6 bg-white max-h-[60vh] overflow-y-auto">
          <div class="text-sm text-slate-600 mb-2 font-medium">Dados brutos do sistema:</div>
          <pre
            class="text-xs bg-slate-800 text-emerald-400 p-4 rounded-xl overflow-x-auto font-mono border border-slate-700 shadow-inner"
          >
{{ formattedData }}
          </pre>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
          <button
            @click="close"
            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 hover:text-slate-800 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button
            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5"
            @click="handleViewProcess"
          >
            Visualizar Processo
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';
import XMarkIcon from '../Icons/XMarkIcon.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  data: {
    type: [Object, Array, String],
    default: null,
  },
});

const emit = defineEmits(['close', 'view-process']);

const formattedData = computed(() => {
  if (!props.data) return '';
  if (typeof props.data === 'string') return props.data;
  return JSON.stringify(props.data, null, 2);
});

function close() {
  emit('close');
}

function handleViewProcess() {
  emit('view-process', props.data);
  close();
}
</script>

