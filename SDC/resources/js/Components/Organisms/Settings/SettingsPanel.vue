<template>
  <div class="settings-panel bg-slate-900 rounded-lg shadow-xl overflow-hidden">
    <!-- Header -->
    <div class="header px-4 py-3 border-b border-slate-700/50">
      <h3 class="text-base font-semibold text-slate-100">Configurações Rápidas</h3>
    </div>

    <!-- Settings List -->
    <div class="settings-list py-2">
      <!-- Theme Toggle -->
      <div class="setting-item px-4 py-3 hover:bg-slate-800/50 transition-colors duration-200 flex items-center justify-between">
        <div class="setting-label flex items-center gap-3">
          <!-- Sun/Moon Icon -->
          <div class="w-5 h-5 text-slate-400">
            <svg v-if="isDarkMode" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
              />
            </svg>
            <svg v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
              />
            </svg>
          </div>
          <span class="text-sm font-medium text-slate-200">Tema Escuro</span>
        </div>

        <!-- Toggle Button -->
        <button
          @click="toggleTheme"
          class="toggle-button relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900"
          :class="isDarkMode ? 'bg-blue-600' : 'bg-slate-600'"
          role="switch"
          :aria-checked="isDarkMode"
        >
          <span
            class="toggle-slider inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200"
            :class="isDarkMode ? 'translate-x-6' : 'translate-x-1'"
          ></span>
        </button>
      </div>

      <!-- Notifications Toggle -->
      <div class="setting-item px-4 py-3 hover:bg-slate-800/50 transition-colors duration-200 flex items-center justify-between">
        <div class="setting-label flex items-center gap-3">
          <div class="w-5 h-5 text-slate-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
          </div>
          <span class="text-sm font-medium text-slate-200">Notificações</span>
        </div>

        <!-- Toggle Button -->
        <button
          @click="toggleNotifications"
          class="toggle-button relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900"
          :class="notificationsEnabled ? 'bg-blue-600' : 'bg-slate-600'"
          role="switch"
          :aria-checked="notificationsEnabled"
        >
          <span
            class="toggle-slider inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200"
            :class="notificationsEnabled ? 'translate-x-6' : 'translate-x-1'"
          ></span>
        </button>
      </div>

      <!-- Language (Future/Disabled) -->
      <div class="setting-item px-4 py-3 opacity-50 cursor-not-allowed flex items-center justify-between">
        <div class="setting-label flex items-center gap-3">
          <div class="w-5 h-5 text-slate-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"
              />
            </svg>
          </div>
          <span class="text-sm font-medium text-slate-400">Idioma</span>
        </div>
        <span class="text-xs text-slate-500 font-medium">Em breve</span>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer px-4 py-3 border-t border-slate-700/50 bg-slate-800/50">
      <button
        @click="handleViewAllSettings"
        class="w-full text-sm text-blue-400 hover:text-blue-300 transition-colors duration-200 font-medium flex items-center justify-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
        <span>Todas as configurações</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTheme } from '@/composables/useTheme';

const { isDarkMode, toggleTheme } = useTheme();

// Notifications preference
const notificationsEnabled = ref(true);

// Load from localStorage
onMounted(() => {
  const saved = localStorage.getItem('notificationsEnabled');
  if (saved !== null) {
    notificationsEnabled.value = JSON.parse(saved);
  }
});

const toggleNotifications = () => {
  notificationsEnabled.value = !notificationsEnabled.value;
  localStorage.setItem('notificationsEnabled', JSON.stringify(notificationsEnabled.value));

  // TODO: Integrar com sistema de notificações real
  // Se desativado, parar de buscar notificações
  // Se ativado, retomar polling
};

const handleViewAllSettings = () => {
  // TODO: Implementar rota de configurações quando criada
  console.log('Ver todas as configurações');
};
</script>

<style scoped>
.settings-panel {
  width: 320px;
  max-width: calc(100vw - 32px); /* Mobile friendly */
}

/* iOS-style toggle animation */
.toggle-button {
  flex-shrink: 0;
}

.toggle-slider {
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

/* Smooth transitions */
.setting-item {
  cursor: pointer;
}

.setting-item.opacity-50 {
  cursor: not-allowed;
}
</style>
