import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';

/**
 * As acoes de uma camada de risco, no formato que o ActionButton espera.
 *
 * Existe porque as TRES telas do modulo oferecem as mesmas acoes sobre a mesma
 * entidade: a lista de /geoespacial, o detalhe de /geoespacial/{id} e "Meus
 * envios" em /geoespacial/enviar. Repetir os handlers nas tres faria a
 * confirmacao de arquivamento divergir -- e arquivar sem confirmar retira area
 * de risco do mapa de plantao com um clique errado.
 *
 * O `allowed` de cada acao vem do BACKEND, em `camada.acoes`, e nao de regra
 * escrita aqui: quem decide e Support\AcessoACamada, que conhece o estado da
 * camada e a procedencia do usuario. O ActionButton ainda aplica o RBAC por
 * cima disso -- os dois filtros somados sao o que fecha o botao.
 *
 * Uso:
 *   const { acoesDe, emEdicao, fecharEdicao, confirmacao, confirmar, cancelar }
 *     = useAcoesDeCamada();
 */
export function useAcoesDeCamada() {
  const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

  // Camada aberta no modal de edicao. null = modal fechado.
  const emEdicao = ref(null);

  function fecharEdicao() {
    emEdicao.value = null;
  }

  /**
   * @param {object} camada linha vinda do backend, com a chave `acoes`
   * @param {{verDetalhe?: boolean}} opcoes
   */
  function acoesDe(camada, opcoes = {}) {
    const permitido = camada.acoes ?? {};

    return [
      // No detalhe o botao de abrir o detalhe nao faz sentido; nas listagens,
      // faz. Quem chama decide.
      ...(opcoes.verDetalhe === false ? [] : [{
        action: 'view',
        label: 'Abrir',
        handler: () => router.get(route('geoespacial.show', camada.id)),
      }]),

      {
        action: 'edit',
        allowed: permitido.editar === true,
        handler: () => { emEdicao.value = camada; },
      },

      {
        action: 'archive',
        allowed: permitido.arquivar === true,
        placement: 'menu',
        handler: () => pedirConfirmacao({
          title: 'Arquivar camada',
          message: `Arquivar "${camada.nome}"?`,
          // A consequencia vem escrita: quem arquiva no meio de um plantao
          // precisa saber que a area sai do mapa AGORA, e nao no proximo dia.
          description: 'A area sai do mapa operacional imediatamente. O historico'
            + ' e mantido e a camada pode ser reativada depois.',
          variant: 'warning',
          confirmText: 'Arquivar',
        }, () => router.post(route('geoespacial.arquivar', camada.id), {}, {
          preserveScroll: true,
        })),
      },

      {
        action: 'check',
        label: 'Reativar',
        // aliasOverride: o ActionButton mapeia 'check' para o slug
        // `...validar`, que nao existe neste modulo. A permissao real de
        // reativar e a de arquivar -- e a mesma decisao, nos dois sentidos.
        aliasOverride: 'arquivar',
        allowed: permitido.reativar === true,
        placement: 'menu',
        handler: () => pedirConfirmacao({
          title: 'Reativar camada',
          message: `Reativar "${camada.nome}"?`,
          description: 'A area volta a aparecer no mapa operacional do estado.',
          variant: 'info',
          confirmText: 'Reativar',
        }, () => router.post(route('geoespacial.reativar', camada.id), {}, {
          preserveScroll: true,
        })),
      },

      {
        action: 'export',
        label: 'Baixar original',
        allowed: permitido.baixar === true,
        placement: 'menu',
        // window.location e nao router.get: e download de arquivo, e o Inertia
        // esperaria uma resposta de pagina que nunca vem -- a tela ficaria
        // travada no estado de carregamento.
        handler: () => { window.location.href = route('geoespacial.arquivo', camada.id); },
      },

      {
        action: 'history',
        label: 'Reprocessar geometria',
        aliasOverride: 'revisar',
        allowed: permitido.reprocessar === true,
        placement: 'menu',
        handler: () => pedirConfirmacao({
          title: 'Reprocessar geometria',
          message: `Refazer o desenho de "${camada.nome}" a partir do arquivo original?`,
          description: 'A geometria atual e substituida pela extraida do arquivo'
            + ' guardado no Bronze. Os dados de identificacao e a aprovacao nao mudam.',
          variant: 'warning',
          confirmText: 'Reprocessar',
        }, () => router.post(route('geoespacial.reprocessar', camada.id), {}, {
          preserveScroll: true,
        })),
      },
    ];
  }

  return { acoesDe, emEdicao, fecharEdicao, confirmacao, confirmar, cancelar };
}
