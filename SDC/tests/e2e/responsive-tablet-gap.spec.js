/**
 * Fase 2 - Regressao: gap preto a esquerda em viewport tablet (768-1023px).
 *
 * Bug original (screenshot 2026-05-15): em ~895px, AuthenticatedLayout aplicava
 * md:ml-20 (80px) mas a Sidebar so renderizava conteudo visivel quando
 * isSidebarOpen===true, resultando em ~80px de fundo preto a esquerda.
 *
 * Fix: Sidebar.vue agora computa effectivelyCollapsed=true em tablet (sem
 * drawer aberto), aplicando a classe is-collapsed que renderiza icones-only
 * no rail de 80px.
 *
 * Este teste valida que em qualquer viewport tablet:
 *   1. A sidebar tem largura 80px (rail collapsed)
 *   2. Existem icones visiveis dentro do rail (.nav-item-icon)
 *   3. O conteudo principal (main) comeca em x >= 80 (sem gap)
 *
 * REQUISITOS: app rodando em https://localhost:19443 com sessao autenticada
 * via TEST_AUTH_TOKEN ou storageState (configurar em playwright.config.js).
 * Como nem todos os ambientes tem auth automatica, este teste e marcado
 * test.skip por padrao. Remover .skip apos configurar auth.
 */
import { test, expect } from '@playwright/test';

const TABLET_VIEWPORTS = [
  { name: 'md-768', width: 768, height: 1024 },
  { name: 'md-895', width: 895, height: 900 }, // viewport da screenshot original
  { name: 'md-1023', width: 1023, height: 768 },
];

test.describe.skip('Tablet gap regression (Fase 2)', () => {
  for (const vp of TABLET_VIEWPORTS) {
    test(`dashboard em ${vp.name} (${vp.width}x${vp.height}) - sem gap a esquerda`, async ({ page }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      const sidebar = page.locator('aside.sidebar');
      await expect(sidebar).toBeVisible();

      const sidebarBox = await sidebar.boundingBox();
      expect(sidebarBox?.x).toBe(0);
      expect(sidebarBox?.width).toBeGreaterThanOrEqual(70);
      expect(sidebarBox?.width).toBeLessThanOrEqual(90);

      const navIcons = page.locator('aside.sidebar .nav-item-icon');
      const visibleIcons = await navIcons.count();
      expect(visibleIcons).toBeGreaterThan(0);

      const main = page.locator('main').first();
      const mainBox = await main.boundingBox();
      expect(mainBox?.x).toBeGreaterThanOrEqual(70);
      expect(mainBox?.x).toBeLessThanOrEqual(90);
    });
  }

  test('drawer expande para 280px ao tocar no hamburguer em tablet', async ({ page }) => {
    await page.setViewportSize({ width: 895, height: 900 });
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    const hamburger = page.locator('button.hamburger-button, [aria-label*="menu" i]').first();
    await hamburger.click();

    const sidebar = page.locator('aside.sidebar.is-mobile-open');
    await expect(sidebar).toBeVisible();
    const box = await sidebar.boundingBox();
    expect(box?.width).toBeGreaterThanOrEqual(270);
  });
});

/**
 * Smoke test que NAO depende de autenticacao: validar layout publico /login
 * (ou outra rota publica) em tablet, garantindo que pelo menos o markup
 * basico nao quebra.
 */
test.describe('Tablet layout smoke (publico, sem auth)', () => {
  for (const vp of TABLET_VIEWPORTS) {
    test(`login em ${vp.name} renderiza sem erros console`, async ({ page }) => {
      const consoleErrors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
      });

      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/login');
      await page.waitForLoadState('networkidle');

      expect(consoleErrors).toHaveLength(0);
      const horizontalOverflow = await page.evaluate(
        () => document.documentElement.scrollWidth > window.innerWidth,
      );
      expect(horizontalOverflow).toBe(false);
    });
  }
});
