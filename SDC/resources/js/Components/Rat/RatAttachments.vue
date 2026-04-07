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

    <!-- Footer de ações — padrão das demais abas -->
    <div v-if="!viewOnly" class="rat-actions-footer mt-4">
      <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 py-3 sm:px-6 sm:py-4">
        <button
          type="button"
          @click="$emit('save')"
          :disabled="uploading"
          class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-1.5 sm:gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Salvar Anexos
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';

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
  uploading.value   = true;
  uploadError.value = null;

  const form = new FormData();
  form.append('file', file);

  try {
    const axios    = window.axios || (await import('axios')).default;
    const response = await axios.post(route('compdec.rat.ocorrencias.attachments.store', props.ratId), form, {
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
      await axios.delete(route('compdec.rat.ocorrencias.attachments.destroy', { id: props.ratId, anexoId }));
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

