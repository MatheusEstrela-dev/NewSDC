# Cedec Fase 5 — Relatorios de Contato Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar a pagina `/cedec/contatos` com as abas de e-mails e telefones, os blocos de 50 contatos prontos para colar no Outlook da Cidade Administrativa (com botao de copiar, que o legado nunca teve) e o export CSV das duas abas.

**Architecture:** Um service (`ContatoRelatorioService`) le `municipios LEFT JOIN compdec_prefeituras`, higieniza os contatos, descarta os vazios ANTES de blocar e devolve blocos de tamanho configuravel, alem das matrizes de CSV. Um controller Inertia serve a pagina e o `StreamedResponse` do CSV. No frontend, a pagina orquestra (abas, aba ativa, modal de export), o organismo `ContatoBlocosOutlook` concentra a interacao de copiar (e e o unico que invoca `useCopiarTexto`), e a molecula `ContatoBloco` so exibe e emite.

**Tech Stack:** Laravel 12 / PHP 8.3, Inertia, Vue 3 `<script setup>`, Tailwind (dark mode por classe), Ziggy, PostgreSQL, Octane/FrankenPHP. Testes em **PHPUnit 11** (nao Pest), com `DatabaseTransactions` e `Inertia\Testing\AssertableInertia`.

**Spec:** `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md` (secao 8)
**Contrato de interfaces:** `docs/superpowers/plans/2026-09-04-cedec-contrato-interfaces.md` (secoes 0, 4, 6 e 8)

---

## Global Constraints

### Ambiente de execucao — corrigido em 2026-09-05, medido

Estas quatro correcoes valem para TODOS os steps deste plano e substituem qualquer
comando divergente no corpo dele.

1. **O container e `newsdc_dev_app`**, imagem `newsdc-swoole-dev` (Swoole, nao FrankenPHP),
   com a aplicacao em `/var/www`. O nome `newsdc_frankenphp_local` do `.claude/kernel.py`
   NAO EXISTE. O Postgres de desenvolvimento e `newsdc_dev_db`, publicado no host em 5434.

2. **Teste roda no HOST, nunca no container.** `docker exec newsdc_dev_app php artisan test`
   falha com `Command "test" is not defined` — a imagem nao tem dev dependencies. Exporte
   uma vez por terminal, a partir de `SDC/`:

   ```bash
   mkdir -p /c/tmp/newsdc-cache
   export MSYS_NO_PATHCONV=1
   export APP_CONFIG_CACHE=/tmp/newsdc-cache/nao-existe-config.php
   export APP_PACKAGES_CACHE=/tmp/newsdc-cache/packages.php
   export APP_SERVICES_CACHE=/tmp/newsdc-cache/services.php
   export APP_ROUTES_CACHE=/tmp/newsdc-cache/routes.php
   export APP_EVENTS_CACHE=/tmp/newsdc-cache/events.php
   export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc
   export DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)"
   ```

   As CINCO variaveis de cache sao obrigatorias, e essa lista foi paga com dor nesta
   sessao. `APP_CONFIG_CACHE` sozinho NAO basta: ele cobre so o cache de config, e o
   PHPUnit do host continua reescrevendo `bootstrap/cache/packages.php` e `services.php`,
   que sao bind-mount compartilhado com o container. Como a imagem foi buildada sem dev
   dependencies, o manifest escrito pelo host descreve outro conjunto de providers e TODO
   `artisan` dentro do container passa a morrer em `ProviderRepository` — no meu caso com
   `Class "App\Modules\Cemaden\CemadenServiceProvider" not found`, apesar de a classe
   existir e `class_exists()` devolver true la dentro. Recuperacao: apagar os dois
   arquivos e rodar `artisan` DUAS vezes (a primeira falha recompilando, a segunda passa).

   Os caminhos precisam comecar com `/`, e por isso o `MSYS_NO_PATHCONV=1`. O
   `Application::normalizeCachePath` so trata como absoluto o que comeca com `/` ou `\`;
   se o Git Bash converter para `C:/tmp/...`, o Laravel concatena com o base path e o
   teste morre com "The <projeto>\C:/tmp/newsdc-cache directory must be present and
   writable". Com a barra preservada, o PHP no Windows resolve `/tmp/...` para `C:\tmp\...`. Os `DB_*` sao obrigatorios porque o `.env` aponta
   `DB_HOST=newsdc_db` (nome de rede Docker que o host nao resolve) e porque o
   `.env.testing` forca sqlite `:memory:` — e como `tests/TestCase.php` e vazio e nao roda
   migration, cair no sqlite da `no such table` na suite inteira.

3. **Os testes rodam contra o banco de DESENVOLVIMENTO.** Nenhum teste pode fazer `update()`
   ou `delete()` sem `where` restrito as linhas que ele mesmo criou, e assercao de contagem
   tem de ser relativa a um "antes", nunca total absoluto.

4. **`SDC/tests` esta no `.gitignore`.** Escrever o teste continua obrigatorio, mas ele NAO
   e versionado: todo `git add` dos steps leva so os arquivos de producao. Incluir caminho
   sob `SDC/tests` faz o `git add` ser recusado.

Valem para TODA task deste plano. Sao copia literal do contrato (secao 0) e das skills
`.claude/skills/frontend/03 - Layout` e `04 - Responsividade`.

- App executavel em `NewSDC/SDC`. Todo caminho deste plano e relativo a essa pasta.
- Backend modular em `app/Modules/Cedec`, rotas em `routes/modules/cedec.php`, frontend em `resources/js`.
- **Sem emoji dentro do codigo.** Emoji so na mensagem de commit (gitmoji).
- Commits: `<emoji> tipo(cedec): descricao` em pt-BR. **Sem trailer de co-autor.**
- Commit atomico: agrupar os arquivos que entregam UMA mudanca. Arquivo de teste criado so para depuracao nao entra no commit.
- Testes: `declare(strict_types=1)`, namespace `Tests\Feature\Cedec`, trait `DatabaseTransactions`, `AssertableInertia` para props, `Spatie\Permission\Models\Permission` para conceder slug.
- Verificacao backend: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=<Nome>`.
- Verificacao de sintaxe: `docker exec newsdc_dev_app php -l /var/www/<caminho>`.
- Verificacao frontend: `npm run build` na pasta `SDC`.
- Depois de mudar PHP: `docker exec newsdc_dev_app php artisan octane:reload` (~1s). Restart do container so para `.env`, `config/` ou extensao — custa ~3min.
- A calha horizontal e do `<main>`; a raiz da pagina **nao** leva `p-*` nem `px-*`. Leva `w-full pb-8`.
- Ritmo vertical de 24px vem de UMA fonte so: `mb-6` nos filhos (Forma A) **ou** `space-y-6` no pai, nunca os dois.
- `document.documentElement.scrollWidth - clientWidth === 0` em 375px **e** em 840px. Quem transborda rola/quebra dentro de si.
- Breakpoint por `useMobile`, alinhado em `lg`. Tabela vira BLOCO no mobile.
- Dark mode por CLASSE (`dark:`), nunca `prefers-color-scheme`.
- **Nenhum `<style scoped>` novo.** So Tailwind.
- Atomo e molecula **nao** chamam API nem estado global; organismo concentra interacao; a pagina orquestra.
- Nao reimplementar header, secao, card, aba, tabela vazia ou botao: usar os componentes ja existentes listados na secao 8 do contrato.

---

## Consumes (o que a fase 5 assume pronto)

Esta fase **nao cria nada disso**; se faltar, pare e conclua a fase correspondente antes.

**Da fase 1** (`2026-09-04-cedec-fase1-dados-etl.md`):

- Colunas em `compdec_prefeituras`, consolidadas em `database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php`: `prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura`.
- `App\Modules\Compdec\Models\Prefeitura` com essas sete colunas em `$fillable` (o model fica no Compdec; o Cedec importa).
- Colunas que ja existiam e a fase 5 tambem le: `municipio_id` (unique, FK para `municipios`), `prefeito_telefone`, `prefeito_celular`, `deleted_at` (SoftDeletes).

**Da fase 2** (`2026-09-04-cedec-fase2-backend-permissoes.md`):

- Modulo `app/Modules/Cedec/` existindo (namespace autoloadado por PSR-4).
- `routes/modules/cedec.php` criado e carregado por `routes/web.php` dentro do grupo autenticado.
- Slugs em `config/permissions.php`: `cedec.contatos.view` e `cedec.prefeituras.export` (alem de `cedec.prefeituras.view` e `.edit`).
- `MODULE_ICONS` de `resources/js/Support/moduleIcons.js` com `prefeituras: apartment`.

**Verificacao de que o Consumes esta satisfeito** (roda antes da Task 1):

```bash
docker exec newsdc_dev_app php artisan tinker --execute="echo implode(',', array_intersect(['email_prefeitura','tel_prefeitura','fax_prefeitura','tel_prefeitura_2','email_prefeitura_2','email_prefeitura_3'], \Illuminate\Support\Facades\Schema::getColumnListing('compdec_prefeituras')));"
```

Esperado: as seis colunas listadas. Se sair vazio ou incompleto, a fase 1 nao foi aplicada.

```bash
docker exec newsdc_dev_app php artisan route:list --name=cedec
```

Esperado: pelo menos as rotas `cedec.prefeituras.*`. Se o comando reclamar que nao ha rotas, a fase 2 nao foi aplicada.

---

## Produces (o que a fase 5 entrega para quem vier depois)

Assinaturas exatas, ja no contrato (secao 4) salvo onde indicado como **adicao declarada**.

```php
// App\Modules\Cedec\Services\ContatoRelatorioService
public const TAMANHO_BLOCO_PADRAO = 50;
public function emails(): \Illuminate\Support\Collection;
public function telefones(): \Illuminate\Support\Collection;
public function blocosDeEmail(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array;
public function blocosDeTelefone(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array;
public function csvEmails(): array;
public function csvTelefones(): array;

// App\Modules\Cedec\Controllers\ContatoRelatorioController
public function __construct(private readonly ContatoRelatorioService $service) {}
public function index(\Illuminate\Http\Request $request): \Inertia\Response;
public function export(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse;
```

Componentes Vue:

