<template>
  <aside
    class="sidebar"
    :class="{
      'is-collapsed': isCollapsed,
      /* Sem gate JS de breakpoint aqui: o proprio CSS ja restringe o efeito
         de .is-mobile-open as media queries < 1024px. Condicionar por
         isMobile/isTablet reintroduz o risco de divergencia JS x CSS na
         fronteira do breakpoint (scrollbar classica, zoom). */
      'is-mobile-open': isSidebarOpen
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
      <!--
        Cidadao (guard "cidadao", Portal de Treinamentos): usa o mesmo shell do
        SDC (Sidebar/TopBar), mas so pode ver o que e dele - nao tem
        roles/permissions do Spatie (auth.user e null pra ela), entao os
        canSeeX abaixo (todos baseados em hasPermission) nao se aplicam. Ramo
        totalmente separado do menu interno, pra nao arriscar side effect nos
        canSeeX de todo mundo tentando "adaptar" o menu de staff pra ela.
      -->
      <nav v-if="isCidadao" class="sidebar-nav sidebar-view sidebar-view-main">
        <div class="nav-section" data-tour="sidebar-principal">
          <div v-show="!isCollapsed" class="nav-section-title">TREINAMENTOS</div>
          <NavItem
            :href="route('portal.treinamento.catalogo')"
            :active="isRouteActive('portal.treinamento.catalogo') || isRouteActive('portal.treinamento.eventos.*')"
            icon="academic"
            :collapsed="isCollapsed"
          >
            Catálogo
          </NavItem>
          <NavItem
            :href="route('portal.treinamento.inscricoes.index')"
            :active="isRouteActive('portal.treinamento.inscricoes.*')"
            icon="checkbadge"
            :collapsed="isCollapsed"
          >
            Minhas Inscrições
          </NavItem>
          <NavItem
            :href="route('portal.treinamento.certificados.index')"
            :active="isRouteActive('portal.treinamento.certificados.*')"
            icon="document-check"
            :collapsed="isCollapsed"
          >
            Certificados
          </NavItem>
        </div>
      </nav>

      <nav
        v-else
        ref="sidebarNav"
        class="sidebar-nav sidebar-view sidebar-view-main"
        :inert="activeSubmenu !== null"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @mousemove="onMouseMove"
        @scroll="onScroll"
      >
        <!-- PRINCIPAL -->
      <div class="nav-section" data-tour="sidebar-principal">
        <div v-show="!isCollapsed" class="nav-section-title">PRINCIPAL</div>
        <NavItem
          :href="route('dashboard')"
          :active="isRouteActive('dashboard')"
          icon="dashboard"
          :collapsed="isCollapsed"
        >
          Visão Geral
        </NavItem>
        <NavItem
          v-if="canSeeDemandas && _routes.hasDemandas"
          :href="route('demandas.index')"
          :active="isRouteActive('demandas.*')"
          icon="checkbadge"
          :collapsed="isCollapsed"
        >
          DEMANDAS
        </NavItem>
        <NavItem
          v-if="canSeeRat && _routes.hasRat"
          :href="ratHref"
          :active="isRouteActive('rat.*')"
          icon="rat-clipboard"
          :collapsed="isCollapsed"
        >
          RAT
        </NavItem>
        <NavItem
          v-if="canSeePae && _routes.hasPae"
          :href="paeHref"
          :active="isRouteActive('pae.*')"
          icon="pae-bolt"
          :collapsed="isCollapsed"
        >
          PAE
        </NavItem>
        <NavItem
          v-if="canSeePlantao && _routes.hasPlantao"
          :href="route('plantao.index')"
          :active="isRouteActive('plantao.*')"
          icon="clock"
          :collapsed="isCollapsed"
        >
          Plantão Diário
        </NavItem>
      </div>

      <!-- MÓDULOS DE GESTÃO -->
      <div class="nav-section" data-tour="sidebar-modulos">
        <div v-show="!isCollapsed" class="nav-section-title">MÓDULOS DE GESTÃO</div>

        <!-- DECRETACOES -->
        <NavItem
          v-if="canSeeDecretacoes && _routes.hasDecretacoes"
          :href="route('decretacoes.index')"
          :active="isRouteActive('decretacoes.*')"
          icon="scale"
          :collapsed="isCollapsed"
        >
          Decretacoes
        </NavItem>

        <!-- Ajuda Humanitaria - drill-down (abre submenu como nova seccao) -->
        <button
          v-if="canSeeAjudaHumanitaria && _routes.hasHumanitaria"
          @click="openSubmenu('ajuda-humanitaria')"
          class="nav-group-toggle nav-drilldown"
          :class="{ 'is-active-route': isRouteActive('ajuda-humanitaria.*') }"
          :title="isCollapsed ? 'Ajuda Humanitaria' : ''"
        >
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <span v-show="!isCollapsed">Ajuda Humanitaria</span>
          <svg
            v-show="!isCollapsed"
            class="nav-arrow nav-arrow-drill"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- COMPDEC / Orgaos -->
        <NavItem
          v-if="canSeeOrgaos && _routes.hasCompdec"
          :href="route('compdec.index')"
          :active="isRouteActive('compdec.*')"
          icon="building"
          :collapsed="isCollapsed"
        >
          Orgaos
        </NavItem>

        <!-- CEDEC / Prefeituras -->
        <NavItem
          v-if="canSeeCedecPrefeituras && _routes.hasCedec"
          :href="route('cedec.prefeituras.index')"
          :active="isRouteActive('cedec.*')"
          icon="building"
          :collapsed="isCollapsed"
        >
          Prefeituras
        </NavItem>

        <!-- TDAP - drill-down (abre submenu como nova seccao) -->
        <button
          v-if="canSeeTdap"
          @click="openSubmenu('tdap')"
          class="nav-group-toggle nav-drilldown"
          :class="{ 'is-active-route': isRouteActive('tdap.*') }"
          :title="isCollapsed ? 'TDAP' : ''"
        >
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
          </svg>
          <span v-show="!isCollapsed">TDAP</span>
          <svg
            v-show="!isCollapsed"
            class="nav-arrow nav-arrow-drill"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <NavItem
          v-if="canSeePmda && _routes.hasPmda"
          :href="pmdaHref"
          :active="isRouteActive('pmda.*')"
          icon="pmda-drop"
          :collapsed="isCollapsed"
        >
          PMDA
        </NavItem>

        <!-- ESTOQUE - drill-down (abre submenu como nova seccao) -->
        <button
          v-if="canSeeEstoque"
          @click="openSubmenu('estoque')"
          class="nav-group-toggle nav-drilldown"
          :class="{ 'is-active-route': isRouteActive('estoque.*') }"
          :title="isCollapsed ? 'Estoque' : ''"
        >
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7.5l8-4.5 8 4.5-8 4.5-8-4.5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7.5v9l8 4.5 8-4.5v-9" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v9M8 9.75v4.5m8-4.5v4.5" />
          </svg>
          <span v-show="!isCollapsed">Estoque</span>
          <svg
            v-show="!isCollapsed"
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
          :href="route('cisternas.beneficiarios.index')"
          :active="isRouteActive('cisternas.*')"
          icon="cisterna"
          :collapsed="isCollapsed"
        >
          Cisternas
        </NavItem>

        <!-- Treinamento -->
        <NavItem
          v-if="canSeeTreinamento && _routes.hasTreinamentos"
          :href="route('treinamentos.index')"
          :active="isRouteActive('treinamentos.*')"
          icon="academic"
          :collapsed="isCollapsed"
        >
          Treinamento
        </NavItem>

        <NavItem
          v-if="page.props.rankingDisponivel && _routes.hasRanking"
          :href="route('ranking.index')"
          :active="isRouteActive('ranking.*')"
          icon="trophy"
          :collapsed="isCollapsed"
        >
          Ranking
        </NavItem>

        <!-- PlanCon (Plano de Contingencia) -->
        <NavItem
          v-if="canSeePlanCon && _routes.hasPlancon"
          :href="route('plancon.index')"
          :active="isRouteActive('plancon.*')"
          icon="shield"
          :collapsed="isCollapsed"
        >
          Plano de Contingencia
        </NavItem>

        <!-- Meteorologia -->
        <NavItem
          v-if="canSeeMeteorologia && _routes.hasInmet"
          :href="route('inmet.index', undefined, false)"
          :active="isRouteActive('inmet.*')"
          icon="cloud"
          :collapsed="isCollapsed"
        >
          Meteorologia
        </NavItem>

        <!-- Camadas geoespaciais -->
        <NavItem
          v-if="canSeeMeteorologia && _routes.hasGeoespacial"
          :href="route('geoespacial.index', undefined, false)"
          :active="isRouteActive('geoespacial.index')"
          icon="map"
          :collapsed="isCollapsed"
        >
          Camadas de Risco
        </NavItem>

        <!--
          Envio. Item proprio porque a COMPDEC entra no sistema para ENVIAR, e
          nao para consultar o mapa do estado: obrigar a passar pela tela de
          consulta para achar um formulario e desenho ruim para quem so quer
          mandar o mapeamento do proprio municipio.
        -->
        <NavItem
          v-if="canEnviarCamadas && _routes.hasGeoespacialEnviar"
          :href="route('geoespacial.enviar', undefined, false)"
          :active="isRouteActive('geoespacial.enviar')"
          icon="cloud"
          :collapsed="isCollapsed"
        >
          Enviar Camada
        </NavItem>

        <!--
          Fila de revisao. Item separado e nao aba dentro da tela porque quem
          revisa e a CEDEC e quem envia e o municipio: sao pessoas diferentes,
          e o item so aparece para quem tem a permissao.
        -->
        <NavItem
          v-if="canRevisarCamadas && _routes.hasGeoespacialRevisao"
          :href="route('geoespacial.revisao', undefined, false)"
          :active="isRouteActive('geoespacial.revisao')"
          icon="checkbadge"
          :collapsed="isCollapsed"
        >
          Revisar Camadas
        </NavItem>

        <!-- Sismos -->
        <NavItem
          v-if="canSeeSismos && _routes.hasSismos"
          :href="route('sismos.index', undefined, false)"
          :active="isRouteActive('sismos.*')"
          icon="map"
          :collapsed="isCollapsed"
        >
          Sismos
        </NavItem>

        <!-- Vistoria -->
        <NavItem
          v-if="canSeeVistoria"
          :href="route('dashboard')"
          :active="false"
          icon="book"
          :collapsed="isCollapsed"
        >
          Vistoria
        </NavItem>
      </div>

      <!-- ADMINISTRACAO - Visivel apenas para usuarios com permissao -->
      <div v-if="canSeeAdminSection" class="nav-section" data-tour="sidebar-admin">
        <div v-show="!isCollapsed" class="nav-section-title">ADMINISTRACAO</div>

        <!-- Permissionamento - Link direto sem submenu -->
        <NavItem
          v-if="canSeePermissionamento"
          :href="permissionamentoHref"
          :active="isRouteActive('admin.permissions.*')"
          icon="lock"
          :collapsed="isCollapsed"
        >
          Permissionamento
        </NavItem>

        <NavItem
          v-if="canSeeInventario && _routes.hasInventario"
          :href="route('inventario.index')"
          :active="isRouteActive('inventario.*')"
          icon="inventory"
          :collapsed="isCollapsed"
        >
          Inventario
        </NavItem>
        <NavItem
          v-if="canSeeInventario"
          href="/inventario/estacoes"
          :active="isRouteActive('inventario.estacoes.*')"
          icon="inventory"
          :collapsed="isCollapsed"
        >
          Estações de trabalho
        </NavItem>
        <NavItem
          v-if="canSeeMovimentacoes"
          href="/inventario/movimentacoes"
          :active="isRouteActive('inventario.movimentacoes.*')"
          icon="inventory"
          :collapsed="isCollapsed"
        >
          Movimentações
        </NavItem>
        <NavItem
          v-if="canSeeAcessos"
          href="/acessos"
          :active="isRouteActive('acessos.*')"
          icon="lock"
          :collapsed="isCollapsed"
        >
          Acessos
        </NavItem>

        <NavItem
          v-if="canSeeLogs"
          :href="route('log-viewer.index')"
          :active="isRouteActive('log-viewer.*')"
          icon="logs-list"
          :collapsed="isCollapsed"
        >
          Logs
        </NavItem>
      </div>

      <!-- INTEGRACOES -->
      <div class="nav-section">
        <div v-show="!isCollapsed" class="nav-section-title">INTEGRACOES</div>
        <NavItem
          href="/api/documentation"
          :active="false"
          icon="code"
          :collapsed="isCollapsed"
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
        v-show="!isCollapsed"
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
          :collapsed="isCollapsed"
        >
          Dashboard
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueProdutos"
          :href="route('estoque.produtos.index')"
          :active="isRouteActive('estoque.produtos.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Produtos e Lotes
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueKits"
          :href="route('estoque.kits.index')"
          :active="isRouteActive('estoque.kits.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Kits
        </NavItem>
        <NavItem
          v-if="_routes.hasEstoqueMovimentacoes"
          :href="route('estoque.movimentacoes.index')"
          :active="isRouteActive('estoque.movimentacoes.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Movimentacoes
        </NavItem>
      </div>
    </nav>
    <!-- SUBMENU VIEW: Ajuda Humanitaria -->
    <nav
      v-if="canSeeAjudaHumanitaria && _routes.hasHumanitaria"
      class="sidebar-nav sidebar-view sidebar-view-submenu"
      :class="{ 'is-active': activeSubmenu === 'ajuda-humanitaria' }"
      :inert="activeSubmenu !== 'ajuda-humanitaria'"
    >
      <button
        type="button"
        class="submenu-back"
        @click="closeSubmenu"
        v-show="!isCollapsed"
      >
        <svg class="submenu-back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="submenu-back-title">{{ submenuTitle || 'Ajuda Humanitaria' }}</span>
      </button>

      <div class="nav-section">
        <NavItem
          v-if="_routes.hasHumanitariaDashboard"
          :href="route('ajuda-humanitaria.dashboard')"
          :active="isRouteActive('ajuda-humanitaria.dashboard')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Dashboard
        </NavItem>
        <NavItem
          :href="route('ajuda-humanitaria.pedidos.index')"
          :active="isRouteActive('ajuda-humanitaria.pedidos.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Pedidos
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaBeneficiarios"
          :href="route('ajuda-humanitaria.beneficiarios.index')"
          :active="isRouteActive('ajuda-humanitaria.beneficiarios.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Beneficiários
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaEstoque"
          :href="route('ajuda-humanitaria.estoque.index')"
          :active="isRouteActive('ajuda-humanitaria.estoque.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Estoque
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaParametros && canManageParametrosAh"
          :href="route('ajuda-humanitaria.parametros.index')"
          :active="isRouteActive('ajuda-humanitaria.parametros.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Parâmetros
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaMovimentos"
          :href="route('ajuda-humanitaria.movimentos.index')"
          :active="isRouteActive('ajuda-humanitaria.movimentos.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Movimentações
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaMateriais && canManageMateriaisAh"
          :href="route('ajuda-humanitaria.materiais.index')"
          :active="isRouteActive('ajuda-humanitaria.materiais.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Materiais
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaEntradas"
          :href="route('ajuda-humanitaria.entradas.index')"
          :active="isRouteActive('ajuda-humanitaria.entradas.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Entradas
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaLiberacoes"
          :href="route('ajuda-humanitaria.liberacoes.index')"
          :active="isRouteActive('ajuda-humanitaria.liberacoes.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Liberações
        </NavItem>
        <NavItem
          v-if="_routes.hasHumanitariaTransferencias"
          :href="route('ajuda-humanitaria.transferencias.index')"
          :active="isRouteActive('ajuda-humanitaria.transferencias.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Transferências
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
        v-show="!isCollapsed"
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
          :collapsed="isCollapsed"
        >
          Dashboard
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapPrestadores"
          :href="route('tdap.prestadores.index')"
          :active="isRouteActive('tdap.prestadores.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Prestadores
        </NavItem>
        <!-- Um item so. Caminhao e vistoria eram duas entradas, e a pergunta
             que o analista faz e uma: "este veiculo pode rodar?". Nenhuma das
             duas respondia sozinha -- a de caminhoes mostra `ativo`, que e flag
             de cadastro, e a de vistorias nao sabe quais veiculos ficaram de
             fora.

             O `:active` era a uniao de dois padroes porque as rotas viviam em
             prefixos separados; agora `tdap.frota.*` cobre as duas, que e o
             sentido da fusao. -->
        <NavItem
          v-if="_routes.hasTdapFrota"
          :href="route('tdap.frota.index')"
          :active="isRouteActive('tdap.frota.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Frota e Vistorias
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapAtas"
          :href="route('tdap.atas.index')"
          :active="isRouteActive('tdap.atas.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Atas
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapLotes"
          :href="route('tdap.lotes.index')"
          :active="isRouteActive('tdap.lotes.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Lotes
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapCronogramas"
          :href="route('tdap.cronogramas.index')"
          :active="isRouteActive('tdap.cronogramas.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Cronogramas
        </NavItem>
        <!-- Confirmacao do municipio: item proprio, nao aba da fila da CEDEC.
             Sao publicos e atos diferentes -- o COMPDEC atesta recebimento, a
             CEDEC libera pagamento -- e o gate de cada um ja separa quem ve o
             que. -->
        <NavItem
          v-if="_routes.hasTdapViagensConfirmacao && podeConfirmarViagens"
          :href="route('tdap.viagens.confirmacao')"
          :active="isRouteActive('tdap.viagens.confirmacao')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Confirmar recebimento
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapViagensPendentes"
          :href="route('tdap.viagens.pendentes')"
          :active="isRouteActive('tdap.viagens.pendentes')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Viagens pendentes
        </NavItem>
        <NavItem
          v-if="_routes.hasTdapHistoricos"
          :href="route('tdap.historicos.index')"
          :active="isRouteActive('tdap.historicos.*')"
          icon="dot"
          is-submenu
          :collapsed="isCollapsed"
        >
          Histórico
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
import { computed, inject, onMounted, onUnmounted, provide, ref } from 'vue';
import { route } from 'ziggy-js';
import NavItem from './NavItem.vue';


