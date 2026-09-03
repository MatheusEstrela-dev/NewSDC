# Camadas geoespaciais por upload de KML/KMZ — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir subir KML/KMZ de alerta de risco, desenhar as areas no mapa e
responder no banco quanto choveu e quais municipios e estacoes estao dentro
delas.

**Architecture:** O upload grava o arquivo cru na camada Bronze do medalhao e
despacha o `NormalizarSilverJob` que ja existe; o parse de geometria acontece no
PostGIS via `ST_MakeValid(ST_Force2D(ST_GeomFromKML(...)))`, nunca em PHP. O
request HTTP so valida, grava e despacha — nenhum parse dentro do Octane.

**Tech Stack:** Laravel 12, PostgreSQL 17 + PostGIS 3.6.3, Inertia + Vue 3,
Leaflet, PHPUnit 11, Octane/Swoole, fila `medalhao`.

**Spec:** `docs/superpowers/specs/2026-09-03-geoespacial-kml-design.md`

## Global Constraints

- Sem emoji dentro de codigo. Comentarios em portugues sem acento, no estilo do
  modulo (explicam o *porque*, nao o *o que*).
- Toda migration de schema do medalhao comeca com
  `if (DB::getDriverName() !== 'pgsql') { return; }`.
- Geometria SEMPRE construida como `ST_MakeValid(ST_Force2D(ST_GeomFromKML(?)))`.
  Sem `ST_Force2D` o import falha com `SQLSTATE[22023] Geometry has Z dimension
  but column does not` — medido contra o arquivo real, que traz altitude.
- Matview do Gold exige indice UNICO para `REFRESH ... CONCURRENTLY`, e
  `CONCURRENTLY` nao roda dentro de transacao.
- Grupo do medalhao: `geoespacial`. Fonte do Bronze: `geo-upload` (uma so para
  os tres dominios — o dedup e por `(fonte, hash)`).
- Parse de XML externo: nao passar `LIBXML_NOENT` nem `LIBXML_DTDLOAD`; passar
  `LIBXML_NONET`.
- CSS: variante escura NUNCA via `:global(.dark)` dentro de `<style scoped>` — o
  compilador emite `.dark` pelado. Usar bloco `<style>` nao-scoped qualificado
  pelo container, e conferir no CSS **compilado**.
- Nada de `.value` em template Vue: desestruturar refs de topo.

## Ambiente de execucao

**Testes rodam no HOST, nao no container.** A imagem tem vendor `--no-dev`, entao
nao ha phpunit dentro dela. O PHP do host em `php` e 8.1 e nao parseia
`final readonly class`; use o 8.3 explicitamente.

`.env.testing` forca `DB_CONNECTION=sqlite`, o que faz TODO teste de PostGIS ser
pulado em silencio. As variaveis de ambiente do shell tem precedencia sobre o
arquivo, e e assim que se destrava.

**Base dedicada `sdc_geoespacial`** — os testes dao TRUNCATE, e rodar contra
`sdc` destruiria o dado de producao de dev (890 estacoes, snapshots do CEMADEN).

Comando de teste (usado em todos os passos "Run:"):

```bash
cd SDC
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc_geoespacial \
DB_USERNAME=sdc DB_PASSWORD=secret APP_CONFIG_CACHE=/nao/existe/config.php \
/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe \
  -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit --filter=<Teste>
```

Use `127.0.0.1`, nunca `localhost`: resolve para IPv6 e a conexao falha.

Depois de criar classe nova, o container precisa de
`docker exec newsdc_dev_app sh -c 'cd /var/www && composer dump-autoload'` — o
classmap e autoritativo e o vendor vive na imagem, nao no mount. O container da
fila precisa do mesmo comando, senao o worker nao acha a classe.

## Ordem e paralelismo

```
Onda 1 (paralelo, sem dependencia entre si):
  Task 1  migrations Silver + Gold
  Task 2  kernel: registro de fonte so-push
  Task 3  extrator de KML/KMZ (PHP puro)
  Task 7  MapaLeaflet aceita poligonos (frontend puro)

Onda 2:  Task 4  repositorio        (precisa de 1 e 3)
Onda 3:  Task 5  normalizador + gold + config  (precisa de 1, 3, 4)
Onda 4 (paralelo):
  Task 6  upload HTTP          (precisa de 3, 4, 5)
  Task 8  pagina Geoespacial   (precisa de 5 e 7)
Onda 5:  Task 9  camada no mapa da Meteorologia (precisa de 7 e 8)
```

Tasks 1, 2, 3 e 7 nao compartilham arquivo nenhum — sao seguras em paralelo.
Tasks 6 e 8 tambem nao. Todo o resto e sequencial de verdade.

---

### Task 1: Migrations do Silver e do Gold

**Files:**
- Create: `SDC/database/migrations/2026_09_03_000003_create_silver_geoespacial.php`
- Create: `SDC/database/migrations/2026_09_03_000004_create_gold_geoespacial_views.php`
- Test: `SDC/tests/Feature/Geoespacial/SchemaGeoespacialTest.php`

**Interfaces:**
- Consumes: nada.
- Produces: tabelas `silver.geo_camadas`, `silver.geo_feicoes`; matviews
  `gold.geo_feicao_mapa`, `gold.geo_camada_municipios`.

- [ ] **Step 1: Criar a base de verificacao**

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres -c "DROP DATABASE IF EXISTS sdc_geoespacial"
docker exec newsdc_dev_db psql -U sdc -d postgres -c "CREATE DATABASE sdc_geoespacial TEMPLATE template_postgis"
```

- [ ] **Step 2: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SchemaGeoespacialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada geoespacial exige PostgreSQL e PostGIS.');
        }
    }

    public function test_tabelas_do_silver_existem(): void
    {
        foreach (['geo_camadas', 'geo_feicoes'] as $tabela) {
            $existe = DB::scalar(
                'SELECT count(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                ['silver', $tabela]
            );

            $this->assertSame(1, (int) $existe, "silver.{$tabela} nao existe");
        }
    }

    public function test_geom_aceita_poligono_linha_e_ponto(): void
    {
        $tipo = DB::scalar(
            "SELECT format_type(a.atttypid, a.atttypmod)
               FROM pg_attribute a
               JOIN pg_class c ON c.oid = a.attrelid
               JOIN pg_namespace n ON n.oid = c.relnamespace
              WHERE n.nspname = 'silver' AND c.relname = 'geo_feicoes' AND a.attname = 'geom'"
        );

        // geometry(Geometry,4326) e nao geometry(MultiPolygon,4326): e o que
        // absorve linha de rio e ponto de regua quando hidro entrar.
        $this->assertSame('geometry(Geometry,4326)', $tipo);
    }

    public function test_hash_do_arquivo_e_unico(): void
    {
        $unicos = DB::scalar(
            "SELECT count(*) FROM pg_indexes
              WHERE schemaname = 'silver' AND tablename = 'geo_camadas'
                AND indexdef LIKE '%UNIQUE%' AND indexdef LIKE '%hash_arquivo%'"
        );

        $this->assertSame(1, (int) $unicos, 'sem indice unico em hash_arquivo o mesmo arquivo entra duas vezes');
    }

    public function test_feicoes_tem_indice_gist(): void
    {
        $gist = DB::scalar(
            "SELECT count(*) FROM pg_indexes
              WHERE schemaname = 'silver' AND tablename = 'geo_feicoes' AND indexdef LIKE '%gist%'"
        );

        $this->assertGreaterThan(0, (int) $gist, 'sem GIST o cruzamento espacial varre a tabela');
    }

    public function test_matviews_do_gold_tem_indice_unico(): void
    {
        // REFRESH ... CONCURRENTLY exige indice unico; sem ele o refresh trava
        // a leitura do mapa.
        foreach (['geo_feicao_mapa', 'geo_camada_municipios'] as $matview) {
            $unicos = DB::scalar(
                "SELECT count(*) FROM pg_indexes
                  WHERE schemaname = 'gold' AND tablename = ? AND indexdef LIKE '%UNIQUE%'",
                [$matview]
            );

            $this->assertGreaterThan(0, (int) $unicos, "gold.{$matview} sem indice unico");
        }
    }
}
```

- [ ] **Step 3: Rodar e confirmar que falha**

Run: comando de teste com `--filter=SchemaGeoespacialTest`
Expected: FAIL — `silver.geo_camadas nao existe`

- [ ] **Step 4: Escrever a migration do Silver**

