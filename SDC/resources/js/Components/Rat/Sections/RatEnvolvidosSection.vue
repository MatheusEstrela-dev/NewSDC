<template>
  <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-purple">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados do Envolvido</h3>
          <p class="text-xs text-slate-500 mt-0.5">Informações pessoais, documentação e contato</p>
        </div>
      </div>

      <div class="rat-section-content">

        <!-- === Dados Pessoais === -->
        <div class="space-y-4">
          <h4 class="subsection-label">Dados Pessoais</h4>
          <div class="rat-grid-3">
            <FormSelect
              label="Tipo de Envolvimento"
              v-model="localData.g_tipo_pessoa"
              :options="tipoEnvolvimentoOptions"
              required
            />
            <FormField label="Nome Completo" v-model="localData.p_nome_completo" placeholder="Nome da pessoa" required />
            <FormField label="Nome Social" v-model="localData.p_nome_social" placeholder="Se houver" />
          </div>
          <div class="rat-grid-4">
            <FormField label="CPF" v-model="localData.p_cpf" mask="cpf" />
            <div>
              <label class="form-label">Data de Nascimento</label>
              <DatePicker v-model="localData.p_data_nascimento" />
            </div>
            <FormSelect label="Sexo" v-model="localData.p_sexo" :options="sexoOptions" />
            <FormSelect label="Grau de Lesão" v-model="localData.g_lesao_grau" :options="lesaoOptions" />
          </div>
          <div class="rat-grid-3">
            <FormField label="Nome da Mãe" v-model="localData.p_nome_mae" />
            <FormField label="Nome do Pai" v-model="localData.p_nome_pai" />
            <FormField label="Ocupação Atual" v-model="localData.p_ocupacao_atual" />
          </div>
          <div class="rat-grid-3">
            <FormSelect label="Escolaridade" v-model="localData.p_escolaridade" :options="escolaridadeOptions" />
            <FormSelect label="Estado Civil" v-model="localData.p_estado_civil" :options="estadoCivilOptions" />
            <FormSelect label="Etnia / Cor" v-model="localData.p_cor_raca" :options="corRacaOptions" />
          </div>
          <div class="rat-grid-3">
            <FormSelect label="Orientação Sexual" v-model="localData.p_orientacao_sexual" :options="orientacaoSexualOptions" />
            <FormSelect label="Identidade de Gênero" v-model="localData.p_identidade_genero" :options="identidadeGeneroOptions" />
            <FormField label="Nacionalidade" v-model="localData.p_nacionalidade" />
          </div>
          <div class="rat-grid-3">
            <FormSelect label="País de Origem" v-model="localData.p_pais_origem" :options="paisOptions" />
            <FormSelect label="Naturalidade / UF" v-model="localData.p_naturalidade_uf" :options="ufOptions" />
            <div>
              <label class="form-label">Turista</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label">
                  <input type="radio" :value="true" v-model="localData.p_turista" class="radio-input" />
                  Sim
                </label>
                <label class="radio-label">
                  <input type="radio" :value="false" v-model="localData.p_turista" class="radio-input" />
                  Não
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- === Documentação de Identificação === -->
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
          <h4 class="subsection-label">Documentação de Identificação</h4>
          <div class="rat-grid-4">
            <FormSelect label="Tipo de Documento" v-model="localData.p_tipo" :options="tipoDocOptions" />
            <FormField label="Número do Documento" v-model="localData.p_numero" />
            <FormField label="Órgão Expedidor" v-model="localData.p_orgao_expedidor" />
            <FormSelect label="UF Expedidora" v-model="localData.p_uf" :options="ufOptions" />
          </div>
        </div>

        <!-- === Endereço === -->
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
          <h4 class="subsection-label">Endereço</h4>
          <div class="rat-grid-4">
            <div>
              <label class="form-label">CEP</label>
              <div class="flex gap-1.5">
                <input
                  type="text"
                  :value="localData.p_end_cep"
                  @input="localData.p_end_cep = applyCepMask($event.target.value)"
                  maxlength="9"
                  placeholder="00000-000"
                  class="cep-input flex-1 min-w-0"
                />
                <button type="button" @click="buscarEnderecoCep" class="cep-btn" title="Buscar CEP">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </button>
              </div>
            </div>
            <FormSelect label="País" v-model="localData.p_end_pais" :options="paisOptions" />
            <FormSelect label="Estado / UF" v-model="localData.p_end_estado_uf" :options="ufOptions" />
            <FormField label="Município" v-model="localData.p_end_municipio" />
          </div>
          <div class="rat-grid-3">
            <FormField label="Bairro" v-model="localData.p_end_bairro" />
            <FormField label="Logradouro" v-model="localData.p_end_logradouro" />
            <FormField label="Número" v-model="localData.p_end_numero" />
          </div>
          <div class="rat-grid-3">
            <FormField label="Complemento" v-model="localData.p_end_complemento" />
            <FormField label="KM" type="number" v-model="localData.p_end_km" />
            <FormField label="Código IBGE" v-model="localData.p_end_ibge" />
          </div>
        </div>

        <!-- === Contato === -->
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
          <h4 class="subsection-label">Contato</h4>
          <div class="rat-grid-3">
            <FormField label="Telefone Residencial" v-model="localData.p_telefone_residencial" mask="phone" />
            <FormField label="Telefone Comercial" v-model="localData.p_telefone_comercial" mask="phone" />
            <FormField label="E-mail" v-model="localData.p_email" type="email" />
          </div>
          <FormField label="Motivo de Ausência de Contato" v-model="localData.p_motivo_ausencia_contato" type="textarea" />
        </div>

        <!-- === Informações Adicionais === -->
        <div class="pt-5 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
          <h4 class="subsection-label">Informações Adicionais</h4>
          <div class="rat-grid-2">
            <div>
              <label class="form-label">Militar / Policial em Serviço</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label">
                  <input type="radio" :value="true" v-model="localData.g_envolvido_presenca" class="radio-input" />
                  Sim
                </label>
                <label class="radio-label">
                  <input type="radio" :value="false" v-model="localData.g_envolvido_presenca" class="radio-input" />
                  Não
                </label>
              </div>
            </div>
            <div>
              <label class="form-label">Situação de Rua</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label">
                  <input type="radio" :value="true" v-model="localData.p_situacao_rua" class="radio-input" />
                  Sim
                </label>
                <label class="radio-label">
                  <input type="radio" :value="false" v-model="localData.p_situacao_rua" class="radio-input" />
                  Não
                </label>
                <label class="radio-label">
                  <input type="radio" :value="null" v-model="localData.p_situacao_rua" class="radio-input" />
                  Não informado
                </label>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </fieldset>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useCep } from '@/Composables/location';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';
