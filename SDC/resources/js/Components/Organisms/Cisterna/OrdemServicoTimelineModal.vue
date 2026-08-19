<template>
  <Modal :show="show" max-width="2xl" @close="$emit('close')">
    <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300">
        <ClockIcon class="h-5 w-5" />
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Historico da ordem</h2>
        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ ordem?.nome }}</p>
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

    <div class="max-h-[60vh] overflow-y-auto px-5 py-4">
      <p v-if="carregando" class="text-sm text-slate-500 dark:text-slate-400">Carregando...</p>

      <p v-else-if="erro" class="text-sm text-red-600 dark:text-red-400">{{ erro }}</p>

      <!--
        Vazio e comum e nao e erro: a trilha vem do audit_logs, e ordem importada
        do legado nao tem evento nenhum registrado -- o historico comeca a existir
        a partir do uso no NewSDC.
      -->
      <ListEmptyState
        v-else-if="eventos.length === 0"
        title="Nenhum evento registrado"
        helper="Ordens vindas do sistema antigo nao trazem historico: ele passa a ser gravado a partir do uso aqui."
      />

      <ol v-else class="space-y-0">
        <li v-for="(evento, indice) in eventos" :key="indice" class="flex gap-3">
          <div class="flex flex-col items-center">
            <span :class="['flex h-7 w-7 shrink-0 items-center justify-center rounded-full', TONS[evento.tipo] ?? TONS.ordem_servico]">
              <component :is="ICONES[evento.tipo] ?? ClipboardDocumentListIcon" class="h-4 w-4" />
            </span>
            <span v-if="indice < eventos.length - 1" class="my-1 w-0.5 flex-1 bg-slate-200 dark:bg-slate-700" />
          </div>

          <div class="flex-1 pb-4">
            <p class="text-sm text-slate-800 dark:text-slate-100">{{ evento.descricao }}</p>
            <p v-if="evento.beneficiario_nome" class="text-xs text-slate-500 dark:text-slate-400">
              {{ evento.beneficiario_nome }}
            </p>
            <p class="mt-0.5 text-xs text-slate-400">
              {{ dataHora(evento.data) }}<span v-if="evento.usuario"> — {{ evento.usuario }}</span>
            </p>
          </div>
        </li>
      </ol>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import {
  ClockIcon,
  XMarkIcon,
  ClipboardDocumentListIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

/**
 * Historico da ordem de servico.
 *
 * Em modal e buscando por fetch de proposito: a rota `timeline` devolve JSON, e
 * nao pagina Inertia. Navegar para ela com `router.visit` jogaria o usuario numa
 * tela de JSON cru.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  ordem: { type: Object, default: null },
});

defineEmits(['close']);

const TONS = {
  ordem_servico: 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
  beneficiario: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
};

const ICONES = {
  ordem_servico: ClipboardDocumentListIcon,
  beneficiario: UserPlusIcon,
};

const eventos = ref([]);
const carregando = ref(false);
const erro = ref('');

watch(
  () => [props.show, props.ordem?.id],
  async ([visivel, id]) => {
    if (!visivel || !id) return;

    carregando.value = true;
    erro.value = '';
    eventos.value = [];

    try {
      const resposta = await fetch(route('cisternas.ordens-servico.timeline', id), {
        headers: { Accept: 'application/json' },
      });

      if (!resposta.ok) {
        erro.value = 'Nao foi possivel carregar o historico.';

        return;
      }

      const dados = await resposta.json();

      eventos.value = Array.isArray(dados) ? dados : (dados.data ?? []);
    } catch {
      erro.value = 'Falha de rede ao carregar o historico.';
    } finally {
      carregando.value = false;
    }
  },
  { immediate: true },
);

function dataHora(iso) {
  if (!iso) return '';

  const data = new Date(iso);

  return Number.isNaN(data.getTime())
    ? String(iso)
    : data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
