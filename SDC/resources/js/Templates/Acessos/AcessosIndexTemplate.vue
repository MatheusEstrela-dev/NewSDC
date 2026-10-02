<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      title="Acessos"
      :description="resumoAtivos"
      :icon="IdentificationIcon"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template v-if="pode.criar" #actions>
        <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="formAberto = true">
          Novo cadastro
        </Button>
      </template>
    </PageHeader>

    <AcessosFiltersSection :filtros="filtros" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Cadastros" :icon="UserGroupIcon" :count="paginacao.total">
      <AcessosLista :cadastros="cadastros" />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>

    <CadastroAcessoModal v-if="pode.criar" :show="formAberto" @close="formAberto = false" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { IdentificationIcon, PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import AcessosFiltersSection from '@/Components/Organisms/Acessos/AcessosFiltersSection.vue';
import AcessosLista from '@/Components/Organisms/Acessos/AcessosLista.vue';
import CadastroAcessoModal from '@/Components/Organisms/Acessos/CadastroAcessoModal.vue';

const props = defineProps({
  cadastros: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  filtros: { type: Object, required: true },
  ativos: { type: Number, default: 0 },
  pode: { type: Object, required: true },
});
defineEmits(['aplicar', 'limpar', 'pagina']);

const formAberto = ref(false);

const resumoAtivos = computed(() => `${props.ativos} ${props.ativos === 1 ? 'cadastro ativo' : 'cadastros ativos'}`);
</script>
