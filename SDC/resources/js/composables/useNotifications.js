import { ref, computed } from 'vue';

// Mock data - pode ser substituído por API calls
const mockNotifications = [
  {
    id: 1,
    title: 'Nova demanda atribuída',
    message: 'DEM-2025-001 foi atribuída a você',
    type: 'info',
    read: false,
    created_at: '2025-12-28T10:30:00',
    link: '/demandas/1'
  },
  {
    id: 2,
    title: 'Prazo próximo do vencimento',
    message: 'PAE-2025-048 vence em 2 dias',
    type: 'warning',
    read: false,
    created_at: '2025-12-28T09:15:00',
    link: '/pae/protocolo/48'
  },
  {
    id: 3,
    title: 'Processo finalizado',
    message: 'Decretação #123 foi aprovada',
    type: 'success',
    read: true,
    created_at: '2025-12-27T16:45:00',
    link: '/decretacoes/123'
  },
  {
    id: 4,
    title: 'Novo RAT registrado',
    message: 'RAT-2025-089 aguardando sua análise',
    type: 'info',
    read: false,
    created_at: '2025-12-27T14:20:00',
    link: '/rat/89'
  },
  {
    id: 5,
    title: 'Comentário adicionado',
    message: 'João Silva comentou na demanda DEM-2025-002',
    type: 'info',
    read: true,
    created_at: '2025-12-27T11:10:00',
    link: '/demandas/2'
  }
];

// Shared state (módulo-level para compartilhar entre instâncias)
const notifications = ref([...mockNotifications]);
const isLoading = ref(false);

export function useNotifications() {
  // Computed
  const unreadCount = computed(() =>
    notifications.value.filter(n => !n.read).length
  );

  const hasUnread = computed(() => unreadCount.value > 0);

  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.read)
  );

  const readNotifications = computed(() =>
    notifications.value.filter(n => n.read)
  );

  // Methods
  const fetchNotifications = async () => {
    isLoading.value = true;
    try {
      // TODO: API call
      // const response = await axios.get('/api/notifications');
      // notifications.value = response.data;

      // Por enquanto, usa mock - simula delay de rede
      await new Promise(resolve => setTimeout(resolve, 500));
    } catch (error) {
      console.error('Erro ao buscar notificações:', error);
    } finally {
      isLoading.value = false;
    }
  };

  const markAsRead = (notificationId) => {
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification && !notification.read) {
      notification.read = true;
      // TODO: API call para marcar como lida
      // await axios.patch(`/api/notifications/${notificationId}/read`);
    }
  };

  const markAllAsRead = () => {
    notifications.value.forEach(n => {
      n.read = true;
    });
    // TODO: API call
    // await axios.post('/api/notifications/mark-all-read');
  };

  const deleteNotification = (notificationId) => {
    const index = notifications.value.findIndex(n => n.id === notificationId);
    if (index !== -1) {
      notifications.value.splice(index, 1);
      // TODO: API call
      // await axios.delete(`/api/notifications/${notificationId}`);
    }
  };

  const addNotification = (notification) => {
    const newNotification = {
      id: Date.now(),
      read: false,
      created_at: new Date().toISOString(),
      ...notification
    };
    notifications.value.unshift(newNotification);
  };

  // Helper para formatar tempo relativo
  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
      return 'Agora mesmo';
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
      return `${diffInMinutes} min atrás`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
      return `${diffInHours}h atrás`;
    }

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
      return `${diffInDays}d atrás`;
    }

    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  return {
    // State
    notifications,
    isLoading,

    // Computed
    unreadCount,
    hasUnread,
    unreadNotifications,
    readNotifications,

    // Methods
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    addNotification,
    formatTimeAgo
  };
}
