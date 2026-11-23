<template>
  <TdapPainel
    rotulo="Território"
    :titulo="camadaAtual.titulo"
    :subtitulo="camadaAtual.subtitulo"
  >
    <ListEmptyState
      v-if="mapa.length === 0"
      :icon="MapIcon"
      title="Nenhum município no recorte"
      helper="Não há capacitados com município de realização para os filtros escolhidos."
    />

    <div v-else class="relative h-[360px] w-full min-w-0 sm:h-[460px]">
      <MapaLeaflet
        class="h-full w-full"
        :poligonos="poligonos"
        :pontos="pontos"
        :enquadrar-ids="idsEnquadrados"
        :centro="[-18.5, -44.5]"
        :zoom="6"
        @clique-poligono="aoClicarMunicipio"
      >
        <template #legenda>
          <div class="max-w-[11rem] rounded-lg border border-slate-200 bg-white/95 px-3 py-2 text-[11px] text-slate-600 shadow dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-300">
            <p class="mb-1 font-semibold">{{ legenda.titulo }}</p>

            <ul v-if="camada === 'cobertura'" class="space-y-0.5">
              <li v-for="item in legenda.itens" :key="item.rotulo" class="flex items-center gap-1.5">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: item.cor }"></span>
                {{ item.rotulo }}
              </li>
            </ul>

            <!-- Bolhas: circulos de referencia no mesmo raio do mapa. -->
            <ul v-else-if="camada === 'volume'" class="flex items-end gap-3">
              <li v-for="item in legenda.itens" :key="item.rotulo" class="flex flex-col items-center gap-1">
                <span
                  class="inline-block shrink-0 rounded-full border border-white/70"
                  :style="{ width: `${item.diametro}px`, height: `${item.diametro}px`, backgroundColor: item.cor, opacity: 0.8 }"
                ></span>
                <span class="tabular-nums">{{ item.rotulo }}</span>
              </li>
            </ul>

            <ul v-else class="max-h-36 space-y-0.5 overflow-y-auto pr-1">
              <li v-for="item in legenda.itens" :key="item.id">
                <button
                  type="button"
                  class="flex w-full items-center gap-1.5 rounded px-0.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800"
                  :class="item.id === redecDestacada ? 'font-semibold text-slate-900 dark:text-slate-100' : ''"
                  @click="alternarRedec(item.id)"
                >
                  <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-sm" :style="{ backgroundColor: item.cor }"></span>
                  <span class="truncate">{{ item.rotulo }}</span>
                </button>
              </li>
            </ul>
          </div>
        </template>
      </MapaLeaflet>

      <!-- Paginacao das tres camadas (um mapa so, RN12): setas, "n de 3" e bolinhas. -->
      <nav
        class="absolute right-3 top-3 z-[500] flex items-center gap-1 rounded-lg border border-slate-200 bg-white/95 p-1 shadow dark:border-slate-700 dark:bg-slate-900/90"
        aria-label="Camadas do mapa"
      >
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          title="Camada anterior"
          aria-label="Camada anterior"
          @click="passarCamada(-1)"
        >
          <ChevronRightIcon class="h-4 w-4 rotate-180" />
        </button>
        <div class="min-w-[9.5rem] px-1 text-center">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ indiceCamada + 1 }} de {{ CAMADAS.length }}</p>
          <p class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ camadaAtual.rotulo }}</p>
          <div class="mt-0.5 flex justify-center gap-1">
            <button
              v-for="(opcao, indice) in CAMADAS"
              :key="opcao.valor"
              type="button"
              class="h-1.5 rounded-full transition-all"
              :class="indice === indiceCamada ? 'w-4 bg-blue-600 dark:bg-blue-400' : 'w-1.5 bg-slate-300 hover:bg-slate-400 dark:bg-slate-600'"
              :title="opcao.rotulo"
              :aria-label="`Ir para ${opcao.rotulo}`"
              :aria-current="indice === indiceCamada ? 'true' : undefined"
              @click="camada = opcao.valor"
            ></button>
          </div>
        </div>
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          title="Próxima camada"
          aria-label="Próxima camada"
          @click="passarCamada(1)"
        >
          <ChevronRightIcon class="h-4 w-4" />
        </button>
      </nav>

      <p
        v-if="statusMalha !== 'pronta'"
        class="pointer-events-none absolute left-3 top-3 z-[500] rounded-md bg-white/90 px-2.5 py-1 text-xs text-slate-600 shadow dark:bg-slate-900/90 dark:text-slate-300"
      >
        {{ statusMalha === 'erro' ? 'Não foi possível carregar os limites municipais.' : 'Carregando limites municipais...' }}
      </p>
    </div>

    <template #rodape>
      <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Fora do mapa</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">Participações sem município em MG (EAD sem município informado ou pessoa de outro estado).</p>
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
 * Mapa de MG "3 em 1": um unico MapaLeaflet com paginacao entre camadas (RN12):
 *  1. cobertura: municipio pintado (azul monocromatico = teve concluidos,
 *     laranja = so em andamento);
 *  2. volume: bolhas de pessoas que concluiram, por municipio, sobre a malha neutra;
 *  3. redecs: municipios coloridos pela REDEC com a borda na mesma cor, para a
 *     regiao aparecer demarcada; atingidos fortes, demais esmaecidos.
 *
 * A malha vem de /geo/mg-municipios.geojson (nao pelo Inertia) e e cruzada por
 * IBGE com `mapa` (dado) e `territorio` (catalogo). O estilo de cada camada
 * mora em camadasMapaIndicadores.js; aqui so se monta a prop do MapaLeaflet.
 */
