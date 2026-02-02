import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable para detectar gestos de swipe em elementos
 * Suporta swipe em todas as direcoes com threshold configuravel
 */
export function useSwipeGesture(elementRef, options = {}) {
  const {
    threshold = 50,
    preventScroll = false,
    onSwipeLeft = null,
    onSwipeRight = null,
    onSwipeUp = null,
    onSwipeDown = null,
    onSwipeStart = null,
    onSwipeMove = null,
    onSwipeEnd = null,
  } = options;

  const isSwiping = ref(false);
  const swipeDirection = ref(null);
  const swipeDistance = ref({ x: 0, y: 0 });

  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let startTime = 0;

  const handleTouchStart = (e) => {
    if (!e.touches || e.touches.length !== 1) return;

    const touch = e.touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
    currentX = startX;
    currentY = startY;
    startTime = Date.now();
    isSwiping.value = true;
    swipeDirection.value = null;
    swipeDistance.value = { x: 0, y: 0 };

    if (onSwipeStart) {
      onSwipeStart({ x: startX, y: startY });
    }
  };

  const handleTouchMove = (e) => {
    if (!isSwiping.value || !e.touches || e.touches.length !== 1) return;

    const touch = e.touches[0];
    currentX = touch.clientX;
    currentY = touch.clientY;

    const deltaX = currentX - startX;
    const deltaY = currentY - startY;

    swipeDistance.value = { x: deltaX, y: deltaY };

    const absX = Math.abs(deltaX);
    const absY = Math.abs(deltaY);

    if (absX > absY && absX > 10) {
      swipeDirection.value = deltaX > 0 ? 'right' : 'left';
      if (preventScroll) {
        e.preventDefault();
      }
    } else if (absY > absX && absY > 10) {
      swipeDirection.value = deltaY > 0 ? 'down' : 'up';
    }

    if (onSwipeMove) {
      onSwipeMove({
        deltaX,
        deltaY,
        direction: swipeDirection.value,
        progress: Math.max(absX, absY) / threshold,
      });
    }
  };

  const handleTouchEnd = () => {
    if (!isSwiping.value) return;

    const deltaX = currentX - startX;
    const deltaY = currentY - startY;
    const absX = Math.abs(deltaX);
    const absY = Math.abs(deltaY);
    const duration = Date.now() - startTime;

    const velocity = Math.max(absX, absY) / duration;
    const isQuickSwipe = velocity > 0.5 && duration < 300;

    const effectiveThreshold = isQuickSwipe ? threshold * 0.5 : threshold;

    if (absX > absY && absX > effectiveThreshold) {
      if (deltaX > 0 && onSwipeRight) {
        onSwipeRight({ distance: deltaX, velocity, duration });
      } else if (deltaX < 0 && onSwipeLeft) {
        onSwipeLeft({ distance: Math.abs(deltaX), velocity, duration });
      }
    } else if (absY > absX && absY > effectiveThreshold) {
      if (deltaY > 0 && onSwipeDown) {
        onSwipeDown({ distance: deltaY, velocity, duration });
      } else if (deltaY < 0 && onSwipeUp) {
        onSwipeUp({ distance: Math.abs(deltaY), velocity, duration });
      }
    }

    if (onSwipeEnd) {
      onSwipeEnd({
        deltaX,
        deltaY,
        direction: swipeDirection.value,
        velocity,
        duration,
        completed: Math.max(absX, absY) > effectiveThreshold,
      });
    }

    isSwiping.value = false;
    swipeDirection.value = null;
    swipeDistance.value = { x: 0, y: 0 };
  };

  const handleTouchCancel = () => {
    isSwiping.value = false;
    swipeDirection.value = null;
    swipeDistance.value = { x: 0, y: 0 };
  };

  onMounted(() => {
    const element = elementRef?.value;
    if (!element) return;

    element.addEventListener('touchstart', handleTouchStart, { passive: true });
    element.addEventListener('touchmove', handleTouchMove, { passive: !preventScroll });
    element.addEventListener('touchend', handleTouchEnd, { passive: true });
    element.addEventListener('touchcancel', handleTouchCancel, { passive: true });
  });

  onUnmounted(() => {
    const element = elementRef?.value;
    if (!element) return;

    element.removeEventListener('touchstart', handleTouchStart);
    element.removeEventListener('touchmove', handleTouchMove);
    element.removeEventListener('touchend', handleTouchEnd);
    element.removeEventListener('touchcancel', handleTouchCancel);
  });

  return {
    isSwiping,
    swipeDirection,
    swipeDistance,
  };
}

/**
 * Composable simplificado para swipe horizontal (drawer/modal)
 */
export function useHorizontalSwipe(elementRef, options = {}) {
  const { threshold = 50, onSwipeLeft = null, onSwipeRight = null } = options;

  return useSwipeGesture(elementRef, {
    threshold,
    preventScroll: true,
    onSwipeLeft,
    onSwipeRight,
  });
}

/**
 * Composable simplificado para swipe vertical (pull-to-refresh, bottom sheet)
 */
export function useVerticalSwipe(elementRef, options = {}) {
  const { threshold = 80, onSwipeUp = null, onSwipeDown = null } = options;

  return useSwipeGesture(elementRef, {
    threshold,
    onSwipeUp,
    onSwipeDown,
  });
}
