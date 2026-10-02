<template>
  <Teleport to="body">
    <Transition name="dialog">
      <div v-if="isOpen" class="dialog-overlay bg-gray-500/75 dark:bg-black/75" @click="onCancel">
        <div
          ref="container"
          class="dialog-container bg-white outline-none dark:bg-slate-800"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="idTitulo"
          :aria-describedby="idMensagem"
          tabindex="-1"
          @click.stop
        >
          <div class="dialog-header border-slate-200 dark:border-slate-700" :class="variantClass">
            <div class="dialog-icon bg-slate-900/5 dark:bg-white/5" :class="iconClass">
              <component :is="currentIcon" />
            </div>
            <h3 :id="idTitulo" class="dialog-title text-slate-900 dark:text-slate-100">{{ title }}</h3>
            <button
              type="button"
              @click="onCancel"
              class="dialog-close bg-transparent text-slate-400 hover:bg-slate-900/5 hover:text-slate-600 dark:hover:bg-white/5 dark:hover:text-slate-200"
              aria-label="Fechar"
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="dialog-body">
            <p :id="idMensagem" class="dialog-message text-slate-700 dark:text-slate-200">{{ message }}</p>
            <p v-if="description" class="dialog-description text-slate-500 dark:text-slate-400">{{ description }}</p>
          </div>

          <div class="dialog-footer border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <button
              ref="botaoCancelar"
              @click="onCancel"
              class="btn bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
            >
              {{ cancelText }}
            </button>
            <button ref="botaoConfirmar" @click="onConfirm" class="btn" :class="confirmButtonClass" :disabled="loading">
              <svg v-if="loading" class="btn-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle class="spinner-circle" cx="12" cy="12" r="10" stroke-width="4" />
              </svg>
              <span>{{ confirmText }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, h, onUnmounted, ref, useId, watch } from 'vue';
import { usePrisaoDeFoco } from '@/Composables/ui/usePrisaoDeFoco';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: null
  },
  variant: {
    type: String,
    default: 'warning',
    validator: (value) => ['info', 'warning', 'danger', 'success'].includes(value)
  },
  confirmText: {
    type: String,
    default: 'Confirmar'
  },
  cancelText: {
    type: String,
    default: 'Cancelar'
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['confirm', 'cancel']);

// Ids para o leitor de tela anunciar titulo e mensagem ao abrir o dialogo.
const idBase = `confirm-dialog-${useId()}`;
const idTitulo = `${idBase}-titulo`;
const idMensagem = `${idBase}-mensagem`;

const variantClass = computed(() => `variant-${props.variant}`);

// Tom 600 no claro (o 400 some no branco); no escuro, o 400 de sempre.
const iconClass = computed(() => {
  const classes = {
    info: 'text-blue-600 dark:text-blue-400',
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-red-600 dark:text-red-400',
    success: 'text-emerald-600 dark:text-emerald-400'
  };
  return classes[props.variant] || classes.info;
});

const confirmButtonClass = computed(() => {
  const classes = {
    info: 'btn-primary',
    warning: 'btn-warning',
    danger: 'btn-danger',
    success: 'btn-success'
  };
  return classes[props.variant] || 'btn-primary';
});

const currentIcon = computed(() => {
  const icons = {
    info: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]),
    warning: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
    ]),
    danger: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]),
    success: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
    ])
  };
  return icons[props.variant] || icons.info;
});

const onConfirm = () => {
  emit('confirm');
};

const onCancel = () => {
  emit('cancel');
};

const container = ref(null);
const botaoCancelar = ref(null);
const botaoConfirmar = ref(null);

// Foco inicial no botao seguro: em exclusao (danger) um Enter apressado nao
// pode confirmar. Nas demais, no Confirmar -- salvo se estiver desabilitado.
usePrisaoDeFoco(container, () => props.isOpen, {
  focoInicial: () => (props.variant === 'danger' || props.loading
    ? botaoCancelar.value
    : botaoConfirmar.value)
});

// Escape cancela, com a mesma guarda dos chamadores: durante o loading nao
// fecha. Escuta na fase de captura e para a propagacao para que um Modal
// aberto por baixo nao feche junto no mesmo Escape.
const aoTeclarEscape = (e) => {
  if (e.key !== 'Escape') {
    return;
  }

  e.preventDefault();
  e.stopPropagation();

  if (!props.loading) {
    onCancel();
  }
};

watch(() => props.isOpen, (aberto) => {
  if (aberto) {
    document.addEventListener('keydown', aoTeclarEscape, true);
  } else {
    document.removeEventListener('keydown', aoTeclarEscape, true);
  }
}, { immediate: true });

onUnmounted(() => document.removeEventListener('keydown', aoTeclarEscape, true));
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.dialog-container {
  border-radius: 16px;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-bottom-width: 1px;
  border-bottom-style: solid;
  position: relative;
}

.dialog-header.variant-info {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.dialog-header.variant-warning {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
}

.dialog-header.variant-danger {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
}

.dialog-header.variant-success {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
}

.dialog-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.dialog-icon svg {
  width: 28px;
  height: 28px;
}

.dialog-title {
  flex: 1;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.dialog-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.dialog-close svg {
  width: 20px;
  height: 20px;
}

.dialog-body {
  padding: 1.5rem;
}

.dialog-message {
  font-size: 1rem;
  line-height: 1.5;
  margin: 0;
}

.dialog-description {
  font-size: 0.875rem;
  line-height: 1.5;
  margin-top: 0.75rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top-width: 1px;
  border-top-style: solid;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.btn-warning:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-spinner {
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

.spinner-circle {
  opacity: 0.25;
  stroke: currentColor;
  fill: none;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.dialog-enter-active,
.dialog-leave-active {
  transition: opacity 0.2s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
}

.dialog-enter-active .dialog-container,
.dialog-leave-active .dialog-container {
  transition: transform 0.2s ease;
}

.dialog-enter-from .dialog-container,
.dialog-leave-to .dialog-container {
  transform: scale(0.95);
}
</style>
