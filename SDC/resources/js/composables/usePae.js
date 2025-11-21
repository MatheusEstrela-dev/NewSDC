import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTabs } from './useTabs';
import { useDocuments } from './useDocuments';
import { useModal } from './useModal';

/**
 * Composable principal do PAE
 * Orchestrates outros composables e gerencia dados do PAE
 * Single Responsibility: Coordenar lógica do PAE
 */
export function usePae(initialData = {}) {
  const tabs = useTabs(initialData.activeTab || 1);
  const documents = useDocuments(initialData.documents || []);
  const modal = useModal();

  // Dados do empreendimento
  const empreendimento = ref(initialData.empreendimento || {
    id: null,
    nome: 'Barragem Sul Superior',
    tipo: 'Barragem de Rejeitos',
    municipio: 'Itabirito',
    coordenadas: { lat: -20.2547, lng: -43.8011 },
    nivelEmergencia: 1,
    protocolo: '2024.10.15.0081',
    status: 'aprovado',
    dataEmissao: '2024-10-15',
    proximoVencimento: '2025-10-15',
  });

  // Histórico de eventos
  const historyEvents = ref(
    initialData.historyEvents && Array.isArray(initialData.historyEvents) && initialData.historyEvents.length > 0
      ? initialData.historyEvents
      : [
    {
      id: 1,
      tipo: 'submissao',
      titulo: 'PAE Submetido para Análise',
      descricao: 'Submetido por Matheus Kevin. Documentos: PAE_v3.pdf, Mapa_Atualizado.kml.',
      data: '01/11/2025 - 10:32',
      protocolo: '2025.11.01.0092',
      autor: 'Matheus Kevin',
    },
    {
      id: 2,
      tipo: 'vencimento',
      titulo: 'Notificação de Vencimento',
      descricao: 'Sistema enviou notificação automática de vencimento do PAE para o empreendedor.',
      data: '25/10/2025 - 09:15',
      protocolo: null,
      autor: 'Sistema SDC',
    },
    {
      id: 3,
      tipo: 'aprovacao',
      titulo: 'PAE Aprovado',
      descricao: 'Plano de Ação de Emergência aprovado.',
      data: '15/10/2024 - 14:00',
      protocolo: '2024.10.15.0081',
      autor: 'Analista SDC',
    },
    {
      id: 4,
      tipo: 'cadastro',
      titulo: 'Empreendimento Cadastrado',
      descricao: 'Cadastro inicial do empreendimento no sistema SDC.',
      data: '01/08/2024 - 11:05',
      protocolo: null,
      autor: 'Equipe Técnica',
    },
    {
      id: 5,
      tipo: 'submissao',
      titulo: 'Revisão de Documentos Solicitada',
      descricao: 'Solicitada revisão de documentos para o PAE. Pendências: Atualização do mapa de risco.',
      data: '28/09/2024 - 16:00',
      protocolo: '2024.09.28.0045',
      autor: 'Dr. Carlos Silva',
    },
    {
      id: 6,
      tipo: 'aprovacao',
      titulo: 'Vistoria Técnica Realizada',
      descricao: 'Vistoria técnica do empreendimento realizada com sucesso. Relatório: Vistoria_2024_09_15.pdf.',
      data: '15/09/2024 - 09:00',
      protocolo: '2024.09.15.0023',
      autor: 'Eng. Ana Paula',
    },
  ]);

  // Membros do comitê
  const committeeMembers = ref(initialData.committeeMembers || [
    {
      id: 1,
      nome: 'João da Silva',
      funcao: 'Coordenador',
      orgao: 'Prefeitura de Itabirito',
      telefone: '(31) 3541-1234',
      email: 'joao.silva@itabirito.mg.gov.br',
    },
    {
      id: 2,
      nome: 'Maria Oliveira',
      funcao: 'Secretário',
      orgao: 'Mineração Rio Verde',
      telefone: '(31) 98765-4321',
      email: 'maria.oliveira@mineracaorioverde.com.br',
    },
    {
      id: 3,
      nome: 'Cap. Carlos Pereira',
      funcao: 'Membro',
      orgao: 'Corpo de Bombeiros MG',
      telefone: '(31) 3429-5678',
      email: 'carlos.pereira@bombeiros.mg.gov.br',
    },
  ]);

  // Atas e reuniões
  const atas = ref(initialData.atas || [
    {
      id: 1,
      titulo: 'Reunião de Alinhamento',
      data: '20/09/2024',
      pauta: 'Discussão sobre novos sistemas de alerta sonoro na ZAS.',
      arquivo: 'Ata_20092024.pdf',
    },
    {
      id: 2,
      titulo: 'Reunião Extraordinária - Simulacro',
      data: '15/08/2024',
      pauta: 'Planejamento e avaliação do simulacro de evacuação realizado em agosto.',
      arquivo: 'Ata_Simulacro_15082024.pdf',
    },
    {
      id: 3,
      titulo: 'Reunião Mensal - CCPAE',
      data: '05/07/2024',
      pauta: 'Revisão do Plano de Ação de Emergência e atualização de contatos de emergência.',
      arquivo: 'Ata_CCPAE_05072024.pdf',
    },
  ]);

  // Dados do empreendedor
  const empreendedor = ref(initialData.empreendedor || {
    razaoSocial: 'Mineração Rio Verde S.A.',
    cnpj: '12.345.678/0001-99',
    endereco: 'Rua das Minas, 1000, Distrito Industrial, Belo Horizonte - MG',
    responsavelTecnico: {
      nome: 'Dr. Ricardo Almeida',
      cpf: '***.123.456-**',
    },
  });

  /**
   * Salva alterações do formulário PAE
   */
  function savePae() {
    router.put(`/pae/${empreendimento.value.id}`, {
      protocolo: empreendimento.value.protocolo,
      status: empreendimento.value.status,
      dataEmissao: empreendimento.value.dataEmissao,
      proximoVencimento: empreendimento.value.proximoVencimento,
    }, {
      onSuccess: () => {
        // Mostrar mensagem de sucesso
      },
      onError: (errors) => {
        // Tratar erros
      },
    });
  }

  /**
   * Salva como rascunho
   */
  function saveDraft() {
    router.put(`/pae/${empreendimento.value.id}/draft`, {
      protocolo: empreendimento.value.protocolo,
      status: 'rascunho',
    });
  }

  /**
   * Arquivar empreendimento
   */
  function archiveEmpreendimento() {
    if (confirm('Tem certeza que deseja arquivar este empreendimento?')) {
      router.put(`/pae/${empreendimento.value.id}/archive`);
    }
  }

  /**
   * Adiciona membro ao comitê
   */
  function addCommitteeMember(membro) {
    committeeMembers.value.push({
      id: Date.now(),
      ...membro,
    });
  }

  /**
   * Remove membro do comitê
   */
  function removeCommitteeMember(id) {
    committeeMembers.value = committeeMembers.value.filter(m => m.id !== id);
  }

  /**
   * Atualiza dados do empreendedor
   */
  function updateEmpreendedor(data) {
    router.put(`/pae/${empreendimento.value.id}/empreendedor`, data);
  }

  /**
   * Busca dados atualizados do PAE
   */
  function refreshPaeData() {
    router.reload({
      only: ['empreendimento', 'historyEvents', 'committeeMembers', 'empreendedor', 'documents'],
    });
  }

  return {
    // State
    empreendimento,
    historyEvents,
    committeeMembers,
    empreendedor,
    atas,

    // Composables
    tabs,
    documents,
    modal,

    // Methods
    savePae,
    saveDraft,
    archiveEmpreendimento,
    addCommitteeMember,
    removeCommitteeMember,
    updateEmpreendedor,
    refreshPaeData,
  };
}

