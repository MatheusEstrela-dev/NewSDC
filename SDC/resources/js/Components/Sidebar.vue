<template>
  <aside
    class="sidebar"
    :class="{
      'is-collapsed': effectivelyCollapsed,
      'is-mobile-open': isSidebarOpen && (isMobile || isTablet)
    }"
  >
    <!-- Header -->
    <div class="sidebar-header">
      <div class="logo-container">
        <picture>
          <source srcset="/imgs/flag.webp" type="image/webp" />
          <img
            src="/imgs/flag.png"
            alt="SDC Logo"
            class="logo-image"
          />
        </picture>
        <div v-show="!effectivelyCollapsed || (isMobile || isTablet)" class="logo-text">
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
      <!-- Botao toggle desktop (somente em telas >= 1024px) -->
      <button
        v-if="isDesktop"
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
    <div class="sidebar-nav-wrapper">
      <!-- Gradientes de indicação de scroll -->
      <div
        class="scroll-gradient scroll-gradient-top"
        :class="{ 'is-visible': showTopGradient && isHovering }"
      ></div>

      <div class="sidebar-views" :class="{ 'show-submenu': activeSubmenu !== null }">
      <nav
        ref="sidebarNav"
        class="sidebar-nav sidebar-view sidebar-view-main"
        :inert="activeSubmenu !== null"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @mousemove="onMouseMove"
        @scroll="onScroll"
      >
        <!-- PRINCIPAL -->
      <div class="nav-section">
        <div v-show="!effectivelyCollapsed" class="nav-section-title">PRINCIPAL</div>
        <NavItem
          :href="route('dashboard')"
          :active="isRouteActive('dashboard')"
          icon="dashboard"
          :collapsed="effectivelyCollapsed"
        >
          Visão Geral
        </NavItem>
        <NavItem
          v-if="canSeeDemandas && _routes.hasDemandas"
          :href="route('demandas.index')"
          :active="isRouteActive('demandas.*')"
          icon="checkbadge"
          :collapsed="effectivelyCollapsed"
        >
          DEMANDAS
        </NavItem>
        <NavItem
          v-if="canSeeRat && _routes.hasRat"
          :href="ratHref"
          :active="isRouteActive('rat.*')"
          icon="rat-clipboard"
          :collapsed="effectivelyCollapsed"
        >
          RAT
        </NavItem>
        <NavItem
          v-if="canSeePae && _routes.hasPae"
          :href="paeHref"
          :active="isRouteActive('pae.*')"
          icon="pae-bolt"
          :collapsed="effectivelyCollapsed"
        >
          PAE
        </NavItem>
        <NavItem
          v-if="canSeePlantao && _routes.hasPlantao"
          :href="route('plantao.index')"
          :active="isRouteActive('plantao.*')"
          icon="clock"
          :collapsed="effectivelyCollapsed"
        >
          Plantão Diário
        </NavItem>
      </div>

      <!-- MÓDULOS DE GESTÃO -->
      <div class="nav-section">
        <div v-show="!effectivelyCollapsed" class="nav-section-title">MÓDULOS DE GESTÃO</div>

        <!-- DECRETACOES -->
        <NavItem
          v-if="canSeeDecretacoes && _routes.hasDecretacoes"
          :href="route('decretacoes.index')"
          :active="isRouteActive('decretacoes.*')"
          icon="scale"
          :collapsed="effectivelyCollapsed"
        >
          Decretacoes
        </NavItem>

        <!-- Ajuda Humanitaria -->
        <NavItem
          v-if="canSeeAjudaHumanitaria && _routes.hasHumanitaria"
          :href="route('ajuda-humanitaria.beneficiarios.index')"
          :active="isRouteActive('ajuda-humanitaria.*')"
          icon="heart"
          :collapsed="effectivelyCollapsed"
        >
          Ajuda Humanitaria
        </NavItem>

        <!-- COMPDEC / Orgaos -->
        <NavItem
          v-if="canSeeOrgaos && _routes.hasCompdec"
          :href="route('compdec.index')"
          :active="isRouteActive('compdec.*')"
          icon="building"
          :collapsed="effectivelyCollapsed"
        >
          Orgaos
        </NavItem>

        <!-- TDAP - drill-down (abre submenu como nova seccao) -->
        <button
          v-if="canSeeTdap"
          @click="openSubmenu('tdap')"
          class="nav-group-toggle nav-drilldown"
          :class="{ 'is-active-route': isRouteActive('tdap.*') }"
          :title="effectivelyCollapsed ? 'TDAP' : ''"
        >
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
          </svg>
          <span v-show="!effectivelyCollapsed">TDAP</span>
          <svg
            v-show="!effectivelyCollapsed"
            class="nav-arrow nav-arrow-drill"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- ESTOQUE - drill-down (abre submenu como nova seccao) -->
        <button
          v-if="canSeeEstoque"
          @click="openSubmenu('estoque')"
          class="nav-group-toggle nav-drilldown"
          :class="{ 'is-active-route': isRouteActive('estoque.*') }"
          :title="effectivelyCollapsed ? 'Estoque' : ''"
        >
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7.5l8-4.5 8 4.5-8 4.5-8-4.5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7.5v9l8 4.5 8-4.5v-9" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v9M8 9.75v4.5m8-4.5v4.5" />
          </svg>
          <span v-show="!effectivelyCollapsed">Estoque</span>
          <svg
            v-show="!effectivelyCollapsed"
            class="nav-arrow nav-arrow-drill"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <NavItem
          v-if="canSeeCisterna && _routes.hasCisterna"
          :href="route('cisternas.index')"
          :active="isRouteActive('cisternas.*')"
          icon="cisterna"
          :collapsed="effectivelyCollapsed"
        >
          Cisternas
        </NavItem>

        <!-- Treinamento -->
        <NavItem
          v-if="canSeeTreinamento && _routes.hasTreinamentos"
          :href="route('treinamentos.index')"
          :active="isRouteActive('treinamentos.*')"
          icon="academic"
          :collapsed="effectivelyCollapsed"
        >
          Treinamento
        </NavItem>

        <!-- PlanCon (Plano de Contingencia) -->
        <NavItem
          v-if="canSeePlanCon && _routes.hasPlancon"
          :href="route('plancon.index')"
          :active="isRouteActive('plancon.*')"
          icon="shield"
          :collapsed="effectivelyCollapsed"
        >
          Plano de Contingencia
        </NavItem>

        <!-- Meteorologia -->
        <NavItem
          v-if="canSeeMeteorologia && _routes.hasInmet"
          :href="route('inmet.index', undefined, false)"
          :active="isRouteActive('inmet.*')"
          icon="cloud"
          :collapsed="effectivelyCollapsed"
        >
          Meteorologia
        </NavItem>

        <!-- Vistoria -->
        <NavItem
          v-if="canSeeVistoria"
          :href="route('dashboard')"
          :active="false"
          icon="book"
          :collapsed="effectivelyCollapsed"
        >
          Vistoria
        </NavItem>
      </div>

      <!-- ADMINISTRACAO - Visivel apenas para usuarios com permissao -->
      <div v-if="canSeeAdminSection" class="nav-section">
        <div v-show="!effectivelyCollapsed" class="nav-section-title">ADMINISTRACAO</div>

        <!-- Permissionamento - Link direto sem submenu -->
        <NavItem
          v-if="canSeePermissionamento"
          :href="permissionamentoHref"
          :active="isRouteActive('admin.permissions.*')"
          icon="lock"
          :collapsed="effectivelyCollapsed"
        >
          Permissionamento
        </NavItem>

        <NavItem
          v-if="canSeeInventario && _routes.hasInventario"
          :href="route('inventario.index')"
          :active="isRouteActive('inventario.*')"
          icon="inventory"
          :collapsed="effectivelyCollapsed"
        >
          Inventario
        </NavItem>

        <NavItem
          v-if="canSeeLogs"
          :href="route('log-viewer.index')"
          :active="isRouteActive('log-viewer.*')"
          icon="logs-list"
          :collapsed="effectivelyCollapsed"
        >
          Logs
        </NavItem>
      </div>

      <!-- INTEGRACOES -->
      <div class="nav-section">
        <div v-show="!effectivelyCollapsed" class="nav-section-title">INTEGRACOES</div>
        <NavItem
          href="/api/documentation"
          :active="false"
          icon="code"
          :collapsed="effectivelyCollapsed"
          external
        >
          API Docs
        </NavItem>
      </div>


    </nav>

    <!-- SUBMENU VIEW: ESTOQUE -->
    <nav
      v-if="canSeeEstoque"
      class="sidebar-nav sidebar-view sidebar-view-submenu"
      :class="{ 'is-active': activeSubmenu === 'estoque' }"
      :inert="activeSubmenu !== 'estoque'"
    >
      <button
        type="button"
        class="submenu-back"
        @click="closeSubmenu"
        v-show="!effectivelyCollapsed"
      >
        <svg class="submenu-back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="submenu-back-title">{{ submenuTitle || 'Estoque' }}</span>
      </button>

      <div class="nav-section">
        <NavItem
          v-if="_routes.hasEstoque"
          :href="route('estoque.index')"
          :active="isRouteActive('estoque.index')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Dashboard
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueProdutos"
          :href="route('estoque.produtos.index')"
          :active="isRouteActive('estoque.produtos.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Produtos e Lotes
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueKits"
          :href="route('estoque.kits.index')"
          :active="isRouteActive('estoque.kits.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Kits
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueMovimentacoes"
          :href="route('estoque.movimentacoes.index')"
          :active="isRouteActive('estoque.movimentacoes.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Movimentacoes
        </NavItem>
      </div>
    </nav>
    <!-- SUBMENU VIEW: TDAP -->
    <nav
      v-if="canSeeTdap"
      class="sidebar-nav sidebar-view sidebar-view-submenu"
      :class="{ 'is-active': activeSubmenu === 'tdap' }"
      :inert="activeSubmenu !== 'tdap'"
    >
      <button
        type="button"
        class="submenu-back"
        @click="closeSubmenu"
        v-show="!effectivelyCollapsed"
      >
        <svg class="submenu-back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="submenu-back-title">{{ submenuTitle || 'TDAP' }}</span>
      </button>

      <div class="nav-section">
        <NavItem
          v-if="_routes.hasTdapDashboard"
          :href="route('tdap.dashboard')"
          :active="isRouteActive('tdap.dashboard')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Dashboard
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapPrestadores"
          :href="route('tdap.prestadores.index')"
          :active="isRouteActive('tdap.prestadores.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Prestadores
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapCaminhoes"
          :href="route('tdap.caminhoes.index')"
          :active="isRouteActive('tdap.caminhoes.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Caminhões
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapAtas"
          :href="route('tdap.atas.index')"
          :active="isRouteActive('tdap.atas.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Atas
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapLotes"
          :href="route('tdap.lotes.index')"
          :active="isRouteActive('tdap.lotes.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Lotes
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapCronogramas"
          :href="route('tdap.cronogramas.index')"
          :active="isRouteActive('tdap.cronogramas.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Cronogramas
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapViagensPendentes"
          :href="route('tdap.viagens.pendentes')"
          :active="isRouteActive('tdap.viagens.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Viagens pendentes
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapVistorias"
          :href="route('tdap.vistorias.index')"
          :active="isRouteActive('tdap.vistorias.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Vistorias
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapHistoricos"
          :href="route('tdap.historicos.index')"
          :active="isRouteActive('tdap.historicos.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Histórico
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapProcessos"
          :href="route('tdap.processos.swimlanes')"
          :active="isRouteActive('tdap.processos.*')"
          icon="dot"
          is-submenu
          :collapsed="effectivelyCollapsed"
        >
          Processos (Workflow)
        </NavItem>
      </div>
    </nav>

    </div><!-- /.sidebar-views -->

    <!-- Gradiente inferior -->
    <div
      class="scroll-gradient scroll-gradient-bottom"
      :class="{ 'is-visible': showBottomGradient && isHovering }"
    ></div>
  </div>
  </aside>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, inject, onMounted, onUnmounted, provide, ref, shallowRef, watch } from 'vue';
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

