<template>
  <form @submit.prevent="handleSubmit" class="processo-form">
    <!-- Secao 1: Identificacao do Processo -->
    <FormSection
      title="Identificacao do Processo"
      subtitle="Tipificacao do desastre, localizacao e situacao de anormalidade"
      :icon="ClipboardDocumentListIcon"
      icon-variant="default"
      :cols="3"
    >
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
        placeholder="Selecione o codigo COBRADE..."
        required
        :error="form.errors.cobrade_id"
        :hint="cobradeHint"
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
        v-model="form.redec_id"
        label="REDEC"
        :options="redecs"
        placeholder="Selecione a REDEC..."
        required
        :error="form.errors.redec_id"
        :hint="redecHint"
      />

      <FormSelect
        v-model="form.municipio_id"
        label="Municipio"
        :options="municipiosFiltrados"
        :placeholder="municipioPlaceholder"
        required
        :error="form.errors.municipio_id"
        :hint="municipioHint"
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

    <!-- Secao 2: Status e Responsavel -->
    <FormSection
      title="Status e Responsavel"
      subtitle="Tramitacao do processo e protocolo FIDE"
      :icon="UserCircleIcon"
      icon-variant="purple"
      :cols="3"
    >
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

    <!-- Aviso: campos de decreto desabilitados quando status Registro -->
    <div
      v-if="isRegistro"
      class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/50 rounded-xl p-4 mb-4 flex items-start gap-3"
    >
      <div class="rat-section-icon rat-section-icon-warning">
        <InformationCircleIcon class="w-5 h-5" />
      </div>
      <div>
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
          Processo em Registro
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Os campos de decreto e reconhecimento nao precisam ser preenchidos enquanto o status for
          <strong>Registro</strong>. As datas de entrada e de ocorrencia continuam obrigatorias.
        </p>
      </div>
    </div>

    <!-- Secao 3: Decreto Municipal, Datas e Prazos (blocos unificados) -->
    <FormSection
      title="Decreto Municipal, Datas e Prazos"
      subtitle="A vigencia e calculada a partir da data de publicacao + prazo em dias"
      :icon="CalendarDaysIcon"
      icon-variant="warning"
      :cols="4"
    >
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
        :hint="dataOcorrenciaHint"
      />

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
        label="Data de Publicacao do Decreto"
        :disabled="isRegistro"
        :error="form.errors.data_publicacao_decreto_municipal"
        :hint="dataPublicacaoHint"
      />

      <FormField
        v-model="form.prazo_vigencia_decreto"
        label="Prazo de Vigencia (dias)"
        type="number"
        :placeholder="`Padrao: ${PRAZO_PADRAO_DIAS}`"
        :disabled="isRegistro"
        :error="form.errors.prazo_vigencia_decreto"
        :hint="prazoVigenciaHint"
      />

      <FormField
        :model-value="dataVencimentoFormatada"
        label="Data de Vencimento (calculada)"
        readonly
        :hint="dataVencimentoHint"
      />

      <FormField
        :model-value="diasRestantesTexto"
        label="Dias Restantes da Vigencia"
        readonly
        :hint="diasRestantesHint"
      />
    </FormSection>

    <!-- Secao 4: Reconhecimento Estadual -->
    <FormSection
      title="Reconhecimento Estadual"
      subtitle="Decreto estadual e publicacao no Diario Oficial de MG"
      :icon="BuildingLibraryIcon"
      icon-variant="success"
      :cols="4"
      collapsible
    >
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

    <!-- Secao 5: Reconhecimento Federal -->
    <FormSection
      title="Reconhecimento Federal"
      subtitle="Portaria federal e publicacao no Diario Oficial da Uniao"
      :icon="ShieldCheckIcon"
      icon-variant="success"
      :cols="4"
      collapsible
    >
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

    <!-- Secao 6: Informacoes Adicionais -->
    <FormSection
      title="Informacoes Adicionais"
      subtitle="Processo SEI e observacoes do analista"
      :icon="InformationCircleIcon"
      icon-variant="default"
      :cols="1"
    >
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

    <!-- Acoes do Formulario -->
    <div class="form-actions flex justify-end gap-3 mt-6">
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
import {
  BuildingLibraryIcon,
  CalendarDaysIcon,
  ClipboardDocumentListIcon,
  InformationCircleIcon,
  ShieldCheckIcon,
  UserCircleIcon,
} from '@heroicons/vue/24/outline';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import {
  PRAZO_PADRAO_DIAS,
  calcularDiasRestantes,
  calcularVencimento,
  formatarData,
  parseDataLocal,
  rotuloDiasRestantes,
  usouPrazoPadrao,
} from '@/Composables/decretacoes/useVigencia';

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

