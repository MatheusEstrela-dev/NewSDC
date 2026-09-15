import { onUnmounted, watch } from 'vue';

/**
 * Bloqueio da rolagem de fundo enquanto um modal esta aberto.
 *
 * POR QUE COMPARTILHADO. Cada modal fazia isto por conta propria, e o estado e
 * UM SO -- o elemento raiz da pagina. Como o watch de cada um roda com
 * `immediate: true`, toda instancia MONTADA executava o ramo de fechar e
 * limpava o bloqueio que o modal aberto tinha acabado de por. A pagina de
 * decretacoes tem quatro modais montados ao mesmo tempo, entao o destravar
 * sempre ganhava: com o modal aberto, `document.body.style.overflow` estava
 * vazio e o gesto rolava a pagina de tras 600px.
 *
 * A contagem resolve: N modais podem pedir bloqueio, e ele so sai quando o
 * ultimo devolve. Cada instancia devolve exatamente o que pegou, nunca mais.
 *
 * POR QUE NO scrollingElement, E NAO NO body. Quem rola aqui e o `html`
 * (`document.scrollingElement === documentElement`, medido). `overflow: hidden`
 * no body so vale quando o valor se propaga para o viewport, o que nao e
 * garantido -- e era a segunda razao de o bloqueio nao pegar.
 */

let travas = 0;
let anterior = null;

function travar() {
  if (travas === 0) {
    const raiz = document.scrollingElement || document.documentElement;

    anterior = {
      overflow: raiz.style.overflow,
      paddingRight: document.body.style.paddingRight,
    };

    // A barra de rolagem some junto com a rolagem. Sem devolver a largura dela
    // como padding, o conteudo inteiro salta para a direita ao abrir o modal --
    // em desktop com barra classica sao ~15px de tranco.
    const larguraDaBarra = window.innerWidth - document.documentElement.clientWidth;

    raiz.style.overflow = 'hidden';
    if (larguraDaBarra > 0) {
      document.body.style.paddingRight = `${larguraDaBarra}px`;
    }
  }

  travas += 1;
}

function destravar() {
  if (travas === 0) {
    return;
  }

  travas -= 1;

  if (travas === 0) {
    const raiz = document.scrollingElement || document.documentElement;
    raiz.style.overflow = anterior?.overflow ?? '';
    document.body.style.paddingRight = anterior?.paddingRight ?? '';
    anterior = null;
  }
}

/**
 * @param {import('vue').Ref<boolean>|(() => boolean)} aberto
 */
export function useBloqueioDeRolagem(aberto) {
  // Trava desta instancia. E o que impede um modal fechado de devolver uma
  // trava que ele nunca pegou e zerar a contagem dos outros.
  let minhaTrava = false;

  const sincronizar = (estaAberto) => {
    if (estaAberto && !minhaTrava) {
      travar();
      minhaTrava = true;
      return;
    }

    if (!estaAberto && minhaTrava) {
      destravar();
      minhaTrava = false;
    }
  };

  watch(aberto, sincronizar, { immediate: true });

  // Fechar por desmontagem (troca de rota com o modal aberto) tambem devolve.
  onUnmounted(() => sincronizar(false));

  return {
    bloquear: () => sincronizar(true),
    liberar: () => sincronizar(false),
  };
}
