<script setup>
/**
 * Leitura da etiqueta do chaveiro.
 *
 * O scan NAO grava nada: ele so pergunta ao backend qual e o proximo ato
 * daquela chave (retirar ou devolver) e abre o formulario correspondente. A
 * gravacao acontece no submit -- sem isso, um scan acidental abriria
 * movimentacao sem hodometro, e hodometro perdido nao se recupera.
 */
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import QrCodeIcon from '@/Components/Icons/QrCodeIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import Modal from '@/Components/Modal.vue';
import QrScanner from '@/Components/Molecules/QrScanner.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ReservaStatusBadge from '@/Components/Molecules/Plantao/ReservaStatusBadge.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
  minhasReservas: {
    type: Array,
    default: () => [],
  },
  toleranciaMinutos: {
    type: Number,
    default: 30,
  },
  plantaoAtivoId: {
    type: Number,
    default: null,
  },
  // filterOptions.niveis do backend, ja em {value, label}.
  niveis: {
    type: Array,
    default: () => [],
  },
});

const scannerRef = ref(null);
const resultado = ref(null);
const erroScan = ref('');
const lendo = ref(false);

const form = useForm({
  saida_hodometro: '',
  saida_combustivel: '',
  destino: '',
  motivo: '',
  retorno_hodometro: '',
  retorno_combustivel: '',
  alteracoes: '',
});

const isCheckin = computed(() => resultado.value?.acao === 'CHECKIN');

// `chave` vem dos controllers de check-in/check-out; `movimentacao` vem do
// MovimentacaoRetornoController, usado na devolucao sem reserva associada.
const erroDominio = computed(() => form.errors.chave || form.errors.movimentacao || '');

// Movimentacao aberta fora da agenda -- saida registrada pela tela de viaturas,
// ou anterior ao modulo de reservas. A chave se devolve do mesmo jeito; o que
// muda e a rota, porque nao ha reserva para concluir.
const semReserva = computed(() => !isCheckin.value && !resultado.value?.reserva);

