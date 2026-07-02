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
      :accepted-types="acceptedTypes"
      @update:modelValue="handleUpdate"
      @upload-file="handleUploadFile"
      @remove-file="handleRemoveFile"
    />

    <!-- Footer: botões de ação da aba Anexos -->
    <div v-if="!viewOnly" class="rat-actions-footer">
      <!-- Aviso: upload em andamento -->
      <div v-if="uploading" class="flex justify-center mb-3">
        <span class="inline-flex items-center gap-2 text-xs text-blue-400 bg-blue-500/10 border border-blue-500/20 px-3 py-1.5 rounded-lg">
          <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          Enviando arquivo ao servidor... aguarde antes de salvar.
        </span>
      </div>

      <div class="flex items-center justify-end gap-3 flex-wrap">
        <button
          type="button"
          :disabled="uploading"
          @click="$emit('save')"
          class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold
                 bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                 text-white shadow-sm transition-colors duration-150
                 disabled:opacity-60 disabled:cursor-not-allowed min-w-[160px] justify-center"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
          </svg>
          Salvar
        </button>

        <!-- Finalizar RAT: oculto se já finalizado -->
        <button
          v-if="!isAlreadyFinalizado"
          type="button"
          :disabled="uploading"
          @click="handleFinalizeClick"
          class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold
                 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800
                 text-white shadow-sm transition-colors duration-150
                 disabled:opacity-60 disabled:cursor-not-allowed min-w-[160px] justify-center"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Finalizar RAT
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';
import { useToast } from '@/Composables/useToast';

// MIME types aceitos: imagens, PDF, Word, Excel, texto
const acceptedTypes = [
  'image/*',
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'text/plain',
  'text/csv',
].join(',');

const props = defineProps({
  ratId:       { type: String,  default: null },
  anexos:      { type: Array,   default: () => [] },
  viewOnly:    { type: Boolean, default: false },
  ratStatus:   { type: String,  default: null },
});

const emit = defineEmits(['add', 'remove', 'update', 'save', 'finalize', 'update:pending-files']);

const { show: toast } = useToast();

const uploading   = ref(false);
const uploadError = ref(null);
const pendingFiles = ref([]);

const hasAttachments   = computed(() => (localAnexos.value.anexos?.length ?? 0) > 0);
const isAlreadyFinalizado = computed(() => props.ratStatus === 'finalizado');;

watch(pendingFiles, (files) => {
  emit('update:pending-files', [...files]);
}, { deep: true });

const localAnexos = ref({ anexos: props.anexos || [] });

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

function detectTipo(file) {
  if (file.type.startsWith('image/')) return 'imagem';
  if (file.type.startsWith('video/')) return 'video';
  if (file.type.startsWith('audio/')) return 'audio';
  return 'documento';
}

async function doUpload(file, tempId) {
  if (!props.ratId) {
    uploadError.value = 'Salve os Dados Gerais antes de anexar arquivos.';
    localAnexos.value.anexos = localAnexos.value.anexos.filter(a => a.id !== tempId);
    emit('update', localAnexos.value.anexos);
    return;
  }

  uploading.value   = true;
  uploadError.value = null;

  const form = new FormData();
  form.append('arquivo', file);
  form.append('tipo', detectTipo(file));
  form.append('descricao', file.name);

  try {
    const axios    = window.axios || (await import('axios')).default;
    const response = await axios.post(
      route('rat.ocorrencias.attachments.store', props.ratId),
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );

    const serverAnexo = response.data;
    localAnexos.value.anexos = [...localAnexos.value.anexos, serverAnexo];
    emit('add', serverAnexo);
    emit('update', localAnexos.value.anexos);
    toast('Anexo salvo com sucesso!', 'success');
  } catch (err) {
    uploadError.value = err.response?.data?.message ?? 'Erro ao fazer upload. Tente novamente.';
    emit('update', localAnexos.value.anexos);
  } finally {
    uploading.value = false;
  }
}

async function handleUploadFile({ file, tempId }) {
  if (!props.ratId) {
    pendingFiles.value.push({ file, tempId });
    uploadError.value = null;
    return;
  }
  await doUpload(file, tempId);
}

async function handleRemoveFile(anexoId) {
  const isTemp = typeof anexoId === 'number' && !Number.isInteger(anexoId);
  if (!isTemp && props.ratId) {
    try {
      const axios = window.axios || (await import('axios')).default;
      await axios.delete(route('rat.ocorrencias.attachments.destroy', { ocorrencia: props.ratId, id: anexoId }));
    } catch { /* silencioso */ }
  }

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

function handleFinalizeClick() {
  if (!hasAttachments.value) {
    toast('Adicione pelo menos um documento antes de finalizar o RAT.', 'error');
    return;
  }
  emit('finalize');
}
</script>