import { computed, ref } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import ChevronRightIcon from '@/Components/Icons/ChevronRightIcon.vue';
import { useTheme } from '@/Composables/ui/useTheme';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';
import { useMalhaMunicipal } from '@/Composables/treinamento/useMalhaMunicipal';
import {
  CAMADAS,
  COR_CONCLUIDO,
  COR_EM_ANDAMENTO,
  corRedec,
  estiloMunicipio,
  faixasConcluidos,
  neutro,
} from '@/Composables/treinamento/camadasMapaIndicadores';

const props = defineProps({
  mapa: { type: Array, default: () => [] },
  territorio: { type: Array, default: () => [] },
  semMunicipio: { type: Object, default: () => ({ participantes: 0, capacitacoes: 0 }) },
  redecs: { type: Array, default: () => [] },
  redecDestacada: { type: Number, default: null },
});

const emit = defineEmits(['selecionar-redec']);

const RAIO_MIN = 4;
const RAIO_MAX = 20;

const { isDarkMode } = useTheme();
const { features, status: statusMalha } = useMalhaMunicipal();

const camada = ref(CAMADAS[0].valor);

const indiceCamada = computed(() => Math.max(0, CAMADAS.findIndex((c) => c.valor === camada.value)));
const camadaAtual = computed(() => CAMADAS[indiceCamada.value]);

// Paginacao circular: da ultima volta para a primeira.
function passarCamada(passo) {
  camada.value = CAMADAS[(indiceCamada.value + passo + CAMADAS.length) % CAMADAS.length].valor;
}

// Indices por IBGE / id: o cruzamento roda para 853 poligonos a cada troca de camada.
const linhaPorIbge = computed(() => new Map(props.mapa.map((linha) => [String(linha.ibge), linha])));
const municipioPorIbge = computed(() => new Map(props.territorio.map((m) => [String(m.ibge), m])));
const nomeRedec = computed(() => new Map(props.redecs.map((redec) => [Number(redec.id), redec.nome])));

const maximoConcluidos = computed(() => Math.max(0, ...props.mapa.map((linha) => Number(linha.concluidos) || 0)));

const redecDoMunicipio = (ibge) => {
  const id = municipioPorIbge.value.get(ibge)?.redec_id;

  return id === null || id === undefined ? null : Number(id);
};

const rotuloRedec = (redecId) => (redecId === null ? 'Sem REDEC' : (nomeRedec.value.get(redecId) ?? `REDEC ${redecId}`));

function montarPopup(ibge, linha) {
  const municipio = municipioPorIbge.value.get(ibge);
  const nome = linha?.nome ?? municipio?.nome ?? ibge;
  const linhas = [{ rotulo: 'REDEC', valor: rotuloRedec(redecDoMunicipio(ibge)) }];

  if (!linha) {
    return { titulo: nome, linhas: [...linhas, { rotulo: 'Participações', valor: 'nenhuma no recorte' }] };
  }

  return {
    titulo: nome,
    linhas: [
      ...linhas,
      { rotulo: 'Concluídos', valor: formatarNumero(linha.concluidos) },
      { rotulo: 'Em andamento', valor: formatarNumero(linha.em_andamento) },
      { rotulo: 'Desistentes', valor: formatarNumero(linha.desistentes) },
      { rotulo: 'Pessoas concluídas', valor: formatarNumero(linha.pessoas_concluidas) },
      { rotulo: 'Pessoas em andamento', valor: formatarNumero(linha.pessoas_em_andamento) },
    ],
  };
}

