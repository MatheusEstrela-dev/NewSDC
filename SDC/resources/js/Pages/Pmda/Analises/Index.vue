<template>
  <Head title="Central de Análises — CEDEC" />

  <div class="pb-6">
    <PageHeader
      title="Central de Análises — CEDEC"
      description="Fila de análise dos PMDA enviados e das comunidades solicitadas pelos municípios. Aprovar uma comunidade a disponibiliza no 'Adicionar Comunidade' da aba Locais."
      :icon="DocumentTextIcon"
      :icon-image="moduleIcon('pmda')"
      variant="gradient"
    />

    <!-- Resumo -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <StatCard title="PMDA em análise" :value="analisesMeta?.total ?? analises.length" :icon="ClipboardDocumentCheckIcon" variant="info" />
      <StatCard title="Comunidades pendentes" :value="solicitacoesMeta?.total ?? solicitacoes.length" :icon="MapPinIcon" variant="warning" />
    </div>

    <!-- Filtro (retrátil, padrão do módulo) -->
    <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6" :default-collapsed="true">
      <FilterField
        label="Município"
        type="select"
        :model-value="filtros.municipio_id"
        :options="municipioOptions"
        placeholder="Todos os municípios"
        @update:model-value="filtros.municipio_id = $event"
      />
      <div class="flex items-end justify-end pt-1 md:col-span-2 lg:col-span-4">
        <FilterActions @search="aplicar" @clear="limpar" />
      </div>
    </FilterSection>

    <!-- Split screen -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <!-- PAINEL ESQUERDO: Análises de PMDA -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/40 dark:bg-slate-900/40">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700/40">
          <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <ClipboardDocumentCheckIcon class="h-4 w-4 text-indigo-500" />
            Análises de PMDA
            <span class="font-normal text-slate-400">({{ analisesMeta?.total ?? analises.length }})</span>
          </h3>
        </div>

        <div v-if="analises.length === 0" class="px-6 py-12 text-center text-slate-400">
          Nenhum PMDA aguardando análise.
        </div>
        <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <li v-for="p in analises" :key="p.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ p.protocolo ?? '—' }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ p.municipio ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-slate-400">
                  Enviado em {{ fmtDataHora(p.dt_analise) }}<span v-if="p.resp_homolog"> · {{ p.resp_homolog }}</span>
                </p>
              </div>
              <PmdaStatusBadge :label="p.status_label" :cor="p.status_cor" />
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
              <Button v-if="canAprovar" variant="success" size="sm" :disabled="processandoPlano === p.id" @click="confirmarAprovacao(p)">
                <CheckIcon class="mr-1 h-4 w-4" /> Aprovar
              </Button>
              <Button v-if="canPedirAlteracao" variant="warning" size="sm" :disabled="processandoPlano === p.id" @click="abrirMotivo('pedir-alteracao', p)">
                <PencilSquareIcon class="mr-1 h-4 w-4" /> Pedir alteração
              </Button>
              <Button v-if="canArquivar" variant="danger" size="sm" :disabled="processandoPlano === p.id" @click="abrirMotivo('arquivar', p)">
                <ArchiveBoxIcon class="mr-1 h-4 w-4" /> Arquivar
              </Button>
            </div>
          </li>
        </ul>

        <div class="px-4 pb-4">
          <Pagination :pagination="analisesMeta" @page-change="irParaPagina('analises_page', $event)" />
        </div>
      </div>

      <!-- PAINEL DIREITO: Solicitações de Comunidade -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/40 dark:bg-slate-900/40">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700/40">
          <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <MapPinIcon class="h-4 w-4 text-amber-500" />
            Solicitações de Comunidade
            <span class="font-normal text-slate-400">({{ solicitacoesMeta?.total ?? solicitacoes.length }})</span>
          </h3>
        </div>

        <div v-if="solicitacoes.length === 0" class="px-6 py-12 text-center text-slate-400">
          Nenhuma comunidade aguardando análise.
        </div>
        <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <li v-for="s in solicitacoes" :key="s.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-900 dark:text-slate-100">{{ s.nome }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ s.municipio ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-slate-400">Solicitada em {{ fmtData(s.created_at) }}</p>
              </div>
              <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="s.status_color">{{ s.status_label }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
              <Button v-if="canAprovarComunidade" variant="success" size="sm" :disabled="processandoSolic === s.id" @click="aprovarComunidade(s)">
                <CheckIcon class="mr-1 h-4 w-4" /> Aprovar
              </Button>
              <Button v-if="canAprovarComunidade" variant="danger" size="sm" :disabled="processandoSolic === s.id" @click="abrirMotivo('rejeitar', s)">
                <XMarkIcon class="mr-1 h-4 w-4" /> Rejeitar
              </Button>
            </div>
          </li>
        </ul>

        <div class="px-4 pb-4">
          <Pagination :pagination="solicitacoesMeta" @page-change="irParaPagina('solicitacoes_page', $event)" />
        </div>
      </div>
    </div>

    <!-- Aprovar PMDA (confirmação) -->
    <ConfirmDialog
      :is-open="aprovarDialog.open"
      variant="success"
      title="Aprovar PMDA"
      :message="aprovarDialog.message"
      description="O plano passa para APROVADO e sai da fila de análise."
      confirm-text="Aprovar"
      cancel-text="Cancelar"
      :loading="aprovarDialog.loading"
      @confirm="confirmDelete_aprovar"
      @cancel="aprovarDialog.open = false"
    />

    <!-- Modal de motivo (arquivar / pedir alteração / rejeitar comunidade) -->
    <Modal :show="motivoModal.open" max-width="md" @close="fecharMotivo">
      <div class="space-y-4 p-5">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ motivoModal.titulo }}</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ motivoModal.descricao }}</p>
        <TextInput v-model="motivo" :maxlength="255" placeholder="Descreva o motivo" />
        <div class="flex justify-end gap-2">
          <Button variant="secondary" size="sm" @click="fecharMotivo">Cancelar</Button>
          <Button :variant="motivoModal.variant" size="sm" :disabled="!motivo.trim()" @click="confirmarMotivo">
            {{ motivoModal.acaoLabel }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast.js';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import {
  CheckIcon, XMarkIcon, PencilSquareIcon, ArchiveBoxIcon,
  ClipboardDocumentCheckIcon, MapPinIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();
const { show: toast } = useToast();

const props = defineProps({
  analises: { type: Object, default: () => ({ data: [], meta: {} }) },
  solicitacoes: { type: Object, default: () => ({ data: [], meta: {} }) },
  filtros: { type: Object, default: () => ({}) },
  municipios: { type: Array, default: () => [] },
});

const analises = computed(() => props.analises?.data ?? []);
const solicitacoes = computed(() => props.solicitacoes?.data ?? []);
const analisesMeta = computed(() => props.analises?.meta ?? null);
const solicitacoesMeta = computed(() => props.solicitacoes?.meta ?? null);

const canAprovar = computed(() => can('pmda.analise.aprovar'));
const canArquivar = computed(() => can('pmda.analise.arquivar'));
const canPedirAlteracao = computed(() => can('pmda.analise.pedir_alteracao'));
const canAprovarComunidade = computed(() => can('pmda.comunidades.aprovar'));

const municipioOptions = computed(() =>
  props.municipios.map((m) => ({ value: m.id, label: `${m.nome} / ${m.uf}` }))
);

const filtros = reactive({ municipio_id: props.filtros?.municipio_id ?? '' });

function aplicar() {
  router.get(route('pmda.analises.index'), {
    municipio_id: filtros.municipio_id || undefined,
  }, { preserveState: true, replace: true });
}

// Navegacao server-side de um painel preservando filtro e a pagina do outro.
function irParaPagina(param, page) {
  const paginaAnalises = param === 'analises_page' ? page : analisesMeta.value?.current_page ?? 1;
  const paginaSolicitacoes = param === 'solicitacoes_page' ? page : solicitacoesMeta.value?.current_page ?? 1;

  router.get(route('pmda.analises.index'), {
    municipio_id: filtros.municipio_id || undefined,
    analises_page: paginaAnalises > 1 ? paginaAnalises : undefined,
    solicitacoes_page: paginaSolicitacoes > 1 ? paginaSolicitacoes : undefined,
  }, { preserveState: true, preserveScroll: true, replace: true });
}

function limpar() {
  filtros.municipio_id = '';
  router.get(route('pmda.analises.index'), {}, { preserveState: true, replace: true });
}

const fmtData = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
};
const fmtDataHora = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
};