Arquivo `2026_09_03_000003_create_silver_geoespacial.php`:

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

        // Cabecalho da camada: um registro por arquivo enviado. O dominio e
        // coluna e nao tabela porque o que varia entre hidro, geologico e
        // meteorologico e legenda e vocabulario, nao estrutura.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_camadas (
                id           bigserial PRIMARY KEY,
                dominio      varchar(20)  NOT NULL,
                nome         varchar(255) NOT NULL,
                arquivo_nome varchar(255) NOT NULL,
                emitido_em   date         NULL,
                valido_ate   date         NULL,
                nivel        varchar(40)  NULL,
                hash_arquivo char(64)     NOT NULL,
                ingestao_id  bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_geo_camadas_hash UNIQUE (hash_arquivo)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_dominio ON silver.geo_camadas (dominio, emitido_em DESC)');

        // Uma linha por Placemark. geometry(Geometry,4326) e nao MultiPolygon:
        // verificado que um campo unico com um GIST serve poligono, linha e
        // ponto, e hidro traz rio como linha.
        //
        // propriedades jsonb porque ExtendedData varia por fonte. O arquivo de
        // 28/02 nao tem nenhum, mas aviso meteorologico carrega atributos, e sem
        // o jsonb cada fonte nova pediria migration.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_feicoes (
                id           bigserial PRIMARY KEY,
                camada_id    bigint       NOT NULL REFERENCES silver.geo_camadas (id) ON DELETE CASCADE,
                nome         varchar(255) NULL,
                propriedades jsonb        NOT NULL DEFAULT '{}'::jsonb,
                geom         geometry(Geometry, 4326) NOT NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_geom ON silver.geo_feicoes USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_camada ON silver.geo_feicoes (camada_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.geo_feicoes');
        DB::statement('DROP TABLE IF EXISTS silver.geo_camadas');
    }
};
```

- [ ] **Step 5: Escrever a migration do Gold**

Arquivo `2026_09_03_000004_create_gold_geoespacial_views.php`:

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

        // GeoJSON pronto pelo banco: o request de leitura nao serializa
        // poligono, so le linha feita. Mesma disciplina do gold.inmet_mapa,
        // que ja entrega lat/lon extraidos da geometria.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_feicao_mapa AS
            SELECT
                f.id,
                f.camada_id,
                c.dominio,
                c.nome        AS camada_nome,
                c.nivel,
                c.emitido_em,
                f.nome        AS feicao_nome,
                f.propriedades,
                ST_GeometryType(f.geom)                     AS tipo_geometria,
                round((ST_Area(f.geom::geography) / 1000000)::numeric, 2) AS area_km2,
                ST_AsGeoJSON(f.geom)::jsonb                 AS geojson,
                f.geom
            FROM silver.geo_feicoes f
            JOIN silver.geo_camadas c ON c.id = f.camada_id
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_feicao_mapa_id ON gold.geo_feicao_mapa (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_geom ON gold.geo_feicao_mapa USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_camada ON gold.geo_feicao_mapa (camada_id)');

        // Cruzamento com municipios. ATENCAO: e centroide dentro do poligono, e
        // nao intersecao de area -- a tabela municipios guarda
        // latitude/longitude, nao geometria de territorio. Municipio cujo
        // centroide cai fora mas cujo territorio e atingido NAO entra. O numero
        // e piso, nao total, e a tela precisa dizer isso.
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_camada_municipios AS
            SELECT
                row_number() OVER (ORDER BY c.id, m.nome) AS id,
                c.id   AS camada_id,
                m.id   AS municipio_id,
                m.nome AS municipio_nome,
                m.uf
            FROM silver.geo_camadas c
            JOIN silver.geo_feicoes f ON f.camada_id = c.id
            JOIN municipios m
              ON ST_Contains(f.geom, ST_SetSRID(ST_MakePoint(m.longitude::float8, m.latitude::float8), 4326))
            WHERE m.latitude IS NOT NULL
              AND m.longitude IS NOT NULL
            GROUP BY c.id, m.id, m.nome, m.uf
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_camada_municipios ON gold.geo_camada_municipios (id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gold_geo_camada_municipios_camada ON gold.geo_camada_municipios (camada_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_camada_municipios');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS gold.geo_feicao_mapa');
    }
};
```

- [ ] **Step 6: Migrar a base de verificacao e rodar o teste**

```bash
cd SDC
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc_geoespacial \
DB_USERNAME=sdc DB_PASSWORD=secret APP_CONFIG_CACHE=/nao/existe/config.php \
/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe \
  -d extension=pdo_pgsql -d extension=pgsql artisan migrate --force
```

Run: comando de teste com `--filter=SchemaGeoespacialTest`
Expected: PASS (5 testes)

- [ ] **Step 7: Migrar a base de dev e commitar**

```bash
docker exec newsdc_dev_app sh -c 'cd /var/www && php artisan migrate --force'
git add SDC/database/migrations/2026_09_03_000003_create_silver_geoespacial.php \
        SDC/database/migrations/2026_09_03_000004_create_gold_geoespacial_views.php \
        SDC/tests/Feature/Geoespacial/SchemaGeoespacialTest.php
git commit -m "🗃️ db(geoespacial): schema Silver e Gold das camadas de KML"
```

---

### Task 2: Kernel — registro de fonte so-push

**Files:**
- Modify: `SDC/app/Modules/Medalhao/Registry/IngestorRegistry.php`
- Test: `SDC/tests/Feature/Medalhao/RegistroPushTest.php`

**Interfaces:**
- Consumes: `NormalizadorSilver`.
- Produces: `IngestorRegistry::registrarPush(string $chave, string $grupo, NormalizadorSilver $normalizador): void`.
  `normalizador(string $chave)` e `chavesDoGrupo(string $grupo)` passam a
  considerar as fontes so-push.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Medalhao;

use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use InvalidArgumentException;
use Tests\TestCase;

final class RegistroPushTest extends TestCase
{
    private function normalizadorFalso(): NormalizadorSilver
    {
        return new class implements NormalizadorSilver
        {
            public function normalizar(PayloadBruto $bruto): iterable
            {
                yield (object) ['conteudo' => $bruto->conteudo];
            }
        };
    }

    public function test_registra_normalizador_sem_ingestor(): void
    {
        $registry = new IngestorRegistry();
        $registry->registrarPush('geo-upload', 'geoespacial', $this->normalizadorFalso());

        $this->assertInstanceOf(NormalizadorSilver::class, $registry->normalizador('geo-upload'));
    }

    public function test_fonte_push_aparece_no_grupo(): void
    {
        $registry = new IngestorRegistry();
        $registry->registrarPush('geo-upload', 'geoespacial', $this->normalizadorFalso());

        $this->assertSame(['geo-upload'], $registry->chavesDoGrupo('geoespacial'));
    }

