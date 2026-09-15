import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { initEcho } from '@/bootstrap';

// Cadencia normal, quando a consulta e a unica fonte de atualizacao.
const INTERVALO_POLLING_MS = 60_000;

// Em realtime a consulta continua, so mais lenta: se o socket cair sem avisar, o
// feed se corrige na proxima passada em vez de congelar. Mesma disciplina do
// useNotifications.
const INTERVALO_RECONCILIACAO_MS = 300_000;

export function useActivityFeed() {
    const items = ref([]);
    const isLoading = ref(false);
    const updateMode = ref('polling');
    let pollInterval = null;
    let echo = null;
    let echoChannel = null;
    let desmontado = false;

    const page = usePage();

    async function fetchFeed() {
        isLoading.value = true;
        try {
            const response = await window.axios.get('/api/v1/activity-feed');
            items.value = Array.isArray(response.data.items) ? response.data.items : [];
            updateMode.value = response.data.update_mode ?? 'polling';
        } catch (e) {
            items.value = [];
        } finally {
            isLoading.value = false;
        }
    }

    function iniciarPolling(intervalo) {
        if (pollInterval) return;

        pollInterval = setInterval(() => {
            // Aba em segundo plano nao consulta: ninguem esta olhando e cada
            // passada custa o ciclo inteiro de request no servidor.
            if (document.hidden) return;
            fetchFeed();
        }, intervalo);
    }

    /**
     * Assina o canal do usuario, se houver websocket.
     *
     * A conexao vem do initEcho, que e o unico ponto que cria Echo na aplicacao.
     * Antes esta funcao lia window.Echo de forma sincrona no mount, o que so
     * funcionava porque um echo.js eager criava a instancia no boot -- ligar ou
     * desligar o realtime aqui dependia da ORDEM em que os componentes montavam.
     *
     * O polling NAO e desligado quando o socket sobe. Sem essa rede, um realtime
     * que nao entrega evento nenhum deixa o feed congelado sem sinal algum: e o
     * que acontece hoje, porque nao existe evento UserActivityEvent transmitindo
     * e o canal `user.{id}` nao esta declarado em routes/channels.php. Enquanto
     * essas duas pecas nao existirem, a consulta lenta e o que mantem o feed vivo.
     */
    async function tentarRealtime(userId) {
        const instancia = await initEcho();

        if (!instancia || desmontado) return false;

        try {
            echo = instancia;
            echoChannel = echo.private(`user.${userId}`)
                .listen('UserActivityEvent', (event) => {
                    if (event.item) {
                        items.value = [event.item, ...items.value].slice(0, 7);
                    }
                });

            return true;
        } catch (e) {
            echo = null;
            echoChannel = null;

            return false;
        }
    }

    function pararRealtime() {
        if (echoChannel) {
            echoChannel.stopListening('UserActivityEvent');
            echoChannel = null;
        }

        if (echo) {
            echo.leave(`user.${page.props?.auth?.user?.id}`);
            echo = null;
        }
    }

    onMounted(async () => {
        await fetchFeed();

        const userId = page.props?.auth?.user?.id;
        const mode = page.props?.auth?.user?.notification_update_mode ?? 'polling';
        updateMode.value = mode;

        // A consulta comeca sempre, e so desacelera se o socket subir. Ordem
        // importa: decidir a cadencia DEPOIS do await deixaria o feed sem fonte
        // de atualizacao durante o download do laravel-echo.
        iniciarPolling(INTERVALO_POLLING_MS);

        if (mode !== 'realtime' || !userId) return;

        if (await tentarRealtime(userId)) {
            clearInterval(pollInterval);
            pollInterval = null;
            iniciarPolling(INTERVALO_RECONCILIACAO_MS);
        }
    });

    onUnmounted(() => {
        desmontado = true;

        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }

        pararRealtime();
    });

    return { items, isLoading, updateMode, refresh: fetchFeed };
}
