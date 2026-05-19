<template>
  <div class="animate-fade-in-up pb-6 space-y-6">

    <!-- ── Card 1: Dados Pessoais ─────────────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-purple">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados Pessoais</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <FormSelect
            label="Tipo de Pessoa"
            v-model="formData.g_tipo_pessoa"
            :options="tipoEnvolvimentoOptions"
            placeholder="Selecione uma opção"
            required
            :error="vErros.g_tipo_pessoa"
          />
          <div class="rat-grid-2">
            <FormField label="Nome Completo / Razão Social" v-model="formData.p_nome_completo" placeholder="Nome completo ou razão social" required :error="vErros.p_nome_completo" />
            <FormField label="Nome Social" v-model="formData.p_nome_social" placeholder="Ex: Zê da Silva" />
          </div>
          <div class="rat-grid-3">
            <div>
              <label class="form-label">Data de Nascimento</label>
              <DatePicker v-model="formData.p_data_nascimento" />
            </div>
            <FormField label="Idade Aparente" v-model="formData.p_idade_aparente" placeholder="Ex: 25" type="number" />
            <FormField label="CPF" v-model="formData.p_cpf" mask="cpf" placeholder="000.000.000-00" required :error="vErros.p_cpf" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Nome da Mãe" v-model="formData.p_nome_mae" placeholder="Nome completo da mãe" />
            <FormField label="Nome do Pai" v-model="formData.p_nome_pai" placeholder="Nome completo do pai" />
          </div>
          <div class="rat-grid-4">
            <FormField label="Ocupação atual" v-model="formData.p_ocupacao_atual" placeholder="Profissão ou ocupação" />
            <FormSelect label="Escolaridade" v-model="formData.p_escolaridade" :options="escolaridadeOptions" placeholder="Selecione uma opção" />
            <FormSelect label="Sexo" v-model="formData.p_sexo" :options="sexoOptions" placeholder="Selecione uma opção" />
            <FormSelect label="Estado Civil" v-model="formData.p_estado_civil" :options="estadoCivilOptions" placeholder="Selecione uma opção" />
          </div>
          <div class="rat-grid-3">
            <FormSelect label="Etnia" v-model="formData.p_cor_raca" :options="corRacaOptions" placeholder="Selecione uma opção" />
            <FormSelect label="Orientação Sexual" v-model="formData.p_orientacao_sexual" :options="orientacaoSexualOptions" placeholder="Selecione uma opção" />
            <FormSelect label="Identidade de Gênero" v-model="formData.p_identidade_genero" :options="identidadeGeneroOptions" placeholder="Selecione uma opção" />
          </div>
          <div class="rat-grid-4">
            <FormField label="Nacionalidade" v-model="formData.p_nacionalidade" placeholder="Brasileira" />
            <FormSelect label="País de Origem" v-model="formData.p_pais_origem" :options="paisOptions" placeholder="Brasil" />
            <FormSelect label="Naturalidade/UF" v-model="formData.p_naturalidade_uf" :options="ufOptions" placeholder="Selecione uma opção" />
            <div>
              <label class="form-label">Indivíduo é turista?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="true"  v-model="formData.p_turista" class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="false" v-model="formData.p_turista" class="radio-input" /> Não</label>
              </div>
            </div>
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Card 2: Documentação de Identificação ─────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Documentação de Identificação</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-4">
            <FormSelect label="Tipo" v-model="formData.p_tipo" :options="tipoDocOptions" placeholder="Selecione uma opção" />
            <FormField label="Número" v-model="formData.p_numero" placeholder="Número do documento" />
            <FormField label="Órgão Expedidor" v-model="formData.p_orgao_expedidor" placeholder="Ex: SSP/MG" />
            <FormSelect label="UF" v-model="formData.p_uf" :options="ufOptions" placeholder="Selecione uma opção" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Card 3: Endereço ───────────────────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-success">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Endereço</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-3">
            <FormSelect label="País" v-model="formData.p_end_pais" :options="paisOptions" placeholder="Brasil" />
            <FormSelect label="Estado/UF" v-model="formData.p_end_estado_uf" :options="ufOptions" placeholder="Selecione uma opção" />
            <FormField label="Município" v-model="formData.p_end_municipio" placeholder="Selecione o município" />
          </div>
          <div class="rat-grid-3">
            <div>
              <label class="form-label">CEP</label>
              <div class="flex gap-1.5">
                <input
                  type="text"
                  :value="formData.p_end_cep"
                  @input="formData.p_end_cep = applyCepMask($event.target.value)"
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
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Digite o CEP e clique em buscar</p>
            </div>
            <FormField label="Bairro" v-model="formData.p_end_bairro" placeholder="Ex: Centro" />
            <FormField label="Logradouro" v-model="formData.p_end_logradouro" placeholder="Ex: Rua das Flores" />
          </div>
          <div class="rat-grid-4">
            <FormField label="Número" v-model="formData.p_end_numero" placeholder="123" />
            <FormField label="Complemento" v-model="formData.p_end_complemento" placeholder="Apto 101" />
            <FormField label="KM (rodovias)" v-model="formData.p_end_km" type="number" placeholder="45" />
            <FormField label="Código IBGE" v-model="formData.p_end_ibge" placeholder="Código IBGE" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Card 4: Contato ────────────────────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-warning">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Contato</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-3">
            <FormField label="Telefone Residencial/Celular" v-model="formData.p_telefone_residencial" mask="phone" placeholder="(31) 99999-9999" />
            <FormField label="Telefone Comercial/Celular" v-model="formData.p_telefone_comercial" mask="phone" placeholder="(31) 3333-3333" />
            <FormField label="E-mail" v-model="formData.p_email" type="email" placeholder="exemplo@email.com" />
          </div>
          <FormField label="Motivo ausência de Telefone/E-mail" v-model="formData.p_motivo_ausencia_contato" type="textarea" placeholder="Ex: Não possui telefone, não informou e-mail, etc." />
        </div>
      </fieldset>
    </div>

    <!-- ── Card 5: Informações Adicionais ────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-danger">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Informações Adicionais</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <FormSelect
            label="Grau de Lesão"
            v-model="formData.g_lesao_grau"
            :options="lesaoGrauOptions"
            placeholder="Selecione uma opção"
          />
          <div class="rat-grid-2">
            <div>
              <label class="form-label">Este Envolvido é Militar/ Policial/ Agente de Segurança Pública?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="false" v-model="formData.g_envolvido_presenca" class="radio-input" /> Não</label>
                <label class="radio-label"><input type="radio" :value="true"  v-model="formData.g_envolvido_presenca" class="radio-input" /> Sim</label>
              </div>
            </div>
            <div>
              <label class="form-label">O envolvido é pessoa em situação de rua?</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="false" v-model="formData.p_situacao_rua" class="radio-input" /> Não</label>
                <label class="radio-label"><input type="radio" :value="true"  v-model="formData.p_situacao_rua" class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="null"  v-model="formData.p_situacao_rua" class="radio-input" /> Não informado</label>
              </div>
            </div>
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Botões: Adicionar Envolvido + Salvar ───────────────────────────── -->
    <div v-if="!viewOnly" class="flex items-center justify-between gap-3">
      <button
        type="button"
        @click="adicionarEnvolvido"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-colors shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Adicionar Envolvido
      </button>
      <button
        type="button"
        @click="handleSave"
        :disabled="loading"
        class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold text-sm transition-colors shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        {{ loading ? 'Salvando...' : 'Salvar Envolvidos' }}
      </button>
    </div>

    <!-- ── Tabela: Envolvidos Adicionados ─────────────────────────────────── -->
    <div v-if="localEnvolvidos.length > 0" class="rat-section-card">
      <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="font-semibold text-sm text-slate-700 dark:text-slate-300">Envolvidos Adicionados</span>
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-500/15 text-purple-600 dark:text-purple-400">{{ localEnvolvidos.length }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700/50">
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Nome</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">CPF</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Documento</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Idade</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(e, i) in localEnvolvidos"
              :key="e.id || i"
              class="border-b border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
              :class="editingIndex === i ? 'bg-amber-50 dark:bg-amber-900/10' : ''"
            >
              <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200 font-medium">{{ e.p_nome_completo || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ e.p_cpf || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">
                <span v-if="e.p_tipo">{{ tipoDocLabel(e.p_tipo) }}<span v-if="e.p_numero">: {{ e.p_numero }}</span></span>
                <span v-else>—</span>
              </td>
              <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ calcIdade(e.p_data_nascimento, e.p_idade_aparente) }}</td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-1">
                  <button
                    v-if="!viewOnly"
                    type="button"
                    @click="editarEnvolvido(i)"
                    class="p-1.5 rounded-lg text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    title="Editar"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    v-if="!viewOnly"
                    type="button"
                    @click="removerEnvolvido(i)"
                    class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                    title="Remover"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useToast } from '@/Composables/useToast';