    public function test_fonte_push_nao_tem_ingestor(): void
    {
        // Upload nao tem o que coletar: pedir o ingestor de uma fonte so-push e
        // erro de programacao, e falhar alto e melhor que devolver um objeto
        // cujo coletar() nunca poderia ser chamado.
        $registry = new IngestorRegistry();
        $registry->registrarPush('geo-upload', 'geoespacial', $this->normalizadorFalso());

        $this->expectException(InvalidArgumentException::class);
        $registry->ingestor('geo-upload');
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: comando de teste com `--filter=RegistroPushTest`
Expected: FAIL — `Call to undefined method ...::registrarPush()`

- [ ] **Step 3: Implementar no registry**

Adicionar a propriedade e o metodo, e ajustar `chavesDoGrupo`:

```php
    /** @var array<string, string> chave -> grupo, para fontes sem ingestor. */
    private array $gruposPush = [];

    /**
     * Registra uma fonte que NAO e coletada: o conteudo chega por upload.
     *
     * Existe porque FonteIngestor::coletar() e contrato de pull agendado, e
     * upload nao tem o que coletar. Forcar um ingestor com coletar() que nunca
     * e chamado seria mentir no contrato para satisfazer o registro.
     */
    public function registrarPush(string $chave, string $grupo, NormalizadorSilver $normalizador): void
    {
        $this->normalizadores[$chave] = $normalizador;
        $this->gruposPush[$chave] = $grupo;
    }
```

E em `chavesDoGrupo`, apos o laco sobre `$this->ingestores`:

```php
        foreach ($this->gruposPush as $chave => $grupoPush) {
            if ($grupoPush === $grupo) {
                $chaves[] = $chave;
            }
        }
```

- [ ] **Step 4: Rodar e confirmar que passa**

Run: comando de teste com `--filter=RegistroPushTest`
Expected: PASS (3 testes)

- [ ] **Step 5: Confirmar que nao quebrou o registro normal**

Run: comando de teste com `--filter=IngerirCommandTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Medalhao/Registry/IngestorRegistry.php \
        SDC/tests/Feature/Medalhao/RegistroPushTest.php
git commit -m "✨ feat(medalhao): kernel aceita fonte so-push, sem ingestor"
```

---

### Task 3: Extrator de KML e KMZ

**Files:**
- Create: `SDC/app/Modules/Geoespacial/Services/KmlExtrator.php`
- Create: `SDC/app/Modules/Geoespacial/DTOs/FeicaoKmlDTO.php`
- Create: `SDC/tests/Fixtures/geoespacial/alerta-geologico.kml` (copia do arquivo real)
- Test: `SDC/tests/Feature/Geoespacial/KmlExtratorTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `FeicaoKmlDTO` com `public readonly ?string $nome` e
    `public readonly string $kmlGeometria`.
  - `KmlExtrator::feicoes(string $conteudo): array` — devolve `list<FeicaoKmlDTO>`.
  - `KmlExtrator::conteudoDeArquivo(string $caminho): string` — devolve o XML,
    extraindo do ZIP quando for KMZ.
  - `KmlExtrator::nomeDoDocumento(string $conteudo): ?string`.
  - Lanca `RuntimeException` para ZIP invalido, ausencia de `.kml` dentro do
    KMZ, ou tamanho descomprimido acima do limite.

- [ ] **Step 1: Copiar o arquivo real como fixture**

```bash
mkdir -p SDC/tests/Fixtures/geoespacial
cp .claude/ALERTA-RISCO-GEOLOGICO-MODERADO-28022026.kml \
   SDC/tests/Fixtures/geoespacial/alerta-geologico.kml
```

- [ ] **Step 2: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use App\Modules\Geoespacial\Services\KmlExtrator;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class KmlExtratorTest extends TestCase
{
    private KmlExtrator $extrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extrator = new KmlExtrator();
    }

    private function kmlReal(): string
    {
        return file_get_contents(base_path('tests/Fixtures/geoespacial/alerta-geologico.kml'));
    }

    public function test_extrai_as_seis_feicoes_do_arquivo_real(): void
    {
        $feicoes = $this->extrator->feicoes($this->kmlReal());

        $this->assertCount(6, $feicoes);
    }

    public function test_cada_feicao_traz_fragmento_de_geometria_que_o_postgis_aceita(): void
    {
        foreach ($this->extrator->feicoes($this->kmlReal()) as $feicao) {
            // O ST_GeomFromKML nao aceita a tag Placemark: so a geometria.
            $this->assertMatchesRegularExpression(
                '#^<(MultiGeometry|Polygon|LineString|Point)#',
                $feicao->kmlGeometria
            );
        }
    }

    public function test_nome_do_documento_serve_de_nome_da_camada(): void
    {
        // Os Placemarks deste arquivo tem nome "0"; o unico nome util e o do
        // Document.
        $this->assertSame('ALERTA MODERADO2802', $this->extrator->nomeDoDocumento($this->kmlReal()));
    }

    public function test_le_kmz_extraindo_o_kml_interno(): void
    {
        $kmz = tempnam(sys_get_temp_dir(), 'teste') . '.kmz';
        $zip = new ZipArchive();
        $zip->open($kmz, ZipArchive::CREATE);
        $zip->addFromString('doc.kml', $this->kmlReal());
        $zip->close();

        $conteudo = $this->extrator->conteudoDeArquivo($kmz);
        unlink($kmz);

        $this->assertCount(6, $this->extrator->feicoes($conteudo));
    }

    public function test_recusa_kmz_sem_kml_dentro(): void
    {
        $kmz = tempnam(sys_get_temp_dir(), 'teste') . '.kmz';
        $zip = new ZipArchive();
        $zip->open($kmz, ZipArchive::CREATE);
        $zip->addFromString('leiame.txt', 'sem kml aqui');
        $zip->close();

        try {
            $this->expectException(RuntimeException::class);
            $this->extrator->conteudoDeArquivo($kmz);
        } finally {
            @unlink($kmz);
        }
    }

    public function test_recusa_zip_bomb_pelo_tamanho_descomprimido(): void
    {
        // 60 MB de zeros comprimem para poucos KB. O limite tem de ser checado
        // ANTES de extrair, senao o worker estoura a memoria antes da validacao.
        $kmz = tempnam(sys_get_temp_dir(), 'teste') . '.kmz';
        $zip = new ZipArchive();
        $zip->open($kmz, ZipArchive::CREATE);
        $zip->addFromString('doc.kml', str_repeat('0', 60 * 1024 * 1024));
        $zip->close();

        try {
            $this->expectException(RuntimeException::class);
            $this->extrator->conteudoDeArquivo($kmz);
        } finally {
            @unlink($kmz);
        }
    }

    public function test_nao_resolve_entidade_externa(): void
    {
        // XXE: sem a guarda, o parse leria arquivo do servidor e o conteudo
        // vazaria para dentro da camada.
        $xxe = <<<'XML'
        <?xml version="1.0"?>
        <!DOCTYPE kml [<!ENTITY vazamento SYSTEM "file:///etc/hostname">]>
        <kml xmlns="http://www.opengis.net/kml/2.2"><Document><name>&vazamento;</name>
        <Placemark><Polygon><outerBoundaryIs><LinearRing><coordinates>
        -44,-20,0 -43,-20,0 -43,-19,0 -44,-20,0
        </coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>
        </Document></kml>
        XML;

        $nome = $this->extrator->nomeDoDocumento($xxe);

        $this->assertStringNotContainsString('/', (string) $nome);
        $this->assertNotEmpty($this->extrator->feicoes($xxe));
    }

    public function test_recusa_conteudo_que_nao_e_kml(): void
    {
        $this->expectException(RuntimeException::class);
        $this->extrator->feicoes('isto nao e xml nenhum');
    }
}
```

- [ ] **Step 3: Rodar e confirmar que falha**

Run: comando de teste com `--filter=KmlExtratorTest`
Expected: FAIL — `Class "App\Modules\Geoespacial\Services\KmlExtrator" not found`

- [ ] **Step 4: Escrever o DTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

final readonly class FeicaoKmlDTO
{
    /**
     * @param string $kmlGeometria Fragmento de geometria PURO (MultiGeometry,
     *                             Polygon, LineString ou Point). O
     *                             ST_GeomFromKML nao aceita a tag Placemark,
     *                             entao guardar o Placemark inteiro aqui faria
     *                             o insert falhar.
     */
    public function __construct(
        public ?string $nome,
        public string $kmlGeometria,
    ) {
    }
}
```

- [ ] **Step 5: Escrever o extrator**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Services;

use App\Modules\Geoespacial\DTOs\FeicaoKmlDTO;
use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Le KML e KMZ e devolve os fragmentos de geometria.
 *
 * Nao interpreta coordenada, nao calcula area, nao valida topologia: isso e
 * trabalho do PostGIS, via ST_GeomFromKML. Aqui so se separa o XML em pedacos.
 */
final class KmlExtrator
{
    /** Tamanho maximo descomprimido, contra zip bomb. */
    private const LIMITE_BYTES = 32 * 1024 * 1024;

    /** Geometrias que o ST_GeomFromKML aceita como raiz do fragmento. */
    private const RAIZES = ['MultiGeometry', 'Polygon', 'LineString', 'Point'];

    public function conteudoDeArquivo(string $caminho): string
    {
        if (! is_file($caminho)) {
            throw new RuntimeException("Arquivo nao encontrado: {$caminho}");
        }

        // KMZ e ZIP. A assinatura decide, e nao a extensao: extensao e o que o
        // usuario escreveu, assinatura e o que o arquivo e.
        $assinatura = (string) file_get_contents($caminho, false, null, 0, 2);

        return $assinatura === 'PK' ? $this->doKmz($caminho) : $this->doArquivoTexto($caminho);
    }

    /** @return list<FeicaoKmlDTO> */
    public function feicoes(string $conteudo): array
    {
        $dom = $this->carregar($conteudo);
        $feicoes = [];

        foreach ($dom->getElementsByTagName('Placemark') as $placemark) {
            foreach (self::RAIZES as $raiz) {
                $geometrias = $placemark->getElementsByTagName($raiz);

                if ($geometrias->length === 0) {
                    continue;
                }

                // Para o primeiro tipo encontrado: MultiGeometry ja contem
                // Polygon dentro, e pegar os dois duplicaria a feicao.
                $nome = null;

                foreach ($placemark->getElementsByTagName('name') as $tag) {
                    $nome = trim((string) $tag->nodeValue);
                    break;
                }

                $xml = $dom->saveXML($geometrias->item(0));

                if ($xml === false) {
                    continue;
                }

                $feicoes[] = new FeicaoKmlDTO(
                    // Neste arquivo todo Placemark se chama "0": nome inutil
                    // vira null para a camada cair no nome do Document.
                    nome: ($nome === '' || $nome === '0') ? null : $nome,
                    kmlGeometria: $xml,
                );

                break;
            }
        }

        if ($feicoes === []) {
            throw new RuntimeException('Nenhuma geometria encontrada: o arquivo nao parece ser um KML valido.');
        }

        return $feicoes;
    }

    public function nomeDoDocumento(string $conteudo): ?string
    {
        $dom = $this->carregar($conteudo);

        foreach ($dom->getElementsByTagName('Document') as $documento) {
            foreach ($documento->getElementsByTagName('name') as $tag) {
                $nome = trim((string) $tag->nodeValue);

                return $nome === '' ? null : $nome;
            }
        }

        return null;
    }

    /**
     * O XML vem de fora, entao o parse e fechado por opcao explicita:
     * LIBXML_NONET corta acesso a rede, e a ausencia de LIBXML_NOENT e de
     * LIBXML_DTDLOAD e o que impede entidade externa ler arquivo do servidor.
     */
    private function carregar(string $conteudo): DOMDocument
    {
        $dom = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);

        $ok = $dom->loadXML($conteudo, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if ($ok === false) {
            throw new RuntimeException('Conteudo nao e XML valido.');
        }

        return $dom;
    }

    private function doKmz(string $caminho): string
    {
        $zip = new ZipArchive();

        if ($zip->open($caminho) !== true) {
            throw new RuntimeException('KMZ ilegivel: ZIP invalido.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if ($stat === false || ! str_ends_with(strtolower((string) $stat['name']), '.kml')) {
                    continue;
                }

                // Checa o tamanho DESCOMPRIMIDO antes de extrair: depois de
                // extrair, a memoria do worker ja foi.
                if ((int) $stat['size'] > self::LIMITE_BYTES) {
                    throw new RuntimeException('KMZ recusado: conteudo descomprimido acima do limite.');
                }

                $conteudo = $zip->getFromIndex($i);

                if ($conteudo === false) {
                    throw new RuntimeException('Falha ao ler o KML de dentro do KMZ.');
                }

                return $conteudo;
            }
        } finally {
            $zip->close();
        }

        throw new RuntimeException('KMZ nao contem nenhum arquivo .kml.');
    }

    private function doArquivoTexto(string $caminho): string
    {
        if ((int) filesize($caminho) > self::LIMITE_BYTES) {
            throw new RuntimeException('KML recusado: acima do limite de tamanho.');
        }

        return (string) file_get_contents($caminho);
    }
}
```

- [ ] **Step 6: Rodar e confirmar que passa**

Run: comando de teste com `--filter=KmlExtratorTest`
Expected: PASS (8 testes)

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Geoespacial/ SDC/tests/Feature/Geoespacial/KmlExtratorTest.php \
        SDC/tests/Fixtures/geoespacial/alerta-geologico.kml
git commit -m "✨ feat(geoespacial): extrator de KML e KMZ com guardas de XXE e zip bomb"
```

---

### Task 4: Repositorio da camada

**Files:**
- Create: `SDC/app/Modules/Geoespacial/DTOs/CamadaGeoDTO.php`
- Create: `SDC/app/Modules/Geoespacial/Repositories/GeoCamadaRepository.php`
- Test: `SDC/tests/Feature/Geoespacial/GeoCamadaRepositoryTest.php`

**Interfaces:**
- Consumes: `FeicaoKmlDTO` (Task 3); tabelas do Task 1.
- Produces:
  - `CamadaGeoDTO` com `dominio`, `nome`, `arquivoNome`, `emitidoEm` (`?string`),
    `validoAte` (`?string`), `nivel` (`?string`), `hashArquivo`, e
    `feicoes` (`list<FeicaoKmlDTO>`).
  - `GeoCamadaRepository::upsertLote(iterable $dtos, ?int $ingestaoId = null): int`
    — contrato exigido pelo kernel.
  - `GeoCamadaRepository::mapa(?int $camadaId = null): Collection`
  - `GeoCamadaRepository::camadas(): Collection`
  - `GeoCamadaRepository::cruzamento(int $camadaId): array` com chaves
    `municipios` (int), `estacoes` (int), `chuva_media` (float),
    `chuva_maxima` (float), `estacoes_com_leitura` (int).

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Services\KmlExtrator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GeoCamadaRepositoryTest extends TestCase
{
    private GeoCamadaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada geoespacial exige PostgreSQL e PostGIS.');
        }

        DB::statement('TRUNCATE silver.geo_camadas CASCADE');

        $this->repo = app(GeoCamadaRepository::class);
    }

    private function camadaDoArquivoReal(string $hash = 'a1'): CamadaGeoDTO
    {
        $xml = file_get_contents(base_path('tests/Fixtures/geoespacial/alerta-geologico.kml'));

        return new CamadaGeoDTO(
            dominio: 'geologico',
            nome: 'ALERTA MODERADO2802',
            arquivoNome: 'alerta-geologico.kml',
            emitidoEm: '2026-02-28',
            validoAte: null,
            nivel: 'moderado',
            hashArquivo: str_pad($hash, 64, '0'),
            feicoes: app(KmlExtrator::class)->feicoes($xml),
        );
    }

    public function test_grava_as_seis_feicoes_do_arquivo_real(): void
    {
        $this->repo->upsertLote([$this->camadaDoArquivoReal()]);

        $this->assertSame(1, (int) DB::scalar('SELECT count(*) FROM silver.geo_camadas'));
        $this->assertSame(6, (int) DB::scalar('SELECT count(*) FROM silver.geo_feicoes'));
    }

    public function test_toda_geometria_gravada_e_valida_e_bidimensional(): void
    {
        // Sem ST_Force2D o insert falharia: o KML traz altitude e o
        // ST_GeomFromKML devolve POLYGON Z.
        $this->repo->upsertLote([$this->camadaDoArquivoReal()]);

        $this->assertSame(
            0,
            (int) DB::scalar('SELECT count(*) FROM silver.geo_feicoes WHERE NOT ST_IsValid(geom)'),
            'ha geometria invalida: ST_MakeValid nao foi aplicado'
        );
        $this->assertSame(
            0,
            (int) DB::scalar('SELECT count(*) FROM silver.geo_feicoes WHERE ST_Zmflag(geom) > 0'),
            'ha geometria 3D: ST_Force2D nao foi aplicado'
        );
    }

    public function test_mesmo_arquivo_nao_entra_duas_vezes(): void
    {
        $this->repo->upsertLote([$this->camadaDoArquivoReal('a1')]);
        $this->repo->upsertLote([$this->camadaDoArquivoReal('a1')]);

        $this->assertSame(1, (int) DB::scalar('SELECT count(*) FROM silver.geo_camadas'));
        $this->assertSame(6, (int) DB::scalar('SELECT count(*) FROM silver.geo_feicoes'));
    }

    public function test_arquivo_diferente_cria_camada_nova(): void
    {
        $this->repo->upsertLote([$this->camadaDoArquivoReal('a1')]);
        $this->repo->upsertLote([$this->camadaDoArquivoReal('b2')]);

        $this->assertSame(2, (int) DB::scalar('SELECT count(*) FROM silver.geo_camadas'));
    }

    public function test_cruzamento_conta_municipios_atingidos(): void
    {
        $this->repo->upsertLote([$this->camadaDoArquivoReal()]);
        DB::statement('REFRESH MATERIALIZED VIEW gold.geo_feicao_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW gold.geo_camada_municipios');

        $camadaId = (int) DB::scalar('SELECT id FROM silver.geo_camadas LIMIT 1');
        $cruzamento = $this->repo->cruzamento($camadaId);

        // Medido em 2026-09-03 contra a malha de municipios semeada.
        $this->assertSame(282, $cruzamento['municipios']);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: comando de teste com `--filter=GeoCamadaRepositoryTest`
Expected: FAIL — classe `CamadaGeoDTO` nao existe

- [ ] **Step 3: Escrever o DTO da camada**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

final readonly class CamadaGeoDTO
{
    /** @param list<FeicaoKmlDTO> $feicoes */
    public function __construct(
        public string $dominio,
        public string $nome,
        public string $arquivoNome,
        public ?string $emitidoEm,
        public ?string $validoAte,
        public ?string $nivel,
        public string $hashArquivo,
        public array $feicoes,
    ) {
    }
}
```

- [ ] **Step 4: Escrever o repositorio**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Repositories;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GeoCamadaRepository
{
    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * @param iterable<CamadaGeoDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $total = 0;

        foreach ($dtos as $dto) {
            $total += $this->gravarCamada($dto, $ingestaoId);
        }

        return $total;
    }

    private function gravarCamada(CamadaGeoDTO $dto, ?int $ingestaoId): int
    {
        // DO NOTHING e nao DO UPDATE: camada e imutavel. O mesmo arquivo
        // reenviado nao deve reescrever nem reimportar feicao -- e por isso que
        // hash_arquivo e unico.
        $id = DB::scalar(
            'INSERT INTO silver.geo_camadas
                (dominio, nome, arquivo_nome, emitido_em, valido_ate, nivel, hash_arquivo, ingestao_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), now())
             ON CONFLICT (hash_arquivo) DO NOTHING
             RETURNING id',
            [
                $dto->dominio, $dto->nome, $dto->arquivoNome,
                $dto->emitidoEm, $dto->validoAte, $dto->nivel,
                $dto->hashArquivo, $ingestaoId,
            ]
        );

        // null significa conflito: a camada ja existia e nada foi inserido.
        if ($id === null) {
            return 0;
        }

        foreach ($dto->feicoes as $feicao) {
            DB::statement(
                'INSERT INTO silver.geo_feicoes (camada_id, nome, propriedades, geom, created_at, updated_at)
                 VALUES (?, ?, ?::jsonb, ST_MakeValid(ST_Force2D(ST_GeomFromKML(?))), now(), now())',
                [(int) $id, $feicao->nome, '{}', $feicao->kmlGeometria]
            );
        }

        return count($dto->feicoes);
    }

