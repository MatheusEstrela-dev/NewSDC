import { ref, computed, onMounted, onUnmounted } from 'vue';

/**
 * Composable para gerenciar navegação e menu
 * Single Responsibility: Gerenciar estado de navegação e menu mobile
 */
export function useNavigation() {
  const activeMenu = ref('pmda');
  const isMobileMenuOpen = ref(false);
  const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
  const openSubMenus = ref({ tdap: true });

  /**
   * Verifica se está em modo mobile
   */
  const isMobile = computed(() => windowWidth.value < 768);

  /**
   * Atualiza largura da janela
   */
  function updateWidth() {
    windowWidth.value = window.innerWidth;
  }

  /**
   * Define o menu ativo
   */
  function setActive(menu) {
    activeMenu.value = menu;
    if (isMobile.value) {
      isMobileMenuOpen.value = false;
    }
  }

  /**
   * Alterna submenu
   */
  function toggleSubMenu(menu) {
    openSubMenus.value[menu] = !openSubMenus.value[menu];
  }

  /**
   * Abre menu mobile
   */
  function openMobileMenu() {
    isMobileMenuOpen.value = true;
  }

  /**
   * Fecha menu mobile
   */
  function closeMobileMenu() {
    isMobileMenuOpen.value = false;
  }

  /**
   * Alterna menu mobile
   */
  function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
  }

  // Lifecycle hooks
  onMounted(() => {
    if (typeof window !== 'undefined') {
      window.addEventListener('resize', updateWidth);
      updateWidth();
    }
  });

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('resize', updateWidth);
    }
  });

  return {
    activeMenu,
    isMobileMenuOpen,
    windowWidth,
    openSubMenus,
    isMobile,
    setActive,
    toggleSubMenu,
    openMobileMenu,
    closeMobileMenu,
    toggleMobileMenu,
  };
}

