const hoje = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const CABECALHO = [
  'Município', 'REDEC', 'Mesorregião', 'Capacitação', 'Ano', 'Início', 'Fim', 'Participantes',
  'Concluídos', 'Em andamento', 'Desistentes', 'Masculino', 'Feminino', 'Outros / não informado',
];

/**
 * Quem esta no total e nao e F nem M: nao-binario, outro e nao informado (a
 * linha da tabela so separa masculino e feminino). Nunca negativo.
 */
export const naoInformado = (linha) => Math.max(0, (linha.participantes ?? 0) - (linha.masculino ?? 0) - (linha.feminino ?? 0));

/**
 * Baixa a tabela detalhada completa (relatorio.linhas) em CSV. BOM UTF-8 e ";"
 * para o Excel abrir com acento, como os demais exports do sistema.
 */
export function baixarIndicadoresCsv(linhas) {
  const corpo = linhas.map((l) => [
    l.municipio ?? 'EAD / sem município', l.redec ?? '', l.mesorregiao ?? '', l.treinamento, l.ano,
    l.periodo_inicio ?? '', l.periodo_fim ?? '', l.participantes,
    l.concluidos ?? 0, l.em_andamento ?? 0, l.desistentes ?? 0,
    l.masculino, l.feminino, naoInformado(l),
  ]);

  const csv = [CABECALHO, ...corpo]
    .map((l) => l.map((v) => `"${String(v ?? '').replace(/"/g, '""')}"`).join(';'))
    .join('\r\n');

  const url = URL.createObjectURL(new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8' }));
  const a = Object.assign(document.createElement('a'), { href: url, download: `capacitacoes_indicadores_${hoje()}.csv` });
  a.click();
  URL.revokeObjectURL(url);
}