    /** @return Collection<int, object> */
    public function camadas(): Collection
    {
        return DB::table('silver.geo_camadas')
            ->select(['id', 'dominio', 'nome', 'arquivo_nome', 'emitido_em', 'valido_ate', 'nivel', 'created_at'])
            ->orderByDesc('emitido_em')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Le a camada Gold. Nenhuma serializacao aqui: a matview ja entrega o
     * GeoJSON pronto.
     *
     * @return Collection<int, object>
     */
    public function mapa(?int $camadaId = null): Collection
    {
        $query = DB::table('gold.geo_feicao_mapa')
            ->select(['id', 'camada_id', 'dominio', 'camada_nome', 'nivel', 'emitido_em', 'feicao_nome', 'tipo_geometria', 'area_km2', 'geojson']);

        if ($camadaId !== null) {
            $query->where('camada_id', $camadaId);
        }

        return $query->orderByDesc('area_km2')->get();
    }

    /**
     * Cruzamento espacial da camada com o dado que o sistema ja tem.
     *
     * ATENCAO: 'municipios' vem de gold.geo_camada_municipios, que cruza por
     * CENTROIDE -- a tabela municipios nao tem geometria de area. O numero e
     * piso, nao total, e a tela precisa dizer isso.
     *
     * @return array{municipios: int, estacoes: int, chuva_media: float, chuva_maxima: float, estacoes_com_leitura: int}
     */
    public function cruzamento(int $camadaId): array
    {
        $municipios = (int) DB::scalar(
            'SELECT count(*) FROM gold.geo_camada_municipios WHERE camada_id = ?',
            [$camadaId]
        );

        $estacoes = (int) DB::scalar(
            'SELECT count(DISTINCT e.id)
               FROM silver.estacoes_cemaden e
               JOIN silver.geo_feicoes f ON ST_Contains(f.geom, e.geom)
              WHERE f.camada_id = ?',
            [$camadaId]
        );

        $chuva = DB::selectOne(
            'SELECT round(avg(g.acumulado_24h), 2) AS media,
                    max(g.acumulado_24h)           AS maxima,
                    count(*)                       AS com_leitura
               FROM gold.cemaden_mapa g
               JOIN silver.geo_feicoes f ON ST_Contains(f.geom, g.geom)
              WHERE f.camada_id = ?
                AND g.acumulado_24h IS NOT NULL',
            [$camadaId]
        );

        return [
            'municipios' => $municipios,
            'estacoes' => $estacoes,
            'chuva_media' => (float) ($chuva->media ?? 0),
            'chuva_maxima' => (float) ($chuva->maxima ?? 0),
            'estacoes_com_leitura' => (int) ($chuva->com_leitura ?? 0),
        ];
    }
}
```

- [ ] **Step 5: Rodar e confirmar que passa**

Run: comando de teste com `--filter=GeoCamadaRepositoryTest`
Expected: PASS (5 testes)

Se `test_cruzamento_conta_municipios_atingidos` falhar por numero diferente de
282, NAO ajuste o teste antes de investigar: a malha de municipios da base de
teste pode nao estar semeada. Confirme com
`SELECT count(*) FROM municipios WHERE latitude IS NOT NULL` — deve dar 853 para
MG.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Geoespacial/ SDC/tests/Feature/Geoespacial/GeoCamadaRepositoryTest.php
git commit -m "✨ feat(geoespacial): repositorio com geometria no PostGIS e cruzamento espacial"
```

---

### Task 5: Normalizador, job do Gold e config

**Files:**
- Create: `SDC/app/Modules/Geoespacial/Normalizadores/GeoKmlNormalizador.php`
- Create: `SDC/app/Modules/Geoespacial/Jobs/AtualizarGoldGeoJob.php`
- Create: `SDC/app/Modules/Geoespacial/GeoespacialServiceProvider.php`
- Create: `SDC/config/geoespacial.php`
- Modify: `SDC/config/medalhao.php`
- Modify: `SDC/config/app.php`
- Test: `SDC/tests/Feature/Geoespacial/PipelineGeoespacialTest.php`

**Interfaces:**
- Consumes: `KmlExtrator`, `CamadaGeoDTO`, `GeoCamadaRepository`.
- Produces: `GeoKmlNormalizador` implementando `NormalizadorSilver`;
  `AtualizarGoldGeoJob`; `config('geoespacial.dominios')`.

O `NormalizarSilverJob` recebe apenas o `PayloadBruto`, entao os metadados que o
operador informa na tela (dominio, emissao, nivel) viajam **dentro do conteudo
do Bronze**, num envelope JSON:

```json
{"dominio":"geologico","nome":"...","arquivo_nome":"...","emitido_em":"2026-02-28",
 "valido_ate":null,"nivel":"moderado","kml":"<?xml ...>"}
```

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use App\Modules\Geoespacial\Jobs\AtualizarGoldGeoJob;
use App\Modules\Geoespacial\Normalizadores\GeoKmlNormalizador;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Events\GoldAtualizado;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PipelineGeoespacialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada geoespacial exige PostgreSQL e PostGIS.');
        }

        DB::statement('TRUNCATE silver.geo_camadas CASCADE');
    }

    private function envelope(): string
    {
        return json_encode([
            'dominio' => 'geologico',
            'nome' => 'ALERTA MODERADO2802',
            'arquivo_nome' => 'alerta-geologico.kml',
            'emitido_em' => '2026-02-28',
            'valido_ate' => null,
            'nivel' => 'moderado',
            'kml' => file_get_contents(base_path('tests/Fixtures/geoespacial/alerta-geologico.kml')),
        ]);
    }

    public function test_normalizador_devolve_uma_camada_com_seis_feicoes(): void
    {
        $dtos = iterator_to_array(
            app(GeoKmlNormalizador::class)->normalizar(
                new PayloadBruto($this->envelope(), 'geo-kml')
            )
        );

        $this->assertCount(1, $dtos);
        $this->assertCount(6, $dtos[0]->feicoes);
        $this->assertSame('geologico', $dtos[0]->dominio);
    }

    public function test_hash_do_envelope_e_o_hash_do_kml_e_nao_do_envelope(): void
    {
        // Se o hash fosse do envelope, mudar so o nivel na tela criaria camada
        // nova com a mesma geometria -- e o mapa mostraria a area duplicada.
        $kml = file_get_contents(base_path('tests/Fixtures/geoespacial/alerta-geologico.kml'));

        $dtos = iterator_to_array(
            app(GeoKmlNormalizador::class)->normalizar(
                new PayloadBruto($this->envelope(), 'geo-kml')
            )
        );

        $this->assertSame(hash('sha256', $kml), $dtos[0]->hashArquivo);
    }

    public function test_fonte_esta_registrada_como_push_no_grupo_geoespacial(): void
    {
        $registry = app(IngestorRegistry::class);

        $this->assertContains('geo-upload', $registry->chavesDoGrupo('geoespacial'));
        $this->assertInstanceOf(GeoKmlNormalizador::class, $registry->normalizador('geo-upload'));
    }

    public function test_config_do_kernel_aponta_para_o_modulo(): void
    {
        $this->assertSame(
            \App\Modules\Geoespacial\Repositories\GeoCamadaRepository::class,
            config('medalhao.persistidores.geoespacial')
        );
        $this->assertSame(
            AtualizarGoldGeoJob::class,
            config('medalhao.refresh_gold.geoespacial')
        );
    }

    public function test_job_do_gold_refaz_as_matviews_e_avisa(): void
    {
        Event::fake([GoldAtualizado::class]);

        app(\App\Modules\Geoespacial\Repositories\GeoCamadaRepository::class)->upsertLote(
            iterator_to_array(
                app(GeoKmlNormalizador::class)->normalizar(new PayloadBruto($this->envelope(), 'geo-kml'))
            )
        );

        (new AtualizarGoldGeoJob())->handle();

        $this->assertSame(6, (int) DB::scalar('SELECT count(*) FROM gold.geo_feicao_mapa'));
        Event::assertDispatched(GoldAtualizado::class);
    }

    public function test_geojson_do_gold_sai_pronto_para_o_mapa(): void
    {
        app(\App\Modules\Geoespacial\Repositories\GeoCamadaRepository::class)->upsertLote(
            iterator_to_array(
                app(GeoKmlNormalizador::class)->normalizar(new PayloadBruto($this->envelope(), 'geo-kml'))
            )
        );

        (new AtualizarGoldGeoJob())->handle();

        $geojson = DB::scalar('SELECT geojson FROM gold.geo_feicao_mapa LIMIT 1');
        $decodificado = json_decode((string) $geojson, true);

        $this->assertSame('Polygon', $decodificado['type']);
        $this->assertNotEmpty($decodificado['coordinates']);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: comando de teste com `--filter=PipelineGeoespacialTest`
Expected: FAIL — `GeoKmlNormalizador` nao existe

- [ ] **Step 3: Escrever o normalizador**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Normalizadores;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use RuntimeException;

final class GeoKmlNormalizador implements NormalizadorSilver
{
    public function __construct(
        private readonly KmlExtrator $extrator,
    ) {
    }

    public function normalizar(PayloadBruto $bruto): iterable
    {
        $envelope = json_decode($bruto->conteudo, true);

        if (! is_array($envelope) || ! isset($envelope['kml'])) {
            throw new RuntimeException('Envelope do upload sem a chave kml.');
        }

        $kml = (string) $envelope['kml'];

        yield new CamadaGeoDTO(
            dominio: (string) ($envelope['dominio'] ?? 'geologico'),
            nome: (string) ($envelope['nome'] ?? $this->extrator->nomeDoDocumento($kml) ?? 'Camada sem nome'),
            arquivoNome: (string) ($envelope['arquivo_nome'] ?? 'desconhecido.kml'),
            emitidoEm: $envelope['emitido_em'] ?? null,
            validoAte: $envelope['valido_ate'] ?? null,
            nivel: $envelope['nivel'] ?? null,
            // Hash do KML, nao do envelope: mudar o nivel na tela nao deve criar
            // camada nova com a mesma geometria, senao o mapa mostra a area
            // duplicada.
            hashArquivo: hash('sha256', $kml),
            feicoes: $this->extrator->feicoes($kml),
        );
    }
}
```

- [ ] **Step 4: Escrever o job do Gold**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Jobs;

use App\Modules\Medalhao\Events\GoldAtualizado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldGeoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('gold-geoespacial'))->expireAfter(900)];
    }

    public function handle(): void
    {
        // CONCURRENTLY exige o indice unico da migration e nao roda dentro de
        // transacao.
        //
        // A ordem importa: geo_camada_municipios cruza a geometria das feicoes,
        // e o mapa vem primeiro para as duas refletirem o mesmo estado.
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.geo_feicao_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.geo_camada_municipios');

        GoldAtualizado::dispatch('geoespacial');
    }
}
```

- [ ] **Step 5: Escrever a config dos dominios**

Arquivo `SDC/config/geoespacial.php`:

```php
<?php

declare(strict_types=1);

return [
    // Dominio novo entra aqui, sem migration: o que varia entre eles e legenda
    // e vocabulario, nao estrutura. A geometria e a mesma coluna.
    'dominios' => [
        'geologico' => [
            'rotulo' => 'Geologico',
            'cor' => '#b45309',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
        'hidro' => [
            'rotulo' => 'Hidrologico',
            'cor' => '#1d4ed8',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
        'meteorologico' => [
            'rotulo' => 'Meteorologico',
            'cor' => '#7c3aed',
            'niveis' => ['baixo', 'moderado', 'alto', 'muito_alto'],
        ],
    ],

    // Limite do upload, em KB, aplicado no FormRequest.
    'upload_max_kb' => (int) env('GEOESPACIAL_UPLOAD_MAX_KB', 20480),
];
```

- [ ] **Step 6: Escrever o provider**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial;

use App\Modules\Geoespacial\Normalizadores\GeoKmlNormalizador;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Support\ServiceProvider;

class GeoespacialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KmlExtrator::class);
        $this->app->singleton(GeoCamadaRepository::class);
    }

    public function boot(): void
    {
        // Fonte so-push: o conteudo chega por upload, entao nao ha ingestor a
        // registrar. Ver IngestorRegistry::registrarPush().
        $this->app->make(IngestorRegistry::class)->registrarPush(
            'geo-upload',
            'geoespacial',
            $this->app->make(GeoKmlNormalizador::class),
        );
    }
}
```

- [ ] **Step 7: Ligar no kernel e no app**

Em `SDC/config/medalhao.php`, adicionar apos a entrada `cemaden` em cada mapa:

```php
        'geoespacial' => \App\Modules\Geoespacial\Repositories\GeoCamadaRepository::class,
