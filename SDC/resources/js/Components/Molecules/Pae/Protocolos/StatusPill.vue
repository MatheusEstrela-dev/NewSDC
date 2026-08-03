<template>
  <Badge :cor="cor" size="pill" class="whitespace-nowrap">
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Situacao do protocolo PAE ao longo do fluxo de analise.
 *
 * Cor explicita e nao variant semantico: a cor marca a ETAPA do fluxo, nao apenas
 * sucesso ou falha. Etapas distintas compartilham matiz de proposito (as tres
 * primeiras em azul = tramitacao inicial; dilacao e aguardando tratativa em laranja
 * = a bola esta com o municipio), e colapsar isso em success/warning/danger perderia
 * a leitura de progresso.
 *
 * A receita de pill vem do Badge; antes as 17 entradas a repetiam a mao. No caminho,
 * algumas divergencias de dark mode desapareceram: havia entradas com /15 em vez de
 * /20 no fundo e tons -200 em vez de -300 no texto, sem intencao documentada --
 * variacao acidental de copia e cola.
 */
import { computed } from 'vue';
import Badge from '../../../Atoms/Badge/Badge.vue';

const props = defineProps({
  situacao: {
    type: String,
    default: '',
  },
});

const config = {
  novo: { label: 'Novo', cor: 'slate' },
  entrada_processo: { label: 'Entrada do Processo', cor: 'blue' },
  criacao_sdc: { label: 'Criação no SDC', cor: 'blue' },
  gerenciamento: { label: 'Gerenciamento', cor: 'blue' },
  notificacao: { label: 'Notificação', cor: 'amber' },
  analise: { label: 'Análise', cor: 'indigo' },
  aprovado: { label: 'Aprovado', cor: 'green' },
  reprovado: { label: 'Reprovado', cor: 'red' },
  ccpae: { label: 'CCPAE', cor: 'emerald' },
  ativo_3_anos: { label: 'Ativo (3 anos)', cor: 'emerald' },
  suspenso: { label: 'Suspenso', cor: 'yellow' },
  revogado: { label: 'Revogado', cor: 'red' },
  esperar_tratativa: { label: 'Aguardando Tratativa', cor: 'orange' },
  dilacao: { label: 'Dilatação', cor: 'orange' },
  aguardando_analise: { label: 'Aguardando Análise', cor: 'amber' },
  em_edicao: { label: 'Em edição', cor: 'red' },
  finalizado: { label: 'Finalizado', cor: 'emerald' },
};

const label = computed(() => config[props.situacao]?.label || props.situacao || '—');
const cor = computed(() => config[props.situacao]?.cor ?? 'slate');
</script>
