<template>
  <AuthenticatedLayout>
    <Head :title="`Demanda ${task.protocolo}`" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <div class="flex items-center space-x-3">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                  {{ task.protocolo }}
                </h2>
                <span
                  :class="task.status_color"
                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                >
                  {{ task.status_label }}
                </span>
                <span
                  :class="task.prioridade_color"
                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                >
                  {{ task.prioridade_label }}
                </span>
              </div>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ task.tipo_label }} aberto {{ task.criado_em_diff }}
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Coluna Principal - Detalhes e Timeline -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Detalhes da Demanda -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                  {{ task.titulo }}
                </h3>

                <div class="prose dark:prose-invert max-w-none">
                  <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ task.descricao || 'Sem descrição detalhada.' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Timeline de Comentários -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                  Atividades
                </h3>

                <!-- Lista de Comentários -->
                <div v-if="task.comentarios && task.comentarios.length > 0" class="space-y-4">
                  <div
                    v-for="comentario in task.comentarios"
                    :key="comentario.id"
                    class="flex space-x-3"
                  >
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                          {{ comentario.autor_iniciais }}
                        </span>
                      </div>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-900 dark:text-white">
                          {{ comentario.autor_nome }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                          {{ comentario.criado_em_diff }}
                        </span>
                      </div>
                      <p class="mt-1 text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        {{ comentario.comentario }}
                      </p>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                  Nenhum comentário ainda
                </div>

                <!-- Formulário de Novo Comentário -->
                <div v-if="canComment" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                  <form @submit.prevent="submitComment">
                    <textarea
                      v-model="commentForm.comentario"
                      rows="3"
                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Adicione um comentário..."
                      required
                    ></textarea>

                    <div class="mt-3 flex justify-end">
                      <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-25 transition"
                        :disabled="commentForm.processing"
                      >
                        {{ commentForm.processing ? 'Enviando...' : 'Comentar' }}
                      </button>
                    </div>

                    <div v-if="commentForm.errors.comentario" class="mt-2 text-sm text-red-600">
                      {{ commentForm.errors.comentario }}
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar - Informações Adicionais -->
          <div class="space-y-6">
            <!-- Informações -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                  Informações
                </h3>

                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Solicitante
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.solicitante_nome }}
                    </dd>
                  </div>

                  <div v-if="task.atribuido_para_nome">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Atribuído para
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.atribuido_para_nome }}
                    </dd>
                  </div>

                  <div v-if="task.categoria">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Categoria
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.categoria }}
                      <span v-if="task.subcategoria" class="text-gray-500">
                        / {{ task.subcategoria }}
                      </span>
                    </dd>
                  </div>

                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Urgência
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.urgencia_label }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Impacto
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.impacto_label }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Criado em
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.criado_em_formatado }}
                    </dd>
                  </div>

                  <div v-if="task.atualizado_em_formatado">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Última atualização
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ task.atualizado_em_formatado }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- SLA -->
            <div v-if="task.prazo_resolucao" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                  Prazo (SLA)
                </h3>

                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                      Prazo de Resolução
                    </dt>
                    <dd class="mt-1 text-sm font-medium" :class="task.sla_resolucao_violado ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                      {{ task.prazo_resolucao_formatado }}
                      <span v-if="task.sla_resolucao_violado" class="text-xs">
                        (Violado)
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Anexos -->
            <div v-if="task.anexos && task.anexos.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                  Anexos
                </h3>

                <ul class="space-y-2">
                  <li
                    v-for="anexo in task.anexos"
                    :key="anexo.id"
                    class="flex items-center text-sm"
                  >
                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/>
                    </svg>
                    <a :href="anexo.url" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                      {{ anexo.nome_arquivo }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  canComment: {
    type: Boolean,
    default: false
  }
});

const commentForm = useForm({
  comentario: ''
});

const submitComment = () => {
  commentForm.post(route('demandas.comments.store', props.task.id), {
    preserveScroll: true,
    onSuccess: () => {
      commentForm.reset();
    }
  });
};
</script>
