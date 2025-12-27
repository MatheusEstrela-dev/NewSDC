/**
 * Teste funcional Playwright para debugar o problema de serialização TDAP
 *
 * Execução:
 * npx playwright test test-tdap-debug.js --headed
 */

const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  // Interceptar requisições para capturar dados
  page.on('response', async (response) => {
    const url = response.url();

    // Capturar apenas respostas das rotas TDAP
    if (url.includes('/tdap/recebimentos') || url.includes('/tdap/movimentacoes')) {
      console.log('\n==========================================');
      console.log(`📡 REQUISIÇÃO: ${url}`);
      console.log(`📊 STATUS: ${response.status()}`);
      console.log(`📋 CONTENT-TYPE: ${response.headers()['content-type']}`);

      try {
        const body = await response.text();

        // Tentar extrair o JSON da página Inertia
        if (body.includes('data-page')) {
          const match = body.match(/data-page="([^"]+)"/);
          if (match) {
            const pageData = JSON.parse(match[1].replace(/&quot;/g, '"'));

            console.log('\n🎯 DADOS INERTIA CAPTURADOS:');
            console.log('==========================================');

            if (pageData.props) {
              // Verificar recebimentos
              if (pageData.props.recebimentos !== undefined) {
                console.log('\n📦 RECEBIMENTOS:');
                console.log(`   Tipo: ${Array.isArray(pageData.props.recebimentos) ? 'Array ✅' : 'Object ❌'}`);
                console.log(`   Estrutura:`, JSON.stringify(pageData.props.recebimentos, null, 2).substring(0, 500));

                if (!Array.isArray(pageData.props.recebimentos)) {
                  console.log('\n❌ ERRO DETECTADO: recebimentos deveria ser Array, mas é Object!');
                  console.log('   Keys do Object:', Object.keys(pageData.props.recebimentos));

                  if (pageData.props.recebimentos.data) {
                    console.log('   ⚠️  Contém propriedade "data" - é um Paginator não serializado!');
                  }
                }
              }

              // Verificar movimentacoes
              if (pageData.props.movimentacoes !== undefined) {
                console.log('\n📦 MOVIMENTACOES:');
                console.log(`   Tipo: ${Array.isArray(pageData.props.movimentacoes) ? 'Array ✅' : 'Object ❌'}`);
                console.log(`   Estrutura:`, JSON.stringify(pageData.props.movimentacoes, null, 2).substring(0, 500));

                if (!Array.isArray(pageData.props.movimentacoes)) {
                  console.log('\n❌ ERRO DETECTADO: movimentacoes deveria ser Array, mas é Object!');
                  console.log('   Keys do Object:', Object.keys(pageData.props.movimentacoes));

                  if (pageData.props.movimentacoes.data) {
                    console.log('   ⚠️  Contém propriedade "data" - é um Paginator não serializado!');
                  }
                }
              }

              // Verificar pagination
              if (pageData.props.pagination !== undefined) {
                console.log('\n📄 PAGINATION:');
                console.log(`   Tipo: ${typeof pageData.props.pagination}`);
                console.log(`   Estrutura:`, JSON.stringify(pageData.props.pagination, null, 2));
              }

              // Verificar filters e statistics
              console.log('\n📋 OUTROS PROPS:');
              console.log(`   filters: ${typeof pageData.props.filters}`);
              console.log(`   statistics: ${typeof pageData.props.statistics}`);
            }

            console.log('\n==========================================\n');
          }
        }
      } catch (err) {
        console.log(`❌ Erro ao processar resposta: ${err.message}`);
      }
    }
  });

  console.log('🚀 Iniciando teste funcional TDAP...\n');

  try {
    // 1. Ir para a página de login
    console.log('1️⃣  Acessando página de login...');
    await page.goto('http://localhost:8001/login');
    await page.waitForLoadState('networkidle');

    // 2. Fazer login (ajuste as credenciais se necessário)
    console.log('2️⃣  Fazendo login...');
    await page.fill('input[type="email"]', 'admin@defesa.mg.gov.br');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    console.log('3️⃣  Login realizado, aguardando dashboard...');
    await page.waitForTimeout(2000);

    // 3. Testar rota de recebimentos
    console.log('\n4️⃣  Acessando /tdap/recebimentos...\n');
    await page.goto('http://localhost:8001/tdap/recebimentos');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // 4. Testar rota de movimentações
    console.log('\n5️⃣  Acessando /tdap/movimentacoes...\n');
    await page.goto('http://localhost:8001/tdap/movimentacoes');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // 5. Capturar erros do console
    console.log('\n6️⃣  Capturando erros do console do navegador...\n');
    page.on('console', msg => {
      if (msg.type() === 'error' || msg.type() === 'warning') {
        console.log(`🔴 Console ${msg.type()}: ${msg.text()}`);
      }
    });

    page.on('pageerror', error => {
      console.log(`💥 Page Error: ${error.message}`);
    });

    console.log('\n✅ Teste concluído! Verifique os logs acima.\n');
    console.log('==========================================');
    console.log('ANÁLISE DOS RESULTADOS:');
    console.log('==========================================');
    console.log('Se você viu "Object ❌" em vez de "Array ✅":');
    console.log('  → O backend AINDA está retornando o Paginator completo');
    console.log('  → O método executeAsDTO() NÃO está sendo executado');
    console.log('  → Possível causa: OPcache ainda com código antigo');
    console.log('');
    console.log('Se você viu "Array ✅":');
    console.log('  → O backend está correto!');
    console.log('  → O problema pode ser cache do navegador');
    console.log('  → Tente Ctrl+Shift+R para hard refresh');
    console.log('==========================================\n');

  } catch (error) {
    console.error('❌ Erro durante o teste:', error);
  }

  // await browser.close();
})();
