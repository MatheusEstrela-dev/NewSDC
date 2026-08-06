import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { initEcho } from '@/bootstrap';

/**
 * Estado do inbox de notificacoes, compartilhado por todos os componentes.
 *
 * Duas formas de atualizacao, decididas pelo backend em notificacoes.update_mode:
 *
 * - realtime: assina o canal privado do usuario via Reverb. O card entra no painel
 *   no instante em que o worker termina de processar a notificacao.
 * - polling: consulta o inbox periodicamente. A resposta traz ETag, entao um ciclo
 *   sem novidade custa um 304 vazio, sem corpo e sem serializacao no servidor.
 *
 * 'auto' tenta o websocket e cai para polling sozinho se ele nao subir, o que
 * mantem o painel funcional mesmo com o Reverb fora do ar.
 */

// Estado global do modulo: o sino e o painel leem a mesma fonte.
const notifications = ref([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const modoAtivo = ref(null);

let pollingHandle = null;
let visibilidadeHandler = null;
let echoChannel = null;
let etag = null;
let assinantes = 0;

// Quantos cards o painel mostra. Vem do backend (config/notificacoes.inbox.painel_max)
// na primeira resposta; o valor abaixo e apenas o palpite ate ela chegar.
let limitePainel = 4;

const INTERVALO_POLLING_MS = 30000;

// Em modo realtime ainda existe uma consulta lenta de seguranca: se o socket cair
// sem avisar (rede dormindo, proxy encerrando conexao ociosa), o painel se corrige
// sozinho na proxima passada em vez de congelar. Custa um 304 a cada 5 minutos.
const INTERVALO_RECONCILIACAO_MS = 300000;

export function useNotifications() {
    const page = usePage();

    /**
     * O agrupamento vem resolvido do banco (group_count), entao nao ha mais logica
     * de juntar linhas aqui: basta ordenar pelo fato mais recente.
     */
    const ordenadas = computed(() =>
        [...notifications.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    );

    const hasUnread = computed(() => unreadCount.value > 0);

    const usuarioId = () => page.props?.auth?.user?.id ?? null;

    const modoConfigurado = () => page.props?.notificacoes?.update_mode ?? 'auto';

    const fetchNotifications = async () => {
        if (notifications.value.length === 0) isLoading.value = true;

        try {
            const response = await window.axios.get('/notificacoes/inbox', {
                headers: etag ? { 'If-None-Match': etag } : {},
                // 304 e resposta valida de negocio, nao erro.
                validateStatus: (status) => (status >= 200 && status < 300) || status === 304,
            });

            if (response.status === 304) return;

            etag = response.headers?.etag ?? null;
            notifications.value = response.data.items ?? [];
            unreadCount.value = response.data.unread_count ?? 0;

            if (response.data.limit) limitePainel = response.data.limit;
        } catch (e) {
            // Rede instavel nao deve limpar o que o usuario ja esta vendo.
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Insere (ou atualiza) um card vindo do websocket, sem refazer a consulta.
     */
    const aplicarNotificacaoRecebida = (payload) => {
        if (!payload?.id) return;

        const indice = notifications.value.findIndex((n) => n.id === payload.id);

        if (indice >= 0) {
            // Agrupamento: a mesma linha voltou com o contador maior.
            notifications.value.splice(indice, 1, {
                ...notifications.value[indice],
                ...payload,
                read: false,
                read_at: null,
            });
        } else {
            // Corta na mesma quantidade que a API devolveria: o painel e uma previa
            // de tamanho fixo, e sem o corte ele cresceria a cada push do socket.
            notifications.value = [
                { ...payload, read: false, read_at: null },
                ...notifications.value,
            ].slice(0, limitePainel);

            unreadCount.value += 1;
        }

        // O ETag guardado nao vale mais para a proxima consulta.
        etag = null;
    };

    const markAsRead = async (id) => {
        const alvo = notifications.value.find((n) => n.id === id);
        if (!alvo || alvo.read) return;

        // Atualizacao otimista: o clique responde na hora.
        alvo.read = true;
        alvo.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        etag = null;

        try {
            const { data } = await window.axios.post(`/notificacoes/${id}/lida`);
            unreadCount.value = data.unread_count ?? unreadCount.value;
        } catch (e) {
            // Falhou: desfazer, para o painel nao mentir sobre o estado no servidor.
            alvo.read = false;
            alvo.read_at = null;
            unreadCount.value += 1;
        }
    };

    const markGroupAsRead = async (ids) => {
        const alvos = notifications.value.filter((n) => ids.includes(n.id) && !n.read);
        if (alvos.length === 0) return;

        alvos.forEach((n) => {
            n.read = true;
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = Math.max(0, unreadCount.value - alvos.length);
        etag = null;

        try {
            const { data } = await window.axios.post('/notificacoes/lidas', { ids });
            unreadCount.value = data.unread_count ?? unreadCount.value;
        } catch (e) {
            alvos.forEach((n) => {
                n.read = false;
                n.read_at = null;
            });
            unreadCount.value += alvos.length;
        }
    };

    const markAllAsRead = async () => {
        const naoLidas = notifications.value.filter((n) => !n.read);
        if (naoLidas.length === 0) return;

        naoLidas.forEach((n) => {
            n.read = true;
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
        etag = null;

        try {
            await window.axios.post('/notificacoes/todas-lidas');
        } catch (e) {
            naoLidas.forEach((n) => {
                n.read = false;
                n.read_at = null;
            });
            unreadCount.value = naoLidas.length;
        }
    };

    const iniciarPolling = (intervalo = INTERVALO_POLLING_MS) => {
        if (pollingHandle) return;

        // Aba em segundo plano nao consulta: ninguem esta olhando o sininho e
        // cada consulta custa o ciclo inteiro de request no servidor. O
        // intervalo continua correndo (e barato) e volta a consultar sozinho
        // quando a aba reaparece.
        pollingHandle = setInterval(() => {
            if (document.hidden) return;
            fetchNotifications();
        }, intervalo);

        // Ao voltar para a aba, consulta na hora em vez de esperar o proximo
        // tick: sem isso o painel podia ficar ate um intervalo inteiro parado
        // justamente no momento em que o usuario olha para ele.
        visibilidadeHandler = () => {
            if (!document.hidden) fetchNotifications();
        };
        document.addEventListener('visibilitychange', visibilidadeHandler);
    };

    const pararPolling = () => {
        if (visibilidadeHandler) {
            document.removeEventListener('visibilitychange', visibilidadeHandler);
            visibilidadeHandler = null;
        }

        if (!pollingHandle) return;
        clearInterval(pollingHandle);
        pollingHandle = null;
    };

    /**
     * Troca a cadencia de consulta conforme o socket esta de pe ou nao.
     *
     * Com socket: consulta lenta, so como rede de seguranca.
     * Sem socket: consulta normal, que passa a ser a fonte de atualizacao.
     */
    const ajustarCadencia = (comSocket) => {
        pararPolling();
        modoAtivo.value = comSocket ? 'realtime' : 'polling';
        iniciarPolling(comSocket ? INTERVALO_RECONCILIACAO_MS : INTERVALO_POLLING_MS);
    };

    /**
     * Observa o estado da conexao do socket. Sem isso, uma queda silenciosa deixava
     * o painel congelado: o fallback para polling era decidido uma unica vez, no
     * start, e nunca reavaliado.
     */
    const vigiarConexao = (echo) => {
        const conexao = echo?.connector?.pusher?.connection;
        if (!conexao?.bind) return;

        conexao.bind('state_change', ({ current }) => {
            if (current === 'connected') {
                // Reconectou: buscar o que passou enquanto estava fora do ar.
                etag = null;
                fetchNotifications();
                ajustarCadencia(true);
                return;
            }

            if (['unavailable', 'failed', 'disconnected'].includes(current)) {
                ajustarCadencia(false);
            }
        });
    };

    const tentarRealtime = async () => {
        const id = usuarioId();
        if (!id) return false;
        if (echoChannel) return true;

        const echo = await initEcho();
        if (!echo) return false;

        try {
            // Canal padrao do Laravel para notificacoes, ja registrado em channels.php.
            echoChannel = echo.private(`App.Models.User.${id}`);
            echoChannel.notification((payload) => aplicarNotificacaoRecebida(payload));

            vigiarConexao(echo);
            ajustarCadencia(true);

            return true;
        } catch (e) {
            echoChannel = null;
            return false;
        }
    };

    /**
     * Liga o inbox. Contado por assinantes, para que dois componentes montados ao
     * mesmo tempo nao criem dois pollers nem duas assinaturas de canal.
     */
    const start = async () => {
        assinantes += 1;

        // O contador ja vem no share do Inertia: o badge acerta no primeiro paint.
        if (unreadCount.value === 0) {
            unreadCount.value = page.props?.notificacoes?.unread_count ?? 0;
        }

        if (assinantes > 1) return;

        await fetchNotifications();

        if (modoConfigurado() === 'polling') {
            ajustarCadencia(false);
            return;
        }

        // Fallback: 'auto' e 'realtime' caem para polling quando o websocket nao
        // sobe, para o painel nunca ficar mudo.
        if (!(await tentarRealtime())) ajustarCadencia(false);
    };

    const stop = () => {
        assinantes = Math.max(0, assinantes - 1);
        if (assinantes > 0) return;

        pararPolling();

        if (echoChannel) {
            // leave() em vez de stopListening: o nome do evento registrado por
            // .notification() e interno do Echo, e sair do canal libera tambem a
            // inscricao e o socket.
            const id = usuarioId();
            if (id && window.Echo) window.Echo.leave(`App.Models.User.${id}`);
            echoChannel = null;
        }

        modoAtivo.value = null;
    };

    const formatTimeAgo = (dateString) => {
        const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);

        if (diff < 60) return 'Agora';
        if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
        return `${Math.floor(diff / 86400)}d atrás`;
    };

    return {
        notifications: ordenadas,
        rawNotifications: notifications,
        isLoading,
        unreadCount,
        hasUnread,
        modoAtivo,
        fetchNotifications,
        markAsRead,
        markGroupAsRead,
        markAllAsRead,
        start,
        stop,
        // Aliases: o painel atual chama startPolling/stopPolling.
        startPolling: start,
        stopPolling: stop,
        formatTimeAgo,
    };
}
