<template>
  <ListContainer
    title="Lista de Protocolos"
    :icon="ClipboardDocumentListIcon"
    :count="protocolos.length"
  >
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Protocolo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Empreendedor / Estrutura</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Analista</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Datas</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Situação</th>
            <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-44 min-w-44">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <!-- table-row-solid da fundo opaco a linha: a coluna de Acoes e fixa
               (table-actions-cell) e herda esse fundo, senao o conteudo rolado
               apareceria por baixo dela. -->
          <tr v-for="protocolo in protocolos" :key="protocolo.id" class="table-row-solid transition-colors">
            <!-- Protocolo -->
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white">#{{ protocolo.protocoloNumero }}</div>
            </td>

            <!-- Empreendedor / Estrutura -->
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white truncate max-w-[200px]">{{ protocolo.empreendedor }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">{{ protocolo.estrutura }}</div>
            </td>

            <!-- Analista -->
            <td class="px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300">{{ protocolo.analista }}</div>
            </td>

            <!-- Datas -->
            <td class="px-4 py-3">
              <div class="text-xs text-slate-600 dark:text-slate-400">
                <span class="font-medium">Entrada:</span> {{ protocolo.dataEntrada }}
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-2">
                <span class="font-medium">Limite:</span> {{ protocolo.limiteAnalise }}
                <PrazosPill :prazo="protocolo.prazo" :estimado="protocolo.prazoEstimado" class="scale-90 origin-left" />
              </div>
            </td>

            <!-- Situação -->
            <td class="px-4 py-3">
              <StatusPill :situacao="protocolo.situacao" />
              <div v-if="protocolo.comunicacoesPendentes" class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                {{ protocolo.comunicacoesPendentes }} comunicação(ões) pendente(s)
              </div>
              <div v-if="protocolo.correcaoPrazoVencido" class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300">
                Correção transitória vencida: avaliar
              </div>
              <div v-if="protocolo.dcoSituacao" class="mt-1 text-xs font-medium" :class="['atrasada', 'nao_conforme'].includes(protocolo.dcoSituacao) ? 'text-red-700 dark:text-red-300' : 'text-slate-600 dark:text-slate-300'">
                DCO: {{ protocolo.dcoSituacao.replaceAll('_', ' ') }}
              </div>
              <div v-if="protocolo.evacuacaoSituacao" class="mt-1 text-xs font-medium" :class="classeSituacaoEvacuacao(protocolo.evacuacaoSituacao)">
                Evacuação: {{ rotuloSituacaoEvacuacao(protocolo.evacuacaoSituacao) }}
              </div>
              <div v-if="protocolo.simuladoSituacao" class="mt-1 text-xs font-medium" :class="classeSituacaoSimulado(protocolo.simuladoSituacao)">
                Simulado: {{ rotuloSituacaoSimulado(protocolo.simuladoSituacao) }}
              </div>
            </td>

            <!-- Acoes -->
            <td class="table-actions-cell px-4 py-3 w-44 min-w-44">
              <div class="flex items-center justify-end">
                <ActionButton
                  module="pae"
                  resource="protocolos"
                  :actions="[
                    { action: 'view',    handler: () => $emit('view', protocolo.id) },
                    { action: 'edit',    handler: () => $emit('edit', protocolo.id),    allowed: canEdit },
                    { action: 'history', aliasOverride: 'view', handler: () => $emit('history', protocolo.id) },
                    { action: 'archive', handler: () => $emit('archive', protocolo.id) },
                    { action: 'delete',  handler: () => $emit('delete', protocolo.id),  allowed: canDelete },
                    { action: 'check',  placement: 'menu', handler: () => $emit('check', protocolo.id),  allowed: canCheck },
                    { action: 'pdf',    placement: 'menu', handler: () => $emit('pdf', protocolo.id),    allowed: canPdf },
                    { action: 'ficha',  placement: 'menu', aliasOverride: 'view', handler: () => $emit('ficha', protocolo.id) },
                    { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'DCO', handler: () => $emit('dco', protocolo.id) },
                    { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'Evacuação', handler: () => $emit('evacuacao', protocolo.id) },
                    { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'Simulados', handler: () => $emit('simulado', protocolo.id) },
                    { action: 'assign', placement: 'menu', handler: () => $emit('assign', protocolo.id), allowed: canAtribuir && isAssignableStatus(protocolo.situacao) },
                    { action: 'relate', placement: 'menu', handler: () => $emit('relate', protocolo.id), allowed: canCreate },
                  ]"
                />
              </div>
            </td>
          </tr>
          <tr v-if="protocolos.length === 0">
            <td colspan="6" class="p-0">
              <ListEmptyState title="Nenhum protocolo encontrado" />
            </td>
          </tr>
        </tbody>
      </table>
  </ListContainer>
</template>

<script setup>
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';
import StatusPill from '@/Components/Molecules/Pae/Protocolos/StatusPill.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { isAssignableStatus } from '@/Composables/usePaeAssignableStatus';
import { classeSituacaoEvacuacao, rotuloSituacaoEvacuacao } from '@/utils/paeEvacuacao';
import { classeSituacaoSimulado, rotuloSituacaoSimulado } from '@/utils/paeSimulado';

defineProps({
  protocolos: {
    type: Array,
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

defineEmits(['view', 'print', 'edit', 'history', 'check', 'pdf', 'ficha', 'dco', 'evacuacao', 'simulado', 'archive', 'delete', 'options', 'assign', 'relate']);
</script>
