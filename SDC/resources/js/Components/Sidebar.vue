<template>
  <aside
    class="sidebar"
    :class="{
      'is-collapsed': isCollapsed,
      'is-mobile-open': isSidebarOpen && (isMobile || isTablet)
    }"
  >
    <!-- Header -->
    <div class="sidebar-header">
      <div class="logo-container">
        <img
          src="/imgs/flag.png"
          alt="SDC Logo"
          class="logo-image"
        />
        <div v-show="!isCollapsed || (isMobile || isTablet)" class="logo-text">
          <div class="logo-title">SDC MG</div>
          <div class="logo-subtitle">SISTEMA INTEGRADO</div>
        </div>
      </div>
      <!-- Botao fechar mobile -->
      <button
        v-if="isMobile || isTablet"
        @click="closeSidebar"
        class="sidebar-close-mobile"
        title="Fechar menu"
      >
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <!-- Botao toggle desktop -->
      <button
        @click="toggleSidebar"
        class="sidebar-toggle hidden lg:flex"
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
    <div class="sidebar-nav-wrapper">
      <!-- Gradientes de indicação de scroll -->
      <div
        class="scroll-gradient scroll-gradient-top"
        :class="{ 'is-visible': showTopGradient && isHovering }"
      ></div>

      <nav
        ref="sidebarNav"
        class="sidebar-nav"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @mousemove="onMouseMove"
        @scroll="onScroll"
      >
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
          v-if="route().has('rat.index') || route().has('rat.create')"
          :href="ratHref"
          :active="route().current('rat.*')"
          icon="document"
          :collapsed="isCollapsed"
        >
          RAT
        </NavItem>
        <NavItem
          v-if="route().has('demandas.index')"
          :href="route('demandas.index')"
          :active="route().current('demandas.*')"
          icon="checkbadge"
          :collapsed="isCollapsed"
        >
          DEMANDAS
        </NavItem>
        <NavItem
          v-if="route().has('pae.protocolos.index') || route().has('pae.index')"
          :href="paeHref"
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

        <!-- DECRETAÇÕES -->
        <NavItem
          v-if="route().has('decretacoes.index')"
          :href="route('decretacoes.index')"
          :active="route().current('decretacoes.*')"
          icon="scale"
          :collapsed="isCollapsed"
        >
          Decretações
        </NavItem>

        <!-- Ajuda Humanitária -->
        <NavItem
          v-if="route().has('ajuda-humanitaria.beneficiarios.index')"
          :href="route('ajuda-humanitaria.beneficiarios.index')"
          :active="route().current('ajuda-humanitaria.*')"
          icon="heart"
          :collapsed="isCollapsed"
        >
          Ajuda Humanitária
        </NavItem>

        <!-- COMPDEC -->
        <NavItem
          v-if="route().has('compdec.index')"
          :href="route('compdec.index')"
          :active="route().current('compdec.*')"
          icon="building"
          :collapsed="isCollapsed"
        >
          Órgãos
        </NavItem>

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
              v-if="route().has('tdap.dashboard')"
              :href="route('tdap.dashboard')"
              :active="route().current('tdap.dashboard')"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Dashboard
            </NavItem>
            <NavItem
              v-if="route().has('tdap.products.index')"
              :href="route('tdap.products.index')"
              :active="route().current('tdap.products.*')"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Produtos
            </NavItem>
            <NavItem
              v-if="route().has('tdap.recebimentos.index')"
              :href="route('tdap.recebimentos.index')"
              :active="route().current('tdap.recebimentos.*')"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Recebimentos
            </NavItem>
            <NavItem
              v-if="route().has('tdap.movimentacoes.index')"
              :href="route('tdap.movimentacoes.index')"
              :active="route().current('tdap.movimentacoes.*')"
              icon="dot"
              is-submenu
              :collapsed="isCollapsed"
            >
              Movimentações
            </NavItem>
          </div>
        </div>

        <!-- Treinamento -->
        <NavItem
          v-if="route().has('treinamentos.index')"
          :href="route('treinamentos.index')"
          :active="route().current('treinamentos.*')"
          icon="academic"
          :collapsed="isCollapsed"
        >
          Treinamento
        </NavItem>

        <!-- Meteorologia -->
        <NavItem
          v-if="route().has('inmet.index')"
          :href="route('inmet.index', undefined, false)"
          :active="route().current('inmet.*')"
          icon="cloud"
          :collapsed="isCollapsed"
        >
          Meteorologia
        </NavItem>

        <NavItem
          :href="route('dashboard')"
          :active="false"
          icon="book"
          :collapsed="isCollapsed"
        >
          Vistoria
        </NavItem>
      </div>

      <!-- ADMINISTRACAO -->
      <div class="nav-section">
        <div v-show="!isCollapsed" class="nav-section-title">ADMINISTRACAO</div>

        <!-- Permissionamento - Link direto sem submenu -->
        <NavItem
          :href="permissionamentoHref"
          :active="route().current('admin.permissions.*')"
          icon="lock"
          :collapsed="isCollapsed"
        >
          Permissionamento
        </NavItem>
      </div>
    </nav>

    <!-- Gradiente inferior -->
    <div
      class="scroll-gradient scroll-gradient-bottom"
      :class="{ 'is-visible': showBottomGradient && isHovering }"
    ></div>
  </div>
  </aside>
