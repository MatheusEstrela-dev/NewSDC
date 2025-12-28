<template>
  <div class="notifications-panel bg-slate-900 rounded-lg shadow-xl overflow-hidden">
    <!-- Header -->
    <div class="header px-4 py-3 border-b border-slate-700/50 flex items-center justify-between">
      <h3 class="text-base font-semibold text-slate-100">Notificações</h3>
      <button
        v-if="hasUnread"
        @click="handleMarkAllAsRead"
        class="text-xs text-blue-400 hover:text-blue-300 transition-colors duration-200 font-medium"
      >
        Marcar todas como lida
      </button>
    </div>

    <!-- List Container -->
    <div class="notifications-list max-h-[400px] overflow-y-auto">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>

      <!-- Notifications List -->
      <template v-else-if="notifications.length > 0">
        <NotificationItem
          v-for="notification in notifications"
          :key="notification.id"
          :notification="notification"
          @click="handleNotificationClick(notification)"
        />
      </template>

      <!-- Empty State -->
      <div v-else class="empty-state py-12 px-6 text-center">
        <div class="flex justify-center mb-3">
          <svg
            class="w-16 h-16 text-slate-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9.143 17.082a24.248 24.248 0 003.844.148m-3.844-.148a23.856 23.856 0 01-5.455-1.31 8.964 8.964 0 002.3-5.542m3.155 6.852a3 3 0 005.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 003.536-1.003A8.967 8.967 0 0118 9.75V9A6 6 0 006.53 6.53m10.245 10.245L6.53 6.53M3 3l3.53 3.53"
            />
          </svg>
        </div>
        <p class="text-slate-400 text-sm font-medium">Nenhuma notificação</p>
        <p class="text-slate-500 text-xs mt-1">Você está em dia!</p>
      </div>
    </div>

    <!-- Footer -->
    <div
      v-if="notifications.length > 0"
      class="footer px-4 py-3 border-t border-slate-700/50 bg-slate-800/50"
    >
      <button
        @click="handleViewAll"
        class="w-full text-sm text-blue-400 hover:text-blue-300 transition-colors duration-200 font-medium flex items-center justify-center gap-1.5"
      >
        <span>Ver todas as notificações</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useNotifications } from '@/composables/useNotifications';
import { router } from '@inertiajs/vue3';
import NotificationItem from './NotificationItem.vue';

const {
  notifications,
  isLoading,
  hasUnread,
  markAsRead,
  markAllAsRead,
} = useNotifications();

const handleMarkAllAsRead = () => {
  markAllAsRead();
};

const handleNotificationClick = (notification) => {
  // Marca como lida
  if (!notification.read) {
    markAsRead(notification.id);
  }

  // Navega para o link se existir
  if (notification.link) {
    router.visit(notification.link);
  }
};

const handleViewAll = () => {
  // TODO: Implementar rota de notificações quando criada
  console.log('Ver todas as notificações');
};
</script>

<style scoped>
.notifications-panel {
  width: 384px; /* 96 * 4px = 384px (Tailwind w-96) */
  max-width: calc(100vw - 32px); /* Mobile friendly */
}

/* Custom scrollbar para dark theme */
.notifications-list::-webkit-scrollbar {
  width: 6px;
}

.notifications-list::-webkit-scrollbar-track {
  background: rgb(15 23 42 / 0.5); /* slate-900/50 */
  border-radius: 3px;
}

.notifications-list::-webkit-scrollbar-thumb {
  background: rgb(71 85 105 / 0.5); /* slate-600/50 */
  border-radius: 3px;
}

.notifications-list::-webkit-scrollbar-thumb:hover {
  background: rgb(71 85 105 / 0.7); /* slate-600/70 */
}

/* Firefox scrollbar */
.notifications-list {
  scrollbar-width: thin;
  scrollbar-color: rgb(71 85 105 / 0.5) rgb(15 23 42 / 0.5);
}
</style>
