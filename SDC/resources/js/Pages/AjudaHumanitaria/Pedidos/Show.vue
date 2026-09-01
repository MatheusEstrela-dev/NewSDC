<template>
  <AuthenticatedLayout>
    <Head :title="`Pedido ${dados.identificador}`" />

    <PageHeader
      :title="`Pedido ${dados.identificador}`"
      :description="dados.municipio?.nome ?? 'Pedido de ajuda humanitária'"
      :icon="HeartIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    >
      <template #actions>
        <PedidoAhStatusBadge
          :status="dados.status"
          :label="dados.status_label"
          :cor="dados.status_cor"
        />
      </template>
    </PageHeader>

    <!-- Abas espelhando o legado: Dados, Materiais, Documentos e
         Despachos/Analises, mais Tramitacao. Prestacao de contas entra na
         proxima etapa. -->
    <!--
      Abas no desenho padrao do projeto (ModuleTabs, nascido no RAT): trilho
      arredondado, aba ativa como pilula com borda inferior, inativas so com
      icone abaixo de `sm`, e fades indicando que a tira rola. A tira crua de
      antes cortava "Documentos" na borda sem nenhuma pista de que havia mais.
    -->
    <ModuleTabs
      class="mt-6"
      :tabs="abasModulo"
      :active-tab="abaAtiva"
      @tab-change="(chave) => (abaAtiva = chave)"
    />

    <div class="mt-6">
      <!-- Dados -->
      <div v-show="abaAtiva === 'dados'" class="grid gap-6 lg:grid-cols-3">
        <FormSection class="lg:col-span-2" title="Dados do Pedido" :icon="DocumentTextIcon">
          <dl class="grid gap-4 sm:grid-cols-2">
            <LinhaDado rotulo="Município" :valor="dados.municipio?.nome" />
            <LinhaDado rotulo="População atendida" :valor="formatarNumero(dados.pop_atendida)" />
            <LinhaDado rotulo="Fase" :valor="dados.fase_label" />
            <LinhaDado rotulo="Entrada no sistema" :valor="formatarData(dados.data_entrada_sistema)" />
            <LinhaDado rotulo="Enviado em" :valor="formatarData(dados.data_hora_envio)" />
            <LinhaDado rotulo="Aprovado em" :valor="formatarData(dados.data_aprovacao)" />
          </dl>

          <div class="mt-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Esforços realizados
            </p>
            <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
              {{ dados.esforcos_realizados || '—' }}
            </p>
          </div>
        </FormSection>

        <FormSection title="Decreto" :icon="ClipboardIcon">
          <dl class="grid gap-4">
            <LinhaDado rotulo="Vigente" :valor="dados.decreto?.vigente ? 'Sim' : 'Não'" />
            <LinhaDado rotulo="Tipo" :valor="dados.decreto?.tipo_label" />
            <LinhaDado rotulo="Número" :valor="dados.decreto?.numero" />
            <LinhaDado rotulo="Vigência" :valor="formatarData(dados.decreto?.vigencia)" />
          </dl>
        </FormSection>

        <FormSection title="Coordenador da COMPDEC" :icon="UsersIcon">
          <dl class="grid gap-4">
            <LinhaDado rotulo="Nome" :valor="dados.coordenador?.nome" />
            <LinhaDado rotulo="E-mail" :valor="dados.coordenador?.email" />
            <LinhaDado rotulo="Telefone" :valor="dados.coordenador?.telefone" />
            <LinhaDado rotulo="Celular" :valor="dados.coordenador?.celular" />
          </dl>
        </FormSection>

        <FormSection title="Prefeito" :icon="BuildingIcon">
          <dl class="grid gap-4">
            <LinhaDado rotulo="Nome" :valor="dados.prefeito?.nome" />
            <LinhaDado rotulo="E-mail" :valor="dados.prefeito?.email" />
            <LinhaDado rotulo="Telefone" :valor="dados.prefeito?.telefone" />
            <LinhaDado rotulo="Celular" :valor="dados.prefeito?.celular" />
          </dl>
        </FormSection>
      </div>

      <!-- Materiais -->
      <FormSection v-show="abaAtiva === 'materiais'" title="Materiais" :icon="CubeIcon">
        <PedidoAhItensTab
          :itens-solicitados="dados.itens_solicitados ?? []"
          :itens-liberados="dados.itens_liberados ?? []"
          :materiais="materiais"
          :status="dados.status"
          :can-edit="canEdit"
          :can-liberar-itens="canLiberarItens"
          @incluir-item="incluirItem"
          @remover-item="removerItem"
        />
      </FormSection>

      <!-- Documentos -->
      <FormSection v-show="abaAtiva === 'documentos'" title="Documentos" :icon="DocumentTextIcon">
        <PedidoAhAnexosTab
          :anexos="anexos"
          :pedido-id="dados.id"
          :can-anexos="canAnexos"
          @anexar="anexar"
          @remover-anexo="removerAnexo"
        />
      </FormSection>

      <!-- Pareceres -->
      <FormSection v-show="abaAtiva === 'pareceres'" title="Pareceres" :icon="ClipboardIcon">
        <PedidoAhPareceresTab
          :pareceres="pareceres"
          :situacoes="situacoesParecer"
          :etapas="etapasParecer"
          :can-parecer="canParecer"
          @emitir-parecer="emitirParecer"
          @remover-parecer="removerParecer"
        />
      </FormSection>

      <!-- Tramitação -->
      <FormSection v-show="abaAtiva === 'tramitacao'" title="Tramitação" :icon="ArrowsRightLeftIcon">
        <PedidoAhTramitacaoTab
          :tramites="tramites"
          :destinos="destinos"
          :can-tramitar="canTramitar"
          @tramitar="tramitar"
        />
      </FormSection>

      <!-- Prestação de Contas (RN-20: oculta para o perfil regional) -->
      <FormSection
        v-if="canVerPrestacao"
        v-show="abaAtiva === 'prestacao'"
        title="Prestação de Contas"
        :icon="CheckCircleIcon"
      >
        <PedidoAhPrestacaoTab
          :prestacao="prestacao"
          :can-lancar="canLancarEntrega"
          :can-homologar="canHomologar"
          @lancar-entrega="lancarEntrega"
          @remover-entrega="removerEntrega"
          @homologar="homologar"
        />
      </FormSection>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import PedidoAhItensTab from '@/Components/Organisms/AjudaHumanitaria/PedidoAhItensTab.vue';
