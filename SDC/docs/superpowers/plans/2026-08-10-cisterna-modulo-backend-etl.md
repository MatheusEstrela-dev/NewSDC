# Modulo Cisterna — Backend + ETL — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Substituir o scaffold `app/Modules/Cisterna` por um modulo fiel ao legado `sdc` — cadastro de beneficiario, comunidades, lotes/OS, cadeia de vistoria em tres etapas e notificacoes de fiscalizacao — com ETL idempotente dos dados de producao.

**Architecture:** Dominio relacional em Postgres com tres pontos de polimorfismo (checklist de itens, notificacoes, midia via Spatie MediaLibrary). As tres tabelas de relatorio do legado colapsam em `cisterna_vistorias` com coluna `etapa`. ETL em duas etapas: landing cru em `jsonb` (nao depende do DDL de producao) e refino para o dominio.

**Tech Stack:** Laravel 11, PHP 8.3, PostgreSQL, Inertia/Vue (fase seguinte), spatie/laravel-medialibrary ^11.10, spatie/laravel-permission ^6.24, PHPUnit ^11.4.

**Spec:** [docs/superpowers/specs/2026-08-10-cisterna-migracao-legado-design.md](../specs/2026-08-10-cisterna-migracao-legado-design.md)

## Global Constraints

- `declare(strict_types=1);` no topo de todo arquivo PHP novo.
- **Sem emojis dentro do codigo.** Gitmoji apenas na mensagem de commit.
- Commits em pt-BR no formato gitmoji: `<emoji> tipo(escopo): descricao`. **Nunca incluir trailer `Co-Authored-By`.**
- Commits atomicos: agrupar os arquivos que entregam UMA mudanca. Nao commitar arquivo isolado de uma mesma feature.
- Migrations: **consolidar** na migration principal em vez de empilhar corretiva. A `2026_05_08_140000_create_cisternas_table.php` e reescrita, nao complementada.
- CHECK constraints de enum textual: aplicar **somente** quando `DB::getDriverName() === 'pgsql'`.
- Namespace do modulo: `App\Modules\Cisterna\...`. Policies em `App\Policies\` (o projeto nao tem `Policies/` dentro de modulo).
- `Requests/` e `Resources/` na **raiz** do modulo, sem `Http/`. Sem pasta `Exports/`.
- Export e **CSV streamado** via `StreamedResponse`. `maatwebsite/excel` nao esta instalado e nao sera adicionado.
- Testes: PHPUnit 11, namespace `Tests\Feature\Cisterna` / `Tests\Unit\Cisterna`, `use DatabaseTransactions;`, estender `Tests\TestCase`.
- As migrations **nao rodam em sqlite** neste projeto — os testes usam o Postgres real (Docker `newsdc_dev_db`, exposto em `127.0.0.1:5434`).

### Ambiente — resolvido por `scripts/test-host.sh`, nao improvisar

Rodar teste neste projeto pelo caminho obvio **nao funciona**, e cada armadilha falha de um jeito diferente. Foram cinco, descobertas uma a uma na Onda 1:

1. **`php artisan test` nao existe.** Responde `Command "test" is not defined`, mesmo com `nunomaduro/collision ^8.5` instalado e o `TestCommand.php` presente no vendor.
2. **O `php` do PATH e 8.1.25 e nao carrega o `vendor/`.** Parse error em `sebastian/version` por causa de `readonly class`. Precisa do 8.3 do Laragon.
3. **O `php.ini` do 8.3 do Laragon nao habilita `pdo_pgsql`.** Sem isso o PDO responde `could not find driver`.
4. **`.env.testing` forca `DB_CONNECTION=sqlite` e `:memory:`**, e o `phpunit.xml` forca `APP_ENV=testing`. Como as migrations deste projeto **nao rodam em sqlite**, o banco vem vazio e todo teste morre com `no such table: municipios`. As env vars de banco precisam ir explicitas — o dotenv do Laravel e imutavel, entao variavel de ambiente real vence o arquivo.
5. **Se `bootstrap/cache/config.php` existir, o item 4 nao resolve** — config cacheado ignora env var.

Os cinco estao encapsulados em **`SDC/scripts/test-host.sh`**, criado na Onda 1. Todo comando de teste deste plano usa ele:

```bash
scripts/test-host.sh --filter=NomeDoTeste    # um teste
scripts/test-host.sh --filter=Cisterna       # o modulo inteiro
scripts/test-host.sh                         # suite completa
```

Argumentos sao repassados ao phpunit. Se o PHP 8.3 estiver em outro caminho, exportar `SDC_PHP_DIR`.

**Nao contorne o script.** Se ele falhar, o problema e real e precisa ser entendido — improvisar `php vendor/bin/phpunit` faz o teste rodar contra um sqlite vazio e **passar por vacuidade** em alguns casos, o que e pior que falhar.

Para os comandos que **nao** sao teste (`artisan migrate`, `artisan tinker`, `artisan db:seed`), o plano usa `$PHP`. Exportar no inicio da sessao:

```bash
PHP=/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe
```

E quando o comando tocar o banco, acrescentar as env vars, pelo mesmo motivo do item 4:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc DB_PASSWORD=secret \
  $PHP artisan migrate
```

**Os arquivos de teste NAO entram no commit.** Regra de ouro 10, confirmada pelo dono do repositorio na Onda 1: arquivo de teste criado durante o trabalho fica no working tree, nao no commit. `SDC/.gitignore:39` ja ignora `tests`, entao o comportamento padrao do `git add` ja e o correto.

- **Nunca use `git add -f`** para versionar teste.
- O teste **precisa existir e passar** — e o que valida a task. Ele so nao e commitado.
- Ao relatar a task, cole a saida real do `scripts/test-host.sh` e diga explicitamente que o arquivo de teste ficou como WIP.
- Se `git add tests/...` reclamar de path ignorado, **esta correto**: siga sem o teste.

Consequencia pratica: quem retomar o trabalho noutra maquina nao tera os testes. Isso e aceito de proposito pelo dono do repositorio.

### Git com agentes em paralelo

Aprendido na Onda 1, onde um commit varreu o trabalho de outro agente: **`git commit` leva tudo o que esta no indice**, nao apenas o que voce acabou de adicionar.

- Faca `git add` dos seus arquivos e `git commit` **na mesma sequencia**, sem intervalo entre os dois.
- Nunca use `git add -A`, `git add .` nem `git commit -a`.
- Se `git commit` responder `no changes added to commit`, outro agente pode ter commitado seus arquivos junto. Confira com `git log --stat -1` e **relate** em vez de tentar reescrever a historia.
- Nunca rode `git reset`, `git rebase` nem `git checkout <branch>`: a arvore de trabalho e compartilhada.

---

## File Structure

### Criados — dominio

| Arquivo | Responsabilidade |
|---|---|
| `app/Modules/Cisterna/Enums/SituacaoAnalise.php` | Ciclo de analise documental, 6 casos |
| `app/Modules/Cisterna/Enums/SituacaoObra.php` | Ciclo de execucao fisica, 3 casos |
| `app/Modules/Cisterna/Enums/EtapaVistoria.php` | fornecedor / compdec / cedec |
| `app/Modules/Cisterna/Enums/ItemInstalacao.php` | Os 13 itens conferidos |
| `app/Modules/Cisterna/Enums/UnidadeItem.php` | un / m |
| `app/Modules/Cisterna/Enums/ResponsavelPipa.php` | 5 responsaveis por atendimento de pipa |
| `app/Modules/Cisterna/Models/CisternaBeneficiario.php` | Agregado raiz do cadastro |
| `app/Modules/Cisterna/Models/CisternaVistoria.php` | Uma etapa da cadeia de vistoria |
| `app/Modules/Cisterna/Models/CisternaItemConferido.php` | Checklist polimorfico |
| `app/Modules/Cisterna/Models/CisternaComunidade.php` | Comunidade por municipio |
| `app/Modules/Cisterna/Models/CisternaLote.php` | Lote de contratacao |
| `app/Modules/Cisterna/Models/CisternaOrdemServico.php` | OS dentro do lote |
| `app/Modules/Cisterna/Models/CisternaNotificacao.php` | Notificacao polimorfica |
| `app/Modules/Cisterna/Models/CisternaAtendimentoPipa.php` | Responsavel declarado |
| `database/migrations/2026_05_08_140000_create_cisternas_table.php` | **Reescrita:** cria as 8 tabelas do dominio |

### Criados — servicos e HTTP

| Arquivo | Responsabilidade |
|---|---|
| `app/Modules/Cisterna/Services/BeneficiarioService.php` | Listagem com escopo por perfil, CRUD, acoes em massa |
| `app/Modules/Cisterna/Services/NumeracaoInstalacaoService.php` | Alocacao atomica do numero de QR |
| `app/Modules/Cisterna/Services/VistoriaService.php` | Cadeia fornecedor -> compdec -> cedec |
| `app/Modules/Cisterna/Services/OrdemServicoService.php` | CRUD + `timeline()` do lote |
| `app/Modules/Cisterna/Services/ComunidadeService.php` | CRUD de comunidades |
| `app/Modules/Cisterna/Services/LoteService.php` | CRUD de lotes |
| `app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php` | Emissao polimorfica |
| `app/Modules/Cisterna/Services/BeneficiarioExportService.php` | CSV de 39 colunas |
| `app/Modules/Cisterna/Services/QrCodeService.php` | SVG, PDF individual, PDF em lote, folhas vazias |
| `app/Modules/Cisterna/Observers/CisternaVistoriaObserver.php` | Avanca `situacao_obra` |
| `app/Policies/Cisterna*Policy.php` | 6 policies estendendo `BasePolicy` |

### Criados — ETL

| Arquivo | Responsabilidade |
|---|---|
| `database/migrations/2026_08_10_120000_create_cisterna_etl_tables.php` | `cisterna_legado_raw` + `cisterna_etl_log` |
| `app/Modules/Cisterna/Console/ExtrairCisternaLegadoCommand.php` | MySQL legado -> `doc jsonb` |
| `app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php` | `doc jsonb` -> dominio |
| `app/Modules/Cisterna/Domain/Etl/MapaCamposLegado.php` | Traducao de nome e tipo de coluna |

### Modificados

| Arquivo | Mudanca |
|---|---|
| `app/Models/Municipio.php` | Novo scope `habilitadosCisterna()` |
| `config/filesystems.php` | Disco de leitura `legado_cisterna` |
| `config/permissions.php` | Grupo `CISTERNAS` expandido em 6 subgrupos |
| `config/database.php` | Conexao `legado_cisterna_mysql` |
| `app/Providers/AuthServiceProvider.php` | Registro das 6 policies, remocao da `CisternaPolicy` |
| `app/Modules/Cisterna/CisternaServiceProvider.php` | Registro do observer e dos commands |
| `routes/modules/cisterna.php` | Reescrito para os novos agregados |

### Removidos

`app/Modules/Cisterna/Enums/{TipoCisterna,StatusCisterna}.php`, `DTOs/CisternaDTO.php`, `Models/Cisterna.php`, `Resources/Cisterna*Resource.php`, `Requests/{Store,Update}CisternaRequest.php`, `Services/CisternaService.php`, `Controllers/CisternaController.php`, `app/Policies/CisternaPolicy.php`, `database/factories/CisternaFactory.php`, `resources/js/Pages/Cisterna/*`, `resources/js/Templates/Cisterna/*`, `resources/js/Components/{Organisms,Molecules}/Cisterna/*`.

---

## Fases e portoes

| Fase | Tasks | Portao de saida |
|---|---|---|
| 0 — Reconhecimento | 1 | DDL de producao em maos, homonimos medidos, `at_cisterna` conferido |
| 1 — Dominio e banco | 2 a 5 | `migrate:fresh` verde, factories gerando registro valido |
| 2 — Servicos e HTTP | 6 a 12 | Escopo por perfil verificado nos tres casos |
| 3 — ETL | 13 a 16 | Duas execucoes seguidas sem duplicar registro |
| 4 — Limpeza | 17 | Nenhuma referencia ao scaffold no codigo ou nos assets |

O plano cobre backend e ETL. **Frontend Vue/Inertia e um plano separado**, cujo primeiro passo e a leitura das 22 views Blade do legado (7.632 linhas, lacuna L4 do spec).

---

## FASE 0 — Reconhecimento

### Task 1: Verificar o dump de producao e preparar o banco de trabalho

**O bloqueio original desta fase caiu.** O DDL e os dados de producao estao no repositorio: `database/data/Cisternas.sql` (24 MB, 28.417 linhas, exportado de `200.198.29.227`, MySQL 8.0.31, via HeidiSQL). A analise completa esta na **secao 4.6 do spec**, e ja corrigiu varias premissas erradas.

Esta task deixou de ser levantamento e passou a ser **verificacao mais preparacao do ambiente**. Ela nao escreve codigo de aplicacao.

O que a analise ja estabeleceu, e que **nao precisa ser refeito**:

| Item | Valor |
|---|---|
| Volumes | 8.105 beneficiarios, 885 comunidades, 856/858/675 vistorias, 7 OS, 3 lotes, 7 notificacoes |
| Engine | 6 das 10 tabelas sao **MyISAM**: zero FK, zero transacao no legado |
| `codmundv` -> `municipios.codigo_ibge` | 55 codigos distintos, **100% casam**, zero orfao |
| Orfaos | apenas 2, em `rel_compdec` -> `rel_fornecedor` |
| `TipoMoradia` | `propria` (7.764, somando `PR?PRIA`), `outros` (108), `cedida` (57), `alugada` (14), `0` (162) -> null |
| `CoberturaTelhado` | `pvc` (4.963), `ceramica` (2.883), `fibrocimento` (157), `zinco` (39), `outros` (22), `concreto` (11), `metalica` (10), `amianto` (6), `0` (14) -> null |
| CPF | 492 repetidos em 1.003 linhas; 485 marcados como Duplicado -> **indice unico parcial** |
| Vistorias | 65 duplicatas de fornecedor e 17 de CEDEC, todas double-submit -> **dedup no refino** |
| `numero_instalacao` | 792 distintos em 828 preenchidos, faixa 1..50.000, **zero colisao entre beneficiarios diferentes** |
| Comunidades homonimas | **75** nomes em mais de um municipio |
| `at_cisterna` no Postgres | **0 de 854** — precisa ser populado pelo refino |

**Files:**
- Create: `docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md`

**Interfaces:**
- Produces: `docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md` com o resultado das verificacoes, e o banco MySQL `cisterna_analise` carregado para consulta durante as tasks 15 a 18

- [ ] **Step 1: Conferir que o dump e o mesmo de producao**

O dump foi exportado em algum momento do passado. Se houve carga nova em producao depois disso, o ETL importaria dado velho.

```bash
head -6 database/data/Cisternas.sql
```

Confere o servidor (`200.198.29.227`) e a versao. Depois, comparar o maior `id` do dump com o de producao:

```bash
grep -oE "AUTO_INCREMENT=[0-9]+" database/data/Cisternas.sql | head -1
```

Esperado: `AUTO_INCREMENT=9205` para `sinc_cisterna`. Se producao ja passou disso, **reexportar antes de continuar** — o ETL desta entrega assume 8.105 beneficiarios.

- [ ] **Step 2: Carregar o dump num banco de trabalho isolado**

O dump traz `CREATE DATABASE dbsdc` e `USE dbsdc` — importar direto **contamina o banco de dev do legado**. Remover as duas linhas antes:

```bash
MY=/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin

sed -e 's/^CREATE DATABASE IF NOT EXISTS `dbsdc`.*$//'     -e 's/^USE `dbsdc`;$//'     database/data/Cisternas.sql > /c/tmp/cisternas_isolado.sql

"$MY/mysql.exe" -u root -h 127.0.0.1 -e   "DROP DATABASE IF EXISTS cisterna_analise; CREATE DATABASE cisterna_analise CHARACTER SET utf8mb4;"

"$MY/mysql.exe" -u root -h 127.0.0.1 --force cisterna_analise < /c/tmp/cisternas_isolado.sql
```

`--force` e necessario: `sinc_cisterna_old` tem PKs duplicadas no dump. Os erros aparecem **somente** nessa tabela, que nao e portada. Erro em qualquer outra tabela e sinal de dump corrompido — parar e reexportar.

Conferir os volumes:

```bash
"$MY/mysql.exe" -u root -h 127.0.0.1 cisterna_analise -e "
SELECT 'sinc_cisterna' t, COUNT(*) n FROM sinc_cisterna
UNION ALL SELECT 'com', COUNT(*) FROM sinc_cisterna_com
UNION ALL SELECT 'rel_fornecedor', COUNT(*) FROM sinc_cisterna_rel_fornecedor
UNION ALL SELECT 'rel_compdec', COUNT(*) FROM sinc_cisterna_rel_compdec
UNION ALL SELECT 'rel_cedec', COUNT(*) FROM sinc_cisterna_rel_cedec;"
```

Esperado: 8105, 885, 856, 858, 675. Divergencia significa dump diferente do analisado — revalidar a secao 4.6 do spec antes de seguir.

- [ ] **Step 3: Apontar a conexao do ETL para o banco de trabalho**

No `.env`, para rodar o ETL sem VPN nem acesso a producao:

```
LEGADO_CISTERNA_DB_DATABASE=cisterna_analise
```

- [ ] **Step 4: Confirmar que `at_cisterna` esta zerado e que a correcao e necessaria**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc -t -c   "SELECT COUNT(*) FILTER (WHERE at_cisterna = 1) AS habilitados, COUNT(*) AS total FROM cedec_municipio;"
```

Esperado: `0 | 854`. Isso confirma o achado 4.6.9-E do spec: o flag nunca foi populado, e `Municipio::habilitadosCisterna()` devolveria lista vazia. A Task 18 corrige, marcando os 55 municipios com beneficiario.

Se vier diferente de zero, alguem populou o flag manualmente: comparar a lista com os 55 codigos do legado antes de deixar a Task 18 sobrescrever.

- [ ] **Step 5: Reconferir a ponte de municipio**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc -t -c "
SELECT COUNT(*) AS sem_correspondencia
FROM (VALUES ('3101706'),('3104452'),('3104502')) AS legado(cod)
LEFT JOIN municipios m ON m.codigo_ibge = legado.cod
WHERE m.id IS NULL;"
```

Esperado: `0`. A verificacao completa dos 55 codigos ja foi feita e deu 100% de correspondencia (spec 4.6.4); esta e uma amostra de sanidade, para pegar banco de teste sem os municipios seedados.

- [ ] **Step 6: Registrar as pendencias que exigem a area**

Criar `docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md` com o resultado dos steps acima e as quatro questoes que **nao se resolvem por consulta** (spec 7.4):

1. Os **26 CPFs que colidem** mesmo fora dos marcados como Duplicado — qual registro vale?
2. `cisterna_id = 8088`, com tres relatorios e `num_instalacao = 50000` — descartar como teste?
3. `sinc_cisterna_relatorio_cedec`: 2 linhas, checklist de 26 itens, sem codigo no legado. E o futuro do formulario da CEDEC ou tentativa abandonada?
4. Os 55 municipios com beneficiario sao exatamente os que devem ficar habilitados no programa?

- [ ] **Step 7: Commit**

```bash
git add docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md .env.example
git commit -m "📝 docs(cisterna): verificacao do dump de producao e pendencias da area"
```
---

## FASE 1 — Dominio e banco

### Task 2: Enums do dominio

Seis enums firmes. `TipoMoradia` e `CoberturaTelhado` ficam para a Task 16, conforme decidido na Task 1 Step 3.

**Files:**
- Create: `app/Modules/Cisterna/Enums/SituacaoAnalise.php`
- Create: `app/Modules/Cisterna/Enums/SituacaoObra.php`
- Create: `app/Modules/Cisterna/Enums/EtapaVistoria.php`
- Create: `app/Modules/Cisterna/Enums/ItemInstalacao.php`
- Create: `app/Modules/Cisterna/Enums/UnidadeItem.php`
- Create: `app/Modules/Cisterna/Enums/ResponsavelPipa.php`
- Delete: `app/Modules/Cisterna/Enums/TipoCisterna.php`
- Delete: `app/Modules/Cisterna/Enums/StatusCisterna.php`
- Test: `tests/Unit/Cisterna/EnumsTest.php`

**Interfaces:**
- Consumes: nada
- Produces:
  - `SituacaoAnalise::{EM_EDICAO,APROVADO,REPROVADO,RESSALVA,DESCONSIDERADO,DUPLICADO}`, `label(): string`, `options(): array`, `static valores(): array<int,string>`
  - `SituacaoObra::{PROCESSAMENTO,ENVIO_INSTALACAO,INSTALADO}`, mesmos metodos
  - `EtapaVistoria::{FORNECEDOR,COMPDEC,CEDEC}`, mesmos metodos, mais `proxima(): ?self`
  - `ItemInstalacao` com 13 casos, mesmos metodos, mais `unidadePadrao(): ?UnidadeItem` e `aceitaDetalhes(): bool`
  - `UnidadeItem::{UN,M}`, mesmos metodos
  - `ResponsavelPipa::{DEFESA_CIVIL,EXERCITO,PARTICULAR,PREFEITURA,OUTROS}`, mesmos metodos

- [ ] **Step 1: Escrever o teste que falha**

`tests/Unit/Cisterna/EnumsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_situacao_analise_cobre_os_seis_estados_do_legado(): void
    {
        $this->assertSame(
            ['em_edicao', 'aprovado', 'reprovado', 'ressalva', 'desconsiderado', 'duplicado'],
            SituacaoAnalise::valores()
        );
        $this->assertSame('Em Edicao', SituacaoAnalise::EM_EDICAO->label());
        $this->assertSame('Desconsiderar Cadastro', SituacaoAnalise::DESCONSIDERADO->label());
    }

    public function test_situacao_obra_cobre_os_tres_estados_do_legado(): void
    {
        $this->assertSame(['processamento', 'envio_instalacao', 'instalado'], SituacaoObra::valores());
        $this->assertSame('Envio Instalacao', SituacaoObra::ENVIO_INSTALACAO->label());
    }

    public function test_etapa_vistoria_encadeia_fornecedor_compdec_cedec(): void
    {
        $this->assertSame(EtapaVistoria::COMPDEC, EtapaVistoria::FORNECEDOR->proxima());
        $this->assertSame(EtapaVistoria::CEDEC, EtapaVistoria::COMPDEC->proxima());
        $this->assertNull(EtapaVistoria::CEDEC->proxima());
    }

    public function test_item_instalacao_tem_os_treze_itens_do_legado(): void
    {
        $this->assertCount(13, ItemInstalacao::cases());
        $this->assertContains('cisterna_logo', ItemInstalacao::valores());
        $this->assertContains('cap_pvc', ItemInstalacao::valores());
    }

    public function test_itens_de_metragem_tem_unidade_metro(): void
    {
        $this->assertSame(UnidadeItem::M, ItemInstalacao::CALHA->unidadePadrao());
        $this->assertSame(UnidadeItem::M, ItemInstalacao::TUBULACAO->unidadePadrao());
        $this->assertSame(UnidadeItem::UN, ItemInstalacao::TE_PVC->unidadePadrao());
        $this->assertNull(ItemInstalacao::BOMBA->unidadePadrao());
    }

    public function test_apenas_fixacao_aceita_detalhes(): void
    {
        $this->assertTrue(ItemInstalacao::FIXACAO->aceitaDetalhes());
        $this->assertFalse(ItemInstalacao::CALHA->aceitaDetalhes());
        $this->assertFalse(ItemInstalacao::CISTERNA_LOGO->aceitaDetalhes());
    }

    public function test_responsavel_pipa_cobre_as_cinco_colunas_respat_do_legado(): void
    {
        $this->assertSame(
            ['defesa_civil', 'exercito', 'particular', 'prefeitura', 'outros'],
            ResponsavelPipa::valores()
        );
    }

    public function test_options_devolve_pares_value_label(): void
    {
        $options = SituacaoObra::options();
        $this->assertSame(['value' => 'processamento', 'label' => 'Processamento'], $options[0]);
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

Run: `scripts/test-host.sh --filter=EnumsTest`
Expected: FAIL com `Class "App\Modules\Cisterna\Enums\SituacaoAnalise" not found`.

- [ ] **Step 3: Escrever `SituacaoAnalise`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Ciclo de analise documental do cadastro. Substitui a coluna `aprovado`
 * (tinyint 0..5) do legado `sinc_cisterna`. Ortogonal a SituacaoObra: um
 * cadastro aprovado pode estar em Processamento, e um em Ressalva pode ja
 * estar Instalado.
 */
enum SituacaoAnalise: string
{
    case EM_EDICAO = 'em_edicao';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';
    case RESSALVA = 'ressalva';
    case DESCONSIDERADO = 'desconsiderado';
    case DUPLICADO = 'duplicado';

    public function label(): string
    {
        return match ($this) {
            self::EM_EDICAO => 'Em Edicao',
            self::APROVADO => 'Aprovado',
            self::REPROVADO => 'Reprovado',
            self::RESSALVA => 'Ressalva',
            self::DESCONSIDERADO => 'Desconsiderar Cadastro',
            self::DUPLICADO => 'Duplicado',
        };
    }

    /**
     * Codigo numerico do legado, para o refino do ETL.
     */
    public static function doLegado(int|string|null $codigo): self
    {
        return match ((string) $codigo) {
            '1' => self::APROVADO,
            '2' => self::REPROVADO,
            '3' => self::RESSALVA,
            '4' => self::DESCONSIDERADO,
            '5' => self::DUPLICADO,
            default => self::EM_EDICAO,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 4: Escrever `SituacaoObra`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Ciclo de execucao fisica da obra. Substitui a coluna `estado` (0..2) do
 * legado. Ortogonal a SituacaoAnalise.
 */
enum SituacaoObra: string
{
    case PROCESSAMENTO = 'processamento';
    case ENVIO_INSTALACAO = 'envio_instalacao';
    case INSTALADO = 'instalado';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSAMENTO => 'Processamento',
            self::ENVIO_INSTALACAO => 'Envio Instalacao',
            self::INSTALADO => 'Instalado',
        };
    }

    public static function doLegado(int|string|null $codigo): self
    {
        return match ((string) $codigo) {
            '1' => self::ENVIO_INSTALACAO,
            '2' => self::INSTALADO,
            default => self::PROCESSAMENTO,
        };
    }

    /**
     * Situacoes que o fornecedor externo pode ver, conforme
     * CisternaController.php:75 do legado.
     *
     * @return array<int, string>
     */
    public static function visiveisAoFornecedor(): array
    {
        return [self::ENVIO_INSTALACAO->value, self::INSTALADO->value];
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 5: Escrever `EtapaVistoria`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * As tres etapas da cadeia de vistoria. No legado eram tres tabelas
 * distintas: sinc_cisterna_rel_fornecedor, _rel_compdec e _rel_cedec.
 */
enum EtapaVistoria: string
{
    case FORNECEDOR = 'fornecedor';
    case COMPDEC = 'compdec';
    case CEDEC = 'cedec';

    public function label(): string
    {
        return match ($this) {
            self::FORNECEDOR => 'Relatorio do Fornecedor',
            self::COMPDEC => 'Conferencia da COMPDEC',
            self::CEDEC => 'Fiscalizacao da CEDEC',
        };
    }

    public function proxima(): ?self
    {
        return match ($this) {
            self::FORNECEDOR => self::COMPDEC,
            self::COMPDEC => self::CEDEC,
            self::CEDEC => null,
        };
    }

    /**
     * Somente a etapa CEDEC preenche processo_sei, contrato, empenho,
     * placa_obras e engenheiro_art.
     */
    public function exigeDadosAdministrativos(): bool
    {
        return $this === self::CEDEC;
    }

    /**
     * Somente a etapa do fornecedor aloca o numero do QR Code.
     */
    public function alocaNumeroInstalacao(): bool
    {
        return $this === self::FORNECEDOR;
    }

    public function tabelaLegado(): string
    {
        return match ($this) {
            self::FORNECEDOR => 'sinc_cisterna_rel_fornecedor',
            self::COMPDEC => 'sinc_cisterna_rel_compdec',
            self::CEDEC => 'sinc_cisterna_rel_cedec',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 6: Escrever `UnidadeItem`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

enum UnidadeItem: string
{
    case UN = 'un';
    case M = 'm';

    public function label(): string
    {
        return match ($this) {
            self::UN => 'Unidade',
            self::M => 'Metro',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 7: Escrever `ItemInstalacao`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Os 13 itens conferidos na instalacao. No legado apareciam como colunas
 * booleanas, de quantidade e de foto repetidas nas tres tabelas de
 * relatorio, com nomes divergentes entre elas (calha_metros no COMPDEC,
 * qtd_calha no fornecedor, calha_opcao tambem no fornecedor).
 */
enum ItemInstalacao: string
{
    case CISTERNA_LOGO = 'cisterna_logo';
    case SUCAO = 'sucao';
    case BOMBA = 'bomba';
    case PLACA = 'placa';
    case CALHA = 'calha';
    case TUBULACAO = 'tubulacao';
    case FIXACAO = 'fixacao';
    case FILTRO = 'filtro';
    case BLOCO = 'bloco';
    case TE_PVC = 'te_pvc';
    case JOELHO_PVC = 'joelho_pvc';
    case LUVA_PVC = 'luva_pvc';
    case CAP_PVC = 'cap_pvc';

    public function label(): string
    {
        return match ($this) {
            self::CISTERNA_LOGO => 'Cisterna com logo',
            self::SUCAO => 'Sucao',
            self::BOMBA => 'Bomba',
            self::PLACA => 'Placa',
            self::CALHA => 'Calha',
            self::TUBULACAO => 'Tubulacao',
            self::FIXACAO => 'Fixacao',
            self::FILTRO => 'Filtro',
            self::BLOCO => 'Bloco',
            self::TE_PVC => 'Te PVC',
            self::JOELHO_PVC => 'Joelho PVC',
            self::LUVA_PVC => 'Luva PVC',
            self::CAP_PVC => 'Cap PVC',
        };
    }

    /**
     * Calha e tubulacao sao medidas em metros; as pecas de PVC em unidades;
     * os demais itens sao apenas conferidos, sem quantidade.
     */
    public function unidadePadrao(): ?UnidadeItem
    {
        return match ($this) {
            self::CALHA, self::TUBULACAO => UnidadeItem::M,
            self::TE_PVC, self::JOELHO_PVC, self::LUVA_PVC, self::CAP_PVC => UnidadeItem::UN,
            default => null,
        };
    }

    /**
     * Somente fixacao tem subquantidades (abracadeira, bucha, parafuso), que
     * vao na coluna `detalhes jsonb` — ver spec secao 4.6.
     */
    public function aceitaDetalhes(): bool
    {
        return $this === self::FIXACAO;
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 8: Escrever `ResponsavelPipa`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Quem atende o beneficiario com caminhao pipa. No legado eram cinco
 * colunas varchar(50): respAtDefesaCivil, respAtExercito, respAtParticular,
 * respAtPrefeitura, respAtOutros.
 */
enum ResponsavelPipa: string
{
    case DEFESA_CIVIL = 'defesa_civil';
    case EXERCITO = 'exercito';
    case PARTICULAR = 'particular';
    case PREFEITURA = 'prefeitura';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::DEFESA_CIVIL => 'Defesa Civil',
            self::EXERCITO => 'Exercito',
            self::PARTICULAR => 'Particular',
            self::PREFEITURA => 'Prefeitura',
            self::OUTROS => 'Outros',
        };
    }

    /**
     * Nome da coluna correspondente em sinc_cisterna, para o refino.
     */
    public function colunaLegado(): string
    {
        return match ($this) {
            self::DEFESA_CIVIL => 'respAtDefesaCivil',
            self::EXERCITO => 'respAtExercito',
            self::PARTICULAR => 'respAtParticular',
            self::PREFEITURA => 'respAtPrefeitura',
            self::OUTROS => 'respAtOutros',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 9: Rodar o teste e confirmar que passa**

Run: `scripts/test-host.sh --filter=EnumsTest`
Expected: PASS, 8 testes.

- [ ] **Step 10: Remover os enums do scaffold**

```bash
git rm app/Modules/Cisterna/Enums/TipoCisterna.php \
       app/Modules/Cisterna/Enums/StatusCisterna.php
```

Isso quebra `Models/Cisterna.php`, `DTOs/CisternaDTO.php`, `Services/CisternaService.php`, `Resources/Cisterna*Resource.php`, `Requests/*CisternaRequest.php` e `database/factories/CisternaFactory.php` — todos removidos na **Task 4**, que substitui o model. A Task 3 (migration) tambem nao depende deles. Nao rodar a suite completa entre esta task e a Task 4: rodar apenas `--filter=EnumsTest` e depois `--filter=SchemaCisternaTest`.

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Cisterna/Enums tests/Unit/Cisterna/EnumsTest.php
git commit -m "✨ feat(cisterna): enums do dominio do legado, aposenta TipoCisterna e StatusCisterna"
```

---

### Task 3: Migration do dominio — 8 tabelas

Reescrita completa da `2026_05_08_140000_create_cisternas_table.php`, **consolidando** em vez de empilhar corretiva (regra 9). A tabela `cisternas` do scaffold e derrubada: nao tem dados reais nem usuarios.

`tipo_moradia` e `cobertura_telhado` nascem `varchar` **sem CHECK** — os valores distintos so serao conhecidos na Task 16.

**Files:**
- Rewrite: `database/migrations/2026_05_08_140000_create_cisternas_table.php`
- Test: `tests/Feature/Cisterna/SchemaCisternaTest.php`

**Interfaces:**
- Consumes: `SituacaoAnalise::valores()`, `SituacaoObra::valores()`, `EtapaVistoria::valores()`, `ItemInstalacao::valores()`, `UnidadeItem::valores()`, `ResponsavelPipa::valores()` (Task 2)
- Produces: as tabelas `cisterna_comunidades`, `cisterna_lotes`, `cisterna_ordens_servico`, `cisterna_beneficiarios`, `cisterna_atendimentos_pipa`, `cisterna_vistorias`, `cisterna_itens_conferidos`, `cisterna_notificacoes`; e a sequence `cisterna_numero_instalacao_seq`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/SchemaCisternaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaCisternaTest extends TestCase
{
    public function test_as_oito_tabelas_do_dominio_existem(): void
    {
        foreach ([
            'cisterna_comunidades',
            'cisterna_lotes',
            'cisterna_ordens_servico',
            'cisterna_beneficiarios',
            'cisterna_atendimentos_pipa',
            'cisterna_vistorias',
            'cisterna_itens_conferidos',
            'cisterna_notificacoes',
        ] as $tabela) {
            $this->assertTrue(Schema::hasTable($tabela), "Tabela ausente: {$tabela}");
        }
    }

    public function test_a_tabela_cisternas_do_scaffold_nao_existe_mais(): void
    {
        $this->assertFalse(Schema::hasTable('cisternas'));
    }

    public function test_cpf_e_unico_apenas_fora_dos_marcados_como_duplicado(): void
    {
        $indice = DB::selectOne(
            'SELECT indexdef FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            ['cisterna_beneficiarios', 'cisterna_beneficiarios_cpf_unq']
        );

        $this->assertNotNull($indice, 'Indice unico parcial de CPF ausente.');
        $this->assertStringContainsString('UNIQUE', $indice->indexdef);
        // Parcial: producao tem 492 CPFs repetidos, 485 marcados como
        // Duplicado. Ver spec secao 4.6.5.
        $this->assertStringContainsString("situacao_analise <> 'duplicado'", $indice->indexdef);
    }

    public function test_cpf_duplicado_e_aceito_quando_marcado_como_duplicado(): void
    {
        $municipioId = DB::table('municipios')->value('id');

        $base = [
            'nome' => 'Teste Tombstone',
            'municipio_id' => $municipioId,
            'situacao_obra' => 'processamento',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('cisterna_beneficiarios')->insert(array_merge($base, [
            'cpf' => '52998224725',
            'situacao_analise' => 'aprovado',
        ]));

        // O legado marcava a duplicata com aprovado=5 em vez de impedi-la.
        DB::table('cisterna_beneficiarios')->insert(array_merge($base, [
            'cpf' => '52998224725',
            'situacao_analise' => 'duplicado',
        ]));

        $this->assertSame(
            2,
            DB::table('cisterna_beneficiarios')->where('cpf', '52998224725')->count()
        );
    }

    public function test_cpf_duplicado_e_rejeitado_entre_dois_registros_ativos(): void
    {
        $municipioId = DB::table('municipios')->value('id');

        $base = [
            'nome' => 'Teste Colisao',
            'municipio_id' => $municipioId,
            'situacao_analise' => 'aprovado',
            'situacao_obra' => 'processamento',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('cisterna_beneficiarios')->insert(array_merge($base, ['cpf' => '11144477735']));

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('cisterna_beneficiarios')->insert(array_merge($base, ['cpf' => '11144477735']));
    }

    public function test_uma_vistoria_por_etapa_e_por_beneficiario(): void
    {
        $this->assertTrue($this->temIndiceUnico('cisterna_vistorias', ['beneficiario_id', 'etapa']));
    }

    public function test_numero_de_instalacao_e_unico(): void
    {
        $this->assertTrue($this->temIndiceUnico('cisterna_vistorias', ['numero_instalacao']));
    }

    public function test_um_registro_por_item_conferido(): void
    {
        $this->assertTrue($this->temIndiceUnico(
            'cisterna_itens_conferidos',
            ['conferivel_type', 'conferivel_id', 'item']
        ));
    }

    public function test_comunidade_e_unica_no_municipio(): void
    {
        $this->assertTrue($this->temIndiceUnico('cisterna_comunidades', ['municipio_id', 'nome']));
    }

    public function test_check_constraint_rejeita_situacao_de_analise_invalida(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('cisterna_beneficiarios')->insert([
            'cpf' => '00000000191',
            'nome' => 'Teste CHECK',
            'municipio_id' => DB::table('municipios')->value('id'),
            'situacao_analise' => 'valor_que_nao_existe',
            'situacao_obra' => 'processamento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_sequence_do_numero_de_instalacao_existe(): void
    {
        $existe = DB::selectOne(
            "SELECT 1 AS ok FROM pg_class WHERE relkind = 'S' AND relname = ?",
            ['cisterna_numero_instalacao_seq']
        );

        $this->assertNotNull($existe);
    }

    public function test_indice_parcial_de_ranqueamento_existe(): void
    {
        $indice = DB::selectOne(
            'SELECT indexdef FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            ['cisterna_beneficiarios', 'cisterna_beneficiarios_ranqueamento_idx']
        );

        $this->assertNotNull($indice);
        $this->assertStringContainsString('ranqueamento_ordem IS NOT NULL', $indice->indexdef);
    }

    /**
     * @param  array<int, string>  $colunas
     */
    private function temIndiceUnico(string $tabela, array $colunas): bool
    {
        $indices = DB::select(
            'SELECT indexdef FROM pg_indexes WHERE tablename = ?',
            [$tabela]
        );

        foreach ($indices as $indice) {
            if (! str_contains($indice->indexdef, 'UNIQUE')) {
                continue;
            }

            $todasPresentes = true;
            foreach ($colunas as $coluna) {
                if (! str_contains($indice->indexdef, $coluna)) {
                    $todasPresentes = false;
                    break;
                }
            }

            if ($todasPresentes) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

Run: `scripts/test-host.sh --filter=SchemaCisternaTest`
Expected: FAIL — `Tabela ausente: cisterna_comunidades`, e `test_a_tabela_cisternas_do_scaffold_nao_existe_mais` tambem falha porque `cisternas` ainda existe.

- [ ] **Step 3: Reescrever a migration — cabecalho e derrubada do scaffold**

Substituir o conteudo integral de `database/migrations/2026_05_08_140000_create_cisternas_table.php`:

```php
<?php

declare(strict_types=1);

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo Cisterna — dominio portado do legado `sdc`.
 *
 * Consolidada (regra 9): esta migration substitui integralmente o scaffold
 * anterior, que criava uma tabela `cisternas` de dominio inventado
 * (codigo / capacidade_litros / tipo comunitaria|individual|escolar). O
 * legado e cadastro de beneficiario mais fiscalizacao de instalacao — ver
 * docs/superpowers/specs/2026-08-10-cisterna-migracao-legado-design.md.
 *
 * Colapsos em relacao ao legado:
 *  - as 3 tabelas de relatorio viram `cisterna_vistorias` + coluna `etapa`
 *  - ~87 colunas de checklist viram `cisterna_itens_conferidos` (polimorfica)
 *  - ~54 colunas de arquivo viram collections do Spatie MediaLibrary
 *  - `sinc_cisterna_relatorio` (89 campos, sem rota nem controller) e descartada
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->derrubarScaffold();

        $this->criarComunidades();
        $this->criarLotes();
        $this->criarOrdensServico();
        $this->criarBeneficiarios();
        $this->criarAtendimentosPipa();
        $this->criarVistorias();
        $this->criarItensConferidos();
        $this->criarNotificacoes();

        $this->criarSequenceDeNumeracao();
        $this->criarIndicesEspecificosDoPostgres();
        $this->criarCheckConstraints();
    }

    public function down(): void
    {
        // Ordem inversa das FKs.
        Schema::dropIfExists('cisterna_notificacoes');
        Schema::dropIfExists('cisterna_itens_conferidos');
        Schema::dropIfExists('cisterna_vistorias');
        Schema::dropIfExists('cisterna_atendimentos_pipa');
        Schema::dropIfExists('cisterna_beneficiarios');
        Schema::dropIfExists('cisterna_ordens_servico');
        Schema::dropIfExists('cisterna_lotes');
        Schema::dropIfExists('cisterna_comunidades');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP SEQUENCE IF EXISTS cisterna_numero_instalacao_seq');
        }
    }

    /**
     * A tabela do scaffold nunca recebeu dado real nem usuario: pode cair.
     */
    private function derrubarScaffold(): void
    {
        Schema::dropIfExists('cisternas');
    }
```

- [ ] **Step 4: Migration — comunidades, lotes e ordens de servico**

Continuar a mesma classe anonima:

```php
    private function criarComunidades(): void
    {
        Schema::create('cisterna_comunidades', function (Blueprint $table): void {
            $table->id();

            // Legado: sinc_cisterna_com guardava codmundv varchar(50) e o nome
            // do municipio duplicado. Aqui e FK de verdade.
            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnDelete();

            $table->string('nome', 70);
            $table->boolean('ativa')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_com.id, idempotencia do ETL');

            $table->timestampsTz();

            // Corrige o defeito C18: o legado contava por nome de comunidade
            // sem o municipio, somando homonimos de municipios distintos.
            $table->unique(['municipio_id', 'nome']);
            $table->unique('legacy_id');
        });
    }

    private function criarLotes(): void
    {
        Schema::create('cisterna_lotes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 255);
            $table->date('data')->nullable();
            $table->text('observacao')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_lotes.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
        });
    }

    private function criarOrdensServico(): void
    {
        Schema::create('cisterna_ordens_servico', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lote_id')
                ->constrained('cisterna_lotes')
                ->cascadeOnDelete();

            $table->string('nome', 255);
            $table->text('observacao')->nullable();

            // Legado: link_doc varchar. Agora e a collection documento_os
            // do MediaLibrary.
            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_ordem_servico.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
            $table->index('lote_id');
        });
    }
```

- [ ] **Step 5: Migration — beneficiarios**

```php
    private function criarBeneficiarios(): void
    {
        Schema::create('cisterna_beneficiarios', function (Blueprint $table): void {
            $table->id();

            // Identificacao. Legado: varchar(14) com mascara, unicidade
            // verificada por count() em PHP antes do insert (race condition).
            // O UNIQUE NAO fica aqui: e um indice PARCIAL criado em
            // criarIndicesEspecificosDoPostgres(), porque producao tem 492
            // CPFs repetidos, 485 deles marcados como Duplicado — o legado
            // usava esse status como tombstone. Ver spec secao 4.6.5.
            $table->char('cpf', 11);
            $table->string('nome', 150);
            $table->string('telefone', 15)->nullable();
            $table->date('data_nascimento')->nullable()
                ->comment('Beneficiario deve ser maior de 18 anos');
            $table->string('cadastro_unico', 12)->nullable();

            // Localizacao. Legado duplicava o nome do municipio e da
            // comunidade como texto em quatro tabelas.
            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnDelete();
            $table->foreignId('comunidade_id')->nullable()
                ->constrained('cisterna_comunidades')
                ->nullOnDelete();
            $table->string('endereco', 150)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Alocacao em lote. Legado: coluna os_id solta, sem FK.
            $table->foreignId('ordem_servico_id')->nullable()
                ->constrained('cisterna_ordens_servico')
                ->nullOnDelete();

            // Os dois ciclos de vida, ortogonais entre si.
            $table->string('situacao_analise', 20)
                ->default(SituacaoAnalise::EM_EDICAO->value)
                ->comment('Analise documental. Legado: coluna `aprovado` 0..5');
            $table->string('situacao_analise_obs', 255)->nullable();
            $table->string('situacao_obra', 20)
                ->default(SituacaoObra::PROCESSAMENTO->value)
                ->comment('Execucao fisica. Legado: coluna `estado` 0..2');

            // Populada fora do sistema: nao existe calculo de ranqueamento no
            // legado (a rota do relatorio aponta para metodo inexistente).
            $table->integer('ranqueamento_ordem')->nullable();

            // Criterios sociais.
            $table->smallInteger('qtd_pessoas')->nullable();
            $table->decimal('renda', 12, 2)->nullable();
            $table->decimal('renda_per_capita', 12, 2)->nullable();
            $table->boolean('possui_deficiencia')->nullable();
            $table->boolean('possui_crianca')->nullable();
            $table->date('data_nascimento_crianca')->nullable()
                ->comment('Crianca deve ter menos de 12 anos');
            $table->boolean('possui_idoso')->nullable();
            $table->boolean('chefiada_mulher')->nullable();

            // Avaliacao tecnica do imovel. CHECK de tipo_moradia e de
            // cobertura_telhado entram depois, quando os valores distintos
            // sairem do cisterna_legado_raw.
            $table->string('tipo_moradia', 30)->nullable();
            $table->string('tipo_moradia_outro', 50)->nullable();
            $table->decimal('comprimento_telhado', 8, 2)->nullable();
            $table->decimal('largura_telhado', 8, 2)->nullable();
            $table->decimal('area_telhado', 8, 2)->nullable();
            $table->decimal('comprimento_testada', 8, 2)->nullable();
            $table->smallInteger('num_caidas_telhado')->nullable();
            $table->string('cobertura_telhado', 30)->nullable();
            $table->string('cobertura_outro', 150)->nullable();
            $table->boolean('possui_fogao_lenha')->nullable();
            $table->decimal('medida_telhado_area_fogao', 8, 2)->nullable();
            $table->decimal('testada_disp_parte_fogao', 8, 2)->nullable();
            $table->boolean('atendido_por_pipa')->nullable();

            // Responsaveis pelo cadastro em campo.
            $table->string('agente_nome', 70)->nullable();
            $table->char('agente_cpf', 11)->nullable();
            $table->string('engenheiro_nome', 150)->nullable();
            $table->string('engenheiro_crea', 20)->nullable();

            $table->text('observacoes')->nullable();

            // Dono do registro, para a trilha de acoes do sino (Rastreavel).
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            // cpf: indice unico PARCIAL, ver criarIndicesEspecificosDoPostgres().
            $table->unique('legacy_id');
            $table->index(['municipio_id', 'situacao_analise']);
            $table->index('situacao_obra');
            $table->index('ordem_servico_id');
            $table->index('comunidade_id');
        });
    }

    private function criarAtendimentosPipa(): void
    {
        Schema::create('cisterna_atendimentos_pipa', function (Blueprint $table): void {
            $table->id();

            // cascadeOnDelete: os responsaveis so existem enquanto o
            // beneficiario existir, nao tem vida propria.
            $table->foreignId('beneficiario_id')
                ->constrained('cisterna_beneficiarios')
                ->cascadeOnDelete();

            $table->string('responsavel', 20)
                ->comment('Legado: as cinco colunas respAt* de sinc_cisterna');
            $table->string('descricao', 255)->nullable()
                ->comment('Legado: outroAtendPipa, usado quando responsavel = outros');

            $table->unique(['beneficiario_id', 'responsavel']);
        });
    }
```

- [ ] **Step 6: Migration — vistorias, itens conferidos e notificacoes**

```php
    private function criarVistorias(): void
    {
        Schema::create('cisterna_vistorias', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('beneficiario_id')
                ->constrained('cisterna_beneficiarios')
                ->cascadeOnDelete();

            $table->string('etapa', 20)
                ->comment('fornecedor -> compdec -> cedec');

            // Numero do QR Code. Legado: range(1,1800) hardcoded, escolhido
            // por array_diff contra todos os usados a cada abertura do
            // formulario (full scan + race condition).
            $table->integer('numero_instalacao')->nullable();

            $table->string('engenheiro_nome', 150)->nullable();
            $table->string('engenheiro_crea', 30)->nullable();
            $table->string('engenheiro_art', 50)->nullable();
            $table->date('data_relatorio')->nullable();
            $table->string('local_relatorio', 255)->nullable();

            // Somente etapa cedec.
            $table->string('processo_sei', 100)->nullable();
            $table->string('contrato', 100)->nullable();
            $table->string('empenho', 100)->nullable();
            $table->smallInteger('placa_obras')->nullable()
                ->comment('Legado valida required|int; semantica conferida na fase 0');

            // Snapshot do endereco no momento da vistoria: o cadastro do
            // beneficiario pode mudar depois.
            $table->string('endereco', 150)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('observacoes')->nullable();

            // Legado marcava a conclusao por `crea_mg` preenchido e diferente
            // de vazio, verificado com whereHas aninhado.
            $table->timestampTz('concluida_em')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // As tres tabelas de origem tem ids independentes: legacy_id
            // sozinho nao e unico, so o par com a etapa.
            $table->unsignedBigInteger('legacy_id')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['beneficiario_id', 'etapa']);
            $table->unique('numero_instalacao');
            $table->unique(['etapa', 'legacy_id']);
            $table->index(['etapa', 'concluida_em']);
        });
    }

    private function criarItensConferidos(): void
    {
        Schema::create('cisterna_itens_conferidos', function (Blueprint $table): void {
            $table->id();

            // Polimorfico: hoje aponta para cisterna_vistorias. Colapsa ~87
            // colunas espalhadas pelas 3 tabelas de relatorio do legado.
            $table->string('conferivel_type', 100);
            $table->unsignedBigInteger('conferivel_id');

            $table->string('item', 20);
            $table->boolean('conferido')->default(false);
            $table->decimal('quantidade', 10, 2)->nullable();
            $table->string('unidade', 5)->nullable();

            // Atributos que variam por item. Somente `fixacao` usa hoje:
            // {"abracadeira": "12", "bucha": "12", "parafuso": "24"} — o
            // legado tinha fix_abracadeira, fix_bucha e fix_parafuso como
            // colunas soltas do COMPDEC.
            $table->jsonb('detalhes')->nullable();

            $table->text('observacao')->nullable();

            $table->timestampsTz();

            $table->unique(['conferivel_type', 'conferivel_id', 'item'], 'cisterna_itens_conferivel_item_unq');
            $table->index(['conferivel_type', 'conferivel_id'], 'cisterna_itens_conferivel_idx');
        });
    }

    private function criarNotificacoes(): void
    {
        Schema::create('cisterna_notificacoes', function (Blueprint $table): void {
            $table->id();

            // Polimorfico: pode pender do beneficiario ou de uma vistoria.
            $table->string('notificavel_type', 100);
            $table->unsignedBigInteger('notificavel_id');

            $table->text('observacao');
            $table->boolean('respondida')->default(false);
            $table->timestampTz('respondida_em')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_notificacoes.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
            $table->index(
                ['notificavel_type', 'notificavel_id', 'respondida'],
                'cisterna_notif_notificavel_idx'
            );
        });
    }
```

- [ ] **Step 7: Migration — sequence, indices e CHECK constraints**

```php
    /**
     * Substitui o range(1,1800) hardcoded do legado. A alocacao passa a ser
     * atomica via nextval, com o UNIQUE da coluna como rede de seguranca.
     */
    private function criarSequenceDeNumeracao(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE SEQUENCE IF NOT EXISTS cisterna_numero_instalacao_seq START WITH 1 INCREMENT BY 1');
    }

    private function criarIndicesEspecificosDoPostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // CPF unico, mas PARCIAL. Producao tem 492 CPFs repetidos em 1.003
        // linhas; 485 dessas estao marcadas aprovado=5 (Duplicado) — o legado
        // nao impedia a duplicata, ele a marcava. Um UNIQUE puro rejeitaria
        // ~511 linhas legitimas no ETL.
        //
        // Parcial resolve os dois lados: preserva os tombstones importados e
        // impede cadastro NOVO duplicado. E o tipo de indice que o Postgres
        // tem e o MySQL do legado nao tinha como expressar.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS cisterna_beneficiarios_cpf_unq '
            .'ON cisterna_beneficiarios (cpf) '
            ."WHERE situacao_analise <> 'duplicado' AND deleted_at IS NULL"
        );

        // Busca por CPF parcial na listagem, que o unique parcial nao cobre.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_cpf_idx '
            .'ON cisterna_beneficiarios (cpf)'
        );

        // Substitui whereNotNull('ranqueamento_ordem') com full scan.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_ranqueamento_idx '
            .'ON cisterna_beneficiarios (ranqueamento_ordem) '
            .'WHERE ranqueamento_ordem IS NOT NULL'
        );

        // Substitui where('nome', 'like', '%termo%').
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_nome_trgm_idx '
            .'ON cisterna_beneficiarios USING gin (nome gin_trgm_ops)'
        );
    }

    private function criarCheckConstraints(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $checks = [
            ['cisterna_beneficiarios', 'situacao_analise', SituacaoAnalise::valores()],
            ['cisterna_beneficiarios', 'situacao_obra', SituacaoObra::valores()],
            ['cisterna_vistorias', 'etapa', EtapaVistoria::valores()],
            ['cisterna_itens_conferidos', 'item', ItemInstalacao::valores()],
            ['cisterna_atendimentos_pipa', 'responsavel', ResponsavelPipa::valores()],
        ];

        foreach ($checks as [$tabela, $coluna, $valores]) {
            $lista = implode(', ', array_map(fn (string $v): string => "'{$v}'", $valores));

            DB::statement(
                "ALTER TABLE {$tabela} ADD CONSTRAINT {$tabela}_{$coluna}_check "
                ."CHECK ({$coluna} IN ({$lista}))"
            );
        }

        // unidade e nullable: o CHECK precisa tolerar NULL.
        $unidades = implode(', ', array_map(
            fn (string $v): string => "'{$v}'",
            UnidadeItem::valores()
        ));

        DB::statement(
            'ALTER TABLE cisterna_itens_conferidos ADD CONSTRAINT cisterna_itens_conferidos_unidade_check '
            ."CHECK (unidade IS NULL OR unidade IN ({$unidades}))"
        );
    }
};
```

- [ ] **Step 8: Rodar a migration**

Run: `$PHP artisan migrate:fresh --seed`
Expected: termina sem erro. Se reclamar de `cisternas` sendo referenciada por FK, conferir que nada mais no projeto aponta para ela — a Task 5 remove o registro da policy e a Task 17 os assets.

- [ ] **Step 9: Rodar o teste e confirmar que passa**

Run: `scripts/test-host.sh --filter=SchemaCisternaTest`
Expected: PASS, 10 testes.

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_05_08_140000_create_cisternas_table.php \
        tests/Feature/Cisterna/SchemaCisternaTest.php
git commit -m "🗃️ db(cisterna): consolida migration com as 8 tabelas do dominio do legado"
```

---

### Task 4: Models e factories

Oito models numa task so: separar por model daria oito revisoes de arquivo trivial, e um revisor nao consegue aprovar `CisternaLote` rejeitando `CisternaOrdemServico` — eles se referenciam.

**Files:**
- Create: `app/Modules/Cisterna/Models/CisternaBeneficiario.php`
- Create: `app/Modules/Cisterna/Models/CisternaVistoria.php`
- Create: `app/Modules/Cisterna/Models/CisternaItemConferido.php`
- Create: `app/Modules/Cisterna/Models/CisternaComunidade.php`
- Create: `app/Modules/Cisterna/Models/CisternaLote.php`
- Create: `app/Modules/Cisterna/Models/CisternaOrdemServico.php`
- Create: `app/Modules/Cisterna/Models/CisternaNotificacao.php`
- Create: `app/Modules/Cisterna/Models/CisternaAtendimentoPipa.php`
- Create: `database/factories/Cisterna/CisternaBeneficiarioFactory.php`
- Create: `database/factories/Cisterna/CisternaComunidadeFactory.php`
- Create: `database/factories/Cisterna/CisternaLoteFactory.php`
- Create: `database/factories/Cisterna/CisternaOrdemServicoFactory.php`
- Create: `database/factories/Cisterna/CisternaVistoriaFactory.php`
- Delete: `app/Modules/Cisterna/Models/Cisterna.php`
- Delete: `database/factories/CisternaFactory.php`
- Delete: `app/Modules/Cisterna/DTOs/CisternaDTO.php`
- Delete: `app/Modules/Cisterna/Services/CisternaService.php`
- Delete: `app/Modules/Cisterna/Controllers/CisternaController.php`
- Delete: `app/Modules/Cisterna/Resources/CisternaResource.php`
- Delete: `app/Modules/Cisterna/Resources/CisternaIndexResource.php`
- Delete: `app/Modules/Cisterna/Requests/StoreCisternaRequest.php`
- Delete: `app/Modules/Cisterna/Requests/UpdateCisternaRequest.php`
- Test: `tests/Feature/Cisterna/ModelsCisternaTest.php`

**Interfaces:**
- Consumes: as tabelas da Task 3, os enums da Task 2
- Produces:
  - `CisternaBeneficiario` com `municipio()`, `comunidade()`, `ordemServico()`, `vistorias()`, `vistoriaDaEtapa(EtapaVistoria): ?CisternaVistoria`, `atendimentosPipa()`, `notificacoes()`, `criador()`, scopes `doMunicipio(int)`, `comSituacaoObra(array)`, `buscarPorNome(?string)`, `ranqueados()`
  - `CisternaVistoria` com `beneficiario()`, `itensConferidos()`, `notificacoes()`, `itemDe(ItemInstalacao): ?CisternaItemConferido`, `estaConcluida(): bool`, scope `daEtapa(EtapaVistoria)`
  - `CisternaItemConferido` com `conferivel()`
  - `CisternaComunidade` com `municipio()`, `beneficiarios()`
  - `CisternaLote` com `ordensServico()`
  - `CisternaOrdemServico` com `lote()`, `beneficiarios()`
  - `CisternaNotificacao` com `notificavel()`, scope `pendentes()`
  - `CisternaAtendimentoPipa` com `beneficiario()`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/ModelsCisternaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaItemConferido;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModelsCisternaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_cria_beneficiario_com_enums_convertidos(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->assertInstanceOf(SituacaoAnalise::class, $beneficiario->situacao_analise);
        $this->assertInstanceOf(SituacaoObra::class, $beneficiario->situacao_obra);
        $this->assertSame(11, strlen($beneficiario->cpf));
    }

    public function test_beneficiario_pertence_a_municipio_e_comunidade(): void
    {
        $comunidade = CisternaComunidade::factory()->create();
        $beneficiario = CisternaBeneficiario::factory()->create([
            'municipio_id' => $comunidade->municipio_id,
            'comunidade_id' => $comunidade->id,
        ]);

        $this->assertSame($comunidade->municipio_id, $beneficiario->municipio->id);
        $this->assertSame($comunidade->id, $beneficiario->comunidade->id);
    }

    public function test_lote_agrega_ordens_de_servico_e_a_os_agrega_beneficiarios(): void
    {
        $lote = CisternaLote::factory()->create();
        $os = CisternaOrdemServico::factory()->create(['lote_id' => $lote->id]);
        CisternaBeneficiario::factory()->count(3)->create(['ordem_servico_id' => $os->id]);

        $this->assertCount(1, $lote->ordensServico);
        $this->assertCount(3, $os->beneficiarios);
        $this->assertSame($lote->id, $os->lote->id);
    }

    public function test_vistoria_da_etapa_devolve_a_linha_correta(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::FORNECEDOR->value,
        ]);
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
            'numero_instalacao' => null,
        ]);

        $this->assertSame(
            EtapaVistoria::COMPDEC,
            $beneficiario->vistoriaDaEtapa(EtapaVistoria::COMPDEC)->etapa
        );
        $this->assertNull($beneficiario->vistoriaDaEtapa(EtapaVistoria::CEDEC));
    }

    public function test_itens_conferidos_sao_polimorficos_e_guardam_detalhes_em_jsonb(): void
    {
        $vistoria = CisternaVistoria::factory()->create();

        CisternaItemConferido::create([
            'conferivel_type' => CisternaVistoria::class,
            'conferivel_id' => $vistoria->id,
            'item' => ItemInstalacao::FIXACAO->value,
            'conferido' => true,
            'detalhes' => ['abracadeira' => '12', 'bucha' => '12', 'parafuso' => '24'],
        ]);

        CisternaItemConferido::create([
            'conferivel_type' => CisternaVistoria::class,
            'conferivel_id' => $vistoria->id,
            'item' => ItemInstalacao::CALHA->value,
            'conferido' => true,
            'quantidade' => 12.5,
            'unidade' => UnidadeItem::M->value,
        ]);

        $vistoria->refresh();

        $this->assertCount(2, $vistoria->itensConferidos);

        $fixacao = $vistoria->itemDe(ItemInstalacao::FIXACAO);
        $this->assertSame('24', $fixacao->detalhes['parafuso']);
        $this->assertInstanceOf(CisternaVistoria::class, $fixacao->conferivel);

        $calha = $vistoria->itemDe(ItemInstalacao::CALHA);
        $this->assertSame('12.50', $calha->quantidade);
        $this->assertSame(UnidadeItem::M, $calha->unidade);
    }

    public function test_notificacao_pende_de_beneficiario_ou_de_vistoria(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        $vistoria = CisternaVistoria::factory()->create(['beneficiario_id' => $beneficiario->id]);

        $doBeneficiario = CisternaNotificacao::create([
            'notificavel_type' => CisternaBeneficiario::class,
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Pendencia no cadastro',
        ]);

        $daVistoria = CisternaNotificacao::create([
            'notificavel_type' => CisternaVistoria::class,
            'notificavel_id' => $vistoria->id,
            'observacao' => 'Foto ilegivel',
            'respondida' => true,
        ]);

        $this->assertInstanceOf(CisternaBeneficiario::class, $doBeneficiario->notificavel);
        $this->assertInstanceOf(CisternaVistoria::class, $daVistoria->notificavel);
        $this->assertCount(1, $beneficiario->notificacoes);
        $this->assertCount(1, CisternaNotificacao::pendentes()->whereKey($doBeneficiario->id)->get());
        $this->assertCount(0, CisternaNotificacao::pendentes()->whereKey($daVistoria->id)->get());
    }

    public function test_estaConcluida_reflete_concluida_em(): void
    {
        $aberta = CisternaVistoria::factory()->create(['concluida_em' => null]);
        $fechada = CisternaVistoria::factory()->create(['concluida_em' => now()]);

        $this->assertFalse($aberta->estaConcluida());
        $this->assertTrue($fechada->estaConcluida());
    }

    public function test_scope_buscarPorNome_usa_ilike(): void
    {
        CisternaBeneficiario::factory()->create(['nome' => 'Maria das Gracas Souza']);
        CisternaBeneficiario::factory()->create(['nome' => 'Joao Pedro Alves']);

        $encontrados = CisternaBeneficiario::query()->buscarPorNome('gracas')->get();

        $this->assertCount(1, $encontrados);
        $this->assertSame('Maria das Gracas Souza', $encontrados->first()->nome);
    }

    public function test_scope_ranqueados_ignora_quem_nao_tem_ordem(): void
    {
        CisternaBeneficiario::factory()->create(['ranqueamento_ordem' => null]);
        CisternaBeneficiario::factory()->create(['ranqueamento_ordem' => 20]);
        CisternaBeneficiario::factory()->create(['ranqueamento_ordem' => 10]);

        $ranqueados = CisternaBeneficiario::query()->ranqueados()->get();

        $this->assertCount(2, $ranqueados);
        $this->assertSame(10, $ranqueados->first()->ranqueamento_ordem);
    }

    public function test_beneficiario_registra_o_criador_automaticamente(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $beneficiario = CisternaBeneficiario::factory()->create(['created_by' => null]);

        $this->assertSame($user->id, $beneficiario->created_by);
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

Run: `scripts/test-host.sh --filter=ModelsCisternaTest`
Expected: FAIL com `Class "App\Modules\Cisterna\Models\CisternaBeneficiario" not found`.

- [ ] **Step 3: Escrever `CisternaBeneficiario`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaBeneficiarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Beneficiario do Projeto Cisterna. Porte de `sinc_cisterna` do legado `sdc`,
 * onde as 54 colunas eram todas varchar(150) — datas, moeda, medidas e
 * booleanos inclusive.
 *
 * @property int             $id
 * @property string          $cpf
 * @property string          $nome
 * @property SituacaoAnalise $situacao_analise
 * @property SituacaoObra    $situacao_obra
 * @property ?int            $ranqueamento_ordem
 * @property ?int            $legacy_id
 */
class CisternaBeneficiario extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_beneficiarios';

    protected $fillable = [
        'cpf', 'nome', 'telefone', 'data_nascimento', 'cadastro_unico',
        'municipio_id', 'comunidade_id', 'endereco', 'latitude', 'longitude',
        'ordem_servico_id',
        'situacao_analise', 'situacao_analise_obs', 'situacao_obra',
        'ranqueamento_ordem',
        'qtd_pessoas', 'renda', 'renda_per_capita',
        'possui_deficiencia', 'possui_crianca', 'data_nascimento_crianca',
        'possui_idoso', 'chefiada_mulher',
        'tipo_moradia', 'tipo_moradia_outro',
        'comprimento_telhado', 'largura_telhado', 'area_telhado', 'comprimento_testada',
        'num_caidas_telhado', 'cobertura_telhado', 'cobertura_outro',
        'possui_fogao_lenha', 'medida_telhado_area_fogao', 'testada_disp_parte_fogao',
        'atendido_por_pipa',
        'agente_nome', 'agente_cpf', 'engenheiro_nome', 'engenheiro_crea',
        'observacoes', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'situacao_analise' => SituacaoAnalise::class,
        'situacao_obra' => SituacaoObra::class,
        'data_nascimento' => 'date',
        'data_nascimento_crianca' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'renda' => 'decimal:2',
        'renda_per_capita' => 'decimal:2',
        'comprimento_telhado' => 'decimal:2',
        'largura_telhado' => 'decimal:2',
        'area_telhado' => 'decimal:2',
        'comprimento_testada' => 'decimal:2',
        'medida_telhado_area_fogao' => 'decimal:2',
        'testada_disp_parte_fogao' => 'decimal:2',
        'qtd_pessoas' => 'integer',
        'num_caidas_telhado' => 'integer',
        'ranqueamento_ordem' => 'integer',
        'possui_deficiencia' => 'boolean',
        'possui_crianca' => 'boolean',
        'possui_idoso' => 'boolean',
        'chefiada_mulher' => 'boolean',
        'possui_fogao_lenha' => 'boolean',
        'atendido_por_pipa' => 'boolean',
        'legacy_id' => 'integer',
    ];

    /* Relacoes */

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function comunidade(): BelongsTo
    {
        return $this->belongsTo(CisternaComunidade::class, 'comunidade_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(CisternaOrdemServico::class, 'ordem_servico_id');
    }

    public function vistorias(): HasMany
    {
        return $this->hasMany(CisternaVistoria::class, 'beneficiario_id');
    }

    public function atendimentosPipa(): HasMany
    {
        return $this->hasMany(CisternaAtendimentoPipa::class, 'beneficiario_id');
    }

    public function notificacoes(): MorphMany
    {
        return $this->morphMany(CisternaNotificacao::class, 'notificavel');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Substitui os tres whereHas aninhados do legado
     * (CisternaController.php:441-457) por lookup direto no par unico
     * (beneficiario_id, etapa).
     */
    public function vistoriaDaEtapa(EtapaVistoria $etapa): ?CisternaVistoria
    {
        if ($this->relationLoaded('vistorias')) {
            return $this->vistorias->firstWhere('etapa', $etapa);
        }

        return $this->vistorias()->where('etapa', $etapa->value)->first();
    }

    /* Scopes */

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * @param  array<int, string>  $situacoes
     */
    public function scopeComSituacaoObra(Builder $query, array $situacoes): Builder
    {
        return $query->whereIn('situacao_obra', $situacoes);
    }

    /**
     * Apoiado no indice GIN pg_trgm de `nome`, em vez do like '%x%' do legado.
     */
    public function scopeBuscarPorNome(Builder $query, ?string $termo): Builder
    {
        if ($termo === null || trim($termo) === '') {
            return $query;
        }

        return $query->where('nome', 'ilike', '%'.trim($termo).'%');
    }

    /**
     * Apoiado no indice parcial de ranqueamento_ordem.
     */
    public function scopeRanqueados(Builder $query): Builder
    {
        return $query->whereNotNull('ranqueamento_ordem')->orderBy('ranqueamento_ordem');
    }

    /* MediaLibrary */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fotos_imovel');
        $this->addMediaCollection('comprovantes');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(320)->height(320)->nonQueued();
    }

    /* Boot */

    protected static function newFactory(): CisternaBeneficiarioFactory
    {
        return CisternaBeneficiarioFactory::new();
    }

    /**
     * Mesmo padrao de Decretacoes\Processo: o dono e quem cadastrou. Sem isso
     * a coluna nao se preenche e a trilha nao acha destinatario. Nao
     * sobrescreve valor que ja veio (refino do ETL, seeder).
     */
    protected static function booted(): void
    {
        static::creating(function (self $beneficiario): void {
            if ($beneficiario->created_by === null && Auth::id() !== null) {
                $beneficiario->created_by = Auth::id();
            }
        });
    }

    /* Trilha de acoes */

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        $nome = trim($this->nome);

        return $nome !== '' ? 'Cisterna de '.$nome : 'Cisterna #'.$this->getKey();
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    public function urlNotificacao(): ?string
    {
        return '/cisternas/beneficiarios/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'situacao_analise';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->situacao_analise instanceof SituacaoAnalise
            ? $this->situacao_analise->label()
            : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return match ($this->situacao_analise) {
            SituacaoAnalise::APROVADO => 'success',
            SituacaoAnalise::REPROVADO, SituacaoAnalise::DUPLICADO => 'danger',
            SituacaoAnalise::RESSALVA => 'warning',
            default => 'info',
        };
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Chave de idempotencia do ETL, nao dado do cadastro.
            'legacy_id',
        ]);
    }
}
```

- [ ] **Step 4: Escrever `CisternaVistoria`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaVistoriaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Uma etapa da cadeia de vistoria. Colapsa as tres tabelas do legado:
 * sinc_cisterna_rel_fornecedor, sinc_cisterna_rel_compdec e
 * sinc_cisterna_rel_cedec.
 *
 * @property int            $id
 * @property EtapaVistoria  $etapa
 * @property ?int           $numero_instalacao
 */
class CisternaVistoria extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_vistorias';

    protected $fillable = [
        'beneficiario_id', 'etapa', 'numero_instalacao',
        'engenheiro_nome', 'engenheiro_crea', 'engenheiro_art',
        'data_relatorio', 'local_relatorio',
        'processo_sei', 'contrato', 'empenho', 'placa_obras',
        'endereco', 'bairro', 'latitude', 'longitude',
        'observacoes', 'concluida_em', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'etapa' => EtapaVistoria::class,
        'numero_instalacao' => 'integer',
        'placa_obras' => 'integer',
        'data_relatorio' => 'date',
        'concluida_em' => 'immutable_datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'legacy_id' => 'integer',
    ];

    /* Relacoes */

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(CisternaBeneficiario::class, 'beneficiario_id');
    }

    public function itensConferidos(): MorphMany
    {
        return $this->morphMany(CisternaItemConferido::class, 'conferivel');
    }

    public function notificacoes(): MorphMany
    {
        return $this->morphMany(CisternaNotificacao::class, 'notificavel');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function itemDe(ItemInstalacao $item): ?CisternaItemConferido
    {
        if ($this->relationLoaded('itensConferidos')) {
            return $this->itensConferidos->firstWhere('item', $item);
        }

        return $this->itensConferidos()->where('item', $item->value)->first();
    }

    /**
     * Legado marcava conclusao por `crea_mg` preenchido e diferente de vazio.
     */
    public function estaConcluida(): bool
    {
        return $this->concluida_em !== null;
    }

    /* Scopes */

    public function scopeDaEtapa(Builder $query, EtapaVistoria $etapa): Builder
    {
        return $query->where('etapa', $etapa->value);
    }

    public function scopeConcluidas(Builder $query): Builder
    {
        return $query->whereNotNull('concluida_em');
    }

    /* MediaLibrary */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fotos_vistoria');
        $this->addMediaCollection('assinatura_engenheiro')->singleFile();
    }

    protected static function newFactory(): CisternaVistoriaFactory
    {
        return CisternaVistoriaFactory::new();
    }

    /* Trilha de acoes */

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        $etapa = $this->etapa instanceof EtapaVistoria ? $this->etapa->label() : 'Vistoria';

        return $this->numero_instalacao !== null
            ? $etapa.' — instalacao '.$this->numero_instalacao
            : $etapa.' #'.$this->getKey();
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    public function urlNotificacao(): ?string
    {
        return '/cisternas/vistorias/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'etapa';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->etapa instanceof EtapaVistoria ? $this->etapa->label() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->estaConcluida() ? 'success' : 'info';
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), ['legacy_id']);
    }
}
```

- [ ] **Step 5: Escrever os seis models restantes**

`CisternaItemConferido.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\UnidadeItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Checklist polimorfico. Colapsa ~87 colunas repetidas nas tres tabelas de
 * relatorio do legado, com nomes divergentes entre elas.
 */
class CisternaItemConferido extends Model
{
    protected $table = 'cisterna_itens_conferidos';

    protected $fillable = [
        'conferivel_type', 'conferivel_id',
        'item', 'conferido', 'quantidade', 'unidade', 'detalhes', 'observacao',
    ];

    protected $casts = [
        'item' => ItemInstalacao::class,
        'unidade' => UnidadeItem::class,
        'conferido' => 'boolean',
        'quantidade' => 'decimal:2',
        // Somente `fixacao` usa hoje: abracadeira, bucha, parafuso.
        'detalhes' => 'array',
    ];

    public function conferivel(): MorphTo
    {
        return $this->morphTo();
    }
}
```

`CisternaComunidade.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use Database\Factories\Cisterna\CisternaComunidadeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CisternaComunidade extends Model
{
    use HasFactory;

    protected $table = 'cisterna_comunidades';

    protected $fillable = ['municipio_id', 'nome', 'ativa', 'legacy_id'];

    protected $casts = [
        'ativa' => 'boolean',
        'legacy_id' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function beneficiarios(): HasMany
    {
        return $this->hasMany(CisternaBeneficiario::class, 'comunidade_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    protected static function newFactory(): CisternaComunidadeFactory
    {
        return CisternaComunidadeFactory::new();
    }
}
```

`CisternaLote.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use Database\Factories\Cisterna\CisternaLoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CisternaLote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cisterna_lotes';

    protected $fillable = ['nome', 'data', 'observacao', 'legacy_id'];

    protected $casts = [
        'data' => 'date',
        'legacy_id' => 'integer',
    ];

    public function ordensServico(): HasMany
    {
        return $this->hasMany(CisternaOrdemServico::class, 'lote_id');
    }

    protected static function newFactory(): CisternaLoteFactory
    {
        return CisternaLoteFactory::new();
    }
}
```

`CisternaOrdemServico.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaOrdemServicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CisternaOrdemServico extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_ordens_servico';

    protected $fillable = ['lote_id', 'nome', 'observacao', 'legacy_id'];

    protected $casts = ['legacy_id' => 'integer'];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(CisternaLote::class, 'lote_id');
    }

    public function beneficiarios(): HasMany
    {
        return $this->hasMany(CisternaBeneficiario::class, 'ordem_servico_id');
    }

    public function registerMediaCollections(): void
    {
        // Legado: coluna link_doc.
        $this->addMediaCollection('documento_os')->singleFile();
    }

    protected static function newFactory(): CisternaOrdemServicoFactory
    {
        return CisternaOrdemServicoFactory::new();
    }
}
```

`CisternaNotificacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Notificacao de fiscalizacao. Polimorfica: pende do beneficiario ou de uma
 * vistoria especifica. No legado so existia por cisterna_id, e o disparo era
 * um Mail::send para um Gmail pessoal hardcoded.
 */
class CisternaNotificacao extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'cisterna_notificacoes';

    protected $fillable = [
        'notificavel_type', 'notificavel_id',
        'observacao', 'respondida', 'respondida_em', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'respondida' => 'boolean',
        'respondida_em' => 'immutable_datetime',
        'legacy_id' => 'integer',
    ];

    public function notificavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('respondida', false);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documentos');
    }
}
```

`CisternaAtendimentoPipa.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CisternaAtendimentoPipa extends Model
{
    public $timestamps = false;

    protected $table = 'cisterna_atendimentos_pipa';

    protected $fillable = ['beneficiario_id', 'responsavel', 'descricao'];

    protected $casts = ['responsavel' => ResponsavelPipa::class];

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(CisternaBeneficiario::class, 'beneficiario_id');
    }
}
```

- [ ] **Step 6: Escrever as cinco factories**

`database/factories/Cisterna/CisternaComunidadeFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaComunidade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<CisternaComunidade>
 */
class CisternaComunidadeFactory extends Factory
{
    protected $model = CisternaComunidade::class;

    public function definition(): array
    {
        return [
            // O banco de dev/teste ja tem os 853 municipios de MG seedados.
            'municipio_id' => DB::table('municipios')->inRandomOrder()->value('id'),
            'nome' => 'Comunidade '.$this->faker->unique()->lastName(),
            'ativa' => true,
            'legacy_id' => null,
        ];
    }
}
```

`database/factories/Cisterna/CisternaBeneficiarioFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<CisternaBeneficiario>
 */
class CisternaBeneficiarioFactory extends Factory
{
    protected $model = CisternaBeneficiario::class;

    public function definition(): array
    {
        $renda = $this->faker->randomFloat(2, 200, 3000);
        $pessoas = $this->faker->numberBetween(1, 8);
        $comprimento = $this->faker->randomFloat(2, 4, 20);
        $largura = $this->faker->randomFloat(2, 3, 12);

        return [
            // CPF sem mascara, 11 digitos, unico.
            'cpf' => $this->faker->unique()->numerify('###########'),
            'nome' => $this->faker->name(),
            'telefone' => $this->faker->numerify('(31) 9####-####'),
            // Maior de 18 anos, conforme a validacao do legado.
            'data_nascimento' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
            'cadastro_unico' => $this->faker->numerify('############'),
            'municipio_id' => DB::table('municipios')->inRandomOrder()->value('id'),
            'comunidade_id' => null,
            'endereco' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'ordem_servico_id' => null,
            'situacao_analise' => SituacaoAnalise::EM_EDICAO->value,
            'situacao_obra' => SituacaoObra::PROCESSAMENTO->value,
            'ranqueamento_ordem' => null,
            'qtd_pessoas' => $pessoas,
            'renda' => $renda,
            'renda_per_capita' => round($renda / $pessoas, 2),
            'possui_deficiencia' => false,
            'possui_crianca' => false,
            'data_nascimento_crianca' => null,
            'possui_idoso' => false,
            'chefiada_mulher' => false,
            'tipo_moradia' => 'alvenaria',
            'comprimento_telhado' => $comprimento,
            'largura_telhado' => $largura,
            'area_telhado' => round($comprimento * $largura, 2),
            'comprimento_testada' => $comprimento,
            'num_caidas_telhado' => $this->faker->numberBetween(1, 4),
            'cobertura_telhado' => 'telha ceramica',
            'possui_fogao_lenha' => false,
            'atendido_por_pipa' => false,
            'agente_nome' => $this->faker->name(),
            'agente_cpf' => $this->faker->numerify('###########'),
            'engenheiro_nome' => $this->faker->name(),
            'engenheiro_crea' => 'MG-'.$this->faker->numerify('######'),
            'observacoes' => null,
            'legacy_id' => null,
        ];
    }

    public function aprovado(): static
    {
        return $this->state(fn (): array => [
            'situacao_analise' => SituacaoAnalise::APROVADO->value,
        ]);
    }

    public function instalado(): static
    {
        return $this->state(fn (): array => [
            'situacao_analise' => SituacaoAnalise::APROVADO->value,
            'situacao_obra' => SituacaoObra::INSTALADO->value,
        ]);
    }
}
```

`database/factories/Cisterna/CisternaLoteFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaLote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaLote>
 */
class CisternaLoteFactory extends Factory
{
    protected $model = CisternaLote::class;

    public function definition(): array
    {
        return [
            'nome' => 'Lote '.$this->faker->unique()->numerify('###/2026'),
            'data' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'observacao' => null,
            'legacy_id' => null,
        ];
    }
}
```

`database/factories/Cisterna/CisternaOrdemServicoFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaOrdemServico>
 */
class CisternaOrdemServicoFactory extends Factory
{
    protected $model = CisternaOrdemServico::class;

    public function definition(): array
    {
        return [
            'lote_id' => CisternaLote::factory(),
            'nome' => 'OS '.$this->faker->unique()->numerify('####'),
            'observacao' => null,
            'legacy_id' => null,
        ];
    }
}
```

`database/factories/Cisterna/CisternaVistoriaFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaVistoria>
 */
class CisternaVistoriaFactory extends Factory
{
    protected $model = CisternaVistoria::class;

    public function definition(): array
    {
        return [
            'beneficiario_id' => CisternaBeneficiario::factory(),
            'etapa' => EtapaVistoria::FORNECEDOR->value,
            'numero_instalacao' => $this->faker->unique()->numberBetween(1, 999999),
            'engenheiro_nome' => $this->faker->name(),
            'engenheiro_crea' => 'MG-'.$this->faker->numerify('######'),
            'engenheiro_art' => null,
            'data_relatorio' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'local_relatorio' => $this->faker->city(),
            'processo_sei' => null,
            'contrato' => null,
            'empenho' => null,
            'placa_obras' => null,
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->citySuffix(),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'observacoes' => null,
            'concluida_em' => null,
            'legacy_id' => null,
        ];
    }

    public function daEtapa(EtapaVistoria $etapa): static
    {
        return $this->state(fn (): array => [
            'etapa' => $etapa->value,
            // Somente a etapa do fornecedor aloca numero de instalacao.
            'numero_instalacao' => $etapa === EtapaVistoria::FORNECEDOR
                ? $this->faker->unique()->numberBetween(1, 999999)
                : null,
            'processo_sei' => $etapa === EtapaVistoria::CEDEC ? 'SEI-'.$this->faker->numerify('######') : null,
            'contrato' => $etapa === EtapaVistoria::CEDEC ? $this->faker->numerify('####/2026') : null,
            'empenho' => $etapa === EtapaVistoria::CEDEC ? $this->faker->numerify('######') : null,
            'placa_obras' => $etapa === EtapaVistoria::CEDEC ? 1 : null,
        ]);
    }

    public function concluida(): static
    {
        return $this->state(fn (): array => ['concluida_em' => now()]);
    }
}
```

- [ ] **Step 7: Remover os artefatos do scaffold que dependiam do model antigo**

```bash
git rm app/Modules/Cisterna/Models/Cisterna.php \
       app/Modules/Cisterna/DTOs/CisternaDTO.php \
       app/Modules/Cisterna/Services/CisternaService.php \
       app/Modules/Cisterna/Controllers/CisternaController.php \
       app/Modules/Cisterna/Resources/CisternaResource.php \
       app/Modules/Cisterna/Resources/CisternaIndexResource.php \
       app/Modules/Cisterna/Requests/StoreCisternaRequest.php \
       app/Modules/Cisterna/Requests/UpdateCisternaRequest.php \
       database/factories/CisternaFactory.php
```

`routes/modules/cisterna.php` e `app/Providers/AuthServiceProvider.php` ainda referenciam o model removido. Comentar o `require` da rota em `routes/web.php:157-158` **temporariamente** e remover a linha 27 do `AuthServiceProvider` (o mapeamento `Cisterna::class => CisternaPolicy::class`) para a suite voltar a rodar. A Task 5 reescreve os dois em definitivo.

- [ ] **Step 8: Rodar o teste e confirmar que passa**

Run: `scripts/test-host.sh --filter=ModelsCisternaTest`
Expected: PASS, 10 testes.

- [ ] **Step 9: Confirmar que nada mais referencia o scaffold**

Run: `grep -rn "TipoCisterna\|StatusCisterna\|Cisterna\\\\Models\\\\Cisterna\b\|CisternaDTO" app config database routes`
Expected: nenhuma saida. Se houver, corrigir antes de commitar.

- [ ] **Step 10: Commit**

```bash
git add app/Modules/Cisterna/Models database/factories/Cisterna \
        tests/Feature/Cisterna/ModelsCisternaTest.php \
        app/Providers/AuthServiceProvider.php routes/web.php
git commit -m "✨ feat(cisterna): models e factories do dominio, remove o scaffold"
```

---

### Task 5: Municipios habilitados, disco do legado e conexao MySQL

Tres itens de configuracao que a Fase 2 e a Fase 3 consomem. Resolve as lacunas L3 e L7 do spec.

**Files:**
- Modify: `app/Models/Municipio.php` (acrescentar scope, depois de `catalogo()`)
- Modify: `config/filesystems.php` (novo disco, depois de `legado_rat`)
- Modify: `config/database.php` (nova conexao)
- Modify: `.env.example`
- Test: `tests/Feature/Cisterna/MunicipiosHabilitadosTest.php`

**Interfaces:**
- Consumes: tabela `cedec_municipio` (migration `2026_03_03_000001`), tabela `municipios`
- Produces:
  - `Municipio::habilitadosCisterna(): Collection` — colecao de `['id' => int, 'nome' => string, 'uf' => string]` ordenada por nome
  - `Municipio::idsHabilitadosCisterna(): array<int, int>`
  - `Municipio::esquecerHabilitadosCisterna(): void`
  - disco `legado_cisterna` em `Storage::disk('legado_cisterna')`
  - conexao `legado_cisterna_mysql` em `DB::connection('legado_cisterna_mysql')`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/MunicipiosHabilitadosTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\Municipio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MunicipiosHabilitadosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Municipio::esquecerHabilitadosCisterna();
    }

    public function test_lista_apenas_municipios_com_at_cisterna_marcado(): void
    {
        // Codigos IBGE ficticios: o banco de dev/teste ja tem os 853
        // municipios reais seedados e codigo_ibge e unique.
        $habilitado = Municipio::firstOrCreate(
            ['codigo_ibge' => '9999801'],
            ['nome' => 'Municipio Habilitado', 'uf' => 'MG']
        );
        $naoHabilitado = Municipio::firstOrCreate(
            ['codigo_ibge' => '9999802'],
            ['nome' => 'Municipio Nao Habilitado', 'uf' => 'MG']
        );

        $this->inserirCedec('Municipio Habilitado', '9999801', 1);
        $this->inserirCedec('Municipio Nao Habilitado', '9999802', 0);

        Municipio::esquecerHabilitadosCisterna();
        $ids = Municipio::idsHabilitadosCisterna();

        $this->assertContains($habilitado->id, $ids);
        $this->assertNotContains($naoHabilitado->id, $ids);
    }

    public function test_devolve_id_nome_e_uf_ordenados_por_nome(): void
    {
        Municipio::firstOrCreate(['codigo_ibge' => '9999803'], ['nome' => 'Zebu', 'uf' => 'MG']);
        Municipio::firstOrCreate(['codigo_ibge' => '9999804'], ['nome' => 'Abadia Teste', 'uf' => 'MG']);

        $this->inserirCedec('Zebu', '9999803', 1);
        $this->inserirCedec('Abadia Teste', '9999804', 1);

        Municipio::esquecerHabilitadosCisterna();
        $lista = Municipio::habilitadosCisterna();

        $primeiro = $lista->first();
        $this->assertArrayHasKey('id', $primeiro);
        $this->assertArrayHasKey('nome', $primeiro);
        $this->assertArrayHasKey('uf', $primeiro);

        $nomes = $lista->pluck('nome')->all();
        $this->assertLessThan(
            array_search('Zebu', $nomes, true),
            array_search('Abadia Teste', $nomes, true)
        );
    }

    public function test_municipio_habilitado_sem_correspondencia_ibge_nao_entra(): void
    {
        $this->inserirCedec('Municipio Orfao', '0000000', 1);

        Municipio::esquecerHabilitadosCisterna();

        $this->assertFalse(
            Municipio::habilitadosCisterna()->contains('nome', 'Municipio Orfao')
        );
    }

    public function test_disco_do_legado_esta_registrado(): void
    {
        $this->assertNotNull(config('filesystems.disks.legado_cisterna'));
        $this->assertSame('local', config('filesystems.disks.legado_cisterna.driver'));
        $this->assertSame('private', config('filesystems.disks.legado_cisterna.visibility'));
    }

    public function test_conexao_do_legado_esta_registrada(): void
    {
        $this->assertSame('mysql', config('database.connections.legado_cisterna_mysql.driver'));
    }

    /**
     * A sequence de cedec_municipio esta dessincronizada no Postgres de dev: as
     * 854 linhas vieram do import do legado com id explicito, entao um insert
     * sem id estoura cedec_municipio_pkey com "duplicate key (id)=(3)".
     *
     * Derivar o id de max(id) contorna sem depender de setval, que exigiria
     * permissao de DDL no banco de teste.
     */
    private function inserirCedec(string $nome, string $codmundv, int $atCisterna): int
    {
        $id = ((int) DB::table('cedec_municipio')->max('id')) + 1;

        DB::table('cedec_municipio')->insert([
            'id' => $id,
            'nome' => $nome,
            'Codmundv' => $codmundv,
            'at_cisterna' => $atCisterna,
        ]);

        return $id;
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

Run: `scripts/test-host.sh --filter=MunicipiosHabilitadosTest`
Expected: FAIL com `Call to undefined method App\Models\Municipio::esquecerHabilitadosCisterna()`.

- [ ] **Step 3: Acrescentar o scope em `Municipio`**

Inserir em `app/Models/Municipio.php`, logo depois do metodo `catalogo()` e das constantes existentes. Acrescentar as duas constantes junto das que ja existem no topo da classe:

```php
    private const CISTERNA_CACHE_KEY = 'municipios:cisterna_habilitados';

    private const CISTERNA_MEMO_TTL = 300;

    private static ?Collection $cisternaMemo = null;

    private static float $cisternaMemoExp = 0.0;
```

E os metodos:

```php
    /**
     * Municipios habilitados no Projeto Cisterna, ordenados por nome.
     *
     * O flag mora em `cedec_municipio.at_cisterna` — a mesma tabela que o
     * ImportCedecMunicipioCommand documenta como a ponte de municipio do
     * legado (`cedec_municipio.Codmundv = municipios.codigo_ibge`). Nao
     * duplicamos o flag em `municipios`: a fonte de verdade continua uma so.
     *
     * O legado resolvia isso com Municipio::where('at_cisterna', 1) em nove
     * pontos diferentes dos controllers.
     *
     * Camadas de cache identicas a catalogo(): memo por worker (300s) ->
     * Redis (24h) -> Postgres. A lista muda raramente e alimenta select de
     * praticamente toda tela do modulo.
     *
     * @return Collection<int, array{id: int, nome: string, uf: string}>
     */
    public static function habilitadosCisterna(): Collection
    {
        $now = microtime(true);

        if (self::$cisternaMemo !== null && self::$cisternaMemoExp > $now) {
            return self::$cisternaMemo;
        }

        $lista = Cache::remember(self::CISTERNA_CACHE_KEY, 86400, function (): Collection {
            return static::query()
                ->join('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
                ->where('cedec_municipio.at_cisterna', 1)
                ->orderBy('municipios.nome')
                ->get(['municipios.id', 'municipios.nome', 'municipios.uf'])
                ->map(fn (self $m): array => ['id' => (int) $m->id, 'nome' => $m->nome, 'uf' => $m->uf])
                ->values();
        });

        self::$cisternaMemo = $lista;
        self::$cisternaMemoExp = $now + self::CISTERNA_MEMO_TTL;

        return $lista;
    }

    /**
     * Somente os ids, para uso em whereIn de escopo por perfil.
     *
     * @return array<int, int>
     */
    public static function idsHabilitadosCisterna(): array
    {
        return static::habilitadosCisterna()->pluck('id')->all();
    }

    /**
     * Invalida a lista cacheada. Chamar depois de alterar cedec_municipio.
     */
    public static function esquecerHabilitadosCisterna(): void
    {
        self::$cisternaMemo = null;
        self::$cisternaMemoExp = 0.0;
        Cache::forget(self::CISTERNA_CACHE_KEY);
    }
```

- [ ] **Step 4: Registrar o disco de leitura do legado**

Em `config/filesystems.php`, logo depois do bloco `legado_rat`:

```php
        // Arquivos do modulo Cisterna no legado `sdc`: fotos do imovel em
        // cisterna/{cpf}/ e fotos de vistoria em
        // relatorios/cisterna/{form}/{id}/. Disco de LEITURA, usado somente
        // pelo refino do ETL para copiar para as collections do MediaLibrary.
        // Nenhum caminho novo contem CPF — o legado usava dado pessoal como
        // nome de diretorio.
        'legado_cisterna' => [
            'driver' => 'local',
            'root' => env('LEGADO_CISTERNA_ANEXOS_ROOT', storage_path('app/public/legado_cisterna')),
            'visibility' => 'private',
            'throw' => false,
        ],
```

- [ ] **Step 5: Registrar a conexao MySQL do legado**

Em `config/database.php`, dentro de `connections`:

```php
        // Somente leitura, consumida por cisterna:extrair-legado. Nao ha
        // migration nem model apontando para ela.
        'legado_cisterna_mysql' => [
            'driver' => 'mysql',
            'host' => env('LEGADO_CISTERNA_DB_HOST', '127.0.0.1'),
            'port' => env('LEGADO_CISTERNA_DB_PORT', '3306'),
            'database' => env('LEGADO_CISTERNA_DB_DATABASE', 'dbsdc'),
            'username' => env('LEGADO_CISTERNA_DB_USERNAME', 'root'),
            'password' => env('LEGADO_CISTERNA_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('LEGADO_CISTERNA_DB_SSL_CA'),
            ]) : [],
        ],
```

- [ ] **Step 6: Documentar as env vars**

Acrescentar ao final de `.env.example`:

```
# Modulo Cisterna — leitura do legado sdc (ETL)
LEGADO_CISTERNA_DB_HOST=127.0.0.1
LEGADO_CISTERNA_DB_PORT=3306
LEGADO_CISTERNA_DB_DATABASE=dbsdc
LEGADO_CISTERNA_DB_USERNAME=root
LEGADO_CISTERNA_DB_PASSWORD=
LEGADO_CISTERNA_ANEXOS_ROOT=
```

- [ ] **Step 7: Rodar o teste e confirmar que passa**

Run: `scripts/test-host.sh --filter=MunicipiosHabilitadosTest`
Expected: PASS, 5 testes.

- [ ] **Step 8: Conferir contra o numero medido na Task 1**

Run: `$PHP artisan tinker --execute="dump(count(App\Models\Municipio::idsHabilitadosCisterna()));"`
Expected: o mesmo numero registrado no Step 4 da Task 1. Divergencia significa municipio habilitado sem correspondencia IBGE — a lista dos orfaos esta no Step 5 da Task 1.

- [ ] **Step 9: Commit**

```bash
git add app/Models/Municipio.php config/filesystems.php config/database.php \
        .env.example tests/Feature/Cisterna/MunicipiosHabilitadosTest.php
git commit -m "🔧 config(cisterna): municipios habilitados, disco e conexao do legado"
```

---

### Task 6: Permissoes, policies e rotas

Expande o grupo `CISTERNAS` de `config/permissions.php` em seis subgrupos, cria as seis policies estendendo `BasePolicy` e reescreve o arquivo de rotas. Resolve a lacuna L2 do spec: o perfil institucional vem de `compdec_orgaos.tipo`, nao de role.

**Files:**
- Modify: `config/permissions.php` (grupo `CISTERNAS`, linha 401)
- Create: `app/Policies/CisternaBeneficiarioPolicy.php`
- Create: `app/Policies/CisternaVistoriaPolicy.php`
- Create: `app/Policies/CisternaComunidadePolicy.php`
- Create: `app/Policies/CisternaLotePolicy.php`
- Create: `app/Policies/CisternaOrdemServicoPolicy.php`
- Create: `app/Policies/CisternaNotificacaoPolicy.php`
- Delete: `app/Policies/CisternaPolicy.php`
- Modify: `app/Providers/AuthServiceProvider.php`
- Rewrite: `routes/modules/cisterna.php`
- Modify: `routes/web.php` (restaurar o require, linhas 157-158)
- Create: `app/Modules/Cisterna/Support/PerfilCisterna.php`
- Test: `tests/Feature/Cisterna/PermissoesCisternaTest.php`

**Interfaces:**
- Consumes: `User::orgaoPrincipal()`, `App\Modules\Compdec\Enums\TipoOrgao`, os models da Task 4
- Produces:
  - `PerfilCisterna::deUsuario(User): self` — value object com `tipoOrgao(): ?TipoOrgao`, `municipioId(): ?int`, `eCedec(): bool`, `eCompdec(): bool`, `eFornecedor(): bool`
  - as 25 permissoes `cisternas.{recurso}.{acao}`
  - as seis policies registradas no `AuthServiceProvider`
  - rotas nomeadas `cisternas.beneficiarios.*`, `cisternas.vistorias.*`, `cisternas.comunidades.*`, `cisternas.lotes.*`, `cisternas.ordens-servico.*`, `cisternas.notificacoes.*`, `cisternas.qrcode.*`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/PermissoesCisternaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissoesCisternaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_config_declara_os_seis_subgrupos_de_cisternas(): void
    {
        $grupo = config('permissions.modules.CISTERNAS');

        $this->assertIsArray($grupo);
        foreach (['Beneficiarios', 'Vistorias', 'Comunidades', 'Lotes', 'OrdensServico', 'Notificacoes'] as $sub) {
            $this->assertArrayHasKey($sub, $grupo, "Subgrupo ausente: {$sub}");
        }

        $this->assertSame('cisternas.beneficiarios.view', $grupo['Beneficiarios']['view']);
        $this->assertSame('cisternas.beneficiarios.export', $grupo['Beneficiarios']['export']);
    }

    public function test_perfil_de_usuario_sem_orgao_nao_e_cedec_nem_compdec(): void
    {
        $perfil = PerfilCisterna::deUsuario(User::factory()->create());

        $this->assertNull($perfil->tipoOrgao());
        $this->assertNull($perfil->municipioId());
        $this->assertFalse($perfil->eCedec());
        $this->assertFalse($perfil->eCompdec());
        $this->assertFalse($perfil->eFornecedor());
    }

    public function test_perfil_cedec_vem_do_tipo_do_orgao_principal(): void
    {
        $perfil = PerfilCisterna::deUsuario($this->usuarioComOrgao(TipoOrgao::CEDEC));

        $this->assertSame(TipoOrgao::CEDEC, $perfil->tipoOrgao());
        $this->assertTrue($perfil->eCedec());
        $this->assertFalse($perfil->eCompdec());
    }

    public function test_perfil_compdec_traz_o_municipio_do_orgao(): void
    {
        $municipioId = (int) DB::table('municipios')->value('id');
        $perfil = PerfilCisterna::deUsuario($this->usuarioComOrgao(TipoOrgao::COMPDEC, $municipioId));

        $this->assertTrue($perfil->eCompdec());
        $this->assertSame($municipioId, $perfil->municipioId());
    }

    public function test_perfil_fornecedor_vem_da_role_funcional(): void
    {
        $user = User::factory()->create();
        $user->assignRole(
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'cisterna_fornecedor', 'guard_name' => 'web']
            )
        );

        $perfil = PerfilCisterna::deUsuario($user->fresh());

        $this->assertTrue($perfil->eFornecedor());
        $this->assertNull($perfil->municipioId());
    }

    public function test_policy_nega_visualizacao_sem_permissao(): void
    {
        $user = User::factory()->create();
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->assertFalse($user->can('view', $beneficiario));
    }

    public function test_policy_libera_visualizacao_com_permissao(): void
    {
        $user = $this->usuarioComPermissoes(['cisternas.beneficiarios.view']);
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->assertTrue($user->can('view', $beneficiario));
    }

    public function test_compdec_nao_ve_beneficiario_de_outro_municipio(): void
    {
        $municipios = DB::table('municipios')->limit(2)->pluck('id')->all();

        $user = $this->usuarioComOrgao(TipoOrgao::COMPDEC, (int) $municipios[0]);
        Permission::firstOrCreate(['name' => 'cisternas.beneficiarios.view', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('cisternas.beneficiarios.view');

        $doProprio = CisternaBeneficiario::factory()->create(['municipio_id' => $municipios[0]]);
        $deOutro = CisternaBeneficiario::factory()->create(['municipio_id' => $municipios[1]]);

        $this->assertTrue($user->can('view', $doProprio));
        $this->assertFalse($user->can('view', $deOutro));
    }

    public function test_rotas_do_modulo_estao_registradas(): void
    {
        foreach ([
            'cisternas.beneficiarios.index',
            'cisternas.beneficiarios.store',
            'cisternas.beneficiarios.export',
            'cisternas.vistorias.store',
            'cisternas.comunidades.index',
            'cisternas.lotes.index',
            'cisternas.ordens-servico.index',
            'cisternas.notificacoes.store',
            'cisternas.qrcode.ficha',
        ] as $rota) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($rota),
                "Rota ausente: {$rota}"
            );
        }
    }

    private function usuarioComOrgao(TipoOrgao $tipo, ?int $municipioId = null): User
    {
        $orgao = Orgao::create([
            'nome' => 'Orgao Teste '.$tipo->value,
            'codigo' => strtoupper($tipo->value).'-'.uniqid(),
            'tipo' => $tipo->value,
            'municipio_id' => $municipioId ?? DB::table('municipios')->value('id'),
        ]);

        $user = User::factory()->create(['orgao_principal_id' => $orgao->id]);
        $user->orgaos()->attach($orgao->id);

        return $user->fresh();
    }

    /**
     * @param  array<int, string>  $permissoes
     */
    private function usuarioComPermissoes(array $permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($permissoes);

        return $user->fresh();
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

Run: `scripts/test-host.sh --filter=PermissoesCisternaTest`
Expected: FAIL — `Subgrupo ausente: Beneficiarios` e `Class "App\Modules\Cisterna\Support\PerfilCisterna" not found`.

- [ ] **Step 3: Expandir o grupo `CISTERNAS` em `config/permissions.php`**

Substituir o bloco atual (linhas 401-409):

```php
        'CISTERNAS' => [
            'Beneficiarios' => [
                'view'   => 'cisternas.beneficiarios.view',
                'create' => 'cisternas.beneficiarios.create',
                'edit'   => 'cisternas.beneficiarios.edit',
                'delete' => 'cisternas.beneficiarios.delete',
                'export' => 'cisternas.beneficiarios.export',
            ],
            'Vistorias' => [
                'view'   => 'cisternas.vistorias.view',
                'create' => 'cisternas.vistorias.create',
                'edit'   => 'cisternas.vistorias.edit',
                'delete' => 'cisternas.vistorias.delete',
            ],
            'Comunidades' => [
                'view'   => 'cisternas.comunidades.view',
                'create' => 'cisternas.comunidades.create',
                'edit'   => 'cisternas.comunidades.edit',
                'delete' => 'cisternas.comunidades.delete',
            ],
            'Lotes' => [
                'view'   => 'cisternas.lotes.view',
                'create' => 'cisternas.lotes.create',
                'edit'   => 'cisternas.lotes.edit',
                'delete' => 'cisternas.lotes.delete',
            ],
            'OrdensServico' => [
                'view'    => 'cisternas.ordens-servico.view',
                'create'  => 'cisternas.ordens-servico.create',
                'edit'    => 'cisternas.ordens-servico.edit',
                'delete'  => 'cisternas.ordens-servico.delete',
                'history' => 'cisternas.ordens-servico.history',
            ],
            'Notificacoes' => [
                'view'   => 'cisternas.notificacoes.view',
                'create' => 'cisternas.notificacoes.create',
                'edit'   => 'cisternas.notificacoes.edit',
                'delete' => 'cisternas.notificacoes.delete',
            ],
        ],
```

- [ ] **Step 4: Criar `PerfilCisterna`**

`app/Modules/Cisterna/Support/PerfilCisterna.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

use App\Models\User;
use App\Modules\Compdec\Enums\TipoOrgao;

/**
 * Perfil do usuario no modulo Cisterna.
 *
 * O legado decidia visibilidade com `user->tipo` (cedec|compdec|externo) e
 * `user->municipio_id`, repetidos em quatro metodos do controller. Nenhuma
 * das duas colunas existe no NewSDC:
 *
 *  - o perfil institucional vem de `compdec_orgaos.tipo` (enum TipoOrgao)
 *  - o territorio vem de `compdec_orgaos.municipio_id`
 *  - o fornecedor externo, que nao tem orgao, e uma role funcional
 *
 * Value object imutavel: resolve tudo uma vez e e passado aos services.
 */
final readonly class PerfilCisterna
{
    public const ROLE_FORNECEDOR = 'cisterna_fornecedor';

    private function __construct(
        private ?TipoOrgao $tipoOrgao,
        private ?int $municipioId,
        private bool $fornecedor,
    ) {}

    public static function deUsuario(User $user): self
    {
        $orgao = $user->orgaoPrincipal;

        $tipo = null;
        if ($orgao !== null && $orgao->tipo !== null) {
            $tipo = $orgao->tipo instanceof TipoOrgao
                ? $orgao->tipo
                : TipoOrgao::tryFrom((string) $orgao->tipo);
        }

        return new self(
            tipoOrgao: $tipo,
            municipioId: $orgao?->municipio_id === null ? null : (int) $orgao->municipio_id,
            fornecedor: $user->hasRole(self::ROLE_FORNECEDOR),
        );
    }

    public function tipoOrgao(): ?TipoOrgao
    {
        return $this->tipoOrgao;
    }

    /**
     * Territorio do usuario. Null para CEDEC e para fornecedor: nenhum dos
     * dois e restrito a um municipio.
     */
    public function municipioId(): ?int
    {
        return $this->eCompdec() ? $this->municipioId : null;
    }

    public function eCedec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::CEDEC;
    }

    public function eCompdec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::COMPDEC;
    }

    public function eFornecedor(): bool
    {
        return $this->fornecedor;
    }
}
```

- [ ] **Step 5: Criar as seis policies**

`app/Policies/CisternaBeneficiarioPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;

class CisternaBeneficiarioPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.view');
    }

    public function view(User $user, CisternaBeneficiario $beneficiario): bool
    {
        if (! $user->can('cisternas.beneficiarios.view')) {
            return false;
        }

        return $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.create');
    }

    public function update(User $user, CisternaBeneficiario $beneficiario): bool
    {
        if (! $user->can('cisternas.beneficiarios.edit')) {
            return false;
        }

        return $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function delete(User $user, CisternaBeneficiario $beneficiario): bool
    {
        return $user->can('cisternas.beneficiarios.delete')
            && $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function export(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.export');
    }

    /**
     * COMPDEC ve somente o proprio municipio. CEDEC, fornecedor e usuarios
     * sem orgao nao tem recorte territorial nesta camada — a listagem aplica
     * o recorte proprio de cada perfil no BeneficiarioService.
     */
    private function dentroDoTerritorio(User $user, CisternaBeneficiario $beneficiario): bool
    {
        $municipioId = PerfilCisterna::deUsuario($user)->municipioId();

        return $municipioId === null || $municipioId === (int) $beneficiario->municipio_id;
    }
}
```

`app/Policies/CisternaVistoriaPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\PerfilCisterna;

class CisternaVistoriaPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.vistorias.view');
    }

    public function view(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.view')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.vistorias.create');
    }

    public function update(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.edit')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    public function delete(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.delete')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    private function dentroDoTerritorio(User $user, CisternaVistoria $vistoria): bool
    {
        $municipioId = PerfilCisterna::deUsuario($user)->municipioId();

        if ($municipioId === null) {
            return true;
        }

        return $municipioId === (int) $vistoria->beneficiario?->municipio_id;
    }
}
```

As quatro restantes seguem o mesmo esqueleto, **sem** recorte territorial (comunidade, lote, OS e notificacao nao pertencem a um municipio unico). Repetidas na integra porque quem implementa pode estar lendo fora de ordem:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaComunidadePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.comunidades.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.comunidades.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.comunidades.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.comunidades.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.comunidades.delete');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaLotePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.lotes.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.lotes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.lotes.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.lotes.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.lotes.delete');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaOrdemServicoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.delete');
    }

    /**
     * Timeline do lote: uniao da trilha da OS com as movimentacoes de
     * beneficiarios cujo ordem_servico_id apontou para ela.
     */
    public function history(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.history');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaNotificacaoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.notificacoes.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.notificacoes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.notificacoes.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.notificacoes.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.notificacoes.delete');
    }
}
```

- [ ] **Step 6: Registrar as policies e remover a antiga**

Em `app/Providers/AuthServiceProvider.php`, no array `$policies`, substituir a linha do scaffold por:

```php
        \App\Modules\Cisterna\Models\CisternaBeneficiario::class => \App\Policies\CisternaBeneficiarioPolicy::class,
        \App\Modules\Cisterna\Models\CisternaVistoria::class => \App\Policies\CisternaVistoriaPolicy::class,
        \App\Modules\Cisterna\Models\CisternaComunidade::class => \App\Policies\CisternaComunidadePolicy::class,
        \App\Modules\Cisterna\Models\CisternaLote::class => \App\Policies\CisternaLotePolicy::class,
        \App\Modules\Cisterna\Models\CisternaOrdemServico::class => \App\Policies\CisternaOrdemServicoPolicy::class,
        \App\Modules\Cisterna\Models\CisternaNotificacao::class => \App\Policies\CisternaNotificacaoPolicy::class,
```

Depois: `git rm app/Policies/CisternaPolicy.php`

- [ ] **Step 7: Reescrever `routes/modules/cisterna.php`**

```php
<?php

declare(strict_types=1);

use App\Modules\Cisterna\Controllers\BeneficiarioController;
use App\Modules\Cisterna\Controllers\ComunidadeController;
use App\Modules\Cisterna\Controllers\LoteController;
use App\Modules\Cisterna\Controllers\NotificacaoFiscalizacaoController;
use App\Modules\Cisterna\Controllers\OrdemServicoController;
use App\Modules\Cisterna\Controllers\QrCodeController;
use App\Modules\Cisterna\Controllers\VistoriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo CISTERNA
|--------------------------------------------------------------------------
| Cadastro de beneficiario do Projeto Cisterna e fiscalizacao da instalacao
| em tres etapas (fornecedor -> COMPDEC -> CEDEC).
|
| Permissoes em config/permissions.php, grupo CISTERNAS.
| Autorizacao por policy: as rotas nao usam middleware `can:`, porque o
| recorte territorial do perfil COMPDEC depende da instancia do model.
|
| Rotas removidas do legado, deliberadamente:
|  - cisterna/relatorio/ranqueamento — apontava para metodo inexistente
|  - /adicionar-permissoes-compdec — concedia permissao em massa via GET
|  - cisterna/check_duplicated_qrcode — a constraint UNIQUE responde
|  - cisterna/valida_cedec — terminava em dd($request)
*/

// Ficha publica lida pelo QR Code colado na cisterna: sem autenticacao,
// como no legado.
Route::get('cisternas/qrcode/{numeroInstalacao}', [QrCodeController::class, 'ficha'])
    ->name('cisternas.qrcode.ficha')
    ->whereNumber('numeroInstalacao');

// Mantem a URL antiga acessivel.
Route::redirect('/cisterna', '/cisternas/beneficiarios')->name('cisterna.redirect');
Route::redirect('/cisternas', '/cisternas/beneficiarios')->name('cisternas.redirect');

Route::middleware(['auth'])->prefix('cisternas')->name('cisternas.')->group(function (): void {

    /* Beneficiarios */
    Route::prefix('beneficiarios')->name('beneficiarios.')->group(function (): void {
        Route::get('/', [BeneficiarioController::class, 'index'])->name('index');
        Route::get('/exportar', [BeneficiarioController::class, 'export'])->name('export');
        Route::get('/novo', [BeneficiarioController::class, 'create'])->name('create');
        Route::post('/', [BeneficiarioController::class, 'store'])->name('store');
        Route::post('/acao-em-massa', [BeneficiarioController::class, 'acaoEmMassa'])->name('acao-em-massa');
        Route::get('/{beneficiario}', [BeneficiarioController::class, 'show'])
            ->name('show')->whereNumber('beneficiario');
        Route::get('/{beneficiario}/editar', [BeneficiarioController::class, 'edit'])
            ->name('edit')->whereNumber('beneficiario');
        Route::put('/{beneficiario}', [BeneficiarioController::class, 'update'])
            ->name('update')->whereNumber('beneficiario');
        Route::delete('/{beneficiario}', [BeneficiarioController::class, 'destroy'])
            ->name('destroy')->whereNumber('beneficiario');
    });

    /* Vistorias */
    Route::prefix('vistorias')->name('vistorias.')->group(function (): void {
        Route::get('/beneficiario/{beneficiario}', [VistoriaController::class, 'index'])
            ->name('index')->whereNumber('beneficiario');
        Route::post('/', [VistoriaController::class, 'store'])->name('store');
        Route::get('/{vistoria}', [VistoriaController::class, 'show'])
            ->name('show')->whereNumber('vistoria');
        Route::put('/{vistoria}', [VistoriaController::class, 'update'])
            ->name('update')->whereNumber('vistoria');
        Route::post('/{vistoria}/concluir', [VistoriaController::class, 'concluir'])
            ->name('concluir')->whereNumber('vistoria');
        Route::delete('/{vistoria}', [VistoriaController::class, 'destroy'])
            ->name('destroy')->whereNumber('vistoria');
    });

    /* Comunidades */
    Route::prefix('comunidades')->name('comunidades.')->group(function (): void {
        Route::get('/', [ComunidadeController::class, 'index'])->name('index');
        Route::get('/municipio/{municipio}', [ComunidadeController::class, 'doMunicipio'])
            ->name('do-municipio')->whereNumber('municipio');
        Route::post('/', [ComunidadeController::class, 'store'])->name('store');
        Route::put('/{comunidade}', [ComunidadeController::class, 'update'])
            ->name('update')->whereNumber('comunidade');
        Route::delete('/{comunidade}', [ComunidadeController::class, 'destroy'])
            ->name('destroy')->whereNumber('comunidade');
    });

    /* Lotes */
    Route::prefix('lotes')->name('lotes.')->group(function (): void {
        Route::get('/', [LoteController::class, 'index'])->name('index');
        Route::post('/', [LoteController::class, 'store'])->name('store');
        Route::put('/{lote}', [LoteController::class, 'update'])
            ->name('update')->whereNumber('lote');
        Route::delete('/{lote}', [LoteController::class, 'destroy'])
            ->name('destroy')->whereNumber('lote');
    });

    /* Ordens de servico */
    Route::prefix('ordens-servico')->name('ordens-servico.')->group(function (): void {
        Route::get('/', [OrdemServicoController::class, 'index'])->name('index');
        Route::get('/lote/{lote}', [OrdemServicoController::class, 'doLote'])
            ->name('do-lote')->whereNumber('lote');
        Route::post('/', [OrdemServicoController::class, 'store'])->name('store');
        Route::get('/{ordemServico}/timeline', [OrdemServicoController::class, 'timeline'])
            ->name('timeline')->whereNumber('ordemServico');
        Route::put('/{ordemServico}', [OrdemServicoController::class, 'update'])
            ->name('update')->whereNumber('ordemServico');
        Route::delete('/{ordemServico}', [OrdemServicoController::class, 'destroy'])
            ->name('destroy')->whereNumber('ordemServico');
    });

    /* Notificacoes de fiscalizacao */
    Route::prefix('notificacoes')->name('notificacoes.')->group(function (): void {
        Route::get('/', [NotificacaoFiscalizacaoController::class, 'index'])->name('index');
        Route::post('/', [NotificacaoFiscalizacaoController::class, 'store'])->name('store');
        Route::put('/{notificacao}', [NotificacaoFiscalizacaoController::class, 'update'])
            ->name('update')->whereNumber('notificacao');
        Route::post('/{notificacao}/responder', [NotificacaoFiscalizacaoController::class, 'responder'])
            ->name('responder')->whereNumber('notificacao');
        Route::delete('/{notificacao}', [NotificacaoFiscalizacaoController::class, 'destroy'])
            ->name('destroy')->whereNumber('notificacao');
    });

    /* QR Code autenticado */
    Route::prefix('qrcode')->name('qrcode.')->group(function (): void {
        Route::get('/vistoria/{vistoria}/pdf', [QrCodeController::class, 'pdfIndividual'])
            ->name('pdf-individual')->whereNumber('vistoria');
        Route::post('/pdf-em-lote', [QrCodeController::class, 'pdfEmLote'])->name('pdf-em-lote');
        Route::get('/folhas-vazias', [QrCodeController::class, 'folhasVazias'])->name('folhas-vazias');
    });
});
```

- [ ] **Step 8: Restaurar o require em `routes/web.php`**

Descomentar as linhas 157-158 comentadas no Step 7 da Task 4, deixando o `require` incondicional (o arquivo passa a ser parte do modulo, nao mais um scaffold opcional):

```php
    require __DIR__ . '/modules/cisterna.php';
```

Os controllers so existem a partir da Task 13. Ate la, as rotas apontam para classes ausentes e **qualquer** request quebra na resolucao. Para nao travar as tasks 7 a 12, criar os sete controllers como stubs vazios agora:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;

/**
 * Stub. Implementado na Task 13.
 */
class BeneficiarioController extends Controller
{
}
```

Repetir para `VistoriaController`, `ComunidadeController`, `LoteController`, `OrdemServicoController`, `NotificacaoFiscalizacaoController` e `QrCodeController`, trocando apenas o nome da classe. O teste `test_rotas_do_modulo_estao_registradas` verifica somente o registro do nome da rota, nao a execucao.

- [ ] **Step 9: Rodar o teste e confirmar que passa**

Run: `scripts/test-host.sh --filter=PermissoesCisternaTest`
Expected: PASS, 9 testes.

- [ ] **Step 10: Semear as permissoes e a role do fornecedor**

Run: `$PHP artisan db:seed --class=RolesAndPermissionsSeeder`

Depois conferir:
Run: `$PHP artisan tinker --execute="dump(Spatie\Permission\Models\Permission::where('name','like','cisternas.%')->count());"`
Expected: 25.

A role `cisterna_fornecedor` nao esta em `config/permissions.php` (as roles de la sao funcionais e genericas). Criar via tinker e registrar o comando no arquivo de anotacoes da Task 1:

```bash
$PHP artisan tinker --execute="
\$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'cisterna_fornecedor', 'guard_name' => 'web']);
\$role->givePermissionTo(['cisternas.beneficiarios.view', 'cisternas.vistorias.view', 'cisternas.vistorias.create', 'cisternas.vistorias.edit']);
dump(\$role->permissions->pluck('name'));"
```

- [ ] **Step 11: Rodar a suite inteira do modulo**

Run: `scripts/test-host.sh --filter=Cisterna`
Expected: PASS — `EnumsTest`, `SchemaCisternaTest`, `ModelsCisternaTest`, `MunicipiosHabilitadosTest`, `PermissoesCisternaTest`.

- [ ] **Step 12: Commit**

```bash
git add config/permissions.php app/Policies app/Providers/AuthServiceProvider.php \
        app/Modules/Cisterna/Support app/Modules/Cisterna/Controllers \
        routes/modules/cisterna.php routes/web.php \
        tests/Feature/Cisterna/PermissoesCisternaTest.php
git commit -m "🔒 security(cisterna): permissoes, policies por agregado e rotas do modulo"
```

**Portao da Fase 1.** A partir daqui o dominio esta de pe: `migrate:fresh` verde, factories gerando registro valido, escopo por perfil resolvido e rotas registradas.

---

## FASE 2 — Servicos e HTTP

### Task 7: DTO e Requests do beneficiario

O legado normalizava mascaras com duas closures declaradas **dentro** do controller, duplicadas em `store()` e `update()` (`CisternaController.php:788-796` e `:1318-1326`). Aqui a normalizacao vai para `prepareForValidation()` do FormRequest, e o service nunca ve `Request`.

**Files:**
- Create: `app/Modules/Cisterna/Support/NormalizaEntrada.php`
- Create: `app/Modules/Cisterna/DTOs/BeneficiarioDTO.php`
- Create: `app/Modules/Cisterna/Requests/StoreBeneficiarioRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateBeneficiarioRequest.php`
- Test: `tests/Unit/Cisterna/NormalizaEntradaTest.php`
- Test: `tests/Feature/Cisterna/BeneficiarioValidacaoTest.php`

**Interfaces:**
- Consumes: enums da Task 2, models da Task 4
- Produces:
  - `NormalizaEntrada::cpf(?string): ?string` — 11 digitos ou null
  - `NormalizaEntrada::moeda(mixed): ?float` — aceita `R$ 1.234,56`
  - `NormalizaEntrada::decimal(mixed): ?float` — aceita `12,50`
  - `NormalizaEntrada::booleanoSimNao(mixed): ?bool` — aceita `sim`/`nao`/`1`/`0`
  - `BeneficiarioDTO::deValidados(array): self`, `toArray(): array`, `atendimentosPipa(): array<int, array{responsavel: string, descricao: ?string}>`
  - `StoreBeneficiarioRequest`, `UpdateBeneficiarioRequest`

- [ ] **Step 1: Escrever o teste unitario que falha**

`tests/Unit/Cisterna/NormalizaEntradaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Cisterna;

use App\Modules\Cisterna\Support\NormalizaEntrada;
use PHPUnit\Framework\TestCase;

class NormalizaEntradaTest extends TestCase
{
    public function test_cpf_remove_mascara_e_devolve_onze_digitos(): void
    {
        $this->assertSame('12345678901', NormalizaEntrada::cpf('123.456.789-01'));
        $this->assertSame('12345678901', NormalizaEntrada::cpf('12345678901'));
        $this->assertSame('12345678901', NormalizaEntrada::cpf(' 123 456 789 01 '));
    }

    public function test_cpf_devolve_null_quando_vazio_ou_incompleto(): void
    {
        $this->assertNull(NormalizaEntrada::cpf(null));
        $this->assertNull(NormalizaEntrada::cpf(''));
        $this->assertNull(NormalizaEntrada::cpf('123.456'));
    }

    public function test_moeda_aceita_o_formato_mascarado_do_legado(): void
    {
        $this->assertSame(1234.56, NormalizaEntrada::moeda('R$ 1.234,56'));
        $this->assertSame(1234.56, NormalizaEntrada::moeda('1.234,56'));
        $this->assertSame(980.0, NormalizaEntrada::moeda('R$ 980,00'));
        $this->assertSame(1500.0, NormalizaEntrada::moeda(1500));
    }

    public function test_moeda_devolve_null_quando_vazio(): void
    {
        $this->assertNull(NormalizaEntrada::moeda(null));
        $this->assertNull(NormalizaEntrada::moeda(''));
    }

    public function test_decimal_aceita_virgula(): void
    {
        $this->assertSame(12.5, NormalizaEntrada::decimal('12,5'));
        $this->assertSame(12.5, NormalizaEntrada::decimal('12.5'));
        $this->assertSame(8.0, NormalizaEntrada::decimal('8'));
        $this->assertNull(NormalizaEntrada::decimal(''));
    }

    public function test_booleano_sim_nao_traduz_as_duas_convencoes_do_legado(): void
    {
        $this->assertTrue(NormalizaEntrada::booleanoSimNao('sim'));
        $this->assertTrue(NormalizaEntrada::booleanoSimNao('SIM'));
        $this->assertTrue(NormalizaEntrada::booleanoSimNao('1'));
        $this->assertTrue(NormalizaEntrada::booleanoSimNao(1));
        $this->assertFalse(NormalizaEntrada::booleanoSimNao('nao'));
        $this->assertFalse(NormalizaEntrada::booleanoSimNao('0'));
        $this->assertNull(NormalizaEntrada::booleanoSimNao(null));
        $this->assertNull(NormalizaEntrada::booleanoSimNao(''));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=NormalizaEntradaTest`
Expected: FAIL com `Class "App\Modules\Cisterna\Support\NormalizaEntrada" not found`.

- [ ] **Step 3: Escrever `NormalizaEntrada`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

/**
 * Normalizacao das mascaras que o formulario do legado enviava como texto.
 *
 * No legado isso vivia em duas closures declaradas dentro do controller,
 * duplicadas em store() e update() (CisternaController.php:788 e :1318).
 * Aqui e usada por prepareForValidation() dos FormRequests e pelo refino do
 * ETL, que le os mesmos formatos do banco legado.
 */
final class NormalizaEntrada
{
    /**
     * Devolve os 11 digitos do CPF, ou null se nao houver 11.
     * O legado guardava com mascara em varchar(150) e fazia str_replace em
     * quatro pontos diferentes, inclusive para montar nome de diretorio.
     */
    public static function cpf(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $valor) ?? '';

        return strlen($digitos) === 11 ? $digitos : null;
    }

    /**
     * Aceita "R$ 1.234,56", "1.234,56" e numero puro.
     */
    public static function moeda(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $limpo = str_replace(['R$', ' ', '.'], '', (string) $valor);
        $limpo = str_replace(',', '.', $limpo);

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    /**
     * Aceita virgula como separador decimal, como o formulario do legado
     * enviava as medidas de telhado e testada.
     */
    public static function decimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $limpo = str_replace(',', '.', trim((string) $valor));

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    /**
     * O legado usava 'sim'/'nao' nos campos sociais e '1'/'0' nos respAt*.
     */
    public static function booleanoSimNao(mixed $valor): ?bool
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(strtolower(trim((string) $valor)), ['sim', '1', 'true', 's'], true);
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=NormalizaEntradaTest`
Expected: PASS, 6 testes.

- [ ] **Step 5: Escrever `BeneficiarioDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;

final readonly class BeneficiarioDTO
{
    /**
     * @param  array<int, array{responsavel: string, descricao: ?string}>  $atendimentosPipa
     */
    public function __construct(
        public string $cpf,
        public string $nome,
        public int $municipioId,
        public SituacaoAnalise $situacaoAnalise,
        public SituacaoObra $situacaoObra,
        public ?string $telefone = null,
        public ?string $dataNascimento = null,
        public ?string $cadastroUnico = null,
        public ?int $comunidadeId = null,
        public ?string $endereco = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $ordemServicoId = null,
        public ?string $situacaoAnaliseObs = null,
        public ?int $ranqueamentoOrdem = null,
        public ?int $qtdPessoas = null,
        public ?float $renda = null,
        public ?float $rendaPerCapita = null,
        public ?bool $possuiDeficiencia = null,
        public ?bool $possuiCrianca = null,
        public ?string $dataNascimentoCrianca = null,
        public ?bool $possuiIdoso = null,
        public ?bool $chefiadaMulher = null,
        public ?string $tipoMoradia = null,
        public ?string $tipoMoradiaOutro = null,
        public ?float $comprimentoTelhado = null,
        public ?float $larguraTelhado = null,
        public ?float $areaTelhado = null,
        public ?float $comprimentoTestada = null,
        public ?int $numCaidasTelhado = null,
        public ?string $coberturaTelhado = null,
        public ?string $coberturaOutro = null,
        public ?bool $possuiFogaoLenha = null,
        public ?float $medidaTelhadoAreaFogao = null,
        public ?float $testadaDispParteFogao = null,
        public ?bool $atendidoPorPipa = null,
        public ?string $agenteNome = null,
        public ?string $agenteCpf = null,
        public ?string $engenheiroNome = null,
        public ?string $engenheiroCrea = null,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
        private array $atendimentosPipa = [],
    ) {}

    /**
     * @param  array<string, mixed>  $d  Ja validado e normalizado pelo FormRequest.
     */
    public static function deValidados(array $d): self
    {
        // area_telhado e derivada quando nao vem informada, como no legado.
        $area = $d['area_telhado'] ?? null;
        if ($area === null && isset($d['comprimento_telhado'], $d['largura_telhado'])) {
            $area = round((float) $d['comprimento_telhado'] * (float) $d['largura_telhado'], 2);
        }

        $renda = isset($d['renda']) ? (float) $d['renda'] : null;
        $pessoas = isset($d['qtd_pessoas']) ? (int) $d['qtd_pessoas'] : null;

        // renda_per_capita e derivada quando nao vem informada.
        $perCapita = $d['renda_per_capita'] ?? null;
        if ($perCapita === null && $renda !== null && $pessoas !== null && $pessoas > 0) {
            $perCapita = round($renda / $pessoas, 2);
        }

        return new self(
            cpf: (string) $d['cpf'],
            nome: (string) $d['nome'],
            municipioId: (int) $d['municipio_id'],
            situacaoAnalise: SituacaoAnalise::from((string) ($d['situacao_analise'] ?? SituacaoAnalise::EM_EDICAO->value)),
            situacaoObra: SituacaoObra::from((string) ($d['situacao_obra'] ?? SituacaoObra::PROCESSAMENTO->value)),
            telefone: $d['telefone'] ?? null,
            dataNascimento: $d['data_nascimento'] ?? null,
            cadastroUnico: $d['cadastro_unico'] ?? null,
            comunidadeId: isset($d['comunidade_id']) ? (int) $d['comunidade_id'] : null,
            endereco: $d['endereco'] ?? null,
            latitude: isset($d['latitude']) ? (float) $d['latitude'] : null,
            longitude: isset($d['longitude']) ? (float) $d['longitude'] : null,
            ordemServicoId: isset($d['ordem_servico_id']) ? (int) $d['ordem_servico_id'] : null,
            situacaoAnaliseObs: $d['situacao_analise_obs'] ?? null,
            ranqueamentoOrdem: isset($d['ranqueamento_ordem']) ? (int) $d['ranqueamento_ordem'] : null,
            qtdPessoas: $pessoas,
            renda: $renda,
            rendaPerCapita: $perCapita === null ? null : (float) $perCapita,
            possuiDeficiencia: $d['possui_deficiencia'] ?? null,
            possuiCrianca: $d['possui_crianca'] ?? null,
            dataNascimentoCrianca: ($d['possui_crianca'] ?? false) ? ($d['data_nascimento_crianca'] ?? null) : null,
            possuiIdoso: $d['possui_idoso'] ?? null,
            chefiadaMulher: $d['chefiada_mulher'] ?? null,
            tipoMoradia: $d['tipo_moradia'] ?? null,
            tipoMoradiaOutro: $d['tipo_moradia_outro'] ?? null,
            comprimentoTelhado: isset($d['comprimento_telhado']) ? (float) $d['comprimento_telhado'] : null,
            larguraTelhado: isset($d['largura_telhado']) ? (float) $d['largura_telhado'] : null,
            areaTelhado: $area === null ? null : (float) $area,
            comprimentoTestada: isset($d['comprimento_testada']) ? (float) $d['comprimento_testada'] : null,
            numCaidasTelhado: isset($d['num_caidas_telhado']) ? (int) $d['num_caidas_telhado'] : null,
            coberturaTelhado: $d['cobertura_telhado'] ?? null,
            coberturaOutro: $d['cobertura_outro'] ?? null,
            possuiFogaoLenha: $d['possui_fogao_lenha'] ?? null,
            medidaTelhadoAreaFogao: isset($d['medida_telhado_area_fogao']) ? (float) $d['medida_telhado_area_fogao'] : null,
            testadaDispParteFogao: isset($d['testada_disp_parte_fogao']) ? (float) $d['testada_disp_parte_fogao'] : null,
            atendidoPorPipa: $d['atendido_por_pipa'] ?? null,
            agenteNome: $d['agente_nome'] ?? null,
            agenteCpf: $d['agente_cpf'] ?? null,
            engenheiroNome: $d['engenheiro_nome'] ?? null,
            engenheiroCrea: $d['engenheiro_crea'] ?? null,
            observacoes: $d['observacoes'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
            atendimentosPipa: self::extrairAtendimentos($d),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cpf' => $this->cpf,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'data_nascimento' => $this->dataNascimento,
            'cadastro_unico' => $this->cadastroUnico,
            'municipio_id' => $this->municipioId,
            'comunidade_id' => $this->comunidadeId,
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ordem_servico_id' => $this->ordemServicoId,
            'situacao_analise' => $this->situacaoAnalise->value,
            'situacao_analise_obs' => $this->situacaoAnaliseObs,
            'situacao_obra' => $this->situacaoObra->value,
            'ranqueamento_ordem' => $this->ranqueamentoOrdem,
            'qtd_pessoas' => $this->qtdPessoas,
            'renda' => $this->renda,
            'renda_per_capita' => $this->rendaPerCapita,
            'possui_deficiencia' => $this->possuiDeficiencia,
            'possui_crianca' => $this->possuiCrianca,
            'data_nascimento_crianca' => $this->dataNascimentoCrianca,
            'possui_idoso' => $this->possuiIdoso,
            'chefiada_mulher' => $this->chefiadaMulher,
            'tipo_moradia' => $this->tipoMoradia,
            'tipo_moradia_outro' => $this->tipoMoradiaOutro,
            'comprimento_telhado' => $this->comprimentoTelhado,
            'largura_telhado' => $this->larguraTelhado,
            'area_telhado' => $this->areaTelhado,
            'comprimento_testada' => $this->comprimentoTestada,
            'num_caidas_telhado' => $this->numCaidasTelhado,
            'cobertura_telhado' => $this->coberturaTelhado,
            'cobertura_outro' => $this->coberturaOutro,
            'possui_fogao_lenha' => $this->possuiFogaoLenha,
            'medida_telhado_area_fogao' => $this->medidaTelhadoAreaFogao,
            'testada_disp_parte_fogao' => $this->testadaDispParteFogao,
            'atendido_por_pipa' => $this->atendidoPorPipa,
            'agente_nome' => $this->agenteNome,
            'agente_cpf' => $this->agenteCpf,
            'engenheiro_nome' => $this->engenheiroNome,
            'engenheiro_crea' => $this->engenheiroCrea,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * @return array<int, array{responsavel: string, descricao: ?string}>
     */
    public function atendimentosPipa(): array
    {
        return $this->atendimentosPipa;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<int, array{responsavel: string, descricao: ?string}>
     */
    private static function extrairAtendimentos(array $d): array
    {
        $selecionados = $d['responsaveis_pipa'] ?? [];
        if (! is_array($selecionados)) {
            return [];
        }

        $descricao = $d['atendimento_pipa_outro'] ?? null;
        $linhas = [];

        foreach ($selecionados as $valor) {
            $responsavel = ResponsavelPipa::tryFrom((string) $valor);
            if ($responsavel === null) {
                continue;
            }

            $linhas[] = [
                'responsavel' => $responsavel->value,
                'descricao' => $responsavel === ResponsavelPipa::OUTROS ? $descricao : null,
            ];
        }

        return $linhas;
    }
}
```

- [ ] **Step 6: Escrever o teste de validacao que falha**

`tests/Feature/Cisterna/BeneficiarioValidacaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Requests\StoreBeneficiarioRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BeneficiarioValidacaoTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<string, mixed>  $sobrescreve
     * @return array<string, mixed>
     */
    private function payload(array $sobrescreve = []): array
    {
        return array_merge([
            'cpf' => '529.982.247-25',
            'nome' => 'Maria de Teste',
            'telefone' => '(31) 98888-7777',
            'data_nascimento' => now()->subYears(40)->toDateString(),
            'municipio_id' => DB::table('municipios')->value('id'),
            'latitude' => '-19,912998',
            'longitude' => '-43,940933',
            'qtd_pessoas' => 4,
            'renda' => 'R$ 1.200,00',
            'possui_deficiencia' => 'nao',
            'possui_crianca' => 'nao',
            'possui_idoso' => 'nao',
            'chefiada_mulher' => 'nao',
            'comprimento_telhado' => '10,5',
            'largura_telhado' => '6',
            'comprimento_testada' => '10,5',
            'num_caidas_telhado' => 2,
            'cobertura_telhado' => 'telha ceramica',
            'tipo_moradia' => 'alvenaria',
            'possui_fogao_lenha' => 'nao',
            'atendido_por_pipa' => 'nao',
            'agente_nome' => 'Agente Teste',
            'agente_cpf' => '111.444.777-35',
            'engenheiro_nome' => 'Eng Teste',
            'engenheiro_crea' => 'MG-123456',
        ], $sobrescreve);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function validar(array $dados): \Illuminate\Validation\Validator
    {
        $request = StoreBeneficiarioRequest::create('/cisternas/beneficiarios', 'POST', $dados);
        $request->setContainer(app())->setRedirector(app('redirect'));

        // prepareForValidation e protected: exercitado pela API publica.
        $metodo = new \ReflectionMethod($request, 'prepareForValidation');
        $metodo->invoke($request);

        return Validator::make($request->all(), $request->rules(), $request->messages());
    }

    public function test_payload_valido_passa_e_normaliza_mascaras(): void
    {
        $validator = $this->validar($this->payload());

        $this->assertFalse($validator->fails(), (string) $validator->errors());

        $dados = $validator->validated();
        $this->assertSame('52998224725', $dados['cpf']);
        $this->assertSame(1200.0, $dados['renda']);
        $this->assertSame(10.5, $dados['comprimento_telhado']);
        $this->assertSame(-19.912998, $dados['latitude']);
        $this->assertFalse($dados['possui_deficiencia']);
    }

    public function test_menor_de_dezoito_anos_e_rejeitado(): void
    {
        $validator = $this->validar($this->payload([
            'data_nascimento' => now()->subYears(17)->toDateString(),
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data_nascimento', $validator->errors()->toArray());
    }

    public function test_nascimento_anterior_a_1910_e_rejeitado(): void
    {
        $validator = $this->validar($this->payload(['data_nascimento' => '1909-12-31']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data_nascimento', $validator->errors()->toArray());
    }

    public function test_crianca_com_doze_anos_ou_mais_e_rejeitada(): void
    {
        $validator = $this->validar($this->payload([
            'possui_crianca' => 'sim',
            'data_nascimento_crianca' => now()->subYears(13)->toDateString(),
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data_nascimento_crianca', $validator->errors()->toArray());
    }

    public function test_data_da_crianca_e_obrigatoria_quando_possui_crianca(): void
    {
        $validator = $this->validar($this->payload([
            'possui_crianca' => 'sim',
            'data_nascimento_crianca' => null,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data_nascimento_crianca', $validator->errors()->toArray());
    }

    public function test_cpf_duplicado_e_rejeitado_pela_regra_unique(): void
    {
        CisternaBeneficiario::factory()->create(['cpf' => '52998224725']);

        $validator = $this->validar($this->payload());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpf', $validator->errors()->toArray());
    }

    public function test_cpf_sem_onze_digitos_e_rejeitado(): void
    {
        $validator = $this->validar($this->payload(['cpf' => '123.456']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpf', $validator->errors()->toArray());
    }

    public function test_dto_deriva_area_do_telhado_e_renda_per_capita(): void
    {
        $validator = $this->validar($this->payload());
        $dto = \App\Modules\Cisterna\DTOs\BeneficiarioDTO::deValidados($validator->validated());

        $this->assertSame(63.0, $dto->areaTelhado);
        $this->assertSame(300.0, $dto->rendaPerCapita);
    }

    public function test_dto_extrai_os_responsaveis_por_pipa(): void
    {
        $validator = $this->validar($this->payload([
            'atendido_por_pipa' => 'sim',
            'responsaveis_pipa' => ['defesa_civil', 'outros'],
            'atendimento_pipa_outro' => 'Associacao local',
        ]));

        $dto = \App\Modules\Cisterna\DTOs\BeneficiarioDTO::deValidados($validator->validated());
        $atendimentos = $dto->atendimentosPipa();

        $this->assertCount(2, $atendimentos);
        $this->assertSame('defesa_civil', $atendimentos[0]['responsavel']);
        $this->assertNull($atendimentos[0]['descricao']);
        $this->assertSame('Associacao local', $atendimentos[1]['descricao']);
    }
}
```

- [ ] **Step 7: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=BeneficiarioValidacaoTest`
Expected: FAIL com `Class "App\Modules\Cisterna\Requests\StoreBeneficiarioRequest" not found`.

- [ ] **Step 8: Escrever `StoreBeneficiarioRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBeneficiarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaBeneficiario::class) ?? false;
    }

    /**
     * Normaliza as mascaras antes de validar. No legado isso acontecia
     * DEPOIS da validacao, com closures duplicadas no controller — o que
     * fazia 'renda' => 'max:15' validar o texto mascarado, nao o numero.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => NormalizaEntrada::cpf($this->input('cpf')),
            'agente_cpf' => NormalizaEntrada::cpf($this->input('agente_cpf')),
            'renda' => NormalizaEntrada::moeda($this->input('renda')),
            'renda_per_capita' => NormalizaEntrada::moeda($this->input('renda_per_capita')),
            'latitude' => NormalizaEntrada::decimal($this->input('latitude')),
            'longitude' => NormalizaEntrada::decimal($this->input('longitude')),
            'comprimento_telhado' => NormalizaEntrada::decimal($this->input('comprimento_telhado')),
            'largura_telhado' => NormalizaEntrada::decimal($this->input('largura_telhado')),
            'area_telhado' => NormalizaEntrada::decimal($this->input('area_telhado')),
            'comprimento_testada' => NormalizaEntrada::decimal($this->input('comprimento_testada')),
            'medida_telhado_area_fogao' => NormalizaEntrada::decimal($this->input('medida_telhado_area_fogao')),
            'testada_disp_parte_fogao' => NormalizaEntrada::decimal($this->input('testada_disp_parte_fogao')),
            'possui_deficiencia' => NormalizaEntrada::booleanoSimNao($this->input('possui_deficiencia')),
            'possui_crianca' => NormalizaEntrada::booleanoSimNao($this->input('possui_crianca')),
            'possui_idoso' => NormalizaEntrada::booleanoSimNao($this->input('possui_idoso')),
            'chefiada_mulher' => NormalizaEntrada::booleanoSimNao($this->input('chefiada_mulher')),
            'possui_fogao_lenha' => NormalizaEntrada::booleanoSimNao($this->input('possui_fogao_lenha')),
            'atendido_por_pipa' => NormalizaEntrada::booleanoSimNao($this->input('atendido_por_pipa')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Limites de idade do legado: beneficiario maior de 18, crianca
        // menor de 12, nascimento nao anterior a 1910.
        $minNascimento = CarbonImmutable::create(1910, 1, 1)->toDateString();
        $maxNascimento = CarbonImmutable::now()->subYears(18)->toDateString();
        $minNascimentoCrianca = CarbonImmutable::now()->subYears(12)->toDateString();

        return [
            // Espelha o indice unico PARCIAL do banco: registro marcado como
            // Duplicado nao bloqueia um cadastro novo com o mesmo CPF.
            'cpf' => [
                'required',
                'string',
                'size:11',
                Rule::unique('cisterna_beneficiarios', 'cpf')
                    ->whereNull('deleted_at')
                    ->where('situacao_analise', '<>', SituacaoAnalise::DUPLICADO->value),
            ],
            'nome' => ['required', 'string', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:15'],
            'data_nascimento' => ['required', 'date', "after_or_equal:{$minNascimento}", "before_or_equal:{$maxNascimento}"],
            'cadastro_unico' => ['nullable', 'string', 'max:12'],

            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['nullable', 'integer', 'exists:cisterna_comunidades,id'],
            'endereco' => ['nullable', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'ordem_servico_id' => ['nullable', 'integer', 'exists:cisterna_ordens_servico,id'],

            'situacao_analise' => ['nullable', Rule::in(SituacaoAnalise::valores())],
            'situacao_analise_obs' => ['nullable', 'string', 'max:255'],
            'situacao_obra' => ['nullable', Rule::in(SituacaoObra::valores())],
            'ranqueamento_ordem' => ['nullable', 'integer', 'min:1'],

            'qtd_pessoas' => ['required', 'integer', 'min:1', 'max:99'],
            'renda' => ['required', 'numeric', 'min:0'],
            'renda_per_capita' => ['nullable', 'numeric', 'min:0'],

            'possui_deficiencia' => ['required', 'boolean'],
            'comprovante_deficiencia' => ['exclude_if:possui_deficiencia,false', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'possui_crianca' => ['required', 'boolean'],
            'data_nascimento_crianca' => ['exclude_if:possui_crianca,false', 'required', 'date', "after:{$minNascimentoCrianca}"],
            'possui_idoso' => ['required', 'boolean'],
            'chefiada_mulher' => ['required', 'boolean'],
            'comprovante_chefia_mulher' => ['exclude_if:chefiada_mulher,false', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'comprovante_observacao' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],

            'tipo_moradia' => ['required', 'string', 'max:30'],
            'tipo_moradia_outro' => ['nullable', 'string', 'max:50'],
            'comprimento_telhado' => ['required', 'numeric', 'min:0'],
            'largura_telhado' => ['required', 'numeric', 'min:0'],
            'area_telhado' => ['nullable', 'numeric', 'min:0'],
            'comprimento_testada' => ['required', 'numeric', 'min:0'],
            'num_caidas_telhado' => ['required', 'integer', 'min:1', 'max:99'],
            'cobertura_telhado' => ['required', 'string', 'max:30'],
            'cobertura_outro' => ['nullable', 'string', 'max:150'],
            'possui_fogao_lenha' => ['required', 'boolean'],
            'medida_telhado_area_fogao' => ['nullable', 'numeric', 'min:0'],
            'testada_disp_parte_fogao' => ['nullable', 'numeric', 'min:0'],

            'atendido_por_pipa' => ['required', 'boolean'],
            'responsaveis_pipa' => ['nullable', 'array'],
            'responsaveis_pipa.*' => [Rule::in(ResponsavelPipa::valores())],
            'atendimento_pipa_outro' => ['nullable', 'string', 'max:255'],

            'agente_nome' => ['required', 'string', 'max:70'],
            'agente_cpf' => ['required', 'string', 'size:11'],
            'engenheiro_nome' => ['required', 'string', 'max:150'],
            'engenheiro_crea' => ['required', 'string', 'max:20'],

            'observacoes' => ['nullable', 'string', 'max:1000'],

            'fotos_imovel' => ['nullable', 'array', 'max:10'],
            'fotos_imovel.*.arquivo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:3072'],
            'fotos_imovel.*.angulo' => ['required', 'string', 'max:40'],
            'fotos_imovel.*.observacao' => ['nullable', 'string', 'max:262'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.required' => 'O CPF do beneficiario e obrigatorio.',
            'cpf.size' => 'O CPF deve ter 11 digitos.',
            'cpf.unique' => 'Este CPF ja esta cadastrado.',
            'nome.required' => 'O nome do beneficiario e obrigatorio.',
            'data_nascimento.required' => 'A data de nascimento e obrigatoria.',
            'data_nascimento.before_or_equal' => 'O beneficiario deve ser maior de 18 anos.',
            'data_nascimento.after_or_equal' => 'A data de nascimento deve ser posterior a 31 de dezembro de 1909.',
            'data_nascimento_crianca.required' => 'A data de nascimento da crianca e obrigatoria.',
            'data_nascimento_crianca.after' => 'A crianca deve ter menos de 12 anos.',
            'comprovante_deficiencia.required' => 'O anexo do laudo de deficiencia e obrigatorio.',
            'comprovante_chefia_mulher.required' => 'O comprovante para residencia chefiada por mulher e obrigatorio.',
            'latitude.required' => 'A latitude e obrigatoria.',
            'longitude.required' => 'A longitude e obrigatoria.',
            'agente_cpf.size' => 'O CPF do agente deve ter 11 digitos.',
        ];
    }
}
```

- [ ] **Step 9: Escrever `UpdateBeneficiarioRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use Illuminate\Validation\Rule;

/**
 * Herda as regras e a normalizacao do Store, ajustando o que muda na edicao:
 *  - o unique de CPF ignora o proprio registro
 *  - o comprovante volta a ser opcional quando ja existe arquivo salvo
 *    (comportamento do legado, CisternaController.php:1287-1297)
 */
class UpdateBeneficiarioRequest extends StoreBeneficiarioRequest
{
    public function authorize(): bool
    {
        $beneficiario = $this->route('beneficiario');

        return $beneficiario !== null && ($this->user()?->can('update', $beneficiario) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $regras = parent::rules();
        $beneficiario = $this->route('beneficiario');

        $regras['cpf'] = [
            'required',
            'string',
            'size:11',
            Rule::unique('cisterna_beneficiarios', 'cpf')
                ->ignore($beneficiario?->getKey())
                ->whereNull('deleted_at'),
        ];

        // Se marcou 'sim' e ja tem arquivo salvo, o envio e opcional: e uma
        // substituicao, nao uma exigencia nova.
        if ($beneficiario?->getMedia('comprovantes')->firstWhere('custom_properties.tipo', 'deficiencia') !== null) {
            $regras['comprovante_deficiencia'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'];
        }

        if ($beneficiario?->getMedia('comprovantes')->firstWhere('custom_properties.tipo', 'chefia_mulher') !== null) {
            $regras['comprovante_chefia_mulher'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'];
        }

        return $regras;
    }
}
```

- [ ] **Step 10: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=BeneficiarioValidacaoTest`
Expected: PASS, 9 testes.

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Cisterna/Support/NormalizaEntrada.php \
        app/Modules/Cisterna/DTOs/BeneficiarioDTO.php \
        app/Modules/Cisterna/Requests \
        tests/Unit/Cisterna/NormalizaEntradaTest.php \
        tests/Feature/Cisterna/BeneficiarioValidacaoTest.php
git commit -m "✨ feat(cisterna): DTO e requests do beneficiario com normalizacao de mascaras"
```

---

### Task 8: BeneficiarioService — escopo por perfil, CRUD e acoes em massa

Concentra o que o legado espalhava em quatro metodos do controller (`index`, `rank`, `aplicarFiltros`, `menu`). Corrige os defeitos C5 (paginate 400 com QR por linha), C6 (tres `whereHas`), C11 (`codmundv` literal), C12 (visibilidade replicada) e C16 (`->get()` so para contar).

**Files:**
- Create: `app/Modules/Cisterna/Services/BeneficiarioService.php`
- Test: `tests/Feature/Cisterna/BeneficiarioServiceTest.php`

**Interfaces:**
- Consumes: `PerfilCisterna` (Task 6), `BeneficiarioDTO` (Task 7), `CisternaBeneficiario` (Task 4), `Municipio::idsHabilitadosCisterna()` (Task 5)
- Produces:
  - `listar(PerfilCisterna, array $filtros = [], int $porPagina = 25): LengthAwarePaginator`
  - `obter(int $id): CisternaBeneficiario`
  - `criar(BeneficiarioDTO): CisternaBeneficiario`
  - `atualizar(CisternaBeneficiario, BeneficiarioDTO): CisternaBeneficiario`
  - `deletar(CisternaBeneficiario): bool`
  - `alocarEmOrdemServico(array<int,int> $ids, int $ordemServicoId): int`
  - `removerDeOrdemServico(array<int,int> $ids): int`
  - `alterarSituacaoObra(array<int,int> $ids, SituacaoObra): int`
  - `indicadores(PerfilCisterna): array{total:int, por_analise:array<string,int>, por_obra:array<string,int>, municipios:int, com_vistoria_fornecedor:int, com_vistoria_compdec:int, com_vistoria_cedec:int}`
  - `PORTE_MAXIMO_PAGINA = 100`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/BeneficiarioServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Cisterna\DTOs\BeneficiarioDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\BeneficiarioService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BeneficiarioServiceTest extends TestCase
{
    use DatabaseTransactions;

    private BeneficiarioService $service;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BeneficiarioService::class);
        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function test_cedec_ve_beneficiarios_de_todos_os_municipios(): void
    {
        CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $total = $this->service->listar($this->perfil(TipoOrgao::CEDEC))->total();

        $this->assertGreaterThanOrEqual(2, $total);
    }

    public function test_compdec_ve_apenas_o_proprio_municipio(): void
    {
        $doProprio = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $pagina = $this->service->listar($this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));

        $this->assertSame(1, $pagina->total());
        $this->assertSame($doProprio->id, $pagina->items()[0]->id);
    }

    public function test_fornecedor_ve_apenas_obras_em_envio_ou_instaladas(): void
    {
        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[0],
            'situacao_obra' => SituacaoObra::PROCESSAMENTO->value,
        ]);
        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[0],
            'situacao_obra' => SituacaoObra::ENVIO_INSTALACAO->value,
        ]);
        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[1],
            'situacao_obra' => SituacaoObra::INSTALADO->value,
        ]);

        $pagina = $this->service->listar($this->perfilFornecedor());

        $this->assertSame(2, $pagina->total());
    }

    public function test_filtro_por_etapa_de_vistoria_usa_join_unico(): void
    {
        $comFornecedor = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->concluida()->create([
            'beneficiario_id' => $comFornecedor->id,
        ]);

        CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);

        $pagina = $this->service->listar(
            $this->perfil(TipoOrgao::CEDEC),
            ['etapa_concluida' => EtapaVistoria::FORNECEDOR->value]
        );

        $this->assertSame(1, $pagina->total());
        $this->assertSame($comFornecedor->id, $pagina->items()[0]->id);
    }

    public function test_porte_de_pagina_tem_teto_de_cem(): void
    {
        $pagina = $this->service->listar($this->perfil(TipoOrgao::CEDEC), [], 5000);

        $this->assertSame(BeneficiarioService::PORTE_MAXIMO_PAGINA, $pagina->perPage());
    }

    public function test_criar_persiste_beneficiario_e_atendimentos_de_pipa(): void
    {
        $dto = $this->dto([
            'atendido_por_pipa' => true,
            'responsaveis_pipa' => ['defesa_civil', 'outros'],
            'atendimento_pipa_outro' => 'Associacao local',
        ]);

        $beneficiario = $this->service->criar($dto);

        $this->assertSame('52998224725', $beneficiario->cpf);
        $this->assertCount(2, $beneficiario->atendimentosPipa);
        $this->assertSame(
            'Associacao local',
            $beneficiario->atendimentosPipa->firstWhere('responsavel.value', 'outros')->descricao
        );
    }

    public function test_atualizar_substitui_os_atendimentos_em_vez_de_acumular(): void
    {
        $beneficiario = $this->service->criar($this->dto([
            'responsaveis_pipa' => ['defesa_civil', 'exercito'],
        ]));

        $atualizado = $this->service->atualizar($beneficiario, $this->dto([
            'responsaveis_pipa' => ['prefeitura'],
        ]));

        $this->assertCount(1, $atualizado->atendimentosPipa);
        $this->assertSame('prefeitura', $atualizado->atendimentosPipa->first()->responsavel->value);
    }

    public function test_alocar_em_ordem_de_servico_em_massa(): void
    {
        $os = CisternaOrdemServico::factory()->create();
        $ids = CisternaBeneficiario::factory()->count(3)
            ->create(['municipio_id' => $this->municipios[0]])
            ->pluck('id')->all();

        $afetados = $this->service->alocarEmOrdemServico($ids, $os->id);

        $this->assertSame(3, $afetados);
        $this->assertSame(3, CisternaBeneficiario::where('ordem_servico_id', $os->id)->count());
    }

    public function test_remover_de_ordem_de_servico_em_massa(): void
    {
        $os = CisternaOrdemServico::factory()->create();
        $ids = CisternaBeneficiario::factory()->count(2)
            ->create(['ordem_servico_id' => $os->id])
            ->pluck('id')->all();

        $afetados = $this->service->removerDeOrdemServico($ids);

        $this->assertSame(2, $afetados);
        $this->assertSame(0, CisternaBeneficiario::where('ordem_servico_id', $os->id)->count());
    }

    public function test_alterar_situacao_de_obra_em_massa(): void
    {
        $ids = CisternaBeneficiario::factory()->count(2)
            ->create(['situacao_obra' => SituacaoObra::PROCESSAMENTO->value])
            ->pluck('id')->all();

        $afetados = $this->service->alterarSituacaoObra($ids, SituacaoObra::ENVIO_INSTALACAO);

        $this->assertSame(2, $afetados);
        $this->assertSame(
            2,
            CisternaBeneficiario::whereIn('id', $ids)
                ->where('situacao_obra', SituacaoObra::ENVIO_INSTALACAO->value)
                ->count()
        );
    }

    public function test_indicadores_agregam_em_sql_sem_carregar_colecoes(): void
    {
        $aprovado = CisternaBeneficiario::factory()->aprovado()->create(['municipio_id' => $this->municipios[0]]);
        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[0],
            'situacao_analise' => SituacaoAnalise::RESSALVA->value,
        ]);
        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->concluida()->create([
            'beneficiario_id' => $aprovado->id,
        ]);

        $indicadores = $this->service->indicadores($this->perfil(TipoOrgao::CEDEC));

        $this->assertGreaterThanOrEqual(2, $indicadores['total']);
        $this->assertGreaterThanOrEqual(1, $indicadores['por_analise'][SituacaoAnalise::APROVADO->value]);
        $this->assertGreaterThanOrEqual(1, $indicadores['por_analise'][SituacaoAnalise::RESSALVA->value]);
        $this->assertGreaterThanOrEqual(1, $indicadores['com_vistoria_fornecedor']);
        $this->assertSame(0, $indicadores['com_vistoria_cedec']);
    }

    /**
     * @param  array<string, mixed>  $sobrescreve
     */
    private function dto(array $sobrescreve = []): BeneficiarioDTO
    {
        return BeneficiarioDTO::deValidados(array_merge([
            'cpf' => '52998224725',
            'nome' => 'Maria de Teste',
            'municipio_id' => $this->municipios[0],
            'data_nascimento' => now()->subYears(40)->toDateString(),
            'qtd_pessoas' => 4,
            'renda' => 1200.0,
            'latitude' => -19.912998,
            'longitude' => -43.940933,
            'comprimento_telhado' => 10.5,
            'largura_telhado' => 6.0,
        ], $sobrescreve));
    }

    private function perfil(TipoOrgao $tipo, ?int $municipioId = null): PerfilCisterna
    {
        $orgao = Orgao::create([
            'nome' => 'Orgao '.$tipo->value.' '.uniqid(),
            'codigo' => strtoupper($tipo->value).'-'.uniqid(),
            'tipo' => $tipo->value,
            'municipio_id' => $municipioId ?? $this->municipios[0],
        ]);

        $user = User::factory()->create(['orgao_principal_id' => $orgao->id]);

        return PerfilCisterna::deUsuario($user->fresh());
    }

    private function perfilFornecedor(): PerfilCisterna
    {
        $user = User::factory()->create();
        $user->assignRole(
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => PerfilCisterna::ROLE_FORNECEDOR, 'guard_name' => 'web']
            )
        );

        return PerfilCisterna::deUsuario($user->fresh());
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=BeneficiarioServiceTest`
Expected: FAIL com `Target class [App\Modules\Cisterna\Services\BeneficiarioService] does not exist.`

- [ ] **Step 3: Escrever `BeneficiarioService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\BeneficiarioDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Listagem, CRUD e acoes em massa do beneficiario.
 *
 * O legado replicava a regra de visibilidade em quatro metodos do controller
 * (index, rank, aplicarFiltros e menu), com um `codmundv` literal no meio
 * (3104452). Aqui existe um caminho unico: aplicarEscopoDoPerfil().
 */
class BeneficiarioService
{
    /**
     * O legado paginava 400 por pagina e gerava um QR Code por linha dentro
     * de um map(). O QR passou a ser sob demanda e a pagina tem teto.
     */
    public const PORTE_MAXIMO_PAGINA = 100;

    public const PORTE_PADRAO_PAGINA = 25;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(
        PerfilCisterna $perfil,
        array $filtros = [],
        int $porPagina = self::PORTE_PADRAO_PAGINA,
    ): LengthAwarePaginator {
        $porPagina = max(1, min($porPagina, self::PORTE_MAXIMO_PAGINA));

        $query = CisternaBeneficiario::query()
            ->with([
                'municipio:id,nome,uf',
                'comunidade:id,nome',
                'ordemServico:id,nome,lote_id',
                'ordemServico.lote:id,nome',
            ]);

        $this->aplicarEscopoDoPerfil($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        // Ranqueamento e uma ordenacao alternativa, nao um filtro: quando
        // pedido, substitui o orderBy padrao.
        if (($filtros['ranqueamento'] ?? false) === true) {
            $query->ranqueados();
        } else {
            $query->orderBy('nome');
        }

        return $query->paginate($porPagina)->withQueryString();
    }

    public function obter(int $id): CisternaBeneficiario
    {
        return CisternaBeneficiario::query()
            ->with([
                'municipio:id,nome,uf',
                'comunidade:id,nome',
                'ordemServico.lote:id,nome',
                'atendimentosPipa',
                'vistorias',
                'notificacoes',
                'media',
            ])
            ->findOrFail($id);
    }

    public function criar(BeneficiarioDTO $dto): CisternaBeneficiario
    {
        return DB::transaction(function () use ($dto): CisternaBeneficiario {
            $beneficiario = CisternaBeneficiario::create($dto->toArray());

            $this->sincronizarAtendimentosPipa($beneficiario, $dto);

            return $beneficiario->load('atendimentosPipa');
        });
    }

    public function atualizar(CisternaBeneficiario $beneficiario, BeneficiarioDTO $dto): CisternaBeneficiario
    {
        return DB::transaction(function () use ($beneficiario, $dto): CisternaBeneficiario {
            $beneficiario->update($dto->toArray());

            $this->sincronizarAtendimentosPipa($beneficiario, $dto);

            return $beneficiario->fresh(['atendimentosPipa', 'municipio', 'comunidade']);
        });
    }

    public function deletar(CisternaBeneficiario $beneficiario): bool
    {
        return (bool) $beneficiario->delete();
    }

    /* Acoes em massa — legado: updateEstadoMass, CisternaController.php:1473 */

    /**
     * @param  array<int, int>  $ids
     */
    public function alocarEmOrdemServico(array $ids, int $ordemServicoId): int
    {
        return CisternaBeneficiario::whereIn('id', $ids)
            ->update(['ordem_servico_id' => $ordemServicoId]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function removerDeOrdemServico(array $ids): int
    {
        return CisternaBeneficiario::whereIn('id', $ids)
            ->update(['ordem_servico_id' => null]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function alterarSituacaoObra(array $ids, SituacaoObra $situacao): int
    {
        return CisternaBeneficiario::whereIn('id', $ids)
            ->update(['situacao_obra' => $situacao->value]);
    }

    /**
     * Indicadores do painel. O legado carregava colecoes inteiras com ->get()
     * so para contar, em nove consultas (CisternaController.php:1843-1853).
     * Aqui e uma consulta agregada mais uma por etapa de vistoria.
     *
     * @return array{
     *     total: int,
     *     por_analise: array<string, int>,
     *     por_obra: array<string, int>,
     *     municipios: int,
     *     com_vistoria_fornecedor: int,
     *     com_vistoria_compdec: int,
     *     com_vistoria_cedec: int
     * }
     */
    public function indicadores(PerfilCisterna $perfil): array
    {
        $base = CisternaBeneficiario::query();
        $this->aplicarEscopoDoPerfil($base, $perfil);

        $selects = ['COUNT(*) AS total', 'COUNT(DISTINCT municipio_id) AS municipios'];
        $bindings = [];

        foreach (SituacaoAnalise::valores() as $valor) {
            $selects[] = "COUNT(*) FILTER (WHERE situacao_analise = ?) AS analise_{$valor}";
            $bindings[] = $valor;
        }

        foreach (SituacaoObra::valores() as $valor) {
            $selects[] = "COUNT(*) FILTER (WHERE situacao_obra = ?) AS obra_{$valor}";
            $bindings[] = $valor;
        }

        $linha = $base->clone()->selectRaw(implode(', ', $selects), $bindings)->first();

        $porAnalise = [];
        foreach (SituacaoAnalise::valores() as $valor) {
            $porAnalise[$valor] = (int) ($linha->{'analise_'.$valor} ?? 0);
        }

        $porObra = [];
        foreach (SituacaoObra::valores() as $valor) {
            $porObra[$valor] = (int) ($linha->{'obra_'.$valor} ?? 0);
        }

        return [
            'total' => (int) ($linha->total ?? 0),
            'por_analise' => $porAnalise,
            'por_obra' => $porObra,
            'municipios' => (int) ($linha->municipios ?? 0),
            'com_vistoria_fornecedor' => $this->contarComEtapaConcluida($base, EtapaVistoria::FORNECEDOR),
            'com_vistoria_compdec' => $this->contarComEtapaConcluida($base, EtapaVistoria::COMPDEC),
            'com_vistoria_cedec' => $this->contarComEtapaConcluida($base, EtapaVistoria::CEDEC),
        ];
    }

    /* Internos */

    /**
     * Perfil CEDEC ve todos os municipios habilitados; COMPDEC so o proprio;
     * fornecedor ve qualquer municipio, mas so obras em envio ou instaladas.
     *
     * @param  Builder<CisternaBeneficiario>  $query
     */
    private function aplicarEscopoDoPerfil(Builder $query, PerfilCisterna $perfil): void
    {
        $municipioId = $perfil->municipioId();

        if ($municipioId !== null) {
            $query->doMunicipio($municipioId);
        }

        if ($perfil->eFornecedor()) {
            $query->comSituacaoObra(SituacaoObra::visiveisAoFornecedor());
        }
    }

    /**
     * @param  Builder<CisternaBeneficiario>  $query
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['municipio_id'] ?? null, fn (Builder $q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['comunidade_id'] ?? null, function (Builder $q, $ids): void {
                $q->whereIn('comunidade_id', is_array($ids) ? $ids : [$ids]);
            })
            ->when($filtros['situacao_analise'] ?? null, function (Builder $q, $valores): void {
                $q->whereIn('situacao_analise', is_array($valores) ? $valores : [$valores]);
            })
            ->when($filtros['situacao_obra'] ?? null, function (Builder $q, $valores): void {
                $q->whereIn('situacao_obra', is_array($valores) ? $valores : [$valores]);
            })
            ->when($filtros['ordem_servico_id'] ?? null, function (Builder $q, $ids): void {
                $q->whereIn('ordem_servico_id', is_array($ids) ? $ids : [$ids]);
            })
            ->when($filtros['lote_id'] ?? null, function (Builder $q, $loteId): void {
                $q->whereHas('ordemServico', fn (Builder $os) => $os->where('lote_id', (int) $loteId));
            })
            ->when($filtros['cpf'] ?? null, function (Builder $q, $cpf): void {
                $digitos = preg_replace('/\D/', '', (string) $cpf) ?? '';
                $q->where('cpf', 'like', $digitos.'%');
            })
            ->when($filtros['search'] ?? null, fn (Builder $q, $termo) => $q->buscarPorNome((string) $termo))
            ->when(($filtros['atendido_por_pipa'] ?? null) !== null, function (Builder $q) use ($filtros): void {
                $q->where('atendido_por_pipa', (bool) $filtros['atendido_por_pipa']);
            })
            ->when($filtros['numero_instalacao'] ?? null, function (Builder $q, $numero): void {
                $q->whereHas('vistorias', function (Builder $v) use ($numero): void {
                    $v->where('numero_instalacao', (int) $numero);
                });
            });

        // Substitui os tres whereHas aninhados do legado (validFornecedor,
        // validCompdec, validCedec) por um EXISTS sobre o par unico
        // (beneficiario_id, etapa) com concluida_em preenchido.
        if (isset($filtros['etapa_concluida'])) {
            $etapa = EtapaVistoria::tryFrom((string) $filtros['etapa_concluida']);

            if ($etapa !== null) {
                $query->whereHas('vistorias', function (Builder $v) use ($etapa): void {
                    $v->daEtapa($etapa)->concluidas();
                });
            }
        }

        if (isset($filtros['etapa_pendente'])) {
            $etapa = EtapaVistoria::tryFrom((string) $filtros['etapa_pendente']);

            if ($etapa !== null) {
                $query->whereDoesntHave('vistorias', function (Builder $v) use ($etapa): void {
                    $v->daEtapa($etapa)->concluidas();
                });
            }
        }
    }

    /**
     * @param  Builder<CisternaBeneficiario>  $base
     */
    private function contarComEtapaConcluida(Builder $base, EtapaVistoria $etapa): int
    {
        return $base->clone()
            ->whereHas('vistorias', fn (Builder $v) => $v->daEtapa($etapa)->concluidas())
            ->count();
    }

    /**
     * Substitui, nao acumula: o formulario envia sempre o conjunto completo
     * de responsaveis marcados.
     */
    private function sincronizarAtendimentosPipa(CisternaBeneficiario $beneficiario, BeneficiarioDTO $dto): void
    {
        $beneficiario->atendimentosPipa()->delete();

        foreach ($dto->atendimentosPipa() as $linha) {
            $beneficiario->atendimentosPipa()->create($linha);
        }
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=BeneficiarioServiceTest`
Expected: PASS, 11 testes.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Cisterna/Services/BeneficiarioService.php \
        tests/Feature/Cisterna/BeneficiarioServiceTest.php
git commit -m "✨ feat(cisterna): BeneficiarioService com escopo unico por perfil e acoes em massa"
```

---

### Task 9: NumeracaoInstalacaoService

Corrige o defeito C2. O legado montava `range(1, 1800)` e fazia `array_diff` contra **todos** os `num_instalacao` ja usados a cada abertura de formulario (`CisternaController.php:1518` com teto 100, `:1736` com teto 1800 — dois tetos diferentes no mesmo sistema), e a escolha do numero ficava com o navegador. Dois fornecedores simultaneos recebiam a mesma lista.

**Files:**
- Create: `app/Modules/Cisterna/Services/NumeracaoInstalacaoService.php`
- Test: `tests/Feature/Cisterna/NumeracaoInstalacaoServiceTest.php`

**Interfaces:**
- Consumes: sequence `cisterna_numero_instalacao_seq` (Task 3), `CisternaVistoria` (Task 4)
- Produces:
  - `proximoNumero(): int`
  - `numeroEstaLivre(int, ?int $ignorarVistoriaId = null): bool`
  - `reservar(?int $numeroDesejado = null, ?int $ignorarVistoriaId = null): int`
  - `sincronizarSequenceComOMaximo(): int`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/NumeracaoInstalacaoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\NumeracaoInstalacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NumeracaoInstalacaoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private NumeracaoInstalacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NumeracaoInstalacaoService::class);
    }

    public function test_numeros_consecutivos_nunca_se_repetem(): void
    {
        $primeiro = $this->service->proximoNumero();
        $segundo = $this->service->proximoNumero();
        $terceiro = $this->service->proximoNumero();

        $this->assertNotSame($primeiro, $segundo);
        $this->assertNotSame($segundo, $terceiro);
        $this->assertSame($primeiro + 1, $segundo);
    }

    public function test_nao_existe_teto_artificial_de_mil_e_oitocentos(): void
    {
        $this->service->sincronizarSequenceComOMaximo();

        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 5000,
        ]);

        $this->service->sincronizarSequenceComOMaximo();

        $this->assertGreaterThan(5000, $this->service->proximoNumero());
    }

    public function test_numero_ja_usado_nao_esta_livre(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 4242,
        ]);

        $this->assertFalse($this->service->numeroEstaLivre(4242));
        // A propria vistoria pode manter o numero dela na edicao.
        $this->assertTrue($this->service->numeroEstaLivre(4242, $vistoria->id));
    }

    public function test_reservar_sem_numero_desejado_pega_o_proximo_da_sequence(): void
    {
        $numero = $this->service->reservar();

        $this->assertGreaterThan(0, $numero);
        $this->assertTrue($this->service->numeroEstaLivre($numero));
    }

    public function test_reservar_numero_desejado_livre_devolve_o_mesmo(): void
    {
        $this->assertSame(987654, $this->service->reservar(987654));
    }

    public function test_reservar_numero_ja_usado_lanca_erro_de_validacao(): void
    {
        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 777,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->reservar(777);
    }

    public function test_sincronizar_sequence_devolve_o_maximo_em_uso(): void
    {
        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 31337,
        ]);

        $this->assertGreaterThanOrEqual(31337, $this->service->sincronizarSequenceComOMaximo());
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=NumeracaoInstalacaoServiceTest`
Expected: FAIL com `Target class [App\Modules\Cisterna\Services\NumeracaoInstalacaoService] does not exist.`

- [ ] **Step 3: Escrever o service**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alocacao do numero de instalacao, que e o numero impresso no QR Code
 * colado na cisterna.
 *
 * O legado montava range(1, 1800) e fazia array_diff contra todos os
 * numeros ja usados a cada abertura do formulario, deixando a escolha para o
 * navegador. Dois fornecedores simultaneos recebiam a mesma lista de
 * disponiveis, e havia dois tetos diferentes no mesmo sistema: 100 em
 * relatorio_instalacao() e 1800 em editFormFornecedor().
 *
 * Aqui a alocacao e atomica via sequence do Postgres, com o UNIQUE da coluna
 * como rede de seguranca, e sem teto.
 */
class NumeracaoInstalacaoService
{
    private const SEQUENCE = 'cisterna_numero_instalacao_seq';

    /**
     * Proximo numero da sequence. Atomico: nextval nao volta atras nem sob
     * concorrencia, e nao participa de rollback de transacao.
     */
    public function proximoNumero(): int
    {
        $linha = DB::selectOne('SELECT nextval(?) AS numero', [self::SEQUENCE]);

        return (int) $linha->numero;
    }

    /**
     * O legado expunha isso como endpoint (cisterna/check_duplicated_qrcode).
     * Aqui e uma checagem interna: a constraint UNIQUE e quem decide.
     */
    public function numeroEstaLivre(int $numero, ?int $ignorarVistoriaId = null): bool
    {
        return ! CisternaVistoria::query()
            ->where('numero_instalacao', $numero)
            ->when($ignorarVistoriaId !== null, fn ($q) => $q->whereKeyNot($ignorarVistoriaId))
            ->exists();
    }

    /**
     * Reserva um numero. Sem numero desejado, consome a sequence e pula os
     * que ja estiverem em uso (pode acontecer depois de um ETL que importou
     * numeros acima do valor corrente da sequence).
     *
     * @throws ValidationException quando o numero desejado ja esta em uso
     */
    public function reservar(?int $numeroDesejado = null, ?int $ignorarVistoriaId = null): int
    {
        if ($numeroDesejado !== null) {
            if (! $this->numeroEstaLivre($numeroDesejado, $ignorarVistoriaId)) {
                throw ValidationException::withMessages([
                    'numero_instalacao' => 'Este QR Code ja esta vinculado a outra cisterna.',
                ]);
            }

            return $numeroDesejado;
        }

        // Teto de tentativas: proteção contra loop caso a sequence esteja
        // muito atras do maximo em uso. 1000 tentativas resolvem qualquer
        // defasagem realista; acima disso o correto e sincronizar.
        for ($tentativa = 0; $tentativa < 1000; $tentativa++) {
            $numero = $this->proximoNumero();

            if ($this->numeroEstaLivre($numero, $ignorarVistoriaId)) {
                return $numero;
            }
        }

        $this->sincronizarSequenceComOMaximo();

        return $this->proximoNumero();
    }

    /**
     * Alinha a sequence ao maior numero em uso. Chamar ao final do refino do
     * ETL: sem isso, a sequence comeca em 1 e colide com tudo o que foi
     * importado.
     */
    public function sincronizarSequenceComOMaximo(): int
    {
        $maximo = (int) (CisternaVistoria::query()->max('numero_instalacao') ?? 0);

        // is_called = true faz o proximo nextval devolver maximo + 1.
        DB::statement('SELECT setval(?, ?, true)', [self::SEQUENCE, max($maximo, 1)]);

        return $maximo;
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=NumeracaoInstalacaoServiceTest`
Expected: PASS, 7 testes.

**Atencao ao rodar isoladamente:** `nextval` nao participa de transacao, entao os numeros consumidos nao voltam com o rollback do `DatabaseTransactions`. Isso e correto e proposital — e o que garante a atomicidade. Nao "consertar" trocando por `MAX() + 1`.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Cisterna/Services/NumeracaoInstalacaoService.php \
        tests/Feature/Cisterna/NumeracaoInstalacaoServiceTest.php
git commit -m "✨ feat(cisterna): alocacao atomica do numero de instalacao via sequence"
```

---

### Task 10: VistoriaService, Request e Observer — a cadeia das tres etapas

O nucleo da regra de negocio. No legado eram cinco metodos de controller
(`storeRelatorioFinalFornecedor`, `storeRelatorioFinalCompdec`,
`storeRelatorioFiscalizacaoCedec`, `updateRelatorioFornecedor`,
`updateRelatorioCompdec`, `updateFormCedec`) com validacoes e upload de foto
duplicados. Corrige o defeito C13: `storeRelatorioFinalCompdec` iterava
`$i = 1..2` mas usava sempre o mesmo campo `{item}_foto`, gravando o arquivo
duas vezes.

**Files:**
- Create: `app/Modules/Cisterna/DTOs/VistoriaDTO.php`
- Create: `app/Modules/Cisterna/DTOs/ItemConferidoDTO.php`
- Create: `app/Modules/Cisterna/Requests/StoreVistoriaRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateVistoriaRequest.php`
- Create: `app/Modules/Cisterna/Services/VistoriaService.php`
- Create: `app/Modules/Cisterna/Observers/CisternaVistoriaObserver.php`
- Modify: `app/Modules/Cisterna/CisternaServiceProvider.php`
- Test: `tests/Feature/Cisterna/VistoriaServiceTest.php`

**Interfaces:**
- Consumes: `EtapaVistoria`, `ItemInstalacao`, `UnidadeItem` (Task 2), `CisternaVistoria`, `CisternaItemConferido` (Task 4), `NumeracaoInstalacaoService` (Task 9)
- Produces:
  - `ItemConferidoDTO::deValidados(string $item, array): self`, `toArray(): array`
  - `VistoriaDTO::deValidados(array): self` com `etapa`, `beneficiarioId`, `numeroInstalacao`, `itens(): array<int, ItemConferidoDTO>`, `toArray(): array`
  - `StoreVistoriaRequest` com `rules()` variando por `EtapaVistoria`
  - `VistoriaService::abrir(VistoriaDTO): CisternaVistoria`
  - `VistoriaService::atualizar(CisternaVistoria, VistoriaDTO): CisternaVistoria`
  - `VistoriaService::concluir(CisternaVistoria): CisternaVistoria`
  - `VistoriaService::etapaDisponivel(CisternaBeneficiario): ?EtapaVistoria`
  - `VistoriaService::sincronizarItens(CisternaVistoria, array<int, ItemConferidoDTO>): void`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/VistoriaServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\VistoriaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VistoriaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private VistoriaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VistoriaService::class);
    }

    public function test_primeira_etapa_disponivel_e_a_do_fornecedor(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->assertSame(EtapaVistoria::FORNECEDOR, $this->service->etapaDisponivel($beneficiario));
    }

    public function test_etapa_disponivel_avanca_conforme_as_anteriores_concluem(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $fornecedor = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));
        $this->assertSame(
            EtapaVistoria::FORNECEDOR,
            $this->service->etapaDisponivel($beneficiario->fresh())
        );

        $this->service->concluir($fornecedor);
        $this->assertSame(
            EtapaVistoria::COMPDEC,
            $this->service->etapaDisponivel($beneficiario->fresh())
        );

        $compdec = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::COMPDEC));
        $this->service->concluir($compdec);
        $this->assertSame(
            EtapaVistoria::CEDEC,
            $this->service->etapaDisponivel($beneficiario->fresh())
        );

        $cedec = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::CEDEC));
        $this->service->concluir($cedec);
        $this->assertNull($this->service->etapaDisponivel($beneficiario->fresh()));
    }

    public function test_nao_permite_abrir_etapa_fora_de_ordem(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->expectException(ValidationException::class);
        $this->service->abrir($this->dto($beneficiario, EtapaVistoria::CEDEC));
    }

    public function test_nao_permite_duas_vistorias_na_mesma_etapa(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));

        $this->expectException(ValidationException::class);
        $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));
    }

    public function test_apenas_a_etapa_do_fornecedor_recebe_numero_de_instalacao(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $fornecedor = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));
        $this->assertNotNull($fornecedor->numero_instalacao);

        $this->service->concluir($fornecedor);
        $compdec = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::COMPDEC));
        $this->assertNull($compdec->numero_instalacao);
    }

    public function test_abrir_a_etapa_do_fornecedor_marca_a_obra_como_instalada(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create([
            'situacao_obra' => SituacaoObra::ENVIO_INSTALACAO->value,
        ]);

        $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));

        $this->assertSame(SituacaoObra::INSTALADO, $beneficiario->fresh()->situacao_obra);
    }

    public function test_etapas_seguintes_nao_alteram_a_situacao_da_obra(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create([
            'situacao_obra' => SituacaoObra::ENVIO_INSTALACAO->value,
        ]);

        $fornecedor = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));
        $this->service->concluir($fornecedor);
        $beneficiario->update(['situacao_obra' => SituacaoObra::PROCESSAMENTO->value]);

        $this->service->abrir($this->dto($beneficiario, EtapaVistoria::COMPDEC));

        $this->assertSame(SituacaoObra::PROCESSAMENTO, $beneficiario->fresh()->situacao_obra);
    }

    public function test_itens_conferidos_sao_gravados_com_quantidade_e_unidade(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $vistoria = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR, [
            'itens' => [
                'calha' => ['conferido' => true, 'quantidade' => '12,5'],
                'te_pvc' => ['conferido' => true, 'quantidade' => '4'],
                'bomba' => ['conferido' => false],
            ],
        ]));

        $calha = $vistoria->itemDe(ItemInstalacao::CALHA);
        $this->assertTrue($calha->conferido);
        $this->assertSame('12.50', $calha->quantidade);
        $this->assertSame(UnidadeItem::M, $calha->unidade);

        $tePvc = $vistoria->itemDe(ItemInstalacao::TE_PVC);
        $this->assertSame(UnidadeItem::UN, $tePvc->unidade);

        $bomba = $vistoria->itemDe(ItemInstalacao::BOMBA);
        $this->assertFalse($bomba->conferido);
        $this->assertNull($bomba->unidade);
    }

    public function test_fixacao_guarda_as_tres_subquantidades_em_detalhes(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $vistoria = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR, [
            'itens' => [
                'fixacao' => [
                    'conferido' => true,
                    'detalhes' => ['abracadeira' => '12', 'bucha' => '12', 'parafuso' => '24'],
                ],
            ],
        ]));

        $fixacao = $vistoria->itemDe(ItemInstalacao::FIXACAO);
        $this->assertSame('24', $fixacao->detalhes['parafuso']);
    }

    public function test_detalhes_sao_descartados_em_item_que_nao_os_aceita(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $vistoria = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR, [
            'itens' => [
                'bomba' => ['conferido' => true, 'detalhes' => ['marca' => 'qualquer']],
            ],
        ]));

        $this->assertNull($vistoria->itemDe(ItemInstalacao::BOMBA)->detalhes);
    }

    public function test_sincronizar_itens_substitui_em_vez_de_duplicar(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $vistoria = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR, [
            'itens' => ['calha' => ['conferido' => true, 'quantidade' => '10']],
        ]));

        $this->service->atualizar($vistoria, $this->dto($beneficiario, EtapaVistoria::FORNECEDOR, [
            'itens' => ['calha' => ['conferido' => true, 'quantidade' => '15']],
        ]));

        $vistoria->refresh()->load('itensConferidos');

        $this->assertCount(1, $vistoria->itensConferidos);
        $this->assertSame('15.00', $vistoria->itemDe(ItemInstalacao::CALHA)->quantidade);
    }

    public function test_concluir_exige_dados_administrativos_na_etapa_cedec(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $fornecedor = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::FORNECEDOR));
        $this->service->concluir($fornecedor);
        $compdec = $this->service->abrir($this->dto($beneficiario, EtapaVistoria::COMPDEC));
        $this->service->concluir($compdec);

        $cedec = CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::CEDEC->value,
            'numero_instalacao' => null,
            'processo_sei' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->concluir($cedec);
    }

    public function test_concluir_exige_engenheiro_e_crea(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'engenheiro_nome' => null,
            'engenheiro_crea' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->concluir($vistoria);
    }

    public function test_concluir_e_idempotente(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create();

        $primeira = $this->service->concluir($vistoria);
        $segunda = $this->service->concluir($primeira);

        $this->assertEquals(
            $primeira->concluida_em->toIso8601String(),
            $segunda->concluida_em->toIso8601String()
        );
    }

    /**
     * @param  array<string, mixed>  $sobrescreve
     */
    private function dto(
        CisternaBeneficiario $beneficiario,
        EtapaVistoria $etapa,
        array $sobrescreve = [],
    ): VistoriaDTO {
        return VistoriaDTO::deValidados(array_merge([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => $etapa->value,
            'engenheiro_nome' => 'Eng Teste',
            'engenheiro_crea' => 'MG-123456',
            'data_relatorio' => now()->toDateString(),
            'local_relatorio' => 'Belo Horizonte',
            'endereco' => $beneficiario->endereco,
            'bairro' => 'Centro',
            'latitude' => -19.912998,
            'longitude' => -43.940933,
            'processo_sei' => $etapa === EtapaVistoria::CEDEC ? 'SEI-123456' : null,
            'contrato' => $etapa === EtapaVistoria::CEDEC ? '0001/2026' : null,
            'empenho' => $etapa === EtapaVistoria::CEDEC ? '999888' : null,
            'placa_obras' => $etapa === EtapaVistoria::CEDEC ? 1 : null,
            'engenheiro_art' => $etapa === EtapaVistoria::CEDEC ? 'ART-777' : null,
            'itens' => [],
        ], $sobrescreve));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=VistoriaServiceTest`
Expected: FAIL com `Class "App\Modules\Cisterna\DTOs\VistoriaDTO" not found`.

- [ ] **Step 3: Escrever `ItemConferidoDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;

final readonly class ItemConferidoDTO
{
    /**
     * @param  array<string, string>|null  $detalhes
     */
    public function __construct(
        public ItemInstalacao $item,
        public bool $conferido,
        public ?float $quantidade = null,
        public ?array $detalhes = null,
        public ?string $observacao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(string $item, array $d): self
    {
        $enum = ItemInstalacao::from($item);

        // Detalhes so fazem sentido em fixacao. Em qualquer outro item o
        // valor e descartado, em vez de sujar o jsonb.
        $detalhes = null;
        if ($enum->aceitaDetalhes() && ! empty($d['detalhes']) && is_array($d['detalhes'])) {
            $detalhes = array_map(fn ($v): string => (string) $v, $d['detalhes']);
        }

        return new self(
            item: $enum,
            conferido: NormalizaEntrada::booleanoSimNao($d['conferido'] ?? null) ?? false,
            quantidade: NormalizaEntrada::decimal($d['quantidade'] ?? null),
            detalhes: $detalhes,
            observacao: $d['observacao'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // A unidade nao vem do formulario: e propriedade do item. Calha e
        // tubulacao em metros, pecas de PVC em unidades, o resto sem
        // quantidade. No legado a mesma peca aparecia como calha_metros numa
        // tabela e qtd_calha noutra.
        $unidade = $this->quantidade === null ? null : $this->item->unidadePadrao();

        return [
            'item' => $this->item->value,
            'conferido' => $this->conferido,
            'quantidade' => $this->quantidade,
            'unidade' => $unidade?->value,
            'detalhes' => $this->detalhes,
            'observacao' => $this->observacao,
        ];
    }
}
```

- [ ] **Step 4: Escrever `VistoriaDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Support\NormalizaEntrada;

final readonly class VistoriaDTO
{
    /**
     * @param  array<int, ItemConferidoDTO>  $itens
     */
    public function __construct(
        public int $beneficiarioId,
        public EtapaVistoria $etapa,
        public ?int $numeroInstalacao = null,
        public ?string $engenheiroNome = null,
        public ?string $engenheiroCrea = null,
        public ?string $engenheiroArt = null,
        public ?string $dataRelatorio = null,
        public ?string $localRelatorio = null,
        public ?string $processoSei = null,
        public ?string $contrato = null,
        public ?string $empenho = null,
        public ?int $placaObras = null,
        public ?string $endereco = null,
        public ?string $bairro = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
        private array $itens = [],
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        $etapa = EtapaVistoria::from((string) $d['etapa']);

        return new self(
            beneficiarioId: (int) $d['beneficiario_id'],
            etapa: $etapa,
            numeroInstalacao: isset($d['numero_instalacao']) && $d['numero_instalacao'] !== null
                ? (int) $d['numero_instalacao']
                : null,
            engenheiroNome: $d['engenheiro_nome'] ?? null,
            engenheiroCrea: $d['engenheiro_crea'] ?? null,
            // Somente a etapa CEDEC tem ART.
            engenheiroArt: $etapa->exigeDadosAdministrativos() ? ($d['engenheiro_art'] ?? null) : null,
            dataRelatorio: $d['data_relatorio'] ?? null,
            localRelatorio: $d['local_relatorio'] ?? null,
            processoSei: $etapa->exigeDadosAdministrativos() ? ($d['processo_sei'] ?? null) : null,
            contrato: $etapa->exigeDadosAdministrativos() ? ($d['contrato'] ?? null) : null,
            empenho: $etapa->exigeDadosAdministrativos() ? ($d['empenho'] ?? null) : null,
            placaObras: $etapa->exigeDadosAdministrativos() && isset($d['placa_obras'])
                ? (int) $d['placa_obras']
                : null,
            endereco: $d['endereco'] ?? null,
            bairro: $d['bairro'] ?? null,
            latitude: NormalizaEntrada::decimal($d['latitude'] ?? null),
            longitude: NormalizaEntrada::decimal($d['longitude'] ?? null),
            observacoes: $d['observacoes'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
            itens: self::extrairItens($d['itens'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'beneficiario_id' => $this->beneficiarioId,
            'etapa' => $this->etapa->value,
            'numero_instalacao' => $this->numeroInstalacao,
            'engenheiro_nome' => $this->engenheiroNome,
            'engenheiro_crea' => $this->engenheiroCrea,
            'engenheiro_art' => $this->engenheiroArt,
            'data_relatorio' => $this->dataRelatorio,
            'local_relatorio' => $this->localRelatorio,
            'processo_sei' => $this->processoSei,
            'contrato' => $this->contrato,
            'empenho' => $this->empenho,
            'placa_obras' => $this->placaObras,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * @return array<int, ItemConferidoDTO>
     */
    public function itens(): array
    {
        return $this->itens;
    }

    /**
     * @param  array<string, array<string, mixed>>  $itens
     * @return array<int, ItemConferidoDTO>
     */
    private static function extrairItens(array $itens): array
    {
        $lista = [];

        foreach ($itens as $chave => $dados) {
            if (! is_array($dados)) {
                continue;
            }

            $lista[] = ItemConferidoDTO::deValidados((string) $chave, $dados);
        }

        return $lista;
    }
}
```

- [ ] **Step 5: Escrever `VistoriaService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A cadeia fornecedor -> COMPDEC -> CEDEC.
 *
 * No legado eram seis metodos de controller com validacao e upload
 * duplicados, e a criacao da linha vazia do COMPDEC acontecia como efeito
 * colateral dentro do store do fornecedor
 * (CisternaController.php:1682). Aqui cada etapa e aberta
 * explicitamente, e a ordem e verificada.
 */
class VistoriaService
{
    public function __construct(
        private readonly NumeracaoInstalacaoService $numeracao,
    ) {}

    /**
     * Qual etapa pode ser trabalhada agora. Null quando a cadeia terminou.
     */
    public function etapaDisponivel(CisternaBeneficiario $beneficiario): ?EtapaVistoria
    {
        $vistorias = $beneficiario->vistorias()->get(['id', 'etapa', 'concluida_em']);

        foreach (EtapaVistoria::cases() as $etapa) {
            $vistoria = $vistorias->firstWhere('etapa', $etapa);

            // Etapa ainda nao aberta, ou aberta e nao concluida: e a atual.
            if ($vistoria === null || ! $vistoria->estaConcluida()) {
                return $etapa;
            }
        }

        return null;
    }

    public function abrir(VistoriaDTO $dto): CisternaVistoria
    {
        $beneficiario = CisternaBeneficiario::findOrFail($dto->beneficiarioId);

        $this->garantirOrdemDaCadeia($beneficiario, $dto->etapa);

        return DB::transaction(function () use ($dto, $beneficiario): CisternaVistoria {
            $atributos = $dto->toArray();

            // So a etapa do fornecedor aloca numero de QR Code.
            $atributos['numero_instalacao'] = $dto->etapa->alocaNumeroInstalacao()
                ? $this->numeracao->reservar($dto->numeroInstalacao)
                : null;

            // Snapshot do endereco: o cadastro pode mudar depois.
            $atributos['endereco'] ??= $beneficiario->endereco;
            $atributos['latitude'] ??= $beneficiario->latitude === null ? null : (float) $beneficiario->latitude;
            $atributos['longitude'] ??= $beneficiario->longitude === null ? null : (float) $beneficiario->longitude;

            $vistoria = CisternaVistoria::create($atributos);

            $this->sincronizarItens($vistoria, $dto->itens());

            return $vistoria->load('itensConferidos');
        });
    }

    public function atualizar(CisternaVistoria $vistoria, VistoriaDTO $dto): CisternaVistoria
    {
        return DB::transaction(function () use ($vistoria, $dto): CisternaVistoria {
            $atributos = $dto->toArray();

            // A etapa e o beneficiario nao mudam na edicao.
            unset($atributos['etapa'], $atributos['beneficiario_id'], $atributos['legacy_id']);

            if ($vistoria->etapa->alocaNumeroInstalacao() && $dto->numeroInstalacao !== null) {
                $atributos['numero_instalacao'] = $this->numeracao->reservar(
                    $dto->numeroInstalacao,
                    $vistoria->id,
                );
            } else {
                unset($atributos['numero_instalacao']);
            }

            $vistoria->update($atributos);

            $this->sincronizarItens($vistoria, $dto->itens());

            return $vistoria->fresh(['itensConferidos', 'beneficiario']);
        });
    }

    /**
     * Marca a etapa como concluida. O legado inferia isso de `crea_mg`
     * preenchido e diferente de vazio, verificado com whereHas aninhado.
     *
     * @throws ValidationException quando faltam dados obrigatorios da etapa
     */
    public function concluir(CisternaVistoria $vistoria): CisternaVistoria
    {
        if ($vistoria->estaConcluida()) {
            return $vistoria;
        }

        $faltando = [];

        foreach (['engenheiro_nome' => 'nome do engenheiro', 'engenheiro_crea' => 'CREA do engenheiro'] as $campo => $rotulo) {
            if (blank($vistoria->{$campo})) {
                $faltando[$campo] = "Informe o {$rotulo} antes de concluir.";
            }
        }

        if ($vistoria->etapa->exigeDadosAdministrativos()) {
            $obrigatorios = [
                'processo_sei' => 'processo SEI',
                'contrato' => 'contrato',
                'empenho' => 'empenho',
                'engenheiro_art' => 'ART',
            ];

            foreach ($obrigatorios as $campo => $rotulo) {
                if (blank($vistoria->{$campo})) {
                    $faltando[$campo] = "Informe o {$rotulo} antes de concluir a fiscalizacao da CEDEC.";
                }
            }
        }

        if ($faltando !== []) {
            throw ValidationException::withMessages($faltando);
        }

        $vistoria->update(['concluida_em' => now()]);

        return $vistoria->fresh();
    }

    /**
     * Substitui o conjunto de itens, nao acumula: o formulario envia sempre o
     * checklist completo.
     *
     * @param  array<int, \App\Modules\Cisterna\DTOs\ItemConferidoDTO>  $itens
     */
    public function sincronizarItens(CisternaVistoria $vistoria, array $itens): void
    {
        if ($itens === []) {
            return;
        }

        $vistoria->itensConferidos()->delete();

        foreach ($itens as $item) {
            $vistoria->itensConferidos()->create($item->toArray());
        }
    }

    /**
     * @throws ValidationException
     */
    private function garantirOrdemDaCadeia(CisternaBeneficiario $beneficiario, EtapaVistoria $etapa): void
    {
        if ($beneficiario->vistoriaDaEtapa($etapa) !== null) {
            throw ValidationException::withMessages([
                'etapa' => "A etapa \"{$etapa->label()}\" ja foi aberta para este beneficiario.",
            ]);
        }

        $disponivel = $this->etapaDisponivel($beneficiario);

        if ($disponivel !== $etapa) {
            $esperada = $disponivel?->label() ?? 'nenhuma etapa pendente';

            throw ValidationException::withMessages([
                'etapa' => "Etapa fora de ordem. A proxima etapa e: {$esperada}.",
            ]);
        }
    }
}
```

- [ ] **Step 6: Escrever o Observer**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Observers;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaVistoria;

/**
 * Ao gravar a vistoria do fornecedor, a obra passa a Instalado.
 *
 * No legado isso era efeito colateral dentro do controller
 * (CisternaController.php:1681), junto com a criacao da linha vazia do
 * COMPDEC. Aqui fica no observer, e vale tambem para o refino do ETL.
 */
class CisternaVistoriaObserver
{
    public function created(CisternaVistoria $vistoria): void
    {
        if ($vistoria->etapa !== EtapaVistoria::FORNECEDOR) {
            return;
        }

        $beneficiario = $vistoria->beneficiario;

        if ($beneficiario === null || $beneficiario->situacao_obra === SituacaoObra::INSTALADO) {
            return;
        }

        $beneficiario->update(['situacao_obra' => SituacaoObra::INSTALADO->value]);
    }
}
```

- [ ] **Step 7: Registrar o observer no ServiceProvider**

Em `app/Modules/Cisterna/CisternaServiceProvider.php`, dentro de `boot()`:

```php
        CisternaVistoria::observe(CisternaVistoriaObserver::class);
```

Com os imports `use App\Modules\Cisterna\Models\CisternaVistoria;` e `use App\Modules\Cisterna\Observers\CisternaVistoriaObserver;`.

- [ ] **Step 8: Escrever `StoreVistoriaRequest`**

Um Request unico com `rules()` variando por etapa. Tres Requests separados duplicariam cerca de 30 regras identicas.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVistoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaVistoria::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $etapa = EtapaVistoria::tryFrom((string) $this->input('etapa'));

        $regras = [
            'beneficiario_id' => ['required', 'integer', 'exists:cisterna_beneficiarios,id'],
            'etapa' => ['required', Rule::in(EtapaVistoria::valores())],

            'engenheiro_nome' => ['required', 'string', 'max:150'],
            'engenheiro_crea' => ['required', 'string', 'max:30'],
            'data_relatorio' => ['required', 'date'],
            'local_relatorio' => ['required', 'string', 'max:255'],

            'endereco' => ['nullable', 'string', 'max:150'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'observacoes' => ['nullable', 'string', 'max:1000'],

            // Checklist. As chaves do array sao os valores do enum.
            'itens' => ['nullable', 'array'],
            'itens.*.conferido' => ['required', 'boolean'],
            'itens.*.quantidade' => ['nullable', 'numeric', 'min:0'],
            'itens.*.detalhes' => ['nullable', 'array'],
            'itens.*.detalhes.*' => ['nullable', 'string', 'max:30'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],

            'assinatura_engenheiro' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fotos_vistoria' => ['nullable', 'array', 'max:40'],
            'fotos_vistoria.*.arquivo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'fotos_vistoria.*.item' => ['required', Rule::in(ItemInstalacao::valores())],
            'fotos_vistoria.*.sequencia' => ['required', 'integer', 'min:1', 'max:2'],
        ];

        // O numero de instalacao e opcional: quando ausente, o
        // NumeracaoInstalacaoService pega o proximo da sequence.
        if ($etapa?->alocaNumeroInstalacao() === true) {
            $regras['numero_instalacao'] = ['nullable', 'integer', 'min:1'];
        } else {
            $regras['numero_instalacao'] = ['prohibited'];
        }

        // Dados administrativos so na etapa CEDEC. Obrigatorios ali, e
        // proibidos nas outras — no legado a validacao nao impedia enviar
        // processo_sei numa vistoria de fornecedor.
        if ($etapa?->exigeDadosAdministrativos() === true) {
            $regras['processo_sei'] = ['required', 'string', 'max:100'];
            $regras['contrato'] = ['required', 'string', 'max:100'];
            $regras['empenho'] = ['required', 'string', 'max:100'];
            $regras['placa_obras'] = ['required', 'integer', 'min:0'];
            $regras['engenheiro_art'] = ['required', 'string', 'max:50'];
        } else {
            foreach (['processo_sei', 'contrato', 'empenho', 'placa_obras', 'engenheiro_art'] as $campo) {
                $regras[$campo] = ['prohibited'];
            }
        }

        return $regras;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'etapa.required' => 'Informe a etapa da vistoria.',
            'engenheiro_nome.required' => 'O nome do engenheiro responsavel e obrigatorio.',
            'engenheiro_crea.required' => 'O CREA do engenheiro e obrigatorio.',
            'processo_sei.required' => 'O processo SEI e obrigatorio na fiscalizacao da CEDEC.',
            'engenheiro_art.required' => 'A ART e obrigatoria na fiscalizacao da CEDEC.',
            'numero_instalacao.prohibited' => 'Somente a vistoria do fornecedor recebe numero de instalacao.',
            'processo_sei.prohibited' => 'Dados administrativos pertencem apenas a etapa da CEDEC.',
        ];
    }
}
```

- [ ] **Step 9: Escrever `UpdateVistoriaRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

/**
 * Na edicao a etapa e o beneficiario nao mudam: sao lidos da vistoria em
 * rota, nao do corpo do request.
 */
class UpdateVistoriaRequest extends StoreVistoriaRequest
{
    public function authorize(): bool
    {
        $vistoria = $this->route('vistoria');

        return $vistoria !== null && ($this->user()?->can('update', $vistoria) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $vistoria = $this->route('vistoria');

        if ($vistoria === null) {
            return;
        }

        // Injeta etapa e beneficiario a partir da rota, para rules() escolher
        // o conjunto certo de regras.
        $this->merge([
            'etapa' => $vistoria->etapa->value,
            'beneficiario_id' => $vistoria->beneficiario_id,
        ]);
    }
}
```

- [ ] **Step 10: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=VistoriaServiceTest`
Expected: PASS, 14 testes.

- [ ] **Step 11: Rodar a suite do modulo**

Run: `scripts/test-host.sh --filter=Cisterna`
Expected: PASS em tudo o que existe ate aqui.

- [ ] **Step 12: Commit**

```bash
git add app/Modules/Cisterna/DTOs/VistoriaDTO.php \
        app/Modules/Cisterna/DTOs/ItemConferidoDTO.php \
        app/Modules/Cisterna/Requests/StoreVistoriaRequest.php \
        app/Modules/Cisterna/Requests/UpdateVistoriaRequest.php \
        app/Modules/Cisterna/Services/VistoriaService.php \
        app/Modules/Cisterna/Observers/CisternaVistoriaObserver.php \
        app/Modules/Cisterna/CisternaServiceProvider.php \
        tests/Feature/Cisterna/VistoriaServiceTest.php
git commit -m "✨ feat(cisterna): cadeia de vistoria fornecedor-compdec-cedec com checklist unificado"
```

---

### Task 11: Comunidades, lotes, ordens de servico e a timeline do lote

Tres CRUDs simples mais a feature de historico do lote (lacuna L6 do spec). Corrige o defeito C18: a contagem de cadastros por comunidade passa a usar `comunidade_id`, nao o nome.

**Files:**
- Create: `app/Modules/Cisterna/DTOs/ComunidadeDTO.php`
- Create: `app/Modules/Cisterna/DTOs/LoteDTO.php`
- Create: `app/Modules/Cisterna/DTOs/OrdemServicoDTO.php`
- Create: `app/Modules/Cisterna/Requests/StoreComunidadeRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateComunidadeRequest.php`
- Create: `app/Modules/Cisterna/Requests/StoreLoteRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateLoteRequest.php`
- Create: `app/Modules/Cisterna/Requests/StoreOrdemServicoRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateOrdemServicoRequest.php`
- Create: `app/Modules/Cisterna/Services/ComunidadeService.php`
- Create: `app/Modules/Cisterna/Services/LoteService.php`
- Create: `app/Modules/Cisterna/Services/OrdemServicoService.php`
- Test: `tests/Feature/Cisterna/ComunidadeLoteOsServiceTest.php`

**Interfaces:**
- Consumes: models da Task 4, `Municipio::habilitadosCisterna()` (Task 5)
- Produces:
  - `ComunidadeService::listar(array $filtros = [], int $porPagina = 50): LengthAwarePaginator` — cada linha com `beneficiarios_count`
  - `ComunidadeService::doMunicipio(int): Collection`
  - `ComunidadeService::criar(ComunidadeDTO)`, `atualizar(CisternaComunidade, ComunidadeDTO)`, `deletar(CisternaComunidade): bool`
  - `LoteService::listar(int $porPagina = 25)`, `criar(LoteDTO)`, `atualizar(CisternaLote, LoteDTO)`, `deletar(CisternaLote): bool`
  - `OrdemServicoService::listar(?int $loteId = null, int $porPagina = 25)`, `criar(OrdemServicoDTO)`, `atualizar(...)`, `deletar(...): bool`
  - `OrdemServicoService::timeline(CisternaOrdemServico): Collection` de `array{data: string, tipo: string, descricao: string, usuario: ?string, beneficiario_id: ?int, beneficiario_nome: ?string}`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/ComunidadeLoteOsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\User;
use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\DTOs\LoteDTO;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\LoteService;
use App\Modules\Cisterna\Services\OrdemServicoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComunidadeLoteOsServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function test_comunidades_homonimas_em_municipios_diferentes_coexistem(): void
    {
        $service = app(ComunidadeService::class);

        $primeira = $service->criar(new ComunidadeDTO($this->municipios[0], 'Sao Jose'));
        $segunda = $service->criar(new ComunidadeDTO($this->municipios[1], 'Sao Jose'));

        $this->assertNotSame($primeira->id, $segunda->id);
        $this->assertSame('Sao Jose', $primeira->nome);
        $this->assertSame('Sao Jose', $segunda->nome);
    }

    public function test_comunidade_duplicada_no_mesmo_municipio_e_rejeitada(): void
    {
        $service = app(ComunidadeService::class);
        $service->criar(new ComunidadeDTO($this->municipios[0], 'Boa Vista'));

        $this->expectException(ValidationException::class);
        $service->criar(new ComunidadeDTO($this->municipios[0], 'Boa Vista'));
    }

    public function test_contagem_de_beneficiarios_usa_a_fk_nao_o_nome(): void
    {
        $service = app(ComunidadeService::class);

        $daA = $service->criar(new ComunidadeDTO($this->municipios[0], 'Sao Jose'));
        $daB = $service->criar(new ComunidadeDTO($this->municipios[1], 'Sao Jose'));

        CisternaBeneficiario::factory()->count(3)->create([
            'municipio_id' => $this->municipios[0],
            'comunidade_id' => $daA->id,
        ]);
        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[1],
            'comunidade_id' => $daB->id,
        ]);

        $pagina = $service->listar(['municipio_id' => $this->municipios[0]]);
        $linha = collect($pagina->items())->firstWhere('id', $daA->id);

        // O legado somaria 4 aqui, porque joinava por nome.
        $this->assertSame(3, (int) $linha->beneficiarios_count);
    }

    public function test_do_municipio_devolve_apenas_as_ativas_ordenadas(): void
    {
        $service = app(ComunidadeService::class);

        $service->criar(new ComunidadeDTO($this->municipios[0], 'Zebu'));
        $service->criar(new ComunidadeDTO($this->municipios[0], 'Agua Limpa'));
        $inativa = $service->criar(new ComunidadeDTO($this->municipios[0], 'Desativada'));
        $service->atualizar($inativa, new ComunidadeDTO($this->municipios[0], 'Desativada', false));

        $nomes = $service->doMunicipio($this->municipios[0])->pluck('nome')->all();

        $this->assertSame(['Agua Limpa', 'Zebu'], $nomes);
    }

    public function test_nao_deleta_comunidade_com_beneficiario_vinculado(): void
    {
        $service = app(ComunidadeService::class);
        $comunidade = $service->criar(new ComunidadeDTO($this->municipios[0], 'Com Vinculo'));

        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[0],
            'comunidade_id' => $comunidade->id,
        ]);

        $this->expectException(ValidationException::class);
        $service->deletar($comunidade);
    }

    public function test_lote_listado_traz_a_contagem_de_ordens_de_servico(): void
    {
        $service = app(LoteService::class);
        $lote = $service->criar(new LoteDTO('Lote 001/2026', now()->toDateString()));

        CisternaOrdemServico::factory()->count(2)->create(['lote_id' => $lote->id]);

        $linha = collect($service->listar()->items())->firstWhere('id', $lote->id);

        $this->assertSame(2, (int) $linha->ordens_servico_count);
    }

    public function test_nao_deleta_lote_com_ordem_de_servico(): void
    {
        $service = app(LoteService::class);
        $lote = $service->criar(new LoteDTO('Lote 002/2026'));
        CisternaOrdemServico::factory()->create(['lote_id' => $lote->id]);

        $this->expectException(ValidationException::class);
        $service->deletar($lote);
    }

    public function test_ordens_de_servico_filtram_por_lote(): void
    {
        $service = app(OrdemServicoService::class);
        $loteA = CisternaLote::factory()->create();
        $loteB = CisternaLote::factory()->create();

        $service->criar(new OrdemServicoDTO($loteA->id, 'OS 1'));
        $service->criar(new OrdemServicoDTO($loteA->id, 'OS 2'));
        $service->criar(new OrdemServicoDTO($loteB->id, 'OS 3'));

        $this->assertSame(2, $service->listar($loteA->id)->total());
    }

    public function test_nao_deleta_os_com_beneficiario_alocado(): void
    {
        $service = app(OrdemServicoService::class);
        $os = CisternaOrdemServico::factory()->create();
        CisternaBeneficiario::factory()->create(['ordem_servico_id' => $os->id]);

        $this->expectException(ValidationException::class);
        $service->deletar($os);
    }

    public function test_timeline_do_lote_inclui_entrada_e_saida_de_beneficiario(): void
    {
        $this->actingAs(User::factory()->create());

        $service = app(OrdemServicoService::class);
        $os = CisternaOrdemServico::factory()->create();
        $outraOs = CisternaOrdemServico::factory()->create();

        $beneficiario = CisternaBeneficiario::factory()->create([
            'nome' => 'Maria Movimentada',
            'ordem_servico_id' => null,
        ]);

        // Entrada.
        $beneficiario->update(['ordem_servico_id' => $os->id]);
        // Saida para outra OS.
        $beneficiario->update(['ordem_servico_id' => $outraOs->id]);

        $timeline = $service->timeline($os->fresh());

        $this->assertGreaterThanOrEqual(2, $timeline->count());

        $movimentacoes = $timeline->where('beneficiario_id', $beneficiario->id);
        $this->assertGreaterThanOrEqual(2, $movimentacoes->count());
        $this->assertSame('Maria Movimentada', $movimentacoes->first()['beneficiario_nome']);
    }

    public function test_timeline_ignora_movimentacao_de_outra_os(): void
    {
        $this->actingAs(User::factory()->create());

        $service = app(OrdemServicoService::class);
        $osObservada = CisternaOrdemServico::factory()->create();
        $osAlheia = CisternaOrdemServico::factory()->create();

        $beneficiario = CisternaBeneficiario::factory()->create(['ordem_servico_id' => null]);
        $beneficiario->update(['ordem_servico_id' => $osAlheia->id]);

        $timeline = $service->timeline($osObservada->fresh());

        $this->assertCount(0, $timeline->whereNotNull('beneficiario_id'));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=ComunidadeLoteOsServiceTest`
Expected: FAIL com `Class "App\Modules\Cisterna\DTOs\ComunidadeDTO" not found`.

- [ ] **Step 3: Escrever os tres DTOs**

`ComunidadeDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class ComunidadeDTO
{
    public function __construct(
        public int $municipioId,
        public string $nome,
        public bool $ativa = true,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            municipioId: (int) $d['municipio_id'],
            nome: trim((string) $d['nome']),
            ativa: (bool) ($d['ativa'] ?? true),
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id' => $this->municipioId,
            'nome' => $this->nome,
            'ativa' => $this->ativa,
            'legacy_id' => $this->legacyId,
        ];
    }
}
```

`LoteDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class LoteDTO
{
    public function __construct(
        public string $nome,
        public ?string $data = null,
        public ?string $observacao = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            nome: trim((string) $d['nome']),
            data: $d['data'] ?? null,
            observacao: $d['observacao'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'data' => $this->data,
            'observacao' => $this->observacao,
            'legacy_id' => $this->legacyId,
        ];
    }
}
```

`OrdemServicoDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class OrdemServicoDTO
{
    public function __construct(
        public int $loteId,
        public string $nome,
        public ?string $observacao = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            loteId: (int) $d['lote_id'],
            nome: trim((string) $d['nome']),
            observacao: $d['observacao'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lote_id' => $this->loteId,
            'nome' => $this->nome,
            'observacao' => $this->observacao,
            'legacy_id' => $this->legacyId,
        ];
    }
}
```

- [ ] **Step 4: Escrever `ComunidadeService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\Models\CisternaComunidade;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ComunidadeService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = [], int $porPagina = 50): LengthAwarePaginator
    {
        return CisternaComunidade::query()
            ->with('municipio:id,nome,uf')
            // Corrige o defeito C18: o legado contava com
            // leftJoin('sinc_cisterna', 'comunidade', '=', 'comunidade'), sem
            // o municipio, somando homonimos de municipios distintos.
            ->withCount('beneficiarios')
            ->when($filtros['municipio_id'] ?? null, fn (Builder $q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, function (Builder $q, $termo): void {
                $q->where('nome', 'ilike', '%'.trim((string) $termo).'%');
            })
            ->when(($filtros['apenas_ativas'] ?? false) === true, fn (Builder $q) => $q->ativas())
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * Usado pelo select em cascata do formulario: escolhido o municipio,
     * carrega as comunidades dele.
     *
     * @return Collection<int, CisternaComunidade>
     */
    public function doMunicipio(int $municipioId): Collection
    {
        return CisternaComunidade::query()
            ->where('municipio_id', $municipioId)
            ->ativas()
            ->orderBy('nome')
            ->get(['id', 'municipio_id', 'nome']);
    }

    public function criar(ComunidadeDTO $dto): CisternaComunidade
    {
        $this->garantirNomeInedito($dto);

        return CisternaComunidade::create($dto->toArray());
    }

    public function atualizar(CisternaComunidade $comunidade, ComunidadeDTO $dto): CisternaComunidade
    {
        $this->garantirNomeInedito($dto, $comunidade->id);

        $comunidade->update($dto->toArray());

        return $comunidade->fresh('municipio');
    }

    /**
     * @throws ValidationException quando ha beneficiario vinculado
     */
    public function deletar(CisternaComunidade $comunidade): bool
    {
        $vinculados = $comunidade->beneficiarios()->count();

        if ($vinculados > 0) {
            throw ValidationException::withMessages([
                'comunidade' => "Nao e possivel excluir: {$vinculados} beneficiario(s) vinculado(s) a esta comunidade.",
            ]);
        }

        return (bool) $comunidade->delete();
    }

    /**
     * @throws ValidationException
     */
    private function garantirNomeInedito(ComunidadeDTO $dto, ?int $ignorarId = null): void
    {
        $existe = CisternaComunidade::query()
            ->where('municipio_id', $dto->municipioId)
            ->where('nome', $dto->nome)
            ->when($ignorarId !== null, fn (Builder $q) => $q->whereKeyNot($ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nome' => 'Esta comunidade ja esta cadastrada neste municipio.',
            ]);
        }
    }
}
```

- [ ] **Step 5: Escrever `LoteService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\LoteDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LoteService
{
    public function listar(int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaLote::query()
            ->withCount('ordensServico')
            ->orderByDesc('data')
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function criar(LoteDTO $dto): CisternaLote
    {
        return CisternaLote::create($dto->toArray());
    }

    public function atualizar(CisternaLote $lote, LoteDTO $dto): CisternaLote
    {
        $lote->update($dto->toArray());

        return $lote->fresh();
    }

    /**
     * @throws ValidationException quando ha ordem de servico vinculada
     */
    public function deletar(CisternaLote $lote): bool
    {
        $ordens = $lote->ordensServico()->count();

        if ($ordens > 0) {
            throw ValidationException::withMessages([
                'lote' => "Nao e possivel excluir: {$ordens} ordem(ns) de servico vinculada(s) a este lote.",
            ]);
        }

        return (bool) $lote->delete();
    }
}
```

- [ ] **Step 6: Escrever `OrdemServicoService` com a timeline**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrdemServicoService
{
    public function listar(?int $loteId = null, int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaOrdemServico::query()
            ->with('lote:id,nome')
            ->withCount('beneficiarios')
            ->when($loteId !== null, fn ($q) => $q->where('lote_id', $loteId))
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function criar(OrdemServicoDTO $dto): CisternaOrdemServico
    {
        return CisternaOrdemServico::create($dto->toArray());
    }

    public function atualizar(CisternaOrdemServico $os, OrdemServicoDTO $dto): CisternaOrdemServico
    {
        $os->update($dto->toArray());

        return $os->fresh('lote');
    }

    /**
     * @throws ValidationException quando ha beneficiario alocado
     */
    public function deletar(CisternaOrdemServico $os): bool
    {
        $alocados = $os->beneficiarios()->count();

        if ($alocados > 0) {
            throw ValidationException::withMessages([
                'ordem_servico' => "Nao e possivel excluir: {$alocados} beneficiario(s) alocado(s) nesta ordem de servico.",
            ]);
        }

        return (bool) $os->delete();
    }

    /**
     * Historico do lote: quem entrou e quem saiu.
     *
     * Mescla a trilha da propria OS com as movimentacoes de beneficiarios
     * cujo campo alterado foi `ordem_servico_id`, apontando de ou para esta
     * OS. O legado fazia isso com whereJsonContains sobre
     * valores_novos->os_id, e precisava testar string E int porque o campo era
     * gravado das duas formas (CisternaOrdemServicoController.php:44-53).
     * Aqui a coluna e integer com FK, entao a comparacao e direta.
     *
     * @return Collection<int, array{
     *     data: string,
     *     tipo: string,
     *     descricao: string,
     *     usuario: ?string,
     *     beneficiario_id: ?int,
     *     beneficiario_nome: ?string
     * }>
     */
    public function timeline(CisternaOrdemServico $os): Collection
    {
        $daOrdem = $os->trilhaDeAcoes()
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($registro): array => [
                'data' => $registro->created_at->toIso8601String(),
                'tipo' => 'ordem_servico',
                'descricao' => $this->descreverAlteracao($registro),
                'usuario' => $registro->usuario?->name,
                'beneficiario_id' => null,
                'beneficiario_nome' => null,
            ]);

        $movimentacoes = CisternaBeneficiario::trilhaDoCampoApontandoPara('ordem_servico_id', $os->getKey())
            ->map(fn (array $mov): array => [
                'data' => $mov['data'],
                'tipo' => $mov['entrou'] ? 'beneficiario_entrou' : 'beneficiario_saiu',
                'descricao' => $mov['entrou']
                    ? "{$mov['beneficiario_nome']} foi alocado nesta ordem de servico."
                    : "{$mov['beneficiario_nome']} saiu desta ordem de servico.",
                'usuario' => $mov['usuario'],
                'beneficiario_id' => $mov['beneficiario_id'],
                'beneficiario_nome' => $mov['beneficiario_nome'],
            ]);

        return $daOrdem->concat($movimentacoes)
            ->sortByDesc('data')
            ->values();
    }

    private function descreverAlteracao(object $registro): string
    {
        $campo = $registro->campo ?? null;

        if ($campo === null) {
            return 'Ordem de servico registrada.';
        }

        return "Campo \"{$campo}\" alterado de \"{$registro->valor_antigo}\" para \"{$registro->valor_novo}\".";
    }
}
```

**Nota de integracao.** `trilhaDeAcoes()` e `trilhaDoCampoApontandoPara()` sao a interface com o modulo `Notificacoes`. Antes de escrever este service, abrir `app/Modules/Notificacoes/Support/TrilhaDeAcoes.php` e conferir os nomes reais dos metodos e das colunas do registro de trilha (`campo`, `valor_antigo`, `valor_novo`, `usuario`, `created_at`). Se a trait nao expuser um helper equivalente a `trilhaDoCampoApontandoPara`, acrescentar um metodo estatico em `CisternaBeneficiario`:

```php
    /**
     * Movimentacoes de beneficiarios cujo campo informado passou a apontar
     * para, ou deixou de apontar para, o valor dado.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     data: string, entrou: bool, usuario: ?string,
     *     beneficiario_id: int, beneficiario_nome: string
     * }>
     */
    public static function trilhaDoCampoApontandoPara(string $campo, int|string $valor): \Illuminate\Support\Collection
    {
        $registros = static::consultarTrilhaDeAcoes()
            ->where('campo', $campo)
            ->where(function ($q) use ($valor): void {
                $q->where('valor_novo', (string) $valor)
                    ->orWhere('valor_antigo', (string) $valor);
            })
            ->orderByDesc('created_at')
            ->get();

        $nomes = static::whereIn('id', $registros->pluck('registravel_id')->unique())
            ->pluck('nome', 'id');

        return $registros->map(fn ($r): array => [
            'data' => $r->created_at->toIso8601String(),
            'entrou' => (string) $r->valor_novo === (string) $valor,
            'usuario' => $r->usuario?->name,
            'beneficiario_id' => (int) $r->registravel_id,
            'beneficiario_nome' => $nomes[$r->registravel_id] ?? 'Beneficiario #'.$r->registravel_id,
        ]);
    }
```

O nome de `consultarTrilhaDeAcoes()` tambem sai da leitura da trait. Ajustar os dois metodos a API real antes de rodar o teste — esta e a unica parte do plano que depende de uma interface que nao foi lida na fase de levantamento.

- [ ] **Step 7: Escrever os seis FormRequests**

`StoreComunidadeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Models\CisternaComunidade;
use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaComunidade::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'nome' => ['required', 'string', 'max:70'],
            'ativa' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'municipio_id.required' => 'Selecione o municipio da comunidade.',
            'nome.required' => 'O nome da comunidade e obrigatorio.',
            'nome.max' => 'O nome da comunidade deve ter no maximo 70 caracteres.',
        ];
    }
}
```

`UpdateComunidadeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateComunidadeRequest extends StoreComunidadeRequest
{
    public function authorize(): bool
    {
        $comunidade = $this->route('comunidade');

        return $comunidade !== null && ($this->user()?->can('update', $comunidade) ?? false);
    }
}
```

`StoreLoteRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Models\CisternaLote;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaLote::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'data' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['nome.required' => 'O nome do lote e obrigatorio.'];
    }
}
```

`UpdateLoteRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateLoteRequest extends StoreLoteRequest
{
    public function authorize(): bool
    {
        $lote = $this->route('lote');

        return $lote !== null && ($this->user()?->can('update', $lote) ?? false);
    }
}
```

`StoreOrdemServicoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaOrdemServico::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lote_id' => ['required', 'integer', 'exists:cisterna_lotes,id'],
            'nome' => ['required', 'string', 'max:255'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            // Legado: coluna link_doc. Agora e a collection documento_os.
            'documento_os' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lote_id.required' => 'Selecione o lote da ordem de servico.',
            'nome.required' => 'O nome da ordem de servico e obrigatorio.',
        ];
    }
}
```

`UpdateOrdemServicoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateOrdemServicoRequest extends StoreOrdemServicoRequest
{
    public function authorize(): bool
    {
        $os = $this->route('ordemServico');

        return $os !== null && ($this->user()?->can('update', $os) ?? false);
    }
}
```

- [ ] **Step 8: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=ComunidadeLoteOsServiceTest`
Expected: PASS, 11 testes.

Se `test_timeline_do_lote_inclui_entrada_e_saida_de_beneficiario` falhar com metodo indefinido, e a interface da trilha (nota do Step 6): ajustar os nomes a `TrilhaDeAcoes` real e rodar de novo.

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Cisterna/DTOs/ComunidadeDTO.php \
        app/Modules/Cisterna/DTOs/LoteDTO.php \
        app/Modules/Cisterna/DTOs/OrdemServicoDTO.php \
        app/Modules/Cisterna/Requests/Store{Comunidade,Lote,OrdemServico}Request.php \
        app/Modules/Cisterna/Requests/Update{Comunidade,Lote,OrdemServico}Request.php \
        app/Modules/Cisterna/Services/{Comunidade,Lote,OrdemServico}Service.php \
        app/Modules/Cisterna/Models/CisternaBeneficiario.php \
        tests/Feature/Cisterna/ComunidadeLoteOsServiceTest.php
git commit -m "✨ feat(cisterna): comunidades, lotes, ordens de servico e timeline do lote"
```

---

### Task 12: Notificacao de fiscalizacao

Corrige o defeito C1: o legado disparava `Mail::send` para `davifadaotr@gmail.com` hardcoded (`NotificacaoFiscalizacaoController.php:56`). Aqui o disparo usa o modulo `Notificacoes`, e a notificacao e polimorfica.

**Files:**
- Create: `app/Modules/Cisterna/DTOs/NotificacaoDTO.php`
- Create: `app/Modules/Cisterna/Requests/StoreNotificacaoRequest.php`
- Create: `app/Modules/Cisterna/Requests/UpdateNotificacaoRequest.php`
- Create: `app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php`
- Test: `tests/Feature/Cisterna/NotificacaoFiscalizacaoServiceTest.php`

**Interfaces:**
- Consumes: `CisternaNotificacao`, `CisternaBeneficiario`, `CisternaVistoria` (Task 4)
- Produces:
  - `NotificacaoDTO::deValidados(array): self` com `notificavelType`, `notificavelId`, `observacao`
  - `NotificacaoFiscalizacaoService::listar(array $filtros = [], int $porPagina = 25): LengthAwarePaginator`
  - `emitir(NotificacaoDTO, ?UploadedFile $arquivo = null): CisternaNotificacao`
  - `atualizar(CisternaNotificacao, NotificacaoDTO, ?UploadedFile $arquivo = null): CisternaNotificacao`
  - `responder(CisternaNotificacao, bool $respondida = true): CisternaNotificacao`
  - `deletar(CisternaNotificacao): bool`
  - `TIPOS_PERMITIDOS: array<string, class-string>`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/NotificacaoFiscalizacaoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\User;
use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NotificacaoFiscalizacaoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private NotificacaoFiscalizacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificacaoFiscalizacaoService::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_emite_notificacao_sobre_beneficiario(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $notificacao = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Documentacao incompleta.',
        ]));

        $this->assertSame(CisternaBeneficiario::class, $notificacao->notificavel_type);
        $this->assertFalse($notificacao->respondida);
        $this->assertNotNull($notificacao->created_by);
    }

    public function test_emite_notificacao_sobre_vistoria(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::COMPDEC)->create();

        $notificacao = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'vistoria',
            'notificavel_id' => $vistoria->id,
            'observacao' => 'Foto da bomba ilegivel.',
        ]));

        $this->assertSame(CisternaVistoria::class, $notificacao->notificavel_type);
        $this->assertInstanceOf(CisternaVistoria::class, $notificacao->notificavel);
    }

    public function test_tipo_notificavel_desconhecido_e_rejeitado(): void
    {
        $this->expectException(ValidationException::class);

        NotificacaoDTO::deValidados([
            'notificavel_type' => 'municipio',
            'notificavel_id' => 1,
            'observacao' => 'Teste',
        ]);
    }

    public function test_notificavel_inexistente_e_rejeitado(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => 99999999,
            'observacao' => 'Teste',
        ]));
    }

    public function test_arquivo_anexado_vai_para_a_collection_documentos(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $notificacao = $this->service->emitir(
            NotificacaoDTO::deValidados([
                'notificavel_type' => 'beneficiario',
                'notificavel_id' => $beneficiario->id,
                'observacao' => 'Ver oficio anexo.',
            ]),
            UploadedFile::fake()->create('oficio.pdf', 120, 'application/pdf'),
        );

        $this->assertCount(1, $notificacao->getMedia('documentos'));
        $this->assertSame('oficio.pdf', $notificacao->getMedia('documentos')->first()->file_name);
    }

    public function test_nao_dispara_email_para_endereco_hardcoded(): void
    {
        Mail::fake();
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Teste sem email direto.',
        ]));

        // O legado fazia Mail::send([], [], ...) para um Gmail pessoal.
        // O disparo agora e responsabilidade do modulo Notificacoes.
        Mail::assertNothingOutgoing();
    }

    public function test_responder_marca_data_e_e_idempotente(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        $notificacao = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Pendencia.',
        ]));

        $respondida = $this->service->responder($notificacao);
        $this->assertTrue($respondida->respondida);
        $this->assertNotNull($respondida->respondida_em);

        $primeiraData = $respondida->respondida_em->toIso8601String();
        $novamente = $this->service->responder($respondida);
        $this->assertSame($primeiraData, $novamente->respondida_em->toIso8601String());
    }

    public function test_reabrir_limpa_a_data_de_resposta(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        $notificacao = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Pendencia.',
        ]));

        $this->service->responder($notificacao);
        $reaberta = $this->service->responder($notificacao->fresh(), false);

        $this->assertFalse($reaberta->respondida);
        $this->assertNull($reaberta->respondida_em);
    }

    public function test_listar_filtra_por_pendentes(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();

        $pendente = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'Pendente.',
        ]));

        $outroBeneficiario = CisternaBeneficiario::factory()->create();
        $resolvida = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $outroBeneficiario->id,
            'observacao' => 'Resolvida.',
        ]));
        $this->service->responder($resolvida);

        $ids = collect($this->service->listar(['apenas_pendentes' => true])->items())
            ->pluck('id')->all();

        $this->assertContains($pendente->id, $ids);
        $this->assertNotContains($resolvida->id, $ids);
    }

    public function test_listar_filtra_por_notificavel(): void
    {
        $alvo = CisternaBeneficiario::factory()->create();
        $outro = CisternaBeneficiario::factory()->create();

        foreach ([$alvo, $outro] as $beneficiario) {
            $this->service->emitir(NotificacaoDTO::deValidados([
                'notificavel_type' => 'beneficiario',
                'notificavel_id' => $beneficiario->id,
                'observacao' => 'Teste.',
            ]));
        }

        $pagina = $this->service->listar([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $alvo->id,
        ]);

        $this->assertSame(1, $pagina->total());
    }

    public function test_deletar_remove_a_notificacao(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create();
        $notificacao = $this->service->emitir(NotificacaoDTO::deValidados([
            'notificavel_type' => 'beneficiario',
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'A remover.',
        ]));

        $this->assertTrue($this->service->deletar($notificacao));
        $this->assertNull(CisternaNotificacao::find($notificacao->id));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=NotificacaoFiscalizacaoServiceTest`
Expected: FAIL com `Class "App\Modules\Cisterna\DTOs\NotificacaoDTO" not found`.

- [ ] **Step 3: Escrever `NotificacaoDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Validation\ValidationException;

final readonly class NotificacaoDTO
{
    /**
     * Alias curto -> classe. O formulario nao envia FQCN, para nao expor a
     * estrutura interna nem aceitar classe arbitraria no morph.
     *
     * @var array<string, class-string>
     */
    public const TIPOS_PERMITIDOS = [
        'beneficiario' => CisternaBeneficiario::class,
        'vistoria' => CisternaVistoria::class,
    ];

    /**
     * @param  class-string  $notificavelType
     */
    public function __construct(
        public string $notificavelType,
        public int $notificavelId,
        public string $observacao,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     *
     * @throws ValidationException quando o alias nao e reconhecido
     */
    public static function deValidados(array $d): self
    {
        $alias = (string) ($d['notificavel_type'] ?? '');
        $classe = self::TIPOS_PERMITIDOS[$alias] ?? null;

        if ($classe === null) {
            throw ValidationException::withMessages([
                'notificavel_type' => 'Tipo de registro invalido para notificacao.',
            ]);
        }

        return new self(
            notificavelType: $classe,
            notificavelId: (int) $d['notificavel_id'],
            observacao: trim((string) $d['observacao']),
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notificavel_type' => $this->notificavelType,
            'notificavel_id' => $this->notificavelId,
            'observacao' => $this->observacao,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * Alias curto correspondente a classe, para devolver ao frontend.
     */
    public function alias(): string
    {
        return (string) array_search($this->notificavelType, self::TIPOS_PERMITIDOS, true);
    }
}
```

- [ ] **Step 4: Escrever `NotificacaoFiscalizacaoService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Notificacao de fiscalizacao, polimorfica.
 *
 * O legado disparava Mail::send([], [], ...) com HTML montado por
 * concatenacao para um Gmail pessoal hardcoded
 * (NotificacaoFiscalizacaoController.php:56). Aqui a notificacao e um
 * registro do dominio, e o aviso ao interessado e responsabilidade da trilha
 * do modulo Notificacoes, que ja resolve destinatario por perfil.
 */
class NotificacaoFiscalizacaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = [], int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaNotificacao::query()
            ->with(['notificavel', 'criador:id,name', 'media'])
            ->when(($filtros['apenas_pendentes'] ?? false) === true, fn (Builder $q) => $q->pendentes())
            ->when($filtros['notificavel_type'] ?? null, function (Builder $q, $alias) use ($filtros): void {
                $classe = NotificacaoDTO::TIPOS_PERMITIDOS[(string) $alias] ?? null;

                if ($classe === null) {
                    return;
                }

                $q->where('notificavel_type', $classe);

                if (isset($filtros['notificavel_id'])) {
                    $q->where('notificavel_id', (int) $filtros['notificavel_id']);
                }
            })
            ->orderByDesc('created_at')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function emitir(NotificacaoDTO $dto, ?UploadedFile $arquivo = null): CisternaNotificacao
    {
        $this->garantirQueONotificavelExiste($dto);

        return DB::transaction(function () use ($dto, $arquivo): CisternaNotificacao {
            $notificacao = CisternaNotificacao::create(array_merge($dto->toArray(), [
                'respondida' => false,
                'created_by' => Auth::id(),
            ]));

            $this->anexar($notificacao, $arquivo);

            return $notificacao->load(['notificavel', 'media']);
        });
    }

    public function atualizar(
        CisternaNotificacao $notificacao,
        NotificacaoDTO $dto,
        ?UploadedFile $arquivo = null,
    ): CisternaNotificacao {
        return DB::transaction(function () use ($notificacao, $dto, $arquivo): CisternaNotificacao {
            // O alvo da notificacao nao muda na edicao: so o texto.
            $notificacao->update(['observacao' => $dto->observacao]);

            $this->anexar($notificacao, $arquivo);

            return $notificacao->fresh(['notificavel', 'media']);
        });
    }

    /**
     * Idempotente: responder duas vezes preserva a data original.
     */
    public function responder(CisternaNotificacao $notificacao, bool $respondida = true): CisternaNotificacao
    {
        if ($notificacao->respondida === $respondida) {
            return $notificacao;
        }

        $notificacao->update([
            'respondida' => $respondida,
            'respondida_em' => $respondida ? now() : null,
        ]);

        return $notificacao->fresh();
    }

    public function deletar(CisternaNotificacao $notificacao): bool
    {
        return (bool) $notificacao->delete();
    }

    /**
     * @throws ValidationException
     */
    private function garantirQueONotificavelExiste(NotificacaoDTO $dto): void
    {
        /** @var class-string<Model> $classe */
        $classe = $dto->notificavelType;

        if (! $classe::query()->whereKey($dto->notificavelId)->exists()) {
            throw ValidationException::withMessages([
                'notificavel_id' => 'O registro a notificar nao foi encontrado.',
            ]);
        }
    }

    private function anexar(CisternaNotificacao $notificacao, ?UploadedFile $arquivo): void
    {
        if ($arquivo === null) {
            return;
        }

        $notificacao->addMedia($arquivo)
            ->usingFileName($arquivo->getClientOriginalName())
            ->toMediaCollection('documentos');
    }
}
```

- [ ] **Step 5: Escrever os dois FormRequests**

`StoreNotificacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaNotificacao::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notificavel_type' => ['required', Rule::in(array_keys(NotificacaoDTO::TIPOS_PERMITIDOS))],
            'notificavel_id' => ['required', 'integer', 'min:1'],
            'observacao' => ['required', 'string', 'max:2000'],
            'arquivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notificavel_type.required' => 'Informe sobre qual registro e a notificacao.',
            'notificavel_type.in' => 'Tipo de registro invalido para notificacao.',
            'observacao.required' => 'Descreva a notificacao.',
        ];
    }
}
```

`UpdateNotificacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

/**
 * Na edicao o alvo nao muda: so o texto e o anexo.
 */
class UpdateNotificacaoRequest extends StoreNotificacaoRequest
{
    public function authorize(): bool
    {
        $notificacao = $this->route('notificacao');

        return $notificacao !== null && ($this->user()?->can('update', $notificacao) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $notificacao = $this->route('notificacao');

        if ($notificacao === null) {
            return;
        }

        $alias = array_search($notificacao->notificavel_type, \App\Modules\Cisterna\DTOs\NotificacaoDTO::TIPOS_PERMITIDOS, true);

        $this->merge([
            'notificavel_type' => $alias === false ? null : $alias,
            'notificavel_id' => $notificacao->notificavel_id,
        ]);
    }
}
```

- [ ] **Step 6: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=NotificacaoFiscalizacaoServiceTest`
Expected: PASS, 11 testes.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Cisterna/DTOs/NotificacaoDTO.php \
        app/Modules/Cisterna/Requests/StoreNotificacaoRequest.php \
        app/Modules/Cisterna/Requests/UpdateNotificacaoRequest.php \
        app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php \
        tests/Feature/Cisterna/NotificacaoFiscalizacaoServiceTest.php
git commit -m "✨ feat(cisterna): notificacao de fiscalizacao polimorfica, sem email hardcoded"
```

---

### Task 13: QR Code e export CSV

**Duas dependencias do legado nao existem no NewSDC** (spec secao 5.1.1):

- `simplesoftwareio/simple-qrcode` — ausente, mas `endroid/qr-code ^5.1` **esta** instalado e em uso em `Treinamento\Services\GeradorQrCodeService`. O QR e reescrito sobre Endroid.
- `barryvdh/laravel-dompdf` — ausente, **e o projeto nao tem nenhuma biblioteca de PDF**. As tres features de PDF do legado (QR individual, QR em lote, folhas de QR vazios) ficam **fora desta entrega**. O service gera PNG e SVG; a impressao em lote exige introduzir uma dependencia de PDF no NewSDC, decisao que extrapola este modulo. **E perda de funcionalidade em relacao ao legado** e precisa ser decidida antes do corte de producao.

`maatwebsite/excel` tambem nao existe: o export vira CSV streamado.

**Files:**
- Create: `app/Modules/Cisterna/Services/QrCodeService.php`
- Create: `app/Modules/Cisterna/Services/BeneficiarioExportService.php`
- Create: `app/Modules/Cisterna/Resources/BeneficiarioIndexResource.php`
- Create: `app/Modules/Cisterna/Resources/BeneficiarioResource.php`
- Create: `app/Modules/Cisterna/Resources/VistoriaResource.php`
- Create: `app/Modules/Cisterna/Resources/ComunidadeResource.php`
- Create: `app/Modules/Cisterna/Resources/LoteResource.php`
- Create: `app/Modules/Cisterna/Resources/OrdemServicoResource.php`
- Create: `app/Modules/Cisterna/Resources/NotificacaoResource.php`
- Test: `tests/Feature/Cisterna/QrCodeServiceTest.php`
- Test: `tests/Feature/Cisterna/BeneficiarioExportServiceTest.php`

**Interfaces:**
- Consumes: models da Task 4, `BeneficiarioService::listar()` (Task 8), `PerfilCisterna` (Task 6)
- Produces:
  - `QrCodeService::svgDaVistoria(CisternaVistoria): string`
  - `QrCodeService::pngDaVistoria(CisternaVistoria): string` — binario PNG
  - `QrCodeService::urlDaFicha(int $numeroInstalacao): string`
  - `QrCodeService::localizarPorNumero(int): ?CisternaVistoria`
  - `BeneficiarioExportService::COLUNAS: array<int, string>` — os 39 cabecalhos do legado
  - `BeneficiarioExportService::streamCsv(PerfilCisterna, array $filtros = []): StreamedResponse`
  - as sete Resources

- [ ] **Step 1: Escrever o teste do QR que falha**

`tests/Feature/Cisterna/QrCodeServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\QrCodeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    use DatabaseTransactions;

    private QrCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QrCodeService::class);
    }

    public function test_url_da_ficha_aponta_para_a_rota_publica(): void
    {
        $url = $this->service->urlDaFicha(4242);

        $this->assertStringContainsString('/cisternas/qrcode/4242', $url);
    }

    public function test_gera_svg_com_conteudo_valido(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 4242,
        ]);

        $svg = $this->service->svgDaVistoria($vistoria);

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_gera_png_com_assinatura_de_arquivo_png(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 4243,
        ]);

        $png = $this->service->pngDaVistoria($vistoria);

        // Assinatura de arquivo PNG.
        $this->assertSame("\x89PNG", substr($png, 0, 4));
    }

    public function test_vistoria_sem_numero_de_instalacao_nao_gera_qr(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::COMPDEC)->create([
            'numero_instalacao' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->svgDaVistoria($vistoria);
    }

    public function test_localizar_por_numero_traz_a_vistoria_e_o_beneficiario(): void
    {
        $vistoria = CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 4244,
        ]);

        $encontrada = $this->service->localizarPorNumero(4244);

        $this->assertNotNull($encontrada);
        $this->assertSame($vistoria->id, $encontrada->id);
        $this->assertTrue($encontrada->relationLoaded('beneficiario'));
    }

    public function test_localizar_numero_inexistente_devolve_null(): void
    {
        $this->assertNull($this->service->localizarPorNumero(99999999));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=QrCodeServiceTest`
Expected: FAIL com `Target class [App\Modules\Cisterna\Services\QrCodeService] does not exist.`

- [ ] **Step 3: Escrever `QrCodeService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\Models\CisternaVistoria;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Validation\ValidationException;

/**
 * QR Code impresso no adesivo colado na cisterna instalada. O numero
 * codificado e o `numero_instalacao` da vistoria do fornecedor, e a URL abre
 * a ficha publica do beneficiario.
 *
 * O legado usava simplesoftwareio/simple-qrcode, que nao existe no NewSDC.
 * Reescrito sobre endroid/qr-code, seguindo Treinamento\Services\
 * GeradorQrCodeService.
 *
 * As tres features de PDF do legado (QR individual, QR em lote e folhas de
 * QR vazios) NAO estao aqui: o NewSDC nao tem biblioteca de PDF. Ver spec
 * secao 5.1.1.
 */
class QrCodeService
{
    private const TAMANHO = 300;

    private const MARGEM = 10;

    public function urlDaFicha(int $numeroInstalacao): string
    {
        return route('cisternas.qrcode.ficha', ['numeroInstalacao' => $numeroInstalacao]);
    }

    public function svgDaVistoria(CisternaVistoria $vistoria): string
    {
        $qrCode = $this->construir($vistoria);

        return (new SvgWriter())->write($qrCode)->getString();
    }

    /**
     * @return string binario PNG
     */
    public function pngDaVistoria(CisternaVistoria $vistoria): string
    {
        $qrCode = $this->construir($vistoria);

        return (new PngWriter())->write($qrCode)->getString();
    }

    /**
     * Usado pela rota publica da ficha. O legado fazia um join manual entre
     * sinc_cisterna_rel_fornecedor e sinc_cisterna
     * (CisternaController.php:329).
     */
    public function localizarPorNumero(int $numeroInstalacao): ?CisternaVistoria
    {
        return CisternaVistoria::query()
            ->with(['beneficiario.municipio:id,nome,uf', 'beneficiario.comunidade:id,nome'])
            ->where('numero_instalacao', $numeroInstalacao)
            ->first();
    }

    /**
     * @throws ValidationException quando a vistoria nao tem numero de instalacao
     */
    private function construir(CisternaVistoria $vistoria): QrCode
    {
        if ($vistoria->numero_instalacao === null) {
            throw ValidationException::withMessages([
                'numero_instalacao' => 'Somente a vistoria do fornecedor tem numero de instalacao para gerar QR Code.',
            ]);
        }

        return new QrCode(
            data: $this->urlDaFicha($vistoria->numero_instalacao),
            size: self::TAMANHO,
            margin: self::MARGEM,
        );
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=QrCodeServiceTest`
Expected: PASS, 6 testes.

- [ ] **Step 5: Escrever o teste do export que falha**

`tests/Feature/Cisterna/BeneficiarioExportServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\User;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BeneficiarioExportServiceTest extends TestCase
{
    use DatabaseTransactions;

    private BeneficiarioExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BeneficiarioExportService::class);
    }

    public function test_tem_as_trinta_e_nove_colunas_do_legado(): void
    {
        $this->assertCount(39, BeneficiarioExportService::COLUNAS);
        $this->assertSame('Identificacao', BeneficiarioExportService::COLUNAS[0]);
        $this->assertSame('Crea do engenheiro', BeneficiarioExportService::COLUNAS[38]);
    }

    public function test_stream_devolve_csv_com_cabecalho_e_linhas(): void
    {
        $comunidade = CisternaComunidade::factory()->create();
        CisternaBeneficiario::factory()->create([
            'nome' => 'Maria Exportada',
            'cpf' => '52998224725',
            'municipio_id' => $comunidade->municipio_id,
            'comunidade_id' => $comunidade->id,
            'situacao_analise' => SituacaoAnalise::APROVADO->value,
        ]);

        $conteudo = $this->capturar($this->perfilCedec());

        $linhas = array_filter(explode("\n", $conteudo));

        $this->assertStringContainsString('Identificacao', $linhas[0]);
        $this->assertStringContainsString('Maria Exportada', $conteudo);
        $this->assertStringContainsString('52998224725', $conteudo);
        // Situacao vem como rotulo, nao como codigo.
        $this->assertStringContainsString('Aprovado', $conteudo);
    }

    public function test_booleanos_saem_como_sim_e_nao(): void
    {
        CisternaBeneficiario::factory()->create([
            'nome' => 'Com Fogao',
            'possui_fogao_lenha' => true,
            'atendido_por_pipa' => false,
        ]);

        $conteudo = $this->capturar($this->perfilCedec());

        $this->assertStringContainsString('Sim', $conteudo);
        $this->assertStringContainsString('Nao', $conteudo);
    }

    public function test_respeita_o_escopo_do_perfil(): void
    {
        $municipios = DB::table('municipios')->limit(2)->pluck('id')->all();

        CisternaBeneficiario::factory()->create([
            'nome' => 'Do Meu Municipio',
            'municipio_id' => $municipios[0],
        ]);
        CisternaBeneficiario::factory()->create([
            'nome' => 'De Outro Municipio',
            'municipio_id' => $municipios[1],
        ]);

        $conteudo = $this->capturar($this->perfilCompdec((int) $municipios[0]));

        $this->assertStringContainsString('Do Meu Municipio', $conteudo);
        $this->assertStringNotContainsString('De Outro Municipio', $conteudo);
    }

    public function test_nome_do_arquivo_traz_data_e_hora(): void
    {
        $resposta = $this->service->streamCsv($this->perfilCedec());

        $this->assertStringContainsString(
            'attachment; filename=',
            (string) $resposta->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString('cisterna-beneficiarios-', (string) $resposta->headers->get('Content-Disposition'));
        $this->assertStringContainsString('text/csv', (string) $resposta->headers->get('Content-Type'));
    }

    private function capturar(PerfilCisterna $perfil, array $filtros = []): string
    {
        $resposta = $this->service->streamCsv($perfil, $filtros);

        ob_start();
        $resposta->sendContent();

        return (string) ob_get_clean();
    }

    private function perfilCedec(): PerfilCisterna
    {
        return $this->perfil(TipoOrgao::CEDEC);
    }

    private function perfilCompdec(int $municipioId): PerfilCisterna
    {
        return $this->perfil(TipoOrgao::COMPDEC, $municipioId);
    }

    private function perfil(TipoOrgao $tipo, ?int $municipioId = null): PerfilCisterna
    {
        $orgao = Orgao::create([
            'nome' => 'Orgao '.$tipo->value.' '.uniqid(),
            'codigo' => strtoupper($tipo->value).'-'.uniqid(),
            'tipo' => $tipo->value,
            'municipio_id' => $municipioId ?? DB::table('municipios')->value('id'),
        ]);

        return PerfilCisterna::deUsuario(
            User::factory()->create(['orgao_principal_id' => $orgao->id])->fresh()
        );
    }
}
```

- [ ] **Step 6: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=BeneficiarioExportServiceTest`
Expected: FAIL com `Target class [App\Modules\Cisterna\Services\BeneficiarioExportService] does not exist.`

- [ ] **Step 7: Escrever `BeneficiarioExportService`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export dos beneficiarios, com as mesmas 39 colunas do
 * App\Exports\ExportCisterna do legado.
 *
 * O legado usava maatwebsite/excel com ShouldQueue e chunk de 1000, gerando
 * .xlsx. A dependencia nao existe no NewSDC; o padrao daqui e CSV streamado
 * (8 metodos `export(): StreamedResponse` em AjudaHumanitaria, Decretacoes e
 * Demandas). O chunk de 1000 e preservado via lazy(), entao a memoria fica
 * constante mesmo com dezenas de milhares de linhas.
 */
class BeneficiarioExportService
{
    /**
     * Cabecalhos na ordem exata do legado (ExportCisterna::headings).
     *
     * @var array<int, string>
     */
    public const COLUNAS = [
        'Identificacao',
        'Municipio',
        'Status',
        'Comunidade',
        'Nome',
        'Endereco',
        'Latitude',
        'Longitude',
        'Cpf',
        'Data de nascimento',
        'Cadastro Unico',
        'Quantidade de pessoas',
        'Renda',
        'Renda Per Capita',
        'Moradia',
        'Outra Moradia',
        'Comprimento do telhado',
        'Largura do telhado',
        'Area do telhado',
        'Comprimento da testada',
        'Numero de caidas do telhado',
        'Tipo de cobertura',
        'Outra cobertura',
        'Existe fogao a lenha',
        'Medida do telhado sem fogao',
        'Testada sem fogao',
        'Atendimento por caminhao pipa',
        'Defesa Civil',
        'Exercito',
        'Particular',
        'Prefeitura',
        'Outros',
        'Descricao do outros',
        'Observacoes',
        'Observacao da ressalva',
        'Nome do agente',
        'CPF do agente',
        'Nome do engenheiro',
        'Crea do engenheiro',
    ];

    private const TAMANHO_DO_LOTE = 1000;

    public function __construct(
        private readonly BeneficiarioService $beneficiarios,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function streamCsv(PerfilCisterna $perfil, array $filtros = []): StreamedResponse
    {
        $nomeArquivo = 'cisterna-beneficiarios-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($perfil, $filtros): void {
            $saida = fopen('php://output', 'wb');

            // BOM UTF-8: sem ele o Excel em pt-BR quebra os acentos.
            fwrite($saida, "\xEF\xBB\xBF");

            fputcsv($saida, self::COLUNAS, ';');

            foreach ($this->consultar($perfil, $filtros) as $beneficiario) {
                fputcsv($saida, $this->mapear($beneficiario), ';');
            }

            fclose($saida);
        }, $nomeArquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return \Illuminate\Support\LazyCollection<int, CisternaBeneficiario>
     */
    private function consultar(PerfilCisterna $perfil, array $filtros): \Illuminate\Support\LazyCollection
    {
        // Reaproveita o escopo por perfil e os filtros da listagem: o export
        // do legado tambem passava pelo mesmo aplicarFiltros().
        return $this->beneficiarios
            ->consultaParaExport($perfil, $filtros)
            ->with(['municipio:id,nome', 'comunidade:id,nome', 'atendimentosPipa'])
            ->orderBy('cpf')
            ->lazy(self::TAMANHO_DO_LOTE);
    }

    /**
     * @return array<int, string|int|float|null>
     */
    private function mapear(CisternaBeneficiario $b): array
    {
        $responsaveis = $b->atendimentosPipa->keyBy(fn ($a): string => $a->responsavel->value);

        $temResponsavel = fn (ResponsavelPipa $r): string => $responsaveis->has($r->value) ? 'Sim' : 'Nao';

        return [
            $b->id,
            $b->municipio?->nome,
            $b->situacao_analise->label(),
            $b->comunidade?->nome,
            $b->nome,
            $b->endereco,
            $b->latitude,
            $b->longitude,
            $b->cpf,
            $b->data_nascimento?->format('d/m/Y'),
            $b->cadastro_unico,
            $b->qtd_pessoas,
            $b->renda,
            $b->renda_per_capita,
            $b->tipo_moradia,
            $b->tipo_moradia_outro,
            $b->comprimento_telhado,
            $b->largura_telhado,
            $b->area_telhado,
            $b->comprimento_testada,
            $b->num_caidas_telhado,
            $b->cobertura_telhado,
            $b->cobertura_outro,
            $this->simNao($b->possui_fogao_lenha),
            $b->medida_telhado_area_fogao,
            $b->testada_disp_parte_fogao,
            $this->simNao($b->atendido_por_pipa),
            $temResponsavel(ResponsavelPipa::DEFESA_CIVIL),
            $temResponsavel(ResponsavelPipa::EXERCITO),
            $temResponsavel(ResponsavelPipa::PARTICULAR),
            $temResponsavel(ResponsavelPipa::PREFEITURA),
            $temResponsavel(ResponsavelPipa::OUTROS),
            $responsaveis->get(ResponsavelPipa::OUTROS->value)?->descricao,
            $b->observacoes,
            $b->situacao_analise_obs,
            $b->agente_nome,
            $b->agente_cpf,
            $b->engenheiro_nome,
            $b->engenheiro_crea,
        ];
    }

    private function simNao(?bool $valor): string
    {
        return $valor === true ? 'Sim' : 'Nao';
    }
}
```

- [ ] **Step 8: Expor a consulta do export no `BeneficiarioService`**

O export precisa do mesmo escopo por perfil e dos mesmos filtros da listagem, sem paginar. Acrescentar em `app/Modules/Cisterna/Services/BeneficiarioService.php`:

```php
    /**
     * Consulta com escopo de perfil e filtros aplicados, sem paginacao nem
     * eager loading — para o export streamar com lazy().
     *
     * @param  array<string, mixed>  $filtros
     * @return Builder<CisternaBeneficiario>
     */
    public function consultaParaExport(PerfilCisterna $perfil, array $filtros = []): Builder
    {
        $query = CisternaBeneficiario::query();

        $this->aplicarEscopoDoPerfil($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        return $query;
    }
```

- [ ] **Step 9: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=BeneficiarioExportServiceTest`
Expected: PASS, 5 testes.

- [ ] **Step 10: Escrever as sete Resources**

`BeneficiarioIndexResource.php` — enxuta, e a linha da tabela:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaBeneficiario
 */
class BeneficiarioIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cpf' => $this->cpf,
            'nome' => $this->nome,
            'municipio' => $this->municipio?->nome,
            'comunidade' => $this->comunidade?->nome,
            'situacao_analise' => [
                'valor' => $this->situacao_analise->value,
                'rotulo' => $this->situacao_analise->label(),
            ],
            'situacao_obra' => [
                'valor' => $this->situacao_obra->value,
                'rotulo' => $this->situacao_obra->label(),
            ],
            'ranqueamento_ordem' => $this->ranqueamento_ordem,
            'lote' => $this->ordemServico?->lote?->nome,
            'ordem_servico' => $this->ordemServico?->nome,
            // Substitui os tres whereHas do legado: as etapas concluidas vem
            // da relacao ja carregada, sem consulta extra por linha.
            'etapas_concluidas' => $this->when(
                $this->relationLoaded('vistorias'),
                fn (): array => $this->vistorias
                    ->filter(fn ($v): bool => $v->estaConcluida())
                    ->map(fn ($v): string => $v->etapa->value)
                    ->values()
                    ->all(),
                []
            ),
            'numero_instalacao' => $this->when(
                $this->relationLoaded('vistorias'),
                fn () => $this->vistoriaDaEtapa(EtapaVistoria::FORNECEDOR)?->numero_instalacao
            ),
        ];
    }
}
```

`BeneficiarioResource.php` — completa, e a tela de detalhe:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaBeneficiario
 */
class BeneficiarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cpf' => $this->cpf,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'data_nascimento' => $this->data_nascimento?->toDateString(),
            'cadastro_unico' => $this->cadastro_unico,

            'municipio' => [
                'id' => $this->municipio_id,
                'nome' => $this->municipio?->nome,
                'uf' => $this->municipio?->uf,
            ],
            'comunidade' => [
                'id' => $this->comunidade_id,
                'nome' => $this->comunidade?->nome,
            ],
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'ordem_servico' => $this->whenLoaded(
                'ordemServico',
                fn (): ?array => $this->ordemServico === null ? null : [
                    'id' => $this->ordemServico->id,
                    'nome' => $this->ordemServico->nome,
                    'lote' => $this->ordemServico->lote?->nome,
                ]
            ),

            'situacao_analise' => [
                'valor' => $this->situacao_analise->value,
                'rotulo' => $this->situacao_analise->label(),
                'observacao' => $this->situacao_analise_obs,
            ],
            'situacao_obra' => [
                'valor' => $this->situacao_obra->value,
                'rotulo' => $this->situacao_obra->label(),
            ],
            'ranqueamento_ordem' => $this->ranqueamento_ordem,

            'criterios_sociais' => [
                'qtd_pessoas' => $this->qtd_pessoas,
                'renda' => $this->renda,
                'renda_per_capita' => $this->renda_per_capita,
                'possui_deficiencia' => $this->possui_deficiencia,
                'possui_crianca' => $this->possui_crianca,
                'data_nascimento_crianca' => $this->data_nascimento_crianca?->toDateString(),
                'possui_idoso' => $this->possui_idoso,
                'chefiada_mulher' => $this->chefiada_mulher,
            ],

            'avaliacao_tecnica' => [
                'tipo_moradia' => $this->tipo_moradia,
                'tipo_moradia_outro' => $this->tipo_moradia_outro,
                'comprimento_telhado' => $this->comprimento_telhado,
                'largura_telhado' => $this->largura_telhado,
                'area_telhado' => $this->area_telhado,
                'comprimento_testada' => $this->comprimento_testada,
                'num_caidas_telhado' => $this->num_caidas_telhado,
                'cobertura_telhado' => $this->cobertura_telhado,
                'cobertura_outro' => $this->cobertura_outro,
                'possui_fogao_lenha' => $this->possui_fogao_lenha,
                'medida_telhado_area_fogao' => $this->medida_telhado_area_fogao,
                'testada_disp_parte_fogao' => $this->testada_disp_parte_fogao,
            ],

            'atendimento_pipa' => [
                'atendido' => $this->atendido_por_pipa,
                'responsaveis' => $this->whenLoaded(
                    'atendimentosPipa',
                    fn (): array => $this->atendimentosPipa->map(fn ($a): array => [
                        'valor' => $a->responsavel->value,
                        'rotulo' => $a->responsavel->label(),
                        'descricao' => $a->descricao,
                    ])->all()
                ),
            ],

            'responsaveis_cadastro' => [
                'agente_nome' => $this->agente_nome,
                'agente_cpf' => $this->agente_cpf,
                'engenheiro_nome' => $this->engenheiro_nome,
                'engenheiro_crea' => $this->engenheiro_crea,
            ],

            'observacoes' => $this->observacoes,

            'vistorias' => VistoriaResource::collection($this->whenLoaded('vistorias')),
            'notificacoes' => NotificacaoResource::collection($this->whenLoaded('notificacoes')),

            'fotos_imovel' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('fotos_imovel')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : null,
                    'angulo' => $m->getCustomProperty('angulo'),
                    'observacao' => $m->getCustomProperty('observacao'),
                ])->all()
            ),
            'comprovantes' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('comprovantes')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'tipo' => $m->getCustomProperty('tipo'),
                    'nome' => $m->file_name,
                ])->all()
            ),

            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

`VistoriaResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaVistoria
 */
class VistoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'beneficiario_id' => $this->beneficiario_id,
            'etapa' => [
                'valor' => $this->etapa->value,
                'rotulo' => $this->etapa->label(),
            ],
            'numero_instalacao' => $this->numero_instalacao,
            'concluida' => $this->estaConcluida(),
            'concluida_em' => $this->concluida_em?->toIso8601String(),

            'engenheiro' => [
                'nome' => $this->engenheiro_nome,
                'crea' => $this->engenheiro_crea,
                'art' => $this->engenheiro_art,
            ],
            'data_relatorio' => $this->data_relatorio?->toDateString(),
            'local_relatorio' => $this->local_relatorio,

            // Somente na etapa CEDEC.
            'dados_administrativos' => $this->when(
                $this->etapa->exigeDadosAdministrativos(),
                fn (): array => [
                    'processo_sei' => $this->processo_sei,
                    'contrato' => $this->contrato,
                    'empenho' => $this->empenho,
                    'placa_obras' => $this->placa_obras,
                ]
            ),

            'local' => [
                'endereco' => $this->endereco,
                'bairro' => $this->bairro,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'itens' => $this->whenLoaded(
                'itensConferidos',
                fn (): array => $this->itensConferidos->map(fn ($i): array => [
                    'item' => $i->item->value,
                    'rotulo' => $i->item->label(),
                    'conferido' => $i->conferido,
                    'quantidade' => $i->quantidade,
                    'unidade' => $i->unidade?->value,
                    'detalhes' => $i->detalhes,
                    'observacao' => $i->observacao,
                ])->all()
            ),

            'observacoes' => $this->observacoes,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

`ComunidadeResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaComunidade
 */
class ComunidadeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'ativa' => $this->ativa,
            'municipio' => [
                'id' => $this->municipio_id,
                'nome' => $this->municipio?->nome,
                'uf' => $this->municipio?->uf,
            ],
            // Contado por comunidade_id, nao pelo nome: no legado comunidades
            // homonimas de municipios diferentes somavam a contagem.
            'beneficiarios' => $this->when(
                $this->beneficiarios_count !== null,
                fn (): int => (int) $this->beneficiarios_count
            ),
        ];
    }
}
```

`LoteResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaLote
 */
class LoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'data' => $this->data?->toDateString(),
            'observacao' => $this->observacao,
            'ordens_servico' => $this->when(
                $this->ordens_servico_count !== null,
                fn (): int => (int) $this->ordens_servico_count
            ),
        ];
    }
}
```

`OrdemServicoResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaOrdemServico
 */
class OrdemServicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'observacao' => $this->observacao,
            'lote' => [
                'id' => $this->lote_id,
                'nome' => $this->lote?->nome,
            ],
            'beneficiarios' => $this->when(
                $this->beneficiarios_count !== null,
                fn (): int => (int) $this->beneficiarios_count
            ),
            // Legado: coluna link_doc.
            'documento' => $this->when(
                $this->relationLoaded('media'),
                fn (): ?string => $this->getFirstMediaUrl('documento_os') ?: null
            ),
        ];
    }
}
```

`NotificacaoResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaNotificacao
 */
class NotificacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Devolve o alias curto, nao o FQCN: o frontend nao precisa conhecer
        // a estrutura interna.
        $alias = array_search($this->notificavel_type, NotificacaoDTO::TIPOS_PERMITIDOS, true);

        return [
            'id' => $this->id,
            'notificavel' => [
                'tipo' => $alias === false ? null : $alias,
                'id' => $this->notificavel_id,
            ],
            'observacao' => $this->observacao,
            'respondida' => $this->respondida,
            'respondida_em' => $this->respondida_em?->toIso8601String(),
            'emitida_por' => $this->whenLoaded('criador', fn (): ?string => $this->criador?->name),
            'documentos' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('documentos')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'nome' => $m->file_name,
                ])->all()
            ),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 11: Rodar a suite do modulo**

Run: `scripts/test-host.sh --filter=Cisterna`
Expected: PASS em tudo.

- [ ] **Step 12: Commit**

```bash
git add app/Modules/Cisterna/Services/QrCodeService.php \
        app/Modules/Cisterna/Services/BeneficiarioExportService.php \
        app/Modules/Cisterna/Services/BeneficiarioService.php \
        app/Modules/Cisterna/Resources \
        tests/Feature/Cisterna/QrCodeServiceTest.php \
        tests/Feature/Cisterna/BeneficiarioExportServiceTest.php
git commit -m "✨ feat(cisterna): QR Code via endroid, export CSV de 39 colunas e resources"
```

---

### Task 14: Controllers

Substitui os stubs criados na Task 6. Nenhum controller tem regra de negocio: resolvem perfil, delegam ao service e devolvem Inertia ou redirect. As paginas Vue nao existem ainda (fase de frontend), mas os controllers e os testes de rota sim — o teste verifica o componente e as props via `Inertia::assertComponent`.

**Files:**
- Rewrite: `app/Modules/Cisterna/Controllers/BeneficiarioController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/VistoriaController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/ComunidadeController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/LoteController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/OrdemServicoController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/NotificacaoFiscalizacaoController.php`
- Rewrite: `app/Modules/Cisterna/Controllers/QrCodeController.php`
- Create: `app/Modules/Cisterna/Requests/AcaoEmMassaRequest.php`
- Test: `tests/Feature/Cisterna/BeneficiarioControllerTest.php`

**Interfaces:**
- Consumes: todos os services e Resources das Tasks 8 a 13, `PerfilCisterna` (Task 6)
- Produces: as paginas Inertia `Cisterna/Beneficiarios/{Index,Create,Edit,Show}`, `Cisterna/Vistorias/{Index,Show}`, `Cisterna/Comunidades/Index`, `Cisterna/Lotes/Index`, `Cisterna/OrdensServico/Index`, `Cisterna/Notificacoes/Index`, `Cisterna/QrCode/Ficha`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/BeneficiarioControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BeneficiarioControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'cisternas.beneficiarios.view',
        'cisternas.beneficiarios.create',
        'cisternas.beneficiarios.edit',
        'cisternas.beneficiarios.delete',
        'cisternas.beneficiarios.export',
        'cisternas.vistorias.view',
        'cisternas.vistorias.create',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_index_renderiza_a_pagina_com_beneficiarios_e_indicadores(): void
    {
        $this->atuandoComoCedec();
        CisternaBeneficiario::factory()->count(3)->create();

        $this->get(route('cisternas.beneficiarios.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cisterna/Beneficiarios/Index')
                ->has('beneficiarios.data')
                ->has('indicadores')
                ->has('filtros')
                ->has('opcoes.situacoes_analise')
                ->has('opcoes.situacoes_obra')
                ->has('opcoes.municipios')
            );
    }

    public function test_index_sem_permissao_devolve_403(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('cisternas.beneficiarios.index'))->assertForbidden();
    }

    public function test_index_aceita_filtro_de_busca_por_nome(): void
    {
        $this->atuandoComoCedec();
        CisternaBeneficiario::factory()->create(['nome' => 'Joana Filtravel']);
        CisternaBeneficiario::factory()->create(['nome' => 'Outro Qualquer']);

        $this->get(route('cisternas.beneficiarios.index', ['search' => 'Filtravel']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('beneficiarios.data', 1));
    }

    public function test_show_renderiza_o_detalhe(): void
    {
        $this->atuandoComoCedec();
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->get(route('cisternas.beneficiarios.show', $beneficiario))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cisterna/Beneficiarios/Show')
                ->where('beneficiario.id', $beneficiario->id)
                ->has('etapa_disponivel')
            );
    }

    public function test_compdec_nao_acessa_beneficiario_de_outro_municipio(): void
    {
        $municipios = DB::table('municipios')->limit(2)->pluck('id')->all();
        $this->atuandoComoCompdec((int) $municipios[0]);

        $deOutro = CisternaBeneficiario::factory()->create(['municipio_id' => $municipios[1]]);

        $this->get(route('cisternas.beneficiarios.show', $deOutro))->assertForbidden();
    }

    public function test_store_cria_beneficiario_e_redireciona_para_o_detalhe(): void
    {
        $this->atuandoComoCedec();

        $resposta = $this->post(route('cisternas.beneficiarios.store'), $this->payload());

        $beneficiario = CisternaBeneficiario::where('cpf', '52998224725')->first();
        $this->assertNotNull($beneficiario);
        $resposta->assertRedirect(route('cisternas.beneficiarios.show', $beneficiario->id));
        $resposta->assertSessionHas('success');
    }

    public function test_store_com_cpf_duplicado_volta_com_erro(): void
    {
        $this->atuandoComoCedec();
        CisternaBeneficiario::factory()->create(['cpf' => '52998224725']);

        $this->post(route('cisternas.beneficiarios.store'), $this->payload())
            ->assertSessionHasErrors('cpf');
    }

    public function test_update_altera_o_registro(): void
    {
        $this->atuandoComoCedec();
        $beneficiario = CisternaBeneficiario::factory()->create(['cpf' => '52998224725']);

        $this->put(
            route('cisternas.beneficiarios.update', $beneficiario),
            $this->payload(['nome' => 'Nome Corrigido'])
        )->assertRedirect(route('cisternas.beneficiarios.show', $beneficiario->id));

        $this->assertSame('Nome Corrigido', $beneficiario->fresh()->nome);
    }

    public function test_destroy_faz_soft_delete(): void
    {
        $this->atuandoComoCedec();
        $beneficiario = CisternaBeneficiario::factory()->create();

        $this->delete(route('cisternas.beneficiarios.destroy', $beneficiario))
            ->assertRedirect(route('cisternas.beneficiarios.index'));

        $this->assertSoftDeleted('cisterna_beneficiarios', ['id' => $beneficiario->id]);
    }

    public function test_acao_em_massa_aloca_em_ordem_de_servico(): void
    {
        $this->atuandoComoCedec();
        $os = CisternaOrdemServico::factory()->create();
        $ids = CisternaBeneficiario::factory()->count(2)->create()->pluck('id')->all();

        $this->post(route('cisternas.beneficiarios.acao-em-massa'), [
            'acao' => 'alocar_em_ordem_servico',
            'ids' => $ids,
            'ordem_servico_id' => $os->id,
        ])->assertRedirect();

        $this->assertSame(2, CisternaBeneficiario::where('ordem_servico_id', $os->id)->count());
    }

    public function test_acao_em_massa_altera_situacao_da_obra(): void
    {
        $this->atuandoComoCedec();
        $ids = CisternaBeneficiario::factory()->count(2)
            ->create(['situacao_obra' => SituacaoObra::PROCESSAMENTO->value])
            ->pluck('id')->all();

        $this->post(route('cisternas.beneficiarios.acao-em-massa'), [
            'acao' => 'alterar_situacao_obra',
            'ids' => $ids,
            'situacao_obra' => SituacaoObra::ENVIO_INSTALACAO->value,
        ])->assertRedirect();

        $this->assertSame(
            2,
            CisternaBeneficiario::whereIn('id', $ids)
                ->where('situacao_obra', SituacaoObra::ENVIO_INSTALACAO->value)
                ->count()
        );
    }

    public function test_acao_em_massa_desconhecida_e_rejeitada(): void
    {
        $this->atuandoComoCedec();

        $this->post(route('cisternas.beneficiarios.acao-em-massa'), [
            'acao' => 'apagar_tudo',
            'ids' => [1],
        ])->assertSessionHasErrors('acao');
    }

    public function test_export_devolve_csv(): void
    {
        $this->atuandoComoCedec();
        CisternaBeneficiario::factory()->create(['nome' => 'Maria Exportada']);

        $resposta = $this->get(route('cisternas.beneficiarios.export'));

        $resposta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $resposta->headers->get('Content-Type'));
    }

    public function test_ficha_publica_do_qr_code_dispensa_autenticacao(): void
    {
        CisternaVistoria::factory()->daEtapa(EtapaVistoria::FORNECEDOR)->create([
            'numero_instalacao' => 5150,
        ]);

        $this->get(route('cisternas.qrcode.ficha', ['numeroInstalacao' => 5150]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Cisterna/QrCode/Ficha'));
    }

    public function test_ficha_publica_de_numero_inexistente_devolve_404(): void
    {
        $this->get(route('cisternas.qrcode.ficha', ['numeroInstalacao' => 99999999]))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $sobrescreve
     * @return array<string, mixed>
     */
    private function payload(array $sobrescreve = []): array
    {
        return array_merge([
            'cpf' => '529.982.247-25',
            'nome' => 'Maria de Teste',
            'telefone' => '(31) 98888-7777',
            'data_nascimento' => now()->subYears(40)->toDateString(),
            'municipio_id' => DB::table('municipios')->value('id'),
            'latitude' => '-19,912998',
            'longitude' => '-43,940933',
            'qtd_pessoas' => 4,
            'renda' => 'R$ 1.200,00',
            'possui_deficiencia' => 'nao',
            'possui_crianca' => 'nao',
            'possui_idoso' => 'nao',
            'chefiada_mulher' => 'nao',
            'tipo_moradia' => 'alvenaria',
            'comprimento_telhado' => '10,5',
            'largura_telhado' => '6',
            'comprimento_testada' => '10,5',
            'num_caidas_telhado' => 2,
            'cobertura_telhado' => 'telha ceramica',
            'possui_fogao_lenha' => 'nao',
            'atendido_por_pipa' => 'nao',
            'agente_nome' => 'Agente Teste',
            'agente_cpf' => '111.444.777-35',
            'engenheiro_nome' => 'Eng Teste',
            'engenheiro_crea' => 'MG-123456',
        ], $sobrescreve);
    }

    private function atuandoComoCedec(): void
    {
        $this->atuandoComOrgao(TipoOrgao::CEDEC);
    }

    private function atuandoComoCompdec(int $municipioId): void
    {
        $this->atuandoComOrgao(TipoOrgao::COMPDEC, $municipioId);
    }

    private function atuandoComOrgao(TipoOrgao $tipo, ?int $municipioId = null): void
    {
        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $orgao = Orgao::create([
            'nome' => 'Orgao '.$tipo->value.' '.uniqid(),
            'codigo' => strtoupper($tipo->value).'-'.uniqid(),
            'tipo' => $tipo->value,
            'municipio_id' => $municipioId ?? DB::table('municipios')->value('id'),
        ]);

        $user = User::factory()->create(['orgao_principal_id' => $orgao->id]);
        $user->givePermissionTo(self::PERMISSOES);

        $this->actingAs($user->fresh());
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=BeneficiarioControllerTest`
Expected: FAIL — os stubs nao tem os metodos, entao a resolucao da rota quebra.

- [ ] **Step 3: Escrever `AcaoEmMassaRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legado: updateEstadoMass, CisternaController.php:1473, que aceitava
 * qualquer string em `acao` e respondia 400 quando nao reconhecia.
 */
class AcaoEmMassaRequest extends FormRequest
{
    public const ACOES = [
        'alocar_em_ordem_servico',
        'remover_de_ordem_servico',
        'alterar_situacao_obra',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('update', CisternaBeneficiario::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'acao' => ['required', Rule::in(self::ACOES)],
            'ids' => ['required', 'array', 'min:1', 'max:5000'],
            'ids.*' => ['integer', 'exists:cisterna_beneficiarios,id'],

            'ordem_servico_id' => [
                Rule::requiredIf(fn (): bool => $this->input('acao') === 'alocar_em_ordem_servico'),
                'nullable',
                'integer',
                'exists:cisterna_ordens_servico,id',
            ],
            'situacao_obra' => [
                Rule::requiredIf(fn (): bool => $this->input('acao') === 'alterar_situacao_obra'),
                'nullable',
                Rule::in(SituacaoObra::valores()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acao.in' => 'Acao em massa nao reconhecida.',
            'ids.required' => 'Selecione ao menos um beneficiario.',
            'ordem_servico_id.required' => 'Selecione a ordem de servico de destino.',
            'situacao_obra.required' => 'Selecione a nova situacao da obra.',
        ];
    }
}
```

- [ ] **Step 4: Escrever `BeneficiarioController`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cisterna\DTOs\BeneficiarioDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Requests\AcaoEmMassaRequest;
use App\Modules\Cisterna\Requests\StoreBeneficiarioRequest;
use App\Modules\Cisterna\Requests\UpdateBeneficiarioRequest;
use App\Modules\Cisterna\Resources\BeneficiarioIndexResource;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\Cisterna\Services\BeneficiarioService;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\VistoriaService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiarioController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $service,
        private readonly VistoriaService $vistorias,
        private readonly ComunidadeService $comunidades,
        private readonly BeneficiarioExportService $export,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaBeneficiario::class);

        $perfil = PerfilCisterna::deUsuario($request->user());
        $filtros = $this->filtros($request);

        $beneficiarios = $this->service->listar(
            $perfil,
            $filtros,
            (int) $request->integer('per_page', BeneficiarioService::PORTE_PADRAO_PAGINA),
        );

        return Inertia::render('Cisterna/Beneficiarios/Index', [
            'beneficiarios' => BeneficiarioIndexResource::collection($beneficiarios),
            // Lazy: o painel de indicadores nao precisa recarregar em toda
            // troca de filtro.
            'indicadores' => fn (): array => $this->service->indicadores($perfil),
            'filtros' => $filtros,
            'opcoes' => $this->opcoes(),
            'perfil' => [
                'e_cedec' => $perfil->eCedec(),
                'e_compdec' => $perfil->eCompdec(),
                'e_fornecedor' => $perfil->eFornecedor(),
            ],
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaBeneficiario::class) ?? false,
                'exportar' => $request->user()?->can('export', CisternaBeneficiario::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CisternaBeneficiario::class);

        return Inertia::render('Cisterna/Beneficiarios/Create', [
            'opcoes' => $this->opcoes(),
        ]);
    }

    public function store(StoreBeneficiarioRequest $request): RedirectResponse
    {
        $beneficiario = $this->service->criar(
            BeneficiarioDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $beneficiario);

        return redirect()
            ->route('cisternas.beneficiarios.show', $beneficiario->id)
            ->with('success', "Beneficiario {$beneficiario->nome} cadastrado com sucesso.");
    }

    public function show(CisternaBeneficiario $beneficiario, Request $request): Response
    {
        $this->authorize('view', $beneficiario);

        $completo = $this->service->obter($beneficiario->id);

        return Inertia::render('Cisterna/Beneficiarios/Show', [
            'beneficiario' => BeneficiarioResource::make($completo),
            'etapa_disponivel' => $this->vistorias->etapaDisponivel($completo)?->value,
            'permissoes' => [
                'editar' => $request->user()?->can('update', $beneficiario) ?? false,
                'excluir' => $request->user()?->can('delete', $beneficiario) ?? false,
            ],
        ]);
    }

    public function edit(CisternaBeneficiario $beneficiario): Response
    {
        $this->authorize('update', $beneficiario);

        return Inertia::render('Cisterna/Beneficiarios/Edit', [
            'beneficiario' => BeneficiarioResource::make($this->service->obter($beneficiario->id)),
            'opcoes' => array_merge($this->opcoes(), [
                'comunidades' => $this->comunidades->doMunicipio((int) $beneficiario->municipio_id),
            ]),
        ]);
    }

    public function update(UpdateBeneficiarioRequest $request, CisternaBeneficiario $beneficiario): RedirectResponse
    {
        $atualizado = $this->service->atualizar(
            $beneficiario,
            BeneficiarioDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $atualizado);

        return redirect()
            ->route('cisternas.beneficiarios.show', $atualizado->id)
            ->with('success', "Beneficiario {$atualizado->nome} atualizado.");
    }

    public function destroy(CisternaBeneficiario $beneficiario): RedirectResponse
    {
        $this->authorize('delete', $beneficiario);

        $nome = $beneficiario->nome;
        $this->service->deletar($beneficiario);

        return redirect()
            ->route('cisternas.beneficiarios.index')
            ->with('success', "Beneficiario {$nome} excluido.");
    }

    public function acaoEmMassa(AcaoEmMassaRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $ids = array_map('intval', $dados['ids']);

        $afetados = match ($dados['acao']) {
            'alocar_em_ordem_servico' => $this->service->alocarEmOrdemServico($ids, (int) $dados['ordem_servico_id']),
            'remover_de_ordem_servico' => $this->service->removerDeOrdemServico($ids),
            'alterar_situacao_obra' => $this->service->alterarSituacaoObra(
                $ids,
                SituacaoObra::from((string) $dados['situacao_obra'])
            ),
        };

        return back()->with('success', "{$afetados} registro(s) atualizado(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', CisternaBeneficiario::class);

        return $this->export->streamCsv(
            PerfilCisterna::deUsuario($request->user()),
            $this->filtros($request),
        );
    }

    /* Internos */

    /**
     * @return array<string, mixed>
     */
    private function filtros(Request $request): array
    {
        $filtros = $request->only([
            'municipio_id', 'comunidade_id', 'situacao_analise', 'situacao_obra',
            'ordem_servico_id', 'lote_id', 'cpf', 'search', 'numero_instalacao',
            'etapa_concluida', 'etapa_pendente',
        ]);

        // Booleanos precisam de tratamento explicito: 'false' em query string
        // e uma string verdadeira.
        if ($request->has('atendido_por_pipa')) {
            $filtros['atendido_por_pipa'] = $request->boolean('atendido_por_pipa');
        }

        if ($request->boolean('ranqueamento')) {
            $filtros['ranqueamento'] = true;
        }

        return $filtros;
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'situacoes_analise' => SituacaoAnalise::options(),
            'situacoes_obra' => SituacaoObra::options(),
            'etapas_vistoria' => EtapaVistoria::options(),
            // Somente os municipios habilitados no programa: o legado fazia
            // Municipio::where('at_cisterna', 1) em nove pontos.
            'municipios' => Municipio::habilitadosCisterna(),
        ];
    }

    private function anexarArquivos(Request $request, CisternaBeneficiario $beneficiario): void
    {
        $comprovantes = [
            'comprovante_deficiencia' => 'deficiencia',
            'comprovante_chefia_mulher' => 'chefia_mulher',
            'comprovante_observacao' => 'observacao',
        ];

        foreach ($comprovantes as $campo => $tipo) {
            if (! $request->hasFile($campo)) {
                continue;
            }

            // Substitui o comprovante daquele tipo, se ja houver.
            $beneficiario->getMedia('comprovantes')
                ->filter(fn ($m): bool => $m->getCustomProperty('tipo') === $tipo)
                ->each(fn ($m) => $m->delete());

            $beneficiario->addMedia($request->file($campo))
                ->withCustomProperties(['tipo' => $tipo])
                ->toMediaCollection('comprovantes');
        }

        foreach ((array) $request->input('fotos_imovel', []) as $indice => $foto) {
            $arquivo = $request->file("fotos_imovel.{$indice}.arquivo");

            if ($arquivo === null) {
                continue;
            }

            $beneficiario->addMedia($arquivo)
                ->withCustomProperties([
                    'angulo' => $foto['angulo'] ?? null,
                    'observacao' => $foto['observacao'] ?? null,
                ])
                ->toMediaCollection('fotos_imovel');
        }
    }
}
```

- [ ] **Step 5: Escrever `VistoriaController`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Requests\StoreVistoriaRequest;
use App\Modules\Cisterna\Requests\UpdateVistoriaRequest;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Resources\VistoriaResource;
use App\Modules\Cisterna\Services\VistoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VistoriaController extends Controller
{
    public function __construct(
        private readonly VistoriaService $service,
    ) {}

    public function index(CisternaBeneficiario $beneficiario, Request $request): Response
    {
        $this->authorize('view', $beneficiario);

        $beneficiario->load(['vistorias.itensConferidos', 'vistorias.media', 'municipio:id,nome,uf']);

        return Inertia::render('Cisterna/Vistorias/Index', [
            'beneficiario' => BeneficiarioResource::make($beneficiario),
            'vistorias' => VistoriaResource::collection($beneficiario->vistorias),
            'etapa_disponivel' => $this->service->etapaDisponivel($beneficiario)?->value,
            'itens' => ItemInstalacao::options(),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaVistoria::class) ?? false,
            ],
        ]);
    }

    public function store(StoreVistoriaRequest $request): RedirectResponse
    {
        $vistoria = $this->service->abrir(
            VistoriaDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $vistoria);

        return redirect()
            ->route('cisternas.vistorias.show', $vistoria->id)
            ->with('success', "{$vistoria->etapa->label()} registrada com sucesso.");
    }

    public function show(CisternaVistoria $vistoria, Request $request): Response
    {
        $this->authorize('view', $vistoria);

        $vistoria->load(['itensConferidos', 'beneficiario.municipio:id,nome,uf', 'notificacoes', 'media']);

        return Inertia::render('Cisterna/Vistorias/Show', [
            'vistoria' => VistoriaResource::make($vistoria),
            'beneficiario' => BeneficiarioResource::make($vistoria->beneficiario),
            'itens' => ItemInstalacao::options(),
            'permissoes' => [
                'editar' => $request->user()?->can('update', $vistoria) ?? false,
                'excluir' => $request->user()?->can('delete', $vistoria) ?? false,
            ],
        ]);
    }

    public function update(UpdateVistoriaRequest $request, CisternaVistoria $vistoria): RedirectResponse
    {
        $atualizada = $this->service->atualizar(
            $vistoria,
            VistoriaDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $atualizada);

        return redirect()
            ->route('cisternas.vistorias.show', $atualizada->id)
            ->with('success', 'Vistoria atualizada.');
    }

    public function concluir(CisternaVistoria $vistoria): RedirectResponse
    {
        $this->authorize('update', $vistoria);

        $this->service->concluir($vistoria);

        return back()->with('success', "{$vistoria->etapa->label()} concluida.");
    }

    public function destroy(CisternaVistoria $vistoria): RedirectResponse
    {
        $this->authorize('delete', $vistoria);

        $beneficiarioId = $vistoria->beneficiario_id;
        $vistoria->delete();

        return redirect()
            ->route('cisternas.vistorias.index', $beneficiarioId)
            ->with('success', 'Vistoria excluida.');
    }

    private function anexarArquivos(Request $request, CisternaVistoria $vistoria): void
    {
        if ($request->hasFile('assinatura_engenheiro')) {
            // singleFile: o MediaLibrary substitui a anterior sozinho.
            $vistoria->addMedia($request->file('assinatura_engenheiro'))
                ->toMediaCollection('assinatura_engenheiro');
        }

        foreach ((array) $request->input('fotos_vistoria', []) as $indice => $foto) {
            $arquivo = $request->file("fotos_vistoria.{$indice}.arquivo");

            if ($arquivo === null) {
                continue;
            }

            // custom_properties substitui as 18 colunas {item}_foto1/2 do
            // legado. Acrescentar um item deixa de exigir migration.
            $vistoria->addMedia($arquivo)
                ->withCustomProperties([
                    'item' => $foto['item'] ?? null,
                    'sequencia' => (int) ($foto['sequencia'] ?? 1),
                ])
                ->toMediaCollection('fotos_vistoria');
        }
    }
}
```

- [ ] **Step 6: Escrever os quatro controllers de apoio**

`ComunidadeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Requests\StoreComunidadeRequest;
use App\Modules\Cisterna\Requests\UpdateComunidadeRequest;
use App\Modules\Cisterna\Resources\ComunidadeResource;
use App\Modules\Cisterna\Services\ComunidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComunidadeController extends Controller
{
    public function __construct(
        private readonly ComunidadeService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        $filtros = $request->only(['municipio_id', 'search', 'apenas_ativas']);

        return Inertia::render('Cisterna/Comunidades/Index', [
            'comunidades' => ComunidadeResource::collection($this->service->listar($filtros)),
            'filtros' => $filtros,
            'municipios' => Municipio::habilitadosCisterna(),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaComunidade::class) ?? false,
            ],
        ]);
    }

    /**
     * Select em cascata do formulario de beneficiario.
     */
    public function doMunicipio(int $municipio): JsonResponse
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        return response()->json($this->service->doMunicipio($municipio));
    }

    public function store(StoreComunidadeRequest $request): RedirectResponse
    {
        $comunidade = $this->service->criar(ComunidadeDTO::deValidados($request->validated()));

        return back()->with('success', "Comunidade {$comunidade->nome} cadastrada.");
    }

    public function update(UpdateComunidadeRequest $request, CisternaComunidade $comunidade): RedirectResponse
    {
        $atualizada = $this->service->atualizar($comunidade, ComunidadeDTO::deValidados($request->validated()));

        return back()->with('success', "Comunidade {$atualizada->nome} atualizada.");
    }

    public function destroy(CisternaComunidade $comunidade): RedirectResponse
    {
        $this->authorize('delete', $comunidade);

        $nome = $comunidade->nome;
        $this->service->deletar($comunidade);

        return back()->with('success', "Comunidade {$nome} excluida.");
    }
}
```

`LoteController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\LoteDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Requests\StoreLoteRequest;
use App\Modules\Cisterna\Requests\UpdateLoteRequest;
use App\Modules\Cisterna\Resources\LoteResource;
use App\Modules\Cisterna\Services\LoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoteController extends Controller
{
    public function __construct(
        private readonly LoteService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaLote::class);

        return Inertia::render('Cisterna/Lotes/Index', [
            'lotes' => LoteResource::collection($this->service->listar()),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaLote::class) ?? false,
            ],
        ]);
    }

    public function store(StoreLoteRequest $request): RedirectResponse
    {
        $lote = $this->service->criar(LoteDTO::deValidados($request->validated()));

        return back()->with('success', "Lote {$lote->nome} criado.");
    }

    public function update(UpdateLoteRequest $request, CisternaLote $lote): RedirectResponse
    {
        $atualizado = $this->service->atualizar($lote, LoteDTO::deValidados($request->validated()));

        return back()->with('success', "Lote {$atualizado->nome} atualizado.");
    }

    public function destroy(CisternaLote $lote): RedirectResponse
    {
        $this->authorize('delete', $lote);

        $nome = $lote->nome;
        $this->service->deletar($lote);

        return back()->with('success', "Lote {$nome} excluido.");
    }
}
```

`OrdemServicoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Requests\StoreOrdemServicoRequest;
use App\Modules\Cisterna\Requests\UpdateOrdemServicoRequest;
use App\Modules\Cisterna\Resources\OrdemServicoResource;
use App\Modules\Cisterna\Services\OrdemServicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrdemServicoController extends Controller
{
    public function __construct(
        private readonly OrdemServicoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        return Inertia::render('Cisterna/OrdensServico/Index', [
            'ordens' => OrdemServicoResource::collection($this->service->listar()),
            'lotes' => CisternaLote::query()->orderBy('nome')->get(['id', 'nome']),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaOrdemServico::class) ?? false,
            ],
        ]);
    }

    public function doLote(CisternaLote $lote, Request $request): Response
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        return Inertia::render('Cisterna/OrdensServico/Index', [
            'ordens' => OrdemServicoResource::collection($this->service->listar($lote->id)),
            'lote' => ['id' => $lote->id, 'nome' => $lote->nome],
            'lotes' => CisternaLote::query()->orderBy('nome')->get(['id', 'nome']),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaOrdemServico::class) ?? false,
            ],
        ]);
    }

    public function store(StoreOrdemServicoRequest $request): RedirectResponse
    {
        $os = $this->service->criar(OrdemServicoDTO::deValidados($request->validated()));

        if ($request->hasFile('documento_os')) {
            $os->addMedia($request->file('documento_os'))->toMediaCollection('documento_os');
        }

        return back()->with('success', "Ordem de servico {$os->nome} criada.");
    }

    /**
     * Historico do lote: quem entrou e quem saiu desta OS.
     */
    public function timeline(CisternaOrdemServico $ordemServico): JsonResponse
    {
        $this->authorize('history', $ordemServico);

        return response()->json($this->service->timeline($ordemServico));
    }

    public function update(UpdateOrdemServicoRequest $request, CisternaOrdemServico $ordemServico): RedirectResponse
    {
        $atualizada = $this->service->atualizar($ordemServico, OrdemServicoDTO::deValidados($request->validated()));

        if ($request->hasFile('documento_os')) {
            $atualizada->addMedia($request->file('documento_os'))->toMediaCollection('documento_os');
        }

        return back()->with('success', "Ordem de servico {$atualizada->nome} atualizada.");
    }

    public function destroy(CisternaOrdemServico $ordemServico): RedirectResponse
    {
        $this->authorize('delete', $ordemServico);

        $nome = $ordemServico->nome;
        $this->service->deletar($ordemServico);

        return back()->with('success', "Ordem de servico {$nome} excluida.");
    }
}
```

`NotificacaoFiscalizacaoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Requests\StoreNotificacaoRequest;
use App\Modules\Cisterna\Requests\UpdateNotificacaoRequest;
use App\Modules\Cisterna\Resources\NotificacaoResource;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificacaoFiscalizacaoController extends Controller
{
    public function __construct(
        private readonly NotificacaoFiscalizacaoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaNotificacao::class);

        $filtros = $request->only(['notificavel_type', 'notificavel_id']);

        if ($request->has('apenas_pendentes')) {
            $filtros['apenas_pendentes'] = $request->boolean('apenas_pendentes');
        }

        return Inertia::render('Cisterna/Notificacoes/Index', [
            'notificacoes' => NotificacaoResource::collection($this->service->listar($filtros)),
            'filtros' => $filtros,
            'tipos' => array_keys(NotificacaoDTO::TIPOS_PERMITIDOS),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaNotificacao::class) ?? false,
            ],
        ]);
    }

    public function store(StoreNotificacaoRequest $request): RedirectResponse
    {
        $this->service->emitir(
            NotificacaoDTO::deValidados($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Notificacao de fiscalizacao registrada.');
    }

    public function update(UpdateNotificacaoRequest $request, CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->service->atualizar(
            $notificacao,
            NotificacaoDTO::deValidados($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Notificacao atualizada.');
    }

    public function responder(Request $request, CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->authorize('update', $notificacao);

        $respondida = $request->boolean('respondida', true);
        $this->service->responder($notificacao, $respondida);

        return back()->with(
            'success',
            $respondida ? 'Notificacao marcada como respondida.' : 'Notificacao reaberta.'
        );
    }

    public function destroy(CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->authorize('delete', $notificacao);

        $this->service->deletar($notificacao);

        return back()->with('success', 'Notificacao excluida.');
    }
}
```

- [ ] **Step 7: Escrever `QrCodeController`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Services\QrCodeService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Ficha publica lida pelo QR Code colado na cisterna, e download do QR.
 *
 * As tres features de PDF do legado (pdfIndividual em lote, baixarQRCodes e
 * gerarQRCodesVazios) NAO estao aqui: o NewSDC nao tem biblioteca de PDF.
 * Ver spec secao 5.1.1. As rotas cisternas.qrcode.pdf-individual,
 * .pdf-em-lote e .folhas-vazias respondem 501 ate a decisao ser tomada.
 */
class QrCodeController extends Controller
{
    public function __construct(
        private readonly QrCodeService $service,
    ) {}

    /**
     * Rota publica, sem autenticacao — como no legado: quem esta em campo le
     * o adesivo com o celular.
     */
    public function ficha(int $numeroInstalacao): Response
    {
        $vistoria = $this->service->localizarPorNumero($numeroInstalacao);

        abort_if($vistoria === null, SymfonyResponse::HTTP_NOT_FOUND);

        return Inertia::render('Cisterna/QrCode/Ficha', [
            'numero_instalacao' => $numeroInstalacao,
            'beneficiario' => BeneficiarioResource::make($vistoria->beneficiario),
            'instalada_em' => $vistoria->data_relatorio?->toDateString(),
        ]);
    }

    /**
     * PNG do QR Code de uma vistoria, para baixar e imprimir individualmente.
     */
    public function pdfIndividual(CisternaVistoria $vistoria): HttpResponse
    {
        $this->authorize('view', $vistoria);

        $png = $this->service->pngDaVistoria($vistoria);

        return response($png, SymfonyResponse::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename=qrcode-cisterna-'.$vistoria->numero_instalacao.'.png',
        ]);
    }

    /**
     * Folha de adesivos com varios QR Codes. Depende de biblioteca de PDF,
     * ausente no NewSDC.
     */
    public function pdfEmLote(): HttpResponse
    {
        return $this->naoImplementado();
    }

    /**
     * Folhas de QR Codes vazios por faixa de numeracao. Depende de biblioteca
     * de PDF, ausente no NewSDC.
     */
    public function folhasVazias(): HttpResponse
    {
        return $this->naoImplementado();
    }

    private function naoImplementado(): HttpResponse
    {
        return response(
            [
                'message' => 'Impressao em lote de QR Codes ainda nao disponivel: '
                    .'depende de biblioteca de PDF a ser definida para o NewSDC.',
            ],
            SymfonyResponse::HTTP_NOT_IMPLEMENTED,
        );
    }
}
```

- [ ] **Step 8: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=BeneficiarioControllerTest`
Expected: PASS, 15 testes.

Os dois testes que renderizam Inertia (`test_index_...` e `test_ficha_publica_...`) verificam apenas componente e props. As paginas Vue nao existem, e `AssertableInertia` nao as exige.

- [ ] **Step 9: Rodar a suite inteira do modulo**

Run: `scripts/test-host.sh --filter=Cisterna`
Expected: PASS em tudo.

- [ ] **Step 10: Conferir estatica**

Run: `vendor/bin/pint --test app/Modules/Cisterna app/Policies && vendor/bin/phpstan analyse app/Modules/Cisterna --memory-limit=1G`
Expected: sem novos apontamentos. Se o larastan reclamar de `@mixin` nas Resources, conferir se o padrao das Resources dos outros modulos usa a mesma anotacao.

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Cisterna/Controllers \
        app/Modules/Cisterna/Requests/AcaoEmMassaRequest.php \
        tests/Feature/Cisterna/BeneficiarioControllerTest.php
git commit -m "✨ feat(cisterna): controllers do modulo, acoes em massa e ficha publica do QR"
```

**Portao da Fase 2.** Backend completo e verificado: escopo por perfil nos tres casos, cadeia de vistoria, checklist polimorfico, notificacoes, export CSV e QR Code.

---

## FASE 3 — ETL

### Task 15: Tabelas de ETL e o comando de extracao

Landing cru do legado em `jsonb`. **Esta task nao depende da Task 1**: a extracao faz `SELECT *` e guarda a linha inteira como `doc jsonb`, sem conhecer o schema. Coluna inesperada em producao aparece como chave a mais, nao como erro.

**Files:**
- Create: `database/migrations/2026_08_10_120000_create_cisterna_etl_tables.php`
- Create: `app/Modules/Cisterna/Domain/Etl/TabelasLegado.php`
- Create: `app/Modules/Cisterna/Console/ExtrairCisternaLegadoCommand.php`
- Create: `app/Modules/Cisterna/Domain/Etl/RegistroEtl.php`
- Modify: `app/Modules/Cisterna/CisternaServiceProvider.php`
- Test: `tests/Feature/Cisterna/ExtrairLegadoCommandTest.php`

**Interfaces:**
- Consumes: conexao `legado_cisterna_mysql` (Task 5)
- Produces:
  - tabelas `cisterna_legado_raw` e `cisterna_etl_log`
  - `TabelasLegado::ORDEM_DE_CARGA: array<int, string>` — as 8 tabelas na ordem de dependencia
  - `TabelasLegado::CHAVE_PRIMARIA: array<string, string>`
  - `TabelasLegado::RECURSO: array<string, string>` — tabela legada -> recurso do log
  - `RegistroEtl::inserido(...)`, `atualizado(...)`, `ignorado(...)`, `erro(...)` — grava em `cisterna_etl_log`
  - comando `cisterna:extrair-legado` com `--only=`, `--chunk=`, `--truncar`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/ExtrairLegadoCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Domain\Etl\TabelasLegado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExtrairLegadoCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_as_tabelas_de_etl_existem(): void
    {
        $this->assertTrue(Schema::hasTable('cisterna_legado_raw'));
        $this->assertTrue(Schema::hasTable('cisterna_etl_log'));
    }

    public function test_raw_tem_indice_gin_no_doc(): void
    {
        $indice = DB::selectOne(
            'SELECT indexdef FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            ['cisterna_legado_raw', 'cisterna_legado_raw_doc_idx']
        );

        $this->assertNotNull($indice);
        $this->assertStringContainsString('gin', strtolower($indice->indexdef));
        $this->assertStringContainsString('jsonb_path_ops', $indice->indexdef);
    }

    public function test_raw_e_unico_por_tabela_e_id_de_origem(): void
    {
        DB::table('cisterna_legado_raw')->insert([
            'legacy_table' => 'sinc_cisterna',
            'legacy_id' => 1,
            'doc' => json_encode(['nome' => 'Primeiro']),
            'extraido_em' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('cisterna_legado_raw')->insert([
            'legacy_table' => 'sinc_cisterna',
            'legacy_id' => 1,
            'doc' => json_encode(['nome' => 'Duplicado']),
            'extraido_em' => now(),
        ]);
    }

    public function test_ordem_de_carga_respeita_as_dependencias(): void
    {
        $ordem = TabelasLegado::ORDEM_DE_CARGA;

        $this->assertCount(8, $ordem);

        $posicao = array_flip($ordem);

        // Comunidades e lotes antes de tudo o que os referencia.
        $this->assertLessThan($posicao['sinc_cisterna'], $posicao['sinc_cisterna_com']);
        $this->assertLessThan($posicao['sinc_cisterna_ordem_servico'], $posicao['sinc_cisterna_lotes']);
        $this->assertLessThan($posicao['sinc_cisterna'], $posicao['sinc_cisterna_ordem_servico']);
        // As vistorias dependem do beneficiario.
        $this->assertLessThan($posicao['sinc_cisterna_rel_fornecedor'], $posicao['sinc_cisterna']);
        // A conferencia do COMPDEC depende da vistoria do fornecedor.
        $this->assertLessThan($posicao['sinc_cisterna_rel_compdec'], $posicao['sinc_cisterna_rel_fornecedor']);
    }

    public function test_cada_tabela_tem_chave_primaria_e_recurso_declarados(): void
    {
        foreach (TabelasLegado::ORDEM_DE_CARGA as $tabela) {
            $this->assertArrayHasKey($tabela, TabelasLegado::CHAVE_PRIMARIA, "Sem PK: {$tabela}");
            $this->assertArrayHasKey($tabela, TabelasLegado::RECURSO, "Sem recurso: {$tabela}");
        }
    }

    public function test_extracao_falha_com_mensagem_clara_sem_conexao_configurada(): void
    {
        config(['database.connections.legado_cisterna_mysql.host' => '127.0.0.1']);
        config(['database.connections.legado_cisterna_mysql.port' => '1']);
        config(['database.connections.legado_cisterna_mysql.database' => 'inexistente']);
        DB::purge('legado_cisterna_mysql');

        $this->artisan('cisterna:extrair-legado', ['--only' => 'sinc_cisterna_com'])
            ->expectsOutputToContain('Nao foi possivel conectar ao legado')
            ->assertExitCode(1);
    }

    public function test_registro_de_erro_no_log_preserva_o_payload(): void
    {
        DB::table('cisterna_etl_log')->insert([
            'recurso' => 'beneficiarios',
            'legacy_table' => 'sinc_cisterna',
            'legacy_id' => 42,
            'new_id' => null,
            'acao' => 'error',
            'motivo' => 'Municipio sem correspondencia IBGE',
            'payload_legado' => json_encode(['codmundv' => '0000000']),
            'created_at' => now(),
        ]);

        $linha = DB::table('cisterna_etl_log')->where('legacy_id', 42)->first();

        $this->assertSame('error', $linha->acao);
        $this->assertSame('0000000', json_decode($linha->payload_legado, true)['codmundv']);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=ExtrairLegadoCommandTest`
Expected: FAIL — as tabelas nao existem.

- [ ] **Step 3: Escrever a migration das tabelas de ETL**

`database/migrations/2026_08_10_120000_create_cisterna_etl_tables.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas de apoio ao ETL do modulo Cisterna. **Efemeras:** drop depois da
 * validacao em producao, como compdec_etl_log.
 *
 * Sem FK para o dominio de proposito: cisterna_legado_raw e o espelho cru do
 * legado, e cisterna_etl_log registra justamente as linhas que NAO
 * conseguiram virar registro — uma FK impediria o registro do erro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cisterna_legado_raw')) {
            Schema::create('cisterna_legado_raw', function (Blueprint $table): void {
                $table->id();

                $table->string('legacy_table', 40);
                $table->unsignedBigInteger('legacy_id');

                // A linha inteira, como veio do MySQL. A extracao nao conhece
                // o schema de producao: coluna inesperada entra como chave a
                // mais em vez de quebrar a carga.
                $table->jsonb('doc');

                $table->timestampTz('extraido_em')->useCurrent();

                $table->unique(['legacy_table', 'legacy_id']);
            });

            if (DB::getDriverName() === 'pgsql') {
                // jsonb_path_ops: menor e mais rapido que o GIN padrao para
                // consultas de contencao, que e o uso aqui. Mesmo padrao de
                // ajuda_h_legado_raw.
                DB::statement(
                    'CREATE INDEX cisterna_legado_raw_doc_idx '
                    .'ON cisterna_legado_raw USING gin (doc jsonb_path_ops)'
                );
            }
        }

        if (Schema::hasTable('cisterna_etl_log')) {
            return;
        }

        // Mesma forma de compdec_etl_log (2026_05_07_100000).
        Schema::create('cisterna_etl_log', function (Blueprint $table): void {
            $table->id();

            $table->string('recurso', 40)
                ->comment('comunidades|lotes|os|beneficiarios|vistorias|itens|notificacoes|midia');

            $table->string('legacy_table', 40);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('new_id')->nullable();

            $table->string('acao', 20)
                ->comment('inserted|updated|skipped|error');

            $table->text('motivo')->nullable();
            $table->jsonb('payload_legado')->nullable();

            $table->timestampTz('created_at')->useCurrent();

            $table->index(['recurso', 'acao']);
            $table->index('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cisterna_etl_log');
        Schema::dropIfExists('cisterna_legado_raw');
    }
};
```

- [ ] **Step 4: Escrever `TabelasLegado`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

/**
 * Mapa das tabelas do legado `sdc` e a ordem de carga.
 *
 * A ordem respeita as dependencias do dominio novo: comunidades e lotes
 * primeiro, porque o beneficiario referencia os dois; o beneficiario antes
 * das vistorias; a vistoria do fornecedor antes da conferencia do COMPDEC,
 * porque sinc_cisterna_rel_compdec.instalacao_id aponta para
 * sinc_cisterna_rel_fornecedor.id, nao para a cisterna.
 *
 * `sinc_cisterna_relatorio` (89 campos, sem rota nem controller) e
 * `sinc_cisterna_old` (schema anterior) estao deliberadamente ausentes.
 */
final class TabelasLegado
{
    /**
     * @var array<int, string>
     */
    public const ORDEM_DE_CARGA = [
        'sinc_cisterna_com',
        'sinc_cisterna_lotes',
        'sinc_cisterna_ordem_servico',
        'sinc_cisterna',
        'sinc_cisterna_rel_fornecedor',
        'sinc_cisterna_rel_compdec',
        'sinc_cisterna_rel_cedec',
        'sinc_cisterna_notificacoes',
    ];

    /**
     * @var array<string, string>
     */
    public const CHAVE_PRIMARIA = [
        'sinc_cisterna_com' => 'id',
        'sinc_cisterna_lotes' => 'id',
        'sinc_cisterna_ordem_servico' => 'id',
        'sinc_cisterna' => 'id',
        'sinc_cisterna_rel_fornecedor' => 'id',
        'sinc_cisterna_rel_compdec' => 'id',
        'sinc_cisterna_rel_cedec' => 'id',
        'sinc_cisterna_notificacoes' => 'id',
    ];

    /**
     * Rotulo do recurso no cisterna_etl_log.
     *
     * @var array<string, string>
     */
    public const RECURSO = [
        'sinc_cisterna_com' => 'comunidades',
        'sinc_cisterna_lotes' => 'lotes',
        'sinc_cisterna_ordem_servico' => 'os',
        'sinc_cisterna' => 'beneficiarios',
        'sinc_cisterna_rel_fornecedor' => 'vistorias',
        'sinc_cisterna_rel_compdec' => 'vistorias',
        'sinc_cisterna_rel_cedec' => 'vistorias',
        'sinc_cisterna_notificacoes' => 'notificacoes',
    ];

    /**
     * @return array<int, string>
     */
    public static function resolverSelecao(?string $only): array
    {
        if ($only === null || trim($only) === '') {
            return self::ORDEM_DE_CARGA;
        }

        $pedidas = array_map('trim', explode(',', $only));

        return array_values(array_filter(
            self::ORDEM_DE_CARGA,
            fn (string $tabela): bool => in_array($tabela, $pedidas, true)
        ));
    }
}
```

- [ ] **Step 5: Escrever `RegistroEtl`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Escrita no cisterna_etl_log. Registra as quatro acoes, nao apenas as
 * falhas: `skipped` por idempotencia e `updated` por reprocesso sao o que
 * permite auditar uma carga de milhares de linhas.
 */
final class RegistroEtl
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function inserido(string $recurso, string $tabela, int $legacyId, int $newId, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'inserted', $newId, null, $payload);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function atualizado(string $recurso, string $tabela, int $legacyId, int $newId, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'updated', $newId, null, $payload);
    }

    public static function ignorado(string $recurso, string $tabela, int $legacyId, string $motivo, ?int $newId = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'skipped', $newId, $motivo, null);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function erro(string $recurso, string $tabela, int $legacyId, string $motivo, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'error', null, $motivo, $payload);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private static function gravar(
        string $recurso,
        string $tabela,
        int $legacyId,
        string $acao,
        ?int $newId,
        ?string $motivo,
        ?array $payload,
    ): void {
        DB::table('cisterna_etl_log')->insert([
            'recurso' => $recurso,
            'legacy_table' => $tabela,
            'legacy_id' => $legacyId,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, int> acao => quantidade
     */
    public static function resumo(?string $recurso = null): array
    {
        return DB::table('cisterna_etl_log')
            ->when($recurso !== null, fn ($q) => $q->where('recurso', $recurso))
            ->selectRaw('acao, COUNT(*) AS total')
            ->groupBy('acao')
            ->pluck('total', 'acao')
            ->map(fn ($t): int => (int) $t)
            ->all();
    }
}
```

- [ ] **Step 6: Escrever `ExtrairCisternaLegadoCommand`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Console;

use App\Modules\Cisterna\Domain\Etl\TabelasLegado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Etapa 1 do ETL: espelha o legado MySQL em cisterna_legado_raw.doc jsonb.
 *
 * Deliberadamente **nao conhece o schema** das tabelas de origem. Faz
 * SELECT * e guarda a linha inteira. Isso remove a dependencia do
 * SHOW CREATE TABLE de producao (spec secao 7.4) desta etapa: coluna
 * inesperada aparece como chave a mais no doc, nao como erro.
 *
 * Mesmo padrao de AjudaHumanitaria\Console\ExtrairLegadoAjuCommand.
 */
class ExtrairCisternaLegadoCommand extends Command
{
    protected $signature = 'cisterna:extrair-legado
                            {--only= : Tabelas separadas por virgula (padrao: todas)}
                            {--chunk=500 : Linhas por lote}
                            {--truncar : Limpa cisterna_legado_raw antes de extrair}';

    protected $description = 'Extrai as tabelas do modulo Cisterna do legado sdc para cisterna_legado_raw (jsonb).';

    public function handle(): int
    {
        $tabelas = TabelasLegado::resolverSelecao($this->option('only'));

        if ($tabelas === []) {
            $this->error('Nenhuma tabela reconhecida em --only. Conhecidas: '
                .implode(', ', TabelasLegado::ORDEM_DE_CARGA));

            return self::FAILURE;
        }

        try {
            $legado = DB::connection('legado_cisterna_mysql');
            $legado->getPdo();
        } catch (Throwable $e) {
            $this->error('Nao foi possivel conectar ao legado: '.$e->getMessage());
            $this->line('Conferir LEGADO_CISTERNA_DB_* no .env.');

            return self::FAILURE;
        }

        if ($this->option('truncar')) {
            DB::table('cisterna_legado_raw')->whereIn('legacy_table', $tabelas)->delete();
            $this->line('cisterna_legado_raw limpa para: '.implode(', ', $tabelas));
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $totalGeral = 0;

        foreach ($tabelas as $tabela) {
            $pk = TabelasLegado::CHAVE_PRIMARIA[$tabela];

            if (! $legado->getSchemaBuilder()->hasTable($tabela)) {
                $this->warn("Tabela ausente no legado, ignorada: {$tabela}");
                continue;
            }

            $total = 0;

            // orderBy na PK e obrigatorio para o chunkById nao repetir linha.
            $legado->table($tabela)->orderBy($pk)->chunkById(
                $chunk,
                function ($linhas) use ($tabela, $pk, &$total): void {
                    $agora = now();

                    $registros = $linhas->map(fn ($linha): array => [
                        'legacy_table' => $tabela,
                        'legacy_id' => (int) $linha->{$pk},
                        'doc' => json_encode((array) $linha, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                        'extraido_em' => $agora,
                    ])->all();

                    // Idempotente: reextrair atualiza o doc em vez de estourar
                    // o unique (legacy_table, legacy_id).
                    DB::table('cisterna_legado_raw')->upsert(
                        $registros,
                        ['legacy_table', 'legacy_id'],
                        ['doc', 'extraido_em'],
                    );

                    $total += count($registros);
                },
                $pk,
            );

            $this->line(sprintf('%-32s %6d linha(s)', $tabela, $total));
            $totalGeral += $total;
        }

        $this->newLine();
        $this->info("Extracao concluida: {$totalGeral} linha(s) em cisterna_legado_raw.");
        $this->line('Proximo passo: $PHP artisan cisterna:refinar-legado --dry-run');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 7: Registrar o comando no ServiceProvider**

Em `app/Modules/Cisterna/CisternaServiceProvider.php`, dentro de `boot()`:

```php
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Cisterna\Console\ExtrairCisternaLegadoCommand::class,
                \App\Modules\Cisterna\Console\RefinarCisternaLegadoCommand::class,
            ]);
        }
```

`RefinarCisternaLegadoCommand` so existe a partir da Task 16. Ate la, registrar apenas o de extracao e acrescentar o segundo na Task 16 — senao o `php artisan` inteiro quebra por classe ausente.

- [ ] **Step 8: Rodar a migration e o teste**

Run: `$PHP artisan migrate && scripts/test-host.sh --filter=ExtrairLegadoCommandTest`
Expected: PASS, 7 testes.

- [ ] **Step 9: Extrair de verdade e fechar os enums pendentes**

Com a conexao do legado configurada:

Run: `$PHP artisan cisterna:extrair-legado`

Depois, os valores distintos de moradia e cobertura — que a Task 1 nao conseguiu obter sem acesso a producao — saem do proprio `doc`:

```bash
$PHP artisan tinker --execute="
dump(DB::table('cisterna_legado_raw')
  ->where('legacy_table', 'sinc_cisterna')
  ->selectRaw(\"doc->>'moradia' AS valor, COUNT(*) AS qtd\")
  ->groupBy('valor')->orderByDesc('qtd')->get());
dump(DB::table('cisterna_legado_raw')
  ->where('legacy_table', 'sinc_cisterna')
  ->selectRaw(\"doc->>'coberturaTelhado' AS valor, COUNT(*) AS qtd\")
  ->groupBy('valor')->orderByDesc('qtd')->get());"
```

Registrar a saida no arquivo de anotacoes da Task 1. A Task 17 usa esses valores para criar os enums `TipoMoradia` e `CoberturaTelhado` e a CHECK constraint.

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_08_10_120000_create_cisterna_etl_tables.php \
        app/Modules/Cisterna/Domain/Etl \
        app/Modules/Cisterna/Console/ExtrairCisternaLegadoCommand.php \
        app/Modules/Cisterna/CisternaServiceProvider.php \
        tests/Feature/Cisterna/ExtrairLegadoCommandTest.php
git commit -m "🗃️ db(cisterna): landing do legado em jsonb e comando de extracao"
```

---

### Task 16: Refino — comunidades, lotes e ordens de servico

Esqueleto do comando de refino mais os tres primeiros refinadores. Resolve a ambiguidade de comunidades homonimas (lacuna L5, defeito C18).

**Files:**
- Create: `app/Modules/Cisterna/Domain/Etl/PonteMunicipio.php`
- Create: `app/Modules/Cisterna/Domain/Etl/LeitorRaw.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaComunidades.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaLotes.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaOrdensServico.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/Refinador.php` (interface)
- Create: `app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php`
- Test: `tests/Feature/Cisterna/RefinarComunidadesLotesOsTest.php`

**Interfaces:**
- Consumes: `cisterna_legado_raw` (Task 15), `RegistroEtl`, `TabelasLegado`, models e services das Tasks 4 e 11
- Produces:
  - `PonteMunicipio::resolver(?string $codmundv): ?int` — Codmundv -> `municipios.id`, memoizado
  - `LeitorRaw::porTabela(string, int $chunk, callable $callback): void`
  - `LeitorRaw::contar(string): int`
  - interface `Refinador` com `recurso(): string`, `tabelaLegado(): string`, `refinar(array $doc, int $legacyId, bool $dryRun): void`
  - comando `cisterna:refinar-legado` com `--only=`, `--chunk=`, `--dry-run`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/RefinarComunidadesLotesOsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\Municipio;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefinarComunidadesLotesOsTest extends TestCase
{
    use DatabaseTransactions;

    private Municipio $municipioA;

    private Municipio $municipioB;

    protected function setUp(): void
    {
        parent::setUp();

        // Codigos IBGE ficticios: o banco de teste ja tem os 853 reais.
        $this->municipioA = Municipio::firstOrCreate(
            ['codigo_ibge' => '9999901'],
            ['nome' => 'Municipio ETL A', 'uf' => 'MG']
        );
        $this->municipioB = Municipio::firstOrCreate(
            ['codigo_ibge' => '9999902'],
            ['nome' => 'Municipio ETL B', 'uf' => 'MG']
        );
    }

    public function test_refina_comunidade_resolvendo_o_municipio_por_codmundv(): void
    {
        $this->semear('sinc_cisterna_com', 1, [
            'id' => 1,
            'codmundv' => '9999901',
            'municipio' => 'Municipio ETL A',
            'comunidade' => 'Agua Boa',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])->assertExitCode(0);

        $comunidade = CisternaComunidade::where('legacy_id', 1)->first();
        $this->assertNotNull($comunidade);
        $this->assertSame('Agua Boa', $comunidade->nome);
        $this->assertSame($this->municipioA->id, $comunidade->municipio_id);
    }

    public function test_comunidades_homonimas_de_municipios_distintos_viram_registros_distintos(): void
    {
        $this->semear('sinc_cisterna_com', 1, [
            'id' => 1, 'codmundv' => '9999901', 'comunidade' => 'Sao Jose',
        ]);
        $this->semear('sinc_cisterna_com', 2, [
            'id' => 2, 'codmundv' => '9999902', 'comunidade' => 'Sao Jose',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])->assertExitCode(0);

        // O legado somava a contagem dessas duas; aqui sao registros
        // separados, um por municipio.
        $this->assertSame(2, CisternaComunidade::whereIn('legacy_id', [1, 2])->count());
    }

    public function test_comunidade_com_municipio_sem_correspondencia_vira_erro_no_log(): void
    {
        $this->semear('sinc_cisterna_com', 9, [
            'id' => 9, 'codmundv' => '0000000', 'comunidade' => 'Orfa',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])->assertExitCode(0);

        $this->assertNull(CisternaComunidade::where('legacy_id', 9)->first());

        $log = DB::table('cisterna_etl_log')
            ->where('legacy_table', 'sinc_cisterna_com')
            ->where('legacy_id', 9)
            ->first();

        $this->assertSame('error', $log->acao);
        $this->assertStringContainsString('municipio', strtolower($log->motivo));
    }

    public function test_dry_run_nao_escreve_no_dominio_mas_registra_o_que_faria(): void
    {
        $this->semear('sinc_cisterna_com', 5, [
            'id' => 5, 'codmundv' => '9999901', 'comunidade' => 'Nao Deve Persistir',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades', '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull(CisternaComunidade::where('legacy_id', 5)->first());
        $this->assertGreaterThan(
            0,
            DB::table('cisterna_etl_log')->where('legacy_id', 5)->count()
        );
    }

    public function test_refino_e_idempotente(): void
    {
        $this->semear('sinc_cisterna_com', 1, [
            'id' => 1, 'codmundv' => '9999901', 'comunidade' => 'Agua Boa',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])->assertExitCode(0);
        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])->assertExitCode(0);

        $this->assertSame(1, CisternaComunidade::where('legacy_id', 1)->count());

        // A segunda passada e updated ou skipped, nunca um segundo inserted.
        $this->assertSame(
            1,
            DB::table('cisterna_etl_log')
                ->where('legacy_id', 1)
                ->where('legacy_table', 'sinc_cisterna_com')
                ->where('acao', 'inserted')
                ->count()
        );
    }

    public function test_refina_lote_e_ordem_de_servico_ligando_pela_fk(): void
    {
        $this->semear('sinc_cisterna_lotes', 7, [
            'id' => 7, 'nome' => 'Lote 007', 'data' => '2025-03-14',
        ]);
        $this->semear('sinc_cisterna_ordem_servico', 70, [
            'id' => 70, 'nome' => 'OS 070', 'lote_id' => 7, 'obs' => 'Primeira OS',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'lotes,os'])->assertExitCode(0);

        $lote = CisternaLote::where('legacy_id', 7)->first();
        $os = CisternaOrdemServico::where('legacy_id', 70)->first();

        $this->assertNotNull($lote);
        $this->assertSame('Lote 007', $lote->nome);
        $this->assertSame('2025-03-14', $lote->data->toDateString());

        $this->assertNotNull($os);
        $this->assertSame($lote->id, $os->lote_id);
        $this->assertSame('Primeira OS', $os->observacao);
    }

    public function test_os_com_lote_inexistente_vira_erro_no_log(): void
    {
        $this->semear('sinc_cisterna_ordem_servico', 99, [
            'id' => 99, 'nome' => 'OS Orfa', 'lote_id' => 4242,
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'os'])->assertExitCode(0);

        $this->assertNull(CisternaOrdemServico::where('legacy_id', 99)->first());

        $log = DB::table('cisterna_etl_log')->where('legacy_id', 99)->first();
        $this->assertSame('error', $log->acao);
    }

    public function test_resumo_final_aparece_na_saida(): void
    {
        $this->semear('sinc_cisterna_com', 1, [
            'id' => 1, 'codmundv' => '9999901', 'comunidade' => 'Agua Boa',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'comunidades'])
            ->expectsOutputToContain('inserted')
            ->assertExitCode(0);
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function semear(string $tabela, int $legacyId, array $doc): void
    {
        DB::table('cisterna_legado_raw')->insert([
            'legacy_table' => $tabela,
            'legacy_id' => $legacyId,
            'doc' => json_encode($doc, JSON_UNESCAPED_UNICODE),
            'extraido_em' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=RefinarComunidadesLotesOsTest`
Expected: FAIL — comando `cisterna:refinar-legado` inexistente.

- [ ] **Step 3: Escrever `PonteMunicipio`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Ponte Codmundv -> municipios.id.
 *
 * O legado guardava `codmundv` (codigo IBGE com digito verificador) como
 * varchar, e o nome do municipio duplicado como texto em quatro tabelas.
 * `cedec_municipio` e a ponte oficial do projeto:
 * cedec_municipio.Codmundv = municipios.codigo_ibge (ver
 * ImportCedecMunicipioCommand).
 *
 * Memoizado: uma carga de milhares de beneficiarios resolve os mesmos ~200
 * municipios repetidamente.
 */
final class PonteMunicipio
{
    /**
     * @var array<string, int|null>
     */
    private array $memo = [];

    public function resolver(?string $codmundv): ?int
    {
        $codigo = $this->normalizar($codmundv);

        if ($codigo === null) {
            return null;
        }

        if (array_key_exists($codigo, $this->memo)) {
            return $this->memo[$codigo];
        }

        $id = DB::table('municipios')->where('codigo_ibge', $codigo)->value('id');

        return $this->memo[$codigo] = $id === null ? null : (int) $id;
    }

    /**
     * Fallback por nome, para linhas do legado sem codmundv. Retorna null
     * quando o nome casa com mais de um municipio — nesse caso o refino
     * registra erro em vez de escolher no escuro.
     */
    public function resolverPorNome(?string $nome): ?int
    {
        if ($nome === null || trim($nome) === '') {
            return null;
        }

        $ids = DB::table('municipios')
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($nome))])
            ->pluck('id');

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    private function normalizar(?string $codmundv): ?string
    {
        if ($codmundv === null) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $codmundv) ?? '';

        return strlen($digitos) === 7 ? $digitos : null;
    }
}
```

- [ ] **Step 4: Escrever `LeitorRaw` e a interface `Refinador`**

`LeitorRaw.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Leitura paginada de cisterna_legado_raw, decodificando o doc jsonb.
 */
final class LeitorRaw
{
    /**
     * @param  callable(array<string, mixed> $doc, int $legacyId): void  $callback
     */
    public function porTabela(string $legacyTable, int $chunk, callable $callback): void
    {
        DB::table('cisterna_legado_raw')
            ->where('legacy_table', $legacyTable)
            ->orderBy('id')
            ->chunkById($chunk, function ($linhas) use ($callback): void {
                foreach ($linhas as $linha) {
                    $doc = json_decode((string) $linha->doc, true);

                    $callback(is_array($doc) ? $doc : [], (int) $linha->legacy_id);
                }
            });
    }

    public function contar(string $legacyTable): int
    {
        return DB::table('cisterna_legado_raw')->where('legacy_table', $legacyTable)->count();
    }
}
```

`Refinadores/Refinador.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

interface Refinador
{
    /**
     * Rotulo do recurso no cisterna_etl_log.
     */
    public function recurso(): string;

    public function tabelaLegado(): string;

    /**
     * @param  array<string, mixed>  $doc  Linha crua do legado.
     */
    public function refinar(array $doc, int $legacyId, bool $dryRun): void;
}
```

- [ ] **Step 5: Escrever os tres refinadores**

`Refinadores/RefinaComunidades.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\PonteMunicipio;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaComunidade;

class RefinaComunidades implements Refinador
{
    public function __construct(
        private readonly PonteMunicipio $ponte,
    ) {}

    public function recurso(): string
    {
        return 'comunidades';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_com';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['comunidade'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Comunidade sem nome.', $doc);

            return;
        }

        $municipioId = $this->ponte->resolver($doc['codmundv'] ?? null)
            ?? $this->ponte->resolverPorNome($doc['municipio'] ?? null);

        if ($municipioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Municipio sem correspondencia IBGE em municipios.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria comunidade \"{$nome}\" no municipio {$municipioId}.");

            return;
        }

        $existente = CisternaComunidade::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update(['municipio_id' => $municipioId, 'nome' => $nome]);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        // Comunidade ja cadastrada manualmente com o mesmo par
        // (municipio, nome): adota em vez de estourar o unique.
        $mesmoPar = CisternaComunidade::where('municipio_id', $municipioId)
            ->where('nome', $nome)
            ->first();

        if ($mesmoPar !== null) {
            $mesmoPar->update(['legacy_id' => $legacyId]);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $mesmoPar->id);

            return;
        }

        $criada = CisternaComunidade::create([
            'municipio_id' => $municipioId,
            'nome' => $nome,
            'ativa' => true,
            'legacy_id' => $legacyId,
        ]);

        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
    }
}
```

`Refinadores/RefinaLotes.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaLote;

class RefinaLotes implements Refinador
{
    public function recurso(): string
    {
        return 'lotes';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_lotes';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['nome'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Lote sem nome.', $doc);

            return;
        }

        $atributos = [
            'nome' => $nome,
            'data' => $this->dataOuNulo($doc['data'] ?? null),
            'legacy_id' => $legacyId,
        ];

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria lote \"{$nome}\".");

            return;
        }

        $existente = CisternaLote::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update($atributos);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        $criado = CisternaLote::create($atributos);
        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criado->id);
    }

    /**
     * O legado guardava data em varchar; valor invalido vira null em vez de
     * derrubar a carga.
     */
    private function dataOuNulo(mixed $valor): ?string
    {
        if ($valor === null || $valor === '' || $valor === '0000-00-00') {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::parse((string) $valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
```

`Refinadores/RefinaOrdensServico.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;

class RefinaOrdensServico implements Refinador
{
    public function recurso(): string
    {
        return 'os';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_ordem_servico';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $nome = trim((string) ($doc['nome'] ?? ''));

        if ($nome === '') {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Ordem de servico sem nome.', $doc);

            return;
        }

        $loteLegacyId = $doc['lote_id'] ?? null;
        $loteId = $loteLegacyId === null
            ? null
            : CisternaLote::where('legacy_id', (int) $loteLegacyId)->value('id');

        if ($loteId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Lote de origem {$loteLegacyId} nao encontrado. Refinar lotes antes.", $doc);

            return;
        }

        $atributos = [
            'lote_id' => (int) $loteId,
            'nome' => $nome,
            // Legado: coluna obs. O link_doc vira collection documento_os na
            // etapa de midia.
            'observacao' => $this->textoOuNulo($doc['obs'] ?? null),
            'legacy_id' => $legacyId,
        ];

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria OS \"{$nome}\" no lote {$loteId}.");

            return;
        }

        $existente = CisternaOrdemServico::where('legacy_id', $legacyId)->first();

        if ($existente !== null) {
            $existente->update($atributos);
            RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

            return;
        }

        $criada = CisternaOrdemServico::create($atributos);
        RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
    }

    private function textoOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
```

- [ ] **Step 6: Escrever `RefinarCisternaLegadoCommand`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Console;

use App\Modules\Cisterna\Domain\Etl\LeitorRaw;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaComunidades;
use App\Modules\Cisterna\Domain\Etl\Refinadores\Refinador;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaLotes;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaOrdensServico;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use Illuminate\Console\Command;
use Throwable;

/**
 * Etapa 2 do ETL: cisterna_legado_raw.doc jsonb -> tabelas do dominio.
 *
 * Idempotente por legacy_id: rodar duas vezes atualiza em vez de duplicar.
 * Cada linha e tratada isoladamente — um erro entra no cisterna_etl_log com
 * o payload de origem e a carga segue, em vez de abortar tudo.
 *
 * Mesmo padrao de AjudaHumanitaria\Console\RefinarLegadoAjuCommand.
 */
class RefinarCisternaLegadoCommand extends Command
{
    protected $signature = 'cisterna:refinar-legado
                            {--only= : Recursos separados por virgula: comunidades,lotes,os,beneficiarios,vistorias,itens,notificacoes,midia}
                            {--chunk=500 : Linhas por lote}
                            {--dry-run : Nao escreve no dominio; registra no cisterna_etl_log o que faria}';

    protected $description = 'Refina cisterna_legado_raw para as tabelas do dominio Cisterna.';

    public function handle(LeitorRaw $leitor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY-RUN: nada sera escrito nas tabelas do dominio.');
        }

        $selecionados = $this->refinadoresSelecionados();

        if ($selecionados === []) {
            $this->error('Nenhum recurso reconhecido em --only. Conhecidos: '
                .implode(', ', array_keys($this->todosOsRefinadores())));

            return self::FAILURE;
        }

        foreach ($selecionados as $recurso => $refinador) {
            $total = $leitor->contar($refinador->tabelaLegado());

            if ($total === 0) {
                $this->warn("Sem linhas em cisterna_legado_raw para {$refinador->tabelaLegado()}. "
                    .'Rodar cisterna:extrair-legado primeiro.');
                continue;
            }

            $this->line("Refinando {$recurso} ({$total} linha(s))...");
            $barra = $this->output->createProgressBar($total);

            $leitor->porTabela(
                $refinador->tabelaLegado(),
                $chunk,
                function (array $doc, int $legacyId) use ($refinador, $dryRun, $barra): void {
                    try {
                        $refinador->refinar($doc, $legacyId, $dryRun);
                    } catch (Throwable $e) {
                        // Erro de uma linha nao derruba a carga inteira.
                        RegistroEtl::erro(
                            $refinador->recurso(),
                            $refinador->tabelaLegado(),
                            $legacyId,
                            'Excecao no refino: '.$e->getMessage(),
                            $doc,
                        );
                    }

                    $barra->advance();
                },
            );

            $barra->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Resumo do cisterna_etl_log:');

        foreach (RegistroEtl::resumo() as $acao => $quantidade) {
            $this->line(sprintf('  %-10s %6d', $acao, $quantidade));
        }

        $this->newLine();
        $this->line('Detalhe dos erros:');
        $this->line("  $PHP artisan tinker --execute=\"dump(DB::table('cisterna_etl_log')"
            ."->where('acao','error')->get(['recurso','legacy_id','motivo']));\"");

        return self::SUCCESS;
    }

    /**
     * @return array<string, Refinador>
     */
    private function todosOsRefinadores(): array
    {
        // A ordem importa: comunidades e lotes antes do que os referencia.
        // Os demais recursos entram nas Tasks 17 e 18.
        return [
            'comunidades' => app(RefinaComunidades::class),
            'lotes' => app(RefinaLotes::class),
            'os' => app(RefinaOrdensServico::class),
        ];
    }

    /**
     * @return array<string, Refinador>
     */
    private function refinadoresSelecionados(): array
    {
        $todos = $this->todosOsRefinadores();
        $only = $this->option('only');

        if ($only === null || trim((string) $only) === '') {
            return $todos;
        }

        $pedidos = array_map('trim', explode(',', (string) $only));

        return array_filter(
            $todos,
            fn (string $recurso): bool => in_array($recurso, $pedidos, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
```

- [ ] **Step 7: Registrar o comando**

Acrescentar `RefinarCisternaLegadoCommand::class` ao array `commands()` do `CisternaServiceProvider` — a linha ja foi preparada no Step 7 da Task 15.

- [ ] **Step 8: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=RefinarComunidadesLotesOsTest`
Expected: PASS, 8 testes.

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Cisterna/Domain/Etl \
        app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php \
        app/Modules/Cisterna/CisternaServiceProvider.php \
        tests/Feature/Cisterna/RefinarComunidadesLotesOsTest.php
git commit -m "✨ feat(cisterna): refino do ETL para comunidades, lotes e ordens de servico"
```

---

### Task 17: Refino dos beneficiarios e fechamento dos enums pendentes

A tabela mais larga do legado: 54 colunas `varchar(150)`, incluindo datas, moeda, medidas e booleanos. Fecha tambem os enums `TipoMoradia` e `CoberturaTelhado`, cujos valores sairam do `doc jsonb` no Step 9 da Task 15.

**Files:**
- Create: `app/Modules/Cisterna/Enums/TipoMoradia.php`
- Create: `app/Modules/Cisterna/Enums/CoberturaTelhado.php`
- Create: `database/migrations/2026_08_10_130000_add_checks_moradia_cobertura_cisterna.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaBeneficiarios.php`
- Modify: `app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php`
- Test: `tests/Feature/Cisterna/RefinarBeneficiariosTest.php`

**Interfaces:**
- Consumes: `PonteMunicipio`, `RegistroEtl` (Task 16), `NormalizaEntrada` (Task 7), `SituacaoAnalise::doLegado()`, `SituacaoObra::doLegado()` (Task 2)
- Produces:
  - `TipoMoradia` e `CoberturaTelhado` com `label()`, `options()`, `valores()`, `doLegado(?string): ?self`
  - `RefinaBeneficiarios` implementando `Refinador`
  - CHECK constraints em `tipo_moradia` e `cobertura_telhado`

- [ ] **Step 1: Conferir os casos, ja medidos em producao**

**Esta task nao esta mais bloqueada.** Os valores sairam da analise do dump (spec 4.6.3), com a frequencia de cada um sobre as 8.105 linhas:

`moradia` — `varchar(7)`:

| Valor no legado | Linhas | Case |
|---|---|---|
| `PROPRIA` | 7.697 | `propria` |
| `PR?PRIA` | **67** | `propria` — encoding corrompido: "PRÓPRIA" nao cabe em `varchar(7)` utf8mb3 |
| `0` | 162 | null |
| `Outros` | 108 | `outros` |
| `CEDIDA` | 57 | `cedida` |
| `ALUGADA` | 14 | `alugada` |

`coberturaTelhado` — `varchar(12)`:

| Valor no legado | Linhas | Case |
|---|---|---|
| `pvc` | 4.963 | `pvc` |
| `ceramica` | 2.883 | `ceramica` |
| `fibrocimento` | 157 | `fibrocimento` |
| `zinco` | 39 | `zinco` |
| `Outros` | 22 | `outros` |
| `0` | 14 | null |
| `Concreto` | 11 | `concreto` |
| `metalica` | 10 | `metalica` |
| `amianto` | 6 | `amianto` |

Duas decisoes tomadas na leitura desses dados:

- **`fibrocimento` e `amianto` ficam separados.** Tecnicamente fibrocimento e cimento-amianto, mas os usuarios os distinguem no formulario e os dois valores coexistem. Unificar apagaria uma distincao que alguem faz de proposito.
- **`PR?PRIA` mapeia para `propria`.** Sao 67 cadastros que o `varchar(7)` truncou; nao e um tipo de moradia.

Reconferir contra o banco de trabalho da Task 1 antes de escrever:

```bash
MY=/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin
"$MY/mysql.exe" -u root -h 127.0.0.1 cisterna_analise -e "
SELECT moradia, COUNT(*) n FROM sinc_cisterna GROUP BY moradia ORDER BY n DESC;
SELECT coberturaTelhado, COUNT(*) n FROM sinc_cisterna GROUP BY coberturaTelhado ORDER BY n DESC;"
```

- [ ] **Step 2: Escrever o teste que falha**

`tests/Feature/Cisterna/RefinarBeneficiariosTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Models\Municipio;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefinarBeneficiariosTest extends TestCase
{
    use DatabaseTransactions;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipio = Municipio::firstOrCreate(
            ['codigo_ibge' => '9999911'],
            ['nome' => 'Municipio Benef ETL', 'uf' => 'MG']
        );
    }

    public function test_converte_tipos_do_legado_corretamente(): void
    {
        $this->semear(100, [
            'id' => 100,
            'codmundv' => '9999911',
            'nome' => 'Jose do Legado',
            'cpf' => '529.982.247-25',
            'dtNasc' => '1975-06-15',
            'tel' => '(31) 98888-7777',
            'cadUnico' => '123456789012',
            'endereco' => 'Sitio Boa Esperanca',
            'latitude' => '-19,912998',
            'longitude' => '-43,940933',
            'qtdPessoa' => '4',
            'renda' => 'R$ 1.200,00',
            'rendaPerCapita' => 'R$ 300,00',
            'compTelhado' => '10,5',
            'larguracompTelhado' => '6',
            'areaTotalTelhado' => '63',
            'compTestada' => '10,5',
            'numCaidaTelhado' => '2',
            'aprovado' => '1',
            'estado' => '2',
            'existeFogaoLenha' => 'nao',
            'atendPipa' => 'sim',
            'nomeAgente' => 'Agente Legado',
            'cpfAgente' => '111.444.777-35',
            'nomeEng' => 'Eng Legado',
            'creaEng' => 'MG-999',
            'outrObs' => 'Observacao do legado',
            'aprovado_obs' => 'Ressalva anotada',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $b = CisternaBeneficiario::where('legacy_id', 100)->first();

        $this->assertNotNull($b);
        // CPF sem mascara, 11 digitos.
        $this->assertSame('52998224725', $b->cpf);
        $this->assertSame('11144477735', $b->agente_cpf);
        // Moeda mascarada vira decimal.
        $this->assertSame('1200.00', $b->renda);
        $this->assertSame('300.00', $b->renda_per_capita);
        // Virgula decimal vira ponto.
        $this->assertSame('10.50', $b->comprimento_telhado);
        $this->assertSame('-19.9129980', $b->latitude);
        // Codigos numericos viram enum.
        $this->assertSame(SituacaoAnalise::APROVADO, $b->situacao_analise);
        $this->assertSame(SituacaoObra::INSTALADO, $b->situacao_obra);
        // sim/nao vira boolean.
        $this->assertFalse($b->possui_fogao_lenha);
        $this->assertTrue($b->atendido_por_pipa);
        // Datas.
        $this->assertSame('1975-06-15', $b->data_nascimento->toDateString());
        // Observacoes: outrObs -> observacoes, aprovado_obs -> situacao_analise_obs.
        $this->assertSame('Observacao do legado', $b->observacoes);
        $this->assertSame('Ressalva anotada', $b->situacao_analise_obs);
    }

    public function test_resolve_comunidade_pelo_par_municipio_e_nome(): void
    {
        $comunidade = CisternaComunidade::create([
            'municipio_id' => $this->municipio->id,
            'nome' => 'Agua Boa',
            'ativa' => true,
        ]);

        $this->semear(101, [
            'id' => 101, 'codmundv' => '9999911', 'nome' => 'Com Comunidade',
            'cpf' => '11144477735', 'comunidade' => 'Agua Boa',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertSame(
            $comunidade->id,
            CisternaBeneficiario::where('legacy_id', 101)->value('comunidade_id')
        );
    }

    public function test_comunidade_inexistente_deixa_fk_nula_e_registra_no_log(): void
    {
        $this->semear(102, [
            'id' => 102, 'codmundv' => '9999911', 'nome' => 'Sem Comunidade',
            'cpf' => '52998224725', 'comunidade' => 'Nao Cadastrada',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $b = CisternaBeneficiario::where('legacy_id', 102)->first();
        $this->assertNotNull($b);
        $this->assertNull($b->comunidade_id);

        // O beneficiario entra mesmo assim: perder o cadastro por causa da
        // comunidade seria pior que a FK nula.
        $log = DB::table('cisterna_etl_log')->where('legacy_id', 102)->get();
        $this->assertTrue($log->contains(fn ($l): bool => str_contains(strtolower((string) $l->motivo ?? ''), 'comunidade')));
    }

    public function test_resolve_ordem_de_servico_pelo_os_id_do_legado(): void
    {
        $os = CisternaOrdemServico::factory()->create(['legacy_id' => 70]);

        $this->semear(103, [
            'id' => 103, 'codmundv' => '9999911', 'nome' => 'Alocado',
            'cpf' => '52998224725', 'os_id' => '70',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertSame(
            $os->id,
            CisternaBeneficiario::where('legacy_id', 103)->value('ordem_servico_id')
        );
    }

    public function test_explode_os_cinco_respat_em_atendimentos_de_pipa(): void
    {
        $this->semear(104, [
            'id' => 104, 'codmundv' => '9999911', 'nome' => 'Com Pipa',
            'cpf' => '52998224725',
            'atendPipa' => 'sim',
            'respAtDefesaCivil' => '1',
            'respAtExercito' => '0',
            'respAtPrefeitura' => '1',
            'respAtOutros' => '1',
            'outroAtendPipa' => 'Associacao de moradores',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $b = CisternaBeneficiario::where('legacy_id', 104)->first();
        $responsaveis = $b->atendimentosPipa->pluck('responsavel')->map(fn ($r): string => $r->value)->all();

        $this->assertContains('defesa_civil', $responsaveis);
        $this->assertContains('prefeitura', $responsaveis);
        $this->assertContains('outros', $responsaveis);
        $this->assertNotContains('exercito', $responsaveis);

        $this->assertSame(
            'Associacao de moradores',
            $b->atendimentosPipa->firstWhere('responsavel.value', 'outros')->descricao
        );
    }

    public function test_cpf_invalido_vira_erro_sem_derrubar_a_carga(): void
    {
        $this->semear(105, [
            'id' => 105, 'codmundv' => '9999911', 'nome' => 'CPF Ruim', 'cpf' => '123',
        ]);
        $this->semear(106, [
            'id' => 106, 'codmundv' => '9999911', 'nome' => 'CPF Bom', 'cpf' => '52998224725',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertNull(CisternaBeneficiario::where('legacy_id', 105)->first());
        $this->assertNotNull(CisternaBeneficiario::where('legacy_id', 106)->first());

        $this->assertSame(
            'error',
            DB::table('cisterna_etl_log')->where('legacy_id', 105)->value('acao')
        );
    }

    public function test_cpf_duplicado_no_legado_registra_erro_na_segunda_linha(): void
    {
        $this->semear(107, [
            'id' => 107, 'codmundv' => '9999911', 'nome' => 'Primeiro', 'cpf' => '52998224725',
        ]);
        $this->semear(108, [
            'id' => 108, 'codmundv' => '9999911', 'nome' => 'Duplicado', 'cpf' => '529.982.247-25',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertSame(1, CisternaBeneficiario::whereIn('legacy_id', [107, 108])->count());

        $erros = DB::table('cisterna_etl_log')
            ->whereIn('legacy_id', [107, 108])
            ->where('acao', 'error')
            ->count();

        $this->assertSame(1, $erros);
    }

    public function test_refino_de_beneficiario_e_idempotente(): void
    {
        $this->semear(109, [
            'id' => 109, 'codmundv' => '9999911', 'nome' => 'Idempotente', 'cpf' => '52998224725',
            'respAtDefesaCivil' => '1', 'respAtPrefeitura' => '1',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);
        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertSame(1, CisternaBeneficiario::where('legacy_id', 109)->count());

        // Atendimentos sao substituidos, nao acumulados: a segunda passada
        // continua com dois, nao quatro.
        $this->assertSame(
            2,
            CisternaBeneficiario::where('legacy_id', 109)->first()->atendimentosPipa()->count()
        );

        // Nenhum segundo `inserted` no log.
        $this->assertSame(
            1,
            DB::table('cisterna_etl_log')
                ->where('legacy_id', 109)
                ->where('legacy_table', 'sinc_cisterna')
                ->where('acao', 'inserted')
                ->count()
        );
    }

    public function test_municipio_sem_correspondencia_vira_erro(): void
    {
        $this->semear(110, [
            'id' => 110, 'codmundv' => '0000000', 'nome' => 'Orfao', 'cpf' => '52998224725',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertNull(CisternaBeneficiario::where('legacy_id', 110)->first());
        $this->assertSame(
            'error',
            DB::table('cisterna_etl_log')->where('legacy_id', 110)->value('acao')
        );
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function semear(int $legacyId, array $doc): void
    {
        DB::table('cisterna_legado_raw')->insert([
            'legacy_table' => 'sinc_cisterna',
            'legacy_id' => $legacyId,
            'doc' => json_encode($doc, JSON_UNESCAPED_UNICODE),
            'extraido_em' => now(),
        ]);
    }

    /**
     * A sequence de cedec_municipio esta dessincronizada no Postgres de dev: as
     * 854 linhas vieram do import do legado com id explicito, entao um insert
     * sem id estoura cedec_municipio_pkey. Derivar de max(id) contorna.
     */
    private function inserirCedec(string $nome, string $codmundv, int $atCisterna): int
    {
        $id = ((int) DB::table('cedec_municipio')->max('id')) + 1;

        DB::table('cedec_municipio')->insert([
            'id' => $id,
            'nome' => $nome,
            'Codmundv' => $codmundv,
            'at_cisterna' => $atCisterna,
        ]);

        return $id;
    }
}
```

- [ ] **Step 3: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=RefinarBeneficiariosTest`
Expected: FAIL — recurso `beneficiarios` nao reconhecido no `--only`.

- [ ] **Step 4: Escrever `RefinaBeneficiarios`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\PonteMunicipio;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Refino de sinc_cisterna: 54 colunas varchar(150) para tipos reais.
 */
class RefinaBeneficiarios implements Refinador
{
    public function __construct(
        private readonly PonteMunicipio $ponte,
    ) {}

    public function recurso(): string
    {
        return 'beneficiarios';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $cpf = NormalizaEntrada::cpf($doc['cpf'] ?? null);

        if ($cpf === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'CPF ausente ou sem 11 digitos: '.($doc['cpf'] ?? 'null'), $doc);

            return;
        }

        $municipioId = $this->ponte->resolver($doc['codmundv'] ?? null)
            ?? $this->ponte->resolverPorNome($doc['municipio'] ?? null);

        if ($municipioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Municipio sem correspondencia IBGE.', $doc);

            return;
        }

        $comunidadeId = $this->resolverComunidade($doc, $municipioId, $legacyId);
        $atributos = $this->mapear($doc, $legacyId, $cpf, $municipioId, $comunidadeId);

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria beneficiario CPF {$cpf}.");

            return;
        }

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId, $cpf): void {
                $existente = CisternaBeneficiario::withTrashed()->where('legacy_id', $legacyId)->first();

                // CPF ja usado por outro registro: o legado nao tinha UNIQUE,
                // garantia era um count() em PHP antes do insert.
                $conflito = CisternaBeneficiario::withTrashed()
                    ->where('cpf', $cpf)
                    ->when($existente !== null, fn ($q) => $q->whereKeyNot($existente->id))
                    ->first();

                if ($conflito !== null) {
                    // Decisao D25. Os 26 CPFs que colidem entre registros
                    // ativos tem DUAS naturezas, e tratar as duas igual seria
                    // errado (notas 5.1):
                    //
                    //  A) 22 casos: mesma pessoa, cadastro em duplicidade.
                    //     Nome quase identico. Marca como duplicado, que e a
                    //     convencao que o legado ja usava.
                    //
                    //  B) 4 casos: CPF digitado errado, apontando para pessoas
                    //     DIFERENTES. Ex.: 05924079659 esta em "DOUGLAS SOARES
                    //     BARBOSA" e em "ISABEL ALVES SEPO". Marcar a segunda
                    //     como duplicata apagaria uma beneficiaria real da
                    //     lista ativa.
                    //
                    // O separador e a similaridade dos nomes normalizados.
                    if (! $this->pareceMesmaPessoa($conflito->nome, $atributos['nome'])) {
                        RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                            "CPF {$cpf} ja usado por #{$conflito->id} (\"{$conflito->nome}\"), mas este "
                            ."registro e de \"{$atributos['nome']}\": nomes divergentes, provavel erro de "
                            .'digitacao de CPF. NAO importado — corrigir o CPF na origem e reprocessar.', $doc);

                        return;
                    }

                    $atributos['situacao_analise'] = SituacaoAnalise::DUPLICADO->value;
                    $atributos['situacao_analise_obs'] = "CPF coincide com o registro #{$conflito->id} "
                        ."(legacy_id {$conflito->legacy_id}). Marcado automaticamente na migracao.";

                    $criado = CisternaBeneficiario::create($atributos);
                    $this->sincronizarAtendimentos($criado, $doc);

                    RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                        "CPF {$cpf} colide com #{$conflito->id}: importado como Duplicado.", $criado->id);

                    return;
                }

                if ($existente !== null) {
                    $existente->update($atributos);
                    $this->sincronizarAtendimentos($existente, $doc);
                    RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

                    return;
                }

                $criado = CisternaBeneficiario::create($atributos);
                $this->sincronizarAtendimentos($criado, $doc);
                RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criado->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar: '.$e->getMessage(), $doc);
        }
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array<string, mixed>
     */
    private function mapear(array $doc, int $legacyId, string $cpf, int $municipioId, ?int $comunidadeId): array
    {
        return [
            'cpf' => $cpf,
            'nome' => $this->texto($doc['nome'] ?? null, 150) ?? 'Beneficiario '.$legacyId,
            'telefone' => $this->texto($doc['tel'] ?? null, 15),
            'data_nascimento' => $this->data($doc['dtNasc'] ?? null),
            'cadastro_unico' => $this->texto($doc['cadUnico'] ?? null, 12),

            'municipio_id' => $municipioId,
            'comunidade_id' => $comunidadeId,
            'endereco' => $this->texto($doc['endereco'] ?? null, 150),
            'latitude' => NormalizaEntrada::decimal($doc['latitude'] ?? null),
            'longitude' => NormalizaEntrada::decimal($doc['longitude'] ?? null),
            'ordem_servico_id' => $this->resolverOrdemServico($doc),

            // Os dois eixos, ortogonais.
            'situacao_analise' => SituacaoAnalise::doLegado($doc['aprovado'] ?? null)->value,
            'situacao_analise_obs' => $this->texto($doc['aprovado_obs'] ?? null, 255),
            'situacao_obra' => SituacaoObra::doLegado($doc['estado'] ?? null)->value,
            'ranqueamento_ordem' => $this->inteiro($doc['ranqueamento_ordem'] ?? null),

            'qtd_pessoas' => $this->inteiro($doc['qtdPessoa'] ?? null),
            'renda' => NormalizaEntrada::moeda($doc['renda'] ?? null),
            'renda_per_capita' => NormalizaEntrada::moeda($doc['rendaPerCapita'] ?? null),

            'possui_deficiencia' => NormalizaEntrada::booleanoSimNao($doc['possui_deficiencia'] ?? null),
            'possui_crianca' => NormalizaEntrada::booleanoSimNao($doc['possui_crianca'] ?? null),
            'data_nascimento_crianca' => $this->data($doc['dtNasc_crianca'] ?? null),
            'possui_idoso' => NormalizaEntrada::booleanoSimNao($doc['possui_idoso'] ?? null),
            'chefiada_mulher' => NormalizaEntrada::booleanoSimNao($doc['chefiada_mulher'] ?? null),

            'tipo_moradia' => $this->texto($doc['moradia'] ?? null, 30),
            'tipo_moradia_outro' => $this->texto($doc['outroMoradia'] ?? null, 50),
            'comprimento_telhado' => NormalizaEntrada::decimal($doc['compTelhado'] ?? null),
            'largura_telhado' => NormalizaEntrada::decimal($doc['larguracompTelhado'] ?? null),
            'area_telhado' => NormalizaEntrada::decimal($doc['areaTotalTelhado'] ?? null),
            'comprimento_testada' => NormalizaEntrada::decimal($doc['compTestada'] ?? null),
            'num_caidas_telhado' => $this->inteiro($doc['numCaidaTelhado'] ?? null),
            'cobertura_telhado' => $this->texto($doc['coberturaTelhado'] ?? null, 30),
            'cobertura_outro' => $this->texto($doc['coberturaOutros'] ?? null, 150),
            'possui_fogao_lenha' => NormalizaEntrada::booleanoSimNao($doc['existeFogaoLenha'] ?? null),
            'medida_telhado_area_fogao' => NormalizaEntrada::decimal($doc['medidaTelhadoAreaFogao'] ?? null),
            'testada_disp_parte_fogao' => NormalizaEntrada::decimal($doc['testadaDispParteFogao'] ?? null),
            'atendido_por_pipa' => NormalizaEntrada::booleanoSimNao($doc['atendPipa'] ?? null),

            'agente_nome' => $this->texto($doc['nomeAgente'] ?? null, 70),
            'agente_cpf' => NormalizaEntrada::cpf($doc['cpfAgente'] ?? null),
            'engenheiro_nome' => $this->texto($doc['nomeEng'] ?? null, 150),
            'engenheiro_crea' => $this->texto($doc['creaEng'] ?? null, 20),

            // Legado: outrObs. `obs1` em algumas versoes do schema.
            'observacoes' => $this->texto($doc['outrObs'] ?? $doc['obs1'] ?? null, 1000),

            'legacy_id' => $legacyId,
        ];
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function resolverComunidade(array $doc, int $municipioId, int $legacyId): ?int
    {
        $nome = trim((string) ($doc['comunidade'] ?? ''));

        if ($nome === '') {
            return null;
        }

        // Par (municipio, nome): e o que corrige o defeito C18 do legado, que
        // casava comunidade so pelo nome.
        $id = CisternaComunidade::where('municipio_id', $municipioId)
            ->where('nome', $nome)
            ->value('id');

        if ($id === null) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Comunidade \"{$nome}\" nao encontrada no municipio {$municipioId}: FK deixada nula.");

            return null;
        }

        return (int) $id;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function resolverOrdemServico(array $doc): ?int
    {
        $osLegacyId = $doc['os_id'] ?? null;

        if ($osLegacyId === null || $osLegacyId === '' || (int) $osLegacyId === 0) {
            return null;
        }

        $id = CisternaOrdemServico::where('legacy_id', (int) $osLegacyId)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Explode as cinco colunas respAt* em linhas. Substitui, nao acumula.
     *
     * @param  array<string, mixed>  $doc
     */
    private function sincronizarAtendimentos(CisternaBeneficiario $beneficiario, array $doc): void
    {
        $beneficiario->atendimentosPipa()->delete();

        foreach (ResponsavelPipa::cases() as $responsavel) {
            $marcado = NormalizaEntrada::booleanoSimNao($doc[$responsavel->colunaLegado()] ?? null);

            if ($marcado !== true) {
                continue;
            }

            $beneficiario->atendimentosPipa()->create([
                'responsavel' => $responsavel->value,
                'descricao' => $responsavel === ResponsavelPipa::OUTROS
                    ? $this->texto($doc['outroAtendPipa'] ?? null, 255)
                    : null,
            ]);
        }
    }

    /**
     * Dois nomes designam a mesma pessoa? Usado para separar duplicidade de
     * cadastro (nome quase igual) de erro de digitacao de CPF (nomes de
     * pessoas diferentes) — decisao D25.
     *
     * Limiar de 80% calibrado sobre os 26 casos reais de producao: separa os
     * 22 de duplicidade dos 4 de CPF errado. E heuristica, nao verdade — os
     * casos limitrofes vao para revisao da area (notas 5.1).
     */
    private function pareceMesmaPessoa(?string $a, ?string $b): bool
    {
        $normalizar = static function (?string $nome): string {
            $texto = trim((string) ($nome ?? ''));
            $semAcento = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
            $texto = strtoupper($semAcento === false ? $texto : $semAcento);

            // Sobra so letra e espaco simples: acento, pontuacao e espaco
            // duplo nao devem contar como diferenca de pessoa.
            return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z ]/', ' ', $texto) ?? '') ?? '');
        };

        $primeiro = $normalizar($a);
        $segundo = $normalizar($b);

        if ($primeiro === '' || $segundo === '') {
            // Sem nome para comparar, nao afirma que sao a mesma pessoa.
            return false;
        }

        if ($primeiro === $segundo) {
            return true;
        }

        similar_text($primeiro, $segundo, $percentual);

        return $percentual >= 80.0;
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', (string) $valor) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    /**
     * O legado guardava data em varchar(150): ha '0000-00-00', formato
     * brasileiro e string vazia.
     */
    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 5: Registrar o refinador no comando**

Em `RefinarCisternaLegadoCommand::todosOsRefinadores()`, acrescentar **depois** de `os` — a ordem do array e a ordem de execucao, e o beneficiario depende de comunidade e de OS:

```php
            'beneficiarios' => app(RefinaBeneficiarios::class),
```

Com o import correspondente.

- [ ] **Step 6: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=RefinarBeneficiariosTest`
Expected: PASS, 9 testes.

- [ ] **Step 7: Criar os dois enums com os valores medidos**

Usar os valores registrados no Step 9 da Task 15. Molde para `TipoMoradia` — **substituir os casos pelos valores reais medidos**, mantendo a estrutura:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Tipo de moradia do beneficiario. Casos derivados do SELECT DISTINCT sobre
 * cisterna_legado_raw.doc->>'moradia' — ver
 * docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md.
 *
 * doLegado() absorve as variacoes de grafia do texto livre do legado.
 */
enum TipoMoradia: string
{
    case ALVENARIA = 'alvenaria';
    case MADEIRA = 'madeira';
    case MISTA = 'mista';
    case OUTRO = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::ALVENARIA => 'Alvenaria',
            self::MADEIRA => 'Madeira',
            self::MISTA => 'Mista',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Texto livre do legado -> case. Null quando nao reconhece: o refino
     * registra skipped com o valor original, sem perder o dado (ele continua
     * no doc jsonb).
     */
    public static function doLegado(?string $valor): ?self
    {
        $normalizado = self::normalizar($valor);

        if ($normalizado === null) {
            return null;
        }

        return match (true) {
            str_contains($normalizado, 'alvenaria') => self::ALVENARIA,
            str_contains($normalizado, 'madeira') => self::MADEIRA,
            str_contains($normalizado, 'mista') || str_contains($normalizado, 'misto') => self::MISTA,
            default => null,
        };
    }

    private static function normalizar(?string $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '') {
            return null;
        }

        // Remove acento para o match nao depender de grafia.
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);

        return mb_strtolower($semAcento === false ? $texto : $semAcento);
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
```

`CoberturaTelhado` segue exatamente a mesma forma, com os oito casos medidos: `pvc`, `ceramica`, `fibrocimento`, `zinco`, `concreto`, `metalica`, `amianto`, `outros`. O `doLegado()` dela e mais simples, porque os valores do legado ja vem quase todos em minuscula e sem acento:

```php
    public static function doLegado(?string $valor): ?self
    {
        $normalizado = self::normalizar($valor);

        if ($normalizado === null || $normalizado === '0') {
            return null;
        }

        return self::tryFrom($normalizado);
    }
```

Com `normalizar()` identico ao de `TipoMoradia` (trim, remocao de acento, minuscula). Isso resolve `Concreto` -> `concreto` e `Outros` -> `outros` sem `match`.

- [ ] **Step 8: Migration das CHECK constraints**

`database/migrations/2026_08_10_130000_add_checks_moradia_cobertura_cisterna.php`:

```php
<?php

declare(strict_types=1);

use App\Modules\Cisterna\Enums\CoberturaTelhado;
use App\Modules\Cisterna\Enums\TipoMoradia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CHECK de tipo_moradia e cobertura_telhado.
 *
 * Separada da migration do dominio de proposito: os valores validos so foram
 * conhecidos depois da extracao do legado, quando o SELECT DISTINCT sobre
 * cisterna_legado_raw.doc revelou o que existe em producao (spec secao 4.3).
 * Empilhar aqui e correto — nao e correcao de erro, e informacao que nao
 * existia antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Normaliza o que o refino gravou como texto livre antes de travar.
        DB::table('cisterna_beneficiarios')
            ->whereNotNull('tipo_moradia')
            ->whereNotIn('tipo_moradia', TipoMoradia::valores())
            ->update(['tipo_moradia' => null]);

        DB::table('cisterna_beneficiarios')
            ->whereNotNull('cobertura_telhado')
            ->whereNotIn('cobertura_telhado', CoberturaTelhado::valores())
            ->update(['cobertura_telhado' => null]);

        $this->adicionarCheck('tipo_moradia', TipoMoradia::valores());
        $this->adicionarCheck('cobertura_telhado', CoberturaTelhado::valores());
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cisterna_beneficiarios DROP CONSTRAINT IF EXISTS cisterna_beneficiarios_tipo_moradia_check');
        DB::statement('ALTER TABLE cisterna_beneficiarios DROP CONSTRAINT IF EXISTS cisterna_beneficiarios_cobertura_telhado_check');
    }

    /**
     * @param  array<int, string>  $valores
     */
    private function adicionarCheck(string $coluna, array $valores): void
    {
        $lista = implode(', ', array_map(fn (string $v): string => "'{$v}'", $valores));

        DB::statement(
            "ALTER TABLE cisterna_beneficiarios ADD CONSTRAINT cisterna_beneficiarios_{$coluna}_check "
            ."CHECK ({$coluna} IS NULL OR {$coluna} IN ({$lista}))"
        );
    }
};
```

- [ ] **Step 9: Aplicar os enums no refino e nos Requests**

No `RefinaBeneficiarios::mapear()`, trocar as duas linhas de texto livre por:

```php
            'tipo_moradia' => TipoMoradia::doLegado($doc['moradia'] ?? null)?->value,
            'cobertura_telhado' => CoberturaTelhado::doLegado($doc['coberturaTelhado'] ?? null)?->value,
```

Em `StoreBeneficiarioRequest::rules()`, trocar as duas regras de `string, max:30` por:

```php
            'tipo_moradia' => ['required', Rule::in(TipoMoradia::valores())],
            'cobertura_telhado' => ['required', Rule::in(CoberturaTelhado::valores())],
```

E acrescentar os dois em `BeneficiarioController::opcoes()`:

```php
            'tipos_moradia' => TipoMoradia::options(),
            'coberturas_telhado' => CoberturaTelhado::options(),
```

Ajustar as factories e os payloads dos testes que usavam `'alvenaria'` e `'telha ceramica'` como texto livre, se os valores medidos forem outros.

- [ ] **Step 10: Rodar a suite do modulo inteira**

Run: `$PHP artisan migrate && scripts/test-host.sh --filter=Cisterna`
Expected: PASS em tudo.

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Cisterna/Enums/TipoMoradia.php \
        app/Modules/Cisterna/Enums/CoberturaTelhado.php \
        database/migrations/2026_08_10_130000_add_checks_moradia_cobertura_cisterna.php \
        app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaBeneficiarios.php \
        app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php \
        app/Modules/Cisterna/Requests/StoreBeneficiarioRequest.php \
        app/Modules/Cisterna/Controllers/BeneficiarioController.php \
        database/factories/Cisterna/CisternaBeneficiarioFactory.php \
        tests/Feature/Cisterna/RefinarBeneficiariosTest.php
git commit -m "✨ feat(cisterna): refino dos beneficiarios e enums de moradia e cobertura"
```

---

### Task 18: Refino das vistorias, itens conferidos, notificacoes e midia

Fecha o ETL. As tres tabelas de relatorio do legado viram linhas de `cisterna_vistorias` com `etapa` distinta, e as ~87 colunas de checklist explodem em `cisterna_itens_conferidos`.

**Files:**
- Create: `app/Modules/Cisterna/Domain/Etl/MapaItensLegado.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaVistoriaFornecedor.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaVistoriaCompdec.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaVistoriaCedec.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaNotificacoes.php`
- Create: `app/Modules/Cisterna/Domain/Etl/Refinadores/RefinaMidia.php`
- Modify: `app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php`
- Test: `tests/Feature/Cisterna/RefinarVistoriasTest.php`

**Interfaces:**
- Consumes: tudo das Tasks 15 a 17, `NumeracaoInstalacaoService` (Task 9)
- Produces:
  - `MapaItensLegado::paraEtapa(EtapaVistoria, array $doc): array<int, array{item:string, conferido:bool, quantidade:?float, unidade:?string, detalhes:?array}>`
  - os cinco refinadores
  - recursos `vistorias`, `notificacoes` e `midia` no `--only`

- [ ] **Step 1: Escrever o teste que falha**

`tests/Feature/Cisterna/RefinarVistoriasTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\NumeracaoInstalacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefinarVistoriasTest extends TestCase
{
    use DatabaseTransactions;

    private CisternaBeneficiario $beneficiario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->beneficiario = CisternaBeneficiario::factory()->create([
            'legacy_id' => 500,
            'situacao_obra' => SituacaoObra::ENVIO_INSTALACAO->value,
        ]);
    }

    public function test_relatorio_do_fornecedor_vira_vistoria_da_etapa_fornecedor(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 10, [
            'id' => 10,
            'cisterna_id' => 500,
            'num_instalacao' => '1234',
            'nome_eng_relatorio' => 'Eng Fornecedor',
            'crea_mg_eng' => 'MG-111',
            'data_relatorio' => '2025-08-01',
            'bairro' => 'Zona Rural',
            'endereco' => 'Sitio do Legado',
            'latitude' => '-19,91',
            'longitude' => '-43,94',
            'obs_instal_relatorio' => 'Instalado sem intercorrencia',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $v = CisternaVistoria::where('etapa', EtapaVistoria::FORNECEDOR->value)
            ->where('legacy_id', 10)->first();

        $this->assertNotNull($v);
        $this->assertSame($this->beneficiario->id, $v->beneficiario_id);
        $this->assertSame(1234, $v->numero_instalacao);
        $this->assertSame('Eng Fornecedor', $v->engenheiro_nome);
        $this->assertSame('2025-08-01', $v->data_relatorio->toDateString());
        $this->assertSame('Zona Rural', $v->bairro);
        // crea_mg_eng preenchido era o marcador de conclusao no legado.
        $this->assertTrue($v->estaConcluida());
    }

    public function test_importar_vistoria_do_fornecedor_marca_a_obra_como_instalada(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 11, [
            'id' => 11, 'cisterna_id' => 500, 'num_instalacao' => '1235',
            'crea_mg_eng' => 'MG-111',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $this->assertSame(SituacaoObra::INSTALADO, $this->beneficiario->fresh()->situacao_obra);
    }

    public function test_itens_do_fornecedor_explodem_em_linhas(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 12, [
            'id' => 12, 'cisterna_id' => 500, 'num_instalacao' => '1236',
            'crea_mg_eng' => 'MG-111',
            // Padrao {item}_opcao do fornecedor.
            'cisterna_opcao' => 'sim',
            'bomba_opcao' => 'nao',
            'calha_opcao' => 'sim',
            'qtd_calha' => '12,5',
            'tubulacao_opcao' => 'sim',
            'qtd_tubulacao' => '30',
            'te_90_pbv_qtd' => '4',
            'cap_pvc_qtd' => '2',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $v = CisternaVistoria::where('legacy_id', 12)
            ->where('etapa', EtapaVistoria::FORNECEDOR->value)->first();

        $logo = $v->itemDe(ItemInstalacao::CISTERNA_LOGO);
        $this->assertTrue($logo->conferido);

        $bomba = $v->itemDe(ItemInstalacao::BOMBA);
        $this->assertFalse($bomba->conferido);

        $calha = $v->itemDe(ItemInstalacao::CALHA);
        $this->assertSame('12.50', $calha->quantidade);
        $this->assertSame(UnidadeItem::M, $calha->unidade);

        $tubulacao = $v->itemDe(ItemInstalacao::TUBULACAO);
        $this->assertSame('30.00', $tubulacao->quantidade);

        $tePvc = $v->itemDe(ItemInstalacao::TE_PVC);
        $this->assertSame('4.00', $tePvc->quantidade);
        $this->assertSame(UnidadeItem::UN, $tePvc->unidade);
    }

    public function test_pecas_de_pvc_do_fornecedor_entram_mesmo_sem_coluna_booleana(): void
    {
        // A tabela do fornecedor NAO tem te_pvc_opcao, joelho_pvc_opcao,
        // luva_pvc_opcao nem cap_pvc_opcao: essas quatro pecas so tem coluna
        // de quantidade. Verificado em producao: 827 das 856 linhas as tem
        // preenchidas. Sem o fallback por quantidade no MapaItensLegado, a
        // carga perderia 3.308 registros de item silenciosamente.
        $this->semear('sinc_cisterna_rel_fornecedor', 13, [
            'id' => 13, 'cisterna_id' => 500, 'num_instalacao' => '1237',
            'crea_mg_eng' => 'MG-111',
            'te_90_pbv_qtd' => '6',
            'joelho_90_pbv_qtd' => '8',
            'luva_pvc_qtd' => '2',
            // cap ficou em zero: conferido deve ser false, nao ausente.
            'cap_pvc_qtd' => '0',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $v = CisternaVistoria::where('legacy_id', 13)
            ->where('etapa', EtapaVistoria::FORNECEDOR->value)->first();

        foreach ([ItemInstalacao::TE_PVC, ItemInstalacao::JOELHO_PVC, ItemInstalacao::LUVA_PVC] as $item) {
            $linha = $v->itemDe($item);
            $this->assertNotNull($linha, "Item {$item->value} foi descartado por falta de booleano.");
            $this->assertTrue($linha->conferido);
            $this->assertSame(UnidadeItem::UN, $linha->unidade);
        }

        $cap = $v->itemDe(ItemInstalacao::CAP_PVC);
        $this->assertNotNull($cap);
        $this->assertFalse($cap->conferido, 'Quantidade zero deve gravar conferido = false.');
    }

    public function test_conferencia_do_compdec_liga_pela_instalacao_id_do_fornecedor(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 20, [
            'id' => 20, 'cisterna_id' => 500, 'num_instalacao' => '2000',
            'crea_mg_eng' => 'MG-111',
        ]);
        $this->semear('sinc_cisterna_rel_compdec', 30, [
            'id' => 30,
            // Aponta para sinc_cisterna_rel_fornecedor.id, nao para a cisterna.
            'instalacao_id' => 20,
            'crea_mg' => 'MG-222',
            'data_relatorio' => '2025-09-10',
            'local_relatorio' => 'Belo Horizonte',
            'calha' => '1',
            'calha_metros' => '11',
            'fixacao' => '1',
            'fix_abracadeira' => '12',
            'fix_bucha' => '12',
            'fix_parafuso' => '24',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $v = CisternaVistoria::where('etapa', EtapaVistoria::COMPDEC->value)
            ->where('legacy_id', 30)->first();

        $this->assertNotNull($v);
        $this->assertSame($this->beneficiario->id, $v->beneficiario_id);
        // Somente a etapa do fornecedor tem numero de instalacao.
        $this->assertNull($v->numero_instalacao);
        $this->assertSame('MG-222', $v->engenheiro_crea);
        $this->assertTrue($v->estaConcluida());

        // As tres subquantidades de fixacao vao para detalhes jsonb: no
        // legado eram fix_abracadeira, fix_bucha e fix_parafuso soltas.
        $fixacao = $v->itemDe(ItemInstalacao::FIXACAO);
        $this->assertSame('12', $fixacao->detalhes['abracadeira']);
        $this->assertSame('24', $fixacao->detalhes['parafuso']);

        $calha = $v->itemDe(ItemInstalacao::CALHA);
        $this->assertSame('11.00', $calha->quantidade);
    }

    public function test_compdec_sem_fornecedor_correspondente_vira_erro(): void
    {
        $this->semear('sinc_cisterna_rel_compdec', 31, [
            'id' => 31, 'instalacao_id' => 9999, 'crea_mg' => 'MG-222',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $this->assertNull(
            CisternaVistoria::where('etapa', EtapaVistoria::COMPDEC->value)->where('legacy_id', 31)->first()
        );
        $this->assertSame(
            'error',
            DB::table('cisterna_etl_log')->where('legacy_id', 31)
                ->where('legacy_table', 'sinc_cisterna_rel_compdec')->value('acao')
        );
    }

    public function test_compdec_vazio_criado_como_efeito_colateral_do_legado_e_ignorado(): void
    {
        // O legado criava RelatorioInstalacaoCompdec::create(['instalacao_id'])
        // vazio junto com o store do fornecedor. Linha sem nenhum dado nao
        // deve gerar vistoria.
        $this->semear('sinc_cisterna_rel_fornecedor', 21, [
            'id' => 21, 'cisterna_id' => 500, 'num_instalacao' => '2100', 'crea_mg_eng' => 'MG-111',
        ]);
        $this->semear('sinc_cisterna_rel_compdec', 32, [
            'id' => 32, 'instalacao_id' => 21, 'crea_mg' => null, 'data_relatorio' => null,
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $this->assertNull(
            CisternaVistoria::where('etapa', EtapaVistoria::COMPDEC->value)->where('legacy_id', 32)->first()
        );
        $this->assertSame(
            'skipped',
            DB::table('cisterna_etl_log')->where('legacy_id', 32)
                ->where('legacy_table', 'sinc_cisterna_rel_compdec')->value('acao')
        );
    }

    public function test_fiscalizacao_da_cedec_traz_os_dados_administrativos(): void
    {
        $this->semear('sinc_cisterna_rel_cedec', 40, [
            'id' => 40,
            // A tabela do CEDEC aponta direto para a cisterna.
            'cisterna_id' => 500,
            'processo_sei' => 'SEI-1080.01',
            'contrato' => '0042/2025',
            'empenho' => '900123',
            'placa_obras' => '1',
            'crea_mg' => 'MG-333',
            'art' => 'ART-4455',
            'data_relatorio' => '2025-10-20',
            'local_relatorio' => 'Belo Horizonte',
            'sucao' => '1',
            'bomba' => '0',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $v = CisternaVistoria::where('etapa', EtapaVistoria::CEDEC->value)
            ->where('legacy_id', 40)->first();

        $this->assertNotNull($v);
        $this->assertSame('SEI-1080.01', $v->processo_sei);
        $this->assertSame('0042/2025', $v->contrato);
        $this->assertSame('900123', $v->empenho);
        $this->assertSame(1, $v->placa_obras);
        $this->assertSame('ART-4455', $v->engenheiro_art);
        $this->assertTrue($v->itemDe(ItemInstalacao::SUCAO)->conferido);
        $this->assertFalse($v->itemDe(ItemInstalacao::BOMBA)->conferido);
    }

    public function test_numeros_de_instalacao_iguais_no_legado_geram_erro_na_segunda(): void
    {
        $outro = CisternaBeneficiario::factory()->create(['legacy_id' => 501]);

        $this->semear('sinc_cisterna_rel_fornecedor', 50, [
            'id' => 50, 'cisterna_id' => 500, 'num_instalacao' => '7777', 'crea_mg_eng' => 'MG-1',
        ]);
        $this->semear('sinc_cisterna_rel_fornecedor', 51, [
            'id' => 51, 'cisterna_id' => 501, 'num_instalacao' => '7777', 'crea_mg_eng' => 'MG-2',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $this->assertSame(1, CisternaVistoria::where('numero_instalacao', 7777)->count());
        $this->assertSame(
            1,
            DB::table('cisterna_etl_log')
                ->whereIn('legacy_id', [50, 51])
                ->where('legacy_table', 'sinc_cisterna_rel_fornecedor')
                ->where('acao', 'error')
                ->count()
        );
    }

    public function test_sequence_e_sincronizada_com_o_maior_numero_importado(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 60, [
            'id' => 60, 'cisterna_id' => 500, 'num_instalacao' => '54321', 'crea_mg_eng' => 'MG-1',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        // Sem isso a sequence comecaria em 1 e colidiria com tudo o que veio
        // do legado.
        $this->assertGreaterThan(54321, app(NumeracaoInstalacaoService::class)->proximoNumero());
    }

    public function test_notificacoes_viram_registros_polimorficos_no_beneficiario(): void
    {
        $this->semear('sinc_cisterna_notificacoes', 80, [
            'id' => 80,
            'cisterna_id' => 500,
            'obs' => 'Pendencia de documentacao',
            'respondida' => '1',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'notificacoes'])->assertExitCode(0);

        $n = CisternaNotificacao::where('legacy_id', 80)->first();

        $this->assertNotNull($n);
        $this->assertSame(CisternaBeneficiario::class, $n->notificavel_type);
        $this->assertSame($this->beneficiario->id, $n->notificavel_id);
        $this->assertTrue($n->respondida);
        $this->assertSame('Pendencia de documentacao', $n->observacao);
    }

    public function test_refino_de_vistorias_e_idempotente(): void
    {
        $this->semear('sinc_cisterna_rel_fornecedor', 90, [
            'id' => 90, 'cisterna_id' => 500, 'num_instalacao' => '9090',
            'crea_mg_eng' => 'MG-1', 'calha_opcao' => 'sim', 'qtd_calha' => '10',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);
        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        $this->assertSame(
            1,
            CisternaVistoria::where('legacy_id', 90)
                ->where('etapa', EtapaVistoria::FORNECEDOR->value)->count()
        );

        // Itens sao substituidos, nao acumulados.
        $v = CisternaVistoria::where('legacy_id', 90)
            ->where('etapa', EtapaVistoria::FORNECEDOR->value)->first();
        $this->assertSame(
            1,
            $v->itensConferidos()->where('item', ItemInstalacao::CALHA->value)->count()
        );
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function semear(string $tabela, int $legacyId, array $doc): void
    {
        DB::table('cisterna_legado_raw')->insert([
            'legacy_table' => $tabela,
            'legacy_id' => $legacyId,
            'doc' => json_encode($doc, JSON_UNESCAPED_UNICODE),
            'extraido_em' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: `scripts/test-host.sh --filter=RefinarVistoriasTest`
Expected: FAIL — recurso `vistorias` nao reconhecido no `--only`.

- [ ] **Step 2b: Escrever `DeduplicaVistorias` — obrigatorio antes dos refinadores**

A analise de producao (spec 4.6.6) mostrou que **65 relatorios de fornecedor e 17 de CEDEC sao double-submit do mesmo formulario**: mesma data, mesmo `num_instalacao`, `DATEDIFF` de 0 dia. Nao sao reinstalacoes. Sem deduplicar, o `UNIQUE (beneficiario_id, etapa)` rejeita todos.

A chave nao pode ser `(cisterna_id, num_instalacao, data)`: nas copias, uma linha costuma ter `num_instalacao` ou `data_relatorio` nulo e a outra preenchido, e `NULL <> NULL` faz a dedup falhar. A chave e **`cisterna_id` sozinho**, mantendo a linha mais completa.

`app/Modules/Cisterna/Domain/Etl/DeduplicaVistorias.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Escolhe uma linha por beneficiario nas tabelas de relatorio do legado.
 *
 * Producao tem 856 relatorios de fornecedor para 791 beneficiarios e 675 de
 * CEDEC para 658 — o excedente e reenvio do mesmo formulario, que o legado
 * nao prevenia (spec 4.6.6).
 *
 * Critério: a linha com mais campos preenchidos vence; `id` maior desempata,
 * por ser a submissao mais recente.
 */
final class DeduplicaVistorias
{
    /**
     * legacy_ids que devem ser refinados, por tabela.
     *
     * @return array<int, int>
     */
    public function vencedores(string $legacyTable, string $colunaBeneficiario): array
    {
        $linhas = DB::table('cisterna_legado_raw')
            ->where('legacy_table', $legacyTable)
            ->get(['legacy_id', 'doc']);

        $porBeneficiario = [];

        foreach ($linhas as $linha) {
            $doc = json_decode((string) $linha->doc, true);

            if (! is_array($doc)) {
                continue;
            }

            $beneficiario = (int) ($doc[$colunaBeneficiario] ?? 0);

            if ($beneficiario === 0) {
                // Sem beneficiario: deixa passar para o refinador registrar o erro.
                $porBeneficiario['orfao_'.$linha->legacy_id] = [
                    'legacy_id' => (int) $linha->legacy_id,
                    'peso' => 0,
                ];

                continue;
            }

            $peso = $this->completude($doc);
            $atual = $porBeneficiario[$beneficiario] ?? null;

            $vence = $atual === null
                || $peso > $atual['peso']
                || ($peso === $atual['peso'] && (int) $linha->legacy_id > $atual['legacy_id']);

            if ($vence) {
                $porBeneficiario[$beneficiario] = [
                    'legacy_id' => (int) $linha->legacy_id,
                    'peso' => $peso,
                ];
            }
        }

        return array_map(fn (array $v): int => $v['legacy_id'], array_values($porBeneficiario));
    }

    /**
     * Quantidade de campos com valor util. Trata '0' e '0000-00-00' como
     * vazios, porque o legado grava os dois no lugar de NULL.
     *
     * @param  array<string, mixed>  $doc
     */
    private function completude(array $doc): int
    {
        $peso = 0;

        foreach ($doc as $valor) {
            if ($valor === null) {
                continue;
            }

            $texto = trim((string) $valor);

            if ($texto === '' || $texto === '0' || str_starts_with($texto, '0000-00-00')) {
                continue;
            }

            $peso++;
        }

        return $peso;
    }
}
```

Cada refinador de vistoria recebe a lista e ignora o que nao vencer. Em `RefinaVistoriaFornecedor::refinar()`, no inicio:

```php
        if (! in_array($legacyId, $this->vencedores(), true)) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Reenvio do mesmo formulario: outra linha do mesmo beneficiario e mais completa.');

            return;
        }
```

Com a lista memoizada no refinador:

```php
    /** @var array<int, int>|null */
    private ?array $vencedores = null;

    /**
     * @return array<int, int>
     */
    private function vencedores(): array
    {
        return $this->vencedores ??= $this->dedup->vencedores($this->tabelaLegado(), 'cisterna_id');
    }
```

`RefinaVistoriaCedec` faz o mesmo, tambem com `cisterna_id`. **`RefinaVistoriaCompdec` nao deduplica**: a analise mostrou que `rel_compdec` e 1:1 perfeito com `rel_fornecedor` (858 linhas para 858 `instalacao_id` distintos) — mas as linhas cujo `instalacao_id` aponta para um fornecedor descartado na dedup precisam ser ignoradas, o que o `orfaos` do proprio refinador ja resolve.

Acrescentar `DeduplicaVistorias $dedup` ao construtor dos dois refinadores.

- [ ] **Step 2c: Marcar os municipios habilitados — sem isso o modulo sobe vazio**

O achado 4.6.9-E do spec: `cedec_municipio.at_cisterna` esta **zerado no Postgres (0 de 854)**. O scope `Municipio::habilitadosCisterna()` da Task 5 devolveria colecao vazia, e todo select de municipio das telas ficaria em branco.

O dado e derivavel: os **55 municipios** que tem beneficiario no legado sao os habilitados. Acrescentar a `RefinarCisternaLegadoCommand`:

```php
    /**
     * Marca at_cisterna nos municipios que tem beneficiario importado.
     *
     * O flag mora em cedec_municipio (a ponte oficial de municipio do legado)
     * e chegou zerado no Postgres — ver spec 4.6.9-E. Sem isso o scope
     * Municipio::habilitadosCisterna() devolve lista vazia.
     */
    private function marcarMunicipiosHabilitados(): int
    {
        // codigo_ibge dos municipios que efetivamente tem beneficiario.
        $codigos = DB::table('cisterna_beneficiarios as b')
            ->join('municipios as m', 'm.id', '=', 'b.municipio_id')
            ->whereNull('b.deleted_at')
            ->distinct()
            ->pluck('m.codigo_ibge');

        if ($codigos->isEmpty()) {
            return 0;
        }

        $marcados = DB::table('cedec_municipio')
            ->whereIn('Codmundv', $codigos)
            ->update(['at_cisterna' => 1]);

        // O scope cacheia por 24h no Redis e 300s por worker.
        Municipio::esquecerHabilitadosCisterna();

        return $marcados;
    }
```

Com os imports `use App\Models\Municipio;` e `use Illuminate\Support\Facades\DB;`.

**Teste a acrescentar** em `RefinarBeneficiariosTest`:

```php
    public function test_refino_marca_o_municipio_como_habilitado_no_programa(): void
    {
        $this->inserirCedec('Municipio Benef ETL', '9999911', 0);
        Municipio::esquecerHabilitadosCisterna();

        $this->semear(120, [
            'id' => 120, 'codmundv' => '9999911', 'nome' => 'Habilitador', 'cpf' => '52998224725',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'beneficiarios'])->assertExitCode(0);

        $this->assertSame(
            1,
            (int) DB::table('cedec_municipio')->where('Codmundv', '9999911')->value('at_cisterna')
        );

        // E o scope passa a devolver o municipio.
        Municipio::esquecerHabilitadosCisterna();
        $this->assertContains(
            $this->municipio->id,
            Municipio::idsHabilitadosCisterna()
        );
    }
```

Com `use App\Models\Municipio;` no teste.

**Teste a acrescentar** em `RefinarVistoriasTest`:

```php
    public function test_reenvio_do_mesmo_formulario_e_descartado_mantendo_o_mais_completo(): void
    {
        // Copia incompleta: sem numero e sem data.
        $this->semear('sinc_cisterna_rel_fornecedor', 200, [
            'id' => 200, 'cisterna_id' => 500, 'num_instalacao' => null,
            'data_relatorio' => null, 'crea_mg_eng' => 'MG-1',
        ]);
        // Copia completa, mesmo beneficiario.
        $this->semear('sinc_cisterna_rel_fornecedor', 201, [
            'id' => 201, 'cisterna_id' => 500, 'num_instalacao' => '4321',
            'data_relatorio' => '2025-11-19', 'crea_mg_eng' => 'MG-1',
            'nome_eng_relatorio' => 'Eng Completo', 'bairro' => 'Centro',
        ]);

        $this->artisan('cisterna:refinar-legado', ['--only' => 'vistorias'])->assertExitCode(0);

        // Uma vistoria so, a completa.
        $vistorias = CisternaVistoria::where('beneficiario_id', $this->beneficiario->id)
            ->where('etapa', EtapaVistoria::FORNECEDOR->value)->get();

        $this->assertCount(1, $vistorias);
        $this->assertSame(4321, $vistorias->first()->numero_instalacao);
        $this->assertSame(201, $vistorias->first()->legacy_id);

        // A descartada ficou registrada.
        $this->assertSame(
            'skipped',
            DB::table('cisterna_etl_log')->where('legacy_id', 200)
                ->where('legacy_table', 'sinc_cisterna_rel_fornecedor')->value('acao')
        );
    }
```

- [ ] **Step 3: Escrever `MapaItensLegado`**

O nucleo da traducao das ~87 colunas. Cada etapa nomeava as mesmas coisas de forma diferente.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;

/**
 * Traduz o checklist do legado para linhas de cisterna_itens_conferidos.
 *
 * As tres tabelas de relatorio nomeavam os mesmos 13 itens de formas
 * diferentes:
 *
 *  - fornecedor: {item}_opcao com 'sim'/'nao', mais qtd_calha,
 *    qtd_tubulacao, te_90_pbv_qtd, joelho_90_pbv_qtd, luva_pvc_qtd,
 *    cap_pvc_qtd
 *  - compdec: {item} booleano, mais calha_metros, tubulacao_metros,
 *    {peca}_qtd, e fixacao desdobrada em fix_abracadeira, fix_bucha,
 *    fix_parafuso
 *  - cedec: {item} booleano puro, sem quantidade
 *
 * O nome do item tambem divergia: `cisterna_opcao` no fornecedor e
 * `cisterna_logo` no COMPDEC e no CEDEC.
 */
final class MapaItensLegado
{
    /**
     * Coluna de quantidade por item, na etapa do fornecedor.
     *
     * A tabela tem DOIS pares de colunas para calha e tubulacao, de geracoes
     * diferentes do formulario: calha_metros/qtd_calha e
     * tubulacao_metros/qtd_tubulacao. Verificado em producao: os `*_metros`
     * estao SEMPRE nulos nesta tabela (0 de 856) e so os `qtd_*` sao usados
     * (827 e 828). Por isso o mapa aponta para os `qtd_*`.
     *
     * No COMPDEC e o inverso: la `calha_metros` e que esta preenchido (679 de
     * 858) — ver QTD_COMPDEC.
     *
     * @var array<string, string>
     */
    private const QTD_FORNECEDOR = [
        'calha' => 'qtd_calha',
        'tubulacao' => 'qtd_tubulacao',
        'fixacao' => 'qtd_fixacao',
        'te_pvc' => 'te_90_pbv_qtd',
        'joelho_pvc' => 'joelho_90_pbv_qtd',
        'luva_pvc' => 'luva_pvc_qtd',
        'cap_pvc' => 'cap_pvc_qtd',
    ];

    /**
     * Coluna de quantidade por item, na etapa do COMPDEC.
     *
     * @var array<string, string>
     */
    private const QTD_COMPDEC = [
        'calha' => 'calha_metros',
        'tubulacao' => 'tubulacao_metros',
        'te_pvc' => 'te_pvc_qtd',
        'joelho_pvc' => 'joelho_pvc_qtd',
        'luva_pvc' => 'luva_pvc_qtd',
        'cap_pvc' => 'cap_pvc_qtd',
    ];

    /**
     * @param  array<string, mixed>  $doc
     * @return array<int, array{item: string, conferido: bool, quantidade: ?float, unidade: ?string, detalhes: ?array<string, string>}>
     */
    public static function paraEtapa(EtapaVistoria $etapa, array $doc): array
    {
        $linhas = [];

        foreach (ItemInstalacao::cases() as $item) {
            $conferido = self::conferido($etapa, $item, $doc);

            if ($conferido === null) {
                // Coluna ausente no doc: o item nao foi avaliado nesta etapa.
                continue;
            }

            $quantidade = self::quantidade($etapa, $item, $doc);

            $linhas[] = [
                'item' => $item->value,
                'conferido' => $conferido,
                'quantidade' => $quantidade,
                'unidade' => $quantidade === null ? null : $item->unidadePadrao()?->value,
                'detalhes' => self::detalhes($etapa, $item, $doc),
            ];
        }

        return $linhas;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function conferido(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?bool
    {
        foreach (self::colunasDeConferencia($etapa, $item) as $coluna) {
            if (! array_key_exists($coluna, $doc)) {
                continue;
            }

            return NormalizaEntrada::booleanoSimNao($doc[$coluna]) ?? false;
        }

        // Na etapa do fornecedor, as quatro pecas de PVC (te, joelho, luva,
        // cap) NAO tem coluna booleana: a tabela so tem te_90_pbv_qtd,
        // joelho_90_pbv_qtd, luva_pvc_qtd e cap_pvc_qtd. Verificado em
        // producao: 827 das 856 linhas tem essas quantidades preenchidas.
        //
        // Sem este fallback o item seria descartado por ausencia de booleano,
        // e a carga perderia 827 x 4 = 3.308 registros de item.
        $quantidade = self::quantidade($etapa, $item, $doc);

        if ($quantidade !== null) {
            return $quantidade > 0;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private static function colunasDeConferencia(EtapaVistoria $etapa, ItemInstalacao $item): array
    {
        if ($etapa === EtapaVistoria::FORNECEDOR) {
            // O fornecedor chamava o primeiro item de `cisterna_opcao`, nao
            // `cisterna_logo_opcao`.
            $base = $item === ItemInstalacao::CISTERNA_LOGO ? 'cisterna' : $item->value;

            return [$base.'_opcao', $item->value];
        }

        return [$item->value];
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function quantidade(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?float
    {
        $mapa = match ($etapa) {
            EtapaVistoria::FORNECEDOR => self::QTD_FORNECEDOR,
            EtapaVistoria::COMPDEC => self::QTD_COMPDEC,
            // O CEDEC so conferia, sem quantidade.
            EtapaVistoria::CEDEC => [],
        };

        $coluna = $mapa[$item->value] ?? null;

        return $coluna === null ? null : NormalizaEntrada::decimal($doc[$coluna] ?? null);
    }

    /**
     * Somente fixacao no COMPDEC tem subquantidades. No legado eram tres
     * colunas soltas; aqui viram uma chave cada em detalhes jsonb.
     *
     * @param  array<string, mixed>  $doc
     * @return array<string, string>|null
     */
    private static function detalhes(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?array
    {
        if (! $item->aceitaDetalhes() || $etapa !== EtapaVistoria::COMPDEC) {
            return null;
        }

        $detalhes = [];

        foreach (['abracadeira' => 'fix_abracadeira', 'bucha' => 'fix_bucha', 'parafuso' => 'fix_parafuso'] as $chave => $coluna) {
            $valor = trim((string) ($doc[$coluna] ?? ''));

            if ($valor !== '') {
                $detalhes[$chave] = $valor;
            }
        }

        return $detalhes === [] ? null : $detalhes;
    }
}
```

- [ ] **Step 4: Escrever `RefinaVistoriaFornecedor`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefinaVistoriaFornecedor implements Refinador
{
    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_fornecedor';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado. '
                .'Refinar beneficiarios antes.', $doc);

            return;
        }

        $numero = $this->inteiroOuNulo($doc['num_instalacao'] ?? null);

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria vistoria de fornecedor, numero {$numero}.");

            return;
        }

        // Numero repetido no legado (nao havia UNIQUE): a primeira linha
        // vence, a segunda entra como erro com o payload preservado.
        if ($numero !== null) {
            $conflito = CisternaVistoria::where('numero_instalacao', $numero)
                ->where(fn ($q) => $q->where('etapa', '!=', EtapaVistoria::FORNECEDOR->value)
                    ->orWhere('legacy_id', '!=', $legacyId))
                ->first();

            if ($conflito !== null) {
                RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                    "Numero de instalacao {$numero} ja usado pela vistoria #{$conflito->id}. "
                    .'Duplicata no legado.', $doc);

                return;
            }
        }

        $atributos = [
            'beneficiario_id' => (int) $beneficiarioId,
            'etapa' => EtapaVistoria::FORNECEDOR->value,
            'numero_instalacao' => $numero,
            'engenheiro_nome' => $this->texto($doc['nome_eng_relatorio'] ?? null, 150),
            'engenheiro_crea' => $this->texto($doc['crea_mg_eng'] ?? null, 30),
            'data_relatorio' => $this->data($doc['data_relatorio'] ?? null),
            'local_relatorio' => $this->texto($doc['municipio'] ?? null, 255),
            'endereco' => $this->texto($doc['endereco'] ?? null, 150),
            'bairro' => $this->texto($doc['bairro'] ?? null, 100),
            'latitude' => NormalizaEntrada::decimal($doc['latitude'] ?? null),
            'longitude' => NormalizaEntrada::decimal($doc['longitude'] ?? null),
            'observacoes' => $this->texto($doc['obs_instal_relatorio'] ?? null, 1000),
            // No legado a conclusao era inferida de crea_mg preenchido e
            // diferente de vazio.
            'concluida_em' => $this->concluidaEm($doc['crea_mg_eng'] ?? null, $doc['data_relatorio'] ?? null),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::FORNECEDOR->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                if ($existente !== null) {
                    $existente->update($atributos);
                    $this->sincronizarItens($existente, $doc);
                    RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

                    return;
                }

                // O observer marca situacao_obra como instalado.
                $criada = CisternaVistoria::create($atributos);
                $this->sincronizarItens($criada, $doc);
                RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar vistoria: '.$e->getMessage(), $doc);
        }
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function sincronizarItens(CisternaVistoria $vistoria, array $doc): void
    {
        $vistoria->itensConferidos()->delete();

        foreach (MapaItensLegado::paraEtapa(EtapaVistoria::FORNECEDOR, $doc) as $linha) {
            $vistoria->itensConferidos()->create($linha);
        }
    }

    private function concluidaEm(mixed $crea, mixed $data): ?string
    {
        if (trim((string) ($crea ?? '')) === '') {
            return null;
        }

        return $this->data($data) ?? now()->toDateTimeString();
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        $digitos = preg_replace('/\D/', '', (string) ($valor ?? '')) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 5: Escrever `RefinaVistoriaCompdec`**

A diferenca essencial: `instalacao_id` aponta para `sinc_cisterna_rel_fornecedor.id`, nao para a cisterna. E linhas totalmente vazias — criadas como efeito colateral pelo legado — sao ignoradas.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefinaVistoriaCompdec implements Refinador
{
    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_compdec';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $crea = trim((string) ($doc['crea_mg'] ?? ''));
        $data = trim((string) ($doc['data_relatorio'] ?? ''));

        // O legado criava RelatorioInstalacaoCompdec::create(['instalacao_id'])
        // vazio junto com o store do fornecedor
        // (CisternaController.php:1682). Linha sem engenheiro e sem data e
        // placeholder, nao conferencia realizada.
        if ($crea === '' && ($data === '' || str_starts_with($data, '0000'))) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Linha vazia criada como efeito colateral no legado: sem conferencia registrada.');

            return;
        }

        $instalacaoLegacyId = (int) ($doc['instalacao_id'] ?? 0);

        // instalacao_id aponta para a vistoria do FORNECEDOR, nao para a
        // cisterna. Precisa dela para achar o beneficiario.
        $doFornecedor = CisternaVistoria::where('etapa', EtapaVistoria::FORNECEDOR->value)
            ->where('legacy_id', $instalacaoLegacyId)
            ->first(['id', 'beneficiario_id']);

        if ($doFornecedor === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Vistoria de fornecedor {$instalacaoLegacyId} nao encontrada. "
                .'Refinar sinc_cisterna_rel_fornecedor antes.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'dry-run: criaria conferencia da COMPDEC para o beneficiario '
                .$doFornecedor->beneficiario_id.'.');

            return;
        }

        $atributos = [
            'beneficiario_id' => (int) $doFornecedor->beneficiario_id,
            'etapa' => EtapaVistoria::COMPDEC->value,
            // Somente a etapa do fornecedor tem numero de instalacao.
            'numero_instalacao' => null,
            'engenheiro_crea' => $crea === '' ? null : mb_substr($crea, 0, 30),
            'data_relatorio' => $this->data($data),
            'local_relatorio' => $this->texto($doc['local_relatorio'] ?? null, 255),
            'concluida_em' => $crea === '' ? null : ($this->data($data) ?? now()->toDateTimeString()),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::COMPDEC->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                $vistoria = $existente ?? CisternaVistoria::create($atributos);

                if ($existente !== null) {
                    $existente->update($atributos);
                }

                $vistoria->itensConferidos()->delete();

                foreach (MapaItensLegado::paraEtapa(EtapaVistoria::COMPDEC, $doc) as $linha) {
                    $vistoria->itensConferidos()->create($linha);
                }

                $existente === null
                    ? RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id)
                    : RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar conferencia da COMPDEC: '.$e->getMessage(), $doc);
        }
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 6: Escrever `RefinaVistoriaCedec`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Diferente do COMPDEC, sinc_cisterna_rel_cedec aponta direto para a cisterna
 * (coluna cisterna_id). E a unica etapa com dados administrativos.
 */
class RefinaVistoriaCedec implements Refinador
{
    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_cedec';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado.', $doc);

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria fiscalizacao da CEDEC para o beneficiario {$beneficiarioId}.");

            return;
        }

        $crea = trim((string) ($doc['crea_mg'] ?? ''));

        $atributos = [
            'beneficiario_id' => (int) $beneficiarioId,
            'etapa' => EtapaVistoria::CEDEC->value,
            'numero_instalacao' => null,
            'engenheiro_crea' => $crea === '' ? null : mb_substr($crea, 0, 30),
            'engenheiro_art' => $this->texto($doc['art'] ?? null, 50),
            'data_relatorio' => $this->data($doc['data_relatorio'] ?? null),
            'local_relatorio' => $this->texto($doc['local_relatorio'] ?? null, 255),

            // Exclusivos da etapa CEDEC.
            'processo_sei' => $this->texto($doc['processo_sei'] ?? null, 100),
            'contrato' => $this->texto($doc['contrato'] ?? null, 100),
            'empenho' => $this->texto($doc['empenho'] ?? null, 100),
            'placa_obras' => $this->inteiroOuNulo($doc['placa_obras'] ?? null),

            'concluida_em' => $crea === ''
                ? null
                : ($this->data($doc['data_relatorio'] ?? null) ?? now()->toDateTimeString()),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::CEDEC->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                $vistoria = $existente ?? CisternaVistoria::create($atributos);

                if ($existente !== null) {
                    $existente->update($atributos);
                }

                $vistoria->itensConferidos()->delete();

                foreach (MapaItensLegado::paraEtapa(EtapaVistoria::CEDEC, $doc) as $linha) {
                    $vistoria->itensConferidos()->create($linha);
                }

                $existente === null
                    ? RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id)
                    : RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $vistoria->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar fiscalizacao da CEDEC: '.$e->getMessage(), $doc);
        }
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        $digitos = preg_replace('/\D/', '', (string) ($valor ?? '')) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 7: Escrever `RefinaNotificacoes`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Throwable;

class RefinaNotificacoes implements Refinador
{
    public function recurso(): string
    {
        return 'notificacoes';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_notificacoes';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado.', $doc);

            return;
        }

        $observacao = trim((string) ($doc['obs'] ?? ''));

        if ($observacao === '') {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Notificacao sem texto.');

            return;
        }

        $respondida = NormalizaEntrada::booleanoSimNao($doc['respondida'] ?? null) ?? false;

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria notificacao para o beneficiario {$beneficiarioId}.");

            return;
        }

        $atributos = [
            // No legado a notificacao so podia pender da cisterna. O morph
            // permite pender de uma vistoria, mas o importado mantem a
            // semantica original.
            'notificavel_type' => CisternaBeneficiario::class,
            'notificavel_id' => (int) $beneficiarioId,
            'observacao' => $observacao,
            'respondida' => $respondida,
            'respondida_em' => $respondida ? ($doc['updated_at'] ?? now()) : null,
            'legacy_id' => $legacyId,
        ];

        try {
            $existente = CisternaNotificacao::where('legacy_id', $legacyId)->first();

            if ($existente !== null) {
                $existente->update($atributos);
                RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

                return;
            }

            $criada = CisternaNotificacao::create($atributos);
            RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar notificacao: '.$e->getMessage(), $doc);
        }
    }
}
```

- [ ] **Step 8: Escrever `RefinaMidia`**

Copia os arquivos do disco `legado_cisterna` para as collections do MediaLibrary. Roda por ultimo, quando todos os registros ja existem.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copia os arquivos do legado para as collections do MediaLibrary.
 *
 * No legado eram ~54 colunas de caminho de arquivo. As fotos do imovel
 * ficavam em cisterna/{cpf}/ — CPF no caminho, dado pessoal — e as de
 * vistoria em relatorios/cisterna/{form}/{id}/.
 *
 * As colunas img_*_lk guardavam link do Google Drive, nao arquivo local:
 * preservadas em custom_properties.origem_legado para conferencia manual.
 */
class RefinaMidia implements Refinador
{
    /**
     * Coluna do legado -> angulo da foto do imovel.
     *
     * @var array<string, string>
     */
    private const ANGULOS = [
        'img_frontal' => 'frontal',
        'img_lat_direito' => 'lateral_direita',
        'img_lat_esquerdo' => 'lateral_esquerda',
        'img_fundo' => 'fundo',
        'img_local_ins_p1' => 'local_instalacao_1',
        'img_local_ins_p2' => 'local_instalacao_2',
        'img_op1' => 'opcional_1',
        'img_op2' => 'opcional_2',
        'img_op3' => 'opcional_3',
        'img_op4' => 'opcional_4',
    ];

    public function recurso(): string
    {
        return 'midia';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiario = CisternaBeneficiario::where('legacy_id', $legacyId)->first();

        if ($beneficiario === null) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario nao importado: midia ignorada.');

            return;
        }

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'dry-run: copiaria as fotos e comprovantes.');

            return;
        }

        $copiadas = 0;
        $ausentes = [];

        // Fotos do imovel. No legado o diretorio era o CPF sem mascara.
        $cpf = $beneficiario->cpf;

        foreach (self::ANGULOS as $coluna => $angulo) {
            $observacao = trim((string) ($doc[$coluna] ?? ''));
            $caminho = "cisterna/{$cpf}/{$cpf}{$coluna}.jpg";

            if (! Storage::disk('legado_cisterna')->exists($caminho)) {
                // Link do Google Drive: nao ha arquivo local para copiar.
                $link = trim((string) ($doc[$coluna.'_lk'] ?? ''));

                if ($link !== '') {
                    $ausentes[] = "{$angulo} (Google Drive: {$link})";
                }

                continue;
            }

            if ($beneficiario->getMedia('fotos_imovel')->contains(
                fn ($m): bool => $m->getCustomProperty('angulo') === $angulo
            )) {
                continue;
            }

            $beneficiario
                ->addMediaFromDisk($caminho, 'legado_cisterna')
                ->preservingOriginal()
                ->withCustomProperties([
                    'angulo' => $angulo,
                    'observacao' => $observacao === '' ? null : $observacao,
                    'origem_legado' => $caminho,
                ])
                ->toMediaCollection('fotos_imovel');

            $copiadas++;
        }

        $copiadas += $this->copiarComprovantes($beneficiario, $doc, $ausentes);
        $copiadas += $this->copiarFotosDeVistoria($beneficiario, $ausentes);

        if ($ausentes !== []) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Arquivos nao localizados: '.implode('; ', $ausentes), null);

            return;
        }

        RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId,
            $beneficiario->id, ['copiadas' => $copiadas]);
    }

    /**
     * @param  array<string, mixed>  $doc
     * @param  array<int, string>  $ausentes
     */
    private function copiarComprovantes(CisternaBeneficiario $beneficiario, array $doc, array &$ausentes): int
    {
        $mapa = [
            'anexo_deficiencia' => 'deficiencia',
            'anexo_mulher' => 'chefia_mulher',
            'anexo_observacao' => 'observacao',
        ];

        $copiados = 0;

        foreach ($mapa as $coluna => $tipo) {
            $caminho = trim((string) ($doc[$coluna] ?? ''));

            if ($caminho === '') {
                continue;
            }

            if (! Storage::disk('legado_cisterna')->exists($caminho)) {
                $ausentes[] = "comprovante {$tipo} ({$caminho})";
                continue;
            }

            if ($beneficiario->getMedia('comprovantes')->contains(
                fn ($m): bool => $m->getCustomProperty('tipo') === $tipo
            )) {
                continue;
            }

            $beneficiario
                ->addMediaFromDisk($caminho, 'legado_cisterna')
                ->preservingOriginal()
                ->withCustomProperties(['tipo' => $tipo, 'origem_legado' => $caminho])
                ->toMediaCollection('comprovantes');

            $copiados++;
        }

        return $copiados;
    }

    /**
     * As fotos de vistoria ficavam em diretorio por formulario e id da
     * cisterna, com nome {item}_foto1.ext / {item}_foto2.ext.
     *
     * @param  array<int, string>  $ausentes
     */
    private function copiarFotosDeVistoria(CisternaBeneficiario $beneficiario, array &$ausentes): int
    {
        $diretorios = [
            EtapaVistoria::FORNECEDOR->value => 'relatorios/cisterna/form_fornecedor_fiscalizacao',
            EtapaVistoria::COMPDEC->value => 'relatorios/cisterna/form_compdec_instalacao',
            EtapaVistoria::CEDEC->value => 'relatorios/cisterna/form_cedec_fiscalizacao',
        ];

        $copiadas = 0;

        foreach ($beneficiario->vistorias as $vistoria) {
            $base = $diretorios[$vistoria->etapa->value] ?? null;

            if ($base === null) {
                continue;
            }

            $pasta = "{$base}/{$beneficiario->legacy_id}";

            if (! Storage::disk('legado_cisterna')->exists($pasta)) {
                continue;
            }

            foreach (Storage::disk('legado_cisterna')->files($pasta) as $arquivo) {
                $nome = pathinfo($arquivo, PATHINFO_FILENAME);

                // Assinatura vai para a collection dedicada.
                if (str_starts_with($nome, 'assinatura')) {
                    if ($vistoria->getMedia('assinatura_engenheiro')->isEmpty()) {
                        $vistoria->addMediaFromDisk($arquivo, 'legado_cisterna')
                            ->preservingOriginal()
                            ->withCustomProperties(['origem_legado' => $arquivo])
                            ->toMediaCollection('assinatura_engenheiro');
                        $copiadas++;
                    }

                    continue;
                }

                [$item, $sequencia] = $this->interpretarNome($nome);

                if ($item === null) {
                    $ausentes[] = "foto nao reconhecida: {$arquivo}";
                    continue;
                }

                $jaTem = $vistoria->getMedia('fotos_vistoria')->contains(
                    fn ($m): bool => $m->getCustomProperty('item') === $item
                        && (int) $m->getCustomProperty('sequencia') === $sequencia
                );

                if ($jaTem) {
                    continue;
                }

                $vistoria->addMediaFromDisk($arquivo, 'legado_cisterna')
                    ->preservingOriginal()
                    ->withCustomProperties([
                        'item' => $item,
                        'sequencia' => $sequencia,
                        'origem_legado' => $arquivo,
                    ])
                    ->toMediaCollection('fotos_vistoria');

                $copiadas++;
            }
        }

        return $copiadas;
    }

    /**
     * "calha_foto2" -> ['calha', 2]. "cisterna_foto1" -> ['cisterna_logo', 1],
     * porque o fornecedor chamava o primeiro item de `cisterna`.
     *
     * @return array{0: ?string, 1: int}
     */
    private function interpretarNome(string $nome): array
    {
        if (! preg_match('/^(.+?)_foto(\d*)$/', $nome, $partes)) {
            return [null, 1];
        }

        $base = $partes[1] === 'cisterna' ? ItemInstalacao::CISTERNA_LOGO->value : $partes[1];
        $item = ItemInstalacao::tryFrom($base);

        return [$item?->value, $partes[2] === '' ? 1 : (int) $partes[2]];
    }
}
```

- [ ] **Step 9: Registrar os cinco refinadores e sincronizar a sequence**

Em `RefinarCisternaLegadoCommand::todosOsRefinadores()`, acrescentar na ordem:

```php
            'vistorias' => app(RefinaVistoriaFornecedor::class),
            'vistorias_compdec' => app(RefinaVistoriaCompdec::class),
            'vistorias_cedec' => app(RefinaVistoriaCedec::class),
            'notificacoes' => app(RefinaNotificacoes::class),
            'midia' => app(RefinaMidia::class),
```

`--only=vistorias` deve rodar as tres etapas. Ajustar `refinadoresSelecionados()`:

```php
    /**
     * Alias -> chaves internas. `vistorias` roda as tres etapas na ordem
     * correta: o COMPDEC depende da vistoria do fornecedor ja existir.
     *
     * @var array<string, array<int, string>>
     */
    private const ALIASES = [
        'vistorias' => ['vistorias', 'vistorias_compdec', 'vistorias_cedec'],
    ];

    /**
     * @return array<string, Refinador>
     */
    private function refinadoresSelecionados(): array
    {
        $todos = $this->todosOsRefinadores();
        $only = $this->option('only');

        if ($only === null || trim((string) $only) === '') {
            return $todos;
        }

        $pedidos = [];

        foreach (array_map('trim', explode(',', (string) $only)) as $pedido) {
            foreach (self::ALIASES[$pedido] ?? [$pedido] as $chave) {
                $pedidos[] = $chave;
            }
        }

        return array_filter(
            $todos,
            fn (string $chave): bool => in_array($chave, $pedidos, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
```

E, ao final de `handle()`, antes do resumo, alinhar a sequence:

```php
        if (! $dryRun && array_key_exists('vistorias', $selecionados)) {
            // Sem isso a sequence comeca em 1 e colide com todo numero de
            // instalacao importado do legado (faixa real: 1 a 50.000).
            $maximo = app(NumeracaoInstalacaoService::class)->sincronizarSequenceComOMaximo();
            $this->line("Sequence de numero de instalacao alinhada em {$maximo}.");
        }

        if (! $dryRun && array_key_exists('beneficiarios', $selecionados)) {
            $habilitados = $this->marcarMunicipiosHabilitados();
            $this->line("Municipios marcados com at_cisterna: {$habilitados}.");
        }
```

- [ ] **Step 10: Rodar e confirmar que passa**

Run: `scripts/test-host.sh --filter=RefinarVistoriasTest`
Expected: PASS, 11 testes.

- [ ] **Step 11: Rodar o ETL completo em dry-run e depois de verdade**

```bash
$PHP artisan cisterna:extrair-legado
$PHP artisan cisterna:refinar-legado --dry-run
```

Conferir o resumo. Se `error` estiver alto, investigar antes de gravar:

```bash
$PHP artisan tinker --execute="
dump(DB::table('cisterna_etl_log')->where('acao','error')
  ->selectRaw('recurso, motivo, COUNT(*) AS qtd')
  ->groupBy('recurso','motivo')->orderByDesc('qtd')->get());"
```

Depois, a carga real:

```bash
$PHP artisan cisterna:refinar-legado
$PHP artisan cisterna:refinar-legado    # segunda passada: idempotencia
```

A segunda execucao nao pode gerar nenhum `inserted` novo. Conferir:

```bash
$PHP artisan tinker --execute="
dump(DB::table('cisterna_etl_log')->selectRaw('acao, COUNT(*) AS qtd')->groupBy('acao')->get());"
```

- [ ] **Step 12: Commit**

```bash
git add app/Modules/Cisterna/Domain/Etl \
        app/Modules/Cisterna/Console/RefinarCisternaLegadoCommand.php \
        tests/Feature/Cisterna/RefinarVistoriasTest.php
git commit -m "✨ feat(cisterna): refino de vistorias, itens conferidos, notificacoes e midia"
```

**Portao da Fase 3.** ETL completo e idempotente: duas execucoes seguidas sem duplicar registro.

---

## FASE 4 — Limpeza

### Task 19: Remover os assets do scaffold e verificar o modulo inteiro

Ultima task. Remove as 10 paginas e componentes Vue do scaffold, que modelavam o dominio inventado, e faz a verificacao final contra os criterios da secao 11 do spec.

**Files:**
- Delete: `resources/js/Pages/Cisterna/{Index,Create,Edit,Show}.vue`
- Delete: `resources/js/Templates/Cisterna/{CisternaFormTemplate,CisternaIndexTemplate}.vue`
- Delete: `resources/js/Components/Organisms/Cisterna/{CisternaForm,CisternaTable,CisternaFiltersSection,CisternaStatsCards}.vue`
- Delete: `resources/js/Components/Molecules/Cisterna/{StatusCisternaBadge,TipoCisternaBadge}.vue`
- Modify: `resources/js/Support/moduleIcons.js` (se referenciar as paginas removidas)
- Modify: `resources/js/Composables/auth/useWelcomeTour.js` (se referenciar rotas removidas)

**Interfaces:**
- Consumes: nada
- Produces: arvore de assets sem referencia ao dominio antigo

- [ ] **Step 1: Localizar as referencias antes de remover**

Run:
```bash
grep -rn "TipoCisternaBadge\|StatusCisternaBadge\|CisternaFormTemplate\|CisternaIndexTemplate\|Pages/Cisterna\|cisternas.index\|cisternas.create\|cisternas.edit\|cisternas.show" resources/js
```

Anotar cada arquivo. `moduleIcons.js`, `useWelcomeTour.js` e `ziggy.js` apareceram no levantamento do legado e provavelmente citam rotas que a Task 6 renomeou (`cisternas.index` virou `cisternas.beneficiarios.index`).

- [ ] **Step 2: Remover as paginas e componentes**

```bash
git rm -r resources/js/Pages/Cisterna \
          resources/js/Templates/Cisterna \
          resources/js/Components/Organisms/Cisterna \
          resources/js/Components/Molecules/Cisterna
```

- [ ] **Step 3: Corrigir as referencias remanescentes**

Em `resources/js/Support/moduleIcons.js`, ajustar a entrada do modulo cisterna para apontar para `cisternas.beneficiarios.index`.

Em `resources/js/Composables/auth/useWelcomeTour.js`, se houver passo do tour citando as paginas removidas, apontar para a nova rota ou remover o passo.

`resources/js/ziggy.js` e gerado: regenerar em vez de editar a mao.

Run: `$PHP artisan ziggy:generate`

- [ ] **Step 4: Confirmar que o build passa**

Run: `npm run build`
Expected: build conclui sem erro de import nao resolvido. Se quebrar apontando para componente removido, o Step 1 deixou referencia passar.

- [ ] **Step 5: Verificacao final — criterios do spec**

Rodar cada item da secao 11 do spec:

```bash
# 1. Schema completo, sem residuo do scaffold
$PHP artisan migrate:fresh --seed
scripts/test-host.sh --filter=SchemaCisternaTest

# 2. Suite do modulo verde
scripts/test-host.sh --filter=Cisterna

# 3. Estatica
vendor/bin/pint --test app/Modules/Cisterna app/Policies
vendor/bin/phpstan analyse app/Modules/Cisterna --memory-limit=1G

# 7. Nenhuma referencia ao scaffold
grep -rn "TipoCisterna\|StatusCisterna\|CisternaPolicy\|CisternaDTO" app config database routes resources/js
grep -rn "\bcisternas\b" database/migrations   # a tabela `cisternas` nao deve aparecer

# 8. Nenhuma pasta fora do padrao
ls app/Modules/Cisterna        # sem Http/, sem Exports/, sem Policies/

# 10. Municipios habilitados
$PHP artisan tinker --execute="dump(count(App\Models\Municipio::idsHabilitadosCisterna()));"

# 12. Nenhum RanqueamentoService
grep -rn "RanqueamentoService" app
```

Expected para os greps: nenhuma saida. Para o `ls`: apenas `Console Controllers DTOs Domain Enums Models Observers Requests Resources Services Support CisternaServiceProvider.php`.

- [ ] **Step 6: Registrar as pendencias que sobreviveram**

Acrescentar ao final de `docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md` a lista do que ficou aberto, para a conversa com a area antes do corte de producao:

1. **Formato do export**: virou CSV; o legado entregava `.xlsx`. Confirmar se serve.
2. **PDF de QR Code em lote e folhas vazias**: nao portados, o NewSDC nao tem biblioteca de PDF. **Perda de funcionalidade** — era como se imprimiam os adesivos.
3. **Ranqueamento**: nao existe calculo no legado; a coluna e importada. Se a area quiser o calculo, e feature nova.
4. **Role `cisterna_fornecedor`**: criada nesta implementacao. Definir como o fornecedor externo autentica (no legado era o `Classe.LoginExterno` do gestaocedec).
5. **Erros do `cisterna_etl_log`**: revisar linha a linha com a area antes de considerar a migracao completa.
6. **Frontend**: plano separado, comecando pela leitura das 22 views Blade (7.632 linhas).
7. **Tabelas efemeras**: agendar o drop de `cisterna_legado_raw` e `cisterna_etl_log` apos a validacao, como `compdec_etl_log`.

- [ ] **Step 7: Commit**

```bash
git add -A resources/js docs/superpowers/notas
git commit -m "🔥 remove(cisterna): assets do scaffold e pendencias registradas"
```

**Portao da Fase 4.** Modulo backend + ETL completo, verificado contra os 13 criterios do spec.

---

## Escopo entregue e nao entregue

**Entregue:** dominio de 8 tabelas com tres pontos de polimorfismo; enums; models e factories; escopo por perfil institucional; cadeia de vistoria em tres etapas; checklist unificado; notificacoes polimorficas; QR Code; export CSV; 7 controllers; ETL em duas etapas idempotente; 20 defeitos do legado corrigidos.

**Nao entregue, com motivo:**

| Item | Motivo |
|---|---|
| Telas Vue/Inertia | Plano separado. Comeca pela leitura das 22 views Blade do legado (lacuna L4 do spec) |
| PDF de QR em lote e folhas vazias | NewSDC nao tem biblioteca de PDF. **Perda de funcionalidade** — decidir antes do corte |
| Export em `.xlsx` | `maatwebsite/excel` ausente; CSV e o padrao do projeto. Confirmar com a area |
| Calculo de ranqueamento | Nao existe no legado (lacuna L1). Seria feature nova |
| API mobile | Codigo 100% comentado no legado |
| Autenticacao do fornecedor externo | Role criada, mas o fluxo de login vinha do `Classe.LoginExterno` do gestaocedec |
| Desativar o modulo no legado `sdc` | Decisao operacional do corte de producao |
