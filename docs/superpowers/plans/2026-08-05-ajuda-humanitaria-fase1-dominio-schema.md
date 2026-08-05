# Ajuda Humanitaria (MAH) - Fase 1: Dominio e Schema - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir a camada de dominio isolada e o schema do processo de Pedido de Material de Ajuda Humanitaria (MAH), de forma puramente aditiva, sem remover nada do mock existente.

**Architecture:** As regras de negocio ficam em classes puras sob `app/Modules/AjudaHumanitaria/Domain`, sem Eloquent e sem facades, recebendo fatos por meio de um contexto imutavel montado pela camada de aplicacao. A maquina de estados vive no enum `StatusPedidoAh` (padrao ja usado por `App\Modules\Tdap\Enums\EstadoProcesso`) e as condicoes de transicao em guardas que implementam um contrato unico. O schema das dez tabelas entra em uma migration consolidada.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL, PHPUnit 11.

## Global Constraints

- Todo arquivo PHP novo comeca com `<?php` seguido de linha em branco e `declare(strict_types=1);`
- Namespace raiz do modulo: `App\Modules\AjudaHumanitaria`
- Proibido emoji em codigo
- Proibido acento em nome de classe, metodo, propriedade, arquivo, coluna de banco e chave de array. Acento e permitido apenas em valor de string destinado a exibicao
- Nada sob `Domain/` pode importar `Illuminate\*`, `App\Models\*`, nem qualquer Model Eloquent. A unica dependencia externa permitida em `Domain/` e `Carbon\CarbonImmutable`
- Fase aditiva: nenhum arquivo existente pode ser removido. As unicas modificacoes permitidas em arquivos existentes sao as descritas nas Tasks 9 e 10
- **Runner de teste.** Nenhum comando documentado no repositorio funciona neste
  ambiente. Foram verificados um por um:

  | Tentativa | Resultado |
  | --- | --- |
  | `php artisan test` | PHP do PATH e 8.1, incompativel com Laravel 12 |
  | `docker exec newsdc_dev_app ... phpunit` | container sem PHPUnit, instalado sem dev-deps |
  | `docker exec newsdc_frankenphp_local ...` | container nao existe mais; nome desatualizado na documentacao |
  | PHP 8.3 do Laragon, direto | `PDOException: could not find driver`, pdo_pgsql nao habilitado |
  | com pdo_pgsql habilitado por `-d` | timeout: config cacheado aponta para `host=db, port=5432`, nomes internos do container |
  | com `APP_CONFIG_CACHE` inexistente | passa a respeitar `.env.testing`, que e sqlite `:memory:` |
  | sqlite `:memory:` com `RefreshDatabase` | migrations do projeto nao rodam em SQLite; `2025_12_26_add_orgao_fk_to_users_table` consulta `INFORMATION_SCHEMA` do MySQL em SQL cru |
  | Postgres em `127.0.0.1:5434` com credenciais do `.env` | funciona |

  O comando canonico, a partir de `SDC/`, e:

  ```powershell
  $php = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
  $ext = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\ext"
  $dot = @{}
  Get-Content .env | Where-Object { $_ -match '^\s*DB_(USERNAME|PASSWORD|DATABASE)\s*=' } | ForEach-Object {
      $par = $_ -split '=', 2
      $dot[$par[0].Trim()] = $par[1].Trim().Trim('"')
  }
  $env:APP_CONFIG_CACHE = "$env:TEMP\sem-cache-newsdc.php"
  $env:DB_CONNECTION = "pgsql"
  $env:DB_HOST = "127.0.0.1"
  $env:DB_PORT = "5434"
  $env:DB_DATABASE = $dot['DB_DATABASE']
  $env:DB_USERNAME = $dot['DB_USERNAME']
  $env:DB_PASSWORD = $dot['DB_PASSWORD']
  & $php -d "extension_dir=$ext" -d "extension=php_pgsql.dll" -d "extension=php_pdo_pgsql.dll" `
      vendor/bin/phpunit <argumentos>
  ```

  `APP_CONFIG_CACHE` aponta para arquivo inexistente de proposito: faz o Laravel
  ler configuracao fresca sem apagar `bootstrap/cache/config.php`, que e
  compartilhado com o container em execucao. Nao rodar `artisan config:clear`,
  que derrubaria o cache do ambiente de desenvolvimento do usuario.

  Nos passos seguintes, `TESTAR` designa esse bloco. Salve-o em um `.ps1` fora
  do repositorio e invoque com os argumentos indicados.

- **Trait de banco nos testes.** Usar `Illuminate\Foundation\Testing\DatabaseTransactions`,
  nunca `RefreshDatabase`. As migrations do projeto nao rodam em SQLite e
  `RefreshDatabase` sobre o Postgres de desenvolvimento apagaria o banco do
  usuario. `DatabaseTransactions` e a convencao real do projeto: nenhum teste
  existente usa `RefreshDatabase`.
- Como os testes de banco rodam sobre o Postgres de desenvolvimento, a migration
  da Task 8 precisa estar aplicada nele antes dos testes das Tasks 8, 9 e 10
- Commits seguem gitmoji: `<emoji> tipo(escopo): descricao em pt-BR`. Escopo desta fase: `ajuda-humanitaria`
- Nunca incluir trailer `Co-Authored-By` em commit

## Matriz normativa de transicoes

Referencia unica para as Tasks 2, 4 e 5. Origem: secao 4.4.1 da spec.

| De | Para | Condicao |
| --- | --- | --- |
| 0 EdicaoCompdec | 1 AnaliseDlog | tem ao menos um item tipo Pedido |
| 0 EdicaoCompdec | 7 Cancelado | nenhuma |
| 1 AnaliseDlog | 2 AnaliseDiretorDlog | tem ao menos um parecer favoravel |
| 1 AnaliseDlog | 0 EdicaoCompdec | nenhuma |
| 1 AnaliseDlog | 8 Reprovado | nenhuma |
| 1 AnaliseDlog | 7 Cancelado | nenhuma |
| 2 AnaliseDiretorDlog | 3 Aprovado | tem ao menos um item tipo Liberado |
| 2 AnaliseDiretorDlog | 1 AnaliseDlog | nenhuma |
| 2 AnaliseDiretorDlog | 8 Reprovado | nenhuma |
| 2 AnaliseDiretorDlog | 7 Cancelado | nenhuma |
| 3 Aprovado | 4 AguardandoDisponibilidade | nenhuma |
| 3 Aprovado | 7 Cancelado | nenhuma |
| 4 AguardandoDisponibilidade | 5 AguardandoRetirada | nenhuma |
| 4 AguardandoDisponibilidade | 7 Cancelado | nenhuma |
| 5 AguardandoRetirada | 6 Atendido | agendamento de retirada aprovado |
| 5 AguardandoRetirada | 7 Cancelado | nenhuma |
| 6 Atendido | 9 Finalizado | acionado via homologacao da prestacao |
| 6 Atendido | 7 Cancelado | nenhuma |
| 7 Cancelado | - | terminal |
| 8 Reprovado | - | terminal |
| 9 Finalizado | - | terminal |

## Estrutura de arquivos

Criados nesta fase, todos sob `SDC/`:

| Arquivo | Responsabilidade |
| --- | --- |
| `app/Modules/AjudaHumanitaria/Enums/TipoItemPedido.php` | Discrimina item pedido de item liberado (RN-08) |
| `app/Modules/AjudaHumanitaria/Enums/SituacaoParecer.php` | Favoravel ou contrario (RN-10) |
| `app/Modules/AjudaHumanitaria/Enums/EtapaParecer.php` | Etapa a que o parecer pertence (RN-10) |
| `app/Modules/AjudaHumanitaria/Enums/TipoDecreto.php` | SE ou ECP (RN-06) |
| `app/Modules/AjudaHumanitaria/Enums/StatusAgendamento.php` | Pendente, aprovado, recusado (RN-21) |
| `app/Modules/AjudaHumanitaria/Enums/StatusPrestacaoConta.php` | Pendente, em lancamento, homologada (RN-19) |
| `app/Modules/AjudaHumanitaria/Enums/FasePedidoAh.php` | Slug da fase, derivado do status (RN-13) |
| `app/Modules/AjudaHumanitaria/Enums/StatusPedidoAh.php` | Maquina de estados: label, fase, cor, transicoes (RN-12, RN-13) |
| `app/Modules/AjudaHumanitaria/Domain/Contracts/ResultadoGuarda.php` | Resultado de uma verificacao: permitido mais motivo |
| `app/Modules/AjudaHumanitaria/Domain/Contracts/ContextoTransicao.php` | Fatos necessarios para decidir uma transicao, imutavel e puro |
| `app/Modules/AjudaHumanitaria/Domain/Contracts/GuardaTransicao.php` | Contrato unico de guarda de transicao |
| `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItemNoPedido.php` | Guarda 0 para 1 |
| `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeParecerFavoravel.php` | Guarda 1 para 2 (RN-11) |
| `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItensLiberados.php` | Guarda 2 para 3 |
| `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeAgendamentoAprovado.php` | Guarda 5 para 6 (RN-21) |
| `app/Modules/AjudaHumanitaria/Domain/Guards/FinalizacaoSomenteViaHomologacao.php` | Guarda 6 para 9 (RN-19) |
| `app/Modules/AjudaHumanitaria/Domain/PedidoAhWorkflow.php` | Compoe enum e guardas; unica autoridade sobre transicao valida |
| `app/Modules/AjudaHumanitaria/Domain/Specifications/MunicipioPodeAbrirPedido.php` | RN-03 |
| `app/Modules/AjudaHumanitaria/Domain/Specifications/PrazoPrestacaoContas.php` | RN-16 |
| `app/Modules/AjudaHumanitaria/Domain/Specifications/SaldoEntregaBeneficiarios.php` | RN-18 |
| `app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php` | Contrato de persistencia do pedido |
| `app/Modules/AjudaHumanitaria/Domain/Repositories/PrestacaoContaRepositoryInterface.php` | Contrato de persistencia da prestacao |
| `app/Modules/AjudaHumanitaria/Domain/Repositories/MaterialAhRepositoryInterface.php` | Contrato do catalogo de material |
| `app/Modules/AjudaHumanitaria/Domain/Repositories/SaldoMaterialRepositoryInterface.php` | Contrato de leitura de saldo (RN-25) |
| `database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php` | As dez tabelas, consolidadas |
| `app/Modules/AjudaHumanitaria/Models/PedidoAh.php` | Raiz do agregado |
| `app/Modules/AjudaHumanitaria/Models/PedidoAhItem.php` | Item do pedido |
| `app/Modules/AjudaHumanitaria/Models/PedidoAhParecer.php` | Parecer tecnico |
| `app/Modules/AjudaHumanitaria/Models/PedidoAhTramite.php` | Log de tramitacao |
| `app/Modules/AjudaHumanitaria/Models/PedidoAhAgendamento.php` | Agendamento de retirada |
| `app/Modules/AjudaHumanitaria/Models/PrestacaoConta.php` | Cabecalho da prestacao |
| `app/Modules/AjudaHumanitaria/Models/PrestacaoContaItem.php` | Material da prestacao |
| `app/Modules/AjudaHumanitaria/Models/PrestacaoContaEntrega.php` | Entrega a beneficiario |
| `app/Modules/AjudaHumanitaria/Models/MaterialAh.php` | Catalogo de material (RN-07) |
| `app/Modules/AjudaHumanitaria/Models/ParametroAh.php` | Parametros do modulo (RN-16) |

Modificados nesta fase:

| Arquivo | Alteracao |
| --- | --- |
| `app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php` | Remove os dois binds para classes inexistentes; registra `PedidoAhWorkflow` com as cinco guardas |

Decisao de schema: `pedidos_ah` nao carrega `regiao_id`. Nao existe mapeamento municipio para REDEC no NewSDC (`rat_redec` tem apenas id, nome e sigla, sem vinculo com municipio) e `municipios.regiao` e `municipios.mesorregiao` sao strings. Uma coluna `regiao_id` seria chave estrangeira sem destino. O escopo REDEC da RN-24 resolve na fase 2, na policy, por `municipios.mesorregiao`.

---

### Task 1: Enums de vocabulario

Seis enums simples, sem regra de transicao. Entregam o vocabulario que as Tasks 7 e 8 usam em casts.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Enums/TipoItemPedido.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/SituacaoParecer.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/EtapaParecer.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/TipoDecreto.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/StatusAgendamento.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/StatusPrestacaoConta.php`
- Test: `tests/Unit/AjudaHumanitaria/Enums/VocabularioEnumsTest.php`

**Interfaces:**
- Consumes: nada
- Produces:
  - `TipoItemPedido::Pedido` valor `'P'`, `TipoItemPedido::Liberado` valor `'L'`; metodos `label(): string`, `options(): array`
  - `SituacaoParecer::Favoravel` valor `'favoravel'`, `SituacaoParecer::Contrario` valor `'contrario'`; metodos `label(): string`, `ehFavoravel(): bool`, `options(): array`
  - `EtapaParecer::AnaliseDlog` valor `'analise_dlog'`, `EtapaParecer::AnaliseDiretor` valor `'analise_coord'`; metodos `label(): string`, `options(): array`
  - `TipoDecreto::SituacaoEmergencia` valor `'SE'`, `TipoDecreto::EstadoCalamidadePublica` valor `'ECP'`; metodos `label(): string`, `options(): array`
  - `StatusAgendamento::Pendente` valor `'pendente'`, `StatusAgendamento::Aprovado` valor `'aprovado'`, `StatusAgendamento::Recusado` valor `'recusado'`; metodos `label(): string`, `options(): array`
  - `StatusPrestacaoConta::Pendente` valor `'pendente'`, `StatusPrestacaoConta::EmLancamento` valor `'em_lancamento'`, `StatusPrestacaoConta::Homologada` valor `'homologada'`; metodos `label(): string`, `options(): array`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Enums/VocabularioEnumsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Enums;

