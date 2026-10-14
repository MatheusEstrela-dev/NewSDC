<template>
  <Head title="Estoque" />
  <EstoqueIndexTemplate
    :active-section="activeSection"
    :lotes="lotes"
    :statistics="statistics"
    :ocupacao="ocupacao"
    :alertas="alertas"
    :kits="kits"
    :movimentacoes="movimentacoes"
    :catalogo="catalogo"
    :municipios="municipios"
    :filters="filters"
    :filter-options="filterOptions"
    :can-create="can('estoque.produtos.create')"
    :can-create-kit="can('estoque.kits.create')"
    :can-export="can('estoque.produtos.export')"
    :can-move="can('estoque.movimentacoes.create')"
    :can-quarentena="can('estoque.lotes.edit')"
  />
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EstoqueIndexTemplate from '@/Templates/Estoque/EstoqueIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

/**
 * Modulo /estoque (#32 inventario de donativos, #33 estoque e kits). Os dados
 * vem do EstoqueController; a API equivalente e /api/v1/inventario e
 * /api/v1/estoque/movimentacoes.
 */
defineProps({
  activeSection: { type: String, default: 'dashboard' },
  lotes: { type: Object, default: () => ({ data: [], next_page_url: null }) },
  statistics: { type: Object, required: true },
  ocupacao: { type: Array, default: () => [] },
  alertas: { type: Array, default: () => [] },
  kits: { type: Array, default: () => [] },
  movimentacoes: { type: Array, default: () => [] },
  catalogo: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();
</script>
