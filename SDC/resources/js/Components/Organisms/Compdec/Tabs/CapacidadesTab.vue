<template>
  <div class="capacidades-tab-layout">
    <!-- Capacidades Estruturais (booleanos diretos) -->
    <CardBase class="primary-card">
      <Heading level="3" class="mb-4">Capacidades Estruturais</Heading>
      <div class="capacidades-grid">
        <div
          v-for="cap in capacidadesEstruturais"
          :key="cap.key"
          class="capacidade-item"
          :class="cap.value ? 'is-true' : 'is-false'"
        >
          <component
            :is="cap.value ? CheckCircleIcon : XMarkIcon"
            class="w-5 h-5 flex-shrink-0"
            :class="cap.value ? 'text-emerald-500' : 'text-slate-400'"
          />
          <div class="flex-1">
            <Text :variant="cap.value ? 'bold' : 'default'">{{ cap.label }}</Text>
            <Text v-if="cap.description" variant="muted" size="sm" class="block">
              {{ cap.description }}
            </Text>
          </div>
        </div>
      </div>
    </CardBase>

    <!-- Capacitacao e Cursos -->
    <CardBase class="primary-card">
      <Heading level="3" class="mb-4">Capacitacao e Cursos</Heading>
      <div class="capacidades-grid">
        <div
          v-for="curso in cursos"
          :key="curso.key"
          class="capacidade-item"
          :class="curso.value ? 'is-true' : 'is-false'"
        >
          <component
            :is="curso.value ? CheckCircleIcon : XMarkIcon"
            class="w-5 h-5 flex-shrink-0"
            :class="curso.value ? 'text-emerald-500' : 'text-slate-400'"
          />
          <div class="flex-1">
            <Text :variant="curso.value ? 'bold' : 'default'">{{ curso.label }}</Text>
            <Text v-if="curso.data" variant="muted" size="sm" class="block">
              Realizado em {{ formatDate(curso.data) }}
            </Text>
          </div>
        </div>
      </div>
    </CardBase>

    <!-- NUPDEC Detalhes -->
    <CardBase v-if="capacidadesSoft.capacitacao_nupdec || capacidadesSoft.nupdec_org_representado" class="detail-card">
      <Heading level="3" class="mb-4">NUPDEC - Detalhes</Heading>
      <div class="info-grid">
        <div class="info-item" v-if="capacidadesSoft.capacitacao_nupdec">
          <Text variant="label">Capacitacao do NUPDEC</Text>
          <Text>{{ capacidadesSoft.capacitacao_nupdec }}</Text>
        </div>

        <div class="info-item" v-if="capacidadesSoft.nupdec_org_representado">
          <Text variant="label">Organizacao Representada</Text>
          <Text>{{ capacidadesSoft.nupdec_org_representado }}</Text>
        </div>
      </div>
    </CardBase>

    <!-- Experiencia em DC -->
    <CardBase v-if="capacidadesSoft.experiencia_dc || capacidadesSoft.tipo_experiencia_dc" class="detail-card">
      <Heading level="3" class="mb-4">Experiencia em Defesa Civil</Heading>
      <div class="info-grid">
        <div class="info-item" v-if="capacidadesSoft.experiencia_dc">
          <Text variant="label">Experiencia</Text>
          <Text>{{ capacidadesSoft.experiencia_dc }}</Text>
        </div>

        <div class="info-item" v-if="capacidadesSoft.tipo_experiencia_dc">
          <Text variant="label">Tipo de Experiencia</Text>
          <Text>{{ capacidadesSoft.tipo_experiencia_dc }}</Text>
        </div>
      </div>
    </CardBase>

    <!-- Observacoes -->
    <CardBase v-if="capacidadesSoft.obs_capacidades" class="full-card">
      <Heading level="3" class="mb-4">Observacoes</Heading>
      <Text class="whitespace-pre-line">{{ capacidadesSoft.obs_capacidades }}</Text>
    </CardBase>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
});

const capacidadesSoft = computed(() => props.orgao.capacidades || {});

const capacidadesEstruturais = computed(() => [
  {
    key: 'tem_sede_propria',
    label: 'Sede Propria',
    description: 'Possui imovel proprio para a coordenadoria',
    value: !!props.orgao.tem_sede_propria,
  },
  {
    key: 'tem_viatura',
    label: 'Viatura',
    description: 'Possui veiculo destinado a defesa civil',
    value: !!props.orgao.tem_viatura,
  },
  {
    key: 'tem_plano_contingencia',
    label: 'Plano de Contingencia',
    description: 'Plano de Contingencia vigente',
    value: !!props.orgao.tem_plano_contingencia,
  },
  {
    key: 'tem_mapeamento_risco',
    label: 'Mapeamento de Risco',
    description: 'Realiza mapeamento de areas de risco',
    value: !!props.orgao.tem_mapeamento_risco,
  },
  {
    key: 'tem_simulado',
    label: 'Realiza Simulados',
    description: 'Conduz exercicios e simulados',
    value: !!props.orgao.tem_simulado,
  },
  {
    key: 'tem_cartao_pdc',
    label: 'Cartao PDC',
    description: 'Possui Cartao de Protecao e Defesa Civil',
    value: !!props.orgao.tem_cartao_pdc,
  },
]);

const cursos = computed(() => [
  {
    key: 'tem_computador',
    label: 'Possui Computador',
    value: !!capacidadesSoft.value.tem_computador,
  },
  {
    key: 'tem_curso_gestao',
    label: 'Curso de Gestao em PDC',
    value: !!capacidadesSoft.value.tem_curso_gestao,
    data: capacidadesSoft.value.data_curso_gestao,
  },
  {
    key: 'tem_curso_sco',
    label: 'Curso SCO (Sistema de Comando)',
    value: !!capacidadesSoft.value.tem_curso_sco,
    data: capacidadesSoft.value.data_curso_sco,
  },
  {
    key: 'tem_workshop_pdc',
    label: 'Workshop PDC',
    value: !!capacidadesSoft.value.tem_workshop_pdc,
    data: capacidadesSoft.value.data_workshop_pdc,
  },
]);

function formatDate(value) {
  if (!value) return '';
  try {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('pt-BR');
  } catch (_e) {
    return value;
  }
}
</script>

<style scoped>
.capacidades-tab-layout {
  display: grid;
  gap: 1.5rem;
}

.capacidades-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 0.75rem;
}

.capacidade-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid;
  transition: all 0.15s ease;
}

.capacidade-item.is-true {
  @apply bg-emerald-50 dark:bg-emerald-500/5 border-emerald-200 dark:border-emerald-500/20;
}

.capacidade-item.is-false {
  @apply bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700/40;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

@media (min-width: 1280px) {
  .capacidades-tab-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .primary-card,
  .detail-card {
    grid-column: span 6;
  }

  .full-card {
    grid-column: span 12;
  }
}
</style>
