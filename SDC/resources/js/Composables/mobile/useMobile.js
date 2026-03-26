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
// Singleton: um unico listener compartilhado entre todas as instancias
const sharedScreenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
let resizeListenerCount = 0;
let throttledResizeHandler = null;

function addSharedResizeListener() {
  resizeListenerCount++;
  if (resizeListenerCount === 1 && typeof window !== 'undefined') {
    let rafId = null;
    throttledResizeHandler = () => {
      if (rafId) return;
      rafId = requestAnimationFrame(() => {
        sharedScreenWidth.value = window.innerWidth;
        rafId = null;
      });
    };
    window.addEventListener('resize', throttledResizeHandler, { passive: true });
    sharedScreenWidth.value = window.innerWidth;
  }
}

function removeSharedResizeListener() {
  resizeListenerCount--;
  if (resizeListenerCount === 0 && throttledResizeHandler && typeof window !== 'undefined') {
    window.removeEventListener('resize', throttledResizeHandler);
    throttledResizeHandler = null;
  }
}

export function useMobile() {
  const isMobile = computed(() => sharedScreenWidth.value < BREAKPOINTS.md);
  const isTablet = computed(() => sharedScreenWidth.value >= BREAKPOINTS.md && sharedScreenWidth.value < BREAKPOINTS.lg);
  const isDesktop = computed(() => sharedScreenWidth.value >= BREAKPOINTS.lg);

  onMounted(() => addSharedResizeListener());
  onUnmounted(() => removeSharedResizeListener());

  return {
    isMobile,
    isTablet,
    isDesktop,
    screenWidth: sharedScreenWidth,
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
