import { onBeforeUnmount } from 'vue';
import axios from 'axios';
import Shepherd from 'shepherd.js';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTourConfetti } from './useTourConfetti';
import { useTheme } from '@/composables/ui';
import { svgIcon } from './tourIcons';

/**
 * Tour de boas-vindas SDC MG v3 - caloroso, painel-centrico e OBRIGATORIO.
 *
 * Regras (reforcadas pelo usuario):
 * - 9 passos: welcome | busca/IA | topbar | sidebar | filtros | kpis | graficos | acoes | aceite.
 * - SEM botao "Pular" em nenhum passo.
 * - Sem X de fechar, sem ESC, sem clicar fora.
 * - Force tema CLARO ao iniciar.
 * - Body scroll travado durante o tour.
 * - Welcome com logo circular oficial /imgs/logo_dc + msg calorosa.
 * - Dots de progresso em AZUL fixo (#2563eb).
 * - Aceite final centralizado (mesma estrutura cinematografica do welcome).
 * - Slogan laranja "DEFESA CIVIL SOMOS TODOS NOS" no passo final.
 *
 * Eventos custom no window (consumidos pelo AuthenticatedLayout):
 *   - sdc-tour:open-guia
 *   - sdc-tour:open-termos
 *   - sdc-tour:open-privacidade
 */

const STEPS_META = [
  { id: 'welcome',   accent: 'blue',    icon: 'check' },
  { id: 'topbar-ia', accent: 'blue',    icon: 'sparkles' },
  { id: 'topbar',    accent: 'cyan',    icon: 'bell' },
  { id: 'sidebar',   accent: 'blue',    icon: 'bars' },
  { id: 'filtros',   accent: 'sky',     icon: 'filter' },
  { id: 'kpis',      accent: 'blue',    icon: 'chartSquare' },
  { id: 'graficos',  accent: 'blue',    icon: 'chartPie' },
  { id: 'acoes',     accent: 'blue',    icon: 'cursor' },
  { id: 'aceite',    accent: 'blue',    icon: 'shield' },
];

const TOTAL_STEPS = STEPS_META.length;

function greetingByHour() {
  const h = new Date().getHours();
  if (h < 12) return 'Bom dia';
  if (h < 18) return 'Boa tarde';
  return 'Boa noite';
}

function dispatch(eventName) {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent(eventName));
  }
}

function progressDotsHtml(activeIdx) {
  const dots = STEPS_META.map((s, i) => {
    let cls = '';
    if (i < activeIdx) cls = 'is-done';
    if (i === activeIdx) cls = 'is-active';
    return `<button type="button" class="${cls}" data-tour-goto="${s.id}" aria-label="Passo ${i + 1}"></button>`;
  }).join('');
  return `<div class="shepherd-progress-dots">${dots}</div>`;
}

/**
 * Decide qual modulo destacar no passo 7 (Acoes).
 */
function detectPrimaryModule(perms = [], isSuper = false) {
  const has = (p) => isSuper || perms.includes(p);
  if (has('rat.protocolos.view') || true) {
    return {
      key: 'rat',
      label: 'RAT (Relatorio de Assistencia Tecnica)',
      contextHint: 'Em listas como RAT, Decretacoes ou PAE, cada linha tem a coluna ACOES no canto direito:',
    };
  }
  if (has('decretacoes.processos.view')) {
    return {
      key: 'decretacoes',
      label: 'Decretacoes',
      contextHint: 'Em Decretacoes, cada processo tem na coluna ACOES estes botoes:',
    };
  }
  if (has('pae.protocolos.view') || has('pae.empreendimentos.view')) {
    return {
      key: 'pae',
      label: 'PAE (Plano de Acao de Emergencia)',
      contextHint: 'Em PAE, cada protocolo tem na coluna ACOES estes botoes:',
    };
  }
  return {
    key: 'generico',
    label: 'modulos disponiveis',
    contextHint: 'Na maioria das listas voce vera uma coluna ACOES com os botoes:',
  };
}

