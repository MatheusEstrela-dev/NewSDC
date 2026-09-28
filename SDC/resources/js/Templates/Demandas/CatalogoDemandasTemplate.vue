<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      title="Catálogo de demandas"
      description="Categorias e assuntos usados na abertura de chamados"
      :icon-image="moduleIcon('demandas')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="abrirCategoriaModal()">
            Nova categoria
          </Button>
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="abrirAssuntoModal()">
            Novo assunto
          </Button>
        </div>
      </template>
    </PageHeader>

    <CatalogoStatisticsCards :estatisticas="estatisticas" />

    <ListContainer title="Categorias" :icon="FolderIcon" :count="categorias.length">
      <CatalogoCategoriasTable
        :categorias="categorias"
        @editar="abrirCategoriaModal"
        @alternar-status="alternarStatusCategoria"
      />
    </ListContainer>

    <ListContainer title="Assuntos" :icon="DocumentTextIcon" :count="assuntos.length">
      <CatalogoAssuntosTable
        :assuntos="assuntos"
        @editar="abrirAssuntoModal"
        @alternar-status="alternarStatusAssunto"
      />
    </ListContainer>

    <CatalogoCategoriaModal
      :show="categoriaModal.aberto"
      :categoria="categoriaModal.item"
      :categorias-principais="categoriasPrincipaisPara(categoriaModal.item)"
      @close="fecharCategoriaModal"
      @saved="fecharCategoriaModal"
    />

    <CatalogoAssuntoModal
      :show="assuntoModal.aberto"
      :assunto="assuntoModal.item"
      :categorias="categorias"
      @close="fecharAssuntoModal"
      @saved="fecharAssuntoModal"
    />
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { FolderIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import CatalogoStatisticsCards from '@/Components/Organisms/Demandas/Catalogo/CatalogoStatisticsCards.vue';
import CatalogoCategoriasTable from '@/Components/Organisms/Demandas/Catalogo/CatalogoCategoriasTable.vue';
import CatalogoAssuntosTable from '@/Components/Organisms/Demandas/Catalogo/CatalogoAssuntosTable.vue';
import CatalogoCategoriaModal from '@/Components/Organisms/Demandas/Catalogo/CatalogoCategoriaModal.vue';
import CatalogoAssuntoModal from '@/Components/Organisms/Demandas/Catalogo/CatalogoAssuntoModal.vue';

const props = defineProps({
  categorias: { type: Array, required: true },
  assuntos: { type: Array, required: true },
  estatisticas: { type: Object, required: true },
});

const categoriaModal = reactive({ aberto: false, item: null });
const assuntoModal = reactive({ aberto: false, item: null });

function abrirCategoriaModal(categoria = null) {
  categoriaModal.item = categoria;
  categoriaModal.aberto = true;
}

function fecharCategoriaModal() {
  categoriaModal.aberto = false;
}

function abrirAssuntoModal(assunto = null) {
  assuntoModal.item = assunto;
  assuntoModal.aberto = true;
}

function fecharAssuntoModal() {
  assuntoModal.aberto = false;
}

// So categorias principais (sem pai) podem virar pai de outra -- e nunca a
// propria categoria em edicao, senao ela viraria pai de si mesma.
function categoriasPrincipaisPara(categoriaEmEdicao) {
  return props.categorias.filter((categoria) => !categoria.parent_id && categoria.id !== categoriaEmEdicao?.id);
}

function alternarStatusCategoria(categoria) {
  router.put(
    route('admin.demandas.categorias.update', categoria.id),
    { ativo: !categoria.ativo },
    { preserveScroll: true },
  );
}

function alternarStatusAssunto(assunto) {
  router.put(
    route('admin.demandas.assuntos.update', assunto.id),
    { ativo: !assunto.ativo },
    { preserveScroll: true },
  );
}
</script>
