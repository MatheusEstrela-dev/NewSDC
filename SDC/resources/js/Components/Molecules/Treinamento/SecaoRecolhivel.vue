<script setup>
import { useCollapsibleSection } from '@/Composables/core/useCollapsibleSection';
import SecaoHeader from '@/Components/Molecules/Treinamento/SecaoHeader.vue';

/*
 * Secao que abre e recolhe pelo proprio cabecalho. Comeca aberta e o navegador
 * lembra o que a pessoa fechou (useCollapsibleSection, por namespace + id).
 */
const props = defineProps({
  titulo: { type: String, required: true },
  total: { type: Number, default: null },
  namespace: { type: String, required: true },
  secaoId: { type: String, required: true },
});

const { estaExpandido, alternar } = useCollapsibleSection(props.namespace, props.secaoId, {
  expandidoPorPadrao: true,
  recolherNoMobile: false,
});
</script>

<template>
  <section>
    <SecaoHeader :titulo="titulo" :total="total" :expandido="estaExpandido" @alternar="alternar" />
    <div v-show="estaExpandido">
      <slot />
    </div>
  </section>
</template>
