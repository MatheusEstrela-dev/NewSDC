<template>
  <Head title="TDAP — Viagens Pendentes" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Viagens pendentes de validação"
      description="Fila de viagens registradas aguardando aprovação"
      :icon="ClockIcon"
    />

    <!-- Cards clicáveis: cada um é um recorte da própria fila. -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        title="Pendentes"
        :value="estatisticas.pendentes"
        :icon="ClockIcon"
        variant="warning"
        clickable
        @click="limparFiltros"
      />
      <StatCard
        title="Esperando +7 dias"
        :value="estatisticas.aguardando_mais_de_7d"
        :icon="ClockIcon"
        variant="danger"
        subtitle="Fila parada"
      />
      <StatCard
        title="De cronograma encerrado"
        :value="estatisticas.de_cronograma_encerrado"
        :icon="ClockIcon"
        variant="danger"
        subtitle="Exigem atenção"
      />
      <StatCard
        title="Municípios"
        :value="estatisticas.municipios"
        :icon="ClockIcon"
        variant="info"
      />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3">
      <FilterField
        v-model="form.search"
        label="Buscar"
        type="search"
        placeholder="Placa ou número do cronograma"
        @update:model-value="buscaComDebounce"
      />
      <FilterField
        v-model="form.municipio_id"
        label="Município"
        type="select"
        :options="opcoesMunicipio"
        @update:model-value="aplicar"
      />
      <div class="flex items-end">
        <FilterActions @search="aplicar" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <ResponsiveTable
        :items="viagens.data"
        :mobile-fields="CAMPOS_MOBILE"
        :get-item-title="(v) => `${v.cronograma_numero} — ${v.municipio_nome ?? 'sem município'}`"
        :get-item-subtitle="(v) => v.caminhao_placa"
        :get-item-variant="varianteDaLinha"
        empty-message="Nenhuma viagem pendente"
      >
        <template #table>
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Cronograma</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Município / Prestador</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Caminhão</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Libera</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Data / Espera</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Prazo</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="v in viagens.data" :key="v.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                <td class="px-4 py-3">
                  <Link
                    v-if="v.cronograma_id"
                    :href="route('tdap.cronogramas.show', v.cronograma_id)"
                    class="font-mono font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400"
                  >
                    {{ v.cronograma_numero }}
                  </Link>
                  <span v-else class="font-mono font-semibold">{{ v.cronograma_numero }}</span>
                  <EstadoBadge v-if="v.cronograma_estado" :estado="v.cronograma_estado" class="ml-2" />
                </td>

                <td class="px-4 py-3">
                  <div class="text-slate-800 dark:text-slate-100">{{ v.municipio_nome ?? '—' }}</div>
                  <div class="text-xs text-slate-500 truncate max-w-[16rem]" :title="v.prestador_nome">
                    {{ v.prestador_nome ?? '—' }}
                  </div>
                </td>

                <td class="px-4 py-3">
                  <div class="font-mono">{{ v.caminhao_placa }}</div>
                  <div class="text-xs text-slate-500">
                    {{ [v.caminhao_marca, v.caminhao_modelo].filter(Boolean).join(' ') || '—' }}
                    <span v-if="v.caminhao_ativo === false" class="text-red-600 font-medium">· inativo</span>
                  </div>
                </td>

                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="font-mono font-semibold">{{ fmtNum(v.m3_da_viagem) }} m³</div>
                  <div class="text-xs text-slate-500">{{ fmtMoeda(v.valor_da_viagem) }}</div>
                </td>

                <td class="px-4 py-3">
                  <div class="text-slate-700 dark:text-slate-300">{{ fmtDateTime(v.data_registro) }}</div>
                  <div class="text-xs" :class="v.dias_aguardando > 7 ? 'text-red-600 font-medium' : 'text-slate-500'">
                    {{ textoEspera(v.dias_aguardando) }}
                  </div>
                </td>

                <td class="px-4 py-3">
                  <PrazoBadge :dias-restantes="v.dias_restantes" :proxima-vencer="v.proxima_vencer" />
                  <div v-if="v.fora_da_vigencia" class="text-xs text-amber-600 mt-1">fora da vigência</div>
                </td>

                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                  <button v-if="canValidar" @click="abrirDecisao(v, true)" class="px-3 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700">
                    Aprovar
                  </button>
                  <button v-if="canValidar" @click="abrirDecisao(v, false)" class="px-3 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700">
                    Rejeitar
                  </button>
                </td>
              </tr>

              <tr v-if="viagens.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                  <ClockIcon class="mx-auto h-10 w-10 text-slate-300 mb-2" />
                  <p class="font-medium text-slate-900 dark:text-slate-100">Nenhuma viagem pendente</p>
                  <p class="text-sm mt-1">Todas as viagens registradas foram validadas.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </template>

        <template #mobile-c1="{ item: v }">
          {{ v.prestador_nome ?? '—' }}
        </template>

        <template #mobile-c2="{ item: v }">
          {{ fmtNum(v.m3_da_viagem) }} m³ · {{ fmtMoeda(v.valor_da_viagem) }}
        </template>

        <template #mobile-c3="{ item: v }">
          {{ fmtDateTime(v.data_registro) }}
          <span class="block text-xs" :class="v.dias_aguardando > 7 ? 'text-red-600' : 'text-slate-500'">
            {{ textoEspera(v.dias_aguardando) }}
          </span>
        </template>

        <template #mobile-c4="{ item: v }">
          <PrazoBadge :dias-restantes="v.dias_restantes" :proxima-vencer="v.proxima_vencer" />
          <span v-if="v.fora_da_vigencia" class="block text-xs text-amber-600 mt-1">fora da vigência</span>
        </template>

        <template #mobile-actions="{ item: v }">
          <button v-if="canValidar" @click="abrirDecisao(v, true)" class="px-3 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700">
            Aprovar
          </button>
          <button v-if="canValidar" @click="abrirDecisao(v, false)" class="px-3 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700">
            Rejeitar
          </button>
        </template>
      </ResponsiveTable>
    </div>

    <Pagination :pagination="viagens.meta" @page-change="irParaPagina" />

    <!-- ConfirmDialog no lugar do prompt()/alert() do navegador, que era o
         unico ponto do TDAP que ainda usava dialogo nativo. -->
    <ConfirmDialog
      :is-open="decisao.aberta"
      :title="decisao.aprovada ? 'Aprovar viagem' : 'Rejeitar viagem'"
      :message="decisao.aprovada
        ? `Aprovar libera ${fmtNum(decisao.viagem?.m3_da_viagem)} m³ (${fmtMoeda(decisao.viagem?.valor_da_viagem)}) no cronograma ${decisao.viagem?.cronograma_numero}.`
        : `Informe o motivo da rejeição da viagem do caminhão ${decisao.viagem?.caminhao_placa}.`"
      :variant="decisao.aprovada ? 'success' : 'danger'"
      :confirm-text="decisao.aprovada ? 'Aprovar' : 'Rejeitar'"
      :loading="decisao.enviando"
      @confirm="confirmarDecisao"
      @cancel="decisao.aberta = false"
    >
      <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
          {{ decisao.aprovada ? 'Observação (opcional)' : 'Motivo da rejeição *' }}
        </label>
        <textarea
          v-model="decisao.obs"
          rows="3"
          maxlength="500"
          class="block w-full text-sm rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500"
        />
        <p v-if="decisao.erro" class="mt-1 text-xs text-red-600">{{ decisao.erro }}</p>
      </div>
    </ConfirmDialog>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import PrazoBadge from '@/Components/Organisms/Tdap/PrazoBadge.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  viagens:      { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ pendentes: 0, aguardando_mais_de_7d: 0, de_cronograma_encerrado: 0, municipios: 0 }) },
  municipios:   { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canValidar:   { type: Boolean, default: false },
});

