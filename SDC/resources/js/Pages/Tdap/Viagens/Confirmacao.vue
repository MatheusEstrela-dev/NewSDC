<template>
  <Head title="TDAP — Confirmar Viagens" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Confirmar ou reprovar viagens"
      :description="municipio ? `Viagens entregues em ${municipio} aguardando sua decisão` : 'Viagens aguardando decisão do município'"
      :icon="ClockIcon"
    />

    <!-- Sem município de lotação a fila é vazia por decisão, não por acaso.
         Sem esse aviso a tela pareceria "sem viagens" e esconderia o cadastro
         incompleto. -->
    <div
      v-if="semMunicipio"
      class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-900/10 p-4 text-sm text-amber-800 dark:text-amber-200"
    >
      Sua conta não está vinculada a nenhum órgão municipal, então não há viagens a confirmar.
      Procure o administrador para vincular seu usuário à COMPDEC do município.
    </div>

    <div v-if="podeAgir" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 dark:border-slate-700/40 bg-white dark:bg-slate-900/40 px-4 py-3">
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 cursor-pointer">
        <input
          type="checkbox"
          class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
          :checked="todasSelecionaveisMarcadas"
          :indeterminate.prop="algumasMarcadas && !todasSelecionaveisMarcadas"
          @change="alternarTodas"
        />
        Selecionar todas dentro do limite ({{ selecionaveis.length }})
      </label>

      <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm text-slate-500">{{ selecionadas.length }} selecionada(s)</span>
        <Button
          v-if="canReprovar"
          variant="danger"
          size="md"
          type="button"
          :icon="XMarkIcon"
          icon-position="left"
          :disabled="selecionadas.length === 0"
          @click="abrirReprovacao"
        >
          Reprovar
        </Button>
        <Button
          v-if="canConfirmar"
          variant="primary"
          size="md"
          type="button"
          :icon="CheckIcon"
          icon-position="left"
          :disabled="selecionadas.length === 0"
          @click="dialogo.aberto = true"
        >
          Confirmar recebimento
        </Button>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <ResponsiveTable
        :items="viagens.data"
        :mobile-fields="CAMPOS_MOBILE"
        :get-item-title="(v) => v.cronograma_numero"
        :get-item-subtitle="(v) => v.caminhao_placa"
        :get-item-variant="(v) => (bloqueada(v) ? 'danger' : 'default')"
        empty-message="Nenhuma viagem aguardando confirmação"
      >
        <template #table>
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
              <tr>
                <th class="px-4 py-3 w-10"></th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Cronograma</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Caminhão</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Recebido</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Data da viagem</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Prazo de entrega</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr
                v-for="v in viagens.data"
                :key="v.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/30"
                :class="bloqueada(v) ? 'opacity-60' : ''"
              >
                <td class="px-4 py-3">
                  <input
                    v-if="podeAgir"
                    type="checkbox"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:opacity-40"
                    :value="v.id"
                    v-model="selecionadas"
                    :disabled="bloqueada(v)"
                    :title="bloqueada(v) ? 'Fora do limite do cronograma — não é possível confirmar nem reprovar' : ''"
                  />
                </td>

                <td class="px-4 py-3">
                  <span class="font-mono font-semibold">{{ v.cronograma_numero }}</span>
                  <EstadoBadge v-if="v.cronograma_estado" :estado="v.cronograma_estado" class="ml-2" />
                </td>

                <td class="px-4 py-3">
                  <div class="font-mono">{{ v.caminhao_placa }}</div>
                  <div class="text-xs text-slate-500">
                    {{ [v.caminhao_marca, v.caminhao_modelo].filter(Boolean).join(' ') || '—' }}
                  </div>
                </td>

                <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap">
                  {{ fmtNum(v.m3_da_viagem) }} m³
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                  {{ fmtDateTime(v.data_registro) }}
                </td>

                <td class="px-4 py-3">
                  <PrazoBadge :dias-restantes="v.dias_restantes" :proxima-vencer="v.proxima_vencer" />
                  <div v-if="bloqueada(v)" class="text-xs text-red-600 mt-1">
                    fora do limite do cronograma — não é possível confirmar nem reprovar
                  </div>
                </td>
              </tr>

              <tr v-if="viagens.data.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                  <ClockIcon class="mx-auto h-10 w-10 text-slate-300 mb-2" />
                  <p class="font-medium text-slate-900 dark:text-slate-100">Nada a confirmar</p>
                  <p class="text-sm mt-1">Todas as viagens do município já foram confirmadas.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </template>

        <template #mobile-c1="{ item: v }">
          <label class="flex items-center gap-2">
            <input
              v-if="podeAgir"
              type="checkbox"
              class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:opacity-40"
              :value="v.id"
              v-model="selecionadas"
              :disabled="bloqueada(v)"
            />
            <span>{{ fmtNum(v.m3_da_viagem) }} m³</span>
          </label>
        </template>

        <template #mobile-c2="{ item: v }">
          {{ fmtDateTime(v.data_registro) }}
        </template>

        <template #mobile-c3="{ item: v }">
          <PrazoBadge :dias-restantes="v.dias_restantes" :proxima-vencer="v.proxima_vencer" />
          <span v-if="bloqueada(v)" class="block text-xs text-red-600 mt-1">fora do limite do cronograma</span>
        </template>
      </ResponsiveTable>
    </div>

    <Pagination :pagination="viagens.meta" @page-change="irParaPagina" />

    <ConfirmDialog
      :is-open="dialogo.aberto"
      title="Confirmar recebimento"
      :message="`Você confirma o recebimento de ${selecionadas.length} viagem(ns), totalizando ${fmtNum(totalM3Selecionado)} m³?`"
      description="A confirmação atesta que a água chegou ao município. A liberação para pagamento continua sendo feita pela CEDEC."
      variant="success"
      confirm-text="Confirmar"
      :loading="dialogo.enviando"
      @confirm="enviarConfirmacao"
      @cancel="dialogo.aberto = false"
    >
      <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Observação (opcional)</label>
        <textarea
          v-model="dialogo.obs"
          rows="3"
          maxlength="500"
          class="block w-full text-sm rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500"
        />
      </div>
    </ConfirmDialog>

    <ConfirmDialog
      :is-open="reprovacao.aberto"
      title="Reprovar viagens"
      :message="`Você atesta que ${selecionadas.length} viagem(ns), totalizando ${fmtNum(totalM3Selecionado)} m³, NÃO foram recebidas?`"
      description="A reprovação impede que a CEDEC aprove essas viagens para pagamento."
      variant="danger"
      confirm-text="Reprovar"
      :loading="reprovacao.enviando"
      @confirm="enviarReprovacao"
      @cancel="reprovacao.aberto = false"
    >
      <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Motivo da reprovação *</label>
        <textarea
          v-model="reprovacao.motivo"
          rows="3"
          maxlength="500"
          class="block w-full text-sm rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-red-500 focus:ring-red-500"
        />
        <p v-if="reprovacao.erro" class="mt-1 text-xs text-red-600">{{ reprovacao.erro }}</p>
      </div>
    </ConfirmDialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import PrazoBadge from '@/Components/Organisms/Tdap/PrazoBadge.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  viagens:      { type: Object, default: () => ({ data: [], meta: {} }) },
  filtros:      { type: Object, default: () => ({}) },
  municipio:    { type: String, default: null },
  semMunicipio: { type: Boolean, default: false },
  canConfirmar: { type: Boolean, default: false },
  canReprovar:  { type: Boolean, default: false },
});

