<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import IndicadorSalvamentoProva from '@/Components/Molecules/Treinamento/Sala/IndicadorSalvamentoProva.vue';
import QuestaoProva from '@/Components/Molecules/Treinamento/Sala/QuestaoProva.vue';
import ResultadoProva from '@/Components/Molecules/Treinamento/Sala/ResultadoProva.vue';
import { formatarNota } from '@/Composables/treinamento/formatarNota';
import { useProvaSala } from '@/Composables/treinamento/useProvaSala';

const props = defineProps({
  item: { type: Object, required: true },
  // Rotas da prova (carregar, iniciar, responder, enviar); null na previa do professor.
  urls: { type: Object, default: null },
});

const emit = defineEmits(['estado', 'revisar']);

const prova = useProvaSala({
  urls: props.urls,
  situacaoInicial: props.item.prova,
  aoAtualizarEstado: (estado) => emit('estado', estado),
});

const {
  situacao, ultimoResultado, tentativa, resultado, respostas, respondidas, salvamento,
  carregando, iniciando, enviando, erro,
} = prova;

const somenteLeitura = computed(() => props.urls === null);
const indice = ref(0);
const confirmando = ref(false);
const titulo = ref(null);

const questaoAtual = computed(() => tentativa.value?.questoes[indice.value] ?? null);
const total = computed(() => tentativa.value?.questoes.length ?? 0);
const naoRespondidas = computed(() => total.value - respondidas.value);
const ehUltima = computed(() => indice.value === total.value - 1);
const emTentativa = computed(() => tentativa.value !== null && resultado.value === null);
const temAberta = computed(() => situacao.value?.tentativa_aberta_id != null);

const numeroTentativa = computed(() => (tentativa.value
  ? tentativa.value.numero
  : (situacao.value?.tentativas_enviadas ?? 0) + 1));

const motivoBloqueio = computed(() => {
  if (!props.item.liberado) return 'Conclua antes os demais itens obrigatórios desta pasta para liberar a prova.';
  if (situacao.value?.aprovada) return null;
  if (situacao.value?.em_preparacao && !temAberta.value) return 'O professor ainda está preparando as questões desta prova. Ela não é exigida até ficar pronta.';
  if (situacao.value?.travada) return 'Suas tentativas acabaram. O professor precisa liberar uma nova tentativa para você continuar.';

  return null;
});

const rotuloSituacao = computed(() => {
  if (situacao.value?.aprovada) return { variant: 'success', texto: 'Aprovado' };
  if (situacao.value?.travada) return { variant: 'danger', texto: 'Tentativas esgotadas' };
  if (temAberta.value) return { variant: 'info', texto: 'Em andamento' };
  if (situacao.value?.em_preparacao) return { variant: 'warning', texto: 'Em preparação' };

  return { variant: 'default', texto: 'Não realizada' };
});

const podeComecar = computed(() => props.item.liberado
  && !situacao.value?.aprovada
  && !situacao.value?.travada
  && (situacao.value?.pode_iniciar === true || temAberta.value));

async function focarTitulo() {
  await nextTick();
  titulo.value?.focus();
}

async function comecar() {
  if (await prova.iniciar()) {
    indice.value = 0;
    confirmando.value = false;
    focarTitulo();
  }
}

function irPara(novo) {
  if (novo < 0 || novo >= total.value) return;

  indice.value = novo;
  confirmando.value = false;
  focarTitulo();
}

async function confirmarEnvio() {
  if (await prova.enviar()) {
    confirmando.value = false;
    focarTitulo();
  }
}

onMounted(() => {
  prova.carregar();
});
</script>

