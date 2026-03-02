import { ref, computed } from 'vue';

/**
 * Composable para gerenciar sistema de abas
 * Single Responsibility: Gerenciar apenas o estado das abas
 */
export function useTabs(initialTab = 1) {
  const activeTab = ref(initialTab);

  /**
   * Define a aba ativa
   */
  function setActiveTab(tab) {
    activeTab.value = tab;
  }

  /**
   * Verifica se uma aba está ativa
   */
  function isActive(tab) {
    return activeTab.value === tab;
  }

  /**
   * Retorna classes CSS para a aba
   */
  function getTabClass(tabId) {
    const baseClass = 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 flex items-center gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 rounded-t cursor-pointer select-none';
    
    if (activeTab.value === tabId) {
      return `${baseClass} border-blue-500 text-blue-400 bg-gradient-to-t from-blue-500/10 to-transparent`;
    }
    
    return `${baseClass} border-transparent text-slate-500 hover:text-slate-300 hover:border-slate-700`;
  }

  return {
    activeTab,
    setActiveTab,
    isActive,
    getTabClass,
  };
}