const selecionadas = ref([]);

const podeAgir = computed(() => (props.canConfirmar || props.canReprovar) && !props.semMunicipio);

/**
 * Viagem fora do limite do cronograma: nem confirma, nem reprova.
 *
 * `pode_decidir_confirmacao` vem do backend (LimiteDoCronograma), a mesma
 * regra que o service aplica -- aqui e conforto visual; a recusa de verdade
 * acontece no servidor, senao a regra ficaria a um F12 de distancia.
 */
function bloqueada(v) {
  return v.pode_decidir_confirmacao === false;
}

const selecionaveis = computed(() => props.viagens.data.filter((v) => !bloqueada(v)));

const todasSelecionaveisMarcadas = computed(
  () => selecionaveis.value.length > 0 && selecionadas.value.length === selecionaveis.value.length,
);

const algumasMarcadas = computed(() => selecionadas.value.length > 0);

const totalM3Selecionado = computed(() => props.viagens.data
  .filter((v) => selecionadas.value.includes(v.id))
  .reduce((soma, v) => soma + Number(v.m3_da_viagem ?? 0), 0));

function alternarTodas(evento) {
  selecionadas.value = evento.target.checked ? selecionaveis.value.map((v) => v.id) : [];
}

const dialogo = ref({ aberto: false, obs: '', enviando: false });

function enviarConfirmacao() {
  dialogo.value.enviando = true;

  router.post(
    route('tdap.viagens.confirmar-lote'),
    { ids: selecionadas.value, obs_confirmacao: dialogo.value.obs },
    {
      preserveScroll: true,
      onSuccess: () => {
        selecionadas.value = [];
        dialogo.value.obs = '';
      },
      onFinish: () => {
        dialogo.value.enviando = false;
        dialogo.value.aberto = false;
      },
    },
  );
}

const reprovacao = ref({ aberto: false, motivo: '', enviando: false, erro: '' });

function abrirReprovacao() {
  reprovacao.value = { aberto: true, motivo: '', enviando: false, erro: '' };
}

function enviarReprovacao() {
  if (reprovacao.value.motivo.trim().length < 5) {
    reprovacao.value.erro = 'Descreva o motivo com ao menos 5 caracteres.';
    return;
  }

  reprovacao.value.enviando = true;
  reprovacao.value.erro = '';

  router.post(
    route('tdap.viagens.reprovar-lote'),
    { ids: selecionadas.value, motivo: reprovacao.value.motivo },
    {
      preserveScroll: true,
      onSuccess: () => {
        selecionadas.value = [];
        reprovacao.value.aberto = false;
      },
      onError: (erros) => {
        reprovacao.value.erro = erros.motivo ?? erros.ids ?? 'Não foi possível reprovar.';
      },
      onFinish: () => {
        reprovacao.value.enviando = false;
      },
    },
  );
}

function irParaPagina(page) {
  router.get(route('tdap.viagens.confirmacao'), { ...props.filtros, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function fmtDateTime(d) {
  if (!d) return '—';

  return new Date(d).toLocaleString('pt-BR');
}

function fmtNum(n) {
  if (n === null || n === undefined) return '—';

  return Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const CAMPOS_MOBILE = [
  { key: 'c1', label: 'Recebido' },
  { key: 'c2', label: 'Data da viagem' },
  { key: 'c3', label: 'Prazo de entrega' },
];
</script>