const formatarInstante = (iso) => {
  if (!iso) return '--';

  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

async function onDecode(token) {
  if (lendo.value) return;

  lendo.value = true;
  erroScan.value = '';
  form.clearErrors();

  try {
    // axios e nao o router do Inertia: a resolucao do token e uma consulta que
    // devolve JSON, nao uma navegacao. Mesmo caminho da presenca por QR do
    // Treinamento.
    const { data } = await axios.post(route('plantao.chave.scan.resolver'), { qr_token: token });

    resultado.value = data;
    const corpo = data;

    // Para a camera: com o formulario aberto, continuar lendo so geraria
    // requisicoes repetidas do mesmo token.
    scannerRef.value?.parar();

    form.reset();
    form.saida_hodometro = corpo.viatura?.hodometro_atual ?? '';
    form.saida_combustivel = corpo.viatura?.combustivel_valor ?? '';
    form.destino = corpo.reserva?.destino ?? '';
    form.motivo = corpo.reserva?.motivo ?? '';
    form.retorno_hodometro = corpo.viatura?.hodometro_atual ?? '';
    form.retorno_combustivel = corpo.viatura?.combustivel_valor ?? '';
  } catch (e) {
    // A recusa de dominio vem com o texto pronto ("Voce nao tem reserva vigente
    // para a viatura X"). Trocar por mensagem generica esconderia justamente o
    // que a pessoa precisa fazer para resolver -- so a falha SEM resposta e que
    // vira "problema de rede".
    erroScan.value = e.response
      ? (e.response.data?.message ?? `Nao foi possivel ler a etiqueta (HTTP ${e.response.status}).`)
      : 'Falha de rede ao ler a etiqueta.';
    resultado.value = null;
  } finally {
    lendo.value = false;
  }
}

function limpar() {
  resultado.value = null;
  erroScan.value = '';
  form.clearErrors();
  form.reset();
}

function handleSubmit() {
  if (!resultado.value) return;

  const reservaId = resultado.value.reserva?.id ?? null;

  if (isCheckin.value) {
    // O resolver so devolve CHECKIN com reserva vigente: sem ela nao ha o que
    // enviar, e a guarda existe para nao postar em rota com id nulo.
    if (reservaId === null) return;

    form
      .transform((data) => ({
        saida_hodometro: data.saida_hodometro === '' ? null : Number(data.saida_hodometro),
        saida_combustivel: data.saida_combustivel || null,
        destino: data.destino || null,
        motivo: data.motivo || null,
        plantao_id: props.plantaoAtivoId ?? null,
      }))
      .post(route('plantao.chave.checkin', reservaId), {
        preserveScroll: true,
        onSuccess: () => limpar(),
      });

    return;
  }

  // Devolucao sem reserva associada cai na rota de retorno que ja existia: a
  // saida foi aberta fora da agenda (pela tela de viaturas, ou antes de as
  // reservas existirem) e nao ha reserva para concluir. Mesmo servico, mesma
  // permissao -- sem este desvio o botao nao faria nada para toda viatura que
  // ja estava em transito quando o modulo entrou.
  const destino = reservaId === null
    ? route('plantao.movimentacoes.retorno', resultado.value.movimentacao.id)
    : route('plantao.chave.checkout', reservaId);

  form
    .transform((data) => ({
      retorno_hodometro: data.retorno_hodometro === '' ? null : Number(data.retorno_hodometro),
      retorno_combustivel: data.retorno_combustivel || null,
      alteracoes: data.alteracoes || null,
    }))
    .post(destino, {
      preserveScroll: true,
      onSuccess: () => limpar(),
    });
}
</script>

<template>
  <div class="chave-scan-container">
    <PageHeader
      title="Chave da Viatura"
      description="Leia a etiqueta do chaveiro para retirar ou devolver"
      :icon="QrCodeIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
    />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-bold text-slate-900 dark:text-slate-100">Leitor</h2>

        <QrScanner ref="scannerRef" @decode="onDecode" />

        <p
          v-if="erroScan"
          class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ erroScan }}
        </p>

        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
          A retirada e liberada a partir de {{ toleranciaMinutos }} minutos antes do inicio da sua reserva.
        </p>
      </section>

      <section class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-bold text-slate-900 dark:text-slate-100">Minhas reservas</h2>

        <p v-if="minhasReservas.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
          Voce nao tem reserva ativa. Sem reserva, a chave nao e liberada.
        </p>

        <ul v-else class="space-y-2">
          <li
            v-for="reserva in minhasReservas"
            :key="reserva.id"
            class="flex items-center justify-between gap-3 rounded-md border border-slate-100 px-3 py-2 dark:border-slate-800"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200">
                {{ reserva.viatura_prefixo }} · {{ reserva.viatura_placa }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatarInstante(reserva.inicio_previsto) }} — {{ formatarInstante(reserva.fim_previsto) }}
              </p>
            </div>

            <ReservaStatusBadge :status="reserva.status_valor" :label="reserva.status" size="sm" />
          </li>
        </ul>
      </section>
    </div>

    <!--
      Modal, e nao painel abaixo do leitor. No celular o formulario nascia fora
      da tela: quem acabou de escanear via a camera parar e nada acontecer, e
      precisava rolar para descobrir que havia um formulario esperando. O modal
      sobe no ato do scan bem-sucedido, que e o unico momento em que ele existe.
    -->
    <Modal :show="!!resultado" max-width="lg" @close="limpar">
      <div v-if="resultado" class="bg-white dark:bg-slate-900">
        <header class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
          <div class="flex items-center gap-3 min-w-0">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
              :class="isCheckin
                ? 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300'
                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'"
            >
              <QrCodeIcon class="h-5 w-5" />
            </div>

            <div class="min-w-0">
              <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                {{ isCheckin ? 'Retirar a chave' : 'Devolver a chave' }}
              </h2>
              <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                {{ resultado.viatura.prefixo }} · {{ resultado.viatura.placa }}
              </p>
            </div>
          </div>

          <button
            type="button"
            class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
            aria-label="Fechar e ler outra"
            @click="limpar"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </header>

        <form class="max-h-[70vh] space-y-5 overflow-y-auto px-5 py-4" @submit.prevent="handleSubmit">
        <div
          v-if="erroDominio"
          class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ erroDominio }}
        </div>

        <p
          v-if="!isCheckin && resultado.movimentacao"
          class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300"
        >
          Saiu em {{ formatarInstante(resultado.movimentacao.saida_em) }} com
          {{ resultado.movimentacao.saida_hodometro?.toLocaleString('pt-BR') }} km.
          <span v-if="semReserva" class="block mt-1 text-amber-700 dark:text-amber-400">
            Esta saida foi registrada fora da agenda, sem reserva. A devolucao funciona normalmente.
          </span>
        </p>

        <template v-if="isCheckin">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.saida_hodometro"
              type="number"
              label="Hodometro de saida (km)"
              required
              inputmode="numeric"
              :error="form.errors.saida_hodometro"
            />

            <FormSelect
              v-model="form.saida_combustivel"
              label="Nivel de combustivel"
              required
              :options="niveis"
              :error="form.errors.saida_combustivel"
            />
          </div>

          <FormField v-model="form.destino" label="Destino" :error="form.errors.destino" />
          <FormField v-model="form.motivo" label="Motivo" :error="form.errors.motivo" />
        </template>

        <template v-else>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.retorno_hodometro"
              type="number"
              label="Hodometro de retorno (km)"
              required
              inputmode="numeric"
              :error="form.errors.retorno_hodometro"
            />

            <FormSelect
              v-model="form.retorno_combustivel"
              label="Nivel de combustivel"
              required
              :options="niveis"
              :error="form.errors.retorno_combustivel"
            />
          </div>

          <FormTextarea
            v-model="form.alteracoes"
            label="Alteracoes / avarias"
            :rows="3"
            :error="form.errors.alteracoes"
          />
        </template>

        </form>

        <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
          <!-- Cancelar volta ao leitor: no celular, fechar o modal E manter o
               resultado carregado deixaria a camera parada sem explicacao. -->
          <FormActions
            :submit-label="isCheckin ? 'Retirar chave' : 'Devolver chave'"
            cancel-label="Ler outra"
            :loading="form.processing"
            @cancel="limpar"
            @submit="handleSubmit"
          />
        </footer>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
.chave-scan-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
