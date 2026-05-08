<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <CardBase>
      <Text variant="muted" size="sm">Total</Text>
      <Heading level="2" class="mt-1">{{ statistics.total ?? 0 }}</Heading>
    </CardBase>
    <CardBase>
      <Text variant="muted" size="sm">Ativas</Text>
      <Heading level="2" class="mt-1 text-emerald-300">{{ statistics.ativas ?? 0 }}</Heading>
    </CardBase>
    <CardBase>
      <Text variant="muted" size="sm">Pendentes</Text>
      <Heading level="2" class="mt-1 text-amber-300">{{ statistics.pendentes ?? 0 }}</Heading>
    </CardBase>
    <CardBase>
      <Text variant="muted" size="sm">Municipios</Text>
      <Heading level="2" class="mt-1">{{ statistics.municipios ?? 0 }}</Heading>
    </CardBase>
  </div>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({ total: 0, ativas: 0, pendentes: 0, municipios: 0 }),
  },
});
</script>
