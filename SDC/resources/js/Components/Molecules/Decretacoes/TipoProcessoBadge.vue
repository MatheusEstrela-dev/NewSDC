<template>
  <Badge :cor="cor" size="pill" class="whitespace-nowrap">
    <component :is="iconComponent" class="w-3 h-3 mr-1" />
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Esfera do processo: Municipal ou Estadual.
 *
 * Categoria, nao estado -- cor explicita. Consolidar azul/roxo em semanticas
 * mudaria o significado: nao ha juizo de progresso nem de risco aqui.
 * A receita de pill vem do Badge; antes estava escrita a mao neste arquivo.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';
import BuildingIcon from '../../Icons/BuildingIcon.vue';
import BuildingOfficeIcon from '../../Icons/BuildingOfficeIcon.vue';

const props = defineProps({
  tipo: {
    type: String,
    required: true,
    // Sem validator: existe fallback para tipos nao mapeados.
  },
});

const config = {
  MUNICIPAL: { label: 'Municipal', cor: 'blue', icon: BuildingIcon },
  ESTADUAL: { label: 'Estadual', cor: 'purple', icon: BuildingOfficeIcon },
};

const label = computed(() => config[props.tipo]?.label || props.tipo);
const cor = computed(() => config[props.tipo]?.cor ?? 'slate');
const iconComponent = computed(() => config[props.tipo]?.icon || BuildingIcon);
</script>
