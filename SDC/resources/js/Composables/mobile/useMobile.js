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
// IMPORTANTE: as flags vem de matchMedia, NAO de window.innerWidth. Os dois
// divergem pela largura da scrollbar em engines com scrollbar classica
// (WebKitGTK/Epiphany no Xfce): numa tela de exatamente 1024px o CSS entrava
// em modo drawer (media query ve ~1009px) enquanto o JS via innerWidth=1024 e
// negava o is-mobile-open — o hamburger aparecia mas a sidebar nunca abria.
// matchMedia usa a MESMA medida das media queries do CSS, por construcao.
const MOBILE_QUERY = `(max-width: ${BREAKPOINTS.md - 1}px)`;
const DESKTOP_QUERY = `(min-width: ${BREAKPOINTS.lg}px)`;

// Singleton: listeners unicos compartilhados entre todas as instancias
const canMatchMedia = typeof window !== 'undefined' && typeof window.matchMedia === 'function';
const sharedIsMobile = ref(canMatchMedia ? window.matchMedia(MOBILE_QUERY).matches : false);
const sharedIsDesktop = ref(canMatchMedia ? window.matchMedia(DESKTOP_QUERY).matches : true);
const sharedScreenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
let mediaListenerCount = 0;
let mqlMobile = null;
let mqlDesktop = null;
let mobileChangeHandler = null;
let desktopChangeHandler = null;
let throttledResizeHandler = null;

// WebKitGTK antigo (Epiphany) so tem a API legada addListener/removeListener
function subscribeMedia(mql, handler) {
  if (typeof mql.addEventListener === 'function') mql.addEventListener('change', handler);
  else mql.addListener(handler);
}

function unsubscribeMedia(mql, handler) {
  if (typeof mql.removeEventListener === 'function') mql.removeEventListener('change', handler);
  else mql.removeListener(handler);
}

function addSharedListeners() {
  mediaListenerCount++;
  if (mediaListenerCount !== 1 || !canMatchMedia) return;

  mqlMobile = window.matchMedia(MOBILE_QUERY);
  mqlDesktop = window.matchMedia(DESKTOP_QUERY);
  mobileChangeHandler = (event) => { sharedIsMobile.value = event.matches; };
  desktopChangeHandler = (event) => { sharedIsDesktop.value = event.matches; };
  subscribeMedia(mqlMobile, mobileChangeHandler);
  subscribeMedia(mqlDesktop, desktopChangeHandler);
  sharedIsMobile.value = mqlMobile.matches;
  sharedIsDesktop.value = mqlDesktop.matches;

  // screenWidth segue exposto para quem precisa do numero bruto (nao usar
  // para decisao de breakpoint — para isso existem as flags acima)
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

function removeSharedListeners() {
  mediaListenerCount--;
  if (mediaListenerCount !== 0 || !canMatchMedia) return;

  if (mqlMobile && mobileChangeHandler) unsubscribeMedia(mqlMobile, mobileChangeHandler);
  if (mqlDesktop && desktopChangeHandler) unsubscribeMedia(mqlDesktop, desktopChangeHandler);
  mqlMobile = null;
  mqlDesktop = null;
  mobileChangeHandler = null;
  desktopChangeHandler = null;
  if (throttledResizeHandler) {
    window.removeEventListener('resize', throttledResizeHandler);
    throttledResizeHandler = null;
  }
}

export function useMobile() {
  const isMobile = computed(() => sharedIsMobile.value);
  const isTablet = computed(() => !sharedIsMobile.value && !sharedIsDesktop.value);
  const isDesktop = computed(() => sharedIsDesktop.value);

  onMounted(() => addSharedListeners());
  onUnmounted(() => removeSharedListeners());

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