import { useCep } from '@/composables/location';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';

const props = defineProps({
  envolvidos: { type: Array,   default: () => [] },
  viewOnly:   { type: Boolean, default: false },
  loading:    { type: Boolean, default: false },
});

const emit = defineEmits(['add', 'remove', 'update', 'save']);

const { show: toast } = useToast();
const vErros = reactive({ g_tipo_pessoa: '', p_nome_completo: '', p_cpf: '' });

// ── Form state ──────────────────────────────────────────────────────────────
const emptyForm = () => ({
  g_tipo_pessoa: '', g_lesao_grau: '',
  p_nome_completo: '', p_nome_social: '', p_cpf: '',
  p_data_nascimento: '', p_idade_aparente: '',
  p_sexo: '', p_nome_mae: '', p_nome_pai: '',
  p_ocupacao_atual: '', p_escolaridade: '', p_estado_civil: '',
  p_cor_raca: '', p_orientacao_sexual: '', p_identidade_genero: '',
  p_nacionalidade: '', p_pais_origem: '', p_naturalidade_uf: '',
  p_turista: null,
  p_tipo: '', p_numero: '', p_orgao_expedidor: '', p_uf: '',
  p_end_cep: '', p_end_pais: '', p_end_estado_uf: '', p_end_municipio: '',
  p_end_bairro: '', p_end_logradouro: '', p_end_numero: '',
  p_end_complemento: '', p_end_km: '', p_end_ibge: '',
  p_telefone_residencial: '', p_telefone_comercial: '',
  p_email: '', p_motivo_ausencia_contato: '',
  g_envolvido_presenca: null, p_situacao_rua: null,
});