import PedidoAhAnexosTab from '@/Components/Organisms/AjudaHumanitaria/PedidoAhAnexosTab.vue';
import PedidoAhPareceresTab from '@/Components/Organisms/AjudaHumanitaria/PedidoAhPareceresTab.vue';
import PedidoAhPrestacaoTab from '@/Components/Organisms/AjudaHumanitaria/PedidoAhPrestacaoTab.vue';
import PedidoAhTramitacaoTab from '@/Components/Organisms/AjudaHumanitaria/PedidoAhTramitacaoTab.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  pedido: { type: Object, required: true },
  tramites: { type: Array, default: () => [] },
  pareceres: { type: Array, default: () => [] },
  anexos: { type: Array, default: () => [] },
  situacoesParecer: { type: Array, default: () => [] },
  etapasParecer: { type: Array, default: () => [] },
  materiais: { type: Array, default: () => [] },
  destinos: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canTramitar: { type: Boolean, default: false },
  canLiberarItens: { type: Boolean, default: false },
  canParecer: { type: Boolean, default: false },
  canAnexos: { type: Boolean, default: false },
  prestacao: { type: Object, default: null },
  canVerPrestacao: { type: Boolean, default: false },
  canLancarEntrega: { type: Boolean, default: false },
  canHomologar: { type: Boolean, default: false },
});

// O Resource do Laravel embrulha em data quando devolvido isoladamente.
const dados = computed(() => props.pedido?.data ?? props.pedido ?? {});

const abaAtiva = ref('dados');

