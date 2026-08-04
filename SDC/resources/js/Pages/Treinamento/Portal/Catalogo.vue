<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import CidadaoPortalLayout from '@/Layouts/CidadaoPortalLayout.vue';
import TreinamentoGrid from '@/Components/Organisms/Treinamento/TreinamentoGrid.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';

defineOptions({ layout: CidadaoPortalLayout });

const props = defineProps({
  treinamentos: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const pagination = computed(() => {
  const m = props.treinamentos?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 12,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('portal.treinamento.catalogo'), {
    data: { ...props.filters, page },
    preserveState: true,
  });
}

function handleView(id) {
  const treinamento = (props.treinamentos.data || []).find((t) => t.id === id);
  if (treinamento?.link_publico_slug) {
    router.visit(route('portal.treinamento.eventos.show', treinamento.link_publico_slug));
  }
}

function handleSearch(evt) {
  router.visit(route('portal.treinamento.catalogo'), {
    data: { search: evt.target.value },
    preserveState: true,
    replace: true,
  });
}
</script>

<template>
  <div>
    <div class="mb-6">
      <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">Cursos e Eventos</Heading>
      <Text size="sm" color="muted">Confira os cursos e eventos abertos para inscrição da Defesa Civil de Minas Gerais.</Text>
    </div>

    <div class="mb-6 max-w-sm">
      <input
        type="text"
        :value="filters.search"
        placeholder="Buscar por título..."
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        @change="handleSearch"
      />
    </div>

    <TreinamentoGrid :treinamentos="treinamentos.data" @view="handleView" />

    <div v-if="pagination" class="mt-6">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>
  </div>
</template>
