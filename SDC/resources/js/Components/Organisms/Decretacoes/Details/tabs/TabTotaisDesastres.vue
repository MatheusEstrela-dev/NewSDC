<script setup>
import { ref, computed } from 'vue';
import {
  UsersIcon,
  HomeModernIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  GlobeAmericasIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
});

const viewMode = ref('geral');
const selectedMunicipioId = ref(null);

function formatNumber(value) {
  if (value === null || value === undefined) return '0';
  return new Intl.NumberFormat('pt-BR').format(value);
}

function formatCurrency(value) {
  if (value === null || value === undefined) return 'R$ 0,00';
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value);
}

const totaisGeral = computed(() => {
  return props.processo?.totais?.geral || props.processo?.totais || {
    danos_humanos: { total: 0 },
    danos_materiais: { quantidade: 0, valor: 0 },
    prejuizos_publicos: { total: 0 },
    prejuizos_privados: { total: 0 },
  };
});

const municipiosDisponiveis = computed(() => {
  return props.processo?.totais?.por_municipio || [];
});

const totaisSelecionados = computed(() => {
  if (viewMode.value === 'geral') {
    return totaisGeral.value;
  }
  const municipio = municipiosDisponiveis.value.find((m) => m.municipio_id === selectedMunicipioId.value);
  return municipio?.totais || totaisGeral.value;
});

const totalDanosHumanos = computed(() => {
  const dh = totaisSelecionados.value?.danos_humanos;
  if (!dh) return 0;
  if (typeof dh.total === 'number') return dh.total;
  return (dh.mortos || 0) + (dh.feridos || 0) + (dh.enfermos || 0) +
         (dh.desabrigados || 0) + (dh.desalojados || 0) +
         (dh.desaparecidos || 0) + (dh.outros_afetados || 0);
});

const totalDanosMateriais = computed(() => {
  const dm = totaisSelecionados.value?.danos_materiais;
  if (!dm) return { quantidade: 0, valor: 0 };
  return {
    quantidade: dm.quantidade || dm.total_quantidade || 0,
    valor: dm.valor || dm.total_valor || 0,
  };
});

const totalPrejuizosPublicos = computed(() => {
  const pp = totaisSelecionados.value?.prejuizos_publicos;
  if (!pp) return 0;
  return pp.total || pp.valor || 0;
});

const totalPrejuizosPrivados = computed(() => {
  const pp = totaisSelecionados.value?.prejuizos_privados;
  if (!pp) return 0;
  return pp.total || pp.valor || 0;
});

function setViewMode(mode) {
  viewMode.value = mode;
  if (mode === 'municipio' && municipiosDisponiveis.value.length > 0 && !selectedMunicipioId.value) {
    selectedMunicipioId.value = municipiosDisponiveis.value[0].municipio_id;
  }
}

// Rotulos dos itens de danos ambientais (chaves vindas do DanosAmbientaisService).
const ROTULOS_AMBIENTAIS = {
  agua: 'Poluicao ou contaminacao da agua',
  ar: 'Poluicao ou contaminacao do ar',
  solo: 'Poluicao ou contaminacao do solo',
  hidrico: 'Diminuicao ou exaurimento hidrico',
  incendio: "Incendios em parques, APA's ou APP's",
};

const danosAmbientais = computed(() => totaisSelecionados.value?.danos_ambientais ?? null);

/**
 * Itens de danos ambientais prontos para a lista.
 *
 * O backend entrega duas formas: no bloco geral cada item e a CONTAGEM de
 * municipios que marcaram o dano; por municipio e a resposta (Sim/Nao) com a
 * intensidade. A distincao aqui e pelo tipo do valor.
 */
const ambientaisItens = computed(() => {
  const itens = danosAmbientais.value?.itens;

  if (!itens) return [];

  return Object.entries(itens).map(([chave, valor]) => {
    const rotulo = ROTULOS_AMBIENTAIS[chave] ?? chave;

    if (typeof valor === 'number') {
      return { chave, rotulo, contagem: valor, resposta: null, faixa: null };
    }

    return {
      chave,
      rotulo,
      contagem: null,
      resposta: valor?.resposta ?? null,
      faixa: valor?.faixa ?? null,
    };
  });
});

const ambientaisMarcados = computed(() => danosAmbientais.value?.marcados ?? 0);
const ambientaisMunicipios = computed(() => danosAmbientais.value?.municipios_afetados ?? null);

const dhDetails = computed(() => {
  const dh = totaisSelecionados.value?.danos_humanos || {};
  return {
    obitos: dh.obitos || 0,
    feridos: dh.feridos || 0,
    desalojados: dh.desalojados || 0,
    desabrigados: dh.desabrigados || 0,
    desaparecidos: dh.desaparecidos || 0,
    outros_afetados: dh.outros_afetados || 0,
  };
});
</script>

