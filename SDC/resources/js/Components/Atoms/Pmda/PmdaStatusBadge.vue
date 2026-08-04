<template>
  <Badge :cor="corEfetiva">{{ label }}</Badge>
</template>

<script setup>
/**
 * Badge de status do PMDA.
 *
 * Antes recebia a classe Tailwind pronta do backend (status_color, de
 * PmdaStatus::getColorClass) e a aplicava direto. Isso deixava a pill fora do padrao
 * do sistema -- sem borda, texto -800 -- e amarrava a aparencia a um enum de PHP:
 * mudar o padrao visual exigia mexer no dominio.
 *
 * Agora o backend manda NOME de cor (status_cor) e o Badge aplica a receita de pill
 * num lugar so.
 *
 * colorClass segue aceito para quem ainda manda a classe crua: a cor e extraida
 * dela, para a transicao nao exigir trocar todos os consumidores de uma vez.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  label: { type: String, required: true },

  /** Nome de cor da paleta do Badge (caminho novo). */
  cor: { type: String, default: null },

  /** @deprecated Classe Tailwind crua vinda do backend. */
  colorClass: { type: String, default: null },
});

// bg-amber-100 -> amber. Ponte para o formato antigo, sem mapa fixo a manter.
const corDaClasse = computed(() => {
  const m = /bg-([a-z]+)-\d{2,3}/.exec(props.colorClass ?? '');
  if (!m) return null;

  // gray nao existe na paleta do Badge; o equivalente semantico e o cinza forte,
  // usado para estado encerrado sem desfecho.
  return m[1] === 'gray' ? 'slate-forte' : m[1];
});

const corEfetiva = computed(() => props.cor ?? corDaClasse.value ?? 'slate');
</script>
