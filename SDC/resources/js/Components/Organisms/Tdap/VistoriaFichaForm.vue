<template>
  <form @submit.prevent="$emit('submit', form)" class="space-y-6">
    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Identificação</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <InputLabel for="nome" value="Vistoriador *" />
          <TextInput id="nome" v-model="form.nome" type="text" class="mt-1 block w-full" maxlength="150" required />
          <InputError :message="form.errors.nome" class="mt-2" />
        </div>
        <!--
          O campo se chama `data_vistoria` no formulario: `data` e propriedade
          reservada do useForm do Inertia (form.data()) e sobrescrevia o valor,
          entregando uma funcao ao DatePicker — o campo nao renderizava. O
          transform em Create/Edit devolve o nome `data` na requisicao, entao o
          erro de validacao continua chegando em `errors.data`.

          `error` pinta a borda quando o backend recusa a data; `extra-class`
          aplica margem/largura na caixa do campo, e nao no wrapper — e como o
          resto do modulo usa o DatePicker (ver CronogramaForm).
        -->
        <div>
          <InputLabel for="data" value="Data da vistoria *" />
          <DatePicker
            id="data"
            v-model="form.data_vistoria"
            :error="!!form.errors.data"
            extra-class="mt-1 w-full"
            required
          />
          <InputError :message="form.errors.data" class="mt-2" />
        </div>
        <div>
          <InputLabel for="edital" value="Edital" />
          <TextInput id="edital" v-model="form.edital" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.edital" class="mt-2" />
        </div>
        <div>
          <InputLabel for="ficha" value="Ficha / Laudo" />
          <TextInput id="ficha" v-model="form.ficha" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.ficha" class="mt-2" />
        </div>
        <!--
          Numero do lacre aplicado no tanque ao fim da vistoria. Substitui o
          campo "Lote" (que apenas repetia o lote da ata e nao dizia nada sobre
          o caminhao vistoriado).
        -->
        <div>
          <InputLabel for="lacre" value="Número do lacre" />
          <TextInput id="lacre" v-model="form.lacre" type="text" class="mt-1 block w-full" maxlength="30" placeholder="Ex: 000123" />
          <InputError :message="form.errors.lacre" class="mt-2" />
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Caminhão vistoriado</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <InputLabel for="placa_id" value="Caminhão *" />
          <select
            id="placa_id"
            v-model="form.placa_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            required
            @change="onCaminhaoChange"
          >
            <option :value="null">Selecione o caminhão</option>
            <option v-for="c in caminhoes" :key="c.id" :value="c.id">
              {{ c.placa }} — {{ c.marca || '' }} {{ c.modelo || '' }} ({{ c.prestador?.nome || '' }})
            </option>
          </select>
          <InputError :message="form.errors.placa_id" class="mt-2" />
        </div>
        <!--
          Placa do caminhao selecionado, em leitura: o texto da opcao fica
          cortado no select em telas estreitas e a placa e o dado que o fiscal
          confere contra o veiculo no patio.
        -->
        <div>
          <InputLabel value="Placa" />
          <p class="mt-1 flex items-center rounded-md border border-slate-200 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-800/40 px-3 py-2 font-mono font-semibold text-slate-900 dark:text-slate-100">
            {{ caminhaoSelecionado?.placa || '—' }}
          </p>
        </div>
        <div>
          <InputLabel for="capacidade" value="Capacidade (m³) *" />
          <TextInput id="capacidade" v-model="form.capacidade" type="number" step="0.01" min="0.01" max="999.99" class="mt-1 block w-full" required />
          <InputError :message="form.errors.capacidade" class="mt-2" />
        </div>
        <div>
          <InputLabel for="modelo" value="Modelo" />
          <TextInput id="modelo" v-model="form.modelo" type="text" class="mt-1 block w-full" maxlength="50" />
          <InputError :message="form.errors.modelo" class="mt-2" />
        </div>
        <div>
          <InputLabel for="cor" value="Cor" />
          <TextInput id="cor" v-model="form.cor" type="text" class="mt-1 block w-full" maxlength="30" />
          <InputError :message="form.errors.cor" class="mt-2" />
        </div>
        <div>
          <InputLabel for="ano" value="Ano" />
          <TextInput id="ano" v-model="form.ano" type="text" class="mt-1 block w-full" maxlength="4" placeholder="2024" />
          <InputError :message="form.errors.ano" class="mt-2" />
        </div>
      </div>
    </div>

    <VistoriaChecklistGroup
      titulo="Condições Estruturais"
      descricao="27 itens de inspeção mecânica, elétrica e de segurança"
      :itens="itensEstruturais"
      :form="form"
      @update="(k, v) => form[k] = v"
      @update-obs="(k, v) => form[`${k}_obs`] = v"
    />

    <VistoriaChecklistGroup
      titulo="Condições do Tanque"
      descricao="7 itens específicos do tanque-pipa"
      :itens="itensTanque"
      :form="form"
      @update="(k, v) => form[k] = v"
      @update-obs="(k, v) => form[`${k}_obs`] = v"
    />

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Parecer Final</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label
            v-for="p in pareceres"
            :key="p.value"
            class="cursor-pointer rounded-lg border-2 p-4 flex items-center gap-3 transition"
            :class="form.parecer === p.value
              ? (p.value === 'aprovada' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20')
              : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/30 hover:border-slate-400'"
          >
            <input type="radio" name="parecer" :value="p.value" v-model="form.parecer" class="text-blue-600 focus:ring-blue-500" />
            <span class="font-medium" :class="p.value === 'aprovada' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">
              {{ p.label }}
            </span>
          </label>
        </div>
        <InputError :message="form.errors.parecer" class="mt-2" />
        <div>
          <InputLabel for="observacoes" value="Observações gerais" />
          <textarea id="observacoes" v-model="form.observacoes" rows="3" maxlength="2000" class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" />
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
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import VistoriaChecklistGroup from '@/Components/Organisms/Tdap/VistoriaChecklistGroup.vue';

const props = defineProps({
  form: { type: Object, required: true },
  caminhoes: { type: Array, default: () => [] },
  pareceres: { type: Array, default: () => [] },
  itensEstruturais: { type: Array, default: () => [] },
  itensTanque: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

const caminhaoSelecionado = computed(
  () => props.caminhoes.find(c => Number(c.id) === Number(props.form.placa_id)) ?? null,
);

function onCaminhaoChange() {
  const c = caminhaoSelecionado.value;
  if (c) {
    if (!props.form.modelo) props.form.modelo = c.modelo || '';
    if (!props.form.cor)    props.form.cor    = c.cor || '';
    if (!props.form.ano)    props.form.ano    = c.ano || '';
    if (!props.form.capacidade) props.form.capacidade = c.capacidade_m3 || '';
  }
}
</script>
