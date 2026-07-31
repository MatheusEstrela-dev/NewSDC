/**
 * Regra de vigencia do decreto municipal no front-end.
 *
 * Espelha App\Modules\Decretacoes\Support\Vigencia (backend):
 *   data_vencimento = data_publicacao + prazo_vigencia (dias)
 *   prazo_vigencia  = informado ou PRAZO_PADRAO_DIAS (180)
 *   dias_restantes  = data_vencimento - hoje  (negativo = vencido, 0 = vence hoje)
 *
 * Datas no formato YYYY-MM-DD sao interpretadas no fuso local (e nao em UTC,
 * como faz `new Date('2026-01-01')`), evitando o erro de um dia no calculo.
 */

/** Prazo padrao de vigencia de SE/ECP. */
export const PRAZO_PADRAO_DIAS = 180;

/** Janela (dias) que classifica o decreto como proximo ao vencimento. */
export const JANELA_PROXIMO_VENCER_DIAS = 30;

/** Prazos usuais oferecidos como atalho no formulario. */
export const PRAZOS_USUAIS = [30, 60, 90, 180, 365];

const MS_POR_DIA = 24 * 60 * 60 * 1000;

/**
 * Converte 'YYYY-MM-DD' (ou Date/ISO) em Date local a meia-noite.
 * @param {string|Date|null} valor
 * @returns {Date|null}
 */
export function parseDataLocal(valor) {
  if (!valor) return null;

  if (valor instanceof Date) {
    return Number.isNaN(valor.getTime())
      ? null
      : new Date(valor.getFullYear(), valor.getMonth(), valor.getDate());
  }

  const texto = String(valor).trim();
  if (!texto) return null;

  const iso = texto.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (iso) {
    return new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]));
  }

  const br = texto.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
  if (br) {
    return new Date(Number(br[3]), Number(br[2]) - 1, Number(br[1]));
  }

  const d = new Date(texto);
  return Number.isNaN(d.getTime()) ? null : new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

/** Hoje a meia-noite (local). */
export function hojeLocal() {
  const agora = new Date();
  return new Date(agora.getFullYear(), agora.getMonth(), agora.getDate());
}

/**
 * Prazo efetivo em dias (180 quando ausente/invalido).
 * @param {number|string|null} prazo
 * @returns {number}
 */
export function prazoEfetivo(prazo) {
  const dias = Number.parseInt(prazo, 10);
  return Number.isFinite(dias) && dias > 0 ? dias : PRAZO_PADRAO_DIAS;
}

/** Indica que o prazo nao foi informado e assumiu o padrao de 180 dias. */
export function usouPrazoPadrao(prazo) {
  const dias = Number.parseInt(prazo, 10);
  return !(Number.isFinite(dias) && dias > 0);
}

/**
 * Data de vencimento = publicacao + prazo efetivo.
 * @returns {Date|null} null sem data de publicacao
 */
export function calcularVencimento(dataPublicacao, prazo) {
  const inicio = parseDataLocal(dataPublicacao);
  if (!inicio) return null;

  const vencimento = new Date(inicio.getTime());
  vencimento.setDate(vencimento.getDate() + prazoEfetivo(prazo));
  return vencimento;
}

/**
 * Dias restantes assinados.
 * @returns {number|null} negativo = vencido, 0 = vence hoje, null = sem vigencia
 */
export function calcularDiasRestantes(dataPublicacao, prazo, hoje = null) {
  const vencimento = calcularVencimento(dataPublicacao, prazo);
  if (!vencimento) return null;

  const base = hoje ? parseDataLocal(hoje) : hojeLocal();
  return Math.round((vencimento.getTime() - base.getTime()) / MS_POR_DIA);
}

/** Formata uma data (Date|string) em dd/mm/aaaa; string vazia quando nula. */
export function formatarData(valor) {
  const data = parseDataLocal(valor);
  return data ? data.toLocaleDateString('pt-BR') : '';
}

/** Formata como YYYY-MM-DD (contrato dos inputs de data). */
export function formatarDataIso(valor) {
  const data = parseDataLocal(valor);
  if (!data) return '';

  const mes = String(data.getMonth() + 1).padStart(2, '0');
  const dia = String(data.getDate()).padStart(2, '0');
  return `${data.getFullYear()}-${mes}-${dia}`;
}

/**
 * Rotulo curto do prazo restante ("Vence hoje", "12 dias", "Vencido ha 3 dias").
 * @param {number|null} dias
 */
export function rotuloDiasRestantes(dias) {
  if (dias === null || dias === undefined) return '—';
  if (dias < 0) {
    const atraso = Math.abs(dias);
    return atraso === 1 ? 'Vencido ha 1 dia' : `Vencido ha ${atraso} dias`;
  }
  if (dias === 0) return 'Vence hoje';
  if (dias === 1) return '1 dia';
  if (dias <= JANELA_PROXIMO_VENCER_DIAS) return `${dias} dias`;

  const meses = Math.floor(dias / 30);
  return meses === 1 ? '1 mes' : `${meses} meses`;
}

/**
 * Classificacao da situacao de vigencia, para cores/badges.
 * @returns {'sem_vigencia'|'vencido'|'critico'|'alerta'|'vigente'}
 */
export function situacaoVigencia(dias) {
  if (dias === null || dias === undefined) return 'sem_vigencia';
  if (dias < 0) return 'vencido';
  if (dias <= 15) return 'critico';
  if (dias <= JANELA_PROXIMO_VENCER_DIAS) return 'alerta';
  return 'vigente';
}
