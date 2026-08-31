<template>
  <!--
    Colapsavel como as outras secoes do formulario.

    Esta e a "Local do Fato" eram `div.rat-section-card` cru, sem chevron e
    SEMPRE abertas: no telefone a sanfona fechava as demais e estas seguiam
    expandidas, o que fazia o modo parecer quebrado.
  -->
  <RatCollapsibleSection
    section-id="endereco-detalhado"
    title="Endereço Detalhado"
    subtitle="Informações completas sobre a localização do fato"
    icon-class="rat-section-icon-default"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
    </template>

      <!-- Linha 1: CEP, Logradouro, Bairro, Complemento -->
      <div class="rat-grid-4">
        <FormField
          label="CEP"
          v-model="localData.cep"
          mask="cep"
          placeholder="00000-000"
          hint="Preencha para buscar o endereço"
          :error="errors.cep"
          @blur="handleCepBlur"
        >
          <template #suffix>
            <button
              type="button"
              class="text-slate-400 hover:text-emerald-500 transition-colors"
              @click="handleCepBlur"
            >
              <svg v-if="isLoadingCep" class="animate-spin h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </button>
          </template>
        </FormField>

        <FormField
          label="Logradouro 1"
          v-model="localData.logradouro"
          placeholder="Ex: Rua São Paulo"
          required
          :error="errors.logradouro"
        />

        <FormField
          label="Bairro"
          v-model="localData.bairro"
          placeholder="Ex: Centro"
          :error="errors.bairro"
        />

        <FormField
          label="Complemento"
          v-model="localData.complemento"
          placeholder="Ex: Apto 101"
          :error="errors.complemento"
        />
      </div>

      <!-- Linha 2: Número, KM, Cruzamento, Ponto de Referência -->
      <div class="rat-grid-4">
        <FormField
          label="Número"
          v-model="localData.numero"
          placeholder="Ex: 123"
          :error="errors.numero"
        />

        <FormField
          label="KM"
          v-model="localData.km"
          placeholder="Ex: 45"
          :error="errors.km"
        />

        <FormField
          label="Cruzamento"
          v-model="localData.cruzamento"
          placeholder="Ex: Rua das Flores"
          :error="errors.cruzamento"
        />

        <FormField
          label="Ponto de Referência"
          v-model="localData.ponto_referencia"
          placeholder="Ao lado da padaria, próximo ao mercado"
          :error="errors.ponto_referencia"
        />
      </div>

      <!-- Linha 3: Tipo de Localização -->
      <div class="rat-grid-2">
        <FormSelect
          label="Tipo de Localização"
          v-model="localData.tipo_localizacao"
          :options="tipoLocalizacaoOptions"
          placeholder="Selecione uma opção"
          required
          :error="errors.tipo_localizacao"
        />

        <div class="flex items-end">
          <button
            v-if="localData.latitude && localData.longitude"
            type="button"
            @click="viewOnMap"
            class="w-full px-4 py-2.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20 transition-all duration-200 text-sm font-medium flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Ver no Mapa
          </button>
        </div>
      </div>

      <input type="hidden" v-model="localData.latitude" />
      <input type="hidden" v-model="localData.longitude" />
  </RatCollapsibleSection>
</template>

<script setup>
import RatCollapsibleSection from './RatCollapsibleSection.vue';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import { useCep } from '@/Composables/location';
import { watch, ref } from 'vue';

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

const { buscarCep, isLoading: isLoadingCep } = useCep();

const aplicarResultadoCep = (resultado) => {
  if (!resultado) return;
  localData.value = {
    ...localData.value,
    logradouro: resultado.logradouro || localData.value.logradouro,
    bairro:     resultado.bairro     || localData.value.bairro,
    complemento: resultado.complemento || localData.value.complemento,
    latitude:   resultado.latitude,
    longitude:  resultado.longitude,
  };
  emit('location-updated', {
    uf:        resultado.uf,
    municipio: resultado.localidade,
  });
};

watch(
  () => props.modelValue?.cep,
  async (newCep) => {
    if (!newCep) return;
    const cepLimpo = newCep.replace(/\D/g, '');
    if (cepLimpo.length !== 8) return;
    aplicarResultadoCep(await buscarCep(cepLimpo));
  }
);

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
  aplicarResultadoCep(await buscarCep(cepLimpo));
};

const viewOnMap = () => {
  const { latitude, longitude } = localData.value;
  window.open(`https://www.google.com/maps?q=${latitude},${longitude}`, '_blank');
};
</script>
