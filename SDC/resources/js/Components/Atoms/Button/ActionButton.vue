<template>
  <!-- ===== MODO GRUPO ===== -->
  <div v-if="isGroupMode" class="flex items-center gap-2">
    <ButtonIcon
      v-for="(item, idx) in visibleInlineActions"
      :key="`inline-${idx}-${item.action}`"
      :icon="ActionIcons[item.action]"
      :variant="item.variant ?? ActionIconVariants[item.action] ?? 'secondary'"
      :size="size"
      :title="item.label ?? ActionLabels[item.action] ?? item.action"
      @click="item.handler"
    />

    <Dropdown
      v-if="visibleMenuActions.length > 0"
      align="right"
      width="48"
      content-classes="py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
    >
      <template #trigger>
        <ButtonIcon
          :icon="ActionIcons.options"
          variant="secondary"
          :size="size"
          title="Opcoes"
        />
      </template>

      <template #content>
        <button
          v-for="(item, idx) in visibleMenuActions"
          :key="`menu-${idx}-${item.action}`"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
          @click="item.handler"
        >
          <component
            :is="ActionIcons[item.action]"
            class="w-4 h-4 flex-shrink-0"
          />
          <span>{{ item.label ?? ActionLabels[item.action] ?? item.action }}</span>
        </button>
      </template>
    </Dropdown>
  </div>

  <!-- ===== MODO UNICO (compatibilidade) ===== -->
  <template v-else-if="shouldRenderSingle">
    <input
      v-if="isUploadButton"
      ref="fileInputRef"
      type="file"
      class="hidden"
      :accept="uploadAccept"
      :multiple="uploadMultiple"
      @change="handleFileChange"
    />
    <ButtonIcon
      v-if="!showLabel"
      :icon="computedIcon"
      :variant="computedIconVariant"
      :size="size"
      :disabled="buttonDisabled"
      :type="type"
      :title="tooltipTitle"
      @click="handleClick"
    />
    <Button
      v-else
      :variant="computedVariant"
      :size="size"
      :icon="computedIcon"
      :icon-position="iconPosition"
      :disabled="buttonDisabled"
      :loading="buttonLoading"
      :type="type"
      :full-width="fullWidth"
      :title="tooltipTitle"
      @click="handleClick"
    >
      <slot>{{ computedLabel }}</slot>
    </Button>
  </template>
</template>

