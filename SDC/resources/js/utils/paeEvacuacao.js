// Rotulos e formatacao compartilhados pela conferencia de evacuacao (PAE, Anexo E).
const ROTULOS_SITUACAO = { nao_conferida: 'não conferida', conforme: 'conforme', nao_conforme: 'não conforme' };

export function rotuloSituacaoEvacuacao(situacao) {
  return ROTULOS_SITUACAO[situacao] ?? ROTULOS_SITUACAO.nao_conferida;
}

export function classeSituacaoEvacuacao(situacao) {
  return situacao === 'nao_conforme' ? 'text-red-700 dark:text-red-300' : 'text-slate-600 dark:text-slate-300';
}

export function numero(valor, casas = 2) {
  if (valor === null || valor === undefined) return '—';
  return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
}

export const MOTIVOS_ROTA = {
  setor_sem_tempo: 'setor sem tempo calculado',
  estrangulamento_abaixo_minimo: 'estrangulamento abaixo de 1,2 m',
};

export const SITUACOES_SETOR = {
  via_insuficiente: 'largura útil insuficiente',
  densidade_inviavel: 'densidade inviável',
};

// Primeira mensagem do erro da linha: a chave exata ou qualquer chave aninhada (prefixo.N).
export function erroDaLinha(erros, prefixo) {
  const chave = Object.keys(erros).find((c) => c === prefixo || c.startsWith(`${prefixo}.`));
  return chave ? erros[chave] : null;
}

export function separarLista(texto) {
  return (texto ?? '').split(',').map((parte) => parte.trim()).filter(Boolean);
}
