<template>
  <CardBase>
    <div v-if="!anexos || anexos.length === 0" class="empty-state">
      <Text variant="muted">Nenhum anexo legal cadastrado.</Text>
    </div>

    <table v-else class="anexos-table">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Titulo</th>
          <th>Numero</th>
          <th>Validade</th>
          <th>Status</th>
          <th class="actions-col">Acoes</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="anexo in anexos" :key="anexo.id">
          <td>
            <Text variant="bold">{{ anexo.tipo_label }}</Text>
          </td>
          <td>
            <Text>{{ anexo.titulo }}</Text>
            <Text v-if="!anexo.tem_arquivo" variant="muted" size="xs" class="block">
              (sem arquivo)
            </Text>
          </td>
          <td>
            <Text variant="mono" size="sm">{{ anexo.numero || '-' }}</Text>
          </td>
          <td>
            <Text size="sm">{{ formatDate(anexo.data_validade) }}</Text>
          </td>
          <td>
            <ValidadeAnexoBadge :status="anexo.status_validade" />
          </td>
          <td class="actions-col">
            <ButtonGroup>
              <Button
                v-if="canDownload && anexo.tem_arquivo"
                variant="ghost"
                size="sm"
                :icon="ArrowDownTrayIcon"
                @click="$emit('download', anexo)"
              />
              <Button
                v-if="canEdit"
                variant="ghost"
                size="sm"
                :icon="PencilIcon"
                @click="$emit('edit', anexo)"
              />
              <Button
                v-if="canDelete"
                variant="ghost"
                size="sm"
                :icon="TrashIcon"
                @click="$emit('delete', anexo)"
              />
            </ButtonGroup>
          </td>
        </tr>
      </tbody>
    </table>
  </CardBase>
</template>

<script setup>
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonGroup from '@/Components/Atoms/Button/ButtonGroup.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import ValidadeAnexoBadge from '@/Components/Molecules/Compdec/ValidadeAnexoBadge.vue';

defineProps({
  anexos: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canDownload: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['edit', 'delete', 'download']);

function formatDate(iso) {
  if (!iso) return 'Sem validade';
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
}
</script>

<style scoped>
.anexos-table {
  width: 100%;
  border-collapse: collapse;
}

.anexos-table th {
  text-align: left;
  padding: 0.75rem 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-700 text-slate-300;
}

.anexos-table td {
  padding: 0.875rem 0.5rem;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-800 text-slate-200;
  vertical-align: top;
}

.anexos-table tr:last-child td {
  border-bottom: none;
}

.actions-col {
  width: 140px;
  text-align: right;
}

.empty-state {
  padding: 1.5rem 0;
  text-align: center;
}
</style>