const formData     = ref(emptyForm());
const editingIndex = ref(-1);

function validarEnvolvido() {
  vErros.g_tipo_pessoa   = formData.value.g_tipo_pessoa   ? '' : 'Campo obrigatório';
  vErros.p_nome_completo = formData.value.p_nome_completo ? '' : 'Campo obrigatório';
  vErros.p_cpf           = formData.value.p_cpf           ? '' : 'Campo obrigatório';
  const LABELS = { g_tipo_pessoa: 'Tipo de Pessoa', p_nome_completo: 'Nome Completo', p_cpf: 'CPF' };
  const faltando = Object.entries(vErros).filter(([, v]) => v).map(([k]) => LABELS[k]);
  if (faltando.length) { toast(`Preencha os campos obrigatórios: ${faltando.join(', ')}`, 'error'); return false; }
  return true;
}

function adicionarEnvolvido() {
  if (!validarEnvolvido()) return;
  if (editingIndex.value >= 0) {
    localEnvolvidos.value[editingIndex.value] = { ...formData.value };
  } else {
    localEnvolvidos.value.push({ ...formData.value, id: Date.now() });
  }
  editingIndex.value = -1;
  formData.value = emptyForm();
  Object.keys(vErros).forEach(k => { vErros[k] = ''; });
  emit('update', [...localEnvolvidos.value]);
}

// ── Local list ──────────────────────────────────────────────────────────────
const localEnvolvidos = ref(
  Array.isArray(props.envolvidos) && props.envolvidos.length > 0
    ? JSON.parse(JSON.stringify(props.envolvidos))
    : []
);

