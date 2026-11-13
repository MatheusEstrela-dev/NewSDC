<template>
  <div :class="isMobile ? 'flex min-w-0 flex-col gap-4' : 'grid grid-cols-[15rem_minmax(0,1fr)] items-start gap-6'">
    <PaeProtocoloPasta
      :protocolo="protocolo"
      :atual="atual"
      :subitens="subitens"
      :subitem-ativo="subitemAtivo"
      @subitem="$emit('subitem', $event)"
    />
    <div class="min-w-0">
      <slot />

      <!-- Saida unica da pasta: so navega, nao altera a situacao do protocolo. -->
      <div class="mt-6 flex justify-end">
        <Button class="w-full sm:w-auto" variant="success" size="md" :icon="CheckIcon" icon-position="left" @click="concluir">
          Concluir e voltar ao PAE
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import PaeProtocoloPasta from '@/Components/Organisms/Pae/Protocolos/PaeProtocoloPasta.vue';
import { useMobile } from '@/Composables/useMobile';
import { CheckIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

defineProps({
  protocolo: { type: Object, required: true },
  atual: { type: String, required: true },
  subitens: { type: Array, default: () => [] },
  subitemAtivo: { type: String, default: null },
});

defineEmits(['subitem']);

const { isMobile } = useMobile();

function concluir() {
  router.visit(route('pae.protocolos.index'));
}
</script>
