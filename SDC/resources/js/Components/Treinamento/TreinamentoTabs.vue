<script setup>
/**
 * Abas do treinamento.
 *
 * O desenho das abas e padrao de projeto e mora em
 * `Molecules/Navigation/ModuleTabs` (regra 22). Este arquivo continua
 * existindo porque o modulo o consome pelo nome; ele so repassa props, evento
 * e slot.
 */
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';

defineProps({
  activeTab: {
    type: [Number, String],
    default: null,
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['tab-change']);
</script>

<template>
  <ModuleTabs
    :active-tab="activeTab"
    :tabs="tabs"
    @tab-change="(id) => emit('tab-change', id)"
  >
    <template #default="slotProps">
      <slot v-bind="slotProps" />
    </template>
  </ModuleTabs>
</template>
