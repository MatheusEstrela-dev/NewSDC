<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Vínculos</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <InputLabel for="ata_id" value="Ata *" />
          <select
            id="ata_id"
            v-model="form.ata_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            required
          >
            <option :value="null">Selecione a Ata</option>
            <option v-for="a in atas" :key="a.id" :value="a.id">
              {{ a.numero }}
              <template v-if="a.dt_inicio && a.dt_final"> ({{ formatDate(a.dt_inicio) }} – {{ formatDate(a.dt_final) }})</template>
            </option>
          </select>
          <InputError :message="form.errors.ata_id" class="mt-2" />
        </div>
        <!--
          O lote atende VARIOS municipios ("...destinado aos municipios de A, B
          e C"): multi-selecao com busca, em vez do select unico que forcava um
          municipio so por lote.
        -->
        <div>
          <InputLabel value="Municípios atendidos *" />
          <div class="mt-1 rounded-md border border-slate-300 dark:border-slate-700 dark:bg-slate-900/50">
            <div class="p-2 border-b border-slate-200 dark:border-slate-700">
              <TextInput
                v-model="buscaMunicipio"
                type="search"
                class="block w-full text-sm"
                placeholder="Buscar município..."
              />
            </div>
            <div v-if="municipiosSelecionados.length" class="flex flex-wrap gap-1 p-2 border-b border-slate-200 dark:border-slate-700">
              <button
                v-for="m in municipiosSelecionados"
                :key="m.id"
                type="button"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/40 text-xs text-blue-800 dark:text-blue-200"
                :title="`Remover ${m.nome}`"
                @click="alternarMunicipio(m.id)"
              >
                {{ m.nome }}<span aria-hidden="true">×</span>
              </button>
            </div>
            <ul class="max-h-52 overflow-y-auto p-2 space-y-1">
              <li v-for="m in municipiosFiltrados" :key="m.id">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                  <input
                    type="checkbox"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    :checked="estaSelecionado(m.id)"
                    @change="alternarMunicipio(m.id)"
                  />
                  <span>{{ m.nome }}<span v-if="m.uf" class="text-slate-400"> / {{ m.uf }}</span></span>
                </label>
              </li>
              <li v-if="municipiosFiltrados.length === 0" class="text-sm text-slate-400 px-1 py-2">
                Nenhum município encontrado.
              </li>
            </ul>
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ municipiosSelecionados.length }} selecionado(s)</p>
          <InputError :message="form.errors.municipio_ids" class="mt-2" />
        </div>
        <div class="md:col-span-2">
          <InputLabel for="prestador_id" value="Prestador *" />
          <select
            id="prestador_id"
            v-model="form.prestador_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            required
          >
            <option :value="null">Selecione o prestador</option>
            <option v-for="p in prestadores" :key="p.id" :value="p.id">
              {{ p.nome }} ({{ p.cnpj }})
            </option>
          </select>
          <InputError :message="form.errors.prestador_id" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="numero" value="Número do Lote *" />
          <TextInput
            id="numero"
            v-model="form.numero"
            type="text"
            class="mt-1 block w-full uppercase"
            maxlength="20"
            placeholder="Ex: L01"
            required
          />
          <InputError :message="form.errors.numero" class="mt-2" />
        </div>
        <div>
          <InputLabel for="nome" value="Nome (opcional)" />
          <TextInput
            id="nome"
            v-model="form.nome"
            type="text"
            class="mt-1 block w-full"
            maxlength="150"
            placeholder="Ex: Lote Norte – Distrito 1"
          />
          <InputError :message="form.errors.nome" class="mt-2" />
        </div>
        <div>
          <InputLabel for="contrato" value="Contrato (opcional)" />
          <TextInput
            id="contrato"
            v-model="form.contrato"
            type="text"
            class="mt-1 block w-full"
            maxlength="50"
            placeholder="Ex: 123/2026"
          />
          <InputError :message="form.errors.contrato" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Quantidade e Valor</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <InputLabel for="qtd_agua_m3" value="Quantidade contratada (m³) *" />
          <TextInput
            id="qtd_agua_m3"
            v-model="form.qtd_agua_m3"
            type="number"
            step="0.01"
            min="0.01"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.qtd_agua_m3" class="mt-2" />
        </div>
        <div>
          <InputLabel for="valor_m3" value="Valor unitário (R$/m³) *" />
          <TextInput
            id="valor_m3"
            v-model="form.valor_m3"
            type="number"
            step="0.01"
            min="0.01"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.valor_m3" class="mt-2" />
        </div>
        <div class="flex items-end">
          <div class="w-full rounded-md border border-slate-200 dark:border-slate-700/40 bg-slate-50 dark:bg-slate-800/40 px-3 py-2">
            <p class="text-xs text-slate-500">Valor total estimado</p>
            <p class="text-lg font-mono font-semibold text-slate-900 dark:text-slate-100">
              R$ {{ valorTotal }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Outros</h3>
      <div class="space-y-4">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.ativo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm text-slate-700 dark:text-slate-300">Lote ativo</span>
        </label>
        <div>
          <InputLabel for="observacoes" value="Observações" />
          <textarea
            id="observacoes"
            v-model="form.observacoes"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            rows="3"
            maxlength="2000"
          />
          <InputError :message="form.errors.observacoes" class="mt-2" />
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
import { computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  form: { type: Object, required: true },
  atas: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  prestadores: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

// Multi-selecao de municipios: o catalogo tem 853 itens, entao a lista e
// filtrada por busca (sem acento/caixa) e os escolhidos aparecem como chips.
const buscaMunicipio = ref('');

const semAcento = (v) => (v ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

const municipiosFiltrados = computed(() => {
  const termo = semAcento(buscaMunicipio.value).trim();
  if (!termo) return props.municipios;
  return props.municipios.filter(m => semAcento(m.nome).includes(termo));
});

const municipiosSelecionados = computed(() => {
  const ids = props.form.municipio_ids ?? [];
  return props.municipios.filter(m => ids.includes(m.id));
});

function estaSelecionado(id) {
  return (props.form.municipio_ids ?? []).includes(id);
}

function alternarMunicipio(id) {
  const atuais = props.form.municipio_ids ?? [];
  props.form.municipio_ids = atuais.includes(id)
    ? atuais.filter(i => i !== id)
    : [...atuais, id];
}

const valorTotal = computed(() => {
  const qtd = Number(props.form.qtd_agua_m3 || 0);
  const vu = Number(props.form.valor_m3 || 0);
  return (qtd * vu).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

function formatDate(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
