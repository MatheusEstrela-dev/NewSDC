import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Recarrega props da pagina quando o servidor avisa que o dado mudou.
 *
 * O evento que chega e so um AVISO, sem dado nenhum: quem rebusca e o Inertia,
 * pelo controller, que segue sendo a unica fonte das props. Isso evita ter duas
 * serializacoes que divergem na primeira mudanca de matview.
 *
 * @param {object}   opcoes
 * @param {string}   opcoes.canal   Canal privado, SEM o prefixo "private-".
 * @param {string}   opcoes.evento  Nome do evento. Com ponto na frente para usar
 *                                  o broadcastAs em vez do FQCN da classe.
 * @param {string[]} opcoes.props   Props a rebuscar, no formato do Inertia.
 */
export function useAtualizacaoAoVivo({ canal, evento, props }) {
    let assinatura = null;
    let pendente = false;
    let visibilidadeHandler = null;

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

    const aoReceber = () => {
        // Aba em segundo plano nao rebusca: ninguem esta olhando e cada
        // recarregamento custa um ciclo de request. Marca pendencia e resolve
        // quando a aba volta -- mesma disciplina que o polling do sino aplica.
        if (document.hidden) {
            pendente = true;

            return;
        }

        recarregar();
    };

    onMounted(() => {
        // Sem Echo nao ha tempo real, e isso NAO e erro: com
        // BROADCAST_CONNECTION=null a pagina funciona como sempre funcionou.
        // Silenciar aqui e o que torna a feature um flag desligavel.
        if (!window.Echo) {
            return;
        }

        assinatura = window.Echo.private(canal);
        assinatura.listen(evento, aoReceber);

        visibilidadeHandler = () => {
            if (!document.hidden && pendente) {
                pendente = false;
                recarregar();
            }
        };

        document.addEventListener('visibilitychange', visibilidadeHandler);
    });

    onBeforeUnmount(() => {
        if (visibilidadeHandler) {
            document.removeEventListener('visibilitychange', visibilidadeHandler);
            visibilidadeHandler = null;
        }

        // Sair do canal alem de parar de escutar: sem o leave, a conexao segue
        // assinada e o Reverb continua entregando para uma pagina que nao existe
        // mais.
        if (assinatura) {
            assinatura.stopListening(evento);
            window.Echo.leave(`private-${canal}`);
            assinatura = null;
        }
    });
}
