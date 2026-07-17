<template>
  <div class="flex flex-col gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
      <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
        <GovBrLogo size="sm" />
      </div>
      <div>
        <h4 class="font-bold text-slate-900 dark:text-white">GOV.br Assinador</h4>
        <p class="text-sm text-slate-500">
          Assine documentos digitais pelo Portal de Assinatura Eletronica GOV.br.
        </p>
      </div>
    </div>

    <GovBrButton
      :href="signerUrl"
      label="Entrar com"
      size="sm"
      title="Abrir Portal de Assinatura Eletronica GOV.br"
    />
  </div>
</template>

<script setup>
import GovBrLogo from '@/Components/Atoms/GovBr/GovBrLogo.vue';
import GovBrButton from '@/Components/Molecules/GovBr/GovBrButton.vue';

const signerUrl = 'https://assinador.iti.br/assinatura/index.xhtml';
</script>