```

```php
        'geoespacial' => \App\Modules\Geoespacial\Jobs\AtualizarGoldGeoJob::class,
```

Em `SDC/config/app.php`, apos `CemadenServiceProvider::class`:

```php
        App\Modules\Geoespacial\GeoespacialServiceProvider::class,
```

- [ ] **Step 8: Regenerar autoload nos dois containers e rodar o teste**

```bash
docker exec newsdc_dev_app sh -c 'cd /var/www && composer dump-autoload'
docker exec newsdc_dev_queue sh -c 'cd /var/www && composer dump-autoload'
docker exec newsdc_dev_app sh -c 'cd /var/www && php artisan config:cache && php artisan octane:reload'
```

Run: comando de teste com `--filter=PipelineGeoespacialTest`
Expected: PASS (6 testes)

- [ ] **Step 9: Commit**

```bash
git add SDC/app/Modules/Geoespacial/ SDC/config/geoespacial.php SDC/config/medalhao.php \
        SDC/config/app.php SDC/tests/Feature/Geoespacial/PipelineGeoespacialTest.php
git commit -m "✨ feat(geoespacial): normalizador, gold e registro no kernel do medalhao"
```

---

### Task 6: Upload HTTP

**Files:**
- Create: `SDC/app/Modules/Geoespacial/Requests/SubirCamadaRequest.php`
- Create: `SDC/app/Modules/Geoespacial/Controllers/GeoUploadController.php`
- Create: `SDC/routes/modules/geoespacial.php`
- Modify: `SDC/routes/web.php`
- Test: `SDC/tests/Feature/Geoespacial/GeoUploadControllerTest.php`

**Interfaces:**
- Consumes: `KmlExtrator`, `IngestaoBruta`, `NormalizarSilverJob`.
- Produces: rotas `geoespacial.index` (GET) e `geoespacial.upload` (POST).

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use App\Models\User;
use App\Modules\Medalhao\Jobs\NormalizarSilverJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GeoUploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada geoespacial exige PostgreSQL e PostGIS.');
        }

        DB::statement('TRUNCATE silver.geo_camadas CASCADE');
        DB::statement("DELETE FROM bronze.ingestao_bruta WHERE fonte = 'geo-upload'");
    }

    private function arquivo(): UploadedFile
    {
        return new UploadedFile(
            base_path('tests/Fixtures/geoespacial/alerta-geologico.kml'),
            'alerta-geologico.kml',
            'application/vnd.google-earth.kml+xml',
            null,
            true
        );
    }

    public function test_upload_grava_bronze_e_despacha_o_job(): void
    {
        Bus::fake([NormalizarSilverJob::class]);

        $this->actingAs(User::factory()->create())
            ->post(route('geoespacial.upload'), [
                'arquivo' => $this->arquivo(),
                'dominio' => 'geologico',
                'nome' => 'ALERTA MODERADO2802',
                'emitido_em' => '2026-02-28',
                'nivel' => 'moderado',
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            (int) DB::scalar("SELECT count(*) FROM bronze.ingestao_bruta WHERE fonte = 'geo-upload'")
        );

        Bus::assertDispatched(NormalizarSilverJob::class);
    }

    public function test_o_request_nao_parseia_o_arquivo(): void
    {
        // O conteudo do Bronze tem de ser o envelope com o KML CRU. Se o
        // controller tivesse parseado, haveria geometria ou feicao aqui -- e o
        // parse teria acontecido dentro do Octane.
        Bus::fake([NormalizarSilverJob::class]);

        $this->actingAs(User::factory()->create())
            ->post(route('geoespacial.upload'), [
                'arquivo' => $this->arquivo(),
                'dominio' => 'geologico',
                'nome' => 'X',
                'emitido_em' => '2026-02-28',
                'nivel' => 'moderado',
            ]);

        $envelope = json_decode(
            (string) DB::scalar("SELECT conteudo_bruto FROM bronze.ingestao_bruta WHERE fonte = 'geo-upload' LIMIT 1"),
            true
        );

        $this->assertArrayHasKey('kml', $envelope);
        $this->assertStringContainsString('<Placemark', $envelope['kml']);
        $this->assertSame(0, (int) DB::scalar('SELECT count(*) FROM silver.geo_feicoes'));
    }

    public function test_recusa_arquivo_que_nao_e_kml_nem_kmz(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('geoespacial.upload'), [
                'arquivo' => UploadedFile::fake()->create('planilha.xlsx', 10),
                'dominio' => 'geologico',
                'nome' => 'X',
                'emitido_em' => '2026-02-28',
                'nivel' => 'moderado',
            ])
            ->assertSessionHasErrors('arquivo');

        $this->assertSame(
            0,
            (int) DB::scalar("SELECT count(*) FROM bronze.ingestao_bruta WHERE fonte = 'geo-upload'")
        );
    }

    public function test_recusa_dominio_fora_da_config(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('geoespacial.upload'), [
                'arquivo' => $this->arquivo(),
                'dominio' => 'inventado',
                'nome' => 'X',
                'emitido_em' => '2026-02-28',
                'nivel' => 'moderado',
            ])
            ->assertSessionHasErrors('dominio');
    }

    public function test_exige_autenticacao(): void
    {
        $this->post(route('geoespacial.upload'), [])->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: comando de teste com `--filter=GeoUploadControllerTest`
Expected: FAIL — rota `geoespacial.upload` nao definida

- [ ] **Step 3: Escrever o FormRequest**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirCamadaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // A validacao de tipo vem antes de qualquer leitura de conteudo: e
            // a primeira barreira da superficie de ataque.
            'arquivo' => [
                'required', 'file',
                'max:' . (int) config('geoespacial.upload_max_kb'),
                'extensions:kml,kmz',
            ],
            'dominio' => ['required', Rule::in(array_keys((array) config('geoespacial.dominios')))],
            'nome' => ['required', 'string', 'max:255'],
            // Emissao, validade e nivel NAO existem dentro do KML -- so no nome
            // do arquivo. Extrair de nome de arquivo externo e contrato que
            // ninguem garante, entao o operador informa.
            'emitido_em' => ['required', 'date'],
            'valido_ate' => ['nullable', 'date', 'after_or_equal:emitido_em'],
            'nivel' => ['required', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.extensions' => 'O arquivo precisa ser .kml ou .kmz.',
            'dominio.in' => 'Dominio desconhecido.',
        ];
    }
}
```

