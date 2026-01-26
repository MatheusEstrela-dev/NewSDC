<template>
  <TableDataRow>
    <TableCell class="w-48 whitespace-nowrap">
      <Link
        :href="route('rat.show', rat.id)"
        class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors"
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
    <TableCell align="right" class="w-36 whitespace-nowrap">
      <TableActions
        :show-print="true"
        @view="handleView"
        @print="handlePrint"
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

// #region agent log
function handlePrint() {
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'RatTableRow.vue:handlePrint',message:'Print button clicked',data:{ratId:props.rat.id,protocolo:props.rat.protocolo},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
  emit('print', props.rat.id);
  fetch('http://127.0.0.1:7242/ingest/64e59590-eb2a-4207-934f-0400ea12fcbd',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'RatTableRow.vue:handlePrint',message:'Print event emitted',data:{ratId:props.rat.id},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
}
// #endregion

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

