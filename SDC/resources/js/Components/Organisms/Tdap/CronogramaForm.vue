<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="numero" value="Número *" />
          <TextInput id="numero" v-model="form.numero" type="text" class="mt-1 block w-full uppercase" :class="fieldCls(form.numero, form.errors.numero)" maxlength="20" placeholder="Ex: 0001/2026" required />
          <InputError :message="form.errors.numero" class="mt-2" />
        </div>
        <div>
          <InputLabel for="empenho" value="Empenho" />
          <TextInput id="empenho" v-model="form.empenho" type="text" class="mt-1 block w-full" :class="fieldCls(form.empenho, form.errors.empenho)" maxlength="30" />
          <InputError :message="form.errors.empenho" class="mt-2" />
        </div>
        <div>
          <InputLabel for="nota_empenho" value="Nota de Empenho" />
          <TextInput id="nota_empenho" v-model="form.nota_empenho" type="text" class="mt-1 block w-full" :class="fieldCls(form.nota_empenho, form.errors.nota_empenho)" maxlength="50" />
          <InputError :message="form.errors.nota_empenho" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vínculos contratuais</h3>
      <p class="text-xs text-slate-500 mb-4">Escolha a Ata e o Lote — Município, Prestador e Valor Unitário serão preenchidos automaticamente pelo Lote.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="ata_id" value="Ata *" />
          <select id="ata_id" v-model="form.ata_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="fieldCls(form.ata_id, form.errors.ata_id)" required @change="onAtaChange">
            <option :value="null">Selecione a Ata</option>
            <option v-for="a in atas" :key="a.id" :value="a.id">
              {{ a.numero }}<template v-if="a.dt_inicio && a.dt_final"> ({{ fmtDate(a.dt_inicio) }} – {{ fmtDate(a.dt_final) }})</template>
            </option>
          </select>
          <InputError :message="form.errors.ata_id" class="mt-2" />
        </div>
        <div>
          <InputLabel for="lote_id" value="Lote *" />
          <select id="lote_id" v-model="form.lote_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="fieldCls(form.lote_id, form.errors.lote_id)" required :disabled="!form.ata_id" @change="onLoteChange">
            <option :value="null">{{ form.ata_id ? 'Selecione o Lote' : 'Selecione a Ata primeiro' }}</option>
            <option v-for="l in lotesDaAta" :key="l.id" :value="l.id">
              {{ l.numero }} — {{ l.municipio?.nome }}<span v-if="l.municipio?.uf">/{{ l.municipio.uf }}</span> ({{ l.prestador?.nome }})
            </option>
          </select>
          <InputError :message="form.errors.lote_id" class="mt-2" />
        </div>
        <div>
          <InputLabel value="Município (auto)" />
          <div class="mt-1 px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-sm text-slate-700 dark:text-slate-300 min-h-[2.5rem]" :class="fieldCls(loteSelecionado?.municipio?.id, form.errors.municipio_id)">
            <span v-if="loteSelecionado?.municipio">{{ loteSelecionado.municipio.nome }}<span v-if="loteSelecionado.municipio.uf">/{{ loteSelecionado.municipio.uf }}</span></span>
            <span v-else class="text-slate-400">—</span>
          </div>
          <InputError :message="form.errors.municipio_id" class="mt-2" />
        </div>
        <div>
          <InputLabel value="Prestador (auto)" />
          <div class="mt-1 px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-sm text-slate-700 dark:text-slate-300 min-h-[2.5rem]" :class="fieldCls(loteSelecionado?.prestador?.id, form.errors.prestador_id)">
            <span v-if="loteSelecionado?.prestador">{{ loteSelecionado.prestador.nome }} <span class="text-xs text-slate-500 font-mono">({{ loteSelecionado.prestador.cnpj }})</span></span>
            <span v-else class="text-slate-400">—</span>
          </div>
          <InputError :message="form.errors.prestador_id" class="mt-2" />
        </div>
        <div>
          <InputLabel for="ponto_captacao_id" value="Ponto de Captação *" />
          <select id="ponto_captacao_id" v-model="form.ponto_captacao_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="fieldCls(form.ponto_captacao_id, form.errors.ponto_captacao_id)" required :disabled="!form.municipio_id">
            <option :value="null">{{ form.municipio_id ? 'Selecione o Ponto de Captação' : 'Selecione o Lote primeiro' }}</option>
            <option v-for="p in pontosDoMunicipio" :key="p.id" :value="p.id">
              {{ p.nome }}<span v-if="tipoNome(p.tipo)"> ({{ tipoNome(p.tipo) }})</span>
            </option>
          </select>
          <p v-if="form.municipio_id && pontosDoMunicipio.length === 0" class="mt-1 text-xs text-amber-600">
            Nenhum ponto de captação cadastrado para este município.
          </p>
          <InputError :message="form.errors.ponto_captacao_id" class="mt-2" />
        </div>
        <div>
          <InputLabel value="Valor Unitário (auto)" />
          <div class="mt-1 px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-sm font-mono text-slate-700 dark:text-slate-300 min-h-[2.5rem]" :class="valorUnitario !== null ? '!border-2 !border-emerald-500/60' : ''">
            <span v-if="valorUnitario !== null">{{ fmtMoeda(valorUnitario) }} / m³</span>
            <span v-else class="text-slate-400">—</span>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vigência e Volume</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="dt_inicio" value="Data Inicial *" />
          <DatePicker v-model="form.dt_inicio" :error="!!form.errors.dt_inicio" extra-class="mt-1 w-full" />
          <InputError :message="form.errors.dt_inicio" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dt_final" value="Data Final *" />
          <DatePicker v-model="form.dt_final" :error="!!form.errors.dt_final" extra-class="mt-1 w-full" />
          <InputError :message="form.errors.dt_final" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dias" value="Dias *" />
          <TextInput id="dias" v-model="form.dias" type="number" min="1" max="1000" class="mt-1 block w-full" :class="fieldCls(form.dias, form.errors.dias)" required />
          <InputError :message="form.errors.dias" class="mt-2" />
        </div>
        <div>
          <InputLabel for="consumo_diario" value="Consumo diário (Litros) *" />
          <TextInput id="consumo_diario" v-model="form.consumo_diario" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :class="fieldCls(form.consumo_diario, form.errors.consumo_diario)" required />
          <InputError :message="form.errors.consumo_diario" class="mt-2" />
        </div>
        <div>
          <div class="flex items-center justify-between">
            <InputLabel for="fator" value="Fator (m³)" />
            <label class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer">
              <input type="checkbox" v-model="form.usar_fator_manual" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
              Manual
            </label>
          </div>
          <TextInput id="fator" v-model="form.fator" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :class="form.usar_fator_manual ? fieldCls(form.fator, form.errors.fator) : ''" :readonly="!form.usar_fator_manual" :tabindex="form.usar_fator_manual ? 0 : -1" />
          <p class="mt-1 text-xs text-slate-400">Automático: (consumo × dias) ÷ 1000</p>
          <InputError :message="form.errors.fator" class="mt-2" />
        </div>
        <div class="flex flex-col gap-2">
          <div class="w-full rounded-md border border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-500/10 px-3 py-2">
            <p class="text-xs text-blue-700 dark:text-blue-300">Volume contratado</p>
            <p class="text-lg font-mono font-semibold text-blue-700 dark:text-blue-200">{{ fmtNum(volumeContratado) }} m³</p>
          </div>
          <div class="w-full rounded-md border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 px-3 py-2">
            <p class="text-xs text-emerald-700 dark:text-emerald-300">Valor total</p>
            <p class="text-lg font-mono font-semibold text-emerald-700 dark:text-emerald-200">{{ valorTotal !== null ? fmtMoeda(valorTotal) : '—' }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Prorrogação <span class="text-xs font-normal text-slate-400">(opcional)</span></h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="dt_inicio_prorrogacao" value="Data Início da Prorrogação" />
          <DatePicker v-model="form.dt_inicio_prorrogacao" :error="!!form.errors.dt_inicio_prorrogacao" extra-class="mt-1 w-full" />
          <InputError :message="form.errors.dt_inicio_prorrogacao" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dt_final_prorrogacao" value="Data Final da Prorrogação" />
          <DatePicker v-model="form.dt_final_prorrogacao" :error="!!form.errors.dt_final_prorrogacao" extra-class="mt-1 w-full" />
          <InputError :message="form.errors.dt_final_prorrogacao" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Justificativa e Observações</h3>
      <div class="space-y-4">
        <div>
          <InputLabel for="justificativa" value="Justificativa" />
          <textarea id="justificativa" v-model="form.justificativa" rows="3" maxlength="5000" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="fieldCls(form.justificativa, form.errors.justificativa)" />
          <InputError :message="form.errors.justificativa" class="mt-2" />
        </div>
        <div>
          <InputLabel for="observacao" value="Observação" />
          <textarea id="observacao" v-model="form.observacao" rows="2" maxlength="5000" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" :class="fieldCls(form.observacao, form.errors.observacao)" />
          <InputError :message="form.errors.observacao" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <SecondaryButton type="button" @click="$emit('cancel')">Cancelar</SecondaryButton>
      <PrimaryButton type="submit" :disabled="form.processing">
        {{ form.processing ? 'Salvando...' : submitLabel }}
      </PrimaryButton>
    </div>
  </form>
</template>

<script setup>
import { computed, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';

const props = defineProps({
  form:           { type: Object, required: true },
  atas:           { type: Array, default: () => [] },
  lotes:          { type: Array, default: () => [] },
  pontosCaptacao: { type: Array, default: () => [] },
  submitLabel:    { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

const TIPOS_PONTO = {
  1: 'COPASA',
  2: 'COPANOR',
  3: 'BARRAGEM',
  4: 'SAAE/DMAE',
  5: 'POÇO PÚBLICO',
  6: 'POÇO PARTICULAR',
};

// Afordancia visual: vermelho se houver erro de validacao (ao salvar),
// verde quando preenchido, padrao caso contrario. !important para vencer
// as regras .dark dos inputs (especificidade maior pelo .dark).
function fieldCls(v, err) {
  if (err) return '!border-2 !border-red-500/70';
  const preenchido = v !== null && v !== undefined && v !== '' && !(typeof v === 'number' && v === 0);
  return preenchido ? '!border-2 !border-emerald-500/60' : '';
}

const lotesDaAta = computed(() => props.lotes.filter(l => Number(l.ata_id) === Number(props.form.ata_id)));

const loteSelecionado = computed(() => props.lotes.find(l => Number(l.id) === Number(props.form.lote_id)) ?? null);

const valorUnitario = computed(() => {
  const v = loteSelecionado.value?.valor_m3;
  return (v === undefined || v === null) ? null : Number(v);
});

const pontosDoMunicipio = computed(() => {
  if (!props.form.municipio_id) return [];
  return props.pontosCaptacao.filter(p => Number(p.municipio_id) === Number(props.form.municipio_id));
});

const fatorAuto = computed(() => {
  const c = Number(props.form.consumo_diario || 0);
  const d = Number(props.form.dias || 0);
  return Math.round((c * d / 1000) * 100) / 100;
});

const volumeContratado = computed(() => {
  return props.form.usar_fator_manual ? Number(props.form.fator || 0) : fatorAuto.value;
});

const valorTotal = computed(() => {
  if (valorUnitario.value === null) return null;
  return volumeContratado.value * valorUnitario.value;
});

// Mantem o campo fator sincronizado com o calculo automatico quando nao for manual.
watch([fatorAuto, () => props.form.usar_fator_manual], () => {
  if (!props.form.usar_fator_manual) {
    props.form.fator = fatorAuto.value;
  }
}, { immediate: true });

function onAtaChange() {
  props.form.lote_id = null;
  props.form.municipio_id = null;
  props.form.prestador_id = null;
  props.form.ponto_captacao_id = null;
  if (props.form.errors) {
    props.form.errors.lote_id = undefined;
    props.form.errors.municipio_id = undefined;
    props.form.errors.prestador_id = undefined;
    props.form.errors.ponto_captacao_id = undefined;
  }
}

function onLoteChange() {
  const l = loteSelecionado.value;
  if (l) {
    props.form.municipio_id = l.municipio?.id ?? l.municipio_id;
    props.form.prestador_id = l.prestador?.id ?? l.prestador_id;
  } else {
    props.form.municipio_id = null;
    props.form.prestador_id = null;
  }
  // Ponto depende do municipio; limpa para evitar inconsistencia.
  props.form.ponto_captacao_id = null;
}

function tipoNome(tipo) {
  return TIPOS_PONTO[Number(tipo)] ?? '';
}

function fmtDate(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}

function fmtNum(v) {
  return Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtMoeda(v) {
  return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
</script>
