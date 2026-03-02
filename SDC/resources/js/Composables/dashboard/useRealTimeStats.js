import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

/**
 * Composable para gerenciamento de estatisticas em tempo real
 *
 * @param {Object} options - Opcoes de configuracao
 * @param {string} options.endpoint - URL do endpoint da API
 * @param {number} options.refreshInterval - Intervalo de atualizacao em ms (default: 30000)
 * @param {boolean} options.autoStart - Iniciar polling automaticamente (default: true)
 * @param {Object} options.initialData - Dados iniciais
 * @param {Function} options.transformer - Funcao para transformar dados da API
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

  /**
   * Busca estatisticas da API
   */
  async function fetchStats() {
    if (!endpoint) {
      console.warn('[useRealTimeStats] Endpoint nao configurado');
      return;
    }

    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get(endpoint);
      const rawData = response.data;

      statistics.value = transformer(rawData);
      lastUpdate.value = new Date();
    } catch (err) {
      error.value = err.message || 'Erro ao carregar estatisticas';
      console.error('[useRealTimeStats] Erro:', err);
    } finally {
      loading.value = false;
    }
  }

  /**
   * Inicia o polling de atualizacao
   */
  function startPolling() {
    if (isPolling.value) return;

    isPolling.value = true;
    fetchStats();

    if (refreshInterval > 0) {
      pollingTimer = setInterval(fetchStats, refreshInterval);
    }
  }

  /**
   * Para o polling de atualizacao
   */
  function stopPolling() {
    isPolling.value = false;

    if (pollingTimer) {
      clearInterval(pollingTimer);
      pollingTimer = null;
    }
  }

  /**
   * Atualiza manualmente as estatisticas
   */
  async function refresh() {
    await fetchStats();
  }

  /**
   * Atualiza estatisticas localmente (sem fetch)
   */
  function updateLocal(newStats) {
    statistics.value = { ...statistics.value, ...newStats };
    lastUpdate.value = new Date();
  }

  /**
   * Tempo desde a ultima atualizacao (em segundos)
   */
  const timeSinceUpdate = computed(() => {
    if (!lastUpdate.value) return null;
    return Math.floor((Date.now() - lastUpdate.value.getTime()) / 1000);
  });

  /**
   * Status formatado da ultima atualizacao
   */
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
    if (autoStart && endpoint) {
      startPolling();
    }
  });

  onUnmounted(() => {
    stopPolling();
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
