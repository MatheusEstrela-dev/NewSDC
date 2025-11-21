<template>
  <header class="top-bar" :class="{ 'sidebar-collapsed': isCollapsed }">
    <div class="top-bar-content">
      <!-- Logo SDC -->
      <div class="logo-section">
        <span class="sdc-text">SDC</span>
      </div>

      <!-- Search Bar -->
      <div class="search-section">
        <div class="search-container">
          <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="searchQuery"
            @input="handleSearch"
            placeholder="Buscar protocolo, município..."
            class="search-input"
          />
        </div>
      </div>

      <!-- Right Section - User Info & Actions -->
      <div class="top-bar-right">
        <!-- Notifications -->
        <button class="icon-button" title="Notificações">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span v-if="hasNotifications" class="notification-badge"></span>
        </button>

        <!-- Theme Toggle -->
        <button class="icon-button" title="Alternar tema" @click="toggleTheme">
          <svg v-if="isDarkMode" class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Sol (tema claro) -->
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Lua (tema escuro) -->
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </button>

        <!-- Settings -->
        <button class="icon-button" title="Configurações" @click="handleSettings">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>

        <!-- User Menu -->
        <div class="user-menu">
          <button class="user-button" @click="toggleUserMenu">
            <div class="user-avatar-small">{{ userInitials }}</div>
            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <!-- Dropdown Menu -->
          <div v-if="showUserMenu" class="user-dropdown">
            <div class="user-dropdown-header">
              <div class="user-avatar-medium">{{ userInitials }}</div>
              <div class="user-dropdown-info">
                <div class="user-name">{{ userName }}</div>
                <div class="user-email">{{ userEmail }}</div>
              </div>
            </div>
            <div class="user-dropdown-divider"></div>
            <Link :href="route('profile.edit')" class="user-dropdown-item">
              <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Meu Perfil
            </Link>
            <Link :href="route('logout')" method="post" as="button" class="user-dropdown-item user-dropdown-item-logout">
              <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Sair
            </Link>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const showUserMenu = ref(false);
const hasNotifications = ref(false);
const searchQuery = ref('');

// Injetar o estado da sidebar
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));
const isCollapsed = computed(() => sidebarCollapsed.value);

// Tema escuro/claro
const isDarkMode = ref(false);

// Verificar tema salvo no localStorage ou preferência do sistema
function initTheme() {
  const savedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  
  if (savedTheme) {
    isDarkMode.value = savedTheme === 'dark';
  } else {
    isDarkMode.value = prefersDark;
  }
  
  applyTheme();
}

// Aplicar tema ao documento
function applyTheme() {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
}

// Alternar tema
function toggleTheme() {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
  applyTheme();
}

// Observar mudanças no tema
watch(isDarkMode, () => {
  applyTheme();
});

onMounted(() => {
  initTheme();
});

function handleSearch() {
  // TODO: Implementar lógica de busca
  if (searchQuery.value.length > 2) {
    // Implementar busca
    console.log('Buscando:', searchQuery.value);
  }
}

function handleSettings() {
  // TODO: Implementar navegação para página de configurações
  console.log('Abrir configurações');
  // router.visit(route('settings'));
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

function closeUserMenu(event) {
  if (!event.target.closest('.user-menu')) {
    showUserMenu.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', closeUserMenu);
});

onUnmounted(() => {
  document.removeEventListener('click', closeUserMenu);
});
</script>

<style scoped>
.top-bar {
  position: fixed;
  top: 0;
  left: 280px;
  right: 0;
  height: 64px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  z-index: 40;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: left 0.3s ease;
}

.top-bar.sidebar-collapsed {
  left: 80px;
}

.top-bar-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  padding: 0 2rem;
  gap: 2rem;
}

.logo-section {
  display: flex;
  align-items: center;
}

.sdc-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: 0.05em;
}

.search-section {
  flex: 1;
  max-width: 600px;
  margin: 0 auto;
}

.search-container {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1rem;
  width: 20px;
  height: 20px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.625rem 1rem 0.625rem 2.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #ffffff;
  font-size: 0.875rem;
  color: #1e293b;
  transition: all 0.2s;
  outline: none;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-input::placeholder {
  color: #94a3b8;
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.icon-button {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.icon-button:hover {
  background: #f1f5f9;
  color: #475569;
}

.icon {
  width: 20px;
  height: 20px;
}

.notification-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.user-menu {
  position: relative;
}

.user-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  background: transparent;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
}

.user-button:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.user-avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}

.chevron-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 280px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  z-index: 50;
}

.user-dropdown-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
}

.user-avatar-medium {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  flex-shrink: 0;
}

.user-dropdown-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 0.75rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 0.5rem 0;
}

.user-dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: #475569;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
}

.user-dropdown-item:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.user-dropdown-item-logout {
  color: #ef4444;
}

.user-dropdown-item-logout:hover {
  background: #fee2e2;
  color: #dc2626;
}

.dropdown-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .top-bar {
    left: 0;
  }
  
  .top-bar-content {
    padding: 0 1rem;
  }
  
  .sdc-text {
    font-size: 1rem;
  }
}
</style>

