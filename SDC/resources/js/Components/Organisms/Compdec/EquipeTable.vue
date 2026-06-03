<template>
  <CardBase>
    <div class="equipe-header">
      <div>
        <Heading level="3">Equipe</Heading>
        <Text variant="muted" size="sm">
          {{ totalAtivos }} ativo(s) de {{ total }} membro(s) cadastrado(s)
        </Text>
      </div>
      <Button
        v-if="canEdit"
        variant="primary"
        size="sm"
        :icon="PlusIcon"
        @click="$emit('create')"
      >
        Adicionar Membro
      </Button>
    </div>

    <div v-if="!membros || membros.length === 0" class="empty-state">
      <Text variant="muted">Nenhum membro cadastrado para a equipe.</Text>
    </div>

    <table v-else class="equipe-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Funcao</th>
          <th>Contato</th>
          <th>Status</th>
          <th v-if="canEdit || canDelete" class="actions-col">Acoes</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="membro in membros" :key="membro.id">
          <td>
            <Text variant="bold">{{ membro.nome }}</Text>
            <Text v-if="membro.cpf" variant="muted" size="xs" class="block">
              CPF: {{ membro.cpf }}
            </Text>
          </td>
          <td>
            <FuncaoEquipeBadge :funcao="membro.funcao" />
          </td>
          <td>
            <Text v-if="membro.email" size="sm">{{ membro.email }}</Text>
            <Text v-if="membro.celular" variant="muted" size="xs" class="block">
              {{ membro.celular }}
            </Text>
            <Text v-if="!membro.email && !membro.celular" variant="muted">-</Text>
          </td>
          <td>
            <StatusOrgaoBadge :status="membro.ativo ? 'ativo' : 'inativo'" />
          </td>
          <td v-if="canEdit || canDelete" class="actions-col">
            <div class="flex justify-end">
              <ActionButton
                module="compdec"
                resource="equipe"
                size="sm"
                :actions="[
                  { action: 'edit',   handler: () => $emit('edit', membro),   allowed: canEdit },
                  { action: 'delete', handler: () => $emit('delete', membro), allowed: canDelete },
                ]"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </CardBase>
</template>

<script setup>
import { computed } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import FuncaoEquipeBadge from '@/Components/Molecules/Compdec/FuncaoEquipeBadge.vue';
import StatusOrgaoBadge from '@/Components/Molecules/Compdec/StatusOrgaoBadge.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  membros: {
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
});

defineEmits(['create', 'edit', 'delete']);

const total = computed(() => props.membros?.length || 0);
const totalAtivos = computed(() => (props.membros || []).filter((membro) => membro.ativo).length);
</script>

<style scoped>
.equipe-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.equipe-table {
  width: 100%;
  border-collapse: collapse;
}

.equipe-table th {
  text-align: left;
  padding: 0.75rem 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-700 text-slate-300;
}

.equipe-table td {
  padding: 0.875rem 0.5rem;
  font-size: 0.875rem;
  border-bottom: 1px solid;
  @apply border-slate-800 text-slate-200;
  vertical-align: top;
}

.equipe-table tr:last-child td {
  border-bottom: none;
}

.actions-col {
  width: 100px;
  text-align: right;
}

.empty-state {
  padding: 1.5rem 0;
  text-align: center;
}
</style>
