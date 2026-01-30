<template>
  <div class="w-full bg-[#020617] border-b border-slate-900 px-6 py-4 flex items-center">
    <div class="flex items-center gap-3">
      <BackButton @click="handleBack" />
      <BreadcrumbTrail :items="breadcrumbItems" />
    </div>

    <div class="ml-auto flex items-center gap-3">
      <div class="text-[11px] text-slate-500 font-mono uppercase tracking-[0.2em]">
        Controlo de Fluxo
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