<script setup>
import { computed, markRaw, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Button from './Button.vue';
import ButtonIcon from './ButtonIcon.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { usePermissions } from '@/composables/auth';

import PlusIcon from '../../Icons/PlusIcon.vue';
import EyeIcon from '../../Icons/EyeIcon.vue';
import PencilIcon from '../../Icons/PencilIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PrinterIcon from '../../Icons/PrinterIcon.vue';
import DownloadIcon from '../../Icons/DownloadIcon.vue';
import DocumentIcon from '../../Icons/DocumentIcon.vue';
import DocumentTextIcon from '../../Icons/DocumentTextIcon.vue';
import CheckIcon from '../../Icons/CheckIcon.vue';
import ArchiveBoxIcon from '../../Icons/ArchiveBoxIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';
import ExclamationIcon from '../../Icons/ExclamationIcon.vue';
import EllipsisVerticalIcon from '../../Icons/EllipsisVerticalIcon.vue';
import UserIcon from '../../Icons/UserIcon.vue';
import BellIcon from '../../Icons/BellIcon.vue';

const ActionIcons = {
  create: markRaw(PlusIcon),
  view: markRaw(EyeIcon),
  edit: markRaw(PencilIcon),
  delete: markRaw(TrashIcon),
  print: markRaw(PrinterIcon),
  export: markRaw(DownloadIcon),
  duplicate: markRaw(DocumentIcon),
  finalize: markRaw(CheckIcon),
  check: markRaw(CheckIcon),
  pdf: markRaw(DocumentTextIcon),
  archive: markRaw(ArchiveBoxIcon),
  upload: markRaw(UploadIcon),
  attachments: markRaw(PaperClipIcon),
  history: markRaw(ClockIcon),
  warning: markRaw(ExclamationIcon),
  options: markRaw(EllipsisVerticalIcon),
  assign: markRaw(UserIcon),
  notifications: markRaw(BellIcon),
};

const ActionLabels = {
  create: 'Novo',
  view: 'Visualizar',
  edit: 'Editar',
  delete: 'Excluir',
  print: 'Imprimir',
  export: 'Exportar',
  duplicate: 'Duplicar',
  finalize: 'Finalizar',
  check: 'Validar',
  pdf: 'PDF',
  archive: 'Arquivar',
  upload: 'Upload',
  attachments: 'Anexos',
  history: 'Historico',
  warning: 'Aviso',
  options: 'Opcoes',
  assign: 'Atribuir',
  notifications: 'Notificacoes',
};

const ActionVariants = {
  create: 'primary',
  view: 'primary',
  edit: 'warning',
  delete: 'danger',
  print: 'info',
  export: 'secondary',
  duplicate: 'secondary',
  finalize: 'success',
  check: 'success',
  pdf: 'danger',
  archive: 'warning',
  upload: 'black',
  attachments: 'success',
  history: 'info',
  warning: 'warning',
  options: 'secondary',
  assign: 'info',
  notifications: 'secondary',
};

const ActionIconVariants = {
  create: 'primary',
  view: 'primary',
  edit: 'warning',
  delete: 'vibrant-danger',
  print: 'info',
  export: 'secondary',
  duplicate: 'secondary',
  finalize: 'success',
  check: 'success',
  pdf: 'vibrant-danger',
  archive: 'topaz',
  upload: 'black',
  attachments: 'success',
  history: 'success',
  warning: 'vibrant-warning',
  options: 'secondary',
  assign: 'info',
  notifications: 'secondary',
};

const ACTION_ALIAS = {
  check: 'validar',
  archive: 'arquivar',
  assign: 'atribuir',
};

const UI_ONLY_ACTIONS = ['options', 'warning', 'notifications'];

const props = defineProps({
  module: { type: String, default: 'global' },
  resource: { type: String, default: null },
  action: {
    type: String,
    default: null,
    validator: (value) => value === null || [
      'create', 'view', 'edit', 'delete', 'print',
      'export', 'duplicate', 'finalize', 'attachments',
      'history', 'archive', 'upload', 'warning',
      'options', 'assign', 'notifications', 'check', 'pdf'
    ].includes(value),
  },
  actions: { type: Array, default: null },
  allowed: { type: Boolean, default: null },
  fallback: { type: String, default: 'hide', validator: v => ['hide', 'disable'].includes(v) },
  variant: { type: String, default: null },
  label: { type: String, default: null },
  showLabel: { type: Boolean, default: true },
  icon: { type: [Object, Function], default: null },
  size: { type: String, default: 'md' },
  iconPosition: { type: String, default: 'left' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  fullWidth: { type: Boolean, default: false },
  tooltipText: { type: String, default: '' },
  uploadUrl: { type: String, default: null },
  uploadFieldName: { type: String, default: 'arquivo' },
  uploadData: { type: Object, default: () => ({}) },
  uploadAccept: { type: String, default: '' },
  uploadMultiple: { type: Boolean, default: false },
  uploadOptions: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
  'click',
  'files-selected',
  'upload-start',
  'upload-success',
  'upload-error',
  'upload-finish',
]);

const fileInputRef = ref(null);
const isUploading = ref(false);

const { can } = usePermissions();
const page = usePage();

const isSuperAdmin = computed(() => page.props.auth?.user?.is_super_admin === true);

function hasPermissionFor({ action, module = props.module, resource = props.resource, allowed = null, aliasOverride = null }) {
  if (isSuperAdmin.value) return true;
  if (UI_ONLY_ACTIONS.includes(action)) return true;

  const slugAction = aliasOverride ?? ACTION_ALIAS[action] ?? action;

  let slug;
  if (resource === '' || resource === null || resource === undefined) {
    if (!module || module === 'global') return allowed === true;
    slug = `${module}.${slugAction}`;
  } else {
    slug = `${module}.${resource}.${slugAction}`;
  }

  const canByPerm = can(slug);
  if (allowed !== null) return canByPerm && allowed;
  return canByPerm;
}

const isGroupMode = computed(() => Array.isArray(props.actions));

const visibleInlineActions = computed(() =>
  (props.actions ?? [])
    .filter(a => (a.placement ?? 'inline') === 'inline')
    .filter(a => hasPermissionFor({
      action: a.action,
      module: a.module,
      resource: a.resource,
      allowed: a.allowed ?? null,
      aliasOverride: a.aliasOverride ?? null,
    }))
);

const visibleMenuActions = computed(() =>
  (props.actions ?? [])
    .filter(a => a.placement === 'menu')
    .filter(a => hasPermissionFor({
      action: a.action,
      module: a.module,
      resource: a.resource,
      allowed: a.allowed ?? null,
      aliasOverride: a.aliasOverride ?? null,
    }))
);

const hasPermissionSingle = computed(() => hasPermissionFor({
  action: props.action,
  module: props.module,
  resource: props.resource,
  allowed: props.allowed,
}));

const shouldRenderSingle = computed(() => {
  if (props.fallback === 'hide') return hasPermissionSingle.value;
  return true;
});

const isDisabledSingle = computed(() => {
  if (props.fallback === 'disable') return !hasPermissionSingle.value;
  return false;
});

const isUploadButton = computed(() => props.action === 'upload' && !!props.uploadUrl);

const buttonDisabled = computed(() => isDisabledSingle.value || props.disabled || props.loading || isUploading.value);
const buttonLoading = computed(() => props.loading || isUploading.value);

const computedVariant = computed(() => props.variant || ActionVariants[props.action] || 'primary');
const computedIconVariant = computed(() => props.variant || ActionIconVariants[props.action] || 'secondary');
const computedIcon = computed(() => props.icon || ActionIcons[props.action] || null);
const computedLabel = computed(() => props.label !== null ? props.label : ActionLabels[props.action] || '');

const tooltipTitle = computed(() => {
  if (!hasPermissionSingle.value && props.fallback === 'disable') {
    return props.tooltipText || 'Voce nao possui permissao para esta acao';
  }
  return props.tooltipText || computedLabel.value || '';
});

const handleClick = (event) => {
  if (!hasPermissionSingle.value || buttonDisabled.value) return;
  emit('click', event);
  if (isUploadButton.value) fileInputRef.value?.click();
};

const appendFormValue = (formData, key, value) => {
  if (value === null || value === undefined) return;
  if (Array.isArray(value)) {
    value.forEach((item) => appendFormValue(formData, `${key}[]`, item));
    return;
  }
  formData.append(key, value);
};

const buildUploadPayload = (files) => {
  const formData = new FormData();
  Object.entries(props.uploadData).forEach(([key, value]) => {
    appendFormValue(formData, key, value);
  });
  files.forEach((file) => {
    formData.append(props.uploadFieldName, file);
  });
  return formData;
};

const uploadFiles = (files) => {
  if (!files.length || !props.uploadUrl) return;

  isUploading.value = true;
  emit('upload-start', files);

  router.post(props.uploadUrl, buildUploadPayload(files), {
    preserveScroll: true,
    forceFormData: true,
    ...props.uploadOptions,
    onSuccess: (pageObj) => {
      props.uploadOptions.onSuccess?.(pageObj);
      emit('upload-success', { files, page: pageObj });
    },
    onError: (errors) => {
      props.uploadOptions.onError?.(errors);
      emit('upload-error', { files, errors });
    },
    onFinish: (visit) => {
      props.uploadOptions.onFinish?.(visit);
      isUploading.value = false;
      emit('upload-finish', { files, visit });
    },
  });
};

const handleFileChange = (event) => {
  const files = Array.from(event.target.files || []);
  emit('files-selected', files);
  uploadFiles(files);
  event.target.value = '';
};
</script>
