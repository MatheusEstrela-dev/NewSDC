<template>
  <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 p-6 animate-fade-in-up">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
      <h3 class="text-xl font-semibold text-white">Histórico de Eventos</h3>
      <button
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
        @click="$emit('filter-change', 'all')"
      >
        Mostrar Todos
      </button>
    </div>

    <ol v-if="events && events.length > 0" class="relative border-l border-slate-700 ml-4 space-y-8">
      <li
        v-for="event in events"
        :key="event.id"
        class="ml-8 relative group"
      >
        <span
          :class="[
            'absolute flex items-center justify-center w-8 h-8 rounded-full -left-12 ring-8 ring-slate-900 transition-transform group-hover:scale-110',
            getEventColorClass(event.tipo),
          ]"
        >
          <component :is="getEventIcon(event.tipo)" class="w-4 h-4" />
        </span>
        <div
          class="bg-slate-800/50 p-4 rounded-lg border border-slate-700 hover:border-slate-600 transition-all cursor-pointer"
          @click="$emit('view-event', event)"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2">
            <h3 class="flex items-center text-lg font-semibold text-white">
              {{ event.titulo }}
              <span
                v-if="event.id === 1"
                class="ml-3 px-2 py-0.5 rounded text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/20"
              >
                Novo
              </span>
            </h3>
            <time class="text-sm text-slate-500 font-mono">{{ event.data }}</time>
          </div>
          <p class="text-slate-300 text-sm mb-3 leading-relaxed">{{ event.descricao }}</p>
          <div
            v-if="event.protocolo || event.autor"
            class="flex flex-wrap items-center gap-2 text-xs text-slate-500"
          >
            <span v-if="event.protocolo" class="bg-slate-700/50 px-2.5 py-1 rounded font-mono border border-slate-600/50">
              Prot: {{ event.protocolo }}
            </span>
            <span v-if="event.autor" class="text-slate-400">
              Por: <span class="text-slate-300 font-medium">{{ event.autor }}</span>
            </span>
          </div>
        </div>
      </li>
    </ol>
    <div v-else class="text-center py-12 text-slate-400">
      <p>Nenhum evento encontrado no histórico.</p>
    </div>
  </div>
</template>

<script setup>
import { getEventColorClass, getEventIcon } from '../../utils/eventColors';

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter-change', 'view-event']);
</script>

