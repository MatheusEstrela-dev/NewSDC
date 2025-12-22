<template>
  <CardBase variant="default" padding="lg" class="bg-slate-800/90 border-slate-700/50">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <Heading level="5" color="white" class="mb-0 truncate">
            Protocolo #
          </Heading>
          <Text class="font-mono text-slate-200 truncate">{{ protocolo.protocoloNumero }}</Text>
        </div>
        <Text size="sm" color="muted" class="mt-1">
          {{ protocolo.estrutura }}
        </Text>
      </div>

      <div class="flex items-center gap-2">
        <StatusPill :situacao="protocolo.situacao" />
      </div>
    </div>

    <div class="space-y-2 text-sm">
      <div class="flex items-center gap-2 text-slate-200">
        <BuildingOfficeIcon class="w-4 h-4 text-slate-400" />
        <span class="font-semibold text-slate-300">Empreendedor:</span>
        <span class="truncate">{{ protocolo.empreendedor }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-200">
        <UsersIcon class="w-4 h-4 text-slate-400" />
        <span class="font-semibold text-slate-300">Analista:</span>
        <span class="truncate">{{ protocolo.analista }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-200">
        <CalendarIcon class="w-4 h-4 text-slate-400" />
        <span class="font-semibold text-slate-300">Data Entrada:</span>
        <span>{{ protocolo.dataEntrada }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-200">
        <ClockIcon class="w-4 h-4 text-slate-400" />
        <span class="font-semibold text-slate-300">Limite Análise:</span>
        <span>{{ protocolo.limiteAnalise }}</span>
        <PrazosPill :prazo="protocolo.prazo" class="ml-2" />
      </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="p-2 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all"
          title="Visualizar"
          @click="$emit('view', protocolo.id)"
        >
          <EyeIcon class="w-5 h-5" />
        </button>
        <button
          type="button"
          class="p-2 rounded-lg text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-all"
          title="Editar"
          @click="$emit('edit', protocolo.id)"
        >
          <PencilIcon class="w-5 h-5" />
        </button>
        <button
          type="button"
          class="p-2 rounded-lg text-cyan-400 hover:text-cyan-300 hover:bg-cyan-500/10 transition-all"
          title="Série Histórica"
          @click="$emit('history', protocolo.id)"
        >
          <ClockIcon class="w-5 h-5" />
        </button>
        <button
          type="button"
          class="p-2 rounded-lg text-yellow-400 hover:text-yellow-300 hover:bg-yellow-500/10 transition-all"
          title="Notificações"
          @click="$emit('history', protocolo.id)"
        >
          <BellIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Removido: badges pequenos de contagem (análises/notificações) a pedido do usuário -->
    </div>
  </CardBase>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import EyeIcon from '@/Components/Icons/EyeIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import BellIcon from '@/Components/Icons/BellIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

import StatusPill from './StatusPill.vue';
import PrazosPill from './PrazosPill.vue';

const props = defineProps({
  protocolo: {
    type: Object,
    required: true,
  },
});

defineEmits(['view', 'edit', 'history']);
</script>


