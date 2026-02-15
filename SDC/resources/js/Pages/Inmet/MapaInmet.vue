<template>

    <div class="inmet-container dark">
      <!-- Header -->
      <div class="header-section">
        <h1 class="page-title">
          Meteorologia
        </h1>
        <div class="search-bar">
          <input type="text" placeholder="Buscar Cidade..." class="search-input" />
        </div>
      </div>

      <!-- Mapa Container -->
      <div class="map-wrapper">
        <div id="map" ref="mapContainer"></div>

        <!-- Overlay: Estatísticas -->
        <div class="map-overlay stats-overlay">
          <h3 class="overlay-title">Estatísticas</h3>
          <div class="stat-row">
            <span>Média:</span>
            <strong>{{ estatisticas.precipitacao_media?.toFixed(2) || '0.00' }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Máxima:</span>
            <strong>{{ estatisticas.precipitacao_maxima?.toFixed(2) || '0.00' }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Estações:</span>
            <strong>{{ estatisticas.total_estacoes }}</strong>
          </div>
          <div class="stat-note">
            <span class="dot-indicator"></span> Modo: Estações individuais
          </div>
        </div>

        <!-- Overlay: Legenda -->
        <div class="map-overlay legend-overlay">
          <h4 class="legend-title">Níveis de Precipitação (24h)</h4>
          <div class="legend-grid">
            <div class="legend-item"><span class="color-box" style="background:#22c55e"></span> Sem chuva (0mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#3b82f6"></span> Muito fraca (0-5mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#06b6d4"></span> Fraca (5-15mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#eab308"></span> Moderada (15-35mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#f97316"></span> Forte (35-60mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#ef4444"></span> Muito forte (60-100mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#991b1b"></span> Intensa (100-140mm)</div>
            <div class="legend-item"><span class="color-box" style="background:#7f1d1d"></span> Extrema (> 140mm)</div>
          </div>
          <p class="legend-footer">*Adaptado do sistema LHASA_RIO para MG</p>
        </div>
      </div>

      <!-- Tabela de Dados -->
      <div class="table-container">
        <table class="dark-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Estação / Município</th>
              <th>Chuva (mm)</th>
              <th>Nível</th>
              <th>Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="leitura in leituras" :key="leitura.codigo_estacao">
              <td class="code-cell">{{ leitura.codigo_estacao }}<br><span class="sub-text">Automática</span></td>
              <td>
                <div class="station-name">{{ leitura.nome_estacao }}</div>
                <div class="municipio-name">{{ leitura.municipio }}</div>
              </td>
              <td class="value-cell" :style="{ color: getMarkerColor(leitura.nivel_color) }">
                {{ leitura.precipitacao?.toFixed(2) ?? 'N/A' }} mm
              </td>
              <td>
                <span class="status-badge" :style="{ borderColor: getMarkerColor(leitura.nivel_color), color: getMarkerColor(leitura.nivel_color) }">
                  {{ leitura.nivel_label }}
                </span>
              </td>
              <td class="time-cell">{{ leitura.data_hora }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onMounted, ref } from 'vue';

const props = defineProps({
  leituras: Array,
  estatisticas: Object,
  uf_selecionada: String,
});

const mapContainer = ref(null);
let map = null;

onMounted(() => {
  nextTick(() => {
    if (props.leituras) {
      initMap();
    }
  });
});

function initMap() {
  map = L.map('map', {
    zoomControl: false,
    attributionControl: false
  }).setView([-18.5122, -44.555], 6);

  L.control.zoom({ position: 'topleft' }).addTo(map);

  // Tile Layer (Usando CartoDB Voyager para um look mais limpo, ou OpenStreetMap padrão)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);

  props.leituras.forEach(leitura => {
    if (leitura.latitude && leitura.longitude) {
      const color = getMarkerColor(leitura.nivel_color);
      
      const marker = L.circleMarker([leitura.latitude, leitura.longitude], {
        radius: 6,
        fillColor: color,
        color: '#1a1d21', // Borda escura para contraste
        weight: 1,
        opacity: 1,
        fillOpacity: 1
      }).addTo(map);

      marker.bindPopup(`
        <div style="min-width: 200px; color: #1a1d21;">
          <h4 style="margin: 0; font-size: 16px; font-weight: bold;">${leitura.nome_estacao}</h4>
          <p style="margin: 4px 0; color: #6b7280; font-size: 12px;">${leitura.municipio}</p>
          <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 18px; font-weight: bold; color: ${color};">${leitura.precipitacao?.toFixed(2) ?? '0.00'} mm</span>
            <span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px;">${leitura.nivel_label}</span>
          </div>
          <p style="margin: 8px 0 0 0; font-size: 11px; color: #9ca3af; text-align: right;">${leitura.data_hora}</p>
        </div>
      `);
    }
  });
}

function getMarkerColor(nivel) {
  const colors = {
    'green': '#22c55e',
    'blue': '#3b82f6',
    'cyan': '#06b6d4', // Fraca
    'yellow': '#eab308',
    'orange': '#f97316',
    'red': '#ef4444',
    'darkred': '#991b1b',
    'purple': '#7f1d1d' // Extrema
  };
  return colors[nivel] || '#6b7280';
}
</script>

<style scoped>
/* Dark Mode Base */
.inmet-container {
  background-color: #111315; /* Cor de fundo bem escura */
  min-height: calc(100vh - 64px); /* Account for topbar height */
  color: #e5e7eb;
  font-family: 'Inter', sans-serif;
  padding: 20px;
  width: 100%; /* Ensure it doesn't overflow */
  max-width: 100%; /* Prevent any overflow */
  box-sizing: border-box; /* Include padding in width calculation */
  margin: 0; /* Remove any default margins */
  position: relative; /* Establish positioning context */
}

/* Header */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #e5e7eb;
}

.search-input {
  background: #1a1d21;
  border: 1px solid #374151;
  color: #e5e7eb;
  padding: 8px 16px;
  border-radius: 6px;
  width: 300px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Map Wrapper */
.map-wrapper {
  position: relative;
  height: 600px;
  width: 100%; /* Ensure it doesn't overflow container */
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #374151;
  margin-bottom: 24px;
  box-sizing: border-box; /* Include border in width calculation */
}

#map {
  height: 100%;
  width: 100%;
  background: #1a1d21; /* Fallback */
}

/* Overlays */
.map-overlay {
  position: absolute;
  background: rgba(26, 29, 33, 0.95);
  border: 1px solid #374151;
  border-radius: 8px;
  padding: 16px;
  z-index: 1000;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.stats-overlay {
  top: 20px;
  right: 20px;
  width: 280px;
}

.legend-overlay {
  bottom: 20px;
  left: 20px;
  width: 300px;
}

.overlay-title, .legend-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: #e5e7eb;
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: #9ca3af;
}

.stat-row strong {
  color: #e5e7eb;
}

.stat-note {
  margin-top: 12px;
  font-size: 11px;
  color: #ef4444;
  display: flex;
  align-items: center;
  gap: 6px;
}

.dot-indicator {
  width: 6px;
  height: 6px;
  background: #ef4444;
  border-radius: 50%;
}

.legend-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: #9ca3af;
}

.color-box {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.legend-footer {
  margin-top: 12px;
  font-size: 10px;
  color: #6b7280;
  font-style: italic;
}

/* Table */
.table-container {
  background: #1a1d21;
  border-radius: 8px;
  border: 1px solid #374151;
  overflow: hidden;
  width: 100%; /* Ensure it doesn't overflow container */
  box-sizing: border-box; /* Include border in width calculation */
  overflow-x: auto; /* Allow horizontal scroll if needed */
}

.dark-table {
  width: 100%;
  border-collapse: collapse;
}

.dark-table th {
  background: #25292f;
  color: #9ca3af;
  font-weight: 500;
  font-size: 12px;
  text-align: left;
  padding: 12px 16px;
  text-transform: uppercase;
  border-bottom: 1px solid #374151;
}

.dark-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #2d333b;
  color: #e5e7eb;
  font-size: 13px;
}

.dark-table tbody tr:last-child td {
  border-bottom: none;
}

.dark-table tbody tr:hover {
  background: #25292f;
}

.code-cell {
  font-weight: 600;
  color: #e5e7eb;
}

.sub-text {
  font-size: 10px;
  color: #6b7280;
  font-weight: normal;
}

.station-name {
  font-weight: 500;
}

.municipio-name {
  font-size: 11px;
  color: #9ca3af;
  text-transform: uppercase;
}

.value-cell {
  font-weight: 700;
}

.status-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  border: 1px solid currentColor;
  text-transform: uppercase;
}

