<template>
  <PaeCard title="4. Anexos">
    <div class="space-y-6">
      <div
        v-if="!canUpload"
        class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-200"
      >
        Salve as Informacoes Gerais ou abra um protocolo antes de adicionar anexos.
      </div>

      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div
          class="rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-5"
          :class="canUpload ? 'hover:border-blue-500/50' : 'opacity-60'"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-blue-500/15 text-blue-400">
              <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0-12l-4 4m4-4l4 4" />
              </svg>
            </div>

            <div class="min-w-0 flex-1">
              <label class="block text-sm font-semibold text-slate-900 dark:text-white">
                Arquivo
              </label>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                PDF, JPG, JPEG ou PNG ate 50 MB.
              </p>
              <input
                ref="fileInput"
                type="file"
                :disabled="!canUpload || saving"
                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                class="mt-3 block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-500 disabled:cursor-not-allowed"
                @change="onFileChange"
              />
              <p v-if="selectedFile" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Selecionado: {{ selectedFile.name }} ({{ formatSize(selectedFile.size) }})
              </p>
              <p v-if="visibleError" class="mt-2 text-xs font-medium text-red-500 dark:text-red-400">
                {{ visibleError }}
              </p>

              <div v-if="showProgress" class="mt-3 space-y-1.5">
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                  <div
                    class="h-full rounded-full bg-blue-500 transition-all duration-300"
                    :style="{ width: `${progressWidth}%` }"
                  />
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ statusMessage || 'Processando anexo...' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            Descricao
          </label>
          <textarea
            v-model="descricao"
            :disabled="!canUpload || saving"
            rows="2"
            placeholder="Descricao opcional do anexo"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
          />
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="!canUpload || !selectedFile || !!fileError || saving"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 font-semibold text-white transition-colors hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <span v-if="saving" class="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
            Adicionar Anexo
          </button>
        </div>
      </form>

      <div
        v-if="visibleBackendError"
        class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-600 dark:text-red-300"
      >
        {{ errorMessage }}
      </div>

      <div class="border-t border-slate-200 pt-5 dark:border-slate-700">
        <div v-if="!items.length" class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-900/40">
          <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Nenhum anexo cadastrado.</p>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">Os arquivos enviados aparecerao aqui.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="anexo in items"
            :key="anexo.id"
            class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                {{ anexo.nome_original }}
              </p>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ anexo.tamanho_formatado }} <span v-if="anexo.descricao">- {{ anexo.descricao }}</span>
              </p>
              <p v-if="anexo.local_armazenamento" class="mt-1 text-xs text-slate-500 dark:text-slate-500">
                Local: {{ anexo.local_armazenamento }}
              </p>
            </div>

            <div class="flex flex-shrink-0 gap-2">
              <button
                type="button"
                class="rounded-lg border border-blue-500/40 px-3 py-2 text-xs font-semibold text-blue-600 transition-colors hover:bg-blue-500/10 dark:text-blue-300"
                @click="$emit('view', anexo.id)"
              >
                Visualizar
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="$emit('download', anexo.id)"
              >
                Baixar
              </button>
              <button
                type="button"
                :disabled="saving"
                class="rounded-lg border border-red-500/40 px-3 py-2 text-xs font-semibold text-red-500 transition-colors hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                @click="$emit('remove', anexo.id)"
              >
                {{ saving ? 'Aguarde...' : 'Remover' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PaeCard>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  formularioId: {
    type: [Number, String],
    default: null,
  },
  protocoloId: {
    type: [Number, String],
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  progress: {
    type: Number,
    default: 0,
  },
  statusMessage: {
    type: String,
    default: '',
  },
  errorMessage: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['upload', 'remove', 'download', 'view']);

const descricao = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);
const fileError = ref('');
const hideBackendError = ref(false);

const maxFileBytes = 50 * 1024 * 1024;

const canUpload = computed(() => !!props.formularioId || !!props.protocoloId);
const visibleBackendError = computed(() => props.errorMessage && !fileError.value && !hideBackendError.value);
const visibleError = computed(() => fileError.value || (hideBackendError.value ? '' : props.errorMessage));
const showProgress = computed(() => props.saving || props.progress > 0);
const progressWidth = computed(() => {
  if (props.progress > 0) {
    return Math.max(8, Math.min(100, props.progress));
  }

  return props.saving ? 12 : 0;
});

watch(() => props.errorMessage, () => {
  hideBackendError.value = false;
});

function onFileChange(event) {
  fileError.value = '';
  hideBackendError.value = true;
  const file = event.target.files[0] ?? null;

  if (!file) {
    selectedFile.value = null;
    return;
  }

  if (file.size > maxFileBytes) {
    selectedFile.value = null;
    fileError.value = 'Arquivo acima de 50 MB. Selecione um PDF, JPG, JPEG ou PNG menor.';
    if (fileInput.value) {
      fileInput.value.value = '';
    }
    return;
  }

  selectedFile.value = file;
}

function handleSubmit() {
  if (!selectedFile.value) return;

  if (selectedFile.value.size > maxFileBytes) {
    fileError.value = 'Arquivo acima de 50 MB. Selecione um PDF, JPG, JPEG ou PNG menor.';
    return;
  }

  emit('upload', {
    arquivo: selectedFile.value,
    descricao: descricao.value,
    onSuccess: resetForm,
    onError: (message) => {
      fileError.value = message;
    },
  });
}

function resetForm() {
  descricao.value = '';
  selectedFile.value = null;
  fileError.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function formatSize(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}
</script>
