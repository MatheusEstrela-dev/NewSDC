<template>
  <div class="w-full min-w-0 pb-6">
    <DetalheHeader
      :title="titulo"
      :icon="icone"
      :subtitle="subtitulo"
      :status-label="statusLabel"
      :status-variant="statusVariant"
      context-label="Protocolo"
      :context-value="protocolo.num_protocolo"
    >
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </DetalheHeader>

    <div v-if="$slots.avisos" class="mb-4 space-y-3">
      <slot name="avisos" />
    </div>

    <div v-if="$slots.topo" class="mb-4 space-y-4">
      <slot name="topo" />
    </div>

    <ModuleTabs v-if="abas.length" :tabs="abas" :active-tab="aba" @tab-change="$emit('update:aba', $event)">
      <template #default="{ activeTab }">
        <slot :aba="activeTab" />
      </template>
    </ModuleTabs>
    <slot v-else :aba="null" />

    <div v-if="$slots.rodape" class="mt-4 space-y-4">
      <slot name="rodape" />
    </div>
  </div>
</template>

<script setup>
import DetalheHeader from '@/Components/Molecules/DetalheHeader.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';

defineProps({
  titulo: { type: String, required: true },
  icone: { type: [Object, Function], default: undefined },
  subtitulo: { type: String, default: '' },
  protocolo: { type: Object, required: true },
  statusLabel: { type: String, default: '' },
  statusVariant: { type: String, default: 'default' },
  abas: { type: Array, default: () => [] },
  aba: { type: [String, Number], default: null },
});

defineEmits(['update:aba']);
</script>
