# API Swagger do modulo Cisterna (somente leitura) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor os dados do modulo Cisterna (beneficiarios, vistorias, comunidades, lotes, ordens de servico e notificacoes) por uma API REST versionada e documentada em Swagger, somente leitura, autenticada pelo token Bearer emitido na pagina de Permissionamento e recortada pelo territorio do usuario do token.

**Architecture:** Controllers finos em `app/Http/Controllers/Api/V1/Cisterna/` — obrigatoriamente ali, porque `config/l5-swagger.php` -> `paths.annotations` varre **somente** `app/Http/Controllers/Api`. Toda a regra de negocio e reaproveitada do modulo (`app/Modules/Cisterna/`): Models, Services, Resources, Enums, Policies e o value object `PerfilCisterna`. A unica logica nova e uma listagem de vistorias (que a interface web nao tem) e a extracao da regra de recorte territorial, hoje presa num metodo privado, para um lugar unico reutilizavel.

**Tech Stack:** Laravel 11 + Octane/Swoole, PostgreSQL 17, Laravel Sanctum (personal access tokens), `darkaonline/l5-swagger` ^8.6 (anotacoes `@OA\*` do `zircote/swagger-php`), Spatie Permission, Spatie MediaLibrary, PHPUnit.

**Spec:** este plano nao tem spec separado. As decisoes de dominio e as perdas de dado da migracao que o contrato da API precisa expor estao em:
- [`docs/superpowers/notas/2026-08-14-cisterna-arquitetura-db-implantada.md`](../notas/2026-08-14-cisterna-arquitetura-db-implantada.md) — as 10 tabelas, as FKs, os indices parciais e as tres decisoes de modelagem
- [`docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md`](../notas/2026-08-10-cisterna-ddl-legado.md) — o que a migracao perdeu e por que (secao 5)
- [`docs/superpowers/specs/2026-08-10-cisterna-migracao-legado-design.md`](../specs/2026-08-10-cisterna-migracao-legado-design.md) — o desenho da migracao
- [`.claude/skills/api/01 - Swagger/01 - api.md`](../../../../.claude/skills/api/01%20-%20Swagger/01%20-%20api.md) — o padrao oficial de autenticacao e envelope da API

---

## Global Constraints

- **Anotacoes OpenAPI so sao lidas em `app/Http/Controllers/Api`.** Controller de API fora desse diretorio **nao entra** no `api-docs.json`. Confirmado em `config/l5-swagger.php` -> `paths.annotations`.
- **Security scheme:** exclusivamente `security={{"bearerAuth": {}}}`. **NUNCA** reintroduzir o scheme `sanctum` (declarava `bearerFormat: JWT`, incorreto) nem `X-PowerBI-Token` (aposentado por decisao registrada).
- **Envelope:** Laravel API Resources -> `data` + `meta`/`links` nas listagens. Padrao do modulo PAE. **Nao** usar o envelope manual `{"success": true, "data": ...}` do Decretacoes.
- **Somente leitura:** nenhum `POST`, `PUT`, `PATCH` ou `DELETE` neste plano.
- **Sem emoji no codigo** (regra de ouro 2). Sem acento nos comentarios de codigo novo, seguindo o modulo.
- **DRY/SOLID** (regra de ouro 4): nenhuma regra de recorte territorial duplicada. Se dois services precisam da mesma regra, ela mora num lugar so.
- **Teto de pagina 100.** `BeneficiarioService::PORTE_MAXIMO_PAGINA = 100`, `PORTE_PADRAO_PAGINA = 25`. Valem para todos os recursos.
- **Assert por delta, nunca por total absoluto.** O banco de dev tem a migracao real carregada (8.099 beneficiarios, 2.136 vistorias, 27.684 itens). A nota `2026-08-10-cisterna-ddl-legado.md` secao 6.3 registra 7 testes que quebraram exatamente por medirem total absoluto. Usar faixa de id reservada: `99xxxx` para identificador, `9xxxxx` para numero de instalacao.
- **PHP CLI local:** o Artisan roda com o PHP do Laragon 8.3. Dentro do container: `docker exec newsdc_dev_app php artisan ...`.
- **Banco de dev:** container `newsdc_dev_db`, exposto em `127.0.0.1:5434`. Acesso: `docker exec newsdc_dev_db psql -U sdc -d sdc`.
- **Assinaturas publicas existentes nao mudam de forma incompativel.** Parametro novo em service ja consumido pela web entra como **opcional, por ultimo**, com default que preserva o comportamento atual.
- **Nenhuma alteracao de comportamento nas telas web.** O recorte territorial ausente nas telas de comunidades e notificacoes e lacuna pre-existente e fica registrada, nao corrigida aqui.

### Estado medido do banco (base para os asserts e para os exemplos do Swagger)

| Tabela | Linhas |
|---|---|
| `cisterna_beneficiarios` | 8.099 — 6.734 aprovado, 516 duplicado, 469 em_edicao, 190 reprovado, 145 ressalva, 45 desconsiderado |
| `cisterna_vistorias` | 2.136 — 794 fornecedor (793 concluidas, **794 com `numero_instalacao`**), 682 compdec (0 com numero), 660 cedec (0 com numero) |
| `cisterna_itens_conferidos` | 27.684 |
| `cisterna_atendimentos_pipa` | 2.904 |
| `cisterna_comunidades` | 840, em 55 municipios distintos |
| `cisterna_lotes` / `cisterna_ordens_servico` / `cisterna_notificacoes` | 3 / 7 / 7 |
| `cisterna_legado_raw` / `cisterna_etl_log` | 11.396 / 19.624 |

---

## File Structure

### Criar

| Arquivo | Responsabilidade |
|---|---|
| `app/Modules/Cisterna/Support/EscopoPerfil.php` | Unico lugar onde a regra de recorte territorial/perfil vira SQL. Tres formas de aplicar: no proprio beneficiario, atras de uma relacao para beneficiario, e por coluna `municipio_id`. |
| `app/Modules/Cisterna/Requests/Api/FiltroApiRequest.php` | Base abstrata: valida `page`/`per_page`, normaliza filtros multivalor e expoe `filtros()` / `porPagina()`. |
| `app/Modules/Cisterna/Requests/Api/ListarBeneficiariosRequest.php` | Regras dos filtros de beneficiario. |
| `app/Modules/Cisterna/Requests/Api/ListarVistoriasRequest.php` | Regras dos filtros de vistoria. |
| `app/Modules/Cisterna/Requests/Api/ListarComunidadesRequest.php` | Regras dos filtros de comunidade. |
| `app/Modules/Cisterna/Requests/Api/ListarNotificacoesRequest.php` | Regras dos filtros de notificacao. |
| `app/Modules/Cisterna/Requests/Api/ListarPaginadoRequest.php` | Sem filtro proprio: lotes e ordens de servico. |
| `app/Http/Controllers/Api/V1/Cisterna/BeneficiarioApiController.php` | `index`, `show`, `export` + anotacoes OpenAPI dos tres. |
| `app/Http/Controllers/Api/V1/Cisterna/VistoriaApiController.php` | `index`, `show` + anotacoes. |
| `app/Http/Controllers/Api/V1/Cisterna/ApoioApiController.php` | `comunidades`, `lotes`, `ordensServico` + anotacoes. Juntos porque sao tres listagens de referencia sem regra propria; separa-los criaria tres arquivos de 30 linhas. |
| `app/Http/Controllers/Api/V1/Cisterna/NotificacaoApiController.php` | `index` + anotacoes. |
| `tests/Feature/Cisterna/Api/EscopoPerfilTest.php` | Equivalencia da extracao e as tres formas de aplicar. |
| `tests/Feature/Cisterna/Api/VistoriaListarTest.php` | A listagem nova de vistorias: escopo, filtros, teto. |
| `tests/Feature/Cisterna/Api/ServicosApoioEscopoTest.php` | Perfil opcional em comunidade e notificacao; lote/OS sem recorte. |
| `tests/Feature/Cisterna/Api/BeneficiarioApiTest.php` | HTTP: 401/403, escopo, filtros, teto, shape, export. |
| `tests/Feature/Cisterna/Api/VistoriaApiTest.php` | HTTP das vistorias. |
| `tests/Feature/Cisterna/Api/ApoioApiTest.php` | HTTP de comunidades, lotes, OS e notificacoes. |

### Modificar

| Arquivo | Mudanca |
|---|---|
| `app/Modules/Cisterna/Services/BeneficiarioService.php:403-414` | `aplicarEscopoDoPerfil()` passa a delegar para `EscopoPerfil`. Comportamento identico. |
| `app/Modules/Cisterna/Services/VistoriaService.php` | Ganha `listar()` e `aplicarFiltros()` privado. |
| `app/Modules/Cisterna/Services/ComunidadeService.php:19-36` | `listar()` ganha 3o parametro `?PerfilCisterna $perfil = null`. |
| `app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php:31-52` | `listar()` ganha 3o parametro `?PerfilCisterna $perfil = null`. |
| `routes/api.php` | Grupo `cisternas` dentro do `prefix('v1')` existente. |
| `app/Http/Controllers/Api/SwaggerController.php` | `@OA\Tag(name="Cisternas")` + 8 schemas com o mapeamento legado -> dominio nas `description`. |

### Nao modificar, de proposito

- `app/Modules/Cisterna/Services/LoteService.php` e `OrdemServicoService.php` — as tabelas nao tem `municipio_id`; o lote e nacional por natureza. O contrato "sem recorte territorial" fica documentado no Swagger e verificado por teste.
- `routes/modules/cisterna.php` e os controllers web — nenhuma mudanca de comportamento na interface.
- `app/Modules/Cisterna/Resources/*` — os Resources ja emitem o payload correto e sao reaproveitados como estao.

---

## Task 1: `EscopoPerfil` — a regra de recorte num lugar so

Hoje a regra existe em **um** lugar, e privado: `BeneficiarioService::aplicarEscopoDoPerfil()` (linha 403). Vistorias, comunidades e notificacoes vao precisar da mesma regra. Copiar recriaria exatamente o defeito que o modulo eliminou do legado, onde a regra estava replicada em quatro metodos do controller com um `codmundv` literal no meio.

**Files:**
- Create: `app/Modules/Cisterna/Support/EscopoPerfil.php`
- Modify: `app/Modules/Cisterna/Services/BeneficiarioService.php:403-414`
- Test: `tests/Feature/Cisterna/Api/EscopoPerfilTest.php`

**Interfaces:**
- Consumes: `App\Modules\Cisterna\Support\PerfilCisterna` (`municipioId(): ?int`, `eFornecedor(): bool`, `eCedec(): bool`, `eCompdec(): bool`, `deUsuario(User): self`); scopes `CisternaBeneficiario::scopeDoMunicipio(int)` e `scopeComSituacaoObra(array)`; `SituacaoObra::visiveisAoFornecedor(): array<int,string>`.
- Produces:
  - `EscopoPerfil::aplicarEmBeneficiario(Builder $query, PerfilCisterna $perfil): void`
  - `EscopoPerfil::aplicarViaBeneficiario(Builder $query, PerfilCisterna $perfil, string $relacao = 'beneficiario'): void`
  - `EscopoPerfil::aplicarEmMunicipio(Builder $query, PerfilCisterna $perfil, string $coluna = 'municipio_id'): void`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/EscopoPerfilTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EscopoPerfilTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function test_compdec_no_beneficiario_filtra_pelo_proprio_municipio(): void
    {
        $doProprio = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $query = CisternaBeneficiario::query();
        EscopoPerfil::aplicarEmBeneficiario($query, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));

        $ids = $query->pluck('id')->all();

        $this->assertContains($doProprio->id, $ids);
        $this->assertSame([$this->municipios[0]], array_values(array_unique(
            CisternaBeneficiario::whereIn('id', $ids)->pluck('municipio_id')->map(fn ($v): int => (int) $v)->all()
        )));
    }

    public function test_cedec_no_beneficiario_nao_filtra_nada(): void
    {
        $query = CisternaBeneficiario::query();
        $antes = $query->clone()->count();

        EscopoPerfil::aplicarEmBeneficiario($query, $this->perfil(TipoOrgao::CEDEC));

        $this->assertSame($antes, $query->count());
    }

    public function test_fornecedor_no_beneficiario_restringe_a_situacao_da_obra(): void
    {
        $query = CisternaBeneficiario::query();
        EscopoPerfil::aplicarEmBeneficiario($query, $this->perfilFornecedor());

        $situacoes = $query->distinct()->pluck('situacao_obra')->all();

        $this->assertEmpty(array_diff($situacoes, SituacaoObra::visiveisAoFornecedor()));
        $this->assertNotContains(SituacaoObra::PROCESSAMENTO->value, $situacoes);
    }

    public function test_via_beneficiario_recorta_vistoria_pelo_municipio_do_beneficiario(): void
    {
        $doProprio = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        $deFora = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $dentro = CisternaVistoria::factory()->create([
            'beneficiario_id' => $doProprio->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
        ]);
        $fora = CisternaVistoria::factory()->create([
            'beneficiario_id' => $deFora->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
        ]);

        $query = CisternaVistoria::query();
        EscopoPerfil::aplicarViaBeneficiario($query, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));

        $ids = $query->pluck('id')->all();

        $this->assertContains($dentro->id, $ids);
        $this->assertNotContains($fora->id, $ids);
    }

    public function test_via_beneficiario_nao_acrescenta_exists_para_cedec(): void
    {
        $query = CisternaVistoria::query();
        EscopoPerfil::aplicarViaBeneficiario($query, $this->perfil(TipoOrgao::CEDEC));

        // Sem recorte, nao ha motivo para pagar um EXISTS correlacionado.
        $this->assertStringNotContainsStringIgnoringCase('exists', $query->toSql());
    }

    public function test_em_municipio_recorta_comunidade_pela_coluna_direta(): void
    {
        $dentro = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[0]]);
        $fora = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[1]]);

        $query = CisternaComunidade::query();
        EscopoPerfil::aplicarEmMunicipio($query, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));

        $ids = $query->pluck('id')->all();

        $this->assertContains($dentro->id, $ids);
        $this->assertNotContains($fora->id, $ids);
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
        $user->assignRole(Role::firstOrCreate([
            'name' => PerfilCisterna::ROLE_FORNECEDOR,
            'guard_name' => 'web',
        ]));

        return PerfilCisterna::deUsuario($user->fresh());
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=EscopoPerfilTest
```

Esperado: FAIL com `Class "App\Modules\Cisterna\Support\EscopoPerfil" not found`.

- [ ] **Step 3: Implementar `EscopoPerfil`**

Criar `app/Modules/Cisterna/Support/EscopoPerfil.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

