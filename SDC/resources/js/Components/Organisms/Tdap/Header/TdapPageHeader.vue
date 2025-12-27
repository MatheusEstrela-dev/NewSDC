<template>
  <div class="mb-6">
    <div
      class="rounded-2xl bg-gradient-to-r from-purple-700/18 to-indigo-600/10 bg-slate-900/25 border border-slate-700/30 p-6"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-full bg-transparent border border-slate-600/40 flex items-center justify-center">
            <CubeIcon class="w-6 h-6 text-slate-200" />
          </div>
          <div>
            <Heading :level="2" color="white" class="mb-1">
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
