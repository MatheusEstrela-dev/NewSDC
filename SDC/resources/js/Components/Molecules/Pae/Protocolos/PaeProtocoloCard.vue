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
        <span class="font-semibold text-slate-600 dark:text-slate-300">Limite Analise:</span>
        <span>{{ protocolo.limiteAnalise }}</span>
        <PrazosPill :prazo="protocolo.prazo" :estimado="protocolo.prazoEstimado" class="ml-2" />
      </div>
      <div v-if="protocolo.comunicacoesPendentes" class="text-sm font-semibold text-amber-700 dark:text-amber-300">
        Comunicações pendentes: {{ protocolo.comunicacoesPendentes }}
      </div>
      <div v-if="protocolo.correcaoPrazoVencido" class="text-sm font-semibold text-red-700 dark:text-red-300">
        Correção transitória vencida: avaliação da CEDEC pendente
      </div>
      <div v-if="protocolo.dcoSituacao" class="text-sm font-medium" :class="['atrasada', 'nao_conforme'].includes(protocolo.dcoSituacao) ? 'text-red-700 dark:text-red-300' : 'text-slate-600 dark:text-slate-300'">
        DCO: {{ protocolo.dcoSituacao.replaceAll('_', ' ') }}
      </div>
      <div v-if="protocolo.evacuacaoSituacao" class="text-sm font-medium" :class="classeSituacaoEvacuacao(protocolo.evacuacaoSituacao)">
        Evacuação: {{ rotuloSituacaoEvacuacao(protocolo.evacuacaoSituacao) }}
      </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-2">
      <ActionButton
        module="pae"
        resource="protocolos"
        :actions="[
          { action: 'view',          handler: () => $emit('view', protocolo.id) },
          { action: 'print',         handler: () => $emit('print', protocolo.id) },
          { action: 'edit',          handler: () => $emit('edit', protocolo.id),          allowed: canEdit },
          { action: 'history',       aliasOverride: 'view', handler: () => $emit('history', protocolo.id) },
          { action: 'notifications', handler: () => $emit('notifications', protocolo.id) },
          { action: 'archive',       handler: () => $emit('archive', protocolo.id),       allowed: canDelete },
          { action: 'check',  placement: 'menu', handler: () => $emit('check', protocolo.id),  allowed: canCheck },
          { action: 'pdf',    placement: 'menu', handler: () => $emit('pdf', protocolo.id),    allowed: canPdf },
          { action: 'ficha',  placement: 'menu', aliasOverride: 'view', handler: () => $emit('ficha', protocolo.id) },
          { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'DCO', handler: () => $emit('dco', protocolo.id) },
          { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'Evacuação', handler: () => $emit('evacuacao', protocolo.id) },
          { action: 'assign', placement: 'menu', handler: () => $emit('assign', protocolo.id), allowed: canAtribuir && isAssignableStatus(protocolo.situacao) },
          { action: 'relate', placement: 'menu', handler: () => $emit('relate', protocolo.id), allowed: canCreate },
        ]"
      />
    </div>
  </CardBase>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';

import PrazosPill from './PrazosPill.vue';
import StatusPill from './StatusPill.vue';
import { isAssignableStatus } from '@/Composables/usePaeAssignableStatus';
import { classeSituacaoEvacuacao, rotuloSituacaoEvacuacao } from '@/utils/paeEvacuacao';

defineProps({
  protocolo: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canAtribuir: {
    type: Boolean,
    default: false,
  },
  canCheck: {
    type: Boolean,
    default: false,
  },
  canPdf: {
    type: Boolean,
    default: false,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['view', 'print', 'edit', 'history', 'notifications', 'check', 'pdf', 'ficha', 'dco', 'evacuacao', 'archive', 'options', 'assign', 'relate']);
</script>
