<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Beneficiarios" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Gestao de Cisternas"
        description="Cadastro de beneficiarios e fiscalizacao da instalacao em tres etapas"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
        <template #actions>
          <ActionButton
            v-if="permissoes.exportar"
            action="export"
            variant="success"
            label="Exportar"
            :allowed="true"
            @click="openExportModal"
          />
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="beneficiarios"
            label="Novo cadastro"
            :allowed="true"
            @click="router.visit(route('cisternas.beneficiarios.create'))"
          />
        </template>
      </PageHeader>

      <CisternaStatisticsCards
        v-if="indicadores"
        :indicadores="indicadores"
        @filter="filtrarPorCard"
      />

      <BeneficiarioFiltersSection
        :filtros="filtros"
        :municipios="opcoes.municipios ?? []"
        :situacoes-analise="opcoes.situacoes_analise ?? []"
        :situacoes-obra="opcoes.situacoes_obra ?? []"
        :etapas-vistoria="opcoes.etapas_vistoria ?? []"
        @apply="aplicar"
        @clear="limpar"
      />

      <BeneficiariosTable
        v-model:marcados="marcados"
        :beneficiarios="beneficiarios.data ?? []"
        :selecionavel="perfil.e_cedec || perfil.e_compdec"
        :permissoes="permissoes"
        :ordenado-por="filtros.sort || 'nome'"
        :direcao="filtros.direction || 'asc'"
        @excluir="confirmarExclusao"
        @historico="abrirHistorico"
        @imprimir="abrirImpressao"
        @pdf="abrirImpressao"
        @qrcode="abrirQrCode"
        @ordenar="ordenar"
      />

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />

      <!--
        Mesmo modal do RAT e do PMDA. O recorte por periodo que ele oferece so
        vale porque o export do Cisterna passou a ler data_inicio/data_fim: modal
        com data que o backend ignora e uma tela que mente.
      -->
      <!--
        Serie historica no molde do PAE. O modal busca sozinho ao abrir, entao
        reflete o estado do momento: a listagem fica aberta por muito tempo e uma
        etapa concluida por outro orgao nesse intervalo apareceria aqui.
      -->
      <BeneficiarioHistoricoModal
        :show="historicoAberto"
        :beneficiario="beneficiarioDoHistorico"
        @close="historicoAberto = false"
      />

      <!--
        Ficha para impressao, no BasePrintModal que Decretacoes e Ajuda
        Humanitaria usam. So aparece com a cadeia de fiscalizacao completa.
      -->
      <BeneficiarioPrintModal
        :show="impressaoAberta"
        :beneficiario="beneficiarioDaImpressao"
        @close="impressaoAberta = false"
      />

      <!-- Adesivo do QR Code, no molde do legado: ver, baixar e imprimir. -->
      <BeneficiarioQrCodeModal
        :show="qrCodeAberto"
        :beneficiario="beneficiarioDoQrCode"
        @close="qrCodeAberto = false"
      />

      <!-- Confirmacao pelo dialogo do sistema, igual a Decretacoes, PAE e RAT. -->
      <ConfirmDialog
        :is-open="confirmacao.aberto"
        v-bind="confirmacao.opcoes"
        @confirm="confirmar"
        @cancel="cancelar"
      />

      <ExportCsvModal
        :show="showExportModal"
        module-name="Cisternas"
        @close="closeExportModal"
        @export="aoExportar"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/Composables/data/useExport';
import { moduleIcon } from '@/Support/moduleIcons';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import CisternaStatisticsCards from '@/Components/Organisms/Cisterna/CisternaStatisticsCards.vue';
import BeneficiarioFiltersSection from '@/Components/Organisms/Cisterna/BeneficiarioFiltersSection.vue';
import BeneficiariosTable from '@/Components/Organisms/Cisterna/BeneficiariosTable.vue';
import BeneficiarioHistoricoModal from '@/Components/Organisms/Cisterna/BeneficiarioHistoricoModal.vue';
import BeneficiarioPrintModal from '@/Components/Organisms/Cisterna/BeneficiarioPrintModal.vue';
import BeneficiarioQrCodeModal from '@/Components/Organisms/Cisterna/BeneficiarioQrCodeModal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';

const props = defineProps({
  beneficiarios: { type: Object, required: true },
  indicadores: { type: Object, default: null },
  filtros: { type: Object, default: () => ({}) },
  opcoes: { type: Object, default: () => ({}) },
  perfil: { type: Object, default: () => ({}) },
  permissoes: { type: Object, default: () => ({}) },
});

const marcados = ref([]);

const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

const historicoAberto = ref(false);
const beneficiarioDoHistorico = ref(null);

