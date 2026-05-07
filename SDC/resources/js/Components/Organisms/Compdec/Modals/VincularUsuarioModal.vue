<template>
  <Modal :show="show" max-width="lg" @close="handleClose">
    <div class="bg-slate-900 border border-slate-700">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-emerald-700/10 to-teal-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-emerald-500/15 text-emerald-300">
              <LinkIcon class="w-5 h-5" />
            </div>
            <Heading :level="3" color="white">Vincular Usuario ao Orgao</Heading>
          </div>
          <button
            class="text-slate-400 hover:text-slate-200 transition-colors"
            @click="handleClose"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <form class="px-6 py-5" @submit.prevent="handleSubmit">
        <div class="space-y-5">
          <FormField
            v-model.number="form.usuario_id"
            type="number"
            label="ID do Usuario"
            required
            :error="errors.usuario_id"
            hint="ID do usuario do sistema a ser vinculado a este orgao"
          />

          <div>
            <Label required>Funcao no orgao</Label>
            <select
              v-model="form.funcao"
              required
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
            >
              <option value="coordenador">Coordenador</option>
              <option value="agente">Agente</option>
              <option value="tecnico">Tecnico</option>
              <option value="apoio">Apoio</option>
            </select>
            <p v-if="errors.funcao" class="mt-1 text-xs text-red-400">{{ errors.funcao }}</p>
          </div>

          <ToggleField
            v-model="form.is_principal"
            label="Orgao principal do usuario"
            hint="Marcar este orgao como principal substitui o vinculo principal anterior"
          />
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-700">
          <Button variant="ghost" type="button" @click="handleClose">
            Cancelar
          </Button>
          <Button variant="primary" type="submit" :loading="loading">
            Vincular
          </Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { XMarkIcon, LinkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  orgaoId: {
    type: [Number, String],
    required: true,
  },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});

const form = reactive({
  usuario_id: '',
  funcao: 'agente',
  is_principal: false,
});

watch(
  () => props.show,
  (visible) => {
    if (!visible) return;
    errors.value = {};
    Object.assign(form, { usuario_id: '', funcao: 'agente', is_principal: false });
  },
);

function handleClose() {
  if (loading.value) return;
  emit('close');
}

function handleSubmit() {
  loading.value = true;
  errors.value = {};

  router.post(
    route('compdec.usuarios.vincular', props.orgaoId),
    { ...form },
    {
      preserveScroll: true,
      onSuccess: () => emit('saved'),
      onError: (e) => { errors.value = e; },
      onFinish: () => { loading.value = false; },
    },
  );
}
</script>
