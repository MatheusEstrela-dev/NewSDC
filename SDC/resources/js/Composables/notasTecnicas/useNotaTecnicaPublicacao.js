import { router } from '@inertiajs/vue3';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';

/**
 * Publicar, revogar e excluir uma nota, sempre com confirmacao. Usado pelo
 * workspace e pela tela de edicao, que montam o ConfirmDialog com
 * `confirmacao`, `confirmar` e `cancelar`.
 *
 * @param {object} opcoesDeVisita  repassadas ao router (ex.: `only` no workspace,
 *                                 para recarregar so o que a acao muda)
 */
export function useNotaTecnicaPublicacao(opcoesDeVisita = {}) {
  const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

  const visita = { preserveScroll: true, ...opcoesDeVisita };

  function publicar(nota) {
    const republicar = nota.status === 'revogada';

    pedirConfirmacao({
      title: `${republicar ? 'Republicar' : 'Publicar'} ${nota.codigo}?`,
      message: 'O PDF vigente passa a ser o documento oficial, visível a todos os leitores, com você como aprovador.',
      variant: 'success',
      confirmText: republicar ? 'Republicar' : 'Publicar',
    }, () => router.post(route('notas-tecnicas.publicar', nota.id), {}, visita));
  }

  function revogar(nota) {
    pedirConfirmacao({
      title: `Revogar ${nota.codigo}?`,
      message: 'A nota sai da lista em vigor e fica marcada como obsoleta. Dá para republicar depois.',
      variant: 'warning',
      confirmText: 'Revogar',
    }, () => router.post(route('notas-tecnicas.revogar', nota.id), {}, visita));
  }

  // router.delete(url, opcoes) nao recebe corpo. Sem `visita`: o backend
  // redireciona para o workspace, que precisa vir inteiro.
  function excluir(nota) {
    pedirConfirmacao({
      title: `Excluir ${nota.codigo}?`,
      message: 'A nota e as versões somem do workspace. O código não é reaproveitado.',
      variant: 'danger',
      confirmText: 'Excluir',
    }, () => router.delete(route('notas-tecnicas.destroy', nota.id)));
  }

  return { confirmacao, confirmar, cancelar, publicar, revogar, excluir };
}
