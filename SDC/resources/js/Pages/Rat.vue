<template>
    <div>
        <Head :title="props.isCreate ? 'Criar Boletim de Ocorrência' : props.viewOnly ? 'Visualizar Boletim de Ocorrência' : 'Editar Boletim de Ocorrência'" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader :rat="rat" :last-update="lastUpdate" :view-only="props.viewOnly" :is-create="props.isCreate" />

          <!-- Sistema de Abas -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="handleTabChange">
            <template #default="{ activeTab }">
              <!-- Aba 1: Dados Gerais -->
              <div v-if="Number(activeTab) === 1">
                <RatDadosGeraisForm
                  :rat="rat"
                  :view-only="props.viewOnly"
                  :loading="loading"
                  @save="(data) => saveAndAdvance({ dadosGerais: data.dadosGerais, comunicacao: data.comunicacao, local: data.local, endereco: data.endereco }, 2)"
                  @update:tem-vistoria="handleToggleVistoria"
                />
              </div>

              <!-- Aba 2: Recursos Empregados -->
              <div v-else-if="Number(activeTab) === 2">
                <RatResources
                  :recursos="recursosState"
                  :view-only="props.viewOnly"
                  :loading="loading"
                  @add="handleAddRecurso"
                  @remove="handleRemoveRecurso"
                  @update="handleUpdateRecurso"
                  @save="() => saveAndAdvance({ recursos: recursosState }, 3)"
                />
              </div>

              <!-- Aba 3: Envolvidos -->
              <div v-else-if="Number(activeTab) === 3">
                <RatInvolved
                  :envolvidos="envolvidosState"
                  :view-only="props.viewOnly"
                  :loading="loading"
                  @add="handleAddEnvolvido"
                  @remove="handleRemoveEnvolvido"
                  @update="handleUpdateEnvolvidos"
                  @save="() => saveAndAdvance({ envolvidos: envolvidosState }, temVistoria ? 4 : 5)"
                />
              </div>

              <!-- Aba 4: Vistoria -->
              <div v-else-if="Number(activeTab) === 4">
                <RatInspection
                  :vistoria="vistoriaState"
                  :view-only="props.viewOnly"
                  :loading="loading"
                  @save="() => saveAndAdvance({ vistoria: vistoriaState }, 5)"
                  @update="handleUpdateVistoria"
                />
              </div>

              <!-- Aba 5: Histórico -->
              <div v-else-if="Number(activeTab) === 5">
                <RatHistory
                  :events="historicoEstado"
                  :view-only="props.viewOnly"
                  :loading="loading"
                  @update="handleUpdateHistorico"
                  @save="() => saveAndAdvance({ historico: historicoEstado }, 6)"
                />
              </div>

              <!-- Aba 6: Anexos -->
              <div v-else-if="Number(activeTab) === 6">
                <RatAttachments
                  :rat-id="rat?.id ? String(rat.id) : null"
                  :anexos="Array.isArray(anexosState) ? anexosState : []"
                  :view-only="props.viewOnly"
                  @add="handleAddAnexo"
                  @remove="handleRemoveAnexo"
                  @update="handleUpdateAnexos"
                  @save="() => saveAndAdvance({ anexos: true }, null)"
                  @finalize="handleFinalize"
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
import { useRat } from '@/Composables/useRat';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { show: toast } = useToast();

const props = defineProps({
  rat:        { type: Object,  default: null },
  viewOnly:   { type: Boolean, default: false },
  isCreate:   { type: Boolean, default: false },
  lastUpdate: { type: String,  default: null },
});

const ABA_TO_TAB = { recursos: 2, envolvidos: 3, vistoria: 4, historico: 5, anexos: 6 };
const TAB_TO_ABA = { 2: 'recursos', 3: 'envolvidos', 4: 'vistoria', 5: 'historico', 6: 'anexos' };

