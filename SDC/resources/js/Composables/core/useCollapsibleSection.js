import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useMobile } from '../mobile/useMobile';

/**
 * Qual secao esta aberta no modo sanfona, por namespace.
 *
 * Vive no MODULO, e nao na instancia: e o que permite uma secao fechar a irma
 * sem o componente pai orquestrar nada. Mesma tecnica do
 * `Composables/rat/useCollapsible`, que ja roda em producao no RAT -- a
 * diferenca e ser por namespace, para duas telas de modulos distintos nunca
 * disputarem a mesma sanfona.
 *
 * @type {Record<string, import('vue').Ref<string|null>>}
 */
const sanfonaPorNamespace = {};

/** Instancias vivas por namespace, para saber quando zerar a sanfona. */
const instanciasPorNamespace = {};

function sanfonaDo(namespace) {
  if (!sanfonaPorNamespace[namespace]) {
    sanfonaPorNamespace[namespace] = ref(null);
  }

  return sanfonaPorNamespace[namespace];
}

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
 * @param {{ expandidoPorPadrao?: boolean, recolherNoMobile?: boolean, sanfonaNoMobile?: boolean }} opcoes
 *
 * `sanfonaNoMobile` liga o comportamento de sanfona do RAT: no celular apenas
 * UMA secao do namespace fica aberta, e abrir outra fecha a anterior. Serve a
 * formulario longo -- o do TDAP tem 28 itens estruturais e 7 de tanque, e com
 * tudo aberto em 375px a pagina passa de dez telas de rolagem.
 */
export function useCollapsibleSection(namespace, sectionId, opcoes = {}) {
  const { expandidoPorPadrao = true, recolherNoMobile = true, sanfonaNoMobile = false } = opcoes;

  const { isMobile } = useMobile();
  const abertoLocal = ref(expandidoPorPadrao);
  const sanfona = sanfonaDo(namespace);

  const emModoSanfona = computed(() => sanfonaNoMobile && isMobile.value);

  const estaExpandido = computed({
    get: () => (emModoSanfona.value ? sanfona.value === sectionId : abertoLocal.value),
    set: (valor) => {
      if (emModoSanfona.value) {
        sanfona.value = valor ? sectionId : null;

        return;
      }

      abertoLocal.value = valor;
    },
  });

  const chave = `${namespace}-sections-state`;

  function carregar() {
    // No modo sanfona a preferencia salva nao vale: quem manda e qual secao
    // esta aberta agora, e isso e estado de momento, nao preferencia.
    if (emModoSanfona.value) {
      return;
    }

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
    if (emModoSanfona.value) {
      return;
    }

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

  onMounted(() => {
    carregar();

    if (!sanfonaNoMobile) {
      return;
    }

    instanciasPorNamespace[namespace] = (instanciasPorNamespace[namespace] ?? 0) + 1;

    // A PRIMEIRA secao a montar abre no celular: sanfona toda fechada entrega
    // uma tela de titulos e nenhum campo.
    if (isMobile.value && sanfona.value === null) {
      sanfona.value = sectionId;
    }
  });

  onUnmounted(() => {
    if (!sanfonaNoMobile) {
      return;
    }

    instanciasPorNamespace[namespace] = (instanciasPorNamespace[namespace] ?? 1) - 1;

    // Ultima secao saiu de tela (troca de pagina): zera, para a proxima abrir
    // na primeira dela em vez de herdar um id que nao existe mais e ficar toda
    // fechada.
    if (instanciasPorNamespace[namespace] <= 0) {
      instanciasPorNamespace[namespace] = 0;
      sanfona.value = null;
    }
  });

  watch(estaExpandido, salvar);

  return { estaExpandido, alternar, expandir, recolher };
}
