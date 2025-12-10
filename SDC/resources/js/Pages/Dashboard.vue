<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-gray-100 p-8">
      <!-- Banner Ano Fiscal -->
      <div class="relative bg-gradient-to-r from-slate-800 to-slate-900 text-white px-6 py-5 rounded-2xl shadow-lg mb-8 overflow-hidden">
        <!-- Badge CI/CD Jenkins - Alteração Visual para Teste -->
        <div class="absolute top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-full text-xs font-bold shadow-lg animate-pulse z-20">
          🚀 Jenkins CI/CD Ativo - Deploy: {{ new Date().toLocaleString('pt-BR') }}
        </div>
        <div class="relative z-10">
          <p class="text-xs text-blue-200/80 uppercase font-bold tracking-widest mb-1">
            Painel Gerencial
          </p>
          <h2 class="text-3xl font-bold tracking-tight text-white">Exercício {{ currentYear }}</h2>
          <p class="text-slate-400 text-sm mt-1 max-w-md">
            Visão consolidada dos processos de transferência e apoio aos municípios mineiros.
          </p>
        </div>
      </div>

      <!-- Grid de Métricas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div
          v-for="(metric, key) in metrics"
          :key="key"
          class="bg-white rounded-xl p-5 shadow-lg border border-slate-100"
        >
          <div class="flex justify-between items-start mb-4">
            <div :class="[metric.color, 'w-10 h-10 rounded-lg flex items-center justify-center text-white']">
              {{ metric.icon }}
            </div>
            <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-full">
              +2%
            </span>
          </div>
          <div>
            <p class="text-3xl font-bold text-slate-800 mt-1">{{ metric.val }}</p>
            <p class="text-sm font-medium text-slate-500">{{ metric.label }}</p>
          </div>
        </div>
      </div>

      <!-- Conteúdo Principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Tabela PMDA -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-slate-100">
          <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg">PMDA em Análise</h3>
            <p class="text-xs text-slate-500 mt-0.5">Processos aguardando intervenção técnica</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-slate-400 uppercase font-bold bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="px-6 py-4">Protocolo</th>
                  <th class="px-6 py-4">Município</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4">Data</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="item in pmdaEmAnalise" :key="item.id" class="hover:bg-slate-50">
                  <td class="px-6 py-4 font-medium text-slate-900">{{ item.protocolo }}</td>
                  <td class="px-6 py-4 text-slate-700">{{ item.municipio }}</td>
                  <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-500 text-xs">{{ item.data }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-xl shadow-lg border border-slate-100">
          <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg">Últimas Movimentações</h3>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div
                v-for="h in historico"
                :key="h.id"
                class="flex gap-4 pb-4 border-b border-slate-100 last:border-0"
              >
                <div class="w-4 h-4 rounded-full bg-blue-500 mt-1"></div>
                <div class="flex-1">
                  <p class="font-semibold text-slate-800 text-sm">{{ h.municipio }}</p>
                  <p class="text-sm text-slate-600 mt-0.5">{{ h.acao }}</p>
                  <p class="text-xs text-slate-400 mt-1 font-mono">{{ h.protocolo }}</p>
                  <span class="text-xs text-slate-500">{{ h.data }}</span>
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
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
