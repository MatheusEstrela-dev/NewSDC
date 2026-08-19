<template>
  <Modal :show="show" max-width="2xl" @close="$emit('close')">
    <div class="bg-slate-900 text-slate-200">
      <div class="border-b border-slate-700/50 bg-gradient-to-r from-cyan-700/70 to-sky-600/40 px-6 py-5">
        <div class="flex items-start justify-between gap-4">
          <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-700/40 bg-slate-900/40">
              <ClockIcon class="h-5 w-5 text-slate-200" />
            </div>
            <div class="min-w-0">
              <h3 class="truncate text-lg font-semibold text-white">Serie Historica do Cadastro</h3>
              <p class="truncate text-sm text-slate-200/80">
                {{ beneficiario?.nome || '—' }}
                <span v-if="beneficiario?.municipio" class="text-slate-300/70">— {{ beneficiario.municipio }}</span>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-lg p-1 text-slate-200/80 transition hover:bg-slate-900/40 hover:text-white"
            aria-label="Fechar"
            @click="$emit('close')"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4 flex items-center gap-6 border-b border-slate-700/40">
          <button
            v-for="aba in ABAS"
            :key="aba.chave"
            type="button"
            class="-mb-px flex items-center gap-2 border-b-2 px-1 pb-2 text-sm font-medium transition"
            :class="abaAtiva === aba.chave
              ? 'border-white text-white'
              : 'border-transparent text-slate-300/70 hover:text-slate-100'"
            @click="abaAtiva = aba.chave"
          >
            {{ aba.rotulo }}
            <Badge v-if="contagem(aba.chave)" :variant="aba.variante" size="sm">{{ contagem(aba.chave) }}</Badge>
          </button>
        </div>
      </div>

      <div class="max-h-[60vh] overflow-y-auto p-6">
        <!-- Carregando e erro sao estados proprios: sem eles o modal abre vazio
             e parece que o cadastro nao tem historico nenhum. -->
        <div v-if="carregando" class="py-10 text-center text-sm text-slate-400">
          Carregando a serie historica...
        </div>

        <div v-else-if="erro" class="py-10 text-center">
          <p class="text-sm text-red-400">{{ erro }}</p>
          <button type="button" class="mt-3 text-sm text-cyan-400 hover:underline" @click="carregar">
            Tentar de novo
          </button>
        </div>

        <template v-else>
          <!--
            Cadeia primeiro, e segregada do relatorio: quem abre o historico quer
            saber EM QUE PONTO a fiscalizacao esta antes de ler evento por
            evento. O estado de cada etapa vem resolvido do servidor -- a ordem
            (fornecedor, COMPDEC, CEDEC) e regra de dominio e reimplementar aqui
            criaria uma segunda versao livre para divergir.
          -->
          <ol v-if="abaAtiva === 'cadeia'" class="space-y-3">
            <li
              v-for="(etapa, indice) in dados.cadeia?.etapas ?? []"
              :key="etapa.valor"
              class="flex gap-3 rounded-xl border border-slate-700/50 bg-slate-800/60 p-4"
            >
              <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                :class="MARCADOR_ETAPA[etapa.estado] ?? MARCADOR_ETAPA.bloqueada"
              >
                {{ indice + 1 }}
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h4 class="text-base font-semibold text-white">{{ etapa.rotulo }}</h4>
                  <Badge :variant="VARIANTE_ETAPA[etapa.estado] ?? 'secondary'" size="sm">
                    {{ TEXTO_ETAPA[etapa.estado] ?? etapa.estado }}
                  </Badge>
                </div>

                <p class="mt-1 text-xs text-slate-400">
                  <template v-if="etapa.estado === 'bloqueada'">Aguarda a etapa anterior.</template>
                  <template v-else-if="etapa.estado === 'disponivel'">Liberada para preenchimento.</template>
                  <template v-else>
                    <span v-if="etapa.numero_instalacao" class="font-mono">Nº {{ etapa.numero_instalacao }}</span>
                    <span v-if="etapa.data"> — {{ etapa.data }}</span>
                    <span v-if="etapa.engenheiro"> — {{ etapa.engenheiro }}</span>
                  </template>
                </p>
              </div>
            </li>
          </ol>

          <ol v-else-if="abaAtiva === 'timeline' && contagem('timeline')" class="relative ml-5 space-y-6 border-l border-slate-700">
            <li v-for="evento in dados.timeline" :key="evento.id" class="relative ml-8">
              <span
                class="absolute -left-[42px] flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-slate-900"
                :class="COR_DO_TIPO[evento.tipo] ?? COR_PADRAO"
              >
                <component :is="ICONE_DO_TIPO[evento.tipo] ?? ClockIcon" class="h-4 w-4" />
              </span>

              <div class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4">
                <h4 class="truncate text-base font-semibold text-white">{{ evento.titulo }}</h4>

                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                  <Badge :variant="VARIANTE_DO_TIPO[evento.tipo] ?? 'info'" size="sm">
                    {{ ROTULO_DO_TIPO[evento.tipo] ?? evento.tipo }}
                  </Badge>
                  <span class="font-mono">{{ evento.data }}</span>
                </div>

                <p v-if="evento.descricao" class="mt-2 whitespace-pre-line text-sm text-slate-300">
                  {{ evento.descricao }}
                </p>

                <p class="mt-3 text-xs text-slate-400">
                  Responsavel: <span class="font-medium text-slate-200">{{ evento.responsavel }}</span>
                </p>
              </div>
            </li>
          </ol>

          <div v-else-if="abaAtiva === 'vistorias' && contagem('vistorias')" class="space-y-3">
            <div
              v-for="v in dados.vistorias"
              :key="v.id"
              class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <h4 class="text-base font-semibold text-white">{{ v.titulo }}</h4>
                <Badge :variant="v.concluida ? 'success' : 'warning'" size="sm">
                  {{ v.concluida ? 'Concluida' : 'Em aberto' }}
                </Badge>
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                <span class="font-mono">{{ v.data || '—' }}</span>
                <span v-if="v.numero_instalacao">Nº instalacao {{ v.numero_instalacao }}</span>
                <span v-if="v.engenheiro">Eng. {{ v.engenheiro }}</span>
              </div>
              <p v-if="v.descricao" class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ v.descricao }}</p>
            </div>
          </div>

          <div v-else-if="abaAtiva === 'notificacoes' && contagem('notificacoes')" class="space-y-3">
            <div
              v-for="n in dados.notificacoes"
              :key="n.id"
              class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <h4 class="text-base font-semibold text-white">{{ n.titulo }}</h4>
                <Badge :variant="n.respondida ? 'success' : 'warning'" size="sm">
                  {{ n.respondida ? 'Respondida' : 'Em aberto' }}
                </Badge>
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                <span class="font-mono">{{ n.data || '—' }}</span>
                <span v-if="n.respondida_em">respondida em {{ n.respondida_em }}</span>
              </div>
              <p v-if="n.descricao" class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ n.descricao }}</p>
              <p class="mt-3 text-xs text-slate-400">
                Emitida por: <span class="font-medium text-slate-200">{{ n.responsavel }}</span>
              </p>
            </div>
          </div>

          <p v-else class="py-10 text-center text-sm text-slate-400">
            {{ VAZIO[abaAtiva] }}
          </p>
        </template>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import {
  ClockIcon,
  XMarkIcon,
  PlusCircleIcon,
  ClipboardDocumentCheckIcon,
  CheckCircleIcon,
  BellAlertIcon,
  ChatBubbleLeftRightIcon,
  FlagIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Precisa de `id` e `nome`; `municipio` e opcional, so para o cabecalho. */
  beneficiario: { type: Object, default: null },
});

