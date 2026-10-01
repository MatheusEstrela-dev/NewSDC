// Rotulos e cores do catalogo de resgate, num lugar so para vitrine, modais e
// fila de propostas. Os valores espelham os enums do backend (TipoItem,
// AcaoProposta) e os CHECKs de resgate.catalogo_itens.

export const TIPOS_ITEM = {
  servico: { rotulo: 'Serviço', cor: 'sky' },
  adesao: { rotulo: 'Adesão', cor: 'violet' },
  bem_consumo: { rotulo: 'Bem de consumo', cor: 'emerald' },
  bem_permanente: { rotulo: 'Bem permanente', cor: 'amber' },
};

export const INSTRUMENTOS = [
  { value: 'ordem_servico', label: 'Ordem de serviço' },
  { value: 'termo_adesao', label: 'Termo de adesão' },
  { value: 'termo_entrega', label: 'Termo de entrega' },
  { value: 'termo_cessao_uso', label: 'Termo de cessão de uso' },
  { value: 'termo_comodato', label: 'Termo de comodato' },
  { value: 'termo_doacao', label: 'Termo de doação' },
];

export const FAIXAS_OPCOES = [
  { value: 'bronze', label: 'Bronze' },
  { value: 'prata', label: 'Prata' },
  { value: 'ouro', label: 'Ouro' },
  { value: 'diamante', label: 'Diamante' },
];

export const BENEFICIARIOS = [
  { value: 'municipio', label: 'Município' },
  { value: 'orgao', label: 'Órgão' },
];

export function rotuloInstrumento(valor) {
  return INSTRUMENTOS.find((opcao) => opcao.value === valor)?.label ?? valor;
}

export function rotuloFaixa(valor) {
  return FAIXAS_OPCOES.find((opcao) => opcao.value === valor)?.label ?? valor;
}
