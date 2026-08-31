<template>
  <div class="flex items-center gap-2">
    <ActionButton
      v-for="action in inlineActions"
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

    <Dropdown
      v-if="hasMenuActions"
      align="right"
      width="48"
      content-classes="py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
    >
      <template #trigger>
        <ActionButton
          :module="module"
          :resource="resource"
          action="options"
          :allowed="true"
          :show-label="false"
          :size="size"
          tooltip-text="Opcoes"
        />
      </template>

      <template #content>
        <button
          v-for="action in menuActions"
          :key="action.name"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
          @click="emit(action.event)"
        >
          <component :is="action.icon" :class="['w-4 h-4 flex-shrink-0', action.iconClass]" />
          <span>{{ action.label }}</span>
        </button>
      </template>
    </Dropdown>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import QrCodeIcon from '@/Components/Icons/QrCodeIcon.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';

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
  attachmentsLabel: {
    type: String,
    default: 'Anexos',
  },
  showFinalize: {
    type: Boolean,
    default: false,
  },
  showCheck: {
    type: Boolean,
    default: false,
  },
  showPdf: {
    type: Boolean,
    default: false,
  },
  // Acao de menu, nao inline: o QR nao e usado a toda hora, e disputar espaco
  // com visualizar/editar/excluir empurraria as acoes rotineiras para longe.
  showQrcode: {
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
  'check',
  'pdf',
  'qrcode',
]);

const actions = computed(() => [
  { name: 'view', event: 'view', show: props.showView, label: 'Visualizar' },
  { name: 'print', event: 'print', show: props.showPrint, label: 'Imprimir' },
  { name: 'edit', event: 'edit', show: props.showEdit, label: 'Editar' },
  { name: 'warning', event: 'warning', show: props.showWarning, label: 'Aviso', variant: 'vibrant-warning' },
  { name: 'upload', event: 'upload', show: props.showUpload, label: 'Upload' },
  { name: 'attachments', event: 'attachments', show: props.showAttachments, label: props.attachmentsLabel },
  { name: 'history', event: 'history', show: props.showHistory, label: 'Serie Historica' },
  { name: 'notifications', event: 'notifications', show: props.showNotifications, label: 'Notificacoes' },
  { name: 'export', event: 'export', show: props.showExport, label: 'Exportar' },
  { name: 'duplicate', event: 'duplicate', show: props.showDuplicate, label: 'Duplicar' },
  { name: 'finalize', event: 'finalize', show: props.showFinalize, label: 'Finalizar' },
  { name: 'delete', event: 'delete', show: props.showDelete, label: 'Excluir', variant: 'vibrant-danger' },
]);

const menuActions = computed(() => [
  {
    name: 'check',
    event: 'check',
    show: props.showCheck,
    label: 'Validar',
    icon: CheckIcon,
    iconClass: 'text-emerald-400',
  },
  {
    name: 'pdf',
    event: 'pdf',
    show: props.showPdf,
    label: 'PDF',
    icon: DocumentTextIcon,
    iconClass: 'text-[#ff4d00]',
  },
  {
    name: 'qrcode',
    event: 'qrcode',
    show: props.showQrcode,
    label: 'QR Code',
    icon: QrCodeIcon,
    iconClass: 'text-cyan-400',
  },
  {
    name: 'archive',
    event: 'archive',
    show: props.showArchive,
    label: 'Arquivar',
    icon: ArchiveBoxIcon,
    iconClass: 'text-yellow-500',
  },
  {
    name: 'assign',
    event: 'assign',
    show: props.showAssign,
    label: 'Atribuir Analista',
    icon: UserIcon,
    iconClass: 'text-sky-400',
  },
].filter((action) => action.show));

const hasMenuActions = computed(() => menuActions.value.length > 0);

const inlineActions = computed(() => {
  const visible = actions.value.filter((action) => action.show);

  if (!props.showOptions || hasMenuActions.value) {
    return visible;
  }

  return [
    ...visible,
    { name: 'options', event: 'options', show: true, label: 'Opcoes' },
  ];
});
</script>
