const MODULOS = {
  ajudahumanitaria: 'Ajuda Humanitária', cedec: 'CEDEC', cisterna: 'Cisternas',
  compdec: 'COMPDEC', decretacoes: 'Decretações', demandas: 'Demandas',
  estoque: 'Estoque', geoespacial: 'Geoespacial', inventario: 'Inventário',
  pae: 'PAE', plancon: 'Plano de Contingência', plantao: 'Plantão',
  pmda: 'PMDA', rat: 'RAT', suporte: 'Suporte', tdap: 'TDAP', treinamento: 'Treinamento',
};

const ENTREGAS = {
  registro_completo: 'Registro completo', relatorio_finalizado: 'Relatório finalizado',
  vistoria_validada: 'Vistoria validada', protocolo_enviado: 'Protocolo enviado',
  formulario_validado: 'Formulário validado', revisao_aceita: 'Revisão aceita',
  parecer_concluido: 'Parecer concluído', plano_enviado: 'Plano enviado',
  cadastro_revalidado: 'Cadastro revalidado', equipe_revalidada: 'Equipe revalidada',
  processo_enviado: 'Processo enviado', instrucao_validada: 'Instrução validada',
  pedido_completo: 'Pedido completo', entrega_comprovada: 'Entrega comprovada',
  contas_aceitas: 'Prestação de contas aceita', execucao_comprovada: 'Execução comprovada',
  cronograma_ativado: 'Cronograma ativado', viagem_validada: 'Viagem validada',
  os_emitida: 'Ordem de serviço emitida', entrega_validada: 'Entrega validada',
  movimento_confirmado: 'Movimento confirmado', inventario_conciliado: 'Inventário conciliado',
  bem_validado: 'Bem validado', ciclo_conciliado: 'Ciclo conciliado',
  turno_assumido: 'Turno assumido', passagem_validada: 'Passagem validada',
  missao_concluida: 'Missão concluída', entrega_aceita: 'Entrega aceita',
  camada_validada: 'Camada validada', curso_concluido: 'Curso concluído',
  turma_encerrada: 'Turma encerrada', cadastro_institucional_validado: 'Cadastro institucional validado',
  solucao_aceita: 'Solução aceita',
};

export const normalizarBusca = (valor) => String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
export const nomeModuloRanking = (modulo) => MODULOS[normalizarBusca(modulo).replace(/[^a-z]/g, '')] ?? String(modulo ?? 'Outros');
export const regraDemonstracao = (regra) => String(regra.rule_key ?? '').startsWith('demo.')
  || String(regra.familia ?? '').startsWith('demo_')
  || regra.motivo_desabilitada === 'demonstracao_homologacao';

export function tituloRegraRanking(regra) {
  const entrega = String(regra.rule_key ?? '').split('.').at(-1);
  const titulo = ENTREGAS[entrega];
  if (titulo) return titulo;
  const fallback = String(regra.familia || entrega || 'Entrega').replace(/^demo_/, '').replace(/_/g, ' ');
  return fallback.charAt(0).toUpperCase() + fallback.slice(1);
}

export function disponibilidadeRegraRanking(regra) {
  if (regraDemonstracao(regra)) return 'Exemplo de homologação para demonstrar a pontuação. Não representa uma regra operacional liberada.';
  if (regra.habilitada) return 'Regra habilitada. A pontuação depende do atendimento às condições da entrega e da vigência da regra.';
  if (regra.motivo_desabilitada === 'source_evidence_missing') return 'Aguardando comprovação da entrega no módulo de origem para liberar a pontuação.';
  return 'Regra desabilitada. Aguardando liberação para pontuar.';
}
