<template>

    <div class="cemaden-container">
      <!-- Header -->
      <div class="header-section">
        <h1 class="page-title">
          Chuva em tempo quase real
        </h1>
        <div class="search-bar">
          <input type="text" placeholder="Buscar Cidade..." class="search-input" />
        </div>
      </div>

      <!-- Mapa Container -->
      <div class="map-wrapper">
        <MapaLeaflet :pontos="pontosDoMapa" :bbox="bbox" class="mapa-area" />

        <!-- Overlay: Estatisticas -->
        <div class="map-overlay stats-overlay">
          <h3 class="overlay-title">Estatisticas</h3>
          <div class="stat-row">
            <span>Media:</span>
            <strong>{{ estatisticas.precipitacao_media?.toFixed(2) || '0.00' }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Maxima:</span>
            <strong>{{ estatisticas.precipitacao_maxima?.toFixed(2) || '0.00' }} mm</strong>
          </div>
          <div class="stat-row">
            <span>Com chuva:</span>
            <strong>{{ estatisticas.estacoes_com_chuva }}</strong>
          </div>
          <div class="stat-row">
            <span>Estacoes:</span>
            <strong>{{ estatisticas.total_estacoes }}</strong>
          </div>
          <!--
            Sem este numero, "830 estacoes" com a maioria em cinza pareceria
            perda de dado. Estacao sem telemetria nao e estacao sem chuva.
          -->
          <div class="stat-row">
            <span>Sem telemetria:</span>
            <strong>{{ estatisticas.estacoes_sem_telemetria }}</strong>
          </div>
          <div class="stat-note">
            <span class="dot-indicator"></span> Atualizado: {{ formatarDataHora(estatisticas.ultima_atualizacao) }}
          </div>
        </div>

        <!-- Overlay: Legenda -->
        <div class="map-overlay legend-overlay">
          <h4 class="legend-title">Chuva acumulada (24h)</h4>
          <div class="legend-grid">
            <div v-for="item in legenda" :key="item.classe" class="legend-item">
              <span class="color-box" :style="{ background: item.cor }"></span> {{ item.rotulo }}
            </div>
          </div>
          <p class="legend-footer">*Rede CEMADEN, adaptado do sistema LHASA_RIO para MG</p>
        </div>
      </div>

      <!-- Tabela de Dados -->
      <div class="table-container">
        <table class="dados-table">
          <thead>
            <tr>
              <th class="hidden md:table-cell">Codigo</th>
              <th>Estacao / Municipio</th>
              <th>Chuva 24h (mm)</th>
              <th>Nivel</th>
              <th class="hidden sm:table-cell">Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="estacao in estacoesDaPagina" :key="estacao.codigo_estacao">
              <td class="code-cell hidden md:table-cell">{{ estacao.codigo_estacao }}<br><span class="sub-text">{{ estacao.tipo }}</span></td>
              <td>
                <div class="station-name">{{ estacao.nome_estacao }}</div>
                <div class="municipio-name">{{ estacao.municipio }}</div>
              </td>
              <td class="value-cell" :style="{ color: corDaClasse(estacao.classe_precipitacao) }">
                {{ formatarMm(estacao.acumulado_24h) }}
              </td>
              <td>
                <span class="status-badge" :style="{ borderColor: corDaClasse(estacao.classe_precipitacao), color: corDaClasse(estacao.classe_precipitacao) }">
                  {{ rotuloDaClasse(estacao.classe_precipitacao) }}
                </span>
              </td>
              <td class="time-cell hidden sm:table-cell">{{ formatarDataHora(estacao.medido_em) }}</td>
            </tr>
            <tr v-if="estacoes.length === 0">
              <td colspan="5" class="empty-cell">Nenhuma estacao com leitura</td>
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
import { usePrecipitacao } from '@/Composables/usePrecipitacao';
import { computed, ref } from 'vue';

const props = defineProps({
  estacoes: { type: Array, default: () => [] },
  estatisticas: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

/*
 * O feed do CEMADEN avanca a cada ~10 minutos, e foi medido avancando em 2
 * minutos (16:58:37 -> 17:00:44 em 2026-09-02). E o oposto do INMET, cujo
 * HR_MEDICAO e horario e so muda em 16:00, 17:00 -- razao pela qual esta rede
 * existe no sistema.
 *
 * `bbox` fica de fora do only: e config, nao muda com a coleta.
 */
useAtualizacaoAoVivo({
  canal: 'medalhao.cemaden',
  evento: '.GoldAtualizado',
  props: ['estacoes', 'estatisticas'],
});

// Faixas, paletas e formatadores vivem no composable: as mesmas faixas
// alimentam a tela do INMET e os CASE das matviews.
const { legenda, corDaClasse, rotuloDaClasse, formatarMm, formatarDataHora } = usePrecipitacao();

/*
 * Paginacao no cliente, nao no servidor: o mapa precisa de TODAS as estacoes de
 * qualquer forma, entao paginar no backend exigiria uma segunda consulta.
 *
 * Aqui sao 830 estacoes contra as 60 do INMET. Ainda cabe no cliente -- o
 * payload sao numeros e dois nomes curtos por linha -- mas e o limite: se a
 * rede dobrar, o mapa deve passar a receber um agregado por municipio em vez
 * de ponto por estacao.
 */
const POR_PAGINA = 10;
const pagina = ref(1);

const paginacao = computed(() => ({
  current_page: pagina.value,
  per_page: POR_PAGINA,
  total: props.estacoes.length,
  last_page: Math.max(1, Math.ceil(props.estacoes.length / POR_PAGINA)),
}));

const estacoesDaPagina = computed(() => {
  const inicio = (pagina.value - 1) * POR_PAGINA;

  return props.estacoes.slice(inicio, inicio + POR_PAGINA);
});

function irParaPagina(numero) {
  pagina.value = Math.min(Math.max(1, numero), paginacao.value.last_page);
}

// A pagina traduz estacao -> ponto; a mecanica de Leaflet vive no componente.
// O popup vai estruturado: quem escapa e o componente, o que importa porque
// nome de estacao e municipio vem do feed do CEMADEN.
const pontosDoMapa = computed(() => props.estacoes.map((estacao) => ({
  id: estacao.id,
  latitude: estacao.latitude,
  longitude: estacao.longitude,
  cor: corDaClasse(estacao.classe_precipitacao),
  raio: 5,
  popup: {
    titulo: estacao.nome_estacao,
    linhas: [
      { rotulo: 'Municipio', valor: estacao.municipio },
      { rotulo: 'Tipo', valor: estacao.tipo },
      { rotulo: 'Chuva 24h', valor: formatarMm(estacao.acumulado_24h) },
      { rotulo: 'Nivel', valor: rotuloDaClasse(estacao.classe_precipitacao) },
      { rotulo: 'Medido em', valor: formatarDataHora(estacao.medido_em) },
    ],
  },
})));
</script>

<style scoped>
/* Dark Mode Base */
/*
 * Regras BASE = tema claro. O escuro vem em :global(.dark), classe que o
 * useTheme poe no <html>. Antes esta pagina tinha `dark` fixo no proprio
 * container e cores escuras aqui, entao ignorava o tema do site.
 */
.cemaden-container {
  /*
   * Um token por papel, em vez de repetir cada regra duas vezes. Trocar o tema
   * troca so este bloco.
   */
  --sup: #ffffff;
  --sup-2: #f1f5f9;
  --borda: #e2e8f0;
  --texto: #1e293b;
  --texto-fraco: #64748b;
  --overlay: rgba(255, 255, 255, 0.94);
  --mapa-fallback: #e2e8f0;

  background-color: #f8fafc;
  min-height: calc(100vh - 64px); /* Account for topbar height */
  color: var(--texto);
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
  color: var(--texto);
}

.search-input {
  background: var(--sup);
  border: 1px solid var(--borda);
  color: var(--texto);
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
  border: 1px solid var(--borda);
  margin-bottom: 24px;
  box-sizing: border-box; /* Include border in width calculation */
}


.mapa-area {
  height: 100%;
  width: 100%;
  background: var(--mapa-fallback); /* Fallback */
}

/* Overlays */
.map-overlay {
  position: absolute;
  background: var(--overlay);
  border: 1px solid var(--borda);
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
  color: var(--texto);
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--texto-fraco);
}

.stat-row strong {
  color: var(--texto);
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
  color: var(--texto-fraco);
}

.color-box {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.legend-footer {
  margin-top: 12px;
  font-size: 10px;
  color: var(--texto-fraco);
  font-style: italic;
}

/* Table */
.table-container {
  background: var(--sup);
  border-radius: 8px;
  border: 1px solid var(--borda);
  overflow: hidden;
  width: 100%; /* Ensure it doesn't overflow container */
  box-sizing: border-box; /* Include border in width calculation */
  overflow-x: auto; /* Allow horizontal scroll if needed */
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
  color: var(--texto);
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
  color: var(--texto-fraco);
  font-size: 12px;
}

/* Base Responsive Container */
.cemaden-container {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
  box-sizing: border-box;
}

/* Responsive Adjustments */
.empty-cell {
  text-align: center;
  padding: 1.5rem;
  color: var(--texto-fraco);
}


@media (max-width: 768px) {
  .cemaden-container {
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
    background: var(--sup);
    border: 1px solid var(--borda);
  }

  /* Table adjustments */
  .table-container {
    margin: 0 -12px;
    width: calc(100% + 24px);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }
  
  .dados-table th, .dados-table td {
    padding: 8px 10px;
    font-size: 12px;
  }
  
  .station-name {
    font-size: 13px;
  }
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
</style>

<!--
  Bloco NAO-scoped de proposito.

  A variante escura depende da classe `dark` que o useTheme poe no <html>, ou
  seja, de um ancestral FORA deste componente. `:global(.dark) .cemaden-container`
  dentro do <style scoped> nao serve: o compilador descarta tudo depois do
  :global() e emite apenas `.dark`, o que define os tokens no proprio <html> --
  onde a redefinicao local do container os sobrescreve, e o tema escuro nunca
  chega. Foi exatamente o defeito visto na tela.

  Qualificar por .cemaden-container mantem o alcance na pagina, mesmo sem scope.
-->
<style>
.dark .cemaden-container {
  --sup: #1a1d21;
  --sup-2: #25292f;
  --borda: #374151;
  --texto: #e5e7eb;
  --texto-fraco: #9ca3af;
  --overlay: rgba(26, 29, 33, 0.95);
  --mapa-fallback: #1a1d21;

  background-color: #111315;
}
</style>
