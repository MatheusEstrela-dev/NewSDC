import { nextTick, onUnmounted, watch } from 'vue';

/**
 * Prende o foco do teclado dentro de um dialogo enquanto ele esta aberto.
 *
 * Ao abrir, guarda quem tinha o foco (o botao que abriu o dialogo) e move o
 * foco para dentro. Enquanto aberto, Tab e Shift+Tab giram so entre os
 * elementos focaveis do container. Ao fechar, devolve o foco a quem abriu,
 * se ele ainda estiver na pagina (a linha excluida, por exemplo, ja saiu).
 *
 * O projeto nao tem focus-trap nem @vueuse/integrations; o necessario aqui
 * cabe nestas linhas sem trazer dependencia nova.
 */

const SELETOR_FOCAVEL = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

function focaveis(raiz) {
  // getClientRects vazio = elemento oculto (display: none), que nao recebe foco.
  return Array.from(raiz.querySelectorAll(SELETOR_FOCAVEL))
    .filter((el) => el.getClientRects().length > 0);
}

/**
 * @param {import('vue').Ref<HTMLElement|null>} container
 * @param {import('vue').Ref<boolean>|(() => boolean)} aberto
 * @param {{ focoInicial?: () => HTMLElement|null|undefined }} [opcoes]
 */
export function usePrisaoDeFoco(container, aberto, { focoInicial } = {}) {
  let origem = null;
  let ativo = false;

  const aoTeclar = (e) => {
    const raiz = container.value;
    if (e.key !== 'Tab' || !raiz) {
      return;
    }

    const lista = focaveis(raiz);
    if (lista.length === 0) {
      e.preventDefault();
      return;
    }

    // Indice -1 cobre o foco fora do dialogo e no proprio container: nos dois
    // casos o Tab nativo poderia escapar para a pagina de tras.
    const i = lista.indexOf(document.activeElement);

    if (e.shiftKey && i <= 0) {
      e.preventDefault();
      lista[lista.length - 1].focus();
    } else if (!e.shiftKey && (i === -1 || i === lista.length - 1)) {
      e.preventDefault();
      lista[0].focus();
    }
  };

  const ativar = async () => {
    if (ativo) {
      return;
    }

    ativo = true;
    origem = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    document.addEventListener('keydown', aoTeclar);

    // O container so existe depois da renderizacao do v-if.
    await nextTick();

    const raiz = container.value;
    if (!ativo || !raiz) {
      return;
    }

    const alvo = focoInicial?.() || focaveis(raiz)[0] || raiz;
    alvo.focus({ preventScroll: true });
  };

  const desativar = () => {
    if (!ativo) {
      return;
    }

    ativo = false;
    document.removeEventListener('keydown', aoTeclar);

    const alvo = origem;
    origem = null;

    if (alvo?.isConnected) {
      alvo.focus({ preventScroll: true });
    }
  };

  watch(aberto, (estaAberto) => (estaAberto ? ativar() : desativar()), { immediate: true });

  // Desmontar com o dialogo aberto (troca de rota) tambem solta o foco.
  onUnmounted(desativar);
}