<template>
  <div class="mx-auto w-full max-w-3xl space-y-5 p-4 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <Badge variant="warning">Prova</Badge>
        <h2
          ref="titulo"
          tabindex="-1"
          class="mt-2 text-xl font-bold text-slate-800 focus:outline-none dark:text-slate-100"
        >
          {{ item.titulo }}
        </h2>
      </div>
      <IndicadorSalvamentoProva v-if="emTentativa" :estado="salvamento" @tentar-novamente="prova.tentarSalvarNovamente" />
    </div>

    <Text v-if="erro" size="sm" color="danger" role="alert">{{ erro }}</Text>

    <ResultadoProva
      v-if="resultado"
      :resultado="resultado"
      :situacao="situacao"
      @revisar="emit('revisar')"
      @nova-tentativa="prova.voltarParaRegras"
    />

    <!-- Respondendo -->
    <template v-else-if="emTentativa && questaoAtual">
      <nav class="flex flex-wrap gap-1.5" aria-label="Questões da prova">
        <button
          v-for="(q, i) in tentativa.questoes"
          :key="q.id"
          type="button"
          class="h-8 w-8 rounded-md border text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500"
          :class="[
            i === indice ? 'ring-2 ring-amber-500' : '',
            respostas[q.id] != null
              ? 'border-emerald-600 bg-emerald-600 text-white'
              : 'border-slate-300 text-slate-600 dark:border-slate-600 dark:text-slate-300',
          ]"
          :aria-current="i === indice ? 'step' : undefined"
          :aria-label="`Questão ${i + 1}${respostas[q.id] != null ? ', respondida' : ', sem resposta'}`"
          @click="irPara(i)"
        >
          {{ i + 1 }}
        </button>
      </nav>

      <QuestaoProva
        :key="questaoAtual.id"
        :questao="questaoAtual"
        :numero="indice + 1"
        :total="total"
        :selecionada="respostas[questaoAtual.id] ?? null"
        :desabilitada="enviando"
        @responder="prova.responder(questaoAtual.id, $event)"
      />

      <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
        <Button variant="outline" size="sm" :disabled="indice === 0 || enviando" @click="irPara(indice - 1)">&lsaquo; Anterior</Button>
        <Button variant="outline" size="sm" :disabled="ehUltima || enviando" @click="irPara(indice + 1)">Próxima &rsaquo;</Button>
        <span class="flex-1" />
        <Button variant="success" size="sm" :disabled="enviando" @click="confirmando = true">Enviar prova</Button>
      </div>

      <div
        v-if="confirmando"
        class="space-y-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-500/10"
        role="alertdialog"
        aria-labelledby="confirmar-envio-titulo"
      >
        <Text id="confirmar-envio-titulo" size="sm" weight="semibold">Enviar a prova agora?</Text>
        <Text size="sm">
          <template v-if="naoRespondidas > 0">
            Você deixou {{ naoRespondidas }} {{ naoRespondidas === 1 ? 'questão' : 'questões' }} sem resposta; elas contam como erradas.
          </template>
          <template v-else>Todas as questões foram respondidas.</template>
          Depois do envio não é possível alterar as respostas.
        </Text>
        <div class="flex gap-2">
          <Button variant="success" size="sm" :loading="enviando" @click="confirmarEnvio">Confirmar envio</Button>
          <Button variant="outline" size="sm" :disabled="enviando" @click="confirmando = false">Voltar à prova</Button>
        </div>
      </div>
    </template>

    <!-- Regras -->
    <template v-else>
      <div v-if="situacao" class="space-y-4">
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <dt class="text-xs text-slate-500 dark:text-slate-400">Questões</dt>
            <dd class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ situacao.total_questoes }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <dt class="text-xs text-slate-500 dark:text-slate-400">Nota mínima</dt>
            <dd class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ formatarNota(situacao.nota_minima, situacao.escala_nota) }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <dt class="text-xs text-slate-500 dark:text-slate-400">Tentativa</dt>
            <dd class="text-lg font-bold text-slate-800 dark:text-slate-100">
              {{ Math.min(numeroTentativa, situacao.tentativas_permitidas) }} de {{ situacao.tentativas_permitidas }}
            </dd>
          </div>
          <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <dt class="text-xs text-slate-500 dark:text-slate-400">Situação</dt>
            <dd class="mt-1"><Badge :variant="rotuloSituacao.variant">{{ rotuloSituacao.texto }}</Badge></dd>
          </div>
        </dl>

        <Text v-if="situacao.aprovada" size="sm" color="success">
          Você foi aprovado nesta prova<template v-if="situacao.nota_final != null"> com nota {{ formatarNota(situacao.nota_final, situacao.escala_nota) }}</template>.
        </Text>
        <Text v-else-if="ultimoResultado" size="sm" color="muted">
          Última tentativa ({{ ultimoResultado.numero }}): nota {{ formatarNota(ultimoResultado.nota, ultimoResultado.escala_nota ?? situacao.escala_nota) }},
          {{ ultimoResultado.acertos }} de {{ ultimoResultado.total }} corretas.
        </Text>

        <Text v-if="motivoBloqueio" size="sm" color="muted">{{ motivoBloqueio }}</Text>
        <Text v-if="somenteLeitura" size="sm" color="muted">Na pré-visualização a prova não pode ser iniciada.</Text>

        <Button v-if="!somenteLeitura && podeComecar" :loading="iniciando || carregando" @click="comecar">
          {{ temAberta ? 'Retomar prova' : 'Iniciar prova' }}
        </Button>
      </div>
      <Text v-else size="sm" color="muted">As regras desta prova não estão disponíveis.</Text>
    </template>
  </div>
</template>
