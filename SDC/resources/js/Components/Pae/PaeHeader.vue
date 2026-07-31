<template>
  <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 md:mb-8 gap-4">
    <div class="flex-1 min-w-0">
      <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight break-words">Ficha do Empreendimento</h1>
      <p class="text-base sm:text-lg md:text-xl text-blue-400 font-light mt-1 flex flex-wrap items-center gap-2">
        <span class="break-words">{{ empreendimento.nome }}</span>
        <span
          :class="[
            'px-2 py-0.5 rounded text-xs font-bold border whitespace-nowrap',
            getNivelEmergenciaClass(empreendimento.nivelEmergencia),
          ]"
        >
          Nível de Emergência {{ empreendimento.nivelEmergencia }}
        </span>
      </p>
    </div>
    <div class="text-left md:text-right flex-shrink-0">
      <span class="text-xs text-slate-500 uppercase tracking-wider font-bold block mb-1">
        Protocolo
      </span>
      <span v-if="protocolo" class="text-lg font-mono font-bold text-slate-700 dark:text-slate-200">
        #{{ protocolo.num_protocolo }}
      </span>
      <span v-else class="text-sm text-slate-400 font-mono">—</span>
      <span
        v-if="protocolo?.status"
        :class="['ml-2 px-2 py-0.5 rounded text-xs font-bold border', statusClass]"
      >
        {{ statusLabel }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const STATUS_LABELS = {
    novo:               'Novo',
    entrada_processo:   'Entrada do Processo',
    criacao_sdc:        'Criação no SDC',
    gerenciamento:      'Gerenciamento',
    notificacao:        'Notificação',
    analise:            'Análise',
    aprovado:           'Aprovado',
    reprovado:          'Reprovado',
    ccpae:              'CCPAE',
    ativo_3_anos:       'Ativo (3 anos)',
    suspenso:           'Suspenso',
    revogado:           'Revogado',
    esperar_tratativa:  'Aguardando Tratativa',
    dilacao:            'Dilatação',
};

const STATUS_CLASSES = {
    novo:               'bg-slate-100 dark:bg-slate-500/10 text-slate-400 border-slate-300 dark:border-slate-500/20',
    entrada_processo:   'bg-blue-100 dark:bg-blue-500/10 text-blue-400 border-blue-300 dark:border-blue-500/20',
    criacao_sdc:        'bg-blue-100 dark:bg-blue-500/10 text-blue-400 border-blue-300 dark:border-blue-500/20',
    gerenciamento:      'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-500/20',
    notificacao:        'bg-amber-100 dark:bg-amber-500/10 text-amber-400 border-amber-300 dark:border-amber-500/20',
    analise:            'bg-indigo-100 dark:bg-indigo-500/10 text-indigo-400 border-indigo-300 dark:border-indigo-500/20',
    aprovado:           'bg-green-100 dark:bg-green-500/10 text-green-400 border-green-300 dark:border-green-500/20',
    reprovado:          'bg-red-100 dark:bg-red-500/10 text-red-400 border-red-300 dark:border-red-500/20',
    ccpae:              'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-400 border-emerald-300 dark:border-emerald-500/20',
    ativo_3_anos:       'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-400 border-emerald-300 dark:border-emerald-500/20',
    suspenso:           'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-400 border-yellow-300 dark:border-yellow-500/20',
    revogado:           'bg-red-100 dark:bg-red-700/10 text-red-400 border-red-300 dark:border-red-700/20',
    esperar_tratativa:  'bg-orange-100 dark:bg-orange-500/10 text-orange-400 border-orange-300 dark:border-orange-500/20',
    dilacao:            'bg-orange-100 dark:bg-orange-500/10 text-orange-400 border-orange-300 dark:border-orange-500/20',
};

const props = defineProps({
    empreendimento: {
        type: Object,
        required: true,
    },
    protocolo: {
        type: Object,
        default: null,
    },
});

const statusLabel = computed(() =>
    props.protocolo?.status ? (STATUS_LABELS[props.protocolo.status] ?? props.protocolo.status) : ''
);

const statusClass = computed(() =>
    props.protocolo?.status ? (STATUS_CLASSES[props.protocolo.status] ?? 'bg-slate-100 dark:bg-slate-500/10 text-slate-400 border-slate-300 dark:border-slate-500/20') : ''
);

function getNivelEmergenciaClass(nivel) {
    const classes = {
        1: 'bg-red-100 dark:bg-red-500/10 text-red-400 border-red-300 dark:border-red-500/20',
        2: 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-400 border-yellow-300 dark:border-yellow-500/20',
        3: 'bg-green-100 dark:bg-green-500/10 text-green-400 border-green-300 dark:border-green-500/20',
    };
    return classes[nivel] || 'bg-slate-100 dark:bg-slate-500/10 text-slate-400 border-slate-300 dark:border-slate-500/20';
}
</script>

