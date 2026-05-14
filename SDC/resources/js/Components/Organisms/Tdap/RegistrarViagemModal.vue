<template>
  <Modal :show="show" @close="$emit('close')">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-1">Registrar viagem</h3>
      <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
        Caminhão <span class="font-mono font-semibold">{{ placa }}</span>
      </p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <InputLabel for="data_registro" value="Data e hora da viagem *" />
          <TextInput id="data_registro" v-model="form.data_registro" type="datetime-local" class="mt-1 block w-full" required />
          <InputError :message="form.errors.data_registro" class="mt-2" />
        </div>

        <div>
          <InputLabel for="obs" value="Observação" />
          <textarea
            id="obs"
            v-model="form.obs"
            rows="3"
            maxlength="1000"
            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
            placeholder="Detalhes da viagem (opcional)"
          />
          <InputError :message="form.errors.obs" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
          <SecondaryButton type="button" @click="$emit('close')">Cancelar</SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            {{ form.processing ? 'Registrando...' : 'Registrar' }}
          </PrimaryButton>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  show:            { type: Boolean, default: false },
  cronoCaminhaoId: { type: [Number, String], required: true },
  placa:           { type: String, default: '' },
});

const emit = defineEmits(['close', 'success']);

const nowLocal = () => {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const form = useForm({
  crono_caminhao_id: props.cronoCaminhaoId,
  data_registro: nowLocal(),
  obs: '',
});

watch(() => props.cronoCaminhaoId, (v) => { form.crono_caminhao_id = v; });
watch(() => props.show, (v) => { if (v) { form.data_registro = nowLocal(); form.obs = ''; } });

function submit() {
  form.post(route('tdap.viagens.store'), {
    preserveScroll: true,
    onSuccess: () => {
      emit('success');
      emit('close');
    },
  });
}
</script>
