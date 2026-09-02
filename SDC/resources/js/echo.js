import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
 * Inicializa o Echo SOMENTE se houver configuracao.
 *
 * Sem a chave, window.Echo fica undefined e o useAtualizacaoAoVivo nao faz nada.
 * E isso que transforma o tempo real num feature flag: com
 * BROADCAST_CONNECTION=null, que foi o padrao deste projeto por meses, as
 * paginas funcionam exatamente como funcionavam.
 *
 * Importa porque esse caminho nunca transmitiu em producao: se algo der errado
 * la, desligar o broadcasting devolve o comportamento anterior sem reverter
 * codigo.
 *
 * As VITE_* sao lidas em BUILD TIME. Mudar host ou porta exige rebuild do
 * frontend, nao apenas restart de container -- e o tipo de coisa que vira uma
 * hora de diagnostico quando ninguem avisou.
 */
const chave = import.meta.env.VITE_REVERB_APP_KEY;

if (chave) {
    window.Pusher = Pusher;

    const porta = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
    const esquema = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: chave,
        // O NAVEGADOR alcanca o Reverb por localhost; o servidor, pelo hostname
        // de rede. Sao valores diferentes para a mesma coisa, e por isso o host
        // vem de uma VITE_ propria em vez de reaproveitar REVERB_HOST.
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: porta,
        wssPort: porta,
        forceTLS: esquema === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
