/**
 * Regras de cor e estilo das tres camadas do mapa do painel de indicadores.
 *
 * Funcoes puras (sem Vue, sem Leaflet): o painel so decide qual camada esta
 * ativa e entrega o tema; daqui sai o estilo de cada municipio.
 */

/** Ordem da paginacao do mapa "3 em 1", com o titulo e o subtitulo de cada pagina. */
export const CAMADAS = [
  {
    valor: 'cobertura',
    rotulo: 'Cobertura municipal',
    titulo: 'Municípios com capacitação',
    subtitulo: 'Azul: municípios com capacitação concluída (mais escuro, mais concluídos). Laranja: só com capacitação em andamento',
  },
  {
    valor: 'volume',
    rotulo: 'Pessoas por município',
    titulo: 'Pessoas capacitadas por município',
    subtitulo: 'O tamanho da bolha segue o número de pessoas que concluíram capacitação no município',
  },
  {
    valor: 'redecs',
    rotulo: 'REDECs',
    titulo: 'Municípios capacitados por REDEC',
    subtitulo: 'Cada REDEC demarcada na sua cor; municípios com capacitação aparecem fortes. Clique para destacar uma REDEC',
  },
];

// Mesmas cores de situacao dos graficos: uma fonte so (useTemaGrafico).
export { COR_CONCLUIDO, COR_EM_ANDAMENTO } from '@/Composables/treinamento/useTemaGrafico';

// Azul monocromatico, do claro ao escuro: quanto mais concluidos, mais escuro.
const AZUIS = ['#bfdbfe', '#93c5fd', '#60a5fa', '#2563eb', '#1e3a8a'];

const PAR = (claro, escuro) => ({ claro, escuro });
const SEM_DADO = PAR('#e2e8f0', '#334155');
const BORDA = PAR('#ffffff', '#0f172a');
const BORDA_DESTAQUE = PAR('#0f172a', '#f8fafc');

const FATOR_FORA_DO_DESTAQUE = 0.35;

const tom = (par, escuro) => (escuro ? par.escuro : par.claro);

/**
 * Cor da REDEC, deterministica pelo id: o angulo aureo espalha os matizes
 * (ids vizinhos nunca ficam parecidos) e tres niveis de luminosidade alternados
 * separam os que ainda caem perto. Mais claro no escuro, para manter contraste
 * com o fundo.
 */
export function corRedec(redecId, escuro) {
  const id = Number(redecId);
  const matiz = Math.round((id * 137.508) % 360);
  const luminosidade = (escuro ? [62, 74, 54] : [46, 60, 36])[id % 3];

  return `hsl(${matiz} 68% ${luminosidade}%)`;
}

/**
 * Faixas da escala azul para o maior valor do recorte. A raiz torna as faixas
 * mais finas na parte baixa, onde esta a maioria dos municipios.
 */
export function faixasConcluidos(maximo) {
  const teto = Math.max(1, maximo);
  const total = AZUIS.length;

  return AZUIS
    .map((cor, indice) => ({
      cor,
      indice,
      de: indice === 0 ? 1 : Math.ceil(teto * (indice / total) ** 2),
      ate: indice === total - 1 ? teto : Math.ceil(teto * ((indice + 1) / total) ** 2) - 1,
    }))
    .filter((faixa) => faixa.de <= faixa.ate);
}

export function corPorConcluidos(concluidos, maximo) {
  const teto = Math.max(1, maximo);
  const indice = Math.min(AZUIS.length - 1, Math.floor(Math.sqrt(concluidos / teto) * AZUIS.length));

  return AZUIS[indice];
}

export function neutro(escuro) {
  return tom(SEM_DADO, escuro);
}

function preenchimento(camada, linha, redecId, contexto) {
  const { escuro, maximoConcluidos } = contexto;

  if (camada === 'cobertura') {
    if (linha?.concluidos > 0) {
      return { fillColor: corPorConcluidos(linha.concluidos, maximoConcluidos), fillOpacity: 0.9 };
    }

    if (linha?.em_andamento > 0) {
      return { fillColor: COR_EM_ANDAMENTO, fillOpacity: 0.9 };
    }

    return { fillColor: neutro(escuro), fillOpacity: 0.55 };
  }

  if (camada === 'redecs') {
    if (redecId === null || redecId === undefined) {
      return { fillColor: neutro(escuro), fillOpacity: 0.4 };
    }

    // Atingido forte, nao atingido esmaecido: a cor diz a REDEC, a opacidade diz o dado.
    return { fillColor: corRedec(redecId, escuro), fillOpacity: linha?.participantes > 0 ? 0.85 : 0.25 };
  }

  // 'volume': o poligono e so contexto; o dado esta nas bolhas.
  return { fillColor: neutro(escuro), fillOpacity: 0.45 };
}

/**
 * Estilo de um municipio. `destacada` (id de REDEC ou null) faz os municipios
 * dela ganharem borda forte e ir para a frente; os demais esmaecem.
 */
export function estiloMunicipio({ camada, linha, redecId, destacada, escuro, maximoConcluidos }) {
  const base = preenchimento(camada, linha, redecId, { escuro, maximoConcluidos });
  // Na camada de REDECs a borda tem a cor da propria REDEC: a divisa entre
  // municipios da mesma REDEC some e a regiao aparece demarcada como um bloco.
  const bordaRedec = camada === 'redecs' && redecId !== null && redecId !== undefined;
  const estilo = bordaRedec
    ? { ...base, color: corRedec(redecId, escuro), weight: 1, opacity: 0.9 }
    : { ...base, color: tom(BORDA, escuro), weight: 0.6, opacity: 1 };

  if (destacada === null || destacada === undefined) {
    return estilo;
  }

  if (redecId === destacada) {
    return { ...estilo, color: tom(BORDA_DESTAQUE, escuro), weight: 2, frente: true };
  }

  return { ...estilo, fillOpacity: estilo.fillOpacity * FATOR_FORA_DO_DESTAQUE };
}