// Tentar injetar o estado do layout, se não existir, criar localmente
const sidebarCollapsed = inject('sidebarCollapsed', ref(false));

// Injetar estados mobile
const isMobile = inject('isMobile', ref(false));
const isTablet = inject('isTablet', ref(false));
const isDesktop = inject('isDesktop', ref(true));
const isSidebarOpen = inject('isSidebarOpen', ref(false));
const closeSidebar = inject('closeSidebar', () => {});

/**
 * Recolher a sidebar e preferencia de DESKTOP, onde ela ocupa espaco de fato e
 * trocar 280px por 80px devolve area util. Abaixo de lg ela e um drawer
 * off-canvas: nao ocupa nada quando fechado e, quando abre, abre inteiro.
 *
 * Antes `isCollapsed` era o `sidebarCollapsed` cru, sem guarda de largura. Quem
 * deixasse a sidebar recolhida no desktop e depois estreitasse a janela abria o
 * drawer com `is-collapsed` junto: os itens herdavam
 * `.nav-item.is-collapsed { justify-content: center; padding: 0.75rem }`, os
 * rotulos longos ("Ajuda Humanitaria", "Plano de Contingencia") quebravam em
 * duas linhas e sobrava uma faixa morta a esquerda -- a largura continuava 280px
 * por causa do `!important` do `.is-mobile-open`, mas o conteudo era desenhado
 * como se fosse o rail de 80px.
 *
 * O guard ja existia para o logo (`!isCollapsed || (isMobile || isTablet)`, mais
 * acima); faltava valer para o resto. Aqui ele passa a ser a fonte unica, e o
 * template todo herda o comportamento certo.
 */
