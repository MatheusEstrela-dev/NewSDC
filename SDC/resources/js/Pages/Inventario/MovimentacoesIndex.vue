<template>
  <Head title="Movimentações de equipamentos" />
  <RemanejamentosIndexTemplate
    :lotes="remanejamentos.data"
    :paginacao="remanejamentos"
    :avulsas="movimentacoes"
    :filtros="filtros"
    :opcoes="opcoes"
    :pode="pode"
    :equipamentos="equipamentos"
    :usuarios="usuarios"
    :estacoes="estacoes"
    @novo="router.visit(route('inventario.remanejamentos.create'))"
    @aplicar="aplicar()"
    @limpar="limpar"
    @pagina="(p) => paginar({ page: p })"
    @pagina-avulsas="(p) => paginar({ pagina_avulsas: p })"
  />
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RemanejamentosIndexTemplate from '@/Templates/Inventario/RemanejamentosIndexTemplate.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  remanejamentos: { type: Object, required: true },
  movimentacoes: { type: Object, required: true },
  filters: { type: [Object, Array], default: () => ({}) },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});

const VAZIO = { search: '', status: '', data: '' };
// filters chega como [] (array PHP vazio) quando nao ha filtro.
const filtros = reactive({ ...VAZIO, ...(Array.isArray(props.filters) ? {} : props.filters) });

function aplicar(extra = {}) {
  const params = Object.fromEntries(
    Object.entries({ ...filtros, ...extra }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
  );
  router.get(route('inventario.movimentacoes.index'), params, { preserveState: true, preserveScroll: true, replace: true });
}

// Paginar uma lista mantem a pagina da outra; mudar filtro (aplicar sem
// pagina) volta as duas para a primeira.
function paginar(pagina) {
  aplicar({
    page: props.remanejamentos.current_page,
    pagina_avulsas: props.movimentacoes.current_page,
    ...pagina,
  });
}

function limpar() {
  Object.assign(filtros, VAZIO);
  aplicar();
}

useAtualizacaoAoVivo({
  canal: 'listagem.inventario-remanejamentos',
  evento: '.RecursoAtualizado',
  props: ['remanejamentos', 'movimentacoes'],
});
</script>
