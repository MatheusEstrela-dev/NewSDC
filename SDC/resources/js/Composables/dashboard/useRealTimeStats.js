import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

/**
 * Composable para gerenciamento de estatisticas em tempo real
 * - Page Visibility API: pausa polling quando tab nao esta visivel
 * - Exponential backoff: reduz frequencia em caso de erros consecutivos
 * - Network-aware: respeita conexoes lentas e save-data
 */
export function useRealTimeStats(options = {}) {
  const {
    endpoint = '',
    refreshInterval = 30000,
    autoStart = true,
    initialData = {},
    transformer = (data) => data,
  } = options;

  const statistics = ref(initialData);
  const loading = ref(false);
  const error = ref(null);
  const lastUpdate = ref(null);
  const isPolling = ref(false);

  let pollingTimer = null;
  let consecutiveErrors = 0;
  let visibilityHandler = null;

  function getEffectiveInterval() {
    if (consecutiveErrors === 0) return refreshInterval;
    return Math.min(refreshInterval * Math.pow(2, consecutiveErrors), 300000);
  }

  function shouldReducePolling() {
    if (!navigator.connection) return false;
    const conn = navigator.connection;
    return conn.saveData || ['slow-2g', '2g', '3g'].includes(conn.effectiveType);
  }

  async function fetchStats() {
    if (!endpoint) return;
    if (document.hidden) return;
    if (shouldReducePolling() && lastUpdate.value) return;

    loading.value = true;
    error.value = null;

    try {
      const axios = window.axios;
      const response = await axios.get(endpoint);
      statistics.value = transformer(response.data);
      lastUpdate.value = new Date();
      consecutiveErrors = 0;
    } catch (err) {
      consecutiveErrors++;
      error.value = err.message || 'Erro ao carregar estatisticas';
    } finally {
      loading.value = false;
    }
  }

  function scheduleNext() {
    if (pollingTimer) clearTimeout(pollingTimer);
    if (!isPolling.value) return;

    const interval = getEffectiveInterval();
    pollingTimer = setTimeout(() => {
      fetchStats().finally(scheduleNext);
    }, interval);
  }

  function startPolling() {
    if (isPolling.value) return;
    isPolling.value = true;
    fetchStats().finally(scheduleNext);
  }

  function stopPolling() {
    isPolling.value = false;
    if (pollingTimer) {
      clearTimeout(pollingTimer);
      pollingTimer = null;
    }
  }

  async function refresh() {
    consecutiveErrors = 0;
    await fetchStats();
  }

  function updateLocal(newStats) {
    statistics.value = { ...statistics.value, ...newStats };
    lastUpdate.value = new Date();
  }

  const timeSinceUpdate = computed(() => {
    if (!lastUpdate.value) return null;
    return Math.floor((Date.now() - lastUpdate.value.getTime()) / 1000);
  });

  const updateStatus = computed(() => {
    if (loading.value) return 'Atualizando...';
    if (error.value) return `Erro: ${error.value}`;
    if (!lastUpdate.value) return 'Aguardando dados...';

    const seconds = timeSinceUpdate.value;
    if (seconds < 60) return `Atualizado ha ${seconds}s`;
    if (seconds < 3600) return `Atualizado ha ${Math.floor(seconds / 60)}min`;
    return `Atualizado ha ${Math.floor(seconds / 3600)}h`;
  });

  onMounted(() => {
    visibilityHandler = () => {
      if (document.hidden) {
        if (pollingTimer) { clearTimeout(pollingTimer); pollingTimer = null; }
      } else if (isPolling.value) {
        fetchStats().finally(scheduleNext);
      }
    };
    document.addEventListener('visibilitychange', visibilityHandler);

    if (autoStart && endpoint) {
      startPolling();
    }
  });

  onUnmounted(() => {
    stopPolling();
    if (visibilityHandler) {
      document.removeEventListener('visibilitychange', visibilityHandler);
    }
  });

  return {
    statistics,
    loading,
    error,
    lastUpdate,
    isPolling,
    timeSinceUpdate,
    updateStatus,
    fetchStats,
    startPolling,
    stopPolling,
    refresh,
    updateLocal,
  };
}

/**
 * Composable especifico para estatisticas de Decretacoes
 */
export function useDecretacoesStats(options = {}) {
  const defaultInitialData = {
    totalEventos: 0,
    totalEventosEcp: 0,
    totalEventosSe: 0,
    registros: 0,
    registrosEcp: 0,
    registrosSe: 0,
    decretacoes: 0,
    decretacoesEcp: 0,
    decretacoesSe: 0,
    municipiosAtingidos: 0,
    municipiosAtingidosEcp: 0,
    municipiosAtingidosSe: 0,
    decretacoesVigentes: 0,
    decretacoesVigentesEcp: 0,
    decretacoesVigentesSe: 0,
  };

  const transformer = (data) => ({
    totalEventos: data.total_eventos ?? data.totalEventos ?? 0,
    totalEventosEcp: data.total_eventos_ecp ?? data.totalEventosEcp ?? 0,
    totalEventosSe: data.total_eventos_se ?? data.totalEventosSe ?? 0,
    registros: data.registros ?? 0,
    registrosEcp: data.registros_ecp ?? data.registrosEcp ?? 0,
    registrosSe: data.registros_se ?? data.registrosSe ?? 0,
    decretacoes: data.decretacoes ?? 0,
    decretacoesEcp: data.decretacoes_ecp ?? data.decretacoesEcp ?? 0,
    decretacoesSe: data.decretacoes_se ?? data.decretacoesSe ?? 0,
    municipiosAtingidos: data.municipios_atingidos ?? data.municipiosAtingidos ?? 0,
    municipiosAtingidosEcp: data.municipios_atingidos_ecp ?? data.municipiosAtingidosEcp ?? 0,
    municipiosAtingidosSe: data.municipios_atingidos_se ?? data.municipiosAtingidosSe ?? 0,
    decretacoesVigentes: data.decretacoes_vigentes ?? data.decretacoesVigentes ?? 0,
    decretacoesVigentesEcp: data.decretacoes_vigentes_ecp ?? data.decretacoesVigentesEcp ?? 0,
    decretacoesVigentesSe: data.decretacoes_vigentes_se ?? data.decretacoesVigentesSe ?? 0,
  });

  return useRealTimeStats({
    endpoint: options.endpoint || '/api/decretacoes/statistics',
    refreshInterval: options.refreshInterval ?? 30000,
    autoStart: options.autoStart ?? true,
    initialData: { ...defaultInitialData, ...options.initialData },
    transformer,
  });
}

export default useRealTimeStats;
