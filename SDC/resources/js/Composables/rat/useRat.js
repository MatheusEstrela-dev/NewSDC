import { db } from '@/infrastructure/database/db';
import { router } from '@inertiajs/vue3';
import { v4 as uuidv4 } from 'uuid';
import { ref } from 'vue';
import { useModal } from '../core/useModal';
import { useTabs } from '../core/useTabs';

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

  // Anexos
  const anexos = ref(initialData.anexos || []);

  /**
   * Salva o RAT
   */
  async function saveRat(data) {
    const payload = {
      ...rat.value,
      recursos: recursos.value,
      envolvidos: envolvidos.value,
      vistoria: vistoria.value,
      anexos: anexos.value,
      ...data
    };

    // Garante ID para offline
    if (!payload.id) {
      payload.id = uuidv4();
      rat.value.id = payload.id;
    }

    if (!navigator.onLine) {
      try {
        await db.rat_pendentes.add({
          ...payload,
          sync_status: 'pending',
          created_at: new Date().toISOString()
        });
        alert('Você está offline. O RAT foi salvo no dispositivo e será enviado quando houver conexão.');
        // Opcional: Redirecionar para lista ou limpar form
      } catch (error) {
        alert('Erro ao salvar no dispositivo.');
      }
    } else {
      // Se estiver online, envia via Inertia
      router.post(route('rat.sync'), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Limpa ou atualiza estado se necessário
        },
        onError: (errors) => {
          // Fallback: se falhar por rede (não validação), salva offline?
          // Por simplicidade, mantemos o erro visível
        }
      });
    }
  }

  /**
   * Salva como rascunho
   */
  async function saveDraft(data) {
    // TODO: Implementar chamada à API
    // router.post('/rat/draft', rat.value);
  }

  /**
   * Cancela o RAT e retorna para a listagem
   */
  function cancelRat() {
    router.visit(route('rat.index'));
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

  /**
   * Adiciona anexo
   */
  function addAnexo(anexo) {
    anexos.value.push({
      id: Date.now(),
      ...anexo,
    });
  }

  /**
   * Remove anexo
   */
  function removeAnexo(id) {
    const index = anexos.value.findIndex(a => a.id === id);
    if (index > -1) {
      anexos.value.splice(index, 1);
    }
  }

  return {
    // State
    rat,
    recursos,
    envolvidos,
    vistoria,
    historyEvents,
    anexos,

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
    addAnexo,
    removeAnexo,
  };
}

