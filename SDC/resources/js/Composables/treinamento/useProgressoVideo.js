/**
 * Progresso do vídeo por tempo. O item é sempre resolvido pelo id do evento
 * no momento do envio, nunca capturado: só envia quando o aluno passa do ponto
 * mais distante já enviado daquele item; ao terminar, envia a duração total.
 */
export function useProgressoVideo({ resolverItem, enviar }) {
  const enviadas = new Map();

  const itemDeVideo = (itemId) => {
    const item = resolverItem(itemId);

    return item?.tipo === 'VIDEO' ? item : null;
  };

  const ultimaEnviada = (item) => Math.max(enviadas.get(item.id) ?? 0, Math.floor(item.posicao ?? 0));

  const registrar = (item, segundos) => {
    enviadas.set(item.id, segundos);

    return enviar(item, segundos);
  };

  return {
    aoPosicao(segundos, itemId) {
      const item = itemDeVideo(itemId);
      const valor = Math.floor(segundos);
      if (!item || !Number.isFinite(valor) || valor <= ultimaEnviada(item)) return;

      return registrar(item, valor);
    },
    aoTerminar(segundosDoPlayer, itemId) {
      const item = itemDeVideo(itemId);
      if (!item) return;

      const total = Math.floor(item.video?.duracao_segundos || segundosDoPlayer);
      if (!Number.isFinite(total) || total <= 0) return;

      return registrar(item, total);
    },
  };
}
