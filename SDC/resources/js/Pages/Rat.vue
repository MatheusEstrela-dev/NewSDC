<template>
    <div>
        <Head title="Gestão de RAT" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader :rat="rat" :last-update="lastUpdate" :view-only="props.viewOnly" />

          <!-- Sistema de Abas -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
            <template #default="{ activeTab }">
              <!-- Aba 1: Dados Gerais -->
              <div v-if="Number(activeTab) === 1">
                <RatForm
                  :rat="rat"
                  :view-only="props.viewOnly"
                  @save="handleSave"
                  @save-draft="handleSaveDraft"
                  @finalize="handleFinalize"
                  @cancel="handleCancel"
                  @update:tem-vistoria="handleToggleVistoria"
                  @update:form-data="handleFormDataUpdate"
                />
              </div>

              <!-- Aba 2: Recursos Empregados -->
              <div v-else-if="Number(activeTab) === 2">
                <RatResources
                  :recursos="recursos"
                  :view-only="props.viewOnly"
                  @add="handleAddRecurso"
                  @remove="handleRemoveRecurso"
                  @update="handleUpdateRecurso"
                  @save="handleSaveFromSubTab"
                />
              </div>

              <!-- Aba 3: Envolvidos -->
              <div v-else-if="Number(activeTab) === 3">
                <RatInvolved
                  :envolvidos="envolvidos"
                  :view-only="props.viewOnly"
                  @add="handleAddEnvolvido"
                  @remove="handleRemoveEnvolvido"
                  @update="handleUpdateEnvolvidos"
                  @save="handleSaveFromSubTab"
                />
              </div>

              <!-- Aba 4: Vistoria -->
              <div v-else-if="Number(activeTab) === 4">
                <RatInspection
                  :vistoria="vistoria"
                  :view-only="props.viewOnly"
                  @save="handleSaveFromSubTab"
                  @update="handleUpdateVistoria"
                />
              </div>

              <!-- Aba 5: Histórico -->
              <div v-else-if="Number(activeTab) === 5">
                <RatHistory
                  :events="historico"
                  :view-only="props.viewOnly"
                  @add-observation="handleAddObservation"
                  @update="handleUpdateHistorico"
                  @save="handleSaveFromSubTab"
                />
              </div>

              <!-- Aba 6: Anexos -->
              <div v-else-if="Number(activeTab) === 6">
                <RatAttachments
                  :rat-id="rat?.id"
                  :anexos="anexos"
                  :view-only="props.viewOnly"
                  @add="handleAddAnexo"
                  @remove="handleRemoveAnexo"
                  @update="handleUpdateAnexos"
                  @save="handleSaveFromSubTab"
                />
              </div>
            </template>
          </RatTabs>
        </div>
    </div>
</template>

<script setup>
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import RatAttachments from '@/Components/Rat/RatAttachments.vue';
import RatForm from '@/Components/Rat/RatForm.vue';
import RatHeader from '@/Components/Rat/RatHeader.vue';
import RatHistory from '@/Components/Rat/RatHistory.vue';
import RatInspection from '@/Components/Rat/RatInspection.vue';
import RatInvolved from '@/Components/Rat/RatInvolved.vue';
import RatResources from '@/Components/Rat/RatResources.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';
import { useRat } from '@/Composables/useRat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import '../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

