// Rotulos e variantes de Badge da situacao anual da DCO (PAE).
const ROTULOS_SITUACAO = {
  nao_avaliada: 'não avaliada',
  nao_aplicavel: 'não aplicável',
  comprovada: 'comprovada',
  aguardando_prazo: 'aguardando prazo',
  pendente_emissao: 'pendente para emissão',
  atrasada: 'atrasada',
  nao_conforme: 'não conforme',
};

const VARIANTES = {
  comprovada: 'success',
  nao_aplicavel: 'neutral',
  aguardando_prazo: 'neutral',
  nao_avaliada: 'warning',
  pendente_emissao: 'warning',
  atrasada: 'danger',
  nao_conforme: 'danger',
};

export function rotuloSituacaoDco(situacao) {
  return ROTULOS_SITUACAO[situacao] ?? ROTULOS_SITUACAO.nao_avaliada;
}

export function varianteSituacaoDco(situacao) {
  return VARIANTES[situacao] ?? 'default';
}
