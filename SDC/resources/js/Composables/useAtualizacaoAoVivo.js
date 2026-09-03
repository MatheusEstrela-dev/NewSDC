import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import { initEcho } from '@/bootstrap';

/**
 * Recarrega props da pagina quando o servidor avisa que o dado mudou.
 *
 * O evento que chega e so um AVISO, sem dado nenhum: quem rebusca e o Inertia,
 * pelo controller, que segue sendo a unica fonte das props. Isso evita ter duas
 * serializacoes que divergem na primeira mudanca de matview.
 *
 * A conexao vem do initEcho, que devolve UMA instancia compartilhada com o inbox
 * de notificacoes. Antes este composable lia window.Echo, e havia duas: o
 * resources/js/echo.js criava a sua no boot e o initEcho sobrescrevia
 * window.Echo depois. A conexao inicial ficava aberta sem dono, e o leave() do
 * unmount atuava na instancia errada -- a assinatura seguia viva no Reverb para
 * uma pagina que nao existia mais.
 *
 * @param {object}   opcoes
 * @param {string}   opcoes.canal   Canal privado, SEM o prefixo "private-".
 * @param {string}   opcoes.evento  Nome do evento. Com ponto na frente para usar
 *                                  o broadcastAs em vez do FQCN da classe.
 * @param {string[]} opcoes.props   Props a rebuscar, no formato do Inertia.
 * @param {number}   [opcoes.debounceMs] Janela de coalescencia, em ms.
 */
export function useAtualizacaoAoVivo({ canal, evento, props, debounceMs = 400 }) {
    let echo = null;
    let assinatura = null;
    let pendente = false;
    let desmontado = false;
    let visibilidadeHandler = null;
    let timerDebounce = null;

    const recarregar = () => {
        // preserveState mantem a pagina da tabela e o estado local; sem
        // preserveScroll a atualizacao daria um salto e seria pior que o F5 que
        // ela veio substituir.
        router.reload({
            only: props,
            preserveScroll: true,
            preserveState: true,
        });
    };

    /*
     * Coalesce rajada num unico reload.
     *
     * Aprovar dez pedidos em sequencia emite dez eventos, e sem esta janela todo
     * viewer roda dez vezes o index do controller -- no PMDA sao tres queries
     * paginadas mais o catalogo de municipios por rodada. O timer reinicia a cada
     * evento, entao a rajada custa um reload em vez de N.
     *
     * Fica no CLIENTE porque e aqui que se sabe se um reload ja esta em voo;
     * coalescer no servidor exigiria estado compartilhado entre workers para uma
     * economia que um setTimeout resolve.
     *
     * Nao substitui a logica de aba oculta: aba oculta nem chega aqui, marca
     * pendencia e resolve no visibilitychange.
     */
    const agendarRecarga = () => {
        if (timerDebounce) {
            clearTimeout(timerDebounce);
        }

        timerDebounce = setTimeout(() => {
            timerDebounce = null;
            recarregar();
        }, debounceMs);
    };

    const aoReceber = () => {
        // Aba em segundo plano nao rebusca: ninguem esta olhando e cada
        // recarregamento custa um ciclo de request. Marca pendencia e resolve
        // quando a aba volta -- mesma disciplina que o polling do sino aplica.
        if (document.hidden) {
            pendente = true;

            return;
        }

        agendarRecarga();
    };

    const assinar = async () => {
        // Sem Echo nao ha tempo real, e isso NAO e erro: com
        // BROADCAST_CONNECTION=null a pagina funciona como sempre funcionou.
        // Silenciar aqui e o que torna a feature um flag desligavel.
        const instancia = await initEcho();

        if (!instancia) {
            return;
        }

        // O initEcho baixa laravel-echo e pusher-js por import dinamico, entao a
        // pagina pode ter sido trocada nesse meio tempo. Assinar depois do
        // unmount deixaria um canal vivo sem ninguem para encerra-lo, que e
        // exatamente o vazamento que este composable existe para evitar.
        if (desmontado) {
            return;
        }

        echo = instancia;
        assinatura = echo.private(canal);
        assinatura.listen(evento, aoReceber);

        visibilidadeHandler = () => {
            if (!document.hidden && pendente) {
                pendente = false;
                agendarRecarga();
            }
        };

        document.addEventListener('visibilitychange', visibilidadeHandler);
    };

    onMounted(() => {
        // Sem await de proposito: a pagina renderiza sem esperar o websocket.
        assinar();
    });

    onBeforeUnmount(() => {
        desmontado = true;

        // Sem cancelar o timer, um reload agendado dispararia depois do unmount e
        // navegaria uma pagina que nao existe mais.
        if (timerDebounce) {
            clearTimeout(timerDebounce);
            timerDebounce = null;
        }

        if (visibilidadeHandler) {
            document.removeEventListener('visibilitychange', visibilidadeHandler);
            visibilidadeHandler = null;
        }

        // Sair do canal alem de parar de escutar: sem o leave, a conexao segue
        // assinada e o Reverb continua entregando para uma pagina que nao existe
        // mais. O leave do Echo ja tenta o nome cru e os prefixos private- e
        // presence-, entao recebe o canal sem prefixo.
        if (assinatura) {
            assinatura.stopListening(evento);
            echo.leave(canal);
            assinatura = null;
            echo = null;
        }
    });
}
