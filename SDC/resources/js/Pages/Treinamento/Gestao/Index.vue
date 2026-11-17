<script setup>
/**
 * Landing da "Área do Gestor" (Sidebar, issue #86): lista todo curso (nao so
 * EAD, diferente de "Meus cursos" do professor) para quem tem
 * treinamento.gestor.acessar escolher qual gerenciar.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import StatusExibicaoCursoBadge from '@/Components/Molecules/Treinamento/StatusExibicaoCursoBadge.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamentos: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
});

const busca = ref(props.filters.search ?? '');
const tipoFiltro = ref(props.filters.tipo ?? '');

const cursos = computed(() => props.treinamentos?.data ?? []);
const paginacao = computed(() => {
  const m = props.treinamentos?.meta;
  if (!m) return null;
  return { current_page: m.current_page, last_page: m.last_page, per_page: m.per_page, total: m.total, from: m.from, to: m.to };
});

function aplicarFiltros(extra = {}) {
  router.get(route('treinamentos.gestor.index'), {
    search: busca.value || undefined,
    tipo: tipoFiltro.value || undefined,
    ...extra,
  }, { preserveState: true, replace: true });
}
</script>

<template>
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      title="Área do Gestor"
      description="Turmas, vagas, período, cronograma e histórico de cada curso"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <CardBase padding="md">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <TextInput v-model="busca" placeholder="Buscar por título" @keyup.enter="aplicarFiltros()" />
        <SelectInput
          v-model="tipoFiltro"
          :options="(filterOptions.tipos || []).map((t) => ({ value: t.value, label: t.label }))"
          placeholder="Todas as modalidades"
          @change="aplicarFiltros()"
        />
        <Button variant="secondary" size="sm" @click="aplicarFiltros()">Filtrar</Button>
      </div>
    </CardBase>

    <CardBase v-if="cursos.length === 0" padding="md">
      <Text color="muted">Nenhum curso encontrado.</Text>
    </CardBase>

    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <CardBase v-for="curso in cursos" :key="curso.id" padding="md" hover>
        <div class="flex items-start justify-between gap-2">
          <Text size="base" class="font-semibold">{{ curso.titulo }}</Text>
          <Badge :color="curso.tipo_color || 'blue'" size="sm">{{ curso.tipo_label }}</Badge>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2">
          <Badge v-if="curso.situacao && curso.situacao !== 'ATIVO'" :variant="curso.situacao_variant" size="sm">
            {{ curso.situacao_label }}
          </Badge>
          <StatusExibicaoCursoBadge v-else :color="curso.status_exibicao_color" :label="curso.status_exibicao_label" size="sm" />
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700/60">
          <Text size="xs" color="muted">
            <span v-if="curso.numero_vagas">{{ curso.vagas_disponiveis }} de {{ curso.numero_vagas }} vagas</span>
            <span v-else>Vagas ilimitadas</span>
          </Text>
          <Button size="sm" :href="route('treinamentos.gestor.show', curso.id)">Gerenciar</Button>
        </div>
      </CardBase>
    </div>

    <Pagination v-if="paginacao" :pagination="paginacao" @page-change="(page) => aplicarFiltros({ page })" />
  </div>
</template>