<template>
  <div class="space-y-6">
    <!-- Cards Grid -->

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Danos Humanos -->
      <div class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-lg">
            <UsersIcon class="w-5 h-5 text-blue-500 dark:text-blue-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Danos Humanos</span>
        </div>
        <div class="space-y-4">
          <div class="flex items-baseline gap-2">
            <span class="text-slate-500 dark:text-slate-400 text-sm">Quantidade Total:</span>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold">
              {{ formatNumber(totalDanosHumanos) }}
            </span>
          </div>

          <!-- Detailed human damage -->
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 pt-4 border-t border-slate-100 dark:border-slate-700/50">
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Obitos</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.obitos) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Feridos</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.feridos) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Desalojados</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.desalojados) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Desabrigados</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.desabrigados) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Desaparecidos</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.desaparecidos) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Outros Afetados</span>
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-bold text-sm">{{ formatNumber(dhDetails.outros_afetados) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Danos Materiais -->
      <div class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-amber-100 dark:bg-amber-500/20 rounded-lg">
            <HomeModernIcon class="w-5 h-5 text-amber-500 dark:text-amber-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Danos Materiais</span>
        </div>
        <div class="space-y-2">
          <div class="flex items-baseline gap-2">
            <span class="text-slate-500 dark:text-slate-400 text-sm">Quantidades danificadas:</span>
            <span class="px-3 py-1 bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 rounded-lg font-semibold">
              {{ formatNumber(totalDanosMateriais.quantidade) }}
            </span>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-slate-500 dark:text-slate-400 text-sm">Valor (R$):</span>
            <span class="px-3 py-1 bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 rounded-lg font-semibold">
              {{ formatCurrency(totalDanosMateriais.valor) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Danos Ambientais (parte dos danos materiais no FIDE) -->
      <div
        v-if="ambientaisItens.length > 0"
        class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm md:col-span-2"
      >
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-teal-100 dark:bg-teal-500/20 rounded-lg">
            <GlobeAmericasIcon class="w-5 h-5 text-teal-500 dark:text-teal-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Danos Ambientais</span>
          <span class="px-3 py-1 bg-teal-100 dark:bg-teal-500/20 text-teal-600 dark:text-teal-300 rounded-lg font-semibold text-sm">
            {{ formatNumber(ambientaisMarcados) }} marcado(s)
          </span>
          <span v-if="ambientaisMunicipios !== null" class="text-xs text-slate-400 dark:text-slate-500">
            em {{ formatNumber(ambientaisMunicipios) }} municipio(s)
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="item in ambientaisItens"
            :key="item.chave"
            class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-slate-700/30 last:border-0"
          >
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ item.rotulo }}</span>

            <!-- Bloco geral: quantos municipios marcaram o dano -->
            <span
              v-if="item.contagem !== null"
              class="px-3 py-1 rounded-lg font-semibold text-sm shrink-0"
              :class="item.contagem > 0
                ? 'bg-teal-100 dark:bg-teal-500/20 text-teal-600 dark:text-teal-300'
                : 'bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500'"
            >
              {{ formatNumber(item.contagem) }}
            </span>

            <!-- Por municipio: resposta e intensidade -->
            <span v-else class="flex flex-col items-end gap-1 shrink-0">
              <span
                class="px-3 py-1 rounded-lg font-semibold text-sm"
                :class="item.resposta === 'Sim'
                  ? 'bg-teal-100 dark:bg-teal-500/20 text-teal-600 dark:text-teal-300'
                  : 'bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500'"
              >
                {{ item.resposta ?? 'Nao informado' }}
              </span>
              <span v-if="item.faixa" class="text-xs text-slate-400 dark:text-slate-500">{{ item.faixa }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Prejuizos Economicos Publicos -->
      <div class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-emerald-100 dark:bg-emerald-500/20 rounded-lg">
            <BuildingOffice2Icon class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Prejuizos Economicos Publicos</span>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-slate-500 dark:text-slate-400 text-sm">Valor do prejuizo (R$):</span>
          <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 rounded-lg font-semibold">
            {{ formatCurrency(totalPrejuizosPublicos) }}
          </span>
        </div>
      </div>

      <!-- Prejuizos Economicos Privados -->
      <div class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-red-200 dark:border-red-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-red-100 dark:bg-red-500/20 rounded-lg">
            <BanknotesIcon class="w-5 h-5 text-red-500 dark:text-red-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Prejuizos Economicos Privados</span>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-slate-500 dark:text-slate-400 text-sm">Valor do prejuizo (R$):</span>
          <span class="px-3 py-1 bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-300 rounded-lg font-semibold">
            {{ formatCurrency(totalPrejuizosPrivados) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
