<template>
  <div class="flex items-center gap-2">
    <button
      v-if="showView"
      type="button"
      @click="$emit('view')"
      class="p-2 sm:p-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200 touch-manipulation flex items-center justify-center"
      title="Visualizar"
    >
      <EyeIcon class="w-6 h-6" />
    </button>
    <button
      v-if="showPrint"
      type="button"
      @click="$emit('print')"
      class="p-2 sm:p-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 rounded-lg text-sky-400 hover:text-sky-300 hover:bg-sky-500/10 transition-all duration-200 touch-manipulation flex items-center justify-center"
      title="Imprimir Boletim"
    >
      <PrinterIcon class="w-6 h-6" />
    </button>
    <button
      v-if="showEdit"
      type="button"
      @click="$emit('edit')"
      class="p-2 sm:p-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 rounded-lg text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-all duration-200 touch-manipulation flex items-center justify-center"
      title="Editar"
    >
      <PencilIcon class="w-6 h-6" />
    </button>
    <button
      v-if="showAttachments"
      type="button"
      @click="$emit('attachments')"
      class="p-2 sm:p-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 rounded-lg text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 transition-all duration-200 touch-manipulation flex items-center justify-center"
      title="Anexos"
    >
      <PaperClipIcon className="w-6 h-6" />
    </button>
    <button
      v-if="showDelete"
      type="button"
      @click="$emit('delete')"
      class="p-2 sm:p-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200 touch-manipulation flex items-center justify-center"
      title="Excluir"
    >
      <TrashIcon class="w-6 h-6" />
    </button>
  </div>
</template>

<script setup>
import EyeIcon from '../../Icons/EyeIcon.vue';
import PrinterIcon from '../../Icons/PrinterIcon.vue';
import PencilIcon from '../../Icons/PencilIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';

const props = defineProps({
  showView: {
    type: Boolean,
    default: true,
  },
  showPrint: {
    type: Boolean,
    default: true,
  },
  showEdit: {
    type: Boolean,
    default: true,
  },
  showAttachments: {
    type: Boolean,
    default: true,
  },
  showDelete: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['view', 'print', 'edit', 'attachments', 'delete']);
</script>

