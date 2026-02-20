<template>
  <div class="flex items-center gap-1">
    <button
      v-if="showView"
      type="button"
      @click="$emit('view')"
      class="p-1.5 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200"
      title="Visualizar"
    >
      <EyeIcon class="w-4 h-4" />
    </button>
    <button
      v-if="showEdit"
      type="button"
      @click="$emit('edit')"
    />
    <ButtonIcon
      v-if="showUpload"
      :icon="UploadIcon"
      variant="warning"
      :size="size"
      title="Upload"
      @click="$emit('upload')"
    />
    <ButtonIcon
      v-if="showAttachments"
      type="button"
      @click="$emit('attachments')"
      class="p-1.5 rounded-lg text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 transition-all duration-200"
      title="Anexos"
    >
      <PaperClipIcon className="w-4 h-4" />
    </button>
    <button
      v-if="showDelete"
      type="button"
      @click="$emit('delete')"
      class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200"
      title="Excluir"
    >
      <TrashIcon class="w-4 h-4" />
    </button>
  </div>
</template>

<script setup>
import EyeIcon from '../../Icons/EyeIcon.vue';
import PencilIcon from '../../Icons/PencilIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';
import ArchiveBoxIcon from '../../Icons/ArchiveBoxIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';

const props = defineProps({
  showView: {
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
  showArchive: {
    type: Boolean,
    default: false,
  },
  showUpload: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

defineEmits(['view', 'print', 'edit', 'attachments', 'history', 'delete', 'archive', 'upload']);
</script>