```
Pages/Cedec/Contatos/Index.vue                       (contrato, secao 8)
Components/Organisms/Cedec/ContatoBlocosOutlook.vue  props { blocos, tamanhoBloco }
Components/Molecules/Cedec/ContatoBloco.vue          props { indice, total, texto, copiado }  emit: copiar
Components/Organisms/Cedec/ContatoTabela.vue         props { colunas, linhas, campoTitulo }
```

**Adicoes declaradas ao contrato (secao 8), com o motivo:**

1. `ContatoBloco` ganha a prop `copiado: Boolean` (default `false`) e o emit `copiar`. O contrato lista so `{ indice, total, texto }`. Sem essas duas coisas a molecula precisaria invocar `useCopiarTexto` e guardar o proprio estado de feedback — a decisao de camada esta em "Decisao de camada: onde `useCopiarTexto` e invocado", logo abaixo.
2. Componente novo `Components/Organisms/Cedec/ContatoTabela.vue`. O contrato nomeia a tabela do modulo so para a listagem de prefeituras (`PrefeituraTable`, fase 3). A aba de e-mails e a de telefones tambem precisam de tabela, e ela e organismo (concentra a alternancia tabela/bloco por breakpoint), nao cabe na pagina nem na molecula. Um componente generico por colunas serve as duas abas — DRY.

---

## Decisao de camada: onde `useCopiarTexto` e invocado

Regra dura: **atomo e molecula nao chamam API nem estado global; organismo concentra interacao.**

`Composables/useCopiarTexto.js` devolve `{ copiar, copiado }`. `copiar(texto)` e `async`, devolve
`boolean`, usa `navigator.clipboard` com fallback para `textarea` + `document.execCommand`, e
`copiado` e um `ref(true)` que volta sozinho para `false` depois de 2500ms.

**Decisao: `useCopiarTexto` e invocado UMA vez, dentro de `ContatoBlocosOutlook.vue` (organismo).**

- `ContatoBloco.vue` (molecula) recebe `copiado` como prop e emite `copiar`. Nao importa composable nenhum, nao toca em `navigator`, nao guarda estado. E funcao pura de props.
- O organismo mantem `indiceCopiado = ref(null)` e passa `:copiado="indiceCopiado === bloco.indice"`. Isso resolve o efeito colateral de instanciar o composable N vezes: com uma instancia por bloco, o `copiado` de cada uma e independente mas nenhuma sabe qual bloco o usuario copiou; com uma instancia no organismo, o feedback aparece exatamente no bloco copiado.
- O `copiado` que vem do composable **nao e usado**: o organismo destrutura so `{ copiar }`. Quem manda no feedback visual e `indiceCopiado`, que sabe de qual bloco se trata.

---

## Risco principal desta fase: o transbordo horizontal do bloco de 50 e-mails

Um bloco de 50 e-mails concatenados com `"; "` e um texto de ~1500 caracteres **sem nenhuma
quebra de linha**. Em 375px, jogado num `<p>` comum, ele empurra o container, o container
empurra o `<main>` e a pagina inteira passa a rolar de lado — exatamente o defeito que a
regra 1 da skill de responsividade proibe.

**Tratamento obrigatorio, aplicado no `ContatoBloco.vue`:**

```html
<p class="mt-3 max-h-48 overflow-y-auto break-words [overflow-wrap:anywhere] ...">
```

- `break-words` (`overflow-wrap: break-word`) faz o texto quebrar nos separadores. Como ha um
  espaco depois de cada `;`, a quebra natural ja acontece na maioria dos casos.
- `[overflow-wrap:anywhere]` cobre o caso patologico: um unico e-mail mais largo que a
  viewport (dominio longo, 375px) nao tem espaco onde quebrar, e so `anywhere` parte no meio
  do token. Sem ele, UM e-mail comprido derruba a medicao inteira.
- `max-h-48 overflow-y-auto` limita a altura: o bloco rola **na vertical, dentro de si**, e a
  pagina nao cresce dez telas por aba.
- **Nao usar `overflow-x-auto` aqui.** Rolagem horizontal dentro do bloco esconde metade dos
  destinatarios atras de um gesto que o usuario nao tem motivo para tentar. O texto quebra.
- O pai flex do cabecalho do bloco leva `min-w-0`, e o botao de copiar leva `shrink-0`
  (regra 2 da skill: as tres partes ou nao funciona).

A mesma disciplina vale para a **celula de e-mail da tabela** (`ContatoTabela.vue`):
`break-words [overflow-wrap:anywhere]` na celula, e o wrapper da tabela com
`min-w-0 overflow-x-auto` no desktop.

**Medicao obrigatoria** (Task 5, no navegador, em 375px e 840px):

```js
const de = document.documentElement;
console.log({ viewport: de.clientWidth, excesso: de.scrollWidth - de.clientWidth });
```

Criterio: `excesso` **0** nas duas larguras.

---

## Nota obrigatoria sobre o banco de teste

`phpunit.xml` **nao troca a conexao de banco** (as linhas de sqlite estao comentadas). Os
testes rodam contra o banco de desenvolvimento, que ja tem as 853 prefeituras reais
carregadas.

**Correcao de code review, 2026-09-05: nenhum teste deste plano pode fazer UPDATE ou DELETE
em massa sobre linha que ele nao criou.** Uma versao anterior desta nota mandava zerar os 8
campos de contato de TODAS as prefeituras dentro do `setUp()`
(`DB::table('compdec_prefeituras')->update(self::CAMPOS_DE_CONTATO)`), protegido so pelo
rollback do `DatabaseTransactions`. Como `phpunit.xml` **nao isola o banco de teste**,
qualquer interrupcao antes do rollback — Ctrl+C, fatal error, timeout do container, OOM —
apagava em definitivo os contatos das 853 prefeituras carregados pelo ETL, sem backup. Um
teste nao pode ter como modo de falha a destruicao do banco de desenvolvimento.

A regra deste plano, em toda classe de teste: **cada teste opera so sobre as linhas que ele
proprio cria.** Duas tecnicas cobrem os casos que esta fase precisa:

1. **A fronteira de bloco (50/51/100/101) e a sanitizacao (`higienizar`/`higienizarEmail`)
   sao testadas via `ReflectionMethod` sobre os metodos privados do service**
   (`blocar()`, `contatos()`, `higienizar()`, `higienizarEmail()`), alimentados com um array
   ou uma `Collection` montados a mao pelo proprio teste. Nenhuma dessas chamadas toca o
   banco — e o mesmo caminho de codigo que `blocosDeEmail()` e `blocosDeTelefone()` executam
   por baixo, so que sem depender de quantos contatos ja existem no banco de desenvolvimento.
   Ver `ContatoRelatorioServiceTest` (Task 1).
2. **Onde o teste precisa mesmo ler do banco real** (higienizacao ponta a ponta, o
   `municipio_id` que aparece na listagem, quantidade de linhas do CSV, props do Inertia), a
   asserçao e de PERTENCIMENTO ou RELATIVA ao "antes" medido no proprio teste — nunca de
   indice fixo nem de total exato sobre uma consulta que enxerga as 853 prefeituras inteiras.
   Exemplos: `->firstWhere('municipio_id', $municipio->id)` numa `Collection`, um closure de
   `Collection::contains(...)` dentro do `where()` do `AssertableInertia`,
   `assertStringContainsString(...)` no `texto` de um bloco pedido com `$tamanho` bem alto (o
   que forca um unico bloco e evita depender de quebra de fronteira), ou
   `$antes = DB::table('municipios')->count()` seguido de `assertCount($antes + N + 1, ...)`.

Nenhuma classe de teste desta fase faz `update()` ou `delete()` sem `where` restrito as linhas
que o proprio teste inseriu. As fixtures continuam dentro da transacao do
`DatabaseTransactions`, que desfaz tudo ao final — agora como uma segunda camada de protecao,
nao a unica.

---

## File Structure

| Arquivo | Responsabilidade |
| --- | --- |
| `app/Modules/Cedec/Services/ContatoRelatorioService.php` | uma consulta, higienizacao, blocagem e matrizes de CSV. Sem HTTP, sem Inertia |
| `app/Modules/Cedec/Controllers/ContatoRelatorioController.php` | traduz request em parametros, escolhe a aba, renderiza Inertia, transmite o CSV |
| `routes/modules/cedec.php` | duas rotas novas dentro do grupo `cedec.` ja criado na fase 2 |
| `resources/js/Components/Molecules/Cedec/ContatoBloco.vue` | exibe indice, total e texto; botao copiar que so emite |
| `resources/js/Components/Organisms/Cedec/ContatoBlocosOutlook.vue` | lista de blocos, `useCopiarTexto`, toast, feedback por bloco |
| `resources/js/Components/Organisms/Cedec/ContatoTabela.vue` | tabela no desktop, cards no mobile, generico por colunas |
| `resources/js/Pages/Cedec/Contatos/Index.vue` | orquestra: abas, stat cards, secoes colapsaveis, modal de export |
| `tests/Feature/Cedec/ContatoRelatorioServiceTest.php` | fronteiras de bloco, higienizacao, excecao, CSV |
| `tests/Feature/Cedec/ContatoRelatorioHttpTest.php` | 403s, props Inertia, download do CSV |

---

### Task 1: `ContatoRelatorioService`

**Files:**
- Create: `app/Modules/Cedec/Services/ContatoRelatorioService.php`
- Test: `tests/Feature/Cedec/ContatoRelatorioServiceTest.php`

**Interfaces:**
- Consumes: tabela `compdec_prefeituras` com as sete colunas da fase 1; `App\Models\Municipio`; `Database\Factories\PrefeituraFactory` (ja existe, define `municipio_id => null`, entao o teste passa o id explicitamente).
- Produces: `App\Modules\Cedec\Services\ContatoRelatorioService` com `TAMANHO_BLOCO_PADRAO = 50`, `emails()`, `telefones()`, `blocosDeEmail(int)`, `blocosDeTelefone(int)`, `csvEmails()`, `csvTelefones()` — assinaturas do contrato, secao 4.

**Contratos de dado que este service fixa:**

