<template>
  <div class="geral-tab-layout">
    <!-- Identificacao -->
    <CardBase class="identificacao-card">
      <Heading level="3" class="mb-4">Identificacao</Heading>
      <div class="info-grid">
        <div class="info-item">
          <Text variant="label">Codigo</Text>
          <Text variant="mono">{{ orgao.codigo }}</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Nome</Text>
          <Text>{{ orgao.nome }}</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Tipo</Text>
          <TipoOrgaoBadge :tipo="orgao.tipo" />
        </div>

        <div class="info-item">
          <Text variant="label">Status</Text>
          <StatusOrgaoBadge :status="orgao.status" />
        </div>

        <div class="info-item" v-if="orgao.municipio">
          <Text variant="label">Municipio</Text>
          <Text>{{ orgao.municipio.nome }}{{ orgao.municipio.uf ? ' - ' + orgao.municipio.uf : '' }}</Text>
        </div>

        <div class="info-item" v-if="orgao.orgao_superior">
          <Text variant="label">Orgao Superior</Text>
          <Text>{{ orgao.orgao_superior.nome }} ({{ orgao.orgao_superior.codigo }})</Text>
        </div>
      </div>
    </CardBase>

    <!-- Atos Legais -->
    <CardBase class="atos-card">
      <Heading level="3" class="mb-4">Atos Legais</Heading>
      <div class="info-grid">
        <div class="info-item">
          <Text variant="label">Lei de Criacao</Text>
          <Text v-if="orgao.lei_criacao_numero || orgao.lei_criacao_data">
            {{ orgao.lei_criacao_numero || '-' }}
            <span v-if="orgao.lei_criacao_data" class="text-slate-500 dark:text-slate-400">
              ({{ formatDate(orgao.lei_criacao_data) }})
            </span>
          </Text>
          <Text v-else variant="muted">Nao informado</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Decreto</Text>
          <Text v-if="orgao.decreto_numero || orgao.decreto_data">
            {{ orgao.decreto_numero || '-' }}
            <span v-if="orgao.decreto_data" class="text-slate-500 dark:text-slate-400">
              ({{ formatDate(orgao.decreto_data) }})
            </span>
          </Text>
          <Text v-else variant="muted">Nao informado</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Portaria</Text>
          <Text v-if="orgao.portaria_numero || orgao.portaria_data">
            {{ orgao.portaria_numero || '-' }}
            <span v-if="orgao.portaria_data" class="text-slate-500 dark:text-slate-400">
              ({{ formatDate(orgao.portaria_data) }})
            </span>
          </Text>
          <Text v-else variant="muted">Nao informado</Text>
        </div>
      </div>
    </CardBase>

    <!-- Quantitativos -->
    <CardBase class="quantitativos-card">
      <Heading level="3" class="mb-4">Quantitativos</Heading>
      <div class="info-grid">
        <div class="info-item">
          <Text variant="label">Efetivo (Agentes Ativos)</Text>
          <Text variant="bold">{{ orgao.qtd_efetivo ?? 0 }}</Text>
        </div>

        <div class="info-item">
          <Text variant="label">NUPDECs (Nucleos Comunitarios)</Text>
          <Text variant="bold">{{ orgao.qtd_nupdec ?? 0 }}</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Membros da Equipe</Text>
          <Text variant="bold">{{ orgao.equipes_count ?? 0 }}</Text>
        </div>

        <div class="info-item">
          <Text variant="label">Anexos Cadastrados</Text>
          <Text variant="bold">{{ orgao.anexos_count ?? 0 }}</Text>
        </div>
      </div>
    </CardBase>

    <!-- Contato -->
    <CardBase class="contato-card">
      <Heading level="3" class="mb-4">Contato</Heading>
      <div class="info-grid">
        <div class="info-item" v-if="orgao.email">
          <Text variant="label">E-mail Principal</Text>
          <Text>{{ orgao.email }}</Text>
        </div>

        <div class="info-item" v-if="orgao.email_secundario">
          <Text variant="label">E-mail Secundario</Text>
          <Text>{{ orgao.email_secundario }}</Text>
        </div>

        <div class="info-item" v-if="orgao.email_terciario">
          <Text variant="label">E-mail Terciario</Text>
          <Text>{{ orgao.email_terciario }}</Text>
        </div>

        <div class="info-item" v-if="orgao.telefone">
          <Text variant="label">Telefone Principal</Text>
          <Text>{{ orgao.telefone }}</Text>
        </div>

        <div class="info-item" v-if="orgao.telefone_secundario">
          <Text variant="label">Telefone Secundario</Text>
          <Text>{{ orgao.telefone_secundario }}</Text>
        </div>

        <div class="info-item" v-if="orgao.fax">
          <Text variant="label">Fax</Text>
          <Text>{{ orgao.fax }}</Text>
        </div>

        <div class="info-item full-width" v-if="orgao.endereco">
          <Text variant="label">Endereco</Text>
          <Text>{{ orgao.endereco }}</Text>
        </div>
      </div>

      <Text v-if="!hasContato" variant="muted" class="block">Nenhuma informacao de contato cadastrada.</Text>
    </CardBase>

    <!-- Responsavel (Coordenador) -->
    <CardBase v-if="orgao.responsavel_nome || orgao.responsavel_email" class="responsavel-card">
      <Heading level="3" class="mb-4">Responsavel</Heading>
      <div class="info-grid">
        <div class="info-item" v-if="orgao.responsavel_nome">
          <Text variant="label">Nome</Text>
          <Text>{{ orgao.responsavel_nome }}</Text>
        </div>

        <div class="info-item" v-if="orgao.responsavel_cpf">
          <Text variant="label">CPF</Text>
          <Text variant="mono">{{ orgao.responsavel_cpf }}</Text>
        </div>

        <div class="info-item" v-if="orgao.responsavel_email">
          <Text variant="label">E-mail</Text>
          <Text>{{ orgao.responsavel_email }}</Text>
        </div>

        <div class="info-item" v-if="orgao.responsavel_telefone">
          <Text variant="label">Telefone</Text>
          <Text>{{ orgao.responsavel_telefone }}</Text>
        </div>
      </div>
    </CardBase>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusOrgaoBadge from '@/Components/Molecules/Compdec/StatusOrgaoBadge.vue';
import TipoOrgaoBadge from '@/Components/Molecules/Compdec/TipoOrgaoBadge.vue';

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
});

const hasContato = computed(() => {
  return !!(props.orgao.email
    || props.orgao.email_secundario
    || props.orgao.email_terciario
    || props.orgao.telefone
    || props.orgao.telefone_secundario
    || props.orgao.fax
    || props.orgao.endereco);
});

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
.geral-tab-layout {
  display: grid;
  gap: 1.5rem;
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

.full-width {
  grid-column: 1 / -1;
}

@media (min-width: 1280px) {
  .geral-tab-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .identificacao-card,
  .contato-card,
  .responsavel-card {
    grid-column: span 12;
  }

  .atos-card {
    grid-column: span 5;
  }

  .quantitativos-card {
    grid-column: span 7;
  }
}
</style>
