<template>
    <div>
        <Head title="Visualizar RAT" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader :rat="props.rat" :last-update="lastUpdate" :view-only="true" />

          <!-- Sistema de Abas (somente leitura) -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
            <template #default="{ activeTab }">
              <!-- Aba 1: Dados Gerais -->
              <div v-if="Number(activeTab) === 1">
                <RatForm
                  :rat="props.rat"
                  :view-only="true"
                />
              </div>

              <!-- Aba 2: Recursos Empregados -->
              <div v-else-if="Number(activeTab) === 2">
                <RatResources
                  :recursos="props.rat?.recursos ?? []"
                  :view-only="true"
                />
              </div>

              <!-- Aba 3: Envolvidos -->
              <div v-else-if="Number(activeTab) === 3">
                <RatInvolved
                  :envolvidos="props.rat?.envolvidos ?? []"
                  :view-only="true"
                />
              </div>

              <!-- Aba 4: Vistoria -->
              <div v-else-if="Number(activeTab) === 4">
                <RatInspection
                  :vistoria="props.rat?.vistoria ?? {}"
                  :view-only="true"
                />
              </div>

              <!-- Aba 5: Histórico -->
              <div v-else-if="Number(activeTab) === 5">
                <RatHistory
                  :events="props.rat?.historico ?? []"
                  :view-only="true"
                />
              </div>

              <!-- Aba 6: Anexos -->
              <div v-else-if="Number(activeTab) === 6">
                <RatAttachments
                  :rat-id="props.rat?.id"
                  :anexos="props.rat?.anexos ?? []"
                  :view-only="true"
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
import RatDadosGeraisForm from '@/Components/Rat/RatDadosGeraisForm.vue';
import RatHeader from '@/Components/Rat/RatHeader.vue';
import RatHistory from '@/Components/Rat/RatHistory.vue';
import RatInspection from '@/Components/Rat/RatInspection.vue';
import RatInvolved from '@/Components/Rat/RatInvolved.vue';
import RatResources from '@/Components/Rat/RatResources.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';
import { useTabs } from '@/Composables/core/useTabs';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import '../../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  rat: { type: Object, default: () => ({}) },
  lastUpdate: { type: String, default: null },
});

const tabs = useTabs(1);

const currentActiveTab = computed(() => {
  const tabValue = tabs.activeTab;
  if (typeof tabValue === 'object' && tabValue !== null && 'value' in tabValue) {
    return Number(tabValue.value);
  }
  return Number(tabValue);
});

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: props.rat?.recursos?.length > 0 ? props.rat.recursos.length : null },
  { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: props.rat?.envolvidos?.length > 0 ? props.rat.envolvidos.length : null },
  { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !props.rat?.tem_vistoria },
  { id: 5, label: 'Histórico', icon: ClockIcon },
  { id: 6, label: 'Anexos', icon: PaperClipIcon, badge: props.rat?.anexos?.length > 0 ? props.rat.anexos.length : null },
]);
</script>