use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusAgendamento;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use PHPUnit\Framework\TestCase;

final class VocabularioEnumsTest extends TestCase
{
    public function test_tipo_item_preserva_os_codigos_do_legado(): void
    {
        $this->assertSame('P', TipoItemPedido::Pedido->value);
        $this->assertSame('L', TipoItemPedido::Liberado->value);
    }

    public function test_situacao_parecer_identifica_favoravel(): void
    {
        $this->assertTrue(SituacaoParecer::Favoravel->ehFavoravel());
        $this->assertFalse(SituacaoParecer::Contrario->ehFavoravel());
    }

    public function test_etapa_parecer_preserva_os_slugs_do_legado(): void
    {
        $this->assertSame('analise_dlog', EtapaParecer::AnaliseDlog->value);
        $this->assertSame('analise_coord', EtapaParecer::AnaliseDiretor->value);
    }

    public function test_tipo_decreto_cobre_se_e_ecp(): void
    {
        $this->assertSame('SE', TipoDecreto::SituacaoEmergencia->value);
        $this->assertSame('ECP', TipoDecreto::EstadoCalamidadePublica->value);
    }

    public function test_status_agendamento_tem_tres_casos(): void
    {
        $this->assertCount(3, StatusAgendamento::cases());
    }

    public function test_status_prestacao_tem_tres_casos(): void
    {
        $this->assertCount(3, StatusPrestacaoConta::cases());
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function enumProvider(): array
    {
        return [
            'tipo item'         => [TipoItemPedido::class],
            'situacao parecer'  => [SituacaoParecer::class],
            'etapa parecer'     => [EtapaParecer::class],
            'tipo decreto'      => [TipoDecreto::class],
            'status agendamento' => [StatusAgendamento::class],
            'status prestacao'  => [StatusPrestacaoConta::class],
        ];
    }

    /**
     * @param  class-string  $enum
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('enumProvider')]
    public function test_todo_enum_tem_label_nao_vazio_e_options_completo(string $enum): void
    {
        $casos = $enum::cases();

        foreach ($casos as $caso) {
            $this->assertNotSame('', $caso->label());
        }

        $options = $enum::options();
        $this->assertCount(count($casos), $options);
        $this->assertSame(['value', 'label'], array_keys($options[0]));
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Rodar a partir de `SDC/`:
```
TESTAR --filter=VocabularioEnumsTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Enums\TipoItemPedido" not found`.

- [ ] **Step 3: Implementar os seis enums**

`app/Modules/AjudaHumanitaria/Enums/TipoItemPedido.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Discriminador de item do pedido (RN-08).
 *
 * Pedido   = quantidade solicitada pelo municipio.
 * Liberado = quantidade efetivamente liberada pelo CEDEC.
 *
 * Os valores 'P' e 'L' sao preservados do legado (aju_h_pedido_itens.tp_item)
 * para manter a leitura de dados historicos possivel.
 */
enum TipoItemPedido: string
{
    case Pedido   = 'P';
    case Liberado = 'L';

    public function label(): string
    {
        return match ($this) {
            self::Pedido   => 'Solicitado pelo municipio',
            self::Liberado => 'Liberado pelo CEDEC',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/SituacaoParecer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao do parecer tecnico (RN-10).
 */
enum SituacaoParecer: string
{
    case Favoravel = 'favoravel';
    case Contrario = 'contrario';

    public function label(): string
    {
        return match ($this) {
            self::Favoravel => 'Favorável',
            self::Contrario => 'Contrário',
        };
    }

    public function ehFavoravel(): bool
    {
        return $this === self::Favoravel;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/EtapaParecer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Etapa do fluxo a que o parecer pertence (RN-10).
 *
 * Os slugs sao preservados do legado (aju_h_pedido_an_tec.tramit_parecer).
 * A etapa 'analise_drd' existia no legado e estava desativada por comentario;
 * nao entra neste modulo.
 */
enum EtapaParecer: string
{
    case AnaliseDlog    = 'analise_dlog';
    case AnaliseDiretor = 'analise_coord';

    public function label(): string
    {
        return match ($this) {
            self::AnaliseDlog    => 'Análise DLOG',
            self::AnaliseDiretor => 'Análise Diretor DLOG',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/TipoDecreto.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Tipo do decreto que embasa o pedido (RN-06).
 */
enum TipoDecreto: string
{
    case SituacaoEmergencia        = 'SE';
    case EstadoCalamidadePublica   = 'ECP';

    public function label(): string
    {
        return match ($this) {
            self::SituacaoEmergencia      => 'Situação de Emergência',
            self::EstadoCalamidadePublica => 'Estado de Calamidade Pública',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/StatusAgendamento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao do agendamento de retirada de material (RN-21).
 */
enum StatusAgendamento: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando aprovação',
            self::Aprovado => 'Aprovado',
            self::Recusado => 'Recusado',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/StatusPrestacaoConta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao da prestacao de contas do pedido (RN-19).
 */
enum StatusPrestacaoConta: string
{
    case Pendente     = 'pendente';
    case EmLancamento = 'em_lancamento';
    case Homologada   = 'homologada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente     => 'Pendente',
            self::EmLancamento => 'Em lançamento',
            self::Homologada   => 'Homologada',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=VocabularioEnumsTest
```
Esperado: PASS, 12 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Enums SDC/tests/Unit/AjudaHumanitaria/Enums/VocabularioEnumsTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): enums de vocabulario do MAH' '' 'Tipo de item preservando os codigos P e L do legado, situacao e etapa' 'de parecer, tipo de decreto, status de agendamento e de prestacao de' 'contas. Cobre RN-06, RN-08, RN-10, RN-19 e RN-21.')"
```

---

### Task 2: Maquina de estados

O nucleo da RN-12 e da RN-13. `StatusPedidoAh` e a fonte unica de verdade e `FasePedidoAh` e derivada dela.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Enums/FasePedidoAh.php`
- Create: `app/Modules/AjudaHumanitaria/Enums/StatusPedidoAh.php`
- Test: `tests/Unit/AjudaHumanitaria/Enums/StatusPedidoAhTest.php`

**Interfaces:**
- Consumes: nada
- Produces:
  - `FasePedidoAh` string enum, casos `EdicaoCompdec` `'edicao_compdec'`, `AnaliseDlog` `'analise_dlog'`, `AnaliseCoord` `'analise_coord'`, `Aprovado` `'aprovado'`, `AguardDisp` `'aguard_disp'`, `AguardRet` `'aguard_ret'`, `Atendido` `'atendido'`, `Cancelado` `'cancelado'`, `Reprovado` `'reprovado'`, `Finalizado` `'finalizado'`; metodo `label(): string`
  - `StatusPedidoAh` int enum, casos `EdicaoCompdec` 0, `AnaliseDlog` 1, `AnaliseDiretorDlog` 2, `Aprovado` 3, `AguardandoDisponibilidade` 4, `AguardandoRetirada` 5, `Atendido` 6, `Cancelado` 7, `Reprovado` 8, `Finalizado` 9
  - `StatusPedidoAh::label(): string`
  - `StatusPedidoAh::fase(): FasePedidoAh`
  - `StatusPedidoAh::cor(): array{fundo: string, texto: string}` devolvendo classes utilitarias Tailwind
  - `StatusPedidoAh::transicoesPermitidas(): array<int, StatusPedidoAh>`
  - `StatusPedidoAh::podeTransitarPara(StatusPedidoAh $alvo): bool`
  - `StatusPedidoAh::ehTerminal(): bool`
  - `StatusPedidoAh::options(): array<int, array{value: int, label: string, fase: string}>`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Enums/StatusPedidoAhTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Enums;

use App\Modules\AjudaHumanitaria\Enums\FasePedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StatusPedidoAhTest extends TestCase
{
    public function test_os_dez_status_do_legado_estao_preservados(): void
    {
        $this->assertCount(10, StatusPedidoAh::cases());
        $this->assertSame(0, StatusPedidoAh::EdicaoCompdec->value);
        $this->assertSame(9, StatusPedidoAh::Finalizado->value);
    }

    public function test_fase_e_derivada_do_status(): void
    {
        $this->assertSame(FasePedidoAh::EdicaoCompdec, StatusPedidoAh::EdicaoCompdec->fase());
        $this->assertSame(FasePedidoAh::AnaliseCoord, StatusPedidoAh::AnaliseDiretorDlog->fase());
        $this->assertSame(FasePedidoAh::Atendido, StatusPedidoAh::Atendido->fase());
        $this->assertSame(FasePedidoAh::Finalizado, StatusPedidoAh::Finalizado->fase());
    }

    public function test_toda_fase_tem_exatamente_um_status_de_origem(): void
    {
        $fases = array_map(
            fn (StatusPedidoAh $status) => $status->fase()->value,
            StatusPedidoAh::cases(),
        );

        $this->assertSame($fases, array_unique($fases));
    }

    /**
     * @return array<string, array{0: StatusPedidoAh, 1: array<int, StatusPedidoAh>}>
     */
    public static function matrizProvider(): array
    {
        return [
            'edicao compdec' => [StatusPedidoAh::EdicaoCompdec, [
                StatusPedidoAh::AnaliseDlog,
                StatusPedidoAh::Cancelado,
            ]],
            'analise dlog' => [StatusPedidoAh::AnaliseDlog, [
                StatusPedidoAh::AnaliseDiretorDlog,
                StatusPedidoAh::EdicaoCompdec,
                StatusPedidoAh::Reprovado,
                StatusPedidoAh::Cancelado,
            ]],
            'analise diretor' => [StatusPedidoAh::AnaliseDiretorDlog, [
                StatusPedidoAh::Aprovado,
                StatusPedidoAh::AnaliseDlog,
                StatusPedidoAh::Reprovado,
                StatusPedidoAh::Cancelado,
            ]],
            'aprovado' => [StatusPedidoAh::Aprovado, [
                StatusPedidoAh::AguardandoDisponibilidade,
                StatusPedidoAh::Cancelado,
            ]],
            'aguardando disponibilidade' => [StatusPedidoAh::AguardandoDisponibilidade, [
                StatusPedidoAh::AguardandoRetirada,
                StatusPedidoAh::Cancelado,
            ]],
            'aguardando retirada' => [StatusPedidoAh::AguardandoRetirada, [
                StatusPedidoAh::Atendido,
                StatusPedidoAh::Cancelado,
            ]],
            'atendido' => [StatusPedidoAh::Atendido, [
                StatusPedidoAh::Finalizado,
                StatusPedidoAh::Cancelado,
            ]],
            'cancelado' => [StatusPedidoAh::Cancelado, []],
            'reprovado' => [StatusPedidoAh::Reprovado, []],
            'finalizado' => [StatusPedidoAh::Finalizado, []],
        ];
    }

    /**
     * @param  array<int, StatusPedidoAh>  $esperadas
     */
    #[DataProvider('matrizProvider')]
    public function test_matriz_de_transicoes(StatusPedidoAh $de, array $esperadas): void
    {
        $this->assertEqualsCanonicalizing($esperadas, $de->transicoesPermitidas());
    }

    public function test_nao_permite_transicao_fora_da_matriz(): void
    {
        $this->assertFalse(
            StatusPedidoAh::EdicaoCompdec->podeTransitarPara(StatusPedidoAh::AguardandoRetirada),
            'O legado permitia esse salto; a matriz normativa o proibe.'
        );
        $this->assertFalse(
            StatusPedidoAh::Atendido->podeTransitarPara(StatusPedidoAh::AnaliseDlog),
            'O legado permitia retroceder de Atendido; a matriz normativa o proibe.'
        );
        $this->assertTrue(
            StatusPedidoAh::EdicaoCompdec->podeTransitarPara(StatusPedidoAh::AnaliseDlog)
        );
    }

    public function test_nenhum_status_transita_para_si_mesmo(): void
    {
        foreach (StatusPedidoAh::cases() as $status) {
            $this->assertFalse(
                $status->podeTransitarPara($status),
                "{$status->name} nao deve transitar para si mesmo"
            );
        }
    }

    public function test_estados_terminais(): void
    {
        $this->assertTrue(StatusPedidoAh::Cancelado->ehTerminal());
        $this->assertTrue(StatusPedidoAh::Reprovado->ehTerminal());
        $this->assertTrue(StatusPedidoAh::Finalizado->ehTerminal());
        $this->assertFalse(StatusPedidoAh::Atendido->ehTerminal());
    }

    public function test_cor_usa_classes_tailwind_e_nao_hexadecimal(): void
    {
        foreach (StatusPedidoAh::cases() as $status) {
            $cor = $status->cor();
            $this->assertArrayHasKey('fundo', $cor);
            $this->assertArrayHasKey('texto', $cor);
            $this->assertStringNotContainsString('#', $cor['fundo']);
            $this->assertStringContainsString('dark:', $cor['fundo']);
        }
    }

    public function test_options_expoe_valor_label_e_fase(): void
    {
        $options = StatusPedidoAh::options();

        $this->assertCount(10, $options);
        $this->assertSame(['value', 'label', 'fase'], array_keys($options[0]));
        $this->assertSame(0, $options[0]['value']);
        $this->assertSame('edicao_compdec', $options[0]['fase']);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=StatusPedidoAhTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Enums\FasePedidoAh" not found`.

- [ ] **Step 3: Implementar os dois enums**

`app/Modules/AjudaHumanitaria/Enums/FasePedidoAh.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Fase do pedido, derivada do status (RN-13).
 *
 * No legado, fase e status eram duas colunas gravadas em paralelo e podiam
 * divergir. Aqui a fase nao e armazenada: e sempre obtida de
 * StatusPedidoAh::fase(). Os slugs sao preservados do legado
 * (aju_h_pedido_pedid.tramit) para leitura de dados historicos.
 */
enum FasePedidoAh: string
{
    case EdicaoCompdec = 'edicao_compdec';
    case AnaliseDlog   = 'analise_dlog';
    case AnaliseCoord  = 'analise_coord';
    case Aprovado      = 'aprovado';
    case AguardDisp    = 'aguard_disp';
    case AguardRet     = 'aguard_ret';
    case Atendido      = 'atendido';
    case Cancelado     = 'cancelado';
    case Reprovado     = 'reprovado';
    case Finalizado    = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::EdicaoCompdec => 'Em edição pelo COMPDEC',
            self::AnaliseDlog   => 'Em análise DLOG',
            self::AnaliseCoord  => 'Em análise do Diretor DLOG',
            self::Aprovado      => 'Processo aprovado',
            self::AguardDisp    => 'Aguardando disponibilidade de material',
            self::AguardRet     => 'Aguardando retirada de material',
            self::Atendido      => 'Em prestação de contas',
            self::Cancelado     => 'Processo cancelado',
            self::Reprovado     => 'Processo reprovado',
            self::Finalizado    => 'Processo finalizado',
        };
    }
}
```

`app/Modules/AjudaHumanitaria/Enums/StatusPedidoAh.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Status do pedido de material de ajuda humanitaria.
 *
 * Fonte unica de verdade do processo (RN-13). A fase e derivada por fase().
 *
 * Matriz de transicoes (RN-12), normativa deste modulo:
 *
 *   0 EdicaoCompdec -> 1 AnaliseDlog -> 2 AnaliseDiretorDlog -> 3 Aprovado
 *   -> 4 AguardandoDisponibilidade -> 5 AguardandoRetirada -> 6 Atendido
 *   -> 9 Finalizado
 *
 *   Devolucoes: 1 -> 0 e 2 -> 1.
 *   Saidas: 7 Cancelado (de 0 a 6) e 8 Reprovado (de 1 e 2).
 *
 * As condicoes de cada transicao nao estao aqui: vivem nas guardas sob
 * Domain/Guards e sao compostas por PedidoAhWorkflow. Este enum responde
 * apenas se o caminho existe no grafo.
 */
enum StatusPedidoAh: int
{
    case EdicaoCompdec              = 0;
    case AnaliseDlog                = 1;
    case AnaliseDiretorDlog         = 2;
    case Aprovado                   = 3;
    case AguardandoDisponibilidade  = 4;
    case AguardandoRetirada         = 5;
    case Atendido                   = 6;
    case Cancelado                  = 7;
    case Reprovado                  = 8;
    case Finalizado                 = 9;

    public function label(): string
    {
        return match ($this) {
            self::EdicaoCompdec             => 'Edição COMPDEC',
            self::AnaliseDlog               => 'Análise DLOG',
            self::AnaliseDiretorDlog        => 'Análise Diretor DLOG',
            self::Aprovado                  => 'Aprovado',
            self::AguardandoDisponibilidade => 'Aguardando disponibilidade de material',
            self::AguardandoRetirada        => 'Aguardando retirada de material',
            self::Atendido                  => 'Atendido',
            self::Cancelado                 => 'Cancelado',
            self::Reprovado                 => 'Reprovado',
            self::Finalizado                => 'Processo finalizado',
        };
    }

    public function fase(): FasePedidoAh
    {
        return match ($this) {
            self::EdicaoCompdec             => FasePedidoAh::EdicaoCompdec,
            self::AnaliseDlog               => FasePedidoAh::AnaliseDlog,
            self::AnaliseDiretorDlog        => FasePedidoAh::AnaliseCoord,
            self::Aprovado                  => FasePedidoAh::Aprovado,
            self::AguardandoDisponibilidade => FasePedidoAh::AguardDisp,
            self::AguardandoRetirada        => FasePedidoAh::AguardRet,
            self::Atendido                  => FasePedidoAh::Atendido,
            self::Cancelado                 => FasePedidoAh::Cancelado,
            self::Reprovado                 => FasePedidoAh::Reprovado,
            self::Finalizado                => FasePedidoAh::Finalizado,
        };
    }

    /**
     * Classes utilitarias para o badge de status.
     *
     * A intencao semantica vem de getCorStatus do legado, convertida para
     * tokens Tailwind com suporte a tema claro e escuro.
     *
     * @return array{fundo: string, texto: string}
     */
    public function cor(): array
    {
        return match ($this) {
            self::EdicaoCompdec => [
                'fundo' => 'bg-amber-100 dark:bg-amber-900/40',
                'texto' => 'text-amber-900 dark:text-amber-100',
            ],
            self::AnaliseDlog => [
                'fundo' => 'bg-slate-100 dark:bg-slate-800',
                'texto' => 'text-slate-900 dark:text-slate-100',
            ],
            self::AnaliseDiretorDlog => [
                'fundo' => 'bg-blue-100 dark:bg-blue-900/40',
                'texto' => 'text-blue-900 dark:text-blue-100',
            ],
            self::Aprovado => [
                'fundo' => 'bg-orange-100 dark:bg-orange-900/40',
                'texto' => 'text-orange-900 dark:text-orange-100',
            ],
            self::AguardandoDisponibilidade => [
                'fundo' => 'bg-violet-100 dark:bg-violet-900/40',
                'texto' => 'text-violet-900 dark:text-violet-100',
            ],
            self::AguardandoRetirada => [
                'fundo' => 'bg-yellow-100 dark:bg-yellow-900/40',
                'texto' => 'text-yellow-900 dark:text-yellow-100',
            ],
            self::Atendido => [
                'fundo' => 'bg-green-100 dark:bg-green-900/40',
                'texto' => 'text-green-900 dark:text-green-100',
            ],
            self::Cancelado => [
                'fundo' => 'bg-red-100 dark:bg-red-900/40',
                'texto' => 'text-red-900 dark:text-red-100',
            ],
            self::Reprovado => [
                'fundo' => 'bg-gray-200 dark:bg-gray-700',
                'texto' => 'text-gray-900 dark:text-gray-100',
            ],
            self::Finalizado => [
                'fundo' => 'bg-emerald-100 dark:bg-emerald-900/40',
                'texto' => 'text-emerald-900 dark:text-emerald-100',
            ],
        };
    }

    /**
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::EdicaoCompdec => [
                self::AnaliseDlog,
                self::Cancelado,
            ],
            self::AnaliseDlog => [
                self::AnaliseDiretorDlog,
                self::EdicaoCompdec,
                self::Reprovado,
                self::Cancelado,
            ],
            self::AnaliseDiretorDlog => [
                self::Aprovado,
                self::AnaliseDlog,
                self::Reprovado,
                self::Cancelado,
            ],
            self::Aprovado => [
                self::AguardandoDisponibilidade,
                self::Cancelado,
            ],
            self::AguardandoDisponibilidade => [
                self::AguardandoRetirada,
                self::Cancelado,
            ],
            self::AguardandoRetirada => [
                self::Atendido,
                self::Cancelado,
            ],
            self::Atendido => [
                self::Finalizado,
                self::Cancelado,
            ],
            self::Cancelado, self::Reprovado, self::Finalizado => [],
        };
    }

    public function podeTransitarPara(self $alvo): bool
    {
        return in_array($alvo, $this->transicoesPermitidas(), true);
    }

    public function ehTerminal(): bool
    {
        return $this->transicoesPermitidas() === [];
    }

    /**
     * @return array<int, array{value: int, label: string, fase: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => [
                'value' => $caso->value,
                'label' => $caso->label(),
                'fase'  => $caso->fase()->value,
            ],
            self::cases(),
        );
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=StatusPedidoAhTest
```
Esperado: PASS, 18 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Enums/FasePedidoAh.php SDC/app/Modules/AjudaHumanitaria/Enums/StatusPedidoAh.php SDC/tests/Unit/AjudaHumanitaria/Enums/StatusPedidoAhTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): maquina de estados do pedido MAH' '' 'StatusPedidoAh como fonte unica de verdade, com fase derivada em vez de' 'coluna paralela, e a matriz normativa de transicoes que resolve a' 'contradicao entre as duas fontes do legado.' '' 'Fecha os saltos invalidos que o legado permitia: 0 direto para' 'aguardando retirada e retrocesso de atendido para analise.' '' 'Cobre RN-12 e RN-13.')"
```

---

### Task 3: Contratos do dominio

Define como uma guarda recebe fatos e devolve veredito, sem tocar Eloquent. E o que mantem `Domain/` puro.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Domain/Contracts/ResultadoGuarda.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Contracts/ContextoTransicao.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Contracts/GuardaTransicao.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/ContratosTest.php`

**Interfaces:**
- Consumes: `StatusPedidoAh` da Task 2
- Produces:
  - `ResultadoGuarda` final readonly, propriedades publicas `bool $permitido` e `?string $motivo`; construtor privado; fabricas estaticas `permitir(): self` e `bloquear(string $motivo): self`
  - `ContextoTransicao` final readonly, construtor nomeado com `StatusPedidoAh $statusAtual`, `StatusPedidoAh $statusAlvo`, `bool $temItemPedido = false`, `bool $temParecerFavoravel = false`, `bool $temItemLiberado = false`, `bool $agendamentoAprovado = false`, `bool $viaHomologacao = false`; metodo `ehTransicao(StatusPedidoAh $de, StatusPedidoAh $para): bool`
  - `GuardaTransicao` interface com `verificar(ContextoTransicao $contexto): ResultadoGuarda`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Domain/ContratosTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use PHPUnit\Framework\TestCase;

final class ContratosTest extends TestCase
{
    public function test_resultado_permitido_nao_tem_motivo(): void
    {
        $resultado = ResultadoGuarda::permitir();

        $this->assertTrue($resultado->permitido);
        $this->assertNull($resultado->motivo);
    }

    public function test_resultado_bloqueado_carrega_o_motivo(): void
    {
        $resultado = ResultadoGuarda::bloquear('Sem parecer favorável.');

        $this->assertFalse($resultado->permitido);
        $this->assertSame('Sem parecer favorável.', $resultado->motivo);
    }

    public function test_contexto_tem_todos_os_fatos_falsos_por_padrao(): void
    {
        $contexto = new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
        );

        $this->assertFalse($contexto->temItemPedido);
        $this->assertFalse($contexto->temParecerFavoravel);
        $this->assertFalse($contexto->temItemLiberado);
        $this->assertFalse($contexto->agendamentoAprovado);
        $this->assertFalse($contexto->viaHomologacao);
    }

    public function test_contexto_identifica_o_par_de_transicao(): void
    {
        $contexto = new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
        );

        $this->assertTrue($contexto->ehTransicao(
            StatusPedidoAh::AnaliseDlog,
            StatusPedidoAh::AnaliseDiretorDlog,
        ));
        $this->assertFalse($contexto->ehTransicao(
            StatusPedidoAh::EdicaoCompdec,
            StatusPedidoAh::AnaliseDlog,
        ));
    }

    public function test_dominio_nao_depende_de_framework(): void
    {
        $base = dirname(__DIR__, 4) . '/app/Modules/AjudaHumanitaria/Domain';

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        $violacoes = [];

        foreach ($iterador as $arquivo) {
            if ($arquivo->getExtension() !== 'php') {
                continue;
            }

            $conteudo = (string) file_get_contents($arquivo->getPathname());

            if (preg_match('/^use\s+(Illuminate|App\\\\Models)\\\\/m', $conteudo) === 1) {
                $violacoes[] = $arquivo->getFilename();
            }
        }

        $this->assertSame([], $violacoes, 'Domain deve permanecer livre de Eloquent e do framework.');
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=ContratosTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda" not found`.

- [ ] **Step 3: Implementar os tres contratos**

`app/Modules/AjudaHumanitaria/Domain/Contracts/ResultadoGuarda.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

/**
 * Veredito de uma verificacao de regra.
 *
 * Quando bloqueia, carrega o motivo em texto pronto para exibicao ao usuario.
 */
final readonly class ResultadoGuarda
{
    private function __construct(
        public bool $permitido,
        public ?string $motivo,
    ) {}

    public static function permitir(): self
    {
        return new self(permitido: true, motivo: null);
    }

    public static function bloquear(string $motivo): self
    {
        return new self(permitido: false, motivo: $motivo);
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Contracts/ContextoTransicao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Fatos necessarios para decidir uma transicao de status.
 *
 * Existe para manter as guardas puras: quem monta este contexto e a camada de
 * aplicacao, consultando os repositorios. As guardas recebem apenas booleanos
 * e nunca tocam o banco.
 *
 * Todos os fatos sao falsos por padrao, de modo que uma guarda esquecida
 * bloqueia em vez de liberar.
 */
final readonly class ContextoTransicao
{
    public function __construct(
        public StatusPedidoAh $statusAtual,
        public StatusPedidoAh $statusAlvo,
        public bool $temItemPedido = false,
        public bool $temParecerFavoravel = false,
        public bool $temItemLiberado = false,
        public bool $agendamentoAprovado = false,
        public bool $viaHomologacao = false,
    ) {}

    public function ehTransicao(StatusPedidoAh $de, StatusPedidoAh $para): bool
    {
        return $this->statusAtual === $de && $this->statusAlvo === $para;
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Contracts/GuardaTransicao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

/**
 * Contrato unico de guarda de transicao.
 *
 * Uma guarda que nao se aplica ao par de status do contexto deve permitir,
 * nao bloquear. Assim as guardas sao independentes e a ordem de execucao
 * nao importa.
 */
interface GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda;
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=ContratosTest
```
Esperado: PASS, 5 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Domain/Contracts SDC/tests/Unit/AjudaHumanitaria/Domain/ContratosTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): contratos do dominio do MAH' '' 'ResultadoGuarda, ContextoTransicao e GuardaTransicao. O contexto carrega' 'apenas booleanos, montados pela camada de aplicacao, o que mantem as' 'guardas puras e testaveis sem banco.' '' 'Inclui teste de arquitetura que falha se algo sob Domain importar' 'Illuminate ou App\\Models.')"
```

---

### Task 4: Guardas de transicao

As cinco condicoes da matriz. Cada guarda cobre um unico par de status e permite silenciosamente quando o par nao e o seu.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItemNoPedido.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeParecerFavoravel.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItensLiberados.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Guards/ExigeAgendamentoAprovado.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Guards/FinalizacaoSomenteViaHomologacao.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/GuardasTest.php`

**Interfaces:**
- Consumes: `ContextoTransicao`, `ResultadoGuarda`, `GuardaTransicao` da Task 3; `StatusPedidoAh` da Task 2
- Produces: cinco classes finais implementando `GuardaTransicao`, todas sem construtor e sem estado

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Domain/GuardasTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeAgendamentoAprovado;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GuardasTest extends TestCase
{
    public function test_exige_item_no_pedido_bloqueia_pedido_vazio(): void
    {
        $resultado = (new ExigeItemNoPedido())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
            temItemPedido: false,
        ));

        $this->assertFalse($resultado->permitido);
        $this->assertNotNull($resultado->motivo);
    }

    public function test_exige_item_no_pedido_permite_com_item(): void
    {
        $resultado = (new ExigeItemNoPedido())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
            temItemPedido: true,
        ));

        $this->assertTrue($resultado->permitido);
    }

    public function test_exige_parecer_favoravel_bloqueia_sem_parecer(): void
    {
        $resultado = (new ExigeParecerFavoravel())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
            temParecerFavoravel: false,
        ));

        $this->assertFalse($resultado->permitido);
    }

    public function test_exige_parecer_favoravel_permite_com_parecer(): void
    {
        $resultado = (new ExigeParecerFavoravel())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
            temParecerFavoravel: true,
        ));

        $this->assertTrue($resultado->permitido);
    }

    public function test_exige_parecer_favoravel_nao_atrapalha_devolucao(): void
    {
        $resultado = (new ExigeParecerFavoravel())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::EdicaoCompdec,
            temParecerFavoravel: false,
        ));

        $this->assertTrue($resultado->permitido, 'Devolver para correcao nao exige parecer.');
    }

    public function test_exige_itens_liberados_bloqueia_aprovacao_sem_liberacao(): void
    {
        $resultado = (new ExigeItensLiberados())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDiretorDlog,
            statusAlvo:  StatusPedidoAh::Aprovado,
            temItemLiberado: false,
        ));

        $this->assertFalse($resultado->permitido);
    }

