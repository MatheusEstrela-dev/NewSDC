# Ajuda Humanitaria (MAH) - Fase 2a: Persistencia - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar implementacao real aos quatro contratos de repositorio criados na fase 1, incluindo a ponte somente-leitura para o estoque legado, e registra-los no container.

**Architecture:** As implementacoes Eloquent ficam em `Infrastructure/Persistence`, atras das interfaces de `Domain/Repositories`. O dominio continua sem conhecer Eloquent. A leitura de saldo usa a conexao `legacy` (MySQL) e degrada silenciosamente quando indisponivel, em vez de estourar.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL (default), MySQL (legado), PHPUnit 11.

## Contexto: o que a fase 1 deixou pronto

Ja existem e estao commitados:

- `Domain/Repositories/{PedidoAh,PrestacaoConta,MaterialAh,SaldoMaterial}RepositoryInterface.php`
- `Domain/PedidoAhWorkflow.php`, `Domain/Guards/` (quatro guardas), `Domain/Specifications/` (tres)
- `Enums/` (oito), `Models/` (dez), migration com as dez tabelas ja aplicada no banco de desenvolvimento
- `AjudaHumanitariaServiceProvider` registrando `PedidoAhWorkflow` como singleton

Esta fase **nao** cria services, controllers, rotas nem telas. Isso e a fase 2b (servicos) e 2c (HTTP).

## Global Constraints

- Todo arquivo PHP novo comeca com `<?php`, linha em branco, `declare(strict_types=1);`
- Namespace raiz do modulo: `App\Modules\AjudaHumanitaria`
- Proibido emoji em codigo. Proibido acento em nome de classe, metodo, propriedade, arquivo ou coluna; acento apenas em string de exibicao
- **Arquivos de teste nao entram em commit.** Regra permanente do usuario: os testes sao escritos, executados e ficam no disco sem versionamento
- Nada sob `Domain/` pode importar `Illuminate\*` nem `App\Models\*`. As implementacoes vao em `Infrastructure/Persistence`, onde Eloquent e permitido
- Testes com banco usam `Illuminate\Foundation\Testing\DatabaseTransactions`, nunca `RefreshDatabase` (as migrations do projeto nao rodam em SQLite e `RefreshDatabase` apagaria o banco de desenvolvimento)
- Em teste que precise de municipio, reaproveitar um existente com `DB::table('municipios')->value('id')`; nao inserir id fixo em tabela de referencia compartilhada
- Nunca rodar `migrate:fresh`, `migrate:refresh` nem `db:wipe`
- Commits em gitmoji, escopo `ajuda-humanitaria`, sem trailer `Co-Authored-By`

### Runner de teste

Salve o bloco abaixo como `testar.ps1` fora do repositorio. Nos passos, `TESTAR` designa esse script.

```powershell
$php = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
$ext = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\ext"
Set-Location "C:\Users\x24679188\Documents\Github\NewSDC\SDC"
$dot = @{}
Get-Content .env | Where-Object { $_ -match '^\s*DB_(USERNAME|PASSWORD|DATABASE)\s*=' } | ForEach-Object {
    $par = $_ -split '=', 2
    $dot[$par[0].Trim()] = $par[1].Trim().Trim('"')
}
$env:APP_CONFIG_CACHE = "$env:TEMP\sem-cache-newsdc.php"
$env:DB_CONNECTION = "pgsql"; $env:DB_HOST = "127.0.0.1"; $env:DB_PORT = "5434"
$env:DB_DATABASE = $dot['DB_DATABASE']
$env:DB_USERNAME = $dot['DB_USERNAME']
$env:DB_PASSWORD = $dot['DB_PASSWORD']
& $php -d "extension_dir=$ext" -d "extension=php_pgsql.dll" -d "extension=php_pdo_pgsql.dll" `
    vendor/bin/phpunit @args
```

`APP_CONFIG_CACHE` aponta para arquivo inexistente de proposito: `bootstrap/cache/config.php` esta cacheado com `host=db, port=5432`, nomes internos do container, e config cacheado ignora variaveis de ambiente. Nao rodar `artisan config:clear`: o cache e compartilhado com o container em execucao.

**Linha de base antes desta fase: 194 testes, 602 assercoes, 1 erro e 4 falhas**, todas pre-existentes em `PaeFormularioControllerTest`, `ProcessoStoreFlashTest` e `PlanConUploadTest` (3). Qualquer falha alem dessas cinco e regressao.

## O banco legado

Levantado por inspecao direta em 2026-08-06. MySQL local, porta 3306, usuario `root` sem senha no ambiente de desenvolvimento. Duas bases contem tabelas `aju_`:

| Base | Papel |
| --- | --- |
| `gestaocedec_local` | Sistema procedural vigente. 1.324 pedidos, 98.558 beneficiarios, 5.709 linhas de estoque. **E a fonte.** |
| `dbsdc` | Port Laravel. 3 pedidos de teste. Irrelevante. |

Schema real das tres tabelas que a ponte le:

```
aju_estoque    id_estoque(PK) id_produto id_deposito saldo         5.709 linhas
aju_deposito   id_deposito(PK) nome endereco regiao id_rpm abreviacao   24 linhas
aju_unidade    id_unidade(PK) nome descricao pedido_h novo complnota
               record_time uni_medida peso valor singular categoria
               singular_id                                          236 linhas
