<template>
  <Modal :show="open" max-width="2xl" @close="$emit('close')">
    <div class="flex max-h-full min-h-0 flex-col modal-serie-corpo">
      <!-- Mesma faixa das janelas do modulo (modal-serie-historica.css): a
           quinta janela do TDAP nao tem por que nascer com outra identidade. -->
      <div class="shrink-0 px-4 py-4 md:px-6 md:py-5 modal-serie-header">
        <div class="flex items-start justify-between gap-4">
          <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-700/40 bg-slate-900/40">
              <ClipboardDocumentListIcon class="h-5 w-5 text-slate-200" />
            </div>
            <div class="min-w-0">
              <h3 class="truncate text-lg font-semibold text-white">Nova Vistoria</h3>
              <p class="truncate text-sm text-slate-200/80">
                Escolha o caminhão-tanque que será vistoriado
              </p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-lg p-2 text-slate-200/80 transition-all hover:bg-white/10 hover:text-white"
            title="Fechar"
            @click="$emit('close')"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4">
          <label class="sr-only" for="busca-caminhao-vistoria">Buscar caminhão</label>
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              id="busca-caminhao-vistoria"
              ref="campoBusca"
              v-model="busca"
              type="search"
              autocomplete="off"
              placeholder="Placa, prestador, marca ou modelo"
              class="w-full rounded-lg border border-slate-700/50 bg-slate-900/50 py-2 pl-9 pr-3 text-sm text-slate-100 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500"
              @keyup.enter="abrirUnicoResultado"
            />
          </div>

          <!-- Filtro por situacao so quando a lista traz a situacao: a tela de
               historico passa a frota crua, sem esse calculo. -->
          <div v-if="temSituacao" class="mt-3 flex flex-wrap items-center gap-2">
            <button
              v-for="chip in chips"
              :key="chip.valor"
              type="button"
              class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold transition"
              :class="situacao === chip.valor
                ? 'border-blue-400 bg-blue-500/20 text-white'
                : 'border-slate-700/50 text-slate-200/80 hover:border-slate-500 hover:text-white'"
              @click="situacao = chip.valor"
            >
              {{ chip.rotulo }}
              <span class="ml-1 opacity-70">{{ chip.total }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
        <div v-if="carregando" class="py-10 text-center modal-serie-apoio">
          Carregando a frota...
        </div>

        <ul v-else-if="filtrados.length" class="space-y-2">
          <li v-for="caminhao in filtrados" :key="caminhao.id">
            <button
              type="button"
              class="modal-serie-cartao flex w-full items-center gap-3 rounded-xl p-3 text-left transition hover:border-blue-400"
              @click="$emit('select', caminhao)"
            >
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-mono text-sm font-bold modal-serie-titulo">{{ caminhao.placa }}</span>
                  <VistoriaSituacaoBadge
                    v-if="caminhao.situacao_vistoria"
                    :situacao="caminhao.situacao_vistoria"
                    :dias-restantes="caminhao.vistoria?.dias_restantes ?? null"
                  />
                </div>
                <p class="mt-0.5 truncate text-xs modal-serie-apoio">
                  {{ nomeDoPrestador(caminhao) || 'Sem prestador' }}
                  <span v-if="descricaoDoVeiculo(caminhao)"> · {{ descricaoDoVeiculo(caminhao) }}</span>
                </p>
              </div>

              <ChevronRightIcon class="h-5 w-5 shrink-0 modal-serie-apoio" />
            </button>
          </li>
        </ul>

        <div v-else class="py-10 text-center modal-serie-apoio">
          <TruckIcon class="mx-auto h-10 w-10 text-slate-400" />
          <p class="mt-3 text-sm font-semibold modal-serie-titulo">Nenhum caminhão encontrado</p>
          <p class="mt-1 text-sm">
            {{ caminhoes.length
              ? 'Ajuste a busca ou o filtro de situação.'
              : 'Cadastre um caminhão na frota antes de registrar a vistoria.' }}
          </p>
        </div>
      </div>

      <div class="shrink-0 border-t border-slate-200 px-4 py-3 text-xs modal-serie-apoio dark:border-slate-700/50 md:px-6">
        A vistoria é sempre registrada a partir de um caminhão — escolher aqui já abre a ficha com os dados do veículo preenchidos.
      </div>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Seletor do caminhao para abrir uma nova vistoria.
 *
 * A vistoria nasce de um caminhao: a rota e `frota/{caminhao}/vistorias/nova`
 * e o formulario ja chega preenchido com os dados do veiculo. So que o botao
 * de cabecalho -- "quero registrar uma vistoria" -- nao sabe de qual veiculo
 * se trata, e a listagem mostra 15 de 132 por pagina: mandar o operador achar
 * a placa na grade, paginando, so para depois abrir o menu da linha e um fluxo
 * que comeca cobrando o que ele ja sabe.
 *
 * Aqui ele digita a placa e entra na ficha. A situacao de vistoria aparece ao
 * lado de cada placa porque a pergunta que traz alguem a esta janela quase
 * sempre e "quais estao vencidos?".
 */
import { computed, nextTick, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import VistoriaSituacaoBadge from '@/Components/Organisms/Tdap/VistoriaSituacaoBadge.vue';
import ChevronRightIcon from '@/Components/Icons/ChevronRightIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import MagnifyingGlassIcon from '@/Components/Icons/MagnifyingGlassIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  /**
   * Frota para escolher. Aceita o formato do CaminhaoIndexResource
   * (`prestador_nome`, `situacao_vistoria`) e o cru do model (`prestador`),
   * que e o que a tela de historico de vistorias ja tem em maos.
   */
  caminhoes: { type: Array, default: () => [] },
  carregando: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'select']);

