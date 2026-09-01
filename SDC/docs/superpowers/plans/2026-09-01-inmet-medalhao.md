# Inmet no Medalhao (Fase 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrar o modulo Inmet para o kernel Medalhao, dando a ele serie historica, geometria PostGIS e agregacao materializada, e extrair o mapa Leaflet duplicado entre Inmet e Sismos para um componente unico.

**Architecture:** O Inmet vira o segundo consumidor do kernel da Fase 1. Um ingestor busca as 68 estacoes de MG com `Http::pool` e consolida num unico payload de Bronze; um normalizador produz DTOs; um repositorio faz upsert na dimensao de estacao (schema `public`) e no fato (`silver.leituras_inmet`); duas matviews em `gold` materializam o mapa e as estatisticas. A unica mudanca em codigo da Fase 1 e tirar o `if ($grupo === 'sismos')` do kernel para config.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL + PostGIS 3.6, Redis (fila `medalhao`), Vue 3 + Inertia + Leaflet 1.9, PHPUnit 11.

**Spec:** `SDC/docs/superpowers/specs/2026-09-01-inmet-medalhao-design.md`

## Global Constraints

- Todo arquivo PHP comeca com `declare(strict_types=1);`.
- Classes de teste sao `final` e os metodos usam snake_case em pt-BR (`test_upsert_nao_duplica_leitura`).
- **Sem emojis no codigo** (regra de ouro 2).
- **Sem acentos** em nome de classe, metodo, coluna, chave de config e mensagem de log. String voltada ao usuario final pode ter acento.
- Toda migration que toca schema, PostGIS ou matview comeca com `if (DB::getDriverName() !== 'pgsql') { return; }`.
- Nenhum calculo de agregacao em PHP na camada de entrega. O controller le apenas o schema `gold`.
- Nenhum job novo fora da fila `medalhao`.
- Commits em gitmoji: `<emoji> tipo(escopo): descricao em pt-BR`. Escopo `inmet` ou `medalhao`.
- **Nao incluir trailer `Co-Authored-By`** nos commits.
- **Arquivos de teste NAO entram nos commits.** `SDC/.gitignore` linha 39 ignora `tests`, e a regra de ouro 10 diz o mesmo. Os testes deste plano existem no worktree como motor do TDD e devem passar, mas cada `git add` inclui somente codigo de producao. Onde um passo de commit listar arquivo sob `tests/`, ignore essa parte.
- Migrations novas seguem `2026_09_01_NNNNNN_descricao.php`.
- Testes que dependem de schema nomeado, PostGIS ou matview sao pgsql-only e pulam com `markTestSkipped` fora do Postgres.

## Ambiente de execucao

O PHP do host e 8.3 e o vendor do projeto exige 8.4. Rode tudo em container:

```bash
# Da raiz do repo. Ajuste WT se estiver em outro worktree.
WT="C:/Users/x24679188/Documents/Github/NewSDC/SDC"
art() {
  rm -f "${WT}/bootstrap/cache/config.php" "${WT}"/bootstrap/cache/routes-*.php
  MSYS_NO_PATHCONV=1 docker run --rm --network newsdc-dev_default \
    -v "${WT}:/app" -w /app \
    -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
    -e DB_DATABASE="${DB_DATABASE:-sdc_medalhao}" -e DB_USERNAME=sdc -e DB_PASSWORD=secret \
    -e REDIS_HOST=redis -e REDIS_PORT=6379 \
    newsdc-swoole-dev:latest "$@"
}
```

Banco de teste dedicado, nunca o `sdc`:

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres \
  -c "CREATE DATABASE sdc_medalhao TEMPLATE template_postgis OWNER sdc;"
art php artisan migrate:fresh --force
art php artisan db:seed --class=MunicipiosMGSeeder --force
```

## Constantes da fonte (medidas em 2026-09-01)

```
Inventario:  GET https://apitempo.inmet.gov.br/estacoes/T
Leituras:    GET https://apitempo.inmet.gov.br/token/estacao/{inicio}/{fim}/{codigoEstacao}/{token}
```

- O codigo da estacao vem **antes** do token. Sem ele: `404 E_ROUTE_NOT_FOUND`.
- **User-Agent de navegador e obrigatorio.** Sem ele o servidor corta a conexao (`curl 56`), mesmo com TLS negociado.
- O inventario traz 674 estacoes automaticas no Brasil, **68 em MG, 61 operantes**.
- Cada chamada de leitura devolve **24 linhas** (`HR_MEDICAO` de `'0000'` a `'2300'`), incluindo horas futuras com todos os valores `NULL`.
- Todos os valores numericos vem como **string**.

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `config/medalhao.php` | Ganha `refresh_gold`, grupo `inmet` em `persistidores`, bloco `inmet` |
| `app/Modules/Medalhao/Jobs/NormalizarSilverJob.php` | Despacha refresh do Gold por config, sem citar dominio |
| `app/Modules/Inmet/DTOs/EstacaoDTO.php` | Dimensao vinda do inventario |
| `app/Modules/Inmet/DTOs/LeituraMeteorologicaDTO.php` | Existente; coordenada nullable |
| `app/Modules/Inmet/Services/InmetApiClient.php` | URL correta, User-Agent, token por env |
| `app/Modules/Inmet/Ingestores/InmetApiIngestor.php` | Inventario + `Http::pool` das leituras, consolidado |
| `app/Modules/Inmet/Normalizadores/InmetJsonNormalizador.php` | JSON consolidado -> DTOs |
| `app/Modules/Inmet/Repositories/InmetRepository.php` | Upsert dimensao + Silver; leitura do Gold |
| `app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php` | `REFRESH ... CONCURRENTLY` das duas matviews |
| `app/Modules/Inmet/InmetServiceProvider.php` | Registra a fonte no `IngestorRegistry` |
| `app/Modules/Inmet/Controllers/InmetIndexController.php` | Le apenas `gold` |
| `app/Modules/Inmet/Services/InmetService.php` | Perde metodos mortos e a agregacao em PHP |
| `resources/js/Components/Mapa/MapaLeaflet.vue` | Mapa unico, consumido pelas duas paginas |

---

### Task 1: Kernel — refresh do Gold por config

**Files:**
- Modify: `SDC/config/medalhao.php`
- Modify: `SDC/app/Modules/Medalhao/Jobs/NormalizarSilverJob.php`
- Test: `SDC/tests/Feature/Medalhao/RefreshGoldPorConfigTest.php`

**Interfaces:**
- Produces: chave de config `medalhao.refresh_gold` mapeando `string $grupo => class-string`. As Tasks 4 e 8 dependem dela.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use App\Modules\Medalhao\Jobs\NormalizarSilverJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RefreshGoldPorConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Bronze exige PostgreSQL.');
        }

        DB::statement('TRUNCATE bronze.ingestao_bruta CASCADE');
    }

    public function test_despacha_o_job_configurado_para_o_grupo(): void
    {
        Bus::fake();

        $bronze = IngestaoBruta::create([
            'fonte' => 'usp-fdsn',
            'conteudo_bruto' => '',
            'formato' => 'fdsn-text',
            'hash_conteudo' => hash('sha256', 'vazio-config'),
            'meta' => [],
            'coletado_em' => now(),
        ]);

        (new NormalizarSilverJob($bronze->id, 'usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        Bus::assertDispatched(AtualizarGoldSismosJob::class);
    }

    public function test_grupo_sem_entrada_de_config_nao_despacha_nada(): void
    {
        Bus::fake();
        config()->set('medalhao.refresh_gold', []);

        $bronze = IngestaoBruta::create([
            'fonte' => 'usp-fdsn',
            'conteudo_bruto' => '',
            'formato' => 'fdsn-text',
            'hash_conteudo' => hash('sha256', 'vazio-sem-config'),
            'meta' => [],
            'coletado_em' => now(),
        ]);

        (new NormalizarSilverJob($bronze->id, 'usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        Bus::assertNothingDispatched();
    }

    public function test_o_kernel_nao_cita_nome_de_grupo_no_codigo(): void
    {
        $fonte = file_get_contents(app_path('Modules/Medalhao/Jobs/NormalizarSilverJob.php'));

        $this->assertStringNotContainsString("'sismos'", $fonte);
        $this->assertStringNotContainsString("'inmet'", $fonte);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=RefreshGoldPorConfigTest`
Expected: FAIL — `test_grupo_sem_entrada_de_config_nao_despacha_nada` e `test_o_kernel_nao_cita_nome_de_grupo_no_codigo` falham, porque o `if` hardcoded ignora config.

- [ ] **Step 3: Adicionar `refresh_gold` ao config**

Em `SDC/config/medalhao.php`, logo abaixo do bloco `persistidores`:

```php
    // Mapa grupo -> job que refaz as matviews da camada Gold. Fica em config
    // pelo mesmo motivo que 'persistidores': o kernel nao conhece dominio, e
    // fonte nova nao deve exigir edicao no NormalizarSilverJob.
    'refresh_gold' => [
        'sismos' => \App\Modules\Sismos\Jobs\AtualizarGoldSismosJob::class,
    ],
```

- [ ] **Step 4: Trocar o `if` pelo despacho por config**

Em `NormalizarSilverJob::handle`, substituir:

```php
        if ($grupo === 'sismos') {
            AtualizarGoldSismosJob::dispatch();
        }
```

por:

```php
        $jobGold = config("medalhao.refresh_gold.{$grupo}");

        if ($jobGold !== null) {
            $jobGold::dispatch();
        }
```

E remover o `use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;` do topo do arquivo — o kernel deixa de importar dominio.

