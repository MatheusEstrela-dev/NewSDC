<template>
  <Head title="Propostas do catálogo" />

  <div class="pb-6">
    <PageHeader
      title="Propostas do catálogo"
      description="Mudanças no catálogo só são publicadas com a aprovação de outra pessoa."
      :icon="CheckBadgeIcon"
      :icon-image="moduleIcon('ranking')"
      variant="gradient"
    >
      <template #actions>
        <Button :href="route('resgate.catalogo')" variant="sky" :icon="GiftIcon">Voltar ao catálogo</Button>
      </template>
    </PageHeader>

    <PropostasPendentes :propostas="pendentes" :nomes="nomes" :pode-aprovar="podeAprovar" :usuario-id="usuarioId" />
  </div>
</template>

<script setup>
/** Fila de propostas do catalogo - pagina propria do SPA (quatro olhos). */
import { Head } from '@inertiajs/vue3';
import { GiftIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PropostasPendentes from '@/Components/Organisms/Resgate/PropostasPendentes.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  pendentes: { type: Array, default: () => [] },
  nomes: { type: Object, default: () => ({}) },
  podeAprovar: { type: Boolean, default: false },
  usuarioId: { type: Number, default: 0 },
});
</script>
