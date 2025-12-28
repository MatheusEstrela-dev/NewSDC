<template>
  <div class="mb-6">
    <div class="rounded-2xl p-6 border
                bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30
                bg-white dark:bg-slate-900/25
                border-slate-200 dark:border-slate-700/30">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-full bg-transparent flex items-center justify-center
                      border border-slate-300 dark:border-slate-600/40">
            <CubeIcon class="w-6 h-6 text-slate-600 dark:text-slate-200" />
          </div>
          <div>
            <Heading :level="2" class="mb-1">
              Gestão de Depósito - Visão Geral
            </Heading>
            <Text size="sm" color="muted">
              Visualize e gerencie todos os produtos, recebimentos e movimentações
            </Text>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Heading from '../../../Atoms/Typography/Heading.vue';
import Text from '../../../Atoms/Typography/Text.vue';
import CubeIcon from '../../../Icons/CubeIcon.vue';
</script>
