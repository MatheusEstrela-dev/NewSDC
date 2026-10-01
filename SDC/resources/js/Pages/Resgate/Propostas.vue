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
        <Link :href="route('resgate.catalogo')" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
          Voltar ao catálogo
        </Link>
      </template>
    </PageHeader>

    <PropostasPendentes :propostas="pendentes" :nomes="nomes" :pode-aprovar="podeAprovar" :usuario-id="usuarioId" />
  </div>
</template>

<script setup>
/** Fila de propostas do catalogo - pagina propria do SPA (quatro olhos). */
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
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
