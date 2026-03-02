import { ref, onMounted, onUnmounted, computed } from 'vue';

/**
 * Breakpoints padrao do Tailwind
 */
const BREAKPOINTS = {
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
  '2xl': 1536,
};

/**
 * Composable para deteccao de tipo de dispositivo
 * @returns {Object} - isMobile, isTablet, isDesktop, screenWidth
 */
export function useMobile() {
  const screenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

  const isMobile = computed(() => screenWidth.value < BREAKPOINTS.md);
  const isTablet = computed(() => screenWidth.value >= BREAKPOINTS.md && screenWidth.value < BREAKPOINTS.lg);
  const isDesktop = computed(() => screenWidth.value >= BREAKPOINTS.lg);

  let resizeHandler = null;

  onMounted(() => {
    if (typeof window === 'undefined') return;

    resizeHandler = () => {
      screenWidth.value = window.innerWidth;
    };

    window.addEventListener('resize', resizeHandler, { passive: true });
    resizeHandler();
  });

  onUnmounted(() => {
    if (resizeHandler && typeof window !== 'undefined') {
      window.removeEventListener('resize', resizeHandler);
    }
  });

  return {
    isMobile,
    isTablet,
    isDesktop,
    screenWidth,
  };
}

/**
 * Estado global da sidebar mobile (singleton)
 */
const sidebarState = ref(false);

/**
 * Composable para gerenciamento da sidebar mobile
 * @returns {Object} - isSidebarOpen, openSidebar, closeSidebar, toggleSidebar
 */
export function useSidebarMobile() {
  const isSidebarOpen = sidebarState;

  const openSidebar = () => {
    isSidebarOpen.value = true;
  };

  const closeSidebar = () => {
    isSidebarOpen.value = false;
  };

  const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
  };

  return {
    isSidebarOpen,
    openSidebar,
    closeSidebar,
    toggleSidebar,
  };
}

/**
 * Composable para deteccao de orientacao do dispositivo
 * @returns {Object} - isPortrait, isLandscape, orientation
 */
export function useOrientation() {
  const orientation = ref(typeof window !== 'undefined' ? window.screen?.orientation?.type : 'portrait-primary');

  const isPortrait = computed(() => orientation.value?.includes('portrait'));
  const isLandscape = computed(() => orientation.value?.includes('landscape'));

  let orientationHandler = null;

  onMounted(() => {
    if (typeof window === 'undefined' || !window.screen?.orientation) return;

    orientationHandler = () => {
      orientation.value = window.screen.orientation.type;
    };

    window.screen.orientation.addEventListener('change', orientationHandler);
  });

  onUnmounted(() => {
    if (orientationHandler && typeof window !== 'undefined' && window.screen?.orientation) {
      window.screen.orientation.removeEventListener('change', orientationHandler);
    }
  });

  return {
    orientation,
    isPortrait,
    isLandscape,
  };
}

/**
 * Composable para deteccao de capacidades touch
 * @returns {Object} - isTouchDevice, supportsHover
 */
export function useTouchCapabilities() {
  const isTouchDevice = computed(() => {
    if (typeof window === 'undefined') return false;
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  });

  const supportsHover = computed(() => {
    if (typeof window === 'undefined') return true;
    return window.matchMedia('(hover: hover)').matches;
  });

  return {
    isTouchDevice,
    supportsHover,
  };
}
