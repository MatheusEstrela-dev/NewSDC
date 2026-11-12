<script setup>
/**
 * Selos do curso EAD na area do professor: um de publicacao e, so quando o
 * curso esta pausado ou desativado, um da situacao. O status do treinamento
 * (fluxo de eventos) nao aparece: no EAD ele repetia a publicacao.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  publicado: { type: Boolean, default: false },
  // SituacaoCurso: ATIVO | PAUSADO | DESATIVADO
  situacao: { type: String, default: 'ATIVO' },
  situacaoLabel: { type: String, default: '' },
});

const VARIANTS = { PAUSADO: 'warning', DESATIVADO: 'neutral' };

const variantSituacao = computed(() => VARIANTS[props.situacao] ?? 'default');
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <Badge :variant="publicado ? 'success' : 'neutral'" size="sm">
      {{ publicado ? 'Publicado' : 'Não publicado' }}
    </Badge>
    <Badge v-if="situacao !== 'ATIVO'" :variant="variantSituacao" size="sm">
      {{ situacaoLabel || situacao }}
    </Badge>
  </div>
</template>
