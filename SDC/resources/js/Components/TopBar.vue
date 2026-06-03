<template>
  <header
    class="fixed top-0 right-0 left-0 z-30 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm md:left-20 lg:left-[280px]"
    :class="{
      'lg:!left-20': isCollapsed
    }"
    :data-collapsed="isCollapsed"
    :style="{ paddingTop: 'max(env(safe-area-inset-top, 0px), var(--inset-top, 12px))' }"
  >
    <div class="flex items-center justify-between h-12 md:h-16 px-3 sm:px-6 lg:px-8 gap-1 sm:gap-4 lg:gap-8">
      <!-- Mobile/Tablet: Hamburger Button (visivel em telas < 1024px) -->
      <div class="flex items-center gap-3 lg:hidden flex-shrink-0">
        <HamburgerButton
          :is-open="isSidebarOpen"
          @click="toggleSidebar"
          class="text-slate-700 dark:text-slate-300"
        />
      </div>

      <!-- Spacer para balancear o layout em desktop (mesmo tamanho do hamburger) -->
      <div class="hidden lg:block w-10 flex-shrink-0"></div>

      <!-- Search Bar Trigger (Pro Mode) - visivel em tablet e desktop -->
      <div class="hidden md:flex flex-1 max-w-2xl mx-auto z-[60] items-center gap-3" data-tour="search">
        <button
          @click="openCommandPalette"
          class="relative flex items-center w-full group outline-none"
        >
          <div class="absolute inset-0 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors group-hover:border-blue-400 dark:group-hover:border-blue-500"></div>
          <svg class="absolute left-3 lg:left-4 w-4 h-4 lg:w-5 lg:h-5 text-slate-400 dark:text-slate-500 pointer-events-none group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <div
            class="w-full pl-10 lg:pl-11 pr-3 lg:pr-4 py-2 lg:py-2.5 text-xs lg:text-sm text-left text-slate-400 dark:text-slate-500 font-medium relative flex items-center justify-between"
          >
            <span>Buscar protocolo, município, demanda...</span>
            <div class="flex items-center gap-1 opacity-60">
                <kbd class="hidden lg:inline-block font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded text-[10px] leading-3 text-slate-500 dark:text-slate-400">Ctrl</kbd>
                <kbd class="hidden lg:inline-block font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded text-[10px] leading-3 text-slate-500 dark:text-slate-400">K</kbd>
            </div>
          </div>
        </button>

        <!-- AI Assistant Button (Next to Search) -->
        <button
          type="button"
          @click="showAiAssistant = true"
          class="relative z-50 flex flex-shrink-0 items-center justify-center w-10 h-10 rounded-lg transition-colors
                 hover:bg-slate-100 dark:hover:bg-slate-800 active:scale-95 group"
          title="Assistente IA - Defesa Civil"
        >
          <div class="w-9 h-9 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:border-blue-400 dark:group-hover:border-blue-500 transition-colors bg-white">
            <picture>
              <source srcset="/imgs/logo_dc.webp" type="image/webp" />
              <img
                src="/imgs/logo_dc.png"
                alt="AI"
                class="w-full h-full object-contain p-0.5"
              />
            </picture>
          </div>
        </button>
      </div>

      <!-- Mobile Only: Search Icon Button (< 768px) -->
      <button
        class="flex md:hidden items-center justify-center w-10 h-10 rounded-lg transition-colors
               text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
        title="Buscar"
        @click="openCommandPalette"
        data-tour="search"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>

      <!-- Right Section - User Info & Actions -->
      <div class="flex items-center gap-1 sm:gap-2 lg:gap-4 flex-shrink-0 relative z-40" data-tour="topbar-actions">
        <!-- Notifications Dropdown -->
        <Dropdown align="right" width="96" contentClasses="p-0 overflow-hidden" :mobileFullWidth="true">
          <template #trigger>
            <button
              class="relative flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                     text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300
                     active:scale-95"
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
          class="flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300
                 active:scale-95"
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

        <!-- Settings Button -->
        <button
          id="settings-btn"
          @click.stop="openSettings"
          class="hidden sm:flex items-center justify-center w-10 h-10 rounded-lg transition-colors
                 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300
                 active:scale-95 relative"
          title="Configurações e Preferências"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>

        <!-- User Menu -->
        <div class="relative user-menu" data-tour="user-menu">
          <button
            @click="toggleUserMenu"
            class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-lg transition-colors
                   border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600
                   active:scale-95"
          >
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-[10px] sm:text-xs font-semibold">
              {{ userInitials }}
            </div>
            <svg class="hidden sm:block w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                     bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
            >
            <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold flex-shrink-0">
                {{ userInitials }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ userName }}</div>
                <div class="text-xs text-slate-600 dark:text-slate-400 truncate">{{ userEmail }}</div>
              </div>
            </div>
            <div class="h-px bg-slate-200 dark:bg-slate-700 my-2"></div>
            <button
              @click="showProfileModal = true; showUserMenu = false"
              class="w-full flex items-center gap-3 px-4 py-3 text-sm transition-colors
                     text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Meu Perfil
            </button>
            <button
              type="button"
              :disabled="isLoggingOut"
              @click="handleLogout"
              class="w-full flex items-center gap-3 px-4 py-3 text-sm transition-colors
                     text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 dark:hover:text-red-300
                     disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              {{ isLoggingOut ? 'Saindo...' : 'Sair' }}
            </button>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </header>

  <!-- Modais fora do Header (Fragment) -->
  <UserProfileModal :show="showProfileModal" @close="showProfileModal = false" />
  <CommandPalette :is-open="isCommandPaletteOpen" @close="isCommandPaletteOpen = false" />
  <SettingsModal :is-open="isSettingsOpen" @close="isSettingsOpen = false" />
  <AiAssistantModal :show="showAiAssistant" @close="showAiAssistant = false" />