</template>

<script setup>
import { ref, provide, inject, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import NavItem from './NavItem.vue';

// Tentar injetar o estado do layout, se não existir, criar localmente
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));
const isCollapsed = sidebarCollapsed;

// Injetar estados mobile
const isMobile = inject('isMobile', ref(false));
const isTablet = inject('isTablet', ref(false));
const isDesktop = inject('isDesktop', ref(true));
const isSidebarOpen = inject('isSidebarOpen', ref(false));
const closeSidebar = inject('closeSidebar', () => {});

const page = usePage();
// Mantemos a checagem para uso futuro (ex.: desabilitar links),
// mas o módulo deve aparecer no sidebar seguindo o padrão do projeto.
const canSeeAdmin = computed(() => !!page.props?.auth?.user);

const openSubMenus = ref({
  tdap: false,
  ajudaHumanitaria: false,
});

// Links resilientes (evita tela branca quando uma rota nao existir no Ziggy)
const ratHref = computed(() => {
  if (route().has('rat.index')) return route('rat.index');
  if (route().has('rat.create')) return route('rat.create');
  return route('dashboard');
});

const paeHref = computed(() => {
  if (route().has('pae.protocolos.index')) return route('pae.protocolos.index');
  if (route().has('pae.index')) return route('pae.index');
  return route('dashboard');
});

const permissionamentoHref = computed(() => {
  if (route().has('admin.permissions.users.index')) return route('admin.permissions.users.index');
  if (route().has('admin.permissions.roles.index')) return route('admin.permissions.roles.index');
  if (route().has('admin.permissions.permissions.index')) return route('admin.permissions.permissions.index');
  return route('dashboard');
});

function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value;
  // Fechar submenus quando colapsar
  if (isCollapsed.value) {
    openSubMenus.value.tdap = false;
    openSubMenus.value.ajudaHumanitaria = false;
  }
}

function toggleSubMenu(menu) {
  if (isCollapsed.value) return;
  openSubMenus.value[menu] = !openSubMenus.value[menu];
}

// Fechar sidebar mobile ao clicar em um link (será propagado aos NavItems)
provide('onNavItemClick', () => {
  if (isMobile.value || isTablet.value) {
    closeSidebar();
  }
});

// ============================================================================
// Smooth Scroll Feature - Auto-scroll quando mouse está próximo das bordas
// ============================================================================
const sidebarNav = ref(null);
let scrollInterval = null;
const scrollSpeed = ref(0);
const isHovering = ref(false);
const showTopGradient = ref(false);
const showBottomGradient = ref(false);

