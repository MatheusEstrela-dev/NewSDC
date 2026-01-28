<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import PrinterIcon from '@/Components/Icons/PrinterIcon.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Imprimir Documento',
  },
  documentTitle: {
    type: String,
    default: 'Documento',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const printContentRef = ref(null);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = null;
    }
  },
  { immediate: true }
);

const close = () => {
  emit('close');
};

const closeOnEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    close();
  }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = null;
});

function handlePrint() {
  const printContent = printContentRef.value;
  if (!printContent) return;

  const printWindow = window.open('', '_blank');
  if (!printWindow) return;

  printWindow.document.write(`
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>${props.documentTitle}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; border-radius: 0 !important; }
        body { font-family: Arial, sans-serif; font-size: 10px; background: white; }
        .container { width: 210mm; max-width: 100%; margin: 0 auto; padding: 10mm; }
        .card { border: 2px solid #000; }
        .card-header { background: #003d82; color: white; padding: 12px; border-bottom: 2px solid #000; }
        .card-header-content { display: flex; align-items: center; justify-content: space-between; }
        .brasao { width: 70px; height: auto; }
        .header-text { text-align: center; flex: 1; padding: 0 12px; }
        .header-text h5 { font-size: 11px; margin-bottom: 4px; }
        .header-text h4 { font-size: 13px; }
        .bos-badge { background: #f5f5f5; color: #333; padding: 4px 8px; font-size: 10px; }
        .section-title { background: #2c3e50; color: white; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid #000; }
        .subsection-title { background: #d5d5d5; color: #000; padding: 4px 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; border: 1px solid #000; border-bottom: none; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        .field-label { background: #e8e8e8; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .field-value { background: white; font-size: 10px; min-height: 18px; }
        .historico-text { min-height: 100px; padding: 8px; text-align: justify; white-space: pre-wrap; font-size: 9px; line-height: 1.3; }
        .signature-line { text-align: center; padding-top: 15px; }
        ul { margin: 0; padding-left: 20px; list-style-type: disc; }
        li { margin-bottom: 5px; }
        @media print {
          body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
          @page { size: A4; margin: 10mm; }
        }
      </style>
    </head>
    <body>
      ${printContent.innerHTML}
    </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
  }, 250);
}

defineExpose({ printContentRef, handlePrint });
</script>

<template>
  <Teleport to="body">
    <Transition leave-active-class="duration-200">
      <div
        v-show="show"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0"
        style="z-index: 9999 !important;"
        scroll-region
      >
        <!-- Backdrop with blur - this blurs the background -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-show="show"
            class="fixed inset-0 transform transition-all"
            style="z-index: 9998 !important;"
            @click="close"
          >
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-md" />
          </div>
        </Transition>

        <!-- Modal content - isolated from blur -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-show="show"
            class="relative mb-6 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-2xl ring-1 ring-black/10 transform transition-all sm:w-full sm:mx-auto max-w-6xl isolate"
            style="z-index: 10000 !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important;"
          >
            <div class="flex items-center justify-between px-6 py-4 bg-sky-600 text-white">
              <h3 class="text-lg font-semibold flex items-center gap-2">
                <PrinterIcon class="w-5 h-5" />
                {{ title }}
              </h3>
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="handlePrint"
                  class="px-4 py-2 bg-white text-sky-600 rounded-lg font-medium hover:bg-sky-50 transition-colors flex items-center gap-2"
                >
                  <PrinterIcon class="w-4 h-4" />
                  Imprimir
                </button>
                <button
                  type="button"
                  @click="close"
                  class="p-2 hover:bg-sky-700 rounded-lg transition-colors"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>
            </div>

            <div class="max-h-[80vh] overflow-y-auto p-6 bg-gray-100 dark:bg-gray-900">
              <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Carregando dados...</span>
              </div>

              <div v-else ref="printContentRef" class="print-content bg-white">
                <slot />
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.print-content {
  font-family: Arial, sans-serif;
  font-size: 10px;
}

.print-content :deep(.section-title) {
  background-color: #2c3e50;
  color: white;
  padding: 6px 10px;
  font-weight: bold;
  font-size: 10px;
  text-align: left;
  margin: 0;
  border: 1px solid #000;
  text-transform: uppercase;
}

.print-content :deep(.subsection-title) {
  background-color: #d5d5d5;
  color: #000;
  padding: 4px 8px;
  font-weight: bold;
  font-size: 8px;
  text-align: left;
  margin: 0;
  border: 1px solid #000;
  border-bottom: none;
  text-transform: uppercase;
}

.print-content :deep(table) {
  border-collapse: collapse;
  width: 100%;
}

.print-content :deep(td) {
  border: 1px solid #000;
  padding: 3px 5px;
  vertical-align: top;
}

.print-content :deep(.field-label) {
  background-color: #e8e8e8;
  font-weight: bold;
  font-size: 8px;
  text-transform: uppercase;
  padding: 2px 4px;
  color: #000;
}

.print-content :deep(.field-value) {
  background-color: #fff;
  padding: 3px 5px;
  min-height: 18px;
  font-size: 10px;
  color: #000;
}
</style>
