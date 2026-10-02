<template>
  <Head title="Acessos" />
  <AcessosIndexTemplate
    :cadastros="cadastros.data"
    :paginacao="cadastros"
    :filtros="query"
    :ativos="ativos"
    :pode="pode"
    @aplicar="search()"
    @limpar="limpar"
    @pagina="paginar"
  />
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AcessosIndexTemplate from '@/Templates/Acessos/AcessosIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({ cadastros: Object, filters: Object, ativos: Number });
const { can } = usePermissions();

const pode = computed(() => ({ criar: can('acessos.cadastros.create') }));

const VAZIO = { nome: '', cpf: '', setor: '', status: '' };
const query = reactive(Object.fromEntries(Object.keys(VAZIO).map((k) => [k, props.filters?.[k] || ''])));

// Remove campos vazios para a URL nao carregar filtro em branco.
const semVazios = (params) => Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined));

function search() {
  router.get('/acessos', semVazios(query), { preserveState: true });
}

function limpar() {
  Object.assign(query, VAZIO);
  search();
}

// Pagina sobre os filtros ja aplicados (os mesmos que o withQueryString leva nos links).
function paginar(page) {
  router.get('/acessos', semVazios({ ...(props.filters || {}), page }));
}
</script>
