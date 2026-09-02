<template>
  <div class="sismos-container">
    <div class="header-section">
      <h1 class="page-title">Sismos</h1>
      <p class="page-subtitle">
        Eventos sismicos no quadrante de Minas Gerais
        <span v-if="estatisticas.ultima_atualizacao" class="update-note">
          &middot; atualizado em {{ formatarDataHora(estatisticas.ultima_atualizacao) }}
        </span>
      </p>
    </div>

    <div class="map-wrapper">
      <MapaLeaflet :pontos="pontosDoMapa" :bbox="bbox" class="mapa-area" />

      <div class="map-overlay stats-overlay">
        <h3 class="overlay-title">Estatisticas</h3>
        <div class="stat-row">
          <span>Eventos:</span>
          <strong>{{ estatisticas.total_eventos }}</strong>
        </div>
        <div class="stat-row">
          <span>Magnitude media:</span>
          <strong>{{ formatarMagnitude(estatisticas.magnitude_media) }}</strong>
        </div>
        <div class="stat-row">
          <span>Magnitude maxima:</span>
          <strong>{{ formatarMagnitude(estatisticas.magnitude_maxima) }}</strong>
        </div>
        <div v-if="eventos.length === 0" class="stat-note">
          <span class="dot-indicator"></span> Nenhum evento na janela atual
        </div>
      </div>

      <div class="map-overlay legend-overlay">
        <h4 class="legend-title">Magnitude</h4>
        <div v-for="item in legenda" :key="item.classe" class="legend-row">
          <span class="legend-dot" :style="{ backgroundColor: item.cor }"></span>
          <span>{{ item.rotulo }}</span>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="dados-table">
        <thead>
          <tr>
            <th class="hidden md:table-cell">Evento</th>
            <th>Regiao</th>
            <th>Magnitude</th>
            <th>Classe</th>
            <th class="hidden sm:table-cell">Profundidade</th>
            <th class="hidden sm:table-cell">Origem (UTC)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="evento in eventosDaPagina" :key="evento.evento_id">
            <td class="code-cell hidden md:table-cell">
              {{ evento.evento_id }}<br><span class="sub-text">{{ evento.fonte }}</span>
            </td>
            <td>
              <div class="station-name">{{ evento.regiao ?? 'Regiao nao informada' }}</div>
              <div class="municipio-name md:hidden">{{ evento.evento_id }}</div>
            </td>
            <td class="value-cell" :style="{ color: corDaClasse(evento.classe_magnitude) }">
              {{ formatarMagnitude(evento.magnitude) }}
              <span class="sub-text">{{ evento.escala_magnitude ?? '' }}</span>
            </td>
            <td>
              <span
                class="status-badge"
                :style="{ borderColor: corDaClasse(evento.classe_magnitude), color: corDaClasse(evento.classe_magnitude) }"
              >
                {{ rotuloDaClasse(evento.classe_magnitude) }}
              </span>
            </td>
            <td class="hidden sm:table-cell">{{ evento.profundidade_km ?? '-' }} km</td>
            <td class="time-cell hidden sm:table-cell">{{ formatarDataHora(evento.origem_utc) }}</td>
          </tr>
          <tr v-if="eventos.length === 0">
            <td colspan="6" class="empty-cell">Nenhum evento na janela atual</td>
          </tr>
        </tbody>
      </table>

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';
import { useTheme } from '@/Composables/ui/useTheme';
import { computed, ref } from 'vue';

