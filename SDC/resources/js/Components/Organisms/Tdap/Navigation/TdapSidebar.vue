<template>
  <aside
    class="sidebar"
    :class="{ 'is-mobile-open': isSidebarOpen && (isMobile || isTablet) }"
    aria-label="Navegação do módulo TDAP"
  >
    <div class="sidebar-header">
      <Link :href="route('dashboard')" class="logo-container" aria-label="Voltar ao painel principal">
        <picture>
          <source srcset="/imgs/flag.webp" type="image/webp" />
          <img src="/imgs/flag.png" alt="SDC Logo" class="logo-image" />
        </picture>
        <div class="logo-text">
          <div class="logo-title">SDC MG</div>
          <div class="logo-subtitle">SISTEMA INTEGRADO</div>
        </div>
      </Link>
      <button
        v-if="isMobile || isTablet"
        class="sidebar-close-mobile"
        title="Fechar menu"
        @click="closeSidebar"
      >
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="sidebar-nav-wrapper">
      <div class="sidebar-views show-submenu">
      <nav class="sidebar-nav sidebar-view sidebar-view-submenu is-active">
        <Link
          :href="route('dashboard')"
          class="submenu-back"
          aria-label="Voltar ao menu principal"
        >
          <svg class="submenu-back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          <span class="submenu-back-title">TDAP</span>
        </Link>

        <div class="nav-section">
          <NavItem
            v-if="routes.hasTdapDashboard"
            :href="route('tdap.dashboard')"
            :active="isActive('tdap.dashboard')"
            icon="dot"
            is-submenu
          >
            Dashboard
          </NavItem>
          <NavItem
            v-if="routes.hasTdapPrestadores"
            :href="route('tdap.prestadores.index')"
            :active="isActive('tdap.prestadores.*')"
            icon="dot"
            is-submenu
          >
            Prestadores
          </NavItem>
          <NavItem
            v-if="routes.hasTdapCaminhoes"
            :href="route('tdap.caminhoes.index')"
            :active="isActive('tdap.caminhoes.*')"
            icon="dot"
            is-submenu
          >
            Caminhões
          </NavItem>
          <NavItem
            v-if="routes.hasTdapAtas"
            :href="route('tdap.atas.index')"
            :active="isActive('tdap.atas.*')"
            icon="dot"
            is-submenu
          >
            Atas
          </NavItem>
          <NavItem
            v-if="routes.hasTdapLotes"
            :href="route('tdap.lotes.index')"
            :active="isActive('tdap.lotes.*')"
            icon="dot"
            is-submenu
          >
            Lotes
          </NavItem>
          <NavItem
            v-if="routes.hasTdapCronogramas"
            :href="route('tdap.cronogramas.index')"
            :active="isActive('tdap.cronogramas.*')"
            icon="dot"
            is-submenu
          >
            Cronogramas
          </NavItem>
          <NavItem
            v-if="routes.hasTdapViagensPendentes"
            :href="route('tdap.viagens.pendentes')"
            :active="isActive('tdap.viagens.*')"
            icon="dot"
            is-submenu
          >
            Viagens pendentes
          </NavItem>
          <NavItem
            v-if="routes.hasTdapVistorias"
            :href="route('tdap.vistorias.index')"
            :active="isActive('tdap.vistorias.*')"
            icon="dot"
            is-submenu
          >
            Vistorias
          </NavItem>
          <NavItem
            v-if="routes.hasTdapHistoricos"
            :href="route('tdap.historicos.index')"
            :active="isActive('tdap.historicos.*')"
            icon="dot"
            is-submenu
          >
            Histórico
          </NavItem>
          <NavItem
            v-if="routes.hasTdapProcessos"
            :href="route('tdap.processos.swimlanes')"
            :active="isActive('tdap.processos.*')"
            icon="dot"
            is-submenu
          >
            Processos (Workflow)
          </NavItem>
        </div>
      </nav>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import { route } from 'ziggy-js';
import NavItem from '@/Components/NavItem.vue';

const isMobile = inject('isMobile', ref(false));
const isTablet = inject('isTablet', ref(false));
const isSidebarOpen = inject('isSidebarOpen', ref(false));
const closeSidebar = inject('closeSidebar', () => {});

const page = usePage();

const routes = {
  hasTdapDashboard: route().has('tdap.dashboard'),
  hasTdapPrestadores: route().has('tdap.prestadores.index'),
  hasTdapCaminhoes: route().has('tdap.caminhoes.index'),
  hasTdapAtas: route().has('tdap.atas.index'),
  hasTdapLotes: route().has('tdap.lotes.index'),
  hasTdapCronogramas: route().has('tdap.cronogramas.index'),
  hasTdapViagensPendentes: route().has('tdap.viagens.pendentes'),
  hasTdapVistorias: route().has('tdap.vistorias.index'),
  hasTdapHistoricos: route().has('tdap.historicos.index'),
  hasTdapProcessos: route().has('tdap.processos.swimlanes'),
};

const activeRoutes = computed(() => {
  // eslint-disable-next-line no-unused-vars
  const _url = page.url;
  return {
    'tdap.dashboard': route().current('tdap.dashboard'),
    'tdap.prestadores.*': route().current('tdap.prestadores.*'),
    'tdap.caminhoes.*': route().current('tdap.caminhoes.*'),
    'tdap.atas.*': route().current('tdap.atas.*'),
    'tdap.lotes.*': route().current('tdap.lotes.*'),
    'tdap.cronogramas.*': route().current('tdap.cronogramas.*'),
    'tdap.viagens.*': route().current('tdap.viagens.*'),
    'tdap.vistorias.*': route().current('tdap.vistorias.*'),
    'tdap.historicos.*': route().current('tdap.historicos.*'),
    'tdap.processos.*': route().current('tdap.processos.*'),
  };
});

const isActive = (pattern) => activeRoutes.value[pattern] ?? false;
</script>

<style src="@/Components/Sidebar.styles.css" scoped></style>

<style scoped>
.logo-container {
  text-decoration: none;
}
</style>
