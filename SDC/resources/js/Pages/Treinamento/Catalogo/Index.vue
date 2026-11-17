<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SearchInput from '@/Components/Atoms/Input/SearchInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import CursosTable from '@/Components/Organisms/Treinamento/Catalogo/CursosTable.vue';
import CursoFormModal from '@/Components/Organisms/Treinamento/Catalogo/CursoFormModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cursos: { type: Object, required: true },
  filtros: { type: Object, default: () => ({}) },
});

const { show: toast } = useToast();

const busca = ref(props.filtros.busca ?? '');
const ativo = ref(props.filtros.ativo ?? '');
const modalAberto = ref(false);
const cursoEmEdicao = ref(null);
let espera = null;

const opcoesAtivo = [
  { value: '', label: 'Todos' },
  { value: '1', label: 'Ativos' },
  { value: '0', label: 'Inativos' },
];

const paginacao = computed(() => {
  const m = props.cursos?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function filtrar(extra = {}) {
  router.get(route('treinamentos.catalogo.index'), { busca: busca.value || undefined, ativo: ativo.value || undefined, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

// Busca digitada espera uma pausa para nao disparar um pedido por tecla.
function filtrarComPausa() {
  clearTimeout(espera);
  espera = setTimeout(() => filtrar(), 350);
}

onBeforeUnmount(() => clearTimeout(espera));

function abrirNovo() {
  cursoEmEdicao.value = null;
  modalAberto.value = true;
}

function abrirEdicao(curso) {
  cursoEmEdicao.value = curso;
  modalAberto.value = true;
}

function alternarAtivo(curso) {
  router.put(route('treinamentos.catalogo.update', curso.id), {
    nome: curso.nome,
    sigla: curso.sigla,
    descricao: curso.descricao,
    ativo: !curso.ativo,
  }, {
    preserveScroll: true,
    onSuccess: () => toast(curso.ativo ? 'Curso inativado.' : 'Curso ativado.', 'success'),
    onError: () => toast('Nao foi possivel alterar o curso.', 'error'),
  });
}
</script>

<template>
  <div class="w-full pb-8">
    <PageHeader
      title="Catálogo de cursos"
      description="Cursos que agrupam as turmas, usados na regra de inscrições e na consulta para edital"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template #actions>
        <Button variant="success" size="sm" @click="abrirNovo">Novo curso</Button>
      </template>
    </PageHeader>

    <CardBase class="p-4 sm:p-6">
      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_12rem]">
        <SearchInput v-model="busca" placeholder="Buscar por nome ou sigla" @update:model-value="filtrarComPausa" />
        <SelectInput v-model="ativo" :options="opcoesAtivo" placeholder="" @update:model-value="filtrar()" />
      </div>

      <CursosTable :cursos="cursos.data" @editar="abrirEdicao" @alternar="alternarAtivo" />
    </CardBase>

    <Pagination v-if="paginacao && paginacao.last_page > 1" :pagination="paginacao" @page-change="(page) => filtrar({ page })" />

    <CursoFormModal :show="modalAberto" :curso="cursoEmEdicao" @fechar="modalAberto = false" />
  </div>
</template>
