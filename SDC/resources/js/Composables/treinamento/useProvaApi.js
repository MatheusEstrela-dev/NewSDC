/**
 * Chamadas JSON das provas na area do professor (rotas `treinamentos.professor.provas*`).
 * Respostas de mutacao sao JSON, nao redirect: o editor de provas nao navega.
 */
export function mensagensDeErro(erro, padrao = 'Nao foi possivel concluir a acao.') {
  const dados = erro?.response?.data;
  const lista = dados?.errors ? Object.values(dados.errors).flat() : [];

  if (lista.length > 0) return [...new Set(lista)];

  return [dados?.message || padrao];
}

export function useProvaApi(treinamentoId) {
  const rota = (nome, ...params) => route(`treinamentos.professor.${nome}`, [treinamentoId, ...params]);
  const dados = (resposta) => resposta.data;

  return {
    carregar: (provaId) => window.axios.get(rota('provas.show', provaId)).then(dados),
    configurar: (provaId, corpo) => window.axios.put(rota('provas.update', provaId), corpo).then(dados),
    criarQuestao: (provaId, corpo) => window.axios.post(rota('provas.questoes.store', provaId), corpo).then(dados),
    atualizarQuestao: (provaId, questaoId, corpo) => window.axios.put(rota('provas.questoes.update', provaId, questaoId), corpo).then(dados),
    excluirQuestao: (provaId, questaoId) => window.axios.delete(rota('provas.questoes.destroy', provaId, questaoId)),
    ordenarQuestoes: (provaId, ids) => window.axios.post(rota('provas.questoes.ordem', provaId), { ids }),
    liberarTentativa: (provaId, corpo) => window.axios.post(rota('provas.liberacoes.store', provaId), corpo).then(dados),
  };
}

/**
 * O backend devolve a trava de publicacao como um texto unico: "O curso nao pode
 * ser publicado. " + uma frase por pendencia. Quebra em itens para a tela.
 */
export function separarPendencias(texto) {
  if (!texto) return [];

  return texto
    .replace(/^O curso nao pode ser publicado\.\s*/, '')
    .split(/(?<=\.)\s+(?=A pasta |Pasta )/)
    .map((p) => p.trim())
    .filter(Boolean);
}