// =============================================================================
// COBRADE (padrao nacional) x Tipo de Desastre
// Os dois selects listam a mesma classificacao: "Tipo de Desastre" ordenado
// pelo nome e "COBRADE" numerado/ordenado pelo codigo (1.1.1.1.0, 1.1.1.2.0...).
// Manter os dois em sincronia evita enviar par contraditorio - no backend o
// cobrade_id e quem prevalece (ProcessoRequestDTO).
// =============================================================================

const cobradeSelecionado = computed(
  () => props.cobrades.find(c => String(c.id) === String(props.form.cobrade_id)) || null
);

const cobradeHint = computed(() => {
  const codigo = cobradeSelecionado.value?.cobrade;

  return codigo
    ? `Codigo COBRADE ${codigo} (classificacao nacional)`
    : 'Numerado pelo padrao nacional: grupo.subgrupo.tipo.subtipo';
});

watch(() => props.form.cobrade_id, (valor) => {
  if (valor && String(props.form.tipo_desastre_id) !== String(valor)) {
    props.form.tipo_desastre_id = valor;
  }
});

watch(() => props.form.tipo_desastre_id, (valor) => {
  if (valor && String(props.form.cobrade_id) !== String(valor)) {
    props.form.cobrade_id = valor;
  }
});

// =============================================================================
// Correspondencia municipio <-> REDEC nas listas suspensas
// Cada municipio vindo do backend carrega `redec_id`/`redec_sigla`
// (ProcessoFilter::getMunicipiosOptions, a partir de cedec_municipio).
// =============================================================================

const temMapeamentoRedec = computed(
  () => props.municipios.some(m => m.redec_id)
);

// TODOS os municipios de MG ficam sempre selecionaveis. A REDEC escolhida nao
// remove opcao nenhuma: apenas traz os municipios correspondentes para o topo
// da lista e anota a REDEC de cada um no rotulo.
const municipiosFiltrados = computed(() => {
  const redecId = props.form.redec_id;

  const comRotulo = props.municipios.map(m => (
    m.redec_sigla ? { ...m, label: `${m.label} - ${m.redec_sigla}` } : m
  ));

  if (!redecId || !temMapeamentoRedec.value) {
    return comRotulo;
  }

  const daRedec = [];
  const demais = [];

  for (const municipio of comRotulo) {
    (String(municipio.redec_id) === String(redecId) ? daRedec : demais).push(municipio);
  }

  return [...daRedec, ...demais];
});

const municipiosDaRedecCount = computed(() => {
  const redecId = props.form.redec_id;
  if (!redecId) return 0;

  return props.municipios.filter(m => String(m.redec_id) === String(redecId)).length;
});

const municipioSelecionado = computed(
  () => props.municipios.find(m => String(m.id) === String(props.form.municipio_id)) || null
);

const redecDoMunicipio = computed(() => municipioSelecionado.value?.redec_id ?? null);

const municipioPlaceholder = computed(() => 'Selecione o municipio...');

const municipioHint = computed(() => {
  const total = props.municipios.length;

  if (!props.form.redec_id || !temMapeamentoRedec.value || municipiosDaRedecCount.value === 0) {
    return total ? `${total} municipios de MG` : '';
  }

  const sigla = props.redecs.find(r => String(r.id) === String(props.form.redec_id))?.sigla
    ?? 'REDEC selecionada';

  return `${municipiosDaRedecCount.value} municipio(s) da ${sigla} no topo - os ${total} de MG seguem disponiveis`;
});

