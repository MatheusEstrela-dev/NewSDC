# Medalhao + Fonte Sismica (Fase 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir o kernel de pipeline em medalhao (Bronze/Silver/Gold) no Laravel e prova-lo ponta a ponta com as fontes sismicas USP e UnB, sem sobrecarregar os workers existentes.

**Architecture:** Dois modulos novos. `Medalhao` e o kernel agnostico de dominio: contratos de ingestao, tabela Bronze generica, jobs em fila dedicada, arquivamento Parquet. `Sismos` e o primeiro consumidor: ingestores HTTP, normalizadores, tabela Silver com PostGIS, matviews Gold e pagina Leaflet. Camadas vivem em schemas Postgres (`bronze`, `silver`, `gold`).

**Tech Stack:** Laravel 11, PHP 8.3, PostgreSQL + PostGIS, Redis (filas), Vue 3 + Inertia + Leaflet 1.9, `flow-php/parquet`, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-07-medalhao-pipeline-sismos-design.md`

## Global Constraints

- Todo arquivo PHP comeca com `declare(strict_types=1);`.
- Classes de teste sao `final` e os metodos usam snake_case em pt-BR (`test_upsert_nao_duplica_evento`).
- **Sem emojis no codigo** (regra de ouro 2).
- **Sem acentos** em nomes de classe, metodo, coluna, chave de config e mensagem de log. Strings voltadas ao usuario final podem ter acento.
- Toda migration que toca schema, PostGIS ou matview comeca com a guarda de driver: `if (DB::getDriverName() !== 'pgsql') { return; }`.
- Nenhum calculo de agregacao em PHP na camada de entrega. O controller le apenas o schema `gold`.
- Nenhum job novo entra nas filas `critical,high,high-throughput,webhooks,default,low`. Tudo do pipeline vai para a fila `medalhao`.
- Commits seguem gitmoji: `<emoji> tipo(escopo): descricao em pt-BR`. Escopo `medalhao` ou `sismos`.
- **Nao incluir trailer `Co-Authored-By`** nos commits.
- Migrations novas seguem o padrao `2026_08_07_NNNNNN_descricao.php`.
- Testes que dependem de schema nomeado, PostGIS ou matview sao pgsql-only e devem pular fora do Postgres com `markTestSkipped`.

## Bounding box de MG (usada em varios pontos)

```
minlatitude  = -22.9
maxlatitude  = -14.23
minlongitude = -51.04
maxlongitude = -39.85
```

---

## Estrutura de arquivos

**Modulo `Medalhao` (kernel):**

| Arquivo | Responsabilidade |
| --- | --- |
| `Contracts/FonteIngestor.php` | Interface de coleta (Bronze) |
| `Contracts/NormalizadorSilver.php` | Interface de normalizacao (Bronze -> Silver) |
| `Contracts/ArquivadorBronze.php` | Interface de escrita Parquet |
| `DTOs/PayloadBruto.php` | DTO readonly do payload coletado |
| `Models/IngestaoBruta.php` | Model da tabela Bronze |
| `Registry/IngestorRegistry.php` | Mapeia chave -> (ingestor, normalizador) |
| `Jobs/IngerirFonteJob.php` | Coleta e grava Bronze |
| `Jobs/NormalizarSilverJob.php` | Bronze -> Silver |
| `Jobs/RolloverParquetJob.php` | Arquiva e poda o Bronze |
| `Infrastructure/FlowParquetArquivador.php` | Implementacao Parquet |
| `Console/IngerirCommand.php` | `medalhao:ingerir {grupo}` |
| `Console/RollupCommand.php` | `medalhao:rollup` |
| `MedalhaoServiceProvider.php` | Bindings e registry |

**Modulo `Sismos` (dominio):**

| Arquivo | Responsabilidade |
| --- | --- |
| `DTOs/SismoDTO.php` | Evento sismico normalizado |
| `Ingestores/UspFdsnIngestor.php` | HTTP contra FDSN da USP |
| `Ingestores/UnbObsisIngestor.php` | HTTP contra portal do UnB |
| `Normalizadores/FdsnTextNormalizador.php` | Texto `\|` -> SismoDTO |
| `Normalizadores/ObsisCsvNormalizador.php` | CSV -> SismoDTO |
| `Repositories/SismoRepository.php` | Upsert Silver, leitura Gold |
| `Jobs/AtualizarGoldSismosJob.php` | REFRESH das matviews |
| `Controllers/SismosIndexController.php` | Pagina do mapa |
| `SismosServiceProvider.php` | Registro das fontes |

---

### Task 1: Fundacao — schemas, tabela Bronze, config e disco

**Files:**
- Create: `SDC/database/migrations/2026_08_07_000001_create_medalhao_schemas.php`
- Create: `SDC/database/migrations/2026_08_07_000002_create_bronze_ingestao_bruta.php`
- Create: `SDC/config/medalhao.php`
- Modify: `SDC/config/filesystems.php` (adicionar disco `medalhao` no array `disks`)
- Test: `SDC/tests/Feature/Medalhao/FundacaoMedalhaoTest.php`

**Interfaces:**
- Consumes: nada
- Produces: schemas `bronze`/`silver`/`gold`; tabela `bronze.ingestao_bruta` com colunas `id, fonte, conteudo_bruto, formato, hash_conteudo, meta, coletado_em, processado_em`; config `medalhao.retencao_dias` (int, 30), `medalhao.sismos.janela_mapa_dias` (int, 90), `medalhao.sismos.bbox` (array); disco `medalhao`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FundacaoMedalhaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Schemas nomeados exigem PostgreSQL.');
        }
    }

    public function test_cria_os_tres_schemas(): void
    {
        $nomes = DB::table('information_schema.schemata')
            ->whereIn('schema_name', ['bronze', 'silver', 'gold'])
            ->pluck('schema_name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['bronze', 'gold', 'silver'], $nomes);
    }

    public function test_tabela_bronze_tem_as_colunas_esperadas(): void
    {
        $colunas = DB::table('information_schema.columns')
            ->where('table_schema', 'bronze')
            ->where('table_name', 'ingestao_bruta')
            ->pluck('column_name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'coletado_em', 'conteudo_bruto', 'fonte', 'formato',
            'hash_conteudo', 'id', 'meta', 'processado_em',
        ], $colunas);
    }

    public function test_config_tem_os_defaults(): void
    {
        $this->assertSame(30, config('medalhao.retencao_dias'));
        $this->assertSame(90, config('medalhao.sismos.janela_mapa_dias'));
        $this->assertSame(-22.9, config('medalhao.sismos.bbox.min_lat'));
        $this->assertSame(-14.23, config('medalhao.sismos.bbox.max_lat'));
        $this->assertSame(-51.04, config('medalhao.sismos.bbox.min_lon'));
        $this->assertSame(-39.85, config('medalhao.sismos.bbox.max_lon'));
    }

    public function test_disco_medalhao_esta_configurado(): void
    {
        $this->assertIsArray(config('filesystems.disks.medalhao'));
        $this->assertNotEmpty(config('filesystems.disks.medalhao.driver'));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=FundacaoMedalhaoTest`
Expected: FAIL — schemas inexistentes / `config('medalhao.retencao_dias')` nulo.

- [ ] **Step 3: Criar a migration de schemas**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SCHEMAS = ['bronze', 'silver', 'gold'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::SCHEMAS as $schema) {
            DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS %s', $schema));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse(self::SCHEMAS) as $schema) {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS %s CASCADE', $schema));
        }
    }
};
```

- [ ] **Step 4: Criar a migration da tabela Bronze**

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

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS bronze.ingestao_bruta (
                id             bigserial PRIMARY KEY,
                fonte          varchar(64) NOT NULL,
                conteudo_bruto text        NOT NULL,
                formato        varchar(32) NOT NULL,
                hash_conteudo  char(64)    NOT NULL,
                meta           jsonb       NOT NULL DEFAULT '{}'::jsonb,
                coletado_em    timestamptz NOT NULL DEFAULT now(),
                processado_em  timestamptz NULL
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_hash ON bronze.ingestao_bruta (fonte, hash_conteudo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_coletado ON bronze.ingestao_bruta (fonte, coletado_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_nao_processado ON bronze.ingestao_bruta (fonte) WHERE processado_em IS NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS bronze.ingestao_bruta');
    }
};
```

- [ ] **Step 5: Criar `config/medalhao.php`**

```php
<?php

declare(strict_types=1);

return [
    // Dias que o payload bruto permanece no Postgres antes de virar Parquet.
    'retencao_dias' => (int) env('MEDALHAO_RETENCAO_DIAS', 30),

    // Disco Flysystem onde o rollup Parquet e gravado.
    'disco' => env('MEDALHAO_DISCO', 'medalhao'),

    'sismos' => [
        // Janela exibida na matview do mapa.
        'janela_mapa_dias' => (int) env('MEDALHAO_SISMOS_JANELA_DIAS', 90),

        // Quadrante de Minas Gerais.
        'bbox' => [
            'min_lat' => -22.9,
            'max_lat' => -14.23,
            'min_lon' => -51.04,
            'max_lon' => -39.85,
        ],

        // Dias retroativos pedidos ao FDSN a cada coleta.
        'janela_coleta_dias' => (int) env('MEDALHAO_SISMOS_COLETA_DIAS', 7),

        'usp_fdsn_url' => env('MEDALHAO_USP_FDSN_URL', 'https://moho.iag.usp.br/fdsnws/event/1/query'),
        'unb_obsis_url' => env('MEDALHAO_UNB_OBSIS_URL', 'http://obsis.unb.br/portalsis/?pg=seism'),
    ],
];
```

- [ ] **Step 6: Adicionar o disco em `config/filesystems.php`**

Dentro do array `'disks' => [ ... ]`, ao lado dos demais discos de dominio, usando o helper `$azureOrLocal` que ja existe no topo do arquivo:

```php
        // Camada Bronze arquivada em Parquet (bind mount on-prem /data/anexos/MEDALHAO).
        'medalhao' => $azureOrLocal('medalhao', 'MEDALHAO', 'app/medalhao'),
```

- [ ] **Step 7: Rodar e ver passar**

Run: `php artisan test --filter=FundacaoMedalhaoTest`
Expected: PASS (4 testes).

- [ ] **Step 8: Commit**

```bash
git add SDC/database/migrations/2026_08_07_000001_create_medalhao_schemas.php \
        SDC/database/migrations/2026_08_07_000002_create_bronze_ingestao_bruta.php \
        SDC/config/medalhao.php SDC/config/filesystems.php \
        SDC/tests/Feature/Medalhao/FundacaoMedalhaoTest.php
git commit -m "🗃️ db(medalhao): schemas bronze/silver/gold e tabela de ingestao bruta"
```

---

### Task 2: Contratos, DTO e registry do kernel

**Files:**
- Create: `SDC/app/Modules/Medalhao/DTOs/PayloadBruto.php`
- Create: `SDC/app/Modules/Medalhao/Contracts/FonteIngestor.php`
- Create: `SDC/app/Modules/Medalhao/Contracts/NormalizadorSilver.php`
- Create: `SDC/app/Modules/Medalhao/Registry/IngestorRegistry.php`
- Create: `SDC/app/Modules/Medalhao/Models/IngestaoBruta.php`
- Create: `SDC/app/Modules/Medalhao/MedalhaoServiceProvider.php`
- Modify: `SDC/config/app.php` (registrar o provider junto de `InmetServiceProvider::class`, linha ~201)
- Test: `SDC/tests/Unit/Medalhao/IngestorRegistryTest.php`