const isCollapsed = computed(() => sidebarCollapsed.value && isDesktop.value);

const page = usePage();

// Cidadao do Portal de Treinamentos (guard "cidadao") logado no mesmo shell -
// ver ramo dedicado no template acima, antes do <!-- PRINCIPAL -->.
const isCidadao = computed(() => !!page.props?.auth?.cidadao);

// ============================================================================
// Verificação de rotas existentes — estáticas, calculadas 1x (rotas não mudam)
// ============================================================================
const _routes = {
  hasRat: route().has('rat.index') || route().has('rat.create'),
  hasDemandas: route().has('demandas.index'),
  hasPae: route().has('pae.protocolos.index') || route().has('pae.index'),
  hasPmda: route().has('pmda.planos.index'),
  hasPlantao: route().has('plantao.index'),
  hasDecretacoes: route().has('decretacoes.index'),
  hasHumanitaria: route().has('ajuda-humanitaria.pedidos.index'),
  hasHumanitariaDashboard: route().has('ajuda-humanitaria.dashboard'),
  hasHumanitariaBeneficiarios: route().has('ajuda-humanitaria.beneficiarios.index'),
  hasHumanitariaEstoque: route().has('ajuda-humanitaria.estoque.index'),
  hasHumanitariaParametros: route().has('ajuda-humanitaria.parametros.index'),
  hasHumanitariaMovimentos: route().has('ajuda-humanitaria.movimentos.index'),
  hasHumanitariaMateriais: route().has('ajuda-humanitaria.materiais.index'),
  hasHumanitariaEntradas: route().has('ajuda-humanitaria.entradas.index'),
  hasHumanitariaLiberacoes: route().has('ajuda-humanitaria.liberacoes.index'),
  hasHumanitariaTransferencias: route().has('ajuda-humanitaria.transferencias.index'),
  hasCompdec: route().has('compdec.index'),
  hasCedec: route().has('cedec.prefeituras.index'),
  hasTdapDashboard: route().has('tdap.dashboard'),
  hasTdapPrestadores: route().has('tdap.prestadores.index'),
  hasTdapFrota: route().has('tdap.frota.index'),
  hasTdapAtas: route().has('tdap.atas.index'),
  hasTdapLotes: route().has('tdap.lotes.index'),
  hasTdapCronogramas: route().has('tdap.cronogramas.index'),
  hasTdapViagensPendentes: route().has('tdap.viagens.pendentes'),
  hasTdapViagensConfirmacao: route().has('tdap.viagens.confirmacao'),
  hasTdapHistoricos: route().has('tdap.historicos.index'),
  hasCisterna: route().has('cisternas.beneficiarios.index'),
  hasInventario: route().has('inventario.index'),
  hasEstoque: route().has('estoque.index'),
  hasEstoqueProdutos: route().has('estoque.produtos.index'),
  hasEstoqueKits: route().has('estoque.kits.index'),
  hasEstoqueMovimentacoes: route().has('estoque.movimentacoes.index'),
  hasTreinamentos: route().has('treinamentos.index'),
  hasRanking: route().has('ranking.index'),
  hasPlancon: route().has('plancon.index'),
  hasInmet: route().has('inmet.index'),
  hasSismos: route().has('sismos.index'),
  hasGeoespacial: route().has('geoespacial.index'),
  hasGeoespacialRevisao: route().has('geoespacial.revisao'),
  hasGeoespacialEnviar: route().has('geoespacial.enviar'),
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
    // isRouteActive so acende o item quando o padrao e chave deste mapa. Sem as
    // linhas abaixo, nenhum item do submenu de Ajuda Humanitaria destacava a
    // pagina em que o usuario esta.
    'ajuda-humanitaria.dashboard': route().current('ajuda-humanitaria.dashboard'),
    'ajuda-humanitaria.pedidos.*': route().current('ajuda-humanitaria.pedidos.*'),
    'ajuda-humanitaria.beneficiarios.*': route().current('ajuda-humanitaria.beneficiarios.*'),
    'ajuda-humanitaria.estoque.*': route().current('ajuda-humanitaria.estoque.*'),
    'ajuda-humanitaria.movimentos.*': route().current('ajuda-humanitaria.movimentos.*'),
    'ajuda-humanitaria.parametros.*': route().current('ajuda-humanitaria.parametros.*'),
    'ajuda-humanitaria.materiais.*': route().current('ajuda-humanitaria.materiais.*'),
    'ajuda-humanitaria.entradas.*': route().current('ajuda-humanitaria.entradas.*'),
    'ajuda-humanitaria.liberacoes.*': route().current('ajuda-humanitaria.liberacoes.*'),
    'ajuda-humanitaria.transferencias.*': route().current('ajuda-humanitaria.transferencias.*'),
    'compdec.*': route().current('compdec.*'),
    'tdap.*': route().current('tdap.*'),
    'tdap.dashboard': route().current('tdap.dashboard'),
    'tdap.prestadores.*': route().current('tdap.prestadores.*'),
    // Frota = caminhao + vistoria; um padrao so cobre as duas desde a fusao.
    'tdap.frota.*': route().current('tdap.frota.*'),
    'tdap.atas.*': route().current('tdap.atas.*'),
    'tdap.lotes.*': route().current('tdap.lotes.*'),
    'tdap.cronogramas.*': route().current('tdap.cronogramas.*'),
    'tdap.viagens.*': route().current('tdap.viagens.*'),
    // Cada fila tem o seu destaque: sao duas telas irmas sob o mesmo prefixo,
    // e o padrao 'tdap.viagens.*' acenderia as duas ao mesmo tempo.
    // isRouteActive so consulta ESTE mapa -- chave ausente nunca acende.
    'tdap.viagens.pendentes': route().current('tdap.viagens.pendentes'),
    'tdap.viagens.confirmacao': route().current('tdap.viagens.confirmacao'),
    'tdap.historicos.*': route().current('tdap.historicos.*'),
    'cisternas.*': route().current('cisternas.*'),
    'pmda.*': route().current('pmda.*'),
    'inventario.*': route().current('inventario.*'),
    'estoque.*': route().current('estoque.*'),
    'estoque.index': route().current('estoque.index'),
    'estoque.produtos.*': route().current('estoque.produtos.*'),
    'estoque.kits.*': route().current('estoque.kits.*'),
    'estoque.movimentacoes.*': route().current('estoque.movimentacoes.*'),
    'treinamentos.*': route().current('treinamentos.*'),
    'plancon.*': route().current('plancon.*'),
    // isRouteActive so consulta ESTE mapa: sem a linha abaixo o item de
    // Ranking nasceria permanentemente apagado, mesmo dentro do modulo.
    'ranking.*': route().current('ranking.*'),
    'inmet.*': route().current('inmet.*'),
    'sismos.*': route().current('sismos.*'),
    // isRouteActive so acende o item quando o padrao e chave DESTE mapa: sem a
    // linha abaixo o item nasceria permanentemente apagado, mesmo na pagina.
    // 'geoespacial.*' casava tambem geoespacial.revisao, e os dois itens do
    // menu acendiam juntos na tela de revisao.
    'geoespacial.index': route().current('geoespacial.index'),
    'geoespacial.revisao': route().current('geoespacial.revisao'),
    'geoespacial.enviar': route().current('geoespacial.enviar'),
    'admin.permissions.*': route().current('admin.permissions.*'),
    'log-viewer.*': route().current('log-viewer.*'),
    'portal.treinamento.catalogo': route().current('portal.treinamento.catalogo'),
    'portal.treinamento.eventos.*': route().current('portal.treinamento.eventos.*'),
    'portal.treinamento.inscricoes.*': route().current('portal.treinamento.inscricoes.*'),
    'portal.treinamento.certificados.*': route().current('portal.treinamento.certificados.*'),
  };
});

