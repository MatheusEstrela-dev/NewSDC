import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable para detecção e gerenciamento de responsividade mobile
 */
export function useMobile() {
  const isMobile = ref(false);
  const isTablet = ref(false);
  const isDesktop = ref(false);

  // Breakpoints padrão (seguindo Tailwind)
  const MOBILE_BREAKPOINT = 768;
  const TABLET_BREAKPOINT = 1024;

  const checkDeviceType = () => {
    const width = window.innerWidth;

    isMobile.value = width < MOBILE_BREAKPOINT;
    isTablet.value = width >= MOBILE_BREAKPOINT && width < TABLET_BREAKPOINT;
    isDesktop.value = width >= TABLET_BREAKPOINT;
  };

  onMounted(() => {
    checkDeviceType();
    window.addEventListener('resize', checkDeviceType);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', checkDeviceType);
  });

  return {
    isMobile,
    isTablet,
    isDesktop,
  };
}

/**
 * Composable para gerenciar estado da sidebar mobile
 */
export function useSidebarMobile() {
  const isSidebarOpen = ref(false);

  const openSidebar = () => {
    isSidebarOpen.value = true;
    // Prevenir scroll do body quando sidebar está aberta
    document.body.style.overflow = 'hidden';
  };

  const closeSidebar = () => {
    isSidebarOpen.value = false;
    // Restaurar scroll do body
    document.body.style.overflow = '';
  };

  const toggleSidebar = () => {
    if (isSidebarOpen.value) {
      closeSidebar();
    } else {
      openSidebar();
    }
  };

  return {
    isSidebarOpen,
    openSidebar,
    closeSidebar,
    toggleSidebar,
  };
}
