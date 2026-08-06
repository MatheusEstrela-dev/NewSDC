<script setup>
import { router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  filterOptions: {
    type: Object,
    default: () => ({ tipos: [], categorias: [] }),
  },
});

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

function salvar() {
  form.post(route('treinamentos.admin.store'), {
    onError: () => toast('Verifique os campos do formulario.', 'error'),
  });
}

function cancelar() {
  router.visit(route('treinamentos.index'));
}
</script>

<template>
  <div class="treinamento-create-container">
    <PageHeader
      title="Novo Curso"
      description="Cadastro de treinamentos e cursos da Diretoria de Educação"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <CardBase class="p-6">
      <div v-if="form.processing" class="mb-4 h-1 w-full overflow-hidden bg-slate-200 dark:bg-slate-700">
        <div class="h-full w-1/2 animate-pulse rounded-r-full bg-blue-600" />
      </div>

      <div class="space-y-4">
        <div>
          <label class="field-label">Título <span class="req">*</span></label>
          <TextInput v-model="form.titulo" :maxlength="255" placeholder="Ex: Curso de Primeiros Socorros" />
          <p v-if="form.errors.titulo" class="field-error">{{ form.errors.titulo }}</p>
        </div>

        <div>
          <label class="field-label">Descrição</label>
          <textarea
            v-model="form.descricao"
            rows="4"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          ></textarea>
          <p v-if="form.errors.descricao" class="field-error">{{ form.errors.descricao }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="field-label">Instrutor</label>
            <TextInput v-model="form.instrutor" :maxlength="255" />
          </div>
          <div>
            <label class="field-label">Local / Link Online</label>
            <TextInput v-model="form.local" :maxlength="255" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="field-label">Horário de Início</label>
            <TextInput v-model="form.hora_inicio" type="time" />
            <p v-if="form.errors.hora_inicio" class="field-error">{{ form.errors.hora_inicio }}</p>
          </div>
          <div>
            <label class="field-label">Frequência Mínima (%)</label>
            <TextInput v-model="form.percentual_frequencia_minimo" type="number" />
          </div>
        </div>
      </div>

      <footer class="mt-6 flex items-center justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button variant="secondary" size="sm" type="button" @click="cancelar">Cancelar</Button>
        <Button variant="success" size="sm" :loading="form.processing" :disabled="!form.titulo.trim() || form.processing" @click="salvar">
          Cadastrar
        </Button>
      </footer>
    </CardBase>
  </div>
</template>

<style scoped>
.treinamento-create-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
.field-label { @apply mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300; }
.field-error { @apply mt-1 text-xs text-red-500; }
.req { @apply text-red-500; }
</style>
