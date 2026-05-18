<template>
  <form @submit.prevent="handleSubmit" class="processo-form">
    <fieldset :disabled="viewOnly" style="border: none; padding: 0; margin: 0;">
    <!-- Secao 1: Identificacao do Processo -->
    <FormSection title="Identificacao do Processo" :cols="3">
      <FormSelect
        v-model="form.tipo_desastre_id"
        label="Tipo de Desastre"
        :options="tiposDesastre"
        placeholder="Selecione o tipo..."
        required
        :error="form.errors.tipo_desastre_id"
      />

      <FormSelect
        v-model="form.cobrade_id"
        label="COBRADE"
        :options="cobrades"
        placeholder="Selecione o COBRADE..."
        required
        :error="form.errors.cobrade_id"
      />

      <FormSelect
        v-model="form.origem"
        label="Origem"
        :options="origensOptions"
        placeholder="Selecione a origem..."
        required
        :error="form.errors.origem"
      />

      <FormSelect
        v-model="form.municipio_id"
        label="Municipio"
        :options="municipios"
        placeholder="Selecione o municipio..."
        required
        :error="form.errors.municipio_id"
      />

      <FormSelect
        v-model="form.redec_id"
        label="REDEC"
        :options="redecs"
        placeholder="Selecione a REDEC..."
        required
        :error="form.errors.redec_id"
      />

      <RadioGroup
        v-model="form.situacao_anormalidade"
        name="situacao_anormalidade"
        label="Situacao de Anormalidade"
        :options="filteredSituacaoOptions"
        :disabled="isRegistro"
        required
        :error="form.errors.situacao_anormalidade"
      />
    </FormSection>

    <!-- Secao 2: Datas e Prazos -->
    <FormSection title="Datas e Prazos" :cols="4">
      <FormDateField
        v-model="form.data_entrada"
        label="Data de Entrada do Processo"
        required
        :error="form.errors.data_entrada"
      />

      <FormDateField
        v-model="form.data_ocorrencia"
        label="Data de Ocorrencia do Desastre"
        required
        :error="form.errors.data_ocorrencia"
      />

      <FormDateField
        v-model="form.data_vencimento_decreto"
        label="Data Vencimento do Decreto"
        required
        :error="form.errors.data_vencimento_decreto"
      />

      <FormField
        :model-value="diasRestantes"
        label="Dias Restantes da Vigencia"
        readonly
        :hint="diasRestantesHint"
      />
    </FormSection>

    <!-- Secao 3: Status e Responsavel -->
    <FormSection title="Status e Responsavel" :cols="3">
      <FormSelect
        v-model="form.status"
        label="Status do Processo"
        :options="statusOptions"
        placeholder="Selecione o status..."
        required
        :error="form.errors.status"
      />

      <FormSelect
        v-model="form.analista_id"
        label="Analista Responsavel"
        :options="analistas"
        placeholder="Selecione o analista..."
        :error="form.errors.analista_id"
      />

      <FormField
        v-model="protocoloFideModel"
        label="N. Protocolo FIDE"
        :placeholder="protocoloFidePlaceholder"
        :error="protocoloFideError"
        :maxlength="protocoloFideMaxLength"
        @focus="initProtocoloFide"
        @blur="protocoloFideTouched = true"
      />
    </FormSection>

    <!-- Aviso: campos desabilitados quando status Registro -->
    <div v-if="isRegistro" class="bg-teal-800/50 border border-teal-600 rounded-lg p-4 flex items-start gap-3">
      <svg class="w-5 h-5 text-teal-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
      </svg>
      <p class="text-teal-200 text-sm">
        Os campos abaixo nao precisam ser preenchidos para processos com status
        <strong>Registro</strong>. Serao habilitados quando o status for alterado.
      </p>
    </div>

    <!-- Secao 4: Decreto Municipal -->
    <FormSection title="Decreto Municipal" :cols="4" collapsible>
      <FormField
        v-model="form.n_decreto_municipal"
        label="N. Decreto Municipal"
        placeholder="Ex: 1234/2024"
        :disabled="isRegistro"
        :error="form.errors.n_decreto_municipal"
      />

      <FormDateField
        v-model="form.data_decreto_municipal"
        label="Data do Decreto Municipal"
        :disabled="isRegistro"
        :error="form.errors.data_decreto_municipal"
      />

      <FormDateField
        v-model="form.data_publicacao_decreto_municipal"
        label="Data Publicacao do Decreto"
        :disabled="isRegistro"
        :error="form.errors.data_publicacao_decreto_municipal"
      />

      <FormField
        v-model="form.prazo_vigencia_decreto"
        label="Prazo Vigencia (dias)"
        type="number"
        placeholder="Ex: 180"
        :disabled="isRegistro"
        :error="form.errors.prazo_vigencia_decreto"
      />
    </FormSection>

    <!-- Secao 5: Reconhecimento Estadual -->
    <FormSection title="Reconhecimento Estadual" :cols="4" collapsible>
      <FormField
        v-model="form.n_decreto_estadual"
        label="N. Decreto Estadual"
        placeholder="Ex: 47.123"
        :disabled="isRegistro"
        :error="form.errors.n_decreto_estadual"
      />

      <FormDateField
        v-model="form.data_decreto_estadual"
        label="Data do Decreto Estadual"
        :disabled="isRegistro"
        :error="form.errors.data_decreto_estadual"
      />

      <FormField
        v-model="form.n_edicao_domg"
        label="N. Edicao DOMG"
        placeholder="Ex: 12345"
        :disabled="isRegistro"
        :error="form.errors.n_edicao_domg"
      />

      <FormDateField
        v-model="form.data_publicacao_domg"
        label="Data Publicacao DOMG"
        :disabled="isRegistro"
        :error="form.errors.data_publicacao_domg"
      />
    </FormSection>

    <!-- Secao 6: Reconhecimento Federal -->
    <FormSection title="Reconhecimento Federal" :cols="4" collapsible>
      <FormField
        v-model="form.n_portaria_federal"
        label="N. Portaria Federal"
        placeholder="Ex: 123/2024"
        :disabled="isRegistro"
        :error="form.errors.n_portaria_federal"
      />

      <FormDateField
        v-model="form.data_portaria_federal"
        label="Data da Portaria Federal"
        :disabled="isRegistro"
        :error="form.errors.data_portaria_federal"
      />

      <FormField
        v-model="form.n_edicao_dou"
        label="N. Edicao DOU"
        placeholder="Ex: 456"
        :disabled="isRegistro"
        :error="form.errors.n_edicao_dou"
      />

      <FormDateField
        v-model="form.data_publicacao_dou"
        label="Data Publicacao DOU"
        :disabled="isRegistro"
        :error="form.errors.data_publicacao_dou"
      />
    </FormSection>

    <!-- Secao 7: Informacoes Adicionais -->
    <FormSection title="Informacoes Adicionais" :cols="1">
      <FormField
        v-model="form.n_processo_sei"
        label="N. Processo SEI"
        placeholder="Ex: SEI-1234.5678.9012"
        required
        :error="form.errors.n_processo_sei"
      />

      <FormTextarea
        v-model="form.observacoes"
        label="Observacoes"
        placeholder="Insira observacoes relevantes sobre o processo..."
        :rows="4"
        :error="form.errors.observacoes"
      />
    </FormSection>

    </fieldset>

    <!-- Acoes do Formulario -->
    <div v-if="!viewOnly" class="form-actions flex justify-end gap-3 mt-6">
      <Button
        type="button"
        variant="secondary"
        :disabled="form.processing"
        @click="handleCancel"
      >
        Cancelar
      </Button>
      <Button
        type="submit"
        variant="primary"
        :loading="form.processing"
        :disabled="form.processing"
      >
        {{ submitLabel }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  tiposDesastre: {
    type: Array,
    default: () => [],
  },
  cobrades: {
    type: Array,
    default: () => [],
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  redecs: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  analistas: {
    type: Array,
    default: () => [],
  },
  submitLabel: {
    type: String,
    default: 'Salvar Processo',
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const origensOptions = [
  { value: 'municipal', label: 'Municipal' },
  { value: 'estadual', label: 'Estadual' },
];

const situacaoOptions = [
  { value: 'N1', label: 'N1 - Nivel 1' },
  { value: 'SE', label: 'SE - Situacao de Emergencia' },
  { value: 'ECP', label: 'ECP - Estado de Calamidade Publica' },
];

const isRegistro = computed(() => props.form.status === 'Registro');

const filteredSituacaoOptions = computed(() => {
  if (isRegistro.value) {
    return situacaoOptions.filter(opt => opt.value === 'N1');
  }
  return situacaoOptions;
});

watch(() => props.form.status, (newVal) => {
  if (newVal === 'Registro') {
    props.form.situacao_anormalidade = 'N1';
  }
});

const diasRestantes = computed(() => {
  if (!props.form.data_vencimento_decreto) return '--';
  const hoje = new Date();
  const vencimento = new Date(props.form.data_vencimento_decreto);
  const diff = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
  return diff.toString();
});

const diasRestantesHint = computed(() => {
  const dias = parseInt(diasRestantes.value);
  if (isNaN(dias)) return '';
  if (dias < 0) return 'Prazo vencido';
  if (dias <= 15) return 'Prazo proximo ao vencimento';
  return '';
});


const PREFIXO = 'MG-F-';
const protocoloFideTouched = ref(false);

const protocoloFidePlaceholder = computed(() => {
  return props.form.origem === 'estadual'
    ? 'MG-F-31-14120-20251110'
    : 'MG-F-3136520-14120-20251110';
});

const protocoloFideMaxLength = computed(() => {
  return props.form.origem === 'estadual' ? 22 : 27;
});

function aplicarMascara(nums, isEstadual) {
  if (isEstadual) {
    if (nums.length <= 2) return nums;
    if (nums.length <= 7) return nums.replace(/^(\d{2})(\d+)/, '$1-$2');
    return nums.replace(/^(\d{2})(\d{1,5})(\d+)/, '$1-$2-$3');
  } else {
    if (nums.length <= 7) return nums;
    if (nums.length <= 12) return nums.replace(/^(\d{7})(\d+)/, '$1-$2');
    return nums.replace(/^(\d{7})(\d{1,5})(\d+)/, '$1-$2-$3');
  }
}

const protocoloFideModel = computed({
  get() {
    return props.form.n_protocolo_fide;
  },
  set(val) {
    if (!val || val.length < PREFIXO.length) {
      props.form.n_protocolo_fide = PREFIXO;
      return;
    }

    const isEstadual = props.form.origem === 'estadual';
    const MAX_DIGITS = isEstadual ? 15 : 20;
    const MAX_TOTAL = isEstadual ? 22 : 27;

    let nums = val.replace(/^MG-?F?-?/i, '').replace(/\D/g, '');
    nums = nums.substring(0, MAX_DIGITS);

    let result = PREFIXO + aplicarMascara(nums, isEstadual);
    props.form.n_protocolo_fide = result.substring(0, MAX_TOTAL);
  },
});

function initProtocoloFide() {
  if (!props.form.n_protocolo_fide) {
    props.form.n_protocolo_fide = PREFIXO;
  }
}

watch(() => props.form.origem, () => {
  protocoloFideTouched.value = false;
  if (props.form.n_protocolo_fide && props.form.n_protocolo_fide !== PREFIXO) {
    const isEstadual = props.form.origem === 'estadual';
    const MAX_DIGITS = isEstadual ? 15 : 20;
    const MAX_TOTAL = isEstadual ? 22 : 27;
    let nums = props.form.n_protocolo_fide.replace(/^MG-?F?-?/i, '').replace(/\D/g, '');
    nums = nums.substring(0, MAX_DIGITS);
    let result = PREFIXO + aplicarMascara(nums, isEstadual);
    props.form.n_protocolo_fide = result.substring(0, MAX_TOTAL);
  }
});

const REGEX_MUNICIPAL = /^MG-F-\d{7}-\d{4,5}-\d{8}$/;
const REGEX_ESTADUAL = /^MG-F-\d{2}-\d{4,5}-\d{8}$/;

const protocoloFideError = computed(() => {
  const valor = props.form.n_protocolo_fide;
  if (!protocoloFideTouched.value || !valor || valor === PREFIXO) return '';

  const isEstadual = props.form.origem === 'estadual';

  if (isEstadual) {
    return REGEX_ESTADUAL.test(valor)
      ? ''
      : 'Formato invalido. Ex: MG-F-31-14120-20251110';
  }
  return REGEX_MUNICIPAL.test(valor)
    ? ''
    : 'Formato invalido. Ex: MG-F-3136520-14120-20251110';
});

function handleSubmit() {
  if (protocoloFideError.value) return;
  emit('submit', props.form);
}

function handleCancel() {
  emit('cancel');
}
</script>

<style scoped>
.processo-form {
  @apply space-y-4;
}
</style>
