<script setup>
import { PrinterIcon } from '@heroicons/vue/24/outline';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CertificadoSituacaoBadge from '@/Components/Molecules/Treinamento/CertificadoSituacaoBadge.vue';
import CodigoVerificacao from '@/Components/Molecules/Treinamento/CodigoVerificacao.vue';

/*
 * Certificado do proprio aluno (servidor ou cidadao): titulo, emissao, codigo
 * de verificacao, aproveitamento, situacao e link de impressao. `imprimirUrl`
 * vem de fora porque a rota difere entre o portal e a area do servidor.
 */
defineProps({
  certificado: { type: Object, required: true },
  imprimirUrl: { type: String, default: null },
});
</script>

<template>
  <CardBase class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0 space-y-1.5">
      <Text size="base" class="font-semibold">{{ certificado.treinamento_titulo }}</Text>
      <Text size="sm" class="text-slate-600 dark:text-slate-300">
        {{ certificado.emitido_em ? `Emitido em ${new Date(certificado.emitido_em).toLocaleDateString('pt-BR')}` : 'Aguardando emissão' }}
        <template v-if="certificado.aproveitamento !== null && certificado.aproveitamento !== undefined">
          · Aproveitamento {{ Number(certificado.aproveitamento).toLocaleString('pt-BR') }}%
        </template>
      </Text>
      <div v-if="certificado.codigo_verificacao" class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
        <span>Código de verificação</span>
        <CodigoVerificacao :codigo="certificado.codigo_verificacao" :url-validacao="certificado.url_validacao" />
      </div>
    </div>
    <div class="flex shrink-0 items-center gap-3">
      <CertificadoSituacaoBadge :certificado="certificado" />
      <a
        v-if="certificado.disponivel && imprimirUrl"
        :href="imprimirUrl"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
      >
        <PrinterIcon class="h-4 w-4" />
        Ver / Imprimir
      </a>
    </div>
  </CardBase>
</template>
