import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const VALID_TABS = ['identificacao', 'desastres'];

/**
 * Composable de tabs do modulo Decretacoes.
 *
 * Single Responsibility: gerencia a aba ativa, sincroniza com query string `?tab=`
 * e expoe a regra de habilitacao da aba "desastres" (so existe quando ha processo).
 *
 * @param {object} options
 * @param {string} options.initialTab          Aba inicial vinda do backend (default 'identificacao')
 * @param {object} options.processo            Ref ou objeto com o processo (pode ser null no /create)
 * @returns {{
 *   activeTab: import('vue').Ref<string>,
 *   setActiveTab: (id: string) => void,
 *   isActive: (id: string) => boolean,
 *   desastresDisabled: import('vue').ComputedRef<boolean>,
 *   tabs: import('vue').ComputedRef<Array>,
 * }}
 */
export function useDecretacaoTabs({ initialTab = 'identificacao', processo = null } = {}) {
  const initial = VALID_TABS.includes(initialTab) ? initialTab : 'identificacao';
  const activeTab = ref(initial);

  const desastresDisabled = computed(() => {
    const p = processo?.value ?? processo;
    return !p?.id;
  });

  const tabs = computed(() => [
    { id: 'identificacao', label: 'Identificacao do Processo' },
    { id: 'desastres',     label: 'Dados de Desastre', disabled: desastresDisabled.value },
  ]);

  function setActiveTab(id) {
    if (!VALID_TABS.includes(id)) return;
    if (id === 'desastres' && desastresDisabled.value) return;
    activeTab.value = id;
  }

  function isActive(id) {
    return activeTab.value === id;
  }

  // Sincroniza ?tab= na URL sem reload (so quando ja houver processo na URL).
  watch(activeTab, (newTab) => {
    const url = new URL(window.location.href);
    if (!url.pathname.includes('/edit')) return; // nao mexe na URL no /create
    url.searchParams.set('tab', newTab);
    window.history.replaceState({}, '', url.toString());
  });

  return {
    activeTab,
    setActiveTab,
    isActive,
    desastresDisabled,
    tabs,
  };
}
