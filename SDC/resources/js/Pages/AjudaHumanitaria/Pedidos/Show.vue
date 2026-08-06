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

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
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

      <FormSection class="lg:col-span-3" title="Materiais" :icon="CubeIcon">
        <div class="grid gap-6 md:grid-cols-2">
          <TabelaItens titulo="Solicitado pelo município" :itens="dados.itens_solicitados ?? []" />
          <TabelaItens titulo="Liberado pelo CEDEC" :itens="dados.itens_liberados ?? []" />
        </div>
      </FormSection>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, h } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PedidoAhStatusBadge from '@/Components/Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  pedido: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

// O Resource do Laravel embrulha em data quando devolvido isoladamente.
const dados = computed(() => props.pedido?.data ?? props.pedido ?? {});

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

/** Tabela simples de itens, usada duas vezes: solicitado e liberado. */
const TabelaItens = (props) => {
  if (!props.itens?.length) {
    return h('div', {}, [
      h('p', { class: 'mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200' }, props.titulo),
      h(ListEmptyState, { title: 'Nenhum material lançado' }),
    ]);
  }

  return h('div', {}, [
    h('p', { class: 'mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200' }, props.titulo),
    h('table', { class: 'min-w-full text-sm' }, [
      h('thead', {}, [
        h('tr', { class: 'text-left text-xs uppercase text-slate-500 dark:text-slate-400' }, [
          h('th', { class: 'py-2' }, 'Material'),
          h('th', { class: 'py-2 text-right' }, 'Qtd'),
          h('th', { class: 'py-2 text-right' }, 'Famílias'),
        ]),
      ]),
      h(
        'tbody',
        {},
        props.itens.map((item) =>
          h('tr', { key: item.id, class: 'border-t border-slate-100 dark:border-slate-800' }, [
            h('td', { class: 'py-2 text-slate-700 dark:text-slate-200' }, item.descricao_item),
            h('td', { class: 'py-2 text-right text-slate-700 dark:text-slate-200' }, item.qtd),
            h('td', { class: 'py-2 text-right text-slate-700 dark:text-slate-200' }, item.qtd_familia_atendida),
          ]),
        ),
      ),
    ]),
  ]);
};
TabelaItens.props = ['titulo', 'itens'];
</script>
