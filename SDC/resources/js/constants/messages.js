/**
 * Constantes de mensagens da aplicação
 * Centralização de strings para facilitar manutenção e i18n
 */

export const MESSAGES = {
  // Modulos
  rat: {
    title: 'Gestão de RAT',
    description: 'Visualize e gerencie todos os Registros de Atendimento Técnico',
    empty: 'Nenhum RAT encontrado',
    loading: 'Carregando RATs...',
  },
  pae: {
    title: 'Protocolos PAE',
    description: 'Gerencie os protocolos de análise de PAE',
    empty: 'Nenhum protocolo encontrado',
    loading: 'Carregando protocolos...',
  },
  decretacoes: {
    title: 'Decretações',
    description: 'Gerencie as decretações de emergência e calamidade',
    empty: 'Nenhuma decretação encontrada',
    loading: 'Carregando decretações...',
  },
  ajudaHumanitaria: {
    title: 'Ajuda Humanitária',
    description: 'Gerencie os beneficiários de ajuda humanitária',
    empty: 'Nenhum beneficiário encontrado',
    loading: 'Carregando beneficiários...',
  },
  treinamento: {
    title: 'Treinamentos',
    description: 'Gerencie os treinamentos e capacitações',
    empty: 'Nenhum treinamento encontrado',
    loading: 'Carregando treinamentos...',
  },
  demandas: {
    title: 'Demandas',
    description: 'Gerencie as demandas e solicitações',
    empty: 'Nenhuma demanda encontrada',
    loading: 'Carregando demandas...',
  },
  tdap: {
    title: 'TDAP',
    description: 'Gerencie equipamentos e patrimônio',
    empty: 'Nenhum item encontrado',
    loading: 'Carregando itens...',
  },
  compdec: {
    title: 'COMPDEC',
    description: 'Gerencie as coordenadorias municipais',
    empty: 'Nenhuma coordenadoria encontrada',
    loading: 'Carregando coordenadorias...',
  },

  // Footer
  footer: {
    organization: 'CEDEC - Defesa Civil de Minas Gerais',
    copyright: (year = new Date().getFullYear()) => `\u00a9 ${year} Todos os direitos reservados.`,
  },

  // Confirmações
  confirmations: {
    delete: 'Tem certeza que deseja excluir este item?',
    deleteRat: 'Tem certeza que deseja excluir este RAT?',
    deleteProtocolo: 'Tem certeza que deseja excluir este protocolo?',
    deleteDecretacao: 'Tem certeza que deseja excluir esta decretação?',
    deleteBeneficiario: 'Tem certeza que deseja excluir este beneficiário?',
    cancel: 'Tem certeza que deseja cancelar? As alterações não salvas serão perdidas.',
    logout: 'Tem certeza que deseja sair?',
  },

  // Erros
  errors: {
    generic: 'Ocorreu um erro. Por favor, tente novamente.',
    network: 'Erro de conexão. Verifique sua internet.',
    notFound: 'Registro não encontrado.',
    unauthorized: 'Você não tem permissão para realizar esta ação.',
    validation: 'Por favor, corrija os erros no formulário.',
    loadData: (module) => `Erro ao carregar dados de ${module}.`,
    saveData: 'Erro ao salvar dados.',
    deleteData: 'Erro ao excluir dados.',
  },

  // Sucesso
  success: {
    saved: 'Dados salvos com sucesso!',
    deleted: 'Registro excluído com sucesso!',
    updated: 'Registro atualizado com sucesso!',
    created: 'Registro criado com sucesso!',
  },

  // Ações
  actions: {
    save: 'Salvar',
    cancel: 'Cancelar',
    delete: 'Excluir',
    edit: 'Editar',
    view: 'Visualizar',
    create: 'Criar',
    search: 'Buscar',
    filter: 'Filtrar',
    export: 'Exportar',
    print: 'Imprimir',
    back: 'Voltar',
    next: 'Próximo',
    previous: 'Anterior',
    confirm: 'Confirmar',
    close: 'Fechar',
    loading: 'Carregando...',
  },

  // Visualização
  viewModes: {
    grid: 'Visualização em Grade',
    table: 'Visualização em Tabela',
    list: 'Visualização em Lista',
  },

  // Status
  status: {
    active: 'Ativo',
    inactive: 'Inativo',
    pending: 'Pendente',
    approved: 'Aprovado',
    rejected: 'Rejeitado',
    inProgress: 'Em Andamento',
    completed: 'Concluído',
    cancelled: 'Cancelado',
  },
};

/**
 * Constantes de cores do Tailwind para consistencia
 */
export const COLOR_CLASSES = {
  text: {
    primary: 'text-slate-900 dark:text-slate-100',
    secondary: 'text-slate-600 dark:text-slate-400',
    muted: 'text-slate-500 dark:text-slate-500',
    light: 'text-slate-700 dark:text-slate-300',
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    danger: 'text-red-600 dark:text-red-400',
    info: 'text-blue-600 dark:text-blue-400',
  },
  bg: {
    card: 'bg-white dark:bg-slate-900',
    cardHover: 'bg-slate-50 dark:bg-slate-800',
    overlay: 'bg-black/50 dark:bg-black/75',
    input: 'bg-slate-50 dark:bg-slate-800',
    success: 'bg-green-100 dark:bg-green-900/30',
    warning: 'bg-yellow-100 dark:bg-yellow-900/30',
    danger: 'bg-red-100 dark:bg-red-900/30',
    info: 'bg-blue-100 dark:bg-blue-900/30',
  },
  border: {
    default: 'border-slate-200 dark:border-slate-700',
    light: 'border-slate-100 dark:border-slate-800',
    focus: 'border-blue-500 dark:border-blue-400',
    success: 'border-green-500 dark:border-green-400',
    warning: 'border-yellow-500 dark:border-yellow-400',
    danger: 'border-red-500 dark:border-red-400',
  },
};

/**
 * Opcoes padrao para ViewModeToggle
 */
export const VIEW_MODE_OPTIONS = {
  default: [
    { value: 'grid', label: 'Grade', title: 'Visualizacao em Grade' },
    { value: 'table', label: 'Tabela', title: 'Visualizacao em Tabela' },
  ],
  withList: [
    { value: 'grid', label: 'Grade', title: 'Visualizacao em Grade' },
    { value: 'table', label: 'Tabela', title: 'Visualizacao em Tabela' },
    { value: 'list', label: 'Lista', title: 'Visualizacao em Lista' },
  ],
};