// --- Aprovar PMDA (confirmação) ---
const processandoPlano = ref(null);
const aprovarDialog = reactive({ open: false, loading: false, plano: null, message: '' });

function confirmarAprovacao(p) {
  aprovarDialog.plano = p;
  aprovarDialog.message = `Aprovar o PMDA ${p.protocolo ?? ''} (${p.municipio ?? '—'})?`;
  aprovarDialog.open = true;
}

function confirmDelete_aprovar() {
  const p = aprovarDialog.plano;
  if (!p) return;
  aprovarDialog.loading = true;
  processandoPlano.value = p.id;
  router.post(route('pmda.planos.aprovar', p.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('PMDA aprovado.', 'success'),
    onError: () => toast('Não foi possível aprovar o PMDA.', 'error'),
    onFinish: () => {
      aprovarDialog.loading = false;
      aprovarDialog.open = false;
      processandoPlano.value = null;
    },
  });
}

// --- Aprovar comunidade (direto) ---
const processandoSolic = ref(null);

function aprovarComunidade(s) {
  processandoSolic.value = s.id;
  router.post(route('pmda.solicitacoes.aprovar', s.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Comunidade aprovada e disponível para os PMDA do município.', 'success'),
    onError: () => toast('Não foi possível aprovar a comunidade.', 'error'),
    onFinish: () => { processandoSolic.value = null; },
  });
}

