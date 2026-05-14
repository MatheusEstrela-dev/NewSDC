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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="agua_prevista" value="Água prevista (m³) *" />
            <TextInput id="agua_prevista" v-model="form.agua_prevista" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
            <InputError :message="form.errors.agua_prevista" class="mt-2" />
          </div>
          <div>
            <InputLabel for="num_viagens" value="Número de viagens *" />
            <TextInput id="num_viagens" v-model="form.num_viagens" type="number" min="1" max="10000" class="mt-1 block w-full" required />
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
import { computed, watch } from 'vue';
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
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
  cronograma_id: props.cronogramaId,
  caminhao_id: null,
  agua_prevista: '',
  num_viagens: 1,
  ordem: 0,
});

watch(() => props.cronogramaId, (v) => { form.cronograma_id = v; });

const caminhoesDisponiveis = computed(() => {
  const set = new Set((props.jaAlocados || []).map(Number));
  return props.caminhoes.filter(c => !set.has(Number(c.id)));
});

function submit() {
  form.post(route('tdap.crono_caminhoes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('caminhao_id', 'agua_prevista', 'num_viagens', 'ordem');
      emit('success');
      emit('close');
    },
  });
}
</script>