**Interfaces:**
- Consumes: tabela `bronze.ingestao_bruta` (Task 1)
- Produces:
  - `PayloadBruto` readonly: `__construct(string $conteudo, string $formato, array $meta = [])`, metodo `hash(): string` (sha256 do conteudo).
  - `FonteIngestor`: `chave(): string`, `grupo(): string`, `formato(): string`, `coletar(): PayloadBruto`.
  - `NormalizadorSilver`: `normalizar(PayloadBruto $bruto): iterable`.
  - `IngestorRegistry`: `registrar(FonteIngestor $i, NormalizadorSilver $n): void`, `ingestor(string $chave): FonteIngestor`, `normalizador(string $chave): NormalizadorSilver`, `chavesDoGrupo(string $grupo): array`.
  - `IngestaoBruta` model: `$table = 'bronze.ingestao_bruta'`, cast `meta` => `array`, `coletado_em`/`processado_em` => `datetime`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Medalhao;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IngestorRegistryTest extends TestCase
{
    private function ingestorFake(string $chave, string $grupo): FonteIngestor
    {
        return new class($chave, $grupo) implements FonteIngestor {
            public function __construct(private string $chave, private string $grupo)
            {
            }

            public function chave(): string
            {
                return $this->chave;
            }

            public function grupo(): string
            {
                return $this->grupo;
            }

            public function formato(): string
            {
                return 'texto';
            }

            public function coletar(): PayloadBruto
            {
                return new PayloadBruto('conteudo', 'texto');
            }
        };
    }

    private function normalizadorFake(): NormalizadorSilver
    {
        return new class implements NormalizadorSilver {
            public function normalizar(PayloadBruto $bruto): iterable
            {
                return [];
            }
        };
    }

    public function test_payload_calcula_sha256_do_conteudo(): void
    {
        $payload = new PayloadBruto('abc', 'texto');

        $this->assertSame(hash('sha256', 'abc'), $payload->hash());
        $this->assertSame([], $payload->meta);
    }

    public function test_registra_e_recupera_por_chave(): void
    {
        $registry = new IngestorRegistry();
        $ingestor = $this->ingestorFake('usp-fdsn', 'sismos');

        $registry->registrar($ingestor, $this->normalizadorFake());

        $this->assertSame($ingestor, $registry->ingestor('usp-fdsn'));
        $this->assertInstanceOf(NormalizadorSilver::class, $registry->normalizador('usp-fdsn'));
    }

    public function test_lista_chaves_do_grupo(): void
    {
        $registry = new IngestorRegistry();
        $registry->registrar($this->ingestorFake('usp-fdsn', 'sismos'), $this->normalizadorFake());
        $registry->registrar($this->ingestorFake('unb-obsis', 'sismos'), $this->normalizadorFake());
        $registry->registrar($this->ingestorFake('inmet-api', 'clima'), $this->normalizadorFake());

        $this->assertSame(['usp-fdsn', 'unb-obsis'], $registry->chavesDoGrupo('sismos'));
        $this->assertSame(['inmet-api'], $registry->chavesDoGrupo('clima'));
    }

    public function test_chave_desconhecida_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new IngestorRegistry())->ingestor('inexistente');
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=IngestorRegistryTest`
Expected: FAIL — classes inexistentes.

- [ ] **Step 3: Criar o DTO e os contratos**

`DTOs/PayloadBruto.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\DTOs;

final readonly class PayloadBruto
{
    public function __construct(
        public string $conteudo,
        public string $formato,
        public array $meta = [],
    ) {
    }

    public function hash(): string
    {
        return hash('sha256', $this->conteudo);
    }
}
```

`Contracts/FonteIngestor.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use App\Modules\Medalhao\DTOs\PayloadBruto;

interface FonteIngestor
{
    /** Identificador unico da fonte, ex.: 'usp-fdsn'. */
    public function chave(): string;

    /** Grupo de agendamento, ex.: 'sismos'. */
    public function grupo(): string;

    /** Formato do conteudo bruto, ex.: 'fdsn-text'. */
    public function formato(): string;

    public function coletar(): PayloadBruto;
}
```

`Contracts/NormalizadorSilver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use App\Modules\Medalhao\DTOs\PayloadBruto;

interface NormalizadorSilver
{
    /**
     * Converte o payload bruto em DTOs de dominio, um por registro.
     * Implementacoes devem iterar sem materializar o conteudo inteiro.
     *
     * @return iterable<object>
     */
    public function normalizar(PayloadBruto $bruto): iterable;
}
```

- [ ] **Step 4: Criar o registry e o model**

`Registry/IngestorRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Registry;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use InvalidArgumentException;

final class IngestorRegistry
{
    /** @var array<string, FonteIngestor> */
    private array $ingestores = [];

    /** @var array<string, NormalizadorSilver> */
    private array $normalizadores = [];

    public function registrar(FonteIngestor $ingestor, NormalizadorSilver $normalizador): void
    {
        $this->ingestores[$ingestor->chave()] = $ingestor;
        $this->normalizadores[$ingestor->chave()] = $normalizador;
    }

    public function ingestor(string $chave): FonteIngestor
    {
        return $this->ingestores[$chave]
            ?? throw new InvalidArgumentException("Fonte nao registrada: {$chave}");
    }

    public function normalizador(string $chave): NormalizadorSilver
    {
        return $this->normalizadores[$chave]
            ?? throw new InvalidArgumentException("Normalizador nao registrado: {$chave}");
    }

    /** @return list<string> */
    public function chavesDoGrupo(string $grupo): array
    {
        $chaves = [];

        foreach ($this->ingestores as $chave => $ingestor) {
            if ($ingestor->grupo() === $grupo) {
                $chaves[] = $chave;
            }
        }

        return $chaves;
    }
}
```

`Models/IngestaoBruta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Models;

use Illuminate\Database\Eloquent\Model;

class IngestaoBruta extends Model
{
    protected $table = 'bronze.ingestao_bruta';

    public $timestamps = false;

    protected $fillable = [
        'fonte',
        'conteudo_bruto',
        'formato',
        'hash_conteudo',
        'meta',
        'coletado_em',
        'processado_em',
    ];

    protected $casts = [
        'meta' => 'array',
        'coletado_em' => 'datetime',
        'processado_em' => 'datetime',
    ];
}
```

- [ ] **Step 5: Criar o provider e registrar**

`MedalhaoServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao;

use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class MedalhaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IngestorRegistry::class);
    }

    public function boot(): void
    {
        // As fontes sao registradas pelos providers de cada modulo de dominio
        // (ex.: SismosServiceProvider), mantendo o kernel agnostico.
    }
}
```

Em `config/app.php`, na lista de providers, junto de `App\Modules\Inmet\InmetServiceProvider::class`:

```php
        App\Modules\Medalhao\MedalhaoServiceProvider::class,
```

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter=IngestorRegistryTest`
Expected: PASS (4 testes).

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Medalhao SDC/config/app.php SDC/tests/Unit/Medalhao/IngestorRegistryTest.php
git commit -m "✨ feat(medalhao): contratos de ingestao, registry e model da camada bronze"
```

---

### Task 3: Fixtures reais e o DTO de sismo

**Files:**
- Create: `SDC/tests/Fixtures/Sismos/usp-fdsn-mg.txt`
- Create: `SDC/tests/Fixtures/Sismos/unb-obsis.html`
- Create: `SDC/app/Modules/Sismos/DTOs/SismoDTO.php`
- Test: `SDC/tests/Unit/Sismos/SismoDTOTest.php`

**Interfaces:**
- Consumes: nada
- Produces: `SismoDTO` readonly com `fonte, evento_id, origem_utc (CarbonImmutable), latitude (float), longitude (float), profundidade_km (?float), magnitude (?float), escala_magnitude (?string), modo (?string), regiao (?string), tipo_evento (?string), autor (?string)`; metodo `dentroDaBbox(array $bbox): bool`.

- [ ] **Step 1: Gravar a fixture do FDSN**

Conteudo exato de `SDC/tests/Fixtures/Sismos/usp-fdsn-mg.txt` (capturado de `https://moho.iag.usp.br/fdsnws/event/1/query` em 2026-08-07):

```
#EventID|Time|Latitude|Longitude|Depth/km|Author|Catalog|Contributor|ContributorID|MagType|Magnitude|MagAuthor|EventLocationName|EventType
usp2026pdfj|2026-08-04T05:09:39.322741|-19.5194149017334|-44.065032958984375|0.0|JAlexandre||USP|usp2026pdfj|mR|1.8744161870778298|JAlexandre|Matozinhos/MG|earthquake
usp2026pcys|2026-08-04T01:47:46.097359|-19.393421173095703|-44.478763580322266|0.0|JAlexandre||USP|usp2026pcys|MLv|1.2854287469313053|JAlexandre|Matozinhos/MG|earthquake
usp2026owdm|2026-07-31T08:14:42.046118|-18.851715087890625|-44.75748062133789|0.0|JAlexandre||USP|usp2026owdm|mR|2.756085737389097|JAlexandre|Felixlandia/MG|earthquake
usp2026nrxj|2026-07-14T19:04:32.232921|-20.15009307861328|-44.87556838989258|0.0|JAlexandre||USP|usp2026nrxj|MLv|1.9433785234637542|JAlexandre|Divinopolis/MG|earthquake
```

- [ ] **Step 2: Gravar a fixture do obsis**

`SDC/tests/Fixtures/Sismos/unb-obsis.html` — HTML minimo que preserva as duas armadilhas reais (entidades `&#10;` como quebra de linha e `Local` generico):

```html
<html><body>
<form>
<textarea id="tabela" rows="20" cols="120">TABELA DE EVENTOS OBSERVATORIO SISMOLOGICO - SIS/UnB&#10;N, Data, Hora(UTC), Latitude, Longitude, Magnitude, Escala, Profundidade(km), Local, Tipo, IDSCP3, Revisor&#10;1, 31/07/2026, 08:14:42, -18.86904525756836, -44.758079528808594, 2.9, mR, 0.0, Brazil, earthquake, unb2026owdm, rbernardes&#10;2, 31/07/2026, 00:58:44, -8.392281532287598, -74.15995025634766, 5.7, Mwp, 155.9, Peru-Brazil Border Region, earthquake, unb2026ovpc, rbernardes&#10;3, 30/07/2026, 10:17:19, -11.4034423828125, -39.21552276611328, 2.1, mR, 0.0, Salvador, BA, earthquake, unb2026ouma, rbernardes</textarea>
</form>
</body></html>
```

A terceira linha usa `Salvador, BA` de proposito: o campo `Local` pode conter virgula, e o parser precisa lidar com isso (o script Python original fazia `",".join(p[8:-3])`).

- [ ] **Step 3: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Sismos;

use App\Modules\Sismos\DTOs\SismoDTO;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SismoDTOTest extends TestCase
{
    private const BBOX_MG = [
        'min_lat' => -22.9,
        'max_lat' => -14.23,
        'min_lon' => -51.04,
        'max_lon' => -39.85,
    ];

    private function dto(float $lat, float $lon): SismoDTO
    {
        return new SismoDTO(
            fonte: 'usp-fdsn',
            evento_id: 'usp2026owdm',
            origem_utc: CarbonImmutable::parse('2026-07-31T08:14:42Z'),
            latitude: $lat,
            longitude: $lon,
            profundidade_km: 0.0,
            magnitude: 2.75,
            escala_magnitude: 'mR',
            modo: null,
            regiao: 'Felixlandia/MG',
            tipo_evento: 'earthquake',
            autor: 'JAlexandre',
        );
    }

    public function test_evento_em_mg_esta_dentro_da_bbox(): void
    {
        $this->assertTrue($this->dto(-18.8517, -44.7575)->dentroDaBbox(self::BBOX_MG));
    }

    public function test_evento_no_peru_esta_fora_da_bbox(): void
    {
        $this->assertFalse($this->dto(-8.3922, -74.1599)->dentroDaBbox(self::BBOX_MG));
    }

    public function test_limite_da_bbox_e_inclusivo(): void
    {
        $this->assertTrue($this->dto(-22.9, -51.04)->dentroDaBbox(self::BBOX_MG));
        $this->assertTrue($this->dto(-14.23, -39.85)->dentroDaBbox(self::BBOX_MG));
    }

    public function test_evento_na_bahia_fica_fora_por_longitude(): void
    {
        $this->assertFalse($this->dto(-11.4034, -39.2155)->dentroDaBbox(self::BBOX_MG));
    }
}
```

- [ ] **Step 4: Rodar e ver falhar**

Run: `php artisan test --filter=SismoDTOTest`
Expected: FAIL — `SismoDTO` inexistente.

- [ ] **Step 5: Criar o DTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\DTOs;

use Carbon\CarbonImmutable;

final readonly class SismoDTO
{
    public function __construct(
        public string $fonte,
        public string $evento_id,
        public CarbonImmutable $origem_utc,
        public float $latitude,
        public float $longitude,
        public ?float $profundidade_km = null,
        public ?float $magnitude = null,
        public ?string $escala_magnitude = null,
        public ?string $modo = null,
        public ?string $regiao = null,
        public ?string $tipo_evento = null,
        public ?string $autor = null,
    ) {
    }

    /** @param array{min_lat: float, max_lat: float, min_lon: float, max_lon: float} $bbox */
    public function dentroDaBbox(array $bbox): bool
    {
        return $this->latitude >= $bbox['min_lat']
            && $this->latitude <= $bbox['max_lat']
            && $this->longitude >= $bbox['min_lon']
            && $this->longitude <= $bbox['max_lon'];
    }
}
```

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter=SismoDTOTest`
Expected: PASS (4 testes).

- [ ] **Step 7: Commit**

```bash
git add SDC/tests/Fixtures/Sismos SDC/app/Modules/Sismos/DTOs/SismoDTO.php SDC/tests/Unit/Sismos/SismoDTOTest.php
git commit -m "✨ feat(sismos): DTO de evento sismico e fixtures reais de USP e UnB"
```

---

### Task 4: Ingestor e normalizador da USP (FDSN)

**Files:**
- Create: `SDC/app/Modules/Sismos/Ingestores/UspFdsnIngestor.php`
- Create: `SDC/app/Modules/Sismos/Normalizadores/FdsnTextNormalizador.php`
- Test: `SDC/tests/Unit/Sismos/UspFdsnIngestorTest.php`
- Test: `SDC/tests/Unit/Sismos/FdsnTextNormalizadorTest.php`

**Interfaces:**
- Consumes: `FonteIngestor`, `NormalizadorSilver`, `PayloadBruto` (Task 2); `SismoDTO` (Task 3); config `medalhao.sismos.*` (Task 1)
- Produces: `UspFdsnIngestor` com `chave() === 'usp-fdsn'`, `grupo() === 'sismos'`, `formato() === 'fdsn-text'`; `FdsnTextNormalizador` que devolve `iterable<SismoDTO>`.

Formato do FDSN text — 14 colunas separadas por `|`, cabecalho iniciado por `#`:

```
EventID|Time|Latitude|Longitude|Depth/km|Author|Catalog|Contributor|ContributorID|MagType|Magnitude|MagAuthor|EventLocationName|EventType
```

- [ ] **Step 1: Escrever o teste do normalizador**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Sismos;

use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Sismos\DTOs\SismoDTO;
use App\Modules\Sismos\Normalizadores\FdsnTextNormalizador;
use PHPUnit\Framework\TestCase;

final class FdsnTextNormalizadorTest extends TestCase
{
    private function payload(?string $conteudo = null): PayloadBruto
    {
        $conteudo ??= file_get_contents(base_path('tests/Fixtures/Sismos/usp-fdsn-mg.txt'));

        return new PayloadBruto($conteudo, 'fdsn-text');
    }

    /** @return list<SismoDTO> */
    private function normalizar(?string $conteudo = null): array
    {
        return iterator_to_array(
            (new FdsnTextNormalizador())->normalizar($this->payload($conteudo)),
            false
        );
    }

    public function test_ignora_o_cabecalho_e_le_todas_as_linhas(): void
    {
        $this->assertCount(4, $this->normalizar());
    }

    public function test_mapeia_os_campos_do_primeiro_evento(): void
    {
        $dto = $this->normalizar()[0];

        $this->assertSame('usp-fdsn', $dto->fonte);
        $this->assertSame('usp2026pdfj', $dto->evento_id);
        $this->assertSame('2026-08-04T05:09:39+00:00', $dto->origem_utc->toIso8601String());
        $this->assertEqualsWithDelta(-19.5194149017334, $dto->latitude, 1e-9);
        $this->assertEqualsWithDelta(-44.065032958984375, $dto->longitude, 1e-9);
        $this->assertEqualsWithDelta(0.0, $dto->profundidade_km, 1e-9);
        $this->assertEqualsWithDelta(1.8744161870778298, $dto->magnitude, 1e-9);
        $this->assertSame('mR', $dto->escala_magnitude);
        $this->assertSame('Matozinhos/MG', $dto->regiao);
        $this->assertSame('earthquake', $dto->tipo_evento);
        $this->assertSame('JAlexandre', $dto->autor);
    }

    public function test_campo_numerico_vazio_vira_null(): void
    {
        $conteudo = "#EventID|Time|Latitude|Longitude|Depth/km|Author|Catalog|Contributor|ContributorID|MagType|Magnitude|MagAuthor|EventLocationName|EventType\n"
            . "usp1|2026-08-04T05:09:39|-19.5|-44.0||JAlexandre||USP|usp1|mR||JAlexandre|Matozinhos/MG|earthquake\n";

        $dto = $this->normalizar($conteudo)[0];

        $this->assertNull($dto->profundidade_km);
        $this->assertNull($dto->magnitude);
    }

    public function test_linha_com_colunas_de_menos_e_descartada(): void
    {
        $conteudo = "#EventID|Time|Latitude|Longitude\n"
            . "usp1|2026-08-04T05:09:39|-19.5\n";

        $this->assertSame([], $this->normalizar($conteudo));
    }