    public function test_exige_agendamento_aprovado_bloqueia_atendimento(): void
    {
        $resultado = (new ExigeAgendamentoAprovado())->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AguardandoRetirada,
            statusAlvo:  StatusPedidoAh::Atendido,
            agendamentoAprovado: false,
        ));

        $this->assertFalse($resultado->permitido);
    }

    public function test_finalizacao_exige_homologacao(): void
    {
        $guarda = new FinalizacaoSomenteViaHomologacao();

        $semHomologacao = $guarda->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::Atendido,
            statusAlvo:  StatusPedidoAh::Finalizado,
            viaHomologacao: false,
        ));
        $comHomologacao = $guarda->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::Atendido,
            statusAlvo:  StatusPedidoAh::Finalizado,
            viaHomologacao: true,
        ));

        $this->assertFalse($semHomologacao->permitido);
        $this->assertTrue($comHomologacao->permitido);
    }

    /**
     * @return array<string, array{0: GuardaTransicao}>
     */
    public static function guardaProvider(): array
    {
        return [
            'item no pedido'    => [new ExigeItemNoPedido()],
            'parecer favoravel' => [new ExigeParecerFavoravel()],
            'itens liberados'   => [new ExigeItensLiberados()],
            'agendamento'       => [new ExigeAgendamentoAprovado()],
            'homologacao'       => [new FinalizacaoSomenteViaHomologacao()],
        ];
    }

    /**
     * Uma guarda fora do seu par deve permitir, para que a ordem de execucao
     * seja irrelevante.
     */
    #[DataProvider('guardaProvider')]
    public function test_guarda_permite_quando_o_par_nao_e_o_seu(GuardaTransicao $guarda): void
    {
        $resultado = $guarda->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::Aprovado,
            statusAlvo:  StatusPedidoAh::Cancelado,
        ));

        $this->assertTrue($resultado->permitido);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=GuardasTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido" not found`.

