<template>
    <div>
        <Head title="Gestão de RAT" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader :rat="rat || {}" :last-update="rat?.updated_at" />

          <!-- Sistema de Abas -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
            <template #default="{ activeTab }">
              <!-- Aba 1: Dados Gerais -->
              <div v-if="Number(activeTab) === 1">
                <RatForm
                  :rat="rat || {}"
                  @save="handleSave"
                  @save-draft="handleSaveDraft"
                  @cancel="cancelRat"
                  @update:tem-vistoria="handleToggleVistoria"
                />
              </div>

              <!-- Aba 2: Recursos Empregados -->
              <div v-else-if="Number(activeTab) === 2">
                <RatResources
                  :recursos="recursosState"
                  @update="handleUpdateRecursos"
                />
              </div>

              <!-- Aba 3: Envolvidos -->
              <div v-else-if="Number(activeTab) === 3">
                <RatInvolved
                  :envolvidos="envolvidosState"
                  @update="handleUpdateEnvolvidos"
                />
              </div>

              <!-- Aba 4: Vistoria -->
              <div v-else-if="Number(activeTab) === 4">
                <RatInspection
                  :vistoria="vistoriaState"
                  @update="handleUpdateVistoria"
                />
              </div>

              <!-- Aba 5: Histórico -->
              <div v-else-if="Number(activeTab) === 5">
                <RatHistory
                  :historico="historicoState"
                  @update="handleUpdateHistorico"
                />
              </div>

              <!-- Aba 6: Imagens -->
              <div v-else-if="Number(activeTab) === 6">
                <RatAttachments
                  :rat-id="rat?.id"
                  :imagens="rat?.imagens || []"
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
import { useRat } from '@/Composables/useRat.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import '../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

// Backend envia apenas `rat` com todos os dados embutidos (via RatResource)
const props = defineProps({
  rat: {
    type: Object,
    default: () => null,
  },
});

const initialTab = (() => {
  try {
    const tab = new URLSearchParams(window.location.search).get('tab');
    const n = Number(tab);
    return Number.isFinite(n) && n > 0 ? n : 1;
  } catch {
    return 1;
  }
})();

// Inicializa composable com sub-campos do rat (todos vêm embutidos no prop `rat`)
const {
  recursos: recursosState,
  envolvidos: envolvidosState,
  vistoria: vistoriaState,
  historico: historicoState,
  tabs,
  saveRat,
  saveDraft,
  cancelRat,
} = useRat({
  rat:        props.rat,
  recursos:   props.rat?.recursos   ?? {},
  envolvidos: props.rat?.envolvidos ?? [],
  vistoria:   props.rat?.vistoria   ?? {},
  historico:  props.rat?.historico  ?? {},
  activeTab:  initialTab,
});

// Aba Vistoria: visível somente quando marcada
const temVistoria = ref(props.rat?.tem_vistoria ?? false);

const currentActiveTab = computed(() => {
  const val = tabs.activeTab;
  return Number(typeof val === 'object' && val !== null && 'value' in val ? val.value : val);
});

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais',       icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon },
  { id: 3, label: 'Envolvidos',          icon: UsersIcon },
  { id: 4, label: 'Vistoria',            icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Histórico',           icon: ClockIcon },
  { id: 6, label: 'Imagens',             icon: PaperClipIcon, badge: props.rat?.imagens?.length || null },
]);

// ── Handlers ─────────────────────────────────────────────────────────────────

function handleSave(formData) {
  saveRat(formData);
}

function handleSaveDraft(formData) {
  saveDraft(formData);
}

function handleToggleVistoria(value) {
  temVistoria.value = value;
}

function handleUpdateRecursos(v) {
  recursosState.value = v;
}

function handleUpdateEnvolvidos(v) {
  envolvidosState.value = v;
}

function handleUpdateVistoria(v) {
  vistoriaState.value = v;
}

function handleUpdateHistorico(v) {
  historicoState.value = v;
}
</script>

<style scoped>
.rat-container {
  @apply max-w-5xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6;
}
</style>


