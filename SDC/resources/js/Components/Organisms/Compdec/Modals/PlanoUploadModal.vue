<template>
  <Modal :show="show" max-width="2xl" @close="handleClose">
    <div class="bg-slate-900 border border-slate-700">
      <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-violet-700/10 to-purple-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-violet-500/15 text-violet-300">
              <ClipboardDocumentListIcon class="w-5 h-5" />
            </div>
            <Heading :level="3" color="white">
              {{ isEditing ? 'Editar Plano de Contingencia' : 'Novo Plano de Contingencia' }}
            </Heading>
          </div>
          <button class="text-slate-400 hover:text-slate-200 transition-colors" @click="handleClose">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <form class="px-6 py-5 max-h-[70vh] overflow-y-auto" @submit.prevent="handleSubmit">
        <div class="space-y-5">
          <FormField
            v-model="form.versao"
            label="Versao"
            required
            :error="errors.versao"
            :maxlength="40"
            placeholder="Ex: v1.0, 2024.1"
          />

          <FormTextarea
            v-model="form.observacoes"
            label="Observacoes"
            :error="errors.observacoes"
            :rows="3"
          />

          <ToggleField
            v-if="!isEditing"
            v-model="form.ativo"
            label="Ativar imediatamente"
            hint="Marca como ativo apos upload (desativa o anterior do orgao)"
          />

          <div>
            <Label :required="!isEditing">Arquivo (PDF / DOC / DOCX / ODT - max 20 MB)</Label>
            <input
              ref="fileInputRef"
              type="file"
              :required="!isEditing"
              accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
              class="block w-full text-sm text-slate-300
                file:mr-4 file:py-2 file:px-4 file:rounded-lg
                file:border-0 file:text-sm file:font-medium
                file:bg-violet-500/15 file:text-violet-300
                hover:file:bg-violet-500/25 transition"
              @change="onFileChange"
            />
            <p v-if="errors.arquivo" class="mt-1 text-xs text-red-400">{{ errors.arquivo }}</p>
            <p v-else-if="selectedFile" class="mt-1 text-xs text-slate-400">
              Selecionado: {{ selectedFile.name }} ({{ formatSize(selectedFile.size) }})
            </p>
            <p v-else-if="isEditing" class="mt-1 text-xs text-slate-500">
              Deixe em branco para manter o arquivo atual.
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-700">
          <Button variant="ghost" type="button" @click="handleClose">Cancelar</Button>
          <Button variant="primary" type="submit" :loading="loading">
            {{ isEditing ? 'Salvar' : 'Adicionar' }}
          </Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { XMarkIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  orgaoId: { type: [Number, String], required: true },
  plano: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});
const selectedFile = ref(null);
const fileInputRef = ref(null);

const form = reactive({
  versao: '',
  observacoes: '',
  ativo: false,
});

const isEditing = computed(() => !!props.plano?.id);

watch(() => props.show, (visible) => {
  if (!visible) return;
  errors.value = {};
  selectedFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';

  if (props.plano) {
    Object.assign(form, {
      versao: props.plano.versao ?? '',
      observacoes: props.plano.observacoes ?? '',
      ativo: !!props.plano.ativo,
    });
  } else {
    Object.assign(form, { versao: '', observacoes: '', ativo: false });
  }
});

function onFileChange(event) {
  selectedFile.value = event.target.files[0] ?? null;
}

function handleClose() {
  if (loading.value) return;
  emit('close');
}

function handleSubmit() {
  loading.value = true;
  errors.value = {};

  const payload = { ...form };
  if (selectedFile.value) payload.arquivo = selectedFile.value;
  if (isEditing.value) payload._method = 'put';

  const onSuccess = () => emit('saved');
  const onError = (e) => { errors.value = e; };
  const onFinish = () => { loading.value = false; };

  if (isEditing.value) {
    router.post(
      route('compdec.planos.update', { orgao: props.orgaoId, plano: props.plano.id }),
      payload,
      { preserveScroll: true, forceFormData: true, onSuccess, onError, onFinish },
    );
  } else {
    router.post(
      route('compdec.planos.store', props.orgaoId),
      payload,
      { preserveScroll: true, forceFormData: true, onSuccess, onError, onFinish },
    );
  }
}

function formatSize(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}
</script>
