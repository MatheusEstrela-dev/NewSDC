import { computed, ref } from 'vue';

/**
 * Inscricao do navegador no Web Push.
 *
 * Duas coisas diferentes moram aqui e nao devem ser confundidas:
 *  - a PREFERENCIA canal_push ("eu quero push"), que e por usuario e vive em
 *    useNotificationPreferences;
 *  - a INSCRICAO deste navegador ("entregue aqui"), que e por dispositivo e e o
 *    assunto deste arquivo.
 *
 * Querer push sem nenhum dispositivo autorizado nao entrega nada, e por isso a
 * tela mostra a lista de dispositivos junto do checkbox.
 *
 * O ressincronizar() nao e zelo excessivo. app.js e bootstrap.js chamam
 * unregister() em TODOS os service workers na recuperacao de build velho e no
 * 419; o navegador volta com um endpoint novo e o antigo morre no banco. Sem
 * reenviar o endpoint atual a cada boot, o push pararia de funcionar depois do
 * primeiro 419 sem nenhum sinal para o usuario.
 */

const ENDPOINT_BASE = '/notificacoes/push';

const dispositivos = ref([]);
const permissao = ref(typeof Notification !== 'undefined' ? Notification.permission : 'default');
const inscrevendo = ref(false);
const erro = ref(null);

// Este navegador esta inscrito? Guardado a parte de `dispositivos` porque a lista
// e do usuario (todos os aparelhos) e isto e sobre a maquina em uso.
const inscritoAqui = ref(false);

const suportado = () =>
    typeof window !== 'undefined'
    && 'serviceWorker' in navigator
    && 'PushManager' in window
    && typeof Notification !== 'undefined';

/**
 * A chave publica VAPID viaja como base64url e o PushManager exige Uint8Array.
 */
function chaveParaBytes(base64UrlKey) {
    const padding = '='.repeat((4 - (base64UrlKey.length % 4)) % 4);
    const base64 = (base64UrlKey + padding).replace(/-/g, '+').replace(/_/g, '/');
    const bruto = window.atob(base64);

    return Uint8Array.from([...bruto].map((c) => c.charCodeAt(0)));
}

/**
 * Registro de service worker que pode receber push, ou null.
 *
 * NAO usa navigator.serviceWorker.ready. O worker do PWA e servido de
 * /build/sw.js e por isso so consegue reivindicar o escopo /build/ -- reivindicar
 * '/' exigiria o header Service-Worker-Allowed, que o servidor nao manda. Como
 * `ready` so resolve para um registro que cobre a pagina atual, em /plantao/... ou
 * qualquer rota fora de /build/ ele fica pendente PARA SEMPRE, sem rejeitar. Era
 * o que prendia o botao em "Ativando..." indefinidamente.
 *
 * Push nao depende do escopo cobrir a pagina: a inscricao pertence ao registro, e
 * um worker em /build/ recebe e exibe o aviso normalmente.
 */
async function registroAtivo() {
    const comTimeout = (promessa) =>
        Promise.race([
            promessa,
            new Promise((resolve) => setTimeout(() => resolve(null), 5000)),
        ]);

    // Preferido: o registro que controla esta pagina, quando existir.
    const doEscopo = await comTimeout(navigator.serviceWorker.getRegistration().catch(() => null));
    if (doEscopo) return doEscopo;

    // Senao, qualquer registro do site serve para receber push.
    const todos = await comTimeout(navigator.serviceWorker.getRegistrations().catch(() => []));

    return (todos ?? []).find((registro) => registro.active) ?? null;
}

async function enviarInscricao(subscription) {
    const bruto = subscription.toJSON();

    const { data } = await window.axios.post(`${ENDPOINT_BASE}/inscrever`, {
        endpoint: bruto.endpoint,
        keys: bruto.keys,
    });

    dispositivos.value = data.dispositivos ?? [];
    inscritoAqui.value = true;
}

/**
 * Pede permissao e inscreve este navegador. Devolve true so quando o backend
 * confirmou: a tela nao deve marcar o canal com base na permissao do navegador,
 * que e apenas metade do caminho.
 */