import DatePicker from '../../Form/DatePicker.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  viewOnly:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const mv = props.modelValue ?? {};

const localData = ref({
  id: mv.id || Date.now(),
  // Dados Gerais
  g_tipo_pessoa:        mv.g_tipo_pessoa        || mv.tipo_envolvimento || '',
  g_lesao_grau:         mv.g_lesao_grau         || '',
  // Dados Pessoais
  p_nome_completo:      mv.p_nome_completo      || mv.nome             || '',
  p_nome_social:        mv.p_nome_social        || '',
  p_cpf:                mv.p_cpf                || mv.cpf              || '',
  p_data_nascimento:    mv.p_data_nascimento    || mv.data_nascimento  || '',
  p_sexo:               mv.p_sexo               || mv.sexo             || '',
  p_nome_mae:           mv.p_nome_mae           || '',
  p_nome_pai:           mv.p_nome_pai           || '',
  p_ocupacao_atual:     mv.p_ocupacao_atual     || '',
  p_escolaridade:       mv.p_escolaridade       || '',
  p_estado_civil:       mv.p_estado_civil       || '',
  p_cor_raca:           mv.p_cor_raca           || '',
  p_orientacao_sexual:  mv.p_orientacao_sexual  || '',
  p_identidade_genero:  mv.p_identidade_genero  || '',
  p_nacionalidade:      mv.p_nacionalidade      || '',
  p_pais_origem:        mv.p_pais_origem        || '',
  p_naturalidade_uf:    mv.p_naturalidade_uf    || '',
  p_turista:            mv.p_turista            ?? null,
  // Documentação
  p_tipo:               mv.p_tipo               || '',
  p_numero:             mv.p_numero             || mv.rg               || '',
  p_orgao_expedidor:    mv.p_orgao_expedidor    || '',
  p_uf:                 mv.p_uf                 || '',
  // Endereço
  p_end_cep:            mv.p_end_cep            || mv.cep              || '',
  p_end_pais:           mv.p_end_pais           || '',
  p_end_estado_uf:      mv.p_end_estado_uf      || mv.uf               || '',
  p_end_municipio:      mv.p_end_municipio      || mv.municipio        || '',
  p_end_bairro:         mv.p_end_bairro         || mv.bairro           || '',
  p_end_logradouro:     mv.p_end_logradouro     || mv.endereco         || '',
  p_end_numero:         mv.p_end_numero         || '',
  p_end_complemento:    mv.p_end_complemento    || '',
  p_end_km:             mv.p_end_km             || '',
  p_end_ibge:           mv.p_end_ibge           || '',
  // Contato
  p_telefone_residencial:    mv.p_telefone_residencial    || mv.telefone || '',
  p_telefone_comercial:      mv.p_telefone_comercial      || '',
  p_email:                   mv.p_email                   || '',
  p_motivo_ausencia_contato: mv.p_motivo_ausencia_contato || '',
  // Informações Adicionais
  g_envolvido_presenca: mv.g_envolvido_presenca ?? null,
  p_situacao_rua:       mv.p_situacao_rua       ?? null,
});

