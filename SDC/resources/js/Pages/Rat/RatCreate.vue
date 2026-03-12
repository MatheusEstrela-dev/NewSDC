<template>
  <Head title="Novo RAT" />

  <RatFormLayout
    :rat="emptyRat"
    :tab-config="tabConfig"
    :active-tab="currentActiveTab"
    :is-create="true"
    :last-update="null"
    :view-only="false"
    @tab-change="tabs.setActiveTab"
  >
    <template #default="{ activeTab }">
      <div v-if="Number(activeTab) === 1">
        <RatDadosGeraisForm
          :rat="null"
          :view-only="false"
          @save-draft="salvarRascunho"
          @finalize="finalizarRat"
          @update:tem-vistoria="temVistoria = $event"
          @update:form-data="currentFormData = $event"
        />
      </div>

      <div v-else-if="Number(activeTab) === 2">
        <RatResources
          :recursos="recursos"
          :view-only="false"
          @add="adicionarRecurso"
          @remove="removerRecurso"
          @update="atualizarRecursos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 3">
        <RatInvolved
          :envolvidos="envolvidos"
          :view-only="false"
          @add="adicionarEnvolvido"
          @remove="removerEnvolvido"
          @update="atualizarEnvolvidos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 4">
        <RatInspection
          :vistoria="vistoria"
          :view-only="false"
          @update="atualizarVistoria"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 5">
        <RatHistory
          :events="historico"
          :view-only="false"
          @add-observation="adicionarObservacao"
          @update="atualizarHistorico"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 6">
        <RatAttachments
          :rat-id="null"
          :anexos="anexos"
          :view-only="false"
          @add="adicionarAnexo"
          @remove="removerAnexo"
          @update="atualizarAnexos"
          @save="() => salvarRascunho(currentFormData.value)"
        />
      </div>
    </template>
  </RatFormLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import RatDadosGeraisForm from '@/Components/Rat/RatDadosGeraisForm.vue';
import RatFormLayout from '@/Components/Rat/Templates/RatFormLayout.vue';
import RatAttachments from '@/Components/Rat/RatAttachments.vue';
import RatHistory from '@/Components/Rat/RatHistory.vue';
import RatInspection from '@/Components/Rat/RatInspection.vue';
import RatInvolved from '@/Components/Rat/RatInvolved.vue';
import RatResources from '@/Components/Rat/RatResources.vue';
import { useRat } from '@/Composables/useRat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import '../../../css/pages/rat/rat.css';

defineOptions({ layout: AuthenticatedLayout });

const emptyRat = { id: null, protocolo: null, status: 'rascunho' };

const {
  rat,
  recursos,
  envolvidos,
  vistoria,
  historico,
  anexos,
  tabs,
  salvarRascunho,
  finalizarRat,
  adicionarRecurso,
  removerRecurso,
  atualizarRecursos,
  adicionarEnvolvido,
  removerEnvolvido,
  atualizarEnvolvidos,
  atualizarVistoria,
  adicionarObservacao,
  atualizarHistorico,
  adicionarAnexo,
  removerAnexo,
  atualizarAnexos,
} = useRat({ rat: null, recursos: [], envolvidos: [], vistoria: {}, historico: [], anexos: [], activeTab: 1 });

const temVistoria = ref(false);
const currentFormData = ref({ dadosGerais: {}, comunicacao: {}, local: {}, endereco: {} });

const currentActiveTab = computed(() => {
  const t = tabs.activeTab;
  return Number(typeof t === 'object' && t !== null && 'value' in t ? t.value : t);
});

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais', icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon, badge: recursos.value?.length || null },
  { id: 3, label: 'Envolvidos', icon: UsersIcon, badge: envolvidos.value?.length || null },
  { id: 4, label: 'Vistoria', icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Historico', icon: ClockIcon },
  { id: 6, label: 'Anexos', icon: PaperClipIcon },
]);
</script>
