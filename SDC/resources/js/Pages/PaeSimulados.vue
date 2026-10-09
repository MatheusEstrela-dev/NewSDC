<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`Simulados - ${protocolo.num_protocolo}`" />
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Simulados do Anexo C · {{ protocolo.num_protocolo }}</h1>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});
</script>
