import { postSilencioso } from './postSilencioso';

/**
 * Envio de progresso que sobrevive ao fechamento da aba (fetch keepalive).
 * Dispara e esquece: devolve false quando nao ha como enviar.
 */
export function enviarProgressoKeepalive(url, posicao) {
  return postSilencioso(url, { posicao }, { keepalive: true });
}