- [ ] **Step 5: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter="RefreshGoldPorConfigTest|Medalhao|Sismos"`
Expected: PASS. A suite de Medalhao/Sismos da Fase 1 continua verde.

- [ ] **Step 6: Commit**

```bash
git add SDC/config/medalhao.php SDC/app/Modules/Medalhao/Jobs/NormalizarSilverJob.php
git commit -m "♻️ refactor(medalhao): refresh do Gold por config, sem dominio no kernel"
```

---

### Task 2: Dimensao de estacao com PostGIS

**Files:**
- Create: `SDC/database/migrations/2026_09_01_000001_add_geom_to_estacoes_meteorologicas.php`
- Create: `SDC/app/Modules/Inmet/DTOs/EstacaoDTO.php`
- Modify: `SDC/app/Modules/Inmet/Models/EstacaoMeteorologica.php`
- Test: `SDC/tests/Unit/Inmet/EstacaoDTOTest.php`

**Interfaces:**
- Produces: `EstacaoDTO` readonly com `codigo, nome, uf, latitude, longitude, altitude, situacao, tipo` e `EstacaoDTO::fromInventarioArray(array): ?self`. Retorna `null` quando falta coordenada. Consumido pelas Tasks 3 e 7.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Inmet;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use Tests\TestCase;

final class EstacaoDTOTest extends TestCase
{
    /** Registro real do inventario /estacoes/T, capturado em 2026-09-01. */
    private function registro(array $sobrepor = []): array
    {
        return array_merge([
            'CD_ESTACAO' => 'A549',
            'DC_NOME' => 'AGUAS VERMELHAS',
            'SG_ESTADO' => 'MG',
            'CD_SITUACAO' => 'Operante',
            'TP_ESTACAO' => 'Automatica',
            'VL_LATITUDE' => '-15.75166666',
            'VL_LONGITUDE' => '-41.45777777',
            'VL_ALTITUDE' => '754.07',
        ], $sobrepor);
    }

    public function test_converte_registro_do_inventario(): void
    {
        $dto = EstacaoDTO::fromInventarioArray($this->registro());

        $this->assertSame('A549', $dto->codigo);
        $this->assertSame('AGUAS VERMELHAS', $dto->nome);
        $this->assertSame('MG', $dto->uf);
        $this->assertSame(-15.75166666, $dto->latitude);
        $this->assertSame(-41.45777777, $dto->longitude);
        $this->assertSame(754.07, $dto->altitude);
        $this->assertSame('Operante', $dto->situacao);
    }

    public function test_estacao_sem_coordenada_e_descartada_em_vez_de_ir_para_zero(): void
    {
        $this->assertNull(EstacaoDTO::fromInventarioArray($this->registro(['VL_LATITUDE' => null])));
        $this->assertNull(EstacaoDTO::fromInventarioArray($this->registro(['VL_LONGITUDE' => ''])));
    }

    public function test_coordenada_zero_tambem_e_descartada(): void
    {
        // Lat 0 / lon 0 e o Golfo da Guine. Nenhuma estacao do INMET fica la, e
        // plotar um ponto errado em mapa de Defesa Civil tem consequencia
        // operacional.
        $this->assertNull(EstacaoDTO::fromInventarioArray($this->registro(['VL_LATITUDE' => '0'])));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=EstacaoDTOTest`
Expected: FAIL — `Class "App\Modules\Inmet\DTOs\EstacaoDTO" not found`.

- [ ] **Step 3: Criar o DTO**

`SDC/app/Modules/Inmet/DTOs/EstacaoDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\DTOs;

final readonly class EstacaoDTO
{
    public function __construct(
        public string $codigo,
        public string $nome,
        public string $uf,
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
        public ?string $situacao = null,
        public ?string $tipo = null,
    ) {
    }

    /**
     * Converte um registro de /estacoes/T. Devolve null quando a estacao nao
     * tem coordenada utilizavel — o chamador descarta em vez de plotar em zero.
     *
     * @param array<string, mixed> $dados
     */
    public static function fromInventarioArray(array $dados): ?self
    {
        $lat = self::numero($dados['VL_LATITUDE'] ?? null);
        $lon = self::numero($dados['VL_LONGITUDE'] ?? null);

        if ($lat === null || $lon === null || $lat === 0.0 || $lon === 0.0) {
            return null;
        }

        return new self(
            codigo: (string) ($dados['CD_ESTACAO'] ?? ''),
            nome: (string) ($dados['DC_NOME'] ?? ''),
            uf: (string) ($dados['SG_ESTADO'] ?? $dados['UF'] ?? ''),
            latitude: $lat,
            longitude: $lon,
            altitude: self::numero($dados['VL_ALTITUDE'] ?? null),
            situacao: isset($dados['CD_SITUACAO']) ? (string) $dados['CD_SITUACAO'] : null,
            tipo: isset($dados['TP_ESTACAO']) ? (string) $dados['TP_ESTACAO'] : null,
        );
    }

    /** A API devolve todo numero como string, e ausencia como null ou ''. */
    private static function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) $valor;
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter=EstacaoDTOTest`
Expected: PASS.

- [ ] **Step 5: Criar a migration da coluna geografica**

`SDC/database/migrations/2026_09_01_000001_add_geom_to_estacoes_meteorologicas.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // A dimensao permanece no schema public: e cadastro de dominio,
        // referenciado pela aplicacao, nao artefato do pipeline. As matviews de
        // gold fazem join entre schemas, o que o Postgres resolve sem custo.
        DB::statement('ALTER TABLE estacoes_meteorologicas ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326) NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_estacoes_meteorologicas_geom ON estacoes_meteorologicas USING GIST (geom)');
        DB::statement('ALTER TABLE estacoes_meteorologicas ADD COLUMN IF NOT EXISTS situacao varchar(32) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_estacoes_meteorologicas_geom');
        DB::statement('ALTER TABLE estacoes_meteorologicas DROP COLUMN IF EXISTS geom');
        DB::statement('ALTER TABLE estacoes_meteorologicas DROP COLUMN IF EXISTS situacao');
    }
};
```

- [ ] **Step 6: Adicionar `situacao` ao fillable do model**

Em `SDC/app/Modules/Inmet/Models/EstacaoMeteorologica.php`, incluir `'situacao'` no array `$fillable`. Nao adicionar `geom`: ela e escrita por SQL cru no repositorio, porque exige `ST_SetSRID(ST_MakePoint(...))`.

- [ ] **Step 7: Rodar a migration e conferir**

```bash
art php artisan migrate --force
docker exec newsdc_dev_db psql -U sdc -d sdc_medalhao -c "\d estacoes_meteorologicas" | grep -E "geom|situacao"
```

Expected: as duas colunas aparecem, `geom` como `geometry(Point,4326)`.

- [ ] **Step 8: Commit**

```bash
git add SDC/database/migrations/2026_09_01_000001_add_geom_to_estacoes_meteorologicas.php \
        SDC/app/Modules/Inmet/DTOs/EstacaoDTO.php \
        SDC/app/Modules/Inmet/Models/EstacaoMeteorologica.php
git commit -m "🗃️ db(inmet): dimensao de estacao ganha geometria PostGIS"
```

---

### Task 3: Camada Silver e o upsert da dimensao

**Files:**
- Create: `SDC/database/migrations/2026_09_01_000002_create_silver_leituras_inmet.php`
- Create: `SDC/app/Modules/Inmet/Repositories/InmetRepository.php`
- Modify: `SDC/app/Modules/Inmet/DTOs/LeituraMeteorologicaDTO.php`
- Test: `SDC/tests/Feature/Inmet/InmetRepositoryTest.php`

**Interfaces:**
- Consumes: `EstacaoDTO` (Task 2).
- Produces: `InmetRepository::upsertLote(iterable $dtos, ?int $ingestaoId = null): int`, contrato que o kernel exige do persistidor. Aceita mistura de `EstacaoDTO` e `LeituraMeteorologicaDTO` no mesmo iteravel. Tambem `InmetRepository::totalLeituras(): int`.

- [ ] **Step 1: Tornar a coordenada do DTO de leitura nullable**

Em `LeituraMeteorologicaDTO`, trocar as duas propriedades e o construtor:

```php
        public ?float $latitude,
        public ?float $longitude,
```

E em `fromInmetArray`, trocar o `?? 0` por parse honesto:

```php
            latitude: self::parseFloat($data['VL_LATITUDE'] ?? null),
            longitude: self::parseFloat($data['VL_LONGITUDE'] ?? null),
```

O `?? 0` mandava estacao sem coordenada para lat 0, lon 0.

