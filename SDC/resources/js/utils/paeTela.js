// Apoio comum das telas do PAE (DCO, evacuacao, ficha cadastral e simulados).

/**
 * Datas puras (YYYY-MM-DD) nao passam por Date, que deslocaria um dia pelo
 * fuso; valores com horario viram data local.
 */
export function formatarData(valor) {
  if (!valor) return '—';
  const texto = String(valor);
  if (texto.length > 10) {
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