// Recebe props do Inertia
const props = defineProps({
  rat: {
    type: Object,
    default: () => ({}),
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  recursos: {
    type: Array,
    default: () => [],
  },
  envolvidos: {
    type: Array,
    default: () => [],
  },
  vistoria: {
    type: Object,
    default: () => ({}),
  },
  historico: {
    type: Array,
    default: () => [],
  },
  anexos: {
    type: Array,
    default: () => [],
  },
  lastUpdate: {
    type: String,
    default: null,
  },
});

// Usa composable com dados do Inertia
const initialTab = (() => {
  try {
    const tab = new URLSearchParams(window.location.search).get('tab');
    const n = Number(tab);
    return Number.isFinite(n) && n > 0 ? n : 1;
  } catch {
    return 1;
  }
})();

const {
  rat: ratState,
  recursos: recursosState,
  envolvidos: envolvidosState,
  vistoria: vistoriaState,
  historico: historicoEstado,
  anexos: anexosState,
  tabs,
  salvarRat,
  salvarRascunho,
  finalizarRat,
  cancelarRat,
} = useRat({
  rat: props.rat,
  recursos: props.rat?.recursos ?? props.recursos ?? [],
  envolvidos: props.rat?.envolvidos ?? props.envolvidos ?? [],
  vistoria: props.rat?.vistoria ?? props.vistoria ?? {},
  historico: props.rat?.historico ?? props.historico ?? [],
  anexos: props.rat?.anexos ?? props.anexos ?? [],
  activeTab: initialTab,
});

// Usa dados do Inertia ou do composable
const rat = computed(() => {
  if (props.rat && props.rat.id) {
    return props.rat;
  }
  return ratState.value;
});

const recursos = computed(() => {
  if (recursosState.value && (Array.isArray(recursosState.value) ? recursosState.value.length > 0 : Object.keys(recursosState.value).length > 0)) {
    return recursosState.value;
  }
  if (Array.isArray(props.rat?.recursos) && props.rat.recursos.length > 0) return props.rat.recursos;
  if (props.rat?.recursos && !Array.isArray(props.rat.recursos) && Object.keys(props.rat.recursos).length > 0) return props.rat.recursos;
  if (props.recursos?.length > 0) return props.recursos;
  return recursosState.value || [];
});

const envolvidos = computed(() => {
  if (envolvidosState.value?.length > 0) return envolvidosState.value;
  if (Array.isArray(props.rat?.envolvidos) && props.rat.envolvidos.length > 0) return props.rat.envolvidos;
  if (props.envolvidos?.length > 0) return props.envolvidos;
  return envolvidosState.value || [];
});

const vistoria = computed(() => {
  if (vistoriaState.value && Object.keys(vistoriaState.value).length > 0) return vistoriaState.value;
  if (props.rat?.vistoria && Object.keys(props.rat.vistoria).length > 0) return props.rat.vistoria;
  if (props.vistoria?.id) return props.vistoria;
  return vistoriaState.value || {};
});

const historico = computed(() => {
  if (historicoEstado.value?.length > 0) return historicoEstado.value;
  if (Array.isArray(props.rat?.historico) && props.rat.historico.length > 0) return props.rat.historico;
  if (props.historico?.length > 0) return props.historico;
  return historicoEstado.value || [];
});

const anexos = computed(() => {
  if (anexosState.value?.length > 0) return anexosState.value;
  if (Array.isArray(props.rat?.anexos) && props.rat.anexos.length > 0) return props.rat.anexos;
  if (props.anexos?.length > 0) return props.anexos;
  return anexosState.value || [];
});

const currentActiveTab = computed(() => {
  const tabValue = tabs.activeTab;
  if (typeof tabValue === 'object' && tabValue !== null && 'value' in tabValue) {
    return Number(tabValue.value);
  }
  return Number(tabValue);
});

// Estado local para controlar visibilidade da aba Vistoria
const temVistoria = ref(props.rat?.tem_vistoria || false);

// Rastreia o estado atual do formulário principal (aba Dados Gerais)
const currentFormData = ref(null);

// Configuração das abas
const tabConfig = computed(() => {
  const tabs = [
    { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
    { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: recursos.value?.length > 0 ? recursos.value.length : null },
    { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: envolvidos.value?.length > 0 ? envolvidos.value.length : null },
    { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !temVistoria.value },
    { id: 5, label: 'Histórico', icon: ClockIcon },
    { id: 6, label: 'Anexos', icon: PaperClipIcon, badge: (anexos.value && Array.isArray(anexos.value) && anexos.value.length > 0) ? anexos.value.length : null },
  ];
  return tabs;
});

// Handlers
function handleSave(data) {
  salvarRat(data);
}

function handleSaveDraft(data) {
  salvarRascunho(data);
}

function handleFinalize(data) {
  finalizarRat(data);
}

function handleCancel() {
  cancelarRat();
}

// Sincroniza dados do formulário principal para uso nas abas secundárias
function handleFormDataUpdate(data) {
  currentFormData.value = data;
}

function handleAddRecurso(recurso) {
  if (!Array.isArray(recursosState.value)) {
    recursosState.value = [];
  }
  recursosState.value.push(recurso);
}

function handleRemoveRecurso(id) {
  if (!Array.isArray(recursosState.value)) return;
  const index = recursosState.value.findIndex(r => r.id === id);
  if (index > -1) {
    recursosState.value.splice(index, 1);
  }
}

function handleUpdateRecurso(data) {
  recursosState.value = data;
}

function handleAddEnvolvido(envolvido) {
  envolvidosState.value.push(envolvido);
}

function handleRemoveEnvolvido(id) {
  const index = envolvidosState.value.findIndex(e => e.id === id);
  if (index > -1) {
    envolvidosState.value.splice(index, 1);
  }
}

function handleUpdateEnvolvidos(data) {
  envolvidosState.value = Array.isArray(data) ? data : [];
}

function handleSaveVistoria(data) {
  vistoriaState.value = { ...vistoriaState.value, ...data };
}

function handleUpdateVistoria(data) {
  vistoriaState.value = { ...vistoriaState.value, ...data };
}

function handleUpdateHistorico(data) {
  historicoEstado.value = data;
}

function handleAddObservation(observation) {
  if (!Array.isArray(historicoEstado.value)) {
    historicoEstado.value = [];
  }
  historicoEstado.value.unshift({
    id: Date.now(),
    ...observation,
    created_at: new Date().toISOString(),
  });
}

/**
 * Salva o RAT a partir de uma aba secundária (Recursos, Envolvidos, Vistoria, Histórico, Anexos).
 * Usa os dados mais recentes do formulário principal se já foram preenchidos.
 */
function handleSaveFromSubTab() {
  const formData = currentFormData.value ?? {
    dadosGerais: props.rat?.dados_gerais ?? {},
    comunicacao: props.rat?.comunicacao  ?? {},
    local:       props.rat?.local        ?? {},
    endereco:    props.rat?.endereco     ?? {},
  };
  salvarRat(formData);
}

function handleAddAnexo(anexo) {
  if (!anexosState.value) {
    anexosState.value = [];
  }
  anexosState.value.push(anexo);
}

function handleRemoveAnexo(id) {
  if (!anexosState.value) return;
  const index = anexosState.value.findIndex(a => a.id === id);
  if (index > -1) {
    anexosState.value.splice(index, 1);
  }
}

function handleUpdateAnexos(updatedAnexos) {
  if (Array.isArray(updatedAnexos)) {
    anexosState.value = [...updatedAnexos];
  }
}

function handleToggleVistoria(value) {
  temVistoria.value = value;
}
</script>

<style scoped>
.rat-container {
  @apply w-full min-h-screen bg-transparent;
}
</style>

