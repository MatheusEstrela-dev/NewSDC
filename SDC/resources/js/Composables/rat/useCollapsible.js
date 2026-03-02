import { ref, onMounted, watch } from 'vue';
import { useMobile } from '../mobile/useMobile';

const STORAGE_KEY = 'rat-sections-state';

/**
 * Composable para gerenciar secoes colapsaveis com persistencia em localStorage
 * @param {string} sectionId - Identificador unico da secao
 * @param {boolean} defaultExpanded - Estado inicial padrao (true = expandido)
 * @returns {Object} - isExpanded, toggle, expand, collapse
 */
export function useCollapsible(sectionId, defaultExpanded = true) {
  const { isMobile } = useMobile();
  const isExpanded = ref(defaultExpanded);

  /**
   * Carrega estado salvo do localStorage
   */
  const loadState = () => {
    try {
      const savedState = localStorage.getItem(STORAGE_KEY);
      if (savedState) {
        const state = JSON.parse(savedState);
        if (typeof state[sectionId] === 'boolean') {
          isExpanded.value = state[sectionId];
          return;
        }
      }
      // Em mobile, iniciar colapsado por padrao (exceto se for a primeira secao)
      if (isMobile.value && sectionId !== 'atendimento') {
        isExpanded.value = false;
      }
    } catch {
      // Falha silenciosa - usa valor padrao
    }
  };

  /**
   * Salva estado no localStorage
   */
  const saveState = () => {
    try {
      const savedState = localStorage.getItem(STORAGE_KEY);
      const state = savedState ? JSON.parse(savedState) : {};
      state[sectionId] = isExpanded.value;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
      // Falha silenciosa
    }
  };

  /**
   * Alterna estado expandido/colapsado
   */
  const toggle = () => {
    isExpanded.value = !isExpanded.value;
    saveState();
  };

  /**
   * Expande a secao
   */
  const expand = () => {
    isExpanded.value = true;
    saveState();
  };

  /**
   * Colapsa a secao
   */
  const collapse = () => {
    isExpanded.value = false;
    saveState();
  };

  onMounted(() => {
    loadState();
  });

  // Atualiza quando muda de mobile para desktop
  watch(isMobile, (newValue, oldValue) => {
    if (oldValue === true && newValue === false) {
      // Mudou de mobile para desktop - expandir todas
      isExpanded.value = true;
      saveState();
    }
  });

  return {
    isExpanded,
    toggle,
    expand,
    collapse,
  };
}

/**
 * Composable para gerenciar multiplas secoes colapsaveis
 * @param {Array} sectionIds - Array de IDs das secoes
 * @returns {Object} - sections, expandAll, collapseAll, toggleAll
 */
export function useCollapsibleSections(sectionIds) {
  const sections = {};

  sectionIds.forEach((id, index) => {
    // Primeira secao inicia expandida, outras colapsadas em mobile
    sections[id] = useCollapsible(id, index === 0);
  });

  const expandAll = () => {
    Object.values(sections).forEach(section => section.expand());
  };

  const collapseAll = () => {
    Object.values(sections).forEach(section => section.collapse());
  };

  const toggleAll = () => {
    const allExpanded = Object.values(sections).every(s => s.isExpanded.value);
    if (allExpanded) {
      collapseAll();
    } else {
      expandAll();
    }
  };

  return {
    sections,
    expandAll,
    collapseAll,
    toggleAll,
  };
}