- [ ] **Step 2: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inmet;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inmet\Enums\NivelPrecipitacao;
use App\Modules\Inmet\Repositories\InmetRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InmetRepositoryTest extends TestCase
{
    private InmetRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Silver exige PostgreSQL e PostGIS.');
        }

        DB::statement('TRUNCATE silver.leituras_inmet');
        DB::statement('DELETE FROM estacoes_meteorologicas WHERE codigo LIKE ?', ['TST%']);

        $this->repo = app(InmetRepository::class);
    }

    private function estacao(): EstacaoDTO
    {
        return new EstacaoDTO(
            codigo: 'TST1',
            nome: 'ESTACAO DE TESTE',
            uf: 'MG',
            latitude: -19.88388888,
            longitude: -43.96944443,
            altitude: 850.0,
            situacao: 'Operante',
            tipo: 'Automatica',
        );
    }

    private function leitura(string $hora, ?float $chuva = 1.5): LeituraMeteorologicaDTO
    {
        return new LeituraMeteorologicaDTO(
            codigoEstacao: 'TST1',
            nomeEstacao: 'ESTACAO DE TESTE',
            municipio: 'BELO HORIZONTE',
            dataHoraMedicao: Carbon::parse("2026-09-01 {$hora}", 'UTC'),
            temperatura: 22.8,
            umidade: 62.0,
            precipitacao: $chuva,
            velocidadeVento: 2.4,
            pressao: 918.2,
            nivelPrecipitacao: NivelPrecipitacao::fromMilimetros($chuva ?? 0.0),
            condicao: 'rain',
            latitude: -19.88388888,
            longitude: -43.96944443,
        );
    }

    public function test_upsert_grava_dimensao_com_geometria_valida(): void
    {
        $this->repo->upsertLote([$this->estacao()]);

        $linha = DB::selectOne(
            'SELECT ST_Y(geom) AS lat, ST_X(geom) AS lon, situacao FROM estacoes_meteorologicas WHERE codigo = ?',
            ['TST1']
        );

        $this->assertEqualsWithDelta(-19.88388888, (float) $linha->lat, 0.0000001);
        $this->assertEqualsWithDelta(-43.96944443, (float) $linha->lon, 0.0000001);
        $this->assertSame('Operante', $linha->situacao);
    }

    public function test_upsert_de_leitura_nao_duplica_a_mesma_hora(): void
    {
        $this->repo->upsertLote([$this->estacao(), $this->leitura('10:00')]);
        $this->repo->upsertLote([$this->estacao(), $this->leitura('10:00', 9.9)]);

        $this->assertSame(1, $this->repo->totalLeituras());

        $chuva = DB::scalar(
            'SELECT precipitacao FROM silver.leituras_inmet WHERE codigo_estacao = ? AND medido_em = ?',
            ['TST1', '2026-09-01 10:00:00+00']
        );

        $this->assertEqualsWithDelta(9.9, (float) $chuva, 0.001);
    }

    public function test_horas_distintas_geram_linhas_distintas(): void
    {
        $this->repo->upsertLote([$this->estacao(), $this->leitura('10:00'), $this->leitura('11:00')]);

        $this->assertSame(2, $this->repo->totalLeituras());
    }

    public function test_upsert_devolve_a_quantidade_gravada(): void
    {
        $total = $this->repo->upsertLote([$this->estacao(), $this->leitura('10:00'), $this->leitura('11:00')]);

        $this->assertSame(3, $total);
    }

    public function test_resolve_o_municipio_real_pela_coordenada(): void
    {
        // O inventario do INMET nao traz municipio: DC_NOME e nome de estacao.
        // A coordenada de TST1 e a da estacao A521 (Pampulha), entao o
        // municipio tem de sair "Belo Horizonte", e nao o nome da estacao.
        if ((int) DB::scalar("SELECT count(*) FROM municipios WHERE uf = 'MG'") === 0) {
            $this->markTestSkipped('Exige MunicipiosMGSeeder rodado.');
        }

        $this->repo->upsertLote([$this->estacao()]);

        $municipio = DB::scalar('SELECT municipio FROM estacoes_meteorologicas WHERE codigo = ?', ['TST1']);

        $this->assertSame('Belo Horizonte', $municipio);
    }
}
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=InmetRepositoryTest`
Expected: FAIL — `relation "silver.leituras_inmet" does not exist`.

- [ ] **Step 4: Criar a migration do fato**

`SDC/database/migrations/2026_09_01_000002_create_silver_leituras_inmet.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Fato puro: nao repete nome nem coordenada da estacao, que vivem na
        // dimensao. Diferente de silver.sismos, onde repetir se justifica porque
        // o evento sismico nao tem entidade estavel por tras. Estacao tem, e
        // isso faz corrigir uma coordenada corrigir o historico num update.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.leituras_inmet (
                id              bigserial PRIMARY KEY,
                codigo_estacao  varchar(16)  NOT NULL,
                medido_em       timestamptz  NOT NULL,
                temperatura     numeric(6,2) NULL,
                umidade         numeric(6,2) NULL,
                precipitacao    numeric(8,2) NULL,
                velocidade_vento numeric(6,2) NULL,
                pressao         numeric(8,2) NULL,
                ingestao_id     bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at      timestamptz  NOT NULL DEFAULT now(),
                updated_at      timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_leituras_inmet UNIQUE (codigo_estacao, medido_em)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_medido ON silver.leituras_inmet (medido_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_estacao ON silver.leituras_inmet (codigo_estacao, medido_em DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.leituras_inmet');
    }
};
```

- [ ] **Step 5: Criar o repositorio**

`SDC/app/Modules/Inmet/Repositories/InmetRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Repositories;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use Illuminate\Support\Facades\DB;

final class InmetRepository
{
    private const CHUNK = 500;

    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * Aceita os dois tipos de DTO no mesmo iteravel porque uma coleta traz
     * inventario e leituras juntos, e o kernel chama o persistidor uma vez.
     *
     * @param iterable<EstacaoDTO|LeituraMeteorologicaDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $estacoes = [];
        $leituras = [];
        $total = 0;

        foreach ($dtos as $dto) {
            if ($dto instanceof EstacaoDTO) {
                $estacoes[] = $dto;
            } else {
                $leituras[] = $dto;
            }

            if (count($estacoes) >= self::CHUNK) {
                $total += $this->gravarEstacoes($estacoes);
                $estacoes = [];
            }

            if (count($leituras) >= self::CHUNK) {
                $total += $this->gravarLeituras($leituras, $ingestaoId);
                $leituras = [];
            }
        }

        if ($estacoes !== []) {
            $total += $this->gravarEstacoes($estacoes);
        }

        if ($leituras !== []) {
            $total += $this->gravarLeituras($leituras, $ingestaoId);
        }

        return $total;
    }

    public function totalLeituras(): int
    {
        return (int) DB::scalar('SELECT count(*) FROM silver.leituras_inmet');
    }

    /**
     * SQL cru porque geom exige ST_SetSRID(ST_MakePoint(...)), que o upsert()
     * do Eloquent nao expressa.
     *
     * @param list<EstacaoDTO> $lote
     */
    private function gravarEstacoes(array $lote): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), now(), now())';

            array_push(
                $bindings,
                $dto->codigo,
                $dto->nome,
                $this->municipioMaisProximo($dto->latitude, $dto->longitude, $dto->uf) ?? $dto->nome,
                $dto->uf,
                $dto->latitude,
                $dto->longitude,
                $dto->altitude,
                $dto->tipo ?? 'automatica',
                $dto->situacao,
                $dto->longitude,     // PostGIS espera X (longitude) antes de Y
                $dto->latitude,
            );
        }

        DB::statement(
            'INSERT INTO estacoes_meteorologicas
                (codigo, nome, municipio, uf, latitude, longitude, altitude, tipo, situacao, geom, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo) DO UPDATE SET
                nome       = EXCLUDED.nome,
                uf         = EXCLUDED.uf,
                latitude   = EXCLUDED.latitude,
                longitude  = EXCLUDED.longitude,
                altitude   = EXCLUDED.altitude,
                tipo       = EXCLUDED.tipo,
                situacao   = EXCLUDED.situacao,
                geom       = EXCLUDED.geom,
                updated_at = now()',
            $bindings
        );

        return count($lote);
    }

    /**
     * O inventario do INMET nao traz municipio: traz DC_NOME, que e nome de
     * estacao ("BELO HORIZONTE - PAMPULHA"), e SG_ESTADO. Como a coluna
     * municipio e NOT NULL, resolve-se pelo centroide mais proximo entre os 853
     * municipios de MG ja semeados.
     *
     * ATENCAO: e centroide, nao contencao por poligono — a tabela municipios
     * tem latitude/longitude, nao geometria de area. Estacao perto de divisa
     * pode resolver para o municipio vizinho. Verificado para A521: resolve
     * Belo Horizonte a 5,3 km, contra Contagem a 10,3 km.
     *
     * O ::numeric no round e obrigatorio: o Postgres nao tem
     * round(double precision, integer).
     */
    private function municipioMaisProximo(float $latitude, float $longitude, string $uf): ?string
    {
        return DB::scalar(
            'SELECT m.nome
               FROM municipios m
              WHERE m.uf = ?
                AND m.latitude IS NOT NULL
                AND m.longitude IS NOT NULL
              ORDER BY ST_Distance(
                        ST_SetSRID(ST_MakePoint(m.longitude::float8, m.latitude::float8), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                      ) ASC
              LIMIT 1',
            [$uf, $longitude, $latitude]
        );
    }

    /** @param list<LeituraMeteorologicaDTO> $lote */
    private function gravarLeituras(array $lote, ?int $ingestaoId): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, now(), now())';

            array_push(
                $bindings,
                $dto->codigoEstacao,
                $dto->dataHoraMedicao->toIso8601String(),
                $dto->temperatura,
                $dto->umidade,
                $dto->precipitacao,
                $dto->velocidadeVento,
                $dto->pressao,
                $ingestaoId,
            );
        }

        DB::statement(
            'INSERT INTO silver.leituras_inmet
                (codigo_estacao, medido_em, temperatura, umidade, precipitacao,
                 velocidade_vento, pressao, ingestao_id, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo_estacao, medido_em) DO UPDATE SET
                temperatura      = EXCLUDED.temperatura,
                umidade          = EXCLUDED.umidade,
                precipitacao     = EXCLUDED.precipitacao,
                velocidade_vento = EXCLUDED.velocidade_vento,
                pressao          = EXCLUDED.pressao,
                ingestao_id      = EXCLUDED.ingestao_id,
                updated_at       = now()',
            $bindings
        );

        return count($lote);
    }
}
```

- [ ] **Step 6: Rodar a migration e os testes**

```bash
art php artisan migrate --force
art php vendor/bin/phpunit --filter=InmetRepositoryTest
```

Expected: PASS, 4 testes.

- [ ] **Step 7: Commit**

```bash
git add SDC/database/migrations/2026_09_01_000002_create_silver_leituras_inmet.php \
        SDC/app/Modules/Inmet/Repositories/InmetRepository.php \
        SDC/app/Modules/Inmet/DTOs/LeituraMeteorologicaDTO.php
git commit -m "🗃️ db(inmet): camada silver de leituras com upsert idempotente"
```

---

### Task 4: Camada Gold e o job de refresh

**Files:**
- Create: `SDC/database/migrations/2026_09_01_000003_create_gold_inmet_views.php`
- Create: `SDC/app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php`
- Modify: `SDC/app/Modules/Inmet/Repositories/InmetRepository.php`
- Modify: `SDC/config/medalhao.php`
- Test: `SDC/tests/Feature/Inmet/GoldInmetTest.php`

**Interfaces:**
- Consumes: `silver.leituras_inmet` e `estacoes_meteorologicas.geom` (Tasks 2 e 3).
- Produces: `InmetRepository::mapa(): Collection` e `InmetRepository::estatisticas(): array{total_estacoes:int, precipitacao_media:float, precipitacao_maxima:float, estacoes_com_chuva:int, temperatura_media:float, ultima_atualizacao:?string}`. Consumido pela Task 9.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inmet;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inmet\Enums\NivelPrecipitacao;
use App\Modules\Inmet\Jobs\AtualizarGoldInmetJob;
use App\Modules\Inmet\Repositories\InmetRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GoldInmetTest extends TestCase
{
    private InmetRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Gold exige PostgreSQL.');
        }

        DB::statement('TRUNCATE silver.leituras_inmet');
        DB::statement('DELETE FROM estacoes_meteorologicas WHERE codigo LIKE ?', ['TST%']);

        $this->repo = app(InmetRepository::class);
    }

    private function semear(): void
    {
        $estacao = new EstacaoDTO(
            codigo: 'TST1', nome: 'ESTACAO DE TESTE', uf: 'MG',
            latitude: -19.88, longitude: -43.96, altitude: 850.0,
            situacao: 'Operante', tipo: 'Automatica',
        );

        $leitura = fn (string $hora, float $chuva) => new LeituraMeteorologicaDTO(
            codigoEstacao: 'TST1', nomeEstacao: 'ESTACAO DE TESTE', municipio: 'BH',
            dataHoraMedicao: Carbon::now('UTC')->setTime((int) $hora, 0),
            temperatura: 20.0, umidade: 60.0, precipitacao: $chuva,
            velocidadeVento: 2.0, pressao: 900.0,
            nivelPrecipitacao: NivelPrecipitacao::fromMilimetros($chuva),
            condicao: 'rain', latitude: -19.88, longitude: -43.96,
        );

        // Duas horas: a matview do mapa deve trazer somente a mais recente.
        $this->repo->upsertLote([$estacao, $leitura('08', 1.0), $leitura('09', 4.0)]);

        (new AtualizarGoldInmetJob())->handle();
    }

    public function test_o_mapa_traz_uma_linha_por_estacao_com_a_leitura_mais_recente(): void
    {
        $this->semear();

        $linhas = $this->repo->mapa()->where('codigo_estacao', 'TST1');

        $this->assertCount(1, $linhas);
        $this->assertEqualsWithDelta(4.0, (float) $linhas->first()->precipitacao, 0.001);
    }

    public function test_o_mapa_expoe_lat_lon_extraidos_da_geometria(): void
    {
        $this->semear();

        $linha = $this->repo->mapa()->firstWhere('codigo_estacao', 'TST1');

        $this->assertEqualsWithDelta(-19.88, (float) $linha->latitude, 0.001);
        $this->assertEqualsWithDelta(-43.96, (float) $linha->longitude, 0.001);
    }

    public function test_estatisticas_vem_da_matview_e_nao_do_php(): void
    {
        $this->semear();

        $stats = $this->repo->estatisticas();

        $this->assertGreaterThanOrEqual(1, $stats['total_estacoes']);
        $this->assertGreaterThanOrEqual(1, $stats['estacoes_com_chuva']);
        $this->assertEqualsWithDelta(4.0, $stats['precipitacao_maxima'], 0.001);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=GoldInmetTest`
