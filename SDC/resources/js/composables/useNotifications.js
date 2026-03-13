import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// State shared across components
const notifications = ref([]);
const isLoading = ref(false);
const pollingInterval = ref(null);

export function useNotifications() {
  
  // Grouping Logic
  const groupedNotifications = computed(() => {
    const groups = {};
    const result = [];

    notifications.value.forEach(notification => {
      // If read, just show it (or archive it, depending on UX. Here we show it).
      // If unread and has group_key, try to group.
      if (!notification.read && notification.data?.group_key) {
        const key = notification.data.group_key;
        if (!groups[key]) {
          groups[key] = {
            ...notification,
            isGroup: true,
            count: 1,
            ids: [notification.id],
            // Use the latest message or a generic one
            // We'll keep the latest notification as the 'main' one
          };
          result.push(groups[key]);
        } else {
          groups[key].count++;
          groups[key].ids.push(notification.id);
          // Update time to latest
          if (new Date(notification.created_at) > new Date(groups[key].created_at)) {
             groups[key].created_at = notification.created_at;
          }
        }
      } else {
        result.push(notification);
      }
    });

    // Sort by date desc
    return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  });

  const unreadCount = computed(() => 
    notifications.value.filter(n => !n.read).length
  );

  const hasUnread = computed(() => unreadCount.value > 0);

  const fetchNotifications = async () => {
    // Only set loading on first fetch to avoid flickering during polling
    if (notifications.value.length === 0) isLoading.value = true;
    
    try {
      // In a real app with axios:
      // const response = await window.axios.get('/notifications');
      // notifications.value = response.data;
      
      // Mocking for now as requested by environment constraints
      // Simulating new notifications arriving occasionally
      // This would normally be replaced by the API call
      
      // Keep existing mock data if empty
      if (notifications.value.length === 0) {
          notifications.value = getMockData();
      }
      
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    } finally {
      isLoading.value = false;
    }
  };

  const markAsRead = async (id) => {
    // Optimistic update
    const target = notifications.value.find(n => n.id === id);
    if (target) {
        target.read = true;
        target.read_at = new Date().toISOString();
    }

    try {
        // await window.axios.put(`/notifications/${id}/read`);
    } catch (e) {
        console.error(e);
    }
  };

  const markGroupAsRead = async (ids) => {
      ids.forEach(id => {
          const target = notifications.value.find(n => n.id === id);
          if (target) target.read = true;
      });
      // await window.axios.post('/notifications/mark-group-read', { ids });
  };

  const markAllAsRead = async () => {
    notifications.value.forEach(n => n.read = true);
    // await window.axios.post('/notifications/read-all');
  };

  const startPolling = (intervalMs = 30000) => {
      if (!pollingInterval.value) {
          fetchNotifications(); // Initial fetch
          pollingInterval.value = setInterval(fetchNotifications, intervalMs);
      }
  };

  const stopPolling = () => {
      if (pollingInterval.value) {
          clearInterval(pollingInterval.value);
          pollingInterval.value = null;
      }
  };

  // Helper: Time Ago
  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // seconds

    if (diff < 60) return 'Agora';
    if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
    return `${Math.floor(diff / 86400)}d atrás`;
  };

  // Setup polling on mount if using this composable in a main component
  // Be careful not to set up multiple pollers if used in multiple places
  // Since state is global, we can manage it carefully.

  return {
    notifications: groupedNotifications, // Expose grouped list
    rawNotifications: notifications,
    isLoading,
    unreadCount,
    hasUnread,
    fetchNotifications,
    markAsRead,
    markGroupAsRead,
    markAllAsRead,
    startPolling,
    stopPolling,
    formatTimeAgo
  };
}

function getMockData() {
    return [
        {
            id: '1',
            type: 'urgent', // urgent, info, success
            data: {
                title: 'Prazo PAE Vencendo',
                message: 'O Protocolo PAE-2024-001 vence em menos de 2 horas.',
                action_url: '/pae/1',
                action_text: 'Ver Protocolo',
                group_key: null
            },
            read: false,
            created_at: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 min ago
        },
        {
            id: '2',
            type: 'info',
            data: {
                title: 'Novo Comentário',
                message: 'João Silva comentou na demanda DEM-001',
                action_url: '/demandas/1',
                group_key: 'dem_001_comments'
            },
            read: false,
            created_at: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
        },
        {
            id: '3',
            type: 'info',
            data: {
                title: 'Novo Comentário',
                message: 'Maria Souza comentou na demanda DEM-001',
                action_url: '/demandas/1',
                group_key: 'dem_001_comments'
            },
            read: false,
            created_at: new Date(Date.now() - 1000 * 60 * 55).toISOString(),
        },
        {
            id: '4',
            type: 'success',
            data: {
                title: 'RAT Aprovado',
                message: 'O Relatório RAT-2024-999 foi aprovado pela chefia.',
                action_url: '/rat/999',
                action_text: 'Baixar PDF',
                group_key: null
            },
            read: true,
            created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(),
        }
    ];
}