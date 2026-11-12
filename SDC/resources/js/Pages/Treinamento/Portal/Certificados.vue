<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CertificadoItem from '@/Components/Molecules/Treinamento/CertificadoItem.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  certificados: { type: Object, required: true },
});

const pagination = computed(() => {
  const m = props.certificados?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 10,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('portal.treinamento.certificados.index'), { data: { page }, preserveState: true });
}
</script>

<template>
  <div>
    <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">Meus Certificados</Heading>

    <div class="space-y-4">
      <CertificadoItem
        v-for="certificado in certificados.data"
        :key="certificado.id"
        :certificado="certificado"
        :imprimir-url="route('portal.treinamento.certificados.imprimir', certificado.id)"
      />

      <CardBase v-if="certificados.data.length === 0" class="p-8 text-center">
        <Text size="sm" color="muted">Nenhum certificado disponível ainda. Conclua um curso ou evento para receber o seu.</Text>
        <Link :href="route('portal.treinamento.catalogo')" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
          Ver catálogo
        </Link>
      </CardBase>
    </div>

    <div v-if="pagination" class="mt-6">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>
  </div>
</template>