</template>

<script setup>
import { useNotifications } from '@/composables/useNotifications';
import { useTheme } from '@/composables/ui';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, inject, onMounted, onUnmounted, ref, Transition, watch } from 'vue';
import HamburgerButton from './Atoms/Button/HamburgerButton.vue';
import Dropdown from './Dropdown.vue';
import AiAssistantModal from './Organisms/AiAssistantModal.vue';
import CommandPalette from './Organisms/CommandPalette.vue';
import NotificationsPanel from './Organisms/Notifications/NotificationsPanel.vue';
import SettingsModal from './Organisms/Settings/SettingsModal.vue';
import UserProfileModal from './Organisms/UserProfileModal.vue';

// Force cache invalidation
const page = usePage();
const showUserMenu = ref(false);
const showProfileModal = ref(false);
const showAiAssistant = ref(false);
const isLoggingOut = ref(false);

// Logout robusto via router.post explicito. O <Link as=button method=post>
// dependia do Link estar visivel/montado no momento do click; com o
// dropdown sumindo no mesmo frame, o submit as vezes nao chegava a
// disparar — botao + router.post elimina o race.
function handleLogout() {
    if (isLoggingOut.value) return;
    isLoggingOut.value = true;
    showUserMenu.value = false;

    router.post(route('logout'), {}, {
        preserveScroll: false,
        preserveState: false,
        onFinish: () => {
            isLoggingOut.value = false;
        },
        onError: () => {
            // Fallback: se Inertia falhar (419/timeout/etc), forca redirect
            // hard para /login para garantir saida da sessao no client.
            window.location.assign('/login');
        },
    });
}

// Pro Mode: Command Palette State
const isCommandPaletteOpen = ref(false);

const openCommandPalette = () => {
    isCommandPaletteOpen.value = true;
};

// Pro Mode: Settings Modal State
const isSettingsOpen = ref(false);
const openSettings = (event) => {
    isSettingsOpen.value = true;
};

// Global Keyboard Shortcuts - using capture:true to intercept before browser
const handleGlobalKeydown = (e) => {
    // Ctrl+K or Cmd+K to open Command Palette
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        openCommandPalette();
        return false;
    }
};

onMounted(() => {
    // Use capture:true to intercept keydown BEFORE browser default handlers
    window.addEventListener('keydown', handleGlobalKeydown, { capture: true });
    
    // Fallback native click listener
    const btn = document.getElementById('settings-btn');
    if (btn) {
        btn.onclick = (e) => openSettings(e);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown, { capture: true });
});


// Injetar o estado da sidebar desktop
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));
const isCollapsed = computed(() => sidebarCollapsed.value);

// Injetar estados mobile
const isMobile = inject('isMobile', ref(false));
const isTablet = inject('isTablet', ref(false));
const isDesktop = inject('isDesktop', ref(true));
const isSidebarOpen = inject('isSidebarOpen', ref(false));
const toggleSidebar = inject('toggleSidebar', () => {});

// Usar composable de tema
const { isDarkMode, toggleTheme } = useTheme();

// Usar composable de notificações
const { unreadCount, hasUnread } = useNotifications();


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

// Watch for URL changes to handle profile modal opening
watch(() => page.url, (newUrl) => {
  if (typeof window === 'undefined') return;
  
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('open_profile')) {
    showProfileModal.value = true;
    
    // Clean up URL without refresh
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, '', cleanUrl);
  }
}, { immediate: true });

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value;
}
</script>

<style scoped>
/* Touch-friendly buttons on mobile */
@media (max-width: 640px) {
  button {
    min-width: 36px;
    min-height: 36px;
  }
}
</style>