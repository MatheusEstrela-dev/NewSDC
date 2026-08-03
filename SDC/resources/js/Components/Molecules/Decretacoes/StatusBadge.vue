<template>
  <Badge :cor="cor" size="pill" class="whitespace-nowrap">
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Situacao do reconhecimento do processo de decretacao.
 *
 * O badge mais rico do sistema: 16 situacoes, e a cor carrega o estagio no fluxo
 * (quem reconheceu, quem falta, quem negou). Por isso cor explicita e nao variant
 * semantico -- mapear tudo em success/warning/danger colapsaria distincoes que o
 * usuario usa para ler a tabela de relance:
 *
 *   lime    = reconhecido pelo Estado, aguardando a Uniao
 *   green   = reconhecido somente pelo Estado
 *   teal    = reconhecido somente pela Uniao
 *   emerald = reconhecido pelos dois
 *   red     = negado pelo Estado (ou por ambos)
 *   rose    = negado somente pela Uniao
 *
 * A receita de pill (fundo/texto/borda, light e dark) vem do Badge; antes cada
 * entrada deste mapa repetia a receita inteira a mao.
 *
 * As chaves sao os rotulos que o backend envia, com acento. Nao normalizar: a
 * comparacao precisa casar exatamente com o valor persistido.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';

const props = defineProps({
  status: {
    type: String,
    required: false,
    default: null,
  },
});

const config = {
  'Registro': { label: 'Registro', cor: 'sky' },
  'TEMPORARIO': { label: 'TEMPORARIO', cor: 'amber' },
  'Envio Direto para União': { label: 'Envio Direto para Uniao', cor: 'purple' },
  'Aguardando Análise do Estado': { label: 'Aguardando Analise', cor: 'amber' },
  'Em análise pelo Estado': { label: 'Em Analise', cor: 'cyan' },
  'Aguardando ajustes do município': { label: 'Aguardando Ajustes', cor: 'orange' },
  'Reconhecido pelo Estado / Aguardando análise da União': { label: 'Rec. Estado / Aguard. Uniao', cor: 'lime' },
  'Reconhecido pelo Estado e pela União': { label: 'Reconhecido (Completo)', cor: 'emerald' },
  'Reconhecido somente pelo Estado': { label: 'Reconhecido (Estado)', cor: 'green' },
  'Reconhecido somente pela União': { label: 'Reconhecido (Uniao)', cor: 'teal' },
  'Não reconhecido pelo Estado': { label: 'Nao Reconhecido (Estado)', cor: 'red' },
  'Não reconhecido pela União': { label: 'Nao Reconhecido (Uniao)', cor: 'rose' },
  'Não reconhecido pelo Estado e União': { label: 'Nao Reconhecido', cor: 'red' },
  'Pendente': { label: 'Pendente', cor: 'violet' },
  'Em andamento': { label: 'Em Andamento', cor: 'indigo' },
  'Concluido': { label: 'Concluido', cor: 'emerald' },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return config[props.status]?.label || props.status;
});

const cor = computed(() => config[props.status]?.cor ?? 'slate');
</script>
