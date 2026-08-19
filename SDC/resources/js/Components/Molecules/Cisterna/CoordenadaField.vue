<template>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <FormField
      :model-value="latitude"
      label="Latitude"
      type="text"
      inputmode="decimal"
      placeholder="-16.393269"
      required
      :error="error?.latitude"
      :hint="dica"
      @update:model-value="$emit('update:latitude', $event)"
    />

    <FormField
      :model-value="longitude"
      label="Longitude"
      type="text"
      inputmode="decimal"
      placeholder="-43.940933"
      required
      :error="error?.longitude"
      @update:model-value="$emit('update:longitude', $event)"
    />

    <!--
      Aviso, nao bloqueio. O backend valida a faixa mundial (-90..90 e
      -180..180); este alerta e sobre plausibilidade: o programa e da CEDEC-MG e
      os 55 municipios atendidos estao todos dentro do estado.
      A migracao mostrou por que isso importa -- a coluna do legado era texto
      livre com 21 formatos, e havia latitude com valor de longitude, coordenada
      truncada e eixos trocados.
    -->
    <p v-if="foraDeMinas" class="sm:col-span-2 text-xs text-amber-600 dark:text-amber-400">
      Coordenada fora da faixa de Minas Gerais (latitude -14 a -23, longitude -40 a -51).
      Confira se os eixos nao foram trocados.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';

const props = defineProps({
  latitude: { type: [String, Number], default: '' },
  longitude: { type: [String, Number], default: '' },
  error: { type: Object, default: null },
});

defineEmits(['update:latitude', 'update:longitude']);

const dica = 'Ponto decimal, nao virgula. Ex.: -16.393269';

// Minas com um grau de folga, os mesmos limites do parser do ETL.
const LIMITES = { latMin: -24, latMax: -13, lonMin: -52, lonMax: -39 };

const foraDeMinas = computed(() => {
  const lat = Number(props.latitude);
  const lon = Number(props.longitude);

  // So avisa quando os dois estao preenchidos e numericos: campo pela metade
  // ainda esta sendo digitado.
  if (!Number.isFinite(lat) || !Number.isFinite(lon) || lat === 0 || lon === 0) {
    return false;
  }

  return lat < LIMITES.latMin || lat > LIMITES.latMax
    || lon < LIMITES.lonMin || lon > LIMITES.lonMax;
});
</script>