```

`aju_unidade.pedido_h = 1` marca os materiais liberados para pedido (RN-07). Hoje sao **nove**: BALDE, CESTA BASICA, COLCHAO, KIT HIGIENE, KIT LIMPEZA, KIT DORMITORIO, LONA, ROUPA, TELHA. O port Laravel havia congelado quatro deles em codigo.

Consulta da ponte, ja validada contra a base real:

```sql
SELECT d.nome AS deposito, u.singular AS material, SUM(e.saldo) AS saldo
FROM aju_estoque e
JOIN aju_deposito d ON e.id_deposito = d.id_deposito
JOIN aju_unidade  u ON e.id_produto  = u.id_unidade
WHERE e.saldo <> 0
GROUP BY d.id_deposito, d.nome, u.singular
```

**A conexao `legacy` nao esta configurada.** As chaves `DB_LEGACY_*` nao existem em `.env`, `.env.local`, `.env.testing` nem `.env.example`; so em `.env.prod`, e la `DB_LEGACY_PASSWORD` esta vazia. Sem elas a conexao cai no fallback `DB_HOST` com driver mysql e database vazio. A Task 1 documenta as chaves e a Task 5 garante degradacao limpa enquanto nao forem preenchidas.

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `config/ajuda-humanitaria.php` | Disco de anexos, limites de upload, nome da conexao legada, TTL do cache de saldo |
| `.env.example` | Documenta as cinco chaves `DB_LEGACY_*` |
| `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPedidoAhRepository.php` | RN-01, RN-03, RN-08, RN-11, RN-14 |
| `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPrestacaoContaRepository.php` | RN-15, RN-16, RN-18, RN-19 |
| `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentMaterialAhRepository.php` | RN-07 |
| `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/LegadoSaldoMaterialRepository.php` | RN-25 |
| `app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php` | **modificado**: remove `temAgendamentoAprovado` |
| `app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php` | **modificado**: quatro binds novos |

---

### Task 1: Configuracao do modulo

**Files:**
- Create: `config/ajuda-humanitaria.php`
- Modify: `.env.example`
- Test: `tests/Feature/AjudaHumanitaria/ConfigMahTest.php`

**Interfaces:**
- Consumes: nada
- Produces: chaves `ajuda-humanitaria.disk`, `ajuda-humanitaria.upload_limits.anexo_pedido`, `ajuda-humanitaria.legacy_connection`, `ajuda-humanitaria.saldo_cache_ttl`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/ConfigMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use Tests\TestCase;

final class ConfigMahTest extends TestCase
{
    public function test_config_do_modulo_existe_com_as_chaves_previstas(): void
    {
        $this->assertIsString(config('ajuda-humanitaria.disk'));
        $this->assertIsInt(config('ajuda-humanitaria.upload_limits.anexo_pedido'));
        $this->assertIsString(config('ajuda-humanitaria.legacy_connection'));
        $this->assertIsInt(config('ajuda-humanitaria.saldo_cache_ttl'));
    }

    public function test_limite_de_anexo_reproduz_o_legado(): void
    {
        $this->assertSame(
            2 * 1024 * 1024,
            config('ajuda-humanitaria.upload_limits.anexo_pedido'),
            'RN-22: o legado aceita PDF de ate 2 MB.'
        );
    }

    public function test_env_example_documenta_a_conexao_legada(): void
    {
        $exemplo = (string) file_get_contents(base_path('.env.example'));

        foreach (['DB_LEGACY_HOST', 'DB_LEGACY_PORT', 'DB_LEGACY_DATABASE', 'DB_LEGACY_USERNAME', 'DB_LEGACY_PASSWORD'] as $chave) {
            $this->assertStringContainsString($chave, $exemplo, "{$chave} deve estar documentada.");
        }
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=ConfigMahTest
```
Esperado: FAIL, `Failed asserting that null is of type string`.

- [ ] **Step 3: Criar o config**

`config/ajuda-humanitaria.php`:

```php
<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de anexos
    |--------------------------------------------------------------------------
    | Onde os PDFs anexados ao pedido sao gravados (RN-22). Mesmo padrao do
    | modulo Compdec, que usa disco proprio por modulo.
    */
    'disk' => env('AJUDA_HUMANITARIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Limites de upload, em bytes
    |--------------------------------------------------------------------------
    | RN-22: o legado aceita apenas PDF, no maximo 2 MB.
    */
    'upload_limits' => [
        'anexo_pedido' => 2 * 1024 * 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conexao com a base legada
    |--------------------------------------------------------------------------
    | Usada somente para leitura de saldo de material por deposito (RN-25).
    | Aponta para a base do sistema procedural (gestaocedec), nao para o port
    | Laravel. Enquanto as chaves DB_LEGACY_* nao estiverem preenchidas no
    | .env, a ponte reporta saldo indisponivel em vez de estourar.
    */
    'legacy_connection' => env('AJUDA_HUMANITARIA_LEGACY_CONNECTION', 'legacy'),

    /*
    |--------------------------------------------------------------------------
    | Cache do saldo legado, em segundos
    |--------------------------------------------------------------------------
    | O saldo muda com baixa frequencia e a consulta cruza tres tabelas em
    | outro banco. Cache curto evita martelar a base legada a cada tela.
    */
    'saldo_cache_ttl' => (int) env('AJUDA_HUMANITARIA_SALDO_CACHE_TTL', 300),

];
```

- [ ] **Step 4: Documentar as chaves no `.env.example`**

Acrescentar ao final de `.env.example`:

```
# Base legada do sistema procedural (gestaocedec), somente leitura.
# Usada pelo modulo Ajuda Humanitaria para consultar saldo de material por
# deposito. Sem estas chaves o modulo reporta saldo indisponivel, sem quebrar.
DB_LEGACY_HOST=127.0.0.1
DB_LEGACY_PORT=3306
DB_LEGACY_DATABASE=gestaocedec_local
DB_LEGACY_USERNAME=
DB_LEGACY_PASSWORD=

AJUDA_HUMANITARIA_DISK=local
AJUDA_HUMANITARIA_LEGACY_CONNECTION=legacy
AJUDA_HUMANITARIA_SALDO_CACHE_TTL=300
```

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=ConfigMahTest
```
Esperado: PASS, 3 testes.

- [ ] **Step 6: Commit**

```bash
git add SDC/config/ajuda-humanitaria.php SDC/.env.example
git commit -F <arquivo com a mensagem>
```

Mensagem:
```
🔧 config(ajuda-humanitaria): configuracao do modulo MAH

Disco e limite de anexo reproduzindo a RN-22 do legado, nome da conexao
legada e TTL do cache de saldo.

Documenta no .env.example as cinco chaves DB_LEGACY_*, que nao existiam em
nenhum .env versionado apesar de a conexao legacy estar declarada em
config/database.php desde antes.
```

---

### Task 2: EloquentPedidoAhRepository

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPedidoAhRepository.php`
- Modify: `app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php`
- Test: `tests/Feature/AjudaHumanitaria/EloquentPedidoAhRepositoryTest.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/RepositoryContractsTest.php` (ja existe, ajustar)

**Interfaces:**
- Consumes: `PedidoAhRepositoryInterface`, models `PedidoAh`, `PedidoAhItem`, `PedidoAhParecer`, `PedidoAhTramite`, enums `StatusPedidoAh`, `TipoItemPedido`, `SituacaoParecer`
- Produces: `EloquentPedidoAhRepository` implementando os **seis** metodos do contrato

