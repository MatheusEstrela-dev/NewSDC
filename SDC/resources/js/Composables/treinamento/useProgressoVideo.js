/**
 * Progresso do video por tempo: so envia quando o aluno avanca alem do
 * ponto mais distante ja enviado; ao terminar, envia a duracao total.
 */
export function useProgressoVideo({ enviar, duracao, posicaoInicial = 0 }) {
  let ultimaEnviada = Math.max(0, Math.floor(posicaoInicial));

  const registrar = (segundos) => {
    ultimaEnviada = segundos;

    return enviar(segundos);
  };

  return {
    aoPosicao(segundos) {
      const valor = Math.floor(segundos);
      if (!Number.isFinite(valor) || valor <= ultimaEnviada) return;

      return registrar(valor);
    },
    aoTerminar() {
      const total = Math.floor(duracao);
      if (!Number.isFinite(total) || total <= 0) return;

      return registrar(total);
    },
  };
}
