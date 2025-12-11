<template>
  <div class="rat-section-card">
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-success">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Endereço Detalhado</h3>
        <p class="text-xs text-slate-500 mt-0.5">Informações completas sobre a localização do fato</p>
      </div>
    </div>

    <div class="rat-section-content">
      <div class="space-y-6">
      <!-- Linha 1: CEP e Logradouro -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <FormField
          label="CEP"
          v-model="localData.cep"
          mask="#####-###"
          placeholder="00000-000"
          :error="errors.cep"
          @blur="handleCepBlur"
        >
          <template #suffix>
            <button
              v-if="isLoadingCep"
              type="button"
              class="text-blue-400 text-xs"
              disabled
            >
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </template>
        </FormField>

        <div class="md:col-span-3">
          <FormField
            label="Logradouro"
            v-model="localData.logradouro"
            placeholder="Rua, Avenida, Rodovia..."
            required
            :error="errors.logradouro"
          />
        </div>
      </div>

      <!-- Linha 2: Bairro, Número, Complemento -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <FormField
          label="Bairro"
          v-model="localData.bairro"
          placeholder="Nome do bairro"
          :error="errors.bairro"
        />

        <FormField
          label="Número"
          v-model="localData.numero"
          placeholder="S/N"
          :error="errors.numero"
        />

        <FormField
          label="Complemento"
          v-model="localData.complemento"
          placeholder="Apto, Bloco, Sala..."
          :error="errors.complemento"
        />
      </div>

      <!-- Linha 3: KM, Cruzamento, Ponto de Referência -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <FormField
          label="KM"
          v-model="localData.km"
          placeholder="Ex: KM 345"
          :error="errors.km"
        />

        <FormField
          label="Cruzamento"
          v-model="localData.cruzamento"
          placeholder="Cruzamento com..."
          :error="errors.cruzamento"
        />

        <FormField
          label="Ponto de Referência"
          v-model="localData.ponto_referencia"
          placeholder="Próximo a..."
          :error="errors.ponto_referencia"
        />
      </div>

      <!-- Linha 4: Tipo de Localização -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <FormSelect
          label="Tipo de Localização"
          v-model="localData.tipo_localizacao"
          :options="tipoLocalizacaoOptions"
          required
          :error="errors.tipo_localizacao"
        />

        <div class="flex items-end">
          <button
            v-if="localData.latitude && localData.longitude"
            type="button"
            @click="viewOnMap"
            class="w-full px-4 py-2.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20 transition-all duration-200 text-sm font-medium flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Ver no Mapa
          </button>
          <p v-else class="text-xs text-slate-500 px-4 py-2">
            Coordenadas não disponíveis
          </p>
        </div>
      </div>

      <!-- Coordenadas (hidden inputs) -->
      <input type="hidden" v-model="localData.latitude" />
      <input type="hidden" v-model="localData.longitude" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import { useCep } from '@/composables/useCep';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      cep: '',
      logradouro: '',
      bairro: '',
      numero: '',
      complemento: '',
      km: '',
      cruzamento: '',
      ponto_referencia: '',
      tipo_localizacao: '',
      latitude: null,
      longitude: null,
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'location-updated']);

const localData = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const { buscarCep, isLoading: isLoadingCep } = useCep();

const tipoLocalizacaoOptions = [
  { value: 'urbana', label: 'Área Urbana' },
  { value: 'rural', label: 'Área Rural' },
  { value: 'rodovia', label: 'Rodovia' },
  { value: 'estrada', label: 'Estrada Vicinal' },
  { value: 'mata', label: 'Área de Mata' },
  { value: 'montanha', label: 'Região Montanhosa' },
  { value: 'rio', label: 'Próximo a Rio/Córrego' },
  { value: 'lago', label: 'Próximo a Lago/Represa' },
  { value: 'outros', label: 'Outros' },
];

const handleCepBlur = async () => {
  if (!localData.value.cep) return;

  const cepLimpo = localData.value.cep.replace(/\D/g, '');
  if (cepLimpo.length !== 8) return;

  const resultado = await buscarCep(cepLimpo);

  if (resultado) {
    localData.value = {
      ...localData.value,
      logradouro: resultado.logradouro || localData.value.logradouro,
      bairro: resultado.bairro || localData.value.bairro,
      latitude: resultado.latitude,
      longitude: resultado.longitude,
    };

    emit('location-updated', {
      uf: resultado.uf,
      municipio: resultado.localidade,
    });
  }
};

const viewOnMap = () => {
  const { latitude, longitude } = localData.value;
  window.open(`https://www.google.com/maps?q=${latitude},${longitude}`, '_blank');
};
</script>
