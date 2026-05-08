<template>
  <Head :title="data.nome" />

  <div class="cisternas-show">
    <PageHeader :title="data.nome">
      <template #actions>
        <Button variant="ghost" :icon="ArrowLeftIcon" @click="handleBack">Voltar</Button>
        <Button v-if="canManage" variant="secondary" :icon="PencilIcon" @click="handleEdit">
          Editar
        </Button>
        <Button v-if="canDelete" variant="danger" :icon="TrashIcon" @click="handleDelete">
          Excluir
        </Button>
      </template>
    </PageHeader>

    <div class="meta-row mb-6">
      <Text variant="muted">Codigo: <Text variant="mono">{{ data.codigo }}</Text></Text>
      <TipoCisternaBadge :tipo="data.tipo" />
      <StatusCisternaBadge :status="data.status" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <CardBase>
        <Heading level="3" class="mb-4">Identificacao</Heading>
        <div class="info-grid">
          <InfoItem label="Municipio" :value="municipioLabel" />
          <InfoItem label="Capacidade" :value="capacidadeLabel" />
          <InfoItem label="Data instalacao" :value="formatDate(data.data_instalacao)" />
        </div>
      </CardBase>

      <CardBase>
        <Heading level="3" class="mb-4">Localizacao</Heading>
        <div class="info-grid">
          <InfoItem label="Endereco" :value="data.endereco || '-'" full-width />
          <InfoItem label="Latitude" :value="data.latitude || '-'" mono />
          <InfoItem label="Longitude" :value="data.longitude || '-'" mono />
        </div>
      </CardBase>

      <CardBase>
        <Heading level="3" class="mb-4">Responsavel</Heading>
        <div class="info-grid">
          <InfoItem label="Nome" :value="data.responsavel_nome || '-'" />
          <InfoItem label="Telefone" :value="data.responsavel_telefone || '-'" />
        </div>
      </CardBase>

      <CardBase v-if="data.observacoes">
        <Heading level="3" class="mb-4">Observacoes</Heading>
        <Text>{{ data.observacoes }}</Text>
      </CardBase>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import StatusCisternaBadge from '@/Components/Molecules/Cisterna/StatusCisternaBadge.vue';
import TipoCisternaBadge from '@/Components/Molecules/Cisterna/TipoCisternaBadge.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cisterna: { type: Object, required: true },
  canManage: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

const data = computed(() => props.cisterna?.data ?? props.cisterna ?? {});

const municipioLabel = computed(() => {
  const m = data.value.municipio;
  if (!m) return '-';
  return `${m.nome ?? '-'}${m.uf ? ' - ' + m.uf : ''}`;
});

const capacidadeLabel = computed(() => {
  if (!data.value.capacidade_litros) return '-';
  return `${Number(data.value.capacidade_litros).toLocaleString('pt-BR')} L`;
});

function formatDate(iso) {
  if (!iso) return '-';
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
}

function handleBack() {
  router.visit(route('cisternas.index'));
}

function handleEdit() {
  router.visit(route('cisternas.edit', data.value.id));
}

function handleDelete() {
  if (!confirm(`Excluir cisterna "${data.value.nome}"?`)) return;
  router.delete(route('cisternas.destroy', data.value.id));
}

// Inline molecule
const InfoItem = {
  props: ['label', 'value', 'mono', 'fullWidth'],
  setup(p) {
    return () => h('div', { class: ['info-item', p.fullWidth ? 'full-width' : ''] }, [
      h(Text, { variant: 'label' }, () => p.label),
      h(Text, { variant: p.mono ? 'mono' : 'bold' }, () => p.value),
    ]);
  },
};
</script>

<style scoped>
.cisternas-show {
  max-width: 1100px;
  margin: 0 auto;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

:deep(.info-item) {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

:deep(.full-width) {
  grid-column: 1 / -1;
}
</style>
