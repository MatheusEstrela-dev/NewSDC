/**
 * Leitura dos eventos do Historico do TDAP (`tdap_historicos.tipo_evento`,
 * formato "entidade.acao"): rotulo legivel e tom de cor. Fonte unica para o
 * dashboard e a tela de auditoria.
 */

const ROTULOS = {
  'cronograma.criado': 'Cronograma criado',
  'cronograma.ativado': 'Cronograma ativado',
  'cronograma.encerrado': 'Cronograma encerrado',
  'cronograma.prorrogado': 'Cronograma prorrogado',
  'cronograma.arquivado': 'Cronograma arquivado',
  'cronograma.desarquivado': 'Cronograma desarquivado',
  'cronograma.pontos_alterados': 'Pontos de captação alterados',
  'cronograma.entregas_recalculadas': 'Água entregue recalculada',
  'viagem.registrada': 'Nova viagem registrada',
  'viagem.aprovada': 'Viagem aprovada',
  'viagem.rejeitada': 'Viagem rejeitada',
  'viagem.removida': 'Viagem removida',
  'vistoria.aprovada': 'Vistoria aprovada',
  'vistoria.reprovada': 'Vistoria reprovada',
  'vistoria.excluida': 'Vistoria excluída',
  'prestador.criado': 'Prestador cadastrado',
  'prestador.atualizado': 'Prestador atualizado',
  'prestador.ativado': 'Prestador ativado',
  'prestador.desativado': 'Prestador desativado',
  'prestador.excluido': 'Prestador excluído',
  'caminhao.criado': 'Caminhão cadastrado',
  'caminhao.atualizado': 'Caminhão atualizado',
  'caminhao.ativado': 'Caminhão ativado',
  'caminhao.desativado': 'Caminhão desativado',
  'caminhao.excluido': 'Caminhão excluído',
  'caminhao.prestador': 'Caminhão trocou de prestador',
};

/** Tipo sem rotulo cadastrado (ex.: "legado") ainda sai legivel: "Legado". */
export function rotuloDoEvento(tipo) {
  if (!tipo) return 'Evento';
  if (ROTULOS[tipo]) return ROTULOS[tipo];

  const texto = tipo.replace(/[._]/g, ' ');
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/**
 * Uma cor por TIPO DE ACAO, igual em todo o modulo: verde criou, amarelo
 * editou, azul aprovou/ativou, vermelho excluiu/recusou, roxo encerrou.
 * Classes por extenso: string dinamica some no purge do Tailwind.
 */
export const ACOES = {
  criacao: {
    rotulo: 'Criação',
    ponto: 'bg-emerald-500',
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  },
  edicao: {
    rotulo: 'Edição',
    ponto: 'bg-amber-500',
    badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  },
  aprovacao: {
    rotulo: 'Aprovação',
    ponto: 'bg-blue-500',
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  },
  exclusao: {
    rotulo: 'Exclusão/recusa',
    ponto: 'bg-red-500',
    badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  },
  encerramento: {
    rotulo: 'Encerramento',
    ponto: 'bg-violet-500',
    badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
  },
  outro: {
    rotulo: 'Outro',
    ponto: 'bg-slate-400',
    badge: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
  },
};

/** Acao do evento ("entidade.acao", parte depois do ponto) -> tipo de acao. */
const TIPO_DA_ACAO = {
  criado: 'criacao',
  registrada: 'criacao',
  atualizado: 'edicao',
  pontos_alterados: 'edicao',
  prorrogado: 'edicao',
  prestador: 'edicao',
  entregas_recalculadas: 'edicao',
  ativado: 'aprovacao',
  aprovada: 'aprovacao',
  desarquivado: 'aprovacao',
  rejeitada: 'exclusao',
  reprovada: 'exclusao',
  removida: 'exclusao',
  excluida: 'exclusao',
  excluido: 'exclusao',
  encerrado: 'encerramento',
  arquivado: 'encerramento',
  desativado: 'encerramento',
};

/** { rotulo, ponto, badge } do tipo de acao; evento sem acao conhecida cai em "Outro". */
export function acaoDoEvento(tipo) {
  const acao = (tipo ?? '').split('.').pop();
  return ACOES[TIPO_DA_ACAO[acao] ?? 'outro'];
}

export function classeBadgeDoEvento(tipo) {
  return acaoDoEvento(tipo).badge;
}
