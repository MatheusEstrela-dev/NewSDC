<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useMobile } from '@/Composables/useMobile';
import { useTabs } from '@/Composables/core/useTabs';
import {
  InformationCircleIcon,
  DocumentTextIcon,
  ChartBarIcon,
  LifebuoyIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

import TipoDesastreResumo from './TipoDesastreResumo.vue';
import TabInformacoes from './tabs/TabInformacoes.vue';
import TabDadosDecreto from './tabs/TabDadosDecreto.vue';
import TabTotaisDesastres from './tabs/TabTotaisDesastres.vue';
import TabPedidoAH from './tabs/TabPedidoAH.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  processo: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const { activeTab, setActiveTab, getTabClass } = useTabs(1);

// matchMedia, nao innerWidth: a mesma medida das media queries do Tailwind.
const { isMobile } = useMobile();

const tabs = computed(() => [
  { id: 1, label: 'Informacoes Basicas', icon: InformationCircleIcon, descricao: 'Protocolo, tipo de desastre e municipios atingidos.' },
  { id: 2, label: 'Dados do Decreto', icon: DocumentTextIcon, descricao: 'Numero, datas e situacao do decreto municipal.' },
  { id: 3, label: 'Totais de Desastres', icon: ChartBarIcon, descricao: 'Danos humanos, materiais e ambientais declarados.' },
  { id: 4, label: 'Pedido AH', icon: LifebuoyIcon, descricao: 'Pedidos de ajuda humanitaria vinculados a esta decretacao.', badge: props.processo?.pedidos_ah?.length || null },
]);

const abaAtiva = computed(() => tabs.value.find((t) => t.id === activeTab.value));

/**
 * Rola a aba escolhida para o centro da tira.
 *
 * Sem isto a tira e reativa so pela metade: cabem tres abas em 375px, entao
 * tocar na ultima visivel deixa a recem-escolhida colada na borda, com as
 * seguintes escondidas e nenhuma pista de que existem. `inline: 'center'`
 * centra na horizontal e `block: 'nearest'` impede que a pagina role junto --
 * a tira e `sticky`, e um scroll vertical aqui tiraria o cabecalho de vista.
 */
const tiraRef = ref(null);

watch(activeTab, async () => {
  await nextTick();
  tiraRef.value
    ?.querySelector('[data-aba-ativa="true"]')
    ?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
});

const tipoReconhecimentoBadge = computed(() => {
  const tipo = props.processo?.tipo_reconhecimento;
  const config = {
    SE: { label: 'SE', class: 'bg-blue-500/20 text-blue-300 border-blue-500/30' },
    EE: { label: 'EE', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' },
    CP: { label: 'CP', class: 'bg-red-500/20 text-red-300 border-red-500/30' },
  };
  return config[tipo] || config.SE;
});

/**
 * Aparencia da aba, com DOIS visuais por largura.
 *
 * No mobile e a pilula solida do modal de configuracoes: em tela pequena o
 * contorno claro do desktop nao se le como "aba ativa", ainda mais numa tira
 * que rola de lado e mostra as vizinhas pela metade. No desktop (`md:`) volta
 * o contorno original -- a tela nao mudou e nao ha motivo para mexer nela.
 */
function classeDaAba(id) {
  return activeTab.value === id
    ? 'bg-blue-600 text-white shadow-sm md:border md:border-blue-200 md:bg-blue-100 md:text-blue-600 md:dark:border-blue-500/30 md:dark:bg-blue-500/20 md:dark:text-blue-400'
    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700/50 md:hover:text-slate-700 md:dark:hover:text-slate-200';
}

function handleClose() {
  emit('close');
}
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="handleClose">
    <div v-if="processo" class="flex max-h-full min-h-0 flex-col overflow-hidden scrollbar-hide">
      <!-- Header -->
      <div class="bg-gradient-to-r from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 px-4 md:px-6 py-3 md:py-4 border-b border-slate-200 dark:border-slate-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-slate-700/50 rounded-lg">
              <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 dark:text-amber-400" />
            </div>
            <div>
              <h2 class="text-base md:text-lg font-semibold text-slate-800 dark:text-white">Detalhes da Decretacao</h2>
              <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 break-all">
                Protocolo: <span class="font-semibold text-blue-600 dark:text-blue-400">{{ processo.protocolo_fide || processo.n_protocolo_fide || 'N/A' }}</span>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700/50 rounded-lg transition-colors"
            @click="handleClose"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Info Rapida: Protocolo + Tipo + Reconhecimento -->
        <div class="mt-3 flex flex-wrap items-center gap-3">
          <!-- Repete o protocolo da linha logo acima: no telefone e so ruido. -->
          <span class="hidden md:inline-block px-3 py-1.5 bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-semibold border border-blue-200 dark:border-blue-500/30">
            {{ processo.protocolo_fide || processo.n_protocolo_fide || 'Sem Protocolo' }}
          </span>
          <span
            class="px-2.5 py-1 text-xs font-bold rounded-md border"
            :class="tipoReconhecimentoBadge.class"
          >
            {{ tipoReconhecimentoBadge.label }}
          </span>
          <span v-if="processo.data_entrada_formatada" class="text-sm text-slate-500 dark:text-slate-400">
            Entrada: {{ processo.data_entrada_formatada }}
          </span>
          <span v-if="processo.dias_restantes !== null && processo.vigente" class="px-2 py-1 bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-300 rounded text-xs font-medium">
            Vigente
          </span>
          <span v-else-if="processo.dias_restantes !== null" class="px-2 py-1 bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 rounded text-xs font-medium">
            Vencido
          </span>
        </div>

        <!-- Tipo de desastre: no desktop fica aqui; no mobile vai para a aba. -->
        <TipoDesastreResumo v-if="!isMobile" :processo="processo" class="mt-4" />

      </div>

      <!-- Tabs Navigation -->
      <!--
        MOBILE: a tira de abas sobe para o topo e gruda, como no modal de
        configuracoes.

        Na ordem do documento ela vem DEPOIS do cabecalho, e o cabecalho da
        decretacao ocupa uma tela inteira em 375px (titulo + protocolo + o
        cartao do tipo de desastre). As abas nasciam abaixo da dobra: quem
        abria o modal via um bloco de leitura e nenhuma navegacao. `order-first`
        resolve a entrada e `sticky` mantem as abas alcancaveis enquanto o
        conteudo rola. No desktop nada muda -- `md:order-none md:static`.
      -->
      <nav ref="tiraRef" class="order-first md:order-none sticky top-0 z-10 md:static flex flex-shrink-0 items-center gap-1.5 px-3 md:px-6 py-2.5 md:py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/50 overflow-x-auto scrollbar-hide">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm font-medium whitespace-nowrap transition-all"
          :class="classeDaAba(tab.id)"
          :data-aba-ativa="activeTab === tab.id"
          @click="setActiveTab(tab.id)"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          <span>{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="px-1.5 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-500/30 text-blue-600 dark:text-blue-300 rounded"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>

      <!--
        Nome e descricao da aba ativa, como no modal de configuracoes.

        So no mobile: a tira mostra tres abas de cada vez e a ativa pode estar
        cortada na borda: sem este bloco, quem rola a tira perde a referencia de
        onde esta. No desktop as quatro abas cabem inteiras e o titulo seria
        repeticao.
      -->
      <div v-if="isMobile && abaAtiva" class="flex-shrink-0 px-4 py-3 border-b border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ abaAtiva.label }}</h3>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ abaAtiva.descricao }}</p>
      </div>

      <!-- Tab Content -->
      <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain p-4 pb-10 md:p-6 md:pb-12 bg-slate-50 dark:bg-slate-900/50 scrollbar-hide">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="space-y-4">
          <div class="animate-pulse space-y-4">
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4"></div>
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
            <div class="grid grid-cols-2 gap-4 mt-6">
              <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
              <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
              <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
              <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>
          </div>
        </div>

        <Transition
          v-else
          mode="out-in"
          enter-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="activeTab === 1">
            <!--
              No mobile o resumo do desastre e a primeira coisa DENTRO da aba,
              nao um bloco fixo acima dela. Sai do caminho das outras tres abas,
              que passam a abrir com a tela toda para o proprio conteudo.
            -->
            <TipoDesastreResumo v-if="isMobile" :processo="processo" class="mb-4" />
            <TabInformacoes :processo="processo" />
          </div>
          <TabDadosDecreto v-else-if="activeTab === 2" :processo="processo" />
          <TabTotaisDesastres v-else-if="activeTab === 3" :processo="processo" />
          <TabPedidoAH v-else-if="activeTab === 4" :processo="processo" />
        </Transition>
      </div>

    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="p-12 text-center">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-slate-400">Carregando dados...</p>
    </div>
  </Modal>
</template>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