Expected: FAIL — `Class "App\Modules\Inmet\Jobs\AtualizarGoldInmetJob" not found`.

- [ ] **Step 3: Criar a migration das matviews**

`SDC/database/migrations/2026_09_01_000003_create_gold_inmet_views.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Uma linha por estacao, com a leitura mais recente. DISTINCT ON e a
        // forma do Postgres de fazer "primeiro por grupo" sem subconsulta.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_mapa AS
            SELECT DISTINCT ON (l.codigo_estacao)
                l.id,
                l.codigo_estacao,
                e.nome            AS nome_estacao,
                e.municipio,
                e.uf,
                l.medido_em,
                ST_Y(e.geom)      AS latitude,
                ST_X(e.geom)      AS longitude,
                e.geom,
                l.temperatura,
                l.umidade,
                l.precipitacao,
                l.velocidade_vento,
                l.pressao,
                CASE
                    WHEN l.precipitacao IS NULL THEN 'desconhecido'
                    WHEN l.precipitacao =  0    THEN 'sem_chuva'
                    WHEN l.precipitacao <  5    THEN 'leve'
                    WHEN l.precipitacao < 25    THEN 'moderada'
                    WHEN l.precipitacao < 50    THEN 'forte'
                    ELSE 'muito_forte'
                END AS classe_precipitacao
            FROM silver.leituras_inmet l
            JOIN estacoes_meteorologicas e ON e.codigo = l.codigo_estacao
            WHERE e.geom IS NOT NULL
            ORDER BY l.codigo_estacao, l.medido_em DESC
        SQL);

        // Indice UNICO e obrigatorio para REFRESH ... CONCURRENTLY, e o
        // CONCURRENTLY e o que evita travar a leitura do mapa no refresh.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_mapa_id ON gold.inmet_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_inmet_mapa_geom ON gold.inmet_mapa USING GIST (geom)');

        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_estatisticas AS
            SELECT
                1                                                  AS id,
                count(*)                                           AS total_estacoes,
                round(avg(precipitacao), 2)                        AS precipitacao_media,
                max(precipitacao)                                  AS precipitacao_maxima,
                count(*) FILTER (WHERE precipitacao > 0)           AS estacoes_com_chuva,
                round(avg(temperatura), 2)                         AS temperatura_media,
                now()                                              AS ultima_atualizacao
            FROM gold.inmet_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_estatisticas ON gold.inmet_estatisticas (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.inmet_estatisticas');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.inmet_mapa');
    }
};
```

- [ ] **Step 4: Criar o job de refresh**

`SDC/app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldInmetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('gold-inmet'))->expireAfter(600)];
    }

    public function handle(): void
    {
        // CONCURRENTLY exige o indice unico da migration e nao roda dentro de
        // transacao — este job nao pode ser chamado de dentro de DB::transaction().
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.inmet_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.inmet_estatisticas');
    }
}
```

- [ ] **Step 5: Adicionar a leitura do Gold ao repositorio**

Acrescentar a `InmetRepository`, antes de `upsertLote`:

```php
    /**
     * Le a camada Gold para o mapa. Nenhuma agregacao aqui: a matview ja
     * entrega lat/lon extraidos e a classe de precipitacao calculada.
     */
    public function mapa(): \Illuminate\Support\Collection
    {
        return DB::table('gold.inmet_mapa')
            ->select([
                'id', 'codigo_estacao', 'nome_estacao', 'municipio', 'uf',
                'medido_em', 'latitude', 'longitude', 'temperatura', 'umidade',
                'precipitacao', 'velocidade_vento', 'pressao', 'classe_precipitacao',
            ])
            ->orderByDesc('precipitacao')
            ->get();
    }

    /**
     * @return array{total_estacoes: int, precipitacao_media: float, precipitacao_maxima: float, estacoes_com_chuva: int, temperatura_media: float, ultima_atualizacao: ?string}
     */
    public function estatisticas(): array
    {
        $linha = DB::table('gold.inmet_estatisticas')->first();

        return [
            'total_estacoes' => (int) ($linha->total_estacoes ?? 0),
            'precipitacao_media' => (float) ($linha->precipitacao_media ?? 0),
            'precipitacao_maxima' => (float) ($linha->precipitacao_maxima ?? 0),
            'estacoes_com_chuva' => (int) ($linha->estacoes_com_chuva ?? 0),
            'temperatura_media' => (float) ($linha->temperatura_media ?? 0),
            'ultima_atualizacao' => $linha->ultima_atualizacao ?? null,
        ];
    }
```

- [ ] **Step 6: Registrar o job no `refresh_gold`**

Em `SDC/config/medalhao.php`, no array `refresh_gold` criado na Task 1:

```php
        'inmet' => \App\Modules\Inmet\Jobs\AtualizarGoldInmetJob::class,
```

- [ ] **Step 7: Rodar a migration e os testes**

```bash
art php artisan migrate --force
art php vendor/bin/phpunit --filter=GoldInmetTest
```

Expected: PASS, 3 testes.

- [ ] **Step 8: Commit**

```bash
git add SDC/database/migrations/2026_09_01_000003_create_gold_inmet_views.php \
        SDC/app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php \
        SDC/app/Modules/Inmet/Repositories/InmetRepository.php \
        SDC/config/medalhao.php
git commit -m "🗃️ db(inmet): matviews gold do mapa e estatisticas"
```

---

### Task 5: Corrigir o cliente da API

**Files:**
- Modify: `SDC/app/Modules/Inmet/Services/InmetApiClient.php`
- Modify: `SDC/config/medalhao.php`
- Modify: `SDC/.env.example`
- Test: `SDC/tests/Unit/Inmet/InmetApiClientTest.php`

**Interfaces:**
- Produces: `InmetApiClient::inventario(): array` e `InmetApiClient::leiturasDaEstacao(string $codigo, string $dia): array`, mais `InmetApiClient::leiturasEmLote(array $codigos, string $dia): array{leituras: array, falhas: array}`. Consumido pela Task 6.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Inmet;

