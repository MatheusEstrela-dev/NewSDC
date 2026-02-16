<template>
  <div @click.stop class="notifications-panel w-96 max-w-[calc(100vw-1rem)] bg-slate-900 rounded-xl shadow-2xl border border-slate-700/50 overflow-hidden flex flex-col max-h-[85vh]">
    <!-- Header -->
    <div class="px-4 py-3 bg-slate-900/95 backdrop-blur-sm border-b border-slate-700 flex items-center justify-between sticky top-0 z-10">
      <div class="flex items-center gap-2">
        <h3 class="text-sm font-bold text-slate-100">Notificações</h3>
        <span v-if="unreadCount > 0" class="bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold shadow-sm">
            {{ unreadCount }}
        </span>
      </div>
      <div class="flex items-center gap-3">
        <button
            @click="showPreferences = !showPreferences"
            class="text-slate-400 hover:text-slate-200 transition-colors"
            title="Configurações de Notificação"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </button>
        <button
            v-if="hasUnread && !showPreferences"
            @click="markAllAsRead"
            class="text-xs text-blue-400 hover:text-blue-300 font-medium transition-colors"
        >
            Ler todas
        </button>
      </div>
    </div>

    <!-- Content: Preferences Mode -->
    <div v-if="showPreferences" class="flex-1 overflow-y-auto p-4 bg-slate-800/50">
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Preferências de Alerta</h4>
        <div class="space-y-3">
            <label class="flex items-center justify-between cursor-pointer group">
                <span class="text-sm text-slate-300 group-hover:text-slate-100">Novos RATs</span>
                <input type="checkbox" checked class="rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0">
            </label>
            <label class="flex items-center justify-between cursor-pointer group">
                <span class="text-sm text-slate-300 group-hover:text-slate-100">Demandas Atribuídas</span>
                <input type="checkbox" checked class="rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0">
            </label>
            <label class="flex items-center justify-between cursor-pointer group">
                <span class="text-sm text-slate-300 group-hover:text-slate-100">Prazos e Vencimentos</span>
                <input type="checkbox" checked class="rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0">
            </label>
            <label class="flex items-center justify-between cursor-pointer group">
                <span class="text-sm text-slate-300 group-hover:text-slate-100">Comentários</span>
                <input type="checkbox" class="rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0">
            </label>
        </div>
        <button 
            @click="showPreferences = false"
            class="mt-6 w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors"
        >
            Salvar Preferências
        </button>
    </div>

    <!-- Content: Notifications List -->
    <div v-else class="flex-1 overflow-y-auto min-h-[300px]">
      <div v-if="isLoading && notifications.length === 0" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
      </div>

      <template v-else-if="notifications.length > 0">
        <NotificationItem
          v-for="notification in notifications"
          :key="notification.isGroup ? `group_${notification.ids[0]}` : notification.id"
          :notification="notification"
          @mark-read="handleMarkRead"
        />
      </template>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="bg-slate-800 p-4 rounded-full mb-4">
            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <p class="text-slate-300 text-sm font-medium">Tudo tranquilo por aqui!</p>
        <p class="text-slate-500 text-xs mt-1">Você não tem novas notificações.</p>
      </div>
    </div>

    <!-- Footer (Only in list mode) -->
    <div v-if="!showPreferences && notifications.length > 0" class="p-3 bg-slate-900 border-t border-slate-700">
      <button class="w-full py-2 text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors flex items-center justify-center gap-2">
        Ver Histórico Completo
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import NotificationItem from './NotificationItem.vue';

const showPreferences = ref(false);

const {
  notifications,
  isLoading,
  unreadCount,
  hasUnread,
  markAsRead,
  markGroupAsRead,
  markAllAsRead,
  startPolling,
  stopPolling
} = useNotifications();

const handleMarkRead = (notification) => {
    if (notification.isGroup) {
        markGroupAsRead(notification.ids);
    } else {
        markAsRead(notification.id);
    }
};

onMounted(() => {
    startPolling(15000); // Poll every 15s
});

onUnmounted(() => {
    stopPolling();
});
</script>

<style scoped>
/* Scrollbar Customization */
.overflow-y-auto::-webkit-scrollbar {
  width: 5px;
}
.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgb(51 65 85);
  border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(71 85 105);
}
</style>