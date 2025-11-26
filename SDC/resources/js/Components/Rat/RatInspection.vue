<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <div
      class="bg-purple-900/10 border border-purple-500/20 rounded-lg p-4 sm:p-6 mb-6"
    >
      <div class="flex gap-3">
        <ClipboardIcon
          class="w-5 h-5 text-purple-400 mt-0.5 flex-shrink-0"
        />
        <div>
          <h3 class="text-purple-300 font-medium">
            Relatório de Vistoria Técnica
          </h3>
          <p class="text-sm text-purple-400/70 mt-1">
            Preencha os dados abaixo referentes à avaliação estrutural e de risco do imóvel.
          </p>
        </div>
      </div>
    </div>

    <div
      class="bg-slate-800 border border-slate-700 rounded-lg divide-y divide-slate-800"
    >
      <div
        v-for="section in sections"
        :key="section.id"
        class="p-4 hover:bg-slate-800/30 cursor-pointer flex justify-between items-center transition-colors"
      >
        <span class="font-medium text-slate-300">{{ section.title }}</span>
        <ChevronDownIcon class="w-4 h-4 text-slate-500 flex-shrink-0" />
      </div>
    </div>
  </div>
</template>

<script setup>
import ClipboardIcon from '../Icons/ClipboardIcon.vue';
import ChevronDownIcon from '../Icons/ChevronDownIcon.vue';

defineProps({
  vistoria: {
    type: Object,
    default: () => ({}),
  },
});

defineEmits(['save']);

const sections = [
  { id: 1, title: '4.1 Identificação e Imóvel' },
  { id: 2, title: '4.6 Infraestrutura e Riscos' },
  { id: 3, title: '4.11 Patologias Identificadas' },
];
</script>

