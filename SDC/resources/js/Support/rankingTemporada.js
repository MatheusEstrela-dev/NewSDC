// Temporada do ranking = trimestre civil. Chave do backend: 'trimestre:2026-T3'
// (TipoPeriodo::Trimestre). Rotulos e datas ficam aqui para a pagina e os
// componentes nao repetirem a mesma conta.

const MESES_DO_TRIMESTRE = { 1: 'jan–mar', 2: 'abr–jun', 3: 'jul–set', 4: 'out–dez' };

/** 'trimestre:2026-T3' -> { ano: 2026, trimestre: 3 }; outra chave -> null. */
export function partesTrimestre(chave) {
  const casou = String(chave ?? '').match(/^trimestre:(\d{4})-T([1-4])$/);
  return casou ? { ano: Number(casou[1]), trimestre: Number(casou[2]) } : null;
}

/** Chave do trimestre de uma data local. */
export function chaveTrimestre(data = new Date()) {
  return `trimestre:${data.getFullYear()}-T${Math.floor(data.getMonth() / 3) + 1}`;
}

/** '3º trimestre de 2026'; com `comMeses`, acrescenta '(jul–set)'. */
export function rotuloTrimestre(chave, { comMeses = false } = {}) {
  const partes = partesTrimestre(chave);
  if (!partes) return '';
  const base = `${partes.trimestre}º trimestre de ${partes.ano}`;
  return comMeses ? `${base} (${MESES_DO_TRIMESTRE[partes.trimestre]})` : base;
}

/** Ultimos `quantos` trimestres a partir do atual, do mais recente ao mais antigo. */
export function trimestresRecentes(quantos = 8, referencia = new Date()) {
  const chaves = [];
  let ano = referencia.getFullYear();
  let trimestre = Math.floor(referencia.getMonth() / 3) + 1;
  for (let i = 0; i < quantos; i += 1) {
    chaves.push(`trimestre:${ano}-T${trimestre}`);
    trimestre -= 1;
    if (trimestre === 0) {
      trimestre = 4;
      ano -= 1;
    }
  }
  return chaves;
}

/** 'AAAA-MM-DD' -> 'DD/MM'. */
export function diaMes(isoData) {
  const partes = String(isoData ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
  return partes ? `${partes[3]}/${partes[2]}` : '';
}
