<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="dashboard-container">
      <!-- Header Padronizado -->
      <PageHeader
        title="Painel Gerencial"
        :description="`Exercício ${currentYear} - Visão consolidada dos processos de transferência e apoio aos municípios mineiros.`"
        :icon="HomeIcon"
        variant="gradient"
      />

      <!-- Grid de Métricas Padronizado -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <StatCard
          title="Em Edição"
          :value="24"
          variant="info"
          :icon="PencilSquareIcon"
        />
        <StatCard
          title="Em Análise"
          :value="5"
          variant="warning"
          :icon="ClockIcon"
        />
        <StatCard
          title="Aprovados"
          :value="77"
          variant="success"
          :icon="CheckCircleIcon"
        />
        <StatCard
          title="Atendidos"
          :value="12"
          variant="danger"
          :icon="CheckCircleIcon"
        />
      </div>

      <!-- Conteúdo Principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- PMDA em Análise - Responsivo -->
        <div class="lg:col-span-2 rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-slate-200">PMDA em Análise</h3>
            <p class="text-xs mt-0.5 text-slate-600 dark:text-slate-400">Processos aguardando intervenção técnica</p>
          </div>

          <!-- Desktop: Tabela -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs uppercase font-bold border-b bg-slate-100 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 border-slate-100 dark:border-slate-700">
                <tr>
                  <th class="px-6 py-4">Protocolo</th>
                  <th class="px-6 py-4">Município</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4">Data</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                <tr v-for="item in pmdaEmAnalise" :key="item.id" class="hover:bg-slate-100 dark:hover:bg-slate-700/30">
                  <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-200">{{ item.protocolo }}</td>
                  <td class="px-6 py-4 text-slate-700 dark:text-slate-300">{{ item.municipio }}</td>
                  <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-400">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">{{ item.data }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile: Cards -->
          <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
            <div
              v-for="item in pmdaEmAnalise"
              :key="item.id"
              class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/20 active:bg-slate-100 dark:active:bg-slate-700/30 transition-colors"
            >
              <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                  <div class="font-bold text-sm text-slate-900 dark:text-slate-200 mb-0.5">
                    {{ item.protocolo }}
                  </div>
                  <div class="text-sm text-slate-700 dark:text-slate-300">
                    {{ item.municipio }}
                  </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-400 flex-shrink-0">
                  {{ item.status }}
                </span>
              </div>
              <div class="text-xs text-slate-500 dark:text-slate-400">
                {{ item.data }}
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-lg text-slate-900 dark:text-slate-200">Últimas Movimentações</h3>
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
                  <p class="font-semibold text-sm text-slate-900 dark:text-slate-200">{{ h.municipio }}</p>
                  <p class="text-sm mt-0.5 text-slate-700 dark:text-slate-400">{{ h.acao }}</p>
                  <p class="text-xs mt-1 font-mono text-slate-500 dark:text-slate-500">{{ h.protocolo }}</p>
                  <span class="text-xs text-slate-500 dark:text-slate-400">{{ h.data }}</span>
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
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

// Dados inline para teste (sem dependências externas)
const currentYear = ref(new Date().getFullYear());

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

<style scoped>
.dashboard-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  /* Padding removed for global alignment */
}
</style>

