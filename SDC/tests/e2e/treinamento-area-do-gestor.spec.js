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
 * conta de teste passada por variavel de ambiente, e um curso/turma/evento ja
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
 *                              existente (usa_turma() true), para abrir a
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

  test('criar turma com vagas e período aparece na lista de turmas', async ({ page }) => {
    await page.goto(`/treinamentos-gestor/${TREINAMENTO_ID}`);

    await page.getByRole('button', { name: 'Nova turma' }).click();

    const nomeDaTurma = `Turma E2E ${Date.now()}`;
    await page.getByPlaceholder('Ex: Turma 1 - Manhã').fill(nomeDaTurma);
    await page.getByLabel(/Data de início/).fill(new Date().toISOString().slice(0, 10));
    const fim = new Date();
    fim.setMonth(fim.getMonth() + 1);
    await page.getByLabel(/Data de término/).fill(fim.toISOString().slice(0, 10));
    await page.getByLabel(/Número de vagas/).fill('10');

    await page.getByRole('button', { name: 'Criar turma' }).click();

    await expect(page.getByText(nomeDaTurma)).toBeVisible();
    await expect(page.getByText(/Turma criada/i)).toBeVisible();
  });

  test('turma com data de término antes do início é recusada com mensagem clara', async ({ page }) => {
    await page.goto(`/treinamentos-gestor/${TREINAMENTO_ID}`);

    await page.getByRole('button', { name: 'Nova turma' }).click();

    await page.getByPlaceholder('Ex: Turma 1 - Manhã').fill(`Turma invalida ${Date.now()}`);
    const hoje = new Date();
    const ontem = new Date(hoje);
    ontem.setDate(ontem.getDate() - 1);
    await page.getByLabel(/Data de início/).fill(hoje.toISOString().slice(0, 10));
    await page.getByLabel(/Data de término/).fill(ontem.toISOString().slice(0, 10));
    await page.getByLabel(/Número de vagas/).fill('5');

    await page.getByRole('button', { name: 'Criar turma' }).click();

    // Validação de formulário (after_or_equal:data_inicio, TurmaStoreRequest)
    // ou a mensagem de domínio de GestaoCursoComandos::exigirPeriodoValido -
    // qualquer uma das duas é uma recusa clara, nunca um turma criada.
    await expect(page.getByText(/termino não pode ser antes|term[ií]no não pode ser antes|data de término deve ser/i)).toBeVisible();
  });
});
