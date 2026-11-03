import { computed } from 'vue';
import { useStorage } from '@vueuse/core';
import { useMobile } from '@/Composables/useMobile';

/**
 * Estado de layout do workspace de notas tecnicas, lembrado entre visitas:
 * pastas e painel de informacoes abertos ou recolhidos.
 *
 * O painel de informacoes nasce aberto so em tela larga (2xl): abaixo disso ele
 * flutua sobre o PDF, e abrir por padrao cobriria o documento.
 *
 * O estado guardado so vale no desktop (lg+): abaixo disso os paineis empilham
 * em fluxo normal e nao ha botao para recolher, entao pastas e informacoes
 * ficam sempre visiveis. Assim o que foi recolhido no desktop nunca esconde
 * painel no celular sem como reabrir.
 */
const TELA_LARGA = '(min-width: 1536px)';

function telaLarga() {
  return typeof window !== 'undefined' && window.matchMedia(TELA_LARGA).matches;
}

export function useNotaTecnicaWorkspace() {
  const pastasAbertas = useStorage('notas-tecnicas:pastas-abertas', true);
  const informacoesAbertas = useStorage('notas-tecnicas:informacoes-abertas', telaLarga());
  const { isDesktop } = useMobile();

  const pastasVisiveis = computed(() => !isDesktop.value || pastasAbertas.value);
  const informacoesVisiveis = computed(() => !isDesktop.value || informacoesAbertas.value);

  return {
    pastasAbertas,
    informacoesAbertas,
    pastasVisiveis,
    informacoesVisiveis,
    alternarPastas: () => { pastasAbertas.value = !pastasAbertas.value; },
    alternarInformacoes: () => { informacoesAbertas.value = !informacoesAbertas.value; },
  };
}
