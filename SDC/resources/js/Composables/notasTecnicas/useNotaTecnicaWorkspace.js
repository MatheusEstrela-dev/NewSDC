import { useStorage } from '@vueuse/core';

/**
 * Estado de layout do workspace de notas tecnicas, lembrado entre visitas:
 * pastas e painel de informacoes abertos ou recolhidos.
 *
 * O painel de informacoes nasce aberto so em tela larga (2xl): abaixo disso ele
 * flutua sobre o PDF, e abrir por padrao cobriria o documento.
 */
const TELA_LARGA = '(min-width: 1536px)';

function telaLarga() {
  return typeof window !== 'undefined' && window.matchMedia(TELA_LARGA).matches;
}

export function useNotaTecnicaWorkspace() {
  const pastasAbertas = useStorage('notas-tecnicas:pastas-abertas', true);
  const informacoesAbertas = useStorage('notas-tecnicas:informacoes-abertas', telaLarga());

  return {
    pastasAbertas,
    informacoesAbertas,
    alternarPastas: () => { pastasAbertas.value = !pastasAbertas.value; },
    alternarInformacoes: () => { informacoesAbertas.value = !informacoesAbertas.value; },
  };
}