// Em tablet a sidebar sempre se comporta como collapsed (rail 80px),
// exceto quando o drawer (is-mobile-open) esta ativo. Isso elimina o gap
// preto da Fase 2 (md:ml-20 no layout sem icones visiveis no aside).
// O toggle desktop (toggleSidebar) continua mutando isCollapsed diretamente
// porque so e visivel em isDesktop.
const effectivelyCollapsed = computed(() => {
  // Drawer aberto (tablet ou mobile) -> sidebar full
  if (isSidebarOpen.value && (isMobile.value || isTablet.value)) return false;
  // Tablet sem drawer -> rail collapsed
  if (isTablet.value) return true;
  // Desktop ou mobile -> respeita o toggle manual
  return isCollapsed.value;
});

const page = usePage();

// ============================================================================
// Verificação de rotas existentes — estáticas, calculadas 1x (rotas não mudam)
// ============================================================================
const _routes = {
  hasRat: route().has('rat.index') || route().has('rat.create'),
  hasDemandas: route().has('demandas.index'),
  hasPae: route().has('pae.protocolos.index') || route().has('pae.index'),
  hasPlantao: route().has('plantao.index'),
  hasDecretacoes: route().has('decretacoes.index'),
  hasHumanitaria: route().has('ajuda-humanitaria.beneficiarios.index'),
  hasCompdec: route().has('compdec.index'),
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
  hasCisterna: route().has('cisternas.index'),
  hasInventario: route().has('inventario.index'),
  hasEstoque: route().has('estoque.index'),
  hasEstoqueProdutos: route().has('estoque.produtos.index'),
  hasEstoqueKits: route().has('estoque.kits.index'),
  hasEstoqueMovimentacoes: route().has('estoque.movimentacoes.index'),
  hasTreinamentos: route().has('treinamentos.index'),
  hasPlancon: route().has('plancon.index'),
  hasInmet: route().has('inmet.index'),
};

