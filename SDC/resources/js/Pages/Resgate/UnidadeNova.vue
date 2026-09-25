<template>
  <Head :title="`Cadastrar unidade · ${item.titulo}`" />

  <div class="pb-6">
    <PageHeader
      :title="`Cadastrar unidade · ${item.titulo}`"
      description="Cada unidade é um bem físico com patrimônio próprio. Não pode ser apagada; sai do catálogo por baixa."
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    />

    <UnidadeBemForm :item="item" />
  </div>
</template>

<script setup>
/** Cadastro de unidade de bem permanente - pagina propria do SPA. */
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import UnidadeBemForm from '@/Components/Organisms/Resgate/UnidadeBemForm.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  item: { type: Object, required: true },
});
</script>
