/** 3725 -> "1h02min05s"; 125 -> "2min05s"; 45 -> "45s". */
export function formatarDuracao(segundos) {
  if (segundos === null || segundos === undefined) return '';
  const total = Math.max(0, Math.round(segundos));
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  const dois = (n) => String(n).padStart(2, '0');

  if (h > 0) return `${h}h${dois(m)}min${dois(s)}s`;
  if (m > 0) return `${m}min${dois(s)}s`;
  return `${s}s`;
}
