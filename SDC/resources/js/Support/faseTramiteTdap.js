/**
 * Cores das fases de tramitacao do cronograma (FaseCronograma no backend).
 * Fonte unica para o badge, as colunas do Acompanhamento e o stepper.
 * Classes por extenso: string dinamica some no purge do Tailwind.
 */
const ESTILOS = {
  elaboracao: {
    cor: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    ponto: 'bg-amber-500',
    borda: 'border-t-amber-500',
  },
  em_analise: {
    cor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    ponto: 'bg-blue-500',
    borda: 'border-t-blue-500',
  },
  aprovado: {
    cor: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    ponto: 'bg-violet-500',
    borda: 'border-t-violet-500',
  },
  em_execucao: {
    cor: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    ponto: 'bg-emerald-500',
    borda: 'border-t-emerald-500',
  },
  entregue: {
    cor: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
    ponto: 'bg-teal-500',
    borda: 'border-t-teal-500',
  },
  concluido: {
    cor: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    ponto: 'bg-slate-500',
    borda: 'border-t-slate-500',
  },
};

export function estiloDaFase(valor) {
  return ESTILOS[valor] ?? ESTILOS.concluido;
}
