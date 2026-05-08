<template>
  <div class="orgao-show">
    <!-- Header -->
    <div class="header-section">
      <div class="header-content">

        <div class="header-title">
          <Heading level="1">{{ orgao.nome }}</Heading>
          <div class="header-meta">
            <TipoOrgaoBadge :tipo="orgao.tipo" />
            <StatusOrgaoBadge :status="orgao.status" />
            <Text variant="muted">Codigo: {{ orgao.codigo }}</Text>
            <Text v-if="orgao.municipio" variant="muted">
              {{ orgao.municipio.nome }}{{ orgao.municipio.uf ? ' - ' + orgao.municipio.uf : '' }}
            </Text>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <CompdecTabs v-model:active-tab="activeTab" :tabs="tabs">
      <template #default="{ activeTab: current }">
        <GeralTab v-if="current === 'geral'" :orgao="orgao" />

        <CapacidadesTab v-if="current === 'capacidades'" :orgao="orgao" />

        <PrefeituraTab
          v-if="current === 'prefeitura'"
          :orgao="orgao"
          :prefeitura="orgao.prefeitura"
          :can-edit="canManage"
          :errors="errors"
        />

        <EquipeTab
          v-if="current === 'equipe'"
          :orgao="orgao"
          :equipe="equipe"
          :can-edit="canManageEquipe"
          :can-delete="canDeleteEquipe"
        />

        <AnexosTab
          v-if="current === 'anexos'"
          :orgao="orgao"
          :anexos="anexos"
          :can-edit="canManageAnexos"
          :can-delete="canDeleteAnexos"
          :can-download="canDownloadAnexos"
        />

        <!-- Placeholder F4 -->
        <CardBase v-if="current === 'plano'" class="text-center py-12">
          <component :is="ClipboardDocumentListIcon" class="w-12 h-12 mx-auto text-slate-400 mb-3" />
          <Heading level="3" class="mb-2">Plano de Contingencia</Heading>
          <Text variant="muted">Disponivel na proxima fase de implementacao.</Text>
        </CardBase>
      </template>
    </CompdecTabs>

    <!-- Usuarios Vinculados (acesso ao sistema) -->
    <CardBase class="mt-8">
      <div class="usuarios-header">
        <div>
          <Heading level="3">Usuarios com Acesso ao Sistema</Heading>
          <Text variant="muted" size="sm">
            {{ usuarios.length }} usuario(s) vinculado(s) a este orgao
          </Text>
        </div>
        <Button
          v-if="canVincularUsuarios"
          variant="primary"
          size="sm"
          :icon="PlusIcon"
          @click="handleVincularUsuario"
        >
          Vincular Usuario
        </Button>
      </div>

      <table v-if="usuarios.length > 0" class="usuarios-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Funcao</th>
            <th>Principal</th>
            <th v-if="canVincularUsuarios" class="actions-col">Acoes</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="usuario in usuarios" :key="usuario.id">
            <td>{{ usuario.name }}</td>
            <td>{{ usuario.email }}</td>
            <td>
              <span class="funcao-badge">{{ usuario.pivot?.funcao || '-' }}</span>
            </td>
            <td>
              <StatusOrgaoBadge v-if="usuario.pivot?.is_principal" status="ativo" />
              <Text v-else variant="muted">-</Text>
            </td>
            <td v-if="canVincularUsuarios" class="actions-col">
              <div class="flex justify-end">
                <ActionButton
                  module="compdec"
                  action="delete"
                  size="sm"
                  :show-label="false"
                  :allowed="canVincularUsuarios"
                  tooltip-text="Voce nao possui permissao para remover vinculos de usuarios"
                  @click="handleDesvincularUsuario(usuario.id)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <Text variant="muted">Nenhum usuario vinculado.</Text>
      </div>
    </CardBase>

    <!-- Acoes da pagina -->
    <div v-if="canManage || canDelete" class="page-actions-footer">
      <Button
        v-if="canManage"
        variant="secondary"
        :icon="PencilIcon"
        @click="handleEdit"
      >
        Editar Dados Gerais
      </Button>
      <ActionButton
        v-if="canDelete"
        module="compdec"
        action="delete"
        :allowed="canDelete"
        label="Excluir"
        @click="handleDelete"
      >
        Excluir
      </ActionButton>
    </div>

    <!-- Modais -->
    <VincularUsuarioModal
      :show="showVincularModal"
      :orgao-id="orgao?.id || orgao?.data?.id"
      @close="showVincularModal = false"
      @saved="handleVincularSaved"
    />

    <ConfirmDialog
      :is-open="deleteDialog.open"
      variant="danger"
      :title="deleteDialog.title"
      :message="deleteDialog.message"
      :description="deleteDialog.description"
      :confirm-text="deleteDialog.confirmText"
      cancel-text="Cancelar"
      :loading="deleteDialog.loading"
      @confirm="confirmDelete"
      @cancel="closeDeleteDialog"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusOrgaoBadge from '@/Components/Molecules/Compdec/StatusOrgaoBadge.vue';
