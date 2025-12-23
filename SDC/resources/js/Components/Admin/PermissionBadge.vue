<template>
  <span class="permission-badge" :class="badgeClass">
    <svg v-if="showIcon" class="badge-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
    </svg>
    <span class="badge-text">{{ label }}</span>
    <span v-if="isImmutable" class="immutable-indicator" title="Imutável">
      <svg class="immutable-icon" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
      </svg>
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  module: {
    type: String,
    default: 'general'
  },
  isImmutable: {
    type: Boolean,
    default: false
  },
  showIcon: {
    type: Boolean,
    default: false
  }
});

const moduleColors = {
  users: { bg: 'rgba(59, 130, 246, 0.1)', color: '#60a5fa' },      // Blue
  roles: { bg: 'rgba(168, 85, 247, 0.1)', color: '#a78bfa' },      // Purple
  permissions: { bg: 'rgba(236, 72, 153, 0.1)', color: '#f472b6' }, // Pink
  pae: { bg: 'rgba(34, 197, 94, 0.1)', color: '#4ade80' },         // Green
  rat: { bg: 'rgba(251, 146, 60, 0.1)', color: '#fb923c' },        // Orange
  bi: { bg: 'rgba(20, 184, 166, 0.1)', color: '#14b8a6' },         // Teal
  integrations: { bg: 'rgba(249, 115, 22, 0.1)', color: '#f97316' }, // Orange
  webhooks: { bg: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' },   // Pink
  system: { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },      // Red
  general: { bg: 'rgba(148, 163, 184, 0.1)', color: '#94a3b8' }    // Gray
};

const badgeClass = computed(() => {
  const colors = moduleColors[props.module] || moduleColors.general;
  return {
    'is-immutable': props.isImmutable
  };
});

const badgeStyle = computed(() => {
  const colors = moduleColors[props.module] || moduleColors.general;
  return {
    background: colors.bg,
    color: colors.color
  };
});
</script>

<style scoped>
.permission-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
  white-space: nowrap;
  background: v-bind('badgeStyle.background');
  color: v-bind('badgeStyle.color');
  transition: all 0.2s;
}

.permission-badge.is-immutable {
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.badge-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.badge-text {
  line-height: 1;
}

.immutable-indicator {
  display: flex;
  align-items: center;
  margin-left: 0.25rem;
}

.immutable-icon {
  width: 12px;
  height: 12px;
  color: #ef4444;
}
</style>