- `indice` comeca em **1**. `total` e a quantidade de contatos DENTRO daquele bloco.
- Fronteira: 50 contatos -> 1 bloco; 51 -> 2; 100 -> 2; 101 -> 3.
- Contato vazio (`null`, `''`, so espacos, ou o sentinela `'-'` do legado) e descartado **antes** de blocar. E o bug do legado: `rel_email_ca.php` contava `($key+1) % 50` sobre TODAS as linhas, entao a "Parte 1" podia sair com 38 destinatarios uteis.
- E-mail sai sempre em **minusculas** e com `trim`.
- Ordem de concatenacao do bloco de telefone (contrato, secao 4): `tel_prefeitura`, `tel_prefeitura_2`, `prefeito_telefone`, `prefeito_celular`, `fax_prefeitura`.
- Ordem das colunas de `telefones()` e do CSV (contrato, secao 4, tipo de retorno): `tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura`, `prefeito_telefone`, `prefeito_celular`.
- `$tamanho < 1` lanca `InvalidArgumentException` **antes** de consultar o banco.
- Prefeitura com `deleted_at` preenchido nao entra: a condicao vai DENTRO do `leftJoin`, nunca num `where` — num `where` ela transformaria o LEFT JOIN em INNER e sumiria com os municipios sem prefeitura.

- [ ] **Step 1: Criar a pasta do service**

```bash
mkdir -p app/Modules/Cedec/Services tests/Feature/Cedec
```

- [ ] **Step 2: Escrever o teste que falha**

Criar `tests/Feature/Cedec/ContatoRelatorioServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Cedec\Services\ContatoRelatorioService;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Substitui rel_email.php e rel_email_ca.php do legado gestaocedec.
 *
 * O legado blocava contando ($key + 1) % 50 sobre TODAS as linhas, inclusive as
 * de e-mail vazio: a "Parte 1" prometia 50 destinatarios e entregava menos.
 * Aqui o vazio e descartado antes de blocar, e os testes de fronteira provam.
 *
 * Correcao de code review, 2026-09-05: esta classe NAO faz UPDATE ou DELETE em
 * massa em `compdec_prefeituras`. O banco de teste e o de desenvolvimento
 * (phpunit.xml nao troca a conexao), com as 853 prefeituras reais carregadas
 * pelo ETL, sem backup. A fronteira de bloco e a sanitizacao sao testadas via
 * `ReflectionMethod` sobre os metodos privados do service, com um array ou
 * uma Collection montados pelo proprio teste — o mesmo caminho de codigo que
 * `blocosDeEmail()`/`blocosDeTelefone()` executam por baixo, sem depender de
 * quantos contatos ja existem no banco. Os testes que precisam mesmo ler do
 * banco real criam a propria fixture e verificam PERTENCIMENTO
 * (`firstWhere`/`str_contains`) ou uma contagem RELATIVA ao "antes" — nunca
 * indice fixo nem total exato sobre uma consulta que enxerga as 853
 * prefeituras inteiras. Ver "Nota obrigatoria sobre o banco de teste", acima.
 */
class ContatoRelatorioServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Usado so para zerar os 8 campos de contato da PROPRIA fixture que o
     * teste cria, dentro de prefeituraCom() — nunca em UPDATE de massa. Sem
     * isso, os valores que PrefeituraFactory sorteia para os campos que o
     * teste nao mencionou poderiam contaminar as asserçoes.
     */
    private const CAMPOS_DE_CONTATO = [
        'email_prefeitura' => null,
        'email_prefeitura_2' => null,
        'email_prefeitura_3' => null,
        'tel_prefeitura' => null,
        'tel_prefeitura_2' => null,
        'fax_prefeitura' => null,
        'prefeito_telefone' => null,
        'prefeito_celular' => null,
    ];

    private function servico(): ContatoRelatorioService
    {
        return app(ContatoRelatorioService::class);
    }

    /** @param array<string, string|null> $contatos */
    private function prefeituraCom(array $contatos): Municipio
    {
        $municipio = Municipio::factory()->create();

        Prefeitura::factory()->create(array_merge(
            self::CAMPOS_DE_CONTATO,
            $contatos,
            ['municipio_id' => $municipio->id],
        ));

        return $municipio;
    }

    private function criarComEmail(int $quantidade): void
    {
        for ($i = 1; $i <= $quantidade; $i++) {
            $this->prefeituraCom(['email_prefeitura' => sprintf('prefeitura%03d@exemplo.mg.gov.br', $i)]);
        }
    }

    /**
     * Chama o `blocar()` privado e estatico do service diretamente, sem
     * passar pelo banco. E o mesmo agrupamento que blocosDeEmail() e
     * blocosDeTelefone() aplicam depois de consultar e higienizar — testa-lo
     * isolado prova a fronteira sem depender de quantos contatos reais ja
     * existem no banco de desenvolvimento.
     *
     * @param  array<int, string>  $contatos
     * @return array<int, array{indice: int, total: int, texto: string}>
     */
    private function blocar(array $contatos, int $tamanho = ContatoRelatorioService::TAMANHO_BLOCO_PADRAO): array
    {
        $metodo = new ReflectionMethod(ContatoRelatorioService::class, 'blocar');
        $metodo->setAccessible(true);

        return $metodo->invoke(null, $contatos, $tamanho);
    }

    /**
     * Chama o `contatos()` privado do service diretamente, sobre uma
     * Collection montada a mao — sem passar pelo banco.
     *
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @param  array<int, string>  $campos
     * @return array<int, string>
     */
    private function contatos(Collection $linhas, array $campos): array
    {
        $metodo = new ReflectionMethod(ContatoRelatorioService::class, 'contatos');
        $metodo->setAccessible(true);

        return $metodo->invoke($this->servico(), $linhas, $campos);
    }

    /** Chama o `higienizarEmail()` privado e estatico do service diretamente. */
    private function higienizarEmail(?string $valor): ?string
    {
        $metodo = new ReflectionMethod(ContatoRelatorioService::class, 'higienizarEmail');
        $metodo->setAccessible(true);

        return $metodo->invoke(null, $valor);
    }

    /** Chama o `higienizar()` privado e estatico do service diretamente. */
    private function higienizar(?string $valor): ?string
    {
        $metodo = new ReflectionMethod(ContatoRelatorioService::class, 'higienizar');
        $metodo->setAccessible(true);

        return $metodo->invoke(null, $valor);
    }

    /** @return array<string, array{int, array<int, int>}> */
    public static function fronteirasDeBloco(): array
    {
        return [
            '50 contatos geram 1 bloco' => [50, [50]],
            '51 contatos geram 2 blocos' => [51, [50, 1]],
            '100 contatos geram 2 blocos' => [100, [50, 50]],
            '101 contatos geram 3 blocos' => [101, [50, 50, 1]],
        ];
    }

    /**
     * O coracao desta fase: a fronteira de bloco, testada direto sobre
     * `blocar()`, sem tocar o banco. A prova e a mesma de antes — so que
     * deterministica por construcao, em vez de depender de zerar as 853
     * prefeituras reais para ter uma contagem global previsivel.
     *
     * @param  array<int, int>  $totaisEsperados
     */
    #[DataProvider('fronteirasDeBloco')]
    public function test_blocar_respeita_a_fronteira_de_cinquenta(int $quantidade, array $totaisEsperados): void
    {
        $contatos = array_map(
            static fn (int $i): string => sprintf('prefeitura%03d@exemplo.mg.gov.br', $i),
            range(1, $quantidade),
        );

        $blocos = $this->blocar($contatos);

        $this->assertCount(count($totaisEsperados), $blocos);
        $this->assertSame($totaisEsperados, array_column($blocos, 'total'));
        $this->assertSame(range(1, count($totaisEsperados)), array_column($blocos, 'indice'));
    }

    public function test_higienizar_email_descarta_os_sentinelas_do_legado(): void
    {
        foreach ([null, '', '   ', '-'] as $sujo) {
            $this->assertNull($this->higienizarEmail($sujo), var_export($sujo, true).' deveria virar null.');
        }
    }

    /**
     * Reproduz "os quatro contatos sujos nao podem virar um segundo bloco"
     * sem tocar o banco: os quatro `null` abaixo sao exatamente o que
     * `higienizarEmail()` ja produz para null/''/'   '/'-' dentro de
     * linhas() — provado a parte no teste anterior.
     */
    public function test_contato_vazio_e_descartado_antes_de_blocar(): void
    {
        $linhas = new Collection(array_merge(
            array_map(
                static fn (int $i): array => ['email_prefeitura' => sprintf('prefeitura%03d@exemplo.mg.gov.br', $i)],
                range(1, 50),
            ),
            [
                ['email_prefeitura' => null],
                ['email_prefeitura' => null],
                ['email_prefeitura' => null],
                ['email_prefeitura' => null],
            ],
        ));

        $blocos = $this->blocar($this->contatos($linhas, ['email_prefeitura']));

        $this->assertCount(1, $blocos, 'Os quatro contatos sujos nao podem virar um segundo bloco.');
        $this->assertSame(50, $blocos[0]['total']);
        $this->assertSame(49, substr_count($blocos[0]['texto'], '; '), 'Cinquenta e-mails tem 49 separadores.');
    }

    public function test_higienizar_descarta_o_sentinela_hifen_do_legado(): void
    {
        $this->assertNull($this->higienizar('-'), 'O sentinela "-" do legado tem de virar null.');
        $this->assertSame(
            '(31) 3333-2222',
            $this->higienizar('(31) 3333-2222'),
            'Hifen DENTRO de um telefone formatado nao e o sentinela.',
        );
    }

    public function test_tamanho_de_bloco_menor_que_um_lanca_excecao_para_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servico()->blocosDeEmail(0);
    }

    public function test_tamanho_de_bloco_negativo_lanca_excecao_para_telefone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servico()->blocosDeTelefone(-1);
    }

    public function test_email_sai_em_minusculas_e_sem_espacos(): void
    {
        $municipio = $this->prefeituraCom(['email_prefeitura' => '  PREFEITURA@Exemplo.MG.GOV.BR ']);

        $linha = $this->servico()->emails()->firstWhere('municipio_id', $municipio->id);
        $this->assertSame('prefeitura@exemplo.mg.gov.br', $linha['email_prefeitura']);

        // Tamanho bem alto forca um unico bloco: isola o teste de quantos
        // e-mails reais ja existem na base. O objetivo aqui e provar que a
        // sanitizacao chega ao texto do bloco, nao contar quantos blocos existem.
        $blocos = $this->servico()->blocosDeEmail(1_000_000);
        $this->assertStringContainsString('prefeitura@exemplo.mg.gov.br', $blocos[0]['texto']);
    }

    public function test_bloco_de_telefone_reune_os_cinco_campos_na_ordem_do_contrato(): void
    {
        $this->prefeituraCom([
            'tel_prefeitura' => '(31) 3333-1111',
            'tel_prefeitura_2' => '(31) 3333-2222',
            'prefeito_telefone' => '(31) 3333-3333',
            'prefeito_celular' => '(31) 98888-4444',
            'fax_prefeitura' => '(31) 3333-5555',
        ]);

        // Tamanho bem alto forca um unico bloco: os cinco campos desta mesma
        // prefeitura ficam sempre consecutivos no array achatado (contatos()
        // percorre as linhas em ordem, e dentro de cada linha percorre os
        // campos na ordem do contrato) — a substring abaixo aparece intacta
        // independente de quantos outros telefones ja existem na base.
        $blocos = $this->servico()->blocosDeTelefone(1_000_000);

        $this->assertStringContainsString(
            '(31) 3333-1111; (31) 3333-2222; (31) 3333-3333; (31) 98888-4444; (31) 3333-5555',
            $blocos[0]['texto'],
        );
    }

    public function test_municipio_sem_prefeitura_aparece_na_listagem_de_emails(): void
    {
        $municipio = Municipio::factory()->create();

        $linha = $this->servico()->emails()->firstWhere('municipio_id', $municipio->id);

        $this->assertNotNull($linha, 'O LEFT JOIN tem de preservar o municipio sem linha de prefeitura.');
        $this->assertNull($linha['email_prefeitura']);
    }

    public function test_csv_de_emails_tem_cabecalho_e_uma_linha_por_municipio(): void
    {
        $municipiosAntes = DB::table('municipios')->count();
        $this->criarComEmail(3);

        $csv = $this->servico()->csvEmails();

        $this->assertSame(['Municipio', 'E-mail institucional', 'E-mail 2', 'E-mail 3'], $csv[0]);
        $this->assertCount($municipiosAntes + 3 + 1, $csv);
    }

    public function test_csv_de_telefones_tem_cabecalho_e_uma_linha_por_municipio(): void
    {
        $municipiosAntes = DB::table('municipios')->count();
        $this->criarComEmail(2);

        $csv = $this->servico()->csvTelefones();

        $this->assertSame(
            ['Municipio', 'Telefone', 'Telefone 2', 'Fax', 'Telefone do prefeito', 'Celular do prefeito'],
            $csv[0],
        );
        $this->assertCount($municipiosAntes + 2 + 1, $csv);
    }
}
```