function abrirHistorico(beneficiario) {
  beneficiarioDoHistorico.value = beneficiario;
  historicoAberto.value = true;
}

const impressaoAberta = ref(false);
const beneficiarioDaImpressao = ref(null);

function abrirImpressao(beneficiario) {
  beneficiarioDaImpressao.value = beneficiario;
  impressaoAberta.value = true;
}

const qrCodeAberto = ref(false);
const beneficiarioDoQrCode = ref(null);

function abrirQrCode(beneficiario) {
  beneficiarioDoQrCode.value = beneficiario;
  qrCodeAberto.value = true;
}

const paginacao = computed(() => {
  const m = props.beneficiarios?.meta;

  if (!m) return null;

  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 25,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

const { showExportModal, openExportModal, closeExportModal, handleExport } =
  useExport('cisternas.beneficiarios.export');

/**
 * O escopo do modal (periodo ou serie inteira) soma aos filtros da tela: a
 * planilha nunca deve mostrar mais do que a listagem, e o escopo territorial do
 * perfil e reaplicado no servidor.
 */
function aoExportar(params) {
  handleExport(params, paraQuery(props.filtros));
}

/**
 * Filtro vindo de stat card SUBSTITUI os filtros de eixo, em vez de somar.
 *
 * Clicar em "Aprovados" tem que mostrar os aprovados, nao a intersecao com o que
 * ja estava filtrado antes -- foi assim que o menu do legado funcionava, e e o
 * que a convencao de card-como-atalho quer dizer. O que se preserva e a busca
 * textual, que e refinamento e nao eixo.
 */
function filtrarPorCard(filtro) {
  const preservado = {
    search: props.filtros.search,
    cpf: props.filtros.cpf,
    numero_instalacao: props.filtros.numero_instalacao,
    municipio_id: props.filtros.municipio_id,
    // Ordenacao sobrevive ao clique no card: trocar o recorte da lista nao e
    // motivo para jogar fora a ordem que o usuario escolheu no cabecalho.
    sort: props.filtros.sort,
    direction: props.filtros.direction,
  };

  buscar({ ...preservado, ...filtro });
}

/**
 * Exclusao e soft delete no dominio, mas o usuario nao sabe disso: confirmar e o
 * que evita perder um cadastro por clique errado numa lista de 8.096 linhas.
 */
function confirmarExclusao(beneficiario) {
  pedirConfirmacao(
    {
      title: 'Remover cadastro',
      message: `Remover o cadastro de ${beneficiario.nome}?`,
      description: 'O cadastro sai das listagens e continua guardado para auditoria, com as vistorias e notificacoes dele.',
      variant: 'danger',
      confirmText: 'Remover',
    },
    () => router.delete(route('cisternas.beneficiarios.destroy', beneficiario.id), {
      preserveScroll: true,
    }),
  );
}

function irParaPagina(page) {
  buscar({ ...props.filtros, page });
}

function aplicar(filtros) {
  buscar(filtros);
}

/**
 * Ordenacao e no banco, nao no cliente: a listagem e paginada em 25, e ordenar
 * no front reordenaria apenas a pagina visivel.
 *
 * Preserva os filtros vigentes -- trocar a ordem de uma lista ja filtrada nao
 * deve descartar o filtro -- e a coluna e validada contra a whitelist do
 * BeneficiarioService antes de virar ORDER BY.
 */
function ordenar({ sort, direction }) {
  buscar({ ...props.filtros, sort, direction });
}

function limpar() {
  buscar({});
}

/**
 * Reload PARCIAL: `indicadores` fica fora do `only`.
 *
 * No controller ele e uma closure, e closure de prop no Inertia e avaliada em
 * TODA visita completa -- ou seja, sem isto os 11 contadores (que fazem
 * agregacao sobre 8.096 linhas com FILTER e tres subconsultas de etapa) seriam
 * recalculados a cada troca de filtro, sem precisar: eles medem o escopo do
 * perfil, nao o resultado filtrado.
 */
function buscar(filtros) {
  marcados.value = [];

  router.get(route('cisternas.beneficiarios.index'), paraQuery(filtros), {
    only: ['beneficiarios', 'filtros'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Tira o que esta vazio, para a URL nao encher de parametro sem valor, e manda
 * booleano como 1/0 -- que e o que o controller le.
 */
function paraQuery(filtros) {
  const query = {};

  Object.entries(filtros ?? {}).forEach(([chave, valor]) => {
    if (valor === undefined || valor === null || valor === '') return;
    if (Array.isArray(valor) && valor.length === 0) return;
    if (valor === false) return;

    query[chave] = valor === true ? 1 : valor;
  });

  return query;
}
</script>
