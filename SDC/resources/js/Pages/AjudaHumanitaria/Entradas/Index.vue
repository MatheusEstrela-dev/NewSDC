<template>
  <AuthenticatedLayout>
    <Head title="Entradas de Material" />

    <EntradaAhIndexTemplate
      :entradas="entradas"
      :estatisticas="estatisticas"
      :depositos="depositos"
      :fontes="fontes"
      :filtros="filtros"
      :ordenacao="ordenacao"
      :formulario="formulario"
      @filtrar="filtrar"
      @nova="mostrarFormulario = true"
      @ordenar="ordenar"
      @pagina="irParaPagina"
      @ver="ver"
      @exportar="exportar"
    />

    <EntradaAhFormModal
      v-if="formulario"
      :show="mostrarFormulario"
      :opcoes="formulario"
      @close="mostrarFormulario = false"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EntradaAhIndexTemplate from '@/Templates/AjudaHumanitaria/EntradaAhIndexTemplate.vue';
import EntradaAhFormModal from '@/Components/Organisms/AjudaHumanitaria/EntradaAhFormModal.vue';

const props = defineProps({
  entradas: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  fontes: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
  formulario: { type: Object, default: null },
});

const mostrarFormulario = ref(false);

const rotaIndex = 'ajuda-humanitaria.entradas.index';

function limpar(parametros) {
  return Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );
}

function navegar(parametros) {
  router.get(route(rotaIndex), limpar(parametros), { preserveState: true, preserveScroll: true, replace: true });
}

function filtrar(filtros) {
  navegar(filtros);
}

/**
 * Ordenacao vai na URL junto dos filtros: a listagem e paginada no banco, e
 * reordenar no cliente reordenaria apenas a pagina visivel. Volta para a
 * primeira pagina, porque a linha que estava na pagina 12 muda de lugar.
 */
function ordenar({ sort, direction }) {
  navegar({ ...props.filtros, sort, direction });
}

function irParaPagina(pagina) {
  navegar({ ...props.filtros, page: pagina });
}

function ver(id) {
  router.get(route('ajuda-humanitaria.entradas.show', id));
}

/**
 * escopo.all significa serie completa: as datas do modal caem, mas o recorte
 * montado na tela permanece.
 */
function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  window.location.href = route('ajuda-humanitaria.entradas.export', limpar(parametros));
}
</script>
