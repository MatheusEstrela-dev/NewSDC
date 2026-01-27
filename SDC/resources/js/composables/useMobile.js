import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable para detecção e gerenciamento de responsividade mobile
 */
export function useMobile() {
  // Breakpoints padrao (seguindo Tailwind)
  const MOBILE_BREAKPOINT = 768;
  const TABLET_BREAKPOINT = 1024;

  // Deteccao inicial imediata (se no browser)
  const getInitialWidth = () => {
    if (typeof window !== 'undefined') {
      return window.innerWidth;
    }
    return TABLET_BREAKPOINT; // Default para desktop no SSR
  };

  const initialWidth = getInitialWidth();

  const isMobile = ref(initialWidth < MOBILE_BREAKPOINT);
  const isTablet = ref(initialWidth >= MOBILE_BREAKPOINT && initialWidth < TABLET_BREAKPOINT);
  const isDesktop = ref(initialWidth >= TABLET_BREAKPOINT);

  const checkDeviceType = () => {
    if (typeof window === 'undefined') return;

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
    if (typeof window !== 'undefined') {
      window.removeEventListener('resize', checkDeviceType);
    }
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