    public function test_linha_em_branco_e_descartada(): void
    {
        $conteudo = "#cabecalho\n\n   \n";

        $this->assertSame([], $this->normalizar($conteudo));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=FdsnTextNormalizadorTest`
Expected: FAIL — `FdsnTextNormalizador` inexistente.

- [ ] **Step 3: Implementar o normalizador**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Normalizadores;

use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Sismos\DTOs\SismoDTO;
use Carbon\CarbonImmutable;
use Generator;
use Throwable;

final class FdsnTextNormalizador implements NormalizadorSilver
{
    private const COLUNAS = 14;

    private const COL_EVENT_ID = 0;
    private const COL_TIME = 1;
    private const COL_LAT = 2;
    private const COL_LON = 3;
    private const COL_DEPTH = 4;
    private const COL_AUTHOR = 5;
    private const COL_MAG_TYPE = 9;
    private const COL_MAG = 10;
    private const COL_LOCATION = 12;
    private const COL_EVENT_TYPE = 13;

    /** @return Generator<SismoDTO> */
    public function normalizar(PayloadBruto $bruto): Generator
    {
        foreach (preg_split('/\R/', $bruto->conteudo) ?: [] as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            $campos = explode('|', $linha);

            if (count($campos) < self::COLUNAS) {
                continue;
            }

            $dto = $this->montar($campos);

            if ($dto !== null) {
                yield $dto;
            }
        }
    }

    /** @param list<string> $campos */
    private function montar(array $campos): ?SismoDTO
    {
        try {
            return new SismoDTO(
                fonte: 'usp-fdsn',
                evento_id: trim($campos[self::COL_EVENT_ID]),
                origem_utc: CarbonImmutable::parse(trim($campos[self::COL_TIME]), 'UTC'),
                latitude: (float) trim($campos[self::COL_LAT]),
                longitude: (float) trim($campos[self::COL_LON]),
                profundidade_km: $this->numero($campos[self::COL_DEPTH]),
                magnitude: $this->numero($campos[self::COL_MAG]),
                escala_magnitude: $this->texto($campos[self::COL_MAG_TYPE]),
                modo: null,
                regiao: $this->texto($campos[self::COL_LOCATION]),
                tipo_evento: $this->texto($campos[self::COL_EVENT_TYPE]),
                autor: $this->texto($campos[self::COL_AUTHOR]),
            );
        } catch (Throwable) {
            // Linha malformada nao interrompe a coleta inteira; o Bronze
            // preserva o bruto para reprocessamento.
            return null;
        }
    }

    private function numero(string $valor): ?float
    {
        $valor = trim($valor);

        return $valor === '' ? null : (float) $valor;
    }

    private function texto(string $valor): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --filter=FdsnTextNormalizadorTest`
Expected: PASS (5 testes).

- [ ] **Step 5: Escrever o teste do ingestor**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Sismos;

use App\Modules\Sismos\Ingestores\UspFdsnIngestor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class UspFdsnIngestorTest extends TestCase
{
    public function test_identificacao_da_fonte(): void
    {
        $ingestor = app(UspFdsnIngestor::class);

        $this->assertSame('usp-fdsn', $ingestor->chave());
        $this->assertSame('sismos', $ingestor->grupo());
        $this->assertSame('fdsn-text', $ingestor->formato());
    }

    public function test_envia_a_bbox_de_mg_e_o_formato_texto(): void
    {
        Http::fake(['*' => Http::response("#cab\n", 200)]);

        app(UspFdsnIngestor::class)->coletar();

        Http::assertSent(function (Request $req): bool {
            $q = $req->data();

            return (float) $q['minlatitude'] === -22.9
                && (float) $q['maxlatitude'] === -14.23
                && (float) $q['minlongitude'] === -51.04
                && (float) $q['maxlongitude'] === -39.85
                && $q['format'] === 'text'
                && isset($q['starttime']);
        });
    }

    public function test_devolve_o_conteudo_bruto_intacto(): void
    {
        $corpo = file_get_contents(base_path('tests/Fixtures/Sismos/usp-fdsn-mg.txt'));
        Http::fake(['*' => Http::response($corpo, 200)]);

        $payload = app(UspFdsnIngestor::class)->coletar();

        $this->assertSame($corpo, $payload->conteudo);
        $this->assertSame('fdsn-text', $payload->formato);
        $this->assertSame(200, $payload->meta['status']);
        $this->assertArrayHasKey('url', $payload->meta);
    }

    public function test_http_404_do_fdsn_significa_sem_dados_e_nao_erro(): void
    {
        Http::fake(['*' => Http::response('Error 404: Not Found', 404)]);

        $payload = app(UspFdsnIngestor::class)->coletar();

        $this->assertSame('', $payload->conteudo);
        $this->assertSame(404, $payload->meta['status']);
    }

    public function test_erro_de_servidor_lanca_excecao(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->expectException(RuntimeException::class);

        app(UspFdsnIngestor::class)->coletar();
    }
}
```

Nota de dominio: no padrao FDSN, **404 significa "nenhum evento no criterio"**, nao falha. Tratar como erro geraria alarme falso em periodo sem sismos em MG — que e o caso normal.

- [ ] **Step 6: Rodar e ver falhar**

Run: `php artisan test --filter=UspFdsnIngestorTest`
Expected: FAIL — `UspFdsnIngestor` inexistente.

- [ ] **Step 7: Implementar o ingestor**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Ingestores;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class UspFdsnIngestor implements FonteIngestor
{
    public function chave(): string
    {
        return 'usp-fdsn';
    }

    public function grupo(): string
    {
        return 'sismos';
    }

    public function formato(): string
    {
        return 'fdsn-text';
    }

    public function coletar(): PayloadBruto
    {
        $url = (string) config('medalhao.sismos.usp_fdsn_url');
        $bbox = config('medalhao.sismos.bbox');
        $dias = (int) config('medalhao.sismos.janela_coleta_dias');

        $params = [
            'starttime' => Carbon::now('UTC')->subDays($dias)->format('Y-m-d'),
            'minlatitude' => $bbox['min_lat'],
            'maxlatitude' => $bbox['max_lat'],
            'minlongitude' => $bbox['min_lon'],
            'maxlongitude' => $bbox['max_lon'],
            'format' => 'text',
        ];

        $inicio = microtime(true);
        $resposta = Http::timeout(30)->retry(3, 500)->get($url, $params);
        $duracao = (int) round((microtime(true) - $inicio) * 1000);

        $meta = [
            'url' => $url,
            'params' => $params,
            'status' => $resposta->status(),
            'duracao_ms' => $duracao,
        ];

        // No padrao FDSN, 204 e 404 significam "nenhum evento no criterio".
        if (in_array($resposta->status(), [204, 404], true)) {
            return new PayloadBruto('', $this->formato(), $meta);
        }

        if ($resposta->failed()) {
            throw new RuntimeException(
                "Falha ao consultar o FDSN da USP: HTTP {$resposta->status()}"
            );
        }

        return new PayloadBruto($resposta->body(), $this->formato(), $meta);
    }
}
```

- [ ] **Step 8: Rodar e ver passar**

Run: `php artisan test --filter=UspFdsnIngestorTest`
Expected: PASS (5 testes).

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/Sismos/Ingestores/UspFdsnIngestor.php \
        SDC/app/Modules/Sismos/Normalizadores/FdsnTextNormalizador.php \
        SDC/tests/Unit/Sismos/UspFdsnIngestorTest.php \
        SDC/tests/Unit/Sismos/FdsnTextNormalizadorTest.php
git commit -m "✨ feat(sismos): ingestao e normalizacao do FDSN da USP"
```

---

### Task 5: Ingestor e normalizador do UnB (obsis)

**Files:**
- Create: `SDC/app/Modules/Sismos/Ingestores/UnbObsisIngestor.php`
- Create: `SDC/app/Modules/Sismos/Normalizadores/ObsisCsvNormalizador.php`
- Test: `SDC/tests/Unit/Sismos/UnbObsisIngestorTest.php`
- Test: `SDC/tests/Unit/Sismos/ObsisCsvNormalizadorTest.php`

**Interfaces:**
- Consumes: mesmos contratos da Task 4
- Produces: `UnbObsisIngestor` com `chave() === 'unb-obsis'`, `grupo() === 'sismos'`, `formato() === 'obsis-csv'`; `ObsisCsvNormalizador` que devolve apenas eventos dentro da bbox de MG.

Duas armadilhas reais, ambas mascaradas pelo Selenium original:
1. As quebras de linha chegam como entidade `&#10;`. O `get_attribute("value")` do WebDriver decodificava; com HTTP puro e preciso `html_entity_decode`.
2. A coluna `Local` traz `Brazil` generico, nao o estado. Filtrar MG por texto **nao funciona** — o filtro tem de ser geografico. `Local` tambem pode conter virgula (`Salvador, BA`), entao o parser fixa as 8 primeiras e as 3 ultimas colunas e junta o miolo.

Colunas: `N, Data, Hora(UTC), Latitude, Longitude, Magnitude, Escala, Profundidade(km), Local, Tipo, IDSCP3, Revisor`

- [ ] **Step 1: Escrever o teste do ingestor**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Sismos;

use App\Modules\Sismos\Ingestores\UnbObsisIngestor;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class UnbObsisIngestorTest extends TestCase
{
    private function fakeComFixture(): void
    {
        Http::fake([
            '*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Sismos/unb-obsis.html')),
                200
            ),
        ]);
    }

    public function test_identificacao_da_fonte(): void
    {
        $ingestor = app(UnbObsisIngestor::class);

        $this->assertSame('unb-obsis', $ingestor->chave());
        $this->assertSame('sismos', $ingestor->grupo());
        $this->assertSame('obsis-csv', $ingestor->formato());
    }

    public function test_extrai_o_textarea_e_decodifica_as_entidades(): void
    {
        $this->fakeComFixture();

        $payload = app(UnbObsisIngestor::class)->coletar();

        $this->assertStringNotContainsString('&#10;', $payload->conteudo);
        $this->assertStringContainsString("\n", $payload->conteudo);
        $this->assertStringContainsString('unb2026owdm', $payload->conteudo);
        $this->assertSame('obsis-csv', $payload->formato);
    }

    public function test_pagina_sem_textarea_lanca_excecao(): void
    {
        Http::fake(['*' => Http::response('<html><body>manutencao</body></html>', 200)]);

        $this->expectException(RuntimeException::class);

        app(UnbObsisIngestor::class)->coletar();
    }

    public function test_erro_http_lanca_excecao(): void
    {
        Http::fake(['*' => Http::response('erro', 503)]);

        $this->expectException(RuntimeException::class);

        app(UnbObsisIngestor::class)->coletar();
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=UnbObsisIngestorTest`
Expected: FAIL — classe inexistente.

- [ ] **Step 3: Implementar o ingestor**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Ingestores;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class UnbObsisIngestor implements FonteIngestor
{
    public function chave(): string
    {
        return 'unb-obsis';
    }

    public function grupo(): string
    {
        return 'sismos';
    }

    public function formato(): string
    {
        return 'obsis-csv';
    }

    public function coletar(): PayloadBruto
    {
        $url = (string) config('medalhao.sismos.unb_obsis_url');

        $inicio = microtime(true);
        $resposta = Http::timeout(30)->retry(3, 500)->get($url);
        $duracao = (int) round((microtime(true) - $inicio) * 1000);

        if ($resposta->failed()) {
            throw new RuntimeException(
                "Falha ao consultar o obsis da UnB: HTTP {$resposta->status()}"
            );
        }

        return new PayloadBruto(
            $this->extrairTextarea($resposta->body()),
            $this->formato(),
            [
                'url' => $url,
                'status' => $resposta->status(),
                'duracao_ms' => $duracao,
            ],
        );
    }

    private function extrairTextarea(string $html): string
    {
        if (preg_match('/<textarea[^>]*>(.*?)<\/textarea>/is', $html, $m) !== 1) {
            throw new RuntimeException('Textarea de eventos nao encontrado na pagina do obsis.');
        }

        // O portal serializa as quebras de linha como entidade &#10;. O Selenium
        // decodificava via get_attribute("value"); no HTTP puro isso e manual.
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --filter=UnbObsisIngestorTest`
Expected: PASS (4 testes).

- [ ] **Step 5: Escrever o teste do normalizador**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Sismos;

use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Sismos\DTOs\SismoDTO;
use App\Modules\Sismos\Normalizadores\ObsisCsvNormalizador;
use Tests\TestCase;

final class ObsisCsvNormalizadorTest extends TestCase
{
    private const CABECALHO = "TABELA DE EVENTOS\nN, Data, Hora(UTC), Latitude, Longitude, Magnitude, Escala, Profundidade(km), Local, Tipo, IDSCP3, Revisor\n";

    /** @return list<SismoDTO> */
    private function normalizar(string $linhas): array
    {
        return iterator_to_array(
            app(ObsisCsvNormalizador::class)->normalizar(
                new PayloadBruto(self::CABECALHO . $linhas, 'obsis-csv')
            ),
            false
        );
    }

    public function test_mantem_apenas_eventos_dentro_de_mg(): void
    {
        $dtos = $this->normalizar(
            "1, 31/07/2026, 08:14:42, -18.86904525756836, -44.758079528808594, 2.9, mR, 0.0, Brazil, earthquake, unb2026owdm, rbernardes\n"
            . "2, 31/07/2026, 00:58:44, -8.392281532287598, -74.15995025634766, 5.7, Mwp, 155.9, Peru-Brazil Border Region, earthquake, unb2026ovpc, rbernardes\n"
        );

        $this->assertCount(1, $dtos);
        $this->assertSame('unb2026owdm', $dtos[0]->evento_id);
    }

    public function test_mapeia_os_campos_e_monta_a_data_em_utc(): void
    {
        $dto = $this->normalizar(
            "1, 31/07/2026, 08:14:42, -18.86904525756836, -44.758079528808594, 2.9, mR, 0.0, Brazil, earthquake, unb2026owdm, rbernardes\n"
        )[0];

        $this->assertSame('unb-obsis', $dto->fonte);
        $this->assertSame('unb2026owdm', $dto->evento_id);
        $this->assertSame('2026-07-31T08:14:42+00:00', $dto->origem_utc->toIso8601String());
        $this->assertEqualsWithDelta(-18.86904525756836, $dto->latitude, 1e-9);
        $this->assertEqualsWithDelta(2.9, $dto->magnitude, 1e-9);
        $this->assertSame('mR', $dto->escala_magnitude);
        $this->assertSame('Brazil', $dto->regiao);
        $this->assertSame('earthquake', $dto->tipo_evento);
        $this->assertSame('rbernardes', $dto->autor);
    }

    public function test_local_com_virgula_nao_desalinha_as_colunas(): void
    {
        // Evento dentro de MG, mas com virgula no campo Local.
        $dto = $this->normalizar(
            "1, 31/07/2026, 08:14:42, -19.5, -44.0, 2.1, mR, 0.0, Belo Horizonte, MG, earthquake, unb2026xxxx, rbernardes\n"
        )[0];

        $this->assertSame('Belo Horizonte, MG', $dto->regiao);
        $this->assertSame('earthquake', $dto->tipo_evento);
        $this->assertSame('unb2026xxxx', $dto->evento_id);
        $this->assertSame('rbernardes', $dto->autor);
    }

    public function test_linha_curta_e_descartada(): void
    {
        $this->assertSame([], $this->normalizar("1, 31/07/2026, 08:14:42\n"));
    }

    public function test_data_invalida_e_descartada(): void
    {
        $this->assertSame([], $this->normalizar(
            "1, xx/xx/xxxx, 99:99:99, -19.5, -44.0, 2.1, mR, 0.0, Brazil, earthquake, unb1, rb\n"
        ));
    }
}
```

- [ ] **Step 6: Rodar e ver falhar**

Run: `php artisan test --filter=ObsisCsvNormalizadorTest`
Expected: FAIL — classe inexistente.

- [ ] **Step 7: Implementar o normalizador**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Normalizadores;

use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Sismos\DTOs\SismoDTO;
use Carbon\CarbonImmutable;
use Generator;
use Throwable;

final class ObsisCsvNormalizador implements NormalizadorSilver
{
    /**
     * Colunas: N, Data, Hora(UTC), Latitude, Longitude, Magnitude, Escala,
     * Profundidade(km), Local, Tipo, IDSCP3, Revisor.
     * O campo Local pode conter virgula, entao as 8 primeiras e as 3 ultimas
     * posicoes sao fixas e o miolo e rejuntado.
     */
    private const MIN_CAMPOS = 12;

    /** @return Generator<SismoDTO> */
    public function normalizar(PayloadBruto $bruto): Generator
    {
        $bbox = config('medalhao.sismos.bbox');

        foreach (preg_split('/\R/', $bruto->conteudo) ?: [] as $linha) {
            $linha = trim($linha);

            if ($linha === '' || ! preg_match('/^\d+\s*,/', $linha)) {
                continue;
            }

            $campos = array_map('trim', explode(',', $linha));

            if (count($campos) < self::MIN_CAMPOS) {
                continue;
            }

            $dto = $this->montar($campos);

            if ($dto !== null && $dto->dentroDaBbox($bbox)) {
                yield $dto;
            }
        }
    }

    /** @param list<string> $campos */
    private function montar(array $campos): ?SismoDTO
    {
        $local = trim(implode(', ', array_slice($campos, 8, count($campos) - 11)));

        try {
            $origem = CarbonImmutable::createFromFormat(
                'd/m/Y H:i:s',
                "{$campos[1]} {$campos[2]}",
                'UTC'
            );

            if ($origem === false) {
                return null;
            }

            return new SismoDTO(
                fonte: 'unb-obsis',
                evento_id: $campos[count($campos) - 2],
                origem_utc: $origem,
                latitude: (float) $campos[3],
                longitude: (float) $campos[4],
                profundidade_km: $this->numero($campos[7]),
                magnitude: $this->numero($campos[5]),
                escala_magnitude: $this->texto($campos[6]),
                modo: null,
                regiao: $local === '' ? null : $local,
                tipo_evento: $this->texto($campos[count($campos) - 3]),
                autor: $this->texto($campos[count($campos) - 1]),
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function numero(string $valor): ?float
    {
        return $valor === '' ? null : (float) $valor;
    }

    private function texto(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }
}
```

Atencao: `CarbonImmutable::createFromFormat` com data invalida pode lancar em vez de devolver `false` dependendo da versao — por isso o `try/catch` envolve a chamada. Se o teste `test_data_invalida_e_descartada` falhar por a excecao nao ser capturada, confirme que o `catch (Throwable)` engloba a criacao da data.

- [ ] **Step 8: Rodar e ver passar**

Run: `php artisan test --filter=ObsisCsvNormalizadorTest`
Expected: PASS (5 testes).

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/Sismos/Ingestores/UnbObsisIngestor.php \
        SDC/app/Modules/Sismos/Normalizadores/ObsisCsvNormalizador.php \
        SDC/tests/Unit/Sismos/UnbObsisIngestorTest.php \
        SDC/tests/Unit/Sismos/ObsisCsvNormalizadorTest.php
git commit -m "✨ feat(sismos): ingestao e normalizacao do obsis da UnB com filtro geografico"
```

---

### Task 6: Camada Silver com PostGIS e o repositorio de upsert

**Files:**
- Create: `SDC/database/migrations/2026_08_07_000003_create_silver_sismos.php`
- Create: `SDC/app/Modules/Sismos/Repositories/SismoRepository.php`
- Test: `SDC/tests/Feature/Sismos/SismoRepositoryTest.php`

**Interfaces:**
- Consumes: schema `silver` (Task 1); `SismoDTO` (Task 3)
- Produces: tabela `silver.sismos`; `SismoRepository::upsertLote(iterable $dtos, ?int $ingestaoId = null): int` (devolve quantos registros foram gravados), `SismoRepository::totalPorFonte(string $fonte): int`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Sismos;

use App\Modules\Sismos\DTOs\SismoDTO;
use App\Modules\Sismos\Repositories\SismoRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SismoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Silver exige PostgreSQL com PostGIS.');
        }
    }

    private function dto(string $eventoId, float $mag = 2.5): SismoDTO
    {
        return new SismoDTO(
            fonte: 'usp-fdsn',
            evento_id: $eventoId,
            origem_utc: CarbonImmutable::parse('2026-07-31T08:14:42Z'),
            latitude: -18.851715,
            longitude: -44.757480,
            profundidade_km: 0.0,
            magnitude: $mag,
            escala_magnitude: 'mR',
            modo: null,
            regiao: 'Felixlandia/MG',
            tipo_evento: 'earthquake',
            autor: 'JAlexandre',
        );
    }

    public function test_grava_o_ponto_com_srid_4326(): void
    {
        app(SismoRepository::class)->upsertLote([$this->dto('usp2026owdm')]);

        $linha = DB::selectOne('SELECT ST_X(geom) AS lon, ST_Y(geom) AS lat, ST_SRID(geom) AS srid FROM silver.sismos');

        $this->assertEqualsWithDelta(-44.757480, (float) $linha->lon, 1e-6);
        $this->assertEqualsWithDelta(-18.851715, (float) $linha->lat, 1e-6);
        $this->assertSame(4326, (int) $linha->srid);
    }

    public function test_reingestao_do_mesmo_evento_atualiza_e_nao_duplica(): void
    {
        $repo = app(SismoRepository::class);

        $repo->upsertLote([$this->dto('usp2026owdm', 2.5)]);
        $repo->upsertLote([$this->dto('usp2026owdm', 3.1)]);

        $this->assertSame(1, $repo->totalPorFonte('usp-fdsn'));
        $this->assertEqualsWithDelta(
            3.1,
            (float) DB::scalar('SELECT magnitude FROM silver.sismos'),
            1e-6
        );
    }

    public function test_mesmo_evento_id_em_fontes_diferentes_coexiste(): void
    {
        $repo = app(SismoRepository::class);

        $repo->upsertLote([$this->dto('owdm')]);
        $repo->upsertLote([new SismoDTO(
            fonte: 'unb-obsis',
            evento_id: 'owdm',
            origem_utc: CarbonImmutable::parse('2026-07-31T08:14:42Z'),
            latitude: -18.869045,
            longitude: -44.758079,
        )]);

        $this->assertSame(2, (int) DB::scalar('SELECT count(*) FROM silver.sismos'));
    }

    public function test_devolve_a_quantidade_gravada(): void
    {
        $total = app(SismoRepository::class)->upsertLote([
            $this->dto('a'),
            $this->dto('b'),
            $this->dto('c'),
        ]);

        $this->assertSame(3, $total);
    }

    public function test_lote_vazio_nao_quebra(): void
    {
        $this->assertSame(0, app(SismoRepository::class)->upsertLote([]));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=SismoRepositoryTest`
Expected: FAIL — tabela e repositorio inexistentes.

- [ ] **Step 3: Criar a migration da Silver**

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

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.sismos (
                id               bigserial PRIMARY KEY,
                fonte            varchar(64)  NOT NULL,
                evento_id        varchar(64)  NOT NULL,
                origem_utc       timestamptz  NOT NULL,
                geom             geometry(Point, 4326) NOT NULL,
                profundidade_km  numeric(8,3) NULL,
                magnitude        numeric(5,3) NULL,
                escala_magnitude varchar(16)  NULL,
                modo             varchar(16)  NULL,
                regiao           text         NULL,
                tipo_evento      varchar(32)  NULL,
                autor            varchar(64)  NULL,
                ingestao_id      bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at       timestamptz  NOT NULL DEFAULT now(),
                updated_at       timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_sismos_fonte_evento UNIQUE (fonte, evento_id)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_sismos_geom ON silver.sismos USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_sismos_origem ON silver.sismos (origem_utc DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.sismos');
    }
};
```

- [ ] **Step 4: Implementar o repositorio**

O upsert usa SQL cru porque `geom` exige `ST_SetSRID(ST_MakePoint(...))`, que o `upsert()` do Eloquent nao expressa.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Repositories;

use App\Modules\Sismos\DTOs\SismoDTO;
use Illuminate\Support\Facades\DB;

final class SismoRepository
{
    private const CHUNK = 500;

    /** @param iterable<SismoDTO> $dtos */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $total = 0;
        $lote = [];

        foreach ($dtos as $dto) {
            $lote[] = $dto;

            if (count($lote) >= self::CHUNK) {
                $total += $this->gravar($lote, $ingestaoId);
                $lote = [];
            }
        }

        if ($lote !== []) {
            $total += $this->gravar($lote, $ingestaoId);
        }

        return $total;
    }

    public function totalPorFonte(string $fonte): int
    {
        return (int) DB::scalar('SELECT count(*) FROM silver.sismos WHERE fonte = ?', [$fonte]);
    }

    /** @param list<SismoDTO> $lote */
    private function gravar(array $lote, ?int $ingestaoId): int
    {
        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?, ?, ?, ?, ?, ?, now(), now())';

            array_push(
                $bindings,
                $dto->fonte,
                $dto->evento_id,
                $dto->origem_utc->toIso8601String(),
                $dto->longitude,   // PostGIS espera X (longitude) primeiro
                $dto->latitude,
                $dto->profundidade_km,
                $dto->magnitude,
                $dto->escala_magnitude,
                $dto->modo,
                $dto->regiao,
                $dto->tipo_evento,
                $dto->autor,
                $ingestaoId,
            );
        }

        $sql = 'INSERT INTO silver.sismos
                    (fonte, evento_id, origem_utc, geom, profundidade_km, magnitude,
                     escala_magnitude, modo, regiao, tipo_evento, autor, ingestao_id,
                     created_at, updated_at)
                VALUES ' . implode(', ', $placeholders) . '
                ON CONFLICT (fonte, evento_id) DO UPDATE SET
                    origem_utc       = EXCLUDED.origem_utc,
                    geom             = EXCLUDED.geom,
                    profundidade_km  = EXCLUDED.profundidade_km,
                    magnitude        = EXCLUDED.magnitude,
                    escala_magnitude = EXCLUDED.escala_magnitude,
                    modo             = EXCLUDED.modo,
                    regiao           = EXCLUDED.regiao,
                    tipo_evento      = EXCLUDED.tipo_evento,
                    autor            = EXCLUDED.autor,
                    ingestao_id      = EXCLUDED.ingestao_id,
                    updated_at       = now()';

        DB::statement($sql, $bindings);

        return count($lote);
    }
}
```

Atencao ao numero de bindings: sao **13 por linha** (12 campos + `ingestao_id`), enquanto o placeholder tem 13 marcadores `?` (lon e lat contam como dois). Confira que a ordem do `array_push` bate exatamente com a ordem do placeholder antes de rodar.

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --filter=SismoRepositoryTest`
Expected: PASS (5 testes).

- [ ] **Step 6: Commit**

```bash
git add SDC/database/migrations/2026_08_07_000003_create_silver_sismos.php \
        SDC/app/Modules/Sismos/Repositories/SismoRepository.php \
        SDC/tests/Feature/Sismos/SismoRepositoryTest.php
git commit -m "🗃️ db(sismos): camada silver com geometria PostGIS e upsert idempotente"
```

---

### Task 7: Camada Gold — matviews e job de refresh

**Files:**
- Create: `SDC/database/migrations/2026_08_07_000004_create_gold_sismos_views.php`
- Create: `SDC/app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php`
- Modify: `SDC/app/Modules/Sismos/Repositories/SismoRepository.php` (adicionar leitura da Gold)
- Test: `SDC/tests/Feature/Sismos/GoldSismosTest.php`

**Interfaces:**
- Consumes: `silver.sismos` (Task 6); config `medalhao.sismos.janela_mapa_dias` (Task 1)
- Produces: matviews `gold.sismos_mapa` (colunas `id, fonte, evento_id, origem_utc, latitude, longitude, magnitude, escala_magnitude, classe_magnitude, profundidade_km, regiao, geom`) e `gold.sismos_estatisticas` (`total_eventos, magnitude_media, magnitude_maxima, ultima_atualizacao`); `AtualizarGoldSismosJob` na fila `medalhao`; `SismoRepository::mapa(?array $bbox = null): Collection` e `::estatisticas(): array`.

Classes de magnitude (convencao usada no mapa): `< 2.0` = `micro`, `2.0–3.9` = `leve`, `4.0–4.9` = `moderado`, `>= 5.0` = `forte`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Sismos;

use App\Modules\Sismos\DTOs\SismoDTO;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use App\Modules\Sismos\Repositories\SismoRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GoldSismosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Matviews exigem PostgreSQL.');
        }
    }

    private function semear(): void
    {
        app(SismoRepository::class)->upsertLote([
            new SismoDTO('usp-fdsn', 'a', CarbonImmutable::now('UTC')->subDay(), -18.85, -44.75, 0.0, 2.5, 'mR'),
            new SismoDTO('usp-fdsn', 'b', CarbonImmutable::now('UTC')->subDays(2), -19.51, -44.06, 0.0, 5.5, 'mb'),
            new SismoDTO('usp-fdsn', 'antigo', CarbonImmutable::now('UTC')->subDays(400), -19.0, -44.0, 0.0, 3.0, 'mR'),
        ]);

        (new AtualizarGoldSismosJob())->handle();
    }

    public function test_mapa_extrai_lat_lon_e_respeita_a_janela(): void
    {
        $this->semear();

        $linhas = DB::select('SELECT evento_id, latitude, longitude FROM gold.sismos_mapa ORDER BY evento_id');

        $this->assertCount(2, $linhas, 'Evento fora da janela nao deve aparecer.');
        $this->assertSame('a', $linhas[0]->evento_id);
        $this->assertEqualsWithDelta(-18.85, (float) $linhas[0]->latitude, 1e-6);
        $this->assertEqualsWithDelta(-44.75, (float) $linhas[0]->longitude, 1e-6);
    }

    public function test_classe_de_magnitude(): void
    {
        $this->semear();

        $classes = DB::table('gold.sismos_mapa')->pluck('classe_magnitude', 'evento_id');

        $this->assertSame('leve', $classes['a']);
        $this->assertSame('forte', $classes['b']);
    }

    public function test_estatisticas_agregam_a_janela(): void
    {
        $this->semear();

        $stats = app(SismoRepository::class)->estatisticas();

        $this->assertSame(2, (int) $stats['total_eventos']);
        $this->assertEqualsWithDelta(4.0, (float) $stats['magnitude_media'], 1e-6);
        $this->assertEqualsWithDelta(5.5, (float) $stats['magnitude_maxima'], 1e-6);
    }

    public function test_refresh_e_idempotente(): void
    {
        $this->semear();
        (new AtualizarGoldSismosJob())->handle();

        $this->assertSame(2, (int) DB::scalar('SELECT count(*) FROM gold.sismos_mapa'));
    }

    public function test_filtro_por_bounding_box(): void
    {
        $this->semear();

        $dentro = app(SismoRepository::class)->mapa([
            'min_lat' => -19.0, 'max_lat' => -18.0,
            'min_lon' => -45.0, 'max_lon' => -44.0,
        ]);

        $this->assertCount(1, $dentro);
        $this->assertSame('a', $dentro->first()->evento_id);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=GoldSismosTest`
Expected: FAIL — matviews e job inexistentes.

- [ ] **Step 3: Criar a migration das matviews**

A janela e interpolada na definicao da view (matview nao aceita parametro em tempo de leitura). Se `janela_mapa_dias` mudar, rode `migrate:refresh` da migration ou recrie a view.

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

        $dias = (int) config('medalhao.sismos.janela_mapa_dias', 90);

        DB::statement(<<<SQL
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.sismos_mapa AS
            SELECT
                s.id,
                s.fonte,
                s.evento_id,
                s.origem_utc,
                ST_Y(s.geom) AS latitude,
                ST_X(s.geom) AS longitude,
                s.geom,
                s.magnitude,
                s.escala_magnitude,
                s.profundidade_km,
                s.regiao,
                CASE
                    WHEN s.magnitude IS NULL  THEN 'desconhecido'
                    WHEN s.magnitude <  2.0   THEN 'micro'
                    WHEN s.magnitude <  4.0   THEN 'leve'
                    WHEN s.magnitude <  5.0   THEN 'moderado'
                    ELSE 'forte'
                END AS classe_magnitude
            FROM silver.sismos s
            WHERE s.origem_utc >= now() - INTERVAL '{$dias} days'
        SQL);

        // Indice UNICO e obrigatorio para REFRESH ... CONCURRENTLY.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_sismos_mapa_id ON gold.sismos_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_sismos_mapa_geom ON gold.sismos_mapa USING GIST (geom)');

        DB::statement(<<<SQL
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.sismos_estatisticas AS
            SELECT
                1                        AS id,
                count(*)                 AS total_eventos,
                round(avg(magnitude), 2) AS magnitude_media,
                max(magnitude)           AS magnitude_maxima,
                now()                    AS ultima_atualizacao
            FROM gold.sismos_mapa
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_sismos_estatisticas ON gold.sismos_estatisticas (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.sismos_estatisticas');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.sismos_mapa');
    }
};
```

- [ ] **Step 4: Criar o job de refresh**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldSismosJob implements ShouldQueue
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

    /** @return list<object> */
    public function middleware(): array
    {
        // Dois refresh concorrentes da mesma matview so competem por I/O.
        return [(new WithoutOverlapping('gold-sismos'))->expireAfter(600)];
    }

    public function handle(): void
    {
        // CONCURRENTLY evita travar a leitura do mapa durante a atualizacao.
        // Exige o indice unico criado na migration.
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.sismos_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.sismos_estatisticas');
    }
}
```

