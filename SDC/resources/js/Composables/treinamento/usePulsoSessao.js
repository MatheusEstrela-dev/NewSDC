import axios from 'axios';

const INTERVALO_PULSO_MS = 300000;

/**
 * Pulso de sessao do video: enquanto estiver tocando, avisa o servidor a cada
 * 5 minutos para a sessao nao expirar no meio da aula. Aba oculta nao envia e
 * a falha e silenciosa: o pulso nunca interrompe o video. `obterUrl` devolve a
 * rota ou null (rota inexistente no manifesto: nada a fazer).
 */
export function usePulsoSessao(obterUrl) {
  let temporizador = null;

  async function enviar() {
    if (document.visibilityState === 'hidden') return;

    const url = obterUrl();
    if (!url) return;

    try {
      await axios.post(url);
    } catch {
      // Falha do pulso e ignorada de proposito.
    }
  }

  function parar() {
    clearInterval(temporizador);
    temporizador = null;
  }

  function iniciar() {
    parar();
    temporizador = setInterval(enviar, INTERVALO_PULSO_MS);
  }

  return { iniciar, parar };
}
