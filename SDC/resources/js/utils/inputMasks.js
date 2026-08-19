import { applyCpfMask } from './cpfMask';
import { applyPhoneMask } from './phoneMask';

/**
 * Mascaras nomeadas para o prop `mask` do FormField.
 *
 * As regras NAO sao arbitrarias: cada uma existe para casar com o que o backend
 * normaliza em NormalizaEntrada, e errar aqui corrompe numero em silencio.
 *
 *   - NormalizaEntrada::moeda   remove 'R$', espacos e PONTOS, e troca virgula
 *                               por ponto. Entao "1500.50" chegaria como 150050:
 *                               o ponto some e o valor fica 100x maior. Por isso
 *                               `moeda` converte ponto em virgula.
 *   - NormalizaEntrada::decimal so troca virgula por ponto, e nao remove nada.
 *                               Entao "12.5" e "12,5" chegam iguais, mas
 *                               "1.234,56" viraria "1.234.56" e nao e numerico.
 *                               Por isso `decimal` aceita UM separador so, e nao
 *                               inventa separador de milhar.
 *   - Coordenada precisa do sinal: Minas Gerais e toda latitude/longitude
 *                               negativa. Mascara de "somente digitos" tornaria
 *                               o campo impossivel de preencher.
 */

/** Digitos e nada mais. Para contagem: pessoas, caidas de telhado, ordem. */
export function apenasDigitos(valor) {
  return String(valor ?? '').replace(/\D/g, '');
}

/**
 * Numero decimal com UM separador, virgula ou ponto, preservando o que a pessoa
 * escolheu digitar -- o backend aceita os dois.
 */
export function decimalSimples(valor) {
  const texto = String(valor ?? '').replace(/[^\d.,]/g, '');
  const primeiro = texto.search(/[.,]/);

  if (primeiro === -1) {
    return texto;
  }

  const separador = texto[primeiro];
  const inteiro = texto.slice(0, primeiro);
  const fracao = texto.slice(primeiro + 1).replace(/[.,]/g, '');

  return `${inteiro}${separador}${fracao}`;
}

/**
 * Decimal que admite sinal negativo.
 *
 * O sinal e avaliado DEPOIS de tirar o que nao e numero: colar "lat: -15,7" tem
 * o '-' no meio do texto bruto, e testar o inicio da string crua perderia o
 * sinal. Latitude positiva em Minas nao da erro de validacao -- a faixa mundial
 * aceita -- ela so joga o ponto no hemisferio norte, calada.
 */
export function coordenada(valor) {
  const bruto = String(valor ?? '').replace(/[^\d.,-]/g, '');

  return (bruto.startsWith('-') ? '-' : '') + decimalSimples(bruto.replace(/-/g, ''));
}

/**
 * Valor monetario com virgula decimal e no maximo 2 casas.
 *
 * Ponto vira virgula de proposito: quem digita "1500.50" quer mil e quinhentos
 * reais e cinquenta centavos, e sem esta troca o backend entregaria 150050.
 */
export function moeda(valor) {
  const texto = String(valor ?? '').replace(/\./g, ',').replace(/[^\d,]/g, '');
  const primeiro = texto.indexOf(',');

  if (primeiro === -1) {
    return texto;
  }

  const centavos = texto.slice(primeiro + 1).replace(/,/g, '').slice(0, 2);

  return `${texto.slice(0, primeiro)},${centavos}`;
}

/** Data no formato dd/mm/aaaa, inserindo as barras conforme se digita. */
export function dataBr(valor) {
  const d = apenasDigitos(valor).slice(0, 8);

  if (d.length <= 2) return d;
  if (d.length <= 4) return `${d.slice(0, 2)}/${d.slice(2)}`;

  return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`;
}

/**
 * Converte dd/mm/aaaa em aaaa-mm-dd, ou null se a data nao existir.
 *
 * A conferencia de volta (getDate/getMonth) e o que barra 31/02: o Date do
 * JavaScript nao recusa dia invalido, ele TRANSBORDA para 03/03 calado.
 */
export function isoDeDataBr(texto) {
  const par = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(texto ?? '').trim());

  if (! par) return null;

  const [, dia, mes, ano] = par.map(Number);
  const data = new Date(ano, mes - 1, dia);

  if (data.getFullYear() !== ano || data.getMonth() !== mes - 1 || data.getDate() !== dia) {
    return null;
  }

  return `${par[3]}-${par[2]}-${par[1]}`;
}

/**
 * Registro consultado pelo FormField. Nome desconhecido devolve o valor
 * intacto, para um erro de digitacao no prop nao virar campo que apaga o que a
 * pessoa escreve.
 */
export const MASCARAS = {
  cpf: applyCpfMask,
  telefone: applyPhoneMask,
  inteiro: apenasDigitos,
  decimal: decimalSimples,
  coordenada,
  moeda,
  data: dataBr,
};

export function aplicarMascara(nome, valor) {
  const mascara = MASCARAS[nome];

  return mascara ? mascara(valor) : valor;
}
