<template>
  <Head title="Novo RAT" />

  <RatFormLayout
    :rat="ratData"
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
          @save="() => salvarComAnexos(currentFormData).then(() => unlockAndAdvanceTab(2)).catch(() => {})"
        />
      </div>

      <div v-else-if="Number(activeTab) === 3">
        <RatInvolved
          :envolvidos="envolvidos"
          :view-only="false"
          @add="adicionarEnvolvido"
          @remove="removerEnvolvido"
          @update="atualizarEnvolvidos"
          @save="() => salvarComAnexos(currentFormData).then(() => unlockAndAdvanceTab(3)).catch(() => {})"
        />
      </div>

      <div v-else-if="Number(activeTab) === 4">
        <RatInspection
          :vistoria="vistoria"
          :view-only="false"
          @update="atualizarVistoria"
          @save="() => salvarComAnexos(currentFormData).then(() => unlockAndAdvanceTab(4)).catch(() => {})"
        />
      </div>

      <div v-else-if="Number(activeTab) === 5">
        <RatHistory
          :events="historico"
          :view-only="false"
          @add-observation="adicionarObservacao"
          @update="atualizarHistorico"
          @save="() => salvarComAnexos(currentFormData).then(() => unlockAndAdvanceTab(5)).catch(() => {})"
        />
      </div>

      <div v-else-if="Number(activeTab) === 6">
        <RatAttachments
          :rat-id="ratData.id"
          :anexos="anexos"
          :view-only="false"
          @add="adicionarAnexo"
          @remove="removerAnexo"
          @update="atualizarAnexos"
          @update:pending-files="pendingAttachmentFiles = $event"
          @save="() => salvarComAnexos(currentFormData)"
          @finalize="handleFinalizar"
        />
      </div>
    </template>
  </RatFormLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
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

// RAT reativo: começa vazio, atualizado após o primeiro save com id e numero_bos
const ratData = reactive({ id: null, protocolo: null, numero_bos: null, status: 'rascunho' });

const {
  recursos,
  envolvidos,
  vistoria,
  historico,
  anexos,
  tabs,
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

const { show: toast } = useToast();

const temVistoria = ref(false);
const currentFormData = ref({ dadosGerais: {}, comunicacao: {}, local: {}, endereco: {} });

// Novo RAT → apenas aba 1 acessível inicialmente
const unlockedTabs = ref([1]);

function unlockAndAdvanceTab(currentTabId) {
  const ordered = [1, 2, 3, temVistoria.value ? 4 : null, 5, 6].filter(Boolean);
  const idx = ordered.indexOf(Number(currentTabId));
  if (idx >= 0 && idx < ordered.length - 1) {
    const nextId = ordered[idx + 1];
    // Bloqueio exclusivo: apenas a próxima aba fica ativa
    unlockedTabs.value = [nextId];
    tabs.setActiveTab(nextId);
  }
}

/** Arquivos pendentes vindos do componente RatAttachments. */
const pendingAttachmentFiles = ref([]);

/**
 * Salva o RAT via axios sem redirecionar.
 * - Primeiro save: POST /rat → atualiza ratData com id e numero_bos
 * - Saves seguintes: PATCH /rat/{id}/draft
 * - Faz upload dos anexos pendentes após salvar
 */
async function salvarComAnexos(formData) {
  const filesToUpload = [...pendingAttachmentFiles.value];

  const data = {
    dadosGerais: formData?.dadosGerais ?? {},
    comunicacao: formData?.comunicacao ?? {},
    local:       formData?.local ?? {},
    endereco:    formData?.endereco ?? {},
    recursos:    recursos.value,
    envolvidos:  envolvidos.value,
    vistoria:    vistoria.value,
    historico:   historico.value,
  };

  const ax = window.axios || (await import('axios')).default;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf };

  try {
    let currentId = ratData.id;

    if (!currentId) {
      // Cria o RAT pela primeira vez
      const response = await ax.post(route('rat.store'), data, { headers });
      currentId = response.data?.id ?? null;
      if (currentId) {
        ratData.id         = currentId;
        ratData.numero_bos = response.data.numero_bos ?? null;
        ratData.protocolo  = response.data.numero_bos ?? null;
        ratData.status     = 'rascunho';
      }
    } else {
      // Atualiza rascunho existente
      await ax.patch(route('rat.draft', currentId), data, { headers });
    }

    if (currentId && filesToUpload.length > 0) {
      for (const { file } of filesToUpload) {
        const form = new FormData();
        form.append('file', file);
        form.append('tipo', file.type?.startsWith('image/') ? 'imagem' : 'documento');
        await ax.post(
          route('rat.ocorrencias.attachments.store', { ocorrencia: currentId }),
          form,
          { headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': csrf } },
        );
      }
      pendingAttachmentFiles.value = [];
    }
    toast('RAT salvo com sucesso!', 'success');
  } catch (e) {
    console.error('Erro ao salvar RAT:', e);
    toast('Erro ao salvar RAT. Tente novamente.', 'error');
    throw e;
  }
}

async function handleFinalizar() {
  if (!ratData.id) {
    toast('Salve o RAT antes de finalizar.', 'error');
    return;
  }
  const ax = window.axios || (await import('axios')).default;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  try {
    await ax.patch(
      route('rat.finalize', ratData.id),
      {},
      { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } },
    );
    toast('RAT finalizado com sucesso!', 'success');
    router.visit(route('rat.index'));
  } catch (e) {
    toast(e.response?.data?.message ?? 'Erro ao finalizar RAT.', 'error');
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
  { id: 1, label: 'Dados Gerais',        icon: DocumentTextIcon, disabled: !unlockedTabs.value.includes(1) },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon,         badge: recursos.value?.length || null, disabled: !unlockedTabs.value.includes(2) },
  { id: 3, label: 'Envolvidos',          icon: UsersIcon,         badge: envolvidos.value?.length || null, disabled: !unlockedTabs.value.includes(3) },
  { id: 4, label: 'Vistoria',            icon: ClipboardIcon,     hidden: !temVistoria.value, disabled: !unlockedTabs.value.includes(4) },
  { id: 5, label: 'Histórico',           icon: ClockIcon,         disabled: !unlockedTabs.value.includes(5) },
  { id: 6, label: 'Anexos',             icon: PaperClipIcon,     disabled: !unlockedTabs.value.includes(6) },
]);
</script>