// Options
const tipoEnvolvimentoOptions = [
  { value: 'vitima',       label: 'Vítima' },
  { value: 'testemunha',   label: 'Testemunha' },
  { value: 'solicitante',  label: 'Solicitante' },
  { value: 'proprietario', label: 'Proprietário' },
  { value: 'condutor',     label: 'Condutor' },
  { value: 'passageiro',   label: 'Passageiro' },
  { value: 'pedestre',     label: 'Pedestre' },
  { value: 'autor',        label: 'Autor' },
  { value: 'suspeito',     label: 'Suspeito' },
  { value: 'outros',       label: 'Outros' },
];

const sexoOptions = [
  { value: 'M', label: 'Masculino' },
  { value: 'F', label: 'Feminino' },
  { value: 'O', label: 'Outro' },
  { value: 'N', label: 'Não informado' },
];

const lesaoOptions = [
  { value: 'sem_lesao',  label: 'Sem Lesão' },
  { value: 'leve',       label: 'Leve' },
  { value: 'moderada',   label: 'Moderada' },
  { value: 'grave',      label: 'Grave' },
  { value: 'gravissima', label: 'Gravíssima' },
  { value: 'fatal',      label: 'Fatal' },
];

const escolaridadeOptions = [
  { value: 'nenhuma',                label: 'Nenhuma' },
  { value: 'fundamental_incompleto', label: 'Fundamental Incompleto' },
  { value: 'fundamental_completo',   label: 'Fundamental Completo' },
  { value: 'medio_incompleto',       label: 'Médio Incompleto' },
  { value: 'medio_completo',         label: 'Médio Completo' },
  { value: 'superior_incompleto',    label: 'Superior Incompleto' },
  { value: 'superior_completo',      label: 'Superior Completo' },
  { value: 'pos_graduacao',          label: 'Pós-Graduação' },
  { value: 'nao_informado',          label: 'Não Informado' },
];

const estadoCivilOptions = [
  { value: 'solteiro',      label: 'Solteiro(a)' },
  { value: 'casado',        label: 'Casado(a)' },
  { value: 'uniao_estavel', label: 'União Estável' },
  { value: 'separado',      label: 'Separado(a)' },
  { value: 'divorciado',    label: 'Divorciado(a)' },
  { value: 'viuvo',         label: 'Viúvo(a)' },
  { value: 'nao_informado', label: 'Não Informado' },
];

const corRacaOptions = [
  { value: 'branca',        label: 'Branca' },
  { value: 'preta',         label: 'Preta' },
  { value: 'parda',         label: 'Parda' },
  { value: 'amarela',       label: 'Amarela' },
  { value: 'indigena',      label: 'Indígena' },
  { value: 'nao_informado', label: 'Não Informado' },
];

const orientacaoSexualOptions = [
  { value: 'heterossexual', label: 'Heterossexual' },
  { value: 'homossexual',   label: 'Homossexual' },
  { value: 'bissexual',     label: 'Bissexual' },
  { value: 'outros',        label: 'Outros' },
  { value: 'nao_informado', label: 'Não Informado' },
];

const identidadeGeneroOptions = [
  { value: 'cisgenero',    label: 'Cisgênero' },
  { value: 'transgenero',  label: 'Transgênero' },
  { value: 'nao_binario',  label: 'Não-Binário' },
  { value: 'outros',       label: 'Outros' },
  { value: 'nao_informado',label: 'Não Informado' },
];

const tipoDocOptions = [
  { value: 'rg',                  label: 'RG - Identidade' },
  { value: 'cnh',                 label: 'CNH' },
  { value: 'passaporte',          label: 'Passaporte' },
  { value: 'certidao_nascimento', label: 'Certidão de Nascimento' },
  { value: 'ctps',                label: 'CTPS' },
  { value: 'outros',              label: 'Outros' },
];