- [ ] **Step 3: Rodar o teste para ver falhar**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorioServiceTest
```

Esperado: FAIL — `Class "App\Modules\Cedec\Services\ContatoRelatorioService" does not exist`.

- [ ] **Step 4: Escrever o service**

Criar `app/Modules/Cedec/Services/ContatoRelatorioService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Services;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Relatorios de contato da CEDEC estadual.
 *
 * Substitui rel_email.php, rel_email_ca.php e o botao "Telefones" que no legado
 * era href="#" e nunca foi implementado.
 *
 * O bloco de 50 existe porque 50 e o limite de destinatarios por envio do
 * Outlook da Cidade Administrativa. O legado ja blocava, mas contava
 * ($key + 1) % 50 sobre todas as linhas, inclusive as de e-mail vazio: a parte
 * prometia 50 destinatarios e entregava menos. Aqui o vazio e descartado ANTES
 * de blocar.
 */
final class ContatoRelatorioService
{
    public const TAMANHO_BLOCO_PADRAO = 50;

    /** Colunas de e-mail, na ordem da tabela e do CSV. */
    private const CAMPOS_EMAIL = [
        'email_prefeitura',
        'email_prefeitura_2',
        'email_prefeitura_3',
    ];

    /** Colunas de telefone, na ordem da tabela e do CSV (contrato, secao 4). */
    private const CAMPOS_TELEFONE = [
        'tel_prefeitura',
        'tel_prefeitura_2',
        'fax_prefeitura',
        'prefeito_telefone',
        'prefeito_celular',
    ];

    /** Ordem de concatenacao dentro do bloco de telefone (contrato, secao 4). */
    private const ORDEM_BLOCO_TELEFONE = [
        'tel_prefeitura',
        'tel_prefeitura_2',
        'prefeito_telefone',
        'prefeito_celular',
        'fax_prefeitura',
    ];

    private const SEPARADOR = '; ';

    /**
     * @return Collection<int, array{municipio_id: int, municipio_nome: string,
     *   email_prefeitura: ?string, email_prefeitura_2: ?string, email_prefeitura_3: ?string}>
     */
    public function emails(): Collection
    {
        return $this->linhas(self::CAMPOS_EMAIL, higienizarComoEmail: true);
    }

    /**
     * @return Collection<int, array{municipio_id: int, municipio_nome: string,
     *   tel_prefeitura: ?string, tel_prefeitura_2: ?string, fax_prefeitura: ?string,
     *   prefeito_telefone: ?string, prefeito_celular: ?string}>
     */
    public function telefones(): Collection
    {
        return $this->linhas(self::CAMPOS_TELEFONE, higienizarComoEmail: false);
    }

    /** @return array<int, array{indice: int, total: int, texto: string}> */
    public function blocosDeEmail(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        self::garantirTamanho($tamanho);

        return self::blocar($this->contatos($this->emails(), self::CAMPOS_EMAIL), $tamanho);
    }

    /** @return array<int, array{indice: int, total: int, texto: string}> */
    public function blocosDeTelefone(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        self::garantirTamanho($tamanho);

        return self::blocar($this->contatos($this->telefones(), self::ORDEM_BLOCO_TELEFONE), $tamanho);
    }

    /**
     * Primeira linha e o cabecalho.
     *
     * @return array<int, array<int, string>>
     */
    public function csvEmails(): array
    {
        $linhas = [['Municipio', 'E-mail institucional', 'E-mail 2', 'E-mail 3']];

        foreach ($this->emails() as $linha) {
            $linhas[] = self::linhaDeCsv($linha, self::CAMPOS_EMAIL);
        }

        return $linhas;
    }

    /** @return array<int, array<int, string>> */
    public function csvTelefones(): array
    {
        $linhas = [['Municipio', 'Telefone', 'Telefone 2', 'Fax', 'Telefone do prefeito', 'Celular do prefeito']];

        foreach ($this->telefones() as $linha) {
            $linhas[] = self::linhaDeCsv($linha, self::CAMPOS_TELEFONE);
        }

        return $linhas;
    }

    /**
     * Municipios com LEFT JOIN em compdec_prefeituras: municipio sem prefeitura
     * tambem aparece, com os contatos em null.
     *
     * A condicao de soft delete vai DENTRO do join. Num where() ela viraria um
     * filtro sobre a tabela da direita e transformaria o LEFT JOIN em INNER,
     * sumindo com todo municipio que ainda nao tem linha de prefeitura.
     *
     * @param  array<int, string>  $campos
     * @return Collection<int, array<string, mixed>>
     */
    private function linhas(array $campos, bool $higienizarComoEmail): Collection
    {
        $selects = array_map(
            static fn (string $campo): string => 'compdec_prefeituras.'.$campo,
            $campos,
        );

        return DB::table('municipios')
            ->leftJoin('compdec_prefeituras', function (JoinClause $join): void {
                $join->on('compdec_prefeituras.municipio_id', '=', 'municipios.id')
                    ->whereNull('compdec_prefeituras.deleted_at');
            })
            ->select(array_merge(
                ['municipios.id as municipio_id', 'municipios.nome as municipio_nome'],
                $selects,
            ))
            ->orderBy('municipios.nome')
            ->get()
            ->map(function (object $registro) use ($campos, $higienizarComoEmail): array {
                $linha = [
                    'municipio_id' => (int) $registro->municipio_id,
                    'municipio_nome' => (string) $registro->municipio_nome,
                ];

                foreach ($campos as $campo) {
                    $valor = $registro->{$campo} === null ? null : (string) $registro->{$campo};
                    $linha[$campo] = $higienizarComoEmail
                        ? self::higienizarEmail($valor)
                        : self::higienizar($valor);
                }

                return $linha;
            });
    }

    /**
     * Achata as linhas nos contatos nao vazios, na ordem dos campos pedida.
     *
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @param  array<int, string>  $campos
     * @return array<int, string>
     */
    private function contatos(Collection $linhas, array $campos): array
    {
        $contatos = [];

        foreach ($linhas as $linha) {
            foreach ($campos as $campo) {
                if ($linha[$campo] !== null) {
                    $contatos[] = $linha[$campo];
                }
            }
        }

        return $contatos;
    }

    /**
     * @param  array<int, string>  $contatos
     * @return array<int, array{indice: int, total: int, texto: string}>
     */
    private static function blocar(array $contatos, int $tamanho): array
    {
        $blocos = [];

        foreach (array_chunk($contatos, $tamanho) as $posicao => $parte) {
            $blocos[] = [
                'indice' => $posicao + 1,
                'total' => count($parte),
                'texto' => implode(self::SEPARADOR, $parte),
            ];
        }

        return $blocos;
    }

    /**
     * @param  array<string, mixed>  $linha
     * @param  array<int, string>  $campos
     * @return array<int, string>
     */
    private static function linhaDeCsv(array $linha, array $campos): array
    {
        $valores = [(string) $linha['municipio_nome']];

        foreach ($campos as $campo) {
            $valores[] = $linha[$campo] ?? '';
        }

        return $valores;
    }

    private static function garantirTamanho(int $tamanho): void
    {
        if ($tamanho < 1) {
            throw new InvalidArgumentException('O tamanho do bloco de contatos deve ser maior ou igual a 1.');
        }
    }

    /** O "-" e o sentinela de "sem telefone" que o legado gravava em cedec_prefeitura.tel2. */
    private static function higienizar(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($valor);

        return ($texto === '' || $texto === '-') ? null : $texto;
    }