const redecHint = computed(() => {
  const redecMunicipio = redecDoMunicipio.value;
  if (!redecMunicipio) return '';

  const nome = municipioSelecionado.value?.label ?? 'municipio selecionado';

  if (String(redecMunicipio) === String(props.form.redec_id)) {
    return `Correspondente a ${nome}`;
  }

  const sigla = props.redecs.find(r => String(r.id) === String(redecMunicipio))?.sigla;

  return sigla ? `Atencao: ${nome} corresponde a ${sigla}` : '';
});

// Municipio escolhido define a REDEC (fonte da verdade da correspondencia).
watch(redecDoMunicipio, (redecId) => {
  if (redecId && String(props.form.redec_id) !== String(redecId)) {
    props.form.redec_id = redecId;
  }
});

// Trocar a REDEC nao mexe no municipio escolhido: todos os municipios de MG
// permanecem validos e a divergencia fica visivel no aviso abaixo do campo.

// =============================================================================
// Vigencia: data de vencimento e dias restantes derivados (nao ha campo manual
// de vencimento - a unica fonte e data de publicacao + prazo).
// =============================================================================

const prazoAssumido = computed(() => usouPrazoPadrao(props.form.prazo_vigencia_decreto));

const dataVencimento = computed(() => calcularVencimento(
  props.form.data_publicacao_decreto_municipal,
  props.form.prazo_vigencia_decreto,
));

const dataVencimentoFormatada = computed(() => (
  dataVencimento.value ? formatarData(dataVencimento.value) : '--'
));

const dataVencimentoHint = computed(() => {
  if (!props.form.data_publicacao_decreto_municipal) {
    return 'Informe a data de publicacao do decreto';
  }
  return prazoAssumido.value
    ? `Publicacao + ${PRAZO_PADRAO_DIAS} dias (prazo padrao)`
    : `Publicacao + ${props.form.prazo_vigencia_decreto} dias`;
});

const diasRestantes = computed(() => calcularDiasRestantes(
  props.form.data_publicacao_decreto_municipal,
  props.form.prazo_vigencia_decreto,
));

const diasRestantesTexto = computed(() => (
  diasRestantes.value === null ? '--' : String(diasRestantes.value)
));

const diasRestantesHint = computed(() => {
  if (diasRestantes.value === null) return 'Sem vigencia calculada';
  return rotuloDiasRestantes(diasRestantes.value);
});

const prazoVigenciaHint = computed(() => (
  prazoAssumido.value
    ? `Em branco assume ${PRAZO_PADRAO_DIAS} dias (padrao de SE/ECP)`
    : ''
));

// Ao informar a publicacao, deixa explicito o prazo padrao de 180 dias.
watch(() => props.form.data_publicacao_decreto_municipal, (valor) => {
  if (valor && usouPrazoPadrao(props.form.prazo_vigencia_decreto)) {
    props.form.prazo_vigencia_decreto = PRAZO_PADRAO_DIAS;
  }
});

// Coerencia entre as datas do bloco (avisos, nao bloqueiam o envio).
const dataOcorrenciaHint = computed(() => {
  const ocorrencia = parseDataLocal(props.form.data_ocorrencia);
  const entrada = parseDataLocal(props.form.data_entrada);

  if (ocorrencia && entrada && ocorrencia > entrada) {
    return 'Ocorrencia posterior a entrada do processo';
  }
  return '';
});

const dataPublicacaoHint = computed(() => {
  const publicacao = parseDataLocal(props.form.data_publicacao_decreto_municipal);
  const decreto = parseDataLocal(props.form.data_decreto_municipal);

  if (publicacao && decreto && publicacao < decreto) {
    return 'Publicacao anterior a data do decreto';
  }
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
