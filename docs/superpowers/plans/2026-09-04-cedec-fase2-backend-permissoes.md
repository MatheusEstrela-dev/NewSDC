# Cedec Fase 2 — Backend, Permissoes e Sidebar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar o backend completo do modulo `Cedec` — enums, DTO de filtro, service, resource, request, controller, rotas, permissoes e o item de sidebar — sem nenhuma pagina Vue. As fases 3, 4 e 5 consomem exatamente as assinaturas e props definidas aqui.

**Architecture:** DDD modular como o resto do repo: `app/Modules/Cedec/{Enums,DTOs,Services,Resources,Requests,Controllers}`. `CedecPrefeituraService` opera por `municipio_id` (nao por orgao) e faz `municipios LEFT JOIN compdec_prefeituras LEFT JOIN cedec_municipio LEFT JOIN dec_redecs` — municipio sem prefeitura tambem aparece. O Model `App\Modules\Compdec\Models\Prefeitura` e importado, nao duplicado (precedente: `Compdec\Models\Orgao` importado por Pmda/PlanCon/AjudaHumanitaria). O `PrefeituraService` do Compdec fica intacto; os dois convergem na mesma linha via `updateOrCreate(['municipio_id' => ...])`.

**Tech Stack:** Laravel 12 / PHP 8.3, Inertia 2 (server-side props apenas nesta fase), Vue 3 (so o item de Sidebar), PostgreSQL, Spatie Permission, Spatie Media Library, Octane/FrankenPHP, PHPUnit 11 (nao Pest).

**Spec:** `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md` (secoes 4, 5, 6, 9)
**Contrato de interfaces:** `docs/superpowers/plans/2026-09-04-cedec-contrato-interfaces.md` (secoes 0, 2, 3, 4, 5, 6, 7 sao desta fase)

---

## Global Constraints

Copiadas literalmente do contrato (secao 0) e da spec.

- App executavel em `NewSDC/SDC`. Todo caminho deste plano e relativo a essa pasta, salvo quando escrito por extenso.
- Testes: **PHPUnit, nao Pest.** `declare(strict_types=1)`, namespace `Tests\Feature\Cedec`, trait `Illuminate\Foundation\Testing\DatabaseTransactions`, `Inertia\Testing\AssertableInertia` para props, `Spatie\Permission\Models\Permission` para conceder slug em teste.
- **`SDC/phpunit.xml` NAO isola o banco de teste** (as linhas de sqlite estao comentadas): os testes rodam contra o banco de desenvolvimento, com as 853 prefeituras reais. Toda asserçao de contagem e RELATIVA (captura o "antes", cria fixtures dentro da transacao, compara com o "depois") — nunca assume banco vazio. `DatabaseTransactions` desfaz tudo ao final.
- Comandos no container: `docker exec newsdc_frankenphp_local php artisan test --filter=<Nome>`, `docker exec newsdc_frankenphp_local php -l /app/<caminho>`, `docker exec newsdc_frankenphp_local php artisan route:list --name=cedec`.
- Depois de mudar PHP: `docker exec newsdc_frankenphp_local php artisan octane:reload` (~1s). **Mudar `config/permissions.php` exige RESTART do container**, nunca `octane:reload` — Octane mantem o config carregado na memoria do worker desde o boot; `octane:reload` nao rele o disco.
- **Sem emoji dentro do codigo.** Emoji so na mensagem de commit (gitmoji).
- Commits: `<emoji> tipo(cedec): descricao` em pt-BR. Commit atomico: agrupar os arquivos que entregam UMA mudanca. **Sem trailer de co-autor.**
- Arquivo de teste criado so para depuracao nao entra no commit; os arquivos de teste nomeados neste plano sao entregaveis e entram.
- **NAO registrar `Route::model('municipio', ...)`.** `Route::model()` e GLOBAL neste projeto e ja causou 404 no Pmda e no PlanCon (ver comentarios em `routes/modules/pmda.php:20-39` e `routes/modules/compdec.php:29-43`). `{municipio}` ja e usado por `routes/modules/cisterna.php:100` sem binder explicito. Usar binding implicito por type-hint `App\Models\Municipio`.
- Migration: fase 1 ja consolidou as sete colunas novas em `compdec_prefeituras` na migration de criacao — esta fase **nao mexe em migration nenhuma**.

---

## Dependencia: o que a fase 1 tem de estar entregue

Esta fase assume, exatamente como o contrato descreve (secoes 1, 2, 3):

- Colunas `prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura` em `compdec_prefeituras`, consolidadas em `database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php`.
- `App\Modules\Compdec\Models\Prefeitura::$fillable` com as sete colunas ao final do array.
- `App\Modules\Compdec\DTOs\PrefeituraDTO` com as sete propriedades camelCase no construtor (`prefeitoPartido`, `emailPrefeitura`, `emailPrefeitura2`, `emailPrefeitura3`, `telPrefeitura`, `telPrefeitura2`, `faxPrefeitura`), `fromRequest(int $municipioId, array $data): self` lendo as chaves snake_case correspondentes, e `toArray(): array` devolvendo as mesmas chaves.

Verificacao antes de comecar (o mesmo comando que a fase 5 usa):

```bash
docker exec newsdc_frankenphp_local php artisan tinker --execute="echo implode(',', array_intersect(['email_prefeitura','tel_prefeitura','fax_prefeitura','tel_prefeitura_2','email_prefeitura_2','email_prefeitura_3','prefeito_partido'], \Illuminate\Support\Facades\Schema::getColumnListing('compdec_prefeituras')));"
```

Esperado: as sete colunas listadas. Se sair vazio ou incompleto, a fase 1 nao foi aplicada — pare e conclua a fase 1 antes de continuar.

---

## Decisoes tomadas neste plano (leia antes de comecar)

1. **REDEC vem de `dec_redecs` via `cedec_municipio`, nao de um catalogo novo.** O contrato nao especifica a fonte do filtro/coluna REDEC. Investigando o codigo: `municipios` nao tem `redec_id`; quem tem e `cedec_municipio.redec_id` (migration `2026_03_03_000001_create_cedec_municipio_table.php:16`), e o catalogo de REDECs ja existe em `App\Modules\Decretacoes\Models\Redec` (tabela `dec_redecs`, PK = numero da REDEC) com `App\Modules\Decretacoes\Services\RedecService::toSelectOptions()` cacheado. A ponte e `cedec_municipio.Codmundv = municipios.codigo_ibge` — o mesmo padrao que `ImportCedecMunicipioCommand` e `Municipio::habilitadosCisterna()` ja usam. **Decisao: reusar `RedecService` do modulo Decretacoes por import direto**, do mesmo jeito que o contrato ja autoriza importar `Compdec\Models\Orgao`/`Prefeitura` entre modulos. E uma adicao de dependencia nao mencionada no contrato — sinalizada aqui explicitamente. Nao criar um catalogo de REDEC paralelo dentro do Cedec (duplicaria `dec_redecs`).
2. **A coluna do banco chama `macroregiao` (uma letra r), nao `macrorregiao`.** Verificado na migration `2026_03_03_000001_create_cedec_municipio_table.php:20`: `$table->string('macroregiao', 255)`. O contrato e a spec usam a grafia correta `macrorregiao` para as CHAVES do dominio (enum, filtro, resposta de `indicadoresMunicipais()`); o SQL interno do service tem de referenciar a coluna real `cedec_municipio.macroregiao`. Armadilha registrada para quem for revisar o service.
3. **Nenhuma `Policy` nova.** `App\Policies\PrefeituraPolicy` existente e escopada as abilities `view`/`update` do Compdec (`compdec.prefeitura.view`/`.edit`) e permanece assim. O contrato define `UpdatePrefeituraRequest::authorize()` e as rotas do Cedec com checagem DIRETA de slug (`$user->can('cedec.prefeituras.edit')`, middleware `can:cedec.prefeituras.*`) — gate por slug basta, sem Policy por Model.
4. **`tem_foto` e calculado com uma segunda query, nao com `EXISTS` na projecao.** Uma sub-query `EXISTS` dentro do `SELECT` de cada linha rodaria 853 vezes por listagem. Em vez disso, `listar()` pagina primeiro (ate 100 linhas), coleta os `prefeitura_id` da pagina e faz UMA query em `media` (`whereIn('model_id', ids_da_pagina)`) para saber quais tem foto — 2 queries no total, independente do tamanho da pagina. Ja o filtro `pendencia=sem_foto` PRECISA ser avaliado em SQL (senao a paginacao ficaria incorreta), entao esse caso usa `whereRaw` com `NOT EXISTS` sobre `media` — e o unico lugar do service com SQL cru alem dos LEFT JOINs.
5. **`estatisticas()` reusa a mesma `baseQuery()` e o mesmo `aplicarPendencia()` de `listar()`.** DRY (regra de ouro 4): a definicao de "sem e-mail" nao pode divergir entre o card e o filtro.
6. **`PrefeituraListaResource` recebe `stdClass`, nao um Model Eloquent.** O retorno de `listar()` e um `LengthAwarePaginator` de linhas cruas do Query Builder (join de duas tabelas nao mapeia para um unico Model). O `JsonResource` acessa `$this->campo` normalmente porque `stdClass` suporta a mesma sintaxe de objeto que um Model.
7. **O prop `prefeituras` e o `LengthAwarePaginator` inteiro, nao `Resource::collection(...)->response()`.** `LengthAwarePaginator::toArray()` ja produz nativamente `{current_page, data, from, last_page, per_page, to, total, ...}` — exatamente o formato ACHATADO que o contrato (secao 8) e a fase 3 exigem, sem precisar montar isso a mao. Basta transformar a `Collection` interna do paginador para o formato do Resource ANTES de devolver.

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `app/Modules/Cedec/Enums/Macrorregiao.php` | 10 casos, valor = rotulo |
| `app/Modules/Cedec/Enums/TerritorioDesenvolvimento.php` | 17 casos, valor = rotulo |
| `app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php` | filtro imutavel da listagem |
| `app/Modules/Cedec/Services/CedecPrefeituraService.php` | listagem, estatisticas, indicadores, upsert, foto |
| `app/Modules/Cedec/Resources/PrefeituraListaResource.php` | forma de uma linha da listagem |
| `app/Modules/Cedec/Requests/UpdatePrefeituraRequest.php` | validacao do update |
| `app/Modules/Cedec/Controllers/PrefeituraController.php` | index, edit, update, uploadFoto, removerFoto |
| `routes/modules/cedec.php` | 5 rotas desta fase (fase 5 acrescenta 2) |
| `routes/web.php` (modificar) | `require __DIR__.'/modules/cedec.php'` |
| `config/permissions.php` (modificar) | bloco `CEDEC` + `role_permissions` |
| `resources/js/Components/Sidebar.vue` (modificar) | item "Prefeituras" atras de `cedec.prefeituras.view` |
| `resources/js/Support/moduleIcons.js` (modificar) | `prefeituras: apartment` |

---

## Task 1: Enums `Macrorregiao` e `TerritorioDesenvolvimento`

**Files:**
- Create: `app/Modules/Cedec/Enums/Macrorregiao.php`
- Create: `app/Modules/Cedec/Enums/TerritorioDesenvolvimento.php`
- Test: `tests/Feature/Cedec/CedecEnumsTest.php`

**Interfaces:**
- Consumes: nada (enums puros).
- Produces: `Macrorregiao::label(): string`, `Macrorregiao::opcoes(): array<int, array{value: string, label: string}>`, mesma assinatura em `TerritorioDesenvolvimento`. Consumidos por: `CedecPrefeituraService` (filtro), `PrefeituraController::index()` (prop `macrorregioes`), fase 4 (indicadores read-only, so leitura de valor).

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/CedecEnumsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Modules\Cedec\Enums\Macrorregiao;
use App\Modules\Cedec\Enums\TerritorioDesenvolvimento;
use Tests\TestCase;

/**
 * Os dois enums substituem os arrays PHP hardcoded de cadastrar.php:20-47 do
 * legado gestaocedec (10 macrorregioes, 17 territorios de desenvolvimento).
 * O valor de cada caso E o rotulo, porque o legado gravava o texto em
 * varchar, nao um id.
 */
class CedecEnumsTest extends TestCase
{
    public function test_macrorregiao_tem_os_dez_casos_do_legado(): void
    {
        $valores = array_map(fn (Macrorregiao $c) => $c->value, Macrorregiao::cases());

        $this->assertCount(10, Macrorregiao::cases());
        $this->assertSame([
            'SUL DE MINAS', 'ALTO PARANAIBA', 'CENTRAL', 'ZONA DA MATA',
            'VALE DO RIO DOCE', 'TRIANGULO', 'CENTRO OESTE',
            'JEQUITINHONHA MUCURI', 'NORTE DE MINAS', 'NOROESTE DE MINAS',
        ], $valores);
    }

