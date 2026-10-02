<template>
  <Head title="Estações de trabalho" />
  <EstacoesIndexTemplate
    :estacoes="estacoes.data"
    :paginacao="estacoes"
    :filtros="filtros"
    :pode="pode"
    @aplicar="aplicar(filtros)"
    @limpar="limpar"
    @pagina="paginar"
    @remover="remover"
  />
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EstacoesIndexTemplate from '@/Templates/Inventario/EstacoesIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  estacoes: { type: Object, required: true },
  filters: { type: [Object, Array], default: () => ({}) },
});

const { can } = usePermissions();
const pode = computed(() => ({
  criar: can('inventario.equipamentos.create'),
  editar: can('inventario.equipamentos.edit'),
  remover: can('inventario.equipamentos.delete'),
}));

const VAZIO = { search: '', status: '' };
// filters chega como [] (array PHP vazio) quando nao ha filtro.
const filtrosAplicados = computed(() => ({ ...VAZIO, ...(Array.isArray(props.filters) ? {} : props.filters) }));
const filtros = reactive({ ...filtrosAplicados.value });

function aplicar(params) {
  router.get(route('inventario.estacoes.index'), params, { preserveState: true });
}

// Paginar usa o filtro ja aplicado, nao o que esta digitado e nao enviado,
// e remonta a pagina como a navegacao pelos links do paginador fazia.
function paginar(pagina) {
  router.get(route('inventario.estacoes.index'), { ...filtrosAplicados.value, page: pagina });
}

function limpar() {
  Object.assign(filtros, VAZIO);
  aplicar(filtros);
}

function remover(item) {
  if (window.confirm(`Remover a estação ${item.nome}?`)) router.delete(route('inventario.estacoes.destroy', item.id));
}
</script>
