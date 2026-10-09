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

export const CLASSE_CAMPO = 'w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';

export const MOTIVOS_ROTA = {
  setor_sem_tempo: 'setor sem tempo calculado',
  estrangulamento_abaixo_minimo: 'estrangulamento abaixo de 1,2 m',
};

export const SITUACOES_SETOR = {
  via_insuficiente: 'largura útil insuficiente',
  densidade_inviavel: 'densidade inviável',
};

export function separarLista(texto) {
  return (texto ?? '').split(',').map((parte) => parte.trim()).filter(Boolean);
}
