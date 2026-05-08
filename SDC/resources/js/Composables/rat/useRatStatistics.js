import { ref, computed } from 'vue';

export function useRatStatistics(initialStatistics = {}) {
  const statistics = ref({
    total: initialStatistics.total || 0,
    hoje: initialStatistics.hoje || 0,
    esteMes: initialStatistics.esteMes || 0,
    esteAno: initialStatistics.esteAno || 0,
  });

  const estatisticasFormatadas = computed(() => {
    return {
      total: statistics.value.total.toLocaleString('pt-BR'),
      hoje: statistics.value.hoje.toLocaleString('pt-BR'),
      esteMes: statistics.value.esteMes.toLocaleString('pt-BR'),
      esteAno: statistics.value.esteAno.toLocaleString('pt-BR'),
    };
  });

  function atualizarEstatisticas(newStatistics) {
    statistics.value = { ...statistics.value, ...newStatistics };
  }

  return {
    statistics,
    estatisticasFormatadas,
    atualizarEstatisticas,
  };
}

