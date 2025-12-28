<template>
  <header
    class="fixed top-0 right-0 h-16 z-40 transition-all duration-300 bg-white border-b border-slate-200 shadow-sm"
    :class="isCollapsed ? 'left-20' : 'left-[280px]'"
  >
    <div class="flex items-center justify-between h-full px-8 gap-8">
      <!-- Logo SDC -->
      <div class="flex items-center">
        <span class="text-xl font-bold text-slate-900 tracking-wider">SDC</span>
      </div>

      <!-- Search Bar -->
      <div class="flex-1 max-w-2xl mx-auto">
        <div class="relative flex items-center">
          <svg class="absolute left-4 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="searchQuery"
            @input="handleSearch"
            placeholder="Buscar protocolo, município..."
            class="w-full pl-11 pr-4 py-2.5 text-sm rounded-lg outline-none transition-all
                   bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400
                   focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10"
          />
        </div>
      </div>

      <!-- Right Section - User Info & Actions -->
      <div class="flex items-center gap-4 flex-shrink-0">
        <!-- Notifications Dropdown -->
        <Dropdown align="right" width="96" contentClasses="p-0 overflow-hidden">
          <template #trigger>
            <button
              class="relative flex items-center justify-center w-10 h-10 rounded-lg transition-all
                     text-slate-600 hover:bg-slate-100 hover:text-slate-700"
              title="Notificações"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="hasUnread"
                class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full animate-pulse"
              ></span>
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full min-w-[18px] text-center"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>
          </template>
          <template #content>
            <NotificationsPanel />
          </template>
        </Dropdown>

        <!-- Theme Toggle -->
        <button
          @click="toggleTheme"
          class="flex items-center justify-center w-10 h-10 rounded-lg transition-all
                 text-slate-600 hover:bg-slate-100 hover:text-slate-700"
          title="Alternar tema"
        >
          <svg v-if="isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Sol (tema claro) -->
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Lua (tema escuro) -->
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </button>

        <!-- Settings Dropdown -->
        <Dropdown align="right" width="80" contentClasses="p-0 overflow-hidden">
          <template #trigger>
            <button
              class="flex items-center justify-center w-10 h-10 rounded-lg transition-all
                     text-slate-600 hover:bg-slate-100 hover:text-slate-700"
              title="Configurações"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </template>
          <template #content>
            <SettingsPanel />
          </template>
        </Dropdown>

        <!-- User Menu -->
        <div class="relative user-menu">
          <button
            @click="toggleUserMenu"
            class="flex items-center gap-2 px-3 py-1.5 rounded-lg transition-all
                   border border-slate-200 hover:bg-slate-50 hover:border-slate-300"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-semibold">
              {{ userInitials }}
            </div>
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Overlay para fechar ao clicar fora -->
          <div
            v-show="showUserMenu"
            class="fixed inset-0 z-[100]"
            @click="showUserMenu = false"
          ></div>

          <!-- Dropdown Menu -->
          <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
          >
            <div
              v-show="showUserMenu"
              class="absolute top-full right-0 mt-2 w-70 rounded-xl shadow-lg border overflow-hidden z-[101]
                     bg-white border-slate-200"
            >
            <div class="flex items-center gap-4 p-4 bg-slate-50">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold flex-shrink-0">
                {{ userInitials }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-slate-900 truncate">{{ userName }}</div>
                <div class="text-xs text-slate-600 truncate">{{ userEmail }}</div>
              </div>
            </div>
            <div class="h-px bg-slate-200 my-2"></div>
            <Link
              :href="route('profile.edit')"
              class="flex items-center gap-3 px-4 py-3 text-sm transition-all
                     text-slate-700 hover:bg-slate-100 hover:text-slate-900"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Meu Perfil
            </Link>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="w-full flex items-center gap-3 px-4 py-3 text-sm transition-all
                     text-red-600 hover:bg-red-50 hover:text-red-700"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Sair
            </Link>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, inject, Transition } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import { useNotifications } from '@/composables/useNotifications';
import Dropdown from './Dropdown.vue';
import NotificationsPanel from './Organisms/Notifications/NotificationsPanel.vue';
import SettingsPanel from './Organisms/Settings/SettingsPanel.vue';

const page = usePage();
const showUserMenu = ref(false);
const searchQuery = ref('');

// Injetar o estado da sidebar
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));
const isCollapsed = computed(() => sidebarCollapsed.value);

// Usar composable de tema
const { isDarkMode, toggleTheme } = useTheme();

// Usar composable de notificações
const { unreadCount, hasUnread } = useNotifications();

function handleSearch() {
  // TODO: Implementar lógica de busca
  if (searchQuery.value.length > 2) {
    // Implementar busca
    console.log('Buscando:', searchQuery.value);
  }
}


const userName = computed(() => page.props.auth?.user?.name || 'Usuário');
const userEmail = computed(() => page.props.auth?.user?.email || '');
const userInitials = computed(() => {
  const name = userName.value;
  return name
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
});

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value;
}
</script>

<style scoped>
/* Responsive */
@media (max-width: 1024px) {
  header {
    left: 0 !important;
  }
}
</style>

