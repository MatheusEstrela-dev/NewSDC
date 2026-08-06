# Ajuda Humanitaria (MAH) - Fase 2b: Servicos - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir a camada de aplicacao do MAH: os DTOs de entrada e os servicos que orquestram dominio e persistencia, deixando o fluxo completo do pedido exercitavel por teste, sem HTTP.

**Architecture:** Cada servico e uma classe pura, sem heranca, que recebe interfaces de repositorio e objetos de dominio pelo construtor. Nenhum servico reimplementa decisao de transicao: `TramitacaoService` e o unico ponto que altera status, e sempre consultando `PedidoAhWorkflow`. Servicos que podem recusar uma operacao devolvem a tupla `[$resultado, $erro]`, padrao ja usado por `ProcessoTdapController`.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL, PHPUnit 11.

## Contexto: o que as fases anteriores deixaram pronto

- **Fase 1**: enums, `PedidoAhWorkflow` com quatro guardas, tres specifications, dez models, schema aplicado
- **Fase 2a**: quatro repositories implementados e registrados no container, `config/ajuda-humanitaria.php`

Esta fase **nao** cria controllers, requests, resources, rotas, permissoes nem telas. Isso e a fase 2c.

## Decisoes de escopo desta fase

Dois servicos previstos na spec original foram cortados:

| Cortado | Motivo |
| --- | --- |
| `AgendamentoRetiradaService` | A funcionalidade nao tem lastro no legado: `aju_h_agendamento` nao existe no banco e os 417 pedidos que atingiram Atendido passaram sem agendamento. A tabela `pedido_ah_agendamentos` permanece no schema, inerte, ate a area confirmar se o processo existe |
| `SaldoMaterialService` | Seria repasse puro sobre `SaldoMaterialRepositoryInterface`, que ja e o contrato de leitura. A fase 2c injeta o repositorio direto no controller |

## Global Constraints

- Todo arquivo PHP novo comeca com `<?php`, linha em branco, `declare(strict_types=1);`
- Namespace raiz: `App\Modules\AjudaHumanitaria`
- Proibido emoji em codigo; sem acento em identificadores, apenas em string de exibicao
- **Arquivos de teste nao entram em commit.** Regra permanente do usuario
- Servicos **nao** estendem `BaseService`: os modulos recentes do projeto (Cisterna, Tdap) usam classe pura, e `BaseService` esta em declinio. O trait `HasPagination` e codigo morto, nao usar
- Servicos dependem de **interfaces** de `Domain/Repositories`, nunca das classes de `Infrastructure/Persistence`
- Nenhum servico alem de `TramitacaoService` pode escrever na coluna `status` de `pedidos_ah`
- Operacoes que gravam em mais de uma tabela ficam dentro de `DB::transaction`
- Testes com banco usam `DatabaseTransactions`, nunca `RefreshDatabase`. Em teste que precise de municipio, reaproveitar um existente com `DB::table('municipios')->value('id')`
- Nunca rodar `migrate:fresh`, `migrate:refresh` nem `db:wipe`
- Commits em gitmoji, escopo `ajuda-humanitaria`, sem trailer `Co-Authored-By`

### Runner de teste

Salve como `.ps1` fora do repositorio; nos passos, `TESTAR` designa este bloco.

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

**Linha de base antes desta fase: 225 testes, 670 assercoes, 1 erro e 4 falhas**, todas pre-existentes em `PaeFormularioControllerTest`, `ProcessoStoreFlashTest` e `PlanConUploadTest` (3). Qualquer falha alem dessas cinco e regressao.

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `DTOs/PedidoAhDTO.php` | Dados de abertura e edicao do pedido |
| `DTOs/ItemPedidoDTO.php` | Material, quantidade, familias e tipo |
| `DTOs/TransicaoPedidoDTO.php` | Status alvo e observacao |
| `DTOs/ParecerDTO.php` | Texto, situacao e etapa do parecer |
| `DTOs/EntregaBeneficiarioDTO.php` | Beneficiario e quantidade entregue |
| `Services/NumeracaoPedidoService.php` | RN-01, com nova tentativa sob concorrencia |
| `Services/PedidoAhService.php` | RN-02, RN-03, listagem e edicao |
| `Services/ItemPedidoService.php` | RN-07, RN-08, RN-09 |
| `Services/ParecerService.php` | RN-10 |
| `Services/TramitacaoService.php` | RN-12, RN-14, RN-15, RN-16 |
| `Services/PrestacaoContasService.php` | RN-17, RN-18, RN-19 |

## Ordem de execucao

- **Onda 1**, paralelizavel: Task 1 (DTOs), Task 2 (NumeracaoPedidoService)
- **Onda 2**, paralelizavel apos a onda 1: Tasks 3, 4, 5, 6
- **Onda 3**, apos a Task 6: Task 7

`TramitacaoService` fala com repositorios, nao com outros servicos, por isso
convive com 3, 4 e 5 na mesma onda. A Task 7 fica sozinha na onda seguinte
porque `PrestacaoContasService` injeta `TramitacaoService`: a homologacao da
prestacao e a finalizacao do processo sao o mesmo ato (RN-19), e essa e a unica
dependencia entre servicos desta fase.

---

### Task 1: DTOs

**Files:**
- Create: `app/Modules/AjudaHumanitaria/DTOs/PedidoAhDTO.php`
- Create: `app/Modules/AjudaHumanitaria/DTOs/ItemPedidoDTO.php`
- Create: `app/Modules/AjudaHumanitaria/DTOs/TransicaoPedidoDTO.php`
- Create: `app/Modules/AjudaHumanitaria/DTOs/ParecerDTO.php`
- Create: `app/Modules/AjudaHumanitaria/DTOs/EntregaBeneficiarioDTO.php`
- Test: `tests/Unit/AjudaHumanitaria/DTOs/DtosMahTest.php`

**Interfaces:**
- Consumes: enums `StatusPedidoAh`, `TipoItemPedido`, `SituacaoParecer`, `EtapaParecer`, `TipoDecreto`
- Produces: cinco `final readonly class`, cada uma com `fromRequest(array $data): self` e `toArray(): array`, seguindo `App\Modules\Tdap\DTOs\TransicaoProcessoDTO`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/DTOs/DtosMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO;
use App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO;
use App\Modules\AjudaHumanitaria\DTOs\ParecerDTO;
use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use PHPUnit\Framework\TestCase;

final class DtosMahTest extends TestCase
{
    public function test_pedido_monta_a_partir_do_request(): void
    {
        $dto = PedidoAhDTO::fromRequest([
            'municipio_id' => 42,
            'cobrade_id' => 7,
            'pop_atendida' => '1200',
            'decreto_se_ecp_vig' => true,
            'tipo_decreto' => 'SE',
            'numero_decreto' => '123/2026',
            'vigencia_decreto' => '2026-12-31',
            'esforcos_realizados' => 'Distribuicao pela equipe local.',
            'nome_coordenador' => 'Maria',
            'email_coordenador' => 'maria@exemplo.gov.br',
        ]);

        $this->assertSame(42, $dto->municipioId);
        $this->assertSame(7, $dto->cobradeId);
        $this->assertSame(1200, $dto->popAtendida);
        $this->assertTrue($dto->decretoSeEcpVigente);
        $this->assertSame(TipoDecreto::SituacaoEmergencia, $dto->tipoDecreto);
        $this->assertSame('Maria', $dto->nomeCoordenador);
    }

    public function test_pedido_aceita_campos_opcionais_ausentes(): void
    {
        $dto = PedidoAhDTO::fromRequest([
            'municipio_id' => 42,
            'pop_atendida' => 10,
            'esforcos_realizados' => 'x',
        ]);

        $this->assertNull($dto->cobradeId);
        $this->assertNull($dto->tipoDecreto);
        $this->assertNull($dto->nomePrefeito);
        $this->assertFalse($dto->decretoSeEcpVigente);
    }

    public function test_pedido_converte_string_vazia_em_nulo(): void
    {
        $dto = PedidoAhDTO::fromRequest([
            'municipio_id' => 42,
            'pop_atendida' => 10,
            'esforcos_realizados' => 'x',
            'nome_prefeito' => '   ',
        ]);

        $this->assertNull($dto->nomePrefeito);
    }

    public function test_item_monta_e_serializa(): void
    {
        $dto = ItemPedidoDTO::fromRequest([
            'material_ah_id' => 3,
            'codigo' => '161',
            'descricao_item' => 'Cesta basica',
            'qtd' => '100',
            'qtd_familia_atendida' => '90',
            'tipo' => 'L',
        ]);

        $this->assertSame(3, $dto->materialAhId);
        $this->assertSame(100, $dto->qtd);
        $this->assertSame(90, $dto->qtdFamiliaAtendida);
        $this->assertSame(TipoItemPedido::Liberado, $dto->tipo);

        $this->assertSame([
            'material_ah_id' => 3,
            'codigo' => '161',
            'descricao_item' => 'Cesta basica',
            'qtd' => 100,
            'qtd_familia_atendida' => 90,
            'tipo' => 'L',
        ], $dto->toArray());
    }

    public function test_item_assume_tipo_pedido_por_padrao(): void
    {
        $dto = ItemPedidoDTO::fromRequest([
            'descricao_item' => 'Colchao',
            'qtd' => 5,
            'qtd_familia_atendida' => 5,
        ]);

        $this->assertSame(TipoItemPedido::Pedido, $dto->tipo);
    }