use App\Modules\Inmet\Services\InmetApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class InmetApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('medalhao.inmet.token', 'TOKEN-DE-TESTE');
        config()->set('medalhao.inmet.inventario_url', 'https://apitempo.inmet.gov.br/estacoes/T');
        config()->set('medalhao.inmet.leituras_url', 'https://apitempo.inmet.gov.br/token/estacao');
    }

    public function test_envia_user_agent_de_navegador(): void
    {
        // Sem User-Agent o servidor do INMET corta a conexao, mesmo com TLS
        // negociado. Http::get() do Laravel nao envia nenhum por padrao.
        Http::fake(['*' => Http::response([], 200)]);

        app(InmetApiClient::class)->inventario();

        Http::assertSent(function ($request) {
            $ua = $request->header('User-Agent')[0] ?? '';

            return str_contains($ua, 'Mozilla/5.0');
        });
    }

    public function test_url_de_leitura_poe_o_codigo_da_estacao_antes_do_token(): void
    {
        // A rota do INMET e /token/estacao/{inicio}/{fim}/{codigo}/{token}.
        // Omitir o codigo devolve 404 E_ROUTE_NOT_FOUND -- o defeito que deixou
        // o modulo sem dado desde sempre.
        Http::fake(['*' => Http::response([], 200)]);

        app(InmetApiClient::class)->leiturasDaEstacao('A521', '2026-09-01');

        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/token/estacao/2026-09-01/2026-09-01/A521/TOKEN-DE-TESTE'
        ));
    }

    public function test_lote_separa_o_que_veio_do_que_falhou(): void
    {
        Http::fake([
            '*/A521/*' => Http::response([['CD_ESTACAO' => 'A521']], 200),
            '*/A999/*' => Http::response('', 500),
        ]);

        $r = app(InmetApiClient::class)->leiturasEmLote(['A521', 'A999'], '2026-09-01');

        $this->assertCount(1, $r['leituras']);
        $this->assertSame(['A999'], array_keys($r['falhas']));
    }

    public function test_o_token_nao_esta_hardcoded_no_fonte(): void
    {
        $fonte = file_get_contents(app_path('Modules/Inmet/Services/InmetApiClient.php'));

        // Nao escrever o token literal aqui: este arquivo e versionado, e
        // repetir a credencial para provar que ela saiu seria autodestrutivo.
        $this->assertStringNotContainsString('API_TOKEN', $fonte);
        $this->assertDoesNotMatchRegularExpression('/const\s+\w*TOKEN\w*\s*=\s*[\'"]/', $fonte);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=InmetApiClientTest`
Expected: FAIL — os quatro testes falham: nao ha User-Agent, nao existe `leiturasDaEstacao`, e `API_TOKEN` esta no fonte.

- [ ] **Step 3: Adicionar o bloco `inmet` ao config**

Em `SDC/config/medalhao.php`, ao lado do bloco `sismos`:

```php
    'inmet' => [
        // Token da API do INMET. Estava hardcoded em InmetApiClient; sai para
        // env porque e credencial, e porque expira sem aviso.
        'token' => env('MEDALHAO_INMET_TOKEN'),

        'inventario_url' => env('MEDALHAO_INMET_INVENTARIO_URL', 'https://apitempo.inmet.gov.br/estacoes/T'),
        'leituras_url' => env('MEDALHAO_INMET_LEITURAS_URL', 'https://apitempo.inmet.gov.br/token/estacao'),

        // Recorte: o inventario traz SG_ESTADO confiavel, entao filtra por UF em
        // vez de bbox. A bbox abaixo so enquadra o mapa na entrega.
        'uf' => env('MEDALHAO_INMET_UF', 'MG'),
        'somente_operantes' => (bool) env('MEDALHAO_INMET_SOMENTE_OPERANTES', true),

        'bbox' => [
            'min_lat' => -22.9,
            'max_lat' => -14.23,
            'min_lon' => -51.04,
            'max_lon' => -39.85,
        ],

        // Requisicoes simultaneas no Http::pool. Medido: 12 concorrentes em
        // menos de 1s, entao 68 estacoes cabem folgado nos 300s do worker.
        'concorrencia' => (int) env('MEDALHAO_INMET_CONCORRENCIA', 20),
    ],
```

- [ ] **Step 4: Adicionar a variavel ao `.env.example`**

```
MEDALHAO_INMET_TOKEN=
```

- [ ] **Step 5: Reescrever o cliente**

Substituir todo o conteudo de `SDC/app/Modules/Inmet/Services/InmetApiClient.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente da API do INMET (apitempo.inmet.gov.br).
 *
 * Duas particularidades da fonte, medidas em 2026-09-01, que a versao anterior
 * desta classe nao atendia e que a deixavam sem devolver dado nenhum:
 *
 *   1. User-Agent de navegador e OBRIGATORIO. Sem ele o servidor completa o
 *      handshake TLS e corta a conexao na leitura da resposta. Http::get() do
 *      Laravel nao envia User-Agent por padrao.
 *   2. A rota de leituras exige o codigo da estacao ANTES do token:
 *      /token/estacao/{inicio}/{fim}/{codigo}/{token}. Sem o codigo a API
 *      responde 404 E_ROUTE_NOT_FOUND.
 *
 * Nao ha endpoint de todas as estacoes: leitura e uma chamada por estacao.
 */
class InmetApiClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /** @return array<int, array<string, mixed>> */
    public function inventario(): array
    {
        $url = (string) config('medalhao.inmet.inventario_url');

        $resposta = $this->requisicao()->get($url);

        if ($resposta->failed()) {
            throw new RuntimeException("Falha no inventario do INMET: HTTP {$resposta->status()}");
        }

        return is_array($resposta->json()) ? $resposta->json() : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function leiturasDaEstacao(string $codigo, string $dia): array
    {
        $resposta = $this->requisicao()->get($this->urlLeituras($codigo, $dia));

        if ($resposta->failed()) {
            throw new RuntimeException("Falha nas leituras de {$codigo}: HTTP {$resposta->status()}");
        }

        return is_array($resposta->json()) ? $resposta->json() : [];
    }

    /**
     * Busca varias estacoes concorrentemente. Falha de estacao nao aborta o
     * lote: Bronze e historico bruto, e um ciclo parcial serve.
     *
     * @param list<string> $codigos
     * @return array{leituras: array<int, array<string, mixed>>, falhas: array<string, string>}
     */
    public function leiturasEmLote(array $codigos, string $dia): array
    {
        $leituras = [];
        $falhas = [];
        $tamanho = max(1, (int) config('medalhao.inmet.concorrencia', 20));

        foreach (array_chunk($codigos, $tamanho) as $fatia) {
            $respostas = Http::pool(fn (Pool $pool) => array_map(
                fn (string $codigo) => $pool->as($codigo)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->timeout(60)
                    ->get($this->urlLeituras($codigo, $dia)),
                $fatia
            ));

            foreach ($fatia as $codigo) {
                $resposta = $respostas[$codigo] ?? null;

                if ($resposta === null || $resposta instanceof \Throwable) {
                    $falhas[$codigo] = $resposta instanceof \Throwable
                        ? $resposta->getMessage()
                        : 'sem resposta';
                    continue;
                }

                if ($resposta->failed()) {
                    $falhas[$codigo] = "HTTP {$resposta->status()}";
                    continue;
                }

                $corpo = $resposta->json();

                if (! is_array($corpo)) {
                    $falhas[$codigo] = 'corpo nao e json';
                    continue;
                }

                foreach ($corpo as $linha) {
                    $leituras[] = $linha;
                }
            }
        }

        return ['leituras' => $leituras, 'falhas' => $falhas];
    }

    private function urlLeituras(string $codigo, string $dia): string
    {
        $base = rtrim((string) config('medalhao.inmet.leituras_url'), '/');
        $token = (string) config('medalhao.inmet.token');

        return "{$base}/{$dia}/{$dia}/{$codigo}/{$token}";
    }

    private function requisicao(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(60)
            ->retry(3, 500, throw: false);
    }
}
```

- [ ] **Step 6: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter=InmetApiClientTest`
Expected: PASS, 4 testes.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Inmet/Services/InmetApiClient.php SDC/config/medalhao.php SDC/.env.example
git commit -m "🐛 fix(inmet): URL da API estava errada e faltava User-Agent"
```

---

### Task 6: Ingestor com coleta concorrente

**Files:**
- Create: `SDC/app/Modules/Inmet/Ingestores/InmetApiIngestor.php`
- Test: `SDC/tests/Unit/Inmet/InmetApiIngestorTest.php`
- Test fixture: `SDC/tests/Fixtures/Inmet/inventario-mg.json`
- Test fixture: `SDC/tests/Fixtures/Inmet/leituras-a521.json`

**Interfaces:**
- Consumes: `InmetApiClient` (Task 5).
- Produces: `InmetApiIngestor` implementando `FonteIngestor` com `chave() = 'inmet-api'`, `grupo() = 'inmet'`, `formato() = 'inmet-json'`. O `PayloadBruto::$conteudo` e um JSON `{"dia": "...", "estacoes": [...], "leituras": [...]}`. Consumido pela Task 7.

- [ ] **Step 1: Gravar as fixtures reais**

```bash
mkdir -p SDC/tests/Fixtures/Inmet
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
TOKEN="<o token que estava hardcoded, ou o de env>"
HOJE=$(date +%Y-%m-%d)

curl -s -A "$UA" "https://apitempo.inmet.gov.br/estacoes/T" \
  | python -c "import json,sys; d=json.load(sys.stdin); mg=[r for r in d if r.get('SG_ESTADO')=='MG']; json.dump(mg[:5], open('SDC/tests/Fixtures/Inmet/inventario-mg.json','w'), indent=2)"

curl -s -A "$UA" -o SDC/tests/Fixtures/Inmet/leituras-a521.json \
  "https://apitempo.inmet.gov.br/token/estacao/${HOJE}/${HOJE}/A521/${TOKEN}"
```

Confira que `inventario-mg.json` tem 5 registros com `SG_ESTADO: "MG"` e que `leituras-a521.json` tem 24 entradas com `HR_MEDICAO` de `"0000"` a `"2300"`.

- [ ] **Step 2: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Inmet;

use App\Modules\Inmet\Ingestores\InmetApiIngestor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class InmetApiIngestorTest extends TestCase
{
    private function fixture(string $nome): string
    {
        return file_get_contents(base_path("tests/Fixtures/Inmet/{$nome}"));
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('medalhao.inmet.token', 'TOKEN-DE-TESTE');
        config()->set('medalhao.inmet.uf', 'MG');
        config()->set('medalhao.inmet.somente_operantes', true);
        config()->set('medalhao.inmet.concorrencia', 5);
    }

    public function test_identidade_da_fonte(): void
    {
        $i = app(InmetApiIngestor::class);

        $this->assertSame('inmet-api', $i->chave());
        $this->assertSame('inmet', $i->grupo());
        $this->assertSame('inmet-json', $i->formato());
    }

    public function test_consolida_inventario_e_leituras_num_payload_unico(): void
    {
        Http::fake([
            '*/estacoes/T' => Http::response(json_decode($this->fixture('inventario-mg.json'), true), 200),
            '*/token/estacao/*' => Http::response(json_decode($this->fixture('leituras-a521.json'), true), 200),
        ]);

        $bruto = app(InmetApiIngestor::class)->coletar();

        $this->assertSame('inmet-json', $bruto->formato);

        $dados = json_decode($bruto->conteudo, true);

        $this->assertArrayHasKey('estacoes', $dados);
        $this->assertArrayHasKey('leituras', $dados);
        $this->assertNotEmpty($dados['estacoes']);
        $this->assertNotEmpty($dados['leituras']);
    }

    public function test_meta_registra_quantas_estacoes_responderam(): void
    {
        Http::fake([
            '*/estacoes/T' => Http::response(json_decode($this->fixture('inventario-mg.json'), true), 200),
            '*/token/estacao/*' => Http::response(json_decode($this->fixture('leituras-a521.json'), true), 200),
        ]);

        $bruto = app(InmetApiIngestor::class)->coletar();

        $this->assertArrayHasKey('estacoes_no_inventario', $bruto->meta);
        $this->assertArrayHasKey('falhas', $bruto->meta);
        $this->assertArrayHasKey('duracao_ms', $bruto->meta);
    }

    public function test_falha_de_estacao_nao_aborta_a_coleta(): void
    {
        // Bronze e historico bruto: ciclo parcial serve, e o proximo recoleta.
        Http::fake([
            '*/estacoes/T' => Http::response(json_decode($this->fixture('inventario-mg.json'), true), 200),
            '*/token/estacao/*' => Http::response('', 500),
        ]);

        $bruto = app(InmetApiIngestor::class)->coletar();
        $dados = json_decode($bruto->conteudo, true);

        $this->assertSame([], $dados['leituras']);
        $this->assertNotEmpty($bruto->meta['falhas']);
        $this->assertNotEmpty($dados['estacoes']);
    }
}
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=InmetApiIngestorTest`
Expected: FAIL — `Class "App\Modules\Inmet\Ingestores\InmetApiIngestor" not found`.

- [ ] **Step 4: Criar o ingestor**

`SDC/app/Modules/Inmet/Ingestores/InmetApiIngestor.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Ingestores;

use App\Modules\Inmet\Services\InmetApiClient;
use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Carbon;

/**
 * Coleta o INMET: um inventario de estacoes e uma leitura por estacao.
 *
 * O contrato devolve UM PayloadBruto, entao as 68 chamadas de MG sao feitas
 * concorrentemente e consolidadas num JSON so. Medido em 2026-09-01: 12
 * chamadas concorrentes em menos de 1s, o que deixa as 68 muito abaixo dos 300s
 * de timeout do worker da fila medalhao.
 */
final class InmetApiIngestor implements FonteIngestor
{
    public function __construct(
        private readonly InmetApiClient $cliente,
    ) {
    }

    public function chave(): string
    {
        return 'inmet-api';
    }

    public function grupo(): string
    {
        return 'inmet';
    }

    public function formato(): string
    {
        return 'inmet-json';
    }

    public function coletar(): PayloadBruto
    {
        $inicio = microtime(true);

        $uf = (string) config('medalhao.inmet.uf', 'MG');
        $somenteOperantes = (bool) config('medalhao.inmet.somente_operantes', true);
        $dia = Carbon::now('America/Sao_Paulo')->format('Y-m-d');

        $estacoes = array_values(array_filter(
            $this->cliente->inventario(),
            static function (array $e) use ($uf, $somenteOperantes): bool {
                if (($e['SG_ESTADO'] ?? $e['UF'] ?? '') !== $uf) {
                    return false;
                }

                // O recorte e por UF, nao por bbox: o inventario do INMET traz
                // SG_ESTADO confiavel, o que e mais preciso e mais barato.
                return ! $somenteOperantes || ($e['CD_SITUACAO'] ?? '') === 'Operante';
            }
        ));

        $codigos = array_values(array_filter(array_map(
            static fn (array $e): string => (string) ($e['CD_ESTACAO'] ?? ''),
            $estacoes
        )));

        $resultado = $this->cliente->leiturasEmLote($codigos, $dia);

        $conteudo = json_encode([
            'dia' => $dia,
            'estacoes' => $estacoes,
            'leituras' => $resultado['leituras'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new PayloadBruto($conteudo, $this->formato(), [
            'dia' => $dia,
            'uf' => $uf,
            'estacoes_no_inventario' => count($estacoes),
            'estacoes_com_resposta' => count($codigos) - count($resultado['falhas']),
            'leituras' => count($resultado['leituras']),
            'falhas' => $resultado['falhas'],
            'duracao_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ]);
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter=InmetApiIngestorTest`
Expected: PASS, 4 testes.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Inmet/Ingestores/InmetApiIngestor.php
git commit -m "✨ feat(inmet): ingestor com coleta concorrente das estacoes de MG"
```

---

### Task 7: Normalizador

**Files:**
- Create: `SDC/app/Modules/Inmet/Normalizadores/InmetJsonNormalizador.php`
- Test: `SDC/tests/Unit/Inmet/InmetJsonNormalizadorTest.php`

**Interfaces:**
- Consumes: `PayloadBruto` produzido pela Task 6; `EstacaoDTO` (Task 2); `LeituraMeteorologicaDTO` (Task 3).
- Produces: `InmetJsonNormalizador::normalizar(PayloadBruto): iterable` — Generator que emite `EstacaoDTO` primeiro e `LeituraMeteorologicaDTO` depois. A ordem importa: a dimensao precisa existir antes do fato, por causa do join da matview.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Inmet;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inmet\Normalizadores\InmetJsonNormalizador;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Tests\TestCase;

final class InmetJsonNormalizadorTest extends TestCase
{
    private function payload(array $sobrepor = []): PayloadBruto
    {
        $dados = array_merge([
            'dia' => '2026-09-01',
            'estacoes' => [[
                'CD_ESTACAO' => 'A521',
                'DC_NOME' => 'BELO HORIZONTE - PAMPULHA',
                'SG_ESTADO' => 'MG',
                'CD_SITUACAO' => 'Operante',
                'TP_ESTACAO' => 'Automatica',
                'VL_LATITUDE' => '-19.88388888',
                'VL_LONGITUDE' => '-43.96944443',
                'VL_ALTITUDE' => '850.0',
            ]],
            'leituras' => [
                // Hora ja medida.
                [
                    'CD_ESTACAO' => 'A521', 'DC_NOME' => 'BELO HORIZONTE - PAMPULHA',
                    'UF' => 'MG', 'DT_MEDICAO' => '2026-09-01', 'HR_MEDICAO' => '0000',
                    'CHUVA' => '0', 'TEM_INS' => '22.8', 'UMD_INS' => '62',
                    'VEN_VEL' => '2.4', 'PRE_INS' => '918.2',
                    'VL_LATITUDE' => '-19.88388888', 'VL_LONGITUDE' => '-43.96944443',
                ],
                // Hora futura: a API devolve as 24 horas do dia, com null nas
                // que ainda nao aconteceram.
                [
                    'CD_ESTACAO' => 'A521', 'DC_NOME' => 'BELO HORIZONTE - PAMPULHA',
                    'UF' => 'MG', 'DT_MEDICAO' => '2026-09-01', 'HR_MEDICAO' => '2300',
                    'CHUVA' => null, 'TEM_INS' => null, 'UMD_INS' => null,
                    'VEN_VEL' => null, 'PRE_INS' => null,
                    'VL_LATITUDE' => '-19.88388888', 'VL_LONGITUDE' => '-43.96944443',
                ],
            ],
        ], $sobrepor);

        return new PayloadBruto(json_encode($dados), 'inmet-json', []);
    }

    public function test_emite_a_estacao_antes_da_leitura(): void
    {
        // A dimensao precisa existir antes do fato: a matview do mapa faz join.
        $dtos = iterator_to_array(app(InmetJsonNormalizador::class)->normalizar($this->payload()), false);

        $this->assertInstanceOf(EstacaoDTO::class, $dtos[0]);
        $this->assertInstanceOf(LeituraMeteorologicaDTO::class, $dtos[1]);
    }

    public function test_descarta_hora_sem_medicao_nenhuma(): void
    {
        $dtos = iterator_to_array(app(InmetJsonNormalizador::class)->normalizar($this->payload()), false);

        $leituras = array_values(array_filter($dtos, fn ($d) => $d instanceof LeituraMeteorologicaDTO));

        $this->assertCount(1, $leituras);
        $this->assertSame('0000', $leituras[0]->dataHoraMedicao->format('Hi'));
    }

    public function test_converte_os_valores_string_da_api(): void
    {
        $dtos = iterator_to_array(app(InmetJsonNormalizador::class)->normalizar($this->payload()), false);
        $leitura = array_values(array_filter($dtos, fn ($d) => $d instanceof LeituraMeteorologicaDTO))[0];

        $this->assertSame(22.8, $leitura->temperatura);
        $this->assertSame(62.0, $leitura->umidade);
        $this->assertSame(0.0, $leitura->precipitacao);
        $this->assertSame(918.2, $leitura->pressao);
    }

    public function test_estacao_sem_coordenada_e_descartada(): void
    {
        $payload = $this->payload(['estacoes' => [[
            'CD_ESTACAO' => 'A999', 'DC_NOME' => 'SEM COORDENADA', 'SG_ESTADO' => 'MG',
            'VL_LATITUDE' => null, 'VL_LONGITUDE' => null,
        ]]]);

        $dtos = iterator_to_array(app(InmetJsonNormalizador::class)->normalizar($payload), false);

        $this->assertCount(0, array_filter($dtos, fn ($d) => $d instanceof EstacaoDTO));
    }

    public function test_payload_vazio_nao_estoura(): void
    {
        $vazio = new PayloadBruto('', 'inmet-json', []);

        $this->assertCount(0, iterator_to_array(app(InmetJsonNormalizador::class)->normalizar($vazio), false));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=InmetJsonNormalizadorTest`
Expected: FAIL — classe inexistente.

- [ ] **Step 3: Criar o normalizador**

`SDC/app/Modules/Inmet/Normalizadores/InmetJsonNormalizador.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Normalizadores;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Generator;

final class InmetJsonNormalizador implements NormalizadorSilver
{
    /** Campos que, todos nulos, indicam hora que ainda nao foi medida. */
    private const MEDICOES = ['CHUVA', 'TEM_INS', 'UMD_INS', 'VEN_VEL', 'PRE_INS'];

    public function normalizar(PayloadBruto $bruto): iterable
    {
        if (trim($bruto->conteudo) === '') {
            return;
        }

        $dados = json_decode($bruto->conteudo, true);

        if (! is_array($dados)) {
            return;
        }

        yield from $this->estacoes($dados['estacoes'] ?? []);
        yield from $this->leituras($dados['leituras'] ?? []);
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @return Generator<EstacaoDTO>
     */
    private function estacoes(array $registros): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro)) {
                continue;
            }

            $dto = EstacaoDTO::fromInventarioArray($registro);

            // null significa coordenada ausente ou zerada. Descartar e melhor
            // que plotar no Golfo da Guine.
            if ($dto !== null && $dto->codigo !== '') {
                yield $dto;
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @return Generator<LeituraMeteorologicaDTO>
     */
    private function leituras(array $registros): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro) || ! $this->temMedicao($registro)) {
                continue;
            }

            yield LeituraMeteorologicaDTO::fromInmetArray($registro);
        }
    }

    /**
     * A API devolve as 24 horas do dia, com todos os valores nulos nas horas
     * que ainda nao aconteceram. Sem esta guarda, o Silver enche de linha vazia.
     *
     * @param array<string, mixed> $registro
     */
    private function temMedicao(array $registro): bool
    {
        foreach (self::MEDICOES as $campo) {
            $valor = $registro[$campo] ?? null;

            if ($valor !== null && $valor !== '') {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter=InmetJsonNormalizadorTest`
Expected: PASS, 5 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/Inmet/Normalizadores/InmetJsonNormalizador.php
git commit -m "✨ feat(inmet): normalizador descartando hora sem medicao"
```

---

### Task 8: Registro da fonte, persistidor e agendamento

**Files:**
- Modify: `SDC/app/Modules/Inmet/InmetServiceProvider.php`
- Modify: `SDC/config/medalhao.php`
- Modify: `SDC/routes/console.php`
- Test: `SDC/tests/Feature/Inmet/PipelineInmetTest.php`

**Interfaces:**
- Consumes: tudo das Tasks 2 a 7.
- Produces: fonte `inmet-api` registrada no `IngestorRegistry`; grupo `inmet` resolvivel por `medalhao:ingerir inmet`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inmet;

use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PipelineInmetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Pipeline exige PostgreSQL.');
        }

        DB::statement('TRUNCATE silver.leituras_inmet');
        DB::statement('TRUNCATE bronze.ingestao_bruta CASCADE');
        DB::statement('DELETE FROM estacoes_meteorologicas WHERE codigo LIKE ?', ['A%']);

        config()->set('medalhao.inmet.token', 'TOKEN-DE-TESTE');
    }

    public function test_a_fonte_esta_registrada_no_grupo_inmet(): void
    {
        $chaves = app(IngestorRegistry::class)->chavesDoGrupo('inmet');

        $this->assertContains('inmet-api', $chaves);
    }

    public function test_o_persistidor_do_grupo_esta_configurado(): void
    {
        $this->assertSame(
            \App\Modules\Inmet\Repositories\InmetRepository::class,
            config('medalhao.persistidores.inmet')
        );
    }

    public function test_ciclo_completo_leva_o_dado_ate_o_silver(): void
    {
        Http::fake([
            '*/estacoes/T' => Http::response(
                json_decode(file_get_contents(base_path('tests/Fixtures/Inmet/inventario-mg.json')), true),
                200
            ),
            '*/token/estacao/*' => Http::response(
                json_decode(file_get_contents(base_path('tests/Fixtures/Inmet/leituras-a521.json')), true),
                200
            ),
        ]);

        $this->artisan('medalhao:ingerir inmet')->assertSuccessful();

        $this->assertGreaterThan(0, IngestaoBruta::where('fonte', 'inmet-api')->count());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=PipelineInmetTest`
Expected: FAIL — a fonte nao esta registrada e nao ha persistidor para o grupo.

- [ ] **Step 3: Registrar a fonte no provider**

Em `SDC/app/Modules/Inmet/InmetServiceProvider.php`, acrescentar o `boot`
(ou o corpo, se ele ja existir):

```php
    public function register(): void
    {
        $this->app->singleton(\App\Modules\Inmet\Repositories\InmetRepository::class);
    }

    public function boot(): void
    {
        // O dominio registra a propria fonte; o kernel do medalhao nao a conhece.
        $registry = $this->app->make(\App\Modules\Medalhao\Registry\IngestorRegistry::class);

        $registry->registrar(
            $this->app->make(\App\Modules\Inmet\Ingestores\InmetApiIngestor::class),
            $this->app->make(\App\Modules\Inmet\Normalizadores\InmetJsonNormalizador::class),
        );
    }
```

Se o provider ja tiver `register`/`boot`, some o conteudo em vez de substituir. Nao remover binding existente.

- [ ] **Step 4: Configurar o persistidor do grupo**

Em `SDC/config/medalhao.php`, no array `persistidores`:

```php
        'inmet' => \App\Modules\Inmet\Repositories\InmetRepository::class,
```

- [ ] **Step 5: Agendar a coleta**

Em `SDC/routes/console.php`, ao lado do agendamento dos sismos:

```php
// Cadencia horaria, nao de 15 minutos como os sismos: a estacao automatica do
// INMET publica de hora em hora, entao coletar mais so multiplica I/O.
Schedule::command('medalhao:ingerir inmet')
    ->hourly()
    ->onOneServer()
    ->runInBackground();
```

- [ ] **Step 6: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter="PipelineInmetTest|Inmet|Medalhao|Sismos"`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Inmet/InmetServiceProvider.php SDC/config/medalhao.php SDC/routes/console.php
git commit -m "✨ feat(inmet): registro da fonte, persistidor e agendamento horario"
```

---

### Task 9: Entrega — controller lendo o Gold

**Files:**
- Modify: `SDC/app/Modules/Inmet/Controllers/InmetIndexController.php`
- Modify: `SDC/app/Modules/Inmet/Services/InmetService.php`
- Test: `SDC/tests/Feature/Inmet/InmetIndexControllerTest.php`

**Interfaces:**
- Consumes: `InmetRepository::mapa()` e `::estatisticas()` (Task 4).
- Produces: pagina Inertia `Inmet/MapaInmet` com props `estacoes`, `estatisticas`, `bbox`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inmet;

use App\Models\User;
use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inmet\Enums\NivelPrecipitacao;
use App\Modules\Inmet\Jobs\AtualizarGoldInmetJob;
use App\Modules\Inmet\Repositories\InmetRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class InmetIndexControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Gold exige PostgreSQL.');
        }

        DB::statement('TRUNCATE silver.leituras_inmet');
        DB::statement('DELETE FROM estacoes_meteorologicas WHERE codigo LIKE ?', ['TST%']);

        app(InmetRepository::class)->upsertLote([
            new EstacaoDTO('TST1', 'ESTACAO DE TESTE', 'MG', -19.88, -43.96, 850.0, 'Operante', 'Automatica'),
            new LeituraMeteorologicaDTO(
                codigoEstacao: 'TST1', nomeEstacao: 'ESTACAO DE TESTE', municipio: 'BH',
                dataHoraMedicao: Carbon::now('UTC'), temperatura: 22.0, umidade: 60.0,
                precipitacao: 12.0, velocidadeVento: 2.0, pressao: 918.0,
                nivelPrecipitacao: NivelPrecipitacao::fromMilimetros(12.0),
                condicao: 'rain', latitude: -19.88, longitude: -43.96,
            ),
        ]);

        (new AtualizarGoldInmetJob())->handle();
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/inmet')->assertRedirect();
    }

    public function test_renderiza_a_pagina_com_estacoes_e_estatisticas(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/inmet')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Inmet/MapaInmet')
                ->has('estacoes')
                ->has('estatisticas')
                ->has('bbox')
            );
    }

    public function test_a_pagina_nao_consulta_a_camada_silver(): void
    {
        $consultas = [];
        DB::listen(function ($q) use (&$consultas) {
            $consultas[] = $q->sql;
        });

        $this->actingAs(User::factory()->create())->get('/inmet')->assertOk();

        foreach ($consultas as $sql) {
            $this->assertStringNotContainsString('silver.', $sql);
        }
    }

    public function test_o_service_nao_agrega_mais_em_php(): void
    {
        $fonte = file_get_contents(app_path('Modules/Inmet/Services/InmetService.php'));

        $this->assertStringNotContainsString('getEstatisticas', $fonte);
        $this->assertStringNotContainsString('findAllEstacoes', $fonte);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `art php vendor/bin/phpunit --filter=InmetIndexControllerTest`
Expected: FAIL — o controller ainda usa `InmetService` e a prop chama-se `leituras`.

- [ ] **Step 3: Reescrever o controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inmet\Repositories\InmetRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly InmetRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        // O parametro uf saiu: o recorte e do pipeline, nao da requisicao.
        return Inertia::render('Inmet/MapaInmet', [
            'estacoes' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            'bbox' => config('medalhao.inmet.bbox'),
        ]);
    }
}
```

- [ ] **Step 4: Limpar o `InmetService`**

Remover de `InmetService`: `getEstatisticas`, `findAllEstacoes`, `findEstacaoByCodigo`, `findEstacoesByUf`, `createEstacao`, `updateEstacao`, `deleteEstacao` — nenhum tem chamador. Se apos a remocao a classe ficar sem metodo algum, apague o arquivo e o `use` correspondente onde houver.

Verifique antes de remover:

```bash
grep -rn "getEstatisticas\|findAllEstacoes\|findEstacaoByCodigo\|findEstacoesByUf\|createEstacao\|updateEstacao\|deleteEstacao" SDC/app SDC/routes SDC/resources
```

Expected: nenhuma ocorrencia fora do proprio `InmetService`.

- [ ] **Step 5: Rodar e ver passar**

Run: `art php vendor/bin/phpunit --filter=InmetIndexControllerTest`
Expected: PASS, 4 testes.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Inmet/Controllers/InmetIndexController.php SDC/app/Modules/Inmet/Services/InmetService.php
git commit -m "♻️ refactor(inmet): entrega le apenas a camada gold"
```

