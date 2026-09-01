<script setup>
/**
 * Abas do RAT.
 *
 * O desenho virou padrao de projeto e mora em `Molecules/Navigation/ModuleTabs`.
 * Este arquivo continua existindo porque o RAT o consome por nome em varios
 * lugares -- e so repassa props, evento e slot.
 */
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';

defineProps({
  activeTab: {
    type: [Number, String],
    default: 1,
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['tab-change']);
</script>

<template>
  <ModuleTabs
    :active-tab="activeTab"
    :tabs="tabs"
    @tab-change="$emit('tab-change', $event)"
  >
    <template #default="slotProps">
      <slot v-bind="slotProps" />
    </template>
  </ModuleTabs>
</template>
