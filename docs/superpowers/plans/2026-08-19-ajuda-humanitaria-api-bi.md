# API Swagger de Ajuda Humanitaria Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor na API v1 do NewSDC os quatro contratos de Ajuda Humanitaria hoje servidos pelos endpoints publicos do Laravel legado, lendo do PostgreSQL novo, com documentacao Swagger.

**Architecture:** Rotas no grupo `v1` de `routes/api.php` sob o prefixo `ajuda-humanitaria`, protegidas por Sanctum e permissao. Tres controllers finos em `Api/V1/AjudaHumanitaria` delegam a services do modulo, que concentram a agregacao (cada contrato tem um agrupamento diferente). A forma do JSON fica em Resources do modulo. Duas colunas promovidas por migration com backfill a partir da staging `ajuda_h_legado_raw`.

**Tech Stack:** Laravel 11, PHP 8.3, PostgreSQL 16, Sanctum, Spatie Permission, darkaonline/l5-swagger, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-19-ajuda-humanitaria-api-bi-design.md`

## Global Constraints

- `declare(strict_types=1);` em todo arquivo PHP novo.
- Sem emoji dentro do codigo. Comentarios em pt-BR sem acento, como no restante do modulo.
- Toda alteracao de schema e **consolidada na migration que cria a tabela**, nunca em migration nova de `ALTER`.
- Permissoes usam somente slugs existentes: `humanitaria.saldo.view` e `humanitaria.pedidos.view`. Nao criar slug novo.
- Endpoints somente leitura. Nenhuma escrita.
- Mapa de situacao da liberacao, identico ao legado: `0 => 'Aberto'`, `1 => 'Pago'`, `2 => 'Cancelado'`, demais `'Desconhecido'`.
- Enum de evento aceito no filtro, identico ao legado: `AJUDA HUMANITARIA`, `CEDEC`, `CHUVA`, `COVID-19`, `OUTROS`, `SECA`.
- Commits em gitmoji: `<emoji> tipo(escopo): descricao` em pt-BR. Sem trailer `Co-Authored-By`.
- Rodar testes: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=<Classe>`
- Banco de desenvolvimento: container `newsdc_dev_db`, base `sdc`, usuario `sdc`, exposto em `localhost:5434`.

---

## Nota operacional sobre a consolidacao de migration

A Task 1 edita os blocos `Schema::create` de duas migrations **ja aplicadas** no
banco de desenvolvimento. Bancos ja migrados nao recebem a coluna sozinhos. Apos
a Task 1, rode:

```bash
docker exec -w /var/www newsdc_dev_app php artisan migrate:fresh
docker exec -w /var/www newsdc_dev_app php artisan legado:aju:importar-dump
docker exec -w /var/www newsdc_dev_app php artisan legado:aju:refinar
```

Se recarregar a staging nao for viavel no momento, aplique o `ALTER TABLE`
equivalente a mao **sem criar arquivo de migration**, para nao contrariar a
regra de consolidacao:

```sql
ALTER TABLE materiais_ah ADD COLUMN valor numeric(10,2), ADD COLUMN peso numeric(10,2), ADD COLUMN categoria varchar(60);
CREATE INDEX materiais_ah_categoria_index ON materiais_ah (categoria);
ALTER TABLE ajuda_h_liberacoes ADD COLUMN evento varchar(40);
CREATE INDEX ajuda_h_liberacoes_evento_data_idx ON ajuda_h_liberacoes (evento, data_libera);
```

---

### Task 1: Colunas promovidas no schema e nos models

Promove a colunas reais os quatro campos do contrato legado que hoje nao tem
coluna: `valor`, `peso` e `categoria` em `materiais_ah`, e `evento` em
`ajuda_h_liberacoes`.

**Files:**
- Modify: `SDC/database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php:24-36` (bloco `materiais_ah`)
- Modify: `SDC/database/migrations/2026_08_06_110100_create_ajuda_h_estoque_tables.php:202-232` (bloco `ajuda_h_liberacoes`)
- Modify: `SDC/app/Modules/AjudaHumanitaria/Models/MaterialAh.php:19-29`
- Modify: `SDC/app/Modules/AjudaHumanitaria/Models/LiberacaoAh.php`
- Test: `SDC/tests/Feature/AjudaHumanitaria/SchemaMahTest.php`

**Interfaces:**
- Consumes: nada.
- Produces: colunas `materiais_ah.valor` (`decimal(10,2)`, nullable), `materiais_ah.peso` (`decimal(10,2)`, nullable), `materiais_ah.categoria` (`string(60)`, nullable, indexada), `ajuda_h_liberacoes.evento` (`string(40)`, nullable, indice composto com `data_libera`), e o indice `ajuda_h_lib_itens_liberacao_idx` promovido a `(liberacao_id, status)`. `MaterialAh::$fillable` passa a aceitar `valor`, `peso`, `categoria`; casts `valor` e `peso` para `decimal:2`. `LiberacaoAh::$fillable` passa a aceitar `evento`.

- [ ] **Step 1: Escrever o teste que falha**

Acrescentar em `SDC/tests/Feature/AjudaHumanitaria/SchemaMahTest.php`:

```php
    public function test_materiais_ah_tem_colunas_de_valor_peso_e_categoria(): void
    {
        $this->assertTrue(Schema::hasColumns('materiais_ah', ['valor', 'peso', 'categoria']));
    }

    public function test_liberacoes_tem_coluna_evento(): void
    {
        $this->assertTrue(Schema::hasColumn('ajuda_h_liberacoes', 'evento'));
    }

    public function test_material_ah_aceita_valor_peso_e_categoria(): void
    {
        $material = \App\Modules\AjudaHumanitaria\Models\MaterialAh::create([
            'nome'          => 'CESTA BASICA TESTE',
            'codigo_legado' => '9901',
            'valor'         => '12.34',
            'peso'          => '20.75',
            'categoria'     => 'CESTA BASICA',
        ]);

        $material->refresh();

        $this->assertSame('12.34', (string) $material->valor);
        $this->assertSame('20.75', (string) $material->peso);
        $this->assertSame('CESTA BASICA', $material->categoria);
    }
```

Confirme que `use Illuminate\Support\Facades\Schema;` esta no topo do arquivo; se nao estiver, acrescente.

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=SchemaMahTest`
Expected: FAIL. As duas primeiras assercoes retornam `false`; a terceira lanca erro de coluna inexistente.

- [ ] **Step 3: Acrescentar as colunas na migration de `materiais_ah`**

Em `2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php`, dentro do bloco `Schema::create('materiais_ah', ...)`, apos a linha de `codigo_legado`:

```php
            // Valor e peso vem de aju_unidade e alimentam o contrato de saldo de
            // cesta basica. Nulos ate o backfill (legado:aju:refinar --etapa=materiais).
            $table->decimal('valor', 10, 2)->nullable();
            $table->decimal('peso', 10, 2)->nullable();
            // A extracao do legado nao trouxe aju_unidade.categoria. O backfill
            // marca CESTA BASICA no material de codigo_legado 1, que e o recorte
            // efetivo que o endpoint saldocesta praticava.
            $table->string('categoria', 60)->nullable();
```

E no bloco de indices da mesma tabela, apos `$table->index('codigo_legado');`:

```php
            $table->index('categoria');
```

- [ ] **Step 4: Acrescentar a coluna na migration de `ajuda_h_liberacoes`**

Em `2026_08_06_110100_create_ajuda_h_estoque_tables.php`, dentro do bloco `Schema::create('ajuda_h_liberacoes', ...)`, imediatamente antes da linha `$table->jsonb('payload_legado')->nullable();`:

```php
            // Promovida de payload_legado->evento: e filtro de consulta da API
            // de liberacoes, e filtro sobre jsonb nao usa indice.
            $table->string('evento', 40)->nullable();