const ufOptions = [
  { value: 'AC', label: 'AC' }, { value: 'AL', label: 'AL' }, { value: 'AP', label: 'AP' },
  { value: 'AM', label: 'AM' }, { value: 'BA', label: 'BA' }, { value: 'CE', label: 'CE' },
  { value: 'DF', label: 'DF' }, { value: 'ES', label: 'ES' }, { value: 'GO', label: 'GO' },
  { value: 'MA', label: 'MA' }, { value: 'MT', label: 'MT' }, { value: 'MS', label: 'MS' },
  { value: 'MG', label: 'MG' }, { value: 'PA', label: 'PA' }, { value: 'PB', label: 'PB' },
  { value: 'PR', label: 'PR' }, { value: 'PE', label: 'PE' }, { value: 'PI', label: 'PI' },
  { value: 'RJ', label: 'RJ' }, { value: 'RN', label: 'RN' }, { value: 'RS', label: 'RS' },
  { value: 'RO', label: 'RO' }, { value: 'RR', label: 'RR' }, { value: 'SC', label: 'SC' },
  { value: 'SP', label: 'SP' }, { value: 'SE', label: 'SE' }, { value: 'TO', label: 'TO' },
];

const paisOptions = [
  { value: 'BR',     label: 'Brasil' },
  { value: 'AR',     label: 'Argentina' },
  { value: 'BO',     label: 'Bolívia' },
  { value: 'CL',     label: 'Chile' },
  { value: 'CO',     label: 'Colômbia' },
  { value: 'EC',     label: 'Equador' },
  { value: 'PY',     label: 'Paraguai' },
  { value: 'PE',     label: 'Peru' },
  { value: 'UY',     label: 'Uruguai' },
  { value: 'VE',     label: 'Venezuela' },
  { value: 'US',     label: 'Estados Unidos' },
  { value: 'PT',     label: 'Portugal' },
  { value: 'ES',     label: 'Espanha' },
  { value: 'DE',     label: 'Alemanha' },
  { value: 'FR',     label: 'França' },
  { value: 'IT',     label: 'Itália' },
  { value: 'JP',     label: 'Japão' },
  { value: 'CN',     label: 'China' },
  { value: 'outros', label: 'Outros' },
];

// CEP
function applyCepMask(value) {
  return value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9);
}

const { buscarCep } = useCep();

async function buscarEnderecoCep() {
  const cepLimpo = (localData.value.p_end_cep || '').replace(/\D/g, '');
  if (cepLimpo.length !== 8) return;
  const resultado = await buscarCep(cepLimpo);
  if (resultado) {
    localData.value.p_end_logradouro = resultado.logradouro || localData.value.p_end_logradouro;
    localData.value.p_end_bairro     = resultado.bairro     || localData.value.p_end_bairro;
    localData.value.p_end_municipio  = resultado.localidade || localData.value.p_end_municipio;
    localData.value.p_end_estado_uf  = resultado.uf         || localData.value.p_end_estado_uf;
    if (resultado.ibge) localData.value.p_end_ibge = resultado.ibge;
  }
}

// Sync local → parent
watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

// Sync parent → local
watch(
  () => props.modelValue,
  (nv) => {
    if (nv && JSON.stringify(nv) !== JSON.stringify(localData.value)) {
      localData.value = JSON.parse(JSON.stringify(nv));
    }
  },
  { deep: true }
);
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}

.subsection-label {
  @apply text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest;
}

.radio-label {
  @apply flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none;
}

.radio-input {
  @apply h-4 w-4 text-blue-600 border-slate-300 dark:border-slate-600 focus:ring-blue-500 cursor-pointer;
}

.cep-input {
  @apply px-3 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    placeholder-slate-400 dark:placeholder-slate-500
    border border-slate-300 dark:border-slate-700/50
    hover:border-slate-400 dark:hover:border-slate-600
    focus:outline-none focus:ring-2 focus:ring-offset-0 focus:border-emerald-500 focus:ring-emerald-500/20
    transition-all duration-200;
}

.cep-btn {
  @apply px-2.5 rounded-xl bg-white dark:bg-slate-900/50
    border border-slate-300 dark:border-slate-700/50
    text-slate-600 dark:text-slate-400
    hover:bg-slate-50 dark:hover:bg-slate-800
    hover:border-slate-400 transition-all duration-200
    flex items-center;
}
</style>