// --- Modal de motivo (compartilhado) ---
const motivo = ref('');
const motivoModal = reactive({ open: false, tipo: null, target: null, titulo: '', descricao: '', acaoLabel: '', variant: 'danger' });

const CONFIG_MOTIVO = {
  arquivar: {
    titulo: 'Arquivar PMDA',
    descricao: 'Informe o motivo do arquivamento. O município verá esta justificativa.',
    acaoLabel: 'Arquivar', variant: 'danger',
  },
  'pedir-alteracao': {
    titulo: 'Pedir alteração',
    descricao: 'O PMDA volta para edição do município. Descreva o que precisa ser corrigido.',
    acaoLabel: 'Devolver', variant: 'warning',
  },
  rejeitar: {
    titulo: 'Rejeitar solicitação',
    descricao: 'Informe o motivo da rejeição da comunidade. O município verá esta justificativa.',
    acaoLabel: 'Rejeitar', variant: 'danger',
  },
};

function abrirMotivo(tipo, target) {
  const cfg = CONFIG_MOTIVO[tipo];
  motivoModal.tipo = tipo;
  motivoModal.target = target;
  motivoModal.titulo = cfg.titulo;
  motivoModal.descricao = cfg.descricao;
  motivoModal.acaoLabel = cfg.acaoLabel;
  motivoModal.variant = cfg.variant;
  motivo.value = '';
  motivoModal.open = true;
}

function fecharMotivo() {
  motivoModal.open = false;
  motivoModal.target = null;
}

function confirmarMotivo() {
  if (!motivo.value.trim() || !motivoModal.target) return;
  const { tipo, target } = motivoModal;
  const rota = {
    arquivar: () => route('pmda.planos.arquivar', target.id),
    'pedir-alteracao': () => route('pmda.planos.pedir-alteracao', target.id),
    rejeitar: () => route('pmda.solicitacoes.rejeitar', target.id),
  }[tipo]();

  const sucesso = {
    arquivar: 'PMDA arquivado.',
    'pedir-alteracao': 'PMDA devolvido ao município.',
    rejeitar: 'Solicitação rejeitada.',
  }[tipo];

  if (tipo === 'rejeitar') processandoSolic.value = target.id; else processandoPlano.value = target.id;

  router.post(rota, { motivo: motivo.value }, {
    preserveScroll: true,
    onSuccess: () => { toast(sucesso, 'success'); fecharMotivo(); },
    onError: () => toast('Não foi possível concluir a ação.', 'error'),
    onFinish: () => { processandoPlano.value = null; processandoSolic.value = null; },
  });
}
</script>
