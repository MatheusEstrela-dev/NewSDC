<template>
  <Head title="Novo RAT" />

  <RatFormLayout
    :rat="emptyRat"
    :tab-config="tabConfig"
    :active-tab="currentActiveTab"
    :is-create="true"
    :last-update="null"
    :view-only="false"
    @tab-change="onTabChange"
  >
    <template #default="{ activeTab }">
      <div v-if="Number(activeTab) === 1">
        <RatDadosGeraisForm
          :rat="null"
          :view-only="false"
          @save="(data) => salvarComAnexos(data).then(() => unlockAndAdvanceTab(1)).catch(() => {})"
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
          @save="() => salvarComAnexos(currentFormData)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 3">
        <RatInvolved
          :envolvidos="envolvidos"
          :view-only="false"
          @add="adicionarEnvolvido"
          @remove="removerEnvolvido"
          @update="atualizarEnvolvidos"
          @save="() => salvarComAnexos(currentFormData)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 4">
        <RatInspection
          :vistoria="vistoria"
          :view-only="false"
          @update="atualizarVistoria"
          @save="() => salvarComAnexos(currentFormData)"
        />
      </div>

      <div v-else-if="Number(activeTab) === 5">
        <RatHistory
          :events="historico"
          :view-only="false"
          @add-observation="adicionarObservacao"
          @update="atualizarHistorico"
          @save="() => salvarComAnexos(currentFormData)"
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
          @update:pending-files="pendingAttachmentFiles = $event"
          @save="() => salvarComAnexos(currentFormData)"
        />
      </div>
    </template>
  </RatFormLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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

// Novo RAT → apenas aba 1 acessível inicialmente
const unlockedTabs = ref([1]);

function unlockAndAdvanceTab(currentTabId) {
  const ordered = [1, 2, 3, temVistoria.value ? 4 : null, 5, 6].filter(Boolean);
  const idx = ordered.indexOf(Number(currentTabId));
  if (idx >= 0 && idx < ordered.length - 1) {
    const nextId = ordered[idx + 1];
    if (!unlockedTabs.value.includes(nextId)) {
      unlockedTabs.value = [...unlockedTabs.value, nextId];
    }
    tabs.setActiveTab(nextId);
  }
}

/** Arquivos pendentes vindos do componente RatAttachments. */
const pendingAttachmentFiles = ref([]);

/**
 * Salva o RAT via axios (sem Inertia) para controlar o fluxo de upload.
 * 1. POST /rat via axios (sem seguir redirect)
 * 2. Extrai o ID do RAT da URL de redirect
 * 3. Faz upload dos anexos pendentes
 * 4. Navega para a pagina de edicao via Inertia
 */
async function salvarComAnexos(formData) {
  const filesToUpload = [...pendingAttachmentFiles.value];
  console.log('[salvarComAnexos] pendingFiles:', filesToUpload.length, 'formData keys:', Object.keys(formData || {}));

  if (filesToUpload.length === 0) {
    console.log('[salvarComAnexos] no pending files, using salvarRascunho');
    salvarRascunho(formData);
    return;
  }

  const data = {
    dadosGerais: formData.dadosGerais ?? {},
    comunicacao: formData.comunicacao ?? {},
    local:       formData.local ?? {},
    endereco:    formData.endereco ?? {},
    recursos:    recursos.value,
    envolvidos:  envolvidos.value,
    vistoria:    vistoria.value,
    historico:   historico.value,
  };

  const ax = window.axios || (await import('axios')).default;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  try {
    const response = await ax.post(route('rat.store'), data, {
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
    });

    const newRatId = response.data?.id ?? null;

    if (newRatId) {
      for (const { file } of filesToUpload) {
        const form = new FormData();
        form.append('file', file);
        const categoria = file.type?.startsWith('image/') ? 'imagem' : 'documento';
        form.append('categoria', categoria);
        await ax.post(route('rat.anexos.store', { id: newRatId }), form, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      pendingAttachmentFiles.value = [];
      router.visit(route('rat.bo.index') + '?ocorrencia_id=' + newRatId);
    }
  } catch (e) {
    console.error('Erro ao salvar RAT:', e);
  }
}

const currentActiveTab = computed(() => {
  const t = tabs.activeTab;
  return Number(typeof t === 'object' && t !== null && 'value' in t ? t.value : t);
});

function onTabChange(tabId) {
  if (unlockedTabs.value.includes(Number(tabId))) {
    tabs.setActiveTab(tabId);
  }
}

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais',        icon: DocumentTextIcon, disabled: false },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon,         badge: recursos.value?.length || null, disabled: !unlockedTabs.value.includes(2) },
  { id: 3, label: 'Envolvidos',          icon: UsersIcon,         badge: envolvidos.value?.length || null, disabled: !unlockedTabs.value.includes(3) },
  { id: 4, label: 'Vistoria',            icon: ClipboardIcon,     hidden: !temVistoria.value, disabled: !unlockedTabs.value.includes(4) },
  { id: 5, label: 'Histórico',           icon: ClockIcon,         disabled: !unlockedTabs.value.includes(5) },
  { id: 6, label: 'Anexos',             icon: PaperClipIcon,     disabled: !unlockedTabs.value.includes(6) },
]);
</script>