const isRouteActive = (pattern) => _activeRoutes.value[pattern] ?? false;

// ============================================================================
// Conjunto de permissoes derivado das props da visita atual.
//
// Era um shallowRef fotografado na montagem, re-hidratado por um watch no
// `auth.user.id`. Como o id nao muda enquanto a pessoa segue logada, o Set
// ficava congelado pela sessao SPA inteira: o admin concedia a permissao, o
// servidor ja devolvia o slug novo no prop (o `inertia_user_data_{id}` e
// invalidado no update/syncPermissions do UserManagementController), e mesmo
// assim o modulo so aparecia na sidebar depois de um F5.
//
// O computed reconstroi o Set quando o objeto `auth.user` troca de identidade,
// o que na pratica e a cada visita Inertia. E o preco de estar sempre correto:
// montar um Set de ~230 strings custa microssegundos, contra um menu que mente
// sobre o acesso da pessoa ate ela recarregar a pagina.
// ============================================================================
const _permSet = computed(() => new Set(page.props?.auth?.user?.permissions ?? []));
const _isSuper = computed(() => page.props?.auth?.user?.is_super_admin ?? false);

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

const canSeePmda = computed(() => {
  return hasPermission(['pmda.planos.view']);
});

// MODULOS DE GESTAO
const canSeeDecretacoes = computed(() => {
  return hasPermission(['decretacoes.processos.view']);
});