defineEmits(['close']);

const ABAS = [
  { chave: 'cadeia', rotulo: 'Cadeia', variante: 'info' },
  { chave: 'timeline', rotulo: 'Timeline', variante: 'info' },
  { chave: 'vistorias', rotulo: 'Vistorias', variante: 'info' },
  { chave: 'notificacoes', rotulo: 'Notificacoes', variante: 'warning' },
];

const VAZIO = {
  cadeia: 'Cadeia de fiscalizacao indisponivel.',
  timeline: 'Nenhum evento registrado para este cadastro.',
  vistorias: 'Nenhuma etapa de fiscalizacao aberta.',
  notificacoes: 'Nenhum apontamento da fiscalizacao.',
};

const ROTULO_DO_TIPO = {
  criacao: 'Criacao',
  vistoria: 'Vistoria',
  conclusao: 'Etapa',
  notificacao: 'Notificacao',
  resposta: 'Resposta',
  situacao: 'Situacao',
  alocacao: 'Alocacao',
};

const VARIANTE_DO_TIPO = {
  criacao: 'success',
  vistoria: 'info',
  conclusao: 'success',
  notificacao: 'warning',
  resposta: 'info',
  situacao: 'info',
  alocacao: 'info',
};

// Classes literais: o Tailwind so inclui o que encontra escrito por extenso,
// entao montar `bg-${cor}-500` produziria classe que nunca existe no CSS.
const COR_DO_TIPO = {
  criacao: 'bg-emerald-600 text-white',
  vistoria: 'bg-sky-600 text-white',
  conclusao: 'bg-emerald-600 text-white',
  notificacao: 'bg-amber-600 text-white',
  resposta: 'bg-cyan-600 text-white',
  situacao: 'bg-indigo-600 text-white',
  alocacao: 'bg-violet-600 text-white',
};

