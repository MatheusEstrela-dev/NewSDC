<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';

const props = defineProps({
  treinamento: {
    type: Object,
    required: true,
  },
});

const formatDate = (date) => {
  if (!date) return null;
  return new Date(date).toLocaleDateString('pt-BR');
};

const goBack = () => {
  router.visit(route('treinamentos.index'));
};
</script>

<template>
  <AuthenticatedLayout :title="treinamento.titulo">
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Voltar -->
      <button
        @click="goBack"
        class="mb-4 flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Voltar
      </button>

      <CardBase class="p-6">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-start justify-between mb-3">
            <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">
              {{ treinamento.titulo }}
            </Heading>
            <Badge :color="treinamento.statusColor" class="text-sm">
              {{ treinamento.statusLabel }}
            </Badge>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <Badge :color="treinamento.tipoColor">
              {{ treinamento.tipoLabel }}
            </Badge>
            <Text size="sm" color="muted">
              {{ treinamento.cargaHoraria }}h de carga horária
            </Text>
          </div>
        </div>

        <!-- Descrição -->
        <div v-if="treinamento.descricao" class="mb-6">
          <Heading :level="3" class="text-lg font-semibold mb-2">
            Descrição
          </Heading>
          <Text size="base" class="text-slate-700 dark:text-slate-300">
            {{ treinamento.descricao }}
          </Text>
        </div>

        <!-- Informações Detalhadas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Instrutor -->
          <div v-if="treinamento.instrutor">
            <Text size="sm" color="muted" class="mb-1">
              Instrutor
            </Text>
            <Text size="base" class="font-medium">
              {{ treinamento.instrutor }}
            </Text>
          </div>

          <!-- Local -->
          <div v-if="treinamento.local">
            <Text size="sm" color="muted" class="mb-1">
              Local
            </Text>
            <Text size="base" class="font-medium">
              {{ treinamento.local }}
            </Text>
          </div>

          <!-- Data Início -->
          <div v-if="treinamento.dataInicio">
            <Text size="sm" color="muted" class="mb-1">
              Data de Início
            </Text>
            <Text size="base" class="font-medium">
              {{ formatDate(treinamento.dataInicio) }}
            </Text>
          </div>

          <!-- Data Fim -->
          <div v-if="treinamento.dataFim">
            <Text size="sm" color="muted" class="mb-1">
              Data de Término
            </Text>
            <Text size="base" class="font-medium">
              {{ formatDate(treinamento.dataFim) }}
            </Text>
          </div>

          <!-- Vagas -->
          <div>
            <Text size="sm" color="muted" class="mb-1">
              Vagas
            </Text>
            <Text size="base" class="font-medium">
              <span v-if="treinamento.numeroVagas">
                {{ treinamento.vagasDisponiveis }} de {{ treinamento.numeroVagas }} disponíveis
              </span>
              <span v-else>Ilimitadas</span>
            </Text>
          </div>

          <!-- Inscrições -->
          <div>
            <Text size="sm" color="muted" class="mb-1">
              Inscrições
            </Text>
            <Text size="base" class="font-medium">
              {{ treinamento.totalInscricoes }} participantes
            </Text>
          </div>
        </div>

        <!-- Módulos -->
        <div v-if="treinamento.totalModulos > 0" class="border-t pt-6">
          <Heading :level="3" class="text-lg font-semibold mb-3">
            Módulos
          </Heading>
          <Text size="sm" color="muted">
            {{ treinamento.totalModulos }} módulos cadastrados
          </Text>
          <!-- TODO: Listar módulos -->
        </div>

        <!-- Ações -->
        <div class="mt-6 flex gap-3">
          <button
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm hover:shadow-md transition-all"
          >
            Inscrever-se
          </button>
        </div>
      </CardBase>
    </div>
  </AuthenticatedLayout>
</template>
