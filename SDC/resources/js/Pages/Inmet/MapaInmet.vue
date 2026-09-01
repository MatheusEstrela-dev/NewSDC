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
        <MapaLeaflet :pontos="pontosDoMapa" :bbox="bbox" class="mapa-area" />

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
            <div v-for="item in legenda" :key="item.classe" class="legend-item">
              <span class="color-box" :style="{ background: item.cor }"></span> {{ item.rotulo }}
            </div>
          </div>
          <p class="legend-footer">*Adaptado do sistema LHASA_RIO para MG</p>
        </div>
      </div>

      <!-- Tabela de Dados -->
      <div class="table-container">
        <table class="dark-table">
          <thead>
            <tr>
              <th class="hidden md:table-cell">Código</th>
              <th>Estação / Município</th>
              <th>Chuva (mm)</th>
              <th>Nível</th>
              <th class="hidden sm:table-cell">Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="estacao in estacoes" :key="estacao.codigo_estacao">
              <td class="code-cell hidden md:table-cell">{{ estacao.codigo_estacao }}<br><span class="sub-text">Automática</span></td>
              <td>
                <div class="station-name">{{ estacao.nome_estacao }}</div>
                <div class="municipio-name">{{ estacao.municipio }}</div>
              </td>
              <td class="value-cell" :style="{ color: corDaClasse(estacao.classe_precipitacao) }">
                {{ formatarMm(estacao.precipitacao) }}
              </td>
              <td>
                <span class="status-badge" :style="{ borderColor: corDaClasse(estacao.classe_precipitacao), color: corDaClasse(estacao.classe_precipitacao) }">
                  {{ rotuloDaClasse(estacao.classe_precipitacao) }}
                </span>
              </td>
              <td class="time-cell hidden sm:table-cell">{{ formatarDataHora(estacao.medido_em) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';
import { computed } from 'vue';

const props = defineProps({
  estacoes: { type: Array, default: () => [] },
  estatisticas: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

// Mesmas classes da matview gold.inmet_mapa, faixas do LHASA_RIO adaptadas para
// MG. Se mudarem la, mudam aqui.
const CORES = {
  sem_chuva: '#22c55e',
  muito_fraca: '#3b82f6',
  fraca: '#06b6d4',
  moderada: '#eab308',
  forte: '#f97316',
  muito_forte: '#ef4444',
  intensa: '#991b1b',
  extrema: '#7f1d1d',
  desconhecido: '#6b7280',
};

const ROTULOS = {
  sem_chuva: 'Sem chuva',
  muito_fraca: 'Muito fraca',
  fraca: 'Fraca',
  moderada: 'Moderada',
  forte: 'Forte',
  muito_forte: 'Muito forte',
  intensa: 'Intensa',
  extrema: 'Extrema',
  desconhecido: 'Sem leitura',
};

// A legenda deriva das mesmas faixas, em vez de repetir as cores em markup —
// era o que permitia a legenda divergir da classificacao sem ninguem notar.
const legenda = [
  { classe: 'sem_chuva', cor: CORES.sem_chuva, rotulo: 'Sem chuva (0 mm)' },
  { classe: 'muito_fraca', cor: CORES.muito_fraca, rotulo: 'Muito fraca (0-5 mm)' },
  { classe: 'fraca', cor: CORES.fraca, rotulo: 'Fraca (5-15 mm)' },
  { classe: 'moderada', cor: CORES.moderada, rotulo: 'Moderada (15-35 mm)' },
  { classe: 'forte', cor: CORES.forte, rotulo: 'Forte (35-60 mm)' },
  { classe: 'muito_forte', cor: CORES.muito_forte, rotulo: 'Muito forte (60-100 mm)' },
  { classe: 'intensa', cor: CORES.intensa, rotulo: 'Intensa (100-140 mm)' },
  { classe: 'extrema', cor: CORES.extrema, rotulo: 'Extrema (> 140 mm)' },
];

function corDaClasse(classe) {
  return CORES[classe] ?? CORES.desconhecido;
}

function rotuloDaClasse(classe) {
  return ROTULOS[classe] ?? ROTULOS.desconhecido;
}

function formatarMm(valor) {
  const numero = Number(valor);

  return Number.isFinite(numero) ? `${numero.toFixed(2)} mm` : 'N/A';
}

function formatarDataHora(valor) {
  if (!valor) {
    return '-';
  }

  const data = new Date(valor);

  return Number.isNaN(data.getTime()) ? String(valor) : data.toLocaleString('pt-BR');
}

// A pagina traduz estacao -> ponto; a mecanica de Leaflet vive no componente.
// O popup vai estruturado: quem escapa e o componente, o que importa porque
// nome de estacao e municipio vem da API do INMET.
const pontosDoMapa = computed(() => props.estacoes.map((estacao) => ({
  id: estacao.id,
  latitude: estacao.latitude,
  longitude: estacao.longitude,
  cor: corDaClasse(estacao.classe_precipitacao),
  raio: 6,
  popup: {
    titulo: estacao.nome_estacao,
    linhas: [
      { rotulo: 'Municipio', valor: estacao.municipio },
      { rotulo: 'Chuva', valor: formatarMm(estacao.precipitacao) },
      { rotulo: 'Nivel', valor: rotuloDaClasse(estacao.classe_precipitacao) },
      { rotulo: 'Temperatura', valor: estacao.temperatura !== null ? `${estacao.temperatura} C` : '-' },
      { rotulo: 'Medido em', valor: formatarDataHora(estacao.medido_em) },
    ],
  },
})));
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
/*
 * `isolation: isolate` conserta o mapa aparecendo SOBRE a sidebar.
 *
 * O Leaflet posiciona os proprios paines em z-index 200-700 e os controles
 * (o +/- de zoom) em 800-1000, e os overlays deste arquivo usam 1000. Sem um
 * contexto de empilhamento aqui, todos esses valores competiam no contexto
 * RAIZ -- e a sidebar e z-index 50. Resultado: no telefone, abrir o menu com o
 * mapa na tela deixava zoom e "Estatisticas" flutuando por cima do drawer.
 *
 * `isolation: isolate` cria o contexto sem depender de position/z-index, e o
 * `z-index: 0` garante o mesmo em navegador que ignore `isolation`. Dentro
 * dele o 1000 do overlay continua valendo sobre os paines do mapa; fora, o
 * wrapper inteiro vale 0 e fica abaixo de qualquer chrome do app.
 *
 * Regra geral: TODO container de mapa, modal ou dropdown de biblioteca externa
 * precisa isolar -- a biblioteca nao conhece a escala de z-index do SDC.
 */
.map-wrapper {
  position: relative;
  isolation: isolate;
  z-index: 0;
  height: 600px;
  width: 100%; /* Ensure it doesn't overflow container */
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #374151;
  margin-bottom: 24px;
  box-sizing: border-box; /* Include border in width calculation */
}

/*
 * Telas pequenas: o mapa de 600px fixos ocupava a tela inteira e os overlays de
 * 280-300px cobriam metade dele. Altura por viewport e overlays em largura
 * relativa devolvem o mapa ao usuario.
 */
@media (max-width: 767px) {
  .map-wrapper {
    height: 60vh;
    min-height: 320px;
    margin-bottom: 16px;
  }

  .map-overlay {
    padding: 10px 12px;
    font-size: 0.8125rem;
  }

  .stats-overlay {
    top: 8px;
    right: 8px;
    left: auto;
    width: auto;
    max-width: 60%;
  }

  /* A legenda vai para baixo do mapa: sobreposta, ela cobria o proprio dado
     que explica. */
  .legend-overlay {
    position: static;
    width: 100%;
    margin-top: 12px;
  }
}

.mapa-area {
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
    padding: 12px 8px;
    height: auto;
    min-height: calc(100dvh - 3rem - env(safe-area-inset-top, 0px));
    padding-bottom: 24px;
  }

  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .search-input {
    width: 100%;
    max-width: 100%;
    font-size: 16px; /* Prevent iOS auto-zoom on focus */
  }

  .map-wrapper {
    height: 55dvh; /* Dynamic height based on viewport */
    max-height: 450px;
    margin: 0 -8px 16px -8px;
    width: calc(100% + 16px);
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
