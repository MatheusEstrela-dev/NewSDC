import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTabs } from './useTabs';
import { useModal } from './useModal';

/**
 * Composable principal do RAT
 * Orchestrates outros composables e gerencia dados do RAT
 * Single Responsibility: Coordenar lógica do RAT
 */
export function useRat(initialData = {}) {
  const tabs = useTabs(initialData.activeTab || 1);
  const modal = useModal();

  // Dados do RAT
  const rat = ref(initialData.rat || {
    id: null,
    protocolo: '',
    status: 'rascunho',
    tem_vistoria: false,
    dadosGerais: {
      data_fato: '',
      data_inicio_atividade: '',
      data_termino_atividade: '',
      nat_cobrade_id: '',
      nat_nome_operacao: '',
      local_municipio: '',
    },
  });

  // Recursos empregados
  const recursos = ref(initialData.recursos || []);

  // Pessoas envolvidas
  const envolvidos = ref(initialData.envolvidos || []);

  // Dados de vistoria
  const vistoria = ref(initialData.vistoria || {});

  // Histórico de eventos
  const historyEvents = ref(initialData.historyEvents || [
    {
      id: 1,
      tipo: 'criacao',
      titulo: 'Rascunho criado',
      descricao: 'Rascunho criado pelo usuário',
      data: new Date().toLocaleString('pt-BR'),
      autor: 'Sistema',
    },
  ]);

  /**
   * Salva o RAT
   */
  function saveRat(data) {
    // TODO: Implementar chamada à API
    console.log('Salvar RAT:', data || rat.value);
    // router.post('/rat', rat.value);
  }

  /**
   * Salva como rascunho
   */
  function saveDraft(data) {
    // TODO: Implementar chamada à API
    console.log('Salvar rascunho:', data || rat.value);
    // router.post('/rat/draft', rat.value);
  }

  /**
   * Cancela o RAT
   */
  function cancelRat() {
    router.visit('/dashboard');
  }

  /**
   * Adiciona recurso
   */
  function addRecurso(recurso) {
    recursos.value.push({
      id: Date.now(),
      ...recurso,
    });
  }

  /**
   * Remove recurso
   */
  function removeRecurso(id) {
    const index = recursos.value.findIndex(r => r.id === id);
    if (index > -1) {
      recursos.value.splice(index, 1);
    }
  }

  /**
   * Adiciona envolvido
   */
  function addEnvolvido(envolvido) {
    envolvidos.value.push({
      id: Date.now(),
      ...envolvido,
    });
  }

  /**
   * Remove envolvido
   */
  function removeEnvolvido(id) {
    const index = envolvidos.value.findIndex(e => e.id === id);
    if (index > -1) {
      envolvidos.value.splice(index, 1);
    }
  }

  /**
   * Salva vistoria
   */
  function saveVistoria(data) {
    Object.assign(vistoria.value, data);
  }

  /**
   * Adiciona observação ao histórico
   */
  function addObservation(observation) {
    historyEvents.value.unshift({
      id: Date.now(),
      tipo: 'observacao',
      titulo: 'Nova observação',
      descricao: observation.texto || observation,
      data: new Date().toLocaleString('pt-BR'),
      autor: 'Usuário',
    });
  }

  return {
    // State
    rat,
    recursos,
    envolvidos,
    vistoria,
    historyEvents,

    // Composables
    tabs,
    modal,

    // Methods
    saveRat,
    saveDraft,
    cancelRat,
    addRecurso,
    removeRecurso,
    addEnvolvido,
    removeEnvolvido,
    saveVistoria,
    addObservation,
  };
}

