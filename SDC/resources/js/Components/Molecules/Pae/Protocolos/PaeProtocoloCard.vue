<template>
  <CardBase variant="default" padding="lg">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <Heading :level="5" color="default" class="mb-0 truncate">
            Protocolo #
          </Heading>
          <Text class="font-mono text-slate-700 dark:text-slate-200 truncate">{{ protocolo.protocoloNumero }}</Text>
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
      <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
        <BuildingOfficeIcon class="w-4 h-4 text-slate-500 dark:text-slate-400" />
        <span class="font-semibold text-slate-600 dark:text-slate-300">Empreendedor:</span>
        <span class="truncate">{{ protocolo.empreendedor }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
        <UsersIcon class="w-4 h-4 text-slate-500 dark:text-slate-400" />
        <span class="font-semibold text-slate-600 dark:text-slate-300">Analista:</span>
        <span class="truncate">{{ protocolo.analista }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
        <CalendarIcon class="w-4 h-4 text-slate-500 dark:text-slate-400" />
        <span class="font-semibold text-slate-600 dark:text-slate-300">Data Entrada:</span>
        <span>{{ protocolo.dataEntrada }}</span>
      </div>
      <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
        <ClockIcon class="w-4 h-4 text-slate-500 dark:text-slate-400" />
        <span class="font-semibold text-slate-600 dark:text-slate-300">Limite Análise:</span>
        <span>{{ protocolo.limiteAnalise }}</span>
        <PrazosPill :prazo="protocolo.prazo" class="ml-2" />
      </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <ButtonIcon
          :icon="EyeIcon"
          variant="primary"
          size="md"
          title="Visualizar"
          @click="$emit('view', protocolo.id)"
        />
        <ButtonIcon
          :icon="PrinterIcon"
          variant="info"
          size="md"
          title="Imprimir"
          @click="$emit('print', protocolo.id)"
        />
        <ButtonIcon
          :icon="PencilIcon"
          variant="warning"
          size="md"
          title="Editar"
          @click="$emit('edit', protocolo.id)"
        />
        <ButtonIcon
          :icon="ClockIcon"
          variant="success"
          size="md"
          title="Série Histórica"
          @click="$emit('history', protocolo.id)"
        />
        <ButtonIcon
          :icon="BellIcon"
          variant="secondary"
          size="md"
          title="Notificacoes"
          @click="$emit('notifications', protocolo.id)"
        />
        <ButtonIcon
          :icon="ArchiveBoxIcon"
          variant="topaz"
          size="md"
          title="Arquivar"
          @click="$emit('archive', protocolo.id)"
        />
      </div>
    </div>
  </CardBase>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import EyeIcon from '@/Components/Icons/EyeIcon.vue';
import PrinterIcon from '@/Components/Icons/PrinterIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import BellIcon from '@/Components/Icons/BellIcon.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
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

defineEmits(['view', 'print', 'edit', 'history', 'notifications', 'archive']);
</script>


