<template>
  <Modal :show="open" max-width="2xl" @close="$emit('close')">
    <div class="bg-slate-900 text-slate-200">
      <div class="px-6 py-5 bg-gradient-to-r from-indigo-700/70 to-fuchsia-600/40 border-b border-slate-700/50">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-slate-900/40 border border-slate-700/40 flex items-center justify-center">
              <ClockIcon class="w-5 h-5 text-slate-200" />
            </div>
            <div class="min-w-0">
              <h3 class="text-lg font-semibold text-white truncate">Série Histórica do Cronograma</h3>
              <p class="text-sm text-slate-200/80 truncate">
                Cronograma
                <span class="font-mono">{{ numero || '—' }}</span>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="p-2 rounded-lg text-slate-200/80 hover:text-white hover:bg-white/10 transition-all"
            title="Fechar"
            @click="$emit('close')"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <div class="p-6">
        <div v-if="loading" class="text-center py-10 text-slate-400">
          Carregando histórico...
        </div>

        <ol v-else-if="timelineCount" class="relative border-l border-slate-700 ml-5 space-y-6">
          <li v-for="event in timeline" :key="event.id" class="ml-8 relative">
            <span
              class="absolute flex items-center justify-center w-9 h-9 rounded-full -left-12 ring-8 ring-slate-900"
              :class="eventColor(event.tipo)"
            >
              <component :is="eventIcon(event.tipo)" class="w-4 h-4" />
            </span>

            <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-4">
              <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                  <h4 class="text-base font-semibold text-white truncate">{{ event.titulo }}</h4>
                  <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                    <Badge :variant="eventBadgeVariant(event.tipo)" size="sm">{{ eventLabel(event.tipo) }}</Badge>
                    <span class="font-mono text-slate-400">{{ event.data }}</span>
                  </div>
                </div>
              </div>

              <p v-if="event.descricao" class="text-sm text-slate-300 leading-relaxed">{{ event.descricao }}</p>

              <div class="mt-3 inline-flex items-center gap-2 bg-slate-900/40 border border-slate-700/40 px-3 py-1 rounded-full text-xs">
                <UsersIcon class="w-4 h-4 text-slate-400" />
                <span class="text-slate-400">Responsável:</span>
                <span class="text-slate-200 font-semibold">{{ event.responsavel || '—' }}</span>
              </div>
            </div>
          </li>
        </ol>

        <div v-else class="text-center py-10 text-slate-400">
          Nenhum evento registrado.
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';

const props = defineProps({
  open:     { type: Boolean, default: false },
  numero:   { type: [String, Number], default: '' },
  timeline: { type: Array, default: () => [] },
  loading:  { type: Boolean, default: false },
});

defineEmits(['close']);

const timelineCount = computed(() => props.timeline?.length || 0);

function eventLabel(tipo) {
  const map = { edicao: 'Evento', criacao: 'Criação', arquivo: 'Arquivamento' };
  return map[tipo] || 'Evento';
}

function eventBadgeVariant(tipo) {
  const map = { edicao: 'warning', criacao: 'success', arquivo: 'info' };
  return map[tipo] || 'default';
}

function eventColor(tipo) {
  const map = {
    edicao: 'bg-amber-500/90 text-white',
    criacao: 'bg-blue-600/90 text-white',
    arquivo: 'bg-cyan-500/90 text-white',
  };
  return map[tipo] || 'bg-slate-600 text-white';
}

function eventIcon(tipo) {
  const map = {
    edicao: PencilIcon,
    criacao: CheckCircleIcon,
    arquivo: DocumentTextIcon,
  };
  return map[tipo] || ExclamationTriangleIcon;
}
</script>