Nota: `REFRESH ... CONCURRENTLY` falha se a matview nunca foi populada. As matviews sao criadas ja populadas (sem `WITH NO DATA`), entao isso esta coberto.

- [ ] **Step 5: Adicionar a leitura da Gold ao repositorio**

Acrescente a `SismoRepository`:

```php
    /** @param array{min_lat: float, max_lat: float, min_lon: float, max_lon: float}|null $bbox */
    public function mapa(?array $bbox = null): \Illuminate\Support\Collection
    {
        $query = DB::table('gold.sismos_mapa')
            ->select([
                'id', 'fonte', 'evento_id', 'origem_utc', 'latitude', 'longitude',
                'magnitude', 'escala_magnitude', 'classe_magnitude',
                'profundidade_km', 'regiao',
            ])
            ->orderByDesc('origem_utc');

        if ($bbox !== null) {
            $query->whereRaw(
                'ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
                [$bbox['min_lon'], $bbox['min_lat'], $bbox['max_lon'], $bbox['max_lat']]
            );
        }

        return $query->get();
    }

    /** @return array{total_eventos: int, magnitude_media: float, magnitude_maxima: float, ultima_atualizacao: ?string} */
    public function estatisticas(): array
    {
        $linha = DB::table('gold.sismos_estatisticas')->first();

        return [
            'total_eventos' => (int) ($linha->total_eventos ?? 0),
            'magnitude_media' => (float) ($linha->magnitude_media ?? 0),
            'magnitude_maxima' => (float) ($linha->magnitude_maxima ?? 0),
            'ultima_atualizacao' => $linha->ultima_atualizacao ?? null,
        ];
    }
```

