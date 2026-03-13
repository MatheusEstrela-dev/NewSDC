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
</script>

<template>
  <div class="space-y-6">
    <!-- Toggle View Mode -->
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
        :class="viewMode === 'geral'
          ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
          : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 border border-transparent'"
        @click="setViewMode('geral')"
      >
        <GlobeAmericasIcon class="w-4 h-4" />
        Total Geral
      </button>
      <button
        v-if="municipiosDisponiveis.length > 0"
        type="button"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all"
        :class="viewMode === 'municipio'
          ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
          : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 border border-transparent'"
        @click="setViewMode('municipio')"
      >
        <MapPinIcon class="w-4 h-4" />
        Por Municipio
      </button>
    </div>

    <!-- Municipio Selector -->
    <div v-if="viewMode === 'municipio' && municipiosDisponiveis.length > 0" class="w-full md:w-64">
      <select
        v-model="selectedMunicipioId"
        class="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      >
        <option
          v-for="mun in municipiosDisponiveis"
          :key="mun.municipio_id"
          :value="mun.municipio_id"
        >
          {{ mun.municipio_nome }}
        </option>
      </select>
    </div>

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
        <div class="flex items-baseline gap-2">
          <span class="text-slate-500 dark:text-slate-400 text-sm">Quantidade:</span>
          <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300 rounded-lg font-semibold">
            {{ formatNumber(totalDanosHumanos) }}
          </span>
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
      <div class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-purple-100 dark:bg-purple-500/20 rounded-lg">
            <BanknotesIcon class="w-5 h-5 text-purple-500 dark:text-purple-400" />
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 uppercase tracking-wide">Prejuizos Economicos Privados</span>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-slate-500 dark:text-slate-400 text-sm">Valor do prejuizo (R$):</span>
          <span class="px-3 py-1 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-300 rounded-lg font-semibold">
            {{ formatCurrency(totalPrejuizosPrivados) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
