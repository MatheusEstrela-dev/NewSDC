<template>
  <div @click.stop class="notifications-panel w-full sm:w-96 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden flex flex-col max-h-[calc(100dvh-5rem)] sm:max-h-[85vh]">
    <!-- Header -->
    <div class="px-4 py-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700 flex items-center justify-between sticky top-0 z-10">
      <div class="flex items-center gap-2">
        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Notificações</h3>
        <span v-if="unreadCount > 0" class="bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold shadow-sm">
            {{ unreadCount }}
        </span>
      </div>
      <div class="flex items-center gap-3">
        <button
            @click="showPreferences = !showPreferences"
            class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
            title="Configurações de Notificação"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </button>
        <button
            v-if="hasUnread && !showPreferences"
            @click="markAllAsRead"
            class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition-colors"
        >
            Ler todas
        </button>
      </div>
    </div>

    <!-- Content: Preferences Mode -->
    <div v-if="showPreferences" class="flex-1 overflow-y-auto p-4 bg-slate-50 dark:bg-slate-800/50">
        <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4">Preferências de Alerta</h4>

        <div v-if="carregandoPreferencias" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
        </div>

        <div v-else class="space-y-1">
            <div
                v-for="modulo in preferencias"
                :key="modulo.module"
                class="flex items-start justify-between gap-3 py-2 px-2 -mx-2 rounded-lg hover:bg-white dark:hover:bg-slate-800/60 transition-colors"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ modulo.label }}</p>
                    <p v-if="modulo.descricao" class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">
                        {{ modulo.descricao }}
                    </p>
                </div>

                <!-- Cada toggle salva sozinho: sem botao de confirmar, o estado da
                     tela e sempre o estado do backend. -->
                <ToggleInput
                    :model-value="modulo.canal_sistema"
                    class="mt-0.5 flex-shrink-0"
                    @update:model-value="alternarModulo(modulo, $event)"
                />
            </div>
        </div>

        <p v-if="erroPreferencias" class="mt-3 text-xs text-red-500 dark:text-red-400">{{ erroPreferencias }}</p>
        <p v-else-if="salvandoModulo" class="mt-3 text-xs text-slate-500 dark:text-slate-400">Salvando...</p>

        <button
            class="mt-6 w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors"
            @click="showPreferences = false"
        >
            Voltar às notificações
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
          :key="notification.id"
          :notification="notification"
          @mark-read="handleMarkRead"
        />
      </template>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-4">
            <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <p class="text-slate-700 dark:text-slate-300 text-sm font-medium">Tudo tranquilo por aqui!</p>
        <p class="text-slate-500 dark:text-slate-500 text-xs mt-1">Você não tem novas notificações.</p>
      </div>
    </div>

    <!-- Footer (Only in list mode) -->
    <div v-if="!showPreferences && notifications.length > 0" class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
      <Link
        href="/notificacoes"
        class="w-full py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 active:bg-blue-700 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2"
      >
        Ver Histórico Completo
      </Link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useNotifications } from '@/Composables/useNotifications';
import { useNotificationPreferences } from '@/Composables/useNotificationPreferences';
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';
import NotificationItem from './NotificationItem.vue';

const showPreferences = ref(false);

// Estado compartilhado com o modal de Configuracoes. Este painel edita so o canal
// do sino; os demais canais ficam na tela cheia, que tem espaco para explicar o
// que falta em cada um.
const {
    modulos: preferencias,
    carregando: carregandoPreferencias,
    salvando: salvandoModulo,
    erro: erroPreferencias,
    carregar: carregarPreferencias,
    alternar,
} = useNotificationPreferences();

const alternarModulo = (modulo, valor) => alternar(modulo, 'canal_sistema', valor);

// Só busca as preferências quando o usuário abre a engrenagem. Sem cache local:
// quem decide se ja tem dado e o composable, entao uma alteracao feita no modal
// aparece aqui na hora, sem F5.
watch(showPreferences, (aberto) => {
    if (aberto) carregarPreferencias();
});

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

// O agrupamento e uma unica linha no banco, entao marcar como lida e sempre uma
// operacao sobre um id. markGroupAsRead segue disponivel para acoes em lote.
const handleMarkRead = (notification) => {
    markAsRead(notification.id);
};

onMounted(() => {
    // start() decide sozinho entre websocket e polling conforme update_mode,
    // e cai para polling se o Reverb nao subir.
    startPolling();
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