const initialTab = (() => {
  try {
    const params = new URLSearchParams(window.location.search);
    const aba = params.get('aba');
    if (aba && ABA_TO_TAB[aba]) return ABA_TO_TAB[aba];
    const tab = params.get('tab');
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
  loading,
  tabs,
} = useRat({
  rat:        props.rat ?? null,
  recursos:   props.rat?.recursos   ?? [],
  envolvidos: props.rat?.envolvidos ?? [],
  vistoria:   props.rat?.vistoria   ?? {},
  historico:  props.rat?.historico  ?? [],
  anexos:     props.rat?.anexos     ?? [],
  activeTab:  initialTab,
});

const rat = computed(() => {
  const base = (props.rat?.id ? props.rat : ratState.value) ?? { id: null, status: 'em_andamento' };
  if (localNumeroBos.value && localNumeroBos.value !== base.numero_bos) {
    return { ...base, numero_bos: localNumeroBos.value, protocolo: localNumeroBos.value };
  }
  return base;
});

const currentActiveTab = computed(() => {
  const v = tabs.activeTab;
  return Number(typeof v === 'object' && v !== null && 'value' in v ? v.value : v);
});

const temVistoria    = ref(props.rat?.tem_vistoria || false);
const localNumeroBos = ref(props.rat?.numero_bos ?? null);

// Abas desbloqueadas progressivamente: novo RAT começa só com aba 1,
// RATs existentes ou view-only têm todas as abas acessíveis.
const maxUnlockedTab = ref(props.isCreate && !props.viewOnly ? 1 : 6);

// Exibe flash message vinda do redirect (ex: após criar novo RAT)
onMounted(() => {
  const page = usePage();
  const flash = page.props?.flash;
  if (flash?.success) toast(flash.success, 'success', { noIcon: true });
  else if (flash?.info)    toast(flash.info,    'info',    { noIcon: true });
  else if (flash?.warning) toast(flash.warning, 'warning', { noIcon: true });
  else if (flash?.error)   toast(flash.error,   'error',   { noIcon: true });
});

// Só a aba que está sendo exibida no momento fica ativa.
// Abas anteriores (já preenchidas) e posteriores (ainda não alcançadas) ficam bloqueadas.
// Em edição sem isCreate: o mesmo princípio — só o tab atualmente visível é clicável.
// Em viewOnly: nenhum bloqueio.
function isTabDisabled(tabId) {
  if (props.viewOnly) return false;
  return tabId !== currentActiveTab.value;
}

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais',        icon: DocumentTextIcon, disabled: isTabDisabled(1) },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon,    badge: recursosState.value?.length > 0  ? recursosState.value.length  : null, disabled: isTabDisabled(2) },
  { id: 3, label: 'Envolvidos',          icon: UsersIcon,    badge: envolvidosState.value?.length > 0 ? envolvidosState.value.length : null, disabled: isTabDisabled(3) },
  { id: 4, label: 'Vistoria',            icon: ClipboardIcon, hidden: !temVistoria.value, disabled: isTabDisabled(4) },
  { id: 5, label: 'Histórico',           icon: ClockIcon,    disabled: isTabDisabled(5) },
  { id: 6, label: 'Anexos',             icon: PaperClipIcon, badge: Array.isArray(anexosState.value) && anexosState.value.length > 0 ? anexosState.value.length : null, disabled: isTabDisabled(6) },
]);

// ─── Save-and-Advance ──────────────────────────────────────────────────────

const TAB_LABELS = {
  dadosGerais: 'Dados Gerais',
  recursos:    'Recursos Empregados',
  envolvidos:  'Envolvidos',
  vistoria:    'Vistoria',
  historico:   'Histórico',
  anexos:      'Anexos',
};

const TAB_MESSAGES = {
  dadosGerais: 'Dados Gerais salvos com sucesso.',
  recursos:    'Recursos salvos com sucesso.',
  envolvidos:  'Envolvidos salvos com sucesso.',
  vistoria:    'Vistoria salva com sucesso.',
  historico:   'Histórico salvo com sucesso.',
  anexos:      'Anexos salvos com sucesso.',
};

function resolveTabLabel(payload) {
  const key = Object.keys(TAB_LABELS).find(k => k in payload);
  return key ? TAB_LABELS[key] : 'Dados';
}

