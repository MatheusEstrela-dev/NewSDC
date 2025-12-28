<template>
  <div
    class="notification-item group cursor-pointer transition-all duration-200"
    :class="{
      'unread': !notification.read,
      'bg-slate-800/50 hover:bg-slate-700/60': notification.read,
      'bg-slate-800 hover:bg-slate-700': !notification.read
    }"
    @click="handleClick"
  >
    <!-- Left border for unread -->
    <div
      v-if="!notification.read"
      class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"
    ></div>

    <!-- Content Container -->
    <div class="flex items-start gap-3 p-3 pl-4">
      <!-- Icon -->
      <div
        class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
        :class="getIconBgClass(notification.type)"
      >
        <!-- Componentes de ícones -->
        <CheckCircleIcon
          v-if="notification.type === 'success'"
          class="w-5 h-5"
          :class="getIconColorClass(notification.type)"
        />
        <ExclamationTriangleIcon
          v-else-if="notification.type === 'warning'"
          class="w-5 h-5"
          :class="getIconColorClass(notification.type)"
        />
        <!-- SVG Inline para Error -->
        <svg
          v-else-if="notification.type === 'error'"
          class="w-5 h-5"
          :class="getIconColorClass(notification.type)"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <!-- SVG Inline para Info (padrão) -->
        <svg
          v-else
          class="w-5 h-5"
          :class="getIconColorClass(notification.type)"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-semibold text-slate-100 truncate">
          {{ notification.title }}
        </h4>
        <p class="text-sm text-slate-400 line-clamp-2 mt-0.5">
          {{ notification.message }}
        </p>
        <span class="text-xs text-slate-500 mt-1 block">
          {{ formattedTime }}
        </span>
      </div>

      <!-- Unread indicator dot -->
      <div v-if="!notification.read" class="flex-shrink-0">
        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

// Icons disponíveis
import CheckCircleIcon from '../../Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '../../Icons/ExclamationTriangleIcon.vue';

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['click']);

const { formatTimeAgo } = useNotifications();

const formattedTime = computed(() => {
  return formatTimeAgo(props.notification.created_at);
});

const getIcon = (type) => {
  const icons = {
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    error: 'error',
    info: 'info'
  };
  return icons[type] || 'info';
};

const getIconBgClass = (type) => {
  const classes = {
    success: 'bg-green-500/10',
    warning: 'bg-yellow-500/10',
    error: 'bg-red-500/10',
    info: 'bg-blue-500/10'
  };
  return classes[type] || classes.info;
};

const getIconColorClass = (type) => {
  const classes = {
    success: 'text-green-400',
    warning: 'text-yellow-400',
    error: 'text-red-400',
    info: 'text-blue-400'
  };
  return classes[type] || classes.info;
};

const handleClick = () => {
  emit('click', props.notification);
};
</script>

<style scoped>
.notification-item {
  position: relative;
  border-bottom: 1px solid rgb(51 65 85 / 0.3); /* slate-700/30 */
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item.unread {
  border-left: 4px solid #3b82f6; /* blue-500 */
}

/* Line clamp utility (se não estiver disponível no Tailwind) */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