    private static function higienizarEmail(?string $valor): ?string
    {
        $texto = self::higienizar($valor);

        return $texto === null ? null : mb_strtolower($texto);
    }
}
```

- [ ] **Step 5: Rodar o teste para ver passar**

```bash
docker exec newsdc_dev_app php -l /var/www/app/Modules/Cedec/Services/ContatoRelatorioService.php
docker exec newsdc_dev_app php artisan octane:reload
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorioServiceTest
```

Esperado: PASS, 12 testes (os 4 casos do data provider contam separado).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Cedec/Services/ContatoRelatorioService.php
git commit -m "✨ feat(cedec): service de relatorio de contatos com blocos de 50"
```

---

### Task 2: `ContatoRelatorioController`, rotas e export CSV

**Files:**
- Create: `app/Modules/Cedec/Controllers/ContatoRelatorioController.php`
- Modify: `routes/modules/cedec.php` (as duas rotas de contatos, se a fase 2 nao as registrou)
- Test: `tests/Feature/Cedec/ContatoRelatorioHttpTest.php`

**Interfaces:**
- Consumes: `ContatoRelatorioService` da Task 1 (`emails()`, `telefones()`, `blocosDeEmail()`, `blocosDeTelefone()`, `csvEmails()`, `csvTelefones()`, `TAMANHO_BLOCO_PADRAO`); slugs `cedec.contatos.view` e `cedec.prefeituras.export` da fase 2.
- Produces: rota `cedec.contatos.index` (GET `/cedec/contatos`, `can:cedec.contatos.view`) e `cedec.contatos.export` (GET `/cedec/contatos/export`, `can:cedec.prefeituras.export`); pagina Inertia `Cedec/Contatos/Index` com as props da secao 8 do contrato: `aba`, `emails`, `telefones`, `blocos`, `tamanho_bloco`, `totais`.

**Regras que o controller fixa:**