// Configurações
const EDGE_THRESHOLD = 60; // Pixels da borda para ativar auto-scroll
const MAX_SCROLL_SPEED = 8; // Velocidade máxima de scroll (pixels por frame)
const SCROLL_ACCELERATION = 0.5; // Aceleração do scroll

function onMouseEnter() {
  isHovering.value = true;
}

function onMouseLeave() {
  isHovering.value = false;
  stopAutoScroll();
}

function onMouseMove(event) {
  if (!sidebarNav.value || !isHovering.value) return;

  const rect = sidebarNav.value.getBoundingClientRect();
  const mouseY = event.clientY - rect.top;
  const containerHeight = rect.height;

  // Calcular a velocidade baseada na proximidade da borda
  let targetSpeed = 0;

  // Mouse próximo do topo
  if (mouseY < EDGE_THRESHOLD) {
    const intensity = 1 - (mouseY / EDGE_THRESHOLD);
    targetSpeed = -MAX_SCROLL_SPEED * intensity;
  }
  // Mouse próximo do fundo
  else if (mouseY > containerHeight - EDGE_THRESHOLD) {
    const distanceFromBottom = containerHeight - mouseY;
    const intensity = 1 - (distanceFromBottom / EDGE_THRESHOLD);
    targetSpeed = MAX_SCROLL_SPEED * intensity;
  }

  // Atualizar velocidade com aceleração suave
  if (targetSpeed !== 0) {
    startAutoScroll(targetSpeed);
  } else {
    stopAutoScroll();
  }
}

function startAutoScroll(targetSpeed) {
  scrollSpeed.value = targetSpeed;

  if (!scrollInterval) {
    scrollInterval = setInterval(() => {
      if (sidebarNav.value && scrollSpeed.value !== 0) {
        sidebarNav.value.scrollTop += scrollSpeed.value;
      }
    }, 16); // ~60fps
  }
}

function stopAutoScroll() {
  if (scrollInterval) {
    clearInterval(scrollInterval);
    scrollInterval = null;
  }
  scrollSpeed.value = 0;
}

function onScroll() {
  updateGradients();
}

function updateGradients() {
  if (!sidebarNav.value) return;

  const { scrollTop, scrollHeight, clientHeight } = sidebarNav.value;

  // Mostrar gradiente superior se não estiver no topo
  showTopGradient.value = scrollTop > 10;

  // Mostrar gradiente inferior se não estiver no fundo
  showBottomGradient.value = scrollTop < scrollHeight - clientHeight - 10;
}

// Atualizar gradientes ao montar e ao redimensionar
onMounted(() => {
  updateGradients();
  window.addEventListener('resize', updateGradients);
});

// Limpar interval ao desmontar componente
onUnmounted(() => {
  stopAutoScroll();
  window.removeEventListener('resize', updateGradients);
});

// Fornecer o estado para componentes filhos
provide('sidebarCollapsed', isCollapsed);
</script>

<style scoped>
.sidebar {
  width: 280px;
  height: 100vh;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 50;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  transition: width 0.3s ease, transform 0.3s ease;
}

.sidebar.is-collapsed {
  width: 80px;
}

/* Tablet (768px - 1023px): Sidebar sempre collapsed (apenas ícones) */
@media (min-width: 768px) and (max-width: 1023px) {
  .sidebar {
    width: 80px !important;
    transform: translateX(0);
  }

  /* Forçar sempre collapsed em tablet */
  .sidebar.is-collapsed,
  .sidebar:not(.is-collapsed) {
    width: 80px !important;
  }

  /* Esconder completamente todos os textos */
  .logo-text,
  .nav-section-title,
  .nav-arrow {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
  }

  /* Esconder submenu em tablet */
  .nav-submenu {
    display: none !important;
  }

  /* Ajustar header */
  .sidebar-header {
    padding: 1rem;
    justify-content: center;
  }

  .logo-container {
    justify-content: center;
  }

  /* Esconder botão de toggle em tablet */
  .sidebar-toggle {
    display: none !important;
  }

  /* Centralizar e ajustar nav-group-toggle */
  .nav-group-toggle {
    justify-content: center !important;
    padding: 0.75rem !important;
  }

  /* Garantir que spans dentro de buttons sejam escondidos */
  .nav-group-toggle span {
    display: none !important;
  }

  /* Ajustar ícones */
  .nav-icon {
    margin: 0 !important;
  }
}