- [ ] **Step 3: Implementar as cinco guardas**

`app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItemNoPedido.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Nao se envia pedido vazio para analise.
 *
 * Trava ausente no legado, onde era possivel tramitar um pedido sem nenhum
 * material solicitado.
 */
final class ExigeItemNoPedido implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::EdicaoCompdec, StatusPedidoAh::AnaliseDlog)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temItemPedido) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'Inclua ao menos um material antes de enviar o pedido para análise.'
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Guards/ExigeParecerFavoravel.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-11: avanco da analise DLOG para o diretor exige ao menos um parecer
 * favoravel. Devolver o pedido para correcao nao exige parecer.
 */
final class ExigeParecerFavoravel implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AnaliseDlog, StatusPedidoAh::AnaliseDiretorDlog)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temParecerFavoravel) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'É necessário ao menos um parecer favorável para encaminhar ao Diretor DLOG.'
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Guards/ExigeItensLiberados.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Nao se aprova pedido sem definir o que sera liberado.
 *
 * Trava ausente no legado. Sem itens liberados, a prestacao de contas nasceria
 * vazia na entrada em Atendido (RN-15).
 */
final class ExigeItensLiberados implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AnaliseDiretorDlog, StatusPedidoAh::Aprovado)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temItemLiberado) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'Defina as quantidades liberadas antes de aprovar o pedido.'
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Guards/ExigeAgendamentoAprovado.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-21: o pedido so passa a Atendido com agendamento de retirada aprovado.
 */
final class ExigeAgendamentoAprovado implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AguardandoRetirada, StatusPedidoAh::Atendido)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->agendamentoAprovado) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'É necessário um agendamento de retirada aprovado para marcar o pedido como atendido.'
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Guards/FinalizacaoSomenteViaHomologacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-19: o processo so chega a Finalizado pela homologacao da prestacao de
 * contas, nunca por tramitacao manual.
 */
final class FinalizacaoSomenteViaHomologacao implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::Atendido, StatusPedidoAh::Finalizado)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->viaHomologacao) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'O processo é finalizado pela homologação da prestação de contas.'
        );
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=GuardasTest
```
Esperado: PASS, 13 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Domain/Guards SDC/tests/Unit/AjudaHumanitaria/Domain/GuardasTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): guardas de transicao do pedido MAH' '' 'Cinco guardas independentes, cada uma responsavel por um unico par de' 'status e permissiva fora dele, o que torna a ordem de execucao' 'irrelevante.' '' 'Cobre RN-11, RN-19 e RN-21, mais duas travas ausentes no legado: pedido' 'vazio enviado para analise e aprovacao sem itens liberados.')"
```

---

### Task 5: PedidoAhWorkflow

Unica autoridade sobre transicao valida. Compoe o grafo do enum com as guardas.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Domain/PedidoAhWorkflow.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/PedidoAhWorkflowTest.php`

**Interfaces:**
- Consumes: `StatusPedidoAh` da Task 2; `ContextoTransicao`, `ResultadoGuarda`, `GuardaTransicao` da Task 3; as cinco guardas da Task 4
- Produces:
  - `PedidoAhWorkflow::__construct(iterable $guardas)` onde cada elemento e um `GuardaTransicao`
  - `PedidoAhWorkflow::verificar(ContextoTransicao $contexto): ResultadoGuarda`
  - `PedidoAhWorkflow::destinosPossiveis(StatusPedidoAh $atual): array<int, StatusPedidoAh>`
  - `PedidoAhWorkflow::comGuardasPadrao(): self` fabrica estatica usada em teste e como fallback

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Domain/PedidoAhWorkflowTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use PHPUnit\Framework\TestCase;

final class PedidoAhWorkflowTest extends TestCase
{
    private PedidoAhWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = PedidoAhWorkflow::comGuardasPadrao();
    }

    public function test_bloqueia_transicao_fora_do_grafo_antes_de_consultar_guardas(): void
    {
        $resultado = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AguardandoRetirada,
            temItemPedido: true,
        ));

        $this->assertFalse($resultado->permitido);
        $this->assertStringContainsString('não é permitida', (string) $resultado->motivo);
    }

    public function test_bloqueia_saida_de_estado_terminal(): void
    {
        foreach ([StatusPedidoAh::Cancelado, StatusPedidoAh::Reprovado, StatusPedidoAh::Finalizado] as $terminal) {
            $resultado = $this->workflow->verificar(new ContextoTransicao(
                statusAtual: $terminal,
                statusAlvo:  StatusPedidoAh::AnaliseDlog,
            ));

            $this->assertFalse($resultado->permitido, "{$terminal->name} deve ser terminal");
        }
    }

    public function test_envio_para_analise_exige_item(): void
    {
        $semItem = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
            temItemPedido: false,
        ));
        $comItem = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::EdicaoCompdec,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
            temItemPedido: true,
        ));

        $this->assertFalse($semItem->permitido);
        $this->assertTrue($comItem->permitido);
    }

    public function test_encaminhamento_ao_diretor_exige_parecer_favoravel(): void
    {
        $resultado = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
            temParecerFavoravel: false,
        ));

        $this->assertFalse($resultado->permitido);
        $this->assertStringContainsString('parecer favorável', (string) $resultado->motivo);
    }

    public function test_aprovacao_exige_itens_liberados(): void
    {
        $resultado = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDiretorDlog,
            statusAlvo:  StatusPedidoAh::Aprovado,
            temItemLiberado: false,
        ));

        $this->assertFalse($resultado->permitido);
    }

    public function test_atendimento_exige_agendamento_aprovado(): void
    {
        $resultado = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AguardandoRetirada,
            statusAlvo:  StatusPedidoAh::Atendido,
            agendamentoAprovado: false,
        ));

        $this->assertFalse($resultado->permitido);
    }

    public function test_finalizacao_somente_via_homologacao(): void
    {
        $manual = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::Atendido,
            statusAlvo:  StatusPedidoAh::Finalizado,
            viaHomologacao: false,
        ));
        $homologada = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::Atendido,
            statusAlvo:  StatusPedidoAh::Finalizado,
            viaHomologacao: true,
        ));

        $this->assertFalse($manual->permitido);
        $this->assertTrue($homologada->permitido);
    }

    public function test_cancelamento_e_livre_de_qualquer_status_nao_terminal(): void
    {
        $cancelaveis = [
            StatusPedidoAh::EdicaoCompdec,
            StatusPedidoAh::AnaliseDlog,
            StatusPedidoAh::AnaliseDiretorDlog,
            StatusPedidoAh::Aprovado,
            StatusPedidoAh::AguardandoDisponibilidade,
            StatusPedidoAh::AguardandoRetirada,
            StatusPedidoAh::Atendido,
        ];

        foreach ($cancelaveis as $status) {
            $resultado = $this->workflow->verificar(new ContextoTransicao(
                statusAtual: $status,
                statusAlvo:  StatusPedidoAh::Cancelado,
            ));

            $this->assertTrue($resultado->permitido, "{$status->name} deve poder ser cancelado");
        }
    }

    public function test_devolucoes_nao_exigem_condicao(): void
    {
        $paraCompdec = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::EdicaoCompdec,
        ));
        $paraDlog = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDiretorDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDlog,
        ));

        $this->assertTrue($paraCompdec->permitido);
        $this->assertTrue($paraDlog->permitido);
    }

    public function test_destinos_possiveis_espelha_o_grafo(): void
    {
        $this->assertEqualsCanonicalizing(
            [StatusPedidoAh::AnaliseDlog, StatusPedidoAh::Cancelado],
            $this->workflow->destinosPossiveis(StatusPedidoAh::EdicaoCompdec),
        );
        $this->assertSame([], $this->workflow->destinosPossiveis(StatusPedidoAh::Finalizado));
    }

    public function test_primeira_guarda_que_bloqueia_define_o_motivo(): void
    {
        $resultado = $this->workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
        ));

        $this->assertFalse($resultado->permitido);
        $this->assertNotNull($resultado->motivo);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=PedidoAhWorkflowTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow" not found`.

