<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="numero" value="Número *" />
          <TextInput id="numero" v-model="form.numero" type="text" class="mt-1 block w-full uppercase" maxlength="20" placeholder="Ex: 0001/2026" required />
          <InputError :message="form.errors.numero" class="mt-2" />
        </div>
        <div>
          <InputLabel for="empenho" value="Empenho" />
          <TextInput id="empenho" v-model="form.empenho" type="text" class="mt-1 block w-full" maxlength="30" />
          <InputError :message="form.errors.empenho" class="mt-2" />
        </div>
        <div>
          <InputLabel for="nota_empenho" value="Nota de Empenho" />
          <TextInput id="nota_empenho" v-model="form.nota_empenho" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.nota_empenho" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vínculos contratuais</h3>
      <p class="text-xs text-slate-500 mb-4">Escolha a Ata e o Lote — Município e Prestador serão preenchidos automaticamente pelo Lote.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="ata_id" value="Ata *" />
          <select id="ata_id" v-model="form.ata_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required @change="onAtaChange">
            <option :value="null">Selecione a Ata</option>
            <option v-for="a in atas" :key="a.id" :value="a.id">
              {{ a.numero }}<template v-if="a.dt_inicio && a.dt_final"> ({{ fmtDate(a.dt_inicio) }} – {{ fmtDate(a.dt_final) }})</template>
            </option>
          </select>
          <InputError :message="form.errors.ata_id" class="mt-2" />
        </div>
        <div>
          <InputLabel for="lote_id" value="Lote *" />
          <select id="lote_id" v-model="form.lote_id" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required :disabled="!form.ata_id" @change="onLoteChange">
            <option :value="null">{{ form.ata_id ? 'Selecione o Lote' : 'Selecione a Ata primeiro' }}</option>
            <option v-for="l in lotesDaAta" :key="l.id" :value="l.id">
              {{ l.numero }} — {{ l.municipio?.nome }}<span v-if="l.municipio?.uf">/{{ l.municipio.uf }}</span> ({{ l.prestador?.nome }})
            </option>
          </select>
          <InputError :message="form.errors.lote_id" class="mt-2" />
        </div>
        <div>
          <InputLabel value="Município (auto)" />
          <div class="mt-1 px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-sm text-slate-700 dark:text-slate-300 min-h-[2.5rem]">
            <span v-if="loteSelecionado?.municipio">{{ loteSelecionado.municipio.nome }}<span v-if="loteSelecionado.municipio.uf">/{{ loteSelecionado.municipio.uf }}</span></span>
            <span v-else class="text-slate-400">—</span>
          </div>
          <InputError :message="form.errors.municipio_id" class="mt-2" />
        </div>
        <div>
          <InputLabel value="Prestador (auto)" />
          <div class="mt-1 px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-sm text-slate-700 dark:text-slate-300 min-h-[2.5rem]">
            <span v-if="loteSelecionado?.prestador">{{ loteSelecionado.prestador.nome }} <span class="text-xs text-slate-500 font-mono">({{ loteSelecionado.prestador.cnpj }})</span></span>
            <span v-else class="text-slate-400">—</span>
          </div>
          <InputError :message="form.errors.prestador_id" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vigência e Volume</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="dt_inicio" value="Data Inicial *" />
          <TextInput id="dt_inicio" v-model="form.dt_inicio" type="date" class="mt-1 block w-full" required />
          <InputError :message="form.errors.dt_inicio" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dt_final" value="Data Final *" />
          <TextInput id="dt_final" v-model="form.dt_final" type="date" class="mt-1 block w-full" required />
          <InputError :message="form.errors.dt_final" class="mt-2" />
        </div>
        <div>
          <InputLabel for="dias" value="Dias úteis *" />
          <TextInput id="dias" v-model="form.dias" type="number" min="1" max="1000" class="mt-1 block w-full" required />
          <InputError :message="form.errors.dias" class="mt-2" />
        </div>
        <div>
          <InputLabel for="consumo_diario" value="Consumo diário (m³) *" />
          <TextInput id="consumo_diario" v-model="form.consumo_diario" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
          <InputError :message="form.errors.consumo_diario" class="mt-2" />
        </div>
        <div>
          <InputLabel for="fator" value="Fator multiplicador" />
          <TextInput id="fator" v-model="form.fator" type="number" step="0.01" min="0.01" max="99.99" class="mt-1 block w-full" />
          <InputError :message="form.errors.fator" class="mt-2" />
        </div>
        <div class="flex items-end">
          <div class="w-full rounded-md border border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-500/10 px-3 py-2">
            <p class="text-xs text-blue-700 dark:text-blue-300">Volume contratado</p>
            <p class="text-lg font-mono font-semibold text-blue-700 dark:text-blue-200">{{ volumeContratado }} m³</p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Justificativa e Observações</h3>
      <div class="space-y-4">
        <div>
          <InputLabel for="justificativa" value="Justificativa" />
          <textarea id="justificativa" v-model="form.justificativa" rows="3" maxlength="5000" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" />
          <InputError :message="form.errors.justificativa" class="mt-2" />
        </div>
        <div>
          <InputLabel for="observacao" value="Observação" />
          <textarea id="observacao" v-model="form.observacao" rows="2" maxlength="5000" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" />
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
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  form:  { type: Object, required: true },
  atas:  { type: Array, default: () => [] },
  lotes: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

const lotesDaAta = computed(() => props.lotes.filter(l => Number(l.ata_id) === Number(props.form.ata_id)));

const loteSelecionado = computed(() => props.lotes.find(l => Number(l.id) === Number(props.form.lote_id)) ?? null);

const volumeContratado = computed(() => {
  const c = Number(props.form.consumo_diario || 0);
  const d = Number(props.form.dias || 0);
  const f = Number(props.form.fator || 1);
  return (c * d * f).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

function onAtaChange() {
  // Quando muda a Ata, limpa Lote/Municipio/Prestador
  props.form.lote_id = null;
  props.form.municipio_id = null;
  props.form.prestador_id = null;
  if (props.form.errors) {
    props.form.errors.lote_id = undefined;
    props.form.errors.municipio_id = undefined;
    props.form.errors.prestador_id = undefined;
  }
}

function onLoteChange() {
  // Autopopula Municipio + Prestador a partir do Lote escolhido
  const l = loteSelecionado.value;
  if (l) {
    props.form.municipio_id = l.municipio?.id ?? l.municipio_id;
    props.form.prestador_id = l.prestador?.id ?? l.prestador_id;
  } else {
    props.form.municipio_id = null;
    props.form.prestador_id = null;
  }
}

function fmtDate(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
