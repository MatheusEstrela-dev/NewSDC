import { ref, computed } from 'vue';

// Mock de demandas
const mockDemandas = ref([
  {
    id: 1,
    codigo: 'DEM-2025-001',
    titulo: 'Configurar novo servidor de produção',
    tipo: 'tarefa',
    prioridade: 'alta',
    status: 'aberta',
    descricao: 'Necessário configurar servidor Linux para deploy da aplicação',
    responsavel_id: '1',
    responsavel_nome: 'João Silva',
    solicitante_id: '3',
    solicitante_nome: 'Carlos Admin',
    prazo: '2025-12-30',
    tags: ['infraestrutura', 'urgente'],
    created_at: '2025-12-20T10:00:00',
    updated_at: '2025-12-20T10:00:00',
  },
  {
    id: 2,
    codigo: 'DEM-2025-002',
    titulo: 'Corrigir erro no login de usuários',
    tipo: 'bug',
    prioridade: 'critica',
    status: 'em_andamento',
    descricao: 'Usuários relatam erro 500 ao tentar fazer login com credenciais válidas',
    responsavel_id: '2',
    responsavel_nome: 'Maria Santos',
    solicitante_id: '1',
    solicitante_nome: 'João Silva',
    prazo: '2025-12-27',
    tags: ['bug', 'autenticação', 'crítico'],
    created_at: '2025-12-19T14:30:00',
    updated_at: '2025-12-26T09:15:00',
  },
  {
    id: 3,
    codigo: 'DEM-2025-003',
    titulo: 'Implementar relatório de desempenho',
    tipo: 'melhoria',
    prioridade: 'media',
    status: 'aberta',
    descricao: 'Criar dashboard com métricas de performance do sistema',
    responsavel_id: null,
    responsavel_nome: null,
    solicitante_id: '3',
    solicitante_nome: 'Carlos Admin',
    prazo: '2026-01-15',
    tags: ['relatório', 'dashboard'],
    created_at: '2025-12-18T08:00:00',
    updated_at: '2025-12-18T08:00:00',
  },
  {
    id: 4,
    codigo: 'DEM-2025-004',
    titulo: 'Atualizar documentação da API',
    tipo: 'tarefa',
    prioridade: 'baixa',
    status: 'concluida',
    descricao: 'Atualizar Swagger com novos endpoints criados',
    responsavel_id: '3',
    responsavel_nome: 'Pedro Oliveira',
    solicitante_id: '2',
    solicitante_nome: 'Maria Santos',
    prazo: '2025-12-25',
    tags: ['documentação', 'api'],
    created_at: '2025-12-15T16:00:00',
    updated_at: '2025-12-24T18:00:00',
  },
  {
    id: 5,
    codigo: 'DEM-2025-005',
    titulo: 'Suporte - Resetar senha de usuário',
    tipo: 'suporte',
    prioridade: 'media',
    status: 'em_andamento',
    descricao: 'Usuário esqueceu senha e não recebe e-mail de recuperação',
    responsavel_id: '1',
    responsavel_nome: 'João Silva',
    solicitante_id: '3',
    solicitante_nome: 'Carlos Admin',
    prazo: '2025-12-26',
    tags: ['suporte', 'senha'],
    created_at: '2025-12-26T11:00:00',
    updated_at: '2025-12-26T11:30:00',
  },
]);