- [ ] **Step 3: Implementar o workflow**

`app/Modules/AjudaHumanitaria/Domain/PedidoAhWorkflow.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeAgendamentoAprovado;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Unica autoridade sobre a validade de uma transicao de status do pedido.
 *
 * Decide em duas etapas: primeiro se o caminho existe no grafo do enum
 * (RN-12), depois se as condicoes das guardas estao satisfeitas (RN-11,
 * RN-19, RN-21). Nenhum service deve reimplementar essa decisao.
 */
final class PedidoAhWorkflow
{
    /** @var array<int, GuardaTransicao> */
    private array $guardas;

    /**
     * @param  iterable<GuardaTransicao>  $guardas
     */
    public function __construct(iterable $guardas)
    {
        $this->guardas = $guardas instanceof \Traversable
            ? iterator_to_array($guardas, false)
            : array_values($guardas);
    }

    /**
     * Conjunto padrao de guardas. Usado como fallback e em teste unitario;
     * em runtime o container injeta a colecao registrada no provider.
     */
    public static function comGuardasPadrao(): self
    {
        return new self([
            new ExigeItemNoPedido(),
            new ExigeParecerFavoravel(),
            new ExigeItensLiberados(),
            new ExigeAgendamentoAprovado(),
            new FinalizacaoSomenteViaHomologacao(),
        ]);
    }

    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->statusAtual->podeTransitarPara($contexto->statusAlvo)) {
            return ResultadoGuarda::bloquear(sprintf(
                'A transição de "%s" para "%s" não é permitida.',
                $contexto->statusAtual->label(),
                $contexto->statusAlvo->label(),
            ));
        }

        foreach ($this->guardas as $guarda) {
            $resultado = $guarda->verificar($contexto);

            if (! $resultado->permitido) {
                return $resultado;
            }
        }

        return ResultadoGuarda::permitir();
    }

    /**
     * Destinos que existem no grafo, sem avaliar condicao.
     *
     * Serve para montar a lista de opcoes na interface; a validade final e
     * sempre confirmada por verificar().
     *
     * @return array<int, StatusPedidoAh>
     */
    public function destinosPossiveis(StatusPedidoAh $atual): array
    {
        return $atual->transicoesPermitidas();
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=PedidoAhWorkflowTest
```
Esperado: PASS, 11 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Domain/PedidoAhWorkflow.php SDC/tests/Unit/AjudaHumanitaria/Domain/PedidoAhWorkflowTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): workflow do pedido MAH' '' 'Compoe o grafo de transicoes do enum com a colecao de guardas e decide' 'em duas etapas: caminho existe, depois condicoes satisfeitas.' '' 'Unica autoridade sobre transicao valida. Cobre RN-12 integrando RN-11,' 'RN-19 e RN-21.')"
```

---

### Task 6: Specifications

Tres regras que nao sao transicao: abertura de pedido, prazo de prestacao e saldo de entrega. Puras, sem banco.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Domain/Specifications/MunicipioPodeAbrirPedido.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Specifications/PrazoPrestacaoContas.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Specifications/SaldoEntregaBeneficiarios.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/SpecificationsTest.php`

**Interfaces:**
- Consumes: `ResultadoGuarda` da Task 3
- Produces:
  - `MunicipioPodeAbrirPedido::verificar(bool $temPedidoEmEdicao): ResultadoGuarda`
  - `PrazoPrestacaoContas::calcular(CarbonImmutable $dataAprovacao, int $prazoDias): CarbonImmutable`
  - `PrazoPrestacaoContas::estaVencido(CarbonImmutable $dataLimite, CarbonImmutable $hoje): bool`
  - `SaldoEntregaBeneficiarios::saldo(int $qtdMaterial, int $qtdJaEntregue): int`
  - `SaldoEntregaBeneficiarios::verificar(int $qtdMaterial, int $qtdJaEntregue, int $qtdNova): ResultadoGuarda`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Domain/SpecificationsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Specifications\MunicipioPodeAbrirPedido;