function resolveToastMsg(payload) {
  const key = Object.keys(TAB_MESSAGES).find(k => k in payload);
  return key ? TAB_MESSAGES[key] : 'Dados salvos com sucesso.';
}

async function saveAndAdvance(tabPayload, nextTab) {
  const ratId = rat.value?.id;
  if (!ratId) {
    toast('Erro: RAT sem ID. Recarregue a página.', 'error', { noIcon: true });
    return;
  }

  const axios   = window.axios || (await import('axios')).default;
  const csrf    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const headers = { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' };

  loading.value = true;
  try {
    const resp = await axios.patch(route('rat.draft', ratId), tabPayload, { headers });

    if (resp.data?.numero_bos) localNumeroBos.value = resp.data.numero_bos;

    toast(resolveToastMsg(tabPayload), 'success', { noIcon: true });

    if (nextTab !== null) {
      maxUnlockedTab.value = Math.max(maxUnlockedTab.value, nextTab);
      tabs.setActiveTab(nextTab);
    } else {
      maxUnlockedTab.value = 6;
    }
  } catch (err) {
    const msg = err.response?.data?.message ?? err.message ?? 'Erro ao salvar.';
    toast('Erro ao salvar: ' + msg, 'error', { noIcon: true });
  } finally {
    loading.value = false;
  }
}

// ─── Finalizar ────────────────────────────────────────────────────────────

async function handleFinalize() {
  const ratId = rat.value?.id;
  if (!ratId) {
    toast('Salve o RAT antes de finalizar.', 'warning', { noIcon: true });
    return;
  }
  const axios  = window.axios || (await import('axios')).default;
  const csrf   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  loading.value = true;
  try {
    await axios.patch(route('rat.finalize', ratId), {}, {
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });
    toast('RAT finalizado com sucesso.', 'success', { noIcon: true });
    router.visit(route('rat.index'));
  } catch (err) {
    loading.value = false;
    toast('Erro ao finalizar: ' + (err.response?.data?.message ?? err.message), 'error', { noIcon: true });
  }
}

// ─── Handlers de estado local ──────────────────────────────────────────────

function handleTabChange(tabId) {
  tabs.setActiveTab(tabId);
  const params = new URLSearchParams(window.location.search);
  const aba = TAB_TO_ABA[tabId];
  if (aba) {
    params.set('aba', aba);
  } else {
    params.delete('aba');
  }
  window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
}

function handleToggleVistoria(value) { temVistoria.value = value; }

function handleAddRecurso(recurso) {
  if (!Array.isArray(recursosState.value)) recursosState.value = [];
  recursosState.value.push(recurso);
}
function handleRemoveRecurso(id) {
  if (!Array.isArray(recursosState.value)) return;
  const i = recursosState.value.findIndex(r => r.id === id);
  if (i > -1) recursosState.value.splice(i, 1);
}
function handleUpdateRecurso(data) { recursosState.value = Array.isArray(data) ? data : []; }

function handleAddEnvolvido(e) { envolvidosState.value.push(e); }
function handleRemoveEnvolvido(id) {
  const i = envolvidosState.value.findIndex(e => e.id === id);
  if (i > -1) envolvidosState.value.splice(i, 1);
}
function handleUpdateEnvolvidos(data) { envolvidosState.value = Array.isArray(data) ? data : []; }

function handleUpdateVistoria(data) { vistoriaState.value = { ...vistoriaState.value, ...data }; }
function handleUpdateHistorico(text) { historicoEstado.value = text; }

function handleAddAnexo(a) {
  if (!Array.isArray(anexosState.value)) anexosState.value = [];
  anexosState.value.push(a);
}
function handleRemoveAnexo(id) {
  if (!Array.isArray(anexosState.value)) return;
  const i = anexosState.value.findIndex(a => a.id === id);
  if (i > -1) anexosState.value.splice(i, 1);
}
function handleUpdateAnexos(data) {
  if (Array.isArray(data)) anexosState.value = [...data];
}
</script>

<style scoped>
.rat-container {
  @apply w-full min-h-screen bg-transparent;
}
</style>
