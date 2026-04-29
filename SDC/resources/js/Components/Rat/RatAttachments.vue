<template>
  <div class="animate-fade-in-up pb-6">
    <RatAttachmentsSection
      v-model="localAnexos"
      :uploading="uploading"
      :upload-error="uploadError"
      :view-only="viewOnly"
      @update:modelValue="handleUpdate"
      @upload-file="handleUploadFile"
      @remove-file="handleRemoveFile"
    />

    <RatFormActions :view-only="viewOnly" :loading="uploading" @save="$emit('save')" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  ratId: {
    type: String,
    default: null,
  },
  anexos: {
    type: Array,
    default: () => [],
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['add', 'remove', 'update', 'save']);

const uploading   = ref(false);
const uploadError = ref(null);

const localAnexos = ref({
  anexos: props.anexos || [],
});

watch(
  () => props.anexos,
  (newAnexos) => {
    if (!newAnexos || newAnexos.length === 0) {
      localAnexos.value.anexos = [];
      return;
    }
    const currentIds = (localAnexos.value.anexos || []).map(a => a.id).filter(Boolean).sort().join(',');
    const newIds     = newAnexos.map(a => a.id).filter(Boolean).sort().join(',');
    if (currentIds !== newIds) {
      localAnexos.value.anexos = [...newAnexos];
    }
  },
  { deep: false, immediate: true }
);

/**
 * Upload a single File object to the backend.
 * On success the server-returned metadata (with real URL/path) replaces the
 * optimistic preview entry added by RatAttachmentsSection.
 */
async function handleUploadFile({ file, tempId }) {
  // CORRIGIDO: Validar se ratId existe antes de fazer upload
  if (!props.ratId) {
    uploadError.value = '❌ Erro: RAT não foi criado ainda. Salve o RAT primeiro antes de anexar arquivos.';
    const updated = localAnexos.value.anexos.filter(a => a.id !== tempId);
    localAnexos.value.anexos = updated;
    emit('update', updated);
    return;
  }

  uploading.value   = true;
  uploadError.value = null;

  const form = new FormData();
  form.append('arquivo', file);

  // Detectar tipo para o backend
  let tipo = 'documento';
  if (file.type.startsWith('image/')) tipo = 'imagem';
  else if (file.type.startsWith('video/')) tipo = 'video';
  else if (file.type.startsWith('audio/')) tipo = 'audio';

  form.append('tipo', tipo);
  form.append('descricao', file.name);

  try {
    const axios    = window.axios || (await import('axios')).default;
    const response = await axios.post(route('rat.ocorrencias.attachments.store', props.ratId), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const serverAnexo = response.data;

    // Replace the optimistic temp entry with the persisted one
    const updated = localAnexos.value.anexos.map(a => (a.id === tempId ? serverAnexo : a));
    localAnexos.value.anexos = updated;
    emit('add', serverAnexo);
    emit('update', updated);
  } catch (err) {
    uploadError.value = err.response?.data?.message ?? 'Erro ao fazer upload. Tente novamente.';
    // Remove the optimistic entry on failure
    const updated = localAnexos.value.anexos.filter(a => a.id !== tempId);
    localAnexos.value.anexos = updated;
    emit('update', updated);
  } finally {
    uploading.value = false;
  }
}

/**
 * Delete a persisted attachment from the backend.
 */
async function handleRemoveFile(anexoId) {
  // If the id is a number (temp/local-only), no server call needed
  const isTemp = typeof anexoId === 'number';
  if (!isTemp) {
    try {
      const axios = window.axios || (await import('axios')).default;
      await axios.delete(route('rat.ocorrencias.attachments.destroy', { id: props.ratId, anexoId }));
    } catch (err) {
      // removal error handled silently
    }
  }

  const updated = localAnexos.value.anexos.filter(a => a.id !== anexoId);
  localAnexos.value.anexos = updated;
  emit('remove', anexoId);
  emit('update', updated);
}

function handleUpdate(newValue) {
  if (newValue?.anexos) {
    emit('update', newValue.anexos);
  }
}
</script>