const COR_PADRAO = 'bg-slate-600 text-white';

const ICONE_DO_TIPO = {
  criacao: PlusCircleIcon,
  vistoria: ClipboardDocumentCheckIcon,
  conclusao: CheckCircleIcon,
  notificacao: BellAlertIcon,
  resposta: ChatBubbleLeftRightIcon,
  situacao: FlagIcon,
  alocacao: TruckIcon,
};

// Espelha VistoriaTimeline: o mesmo estado tem que ter a mesma cor nas duas
// telas, senao "liberada" no modal e "liberada" na pagina parecem coisas
// diferentes.
const MARCADOR_ETAPA = {
  concluida: 'bg-emerald-600 text-white',
  em_aberto: 'bg-amber-500/20 text-amber-300',
  disponivel: 'bg-sky-500/20 text-sky-300',
  bloqueada: 'bg-slate-700 text-slate-400',
};

const TEXTO_ETAPA = {
  concluida: 'Concluida',
  em_aberto: 'Em aberto',
  disponivel: 'Liberada',
  bloqueada: 'Bloqueada',
};

const VARIANTE_ETAPA = {
  concluida: 'success',
  em_aberto: 'warning',
  disponivel: 'info',
  bloqueada: 'secondary',
};

const abaAtiva = ref('cadeia');
const carregando = ref(false);
const erro = ref('');
const dados = ref({ cadeia: null, timeline: [], vistorias: [], notificacoes: [] });

function contagem(chave) {
  // A cadeia tem sempre as tres etapas: um badge "3" fixo no rotulo nao informa
  // nada, entao ela nao conta.
  if (chave === 'cadeia') return 0;

  return dados.value?.[chave]?.length ?? 0;
}

/**
 * Busca a cada abertura, em vez de receber pronto por prop.
 *
 * E o que faz o painel mostrar o estado do MOMENTO: a listagem carrega 25 linhas
 * e fica aberta por muito tempo, e uma vistoria concluida por outro orgao nesse
 * intervalo nao apareceria se os dados viessem junto da pagina.
 */
async function carregar() {
  if (!props.beneficiario?.id) return;

  carregando.value = true;
  erro.value = '';
  dados.value = { cadeia: null, timeline: [], vistorias: [], notificacoes: [] };

  let url;

  try {
    url = route('cisternas.beneficiarios.historico', props.beneficiario.id);
  } catch {
    // O Ziggy lanca quando a rota nao esta na tabela, e essa tabela vem do
    // @routes -- renderizado no carregamento da PAGINA, nao a cada navegacao do
    // Inertia. Numa aba aberta desde antes do deploy a rota nova nao existe ali,
    // e so recarregar resolve. Dizer isso evita que a pessoa procure o problema
    // no cadastro.
    erro.value = 'Esta aba foi carregada antes desta funcionalidade existir. Recarregue a pagina.';
    carregando.value = false;

    return;
  }

  try {
    const resposta = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!resposta.ok) {
      // O status importa: 403 e problema de alcance do perfil e 500 e defeito
      // do servidor. A mensagem unica de antes mandava investigar a coisa
      // errada.
      erro.value = resposta.status === 403
        ? 'Voce nao tem alcance sobre este cadastro para ver o andamento dele.'
        : `Nao foi possivel carregar a serie historica (HTTP ${resposta.status}).`;

      return;
    }

    dados.value = await resposta.json();
  } catch {
    // Mensagem no lugar de modal vazio: sem isto a falha de rede seria lida
    // como "este cadastro nao tem historico".
    erro.value = 'Falha de rede ao buscar a serie historica.';
  } finally {
    carregando.value = false;
  }
}

watch(
  () => [props.show, props.beneficiario?.id],
  ([aberto]) => {
    if (!aberto) return;

    abaAtiva.value = 'cadeia';
    carregar();
  },
);
</script>
