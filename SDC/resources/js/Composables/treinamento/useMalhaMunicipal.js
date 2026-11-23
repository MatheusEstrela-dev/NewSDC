import { markRaw, onMounted, ref, shallowRef } from 'vue';

const URL_MALHA = '/geo/mg-municipios.geojson';

// Cache de modulo: a malha tem ~500 KB e 853 poligonos; remontar o painel (troca
// de filtro, navegacao de volta) nao pode baixar nem reprocessar de novo.
let promessa = null;

/**
 * Features da malha municipal de MG, cada uma marcada como raw: o Vue nao
 * transforma em proxy (nem percorre) milhares de coordenadas, e o
 * `watch deep` do MapaLeaflet pula esses objetos.
 */
export function carregarMalhaMunicipal() {
  promessa ??= fetch(URL_MALHA)
    .then((resposta) => {
      if (!resposta.ok) {
        throw new Error(`Malha municipal indisponivel (${resposta.status})`);
      }

      return resposta.json();
    })
    .then((colecao) => (colecao.features ?? []).map((feature) => markRaw(feature)))
    .catch((erro) => {
      // Nao guarda a falha: a proxima montagem tenta de novo.
      promessa = null;
      throw erro;
    });

  return promessa;
}

/** Estado reativo do carregamento: 'carregando' | 'pronta' | 'erro'. */
export function useMalhaMunicipal() {
  const features = shallowRef([]);
  const status = ref('carregando');

  onMounted(async () => {
    try {
      features.value = await carregarMalhaMunicipal();
      status.value = 'pronta';
    } catch {
      status.value = 'erro';
    }
  });

  return { features, status };
}