// ============================================================================
// Rotas ativas — recalculadas 1x por navegação em um único computed
// (em vez de 15+ chamadas individuais route().current() no template)
// ============================================================================
const _activeRoutes = computed(() => {
  const _url = page.url; // dependência reativa única
  return {
    'dashboard': route().current('dashboard'),
    'rat.*': route().current('rat.*'),
    'demandas.*': route().current('demandas.*'),
    'pae.*': route().current('pae.*'),
    'plantao.*': route().current('plantao.*'),
    'decretacoes.*': route().current('decretacoes.*'),
    'ajuda-humanitaria.*': route().current('ajuda-humanitaria.*'),
    'compdec.*': route().current('compdec.*'),
    'tdap.*': route().current('tdap.*'),
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
    'cisternas.*': route().current('cisternas.*'),
    'inventario.*': route().current('inventario.*'),
    'estoque.*': route().current('estoque.*'),
    'estoque.index': route().current('estoque.index'),
    'estoque.produtos.*': route().current('estoque.produtos.*'),
    'estoque.kits.*': route().current('estoque.kits.*'),
    'estoque.movimentacoes.*': route().current('estoque.movimentacoes.*'),
    'treinamentos.*': route().current('treinamentos.*'),
    'plancon.*': route().current('plancon.*'),
    'inmet.*': route().current('inmet.*'),
    'admin.permissions.*': route().current('admin.permissions.*'),
    'log-viewer.*': route().current('log-viewer.*'),
  };
});

