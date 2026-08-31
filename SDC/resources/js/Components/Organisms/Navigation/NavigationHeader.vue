<template>
  <!--
    `min-w-0` + `overflow-x-auto` no trilho, e nao no pai.

    O <main> do AuthenticatedLayout tem `[overflow-x:clip]`, mas este header e
    IRMAO dele -- fica fora do clip. Sem conter o transbordo aqui, a trilha
    ("Voltar | Inicio | Plantao | Plantonistas", com `whitespace-nowrap` em cada
    rotulo) empurra a largura do wrapper, e a PAGINA INTEIRA passa a rolar de
    lado no telefone: os stat cards apareciam cortados na esquerda.

    A solucao e a mesma que o <main> ja usava, aplicada um nivel acima: quem
    transborda rola dentro de si. `min-w-0` e obrigatorio junto -- item flex nao
    encolhe abaixo do conteudo sem ele, e o `overflow` sozinho nao teria efeito.

    O botao Voltar fica `shrink-0`: ele e o alvo de toque que nao pode diminuir.
  -->
  <div class="w-full min-w-0 bg-white dark:bg-[#020617] border-b border-slate-200 dark:border-slate-900 px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
    <div class="flex min-w-0 items-center gap-3">
      <div class="shrink-0">
        <BackButton @click="handleBack" />
      </div>
      <div class="min-w-0 overflow-x-auto trilha-scroll">
        <BreadcrumbTrail :items="breadcrumbItems" />
      </div>
    </div>
  </div>
</template>

<script setup>
import BackButton from '@/Components/Atoms/Navigation/BackButton.vue';
import BreadcrumbTrail from '@/Components/Molecules/Navigation/BreadcrumbTrail.vue';
import { useBreadcrumb } from '@/Composables/useBreadcrumb';

const { breadcrumbItems, handleBack } = useBreadcrumb();
</script>

<style scoped>
/*
 * Barra de rolagem escondida no trilho: ela rola por gesto no telefone e a
 * barra visivel cortaria a borda de baixo dos segmentos, que tem `rounded-lg`.
 * O conteudo segue acessivel por teclado e por scroll horizontal normal.
 */
.trilha-scroll {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.trilha-scroll::-webkit-scrollbar {
  display: none;
}
</style>
