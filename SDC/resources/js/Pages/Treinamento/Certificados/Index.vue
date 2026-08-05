<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import CertificadosList from '@/Components/Organisms/Treinamento/CertificadosList.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamento: { type: Object, required: true },
  certificados: { type: Object, required: true },
});

const { can } = usePermissions();
const { show: toast } = useToast();

const pagination = computed(() => {
  const m = props.certificados?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('treinamentos.certificados.index', props.treinamento.id), {
    data: { page },
    preserveState: true,
  });
}

function reemitir(certificado) {
  router.post(route('treinamentos.certificados.reemitir', certificado.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast('Certificado reemitido.', 'success'),
    onError: () => toast('Não foi possível reemitir o certificado.', 'error'),
  });
}
</script>

<template>
  <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <Link :href="route('treinamentos.show', treinamento.id)" class="mb-4 inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Voltar para {{ treinamento.titulo }}
    </Link>

    <CardBase class="p-6 mb-6">
      <Heading :level="1" class="text-xl font-bold mb-1">Certificados</Heading>
      <Text size="sm" color="muted">{{ treinamento.titulo }}</Text>
    </CardBase>

    <CardBase class="p-6">
      <CertificadosList
        :certificados="certificados.data"
        :can-reemitir="can('treinamento.certificados.reemitir')"
        @reemitir="reemitir"
      />

      <div v-if="pagination" class="mt-6">
        <Pagination :pagination="pagination" @page-change="irParaPagina" />
      </div>
    </CardBase>
  </div>
</template>