const busca = ref('');
const situacao = ref('');
const campoBusca = ref(null);

// Abrir zera o recorte anterior e poe o cursor na busca: quem chega aqui vem
// com uma placa na cabeca, e o caminho mais curto e digitar.
watch(() => props.open, async (aberto) => {
  if (! aberto) return;

  busca.value = '';
  situacao.value = '';

  await nextTick();
  campoBusca.value?.focus();
});

const temSituacao = computed(() => props.caminhoes.some((c) => Boolean(c.situacao_vistoria)));

function nomeDoPrestador(caminhao) {
  return caminhao.prestador_nome ?? caminhao.prestador?.nome ?? '';
}

function descricaoDoVeiculo(caminhao) {
  return [caminhao.marca, caminhao.modelo].filter(Boolean).join(' ');
}

const porSituacao = computed(() => props.caminhoes.filter(
  (c) => ! situacao.value || c.situacao_vistoria === situacao.value,
));

const filtrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();

  if (! termo) return porSituacao.value;

  return porSituacao.value.filter((c) => [
    c.placa,
    nomeDoPrestador(c),
    c.marca,
    c.modelo,
  ].some((campo) => String(campo ?? '').toLowerCase().includes(termo)));
});

function contar(valor) {
  return valor
    ? props.caminhoes.filter((c) => c.situacao_vistoria === valor).length
    : props.caminhoes.length;
}

// A ordem e a da urgencia, nao a do enum: quem nunca foi vistoriado e quem
// venceu sao os dois motivos de alguem abrir esta janela.
const chips = computed(() => [
  { valor: '', rotulo: 'Toda a frota', total: contar('') },
  { valor: 'sem_vistoria', rotulo: 'Sem vistoria', total: contar('sem_vistoria') },
  { valor: 'vencida', rotulo: 'Vencida', total: contar('vencida') },
  { valor: 'apto', rotulo: 'Apto', total: contar('apto') },
]);

/**
 * Enter na busca quando sobrou um so: digitar a placa inteira e ainda ter de
 * mover a mao para o mouse para confirmar o obvio e atrito puro. Com dois ou
 * mais na lista nao ha o que adivinhar, e o Enter nao faz nada.
 */
function abrirUnicoResultado() {
  if (filtrados.value.length === 1) {
    emit('select', filtrados.value[0]);
  }
}
</script>
