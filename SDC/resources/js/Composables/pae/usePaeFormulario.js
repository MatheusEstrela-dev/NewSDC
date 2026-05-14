import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const OBJETIVO_DEFAULT = 'Analisar os requisitos necessários para a aprovação da Segunda Seção do Plano de Ação de Emergência, relativos à competência do órgão Estadual de Proteção e Defesa Civil, expressa no Decreto Estadual n. 48.078, de 05 de novembro de 2020 e notificar o empreendedor sobre as inconsistências observadas para devida correção.';

const CONTEXTUALIZACAO_DEFAULT = 'O PAE é analisado conforme a Resolução GMG n. 83/2024, além da legislação estadual e federal vigentes. Após a sua aprovação, será emitido o Certificado de Conformidade do Plano de Ação de Emergência (CCPAE) pelo Coordenador Estadual de Defesa Civil de Minas Gerais.\n\nA emissão do CCPAE está vinculada à análise de um cenário hipotético, no qual os detalhes específicos serão descritos em um relatório relacionado à estrutura analisada. A barragem poderá ser vistoriada a qualquer tempo pelos órgãos fiscalizadores federais e estaduais e caso sejam constatadas irregularidades previstas em legislação, o CCPAE poderá ser revogado.';

/**
 * Composable do formulário RAT do PAE
 * Single Responsibility: State e operações do formulário de análise técnica
 */
