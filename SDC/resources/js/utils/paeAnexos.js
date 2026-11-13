// Apoio dos anexos do protocolo PAE: formatos aceitos, pasta sugerida pelo
// nome do arquivo e traducao da leitura do PDF para os campos do relatorio.

// Espelha StorePaeFormAnexoRequest::EXTENSOES e TAMANHO_MAXIMO_KB.
export const EXTENSOES_ACEITAS = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'pptx', 'docx', 'kmz', 'kml', 'zip', 'shp', 'shx', 'dbf', 'prj', 'cpg', 'qix', 'qmd'];
export const TAMANHO_MAXIMO_BYTES = 150 * 1024 * 1024;

const EXTENSOES_KMZ = ['kmz', 'kml'];
const EXTENSOES_SHP = ['shp', 'shx', 'dbf', 'prj', 'cpg', 'qix', 'qmd', 'zip'];

export function extensaoDe(nome) {
  return nome.includes('.') ? nome.split('.').pop().toLowerCase() : '';
}

export function formatarTamanho(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

/**
 * Pasta sugerida pelo nome, no padrao em que os empreendedores entregam:
 * "ANEXO C - ..." (anexos da resolucao), "ANEXO 3 - ..." (anexos internos do
 * Anexo B) e a cartografia (mapas em PDF, KMZ/KML, shapefile), que vai para a
 * pasta Mapas. E so sugestao: o analista troca antes de enviar.
 */
export function categoriaSugerida(nome, categorias, padrao = 'outros') {
  const existe = (valor) => categorias.some((c) => c.value === valor);
  const extensao = extensaoDe(nome);

  const letra = nome.match(/anexo[\s_-]*([a-j])(?![a-z])/i);
  if (letra && existe(`anexo_${letra[1].toLowerCase()}`)) return `anexo_${letra[1].toLowerCase()}`;

  const numero = nome.match(/anexo[\s_-]*([1-7])(?!\d)/i);
  if (numero && existe(`anexo_b${numero[1]}`)) return `anexo_b${numero[1]}`;

  if (EXTENSOES_KMZ.includes(extensao) && existe('mapas_kmz')) return 'mapas_kmz';
  if (EXTENSOES_SHP.includes(extensao) && existe('mapas_shp')) return 'mapas_shp';
  if (/mapa/i.test(nome) && existe('mapas')) return 'mapas';

  return existe(padrao) ? padrao : 'outros';
}

// Rotulos da leitura do PDF (chaves de LeitorDadosBasicosPae), na ordem da ficha.
export const ROTULOS_LEITURA = {
  empreendedor: 'Empreendedor',
  empreendimento: 'Empreendimento',
  barragem: 'Barragem',
  mina: 'Mina',
  metodo_alteamento: 'Método de alteamento',
  volume_reservatorio: 'Volume do reservatório',
  municipio: 'Município',
  latitude: 'Latitude',
  longitude: 'Longitude',
  tipo_rejeito: 'Tipo de rejeito',
  rejeito_toxico: 'Rejeito tóxico à saúde humana',
  extensao_zas: 'Extensão da ZAS',
  populacao_zas: 'População na ZAS',
  populacao_dificuldade_locomocao: 'População com dificuldade de locomoção',
  populacao_zss: 'População na ZSS',
  municipios_zas: 'Municípios na ZAS',
  municipios_zss: 'Municípios na ZSS',
  cursos_agua: "Cursos d'água afetados",
  edificacoes_sensiveis: 'Edificações sensíveis',
  estruturas_associadas: 'Estruturas associadas',
  data_exercicio: 'Data do exercício simulado',
};

const METODOS_CONSTRUTIVOS = ['Jusante', 'Montante', 'Etapa única', 'Linha de Centro'];

/** Campos das Informacoes Gerais que a leitura consegue sugerir. */
export function leituraParaInfoGerais(leitura) {
  const campos = leitura?.campos ?? {};
  const metodo = METODOS_CONSTRUTIVOS.find((m) => (campos.metodo_alteamento ?? '').toLowerCase().includes(m.toLowerCase()));

  return Object.fromEntries(Object.entries({
    barragem: campos.barragem,
    empreendedor_res: campos.empreendedor,
    metodo_construtivo: metodo,
    municipio_id: leitura?.municipio_id ? String(leitura.municipio_id) : undefined,
  }).filter(([, valor]) => valor));
}

/** "85.000,00 m3" -> 85000; "10 km" -> 10; sem numero -> undefined. */
function numeroBr(texto) {
  const achado = String(texto ?? '').match(/-?\d[\d.]*(?:,\d+)?/);
  if (!achado) return undefined;
  const numero = Number(achado[0].replace(/\./g, '').replace(',', '.'));
  return Number.isFinite(numero) ? numero : undefined;
}

/** Campos da Ficha cadastral (Anexo B, item 2) que a leitura consegue sugerir. */
export function leituraParaFicha(leitura) {
  const campos = leitura?.campos ?? {};

  return Object.fromEntries(Object.entries({
    nome_barragem: campos.barragem,
    nome_mina: campos.mina,
    metodo_construtivo: campos.metodo_alteamento,
    volume_reservatorio: numeroBr(campos.volume_reservatorio),
    municipio_sede_id: leitura?.municipio_id ?? undefined,
    latitude: campos.latitude ? Number(campos.latitude) : undefined,
    longitude: campos.longitude ? Number(campos.longitude) : undefined,
    tipo_rejeito: campos.tipo_rejeito,
    extensao_zas_km: numeroBr(campos.extensao_zas),
    populacao_zas: numeroBr(campos.populacao_zas),
    populacao_zas_mobilidade_reduzida: numeroBr(campos.populacao_dificuldade_locomocao),
    populacao_zss: numeroBr(campos.populacao_zss),
  }).filter(([, valor]) => valor !== undefined && valor !== null && valor !== ''));
}

// A sugestao viaja pela sessionStorage da aba ate a tela de destino
// ('relatorio' ou 'ficha'), que a consome uma unica vez.
const chaveLeitura = (protocoloId, destino) => `pae-leitura:${destino}:${protocoloId}`;

export function guardarLeitura(protocoloId, destino, valores) {
  if (!Object.keys(valores ?? {}).length) return;
  try {
    window.sessionStorage.setItem(chaveLeitura(protocoloId, destino), JSON.stringify(valores));
  } catch {
    // Sem sessionStorage (modo privado restrito) a sugestao so nao e levada adiante.
  }
}

export function retirarLeitura(protocoloId, destino) {
  try {
    const bruto = window.sessionStorage.getItem(chaveLeitura(protocoloId, destino));
    window.sessionStorage.removeItem(chaveLeitura(protocoloId, destino));
    return bruto ? JSON.parse(bruto) : null;
  } catch {
    return null;
  }
}

/** Preenche so os campos vazios do alvo; devolve quantos entraram. */
export function aplicarEmVazios(alvo, sugestao) {
  const novos = Object.entries(sugestao ?? {}).filter(([campo]) => alvo[campo] === '' || alvo[campo] === null || alvo[campo] === undefined);
  Object.assign(alvo, Object.fromEntries(novos));
  return novos.length;
}