/* Mobile (< 768px): Esconder sidebar por padrão e mostrar como drawer */
@media (max-width: 767px) {
  .sidebar {
    transform: translateX(-100%);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
    z-index: 50;
  }

  .sidebar.is-mobile-open {
    transform: translateX(0);
  }

  /* Desabilitar collapsed mode em mobile quando drawer aberto */
  .sidebar.is-collapsed {
    width: 280px;
  }
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
  overflow-x: hidden;
  padding: 1rem 0;
  scroll-behavior: smooth;
  overscroll-behavior: contain;
  min-height: 0; /* Critical: allows flexbox to shrink below content size */
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

.sidebar.is-collapsed .nav-group-toggle {
  padding: 0.75rem;
  justify-content: center;
  gap: 0;
}

.sidebar.is-collapsed .nav-group-toggle.is-open {
  background: rgba(59, 130, 246, 0.15);
  color: #3b82f6;
  box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.35);
  border-radius: 12px;
  margin: 0 0.5rem;
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

/* Wrapper para os gradientes */
.sidebar-nav-wrapper {
  position: relative;
  flex: 1 1 auto; /* Allow shrinking and growing */
  overflow: hidden;
  min-height: 0; /* Critical: Fix flexbox overflow issue */
  display: flex;
  flex-direction: column;
}

/* Gradientes de indicação de scroll */
.scroll-gradient {
  position: absolute;
  left: 0;
  right: 0;
  height: 60px;
  pointer-events: none;
  z-index: 10;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.scroll-gradient.is-visible {
  opacity: 1;
}

.scroll-gradient-top {
  top: 0;
  background: linear-gradient(
    to bottom,
    rgba(30, 41, 59, 0.95) 0%,
    rgba(30, 41, 59, 0.8) 40%,
    rgba(30, 41, 59, 0) 100%
  );
}

.scroll-gradient-bottom {
  bottom: 0;
  background: linear-gradient(
    to top,
    rgba(15, 23, 42, 0.95) 0%,
    rgba(15, 23, 42, 0.8) 40%,
    rgba(15, 23, 42, 0) 100%
  );
}

/* Indicador visual de zona de scroll ao hover */
.sidebar-nav-wrapper:hover .scroll-gradient.is-visible {
  opacity: 0.7;
}

/* Efeito de brilho nas bordas durante hover */
.sidebar-nav-wrapper:hover .scroll-gradient-top.is-visible::after,
.sidebar-nav-wrapper:hover .scroll-gradient-bottom.is-visible::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(59, 130, 246, 0.5) 50%,
    transparent 100%
  );
  animation: shimmer 2s ease-in-out infinite;
}

.sidebar-nav-wrapper:hover .scroll-gradient-top.is-visible::after {
  top: 0;
}

.sidebar-nav-wrapper:hover .scroll-gradient-bottom.is-visible::after {
  bottom: 0;
}

@keyframes shimmer {
  0%, 100% {
    opacity: 0.3;
  }
  50% {
    opacity: 0.8;
  }
}

/* Mobile close button */
.sidebar-close-mobile {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
}

.sidebar-close-mobile:hover,
.sidebar-close-mobile:active {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.sidebar-close-mobile:active {
  transform: scale(0.95);
}

.sidebar-close-mobile svg {
  width: 24px;
  height: 24px;
}
</style>
