import { ref, onMounted, watch } from 'vue';
import { useMobile } from '../mobile/useMobile';

const STORAGE_KEY = 'rat-sections-state';

/**
 * Composable para gerenciar secoes colapsaveis com persistencia em localStorage
 * @param {string} sectionId - Identificador unico da secao
 * @param {boolean} defaultExpanded - Estado inicial padrao (true = expandido)
 * @returns {Object} - estaExpandido, alternar, expandir, recolher
 */
export function useCollapsible(sectionId, defaultExpanded = true) {
  const { isMobile } = useMobile();
  const estaExpandido = ref(defaultExpanded);

  /**
   * Carrega estado salvo do localStorage
   */
  const carregarEstado = () => {
    try {
      const savedState = localStorage.getItem(STORAGE_KEY);
      if (savedState) {
        const state = JSON.parse(savedState);
        if (typeof state[sectionId] === 'boolean') {
          estaExpandido.value = state[sectionId];
          return;
        }
      }
      // Em mobile, iniciar colapsado por padrao (exceto se for a primeira secao)
      if (isMobile.value && sectionId !== 'atendimento') {
        estaExpandido.value = false;
      }
    } catch {
      // Falha silenciosa - usa valor padrao
    }
  };

  /**
   * Salva estado no localStorage
   */
  const salvarEstado = () => {
    try {
      const savedState = localStorage.getItem(STORAGE_KEY);
      const state = savedState ? JSON.parse(savedState) : {};
      state[sectionId] = estaExpandido.value;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
      // Falha silenciosa
    }
  };

  /**
   * Alterna estado expandido/colapsado
   */
  const alternar = () => {
    estaExpandido.value = !estaExpandido.value;
    salvarEstado();
  };

  /**
   * Expande a secao
   */
  const expandir = () => {
    estaExpandido.value = true;
    salvarEstado();
  };

  /**
   * Colapsa a secao
   */
  const recolher = () => {
    estaExpandido.value = false;
    salvarEstado();
  };

  onMounted(() => {
    carregarEstado();
  });

  // Atualiza quando muda de mobile para desktop
  watch(isMobile, (newValue, oldValue) => {
    if (oldValue === true && newValue === false) {
      // Mudou de mobile para desktop - expandir todas
      estaExpandido.value = true;
      salvarEstado();
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
 * Composable para gerenciar multiplas secoes colapsaveis
 * @param {Array} sectionIds - Array de IDs das secoes
 * @returns {Object} - sections, expandirTodos, recolherTodos, alternarTodos
 */
export function useCollapsibleSections(sectionIds) {
  const sections = {};

  sectionIds.forEach((id, index) => {
    // Primeira secao inicia expandida, outras colapsadas em mobile
    sections[id] = useCollapsible(id, index === 0);
  });

  const expandirTodos = () => {
    Object.values(sections).forEach(section => section.expandir());
  };

  const recolherTodos = () => {
    Object.values(sections).forEach(section => section.recolher());
  };

  const alternarTodos = () => {
    const todosExpandidos = Object.values(sections).every(s => s.estaExpandido.value);
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