A modificacao do contrato: remover `temAgendamentoAprovado(int $pedidoId): bool`. Ele existia para alimentar a guarda de agendamento, removida ao se descobrir que `aju_h_agendamento` nao existe no legado. Sem consumidor, o metodo sai — o teste de contrato verifica lista exata de metodos.

- [ ] **Step 1: Ajustar o teste de contrato**

Em `tests/Unit/AjudaHumanitaria/Domain/RepositoryContractsTest.php`, no `contratoProvider`, trocar o bloco `'pedido'` por:

```php
            'pedido' => [PedidoAhRepositoryInterface::class, [
                'proximoNumeroDoAno',
                'municipioTemPedidoEmEdicao',
                'contarItensPorTipo',
                'temParecerFavoravel',
                'atualizarStatus',
                'registrarTramite',
            ]],
```

- [ ] **Step 2: Escrever o teste da implementacao**

Criar `tests/Feature/AjudaHumanitaria/EloquentPedidoAhRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EloquentPedidoAhRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PedidoAhRepositoryInterface $repo;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = app(PedidoAhRepositoryInterface::class);

        $id = DB::table('municipios')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }
        $this->municipioId = (int) $id;
    }

    private function criarPedido(
        int $numero = 990_001,
        int $ano = 2097,
        StatusPedidoAh $status = StatusPedidoAh::EdicaoCompdec,
    ): PedidoAh {
        return PedidoAh::create([
            'numero' => $numero,
            'ano' => $ano,
            'municipio_id' => $this->municipioId,
            'pop_atendida' => 500,
            'esforcos_realizados' => 'Teste de repositorio.',
            'status' => $status,
            'data_entrada_sistema' => now(),
        ]);
    }

    public function test_container_resolve_a_implementacao_eloquent(): void
    {
        $this->assertInstanceOf(
            \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPedidoAhRepository::class,
            $this->repo,
        );
    }

    public function test_primeiro_numero_de_um_ano_virgem_e_um(): void
    {
        $this->assertSame(1, $this->repo->proximoNumeroDoAno(2095));
    }

    public function test_proximo_numero_sucede_o_maior_do_ano(): void
    {
        $this->criarPedido(numero: 7, ano: 2096);
        $this->criarPedido(numero: 3, ano: 2096);

        $this->assertSame(8, $this->repo->proximoNumeroDoAno(2096));
    }

    public function test_proximo_numero_ignora_outros_anos(): void
    {
        $this->criarPedido(numero: 500, ano: 2094);

        $this->assertSame(1, $this->repo->proximoNumeroDoAno(2095));
    }

    public function test_detecta_municipio_com_pedido_em_edicao(): void
    {
        $this->assertFalse($this->repo->municipioTemPedidoEmEdicao($this->municipioId));

        $this->criarPedido(status: StatusPedidoAh::EdicaoCompdec);

        $this->assertTrue($this->repo->municipioTemPedidoEmEdicao($this->municipioId));
    }

    public function test_pedido_em_analise_nao_conta_como_em_edicao(): void
    {
        $this->criarPedido(status: StatusPedidoAh::AnaliseDlog);

        $this->assertFalse(
            $this->repo->municipioTemPedidoEmEdicao($this->municipioId),
            'RN-03 olha apenas status 0.'
        );
    }

    public function test_conta_itens_por_tipo(): void
    {
        $pedido = $this->criarPedido();

        foreach ([TipoItemPedido::Pedido, TipoItemPedido::Pedido, TipoItemPedido::Liberado] as $tipo) {
            PedidoAhItem::create([
                'pedido_ah_id' => $pedido->id,
                'descricao_item' => 'Cesta basica',
                'qtd' => 10,
                'qtd_familia_atendida' => 10,
                'tipo' => $tipo,
            ]);
        }

        $this->assertSame(2, $this->repo->contarItensPorTipo($pedido->id, TipoItemPedido::Pedido));
        $this->assertSame(1, $this->repo->contarItensPorTipo($pedido->id, TipoItemPedido::Liberado));
    }

    public function test_detecta_parecer_favoravel(): void
    {
        $pedido = $this->criarPedido();

        $this->assertFalse($this->repo->temParecerFavoravel($pedido->id));

        PedidoAhParecer::create([
            'pedido_ah_id' => $pedido->id,
            'data_parecer' => now()->toDateString(),
            'parecer' => 'Contrario ao pleito.',
            'situacao' => SituacaoParecer::Contrario,
            'etapa' => EtapaParecer::AnaliseDlog,
        ]);

        $this->assertFalse(
            $this->repo->temParecerFavoravel($pedido->id),
            'Parecer contrario nao habilita o avanco.'
        );

        PedidoAhParecer::create([
            'pedido_ah_id' => $pedido->id,
            'data_parecer' => now()->toDateString(),
            'parecer' => 'Favoravel.',
            'situacao' => SituacaoParecer::Favoravel,
            'etapa' => EtapaParecer::AnaliseDlog,
        ]);

        $this->assertTrue($this->repo->temParecerFavoravel($pedido->id));
    }

    public function test_atualiza_status(): void
    {
        $pedido = $this->criarPedido();

        $this->repo->atualizarStatus($pedido->id, StatusPedidoAh::AnaliseDlog);

        $this->assertSame(StatusPedidoAh::AnaliseDlog, $pedido->fresh()->status);
    }

    public function test_registra_tramite(): void
    {
        $pedido = $this->criarPedido();

        $this->repo->registrarTramite(
            $pedido->id,
            StatusPedidoAh::EdicaoCompdec,
            StatusPedidoAh::AnaliseDlog,
            'Enviado para analise.',
            null,
        );

        $tramite = PedidoAhTramite::where('pedido_ah_id', $pedido->id)->firstOrFail();

        $this->assertSame(StatusPedidoAh::EdicaoCompdec, $tramite->status_anterior);
        $this->assertSame(StatusPedidoAh::AnaliseDlog, $tramite->status_novo);
        $this->assertSame('Enviado para analise.', $tramite->observacao);
    }
}
```

- [ ] **Step 3: Rodar e confirmar que falha**

