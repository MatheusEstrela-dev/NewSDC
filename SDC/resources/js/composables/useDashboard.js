import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useModal } from './useModal';
import { useNavigation } from './useNavigation';

/**
 * Composable principal do Dashboard
 * Orchestrates outros composables e gerencia dados do dashboard
 * Single Responsibility: Coordenar lógica do dashboard
 */
export function useDashboard() {
  const { open: openModal, ...modal } = useModal();
  const navigation = useNavigation();

  // Dados do dashboard (serão substituídos por dados reais da API)
  const metrics = ref({
    emEdicao: {
      val: 24,
      label: 'Em Edição',
      color: 'bg-blue-600',
      icon: 'pencil',
      desc: 'Planos sendo editados pelos municípios.',
    },
    emAnalise: {
      val: 5,
      label: 'Em Análise',
      color: 'bg-amber-500',
      icon: 'clock',
      desc: 'Aguardando parecer técnico da CEDEC.',
    },
    aprovados: {
      val: 77,
      label: 'Aprovados',
      color: 'bg-emerald-600',
      icon: 'check',
      desc: 'Planos homologados e vigentes.',
    },
    atendidos: {
      val: 12,
      label: 'Atendidos',
      color: 'bg-indigo-600',
      icon: 'check-badge',
      desc: 'Recursos liberados ou ação concluída.',
    },
  });

  const pmdaEmAnalise = ref([
    {
      id: 1,
      protocolo: '2025/001',
      status: 'Análise Técnica',
      data: '20/01/2025',
      municipio: 'Belo Horizonte',
      responsavel: 'Carlos Silva',
    },
    {
      id: 2,
      protocolo: '2025/002',
      status: 'Parecer',
      data: '12/02/2025',
      municipio: 'Contagem',
      responsavel: 'Ana Souza',
    },
    {
      id: 3,
      protocolo: '2025/005',
      status: 'Aguard. Doc.',
      data: '15/02/2025',
      municipio: 'Betim',
      responsavel: 'Mário Dias',
    },
    {
      id: 4,
      protocolo: '2025/008',
      status: 'Análise Técnica',
      data: '18/02/2025',
      municipio: 'Nova Lima',
      responsavel: 'Fernanda Lima',
    },
    {
      id: 5,
      protocolo: '2025/012',
      status: 'Triagem',
      data: '20/02/2025',
      municipio: 'Sabará',
      responsavel: 'Pendente',
    },
  ]);

  const historico = ref([
    {
      id: 101,
      protocolo: '2025/001',
      municipio: 'Belo Horizonte',
      data: 'Há 2 horas',
      acao: 'Envio para análise',
    },
    {
      id: 102,
      protocolo: '2025/002',
      municipio: 'Contagem',
      data: 'Ontem',
      acao: 'Correção de documentos',
    },
    {
      id: 103,
      protocolo: '2025/005',
      municipio: 'Betim',
      data: '15/02/2025',
      acao: 'Solicitação de vistoria',
    },
    {
      id: 104,
      protocolo: 'RAT-992',
      municipio: 'Ouro Preto',
      data: '10/02/2025',
      acao: 'Relatório finalizado',
    },
  ]);

  const currentYear = ref(new Date().getFullYear());

  /**
   * Abre detalhes de um item
   */
  function openDetails(title, item) {
    openModal(title, item);
  }

  /**
   * Busca dados do dashboard (será implementado com API real)
   */
  function fetchDashboardData() {
    // TODO: Implementar chamada à API
    // router.reload({ only: ['metrics', 'pmdaEmAnalise', 'historico'] });
  }

  /**
   * Atualiza métricas
   */
  function refreshMetrics() {
    fetchDashboardData();
  }

  return {
    // State
    metrics,
    pmdaEmAnalise,
    historico,
    currentYear,

    // Composables
    modal,
    navigation,

    // Methods
    openDetails,
    fetchDashboardData,
    refreshMetrics,
  };
}