use App\Modules\AjudaHumanitaria\Domain\Specifications\PrazoPrestacaoContas;
use App\Modules\AjudaHumanitaria\Domain\Specifications\SaldoEntregaBeneficiarios;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SpecificationsTest extends TestCase
{
    public function test_municipio_com_pedido_em_edicao_nao_abre_outro(): void
    {
        $spec = new MunicipioPodeAbrirPedido();

        $this->assertFalse($spec->verificar(true)->permitido);
    }

    public function test_municipio_sem_pedido_em_edicao_abre(): void
    {
        $spec = new MunicipioPodeAbrirPedido();

        $this->assertTrue($spec->verificar(false)->permitido);
    }

    public function test_prazo_soma_dias_a_data_de_aprovacao(): void
    {
        $limite = (new PrazoPrestacaoContas())->calcular(
            CarbonImmutable::parse('2026-08-05'),
            30,
        );

        $this->assertSame('2026-09-04', $limite->toDateString());
    }

    public function test_prazo_com_zero_dias_vence_no_mesmo_dia(): void
    {
        $limite = (new PrazoPrestacaoContas())->calcular(
            CarbonImmutable::parse('2026-08-05'),
            0,
        );

        $this->assertSame('2026-08-05', $limite->toDateString());
    }

    public function test_vencimento_e_avaliado_por_data(): void
    {
        $spec   = new PrazoPrestacaoContas();
        $limite = CarbonImmutable::parse('2026-08-05');

        $this->assertFalse($spec->estaVencido($limite, CarbonImmutable::parse('2026-08-05')));
        $this->assertTrue($spec->estaVencido($limite, CarbonImmutable::parse('2026-08-06')));
        $this->assertFalse($spec->estaVencido($limite, CarbonImmutable::parse('2026-08-04')));
    }

    public function test_saldo_e_material_menos_entregue(): void
    {
        $spec = new SaldoEntregaBeneficiarios();

        $this->assertSame(70, $spec->saldo(100, 30));
        $this->assertSame(0, $spec->saldo(100, 100));
    }

    public function test_saldo_nunca_e_negativo(): void
    {
        $spec = new SaldoEntregaBeneficiarios();

        $this->assertSame(0, $spec->saldo(100, 150));
    }

    public function test_entrega_dentro_do_saldo_e_permitida(): void
    {
        $spec = new SaldoEntregaBeneficiarios();

        $this->assertTrue($spec->verificar(100, 30, 70)->permitido);
    }

    public function test_entrega_que_estoura_o_saldo_e_bloqueada(): void
    {
        $spec      = new SaldoEntregaBeneficiarios();
        $resultado = $spec->verificar(100, 30, 71);

        $this->assertFalse($resultado->permitido);
        $this->assertStringContainsString('70', (string) $resultado->motivo);
    }

    public function test_entrega_de_quantidade_nao_positiva_e_bloqueada(): void
    {
        $spec = new SaldoEntregaBeneficiarios();

        $this->assertFalse($spec->verificar(100, 0, 0)->permitido);
        $this->assertFalse($spec->verificar(100, 0, -5)->permitido);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=SpecificationsTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Domain\Specifications\MunicipioPodeAbrirPedido" not found`.

- [ ] **Step 3: Implementar as tres specifications**

`app/Modules/AjudaHumanitaria/Domain/Specifications/MunicipioPodeAbrirPedido.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;

/**
 * RN-03: o municipio nao abre pedido novo enquanto tiver um em edicao.
 *
 * O legado tinha duas versoes dessa regra. A que funcionava contava pedidos
 * com status 0 (buscaStatus); a outra, que contava status menor que 4
 * (compdecVerificaPedido), retornava sempre verdadeiro por erro de
 * implementacao. Esta specification reproduz a que funcionava.
 */
final class MunicipioPodeAbrirPedido
{
    public function verificar(bool $temPedidoEmEdicao): ResultadoGuarda
    {
        if (! $temPedidoEmEdicao) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'O município já possui um pedido em edição. Conclua ou cancele o pedido atual antes de abrir outro.'
        );
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Specifications/PrazoPrestacaoContas.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use Carbon\CarbonImmutable;

/**
 * RN-16: prazo da prestacao de contas e a data de aprovacao somada ao numero
 * de dias configurado no modulo.
 */
final class PrazoPrestacaoContas
{
    public function calcular(CarbonImmutable $dataAprovacao, int $prazoDias): CarbonImmutable
    {
        return $dataAprovacao->startOfDay()->addDays($prazoDias);
    }

    public function estaVencido(CarbonImmutable $dataLimite, CarbonImmutable $hoje): bool
    {
        return $hoje->startOfDay()->greaterThan($dataLimite->startOfDay());
    }
}
```

`app/Modules/AjudaHumanitaria/Domain/Specifications/SaldoEntregaBeneficiarios.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;

/**
 * RN-18: a soma das quantidades entregues aos beneficiarios nao pode exceder
 * a quantidade de material daquele item da prestacao.
 *
 * Equivale a verificaRestanteBenef do legado, que calculava o restante como
 * QtdMaterialPrest menos percBenef.
 */
final class SaldoEntregaBeneficiarios
{
    public function saldo(int $qtdMaterial, int $qtdJaEntregue): int
    {
        return max(0, $qtdMaterial - $qtdJaEntregue);
    }

    public function verificar(int $qtdMaterial, int $qtdJaEntregue, int $qtdNova): ResultadoGuarda
    {
        if ($qtdNova <= 0) {
            return ResultadoGuarda::bloquear('A quantidade entregue deve ser maior que zero.');
        }

        $saldo = $this->saldo($qtdMaterial, $qtdJaEntregue);

        if ($qtdNova > $saldo) {
            return ResultadoGuarda::bloquear(sprintf(
                'Quantidade acima do saldo disponível. Restam %d de %d.',
                $saldo,
                $qtdMaterial,
            ));
        }

        return ResultadoGuarda::permitir();
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=SpecificationsTest
```
Esperado: PASS, 10 testes.

- [ ] **Step 5: Rodar a suite de dominio inteira**

```
TESTAR tests/Unit/AjudaHumanitaria
```
Esperado: PASS. Confirma que o teste de arquitetura da Task 3 continua verde com os novos arquivos sob `Domain/`.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Domain/Specifications SDC/tests/Unit/AjudaHumanitaria/Domain/SpecificationsTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): specifications do MAH' '' 'Abertura de pedido por municipio, prazo da prestacao de contas e saldo' 'de entrega a beneficiarios, todas puras e sem banco.' '' 'Cobre RN-03, RN-16 e RN-18. A RN-03 reproduz a versao do legado que' 'funcionava, nao a que retornava sempre verdadeiro.')"
```

---

### Task 7: Contratos de repositorio

Interfaces que a fase 2 implementa. Entram agora para fechar o desenho do dominio e permitir que a fase 2 comece por qualquer ponta.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Repositories/PrestacaoContaRepositoryInterface.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Repositories/MaterialAhRepositoryInterface.php`
- Create: `app/Modules/AjudaHumanitaria/Domain/Repositories/SaldoMaterialRepositoryInterface.php`
- Test: `tests/Unit/AjudaHumanitaria/Domain/RepositoryContractsTest.php`

**Interfaces:**
- Consumes: `StatusPedidoAh` da Task 2; `TipoItemPedido` da Task 1
- Produces:
  - `PedidoAhRepositoryInterface`: `proximoNumeroDoAno(int $ano): int`, `municipioTemPedidoEmEdicao(int $municipioId): bool`, `contarItensPorTipo(int $pedidoId, TipoItemPedido $tipo): int`, `temParecerFavoravel(int $pedidoId): bool`, `temAgendamentoAprovado(int $pedidoId): bool`, `atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void`, `registrarTramite(int $pedidoId, StatusPedidoAh $anterior, StatusPedidoAh $novo, ?string $observacao, int $usuarioId): void`
  - `PrestacaoContaRepositoryInterface`: `abrirParaPedido(int $pedidoId, \Carbon\CarbonImmutable $dataLimite): int`, `copiarItensLiberados(int $pedidoId, int $prestacaoContaId): int`, `quantidadeDoItem(int $prestacaoContaItemId): int`, `quantidadeJaEntregue(int $prestacaoContaItemId): int`, `homologar(int $prestacaoContaId, int $usuarioId): void`
  - `MaterialAhRepositoryInterface`: `disponiveisParaPedido(): array`, `definirDisponibilidade(int $materialId, bool $disponivel): void`
  - `SaldoMaterialRepositoryInterface`: `saldoPorDeposito(?string $codigoLegado = null): array`, `disponivel(): bool`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/AjudaHumanitaria/Domain/RepositoryContractsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RepositoryContractsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: array<int, string>}>
     */
    public static function contratoProvider(): array
    {
        return [
            'pedido' => [PedidoAhRepositoryInterface::class, [
                'proximoNumeroDoAno',
                'municipioTemPedidoEmEdicao',
                'contarItensPorTipo',
                'temParecerFavoravel',
                'temAgendamentoAprovado',
                'atualizarStatus',
                'registrarTramite',
            ]],
            'prestacao' => [PrestacaoContaRepositoryInterface::class, [
                'abrirParaPedido',
                'copiarItensLiberados',
                'quantidadeDoItem',
                'quantidadeJaEntregue',
                'homologar',
            ]],
            'material' => [MaterialAhRepositoryInterface::class, [
                'disponiveisParaPedido',
                'definirDisponibilidade',
            ]],
            'saldo' => [SaldoMaterialRepositoryInterface::class, [
                'saldoPorDeposito',
                'disponivel',
            ]],
        ];
    }

    /**
     * @param  class-string  $contrato
     * @param  array<int, string>  $metodos
     */
    #[DataProvider('contratoProvider')]
    public function test_contrato_expoe_exatamente_os_metodos_previstos(string $contrato, array $metodos): void
    {
        $reflexao = new ReflectionClass($contrato);

        $this->assertTrue($reflexao->isInterface(), "{$contrato} deve ser interface");

        $declarados = array_map(
            fn (\ReflectionMethod $m) => $m->getName(),
            $reflexao->getMethods(),
        );

        $this->assertEqualsCanonicalizing($metodos, $declarados);
    }

    public function test_segregacao_nenhum_contrato_passa_de_sete_metodos(): void
    {
        foreach (self::contratoProvider() as $rotulo => [$contrato, $metodos]) {
            $this->assertLessThanOrEqual(
                7,
                count($metodos),
                "Contrato {$rotulo} grande demais; considere segregar."
            );
        }
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=RepositoryContractsTest
```
Esperado: FAIL com `Interface "App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface" not found`.

- [ ] **Step 3: Implementar as quatro interfaces**

`app/Modules/AjudaHumanitaria/Domain/Repositories/PedidoAhRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;

/**
 * Persistencia do pedido e dos fatos que alimentam o ContextoTransicao.
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
 */
interface PedidoAhRepositoryInterface
{
    /** RN-01: proximo numero sequencial do ano informado. */
    public function proximoNumeroDoAno(int $ano): int;

    /** RN-03: existe pedido em status EdicaoCompdec para o municipio. */
    public function municipioTemPedidoEmEdicao(int $municipioId): bool;

    /** RN-08: quantos itens do tipo informado o pedido possui. */
    public function contarItensPorTipo(int $pedidoId, TipoItemPedido $tipo): int;

    /** RN-11: existe ao menos um parecer favoravel. */
    public function temParecerFavoravel(int $pedidoId): bool;

    /** RN-21: existe agendamento de retirada aprovado. */
    public function temAgendamentoAprovado(int $pedidoId): bool;

    public function atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void;

    /** RN-14: grava o log da transicao. */
    public function registrarTramite(
        int $pedidoId,
        StatusPedidoAh $anterior,
        StatusPedidoAh $novo,
        ?string $observacao,
        int $usuarioId,
    ): void;
}
```

`app/Modules/AjudaHumanitaria/Domain/Repositories/PrestacaoContaRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use Carbon\CarbonImmutable;

/**
 * Persistencia da prestacao de contas.
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
 */
interface PrestacaoContaRepositoryInterface
{
    /** RN-16: cria o cabecalho da prestacao e devolve o id. */
    public function abrirParaPedido(int $pedidoId, CarbonImmutable $dataLimite): int;

    /**
     * RN-15: copia os itens tipo Liberado do pedido para a prestacao.
     * Devolve quantos itens foram copiados.
     */
    public function copiarItensLiberados(int $pedidoId, int $prestacaoContaId): int;

    /** RN-18: quantidade de material do item da prestacao. */
    public function quantidadeDoItem(int $prestacaoContaItemId): int;

    /** RN-18: soma das quantidades ja entregues a beneficiarios do item. */
    public function quantidadeJaEntregue(int $prestacaoContaItemId): int;

    /** RN-19: marca a prestacao como homologada. */
    public function homologar(int $prestacaoContaId, int $usuarioId): void;
}
```

`app/Modules/AjudaHumanitaria/Domain/Repositories/MaterialAhRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

/**
 * Catalogo de material disponivel para pedido (RN-07).
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
 */
interface MaterialAhRepositoryInterface
{
    /**
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function disponiveisParaPedido(): array;

    public function definirDisponibilidade(int $materialId, bool $disponivel): void;
}
```

`app/Modules/AjudaHumanitaria/Domain/Repositories/SaldoMaterialRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

/**
 * Leitura de saldo de material por deposito (RN-25).
 *
 * A implementacao da fase 2 le a base legada em modo somente leitura. Quando
 * o estoque virar nativo, basta trocar o bind: nada no dominio muda.
 *
 * disponivel() permite a interface degradar em vez de estourar quando a
 * conexao legada estiver fora do ar.
 */
interface SaldoMaterialRepositoryInterface
{
    /**
     * @return array<int, array{deposito: string, material: string, saldo: int}>
     */
    public function saldoPorDeposito(?string $codigoLegado = null): array;

    public function disponivel(): bool;
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=RepositoryContractsTest
```
Esperado: PASS, 5 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Domain/Repositories SDC/tests/Unit/AjudaHumanitaria/Domain/RepositoryContractsTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): contratos de repositorio do MAH' '' 'Quatro interfaces segregadas por caso de uso em vez de um repositorio' 'unico: pedido, prestacao de contas, catalogo de material e saldo.' '' 'O contrato de saldo isola a base legada atras de uma abstracao, com' 'disponivel() para permitir degradacao quando a conexao cair.')"
```

---

### Task 8: Migration consolidada

As dez tabelas em um unico arquivo, na ordem de dependencia. Uma migration so, em vez de dez, para que o schema do modulo seja editavel como uma unidade enquanto o desenho amadurece.

**Files:**
- Create: `database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php`
- Test: `tests/Feature/AjudaHumanitaria/SchemaMahTest.php`

**Interfaces:**
- Consumes: nada
- Produces: tabelas `materiais_ah`, `parametros_ah`, `pedidos_ah`, `pedido_ah_itens`, `pedido_ah_pareceres`, `pedido_ah_tramites`, `pedido_ah_agendamentos`, `prestacoes_conta`, `prestacao_conta_itens`, `prestacao_conta_entregas`

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/SchemaMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SchemaMahTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Um municipio qualquer que ja exista no banco. Evita inserir registro em
     * tabela de referencia compartilhada.
     */
    private function municipioExistente(): int
    {
        $id = DB::table('municipios')->value('id');

        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios cadastrados.');
        }

        return (int) $id;
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function tabelaProvider(): array
    {
        return [
            'materiais_ah' => ['materiais_ah', [
                'id', 'nome', 'descricao', 'unidade_medida',
                'disponivel_para_pedido', 'codigo_legado',
            ]],
            'parametros_ah' => ['parametros_ah', [
                'id', 'prazo_prestacao_contas_dias',
            ]],
            'pedidos_ah' => ['pedidos_ah', [
                'id', 'numero', 'ano', 'municipio_id', 'cobrade_id', 'pop_atendida',
                'decreto_se_ecp_vig', 'tipo_decreto', 'numero_decreto', 'vigencia_decreto',
                'esforcos_realizados', 'nome_coordenador', 'tel_coordenador',
                'cel_coordenador', 'email_coordenador', 'nome_prefeito', 'tel_prefeito',
                'cel_prefeito', 'email_prefeito', 'status', 'analista_id', 'diretor_id',
                'data_entrada_sistema', 'data_hora_envio', 'data_aprovacao',
                'created_by', 'deleted_at',
            ]],
            'pedido_ah_itens' => ['pedido_ah_itens', [
                'id', 'pedido_ah_id', 'material_ah_id', 'codigo', 'descricao_item',
                'qtd', 'qtd_familia_atendida', 'tipo',
            ]],
            'pedido_ah_pareceres' => ['pedido_ah_pareceres', [
                'id', 'pedido_ah_id', 'user_id', 'data_parecer', 'parecer',
                'situacao', 'etapa',
            ]],
            'pedido_ah_tramites' => ['pedido_ah_tramites', [
                'id', 'pedido_ah_id', 'status_anterior', 'status_novo',
                'observacao', 'user_id',
            ]],
            'pedido_ah_agendamentos' => ['pedido_ah_agendamentos', [
                'id', 'pedido_ah_id', 'municipio_id', 'data_retirada', 'horario',
                'status', 'motivo_recusa', 'usuario_aprovacao_id', 'data_aprovacao',
            ]],
            'prestacoes_conta' => ['prestacoes_conta', [
                'id', 'pedido_ah_id', 'status', 'data_limite',
                'homologado_por', 'homologado_em',
            ]],
            'prestacao_conta_itens' => ['prestacao_conta_itens', [
                'id', 'prestacao_conta_id', 'material_ah_id', 'codigo_material',
                'nome_material', 'qtd', 'total_familia_atendida',
            ]],
            'prestacao_conta_entregas' => ['prestacao_conta_entregas', [
                'id', 'prestacao_conta_item_id', 'nome_beneficiario', 'rg',
                'comunidade', 'qtd', 'data_entrega',
            ]],
        ];
    }

    /**
     * @param  array<int, string>  $colunas
     */
    #[DataProvider('tabelaProvider')]
    public function test_tabela_existe_com_as_colunas_previstas(string $tabela, array $colunas): void
    {
        $this->assertTrue(Schema::hasTable($tabela), "Tabela {$tabela} nao existe");

        foreach ($colunas as $coluna) {
            $this->assertTrue(
                Schema::hasColumn($tabela, $coluna),
                "Coluna {$tabela}.{$coluna} nao existe"
            );
        }
    }

    public function test_pedidos_ah_nao_carrega_regiao_id(): void
    {
        $this->assertFalse(
            Schema::hasColumn('pedidos_ah', 'regiao_id'),
            'Nao existe mapeamento municipio para REDEC no NewSDC; o escopo regional resolve por municipios.mesorregiao.'
        );
    }

    public function test_numero_e_unico_por_ano(): void
    {
        $base = [
            'numero' => 999_001, 'ano' => 2099, 'municipio_id' => $this->municipioExistente(),
            'pop_atendida' => 100, 'esforcos_realizados' => 'x', 'status' => 0,
            'data_entrada_sistema' => now(), 'created_at' => now(), 'updated_at' => now(),
        ];

        DB::table('pedidos_ah')->insert($base);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        DB::table('pedidos_ah')->insert($base);
    }

    public function test_mesmo_numero_em_ano_diferente_e_aceito(): void
    {
        $base = [
            'numero' => 999_002, 'municipio_id' => $this->municipioExistente(),
            'pop_atendida' => 100, 'esforcos_realizados' => 'x', 'status' => 0,
            'data_entrada_sistema' => now(), 'created_at' => now(), 'updated_at' => now(),
        ];

        DB::table('pedidos_ah')->insert($base + ['ano' => 2098]);
        DB::table('pedidos_ah')->insert($base + ['ano' => 2099]);

        $this->assertSame(
            2,
            DB::table('pedidos_ah')->where('numero', 999_002)->count(),
        );
    }

    public function test_parametros_ah_nasce_com_a_linha_padrao(): void
    {
        $linha = DB::table('parametros_ah')->first();

        $this->assertNotNull($linha, 'A migration deve semear a linha unica de parametros.');
        $this->assertSame(30, (int) $linha->prazo_prestacao_contas_dias);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=SchemaMahTest
```
Esperado: FAIL com `Tabela materiais_ah nao existe`.

- [ ] **Step 3: Escrever a migration**

Criar `database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema do processo de Pedido de Material de Ajuda Humanitaria (MAH).
 *
 * Consolidado em um unico arquivo: o modulo nasce inteiro aqui, e alteracoes
 * de desenho durante a construcao editam esta migration em vez de empilhar
 * arquivos de patch.
 *
 * pedidos_ah nao tem regiao_id de proposito. Nao existe mapeamento municipio
 * para REDEC no NewSDC; o escopo regional resolve por municipios.mesorregiao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiais_ah', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('unidade_medida', 30)->default('UN');
            $table->boolean('disponivel_para_pedido')->default(true);
            $table->string('codigo_legado', 30)->nullable()
                ->comment('aju_unidade.id_unidade, ponte para o saldo do deposito legado');
            $table->timestamps();

            $table->index('disponivel_para_pedido');
            $table->index('codigo_legado');
        });

        Schema::create('parametros_ah', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('prazo_prestacao_contas_dias')->default(30);
            $table->timestamps();
        });

        Schema::create('pedidos_ah', function (Blueprint $table): void {
            $table->id();

            $table->unsignedInteger('numero');
            $table->unsignedSmallInteger('ano');

            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cobrade_id')->nullable()->constrained('dec_cobrade')->nullOnDelete();

            $table->unsignedInteger('pop_atendida')->default(0);

            $table->boolean('decreto_se_ecp_vig')->default(false);
            $table->string('tipo_decreto', 3)->nullable();
            $table->string('numero_decreto', 50)->nullable();
            $table->date('vigencia_decreto')->nullable();

            $table->text('esforcos_realizados');

            $table->string('nome_coordenador')->nullable();
            $table->string('tel_coordenador', 20)->nullable();
            $table->string('cel_coordenador', 20)->nullable();
            $table->string('email_coordenador')->nullable();

            $table->string('nome_prefeito')->nullable();
            $table->string('tel_prefeito', 20)->nullable();
            $table->string('cel_prefeito', 20)->nullable();
            $table->string('email_prefeito')->nullable();

            $table->unsignedSmallInteger('status')->default(0);

            $table->foreignId('analista_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diretor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('data_entrada_sistema');
            $table->timestamp('data_hora_envio')->nullable();
            $table->timestamp('data_aprovacao')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'ano']);
            $table->index(['municipio_id', 'status']);
            $table->index(['ano', 'status']);
        });

        Schema::create('pedido_ah_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->nullable()->constrained('materiais_ah')->nullOnDelete();
            $table->string('codigo', 30)->nullable();
            $table->string('descricao_item');
            $table->unsignedInteger('qtd')->default(0);
            $table->unsignedInteger('qtd_familia_atendida')->default(0);
            $table->char('tipo', 1)->comment('P = solicitado pelo municipio, L = liberado pelo CEDEC');
            $table->timestamps();

            $table->index(['pedido_ah_id', 'tipo']);
        });

        Schema::create('pedido_ah_pareceres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data_parecer');
            $table->text('parecer');
            $table->string('situacao', 20);
            $table->string('etapa', 30);
            $table->timestamps();

            $table->index(['pedido_ah_id', 'situacao']);
        });

        Schema::create('pedido_ah_tramites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->unsignedSmallInteger('status_anterior');
            $table->unsignedSmallInteger('status_novo');
            $table->text('observacao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pedido_ah_id', 'created_at']);
        });

        Schema::create('pedido_ah_agendamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios')->restrictOnDelete();
            $table->date('data_retirada');
            $table->string('horario', 5)->comment('HH:MM do slot');
            $table->string('status', 20)->default('pendente');
            $table->text('motivo_recusa')->nullable();
            $table->foreignId('usuario_aprovacao_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamps();

            $table->index(['pedido_ah_id', 'status']);
            $table->index(['data_retirada', 'horario']);
        });

        Schema::create('prestacoes_conta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->unique()->constrained('pedidos_ah')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente');
            $table->date('data_limite')->nullable();
            $table->foreignId('homologado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('homologado_em')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('prestacao_conta_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prestacao_conta_id')->constrained('prestacoes_conta')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->nullable()->constrained('materiais_ah')->nullOnDelete();
            $table->string('codigo_material', 30)->nullable();
            $table->string('nome_material');
            $table->unsignedInteger('qtd')->default(0);
            $table->unsignedInteger('total_familia_atendida')->default(0);
            $table->timestamps();

            $table->index('prestacao_conta_id');
        });

        Schema::create('prestacao_conta_entregas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prestacao_conta_item_id')->constrained('prestacao_conta_itens')->cascadeOnDelete();
            $table->string('nome_beneficiario');
            $table->string('rg', 30)->nullable();
            $table->string('comunidade')->nullable();
            $table->unsignedInteger('qtd')->default(0);
            $table->date('data_entrega');
            $table->timestamps();

            $table->index('prestacao_conta_item_id');
        });

        DB::table('parametros_ah')->insert([
            'prazo_prestacao_contas_dias' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('prestacao_conta_entregas');
        Schema::dropIfExists('prestacao_conta_itens');
        Schema::dropIfExists('prestacoes_conta');
        Schema::dropIfExists('pedido_ah_agendamentos');
        Schema::dropIfExists('pedido_ah_tramites');
        Schema::dropIfExists('pedido_ah_pareceres');
        Schema::dropIfExists('pedido_ah_itens');
        Schema::dropIfExists('pedidos_ah');
        Schema::dropIfExists('parametros_ah');
        Schema::dropIfExists('materiais_ah');
    }
};
```

- [ ] **Step 4: Confirmar que a migration sobe e desce**

Como os testes usam `DatabaseTransactions` sobre o Postgres de desenvolvimento,
a migration precisa ser aplicada antes de o teste poder passar. Aplique, reverta
e aplique de novo, para provar os dois sentidos:

```powershell
# mesmas variaveis de ambiente do bloco TESTAR, trocando phpunit por artisan
& $php artisan migrate --force
& $php artisan migrate:rollback --step=1
& $php artisan migrate --force
```
Esperado: as dez tabelas sao criadas, removidas e recriadas sem erro de
dependencia de chave estrangeira. O rollback exercita a ordem inversa do
`down()`.

Nao rodar `migrate:fresh` nem `migrate:refresh`: apagariam o banco de
desenvolvimento do usuario.

- [ ] **Step 5: Rodar o teste e confirmar que passa**

```
TESTAR --filter=SchemaMahTest
```
Esperado: PASS, 14 testes.

Se `test_numero_e_unico_por_ano` falhar por nome de excecao, confirme a classe que o driver PostgreSQL lanca e ajuste o `expectException` para ela.

- [ ] **Step 6: Commit**

```bash
git add SDC/database/migrations/2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php SDC/tests/Feature/AjudaHumanitaria/SchemaMahTest.php
git commit -m "$(printf '%s\n' '🗃️ db(ajuda-humanitaria): schema do processo MAH' '' 'Dez tabelas em uma migration consolidada, na ordem de dependencia, com' 'a linha unica de parametros semeada.' '' 'numero e unico por ano, atendendo a RN-01. pedidos_ah nao carrega' 'regiao_id porque nao existe mapeamento municipio para REDEC no NewSDC;' 'o escopo regional resolve por municipios.mesorregiao na fase 2.')"
```

---

### Task 9: Models

Dez models finos: relacoes, casts para os enums e nada de regra de negocio. A regra ja vive no dominio.

**Files:**
- Create: `app/Modules/AjudaHumanitaria/Models/PedidoAh.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PedidoAhItem.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PedidoAhParecer.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PedidoAhTramite.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PedidoAhAgendamento.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PrestacaoConta.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PrestacaoContaItem.php`
- Create: `app/Modules/AjudaHumanitaria/Models/PrestacaoContaEntrega.php`
- Create: `app/Modules/AjudaHumanitaria/Models/MaterialAh.php`
- Create: `app/Modules/AjudaHumanitaria/Models/ParametroAh.php`
- Test: `tests/Feature/AjudaHumanitaria/ModelsMahTest.php`

**Interfaces:**
- Consumes: enums das Tasks 1 e 2; tabelas da Task 8
- Produces:
  - `PedidoAh` com `itens()`, `itensPedido()`, `itensLiberados()`, `pareceres()`, `tramites()`, `agendamentos()`, `prestacaoConta()`, `municipio()`, e accessor `identificador` no formato `numero/ano`
  - `PrestacaoConta` com `itens()` e `pedido()`
  - `PrestacaoContaItem` com `entregas()` e `prestacaoConta()`
  - demais models com `pedido()` ou `prestacaoConta()` conforme o vinculo

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/ModelsMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ModelsMahTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();

        $id = DB::table('municipios')->value('id');

        if ($id === null) {
            $this->markTestSkipped('Banco de desenvolvimento sem municipios cadastrados.');
        }

        $this->municipioId = (int) $id;
    }

    private function criarPedido(int $numero = 999_101, int $ano = 2099): PedidoAh
    {
        return PedidoAh::create([
            'numero' => $numero,
            'ano' => $ano,
            'municipio_id' => $this->municipioId,
            'pop_atendida' => 1200,
            'esforcos_realizados' => 'Distribuição de cestas pela equipe local.',
            'status' => StatusPedidoAh::EdicaoCompdec,
            'data_entrada_sistema' => now(),
        ]);
    }

    public function test_status_e_lido_como_enum(): void
    {
        $pedido = $this->criarPedido()->fresh();

        $this->assertInstanceOf(StatusPedidoAh::class, $pedido->status);
        $this->assertSame(StatusPedidoAh::EdicaoCompdec, $pedido->status);
    }

    public function test_identificador_combina_numero_e_ano(): void
    {
        $pedido = $this->criarPedido(numero: 7, ano: 2026);

        $this->assertSame('7/2026', $pedido->identificador);
    }

    public function test_itens_separam_pedido_de_liberado(): void
    {
        $pedido   = $this->criarPedido();
        $material = MaterialAh::create(['nome' => 'Cesta básica', 'unidade_medida' => 'UN']);

        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'material_ah_id' => $material->id,
            'descricao_item' => 'Cesta básica', 'qtd' => 100,
            'qtd_familia_atendida' => 100, 'tipo' => TipoItemPedido::Pedido,
        ]);
        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'material_ah_id' => $material->id,
            'descricao_item' => 'Cesta básica', 'qtd' => 60,
            'qtd_familia_atendida' => 60, 'tipo' => TipoItemPedido::Liberado,
        ]);

        $pedido->refresh();

        $this->assertCount(2, $pedido->itens);
        $this->assertCount(1, $pedido->itensPedido);
        $this->assertCount(1, $pedido->itensLiberados);
        $this->assertSame(100, $pedido->itensPedido->first()->qtd);
        $this->assertSame(60, $pedido->itensLiberados->first()->qtd);
    }

    public function test_tipo_do_item_e_lido_como_enum(): void
    {
        $pedido = $this->criarPedido();

        $item = PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'descricao_item' => 'Colchão',
            'qtd' => 10, 'qtd_familia_atendida' => 10, 'tipo' => TipoItemPedido::Liberado,
        ])->fresh();

        $this->assertSame(TipoItemPedido::Liberado, $item->tipo);
    }

    public function test_parecer_pertence_ao_pedido_e_situacao_e_enum(): void
    {
        $pedido = $this->criarPedido();

        $parecer = PedidoAhParecer::create([
            'pedido_ah_id' => $pedido->id,
            'data_parecer' => now()->toDateString(),
            'parecer' => 'Pedido compatível com o desastre informado.',
            'situacao' => SituacaoParecer::Favoravel,
            'etapa' => \App\Modules\AjudaHumanitaria\Enums\EtapaParecer::AnaliseDlog,
        ])->fresh();

        $this->assertSame(SituacaoParecer::Favoravel, $parecer->situacao);
        $this->assertSame($pedido->id, $parecer->pedido->id);
        $this->assertCount(1, $pedido->fresh()->pareceres);
    }

    public function test_prestacao_encadeia_itens_e_entregas(): void
    {
        $pedido = $this->criarPedido();

        $prestacao = PrestacaoConta::create([
            'pedido_ah_id' => $pedido->id,
            'status' => StatusPrestacaoConta::EmLancamento,
            'data_limite' => now()->addDays(30)->toDateString(),
        ]);

        $item = PrestacaoContaItem::create([
            'prestacao_conta_id' => $prestacao->id,
            'nome_material' => 'Cesta básica',
            'qtd' => 60,
            'total_familia_atendida' => 60,
        ]);

        PrestacaoContaEntrega::create([
            'prestacao_conta_item_id' => $item->id,
            'nome_beneficiario' => 'Maria da Silva',
            'qtd' => 2,
            'data_entrega' => now()->toDateString(),
        ]);

        $prestacao->refresh();

        $this->assertSame(StatusPrestacaoConta::EmLancamento, $prestacao->status);
        $this->assertCount(1, $prestacao->itens);
        $this->assertCount(1, $prestacao->itens->first()->entregas);
        $this->assertSame($pedido->id, $prestacao->pedido->id);
        $this->assertSame($prestacao->id, $pedido->prestacaoConta->id);
    }

    public function test_material_filtra_disponiveis_para_pedido(): void
    {
        MaterialAh::create(['nome' => 'Cesta básica', 'disponivel_para_pedido' => true]);
        MaterialAh::create(['nome' => 'Item descontinuado', 'disponivel_para_pedido' => false]);

        $this->assertCount(1, MaterialAh::disponiveisParaPedido()->get());
    }

    public function test_parametro_expoe_a_linha_unica(): void
    {
        $this->assertSame(30, ParametroAh::atual()->prazo_prestacao_contas_dias);
    }

    public function test_exclusao_do_pedido_leva_os_filhos(): void
    {
        $pedido = $this->criarPedido();

        PedidoAhItem::create([
            'pedido_ah_id' => $pedido->id, 'descricao_item' => 'Kit higiene',
            'qtd' => 5, 'qtd_familia_atendida' => 5, 'tipo' => TipoItemPedido::Pedido,
        ]);

        $pedido->forceDelete();

        $this->assertSame(0, PedidoAhItem::count());
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=ModelsMahTest
```
Esperado: FAIL com `Class "App\Modules\AjudaHumanitaria\Models\PedidoAh" not found`.

