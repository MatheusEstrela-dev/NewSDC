import { ref, computed, onMounted, onUnmounted } from 'vue';

/**
 * Composable para monitorar status de rede e conexao
 * Detecta online/offline, tipo de conexao e modo de economia de dados
 */
export function useNetworkStatus() {
  const isOnline = ref(true);
  const connectionType = ref('unknown');
  const effectiveType = ref('4g');
  const saveData = ref(false);
  const downlink = ref(null);
  const rtt = ref(null);

  const updateNetworkInfo = () => {
    if (typeof navigator === 'undefined') return;

    isOnline.value = navigator.onLine;

    if ('connection' in navigator) {
      const conn = navigator.connection;
      connectionType.value = conn.type || 'unknown';
      effectiveType.value = conn.effectiveType || '4g';
      saveData.value = conn.saveData || false;
      downlink.value = conn.downlink || null;
      rtt.value = conn.rtt || null;
    }
  };

  const isSlowConnection = computed(() => {
    const slowTypes = ['slow-2g', '2g', '3g'];
    return slowTypes.includes(effectiveType.value) || (rtt.value && rtt.value > 500);
  });

  const isFastConnection = computed(() => {
    return effectiveType.value === '4g' && (!rtt.value || rtt.value < 100);
  });

  const shouldReduceData = computed(() => {
    return saveData.value || isSlowConnection.value;
  });

  const connectionQuality = computed(() => {
    if (!isOnline.value) return 'offline';
    if (saveData.value) return 'save-data';
    if (effectiveType.value === 'slow-2g' || effectiveType.value === '2g') return 'poor';
    if (effectiveType.value === '3g') return 'moderate';
    if (effectiveType.value === '4g') {
      if (rtt.value && rtt.value > 300) return 'moderate';
      return 'good';
    }
    return 'unknown';
  });

  const handleOnline = () => {
    isOnline.value = true;
    updateNetworkInfo();
  };

  const handleOffline = () => {
    isOnline.value = false;
  };

  const handleConnectionChange = () => {
    updateNetworkInfo();
  };

  onMounted(() => {
    if (typeof window === 'undefined') return;

    updateNetworkInfo();

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    if ('connection' in navigator) {
      navigator.connection.addEventListener('change', handleConnectionChange);
    }
  });

  onUnmounted(() => {
    if (typeof window === 'undefined') return;

    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);

    if ('connection' in navigator) {
      navigator.connection.removeEventListener('change', handleConnectionChange);
    }
  });

  return {
    isOnline,
    connectionType,
    effectiveType,
    saveData,
    downlink,
    rtt,
    isSlowConnection,
    isFastConnection,
    shouldReduceData,
    connectionQuality,
  };
}
