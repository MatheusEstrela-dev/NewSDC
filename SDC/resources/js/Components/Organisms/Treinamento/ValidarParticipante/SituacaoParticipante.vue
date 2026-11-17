<script setup>
import { computed } from 'vue';
import { QrCodeIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import ResultadoParticipante from '@/Components/Organisms/Treinamento/ValidarParticipante/ResultadoParticipante.vue';

/*
 * Painel "Situacao": sempre visivel. Vazio explicativo antes da consulta; depois,
 * o selo de situacao (certificados validos / andamento) e o resultado, ou o erro.
 */
const props = defineProps({
  resultado: { type: Object, default: null },
  erro: { type: String, default: null },
  urlProximo: { type: String, required: true },
});

const temConteudo = computed(() => props.resultado !== null || props.erro !== null);

const totalCertificados = computed(() => props.resultado?.certificados.length ?? 0);
const totalAndamento = computed(() => props.resultado?.em_andamento.length ?? 0);

const plural = (n, singular, pluralTexto) => (n === 1 ? singular : pluralTexto);
</script>

<template>
  <CardBase class="space-y-4">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Situação</h2>

    <p
      v-if="erro"
      class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-base font-medium text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
      role="alert"
    >
      {{ erro }}
    </p>

    <template v-if="resultado">
      <div class="flex flex-wrap gap-2">
        <Badge :variant="totalCertificados ? 'success' : 'warning'">
          {{ totalCertificados
            ? 'Possui ' + totalCertificados + ' ' + plural(totalCertificados, 'certificado válido', 'certificados válidos')
            : 'Nenhum certificado válido' }}
        </Badge>
        <Badge v-if="totalAndamento" variant="info">
          {{ totalAndamento }} {{ plural(totalAndamento, 'curso em andamento', 'cursos em andamento') }}
        </Badge>
      </div>
      <ResultadoParticipante :resultado="resultado" />
    </template>

    <p v-else-if="!erro" class="py-6 text-center text-base text-slate-600 dark:text-slate-300">
      Leia o QR ou pesquise por CPF/certificado para ver a situação do servidor.
    </p>

    <Button v-if="temConteudo" variant="primary" size="lg" full-width :icon="QrCodeIcon" :href="urlProximo">
      Ler próximo
    </Button>
  </CardBase>
</template>
