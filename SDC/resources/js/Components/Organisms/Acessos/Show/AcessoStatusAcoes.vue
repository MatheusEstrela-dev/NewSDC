<template>
  <Button
    v-for="acao in acoes"
    :key="acao.chave"
    :variant="acao.variante"
    size="md"
    class="min-h-10"
    :disabled="processando"
    @click="acao.acionar"
  >
    {{ acao.rotulo }}
  </Button>
</template>

<script setup>
import { computed } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  status: { type: String, required: true },
  processando: { type: Boolean, default: false },
});
const emit = defineEmits(['aprovar', 'status']);

// Mesmas transicoes oferecidas antes; a regra valida fica no alterarStatus do controller.
const acoes = computed(() => [
  props.status === 'pendente' && { chave: 'aprovar', rotulo: 'Aprovar', variante: 'success', acionar: () => emit('aprovar') },
  ['aprovado', 'inativo'].includes(props.status) && { chave: 'ativar', rotulo: 'Ativar cadastro', variante: 'outline', acionar: () => emit('status', 'ativo') },
  props.status === 'ativo' && { chave: 'inativar', rotulo: 'Inativar cadastro', variante: 'outline', acionar: () => emit('status', 'inativo') },
  props.status === 'pendente' && { chave: 'rejeitar', rotulo: 'Rejeitar', variante: 'outline', acionar: () => emit('status', 'rejeitado') },
].filter(Boolean));
</script>