.time-cell {
  color: #6b7280;
  font-size: 12px;
}

/* Base Responsive Container */
.inmet-container {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
  box-sizing: border-box;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .inmet-container {
    padding: 16px 12px;
    height: auto;
    min-height: calc(100vh - 64px);
    padding-bottom: 80px; /* Space for bottom navigation if exists */
  }

  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .search-input {
    width: 100%;
    max-width: 100%;
  }

  .map-wrapper {
    height: 450px; /* Slightly shorter on mobile */
    margin: 0 -12px 24px -12px; /* Full bleed on very small screens */
    width: calc(100% + 24px);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }

  /* Reposition Overlays for Mobile */
  .stats-overlay {
    top: auto;
    bottom: 10px;
    right: 10px;
    left: 10px;
    width: auto;
    transform: none;
    background: rgba(26, 29, 33, 0.98);
    /* Make it collapsible or compact */
    padding: 12px;
  }
  
  .stats-overlay .stat-row {
     display: inline-block;
     margin-right: 12px;
     margin-bottom: 4px;
  }

  .legend-overlay {
    position: static; /* Move out of map flow */
    width: 100%;
    margin-bottom: 20px;
    background: #1a1d21;
    border: 1px solid #374151;
  }

  /* Table adjustments */
  .table-container {
    margin: 0 -12px;
    width: calc(100% + 24px);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }
  
  .dark-table th, .dark-table td {
    padding: 8px 10px;
    font-size: 12px;
  }
  
  .station-name {
    font-size: 13px;
  }
}

</style>
