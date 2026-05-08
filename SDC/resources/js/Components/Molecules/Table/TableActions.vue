<template>
  <div class="flex items-center gap-2">
    <ActionButton
      v-for="action in visibleActions"
      :key="action.name"
      :module="module"
      :resource="resource"
      :action="action.name"
      :variant="action.variant"
      :label="action.label"
      :allowed="true"
      :show-label="false"
      :size="size"
      :tooltip-text="action.label"
      @click="emit(action.event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';

const props = defineProps({
  module: {
    type: String,
    default: 'global',
  },
  resource: {
    type: String,
    default: null,
  },
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
  showArchive: {
    type: Boolean,
    default: false,
  },
  showUpload: {
    type: Boolean,
    default: false,
  },
  showWarning: {
    type: Boolean,
    default: false,
  },
  showOptions: {
    type: Boolean,
    default: false,
  },
  showAssign: {
    type: Boolean,
    default: false,
  },
  showNotifications: {
    type: Boolean,
    default: false,
  },
  showExport: {
    type: Boolean,
    default: false,
  },
  showDuplicate: {
    type: Boolean,
    default: false,
  },
  showFinalize: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const emit = defineEmits([
  'view',
  'print',
  'edit',
  'attachments',
  'history',
  'delete',
  'archive',
  'upload',
  'warning',
  'options',
  'assign',
  'notifications',
  'export',
  'duplicate',
  'finalize',
]);

const actions = computed(() => [
  { name: 'view', event: 'view', show: props.showView, label: 'Visualizar' },
  { name: 'print', event: 'print', show: props.showPrint, label: 'Imprimir' },
  { name: 'edit', event: 'edit', show: props.showEdit, label: 'Editar' },
  { name: 'warning', event: 'warning', show: props.showWarning, label: 'Aviso', variant: 'vibrant-warning' },
  { name: 'upload', event: 'upload', show: props.showUpload, label: 'Upload' },
  { name: 'attachments', event: 'attachments', show: props.showAttachments, label: 'Anexos' },
  { name: 'history', event: 'history', show: props.showHistory, label: 'Serie Historica' },
  { name: 'notifications', event: 'notifications', show: props.showNotifications, label: 'Notificacoes' },
  { name: 'export', event: 'export', show: props.showExport, label: 'Exportar' },
  { name: 'duplicate', event: 'duplicate', show: props.showDuplicate, label: 'Duplicar' },
  { name: 'finalize', event: 'finalize', show: props.showFinalize, label: 'Finalizar' },
  { name: 'archive', event: 'archive', show: props.showArchive, label: 'Arquivar' },
  { name: 'delete', event: 'delete', show: props.showDelete, label: 'Excluir', variant: 'vibrant-danger' },
  { name: 'assign', event: 'assign', show: props.showAssign, label: 'Atribuir Analista' },
  { name: 'options', event: 'options', show: props.showOptions, label: 'Opcoes' },
]);

const visibleActions = computed(() => actions.value.filter((action) => action.show));
</script>