```
TESTAR --filter=EloquentPedidoAhRepositoryTest
```
Esperado: FAIL, `Target [PedidoAhRepositoryInterface] is not instantiable`.

- [ ] **Step 4: Ajustar o contrato**

Em `Domain/Repositories/PedidoAhRepositoryInterface.php`, remover o metodo e seu comentario:

```php
    /** RN-21: existe agendamento de retirada aprovado. */
    public function temAgendamentoAprovado(int $pedidoId): bool;
```

Acrescentar, logo acima da declaracao da interface, a nota:

```php
/**
 * Persistencia do pedido e dos fatos que alimentam o ContextoTransicao.
 *
 * Nao ha metodo de agendamento: a guarda que o consumiria foi removida ao se
 * verificar que aju_h_agendamento nao existe no banco legado e que os 417
 * pedidos que atingiram Atendido o fizeram sem agendamento algum.
 */
```

- [ ] **Step 5: Implementar o repositorio**

`app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPedidoAhRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;

/**
 * Implementacao Eloquent da persistencia do pedido.
 *
 * Unico lugar do modulo, junto com os demais repositorios, onde Eloquent
 * aparece a servico do dominio.
 */
final class EloquentPedidoAhRepository implements PedidoAhRepositoryInterface
{
    /**
     * RN-01. A unicidade real e garantida pela constraint unique(numero, ano);
     * esta consulta apenas sugere o proximo valor. Sob criacao concorrente duas
     * requisicoes podem calcular o mesmo numero e a segunda recebera violacao
     * de constraint, que a camada de servico deve tratar com nova tentativa.
     */
    public function proximoNumeroDoAno(int $ano): int
    {
        $maior = PedidoAh::withTrashed()
            ->where('ano', $ano)
            ->max('numero');

        return ((int) $maior) + 1;
    }

    /**
     * RN-03. Considera apenas o status de edicao, reproduzindo a versao do
     * legado que de fato funcionava.
     */
    public function municipioTemPedidoEmEdicao(int $municipioId): bool
    {
        return PedidoAh::query()
            ->where('municipio_id', $municipioId)
            ->where('status', StatusPedidoAh::EdicaoCompdec->value)
            ->exists();
    }

    public function contarItensPorTipo(int $pedidoId, TipoItemPedido $tipo): int
    {
        return PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('tipo', $tipo->value)
            ->count();
    }

    /** RN-11. */
    public function temParecerFavoravel(int $pedidoId): bool
    {
        return PedidoAhParecer::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('situacao', SituacaoParecer::Favoravel->value)
            ->exists();
    }

    public function atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void
    {
        PedidoAh::query()
            ->whereKey($pedidoId)
            ->update(['status' => $novo->value]);
    }

    /** RN-14. */
    public function registrarTramite(
        int $pedidoId,
        StatusPedidoAh $anterior,
        StatusPedidoAh $novo,
        ?string $observacao,
        ?int $usuarioId,
    ): void {
        PedidoAhTramite::create([
            'pedido_ah_id'    => $pedidoId,
            'status_anterior' => $anterior->value,
            'status_novo'     => $novo->value,
            'observacao'      => $observacao,
            'user_id'         => $usuarioId,
        ]);
    }
}
```

Nota: a assinatura de `registrarTramite` no contrato da fase 1 declara `int $usuarioId`. Torne-o `?int $usuarioId` **nas duas** — interface e implementacao. Justificativa: transicoes disparadas por comando de console ou por job nao tem usuario autenticado, e a coluna `pedido_ah_tramites.user_id` ja e nullable.

- [ ] **Step 6: Registrar o bind provisorio**

Em `AjudaHumanitariaServiceProvider::register()`, acrescentar antes do singleton do workflow:

```php
        $this->app->bind(
            \App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface::class,
            \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPedidoAhRepository::class,
        );
```

A Task 6 reorganiza os quatro binds em bloco; este e provisorio para o teste passar.

- [ ] **Step 7: Rodar e confirmar que passa**

```
TESTAR --filter=EloquentPedidoAhRepositoryTest
TESTAR --filter=RepositoryContractsTest
```
Esperado: PASS nos dois, 10 e 5 testes.

- [ ] **Step 8: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Infrastructure SDC/app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php SDC/app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php
```

Mensagem:
```
✨ feat(ajuda-humanitaria): persistencia Eloquent do pedido MAH

Implementa os seis metodos do contrato: proximo numero do ano, deteccao de
pedido em edicao, contagem de itens por tipo, presenca de parecer
favoravel, atualizacao de status e registro de tramite.

Remove temAgendamentoAprovado do contrato: a guarda que o consumiria caiu
junto com a descoberta de que aju_h_agendamento nao existe no legado.

registrarTramite passa a aceitar usuario nulo, para transicoes disparadas
por console ou job.
```

---

### Task 3: EloquentPrestacaoContaRepository

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPrestacaoContaRepository.php`
- Test: `tests/Feature/AjudaHumanitaria/EloquentPrestacaoContaRepositoryTest.php`

**Interfaces:**
- Consumes: `PrestacaoContaRepositoryInterface`, models `PrestacaoConta`, `PrestacaoContaItem`, `PrestacaoContaEntrega`, `PedidoAhItem`
- Produces: `EloquentPrestacaoContaRepository`

