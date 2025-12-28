<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';

const iconStatus = ref('loading'); // loading | ok | error

function handleIconLoad() {
  iconStatus.value = 'ok';
}

function handleIconError() {
  iconStatus.value = 'error';
}
</script>

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
            <!-- Tenta usar o PNG do ícone da Defesa Civil; se não existir, cai no Clipboard sem ícone quebrado -->
            <img
              src="/imgs/defesa-civil.png"
              alt="Defesa Civil"
              class="w-7 h-7 object-contain"
              :class="iconStatus === 'ok' ? 'block' : 'hidden'"
              @load="handleIconLoad"
              @error="handleIconError"
            />
            <ClipboardIcon v-if="iconStatus !== 'ok'" class="w-6 h-6 text-slate-600 dark:text-slate-200" />
          </div>
          <div>
            <Heading :level="2" color="white" class="mb-1">
              Protocolos PAE
            </Heading>
            <Text size="sm" color="muted">
              Gerencie os protocolos de análise de PAE
            </Text>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Mantém o botão para o futuro (rota de criação real) -->
          <Link :href="route('pae.index')">
            <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
              Novo Protocolo
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>