- [ ] **Step 4: Escrever o controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Requests\SubirCamadaRequest;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Jobs\NormalizarSilverJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeoUploadController extends Controller
{
    public function __construct(
        private readonly GeoCamadaRepository $repository,
        private readonly KmlExtrator $extrator,
    ) {
    }

    public function index(Request $request): Response
    {
        $camadaId = $request->integer('camada') ?: null;

        return Inertia::render('Geoespacial/Camadas', [
            'camadas' => $this->repository->camadas()->all(),
            'feicoes' => $this->repository->mapa($camadaId)->all(),
            'cruzamento' => $camadaId !== null ? $this->repository->cruzamento($camadaId) : null,
            'camadaSelecionada' => $camadaId,
            'dominios' => config('geoespacial.dominios'),
            'bbox' => config('medalhao.inmet.bbox'),
        ]);
    }

    /**
     * O request faz tres coisas e nenhuma delas e parse: valida, grava o cru no
     * Bronze, despacha job. O ZIP so e aberto no worker da fila -- e por isso
     * que o Octane nao sente o upload.
     *
     * A unica leitura aqui e conteudoDeArquivo(), que para KMZ abre o ZIP. E o
     * minimo necessario para guardar o KML e nao o container, e ja carrega as
     * guardas de tamanho.
     */
    public function upload(SubirCamadaRequest $request): RedirectResponse
    {
        $arquivo = $request->file('arquivo');
        $kml = $this->extrator->conteudoDeArquivo($arquivo->getRealPath());

        $envelope = json_encode([
            'dominio' => $request->string('dominio')->toString(),
            'nome' => $request->string('nome')->toString(),
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'emitido_em' => $request->date('emitido_em')?->toDateString(),
            'valido_ate' => $request->date('valido_ate')?->toDateString(),
            'nivel' => $request->string('nivel')->toString(),
            'kml' => $kml,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $bronze = IngestaoBruta::create([
            'fonte' => 'geo-upload',
            'conteudo_bruto' => $envelope,
            'formato' => 'geo-kml',
            'hash_conteudo' => hash('sha256', $envelope),
            'meta' => [
                'arquivo_nome' => $arquivo->getClientOriginalName(),
                'bytes' => $arquivo->getSize(),
                'usuario_id' => $request->user()?->id,
            ],
            'coletado_em' => now(),
            'verificado_em' => now(),
        ]);

        NormalizarSilverJob::dispatch((int) $bronze->id, 'geo-upload');

        return back()->with('sucesso', 'Camada enviada. O processamento acontece em segundo plano.');
    }
}
```

- [ ] **Step 5: Escrever as rotas**

Arquivo `SDC/routes/modules/geoespacial.php`:

```php
<?php

use App\Modules\Geoespacial\Controllers\GeoUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('geoespacial')->name('geoespacial.')->group(function () {
    Route::get('/', [GeoUploadController::class, 'index'])->name('index');
    Route::post('/', [GeoUploadController::class, 'upload'])->name('upload');
});
```

Em `SDC/routes/web.php`, apos `require __DIR__ . '/modules/cemaden.php';` (se
existir) ou apos a linha do `inmet.php`:

```php
    require __DIR__ . '/modules/geoespacial.php';
```

- [ ] **Step 6: Regenerar autoload, rotas e ziggy**

```bash
docker exec newsdc_dev_app sh -c 'cd /var/www && composer dump-autoload'
docker exec newsdc_dev_queue sh -c 'cd /var/www && composer dump-autoload'
docker exec newsdc_dev_app sh -c 'cd /var/www && php artisan route:clear && php artisan route:cache && php artisan ziggy:generate && php artisan octane:reload'
```

A ordem importa: `ziggy:generate` le a lista de rotas, entao rodar antes do
`route:cache` gera o arquivo com o cache velho e a rota nova nao aparece no
front.

Run: comando de teste com `--filter=GeoUploadControllerTest`
Expected: PASS (5 testes)

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/Geoespacial/ SDC/routes/modules/geoespacial.php SDC/routes/web.php \
        SDC/resources/js/ziggy.js SDC/tests/Feature/Geoespacial/GeoUploadControllerTest.php
git commit -m "✨ feat(geoespacial): upload de KML/KMZ sem parse dentro do Octane"
```

---

### Task 7: MapaLeaflet aceita poligonos

**Files:**
- Modify: `SDC/resources/js/Components/Mapa/MapaLeaflet.vue`

**Interfaces:**
- Consumes: nada.
- Produces: prop nova `poligonos`, no formato
  `[{ id, geojson, cor, rotulo }]`, onde `geojson` e objeto GeoJSON (nao string).

- [ ] **Step 1: Adicionar a prop**

Depois de `pontos` em `defineProps`:

```js
  /**
   * [{ id, geojson, cor, rotulo }] — geojson e OBJETO, nao string: vem do
   * ST_AsGeoJSON(...)::jsonb, que o Postgres ja entrega decodificado.
   */
  poligonos: { type: Array, default: () => [] },
```

- [ ] **Step 2: Criar a camada propria, abaixo da de pontos**

Ao lado de `let camada = null;`:

```js
// Camada separada e criada ANTES da de pontos: no Leaflet a ordem de adicao
// define o empilhamento, e area de alerta desenhada por cima esconderia os
// pontos de chuva -- que sao o dado que o operador precisa ver dentro dela.
let camadaPoligonos = null;
```

- [ ] **Step 3: Desenhar os poligonos**

No inicio de `desenhar()`, antes do bloco que trata `camada`:

```js
  if (camadaPoligonos) {
    camadaPoligonos.clearLayers();
  } else {
    camadaPoligonos = L.layerGroup().addTo(mapa);
  }

  props.poligonos.forEach((poligono) => {
    if (!poligono.geojson) {
      return;
    }

    const cor = poligono.cor ?? '#b45309';

    const camadaGeo = L.geoJSON(poligono.geojson, {
      style: {
        color: cor,
        weight: 2,
        // Preenchimento fraco de proposito: a area e recorte, nao dado. Opaca
        // demais e ela compete com os pontos que estao dentro dela.
        fillColor: cor,
        fillOpacity: 0.12,
      },
    });

    if (poligono.rotulo) {
      camadaGeo.bindPopup(escapar(poligono.rotulo));
    }

    camadaGeo.addTo(camadaPoligonos);
  });
```

- [ ] **Step 4: Redesenhar quando a prop mudar**

Localize o `watch` que hoje observa `props.pontos` e inclua `props.poligonos` na
lista observada, mantendo o mesmo callback `desenhar`.

- [ ] **Step 5: Buildar e conferir que nada quebrou**

```bash
cd SDC && npx vite build
```

Expected: build sem erro, e os chunks `MapaInmet` e `MapaSismos` regerados.

- [ ] **Step 6: Conferir que as telas existentes seguem funcionando**

Abra `/inmet` e `/sismos`. Os pontos devem continuar aparecendo e a busca por
municipio deve continuar centralizando — `focarPonto()` usa `marcadoresPorId`,
que nao foi tocado.

- [ ] **Step 7: Commit**

```bash
git add SDC/resources/js/Components/Mapa/MapaLeaflet.vue
git commit -m "✨ feat(mapa): MapaLeaflet desenha poligonos abaixo dos pontos"
```

---

### Task 8: Pagina Geoespacial

**Files:**
- Create: `SDC/resources/js/Pages/Geoespacial/Camadas.vue`
- Modify: `SDC/resources/js/Components/Sidebar.vue`

**Interfaces:**
- Consumes: props de `GeoUploadController::index` (`camadas`, `feicoes`,
  `cruzamento`, `camadaSelecionada`, `dominios`, `bbox`); `MapaLeaflet` com
  `poligonos` (Task 7).
- Produces: nada para tasks seguintes.

- [ ] **Step 1: Escrever a pagina**

Estrutura, seguindo o padrao de `Pages/Sismos/MapaSismos.vue`:

- `<script setup>` com `defineOptions({ layout: AuthenticatedLayout })`
- `useForm` do Inertia para o upload (`arquivo`, `dominio`, `nome`,
  `emitido_em`, `valido_ate`, `nivel`), com `forceFormData: true` — sem isso o
  arquivo nao sobe
- `useAtualizacaoAoVivo({ canal: 'medalhao.geoespacial', evento: '.GoldAtualizado', props: ['camadas', 'feicoes', 'cruzamento'] })`
- Lista de camadas; clicar seleciona via `router.get(route('geoespacial.index'), { camada: id }, { preserveState: true, preserveScroll: true })`
- `MapaLeaflet` recebendo `:poligonos="poligonosDoMapa"`, computado de `feicoes`:

```js
const poligonosDoMapa = computed(() => props.feicoes.map((feicao) => ({
  id: feicao.id,
  geojson: feicao.geojson,
  cor: props.dominios[feicao.dominio]?.cor ?? '#b45309',
  rotulo: `${feicao.camada_nome} — ${feicao.area_km2} km2`,
})));
```

- Painel de cruzamento, com a ressalva visivel:

```html
<div v-if="cruzamento" class="cruzamento">
  <div class="cruzamento-linha">
    <span>Municipios atingidos</span>
    <strong>{{ cruzamento.municipios }}</strong>
  </div>
  <!--
    A ressalva e obrigatoria: gold.geo_camada_municipios cruza por CENTROIDE,
    porque a tabela municipios nao tem geometria de area. Municipio cujo
    centroide cai fora mas cujo territorio e atingido nao entra na conta.
  -->
  <p class="cruzamento-nota">
    Contagem por centroide do municipio: e piso, nao total.
  </p>
  <div class="cruzamento-linha">
    <span>Estacoes na area</span>
    <strong>{{ cruzamento.estacoes }}</strong>
  </div>
  <div class="cruzamento-linha">
    <span>Chuva 24h na area</span>
    <strong>{{ cruzamento.chuva_media }} mm (max {{ cruzamento.chuva_maxima }} mm)</strong>
  </div>
  <p class="cruzamento-nota">
    De {{ cruzamento.estacoes_com_leitura }} estacoes com leitura.
  </p>
</div>
```

- Estilos: tokens no container e bloco `<style>` NAO-scoped para
  `.dark .geoespacial-container`, seguindo o padrao das outras duas telas.

- [ ] **Step 2: Adicionar ao menu**

Em `Sidebar.vue`, apos o item `Meteorologia`:

```html
        <!-- Camadas geoespaciais -->
        <NavItem
          v-if="canSeeMeteorologia && _routes.hasGeoespacial"
          :href="route('geoespacial.index', undefined, false)"
          :active="isRouteActive('geoespacial.*')"
          icon="map"
          :collapsed="isCollapsed"
        >
          Camadas de Risco
        </NavItem>
```

E no objeto `_routes`:

```js
  hasGeoespacial: route().has('geoespacial.index'),
```

Se o icone `map` nao existir no `NavItem`, use `cloud`, que ja e usado pelas
outras telas do medalhao.

- [ ] **Step 3: Registrar o canal de broadcast**

Em `SDC/routes/channels.php` nao e necessario nada novo: o canal
`medalhao.{grupo}` ja e parametrizado e cobre `medalhao.geoespacial`.

Confirme com:

```bash
grep -n "medalhao" SDC/routes/channels.php
```

- [ ] **Step 4: Buildar**

```bash
cd SDC && npx vite build
```

Expected: build sem erro e chunk `Camadas` gerado.

- [ ] **Step 5: Verificar o CSS compilado**

```bash
grep -o "\.dark[^{]*geoespacial-container[^{]*{[^}]*}" SDC/public/build/assets/Camadas-*.css | head -3
grep -oE "^\.dark\{[^}]*\}" SDC/public/build/assets/Camadas-*.css | head -2
```

Expected: primeiro comando mostra seletor completo; o segundo NAO retorna nada.
`.dark` pelado significa que a regra escura esta pintando o `<html>` inteiro.

- [ ] **Step 6: Teste manual do caminho completo**

Suba `.claude/ALERTA-RISCO-GEOLOGICO-MODERADO-28022026.kml` pela tela, com
dominio `geologico`, emissao `2026-02-28` e nivel `moderado`. Confira nos logs
da fila que o pipeline correu:

```bash
docker logs newsdc_dev_queue --since 2m 2>&1 | grep -iE "NormalizarSilver|AtualizarGoldGeo|GoldAtualizado|error"
```

Expected: os tres jobs em DONE, sem erro. Na tela: 6 areas no mapa, 282
municipios, e chuva na area com numero de estacoes.

- [ ] **Step 7: Commit**

```bash
git add SDC/resources/js/Pages/Geoespacial/ SDC/resources/js/Components/Sidebar.vue \
        SDC/resources/js/ziggy.js
git commit -m "✨ feat(geoespacial): tela de camadas com upload, mapa e cruzamento"
```

---

### Task 9: Camada no mapa da Meteorologia

**Files:**
- Modify: `SDC/app/Modules/Inmet/Controllers/InmetIndexController.php`
- Modify: `SDC/resources/js/Pages/Inmet/MapaInmet.vue`
- Test: `SDC/tests/Feature/Geoespacial/CamadaNaMeteorologiaTest.php`

**Interfaces:**
- Consumes: `GeoCamadaRepository::mapa()`, `camadas()`.
- Produces: nada.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Geoespacial;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class CamadaNaMeteorologiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Camada geoespacial exige PostgreSQL e PostGIS.');
        }
    }

    public function test_meteorologia_recebe_as_camadas_disponiveis(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inmet.index'))
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->component('Inmet/MapaInmet')
                ->has('camadasGeo')
            );
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

