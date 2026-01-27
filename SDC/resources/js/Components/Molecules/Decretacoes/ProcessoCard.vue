<template>
  <CardBase
    variant="hover"
    padding="md"
    class="cursor-pointer transition-all duration-200 hover:scale-[1.01] touch-manipulation sm:p-5"
    @click="$emit('click')"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-3 sm:mb-4">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 sm:gap-2 mb-1.5 sm:mb-2 flex-wrap">
          <TipoProcessoBadge :tipo="processo.processo" />
          <StatusBadge :status="processo.status" />
        </div>
        <Heading :level="4" color="default" class="mb-0.5 sm:mb-1 text-sm sm:text-base truncate">
          {{ processo.tipo_desastre_nome || 'Desastre não especificado' }}
        </Heading>
        <Text size="sm" color="muted" class="text-xs sm:text-sm hidden sm:block">
          COBRADE: {{ processo.tipo_desastre_cobrade || '—' }}
        </Text>
      </div>
      <PrazoBadge
        :dias-restantes="processo.dias_restantes"
        :data-vencimento="processo.data_vencimento"
        class="flex-shrink-0"
      />
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 gap-2 sm:gap-4 mb-3 sm:mb-4">
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Protocolo FIDE</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm truncate">{{ processo.n_protocolo_fide || '—' }}</Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Data Entrada</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm">{{ formatDate(processo.data_entrada) }}</Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Analista</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm truncate">{{ processo.analista || '—' }}</Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Municípios</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm">{{ municipiosCount }}</Text>
      </div>
    </div>

    <!-- Municípios (se ESTADUAL) -->
    <div v-if="processo.processo === 'ESTADUAL' && processo.municipios?.length" class="mb-3 sm:mb-4 hidden sm:block">
      <Text size="xs" color="muted" class="mb-1.5 sm:mb-2">Municípios Afetados:</Text>
      <div class="flex flex-wrap gap-1.5 sm:gap-2">
        <span
          v-for="municipio in processo.municipios.slice(0, 3)"
          :key="municipio.id"
          class="px-2 py-0.5 sm:py-1 rounded bg-slate-700/30 dark:bg-slate-700/30 bg-slate-200 text-xs text-slate-300 dark:text-slate-300 text-slate-700"
        >
          {{ municipio.nome }}
        </span>
        <span
          v-if="processo.municipios.length > 3"
          class="px-2 py-0.5 sm:py-1 rounded bg-slate-700/30 dark:bg-slate-700/30 bg-slate-200 text-xs text-slate-400 dark:text-slate-400 text-slate-600"
        >
          +{{ processo.municipios.length - 3 }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-1 sm:gap-2 pt-3 sm:pt-4 border-t border-slate-700/30 dark:border-slate-700/30 border-slate-200">
      <TableActions
        :show-view="true"
        :show-print="true"
        :show-edit="canEdit"
        :show-attachments="false"
        :show-delete="false"
        @view="$emit('view', processo.id)"
        @print="$emit('print', processo.id)"
        @edit="$emit('edit', processo.id)"
      />
    </div>
  </CardBase>
</template>

<script setup>
import { computed } from 'vue';
import CardBase from '../../Atoms/Card/CardBase.vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import Text from '../../Atoms/Typography/Text.vue';
import StatusBadge from './StatusBadge.vue';
import PrazoBadge from './PrazoBadge.vue';
import TipoProcessoBadge from './TipoProcessoBadge.vue';
import TableActions from '../../Molecules/Table/TableActions.vue';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['click', 'view', 'print', 'edit']);

const municipiosCount = computed(() => {
  if (!props.processo.municipios?.length) return '—';
  return props.processo.municipios.length;
});

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('pt-BR');
};
</script>
