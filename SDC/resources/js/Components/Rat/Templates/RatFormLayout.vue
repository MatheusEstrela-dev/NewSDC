<template>
  <div class="rat-container">
    <RatHeader
      :rat="rat"
      :last-update="lastUpdate"
      :view-only="viewOnly"
      :is-create="isCreate"
    />

    <RatTabs
      :active-tab="activeTab"
      :tabs="tabConfig"
      @tab-change="$emit('tab-change', $event)"
    >
      <template #default="slotProps">
        <slot v-bind="slotProps" />
      </template>
    </RatTabs>
  </div>
</template>

<script setup>
import RatHeader from '@/Components/Rat/RatHeader.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';

defineProps({
  rat: {
    type: Object,
    required: true,
  },
  tabConfig: {
    type: Array,
    default: () => [],
  },
  activeTab: {
    type: Number,
    default: 1,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  isCreate: {
    type: Boolean,
    default: false,
  },
  lastUpdate: {
    type: String,
    default: null,
  },
});

defineEmits(['tab-change']);
</script>
