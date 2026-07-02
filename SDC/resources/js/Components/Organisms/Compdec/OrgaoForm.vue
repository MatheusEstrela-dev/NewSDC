<template>
  <form @submit.prevent="handleSubmit" class="orgao-form">
    <!-- Identificacao -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Identificacao</Heading>
      <div class="form-grid">
        <SelectInput
          v-model="formData.tipo"
          label="Tipo de Orgao *"
          :options="[
            { value: 'compdec', label: 'COMPDEC - Coordenadoria Municipal' },
            { value: 'redec', label: 'REDEC - Regional' },
            { value: 'cedec', label: 'CEDEC - Estadual' }
          ]"
          :error="errors.tipo"
          required
        />

        <TextInput
          v-model="formData.codigo"
          label="Codigo"
          placeholder="Ex: COMPDEC-MG-001"
          :error="errors.codigo"
          help="Deixe em branco para gerar automaticamente"
        />

        <TextInput
          v-model="formData.nome"
          label="Nome *"
          placeholder="Ex: COMPDEC de Belo Horizonte"
          :error="errors.nome"
          required
          class="full-width"
        />

        <SelectInput
          v-model="formData.status"
          label="Status *"
          :options="[
            { value: 'ativo', label: 'Ativo' },
            { value: 'inativo', label: 'Inativo' },
            { value: 'em_implantacao', label: 'Em Implantacao' },
            { value: 'suspenso', label: 'Suspenso' }
          ]"
          :error="errors.status"
          required
        />

        <SelectInput
          v-if="formData.tipo === 'compdec'"
          v-model="formData.municipio_id"
          label="Municipio *"
          :options="municipioOptions"
          :error="errors.municipio_id"
          required
        />

        <SelectInput
          v-model="formData.orgao_superior_id"
          label="Orgao Superior"
          :options="orgaoSuperiorOptions"
          :error="errors.orgao_superior_id"
        />
      </div>
    </div>

    <!-- Atos Legais -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Atos Legais</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.lei_criacao_numero"
          label="Lei de Criacao - Numero"
          placeholder="Ex: 1234/2005"
          :error="errors.lei_criacao_numero"
        />

        <DatePicker
          v-model="formData.lei_criacao_data"
          :error="!!errors.lei_criacao_data"
        />

        <TextInput
          v-model="formData.decreto_numero"
          label="Decreto - Numero"
          placeholder="Ex: 57/2005"
          :error="errors.decreto_numero"
        />

        <DatePicker
          v-model="formData.decreto_data"
          :error="!!errors.decreto_data"
        />

        <TextInput
          v-model="formData.portaria_numero"
          label="Portaria - Numero"
          placeholder="Ex: 640/2024"
          :error="errors.portaria_numero"
        />

        <DatePicker
          v-model="formData.portaria_data"
          :error="!!errors.portaria_data"
        />
      </div>
    </div>

    <!-- Quantitativos -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Quantitativos</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.qtd_efetivo"
          label="Efetivo (Agentes Ativos)"
          type="number"
          min="0"
          placeholder="0"
          :error="errors.qtd_efetivo"
        />

        <TextInput
          v-model="formData.qtd_nupdec"
          label="Quantidade de NUPDECs"
          type="number"
          min="0"
          placeholder="0"
          :error="errors.qtd_nupdec"
        />
      </div>
    </div>

    <!-- Contato -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Informacoes de Contato</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.email"
          label="E-mail Principal"
          type="email"
          placeholder="contato@compdec.mg.gov.br"
          :error="errors.email"
        />

        <TextInput
          v-model="formData.email_secundario"
          label="E-mail Secundario"
          type="email"
          :error="errors.email_secundario"
        />

        <TextInput
          v-model="formData.email_terciario"
          label="E-mail Terciario"
          type="email"
          :error="errors.email_terciario"
        />

        <TextInput
          v-model="formData.telefone"
          label="Telefone Principal"
          placeholder="(31) 3333-3333"
          :error="errors.telefone"
        />

        <TextInput
          v-model="formData.telefone_secundario"
          label="Telefone Secundario"
          placeholder="(31) 3333-3333"
          :error="errors.telefone_secundario"
        />

        <TextInput
          v-model="formData.fax"
          label="Fax"
          placeholder="(31) 3333-3333"
          :error="errors.fax"
        />

        <TextInput
          v-model="formData.endereco"
          label="Endereco"
          placeholder="Rua, numero, bairro"
          :error="errors.endereco"
          class="full-width"
        />
      </div>
    </div>

    <!-- Capacidades Estruturais -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Capacidades Estruturais</Heading>
      <Text variant="muted" size="sm" class="block mb-4">
        Marque os recursos disponiveis no orgao.
      </Text>
      <div class="capacidades-grid">
        <ToggleField
          v-model="formData.tem_sede_propria"
          label="Sede Propria"
          description="Possui imovel proprio"
        />
        <ToggleField
          v-model="formData.tem_viatura"
          label="Viatura"
          description="Veiculo destinado a defesa civil"
        />
        <ToggleField
          v-model="formData.tem_mapeamento_risco"
          label="Mapeamento de Risco"
          description="Mapeia areas de risco"
        />
        <ToggleField
          v-model="formData.tem_simulado"
          label="Realiza Simulados"
          description="Conduz simulados periodicos"
        />
        <ToggleField
          v-model="formData.tem_cartao_pdc"
          label="Cartao PDC"
          description="Possui Cartao de Protecao e Defesa Civil"
        />
      </div>
    </div>

    <!-- Capacitacao e Cursos (capacidades soft) -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Capacitacao e Cursos</Heading>
      <Text variant="muted" size="sm" class="block mb-4">
        Informacoes sobre capacitacao da equipe e cursos realizados.
      </Text>

      <div class="capacidades-grid mb-4">
        <ToggleField
          v-model="formData.capacidades.tem_computador"
          label="Possui Computador"
        />
        <ToggleField
          v-model="formData.capacidades.tem_curso_gestao"
          label="Curso de Gestao em PDC"
        />
        <ToggleField
          v-model="formData.capacidades.tem_curso_sco"
          label="Curso SCO"
          description="Sistema de Comando de Operacoes"
        />
        <ToggleField
          v-model="formData.capacidades.tem_workshop_pdc"
          label="Workshop PDC"
        />
      </div>

      <div class="form-grid">
        <DatePicker
          v-if="formData.capacidades.tem_curso_gestao"
          v-model="formData.capacidades.data_curso_gestao"
          :error="!!errors['capacidades.data_curso_gestao']"
        />

        <DatePicker
          v-if="formData.capacidades.tem_curso_sco"
          v-model="formData.capacidades.data_curso_sco"
          :error="!!errors['capacidades.data_curso_sco']"
        />

        <DatePicker
          v-if="formData.capacidades.tem_workshop_pdc"
          v-model="formData.capacidades.data_workshop_pdc"
          :error="!!errors['capacidades.data_workshop_pdc']"
        />

        <TextInput
          v-model="formData.capacidades.experiencia_dc"
          label="Experiencia em Defesa Civil"
          placeholder="Resumo da experiencia"
          :error="errors['capacidades.experiencia_dc']"
        />

        <TextInput
          v-model="formData.capacidades.tipo_experiencia_dc"
          label="Tipo de Experiencia"
          :error="errors['capacidades.tipo_experiencia_dc']"
        />

        <TextInput
          v-model="formData.capacidades.capacitacao_nupdec"
          label="Capacitacao do NUPDEC"
          :error="errors['capacidades.capacitacao_nupdec']"
        />

        <TextInput
          v-model="formData.capacidades.nupdec_org_representado"
          label="NUPDEC - Organizacao Representada"
          :error="errors['capacidades.nupdec_org_representado']"
          class="full-width"
        />
      </div>
    </div>

    <!-- Responsavel (Coordenador) -->
    <div class="form-section">
      <Heading level="3" class="mb-3">Responsavel</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.responsavel_nome"
          label="Nome"
          placeholder="Nome completo"
          :error="errors.responsavel_nome"
        />

        <TextInput
          v-model="formData.responsavel_cpf"
          label="CPF"
          placeholder="000.000.000-00"
          :error="errors.responsavel_cpf"
        />

        <TextInput
          v-model="formData.responsavel_email"
          label="E-mail"
          type="email"
          placeholder="responsavel@email.com"
          :error="errors.responsavel_email"
        />

        <TextInput
          v-model="formData.responsavel_telefone"
          label="Telefone"
          placeholder="(31) 99999-9999"
          :error="errors.responsavel_telefone"
        />
      </div>
    </div>

    <!-- Acoes -->
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
        {{ loading ? 'Salvando...' : (isEditing ? 'Atualizar' : 'Criar Orgao') }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

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
  { value: '', label: 'Selecione um municipio' },
  ...props.municipios.map(m => ({
    value: m.id,
    label: `${m.nome}${m.uf ? ' - ' + m.uf : ''}`,
  })),
]);

const orgaoSuperiorOptions = computed(() => {
  const options = [{ value: '', label: 'Nenhum (orgao de topo)' }];

  const validOptions = props.orgaosSuperior
    .filter(orgao => {
      if (props.formData.tipo === 'compdec') return orgao.tipo === 'redec';
      if (props.formData.tipo === 'redec') return orgao.tipo === 'cedec';
      return false;
    })
    .map(orgao => ({
      value: orgao.id,
      label: `${orgao.nome} (${orgao.codigo})`,
    }));

  return [...options, ...validOptions];
});

function handleSubmit() {
  emit('submit', props.formData);
}

function handleCancel() {
  emit('cancel');
}
</script>

<style scoped>
.orgao-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid;
  @apply bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700/50;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.25rem;
}

.full-width {
  grid-column: 1 / -1;
}

.capacidades-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 0.75rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid;
  @apply border-slate-200 dark:border-slate-700/50;
}
</style>
