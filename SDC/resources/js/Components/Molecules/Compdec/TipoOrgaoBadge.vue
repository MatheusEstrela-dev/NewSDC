<template>
  <Badge :cor="cor" size="pill" class="whitespace-nowrap">
    <component :is="iconComponent" class="w-3 h-3 mr-1" />
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Tipo de orgao: CEDEC, REDEC ou COMPDEC.
 *
 * Categoria, nao estado -- por isso cor explicita em vez de variant semantico.
 * A receita de pill (fundo/texto/borda, light e dark) vem do Badge; antes estava
 * escrita a mao aqui, como em outros nove badges de modulo.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';
import BuildingIcon from '../../Icons/BuildingIcon.vue';
import BuildingOfficeIcon from '../../Icons/BuildingOfficeIcon.vue';

const props = defineProps({
  tipo: {
    type: String,
    required: true,
  },
});

const config = {
  cedec: { label: 'CEDEC', cor: 'purple', icon: BuildingOfficeIcon },
  redec: { label: 'REDEC', cor: 'amber', icon: BuildingOfficeIcon },
  compdec: { label: 'COMPDEC', cor: 'blue', icon: BuildingIcon },
};

const label = computed(() => config[props.tipo]?.label || (props.tipo || '').toUpperCase());
const cor = computed(() => config[props.tipo]?.cor ?? 'slate');
const iconComponent = computed(() => config[props.tipo]?.icon || BuildingIcon);
</script>
