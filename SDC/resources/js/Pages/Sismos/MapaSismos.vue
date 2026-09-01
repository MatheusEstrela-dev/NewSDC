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
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import { computed } from 'vue';

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

// A pagina traduz evento -> ponto; toda a mecanica de Leaflet vive no
// componente. O popup vai estruturado: quem escapa e o componente, porque
// regiao e autor vem do catalogo externo.
const pontosDoMapa = computed(() => props.eventos.map((evento) => ({
  id: evento.id,
  latitude: evento.latitude,
  longitude: evento.longitude,
  cor: CORES[evento.classe_magnitude] ?? CORES.desconhecido,
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

.mapa-area {
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