const abas = computed(() => [
  { chave: 'dados', rotulo: 'Dados do Pedido', contador: null },
  {
    chave: 'materiais',
    rotulo: 'Materiais',
    contador: (dados.value.itens_solicitados?.length ?? 0) + (dados.value.itens_liberados?.length ?? 0),
  },
  { chave: 'documentos', rotulo: 'Documentos', contador: props.anexos.length },
  { chave: 'pareceres', rotulo: 'Pareceres', contador: props.pareceres.length },
  { chave: 'tramitacao', rotulo: 'Tramitação', contador: props.tramites.length },
  ...(props.canVerPrestacao
    ? [{
        chave: 'prestacao',
        rotulo: 'Prestação de Contas',
        contador: props.prestacao?.itens?.length ?? null,
      }]
    : []),
]);

/**
 * As abas no formato do ModuleTabs.
 *
 * `contador` vira `badge`, e zero/null nao viram badge nenhum -- um "0" ao lado
 * do nome so ocupa a largura que falta no telefone. O icone e o MESMO da secao
 * correspondente abaixo, para a aba continuar reconhecivel quando o rotulo some
 * no mobile.
 */
const ICONES_DAS_ABAS = {
  dados: DocumentTextIcon,
  materiais: CubeIcon,
  documentos: DocumentTextIcon,
  pareceres: ClipboardIcon,
  tramitacao: ArrowsRightLeftIcon,
  prestacao: CheckCircleIcon,
};

const abasModulo = computed(() =>
  abas.value.map((aba) => ({
    id: aba.chave,
    label: aba.rotulo,
    icon: ICONES_DAS_ABAS[aba.chave] ?? DocumentTextIcon,
    badge: aba.contador || null,
  })),
);

const opcoesPadrao = { preserveScroll: true, preserveState: false };

function incluirItem(payload) {
  router.post(route('ajuda-humanitaria.pedidos.itens.store', dados.value.id), payload, opcoesPadrao);
}

function removerItem(itemId) {
  router.delete(
    route('ajuda-humanitaria.pedidos.itens.destroy', [dados.value.id, itemId]),
    opcoesPadrao,
  );
}

function tramitar(payload) {
  router.post(route('ajuda-humanitaria.pedidos.tramitar', dados.value.id), payload, opcoesPadrao);
}

function emitirParecer(payload) {
  router.post(route('ajuda-humanitaria.pedidos.pareceres.store', dados.value.id), payload, opcoesPadrao);
}

function removerParecer(parecerId) {
  router.delete(
    route('ajuda-humanitaria.pedidos.pareceres.destroy', [dados.value.id, parecerId]),
    opcoesPadrao,
  );
}

function anexar(arquivo) {
  router.post(
    route('ajuda-humanitaria.pedidos.anexos.store', dados.value.id),
    { arquivo },
    { ...opcoesPadrao, forceFormData: true },
  );
}

function removerAnexo(mediaId) {
  router.delete(
    route('ajuda-humanitaria.pedidos.anexos.destroy', [dados.value.id, mediaId]),
    opcoesPadrao,
  );
}

function lancarEntrega(payload) {
  router.post(route('ajuda-humanitaria.pedidos.entregas.store', dados.value.id), payload, opcoesPadrao);
}

function removerEntrega(entregaId) {
  router.delete(
    route('ajuda-humanitaria.pedidos.entregas.destroy', [dados.value.id, entregaId]),
    opcoesPadrao,
  );
}

function homologar() {
  router.post(route('ajuda-humanitaria.pedidos.homologar', dados.value.id), {}, opcoesPadrao);
}

function formatarNumero(valor) {
  return typeof valor === 'number' ? valor.toLocaleString('pt-BR') : '—';
}

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}

/** Par rotulo/valor do painel de detalhe. */
const LinhaDado = (props) =>
  h('div', {}, [
    h(
      'dt',
      { class: 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400' },
      props.rotulo,
    ),
    h('dd', { class: 'mt-0.5 text-sm text-slate-800 dark:text-slate-100' }, props.valor || '—'),
  ]);
LinhaDado.props = ['rotulo', 'valor'];
</script>
