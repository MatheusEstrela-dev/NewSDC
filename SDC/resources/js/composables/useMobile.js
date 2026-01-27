import { ref, onMounted, onUnmounted, computed } from 'vue';

/**
 * Verifica se o dispositivo e realmente um touch device (mobile/tablet real)
 */
function isTouchDevice() {
  if (typeof window === 'undefined') return false;
  return (
    'ontouchstart' in window ||
    navigator.maxTouchPoints > 0 ||
    window.matchMedia('(pointer: coarse)').matches
  );
}

/**
 * Composable para detecção e gerenciamento de responsividade mobile
 * Considera zoom do navegador para evitar falsos positivos em desktop
 */
export function useMobile() {
  const MOBILE_BREAKPOINT = 768;
  const TABLET_BREAKPOINT = 1024;

  const isRealTouchDevice = ref(false);
  const viewportWidth = ref(TABLET_BREAKPOINT);

  const getViewportWidth = () => {
    if (typeof window === 'undefined') return TABLET_BREAKPOINT;
    return window.innerWidth;
  };

  const checkDeviceType = () => {
    if (typeof window === 'undefined') return;
    viewportWidth.value = getViewportWidth();
    isRealTouchDevice.value = isTouchDevice();
  };

  const isMobile = computed(() => {
    const width = viewportWidth.value;
    if (!isRealTouchDevice.value) {
      return false;
    }
    return width < MOBILE_BREAKPOINT;
  });

  const isTablet = computed(() => {
    const width = viewportWidth.value;
    if (!isRealTouchDevice.value) {
      return false;
    }
    return width >= MOBILE_BREAKPOINT && width < TABLET_BREAKPOINT;
  });

  const isDesktop = computed(() => {
    if (!isRealTouchDevice.value) {
      return true;
    }
    return viewportWidth.value >= TABLET_BREAKPOINT;
  });

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
    if (typeof document !== 'undefined') {
      document.body.style.overflow = 'hidden';
      document.body.classList.add('sidebar-open');
    }
  };

  const closeSidebar = () => {
    isSidebarOpen.value = false;
    if (typeof document !== 'undefined') {
      document.body.style.overflow = '';
      document.body.classList.remove('sidebar-open');
    }
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
