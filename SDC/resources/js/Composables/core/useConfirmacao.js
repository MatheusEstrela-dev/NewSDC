import { ref, shallowRef } from 'vue';

/**
 * Confirmacao pelo ConfirmDialog do sistema, no lugar do window.confirm.
 *
 * O confirm nativo do navegador nao e so feio: ele ignora o tema, nao aceita
 * descricao nem variante, e em alguns navegadores oferece "nao mostrar mais
 * mensagens assim" -- e quem marca isso passa a excluir registro sem nenhuma
 * confirmacao, calado. Decretacoes, PAE, RAT e Tdap ja usavam o ConfirmDialog;
 * este composable existe para o Cisterna seguir o mesmo padrao sem cada tela
 * reimplementar o controle de estado.
 *
 * Guarda a acao pendente numa referencia NAO reativa (shallowRef com funcao):
 * tornar uma funcao reativa nao serve para nada e o Vue avisaria.
 *
 * Uso:
 *   const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();
 *   pedirConfirmacao({ title: '...', message: '...' }, () => router.delete(...));
 *   <ConfirmDialog :is-open="confirmacao.aberto" v-bind="confirmacao.opcoes"
 *                  @confirm="confirmar" @cancel="cancelar" />
 */
export function useConfirmacao() {
  const confirmacao = ref({ aberto: false, opcoes: {} });
  const acaoPendente = shallowRef(null);

  /**
   * @param {{title: string, message: string, description?: string, variant?: string, confirmText?: string, cancelText?: string}} opcoes
   * @param {() => void} aoConfirmar
   */
  function pedirConfirmacao(opcoes, aoConfirmar) {
    confirmacao.value = { aberto: true, opcoes };
    acaoPendente.value = aoConfirmar;
  }

  function confirmar() {
    const acao = acaoPendente.value;

    // Fecha ANTES de executar: a acao normalmente navega, e um dialogo aberto
    // durante a navegacao pisca na tela seguinte.
    fechar();
    acao?.();
  }

  function cancelar() {
    fechar();
  }

  function fechar() {
    confirmacao.value = { aberto: false, opcoes: {} };
    acaoPendente.value = null;
  }

  return { confirmacao, pedirConfirmacao, confirmar, cancelar };
}
