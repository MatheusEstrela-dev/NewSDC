import { test, expect } from '@playwright/test';

test.describe('User Menu Dropdown', () => {
  test.beforeEach(async ({ page }) => {
    // Navegar para a página de login
    await page.goto('/login');

    // Fazer login (ajuste com credenciais válidas)
    await page.fill('input[type="email"]', 'admin@sdc.com');
    await page.fill('input[type="password"]', 'senha123');
    await page.click('button[type="submit"]');

    // Aguardar navegação após login
    await page.waitForURL('**/dashboard');
  });

  test('deve abrir o dropdown ao clicar no avatar', async ({ page }) => {
    // Verificar que o dropdown está oculto inicialmente
    const dropdown = page.locator('.user-menu div[class*="absolute"]');
    await expect(dropdown).toBeHidden();

    // Clicar no botão do avatar
    const userButton = page.locator('.user-menu button');
    await userButton.click();

    // Aguardar um pouco para a animação
    await page.waitForTimeout(100);

    // Verificar que o dropdown está visível
    await expect(dropdown).toBeVisible();

    // Verificar que os itens do menu estão visíveis
    await expect(page.getByText('Meu Perfil')).toBeVisible();
    await expect(page.getByText('Sair')).toBeVisible();
  });

  test('deve manter o dropdown aberto ao clicar dentro dele', async ({ page }) => {
    // Abrir o dropdown
    await page.locator('.user-menu button').click();
    await page.waitForTimeout(100);

    const dropdown = page.locator('.user-menu div[class*="absolute"]');
    await expect(dropdown).toBeVisible();

    // Clicar dentro do dropdown (não em um link)
    await dropdown.click({ position: { x: 10, y: 10 } });
    await page.waitForTimeout(100);

    // O dropdown deve permanecer aberto
    await expect(dropdown).toBeVisible();
  });

  test('deve fechar o dropdown ao clicar fora', async ({ page }) => {
    // Abrir o dropdown
    await page.locator('.user-menu button').click();
    await page.waitForTimeout(100);

    const dropdown = page.locator('.user-menu div[class*="absolute"]');
    await expect(dropdown).toBeVisible();

    // Clicar fora do dropdown
    await page.locator('header').click({ position: { x: 10, y: 10 } });
    await page.waitForTimeout(100);

    // O dropdown deve estar oculto
    await expect(dropdown).toBeHidden();
  });

  test('deve debugar o comportamento do clique', async ({ page }) => {
    console.log('🔍 Iniciando teste de debug...');

    // Adicionar listeners de eventos
    await page.evaluate(() => {
      window.clickEvents = [];
      document.addEventListener('click', (e) => {
        window.clickEvents.push({
          target: e.target.className,
          timestamp: Date.now()
        });
      }, true);
    });

    const userButton = page.locator('.user-menu button');

    // Verificar estado inicial
    const dropdownBefore = page.locator('.user-menu div[class*="absolute"]');
    const isVisibleBefore = await dropdownBefore.isVisible().catch(() => false);
    console.log('📊 Dropdown visível antes do clique:', isVisibleBefore);

    // Clicar no botão
    console.log('🖱️ Clicando no botão do usuário...');
    await userButton.click();

    // Aguardar e verificar
    await page.waitForTimeout(200);

    const isVisibleAfter = await dropdownBefore.isVisible().catch(() => false);
    console.log('📊 Dropdown visível depois do clique:', isVisibleAfter);

    // Pegar eventos de clique registrados
    const clickEvents = await page.evaluate(() => window.clickEvents);
    console.log('📝 Eventos de clique registrados:', clickEvents);

    // Tirar screenshot para análise
    await page.screenshot({ path: 'tests/e2e/screenshots/dropdown-debug.png', fullPage: true });
    console.log('📸 Screenshot salva em tests/e2e/screenshots/dropdown-debug.png');
  });
});