use App\Modules\Cisterna\Enums\SituacaoObra;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traducao do perfil do usuario em recorte de consulta.
 *
 * Existe porque a regra e a mesma para beneficiario, vistoria, comunidade e
 * notificacao, e o legado a tinha replicada em quatro metodos do controller
 * -- um deles com o codmundv 3104452 literal no meio. Aqui e um lugar so.
 *
 * As tres formas correspondem a distancia entre a tabela consultada e o
 * municipio: o beneficiario tem a coluna, a vistoria chega por relacao, e a
 * comunidade tem a coluna com outro nome de caminho.
 */
final class EscopoPerfil
{
    /**
     * Recorte sobre a propria tabela de beneficiarios.
     *
     * COMPDEC ve so o proprio municipio. Fornecedor nao tem territorio, mas ve
     * somente as obras que sairam para instalacao -- e o mesmo recorte que o
     * legado fazia em CisternaController.php:75.
     */
    public static function aplicarEmBeneficiario(Builder $query, PerfilCisterna $perfil): void
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
     * Recorte de uma tabela que alcanca o municipio por relacao ao
     * beneficiario -- vistoria e notificacao de vistoria.
     *
     * A saida antecipada nao e otimizacao prematura: sem ela, CEDEC pagaria um
     * EXISTS correlacionado em toda consulta para nao filtrar nada. Vistoria
     * tem `beneficiario_id NOT NULL` com FK CASCADE, entao nao existe linha
     * orfa que o EXISTS estivesse eliminando de tabela.
     */
    public static function aplicarViaBeneficiario(
        Builder $query,
        PerfilCisterna $perfil,
        string $relacao = 'beneficiario',
    ): void {
        if (! self::temRecorte($perfil)) {
            return;
        }

        $query->whereHas($relacao, function (Builder $beneficiario) use ($perfil): void {
            self::aplicarEmBeneficiario($beneficiario, $perfil);
        });
    }

    /**
     * Recorte por coluna de municipio na propria tabela, sem passar por
     * beneficiario -- comunidade.
     *
     * Fornecedor NAO e restringido aqui de proposito: `situacao_obra` e do
     * beneficiario, nao da comunidade, e esconder a comunidade porque nenhuma
     * obra dela saiu para instalacao quebraria o select em cascata do
     * formulario sem proteger nada.
     */
    public static function aplicarEmMunicipio(
        Builder $query,
        PerfilCisterna $perfil,
        string $coluna = 'municipio_id',
    ): void {
        $municipioId = $perfil->municipioId();

        if ($municipioId !== null) {
            $query->where($coluna, $municipioId);
        }
    }

    public static function temRecorte(PerfilCisterna $perfil): bool
    {
        return $perfil->municipioId() !== null || $perfil->eFornecedor();
    }
}
```

- [ ] **Step 4: Delegar do `BeneficiarioService`**

Em `app/Modules/Cisterna/Services/BeneficiarioService.php`, substituir o corpo de `aplicarEscopoDoPerfil()` (linhas 403-414) por:

```php
    private function aplicarEscopoDoPerfil(Builder $query, PerfilCisterna $perfil): void
    {
        EscopoPerfil::aplicarEmBeneficiario($query, $perfil);
    }
```

E acrescentar o import, em ordem alfabetica junto aos outros `use`:

```php
use App\Modules\Cisterna\Support\EscopoPerfil;
```

Remover o `use App\Modules\Cisterna\Enums\SituacaoObra;` **somente se** nenhum outro ponto do arquivo o usar — conferir com:

```bash
grep -n "SituacaoObra" app/Modules/Cisterna/Services/BeneficiarioService.php
```

Se aparecer em outra linha (validacao de acao em massa, por exemplo), manter o import.

- [ ] **Step 5: Rodar os testes e confirmar que passam, sem regressao**

```bash
docker exec newsdc_dev_app php artisan test --filter=EscopoPerfilTest
docker exec newsdc_dev_app php artisan test --filter=BeneficiarioServiceTest
```

Esperado: ambos PASS. `BeneficiarioServiceTest` cobre os tres perfis e e a rede de seguranca da extracao — se ele passar, o comportamento nao mudou.

- [ ] **Step 6: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Modules/Cisterna/Support/EscopoPerfil.php app/Modules/Cisterna/Services/BeneficiarioService.php tests/Feature/Cisterna/Api/EscopoPerfilTest.php
git add app/Modules/Cisterna/Support/EscopoPerfil.php app/Modules/Cisterna/Services/BeneficiarioService.php tests/Feature/Cisterna/Api/EscopoPerfilTest.php
git commit -m "♻️ refactor(cisterna): extrai recorte de perfil para EscopoPerfil"
```

---

## Task 2: `VistoriaService::listar()`

`VistoriaService` hoje tem `etapaDisponivel`, `abrir`, `atualizar`, `concluir` e `sincronizarItens` — **nenhuma listagem**. A tela web lista as vistorias de um beneficiario direto no controller (`VistoriaController::index` carrega `$beneficiario->vistorias`). A API precisa listar as 2.136 vistorias de forma paginada, filtravel e recortada por perfil.

**Files:**
- Modify: `app/Modules/Cisterna/Services/VistoriaService.php`
- Test: `tests/Feature/Cisterna/Api/VistoriaListarTest.php`

