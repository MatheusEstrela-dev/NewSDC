<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Notificacoes" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Notificacoes de fiscalizacao"
        description="Apontamentos abertos sobre um cadastro ou uma vistoria"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="notificacoes"
            label="Nova notificacao"
            :allowed="true"
            @click="abrirNovo"
          />
        </template>
      </PageHeader>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800/60">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th :class="TH">Sobre</th>
                <th :class="TH">Apontamento</th>
                <th :class="TH">Emitida por</th>
                <th :class="TH">Situacao</th>
                <th :class="TH">Anexos</th>
                <th :class="[TH, 'table-actions-head w-36 min-w-36 text-right']">Opcoes</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
              <tr v-for="n in lista" :key="n.id" class="table-row-solid transition-colors">
                <td :class="TD">
                  <span :class="PILULA_TIPO">{{ rotuloTipo(n.notificavel?.tipo) }}</span>
                  <span class="ml-1 font-mono text-xs text-slate-400">#{{ n.notificavel?.id }}</span>
                </td>
                <td class="max-w-md px-3 py-2 text-sm text-slate-700 dark:text-slate-200">
                  <span class="line-clamp-2" :title="n.observacao">{{ n.observacao }}</span>
                </td>
                <td :class="TD">{{ n.emitida_por ?? '—' }}</td>
                <td :class="TD">
                  <span :class="n.respondida ? PILULA_OK : PILULA_ABERTA">
                    {{ n.respondida ? 'Respondida' : 'Em aberto' }}
                  </span>
                  <span v-if="n.respondida_em" class="ml-1 text-xs text-slate-400">
                    {{ dataBr(n.respondida_em) }}
                  </span>
                </td>
                <td :class="TD">
                  <a
                    v-for="d in (n.documentos ?? [])"
                    :key="d.id"
                    :href="d.url"
                    target="_blank"
                    rel="noopener"
                    :class="ELO"
                  >
                    {{ d.nome || 'anexo' }}
                  </a>
                  <span v-if="(n.documentos ?? []).length === 0" class="text-slate-400">—</span>
                </td>
                <!-- Coluna fixa no canto direito: em tela estreita a tabela rola
                     na horizontal e as acoes precisam continuar alcancaveis. Depende
                     de .table-row-solid na <tr> para o fundo opaco. -->
                <td class="table-actions-cell w-36 min-w-36 whitespace-nowrap px-3 py-2 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <!--
                      Botao proprio, e nao a acao `check` do ActionButton: aquela
                      consulta o slug `cisternas.notificacoes.validar`, que NAO
                      existe no config/permissions.php -- o icone nunca renderizava
                      e responder ficava inalcancavel. O backend autoriza o
                      responder com `update`, entao a guarda aqui e a mesma.
                    -->
                    <button
                      v-if="!n.respondida && podeResponder"
                      type="button"
                      :class="BOTAO_RESPONDER"
                      title="Marcar como respondida"
                      @click="responder(n)"
                    >
                      <CheckCircleIcon class="h-4 w-4" />
                    </button>

                    <ActionButton
                      module="cisternas"
                      resource="notificacoes"
                      :actions="[
                        { action: 'edit',   handler: () => abrirEdicao(n) },
                        { action: 'delete', handler: () => excluir(n, 'esta notificacao') },
                      ]"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="lista.length === 0">
                <td colspan="6" class="px-3 py-10">
                  <ListEmptyState
                    title="Nenhuma notificacao"
                    helper="A notificacao registra um apontamento da fiscalizacao sobre um cadastro ou vistoria."
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <CisternaFormModal
        :show="aberto"
        :titulo="editando ? 'Editar notificacao' : 'Nova notificacao'"
        subtitulo="A notificacao aponta para um cadastro ou para uma vistoria"
        :icon="BellAlertIcon"
        tom="warning"
        :processando="form.processing"
        @close="fechar"
        @submit="salvar"
      >
        <div class="space-y-3">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <FormSelect
              v-model="form.notificavel_type"
              label="Sobre"
              :options="tiposOpcoes"
              placeholder="Selecione"
              required
              :error="form.errors.notificavel_type"
            />
            <FormField
              v-model="form.notificavel_id"
              label="Numero do registro"
              type="number"
              required
              :error="form.errors.notificavel_id"
              hint="Id do beneficiario ou da vistoria"
            />
          </div>

          <FormTextarea
            v-model="form.observacao"
            label="Apontamento"
            :rows="5"
            maxlength="2000"
            required
            :error="form.errors.observacao"
          />

          <ArquivoField
            label="Anexar documento"
            :error="form.errors.arquivo"
            @change="(f) => anexar({ campo: 'arquivo', arquivo: f })"
          />
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
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BellAlertIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
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
import { moduleIcon } from '@/Support/moduleIcons';
import { usePermissions } from '@/Composables/auth';
import { useCrudModal } from '@/Composables/cisterna/useCrudModal';

const props = defineProps({
  notificacoes: { type: [Object, Array], default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  /** Aliases de NotificacaoDTO::TIPOS_PERMITIDOS: 'beneficiario', 'vistoria'. */
  tipos: { type: Array, default: () => [] },
  permissoes: { type: Object, default: () => ({}) },
});

const TH = 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const ELO = 'mr-2 text-blue-700 hover:underline dark:text-blue-300';
const { can } = usePermissions();

/**
 * O backend autoriza o responder com `update`, que na policy e
 * `cisternas.notificacoes.edit`. Consultado pelo mesmo helper do ActionButton,
 * para a tela e o servidor concordarem.
 */
const podeResponder = computed(() => can('cisternas.notificacoes.edit'));

const BOTAO_RESPONDER = 'rounded p-1.5 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10';
const PILULA_TIPO = 'rounded px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300';
const PILULA_OK = 'rounded px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
const PILULA_ABERTA = 'rounded px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300';

const ROTULOS_TIPO = {
  beneficiario: 'Beneficiario',
  vistoria: 'Vistoria',
};

const lista = computed(() => props.notificacoes?.data ?? props.notificacoes ?? []);

const tiposOpcoes = computed(
  () => (props.tipos ?? []).map((t) => ({ value: t, label: ROTULOS_TIPO[t] ?? t })),
);

const { aberto, editando, form, abrirNovo, abrirEdicao, fechar, salvar, anexar, excluir,
  confirmacao, confirmar, cancelar,
} = useCrudModal(
  'cisternas.notificacoes',
  { notificavel_type: '', notificavel_id: '', observacao: '', arquivo: null },
  (n) => ({
    notificavel_type: n.notificavel?.tipo ?? '',
    notificavel_id: n.notificavel?.id ?? '',
    observacao: n.observacao ?? '',
    arquivo: null,
  }),
  { comArquivo: true },
);

/**
 * Marcar como respondida e acao propria, com rota propria: nao passa pelo
 * formulario porque nao muda o apontamento -- so registra que foi atendido.
 */
function responder(notificacao) {
  router.post(
    route('cisternas.notificacoes.responder', notificacao.id),
    { respondida: true },
    { preserveScroll: true },
  );
}

function rotuloTipo(tipo) {
  return ROTULOS_TIPO[tipo] ?? tipo ?? '—';
}

function dataBr(iso) {
  if (!iso) return '';

  const data = String(iso).slice(0, 10).split('-');

  return data.length === 3 ? `${data[2]}/${data[1]}/${data[0]}` : iso;
}
</script>
