<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import AvaliacaoCursoCard from '@/Components/Organisms/Treinamento/AvaliacaoCursoCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  inscricoes: { type: Object, required: true },
  salas: { type: Object, default: () => ({}) },
  /** Por id da inscricao: eventos (sem sala) em que falta so avaliar para concluir. */
  avaliacoes_pendentes: { type: Object, default: () => ({}) },
  notas_avaliacao: { type: Array, default: () => [] },
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
          <img
            v-if="inscricao.treinamento_capa_url"
            :src="inscricao.treinamento_capa_url"
            :alt="`Capa do curso: ${inscricao.treinamento_titulo}`"
            class="h-16 w-28 shrink-0 rounded-md object-cover"
            loading="lazy"
          />
          <div class="min-w-0 flex-1">
            <Text size="base" class="font-semibold">{{ inscricao.treinamento_titulo }}</Text>
            <Text size="sm" color="muted">
              {{ formatDate(inscricao.treinamento_data_inicio) }}
              <span v-if="inscricao.treinamento_local"> · {{ inscricao.treinamento_local }}</span>
            </Text>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <Badge v-if="inscricao.treinamento_situacao && inscricao.treinamento_situacao !== 'ATIVO'" :variant="inscricao.treinamento_situacao_variant" size="sm">
              {{ inscricao.treinamento_situacao === 'DESATIVADO' ? 'Encerrado' : inscricao.treinamento_situacao_label }}
            </Badge>
            <Badge :color="inscricao.status_color">{{ inscricao.status_label }}</Badge>
          </div>
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
          <Link
            v-if="salas[inscricao.id]?.avaliacao_pendente"
            :href="salas[inscricao.id].sala_url"
            class="text-xs font-semibold text-amber-700 hover:underline dark:text-amber-400"
          >
            Avalie o curso para concluir ({{ Math.round(salas[inscricao.id].percentual) }}%)
          </Link>
          <Link
            v-else-if="salas[inscricao.id]"
            :href="salas[inscricao.id].sala_url"
            class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
          >
            Abrir curso ({{ Math.round(salas[inscricao.id].percentual) }}%)
          </Link>
          <Text v-if="avaliacoes_pendentes[inscricao.id]" size="xs" weight="semibold" color="warning">
            Avalie o curso para concluir
          </Text>
        </div>

        <!-- Evento sem sala com os requisitos cumpridos: a avaliacao e a etapa final. -->
        <div v-if="avaliacoes_pendentes[inscricao.id]" class="mt-4">
          <AvaliacaoCursoCard
            :url="avaliacoes_pendentes[inscricao.id].url_avaliar"
            :notas="notas_avaliacao"
            subtitulo="Você cumpriu os requisitos. Avalie o curso para concluí-lo e receber o certificado."
          />
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