    public function test_macrorregiao_label_e_o_proprio_valor(): void
    {
        $this->assertSame('SUL DE MINAS', Macrorregiao::SUL_DE_MINAS->label());
    }

    public function test_macrorregiao_opcoes_tem_a_forma_value_label(): void
    {
        $opcoes = Macrorregiao::opcoes();

        $this->assertCount(10, $opcoes);
        $this->assertSame(['value' => 'CENTRAL', 'label' => 'CENTRAL'], $opcoes[2]);
    }

    public function test_territorio_desenvolvimento_tem_os_dezessete_casos_do_legado(): void
    {
        $valores = array_map(fn (TerritorioDesenvolvimento $c) => $c->value, TerritorioDesenvolvimento::cases());

        $this->assertCount(17, TerritorioDesenvolvimento::cases());
        $this->assertSame([
            'Vertentes', 'Vale do Rio Doce', 'Vale do Aco', 'Triangulo Sul',
            'Triangulo Norte', 'Sul', 'Sudoeste', 'Oeste', 'Norte', 'Noroeste',
            'Mucuri', 'Metropolitana', 'Medio e Baixo Jequitinhonha', 'Mata',
            'Central', 'Caparao', 'Alto Jequitinhonha',
        ], $valores);
    }

    public function test_territorio_desenvolvimento_opcoes_tem_a_forma_value_label(): void
    {
        $opcoes = TerritorioDesenvolvimento::opcoes();

        $this->assertCount(17, $opcoes);
        $this->assertSame(['value' => 'Mata', 'label' => 'Mata'], $opcoes[13]);
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CedecEnumsTest`
Expected: FAIL — `Class "App\Modules\Cedec\Enums\Macrorregiao" not found`.

- [ ] **Step 3: Criar `Macrorregiao`**

Criar `app/Modules/Cedec/Enums/Macrorregiao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Enums;

/**
 * As 10 macrorregioes do legado gestaocedec, hardcoded em
 * mod_cedec/backEnd/View/municipio/cadastrar.php:20-30.
 *
 * O valor de cada caso E o rotulo: o legado gravava o texto em
 * cedec_municipio.macroregiao (varchar), nao um id.
 */
enum Macrorregiao: string
{
    case SUL_DE_MINAS = 'SUL DE MINAS';
    case ALTO_PARANAIBA = 'ALTO PARANAIBA';
    case CENTRAL = 'CENTRAL';
    case ZONA_DA_MATA = 'ZONA DA MATA';
    case VALE_DO_RIO_DOCE = 'VALE DO RIO DOCE';
    case TRIANGULO = 'TRIANGULO';
    case CENTRO_OESTE = 'CENTRO OESTE';
    case JEQUITINHONHA_MUCURI = 'JEQUITINHONHA MUCURI';
    case NORTE_DE_MINAS = 'NORTE DE MINAS';
    case NOROESTE_DE_MINAS = 'NOROESTE DE MINAS';

    public function label(): string
    {
        return $this->value;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opcoes(): array
    {
        return array_map(
            static fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 4: Criar `TerritorioDesenvolvimento`**

Criar `app/Modules/Cedec/Enums/TerritorioDesenvolvimento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Enums;

/**
 * Os 17 territorios de desenvolvimento do legado gestaocedec, hardcoded em
 * mod_cedec/backEnd/View/municipio/cadastrar.php:33-49.
 *
 * O valor de cada caso E o rotulo, mesmo motivo de Macrorregiao.
 */
enum TerritorioDesenvolvimento: string
{
    case VERTENTES = 'Vertentes';
    case VALE_DO_RIO_DOCE = 'Vale do Rio Doce';
    case VALE_DO_ACO = 'Vale do Aco';
    case TRIANGULO_SUL = 'Triangulo Sul';
    case TRIANGULO_NORTE = 'Triangulo Norte';
    case SUL = 'Sul';
    case SUDOESTE = 'Sudoeste';
    case OESTE = 'Oeste';
    case NORTE = 'Norte';
    case NOROESTE = 'Noroeste';
    case MUCURI = 'Mucuri';
    case METROPOLITANA = 'Metropolitana';
    case MEDIO_E_BAIXO_JEQUITINHONHA = 'Medio e Baixo Jequitinhonha';
    case MATA = 'Mata';
    case CENTRAL = 'Central';
    case CAPARAO = 'Caparao';
    case ALTO_JEQUITINHONHA = 'Alto Jequitinhonha';

    public function label(): string
    {
        return $this->value;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opcoes(): array
    {
        return array_map(
            static fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 5: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Enums/Macrorregiao.php
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Enums/TerritorioDesenvolvimento.php
docker exec newsdc_frankenphp_local php artisan test --filter=CedecEnumsTest
```
Expected: PASS, 5 testes.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Cedec/Enums/Macrorregiao.php \
        app/Modules/Cedec/Enums/TerritorioDesenvolvimento.php \
        tests/Feature/Cedec/CedecEnumsTest.php
git commit -m "✨ feat(cedec): enums de macrorregiao e territorio de desenvolvimento"
```

---

## Task 2: DTO `PrefeituraFiltroDTO`

**Files:**
- Create: `app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php`
- Test: `tests/Feature/Cedec/PrefeituraFiltroDTOTest.php`

**Interfaces:**
- Consumes: nada.
- Produces: assinatura exata do contrato secao 3 — `fromRequest(Request): self`, `toArray(): array{busca, redec_id, macrorregiao, pendencia}`, propriedades publicas `busca`, `redecId`, `macrorregiao`, `pendencia`, `perPage`. Consumido por `CedecPrefeituraService::listar()` e `PrefeituraController::index()`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/PrefeituraFiltroDTOTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrefeituraFiltroDTOTest extends TestCase
{
    public function test_from_request_sem_parametros_usa_os_defaults(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras'));

        $this->assertNull($dto->busca);
        $this->assertNull($dto->redecId);
        $this->assertNull($dto->macrorregiao);
        $this->assertNull($dto->pendencia);
        $this->assertSame(20, $dto->perPage);
    }

    public function test_from_request_le_busca_redec_e_macrorregiao(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', [
            'busca' => '  Ouro Preto  ',
            'redec_id' => '7',
            'macrorregiao' => 'CENTRAL',
        ]));

        $this->assertSame('Ouro Preto', $dto->busca);
        $this->assertSame(7, $dto->redecId);
        $this->assertSame('CENTRAL', $dto->macrorregiao);
    }

    public function test_busca_vazia_ou_so_espaco_vira_null(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['busca' => '   ']));

        $this->assertNull($dto->busca);
    }

    /** @return array<string, array{string}> */
    public static function pendenciasValidas(): array
    {
        return [
            'sem_email' => ['sem_email'],
            'sem_telefone' => ['sem_telefone'],
            'sem_foto' => ['sem_foto'],
        ];
    }

    #[DataProvider('pendenciasValidas')]
    public function test_pendencia_valida_e_aceita(string $valor): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['pendencia' => $valor]));

        $this->assertSame($valor, $dto->pendencia);
    }

    public function test_pendencia_desconhecida_vira_null(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['pendencia' => 'inventada']));

        $this->assertNull($dto->pendencia);
    }

    /** @return array<string, array{string, int}> */
    public static function perPageValidos(): array
    {
        return [
            '20' => ['20', 20],
            '50' => ['50', 50],
            '100' => ['100', 100],
        ];
    }

    #[DataProvider('perPageValidos')]
    public function test_per_page_valido_e_aceito(string $enviado, int $esperado): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['per_page' => $enviado]));

        $this->assertSame($esperado, $dto->perPage);
    }

    public function test_per_page_fora_da_lista_cai_em_vinte(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['per_page' => '999']));

        $this->assertSame(20, $dto->perPage);
    }

    public function test_redec_id_nao_numerico_vira_null(): void
    {
        $dto = PrefeituraFiltroDTO::fromRequest(Request::create('/cedec/prefeituras', 'GET', ['redec_id' => 'abc']));

        $this->assertNull($dto->redecId);
    }

    public function test_to_array_devolve_as_quatro_chaves_snake_case(): void
    {
        $dto = new PrefeituraFiltroDTO(busca: 'Congonhas', redecId: 3, macrorregiao: 'CENTRAL', pendencia: 'sem_email');

        $this->assertSame([
            'busca' => 'Congonhas',
            'redec_id' => 3,
            'macrorregiao' => 'CENTRAL',
            'pendencia' => 'sem_email',
        ], $dto->toArray());
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PrefeituraFiltroDTOTest`
Expected: FAIL — `Class "App\Modules\Cedec\DTOs\PrefeituraFiltroDTO" not found`.

- [ ] **Step 3: Criar o DTO**

Criar `app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\DTOs;

use Illuminate\Http\Request;

/**
 * Filtro da listagem estadual de prefeituras. Imutavel: uma instancia por
 * requisicao, construida em fromRequest() e repassada inteira ao service.
 */
final class PrefeituraFiltroDTO
{
    private const PENDENCIAS_VALIDAS = ['sem_email', 'sem_telefone', 'sem_foto'];

    private const PER_PAGE_VALIDOS = [20, 50, 100];

    public function __construct(
        public ?string $busca = null,
        public ?int $redecId = null,
        public ?string $macrorregiao = null,
        public ?string $pendencia = null,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $pendencia = $request->query('pendencia');
        $perPage = (int) $request->query('per_page', 20);

        return new self(
            busca: self::stringOuNulo($request->query('busca')),
            redecId: self::intOuNulo($request->query('redec_id')),
            macrorregiao: self::stringOuNulo($request->query('macrorregiao')),
            pendencia: in_array($pendencia, self::PENDENCIAS_VALIDAS, true) ? $pendencia : null,
            perPage: in_array($perPage, self::PER_PAGE_VALIDOS, true) ? $perPage : 20,
        );
    }

    /** @return array{busca: ?string, redec_id: ?int, macrorregiao: ?string, pendencia: ?string} */
    public function toArray(): array
    {
        return [
            'busca' => $this->busca,
            'redec_id' => $this->redecId,
            'macrorregiao' => $this->macrorregiao,
            'pendencia' => $this->pendencia,
        ];
    }

    private static function stringOuNulo(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private static function intOuNulo(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return is_numeric($valor) ? (int) $valor : null;
    }
}
```

- [ ] **Step 4: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php
docker exec newsdc_frankenphp_local php artisan test --filter=PrefeituraFiltroDTOTest
```
Expected: PASS, 11 testes (contando os data providers).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php tests/Feature/Cedec/PrefeituraFiltroDTOTest.php
git commit -m "✨ feat(cedec): DTO de filtro da listagem de prefeituras"
```

---

## Task 3: `CedecPrefeituraService` — leitura (listar, estatisticas, obterPorMunicipio, indicadoresMunicipais)

**Files:**
- Create: `app/Modules/Cedec/Services/CedecPrefeituraService.php`
- Test: `tests/Feature/Cedec/CedecPrefeituraServiceListagemTest.php`

**Interfaces:**
- Consumes: `App\Models\Municipio` (existente); `App\Modules\Compdec\Models\Prefeitura` (fase 1, com as 7 colunas); tabela `cedec_municipio` (colunas `Codmundv`, `redec_id`, `macroregiao`, `populacao`, `pop_rural`, `area`, `territorio_desenv`, `distancia_bh`, `qtd_pipa`, `latitude`, `longitude` — migration `2026_03_03_000001_create_cedec_municipio_table.php`); tabela `dec_redecs` (colunas `id`, `sigla` — migration `2026_08_25_100000_create_dec_redecs_table.php`); tabela `media` (Spatie, colunas `model_type`, `model_id`, `collection_name`).
- Produces (assinaturas do contrato secao 4, as quatro de leitura):
  - `listar(PrefeituraFiltroDTO $filtro): \Illuminate\Contracts\Pagination\LengthAwarePaginator` — itens sao `stdClass` com `municipio_id`, `municipio_nome`, `codigo_ibge`, `redec`, `prefeitura_id`, `prefeito_nome`, `email_prefeitura`, `tel_prefeitura`, `tem_foto` (bool).
  - `estatisticas(): array{total: int, sem_email: int, sem_telefone: int, sem_foto: int}`.
  - `obterPorMunicipio(int $municipioId): ?Prefeitura`.
  - `indicadoresMunicipais(int $municipioId): array{populacao: ?float, pop_rural: ?int, area: ?string, macrorregiao: ?string, territorio_desenv: ?string, distancia_bh: ?float, qtd_pipa: ?int, latitude: ?string, longitude: ?string, origem: string}`.
  - Consumidos por: `PrefeituraController` (Task 7), `PrefeituraListaResource` (Task 5, le os campos de `stdClass` que `listar()` devolve).

**Contratos de dado que este service fixa:**

- `listar()` faz LEFT JOIN: municipio sem `compdec_prefeituras` aparece com `prefeitura_id`, `prefeito_nome`, `email_prefeitura`, `tel_prefeitura` e `tem_foto = false`.
- `redec` na linha e `dec_redecs.sigla` (ex.: `"3ª REDEC"`), `null` quando o municipio nao tem `cedec_municipio` correspondente ou a REDEC nao existe em `dec_redecs`.
- Filtro `macrorregiao` compara contra `cedec_municipio.macroregiao` (grafia da coluna real, uma letra r).
- Filtro `pendencia=sem_foto` usa `NOT EXISTS` sobre `media`; os outros dois usam `whereNull` OR `= ''`.
- `indicadoresMunicipais()` de um municipio sem linha em `cedec_municipio` devolve todas as chaves `null`, exceto `origem`, que e sempre a string literal.

- [ ] **Step 1: Criar as pastas**

```bash
mkdir -p app/Modules/Cedec/Services tests/Feature/Cedec
```

- [ ] **Step 2: Escrever o teste que falha**

Criar `tests/Feature/Cedec/CedecPrefeituraServiceListagemTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Cedec\Services\CedecPrefeituraService;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A base e municipios LEFT JOIN compdec_prefeituras: o municipio sem
 * prefeitura tem de aparecer. O banco de teste e o de desenvolvimento
 * (phpunit.xml nao troca a conexao) com as 853 prefeituras reais, entao toda
 * contagem e RELATIVA ao "antes" medido no proprio teste.
 */
class CedecPrefeituraServiceListagemTest extends TestCase
{
    use DatabaseTransactions;

    private function servico(): CedecPrefeituraService
    {
        return app(CedecPrefeituraService::class);
    }

    private function filtro(array $sobrescreve = []): PrefeituraFiltroDTO
    {
        return new PrefeituraFiltroDTO(
            busca: $sobrescreve['busca'] ?? null,
            redecId: $sobrescreve['redecId'] ?? null,
            macrorregiao: $sobrescreve['macrorregiao'] ?? null,
            pendencia: $sobrescreve['pendencia'] ?? null,
            perPage: $sobrescreve['perPage'] ?? 100,
        );
    }

    /** Insere uma linha em cedec_municipio ligada ao municipio pelo Codmundv. */
    private function comCedecMunicipio(Municipio $municipio, array $campos = []): void
    {
        DB::table('cedec_municipio')->insert(array_merge([
            'Codmundv' => $municipio->codigo_ibge,
            'redec_id' => null,
            'macroregiao' => null,
        ], $campos));
    }

    public function test_municipio_sem_prefeitura_aparece_na_listagem(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Municipio Sem Prefeitura Teste']);

        $pagina = $this->servico()->listar($this->filtro(['busca' => 'Municipio Sem Prefeitura Teste']));

        $this->assertCount(1, $pagina->items());
        $linha = $pagina->items()[0];
        $this->assertSame($municipio->id, $linha->municipio_id);
        $this->assertNull($linha->prefeitura_id);
        $this->assertNull($linha->email_prefeitura);
        $this->assertFalse($linha->tem_foto);
    }

    public function test_filtro_busca_por_nome_de_municipio(): void
    {
        Municipio::factory()->create(['nome' => 'Cedecopolis Unica']);
        Municipio::factory()->create(['nome' => 'Outro Municipio Qualquer']);

        $pagina = $this->servico()->listar($this->filtro(['busca' => 'cedecopolis']));

        $this->assertCount(1, $pagina->items());
        $this->assertSame('Cedecopolis Unica', $pagina->items()[0]->municipio_nome);
    }

    public function test_filtro_por_redec_id(): void
    {
        $comRedec = Municipio::factory()->create();
        $this->comCedecMunicipio($comRedec, ['redec_id' => 9001]);

        $semRedec = Municipio::factory()->create();
        $this->comCedecMunicipio($semRedec, ['redec_id' => 9002]);

        $pagina = $this->servico()->listar($this->filtro(['redecId' => 9001]));

        $ids = array_column($pagina->items(), 'municipio_id');
        $this->assertContains($comRedec->id, $ids);
        $this->assertNotContains($semRedec->id, $ids);
    }

    public function test_filtro_por_macrorregiao(): void
    {
        $central = Municipio::factory()->create();
        $this->comCedecMunicipio($central, ['macroregiao' => 'CENTRAL']);

        $sul = Municipio::factory()->create();
        $this->comCedecMunicipio($sul, ['macroregiao' => 'SUL DE MINAS']);

        $pagina = $this->servico()->listar($this->filtro(['macrorregiao' => 'CENTRAL']));

        $ids = array_column($pagina->items(), 'municipio_id');
        $this->assertContains($central->id, $ids);
        $this->assertNotContains($sul->id, $ids);
    }

    public function test_filtro_pendencia_sem_email(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Teste Pendencia Email']);
        Prefeitura::factory()->create(['municipio_id' => $municipio->id, 'email_prefeitura' => null]);

        $comEmail = Municipio::factory()->create(['nome' => 'Teste Com Email']);
        Prefeitura::factory()->create(['municipio_id' => $comEmail->id, 'email_prefeitura' => 'contato@municipio.mg.gov.br']);

        $pagina = $this->servico()->listar($this->filtro(['pendencia' => 'sem_email', 'busca' => 'Teste']));

        $ids = array_column($pagina->items(), 'municipio_id');
        $this->assertContains($municipio->id, $ids);
        $this->assertNotContains($comEmail->id, $ids);
    }

    public function test_filtro_pendencia_sem_telefone(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Teste Pendencia Telefone']);
        Prefeitura::factory()->create(['municipio_id' => $municipio->id, 'tel_prefeitura' => null]);

        $comTelefone = Municipio::factory()->create(['nome' => 'Teste Com Telefone']);
        Prefeitura::factory()->create(['municipio_id' => $comTelefone->id, 'tel_prefeitura' => '(31) 3333-3333']);

        $pagina = $this->servico()->listar($this->filtro(['pendencia' => 'sem_telefone', 'busca' => 'Teste']));

        $ids = array_column($pagina->items(), 'municipio_id');
        $this->assertContains($municipio->id, $ids);
        $this->assertNotContains($comTelefone->id, $ids);
    }

    public function test_filtro_pendencia_sem_foto(): void
    {
        $semFoto = Municipio::factory()->create(['nome' => 'Teste Pendencia Foto']);
        $prefeituraSemFoto = Prefeitura::factory()->create(['municipio_id' => $semFoto->id]);

        $comFoto = Municipio::factory()->create(['nome' => 'Teste Com Foto']);
        $prefeituraComFoto = Prefeitura::factory()->create(['municipio_id' => $comFoto->id]);
        $this->inserirMediaFake($prefeituraComFoto->id);

        $pagina = $this->servico()->listar($this->filtro(['pendencia' => 'sem_foto', 'busca' => 'Teste']));

        $ids = array_column($pagina->items(), 'municipio_id');
        $this->assertContains($semFoto->id, $ids);
        $this->assertNotContains($comFoto->id, $ids);
        $this->assertNotNull($prefeituraSemFoto->id, 'sanidade: a prefeitura sem foto foi mesmo criada.');
    }

    public function test_tem_foto_e_true_quando_a_prefeitura_tem_media(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Teste Com Foto Flag']);
        $prefeitura = Prefeitura::factory()->create(['municipio_id' => $municipio->id]);
        $this->inserirMediaFake($prefeitura->id);

        $pagina = $this->servico()->listar($this->filtro(['busca' => 'Teste Com Foto Flag']));

        $this->assertTrue($pagina->items()[0]->tem_foto);
    }

    public function test_estatisticas_contam_relativo_ao_antes(): void
    {
        $antes = $this->servico()->estatisticas();

        Municipio::factory()->create(); // sem prefeitura: conta em sem_email, sem_telefone e sem_foto

        $depois = $this->servico()->estatisticas();

        $this->assertSame($antes['total'] + 1, $depois['total']);
        $this->assertSame($antes['sem_email'] + 1, $depois['sem_email']);
        $this->assertSame($antes['sem_telefone'] + 1, $depois['sem_telefone']);
        $this->assertSame($antes['sem_foto'] + 1, $depois['sem_foto']);
    }

    public function test_indicadores_municipais_le_de_cedec_municipio(): void
    {
        $municipio = Municipio::factory()->create();
        $this->comCedecMunicipio($municipio, [
            'macroregiao' => 'CENTRAL',
            'territorio_desenv' => 'Metropolitana',
            'populacao' => 12345.0,
            'pop_rural' => 100,
            'area' => '850,5',
            'distancia_bh' => 45.3,
            'qtd_pipa' => 2,
            'latitude' => '-19.9166813',
            'longitude' => '-43.9344931',
        ]);

        $indicadores = $this->servico()->indicadoresMunicipais($municipio->id);

        $this->assertSame('CENTRAL', $indicadores['macrorregiao']);
        $this->assertSame('Metropolitana', $indicadores['territorio_desenv']);
        $this->assertSame(12345.0, $indicadores['populacao']);
        $this->assertSame(100, $indicadores['pop_rural']);
        $this->assertSame('850,5', $indicadores['area']);
        $this->assertSame(45.3, $indicadores['distancia_bh']);
        $this->assertSame(2, $indicadores['qtd_pipa']);
        $this->assertSame('-19.9166813', $indicadores['latitude']);
        $this->assertSame('cedec_municipio (espelho do legado)', $indicadores['origem']);
    }

    public function test_indicadores_municipais_sem_linha_correspondente_devolve_tudo_null(): void
    {
        $municipio = Municipio::factory()->create();

        $indicadores = $this->servico()->indicadoresMunicipais($municipio->id);

        $this->assertNull($indicadores['populacao']);
        $this->assertNull($indicadores['macrorregiao']);
        $this->assertSame('cedec_municipio (espelho do legado)', $indicadores['origem']);
    }

    public function test_obter_por_municipio_devolve_null_quando_nao_tem_prefeitura(): void
    {
        $municipio = Municipio::factory()->create();

        $this->assertNull($this->servico()->obterPorMunicipio($municipio->id));
    }

    public function test_obter_por_municipio_devolve_a_prefeitura(): void
    {
        $municipio = Municipio::factory()->create();
        Prefeitura::factory()->create(['municipio_id' => $municipio->id, 'prefeito_nome' => 'Fulano']);

        $prefeitura = $this->servico()->obterPorMunicipio($municipio->id);

        $this->assertNotNull($prefeitura);
        $this->assertSame('Fulano', $prefeitura->prefeito_nome);
    }

    /** Insere uma linha minima em `media` sem tocar em storage real. */
    private function inserirMediaFake(int $prefeituraId): void
    {
        DB::table('media')->insert([
            'model_type' => Prefeitura::class,
            'model_id' => $prefeituraId,
            'collection_name' => Prefeitura::MEDIA_FOTO_PREFEITO,
            'name' => 'foto-teste',
            'file_name' => 'foto-teste.jpg',
            'disk' => 'compdec',
            'size' => 1024,
            'manipulations' => '{}',
            'custom_properties' => '{}',
            'generated_conversions' => '{}',
            'responsive_images' => '{}',
        ]);
    }
}
```

- [ ] **Step 3: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraServiceListagemTest`
Expected: FAIL — `Class "App\Modules\Cedec\Services\CedecPrefeituraService" not found`.

- [ ] **Step 4: Escrever o service (metodos de leitura)**

Criar `app/Modules/Cedec/Services/CedecPrefeituraService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Services;

use App\Models\Municipio;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Prefeituras vistas pela CEDEC estadual: as linhas de `municipios`, com ou
 * sem `compdec_prefeituras` correspondente.
 *
 * Opera por `municipio_id` -- nao por orgao. `App\Modules\Compdec\Services\PrefeituraService`
 * continua intacto para a aba municipal do Compdec; os dois convergem na
 * mesma linha via updateOrCreate(['municipio_id' => ...]) (ver Task 4).
 */
final class CedecPrefeituraService
{
    /**
     * municipios LEFT JOIN compdec_prefeituras (municipio sem prefeitura
     * tambem aparece) LEFT JOIN cedec_municipio (REDEC, macrorregiao) LEFT
     * JOIN dec_redecs (rotulo da REDEC).
     *
     * A condicao de soft delete do lado direito vai DENTRO do join: num
     * where() ela viraria filtro sobre a tabela da direita e transformaria o
     * LEFT JOIN em INNER, sumindo com todo municipio sem prefeitura.
     */
    private function baseQuery(): Builder
    {
        return DB::table('municipios')
            ->leftJoin('compdec_prefeituras', function (JoinClause $join): void {
                $join->on('compdec_prefeituras.municipio_id', '=', 'municipios.id')
                    ->whereNull('compdec_prefeituras.deleted_at');
            })
            ->leftJoin('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
            ->leftJoin('dec_redecs', 'dec_redecs.id', '=', 'cedec_municipio.redec_id');
    }

    public function listar(PrefeituraFiltroDTO $filtro): LengthAwarePaginator
    {
        $query = $this->baseQuery()
            ->select([
                'municipios.id as municipio_id',
                'municipios.nome as municipio_nome',
                'municipios.codigo_ibge',
                'dec_redecs.sigla as redec',
                'compdec_prefeituras.id as prefeitura_id',
                'compdec_prefeituras.prefeito_nome',
                'compdec_prefeituras.email_prefeitura',
                'compdec_prefeituras.tel_prefeitura',
            ])
            ->orderBy('municipios.nome');

        if ($filtro->busca !== null && trim($filtro->busca) !== '') {
            $query->where('municipios.nome', 'ILIKE', '%'.trim($filtro->busca).'%');
        }

        if ($filtro->redecId !== null) {
            $query->where('cedec_municipio.redec_id', $filtro->redecId);
        }

        if ($filtro->macrorregiao !== null && $filtro->macrorregiao !== '') {
            // Coluna real: `macroregiao` (uma letra r) -- ver Decisao 2 do plano.
            $query->where('cedec_municipio.macroregiao', $filtro->macrorregiao);
        }

        $this->aplicarPendencia($query, $filtro->pendencia);

        $paginador = $query->paginate($filtro->perPage);

        $this->anexarTemFoto($paginador);

        return $paginador;
    }

    /** @return array{total: int, sem_email: int, sem_telefone: int, sem_foto: int} */
    public function estatisticas(): array
    {
        return [
            'total' => $this->baseQuery()->count('municipios.id'),
            'sem_email' => $this->aplicarPendencia($this->baseQuery(), 'sem_email')->count('municipios.id'),
            'sem_telefone' => $this->aplicarPendencia($this->baseQuery(), 'sem_telefone')->count('municipios.id'),
            'sem_foto' => $this->aplicarPendencia($this->baseQuery(), 'sem_foto')->count('municipios.id'),
        ];
    }

    public function obterPorMunicipio(int $municipioId): ?Prefeitura
    {
        return Prefeitura::query()->where('municipio_id', $municipioId)->first();
    }

    /**
     * Indicadores read-only vindos de `municipios` + `cedec_municipio`.
     *
     * A coluna do banco chama `macroregiao` (uma unica letra r) -- e o nome
     * gravado na migration do legado. A chave devolvida aqui, `macrorregiao`,
     * segue a grafia correta usada no resto do dominio Cedec.
     *
     * @return array{populacao: ?float, pop_rural: ?int, area: ?string,
     *   macrorregiao: ?string, territorio_desenv: ?string, distancia_bh: ?float,
     *   qtd_pipa: ?int, latitude: ?string, longitude: ?string, origem: string}
     */
    public function indicadoresMunicipais(int $municipioId): array
    {
        $municipio = Municipio::query()->findOrFail($municipioId);

        $linha = DB::table('cedec_municipio')
            ->where('Codmundv', $municipio->codigo_ibge)
            ->first();

        return [
            'populacao' => $linha?->populacao !== null ? (float) $linha->populacao : null,
            'pop_rural' => $linha?->pop_rural !== null ? (int) $linha->pop_rural : null,
            'area' => $linha?->area,
            'macrorregiao' => $linha?->macroregiao,
            'territorio_desenv' => $linha?->territorio_desenv,
            'distancia_bh' => $linha?->distancia_bh !== null ? (float) $linha->distancia_bh : null,
            'qtd_pipa' => $linha?->qtd_pipa !== null ? (int) $linha->qtd_pipa : null,
            'latitude' => $linha?->latitude,
            'longitude' => $linha?->longitude,
            'origem' => 'cedec_municipio (espelho do legado)',
        ];
    }

    private function aplicarPendencia(Builder $query, ?string $pendencia): Builder
    {
        return match ($pendencia) {
            'sem_email' => $query->where(function (Builder $sub): void {
                $sub->whereNull('compdec_prefeituras.email_prefeitura')
                    ->orWhere('compdec_prefeituras.email_prefeitura', '');
            }),
            'sem_telefone' => $query->where(function (Builder $sub): void {
                $sub->whereNull('compdec_prefeituras.tel_prefeitura')
                    ->orWhere('compdec_prefeituras.tel_prefeitura', '');
            }),
            'sem_foto' => $query->whereRaw(
                'NOT EXISTS (SELECT 1 FROM media WHERE media.model_type = ? AND media.model_id = compdec_prefeituras.id AND media.collection_name = ?)',
                [Prefeitura::class, Prefeitura::MEDIA_FOTO_PREFEITO],
            ),
            default => $query,
        };
    }

    /**
     * Uma segunda query em `media` limitada aos ids da PAGINA atual -- nunca
     * um EXISTS por linha na projecao, o que rodaria ate 100 vezes por
     * listagem.
     */
    private function anexarTemFoto(LengthAwarePaginator $paginador): void
    {
        $idsDaPagina = array_values(array_filter($paginador->getCollection()->pluck('prefeitura_id')->all()));

        $idsComFoto = $idsDaPagina === [] ? [] : Media::query()
            ->where('model_type', Prefeitura::class)
            ->where('collection_name', Prefeitura::MEDIA_FOTO_PREFEITO)
            ->whereIn('model_id', $idsDaPagina)
            ->pluck('model_id')
            ->all();

        $paginador->getCollection()->transform(function (object $linha) use ($idsComFoto): object {
            $linha->tem_foto = $linha->prefeitura_id !== null && in_array($linha->prefeitura_id, $idsComFoto, true);

            return $linha;
        });
    }
}
```

- [ ] **Step 5: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Services/CedecPrefeituraService.php
docker exec newsdc_frankenphp_local php artisan octane:reload
docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraServiceListagemTest
```
Expected: PASS, 13 testes.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Cedec/Services/CedecPrefeituraService.php tests/Feature/Cedec/CedecPrefeituraServiceListagemTest.php
git commit -m "✨ feat(cedec): listagem, estatisticas e indicadores municipais no service"
```

---

## Task 4: `CedecPrefeituraService` — escrita (upsertPorMunicipio, uploadFoto, removerFoto)

**Files:**
- Modify: `app/Modules/Cedec/Services/CedecPrefeituraService.php`
- Test: `tests/Feature/Cedec/CedecPrefeituraServiceUpsertFotoTest.php`

**Interfaces:**
- Consumes: `App\Modules\Compdec\DTOs\PrefeituraDTO` (fase 1, estendido); `Prefeitura::MEDIA_FOTO_PREFEITO`, `registerMediaCollections()`, `registerMediaConversions()` (ja existentes, nao reimplementados); `config('compdec.disk')`.
- Produces: `upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura`, `uploadFoto(int $municipioId, UploadedFile $arquivo): Media`, `removerFoto(int $municipioId): bool`. Consumidos por `PrefeituraController` (Task 7).

**Contrato que este metodo fixa:** `uploadFoto()` cria a linha de `compdec_prefeituras` se ainda nao existir (`firstOrCreate`) — a CEDEC pode enviar a foto de um municipio que nunca teve outro dado preenchido, porque a navegacao e por `Municipio`, nao por `Prefeitura`.

**Nota — correcao de code review aplicada mais tarde (Task 7, Steps 7-10):** a versao
de `upsertPorMunicipio()` escrita nesta task ainda NAO trata `legacy_id`; o payload de
`$dto->toArray()` grava `legacy_id` mesmo quando ele e `null`. Isso so vira um problema
observavel quando existe a rota `cedec.prefeituras.update` para reproduzir o caso via
HTTP (um municipio ja migrado pelo ETL da fase 1, editado pela tela da CEDEC), entao o
teste de regressao e a correcao ficam na Task 7, depois que o controller e as rotas
existirem — nao aqui. Os testes desta task continuam validos sem alteracao: nenhum
deles cria uma prefeitura com `legacy_id` previo antes de chamar `upsertPorMunicipio()`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/CedecPrefeituraServiceUpsertFotoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Cedec\Services\CedecPrefeituraService;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CedecPrefeituraServiceUpsertFotoTest extends TestCase
{
    use DatabaseTransactions;

    private function servico(): CedecPrefeituraService
    {
        return app(CedecPrefeituraService::class);
    }

    public function test_upsert_por_municipio_cria_quando_nao_existe(): void
    {
        $municipio = Municipio::factory()->create();
        $dto = PrefeituraDTO::fromRequest($municipio->id, ['prefeito_nome' => 'Primeira Gestora']);

        $prefeitura = $this->servico()->upsertPorMunicipio($municipio->id, $dto);

        $this->assertSame('Primeira Gestora', $prefeitura->prefeito_nome);
        $this->assertSame(1, Prefeitura::query()->where('municipio_id', $municipio->id)->count());
    }

    public function test_upsert_por_municipio_atualiza_a_mesma_linha(): void
    {
        $municipio = Municipio::factory()->create();
        $primeiro = PrefeituraDTO::fromRequest($municipio->id, ['prefeito_nome' => 'Gestora Um']);
        $segundo = PrefeituraDTO::fromRequest($municipio->id, ['prefeito_nome' => 'Gestora Dois']);

        $criada = $this->servico()->upsertPorMunicipio($municipio->id, $primeiro);
        $atualizada = $this->servico()->upsertPorMunicipio($municipio->id, $segundo);

        $this->assertSame($criada->id, $atualizada->id);
        $this->assertSame('Gestora Dois', $atualizada->fresh()->prefeito_nome);
        $this->assertSame(1, Prefeitura::query()->where('municipio_id', $municipio->id)->count());
    }

    public function test_upload_foto_cria_a_prefeitura_quando_ainda_nao_existe(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();
        $arquivo = UploadedFile::fake()->image('prefeito.jpg', 300, 300)->size(50);

        $media = $this->servico()->uploadFoto($municipio->id, $arquivo);

        $this->assertSame(Prefeitura::MEDIA_FOTO_PREFEITO, $media->collection_name);
        $prefeitura = Prefeitura::query()->where('municipio_id', $municipio->id)->first();
        $this->assertNotNull($prefeitura);
        $this->assertNotNull($prefeitura->fotoPrefeitoUrl);
    }

    public function test_upload_foto_substitui_a_anterior_singlefile(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();
        Prefeitura::factory()->create(['municipio_id' => $municipio->id]);

        $this->servico()->uploadFoto($municipio->id, UploadedFile::fake()->image('a.jpg')->size(50));
        $this->servico()->uploadFoto($municipio->id, UploadedFile::fake()->image('b.jpg')->size(50));

        $prefeitura = Prefeitura::query()->where('municipio_id', $municipio->id)->first();
        $this->assertCount(1, $prefeitura->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }

    public function test_remover_foto_limpa_a_colecao(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();
        $this->servico()->uploadFoto($municipio->id, UploadedFile::fake()->image('c.jpg')->size(50));

        $resultado = $this->servico()->removerFoto($municipio->id);

        $prefeitura = Prefeitura::query()->where('municipio_id', $municipio->id)->first();
        $this->assertTrue($resultado);
        $this->assertCount(0, $prefeitura->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }

    public function test_remover_foto_de_municipio_sem_prefeitura_devolve_false(): void
    {
        $municipio = Municipio::factory()->create();

        $this->assertFalse($this->servico()->removerFoto($municipio->id));
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraServiceUpsertFotoTest`
Expected: FAIL — `Call to undefined method App\Modules\Cedec\Services\CedecPrefeituraService::upsertPorMunicipio()`.

- [ ] **Step 3: Acrescentar os metodos de escrita**

Em `app/Modules/Cedec/Services/CedecPrefeituraService.php`, acrescentar aos `use` do topo:

```php
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use Illuminate\Http\UploadedFile;
```

E, logo apos o metodo `indicadoresMunicipais()` (antes de `aplicarPendencia()`), inserir:

```php
    /** updateOrCreate por municipio_id, em transacao. */
    public function upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($municipioId, $dto): Prefeitura {
            $payload = $dto->toArray();
            $payload['municipio_id'] = $municipioId;

            return Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);
        });
    }

    /**
     * Cria a linha de compdec_prefeituras se ainda nao existir: a CEDEC
     * navega pelos municipios, nao pelas prefeituras, entao pode chegar aqui
     * antes de qualquer outro dado ter sido preenchido.
     */
    public function uploadFoto(int $municipioId, UploadedFile $arquivo): Media
    {
        $prefeitura = Prefeitura::query()->firstOrCreate(['municipio_id' => $municipioId]);

        return $prefeitura
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
    }

    public function removerFoto(int $municipioId): bool
    {
        $prefeitura = Prefeitura::query()->where('municipio_id', $municipioId)->first();

        if ($prefeitura === null) {
            return false;
        }

        $prefeitura->clearMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO);

        return true;
    }

```

- [ ] **Step 4: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Services/CedecPrefeituraService.php
docker exec newsdc_frankenphp_local php artisan octane:reload
docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraServiceUpsertFotoTest
```
Expected: PASS, 6 testes.

- [ ] **Step 5: Rodar tambem a suite de leitura para garantir que nada quebrou**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraService`
Expected: PASS, as duas classes (leitura + escrita).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Cedec/Services/CedecPrefeituraService.php tests/Feature/Cedec/CedecPrefeituraServiceUpsertFotoTest.php
git commit -m "✨ feat(cedec): upsert por municipio e upload/remocao de foto do prefeito"
```

---

## Task 5: `PrefeituraListaResource`

**Files:**
- Create: `app/Modules/Cedec/Resources/PrefeituraListaResource.php`
- Test: `tests/Feature/Cedec/PrefeituraListaResourceTest.php`

**Interfaces:**
- Consumes: `stdClass` no formato que `CedecPrefeituraService::listar()` produz (Task 3): `municipio_id`, `municipio_nome`, `codigo_ibge`, `redec`, `prefeito_nome`, `email_prefeitura`, `tel_prefeitura`, `tem_foto`.
- Produces: forma exata do contrato secao 6 — `{municipio_id: int, municipio_nome: string, codigo_ibge: string, redec: ?string, prefeito_nome: ?string, email_prefeitura: ?string, tel_prefeitura: ?string, tem_foto: bool}`. Consumido por `PrefeituraController::index()` (Task 7) e pela fase 3 (`PrefeituraTable.vue`).

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/PrefeituraListaResourceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Modules\Cedec\Resources\PrefeituraListaResource;
use Tests\TestCase;

class PrefeituraListaResourceTest extends TestCase
{
    public function test_resolve_traduz_a_linha_crua_do_service(): void
    {
        $linha = (object) [
            'municipio_id' => 42,
            'municipio_nome' => 'Ouro Preto',
            'codigo_ibge' => '3146107',
            'redec' => '3ª REDEC',
            'prefeitura_id' => 7,
            'prefeito_nome' => 'Joana Ferreira',
            'email_prefeitura' => 'gabinete@ouropreto.mg.gov.br',
            'tel_prefeitura' => '(31) 3551-0000',
            'tem_foto' => true,
        ];

        $resolvido = (new PrefeituraListaResource($linha))->resolve();

        $this->assertSame([
            'municipio_id' => 42,
            'municipio_nome' => 'Ouro Preto',
            'codigo_ibge' => '3146107',
            'redec' => '3ª REDEC',
            'prefeito_nome' => 'Joana Ferreira',
            'email_prefeitura' => 'gabinete@ouropreto.mg.gov.br',
            'tel_prefeitura' => '(31) 3551-0000',
            'tem_foto' => true,
        ], $resolvido);
    }

    public function test_resolve_com_campos_nulos_de_municipio_sem_prefeitura(): void
    {
        $linha = (object) [
            'municipio_id' => 1,
            'municipio_nome' => 'Municipio Sem Prefeitura',
            'codigo_ibge' => '3100104',
            'redec' => null,
            'prefeitura_id' => null,
            'prefeito_nome' => null,
            'email_prefeitura' => null,
            'tel_prefeitura' => null,
            'tem_foto' => false,
        ];

        $resolvido = (new PrefeituraListaResource($linha))->resolve();

        $this->assertNull($resolvido['redec']);
        $this->assertNull($resolvido['prefeito_nome']);
        $this->assertFalse($resolvido['tem_foto']);
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PrefeituraListaResourceTest`
Expected: FAIL — `Class "App\Modules\Cedec\Resources\PrefeituraListaResource" not found`.

- [ ] **Step 3: Criar o Resource**

Criar `app/Modules/Cedec/Resources/PrefeituraListaResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma linha da listagem estadual de prefeituras.
 *
 * O `resource` que chega aqui e o stdClass que o Query Builder devolve em
 * CedecPrefeituraService::listar() (municipios LEFT JOIN compdec_prefeituras),
 * ja com `tem_foto` calculado -- nao um Model Eloquent. stdClass suporta a
 * mesma sintaxe `$this->campo` que um Model, entao o Resource nao muda.
 */
final class PrefeituraListaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'municipio_id' => (int) $this->municipio_id,
            'municipio_nome' => (string) $this->municipio_nome,
            'codigo_ibge' => (string) $this->codigo_ibge,
            'redec' => $this->redec,
            'prefeito_nome' => $this->prefeito_nome,
            'email_prefeitura' => $this->email_prefeitura,
            'tel_prefeitura' => $this->tel_prefeitura,
            'tem_foto' => (bool) $this->tem_foto,
        ];
    }
}
```

- [ ] **Step 4: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Resources/PrefeituraListaResource.php
docker exec newsdc_frankenphp_local php artisan test --filter=PrefeituraListaResourceTest
```
Expected: PASS, 2 testes.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Cedec/Resources/PrefeituraListaResource.php tests/Feature/Cedec/PrefeituraListaResourceTest.php
git commit -m "✨ feat(cedec): resource da listagem de prefeituras"
```

---

## Task 6: `UpdatePrefeituraRequest`

**Files:**
- Create: `app/Modules/Cedec/Requests/UpdatePrefeituraRequest.php`
- Test: `tests/Feature/Cedec/UpdatePrefeituraRequestTest.php`

**Interfaces:**
- Consumes: nada em tempo de execucao (FormRequest padrao).
- Produces: `authorize(): bool`, `rules(): array`, `messages(): array` — exatamente as regras do contrato secao 6. Consumido por `PrefeituraController::update()` (Task 7).

Este teste cobre so `rules()`/`messages()` (puros, sem HTTP). `authorize()` e testado de ponta a ponta na Task 7, junto com o 403.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/UpdatePrefeituraRequestTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Modules\Cedec\Requests\UpdatePrefeituraRequest;
use Tests\TestCase;

class UpdatePrefeituraRequestTest extends TestCase
{
    public function test_rules_cobre_os_campos_novos_da_fase_1_e_os_antigos(): void
    {
        $regras = (new UpdatePrefeituraRequest())->rules();

        foreach ([
            'prefeito_nome', 'prefeito_partido', 'prefeito_telefone', 'prefeito_celular', 'prefeito_email',
            'email_prefeitura', 'email_prefeitura_2', 'email_prefeitura_3',
            'tel_prefeitura', 'tel_prefeitura_2', 'fax_prefeitura',
            'endereco', 'bairro', 'cep', 'latitude', 'longitude',
            'inss_tem_cobranca', 'inss_aliquota', 'inss_lei_cobranca', 'inss_responsavel',
        ] as $campo) {
            $this->assertArrayHasKey($campo, $regras, "Falta a regra do campo {$campo}.");
        }
    }

    public function test_cep_exige_o_formato_do_contrato(): void
    {
        $regras = (new UpdatePrefeituraRequest())->rules();

        $this->assertContains('regex:/^\d{5}-?\d{3}$/', $regras['cep']);
    }

    public function test_latitude_e_longitude_tem_faixa_geografica(): void
    {
        $regras = (new UpdatePrefeituraRequest())->rules();

        $this->assertContains('between:-90,90', $regras['latitude']);
        $this->assertContains('between:-180,180', $regras['longitude']);
    }

    public function test_mensagem_de_cep_e_a_do_contrato(): void
    {
        $mensagens = (new UpdatePrefeituraRequest())->messages();

        $this->assertSame('CEP deve estar no formato 00000-000 ou 00000000.', $mensagens['cep.regex']);
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=UpdatePrefeituraRequestTest`
Expected: FAIL — `Class "App\Modules\Cedec\Requests\UpdatePrefeituraRequest" not found`.

- [ ] **Step 3: Criar o Request**

Criar `app/Modules/Cedec/Requests/UpdatePrefeituraRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePrefeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cedec.prefeituras.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'prefeito_nome' => ['nullable', 'string', 'max:255'],
            'prefeito_partido' => ['nullable', 'string', 'max:60'],
            'prefeito_telefone' => ['nullable', 'string', 'max:20'],
            'prefeito_celular' => ['nullable', 'string', 'max:20'],
            'prefeito_email' => ['nullable', 'email', 'max:255'],
            'email_prefeitura' => ['nullable', 'email', 'max:255'],
            'email_prefeitura_2' => ['nullable', 'email', 'max:255'],
            'email_prefeitura_3' => ['nullable', 'email', 'max:255'],
            'tel_prefeitura' => ['nullable', 'string', 'max:20'],
            'tel_prefeitura_2' => ['nullable', 'string', 'max:20'],
            'fax_prefeitura' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cep' => ['nullable', 'string', 'max:10', 'regex:/^\d{5}-?\d{3}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'inss_tem_cobranca' => ['nullable', 'boolean'],
            'inss_aliquota' => ['nullable', 'numeric', 'between:0,100'],
            'inss_lei_cobranca' => ['nullable', 'string', 'max:120'],
            'inss_responsavel' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cep.regex' => 'CEP deve estar no formato 00000-000 ou 00000000.',
        ];
    }
}
```

- [ ] **Step 4: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Requests/UpdatePrefeituraRequest.php
docker exec newsdc_frankenphp_local php artisan test --filter=UpdatePrefeituraRequestTest
```
Expected: PASS, 4 testes.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Cedec/Requests/UpdatePrefeituraRequest.php tests/Feature/Cedec/UpdatePrefeituraRequestTest.php
git commit -m "✨ feat(cedec): validacao do update de prefeitura"
```

---

## Task 7: `PrefeituraController`, rotas e testes de autorizacao/HTTP

**Correcao de code review (2026-09-05) — `update()` nao pode apagar `legacy_id`.**
`PrefeituraController::update()` monta o `PrefeituraDTO` so com `$request->validated()`.
`UpdatePrefeituraRequest::rules()` (Task 6) nao inclui `legacy_id` — corretamente, nao e
campo de formulario da CEDEC — entao `PrefeituraDTO::fromRequest()` devolve `legacyId:
null`, e `PrefeituraDTO::toArray()` (fase 1) SEMPRE emite a chave `legacy_id` no array.
Sem cuidado extra, o `updateOrCreate()` de `upsertPorMunicipio()` (Task 4) grava esse
`null` por cima do valor existente — a coluna e a rastreabilidade do registro de origem
do ETL da fase 1, com indice proprio na migration (secao 1 do contrato). No primeiro
save que um usuario da CEDEC fizer num municipio ja migrado, `legacy_id` vai a NULL em
silencio, sem nada falhar ou avisar.

**Correcao:** em `CedecPrefeituraService::upsertPorMunicipio()` (Task 4), remover a
chave `legacy_id` do payload quando `$dto->legacyId` for `null`, antes do
`updateOrCreate()`. Isso preserva o valor ja gravado (a coluna simplesmente nao entra no
`UPDATE`/`INSERT`) e nao interfere em nenhum consumidor que venha a passar um
`legacyId` de verdade — o ETL da fase 1, alias, nem usa este metodo:
`PrefeituraService::migrarLegado()` (Compdec) monta o proprio array e chama
`Prefeitura::query()->updateOrCreate()` diretamente (ver
`docs/superpowers/plans/2026-09-04-cedec-fase1-dados-etl.md`, Task 6), sem passar por
`CedecPrefeituraService`. A correcao entra nos Steps 7-10 abaixo, depois que a rota
`cedec.prefeituras.update` existir (o teste de regressao precisa dela), e fecha no MESMO
commit desta task (Step 11).

**Achado adjacente no Compdec — mesmo defeito, FORA de escopo, nao corrigido aqui.** A
aba do Compdec tem o defeito identico: `App\Modules\Compdec\Controllers\
PrefeituraController::upsert()` (`SDC/app/Modules/Compdec/Controllers/
PrefeituraController.php:42`) monta o DTO com `PrefeituraDTO::fromRequest($orgao->
municipio_id, $request->validated())` — `UpsertPrefeituraRequest` tambem nao valida
`legacy_id` — e repassa para `PrefeituraService::upsertPorOrgao()`
(`SDC/app/Modules/Compdec/Services/PrefeituraService.php:44-50`), que monta
`$payload = $dto->toArray(); ...; return Prefeitura::updateOrCreate(['municipio_id' =>
$orgao->municipio_id], $payload);` sem nenhum tratamento de `legacy_id` nulo. Salvar
pela tela do Compdec tambem apaga `legacy_id` em silencio. Registrado aqui como
pendencia — corrigir e escopo de quem tocar `PrefeituraService::upsertPorOrgao()` ou de
uma fase de manutencao do Compdec, nao desta fase do Cedec.

**Files:**
- Create: `app/Modules/Cedec/Controllers/PrefeituraController.php`
- Create: `routes/modules/cedec.php`
- Modify: `routes/web.php`
- Modify: `app/Modules/Cedec/Services/CedecPrefeituraService.php` (Steps 7-10: correcao do `legacy_id`)
- Test: `tests/Feature/Cedec/CedecPrefeituraControllerTest.php`

**Interfaces:**
- Consumes: `CedecPrefeituraService` (Tasks 3-4), `UpdatePrefeituraRequest` (Task 6), `PrefeituraListaResource` (Task 5), `Macrorregiao::opcoes()` (Task 1), `PrefeituraDTO::fromRequest()` (fase 1), `App\Models\Municipio` (binding implicito), `App\Modules\Decretacoes\Services\RedecService::toSelectOptions()` (decisao 1 deste plano).
- Produces: rotas nomeadas `cedec.prefeituras.index`, `.edit`, `.update`, `.foto.upload`, `.foto.destroy` (as 5 desta fase; a fase 5 acrescenta `cedec.contatos.*` no MESMO grupo `Route::prefix('cedec')->name('cedec.')`). Pagina Inertia `Cedec/Prefeituras/Index` com as props `prefeituras`, `filtros`, `estatisticas`, `redecs`, `macrorregioes` (secao 8 do contrato); pagina `Cedec/Prefeituras/Edit` com `municipio`, `prefeitura`, `indicadores`.

**Regras que este controller fixa:**

- `index()`: `estatisticas`, `redecs` e `macrorregioes` sao props FECHADAS EM CLOSURE — numa visita parcial `only: ['prefeituras', 'filtros']` (que a fase 3 usa a cada troca de filtro), o Inertia nunca invoca esses callbacks, entao os agregados sobre as centenas de municipios nao sao recalculados a cada tecla.
- `prefeituras` e o `LengthAwarePaginator` inteiro (nao `Resource::collection(...)->response()`): `toArray()` do paginador ja produz o formato achatado `{data, current_page, last_page, per_page, total, from, to, ...}` que o contrato exige, bastando transformar a `Collection` interna para a forma do Resource antes.
- `edit()` funciona para municipio SEM prefeitura (`prefeitura: null` na prop).
- `uploadFoto()` valida mime (`jpeg,png,webp`) e tamanho (`config('compdec.upload_limits.foto_prefeito')`), do mesmo jeito que `Compdec\Controllers\PrefeituraController::uploadFoto()` ja faz.
- `update()` **nunca** altera `legacy_id`, nem para `null` nem para outro valor: a coluna e escrita SOMENTE pelo ETL da fase 1, nunca pela tela da CEDEC (ver correcao de code review acima).

**Armadilha de teste apurada no vendor:** `Inertia\Testing\AssertableInertia::component()` verifica no DISCO se o arquivo Vue existe (`config('inertia.testing.ensure_pages_exist')` = `true` neste projeto — `config/inertia.php:39`) e falha o teste com "Inertia page component file [...] does not exist" mesmo quando a rota e o controller estao corretos. Como as paginas `Cedec/Prefeituras/Index.vue` e `Edit.vue` so nascem nas fases 3 e 4, todo teste desta fase que chama `->component(...)` PRECISA passar `false` como segundo argumento (`->component('Cedec/Prefeituras/Index', false)`) para pular essa checagem — e o parametro `$shouldExist` que o proprio pacote `inertiajs/inertia-laravel` expoe para este caso exato (`vendor/inertiajs/inertia-laravel/src/Testing/AssertableInertia.php:45-54`).

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/CedecPrefeituraControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CedecPrefeituraControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = ['cedec.prefeituras.view', 'cedec.prefeituras.edit'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
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

    public function test_index_sem_a_permissao_de_view_devolve_403(): void
    {
        $this->actingAs($this->usuario([]))
            ->get(route('cedec.prefeituras.index'))
            ->assertForbidden();
    }

    public function test_update_sem_a_permissao_de_edit_devolve_403(): void
    {
        $municipio = Municipio::factory()->create();

        $this->actingAs($this->usuario(['cedec.prefeituras.view']))
            ->put(route('cedec.prefeituras.update', $municipio->id), ['prefeito_nome' => 'X'])
            ->assertForbidden();
    }

    public function test_index_renderiza_com_as_props_do_contrato(): void
    {
        Municipio::factory()->create(['nome' => 'Teste Controller Index']);

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.prefeituras.index', ['busca' => 'Teste Controller Index']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // `false`: as paginas Vue so nascem nas fases 3/4. Sem isso,
                // ->component() falha com "Inertia page component file [...]
                // does not exist" mesmo com a rota e o controller corretos --
                // e o comportamento de inertiajs/inertia-laravel (ver
                // AssertableInertia::component(), parametro $shouldExist).
                ->component('Cedec/Prefeituras/Index', false)
                ->has('prefeituras.data', 1)
                ->where('prefeituras.data.0.municipio_nome', 'Teste Controller Index')
                ->has('prefeituras.current_page')
                ->has('prefeituras.last_page')
                ->has('prefeituras.per_page')
                ->has('prefeituras.total')
                ->has('filtros')
                ->where('filtros.busca', 'Teste Controller Index')
                ->has('estatisticas.total')
                ->has('redecs')
                ->has('macrorregioes', 10)
            );
    }

    public function test_edit_funciona_para_municipio_sem_prefeitura(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Municipio Edit Sem Prefeitura']);

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.prefeituras.edit', $municipio->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cedec/Prefeituras/Edit', false)
                ->where('municipio.nome', 'Municipio Edit Sem Prefeitura')
                ->where('prefeitura', null)
                ->has('indicadores.origem')
            );
    }

    public function test_edit_traz_a_prefeitura_existente(): void
    {
        $municipio = Municipio::factory()->create();
        Prefeitura::factory()->create(['municipio_id' => $municipio->id, 'prefeito_nome' => 'Gestor Atual']);

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->get(route('cedec.prefeituras.edit', $municipio->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('prefeitura.prefeito_nome', 'Gestor Atual')
            );
    }

    public function test_update_valido_persiste_e_redireciona(): void
    {
        $municipio = Municipio::factory()->create();

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->put(route('cedec.prefeituras.update', $municipio->id), [
                'prefeito_nome' => 'Nova Gestora',
                'email_prefeitura' => 'gabinete@municipio.mg.gov.br',
                'cep' => '35400-000',
                'latitude' => '-20.3856000',
                'longitude' => '-43.5035000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('compdec_prefeituras', [
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Nova Gestora',
            'email_prefeitura' => 'gabinete@municipio.mg.gov.br',
        ]);
    }

    public function test_update_por_municipio_atualiza_a_mesma_linha_em_chamadas_sucessivas(): void
    {
        $municipio = Municipio::factory()->create();
        $usuario = $this->usuario(self::PERMISSOES);

        $this->actingAs($usuario)->put(route('cedec.prefeituras.update', $municipio->id), ['prefeito_nome' => 'Primeiro']);
        $this->actingAs($usuario)->put(route('cedec.prefeituras.update', $municipio->id), ['prefeito_nome' => 'Segundo']);

        $this->assertSame(1, Prefeitura::query()->where('municipio_id', $municipio->id)->count());
        $this->assertDatabaseHas('compdec_prefeituras', ['municipio_id' => $municipio->id, 'prefeito_nome' => 'Segundo']);
    }

    public function test_update_invalido_devolve_erro_por_campo(): void
    {
        $municipio = Municipio::factory()->create();

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->from(route('cedec.prefeituras.edit', $municipio->id))
            ->put(route('cedec.prefeituras.update', $municipio->id), [
                'cep' => '3540-00',
                'email_prefeitura' => 'gabinete sem arroba',
                'latitude' => '-120.5',
            ])
            ->assertSessionHasErrors(['cep', 'email_prefeitura', 'latitude']);

        $this->assertDatabaseMissing('compdec_prefeituras', ['municipio_id' => $municipio->id]);
    }

    public function test_upload_foto_com_mime_invalido_e_rejeitado(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->post(route('cedec.prefeituras.foto.upload', $municipio->id), [
                'foto' => UploadedFile::fake()->create('documento.pdf', 50, 'application/pdf'),
            ])
            ->assertSessionHasErrors('foto');
    }

    public function test_upload_foto_acima_do_limite_e_rejeitado(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->post(route('cedec.prefeituras.foto.upload', $municipio->id), [
                'foto' => UploadedFile::fake()->image('grande.jpg')->size(500),
            ])
            ->assertSessionHasErrors('foto');
    }

    public function test_upload_e_remocao_de_foto_felizes(): void
    {
        Storage::fake('compdec');
        $municipio = Municipio::factory()->create();
        $usuario = $this->usuario(self::PERMISSOES);

        $this->actingAs($usuario)
            ->post(route('cedec.prefeituras.foto.upload', $municipio->id), [
                'foto' => UploadedFile::fake()->image('prefeito.jpg')->size(50),
            ])
            ->assertRedirect();

        $prefeitura = Prefeitura::query()->where('municipio_id', $municipio->id)->firstOrFail();
        $this->assertCount(1, $prefeitura->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));

        $this->actingAs($usuario)
            ->delete(route('cedec.prefeituras.foto.destroy', $municipio->id))
            ->assertRedirect();

        $this->assertCount(0, $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraControllerTest`
Expected: FAIL — `Route [cedec.prefeituras.index] not defined.`

- [ ] **Step 3: Escrever o controller**

Criar `app/Modules/Cedec/Controllers/PrefeituraController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Cedec\Enums\Macrorregiao;
use App\Modules\Cedec\Requests\UpdatePrefeituraRequest;
use App\Modules\Cedec\Resources\PrefeituraListaResource;
use App\Modules\Cedec\Services\CedecPrefeituraService;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela estadual de cadastro de prefeituras (CEDEC). A navegacao e por
 * Municipio, nao por Prefeitura: os 853 municipios aparecem, inclusive os que
 * ainda nao tem linha em compdec_prefeituras.
 */
final class PrefeituraController extends Controller
{
    public function __construct(private readonly CedecPrefeituraService $service) {}

    public function index(Request $request): Response
    {
        $filtro = PrefeituraFiltroDTO::fromRequest($request);

        return Inertia::render('Cedec/Prefeituras/Index', [
            'prefeituras' => $this->paraResource($this->service->listar($filtro)),
            'filtros' => $filtro->toArray(),
            // Fechado em closure: numa visita PARCIAL (only: ['prefeituras','filtros'],
            // que a fase 3 usa a cada troca de filtro) o Inertia nunca invoca este
            // callback, entao os agregados sobre todos os municipios nao sao
            // recalculados a cada tecla.
            'estatisticas' => fn () => $this->service->estatisticas(),
            'redecs' => fn () => $this->redecsParaSelect(),
            'macrorregioes' => fn () => Macrorregiao::opcoes(),
        ]);
    }

    public function edit(Municipio $municipio): Response
    {
        $prefeitura = $this->service->obterPorMunicipio($municipio->id);

        return Inertia::render('Cedec/Prefeituras/Edit', [
            'municipio' => [
                'id' => $municipio->id,
                'nome' => $municipio->nome,
                'codigo_ibge' => $municipio->codigo_ibge,
                'uf' => $municipio->uf,
            ],
            'prefeitura' => $prefeitura === null ? null : array_merge(
                $prefeitura->toArray(),
                ['foto_prefeito_url' => $prefeitura->fotoPrefeitoUrl],
            ),
            'indicadores' => $this->service->indicadoresMunicipais($municipio->id),
        ]);
    }

    public function update(UpdatePrefeituraRequest $request, Municipio $municipio): RedirectResponse
    {
        $this->service->upsertPorMunicipio(
            $municipio->id,
            PrefeituraDTO::fromRequest($municipio->id, $request->validated()),
        );

        return back()->with('success', 'Prefeitura atualizada.');
    }

    public function uploadFoto(Request $request, Municipio $municipio): RedirectResponse
    {
        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:'.(int) (config('compdec.upload_limits.foto_prefeito', 307200) / 1024),
            ],
        ]);

        $this->service->uploadFoto($municipio->id, $request->file('foto'));

        return back()->with('success', 'Foto do prefeito atualizada.');
    }

    public function removerFoto(Municipio $municipio): RedirectResponse
    {
        $this->service->removerFoto($municipio->id);

        return back()->with('success', 'Foto do prefeito removida.');
    }

    /**
     * `LengthAwarePaginator::toArray()` ja produz {data, current_page,
     * last_page, per_page, total, from, to, ...} -- o formato ACHATADO que o
     * contrato exige. So falta traduzir cada linha crua para a forma do
     * Resource antes de devolver.
     */
    private function paraResource(LengthAwarePaginator $paginador): LengthAwarePaginator
    {
        $paginador->getCollection()->transform(
            fn (object $linha): array => (new PrefeituraListaResource($linha))->resolve(),
        );

        return $paginador;
    }

    /**
     * Catalogo de REDECs do modulo Decretacoes, mapeado para {value,label}.
     *
     * Decisao registrada no plano da fase 2: o Cedec nao duplica o catalogo
     * de REDECs -- reusa `RedecService`, que ja mantem `dec_redecs` cacheado,
     * do mesmo jeito que outros modulos reusam `Municipio::catalogo()`.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function redecsParaSelect(): array
    {
        return array_map(
            static fn (array $redec): array => ['value' => $redec['id'], 'label' => $redec['label']],
            RedecService::toSelectOptions(),
        );
    }
}
```

- [ ] **Step 4: Criar as rotas**

Criar `routes/modules/cedec.php`:

```php
<?php

declare(strict_types=1);

use App\Modules\Cedec\Controllers\PrefeituraController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo CEDEC - Cadastro estadual de prefeituras
|--------------------------------------------------------------------------
| Permissoes em config/permissions.php (bloco CEDEC).
|
| Armadilha obrigatoria: Route::model() e GLOBAL neste projeto e ja causou
| 404 no Pmda e no PlanCon (ver routes/modules/pmda.php:20-39 e
| routes/modules/compdec.php:29-43). NAO registrar Route::model('municipio', ...)
| aqui -- {municipio} ja e usado por routes/modules/cisterna.php:100 sem
| binder explicito, e o binding implicito por type-hint App\Models\Municipio
| ja resolve nos dois lugares.
|
| A fase 5 acrescenta o sub-grupo `contatos` DENTRO deste mesmo grupo
| `cedec.`, ao final, antes do fechamento.
*/
Route::prefix('cedec')->name('cedec.')->group(function () {
    Route::prefix('prefeituras')->name('prefeituras.')->group(function () {
        Route::get('/', [PrefeituraController::class, 'index'])
            ->name('index')->middleware('can:cedec.prefeituras.view');
        Route::get('/{municipio}/edit', [PrefeituraController::class, 'edit'])
            ->name('edit')->middleware('can:cedec.prefeituras.view');
        Route::put('/{municipio}', [PrefeituraController::class, 'update'])
            ->name('update')->middleware('can:cedec.prefeituras.edit');
        Route::post('/{municipio}/foto', [PrefeituraController::class, 'uploadFoto'])
            ->name('foto.upload')->middleware('can:cedec.prefeituras.edit');
        Route::delete('/{municipio}/foto', [PrefeituraController::class, 'removerFoto'])
            ->name('foto.destroy')->middleware('can:cedec.prefeituras.edit');
    });
});
```

- [ ] **Step 5: Carregar o arquivo de rotas em `routes/web.php`**

Em `routes/web.php`, logo apos a linha `require __DIR__ . '/modules/compdec.php';` (dentro do bloco `Route::middleware('auth')->group(function () { ... })`), acrescentar:

```php
    // Modulo: Cedec (cadastro estadual de prefeituras)
    require __DIR__ . '/modules/cedec.php';
```

- [ ] **Step 6: Rodar o teste para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Controllers/PrefeituraController.php
docker exec newsdc_frankenphp_local php -l /app/routes/modules/cedec.php
docker exec newsdc_frankenphp_local php artisan octane:reload
docker exec newsdc_frankenphp_local php artisan route:list --name=cedec
docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraControllerTest
```
Expected: `route:list` lista as 5 rotas `cedec.prefeituras.*`; os testes PASSAM (12 metodos). Como as permissoes `cedec.*` ainda nao existem em `config/permissions.php` (Task 8), os testes de 403 e os de sucesso funcionam igual: `Permission::firstOrCreate()` no `setUp()` cria a permissao direto no banco de teste, sem depender do config.

- [ ] **Step 7: Escrever o teste que prova a correcao de code review (legacy_id)**

So agora a rota `cedec.prefeituras.update` existe, entao da para reproduzir o achado de
ponta a ponta. Em `tests/Feature/Cedec/CedecPrefeituraControllerTest.php`, acrescentar o
metodo abaixo ao final da classe (antes do `}` de fechamento):

```php
    public function test_update_pela_cedec_nao_apaga_o_legacy_id_da_prefeitura(): void
    {
        $municipio = Municipio::factory()->create();
        $prefeitura = Prefeitura::factory()->create([
            'municipio_id' => $municipio->id,
            'legacy_id' => 9001,
        ]);

        $this->actingAs($this->usuario(self::PERMISSOES))
            ->put(route('cedec.prefeituras.update', $municipio->id), [
                'prefeito_nome' => 'Gestora Pos Etl',
            ])
            ->assertRedirect();

        $this->assertSame(9001, $prefeitura->fresh()->legacy_id);
    }
```

`legacy_id` e preenchido direto na fixture (simulando um municipio ja migrado pelo ETL
da fase 1); o payload do `PUT` NAO inclui `legacy_id` (a tela da CEDEC nunca manda esse
campo — `UpdatePrefeituraRequest::rules()` nem o valida). A asserção confere que o valor
original sobrevive ao update.

- [ ] **Step 8: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=test_update_pela_cedec_nao_apaga_o_legacy_id_da_prefeitura`
Expected: FAIL — `Failed asserting that null matches expected 9001.` `upsertPorMunicipio()`
(Task 4) monta `$payload = $dto->toArray()`, que sempre traz `'legacy_id' => null`
(porque `$dto->legacyId` e null), e o `updateOrCreate()` grava esse `null` por cima do
`9001` da fixture.

- [ ] **Step 9: Implementar a correcao em `CedecPrefeituraService::upsertPorMunicipio()`**

Em `app/Modules/Cedec/Services/CedecPrefeituraService.php` (criado na Task 3, com o
metodo `upsertPorMunicipio()` acrescentado na Task 4), trocar:

```php
    public function upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($municipioId, $dto): Prefeitura {
            $payload = $dto->toArray();
            $payload['municipio_id'] = $municipioId;

            return Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);
        });
    }
```

por:

```php
    /**
     * updateOrCreate por municipio_id, em transacao.
     *
     * legacy_id NUNCA e sobrescrito por este caminho quando o DTO nao traz um
     * valor: UpdatePrefeituraRequest (tela da CEDEC) nao valida legacy_id --
     * corretamente, nao e campo de formulario -- entao PrefeituraDTO::toArray()
     * sempre emite a chave com null. Sem este cuidado, o primeiro save da
     * CEDEC apagaria em silencio a rastreabilidade de origem do ETL (fase 1).
     * Quando o DTO TRAZ um legacy_id de verdade, a chave permanece no payload
     * normalmente -- este metodo nao e usado pelo ETL (que grava direto via
     * Compdec\Services\PrefeituraService::migrarLegado()), mas fica correto
     * para qualquer chamador futuro que precise gravar o campo.
     */
    public function upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($municipioId, $dto): Prefeitura {
            $payload = $dto->toArray();
            $payload['municipio_id'] = $municipioId;

            if ($dto->legacyId === null) {
                unset($payload['legacy_id']);
            }

            return Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);
        });
    }
```

- [ ] **Step 10: Rodar os testes para ver passar**

Run:
```bash
docker exec newsdc_frankenphp_local php -l /app/app/Modules/Cedec/Services/CedecPrefeituraService.php
docker exec newsdc_frankenphp_local php artisan octane:reload
docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraControllerTest
docker exec newsdc_frankenphp_local php artisan test --filter=CedecPrefeituraService
```
Expected: PASS em toda a `CedecPrefeituraControllerTest` (inclui o teste novo do Step 7)
e em toda a `CedecPrefeituraServiceUpsertFotoTest`/`CedecPrefeituraServiceListagemTest` —
a mudanca so afeta o caso `legacyId === null`, que e exatamente o caso que os testes de
upsert da Task 4 ja cobrem (nenhum deles cria prefeitura com `legacy_id` previo, entao
`unset()` e um no-op transparente para eles).

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Cedec/Controllers/PrefeituraController.php \
        app/Modules/Cedec/Services/CedecPrefeituraService.php \
        routes/modules/cedec.php \
        routes/web.php \
        tests/Feature/Cedec/CedecPrefeituraControllerTest.php
git commit -m "$(cat <<'EOF'
✨ feat(cedec): controller e rotas de prefeituras

Inclui a correcao de code review: upsertPorMunicipio() nao apaga legacy_id quando
o DTO da tela da CEDEC chega sem esse campo (UpdatePrefeituraRequest nao o valida
de proposito). Sem isso, o primeiro save de um municipio ja migrado pelo ETL da
fase 1 zerava a rastreabilidade de origem em silencio.
EOF
)"
```

---

## Task 8: Permissoes em `config/permissions.php`

**Files:**
- Modify: `config/permissions.php`
- Test: `tests/Feature/Cedec/PermissoesCedecTest.php`

**Interfaces:**
- Consumes: estrutura existente de `config/permissions.php` (`modules`, `role_permissions`).
- Produces: quatro slugs — `cedec.prefeituras.view`, `cedec.prefeituras.edit`, `cedec.prefeituras.export`, `cedec.contatos.view` — declarados no bloco `modules.CEDEC` e concedidos aos perfis CEDEC estaduais em `role_permissions`.

**Correcao ao contrato (secao 7) apurada no codigo real:** o contrato diz que os slugs "entram em tres lugares": o bloco `modules`, "a lista achatada de permissoes" e `role_permissions`. Investigando `database/seeders/RolesAndPermissionsSeeder.php:157-171` (`getAllPermissionSlugs()`), **a lista achatada NAO existe como array escrito a mao no arquivo** — ela e derivada em tempo de execucao, iterando `config('permissions.modules')`. Editar o bloco `modules` ja basta; nao ha um terceiro array para tocar. Este plano edita `config/permissions.php` em DOIS lugares (bloco `modules` e tres arrays de `role_permissions`), nao tres.

**Perfis CEDEC estaduais (decisao, sem correspondencia 1:1 no contrato):** os cargos deste projeto (`admin`, `manager`, `analyst`, `operator`, `viewer`, `user`, `citizen`) sao niveis de hierarquia, nao papeis "municipal" vs. "estadual". Seguindo o MESMO padrao ja usado para `compdec.prefeitura.*` (concedido a `admin` via `compdec.*`, e explicitamente a `manager` e `analyst`, mas NAO a `operator`/`viewer`/`user`) e para as abilities de analise CEDEC do PMDA (`pmda.analise.*`, tambem so em `manager`), os quatro slugs do Cedec vao para `admin` (via `cedec.*` na lista de wildcards), `manager` e `analyst`. `operator`, `viewer`, `user` e `citizen` NAO recebem nenhum — e a leitura mais proxima de "perfis municipais nao recebem" que a estrutura atual permite expressar.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cedec/PermissoesCedecTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Testa o ARQUIVO de configuracao diretamente -- sem banco, sem HTTP. E a
 * fonte que RolesAndPermissionsSeeder::seedPermissions() le para criar as
 * linhas de Permission (ver getAllPermissionSlugs(), que apenas acha o
 * `modules` config; nao existe uma "lista achatada" escrita a mao no arquivo).
 */
class PermissoesCedecTest extends TestCase
{
    private const SLUGS = [
        'cedec.prefeituras.view',
        'cedec.prefeituras.edit',
        'cedec.prefeituras.export',
        'cedec.contatos.view',
    ];

    public function test_bloco_modules_declara_os_quatro_slugs_do_cedec(): void
    {
        $modulos = config('permissions.modules');

        $this->assertArrayHasKey('CEDEC', $modulos);
        $this->assertSame([
            'view' => 'cedec.prefeituras.view',
            'edit' => 'cedec.prefeituras.edit',
            'export' => 'cedec.prefeituras.export',
        ], $modulos['CEDEC']['Prefeituras']);
        $this->assertSame(['view' => 'cedec.contatos.view'], $modulos['CEDEC']['Contatos']);
    }

    /** @return array<string, array{string}> */
    public static function perfisComOsQuatroSlugs(): array
    {
        return [
            'manager' => ['manager'],
            'analyst' => ['analyst'],
        ];
    }

    #[DataProvider('perfisComOsQuatroSlugs')]
    public function test_perfis_cedec_estaduais_recebem_os_quatro_slugs(string $perfil): void
    {
        $permissoes = config("permissions.role_permissions.{$perfil}");

        foreach (self::SLUGS as $slug) {
            $this->assertContains($slug, $permissoes, "O perfil {$perfil} deveria ter {$slug}.");
        }
    }

    public function test_admin_recebe_via_wildcard(): void
    {
        $this->assertContains('cedec.*', config('permissions.role_permissions.admin'));
    }

    /** @return array<string, array{string}> */
    public static function perfisMunicipaisSemAcesso(): array
    {
        return [
            'operator' => ['operator'],
            'viewer' => ['viewer'],
            'user' => ['user'],
            'citizen' => ['citizen'],
        ];
    }

    #[DataProvider('perfisMunicipaisSemAcesso')]
    public function test_perfis_municipais_nao_recebem_nenhum_slug_do_cedec(string $perfil): void
    {
        $permissoes = config("permissions.role_permissions.{$perfil}", []);

        foreach (self::SLUGS as $slug) {
            $this->assertNotContains($slug, $permissoes, "O perfil {$perfil} NAO deveria ter {$slug}.");
        }
        $this->assertNotContains('cedec.*', $permissoes);
    }
}
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PermissoesCedecTest`
Expected: FAIL — `Failed asserting that an array has the key 'CEDEC'`.

- [ ] **Step 3: Acrescentar o bloco `CEDEC` em `modules`**

Em `config/permissions.php`, logo apos o fechamento do bloco `'COMPDEC' => [ ... ],` (antes de `// Painel estadual de cobertura ...` / `'PLANCON' => [`), inserir:

```php
        'CEDEC' => [
            'Prefeituras' => [
                'view' => 'cedec.prefeituras.view',
                'edit' => 'cedec.prefeituras.edit',
                'export' => 'cedec.prefeituras.export',
            ],
            'Contatos' => [
                'view' => 'cedec.contatos.view',
            ],
        ],
```

- [ ] **Step 4: Conceder ao `admin` via wildcard**

Na lista `'admin' => [ ... ]` de `role_permissions`, logo apos `'compdec.*',`, acrescentar:

```php
            'cedec.*',
```

- [ ] **Step 5: Conceder a `manager` e `analyst` explicitamente**

Na lista `'manager' => [ ... ]`, logo apos o bloco `// COMPDEC - sem delete e sem aprovar` (apos `'compdec.usuarios.manage',`), acrescentar:

```php
            // CEDEC - cadastro estadual de prefeituras e relatorios de contato
            'cedec.prefeituras.view',
            'cedec.prefeituras.edit',
            'cedec.prefeituras.export',
            'cedec.contatos.view',
```

Na lista `'analyst' => [ ... ]`, logo apos o bloco COMPDEC (apos `'compdec.plano.download',`), acrescentar o mesmo bloco:

```php
            // CEDEC - cadastro estadual de prefeituras e relatorios de contato
            'cedec.prefeituras.view',
            'cedec.prefeituras.edit',
            'cedec.prefeituras.export',
            'cedec.contatos.view',
```

**Nao tocar** em `operator`, `viewer`, `user` nem `citizen`.

- [ ] **Step 6: Rodar o teste para ver passar**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=PermissoesCedecTest`
Expected: PASS, 8 testes (contando os data providers).

- [ ] **Step 7: RESTART do container (nao octane:reload) e sincronizar as permissoes no banco**

```bash
docker restart newsdc_frankenphp_local
docker exec newsdc_frankenphp_local php artisan db:seed --class=RolesAndPermissionsSeeder
docker exec newsdc_frankenphp_local php artisan route:list --name=cedec
```
Expected: o seeder imprime a sincronizacao sem erro; as rotas `cedec.prefeituras.*` continuam listadas. So depois deste passo um usuario real (nao criado via `Permission::firstOrCreate()` em teste) passa a ter os slugs disponiveis para receber por role.

- [ ] **Step 8: Rodar a suite inteira do Cedec para garantir que nada quebrou com o restart**

Run: `docker exec newsdc_frankenphp_local php artisan test --filter=Cedec`
Expected: PASS em todas as classes das Tasks 1 a 8.

- [ ] **Step 9: Commit**

```bash
git add config/permissions.php tests/Feature/Cedec/PermissoesCedecTest.php
git commit -m "🔒 security(cedec): slugs de permissao do cadastro estadual de prefeituras"
```

---

## Task 9: Item no Sidebar e icone do modulo

**Files:**
- Modify: `resources/js/Components/Sidebar.vue`
- Modify: `resources/js/Support/moduleIcons.js`

**Interfaces:**
- Consumes: `resources/js/Components/NavItem.vue` (prop `icon` com catalogo proprio de nomes — `building` ja existe e e o mesmo usado por "COMPDEC / Orgaos"); `hasPermission()` e `_routes` (padroes ja existentes no Sidebar); `MODULE_ICONS`/`ICONS` de `moduleIcons.js` (`apartment` ja importado e ja esta em `ICONS`, so falta em `MODULE_ICONS`).
- Produces: item "Prefeituras" visivel atras de `cedec.prefeituras.view`; entrada `prefeituras: apartment` em `MODULE_ICONS`, consumida pelas fases 3 e 4 via `moduleIcon('prefeituras')` no `PageHeader`.

Nao ha teste PHPUnit nesta task (e front-end puro). Verificacao por `npm run build` + `grep`.

- [ ] **Step 1: Verificar se `moduleIcons.js` ja tem a entrada (idempotente)**

```bash
grep -n "prefeituras" SDC/resources/js/Support/moduleIcons.js
```

Se ja houver saida (alguma fase seguinte rodou primeiro em paralelo), pule o Step 2.

- [ ] **Step 2: Registrar `prefeituras: apartment` em `MODULE_ICONS`**

Em `resources/js/Support/moduleIcons.js`, dentro do objeto `MODULE_ICONS`, logo apos a linha `orgaos: officeBuilding,`, inserir:

```js
  prefeituras: apartment,
```

- [ ] **Step 3: Adicionar a checagem de rota existente**

Em `resources/js/Components/Sidebar.vue`, dentro do objeto `_routes` (por volta da linha 813, logo apos `hasCompdec: route().has('compdec.index'),`), inserir:

```js
  hasCedec: route().has('cedec.prefeituras.index'),
```

- [ ] **Step 4: Adicionar o computed de permissao**

No bloco de computeds "PRINCIPAL" (por volta da linha 950, logo apos `canSeeDemandas`), inserir:

```js
const canSeeCedecPrefeituras = computed(() => {
  return hasPermission(['cedec.prefeituras.view']);
});
```

- [ ] **Step 5: Adicionar o item de navegacao**

No `<template>`, logo apos o bloco `<!-- COMPDEC / Orgaos -->` / `</NavItem>` (por volta da linha 212), inserir:

```html
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
```

`icon="building"` reusa o mesmo icone que "Orgaos" (COMPDEC) ja usa em `NavItem.vue` — nao existe um icone `apartment` no catalogo PROPRIO do `NavItem` (esse catalogo e independente de `moduleIcons.js`, que serve o `PageHeader`, nao a sidebar).

- [ ] **Step 6: Verificar que compila**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro.

- [ ] **Step 7: Conferir as duas insercoes**

```bash
grep -n "prefeituras: apartment" SDC/resources/js/Support/moduleIcons.js
grep -n "hasCedec\|canSeeCedecPrefeituras\|CEDEC / Prefeituras" SDC/resources/js/Components/Sidebar.vue
```
Expected: as tres linhas aparecem.

- [ ] **Step 8: Commit**

```bash
git add resources/js/Components/Sidebar.vue resources/js/Support/moduleIcons.js
git commit -m "✨ feat(cedec): item de sidebar e icone do modulo"
```

---

## Verificacao final da fase

```bash
docker exec newsdc_frankenphp_local php artisan test --filter=Cedec
docker exec newsdc_frankenphp_local php artisan route:list --name=cedec
cd SDC && npm run build
```

Expected: todas as classes de `Tests\Feature\Cedec` passam; `route:list` lista as 5 rotas `cedec.prefeituras.*`; o build do frontend conclui sem erro (o unico Vue tocado nesta fase e o Sidebar).

---

## Fora do escopo desta fase (nao implemente)

- Qualquer pagina `Pages/Cedec/**` — fases 3, 4 e 5.
- `ContatoRelatorioController`/`ContatoRelatorioService` e as rotas `cedec.contatos.*` — fase 5 (o grupo `Route::prefix('cedec')->name('cedec.')` criado aqui fica aberto para elas).
- Refit de `Organisms/Compdec/PrefeituraForm.vue` — fase 4.
- Migration, `Prefeitura::$fillable`, `PrefeituraDTO` e ETL — fase 1 (assumidos prontos).

---

## Divergencias encontradas em relacao ao contrato (relatar, nao corrigir aqui)

1. **"Lista achatada de permissoes" (contrato secao 7) nao existe como array separado.** `RolesAndPermissionsSeeder::getAllPermissionSlugs()` deriva a lista automaticamente do bloco `modules`. Este plano edita `config/permissions.php` em dois lugares reais (`modules` e `role_permissions`), nao tres.
2. **Fonte do filtro/coluna REDEC nao esta no contrato.** Resolvida reusando `App\Modules\Decretacoes\Services\RedecService` (import cross-modulo, mesmo padrao ja usado para `Compdec\Models\Orgao`/`Prefeitura`). Nenhuma fase posterior (3/4/5) referencia `RedecService` diretamente — elas so consomem a prop `redecs: [{value,label}]`, que e exatamente o que este controller produz, entao a decisao e transparente para elas.
3. **Coluna real e `cedec_municipio.macroregiao`** (uma letra r), enquanto o dominio (enum, DTO, chave de `indicadoresMunicipais()`) usa `macrorregiao`. Documentado no service; nenhuma fase posterior le essa coluna diretamente.
4. **"Perfis CEDEC estaduais" nao tem correspondencia 1:1 nos cargos do sistema** (`admin`/`manager`/`analyst`/`operator`/`viewer`/`user`/`citizen` sao niveis de hierarquia, nao um eixo municipal/estadual). Resolvido replicando o padrao ja usado por `compdec.prefeitura.edit` e `pmda.analise.*`: `admin` + `manager` + `analyst`. Se a intencao real for outra, e decisao de produto a revisitar — nao um defeito de implementacao.
5. **(Code review 2026-09-05) O Compdec tem o mesmo defeito de `legacy_id`, NAO corrigido nesta fase.** `App\Modules\Compdec\Controllers\PrefeituraController::upsert()` (`SDC/app/Modules/Compdec/Controllers/PrefeituraController.php:42`) monta `PrefeituraDTO::fromRequest($orgao->municipio_id, $request->validated())` — `UpsertPrefeituraRequest` tambem nao valida `legacy_id` — e `PrefeituraService::upsertPorOrgao()` (`SDC/app/Modules/Compdec/Services/PrefeituraService.php:44-50`) grava o payload inteiro via `updateOrCreate()` sem nenhum tratamento para `legacy_id` nulo. Salvar pela aba do Compdec tambem apaga `legacy_id` em silencio no primeiro save de um municipio ja migrado. A correcao desta fase (Task 7, Steps 7-10) so cobre `CedecPrefeituraService::upsertPorMunicipio()` — o caminho do Compdec fica como pendencia registrada, fora do escopo desta fase do Cedec.
