<template>
  <template v-if="shouldRender">
    <Button
      v-bind="$attrs"
      :variant="variant"
      :size="size"
      :icon="icon"
      :icon-position="iconPosition"
      :disabled="isDisabled || disabled"
      :loading="loading"
      :type="type"
      :full-width="fullWidth"
      :title="tooltipTitle"
      @click="handleClick"
    >
      <slot />
    </Button>
  </template>
</template>

<script setup>
/**
 * PermissionButton - Botao com verificacao de permissao integrada
 *
 * Comportamentos de fallback:
 * - 'hide': Esconde o botao completamente (padrao)
 * - 'disable': Mostra botao desabilitado
 * - 'tooltip': Mostra botao desabilitado com tooltip explicativo
 *
 * Uso:
 * <PermissionButton permission="rat.protocolos.create" @click="criar">
 *   Novo Protocolo
 * </PermissionButton>
 */
import { computed } from 'vue';
import { usePermissions } from '@/composables/auth';
import Button from './Button.vue';

const props = defineProps({
  permission: {
    type: String,
    required: true,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
  requireAll: {
    type: Boolean,
    default: false,
  },
  fallback: {
    type: String,
    default: 'hide',
    validator: (value) => ['hide', 'disable', 'tooltip'].includes(value),
  },
  tooltipText: {
    type: String,
    default: 'Voce nao possui permissao para esta acao',
  },
  variant: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'md',
  },
  icon: {
    type: [Object, Function],
    default: null,
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
});

const emit = defineEmits(['click']);

const { can, canAny, canAll } = usePermissions();

const hasPermission = computed(() => {
  if (props.permissions.length > 0) {
    return props.requireAll
      ? canAll(props.permissions)
      : canAny(props.permissions);
  }
  return can(props.permission);
});

const shouldRender = computed(() => {
  if (props.fallback === 'hide') {
    return hasPermission.value;
  }
  return true;
});

const isDisabled = computed(() => {
  if (props.fallback === 'disable' || props.fallback === 'tooltip') {
    return !hasPermission.value;
  }
  return false;
});

const tooltipTitle = computed(() => {
  if (!hasPermission.value && props.fallback === 'tooltip') {
    return props.tooltipText;
  }
  return '';
});

const handleClick = (event) => {
  if (hasPermission.value && !props.disabled && !props.loading) {
    emit('click', event);
  }
};
</script>
