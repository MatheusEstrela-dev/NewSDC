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
  inscricoes: { type: Object, required: true },
});

const pagination = computed(() => {
  const m = props.inscricoes?.meta;
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
  router.visit(route('portal.treinamento.inscricoes.index'), { data: { page }, preserveState: true });
}

const formatDate = (dateValue) => {
  if (!dateValue) return '—';
  const d = new Date(dateValue);
  if (isNaN(d.getTime())) return dateValue;
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};
</script>

<template>
  <div>
    <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">Minhas Inscrições</Heading>

    <div class="space-y-4">
      <CardBase v-for="inscricao in inscricoes.data" :key="inscricao.id" class="p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <Text size="base" class="font-semibold">{{ inscricao.treinamento_titulo }}</Text>
            <Text size="sm" color="muted">
              {{ formatDate(inscricao.treinamento_data_inicio) }}
              <span v-if="inscricao.treinamento_local"> · {{ inscricao.treinamento_local }}</span>
            </Text>
          </div>
          <Badge :color="inscricao.status_color">{{ inscricao.status_label }}</Badge>
        </div>

        <div class="mt-3 flex items-center gap-4">
          <Text size="xs" color="muted">Frequência: {{ inscricao.percentual_frequencia?.toFixed(0) ?? 0 }}%</Text>
          <a
            v-if="inscricao.certificado?.disponivel"
            :href="route('portal.treinamento.certificados.imprimir', inscricao.certificado.id)"
            target="_blank"
            class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            Ver certificado
          </a>
        </div>
      </CardBase>

      <CardBase v-if="inscricoes.data.length === 0" class="p-8 text-center">
        <Text size="sm" color="muted">Você ainda não se inscreveu em nenhum curso ou evento.</Text>
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
