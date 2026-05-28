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
      {{ municipioDisplay || 'Não informado' }}
    </TableCell>
    <TableCell class="w-44 whitespace-nowrap">
      {{ rat.criado_por || 'Sistema' }}
    </TableCell>
    <TableCell align="right" class="w-44 whitespace-nowrap">
      <div class="flex justify-end">
        <ActionButton
          module="rat"
          resource="protocolos"
          :actions="[
            { action: 'view',        handler: () => emit('view', props.rat) },
            { action: 'print',       handler: () => emit('print', props.rat) },
            { action: 'edit',        handler: () => emit('edit', props.rat),        allowed: canEdit },
            { action: 'attachments', handler: () => emit('attachments', props.rat), label: 'Relacionar' },
            { action: 'delete',      handler: () => emit('delete', props.rat),      allowed: canDelete },
          ]"
        />
      </div>
    </TableCell>
  </TableDataRow>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Badge from '../../../Atoms/Badge/Badge.vue';
import StatusBadge from '../../../Atoms/Badge/StatusBadge.vue';
import TableCell from '../../../Atoms/Table/TableCell.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import TableDataRow from '../../../Molecules/Table/TableDataRow.vue';

// Module-level cache so each IBGE code is fetched only once per page load
const _municipioCache = {};

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

const municipioDisplay = ref(props.rat.municipio ?? '');

onMounted(async () => {
  const raw = props.rat.municipio ?? props.rat.local?.municipio_id ?? '';
  if (!raw || !/^\d+$/.test(String(raw))) {
    municipioDisplay.value = raw || 'Não informado';
    return;
  }
  const code = String(raw);
  if (_municipioCache[code]) {
    municipioDisplay.value = _municipioCache[code];
    return;
  }
  try {
    const res = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/municipios/${code}`);
    if (res.ok) {
      const d = await res.json();
      _municipioCache[code] = d.nome;
      municipioDisplay.value = d.nome;
    }
  } catch {
    // keep raw code if API fails
  }
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


