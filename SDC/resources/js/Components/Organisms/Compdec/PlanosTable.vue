<template>
  <CardBase>
    <div v-if="!planos || planos.length === 0" class="empty-state">
      <Text variant="muted">Nenhum plano de contingencia cadastrado.</Text>
    </div>

    <table v-else class="planos-table">
      <thead>
        <tr>
          <th>Versao</th>
          <th>Status</th>
          <th>Aprovacao</th>
          <th>Tamanho</th>
          <th>Criado em</th>
          <th class="actions-col">Acoes</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="plano in planos" :key="plano.id" :class="{ 'plano-ativo': plano.ativo }">
          <td>
            <Text variant="bold">{{ plano.versao }}</Text>
          </td>
          <td>
            <PlanoVigenciaBadge :ativo="plano.ativo" :esta-aprovado="plano.esta_aprovado" />
          </td>
          <td>
            <template v-if="plano.esta_aprovado">
              <Text size="sm">{{ plano.aprovador_nome || 'Aprovado' }}</Text>
              <Text variant="muted" size="xs" class="block">{{ formatDate(plano.aprovado_em) }}</Text>
            </template>
            <Text v-else variant="muted">-</Text>
          </td>
          <td>
            <Text variant="muted" size="sm">{{ formatBytes(plano.tamanho_bytes) }}</Text>
          </td>
          <td>
            <Text variant="muted" size="sm">{{ formatDate(plano.created_at) }}</Text>
          </td>
          <td class="actions-col">
            <ButtonGroup>
              <Button
                v-if="canDownload && plano.tem_arquivo"
                variant="ghost"
                size="sm"
                :icon="ArrowDownTrayIcon"
                @click="$emit('download', plano)"
              />
              <Button
                v-if="canEdit && !plano.ativo"
                variant="ghost"
                size="sm"
                @click="$emit('ativar', plano)"
              >
                Ativar
              </Button>
              <Button
                v-if="canAprovar && !plano.esta_aprovado"
                variant="ghost"
                size="sm"
                @click="$emit('aprovar', plano)"
              >
                Aprovar
              </Button>
              <Button
                v-if="canEdit"
                variant="ghost"
                size="sm"
                :icon="PencilIcon"
                @click="$emit('edit', plano)"
              />
              <Button
                v-if="canDelete"
                variant="ghost"
                size="sm"
                :icon="TrashIcon"
                @click="$emit('delete', plano)"
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
import PlanoVigenciaBadge from '@/Components/Molecules/Compdec/PlanoVigenciaBadge.vue';

defineProps({
  planos: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canAprovar: { type: Boolean, default: false },
  canDownload: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete', 'ativar', 'aprovar', 'download']);

function formatBytes(bytes) {
  if (!bytes) return '-';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function formatDate(iso) {
  if (!iso) return '-';
  const d = new Date(iso);
  return d.toLocaleDateString('pt-BR');
}
</script>

<style scoped>
.planos-table {
  width: 100%;
  border-collapse: collapse;
}

.planos-table th {
  text-align: left;
  padding: 0.75rem 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-700 text-slate-300;
}

.planos-table td {
  padding: 0.875rem 0.5rem;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-800 text-slate-200;
  vertical-align: top;
}

.planos-table tr:last-child td {
  border-bottom: none;
}

.plano-ativo {
  @apply bg-emerald-500/5;
}

.actions-col {
  width: 220px;
  text-align: right;
}

.empty-state {
  padding: 1.5rem 0;
  text-align: center;
}
</style>
