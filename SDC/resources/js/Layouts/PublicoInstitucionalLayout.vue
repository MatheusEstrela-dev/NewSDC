<script setup>
/**
 * Moldura das telas publicas (sem login) com a identidade da Defesa Civil MG:
 * faixa azul institucional com o logo, filete laranja, conteudo centralizado e
 * rodape discreto. O GuestLayout segue sendo o do login; este nao o substitui.
 *
 * useTheme so inicializa o tema salvo (html.dark), ja que aqui nao ha topbar.
 */
import { useTheme } from '@/Composables/ui/useTheme';

defineProps({
  /** Largura maxima do conteudo (classe Tailwind estatica). */
  larguraMax: { type: String, default: 'max-w-3xl' },
});

useTheme();
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 [overflow-x:clip]">
    <header class="bg-[#093871] text-white">
      <div class="mx-auto flex w-full max-w-5xl items-center gap-3 px-4 py-3 sm:gap-4 sm:px-6">
        <span class="shrink-0 rounded-lg bg-white p-1.5 shadow-sm">
          <img
            src="/imgs/certificado/logo-defesa-civil-mg.png"
            alt="Defesa Civil de Minas Gerais"
            width="56"
            height="56"
            class="block h-12 w-auto sm:h-14"
            decoding="async"
          />
        </span>
        <div class="min-w-0">
          <p class="text-sm font-semibold leading-tight sm:text-base">Defesa Civil de Minas Gerais</p>
          <p class="text-xs leading-snug text-blue-100 sm:text-sm">Coordenadoria Estadual de Defesa Civil (CEDEC-MG)</p>
        </div>
      </div>
      <div class="h-1 bg-[#F18945]" aria-hidden="true"></div>
    </header>

    <main class="flex-1 px-4 py-6 sm:px-6 sm:py-10">
      <div :class="['mx-auto w-full', larguraMax]">
        <slot />
      </div>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center dark:border-slate-800 dark:bg-slate-900">
      <p class="text-xs text-slate-600 dark:text-slate-400">
        Diretoria de Educação em Proteção e Defesa Civil (DEPDC) - Coordenadoria Estadual de Defesa Civil de Minas Gerais (CEDEC-MG)
      </p>
      <p class="mt-1 text-xs text-slate-500">&copy; {{ new Date().getFullYear() }} Governo do Estado de Minas Gerais</p>
    </footer>
  </div>
</template>