Adicione `use Illuminate\Support\Collection;` ao topo e troque o tipo de retorno de `mapa()` por `Collection`.

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter=GoldSismosTest`
Expected: PASS (5 testes).

- [ ] **Step 7: Commit**

```bash
git add SDC/database/migrations/2026_08_07_000004_create_gold_sismos_views.php \
        SDC/app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php \
        SDC/app/Modules/Sismos/Repositories/SismoRepository.php \
        SDC/tests/Feature/Sismos/GoldSismosTest.php
git commit -m "🗃️ db(sismos): matviews gold do mapa e estatisticas com refresh concorrente"
```

---

### Task 8: Jobs de ingestao e normalizacao do kernel

**Files:**
- Create: `SDC/app/Modules/Medalhao/Jobs/IngerirFonteJob.php`
- Create: `SDC/app/Modules/Medalhao/Jobs/NormalizarSilverJob.php`
- Create: `SDC/app/Modules/Sismos/SismosServiceProvider.php`
- Modify: `SDC/config/app.php` (registrar `SismosServiceProvider`)
- Test: `SDC/tests/Feature/Medalhao/PipelineIngestaoTest.php`

**Interfaces:**
- Consumes: `IngestorRegistry` (Task 2); ingestores e normalizadores (Tasks 4-5); `SismoRepository` (Task 6); `AtualizarGoldSismosJob` (Task 7)
- Produces: `IngerirFonteJob::__construct(string $chave)`; `NormalizarSilverJob::__construct(int $ingestaoId, string $chave)`; ambos na fila `medalhao`.

Para manter o kernel agnostico, o `NormalizarSilverJob` recebe o destino via um `callable` resolvido pelo registry seria acoplamento indireto. Solucao mais simples e explicita: o normalizador devolve DTOs e o job delega a persistencia a um `SismoRepository` resolvido pelo container **apenas quando o grupo e `sismos`**. Como so ha um dominio nesta fase, o job usa um mapa de grupo -> repositorio declarado em `config/medalhao.php`.

Acrescente a `config/medalhao.php`:

```php
    // Mapa grupo -> classe que persiste os DTOs na camada Silver.
    'persistidores' => [
        'sismos' => \App\Modules\Sismos\Repositories\SismoRepository::class,
    ],
```

O contrato esperado do persistidor e `upsertLote(iterable $dtos, ?int $ingestaoId = null): int` — ja implementado pelo `SismoRepository`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use App\Modules\Medalhao\Jobs\IngerirFonteJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PipelineIngestaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Pipeline exige PostgreSQL com PostGIS.');
        }

        Http::fake([
            'moho.iag.usp.br/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Sismos/usp-fdsn-mg.txt')),
                200
            ),
        ]);
    }

    public function test_coleta_grava_bronze_e_popula_silver(): void
    {
        (new IngerirFonteJob('usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        $this->assertSame(1, IngestaoBruta::where('fonte', 'usp-fdsn')->count());
        $this->assertSame(4, (int) DB::scalar("SELECT count(*) FROM silver.sismos WHERE fonte = 'usp-fdsn'"));
    }

    public function test_segunda_coleta_identica_nao_cria_novo_bronze(): void
    {
        $registry = app(\App\Modules\Medalhao\Registry\IngestorRegistry::class);

        (new IngerirFonteJob('usp-fdsn'))->handle($registry);
        (new IngerirFonteJob('usp-fdsn'))->handle($registry);

        $this->assertSame(1, IngestaoBruta::where('fonte', 'usp-fdsn')->count());
    }

    public function test_bronze_guarda_o_texto_original_e_o_meta(): void
    {
        (new IngerirFonteJob('usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        $bronze = IngestaoBruta::where('fonte', 'usp-fdsn')->firstOrFail();

        $this->assertStringContainsString('usp2026owdm', $bronze->conteudo_bruto);
        $this->assertSame('fdsn-text', $bronze->formato);
        $this->assertSame(hash('sha256', $bronze->conteudo_bruto), $bronze->hash_conteudo);
        $this->assertSame(200, $bronze->meta['status']);
        $this->assertNotNull($bronze->processado_em);
    }

    public function test_silver_referencia_o_bronze_de_origem(): void
    {
        (new IngerirFonteJob('usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        $bronzeId = IngestaoBruta::where('fonte', 'usp-fdsn')->value('id');

        $this->assertSame(
            4,
            (int) DB::scalar('SELECT count(*) FROM silver.sismos WHERE ingestao_id = ?', [$bronzeId])
        );
    }

    public function test_payload_vazio_nao_grava_bronze(): void
    {
        Http::fake(['moho.iag.usp.br/*' => Http::response('', 404)]);

        (new IngerirFonteJob('usp-fdsn'))->handle(app(\App\Modules\Medalhao\Registry\IngestorRegistry::class));

        $this->assertSame(0, IngestaoBruta::count());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=PipelineIngestaoTest`
Expected: FAIL — jobs e provider inexistentes.

- [ ] **Step 3: Criar `IngerirFonteJob`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class IngerirFonteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(public readonly string $chave)
    {
        $this->onQueue('medalhao');
    }

    public function handle(IngestorRegistry $registry): void
    {
        $ingestor = $registry->ingestor($this->chave);
        $payload = $ingestor->coletar();

        if (trim($payload->conteudo) === '') {
            Log::info('medalhao: coleta sem conteudo', ['fonte' => $this->chave]);

            return;
        }

        $hash = $payload->hash();

        $jaExiste = IngestaoBruta::query()
            ->where('fonte', $this->chave)
            ->where('hash_conteudo', $hash)
            ->exists();

        if ($jaExiste) {
            Log::info('medalhao: conteudo identico ao anterior, ignorado', ['fonte' => $this->chave]);

            return;
        }

        $bronze = IngestaoBruta::create([
            'fonte' => $this->chave,
            'conteudo_bruto' => $payload->conteudo,
            'formato' => $payload->formato,
            'hash_conteudo' => $hash,
            'meta' => $payload->meta,
            'coletado_em' => now(),
        ]);

        NormalizarSilverJob::dispatch((int) $bronze->id, $this->chave);
    }
}
```

- [ ] **Step 4: Criar `NormalizarSilverJob`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NormalizarSilverJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(
        public readonly int $ingestaoId,
        public readonly string $chave,
    ) {
        $this->onQueue('medalhao');
    }

    public function handle(IngestorRegistry $registry): void
    {
        $bronze = IngestaoBruta::findOrFail($this->ingestaoId);
        $grupo = $registry->ingestor($this->chave)->grupo();

        $dtos = $registry->normalizador($this->chave)->normalizar(
            new PayloadBruto($bronze->conteudo_bruto, $bronze->formato, $bronze->meta ?? [])
        );

        $total = $this->persistidor($grupo)->upsertLote($dtos, $this->ingestaoId);

        $bronze->update(['processado_em' => now()]);

        Log::info('medalhao: silver atualizado', [
            'fonte' => $this->chave,
            'registros' => $total,
        ]);

        if ($grupo === 'sismos') {
            AtualizarGoldSismosJob::dispatch();
        }
    }

    private function persistidor(string $grupo): object
    {
        $classe = config("medalhao.persistidores.{$grupo}")
            ?? throw new RuntimeException("Sem persistidor configurado para o grupo: {$grupo}");

        return app($classe);
    }
}
```

- [ ] **Step 5: Criar o `SismosServiceProvider` e registrar**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos;

use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Sismos\Ingestores\UnbObsisIngestor;
use App\Modules\Sismos\Ingestores\UspFdsnIngestor;
use App\Modules\Sismos\Normalizadores\FdsnTextNormalizador;
use App\Modules\Sismos\Normalizadores\ObsisCsvNormalizador;
use App\Modules\Sismos\Repositories\SismoRepository;
use Illuminate\Support\ServiceProvider;

class SismosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SismoRepository::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(IngestorRegistry::class);

        $registry->registrar(
            $this->app->make(UspFdsnIngestor::class),
            $this->app->make(FdsnTextNormalizador::class),
        );

        $registry->registrar(
            $this->app->make(UnbObsisIngestor::class),
            $this->app->make(ObsisCsvNormalizador::class),
        );
    }
}
```

Em `config/app.php`, logo apos `MedalhaoServiceProvider::class`:

```php
        App\Modules\Sismos\SismosServiceProvider::class,
```

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter=PipelineIngestaoTest`
Expected: PASS (5 testes).

Nota: `QUEUE_CONNECTION=sync` no `phpunit.xml` faz o `dispatch` do `NormalizarSilverJob` rodar na hora, o que e o comportamento desejado neste teste de integracao.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Medalhao/Jobs SDC/app/Modules/Sismos/SismosServiceProvider.php \
        SDC/config/app.php SDC/config/medalhao.php \
        SDC/tests/Feature/Medalhao/PipelineIngestaoTest.php