export function usePaeFormulario(empreendimento = {}, formulario = null) {
  const saving = ref(false);
  const anexoProgress = ref(0);
  const anexoStatus = ref('');
  const anexoError = ref('');
  const { show: toast } = useToast();
  let nextId = 10;

  function makeId() {
    return nextId++;
  }

  const infoGerais = ref({
    barragem:                formulario?.barragem                ?? empreendimento?.nome               ?? '',
    municipio_id:            formulario?.municipio_id             ?? empreendimento?.municipio_id       ?? '',
    pae_empnto_id:           formulario?.pae_empnto_id            ?? empreendimento?.id                 ?? '',
    pae_protocolo_id:        formulario?.pae_protocolo_id         ?? '',
    coordenador_pae:         formulario?.coordenador_pae          ?? empreendimento?.coordenador        ?? '',
    email:                   formulario?.email                    ?? empreendimento?.email_coord        ?? '',
    coordenador_mun_def_civ: formulario?.coordenador_mun_def_civ  ?? '',
    coordenador_mun_compdec: formulario?.coordenador_mun_compdec  ?? '',
    empreendedor_res:        formulario?.empreendedor_res         ?? empreendimento?.empreendedor?.nome ?? '',
    metodo_construtivo:      formulario?.metodo_construtivo       ?? empreendimento?.m_construcao      ?? '',
    numero_zas:              formulario?.numero_zas               ?? empreendimento?.pop_zas            ?? '',
    nivel_emergencia:        formulario?.nivel_emergencia         ?? '',
  });

  const objetivoContexto = ref({
    objetivo:          formulario?.objetivo         ?? OBJETIVO_DEFAULT,
    contextualizacao:  formulario?.contextualizacao ?? CONTEXTUALIZACAO_DEFAULT,
  });

  const apontamentos = ref(
    formulario?.apontamentos?.length
      ? formulario.apontamentos
      : [{ id: makeId(), text: '', children: [] }]
  );

  const conclusao = ref(
    formulario?.conclusao?.length
      ? formulario.conclusao
      : [{ id: makeId(), text: '', children: [] }]
  );

  const anexos = ref(formulario?.anexos ?? []);

  function syncAnexosFromPage(page) {
    const updated = page.props?.formulario?.anexos;
    if (Array.isArray(updated)) {
      anexos.value = updated;
    }
  }

  function clearAnexoFeedback() {
    anexoProgress.value = 0;
    anexoStatus.value = '';
    anexoError.value = '';
  }

  function firstErrorMessage(errors, fallback) {
    if (!errors) return fallback;
    if (typeof errors === 'string') return errors;

    const first = Object.values(errors)[0];
    if (Array.isArray(first)) {
      return first[0] ?? fallback;
    }

    return first ? String(first) : fallback;
  }

  function saveInfoGerais(id, onCreated) {
    saving.value = true;
    if (!id) {
      router.post('/pae/formulario', infoGerais.value, {
        onSuccess: (page) => {
          toast('Informações gerais salvas com sucesso.');
          const newId = page.props?.formulario?.id ?? null;
          if (onCreated) onCreated(newId);
        },
        onFinish: () => { saving.value = false; },
      });
    } else {
      router.put(`/pae/formulario/${id}/infogerais`, infoGerais.value, {
        onSuccess: () => toast('Informações gerais atualizadas.'),
        onFinish: () => { saving.value = false; },
      });
    }
  }

  function saveObjetivoContexto(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/objetivo`, objetivoContexto.value, {
      onSuccess: () => toast('Objetivo e contextualização atualizados.'),
      onFinish: () => { saving.value = false; },
    });
  }

  function saveApontamentos(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/aptecnico`, { apontamentos: apontamentos.value }, {
      onSuccess: () => toast('Apontamentos técnicos salvos.'),
      onFinish: () => { saving.value = false; },
    });
  }

  function saveConclusao(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/conclusao`, { conclusao: conclusao.value }, {
      onSuccess: () => toast('Conclusão salva.'),
      onFinish: () => { saving.value = false; },
    });
  }

  function uploadAnexo(id, payload, callbacks = {}) {
    clearAnexoFeedback();

    if (!id) {
      const message = 'Salve as Informacoes Gerais antes de adicionar anexos.';
      anexoError.value = message;
      toast(message);
      callbacks.onError?.(message);
      return;
    }

    saving.value = true;
    anexoProgress.value = 8;
    anexoStatus.value = 'Validando arquivo...';

    router.post(`/pae/formulario/${id}/anexos`, payload, {
      preserveScroll: true,
      forceFormData: true,
      onStart: () => {
        anexoProgress.value = 12;
        anexoStatus.value = 'Enviando anexo...';
      },
      onProgress: (progress) => {
        if (!progress?.percentage) return;

        anexoProgress.value = Math.max(12, Math.min(95, Math.round(progress.percentage)));
        anexoStatus.value = anexoProgress.value >= 95 ? 'Processando anexo...' : 'Enviando anexo...';
      },
      onSuccess: (page) => {
        anexoProgress.value = 100;
        anexoStatus.value = 'Anexo salvo.';
        syncAnexosFromPage(page);
        toast('Anexo salvo.');
        callbacks.onSuccess?.();
      },
      onError: (errors) => {
        const message = firstErrorMessage(errors, 'Nao foi possivel adicionar o anexo. Tente novamente.');
        anexoError.value = message;
        anexoStatus.value = 'Falha ao adicionar anexo.';
        toast(message);
        callbacks.onError?.(message);
      },
      onCancel: () => {
        const message = 'Envio cancelado antes da conclusao.';
        anexoError.value = message;
        anexoStatus.value = 'Envio cancelado.';
        callbacks.onError?.(message);
      },
      onFinish: () => {
        saving.value = false;
        if (!anexoError.value) {
          window.setTimeout(() => {
            anexoProgress.value = 0;
            anexoStatus.value = '';
          }, 900);
        }
      },
    });
  }

  function deleteAnexo(id, anexoId) {
    if (!id || !anexoId) return;

    clearAnexoFeedback();
    saving.value = true;
    anexoProgress.value = 35;
    anexoStatus.value = 'Removendo anexo...';

    router.delete(`/pae/formulario/${id}/anexos/${anexoId}`, {
      preserveScroll: true,
      onSuccess: (page) => {
        anexoProgress.value = 100;
        anexoStatus.value = 'Anexo removido.';
        syncAnexosFromPage(page);
        toast('Anexo removido.');
      },
      onError: (errors) => {
        const message = firstErrorMessage(errors, 'Nao foi possivel remover o anexo. Tente novamente.');
        anexoError.value = message;
        anexoStatus.value = 'Falha ao remover anexo.';
        toast(message);
      },
      onFinish: () => {
        saving.value = false;
        if (!anexoError.value) {
          window.setTimeout(() => {
            anexoProgress.value = 0;
            anexoStatus.value = '';
          }, 900);
        }
      },
    });
  }

  function downloadAnexo(id, anexoId) {
    if (!id || !anexoId) return;

    window.location.assign(`/pae/formulario/${id}/anexos/${anexoId}/download`);
  }

  function finalizarRelatorio(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/finalizar`, { conclusao: conclusao.value }, {
      onSuccess: () => toast('Relatório finalizado com sucesso.'),
      onFinish: () => { saving.value = false; },
    });
  }

  function getList(section) {
    return section === 'apontamentos' ? apontamentos : conclusao;
  }

  function addItem(section) {
    getList(section).value.push({ id: makeId(), text: '', children: [] });
  }

  function removeItem(section, index) {
    const list = getList(section).value;
    if (list.length > 1) list.splice(index, 1);
  }

  function addSubItem(section, itemIndex) {
    getList(section).value[itemIndex].children.push({ id: makeId(), text: '' });
  }

  function removeSubItem(section, itemIndex, subIndex) {
    getList(section).value[itemIndex].children.splice(subIndex, 1);
  }

  return {
    saving,
    anexoProgress,
    anexoStatus,
    anexoError,
    infoGerais,
    objetivoContexto,
    apontamentos,
    anexos,
    conclusao,
    saveInfoGerais,
    saveObjetivoContexto,
    saveApontamentos,
    uploadAnexo,
    deleteAnexo,
    downloadAnexo,
    saveConclusao,
    finalizarRelatorio,
    addItem,
    removeItem,
    addSubItem,
    removeSubItem,
  };
}
