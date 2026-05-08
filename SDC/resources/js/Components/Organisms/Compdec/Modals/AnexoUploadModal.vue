<template>
  <Modal :show="show" max-width="2xl" @close="handleClose">
    <div class="bg-slate-900 border border-slate-700">
      <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-amber-700/10 to-orange-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-amber-500/15 text-amber-300">
              <DocumentTextIcon class="w-5 h-5" />
            </div>
            <Heading :level="3" color="white">
              {{ isEditing ? 'Editar Anexo Legal' : 'Adicionar Anexo Legal' }}
            </Heading>
          </div>
          <button class="text-slate-400 hover:text-slate-200 transition-colors" @click="handleClose">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <form class="px-6 py-5 max-h-[70vh] overflow-y-auto" @submit.prevent="handleSubmit">
        <div class="space-y-5">
          <!-- Tipo + Titulo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label required>Tipo</Label>
              <select
                v-model="form.tipo"
                required
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all"
              >
                <option value="" disabled>Selecione...</option>
                <option value="lei">Lei</option>
                <option value="decreto">Decreto</option>
                <option value="portaria">Portaria</option>
                <option value="regimento">Regimento Interno</option>
                <option value="outros">Outros</option>
              </select>
              <p v-if="errors.tipo" class="mt-1 text-xs text-red-400">{{ errors.tipo }}</p>
            </div>

            <FormField
              v-model="form.titulo"
              label="Titulo"
              required
              :error="errors.titulo"
              :maxlength="255"
            />
          </div>

          <!-- Numero + datas -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <FormField
              v-model="form.numero"
              label="Numero"
              :error="errors.numero"
              :maxlength="60"
              placeholder="Ex: 12345/2024"
            />

            <FormField
              v-model="form.data_emissao"
              type="date"
              label="Data Emissao"
              :error="errors.data_emissao"
            />

            <FormField
              v-model="form.data_validade"
              type="date"
              label="Data Validade"
              :error="errors.data_validade"
              hint="Vazio = sem validade"
            />
          </div>

          <!-- Descricao -->
          <FormTextarea
            v-model="form.descricao"
            label="Descricao"
            :error="errors.descricao"
            :rows="3"
          />

          <!-- Upload arquivo -->
          <div>
            <Label>Arquivo (PDF / DOC / DOCX / ODT - max 2 MB)</Label>
            <input
              ref="fileInputRef"
              type="file"
              accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
              class="block w-full text-sm text-slate-300
                file:mr-4 file:py-2 file:px-4 file:rounded-lg
                file:border-0 file:text-sm file:font-medium
                file:bg-amber-500/15 file:text-amber-300
                hover:file:bg-amber-500/25 transition"
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
import { XMarkIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  orgaoId: { type: [Number, String], required: true },
  anexo: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});
const selectedFile = ref(null);
const fileInputRef = ref(null);

const form = reactive({
  tipo: '',
  titulo: '',
  descricao: '',
  numero: '',
  data_emissao: '',
  data_validade: '',
});

const isEditing = computed(() => !!props.anexo?.id);

watch(() => props.show, (visible) => {
  if (!visible) return;
  errors.value = {};
  selectedFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';

  if (props.anexo) {
    Object.assign(form, {
      tipo: props.anexo.tipo ?? '',
      titulo: props.anexo.titulo ?? '',
      descricao: props.anexo.descricao ?? '',
      numero: props.anexo.numero ?? '',
      data_emissao: props.anexo.data_emissao ?? '',
      data_validade: props.anexo.data_validade ?? '',
    });
  } else {
    Object.assign(form, {
      tipo: '', titulo: '', descricao: '', numero: '',
      data_emissao: '', data_validade: '',
    });
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
  if (selectedFile.value) {
    payload.arquivo = selectedFile.value;
  }
  if (isEditing.value) {
    // POST com _method=PUT para suportar upload em update
    payload._method = 'put';
  }

  const onSuccess = () => emit('saved');
  const onError = (e) => { errors.value = e; };
  const onFinish = () => { loading.value = false; };

  if (isEditing.value) {
    router.post(
      route('compdec.anexos.update', { orgao: props.orgaoId, anexo: props.anexo.id }),
      payload,
      { preserveScroll: true, forceFormData: true, onSuccess, onError, onFinish },
    );
  } else {
    router.post(
      route('compdec.anexos.store', props.orgaoId),
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
