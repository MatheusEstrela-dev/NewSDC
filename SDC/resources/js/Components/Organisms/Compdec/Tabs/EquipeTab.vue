<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-start gap-4">
      <div>
        <Heading level="3">Equipe</Heading>
        <Text variant="muted" size="sm">
          {{ totalAtivos }} ativo(s) de {{ total }} membro(s) cadastrado(s)
        </Text>
      </div>
      <Button
        v-if="canEdit"
        variant="primary"
        size="sm"
        :icon="PlusIcon"
        @click="openCreateModal"
      >
        Adicionar Membro
      </Button>
    </div>

    <!-- Loading state (lazy partial reload) -->
    <CardBase v-if="loading" class="text-center py-12">
      <Text variant="muted">Carregando equipe...</Text>
    </CardBase>

    <!-- Lista -->
    <EquipeTable
      v-else
      :membros="membros"
      :can-edit="canEdit"
      @edit="openEditModal"
      @delete="handleDelete"
    />

    <!-- Modal CRUD -->
    <EquipeFormModal
      :show="modalOpen"
      :orgao-id="orgao.id"
      :equipe="editingEquipe"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import EquipeTable from '@/Components/Organisms/Compdec/EquipeTable.vue';
import EquipeFormModal from '@/Components/Organisms/Compdec/Modals/EquipeFormModal.vue';

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
});

const loading = ref(false);
const modalOpen = ref(false);
const editingEquipe = ref(null);

const membros = computed(() => {
  if (!props.equipe) return [];
  // EquipeIndexResource::collection retorna { data: [...], links, meta }
  return props.equipe.data ?? props.equipe ?? [];
});

const total = computed(() => membros.value.length);
const totalAtivos = computed(() => membros.value.filter((m) => m.ativo).length);

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
  if (!confirm(`Remover ${membro.nome} da equipe?`)) return;

  router.delete(
    route('compdec.equipe.destroy', { orgao: props.orgao.id, equipe: membro.id }),
    {
      preserveScroll: true,
      onSuccess: () => reloadEquipe(),
    },
  );
}
</script>
