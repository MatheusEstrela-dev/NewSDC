<template>
  <div class="space-y-6">
    <!-- Loading state (lazy partial reload) -->
    <CardBase v-if="loading" class="text-center py-12">
      <Text variant="muted">Carregando equipe...</Text>
    </CardBase>

    <!-- Lista -->
    <EquipeTable
      v-else
      :membros="membros"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @create="openCreateModal"
      @edit="openEditModal"
      @delete="handleDelete"
    />

    <!-- Modal CRUD -->
    <EquipeFormModal
      :show="modalOpen"
      :orgao-id="orgao?.id || orgao?.data?.id"
      :equipe="editingEquipe"
      @close="closeModal"
      @saved="handleSaved"
    />

    <ConfirmDialog
      :is-open="deleteDialog.open"
      variant="danger"
      title="Remover Membro"
      :message="deleteDialog.message"
      description="O membro sera removido da equipe deste orgao."
      confirm-text="Remover"
      cancel-text="Cancelar"
      :loading="deleteDialog.loading"
      @confirm="confirmDelete"
      @cancel="closeDeleteDialog"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import EquipeTable from '@/Components/Organisms/Compdec/EquipeTable.vue';
import EquipeFormModal from '@/Components/Organisms/Compdec/Modals/EquipeFormModal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
  equipe: {
    type: Object,
    default: null,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const loading = ref(false);
const modalOpen = ref(false);
const editingEquipe = ref(null);
const { toast } = useToast();
const deleteDialog = ref({
  open: false,
  loading: false,
  membro: null,
  message: '',
});

const membros = computed(() => {
  if (!props.equipe) return [];
  // EquipeIndexResource::collection retorna { data: [...], links, meta }
  return props.equipe.data ?? props.equipe ?? [];
});

onMounted(() => {
  // Lazy load via Inertia partial reload se ainda nao carregado
  if (props.equipe === null || props.equipe === undefined) {
    reloadEquipe();
  }
});

function reloadEquipe() {
  loading.value = true;
  router.reload({
    only: ['equipe'],
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { loading.value = false; },
  });
}

function openCreateModal() {
  editingEquipe.value = null;
  modalOpen.value = true;
}

function openEditModal(membro) {
  editingEquipe.value = membro;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
  editingEquipe.value = null;
}

function handleSaved() {
  closeModal();
  reloadEquipe();
}

function handleDelete(membro) {
  deleteDialog.value = {
    open: true,
    loading: false,
    membro,
    message: `Tem certeza que deseja remover ${membro.nome} da equipe?`,
  };
}

function closeDeleteDialog() {
  if (deleteDialog.value.loading) return;
  deleteDialog.value.open = false;
  deleteDialog.value.membro = null;
}

function confirmDelete() {
  const membro = deleteDialog.value.membro;
  if (!membro) return;

  let handled = false;
  deleteDialog.value.loading = true;
  router.delete(
    route('compdec.equipe.destroy', { orgao: props.orgao?.id || props.orgao?.data?.id, equipe: membro.id }),
    {
      preserveScroll: true,
      onSuccess: () => {
        handled = true;
        toast('Membro removido da equipe.', 'success');
        deleteDialog.value.loading = false;
        closeDeleteDialog();
        reloadEquipe();
      },
      onError: () => {
        handled = true;
        toast('Nao foi possivel remover o membro da equipe. Verifique suas permissoes e tente novamente.', 'error');
      },
      onFinish: () => {
        deleteDialog.value.loading = false;
        if (!handled) {
          toast('Nao foi possivel remover o membro. A requisicao foi rejeitada pelo servidor.', 'error');
        }
      },
    },
  );
}
</script>
