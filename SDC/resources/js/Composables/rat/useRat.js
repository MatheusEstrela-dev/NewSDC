import { db } from '@/infrastructure/database/db';
import { router } from '@inertiajs/vue3';
import { v4 as uuidv4 } from 'uuid';
import { ref } from 'vue';
import { useDocuments } from '@/composables/useDocuments';
import { useModal } from '../core/useModal';
import { useTabs } from '../core/useTabs';

export function useRat(initialData = {}) {
  const tabs = useTabs(initialData.activeTab || 1);
  const modal = useModal();
  const documents = useDocuments(initialData.anexos || []);

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

  const recursos = ref(initialData.recursos || []);
  const envolvidos = ref(initialData.envolvidos || []);
  const vistoria = ref(initialData.vistoria || {});
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

  async function saveRat(data) {
    const ratId = rat.value.id;

    const payload = {
      ...rat.value,
      recursos: recursos.value,
      envolvidos: envolvidos.value,
      vistoria: vistoria.value,
      ...data,
    };

    if (!payload.id) {
      payload.id = uuidv4();
      rat.value.id = payload.id;
    }

    if (!navigator.onLine) {
      try {
        await db.rat_pendentes.add({
          ...payload,
          sync_status: 'pending',
          created_at: new Date().toISOString(),
        });
        alert('Você está offline. O RAT foi salvo no dispositivo e será enviado quando houver conexão.');
      } catch (error) {
        console.error('Erro ao salvar offline:', error);
        alert('Erro ao salvar no dispositivo.');
      }
      return;
    }

    router.post(route('rat.sync'), payload, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        if (ratId) {
          documents.uploadDocuments(route('rat.anexos.store', ratId));
        }
      },
      onError: (errors) => {
        console.error('Erro no envio online:', errors);
      },
    });
  }

  async function saveDraft(data) {
    console.log('Salvar rascunho:', data || rat.value);
  }

  function cancelRat() {
    router.visit('/dashboard');
  }

  function addRecurso(recurso) {
    recursos.value.push({ id: Date.now(), ...recurso });
  }

  function removeRecurso(id) {
    const index = recursos.value.findIndex(r => r.id === id);
    if (index > -1) recursos.value.splice(index, 1);
  }

  function addEnvolvido(envolvido) {
    envolvidos.value.push({ id: Date.now(), ...envolvido });
  }

  function removeEnvolvido(id) {
    const index = envolvidos.value.findIndex(e => e.id === id);
    if (index > -1) envolvidos.value.splice(index, 1);
  }

  function saveVistoria(data) {
    Object.assign(vistoria.value, data);
  }

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
    rat,
    recursos,
    envolvidos,
    vistoria,
    historyEvents,
    documents,
    tabs,
    modal,
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
