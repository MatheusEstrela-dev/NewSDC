const ROTAS = {
  aluno: {
    arquivo: 'treinamentos.aluno.itens.arquivo',
    progresso: 'treinamentos.aluno.itens.progresso',
    entrega: 'treinamentos.aluno.itens.entregas',
    pulso: 'treinamentos.sessao.pulso',
    fechar: ['treinamentos.aluno.index', false],
  },
  portal: {
    arquivo: 'portal.treinamento.cursos.itens.arquivo',
    progresso: 'portal.treinamento.cursos.itens.progresso',
    entrega: 'portal.treinamento.cursos.itens.entregas',
    pulso: 'portal.treinamento.sessao.pulso',
    fechar: ['portal.treinamento.inscricoes.index', false],
  },
  previa: {
    arquivo: 'treinamentos.professor.itens.arquivo',
    progresso: null,
    entrega: null,
    pulso: 'treinamentos.sessao.pulso',
    fechar: ['treinamentos.professor.conteudo', true],
  },
};

/**
 * Rotas da sala de aula por escopo. A chave e o id do curso (aluno e previa)
 * ou o slug (portal). `fechar` leva de volta a lista de cursos. Na previa nao ha progresso nem entrega: devolvem null.
 */
export function useRotasSala(escopo, chave) {
  const nomes = ROTAS[escopo];

  const montar = (nome, itemId) => (nome ? route(nome, [chave, itemId]) : null);

  return {
    arquivo: (itemId, download = false) => {
      const url = montar(nomes.arquivo, itemId);

      return download ? `${url}?download=1` : url;
    },
    progresso: (itemId) => montar(nomes.progresso, itemId),
    entrega: (itemId) => montar(nomes.entrega, itemId),
    // A rota do pulso pode nao estar no manifesto Ziggy ainda: sem ela, null.
    pulso: () => (nomes.pulso && route().has(nomes.pulso) ? route(nomes.pulso) : null),
    fechar: () => (nomes.fechar[1] ? route(nomes.fechar[0], chave) : route(nomes.fechar[0])),
  };
}
