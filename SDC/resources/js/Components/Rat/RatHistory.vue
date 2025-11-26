<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <div class="relative pl-6 border-l border-slate-800 space-y-8 my-4">
      <div
        v-for="event in events"
        :key="event.id"
        class="relative"
      >
        <div
          :class="[
            'absolute -left-[31px] w-4 h-4 rounded-full mt-1 border',
            getEventTypeClass(event.tipo),
          ]"
        ></div>
        <p class="text-sm text-slate-500 mb-1">{{ event.data }}</p>
        <p class="text-slate-300">
          {{ event.descricao || event.titulo }}
          <span v-if="event.autor" class="text-blue-400">{{ event.autor }}</span>.
        </p>
      </div>
    </div>

    <div class="mt-6">
      <label class="text-sm text-slate-400 mb-2 block">Adicionar Observação</label>
      <textarea
        v-model="observationText"
        rows="3"
        class="w-full bg-slate-900 border border-slate-800 rounded p-3 text-sm text-slate-200 focus:border-blue-500 outline-none placeholder-slate-600"
        placeholder="Digite uma nova observação..."
      ></textarea>
      <div class="flex justify-end mt-2">
        <button
          @click="handleAddObservation"
          class="text-sm bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded transition-colors"
        >
          Registrar no Histórico
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['add-observation']);

const observationText = ref('');

function getEventTypeClass(tipo) {
  const classes = {
    criacao: 'bg-green-900/50 border-green-500/50',
    observacao: 'bg-blue-900/50 border-blue-500/50',
    alteracao: 'bg-yellow-900/50 border-yellow-500/50',
  };
  return classes[tipo] || 'bg-slate-900/50 border-slate-500/50';
}

function handleAddObservation() {
  if (observationText.value.trim()) {
    emit('add-observation', {
      texto: observationText.value,
    });
    observationText.value = '';
  }
}
</script>

