<template>
  <div class="rat-section-card">
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Local do Fato</h3>
        <p class="text-xs text-slate-500 mt-0.5">Identificação geográfica da ocorrência</p>
      </div>
    </div>

    <div class="rat-section-content">
      <div class="rat-grid-3">
        <FormSelect
          label="País"
          v-model="localData.pais"
          :options="paisOptions"
          required
          :error="errors.pais"
        />

        <FormSelect
          label="Estado/UF"
          v-model="localData.uf"
          :options="ufOptions"
          required
          :error="errors.uf"
          @update:modelValue="handleUfChange"
        />

        <FormSelect
          label="Município"
          v-model="localData.municipio_id"
          :options="municipioOptions"
          :disabled="!localData.uf"
          required
          :error="errors.municipio_id"
          placeholder="Selecione o estado primeiro"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import { useLocationData } from '@/Composables/location';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      pais: 'BR',
      uf: '',
      municipio_id: null,
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({
  municipio_nome: '',
  ...props.modelValue,
});

// Sincroniza local -> pai apenas se houver mudança real
watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

// Sincroniza pai -> local apenas se os dados externos mudarem
watch(
  () => props.modelValue,
  (nv) => {
    if (nv && JSON.stringify(nv) !== JSON.stringify(localData.value)) {
      localData.value = JSON.parse(JSON.stringify(nv));
    }
  },
  { deep: true }
);

const {
  paisOptions,
  ufOptions,
  municipios,
  municipioOptions,
  loadMunicipios
} = useLocationData();

// Resolve municipality name from IBGE code whenever id or options change
watch(
  [() => localData.value.municipio_id, municipios],
  ([id]) => {
    if (!id || !municipios.value.length) return;
    const match = municipios.value.find(m => String(m.id) === String(id));
    if (match && localData.value.municipio_nome !== match.nome) {
      localData.value.municipio_nome = match.nome;
    }
  }
);

const handleUfChange = (uf) => {
  localData.value.municipio_id = null;
  if (uf) {
    loadMunicipios(uf);
  }
};

// Carrega municípios se já tiver UF selecionada
watch(() => props.modelValue.uf, (newUf) => {
  if (newUf) {
    loadMunicipios(newUf);
  }
}, { immediate: true });
</script>
