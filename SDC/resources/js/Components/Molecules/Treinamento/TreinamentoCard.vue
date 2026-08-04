<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import { computed } from 'vue';

const props = defineProps({
  treinamento: {
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
});

const emit = defineEmits(['view', 'edit', 'delete']);

const formatDate = (dateValue) => {
  if (!dateValue) return '—';
  const str = String(dateValue).trim();
  if (str.includes('/')) return str;
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return str;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

/**
 * Cor do status na semantica do Badge.
 *
 * Antes o template passava :color="treinamento.status_color" -- prop que o Badge nao
 * tem, com um campo que o backend do Treinamento nem produz. O badge caia no default
 * e saia sempre cinza, independente do status.
 */
const statusVariant = computed(() => ({
  PLANEJADO: 'info',
  EM_ANDAMENTO: 'warning',
  CONCLUIDO: 'success',
  CANCELADO: 'danger',
}[props.treinamento.status] ?? 'default'));

const vagasText = computed(() => {
  if (props.treinamento.numero_vagas === null) {
    return 'Vagas ilimitadas';
  }
  return `${props.treinamento.vagas_disponiveis || 0} de ${props.treinamento.numero_vagas} vagas`;
});
</script>

<template>
  <CardBase class="treinamento-card hover:shadow-lg transition-all cursor-pointer" @click="emit('view', treinamento.id)">
    <!-- Header com Badges -->
    <div class="flex justify-between items-start mb-3">
      <div class="flex-1">
        <Heading :level="3" class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-1">
          {{ treinamento.titulo }}
        </Heading>
      </div>
      <Badge :variant="statusVariant" class="ml-2">
        {{ treinamento.status_label || treinamento.status }}
      </Badge>
    </div>

    <!-- Descrição -->
    <Text v-if="treinamento.descricao" size="sm" color="muted" class="mb-4 line-clamp-2">
      {{ treinamento.descricao }}
    </Text>

    <!-- Informações principais -->
    <div class="space-y-2 mb-4">
      <div class="flex items-center gap-2 flex-wrap">
        <Badge :cor="treinamento.tipo_color || 'blue'">
          {{ treinamento.tipo_label || treinamento.tipo }}
        </Badge>
        <Badge v-if="treinamento.categoria_label" cor="slate">
          {{ treinamento.categoria_label }}
        </Badge>
        <Text size="sm" color="muted">
          {{ treinamento.carga_horaria || 0 }}h
        </Text>
      </div>

      <!-- Instrutor -->
      <div v-if="treinamento.instrutor" class="flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <Text size="sm">
          {{ treinamento.instrutor }}
        </Text>
      </div>

      <!-- Datas -->
      <div v-if="treinamento.data_inicio" class="flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <Text size="sm">
          {{ formatDate(treinamento.data_inicio) }}
          <span v-if="treinamento.data_fim"> até {{ formatDate(treinamento.data_fim) }}</span>
        </Text>
      </div>
    </div>

    <!-- Footer com estatísticas e ações -->
    <div class="flex justify-between items-center pt-3 border-t border-slate-200 dark:border-slate-700">
      <div class="flex gap-4">
        <Text size="xs" color="muted" class="flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ treinamento.total_inscricoes || 0 }} inscritos
        </Text>
        <Text size="xs" color="muted" class="flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ treinamento.total_modulos || 0 }} módulos
        </Text>
        <Text size="xs" color="muted">
          {{ vagasText }}
        </Text>
      </div>

      <!-- Ações -->
      <div class="flex gap-1" @click.stop>
        <ActionButton
          module="treinamento"
          resource="cursos"
          size="sm"
          :actions="[
            { action: 'view',   handler: () => emit('view', treinamento.id) },
            { action: 'edit',   handler: () => emit('edit', treinamento.id),   allowed: canEdit },
            { action: 'delete', handler: () => emit('delete', treinamento.id), allowed: canDelete },
          ]"
        />
      </div>
    </div>
  </CardBase>
</template>

<style scoped>
.treinamento-card {
  @apply bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
