<template>
  <RatCollapsibleSection
    section-id="atendimento"
    title="Atendimento"
    subtitle="Data e horário do fato e atividades"
    icon-class="rat-section-icon-default"
    :default-expanded="true"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </template>

    <div class="rat-grid-3">
      <!-- Data/Hora do Fato -->
      <div>
        <label class="form-label">
          Data/Hora do Fato <span class="text-red-400">*</span>
        </label>
        <div class="flex gap-2">
          <div class="flex-1 min-w-0">
            <DatePicker
              :model-value="getDate(modelValue.data_fato)"
              @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_fato: combine(v, getTime(modelValue.data_fato)) })"
            />
          </div>
          <TimePicker
            :model-value="getTime(modelValue.data_fato)"
            @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_fato: combine(getDate(modelValue.data_fato), v) })"
            extra-class="w-28"
          />
        </div>
        <button type="button" class="hoje-btn" @click="emit('update:modelValue', { ...modelValue, data_fato: combine(today(), getTime(modelValue.data_fato) || '00:00') })">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Hoje
        </button>
      </div>

      <!-- Data/Hora Início da Atividade -->
      <div>
        <label class="form-label">
          Data/Hora Início da Atividade <span class="text-red-400">*</span>
        </label>
        <div class="flex gap-2">
          <div class="flex-1 min-w-0">
            <DatePicker
              :model-value="getDate(modelValue.data_inicio_atividade)"
              @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_inicio_atividade: combine(v, getTime(modelValue.data_inicio_atividade)) })"
            />
          </div>
          <TimePicker
            :model-value="getTime(modelValue.data_inicio_atividade)"
            @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_inicio_atividade: combine(getDate(modelValue.data_inicio_atividade), v) })"
            extra-class="w-28"
          />
        </div>
        <button type="button" class="hoje-btn" @click="emit('update:modelValue', { ...modelValue, data_inicio_atividade: combine(today(), getTime(modelValue.data_inicio_atividade) || '00:00') })">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Hoje
        </button>
      </div>

      <!-- Data/Hora Término da Atividade -->
      <div>
        <label class="form-label">
          Data/Hora Término da Atividade <span class="text-red-400">*</span>
        </label>
        <div class="flex gap-2">
          <div class="flex-1 min-w-0">
            <DatePicker
              :model-value="getDate(modelValue.data_termino_atividade)"
              @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_termino_atividade: combine(v, getTime(modelValue.data_termino_atividade)) })"
            />
          </div>
          <TimePicker
            :model-value="getTime(modelValue.data_termino_atividade)"
            @update:model-value="(v) => emit('update:modelValue', { ...modelValue, data_termino_atividade: combine(getDate(modelValue.data_termino_atividade), v) })"
            extra-class="w-28"
          />
        </div>
        <button type="button" class="hoje-btn" @click="emit('update:modelValue', { ...modelValue, data_termino_atividade: combine(today(), getTime(modelValue.data_termino_atividade) || '00:00') })">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Hoje
        </button>
      </div>
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import RatCollapsibleSection from './RatCollapsibleSection.vue';
import DatePicker from '../../Form/DatePicker.vue';
import TimePicker from '../../Form/TimePicker.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      data_fato: '',
      data_inicio_atividade: '',
      data_termino_atividade: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

function getDate(datetime) {
  if (!datetime) return '';
  return datetime.includes('T') ? datetime.split('T')[0] : datetime;
}

function getTime(datetime) {
  if (!datetime) return '';
  return datetime.includes('T') ? datetime.split('T')[1] : '';
}

function combine(date, time) {
  if (!date) return '';
  return time ? `${date}T${time}` : date;
}

function today() {
  return new Date().toISOString().split('T')[0];
}
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}

.hoje-btn {
  @apply mt-2 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded
    border border-emerald-500/50
    text-emerald-600 dark:text-emerald-400
    bg-emerald-50 dark:bg-emerald-900/20
    hover:bg-emerald-100 dark:hover:bg-emerald-900/30
    transition-colors duration-150;
}
</style>
