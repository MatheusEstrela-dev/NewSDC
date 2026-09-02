<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import Modal from '@/Components/Modal.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // Ja mapeado para {value, label} pelo ReservaIndexController.
  viaturas: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'saved']);

const VAZIO = {
  viatura_id: '',
  inicio_previsto: '',
  fim_previsto: '',
  destino: '',
  motivo: '',
};

const form = useForm({ ...VAZIO });

// Conflito de agenda e demais guardas do ReservaViaturaService chegam na chave
// "reserva": e aqui que o agente le "Ja reservada por Fulano de 14:00 a 18:00",
// em vez de numa pagina de erro.
watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;
    form.clearErrors();
    form.reset();
  },
);

function handleClose() {
  if (form.processing) return;
  emit('close');
}

function handleSubmit() {
  form
    .transform((data) => ({
      viatura_id: data.viatura_id || null,
      inicio_previsto: data.inicio_previsto || null,
      fim_previsto: data.fim_previsto || null,
      destino: data.destino || null,
      motivo: data.motivo || null,
    }))
    .post(route('plantao.reservas.store'), {
      preserveScroll: true,
      onSuccess: () => emit('saved'),
    });
}
</script>

<template>
  <Modal :show="show" max-width="lg" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <CalendarIcon class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Nova reserva</h2>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            A reserva fica no seu nome. So voce retira a chave dela.
          </p>
        </div>

        <button
          type="button"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          aria-label="Fechar"
          @click="handleClose"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="max-h-[70vh] space-y-5 overflow-y-auto px-5 py-4">
        <div
          v-if="form.errors.reserva"
          class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ form.errors.reserva }}
        </div>

        <FormSelect
          v-model="form.viatura_id"
          label="Viatura"
          required
          :options="viaturas"
          :error="form.errors.viatura_id"
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField
            v-model="form.inicio_previsto"
            type="datetime-local"
            label="Inicio"
            required
            :error="form.errors.inicio_previsto"
          />

          <FormField
            v-model="form.fim_previsto"
            type="datetime-local"
            label="Fim"
            required
            :error="form.errors.fim_previsto"
          />
        </div>

        <FormField
          v-model="form.destino"
          label="Destino"
          :error="form.errors.destino"
        />

        <FormField
          v-model="form.motivo"
          label="Motivo"
          :error="form.errors.motivo"
        />

        <p class="text-xs text-slate-500 dark:text-slate-400">
          Destino e motivo ja preenchem o formulario da retirada da chave.
        </p>
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <FormActions
          submit-label="Reservar"
          :loading="form.processing"
          @cancel="handleClose"
          @submit="handleSubmit"
        />
      </footer>
    </form>
  </Modal>
</template>
