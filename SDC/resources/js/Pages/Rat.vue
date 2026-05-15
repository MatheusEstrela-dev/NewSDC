<template>
    <div>
        <Head title="Gestão de RAT" />

        <div class="rat-container">
          <!-- Header -->
          <RatHeader :rat="rat" :last-update="lastUpdate" :view-only="props.viewOnly" :is-create="props.isCreate" />

          <!-- Sistema de Abas -->
          <RatTabs :active-tab="currentActiveTab" :tabs="tabConfig" @tab-change="tabs.setActiveTab">
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
<<<<<<< Updated upstream
const localNumeroBos = ref(props.rat?.numero_bos ?? null);

// Exibe flash message vinda do redirect (ex: após criar novo RAT)
onMounted(() => {
  const page = usePage();
  const flash = page.props?.flash;
  if (flash?.success) toast(flash.success, 'success', { noIcon: true });
  else if (flash?.info)    toast(flash.info,    'info',    { noIcon: true });
  else if (flash?.warning) toast(flash.warning, 'warning', { noIcon: true });
  else if (flash?.error)   toast(flash.error,   'error',   { noIcon: true });
});
=======
const currentFormData = ref(null);
const creating        = ref(false);
const ratDebugEnabled = (() => {
  try {
    const params = new URLSearchParams(window.location.search);
    return params.get('debug') === 'rat' || window.localStorage?.getItem('rat_debug') === '1';
  } catch {
    return false;
  }
})();
>>>>>>> Stashed changes

const tabConfig = computed(() => [
  { id: 1, label: 'Dados Gerais',        icon: DocumentTextIcon },
  { id: 2, label: 'Recursos Empregados', icon: TruckIcon,    badge: recursosState.value?.length > 0  ? recursosState.value.length  : null },
  { id: 3, label: 'Envolvidos',          icon: UsersIcon,    badge: envolvidosState.value?.length > 0 ? envolvidosState.value.length : null },
  { id: 4, label: 'Vistoria',            icon: ClipboardIcon, hidden: !temVistoria.value },
  { id: 5, label: 'Histórico',           icon: ClockIcon },
  { id: 6, label: 'Anexos',             icon: PaperClipIcon, badge: Array.isArray(anexosState.value) && anexosState.value.length > 0 ? anexosState.value.length : null },
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
  const ratId  = rat.value?.id;
  if (!ratId) {
    toast('RAT ainda não foi criado. Atualize a página.', 'warning', { noIcon: true });
    return;
  }

  const axios  = window.axios || (await import('axios')).default;
  const csrf   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const headers = { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' };

  loading.value = true;
  try {
<<<<<<< Updated upstream
    const payload = { ...tabPayload };
    const resp = await axios.patch(route('rat.draft', ratId), payload, { headers });
=======
    if (!ratId) {
      creating.value = true;
      const payload = {
        ...buildDadosGeraisPayload(),
        recursos:   recursosState.value,
        envolvidos: envolvidosState.value,
        vistoria:   vistoriaState.value,
        historico:  historicoEstado.value,
        ...tabPayload,
      };
      if (ratDebugEnabled) {
        payload._debug = 'rat';
        console.groupCollapsed('[RAT_DEBUG_CREATE] frontend payload POST /rat');
        console.info('tabPayload', tabPayload);
        console.info('payload', payload);
        console.groupEnd();
      }
      router.post(route('rat.store'), payload, {
        preserveScroll: false,
        onSuccess: () => {
          if (ratDebugEnabled) {
            console.info('[RAT_DEBUG_CREATE] frontend success POST /rat');
          }
          toast('RAT criado com sucesso.', 'success', { noIcon: true });
          if (nextTab !== null) tabs.setActiveTab(nextTab);
        },
        onError:   (errors) => {
          if (ratDebugEnabled) {
            console.error('[RAT_DEBUG_CREATE] frontend error POST /rat', errors);
          }
          toast('Erro ao salvar dados gerais.', 'error', { noIcon: true });
        },
        onFinish:  () => { loading.value = false; creating.value = false; },
      });
    } else {
      const payload = { ...tabPayload };
      await axios.patch(route('rat.draft', ratId), payload, { headers });
>>>>>>> Stashed changes

    // Atualiza o número do BOS no header se o backend retornou um novo valor
    if (resp.data?.numero_bos) localNumeroBos.value = resp.data.numero_bos;

    toast(resolveToastMsg(tabPayload), 'success', { noIcon: true });

    if (nextTab !== null) {
      tabs.setActiveTab(nextTab);
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
  if (!confirm('Deseja finalizar este RAT? Esta ação não poderá ser desfeita.')) return;

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