// ── CEP ──────────────────────────────────────────────────────────────────────
function applyCepMask(value) {
  return value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9);
}
const { buscarCep } = useCep();
async function buscarEnderecoCep() {
  const cepLimpo = (formData.value.p_end_cep || '').replace(/\D/g, '');
  if (cepLimpo.length !== 8) return;
  const r = await buscarCep(cepLimpo);
  if (r) {
    formData.value.p_end_logradouro = r.logradouro || formData.value.p_end_logradouro;
    formData.value.p_end_bairro     = r.bairro     || formData.value.p_end_bairro;
    formData.value.p_end_municipio  = r.localidade || formData.value.p_end_municipio;
    formData.value.p_end_estado_uf  = r.uf         || formData.value.p_end_estado_uf;
    if (r.ibge) formData.value.p_end_ibge = r.ibge;
  }
}

// ── Actions ─────────────────────────────────────────────────────────────────
function editarEnvolvido(index) {
  formData.value = { ...localEnvolvidos.value[index] };
  editingIndex.value = index;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function removerEnvolvido(index) {
  localEnvolvidos.value.splice(index, 1);
  if (editingIndex.value === index) { editingIndex.value = -1; formData.value = emptyForm(); }
  else if (editingIndex.value > index) editingIndex.value--;
  emit('update', [...localEnvolvidos.value]);
}

function handleSave() {
  emit('update', [...localEnvolvidos.value]);
  emit('save');
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function calcIdade(dataNasc, idadeAparente) {
  if (dataNasc) {
    const birth = new Date(dataNasc);
    if (!isNaN(birth)) {
      const today = new Date();
      let age = today.getFullYear() - birth.getFullYear();
      const m = today.getMonth() - birth.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
      return age;
    }
  }
  return idadeAparente || '—';
}

function tipoDocLabel(v) {
  return tipoDocOptions.find(o => o.value === v)?.label || v;
}

// ── Sync from parent ─────────────────────────────────────────────────────────
watch(() => props.envolvidos, (nv) => {
  const arr = Array.isArray(nv) ? nv : [];
  if (JSON.stringify(arr) !== JSON.stringify(localEnvolvidos.value)) {
    localEnvolvidos.value = JSON.parse(JSON.stringify(arr));
  }
}, { deep: false });

// ── Options ──────────────────────────────────────────────────────────────────
const lesaoGrauOptions = [
  { value: 'sem_lesao',    label: 'Sem Lesão' },
  { value: 'leve',         label: 'Leve' },
  { value: 'moderada',     label: 'Moderada' },
  { value: 'grave',        label: 'Grave' },
  { value: 'gravissima',   label: 'Gravíssima' },
  { value: 'fatal',        label: 'Fatal / Óbito' },
  { value: 'nao_informado', label: 'Não Informado' },
];
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
  { value: 'cisgenero',     label: 'Cisgênero' },
  { value: 'transgenero',   label: 'Transgênero' },
  { value: 'nao_binario',   label: 'Não-Binário' },
  { value: 'outros',        label: 'Outros' },
  { value: 'nao_informado', label: 'Não Informado' },
];
const tipoDocOptions = [
  { value: 'rg',                  label: 'RG' },
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
  { value: 'PY',     label: 'Paraguai' },
  { value: 'PE',     label: 'Peru' },
  { value: 'UY',     label: 'Uruguai' },
  { value: 'VE',     label: 'Venezuela' },
  { value: 'US',     label: 'Estados Unidos' },
  { value: 'PT',     label: 'Portugal' },
  { value: 'outros', label: 'Outros' },
];
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}
.radio-label {
  @apply flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none;
}
.radio-input {
  @apply h-4 w-4 text-blue-600 border-slate-300 dark:border-slate-600 focus:ring-blue-500 cursor-pointer;
}
.cep-input {
  @apply px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base rounded-lg bg-slate-50 dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    placeholder-slate-400 dark:placeholder-slate-500
    border border-slate-300 dark:border-slate-700/50
    hover:border-slate-400 dark:hover:border-slate-600
    focus:outline-none focus:ring-2 focus:ring-offset-0 focus:border-emerald-500 focus:ring-emerald-500/20
    transition-all duration-200;
}
.cep-btn {
  @apply px-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/50
    border border-slate-300 dark:border-slate-700/50
    text-slate-600 dark:text-slate-400
    hover:bg-slate-100 dark:hover:bg-slate-800
    hover:border-slate-400 transition-all duration-200
    flex items-center;
}
</style>
