<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      title="Estações de trabalho"
      description="Locais, ocupação e ponto de rede"
      :icon-image="moduleIcon('inventario')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <Button v-if="pode.criar" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="abrirFormulario(null)">
          Nova estação
        </Button>
      </template>
    </PageHeader>

    <EstacoesFiltersSection :filtros="filtros" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Estações" :icon="ComputerDesktopIcon" :count="paginacao.total">
      <EstacoesLista :estacoes="estacoes" :pode="pode" @editar="abrirFormulario" @remover="(item) => $emit('remover', item)" />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>

    <EstacaoFormModal :show="formularioAberto" :estacao="selecionada" :usuarios="usuarios" @close="formularioAberto = false" />
  </div>
</template>

<script setup>
import { moduleIcon } from '@/Support/moduleIcons';
import { ref } from 'vue';
import { ComputerDesktopIcon, PlusIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import EstacoesFiltersSection from '@/Components/Organisms/Inventario/Estacoes/EstacoesFiltersSection.vue';
import EstacoesLista from '@/Components/Organisms/Inventario/Estacoes/EstacoesLista.vue';
import EstacaoFormModal from '@/Components/Organisms/Inventario/Estacoes/EstacaoFormModal.vue';

defineProps({
  estacoes: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  filtros: { type: Object, required: true },
  pode: { type: Object, required: true },
  usuarios: { type: Array, default: () => [] },
});
defineEmits(['aplicar', 'limpar', 'pagina', 'remover']);

const formularioAberto = ref(false);
const selecionada = ref(null);

// Nulo abre o cadastro; uma estacao abre a edicao dela.
function abrirFormulario(estacao) {
  selecionada.value = estacao;
  formularioAberto.value = true;
}
</script>
