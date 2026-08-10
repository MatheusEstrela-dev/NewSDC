<template>
  <div class="sismos-container dark">
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
      <div id="map-sismos" ref="mapContainer"></div>

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
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
  eventos: { type: Array, default: () => [] },
  estatisticas: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

// Mesmas faixas da matview gold.sismos_mapa. Se mudarem la, mudam aqui.
const CORES = {
  micro: '#94a3b8',
  leve: '#22c55e',
  moderado: '#f59e0b',
  forte: '#ef4444',
  desconhecido: '#64748b',
};

const legenda = [
  { classe: 'micro', cor: CORES.micro, rotulo: 'Micro (< 2,0)' },
  { classe: 'leve', cor: CORES.leve, rotulo: 'Leve (2,0 a 3,9)' },
  { classe: 'moderado', cor: CORES.moderado, rotulo: 'Moderado (4,0 a 4,9)' },
  { classe: 'forte', cor: CORES.forte, rotulo: 'Forte (>= 5,0)' },
  { classe: 'desconhecido', cor: CORES.desconhecido, rotulo: 'Sem magnitude' },
];

const mapContainer = ref(null);
let map = null;

onMounted(() => {
  nextTick(() => initMap());
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

function initMap() {
  map = L.map('map-sismos', {
    zoomControl: false,
    attributionControl: false,
  });

  // Enquadra o quadrante de MG que o backend usa para coletar, em vez de um
  // centro fixo: mapa e coleta ficam coerentes.
  map.fitBounds([
    [props.bbox.min_lat, props.bbox.min_lon],
    [props.bbox.max_lat, props.bbox.max_lon],
  ]);

  L.control.zoom({ position: 'topleft' }).addTo(map);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);

  props.eventos.forEach((evento) => {
    const lat = Number(evento.latitude);
    const lon = Number(evento.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      return;
    }

    const cor = CORES[evento.classe_magnitude] ?? CORES.desconhecido;

    L.circleMarker([lat, lon], {
      radius: raioPorMagnitude(evento.magnitude),
      fillColor: cor,
      color: '#1a1d21',
      weight: 1,
      opacity: 1,
      fillOpacity: 0.85,
    })
      .bindPopup(montarPopup(evento))
      .addTo(map);
  });
}

// Raio proporcional a magnitude, como o CircleMarker do folium nos notebooks.
function raioPorMagnitude(magnitude) {
  const valor = Number(magnitude);

  return Number.isFinite(valor) ? Math.max(4, valor * 2.5) : 4;
}

function montarPopup(evento) {
  const linhas = [
    `<strong>${escapar(evento.regiao ?? 'Regiao nao informada')}</strong>`,
    `Magnitude: ${formatarMagnitude(evento.magnitude)} ${escapar(evento.escala_magnitude ?? '')}`,
    `Profundidade: ${evento.profundidade_km ?? '-'} km`,
    `Origem (UTC): ${formatarDataHora(evento.origem_utc)}`,
    `Fonte: ${escapar(evento.fonte)}`,
    `ID: ${escapar(evento.evento_id)}`,
  ];

  return linhas.join('<br>');
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

// O popup monta HTML, e regiao/autor vem de fonte externa: escapar evita que
// conteudo do catalogo seja interpretado como marcacao.
function escapar(texto) {
  const div = document.createElement('div');
  div.textContent = String(texto ?? '');

  return div.innerHTML;
}
</script>

<style scoped>
.sismos-container {
  padding: 1.5rem;
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

.map-wrapper {
  position: relative;
  border-radius: 0.5rem;
  overflow: hidden;
}

#map-sismos {
  height: 600px;
  width: 100%;
}

.map-overlay {
  position: absolute;
  z-index: 500;
  background: rgba(26, 29, 33, 0.9);
  color: #f8fafc;
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
</style>
