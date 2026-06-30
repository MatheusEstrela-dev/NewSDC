<template>
  <div class="mb-6">
    <div class="rounded-2xl p-6 border
                bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30
                border-slate-200 dark:border-slate-700/30">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 flex items-center justify-center shrink-0">
            <img :src="moduleIcon('demandas')" alt="Demandas" class="h-14 w-14 object-contain" />
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
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="handleOpenExport">
            <span class="hidden sm:inline">Exportar</span>
          </Button>
          <Button v-if="canCreate" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="handleOpenModal">
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
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  canCreate: {
    type: Boolean,
    default: false,
  },
  canExport: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['open-modal', 'open-export']);

const handleOpenModal = () => {
  emit('open-modal');
};

const handleOpenExport = () => {
  emit('open-export');
};
</script>
