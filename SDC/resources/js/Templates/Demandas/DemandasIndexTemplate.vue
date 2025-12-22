<template>
  <div class="demandas-container">
    <div class="mb-6">
      <div class="rounded-2xl bg-gradient-to-r from-red-700/18 to-rose-600/10 bg-slate-900/25 border border-slate-700/30 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-full bg-transparent border border-slate-600/40 flex items-center justify-center">
              <CheckBadgeIcon class="w-6 h-6 text-slate-200" />
            </div>
            <div>
              <Heading level="2" color="white" class="mb-1">
                Demandas
              </Heading>
              <Text size="sm" color="muted">
                Módulo em construção — estrutura pronta para evoluir com Atomic Design e DDD.
              </Text>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left" disabled>
              Nova Demanda
            </Button>
          </div>
        </div>
      </div>
    </div>

    <CardBase variant="default" padding="lg" class="bg-slate-800/60 border-slate-700/50">
      <Heading level="4" color="white" class="mb-2">Próximos passos</Heading>
      <Text size="sm" color="muted">
        Aqui entraremos com listagem, filtros retráteis, cards e paginação (mock) — seguindo o mesmo padrão de RAT/PAE.
      </Text>
    </CardBase>
  </div>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
</script>

<style scoped>
.demandas-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .demandas-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .demandas-container {
    padding: 2rem 2.5rem;
  }
}
</style>


