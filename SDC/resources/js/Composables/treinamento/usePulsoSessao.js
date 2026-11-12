import { postSilencioso } from './postSilencioso';

const INTERVALO_PULSO_MS = 300000;
const INTERVALO_MINIMO_MS = 60000;

/**
 * Pulso de sessao do video: enquanto estiver tocando, avisa o servidor ao
 * comecar e a cada 5 minutos para a sessao nao expirar no meio da aula. Vale
 * tambem com a aba oculta (audio em segundo plano). A falha e silenciosa: o
 * pulso nunca interrompe o video nem recarrega a pagina. `obterUrl` devolve a
 * rota ou null (rota inexistente no manifesto: nada a fazer).
 */
export function usePulsoSessao(obterUrl) {
  let temporizador = null;
  let ultimoEnvio = 0;

  function enviar() {
    const agora = Date.now();
    if (agora - ultimoEnvio < INTERVALO_MINIMO_MS) return;

    if (postSilencioso(obterUrl())) {
      ultimoEnvio = agora;
    }
  }

  function parar() {
    clearInterval(temporizador);
    temporizador = null;
  }

  function iniciar() {
    parar();
    enviar();
    temporizador = setInterval(enviar, INTERVALO_PULSO_MS);
  }

  return { iniciar, parar };
}
