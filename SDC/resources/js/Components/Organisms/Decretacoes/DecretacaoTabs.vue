<script setup>
/**
 * Abas da decretacao.
 *
 * O desenho das abas e padrao de projeto e mora em
 * `Molecules/Navigation/ModuleTabs` (regra 22). Este arquivo continua
 * existindo porque o modulo o consome pelo nome; ele so repassa props, evento
 * e slot.
 *
 * As abas ganharam icone em `useDecretacaoTabs`: sem icone a aba viraria um
 * botao vazio no mobile, onde so a ativa mostra o nome.
 */
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';

defineProps({
  activeTab: {
    type: [Number, String],
    default: 'identificacao',
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:activeTab']);
</script>

<template>
  <ModuleTabs
    :active-tab="activeTab"
    :tabs="tabs"
    @tab-change="(id) => emit('update:activeTab', id)"
  >
    <template #default="slotProps">
      <slot v-bind="slotProps" />
    </template>
  </ModuleTabs>
</template>