async function ativar(vapidPublicKey) {
    if (!suportado()) {
        erro.value = 'Este navegador não suporta notificações push.';
        return false;
    }

    if (!vapidPublicKey) {
        erro.value = 'Push não está configurado neste servidor.';
        return false;
    }

    inscrevendo.value = true;
    erro.value = null;

    try {
        permissao.value = await Notification.requestPermission();

        if (permissao.value !== 'granted') {
            erro.value = permissao.value === 'denied'
                ? 'Você bloqueou as notificações. Libere nas configurações do navegador.'
                : 'Permissão não concedida.';
            return false;
        }

        const registro = await registroAtivo();

        if (!registro) {
            erro.value = 'O serviço de notificações ainda não está pronto. Recarregue a página e tente de novo.';
            return false;
        }

        // userVisibleOnly e obrigatorio no Chrome: todo push tem de virar aviso
        // visivel. Nao existe push silencioso aqui, o que e desejavel.
        const subscription = await registro.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: chaveParaBytes(vapidPublicKey),
        });

        await enviarInscricao(subscription);

        return true;
    } catch (e) {
        erro.value = 'Não foi possível ativar as notificações neste dispositivo.';
        return false;
    } finally {
        inscrevendo.value = false;
    }
}

/**
 * Remove este navegador. O canal continua ligado para os outros dispositivos.
 */
async function desativar() {
    if (!suportado()) return false;

    inscrevendo.value = true;
    erro.value = null;

    try {
        const registro = await registroAtivo();
        const subscription = registro ? await registro.pushManager.getSubscription() : null;

        if (subscription) {
            const { endpoint } = subscription.toJSON();
            await subscription.unsubscribe();
            const { data } = await window.axios.delete(`${ENDPOINT_BASE}/inscrever`, {
                data: { endpoint },
            });
            dispositivos.value = data.dispositivos ?? [];
        }

        inscritoAqui.value = false;
        return true;
    } catch (e) {
        erro.value = 'Não foi possível remover este dispositivo.';
        return false;
    } finally {
        inscrevendo.value = false;
    }
}

/**
 * Remove um dispositivo da lista pelo id (outro navegador, outra maquina).
 */
async function removerDispositivo(id) {
    try {
        const { data } = await window.axios.delete(`${ENDPOINT_BASE}/inscrever`, { data: { id } });
        dispositivos.value = data.dispositivos ?? [];
        return true;
    } catch (e) {
        erro.value = 'Não foi possível remover o dispositivo.';
        return false;
    }
}

/**
 * Reenvia ao backend o endpoint que este navegador tem agora, se houver.
 *
 * Chamado no boot. Cobre o caso do service worker recriado apos um 419 ou uma
 * recuperacao de build: a inscricao existe no navegador, mas com endpoint que o
 * banco nao conhece.
 */
async function ressincronizar() {
    if (!suportado() || Notification.permission !== 'granted') {
        inscritoAqui.value = false;
        return;
    }

    try {
        const registro = await registroAtivo();
        const subscription = registro ? await registro.pushManager.getSubscription() : null;

        if (!subscription) {
            inscritoAqui.value = false;
            return;
        }

        await enviarInscricao(subscription);
    } catch (e) {
        // Silencioso: e manutencao de fundo, nao uma acao que o usuario pediu.
    }
}

async function carregarDispositivos() {
    try {
        const { data } = await window.axios.get(`${ENDPOINT_BASE}/dispositivos`);
        dispositivos.value = data.dispositivos ?? [];
    } catch (e) {
        // mantem a lista anterior
    }
}

export function useWebPush() {
    return {
        dispositivos,
        permissao,
        inscrevendo,
        erro,
        inscritoAqui,
        suportado: computed(() => suportado()),
        bloqueado: computed(() => permissao.value === 'denied'),
        ativar,
        desativar,
        removerDispositivo,
        ressincronizar,
        carregarDispositivos,
    };
}

export default useWebPush;