const canSeeAjudaHumanitaria = computed(() => {
  return hasPermission(['humanitaria.beneficiarios.view']);
});

// O catalogo tem um slug so, de escrita: quem nao gerencia material nao tem o
// que fazer na tela, e sem esta checagem o item levaria a um 403.
const canManageMateriaisAh = computed(() => {
  return hasPermission(['humanitaria.materiais.manage']);
});

const canManageParametrosAh = computed(() => {
  return hasPermission(['humanitaria.parametros.manage']);
});

const canSeeOrgaos = computed(() => {
  // TODO: Adicionar permissao compdec.orgaos.view no config
  return hasPermission(['users.view']); // Temporario - usar permissao de admin
});

const canSeeCedecPrefeituras = computed(() => {
  return hasPermission(['cedec.prefeituras.view']);
});

// A confirmacao e do municipio: quem nao tem o slug nao ve o item, mesmo
// enxergando o resto do TDAP.
const podeConfirmarViagens = computed(() => hasPermission(['tdap.viagens.confirmar']));

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
  ]);
});

const canSeeCisterna = computed(() => {
  return hasPermission(['cisternas.beneficiarios.view']);
});

const canSeeInventario = computed(() => {
  return hasPermission(['inventario.equipamentos.view', 'inventario.emprestimos.view']);
});

