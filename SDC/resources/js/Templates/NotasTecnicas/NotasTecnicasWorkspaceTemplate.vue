<template>
  <div class="w-full space-y-6 pb-8">
    <slot name="cabecalho" />
    <slot name="estatisticas" />

    <!--
      Workspace estilo Notes. Abaixo de lg os paineis empilham na ordem do DOM
      (pastas, lista, leitor, informacoes). A partir de lg vira grade com altura
      fixa e cada painel rola dentro de si: min-h-0 em toda a cadeia (regra 17 da
      responsividade), senao o overflow-y-auto interno nunca ativa.

      Informacoes: coluna propria em 2xl; abaixo disso flutua sobre a borda
      direita do leitor (absolute no container relative), para nao espremer o PDF.
    -->
    <div
      class="relative grid min-h-0 grid-cols-1 gap-4 lg:h-[calc(100dvh-8rem)] lg:min-h-[34rem]"
      :class="colunas"
    >
      <div v-show="pastasAbertas" :class="[painel, 'max-h-72 lg:max-h-none']">
        <slot name="pastas" />
      </div>

      <div :class="[painel, 'max-h-[32rem] lg:max-h-none']">
        <slot name="lista" />
      </div>

      <div :class="[painel, 'min-h-[12rem]']">
        <slot name="leitor" />
      </div>

      <div
        v-show="informacoesAbertas"
        :class="[
          painel,
          'lg:absolute lg:inset-y-0 lg:right-0 lg:z-10 lg:w-80 lg:shadow-2xl',
          '2xl:static 2xl:z-auto 2xl:w-auto 2xl:shadow-none',
        ]"
      >
        <slot name="informacoes" />
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Matriz de layout do workspace de notas tecnicas. So posiciona: nao conhece
 * nota, rota nem permissao. Os paineis chegam por slot.
 */
import { computed } from 'vue';

const props = defineProps({
  pastasAbertas: { type: Boolean, default: true },
  informacoesAbertas: { type: Boolean, default: false },
});

const painel = 'flex min-h-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700/50 dark:bg-slate-900/40';

// Literais por extenso: o Tailwind so gera classe que encontra escrita no fonte.
const colunas = computed(() => {
  if (props.pastasAbertas) {
    return props.informacoesAbertas
      ? 'lg:grid-cols-[13rem_19rem_minmax(0,1fr)] 2xl:grid-cols-[13rem_19rem_minmax(0,1fr)_17rem]'
      : 'lg:grid-cols-[13rem_19rem_minmax(0,1fr)]';
  }

  return props.informacoesAbertas
    ? 'lg:grid-cols-[19rem_minmax(0,1fr)] 2xl:grid-cols-[19rem_minmax(0,1fr)_17rem]'
    : 'lg:grid-cols-[19rem_minmax(0,1fr)]';
});
</script>
