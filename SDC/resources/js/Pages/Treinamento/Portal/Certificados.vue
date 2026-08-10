<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
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
      <CardBase v-for="certificado in certificados.data" :key="certificado.id" class="p-5 flex items-center justify-between">
        <div>
          <Text size="base" class="font-semibold">{{ certificado.treinamento_titulo }}</Text>
          <Text size="xs" color="muted">
            {{ certificado.emitido_em ? new Date(certificado.emitido_em).toLocaleDateString('pt-BR') : 'Aguardando emissão' }}
          </Text>
        </div>
        <div class="flex items-center gap-3">
          <Badge :color="certificado.status_color">{{ certificado.status_label }}</Badge>
          <a
            v-if="certificado.disponivel"
            :href="route('portal.treinamento.certificados.imprimir', certificado.id)"
            target="_blank"
            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            Ver / Imprimir
          </a>
        </div>
      </CardBase>

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
