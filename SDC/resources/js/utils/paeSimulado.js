// Rotulos e metadados compartilhados pela fase F (simulados do Anexo C).
import { opcoes } from '@/utils/paeTela';

const ROTULOS_SITUACAO = {
  nao_avaliada: 'não avaliado',
  dispensado: 'dispensado',
  em_dia: 'em dia',
  nao_validado: 'não validado',
  vencido: 'vencido',
  pendente_emissao: 'pendente para emissão',
};

const CLASSES_TEXTO = {
  vencido: 'text-red-700 dark:text-red-300',
  nao_validado: 'text-red-700 dark:text-red-300',
  pendente_emissao: 'text-amber-700 dark:text-amber-300',
  nao_avaliada: 'text-amber-700 dark:text-amber-300',
};

const VARIANTES = {
  em_dia: 'success',
  dispensado: 'neutral',
  nao_validado: 'danger',
  vencido: 'danger',
  pendente_emissao: 'warning',
  nao_avaliada: 'warning',
};

export function rotuloSituacaoSimulado(situacao) {
  return ROTULOS_SITUACAO[situacao] ?? ROTULOS_SITUACAO.nao_avaliada;
}

export function classeSituacaoSimulado(situacao) {
  return CLASSES_TEXTO[situacao] ?? 'text-slate-600 dark:text-slate-300';
}

export function varianteSituacaoSimulado(situacao) {
  return VARIANTES[situacao] ?? 'default';
}

/** Situacoes em que a emissao do CCPAE nao esbarra no simulado. */
export function simuladoPronto(situacao) {
  return situacao === 'dispensado' || situacao === 'em_dia';
}

export const MOTIVOS_DISPENSA = {
  licenca_instalacao: 'PAE para Licença de Instalação (Art. 17)',
  metodo_alternativo: 'Método alternativo aprovado pela CEDEC (Art. 21)',
};
export const OPCOES_MOTIVO = opcoes(MOTIVOS_DISPENSA);

export const OPCOES_NIVEL = [
  { value: 2, label: 'Nível 2 (evacuação preventiva)' },
  { value: 3, label: 'Nível 3 (evacuação imediata)' },
];

// Categorias de tempo da secao 7 do Anexo C e o criterio do item 8.1 que elas alimentam.
export const CATEGORIAS_TEMPO = [
  { chave: 'sem_dificuldade', titulo: 'Sem dificuldade de locomoção (7.1.3)', criterio: 5, nome: 'Rota de fuga', comPopulacao: true, nivel: false },
  { chave: 'com_dificuldade', titulo: 'Com dificuldade de locomoção (7.2.4)', criterio: 6, nome: 'Grupo ou amostra', comPopulacao: true, nivel: false },
  { chave: 'ensino', titulo: 'Unidades de ensino (7.3.2)', criterio: null, nome: 'Unidade de ensino', comPopulacao: false, nivel: false },
  { chave: 'hospitalares_prisionais', titulo: 'Hospitalares e prisionais (7.4.2)', criterio: 7, nome: 'Unidade', comPopulacao: false, nivel: true },
  { chave: 'aglomeracao', titulo: 'Locais com aglomeração (7.5.2)', criterio: 8, nome: 'Edificação', comPopulacao: false, nivel: false },
];

export const ROTULOS_INDICIO = {
  saida_maior_igual_onda: 'saída maior ou igual à chegada da onda',
  houve_problemas: 'houve problemas na evacuação',
  ponto_invalido: 'ponto de encontro inválido',
  alarme_sem_morador: 'alarme não audível em todos os pontos, sem o morador indicado (nome e localização)',
};

export function descricaoIndicio(indicio) {
  const base = ROTULOS_INDICIO[indicio.codigo] ?? indicio.codigo;
  const categoria = CATEGORIAS_TEMPO.find((c) => c.chave === indicio.categoria);
  const onde = categoria ? ` · ${categoria.titulo}${indicio.nome ? `, ${indicio.nome}` : ''}` : '';
  return `${base}${onde}`;
}
