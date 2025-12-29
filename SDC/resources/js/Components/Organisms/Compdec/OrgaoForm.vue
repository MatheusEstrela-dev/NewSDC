<template>
  <form @submit.prevent="handleSubmit" class="orgao-form">
    <div class="form-grid">
      <!-- Tipo de Órgão -->
      <SelectInput
        v-model="formData.tipo"
        label="Tipo de Órgão *"
        :options="[
          { value: 'compdec', label: 'COMPDEC - Coordenadoria Municipal' },
          { value: 'redec', label: 'REDEC - Regional' },
          { value: 'cedec', label: 'CEDEC - Estadual' }
        ]"
        :error="errors.tipo"
        required
      />

      <!-- Código -->
      <TextInput
        v-model="formData.codigo"
        label="Código"
        placeholder="Ex: COMPDEC-MG-001"
        :error="errors.codigo"
        help="Deixe em branco para gerar automaticamente"
      />

      <!-- Nome -->
      <TextInput
        v-model="formData.nome"
        label="Nome *"
        placeholder="Ex: COMPDEC de Belo Horizonte"
        :error="errors.nome"
        required
        class="full-width"
      />

      <!-- Status -->
      <SelectInput
        v-model="formData.status"
        label="Status *"
        :options="[
          { value: 'ativo', label: 'Ativo' },
          { value: 'inativo', label: 'Inativo' },
          { value: 'em_implantacao', label: 'Em Implantação' },
          { value: 'suspenso', label: 'Suspenso' }
        ]"
        :error="errors.status"
        required
      />

      <!-- Município (apenas para COMPDEC) -->
      <SelectInput
        v-if="formData.tipo === 'compdec'"
        v-model="formData.municipio_id"
        label="Município *"
        :options="municipioOptions"
        :error="errors.municipio_id"
        required
      />

      <!-- Órgão Superior -->
      <SelectInput
        v-model="formData.orgao_superior_id"
        label="Órgão Superior"
        :options="orgaoSuperiorOptions"
        :error="errors.orgao_superior_id"
      />
    </div>

    <!-- Seção: Contato -->
    <div class="form-section">
      <Heading level="3">Informações de Contato</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.email"
          label="E-mail"
          type="email"
          placeholder="contato@compdec.mg.gov.br"
          :error="errors.email"
        />

        <TextInput
          v-model="formData.telefone"
          label="Telefone"
          placeholder="(31) 3333-3333"
          :error="errors.telefone"
        />

        <TextInput
          v-model="formData.endereco"
          label="Endereço"
          placeholder="Rua, número, bairro"
          :error="errors.endereco"
          class="full-width"
        />
      </div>
    </div>

    <!-- Seção: Responsável -->
    <div class="form-section">
      <Heading level="3">Dados do Responsável</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.responsavel_nome"
          label="Nome do Responsável"
          placeholder="Nome completo"
          :error="errors.responsavel_nome"
        />

        <TextInput
          v-model="formData.responsavel_cpf"
          label="CPF do Responsável"
          placeholder="000.000.000-00"
          :error="errors.responsavel_cpf"
        />

        <TextInput
          v-model="formData.responsavel_email"
          label="E-mail do Responsável"
          type="email"
          placeholder="responsavel@email.com"
          :error="errors.responsavel_email"
        />

        <TextInput
          v-model="formData.responsavel_telefone"
          label="Telefone do Responsável"
          placeholder="(31) 99999-9999"
          :error="errors.responsavel_telefone"
        />
      </div>
    </div>

    <!-- Ações -->
    <div class="form-actions">
      <Button
        type="button"
        variant="outline"
        @click="handleCancel"
      >
        Cancelar
      </Button>

      <Button
        type="submit"
        variant="primary"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : (isEditing ? 'Atualizar' : 'Criar Órgão') }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  orgaosSuperior: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const municipioOptions = computed(() => [
  { value: '', label: 'Selecione um município' },
  ...props.municipios.map(m => ({
    value: m.id,
    label: `${m.nome} - ${m.uf}`,
  })),
]);

const orgaoSuperiorOptions = computed(() => {
  const options = [{ value: '', label: 'Nenhum (órgão de topo)' }];

  // Filtrar apenas órgãos que podem ser superiores ao tipo atual
  const validOptions = props.orgaosSuperior
    .filter(orgao => {
      if (props.formData.tipo === 'compdec') return orgao.tipo === 'redec';
      if (props.formData.tipo === 'redec') return orgao.tipo === 'cedec';
      return false; // CEDEC não tem superior
    })
    .map(orgao => ({
      value: orgao.id,
      label: `${orgao.nome} (${orgao.codigo})`,
    }));

  return [...options, ...validOptions];
});

const handleSubmit = () => {
  emit('submit', props.formData);
};

const handleCancel = () => {
  emit('cancel');
};
</script>

<style scoped>
.orgao-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.full-width {
  grid-column: 1 / -1;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}
</style>