const canSeeMovimentacoes = computed(() => hasPermission(['inventario.emprestimos.view']));
const canSeeAcessos = computed(() => hasPermission(['acessos.cadastros.view']));

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

const canEnviarCamadas = computed(() => {
  return hasPermission(['geoespacial.camadas.enviar']);
});

const canRevisarCamadas = computed(() => {
  // Diferente dos modulos de consulta liberados: revisar camada municipal
  // publica geometria no mapa estadual, entao exige permissao de verdade.
  return hasPermission(['geoespacial.camadas.revisar']);
});

const canSeeMeteorologia = computed(() => {
  // TODO: Adicionar permissao meteorologia.dados.view no config
  return true; // Liberado - modulo publico
});

const canSeeSismos = computed(() => {
  // Mesmo tratamento de Meteorologia: modulo de consulta, sem permissao propria
  // por enquanto. A rota ja exige autenticacao (grupo auth em routes/web.php).
  return true;
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
  return canSeePermissionamento.value || canSeeInventario.value || canSeeAcessos.value || canSeeLogs.value;
});

const canSeeLogs = computed(() => {
  return hasPermission(['system.logs.view']);
});

// DEBUG - apenas em dev ou super admin
const canSeeDebug = computed(() => {
  const isDev = import.meta.env.DEV || window.location.hostname === 'localhost';
  return isDev || _isSuper.value;
});