- [ ] **Step 3: Implementar os dez models**

`app/Modules/AjudaHumanitaria/Models/PedidoAh.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Raiz do agregado do pedido de material de ajuda humanitaria.
 *
 * Model fino de proposito: a regra de negocio vive em
 * App\Modules\AjudaHumanitaria\Domain. Aqui ficam apenas relacoes, casts e
 * leitura derivada de apresentacao.
 */
class PedidoAh extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_ah';

    protected $fillable = [
        'numero',
        'ano',
        'municipio_id',
        'cobrade_id',
        'pop_atendida',
        'decreto_se_ecp_vig',
        'tipo_decreto',
        'numero_decreto',
        'vigencia_decreto',
        'esforcos_realizados',
        'nome_coordenador',
        'tel_coordenador',
        'cel_coordenador',
        'email_coordenador',
        'nome_prefeito',
        'tel_prefeito',
        'cel_prefeito',
        'email_prefeito',
        'status',
        'analista_id',
        'diretor_id',
        'data_entrada_sistema',
        'data_hora_envio',
        'data_aprovacao',
        'created_by',
    ];

    protected $casts = [
        'numero'               => 'integer',
        'ano'                  => 'integer',
        'pop_atendida'         => 'integer',
        'decreto_se_ecp_vig'   => 'boolean',
        'tipo_decreto'         => TipoDecreto::class,
        'vigencia_decreto'     => 'date',
        'status'               => StatusPedidoAh::class,
        'data_entrada_sistema' => 'datetime',
        'data_hora_envio'      => 'datetime',
        'data_aprovacao'       => 'datetime',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoAhItem::class, 'pedido_ah_id');
    }

    public function itensPedido(): HasMany
    {
        return $this->itens()->where('tipo', TipoItemPedido::Pedido->value);
    }

    public function itensLiberados(): HasMany
    {
        return $this->itens()->where('tipo', TipoItemPedido::Liberado->value);
    }

    public function pareceres(): HasMany
    {
        return $this->hasMany(PedidoAhParecer::class, 'pedido_ah_id');
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(PedidoAhTramite::class, 'pedido_ah_id')->orderBy('created_at');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(PedidoAhAgendamento::class, 'pedido_ah_id');
    }

    public function prestacaoConta(): HasOne
    {
        return $this->hasOne(PrestacaoConta::class, 'pedido_ah_id');
    }

    /**
     * RN-01: identificador exibido ao usuario.
     */
    public function getIdentificadorAttribute(): string
    {
        return "{$this->numero}/{$this->ano}";
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PedidoAhItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhItem extends Model
{
    protected $table = 'pedido_ah_itens';

    protected $fillable = [
        'pedido_ah_id',
        'material_ah_id',
        'codigo',
        'descricao_item',
        'qtd',
        'qtd_familia_atendida',
        'tipo',
    ];

    protected $casts = [
        'qtd'                  => 'integer',
        'qtd_familia_atendida' => 'integer',
        'tipo'                 => TipoItemPedido::class,
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PedidoAhParecer.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhParecer extends Model
{
    protected $table = 'pedido_ah_pareceres';

    protected $fillable = [
        'pedido_ah_id',
        'user_id',
        'data_parecer',
        'parecer',
        'situacao',
        'etapa',
    ];

    protected $casts = [
        'data_parecer' => 'date',
        'situacao'     => SituacaoParecer::class,
        'etapa'        => EtapaParecer::class,
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PedidoAhTramite.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-14: log imutavel de tramitacao. Nao ha update nem delete previstos.
 */
class PedidoAhTramite extends Model
{
    protected $table = 'pedido_ah_tramites';

    protected $fillable = [
        'pedido_ah_id',
        'status_anterior',
        'status_novo',
        'observacao',
        'user_id',
    ];

    protected $casts = [
        'status_anterior' => StatusPedidoAh::class,
        'status_novo'     => StatusPedidoAh::class,
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PedidoAhAgendamento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusAgendamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAhAgendamento extends Model
{
    protected $table = 'pedido_ah_agendamentos';

    protected $fillable = [
        'pedido_ah_id',
        'municipio_id',
        'data_retirada',
        'horario',
        'status',
        'motivo_recusa',
        'usuario_aprovacao_id',
        'data_aprovacao',
    ];

    protected $casts = [
        'data_retirada'  => 'date',
        'status'         => StatusAgendamento::class,
        'data_aprovacao' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_aprovacao_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PrestacaoConta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestacaoConta extends Model
{
    protected $table = 'prestacoes_conta';

    protected $fillable = [
        'pedido_ah_id',
        'status',
        'data_limite',
        'homologado_por',
        'homologado_em',
    ];

    protected $casts = [
        'status'        => StatusPrestacaoConta::class,
        'data_limite'   => 'date',
        'homologado_em' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoAh::class, 'pedido_ah_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PrestacaoContaItem::class, 'prestacao_conta_id');
    }

    public function homologador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homologado_por');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PrestacaoContaItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestacaoContaItem extends Model
{
    protected $table = 'prestacao_conta_itens';

    protected $fillable = [
        'prestacao_conta_id',
        'material_ah_id',
        'codigo_material',
        'nome_material',
        'qtd',
        'total_familia_atendida',
    ];

    protected $casts = [
        'qtd'                    => 'integer',
        'total_familia_atendida' => 'integer',
    ];

    public function prestacaoConta(): BelongsTo
    {
        return $this->belongsTo(PrestacaoConta::class, 'prestacao_conta_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(PrestacaoContaEntrega::class, 'prestacao_conta_item_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/PrestacaoContaEntrega.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-17: entrega de material a um beneficiario, dentro de um item da
 * prestacao de contas.
 */
class PrestacaoContaEntrega extends Model
{
    protected $table = 'prestacao_conta_entregas';

    protected $fillable = [
        'prestacao_conta_item_id',
        'nome_beneficiario',
        'rg',
        'comunidade',
        'qtd',
        'data_entrega',
    ];

    protected $casts = [
        'qtd'          => 'integer',
        'data_entrega' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContaItem::class, 'prestacao_conta_item_id');
    }
}
```

