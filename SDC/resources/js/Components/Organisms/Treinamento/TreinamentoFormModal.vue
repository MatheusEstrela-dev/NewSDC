<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamento: { type: Object, default: null },
  filterOptions: { type: Object, default: () => ({ tipos: [], categorias: [] }) },
});

const emit = defineEmits(['close', 'saved']);
const { show: toast } = useToast();

const form = useForm({
  titulo: '',
  descricao: '',
  carga_horaria: '',
  categoria: 'CURSO',
  tipo: 'PRESENCIAL',
  instrutor: '',
  local: '',
  data_inicio: '',
  data_fim: '',
  hora_inicio: '',
  percentual_frequencia_minimo: 75,
});

const editando = () => Boolean(props.treinamento?.id);

function preencherForm() {
  form.clearErrors();
  if (props.treinamento) {
    form.titulo = props.treinamento.titulo ?? '';
    form.descricao = props.treinamento.descricao ?? '';
    form.carga_horaria = props.treinamento.carga_horaria ?? '';
    form.categoria = props.treinamento.categoria ?? 'CURSO';
    form.tipo = props.treinamento.tipo ?? 'PRESENCIAL';
    form.instrutor = props.treinamento.instrutor ?? '';
    form.local = props.treinamento.local ?? '';
    form.data_inicio = props.treinamento.data_inicio ?? '';
    form.data_fim = props.treinamento.data_fim ?? '';
    form.hora_inicio = props.treinamento.hora_inicio ?? '';
    form.percentual_frequencia_minimo = props.treinamento.percentual_frequencia_minimo ?? 75;
  } else {
    form.reset();
  }
}

watch(() => props.show, (v) => { if (v) preencherForm(); });

function salvar() {
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      toast(editando() ? 'Treinamento atualizado.' : 'Treinamento cadastrado.', 'success');
      emit('saved');
      emit('close');
    },
    onError: () => toast('Verifique os campos do formulario.', 'error'),
  };

  if (editando()) {
    form.put(route('treinamentos.update', props.treinamento.id), opts);
  } else {
    form.post(route('treinamentos.admin.store'), opts);
  }
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="$emit('close')">
    <div class="flex max-h-[90vh] flex-col overflow-hidden">
      <div v-if="form.processing" class="h-1 w-full overflow-hidden bg-slate-200 dark:bg-slate-700"><div class="h-full w-1/2 animate-pulse rounded-r-full bg-blue-600" /></div>

      <div class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200 px-6 py-4 dark:border-slate-700/50 dark:from-slate-800 dark:to-slate-900">
        <div>
          <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
            {{ editando() ? 'Editar Treinamento' : 'Novo Treinamento' }}
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Cursos e eventos da Diretoria de Educação.</p>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700/50 dark:hover:text-white" @click="$emit('close')">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50 p-6 scrollbar-hide dark:bg-slate-900/50">
        <div>
          <label class="field-label">Título <span class="req">*</span></label>
          <TextInput v-model="form.titulo" :maxlength="255" placeholder="Ex: Curso de Primeiros Socorros" />
          <p v-if="form.errors.titulo" class="field-error">{{ form.errors.titulo }}</p>
        </div>

        <div>
          <label class="field-label">Descrição</label>
          <textarea
            v-model="form.descricao"
            rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          ></textarea>
          <p v-if="form.errors.descricao" class="field-error">{{ form.errors.descricao }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label class="field-label">Categoria <span class="req">*</span></label>
            <SelectInput
              v-model="form.categoria"
              :options="(filterOptions.categorias || []).map(c => ({ value: c.value, label: c.label }))"
              placeholder=""
            />
          </div>
          <div>
            <label class="field-label">Tipo <span class="req">*</span></label>
            <SelectInput
              v-model="form.tipo"
              :options="(filterOptions.tipos || []).map(t => ({ value: t.value, label: t.label }))"
              placeholder=""
            />
          </div>
          <div>
            <label class="field-label">Carga Horária (h) <span class="req">*</span></label>
            <TextInput v-model="form.carga_horaria" type="number" />
            <p v-if="form.errors.carga_horaria" class="field-error">{{ form.errors.carga_horaria }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Instrutor</label>
            <TextInput v-model="form.instrutor" :maxlength="255" />
          </div>
          <div>
            <label class="field-label">Local / Link Online</label>
            <TextInput v-model="form.local" :maxlength="255" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Data de Início</label>
            <TextInput v-model="form.data_inicio" type="date" />
          </div>
          <div>
            <label class="field-label">Data de Término</label>
            <TextInput v-model="form.data_fim" type="date" />
            <p v-if="form.errors.data_fim" class="field-error">{{ form.errors.data_fim }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Horário de Início</label>
            <TextInput v-model="form.hora_inicio" type="time" />
            <p v-if="form.errors.hora_inicio" class="field-error">{{ form.errors.hora_inicio }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Frequência Mínima (%)</label>
            <TextInput v-model="form.percentual_frequencia_minimo" type="number" />
          </div>
        </div>
      </div>

      <footer class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700/50 dark:bg-slate-800">
        <Button variant="secondary" size="sm" type="button" @click="$emit('close')">Cancelar</Button>
        <Button variant="success" size="sm" :loading="form.processing" :disabled="!form.titulo.trim() || form.processing" @click="salvar">
          {{ editando() ? 'Atualizar' : 'Cadastrar' }}
        </Button>
      </footer>
    </div>
  </Modal>
</template>

<style scoped>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
.req { @apply text-red-500; }
</style>
