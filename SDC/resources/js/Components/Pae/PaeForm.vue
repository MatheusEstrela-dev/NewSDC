<template>
  <div class="space-y-4 sm:space-y-6 animate-fade-in-up">
    <PaeFormTabs
      :active-tab="activeSubTab"
      :tabs="tabConfig"
      @tab-change="activeSubTab = $event"
    />

    <div v-if="activeSubTab === 1">
      <PaeFormInfoGerais
        :model-value="rat.infoGerais"
        :municipios="municipios"
        :saving="rat.saving"
        :read-only="readOnly"
        @save="handleSaveInfoGerais"
      />
    </div>

    <div v-else-if="activeSubTab === 2">
      <PaeFormObjetivoContexto
        :model-value="rat.objetivoContexto"
        :saving="rat.saving"
        :read-only="readOnly"
        @save="handleSaveObjetivo"
      />
    </div>

    <div v-else-if="activeSubTab === 3">
      <PaeFormApontamentos
        :items="rat.apontamentos"
        :saving="rat.saving"
        :read-only="readOnly"
        @save="handleSaveApontamentos"
        @add-item="handleAddItem('apontamentos')"
        @remove-item="(i) => handleRemoveItem('apontamentos', i)"
        @add-sub="(i) => handleAddSubItem('apontamentos', i)"
        @remove-sub="(i, j) => handleRemoveSubItem('apontamentos', i, j)"
      />
    </div>

    <div v-else-if="activeSubTab === 4">
      <PaeFormAnexos
        :items="rat.anexos"
        :historico="props.formulario?.anexos_historico ?? []"
        :formulario-id="formularioId"
        :protocolo-id="protocoloId"
        :saving="rat.saving"
        :progress="rat.anexoProgress"
        :status-message="rat.anexoStatus"
        :error-message="rat.anexoError"
        :read-only="readOnly"
        @upload="handleUploadAnexo"
        @remove="handleRemoveAnexo"
        @download="handleDownloadAnexo"
        @view="handleViewAnexo"
      />
    </div>

    <div v-else-if="activeSubTab === 5">
      <PaeFormConclusao
        :items="rat.conclusao"
        :saving="rat.saving"
        :read-only="readOnly"
        @save="handleSaveConclusao"
        @finalizar="handleFinalizar"
        @add-item="handleAddItem('conclusao')"
        @remove-item="(i) => handleRemoveItem('conclusao', i)"
        @add-sub="(i) => handleAddSubItem('conclusao', i)"
        @remove-sub="(i, j) => handleRemoveSubItem('conclusao', i, j)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, h, reactive, computed, watch } from 'vue';
import { usePaeFormulario } from '@/Composables/pae/usePaeFormulario';
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
  protocolo: {
    type: Object,
    default: null,
  },
  readOnly: {
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

const todasAsAbas = [
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

const abasLiberadas = computed(() => {
  // Sem protocolo vinculado (fluxo avulso) todas as abas ficam liberadas.
  if (!props.protocolo?.id) return true;
  return !!props.protocolo?.analista_atual_id;
});

const tabConfig = computed(() =>
  abasLiberadas.value
    ? todasAsAbas
    : todasAsAbas.filter((tab) => [1, 4].includes(tab.id))
);

watch(tabConfig, (tabs) => {
  if (!tabs.some((tab) => tab.id === activeSubTab.value)) {
    activeSubTab.value = 1;
  }
}, { immediate: true });

const formularioId = computed(
  () => localFormularioId.value
    ?? props.formulario?.id
    ?? props.empreendimento?.formulario_id
    ?? null
);

const protocoloId = computed(
  () => props.protocolo?.id
    ?? props.formulario?.pae_protocolo_id
    ?? rat.infoGerais?.pae_protocolo_id
    ?? null
);

// Próxima aba realmente liberada em tabConfig (não a próxima numérica): no
// fluxo completo 1 -> 2 (Objetivo); no fluxo reduzido 1 -> 4 (Anexos).
function nextTabId(currentId) {
  const tabs = tabConfig.value;
  const idx = tabs.findIndex((tab) => tab.id === currentId);
  return tabs[idx + 1]?.id ?? currentId;
}

function handleSaveInfoGerais(data) {
  if (props.readOnly) return;

  Object.assign(rat.infoGerais, data);
  // O callback recebe o ID do formulario (novo no POST, existente no PUT) e so
  // avanca de aba com ID em maos; a proxima aba segue o fluxo liberado atual.
  rat.saveInfoGerais(formularioId.value, (savedId) => {
    if (savedId) {
      localFormularioId.value = savedId;
      activeSubTab.value = nextTabId(activeSubTab.value);
    }
  });
}

function handleSaveObjetivo(data) {
  if (props.readOnly) return;

  Object.assign(rat.objetivoContexto, data);
  rat.saveObjetivoContexto(formularioId.value, () => {
    activeSubTab.value = 3;
  });
}

function handleSaveApontamentos() {
  if (props.readOnly) return;

  rat.saveApontamentos(formularioId.value, () => {
    activeSubTab.value = 4;
  });
}

function handleUploadAnexo(payload) {
  if (props.readOnly) return;

  const { onSuccess, onError, ...data } = payload;
  rat.uploadAnexo(formularioId.value, data, { onSuccess, onError }, protocoloId.value);
}

function handleRemoveAnexo(anexoId) {
  if (props.readOnly) return;

  rat.deleteAnexo(formularioId.value, anexoId);
}

function handleDownloadAnexo(anexoId) {
  rat.downloadAnexo(formularioId.value, anexoId);
}

function handleViewAnexo(anexoId) {
  rat.viewAnexo(formularioId.value, anexoId);
}

function handleSaveConclusao() {
  if (props.readOnly) return;

  rat.saveConclusao(formularioId.value);
}

function handleFinalizar() {
  if (props.readOnly) return;

  rat.finalizarRelatorio(formularioId.value);
}

function handleAddItem(section) {
  if (props.readOnly) return;
  rat.addItem(section);
}

function handleRemoveItem(section, index) {
  if (props.readOnly) return;
  rat.removeItem(section, index);
}

function handleAddSubItem(section, index) {
  if (props.readOnly) return;
  rat.addSubItem(section, index);
}

function handleRemoveSubItem(section, index, childIndex) {
  if (props.readOnly) return;
  rat.removeSubItem(section, index, childIndex);
}
</script>