import TipoOrgaoBadge from '@/Components/Molecules/Compdec/TipoOrgaoBadge.vue';
import CompdecTabs from '@/Components/Organisms/Compdec/CompdecTabs.vue';
import GeralTab from '@/Components/Organisms/Compdec/Tabs/GeralTab.vue';
import CapacidadesTab from '@/Components/Organisms/Compdec/Tabs/CapacidadesTab.vue';
import PrefeituraTab from '@/Components/Organisms/Compdec/Tabs/PrefeituraTab.vue';
import EquipeTab from '@/Components/Organisms/Compdec/Tabs/EquipeTab.vue';
import AnexosTab from '@/Components/Organisms/Compdec/Tabs/AnexosTab.vue';
import VincularUsuarioModal from '@/Components/Organisms/Compdec/Modals/VincularUsuarioModal.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { useToast } from '@/composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
  usuarios: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  canManage: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canManageEquipe: {
    type: Boolean,
    default: false,
  },
  canDeleteEquipe: {
    type: Boolean,
    default: false,
  },
  canVincularUsuarios: {
    type: Boolean,
    default: false,
  },
  equipe: {
    type: Object,
    default: null,
  },
  anexos: {
    type: Object,
    default: null,
  },
  canManageAnexos: {
    type: Boolean,
    default: false,
  },
  canDeleteAnexos: {
    type: Boolean,
    default: false,
  },
  canDownloadAnexos: {
    type: Boolean,
    default: false,
  },
});

// Estado do modal de vincular usuario
const showVincularModal = ref(false);
const { toast } = useToast();
const deleteDialog = ref({
  open: false,
  loading: false,
  type: null,
  payload: null,
  title: '',
  message: '',
  description: '',
  confirmText: 'Remover',
});

// Estado da aba ativa - sincronizado com query string ?tab=
const initialTab = (() => {
  if (typeof window === 'undefined') return 'geral';
  const url = new URL(window.location.href);
  return url.searchParams.get('tab') || 'geral';
})();
const activeTab = ref(initialTab);

watch(activeTab, (newTab) => {
  if (typeof window === 'undefined') return;
  const url = new URL(window.location.href);
  url.searchParams.set('tab', newTab);
  window.history.replaceState({}, '', url.toString());
});

const tabs = computed(() => [
  {
    id: 'geral',
    label: 'Geral',
    icon: BuildingOfficeIcon,
  },
  {
    id: 'capacidades',
    label: 'Capacidades',
    icon: CheckBadgeIcon,
  },
  {
    id: 'prefeitura',
    label: 'Prefeitura',
    icon: HomeIcon,
  },
  {
    id: 'equipe',
    label: 'Equipe',
    icon: UsersIcon,
    badge: props.orgao?.equipes_count ?? null,
  },
  {
    id: 'anexos',
    label: 'Documentos',
    icon: DocumentIcon,
    badge: props.orgao?.anexos_count ?? null,
  },
  {
    id: 'plano',
    label: 'Plano de Contingencia',
    icon: ClipboardDocumentListIcon,
    disabled: true,
    disabledReason: 'Disponivel na proxima fase de implementacao',
    badge: props.orgao?.planos_count ?? null,
  },
]);


function handleEdit() {
  router.visit(route('compdec.edit', props.orgao.id));
}

