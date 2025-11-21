<template>
  <AuthenticatedLayout>
    <Head title="Gestão de PAE" />

    <div class="pae-container">
      <!-- Breadcrumb -->
      <PaeBreadcrumb />

      <!-- Header -->
      <PaeHeader :empreendimento="empreendimento" :last-update="lastUpdate" />

      <!-- Sistema de Abas -->
      <PaeTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
        <template #default="{ activeTab }">
          <!-- Aba 1: Formulário PAE -->
          <div v-if="Number(activeTab) === 1">
            <PaeForm
              :empreendimento="empreendimento"
              :documents="Array.isArray(documents.documents) ? documents.documents : []"
              @save="handleSave"
              @save-draft="handleSaveDraft"
              @archive="handleArchive"
              @upload="handleUpload"
              @remove="handleRemove"
            />
          </div>

          <!-- Aba 2: Histórico -->
          <div v-else-if="Number(activeTab) === 2">
            <PaeHistory
              :events="historyEvents"
              @filter-change="handleFilterChange"
              @view-event="handleViewEvent"
            />
          </div>

          <!-- Aba 3: CCPAE -->
          <div v-else-if="Number(activeTab) === 3">
            <PaeCommittee
              :members="committeeMembers"
              :atas="atas"
              @add-member="handleAddMember"
              @add-meeting="handleAddMeeting"
              @view-ata="handleViewAta"
            />
          </div>

          <!-- Aba 4: Empreendedor -->
          <div v-else-if="Number(activeTab) === 4">
            <PaeEntrepreneur :empreendedor="empreendedor" @save="handleSaveEmpreendedor" />
          </div>
        </template>
      </PaeTabs>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import '../../css/pages/pae/pae.css';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import PaeBreadcrumb from '@/Components/Pae/PaeBreadcrumb.vue';
import PaeCommittee from '@/Components/Pae/PaeCommittee.vue';
import PaeEntrepreneur from '@/Components/Pae/PaeEntrepreneur.vue';
import PaeForm from '@/Components/Pae/PaeForm.vue';
import PaeHeader from '@/Components/Pae/PaeHeader.vue';
import PaeHistory from '@/Components/Pae/PaeHistory.vue';
import PaeTabs from '@/Components/Pae/PaeTabs.vue';
import { usePae } from '@/composables/usePae';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Recebe props do Inertia
const props = defineProps({
  empreendimento: {
    type: Object,
    default: () => ({}),
  },
  historyEvents: {
    type: Array,
    default: () => [],
  },
  committeeMembers: {
    type: Array,
    default: () => [],
  },
  empreendedor: {
    type: Object,
    default: () => ({}),
  },
  documents: {
    type: Array,
    default: () => [],
  },
  atas: {
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
  empreendimento: empreendimentoState,
  historyEvents: historyEventsState,
  committeeMembers: committeeMembersState,
  empreendedor: empreendedorState,
  atas: atasState,
  tabs,
  documents,
  modal,
  savePae,
  saveDraft,
  archiveEmpreendimento,
  addCommitteeMember,
  updateEmpreendedor,
} = usePae({
  empreendimento: props.empreendimento,
  historyEvents: props.historyEvents,
  committeeMembers: props.committeeMembers,
  empreendedor: props.empreendedor,
  documents: props.documents,
  atas: props.atas,
  activeTab: 1,
});

// Usa dados do Inertia ou do composable
const empreendimento = computed(() => {
  if (props.empreendimento && props.empreendimento.id) {
    return props.empreendimento;
  }
  return empreendimentoState.value;
});
const historyEvents = computed(() => {
  // Priorizar dados do Inertia se existirem
  if (props.historyEvents && Array.isArray(props.historyEvents) && props.historyEvents.length > 0) {
    return props.historyEvents;
  }
  // Caso contrário, usar dados do composable
  const events = historyEventsState.value;
  if (Array.isArray(events) && events.length > 0) {
    return events;
  }
  // Retornar array vazio se não houver dados
  return [];
});
const committeeMembers = computed(() => {
  if (props.committeeMembers && props.committeeMembers.length > 0) {
    return props.committeeMembers;
  }
  return committeeMembersState.value;
});
const empreendedor = computed(() => {
  if (props.empreendedor && props.empreendedor.id) {
    return props.empreendedor;
  }
  return empreendedorState.value;
});
const atas = computed(() => {
  if (props.atas && props.atas.length > 0) {
    return props.atas;
  }
  return atasState.value || [];
});
const currentActiveTab = computed(() => {
  const tabValue = tabs.activeTab;
  if (typeof tabValue === 'object' && tabValue !== null && 'value' in tabValue) {
    return Number(tabValue.value);
  }
  return Number(tabValue);
});

// Configuração das abas
const tabConfig = computed(() => [
  { id: 1, label: 'Formulário PAE', icon: DocumentTextIcon },
  { id: 2, label: 'Histórico', icon: ClockIcon, badge: historyEvents.value.length > 0 ? historyEvents.value.length : null },
  { id: 3, label: 'CCPAE', icon: UsersIcon },
  { id: 4, label: 'Empreendedor', icon: BuildingOfficeIcon },
]);

// Handlers
function handleSave(data) {
  savePae();
}

function handleSaveDraft(data) {
  saveDraft();
}

function handleArchive() {
  archiveEmpreendimento();
}

function handleUpload(files) {
  files.forEach(file => {
    documents.addDocument(file);
  });
}

function handleRemove(id) {
  documents.removeDocument(id);
}

function handleFilterChange(filter) {
  // TODO: Implementar filtro
  console.log('Filtro:', filter);
}

function handleViewEvent(event) {
  modal.open('Detalhes do Evento', event);
}

function handleAddMember() {
  // TODO: Abrir modal para adicionar membro
  console.log('Adicionar membro');
}

function handleAddMeeting() {
  // TODO: Abrir modal para adicionar reunião
  console.log('Adicionar reunião');
}

function handleViewAta(ata) {
  modal.open('Ata de Reunião', ata);
}

function handleSaveEmpreendedor(data) {
  updateEmpreendedor(data);
}
</script>

<style scoped>
.pae-container {
  @apply w-full min-h-screen;
  padding: 1.5rem 2rem;
  background: #0f172a;
}
</style>
