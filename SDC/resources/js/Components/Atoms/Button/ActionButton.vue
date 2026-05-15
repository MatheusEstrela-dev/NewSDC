<template>
  <template v-if="shouldRender">
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
      <slot v-if="showLabel">{{ computedLabel }}</slot>
    </Button>
  </template>
</template>

<script setup>
/**
 * ActionButton - Botao inteligente para acoes CRUD
 *
 * Auto-configura visual e permissao baseado no tipo de acao.
 * Integra com useActionConfig para verificar permissoes por modulo.
 *
 * Uso simples (auto-detecta tudo):
 * <ActionButton module="rat" action="create" @click="criar" />
 *
 * Uso customizado:
 * <ActionButton
 *   module="rat"
 *   action="export"
 *   variant="info"
 *   @click="exportar"
 * >
 *   Exportar CSV
 * </ActionButton>
 *
 * Upload com envio real:
 * <ActionButton
 *   action="upload"
 *   upload-url="/endpoint"
 *   upload-field-name="arquivo"
 *   @upload-success="recarregar"
 * />
 */
import { computed, markRaw, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from './Button.vue';
import ButtonIcon from './ButtonIcon.vue';
import { useActionConfig } from '@/composables/ui';
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

const props = defineProps({
  module: {
    type: String,
    default: 'global',
  },
  resource: {
    type: String,
    default: null,
  },
  action: {
    type: String,
    required: true,
    validator: (value) => [
      'create', 'view', 'edit', 'delete', 'print',
      'export', 'duplicate', 'finalize', 'attachments',
      'history', 'archive', 'upload', 'warning',
      'options', 'assign', 'notifications', 'check', 'pdf'
    ].includes(value),
  },
  fallback: {
    type: String,
    default: 'hide',
    validator: (value) => ['hide', 'disable'].includes(value),
  },
  variant: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: null,
  },
  showLabel: {
    type: Boolean,
    default: true,
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  allowed: {
    type: Boolean,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
  },
  iconPosition: {
    type: String,
    default: 'left',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: 'button',
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
  tooltipText: {
    type: String,
    default: '',
  },
  uploadUrl: {
    type: String,
    default: null,
  },
  uploadFieldName: {
    type: String,
    default: 'arquivo',
  },
  uploadData: {
    type: Object,
    default: () => ({}),
  },
  uploadAccept: {
    type: String,
    default: '',
  },
  uploadMultiple: {
    type: Boolean,
    default: false,
  },
  uploadOptions: {
    type: Object,
    default: () => ({}),
  },
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
const { isActionEnabled, getTooltip } = useActionConfig(props.module);

const hasPermission = computed(() => {
  if (props.allowed !== null) {
    return props.allowed;
  }

  if (props.action === 'create' && props.resource) {
    return can(`${props.module}.${props.resource}.create`);
  }
  return isActionEnabled(props.action);
});

const shouldRender = computed(() => {
  if (props.fallback === 'hide') {
    return hasPermission.value;
  }
  return true;
});

const isDisabled = computed(() => {
  if (props.fallback === 'disable') {
    return !hasPermission.value;
  }
  return false;
});

const isUploadButton = computed(() => {
  return props.action === 'upload' && !!props.uploadUrl;
});

const buttonDisabled = computed(() => {
  return isDisabled.value || props.disabled || props.loading || isUploading.value;
});

const buttonLoading = computed(() => {
  return props.loading || isUploading.value;
});

const computedVariant = computed(() => {
  return props.variant || ActionVariants[props.action] || 'primary';
});

const computedIconVariant = computed(() => {
  return props.variant || ActionIconVariants[props.action] || 'secondary';
});

const computedIcon = computed(() => {
  return props.icon || ActionIcons[props.action] || null;
});

const computedLabel = computed(() => {
  return props.label !== null ? props.label : ActionLabels[props.action] || '';
});

const tooltipTitle = computed(() => {
  if (!hasPermission.value && props.fallback === 'disable') {
    return props.tooltipText || 'Voce nao possui permissao para esta acao';
  }
  return getTooltip(props.action) || props.tooltipText || computedLabel.value || '';
});

const handleClick = (event) => {
  if (!hasPermission.value || buttonDisabled.value) {
    return;
  }

  emit('click', event);

  if (isUploadButton.value) {
    fileInputRef.value?.click();
  }
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
    onSuccess: (page) => {
      props.uploadOptions.onSuccess?.(page);
      emit('upload-success', { files, page });
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
