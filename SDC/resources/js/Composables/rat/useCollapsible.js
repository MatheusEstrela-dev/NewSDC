import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useMobile } from '../mobile/useMobile';

const STORAGE_KEY = 'rat-sections-state';

/**
 * Secoes colapsaveis de formulario longo, com DOIS modos por largura de tela.
 *
 *   >= md   independente  varias secoes abertas ao mesmo tempo, estado
 *                         lembrado em localStorage entre visitas
 *   <  md   SANFONA       exatamente UMA secao aberta; abrir uma fecha a outra
 *
 * O motivo do modo sanfona: o formulario do RAT tem seis a oito secoes, cada
 * uma com quatro a dez campos. Com todas abertas em 375px a pagina passa de dez
 * telas de rolagem e o usuario perde a nocao de onde esta -- foi o que as
 * capturas de 2026-08-31 mostraram.
 *
 * DOIS DEFEITOS que este arquivo tinha e que a reescrita corrige:
 *
 * 1. `carregarEstado` consultava o localStorage ANTES de olhar a largura e
 *    retornava cedo quando achava valor salvo. Quem expandia tudo no desktop
 *    voltava no celular com tudo expandido -- a regra de mobile nunca chegava a
 *    valer. Agora o estado salvo governa so o desktop; no mobile a sanfona
 *    manda, sempre.
 *
 * 2. O padrao de mobile era `sectionId !== 'atendimento'`, com o id cravado no
 *    composable. A primeira secao de cada aba tinha outro id e nenhuma abria.
 *    Agora abre a primeira que montar, seja qual for.
 */

/**
 * Qual secao esta aberta no modo sanfona. Vive no MODULO, nao na instancia: e o
 * que permite uma secao fechar a irma sem o componente pai orquestrar nada.
 *
 * Uma so para todo o RAT e correto -- as abas nao coexistem em tela.
 */
const secaoAbertaNoMobile = ref(null);

/** Quantas instancias vivas, para saber quando zerar a sanfona. */
let instancias = 0;

/**
 * @param {string} sectionId - identificador unico da secao
 * @param {boolean} defaultExpanded - estado inicial no DESKTOP
 */
export function useCollapsible(sectionId, defaultExpanded = true) {
  const { isMobile } = useMobile();

  /** Estado do desktop: por instancia e persistido. */
  const abertoNoDesktop = ref(defaultExpanded);

  const estaExpandido = computed(() =>
    isMobile.value
      ? secaoAbertaNoMobile.value === sectionId
      : abertoNoDesktop.value
  );

  const carregarEstadoDoDesktop = () => {
    try {
      const salvo = localStorage.getItem(STORAGE_KEY);
      if (!salvo) return;

      const estado = JSON.parse(salvo);
      if (typeof estado[sectionId] === 'boolean') {
        abertoNoDesktop.value = estado[sectionId];
      }
    } catch {
      // localStorage indisponivel (aba privada, cota cheia): segue no default.
      // Perder a preferencia e aceitavel; quebrar o formulario nao.
    }
  };

  const salvarEstadoDoDesktop = () => {
    try {
      const salvo = localStorage.getItem(STORAGE_KEY);
      const estado = salvo ? JSON.parse(salvo) : {};
      estado[sectionId] = abertoNoDesktop.value;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(estado));
    } catch {
      // idem
    }
  };

  const alternar = () => {
    if (isMobile.value) {
      // Sanfona: tocar na aberta fecha; tocar em outra troca.
      //
      // NAO persiste de proposito. Qual secao esta aberta no telefone e estado
      // de momento, nao preferencia -- gravar faria a proxima visita abrir numa
      // secao do meio do formulario, sem razao aparente.
      secaoAbertaNoMobile.value =
        secaoAbertaNoMobile.value === sectionId ? null : sectionId;
      return;
    }

    abertoNoDesktop.value = !abertoNoDesktop.value;
    salvarEstadoDoDesktop();
  };

  const expandir = () => {
    if (isMobile.value) {
      secaoAbertaNoMobile.value = sectionId;
      return;
    }

    abertoNoDesktop.value = true;
    salvarEstadoDoDesktop();
  };

  const recolher = () => {
    if (isMobile.value) {
      if (secaoAbertaNoMobile.value === sectionId) {
        secaoAbertaNoMobile.value = null;
      }
      return;
    }

    abertoNoDesktop.value = false;
    salvarEstadoDoDesktop();
  };

  onMounted(() => {
    instancias += 1;
    carregarEstadoDoDesktop();

    // A PRIMEIRA secao a montar abre no mobile. Sem id cravado: cada aba tem a
    // sua primeira, e todas devem comecar com algo aberto -- sanfona toda
    // fechada abre com uma tela de titulos e nenhum campo.
    if (isMobile.value && secaoAbertaNoMobile.value === null) {
      secaoAbertaNoMobile.value = sectionId;
    }
  });

  onUnmounted(() => {
    instancias -= 1;

    // Ultima secao saiu de tela (troca de aba ou de pagina): zera, para a
    // proxima aba abrir na primeira dela em vez de herdar um id que nao existe
    // mais e ficar toda fechada.
    if (instancias <= 0) {
      instancias = 0;
      secaoAbertaNoMobile.value = null;
    }
  });

  return {
    estaExpandido,
    alternar,
    expandir,
    recolher,
  };
}

/**
 * Varias secoes de uma vez.
 *
 * `expandirTodos` nao tem efeito no modo sanfona: abrir todas e exatamente o
 * que ele existe para impedir. Em mobile ele abre a primeira, que e a leitura
 * util do pedido.
 *
 * @param {Array<string>} sectionIds
 */
export function useCollapsibleSections(sectionIds) {
  const { isMobile } = useMobile();
  const sections = {};

  sectionIds.forEach((id, index) => {
    sections[id] = useCollapsible(id, index === 0);
  });

  const expandirTodos = () => {
    if (isMobile.value) {
      sections[sectionIds[0]]?.expandir();
      return;
    }

    Object.values(sections).forEach((s) => s.expandir());
  };

  const recolherTodos = () => {
    Object.values(sections).forEach((s) => s.recolher());
  };

  const alternarTodos = () => {
    const todosExpandidos = Object.values(sections).every((s) => s.estaExpandido.value);

    if (todosExpandidos) {
      recolherTodos();
    } else {
      expandirTodos();
    }
  };

  return {
    sections,
    expandirTodos,
    recolherTodos,
    alternarTodos,
  };
}
