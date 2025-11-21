<template>
  <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
    <div>
      <h1 class="text-3xl font-bold text-white tracking-tight">Ficha do Empreendimento</h1>
      <p class="text-xl text-blue-400 font-light mt-1 flex items-center gap-2">
        {{ empreendimento.nome }}
        <span
          :class="[
            'px-2 py-0.5 rounded text-xs font-bold border',
            getNivelEmergenciaClass(empreendimento.nivelEmergencia),
          ]"
        >
          Nível de Emergência {{ empreendimento.nivelEmergencia }}
        </span>
      </p>
    </div>
    <div class="text-right hidden md:block">
      <span class="text-xs text-slate-500 uppercase tracking-wider font-bold block mb-1">
        Última Atualização
      </span>
      <span class="text-sm text-slate-300 font-mono">{{ lastUpdate }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDateTime } from '../../utils/dateFormatter';

const props = defineProps({
  empreendimento: {
    type: Object,
    required: true,
  },
  lastUpdate: {
    type: String,
    default: null,
  },
});

const lastUpdate = computed(() => {
  return props.lastUpdate || formatDateTime(new Date());
});

function getNivelEmergenciaClass(nivel) {
  const classes = {
    1: 'bg-red-500/10 text-red-400 border-red-500/20',
    2: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    3: 'bg-green-500/10 text-green-400 border-green-500/20',
  };

  return classes[nivel] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
}
</script>