const isRouteActive = (pattern) => _activeRoutes.value[pattern] ?? false;

// ============================================================================
// Cache estável de permissões — não re-executa em cada navegação.
// Atualiza apenas quando o ID do usuário muda (login/logout).
// ============================================================================
const _permSet = shallowRef(new Set(page.props?.auth?.user?.permissions ?? []));
const _isSuper = shallowRef(page.props?.auth?.user?.is_super_admin ?? false);

watch(
  () => page.props?.auth?.user?.id,
  (newId, prevId) => {
    if (newId !== prevId) {
      const user = page.props?.auth?.user;
      _permSet.value = new Set(user?.permissions ?? []);
      _isSuper.value = user?.is_super_admin ?? false;
    }
  }
);

const hasPermission = (permissionList) => {
  if (_isSuper.value) return true;
  return permissionList.some(p => _permSet.value.has(p));
};

const hasRole = (roleList) => {
  if (_isSuper.value) return true;
  const roles = page.props?.auth?.user?.roles ?? [];
  return roles.some(role => roleList.includes(role.slug || role.name));
};

// ============================================================================
// CONTROLE DE VISIBILIDADE POR MODULO
// Verifica permissao .view de cada modulo conforme config/permissions.php
// Segue padrao: MODULO.GRUPO.view
// ============================================================================

