<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <h2 class="text-lg font-medium text-slate-300">
        Recursos Empregados
      </h2>
      <button
        @click="$emit('add')"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded flex items-center gap-2 transition-colors w-full sm:w-auto justify-center"
      >
        <PlusIcon class="w-4 h-4" />
        Adicionar Recurso
      </button>
    </div>

    <div
      v-if="recursos.length === 0"
      class="bg-slate-800 border border-slate-700 rounded-lg p-10 text-center text-slate-500 flex flex-col items-center"
    >
      <TruckIcon class="w-12 h-12 mb-3 text-slate-700" />
      <p>Nenhum recurso adicionado a esta ocorrência.</p>
    </div>

    <div v-else class="grid gap-4">
      <div
        v-for="(rec, index) in recursos"
        :key="rec.id || index"
        class="bg-slate-800 border border-slate-700 p-4 rounded-lg flex justify-between items-center group hover:border-slate-600 transition-colors"
      >
        <div class="flex items-center gap-4 min-w-0 flex-1">
          <div class="bg-slate-900 p-2 rounded text-slate-400 flex-shrink-0">
            <TruckIcon class="w-5 h-5" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-medium text-slate-200 capitalize truncate">
              {{ rec.recurso_tipo || 'Recurso' }}
            </p>
            <p class="text-sm text-slate-500 truncate">{{ rec.descricao || 'Sem descrição' }}</p>
          </div>
        </div>
        <button
          @click="$emit('remove', rec.id || index)"
          class="text-slate-500 hover:text-red-400 p-2 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 ml-2"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import PlusIcon from '../Icons/PlusIcon.vue';
import TruckIcon from '../Icons/TruckIcon.vue';
import TrashIcon from '../Icons/TrashIcon.vue';

defineProps({
  recursos: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['add', 'remove']);
</script>

