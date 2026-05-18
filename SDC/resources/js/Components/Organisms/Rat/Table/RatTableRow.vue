<template>
  <TableDataRow>
    <TableCell class="w-48 whitespace-nowrap">
      <Link
        :href="route('rat.show', rat.id)"
        class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors"
      >
        {{ rat.protocolo || rat.numero_bos || `RAT #${rat.id}` }}
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
    <TableCell class="w-auto whitespace-nowrap">
      {{ rat.municipio || rat.dados_gerais?.local_municipio || rat.local?.municipio || 'Não informado' }}
    </TableCell>
    <TableCell class="w-44 whitespace-nowrap">
      {{ rat.criado_por || 'Sistema' }}
    </TableCell>
    <TableCell align="right" class="w-56 min-w-56 whitespace-nowrap">
      <div class="flex justify-end">
        <TableActions
          :show-print="true"
          :show-edit="canEdit"
          :show-delete="canDelete"
          @view="handleView"
          @print="handlePrint"
          @edit="handleEdit"
          @attachments="handleAttachments"
          @delete="handleDelete"
        />
      </div>
    </TableCell>
  </TableDataRow>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Badge from '../../../Atoms/Badge/Badge.vue';
import StatusBadge from '../../../Atoms/Badge/StatusBadge.vue';
import TableCell from '../../../Atoms/Table/TableCell.vue';
import TableActions from '../../../Molecules/Table/TableActions.vue';
import TableDataRow from '../../../Molecules/Table/TableDataRow.vue';

const props = defineProps({
  rat: {
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

const emit = defineEmits(['view', 'print', 'edit', 'attachments', 'delete']);

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

function handlePrint() {
  emit('print', props.rat.id);
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


