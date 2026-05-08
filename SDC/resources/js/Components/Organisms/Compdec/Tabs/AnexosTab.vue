<template>
  <div class="space-y-6">
    <div class="flex justify-between items-start gap-4">
      <div>
        <Heading level="3">Anexos Legais</Heading>
        <Text variant="muted" size="sm">
          {{ total }} documento(s) - {{ vencidos }} vencido(s), {{ proximos }} prox. vencimento
        </Text>
      </div>
      <Button
        v-if="canEdit"
        variant="primary"
        size="sm"
        :icon="PlusIcon"
        @click="openCreateModal"
      >
        Adicionar Anexo
      </Button>
    </div>

    <CardBase v-if="loading" class="text-center py-12">
      <Text variant="muted">Carregando anexos...</Text>
    </CardBase>

    <AnexosTable
      v-else
      :anexos="anexosList"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-download="canDownload"
      @edit="openEditModal"
      @delete="handleDelete"
      @download="handleDownload"
    />

    <AnexoUploadModal
      :show="modalOpen"
      :orgao-id="orgao.id"
      :anexo="editingAnexo"
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
import AnexosTable from '@/Components/Organisms/Compdec/AnexosTable.vue';
import AnexoUploadModal from '@/Components/Organisms/Compdec/Modals/AnexoUploadModal.vue';

const props = defineProps({
  orgao: { type: Object, required: true },
  anexos: { type: Object, default: null },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canDownload: { type: Boolean, default: false },
});

const loading = ref(false);
const modalOpen = ref(false);
const editingAnexo = ref(null);

const anexosList = computed(() => {
  if (!props.anexos) return [];
  return props.anexos.data ?? props.anexos ?? [];
});

const total = computed(() => anexosList.value.length);
const vencidos = computed(() => anexosList.value.filter((a) => a.status_validade === 'vencido').length);
const proximos = computed(() => anexosList.value.filter((a) => a.status_validade === 'prox_vencimento').length);

onMounted(() => {
  if (props.anexos === null || props.anexos === undefined) {
    reloadAnexos();
  }
});

function reloadAnexos() {
  loading.value = true;
  router.reload({
    only: ['anexos'],
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { loading.value = false; },
  });
}

function openCreateModal() {
  editingAnexo.value = null;
  modalOpen.value = true;
}

function openEditModal(anexo) {
  editingAnexo.value = anexo;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
  editingAnexo.value = null;
}

function handleSaved() {
  closeModal();
  reloadAnexos();
}

function handleDelete(anexo) {
  if (!confirm(`Remover "${anexo.titulo}"?`)) return;

  router.delete(
    route('compdec.anexos.destroy', { orgao: props.orgao.id, anexo: anexo.id }),
    {
      preserveScroll: true,
      onSuccess: () => reloadAnexos(),
    },
  );
}

function handleDownload(anexo) {
  const url = route('compdec.anexos.download', { orgao: props.orgao.id, anexo: anexo.id });
  window.open(url, '_blank');
}
</script>
