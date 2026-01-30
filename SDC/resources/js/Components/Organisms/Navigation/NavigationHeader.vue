<template>
  <div class="w-full bg-white dark:bg-[#020617] border-b border-slate-200 dark:border-slate-900 px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <BackButton @click="handleBack" />
      <BreadcrumbTrail :items="breadcrumbItems" />
    </div>
  </div>
</template>

<script setup>
import BackButton from '@/Components/Atoms/Navigation/BackButton.vue';
import BreadcrumbTrail from '@/Components/Molecules/Navigation/BreadcrumbTrail.vue';
import { useBreadcrumb } from '@/Composables/useBreadcrumb';

const { breadcrumbItems, handleBack } = useBreadcrumb();
</script>
