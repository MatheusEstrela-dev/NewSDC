<template>
  <Modal :show="show" @close="handleClose" max-width="lg">
    <div class="bg-slate-900 border border-slate-700/60 rounded-lg">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-700/50 bg-gradient-to-r from-blue-700/10 to-cyan-600/5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-blue-500/15 text-blue-400">
              <PlusIcon class="w-5 h-5" />
            </div>
            <div>
              <Heading :level="3" color="white" class="mb-0">Novo Plantão</Heading>
              <Text size="xs" color="muted">Inicie as atividades do dia</Text>
            </div>
          </div>
          <button
            @click="handleClose"
            class="text-slate-400 hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-700/50"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="px-6 py-5">
        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Data e Período -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2">
                <Text size="xs" weight="semibold" color="muted" class="uppercase tracking-wider">
                  Data do Plantão
                </Text>
              </label>
              <DatePicker v-model="form.data" required class="w-full" />
            </div>

            <div>
              <label class="block mb-2">
                <Text size="xs" weight="semibold" color="muted" class="uppercase tracking-wider">
                  Período
                </Text>
              </label>
              <select
                v-model="form.periodo"
                required
                class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
              >
                <option v-for="opt in periodos" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Plantonista Responsável -->
          <div>
            <Text size="xs" weight="semibold" color="muted" class="uppercase tracking-wider mb-3 block">
              Plantonista Responsável
            </Text>
            <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-slate-800/60 border border-slate-700/60">
              <div class="w-8 h-8 rounded-full bg-blue-600/30 flex items-center justify-center text-xs font-bold text-blue-400 ring-2 ring-blue-500/25">
                {{ userInitials }}
              </div>
              <Text size="sm" color="white" weight="medium">{{ userName }}</Text>
            </div>
          </div>

          <!-- Info Alert -->
          <div class="flex gap-3 p-3.5 rounded-lg bg-blue-500/8 border border-blue-500/15">
            <div class="flex-shrink-0 mt-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <Text size="xs" color="muted" class="leading-relaxed">
              Ao abrir o plantão, os módulos de lançamento e boletim serão
              habilitados para o seu usuário. Todos os dados serão vinculados ao seu
              registro.
            </Text>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-slate-700/50 bg-slate-900/50 flex items-center justify-end gap-3">
        <Button
          variant="ghost"
          size="md"
          @click="handleClose"
        >
          Cancelar
        </Button>
        <Button
          variant="danger"
          size="md"
          @click="handleSubmit"
          :disabled="!isFormValid"
        >
          Confirmar Abertura
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import Modal from '@/Components/Modal.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  periodos: {
    type: Array,
    default: () => [
      { value: 'DIURNO', label: '07:00hs as 19:00hs' },
      { value: 'NOTURNO', label: '19:00hs as 07:00hs' },
      { value: 'EXTRAORDINARIO', label: 'Extraordinário' },
    ],
  },
});

const emit = defineEmits(['close', 'submit']);

const today = new Date().toISOString().split('T')[0];

const form = ref({
  data: today,
  periodo: 'DIURNO',
});

const userName = computed(() => page.props.auth?.user?.name || 'Usuário');

const userInitials = computed(() => {
  const name = userName.value;
  const parts = name.split(' ');
  return (
    (parts[0]?.[0] || '') + (parts[parts.length - 1]?.[0] || '')
  ).toUpperCase();
});

const isFormValid = computed(() => {
  return form.value.data !== '' && form.value.periodo !== '';
});

const handleClose = () => {
  resetForm();
  emit('close');
};

const handleSubmit = () => {
  if (!isFormValid.value) return;

  emit('submit', {
    data: form.value.data,
    periodo: form.value.periodo,
    plantonista_nome: userName.value,
  });

  resetForm();
};

const resetForm = () => {
  form.value = {
    data: today,
    periodo: 'DIURNO',
  };
};
</script>
