<template>
  <Head title="Solicitações de Comunidade — PMDA" />

  <div class="pb-6">
    <PageHeader
      title="Solicitações de Comunidade"
      description="Análise das comunidades solicitadas pelos municípios. Aprovar disponibiliza a comunidade para os PMDA."
      :icon="DocumentTextIcon"
      variant="gradient"
    />

    <!-- Filtros de Pesquisa (retrátil, padrão do módulo) -->
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

    <!-- Lista -->
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
      <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
          <DocumentTextIcon class="h-4 w-4 text-slate-400" />
          Solicitações Pendentes
          <span class="font-normal text-slate-400">({{ lista.length }} registros)</span>
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left">Município</th>
              <th class="px-4 py-3 text-left">Comunidade</th>
              <th class="px-4 py-3 text-left">Solicitada em</th>
              <th class="w-48 px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="s in lista" :key="s.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
              <td class="px-4 py-4 text-slate-700 dark:text-slate-300">{{ s.municipio ?? '—' }}</td>
              <td class="px-4 py-4 font-medium text-slate-800 dark:text-slate-100">{{ s.nome }}</td>
              <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ fmtData(s.created_at) }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-2">
                  <Button variant="success" size="sm" :disabled="processando === s.id" @click="aprovar(s)">
                    <CheckIcon class="mr-1 h-4 w-4" /> Aprovar
                  </Button>
                  <Button variant="danger" size="sm" :disabled="processando === s.id" @click="abrirRejeicao(s)">
                    <XMarkIcon class="mr-1 h-4 w-4" /> Rejeitar
                  </Button>
                </div>
              </td>
            </tr>

            <tr v-if="lista.length === 0">
              <td colspan="4" class="px-4 py-12 text-center">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300" />
                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhuma solicitação pendente</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Não há comunidades aguardando análise.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Paginação -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>

    <!-- Modal de rejeição -->
    <Modal :show="!!rejeitando" max-width="md" @close="rejeitando = null">
      <div class="space-y-4 p-5">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Rejeitar solicitação</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Informe o motivo da rejeição de <strong>{{ rejeitando?.nome }}</strong>. O município verá esta justificativa.
        </p>
        <TextInput v-model="motivo" :maxlength="255" placeholder="Motivo da rejeição" />
        <div class="flex justify-end gap-2">
          <Button variant="secondary" size="sm" @click="rejeitando = null">Cancelar</Button>
          <Button variant="danger" size="sm" :disabled="!motivo.trim() || processando" @click="confirmarRejeicao">Rejeitar</Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { XMarkIcon, CheckIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  solicitacoes: { type: Object, default: () => ({ data: [], meta: {} }) },
  filtros: { type: Object, default: () => ({}) },
  municipios: { type: Array, default: () => [] },
});

const lista = computed(() => props.solicitacoes?.data ?? []);

const pagination = computed(() => {
  const m = props.solicitacoes?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

const municipioOptions = computed(() =>
  props.municipios.map((m) => ({ value: m.id, label: `${m.nome} / ${m.uf}` }))
);

const filtros = reactive({ municipio_id: props.filtros?.municipio_id ?? '' });

function aplicar() {
  router.get(route('pmda.solicitacoes.index'), {
    municipio_id: filtros.municipio_id || undefined,
  }, { preserveState: true, replace: true });
}

function limpar() {
  filtros.municipio_id = '';
  router.get(route('pmda.solicitacoes.index'), {}, { preserveState: true, replace: true });
}

function irParaPagina(p) {
  router.get(route('pmda.solicitacoes.index'), { ...filtros, page: p }, { preserveState: true, replace: true });
}

const fmtData = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
};

const processando = ref(null);

function aprovar(s) {
  processando.value = s.id;
  router.post(route('pmda.solicitacoes.aprovar', s.id), {}, {
    preserveScroll: true,
    onFinish: () => { processando.value = null; },
  });
}

const rejeitando = ref(null);
const motivo = ref('');

function abrirRejeicao(s) {
  rejeitando.value = s;
  motivo.value = '';
}

function confirmarRejeicao() {
  if (!motivo.value.trim() || !rejeitando.value) return;
  const id = rejeitando.value.id;
  processando.value = id;
  router.post(route('pmda.solicitacoes.rejeitar', id), { motivo: motivo.value }, {
    preserveScroll: true,
    onSuccess: () => { rejeitando.value = null; motivo.value = ''; },
    onFinish: () => { processando.value = null; },
  });
}
</script>