git commit -m "✨ feat(medalhao): jobs de ingestao e normalizacao com deduplicacao por hash"
```

---

### Task 9: Comando, agendamento e worker dedicado

**Files:**
- Create: `SDC/app/Modules/Medalhao/Console/IngerirCommand.php`
- Modify: `SDC/routes/console.php` (agendamento)
- Create: `SDC/docker/supervisor/medalhao-worker.conf`
- Modify: `SDC/docker/docker-compose.yml` (comentario apontando o worker; ver Step 5)
- Test: `SDC/tests/Feature/Medalhao/IngerirCommandTest.php`

**Interfaces:**
- Consumes: `IngestorRegistry` (Task 2), `IngerirFonteJob` (Task 8)
- Produces: comando `medalhao:ingerir {grupo}`; fila `medalhao` consumida por processo proprio.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use App\Modules\Medalhao\Jobs\IngerirFonteJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class IngerirCommandTest extends TestCase
{
    public function test_despacha_um_job_por_fonte_do_grupo(): void
    {
        Queue::fake();

        $this->artisan('medalhao:ingerir', ['grupo' => 'sismos'])
            ->assertExitCode(0);

        Queue::assertPushed(IngerirFonteJob::class, 2);
        Queue::assertPushed(fn (IngerirFonteJob $j): bool => $j->chave === 'usp-fdsn');
        Queue::assertPushed(fn (IngerirFonteJob $j): bool => $j->chave === 'unb-obsis');
    }

    public function test_job_vai_para_a_fila_medalhao(): void
    {
        Queue::fake();

        $this->artisan('medalhao:ingerir', ['grupo' => 'sismos']);

        Queue::assertPushedOn('medalhao', IngerirFonteJob::class);
    }

    public function test_grupo_sem_fonte_falha_com_codigo_1(): void
    {
        Queue::fake();

        $this->artisan('medalhao:ingerir', ['grupo' => 'inexistente'])
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=IngerirCommandTest`
Expected: FAIL — comando nao registrado.

- [ ] **Step 3: Criar o comando**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Jobs\IngerirFonteJob;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Console\Command;

class IngerirCommand extends Command
{
    protected $signature = 'medalhao:ingerir {grupo : Grupo de fontes, ex.: sismos}';

    protected $description = 'Despacha a coleta das fontes de um grupo para a fila medalhao';

    public function handle(IngestorRegistry $registry): int
    {
        $grupo = (string) $this->argument('grupo');
        $chaves = $registry->chavesDoGrupo($grupo);

        if ($chaves === []) {
            $this->error("Nenhuma fonte registrada para o grupo: {$grupo}");

            return self::FAILURE;
        }

        foreach ($chaves as $chave) {
            IngerirFonteJob::dispatch($chave);
            $this->line("Coleta despachada: {$chave}");
        }

        $this->info(sprintf('%d fonte(s) do grupo %s na fila.', count($chaves), $grupo));

        return self::SUCCESS;
    }
}
```

Registre-o no `MedalhaoServiceProvider::boot()`:

```php
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Medalhao\Console\IngerirCommand::class,
            ]);
        }
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --filter=IngerirCommandTest`
Expected: PASS (3 testes).

- [ ] **Step 5: Agendar e criar o worker dedicado**

Em `SDC/routes/console.php`, junto dos demais `Schedule::command(...)`:

```php
// Pipeline medalhao. Fila propria (ver docker/supervisor/medalhao-worker.conf):
// ETL nao pode disputar worker com notificacao e webhook.
Schedule::command('medalhao:ingerir sismos')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->runInBackground();
```

Criar `SDC/docker/supervisor/medalhao-worker.conf`, espelhando o formato de `laravel-worker.conf`:

```ini
[program:medalhao-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --queue=medalhao --sleep=5 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/medalhao-worker.log
stopwaitsecs=310
```

Confira `laravel-worker.conf` e alinhe `user`, caminhos de log e demais diretivas ao que o arquivo existente usa — os valores acima seguem o padrao esperado, mas o arquivo real e a fonte da verdade.

O `--timeout=300` combina com `IngerirFonteJob::$timeout`; `stopwaitsecs` deve ser maior que o timeout para o supervisor nao matar job em andamento no deploy.

- [ ] **Step 6: Verificar que a fila esta isolada**

Run: `grep -rn "queue:work" SDC/docker/`
Expected: o worker principal continua **sem** `medalhao` na lista `critical,high,high-throughput,webhooks,default,low`, e o novo arquivo consome apenas `medalhao`.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Medalhao/Console/IngerirCommand.php \
        SDC/app/Modules/Medalhao/MedalhaoServiceProvider.php \
        SDC/routes/console.php SDC/docker/supervisor/medalhao-worker.conf \
        SDC/tests/Feature/Medalhao/IngerirCommandTest.php
git commit -m "✨ feat(medalhao): comando de ingestao, agendamento e worker dedicado da fila"
```

---

### Task 10: Arquivamento Parquet e poda do Bronze

**Files:**
- Create: `SDC/app/Modules/Medalhao/Contracts/ArquivadorBronze.php`
- Create: `SDC/app/Modules/Medalhao/Infrastructure/FlowParquetArquivador.php`
- Create: `SDC/app/Modules/Medalhao/Jobs/RolloverParquetJob.php`
- Create: `SDC/app/Modules/Medalhao/Console/RollupCommand.php`
- Modify: `SDC/app/Modules/Medalhao/MedalhaoServiceProvider.php` (binding + comando)
- Modify: `SDC/routes/console.php` (agendamento diario)
- Modify: `SDC/composer.json` (`flow-php/parquet`)
- Test: `SDC/tests/Feature/Medalhao/RolloverParquetTest.php`

**Interfaces:**
- Consumes: `IngestaoBruta` (Task 2), config `medalhao.retencao_dias` e `medalhao.disco` (Task 1)
- Produces: `ArquivadorBronze::arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string` (devolve o caminho relativo escrito); `RolloverParquetJob`; comando `medalhao:rollup`.

Regra inegociavel: **a poda so ocorre depois de reler o arquivo escrito**. Falha na escrita ou na releitura aborta e mantem o Bronze intacto.

- [ ] **Step 1: Instalar a dependencia**

```bash
cd SDC && composer require flow-php/parquet
```

Se a instalacao falhar por restricao de plataforma, pare e reporte — nao troque a lib sem registrar a decisao no spec.

- [ ] **Step 2: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use App\Modules\Medalhao\Jobs\RolloverParquetJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RolloverParquetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Bronze exige PostgreSQL.');
        }

        Storage::fake('medalhao');
        config()->set('medalhao.retencao_dias', 30);
    }

    private function semear(int $diasAtras, string $hash): IngestaoBruta
    {
        return IngestaoBruta::create([
            'fonte' => 'usp-fdsn',
            'conteudo_bruto' => "conteudo {$hash}",
            'formato' => 'fdsn-text',
            'hash_conteudo' => hash('sha256', $hash),
            'meta' => ['status' => 200],
            'coletado_em' => now()->subDays($diasAtras),
            'processado_em' => now()->subDays($diasAtras),
        ]);
    }

    public function test_arquiva_e_poda_apenas_o_que_passou_da_retencao(): void
    {
        $antigo = $this->semear(45, 'antigo');
        $recente = $this->semear(5, 'recente');

        (new RolloverParquetJob())->handle(app(\App\Modules\Medalhao\Contracts\ArquivadorBronze::class));

        $this->assertDatabaseMissing('bronze.ingestao_bruta', ['id' => $antigo->id]);
        $this->assertDatabaseHas('bronze.ingestao_bruta', ['id' => $recente->id]);
    }

    public function test_grava_o_parquet_particionado_por_fonte_e_dia(): void
    {
        $this->semear(45, 'antigo');

        (new RolloverParquetJob())->handle(app(\App\Modules\Medalhao\Contracts\ArquivadorBronze::class));

        $arquivos = Storage::disk('medalhao')->allFiles();

        $this->assertCount(1, $arquivos);
        $this->assertStringContainsString('bronze/fonte=usp-fdsn/dt=', $arquivos[0]);
        $this->assertStringEndsWith('.parquet', $arquivos[0]);
    }

    public function test_nada_a_arquivar_nao_quebra(): void
    {
        $this->semear(5, 'recente');

        (new RolloverParquetJob())->handle(app(\App\Modules\Medalhao\Contracts\ArquivadorBronze::class));

        $this->assertSame([], Storage::disk('medalhao')->allFiles());
        $this->assertSame(1, IngestaoBruta::count());
    }

    public function test_falha_na_escrita_preserva_o_bronze(): void
    {
        $antigo = $this->semear(45, 'antigo');

        $arquivadorQuebrado = new class implements \App\Modules\Medalhao\Contracts\ArquivadorBronze {
            public function arquivar(string $fonte, \Carbon\CarbonInterface $dia, iterable $linhas): string
            {
                throw new \RuntimeException('disco cheio');
            }
        };

        try {
            (new RolloverParquetJob())->handle($arquivadorQuebrado);
            $this->fail('Esperava excecao do arquivador.');
        } catch (\RuntimeException) {
            // esperado
        }

        $this->assertDatabaseHas('bronze.ingestao_bruta', ['id' => $antigo->id]);
    }
}
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `php artisan test --filter=RolloverParquetTest`
Expected: FAIL — contrato e job inexistentes.

- [ ] **Step 4: Criar o contrato e a implementacao**

`Contracts/ArquivadorBronze.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use Carbon\CarbonInterface;

interface ArquivadorBronze
{
    /**
     * Escreve as linhas em Parquet e devolve o caminho relativo no disco.
     *
     * @param iterable<array<string, mixed>> $linhas
     */
    public function arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string;
}
```

`Infrastructure/FlowParquetArquivador.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Infrastructure;

use App\Modules\Medalhao\Contracts\ArquivadorBronze;
use Carbon\CarbonInterface;
use Flow\Parquet\ParquetFile\Schema;
use Flow\Parquet\ParquetFile\Schema\FlatColumn;
use Flow\Parquet\Writer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class FlowParquetArquivador implements ArquivadorBronze
{
    public function arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string
    {
        $disco = (string) config('medalhao.disco');
        $relativo = sprintf(
            'bronze/fonte=%s/dt=%s/parte-0.parquet',
            $fonte,
            $dia->format('Y-m-d')
        );

        $temporario = tempnam(sys_get_temp_dir(), 'medalhao-parquet-');

        if ($temporario === false) {
            throw new RuntimeException('Nao foi possivel criar arquivo temporario para o Parquet.');
        }

        try {
            $schema = Schema::with(
                FlatColumn::int64('id'),
                FlatColumn::string('fonte'),
                FlatColumn::string('conteudo_bruto'),
                FlatColumn::string('formato'),
                FlatColumn::string('hash_conteudo'),
                FlatColumn::string('meta'),
                FlatColumn::string('coletado_em'),
                FlatColumn::string('processado_em'),
            );

            $writer = new Writer();
            $writer->write($temporario, $schema, $linhas);

            $conteudo = file_get_contents($temporario);

            if ($conteudo === false || $conteudo === '') {
                throw new RuntimeException('Parquet gerado vazio.');
            }

            Storage::disk($disco)->put($relativo, $conteudo);

            if (! Storage::disk($disco)->exists($relativo)) {
                throw new RuntimeException("Parquet nao encontrado apos a escrita: {$relativo}");
            }

            return $relativo;
        } finally {
            @unlink($temporario);
        }
    }
}
```

Se a API do `flow-php/parquet` divergir (a lib esta em 0.x), ajuste **apenas esta classe** — o contrato e os testes do job nao mudam. Consulte a documentacao instalada em `vendor/flow-php/parquet` para a assinatura correta de `Writer::write`.

- [ ] **Step 5: Criar o job e o comando**

`Jobs/RolloverParquetJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\Contracts\ArquivadorBronze;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RolloverParquetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    public function handle(ArquivadorBronze $arquivador): void
    {
        $corte = Carbon::now()->subDays((int) config('medalhao.retencao_dias'));

        $grupos = IngestaoBruta::query()
            ->where('coletado_em', '<', $corte)
            ->selectRaw('fonte, date(coletado_em) AS dia')
            ->groupBy('fonte')
            ->groupByRaw('date(coletado_em)')
            ->get();

        foreach ($grupos as $grupo) {
            $dia = Carbon::parse($grupo->dia);

            $registros = IngestaoBruta::query()
                ->where('fonte', $grupo->fonte)
                ->whereRaw('date(coletado_em) = ?', [$dia->toDateString()])
                ->orderBy('id')
                ->get();

            if ($registros->isEmpty()) {
                continue;
            }

            $linhas = $registros->map(fn (IngestaoBruta $r): array => [
                'id' => (int) $r->id,
                'fonte' => (string) $r->fonte,
                'conteudo_bruto' => (string) $r->conteudo_bruto,
                'formato' => (string) $r->formato,
                'hash_conteudo' => (string) $r->hash_conteudo,
                'meta' => json_encode($r->meta ?? [], JSON_UNESCAPED_UNICODE),
                'coletado_em' => (string) $r->coletado_em?->toIso8601String(),
                'processado_em' => (string) $r->processado_em?->toIso8601String(),
            ])->all();

            // Escreve e verifica. Se qualquer coisa falhar, a excecao sobe e a
            // poda nao acontece: o Bronze permanece intacto.
            $caminho = $arquivador->arquivar($grupo->fonte, $dia, $linhas);

            IngestaoBruta::query()->whereIn('id', $registros->pluck('id'))->delete();

            Log::info('medalhao: bronze arquivado em parquet', [
                'fonte' => $grupo->fonte,
                'dia' => $dia->toDateString(),
                'registros' => $registros->count(),
                'arquivo' => $caminho,
            ]);
        }
    }
}
```

