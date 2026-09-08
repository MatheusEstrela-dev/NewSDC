<template>
  <CollapsibleSection
    namespace="cedec"
    section-id="indicadores-municipais"
    title="Indicadores municipais"
    subtitle="Referência IBGE/CEDEC — não editável nesta tela"
    :icon="ChartBarSquareIcon"
    tom="neutro"
    :expandido-por-padrao="!isMobile"
  >
    <div class="space-y-4">
      <!--
        O formulario legado misturava dado de prefeitura com indicador do municipio,
        e a CEDEC editava os dois no mesmo lugar. Aqui o bloco e de leitura, e a tela
        diz isso em vez de deixar o usuario descobrir ao tentar salvar.
      -->
      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <LockClosedIcon class="h-3.5 w-3.5" />
          Somente leitura
        </span>
        <span class="text-xs text-slate-500 dark:text-slate-400">
          Alterar exige correção na base de origem.
        </span>
      </div>

      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="item in itens" :key="item.rotulo" class="min-w-0">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ item.rotulo }}
          </dt>
          <dd class="mt-0.5 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ item.valor }}
          </dd>
        </div>
      </dl>

      <p class="border-t border-slate-200 pt-3 text-xs text-slate-500 dark:border-slate-700/50 dark:text-slate-400">
        Origem do dado: <span class="font-mono">{{ indicadores.origem }}</span>
      </p>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue';
import { ChartBarSquareIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { useMobile } from '@/Composables/useMobile';

/**
 * Indicadores municipais em modo leitura. Recebe exatamente o retorno de
 * CedecPrefeituraService::indicadoresMunicipais(), incluindo a chave `origem`, que
 * vai para a tela sem reescrita: quem le precisa saber de qual base o numero veio
 * para saber onde corrigir.
 */
const props = defineProps({
  indicadores: { type: Object, required: true },
});

const { isMobile } = useMobile();

const VAZIO = 'Não informado';

function numero(valor, casas = 0) {
  if (valor === null || valor === undefined || valor === '') {
    return VAZIO;
  }

  return Number(valor).toLocaleString('pt-BR', {
    minimumFractionDigits: casas,
    maximumFractionDigits: casas,
  });
}

function texto(valor) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return VAZIO;
  }

  return String(valor);
}

const itens = computed(() => [
  { rotulo: 'População urbana', valor: numero(props.indicadores.populacao) },
  { rotulo: 'População rural', valor: numero(props.indicadores.pop_rural) },
  { rotulo: 'Área', valor: texto(props.indicadores.area) },
  { rotulo: 'Macrorregião', valor: texto(props.indicadores.macrorregiao) },
  { rotulo: 'Território de desenvolvimento', valor: texto(props.indicadores.territorio_desenv) },
  { rotulo: 'Distância de BH (km)', valor: numero(props.indicadores.distancia_bh, 1) },
  { rotulo: 'Caminhões pipa', valor: numero(props.indicadores.qtd_pipa) },
  { rotulo: 'Latitude do município', valor: texto(props.indicadores.latitude) },
  { rotulo: 'Longitude do município', valor: texto(props.indicadores.longitude) },
]);
</script>
