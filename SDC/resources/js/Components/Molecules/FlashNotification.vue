<template>
  <Teleport to="body">
    <Transition name="flash-slide">
      <div
        v-if="visible && message"
        :class="containerClass"
        role="alert"
        aria-live="polite"
      >
        <div class="flex items-start gap-3">
          <component :is="iconComponent" class="w-5 h-5 mt-0.5 shrink-0" />
          <!-- min-w-0 + break-words: sem eles uma placa ou URL longa nao quebra
               e empurra o botao de fechar para fora do cartao. -->
          <p class="min-w-0 flex-1 break-words text-sm font-medium leading-snug">{{ message }}</p>
          <button
            @click="dismiss"
            class="shrink-0 opacity-70 hover:opacity-100 transition-opacity"
            aria-label="Fechar"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
        <!-- barra de progresso -->
        <div :class="progressClass" :style="{ width: progress + '%' }" />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

// ícones inline para não depender de imports externos
const CheckCircleIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
};
const XCircleIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
};
const ExclamationIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>`,
};
const InformationCircleIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>`,
};
const XMarkIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>`,
};

const DURATION = 5000;

const page   = usePage();
const visible = ref(false);
const message = ref('');
const type    = ref('success');
const progress = ref(100);

let timer    = null;
let rafId    = null;
let startTime = null;

const typeConfig = {
  success: {
    container: 'bg-emerald-600 text-white border border-emerald-500',
    progress:  'bg-emerald-400/60',
    icon: CheckCircleIcon,
  },
  error: {
    container: 'bg-red-600 text-white border border-red-500',
    progress:  'bg-red-400/60',
    icon: XCircleIcon,
  },
  warning: {
    container: 'bg-amber-500 text-white border border-amber-400',
    progress:  'bg-amber-300/60',
    icon: ExclamationIcon,
  },
  info: {
    container: 'bg-blue-600 text-white border border-blue-500',
    progress:  'bg-blue-400/60',
    icon: InformationCircleIcon,
  },
};

// Ancorada no topo-direita (top-20 = 5rem: mesma folga do header usada pelo
// ToastContainer), nao mais no rodape.
/**
 * Posicao do toast.
 *
 * NAO usar `w-full` junto de `right-6`: num celular de 375px isso da um toast de
 * 375px ancorado a 24px da direita, ou seja, com a borda esquerda em -24px --
 * o texto aparecia cortado ("have retirada" em vez de "Chave retirada"). O
 * `max-w-sm` mascarava o defeito no desktop, onde 384px cabem folgados.
 *
 * Celular: preso aos dois lados (left-3 right-3), largura resolvida pelos
 * insets, e ancorado embaixo -- em cima ele cobria o cabecalho e a trilha de
 * navegacao, e embaixo fica na area do polegar. O safe-area evita a barra de
 * gestos do iOS.
 *
 * Desktop (sm+): volta a ser um cartao no topo direito.
 */
const containerClass = computed(() => [
  'fixed z-[9999] rounded-xl shadow-2xl px-4 py-3 overflow-hidden',
  'left-3 right-3 bottom-[calc(1rem+env(safe-area-inset-bottom))]',
  'sm:left-auto sm:bottom-auto sm:top-20 sm:right-6 sm:w-full sm:max-w-sm',
  typeConfig[type.value]?.container ?? typeConfig.success.container,
]);

const progressClass = computed(() => [
  'absolute bottom-0 left-0 h-1 transition-none rounded-full',
  typeConfig[type.value]?.progress ?? typeConfig.success.progress,
]);

const iconComponent = computed(() => typeConfig[type.value]?.icon ?? CheckCircleIcon);

function showNotification(msg, t = 'success') {
  clearAll();
  message.value  = msg;
  type.value     = t;
  visible.value  = true;
  progress.value = 100;
  startTime = performance.now();
  animateProgress();
  timer = setTimeout(dismiss, DURATION);
}

function animateProgress() {
  rafId = requestAnimationFrame(() => {
    if (!visible.value) return;
    const elapsed  = performance.now() - startTime;
    progress.value = Math.max(0, 100 - (elapsed / DURATION) * 100);
    if (progress.value > 0) animateProgress();
  });
}

function dismiss() {
  clearAll();
  visible.value = false;
}

function clearAll() {
  clearTimeout(timer);
  cancelAnimationFrame(rafId);
  timer = null;
  rafId = null;
}

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return;
    if (flash.success) showNotification(flash.success, 'success');
    else if (flash.error)   showNotification(flash.error,   'error');
    else if (flash.warning) showNotification(flash.warning, 'warning');
    else if (flash.info)    showNotification(flash.info,    'info');
  },
  { deep: true, immediate: true },
);

onUnmounted(clearAll);
</script>

<style scoped>
.flash-slide-enter-active,
.flash-slide-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
/* Celular: o toast mora embaixo, entao entra e sai por baixo. */
.flash-slide-enter-from,
.flash-slide-leave-to {
  opacity: 0;
  transform: translateY(1rem) scale(0.96);
}

/* Desktop (sm+): volta ao topo direito e a animacao acompanha, entrando por
   cima. Mesmo ponto de corte do `sm:` das classes de posicao. */
@media (min-width: 640px) {
  .flash-slide-enter-from,
  .flash-slide-leave-to {
    transform: translateY(-1rem) scale(0.96);
  }
}
</style>
