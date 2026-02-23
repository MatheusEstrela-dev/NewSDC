<script setup>
import { computed } from 'vue';
import { WifiIcon, SignalSlashIcon } from '@heroicons/vue/24/outline';
import { useNetworkStatus } from '@/Composables/useNetworkStatus';

const { isOnline, connectionQuality, isSlowConnection } = useNetworkStatus();

const showIndicator = computed(() => {
  return !isOnline.value || isSlowConnection.value;
});

const indicatorConfig = computed(() => {
  if (!isOnline.value) {
    return {
      icon: SignalSlashIcon,
      message: 'Voce esta offline. Dados em cache disponiveis.',
      bgClass: 'bg-red-500 dark:bg-red-600',
      textClass: 'text-white',
    };
  }

  if (isSlowConnection.value) {
    return {
      icon: WifiIcon,
      message: 'Conexao lenta detectada. Alguns recursos podem demorar.',
      bgClass: 'bg-amber-500 dark:bg-amber-600',
      textClass: 'text-white',
    };
  }

  return null;
});
</script>

<template>
  <Transition name="slide-down">
    <div
      v-if="showIndicator && indicatorConfig"
      class="offline-indicator"
      :class="[indicatorConfig.bgClass, indicatorConfig.textClass]"
    >
      <div class="offline-indicator-content">
        <component
          :is="indicatorConfig.icon"
          class="offline-indicator-icon"
        />
        <span class="offline-indicator-text">
          {{ indicatorConfig.message }}
        </span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.offline-indicator {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  padding: 0.5rem 1rem;
  padding-top: calc(0.5rem + env(safe-area-inset-top, 0));
}

.offline-indicator-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  max-width: 100%;
}

.offline-indicator-icon {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.offline-indicator-text {
  font-size: 0.8125rem;
  font-weight: 500;
  text-align: center;
  line-height: 1.3;
}

/* Transition animations */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}

/* Ajustes para telas pequenas */
@media (max-width: 480px) {
  .offline-indicator {
    padding: 0.375rem 0.75rem;
    padding-top: calc(0.375rem + env(safe-area-inset-top, 0));
  }

  .offline-indicator-text {
    font-size: 0.75rem;
  }

  .offline-indicator-icon {
    width: 1rem;
    height: 1rem;
  }
}
</style>