export function useWelcomeTour({ userName = '', skipPersist = false } = {}) {
  let tour = null;
  const { fireConfetti } = useTourConfetti();
  const { isDarkMode } = useTheme();
  const page = usePage();
  let previousTheme = null;
  let escListener = null;
  let backdropEl = null;
  let backdropFrame = null;
  let backdropListenersAttached = false;
  let virtualTargetEl = null;

  function forceLightTheme() {
    previousTheme = isDarkMode.value;
    isDarkMode.value = false;
  }

  function restoreTheme() {
    if (previousTheme !== null) {
      isDarkMode.value = previousTheme;
      previousTheme = null;
    }
  }

  // Bloqueia scroll do USUARIO via event listeners. Antes usavamos
  // `body { overflow: hidden }`, que bloqueava tambem window.scrollTo
  // programatico (impedia o tour de rolar para o anchor correto).
  const SCROLL_KEYS = new Set([
    'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' ', 'Spacebar',
  ]);

  // Devolve true quando o evento pode passar — sempre que o alvo estiver
  // dentro do card do Shepherd OU de qualquer overlay com z-index > 10000
  // (Termos/Privacidade/Guia rodam em z-[10050], acima do tour).
  function isAllowedTarget(target) {
    if (!(target instanceof Node)) return false;
    if (target.closest?.('.shepherd-element')) return true;
    let cursor = target instanceof Element ? target : null;
    while (cursor && cursor !== document.body) {
      const z = parseInt(window.getComputedStyle(cursor).zIndex, 10);
      if (Number.isFinite(z) && z > 10000) return true;
      cursor = cursor.parentElement;
    }
    return false;
  }

  function preventUserScroll(event) {
    if (isAllowedTarget(event.target)) return;
    event.preventDefault();
  }

  function preventScrollKeys(event) {
    if (!SCROLL_KEYS.has(event.key)) return;
    if (isAllowedTarget(event.target)) return;
    event.preventDefault();
  }

  // Trava interacao com a pagina por baixo do tour. Capture phase + stopImmediate
  // garantem que o evento nao chegue nos delegated listeners do Inertia/Vue —
  // sem isso, clicks na sidebar/topbar navegam e quebram o passo atual.
  const INTERACTION_EVENTS = ['click', 'mousedown', 'mouseup', 'dblclick', 'pointerdown', 'auxclick'];

  function preventOutsideInteraction(event) {
    if (!(event.target instanceof Node)) return;
    if (event.target.closest?.('.shepherd-modal-overlay-container')) return;
    if (isAllowedTarget(event.target)) return;
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
  }

  function lockBodyScroll() {
    if (typeof document === 'undefined') return;
    document.body.classList.add('shepherd-tour-locked');
    window.addEventListener('wheel', preventUserScroll, { passive: false });
    window.addEventListener('touchmove', preventUserScroll, { passive: false });
    window.addEventListener('keydown', preventScrollKeys);
    INTERACTION_EVENTS.forEach((evt) => {
      document.addEventListener(evt, preventOutsideInteraction, true);
    });
  }

  function unlockBodyScroll() {
    if (typeof document === 'undefined') return;
    document.body.classList.remove('shepherd-tour-locked');
    window.removeEventListener('wheel', preventUserScroll);
    window.removeEventListener('touchmove', preventUserScroll);
    window.removeEventListener('keydown', preventScrollKeys);
    INTERACTION_EVENTS.forEach((evt) => {
      document.removeEventListener(evt, preventOutsideInteraction, true);
    });
  }

  function ensureBackdrop() {
    if (typeof document === 'undefined') return null;
    if (backdropEl) return backdropEl;

    backdropEl = document.createElement('div');
    backdropEl.className = 'sdc-tour-backdrop';
    backdropEl.setAttribute('aria-hidden', 'true');
    ['top', 'right', 'bottom', 'left'].forEach((pane) => {
      const paneEl = document.createElement('span');
      paneEl.className = `sdc-tour-backdrop__pane sdc-tour-backdrop__pane--${pane}`;
      paneEl.dataset.pane = pane;
      backdropEl.appendChild(paneEl);
    });
    document.body.appendChild(backdropEl);
    return backdropEl;
  }

  function ensureVirtualTarget() {
    if (typeof document === 'undefined') return null;
    if (virtualTargetEl) return virtualTargetEl;

    virtualTargetEl = document.createElement('div');
    virtualTargetEl.className = 'sdc-tour-virtual-target';
    virtualTargetEl.setAttribute('aria-hidden', 'true');
    document.body.appendChild(virtualTargetEl);
    return virtualTargetEl;
  }

  function scrollSelectorIntoView(selector, block = 'center') {
    if (typeof document === 'undefined' || typeof window === 'undefined') return;

    const rects = Array.from(document.querySelectorAll(selector))
      .filter((el) => el instanceof HTMLElement)
      .map((el) => el.getBoundingClientRect())
      .filter((rect) => rect.width > 0 && rect.height > 0);

    if (!rects.length) return;

    const top = Math.min(...rects.map((rect) => rect.top));
    const bottom = Math.max(...rects.map((rect) => rect.bottom));
    const height = bottom - top;
    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const topbarOffset = 112;

    const targetTop = block === 'start'
      ? scrollY + top - topbarOffset
      : scrollY + top - Math.max(80, (viewportHeight - height) / 2);

    const nextTop = Math.max(0, Math.round(targetTop));
    // O lock atual usa event listeners pra bloquear o usuario, mas deixa
    // window.scrollTo programatico funcionar — entao basta rolar direto.
    window.scrollTo({ top: nextTop, behavior: 'auto' });
  }

  function syncVirtualTarget(selector, options = {}) {
    const targetEl = ensureVirtualTarget();
    if (!targetEl || typeof document === 'undefined') return null;

    if (options.scroll) {
      scrollSelectorIntoView(selector, options.block);
    }

    const targets = Array.from(document.querySelectorAll(selector))
      .filter((el) => el instanceof HTMLElement)
      .map((el) => el.getBoundingClientRect())
      .filter((rect) => rect.width > 0 && rect.height > 0);

    if (!targets.length) return targetEl;

    const top = Math.min(...targets.map((rect) => rect.top));
    const right = Math.max(...targets.map((rect) => rect.right));
    const bottom = Math.max(...targets.map((rect) => rect.bottom));
    const left = Math.min(...targets.map((rect) => rect.left));
    const scrollX = window.scrollX || document.documentElement.scrollLeft || 0;
    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;

    targetEl.style.left = `${Math.round(left + scrollX)}px`;
    targetEl.style.top = `${Math.round(top + scrollY)}px`;
    targetEl.style.width = `${Math.round(right - left)}px`;
    targetEl.style.height = `${Math.round(bottom - top)}px`;

    return targetEl;
  }

  function focusVirtualTarget(selector, block = 'center') {
    scrollSelectorIntoView(selector, block);

    setTimeout(() => {
      syncVirtualTarget(selector);
      scheduleBackdropSync();
    }, 80);

    setTimeout(() => {
      syncVirtualTarget(selector);
      scheduleBackdropSync();
    }, 260);
  }

  function resolveCurrentTarget() {
    if (typeof document === 'undefined') return null;
    const currentStep = tour?.getCurrentStep?.();
    const target = currentStep?.options?.attachTo?.element;
    const element = typeof target === 'function'
      ? target.call(currentStep)
      : typeof target === 'string'
        ? document.querySelector(target)
        : target;

    if (!(element instanceof HTMLElement)) return null;

    const rect = element.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) return null;
    return { element, rect };
  }

  function setPaneRect(pane, top, right, bottom, left) {
    const width = Math.max(0, right - left);
    const height = Math.max(0, bottom - top);

    pane.style.transform = `translate3d(${Math.round(left)}px, ${Math.round(top)}px, 0)`;
    pane.style.width = `${Math.round(width)}px`;
    pane.style.height = `${Math.round(height)}px`;
    pane.style.opacity = width > 0 && height > 0 ? '1' : '0';
  }

  function syncBackdrop() {
    const backdrop = ensureBackdrop();
    if (!backdrop || typeof window === 'undefined') return;

    const panes = {
      top: backdrop.querySelector('[data-pane="top"]'),
      right: backdrop.querySelector('[data-pane="right"]'),
      bottom: backdrop.querySelector('[data-pane="bottom"]'),
      left: backdrop.querySelector('[data-pane="left"]'),
    };

    const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const target = resolveCurrentTarget();

    if (!target) {
      setPaneRect(panes.top, 0, viewportWidth, viewportHeight, 0);
      setPaneRect(panes.right, 0, 0, 0, 0);
      setPaneRect(panes.bottom, 0, 0, 0, 0);
      setPaneRect(panes.left, 0, 0, 0, 0);
      return;
    }

    const padding = 10;
    const top = Math.max(0, target.rect.top - padding);
    const right = Math.min(viewportWidth, target.rect.right + padding);
    const bottom = Math.min(viewportHeight, target.rect.bottom + padding);
    const left = Math.max(0, target.rect.left - padding);

    setPaneRect(panes.top, 0, viewportWidth, top, 0);
    setPaneRect(panes.bottom, bottom, viewportWidth, viewportHeight, 0);
    setPaneRect(panes.left, top, left, bottom, 0);
    setPaneRect(panes.right, top, viewportWidth, bottom, right);
  }

  function scheduleBackdropSync() {
    if (typeof window === 'undefined') return;
    if (backdropFrame) window.cancelAnimationFrame(backdropFrame);
    backdropFrame = window.requestAnimationFrame(() => {
      backdropFrame = null;
      syncBackdrop();
    });
  }

  function installBackdropSync() {
    ensureBackdrop();
    scheduleBackdropSync();

    if (backdropListenersAttached || typeof window === 'undefined') return;
    backdropListenersAttached = true;
    window.addEventListener('resize', scheduleBackdropSync);
    window.addEventListener('scroll', scheduleBackdropSync, true);
    window.visualViewport?.addEventListener('resize', scheduleBackdropSync);
    window.visualViewport?.addEventListener('scroll', scheduleBackdropSync);
  }

  function removeBackdropSync() {
    if (typeof window !== 'undefined') {
      if (backdropFrame) {
        window.cancelAnimationFrame(backdropFrame);
        backdropFrame = null;
      }
      window.removeEventListener('resize', scheduleBackdropSync);
      window.removeEventListener('scroll', scheduleBackdropSync, true);
      window.visualViewport?.removeEventListener('resize', scheduleBackdropSync);
      window.visualViewport?.removeEventListener('scroll', scheduleBackdropSync);
    }

    backdropListenersAttached = false;
    backdropEl?.remove();
    backdropEl = null;
    virtualTargetEl?.remove();
    virtualTargetEl = null;
  }

  function blockEscape(e) {
    if (e.key === 'Escape' && tour?.isActive?.()) {
      e.preventDefault();
      e.stopPropagation();
    }
  }

  function attachDotListeners() {
    setTimeout(() => {
      document.querySelectorAll('.shepherd-progress-dots button[data-tour-goto]').forEach((btn) => {
        btn.onclick = (ev) => {
          const id = ev.currentTarget.getAttribute('data-tour-goto');
          if (id && tour) tour.show(id);
        };
      });
    }, 30);
  }

  function attachAcceptScrollListener() {
    setTimeout(() => {
      const box = document.querySelector('.shepherd-accept-box');
      const acceptBtn = document.querySelector('.shepherd-button.shepherd-accept-btn');
      if (!box || !acceptBtn) return;

      const checkScroll = () => {
        const isEnd = box.scrollTop + box.clientHeight >= box.scrollHeight - 8;
        if (isEnd) {
          box.classList.add('is-scrolled-end');
          acceptBtn.disabled = false;
        }
      };

      box.addEventListener('scroll', checkScroll);
      checkScroll();

      document.querySelectorAll('[data-final-action]').forEach((el) => {
        el.onclick = (ev) => {
          ev.preventDefault();
          const a = ev.currentTarget.getAttribute('data-final-action');
          if (a === 'open-guia') dispatch('sdc-tour:open-guia');
          if (a === 'open-termos') dispatch('sdc-tour:open-termos');
          if (a === 'open-privacidade') dispatch('sdc-tour:open-privacidade');
        };
      });
    }, 30);
  }

  function onStepShown(...callbacks) {
    scheduleBackdropSync();
    attachDotListeners();
    callbacks.forEach((callback) => callback?.());
  }

  function header(stepIdx, title) {
    const meta = STEPS_META[stepIdx];
    return `${progressDotsHtml(stepIdx)}<div class="shepherd-header"><span class="shepherd-step-icon">${svgIcon(meta.icon, 26)}</span><h3 class="shepherd-title">${title}</h3></div>`;
  }

  function buildSteps() {
    const firstName = userName ? userName.split(' ')[0] : 'colega';
    const saudacao = greetingByHour();
    const perms = page.props?.auth?.user?.permissions ?? [];
    const isSuper = page.props?.auth?.user?.is_super_admin ?? false;
    const primaryModule = detectPrimaryModule(perms, isSuper);

    return [
      // ===== Passo 1: Welcome cinematografico com logo Defesa Civil =====
      {
        id: 'welcome',
        title: ' ',
        text: `
          <div class="shepherd-particles"><span></span><span></span><span></span><span></span></div>
          ${progressDotsHtml(0)}
          <div class="shepherd-hero">
            <div class="shepherd-hero-logo">
              <picture>
                <source srcset="/imgs/logo_dc.webp" type="image/webp" />
                <img src="/imgs/logo_dc.png" alt="Defesa Civil de Minas Gerais" />
              </picture>
            </div>
            <div class="shepherd-hero-badge"><span class="shepherd-hero-dot"></span> SISTEMA OFICIAL - DEFESA CIVIL MG</div>
            <h1 class="shepherd-hero-title">${saudacao}, <span class="shepherd-hero-name">${firstName}</span>!</h1>
            <p class="shepherd-hero-sub">Seja bem-vindo(a) a <strong>Equipe da Defesa Civil do Estado de Minas Gerais</strong>. Sua conta foi <em>ativada com sucesso</em> e a partir de agora voce faz parte de quem protege Minas. Em <strong>${TOTAL_STEPS} passos</strong> vamos explorar o sistema juntos.</p>
          </div>
        `,
        classes: 'shepherd-sdc shepherd-sdc--blue shepherd-sdc--cinematic shepherd-sdc--welcome',
        buttons: [
          { classes: 'shepherd-button-primary', text: 'Comecar a conhecer', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 2: Topbar - busca e IA integrada =====
      {
        id: 'topbar-ia',
        title: 'Topbar: busca e IA integrada',
        text: `
          ${header(1, 'Topbar: busca e IA integrada')}
          <div class="shepherd-text">
            <p>Esta area central do topo e o ponto mais rapido para encontrar informacoes e acionar apoio inteligente:</p>
            <ul class="shepherd-feature-list">
              <li>
                <span class="shepherd-feature-icon">${svgIcon('search', 18)}</span>
                <span><strong>Busca unificada</strong> - localize protocolos, municipios e demandas sem sair da pagina. Use <kbd>Ctrl</kbd> + <kbd>K</kbd> para abrir direto pelo teclado.</span>
              </li>
              <li>
                <span class="shepherd-feature-icon">${svgIcon('sparkles', 18)}</span>
                <span><strong>IA integrada</strong> - o icone da Defesa Civil ao lado da busca abre o assistente para apoiar consultas, resumos e orientacoes operacionais dentro do SDC.</span>
              </li>
            </ul>
          </div>
        `,
        attachTo: { element: '[data-tour="search"]', on: 'bottom' },
        classes: 'shepherd-sdc shepherd-sdc--blue',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 3: Topbar =====
      {
        id: 'topbar',
        title: 'Topo: alertas e sua conta',
        text: `
          ${header(2, 'Topo: alertas e sua conta')}
          <div class="shepherd-text">
            <p>No lado direito da topbar ficam os controles rapidos da sua sessao:</p>
            <ul class="shepherd-feature-list">
              <li>
                <span class="shepherd-feature-icon">${svgIcon('bell', 18)}</span>
                <span><strong>Notificacoes</strong> - mostra alertas, pendencias e avisos recentes.</span>
              </li>
              <li>
                <span class="shepherd-feature-icon">${svgIcon('moon', 18)}</span>
                <span><strong>Tema</strong> - alterna rapidamente entre modo claro e escuro.</span>
              </li>
              <li>
                <span class="shepherd-feature-icon">${svgIcon('cog', 18)}</span>
                <span><strong>Configuracoes</strong> - ajusta preferencias e opcoes da sua experiencia.</span>
              </li>
              <li>
                <span class="shepherd-feature-icon">${svgIcon('user', 18)}</span>
                <span><strong>Sua conta</strong> - abre perfil, assinatura digital e logout.</span>
              </li>
            </ul>
          </div>
        `,
        attachTo: { element: '[data-tour="topbar-actions"]', on: 'bottom' },
        classes: 'shepherd-sdc shepherd-sdc--cyan',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 4: Sidebar =====
      {
        id: 'sidebar',
        title: 'Barra lateral: seus modulos',
        text: `
          ${header(3, 'Barra lateral: seus modulos')}
          <div class="shepherd-text">
            <p>A barra a esquerda organiza tudo o que voce pode acessar no sistema, agrupado em tres secoes:</p>
            <ul>
              <li><strong>Principal</strong> - Visao Geral, Demandas, RAT, PAE e Plantao Diario.</li>
              <li><strong>Modulos de Gestao</strong> - Decretacoes, Ajuda Humanitaria, TDAP, Estoque, Cisternas, Treinamento, Plancon, Meteorologia e Vistoria.</li>
              <li><strong>Administracao</strong> - Permissionamento, Inventario e Logs (somente para perfis autorizados).</li>
            </ul>
          </div>
        `,
        attachTo: { element: 'aside.sidebar', on: 'right' },
        classes: 'shepherd-sdc shepherd-sdc--blue',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 5: Filtros de Pesquisa (centralizado, com mini-preview) =====
      {
        id: 'filtros',
        title: 'Filtros de Pesquisa',
        text: `
          ${header(4, 'Filtros de Pesquisa')}
          <div class="shepherd-text">
            <p>Nos modulos principais, o topo da lista tem um <strong>bloco de filtros</strong> para refinar a busca:</p>
            <div class="shepherd-filter-preview">
              <div class="shepherd-filter-row">
                <div class="shepherd-filter-field">
                  <span class="shepherd-filter-label">Busca</span>
                  <div class="shepherd-filter-input">analista, municipio, protocolo...</div>
                </div>
                <div class="shepherd-filter-field">
                  <span class="shepherd-filter-label">Data</span>
                  <div class="shepherd-filter-input">dd/mm/aaaa</div>
                </div>
              </div>
              <div class="shepherd-filter-row">
                <div class="shepherd-filter-field">
                  <span class="shepherd-filter-label">Tipo</span>
                  <div class="shepherd-filter-input">Todos os tipos</div>
                </div>
                <div class="shepherd-filter-field">
                  <span class="shepherd-filter-label">Status</span>
                  <div class="shepherd-filter-input">Selecione um status</div>
                </div>
              </div>
              <div class="shepherd-filter-advanced">
                <span>+ Filtros Avancados</span>
                <span>+ Filtro COBRADE</span>
              </div>
              <div class="shepherd-filter-actions">
                <span class="shepherd-filter-btn shepherd-filter-btn--apply">Aplicar</span>
                <span class="shepherd-filter-btn shepherd-filter-btn--clear">Limpar</span>
              </div>
            </div>
            <p class="shepherd-compact-hint">Os filtros sao <em>incrementais</em>: combine quantos precisar e aplique.</p>
          </div>
        `,
        classes: 'shepherd-sdc shepherd-sdc--sky shepherd-sdc--filters',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 6: KPIs do Painel =====
      {
        id: 'kpis',
        title: 'Indicadores do exercicio (KPIs)',
        text: `
          ${header(5, 'Indicadores do exercicio (KPIs)')}
          <div class="shepherd-text">
            <p>Os 4 cartoes do topo do painel mostram o <strong>resumo do exercicio vigente</strong>:</p>
            <ul>
              <li><strong>RAT Abertas</strong> - total de ocorrencias em andamento.</li>
              <li><strong>PAE em Analise</strong> - protocolos aguardando parecer.</li>
              <li><strong>Decretos Aprovados</strong> - reconhecimentos homologados.</li>
              <li><strong>Demandas Concluidas</strong> - chamados resolvidos e fechados.</li>
            </ul>
            <p>A seta colorida indica <em>tendencia mes a mes</em>. Os cartoes sao <strong>arrastaveis</strong> - reordene como preferir.</p>
          </div>
        `,
        // scrollSelectorIntoView poe os 4 KPI cards logo abaixo da topbar
        // (viewport y=112) ANTES do Shepherd montar o passo. Com isso Floating
        // UI ve ~800px de espaco livre abaixo e nao precisa flipar a modal
        // pra cima (era a causa do overlap).
        beforeShowPromise: () => new Promise((resolve) => {
          scrollSelectorIntoView('[data-tour="kpis-row"]', 'start');
          requestAnimationFrame(() => requestAnimationFrame(resolve));
        }),
        // Anchor estavel: Dashboard.vue isola os 4 KPI cards num draggable
        // proprio com data-tour="kpis-row" no root. Shepherd cobre os 4 cards
        // numa unica faixa, sem precisar de virtual target.
        attachTo: { element: '[data-tour="kpis-row"]', on: 'bottom' },
        // shepherd-sdc--kpis-gap aplica margin-top via CSS pra empurrar a modal
        // pra longe do spotlight (Shepherd 15 usa Floating UI, popperOptions nao
        // funciona). Ver shepherd-sdc.css.
        classes: 'shepherd-sdc shepherd-sdc--blue shepherd-sdc--kpis-gap',
        modalOverlayOpeningPadding: 12,
        modalOverlayOpeningRadius: 16,
        // scrollTo: false impede o Shepherd nativo de sobrescrever nossa
        // posicao com block:'center' (que joga os cards pro meio do viewport
        // e leva Floating UI a flipar a modal pra cima).
        scrollTo: false,
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 7: Graficos e listas =====
      {
        id: 'graficos',
        title: 'Graficos e movimentacoes',
        text: `
          ${header(6, 'Graficos e movimentacoes')}
          <div class="shepherd-text">
            <p>Abaixo dos KPIs voce tem a <strong>analise visual</strong> do exercicio:</p>
            <ul>
              <li><strong>Atendimentos por Mes</strong> - alterne entre 6M e 12M para acompanhar a evolucao.</li>
              <li><strong>Distribuicao por Modulo</strong> - donut com a proporcao de registros por area ativa.</li>
              <li><strong>PMDA em Analise</strong> - fila prioritaria de processos municipais.</li>
              <li><strong>Ultimas Movimentacoes</strong> - timeline em tempo real do que acontece no sistema.</li>
            </ul>
          </div>
        `,
        attachTo: { element: '[data-tour="chart-donut"]', on: 'left' },
        classes: 'shepherd-sdc shepherd-sdc--blue',
        modalOverlayOpeningPadding: 12,
        modalOverlayOpeningRadius: 16,
        scrollTo: { behavior: 'smooth', block: 'center' },
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 8: Acoes (barra real do modulo) =====
      {
        id: 'acoes',
        title: 'Coluna de Acoes',
        text: `
          ${header(7, 'Coluna de Acoes')}
          <div class="shepherd-text">
            <p>${primaryModule.contextHint}</p>
            <div class="shepherd-action-row" aria-label="Pre-visualizacao da barra de acoes">
              <span class="shepherd-action-row-label">ACOES</span>
              <div class="shepherd-action-row-icons">
                <span class="shepherd-action-icon-pill shepherd-action-pill--view" title="Visualizar">${svgIcon('eye', 18)}</span>
                <span class="shepherd-action-icon-pill shepherd-action-pill--print" title="Imprimir / Exportar PDF">${svgIcon('printer', 18)}</span>
                <span class="shepherd-action-icon-pill shepherd-action-pill--edit" title="Editar">${svgIcon('pencil', 18)}</span>
                <span class="shepherd-action-icon-pill shepherd-action-pill--alert" title="Alerta / Pendencia">${svgIcon('alert', 18)}</span>
                <span class="shepherd-action-icon-pill shepherd-action-pill--delete" title="Excluir">${svgIcon('trash', 18)}</span>
                <span class="shepherd-action-icon-pill shepherd-action-pill--more" title="Mais opcoes">${svgIcon('dots', 18)}</span>
              </div>
            </div>
            <ul>
              <li><strong>Visualizar</strong> - abre o registro em modo leitura.</li>
              <li><strong>Imprimir / Exportar</strong> - gera PDF do registro.</li>
              <li><strong>Editar</strong> - aparece apenas se voce tem permissao.</li>
              <li><strong>Alerta / Pendencia</strong> - sinaliza item que precisa de atencao.</li>
              <li><strong>Excluir</strong> - move pra lixeira (auditado, restauravel).</li>
              <li><strong>Mais opcoes</strong> - acoes secundarias contextuais.</li>
            </ul>
            <p class="shepherd-acoes-hint">Voce ve esse padrao em <strong>${primaryModule.label}</strong> e nos demais modulos - botoes podem aparecer ou nao conforme seu perfil.</p>
          </div>
        `,
        classes: 'shepherd-sdc shepherd-sdc--blue shepherd-sdc--actions',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          { classes: 'shepherd-button-primary', text: 'Avancar', action() { this.next(); } },
        ],
        when: { show() { onStepShown(); } },
      },

      // ===== Passo 9: Aceite (centralizado cinematografico + slogan laranja) =====
      {
        id: 'aceite',
        title: 'Termo de aceite institucional',
        text: `
          <div class="shepherd-particles"><span></span><span></span><span></span><span></span></div>
          ${progressDotsHtml(8)}
          <div class="shepherd-hero shepherd-hero--finale">
            <div class="shepherd-hero-badge"><span class="shepherd-hero-dot"></span> ULTIMO PASSO - ACEITE</div>
            <h1 class="shepherd-hero-title">Antes de comecar, leia abaixo</h1>
            <p class="shepherd-hero-sub">Role o conteudo ate o final para liberar o botao <em>Aceitar e Comecar</em>.</p>
            <div class="shepherd-accept-box">
              <div class="shepherd-accept-card shepherd-accept-card--danger">
                <span class="shepherd-accept-icon-lg">${svgIcon('alert', 22)}</span>
                <div class="shepherd-accept-body">
                  <h4>Termos de Uso <span class="shepherd-accept-badge">obrigatorio</span></h4>
                  <p>O SDC e um sistema oficial do Estado de MG, de uso estritamente institucional e monitorado. Voce e responsavel por todas as acoes feitas sob suas credenciais. A insercao de dados falsos pode caracterizar crime de <strong>falsidade ideologica (Art. 299 CP)</strong> e <strong>insercao de dados falsos em sistema (Art. 313-A CP)</strong>. <a href="#" data-final-action="open-termos">Ler integra dos Termos</a>.</p>
                </div>
              </div>
              <div class="shepherd-accept-card shepherd-accept-card--info">
                <span class="shepherd-accept-icon-lg">${svgIcon('shield', 22)}</span>
                <div class="shepherd-accept-body">
                  <h4>Privacidade <span class="shepherd-accept-badge">lgpd</span></h4>
                  <p>Seus dados sao tratados conforme a <strong>Lei 13.709/2018 (LGPD)</strong>, com finalidade de gestao de riscos, auditoria e cumprimento de obrigacoes legais. Protegidos por TLS 1.3 e AES-256, armazenados exclusivamente em territorio brasileiro. Voce tem direito a confirmacao, acesso, correcao, portabilidade e revogacao. <a href="#" data-final-action="open-privacidade">Ler integra da Politica</a>.</p>
                </div>
              </div>
              <div class="shepherd-accept-card shepherd-accept-card--warn">
                <span class="shepherd-accept-icon-lg">${svgIcon('check', 22)}</span>
                <div class="shepherd-accept-body">
                  <h4>Suporte e replay deste tour <span class="shepherd-accept-badge">dica</span></h4>
                  <p>Em caso de duvidas, acesse o <strong>menu Suporte</strong> no rodape. La voce encontra o Manual de Modulos, abertura de chamados e o botao <em>Reiniciar Tour Guiado</em> para revisar esta apresentacao quantas vezes precisar. <a href="#" data-final-action="open-guia">Abrir Guia do Sistema</a>.</p>
                </div>
              </div>
              <div class="shepherd-accept-card shepherd-accept-card--success">
                <span class="shepherd-accept-icon-lg">${svgIcon('check', 22)}</span>
                <div class="shepherd-accept-body">
                  <h4>Confirmacao final <span class="shepherd-accept-badge">aceite</span></h4>
                  <p>Ao clicar em <strong>Aceitar e Comecar</strong>, voce confirma ter lido e concorda integralmente com os Termos de Uso, com a Politica de Privacidade (LGPD) e declara ciencia das responsabilidades civis e criminais aplicaveis ao uso deste sistema oficial.</p>
                </div>
              </div>
              <div class="shepherd-accept-scroll-hint">role para baixo para continuar ↓</div>
            </div>
            <div class="shepherd-finale-slogan">
              <span class="shepherd-finale-rule"></span>
              <span class="shepherd-finale-text">DEFESA CIVIL SOMOS TODOS NOS</span>
              <span class="shepherd-finale-rule"></span>
            </div>
          </div>
        `,
        classes: 'shepherd-sdc shepherd-sdc--blue shepherd-sdc--cinematic shepherd-sdc--finale',
        buttons: [
          { classes: 'shepherd-button-secondary', text: 'Voltar', action() { this.back(); } },
          {
            classes: 'shepherd-button-primary shepherd-accept-btn',
            text: 'Aceitar e Comecar',
            action() { this.complete(); },
            disabled: true,
          },
        ],
        when: {
          show() {
            onStepShown(attachAcceptScrollListener, fireConfetti);
          },
        },
      },
    ];
  }

  function recordCompletion() {
    if (skipPersist) return;
    axios.post(route('onboarding.tour.complete')).catch(() => {});
  }

  function startWelcomeTour() {
    if (tour) {
      if (!tour.isActive?.()) tour.start();
      return tour;
    }

    forceLightTheme();
    lockBodyScroll();
    installBackdropSync();

    tour = new Shepherd.Tour({
      useModalOverlay: false,
      exitOnEsc: false,
      keyboardNavigation: false,
      defaultStepOptions: {
        scrollTo: { behavior: 'smooth', block: 'center' },
        cancelIcon: { enabled: false },
        classes: 'shepherd-sdc',
        modalOverlayOpeningPadding: 6,
        modalOverlayOpeningRadius: 12,
      },
    });

    buildSteps().forEach((step) => tour.addStep(step));

    tour.on('complete', () => {
      recordCompletion();
      restoreTheme();
      unlockBodyScroll();
      removeBackdropSync();
    });

    // Modo obrigatorio: bloqueia cancel - re-mostra o passo atual.
    tour.on('cancel', () => {
      if (tour?.getCurrentStep?.()) {
        tour.show(tour.getCurrentStep().id);
      }
    });

    escListener = blockEscape;
    window.addEventListener('keydown', escListener, true);

    tour.start();
    return tour;
  }

  onBeforeUnmount(() => {
    if (tour && tour.isActive?.()) tour.cancel();
    if (escListener) {
      window.removeEventListener('keydown', escListener, true);
      escListener = null;
    }
    removeBackdropSync();
    restoreTheme();
    unlockBodyScroll();
    tour = null;
  });

  return { startWelcomeTour };
}
