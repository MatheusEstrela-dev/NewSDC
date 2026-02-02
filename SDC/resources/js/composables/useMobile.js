import { ref, onMounted, onUnmounted, computed } from 'vue';

/**
 * Verifica se o dispositivo suporta touch (para features especificas)
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
 * Composable para deteccao e gerenciamento de responsividade mobile
 * Usa apenas viewport width para consistencia com media queries CSS
 */
export function useMobile() {
  const MOBILE_BREAKPOINT = 768;
  const TABLET_BREAKPOINT = 1024;

  const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : TABLET_BREAKPOINT);
  const hasTouchSupport = ref(false);

  const getViewportWidth = () => {
    if (typeof window === 'undefined') return TABLET_BREAKPOINT;
    return window.innerWidth;
  };

  const checkDeviceType = () => {
    if (typeof window === 'undefined') return;
    viewportWidth.value = getViewportWidth();
    hasTouchSupport.value = isTouchDevice();
  };

  // Mobile: viewport < 768px (consistente com CSS @media max-width: 767px)
  const isMobile = computed(() => {
    return viewportWidth.value < MOBILE_BREAKPOINT;
  });

  // Tablet: viewport >= 768px e < 1024px (consistente com CSS @media min-width: 768px and max-width: 1023px)
  const isTablet = computed(() => {
    return viewportWidth.value >= MOBILE_BREAKPOINT && viewportWidth.value < TABLET_BREAKPOINT;
  });

  // Desktop: viewport >= 1024px
  const isDesktop = computed(() => {
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
    hasTouchSupport,
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
