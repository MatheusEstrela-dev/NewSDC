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
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-400 dark:border-blue-400 border-blue-600"></div>
      <p class="mt-4 text-slate-400 dark:text-slate-400 text-slate-600">Carregando...</p>
    </div>

    <div v-else-if="protocolos.length === 0" class="p-12 text-center">
      <DocumentTextIcon class="w-12 h-12 text-slate-600 dark:text-slate-600 text-slate-400 mx-auto mb-4" />
      <Heading :level="4" color="muted">Nenhum protocolo encontrado</Heading>
      <Text size="sm" color="muted" class="mt-2">
        Tente ajustar os filtros de busca
      </Text>
    </div>

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
      />
    </div>

  </div>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
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
});

defineEmits(['view', 'print', 'edit', 'history', 'check', 'pdf', 'archive', 'delete', 'options', 'assign']);
</script>


