<template>
  <div :class="editMode ? 'prefeitura-tab-edit' : 'prefeitura-tab-layout'">
    <!-- Modo de visualizacao -->
    <template v-if="!editMode">
      <!-- Foto + Dados do Prefeito -->
      <CardBase class="prefeito-card">
        <div class="flex justify-between items-start mb-4 gap-4">
          <Heading level="3">Prefeito</Heading>
          <Button
            v-if="canEdit"
            variant="outline"
            size="sm"
            :icon="PencilIcon"
            @click="enableEdit"
          >
            Editar Prefeitura
          </Button>
        </div>

        <div v-if="!prefeitura" class="empty-state">
          <Text variant="muted">Nenhum dado de prefeitura cadastrado.</Text>
          <Button
            v-if="canEdit"
            variant="primary"
            size="sm"
            class="mt-3"
            @click="enableEdit"
          >
            Cadastrar Prefeitura
          </Button>
        </div>

        <div v-else class="prefeitura-content">
          <div v-if="prefeitura.foto_url" class="prefeitura-foto">
            <img :src="prefeitura.foto_url" alt="Foto do Prefeito" class="foto-img" />
          </div>

          <div class="info-grid flex-1">
            <div class="info-item" v-if="prefeitura.prefeito_nome">
              <Text variant="label">Nome</Text>
              <Text variant="bold">{{ prefeitura.prefeito_nome }}</Text>
            </div>

            <div class="info-item" v-if="prefeitura.prefeito_email">
              <Text variant="label">E-mail</Text>
              <Text>{{ prefeitura.prefeito_email }}</Text>
            </div>

            <div class="info-item" v-if="prefeitura.prefeito_telefone">
              <Text variant="label">Telefone</Text>
              <Text>{{ prefeitura.prefeito_telefone }}</Text>
            </div>

            <div class="info-item" v-if="prefeitura.prefeito_celular">
              <Text variant="label">Celular</Text>
              <Text>{{ prefeitura.prefeito_celular }}</Text>
            </div>
          </div>
        </div>
      </CardBase>

      <!-- Endereco -->
      <CardBase v-if="hasEndereco" class="endereco-card">
        <Heading level="3" class="mb-4">Endereco</Heading>
        <div class="info-grid">
          <div class="info-item full-width" v-if="prefeitura.endereco">
            <Text variant="label">Logradouro</Text>
            <Text>{{ prefeitura.endereco }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.bairro">
            <Text variant="label">Bairro</Text>
            <Text>{{ prefeitura.bairro }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.cep">
            <Text variant="label">CEP</Text>
            <Text variant="mono">{{ prefeitura.cep }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.latitude">
            <Text variant="label">Latitude</Text>
            <Text variant="mono">{{ prefeitura.latitude }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.longitude">
            <Text variant="label">Longitude</Text>
            <Text variant="mono">{{ prefeitura.longitude }}</Text>
          </div>
        </div>
      </CardBase>

      <!-- INSS -->
      <CardBase v-if="prefeitura && prefeitura.inss_tem_cobranca" class="inss-card">
        <Heading level="3" class="mb-4">INSS</Heading>
        <div class="info-grid">
          <div class="info-item">
            <Text variant="label">Cobranca</Text>
            <StatusOrgaoBadge status="ativo" />
          </div>

          <div class="info-item" v-if="prefeitura.inss_aliquota">
            <Text variant="label">Aliquota</Text>
            <Text variant="bold">{{ formatPercent(prefeitura.inss_aliquota) }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.inss_lei_cobranca">
            <Text variant="label">Lei de Cobranca</Text>
            <Text>{{ prefeitura.inss_lei_cobranca }}</Text>
          </div>

          <div class="info-item" v-if="prefeitura.inss_responsavel">
            <Text variant="label">Responsavel</Text>
            <Text>{{ prefeitura.inss_responsavel }}</Text>
          </div>
        </div>
      </CardBase>
    </template>

    <!-- Modo de edicao -->
    <CardBase v-else class="prefeitura-form-card">
      <Heading level="3" class="mb-4">
        {{ prefeitura ? 'Editar Prefeitura' : 'Cadastrar Prefeitura' }}
      </Heading>
      <PrefeituraForm
        :form-data="formData"
        :errors="errors"
        :loading="loading"
        @submit="handleSubmit"
        @cancel="cancelEdit"
      />
    </CardBase>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import StatusOrgaoBadge from '@/Components/Molecules/Compdec/StatusOrgaoBadge.vue';
import PrefeituraForm from '@/Components/Organisms/Compdec/PrefeituraForm.vue';

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
  prefeitura: {
    type: Object,
    default: null,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const editMode = ref(false);
const loading = ref(false);

const formData = reactive({
  prefeito_nome: props.prefeitura?.prefeito_nome || '',
  prefeito_email: props.prefeitura?.prefeito_email || '',
  prefeito_telefone: props.prefeitura?.prefeito_telefone || '',
  prefeito_celular: props.prefeitura?.prefeito_celular || '',
  endereco: props.prefeitura?.endereco || '',
  bairro: props.prefeitura?.bairro || '',
  cep: props.prefeitura?.cep || '',
  latitude: props.prefeitura?.latitude || '',
  longitude: props.prefeitura?.longitude || '',
  inss_tem_cobranca: props.prefeitura?.inss_tem_cobranca || false,
  inss_aliquota: props.prefeitura?.inss_aliquota || '',
  inss_lei_cobranca: props.prefeitura?.inss_lei_cobranca || '',
  inss_responsavel: props.prefeitura?.inss_responsavel || '',
});

const hasEndereco = computed(() => {
  return !!(props.prefeitura && (
    props.prefeitura.endereco
    || props.prefeitura.bairro
    || props.prefeitura.cep
    || props.prefeitura.latitude
    || props.prefeitura.longitude
  ));
});

function enableEdit() {
  editMode.value = true;
}

function cancelEdit() {
  editMode.value = false;
}

function handleSubmit(payload) {
  loading.value = true;
  router.post(
    route('compdec.prefeitura.upsert', props.orgao.id),
    { _method: 'put', ...payload },
    {
      preserveScroll: true,
      onSuccess: () => {
        editMode.value = false;
      },
      onFinish: () => {
        loading.value = false;
      },
    },
  );
}

function formatPercent(value) {
  if (value === null || value === undefined || value === '') return '-';
  return `${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
}
</script>

<style scoped>
.prefeitura-tab-layout,
.prefeitura-tab-edit {
  display: grid;
  gap: 1.5rem;
}

.prefeitura-content {
  display: flex;
  gap: 1.5rem;
  align-items: flex-start;
  flex-wrap: wrap;
}

.prefeitura-foto {
  flex-shrink: 0;
}

.foto-img {
  width: 120px;
  height: 120px;
  border-radius: 0.75rem;
  object-fit: cover;
  border: 2px solid;
  @apply border-slate-200 dark:border-slate-700;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
  min-width: 0;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.full-width {
  grid-column: 1 / -1;
}

.empty-state {
  padding: 1.5rem 0;
  text-align: center;
}

@media (min-width: 1280px) {
  .prefeitura-tab-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .prefeito-card {
    grid-column: span 7;
  }

  .endereco-card {
    grid-column: span 5;
  }

  .inss-card,
  .prefeitura-form-card {
    grid-column: span 12;
  }
}
</style>
