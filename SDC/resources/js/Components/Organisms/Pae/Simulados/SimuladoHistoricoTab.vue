<template>
  <CollapsibleSection namespace="pae" section-id="simulado-historico" title="Relatórios e versões" :subtitle="`${relatorios.length} registro(s); a maior versão de cada simulado prevalece.`" :icon="ClockIcon" tom="neutro">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('novo')">Novo simulado</Button>
    </div>
    <p v-if="!relatorios.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhum relatório registrado.</p>
    <ul v-else class="space-y-3">
      <li v-for="item in relatorios" :key="item.id" class="rounded-lg border p-3 text-sm" :class="item.id === selecionadoId ? 'border-blue-500 dark:border-blue-400' : 'border-slate-200 dark:border-slate-700'">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <strong class="text-slate-900 dark:text-white">Simulado de {{ formatarData(item.dt_realizacao) }} · versão {{ item.versao }}</strong>
          <span class="flex flex-wrap items-center gap-2">
            <Badge v-if="item.vigente" variant="info" size="sm">Vigente</Badge>
            <Badge :variant="item.validado ? 'success' : 'danger'" size="sm">{{ item.validado ? 'Validado' : 'Não validado' }}</Badge>
          </span>
        </div>
        <p class="mt-1 text-slate-600 dark:text-slate-300">Nível {{ item.nivel_emergencia }} · apresentado {{ formatarData(item.dt_apresentacao) }} · SEI {{ item.num_sei }} · {{ item.registrador || 'Responsável não disponível' }}</p>
        <p v-if="item.integrado" class="mt-1 text-slate-600 dark:text-slate-300">Simulado integrado: {{ item.barragens_integradas }}</p>
        <p v-if="item.alerta_aviso" class="mt-1 text-amber-700 dark:text-amber-300">Aviso à CEDEC com {{ item.aviso_antecedencia_dias }} dia(s) de antecedência (mínimo de {{ minimoDias }}, Art. 94).</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <Button variant="outline" size="sm" @click="$emit('abrir', item)">{{ item.id === selecionadoId ? 'Aberto' : (item.vigente && !somenteLeitura ? 'Abrir para revisar' : 'Abrir') }}</Button>
          <a v-if="canView" :href="route('pae.protocolo.simulados.relatorios.download', [protocoloId, item.id])" class="font-semibold text-blue-700 underline dark:text-blue-300">Baixar PDF</a>
        </div>
      </li>
    </ul>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { formatarData } from '@/utils/paeTela';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
  relatorios: { type: Array, required: true },
  selecionadoId: { type: Number, default: null },
  protocoloId: { type: Number, required: true },
  somenteLeitura: { type: Boolean, default: false },
  canView: { type: Boolean, default: false },
  minimoDias: { type: Number, default: 7 },
});

defineEmits(['abrir', 'novo']);
</script>