const props = defineProps({
  eventos: { type: Array, default: () => [] },
  estatisticas: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

// A coleta roda a cada 15 minutos, mas o dedup por hash so deixa passar quando
// ha evento novo -- entao esta pagina raramente vai piscar, e quando piscar sera
// porque algo aconteceu de verdade.
useAtualizacaoAoVivo({
  canal: 'medalhao.sismos',
  evento: '.GoldAtualizado',
  props: ['eventos', 'estatisticas'],
});

/*
 * Mesmas faixas da matview gold.sismos_mapa. Se mudarem la, mudam aqui.
 *
 * Duas paletas porque a saturacao que funciona sobre fundo escuro agride sobre
 * branco: os tons vivos foram escolhidos para o tema escuro. No claro entram as
 * variantes 600/700 do Tailwind, que tem contraste suficiente sem estourar.
 */
const CORES_ESCURO = {
  micro: '#94a3b8',
  leve: '#22c55e',
  moderado: '#f59e0b',
  forte: '#ef4444',
  desconhecido: '#64748b',
};

const CORES_CLARO = {
  micro: '#64748b',
  leve: '#15803d',
  moderado: '#b45309',
  forte: '#b91c1c',
  desconhecido: '#475569',
};

const { isDarkMode } = useTheme();

const CORES = computed(() => (isDarkMode.value ? CORES_ESCURO : CORES_CLARO));

const FAIXAS = [
  { classe: 'micro', rotulo: 'Micro (< 2,0)' },
  { classe: 'leve', rotulo: 'Leve (2,0 a 3,9)' },
  { classe: 'moderado', rotulo: 'Moderado (4,0 a 4,9)' },
  { classe: 'forte', rotulo: 'Forte (>= 5,0)' },
  { classe: 'desconhecido', rotulo: 'Sem magnitude' },
];

// Computed porque a cor depende do tema: trocar claro/escuro repinta a legenda.
const legenda = computed(() => FAIXAS.map((faixa) => ({
  ...faixa,
  cor: CORES.value[faixa.classe],
})));

// A pagina traduz evento -> ponto; toda a mecanica de Leaflet vive no
// componente. O popup vai estruturado: quem escapa e o componente, porque
// regiao e autor vem do catalogo externo.
const pontosDoMapa = computed(() => props.eventos.map((evento) => ({
  id: evento.id,
  latitude: evento.latitude,
  longitude: evento.longitude,
  cor: CORES.value[evento.classe_magnitude] ?? CORES.value.desconhecido,
  raio: raioPorMagnitude(evento.magnitude),
  popup: {
    titulo: evento.regiao ?? 'Regiao nao informada',
    linhas: [
      { rotulo: 'Magnitude', valor: `${formatarMagnitude(evento.magnitude)} ${evento.escala_magnitude ?? ''}`.trim() },
      { rotulo: 'Profundidade', valor: `${evento.profundidade_km ?? '-'} km` },
      { rotulo: 'Origem (UTC)', valor: formatarDataHora(evento.origem_utc) },
      { rotulo: 'Fonte', valor: evento.fonte },
      { rotulo: 'ID', valor: evento.evento_id },
    ],
  },
})));

const ROTULOS = {
  micro: 'Micro',
  leve: 'Leve',
  moderado: 'Moderado',
  forte: 'Forte',
  desconhecido: 'Sem magnitude',
};

function corDaClasse(classe) {
  return CORES.value[classe] ?? CORES.value.desconhecido;
}

function rotuloDaClasse(classe) {
  return ROTULOS[classe] ?? ROTULOS.desconhecido;
}

/*
 * Paginacao no cliente, nao no servidor: o mapa precisa de TODOS os eventos de
 * qualquer forma, entao paginar no backend exigiria uma segunda consulta para
 * ganhar nada. O componente Pagination so precisa do formato do objeto.
 */
const POR_PAGINA = 10;
const pagina = ref(1);

const paginacao = computed(() => ({
  current_page: pagina.value,
  per_page: POR_PAGINA,
  total: props.eventos.length,
  last_page: Math.max(1, Math.ceil(props.eventos.length / POR_PAGINA)),
}));

const eventosDaPagina = computed(() => {
  const inicio = (pagina.value - 1) * POR_PAGINA;

  return props.eventos.slice(inicio, inicio + POR_PAGINA);
});

function irParaPagina(numero) {
  pagina.value = Math.min(Math.max(1, numero), paginacao.value.last_page);
}

// Raio proporcional a magnitude, como o CircleMarker do folium nos notebooks.
function raioPorMagnitude(magnitude) {
  const valor = Number(magnitude);

  return Number.isFinite(valor) ? Math.max(4, valor * 2.5) : 4;
}

function formatarMagnitude(valor) {
  const numero = Number(valor);

  return Number.isFinite(numero) && numero !== 0 ? numero.toFixed(1) : '-';
}

function formatarDataHora(valor) {
  if (!valor) {
    return '-';
  }

  const data = new Date(valor);

  return Number.isNaN(data.getTime()) ? String(valor) : data.toLocaleString('pt-BR');
}

// O escapar() saiu daqui: quem monta o HTML do popup agora e o MapaLeaflet, e a
// protecao vive num lugar so, valendo tambem para a pagina do Inmet.
</script>

<style scoped>
/*
 * Um token por papel: as regras abaixo nunca repetem cor por tema. As variantes
 * escuras vivem no <style> NAO-scoped no fim do arquivo, porque dependem da
 * classe `dark` que o useTheme poe no <html>, fora deste componente.
 */
.sismos-container {
  --sup: #ffffff;
  --sup-2: #f1f5f9;
  --borda: #e2e8f0;
  --texto: #1e293b;
  --texto-fraco: #64748b;
  --overlay: rgba(255, 255, 255, 0.94);
  --mapa-fallback: #e2e8f0;

  padding: 1.5rem;
  color: var(--texto);
}

.header-section {
  margin-bottom: 1rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
}

.page-subtitle {
  font-size: 0.875rem;
  opacity: 0.7;
}

.update-note {
  opacity: 0.6;
}

/*
 * Mesma correcao do MapaInmet: o wrapper isola o contexto de empilhamento.
 *
 * O Leaflet posiciona os proprios paines em z-index 200-700 e os controles de
 * zoom em 800-1000, e os overlays deste arquivo usam 500. Sem contexto proprio,
 * tudo isso competia no contexto RAIZ -- e a sidebar do SDC e z-index 50. No
 * telefone, abrir o menu com o mapa na tela deixava o zoom e o painel de
 * estatisticas flutuando por cima do drawer.
 *
 * `isolation: isolate` cria o contexto sem depender de position/z-index; o
 * `z-index: 0` cobre navegador que ignore `isolation`. Dentro dele o 500 do
 * overlay continua valendo sobre os paines do mapa; fora, o wrapper todo vale 0.
 *
 * A altura sai do #map e vem para o wrapper: e o wrapper que a media query de
 * mobile precisa encolher, e o mapa passa a preencher 100% dele.
 */
.map-wrapper {
  position: relative;
  isolation: isolate;
  z-index: 0;
  height: 600px;
  width: 100%;
  border-radius: 0.5rem;
  overflow: hidden;
  box-sizing: border-box;
}

/*
 * O seletor mudou de #map-sismos para .mapa-area porque o mapa agora e o
 * componente MapaLeaflet, que gera id proprio por instancia. A altura de 100%
 * e a do wrapper: e o .map-wrapper que carrega os 600px e que a media query
 * encolhe no telefone.
 */
.mapa-area {
  height: 100%;
  width: 100%;
  background: var(--mapa-fallback); /* fallback enquanto os tiles nao chegam */
}


.map-overlay {
  position: absolute;
  z-index: 500;
  background: var(--overlay);
  color: var(--texto);
  border: 1px solid var(--borda);
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  min-width: 190px;
}

.stats-overlay {
  top: 1rem;
  right: 1rem;
}

.legend-overlay {
  bottom: 1rem;
  right: 1rem;
}

.overlay-title,
.legend-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.stat-row,
.legend-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.125rem 0;
}

.legend-row {
  justify-content: flex-start;
}

.legend-dot,
.dot-indicator {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.dot-indicator {
  background: #94a3b8;
}

.stat-note {
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  opacity: 0.7;
}

/*
 * ESTE BLOCO FICA NO FIM DO ARQUIVO DE PROPOSITO.
 *
 * Media query NAO soma especificidade: `.legend-overlay` aqui dentro vale
 * 0,1,0, igual ao `.map-overlay { position: absolute }` das regras base. Com
 * o bloco no meio do arquivo, a regra base vinha DEPOIS e vencia -- a legenda
 * seguia sobreposta ao mapa no telefone, cobrindo os pontos que explica, sem
 * nenhum sintoma no CSS.
 */

/*
 * Tabela de eventos. As regras BASE sao o tema claro; as variantes escuras
 * ficam no <style> nao-scoped no fim do arquivo, trocando so os tokens.
 */
.table-container {
  margin-top: 1rem;
  background: var(--sup);
  border: 1px solid var(--borda);
  border-radius: 8px;
  overflow: hidden;
  overflow-x: auto;
  width: 100%;
  box-sizing: border-box;
}

.dados-table {
  width: 100%;
  border-collapse: collapse;
}

.dados-table th {
  background: var(--sup-2);
  color: var(--texto-fraco);
  font-weight: 500;
  font-size: 12px;
  text-align: left;
  padding: 12px 16px;
  text-transform: uppercase;
  border-bottom: 1px solid var(--borda);
  white-space: nowrap;
}

.dados-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--borda);
  color: var(--texto);
  font-size: 13px;
}