// PRINCIPAL
const canSeeRat = computed(() => {
  // RAT é visível para todos os usuários autenticados (módulo crítico)
  // TODO: Configurar permissão rat.protocolos.view quando sistema de permissões estiver completo
  return true;
});

const canSeeDemandas = computed(() => {
  return hasPermission(['demandas.chamados.view']);
});

const canSeePae = computed(() => {
  return hasPermission(['pae.protocolos.view', 'pae.empreendimentos.view']);
});

// MODULOS DE GESTAO
const canSeeDecretacoes = computed(() => {
  return hasPermission(['decretacoes.processos.view']);
});

const canSeeAjudaHumanitaria = computed(() => {
  return hasPermission(['humanitaria.beneficiarios.view']);
});

const canSeeOrgaos = computed(() => {
  // TODO: Adicionar permissao compdec.orgaos.view no config
  return hasPermission(['users.view']); // Temporario - usar permissao de admin
});

const canSeeTdap = computed(() => {
  return hasPermission([
    'tdap.dashboard.view',
    'tdap.prestadores.view',
    'tdap.caminhoes.view',
    'tdap.atas.view',
    'tdap.lotes.view',
    'tdap.cronogramas.view',
    'tdap.viagens.view',
    'tdap.vistorias.view',
    'tdap.historico.view',
    'tdap.processos.view',
  ]);
});

const canSeeCisterna = computed(() => {
  return hasPermission(['cisternas.view']);
});

const canSeeInventario = computed(() => {
  return hasPermission(['inventario.equipamentos.view', 'inventario.emprestimos.view']);
});

const canSeeEstoque = computed(() => {
  return hasPermission([
    'estoque.produtos.view',
    'estoque.lotes.view',
    'estoque.kits.view',
    'estoque.movimentacoes.view'
  ]);
});

const canSeeEstoqueExport = computed(() => {
  return hasPermission(['estoque.produtos.export', 'estoque.movimentacoes.export']);
});
const canSeeTreinamento = computed(() => {
  return hasPermission(['treinamento.cursos.view']);
});

const canSeePlanCon = computed(() => {
  // TODO: Adicionar permissao plancon.planos.view no config
  return true; // Liberado temporariamente - modulo novo
});

const canSeePlantao = computed(() => {
  return hasPermission(['plantao.turnos.view']);
});

const canSeeMeteorologia = computed(() => {
  // TODO: Adicionar permissao meteorologia.dados.view no config
  return true; // Liberado - modulo publico
});

const canSeeVistoria = computed(() => {
  // TODO: Adicionar permissao vistoria.registros.view no config
  return true; // Liberado - modulo em desenvolvimento
});

// ADMINISTRACAO
const canSeePermissionamento = computed(() => {
  return hasPermission(['users.view', 'roles.view', 'permissions.view']);
});

const canSeeAdminSection = computed(() => {
  return canSeePermissionamento.value || canSeeInventario.value || canSeeLogs.value;
});

const canSeeLogs = computed(() => {
  return hasPermission(['system.logs.view']);
});

// DEBUG - apenas em dev ou super admin
const canSeeDebug = computed(() => {
  const isDev = import.meta.env.DEV || window.location.hostname === 'localhost';
  return isDev || _isSuper.value;
});

const activeSubmenu = ref(null);

const submenuTitles = {
  tdap: 'TDAP',
  estoque: 'Estoque',
};

const submenuTitle = computed(() => submenuTitles[activeSubmenu.value] ?? '');

// Links resilientes (evita tela branca quando uma rota nao existir no Ziggy)
// URLs estáticas — rotas não mudam em runtime, sem necessidade de computed reativo
const ratHref = route().has('rat.index') ? route('rat.index') :
                route().has('rat.create') ? route('rat.create') :
                route('dashboard');

const paeHref = route().has('pae.protocolos.index') ? route('pae.protocolos.index') :
                route().has('pae.index') ? route('pae.index') :
                route('dashboard');

