<template>
  <TdapPainel
    rotulo="Território"
    titulo="Capacitados por município"
    subtitulo="Município de realização da turma: o tamanho do círculo segue os participantes, a cor segue as capacitações"
  >
    <ListEmptyState
      v-if="pontos.length === 0"
      :icon="MapIcon"
      title="Nenhum município no recorte"
      helper="Não há capacitados com município de realização para os filtros escolhidos."
    />

    <div v-else class="relative h-[320px] w-full min-w-0 sm:h-[420px]">
      <MapaLeaflet class="h-full w-full" :pontos="pontos" :centro="[-18.5, -44.5]" :zoom="6">
        <template #legenda>
          <div class="rounded-lg border border-slate-200 bg-white/95 px-3 py-2 text-[11px] text-slate-600 shadow dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-300">
            <p class="mb-1 font-semibold">Capacitações</p>
            <ul class="space-y-0.5">
              <li v-for="faixa in FAIXAS" :key="faixa.rotulo" class="flex items-center gap-1.5">
                <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: faixa.cor }"></span>
                {{ faixa.rotulo }}
              </li>
            </ul>
          </div>
        </template>
      </MapaLeaflet>
    </div>

    <template #rodape>
      <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">EAD / sem município</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">Turmas sem município de realização não aparecem no mapa.</p>
        </div>
        <p class="text-sm text-slate-700 dark:text-slate-200">
          <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ formatarNumero(semMunicipio.participantes) }}</span>
          participantes em
          <span class="font-semibold">{{ formatarNumero(semMunicipio.capacitacoes) }}</span>
          {{ semMunicipio.capacitacoes === 1 ? 'capacitação' : 'capacitações' }}
        </p>
      </div>
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Mapa de MG com um circulo por municipio (props.mapa). Reaproveita o
 * MapaLeaflet do projeto, que ja cuida de tiles, popup escapado e limpeza do
 * mapa ao desmontar. Aqui so se traduz o dado em raio e cor.
 */
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  mapa: { type: Array, default: () => [] },
  semMunicipio: { type: Object, default: () => ({ participantes: 0, capacitacoes: 0 }) },
});

const RAIO_MIN = 5;
const RAIO_MAX = 22;

// Verde claro para escuro: quanto mais capacitacoes, mais escuro.
const FAIXAS = [
  { limite: 1, cor: '#a7f3d0', rotulo: '1' },
  { limite: 2, cor: '#34d399', rotulo: '2' },
  { limite: 4, cor: '#059669', rotulo: '3 a 4' },
  { limite: Infinity, cor: '#064e3b', rotulo: '5 ou mais' },
];

const corPorCapacitacoes = (qtd) => FAIXAS.find((faixa) => qtd <= faixa.limite).cor;

const pontos = computed(() => {
  const validos = props.mapa.filter((m) => Number.isFinite(Number(m.lat)) && Number.isFinite(Number(m.lng)));
  const maximo = Math.max(1, ...validos.map((m) => m.participantes));

  return validos.map((m) => ({
    id: m.municipio_id,
    latitude: m.lat,
    longitude: m.lng,
    // Raiz: a area do circulo cresce com o numero, nao o diametro.
    raio: RAIO_MIN + (RAIO_MAX - RAIO_MIN) * Math.sqrt(m.participantes / maximo),
    cor: corPorCapacitacoes(m.capacitacoes),
    popup: {
      titulo: m.nome,
      linhas: [
        { rotulo: 'Participantes', valor: formatarNumero(m.participantes) },
        { rotulo: 'Capacitações', valor: formatarNumero(m.capacitacoes) },
      ],
    },
  }));
});
</script>