/**
 * Prefixo de rota que pertence a cada submenu.
 *
 * Serve para reabrir o submenu certo depois de navegar: a Sidebar remonta a
 * cada visita do Inertia, e sem isso o menu voltaria para a raiz assim que o
 * usuario clicasse em qualquer item de dentro da pasta.
 */
const submenuPorRota = {
  'tdap.': 'tdap',
  'estoque.': 'estoque',
  'ajuda-humanitaria.': 'ajuda-humanitaria',
};

function submenuDaRotaAtual() {
  const atual = route().current();

  if (!atual) {
    return null;
  }

  const par = Object.entries(submenuPorRota).find(([prefixo]) => atual.startsWith(prefixo));

  return par ? par[1] : null;
}

const activeSubmenu = ref(submenuDaRotaAtual());

const submenuTitles = {
  tdap: 'TDAP',
  estoque: 'Estoque',
  'ajuda-humanitaria': 'Ajuda Humanitaria',
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

const pmdaHref = route().has('pmda.planos.index') ? route('pmda.planos.index') :
                route('dashboard');

const permissionamentoHref = route().has('admin.permissions.users.index') ? route('admin.permissions.users.index') :
                              route().has('admin.permissions.roles.index') ? route('admin.permissions.roles.index') :
                              route().has('admin.permissions.permissions.index') ? route('admin.permissions.permissions.index') :
                              route('dashboard');

/**
 * Escreve na FONTE (`sidebarCollapsed`, o ref injetado do layout), nunca em
 * `isCollapsed`, que agora e um computed derivado e somente leitura -- atribuir
 * a ele falha em silencio e o botao de recolher para de funcionar.
 *
 * O layout tambem le `sidebarCollapsed` para o offset `lg:!ml-20` do conteudo,
 * entao a fonte precisa ser a mesma nos dois lados.
 */
function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;

  if (sidebarCollapsed.value) {
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
