import { ref, computed } from 'vue';

export function usePlanCon(initialStats = {}) {
  const stats = ref({
    totalMunicipios: initialStats.totalMunicipios || 853,
    municipiosComPlano: initialStats.municipiosComPlano || 0,
    municipiosSemPlano: initialStats.municipiosSemPlano || 0,
    percentualComPlano: initialStats.percentualComPlano || 0,
    totalPlanos: initialStats.totalPlanos || 0,
    planosRegulares: initialStats.planosRegulares || 0,
    planosIrregulares: initialStats.planosIrregulares || 0,
    percentualRegulares: initialStats.percentualRegulares || 0,
  });

  const isLoading = ref(false);

  const formattedStats = computed(() => ({
    totalMunicipios: stats.value.totalMunicipios.toLocaleString('pt-BR'),
    municipiosComPlano: stats.value.municipiosComPlano.toLocaleString('pt-BR'),
    municipiosSemPlano: stats.value.municipiosSemPlano.toLocaleString('pt-BR'),
    percentualComPlano: `${stats.value.percentualComPlano}%`,
    totalPlanos: stats.value.totalPlanos.toLocaleString('pt-BR'),
    planosRegulares: stats.value.planosRegulares.toLocaleString('pt-BR'),
    planosIrregulares: stats.value.planosIrregulares.toLocaleString('pt-BR'),
    percentualRegulares: `${stats.value.percentualRegulares}%`,
  }));

  const municipiosChartData = computed(() => [
    {
      name: 'Municipios com plano',
      value: stats.value.municipiosComPlano,
      percent: stats.value.percentualComPlano,
      color: '#3b82f6',
    },
    {
      name: 'Municipios Sem Plano',
      value: stats.value.municipiosSemPlano,
      percent: parseFloat((100 - stats.value.percentualComPlano).toFixed(1)),
      color: '#f97316',
    },
  ]);

  const planosChartData = computed(() => [
    {
      name: 'Planos em Situacao Regular',
      value: stats.value.planosRegulares,
      percent: stats.value.percentualRegulares,
      color: '#3b82f6',
    },
    {
      name: 'Planos em Situacao Irregular',
      value: stats.value.planosIrregulares,
      percent: parseFloat((100 - stats.value.percentualRegulares).toFixed(1)),
      color: '#f97316',
    },
  ]);

  function updateStats(newStats) {
    stats.value = { ...stats.value, ...newStats };
  }

  async function refreshStats() {
    isLoading.value = true;
    try {
      const response = await fetch(route('plancon.stats'));
      const data = await response.json();
      updateStats(data);
    } catch (error) {
      console.error('Failed to refresh PlanCon stats:', error);
    } finally {
      isLoading.value = false;
    }
  }

  return {
    stats,
    isLoading,
    formattedStats,
    municipiosChartData,
    planosChartData,
    updateStats,
    refreshStats,
  };
}