const permissionamentoHref = route().has('admin.permissions.users.index') ? route('admin.permissions.users.index') :
                              route().has('admin.permissions.roles.index') ? route('admin.permissions.roles.index') :
                              route().has('admin.permissions.permissions.index') ? route('admin.permissions.permissions.index') :
                              route('dashboard');

function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value;
  if (isCollapsed.value) {
    activeSubmenu.value = null;
  }
}

function openSubmenu(name) {
  if (isCollapsed.value) return;
  activeSubmenu.value = name;
}

function closeSubmenu() {
  activeSubmenu.value = null;
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
let rafId = null;
const scrollSpeed = ref(0);
const isHovering = ref(false);
const showTopGradient = ref(false);
const showBottomGradient = ref(false);

// Configurações
const EDGE_THRESHOLD = 60; // Pixels da borda para ativar auto-scroll
const MAX_SCROLL_SPEED = 8; // Velocidade máxima de scroll (pixels por frame)

function onMouseEnter() {
  isHovering.value = true;
}

function onMouseLeave() {
  isHovering.value = false;
  stopAutoScroll();
}

let cachedRect = null;
let rectCacheFrame = -1;

function onMouseMove(event) {
  if (!sidebarNav.value || !isHovering.value) return;

  // Reaproveitar getBoundingClientRect por frame para evitar layout thrashing
  const currentFrame = performance.now();
  if (!cachedRect || currentFrame - rectCacheFrame > 100) {
    cachedRect = sidebarNav.value.getBoundingClientRect();
    rectCacheFrame = currentFrame;
  }

  const mouseY = event.clientY - cachedRect.top;
  const containerHeight = cachedRect.height;

  let targetSpeed = 0;

  if (mouseY < EDGE_THRESHOLD) {
    const intensity = 1 - (mouseY / EDGE_THRESHOLD);
    targetSpeed = -MAX_SCROLL_SPEED * intensity;
  } else if (mouseY > containerHeight - EDGE_THRESHOLD) {
    const distanceFromBottom = containerHeight - mouseY;
    const intensity = 1 - (distanceFromBottom / EDGE_THRESHOLD);
    targetSpeed = MAX_SCROLL_SPEED * intensity;
  }

  if (targetSpeed !== 0) {
    startAutoScroll(targetSpeed);
  } else {
    stopAutoScroll();
  }
}

function startAutoScroll(targetSpeed) {
  scrollSpeed.value = targetSpeed;

  if (!rafId) {
    const loop = () => {
      if (sidebarNav.value && scrollSpeed.value !== 0) {
        sidebarNav.value.scrollTop += scrollSpeed.value;
        rafId = requestAnimationFrame(loop);
      } else {
        rafId = null;
      }
    };
    rafId = requestAnimationFrame(loop);
  }
}

function stopAutoScroll() {
  if (rafId) {
    cancelAnimationFrame(rafId);
    rafId = null;
  }
  scrollSpeed.value = 0;
}

function onScroll() {
  updateGradients();
}

function updateGradients() {
  if (!sidebarNav.value) return;

  const { scrollTop, scrollHeight, clientHeight } = sidebarNav.value;

  showTopGradient.value = scrollTop > 10;
  showBottomGradient.value = scrollTop < scrollHeight - clientHeight - 10;
}

// Atualizar gradientes ao montar e ao redimensionar
onMounted(() => {
  updateGradients();
  window.addEventListener('resize', updateGradients);
});

// Limpar RAF ao desmontar componente
onUnmounted(() => {
  stopAutoScroll();
  window.removeEventListener('resize', updateGradients);
});

// Fornecer o estado para componentes filhos
provide('sidebarCollapsed', isCollapsed);
</script>

<!--
  Estilos extraidos para ./Sidebar.styles.css (Fase 5.0 da auditoria).
  Estrutura dos estilos externos (busque os marcadores [REGION:*] em Sidebar.styles.css):
    - base, header, nav, views, submenu, gradients, mobile-ui

  REGRA: para garantir que selectors recebam o atributo data-v-* (scoped),
  adicione todos os estilos em Sidebar.styles.css. NAO use @import dentro
  de Sidebar.styles.css - imports aninhados podem perder o scope.
-->
<style src="./Sidebar.styles.css" scoped></style>
