<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-8">
      <!-- Banner Ano Fiscal -->
      <div class="relative px-6 py-5 rounded-2xl shadow-lg mb-8 overflow-hidden bg-gradient-to-r from-slate-800 to-slate-900 dark:from-slate-800 dark:to-slate-900 from-blue-50 to-indigo-50 border border-blue-100 dark:border-transparent">
        <div class="relative z-10">
          <p class="text-xs uppercase font-bold tracking-widest mb-1 text-blue-200/80 dark:text-blue-200/80 text-blue-600">
            Painel Gerencial
          </p>
          <h2 class="text-3xl font-bold tracking-tight text-white dark:text-white text-slate-900">Exercício {{ currentYear }}</h2>
          <p class="text-sm mt-1 max-w-md text-slate-400 dark:text-slate-400 text-slate-600">
            Visão consolidada dos processos de transferência e apoio aos municípios mineiros.
          </p>
        </div>
      </div>

      <!-- Grid de Métricas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div
          v-for="(metric, key) in metrics"
          :key="key"
          class="rounded-xl p-5 shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50"
        >
          <div class="flex justify-between items-start mb-4">
            <div :class="[metric.color, 'w-10 h-10 rounded-lg flex items-center justify-center text-white']">
              {{ metric.icon }}
            </div>
            <span class="text-xs font-bold px-2 py-1 rounded-full text-slate-400 dark:text-slate-400 text-slate-600 bg-slate-50 dark:bg-slate-700/50 bg-slate-100">
              +2%
            </span>
          </div>
          <div>
            <p class="text-3xl font-bold mt-1 text-slate-800 dark:text-slate-200 text-slate-900">{{ metric.val }}</p>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 text-slate-600">{{ metric.label }}</p>
          </div>
        </div>
      </div>

      <!-- Conteúdo Principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Tabela PMDA -->
        <div class="lg:col-span-2 rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 text-slate-900">PMDA em Análise</h3>
            <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400 text-slate-600">Processos aguardando intervenção técnica</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs uppercase font-bold border-b bg-slate-50 dark:bg-slate-900/50 bg-slate-100 text-slate-400 dark:text-slate-400 text-slate-600 border-slate-100 dark:border-slate-700">
                <tr>
                  <th class="px-6 py-4">Protocolo</th>
                  <th class="px-6 py-4">Município</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4">Data</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50 divide-slate-100">
                <tr v-for="item in pmdaEmAnalise" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 hover:bg-slate-100">
                  <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-200 text-slate-900">{{ item.protocolo }}</td>
                  <td class="px-6 py-4 text-slate-700 dark:text-slate-300 text-slate-700">{{ item.municipio }}</td>
                  <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-500/20 bg-blue-100 text-blue-800 dark:text-blue-400 text-blue-800">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 text-slate-500">{{ item.data }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Timeline -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 text-slate-900">Últimas Movimentações</h3>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div
                v-for="h in historico"
                :key="h.id"
                class="flex gap-4 pb-4 border-b last:border-0 border-slate-100 dark:border-slate-700"
              >
                <div class="w-4 h-4 rounded-full bg-blue-500 mt-1"></div>
                <div class="flex-1">
                  <p class="font-semibold text-sm text-slate-800 dark:text-slate-200 text-slate-900">{{ h.municipio }}</p>
                  <p class="text-sm mt-0.5 text-slate-600 dark:text-slate-400 text-slate-700">{{ h.acao }}</p>
                  <p class="text-xs mt-1 font-mono text-slate-400 dark:text-slate-500 text-slate-500">{{ h.protocolo }}</p>
                  <span class="text-xs text-slate-500 dark:text-slate-400 text-slate-500">{{ h.data }}</span>
                </div>
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
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Dados inline para teste (sem dependências externas)
const currentYear = ref(new Date().getFullYear());

const metrics = ref({
  emEdicao: { val: 24, label: 'Em Edição', color: 'bg-blue-600', icon: '✏️' },
  emAnalise: { val: 5, label: 'Em Análise', color: 'bg-amber-500', icon: '⏰' },
  aprovados: { val: 77, label: 'Aprovados', color: 'bg-emerald-600', icon: '✓' },
  atendidos: { val: 12, label: 'Atendidos', color: 'bg-indigo-600', icon: '✓✓' },
});

const pmdaEmAnalise = ref([
  { id: 1, protocolo: '2025/001', status: 'Análise Técnica', data: '20/01/2025', municipio: 'Belo Horizonte' },
  { id: 2, protocolo: '2025/002', status: 'Parecer', data: '12/02/2025', municipio: 'Contagem' },
  { id: 3, protocolo: '2025/005', status: 'Aguard. Doc.', data: '15/02/2025', municipio: 'Betim' },
  { id: 4, protocolo: '2025/008', status: 'Análise Técnica', data: '18/02/2025', municipio: 'Nova Lima' },
  { id: 5, protocolo: '2025/012', status: 'Triagem', data: '20/02/2025', municipio: 'Sabará' },
]);

const historico = ref([
  { id: 101, protocolo: '2025/001', municipio: 'Belo Horizonte', data: 'Há 2 horas', acao: 'Envio para análise' },
  { id: 102, protocolo: '2025/002', municipio: 'Contagem', data: 'Ontem', acao: 'Correção de documentos' },
  { id: 103, protocolo: '2025/005', municipio: 'Betim', data: '15/02/2025', acao: 'Solicitação de vistoria' },
  { id: 104, protocolo: 'RAT-992', municipio: 'Ouro Preto', data: '10/02/2025', acao: 'Relatório finalizado' },
]);
</script>
