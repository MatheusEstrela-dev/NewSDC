<template>
  <div class="tdap-dashboard-container">
    <TdapPageHeader />
    <TdapStatisticsCards :statistics="statistics" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Card: Ver todos os produtos -->
      <CardBase variant="default" padding="lg" class="hover:border-cyan-500/30 dark:hover:border-cyan-500/30 hover:border-cyan-300 transition-all cursor-pointer" @click="handleNavigate('tdap.products.index')">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-lg bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-1 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300">
            <CubeIcon class="w-6 h-6" />
          </div>
          <div class="flex-1">
            <Heading :level="3" color="default" class="mb-1">Total de Produtos</Heading>
            <Text size="lg" color="default" weight="bold">{{ statistics.total_produtos || 0 }}</Text>
            <Text size="sm" color="muted" class="mt-1">Ver todos os produtos →</Text>
          </div>
        </div>
      </CardBase>

      <!-- Card: Recebimentos Finalizados -->
      <CardBase variant="default" padding="lg" class="hover:border-emerald-500/30 dark:hover:border-emerald-500/30 hover:border-emerald-300 transition-all cursor-pointer" @click="handleNavigate('tdap.recebimentos.index')">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-lg bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-1 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300">
            <CheckCircleIcon class="w-6 h-6" />
          </div>
          <div class="flex-1">
            <Heading :level="3" color="default" class="mb-1">Recebimentos Finalizados</Heading>
            <Text size="lg" color="default" weight="bold">{{ statistics.recebimentos_finalizados || 0 }}</Text>
            <Text size="sm" color="muted" class="mt-1">{{ statistics.recebimentos_pendentes || 0 }} pendentes</Text>
          </div>
        </div>
      </CardBase>

      <!-- Card: Movimentações (Mês) -->
      <CardBase variant="default" padding="lg" class="hover:border-amber-500/30 dark:hover:border-amber-500/30 hover:border-amber-300 transition-all cursor-pointer" @click="handleNavigate('tdap.movimentacoes.index')">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-lg bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-1 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300">
            <ArrowsRightLeftIcon class="w-6 h-6" />
          </div>
          <div class="flex-1">
            <Heading :level="3" color="default" class="mb-1">Movimentações (Mês)</Heading>
            <Text size="lg" color="default" weight="bold">{{ statistics.movimentacoes_mes || 0 }}</Text>
            <Text size="sm" color="muted" class="mt-1">0 entradas, 0 saídas</Text>
          </div>
        </div>
      </CardBase>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapStatisticsCards from '@/Components/Organisms/Tdap/Statistics/TdapStatisticsCards.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
    default: () => ({
      total_produtos: 0,
      recebimentos_finalizados: 0,
      recebimentos_pendentes: 0,
      movimentacoes_mes: 0,
    }),
  },
});

function handleNavigate(routeName) {
  router.visit(route(routeName));
}
</script>

<style scoped>
.tdap-dashboard-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .tdap-dashboard-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .tdap-dashboard-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .tdap-dashboard-container {
    padding: 2rem 3rem;
  }
}
</style>
