<template>
  <TableDataRow>
    <TableCell class="w-48 whitespace-nowrap">
      <Link
        :href="route('rat.show', rat.id)"
        class="text-blue-400 hover:text-blue-300 font-medium transition-colors"
      >
        {{ rat.protocolo || `RAT #${rat.id}` }}
      </Link>
    </TableCell>
    <TableCell class="w-44 whitespace-nowrap">
      {{ formatDateTime(rat.created_at) }}
    </TableCell>
    <TableCell align="center" class="w-24 whitespace-nowrap">
      <Badge variant="info" size="sm">
        {{ getYear(rat.created_at) }}
      </Badge>
    </TableCell>
    <TableCell align="center" class="w-36 whitespace-nowrap">
      <StatusBadge :status="rat.status" />
    </TableCell>
    <TableCell class="w-56 whitespace-nowrap">
      {{ rat.local?.municipio || 'Não informado' }}
    </TableCell>
    <TableCell class="w-44 whitespace-nowrap">
      {{ rat.criado_por || 'Sistema' }}
    </TableCell>
    <TableCell align="right" class="w-28 whitespace-nowrap">
      <TableActions
        @view="handleView"
        @edit="handleEdit"
        @attachments="handleAttachments"
        @delete="handleDelete"
      />
    </TableCell>
  </TableDataRow>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import TableDataRow from '../../../Molecules/Table/TableDataRow.vue';
import TableCell from '../../../Atoms/Table/TableCell.vue';
import TableActions from '../../../Molecules/Table/TableActions.vue';
import Badge from '../../../Atoms/Badge/Badge.vue';
import StatusBadge from '../../../Atoms/Badge/StatusBadge.vue';

const props = defineProps({
  rat: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['view', 'edit', 'attachments', 'delete']);

function formatDateTime(date) {
  if (!date) return 'Data não informada';
  const d = new Date(date);
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getYear(date) {
  if (!date) return new Date().getFullYear();
  return new Date(date).getFullYear();
}

function handleView() {
  emit('view', props.rat.id);
}

function handleEdit() {
  emit('edit', props.rat.id);
}

function handleAttachments() {
  emit('attachments', props.rat.id);
}

function handleDelete() {
  emit('delete', props.rat.id);
}
</script>