`Console/RollupCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Jobs\RolloverParquetJob;
use Illuminate\Console\Command;

class RollupCommand extends Command
{
    protected $signature = 'medalhao:rollup';

    protected $description = 'Arquiva a camada Bronze vencida em Parquet e poda o Postgres';

    public function handle(): int
    {
        RolloverParquetJob::dispatch();

        $this->info('Rollup da camada Bronze despachado para a fila medalhao.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Ligar binding, comando e agendamento**

No `MedalhaoServiceProvider::register()`:

```php
        $this->app->bind(
            \App\Modules\Medalhao\Contracts\ArquivadorBronze::class,
            \App\Modules\Medalhao\Infrastructure\FlowParquetArquivador::class,
        );
```

No `boot()`, acrescente `RollupCommand::class` ao array `$this->commands([...])`.

Em `routes/console.php`:

```php
Schedule::command('medalhao:rollup')
    ->dailyAt('04:00')
    ->onOneServer()
    ->runInBackground();
```

- [ ] **Step 7: Rodar e ver passar**

Run: `php artisan test --filter=RolloverParquetTest`
Expected: PASS (4 testes).

- [ ] **Step 8: Provar que o Parquet abre em pandas**

Gere um arquivo real e leia com o mesmo ferramental do CINDEC:

```bash
cd SDC && php artisan medalhao:rollup
python -c "import pandas as pd, glob; f=glob.glob('storage/app/medalhao/bronze/**/*.parquet', recursive=True); print(f); d=pd.read_parquet(f[0]); print(d.dtypes); print(d.head())"
```

Expected: o DataFrame carrega, com as 8 colunas e `id` inteiro. Se falhar, o problema esta em `FlowParquetArquivador` — corrija ali, nao no job.

Se nao houver dado vencido no ambiente, insira uma linha de teste com `coletado_em` antiga antes de rodar.

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/Medalhao SDC/composer.json SDC/composer.lock \
        SDC/routes/console.php SDC/tests/Feature/Medalhao/RolloverParquetTest.php
git commit -m "✨ feat(medalhao): arquivamento parquet da camada bronze com poda verificada"
```

---

### Task 11: Entrega — controller, rota e mapa Leaflet

**Files:**
- Create: `SDC/app/Modules/Sismos/Controllers/SismosIndexController.php`
- Create: `SDC/routes/modules/sismos.php`
- Modify: `SDC/routes/web.php` (require do modulo, junto de `inmet.php`)
- Create: `SDC/resources/js/Pages/Sismos/MapaSismos.vue`
- Test: `SDC/tests/Feature/Sismos/SismosIndexControllerTest.php`

**Interfaces:**
- Consumes: `SismoRepository::mapa()` e `::estatisticas()` (Task 7)
- Produces: rota nomeada `sismos.index` em `GET /sismos`; pagina Inertia `Sismos/MapaSismos` com props `eventos`, `estatisticas`, `bbox`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Sismos;

use App\Modules\Sismos\DTOs\SismoDTO;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use App\Modules\Sismos\Repositories\SismoRepository;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class SismosIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada Gold exige PostgreSQL.');
        }

        app(SismoRepository::class)->upsertLote([
            new SismoDTO('usp-fdsn', 'usp2026owdm', CarbonImmutable::now('UTC')->subDay(), -18.85, -44.75, 0.0, 2.5, 'mR', null, 'Felixlandia/MG', 'earthquake'),
        ]);

        (new AtualizarGoldSismosJob())->handle();
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/sismos')->assertRedirect();
    }

    public function test_renderiza_a_pagina_com_eventos_e_estatisticas(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/sismos')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Sismos/MapaSismos')
                ->has('eventos', 1)
                ->where('eventos.0.evento_id', 'usp2026owdm')
                ->where('eventos.0.classe_magnitude', 'leve')
                ->where('estatisticas.total_eventos', 1)
                ->has('bbox')
            );
    }
}
```

Se o projeto exigir permissao especifica para paginas de modulo, espelhe o que `routes/modules/inmet.php` faz — a rota de sismos deve usar o mesmo nivel de protecao da de INMET, nem mais nem menos.

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --filter=SismosIndexControllerTest`
Expected: FAIL — rota 404.

- [ ] **Step 3: Criar o controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sismos\Repositories\SismoRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SismosIndexController extends Controller
{
    public function __construct(
        private readonly SismoRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $bbox = config('medalhao.sismos.bbox');

        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        return Inertia::render('Sismos/MapaSismos', [
            'eventos' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            'bbox' => $bbox,
        ]);
    }
}
```

- [ ] **Step 4: Criar a rota**

`routes/modules/sismos.php`:

```php
<?php

use App\Modules\Sismos\Controllers\SismosIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('sismos')->name('sismos.')->group(function () {
    Route::get('/', SismosIndexController::class)->name('index');
});
```

Em `routes/web.php`, na secao de modulos operacionais, imediatamente apos a linha `require __DIR__ . '/modules/inmet.php';`:

```php
    require __DIR__ . '/modules/sismos.php';
```

- [ ] **Step 5: Criar a pagina Vue**

`resources/js/Pages/Sismos/MapaSismos.vue`:

```vue
<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Sismos monitorados
      </h2>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-gray-500">Eventos na janela</p>
            <p class="text-2xl font-semibold">{{ estatisticas.total_eventos }}</p>
          </div>
          <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-gray-500">Magnitude media</p>
            <p class="text-2xl font-semibold">{{ estatisticas.magnitude_media }}</p>
          </div>
          <div class="rounded-lg bg-white p-4 shadow">
            <p class="text-sm text-gray-500">Magnitude maxima</p>
            <p class="text-2xl font-semibold">{{ estatisticas.magnitude_maxima }}</p>
          </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow">
          <div id="mapa-sismos" class="h-[600px] w-full rounded"></div>
          <p v-if="eventos.length === 0" class="mt-2 text-sm text-gray-500">
            Nenhum evento sismico registrado na janela atual.
          </p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onMounted } from 'vue';

const props = defineProps({
  eventos: { type: Array, default: () => [] },
  estatisticas: { type: Object, default: () => ({}) },
  bbox: { type: Object, required: true },
});

const CORES = {
  micro: '#94a3b8',
  leve: '#22c55e',
  moderado: '#f59e0b',
  forte: '#ef4444',
  desconhecido: '#64748b',
};

onMounted(async () => {
  await nextTick();

  const mapa = L.map('mapa-sismos').fitBounds([
    [props.bbox.min_lat, props.bbox.min_lon],
    [props.bbox.max_lat, props.bbox.max_lon],
  ]);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 18,
  }).addTo(mapa);

  props.eventos.forEach((evento) => {
    const magnitude = Number(evento.magnitude) || 0;

    L.circleMarker([Number(evento.latitude), Number(evento.longitude)], {
      radius: Math.max(4, magnitude * 2),
      color: CORES[evento.classe_magnitude] ?? CORES.desconhecido,
      fillColor: CORES[evento.classe_magnitude] ?? CORES.desconhecido,
      fillOpacity: 0.6,
      weight: 1,
    })
      .bindPopup(
        `<strong>${evento.regiao ?? 'Regiao nao informada'}</strong><br>` +
          `Magnitude: ${evento.magnitude ?? '-'} ${evento.escala_magnitude ?? ''}<br>` +
          `Profundidade: ${evento.profundidade_km ?? '-'} km<br>` +
          `Origem (UTC): ${evento.origem_utc}<br>` +
          `Fonte: ${evento.fonte}`,
      )
      .addTo(mapa);
  });
});
</script>
```

- [ ] **Step 6: Rodar e ver passar**

Run: `php artisan test --filter=SismosIndexControllerTest`
Expected: PASS (2 testes).

- [ ] **Step 7: Compilar o frontend**

Run: `cd SDC && npm run build`
Expected: build sem erro; `MapaSismos` aparece nos assets gerados.

- [ ] **Step 8: Commit**

```bash
git add SDC/app/Modules/Sismos/Controllers SDC/routes/modules/sismos.php \
        SDC/routes/web.php SDC/resources/js/Pages/Sismos \
        SDC/tests/Feature/Sismos/SismosIndexControllerTest.php
git commit -m "✨ feat(sismos): pagina de mapa Leaflet lendo exclusivamente a camada gold"
```

---

### Task 12: Verificacao final ponta a ponta

**Files:**
- Nenhum arquivo novo; validacao dos criterios do spec.

- [ ] **Step 1: Suite completa**

Run: `cd SDC && php artisan test`
Expected: verde. Fora do Postgres, os testes marcados como pgsql-only aparecem como skipped, nao failed.

- [ ] **Step 2: Migrations do zero**

Run: `php artisan migrate:fresh`
Expected: sem erro; os tres schemas, `silver.sismos` e as duas matviews existem.

Verificacao:

```bash
php artisan tinker --execute="
  print_r(DB::select(\"SELECT schema_name FROM information_schema.schemata WHERE schema_name IN ('bronze','silver','gold')\"));
  print_r(DB::select(\"SELECT matviewname FROM pg_matviews WHERE schemaname='gold'\"));
"
```

- [ ] **Step 3: Ciclo real de ingestao**

```bash
php artisan medalhao:ingerir sismos
php artisan queue:work --queue=medalhao --stop-when-empty
php artisan tinker --execute="
  echo 'bronze: ' . DB::scalar('SELECT count(*) FROM bronze.ingestao_bruta') . PHP_EOL;
  echo 'silver: ' . DB::scalar('SELECT count(*) FROM silver.sismos') . PHP_EOL;
  echo 'gold:   ' . DB::scalar('SELECT count(*) FROM gold.sismos_mapa') . PHP_EOL;
"
```

Expected: as tres contagens maiores que zero (assumindo que ha eventos em MG na janela; se `silver` vier zero, confirme com uma consulta direta ao FDSN antes de tratar como defeito).

- [ ] **Step 4: Idempotencia**

Rode `php artisan medalhao:ingerir sismos` e o worker de novo.
Expected: a contagem de `bronze` **nao** aumenta (hash identico) e a de `silver` permanece igual.

- [ ] **Step 5: Isolamento da fila**

Run: `grep -rn "queue:work" SDC/docker/`
Expected: `medalhao` aparece somente em `medalhao-worker.conf`; a lista do worker principal segue inalterada.

- [ ] **Step 6: Conferencia contra os criterios do spec**

Percorra a secao 9 do spec (`2026-08-07-medalhao-pipeline-sismos-design.md`) e marque os nove criterios. Qualquer um que nao passe vira correcao antes de considerar a fase concluida.

- [ ] **Step 7: Commit final**

```bash
git add -A
git commit -m "✅ test(medalhao): verificacao ponta a ponta da fase 1"
```

---

## Notas de execucao

**Branch.** A branch atual (`feat/ajuda-humanitaria-liberacoes`) tem trabalho nao relacionado em andamento. Crie uma branch propria antes da Task 1:

```bash
git switch -c feat/medalhao-pipeline-sismos
```

**Ordem.** As tasks 1 e 2 sao pre-requisito de tudo. As tasks 4 e 5 (USP e UnB) sao independentes entre si e podem ser paralelizadas. A 6 depende da 3; a 7 depende da 6; a 8 depende de 2, 4, 5, 6 e 7. As 9, 10 e 11 dependem da 8.

**Fora de escopo desta fase, por decisao registrada no spec:** deduplicacao entre USP e UnB (secao 8 do spec explica por que casar por sufixo de ID falha em silencio), ingestao do CEMADEN SALVAR, migracao do modulo Inmet, agregacoes de chuva e superficie interpolada.
