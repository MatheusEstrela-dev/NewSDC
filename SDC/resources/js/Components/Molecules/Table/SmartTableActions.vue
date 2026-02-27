<template>
  <TableActions
    v-if="hasAnyAction"
    v-bind="actionProps"
    :size="size"
    @view="$emit('view')"
    @print="$emit('print')"
    @edit="$emit('edit')"
    @delete="handleDelete"
    @attachments="$emit('attachments')"
    @history="$emit('history')"
    @archive="handleArchive"
    @upload="$emit('upload')"
  />
</template>

<script setup>
/**
 * SmartTableActions - TableActions com permissionamento automatico
 *
 * Integra com useActionConfig para verificar permissoes automaticamente.
 * Substitui o padrao manual de passar showView, showEdit, etc.
 *
 * Uso:
 * <SmartTableActions
 *   module="rat"
 *   :entity="item"
 *   @view="handleView(item)"
 *   @edit="handleEdit(item)"
 *   @delete="handleDelete(item)"
 * />
 */
import { computed } from 'vue';
import TableActions from './TableActions.vue';
import { useActionConfig } from '@/composables/useActionConfig';

const props = defineProps({
  module: {
    type: String,
    required: true,
  },
  entity: {
    type: Object,
    default: null,
  },
  overrides: {
    type: Object,
    default: () => ({}),
  },
  rules: {
    type: Object,
    default: () => ({}),
  },
  size: {
    type: String,
    default: 'md',
  },
});

const emit = defineEmits([
  'view',
  'print',
  'edit',
  'delete',
  'attachments',
  'history',
  'archive',
  'upload',
]);

const {
  getTableActionsProps,
  getTableActionsPropsForEntity,
  requiresConfirmation,
  getConfirmationMessage,
} = useActionConfig(props.module);

const actionProps = computed(() => {
  if (props.entity && Object.keys(props.rules).length > 0) {
    return getTableActionsPropsForEntity(props.entity, props.rules);
  }
  return getTableActionsProps(props.overrides);
});

const hasAnyAction = computed(() => {
  return Object.values(actionProps.value).some(v => v === true);
});

const handleDelete = async () => {
  if (requiresConfirmation('delete')) {
    const message = getConfirmationMessage('delete');
    const confirmed = window.confirm(message || 'Tem certeza que deseja excluir?');
    if (!confirmed) return;
  }
  emit('delete');
};

const handleArchive = async () => {
  if (requiresConfirmation('archive')) {
    const message = getConfirmationMessage('archive');
    const confirmed = window.confirm(message || 'Tem certeza que deseja arquivar?');
    if (!confirmed) return;
  }
  emit('archive');
};
</script>
