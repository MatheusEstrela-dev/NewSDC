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
  /**
   * [{ id, geojson, cor, rotulo }] — geojson tem de ser OBJETO ja decodificado.
   *
   * O PDO do Postgres entrega coluna jsonb como STRING, entao quem consome a
   * matview precisa dar JSON.parse antes de montar esta prop. Passar a string
   * crua nao desenha nada E NAO LEVANTA ERRO: o L.geoJSON simplesmente ignora,
   * e o poligono some sem deixar rastro no console.
   */
  poligonos: { type: Array, default: () => [] },
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

// Camada propria, criada ANTES da de pontos: no Leaflet a ordem de adicao
// define o empilhamento, e area de alerta desenhada por cima esconderia os
// pontos de chuva -- que sao justamente o dado que o operador precisa ver
// DENTRO dela.
let camadaPoligonos = null;
let observador = null;

// Indice id -> marcador, refeito a cada desenho. Existe para focarPonto()
// conseguir achar UM ponto entre centenas sem varrer a camada: a camada e um
// layerGroup e nao guarda o id de dominio de cada marcador.
const marcadoresPorId = new Map();

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

  if (camadaPoligonos) {
    camadaPoligonos.clearLayers();
  } else {
    camadaPoligonos = L.layerGroup().addTo(mapa);
  }

  props.poligonos.forEach((poligono) => {
    if (!poligono.geojson) {
      return;
    }

    const cor = poligono.cor ?? '#b45309';

    const camadaGeo = L.geoJSON(poligono.geojson, {
      style: {
        color: cor,
        weight: 2,
        // Preenchimento fraco de proposito: a area e recorte, nao dado. Opaca
        // demais, ela compete com os pontos que estao dentro dela.
        fillColor: cor,
        fillOpacity: 0.12,
      },
    });

    if (poligono.rotulo) {
      camadaGeo.bindPopup(escapar(poligono.rotulo));
    }

    camadaGeo.addTo(camadaPoligonos);
  });

  if (camada) {
    camada.clearLayers();
  } else {
    camada = L.layerGroup().addTo(mapa);
  }

  // clearLayers() destruiu os marcadores: o indice tem de morrer com eles,
  // senao focarPonto() guardaria referencia para marcador fora do mapa.
  marcadoresPorId.clear();

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

    if (ponto.id !== undefined && ponto.id !== null) {
      marcadoresPorId.set(String(ponto.id), marcador);
    }
  });
}

/**
 * Centraliza o mapa num ponto e abre o popup dele.
 *
 * Imperativo, e nao por prop, porque focar e EVENTO e nao estado: com
 * `pontoFocado` como prop, pesquisar a mesma cidade duas vezes seguidas nao
 * faria nada -- a prop nao mudaria e o watch nao dispararia.
 *
 * Devolve false quando o ponto nao esta no mapa, para quem chamou poder dizer
 * ao usuario que nao achou em vez de fingir que centralizou.
 */
function focarPonto(id, { zoom = 11 } = {}) {
  const marcador = marcadoresPorId.get(String(id));

  if (!mapa || !marcador) {
    return false;
  }

  // flyTo e nao setView: a animacao mostra PARA ONDE o mapa foi, e num mapa de
  // 890 pontos um salto seco deixa o operador sem saber o que mudou.
  mapa.flyTo(marcador.getLatLng(), zoom, { duration: 0.8 });

  // Depois da animacao: abrir o popup antes faz o Leaflet reposicionar o mapa
  // no meio do voo para caber o balao, e o destino final sai torto.
  mapa.once('moveend', () => marcador.openPopup());

  return true;
}

defineExpose({ focarPonto });

function enquadrar() {
  if (!mapa) {
    return;
  }

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
}

onMounted(async () => {
  await nextTick();

  const container = document.getElementById(idMapa);

  mapa = L.map(idMapa, {
    zoomControl: false,
    attributionControl: false,
  });

  enquadrar();

  L.control.zoom({ position: 'topleft' }).addTo(mapa);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(mapa);

  desenhar();

  /*
   * O container recebe altura por cadeia de `height: 100%` ate o .map-wrapper
   * da pagina, e essa cadeia pode nao estar resolvida no onMounted. Medindo
   * zero, o Leaflet calcula o zoom para caber tudo em 0px -- e cai no zoom
   * minimo, mostrando o mundo inteiro em vez de Minas.
   *
   * O ResizeObserver reenquadra na primeira medida com altura de verdade e se
   * desliga: reenquadrar a cada resize desfaria o zoom que o usuario deu.
   */
  if (container && typeof ResizeObserver !== 'undefined') {
    observador = new ResizeObserver(() => {
      if (container.clientHeight > 0 && container.clientWidth > 0) {
        mapa?.invalidateSize();
        enquadrar();
        observador?.disconnect();
        observador = null;
      }
    });

    observador.observe(container);
  }
});

onBeforeUnmount(() => {
  observador?.disconnect();
  observador = null;

  if (mapa) {
    mapa.remove();
    mapa = null;
    camada = null;
  }
});

watch(() => [props.pontos, props.poligonos], desenhar, { deep: true });
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
