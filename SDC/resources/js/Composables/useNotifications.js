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

// Consulta em voo, compartilhada. Timer, retorno da aba e reconexao do socket
// disparavam consultas independentes: bastava o servidor demorar para os tres
// se empilharem e o mesmo cliente manter varios GETs simultaneos do mesmo
// recurso. Agora quem chega no meio de uma consulta espera a que ja existe.
let consultaEmVoo = null;

// Intervalo base vigente (muda com a cadencia) e falhas consecutivas, que
// alimentam o recuo progressivo.
let intervaloBase = null;
let falhasSeguidas = 0;
let ultimaConsultaEm = 0;

// Geracao da cadeia de agendamento. ajustarCadencia() para e recria o ciclo, e
// isso pode acontecer com uma consulta ainda em voo dentro do timeout antigo:
// ao terminar, aquele callback agendaria a sua proxima passada ao lado da nova,
// e o cliente passaria a consultar em duas cadencias ao mesmo tempo. Cada
// callback so continua se a geracao que ele capturou ainda for a vigente.
let geracaoPolling = 0;

// Quantos cards o painel mostra. Vem do backend (config/notificacoes.inbox.painel_max)
// na primeira resposta; o valor abaixo e apenas o palpite ate ela chegar.
let limitePainel = 4;

const INTERVALO_POLLING_MS = 30000;

// Em modo realtime ainda existe uma consulta lenta de seguranca: se o socket cair
// sem avisar (rede dormindo, proxy encerrando conexao ociosa), o painel se corrige
// sozinho na proxima passada em vez de congelar. Custa um 304 a cada 5 minutos.
const INTERVALO_RECONCILIACAO_MS = 300000;

// Dispersao aleatoria aplicada a cada agendamento. Sem ela, os clientes que
// carregaram a pagina juntos (um turno comecando, um deploy, a volta de uma
// queda do Reverb) consultam em fase e o servidor recebe o trafego de um
// intervalo inteiro concentrado no mesmo instante, em vez de diluido.
const JITTER = 0.25;

// Recuo progressivo em falha: o intervalo dobra a cada erro consecutivo, ate o
// teto. Um servidor em dificuldade recebia a MESMA cadencia de sempre, porque
// o erro era engolido e o timer seguia igual -- carga constante justamente
// quando ele precisava de folga para se recuperar.
const BACKOFF_MAX_MS = 300000;

// Piso entre consultas disparadas por evento (voltar para a aba, reconectar).
// Alternar de aba repetidamente disparava um GET por alternancia.
const INTERVALO_MINIMO_MS = 5000;

