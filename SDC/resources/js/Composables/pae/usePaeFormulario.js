import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const OBJETIVO_DEFAULT = 'Analisar os requisitos necessários para a aprovação da Segunda Seção do Plano de Ação de Emergência, relativos à competência do órgão Estadual de Proteção e Defesa Civil, expressa no Decreto Estadual n. 48.078, de 05 de novembro de 2020 e notificar o empreendedor sobre as inconsistências observadas para devida correção.';

const CONTEXTUALIZACAO_DEFAULT = 'O PAE é analisado conforme a Resolução GMG n. 83/2024, além da legislação estadual e federal vigentes. Após a sua aprovação, será emitido o Certificado de Conformidade do Plano de Ação de Emergência (CCPAE) pelo Coordenador Estadual de Defesa Civil de Minas Gerais.\n\nA emissão do CCPAE está vinculada à análise de um cenário hipotético, no qual os detalhes específicos serão descritos em um relatório relacionado à estrutura analisada. A barragem poderá ser vistoriada a qualquer tempo pelos órgãos fiscalizadores federais e estaduais e caso sejam constatadas irregularidades previstas em legislação, o CCPAE poderá ser revogado.';

/**
 * Composable do formulário RAT do PAE
 * Single Responsibility: State e operações do formulário de análise técnica
 */
export function usePaeFormulario(empreendimento = {}, formulario = null) {
  const saving = ref(false);
  let nextId = 10;

  function makeId() {
    return nextId++;
  }

  const infoGerais = ref({
    barragem:                formulario?.barragem                ?? empreendimento?.nome            ?? '',
    municipio_id:            formulario?.municipio_id             ?? empreendimento?.municipio_id    ?? '',
    coordenador_pae:         formulario?.coordenador_pae          ?? empreendimento?.coordenador     ?? '',
    email:                   formulario?.email                    ?? empreendimento?.email_coord     ?? '',
    coordenador_mun_def_civ: formulario?.coordenador_mun_def_civ  ?? '',
    coordenador_mun_compdec: formulario?.coordenador_mun_compdec  ?? '',
    empreendedor_res:        formulario?.empreendedor_res         ?? empreendimento?.empreendedor?.nome ?? '',
    metodo_construtivo:      formulario?.metodo_construtivo       ?? empreendimento?.m_construcao   ?? '',
    numero_zas:              formulario?.numero_zas               ?? empreendimento?.pop_zas         ?? '',
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

  function saveInfoGerais(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/infogerais`, infoGerais.value, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveObjetivoContexto(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/objetivo`, objetivoContexto.value, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveApontamentos(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/aptecnico`, { apontamentos: apontamentos.value }, {
      onFinish: () => { saving.value = false; },
    });
  }

  function saveConclusao(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/conclusao`, { conclusao: conclusao.value }, {
      onFinish: () => { saving.value = false; },
    });
  }

  function finalizarRelatorio(id) {
    saving.value = true;
    router.put(`/pae/formulario/${id}/finalizar`, { conclusao: conclusao.value }, {
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
    infoGerais,
    objetivoContexto,
    apontamentos,
    conclusao,
    saveInfoGerais,
    saveObjetivoContexto,
    saveApontamentos,
    saveConclusao,
    finalizarRelatorio,
    addItem,
    removeItem,
    addSubItem,
    removeSubItem,
  };
}
