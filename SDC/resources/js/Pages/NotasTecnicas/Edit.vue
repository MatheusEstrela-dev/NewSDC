<template>
  <Head :title="`Editar ${nota.codigo}`" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="`Editar ${nota.codigo}`"
      :description="nota.titulo"
      :icon-image="moduleIcon('notas-tecnicas') ?? ''"
      variant="gradient"
      :espaco-inferior="false"
    />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <form class="min-w-0 space-y-6 lg:col-span-2" @submit.prevent="salvar">
        <NotaTecnicaFormSecoes
          :form="form"
          :errors="form.errors"
          :categorias="categorias"
          :telas="telas"
        />

        <FormActions
          submit-label="Salvar dados"
          :loading="form.processing"
          :disabled="form.processing"
          @cancel="voltar"
          @submit="salvar"
        />
      </form>

      <div class="min-w-0 space-y-6">
        <section :class="cartao">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Situação</h3>
            <Badge :variant="nota.status_variante" size="sm">{{ nota.status_label }}</Badge>
          </div>
          <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ explicacaoStatus }}</p>
          <p v-if="erroNota" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ erroNota }}</p>

          <div class="mt-4 flex flex-wrap gap-2">
            <Button
              v-if="permissoes.publicar && nota.status !== 'publicada'"
              variant="success"
              size="sm"
              :icon="CheckBadgeIcon"
              :disabled="!nota.versao"
              @click="publicar(nota)"
            >
              {{ nota.status === 'revogada' ? 'Republicar' : 'Publicar' }}
            </Button>
            <Button
              v-if="permissoes.publicar && nota.status === 'publicada'"
              variant="warning"
              size="sm"
              :icon="ArchiveBoxIcon"
              @click="revogar(nota)"
            >
              Revogar
            </Button>
            <Button
              v-if="permissoes.excluir"
              variant="danger"
              size="sm"
              :icon="TrashIcon"
              @click="excluir(nota)"
            >
              Excluir
            </Button>
          </div>
        </section>

        <section :class="cartao">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Versões</h3>
            <!--
              Em nota publicada a versao nova entra em vigor na hora: o backend
              exige `publicar`. Sem ela o botao some, em vez de terminar em 403.
            -->
            <Button
              v-if="nota.status !== 'publicada' || permissoes.publicar"
              variant="primary"
              size="sm"
              :icon="ArrowUpTrayIcon"
              @click="modalVersao = true"
            >
              Nova versão
            </Button>
          </div>
          <Link
            v-if="permissoes.leituras && nota.status !== 'rascunho' && nota.versoes.length"
            :href="route('notas-tecnicas.leituras', nota.id)"
            class="mt-2 inline-flex min-h-[2.5rem] items-center gap-1.5 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            <UsersIcon class="h-4 w-4" />
            Ver quem leu
          </Link>

          <ul class="mt-3 space-y-1">
            <li v-for="versao in nota.versoes" :key="versao.id" class="flex items-center gap-2 text-sm">
              <a
                :href="route('notas-tecnicas.versoes.pdf', versao.id)"
                target="_blank"
                rel="noopener"
                class="flex min-h-[2.5rem] min-w-0 flex-1 items-center gap-2 rounded-md px-2 hover:bg-slate-50 dark:hover:bg-slate-800/50"
              >
                <DocumentIcon class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="font-semibold text-slate-900 dark:text-slate-100">v{{ versao.numero }}</span>
                <span v-if="versao.atual" class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Vigente</span>
                <span class="ml-auto truncate text-xs text-slate-500 dark:text-slate-400">{{ dataCurta(versao.publicada_em) }}</span>
              </a>
            </li>
          </ul>
        </section>
      </div>
    </div>

    <NotaTecnicaNovaVersaoModal
      :show="modalVersao"
      :nota-id="nota.id"
      :codigo="nota.codigo"
      :publicada="nota.status === 'publicada'"
      :proxima-versao="proximaVersao"
      :limite-mb="limiteMb"
      @close="modalVersao = false"
    />

    <ConfirmDialog
      :is-open="confirmacao.aberto"
      v-bind="confirmacao.opcoes"
      @confirm="confirmar"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArchiveBoxIcon, ArrowUpTrayIcon, CheckBadgeIcon, DocumentIcon, TrashIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import NotaTecnicaFormSecoes from '@/Components/Organisms/NotasTecnicas/NotaTecnicaFormSecoes.vue';
import NotaTecnicaNovaVersaoModal from '@/Components/Organisms/NotasTecnicas/NotaTecnicaNovaVersaoModal.vue';
import { useNotaTecnicaPublicacao } from '@/Composables/notasTecnicas/useNotaTecnicaPublicacao';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  nota: { type: Object, required: true },
  valores: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
  telas: { type: Array, default: () => [] },
  limiteMb: { type: Number, default: 30 },
  proximaVersao: { type: String, default: '' },
  permissoes: { type: Object, default: () => ({}) },
});

const cartao = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40';

const form = useForm({ ...props.valores, vigencia_inicio: props.valores.vigencia_inicio ?? '' });
const modalVersao = ref(false);
const { confirmacao, confirmar, cancelar, publicar, revogar, excluir } = useNotaTecnicaPublicacao();

// Publicar sem PDF e revogar rascunho voltam como erro de validacao em `nota`.
const page = usePage();
const erroNota = computed(() => page.props.errors?.nota ?? '');

const explicacaoStatus = computed(() => ({
  rascunho: 'Só quem edita vê esta nota. Publique para ela aparecer a todos os leitores.',
  publicada: 'Em vigor e visível a todos os leitores.',
  revogada: 'Obsoleta: continua consultável na pasta de revogadas.',
}[props.nota.status] ?? ''));

function salvar() {
  form.put(route('notas-tecnicas.update', props.nota.id), { preserveScroll: true });
}

function voltar() {
  router.visit(route('notas-tecnicas.index', { nota: props.nota.id }));
}

function dataCurta(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}
</script>