const comJitter = (ms) => Math.round(ms * (1 + (Math.random() * 2 - 1) * JITTER));

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

    const consultar = async () => {
        if (notifications.value.length === 0) isLoading.value = true;

        try {
            const response = await window.axios.get('/notificacoes/inbox', {
                headers: etag ? { 'If-None-Match': etag } : {},
                // 304 e resposta valida de negocio, nao erro.
                validateStatus: (status) => (status >= 200 && status < 300) || status === 304,
            });

            // Consulta que chegou ao servidor zera o recuo, 304 inclusive.
            falhasSeguidas = 0;

            if (response.status === 304) return;

            etag = response.headers?.etag ?? null;
            notifications.value = response.data.items ?? [];
            unreadCount.value = response.data.unread_count ?? 0;

            if (response.data.limit) limitePainel = response.data.limit;
        } catch (e) {
            // Rede instavel nao deve limpar o que o usuario ja esta vendo -- mas
            // precisa afrouxar a cadencia: insistir na mesma frequencia contra um
            // servidor que esta recusando (503/429) so aprofunda o problema.
            falhasSeguidas += 1;
        } finally {
            ultimaConsultaEm = Date.now();
            isLoading.value = false;
        }
    };

    /**
     * Consulta o inbox no maximo uma vez por vez.
     *
     * Quem chamar enquanto houver consulta em andamento recebe a promessa da
     * que ja esta em voo, em vez de abrir outra. Timer, visibilidade e
     * reconexao podem coincidir sem que isso vire varios GETs do mesmo
     * recurso partindo do mesmo cliente.
     */
    const fetchNotifications = () => {
        if (consultaEmVoo) return consultaEmVoo;

        consultaEmVoo = consultar().finally(() => {
            consultaEmVoo = null;
        });

        return consultaEmVoo;
    };

    /**
     * Consulta respeitando um piso desde a ultima. Para gatilhos de evento
     * (voltar para a aba, socket reconectando), que o usuario pode repetir a
     * vontade e que chegam em rajada depois de uma queda.
     */
    const consultarSeVencida = () => {
        if (Date.now() - ultimaConsultaEm < INTERVALO_MINIMO_MS) return Promise.resolve();

        return fetchNotifications();
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

    /**
     * Esvazia o sino.
     *
     * ARQUIVA no servidor, nao apaga: as linhas vao para
     * notifications_archive e seguem no historico completo. O que desaparece e
     * a caixa quente, que e o que o usuario pediu.
     *
     * Optimista com rollback: a lista some na hora e volta inteira se o
     * servidor recusar. `etag = null` obriga a proxima consulta a trazer corpo
     * em vez de 304 -- sem isso o painel reexibiria o que acabou de limpar.
     */
    const clearAll = async () => {
        if (notifications.value.length === 0) return;

        const anteriores = notifications.value.slice();
        const contagemAnterior = unreadCount.value;

        notifications.value = [];
        unreadCount.value = 0;
        etag = null;

        try {
            await window.axios.post('/notificacoes/limpar');
        } catch (e) {
            notifications.value = anteriores;
            unreadCount.value = contagemAnterior;

            // Sem este log a falha era MUDA: a lista voltava inteira e o
            // usuario via "nao limpou", sem nada no console apontando o 500 do
            // servidor. Foi exatamente o que aconteceu quando o endpoint subiu
            // com o classmap velho nos workers do Octane.
            console.error('[notificacoes] falha ao limpar; lista restaurada', e);
        }
    };

    /**
     * Quanto esperar ate a proxima consulta: intervalo base, dobrado uma vez
     * por falha consecutiva ate o teto, e disperso por jitter.
     */
    const proximoIntervalo = () => {
        const recuo = Math.min(intervaloBase * 2 ** falhasSeguidas, BACKOFF_MAX_MS);

        return comJitter(recuo);
    };

    /**
     * Agenda a proxima passada DEPOIS que a atual termina.
     *
     * Era um setInterval de periodo fixo, que tem dois problemas sob carga: ele
     * dispara a proxima consulta mesmo com a anterior ainda aberta (o cliente
     * enfileira consultas contra um servidor que ja esta lento) e mantem todos
     * os clientes em fase, concentrando o trafego. Encadear timeouts apos a
     * conclusao faz a cadencia respeitar o tempo real de resposta.
     */
    const agendarProxima = (geracao) => {
        pollingHandle = setTimeout(async () => {
            // Aba em segundo plano nao consulta: ninguem esta olhando o sininho
            // e cada consulta custa o ciclo inteiro de request no servidor. O
            // ciclo continua correndo (e barato) e volta a consultar sozinho
            // quando a aba reaparece.
            if (!document.hidden) await fetchNotifications();

            // A cadencia pode ter sido trocada (ou parada) durante a consulta.
            if (geracao !== geracaoPolling) return;

            agendarProxima(geracao);
        }, proximoIntervalo());
    };

    const iniciarPolling = (intervalo = INTERVALO_POLLING_MS) => {
        if (pollingHandle) return;

        intervaloBase = intervalo;
        geracaoPolling += 1;
        agendarProxima(geracaoPolling);

        // Ao voltar para a aba, consulta na hora em vez de esperar o proximo
        // ciclo: sem isso o painel podia ficar ate um intervalo inteiro parado
        // justamente no momento em que o usuario olha para ele. Com piso, para
        // que alternar de aba repetidamente nao vire um GET por alternancia.
        visibilidadeHandler = () => {
            if (!document.hidden) consultarSeVencida();
        };
        document.addEventListener('visibilitychange', visibilidadeHandler);
    };

    const pararPolling = () => {
        if (visibilidadeHandler) {
            document.removeEventListener('visibilitychange', visibilidadeHandler);
            visibilidadeHandler = null;
        }

        // Invalida a geracao ANTES de limpar o timer: se houver consulta em voo
        // dentro do callback atual, e isto que a impede de se reagendar.
        geracaoPolling += 1;

        if (!pollingHandle) return;
        clearTimeout(pollingHandle);
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
                //
                // Com piso e nao imediato: quando o Reverb volta, TODOS os
                // clientes reconectam praticamente juntos, e uma consulta
                // imediata por cliente entrega ao servidor a onda inteira no
                // mesmo instante -- justo depois de um incidente, que e quando
                // ele tem menos folga.
                etag = null;
                ajustarCadencia(true);
                consultarSeVencida();
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
        clearAll,
        start,
        stop,
        // Aliases: o painel atual chama startPolling/stopPolling.
        startPolling: start,
        stopPolling: stop,
        formatTimeAgo,
    };
}