---

### Task 10: Componente de mapa compartilhado

**Files:**
- Create: `SDC/resources/js/Components/Mapa/MapaLeaflet.vue`
- Modify: `SDC/resources/js/Pages/Sismos/MapaSismos.vue`
- Modify: `SDC/resources/js/Pages/Inmet/MapaInmet.vue`

**Interfaces:**
- Produces: componente `MapaLeaflet` com props `pontos` (array de `{ id, latitude, longitude, cor, raio, popup }`), `centro` (`[lat, lon]`), `zoom` (number), `bbox` (objeto opcional) e slot `legenda`.

- [ ] **Step 1: Criar o componente**

`SDC/resources/js/Components/Mapa/MapaLeaflet.vue`:

```vue
<template>
  <div class="relative">
    <div :id="idMapa" class="h-[520px] w-full rounded-lg z-0"></div>
    <div v-if="$slots.legenda" class="absolute bottom-3 right-3 z-10">
      <slot name="legenda" />
    </div>
  </div>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onBeforeUnmount, onMounted, watch } from 'vue';

const props = defineProps({
  pontos: { type: Array, default: () => [] },
  centro: { type: Array, default: () => [-18.5, -44.5] },
  zoom: { type: Number, default: 6 },
  bbox: { type: Object, default: null },
});

// Id proprio por instancia: duas paginas usavam o id fixo "map", o que quebra
// se as duas montarem na mesma arvore.
const idMapa = `mapa-leaflet-${Math.random().toString(36).slice(2, 9)}`;

let mapa = null;
let camada = null;

function desenhar() {
  if (!mapa) return;

  if (camada) {
    camada.clearLayers();
  } else {
    camada = L.layerGroup().addTo(mapa);
  }

  props.pontos.forEach((p) => {
    const lat = Number(p.latitude);
    const lon = Number(p.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

    const marcador = L.circleMarker([lat, lon], {
      radius: p.raio ?? 7,
      color: p.cor ?? '#2563eb',
      fillColor: p.cor ?? '#2563eb',
      fillOpacity: 0.75,
      weight: 1,
    });

    if (p.popup) marcador.bindPopup(p.popup);

    marcador.addTo(camada);
  });
}

onMounted(async () => {
  await nextTick();

  mapa = L.map(idMapa, { scrollWheelZoom: false }).setView(props.centro, props.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 18,
  }).addTo(mapa);

  if (props.bbox) {
    mapa.fitBounds([
      [props.bbox.min_lat, props.bbox.min_lon],
      [props.bbox.max_lat, props.bbox.max_lon],
    ]);
  }

  desenhar();
});

// A pagina do Inmet nao tinha este hook: a instancia do mapa ficava viva apos
// navegar para fora, segurando listeners e nos de DOM.
onBeforeUnmount(() => {
  if (mapa) {
    mapa.remove();
    mapa = null;
    camada = null;
  }
});

watch(() => props.pontos, desenhar, { deep: true });
</script>
```

