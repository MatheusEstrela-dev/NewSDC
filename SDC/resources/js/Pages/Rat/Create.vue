<template>
    <div>
        <Head title="Novo RAT" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader
            :rat="emptyRat"
            :last-update="null"
            :view-only="false"
            :is-create="true"
          />

          <!-- Sistema de Abas -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
            <template #default="{ activeTab }">
              <!-- Aba 1: Dados Gerais -->
              <div v-if="Number(activeTab) === 1">
                <RatForm
                  :rat="null"
                  :view-only="false"
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
                  :view-only="false"
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
                  :view-only="false"
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
                  :view-only="false"
                  @save="handleSaveFromSubTab"
                  @update="handleUpdateVistoria"
                />
              </div>

              <!-- Aba 5: Histórico -->
              <div v-else-if="Number(activeTab) === 5">
                <RatHistory
                  :events="historico"
                  :view-only="false"
                  @add-observation="handleAddObservation"
                  @update="handleUpdateHistorico"
                  @save="handleSaveFromSubTab"
                />
              </div>

              <!-- Aba 6: Anexos -->
              <div v-else-if="Number(activeTab) === 6">
                <RatAttachments
                  :rat-id="null"
                  :anexos="anexos"
                  :view-only="false"
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
import '../../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

// Objeto RAT vazio para o header (sem protocolo, sem status)
const emptyRat = { id: null, protocolo: null, status: 'rascunho' };

const {
  rat,
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
  rat: null,
  recursos: [],
  envolvidos: [],
  vistoria: {},
  historico: [],
  anexos: [],
  activeTab: 1,
});

const recursos = computed(() => recursosState.value || []);
const envolvidos = computed(() => envolvidosState.value || []);
const vistoria = computed(() => vistoriaState.value || {});
const historico = computed(() => historicoEstado.value || []);
const anexos = computed(() => anexosState.value || []);

const currentActiveTab = computed(() => {
  const tabValue = tabs.activeTab;
  if (typeof tabValue === 'object' && tabValue !== null && 'value' in tabValue) {
    return Number(tabValue.value);
  }
  return Number(tabValue);
});

const temVistoria = ref(false);
const currentFormData = ref(null);

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: recursos.value?.length > 0 ? recursos.value.length : null },
  { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: envolvidos.value?.length > 0 ? envolvidos.value.length : null },
  { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Histórico', icon: ClockIcon },
  { id: 6, label: 'Anexos', icon: PaperClipIcon },
]);

function handleSave(data) { salvarRat(data); }
function handleSaveDraft(data) { salvarRascunho(data); }
function handleFinalize(data) { finalizarRat(data); }
function handleCancel() { cancelarRat(); }

function handleFormDataUpdate(data) { currentFormData.value = data; }
function handleToggleVistoria(value) { temVistoria.value = value; }

function handleSaveFromSubTab() {
  const formData = currentFormData.value ?? { dadosGerais: {}, comunicacao: {}, local: {}, endereco: {} };
  salvarRascunho(formData);
}

function handleAddRecurso(recurso) {
  if (!Array.isArray(recursosState.value)) recursosState.value = [];
  recursosState.value.push(recurso);
}
function handleRemoveRecurso(id) {
  if (!Array.isArray(recursosState.value)) return;
  const i = recursosState.value.findIndex(r => r.id === id);
  if (i > -1) recursosState.value.splice(i, 1);
}
function handleUpdateRecurso(data) { recursosState.value = data; }

function handleAddEnvolvido(e) { envolvidosState.value.push(e); }
function handleRemoveEnvolvido(id) {
  const i = envolvidosState.value.findIndex(e => e.id === id);
  if (i > -1) envolvidosState.value.splice(i, 1);
}
function handleUpdateEnvolvidos(data) { envolvidosState.value = Array.isArray(data) ? data : []; }

function handleUpdateVistoria(data) { vistoriaState.value = { ...vistoriaState.value, ...data }; }
function handleUpdateHistorico(data) { historicoEstado.value = data; }
function handleAddObservation(obs) {
  if (!Array.isArray(historicoEstado.value)) historicoEstado.value = [];
  historicoEstado.value.unshift({ id: Date.now(), ...obs, created_at: new Date().toISOString() });
}
function handleAddAnexo(anexo) {
  if (!anexosState.value) anexosState.value = [];
  anexosState.value.push(anexo);
}
function handleRemoveAnexo(id) {
  if (!anexosState.value) return;
  const i = anexosState.value.findIndex(a => a.id === id);
  if (i > -1) anexosState.value.splice(i, 1);
}
function handleUpdateAnexos(data) { anexosState.value = data; }
</script>
