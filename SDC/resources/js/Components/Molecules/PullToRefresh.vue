<script setup>
import { computed, watch, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import { usePullToRefresh } from '@/Composables/usePullToRefresh';
import { useMobile } from '@/Composables/useMobile';

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  threshold: {
    type: Number,
    default: 80,
  },
});

const emit = defineEmits(['refresh']);

const { isMobile, isTablet } = useMobile();

const handleRefresh = async () => {
  emit('refresh');

  return new Promise((resolve) => {
    router.reload({
      preserveScroll: true,
      preserveState: false,
      onFinish: () => {
        resolve();
      },
    });
  });
};

const isDisabled = computed(() => {
  return props.disabled || (!isMobile.value && !isTablet.value);
});

const disabledRef = ref(isDisabled.value);
watch(isDisabled, (val) => {
  disabledRef.value = val;
}, { immediate: true });

const { isPulling, isRefreshing, pullDistance, pullProgress } = usePullToRefresh({
  threshold: props.threshold,
  onRefresh: handleRefresh,
  disabledRef,
});

const indicatorStyle = computed(() => {
  if (!isPulling.value && !isRefreshing.value) {
    return {
      transform: 'translateY(-100%)',
      opacity: 0,
    };
  }

  return {
    transform: `translateY(${pullDistance.value - 48}px)`,
    opacity: Math.min(pullProgress.value, 1),
  };
});

const iconRotation = computed(() => {
  if (isRefreshing.value) return 0;
  return pullProgress.value * 180;
});
</script>

<template>
  <div
    v-if="isMobile || isTablet"
    class="pull-to-refresh-indicator"
    :style="indicatorStyle"
  >
    <div
      class="pull-to-refresh-spinner"
      :class="{ 'is-refreshing': isRefreshing }"
    >
      <ArrowPathIcon
        class="pull-to-refresh-icon"
        :style="{ transform: `rotate(${iconRotation}deg)` }"
        :class="{ 'animate-spin': isRefreshing }"
      />
    </div>
    <span v-if="pullProgress >= 1 && !isRefreshing" class="pull-to-refresh-text">
      Solte para atualizar
    </span>
    <span v-else-if="isRefreshing" class="pull-to-refresh-text">
      Atualizando...
    </span>
  </div>
</template>

<style scoped>
.pull-to-refresh-indicator {
  position: fixed;
  top: 0;
  left: 50%;
  transform: translateX(-50%) translateY(-100%);
  z-index: 99;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.75rem;
  padding-top: calc(0.75rem + env(safe-area-inset-top, 0));
  transition: opacity 0.2s ease;
  pointer-events: none;
}

.pull-to-refresh-spinner {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 50%;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  transition: transform 0.2s ease;
}

:global(.dark) .pull-to-refresh-spinner {
  background: #1e293b;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
}

.pull-to-refresh-spinner.is-refreshing {
  transform: scale(1.1);
}

.pull-to-refresh-icon {
  width: 24px;
  height: 24px;
  color: #2563eb;
  transition: transform 0.1s ease;
}

:global(.dark) .pull-to-refresh-icon {
  color: #60a5fa;
}

.pull-to-refresh-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
  white-space: nowrap;
}

:global(.dark) .pull-to-refresh-text {
  color: #94a3b8;
}

/* Animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