.dados-table tbody tr:last-child td {
  border-bottom: none;
}

.dados-table tbody tr:hover {
  background: var(--sup-2);
}

.code-cell {
  font-weight: 600;
}

.sub-text {
  font-size: 10px;
  color: var(--texto-fraco);
  font-weight: normal;
}

.station-name {
  font-weight: 500;
}

.municipio-name {
  font-size: 11px;
  color: var(--texto-fraco);
  text-transform: uppercase;
}

.value-cell {
  font-weight: 600;
  white-space: nowrap;
}

.status-badge {
  border: 1px solid currentColor;
  border-radius: 4px;
  padding: 2px 8px;
  font-size: 11px;
  white-space: nowrap;
}

.time-cell,
.empty-cell {
  color: var(--texto-fraco);
  white-space: nowrap;
}

.empty-cell {
  text-align: center;
  padding: 1.5rem;
}

@media (max-width: 767px) {
  .map-wrapper {
    height: 60vh;
    min-height: 320px;
  }

  .map-overlay {
    padding: 0.625rem 0.75rem;
    font-size: 0.8125rem;
    min-width: 0;
  }

  .stats-overlay {
    top: 0.5rem;
    right: 0.5rem;
    left: auto;
    max-width: 60%;
  }

  /* A legenda desce para baixo do mapa em vez de cobri-lo. */
  .legend-overlay {
    position: static;
    width: 100%;
    margin-top: 0.75rem;
  }
}
</style>

<!--
  Bloco NAO-scoped de proposito, mesma razao do MapaInmet: a variante escura
  depende da classe `dark` no <html>, que e ancestral fora deste componente.
  `:global(.dark) .x` dentro do <style scoped> NAO funciona aqui -- o compilador
  descarta tudo depois do :global() e emite apenas `.dark`, o que aplicava estas
  cores ao proprio <html> em vez da pagina.
-->
<style>
.dark .sismos-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;
  --overlay: rgba(26, 29, 33, 0.9);
  --mapa-fallback: #1a1d21;
}
</style>
