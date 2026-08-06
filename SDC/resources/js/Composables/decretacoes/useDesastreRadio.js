/**
 * Convencao dos campos radio de danos (Sim/Nao).
 *
 * Cada resposta e um campo proprio em `dec_desastre_item_campos` (um "Sim" e um
 * "Nao" por item) e o banco guarda '1' no campo marcado e '0' no irmao. Sem
 * gravar o '0' no irmao, marcar "Sim" deixava os dois preenchidos e o item
 * voltava do banco com Sim e Nao ao mesmo tempo.
 *
 * O irmao vai como '0' (e nao null): o DesastreDataService ignora campos com
 * valor null, logo um null nunca limparia o registro que ja existe no banco.
 */
export const MARCADO = '1';
export const NAO_MARCADO = '0';

/**
 * Grava o valor de um campo, aplicando exclusividade quando o campo e radio.
 *
 * Muta o array `items` recebido (que ja e uma copia local do componente).
 *
 * @param {Array} items Itens do bloco de danos
 * @param {number} iIndex Indice do item
 * @param {number} fIndex Indice do campo dentro do item
 * @param {*} valor Valor novo do campo
 */
export function aplicaValorDoCampo(items, iIndex, fIndex, valor) {
  const item = items?.[iIndex];
  const campo = item?.campos?.[fIndex];

  if (!campo) {
    return;
  }

  campo.valor = valor;

  if (campo.tipo !== 'radio') {
    return;
  }

  item.campos.forEach((outro, indice) => {
    if (indice !== fIndex && outro.tipo === 'radio') {
      outro.valor = NAO_MARCADO;
    }
  });
}