const poligonos = computed(() => features.value
  .filter((feature) => feature.properties?.codarea)
  .map((feature) => {
    const ibge = String(feature.properties.codarea);
    const linha = linhaPorIbge.value.get(ibge);

    return {
      id: ibge,
      geojson: feature,
      estilo: estiloMunicipio({
        camada: camada.value,
        linha,
        redecId: redecDoMunicipio(ibge),
        destacada: props.redecDestacada,
        escuro: isDarkMode.value,
        maximoConcluidos: maximoConcluidos.value,
      }),
      popup: montarPopup(ibge, linha),
    };
  }));

// Bolhas so na camada de volume: uma por municipio, pessoas que concluiram.
// lat/lng podem vir null (contrato): Number(null) e 0 e passaria no isFinite.
const temCoordenada = (m) => m.lat !== null && m.lat !== undefined && m.lng !== null && m.lng !== undefined
  && Number.isFinite(Number(m.lat)) && Number.isFinite(Number(m.lng));
const comCoordenada = computed(() => props.mapa.filter(temCoordenada));
const maximoPessoas = computed(() => Math.max(1, ...comCoordenada.value.map((m) => Number(m.pessoas_concluidas) || 0)));

// Raiz: a area do circulo cresce com o numero, nao o diametro.
const raioDe = (pessoas) => RAIO_MIN + (RAIO_MAX - RAIO_MIN) * Math.sqrt(pessoas / maximoPessoas.value);

const pontos = computed(() => {
  if (camada.value !== 'volume') {
    return [];
  }

  const destacada = props.redecDestacada;

  return comCoordenada.value
    .filter((m) => Number(m.pessoas_concluidas) > 0)
    .map((m) => ({
      id: `${m.municipio_id}-c`,
      latitude: m.lat,
      longitude: m.lng,
      raio: raioDe(Number(m.pessoas_concluidas)),
      cor: COR_CONCLUIDO,
      opacidade: destacada === null || redecDoMunicipio(String(m.ibge)) === destacada ? 0.8 : 0.2,
      popup: montarPopup(String(m.ibge), m),
    }))
    // A maior entra primeiro para a menor (vizinha) nao ficar escondida.
    .sort((a, b) => b.raio - a.raio);
});

// Ids dos poligonos da REDEC destacada: o MapaLeaflet enquadra neles e volta ao
// enquadramento padrao quando a lista fica nula.
const idsEnquadrados = computed(() => {
  if (props.redecDestacada === null || props.redecDestacada === undefined) {
    return null;
  }

  const ids = props.territorio
    .filter((m) => Number(m.redec_id) === props.redecDestacada)
    .map((m) => String(m.ibge));

  return ids.length > 0 ? ids : null;
});

const legenda = computed(() => {
  const escuro = isDarkMode.value;

  if (camada.value === 'cobertura') {
    return {
      titulo: 'Concluídos',
      itens: [
        ...faixasConcluidos(maximoConcluidos.value).map((faixa) => ({
          cor: faixa.cor,
          rotulo: faixa.de === faixa.ate ? String(faixa.de) : `${faixa.de} a ${faixa.ate}`,
        })),
        { cor: COR_EM_ANDAMENTO, rotulo: 'Só em andamento' },
        { cor: neutro(escuro), rotulo: 'Sem participantes' },
      ],
    };
  }

  if (camada.value === 'volume') {
    // Tres tamanhos de referencia (maximo, metade, um quarto), sem repetir valor.
    const valores = [...new Set([maximoPessoas.value, Math.round(maximoPessoas.value / 2), Math.round(maximoPessoas.value / 4)])]
      .filter((v) => v > 0)
      .sort((a, b) => a - b);

    return {
      titulo: 'Pessoas que concluíram',
      itens: valores.map((v) => ({ cor: COR_CONCLUIDO, rotulo: formatarNumero(v), diametro: Math.round(raioDe(v) * 2) })),
    };
  }

  return {
    titulo: 'REDEC',
    itens: [...props.redecs]
      .sort((a, b) => Number(a.id) - Number(b.id))
      .map((redec) => ({ id: Number(redec.id), cor: corRedec(redec.id, escuro), rotulo: redec.nome })),
  };
});

// Clicar na REDEC ja destacada tira o destaque.
function alternarRedec(redecId) {
  emit('selecionar-redec', redecId === props.redecDestacada ? null : redecId);
}

function aoClicarMunicipio(ibge) {
  if (camada.value !== 'redecs') {
    return;
  }

  const redecId = redecDoMunicipio(String(ibge));

  if (redecId !== null) {
    alternarRedec(redecId);
  }
}
</script>