```

E no bloco de indices da mesma tabela, apos o indice `ajuda_h_liberacoes_dep_data_idx`:

```php
            $table->index(['evento', 'data_libera'], 'ajuda_h_liberacoes_evento_data_idx');
```

Na mesma migration, no bloco `Schema::create('ajuda_h_liberacao_itens', ...)`,
trocar o indice de coluna unica por um composto: o endpoint plano do CEDEC
filtra por `status` dentro do join por `liberacao_id`.

```php
            $table->index(['liberacao_id', 'status'], 'ajuda_h_lib_itens_liberacao_idx');
```

- [ ] **Step 5: Liberar os campos nos models**

Em `SDC/app/Modules/AjudaHumanitaria/Models/MaterialAh.php`, acrescentar ao `$fillable` (apos `'codigo_legado'`) e ao `$casts`:

```php
    protected $fillable = [
        'nome',
        'descricao',
        'unidade_medida',
        'disponivel_para_pedido',
        'codigo_legado',
        'valor',
        'peso',
        'categoria',
    ];

    protected $casts = [
        'disponivel_para_pedido' => 'boolean',
        'valor'                  => 'decimal:2',
        'peso'                   => 'decimal:2',
    ];
```

Em `SDC/app/Modules/AjudaHumanitaria/Models/LiberacaoAh.php`, acrescentar `'evento'` ao `$fillable`, mantendo os demais campos como estao.

- [ ] **Step 6: Recriar o schema e rodar o teste**

```bash
docker exec -w /var/www newsdc_dev_app php artisan migrate:fresh
docker exec -w /var/www newsdc_dev_app php artisan test --filter=SchemaMahTest
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add SDC/database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php \
        SDC/database/migrations/2026_08_06_110100_create_ajuda_h_estoque_tables.php \
        SDC/app/Modules/AjudaHumanitaria/Models/MaterialAh.php \
        SDC/app/Modules/AjudaHumanitaria/Models/LiberacaoAh.php \
        SDC/tests/Feature/AjudaHumanitaria/SchemaMahTest.php
git commit -m "🗃️ db(ajuda-humanitaria): promove valor, peso, categoria e evento a colunas"
```

---

### Task 2: Backfill de valor, peso, categoria e evento

Popula as colunas da Task 1 a partir de `ajuda_h_legado_raw`, estendendo as
etapas que o comando de refino ja tem. Nao cria comando novo.

**Files:**
- Modify: `SDC/app/Modules/AjudaHumanitaria/Console/RefinarLegadoAjuCommand.php`
- Test: `SDC/tests/Feature/AjudaHumanitaria/RefinoBackfillCamposApiTest.php` (criar)

**Interfaces:**
- Consumes: colunas da Task 1.
- Produces: `legado:aju:refinar --etapa=materiais` grava `valor`, `peso` e `categoria`; `legado:aju:refinar --etapa=liberacoes` grava `evento`. Sem mudanca de assinatura do comando.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/AjudaHumanitaria/RefinoBackfillCamposApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Models\LiberacaoAh;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefinoBackfillCamposApiTest extends TestCase
{
    use DatabaseTransactions;

    private function semearRaw(string $tabela, string $pk, array $doc): void
    {
        DB::table('ajuda_h_legado_raw')->insert([
            'tabela'      => $tabela,
            'pk_legado'   => $pk,
            'doc'         => json_encode($doc),
            'extraido_em' => now(),
        ]);
    }

    public function test_etapa_materiais_grava_valor_peso_e_categoria(): void
    {
        $this->semearRaw('aju_unidade', '9902', [
            'id_unidade' => '9902',
            'nome'       => 'CESTA BASICA',
            'singular'   => 'CESTA',
            'valor'      => '1.00',
            'peso'       => '20.50',
            'pedido_h'   => '1',
        ]);

        $this->artisan('legado:aju:refinar', ['--etapa' => ['materiais']])
            ->assertSuccessful();

        $material = MaterialAh::where('codigo_legado', '9902')->firstOrFail();

        $this->assertSame('1.00', (string) $material->valor);
        $this->assertSame('20.50', (string) $material->peso);
        $this->assertSame('CESTA BASICA', $material->categoria);
    }

    public function test_etapa_materiais_nao_marca_categoria_em_material_comum(): void
    {
        $this->semearRaw('aju_unidade', '9903', [
            'id_unidade' => '9903',
            'nome'       => 'COBERTOR - FONTE 10',
            'singular'   => 'COBERTOR',
            'valor'      => '30.00',
            'peso'       => '2.00',
            'pedido_h'   => '1',
        ]);

        $this->artisan('legado:aju:refinar', ['--etapa' => ['materiais']])
            ->assertSuccessful();

        $this->assertNull(MaterialAh::where('codigo_legado', '9903')->firstOrFail()->categoria);
    }

    public function test_etapa_liberacoes_promove_evento_do_payload(): void
    {
        $this->artisan('legado:aju:refinar', ['--etapa' => ['liberacoes']])
            ->assertSuccessful();

        $comPayload = LiberacaoAh::whereNotNull('payload_legado')
            ->whereRaw("payload_legado->>'evento' IS NOT NULL")
            ->first();

        if ($comPayload === null) {
            $this->markTestSkipped('Staging sem liberacao com evento no payload.');
        }

        $this->assertSame(
            $comPayload->payload_legado['evento'],
            $comPayload->evento
        );
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=RefinoBackfillCamposApiTest`
Expected: FAIL. `valor`, `peso`, `categoria` e `evento` vem nulos porque o refino ainda nao os grava.

- [ ] **Step 3: Gravar os campos na etapa de materiais**

Em `RefinarLegadoAjuCommand.php`, no metodo que refina materiais, acrescentar os
tres campos ao array de atributos gravados em `materiais_ah`. O recorte de
categoria fica em constante privada da classe, nao em literal solto:

```php
    /**
     * A extracao do legado nao trouxe aju_unidade.categoria. O endpoint
     * saldocesta filtrava categoria = 'CESTA BASICA', e na pratica o unico
     * material sob esse recorte e o de id_unidade 1. Enquanto a area nao
     * confirmar a lista completa, o backfill marca por nome.
     */
    private const CATEGORIA_CESTA = 'CESTA BASICA';

    private function categoriaDoMaterial(?string $nome): ?string
    {
        return $nome === self::CATEGORIA_CESTA ? self::CATEGORIA_CESTA : null;
    }
```

E, no array de atributos do `updateOrCreate` de materiais, acrescentar:

```php
            'valor'     => $doc['valor'] ?? null,
            'peso'      => $doc['peso'] ?? null,
            'categoria' => $this->categoriaDoMaterial($doc['nome'] ?? null),
```

- [ ] **Step 4: Gravar o evento na etapa de liberacoes**

No metodo que refina liberacoes, acrescentar ao array de atributos gravados em `ajuda_h_liberacoes`:

```php
            'evento' => $doc['evento'] ?? null,
```

`evento` ja vem no `doc` de `aju_liberacao`; o mesmo valor continua em
`payload_legado`, que permanece intacto para nao perder o rastro da origem.

- [ ] **Step 5: Rodar o teste e confirmar que passa**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=RefinoBackfillCamposApiTest`
Expected: PASS.

- [ ] **Step 6: Recarregar a base de desenvolvimento e conferir o backfill**

```bash
docker exec -w /var/www newsdc_dev_app php artisan legado:aju:refinar --etapa=materiais --etapa=liberacoes
docker exec newsdc_dev_db psql -U sdc -d sdc -c "SELECT count(*) FILTER (WHERE valor IS NOT NULL) AS com_valor, count(*) FILTER (WHERE categoria IS NOT NULL) AS cesta, count(*) AS total FROM materiais_ah;"
docker exec newsdc_dev_db psql -U sdc -d sdc -c "SELECT evento, count(*) FROM ajuda_h_liberacoes GROUP BY evento ORDER BY 2 DESC;"
```
Expected: `com_valor = 187`, `cesta >= 1`; a distribuicao de evento cobre as 3.582 liberacoes.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Console/RefinarLegadoAjuCommand.php \
        SDC/tests/Feature/AjudaHumanitaria/RefinoBackfillCamposApiTest.php
git commit -m "✨ feat(ajuda-humanitaria): backfill de valor, peso, categoria e evento no refino"
```

