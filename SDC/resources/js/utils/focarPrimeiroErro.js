// Campo com erro (atoms de input marcam atom-input-error) ou aviso de erro.
const SELETOR_ERRO = '.atom-input-error, [aria-invalid="true"], [role="alert"]';
const FOCAVEL = 'input, select, textarea, button, [tabindex]';

/**
 * Leva o usuario ao primeiro erro dentro de `raiz`, na ordem do documento.
 *
 * Existe por causa do preserveScroll: depois de um envio recusado a tela fica
 * onde estava (no botao, la embaixo) e, no celular, so o ultimo erro aparece.
 * Campo recebe o foco; aviso sem foco nativo ganha tabindex=-1 para que o
 * leitor de tela leia a mensagem. Chamar depois do DOM refletir os erros
 * (nextTick no onError).
 *
 * @param {HTMLElement|null} raiz
 * @returns {boolean} se achou algum erro
 */
export function focarPrimeiroErro(raiz) {
  const alvo = raiz?.querySelector(SELETOR_ERRO);
  if (!alvo) return false;

  alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });

  if (!alvo.matches(FOCAVEL)) {
    alvo.setAttribute('tabindex', '-1');
  }
  // preventScroll: o scrollIntoView acima ja centraliza; o foco nao deve
  // brigar com ele pulando para a borda da tela.
  alvo.focus({ preventScroll: true });

  return true;
}
