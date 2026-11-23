/**
 * Leitura minima de cada pagina nova do PDF na sala de aula, nos materiais em
 * que o professor marcou a trava (item.exige_tempo_leitura). Fonte unica do
 * numero: a sala trava o avanco com ele e o Conteudo mostra o mesmo valor ao
 * professor.
 */
export const SEGUNDOS_POR_PAGINA_PDF = 60;

export const TEMPO_POR_PAGINA_PDF_ROTULO = SEGUNDOS_POR_PAGINA_PDF % 60 === 0
  ? `${SEGUNDOS_POR_PAGINA_PDF / 60} min por página`
  : `${SEGUNDOS_POR_PAGINA_PDF} s por página`;
