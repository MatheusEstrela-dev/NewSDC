<script setup>
/**
 * Abas do formulario do PAE.
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
    default: 1,
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
  </ModuleTabs>
</template>
