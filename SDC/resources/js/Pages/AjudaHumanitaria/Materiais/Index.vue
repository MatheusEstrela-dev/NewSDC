<template>
  <AuthenticatedLayout>
    <Head title="Catálogo de Materiais" />

    <MaterialAhIndexTemplate
      :materiais="materiais"
      :estatisticas="estatisticas"
      :unidades="unidades"
      :filtros="filtros"
      :ordenacao="ordenacao"
      @filtrar="filtrar"
      @ordenar="ordenar"
      @pagina="irParaPagina"
      @exportar="exportar"
      @novo="abrirCadastro"
      @editar="abrirEdicao"
      @excluir="pedirConfirmacao"
    />

    <MaterialAhFormModal
      :show="mostrarFormulario"
      :material="materialEmEdicao"
      :unidades="unidades"
      @close="mostrarFormulario = false"
    />

    <ConfirmDialog
      :is-open="Boolean(materialParaExcluir)"
      title="Excluir material"
      :message="`Tem certeza que deseja excluir '${materialParaExcluir?.nome}'?`"
      description="Material já usado em entradas, liberações ou transferências não pode ser excluído; nesse caso, marque como indisponível para pedido."
      variant="danger"
      confirm-text="Sim, excluir"
      cancel-text="Cancelar"
      :loading="excluindo"
      @confirm="excluir"
      @cancel="materialParaExcluir = null"
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
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import MaterialAhFormModal from '@/Components/Organisms/AjudaHumanitaria/MaterialAhFormModal.vue';
import MaterialAhIndexTemplate from '@/Templates/AjudaHumanitaria/MaterialAhIndexTemplate.vue';

const props = defineProps({
  materiais: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  unidades: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.materiais.index';

const mostrarFormulario = ref(false);
const materialEmEdicao = ref({});
const materialParaExcluir = ref(null);
const excluindo = ref(false);

function limpar(parametros) {
  return Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );
}

function navegar(parametros) {
  router.get(route(rotaIndex), limpar(parametros), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/** Trocar de filtro volta para a primeira pagina. */
function filtrar(filtros) {
  navegar(filtros);
}

/**
 * Ordenacao vai na URL junto dos filtros: a listagem e paginada no banco, e
 * reordenar no cliente reordenaria apenas a pagina visivel.
 */
function ordenar({ sort, direction }) {
  navegar({ ...props.filtros, sort, direction });
}

function irParaPagina(pagina) {
  navegar({ ...props.filtros, page: pagina });
}

/**
 * O catalogo nao tem coluna de data, entao nao ha janela a escolher: o CSV sai
 * com o mesmo recorte que esta na tela.
 */
function exportar() {
  window.location.href = route('ajuda-humanitaria.materiais.export', limpar({ ...props.filtros }));
}

function abrirCadastro() {
  materialEmEdicao.value = {};
  mostrarFormulario.value = true;
}

function abrirEdicao(material) {
  materialEmEdicao.value = { ...material };
  mostrarFormulario.value = true;
}

function pedirConfirmacao(material) {
  materialParaExcluir.value = material;
}

function excluir() {
  const material = materialParaExcluir.value;

  if (!material) return;

  excluindo.value = true;

  router.delete(route('ajuda-humanitaria.materiais.destroy', material.id), {
    preserveScroll: true,
    // O dialogo fecha em onFinish, e nao em onSuccess: material com historico
    // volta com mensagem de erro, e o dialogo aberto esconderia o aviso.
    onFinish: () => {
      excluindo.value = false;
      materialParaExcluir.value = null;
    },
  });
}
</script>
