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
            <CheckBadgeIcon class="w-6 h-6 text-slate-600 dark:text-slate-200" />
          </div>
          <div>
            <Heading :level="2" class="mb-1">
              Demandas
            </Heading>
            <Text size="sm" color="muted">
              Gerencie todas as tarefas e chamados do sistema
            </Text>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Botão Exportar -->
          <Button variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="handleOpenExport">
            <span class="hidden sm:inline">Exportar</span>
          </Button>
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="handleOpenModal">
            <span class="hidden sm:inline">Nova Demanda</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Heading from '../../../Atoms/Typography/Heading.vue';
import Text from '../../../Atoms/Typography/Text.vue';
import Button from '../../../Atoms/Button/Button.vue';
import PlusIcon from '../../../Icons/PlusIcon.vue';
import CheckBadgeIcon from '../../../Icons/CheckBadgeIcon.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const emit = defineEmits(['open-modal', 'open-export']);

const handleOpenModal = () => {
  emit('open-modal');
};

const handleOpenExport = () => {
  emit('open-export');
};
</script>
