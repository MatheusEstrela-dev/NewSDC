<template>
  <div class="mb-6">
    <div
      class="rounded-2xl p-6 border
             bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30
             bg-white dark:bg-slate-900/25
             border-slate-200 dark:border-slate-700/30"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-full bg-transparent flex items-center justify-center
                      border border-slate-300 dark:border-slate-600/40">
            <DocumentTextIcon class="w-6 h-6 text-slate-600 dark:text-slate-200" />
          </div>
          <div>
            <Heading :level="2" class="mb-1">
              Gestão de RAT
            </Heading>
            <Text size="sm" color="muted">
              Visualize e gerencie todos os Registros de Atendimento Técnico
            </Text>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link :href="route('rat.create')">
            <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
              Novo RAT
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Heading from '../../../Atoms/Typography/Heading.vue';
import Text from '../../../Atoms/Typography/Text.vue';
import Button from '../../../Atoms/Button/Button.vue';
import PlusIcon from '../../../Icons/PlusIcon.vue';
import DocumentTextIcon from '../../../Icons/DocumentTextIcon.vue';

</script>

