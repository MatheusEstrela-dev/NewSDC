<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Lotes" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Lotes"
        description="Agrupamento das instalacoes; cada lote reune ordens de servico"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="lotes"
            label="Novo lote"
            :allowed="true"
            @click="abrirNovo"
          />
        </template>
      </PageHeader>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-800/70">
              <tr>
                <th :class="TH">Lote</th>
                <th :class="TH">Data</th>
                <th :class="TH">Ordens de servico</th>
                <th :class="TH">Observacao</th>
                <th :class="TH">Opcoes</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
              <tr v-for="l in lista" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td :class="TD_FORTE">{{ l.nome }}</td>
                <td :class="TD">{{ dataBr(l.data) }}</td>
                <td :class="TD_MONO">{{ l.ordens_servico ?? 0 }}</td>
                <td class="max-w-xs truncate px-3 py-2 text-sm text-slate-600 dark:text-slate-300" :title="l.observacao">
                  {{ l.observacao || '—' }}
                </td>
                <td class="whitespace-nowrap px-3 py-2 text-right">
                  <TableActions
                    module="cisternas"
                    resource="lotes"
                    :show-view="true"
                    :show-edit="true"
                    :show-delete="true"
                    @view="verOrdens(l)"
                    @edit="abrirEdicao(l)"
                    @delete="excluir(l, `o lote ${l.nome}`)"
                  />
                </td>
              </tr>

              <tr v-if="lista.length === 0">
                <td colspan="5" class="px-3 py-10">
                  <ListEmptyState
                    title="Nenhum lote cadastrado"
                    helper="O lote agrupa as ordens de servico da instalacao."
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <CisternaFormModal
        :show="aberto"
        :titulo="editando ? 'Editar lote' : 'Novo lote'"
        subtitulo="O lote agrupa ordens de servico; os beneficiarios entram pela ordem"
        :icon="RectangleStackIcon"
        :processando="form.processing"
        @close="fechar"
        @submit="salvar"
      >
        <div class="space-y-3">
          <FormField v-model="form.nome" label="Nome do lote" maxlength="255" required :error="form.errors.nome" />
          <FormDateField v-model="form.data" label="Data" :error="form.errors.data" />
          <FormTextarea v-model="form.observacao" label="Observacao" :rows="3" maxlength="1000" :error="form.errors.observacao" />
        </div>
      </CisternaFormModal>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { RectangleStackIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import CisternaFormModal from '@/Components/Organisms/Cisterna/CisternaFormModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useCrudModal } from '@/Composables/cisterna/useCrudModal';

const props = defineProps({
  lotes: { type: [Object, Array], default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const TH = 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const TD_MONO = 'whitespace-nowrap px-3 py-2 font-mono text-sm text-slate-600 dark:text-slate-300';

const lista = computed(() => props.lotes?.data ?? props.lotes ?? []);

const { aberto, editando, form, abrirNovo, abrirEdicao, fechar, salvar, excluir } = useCrudModal(
  'cisternas.lotes',
  { nome: '', data: '', observacao: '' },
  (l) => ({ nome: l.nome, data: l.data ?? '', observacao: l.observacao ?? '' }),
);

/** O olho leva as ordens DESTE lote: e o caminho natural a partir da lista. */
function verOrdens(lote) {
  router.visit(route('cisternas.ordens-servico.do-lote', lote.id));
}

function dataBr(iso) {
  if (!iso) return '—';

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : iso;
}
</script>
