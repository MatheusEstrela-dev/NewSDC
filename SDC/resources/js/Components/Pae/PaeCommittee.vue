<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up">
    <!-- Membros -->
    <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
          <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
          Membros do Comitê
        </h3>
        <button
          class="text-blue-400 hover:text-blue-300 text-sm font-medium flex items-center gap-1 transition-colors"
          @click="$emit('add-member')"
        >
          <PlusIcon class="w-4 h-4" />
          Adicionar
        </button>
      </div>
      <div class="p-6 space-y-3">
        <div
          v-for="membro in members"
          :key="membro.id"
          class="flex items-center justify-between p-4 bg-slate-900/50 rounded-lg border border-slate-700/30 hover:border-slate-600 transition-all"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 font-bold"
            >
              {{ membro.nome.charAt(0) }}
            </div>
            <div>
              <p class="text-white font-medium">{{ membro.nome }}</p>
              <p class="text-sm text-slate-400">{{ membro.orgao }}</p>
            </div>
          </div>
          <span :class="getRoleClass(membro.funcao)">{{ membro.funcao }}</span>
        </div>
      </div>
    </div>

    <!-- Atas -->
    <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
          <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
          Atas e Reuniões
        </h3>
        <button
          class="text-blue-400 hover:text-blue-300 text-sm font-medium flex items-center gap-1 transition-colors"
          @click="$emit('add-meeting')"
        >
          <PlusIcon class="w-4 h-4" />
          Nova Reunião
        </button>
      </div>
      <div class="p-6 space-y-4">
        <div
          v-for="ata in atas"
          :key="ata.id"
          class="p-4 bg-slate-900/50 rounded-lg border-l-4 border-blue-500 hover:bg-slate-800 transition-colors cursor-pointer group"
          @click="$emit('view-ata', ata)"
        >
          <div class="flex justify-between items-start">
            <p class="text-base font-medium text-white group-hover:text-blue-400 transition-colors">
              {{ ata.titulo }}
            </p>
            <span class="text-xs text-slate-500">{{ ata.data }}</span>
          </div>
          <p class="text-sm text-slate-400 mt-1">{{ ata.pauta }}</p>
          <div class="mt-3 flex items-center text-xs text-blue-400 font-medium">
            <DocumentTextIcon class="mr-1 w-3 h-3" />
            {{ ata.arquivo }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { getRoleClass } from '../../utils/roleColors';
import PlusIcon from '../Icons/PlusIcon.vue';
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';

defineProps({
  members: {
    type: Array,
    default: () => [],
  },
  atas: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['add-member', 'add-meeting', 'view-ata']);
</script>