const form = reactive({
  search: props.filtros.search ?? '',
  municipio_id: props.filtros.municipio_id ?? '',
});

const opcoesMunicipio = computed(() => [
  { value: '', label: 'Todos os municípios' },
  ...props.municipios.map((m) => ({ value: String(m.id), label: m.nome })),
]);

/** Remove chave vazia antes de navegar: querystring limpa e filtro previsível. */
function filtrosLimpos() {
  return Object.fromEntries(
    Object.entries({ ...form }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
  );
}

function aplicar() {
  router.get(route('tdap.viagens.pendentes'), filtrosLimpos(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

// Debounce só na busca por texto; select aplica na hora. Mesmo critério do
// TdapCaminhoesFiltersSection.
let timer;
function buscaComDebounce() {
  clearTimeout(timer);
  timer = setTimeout(aplicar, 350);
}

function limparFiltros() {
  form.search = '';
  form.municipio_id = '';
  aplicar();
}

/**
 * Paginar SEM perder o filtro.
 *
 * A versão anterior chamava router.get só com { page }, o que descartava a
 * querystring: filtrar e virar a página devolvia a lista inteira.
 */
function irParaPagina(page) {
  router.get(route('tdap.viagens.pendentes'), { ...filtrosLimpos(), page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/** Pinta o card no mobile: o risco precisa saltar sem depender de coluna. */
function varianteDaLinha(v) {
  if (v.cronograma_estado === 'encerrado' || v.caminhao_ativo === false) return 'danger';
  if (v.dias_aguardando > 7 || v.fora_da_vigencia) return 'warning';

  return 'default';
}

const decisao = reactive({
  aberta: false,
  aprovada: true,
  viagem: null,
  obs: '',
  erro: '',
  enviando: false,
});

function abrirDecisao(viagem, aprovada) {
  Object.assign(decisao, { aberta: true, aprovada, viagem, obs: '', erro: '', enviando: false });
}

function confirmarDecisao() {
  if (!decisao.aprovada && !decisao.obs.trim()) {
    decisao.erro = 'Motivo da rejeição é obrigatório.';

    return;
  }

  decisao.enviando = true;

  router.post(
    route('tdap.viagens.validar', decisao.viagem.id),
    { aprovada: decisao.aprovada, obs_aprovacao: decisao.obs },
    {
      preserveScroll: true,
      onFinish: () => {
        decisao.enviando = false;
        decisao.aberta = false;
      },
    },
  );
}

function textoEspera(dias) {
  if (dias === null || dias === undefined) return '';
  if (dias <= 0) return 'hoje';

  return `há ${dias} ${dias === 1 ? 'dia' : 'dias'}`;
}

function fmtDateTime(d) {
  if (!d) return '—';

  return new Date(d).toLocaleString('pt-BR');
}

function fmtNum(n) {
  if (n === null || n === undefined) return '—';

  return Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtMoeda(n) {
  if (n === null || n === undefined) return '—';

  return Number(n).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

const CAMPOS_MOBILE = [
  { key: 'c1', label: 'Prestador' },
  { key: 'c2', label: 'Libera' },
  { key: 'c3', label: 'Data / Espera' },
  { key: 'c4', label: 'Prazo' },
];
</script>
