<template>
  <div class="space-y-6 pb-8">
    <PageHeader title="Demandas" description="Chamados abertos para a equipe CEDEC" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button v-if="pode.exportar" variant="secondary" size="md" :icon="DownloadIcon" icon-position="left" @click="$emit('exportar')">Exportar</Button>
          <Button v-if="pode.criar" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="$emit('criar')">Nova demanda</Button>
        </div>
      </template>
    </PageHeader>

    <DemandasStatisticsCards :estatisticas="estatisticas" @filtrar="(e) => $emit('filtrar-etapa', e)" />

    <DemandasFiltersSection :filtros="filtros" :opcoes="opcoes" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Todas as demandas" :icon="DocumentTextIcon" :count="paginacao.total">
      <DemandasTable :demandas="demandas" @abrir="(id) => $emit('abrir', id)" />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>
  </div>
</template>

<script setup>
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import DemandasFiltersSection from '@/Components/Organisms/Demandas/Filters/DemandasFiltersSection.vue';
import DemandasTable from '@/Components/Organisms/Demandas/Table/DemandasTable.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  demandas: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  estatisticas: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
});
defineEmits(['criar', 'exportar', 'aplicar', 'limpar', 'filtrar-etapa', 'abrir', 'pagina']);
</script>