function handleDelete() {
  deleteDialog.value = {
    open: true,
    loading: false,
    type: 'orgao',
    payload: props.orgao,
    title: 'Excluir Orgao',
    message: 'Tem certeza que deseja excluir este orgao?',
    description: 'Orgaos com usuarios vinculados ou subordinados nao podem ser excluidos.',
    confirmText: 'Excluir',
  };
}

function handleVincularUsuario() {
  showVincularModal.value = true;
}

function handleVincularSaved() {
  showVincularModal.value = false;
  toast('Usuario vinculado ao orgao.', 'success');
  router.reload({ only: ['usuarios'], preserveScroll: true });
}

function handleDesvincularUsuario(userId) {
  const usuario = props.usuarios.find((item) => item.id === userId);
  deleteDialog.value = {
    open: true,
    loading: false,
    type: 'usuario',
    payload: { id: userId, nome: usuario?.name || 'este usuario' },
    title: 'Remover Vinculo',
    message: `Tem certeza que deseja remover o vinculo de ${usuario?.name || 'este usuario'}?`,
    description: 'O usuario deixara de ter acesso ao sistema por este orgao.',
    confirmText: 'Remover',
  };
}

function closeDeleteDialog() {
  if (deleteDialog.value.loading) return;
  deleteDialog.value.open = false;
  deleteDialog.value.type = null;
  deleteDialog.value.payload = null;
}

function confirmDelete() {
  if (deleteDialog.value.type === 'orgao') {
    confirmDeleteOrgao();
    return;
  }

  if (deleteDialog.value.type === 'usuario') {
    confirmDesvincularUsuario();
  }
}

function confirmDeleteOrgao() {
  let handled = false;
  deleteDialog.value.loading = true;
  router.delete(route('compdec.destroy', props.orgao?.id || props.orgao?.data?.id), {
    onSuccess: () => {
      handled = true;
      toast('Orgao excluido com sucesso.', 'success');
      deleteDialog.value.loading = false;
      closeDeleteDialog();
    },
    onError: () => {
      handled = true;
      toast('Nao foi possivel excluir este orgao. Verifique vinculos, subordinados e permissoes.', 'error');
    },
    onFinish: () => {
      deleteDialog.value.loading = false;
      if (!handled) {
        toast('Nao foi possivel excluir este orgao. A requisicao foi rejeitada pelo servidor.', 'error');
      }
    },
  });
}

function confirmDesvincularUsuario() {
  const usuario = deleteDialog.value.payload;
  if (!usuario?.id) return;

  let handled = false;
  deleteDialog.value.loading = true;
  router.delete(route('compdec.usuarios.desvincular', {
    orgao: props.orgao?.id || props.orgao?.data?.id,
    usuario: usuario.id,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      handled = true;
      toast('Vinculo removido com sucesso.', 'success');
      deleteDialog.value.loading = false;
      closeDeleteDialog();
      router.reload({ only: ['usuarios'], preserveScroll: true });
    },
    onError: () => {
      handled = true;
      toast('Nao foi possivel remover o vinculo do usuario. Verifique suas permissoes e tente novamente.', 'error');
    },
    onFinish: () => {
      deleteDialog.value.loading = false;
      if (!handled) {
        toast('Nao foi possivel remover o vinculo. A requisicao foi rejeitada pelo servidor.', 'error');
      }
    },
  });
}
</script>

<style scoped>
.orgao-show {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  gap: 2rem;
  flex-wrap: wrap;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.header-title {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.header-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.usuarios-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.usuarios-table {
  width: 100%;
  border-collapse: collapse;
}

.usuarios-table thead {
  @apply bg-slate-50 dark:bg-slate-900/40 border-b-2 border-slate-200 dark:border-slate-700;
}

.usuarios-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  @apply text-slate-600 dark:text-slate-400;
}

.usuarios-table td {
  padding: 0.75rem 1rem;
  @apply border-b border-slate-100 dark:border-slate-800;
}

.actions-col {
  text-align: right;
  width: 120px;
}

.funcao-badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  @apply bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300;
}

.empty-state {
  padding: 2rem 0;
  text-align: center;
}

.page-actions-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid;
  @apply border-slate-200 dark:border-slate-800;
}

@media (max-width: 640px) {
  .page-actions-footer {
    flex-direction: column;
  }
}
</style>
