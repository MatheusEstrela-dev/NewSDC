<template>
  <Badge :cor="cor" size="pill" class="whitespace-nowrap" :class="{ 'opacity-60': !funcao }">
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Funcao do membro na equipe da COMPDEC.
 *
 * Categoria, nao estado: cor explicita. Sem funcao definida o badge fica cinza e
 * atenuado -- antes isso era feito trocando o texto por text-slate-400; a opacidade
 * preserva a mesma leitura de "ausencia" sem exigir uma variante de cor so para
 * esse caso.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';

const props = defineProps({
  funcao: {
    type: String,
    required: false,
    default: null,
  },
});

const config = {
  coordenador: { label: 'Coordenador', cor: 'blue' },
  agente: { label: 'Agente', cor: 'emerald' },
  tecnico: { label: 'Tecnico', cor: 'violet' },
  apoio: { label: 'Apoio', cor: 'amber' },
  outro: { label: 'Outro', cor: 'slate' },
};

const label = computed(() => {
  if (!props.funcao) return 'N/A';
  return config[props.funcao]?.label || props.funcao;
});

const cor = computed(() => config[props.funcao]?.cor ?? 'slate');
</script>
