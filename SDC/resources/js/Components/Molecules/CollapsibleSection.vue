<template>
  <section
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow dark:border-slate-700/50 dark:bg-slate-900/40"
  >
    <div
      class="flex cursor-pointer select-none items-center gap-3 px-4 lg:px-6 py-3"
      role="button"
      :aria-expanded="estaExpandido"
      tabindex="0"
      @click="alternar"
      @keydown.enter="alternar"
      @keydown.space.prevent="alternar"
    >
      <div v-if="$slots.icon || icon" :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', TONS[tom] ?? TONS.info]">
        <slot name="icon">
          <component :is="icon" class="h-5 w-5" />
        </slot>
      </div>

      <div class="min-w-0 flex-1">
        <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ title }}</h3>
        <p v-if="subtitle" class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
      </div>

      <!-- Resumo quando recolhido: evita ter que abrir para saber o que tem dentro. -->
      <span v-if="!estaExpandido && statusText" class="mr-1 hidden text-xs text-slate-400 sm:block">
        {{ statusText }}
      </span>

      <ChevronDownIcon
        class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200"
        :class="{ 'rotate-180': estaExpandido }"
      />
    </div>

    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      leave-active-class="transition-all duration-200 ease-in"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-[3000px]"
      leave-from-class="opacity-100 max-h-[3000px]"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="estaExpandido" class="overflow-hidden border-t border-slate-200 px-4 lg:px-6 py-4 dark:border-slate-700/50">
        <slot />
      </div>
    </Transition>
  </section>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useCollapsibleSection } from '@/Composables/core/useCollapsibleSection';

/**
 * Secao colapsavel de formulario, no visual do modulo RAT.
 *
 * E a versao GENERICA do `Rat/Sections/RatCollapsibleSection`. Aquele depende de
 * `resources/css/pages/rat/sections.css`, carregado sob demanda so nas paginas do
 * RAT (ver loadPageCSS em app.js): usado em outro modulo, renderiza sem estilo
 * nenhum. Aqui e tudo utilitario Tailwind, entao funciona em qualquer pagina.
 */
const props = defineProps({
  /** Escopo do estado salvo. Um por modulo, para nao misturar telas. */
  namespace: { type: String, required: true },
  sectionId: { type: String, required: true },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  /** Componente de icone; o slot `icon` tem prioridade. */
  icon: { type: [Object, Function], default: null },
  tom: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'danger', 'neutro'].includes(v),
  },
  statusText: { type: String, default: '' },
  expandidoPorPadrao: { type: Boolean, default: true },
});

const TONS = {
  info: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
  success: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
  warning: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
  danger: 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
  neutro: 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
};

const { estaExpandido, alternar } = useCollapsibleSection(
  props.namespace,
  props.sectionId,
  { expandidoPorPadrao: props.expandidoPorPadrao },
);
</script>
