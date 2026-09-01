<template>
  <div class="mapa-leaflet-wrapper">
    <div :id="idMapa" class="mapa-leaflet"></div>

    <div v-if="$slots.legenda" class="mapa-leaflet-legenda">
      <slot name="legenda" />
    </div>
  </div>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onBeforeUnmount, onMounted, watch } from 'vue';

/**
 * Mapa Leaflet unico, consumido pelas paginas de Sismos e Inmet.
 *
 * Antes cada pagina montava o proprio L.map com o mesmo tile e o mesmo
 * circleMarker. A de Inmet ainda esquecia o onBeforeUnmount, entao a instancia
 * do mapa sobrevivia a navegacao, segurando listeners e nos de DOM.
 *
 * O popup chega ESTRUTURADO, nao como HTML pronto: quem monta a string aqui e o
 * componente, que escapa cada valor. Nome de estacao e regiao de catalogo vem de
 * fonte externa, e deixar cada pagina montar HTML espalharia essa
 * responsabilidade -- foi assim que a pagina de Sismos acabou com um escapar()
 * proprio.
 */
const props = defineProps({
  /**
   * [{ id, latitude, longitude, cor, raio, popup: { titulo, linhas: [{rotulo, valor}] } }]
   */
  pontos: { type: Array, default: () => [] },
  /** { min_lat, max_lat, min_lon, max_lon } — enquadra o mapa. */
  bbox: { type: Object, default: null },
  centro: { type: Array, default: () => [-18.5, -44.5] },
  zoom: { type: Number, default: 6 },
});

// Id proprio por instancia: as duas paginas usavam id fixo, o que quebraria se
// duas montassem na mesma arvore.
const idMapa = `mapa-leaflet-${Math.random().toString(36).slice(2, 9)}`;

let mapa = null;
let camada = null;

// O popup monta HTML e recebe dado de fonte externa: escapar evita que conteudo
// do catalogo seja interpretado como marcacao.
function escapar(texto) {
  const div = document.createElement('div');
  div.textContent = String(texto ?? '');

  return div.innerHTML;
}

function montarPopup(popup) {
  if (!popup) {
    return null;
  }

  const linhas = [];

  if (popup.titulo) {
    linhas.push(`<strong>${escapar(popup.titulo)}</strong>`);
  }

  (popup.linhas ?? []).forEach((linha) => {
    linhas.push(`${escapar(linha.rotulo)}: ${escapar(linha.valor)}`);
  });

  return linhas.join('<br>');
}

function desenhar() {
  if (!mapa) {
    return;
  }

  if (camada) {
    camada.clearLayers();
  } else {
    camada = L.layerGroup().addTo(mapa);
  }

  props.pontos.forEach((ponto) => {
    const lat = Number(ponto.latitude);
    const lon = Number(ponto.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      return;
    }

    const marcador = L.circleMarker([lat, lon], {
      radius: ponto.raio ?? 6,
      fillColor: ponto.cor ?? '#2563eb',
      color: '#1a1d21',
      weight: 1,
      opacity: 1,
      fillOpacity: 0.85,
    });

    const conteudo = montarPopup(ponto.popup);

    if (conteudo) {
      marcador.bindPopup(conteudo);
    }

    marcador.addTo(camada);
  });
}

onMounted(async () => {
  await nextTick();

  mapa = L.map(idMapa, {
    zoomControl: false,
    attributionControl: false,
  });

  if (props.bbox) {
    // Enquadra o quadrante que o backend usa, em vez de um centro fixo: mapa e
    // coleta ficam coerentes.
    mapa.fitBounds([
      [props.bbox.min_lat, props.bbox.min_lon],
      [props.bbox.max_lat, props.bbox.max_lon],
    ]);
  } else {
    mapa.setView(props.centro, props.zoom);
  }

  L.control.zoom({ position: 'topleft' }).addTo(mapa);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(mapa);

  desenhar();
});

onBeforeUnmount(() => {
  if (mapa) {
    mapa.remove();
    mapa = null;
    camada = null;
  }
});

watch(() => props.pontos, desenhar, { deep: true });
</script>

<style scoped>
.mapa-leaflet-wrapper {
  position: relative;
}

/*
 * Sem min-height de proposito: quem define altura e o consumidor.
 *
 * Um min-height aqui venceria o encolhimento da media query das paginas -- elas
 * levam o .map-wrapper para 60vh / min-height 320px no telefone, e o mapa
 * ficaria maior que o wrapper que o contem.
 */
.mapa-leaflet {
  width: 100%;
  height: 100%;
  border-radius: 0.5rem;
  z-index: 0;
}

.mapa-leaflet-legenda {
  position: absolute;
  right: 0.75rem;
  bottom: 0.75rem;
  z-index: 10;
}
</style>
