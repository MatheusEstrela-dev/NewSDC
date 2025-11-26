<template>
  <aside class="sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <!-- Header -->
    <div class="sidebar-header">
      <div class="logo-container">
        <img
          src="/imgs/flag.png"
          alt="SDC Logo"
          class="logo-image"
        />
        <div v-show="!isCollapsed" class="logo-text">
          <div class="logo-title">SDC MG</div>
          <div class="logo-subtitle">SISTEMA INTEGRADO</div>
        </div>
      </div>
      <button
        @click="toggleSidebar"
        class="sidebar-toggle"
        :title="isCollapsed ? 'Expandir sidebar' : 'Recolher sidebar'"
      >
        <svg
          class="toggle-icon"
          :class="{ 'rotated': isCollapsed }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <!-- PRINCIPAL -->
      <div class="nav-section">
        <div v-show="!isCollapsed" class="nav-section-title">PRINCIPAL</div>
        <NavItem
          :href="route('dashboard')"
          :active="route().current('dashboard')"
          icon="dashboard"
          :collapsed="isCollapsed"
        >
          Visão Geral
        </NavItem>
        <NavItem
          href="/rat"
          :active="false"
          icon="document"
          :collapsed="isCollapsed"
        >
          RAT
        </NavItem>
        <NavItem
          :href="route('pae.index')"
          :active="route().current('pae.*')"
          icon="document"
          :collapsed="isCollapsed"
        >
          PAE
        </NavItem>
      </div>

      <!-- MÓDULOS DE GESTÃO -->
      <div class="nav-section">
        <div v-show="!isCollapsed" class="nav-section-title">MÓDULOS DE GESTÃO</div>
        
        <!-- TDAP com submenu -->
        <div class="nav-group">
          <button
            @click="toggleSubMenu('tdap')"
            class="nav-group-toggle"
            :class="{ 'is-open': openSubMenus.tdap }"
            :title="isCollapsed ? 'TDAP' : ''"
          >
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <span v-show="!isCollapsed">TDAP</span>
            <svg
              v-show="!isCollapsed"
              class="nav-arrow"
              :class="{ 'rotate-90': openSubMenus.tdap }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-show="openSubMenus.tdap && !isCollapsed" class="nav-submenu">
            <NavItem
              :href="route('dashboard')"
              :active="route().current('dashboard')"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              PMDA
            </NavItem>
            <NavItem
              :href="route('dashboard')"
              :active="false"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Relatórios
            </NavItem>
            <NavItem
              :href="route('dashboard')"
              :active="false"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Configurações
            </NavItem>
          </div>
        </div>

        <NavItem
          :href="route('dashboard')"
          :active="false"
          icon="book"
          :collapsed="isCollapsed"
        >
          Vistoria
        </NavItem>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { ref, provide, inject } from 'vue';
import NavItem from './NavItem.vue';

// Tentar injetar o estado do layout, se não existir, criar localmente
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));
const isCollapsed = sidebarCollapsed;

const openSubMenus = ref({
  tdap: true,
});

function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value;
  // Fechar submenus quando colapsar
  if (isCollapsed.value) {
    openSubMenus.value.tdap = false;
  }
}

function toggleSubMenu(menu) {
  if (isCollapsed.value) return;
  openSubMenus.value[menu] = !openSubMenus.value[menu];
}

// Fornecer o estado para componentes filhos
provide('sidebarCollapsed', isCollapsed);
</script>

<style scoped>
.sidebar {
  width: 280px;
  min-height: 100vh;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 50;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  transition: width 0.3s ease;
}

.sidebar.is-collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  position: relative;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.logo-image {
  width: 40px;
  height: 40px;
  object-fit: contain;
  flex-shrink: 0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-title {
  color: white;
  font-weight: 700;
  font-size: 1.125rem;
  line-height: 1.2;
}

.logo-subtitle {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.75rem;
  line-height: 1.2;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.sidebar-toggle {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.sidebar-toggle:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-color: rgba(255, 255, 255, 0.3);
}

.toggle-icon {
  width: 18px;
  height: 18px;
  transition: transform 0.3s ease;
}

.toggle-icon.rotated {
  transform: rotate(180deg);
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 0;
}

.nav-section {
  margin-bottom: 1.5rem;
}

.nav-section-title {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0 1.25rem;
  margin-bottom: 0.5rem;
}

.nav-group {
  margin-bottom: 0.25rem;
}

.nav-group-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: rgba(255, 255, 255, 0.8);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9375rem;
}

.nav-group-toggle:hover {
  background: rgba(255, 255, 255, 0.05);
  color: white;
}

.nav-group-toggle.is-open {
  color: white;
}

.nav-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.nav-arrow {
  width: 16px;
  height: 16px;
  margin-left: auto;
  transition: transform 0.2s;
}

.nav-arrow.rotate-90 {
  transform: rotate(90deg);
}

.nav-submenu {
  padding-left: 1.25rem;
  margin-top: 0.25rem;
}

/* Scrollbar styling */
.sidebar-nav::-webkit-scrollbar {
  width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>

