<template>
  <Head :title="item ? `Propor mudança em ${item.codigo}` : 'Propor novo item'" />

  <div class="pb-6">
    <PageHeader
      :title="item ? `Propor mudança em ${item.titulo}` : 'Propor novo item'"
      description="A proposta só entra no catálogo depois de aprovada por outra pessoa. Autor, data e IP ficam registrados."
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    />

    <PropostaCatalogoForm :item="item" :tipos="tipos" />
  </div>
</template>

<script setup>
/** Proposta de item do catalogo - pagina propria do SPA. */
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PropostaCatalogoForm from '@/Components/Organisms/Resgate/PropostaCatalogoForm.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  item: { type: Object, default: null },
  tipos: { type: Array, default: () => [] },
});
</script>