Run: comando de teste com `--filter=CamadaNaMeteorologiaTest`
Expected: FAIL — prop `camadasGeo` ausente

- [ ] **Step 3: Passar as camadas no controller**

Adicionar ao construtor, junto dos que ja existem:

```php
        private readonly GeoCamadaRepository $geoespacial,
```

E no `__invoke`, antes do `Inertia::render`:

```php
        $camadaGeoId = $request->integer('camada_geo') ?: null;
```

E dentro do array do `Inertia::render`:

```php
            // Lista enxuta: so o que o seletor precisa. As feicoes da camada
            // escolhida vem por partial reload, e nao todas de uma vez -- com
            // varias camadas carregadas, mandar toda geometria seria payload
            // morto para o operador que olha uma.
            'camadasGeo' => $this->geoespacial->camadas()->all(),
            'feicoesGeo' => $camadaGeoId !== null ? $this->geoespacial->mapa($camadaGeoId)->all() : [],
```

- [ ] **Step 4: Ligar o seletor na tela**

Em `MapaInmet.vue`, adicionar as props:

```js
  camadasGeo: { type: Array, default: () => [] },
  feicoesGeo: { type: Array, default: () => [] },
```

O seletor, ao lado dos chips de rede:

```html
      <div class="rede-filtro">
        <select v-model="camadaGeoSelecionada" class="camada-select" @change="trocarCamadaGeo">
          <option :value="null">Sem camada de risco</option>
          <option v-for="camada in camadasGeo" :key="camada.id" :value="camada.id">
            {{ camada.nome }} ({{ camada.nivel }})
          </option>
        </select>
      </div>
```