O metodo central e `copiarItensLiberados`, que materializa a RN-15.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/EloquentPrestacaoContaRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EloquentPrestacaoContaRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PrestacaoContaRepositoryInterface $repo;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = app(PrestacaoContaRepositoryInterface::class);

        $id = DB::table('municipios')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }
        $this->municipioId = (int) $id;
    }

    private function criarPedido(): PedidoAh
    {
        return PedidoAh::create([
            'numero' => 990_501,
            'ano' => 2093,
            'municipio_id' => $this->municipioId,
            'pop_atendida' => 800,
            'esforcos_realizados' => 'Teste de prestacao.',
            'status' => StatusPedidoAh::Atendido,
            'data_entrada_sistema' => now(),
        ]);
    }

    public function test_abre_prestacao_com_prazo_e_status_inicial(): void
    {
        $pedido = $this->criarPedido();
        $limite = CarbonImmutable::parse('2093-10-15');

        $id = $this->repo->abrirParaPedido($pedido->id, $limite);

        $prestacao = PrestacaoConta::findOrFail($id);

        $this->assertSame($pedido->id, $prestacao->pedido_ah_id);
        $this->assertSame('2093-10-15', $prestacao->data_limite->toDateString());
        $this->assertSame(StatusPrestacaoConta::Pendente, $prestacao->status);
    }

    public function test_copia_somente_os_itens_liberados(): void
    {
        $pedido   = $this->criarPedido();
        $material = MaterialAh::create(['nome' => 'Cesta basica', 'codigo_legado' => '161']);

        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'material_ah_id' => $material->id,
            'codigo' => '161', 'descricao_item' => 'Cesta basica',
            'qtd' => 100, 'qtd_familia_atendida' => 100, 'tipo' => TipoItemPedido::Pedido,
        ]);
        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'material_ah_id' => $material->id,
            'codigo' => '161', 'descricao_item' => 'Cesta basica',
            'qtd' => 60, 'qtd_familia_atendida' => 55, 'tipo' => TipoItemPedido::Liberado,
        ]);

        $prestacaoId = $this->repo->abrirParaPedido($pedido->id, CarbonImmutable::parse('2093-10-15'));
        $copiados    = $this->repo->copiarItensLiberados($pedido->id, $prestacaoId);

        $this->assertSame(1, $copiados, 'RN-15 copia apenas o tipo Liberado.');

        $item = PrestacaoContaItem::where('prestacao_conta_id', $prestacaoId)->firstOrFail();

        $this->assertSame(60, $item->qtd);
        $this->assertSame(55, $item->total_familia_atendida);
        $this->assertSame('Cesta basica', $item->nome_material);
        $this->assertSame($material->id, $item->material_ah_id);
    }

    public function test_copiar_pedido_sem_itens_liberados_devolve_zero(): void
    {
        $pedido      = $this->criarPedido();
        $prestacaoId = $this->repo->abrirParaPedido($pedido->id, CarbonImmutable::parse('2093-10-15'));

        $this->assertSame(0, $this->repo->copiarItensLiberados($pedido->id, $prestacaoId));
    }

    public function test_quantidades_do_item_e_ja_entregue(): void
    {
        $pedido      = $this->criarPedido();
        $prestacaoId = $this->repo->abrirParaPedido($pedido->id, CarbonImmutable::parse('2093-10-15'));

        $item = PrestacaoContaItem::create([
            'prestacao_conta_id' => $prestacaoId,
            'nome_material' => 'Colchao',
            'qtd' => 40,
            'total_familia_atendida' => 40,
        ]);

        $this->assertSame(40, $this->repo->quantidadeDoItem($item->id));
        $this->assertSame(0, $this->repo->quantidadeJaEntregue($item->id));

        foreach ([5, 7] as $qtd) {
            PrestacaoContaEntrega::create([
                'prestacao_conta_item_id' => $item->id,
                'nome_beneficiario' => 'Beneficiario teste',
                'qtd' => $qtd,
                'data_entrega' => now()->toDateString(),
            ]);
        }

        $this->assertSame(12, $this->repo->quantidadeJaEntregue($item->id));
    }

    public function test_homologa_marca_status_e_carimba_usuario(): void
    {
        $pedido      = $this->criarPedido();
        $prestacaoId = $this->repo->abrirParaPedido($pedido->id, CarbonImmutable::parse('2093-10-15'));

        $usuarioId = (int) DB::table('users')->value('id');
        if ($usuarioId === 0) {
            $this->markTestSkipped('Banco de desenvolvimento sem usuarios.');
        }

        $this->repo->homologar($prestacaoId, $usuarioId);

        $prestacao = PrestacaoConta::findOrFail($prestacaoId);

        $this->assertSame(StatusPrestacaoConta::Homologada, $prestacao->status);
        $this->assertSame($usuarioId, $prestacao->homologado_por);
        $this->assertNotNull($prestacao->homologado_em);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=EloquentPrestacaoContaRepositoryTest
```
Esperado: FAIL, `Target [PrestacaoContaRepositoryInterface] is not instantiable`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentPrestacaoContaRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Carbon\CarbonImmutable;

final class EloquentPrestacaoContaRepository implements PrestacaoContaRepositoryInterface
{
    /** RN-16. */
    public function abrirParaPedido(int $pedidoId, CarbonImmutable $dataLimite): int
    {
        $prestacao = PrestacaoConta::create([
            'pedido_ah_id' => $pedidoId,
            'status'       => StatusPrestacaoConta::Pendente,
            'data_limite'  => $dataLimite->toDateString(),
        ]);

        return (int) $prestacao->id;
    }

    /**
     * RN-15: copia para a prestacao o que foi efetivamente liberado.
     *
     * Equivale a iniciaPrestContas do legado, que percorria os itens tipo L e
     * inseria um registro de prestacao para cada. No Laravel isso nunca chegou a
     * existir: AjudaPrestConta::lancarPrestContaItens era chamada e nunca
     * definida, o que quebrava a transicao para Atendido.
     */
    public function copiarItensLiberados(int $pedidoId, int $prestacaoContaId): int
    {
        $itens = PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('tipo', TipoItemPedido::Liberado->value)
            ->get();

        foreach ($itens as $item) {
            PrestacaoContaItem::create([
                'prestacao_conta_id'     => $prestacaoContaId,
                'material_ah_id'         => $item->material_ah_id,
                'codigo_material'        => $item->codigo,
                'nome_material'          => $item->descricao_item,
                'qtd'                    => $item->qtd,
                'total_familia_atendida' => $item->qtd_familia_atendida,
            ]);
        }

        return $itens->count();
    }

    /** RN-18. */
    public function quantidadeDoItem(int $prestacaoContaItemId): int
    {
        return (int) PrestacaoContaItem::query()
            ->whereKey($prestacaoContaItemId)
            ->value('qtd');
    }

    /** RN-18. */
    public function quantidadeJaEntregue(int $prestacaoContaItemId): int
    {
        return (int) PrestacaoContaEntrega::query()
            ->where('prestacao_conta_item_id', $prestacaoContaItemId)
            ->sum('qtd');
    }

    /** RN-19. */
    public function homologar(int $prestacaoContaId, int $usuarioId): void
    {
        PrestacaoConta::query()
            ->whereKey($prestacaoContaId)
            ->update([
                'status'         => StatusPrestacaoConta::Homologada->value,
                'homologado_por' => $usuarioId,
                'homologado_em'  => now(),
            ]);
    }
}
```

- [ ] **Step 4: Registrar o bind provisorio**

Em `AjudaHumanitariaServiceProvider::register()`:

```php
        $this->app->bind(
            \App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface::class,
            \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPrestacaoContaRepository::class,
        );
```

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=EloquentPrestacaoContaRepositoryTest
```
Esperado: PASS, 5 testes.

- [ ] **Step 6: Commit**

Mensagem:
```
✨ feat(ajuda-humanitaria): persistencia da prestacao de contas do MAH

Implementa a abertura da prestacao com prazo, a copia dos itens
liberados, as quantidades que alimentam a RN-18 e a homologacao.

copiarItensLiberados e a RN-15, que o port Laravel nunca chegou a ter:
lancarPrestContaItens era chamada e nunca definida, quebrando a transicao
para Atendido.
```

---

### Task 4: EloquentMaterialAhRepository

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentMaterialAhRepository.php`
- Test: `tests/Feature/AjudaHumanitaria/EloquentMaterialAhRepositoryTest.php`

**Interfaces:**
- Consumes: `MaterialAhRepositoryInterface`, model `MaterialAh`
- Produces: `EloquentMaterialAhRepository`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/EloquentMaterialAhRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class EloquentMaterialAhRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private MaterialAhRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(MaterialAhRepositoryInterface::class);
    }

    public function test_lista_apenas_os_disponiveis_para_pedido(): void
    {
        MaterialAh::query()->update(['disponivel_para_pedido' => false]);

        $disponivel = MaterialAh::create([
            'nome' => 'Cesta basica teste',
            'unidade_medida' => 'UN',
            'disponivel_para_pedido' => true,
        ]);
        MaterialAh::create([
            'nome' => 'Item descontinuado teste',
            'unidade_medida' => 'UN',
            'disponivel_para_pedido' => false,
        ]);

        $lista = $this->repo->disponiveisParaPedido();

        $this->assertCount(1, $lista);
        $this->assertSame($disponivel->id, $lista[0]['id']);
        $this->assertSame('Cesta basica teste', $lista[0]['nome']);
        $this->assertSame('UN', $lista[0]['unidade_medida']);
        $this->assertSame(['id', 'nome', 'unidade_medida'], array_keys($lista[0]));
    }

    public function test_lista_vem_ordenada_por_nome(): void
    {
        MaterialAh::query()->update(['disponivel_para_pedido' => false]);

        foreach (['Telha teste', 'Balde teste', 'Colchao teste'] as $nome) {
            MaterialAh::create([
                'nome' => $nome,
                'unidade_medida' => 'UN',
                'disponivel_para_pedido' => true,
            ]);
        }

        $nomes = array_column($this->repo->disponiveisParaPedido(), 'nome');

        $this->assertSame(['Balde teste', 'Colchao teste', 'Telha teste'], $nomes);
    }

    public function test_alterna_a_disponibilidade(): void
    {
        $material = MaterialAh::create([
            'nome' => 'Lona teste',
            'unidade_medida' => 'UN',
            'disponivel_para_pedido' => true,
        ]);

        $this->repo->definirDisponibilidade($material->id, false);
        $this->assertFalse($material->fresh()->disponivel_para_pedido);

        $this->repo->definirDisponibilidade($material->id, true);
        $this->assertTrue($material->fresh()->disponivel_para_pedido);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=EloquentMaterialAhRepositoryTest
```
Esperado: FAIL, `Target [MaterialAhRepositoryInterface] is not instantiable`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Infrastructure/Persistence/EloquentMaterialAhRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;

/**
 * RN-07: o catalogo de material disponivel para pedido e configuravel pelo
 * CEDEC. No legado a flag vive em aju_unidade.pedido_h e hoje marca nove
 * materiais; o port Laravel havia regredido isso para quatro itens fixos em
 * codigo, duplicados entre as telas de criacao e de edicao.
 */
final class EloquentMaterialAhRepository implements MaterialAhRepositoryInterface
{
    /**
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function disponiveisParaPedido(): array
    {
        return MaterialAh::query()
            ->where('disponivel_para_pedido', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'unidade_medida'])
            ->map(static fn (MaterialAh $material): array => [
                'id'             => (int) $material->id,
                'nome'           => (string) $material->nome,
                'unidade_medida' => (string) $material->unidade_medida,
            ])
            ->all();
    }

    public function definirDisponibilidade(int $materialId, bool $disponivel): void
    {
        MaterialAh::query()
            ->whereKey($materialId)
            ->update(['disponivel_para_pedido' => $disponivel]);
    }
}
```

- [ ] **Step 4: Registrar o bind provisorio**

```php
        $this->app->bind(
            \App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface::class,
            \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentMaterialAhRepository::class,
        );
```

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=EloquentMaterialAhRepositoryTest
```
Esperado: PASS, 3 testes.

- [ ] **Step 6: Commit**

Mensagem:
```
✨ feat(ajuda-humanitaria): catalogo de material do MAH

Lista ordenada dos materiais disponiveis para pedido e alternancia da
disponibilidade, devolvendo a configurabilidade da RN-07 que o port
Laravel havia congelado em quatro itens no codigo.
```

---

### Task 5: LegadoSaldoMaterialRepository

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Infrastructure/Persistence/LegadoSaldoMaterialRepository.php`
- Test: `tests/Feature/AjudaHumanitaria/LegadoSaldoMaterialRepositoryTest.php`

**Interfaces:**
- Consumes: `SaldoMaterialRepositoryInterface`
- Produces: `LegadoSaldoMaterialRepository`

Esta e a unica classe do modulo que fala com a base legada, sempre em leitura.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/LegadoSaldoMaterialRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class LegadoSaldoMaterialRepositoryTest extends TestCase
{
    private SaldoMaterialRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->repo = app(SaldoMaterialRepositoryInterface::class);
    }

    /**
     * Aponta a conexao legada para a base real de desenvolvimento. Feito no
     * teste, e nao via .env, para que a suite nao dependa da configuracao
     * pessoal de quem roda.
     */
    private function configurarConexaoReal(): void
    {
        Config::set('database.connections.legacy.host', '127.0.0.1');
        Config::set('database.connections.legacy.port', '3306');
        Config::set('database.connections.legacy.database', 'gestaocedec_local');
        Config::set('database.connections.legacy.username', 'root');
        Config::set('database.connections.legacy.password', '');

        \Illuminate\Support\Facades\DB::purge('legacy');
    }

    private function configurarConexaoQuebrada(): void
    {
        Config::set('database.connections.legacy.host', '127.0.0.1');
        Config::set('database.connections.legacy.port', '1');
        Config::set('database.connections.legacy.database', 'inexistente');
        Config::set('database.connections.legacy.username', 'ninguem');
        Config::set('database.connections.legacy.password', 'nada');

        \Illuminate\Support\Facades\DB::purge('legacy');
    }

    public function test_reporta_indisponivel_quando_a_conexao_falha(): void
    {
        $this->configurarConexaoQuebrada();

        $this->assertFalse($this->repo->disponivel());
    }

    public function test_devolve_lista_vazia_em_vez_de_estourar_quando_indisponivel(): void
    {
        $this->configurarConexaoQuebrada();

        $this->assertSame([], $this->repo->saldoPorDeposito());
    }

    public function test_reporta_disponivel_com_a_base_real(): void
    {
        $this->configurarConexaoReal();

        if (! $this->repo->disponivel()) {
            $this->markTestSkipped('Base legada gestaocedec_local nao acessivel neste ambiente.');
        }

        $this->assertTrue($this->repo->disponivel());
    }

    public function test_le_saldo_por_deposito_da_base_real(): void
    {
        $this->configurarConexaoReal();

        if (! $this->repo->disponivel()) {
            $this->markTestSkipped('Base legada gestaocedec_local nao acessivel neste ambiente.');
        }

        $saldo = $this->repo->saldoPorDeposito();

        $this->assertNotEmpty($saldo);
        $this->assertSame(['deposito', 'material', 'saldo'], array_keys($saldo[0]));
        $this->assertIsString($saldo[0]['deposito']);
        $this->assertIsString($saldo[0]['material']);
        $this->assertIsInt($saldo[0]['saldo']);
        $this->assertGreaterThan(0, $saldo[0]['saldo'], 'A consulta descarta saldo zero.');
    }

    public function test_filtra_por_codigo_legado_do_material(): void
    {
        $this->configurarConexaoReal();

        if (! $this->repo->disponivel()) {
            $this->markTestSkipped('Base legada gestaocedec_local nao acessivel neste ambiente.');
        }

        $todos = $this->repo->saldoPorDeposito();
        $this->assertNotEmpty($todos);

        // 210 e o id_unidade de TELHA em aju_unidade, material com saldo real.
        $filtrado = $this->repo->saldoPorDeposito('210');

        $this->assertLessThanOrEqual(count($todos), count($filtrado));

        foreach ($filtrado as $linha) {
            $this->assertSame('TELHA', $linha['material']);
        }
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=LegadoSaldoMaterialRepositoryTest
```
Esperado: FAIL, `Target [SaldoMaterialRepositoryInterface] is not instantiable`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Infrastructure/Persistence/LegadoSaldoMaterialRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RN-25: leitura do saldo de material por deposito na base legada.
 *
 * Somente leitura. Nenhuma escrita na base do sistema procedural.
 *
 * A base e a do gestaocedec, onde vivem aju_estoque, aju_deposito e
 * aju_unidade. O legado consultava esse saldo com a categoria CESTA BASICA
 * fixa em codigo; aqui o recorte e pelo codigo legado do material, que o
 * catalogo do modulo guarda em materiais_ah.codigo_legado.
 *
 * Toda falha de conexao vira lista vazia mais registro em log: a tela de
 * disponibilidade de material degrada, nao quebra. Enquanto as chaves
 * DB_LEGACY_* nao estiverem no .env, esse e o comportamento esperado.
 */
final class LegadoSaldoMaterialRepository implements SaldoMaterialRepositoryInterface
{
    /**
     * @return array<int, array{deposito: string, material: string, saldo: int}>
     */
    public function saldoPorDeposito(?string $codigoLegado = null): array
    {
        $chave = 'ajuda_humanitaria:saldo_legado:' . ($codigoLegado ?? 'todos');
        $ttl   = (int) config('ajuda-humanitaria.saldo_cache_ttl', 300);

        return Cache::remember($chave, $ttl, function () use ($codigoLegado): array {
            try {
                $consulta = DB::connection($this->conexao())
                    ->table('aju_estoque as e')
                    ->join('aju_deposito as d', 'e.id_deposito', '=', 'd.id_deposito')
                    ->join('aju_unidade as u', 'e.id_produto', '=', 'u.id_unidade')
                    ->where('e.saldo', '<>', 0)
                    ->groupBy('d.id_deposito', 'd.nome', 'u.singular')
                    ->orderBy('d.nome')
                    ->select([
                        'd.nome as deposito',
                        'u.singular as material',
                        DB::raw('SUM(e.saldo) as saldo'),
                    ]);

                if ($codigoLegado !== null) {
                    $consulta->where('u.id_unidade', $codigoLegado);
                }

                return $consulta->get()
                    ->map(static fn (object $linha): array => [
                        'deposito' => (string) $linha->deposito,
                        'material' => (string) $linha->material,
                        'saldo'    => (int) $linha->saldo,
                    ])
                    ->all();
            } catch (Throwable $erro) {
                Log::warning('Saldo de material legado indisponivel.', [
                    'conexao' => $this->conexao(),
                    'erro'    => $erro->getMessage(),
                ]);

                return [];
            }
        });
    }

    public function disponivel(): bool
    {
        try {
            DB::connection($this->conexao())->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function conexao(): string
    {
        return (string) config('ajuda-humanitaria.legacy_connection', 'legacy');
    }
}
```

- [ ] **Step 4: Registrar o bind provisorio**

```php
        $this->app->bind(
            \App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface::class,
            \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\LegadoSaldoMaterialRepository::class,
        );
```

- [ ] **Step 5: Rodar e confirmar que passa**

```
TESTAR --filter=LegadoSaldoMaterialRepositoryTest
```
Esperado: PASS, 5 testes. Se a base `gestaocedec_local` nao existir na maquina, tres deles ficam `skipped` e os dois de degradacao continuam passando — o que ja prova o requisito principal.

Se o driver mysql nao estiver carregado, o runner precisa de `-d "extension=php_pdo_mysql.dll"` alem das extensoes de pgsql.

- [ ] **Step 6: Commit**

Mensagem:
```
✨ feat(ajuda-humanitaria): ponte de saldo do estoque legado

Le saldo por deposito em aju_estoque, aju_deposito e aju_unidade na base
do gestaocedec, com cache curto e filtro pelo codigo legado do material em
vez da categoria CESTA BASICA que o legado fixava em codigo.

Somente leitura. Falha de conexao vira lista vazia mais log: a tela
degrada em vez de quebrar, que e o comportamento esperado enquanto as
chaves DB_LEGACY_* nao estiverem preenchidas.
```

---

### Task 6: Consolidar os binds no provider

**Files:**
- Modify: `app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php`
- Test: `tests/Feature/AjudaHumanitaria/ProviderMahTest.php` (ja existe, acrescentar casos)

**Interfaces:**
- Consumes: as quatro interfaces e as quatro implementacoes
- Produces: provider com os binds declarados em um unico mapa

- [ ] **Step 1: Acrescentar os casos ao teste existente**

Em `tests/Feature/AjudaHumanitaria/ProviderMahTest.php`, acrescentar:

```php
    /**
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function bindProvider(): array
    {
        return [
            'pedido' => [
                \App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface::class,
                \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPedidoAhRepository::class,
            ],
            'prestacao' => [
                \App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface::class,
                \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPrestacaoContaRepository::class,
            ],
            'material' => [
                \App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface::class,
                \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentMaterialAhRepository::class,
            ],
            'saldo' => [
                \App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface::class,
                \App\Modules\AjudaHumanitaria\Infrastructure\Persistence\LegadoSaldoMaterialRepository::class,
            ],
        ];
    }

    /**
     * @param  class-string  $contrato
     * @param  class-string  $implementacao
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bindProvider')]
    public function test_contrato_resolve_para_a_implementacao(string $contrato, string $implementacao): void
    {
        $this->assertInstanceOf($implementacao, app($contrato));
    }

    public function test_dominio_nao_conhece_a_camada_de_infraestrutura(): void
    {
        $base = base_path('app/Modules/AjudaHumanitaria/Domain');

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        $violacoes = [];

        foreach ($iterador as $arquivo) {
            if ($arquivo->getExtension() !== 'php') {
                continue;
            }

            $conteudo = (string) file_get_contents($arquivo->getPathname());

            if (str_contains($conteudo, 'Infrastructure\\Persistence')) {
                $violacoes[] = $arquivo->getFilename();
            }
        }

        $this->assertSame([], $violacoes, 'Domain nao pode referenciar Infrastructure.');
    }
```

- [ ] **Step 2: Rodar e confirmar o estado atual**

```
TESTAR --filter=ProviderMahTest
```
Esperado: PASS, ja que as Tasks 2 a 5 registraram os binds provisorios. Este passo confirma o ponto de partida antes de reorganizar.

- [ ] **Step 3: Consolidar o provider**

Substituir o `register()` de `AjudaHumanitariaServiceProvider` por:

```php
    /**
     * Contratos de dominio e suas implementacoes concretas.
     *
     * A troca de qualquer implementacao acontece aqui, em uma linha. O caso mais
     * concreto e o saldo: quando o estoque virar nativo do NewSDC, basta apontar
     * SaldoMaterialRepositoryInterface para a nova classe, sem tocar no dominio.
     *
     * @var array<class-string, class-string>
     */
    private const REPOSITORIOS = [
        PedidoAhRepositoryInterface::class       => EloquentPedidoAhRepository::class,
        PrestacaoContaRepositoryInterface::class => EloquentPrestacaoContaRepository::class,
        MaterialAhRepositoryInterface::class     => EloquentMaterialAhRepository::class,
        SaldoMaterialRepositoryInterface::class  => LegadoSaldoMaterialRepository::class,
    ];

    public function register(): void
    {
        foreach (self::REPOSITORIOS as $contrato => $implementacao) {
            $this->app->bind($contrato, $implementacao);
        }

        $this->app->singleton(PedidoAhWorkflow::class, function ($app): PedidoAhWorkflow {
            $guardas = array_map(
                static fn (string $guarda) => $app->make($guarda),
                self::GUARDAS_TRANSICAO,
            );

            return new PedidoAhWorkflow($guardas);
        });
    }
```

Acrescentar os `use` correspondentes no topo do arquivo:

```php
use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentMaterialAhRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPedidoAhRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPrestacaoContaRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\LegadoSaldoMaterialRepository;
```

- [ ] **Step 4: Rodar a suite do modulo**

```
TESTAR tests/Unit/AjudaHumanitaria tests/Feature/AjudaHumanitaria
```
Esperado: PASS em tudo.

- [ ] **Step 5: Rodar a suite completa e comparar com a linha de base**

```
TESTAR
```
Esperado: o mesmo 1 erro e as mesmas 4 falhas pre-existentes, mais os testes novos passando. Qualquer falha diferente dessas cinco e regressao.

- [ ] **Step 6: Commit**

Mensagem:
```
♻️ refactor(ajuda-humanitaria): consolida os binds de repositorio

Os quatro contratos passam a ser declarados em um unico mapa no provider,
no lugar dos binds provisorios espalhados pelas tasks anteriores.

Trocar implementacao vira uma linha. O caso concreto e o saldo: quando o
estoque virar nativo, o dominio nao muda.
```

---

## Verificacao da fase

1. `TESTAR tests/Unit/AjudaHumanitaria` e `TESTAR tests/Feature/AjudaHumanitaria` passam
2. Os quatro contratos resolvem para as implementacoes concretas pelo container
3. `Domain/` continua sem importar `Illuminate`, `App\Models` ou `Infrastructure\Persistence`
4. A ponte de saldo devolve lista vazia com a conexao quebrada e dados reais com a conexao configurada
5. Suite completa mantem exatamente as cinco falhas pre-existentes
6. Nenhum arquivo do mock foi removido

## Regras cobertas nesta fase

RN-01 (calculo do proximo numero), RN-03, RN-07, RN-08, RN-11, RN-14, RN-15, RN-18 (as quantidades que a alimentam), RN-19, RN-25.

Ficam para a fase 2b, que constroi os services: RN-02, RN-16 (aplicacao do prazo), RN-17. E para a 2c, que constroi o HTTP: RN-04, RN-05, RN-20, RN-22, RN-23, RN-24.
