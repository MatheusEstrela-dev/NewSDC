<template>
  <Modal :show="show" @close="$emit('close')">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-1">Alocar Caminhão ao Cronograma</h3>
      <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Cronograma <span class="font-mono font-semibold">{{ cronogramaNumero }}</span></p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <InputLabel for="caminhao_id" value="Caminhão *" />
          <select
            id="caminhao_id"
            v-model="form.caminhao_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            required
          >
            <option :value="null">Selecione um caminhão</option>
            <option v-for="c in caminhoesDisponiveis" :key="c.id" :value="c.id">
              {{ c.placa }} — {{ c.marca || '' }} {{ c.modelo || '' }} ({{ Number(c.capacidade_m3).toFixed(2) }} m³)
            </option>
          </select>
          <InputError :message="form.errors.caminhao_id" class="mt-2" />
        </div>

        <div>
          <InputLabel for="comunidade_id" value="Comunidade atendida" />
          <select
            id="comunidade_id"
            v-model="form.comunidade_id"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
          >
            <option :value="null">Sem comunidade — informar os valores manualmente</option>
            <option v-for="cm in comunidades" :key="cm.id" :value="cm.id">
              {{ cm.nome }}<template v-if="cm.pop_atendida"> ({{ cm.pop_atendida }} hab.)</template>
            </option>
          </select>
          <p v-if="comunidadeSelecionada && !comunidadeSelecionada.pop_atendida" class="mt-1 text-xs text-amber-600">
            Esta comunidade não tem população atendida cadastrada — os valores seguem manuais.
          </p>
          <InputError :message="form.errors.comunidade_id" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-500 dark:text-slate-400">
            <template v-if="podeCalcular">
              Calculado: {{ consumoDiario }} L/hab/dia × {{ dias }} dias × {{ comunidadeSelecionada.pop_atendida }} hab.
            </template>
            <template v-else>
              Informe os valores manualmente.
            </template>
          </p>
          <label v-if="podeCalcular" class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer">
            <input type="checkbox" v-model="manual" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
            Manual
          </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="agua_prevista" value="Água prevista (m³) *" />
            <TextInput id="agua_prevista" v-model="form.agua_prevista" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :readonly="calculoAtivo" required />
            <InputError :message="form.errors.agua_prevista" class="mt-2" />
          </div>
          <div>
            <InputLabel for="num_viagens" value="Número de viagens *" />
            <TextInput id="num_viagens" v-model="form.num_viagens" type="number" min="1" max="10000" class="mt-1 block w-full" :readonly="calculoAtivo" required />
            <InputError :message="form.errors.num_viagens" class="mt-2" />
          </div>
        </div>

        <div>
          <InputLabel for="ordem" value="Ordem de exibição" />
          <TextInput id="ordem" v-model="form.ordem" type="number" min="0" max="255" class="mt-1 block w-full" />
          <InputError :message="form.errors.ordem" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
          <SecondaryButton type="button" @click="$emit('close')">Cancelar</SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            {{ form.processing ? 'Alocando...' : 'Alocar' }}
          </PrimaryButton>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  show:             { type: Boolean, default: false },
  cronogramaId:     { type: [Number, String], required: true },
  cronogramaNumero: { type: String, default: '' },
  caminhoes:        { type: Array, default: () => [] },
  jaAlocados:       { type: Array, default: () => [] },
  comunidades:      { type: Array, default: () => [] },
  /** Consumo por habitante/dia, em litros, vindo do cronograma. */
  consumoDiario:    { type: [Number, String], default: 20 },
  /** Dias de vigencia do cronograma. */
  dias:             { type: [Number, String], default: 30 },
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
  cronograma_id: props.cronogramaId,
  caminhao_id: null,
  comunidade_id: null,
  agua_prevista: '',
  num_viagens: 1,
  ordem: 0,
});

/** Override: quem marca assume os dois numeros na mao. */
const manual = ref(false);

watch(() => props.cronogramaId, (v) => { form.cronograma_id = v; });

const comunidadeSelecionada = computed(
  () => props.comunidades.find((c) => Number(c.id) === Number(form.comunidade_id)) ?? null,
);

const caminhaoSelecionado = computed(
  () => props.caminhoes.find((c) => Number(c.id) === Number(form.caminhao_id)) ?? null,
);

/** Só dá para calcular com comunidade populada E caminhão escolhido. */
const podeCalcular = computed(() => Boolean(
  comunidadeSelecionada.value?.pop_atendida
  && Number(caminhaoSelecionado.value?.capacidade_m3) > 0,
));

const calculoAtivo = computed(() => podeCalcular.value && !manual.value);

/**
 * A formula e a do SDC legado (cronograma/create.blade.php), mantida ao pe da
 * letra para os numeros do sistema novo baterem com os do antigo:
 *
 *   agua_prevista = (consumo_diario * dias * pop_atendida) / 1000
 *   num_viagens   = round(agua_prevista / capacidade), com piso de 1
 *
 * O consumo diario e por HABITANTE, nao do municipio inteiro -- 20 L/hab/dia e
 * a referencia de abastecimento emergencial. Tratar esse 20 como consumo total
 * era o que fazia o "fator" da tela anunciar 0,60 m3 para operacoes de
 * centenas de metros cubicos.
 *
 * O piso de 1 existe porque comunidade pequena da fracao de viagem, e meia
 * viagem nao se faz: o caminhao vai ou nao vai.
 */
function recalcular() {
  if (!calculoAtivo.value) {
    return;
  }

  const consumo = Number(props.consumoDiario) || 0;
  const dias = Number(props.dias) || 0;
  const populacao = Number(comunidadeSelecionada.value.pop_atendida) || 0;
  const capacidade = Number(caminhaoSelecionado.value.capacidade_m3) || 1;

  const aguaPrevista = (consumo * dias * populacao) / 1000;
  const viagens = Math.max(1, Math.round(aguaPrevista / capacidade));

  form.agua_prevista = aguaPrevista.toFixed(2);
  form.num_viagens = viagens;
}

watch([() => form.comunidade_id, () => form.caminhao_id, manual], recalcular);

const caminhoesDisponiveis = computed(() => {
  const set = new Set((props.jaAlocados || []).map(Number));
  return props.caminhoes.filter(c => !set.has(Number(c.id)));
});

function submit() {
  form.post(route('tdap.crono_caminhoes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('caminhao_id', 'comunidade_id', 'agua_prevista', 'num_viagens', 'ordem');
      manual.value = false;
      emit('success');
      emit('close');
    },
  });
}
</script>
