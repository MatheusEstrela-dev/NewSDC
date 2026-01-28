<template>
  <div class="flex items-center gap-2">
    <ButtonIcon
      v-if="showView"
      :icon="EyeIcon"
      variant="primary"
      :size="size"
      title="Visualizar"
      @click="$emit('view')"
    />
    <ButtonIcon
      v-if="showPrint"
      :icon="PrinterIcon"
      variant="info"
      :size="size"
      title="Imprimir"
      @click="$emit('print')"
    />
    <ButtonIcon
      v-if="showEdit"
      :icon="PencilIcon"
      variant="warning"
      :size="size"
      title="Editar"
      @click="$emit('edit')"
    />
    <ButtonIcon
      v-if="showAttachments"
      :icon="PaperClipIcon"
      variant="success"
      :size="size"
      title="Anexos"
      @click="$emit('attachments')"
    />
    <ButtonIcon
      v-if="showDelete"
      :icon="TrashIcon"
      variant="danger"
      :size="size"
      title="Excluir"
      @click="$emit('delete')"
    />
    <ButtonIcon
      v-if="showHistory"
      :icon="ClockIcon"
      variant="success"
      :size="size"
      title="Série Histórica"
      @click="$emit('history')"
    />
  </div>
</template>

<script setup>
import ButtonIcon from '../../Atoms/Button/ButtonIcon.vue';
import EyeIcon from '../../Icons/EyeIcon.vue';
import PrinterIcon from '../../Icons/PrinterIcon.vue';
import PencilIcon from '../../Icons/PencilIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';

defineProps({
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
  showHistory: {
    type: Boolean,
    default: false,
  },
  showDelete: {
    type: Boolean,
    default: true,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

defineEmits(['view', 'print', 'edit', 'attachments', 'history', 'delete']);
</script>

