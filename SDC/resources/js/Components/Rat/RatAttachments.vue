<template>
  <div class="animate-fade-in-up pb-6">
    <!-- Aviso: arquivos pendentes aguardando salvamento do RAT -->
    <div
      v-if="pendingFiles.length > 0 && !ratId"
      class="mb-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/30 text-sm text-amber-400 flex items-center gap-2"
    >
      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span>
        {{ pendingFiles.length }} arquivo(s) pendente(s). Salve o RAT primeiro (aba Dados Gerais) para enviar os arquivos ao servidor.
      </span>
    </div>

    <RatAttachmentsSection
      v-model="localAnexos"
      :uploading="uploading"
      :upload-error="uploadError"
      :view-only="viewOnly"
      @update:modelValue="handleUpdate"
      @upload-file="handleUploadFile"
      @remove-file="handleRemoveFile"
    />

    <!-- Footer de acoes -->
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

const emit = defineEmits(['add', 'remove', 'update', 'save', 'update:pending-files']);

const uploading   = ref(false);
const uploadError = ref(null);

/** Fila de arquivos aguardando o RAT ser salvo (ratId ainda e null). */
const pendingFiles = ref([]);

/** Notifica o parent sempre que a fila de pendentes mudar. */
watch(pendingFiles, (files) => {
  emit('update:pending-files', [...files]);
}, { deep: true });

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
 * Quando o ratId muda de null para um valor real, faz flush da fila pendente.
 */
watch(
  () => props.ratId,
  async (newId, oldId) => {
    if (newId && !oldId && pendingFiles.value.length > 0) {
      const filesToUpload = [...pendingFiles.value];
      pendingFiles.value = [];

      for (const { file, tempId } of filesToUpload) {
        await doUpload(file, tempId);
      }
    }
  }
);

/**
 * Envia o arquivo para o backend via API.
 */
async function doUpload(file, tempId) {
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

  const categoria = file.type?.startsWith('image/') ? 'imagem' : 'documento';
  form.append('categoria', categoria);

  // Detectar tipo para o backend
  let tipo = 'documento';
  if (file.type.startsWith('image/')) tipo = 'imagem';
  else if (file.type.startsWith('video/')) tipo = 'video';
  else if (file.type.startsWith('audio/')) tipo = 'audio';

  form.append('tipo', tipo);
  form.append('descricao', file.name);

  try {
    const axios    = window.axios || (await import('axios')).default;
    const response = await axios.post(route('rat.anexos.store', { id: props.ratId }), form, {
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
 * Upload a single File object to the backend.
 * If ratId is null (create page), queue the file for later.
 */
async function handleUploadFile({ file, tempId }) {
  if (!props.ratId) {
    // RAT ainda não foi salvo — enfileira o arquivo para upload posterior
    pendingFiles.value.push({ file, tempId });
    uploadError.value = null;
    return;
  }

  await doUpload(file, tempId);
}

/**
 * Delete a persisted attachment from the backend.
 */
async function handleRemoveFile(anexoId) {
  // If the id is a number (temp/local-only) or ratId is null, no server call needed
  const isTemp = typeof anexoId === 'number';
  if (!isTemp && props.ratId) {
    try {
      const axios = window.axios || (await import('axios')).default;
      await axios.delete(route('rat.anexos.destroy', { id: props.ratId, anexo: anexoId }));
      await axios.delete(route('rat.ocorrencias.attachments.destroy', { id: props.ratId, anexoId }));
    } catch (err) {
      // removal error handled silently
    }
  }

  // Also remove from pending queue if present
  pendingFiles.value = pendingFiles.value.filter(p => p.tempId !== anexoId);

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

