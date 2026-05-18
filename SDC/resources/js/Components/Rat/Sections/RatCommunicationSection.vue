<template>
  <RatCollapsibleSection
    section-id="comunicacao"
    title="Comunicação da Ocorrência"
    subtitle="Informações sobre como e quando a ocorrência foi comunicada"
    icon-class="rat-section-icon-default"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
      </svg>
    </template>

    <div class="rat-grid-2">
      <!-- Data/Hora da Comunicação -->
      <div>
        <label class="form-label">
          Data/Hora da Comunicação <span class="text-red-400">*</span>
        </label>
        <div class="flex gap-2">
          <div class="flex-1 min-w-0">
            <DatePicker
              :model-value="getDate(localData.data_comunicacao)"
              @update:model-value="(v) => updateComunicacao('date', v)"
            />
          </div>
          <TimePicker
            :model-value="getTime(localData.data_comunicacao)"
            @update:model-value="(v) => updateComunicacao('time', v)"
            extra-class="w-28"
          />
        </div>
      </div>

      <FormSelect
        label="Como foi solicitado o atendimento"
        v-model="localData.tipo_solicitacao"
        :options="tipoSolicitacaoOptions"
        placeholder="Selecione uma opção"
        required
        :error="errors.tipo_solicitacao"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import TimePicker from '@/Components/Form/TimePicker.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      data_comunicacao: '',
      tipo_solicitacao: '',
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({ ...props.modelValue });

watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

watch(
  () => props.modelValue,
  (nv) => {
    if (nv && JSON.stringify(nv) !== JSON.stringify(localData.value)) {
      localData.value = JSON.parse(JSON.stringify(nv));
    }
  },
  { deep: true }
);

function getDate(datetime) {
  if (!datetime) return '';
  return datetime.includes('T') ? datetime.split('T')[0] : datetime;
}

function getTime(datetime) {
  if (!datetime) return '';
  return datetime.includes('T') ? datetime.split('T')[1] : '';
}

function updateComunicacao(part, value) {
  const current = localData.value.data_comunicacao || '';
  const date = part === 'date' ? value : getDate(current);
  const time = part === 'time' ? value : (getTime(current) || '00:00');
  localData.value = {
    ...localData.value,
    data_comunicacao: date ? `${date}T${time}` : '',
  };
}

const tipoSolicitacaoOptions = [
  { value: 'telefone', label: 'Telefone' },
  { value: '190', label: '190 - Emergência' },
  { value: 'radio', label: 'Rádio' },
  { value: 'presencial', label: 'Presencial' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'app', label: 'Aplicativo' },
  { value: 'email', label: 'E-mail' },
  { value: 'outros', label: 'Outros' },
];
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}
</style>
