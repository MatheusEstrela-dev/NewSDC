<template>
  <AuthenticatedLayout :title="`Processo #${processo.id}`">
    <div class="processo-show-container">
      <!-- Breadcrumb -->
      <nav class="mb-6 flex items-center gap-2 text-sm">
        <a
          href="/decretacoes"
          class="text-slate-400 hover:text-slate-200 transition-colors"
        >
          Decretações
        </a>
        <span class="text-slate-600">/</span>
        <span class="text-slate-200">Processo #{{ processo.id }}</span>
      </nav>

      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <Heading :level="2" color="default" class="mb-2">
              {{ processo.tipo_desastre_nome || 'Processo de Reconhecimento' }}
            </Heading>
            <div class="flex items-center gap-2 flex-wrap">
              <TipoProcessoBadge :tipo="processo.processo" />
              <StatusBadge :status="processo.status" />
              <PrazoBadge
                :dias-restantes="processo.dias_restantes"
                :data-vencimento="processo.data_vencimento"
              />
            </div>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
              @click="handlePrint"
            >
              Imprimir
            </button>
            <button
              v-if="processo.pode_editar"
              type="button"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
              @click="handleEdit"
            >
              Editar
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6">
        <div class="border-b border-slate-700">
          <nav class="flex gap-4">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              :class="[
                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                currentTab === tab.id
                  ? 'border-primary-500 text-primary-400'
                  : 'border-transparent text-slate-400 hover:text-slate-200'
              ]"
              @click="currentTab = tab.id"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Dados Gerais -->
        <CardBase v-show="currentTab === 'geral'" variant="default" padding="lg">
          <Heading :level="4" color="default" class="mb-4">Dados Gerais</Heading>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Text size="xs" color="muted" class="mb-1">Tipo de Desastre</Text>
              <Text size="sm" weight="medium">{{ processo.tipo_desastre_nome }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">COBRADE</Text>
              <Text size="sm" weight="medium">{{ processo.tipo_desastre_cobrade || '—' }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Data Ocorrência</Text>
              <Text size="sm" weight="medium">{{ formatDate(processo.data_ocorrencia_desastre) }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Data Entrada</Text>
              <Text size="sm" weight="medium">{{ formatDate(processo.data_entrada) }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Protocolo FIDE</Text>
              <Text size="sm" weight="medium">{{ processo.n_protocolo_fide || '—' }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Decreto Municipal</Text>
              <Text size="sm" weight="medium">{{ processo.decreto_municipal || '—' }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Analista</Text>
              <Text size="sm" weight="medium">{{ processo.analista || '—' }}</Text>
            </div>
            <div>
              <Text size="xs" color="muted" class="mb-1">Processo SEI</Text>
              <Text size="sm" weight="medium">{{ processo.processo_inserido_sei || '—' }}</Text>
            </div>
          </div>
        </CardBase>

        <!-- Municípios -->
        <div v-show="currentTab === 'municipios'" class="space-y-4">
          <CardBase
            v-for="municipio in processo.municipios"
            :key="municipio.id"
            variant="default"
            padding="lg"
          >
            <Heading :level="4" color="default" class="mb-4">
              {{ municipio.p_nome || municipio.nome }}
            </Heading>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <Text size="xs" color="muted" class="mb-1">População</Text>
                <Text size="sm" weight="medium">{{ formatNumber(municipio.populacao) }}</Text>
              </div>
              <div>
                <Text size="xs" color="muted" class="mb-1">Redec</Text>
                <Text size="sm" weight="medium">{{ municipio.rpm || '—' }}</Text>
              </div>
              <div>
                <Text size="xs" color="muted" class="mb-1">Código IBGE</Text>
                <Text size="sm" weight="medium">{{ municipio.Codmundv || '—' }}</Text>
              </div>
            </div>
          </CardBase>
        </div>

        <!-- TODO: Adicionar outros tabs (Danos, Documentos, Log) -->
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import PrazoBadge from '@/Components/Molecules/Decretacoes/PrazoBadge.vue';
import TipoProcessoBadge from '@/Components/Molecules/Decretacoes/TipoProcessoBadge.vue';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
});

const currentTab = ref('geral');

const tabs = [
  { id: 'geral', label: 'Dados Gerais' },
  { id: 'municipios', label: 'Municípios' },
  { id: 'danos', label: 'Danos' },
  { id: 'documentos', label: 'Documentos' },
  { id: 'log', label: 'Histórico' },
];

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('pt-BR');
};

const formatNumber = (num) => {
  if (!num) return '—';
  return new Intl.NumberFormat('pt-BR').format(num);
};

const handleEdit = () => {
  router.visit(`/decretacoes/${props.processo.id}/edit`);
};

const handlePrint = () => {
  window.print();
};
</script>

<style scoped>
.processo-show-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .processo-show-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .processo-show-container {
    padding: 2rem 3rem;
  }
}
</style>
