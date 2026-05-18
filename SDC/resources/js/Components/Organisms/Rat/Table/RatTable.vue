<template>
  <ListSurface
    title="Lista de RATs"
    subtitle="Registros de Atendimento Técnico"
    :count="pagination?.total || rats.length"
    :icon="DocumentTextIcon"
  >
    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
      <p class="mt-4 text-slate-600 dark:text-slate-400">Carregando...</p>
    </div>

    <div v-else-if="rats.length === 0" class="p-12 text-center">
      <DocumentTextIcon class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-4" />
      <Heading :level="4" color="muted">Nenhum RAT encontrado</Heading>
      <Text size="sm" color="muted" class="mt-2">
        Tente ajustar os filtros de busca ou crie um novo RAT
      </Text>
    </div>

    <!-- Desktop: Tabela -->
    <div v-else-if="!isMobile" class="overflow-x-auto -mx-px">
      <table class="w-full text-sm">
        <TableHeaderRow>
          <TableHeader class="w-48 whitespace-nowrap">Número RAT</TableHeader>
          <TableHeader class="w-44 whitespace-nowrap">Data/Hora</TableHeader>
          <TableHeader align="center" class="w-24 whitespace-nowrap">Ano</TableHeader>
          <TableHeader align="center" class="w-36 whitespace-nowrap">Status</TableHeader>
          <TableHeader class="w-auto whitespace-nowrap">Município</TableHeader>
          <TableHeader class="w-44 whitespace-nowrap">Criado por</TableHeader>
          <TableHeader align="right" class="w-56 min-w-56 whitespace-nowrap text-right">Ações</TableHeader>
        </TableHeaderRow>
        <tbody>
          <RatTableRow
            v-for="rat in rats"
            :key="rat.id"
            :rat="rat"
            :can-edit="canEdit"
            :can-delete="canDelete"
            @view="handleView"
            @print="handlePrint"
            @edit="handleEdit"
            @attachments="handleAttachments"
            @delete="handleDelete"
          />
        </tbody>
      </table>
    </div>

    <!-- Mobile: Cards -->
    <div v-else class="divide-y divide-slate-200 dark:divide-slate-700/50">
      <RatCard
        v-for="rat in rats"
        :key="rat.id"
        :rat="rat"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @view="handleView"
        @print="handlePrint"
        @edit="handleEdit"
        @attachments="handleAttachments"
        @delete="handleDelete"
        class="m-4 first:mt-0 last:mb-0"
      />
    </div>
  </ListSurface>
</template>

<script setup>
import TableHeader from '@/Components/Atoms/Table/TableHeader.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import RatCard from '@/Components/Molecules/Rat/RatCard.vue';
import TableHeaderRow from '@/Components/Molecules/Table/TableHeaderRow.vue';
import ListSurface from '@/Components/Organisms/Table/ListSurface.vue';
import { useMobile } from '@/Composables/useMobile';
import RatTableRow from './RatTableRow.vue';

// Detecção mobile
const { isMobile } = useMobile();

const props = defineProps({
  rats: {
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
});

const emit = defineEmits(['view', 'print', 'edit', 'attachments', 'delete']);

function handleView(id) {
  emit('view', id);
}

function handlePrint(id) {
  emit('print', id);
}

function handleEdit(id) {
  emit('edit', id);
}

function handleAttachments(id) {
  emit('attachments', id);
}

function handleDelete(id) {
  emit('delete', id);
}
</script>
