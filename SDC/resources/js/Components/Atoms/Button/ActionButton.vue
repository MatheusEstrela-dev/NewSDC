<template>
  <template v-if="shouldRender">
    <ButtonIcon
      v-if="!showLabel"
      :icon="computedIcon"
      :variant="computedIconVariant"
      :size="size"
      :disabled="isDisabled || disabled || loading"
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
      :disabled="isDisabled || disabled"
      :loading="loading"
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
 */
import { computed, markRaw } from 'vue';
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
import CheckIcon from '../../Icons/CheckIcon.vue';
import ArchiveBoxIcon from '../../Icons/ArchiveBoxIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';

const ActionIcons = {
  create: markRaw(PlusIcon),
  view: markRaw(EyeIcon),
  edit: markRaw(PencilIcon),
  delete: markRaw(TrashIcon),
  print: markRaw(PrinterIcon),
  export: markRaw(DownloadIcon),
  duplicate: markRaw(DocumentIcon),
  finalize: markRaw(CheckIcon),
  archive: markRaw(ArchiveBoxIcon),
  upload: markRaw(UploadIcon),
  attachments: markRaw(PaperClipIcon),
  history: markRaw(ClockIcon),
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
  archive: 'Arquivar',
  upload: 'Upload',
  attachments: 'Anexos',
  history: 'Historico',
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
  archive: 'warning',
  upload: 'info',
  attachments: 'success',
  history: 'info',
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
  archive: 'topaz',
  upload: 'warning',
  attachments: 'success',
  history: 'success',
};

const props = defineProps({
  module: {
    type: String,
    required: true,
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
      'history', 'archive', 'upload'
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
    default: 'Voce nao possui permissao para esta acao',
  },
});

const emit = defineEmits(['click']);

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
    return props.tooltipText;
  }
  return getTooltip(props.action) || '';
});

const handleClick = (event) => {
  if (hasPermission.value && !props.disabled && !props.loading) {
    emit('click', event);
  }
};
</script>
