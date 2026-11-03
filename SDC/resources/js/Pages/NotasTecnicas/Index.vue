<template>
  <Head title="Notas Técnicas" />

  <NotasTecnicasWorkspaceTemplate
    :pastas-abertas="pastasAbertas"
    :informacoes-abertas="informacoesAbertas"
  >
    <template #cabecalho>
      <PageHeader
        title="Notas Técnicas"
        description="Notas técnicas e procedimentos operacionais padrão em PDF"
        :icon-image="moduleIcon('notas-tecnicas') ?? ''"
        variant="gradient"
        :espaco-inferior="false"
      >
        <template #actions>
          <div v-if="isDesktop" class="flex flex-wrap items-center gap-2 sm:gap-3">
            <ButtonIcon
              :icon="FolderIcon"
              :title="pastasAbertas ? 'Recolher pastas' : 'Mostrar pastas'"
              :variant="pastasAbertas ? 'primary' : 'secondary'"
              @click="alternarPastas"
            />
            <ButtonIcon
              :icon="InformationCircleIcon"
              :title="informacoesAbertas ? 'Recolher informações' : 'Mostrar informações'"
              :variant="informacoesAbertas ? 'primary' : 'secondary'"
              @click="alternarInformacoes"
            />
          </div>
        </template>
      </PageHeader>
    </template>

    <template #estatisticas>
      <NotaTecnicaStatCards :estatisticas="estatisticas" @filter="filtrarPorCard" />
    </template>

    <template #pastas>
      <NotaTecnicaCategoriaArvore
        :categorias="categorias"
        :categoria-id="filtros.categoria_id ?? null"
        @select="filtrarPorPasta"
      />
    </template>

    <template #lista>
      <NotaTecnicaLista
        :notas="notas"
        :busca="filtros.busca ?? ''"
        :nota-id="selecionada?.id ?? null"
        :versao-id="selecionada?.versao?.id ?? null"
        :versoes="selecionada?.versoes ?? []"
        @select-nota="abrirNota"
        @select-versao="abrirVersao"
        @buscar="buscarTexto"
        @page="irParaPagina"
      />
    </template>

    <template #leitor>
      <NotaTecnicaLeitor :nota="selecionada" :embutir="isDesktop" />
    </template>

    <template #informacoes>
      <NotaTecnicaMetadadosPainel :nota="selecionada">
        <template v-if="isDesktop" #acoes>
          <ButtonIcon :icon="XMarkIcon" title="Recolher informações" size="sm" @click="alternarInformacoes" />
        </template>
      </NotaTecnicaMetadadosPainel>
    </template>
  </NotasTecnicasWorkspaceTemplate>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { FolderIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import NotaTecnicaStatCards from '@/Components/Organisms/NotasTecnicas/NotaTecnicaStatCards.vue';
import NotaTecnicaCategoriaArvore from '@/Components/Organisms/NotasTecnicas/NotaTecnicaCategoriaArvore.vue';
import NotaTecnicaLista from '@/Components/Organisms/NotasTecnicas/NotaTecnicaLista.vue';
import NotaTecnicaLeitor from '@/Components/Organisms/NotasTecnicas/NotaTecnicaLeitor.vue';
import NotaTecnicaMetadadosPainel from '@/Components/Organisms/NotasTecnicas/NotaTecnicaMetadadosPainel.vue';
import NotasTecnicasWorkspaceTemplate from '@/Templates/NotasTecnicas/NotasTecnicasWorkspaceTemplate.vue';
import { useMobile } from '@/Composables/useMobile';
import { useNotaTecnicaWorkspace } from '@/Composables/notasTecnicas/useNotaTecnicaWorkspace';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  notas: {
    type: Object,
    default: () => ({ data: [], current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null }),
  },
  filtros: { type: Object, default: () => ({}) },
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, nao_lidas: 0, revogadas: 0, rascunhos: null }),
  },
  categorias: { type: Array, default: () => [] },
  selecionada: { type: Object, default: null },
  permissoes: { type: Object, default: () => ({}) },
});

const { isDesktop } = useMobile();
const { pastasAbertas, informacoesAbertas, alternarPastas, alternarInformacoes } = useNotaTecnicaWorkspace();

/** Nota aberta, lida da URL atual: e ela que sobrevive a troca de filtro. */
function selecaoAtual() {
  const query = new URLSearchParams(window.location.search);

  return { nota: query.get('nota') ?? '', versao: query.get('versao') ?? '' };
}

/**
 * Trocar filtro recarrega a lista e os contadores da pasta, mas mantem a nota
 * aberta no leitor: o PDF nao some porque a pessoa mudou de pasta.
 * estatisticas fica fora: mede o acervo inteiro, nao o recorte.
 */
function filtrar(filtros) {
  router.get(route('notas-tecnicas.index'), paraQuery({ ...filtros, ...selecaoAtual() }), {
    only: ['notas', 'filtros', 'categorias'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Abrir nota so recarrega `selecionada`: lista, pastas e contadores continuam
 * os mesmos. Sem `replace`: cada nota aberta entra no historico, e o voltar do
 * navegador volta para a nota anterior.
 */
function selecionar(nota, versao = '') {
  router.get(route('notas-tecnicas.index'), paraQuery({ ...props.filtros, nota, versao }), {
    only: ['selecionada'],
    preserveState: true,
    preserveScroll: true,
  });
}

function abrirNota(id) {
  selecionar(id);

  // Abaixo de 2xl as informacoes flutuam sobre o PDF; no telefone sao o unico
  // jeito de ver os metadados. Em tela larga a coluna ja esta onde estava.
  if (!isDesktop.value) informacoesAbertas.value = true;
}

function abrirVersao(id) {
  selecionar(props.selecionada?.id ?? '', id);
}

/** Card substitui o eixo de status; busca e pasta continuam (sao refinamento). */
function filtrarPorCard(chave) {
  const base = { busca: props.filtros.busca, categoria_id: props.filtros.categoria_id };

  if (chave === 'nao_lidas') {
    filtrar({ ...base, nao_lidas: 1 });
    return;
  }

  filtrar({ ...base, status: chave ?? '' });
}

function filtrarPorPasta(categoriaId) {
  filtrar({ ...props.filtros, categoria_id: categoriaId ?? '', page: '' });
}

function buscarTexto(busca) {
  filtrar({ ...props.filtros, busca, page: '' });
}

function irParaPagina(pagina) {
  filtrar({ ...props.filtros, page: pagina });
}

/**
 * Tira o vazio e o falso, para a URL nao encher de parametro sem valor e o
 * NotaTecnicaFiltroDTO receber null em vez de string vazia.
 */
function paraQuery(filtros) {
  const query = {};

  Object.entries(filtros ?? {}).forEach(([chave, valor]) => {
    if (valor === undefined || valor === null || valor === '' || valor === false) return;
    if (chave === 'per_page' && Number(valor) === 25) return;

    query[chave] = valor === true ? 1 : valor;
  });

  return query;
}
</script>
