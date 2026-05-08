<template>
  <div class="space-y-6">
    <div class="flex justify-between items-start gap-4">
      <div>
        <Heading level="3">Plano de Contingencia</Heading>
        <Text variant="muted" size="sm">
          {{ total }} plano(s) - {{ ativos }} ativo(s)
        </Text>
      </div>
      <Button
        v-if="canCreate"
        variant="primary"
        size="sm"
        :icon="PlusIcon"
        @click="openCreateModal"
      >
        Novo Plano
      </Button>
    </div>

    <CardBase v-if="loading" class="text-center py-12">
      <Text variant="muted">Carregando planos...</Text>
    </CardBase>

    <PlanosTable
      v-else
      :planos="planosList"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-aprovar="canAprovar"
      :can-download="canDownload"
      @edit="openEditModal"
      @delete="handleDelete"
      @ativar="handleAtivar"
      @aprovar="handleAprovar"
      @download="handleDownload"
    />

    <PlanoUploadModal
      :show="modalOpen"
      :orgao-id="orgao.id"
      :plano="editingPlano"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { PlusIcon } from '@heroicons/vue/24/outline';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlanosTable from '@/Components/Organisms/Compdec/PlanosTable.vue';
import PlanoUploadModal from '@/Components/Organisms/Compdec/Modals/PlanoUploadModal.vue';

const props = defineProps({
  orgao: { type: Object, required: true },
  planos: { type: Object, default: null },
  canCreate: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canAprovar: { type: Boolean, default: false },
  canDownload: { type: Boolean, default: false },
});

const loading = ref(false);
const modalOpen = ref(false);
const editingPlano = ref(null);

const planosList = computed(() => {
  if (!props.planos) return [];
  return props.planos.data ?? props.planos ?? [];
});

const total = computed(() => planosList.value.length);
const ativos = computed(() => planosList.value.filter((p) => p.ativo).length);

onMounted(() => {
  if (props.planos === null || props.planos === undefined) {
    reloadPlanos();
  }
});

function reloadPlanos() {
  loading.value = true;
  router.reload({
    only: ['planos'],
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { loading.value = false; },
  });
}

function openCreateModal() {
  editingPlano.value = null;
  modalOpen.value = true;
}

function openEditModal(plano) {
  editingPlano.value = plano;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
  editingPlano.value = null;
}

function handleSaved() {
  closeModal();
  reloadPlanos();
}

function handleDelete(plano) {
  if (!confirm(`Remover plano "${plano.versao}"?`)) return;

  router.delete(
    route('compdec.planos.destroy', { orgao: props.orgao.id, plano: plano.id }),
    { preserveScroll: true, onSuccess: reloadPlanos },
  );
}

function handleAtivar(plano) {
  if (!confirm(`Ativar plano "${plano.versao}"? Isso desativa o plano atual.`)) return;

  router.post(
    route('compdec.planos.ativar', { orgao: props.orgao.id, plano: plano.id }),
    {},
    { preserveScroll: true, onSuccess: reloadPlanos },
  );
}

function handleAprovar(plano) {
  if (!confirm(`Aprovar plano "${plano.versao}"?`)) return;

  router.post(
    route('compdec.planos.aprovar', { orgao: props.orgao.id, plano: plano.id }),
    {},
    { preserveScroll: true, onSuccess: reloadPlanos },
  );
}

function handleDownload(plano) {
  const url = route('compdec.planos.download', { orgao: props.orgao.id, plano: plano.id });
  window.open(url, '_blank');
}
</script>