    public function test_transicao_monta_com_status_alvo_e_observacao(): void
    {
        $dto = TransicaoPedidoDTO::fromRequest([
            'status_alvo' => 5,
            'observacao' => 'Material separado.',
        ]);

        $this->assertSame(StatusPedidoAh::AguardandoRetirada, $dto->statusAlvo);
        $this->assertSame('Material separado.', $dto->observacao);
    }

    public function test_transicao_sem_observacao(): void
    {
        $dto = TransicaoPedidoDTO::fromRequest(['status_alvo' => 7]);

        $this->assertSame(StatusPedidoAh::Cancelado, $dto->statusAlvo);
        $this->assertNull($dto->observacao);
    }

    public function test_parecer_monta_a_partir_do_request(): void
    {
        $dto = ParecerDTO::fromRequest([
            'data_parecer' => '2026-08-06',
            'parecer' => 'Favoravel ao pleito.',
            'situacao' => 'favoravel',
            'etapa' => 'analise_dlog',
        ]);

        $this->assertSame('2026-08-06', $dto->dataParecer);
        $this->assertSame(SituacaoParecer::Favoravel, $dto->situacao);
        $this->assertSame(EtapaParecer::AnaliseDlog, $dto->etapa);
    }

    public function test_entrega_monta_e_converte_quantidade(): void
    {
        $dto = EntregaBeneficiarioDTO::fromRequest([
            'prestacao_conta_item_id' => 9,
            'nome_beneficiario' => 'Joao da Silva',
            'rg' => 'MG-12.345.678',
            'comunidade' => 'Zona rural',
            'qtd' => '3',
            'data_entrega' => '2026-08-06',
        ]);

        $this->assertSame(9, $dto->prestacaoContaItemId);
        $this->assertSame('Joao da Silva', $dto->nomeBeneficiario);
        $this->assertSame(3, $dto->qtd);
        $this->assertSame('2026-08-06', $dto->dataEntrega);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=DtosMahTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO" not found`.

- [ ] **Step 3: Implementar os cinco DTOs**

`app/Modules/AjudaHumanitaria/DTOs/PedidoAhDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;

/**
 * Dados de abertura e edicao do pedido (RN-04, RN-05, RN-06).
 *
 * Nao carrega numero nem ano: sao atribuidos pelo NumeracaoPedidoService.
 * Nao carrega status: quem o altera e o TramitacaoService.
 */
final readonly class PedidoAhDTO
{
    public function __construct(
        public int $municipioId,
        public int $popAtendida,
        public string $esforcosRealizados,
        public ?int $cobradeId = null,
        public bool $decretoSeEcpVigente = false,
        public ?TipoDecreto $tipoDecreto = null,
        public ?string $numeroDecreto = null,
        public ?string $vigenciaDecreto = null,
        public ?string $nomeCoordenador = null,
        public ?string $telCoordenador = null,
        public ?string $celCoordenador = null,
        public ?string $emailCoordenador = null,
        public ?string $nomePrefeito = null,
        public ?string $telPrefeito = null,
        public ?string $celPrefeito = null,
        public ?string $emailPrefeito = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $tipo = self::texto($data['tipo_decreto'] ?? null);

        return new self(
            municipioId:         (int) ($data['municipio_id'] ?? 0),
            popAtendida:         (int) ($data['pop_atendida'] ?? 0),
            esforcosRealizados:  (string) ($data['esforcos_realizados'] ?? ''),
            cobradeId:           isset($data['cobrade_id']) ? (int) $data['cobrade_id'] : null,
            decretoSeEcpVigente: (bool) ($data['decreto_se_ecp_vig'] ?? false),
            tipoDecreto:         $tipo !== null ? TipoDecreto::from($tipo) : null,
            numeroDecreto:       self::texto($data['numero_decreto'] ?? null),
            vigenciaDecreto:     self::texto($data['vigencia_decreto'] ?? null),
            nomeCoordenador:     self::texto($data['nome_coordenador'] ?? null),
            telCoordenador:      self::texto($data['tel_coordenador'] ?? null),
            celCoordenador:      self::texto($data['cel_coordenador'] ?? null),
            emailCoordenador:    self::texto($data['email_coordenador'] ?? null),
            nomePrefeito:        self::texto($data['nome_prefeito'] ?? null),
            telPrefeito:         self::texto($data['tel_prefeito'] ?? null),
            celPrefeito:         self::texto($data['cel_prefeito'] ?? null),
            emailPrefeito:       self::texto($data['email_prefeito'] ?? null),
        );
    }

    /**
     * Colunas de pedidos_ah correspondentes. Numero, ano e status ficam a cargo
     * de quem cria ou tramita.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id'         => $this->municipioId,
            'pop_atendida'         => $this->popAtendida,
            'esforcos_realizados'  => $this->esforcosRealizados,
            'cobrade_id'           => $this->cobradeId,
            'decreto_se_ecp_vig'   => $this->decretoSeEcpVigente,
            'tipo_decreto'         => $this->tipoDecreto?->value,
            'numero_decreto'       => $this->numeroDecreto,
            'vigencia_decreto'     => $this->vigenciaDecreto,
            'nome_coordenador'     => $this->nomeCoordenador,
            'tel_coordenador'      => $this->telCoordenador,
            'cel_coordenador'      => $this->celCoordenador,
            'email_coordenador'    => $this->emailCoordenador,
            'nome_prefeito'        => $this->nomePrefeito,
            'tel_prefeito'         => $this->telPrefeito,
            'cel_prefeito'         => $this->celPrefeito,
            'email_prefeito'       => $this->emailPrefeito,
        ];
    }

    private static function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
```

`app/Modules/AjudaHumanitaria/DTOs/ItemPedidoDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;

/**
 * Item do pedido (RN-08). O tipo distingue o solicitado pelo municipio do
 * liberado pelo CEDEC, e por padrao e o solicitado.
 */
final readonly class ItemPedidoDTO
{
    public function __construct(
        public string $descricaoItem,
        public int $qtd,
        public int $qtdFamiliaAtendida,
        public TipoItemPedido $tipo = TipoItemPedido::Pedido,
        public ?int $materialAhId = null,
        public ?string $codigo = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $tipo = isset($data['tipo']) && $data['tipo'] !== ''
            ? TipoItemPedido::from((string) $data['tipo'])
            : TipoItemPedido::Pedido;

        return new self(
            descricaoItem:      (string) ($data['descricao_item'] ?? ''),
            qtd:                (int) ($data['qtd'] ?? 0),
            qtdFamiliaAtendida: (int) ($data['qtd_familia_atendida'] ?? 0),
            tipo:               $tipo,
            materialAhId:       isset($data['material_ah_id']) ? (int) $data['material_ah_id'] : null,
            codigo:             isset($data['codigo']) ? (string) $data['codigo'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'material_ah_id'       => $this->materialAhId,
            'codigo'               => $this->codigo,
            'descricao_item'       => $this->descricaoItem,
            'qtd'                  => $this->qtd,
            'qtd_familia_atendida' => $this->qtdFamiliaAtendida,
            'tipo'                 => $this->tipo->value,
        ];
    }
}
```

`app/Modules/AjudaHumanitaria/DTOs/TransicaoPedidoDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Pedido de mudanca de status (RN-12). A observacao vai para o log de
 * tramitacao (RN-14).
 */
final readonly class TransicaoPedidoDTO
{
    public function __construct(
        public StatusPedidoAh $statusAlvo,
        public ?string $observacao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $observacao = $data['observacao'] ?? null;
        $observacao = $observacao === null ? null : trim((string) $observacao);

        return new self(
            statusAlvo: StatusPedidoAh::from((int) ($data['status_alvo'] ?? -1)),
            observacao: $observacao === '' ? null : $observacao,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status_alvo' => $this->statusAlvo->value,
            'observacao'  => $this->observacao,
        ];
    }
}
```

`app/Modules/AjudaHumanitaria/DTOs/ParecerDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;

/**
 * Parecer tecnico (RN-10).
 */
final readonly class ParecerDTO
{
    public function __construct(
        public string $dataParecer,
        public string $parecer,
        public SituacaoParecer $situacao,
        public EtapaParecer $etapa,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            dataParecer: (string) ($data['data_parecer'] ?? ''),
            parecer:     (string) ($data['parecer'] ?? ''),
            situacao:    SituacaoParecer::from((string) ($data['situacao'] ?? '')),
            etapa:       EtapaParecer::from((string) ($data['etapa'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data_parecer' => $this->dataParecer,
            'parecer'      => $this->parecer,
            'situacao'     => $this->situacao->value,
            'etapa'        => $this->etapa->value,
        ];
    }
}
```

`app/Modules/AjudaHumanitaria/DTOs/EntregaBeneficiarioDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

/**
 * Entrega de material a um beneficiario, dentro de um item da prestacao de
 * contas (RN-17).
 */
final readonly class EntregaBeneficiarioDTO
{
    public function __construct(
        public int $prestacaoContaItemId,
        public string $nomeBeneficiario,
        public int $qtd,
        public string $dataEntrega,
        public ?string $rg = null,
        public ?string $comunidade = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            prestacaoContaItemId: (int) ($data['prestacao_conta_item_id'] ?? 0),
            nomeBeneficiario:     (string) ($data['nome_beneficiario'] ?? ''),
            qtd:                  (int) ($data['qtd'] ?? 0),
            dataEntrega:          (string) ($data['data_entrega'] ?? ''),
            rg:                   self::texto($data['rg'] ?? null),
            comunidade:           self::texto($data['comunidade'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prestacao_conta_item_id' => $this->prestacaoContaItemId,
            'nome_beneficiario'       => $this->nomeBeneficiario,
            'rg'                      => $this->rg,
            'comunidade'              => $this->comunidade,
            'qtd'                     => $this->qtd,
            'data_entrega'            => $this->dataEntrega,
        ];
    }

    private static function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=DtosMahTest
```
Esperado: PASS, 9 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): DTOs do MAH

Cinco objetos imutaveis de entrada, no padrao de TransicaoProcessoDTO do
Tdap: pedido, item, transicao, parecer e entrega a beneficiario.

PedidoAhDTO nao carrega numero, ano nem status de proposito: numeracao e
do NumeracaoPedidoService e status so muda pelo TramitacaoService.
```

---

### Task 2: NumeracaoPedidoService

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/NumeracaoPedidoService.php`
- Test: `tests/Feature/AjudaHumanitaria/NumeracaoPedidoServiceTest.php`

**Interfaces:**
- Consumes: `PedidoAhRepositoryInterface`
- Produces: `NumeracaoPedidoService::__construct(PedidoAhRepositoryInterface $pedidos)`, `proximoNumero(int $ano): int`, `anoCorrente(): int`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/NumeracaoPedidoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Services\NumeracaoPedidoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class NumeracaoPedidoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private NumeracaoPedidoService $servico;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(NumeracaoPedidoService::class);

