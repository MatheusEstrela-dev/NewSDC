const ROTAS = {
  aluno: {
    arquivo: 'treinamentos.aluno.itens.arquivo',
    progresso: 'treinamentos.aluno.itens.progresso',
    entrega: 'treinamentos.aluno.itens.entregas',
    fechar: ['treinamentos.aluno.index', false],
  },
  portal: {
    arquivo: 'portal.treinamento.cursos.itens.arquivo',
    progresso: 'portal.treinamento.cursos.itens.progresso',
    entrega: 'portal.treinamento.cursos.itens.entregas',
    fechar: ['portal.treinamento.inscricoes.index', false],
  },
  previa: {
    arquivo: 'treinamentos.professor.itens.arquivo',
    progresso: null,
    entrega: null,
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
    fechar: () => (nomes.fechar[1] ? route(nomes.fechar[0], chave) : route(nomes.fechar[0])),
  };
}