`app/Modules/AjudaHumanitaria/Models/MaterialAh.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * RN-07: catalogo de material. A disponibilidade para pedido e configuravel
 * pelo CEDEC, em vez de lista fixa em codigo.
 */
class MaterialAh extends Model
{
    protected $table = 'materiais_ah';

    protected $fillable = [
        'nome',
        'descricao',
        'unidade_medida',
        'disponivel_para_pedido',
        'codigo_legado',
    ];

    protected $casts = [
        'disponivel_para_pedido' => 'boolean',
    ];

    public function scopeDisponiveisParaPedido(Builder $query): Builder
    {
        return $query->where('disponivel_para_pedido', true);
    }
}
```

`app/Modules/AjudaHumanitaria/Models/ParametroAh.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RN-16: parametros do modulo em linha unica, editaveis pelo CEDEC sem
 * necessidade de deploy.
 */
class ParametroAh extends Model
{
    protected $table = 'parametros_ah';

    protected $fillable = [
        'prazo_prestacao_contas_dias',
    ];

    protected $casts = [
        'prazo_prestacao_contas_dias' => 'integer',
    ];

    /**
     * A linha unica de parametros. Cria com os valores padrao se ainda nao
     * existir, para que o modulo nunca opere sem parametro.
     */
    public static function atual(): self
    {
        return static::query()->firstOrCreate([], ['prazo_prestacao_contas_dias' => 30]);
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=ModelsMahTest
```
Esperado: PASS, 9 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Models SDC/tests/Feature/AjudaHumanitaria/ModelsMahTest.php
git commit -m "$(printf '%s\n' '✨ feat(ajuda-humanitaria): models do processo MAH' '' 'Dez models finos com relacoes e casts para os enums do modulo. Nenhuma' 'regra de negocio: ela vive no dominio.' '' 'itensPedido e itensLiberados separam o solicitado do liberado pelo' 'discriminador de tipo, atendendo RN-08 e RN-09 sem tabela extra.')"
```

---

### Task 10: ServiceProvider

Remove os dois binds para classes inexistentes e registra o workflow com a colecao de guardas.

**Files:**
- Modify: `app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php`
- Test: `tests/Feature/AjudaHumanitaria/ProviderMahTest.php`

**Interfaces:**
- Consumes: `PedidoAhWorkflow` da Task 5; as cinco guardas da Task 4
- Produces: `PedidoAhWorkflow` resolvivel do container como singleton, com as cinco guardas injetadas

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/AjudaHumanitaria/ProviderMahTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Tests\TestCase;

final class ProviderMahTest extends TestCase
{
    public function test_workflow_e_resolvivel_do_container(): void
    {
        $workflow = app(PedidoAhWorkflow::class);

        $this->assertInstanceOf(PedidoAhWorkflow::class, $workflow);
    }

    public function test_workflow_e_singleton(): void
    {
        $this->assertSame(
            app(PedidoAhWorkflow::class),
            app(PedidoAhWorkflow::class),
        );
    }

    public function test_workflow_do_container_tem_as_guardas_registradas(): void
    {
        $workflow = app(PedidoAhWorkflow::class);

        $semParecer = $workflow->verificar(new ContextoTransicao(
            statusAtual: StatusPedidoAh::AnaliseDlog,
            statusAlvo:  StatusPedidoAh::AnaliseDiretorDlog,
            temParecerFavoravel: false,
        ));

        $this->assertFalse(
            $semParecer->permitido,
            'Se passar, as guardas nao foram injetadas pelo provider.'
        );
    }

    public function test_provider_nao_referencia_classe_inexistente(): void
    {
        $arquivo = base_path('app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php');
        $conteudo = (string) file_get_contents($arquivo);

        $this->assertStringNotContainsString('BeneficiarioRepositoryInterface', $conteudo);
        $this->assertStringNotContainsString('EloquentBeneficiarioRepository', $conteudo);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```
TESTAR --filter=ProviderMahTest
```
Esperado: FAIL em `test_workflow_e_singleton` e em `test_provider_nao_referencia_classe_inexistente`.

- [ ] **Step 3: Reescrever o provider**

Substituir o conteudo de `app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeAgendamentoAprovado;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider do modulo Ajuda Humanitaria.
 *
 * As guardas de transicao sao declaradas aqui, em um unico lugar. Para
 * acrescentar uma regra de transicao, implemente GuardaTransicao e inclua a
 * classe em GUARDAS_TRANSICAO: nenhum service precisa mudar.
 *
 * Os binds de repositorio entram na fase 2, junto com as implementacoes sob
 * Infrastructure/Persistence.
 */
class AjudaHumanitariaServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string<\App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao>>
     */
    private const GUARDAS_TRANSICAO = [
        ExigeItemNoPedido::class,
        ExigeParecerFavoravel::class,
        ExigeItensLiberados::class,
        ExigeAgendamentoAprovado::class,
        FinalizacaoSomenteViaHomologacao::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PedidoAhWorkflow::class, function ($app): PedidoAhWorkflow {
            $guardas = array_map(
                static fn (string $guarda) => $app->make($guarda),
                self::GUARDAS_TRANSICAO,
            );

            return new PedidoAhWorkflow($guardas);
        });
    }

    /**
     * As rotas do modulo sao carregadas por routes/web.php dentro do grupo de
     * middleware auth, que inclui web. Nao usar loadRoutesFrom aqui: isso
     * registraria rotas sem sessao e sem autenticacao, resultando em 403 para
     * todos os usuarios. Mesmo padrao do RatServiceProvider.
     */
    public function boot(): void
    {
        //
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```
TESTAR --filter=ProviderMahTest
```
Esperado: PASS, 4 testes.

- [ ] **Step 5: Rodar a suite completa do modulo**

```
TESTAR tests/Unit/AjudaHumanitaria tests/Feature/AjudaHumanitaria
```
Esperado: PASS em tudo.

- [ ] **Step 6: Confirmar que nada existente regrediu**

```
TESTAR
```
Linha de base medida antes da Task 1, com o runner corrigido: **86 testes, 266
assercoes, 1 erro e 4 falhas**, concentradas em
`tests/Feature/PlanCon/PlanConUploadTest.php`. Todas pre-existentes.

Esperado apos a fase: o mesmo 1 erro e 4 falhas, mais os testes novos passando.
Qualquer falha diferente dessas cinco e regressao introduzida pela fase.

- [ ] **Step 7: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/AjudaHumanitariaServiceProvider.php SDC/tests/Feature/AjudaHumanitaria/ProviderMahTest.php
git commit -m "$(printf '%s\n' '🐛 fix(ajuda-humanitaria): provider apontava para classes inexistentes' '' 'Os binds de BeneficiarioRepositoryInterface e' 'EloquentBeneficiarioRepository referenciavam classes que nunca foram' 'criadas; resolver qualquer um deles lancava excecao.' '' 'No lugar, registra PedidoAhWorkflow como singleton com a colecao de' 'guardas declarada em um unico ponto do provider.')"
```

---

## Verificacao da fase

Ao concluir as dez tasks:

1. `TESTAR tests/Unit/AjudaHumanitaria` passa sem banco
2. `TESTAR tests/Feature/AjudaHumanitaria` passa
3. O teste de arquitetura da Task 3 confirma que nada sob `Domain/` importa `Illuminate` nem `App\Models`
4. `artisan migrate` e `artisan migrate:rollback --step=1` funcionam nas dez tabelas, sem uso de `migrate:fresh` nem `migrate:refresh`
5. Nenhum arquivo do mock foi removido; a pagina de Beneficiario continua funcionando
6. Regras cobertas nesta fase: RN-01 parcial (numeracao unica garantida por constraint; o calculo do proximo numero e fase 2), RN-03, RN-06, RN-08, RN-09, RN-10, RN-11, RN-12, RN-13, RN-16, RN-18, RN-19, RN-21
7. Regras que ficam para a fase 2: RN-02, RN-04, RN-05, RN-07, RN-14, RN-15, RN-17, RN-20, RN-22, RN-23, RN-24, RN-25
