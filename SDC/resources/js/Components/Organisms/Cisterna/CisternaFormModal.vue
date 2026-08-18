<template>
  <Modal :show="show" :max-width="maxWidth" @close="$emit('close')">
    <form @submit.prevent="$emit('submit')">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', TONS[tom] ?? TONS.info]">
          <component :is="icon" v-if="icon" class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ titulo }}</h2>
          <p v-if="subtitulo" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ subtitulo }}</p>
        </div>

        <button
          type="button"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          aria-label="Fechar"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="px-5 py-4">
        <slot />
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <button type="button" :class="BOTAO_SEC" @click="$emit('close')">Cancelar</button>
        <button type="submit" :class="BOTAO" :disabled="processando">
          {{ processando ? 'Salvando...' : rotuloEnvio }}
        </button>
      </footer>
    </form>
  </Modal>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';

/**
 * Casca de modal de formulario do modulo.
 *
 * Existe porque os quatro CRUDs de apoio -- comunidades, lotes, ordens de
 * servico e notificacoes -- tem a MESMA estrutura de modal e mudam so os campos.
 * No legado eram 11 views para essas quatro entidades, `create` e `edit`
 * separados, e ja tinham divergido entre si.
 *
 * Segue o cabecalho do CollapsibleSection (icone com tom, titulo, subtitulo) para
 * o modal nao parecer de outro sistema.
 */
defineProps({
  show: { type: Boolean, default: false },
  titulo: { type: String, required: true },
  subtitulo: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tom: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'danger', 'neutro'].includes(v),
  },
  maxWidth: { type: String, default: 'lg' },
  processando: { type: Boolean, default: false },
  rotuloEnvio: { type: String, default: 'Salvar' },
});

defineEmits(['close', 'submit']);

const TONS = {
  info: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
  success: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
  warning: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
  danger: 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
  neutro: 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
};

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';
</script>
