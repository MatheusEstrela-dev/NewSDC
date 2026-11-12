<script setup>
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
  <CardBase class="flex items-center justify-between gap-4 p-5">
    <div class="min-w-0">
      <Text size="base" class="font-semibold">{{ certificado.treinamento_titulo }}</Text>
      <Text size="xs" color="muted">
        {{ certificado.emitido_em ? new Date(certificado.emitido_em).toLocaleDateString('pt-BR') : 'Aguardando emissão' }}
        <template v-if="certificado.aproveitamento !== null && certificado.aproveitamento !== undefined">
          · Aproveitamento {{ Number(certificado.aproveitamento).toLocaleString('pt-BR') }}%
        </template>
      </Text>
      <Text v-if="certificado.codigo_verificacao" size="xs" color="muted" class="mt-1">
        Código de verificação:
        <CodigoVerificacao :codigo="certificado.codigo_verificacao" :url-validacao="certificado.url_validacao" />
      </Text>
    </div>
    <div class="flex shrink-0 items-center gap-3">
      <CertificadoSituacaoBadge :certificado="certificado" />
      <a
        v-if="certificado.disponivel && imprimirUrl"
        :href="imprimirUrl"
        target="_blank"
        rel="noopener"
        class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
      >
        Ver / Imprimir
      </a>
    </div>
  </CardBase>
</template>
