<script setup>
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CredencialCertificadoLinha from '@/Components/Molecules/Treinamento/CredencialCertificadoLinha.vue';
import CredencialCursoAndamento from '@/Components/Molecules/Treinamento/CredencialCursoAndamento.vue';
import { formatDateTime } from '@/utils/dateFormatter';

/* Resultado da consulta de uma credencial: identificacao, certificados validos e cursos em andamento. */
defineProps({
  resultado: { type: Object, required: true },
});
</script>

<template>
  <div class="space-y-4">
    <CardBase>
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="break-words text-xl font-bold text-slate-900 dark:text-white">{{ resultado.servidor.nome }}</h2>
          <p class="mt-1 font-mono text-base text-slate-700 dark:text-slate-200">CPF {{ resultado.servidor.cpf_mascarado }}</p>
          <p v-if="resultado.servidor.orgao" class="mt-1 break-words text-sm text-slate-600 dark:text-slate-300">
            {{ resultado.servidor.orgao }}
          </p>
        </div>
        <Badge variant="success" class="shrink-0">
          <ShieldCheckIcon class="mr-1 h-4 w-4" />
          Consultado agora
        </Badge>
      </div>
      <p v-if="resultado.consultado_em" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        {{ formatDateTime(resultado.consultado_em) }}
      </p>
    </CardBase>

    <CardBase>
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        Certificados válidos ({{ resultado.certificados.length }})
      </h3>
      <ul v-if="resultado.certificados.length" class="mt-1 divide-y divide-slate-200 dark:divide-slate-700">
        <CredencialCertificadoLinha
          v-for="certificado in resultado.certificados"
          :key="certificado.codigo_verificacao"
          :certificado="certificado"
        />
      </ul>
      <p v-else class="py-4 text-base text-slate-600 dark:text-slate-300">Nenhum certificado válido.</p>
    </CardBase>

    <CardBase v-if="resultado.em_andamento.length">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        Cursos em andamento ({{ resultado.em_andamento.length }})
      </h3>
      <ul class="mt-1 divide-y divide-slate-200 dark:divide-slate-700">
        <CredencialCursoAndamento
          v-for="(curso, indice) in resultado.em_andamento"
          :key="indice + curso.titulo"
          :curso="curso"
        />
      </ul>
    </CardBase>
  </div>
</template>
