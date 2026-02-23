<template>

    <Head title="Nova Demanda" />

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-3xl font-bold text-slate-900 dark:text-white">
                Nova Demanda
              </h2>
              <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Descreva seu problema ou solicitacao para a equipe de TI
              </p>
            </div>

            <Link
              :href="route('demandas.index')"
              class="inline-flex items-center px-4 py-2 bg-slate-200 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-300 dark:hover:bg-slate-600 transition"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              Voltar
            </Link>
          </div>
        </div>

        <!-- Formulario -->
        <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg">
          <form @submit.prevent="submit" class="p-6 space-y-6">
            <!-- Tipo de Demanda -->
            <FormSelect
              v-model="form.tipo"
              label="Tipo de Demanda"
              :options="tiposOptions"
              placeholder="Selecione o tipo"
              required
              :error="form.errors.tipo"
              hint="Incidente: Algo quebrado. Solicitacao: Pedir algo novo."
            />

            <!-- Titulo -->
            <FormField
              v-model="form.titulo"
              label="Titulo"
              type="text"
              placeholder="Resumo breve do problema ou solicitacao"
              required
              :error="form.errors.titulo"
            />

            <!-- Descricao -->
            <FormTextarea
              v-model="form.descricao"
              label="Descricao"
              :rows="5"
              placeholder="Descreva detalhadamente o problema ou solicitacao."
              :error="form.errors.descricao"
            />

            <!-- Categoria e Subcategoria -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormSelect
                v-model="form.categoria"
                label="Categoria"
                :options="categoriasOptions"
                placeholder="Selecione uma categoria"
                :error="form.errors.categoria"
                @update:model-value="form.subcategoria = ''"
              />

              <FormSelect
                v-model="form.subcategoria"
                label="Subcategoria"
                :options="subcategoriasOptions"
                placeholder="Selecione uma subcategoria"
                :disabled="!form.categoria"
                :error="form.errors.subcategoria"
              />
            </div>

            <!-- Urgencia e Impacto -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormSelect
                v-model="form.urgencia"
                label="Urgencia"
                :options="urgenciaOptions"
                :error="form.errors.urgencia"
                hint="Quando voce precisa que seja resolvido?"
              />

              <FormSelect
                v-model="form.impacto"
                label="Impacto"
                :options="impactoOptions"
                :error="form.errors.impacto"
                hint="Quantas pessoas sao afetadas?"
              />
            </div>

            <!-- Info sobre Prioridade -->
            <div v-if="form.urgencia && form.impacto" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Prioridade sera calculada automaticamente
                  </h3>
                  <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Com base na urgencia e impacto informados, a equipe de TI definira a prioridade da sua demanda.
                  </p>
                </div>
              </div>
            </div>

            <!-- Botoes -->
            <div class="flex items-center justify-end space-x-4 pt-4">
              <Link
                :href="route('demandas.index')"
                class="inline-flex items-center px-4 py-2 bg-slate-200 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-300 dark:hover:bg-slate-600 transition"
              >
                Cancelar
              </Link>

              <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                :disabled="form.processing"
              >
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ form.processing ? 'Salvando...' : 'Abrir Demanda' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

</template>

<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  categorias: {
    type: Object,
    required: true
  },
  tipos: {
    type: Object,
    required: true
  }
});

const form = useForm({
  tipo: '',
  titulo: '',
  descricao: '',
  categoria: '',
  subcategoria: '',
  urgencia: '',
  impacto: ''
});

const tiposOptions = computed(() => {
  return Object.entries(props.tipos).map(([value, label]) => ({ value, label }));
});

const categoriasOptions = computed(() => {
  return Object.keys(props.categorias).map(cat => ({ value: cat, label: cat }));
});

const subcategoriasOptions = computed(() => {
  if (!form.categoria || !props.categorias[form.categoria]) return [];
  return props.categorias[form.categoria].map(sub => ({ value: sub, label: sub }));
});

const urgenciaOptions = [
  { value: '', label: 'Deixar TI avaliar' },
  { value: 'alta', label: 'Alta - Preciso resolver agora' },
  { value: 'media', label: 'Media - Posso esperar algumas horas' },
  { value: 'baixa', label: 'Baixa - Pode ser resolvido quando possivel' },
];

const impactoOptions = [
  { value: '', label: 'Deixar TI avaliar' },
  { value: 'alto', label: 'Alto - Afeta toda equipe/departamento' },
  { value: 'medio', label: 'Medio - Afeta algumas pessoas' },
  { value: 'baixo', label: 'Baixo - Afeta apenas a mim' },
];

const submit = () => {
  form.post(route('demandas.store'), {
    preserveScroll: true,
    onSuccess: () => {
    }
  });
};
</script>
