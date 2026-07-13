<template>
  <div class="flex flex-wrap items-center gap-1">
    <Badge
      v-for="(valor, titulo) in totals"
      :key="titulo"
      variant="info"
      size="sm"
    >
      {{ titulo }}: <strong class="ml-1">{{ valor }}</strong>
    </Badge>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { formatCurrency, formatNumber } from '@/Composables/ui/useDesastreMask';

const props = defineProps({
  desastre: {
    type: Object,
    required: true,
  },
});

const totals = computed(() => {
  const acc = {};
  const types = {};

  (props.desastre.items ?? []).forEach((item) => {
    (item.campos ?? []).forEach((campo) => {
      if (campo.tipo !== 'number' && campo.tipo !== 'currency') return;

      if (!(campo.titulo in acc)) {
        acc[campo.titulo] = 0;
        types[campo.titulo] = campo.tipo;
      }

      const raw = String(campo.valor ?? '0').replace(/\D/g, '');
      if (!raw) return;

      if (campo.tipo === 'currency') {
        acc[campo.titulo] += parseFloat(raw) / 100;
      } else {
        acc[campo.titulo] += parseInt(raw, 10);
      }
    });
  });

  const result = {};
  Object.keys(acc).forEach((titulo) => {
    result[titulo] = types[titulo] === 'currency'
      ? formatCurrency(acc[titulo])
      : formatNumber(String(acc[titulo]));
  });

  return result;
});
</script>
