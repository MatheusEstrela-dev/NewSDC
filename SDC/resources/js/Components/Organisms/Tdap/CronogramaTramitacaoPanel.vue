<template>
  <div class="space-y-6">
    <div v-if="carregando && !detalhe" class="text-center py-10 modal-serie-apoio">Carregando acompanhamento...</div>
    <div v-else-if="erro" class="text-center py-10 text-red-600">{{ erro }}</div>

    <template v-else-if="detalhe">
      <!--
        Fase atual + etapas: mesma linguagem visual da Serie Historica
        (modal-serie-*), para o Acompanhamento e o Historico parecerem uma
        janela so.
      -->
      <div class="modal-serie-cartao rounded-xl p-4 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="min-w-0">
            <p class="text-xs modal-serie-apoio">Fase atual</p>
            <h4 class="text-base font-semibold modal-serie-titulo">{{ detalhe.fase.label }}</h4>
          </div>
          <div class="flex items-center gap-2">
            <FaseTramiteBadge :fase="detalhe.fase" />
            <Badge v-if="detalhe.arquivado" variant="neutral" size="sm">Arquivado</Badge>
          </div>
        </div>

        <ol class="grid grid-cols-5 gap-2">
          <li v-for="f in detalhe.fases" :key="f.value" class="flex flex-col items-center text-center gap-1">
            <span
              class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-semibold"
              :class="classeDoPasso(f)"
            >
              <CheckIcon v-if="f.ordem < detalhe.fase.ordem || concluido" class="w-4 h-4" />
              <template v-else>{{ f.ordem }}</template>
            </span>
            <span class="text-xs font-medium modal-serie-titulo leading-tight">{{ f.label }}</span>
            <span class="text-[11px] modal-serie-apoio leading-tight">{{ f.responsavel }}</span>
          </li>
        </ol>

        <div v-if="!concluido" class="inline-flex items-center gap-2 modal-serie-pilula px-3 py-1 rounded-full text-xs">
          <UsersIcon class="w-4 h-4 modal-serie-apoio" />
          <span class="modal-serie-apoio">Responsável agora:</span>
          <span class="modal-serie-valor">{{ detalhe.fase.responsavel }}</span>
        </div>

        <!-- Entregues: o fluxo acabou, entao o cartao resume o que foi executado. -->
        <dl v-if="concluido && detalhe.resumo" class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
          <div class="modal-serie-pilula rounded-lg px-3 py-2">
            <dt class="modal-serie-apoio">Ativado em</dt>
            <dd class="modal-serie-valor">{{ fmtData(detalhe.resumo.ativado_em) || '—' }}</dd>
          </div>
          <div class="modal-serie-pilula rounded-lg px-3 py-2">
            <dt class="modal-serie-apoio">Encerrado em</dt>
            <dd class="modal-serie-valor">{{ fmtData(detalhe.resumo.encerrado_em) || '—' }}</dd>
          </div>
          <div class="modal-serie-pilula rounded-lg px-3 py-2">
            <dt class="modal-serie-apoio">Viagens</dt>
            <dd class="modal-serie-valor">{{ detalhe.resumo.viagens_realizadas }} / {{ detalhe.resumo.viagens_previstas }}</dd>
          </div>
          <div class="modal-serie-pilula rounded-lg px-3 py-2">
            <dt class="modal-serie-apoio">Água entregue</dt>
            <dd class="modal-serie-valor">{{ fmtM3(detalhe.resumo.volume_entregue) }} / {{ fmtM3(detalhe.resumo.volume_contratado) }} m³</dd>
          </div>
        </dl>

        <!-- Acoes: so as do responsavel da fase, decididas no backend. -->
        <div v-if="detalhe.acoes.length" class="space-y-2">
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="a in detalhe.acoes"
              :key="a.value"
              type="button"
              size="sm"
              :variant="a.value === 'devolver' ? 'danger' : 'primary'"
              :disabled="!!a.bloqueio || enviando"
              :title="a.bloqueio || a.label"
              @click="iniciar(a)"
            >
              {{ a.label }}
            </Button>
          </div>
          <p
            v-for="a in detalhe.acoes.filter(x => x.bloqueio)"
            :key="`b-${a.value}`"
            class="text-xs text-amber-700 dark:text-amber-300"
          >
            <strong>{{ a.label }}:</strong> {{ a.bloqueio }}
          </p>
        </div>
      </div>

      <!--
        Linha do tempo: todo o fluxo deste cronograma (tramites + Historico),
        no mesmo trilho, marcador e cartao do CronogramaHistoricoModal.
      -->
      <h4 v-if="detalhe.tramites.length" class="text-sm font-semibold modal-serie-titulo -mb-2">
        {{ concluido ? 'Fluxo completo do cronograma' : 'Fluxo do cronograma' }}
        <span class="font-normal modal-serie-apoio">({{ detalhe.tramites.length }} etapa(s))</span>
      </h4>
      <ol v-if="detalhe.tramites.length" class="modal-serie-trilho relative ml-5 space-y-6">
        <li v-for="t in detalhe.tramites" :key="t.id" class="ml-8 relative">
          <span
            class="absolute flex items-center justify-center w-9 h-9 rounded-full -left-12 modal-serie-marcador"
            :class="estiloDaAcao(t.acao).cor"
          >
            <component :is="estiloDaAcao(t.acao).icone" class="w-4 h-4" />
          </span>

          <div class="modal-serie-cartao rounded-xl p-4">
            <div class="min-w-0 mb-2">
              <h4 class="text-base font-semibold modal-serie-titulo truncate">{{ t.acao_label }}</h4>
              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs modal-serie-apoio">
                <Badge :variant="estiloDaAcao(t.acao).variant" size="sm">{{ estiloDaAcao(t.acao).rotulo }}</Badge>
                <span>{{ fmtDataHora(t.data) }}</span>
              </div>
            </div>

            <p v-if="t.fase_anterior" class="text-sm modal-serie-texto leading-relaxed">{{ t.fase_anterior }} → {{ t.fase_nova }}</p>
            <p v-if="t.observacao" class="mt-1 text-sm modal-serie-texto leading-relaxed whitespace-pre-line">{{ t.observacao }}</p>

            <div class="mt-3 inline-flex items-center gap-2 modal-serie-pilula px-3 py-1 rounded-full text-xs">
              <UsersIcon class="w-4 h-4 modal-serie-apoio" />
              <span class="modal-serie-apoio">Responsável:</span>
              <span class="modal-serie-valor">{{ t.usuario || '—' }}</span>
            </div>
          </div>
        </li>
      </ol>
      <div v-else class="text-center py-6 modal-serie-apoio">Nenhum evento registrado.</div>
    </template>

    <Modal :show="!!acaoEmCurso" max-width="md" @close="cancelar">
      <div class="p-6 space-y-4">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
          {{ acaoEmCurso?.label }} — cronograma {{ detalhe?.numero }}
        </h3>
        <div>
          <InputLabel
            for="observacao-tramite"
            :value="acaoEmCurso?.exige_observacao ? 'Motivo da devolução *' : 'Observação (opcional)'"
          />
          <textarea
            id="observacao-tramite"
            v-model="observacao"
            rows="4"
            maxlength="2000"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
          />
          <InputError :message="erroObservacao" class="mt-2" />
        </div>
        <div class="flex justify-end gap-2">
          <Button type="button" variant="secondary" size="sm" @click="cancelar">Cancelar</Button>
          <Button
            type="button"
            size="sm"
            :variant="acaoEmCurso?.value === 'devolver' ? 'danger' : 'primary'"
            :disabled="enviando || (acaoEmCurso?.exige_observacao && !observacao.trim())"
            @click="confirmar"
          >
            {{ enviando ? 'Enviando...' : acaoEmCurso?.label }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import FaseTramiteBadge from '@/Components/Organisms/Tdap/FaseTramiteBadge.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import ArrowRightIcon from '@/Components/Icons/ArrowRightIcon.vue';
import ArrowLeftIcon from '@/Components/Icons/ArrowLeftIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';

const props = defineProps({
  cronogramaId: { type: Number, required: true },
});

const emit = defineEmits(['tramitado']);

const detalhe = ref(null);
const carregando = ref(false);
const erro = ref('');
const enviando = ref(false);
const acaoEmCurso = ref(null);
const observacao = ref('');
const erroObservacao = ref('');

const concluido = computed(() => detalhe.value?.fase.value === 'entregue');

/*
 * Rotulo, cor e icone de cada tramite (AcaoTramite), no molde de
 * eventLabel/eventColor/eventIcon do CronogramaHistoricoModal.
 */
const ESTILO_DA_ACAO = {
  enviar:   { rotulo: 'Envio',      variant: 'info',    cor: 'bg-blue-600/90 text-white',    icone: ArrowRightIcon },
  aprovar:  { rotulo: 'Aprovação',  variant: 'success', cor: 'bg-emerald-600/90 text-white', icone: CheckCircleIcon },
  devolver: { rotulo: 'Devolução',  variant: 'danger',  cor: 'bg-red-600/90 text-white',     icone: ArrowLeftIcon },
  ativar:   { rotulo: 'Ativação',   variant: 'success', cor: 'bg-violet-600/90 text-white',  icone: TruckIcon },
  encerrar: { rotulo: 'Entrega',    variant: 'info',    cor: 'bg-teal-600/90 text-white',    icone: CheckBadgeIcon },
  // Eventos do Historico (linhaDoTempo no TramitacaoCronogramaService).
  criacao:   { rotulo: 'Criação',    variant: 'success', cor: 'bg-blue-600/90 text-white',    icone: PlusIcon },
  prorrogar: { rotulo: 'Prorrogação', variant: 'warning', cor: 'bg-amber-500/90 text-white',   icone: CalendarIcon },
  edicao:    { rotulo: 'Evento',     variant: 'warning', cor: 'bg-amber-500/90 text-white',   icone: PencilIcon },
  arquivo:   { rotulo: 'Arquivamento', variant: 'info',  cor: 'bg-cyan-500/90 text-white',    icone: ArchiveBoxIcon },
};

function estiloDaAcao(acao) {
  return ESTILO_DA_ACAO[acao] ?? { rotulo: 'Evento', variant: 'default', cor: 'bg-slate-600 text-white', icone: ExclamationTriangleIcon };
}

async function carregar() {
  carregando.value = true;
  erro.value = '';
  try {
    const res = await fetch(route('tdap.acompanhamento.detalhe', props.cronogramaId), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    detalhe.value = await res.json();
  } catch (_) {
    erro.value = 'Não foi possível carregar o acompanhamento.';
  } finally {
    carregando.value = false;
  }
}

onMounted(carregar);
watch(() => props.cronogramaId, () => { detalhe.value = null; carregar(); });

function classeDoPasso(f) {
  const atual = detalhe.value.fase.ordem;
  if (f.ordem < atual || concluido.value) return 'bg-emerald-600/90 text-white';
  if (f.ordem === atual) return 'bg-blue-600/90 text-white ring-4 ring-blue-500/20';
  return 'border-2 border-slate-300 dark:border-slate-600 modal-serie-apoio';
}

function iniciar(acao) {
  acaoEmCurso.value = acao;
  observacao.value = '';
  erroObservacao.value = '';
}

function cancelar() {
  if (enviando.value) return;
  acaoEmCurso.value = null;
}

function confirmar() {
  enviando.value = true;
  router.post(
    route('tdap.acompanhamento.tramitar', props.cronogramaId),
    { acao: acaoEmCurso.value.value, observacao: observacao.value },
    {
      preserveScroll: true,
      onSuccess: (page) => {
        // Bloqueio de negocio (ex.: outra pessoa ja moveu o cronograma) volta
        // como redirect com flash `error`, nao como erro de validacao: o
        // Inertia chama onSuccess. A janela fica aberta e mostra o motivo, e
        // a tela recarrega para refletir a fase real.
        const bloqueio = page?.props?.flash?.error;
        if (bloqueio) {
          erroObservacao.value = bloqueio;
          carregar();
          emit('tramitado');
          return;
        }

        acaoEmCurso.value = null;
        carregar();
        emit('tramitado');
      },
      onError: (errors) => {
        erroObservacao.value = errors.observacao ?? errors.acao ?? '';
      },
      onFinish: () => { enviando.value = false; },
    },
  );
}

function fmtData(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}

function fmtM3(v) {
  return Number(v ?? 0).toLocaleString('pt-BR', { maximumFractionDigits: 0 });
}

function fmtDataHora(iso) {
  return iso ? new Date(iso).toLocaleString('pt-BR') : '';
}
</script>
