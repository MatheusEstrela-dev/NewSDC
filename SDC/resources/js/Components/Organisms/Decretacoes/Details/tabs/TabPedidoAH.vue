<script setup>
import { computed } from 'vue';
import {
  LifebuoyIcon,
  InboxIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  TagIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
});

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('pt-BR');
}

function formatCurrency(value) {
  if (value === null || value === undefined) return 'R$ 0,00';
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value);
}

const pedidos = computed(() => {
  return props.processo?.pedidos_ah || [];
});

const hasPedidos = computed(() => pedidos.value.length > 0);

function getStatusClass(status) {
  const statusLower = (status || '').toLowerCase();
  if (statusLower.includes('aprovado') || statusLower.includes('concluido')) {
    return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
  }
  if (statusLower.includes('pendente') || statusLower.includes('aguardando')) {
    return 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30';
  }
  if (statusLower.includes('negado') || statusLower.includes('recusado')) {
    return 'bg-red-500/20 text-red-300 border-red-500/30';
  }
  return 'bg-slate-500/20 text-slate-300 border-slate-500/30';
}
</script>

<template>
  <div>
    <!-- Lista de Pedidos -->
    <div v-if="hasPedidos" class="space-y-4">
      <div
        v-for="pedido in pedidos"
        :key="pedido.id"
        class="p-5 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-cyan-100 dark:bg-cyan-500/20 rounded-lg">
              <LifebuoyIcon class="w-5 h-5 text-cyan-500 dark:text-cyan-400" />
            </div>
            <div>
              <p class="text-slate-800 dark:text-white font-medium">Pedido #{{ pedido.numero || pedido.id }}</p>
              <p class="text-slate-500 dark:text-slate-400 text-sm">{{ pedido.tipo || 'Ajuda Humanitaria' }}</p>
            </div>
          </div>
          <span
            class="px-2.5 py-1 text-xs font-semibold rounded-full border"
            :class="getStatusClass(pedido.status)"
          >
            {{ pedido.status || 'N/A' }}
          </span>
        </div>

        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
            <CalendarIcon class="w-4 h-4" />
            <span>{{ formatDate(pedido.data) }}</span>
          </div>
          <div v-if="pedido.valor" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
            <CurrencyDollarIcon class="w-4 h-4" />
            <span>{{ formatCurrency(pedido.valor) }}</span>
          </div>
          <div v-if="pedido.tipo" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
            <TagIcon class="w-4 h-4" />
            <span>{{ pedido.tipo }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="py-12 text-center">
      <div class="mx-auto w-16 h-16 bg-slate-200 dark:bg-slate-700/50 rounded-full flex items-center justify-center mb-4">
        <InboxIcon class="w-8 h-8 text-slate-400 dark:text-slate-500" />
      </div>
      <p class="text-slate-500 dark:text-slate-400">Nao ha pedidos de ajuda humanitaria registrados para este processo.</p>
    </div>
  </div>
</template>