- [ ] **Step 2: Conferir que o build passa**

Run: `cd SDC && npm run build`
Expected: build sem erro. Se `npm` nao estiver disponivel no host, rode dentro do container `newsdc_dev_app`.

- [ ] **Step 3: Trocar a pagina de sismos para o componente**

Em `MapaSismos.vue`: remover os imports de `leaflet` e `leaflet/dist/leaflet.css`, remover o `onMounted`/`onBeforeUnmount` que montam o mapa, remover a `div#map-sismos`, e usar:

```vue
    <MapaLeaflet :pontos="pontosDoMapa" :bbox="bbox">
      <template #legenda>
        <!-- Mover para ca, sem reescrever, o bloco de legenda que a pagina ja
             tem. Localize-o com:
             grep -n "legenda\|Magnitude\|classe_magnitude" SDC/resources/js/Pages/Sismos/MapaSismos.vue
             Se a pagina nao tiver legenda, omita este template inteiro: o
             componente so renderiza o container quando o slot existe. -->
      </template>
    </MapaLeaflet>
```

com o computed que traduz o evento para o formato do componente:

```js
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';

const CORES = {
  micro: '#94a3b8',
  leve: '#22c55e',
  moderado: '#f59e0b',
  forte: '#ef4444',
  desconhecido: '#64748b',
};

const pontosDoMapa = computed(() => props.eventos.map((e) => ({
  id: e.id,
  latitude: e.latitude,
  longitude: e.longitude,
  cor: CORES[e.classe_magnitude] ?? CORES.desconhecido,
  raio: e.magnitude ? Math.max(5, Math.min(16, e.magnitude * 3)) : 5,
  popup: `<strong>${e.regiao ?? 'Sem regiao'}</strong><br>Magnitude: ${e.magnitude ?? 'n/d'}`,
})));
```

