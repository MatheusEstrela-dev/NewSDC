<template>
  <AuthenticatedLayout>
    <Head title="Gestão de RAT" />

    <div class="rat-container">
      <!-- Breadcrumb -->
      <RatBreadcrumb />

      <!-- Header -->
      <RatHeader :rat="rat" :last-update="lastUpdate" />

      <!-- Sistema de Abas -->
      <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
        <template #default="{ activeTab }">
          <!-- Aba 1: Dados Gerais -->
          <div v-if="Number(activeTab) === 1">
            <RatForm
              :rat="rat"
              @save="handleSave"
              @save-draft="handleSaveDraft"
              @cancel="handleCancel"
              @update:tem-vistoria="handleToggleVistoria"
            />
          </div>

          <!-- Aba 2: Recursos Empregados -->
          <div v-else-if="Number(activeTab) === 2">
            <RatResources
              :recursos="recursos"
              @add="handleAddRecurso"
              @remove="handleRemoveRecurso"
            />
          </div>

          <!-- Aba 3: Envolvidos -->
          <div v-else-if="Number(activeTab) === 3">
            <RatInvolved
              :envolvidos="envolvidos"
              @add="handleAddEnvolvido"
              @remove="handleRemoveEnvolvido"
            />
          </div>

          <!-- Aba 4: Vistoria -->
          <div v-else-if="Number(activeTab) === 4">
            <RatInspection
              :vistoria="vistoria"
              @save="handleSaveVistoria"
            />
          </div>

          <!-- Aba 5: Histórico -->
          <div v-else-if="Number(activeTab) === 5">
            <RatHistory
              :events="historyEvents"
              @add-observation="handleAddObservation"
            />
          </div>
        </template>
      </RatTabs>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import '../../css/pages/rat/rat.css';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import RatBreadcrumb from '@/Components/Rat/RatBreadcrumb.vue';
import RatForm from '@/Components/Rat/RatForm.vue';
import RatHeader from '@/Components/Rat/RatHeader.vue';
import RatHistory from '@/Components/Rat/RatHistory.vue';
import RatInspection from '@/Components/Rat/RatInspection.vue';
import RatInvolved from '@/Components/Rat/RatInvolved.vue';
import RatResources from '@/Components/Rat/RatResources.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';
import { useRat } from '@/composables/useRat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Recebe props do Inertia
const props = defineProps({
  rat: {
    type: Object,
    default: () => ({}),
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
  historyEvents: {
    type: Array,
    default: () => [],
  },
  lastUpdate: {
    type: String,
    default: null,
  },
});

// Usa composable com dados do Inertia
const {
  rat: ratState,
  recursos: recursosState,
  envolvidos: envolvidosState,
  vistoria: vistoriaState,
  historyEvents: historyEventsState,
  tabs,
  saveRat,
  saveDraft,
  cancelRat,
} = useRat({
  rat: props.rat,
  recursos: props.recursos,
  envolvidos: props.envolvidos,
  vistoria: props.vistoria,
  historyEvents: props.historyEvents,
  activeTab: 1,
});

// Usa dados do Inertia ou do composable
const rat = computed(() => {
  if (props.rat && props.rat.id) {
    return props.rat;
  }
  return ratState.value;
});

const recursos = computed(() => {
  if (props.recursos && Array.isArray(props.recursos) && props.recursos.length > 0) {
    return props.recursos;
  }
  return recursosState.value || [];
});

const envolvidos = computed(() => {
  if (props.envolvidos && Array.isArray(props.envolvidos) && props.envolvidos.length > 0) {
    return props.envolvidos;
  }
  return envolvidosState.value || [];
});

const vistoria = computed(() => {
  if (props.vistoria && props.vistoria.id) {
    return props.vistoria;
  }
  return vistoriaState.value || {};
});

const historyEvents = computed(() => {
  if (props.historyEvents && Array.isArray(props.historyEvents) && props.historyEvents.length > 0) {
    return props.historyEvents;
  }
  return historyEventsState.value || [];
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

// Configuração das abas
const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: recursos.value.length > 0 ? recursos.value.length : null },
  { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: envolvidos.value.length > 0 ? envolvidos.value.length : null },
  { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Histórico', icon: ClockIcon },
]);

// Handlers
function handleSave(data) {
  saveRat(data);
}

function handleSaveDraft(data) {
  saveDraft(data);
}

function handleCancel() {
  cancelRat();
}

function handleAddRecurso(recurso) {
  recursosState.value.push(recurso);
}

function handleRemoveRecurso(id) {
  const index = recursosState.value.findIndex(r => r.id === id);
  if (index > -1) {
    recursosState.value.splice(index, 1);
  }
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

function handleSaveVistoria(data) {
  Object.assign(vistoriaState.value, data);
}

function handleAddObservation(observation) {
  historyEventsState.value.unshift({
    id: Date.now(),
    ...observation,
    created_at: new Date().toISOString(),
  });
}

function handleToggleVistoria(value) {
  temVistoria.value = value;
}
</script>

<style scoped>
.rat-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

/* Responsive padding */
@media (min-width: 640px) {
  .rat-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .rat-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .rat-container {
    padding: 2rem 3rem;
  }
}
</style>

