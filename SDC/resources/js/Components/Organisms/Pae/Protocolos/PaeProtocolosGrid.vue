<template>
  <div>
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <Badge variant="danger" size="sm">
        Processo Finalizado/Encerrado
      </Badge>
      <Badge variant="warning" size="sm">
        Prazo de Vencimento Próximo
      </Badge>
    </div>

    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
      <p class="mt-4 text-slate-600 dark:text-slate-400">Carregando...</p>
    </div>

    <ListEmptyState
      v-else-if="protocolos.length === 0"
      :icon="DocumentTextIcon"
      title="Nenhum protocolo encontrado"
      helper="Tente ajustar os filtros de busca"
    />

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <PaeProtocoloCard
        v-for="p in protocolos"
        :key="p.id"
        :protocolo="p"
        :can-edit="canEdit"
        :can-delete="canDelete"
        :can-atribuir="canAtribuir"
        :can-check="canCheck"
        :can-pdf="canPdf"
        :can-create="canCreate"
        @view="$emit('view', $event)"
        @print="$emit('print', $event)"
        @edit="$emit('edit', $event)"
        @history="$emit('history', $event)"
        @check="$emit('check', $event)"
        @pdf="$emit('pdf', $event)"
        @archive="$emit('archive', $event)"
        @delete="$emit('delete', $event)"
        @options="$emit('options', $event)"
        @assign="$emit('assign', $event)"
        @relate="$emit('relate', $event)"
      />
    </div>

  </div>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import PaeProtocoloCard from '@/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue';

const props = defineProps({
  protocolos: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    default: null,
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

defineEmits(['view', 'print', 'edit', 'history', 'check', 'pdf', 'archive', 'delete', 'options', 'assign', 'relate']);
</script>