- [ ] **Step 4: Trocar a pagina do Inmet para o componente**

Em `MapaInmet.vue`: mesma remocao de imports e de `onMounted`, e a prop passa a ser `estacoes` em vez de `leituras`:

```js
import MapaLeaflet from '@/Components/Mapa/MapaLeaflet.vue';

const CORES = {
  sem_chuva: '#94a3b8',
  leve: '#60a5fa',
  moderada: '#2563eb',
  forte: '#f59e0b',
  muito_forte: '#ef4444',
  desconhecido: '#64748b',
};

const pontosDoMapa = computed(() => props.estacoes.map((e) => ({
  id: e.id,
  latitude: e.latitude,
  longitude: e.longitude,
  cor: CORES[e.classe_precipitacao] ?? CORES.desconhecido,
  raio: 7,
  popup: `<strong>${e.nome_estacao}</strong><br>Chuva: ${e.precipitacao ?? 'n/d'} mm<br>Temp: ${e.temperatura ?? 'n/d'} C`,
})));
```

Ajustar tambem o `defineProps` para `estacoes`, `estatisticas` e `bbox`, e os cartoes de estatistica para as chaves novas (`total_estacoes`, `precipitacao_media`, `precipitacao_maxima`, `estacoes_com_chuva`, `temperatura_media`).

- [ ] **Step 5: Conferir que nenhuma pagina instancia Leaflet direto**

```bash
grep -rn "from 'leaflet'" SDC/resources/js/Pages/
```

Expected: nenhuma ocorrencia. Somente `Components/Mapa/MapaLeaflet.vue` importa Leaflet.

- [ ] **Step 6: Build e commit**

```bash
cd SDC && npm run build && cd ..
git add SDC/resources/js/Components/Mapa/MapaLeaflet.vue \
        SDC/resources/js/Pages/Sismos/MapaSismos.vue \
        SDC/resources/js/Pages/Inmet/MapaInmet.vue
git commit -m "♻️ refactor(mapa): Leaflet num componente unico para Inmet e Sismos"
```

---

### Task 11: Verificacao ponta a ponta

**Files:**
- Nenhum arquivo novo; validacao dos criterios da secao 6 do spec.

- [ ] **Step 1: Suite completa**

Run: `art php vendor/bin/phpunit`
Expected: nenhuma falha nova em Inmet, Medalhao ou Sismos. Sobre banco limpo, testes que exigem dado semeado falham por falta de seed — compare com o baseline antes de tratar como regressao.

- [ ] **Step 2: Migrations do zero**

```bash
art php artisan migrate:fresh --force
docker exec newsdc_dev_db psql -U sdc -d sdc_medalhao \
  -c "SELECT matviewname FROM pg_matviews WHERE schemaname='gold' ORDER BY 1;" \
  -c "SELECT table_schema||'.'||table_name FROM information_schema.tables WHERE table_schema='silver' ORDER BY 1;"
```

Expected: `gold.inmet_estatisticas`, `gold.inmet_mapa`, `gold.sismos_estatisticas`, `gold.sismos_mapa`; `silver.leituras_inmet` e `silver.sismos`.

- [ ] **Step 3: Ciclo real de ingestao**

```bash
# A fila do Redis sobrevive entre execucoes e pode replayar job antigo,
# contaminando a contagem. Limpe antes de medir.
docker exec newsdc_dev_redis redis-cli --scan --pattern "*medalhao*" \
  | xargs -r docker exec newsdc_dev_redis redis-cli DEL

MEDALHAO_INMET_TOKEN="<token>" art php artisan medalhao:ingerir inmet
art php artisan queue:work --queue=medalhao --stop-when-empty

docker exec newsdc_dev_db psql -U sdc -d sdc_medalhao -c "
  SELECT 'bronze' camada, count(*) FROM bronze.ingestao_bruta WHERE fonte='inmet-api'
  UNION ALL SELECT 'dimensao', count(*) FROM estacoes_meteorologicas
  UNION ALL SELECT 'silver',   count(*) FROM silver.leituras_inmet
  UNION ALL SELECT 'gold',     count(*) FROM gold.inmet_mapa;"
```

Expected: bronze 1; dimensao ~61; silver bem maior que zero; gold igual ao numero de estacoes com leitura.

- [ ] **Step 4: Idempotencia**

Rode o Step 3 de novo, sem limpar o Redis.
Expected: a contagem de `silver` **nao** aumenta em multiplo (o upsert por `(codigo_estacao, medido_em)` reaproveita as linhas do mesmo dia); `dimensao` fica igual.

- [ ] **Step 5: Geometria confere com a origem**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc_medalhao -c "
  SELECT codigo, ST_Y(geom) lat, ST_X(geom) lon FROM estacoes_meteorologicas WHERE codigo='A521';"
```

Expected: `lat` ~ -19.88388888 e `lon` ~ -43.96944443, batendo com `VL_LATITUDE`/`VL_LONGITUDE` do inventario.

- [ ] **Step 6: Nenhuma estacao no Golfo da Guine**

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc_medalhao -c "
  SELECT count(*) FROM estacoes_meteorologicas WHERE ST_Y(geom)=0 OR ST_X(geom)=0 OR geom IS NULL;"
```

Expected: 0.

- [ ] **Step 7: Isolamento da fila e ausencia de dominio no kernel**

```bash
grep -rn "queue:work" SDC/docker/ | grep -c medalhao
grep -n "'sismos'\|'inmet'" SDC/app/Modules/Medalhao/Jobs/NormalizarSilverJob.php
```

Expected: `medalhao` segue somente nos tres processos dedicados; a segunda busca nao retorna nada.

- [ ] **Step 8: O token nao esta versionado**

```bash
# Le o token do .env (nao versionado) e procura por ele no que E versionado.
git grep -n -F "$(grep '^MEDALHAO_INMET_TOKEN=' SDC/.env | cut -d= -f2-)" -- . \
  || echo "ok: token ausente do versionado"
```

Expected: `ok: token ausente do versionado`.

- [ ] **Step 9: Conferencia contra os criterios do spec**

Percorra os dez criterios da secao 6 de
`SDC/docs/superpowers/specs/2026-09-01-inmet-medalhao-design.md` e marque cada um.
Qualquer um que nao passe vira correcao antes de considerar a fase concluida.

- [ ] **Step 10: Commit final**

```bash
git add SDC/docs/superpowers/plans/2026-09-01-inmet-medalhao.md
git commit -m "✅ test(inmet): verificacao ponta a ponta da fase 3"
```

---

## Notas de execucao

**Branch.** Crie uma branch propria antes da Task 1:

```bash
git switch -c feat/inmet-medalhao
```

**Ordem.** A Task 1 e independente e pode ir primeiro ou por ultimo, mas a Task 4
depende dela por causa do `refresh_gold`. As Tasks 2 e 5 sao independentes entre
si. A 3 depende da 2; a 4 depende da 3; a 6 depende da 5; a 7 depende de 2, 3 e 6;
a 8 depende de 4, 6 e 7; a 9 depende da 4; a 10 depende da 9 (prop `estacoes`).

**O token.** `MEDALHAO_INMET_TOKEN` precisa estar no `.env` local antes das Tasks 6,
8 e 11. Use o valor que estava hardcoded em `InmetApiClient` no historico do git.
Se ele tiver expirado, a coleta falha com HTTP 4xx registrado em log — nao
silenciosamente como antes.

**Fora de escopo desta fase, por decisao registrada no spec:** agregacoes de chuva
com janelas e QC (Fase 4), superficie interpolada (Fase 5), ingestao CEMADEN
SALVAR (Fase 2), pagina unica com camadas ligaveis, retencao de Bronze por fonte,
e estacoes convencionais (`/estacoes/M`).
