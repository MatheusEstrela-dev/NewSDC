<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Ordens de servico" />

    <div class="w-full space-y-6 pb-8">
      <PageHeader
        title="Ordens de servico"
        :description="lote ? `Do lote ${lote.nome}` : 'Todas as ordens; cada uma pertence a um lote'"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
        <template #actions>
          <Link v-if="lote" :href="route('cisternas.lotes.index')" :class="BOTAO_SEC">Todos os lotes</Link>
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="ordens-servico"
            label="Nova ordem"
            :allowed="true"
            @click="novaOrdem"
          />
        </template>
      </PageHeader>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th :class="TH">Ordem</th>
                <th :class="TH">Lote</th>
                <th :class="TH">Beneficiarios</th>
                <th :class="TH">Documento</th>
                <th :class="[TH, 'table-actions-head w-36 min-w-36 text-right']">Opcoes</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
              <tr v-for="o in lista" :key="o.id" class="table-row-solid transition-colors">
                <td :class="TD_FORTE">{{ o.nome }}</td>
                <td :class="TD">{{ o.lote?.nome ?? '—' }}</td>
                <td :class="TD_MONO">{{ o.beneficiarios ?? 0 }}</td>
                <td :class="TD">
                  <!--
                    Duas origens diferentes: o legado guardava em `link_doc` tanto
                    URL do SEI quanto caminho de arquivo. Na migracao isso virou
                    coluna propria (documento_url) e anexo do MediaLibrary, e a
                    tela mostra os dois quando existem.
                  -->
                  <a v-if="o.documento_url" :href="o.documento_url" target="_blank" rel="noopener" :class="ELO">
                    Link externo
                  </a>
                  <a v-if="o.documento_anexo" :href="o.documento_anexo" target="_blank" rel="noopener" :class="ELO">
                    Arquivo
                  </a>
                  <span v-if="!o.documento_url && !o.documento_anexo" class="text-slate-400">—</span>
                </td>
                <!-- Coluna fixa no canto direito: em tela estreita a tabela rola
                     na horizontal e as acoes precisam continuar alcancaveis. Depende
                     de .table-row-solid na <tr> para o fundo opaco. -->
                <td class="table-actions-cell w-36 min-w-36 whitespace-nowrap px-3 py-2 text-right">
                  <div class="flex items-center justify-end">
                    <ActionButton
                      module="cisternas"
                      resource="ordens-servico"
                      :actions="[
                        { action: 'edit',    handler: () => abrirEdicao(o) },
                        { action: 'history', handler: () => verTimeline(o) },
                        { action: 'delete',  handler: () => excluir(o, `a ordem ${o.nome}`) },
                      ]"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="lista.length === 0">
                <td colspan="5" class="px-3 py-10">
                  <ListEmptyState
                    title="Nenhuma ordem de servico"
                    helper="A ordem aloca os beneficiarios de um lote para instalacao."
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <OrdemServicoTimelineModal
        :show="timelineAberta"
        :ordem="ordemDaTimeline"
        @close="timelineAberta = false"
      />

      <CisternaFormModal
        :show="aberto"
        :titulo="editando ? 'Editar ordem de servico' : 'Nova ordem de servico'"
        subtitulo="O documento pode ser um link do SEI ou um arquivo anexado"
        :icon="ClipboardDocumentListIcon"
        :processando="form.processing"
        @close="fechar"
        @submit="salvar"
      >
        <div class="space-y-3">
          <FormSelect
            v-model="form.lote_id"
            label="Lote"
            :options="lotesOpcoes"
            placeholder="Selecione"
            required
            :error="form.errors.lote_id"
          />
          <FormField v-model="form.nome" label="Nome da ordem" maxlength="255" required :error="form.errors.nome" />
          <FormField
            v-model="form.documento_url"
            label="Link do documento"
            :error="form.errors.documento_url"
            hint="URL completa, do SEI por exemplo"
          />
          <ArquivoField
            label="Anexar documento"
            :error="form.errors.documento_os"
            @change="(f) => anexar({ campo: 'documento_os', arquivo: f })"
          />
          <FormTextarea v-model="form.observacao" label="Observacao" :rows="3" maxlength="1000" :error="form.errors.observacao" />
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
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ArquivoField from '@/Components/Molecules/Cisterna/ArquivoField.vue';
import CisternaFormModal from '@/Components/Organisms/Cisterna/CisternaFormModal.vue';
import OrdemServicoTimelineModal from '@/Components/Organisms/Cisterna/OrdemServicoTimelineModal.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useCrudModal } from '@/Composables/cisterna/useCrudModal';

const props = defineProps({
  ordens: { type: [Object, Array], default: () => [] },
  lote: { type: Object, default: null },
  lotes: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const TH = 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const TD_MONO = 'whitespace-nowrap px-3 py-2 font-mono text-sm text-slate-600 dark:text-slate-300';
const ELO = 'mr-2 text-blue-700 hover:underline dark:text-blue-300';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

const lista = computed(() => props.ordens?.data ?? props.ordens ?? []);

const timelineAberta = ref(false);
const ordemDaTimeline = ref(null);

const lotesOpcoes = computed(
  () => (props.lotes ?? []).map((l) => ({ value: l.id, label: l.nome })),
);

// `comArquivo`: a ordem aceita anexo, e ai o update precisa de POST + _method.
const { aberto, editando, form, abrirNovo, abrirEdicao, fechar, salvar, anexar, excluir,
  confirmacao, confirmar, cancelar,
} = useCrudModal(
  'cisternas.ordens-servico',
  { lote_id: '', nome: '', observacao: '', documento_url: '', documento_os: null },
  (o) => ({
    lote_id: o.lote?.id ?? '',
    nome: o.nome,
    observacao: o.observacao ?? '',
    documento_url: o.documento_url ?? '',
    documento_os: null,
  }),
  { comArquivo: true },
);

/**
 * Vindo da tela de um lote, a nova ordem ja nasce nele: e o contexto em que o
 * usuario esta, e obrigar a reescolher o lote seria trabalho a mais.
 */
function novaOrdem() {
  abrirNovo();

  if (props.lote?.id) {
    form.lote_id = props.lote.id;
  }
}

/**
 * Modal, e nao navegacao: a rota `timeline` devolve JSON, entao um
 * `router.visit` jogaria o usuario numa tela de JSON cru.
 */
function verTimeline(ordem) {
  ordemDaTimeline.value = ordem;
  timelineAberta.value = true;
}
</script>
