const ESCALA_PADRAO = 100;

/** 85 (escala 100) -> "85%"; 66.666 -> "66,67%"; 7 (escala 10) -> "70%"; null -> "-". */
export function formatarNota(valor, escala = ESCALA_PADRAO) {
  if (valor === null || valor === undefined || valor === '') return '-';

  const base = Number(escala) > 0 ? Number(escala) : ESCALA_PADRAO;
  const percentual = (Number(valor) / base) * 100;

  return `${percentual.toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`;
}
