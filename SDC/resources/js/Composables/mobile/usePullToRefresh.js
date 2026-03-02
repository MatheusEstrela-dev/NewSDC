import { ref, onMounted, onUnmounted, isRef, unref } from 'vue';

/**
 * Composable para implementar Pull-to-Refresh em mobile
 * Detecta pull down no topo da pagina e dispara callback de refresh
 */
export function usePullToRefresh(options = {}) {
  const {
    threshold = 80,
    maxPull = 150,
    onRefresh = null,
    resistance = 2.5,
    disabled = false,
    disabledRef = null,
  } = options;

  const isPulling = ref(false);
  const isRefreshing = ref(false);
  const pullDistance = ref(0);
  const pullProgress = ref(0);

  let startY = 0;
  let currentY = 0;
  let touchStarted = false;

  const isDisabled = () => {
    if (disabledRef && isRef(disabledRef)) {
      return unref(disabledRef);
    }
    return disabled;
  };

  const canPull = () => {
    if (isDisabled()) return false;
    if (typeof window === 'undefined') return false;

    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    return scrollTop <= 0;
  };

  const handleTouchStart = (e) => {
    if (!canPull() || isRefreshing.value) return;

    const touch = e.touches[0];
    startY = touch.clientY;
    touchStarted = true;
  };

  const handleTouchMove = (e) => {
    if (!touchStarted || isRefreshing.value) return;

    const touch = e.touches[0];
    currentY = touch.clientY;
    const deltaY = currentY - startY;

    if (deltaY > 0 && canPull()) {
      isPulling.value = true;

      const resistedPull = deltaY / resistance;
      pullDistance.value = Math.min(resistedPull, maxPull);
      pullProgress.value = Math.min(pullDistance.value / threshold, 1);

      if (deltaY > 10) {
        e.preventDefault();
      }
    }
  };

  const handleTouchEnd = async () => {
    if (!touchStarted) return;

    touchStarted = false;

    if (isPulling.value && pullDistance.value >= threshold && onRefresh) {
      isRefreshing.value = true;
      pullDistance.value = threshold * 0.6;

      try {
        await onRefresh();
      } catch (error) {
        console.error('Pull to refresh error:', error);
      } finally {
        isRefreshing.value = false;
        pullDistance.value = 0;
        pullProgress.value = 0;
      }
    } else {
      pullDistance.value = 0;
      pullProgress.value = 0;
    }

    isPulling.value = false;
  };

  const handleTouchCancel = () => {
    touchStarted = false;
    isPulling.value = false;
    pullDistance.value = 0;
    pullProgress.value = 0;
  };

  onMounted(() => {
    if (typeof window === 'undefined') return;

    document.addEventListener('touchstart', handleTouchStart, { passive: true });
    document.addEventListener('touchmove', handleTouchMove, { passive: false });
    document.addEventListener('touchend', handleTouchEnd, { passive: true });
    document.addEventListener('touchcancel', handleTouchCancel, { passive: true });
  });

  onUnmounted(() => {
    if (typeof window === 'undefined') return;

    document.removeEventListener('touchstart', handleTouchStart);
    document.removeEventListener('touchmove', handleTouchMove);
    document.removeEventListener('touchend', handleTouchEnd);
    document.removeEventListener('touchcancel', handleTouchCancel);
  });

  return {
    isPulling,
    isRefreshing,
    pullDistance,
    pullProgress,
  };
}
