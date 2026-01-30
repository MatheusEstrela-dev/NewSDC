<template>
  <div
    class="relative rounded-xl border backdrop-blur-sm p-4 sm:p-5 cursor-pointer transition-all duration-300 touch-manipulation group"
    :class="getCardClasses()"
    @click="$emit('click')"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-3 sm:mb-4">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 sm:gap-2 mb-1.5 sm:mb-2 flex-wrap">
          <Badge
            :variant="getTipoCadastroVariant(beneficiario.tipo_cadastro)"
            size="sm"
          >
            {{ beneficiario.tipo_cadastro }}
          </Badge>
          <Badge
            :variant="getStatusVariant(beneficiario.status)"
            size="sm"
          >
            {{ beneficiario.status }}
          </Badge>
        </div>
        <Heading :level="4" color="default" class="mb-0.5 sm:mb-1 text-sm sm:text-base truncate">
          {{ beneficiario.nome_responsavel }}
        </Heading>
        <Text size="sm" color="muted" class="text-xs sm:text-sm">
          CPF: {{ beneficiario.cpf || 'Não informado' }}
        </Text>
      </div>
      <Badge
        v-if="beneficiario.abrigo_atual_id"
        variant="info"
        size="sm"
        class="flex-shrink-0"
      >
        Em Abrigo
      </Badge>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 gap-2 sm:gap-4 mb-3 sm:mb-4">
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Contato</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm truncate">
          {{ beneficiario.telefone || '—' }}
        </Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Município</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm truncate">
          {{ beneficiario.municipio?.nome || '—' }}
        </Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Endereço</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm truncate">
          {{ getEnderecoCurto(beneficiario) }}
        </Text>
      </div>
      <div class="min-w-0">
        <Text size="xs" color="muted" class="mb-0.5 sm:mb-1 text-xs">Membros Família</Text>
        <Text size="sm" weight="medium" class="text-xs sm:text-sm">
          {{ beneficiario.membros_familia_count || 0 }}
        </Text>
      </div>
    </div>

    <!-- Situações de Vulnerabilidade -->
    <div v-if="beneficiario.situacoes_vulnerabilidade?.length" class="mb-3 sm:mb-4">
      <Text size="xs" color="muted" class="mb-1.5 sm:mb-2">Vulnerabilidades:</Text>
      <div class="flex flex-wrap gap-1.5 sm:gap-2">
        <span
          v-for="situacao in beneficiario.situacoes_vulnerabilidade.slice(0, 3)"
          :key="situacao"
          class="px-2 py-0.5 sm:py-1 rounded bg-orange-500/10 text-xs text-orange-400"
        >
          {{ formatSituacao(situacao) }}
        </span>
        <span
          v-if="beneficiario.situacoes_vulnerabilidade.length > 3"
          class="px-2 py-0.5 sm:py-1 rounded bg-slate-700/30 text-xs text-slate-400"
        >
          +{{ beneficiario.situacoes_vulnerabilidade.length - 3 }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-1 sm:gap-2 pt-3 sm:pt-4 border-t border-slate-700/30 dark:border-slate-700/30 border-slate-200">
      <TableActions
        :show-view="true"
        :show-print="true"
        :show-edit="canEdit"
        :show-attachments="false"
        :show-delete="canDelete"
        @view="$emit('view', beneficiario.id)"
        @print="$emit('print', beneficiario.id)"
        @edit="$emit('edit', beneficiario.id)"
        @delete="$emit('delete', beneficiario.id)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import Text from '../../Atoms/Typography/Text.vue';
import Badge from '../../Atoms/Badge/Badge.vue';
import TableActions from '../Table/TableActions.vue';

const props = defineProps({
  beneficiario: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: true,
  },
  canDelete: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['click', 'view', 'print', 'edit', 'delete']);

// Glow neon effect baseado no status
const getCardClasses = () => {
  const baseClasses = 'bg-slate-900/60 dark:bg-slate-900/60 bg-white hover:scale-[1.02] active:scale-[0.98]';

  // Classe CSS customizada com efeito glow baseado no status
  const statusGlow = {
    'ATIVO': 'beneficiario-card-ativo',
    'INATIVO': 'beneficiario-card-inativo',
    'DESLOCADO': 'beneficiario-card-deslocado',
    'RETORNADO': 'beneficiario-card-retornado',
  };

  const glowClass = statusGlow[props.beneficiario.status] || 'beneficiario-card-inativo';

  return `${baseClasses} ${glowClass}`;
};

const getTipoCadastroVariant = (tipo) => {
  const variants = {
    'FAMILIAR': 'primary',
    'INDIVIDUAL': 'secondary',
  };
  return variants[tipo] || 'default';
};

const getStatusVariant = (status) => {
  const variants = {
    'ATIVO': 'success',
    'INATIVO': 'default',
    'DESLOCADO': 'warning',
    'RETORNADO': 'info',
  };
  return variants[status] || 'default';
};

const getEnderecoCurto = (beneficiario) => {
  if (beneficiario.endereco_rua) {
    return `${beneficiario.endereco_rua}${beneficiario.endereco_numero ? ', ' + beneficiario.endereco_numero : ''}`;
  }
  return '—';
};

const formatSituacao = (situacao) => {
  const labels = {
    'IDOSO': 'Idoso',
    'CRIANCA': 'Criança',
    'GESTANTE': 'Gestante',
    'DEFICIENCIA': 'PcD',
    'DOENCA_CRONICA': 'Doença Crônica',
    'VULNERABILIDADE_SOCIAL': 'Vulnerabilidade Social',
  };
  return labels[situacao] || situacao;
};
</script>