        $id = DB::table('municipios')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }
        $this->municipioId = (int) $id;
    }

    public function test_ano_virgem_comeca_em_um(): void
    {
        $this->assertSame(1, $this->servico->proximoNumero(2089));
    }

    public function test_numeracao_e_sequencial_por_ano(): void
    {
        PedidoAh::create([
            'numero' => 4, 'ano' => 2088, 'municipio_id' => $this->municipioId,
            'pop_atendida' => 1, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::EdicaoCompdec, 'data_entrada_sistema' => now(),
        ]);

        $this->assertSame(5, $this->servico->proximoNumero(2088));
    }

    public function test_numeracao_reinicia_a_cada_ano(): void
    {
        PedidoAh::create([
            'numero' => 90, 'ano' => 2087, 'municipio_id' => $this->municipioId,
            'pop_atendida' => 1, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::EdicaoCompdec, 'data_entrada_sistema' => now(),
        ]);

        $this->assertSame(1, $this->servico->proximoNumero(2086));
    }

    public function test_pedido_removido_nao_libera_o_numero(): void
    {
        $pedido = PedidoAh::create([
            'numero' => 11, 'ano' => 2085, 'municipio_id' => $this->municipioId,
            'pop_atendida' => 1, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::EdicaoCompdec, 'data_entrada_sistema' => now(),
        ]);

        $pedido->delete();

        $this->assertSame(
            12,
            $this->servico->proximoNumero(2085),
            'Soft delete nao pode reciclar numero: a constraint unique ignora deleted_at.'
        );
    }

    public function test_ano_corrente_vem_do_relogio(): void
    {
        $this->assertSame((int) date('Y'), $this->servico->anoCorrente());
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=NumeracaoPedidoServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\NumeracaoPedidoService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/NumeracaoPedidoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;

/**
 * RN-01: numeracao sequencial por ano.
 *
 * O legado tem dois pares numero mais ano duplicados, sinal de que a regra
 * existia sem ser aplicada. Aqui a unicidade e garantida pela constraint do
 * banco; este servico apenas calcula o proximo valor, e quem cria o pedido
 * trata a violacao com nova tentativa.
 */
final class NumeracaoPedidoService
{
    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
    ) {}

    public function proximoNumero(int $ano): int
    {
        return $this->pedidos->proximoNumeroDoAno($ano);
    }

    public function anoCorrente(): int
    {
        return (int) date('Y');
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=NumeracaoPedidoServiceTest
```
Esperado: PASS, 5 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): numeracao do pedido MAH

Calcula o proximo numero sequencial do ano. A unicidade real fica com a
constraint do banco: o legado tem dois pares numero mais ano duplicados,
sinal de regra declarada e nao aplicada.

Pedido removido nao recicla numero, porque a constraint ignora deleted_at.
```

---

### Task 3: PedidoAhService

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/PedidoAhService.php`
- Test: `tests/Feature/AjudaHumanitaria/PedidoAhServiceTest.php`

**Interfaces:**
- Consumes: `PedidoAhRepositoryInterface`, `NumeracaoPedidoService`, `MunicipioPodeAbrirPedido`, `PedidoAhDTO`
- Produces:
  - `abrir(PedidoAhDTO $dto, ?int $usuarioId): array` devolvendo `[?PedidoAh, ?string]`
  - `atualizar(int $pedidoId, PedidoAhDTO $dto): array` devolvendo `[?PedidoAh, ?string]`
  - `listar(int $perPage, array $filtros): LengthAwarePaginator`
  - `obter(int $pedidoId): PedidoAh`
  - `remover(int $pedidoId): array` devolvendo `[bool, ?string]`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/PedidoAhServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Services\PedidoAhService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PedidoAhServiceTest extends TestCase
{
    use DatabaseTransactions;

    private PedidoAhService $servico;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(PedidoAhService::class);

        $id = DB::table('municipios')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }
        $this->municipioId = (int) $id;

        // Isola o municipio de pedidos preexistentes, para a RN-03 ser testavel.
        PedidoAh::where('municipio_id', $this->municipioId)->forceDelete();
    }

    private function dto(): PedidoAhDTO
    {
        return PedidoAhDTO::fromRequest([
            'municipio_id' => $this->municipioId,
            'pop_atendida' => 1500,
            'esforcos_realizados' => 'Equipe local mobilizada.',
            'nome_coordenador' => 'Maria Coordenadora',
        ]);
    }

    public function test_abre_pedido_em_edicao_com_numeracao_do_ano(): void
    {
        [$pedido, $erro] = $this->servico->abrir($this->dto(), null);

        $this->assertNull($erro);
        $this->assertNotNull($pedido);
        $this->assertSame(StatusPedidoAh::EdicaoCompdec, $pedido->status);
        $this->assertSame((int) date('Y'), $pedido->ano);
        $this->assertGreaterThanOrEqual(1, $pedido->numero);
        $this->assertNotNull($pedido->data_entrada_sistema);
    }

    public function test_bloqueia_segundo_pedido_em_edicao_do_mesmo_municipio(): void
    {
        [$primeiro, $erroPrimeiro] = $this->servico->abrir($this->dto(), null);
        $this->assertNull($erroPrimeiro);
        $this->assertNotNull($primeiro);

        [$segundo, $erroSegundo] = $this->servico->abrir($this->dto(), null);

        $this->assertNull($segundo);
        $this->assertNotNull($erroSegundo);
        $this->assertStringContainsString('edição', (string) $erroSegundo);
    }

    public function test_permite_novo_pedido_quando_o_anterior_saiu_da_edicao(): void
    {
        [$primeiro, ] = $this->servico->abrir($this->dto(), null);
        $primeiro->update(['status' => StatusPedidoAh::AnaliseDlog]);

        [$segundo, $erro] = $this->servico->abrir($this->dto(), null);

        $this->assertNull($erro);
        $this->assertNotNull($segundo);
    }

    public function test_atualiza_pedido_em_edicao(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);

        $novo = PedidoAhDTO::fromRequest([
            'municipio_id' => $this->municipioId,
            'pop_atendida' => 3000,
            'esforcos_realizados' => 'Texto revisado.',
        ]);

        [$atualizado, $erro] = $this->servico->atualizar($pedido->id, $novo);

        $this->assertNull($erro);
        $this->assertSame(3000, $atualizado->pop_atendida);
        $this->assertSame('Texto revisado.', $atualizado->esforcos_realizados);
    }

    public function test_atualizacao_nao_altera_numero_ano_nem_status(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);
        $numeroOriginal = $pedido->numero;

        [$atualizado, ] = $this->servico->atualizar($pedido->id, $this->dto());

        $this->assertSame($numeroOriginal, $atualizado->numero);
        $this->assertSame(StatusPedidoAh::EdicaoCompdec, $atualizado->status);
    }

    public function test_bloqueia_edicao_de_pedido_fora_da_edicao(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);
        $pedido->update(['status' => StatusPedidoAh::AnaliseDlog]);

        [$atualizado, $erro] = $this->servico->atualizar($pedido->id, $this->dto());

        $this->assertNull($atualizado);
        $this->assertNotNull($erro);
    }

    public function test_remove_somente_pedido_em_edicao(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);

        [$removido, $erro] = $this->servico->remover($pedido->id);

        $this->assertTrue($removido);
        $this->assertNull($erro);
        $this->assertSoftDeleted('pedidos_ah', ['id' => $pedido->id]);
    }

    public function test_bloqueia_remocao_de_pedido_tramitado(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);
        $pedido->update(['status' => StatusPedidoAh::AnaliseDlog]);

        [$removido, $erro] = $this->servico->remover($pedido->id);

        $this->assertFalse($removido);
        $this->assertNotNull($erro);
    }

    public function test_lista_filtra_por_municipio_e_status(): void
    {
        [$pedido, ] = $this->servico->abrir($this->dto(), null);

        $pagina = $this->servico->listar(15, [
            'municipio_id' => $this->municipioId,
            'status' => StatusPedidoAh::EdicaoCompdec->value,
        ]);

        $this->assertGreaterThanOrEqual(1, $pagina->total());
        $this->assertTrue($pagina->pluck('id')->contains($pedido->id));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=PedidoAhServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\PedidoAhService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/PedidoAhService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\MunicipioPodeAbrirPedido;
use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de vida do pedido fora da tramitacao: abertura, edicao, remocao e
 * listagem.
 *
 * Nao altera status. Toda mudanca de status passa pelo TramitacaoService.
 */
final class PedidoAhService
{
    /** Tentativas de gravacao sob colisao de numero (RN-01). */
    private const TENTATIVAS_NUMERACAO = 3;

    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
        private readonly NumeracaoPedidoService $numeracao,
        private readonly MunicipioPodeAbrirPedido $podeAbrir,
    ) {}

    /**
     * RN-02 e RN-03.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function abrir(PedidoAhDTO $dto, ?int $usuarioId): array
    {
        $resultado = $this->podeAbrir->verificar(
            $this->pedidos->municipioTemPedidoEmEdicao($dto->municipioId)
        );

        if (! $resultado->permitido) {
            return [null, $resultado->motivo];
        }

        $ano = $this->numeracao->anoCorrente();

        for ($tentativa = 1; $tentativa <= self::TENTATIVAS_NUMERACAO; $tentativa++) {
            try {
                $pedido = PedidoAh::create($dto->toArray() + [
                    'numero'               => $this->numeracao->proximoNumero($ano),
                    'ano'                  => $ano,
                    'status'               => StatusPedidoAh::EdicaoCompdec,
                    'data_entrada_sistema' => now(),
                    'created_by'           => $usuarioId,
                ]);

                return [$pedido, null];
            } catch (UniqueConstraintViolationException $colisao) {
                if ($tentativa === self::TENTATIVAS_NUMERACAO) {
                    return [null, 'Nao foi possível gerar o número do pedido. Tente novamente.'];
                }
            }
        }

        return [null, 'Nao foi possível abrir o pedido.'];
    }

    /**
     * Edicao so e permitida enquanto o pedido esta com o municipio.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function atualizar(int $pedidoId, PedidoAhDTO $dto): array
    {
        $pedido = $this->obter($pedidoId);

        if ($pedido->status !== StatusPedidoAh::EdicaoCompdec) {
            return [null, 'O pedido já foi enviado para análise e não pode mais ser editado.'];
        }

        $pedido->update($dto->toArray());

        return [$pedido->fresh(), null];
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    public function remover(int $pedidoId): array
    {
        $pedido = $this->obter($pedidoId);

        if ($pedido->status !== StatusPedidoAh::EdicaoCompdec) {
            return [false, 'Somente pedido em edição pode ser excluído. Use o cancelamento.'];
        }

        return [(bool) $pedido->delete(), null];
    }

    public function obter(int $pedidoId): PedidoAh
    {
        return PedidoAh::with(['municipio', 'itens'])->findOrFail($pedidoId);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return PedidoAh::query()
            ->with(['municipio:id,nome,uf'])
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when(isset($filtros['status']) && $filtros['status'] !== '', fn ($q) => $q->where('status', (int) $filtros['status']))
            ->when($filtros['ano'] ?? null, fn ($q, $ano) => $q->where('ano', (int) $ano))
            ->when($filtros['cobrade_id'] ?? null, fn ($q, $id) => $q->where('cobrade_id', (int) $id))
            ->when($filtros['search'] ?? null, function ($q, $termo) {
                $q->where(function ($sub) use ($termo) {
                    $sub->where('numero', 'like', "%{$termo}%")
                        ->orWhere('numero_decreto', 'like', "%{$termo}%")
                        ->orWhereHas('municipio', fn ($m) => $m->where('nome', 'like', "%{$termo}%"));
                });
            })
            ->orderByDesc('ano')
            ->orderByDesc('numero')
            ->paginate($perPage)
            ->withQueryString();
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=PedidoAhServiceTest
```
Esperado: PASS, 9 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): ciclo de vida do pedido MAH

Abertura com numeracao anual e nova tentativa sob colisao, edicao restrita
ao periodo em que o pedido esta com o municipio, remocao apenas em edicao
e listagem filtrada.

Cobre RN-02 e RN-03. Nao altera status: isso e exclusivo do
TramitacaoService.
```

---

### Task 4: ItemPedidoService

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/ItemPedidoService.php`
- Test: `tests/Feature/AjudaHumanitaria/ItemPedidoServiceTest.php`

**Interfaces:**
- Consumes: `MaterialAhRepositoryInterface`, `ItemPedidoDTO`, models `PedidoAh`, `PedidoAhItem`
- Produces:
  - `materiaisDisponiveis(): array`
  - `adicionar(int $pedidoId, ItemPedidoDTO $dto): array` devolvendo `[?PedidoAhItem, ?string]`
  - `remover(int $itemId): array` devolvendo `[bool, ?string]`
  - `itensDoPedido(int $pedidoId, ?TipoItemPedido $tipo): Collection`

Regra central desta task, a RN-09: o item tipo Pedido e congelado quando o pedido sai do status de edicao, e o item tipo Liberado so pode ser criado depois disso. As duas representacoes nunca se sobrepoem no tempo, que e como o discriminador substitui a terceira tabela do legado.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/ItemPedidoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Services\ItemPedidoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ItemPedidoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ItemPedidoService $servico;
    private PedidoAh $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(ItemPedidoService::class);

        $municipioId = DB::table('municipios')->value('id');
        if ($municipioId === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }

        $this->pedido = PedidoAh::create([
            'numero' => 980_101, 'ano' => 2084, 'municipio_id' => (int) $municipioId,
            'pop_atendida' => 100, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::EdicaoCompdec, 'data_entrada_sistema' => now(),
        ]);
    }

    private function dtoPedido(int $qtd = 50): ItemPedidoDTO
    {
        return ItemPedidoDTO::fromRequest([
            'descricao_item' => 'Cesta basica',
            'qtd' => $qtd,
            'qtd_familia_atendida' => $qtd,
            'tipo' => TipoItemPedido::Pedido->value,
        ]);
    }

    private function dtoLiberado(int $qtd = 30): ItemPedidoDTO
    {
        return ItemPedidoDTO::fromRequest([
            'descricao_item' => 'Cesta basica',
            'qtd' => $qtd,
            'qtd_familia_atendida' => $qtd,
            'tipo' => TipoItemPedido::Liberado->value,
        ]);
    }

    public function test_lista_materiais_disponiveis(): void
    {
        MaterialAh::query()->update(['disponivel_para_pedido' => false]);
        MaterialAh::create([
            'nome' => 'Cesta basica servico', 'unidade_medida' => 'UN',
            'disponivel_para_pedido' => true,
        ]);

        $materiais = $this->servico->materiaisDisponiveis();

        $this->assertCount(1, $materiais);
        $this->assertSame('Cesta basica servico', $materiais[0]['nome']);
    }

    public function test_adiciona_item_solicitado_com_pedido_em_edicao(): void
    {
        [$item, $erro] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido());

        $this->assertNull($erro);
        $this->assertSame(TipoItemPedido::Pedido, $item->tipo);
        $this->assertSame(50, $item->qtd);
    }

    public function test_bloqueia_item_solicitado_apos_sair_da_edicao(): void
    {
        $this->pedido->update(['status' => StatusPedidoAh::AnaliseDlog]);

        [$item, $erro] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido());

        $this->assertNull($item);
        $this->assertNotNull($erro);
    }

    public function test_bloqueia_item_liberado_enquanto_o_pedido_esta_em_edicao(): void
    {
        [$item, $erro] = $this->servico->adicionar($this->pedido->id, $this->dtoLiberado());

        $this->assertNull($item);
        $this->assertNotNull($erro);
    }

    public function test_permite_item_liberado_apos_a_analise_comecar(): void
    {
        $this->pedido->update(['status' => StatusPedidoAh::AnaliseDiretorDlog]);

        [$item, $erro] = $this->servico->adicionar($this->pedido->id, $this->dtoLiberado());

        $this->assertNull($erro);
        $this->assertSame(TipoItemPedido::Liberado, $item->tipo);
    }

    public function test_bloqueia_quantidade_nao_positiva(): void
    {
        [$item, $erro] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido(qtd: 0));

        $this->assertNull($item);
        $this->assertNotNull($erro);
    }

    public function test_remove_item_solicitado_em_edicao(): void
    {
        [$item, ] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido());

        [$removido, $erro] = $this->servico->remover($item->id);

        $this->assertTrue($removido);
        $this->assertNull($erro);
    }

    public function test_bloqueia_remocao_de_item_solicitado_apos_a_edicao(): void
    {
        [$item, ] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido());
        $this->pedido->update(['status' => StatusPedidoAh::AnaliseDlog]);

        [$removido, $erro] = $this->servico->remover($item->id);

        $this->assertFalse($removido);
        $this->assertNotNull($erro);
    }

    public function test_separa_solicitado_de_liberado(): void
    {
        [$solicitado, ] = $this->servico->adicionar($this->pedido->id, $this->dtoPedido(qtd: 80));
        $this->pedido->update(['status' => StatusPedidoAh::AnaliseDiretorDlog]);
        [$liberado, ] = $this->servico->adicionar($this->pedido->id, $this->dtoLiberado(qtd: 45));

        $todos      = $this->servico->itensDoPedido($this->pedido->id, null);
        $soPedidos  = $this->servico->itensDoPedido($this->pedido->id, TipoItemPedido::Pedido);
        $soLiberado = $this->servico->itensDoPedido($this->pedido->id, TipoItemPedido::Liberado);

        $this->assertCount(2, $todos);
        $this->assertCount(1, $soPedidos);
        $this->assertCount(1, $soLiberado);
        $this->assertSame(80, $soPedidos->first()->qtd, 'RN-09: o solicitado nao e alterado pelo corte do CEDEC.');
        $this->assertSame(45, $soLiberado->first()->qtd);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=ItemPedidoServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\ItemPedidoService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/ItemPedidoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Itens do pedido (RN-07, RN-08, RN-09).
 *
 * O discriminador de tipo substitui a terceira tabela que o legado mantinha
 * para preservar o pedido original. Para que as duas representacoes nunca se
 * sobreponham, este servico as separa no tempo:
 *
 * - o item Solicitado so pode ser criado ou removido enquanto o pedido esta em
 *   edicao com o municipio
 * - o item Liberado so pode ser criado depois que o pedido saiu da edicao
 *
 * Assim o que o municipio pediu fica congelado no instante do envio, e o corte
 * de quantidade do CEDEC nunca o sobrescreve.
 */
final class ItemPedidoService
{
    public function __construct(
        private readonly MaterialAhRepositoryInterface $materiais,
    ) {}

    /**
     * RN-07.
     *
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function materiaisDisponiveis(): array
    {
        return $this->materiais->disponiveisParaPedido();
    }

    /**
     * @return array{0: ?PedidoAhItem, 1: ?string}
     */
    public function adicionar(int $pedidoId, ItemPedidoDTO $dto): array
    {
        if ($dto->qtd <= 0) {
            return [null, 'A quantidade deve ser maior que zero.'];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);
        $emEdicao = $pedido->status === StatusPedidoAh::EdicaoCompdec;

        if ($dto->tipo === TipoItemPedido::Pedido && ! $emEdicao) {
            return [null, 'O pedido já foi enviado para análise; os materiais solicitados não podem mais ser alterados.'];
        }

        if ($dto->tipo === TipoItemPedido::Liberado && $emEdicao) {
            return [null, 'As quantidades liberadas só podem ser definidas depois que o pedido entrar em análise.'];
        }

        $item = PedidoAhItem::create($dto->toArray() + ['pedido_ah_id' => $pedidoId]);

        return [$item, null];
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    public function remover(int $itemId): array
    {
        $item = PedidoAhItem::with('pedido')->findOrFail($itemId);
        $emEdicao = $item->pedido->status === StatusPedidoAh::EdicaoCompdec;

        if ($item->tipo === TipoItemPedido::Pedido && ! $emEdicao) {
            return [false, 'O pedido já foi enviado para análise; os materiais solicitados não podem mais ser alterados.'];
        }

        return [(bool) $item->delete(), null];
    }

    /**
     * @return Collection<int, PedidoAhItem>
     */
    public function itensDoPedido(int $pedidoId, ?TipoItemPedido $tipo = null): Collection
    {
        return PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->when($tipo !== null, fn ($q) => $q->where('tipo', $tipo->value))
            ->orderBy('descricao_item')
            ->get();
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=ItemPedidoServiceTest
```
Esperado: PASS, 9 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): itens do pedido MAH

Materiais disponiveis vindos do catalogo configuravel (RN-07) e gestao dos
itens por tipo (RN-08).

A RN-09 e resolvida separando os dois tipos no tempo: o Solicitado so
existe enquanto o pedido esta com o municipio, o Liberado so depois que
entra em analise. E o que permite o discriminador substituir a terceira
tabela do legado sem risco de sobreposicao.
```

---

### Task 5: ParecerService

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/ParecerService.php`
- Test: `tests/Feature/AjudaHumanitaria/ParecerServiceTest.php`

**Interfaces:**
- Consumes: `PedidoAhRepositoryInterface`, `ParecerDTO`, models `PedidoAh`, `PedidoAhParecer`
- Produces:
  - `emitir(int $pedidoId, ParecerDTO $dto, ?int $usuarioId): array` devolvendo `[?PedidoAhParecer, ?string]`
  - `remover(int $parecerId): bool`
  - `doPedido(int $pedidoId): Collection`
  - `temFavoravel(int $pedidoId): bool`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/ParecerServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\DTOs\ParecerDTO;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Services\ParecerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ParecerServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ParecerService $servico;
    private PedidoAh $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(ParecerService::class);

        $municipioId = DB::table('municipios')->value('id');
        if ($municipioId === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }

        $this->pedido = PedidoAh::create([
            'numero' => 970_101, 'ano' => 2083, 'municipio_id' => (int) $municipioId,
            'pop_atendida' => 100, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::AnaliseDlog, 'data_entrada_sistema' => now(),
        ]);
    }

    private function dto(SituacaoParecer $situacao = SituacaoParecer::Favoravel): ParecerDTO
    {
        return ParecerDTO::fromRequest([
            'data_parecer' => now()->toDateString(),
            'parecer' => 'Analise tecnica do pleito.',
            'situacao' => $situacao->value,
            'etapa' => EtapaParecer::AnaliseDlog->value,
        ]);
    }

    public function test_emite_parecer(): void
    {
        [$parecer, $erro] = $this->servico->emitir($this->pedido->id, $this->dto(), null);

        $this->assertNull($erro);
        $this->assertSame(SituacaoParecer::Favoravel, $parecer->situacao);
        $this->assertSame(EtapaParecer::AnaliseDlog, $parecer->etapa);
        $this->assertSame($this->pedido->id, $parecer->pedido_ah_id);
    }

    public function test_bloqueia_parecer_em_pedido_ainda_com_o_municipio(): void
    {
        $this->pedido->update(['status' => StatusPedidoAh::EdicaoCompdec]);

        [$parecer, $erro] = $this->servico->emitir($this->pedido->id, $this->dto(), null);

        $this->assertNull($parecer);
        $this->assertNotNull($erro);
    }

    public function test_bloqueia_parecer_em_processo_terminal(): void
    {
        $this->pedido->update(['status' => StatusPedidoAh::Finalizado]);

        [$parecer, $erro] = $this->servico->emitir($this->pedido->id, $this->dto(), null);

        $this->assertNull($parecer);
        $this->assertNotNull($erro);
    }

    public function test_bloqueia_parecer_vazio(): void
    {
        $dto = ParecerDTO::fromRequest([
            'data_parecer' => now()->toDateString(),
            'parecer' => '   ',
            'situacao' => SituacaoParecer::Favoravel->value,
            'etapa' => EtapaParecer::AnaliseDlog->value,
        ]);

        [$parecer, $erro] = $this->servico->emitir($this->pedido->id, $dto, null);

        $this->assertNull($parecer);
        $this->assertNotNull($erro);
    }

    public function test_detecta_presenca_de_parecer_favoravel(): void
    {
        $this->assertFalse($this->servico->temFavoravel($this->pedido->id));

        $this->servico->emitir($this->pedido->id, $this->dto(SituacaoParecer::Contrario), null);
        $this->assertFalse($this->servico->temFavoravel($this->pedido->id));

        $this->servico->emitir($this->pedido->id, $this->dto(SituacaoParecer::Favoravel), null);
        $this->assertTrue($this->servico->temFavoravel($this->pedido->id));
    }

    public function test_lista_pareceres_do_pedido(): void
    {
        $this->servico->emitir($this->pedido->id, $this->dto(SituacaoParecer::Contrario), null);
        $this->servico->emitir($this->pedido->id, $this->dto(SituacaoParecer::Favoravel), null);

        $this->assertCount(2, $this->servico->doPedido($this->pedido->id));
    }

    public function test_remove_parecer(): void
    {
        [$parecer, ] = $this->servico->emitir($this->pedido->id, $this->dto(), null);

        $this->assertTrue($this->servico->remover($parecer->id));
        $this->assertCount(0, $this->servico->doPedido($this->pedido->id));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=ParecerServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\ParecerService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/ParecerService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\DTOs\ParecerDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Parecer tecnico (RN-10).
 *
 * O parecer favoravel e o que habilita o avanco da analise DLOG para o
 * Diretor (RN-11), mas quem consulta esse fato na hora da transicao e o
 * TramitacaoService, pelo repositorio.
 */
final class ParecerService
{
    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
    ) {}

    /**
     * @return array{0: ?PedidoAhParecer, 1: ?string}
     */
    public function emitir(int $pedidoId, ParecerDTO $dto, ?int $usuarioId): array
    {
        if (trim($dto->parecer) === '') {
            return [null, 'O texto do parecer é obrigatório.'];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);

        if ($pedido->status === StatusPedidoAh::EdicaoCompdec) {
            return [null, 'O pedido ainda está em edição pelo município e não pode receber parecer.'];
        }

        if ($pedido->status->ehTerminal()) {
            return [null, 'O processo já foi encerrado e não pode receber parecer.'];
        }

        $parecer = PedidoAhParecer::create($dto->toArray() + [
            'pedido_ah_id' => $pedidoId,
            'user_id'      => $usuarioId,
        ]);

        return [$parecer, null];
    }

    public function remover(int $parecerId): bool
    {
        return (bool) PedidoAhParecer::findOrFail($parecerId)->delete();
    }

    /**
     * @return Collection<int, PedidoAhParecer>
     */
    public function doPedido(int $pedidoId): Collection
    {
        return PedidoAhParecer::query()
            ->with('autor:id,name')
            ->where('pedido_ah_id', $pedidoId)
            ->orderByDesc('data_parecer')
            ->get();
    }

    /** RN-11. */
    public function temFavoravel(int $pedidoId): bool
    {
        return $this->pedidos->temParecerFavoravel($pedidoId);
    }
}
```

Nota: `autor:id,name` esta correto — `App\Models\User` tem `name` no `$fillable`, verificado.

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=ParecerServiceTest
```
Esperado: PASS, 7 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): parecer tecnico do MAH

Emissao, remocao e listagem do parecer, com bloqueio em pedido ainda em
edicao pelo municipio e em processo terminal.

Cobre RN-10. A consulta de parecer favoravel que habilita o avanco fica
exposta aqui, mas quem a consome na transicao e o TramitacaoService.
```

---

### Task 6: TramitacaoService

E o centro da fase. Unico ponto do modulo que altera `pedidos_ah.status`.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/TramitacaoService.php`
- Test: `tests/Feature/AjudaHumanitaria/TramitacaoServiceTest.php`

**Interfaces:**
- Consumes: `PedidoAhWorkflow`, `PedidoAhRepositoryInterface`, `PrestacaoContaRepositoryInterface`, `PrazoPrestacaoContas`, `TransicaoPedidoDTO`, models `PedidoAh`, `ParametroAh`
- Produces:
  - `tramitar(int $pedidoId, TransicaoPedidoDTO $dto, ?int $usuarioId): array` devolvendo `[?PedidoAh, ?string]`
  - `finalizarPorHomologacao(int $pedidoId, ?int $usuarioId): array` devolvendo `[?PedidoAh, ?string]`
  - `destinosPossiveis(int $pedidoId): array<int, StatusPedidoAh>`

Efeitos colaterais que a transicao dispara:

| Entrada em | Efeito |
| --- | --- |
| 3 Aprovado | grava `data_aprovacao`, base do prazo da RN-16 |
| 6 Atendido | abre a prestacao de contas e copia os itens Liberado (RN-15), com prazo calculado (RN-16) |

Ponto de atencao vindo do log do legado: o Diretor despacha direto de 2 para 6 em 208 casos, sem passar por Aprovado. Nesses casos `data_aprovacao` e nula e o prazo passa a contar da data do atendimento.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/TramitacaoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Services\TramitacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TramitacaoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private TramitacaoService $servico;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(TramitacaoService::class);

        $id = DB::table('municipios')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }
        $this->municipioId = (int) $id;
    }

    private function pedido(StatusPedidoAh $status, int $numero = 960_101): PedidoAh
    {
        return PedidoAh::create([
            'numero' => $numero, 'ano' => 2082, 'municipio_id' => $this->municipioId,
            'pop_atendida' => 100, 'esforcos_realizados' => 'x',
            'status' => $status, 'data_entrada_sistema' => now(),
        ]);
    }

    private function comItem(PedidoAh $pedido, TipoItemPedido $tipo, int $qtd = 40): void
    {
        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'descricao_item' => 'Cesta basica',
            'qtd' => $qtd, 'qtd_familia_atendida' => $qtd, 'tipo' => $tipo,
        ]);
    }

    private function comParecerFavoravel(PedidoAh $pedido): void
    {
        PedidoAhParecer::create([
            'pedido_ah_id' => $pedido->id, 'data_parecer' => now()->toDateString(),
            'parecer' => 'Favoravel.', 'situacao' => SituacaoParecer::Favoravel,
            'etapa' => EtapaParecer::AnaliseDlog,
        ]);
    }

    private function transicao(StatusPedidoAh $alvo, ?string $obs = null): TransicaoPedidoDTO
    {
        return new TransicaoPedidoDTO(statusAlvo: $alvo, observacao: $obs);
    }

    public function test_envia_pedido_com_item_para_analise(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);
        $this->comItem($pedido, TipoItemPedido::Pedido);

        [$atualizado, $erro] = $this->servico->tramitar(
            $pedido->id,
            $this->transicao(StatusPedidoAh::AnaliseDlog, 'Enviado.'),
            null,
        );

        $this->assertNull($erro);
        $this->assertSame(StatusPedidoAh::AnaliseDlog, $atualizado->status);
    }

    public function test_bloqueia_envio_de_pedido_vazio(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);

        [$atualizado, $erro] = $this->servico->tramitar(
            $pedido->id,
            $this->transicao(StatusPedidoAh::AnaliseDlog),
            null,
        );

        $this->assertNull($atualizado);
        $this->assertStringContainsString('material', (string) $erro);
    }

    public function test_bloqueia_avanco_ao_diretor_sem_parecer_favoravel(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AnaliseDlog);

        [$atualizado, $erro] = $this->servico->tramitar(
            $pedido->id,
            $this->transicao(StatusPedidoAh::AnaliseDiretorDlog),
            null,
        );

        $this->assertNull($atualizado);
        $this->assertStringContainsString('parecer', (string) $erro);
    }

    public function test_permite_avanco_ao_diretor_com_parecer_favoravel(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AnaliseDlog);
        $this->comParecerFavoravel($pedido);

        [$atualizado, $erro] = $this->servico->tramitar(
            $pedido->id,
            $this->transicao(StatusPedidoAh::AnaliseDiretorDlog),
            null,
        );

        $this->assertNull($erro);
        $this->assertSame(StatusPedidoAh::AnaliseDiretorDlog, $atualizado->status);
    }

    public function test_bloqueia_transicao_fora_do_grafo(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);
        $this->comItem($pedido, TipoItemPedido::Pedido);

        [$atualizado, $erro] = $this->servico->tramitar(
            $pedido->id,
            $this->transicao(StatusPedidoAh::Atendido),
            null,
        );

        $this->assertNull($atualizado);
        $this->assertStringContainsString('não é permitida', (string) $erro);
    }

    public function test_toda_transicao_grava_tramite(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);
        $this->comItem($pedido, TipoItemPedido::Pedido);

        $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::AnaliseDlog, 'Observacao registrada.'), null);

        $tramite = PedidoAhTramite::where('pedido_ah_id', $pedido->id)->firstOrFail();

        $this->assertSame(StatusPedidoAh::EdicaoCompdec, $tramite->status_anterior);
        $this->assertSame(StatusPedidoAh::AnaliseDlog, $tramite->status_novo);
        $this->assertSame('Observacao registrada.', $tramite->observacao);
    }

    public function test_transicao_bloqueada_nao_grava_tramite(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);

        $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::AnaliseDlog), null);

        $this->assertSame(0, PedidoAhTramite::where('pedido_ah_id', $pedido->id)->count());
    }

    public function test_aprovacao_carimba_data_de_aprovacao(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AnaliseDiretorDlog);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Aprovado), null);

        $this->assertNull($erro);
        $this->assertNotNull($atualizado->data_aprovacao);
    }

    public function test_atendimento_abre_prestacao_e_copia_itens_liberados(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AguardandoRetirada);
        $this->comItem($pedido, TipoItemPedido::Pedido, qtd: 100);
        $this->comItem($pedido, TipoItemPedido::Liberado, qtd: 60);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Atendido), null);

        $this->assertNull($erro);
        $this->assertSame(StatusPedidoAh::Atendido, $atualizado->status);

        $prestacao = PrestacaoConta::where('pedido_ah_id', $pedido->id)->firstOrFail();

        $this->assertSame(StatusPrestacaoConta::Pendente, $prestacao->status);
        $this->assertNotNull($prestacao->data_limite);
        $this->assertCount(1, $prestacao->itens, 'RN-15: copia apenas o tipo Liberado.');
        $this->assertSame(60, $prestacao->itens->first()->qtd);
    }

    public function test_bloqueia_atendimento_sem_itens_liberados(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AguardandoRetirada);
        $this->comItem($pedido, TipoItemPedido::Pedido);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Atendido), null);

        $this->assertNull($atualizado);
        $this->assertNotNull($erro);
        $this->assertSame(0, PrestacaoConta::where('pedido_ah_id', $pedido->id)->count());
    }

    public function test_despacho_direto_do_diretor_para_atendido(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::AnaliseDiretorDlog);
        $this->comItem($pedido, TipoItemPedido::Liberado, qtd: 25);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Atendido), null);

        $this->assertNull($erro, 'Ocorre 208 vezes no legado.');
        $this->assertSame(StatusPedidoAh::Atendido, $atualizado->status);

        $prestacao = PrestacaoConta::where('pedido_ah_id', $pedido->id)->firstOrFail();
        $this->assertNotNull(
            $prestacao->data_limite,
            'Sem data_aprovacao, o prazo conta a partir do atendimento.'
        );
    }

    public function test_prazo_da_prestacao_usa_o_parametro_do_modulo(): void
    {
        ParametroAh::atual()->update(['prazo_prestacao_contas_dias' => 45]);

        $pedido = $this->pedido(StatusPedidoAh::Aprovado);
        $this->comItem($pedido, TipoItemPedido::Liberado);
        $pedido->update(['data_aprovacao' => now()]);

        $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Atendido), null);

        $prestacao = PrestacaoConta::where('pedido_ah_id', $pedido->id)->firstOrFail();

        $this->assertSame(
            now()->startOfDay()->addDays(45)->toDateString(),
            $prestacao->data_limite->toDateString(),
        );
    }

    public function test_finalizacao_manual_e_recusada(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::Atendido);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::Finalizado), null);

        $this->assertNull($atualizado);
        $this->assertStringContainsString('homologação', (string) $erro);
    }

    public function test_finalizacao_por_homologacao_e_aceita(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::Atendido);

        [$atualizado, $erro] = $this->servico->finalizarPorHomologacao($pedido->id, null);

        $this->assertNull($erro);
        $this->assertSame(StatusPedidoAh::Finalizado, $atualizado->status);
    }

    public function test_reabertura_de_atendido_para_analise(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::Atendido);

        [$atualizado, $erro] = $this->servico->tramitar($pedido->id, $this->transicao(StatusPedidoAh::AnaliseDlog), null);

        $this->assertNull($erro, 'Ocorre 27 vezes no legado.');
        $this->assertSame(StatusPedidoAh::AnaliseDlog, $atualizado->status);
    }

    public function test_destinos_possiveis_do_pedido(): void
    {
        $pedido = $this->pedido(StatusPedidoAh::EdicaoCompdec);

        $destinos = $this->servico->destinosPossiveis($pedido->id);

        $this->assertEqualsCanonicalizing(
            [StatusPedidoAh::AnaliseDlog, StatusPedidoAh::Cancelado],
            $destinos,
        );
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=TramitacaoServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\TramitacaoService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/TramitacaoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\PrazoPrestacaoContas;
use App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Unico ponto do modulo que altera o status do pedido.
 *
 * A decisao sobre a validade da transicao nao mora aqui: e delegada ao
 * PedidoAhWorkflow, que combina o grafo do enum com as guardas. Este servico
 * monta os fatos que o workflow precisa, persiste o resultado e dispara os
 * efeitos colaterais.
 */
final class TramitacaoService
{
    public function __construct(
        private readonly PedidoAhWorkflow $workflow,
        private readonly PedidoAhRepositoryInterface $pedidos,
        private readonly PrestacaoContaRepositoryInterface $prestacoes,
        private readonly PrazoPrestacaoContas $prazo,
    ) {}

    /**
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function tramitar(int $pedidoId, TransicaoPedidoDTO $dto, ?int $usuarioId): array
    {
        return $this->executar($pedidoId, $dto->statusAlvo, $dto->observacao, $usuarioId, viaHomologacao: false);
    }

    /**
     * RN-19: o unico caminho para Finalizado.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function finalizarPorHomologacao(int $pedidoId, ?int $usuarioId): array
    {
        return $this->executar(
            $pedidoId,
            StatusPedidoAh::Finalizado,
            'Prestação de contas homologada.',
            $usuarioId,
            viaHomologacao: true,
        );
    }

    /**
     * Destinos existentes no grafo, sem avaliar condicao. Serve para montar a
     * lista de opcoes na interface; a validade final e sempre confirmada por
     * tramitar().
     *
     * @return array<int, StatusPedidoAh>
     */
    public function destinosPossiveis(int $pedidoId): array
    {
        return $this->workflow->destinosPossiveis(
            PedidoAh::findOrFail($pedidoId)->status
        );
    }

    /**
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    private function executar(
        int $pedidoId,
        StatusPedidoAh $alvo,
        ?string $observacao,
        ?int $usuarioId,
        bool $viaHomologacao,
    ): array {
        $pedido = PedidoAh::findOrFail($pedidoId);
        $origem = $pedido->status;

        $contexto = new ContextoTransicao(
            statusAtual:         $origem,
            statusAlvo:          $alvo,
            temItemPedido:       $this->pedidos->contarItensPorTipo($pedidoId, TipoItemPedido::Pedido) > 0,
            temParecerFavoravel: $this->pedidos->temParecerFavoravel($pedidoId),
            temItemLiberado:     $this->pedidos->contarItensPorTipo($pedidoId, TipoItemPedido::Liberado) > 0,
            viaHomologacao:      $viaHomologacao,
        );

        $veredito = $this->workflow->verificar($contexto);

        if (! $veredito->permitido) {
            return [null, $veredito->motivo];
        }

        DB::transaction(function () use ($pedido, $pedidoId, $origem, $alvo, $observacao, $usuarioId): void {
            $this->pedidos->atualizarStatus($pedidoId, $alvo);
            $this->pedidos->registrarTramite($pedidoId, $origem, $alvo, $observacao, $usuarioId);

            if ($alvo === StatusPedidoAh::Aprovado && $pedido->data_aprovacao === null) {
                $pedido->forceFill(['data_aprovacao' => now()])->save();
            }

            if ($alvo === StatusPedidoAh::Atendido) {
                $this->abrirPrestacao($pedido);
            }
        });

        return [$pedido->fresh(), null];
    }

    /**
     * RN-15 e RN-16.
     *
     * O prazo conta da data de aprovacao. Quando o Diretor despacha direto para
     * Atendido, sem passar por Aprovado, essa data e nula e o prazo passa a
     * contar do proprio atendimento. O legado registra 208 despachos assim.
     */
    private function abrirPrestacao(PedidoAh $pedido): void
    {
        if ($pedido->prestacaoConta()->exists()) {
            return;
        }

        $base = $pedido->data_aprovacao !== null
            ? CarbonImmutable::parse($pedido->data_aprovacao)
            : CarbonImmutable::now();

        $dias = ParametroAh::atual()->prazo_prestacao_contas_dias;

        $prestacaoId = $this->prestacoes->abrirParaPedido(
            $pedido->id,
            $this->prazo->calcular($base, $dias),
        );

        $this->prestacoes->copiarItensLiberados($pedido->id, $prestacaoId);
    }
}
```

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=TramitacaoServiceTest
```
Esperado: PASS, 16 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): tramitacao do pedido MAH

Unico ponto do modulo que altera status. Monta o contexto de fatos, delega
a decisao ao PedidoAhWorkflow, persiste e dispara os efeitos colaterais:
carimbo da data de aprovacao e abertura da prestacao de contas com copia
dos itens liberados.

Cobre RN-12, RN-14, RN-15 e RN-16. Transicao recusada nao grava tramite.

Trata o despacho direto do Diretor para Atendido, 208 casos no legado, em
que nao ha data de aprovacao e o prazo da prestacao conta do atendimento.
```

---

### Task 7: PrestacaoContasService

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Services/PrestacaoContasService.php`
- Test: `tests/Feature/AjudaHumanitaria/PrestacaoContasServiceTest.php`

**Interfaces:**
- Consumes: `PrestacaoContaRepositoryInterface`, `SaldoEntregaBeneficiarios`, `PrazoPrestacaoContas`, `TramitacaoService`, `EntregaBeneficiarioDTO`
- Produces:
  - `lancarEntrega(EntregaBeneficiarioDTO $dto): array` devolvendo `[?PrestacaoContaEntrega, ?string]`
  - `removerEntrega(int $entregaId): bool`
  - `saldoDoItem(int $itemId): int`
  - `homologar(int $prestacaoId, ?int $usuarioId): array` devolvendo `[bool, ?string]`
  - `estaVencida(int $prestacaoId): bool`

`homologar` e o unico consumidor de `TramitacaoService::finalizarPorHomologacao`. E a excecao a regra de servicos nao chamarem servicos, e existe porque a RN-19 amarra as duas coisas: homologar a prestacao e finalizar o processo sao o mesmo ato.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/PrestacaoContasServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use App\Modules\AjudaHumanitaria\Services\PrestacaoContasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PrestacaoContasServiceTest extends TestCase
{
    use DatabaseTransactions;

    private PrestacaoContasService $servico;
    private PedidoAh $pedido;
    private PrestacaoConta $prestacao;
    private PrestacaoContaItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(PrestacaoContasService::class);

        $municipioId = DB::table('municipios')->value('id');
        if ($municipioId === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios.');
        }

        $this->pedido = PedidoAh::create([
            'numero' => 950_101, 'ano' => 2081, 'municipio_id' => (int) $municipioId,
            'pop_atendida' => 100, 'esforcos_realizados' => 'x',
            'status' => StatusPedidoAh::Atendido, 'data_entrada_sistema' => now(),
        ]);

        $this->prestacao = PrestacaoConta::create([
            'pedido_ah_id' => $this->pedido->id,
            'status' => StatusPrestacaoConta::Pendente,
            'data_limite' => now()->addDays(30)->toDateString(),
        ]);

        $this->item = PrestacaoContaItem::create([
            'prestacao_conta_id' => $this->prestacao->id,
            'nome_material' => 'Cesta basica',
            'qtd' => 50,
            'total_familia_atendida' => 50,
        ]);
    }

    private function entrega(int $qtd): EntregaBeneficiarioDTO
    {
        return EntregaBeneficiarioDTO::fromRequest([
            'prestacao_conta_item_id' => $this->item->id,
            'nome_beneficiario' => 'Beneficiario Teste',
            'rg' => 'MG-1234567',
            'comunidade' => 'Centro',
            'qtd' => $qtd,
            'data_entrega' => now()->toDateString(),
        ]);
    }

    public function test_lanca_entrega_dentro_do_saldo(): void
    {
        [$entrega, $erro] = $this->servico->lancarEntrega($this->entrega(20));

        $this->assertNull($erro);
        $this->assertSame(20, $entrega->qtd);
        $this->assertSame('Beneficiario Teste', $entrega->nome_beneficiario);
    }

    public function test_saldo_diminui_a_cada_entrega(): void
    {
        $this->assertSame(50, $this->servico->saldoDoItem($this->item->id));

        $this->servico->lancarEntrega($this->entrega(20));
        $this->assertSame(30, $this->servico->saldoDoItem($this->item->id));

        $this->servico->lancarEntrega($this->entrega(30));
        $this->assertSame(0, $this->servico->saldoDoItem($this->item->id));
    }

    public function test_bloqueia_entrega_acima_do_saldo(): void
    {
        $this->servico->lancarEntrega($this->entrega(45));

        [$entrega, $erro] = $this->servico->lancarEntrega($this->entrega(10));

        $this->assertNull($entrega);
        $this->assertNotNull($erro);
        $this->assertStringContainsString('5', (string) $erro, 'A mensagem informa o saldo restante.');
    }

    public function test_bloqueia_entrega_de_quantidade_nao_positiva(): void
    {
        [$entrega, $erro] = $this->servico->lancarEntrega($this->entrega(0));

        $this->assertNull($entrega);
        $this->assertNotNull($erro);
    }

    public function test_primeiro_lancamento_move_prestacao_para_em_lancamento(): void
    {
        $this->servico->lancarEntrega($this->entrega(5));

        $this->assertSame(
            StatusPrestacaoConta::EmLancamento,
            $this->prestacao->fresh()->status,
        );
    }

    public function test_remove_entrega_e_devolve_saldo(): void
    {
        [$entrega, ] = $this->servico->lancarEntrega($this->entrega(20));

        $this->assertTrue($this->servico->removerEntrega($entrega->id));
        $this->assertSame(50, $this->servico->saldoDoItem($this->item->id));
    }

    public function test_homologa_e_finaliza_o_processo(): void
    {
        $this->servico->lancarEntrega($this->entrega(50));

        [$ok, $erro] = $this->servico->homologar($this->prestacao->id, null);

        $this->assertTrue($ok);
        $this->assertNull($erro);
        $this->assertSame(StatusPrestacaoConta::Homologada, $this->prestacao->fresh()->status);
        $this->assertSame(StatusPedidoAh::Finalizado, $this->pedido->fresh()->status);
    }

    public function test_bloqueia_homologacao_com_saldo_pendente(): void
    {
        $this->servico->lancarEntrega($this->entrega(10));

        [$ok, $erro] = $this->servico->homologar($this->prestacao->id, null);

        $this->assertFalse($ok);
        $this->assertNotNull($erro);
        $this->assertSame(StatusPedidoAh::Atendido, $this->pedido->fresh()->status);
    }

    public function test_detecta_prestacao_vencida(): void
    {
        $this->assertFalse($this->servico->estaVencida($this->prestacao->id));

        $this->prestacao->update(['data_limite' => now()->subDay()->toDateString()]);

        $this->assertTrue($this->servico->estaVencida($this->prestacao->id));
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```
TESTAR --filter=PrestacaoContasServiceTest
```
Esperado: FAIL, `Class "App\Modules\AjudaHumanitaria\Services\PrestacaoContasService" not found`.

- [ ] **Step 3: Implementar**

`app/Modules/AjudaHumanitaria/Services/PrestacaoContasService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\PrazoPrestacaoContas;
use App\Modules\AjudaHumanitaria\Domain\Specifications\SaldoEntregaBeneficiarios;
use App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Prestacao de contas (RN-17, RN-18, RN-19).
 *
 * A abertura da prestacao nao acontece aqui: e efeito da entrada do pedido em
 * Atendido, disparada pelo TramitacaoService (RN-15).
 */
final class PrestacaoContasService
{
    public function __construct(
        private readonly PrestacaoContaRepositoryInterface $prestacoes,
        private readonly SaldoEntregaBeneficiarios $saldo,
        private readonly PrazoPrestacaoContas $prazo,
        private readonly TramitacaoService $tramitacao,
    ) {}

    /**
     * RN-17 e RN-18.
     *
     * @return array{0: ?PrestacaoContaEntrega, 1: ?string}
     */
    public function lancarEntrega(EntregaBeneficiarioDTO $dto): array
    {
        $item = PrestacaoContaItem::findOrFail($dto->prestacaoContaItemId);

        $veredito = $this->saldo->verificar(
            $this->prestacoes->quantidadeDoItem($item->id),
            $this->prestacoes->quantidadeJaEntregue($item->id),
            $dto->qtd,
        );

        if (! $veredito->permitido) {
            return [null, $veredito->motivo];
        }

        $entrega = DB::transaction(function () use ($dto, $item): PrestacaoContaEntrega {
            $entrega = PrestacaoContaEntrega::create($dto->toArray());

            PrestacaoConta::query()
                ->whereKey($item->prestacao_conta_id)
                ->where('status', StatusPrestacaoConta::Pendente->value)
                ->update(['status' => StatusPrestacaoConta::EmLancamento->value]);

            return $entrega;
        });

        return [$entrega, null];
    }

    public function removerEntrega(int $entregaId): bool
    {
        return (bool) PrestacaoContaEntrega::findOrFail($entregaId)->delete();
    }

    /** RN-18: quanto ainda falta entregar do item. */
    public function saldoDoItem(int $itemId): int
    {
        return $this->saldo->saldo(
            $this->prestacoes->quantidadeDoItem($itemId),
            $this->prestacoes->quantidadeJaEntregue($itemId),
        );
    }

    /**
     * RN-19: homologar a prestacao e finalizar o processo sao o mesmo ato.
     *
     * @return array{0: bool, 1: ?string}
     */
    public function homologar(int $prestacaoId, ?int $usuarioId): array
    {
        $prestacao = PrestacaoConta::with('itens')->findOrFail($prestacaoId);

        foreach ($prestacao->itens as $item) {
            if ($this->saldoDoItem($item->id) > 0) {
                return [false, "Ainda há saldo pendente de entrega em \"{$item->nome_material}\"."];
            }
        }

        [$pedido, $erro] = $this->tramitacao->finalizarPorHomologacao(
            $prestacao->pedido_ah_id,
            $usuarioId,
        );

        if ($erro !== null) {
            return [false, $erro];
        }

        $this->prestacoes->homologar($prestacaoId, (int) $usuarioId);

        return [true, null];
    }

    /** RN-16. */
    public function estaVencida(int $prestacaoId): bool
    {
        $prestacao = PrestacaoConta::findOrFail($prestacaoId);

        if ($prestacao->data_limite === null) {
            return false;
        }

        return $this->prazo->estaVencido(
            CarbonImmutable::parse($prestacao->data_limite),
            CarbonImmutable::now(),
        );
    }
}
```

Atencao: `$this->prestacoes->homologar($prestacaoId, (int) $usuarioId)` recebe `int` pelo contrato, mas o servico aceita `?int`. Se `homologar` for chamado sem usuario autenticado, `(int) null` vira `0`, o que violaria a foreign key de `homologado_por`. Ajuste o contrato e a implementacao da fase 2a para `?int $usuarioId`, do mesmo modo que foi feito com `registrarTramite`, e passe `$usuarioId` direto.

- [ ] **Step 4: Rodar e confirmar que passa**

```
TESTAR --filter=PrestacaoContasServiceTest
```
Esperado: PASS, 9 testes.

- [ ] **Step 5: Commit**

```
✨ feat(ajuda-humanitaria): prestacao de contas do MAH

Lancamento de entrega a beneficiario com controle de saldo, remocao,
consulta de saldo restante, deteccao de vencimento e homologacao.

Cobre RN-17, RN-18 e RN-19. A homologacao so passa com todos os itens
zerados e finaliza o processo pelo TramitacaoService: o legado permitia
entrega acima do material, e ha duas prestacoes historicas nessa situacao.
```

---

## Verificacao da fase

1. `TESTAR tests/Unit/AjudaHumanitaria` e `TESTAR tests/Feature/AjudaHumanitaria` passam
2. Fluxo completo exercitavel por teste: abrir, adicionar item, enviar, emitir parecer, avancar, liberar item, atender, lancar entrega, homologar
3. Nenhum servico alem de `TramitacaoService` escreve em `pedidos_ah.status` — confira com `rg "atualizarStatus|'status'" app/Modules/AjudaHumanitaria/Services`
4. Suite completa mantem exatamente as cinco falhas pre-existentes
5. Nenhum arquivo do mock foi removido

## Regras cobertas nesta fase

RN-02, RN-03, RN-07, RN-08, RN-09, RN-10, RN-12, RN-14, RN-15, RN-16, RN-17, RN-18, RN-19.

Ficam para a fase 2c: RN-04 e RN-05 (validacao de formulario), RN-20 e RN-24 (policy), RN-22 (anexos), RN-23 (permissoes), RN-25 (exposicao do saldo na tela).
