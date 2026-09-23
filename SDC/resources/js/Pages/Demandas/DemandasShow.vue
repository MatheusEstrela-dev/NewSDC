<template>
  <div class="pb-6">
    <Head :title="`Demanda ${demanda.protocolo}`" />
    
    <PageHeader 
      :title="`Demanda ${demanda.protocolo}`" 
      :description="demanda.titulo" 
      :icon-image="moduleIcon('demandas')" 
    >
      <template #actions>
        <Button variant="default" @click="goBack" class="mr-2">Voltar</Button>
        <Button v-if="canEdit" variant="primary" :icon="PencilIcon">Editar Demanda</Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <!-- Main Details -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
          <div class="p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4">
              <DemandaStatusBadge :status="demanda.status">{{ demanda.status_label || demanda.status }}</DemandaStatusBadge>
              <DemandaPrioridadeBadge :prioridade="demanda.prioridade">{{ demanda.prioridade_label || 'Prioridade ' + demanda.prioridade }}</DemandaPrioridadeBadge>
            </div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 leading-snug">{{ demanda.titulo }}</h2>
            <div class="prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 whitespace-pre-wrap text-sm leading-relaxed">
              {{ demanda.descricao }}
            </div>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800/50 p-4 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <div class="text-slate-500 mb-1">Solicitante</div>
              <div class="font-medium truncate" :title="demanda.solicitante?.name">{{ demanda.solicitante?.name || 'Sistema' }}</div>
            </div>
            <div>
              <div class="text-slate-500 mb-1">Responsável (TI)</div>
              <div class="font-medium truncate" :title="demanda.atribuido_para?.name">{{ demanda.atribuido_para?.name || 'Não atribuído' }}</div>
            </div>
            <div>
              <div class="text-slate-500 mb-1">Data Abertura</div>
              <div class="font-medium">{{ formatDate(demanda.created_at) }}</div>
            </div>
            <div>
              <div class="text-slate-500 mb-1">Tipo</div>
              <div class="font-medium uppercase">{{ demanda.tipo }}</div>
            </div>
          </div>
        </div>

        <!-- Comments and Timeline -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
          <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            Acompanhamento
          </h3>
          <div class="space-y-4 mb-6 max-h-[500px] overflow-y-auto pr-2">
            <div v-for="comentario in demanda.comments" :key="comentario.id" class="flex gap-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold text-xs">
                  {{ comentario.user?.name?.charAt(0) || 'S' }}
                </div>
              </div>
              <div class="flex-1 bg-slate-50 dark:bg-slate-800/70 p-3 rounded-lg border border-slate-100 dark:border-slate-700/50">
                <div class="flex justify-between items-start mb-1.5">
                  <div class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ comentario.user?.name || 'Sistema' }}</div>
                  <div class="text-xs text-slate-500">{{ formatDateTime(comentario.created_at) }}</div>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{{ comentario.conteudo }}</div>
              </div>
            </div>
            <div v-if="!demanda.comments?.length" class="text-center text-slate-500 py-6 text-sm bg-slate-50 dark:bg-slate-800/30 rounded-lg border border-dashed border-slate-200 dark:border-slate-700">
              Nenhuma interação registrada nesta demanda até o momento.
            </div>
          </div>

          <!-- Add Comment Form -->
          <form @submit.prevent="submitComment" class="mt-4 border-t border-slate-100 dark:border-slate-800 pt-4">
            <textarea v-model="commentForm.conteudo" rows="3" class="form-textarea w-full rounded-md text-sm mb-3 border-slate-300 dark:border-slate-700 dark:bg-slate-800" placeholder="Escreva uma resposta ou atualização..." required></textarea>
            <div class="flex justify-between items-center">
              <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 cursor-pointer">
                <input type="checkbox" v-model="commentForm.interno" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                <span>Nota Interna (Visível apenas para equipe TI)</span>
              </label>
              <Button type="submit" variant="primary" :disabled="commentForm.processing">Enviar Resposta</Button>
            </div>
          </form>
        </div>
      </div>

      <div class="space-y-6">
        <!-- SLA Panel -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-md font-bold text-slate-800 dark:text-slate-200">Painel de SLA</h3>
          </div>
          <div class="p-5 space-y-4 text-sm">
            <div>
              <div class="flex justify-between mb-1">
                <span class="text-slate-500">Primeira Resposta</span>
                <span class="font-medium" :class="demanda.sla_primeira_resposta_violado ? 'text-red-500' : 'text-slate-900 dark:text-slate-100'">
                  {{ demanda.primeira_resposta_em ? formatDateTime(demanda.primeira_resposta_em) : 'Pendente' }}
                </span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2">
                <div class="bg-green-500 h-1.5 rounded-full" :style="{ width: demanda.primeira_resposta_em ? '100%' : '20%' }"></div>
              </div>
            </div>
            
            <div class="pt-2">
              <div class="flex justify-between mb-1">
                <span class="text-slate-500">Resolução Final</span>
                <span class="font-medium" :class="demanda.sla_resolucao_violado ? 'text-red-500' : 'text-slate-900 dark:text-slate-100'">
                  {{ demanda.resolvido_em ? formatDateTime(demanda.resolvido_em) : 'No Prazo' }}
                </span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2">
                <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: demanda.resolvido_em ? '100%' : '50%' }"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Attachments -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-md font-bold text-slate-800 dark:text-slate-200">Anexos</h3>
            <button class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Adicionar</button>
          </div>
          <div class="p-5">
            <ul v-if="demanda.attachments?.length" class="space-y-3 text-sm">
              <li v-for="anexo in demanda.attachments" :key="anexo.id" class="flex items-start gap-3 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md transition group">
                <div class="mt-0.5 opacity-60">📎</div>
                <div class="flex-1 overflow-hidden">
                  <a href="#" class="block text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium truncate">{{ anexo.nome_original }}</a>
                  <div class="text-xs text-slate-400 mt-0.5">{{ anexo.tamanho_formatado || '1.2 MB' }} • {{ formatDateTime(anexo.created_at) }}</div>
                </div>
              </li>
            </ul>
            <div v-else class="text-sm text-slate-500 text-center py-4 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
              Nenhum anexo disponível
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineOptions({ layout: AuthenticatedLayout });

import { Head, router, useForm } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import { moduleIcon } from '@/Support/moduleIcons';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import { PencilIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  demanda: { type: Object, required: true },
});

const { can } = usePermissions();
const canEdit = can('demandas.chamados.edit');

const commentForm = useForm({
  conteudo: '',
  interno: false
});

const goBack = () => {
  window.history.back();
};

const submitComment = () => {
  commentForm.post(route('demandas.comments.store', props.demanda.id), {
    preserveScroll: true,
    onSuccess: () => commentForm.reset('conteudo'),
  });
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString('pt-BR') : '--';
const formatDateTime = (date) => {
  if (!date) return '--';
  const d = new Date(date);
  return `${d.toLocaleDateString('pt-BR')} às ${d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute:'2-digit' })}`;
};
</script>