- `aba` aceita exatamente `'emails'` ou `'telefones'`; qualquer outro valor cai em `'emails'`.
- `tamanho` (query string, opcional) fora da faixa 1..200 cai no padrao 50. A `InvalidArgumentException` do service e guarda de programador, nunca alcancavel por HTTP.
- `totais.emails_preenchidos` e `totais.telefones_preenchidos` sao a soma dos `total` dos blocos — o mesmo numero que alimenta os blocos, entao a invariante `array_sum(total) === preenchidos` vale sempre.
- `totais.municipios` e a quantidade de linhas da listagem (todos os municipios, com ou sem prefeitura).
- O `export` ignora `type`, `data_inicio`, `data_fim` e `all` que o `ExportCsvModal` envia: relatorio de contato nao tem recorte por data. So `aba` importa.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/ContatoRelatorioHttpTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContatoRelatorioHttpTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'cedec.contatos.view',
        'cedec.prefeituras.export',
    ];

    /** Ver a nota do ContatoRelatorioServiceTest: o banco de teste e o de desenvolvimento. */
    private const CAMPOS_DE_CONTATO = [
        'email_prefeitura' => null,
        'email_prefeitura_2' => null,
        'email_prefeitura_3' => null,
        'tel_prefeitura' => null,
        'tel_prefeitura_2' => null,
        'fax_prefeitura' => null,
        'prefeito_telefone' => null,
        'prefeito_celular' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // NAO zerar contato em massa aqui. O banco de teste e o de
        // desenvolvimento (phpunit.xml tem as linhas de sqlite comentadas), e
        // um UPDATE sem where sobre compdec_prefeituras apaga em definitivo o
        // que o ETL carregou se o processo morrer antes do rollback. Cada
        // teste cria a propria fixture em prefeituraCom() e assere sobre ELA.
    }

    /** @param array<int, string> $permissoes */
    private function usuario(array $permissoes): User
    {
        $user = User::factory()->create();

        if ($permissoes !== []) {
            $user->givePermissionTo($permissoes);
        }

        return $user;
    }

    /** @param array<string, string|null> $contatos */
    private function prefeituraCom(array $contatos): Municipio
    {
        $municipio = Municipio::factory()->create();

        Prefeitura::factory()->create(array_merge(
            self::CAMPOS_DE_CONTATO,
            $contatos,
            ['municipio_id' => $municipio->id],
        ));

        return $municipio;
    }

    public function test_index_sem_a_permissao_de_contatos_devolve_403(): void
    {
        $this->actingAs($this->usuario([]))
            ->get(route('cedec.contatos.index'))
            ->assertForbidden();
    }

    public function test_export_sem_a_permissao_de_export_devolve_403(): void
    {
        $this->actingAs($this->usuario(['cedec.contatos.view']))
            ->get(route('cedec.contatos.export', ['aba' => 'emails']))
            ->assertForbidden();
    }

    public function test_index_renderiza_a_pagina_com_as_props_do_contrato(): void
    {
        $this->prefeituraCom(['email_prefeitura' => 'PREFEITURA@Exemplo.MG.GOV.BR']);

        $resposta = $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.contatos.index'));

        $resposta->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // O segundo argumento false desliga o ensure_pages_exist de
                // config/inertia.php, que confere o arquivo .vue NO DISCO. A
                // pagina Cedec/Contatos/Index so nasce na Task 5; sem o false
                // este teste falha por pagina inexistente em vez de validar props.
                ->component('Cedec/Contatos/Index', false)
                ->where('aba', 'emails')
                ->where('tamanho_bloco', 50)
                ->has('emails')
                ->has('telefones')
                ->has('blocos')
                ->has('totais.emails_preenchidos')
                ->has('totais.telefones_preenchidos')
                ->has('totais.municipios'));

        // Conteudo se assere por PERTENCIMENTO, nunca por indice fixo nem
        // total exato: o banco de teste e o de desenvolvimento e pode ter
        // prefeituras reais alem da fixture. O e-mail entra em algum bloco,
        // ja em minusculas.
        $blocos = $resposta->viewData('page')['props']['blocos'];

        $this->assertStringContainsString(
            'prefeitura@exemplo.mg.gov.br',
            implode(' ', array_column($blocos, 'texto')),
        );
    }

    public function test_aba_de_telefones_devolve_os_blocos_de_telefone(): void
    {
        $this->prefeituraCom([
            'email_prefeitura' => 'nao-entra@exemplo.mg.gov.br',
            'tel_prefeitura' => '(31) 3333-1111',
        ]);

        $resposta = $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.contatos.index', ['aba' => 'telefones']));

        $resposta->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // false pelo mesmo motivo do teste anterior: a pagina Vue so
                // existe a partir da Task 5.
                ->component('Cedec/Contatos/Index', false)
                ->where('aba', 'telefones')
                ->has('blocos'));

        $textoDosBlocos = implode(' ', array_column(
            $resposta->viewData('page')['props']['blocos'],
            'texto',
        ));

        $this->assertStringContainsString('(31) 3333-1111', $textoDosBlocos);

        // O e-mail da mesma fixture NAO pode vazar para a aba de telefones.
        $this->assertStringNotContainsString('nao-entra@exemplo.mg.gov.br', $textoDosBlocos);
    }

    public function test_aba_desconhecida_cai_em_emails(): void
    {
        $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.contatos.index', ['aba' => 'inventada']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('aba', 'emails'));
    }

    public function test_export_de_emails_devolve_csv_com_cabecalho_e_uma_linha_por_municipio(): void
    {
        $municipiosAntes = DB::table('municipios')->count();
        $this->prefeituraCom(['email_prefeitura' => 'prefeitura@exemplo.mg.gov.br']);

        $resposta = $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.contatos.export', ['aba' => 'emails']));

        $resposta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $resposta->headers->get('Content-Type'));
        $this->assertStringContainsString('cedec_contatos_emails_', (string) $resposta->headers->get('Content-Disposition'));

        $linhas = array_values(array_filter(explode("\n", trim($resposta->streamedContent()))));

        $this->assertStringContainsString('Municipio', $linhas[0]);
        $this->assertCount($municipiosAntes + 1 + 1, $linhas);
    }

    public function test_export_de_telefones_usa_o_cabecalho_de_telefone(): void
    {
        $resposta = $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.contatos.export', ['aba' => 'telefones']));

        $resposta->assertOk();

        $linhas = array_values(array_filter(explode("\n", trim($resposta->streamedContent()))));

        $this->assertStringContainsString('Celular do prefeito', $linhas[0]);
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorioHttpTest
```

Esperado: FAIL — `Route [cedec.contatos.index] not defined.`

- [ ] **Step 3: Escrever o controller**

Criar `app/Modules/Cedec/Controllers/ContatoRelatorioController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cedec\Services\ContatoRelatorioService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatorios de contato da CEDEC (substitui relbusca.php, rel_email.php e
 * rel_email_ca.php do legado gestaocedec).
 *
 * Duas abas: e-mails e telefones. A de telefones era href="#" no legado.
 */
final class ContatoRelatorioController extends Controller
{
    private const ABAS = ['emails', 'telefones'];

    private const TAMANHO_MAXIMO = 200;

    public function __construct(private readonly ContatoRelatorioService $service) {}

    public function index(Request $request): Response
    {
        $aba = $this->aba($request);
        $tamanho = $this->tamanhoDeBloco($request);

        $emails = $this->service->emails();
        $telefones = $this->service->telefones();
        $blocosDeEmail = $this->service->blocosDeEmail($tamanho);
        $blocosDeTelefone = $this->service->blocosDeTelefone($tamanho);

        return Inertia::render('Cedec/Contatos/Index', [
            'aba' => $aba,
            'emails' => $emails->values()->all(),
            'telefones' => $telefones->values()->all(),
            'blocos' => $aba === 'telefones' ? $blocosDeTelefone : $blocosDeEmail,
            'tamanho_bloco' => $tamanho,
            'totais' => [
                // Somar o total dos blocos, e nao recontar, e o que garante que o
                // numero mostrado no card seja o mesmo que foi para os blocos.
                'emails_preenchidos' => array_sum(array_column($blocosDeEmail, 'total')),
                'telefones_preenchidos' => array_sum(array_column($blocosDeTelefone, 'total')),
                'municipios' => $emails->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $aba = $this->aba($request);

        $dados = $aba === 'telefones'
            ? $this->service->csvTelefones()
            : $this->service->csvEmails();

        $arquivo = 'cedec_contatos_'.$aba.'_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($dados): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8, para o Excel

            foreach ($dados as $linha) {
                fputcsv($handle, $linha, ';');
            }

            fclose($handle);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function aba(Request $request): string
    {
        $aba = (string) $request->query('aba', 'emails');

        return in_array($aba, self::ABAS, true) ? $aba : 'emails';
    }

    /**
     * Fora da faixa util cai no padrao. A InvalidArgumentException do service e
     * guarda de programador: por HTTP ela nunca e alcancavel.
     */
    private function tamanhoDeBloco(Request $request): int
    {
        $tamanho = (int) $request->query('tamanho', (string) ContatoRelatorioService::TAMANHO_BLOCO_PADRAO);

        if ($tamanho < 1 || $tamanho > self::TAMANHO_MAXIMO) {
            return ContatoRelatorioService::TAMANHO_BLOCO_PADRAO;
        }

        return $tamanho;
    }
}
```

- [ ] **Step 4: Conferir se as rotas de contatos ja existem**

```bash
docker exec newsdc_dev_app php artisan route:list --name=cedec.contatos
```

Se as duas rotas aparecerem (a fase 2 as registrou), **pule o Step 5**. Se nao aparecer
nenhuma, siga para o Step 5.

- [ ] **Step 5: Registrar as rotas de contatos**

Em `routes/modules/cedec.php`, DENTRO do grupo `Route::prefix('cedec')->name('cedec.')`
criado na fase 2, acrescentar ao final do grupo:

```php
    // Relatorios de contato. `/export` vem antes de qualquer rota com parametro
    // no mesmo prefixo para nao ser capturado por ela.
    Route::prefix('contatos')->name('contatos.')->group(function () {
        Route::get('/', [ContatoRelatorioController::class, 'index'])
            ->name('index')->middleware('can:cedec.contatos.view');
        Route::get('/export', [ContatoRelatorioController::class, 'export'])
            ->name('export')->middleware('can:cedec.prefeituras.export');
    });
```

E no topo do arquivo, junto dos outros `use`:

```php
use App\Modules\Cedec\Controllers\ContatoRelatorioController;
```

Nao registrar `Route::model()` nenhum: `Route::model()` e GLOBAL neste projeto e ja causou
404 no Pmda e no PlanCon.

- [ ] **Step 6: Rodar o teste para ver passar**

```bash
docker exec newsdc_dev_app php -l /var/www/app/Modules/Cedec/Controllers/ContatoRelatorioController.php
docker exec newsdc_dev_app php artisan octane:reload
docker exec newsdc_dev_app php artisan route:list --name=cedec.contatos
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorioHttpTest
```

Esperado: as duas rotas listadas e 7 testes PASS. O teste do Inertia falha com
"Unable to find component" so quando a pagina Vue nao existe — nao e o caso: o Inertia de
teste nao resolve o componente, so compara o nome.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Cedec/Controllers/ContatoRelatorioController.php routes/modules/cedec.php
git commit -m "✨ feat(cedec): rotas e controller dos relatorios de contato"
```

---

### Task 3: `ContatoBloco` (molecula) e `ContatoBlocosOutlook` (organismo)

**Files:**
- Create: `resources/js/Components/Molecules/Cedec/ContatoBloco.vue`
- Create: `resources/js/Components/Organisms/Cedec/ContatoBlocosOutlook.vue`

**Interfaces:**
- Consumes: `Composables/useCopiarTexto.js` (`{ copiar, copiado }`, `copiar` e async e devolve boolean); `Composables/useToast.js` (`{ show }`, assinatura `show(mensagem, tipo)`); `Components/Icons/ClipboardIcon.vue`; `Components/Icons/CheckIcon.vue`; `Components/Molecules/ListEmptyState.vue` (props `title`, `helper`).
- Produces: `ContatoBloco` com props `{ indice: Number, total: Number, texto: String, copiado: Boolean }` e emit `copiar`; `ContatoBlocosOutlook` com props `{ blocos: Array, tamanhoBloco: Number }`.

- [ ] **Step 1: Criar as pastas**

```bash
mkdir -p resources/js/Components/Molecules/Cedec resources/js/Components/Organisms/Cedec
```

- [ ] **Step 2: Escrever a molecula `ContatoBloco.vue`**

```vue
<script setup>
/**
 * Um bloco de contatos concatenados, pronto para colar no campo de
 * destinatarios do Outlook.
 *
 * MOLECULA: nao chama API, nao usa estado global e nao conhece a area de
 * transferencia. O clique so emite `copiar`; quem copia e o organismo
 * ContatoBlocosOutlook, que e onde `useCopiarTexto` e invocado uma unica vez.
 *
 * A prop `copiado` e o feedback devolvido pelo organismo para ESTE bloco (o
 * organismo guarda qual indice foi copiado por ultimo).
 */
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';

defineProps({
  indice: { type: Number, required: true },
  total: { type: Number, required: true },
  texto: { type: String, required: true },
  copiado: { type: Boolean, default: false },
});

defineEmits(['copiar']);
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
    <!-- min-w-0 no pai e shrink-0 no botao: as duas metades da regra 2 de responsividade -->
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
      <div class="min-w-0">
        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
          Parte {{ indice }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ total }} contatos neste bloco
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border px-3 text-sm font-semibold transition-colors"
        :class="copiado
          ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300'
          : 'border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800'"
        :title="`Copiar os ${total} contatos da parte ${indice}`"
        @click="$emit('copiar')"
      >
        <component :is="copiado ? CheckIcon : ClipboardIcon" class="h-4 w-4 shrink-0" />
        <span>{{ copiado ? 'Copiado' : 'Copiar' }}</span>
      </button>
    </div>

    <!--
      O texto de 50 e-mails passa de 1500 caracteres sem quebra natural.
      break-words quebra nos separadores; [overflow-wrap:anywhere] cobre o caso
      de UM e-mail mais largo que a viewport de 375px, que nao tem onde quebrar.
      A altura e limitada e a rolagem e VERTICAL, dentro do bloco: rolagem
      horizontal esconderia metade dos destinatarios atras de um gesto que o
      usuario nao tem motivo para tentar.
    -->
    <p
      class="mt-3 max-h-48 overflow-y-auto rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-700 break-words [overflow-wrap:anywhere] dark:bg-slate-950/60 dark:text-slate-300"
    >
      {{ texto }}
    </p>
  </div>
</template>
```

- [ ] **Step 3: Escrever o organismo `ContatoBlocosOutlook.vue`**

```vue
<script setup>
/**
 * Lista dos blocos de contato prontos para o Outlook da Cidade Administrativa.
 *
 * ORGANISMO: e aqui que a interacao mora. `useCopiarTexto` e invocado UMA vez,
 * neste componente — se cada ContatoBloco invocasse o proprio, o feedback de
 * "copiado" de cada instancia seria independente mas nenhuma saberia qual bloco
 * o usuario copiou, e a molecula passaria a carregar estado, quebrando a camada.
 *
 * O `copiado` devolvido pelo composable nao e usado: quem manda no feedback e
 * `indiceCopiado`, que sabe de QUAL bloco se trata.
 */
import { computed, ref } from 'vue';
import ContatoBloco from '@/Components/Molecules/Cedec/ContatoBloco.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useCopiarTexto } from '@/Composables/useCopiarTexto';
import { useToast } from '@/Composables/useToast.js';

const props = defineProps({
  blocos: { type: Array, default: () => [] },
  tamanhoBloco: { type: Number, default: 50 },
});

const { copiar } = useCopiarTexto();
const { show } = useToast();

const indiceCopiado = ref(null);

const totalDeContatos = computed(
  () => props.blocos.reduce((soma, bloco) => soma + bloco.total, 0),
);

const copiarBloco = async (bloco) => {
  const ok = await copiar(bloco.texto);

  if (!ok) {
    show('Nao foi possivel copiar o bloco. Selecione o texto e copie manualmente.', 'error');
    return;
  }

  indiceCopiado.value = bloco.indice;
  show(`Parte ${bloco.indice} copiada: ${bloco.total} contatos.`, 'success');

  setTimeout(() => {
    if (indiceCopiado.value === bloco.indice) {
      indiceCopiado.value = null;
    }
  }, 2500);
};
</script>

<template>
  <div class="space-y-4">
    <p class="text-xs text-slate-500 dark:text-slate-400">
      O limite de destinatarios por envio do Outlook da Cidade Administrativa e de
      {{ tamanhoBloco }}. Os {{ totalDeContatos }} contatos abaixo ja estao divididos:
      copie uma parte por envio.
    </p>

    <ListEmptyState
      v-if="blocos.length === 0"
      title="Nenhum contato preenchido"
      helper="Nenhuma prefeitura tem contato cadastrado para esta aba."
    />

    <ContatoBloco
      v-for="bloco in blocos"
      v-else
      :key="bloco.indice"
      :indice="bloco.indice"
      :total="bloco.total"
      :texto="bloco.texto"
      :copiado="indiceCopiado === bloco.indice"
      @copiar="copiarBloco(bloco)"
    />
  </div>
</template>
```

- [ ] **Step 4: Compilar**

```bash
npm run build
```

Esperado: build sem erro. Os dois componentes ainda nao sao importados por pagina nenhuma,
entao o Vite so valida a sintaxe se algum arquivo os referenciar — a validacao de verdade
vem na Task 5. Se o build passar, siga.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/Molecules/Cedec/ContatoBloco.vue resources/js/Components/Organisms/Cedec/ContatoBlocosOutlook.vue
git commit -m "✨ feat(cedec): blocos de contato com botao copiar"
```

---

### Task 4: `ContatoTabela` (organismo responsivo)

**Files:**
- Create: `resources/js/Components/Organisms/Cedec/ContatoTabela.vue`

**Interfaces:**
- Consumes: `Composables/useMobile.js` (`useMobile()` devolve `{ isMobile, isTablet, isDesktop, screenWidth }`; `isDesktop` e `matchMedia('(min-width: 1024px)')`); `Components/Molecules/ListEmptyState.vue`.
- Produces: `ContatoTabela` com props `{ colunas: Array<{key, label}>, linhas: Array<Object>, campoTitulo: String, vazioTitulo: String, vazioAjuda: String }`.

**Por que `isDesktop` e nao `isMobile`:** a regra 5 da skill de responsividade manda alinhar
toda decisao de layout em `lg` (1024px). Em `useMobile`, `isMobile` corta em `md` (767px) e
so `isDesktop` corta em `lg`. Entao a alternancia e `v-if="isDesktop"` / `v-else`, nunca
`v-if="!isMobile"` — senao a tabela de 6 colunas continua rolando de lado na faixa de 768 a
1023px, que e justamente onde mais defeito se esconde.

- [ ] **Step 1: Escrever o organismo**

```vue
<script setup>
/**
 * Tabela de contatos: tabela no desktop, cartoes empilhados abaixo de `lg`.
 *
 * Generico por colunas para servir as duas abas (e-mails e telefones) sem
 * duplicar markup.
 *
 * O corte e por `isDesktop` (lg, 1024px) e nao por `isMobile` (md, 767px):
 * a regra 5 da skill de responsividade manda alinhar toda decisao de layout em
 * `lg`. Com o corte em `md`, a tabela de seis colunas seguiria rolando de lado
 * entre 768 e 1023px.
 */
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useMobile } from '@/Composables/useMobile';

defineProps({
  /** [{ key: 'email_prefeitura', label: 'E-mail institucional' }] */
  colunas: { type: Array, required: true },
  linhas: { type: Array, default: () => [] },
  /** Chave usada como titulo do cartao no mobile e como primeira coluna. */
  campoTitulo: { type: String, default: 'municipio_nome' },
  vazioTitulo: { type: String, default: 'Nenhum contato encontrado' },
  vazioAjuda: { type: String, default: 'Nenhuma prefeitura tem contato cadastrado.' },
});

const { isDesktop } = useMobile();
</script>

<template>
  <ListEmptyState
    v-if="linhas.length === 0"
    :title="vazioTitulo"
    :helper="vazioAjuda"
  />

  <!-- Desktop: tabela. min-w-0 + overflow-x-auto contem o transbordo NELA, nao na pagina. -->
  <div v-else-if="isDesktop" class="min-w-0 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/50">
    <table class="w-full text-left text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800/60">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">Municipio</th>
          <th
            v-for="coluna in colunas"
            :key="coluna.key"
            class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200"
          >
            {{ coluna.label }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr
          v-for="linha in linhas"
          :key="linha.municipio_id"
          class="bg-white dark:bg-slate-900/40"
        >
          <td class="px-4 py-2 font-medium text-slate-900 dark:text-slate-100">
            {{ linha[campoTitulo] }}
          </td>
          <td
            v-for="coluna in colunas"
            :key="coluna.key"
            class="px-4 py-2 text-slate-600 break-words [overflow-wrap:anywhere] dark:text-slate-300"
          >
            {{ linha[coluna.key] || '-' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Abaixo de lg: um cartao por municipio, pares rotulo/valor -->
  <div v-else class="space-y-3">
    <div
      v-for="linha in linhas"
      :key="linha.municipio_id"
      class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/40"
    >
      <p class="text-sm font-bold text-slate-900 break-words dark:text-slate-100">
        {{ linha[campoTitulo] }}
      </p>
      <dl class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
        <div v-for="coluna in colunas" :key="coluna.key" class="min-w-0">
          <dt class="text-xs text-slate-500 dark:text-slate-400">{{ coluna.label }}</dt>
          <dd class="text-sm text-slate-700 break-words [overflow-wrap:anywhere] dark:text-slate-200">
            {{ linha[coluna.key] || '-' }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Compilar**

```bash
npm run build
```

Esperado: build sem erro.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/Organisms/Cedec/ContatoTabela.vue
git commit -m "✨ feat(cedec): tabela de contatos responsiva com bloco no mobile"
```

---

### Task 5: `Pages/Cedec/Contatos/Index.vue` — abas, orquestracao e export CSV

**Files:**
- Create: `resources/js/Pages/Cedec/Contatos/Index.vue`

**Interfaces:**
- Consumes: props Inertia da Task 2 (`aba`, `emails`, `telefones`, `blocos`, `tamanho_bloco`, `totais`); `ContatoBlocosOutlook` (Task 3); `ContatoTabela` (Task 4); rotas `cedec.contatos.index` e `cedec.contatos.export` (Task 2).
- Componentes existentes reusados, sem substituto: `Layouts/AuthenticatedLayout.vue`; `Components/Organisms/PageHeader.vue` (`variant="gradient"`, `:icon-image`, slot `#actions`); `Components/Molecules/Statistics/StatCardsGrid.vue` (prop `colunas`) + `StatCard.vue` (props `title`, `value`, `icon`, `variant`); `Components/Molecules/Navigation/ModuleTabs.vue` (props `tabs`, `activeTab`; evento `tab-change`; cada tab e `{ id, label, icon }`); `Components/Molecules/CollapsibleSection.vue` (props obrigatorias `namespace`, `sectionId`, `title`); `Components/Atoms/Button/ActionButton.vue` (`module`/`resource`/`action` montam o slug `cedec.prefeituras.export` e o RBAC decide a visibilidade); `Components/Organisms/ExportCsvModal.vue`; `Composables/data/useExport.js` (`useExport(nomeDaRota)` devolve `{ showExportModal, openExportModal, closeExportModal, handleExport }`); `Support/moduleIcons.js` (`moduleIcon`).
- Produces: pagina Inertia `Cedec/Contatos/Index`.

**Decisoes de layout desta pagina:**

- Forma A da regra 2 de layout: raiz `w-full pb-8`, sem `p-*`; cada bloco carrega a propria
  `mb-6` (`PageHeader` e `StatCardsGrid` ja trazem por padrao, `ModuleTabs` traz no trilho).
  As duas `CollapsibleSection` ficam num `space-y-6` que e o ultimo filho — nao ha soma.
- Trocar de aba faz uma visita ao servidor: os blocos sao calculados no backend, e recalcular
  no cliente duplicaria a regra de blocagem. `preserveScroll: true` para nao jogar o usuario
  no topo; `replace: true` para nao encher o historico do navegador.
- `moduleIcon('prefeituras')` devolve `null` enquanto a fase 2 nao registrar o mapeamento;
  `PageHeader` aceita `null` e cai no `:icon`. A pagina passa os dois, nessa ordem.
- O `ExportCsvModal` e usado como esta, sem alteracao. Ele abre em "Periodo Especifico" e so
  habilita o botao com as duas datas — o escopo util aqui e "Toda Serie Historica". O
  controller ignora `type`, `data_inicio`, `data_fim` e `all`. Isso esta anotado em comentario
  no arquivo e listado como divergencia no fim deste plano.

- [ ] **Step 1: Criar a pasta**

```bash
mkdir -p resources/js/Pages/Cedec/Contatos
```

- [ ] **Step 2: Escrever a pagina**

```vue
<script setup>
/**
 * Relatorios de contato da CEDEC.
 *
 * Substitui relbusca.php (tres botoes), rel_email.php (tabela) e rel_email_ca.php
 * (blocos de 50) do legado gestaocedec. A aba de telefones e nova: no legado era
 * um href="#" que nunca foi implementado.
 *
 * PAGINA: orquestra. Nao copia texto (isso e do ContatoBlocosOutlook) e nao
 * decide layout de tabela (isso e do ContatoTabela).
 */
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { EnvelopeIcon, PhoneIcon, BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import ContatoBlocosOutlook from '@/Components/Organisms/Cedec/ContatoBlocosOutlook.vue';
import ContatoTabela from '@/Components/Organisms/Cedec/ContatoTabela.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/Composables/data/useExport';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  aba: { type: String, default: 'emails' },
  emails: { type: Array, default: () => [] },
  telefones: { type: Array, default: () => [] },
  blocos: { type: Array, default: () => [] },
  tamanho_bloco: { type: Number, default: 50 },
  totais: {
    type: Object,
    default: () => ({ emails_preenchidos: 0, telefones_preenchidos: 0, municipios: 0 }),
  },
});

defineOptions({ layout: AuthenticatedLayout });

const abaAtiva = ref(props.aba);

// A prop manda: depois da visita ao servidor a aba tem de refletir o que veio.
watch(() => props.aba, (valor) => { abaAtiva.value = valor; });

const abas = [
  { id: 'emails', label: 'E-mails', icon: EnvelopeIcon },
  { id: 'telefones', label: 'Telefones', icon: PhoneIcon },
];

const COLUNAS_EMAIL = [
  { key: 'email_prefeitura', label: 'E-mail institucional' },
  { key: 'email_prefeitura_2', label: 'E-mail 2' },
  { key: 'email_prefeitura_3', label: 'E-mail 3' },
];

const COLUNAS_TELEFONE = [
  { key: 'tel_prefeitura', label: 'Telefone' },
  { key: 'tel_prefeitura_2', label: 'Telefone 2' },
  { key: 'fax_prefeitura', label: 'Fax' },
  { key: 'prefeito_telefone', label: 'Telefone do prefeito' },
  { key: 'prefeito_celular', label: 'Celular do prefeito' },
];

const ehTelefones = computed(() => abaAtiva.value === 'telefones');
const colunas = computed(() => (ehTelefones.value ? COLUNAS_TELEFONE : COLUNAS_EMAIL));
const linhas = computed(() => (ehTelefones.value ? props.telefones : props.emails));

// Os blocos vem calculados do backend: recalcular no cliente duplicaria a regra
// de blocagem, que e justamente o que o service existe para centralizar.
const trocarAba = (id) => {
  if (id === abaAtiva.value) return;

  router.get(
    route('cedec.contatos.index'),
    { aba: id },
    { preserveScroll: true, replace: true },
  );
};

// O ExportCsvModal manda type/data_inicio/data_fim/all; o controller de contatos
// ignora tudo isso e le so `aba`. Relatorio de contato nao tem recorte por data.
const { showExportModal, openExportModal, closeExportModal, handleExport } =
  useExport('cedec.contatos.export');

const exportar = (params) => handleExport(params, { aba: abaAtiva.value });
</script>

<template>
  <Head title="Contatos das Prefeituras" />

  <div class="w-full pb-8">
    <PageHeader
      title="Contatos das Prefeituras"
      description="E-mails e telefones institucionais das 853 prefeituras, prontos para envio em blocos."
      :icon="BuildingOffice2Icon"
      :icon-image="moduleIcon('prefeituras')"
      variant="gradient"
    >
      <template #actions>
        <ActionButton
          module="cedec"
          resource="prefeituras"
          action="export"
          label="Exportar CSV"
          @click="openExportModal"
        />
      </template>
    </PageHeader>

    <StatCardsGrid :colunas="3">
      <StatCard
        title="Municipios"
        :value="totais.municipios"
        :icon="BuildingOffice2Icon"
        variant="info"
      />
      <StatCard
        title="E-mails preenchidos"
        :value="totais.emails_preenchidos"
        :icon="EnvelopeIcon"
        variant="success"
      />
      <StatCard
        title="Telefones preenchidos"
        :value="totais.telefones_preenchidos"
        :icon="PhoneIcon"
        variant="warning"
      />
    </StatCardsGrid>

    <ModuleTabs :tabs="abas" :active-tab="abaAtiva" @tab-change="trocarAba">
      <div class="space-y-6">
        <CollapsibleSection
          namespace="cedec"
          :section-id="`blocos-${abaAtiva}`"
          title="Blocos para envio"
          subtitle="Cada parte cabe em um envio do Outlook da Cidade Administrativa"
          :icon="ehTelefones ? PhoneIcon : EnvelopeIcon"
          tom="success"
        >
          <ContatoBlocosOutlook :blocos="blocos" :tamanho-bloco="tamanho_bloco" />
        </CollapsibleSection>

        <CollapsibleSection
          namespace="cedec"
          :section-id="`tabela-${abaAtiva}`"
          title="Lista por municipio"
          :subtitle="`${linhas.length} municipios`"
          :icon="BuildingOffice2Icon"
          tom="info"
        >
          <ContatoTabela
            :colunas="colunas"
            :linhas="linhas"
            vazio-titulo="Nenhum municipio encontrado"
            vazio-ajuda="A carga de prefeituras ainda nao foi executada."
          />
        </CollapsibleSection>
      </div>
    </ModuleTabs>

    <ExportCsvModal
      :show="showExportModal"
      :module-name="ehTelefones ? 'Telefones das Prefeituras' : 'E-mails das Prefeituras'"
      @close="closeExportModal"
      @export="exportar"
    />
  </div>
</template>
```

- [ ] **Step 3: Acrescentar o import que falta**

O bloco acima usa `PageHeader` no template. Confirmar que a linha de import esta presente
junto dos demais imports do `<script setup>`:

```js
import PageHeader from '@/Components/Organisms/PageHeader.vue';
```

Se nao estiver, acrescentar logo depois do import de `AuthenticatedLayout`.

- [ ] **Step 4: Compilar**

```bash
npm run build
```

Esperado: build sem erro e sem aviso de componente nao resolvido.

- [ ] **Step 5: Rodar a suite inteira da fase**

```bash
docker exec newsdc_dev_app php artisan octane:reload
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorio
```

Esperado: PASS nas duas classes (19 testes).

- [ ] **Step 6: Medir o transbordo horizontal no navegador**

Abrir `/cedec/contatos` com um usuario que tenha `cedec.contatos.view`, em **375px** e depois
em **840px**, e rodar no console:

```js
(() => {
  const de = document.documentElement;
  const rolaveis = [...document.querySelectorAll('.overflow-x-auto, .overflow-y-auto')]
    .map((el) => ({ cls: el.className.slice(0, 40), rola: el.scrollWidth > el.clientWidth }));
  return { viewport: de.clientWidth, excesso: de.scrollWidth - de.clientWidth, rolaveis };
})();
```

Criterio de aprovacao, nas duas larguras:

- `excesso` igual a **0**;
- nenhum item de `rolaveis` com `rola: true` que seja o bloco de texto (o bloco quebra a
  linha, nao rola de lado);
- repetir na aba de Telefones, que tem cinco colunas.

Se `excesso` for maior que 0, o culpado quase sempre e a celula de e-mail sem
`[overflow-wrap:anywhere]` ou um pai flex sem `min-w-0`. Corrigir antes de commitar.

- [ ] **Step 7: Conferir a alternancia de tabela para bloco**

Ainda em 375px, confirmar que a "Lista por municipio" aparece como cartoes empilhados e
**nao** como tabela; em 1280px, como tabela. Em 840px (tablet) tem de ser cartao — o corte e
em `lg`.

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Cedec/Contatos/Index.vue
git commit -m "✨ feat(cedec): pagina de relatorios de contato com abas e export CSV"
```

---

## Verificacao final da fase

- [ ] `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ContatoRelatorio` — PASS
- [ ] `docker exec newsdc_dev_app php artisan route:list --name=cedec.contatos` — duas rotas
- [ ] `npm run build` na pasta `SDC` — sem erro
- [ ] `excesso` horizontal 0 em 375px e 840px, nas duas abas
- [ ] `grep -rn "style scoped" resources/js/Components/*/Cedec resources/js/Pages/Cedec` — nenhum resultado
- [ ] `grep -rn "prefers-color-scheme" resources/js/Components/*/Cedec resources/js/Pages/Cedec` — nenhum resultado
- [ ] `git log --oneline -4` — quatro commits gitmoji com escopo `cedec` e sem trailer de co-autor
- [ ] `git status` — nenhum arquivo de teste de depuracao pendente

---

## Divergencias entre spec, contrato e codigo real

Registradas aqui, **nao corrigidas** por este plano. Cada uma precisa de decisao humana.

1. **Tres abas na spec, duas no contrato.** A spec (secao 8) descreve "uma pagina com tres
   abas": E-mails, Outlook CA e Telefones. O contrato (secao 8) fixa
   `aba: 'emails' | 'telefones'`. Este plano segue o contrato: duas abas, com os blocos do
   Outlook aparecendo DENTRO de cada aba (a de e-mails e a de telefones), o que na pratica
   entrega as tres funcoes em duas abas. Se a decisao for voltar para tres abas, o valor de
   `aba` e a lista `abas` da pagina mudam.

2. **`ExportCsvModal` foi desenhado para recorte por data.** O componente abre em "Periodo
   Especifico", so habilita o botao com as duas datas preenchidas, e o unico escopo util para
   contatos e "Toda Serie Historica". O controller ignora `type`, `data_inicio`, `data_fim` e
   `all`. Nao alterei o modal: ele tem 20 consumidores. A correcao limpa seria uma prop
   aditiva `allowPeriod` (default `true`), no mesmo molde da `allowRedec` que ja existe —
   fora do escopo desta fase.

3. **Ordem dos campos de telefone diverge dentro do proprio contrato.** Na secao 4, o tipo de
   retorno de `telefones()` lista `tel_prefeitura, tel_prefeitura_2, fax_prefeitura,
   prefeito_telefone, prefeito_celular`; o docblock de `blocosDeTelefone()` diz
   "`tel_prefeitura + tel_prefeitura_2 + prefeito_telefone + prefeito_celular +
   fax_prefeitura`". O enunciado da fase repete a primeira ordem. O plano usa as duas,
   explicitamente: a ordem do tipo de retorno para a tabela e o CSV, a ordem do docblock para
   a concatenacao dos blocos, cada uma numa constante nomeada.

4. **"Breakpoint por `useMobile` alinhado em `lg`" nao bate com o `useMobile` real.** No
   codigo, `isMobile` e `matchMedia('(max-width: 767px)')` — corta em `md`, nao em `lg`. So
   `isDesktop` corta em 1024px. Este plano usa `isDesktop` para toda decisao de layout, que e
   a unica leitura que satisfaz a regra 5 da skill. `Organisms/Table/ResponsiveTable.vue`, que
   seria o componente generico para "tabela vira bloco", corta em `md` (`hidden md:block`) e
   por isso **nao** foi usado: entre 768 e 1023px ele deixaria a tabela de cinco colunas
   rolando de lado.

5. **`Molecules/Navigation/ModuleTabs.vue` tem `<style scoped>`.** A regra proibe
   `<style scoped>` **novo**; o componente e preexistente e a regra 22 manda usa-lo em vez de
   escrever outra tira de abas. Nenhum arquivo criado por esta fase tem `<style scoped>`.

6. **O contrato nao nomeia tabela para a pagina de contatos.** A secao 8 lista
   `PrefeituraTable` (listagem da fase 3) mas nenhuma tabela para os relatorios, embora a
   spec descreva "tabela municipio + e-mail institucional". Dai a adicao declarada
   `Organisms/Cedec/ContatoTabela.vue`.

7. **`ContatoBloco` precisa de mais que `{ indice, total, texto }`.** Com so essas tres props
   a molecula teria de invocar `useCopiarTexto` e guardar o proprio estado de "copiado",
   violando a regra de camada. Dai a adicao declarada das props `copiado` e do emit `copiar`.

8. **O `index()` faz quatro consultas de ~853 linhas.** `emails()`, `telefones()`,
   `blocosDeEmail()` e `blocosDeTelefone()` consultam o banco cada um. Foi escolha
   deliberada: extrair um cache interno no service economizaria duas consultas triviais e
   acrescentaria estado a uma classe hoje sem estado. Se a medicao em producao mostrar que
   pesa, o lugar da correcao e o service, nao o controller.

9. **`phpunit.xml` nao isola o banco de teste.** As linhas de sqlite estao comentadas: os
   testes rodam contra o banco de desenvolvimento, com as 853 prefeituras reais. E contorno,
   nao conserto: isolar a conexao de teste e decisao maior que esta fase.

   **Correcao de code review (2026-09-05).** A versao anterior deste plano zerava os oito
   campos de contato de TODAS as prefeituras num `DB::table('compdec_prefeituras')->update()`
   dentro do `setUp`, protegido so pelo rollback do `DatabaseTransactions`. Isso fazia com
   que o modo de falha do teste fosse a destruicao do banco: qualquer Ctrl+C, fatal error,
   timeout de container ou OOM antes do rollback apagava em definitivo o que o ETL carregou,
   sem backup. Um teste nao pode ter isso como modo de falha.

   A regra agora e absoluta nas duas classes de teste desta fase: **nenhum `update()` ou
   `delete()` sem `where` restrito as linhas que o proprio teste criou.** Cada teste monta a
   fixture em `prefeituraCom()`, que ja aplica `CAMPOS_DE_CONTATO` na PROPRIA linha, e as
   asserçoes passam a ser por pertencimento (`assertStringContainsString` sobre o texto dos
   blocos) ou por contagem RELATIVA ao "antes" — nunca indice fixo como `blocos.0.texto` nem
   total exato como `has('blocos', 1)`, que so passavam porque o banco tinha sido esvaziado.
