// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Issue #86 - Área do Gestor (Capacitações).
 *
 * NAO HA spec de Treinamento (nem de nenhum outro modulo) neste checkout para
 * copiar o padrao de login/seed: `tests/e2e/` nao existia antes deste
 * arquivo (confirmado - SDC/.gitignore ignora `tests/`, entao specs escritas
 * em sessoes anteriores nunca chegaram ao git) e `tests/e2e/.env.test`
 * (referenciado pelos scripts `test:e2e*` do package.json) tambem nao existe.
 * Este spec assume, por isso, a convencao mais simples possivel: login real
 * pela tela (CPF + senha, ver resources/js/Pages/Auth/Login.vue) com uma
 * conta de teste passada por variavel de ambiente, e um curso ja
 * cadastrados (tambem por variavel de ambiente) - NAO cria dado de setup via
 * API/seeder porque este agente nao deve gravar no banco de dev.
 *
 * Variaveis de ambiente esperadas (tests/e2e/.env.test, carregado pelo
 * dotenvx nos scripts test:e2e* do package.json):
 *   E2E_GESTOR_CPF            CPF (so digitos ou formatado) de um usuario com
 *                              treinamento.gestor.acessar + .cursos.configurar
 *                              (cargo "manager" ou "admin").
 *   E2E_GESTOR_SENHA          senha dessa conta.
 *   E2E_TREINAMENTO_ID        id de um curso PRESENCIAL/SEMI_PRESENCIAL/ONLINE
 *                              existente, para abrir a
 *                              pagina de gestao sem depender de criar curso.
 *
 * TEST_BASE_URL (playwright.config.js) precisa apontar para um ambiente de
 * TESTE/dev local, nunca para producao nem para a URL publica do Azure.
 */

const CPF = process.env.E2E_GESTOR_CPF;
const SENHA = process.env.E2E_GESTOR_SENHA;
const TREINAMENTO_ID = process.env.E2E_TREINAMENTO_ID;

test.describe('Área do Gestor - Capacitações (issue #86)', () => {
  test.skip(!CPF || !SENHA || !TREINAMENTO_ID, 'E2E_GESTOR_CPF, E2E_GESTOR_SENHA e E2E_TREINAMENTO_ID precisam estar definidas (tests/e2e/.env.test).');

  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.locator('#cpf').fill(CPF);
    await page.locator('#password').fill(SENHA);
    await page.getByRole('button', { name: 'Acessar Sistema' }).click();
    // Login via Inertia: sem navegação de página cheia, só a troca do componente.
    await expect(page).not.toHaveURL(/\/login$/);
  });

  test('botão "Gestão do curso" na tela do curso leva à Área do Gestor', async ({ page }) => {
    await page.goto(`/treinamentos/${TREINAMENTO_ID}`);

    const botao = page.getByRole('link', { name: 'Gestão do curso' });
    await expect(botao).toBeVisible();
    await botao.click();

    await expect(page).toHaveURL(new RegExp(`/treinamentos-gestor/${TREINAMENTO_ID}$`));
  });

  test('título da página e rótulo de modalidade aparecem no cabeçalho da gestão', async ({ page }) => {
    await page.goto(`/treinamentos-gestor/${TREINAMENTO_ID}`);

    // PageHeader: :title="curso.titulo" e description="Gestão do curso · {tipo_label}".
    await expect(page.getByText('Gestão do curso ·')).toBeVisible();
  });

  test('histórico do curso aparece em cards dentro da seção recolhível', async ({ page }) => {
    await page.goto(`/treinamentos-gestor/${TREINAMENTO_ID}`);

    const secaoHistorico = page.getByRole('button', { name: /Histórico/ });
    await expect(secaoHistorico).toBeVisible();
    await secaoHistorico.click();

    // Pelo menos CURSO_CRIADO sempre existe (TreinamentoService::create grava
    // no histórico desde o bloco 1).
    await expect(page.getByText(/criado/i).first()).toBeVisible();
  });

  test('cronograma mostra o calendário (EscalaCalendario reaproveitado do Plantão)', async ({ page }) => {
    await page.goto(`/treinamentos-gestor/${TREINAMENTO_ID}`);

    const secaoCronograma = page.getByRole('button', { name: /Cronograma/ });
    await expect(secaoCronograma).toBeVisible();
    await secaoCronograma.click();

    // O calendário busca por axios (GestaoCursoConsulta::cronograma), não é
    // prop Inertia: espera a navegação de mês ou a grade aparecer.
    await expect(page.locator('.vc-container, [class*="calendar"]').first()).toBeVisible({ timeout: 10000 });
  });
});
