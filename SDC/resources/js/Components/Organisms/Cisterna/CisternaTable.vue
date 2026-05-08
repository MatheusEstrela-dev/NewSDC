<template>
  <CardBase>
    <div v-if="!cisternas || cisternas.length === 0" class="empty-state">
      <Text variant="muted">Nenhuma cisterna cadastrada para os filtros atuais.</Text>
    </div>

    <table v-else class="cisternas-table">
      <thead>
        <tr>
          <th>Codigo</th>
          <th>Nome</th>
          <th>Municipio</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Capacidade</th>
          <th class="actions-col">Acoes</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="cisterna in cisternas" :key="cisterna.id" @click="$emit('show', cisterna)">
          <td><Text variant="mono" size="sm">{{ cisterna.codigo }}</Text></td>
          <td><Text variant="bold">{{ cisterna.nome }}</Text></td>
          <td>
            <Text v-if="cisterna.municipio" size="sm">
              {{ cisterna.municipio.nome }}{{ cisterna.municipio.uf ? ' - ' + cisterna.municipio.uf : '' }}
            </Text>
            <Text v-else variant="muted">-</Text>
          </td>
          <td><TipoCisternaBadge :tipo="cisterna.tipo" /></td>
          <td><StatusCisternaBadge :status="cisterna.status" /></td>
          <td>
            <Text v-if="cisterna.capacidade_litros" variant="mono" size="sm">
              {{ formatLitros(cisterna.capacidade_litros) }} L
            </Text>
            <Text v-else variant="muted">-</Text>
          </td>
          <td class="actions-col" @click.stop>
            <ButtonGroup>
              <Button
                variant="ghost"
                size="sm"
                :icon="EyeIcon"
                @click="$emit('show', cisterna)"
              />
              <Button
                v-if="canEdit"
                variant="ghost"
                size="sm"
                :icon="PencilIcon"
                @click="$emit('edit', cisterna)"
              />
              <Button
                v-if="canDelete"
                variant="ghost"
                size="sm"
                :icon="TrashIcon"
                @click="$emit('delete', cisterna)"
              />
            </ButtonGroup>
          </td>
        </tr>
      </tbody>
    </table>
  </CardBase>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonGroup from '@/Components/Atoms/Button/ButtonGroup.vue';
import EyeIcon from '@/Components/Icons/EyeIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import StatusCisternaBadge from '@/Components/Molecules/Cisterna/StatusCisternaBadge.vue';
import TipoCisternaBadge from '@/Components/Molecules/Cisterna/TipoCisternaBadge.vue';

defineProps({
  cisternas: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

defineEmits(['show', 'edit', 'delete']);

function formatLitros(value) {
  return Number(value).toLocaleString('pt-BR');
}
</script>

<style scoped>
.cisternas-table {
  width: 100%;
  border-collapse: collapse;
}

.cisternas-table th {
  text-align: left;
  padding: 0.75rem 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-700 text-slate-300;
}

.cisternas-table td {
  padding: 0.875rem 0.5rem;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-800 text-slate-200;
  vertical-align: middle;
}

.cisternas-table tbody tr {
  cursor: pointer;
  transition: background-color 0.15s;
}

.cisternas-table tbody tr:hover {
  @apply bg-slate-800/50;
}

.cisternas-table tr:last-child td {
  border-bottom: none;
}

.actions-col {
  width: 140px;
  text-align: right;
}

.empty-state {
  padding: 2rem 0;
  text-align: center;
}
</style>
