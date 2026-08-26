<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Comunidades" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Comunidades"
        description="Comunidades atendidas pelo programa, por municipio"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
        <template #actions>
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="comunidades"
            label="Nova comunidade"
            :allowed="true"
            @click="abrirNovo"
          />
        </template>
      </PageHeader>

      <CollapsibleSection
        namespace="cisterna"
        section-id="filtros-comunidades"
        title="Filtros de pesquisa"
        :icon="FunnelIcon"
        tom="neutro"
      >
        <form class="grid grid-cols-1 gap-3 sm:grid-cols-3" @submit.prevent="pesquisar">
          <FormField v-model="filtro.search" label="Nome da comunidade" />
          <FormSelect v-model="filtro.municipio_id" label="Municipio" :options="municipiosOpcoes" placeholder="Todos" />

          <div class="flex items-end gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input v-model="filtro.apenas_ativas" type="checkbox" class="rounded border-slate-300 dark:border-slate-600">
              <span>Apenas ativas</span>
            </label>
            <button type="submit" :class="BOTAO" class="ml-auto">Pesquisar</button>
          </div>
        </form>
      </CollapsibleSection>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th :class="TH">Comunidade</th>
                <th :class="TH">Municipio</th>
                <th :class="TH">Beneficiarios</th>
                <th :class="TH">Situacao</th>
                <th :class="[TH, 'table-actions-head w-28 min-w-28 text-right']">Opcoes</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
              <tr v-for="c in lista" :key="c.id" class="table-row-solid transition-colors">
                <td :class="TD_FORTE">{{ c.nome }}</td>
                <td :class="TD">{{ nomeUf(c.municipio) }}</td>
                <td :class="TD_MONO">{{ c.beneficiarios ?? 0 }}</td>
                <td :class="TD">
                  <span :class="c.ativa ? PILULA_ATIVA : PILULA_INATIVA">
                    {{ c.ativa ? 'Ativa' : 'Inativa' }}
                  </span>
                </td>
                <!-- Coluna fixa no canto direito: em tela estreita a tabela rola
                     na horizontal e as acoes precisam continuar alcancaveis. Depende
                     de .table-row-solid na <tr> para o fundo opaco. -->
                <td class="table-actions-cell w-28 min-w-28 whitespace-nowrap px-3 py-2 text-right">
                  <div class="flex items-center justify-end">
                    <ActionButton
                      module="cisternas"
                      resource="comunidades"
                      :actions="[
                        { action: 'edit',   handler: () => abrirEdicao(c) },
                        { action: 'delete', handler: () => excluir(c, `a comunidade ${c.nome}`) },
                      ]"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="lista.length === 0">
                <td colspan="5" class="px-3 py-10">
                  <ListEmptyState
                    title="Nenhuma comunidade encontrada"
                    :helper="semMunicipios
                      ? 'Nenhum municipio esta habilitado no programa: rode o import do cedec_municipio.'
                      : 'Ajuste os filtros ou cadastre uma comunidade.'"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <CisternaFormModal
        :show="aberto"
        :titulo="editando ? 'Editar comunidade' : 'Nova comunidade'"
        subtitulo="A comunidade pertence a um municipio: o mesmo nome pode existir em municipios diferentes"
        :icon="MapPinIcon"
        :processando="form.processing"
        @close="fechar"
        @submit="salvar"
      >
        <div class="space-y-3">
          <FormSelect
            v-model="form.municipio_id"
            label="Municipio"
            :options="municipiosOpcoes"
            placeholder="Selecione"
            required
            :error="form.errors.municipio_id"
          />
          <FormField
            v-model="form.nome"
            label="Nome da comunidade"
            maxlength="70"
            required
            :error="form.errors.nome"
          />
          <ToggleField v-model="form.ativa" label="Comunidade ativa" description="Inativa nao aparece na escolha de novos cadastros" />
        </div>
      </CisternaFormModal>

      <!-- Confirmacao pelo dialogo do sistema, igual a Decretacoes, PAE e RAT. -->
      <ConfirmDialog
        :is-open="confirmacao.aberto"
        v-bind="confirmacao.opcoes"
        @confirm="confirmar"
        @cancel="cancelar"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FunnelIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import CisternaFormModal from '@/Components/Organisms/Cisterna/CisternaFormModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useCrudModal } from '@/Composables/cisterna/useCrudModal';

const props = defineProps({
  comunidades: { type: [Object, Array], default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  municipios: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const TH = 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const TD_MONO = 'whitespace-nowrap px-3 py-2 font-mono text-sm text-slate-600 dark:text-slate-300';
const BOTAO = 'rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700';
const PILULA_ATIVA = 'rounded px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
const PILULA_INATIVA = 'rounded px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300';

// `Resource::collection` embrulha em `data` quando e prop de topo.
const lista = computed(() => props.comunidades?.data ?? props.comunidades ?? []);

const semMunicipios = computed(() => (props.municipios?.length ?? 0) === 0);

const municipiosOpcoes = computed(
  () => (props.municipios ?? []).map((m) => ({
    value: m.id,
    label: m.uf ? `${m.nome} / ${m.uf}` : m.nome,
  })),
);

const filtro = reactive({
  search: props.filtros.search ?? '',
  municipio_id: props.filtros.municipio_id ?? '',
  apenas_ativas: Boolean(props.filtros.apenas_ativas),
});

const { aberto, editando, form, abrirNovo, abrirEdicao, fechar, salvar, excluir,
  confirmacao, confirmar, cancelar,
} = useCrudModal(
  'cisternas.comunidades',
  { municipio_id: '', nome: '', ativa: true },
  (c) => ({ municipio_id: c.municipio?.id ?? '', nome: c.nome, ativa: Boolean(c.ativa) }),
);

function pesquisar() {
  router.get(route('cisternas.comunidades.index'), limpar(filtro), {
    preserveState: true,
    replace: true,
  });
}

function limpar(valores) {
  const query = {};

  Object.entries(valores).forEach(([chave, valor]) => {
    if (valor === '' || valor === false || valor === null || valor === undefined) return;

    query[chave] = valor === true ? 1 : valor;
  });

  return query;
}

function nomeUf(municipio) {
  if (!municipio) return '—';

  return municipio.uf ? `${municipio.nome} / ${municipio.uf}` : municipio.nome;
}
</script>