**Interfaces:**
- Consumes: `EscopoPerfil::aplicarViaBeneficiario()` (Task 1); scopes `CisternaVistoria::scopeDaEtapa(EtapaVistoria)` e `scopeConcluidas()`; `EtapaVistoria::tryFrom(string)`.
- Produces: `VistoriaService::listar(PerfilCisterna $perfil, array $filtros = [], int $porPagina = 25): LengthAwarePaginator`, e as constantes `VistoriaService::PORTE_MAXIMO_PAGINA = 100` / `PORTE_PADRAO_PAGINA = 25`.
- Filtros aceitos: `etapa`, `beneficiario_id`, `municipio_id`, `comunidade_id`, `numero_instalacao`, `concluida` (bool), `data_relatorio_inicio`, `data_relatorio_fim`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/VistoriaListarTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\VistoriaService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VistoriaListarTest extends TestCase
{
    use DatabaseTransactions;

    private VistoriaService $service;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(VistoriaService::class);
        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function test_cedec_ve_vistoria_de_qualquer_municipio(): void
    {
        $antes = $this->service->listar($this->perfil(TipoOrgao::CEDEC))->total();

        $this->vistoriaEm($this->municipios[0], EtapaVistoria::COMPDEC);
        $this->vistoriaEm($this->municipios[1], EtapaVistoria::COMPDEC);

        $depois = $this->service->listar($this->perfil(TipoOrgao::CEDEC))->total();

        // Delta, nao total: o banco tem 2.136 vistorias da migracao real.
        $this->assertSame(2, $depois - $antes);
    }

    public function test_compdec_ve_apenas_vistoria_do_proprio_municipio(): void
    {
        $perfil = $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]);
        $antes = $this->service->listar($perfil)->total();

        $dentro = $this->vistoriaEm($this->municipios[0], EtapaVistoria::COMPDEC);
        $this->vistoriaEm($this->municipios[1], EtapaVistoria::COMPDEC);

        $pagina = $this->service->listar($perfil);

        $this->assertSame(1, $pagina->total() - $antes);
        $this->assertContains($dentro->id, collect($pagina->items())->pluck('id')->all());
    }

    public function test_filtra_por_etapa(): void
    {
        $perfil = $this->perfil(TipoOrgao::CEDEC);
        $antes = $this->service->listar($perfil, ['etapa' => EtapaVistoria::CEDEC->value])->total();

        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::CEDEC->value,
        ]);
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
        ]);

        $depois = $this->service->listar($perfil, ['etapa' => EtapaVistoria::CEDEC->value]);

        $this->assertSame(1, $depois->total() - $antes);
        $this->assertSame(
            [EtapaVistoria::CEDEC->value],
            collect($depois->items())->pluck('etapa.value')->unique()->values()->all()
        );
    }

    public function test_filtra_por_numero_de_instalacao(): void
    {
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        // Faixa reservada 9xxxxx: o banco real usa numeracao baixa.
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::FORNECEDOR->value,
            'numero_instalacao' => 900731,
        ]);

        $pagina = $this->service->listar(
            $this->perfil(TipoOrgao::CEDEC),
            ['numero_instalacao' => 900731]
        );

        $this->assertSame(1, $pagina->total());
        $this->assertSame(900731, $pagina->items()[0]->numero_instalacao);
    }

    public function test_filtro_concluida_false_nao_e_ignorado(): void
    {
        $perfil = $this->perfil(TipoOrgao::CEDEC);
        $antes = $this->service->listar($perfil, ['concluida' => false])->total();

        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
            'concluida_em' => null,
        ]);
        CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::CEDEC->value,
            'concluida_em' => now(),
        ]);

        $pendentes = $this->service->listar($perfil, ['concluida' => false]);

        // Se `concluida => false` fosse tratado por when(), o filtro nao
        // dispararia e as duas entrariam -- erro silencioso.
        $this->assertSame(1, $pendentes->total() - $antes);
        $this->assertNull($pendentes->items()[0]->concluida_em);
    }

    public function test_teto_de_pagina_e_cem(): void
    {
        $pagina = $this->service->listar($this->perfil(TipoOrgao::CEDEC), [], 100000);

        $this->assertSame(VistoriaService::PORTE_MAXIMO_PAGINA, $pagina->perPage());
    }

    public function test_carrega_beneficiario_e_itens_sem_consulta_por_linha(): void
    {
        $pagina = $this->service->listar($this->perfil(TipoOrgao::CEDEC), [], 5);

        $this->assertNotEmpty($pagina->items(), 'o banco de dev deve ter vistorias da migracao');

        $primeira = $pagina->items()[0];

        $this->assertTrue($primeira->relationLoaded('beneficiario'));
        $this->assertTrue($primeira->relationLoaded('itensConferidos'));
    }

    private function vistoriaEm(int $municipioId, EtapaVistoria $etapa): CisternaVistoria
    {
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $municipioId]);

        return CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => $etapa->value,
        ]);
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
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=VistoriaListarTest
```

Esperado: FAIL com `Call to undefined method App\Modules\Cisterna\Services\VistoriaService::listar()`.

- [ ] **Step 3: Implementar `listar()` e `aplicarFiltros()`**

Em `app/Modules/Cisterna/Services/VistoriaService.php`, acrescentar aos imports:

```php
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
```

E, logo depois do `__construct`, os metodos:

```php
    /**
     * Mesmo teto do beneficiario. Sem ele, `per_page=100000` puxa as 2.136
     * vistorias com os 27.684 itens conferidos num unico request.
     */
    public const PORTE_MAXIMO_PAGINA = 100;

    public const PORTE_PADRAO_PAGINA = 25;

    /**
     * Listagem paginada das vistorias, recortada pelo perfil.
     *
     * Nao existe equivalente na interface web: a tela lista as vistorias de um
     * beneficiario so, direto da relacao. Este metodo nasce para a API.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listar(
        PerfilCisterna $perfil,
        array $filtros = [],
        int $porPagina = self::PORTE_PADRAO_PAGINA,
    ): LengthAwarePaginator {
        $porPagina = max(1, min($porPagina, self::PORTE_MAXIMO_PAGINA));

        $query = CisternaVistoria::query()
            ->with([
                // Colunas restritas: a vistoria e o beneficiario juntos passam
                // de 60 colunas, e a listagem precisa de meia duzia.
                'beneficiario:id,nome,cpf,municipio_id,comunidade_id',
                'beneficiario.municipio:id,nome,uf',
                'itensConferidos',
            ]);

        EscopoPerfil::aplicarViaBeneficiario($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        return $query
            // `nulls last` explicito: data_relatorio e anulavel, e no Postgres
            // DESC coloca NULL primeiro -- a primeira pagina viria com as
            // vistorias sem data em vez das mais recentes.
            ->orderByRaw('data_relatorio desc nulls last')
            // Desempate por id mantem a paginacao estavel: sem ele, linhas com
            // a mesma data trocam de pagina entre requests e o consumidor le a
            // mesma vistoria duas vezes (ou nenhuma).
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['etapa'] ?? null, function (Builder $q, $valor): void {
                $etapa = EtapaVistoria::tryFrom((string) $valor);

                if ($etapa !== null) {
                    $q->daEtapa($etapa);
                }
            })
            ->when($filtros['beneficiario_id'] ?? null, function (Builder $q, $id): void {
                $q->where('beneficiario_id', (int) $id);
            })
            ->when($filtros['numero_instalacao'] ?? null, function (Builder $q, $numero): void {
                $q->where('numero_instalacao', (int) $numero);
            })
            ->when($filtros['municipio_id'] ?? null, function (Builder $q, $id): void {
                $q->whereHas('beneficiario', fn (Builder $b) => $b->where('municipio_id', (int) $id));
            })
            ->when($filtros['comunidade_id'] ?? null, function (Builder $q, $id): void {
                $q->whereHas('beneficiario', fn (Builder $b) => $b->where('comunidade_id', (int) $id));
            })
            ->when($filtros['data_relatorio_inicio'] ?? null, function (Builder $q, $inicio): void {
                $q->whereDate('data_relatorio', '>=', $inicio);
            })
            ->when($filtros['data_relatorio_fim'] ?? null, function (Builder $q, $fim): void {
                $q->whereDate('data_relatorio', '<=', $fim);
            });

        // `concluida` e booleano e precisa de isset, nao de when(): when() nao
        // dispara com false, entao `concluida=false` seria ignorado e a API
        // devolveria tudo -- errado sem erro nenhum aparecer.
        if (isset($filtros['concluida'])) {
            if ((bool) $filtros['concluida']) {
                $query->concluidas();
            } else {
                $query->whereNull('concluida_em');
            }
        }
    }
```

Conferir que `EtapaVistoria` e `CisternaVistoria` ja estao importados no arquivo (estao — `abrir()` e `etapaDisponivel()` usam os dois).

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```bash
docker exec newsdc_dev_app php artisan test --filter=VistoriaListarTest
```

Esperado: PASS, 7 testes.

- [ ] **Step 5: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Modules/Cisterna/Services/VistoriaService.php tests/Feature/Cisterna/Api/VistoriaListarTest.php
git add app/Modules/Cisterna/Services/VistoriaService.php tests/Feature/Cisterna/Api/VistoriaListarTest.php
git commit -m "✨ feat(cisterna): listagem paginada de vistorias com recorte de perfil"
```

---

## Task 3: Recorte de perfil nos services de apoio

`ComunidadeService::listar()` e `NotificacaoFiscalizacaoService::listar()` hoje nao recebem perfil nenhum — nao ha recorte territorial. `LoteService` e `OrdemServicoService` tambem nao, mas ali e correto: as tabelas nao tem `municipio_id`.

O parametro entra **por ultimo e opcional**, com default `null` que preserva exatamente o comportamento das telas web. Nenhum call site web e alterado.

> **A registrar para o dono, nao a corrigir aqui:** as telas web de comunidades e notificacoes seguirao sem recorte territorial. Passar o perfil na web tambem mudaria o que o COMPDEC ve hoje na interface — decisao dele, fora deste plano.

**Files:**
- Modify: `app/Modules/Cisterna/Services/ComunidadeService.php:19-36`
- Modify: `app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php:31-52`
- Test: `tests/Feature/Cisterna/Api/ServicosApoioEscopoTest.php`

**Interfaces:**
- Consumes: `EscopoPerfil::aplicarEmBeneficiario()`, `aplicarViaBeneficiario()`, `aplicarEmMunicipio()`, `temRecorte()` (Task 1).
- Produces:
  - `ComunidadeService::listar(array $filtros = [], int $porPagina = 50, ?PerfilCisterna $perfil = null): LengthAwarePaginator`
  - `NotificacaoFiscalizacaoService::listar(array $filtros = [], int $porPagina = 25, ?PerfilCisterna $perfil = null): LengthAwarePaginator`
  - `LoteService::listar(int $porPagina = 25)` e `OrdemServicoService::listar(?int $loteId = null, int $porPagina = 25)` — **inalterados**, contrato "sem recorte" verificado por teste.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/ServicosApoioEscopoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\LoteService;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServicosApoioEscopoTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function test_comunidade_sem_perfil_mantem_o_comportamento_da_web(): void
    {
        $service = app(ComunidadeService::class);

        $a = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[0]]);
        $b = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[1]]);

        $ids = collect($service->listar([], 100000)->items())->pluck('id')->all();

        // Sem perfil, nada e recortado. Confirma que a web nao muda.
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_comunidade_com_perfil_compdec_recorta_pelo_municipio(): void
    {
        $service = app(ComunidadeService::class);

        $dentro = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[0]]);
        $fora = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[1]]);

        $pagina = $service->listar([], 100000, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));
        $ids = collect($pagina->items())->pluck('id')->all();

        $this->assertContains($dentro->id, $ids);
        $this->assertNotContains($fora->id, $ids);
    }

    public function test_notificacao_de_beneficiario_recorta_pelo_municipio(): void
    {
        $service = app(NotificacaoFiscalizacaoService::class);
        $autor = User::factory()->create();

        $dentro = $this->notificacaoDeBeneficiario($this->municipios[0], $autor);
        $fora = $this->notificacaoDeBeneficiario($this->municipios[1], $autor);

        $pagina = $service->listar([], 100000, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));
        $ids = collect($pagina->items())->pluck('id')->all();

        $this->assertContains($dentro->id, $ids);
        $this->assertNotContains($fora->id, $ids);
    }

    public function test_notificacao_de_vistoria_recorta_pelo_municipio_do_beneficiario(): void
    {
        $service = app(NotificacaoFiscalizacaoService::class);
        $autor = User::factory()->create();

        $dentro = $this->notificacaoDeVistoria($this->municipios[0], $autor);
        $fora = $this->notificacaoDeVistoria($this->municipios[1], $autor);

        $pagina = $service->listar([], 100000, $this->perfil(TipoOrgao::COMPDEC, $this->municipios[0]));
        $ids = collect($pagina->items())->pluck('id')->all();

        // O notificavel polimorfico precisa das duas pontas: sem whereHasMorph
        // cobrindo vistoria, esta assercao falha.
        $this->assertContains($dentro->id, $ids);
        $this->assertNotContains($fora->id, $ids);
    }

    public function test_lote_nao_tem_recorte_territorial(): void
    {
        $service = app(LoteService::class);

        $lote = CisternaLote::factory()->create();

        $ids = collect($service->listar(100000)->items())->pluck('id')->all();

        // Contrato explicito: cisterna_lotes nao tem municipio_id. O lote e
        // nacional, e um COMPDEC precisa ve-lo para saber em que lote esta a
        // propria ordem de servico.
        $this->assertContains($lote->id, $ids);
    }

    private function notificacaoDeBeneficiario(int $municipioId, User $autor): CisternaNotificacao
    {
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $municipioId]);

        return CisternaNotificacao::create([
            'notificavel_type' => NotificacaoDTO::TIPOS_PERMITIDOS['beneficiario'],
            'notificavel_id' => $beneficiario->id,
            'observacao' => 'teste de escopo '.uniqid(),
            'respondida' => false,
            'created_by' => $autor->id,
        ]);
    }

    private function notificacaoDeVistoria(int $municipioId, User $autor): CisternaNotificacao
    {
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $municipioId]);
        $vistoria = CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
        ]);

        return CisternaNotificacao::create([
            'notificavel_type' => NotificacaoDTO::TIPOS_PERMITIDOS['vistoria'],
            'notificavel_id' => $vistoria->id,
            'observacao' => 'teste de escopo vistoria '.uniqid(),
            'respondida' => false,
            'created_by' => $autor->id,
        ]);
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
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=ServicosApoioEscopoTest
```

Esperado: FAIL — `test_comunidade_com_perfil_compdec_recorta_pelo_municipio` acusa `ArgumentCountError` ou passa o perfil como valor ignorado, e as duas de notificacao falham na assercao `assertNotContains`.

- [ ] **Step 3: `ComunidadeService::listar()` ganha o perfil**

Em `app/Modules/Cisterna/Services/ComunidadeService.php`, acrescentar aos imports:

```php
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
```

E substituir o metodo `listar()` inteiro (linhas 19-36) por:

```php
    /**
     * @param  array<string, mixed>  $filtros
     *
     * O perfil e o ultimo parametro e opcional: as telas web chamam
     * `listar($filtros, $porPagina)` e continuam sem recorte territorial, que
     * e o comportamento atual delas. A API passa o perfil.
     */
    public function listar(
        array $filtros = [],
        int $porPagina = 50,
        ?PerfilCisterna $perfil = null,
    ): LengthAwarePaginator {
        $query = CisternaComunidade::query()
            ->with('municipio:id,nome,uf')
            // Corrige o defeito C18: o legado contava com
            // leftJoin('sinc_cisterna', 'comunidade', '=', 'comunidade'), sem o
            // municipio, entao os 75 nomes de comunidade que existem em mais de
            // um municipio tinham a contagem somada entre eles.
            ->withCount('beneficiarios')
            ->when($filtros['municipio_id'] ?? null, fn (Builder $q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, function (Builder $q, $termo): void {
                $q->where('nome', 'ilike', '%'.trim((string) $termo).'%');
            })
            ->when(($filtros['apenas_ativas'] ?? false) === true, fn (Builder $q) => $q->ativas());

        if ($perfil !== null) {
            EscopoPerfil::aplicarEmMunicipio($query, $perfil);
        }

        return $query
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }
```

- [ ] **Step 4: `NotificacaoFiscalizacaoService::listar()` ganha o perfil**

Em `app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php`, acrescentar aos imports:

```php
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
```

E substituir o metodo `listar()` inteiro (linhas 31-52) por:

```php
    /**
     * @param  array<string, mixed>  $filtros
     *
     * O perfil e opcional e vem por ultimo, para as telas web continuarem
     * chamando `listar($filtros, $porPagina)` sem mudanca de comportamento.
     */
    public function listar(
        array $filtros = [],
        int $porPagina = 25,
        ?PerfilCisterna $perfil = null,
    ): LengthAwarePaginator {
        $query = CisternaNotificacao::query()
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
            });

        if ($perfil !== null && EscopoPerfil::temRecorte($perfil)) {
            $this->aplicarEscopoNoNotificavel($query, $perfil);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * O notificavel e polimorfico e as duas pontas chegam ao municipio por
     * caminhos diferentes: o beneficiario tem a coluna, a vistoria chega pela
     * relacao. Cobrir so uma delas deixaria metade das notificacoes vazando
     * para fora do territorio.
     */
    private function aplicarEscopoNoNotificavel(Builder $query, PerfilCisterna $perfil): void
    {
        $query->whereHasMorph(
            'notificavel',
            [CisternaBeneficiario::class, CisternaVistoria::class],
            function (Builder $notificavel, string $tipo) use ($perfil): void {
                if ($tipo === CisternaBeneficiario::class) {
                    EscopoPerfil::aplicarEmBeneficiario($notificavel, $perfil);

                    return;
                }

                EscopoPerfil::aplicarViaBeneficiario($notificavel, $perfil);
            }
        );
    }
```

- [ ] **Step 5: Rodar os testes e confirmar que passam, sem regressao**

```bash
docker exec newsdc_dev_app php artisan test --filter=ServicosApoioEscopoTest
docker exec newsdc_dev_app php artisan test --filter=ComunidadeLoteOsServiceTest
docker exec newsdc_dev_app php artisan test --filter=NotificacaoFiscalizacaoServiceTest
docker exec newsdc_dev_app php artisan test --filter=ApoioPaginasRenderTest
```

Esperado: os quatro PASS. Os tres ultimos sao a rede de seguranca de que a web nao mudou.

- [ ] **Step 6: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Modules/Cisterna/Services/ComunidadeService.php app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php tests/Feature/Cisterna/Api/ServicosApoioEscopoTest.php
git add app/Modules/Cisterna/Services/ComunidadeService.php app/Modules/Cisterna/Services/NotificacaoFiscalizacaoService.php tests/Feature/Cisterna/Api/ServicosApoioEscopoTest.php
git commit -m "✨ feat(cisterna): recorte de perfil opcional em comunidades e notificacoes"
```

---

## Task 4: Requests de filtro da API

Filtro invalido deve devolver **422 com a mensagem**, nao ser ignorado em silencio. Uma base abstrata carrega `page`/`per_page` e a normalizacao dos filtros multivalor; cada recurso declara so as regras proprias.

Os services aceitam escalar ou array nos filtros multivalor (`is_array($ids) ? $ids : [$ids]`). A base normaliza **tudo para array** antes da validacao, para as regras `campo.*` sempre valerem — com escalar, `campo.*` nao se aplica e o valor passaria sem validacao.

**Files:**
- Create: `app/Modules/Cisterna/Requests/Api/FiltroApiRequest.php`
- Create: `app/Modules/Cisterna/Requests/Api/ListarBeneficiariosRequest.php`
- Create: `app/Modules/Cisterna/Requests/Api/ListarVistoriasRequest.php`
- Create: `app/Modules/Cisterna/Requests/Api/ListarComunidadesRequest.php`
- Create: `app/Modules/Cisterna/Requests/Api/ListarNotificacoesRequest.php`
- Create: `app/Modules/Cisterna/Requests/Api/ListarPaginadoRequest.php`

**Interfaces:**
- Consumes: `BeneficiarioService::PORTE_MAXIMO_PAGINA` (100) e `PORTE_PADRAO_PAGINA` (25); `SituacaoAnalise::valores()`, `SituacaoObra::valores()`, `EtapaVistoria::valores()`; `NotificacaoDTO::TIPOS_PERMITIDOS` (chaves `beneficiario`, `vistoria`).
- Produces, em todas as classes:
  - `filtros(): array<string, mixed>` — validados, sem `page`/`per_page`
  - `porPagina(): int`

- [ ] **Step 1: Criar a base**

`app/Modules/Cisterna/Requests/Api/FiltroApiRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

use App\Modules\Cisterna\Services\BeneficiarioService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base dos filtros de listagem da API do Cisterna.
 *
 * Filtro fora do dominio devolve 422 em vez de ser descartado: o consumidor
 * que escreve `etapa=fornecedo` precisa saber que errou, e nao receber a base
 * inteira achando que filtrou.
 */
abstract class FiltroApiRequest extends FormRequest
{
    /**
     * A autorizacao e do middleware `can:` na rota e da policy no `show`.
     * Repetir aqui daria 403 antes da validacao e mascararia qual das duas
     * barreiras recusou.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtros multivalor aceitos como lista. Declarados pela subclasse.
     *
     * @return array<int, string>
     */
    protected function camposMultivalor(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function regrasDoFiltro(): array;

    /**
     * @return array<string, mixed>
     */
    final public function rules(): array
    {
        return array_merge([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.BeneficiarioService::PORTE_MAXIMO_PAGINA],
        ], $this->regrasDoFiltro());
    }

    /**
     * Aceita `?situacao_analise=aprovado`, `?situacao_analise=a,b` e
     * `?situacao_analise[]=a&situacao_analise[]=b`, normalizando as tres para
     * array. Sem isso a regra `campo.*` nao se aplica ao escalar e o valor
     * entraria sem validacao.
     */
    protected function prepareForValidation(): void
    {
        foreach ($this->camposMultivalor() as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);

            if (is_string($valor)) {
                $valor = array_values(array_filter(array_map('trim', explode(',', $valor)), fn (string $v): bool => $v !== ''));
            }

            $this->merge([$campo => is_array($valor) ? array_values($valor) : [$valor]]);
        }
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', BeneficiarioService::PORTE_PADRAO_PAGINA);
    }

    /**
     * @return array<string, mixed>
     */
    public function filtros(): array
    {
        return $this->safe()->except(['page', 'per_page']);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.max' => 'O maximo por pagina e '.BeneficiarioService::PORTE_MAXIMO_PAGINA.'.',
        ];
    }
}
```

- [ ] **Step 2: Criar as cinco subclasses**

`ListarBeneficiariosRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use Illuminate\Validation\Rule;

class ListarBeneficiariosRequest extends FiltroApiRequest
{
    /**
     * @return array<int, string>
     */
    protected function camposMultivalor(): array
    {
        return ['comunidade_id', 'situacao_analise', 'situacao_obra', 'ordem_servico_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['sometimes', 'array'],
            'comunidade_id.*' => ['integer', 'exists:cisterna_comunidades,id'],
            'situacao_analise' => ['sometimes', 'array'],
            'situacao_analise.*' => [Rule::in(SituacaoAnalise::valores())],
            'situacao_obra' => ['sometimes', 'array'],
            'situacao_obra.*' => [Rule::in(SituacaoObra::valores())],
            'ordem_servico_id' => ['sometimes', 'array'],
            'ordem_servico_id.*' => ['integer', 'exists:cisterna_ordens_servico,id'],
            'lote_id' => ['sometimes', 'integer', 'exists:cisterna_lotes,id'],
            'cpf' => ['sometimes', 'string', 'max:14'],
            'search' => ['sometimes', 'string', 'max:150'],
            'data_inicio' => ['sometimes', 'date'],
            'data_fim' => ['sometimes', 'date', 'after_or_equal:data_inicio'],
            'atendido_por_pipa' => ['sometimes', 'boolean'],
            'numero_instalacao' => ['sometimes', 'integer', 'min:1'],
            'etapa_concluida' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'etapa_pendente' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'ranqueamento' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', Rule::in(['nome', 'cpf', 'situacao_analise', 'situacao_obra', 'municipio', 'comunidade', 'etapas'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
```

`ListarVistoriasRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use Illuminate\Validation\Rule;

class ListarVistoriasRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'etapa' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'beneficiario_id' => ['sometimes', 'integer', 'exists:cisterna_beneficiarios,id'],
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['sometimes', 'integer', 'exists:cisterna_comunidades,id'],
            'numero_instalacao' => ['sometimes', 'integer', 'min:1'],
            'concluida' => ['sometimes', 'boolean'],
            'data_relatorio_inicio' => ['sometimes', 'date'],
            'data_relatorio_fim' => ['sometimes', 'date', 'after_or_equal:data_relatorio_inicio'],
        ];
    }
}
```

`ListarComunidadesRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

class ListarComunidadesRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'search' => ['sometimes', 'string', 'max:70'],
            'apenas_ativas' => ['sometimes', 'boolean'],
        ];
    }
}
```

`ListarNotificacoesRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use Illuminate\Validation\Rule;

class ListarNotificacoesRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            // Alias curto, nao o FQCN: o consumidor nao precisa conhecer a
            // estrutura interna. NotificacaoResource devolve o mesmo alias.
            'notificavel_type' => ['sometimes', Rule::in(array_keys(NotificacaoDTO::TIPOS_PERMITIDOS))],
            'notificavel_id' => ['sometimes', 'integer', 'required_with:notificavel_type'],
            'apenas_pendentes' => ['sometimes', 'boolean'],
        ];
    }
}
```

`ListarPaginadoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

/**
 * Lotes e ordens de servico: paginacao, sem filtro proprio alem do lote.
 */
class ListarPaginadoRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'lote_id' => ['sometimes', 'integer', 'exists:cisterna_lotes,id'],
        ];
    }
}
```

- [ ] **Step 3: Conferir que as classes carregam**

```bash
docker exec newsdc_dev_app php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; foreach ([\App\Modules\Cisterna\Requests\Api\ListarBeneficiariosRequest::class, \App\Modules\Cisterna\Requests\Api\ListarVistoriasRequest::class, \App\Modules\Cisterna\Requests\Api\ListarComunidadesRequest::class, \App\Modules\Cisterna\Requests\Api\ListarNotificacoesRequest::class, \App\Modules\Cisterna\Requests\Api\ListarPaginadoRequest::class] as \$c) { new \$c(); echo \$c, \" ok\n\"; }"
```

Esperado: as cinco linhas com `ok`. As regras em si sao exercitadas pelos testes HTTP das Tasks 5 a 7.

- [ ] **Step 4: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Modules/Cisterna/Requests/Api/
git add app/Modules/Cisterna/Requests/Api/
git commit -m "✨ feat(cisterna): requests de filtro da API com validacao de enum"
```

---

## Task 5: Beneficiarios na API — controller, rotas e export

**Files:**
- Create: `app/Http/Controllers/Api/V1/Cisterna/BeneficiarioApiController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Cisterna/Api/BeneficiarioApiTest.php`

**Interfaces:**
- Consumes: `BeneficiarioService::listar(PerfilCisterna, array, int)`; `BeneficiarioExportService::streamCsv(PerfilCisterna, array): StreamedResponse`; `ListarBeneficiariosRequest::filtros()` / `porPagina()`; `BeneficiarioIndexResource`, `BeneficiarioResource`; `PerfilCisterna::deUsuario(User)`; `CisternaBeneficiarioPolicy` (`viewAny`, `view`, `export`).
- Produces: rotas nomeadas `api.v1.cisternas.beneficiarios.index`, `.show`, `.export`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/BeneficiarioApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BeneficiarioApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function test_sem_token_devolve_401(): void
    {
        $this->getJson('/api/v1/cisternas/beneficiarios')->assertUnauthorized();
    }

    public function test_com_token_sem_permissao_devolve_403(): void
    {
        $user = $this->usuario();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/beneficiarios')
            ->assertForbidden();
    }

    public function test_index_devolve_lista_paginada_com_envelope_de_resource(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/beneficiarios?per_page=5')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'cpf', 'nome', 'municipio', 'situacao_analise' => ['valor', 'rotulo'], 'etapas_concluidas']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_index_respeita_o_teto_de_cem_por_pagina(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/beneficiarios?per_page=100000')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_index_recusa_situacao_de_analise_invalida(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/beneficiarios?situacao_analise=inexistente')
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao_analise.0');
    }

    public function test_index_aceita_situacao_de_analise_em_lista_separada_por_virgula(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');

        $resposta = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/beneficiarios?situacao_analise=aprovado,ressalva&per_page=100')
            ->assertOk();

        $valores = collect($resposta->json('data'))->pluck('situacao_analise.valor')->unique()->all();

        $this->assertEmpty(array_diff($valores, [
            SituacaoAnalise::APROVADO->value,
            SituacaoAnalise::RESSALVA->value,
        ]));
    }

    public function test_compdec_ve_apenas_o_proprio_municipio(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC API '.uniqid(),
            'codigo' => 'COMPDEC-API-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.beneficiarios.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $dentro = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);
        $fora = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $ids = collect(
            $this->actingAs($user->fresh(), 'sanctum')
                ->getJson('/api/v1/cisternas/beneficiarios?per_page=100&municipio_id='.$this->municipios[1])
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();

        // Mesmo pedindo o municipio do vizinho, o recorte vence o filtro.
        $this->assertNotContains($fora->id, $ids);
        $this->assertNotContains($dentro->id, $ids);
    }

    public function test_fornecedor_nao_ve_obra_em_processamento(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');
        $user->assignRole(Role::firstOrCreate([
            'name' => PerfilCisterna::ROLE_FORNECEDOR,
            'guard_name' => 'web',
        ]));

        CisternaBeneficiario::factory()->create([
            'municipio_id' => $this->municipios[0],
            'situacao_obra' => SituacaoObra::PROCESSAMENTO->value,
        ]);

        $valores = collect(
            $this->actingAs($user->fresh(), 'sanctum')
                ->getJson('/api/v1/cisternas/beneficiarios?per_page=100')
                ->assertOk()
                ->json('data')
        )->pluck('situacao_obra.valor')->unique()->all();

        $this->assertNotContains(SituacaoObra::PROCESSAMENTO->value, $valores);
    }

    public function test_show_traz_vistorias_itens_e_criterios_sociais(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[0]]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/cisternas/beneficiarios/{$beneficiario->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $beneficiario->id)
            ->assertJsonStructure([
                'data' => [
                    'id', 'cpf', 'nome', 'municipio' => ['id', 'nome', 'uf'],
                    'criterios_sociais' => ['qtd_pessoas', 'renda', 'renda_per_capita'],
                    'avaliacao_tecnica' => ['area_telhado', 'cobertura_telhado'],
                    'atendimento_pipa' => ['atendido'],
                    'vistorias',
                ],
            ]);
    }

    public function test_show_de_outro_municipio_devolve_403_para_compdec(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC API '.uniqid(),
            'codigo' => 'COMPDEC-API-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.beneficiarios.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $fora = CisternaBeneficiario::factory()->create(['municipio_id' => $this->municipios[1]]);

        $this->actingAs($user->fresh(), 'sanctum')
            ->getJson("/api/v1/cisternas/beneficiarios/{$fora->id}")
            ->assertForbidden();
    }

    public function test_export_exige_permissao_propria(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view');

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/cisternas/beneficiarios/export')
            ->assertForbidden();
    }

    public function test_export_devolve_csv_streamado(): void
    {
        $user = $this->usuario('cisternas.beneficiarios.view', 'cisternas.beneficiarios.export');

        $resposta = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/cisternas/beneficiarios/export')
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $resposta->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $resposta->headers->get('content-disposition'));
    }

    private function usuario(string ...$permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        if ($permissoes !== []) {
            $user->givePermissionTo($permissoes);
        }

        return $user;
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=BeneficiarioApiTest
```

Esperado: FAIL — as rotas nao existem, entao os testes recebem 404 onde esperam 200/403/422.

- [ ] **Step 3: Implementar o controller**

Criar `app/Http/Controllers/Api/V1/Cisterna/BeneficiarioApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Requests\Api\ListarBeneficiariosRequest;
use App\Modules\Cisterna\Resources\BeneficiarioIndexResource;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\Cisterna\Services\BeneficiarioService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Tag(
 *     name="Cisternas",
 *     description="Projeto Cisterna — cadastro do beneficiario e a cadeia de vistoria em tres etapas (fornecedor, COMPDEC, CEDEC). Somente leitura."
 * )
 */
class BeneficiarioApiController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $service,
        private readonly BeneficiarioExportService $exportService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios",
     *     summary="Lista beneficiarios do Projeto Cisterna",
     *     description="Lista paginada. O recorte territorial vem do usuario dono do token: orgao COMPDEC ve somente o proprio municipio, e a role cisterna_fornecedor ve somente obras em envio_instalacao ou instalado. O recorte vence o filtro `municipio_id`. Atencao: 516 dos registros tem `situacao_analise = duplicado` e sao tombstone do legado, nao cadastro ativo — filtre-os em analise.",
     *     operationId="cisternasBeneficiariosIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1, example=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100. Acima disso a resposta e 422.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comunidade_id", in="query", required=false, description="Aceita lista separada por virgula.", @OA\Schema(type="string", example="12,34")),
     *     @OA\Parameter(name="situacao_analise", in="query", required=false, description="Aceita lista separada por virgula.", @OA\Schema(type="string", example="aprovado,ressalva")),
     *     @OA\Parameter(name="situacao_obra", in="query", required=false, @OA\Schema(type="string", example="instalado")),
     *     @OA\Parameter(name="lote_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="ordem_servico_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="cpf", in="query", required=false, description="Prefixo. Aceita com ou sem mascara.", @OA\Schema(type="string", example="123456789")),
     *     @OA\Parameter(name="search", in="query", required=false, description="Busca por nome, apoiada em indice GIN pg_trgm.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="data_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="atendido_por_pipa", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="numero_instalacao", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="etapa_concluida", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="etapa_pendente", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="ranqueamento", in="query", required=false, description="Quando verdadeiro, substitui a ordenacao pela ordem de ranqueamento.", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"nome","cpf","situacao_analise","situacao_obra","municipio","comunidade","etapas"})),
     *     @OA\Parameter(name="direction", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaBeneficiarioItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.beneficiarios.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarBeneficiariosRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaBeneficiario::class);

        $pagina = $this->service->listar(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
            $request->porPagina(),
        );

        return BeneficiarioIndexResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios/{id}",
     *     summary="Detalhe do beneficiario",
     *     description="Traz criterios sociais, avaliacao tecnica do telhado, atendimento por pipa, as vistorias das tres etapas com os itens conferidos, as notificacoes e a midia. Um usuario de orgao COMPDEC recebe 403 ao pedir beneficiario de outro municipio.",
     *     operationId="cisternasBeneficiariosShow",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=4201)),
     *     @OA\Response(
     *         response=200,
     *         description="Beneficiario",
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CisternaBeneficiarioDetail"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Fora do territorio do usuario ou sem permissao", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Nao encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(CisternaBeneficiario $beneficiario): BeneficiarioResource
    {
        // Policy por instancia, e nao middleware can:, porque o recorte
        // territorial do COMPDEC depende do municipio DESTE registro.
        $this->authorize('view', $beneficiario);

        $beneficiario->load([
            'municipio:id,nome,uf',
            'comunidade:id,nome',
            'ordemServico:id,nome,lote_id',
            'ordemServico.lote:id,nome',
            'vistorias.itensConferidos',
            'atendimentosPipa',
            'notificacoes',
            'media',
        ]);

        return BeneficiarioResource::make($beneficiario);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/beneficiarios/export",
     *     summary="Exporta beneficiarios em CSV",
     *     description="CSV streamado, sem teto de linhas — aceita os mesmos filtros do index e aplica o mesmo recorte de perfil. Exige a permissao cisternas.beneficiarios.export, separada da de leitura.",
     *     operationId="cisternasBeneficiariosExport",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="situacao_analise", in="query", required=false, @OA\Schema(type="string", example="aprovado")),
     *     @OA\Parameter(name="situacao_obra", in="query", required=false, @OA\Schema(type="string", example="instalado")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo CSV",
     *         @OA\MediaType(mediaType="text/csv", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.beneficiarios.export", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function export(ListarBeneficiariosRequest $request): StreamedResponse
    {
        $this->authorize('export', CisternaBeneficiario::class);

        return $this->exportService->streamCsv(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
        );
    }
}
```

> Conferir a assinatura real de `BeneficiarioExportService::streamCsv()` antes de rodar (`grep -n "public function streamCsv" -A 4 app/Modules/Cisterna/Services/BeneficiarioExportService.php`). No arquivo atual ela e `streamCsv(PerfilCisterna $perfil, array $filtros = []): StreamedResponse`.

- [ ] **Step 4: Registrar as rotas**

Em `routes/api.php`, acrescentar aos imports do topo:

```php
use App\Http\Controllers\Api\V1\Cisterna\BeneficiarioApiController;
```

E, **dentro** do grupo `Route::prefix('v1')->middleware([...])->group(function () {` existente (o que ja aplica `auth:sanctum`, `CheckUserActive` e `statement_timeout:10000`), depois do bloco do PAE:

```php
    // ========================================================================
    // Modulo CISTERNA — somente leitura
    // ========================================================================
    // O recorte territorial nao cabe em middleware: depende do usuario dono do
    // token e, no `show`, da instancia do registro. `can:` cobre a permissao;
    // PerfilCisterna e a policy cobrem o territorio.
    Route::prefix('cisternas')->name('api.v1.cisternas.')->group(function (): void {

        Route::prefix('beneficiarios')->name('beneficiarios.')->group(function (): void {
            Route::get('/', [BeneficiarioApiController::class, 'index'])
                ->name('index')
                ->middleware('can:cisternas.beneficiarios.view');

            // Antes do /{id}: sem isto, "export" casa com o parametro e o
            // whereNumber devolveria 404 em vez de servir o CSV.
            Route::get('/export', [BeneficiarioApiController::class, 'export'])
                ->name('export')
                ->middleware('can:cisternas.beneficiarios.export');

            Route::get('/{beneficiario}', [BeneficiarioApiController::class, 'show'])
                ->name('show')
                ->whereNumber('beneficiario');
        });
    });
```

> O `show` **nao** leva `can:`: a policy `CisternaBeneficiarioPolicy::view()` ja checa a permissao **e** o territorio, e um `can:` antes dela devolveria 403 sem distinguir os dois motivos.

- [ ] **Step 5: Rodar o teste e confirmar que passa**

```bash
docker exec newsdc_dev_app php artisan route:clear
docker exec newsdc_dev_app php artisan test --filter=BeneficiarioApiTest
```

Esperado: PASS, 12 testes.

- [ ] **Step 6: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Http/Controllers/Api/V1/Cisterna/ routes/api.php tests/Feature/Cisterna/Api/BeneficiarioApiTest.php
git add app/Http/Controllers/Api/V1/Cisterna/BeneficiarioApiController.php routes/api.php tests/Feature/Cisterna/Api/BeneficiarioApiTest.php
git commit -m "✨ feat(cisterna): API de beneficiarios com Swagger e recorte de perfil"
```

---

## Task 6: Vistorias na API

**Files:**
- Create: `app/Http/Controllers/Api/V1/Cisterna/VistoriaApiController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Cisterna/Api/VistoriaApiTest.php`

**Interfaces:**
- Consumes: `VistoriaService::listar(PerfilCisterna, array, int)` (Task 2); `ListarVistoriasRequest` (Task 4); `VistoriaResource`; `CisternaVistoriaPolicy` (`viewAny`, `view`).
- Produces: rotas `api.v1.cisternas.vistorias.index` e `.show`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/VistoriaApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VistoriaApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function test_sem_token_devolve_401(): void
    {
        $this->getJson('/api/v1/cisternas/vistorias')->assertUnauthorized();
    }

    public function test_sem_permissao_devolve_403(): void
    {
        $this->actingAs($this->usuario(), 'sanctum')
            ->getJson('/api/v1/cisternas/vistorias')
            ->assertForbidden();
    }

    public function test_index_devolve_etapa_como_objeto_com_valor_e_rotulo(): void
    {
        $user = $this->usuario('cisternas.vistorias.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/vistorias?per_page=5')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'beneficiario_id', 'etapa' => ['valor', 'rotulo'], 'concluida', 'engenheiro' => ['nome', 'crea'], 'local']],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_filtra_por_etapa(): void
    {
        $user = $this->usuario('cisternas.vistorias.view');

        $valores = collect(
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/cisternas/vistorias?etapa=cedec&per_page=50')
                ->assertOk()
                ->json('data')
        )->pluck('etapa.valor')->unique()->all();

        $this->assertSame([EtapaVistoria::CEDEC->value], $valores);
    }

    public function test_index_recusa_etapa_invalida(): void
    {
        $user = $this->usuario('cisternas.vistorias.view');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cisternas/vistorias?etapa=fornecedo')
            ->assertStatus(422)
            ->assertJsonValidationErrors('etapa');
    }

    public function test_numero_de_instalacao_e_nulo_fora_da_etapa_do_fornecedor(): void
    {
        $user = $this->usuario('cisternas.vistorias.view');

        $numeros = collect(
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/cisternas/vistorias?etapa=compdec&per_page=50')
                ->assertOk()
                ->json('data')
        )->pluck('numero_instalacao')->unique()->all();

        // Contrato documentado no Swagger: so a etapa do fornecedor aloca o
        // numero do QR Code. Medido no banco: 794 de 794 no fornecedor, 0 nas
        // outras duas.
        $this->assertSame([null], $numeros);
    }

    public function test_compdec_nao_ve_vistoria_de_outro_municipio(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC VIST '.uniqid(),
            'codigo' => 'COMPDEC-VIST-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.vistorias.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $fora = $this->vistoriaEm($this->municipios[1]);

        $ids = collect(
            $this->actingAs($user->fresh(), 'sanctum')
                ->getJson('/api/v1/cisternas/vistorias?per_page=100')
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();

        $this->assertNotContains($fora->id, $ids);
    }

    public function test_show_traz_os_itens_conferidos(): void
    {
        $user = $this->usuario('cisternas.vistorias.view');
        $vistoria = $this->vistoriaEm($this->municipios[0]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/cisternas/vistorias/{$vistoria->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vistoria->id)
            ->assertJsonStructure(['data' => ['id', 'etapa' => ['valor', 'rotulo'], 'itens', 'local', 'engenheiro']]);
    }

    public function test_show_de_outro_municipio_devolve_403_para_compdec(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC VIST '.uniqid(),
            'codigo' => 'COMPDEC-VIST-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.vistorias.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $fora = $this->vistoriaEm($this->municipios[1]);

        $this->actingAs($user->fresh(), 'sanctum')
            ->getJson("/api/v1/cisternas/vistorias/{$fora->id}")
            ->assertForbidden();
    }

    private function vistoriaEm(int $municipioId): CisternaVistoria
    {
        $beneficiario = CisternaBeneficiario::factory()->create(['municipio_id' => $municipioId]);

        return CisternaVistoria::factory()->create([
            'beneficiario_id' => $beneficiario->id,
            'etapa' => EtapaVistoria::COMPDEC->value,
        ]);
    }

    private function usuario(string ...$permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        if ($permissoes !== []) {
            $user->givePermissionTo($permissoes);
        }

        return $user;
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=VistoriaApiTest
```

Esperado: FAIL com 404 nas rotas.

- [ ] **Step 3: Implementar o controller**

Criar `app/Http/Controllers/Api/V1/Cisterna/VistoriaApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Requests\Api\ListarVistoriasRequest;
use App\Modules\Cisterna\Resources\VistoriaResource;
use App\Modules\Cisterna\Services\VistoriaService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VistoriaApiController extends Controller
{
    public function __construct(
        private readonly VistoriaService $service,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/vistorias",
     *     summary="Lista vistorias das tres etapas",
     *     description="Uma linha por etapa do mesmo documento, com `unique (beneficiario_id, etapa)`. No legado eram tres tabelas separadas (sinc_cisterna_rel_fornecedor, _rel_compdec, _rel_cedec). ATENCAO: `numero_instalacao` e preenchido SOMENTE na etapa `fornecedor` — nas etapas `compdec` e `cedec` e sempre nulo, por contrato, nao por falta de dado. Os campos `dados_administrativos` aparecem somente na etapa `cedec`.",
     *     operationId="cisternasVistoriasIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="etapa", in="query", required=false, @OA\Schema(type="string", enum={"fornecedor","compdec","cedec"})),
     *     @OA\Parameter(name="beneficiario_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comunidade_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="numero_instalacao", in="query", required=false, description="Numero do QR Code. Existe somente na etapa fornecedor.", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="concluida", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="data_relatorio_inicio", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="data_relatorio_fim", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaVistoriaItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.vistorias.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarVistoriasRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaVistoria::class);

        $pagina = $this->service->listar(
            PerfilCisterna::deUsuario($request->user()),
            $request->filtros(),
            $request->porPagina(),
        );

        return VistoriaResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/vistorias/{id}",
     *     summary="Detalhe da vistoria com o checklist conferido",
     *     description="Traz `itens`: uma entrada por item de instalacao conferido (13 itens no enum ItemInstalacao). No legado o checklist eram ~87 colunas espalhadas pelas tres tabelas, com nomes divergentes entre elas. O item `fixacao` traz as subquantidades (abracadeira, bucha, parafuso) em `detalhes`.",
     *     operationId="cisternasVistoriasShow",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=8088)),
     *     @OA\Response(
     *         response=200,
     *         description="Vistoria",
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/CisternaVistoriaItem"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Fora do territorio do usuario ou sem permissao", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Nao encontrada", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(CisternaVistoria $vistoria): VistoriaResource
    {
        // `beneficiario` precisa estar carregado ANTES da policy: o
        // dentroDoTerritorio() le $vistoria->beneficiario?->municipio_id, e sem
        // o load ele dispara uma consulta lazy por chamada.
        $vistoria->load([
            'beneficiario:id,nome,cpf,municipio_id',
            'beneficiario.municipio:id,nome,uf',
            'itensConferidos',
            'notificacoes',
            'media',
        ]);

        $this->authorize('view', $vistoria);

        return VistoriaResource::make($vistoria);
    }
}
```

- [ ] **Step 4: Registrar as rotas**

Em `routes/api.php`, acrescentar ao import:

```php
use App\Http\Controllers\Api\V1\Cisterna\VistoriaApiController;
```

E, dentro do grupo `Route::prefix('cisternas')->name('api.v1.cisternas.')` criado na Task 5, depois do bloco `beneficiarios`:

```php
        Route::prefix('vistorias')->name('vistorias.')->group(function (): void {
            Route::get('/', [VistoriaApiController::class, 'index'])
                ->name('index')
                ->middleware('can:cisternas.vistorias.view');

            Route::get('/{vistoria}', [VistoriaApiController::class, 'show'])
                ->name('show')
                ->whereNumber('vistoria');
        });
```

> **Cuidado com o binder do TDAP.** `routes/modules/tdap.php` registra `Route::model()` explicitos porque tem `/tdap/vistorias/{vistoria}` e o binder explicito vence o implicito no `SubstituteBindings`. Confirmar que o `{vistoria}` desta rota resolve para `CisternaVistoria` rodando `test_show_traz_os_itens_conferidos`. Se resolver para o model do TDAP, acrescentar o binder explicito na rota:
>
> ```php
> Route::get('/{vistoria}', [VistoriaApiController::class, 'show'])
>     ->name('show')
>     ->whereNumber('vistoria')
>     ->missing(fn () => abort(404));
> ```
>
> e, se o conflito persistir, trocar o nome do parametro para `{cisternaVistoria}` com `whereNumber('cisternaVistoria')` e ajustar a assinatura do metodo para `show(CisternaVistoria $cisternaVistoria)`.

- [ ] **Step 5: Rodar o teste e confirmar que passa**

```bash
docker exec newsdc_dev_app php artisan route:clear
docker exec newsdc_dev_app php artisan test --filter=VistoriaApiTest
```

Esperado: PASS, 9 testes.

- [ ] **Step 6: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Http/Controllers/Api/V1/Cisterna/ routes/api.php tests/Feature/Cisterna/Api/VistoriaApiTest.php
git add app/Http/Controllers/Api/V1/Cisterna/VistoriaApiController.php routes/api.php tests/Feature/Cisterna/Api/VistoriaApiTest.php
git commit -m "✨ feat(cisterna): API de vistorias com filtro por etapa e checklist"
```

---

## Task 7: Comunidades, lotes, ordens de servico e notificacoes na API

**Files:**
- Create: `app/Http/Controllers/Api/V1/Cisterna/ApoioApiController.php`
- Create: `app/Http/Controllers/Api/V1/Cisterna/NotificacaoApiController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Cisterna/Api/ApoioApiTest.php`

**Interfaces:**
- Consumes: `ComunidadeService::listar(array, int, ?PerfilCisterna)` e `NotificacaoFiscalizacaoService::listar(array, int, ?PerfilCisterna)` (Task 3); `LoteService::listar(int)`; `OrdemServicoService::listar(?int, int)`; `ListarComunidadesRequest`, `ListarNotificacoesRequest`, `ListarPaginadoRequest` (Task 4); `ComunidadeResource`, `LoteResource`, `OrdemServicoResource`, `NotificacaoResource`; policies `CisternaComunidadePolicy`, `CisternaLotePolicy`, `CisternaOrdemServicoPolicy`, `CisternaNotificacaoPolicy` (`viewAny`).
- Produces: rotas `api.v1.cisternas.comunidades.index`, `.lotes.index`, `.ordens-servico.index`, `.notificacoes.index`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Cisterna/Api/ApoioApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cisterna\Api;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApoioApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, int> */
    private array $municipios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->municipios = DB::table('municipios')->limit(2)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function recursos(): array
    {
        return [
            ['/api/v1/cisternas/comunidades', 'cisternas.comunidades.view'],
            ['/api/v1/cisternas/lotes', 'cisternas.lotes.view'],
            ['/api/v1/cisternas/ordens-servico', 'cisternas.ordens-servico.view'],
            ['/api/v1/cisternas/notificacoes', 'cisternas.notificacoes.view'],
        ];
    }

    /** @dataProvider recursos */
    public function test_sem_token_devolve_401(string $url, string $permissao): void
    {
        $this->getJson($url)->assertUnauthorized();
    }

    /** @dataProvider recursos */
    public function test_sem_permissao_devolve_403(string $url, string $permissao): void
    {
        $this->actingAs($this->usuario(), 'sanctum')->getJson($url)->assertForbidden();
    }

    /** @dataProvider recursos */
    public function test_com_permissao_devolve_lista_paginada(string $url, string $permissao): void
    {
        $this->actingAs($this->usuario($permissao), 'sanctum')
            ->getJson($url.'?per_page=5')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']])
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_comunidade_traz_municipio_e_contagem_de_beneficiarios(): void
    {
        $this->actingAs($this->usuario('cisternas.comunidades.view'), 'sanctum')
            ->getJson('/api/v1/cisternas/comunidades?per_page=5')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'nome', 'ativa', 'municipio' => ['id', 'nome', 'uf'], 'beneficiarios']]]);
    }

    public function test_compdec_ve_apenas_comunidades_do_proprio_municipio(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC APOIO '.uniqid(),
            'codigo' => 'COMPDEC-APOIO-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.comunidades.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $fora = CisternaComunidade::factory()->create(['municipio_id' => $this->municipios[1]]);

        $ids = collect(
            $this->actingAs($user->fresh(), 'sanctum')
                ->getJson('/api/v1/cisternas/comunidades?per_page=100')
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();

        $this->assertNotContains($fora->id, $ids);
    }

    public function test_lote_nao_tem_recorte_territorial_para_compdec(): void
    {
        $orgao = Orgao::create([
            'nome' => 'COMPDEC LOTE '.uniqid(),
            'codigo' => 'COMPDEC-LOTE-'.uniqid(),
            'tipo' => TipoOrgao::COMPDEC->value,
            'municipio_id' => $this->municipios[0],
        ]);
        $user = $this->usuario('cisternas.lotes.view');
        $user->update(['orgao_principal_id' => $orgao->id]);

        $lote = CisternaLote::factory()->create();

        $ids = collect(
            $this->actingAs($user->fresh(), 'sanctum')
                ->getJson('/api/v1/cisternas/lotes?per_page=100')
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();

        // Contrato explicito: cisterna_lotes nao tem municipio_id.
        $this->assertContains($lote->id, $ids);
    }

    public function test_ordem_de_servico_filtra_por_lote(): void
    {
        $lote = CisternaLote::factory()->create();

        $this->actingAs($this->usuario('cisternas.ordens-servico.view'), 'sanctum')
            ->getJson('/api/v1/cisternas/ordens-servico?lote_id='.$lote->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_notificacao_recusa_tipo_de_notificavel_invalido(): void
    {
        $this->actingAs($this->usuario('cisternas.notificacoes.view'), 'sanctum')
            ->getJson('/api/v1/cisternas/notificacoes?notificavel_type=processo&notificavel_id=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors('notificavel_type');
    }

    public function test_notificacao_devolve_alias_curto_do_notificavel(): void
    {
        $resposta = $this->actingAs($this->usuario('cisternas.notificacoes.view'), 'sanctum')
            ->getJson('/api/v1/cisternas/notificacoes?per_page=10')
            ->assertOk();

        foreach ($resposta->json('data') as $item) {
            $this->assertContains($item['notificavel']['tipo'], ['beneficiario', 'vistoria', null]);
        }
    }

    private function usuario(string ...$permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        if ($permissoes !== []) {
            $user->givePermissionTo($permissoes);
        }

        return $user;
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec newsdc_dev_app php artisan test --filter=ApoioApiTest
```

Esperado: FAIL com 404 nas quatro rotas.

- [ ] **Step 3: Implementar o `ApoioApiController`**

Criar `app/Http/Controllers/Api/V1/Cisterna/ApoioApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Requests\Api\ListarComunidadesRequest;
use App\Modules\Cisterna\Requests\Api\ListarPaginadoRequest;
use App\Modules\Cisterna\Resources\ComunidadeResource;
use App\Modules\Cisterna\Resources\LoteResource;
use App\Modules\Cisterna\Resources\OrdemServicoResource;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\LoteService;
use App\Modules\Cisterna\Services\OrdemServicoService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * As tres listagens de referencia do modulo: comunidade, lote e ordem de
 * servico. Juntas num controller porque nenhuma tem regra propria alem de
 * paginar — tres arquivos de trinta linhas nao ajudariam ninguem.
 */
class ApoioApiController extends Controller
{
    // Sufixo `Service` nas propriedades de proposito: sem ele, `$this->lotes`
    // (propriedade) e `$this->lotes()` (metodo) leem como a mesma coisa e nao
    // sao -- PHP aceita, quem revisa tropeca.
    public function __construct(
        private readonly ComunidadeService $comunidadeService,
        private readonly LoteService $loteService,
        private readonly OrdemServicoService $ordemServicoService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/comunidades",
     *     summary="Lista comunidades atendidas",
     *     description="840 comunidades em 55 municipios. `beneficiarios` e a contagem por `comunidade_id`, nao por nome: no legado o join era por nome de comunidade sem o municipio, e os 75 nomes que existem em mais de um municipio somavam a contagem entre eles. Recorte territorial: orgao COMPDEC ve somente o proprio municipio.",
     *     operationId="cisternasComunidadesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="municipio_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="apenas_ativas", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaComunidadeItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.comunidades.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function comunidades(ListarComunidadesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaComunidade::class);

        $pagina = $this->comunidadeService->listar(
            $request->filtros(),
            $request->porPagina(),
            PerfilCisterna::deUsuario($request->user()),
        );

        return ComunidadeResource::collection($pagina);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/lotes",
     *     summary="Lista lotes de contratacao",
     *     description="SEM recorte territorial, por contrato: `cisterna_lotes` nao tem `municipio_id` — o lote e nacional, e um COMPDEC precisa ve-lo para saber em que lote esta a propria ordem de servico. `ordens_servico` e a contagem de OS do lote.",
     *     operationId="cisternasLotesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaLoteItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.lotes.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function lotes(ListarPaginadoRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaLote::class);

        return LoteResource::collection($this->loteService->listar($request->porPagina()));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/ordens-servico",
     *     summary="Lista ordens de servico",
     *     description="SEM recorte territorial, mesma razao do lote. `documento_url` e a URL do processo no SEI vinda do legado; `documento_anexo` e arquivo anexado no NewSDC, que o legado nao tinha.",
     *     operationId="cisternasOrdensServicoIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="lote_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaOrdemServicoItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.ordens-servico.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function ordensServico(ListarPaginadoRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaOrdemServico::class);

        $loteId = $request->filled('lote_id') ? $request->integer('lote_id') : null;

        return OrdemServicoResource::collection(
            $this->ordemServicoService->listar($loteId, $request->porPagina())
        );
    }
}
```

- [ ] **Step 4: Implementar o `NotificacaoApiController`**

Criar `app/Http/Controllers/Api/V1/Cisterna/NotificacaoApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Requests\Api\ListarNotificacoesRequest;
use App\Modules\Cisterna\Resources\NotificacaoResource;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacaoApiController extends Controller
{
    public function __construct(
        private readonly NotificacaoFiscalizacaoService $service,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cisternas/notificacoes",
     *     summary="Lista notificacoes de fiscalizacao",
     *     description="Notificacao polimorfica: o `notificavel` e um beneficiario ou uma vistoria, identificado pelo alias curto (`beneficiario` / `vistoria`), nao pelo FQCN. O recorte territorial cobre as duas pontas — o beneficiario tem `municipio_id`, e a vistoria chega ao municipio pela relacao. As 7 linhas migradas do legado sao dado de teste.",
     *     operationId="cisternasNotificacoesIndex",
     *     tags={"Cisternas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Maximo 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *     @OA\Parameter(name="notificavel_type", in="query", required=false, @OA\Schema(type="string", enum={"beneficiario","vistoria"})),
     *     @OA\Parameter(name="notificavel_id", in="query", required=false, description="Obrigatorio quando notificavel_type e informado.", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="apenas_pendentes", in="query", required=false, description="Somente as ainda nao respondidas.", @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CisternaNotificacaoItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissao cisternas.notificacoes.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtro invalido", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ListarNotificacoesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CisternaNotificacao::class);

        $pagina = $this->service->listar(
            $request->filtros(),
            $request->porPagina(),
            PerfilCisterna::deUsuario($request->user()),
        );

        return NotificacaoResource::collection($pagina);
    }
}
```

- [ ] **Step 5: Registrar as rotas**

Em `routes/api.php`, acrescentar aos imports:

```php
use App\Http\Controllers\Api\V1\Cisterna\ApoioApiController;
use App\Http\Controllers\Api\V1\Cisterna\NotificacaoApiController;
```

E, dentro do grupo `Route::prefix('cisternas')->name('api.v1.cisternas.')`, depois do bloco `vistorias`:

```php
        Route::get('comunidades', [ApoioApiController::class, 'comunidades'])
            ->name('comunidades.index')
            ->middleware('can:cisternas.comunidades.view');

        Route::get('lotes', [ApoioApiController::class, 'lotes'])
            ->name('lotes.index')
            ->middleware('can:cisternas.lotes.view');

        Route::get('ordens-servico', [ApoioApiController::class, 'ordensServico'])
            ->name('ordens-servico.index')
            ->middleware('can:cisternas.ordens-servico.view');

        Route::get('notificacoes', [NotificacaoApiController::class, 'index'])
            ->name('notificacoes.index')
            ->middleware('can:cisternas.notificacoes.view');
```

- [ ] **Step 6: Rodar o teste e confirmar que passa**

```bash
docker exec newsdc_dev_app php artisan route:clear
docker exec newsdc_dev_app php artisan test --filter=ApoioApiTest
```

Esperado: PASS, 19 testes (12 do data provider + 7 proprios).

- [ ] **Step 7: Pint e commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Http/Controllers/Api/V1/Cisterna/ routes/api.php tests/Feature/Cisterna/Api/ApoioApiTest.php
git add app/Http/Controllers/Api/V1/Cisterna/ApoioApiController.php app/Http/Controllers/Api/V1/Cisterna/NotificacaoApiController.php routes/api.php tests/Feature/Cisterna/Api/ApoioApiTest.php
git commit -m "✨ feat(cisterna): API de comunidades, lotes, OS e notificacoes"
```

---

## Task 8: Schemas Swagger e o mapeamento legado -> dominio -> payload

Os `$ref` usados nas Tasks 5 a 7 apontam para schemas que ainda nao existem. Sem eles o `l5-swagger:generate` gera referencia quebrada e a UI mostra o endpoint sem corpo de resposta.

As `description` de cada propriedade sao **o entregavel do mapeamento**: a origem no legado e as ressalvas de migracao ficam onde o consumidor da API le, e nao num documento que ninguem abre. Fonte dos nomes de coluna do legado: `database/data/Cisternas.sql` (80 colunas em `sinc_cisterna`, 53 em `_rel_fornecedor`, 39 em `_rel_compdec`, 27 em `_rel_cedec`, 6 em `_com`, 6 em `_lotes`, 7 em `_ordem_servico`, 7 em `_notificacoes`), conferidos contra `app/Modules/Cisterna/Domain/Etl/Refinadores/`.

**Files:**
- Modify: `app/Http/Controllers/Api/SwaggerController.php` (582 linhas hoje; os schemas entram no bloco de docblock, depois de `RatProtocoloDetail`)

**Interfaces:**
- Consumes: `PaginationMeta`, `PaginationLinks` (definidos em `Api/V1/Pae/EmpreendimentoController.php`) e `ErrorResponse` (em `SwaggerController.php`) — todos ja existem, **nao redefinir**.
- Produces: `CisternaBeneficiarioItem`, `CisternaBeneficiarioDetail`, `CisternaVistoriaItem`, `CisternaItemConferido`, `CisternaComunidadeItem`, `CisternaLoteItem`, `CisternaOrdemServicoItem`, `CisternaNotificacaoItem`.

- [ ] **Step 1: Conferir o que ja existe, para nao duplicar**

```bash
grep -n 'schema="Pagination\|schema="ErrorResponse\|schema="PaginatedResponse' app/Http/Controllers/Api/SwaggerController.php app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php
```

Esperado: `ErrorResponse`, `PaginatedResponse` e `SuccessResponse` em `SwaggerController.php`; `PaginationMeta` e `PaginationLinks` em `EmpreendimentoController.php`. Schema duplicado faz o `swagger-php` avisar e a ultima definicao vencer — nao redefinir nenhum dos cinco.

- [ ] **Step 2: Acrescentar os schemas**

Em `app/Http/Controllers/Api/SwaggerController.php`, dentro do docblock principal, **antes** do `*/` de fechamento:

```php
 * @OA\Schema(
 *     schema="CisternaBeneficiarioItem",
 *     type="object",
 *     title="Beneficiario do Projeto Cisterna (listagem)",
 *     description="Formato reduzido da listagem. Origem no legado: tabela `sinc_cisterna` (80 colunas).",
 *     @OA\Property(property="id", type="integer", example=4201),
 *     @OA\Property(property="cpf", type="string", nullable=true, description="11 digitos, sem mascara. Legado: `sinc_cisterna.cpf` varchar(14) com mascara. 5 cadastros nao foram importados por CPF truncado na origem.", example="05924079659"),
 *     @OA\Property(property="nome", type="string", example="Maria Aparecida de Souza"),
 *     @OA\Property(property="municipio", type="string", nullable=true, description="Nome. Legado: `sinc_cisterna.codmundv` (codigo IBGE), traduzido para `municipios.id` pela ponte PonteMunicipio.", example="Janauba"),
 *     @OA\Property(property="comunidade", type="string", nullable=true, description="Legado: `sinc_cisterna.comunidade` varchar(34) texto livre, normalizado em `cisterna_comunidades`."),
 *     @OA\Property(property="situacao_analise", type="object", description="Legado: `sinc_cisterna.aprovado` int. ATENCAO: `duplicado` (valor 5 no legado) e tombstone, nao cadastro ativo — 516 registros. Filtre em analise.",
 *         @OA\Property(property="valor", type="string", enum={"em_edicao","aprovado","reprovado","ressalva","desconsiderado","duplicado"}, example="aprovado"),
 *         @OA\Property(property="rotulo", type="string", example="Aprovado")
 *     ),
 *     @OA\Property(property="situacao_obra", type="object", description="Legado: `sinc_cisterna.estado` (0..2). Ortogonal a situacao_analise.",
 *         @OA\Property(property="valor", type="string", enum={"processamento","envio_instalacao","instalado"}, example="instalado"),
 *         @OA\Property(property="rotulo", type="string", example="Instalado")
 *     ),
 *     @OA\Property(property="ranqueamento_ordem", type="integer", nullable=true, description="Ordem de prioridade social. Nulo na maioria: e ordenavel, nao calculado pelo sistema."),
 *     @OA\Property(property="lote", type="string", nullable=true, description="Nome do lote da ordem de servico do beneficiario."),
 *     @OA\Property(property="ordem_servico", type="string", nullable=true),
 *     @OA\Property(property="etapas_concluidas", type="array", description="Etapas de vistoria ja concluidas. Substitui os tres whereHas aninhados do legado.", @OA\Items(type="string", enum={"fornecedor","compdec","cedec"})),
 *     @OA\Property(property="numero_instalacao", type="integer", nullable=true, description="Numero do QR Code colado na cisterna. Alocado SOMENTE na etapa `fornecedor`.", example=1247)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaBeneficiarioDetail",
 *     type="object",
 *     title="Beneficiario do Projeto Cisterna (detalhe)",
 *     description="Formato completo. Origem: `sinc_cisterna` (80 colunas) mais as tabelas de relatorio. As ~54 colunas de caminho de arquivo do legado sairam do dominio: os arquivos vivem em collections do Spatie MediaLibrary.",
 *     @OA\Property(property="id", type="integer", example=4201),
 *     @OA\Property(property="cpf", type="string", nullable=true, example="05924079659"),
 *     @OA\Property(property="nome", type="string", example="Maria Aparecida de Souza"),
 *     @OA\Property(property="telefone", type="string", nullable=true),
 *     @OA\Property(property="data_nascimento", type="string", format="date", nullable=true),
 *     @OA\Property(property="cadastro_unico", type="string", nullable=true, description="NIS. Legado: `sinc_cisterna.cad_unico`."),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer", example=1234),
 *         @OA\Property(property="nome", type="string", example="Janauba"),
 *         @OA\Property(property="uf", type="string", example="MG")
 *     ),
 *     @OA\Property(property="comunidade", type="object",
 *         @OA\Property(property="id", type="integer", nullable=true),
 *         @OA\Property(property="nome", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="endereco", type="string", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true, description="Legado: `sinc_cisterna.latitude` varchar(150) de texto livre, com 21 formatos distintos. 7.993 de 8.099 foram parseadas; o resto e perda irrecuperavel (truncada na origem) ou eixo trocado no cadastro. O valor original continua em `cisterna_legado_raw.doc`.", example=-15.8021456),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true, example=-43.9673012),
 *     @OA\Property(property="ordem_servico", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="nome", type="string"),
 *         @OA\Property(property="lote", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="situacao_analise", type="object",
 *         @OA\Property(property="valor", type="string", enum={"em_edicao","aprovado","reprovado","ressalva","desconsiderado","duplicado"}),
 *         @OA\Property(property="rotulo", type="string"),
 *         @OA\Property(property="observacao", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="situacao_obra", type="object",
 *         @OA\Property(property="valor", type="string", enum={"processamento","envio_instalacao","instalado"}),
 *         @OA\Property(property="rotulo", type="string")
 *     ),
 *     @OA\Property(property="ranqueamento_ordem", type="integer", nullable=true),
 *     @OA\Property(property="criterios_sociais", type="object", description="Criterios de elegibilidade do programa.",
 *         @OA\Property(property="qtd_pessoas", type="integer", nullable=true),
 *         @OA\Property(property="renda", type="number", format="float", nullable=true, description="SEM CENTAVOS: no legado `renda` e float(10,0), zero casas decimais. Perda na origem, nao na migracao — nenhuma das 8.099 linhas tinha centavos.", example=1412),
 *         @OA\Property(property="renda_per_capita", type="number", format="float", nullable=true),
 *         @OA\Property(property="possui_deficiencia", type="boolean", nullable=true),
 *         @OA\Property(property="possui_crianca", type="boolean", nullable=true),
 *         @OA\Property(property="data_nascimento_crianca", type="string", format="date", nullable=true),
 *         @OA\Property(property="possui_idoso", type="boolean", nullable=true),
 *         @OA\Property(property="chefiada_mulher", type="boolean", nullable=true)
 *     ),
 *     @OA\Property(property="avaliacao_tecnica", type="object", description="Medidas do telhado que definem a viabilidade da captacao.",
 *         @OA\Property(property="tipo_moradia", type="string", nullable=true, description="Legado: `moradia` varchar(7) em utf8mb3 — 'PROPRIA' com acento nao cabia e chegou como 'PR?PRIA' em 67 cadastros. 162 linhas gravaram o literal '0' (placeholder de nao respondido) e viraram nulo.", enum={"propria","cedida","alugada","outros"}),
 *         @OA\Property(property="tipo_moradia_outro", type="string", nullable=true),
 *         @OA\Property(property="comprimento_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="largura_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="area_telhado", type="number", format="float", nullable=true),
 *         @OA\Property(property="comprimento_testada", type="number", format="float", nullable=true),
 *         @OA\Property(property="num_caidas_telhado", type="integer", nullable=true),
 *         @OA\Property(property="cobertura_telhado", type="string", nullable=true, description="Legado: `coberturaTelhado`. 14 linhas com o literal '0' viraram nulo; as 434 'Ceramica' acentuadas casaram."),
 *         @OA\Property(property="cobertura_outro", type="string", nullable=true),
 *         @OA\Property(property="possui_fogao_lenha", type="boolean", nullable=true),
 *         @OA\Property(property="medida_telhado_area_fogao", type="number", format="float", nullable=true),
 *         @OA\Property(property="testada_disp_parte_fogao", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(property="atendimento_pipa", type="object", description="Legado: `atendPipa` varchar(36) que devia ser booleano. 34 cadastros gravaram ali o RESPONSAVEL ('prefeitura', 'respAtExercito', ...) em vez de sim/nao; o refino leu como atendido=sim e guardou o responsavel.",
 *         @OA\Property(property="atendido", type="boolean", nullable=true),
 *         @OA\Property(property="responsaveis", type="array", @OA\Items(type="object",
 *             @OA\Property(property="valor", type="string", enum={"prefeitura","exercito","defesa_civil","outros"}),
 *             @OA\Property(property="rotulo", type="string"),
 *             @OA\Property(property="descricao", type="string", nullable=true)
 *         ))
 *     ),
 *     @OA\Property(property="responsaveis_cadastro", type="object",
 *         @OA\Property(property="agente_nome", type="string", nullable=true),
 *         @OA\Property(property="agente_cpf", type="string", nullable=true),
 *         @OA\Property(property="engenheiro_nome", type="string", nullable=true),
 *         @OA\Property(property="engenheiro_crea", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="observacoes", type="string", nullable=true),
 *     @OA\Property(property="vistorias", type="array", @OA\Items(ref="#/components/schemas/CisternaVistoriaItem")),
 *     @OA\Property(property="notificacoes", type="array", @OA\Items(ref="#/components/schemas/CisternaNotificacaoItem")),
 *     @OA\Property(property="fotos_imovel", type="array", description="ATENCAO: 72% dos cadastros do legado NAO tem o arquivo aqui. As colunas `img_*` do legado guardavam o rotulo da foto ('FRENTE', 'FUNDO'), nao o caminho; o arquivo esta no Google Drive, e a URL foi preservada em `custom_properties.origem_legado`. Extrair ~5.800 arquivos do Drive e decisao de infraestrutura pendente.", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="thumb", type="string", nullable=true),
 *         @OA\Property(property="angulo", type="string", nullable=true),
 *         @OA\Property(property="observacao", type="string", nullable=true)
 *     )),
 *     @OA\Property(property="comprovantes", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="tipo", type="string", nullable=true),
 *         @OA\Property(property="nome", type="string")
 *     )),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="atualizado_em", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaVistoriaItem",
 *     type="object",
 *     title="Vistoria de instalacao",
 *     description="Uma linha por etapa do mesmo documento, com `unique (beneficiario_id, etapa)`. No legado eram tres tabelas: `sinc_cisterna_rel_fornecedor` (53 colunas), `sinc_cisterna_rel_compdec` (39) e `sinc_cisterna_rel_cedec` (27).",
 *     @OA\Property(property="id", type="integer", example=8088),
 *     @OA\Property(property="beneficiario_id", type="integer", example=4201),
 *     @OA\Property(property="etapa", type="object",
 *         @OA\Property(property="valor", type="string", enum={"fornecedor","compdec","cedec"}, example="fornecedor"),
 *         @OA\Property(property="rotulo", type="string", example="Relatorio do Fornecedor")
 *     ),
 *     @OA\Property(property="numero_instalacao", type="integer", nullable=true, description="Numero do QR Code. Alocado SOMENTE na etapa `fornecedor` — medido no banco: 794 de 794 no fornecedor, 0 em compdec e 0 em cedec. Nulo nas outras etapas e contrato, nao dado faltante.", example=1247),
 *     @OA\Property(property="concluida", type="boolean"),
 *     @OA\Property(property="concluida_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="engenheiro", type="object",
 *         @OA\Property(property="nome", type="string", nullable=true),
 *         @OA\Property(property="crea", type="string", nullable=true),
 *         @OA\Property(property="art", type="string", nullable=true, description="Preenchido somente na etapa cedec.")
 *     ),
 *     @OA\Property(property="data_relatorio", type="string", format="date", nullable=true),
 *     @OA\Property(property="local_relatorio", type="string", nullable=true),
 *     @OA\Property(property="dados_administrativos", type="object", nullable=true, description="Chave AUSENTE fora da etapa `cedec`: so ela preenche processo, contrato e empenho.",
 *         @OA\Property(property="processo_sei", type="string", nullable=true),
 *         @OA\Property(property="contrato", type="string", nullable=true),
 *         @OA\Property(property="empenho", type="string", nullable=true),
 *         @OA\Property(property="placa_obras", type="integer", nullable=true)
 *     ),
 *     @OA\Property(property="local", type="object",
 *         @OA\Property(property="endereco", type="string", nullable=true),
 *         @OA\Property(property="bairro", type="string", nullable=true),
 *         @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *         @OA\Property(property="longitude", type="number", format="float", nullable=true)
 *     ),
 *     @OA\Property(property="itens", type="array", description="Chave presente somente quando a relacao vem carregada (endpoint de detalhe).", @OA\Items(ref="#/components/schemas/CisternaItemConferido")),
 *     @OA\Property(property="observacoes", type="string", nullable=true),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaItemConferido",
 *     type="object",
 *     title="Item do checklist de instalacao",
 *     description="Uma linha por item conferido, polimorfica. No legado os 13 itens eram ~87 colunas espalhadas pelas tres tabelas de relatorio, com nomes divergentes entre elas (`calha_metros` numa, `qtd_calha` noutra, `calha_opcao` numa terceira).",
 *     @OA\Property(property="item", type="string", enum={"cisterna_logo","sucao","bomba","placa","calha","tubulacao","fixacao","filtro","bloco","te_pvc","joelho_pvc","luva_pvc","cap_pvc"}, example="calha"),
 *     @OA\Property(property="rotulo", type="string", example="Calha"),
 *     @OA\Property(property="conferido", type="boolean", nullable=true),
 *     @OA\Property(property="quantidade", type="number", format="float", nullable=true),
 *     @OA\Property(property="unidade", type="string", nullable=true, example="m"),
 *     @OA\Property(property="detalhes", type="object", nullable=true, description="Subquantidades que nao cabem numa coluna. Existe para `fixacao`, que no COMPDEC se desdobra em abracadeira, bucha e parafuso."),
 *     @OA\Property(property="observacao", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CisternaComunidadeItem",
 *     type="object",
 *     title="Comunidade atendida",
 *     description="Legado: `sinc_cisterna_com` (6 colunas). 840 comunidades em 55 municipios; 54 pares (municipio, nome) duplicados na origem foram deduplicados, e 58 nomes que existem em municipios distintos convivem como registros separados.",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string", example="Barreiro Grande"),
 *     @OA\Property(property="ativa", type="boolean"),
 *     @OA\Property(property="municipio", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="nome", type="string"),
 *         @OA\Property(property="uf", type="string")
 *     ),
 *     @OA\Property(property="beneficiarios", type="integer", description="Contagem por `comunidade_id`, nao por nome — o legado somava a contagem entre comunidades homonimas de municipios distintos.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaLoteItem",
 *     type="object",
 *     title="Lote de contratacao",
 *     description="Legado: `sinc_cisterna_lotes` (6 colunas), 3 linhas. Nao tem municipio: o lote e nacional e a listagem nao aplica recorte territorial.",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="data", type="string", format="date", nullable=true),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="ordens_servico", type="integer", description="Contagem de OS do lote.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaOrdemServicoItem",
 *     type="object",
 *     title="Ordem de servico",
 *     description="Legado: `sinc_cisterna_ordem_servico` (7 colunas), 7 linhas. Sem recorte territorial, mesma razao do lote.",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="lote", type="object",
 *         @OA\Property(property="id", type="integer", nullable=true),
 *         @OA\Property(property="nome", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="beneficiarios", type="integer"),
 *     @OA\Property(property="documento_url", type="string", nullable=true, description="URL do processo no SEI, vinda do legado. Nao e arquivo."),
 *     @OA\Property(property="documento_anexo", type="string", nullable=true, description="Arquivo anexado no NewSDC, que o legado nao tinha.")
 * )
 *
 * @OA\Schema(
 *     schema="CisternaNotificacaoItem",
 *     type="object",
 *     title="Notificacao de fiscalizacao",
 *     description="Legado: `sinc_cisterna_notificacoes` (7 colunas), 7 linhas — todas dado de teste. Polimorfica: o notificavel e um beneficiario ou uma vistoria.",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="notificavel", type="object",
 *         @OA\Property(property="tipo", type="string", nullable=true, enum={"beneficiario","vistoria"}, description="Alias curto, nao o FQCN."),
 *         @OA\Property(property="id", type="integer")
 *     ),
 *     @OA\Property(property="observacao", type="string", nullable=true),
 *     @OA\Property(property="respondida", type="boolean"),
 *     @OA\Property(property="respondida_em", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="emitida_por", type="string", nullable=true, description="Nome de quem emitiu. NULO em tudo que veio do legado: os 43 usuarios de origem nao mapeiam para o NewSDC (0 casam por CPF, 0 por e-mail). O `user_id` original continua em `cisterna_legado_raw.doc`."),
 *     @OA\Property(property="documentos", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="url", type="string"),
 *         @OA\Property(property="nome", type="string")
 *     )),
 *     @OA\Property(property="criado_em", type="string", format="date-time", nullable=true)
 * )