---

### Task 3: Endpoint de saldo de cesta basica

Paridade com `GET /api/saldocesta` do legado. Primeiro endpoint: registra a tag
Swagger do modulo e o grupo de rotas que as tasks seguintes reaproveitam.

**Files:**
- Create: `SDC/app/Modules/AjudaHumanitaria/Services/SaldoCestaApiService.php`
- Create: `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/EstoqueApiController.php`
- Modify: `SDC/routes/api.php` (dentro do grupo `Route::prefix('v1')`)
- Modify: `SDC/app/Http/Controllers/Api/SwaggerController.php` (nova `@OA\Tag`)
- Test: `SDC/tests/Feature/AjudaHumanitaria/Api/SaldoCestaApiTest.php` (criar)

**Interfaces:**
- Consumes: colunas e backfill das Tasks 1 e 2.
- Produces: `SaldoCestaApiService::consultar(): array<int, array{id_deposito:int, nome:string, total_saldo:string, singular:string, valor:string|null, peso:int|null}>`. Rota nomeada `api.v1.ajuda-humanitaria.estoque.saldo-cesta`. Grupo de rotas `Route::prefix('ajuda-humanitaria')->name('api.v1.ajuda-humanitaria.')` dentro de `v1`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/AjudaHumanitaria/Api/SaldoCestaApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SaldoCestaApiTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/v1/ajuda-humanitaria/estoque/saldo-cesta';

    private function usuarioComPermissao(string ...$perms): User
    {
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perms);

        return $user;
    }

    private function semearSaldo(): array
    {
        $municipioId = DB::table('municipios')->value('id');

        $depositoId = DB::table('ajuda_h_depositos')->insertGetId([
            'nome'         => 'DEPOSITO TESTE',
            'municipio_id' => $municipioId,
            'ativo'        => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $materialId = DB::table('materiais_ah')->insertGetId([
            'nome'                   => 'CESTA BASICA',
            'unidade_medida'         => 'UN',
            'disponivel_para_pedido' => true,
            'codigo_legado'          => '9910',
            'valor'                  => 1.00,
            'peso'                   => 20.75,
            'categoria'              => 'CESTA BASICA',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('ajuda_h_estoque_saldos')->insert([
            ['material_ah_id' => $materialId, 'deposito_id' => $depositoId, 'saldo' => 40, 'atualizado_em' => now()],
        ]);

        return ['deposito' => $depositoId, 'material' => $materialId];
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_exige_permissao(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::URL)
            ->assertForbidden();
    }

    public function test_lista_saldo_por_deposito_com_valor_e_peso_truncado(): void
    {
        $ids = $this->semearSaldo();

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertOk()
            ->json();

        $linha = collect($resposta)->firstWhere('id_deposito', $ids['deposito']);

        $this->assertNotNull($linha, 'Deposito semeado ausente da resposta.');
        $this->assertSame('DEPOSITO TESTE', $linha['nome']);
        $this->assertSame(40, (int) $linha['total_saldo']);
        $this->assertSame('CESTA BASICA', $linha['singular']);
        $this->assertSame(20, $linha['peso'], 'peso deve ser truncado, como floor() no legado.');
    }

    public function test_ignora_material_fora_da_categoria_cesta(): void
    {
        $municipioId = DB::table('municipios')->value('id');

        $depositoId = DB::table('ajuda_h_depositos')->insertGetId([
            'nome'         => 'DEPOSITO SEM CESTA',
            'municipio_id' => $municipioId,
            'ativo'        => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $materialId = DB::table('materiais_ah')->insertGetId([
            'nome'                   => 'COBERTOR TESTE',
            'unidade_medida'         => 'UN',
            'disponivel_para_pedido' => true,
            'codigo_legado'          => '9911',
            'categoria'              => null,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('ajuda_h_estoque_saldos')->insert([
            ['material_ah_id' => $materialId, 'deposito_id' => $depositoId, 'saldo' => 99, 'atualizado_em' => now()],
        ]);

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertOk()
            ->json();

        $this->assertNull(collect($resposta)->firstWhere('id_deposito', $depositoId));
    }

    public function test_ignora_saldo_zero(): void
    {
        $municipioId = DB::table('municipios')->value('id');

        $depositoId = DB::table('ajuda_h_depositos')->insertGetId([
            'nome'         => 'DEPOSITO ZERADO',
            'municipio_id' => $municipioId,
            'ativo'        => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $materialId = DB::table('materiais_ah')->insertGetId([
            'nome'                   => 'CESTA BASICA',
            'unidade_medida'         => 'UN',
            'disponivel_para_pedido' => true,
            'codigo_legado'          => '9912',
            'categoria'              => 'CESTA BASICA',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('ajuda_h_estoque_saldos')->insert([
            ['material_ah_id' => $materialId, 'deposito_id' => $depositoId, 'saldo' => 0, 'atualizado_em' => now()],
        ]);

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertOk()
            ->json();

        $this->assertNull(collect($resposta)->firstWhere('id_deposito', $depositoId));
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=SaldoCestaApiTest`
Expected: FAIL com 404 na rota, que ainda nao existe.

- [ ] **Step 3: Escrever o service**

Criar `SDC/app/Modules/AjudaHumanitaria/Services/SaldoCestaApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use Illuminate\Support\Facades\DB;

/**
 * Paridade com o endpoint saldocesta do Laravel legado.
 *
 * O legado filtrava aju_unidade.categoria = 'CESTA BASICA' e saldo <> 0,
 * agrupando por deposito, e truncava o peso com floor(). O recorte aqui e o
 * mesmo, sobre as tabelas do banco novo.
 *
 * Somente leitura.
 */
final class SaldoCestaApiService
{
    public const CATEGORIA = 'CESTA BASICA';

    /**
     * @return array<int, array{id_deposito: int, nome: string, total_saldo: string, singular: string, valor: string|null, peso: int|null}>
     */
    public function consultar(): array
    {
        return DB::table('ajuda_h_estoque_saldos as s')
            ->join('ajuda_h_depositos as d', 's.deposito_id', '=', 'd.id')
            ->join('materiais_ah as m', 's.material_ah_id', '=', 'm.id')
            ->where('m.categoria', self::CATEGORIA)
            ->where('s.saldo', '<>', 0)
            ->groupBy('s.deposito_id', 'd.nome', 'm.nome', 'm.valor', 'm.peso')
            ->orderBy('d.nome')
            ->select([
                's.deposito_id as id_deposito',
                'd.nome',
                DB::raw('SUM(s.saldo) AS total_saldo'),
                // O legado publicava aju_unidade.singular. O schema novo tem so
                // materiais_ah.nome; a divergencia esta documentada no design.
                'm.nome as singular',
                'm.valor',
                DB::raw('FLOOR(m.peso) AS peso'),
            ])
            ->get()
            ->map(static fn (object $linha): array => [
                'id_deposito' => (int) $linha->id_deposito,
                'nome'        => (string) $linha->nome,
                'total_saldo' => (string) $linha->total_saldo,
                'singular'    => (string) $linha->singular,
                'valor'       => $linha->valor === null ? null : (string) $linha->valor,
                'peso'        => $linha->peso === null ? null : (int) $linha->peso,
            ])
            ->all();
    }
}
```

- [ ] **Step 4: Escrever o controller com as anotacoes Swagger**

Criar `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/EstoqueApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Services\SaldoCestaApiService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhSaldoCestaItem",
 *     type="object",
 *     title="Saldo de cesta basica por deposito",
 *     @OA\Property(property="id_deposito", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="BELO HORIZONTE"),
 *     @OA\Property(property="total_saldo", type="string", example="1240"),
 *     @OA\Property(property="singular", type="string", example="CESTA BASICA", description="No legado era aju_unidade.singular ('CESTA'). O schema novo tem apenas materiais_ah.nome."),
 *     @OA\Property(property="valor", type="string", nullable=true, example="1.00"),
 *     @OA\Property(property="peso", type="integer", nullable=true, example=20, description="Peso truncado, equivalente ao floor() do legado.")
 * )
 */
final class EstoqueApiController extends Controller
{
    public function __construct(private readonly SaldoCestaApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/estoque/saldo-cesta",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Saldo de cesta basica por deposito",
     *     description="Paridade com o endpoint publico saldocesta do sistema legado, lendo do banco do NewSDC. Considera apenas material de categoria CESTA BASICA com saldo diferente de zero.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de saldos consolidados por deposito",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AhSaldoCestaItem"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function saldoCesta(): JsonResponse
    {
        return response()->json($this->servico->consultar());
    }
}
```

- [ ] **Step 5: Registrar a tag Swagger**

Em `SDC/app/Http/Controllers/Api/SwaggerController.php`, apos o bloco `@OA\Tag` de `RAT`, acrescentar:

```php
 * @OA\Tag(
 *     name="Ajuda Humanitaria",
 *     description="Fornecimento de dados de Ajuda Humanitaria — saldo de estoque, liberacoes e consolidado de pedidos. Paridade com os endpoints publicos do sistema legado"
 * )
 *
```

- [ ] **Step 6: Registrar a rota**

Em `SDC/routes/api.php`, dentro do grupo `Route::prefix('v1')->middleware([...])->group(function () {`, apos o bloco do modulo PAE, acrescentar:

```php
    // Modulo Ajuda Humanitaria: fornecimento de dados, somente leitura.
    // Paridade de contrato com os endpoints publicos do legado, agora sob token.
    Route::prefix('ajuda-humanitaria')->name('api.v1.ajuda-humanitaria.')->group(function () {
        Route::get('estoque/saldo-cesta', [\App\Http\Controllers\Api\V1\AjudaHumanitaria\EstoqueApiController::class, 'saldoCesta'])
            ->name('estoque.saldo-cesta')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:60,1']);
    });
```

- [ ] **Step 7: Rodar o teste e confirmar que passa**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=SaldoCestaApiTest`
Expected: PASS.

- [ ] **Step 8: Gerar o Swagger e conferir**

```bash
docker exec -w /var/www newsdc_dev_app php artisan l5-swagger:generate
```
Expected: sem erro; a tag `Ajuda Humanitaria` e o endpoint aparecem no JSON gerado.

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Services/SaldoCestaApiService.php \
        SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/EstoqueApiController.php \
        SDC/app/Http/Controllers/Api/SwaggerController.php \
        SDC/routes/api.php \
        SDC/tests/Feature/AjudaHumanitaria/Api/SaldoCestaApiTest.php
git commit -m "✨ feat(ajuda-humanitaria): API de saldo de cesta basica com Swagger"
```

---

### Task 4: Endpoint de liberacoes agrupadas por ano

Paridade com `GET /api/pubajudah`. Resposta agrupada por ano, com `meta.totais`.
Inclui a verificacao de `Codmundv` contra `codigo_ibge` prevista no design.

**Files:**
- Create: `SDC/app/Modules/AjudaHumanitaria/Services/LiberacaoApiService.php`
- Create: `SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaLiberacaoApiRequest.php`
- Create: `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/LiberacaoApiController.php`
- Modify: `SDC/routes/api.php` (grupo criado na Task 3)
- Test: `SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoApiTest.php` (criar)

**Interfaces:**
- Consumes: coluna `evento` (Task 1), backfill (Task 2), grupo de rotas `api.v1.ajuda-humanitaria.` (Task 3).
- Produces: `LiberacaoApiService::agrupadasPorAno(int $anoComeco, ?int $anoFim, ?string $evento): array{data: array<string, list<array>>, meta: array{totais: array{total_registros:int, total_pagas:int, total_aberto:int, total_canceladas:int}}}`. Constante publica `LiberacaoApiService::SITUACOES` com o mapa `0/1/2`. Rota `api.v1.ajuda-humanitaria.liberacoes.index`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LiberacaoApiTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/v1/ajuda-humanitaria/liberacoes';

    private function usuarioComPermissao(string ...$perms): User
    {
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perms);

        return $user;
    }

    private function semearLiberacao(int $status, string $data, string $evento): int
    {
        $municipioId = DB::table('municipios')->value('id');

        $depositoId = DB::table('ajuda_h_depositos')->insertGetId([
            'nome'         => 'DEPOSITO LIB',
            'municipio_id' => $municipioId,
            'ativo'        => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return DB::table('ajuda_h_liberacoes')->insertGetId([
            'municipio_id'   => $municipioId,
            'deposito_id'    => $depositoId,
            'data_libera'    => $data,
            'status'         => $status,
            'evento'         => $evento,
            'payload_legado' => json_encode(['hora_libera' => null]),
            'codigo_legado'  => 'T' . $status . substr($data, 0, 4) . $evento,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson(self::URL . '?ano_comeco=2022')->assertUnauthorized();
    }

    public function test_exige_permissao(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::URL . '?ano_comeco=2022')
            ->assertForbidden();
    }

    public function test_ano_comeco_e_obrigatorio(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertStatus(422)
            ->assertJsonValidationErrors('ano_comeco');
    }

    public function test_evento_fora_do_enum_e_rejeitado(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL . '?ano_comeco=2022&evento=INEXISTENTE')
            ->assertStatus(422)
            ->assertJsonValidationErrors('evento');
    }

    public function test_agrupa_por_ano_e_mapeia_situacao(): void
    {
        $this->semearLiberacao(1, '2022-03-10', 'SECA');

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL . '?ano_comeco=2022&ano_fim=2022')
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('2022', $resposta['data']);

        $linha = collect($resposta['data']['2022'])->firstWhere('evento', 'SECA');

        $this->assertNotNull($linha);
        $this->assertSame('Pago', $linha['situacao']);
        $this->assertSame(3, $linha['mes']);
        $this->assertSame(0, $linha['items_quant'], 'Sem carga de itens, a soma e zero.');
        $this->assertSame([], $linha['items']);
        $this->assertArrayHasKey('codmundv', $linha['unidade']);
    }

    public function test_totais_contam_por_situacao(): void
    {
        $this->semearLiberacao(0, '2019-01-05', 'CHUVA');
        $this->semearLiberacao(1, '2019-02-05', 'CHUVA');
        $this->semearLiberacao(2, '2019-03-05', 'CHUVA');

        $totais = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL . '?ano_comeco=2019&ano_fim=2019&evento=CHUVA')
            ->assertOk()
            ->json('meta.totais');

        $this->assertSame(3, $totais['total_registros']);
        $this->assertSame(1, $totais['total_aberto']);
        $this->assertSame(1, $totais['total_pagas']);
        $this->assertSame(1, $totais['total_canceladas']);
    }

    public function test_filtro_de_ano_recorta_o_resultado(): void
    {
        $this->semearLiberacao(1, '2020-05-05', 'CEDEC');
        $this->semearLiberacao(1, '2024-05-05', 'CEDEC');

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL . '?ano_comeco=2020&ano_fim=2020&evento=CEDEC')
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('2020', $resposta['data']);
        $this->assertArrayNotHasKey('2024', $resposta['data']);
    }

    public function test_codmundv_confere_com_codigo_ibge_do_municipio(): void
    {
        $municipio = DB::table('municipios')->select('id', 'codigo_ibge')->first();

        $this->semearLiberacao(1, '2021-07-07', 'OUTROS');

        $linha = collect(
            $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
                ->getJson(self::URL . '?ano_comeco=2021&ano_fim=2021&evento=OUTROS')
                ->assertOk()
                ->json('data.2021')
        )->first();

        $this->assertSame((string) $municipio->codigo_ibge, (string) $linha['unidade']['codmundv']);
        $this->assertSame(7, strlen((string) $linha['unidade']['codmundv']), 'Codmundv do legado tem 7 digitos, com digito verificador.');
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=LiberacaoApiTest`
Expected: FAIL com 404 na rota.

- [ ] **Step 3: Escrever o FormRequest**

Criar `SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaLiberacaoApiRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacao identica a do endpoint pubajudah do legado.
 */
final class ConsultaLiberacaoApiRequest extends FormRequest
{
    public const EVENTOS = [
        'AJUDA HUMANITARIA',
        'CEDEC',
        'CHUVA',
        'COVID-19',
        'OUTROS',
        'SECA',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ano_comeco' => ['required', 'integer', 'digits:4', 'min:1900'],
            'ano_fim'    => ['sometimes', 'integer', 'digits:4', 'min:1900', 'max:' . date('Y')],
            'evento'     => ['sometimes', Rule::in(self::EVENTOS)],
        ];
    }

    public function anoComeco(): int
    {
        return (int) $this->validated('ano_comeco');
    }

    public function anoFim(): ?int
    {
        $valor = $this->validated('ano_fim');

        return $valor === null ? null : (int) $valor;
    }

    public function evento(): ?string
    {
        $valor = $this->validated('evento');

        return $valor === null ? null : (string) $valor;
    }
}
```

- [ ] **Step 4: Escrever o service**

Criar `SDC/app/Modules/AjudaHumanitaria/Services/LiberacaoApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Paridade com os endpoints pubajudah e pubajudahCedec do Laravel legado.
 *
 * O array items vem de ajuda_h_liberacao_itens, que a carga atual do legado nao
 * preenche (aju_item nao foi extraida). Por isso items sai vazio e items_quant
 * sai zero: o contrato esta correto, a carga e que esta incompleta. Quando os
 * itens entrarem, nada aqui muda.
 *
 * Somente leitura.
 */
final class LiberacaoApiService
{
    /** @var array<int, string> */
    public const SITUACOES = [
        0 => 'Aberto',
        1 => 'Pago',
        2 => 'Cancelado',
    ];

    /**
     * @return array{data: array<string, list<array<string, mixed>>>, meta: array{totais: array<string, int>}}
     */
    public function agrupadasPorAno(int $anoComeco, ?int $anoFim = null, ?string $evento = null): array
    {
        $linhas = $this->consultaBase($evento)
            ->whereRaw('EXTRACT(YEAR FROM l.data_libera) >= ?', [$anoComeco])
            ->when($anoFim !== null, fn ($q) => $q->whereRaw('EXTRACT(YEAR FROM l.data_libera) <= ?', [$anoFim]))
            ->orderBy('l.data_libera')
            ->get();

        return [
            'data' => $linhas
                ->groupBy(static fn (object $l): string => (string) $l->ano)
                ->map(fn (Collection $doAno): array => $doAno->map(fn (object $l): array => $this->formatar($l))->all())
                ->all(),
            'meta' => ['totais' => $this->totais($linhas)],
        ];
    }

    /**
     * Formato plano do pubajudahCedec: uma linha por item de liberacao.
     *
     * @return list<array<string, mixed>>
     */
    public function planaParaCedec(): array
    {
        return DB::table('ajuda_h_liberacoes as l')
            ->join('municipios as mun', 'l.municipio_id', '=', 'mun.id')
            ->join('ajuda_h_depositos as d', 'l.deposito_id', '=', 'd.id')
            ->join('ajuda_h_liberacao_itens as i', 'i.liberacao_id', '=', 'l.id')
            ->join('materiais_ah as m', 'i.material_ah_id', '=', 'm.id')
            ->whereIn('i.status', [0, 1])
            ->whereNull('l.deleted_at')
            ->select([
                'l.municipio_id as id_municipio',
                'mun.codigo_ibge as Codmundv',
                'mun.nome as municipio',
                'l.data_libera as dataLibera',
                'i.qtd as quantidade',
                'm.codigo_legado as id_material',
                'm.nome as material',
                'l.evento',
                'd.nome as deposito',
                'i.status',
            ])
            ->get()
            ->map(static fn (object $l): array => [
                'id_municipio' => (int) $l->id_municipio,
                'Codmundv'     => $l->Codmundv === null ? null : (string) $l->Codmundv,
                'municipio'    => (string) $l->municipio,
                'dataLibera'   => (string) $l->dataLibera,
                'quantidade'   => (string) $l->quantidade,
                'id_material'  => $l->id_material === null ? null : (string) $l->id_material,
                'material'     => (string) $l->material,
                'evento'       => $l->evento === null ? null : (string) $l->evento,
                'deposito'     => (string) $l->deposito,
                'status'       => (int) $l->status,
            ])
            ->all();
    }

    private function consultaBase(?string $evento): \Illuminate\Database\Query\Builder
    {
        return DB::table('ajuda_h_liberacoes as l')
            ->join('municipios as mun', 'l.municipio_id', '=', 'mun.id')
            ->leftJoin('ajuda_h_liberacao_itens as i', 'i.liberacao_id', '=', 'l.id')
            ->whereNull('l.deleted_at')
            ->when($evento !== null, fn ($q) => $q->where('l.evento', $evento))
            ->groupBy('l.id', 'l.codigo_legado', 'l.data_libera', 'l.status', 'l.evento', 'l.payload_legado', 'mun.id', 'mun.codigo_ibge', 'mun.nome')
            ->select([
                'l.id',
                'l.codigo_legado',
                'l.data_libera',
                'l.status',
                'l.evento',
                'l.payload_legado',
                'mun.id as municipio_id',
                'mun.codigo_ibge',
                'mun.nome as municipio_nome',
                DB::raw('EXTRACT(YEAR FROM l.data_libera)::int AS ano'),
                DB::raw('EXTRACT(MONTH FROM l.data_libera)::int AS mes'),
                DB::raw('COALESCE(SUM(i.qtd), 0) AS items_quant'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatar(object $linha): array
    {
        $payload = $linha->payload_legado === null
            ? []
            : (array) json_decode((string) $linha->payload_legado, true);

        return [
            'id_liberacao'   => $linha->codigo_legado === null ? null : (int) $linha->codigo_legado,
            'data_liberacao' => (string) $linha->data_libera,
            'hora_liberacao' => $payload['hora_libera'] ?? null,
            'mes'            => (int) $linha->mes,
            'evento'         => $linha->evento === null ? null : (string) $linha->evento,
            'situacao'       => self::SITUACOES[(int) $linha->status] ?? 'Desconhecido',
            'unidade'        => [
                'id_municipio' => (int) $linha->municipio_id,
                'codmundv'     => $linha->codigo_ibge === null ? null : (string) $linha->codigo_ibge,
                'nome'         => (string) $linha->municipio_nome,
            ],
            'items_quant' => (int) $linha->items_quant,
            // Vazio enquanto ajuda_h_liberacao_itens nao tiver carga. O array
            // produtos do legado nao tem tabela destino no schema novo.
            'items' => [],
        ];
    }

    /**
     * @param  Collection<int, object>  $linhas
     * @return array<string, int>
     */
    private function totais(Collection $linhas): array
    {
        return [
            'total_registros'  => $linhas->count(),
            'total_pagas'      => $linhas->where('status', 1)->count(),
            'total_aberto'     => $linhas->where('status', 0)->count(),
            'total_canceladas' => $linhas->where('status', 2)->count(),
        ];
    }
}
```

- [ ] **Step 5: Escrever o controller**

Criar `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/LiberacaoApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Requests\ConsultaLiberacaoApiRequest;
use App\Modules\AjudaHumanitaria\Services\LiberacaoApiService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhLiberacaoItem",
 *     type="object",
 *     title="Liberacao de material de ajuda humanitaria",
 *     @OA\Property(property="id_liberacao", type="integer", nullable=true, example=3421, description="Identificador do sistema legado, preservado para consumidores de BI."),
 *     @OA\Property(property="data_liberacao", type="string", format="date", example="2022-03-10"),
 *     @OA\Property(property="hora_liberacao", type="string", nullable=true, example=null),
 *     @OA\Property(property="mes", type="integer", example=3),
 *     @OA\Property(property="evento", type="string", nullable=true, enum={"AJUDA HUMANITARIA","CEDEC","CHUVA","COVID-19","OUTROS","SECA"}, example="SECA"),
 *     @OA\Property(property="situacao", type="string", enum={"Aberto","Pago","Cancelado","Desconhecido"}, example="Pago"),
 *     @OA\Property(property="unidade", type="object",
 *         @OA\Property(property="id_municipio", type="integer", example=123),
 *         @OA\Property(property="codmundv", type="string", nullable=true, example="3106200"),
 *         @OA\Property(property="nome", type="string", example="Belo Horizonte")
 *     ),
 *     @OA\Property(property="items_quant", type="integer", example=0, description="Soma das quantidades dos itens. Zero enquanto a carga de itens do legado nao estiver concluida."),
 *     @OA\Property(property="items", type="array", @OA\Items(type="object"), description="Vazio enquanto ajuda_h_liberacao_itens nao tiver carga.")
 * )
 */
final class LiberacaoApiController extends Controller
{
    public function __construct(private readonly LiberacaoApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/liberacoes",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Liberacoes agrupadas por ano",
     *     description="Paridade com o endpoint publico pubajudah do sistema legado, lendo do banco do NewSDC.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ano_comeco", in="query", required=true, description="Ano inicial, 4 digitos", @OA\Schema(type="integer", example=2022)),
     *     @OA\Parameter(name="ano_fim", in="query", required=false, description="Ano final, 4 digitos", @OA\Schema(type="integer", example=2024)),
     *     @OA\Parameter(name="evento", in="query", required=false, @OA\Schema(type="string", enum={"AJUDA HUMANITARIA","CEDEC","CHUVA","COVID-19","OUTROS","SECA"})),
     *     @OA\Response(
     *         response=200,
     *         description="Liberacoes agrupadas por ano, com totais por situacao",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", description="Chave = ano", additionalProperties=@OA\Schema(type="array", @OA\Items(ref="#/components/schemas/AhLiberacaoItem"))),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="totais", type="object",
     *                     @OA\Property(property="total_registros", type="integer", example=3582),
     *                     @OA\Property(property="total_pagas", type="integer", example=3300),
     *                     @OA\Property(property="total_aberto", type="integer", example=200),
     *                     @OA\Property(property="total_canceladas", type="integer", example=82)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Parametros invalidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ConsultaLiberacaoApiRequest $request): JsonResponse
    {
        return response()->json($this->servico->agrupadasPorAno(
            $request->anoComeco(),
            $request->anoFim(),
            $request->evento(),
        ));
    }
}
```

- [ ] **Step 6: Registrar a rota**

Em `SDC/routes/api.php`, dentro do grupo `ajuda-humanitaria` criado na Task 3, acrescentar antes da rota de estoque:

```php
        Route::get('liberacoes', [\App\Http\Controllers\Api\V1\AjudaHumanitaria\LiberacaoApiController::class, 'index'])
            ->name('liberacoes.index')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:60,1']);
```

- [ ] **Step 7: Rodar os testes e confirmar que passam**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=LiberacaoApiTest`
Expected: PASS. Se `test_codmundv_confere_com_codigo_ibge_do_municipio` falhar, `codigo_ibge` nao equivale a `Codmundv`: nesse caso troque a fonte de `codmundv` para `cedec_municipio.Codmundv`, casando por `municipios.codigo_ibge`, e mantenha o teste como esta.

- [ ] **Step 8: Conferir contra o dado real**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc -c "SELECT status, count(*) FROM ajuda_h_liberacoes WHERE deleted_at IS NULL GROUP BY status ORDER BY status;"
```
Compare com `meta.totais` de uma chamada sem filtro de evento a partir de `ano_comeco=1900`.

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Services/LiberacaoApiService.php \
        SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaLiberacaoApiRequest.php \
        SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/LiberacaoApiController.php \
        SDC/routes/api.php \
        SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoApiTest.php
git commit -m "✨ feat(ajuda-humanitaria): API de liberacoes agrupadas por ano com Swagger"
```

---

### Task 5: Endpoint plano de liberacoes para o CEDEC

Paridade com `GET /api/pubajudahCedec`. Reaproveita
`LiberacaoApiService::planaParaCedec()`, escrito na Task 4.

**Files:**
- Modify: `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/LiberacaoApiController.php`
- Modify: `SDC/routes/api.php`
- Test: `SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoCedecApiTest.php` (criar)

**Interfaces:**
- Consumes: `LiberacaoApiService::planaParaCedec(): list<array>` (Task 4).
- Produces: rota `api.v1.ajuda-humanitaria.liberacoes.cedec`; metodo `LiberacaoApiController::cedec(): JsonResponse`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoCedecApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LiberacaoCedecApiTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/v1/ajuda-humanitaria/liberacoes/cedec';

    private function usuarioComPermissao(string ...$perms): User
    {
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perms);

        return $user;
    }

    /**
     * @return array{liberacao: int, material: int}
     */
    private function semearLiberacaoComItem(int $statusItem): array
    {
        $municipioId = DB::table('municipios')->value('id');

        $depositoId = DB::table('ajuda_h_depositos')->insertGetId([
            'nome'         => 'DEPOSITO CEDEC',
            'municipio_id' => $municipioId,
            'ativo'        => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $materialId = DB::table('materiais_ah')->insertGetId([
            'nome'                   => 'CESTA BASICA',
            'unidade_medida'         => 'UN',
            'disponivel_para_pedido' => true,
            'codigo_legado'          => '9920',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $liberacaoId = DB::table('ajuda_h_liberacoes')->insertGetId([
            'municipio_id'  => $municipioId,
            'deposito_id'   => $depositoId,
            'data_libera'   => '2023-04-01',
            'status'        => 1,
            'evento'        => 'CHUVA',
            'codigo_legado' => 'TC' . $statusItem,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('ajuda_h_liberacao_itens')->insert([
            'liberacao_id'   => $liberacaoId,
            'material_ah_id' => $materialId,
            'qtd'            => 15,
            'status'         => $statusItem,
            'codigo_legado'  => 'TCI' . $statusItem,
        ]);

        return ['liberacao' => $liberacaoId, 'material' => $materialId];
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_exige_permissao(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::URL)
            ->assertForbidden();
    }

    public function test_responde_vazio_sem_erro_quando_nao_ha_itens(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertOk()
            ->assertJson([]);
    }

    public function test_publica_linha_plana_por_item(): void
    {
        $this->semearLiberacaoComItem(1);

        $linha = collect(
            $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
                ->getJson(self::URL)
                ->assertOk()
                ->json()
        )->firstWhere('deposito', 'DEPOSITO CEDEC');

        $this->assertNotNull($linha);
        $this->assertSame('2023-04-01', $linha['dataLibera']);
        $this->assertSame('CHUVA', $linha['evento']);
        $this->assertSame('9920', $linha['id_material']);
        $this->assertSame('CESTA BASICA', $linha['material']);
        $this->assertSame(15, (int) $linha['quantidade']);
        $this->assertSame(1, $linha['status']);
        $this->assertArrayHasKey('Codmundv', $linha);
    }

    public function test_ignora_item_com_status_fora_de_zero_e_um(): void
    {
        $this->semearLiberacaoComItem(2);

        $resposta = $this->actingAs($this->usuarioComPermissao('humanitaria.saldo.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertOk()
            ->json();

        $this->assertNull(collect($resposta)->firstWhere('deposito', 'DEPOSITO CEDEC'));
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=LiberacaoCedecApiTest`
Expected: FAIL com 404 na rota.

- [ ] **Step 3: Acrescentar o metodo no controller**

Em `LiberacaoApiController.php`, acrescentar o schema no bloco de docblock da
classe, apos `AhLiberacaoItem`:

```php
 * @OA\Schema(
 *     schema="AhLiberacaoCedecItem",
 *     type="object",
 *     title="Liberacao no formato plano do CEDEC",
 *     @OA\Property(property="id_municipio", type="integer", example=123),
 *     @OA\Property(property="Codmundv", type="string", nullable=true, example="3106200"),
 *     @OA\Property(property="municipio", type="string", example="Belo Horizonte"),
 *     @OA\Property(property="dataLibera", type="string", format="date", example="2023-04-01"),
 *     @OA\Property(property="quantidade", type="string", example="15.000"),
 *     @OA\Property(property="id_material", type="string", nullable=true, example="1", description="aju_unidade.id_unidade do legado."),
 *     @OA\Property(property="material", type="string", example="CESTA BASICA"),
 *     @OA\Property(property="evento", type="string", nullable=true, example="CHUVA"),
 *     @OA\Property(property="deposito", type="string", example="BELO HORIZONTE"),
 *     @OA\Property(property="status", type="integer", enum={0, 1}, example=1)
 * )
```

E o metodo, apos `index()`:

```php
    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/liberacoes/cedec",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Liberacoes em formato plano, uma linha por item",
     *     description="Paridade com o endpoint publico pubajudahCedec do sistema legado. Considera itens de status 0 ou 1. Retorna lista vazia enquanto a carga de itens de liberacao do legado nao estiver concluida.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista plana de itens liberados",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AhLiberacaoCedecItem"))
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.saldo.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function cedec(): JsonResponse
    {
        return response()->json($this->servico->planaParaCedec());
    }
```

- [ ] **Step 4: Registrar a rota**

Em `SDC/routes/api.php`, no grupo `ajuda-humanitaria`, apos a rota `liberacoes`:

```php
        Route::get('liberacoes/cedec', [\App\Http\Controllers\Api\V1\AjudaHumanitaria\LiberacaoApiController::class, 'cedec'])
            ->name('liberacoes.cedec')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:30,1']);
```

O throttle e mais apertado que o dos demais: este endpoint nao tem filtro
obrigatorio e, com a carga de itens completa, e a consulta mais pesada dos
quatro.

- [ ] **Step 5: Rodar os testes e confirmar que passam**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=LiberacaoCedecApiTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/LiberacaoApiController.php \
        SDC/routes/api.php \
        SDC/tests/Feature/AjudaHumanitaria/Api/LiberacaoCedecApiTest.php
git commit -m "✨ feat(ajuda-humanitaria): API plana de liberacoes para o CEDEC com Swagger"
```

---

### Task 6: Endpoint de consolidado de pedidos

Paridade com `GET /api/listPedidoAh`, nos dois modos do legado: por decreto e
por BI. `pedidos_ah` esta vazia; o endpoint entrega contrato e filtros corretos.

**Files:**
- Create: `SDC/app/Modules/AjudaHumanitaria/Services/PedidoConsolidadoApiService.php`
- Create: `SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaPedidoConsolidadoRequest.php`
- Create: `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/PedidoConsolidadoController.php`
- Modify: `SDC/routes/api.php`
- Test: `SDC/tests/Feature/AjudaHumanitaria/Api/PedidoConsolidadoApiTest.php` (criar)

**Interfaces:**
- Consumes: grupo de rotas da Task 3.
- Produces: `PedidoConsolidadoApiService::porDecreto(string $numDecreto): array<string, list<array>>` e `PedidoConsolidadoApiService::paraBi(): list<array>`. Rota `api.v1.ajuda-humanitaria.pedidos.consolidado`.

Colunas reais conferidas no banco em 2026-08-19, e usadas nos trechos abaixo:
`pedidos_ah.numero_decreto`, `pedidos_ah.municipio_id`, `pedidos_ah.status`,
`pedidos_ah.deleted_at`, `pedido_ah_itens.pedido_ah_id`,
`pedido_ah_itens.descricao_item`, `pedido_ah_itens.qtd`,
`pedido_ah_itens.tipo`. `descricao_item` e coluna propria do item, igual ao
legado -- nao ha join com `materiais_ah`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/AjudaHumanitaria/Api/PedidoConsolidadoApiTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PedidoConsolidadoApiTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/v1/ajuda-humanitaria/pedidos/consolidado';

    private function usuarioComPermissao(string ...$perms): User
    {
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo($perms);

        return $user;
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson(self::URL . '?bi=1')->assertUnauthorized();
    }

    public function test_exige_permissao(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::URL . '?bi=1')
            ->assertForbidden();
    }

    public function test_exige_um_dos_dois_modos(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.pedidos.view'), 'sanctum')
            ->getJson(self::URL)
            ->assertStatus(422)
            ->assertJsonValidationErrors('decreto_id');
    }

    public function test_modo_bi_responde_lista_vazia_sem_erro(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.pedidos.view'), 'sanctum')
            ->getJson(self::URL . '?bi=1')
            ->assertOk()
            ->assertJson([]);
    }

    public function test_modo_decreto_responde_objeto_vazio_sem_erro(): void
    {
        $this->actingAs($this->usuarioComPermissao('humanitaria.pedidos.view'), 'sanctum')
            ->getJson(self::URL . '?decreto_id=123')
            ->assertOk()
            ->assertJson([]);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar a falha**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=PedidoConsolidadoApiTest`
Expected: FAIL com 404 na rota.

- [ ] **Step 3: Escrever o FormRequest**

Criar `SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaPedidoConsolidadoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * O legado aceitava decreto_id ou bi, e devolvia erro de variavel indefinida
 * quando nenhum dos dois vinha. Aqui a ausencia dos dois e 422.
 */
final class ConsultaPedidoConsolidadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decreto_id' => ['required_without:bi', 'nullable', 'string', 'max:60'],
            'bi'         => ['required_without:decreto_id', 'nullable'],
        ];
    }

    public function decretoId(): ?string
    {
        $valor = $this->validated('decreto_id');

        return $valor === null ? null : (string) $valor;
    }

    public function modoBi(): bool
    {
        return $this->decretoId() === null;
    }
}
```

- [ ] **Step 4: Escrever o service**

Criar `SDC/app/Modules/AjudaHumanitaria/Services/PedidoConsolidadoApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Paridade com o endpoint listPedidoAh do Laravel legado.
 *
 * pedidos_ah esta vazia: o historico do legado (aju_h_pedido_pedid e
 * aju_h_pedido_itens) nao esta no mapa de carga. Os dois modos respondem vazio
 * enquanto isso, e passam a responder sem alteracao de codigo quando a carga
 * existir.
 *
 * O legado gravava tramit como texto ('atendido', 'finalizado'); aqui o recorte
 * usa status inteiro, fonte unica do modulo.
 *
 * Somente leitura.
 */
final class PedidoConsolidadoApiService
{
    /** @var list<int> */
    private const STATUS_CONCLUIDOS = [
        StatusPedidoAh::Atendido->value,
        StatusPedidoAh::Finalizado->value,
    ];

    /**
     * Modo decreto_id: agrupado por municipio, so pedidos concluidos.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function porDecreto(string $numDecreto): array
    {
        return $this->consultaBase()
            ->where('p.numero_decreto', $numDecreto)
            ->whereIn('p.status', self::STATUS_CONCLUIDOS)
            ->groupBy('p.status', 'i.descricao_item', 'i.tipo', 'mun.nome', 'p.numero_decreto')
            ->orderBy('i.descricao_item')
            ->get()
            ->map(fn (object $l): array => $this->formatar($l))
            ->groupBy('municipio')
            ->map(static fn (Collection $doMunicipio): array => $doMunicipio->values()->all())
            ->all();
    }

    /**
     * Modo bi: lista plana, sem recorte de status.
     *
     * @return list<array<string, mixed>>
     */
    public function paraBi(): array
    {
        return $this->consultaBase()
            ->groupBy('p.status', 'i.descricao_item', 'i.tipo', 'mun.nome', 'p.numero_decreto')
            ->get()
            ->map(fn (object $l): array => $this->formatar($l))
            ->all();
    }

    private function consultaBase(): \Illuminate\Database\Query\Builder
    {
        return DB::table('pedidos_ah as p')
            ->join('pedido_ah_itens as i', 'i.pedido_ah_id', '=', 'p.id')
            ->join('municipios as mun', 'p.municipio_id', '=', 'mun.id')
            ->whereNull('p.deleted_at')
            ->select([
                'p.status',
                // Coluna propria do item, como no legado. Nao vem de materiais_ah:
                // o texto pedido pelo municipio e preservado como foi escrito.
                'i.descricao_item',
                'i.tipo as tp_item',
                'mun.nome as municipio',
                'p.numero_decreto',
                DB::raw('SUM(i.qtd) AS total_qtd'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatar(object $linha): array
    {
        return [
            'status'         => StatusPedidoAh::tryFrom((int) $linha->status)?->name,
            'descricao_item' => (string) $linha->descricao_item,
            'tp_item'        => $linha->tp_item === null ? null : trim((string) $linha->tp_item),
            'municipio'      => (string) $linha->municipio,
            'num_decreto'    => $linha->numero_decreto === null ? null : (string) $linha->numero_decreto,
            'total_qtd'      => (string) $linha->total_qtd,
        ];
    }
}
```

- [ ] **Step 5: Escrever o controller**

Criar `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/PedidoConsolidadoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Requests\ConsultaPedidoConsolidadoRequest;
use App\Modules\AjudaHumanitaria\Services\PedidoConsolidadoApiService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="AhPedidoConsolidadoItem",
 *     type="object",
 *     title="Item consolidado de pedido de ajuda humanitaria",
 *     @OA\Property(property="status", type="string", nullable=true, enum={"EdicaoCompdec","AnaliseDlog","AnaliseDiretorDlog","Aprovado","AguardandoDisponibilidade","AguardandoRetirada","Atendido","Cancelado","Reprovado","Finalizado"}, example="Atendido"),
 *     @OA\Property(property="descricao_item", type="string", example="CESTA BASICA"),
 *     @OA\Property(property="tp_item", type="string", nullable=true, enum={"P","L"}, example="L", description="P = pedido pelo municipio, L = liberado pelo CEDEC."),
 *     @OA\Property(property="municipio", type="string", example="Belo Horizonte"),
 *     @OA\Property(property="num_decreto", type="string", nullable=true, example="123"),
 *     @OA\Property(property="total_qtd", type="string", example="500.000")
 * )
 */
final class PedidoConsolidadoController extends Controller
{
    public function __construct(private readonly PedidoConsolidadoApiService $servico)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ajuda-humanitaria/pedidos/consolidado",
     *     tags={"Ajuda Humanitaria"},
     *     summary="Consolidado de itens de pedido",
     *     description="Paridade com o endpoint listPedidoAh do sistema legado. Informe decreto_id (agrupa por municipio, somente pedidos Atendido ou Finalizado) ou bi (lista plana, sem recorte de status). Retorna vazio enquanto o historico de pedidos do legado nao estiver carregado no banco novo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="decreto_id", in="query", required=false, description="Numero do decreto. Obrigatorio se bi nao for informado", @OA\Schema(type="string", example="123")),
     *     @OA\Parameter(name="bi", in="query", required=false, description="Qualquer valor ativa o modo plano. Obrigatorio se decreto_id nao for informado", @OA\Schema(type="string", example="1")),
     *     @OA\Response(
     *         response=200,
     *         description="Modo decreto_id devolve objeto com chave = municipio; modo bi devolve array plano",
     *         @OA\JsonContent(oneOf={
     *             @OA\Schema(type="object", additionalProperties=@OA\Schema(type="array", @OA\Items(ref="#/components/schemas/AhPedidoConsolidadoItem"))),
     *             @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/AhPedidoConsolidadoItem"))
     *         })
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem permissao humanitaria.pedidos.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Nem decreto_id nem bi informado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function __invoke(ConsultaPedidoConsolidadoRequest $request): JsonResponse
    {
        if ($request->modoBi()) {
            return response()->json($this->servico->paraBi());
        }

        return response()->json($this->servico->porDecreto((string) $request->decretoId()));
    }
}
```

- [ ] **Step 6: Registrar a rota**

Em `SDC/routes/api.php`, no grupo `ajuda-humanitaria`, apos as rotas de liberacoes:

```php
        Route::get('pedidos/consolidado', \App\Http\Controllers\Api\V1\AjudaHumanitaria\PedidoConsolidadoController::class)
            ->name('pedidos.consolidado')
            ->middleware(['can:humanitaria.pedidos.view', 'throttle:60,1']);
```

- [ ] **Step 7: Rodar os testes e confirmar que passam**

Run: `docker exec -w /var/www newsdc_dev_app php artisan test --filter=PedidoConsolidadoApiTest`
Expected: PASS.

- [ ] **Step 8: Rodar a suite do modulo e gerar o Swagger**

```bash
docker exec -w /var/www newsdc_dev_app php artisan test --filter=AjudaHumanitaria
docker exec -w /var/www newsdc_dev_app php artisan l5-swagger:generate
```
Expected: suite verde; os quatro endpoints presentes no JSON gerado, sob a tag `Ajuda Humanitaria`.

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Services/PedidoConsolidadoApiService.php \
        SDC/app/Modules/AjudaHumanitaria/Requests/ConsultaPedidoConsolidadoRequest.php \
        SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria/PedidoConsolidadoController.php \
        SDC/routes/api.php \
        SDC/tests/Feature/AjudaHumanitaria/Api/PedidoConsolidadoApiTest.php
git commit -m "✨ feat(ajuda-humanitaria): API de consolidado de pedidos com Swagger"
```

---

## Verificacao final

Corresponde a secao 8 do spec.

- [ ] `docker exec -w /var/www newsdc_dev_app php artisan l5-swagger:generate` sem erro, e os quatro endpoints visiveis na UI do Swagger
- [ ] `GET /api/v1/ajuda-humanitaria/estoque/saldo-cesta` devolve os saldos consolidados por deposito, com `valor` e `peso` preenchidos
- [ ] `GET /api/v1/ajuda-humanitaria/liberacoes?ano_comeco=1900` tem `meta.totais` igual a `SELECT status, count(*) FROM ajuda_h_liberacoes WHERE deleted_at IS NULL GROUP BY status`
- [ ] `GET /api/v1/ajuda-humanitaria/liberacoes/cedec` e `GET /api/v1/ajuda-humanitaria/pedidos/consolidado?bi=1` respondem 200 com lista vazia
- [ ] `docker exec -w /var/www newsdc_dev_app php artisan test --filter=AjudaHumanitaria` verde
- [ ] Nenhum arquivo de teste temporario ou linha de log de depuracao ficou no commit
