<template>
  <CardBase variant="default" padding="none" class="overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-700/50 bg-slate-900/30">
      <div class="flex items-center justify-between">
        <Heading level="5" color="default" class="flex items-center gap-2">
          <DocumentTextIcon class="w-5 h-5" />
          Lista de RATs ({{ pagination?.total || rats.length }} registros)
        </Heading>
      </div>
    </div>
    
    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-400"></div>
      <p class="mt-4 text-slate-400">Carregando...</p>
    </div>
    
    <div v-else-if="rats.length === 0" class="p-12 text-center">
      <DocumentTextIcon class="w-12 h-12 text-slate-600 mx-auto mb-4" />
      <Heading level="4" color="muted">Nenhum RAT encontrado</Heading>
      <Text size="sm" color="muted" class="mt-2">
        Tente ajustar os filtros de busca ou crie um novo RAT
      </Text>
    </div>
    
    <div v-else class="overflow-x-auto">
      <table class="w-full">
        <TableHeaderRow>
          <TableHeader>Número RAT</TableHeader>
          <TableHeader>Data/Hora</TableHeader>
          <TableHeader align="center">Ano</TableHeader>
          <TableHeader align="center">Status</TableHeader>
          <TableHeader>Município</TableHeader>
          <TableHeader>Criado por</TableHeader>
          <TableHeader align="right">Ações</TableHeader>
        </TableHeaderRow>
        <tbody>
          <RatTableRow
            v-for="rat in rats"
            :key="rat.id"
            :rat="rat"
            @view="handleView"
            @edit="handleEdit"
            @attachments="handleAttachments"
            @delete="handleDelete"
          />
        </tbody>
      </table>
    </div>
    
    <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-slate-700/50">
      <Pagination :pagination="pagination" @page-change="handlePageChange" />
    </div>
  </CardBase>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import TableHeaderRow from '@/Components/Molecules/Table/TableHeaderRow.vue';
import TableHeader from '@/Components/Atoms/Table/TableHeader.vue';
import RatTableRow from './RatTableRow.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';

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
});

const emit = defineEmits(['view', 'edit', 'attachments', 'delete', 'page-change']);

function handleView(id) {
  emit('view', id);
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

function handlePageChange(page) {
  emit('page-change', page);
}
</script>

