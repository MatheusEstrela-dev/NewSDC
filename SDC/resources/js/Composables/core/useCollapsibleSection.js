import { ref, onMounted, watch } from 'vue';
import { useMobile } from '../mobile/useMobile';

/**
 * Secao colapsavel com estado persistido, para qualquer modulo.
 *
 * Existe porque o `Composables/rat/useCollapsible` e acoplado ao RAT: a chave de
 * storage e fixa em 'rat-sections-state' e ha um caso especial hardcoded para a
 * secao 'atendimento'. Reusar de outro modulo misturaria o estado das duas telas
 * no mesmo registro.
 *
 * @param {string} namespace  Escopo do storage, geralmente o modulo.
 * @param {string} sectionId  Identificador da secao dentro do namespace.
 * @param {{ expandidoPorPadrao?: boolean, recolherNoMobile?: boolean }} opcoes
 */
export function useCollapsibleSection(namespace, sectionId, opcoes = {}) {
  const { expandidoPorPadrao = true, recolherNoMobile = true } = opcoes;

  const { isMobile } = useMobile();
  const estaExpandido = ref(expandidoPorPadrao);

  const chave = `${namespace}-sections-state`;

  function carregar() {
    try {
      const salvo = localStorage.getItem(chave);

      if (salvo) {
        const estado = JSON.parse(salvo);

        if (typeof estado[sectionId] === 'boolean') {
          estaExpandido.value = estado[sectionId];

          return;
        }
      }

      // Sem preferencia salva: no celular comeca recolhido, senao a primeira
      // dobra da tela vira uma pilha de titulos e o usuario rola muito para
      // achar o campo. Quem escolher expandir tem a escolha respeitada.
      if (recolherNoMobile && isMobile.value) {
        estaExpandido.value = false;
      }
    } catch {
      // localStorage indisponivel (modo privado, cota): segue com o padrao.
    }
  }

  function salvar() {
    try {
      const salvo = localStorage.getItem(chave);
      const estado = salvo ? JSON.parse(salvo) : {};

      estado[sectionId] = estaExpandido.value;
      localStorage.setItem(chave, JSON.stringify(estado));
    } catch {
      // Nao poder lembrar a preferencia nao pode quebrar a tela.
    }
  }

  function alternar() {
    estaExpandido.value = !estaExpandido.value;
  }

  function expandir() {
    estaExpandido.value = true;
  }

  function recolher() {
    estaExpandido.value = false;
  }

  onMounted(carregar);
  watch(estaExpandido, salvar);

  return { estaExpandido, alternar, expandir, recolher };
}