```

- [ ] **Step 3: Acrescentar a tag `Cisternas` ao bloco de tags globais**

A tag e declarada no `BeneficiarioApiController` (Task 5). Confirmar que **nao** ha uma segunda declaracao em `SwaggerController.php`:

```bash
grep -n 'name="Cisternas"' app/Http/Controllers/Api/SwaggerController.php app/Http/Controllers/Api/V1/Cisterna/*.php
```

Esperado: exatamente uma ocorrencia, em `BeneficiarioApiController.php`. Duas declaracoes fazem a segunda sobrescrever a primeira.

- [ ] **Step 4: Gerar a documentacao**

```bash
docker exec newsdc_dev_app php artisan l5-swagger:generate
```

Esperado: sem erro e sem aviso de `$ref` nao resolvida. Se aparecer `Unable to resolve @OA\Schema(...)`, o schema referenciado esta escrito com nome diferente do `$ref` — conferir a grafia exata dos oito nomes.

- [ ] **Step 5: Conferir que os oito schemas e os nove endpoints entraram no JSON**

```bash
docker exec newsdc_dev_app php -r "
\$doc = json_decode(file_get_contents('storage/api-docs/api-docs.json'), true);
foreach (['CisternaBeneficiarioItem','CisternaBeneficiarioDetail','CisternaVistoriaItem','CisternaItemConferido','CisternaComunidadeItem','CisternaLoteItem','CisternaOrdemServicoItem','CisternaNotificacaoItem'] as \$s) {
    echo \$s, ': ', isset(\$doc['components']['schemas'][\$s]) ? 'ok' : 'FALTANDO', PHP_EOL;
}
foreach (array_keys(\$doc['paths']) as \$p) { if (str_contains(\$p, 'cisternas')) { echo 'path ', \$p, PHP_EOL; } }
"
```

Esperado: oito `ok` e nove linhas `path` (beneficiarios index/show/export, vistorias index/show, comunidades, lotes, ordens-servico, notificacoes).

- [ ] **Step 6: Commit**

```bash
docker exec newsdc_dev_app vendor/bin/pint app/Http/Controllers/Api/SwaggerController.php
git add app/Http/Controllers/Api/SwaggerController.php storage/api-docs/api-docs.json
git commit -m "📝 docs(cisterna): schemas Swagger com mapeamento legado -> dominio"
```

> `storage/api-docs/api-docs.json` **nao** esta no `.gitignore` deste repositorio (verificado com `git check-ignore -v`), entao entra no commit — o `api-docs.json` versionado e o que a UI serve em producao sem depender de rodar o `generate` no deploy.

---

## Task 9: Verificacao de ponta a ponta

**Files:** nenhum. Esta task so verifica.

- [ ] **Step 1: Suite do modulo verde**

```bash
docker exec newsdc_dev_app php artisan test --filter=Cisterna
```

Esperado: PASS. Os testes pre-existentes do Cisterna (211 testes, 616 asserts conforme a nota de migracao) mais os ~47 novos das Tasks 1 a 7.

- [ ] **Step 2: Nenhuma regressao fora do modulo**

```bash
docker exec newsdc_dev_app php artisan test
```

Esperado: nenhuma falha **nova**. O baseline conhecido deste banco tem 1 erro e 5 falhas em `Pae`, `AjudaHumanitaria` e `PlanCon`, todas pre-existentes e pela mesma causa (testes que leem dado pre-existente do banco em vez de semear o proprio). Se o total de falhas subir de 6, investigar antes de seguir.

- [ ] **Step 3: Pint limpo no que foi tocado**

```bash
docker exec newsdc_dev_app vendor/bin/pint --test app/Http/Controllers/Api/V1/Cisterna app/Modules/Cisterna app/Http/Controllers/Api/SwaggerController.php routes/api.php tests/Feature/Cisterna
```

Esperado: `PASS`.

- [ ] **Step 4: Rotas registradas com os middlewares certos**

```bash
docker exec newsdc_dev_app php artisan route:list --path=api/v1/cisternas --columns=method,uri,name,middleware
```

Conferir em cada linha: `auth:sanctum`, `CheckUserActive`, `statement_timeout:10000` e o `can:` correspondente. O `show` de beneficiario e o de vistoria **nao** devem ter `can:` — quem autoriza ali e a policy.

- [ ] **Step 5: Emitir um token real e bater cada endpoint pela UI do Swagger**

1. Como usuario com `users.edit`, abrir `/admin/permissions/users/{id}`, secao **Tokens de API (Bearer / Sanctum)**, "Gerar Novo Token", nome `Swagger Cisterna`, expiracao `30 dias`. Copiar o `plainTextToken` (formato `{id}|{40-chars}`).
2. Garantir que o usuario do token tem as seis permissoes de leitura: `cisternas.beneficiarios.view`, `.export`, `cisternas.vistorias.view`, `cisternas.comunidades.view`, `cisternas.lotes.view`, `cisternas.ordens-servico.view`, `cisternas.notificacoes.view`.
3. Abrir `http://localhost:8000/api/documentation`, botao **Authorize**, colar **somente o token** (o prefixo `Bearer ` e adicionado pela UI).
4. Executar os nove endpoints da tag **Cisternas**.

- [ ] **Step 6: Conferir os totais da API contra o banco**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc -c "
select 'beneficiarios' recurso, count(*) from cisterna_beneficiarios
union all select 'vistorias', count(*) from cisterna_vistorias
union all select 'comunidades', count(*) from cisterna_comunidades
union all select 'lotes', count(*) from cisterna_lotes
union all select 'ordens_servico', count(*) from cisterna_ordens_servico
union all select 'notificacoes', count(*) from cisterna_notificacoes;"

docker exec newsdc_dev_db psql -U sdc -d sdc -c "
select etapa, count(*) total, count(numero_instalacao) com_numero
from cisterna_vistorias group by etapa order by etapa;"
```

Com um token de usuario **sem** orgao COMPDEC e **sem** a role de fornecedor (portanto sem recorte), o `meta.total` de cada endpoint deve casar com estes numeros: 8.099 / 2.136 / 840 / 3 / 7 / 7. E `etapa=fornecedor` deve dar 794 com `numero_instalacao` preenchido em todas, enquanto `etapa=compdec` e `etapa=cedec` dao 682 e 660 com `numero_instalacao` nulo em todas.

- [ ] **Step 7: Revogar o token de teste**

Em `/admin/permissions/users/{id}`, revogar o token `Swagger Cisterna`. Token com `['*']` de abilities e expiracao de 30 dias nao fica solto depois da verificacao.

---

## Fora do escopo, de proposito

- **Escrita pela API** (POST/PUT/DELETE, incluindo `concluir` vistoria) — decisao: somente leitura.
- **Endpoint de indicadores agregados** — nao pedido. YAGNI.
- **Recorte territorial nas telas web** de comunidades e notificacoes — lacuna pre-existente, decisao do dono. Este plano so passa o perfil pela API.
- **Baixar as ~5.800 fotos do Google Drive** — decisao de infraestrutura pendente com a area (nota de migracao, secao 5.6).
- **Reconciliar `created_by`** dos 8.099 registros importados — depende das contas COMPDEC existirem no NewSDC (nota, secao 5.7).
- **`sinc_cisterna_relatorio_cedec`** (2 linhas, orfa no legado, sem controller) — decisao D23, nao portada.
- **Derrubar a tabela orfa `cisternas`** do scaffold anterior — migration propria, revisavel, fora daqui.