E o script:

```js
import { router } from '@inertiajs/vue3';

const camadaGeoSelecionada = ref(null);

/*
 * only: ['feicoesGeo'] e o que evita rebuscar as 890 estacoes a cada troca de
 * camada. preserveState mantem a pagina da tabela e o filtro de rede, que o
 * operador nao mexeu.
 */
function trocarCamadaGeo() {
  router.get(
    route('inmet.index'),
    { camada_geo: camadaGeoSelecionada.value },
    { only: ['feicoesGeo'], preserveState: true, preserveScroll: true },
  );
}

const poligonosGeo = computed(() => props.feicoesGeo.map((feicao) => ({
  id: feicao.id,
  geojson: feicao.geojson,
  cor: '#b45309',
  rotulo: `${feicao.camada_nome} — ${feicao.area_km2} km2`,
})));
```

E no `MapaLeaflet`, acrescentar `:poligonos="poligonosGeo"` ao lado do
`:pontos` que ja existe.

O `.camada-select` precisa de estilo usando os tokens do container
(`var(--sup)`, `var(--borda)`, `var(--texto)`), como `.rede-chip` faz — sem
isso ele nasce branco no tema escuro.

- [ ] **Step 5: Rodar o teste e buildar**

Run: comando de teste com `--filter=CamadaNaMeteorologiaTest`
Expected: PASS

```bash
cd SDC && npx vite build
```

- [ ] **Step 6: Rodar a suite geoespacial inteira**

Run: comando de teste com `--filter=Geoespacial`
Expected: todos passando

- [ ] **Step 7: Verificacao final contra os criterios da spec**

Confira os 6 criterios da secao 6 da spec, um a um, e registre o resultado real
de cada um. Nao marque como feito criterio que nao foi exercitado.

- [ ] **Step 8: Commit**

```bash
git add SDC/app/Modules/Inmet/Controllers/InmetIndexController.php \
        SDC/resources/js/Pages/Inmet/MapaInmet.vue \
        SDC/tests/Feature/Geoespacial/CamadaNaMeteorologiaTest.php
git commit -m "✨ feat(geoespacial): area de risco como camada no mapa da Meteorologia"
```
