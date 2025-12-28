import { test, expect } from '@playwright/test';

test.describe('User Dropdown - Debug', () => {
  test('verificar estrutura do dropdown', async ({ page }) => {
    // Ir para a página de login primeiro
    await page.goto('http://localhost:8000/login');

    // Fazer login (ajuste credenciais se necessário)
    await page.fill('input[name="email"]', 'admin@sdc.com');
    await page.fill('input[name="password"]', 'senha123');

    await page.click('button[type="submit"]');

    // Esperar carregar o dashboard
    await page.waitForTimeout(2000);

    console.log('✅ Login realizado');

    // Procurar o container do user menu
    const userMenuContainer = page.locator('.user-menu');
    await expect(userMenuContainer).toBeVisible();
    console.log('✅ Container .user-menu encontrado');

    // Procurar o botão dentro do user menu
    const userButton = userMenuContainer.locator('button');
    await expect(userButton).toBeVisible();
    console.log('✅ Botão do usuário encontrado');

    // Verificar se o dropdown existe (mesmo que oculto)
    const dropdown = userMenuContainer.locator('div[class*="absolute"]');
    console.log('📊 Número de dropdowns encontrados:', await dropdown.count());

    // Pegar o HTML do container user-menu
    const userMenuHTML = await userMenuContainer.innerHTML();
    console.log('📝 HTML do user-menu:');
    console.log(userMenuHTML.substring(0, 500) + '...');

    // Verificar se v-if está no elemento
    const dropdownIsPresent = await dropdown.count() > 0;
    console.log('📊 Dropdown presente no DOM:', dropdownIsPresent);

    if (!dropdownIsPresent) {
      console.log('⚠️ PROBLEMA: Dropdown não está no DOM inicialmente (v-if está oculto)');
    }

    // Clicar no botão
    console.log('🖱️ Clicando no botão do usuário...');
    await userButton.click();

    // Esperar um pouco
    await page.waitForTimeout(500);

    // Verificar novamente se o dropdown apareceu
    const dropdownCountAfter = await dropdown.count();
    console.log('📊 Dropdowns após clique:', dropdownCountAfter);

    if (dropdownCountAfter > 0) {
      const isVisible = await dropdown.isVisible();
      console.log('📊 Dropdown visível após clique:', isVisible);

      // Pegar HTML do dropdown
      const dropdownHTML = await dropdown.innerHTML();
      console.log('📝 HTML do dropdown:');
      console.log(dropdownHTML.substring(0, 300));
    } else {
      console.log('❌ ERRO: Dropdown ainda não está no DOM após o clique!');
    }

    // Tirar screenshot
    await page.screenshot({
      path: 'tests/e2e/screenshots/dropdown-after-click.png',
      fullPage: true
    });
    console.log('📸 Screenshot salva');

    // Verificar o valor de showUserMenu no Vue
    const showUserMenuValue = await page.evaluate(() => {
      // Tentar acessar a instância do Vue
      const app = document.querySelector('[data-page]');
      return app ? 'Vue instance found' : 'No Vue instance';
    });
    console.log('🔍 Vue instance:', showUserMenuValue);
  });
});
