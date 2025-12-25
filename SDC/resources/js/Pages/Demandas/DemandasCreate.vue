<template>
  <AuthenticatedLayout>
    <Head title="Nova Demanda" />

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                Nova Demanda
              </h2>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Descreva seu problema ou solicitação para a equipe de TI
              </p>
            </div>

            <Link
              :href="route('demandas.index')"
              class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 transition"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              Voltar
            </Link>
          </div>
        </div>

        <!-- Formulário -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <form @submit.prevent="submit" class="p-6 space-y-6">
            <!-- Tipo de Demanda -->
            <div>
              <label for="tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Tipo de Demanda <span class="text-red-500">*</span>
              </label>
              <select
                id="tipo"
                v-model="form.tipo"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
              >
                <option value="">Selecione o tipo</option>
                <option v-for="(label, value) in tipos" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <strong>Incidente:</strong> Algo que está quebrado ou não funciona.
                <strong>Solicitação:</strong> Pedir algo novo ou mudança.
              </p>
              <div v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">
                {{ form.errors.tipo }}
              </div>
            </div>

            <!-- Título -->
            <div>
              <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Título <span class="text-red-500">*</span>
              </label>
              <input
                id="titulo"
                v-model="form.titulo"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Resumo breve do problema ou solicitação"
                maxlength="255"
                required
              />
              <div v-if="form.errors.titulo" class="mt-1 text-sm text-red-600">
                {{ form.errors.titulo }}
              </div>
            </div>

            <!-- Descrição -->
            <div>
              <label for="descricao" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Descrição
              </label>
              <textarea
                id="descricao"
                v-model="form.descricao"
                rows="5"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Descreva detalhadamente o problema ou solicitação. Inclua informações relevantes como: O que aconteceu? Quando começou? Onde está acontecendo?"
              ></textarea>
              <div v-if="form.errors.descricao" class="mt-1 text-sm text-red-600">
                {{ form.errors.descricao }}
              </div>
            </div>

            <!-- Categoria e Subcategoria -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="categoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Categoria
                </label>
                <select
                  id="categoria"
                  v-model="form.categoria"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  @change="form.subcategoria = ''"
                >
                  <option value="">Selecione uma categoria</option>
                  <option v-for="(subcats, cat) in categorias" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
                <div v-if="form.errors.categoria" class="mt-1 text-sm text-red-600">
                  {{ form.errors.categoria }}
                </div>
              </div>

              <div>
                <label for="subcategoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Subcategoria
                </label>
                <select
                  id="subcategoria"
                  v-model="form.subcategoria"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  :disabled="!form.categoria"
                >
                  <option value="">Selecione uma subcategoria</option>
                  <option
                    v-for="subcat in (form.categoria ? categorias[form.categoria] : [])"
                    :key="subcat"
                    :value="subcat"
                  >
                    {{ subcat }}
                  </option>
                </select>
                <div v-if="form.errors.subcategoria" class="mt-1 text-sm text-red-600">
                  {{ form.errors.subcategoria }}
                </div>
              </div>
            </div>

            <!-- Urgência e Impacto -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="urgencia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Urgência
                </label>
                <select
                  id="urgencia"
                  v-model="form.urgencia"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">Deixar TI avaliar</option>
                  <option value="alta">Alta - Preciso resolver agora</option>
                  <option value="media">Média - Posso esperar algumas horas</option>
                  <option value="baixa">Baixa - Pode ser resolvido quando possível</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Quando você precisa que seja resolvido?
                </p>
                <div v-if="form.errors.urgencia" class="mt-1 text-sm text-red-600">
                  {{ form.errors.urgencia }}
                </div>
              </div>

              <div>
                <label for="impacto" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Impacto
                </label>
                <select
                  id="impacto"
                  v-model="form.impacto"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">Deixar TI avaliar</option>
                  <option value="alto">Alto - Afeta toda equipe/departamento</option>
                  <option value="medio">Médio - Afeta algumas pessoas</option>
                  <option value="baixo">Baixo - Afeta apenas a mim</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Quantas pessoas são afetadas?
                </p>
                <div v-if="form.errors.impacto" class="mt-1 text-sm text-red-600">
                  {{ form.errors.impacto }}
                </div>
              </div>
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
                    Prioridade será calculada automaticamente
                  </h3>
                  <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Com base na urgência e impacto informados, a equipe de TI definirá a prioridade da sua demanda.
                  </p>
                </div>
              </div>
            </div>

            <!-- Botões -->
            <div class="flex items-center justify-end space-x-4 pt-4">
              <Link
                :href="route('demandas.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 transition"
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
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
  form.post(route('demandas.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect handled by controller
    }
  });
};
</script>
