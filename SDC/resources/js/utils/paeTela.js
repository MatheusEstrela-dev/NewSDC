// Apoio comum das telas do PAE (DCO, evacuacao, ficha cadastral e simulados).

// Meia-noite UTC e como o Laravel serializa colunas `date` com cast (Carbon).
const MEIA_NOITE_UTC = /^\d{4}-\d{2}-\d{2}T00:00:00(\.\d+)?Z?$/;

/**
 * Datas puras (YYYY-MM-DD) e valores a meia-noite UTC nao passam por Date, que
 * deslocaria um dia pelo fuso; so valores com horario real viram data local.
 */
export function formatarData(valor) {
  if (!valor) return '—';
  const texto = String(valor);
  if (texto.length > 10 && !MEIA_NOITE_UTC.test(texto)) {
    const data = new Date(texto);
    if (!Number.isNaN(data.getTime())) return data.toLocaleDateString('pt-BR');
  }
  const [ano, mes, dia] = texto.slice(0, 10).split('-');
  return `${dia}/${mes}/${ano}`;
}

/** Junta os erros de chaves sem campo proprio no formulario (ex.: avaliacao, dco, protocolo). */
export function errosSemCampo(erros, camposConhecidos) {
  return Object.entries(erros)
    .filter(([campo]) => !camposConhecidos.includes(campo))
    .map(([, mensagem]) => mensagem)
    .join(' ');
}

/** {chave: rotulo} para a lista {value, label} dos selects. */
export function opcoes(mapa) {
  return Object.entries(mapa).map(([value, label]) => ({ value, label }));
}

/** UUID v4; `crypto.randomUUID` so existe em contexto seguro (https/localhost), entao cai para getRandomValues. */
export function novoUuid() {
  if (typeof crypto.randomUUID === 'function') return crypto.randomUUID();
  const bytes = crypto.getRandomValues(new Uint8Array(16));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