export function useDemandas() {
  const demandas = ref([...mockDemandas.value]);
  const filters = ref({
    search: '',
    tipo: '',
    prioridade: '',
    status: '',
    responsavel_id: '',
  });
  const currentPage = ref(1);
  const perPage = ref(10);
  const loading = ref(false);

  // Computed - Estatísticas
  const statistics = computed(() => {
    return {
      total: demandas.value.length,
      abertas: demandas.value.filter(d => d.status === 'aberta').length,
      em_andamento: demandas.value.filter(d => d.status === 'em_andamento').length,
      concluidas: demandas.value.filter(d => d.status === 'concluida').length,
    };
  });

  // Computed - Demandas filtradas
  const filteredDemandas = computed(() => {
    let result = [...demandas.value];

    // Filtro de busca
    if (filters.value.search) {
      const searchLower = filters.value.search.toLowerCase();
      result = result.filter(
        d =>
          d.titulo.toLowerCase().includes(searchLower) ||
          d.codigo.toLowerCase().includes(searchLower) ||
          d.descricao.toLowerCase().includes(searchLower)
      );
    }

    // Filtro de tipo
    if (filters.value.tipo) {
      result = result.filter(d => d.tipo === filters.value.tipo);
    }

    // Filtro de prioridade
    if (filters.value.prioridade) {
      result = result.filter(d => d.prioridade === filters.value.prioridade);
    }

    // Filtro de status
    if (filters.value.status) {
      result = result.filter(d => d.status === filters.value.status);
    }

    // Filtro de responsável
    if (filters.value.responsavel_id) {
      result = result.filter(d => d.responsavel_id === filters.value.responsavel_id);
    }

    return result;
  });

  // Computed - Paginação
  const paginatedDemandas = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredDemandas.value.slice(start, end);
  });

  const totalPages = computed(() => {
    return Math.ceil(filteredDemandas.value.length / perPage.value);
  });

  // Actions
  const createDemanda = (data) => {
    const newId = Math.max(...demandas.value.map(d => d.id)) + 1;
    const newCodigo = `DEM-2025-${String(newId).padStart(3, '0')}`;

    const newDemanda = {
      id: newId,
      codigo: newCodigo,
      titulo: data.titulo,
      tipo: data.tipo,
      prioridade: data.prioridade,
      status: 'aberta',
      descricao: data.descricao,
      responsavel_id: data.responsavel_id || null,
      responsavel_nome: getResponsavelNome(data.responsavel_id),
      solicitante_id: '1', // Mock - usuário atual
      solicitante_nome: 'Usuário Atual',
      prazo: data.prazo || null,
      tags: data.tags || [],
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    demandas.value.unshift(newDemanda);
    return newDemanda;
  };

  const updateDemanda = (id, data) => {
    const index = demandas.value.findIndex(d => d.id === id);
    if (index !== -1) {
      demandas.value[index] = {
        ...demandas.value[index],
        ...data,
        responsavel_nome: data.responsavel_id ? getResponsavelNome(data.responsavel_id) : null,
        updated_at: new Date().toISOString(),
      };
      return demandas.value[index];
    }
    return null;
  };

  const deleteDemanda = (id) => {
    const index = demandas.value.findIndex(d => d.id === id);
    if (index !== -1) {
      demandas.value.splice(index, 1);
      return true;
    }
    return false;
  };

  const updateStatus = (id, newStatus) => {
    return updateDemanda(id, { status: newStatus });
  };

  const setFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
    currentPage.value = 1; // Reset para primeira página ao filtrar
  };

  const clearFilters = () => {
    filters.value = {
      search: '',
      tipo: '',
      prioridade: '',
      status: '',
      responsavel_id: '',
    };
    currentPage.value = 1;
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
    }
  };

  // Helpers
  const getResponsavelNome = (responsavelId) => {
    const responsaveis = {
      '1': 'João Silva',
      '2': 'Maria Santos',
      '3': 'Pedro Oliveira',
    };
    return responsaveis[responsavelId] || null;
  };

  const getTipoLabel = (tipo) => {
    const labels = {
      tarefa: 'Tarefa',
      bug: 'Bug',
      melhoria: 'Melhoria',
      suporte: 'Suporte',
    };
    return labels[tipo] || tipo;
  };

  const getPrioridadeLabel = (prioridade) => {
    const labels = {
      baixa: 'Baixa',
      media: 'Média',
      alta: 'Alta',
      critica: 'Crítica',
    };
    return labels[prioridade] || prioridade;
  };

  const getStatusLabel = (status) => {
    const labels = {
      aberta: 'Aberta',
      em_andamento: 'Em Andamento',
      concluida: 'Concluída',
      cancelada: 'Cancelada',
    };
    return labels[status] || status;
  };

  return {
    // State
    demandas: paginatedDemandas,
    allDemandas: demandas,
    filters,
    currentPage,
    perPage,
    totalPages,
    loading,
    statistics,

    // Actions
    createDemanda,
    updateDemanda,
    deleteDemanda,
    updateStatus,
    setFilters,
    clearFilters,
    goToPage,

    // Helpers
    getTipoLabel,
    getPrioridadeLabel,
    getStatusLabel,
  };
}
