<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <PaeFormTabs
      :active-tab="activeSubTab"
      :tabs="tabConfig"
      @tab-change="activeSubTab = $event"
    />

    <fieldset :disabled="viewOnly" class="contents">
    <div v-if="activeSubTab === 1">
      <PaeFormInfoGerais
        :model-value="rat.infoGerais"
        :municipios="municipios"
        :saving="rat.saving"
        :view-only="viewOnly"
        @save="handleSaveInfoGerais"
      />
    </div>

    <div v-else-if="activeSubTab === 2">
      <PaeFormObjetivoContexto
        :model-value="rat.objetivoContexto"
        :saving="rat.saving"
        :view-only="viewOnly"
        @save="handleSaveObjetivo"
      />
    </div>

    <div v-else-if="activeSubTab === 3">
      <PaeFormApontamentos
        :items="rat.apontamentos"
        :saving="rat.saving"
        :view-only="viewOnly"
        @save="handleSaveApontamentos"
        @add-item="rat.addItem('apontamentos')"
        @remove-item="(i) => rat.removeItem('apontamentos', i)"
        @add-sub="(i) => rat.addSubItem('apontamentos', i)"
        @remove-sub="(i, j) => rat.removeSubItem('apontamentos', i, j)"
      />
    </div>

    <div v-else-if="activeSubTab === 4">
      <PaeFormAnexos
        :items="rat.anexos"
        :formulario-id="formularioId"
        :saving="rat.saving"
        :progress="rat.anexoProgress"
        :status-message="rat.anexoStatus"
        :error-message="rat.anexoError"
        :view-only="viewOnly"
        @upload="handleUploadAnexo"
        @remove="handleRemoveAnexo"
        @download="handleDownloadAnexo"
      />
    </div>

    <div v-else-if="activeSubTab === 5">
      <PaeFormConclusao
        :items="rat.conclusao"
        :saving="rat.saving"
        :view-only="viewOnly"
        @save="handleSaveConclusao"
        @finalizar="handleFinalizar"
        @add-item="rat.addItem('conclusao')"
        @remove-item="(i) => rat.removeItem('conclusao', i)"
        @add-sub="(i) => rat.addSubItem('conclusao', i)"
        @remove-sub="(i, j) => rat.removeSubItem('conclusao', i, j)"
      />
    </div>
    </fieldset>
  </div>
</template>

<script setup>
import { ref, h, reactive, computed } from 'vue';
import { usePaeFormulario } from '@/composables/pae/usePaeFormulario';
import { usePage } from '@inertiajs/vue3';
import PaeFormTabs from './PaeFormTabs.vue';
import PaeFormInfoGerais from './PaeFormInfoGerais.vue';
import PaeFormObjetivoContexto from './PaeFormObjetivoContexto.vue';
import PaeFormApontamentos from './PaeFormApontamentos.vue';
import PaeFormAnexos from './PaeFormAnexos.vue';
import PaeFormConclusao from './PaeFormConclusao.vue';

function svgIcon(d) {
  return {
    render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d }),
    ]),
  };
}

const props = defineProps({
  empreendimento: {
    type: Object,
    default: () => ({}),
  },
  municipios: {
    type: Object,
    default: () => ({}),
  },
  formulario: {
    type: Object,
    default: null,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const activeSubTab = ref(1);
const page = usePage();

// ID local: captura o ID retornado pelo backend após o primeiro save (POST)
// e tem prioridade sobre os props (que podem ainda não ter sido atualizados)
const localFormularioId = ref(props.formulario?.id ?? null);

const rat = reactive(usePaeFormulario(props.empreendimento, props.formulario));

const tabConfig = [
  {
    id: 1,
    label: 'Informações Gerais',
    icon: svgIcon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'),
  },
  {
    id: 2,
    label: 'Objetivo e Contexto',
    icon: svgIcon('M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'),
  },
  {
    id: 3,
    label: 'Apontamentos Técnicos',
    icon: svgIcon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'),
  },
  {
    id: 4,
    label: 'Anexos',
    icon: svgIcon('M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0-12l-4 4m4-4l4 4'),
  },
  {
    id: 5,
    label: 'Conclusão',
    icon: svgIcon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'),
  },
];

const formularioId = computed(
  () => localFormularioId.value
    ?? props.formulario?.id
    ?? props.empreendimento?.formulario_id
    ?? null
);

function handleSaveInfoGerais(data) {
  Object.assign(rat.infoGerais, data);
  // Após o POST de criação, o Inertia recarrega a página com o novo formulario
  // no prop. Capturamos o ID e avançamos para a próxima aba automaticamente.
  rat.saveInfoGerais(formularioId.value, (newId) => {
    if (newId) {
      localFormularioId.value = newId;
      activeSubTab.value = 2;
    }
  });
}

function handleSaveObjetivo(data) {
  Object.assign(rat.objetivoContexto, data);
  rat.saveObjetivoContexto(formularioId.value);
}

function handleSaveApontamentos() {
  rat.saveApontamentos(formularioId.value);
}

function handleUploadAnexo(payload) {
  const { onSuccess, onError, ...data } = payload;
  rat.uploadAnexo(formularioId.value, data, { onSuccess, onError });
}

function handleRemoveAnexo(anexoId) {
  rat.deleteAnexo(formularioId.value, anexoId);
}

function handleDownloadAnexo(anexoId) {
  rat.downloadAnexo(formularioId.value, anexoId);
}

function handleSaveConclusao() {
  rat.saveConclusao(formularioId.value);
}

function handleFinalizar() {
  rat.finalizarRelatorio(formularioId.value);
}
</script>
