import { DocumentDuplicateIcon } from '@heroicons/vue/24/outline';

/**
 * Acao "Nova versao" / "Continuar rascunho" de uma linha/card da listagem de cursos.
 * A elegibilidade vem pronta do backend (AtalhoDeNovaVersao); retorna [] quando nao
 * ha atalho, para ser espalhada na lista de acoes do ActionButton.
 */
export function acoesDeVersao(treinamento, onVersao) {
  if (!treinamento.pode_criar_versao && !treinamento.pode_continuar_rascunho) return [];

  return [{
    action: 'edit',
    placement: 'menu',
    label: treinamento.pode_continuar_rascunho ? 'Continuar rascunho' : 'Nova versão',
    icon: DocumentDuplicateIcon,
    handler: () => onVersao(treinamento),
  }];
}
