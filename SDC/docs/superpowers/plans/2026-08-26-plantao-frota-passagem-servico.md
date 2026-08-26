# Plantao — Frota de Viaturas e Passagem de Servico — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Substituir o relatorio de passagem de servico digitado a mao no WhatsApp por um fluxo com lastro em banco: cadastro da frota CEDEC, registro de saida e retorno de cada viatura, e passagem de turno com aceite formal das duas partes, que gera o mesmo texto ao final.

**Architecture:** Extensao do modulo existente `app/Modules/Plantao`, sem novo modulo. A movimentacao individual de viatura e a fonte da verdade; o snapshot do turno e derivado dela e confirmado pelo plantonista. Servicos com uma responsabilidade cada; `MovimentacaoViaturaService` e o unico ponto que escreve o estado corrente da viatura. Frontend em atomic design reaproveitando a casca padrao (PageHeader, CollapsibleSection, StatCard, Pagination, ListEmptyState).

**Tech Stack:** Laravel 12 / PHP 8.3, PostgreSQL, Inertia.js + Vue 3, Tailwind, Ziggy, Spatie Laravel Permission, Octane/FrankenPHP.

**Spec:** `docs/superpowers/plans/../specs/2026-08-26-plantao-frota-passagem-servico-design.md`
(caminho absoluto no repo: `SDC/docs/superpowers/specs/2026-08-26-plantao-frota-passagem-servico-design.md`)

## Global Constraints

- **Regra de ouro 2 — sem emoji no codigo.** Nenhum emoji em `.php` ou `.vue`. O unico arquivo com emoji e `resources/views/plantao/passagem-servico.txt.blade.php`, que e view/conteudo.
- **Regra de ouro 9 — migrations consolidadas.** Cada tabela nova tem UMA migration. Ajuste descoberto durante esta release edita a migration original; nao empilha migration nova.
- **Regra de ouro 11 — commits gitmoji.** Formato `<emoji> tipo(escopo): descricao em pt-BR`. Escopo desta release: `plantao`.
- **Regra de ouro 12 — commits atomicos.** Uma unidade coerente por commit. Nao quebrar a mesma classe em varios commits.
- **Regra de ouro 10 — teste nao entra no commit?** Nao se aplica aqui: os testes deste plano sao a suite permanente do modulo, nao scripts descartaveis de investigacao. Eles entram no commit.
- **Sem trailer `Co-Authored-By`** em nenhum commit.
- **Banco:** PostgreSQL (`config/database.php` default `pgsql`). Nao usar `$table->engine`, `charset` nem `collation` nas migrations — sao MySQL-ismos.
- **Todo arquivo PHP novo comeca com** `declare(strict_types=1);`.
- **Testes usam** `Illuminate\Foundation\Testing\DatabaseTransactions` (nao `RefreshDatabase`), `withoutMiddleware(VerifyCsrfToken::class)` no `setUp`, e criam permissao com `Permission::firstOrCreate([...])` seguido de `app(PermissionRegistrar::class)->forgetCachedPermissions()`.
- **AMBIENTE (corrigido na execucao).** O container `newsdc_frankenphp_local` citado
  originalmente NAO existe; o container real e `newsdc_dev_app` e ele monta o
  REPO PRINCIPAL (`NewSDC/SDC` -> `/var/www`), nao este worktree. Portanto o
  container NAO serve para rodar nada desta branch. Toda execucao acontece no
  HOST, com o PHP 8.3 do Laragon.
- **Atalho:** defina uma vez por sessao de shell:

  ```bash
  export PHP83="C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe"
  export ART="APP_CONFIG_CACHE=/nonexistent/config.php $PHP83 -d extension=pdo_pgsql -d extension=pgsql artisan"
  ```

  `APP_CONFIG_CACHE` apontando para caminho inexistente e obrigatorio: sem ele o
  Laravel tenta usar o config cacheado e quebra. `pdo_pgsql` e `pgsql` nao estao
  no php.ini do Laragon, precisam do `-d`.
- **Comando de teste:** `APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=<NomeDoTeste>`
- **Comando de lint:** `"$PHP83" -l <caminho relativo a SDC/>`
- **Comando de migration:** o mesmo `artisan` do host. O banco e o Postgres de dev
  COMPARTILHADO com o repo principal. As migrations desta release sao aditivas
  (4 tabelas novas + 8 colunas nullable em `plantoes`) e nao alteram nada que
  outro modulo use.
- **Octane:** NAO rodar `octane:reload` nem restart. O Octane roda no container,
  que aponta para o repo principal — reload ali nao tem efeito nenhum sobre esta
  branch, e restart custa ~3min por nada. Ignore todo passo do plano que peca
  reload ou restart.
- **`npm run build` e `npm run prebuild`** rodam no host, dentro de `SDC/`
  (`node_modules` esta ligado por junction ao repo principal).
- **Apos criar rota nova:** rodar `npm run prebuild` para regenerar `resources/js/ziggy.js`.
- **Enum nunca devolve classe CSS.** Tailwind nao escaneia `app/**/*.php`. Cor vive no `.vue`.

---

## File Structure

### Backend — criar

| Arquivo | Responsabilidade |
|---|---|
| `database/migrations/2026_08_26_100001_create_plantao_viaturas_table.php` | Tabela da frota |
| `database/migrations/2026_08_26_100002_create_plantao_viatura_movimentacoes_table.php` | Tabela de saida/retorno |
| `database/migrations/2026_08_26_100003_create_plantao_viatura_snapshots_table.php` | Tabela de estado congelado por turno |
| `database/migrations/2026_08_26_100004_add_passagem_servico_to_plantoes_table.php` | ALTER em `plantoes` |
| `app/Modules/Plantao/Enums/NivelCombustivel.php` | Nivel do tanque + percentual |
| `app/Modules/Plantao/Enums/StatusViatura.php` | Situacao da viatura |
| `app/Modules/Plantao/Enums/LocalizacaoViatura.php` | Onde a viatura esta |
| `app/Modules/Plantao/Enums/StatusMovimentacao.php` | Situacao da movimentacao |
| `app/Modules/Plantao/Models/Viatura.php` | Model da frota |
| `app/Modules/Plantao/Models/ViaturaMovimentacao.php` | Model da movimentacao |
| `app/Modules/Plantao/Models/ViaturaSnapshot.php` | Model do snapshot |
| `app/Modules/Plantao/Services/ViaturaService.php` | CRUD e listagem da frota |
| `app/Modules/Plantao/Services/MovimentacaoViaturaService.php` | Saida, retorno, e escrita do estado corrente |
| `app/Modules/Plantao/Services/PassagemServicoService.php` | Encerrar, aceitar, divergir, abrir turno |
| `app/Modules/Plantao/Services/RelatorioPassagemService.php` | Render do texto do relatorio |
| `app/Modules/Plantao/DTOs/ViaturaListDTO.php` | Payload da frota para o Inertia |
| `app/Modules/Plantao/DTOs/SnapshotDTO.php` | Payload do snapshot para o Inertia |
| `app/Modules/Plantao/Requests/StoreViaturaRequest.php` | Validacao de criacao de viatura |
| `app/Modules/Plantao/Requests/UpdateViaturaRequest.php` | Validacao de edicao de viatura |
| `app/Modules/Plantao/Requests/MovimentacaoSaidaRequest.php` | Validacao de saida |
| `app/Modules/Plantao/Requests/MovimentacaoRetornoRequest.php` | Validacao de retorno |
| `app/Modules/Plantao/Requests/EncerrarPassagemRequest.php` | Validacao do encerramento com snapshots |
| `app/Modules/Plantao/Requests/AceitarPassagemRequest.php` | Validacao do aceite |
| `app/Modules/Plantao/Controllers/ViaturaIndexController.php` | Tela da frota |
| `app/Modules/Plantao/Controllers/ViaturaStoreController.php` | Criar viatura |
| `app/Modules/Plantao/Controllers/ViaturaUpdateController.php` | Editar viatura |
| `app/Modules/Plantao/Controllers/ViaturaDestroyController.php` | Remover viatura |
| `app/Modules/Plantao/Controllers/MovimentacaoSaidaController.php` | Registrar saida |
| `app/Modules/Plantao/Controllers/MovimentacaoRetornoController.php` | Registrar retorno |
| `app/Modules/Plantao/Controllers/PassagemEncerrarController.php` | Encerrar turno |
| `app/Modules/Plantao/Controllers/PassagemAceitarController.php` | Aceitar ou divergir |
| `app/Modules/Plantao/Controllers/RelatorioPassagemController.php` | Devolver o texto |
| `config/plantao.php` | Rodape fixo do relatorio |
| `resources/views/plantao/passagem-servico.txt.blade.php` | Template do texto (unico arquivo com emoji) |
| `database/factories/Plantao/ViaturaFactory.php` | Factory da viatura |
| `database/factories/Plantao/PlantaoFactory.php` | Factory do turno |

### Backend — modificar

| Arquivo | Mudanca |
|---|---|
| `app/Modules/Plantao/Enums/PeriodoPlantao.php` | Labels corrigidos + metodo `labelCurto()` |
| `app/Modules/Plantao/Enums/StatusPlantao.php` | + `PENDENTE_ACEITE`, + `FINALIZADO_COM_DIVERGENCIA` |
| `app/Modules/Plantao/Models/Plantao.php` | Novos campos em `$fillable` e `$casts`, relacoes novas |
| `app/Modules/Plantao/Services/PlantaoService.php` | Contadores dos novos status |
| `app/Modules/Plantao/PlantaoServiceProvider.php` | Registro dos novos servicos |
| `routes/modules/plantao.php` | Novas rotas |
| `config/permissions.php` | Grupos `Viaturas` e `Passagem` sob `PLANTAO` |

### Frontend — criar

| Arquivo | Responsabilidade |
|---|---|
| `resources/js/Components/Atoms/Plantao/CombustivelGauge.vue` | Barra vertical de nivel |
| `resources/js/Components/Atoms/Plantao/HodometroBadge.vue` | Hodometro formatado |
| `resources/js/Components/Molecules/Plantao/ViaturaSnapshotCard.vue` | Bloco de viatura |
| `resources/js/Components/Molecules/Plantao/PassagemHandshakeBanner.vue` | Pendencia de aceite |
| `resources/js/Components/Organisms/Plantao/ViaturasGrid.vue` | Grade da frota |
| `resources/js/Components/Organisms/Plantao/ViaturasTable.vue` | Tabela da frota |
| `resources/js/Components/Organisms/Plantao/ViaturaFormModal.vue` | Cadastro e edicao |
| `resources/js/Components/Organisms/Plantao/MovimentacaoModal.vue` | Saida e retorno |
| `resources/js/Components/Organisms/Plantao/EncerrarTurnoModal.vue` | Conferencia no encerramento |
| `resources/js/Components/Organisms/Plantao/AceitarPassagemModal.vue` | Aceite ou divergencia |
| `resources/js/Components/Organisms/Plantao/RelatorioPassagemPanel.vue` | Preview + copiar |
| `resources/js/Templates/Plantao/ViaturasIndexTemplate.vue` | Template da tela da frota |
| `resources/js/Pages/Plantao/ViaturasIndex.vue` | Pagina Inertia da frota |
| `resources/js/Composables/useCopiarTexto.js` | Copia com fallback |

### Frontend — modificar

| Arquivo | Mudanca |
|---|---|
| `resources/js/Templates/Plantao/PlantaoIndexTemplate.vue` | Banner de pendencia + painel de relatorio + botao para a frota |

### Testes — criar

| Arquivo | Cobertura |
|---|---|
| `tests/Unit/Plantao/NivelCombustivelTest.php` | Percentual e labels |
| `tests/Unit/Plantao/RelatorioPassagemServiceTest.php` | Texto caractere a caractere |
| `tests/Feature/Plantao/ViaturaCrudTest.php` | CRUD e permissoes da frota |
| `tests/Feature/Plantao/MovimentacaoViaturaTest.php` | Saida, retorno e as guardas |
| `tests/Feature/Plantao/PassagemServicoTest.php` | Encerrar, aceitar, divergir, abrir |

---

# FASE 1 — Schema e Frota

Entrega: a frota CEDEC cadastrada, listavel e editavel na interface, com permissao propria. Ao fim da fase existe uma tela funcional em `/plantao/viaturas`.

---

### Task 1: Enums da frota e correcao do PeriodoPlantao

**Files:**
- Create: `app/Modules/Plantao/Enums/NivelCombustivel.php`
- Create: `app/Modules/Plantao/Enums/StatusViatura.php`
- Create: `app/Modules/Plantao/Enums/LocalizacaoViatura.php`
- Create: `app/Modules/Plantao/Enums/StatusMovimentacao.php`
- Modify: `app/Modules/Plantao/Enums/PeriodoPlantao.php`
- Modify: `app/Modules/Plantao/Enums/StatusPlantao.php`
- Test: `tests/Unit/Plantao/NivelCombustivelTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `NivelCombustivel` (string enum): casos `VAZIO`, `QUARTO_1`, `QUARTO_2`, `QUARTO_3`, `QUARTO_4`; metodos `label(): string`, `percentual(): int`, estatico `toSelectArray(): array`.
  - `StatusViatura` (string enum): casos `DISPONIVEL`, `EM_TRANSITO`, `MANUTENCAO`, `CEDIDA`, `INDISPONIVEL`; metodos `label(): string`, `emCondicoes(): bool`, `podeSair(): bool`, estatico `toSelectArray(): array`.
  - `LocalizacaoViatura` (string enum): casos `PREDIO_ALTEROSAS`, `OFICINA`, `CEDIDA`, `OUTRO`; metodos `label(): string`, estatico `toSelectArray(): array`.
  - `StatusMovimentacao` (string enum): casos `EM_TRANSITO`, `RETORNADA`; metodos `label(): string`, estatico `toSelectArray(): array`.
  - `PeriodoPlantao` ganha `labelCurto(): string`.
  - `StatusPlantao` ganha casos `PENDENTE_ACEITE` e `FINALIZADO_COM_DIVERGENCIA`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Unit/Plantao/NivelCombustivelTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusViatura;
use PHPUnit\Framework\TestCase;

class NivelCombustivelTest extends TestCase
{
    public function test_percentual_de_cada_nivel(): void
    {
        $this->assertSame(0, NivelCombustivel::VAZIO->percentual());
        $this->assertSame(25, NivelCombustivel::QUARTO_1->percentual());
        $this->assertSame(50, NivelCombustivel::QUARTO_2->percentual());
        $this->assertSame(75, NivelCombustivel::QUARTO_3->percentual());
        $this->assertSame(100, NivelCombustivel::QUARTO_4->percentual());
    }

    public function test_label_usa_a_notacao_de_quartos_do_relatorio(): void
    {
        $this->assertSame('0/4', NivelCombustivel::VAZIO->label());
        $this->assertSame('3/4', NivelCombustivel::QUARTO_3->label());
        $this->assertSame('4/4', NivelCombustivel::QUARTO_4->label());
    }

    public function test_periodo_tem_label_curto_para_o_relatorio(): void
    {
        $this->assertSame('06h às 16h', PeriodoPlantao::DIURNO->labelCurto());
        $this->assertSame('16h às 02h', PeriodoPlantao::NOTURNO->labelCurto());
    }

    public function test_periodo_label_completo_reflete_a_operacao_real(): void
    {
        $this->assertSame('06:00hs as 16:00hs', PeriodoPlantao::DIURNO->label());
        $this->assertSame('16:00hs as 02:00hs', PeriodoPlantao::NOTURNO->label());
    }

    public function test_status_viatura_define_quem_pode_sair(): void
    {
        $this->assertTrue(StatusViatura::DISPONIVEL->podeSair());
        $this->assertFalse(StatusViatura::EM_TRANSITO->podeSair());
        $this->assertFalse(StatusViatura::MANUTENCAO->podeSair());
        $this->assertFalse(StatusViatura::CEDIDA->podeSair());
        $this->assertFalse(StatusViatura::INDISPONIVEL->podeSair());
    }

    public function test_status_viatura_define_quem_esta_em_condicoes(): void
    {
        $this->assertTrue(StatusViatura::DISPONIVEL->emCondicoes());
        $this->assertTrue(StatusViatura::EM_TRANSITO->emCondicoes());
        $this->assertFalse(StatusViatura::MANUTENCAO->emCondicoes());
        $this->assertFalse(StatusViatura::CEDIDA->emCondicoes());
        $this->assertFalse(StatusViatura::INDISPONIVEL->emCondicoes());
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=NivelCombustivelTest
```

Esperado: FAIL com `Class "App\Modules\Plantao\Enums\NivelCombustivel" not found`.

- [ ] **Step 3: Criar `NivelCombustivel`**

`app/Modules/Plantao/Enums/NivelCombustivel.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum NivelCombustivel: string
{
    case VAZIO = 'VAZIO';
    case QUARTO_1 = 'QUARTO_1';
    case QUARTO_2 = 'QUARTO_2';
    case QUARTO_3 = 'QUARTO_3';
    case QUARTO_4 = 'QUARTO_4';

    public function label(): string
    {
        return match ($this) {
            self::VAZIO => '0/4',
            self::QUARTO_1 => '1/4',
            self::QUARTO_2 => '2/4',
            self::QUARTO_3 => '3/4',
            self::QUARTO_4 => '4/4',
        };
    }

    /**
     * Percentual do tanque. Consumido pelo gauge do frontend; o enum devolve
     * numero, nunca classe CSS (Tailwind nao escaneia app/**\/*.php).
     */
    public function percentual(): int
    {
        return match ($this) {
            self::VAZIO => 0,
            self::QUARTO_1 => 25,
            self::QUARTO_2 => 50,
            self::QUARTO_3 => 75,
            self::QUARTO_4 => 100,
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $nivel) => [
                'value' => $nivel->value,
                'label' => $nivel->label(),
            ],
            self::cases()
        );
    }
}
```

- [ ] **Step 4: Criar `StatusViatura`**

`app/Modules/Plantao/Enums/StatusViatura.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusViatura: string
{
    case DISPONIVEL = 'DISPONIVEL';
    case EM_TRANSITO = 'EM_TRANSITO';
    case MANUTENCAO = 'MANUTENCAO';
    case CEDIDA = 'CEDIDA';
    case INDISPONIVEL = 'INDISPONIVEL';

    public function label(): string
    {
        return match ($this) {
            self::DISPONIVEL => 'Disponivel',
            self::EM_TRANSITO => 'Em transito',
            self::MANUTENCAO => 'Manutencao',
            self::CEDIDA => 'Cedida',
            self::INDISPONIVEL => 'Indisponivel',
        };
    }

    /**
     * Entra na listagem de "viaturas em condicoes de atendimento" do relatorio.
     * Viatura em transito continua em condicoes: ela esta rodando, nao avariada.
     */
    public function emCondicoes(): bool
    {
        return match ($this) {
            self::DISPONIVEL, self::EM_TRANSITO => true,
            self::MANUTENCAO, self::CEDIDA, self::INDISPONIVEL => false,
        };
    }

    /**
     * Pode iniciar uma nova movimentacao. Em transito nao pode: ja esta fora.
     */
    public function podeSair(): bool
    {
        return $this === self::DISPONIVEL;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
```

- [ ] **Step 5: Criar `LocalizacaoViatura` e `StatusMovimentacao`**

`app/Modules/Plantao/Enums/LocalizacaoViatura.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum LocalizacaoViatura: string
{
    case PREDIO_ALTEROSAS = 'PREDIO_ALTEROSAS';
    case OFICINA = 'OFICINA';
    case CEDIDA = 'CEDIDA';
    case OUTRO = 'OUTRO';

    public function label(): string
    {
        return match ($this) {
            self::PREDIO_ALTEROSAS => 'Predio Alterosas',
            self::OFICINA => 'Oficina',
            self::CEDIDA => 'Cedida',
            self::OUTRO => 'Outro',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $local) => [
                'value' => $local->value,
                'label' => $local->label(),
            ],
            self::cases()
        );
    }
}
```

`app/Modules/Plantao/Enums/StatusMovimentacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusMovimentacao: string
{
    case EM_TRANSITO = 'EM_TRANSITO';
    case RETORNADA = 'RETORNADA';

    public function label(): string
    {
        return match ($this) {
            self::EM_TRANSITO => 'Em transito',
            self::RETORNADA => 'Retornada',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
```

- [ ] **Step 6: Corrigir `PeriodoPlantao`**

Substituir o corpo de `app/Modules/Plantao/Enums/PeriodoPlantao.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum PeriodoPlantao: string
{
    case DIURNO = 'DIURNO';
    case NOTURNO = 'NOTURNO';
    case EXTRAORDINARIO = 'EXTRAORDINARIO';

    /**
     * Horario real praticado pelo plantao CEDEC: dois turnos de 10h.
     * A lacuna 02h-06h e coberta por sobreaviso, nao por plantao presencial.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIURNO => '06:00hs as 16:00hs',
            self::NOTURNO => '16:00hs as 02:00hs',
            self::EXTRAORDINARIO => 'Extraordinario',
        };
    }

    /**
     * Forma abreviada usada no cabecalho do relatorio de passagem de servico.
     */
    public function labelCurto(): string
    {
        return match ($this) {
            self::DIURNO => '06h às 16h',
            self::NOTURNO => '16h às 02h',
            self::EXTRAORDINARIO => 'Extraordinario',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $periodo) => [
                'value' => $periodo->value,
                'label' => $periodo->label(),
            ],
            self::cases()
        );
    }
}
```

- [ ] **Step 7: Ampliar `StatusPlantao`**

Substituir o corpo de `app/Modules/Plantao/Enums/StatusPlantao.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusPlantao: string
{
    case ATIVO = 'ATIVO';
    case PENDENTE_ACEITE = 'PENDENTE_ACEITE';
    case FINALIZADO = 'FINALIZADO';
    case FINALIZADO_COM_DIVERGENCIA = 'FINALIZADO_COM_DIVERGENCIA';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::PENDENTE_ACEITE => 'Pendente de aceite',
            self::FINALIZADO => 'Finalizado',
            self::FINALIZADO_COM_DIVERGENCIA => 'Finalizado com divergencia',
        };
    }

    /**
     * O turno ja saiu do ar: nao aceita mais movimentacao nem novo snapshot.
     */
    public function encerrado(): bool
    {
        return $this === self::FINALIZADO
            || $this === self::FINALIZADO_COM_DIVERGENCIA;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
```

- [ ] **Step 8: Rodar o teste e confirmar que passa**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=NivelCombustivelTest
```

Esperado: PASS, 6 testes.

- [ ] **Step 9: Verificar que nada existente quebrou**

O `DIURNO`/`NOTURNO` mudou de label, e o `PlantaoListDTO` usa `->label()`. Rodar a suite do modulo e a busca global:

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=Plantao
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=GlobalSearchServiceTest
```

Esperado: PASS. Se algum teste asseverar o texto antigo `07:00hs as 19:00hs`, atualizar a assercao — o label antigo estava errado em relacao a operacao.

- [ ] **Step 10: Commit**

```bash
git add app/Modules/Plantao/Enums tests/Unit/Plantao/NivelCombustivelTest.php
git commit -m "✨ feat(plantao): enums de frota e correcao dos turnos para 06-16 e 16-02"
```

---

### Task 2: Migrations do schema

**Files:**
- Create: `database/migrations/2026_08_26_100001_create_plantao_viaturas_table.php`
- Create: `database/migrations/2026_08_26_100002_create_plantao_viatura_movimentacoes_table.php`
- Create: `database/migrations/2026_08_26_100003_create_plantao_viatura_snapshots_table.php`
- Create: `database/migrations/2026_08_26_100004_add_passagem_servico_to_plantoes_table.php`

**Interfaces:**
- Consumes: nada (as migrations nao referenciam os enums PHP; gravam string).
- Produces: tabelas `plantao_viaturas`, `plantao_viatura_movimentacoes`, `plantao_viatura_snapshots`, e as colunas novas em `plantoes` conforme a secao 3 do spec.

- [ ] **Step 1: Criar a migration da frota**

`database/migrations/2026_08_26_100001_create_plantao_viaturas_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viaturas', function (Blueprint $table) {
            $table->id();

            $table->string('prefixo', 20);
            $table->string('placa', 10)->unique();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 100);
            $table->string('localizacao', 40)->default('PREDIO_ALTEROSAS');
            $table->boolean('exclusiva_sobreaviso')->default(false);
            $table->string('status', 30)->default('DISPONIVEL');

            // Estado corrente. Derivado da ultima movimentacao e materializado
            // aqui porque a tela de indice lista a frota inteira com esses
            // valores. Escrito exclusivamente por MovimentacaoViaturaService.
            $table->unsignedInteger('hodometro_atual')->nullable();
            $table->string('nivel_combustivel', 20)->nullable();
            $table->foreignId('ultimo_condutor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ultimo_condutor_nome')->nullable();

            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('ativo');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viaturas');
    }
};
```

- [ ] **Step 2: Criar a migration das movimentacoes**

`database/migrations/2026_08_26_100002_create_plantao_viatura_movimentacoes_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viatura_movimentacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('viatura_id')
                ->constrained('plantao_viaturas')->cascadeOnDelete();
            $table->foreignId('plantao_id')->nullable()
                ->constrained('plantoes')->nullOnDelete();

            $table->foreignId('condutor_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('condutor_nome');

            $table->dateTime('saida_em');
            $table->unsignedInteger('saida_hodometro');
            $table->string('saida_combustivel', 20);
            $table->string('destino', 160)->nullable();
            $table->string('motivo', 160)->nullable();

            $table->dateTime('retorno_em')->nullable();
            $table->unsignedInteger('retorno_hodometro')->nullable();
            $table->string('retorno_combustivel', 20)->nullable();
            $table->text('alteracoes')->nullable();

            $table->string('status', 20)->default('EM_TRANSITO');

            $table->timestamps();
            $table->softDeletes();

            $table->index('plantao_id');
            $table->index('condutor_id');
            // Suporta a guarda "uma viatura nao pode ter duas saidas abertas".
            $table->index(['viatura_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viatura_movimentacoes');
    }
};
```

- [ ] **Step 3: Criar a migration dos snapshots**

`database/migrations/2026_08_26_100003_create_plantao_viatura_snapshots_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viatura_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plantao_id')
                ->constrained('plantoes')->cascadeOnDelete();
            $table->foreignId('viatura_id')
                ->constrained('plantao_viaturas')->restrictOnDelete();

            // Espelhos: o snapshot e registro historico. Se a placa mudar, o
            // relatorio de um turno passado continua fiel ao que foi declarado.
            $table->string('prefixo', 20);
            $table->string('placa', 10);

            $table->unsignedInteger('hodometro');
            $table->string('nivel_combustivel', 20);
            $table->text('alteracoes')->nullable();

            $table->foreignId('ultimo_condutor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ultimo_condutor_nome')->nullable();

            $table->string('anotacao', 160)->nullable();
            $table->boolean('em_condicoes')->default(true);

            $table->timestamps();

            $table->unique(['plantao_id', 'viatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viatura_snapshots');
    }
};
```

- [ ] **Step 4: Criar a migration do ALTER em `plantoes`**

`database/migrations/2026_08_26_100004_add_passagem_servico_to_plantoes_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->foreignId('plantonista_saida_id')->nullable()->after('plantonista_nome')
                ->constrained('users')->nullOnDelete();
            $table->string('plantonista_saida_nome')->nullable()->after('plantonista_saida_id');

            $table->string('localizacao', 60)->nullable()->after('periodo');
            $table->text('ocorrencias_destaque')->nullable()->after('observacoes');

            $table->dateTime('encerrado_em')->nullable()->after('ocorrencias_destaque');
            // Quem declarou o estado. Quando difere de plantonista_id, o
            // encerramento foi feito por terceiro (ver secao 4.3 do spec).
            $table->foreignId('encerrado_por_id')->nullable()->after('encerrado_em')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('aceito_em')->nullable()->after('encerrado_por_id');
            $table->foreignId('aceito_por_id')->nullable()->after('aceito_em')
                ->constrained('users')->nullOnDelete();
            $table->text('divergencia')->nullable()->after('aceito_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plantonista_saida_id');
            $table->dropConstrainedForeignId('encerrado_por_id');
            $table->dropConstrainedForeignId('aceito_por_id');
            $table->dropColumn([
                'plantonista_saida_nome',
                'localizacao',
                'ocorrencias_destaque',
                'encerrado_em',
                'aceito_em',
                'divergencia',
            ]);
        });
    }
};
```

- [ ] **Step 5: Rodar as migrations**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan migrate
```

Esperado: as 4 migrations executam sem erro.

- [ ] **Step 6: Verificar o schema no banco**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan db:table plantao_viaturas
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan db:table plantao_viatura_movimentacoes
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan db:table plantao_viatura_snapshots
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan db:table plantoes
```

Esperado: todas as colunas da secao 3 do spec presentes, `unique` em `plantao_viaturas.placa` e em `(plantao_id, viatura_id)` dos snapshots.

- [ ] **Step 7: Testar o rollback**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan migrate:rollback --step=4
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan migrate
```

Esperado: rollback e reaplicacao sem erro. Este passo prova que o `down()` esta correto antes de qualquer dado real existir.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_26_1000*
git commit -m "🗃️ db(plantao): schema de frota, movimentacoes, snapshots e passagem de servico"
```

---

### Task 3: Models e factories

**Files:**
- Create: `app/Modules/Plantao/Models/Viatura.php`
- Create: `app/Modules/Plantao/Models/ViaturaMovimentacao.php`
- Create: `app/Modules/Plantao/Models/ViaturaSnapshot.php`
- Create: `database/factories/Plantao/ViaturaFactory.php`
- Create: `database/factories/Plantao/PlantaoFactory.php`
- Modify: `app/Modules/Plantao/Models/Plantao.php`

**Interfaces:**
- Consumes: os enums da Task 1.
- Produces:
  - `Viatura` — `$fillable` completo, casts de enum, relacoes `movimentacoes(): HasMany`, `snapshots(): HasMany`, `ultimoCondutor(): BelongsTo`, `movimentacaoAberta(): HasOne`, scope `ativas()`.
  - `ViaturaMovimentacao` — relacoes `viatura(): BelongsTo`, `plantao(): BelongsTo`, `condutor(): BelongsTo`, scope `abertas()`.
  - `ViaturaSnapshot` — relacoes `plantao(): BelongsTo`, `viatura(): BelongsTo`, `ultimoCondutor(): BelongsTo`.
  - `Plantao` — novos campos em `$fillable`/`$casts`, relacoes `plantonistaSaida(): BelongsTo`, `aceitoPor(): BelongsTo`, `snapshots(): HasMany`, `movimentacoes(): HasMany`.
  - `ViaturaFactory::new()` com estado `->emManutencao()`.
  - `PlantaoFactory::new()` com estados `->pendenteAceite()` e `->finalizado()`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Plantao/ViaturaCrudTest.php` com apenas o primeiro teste, o de model:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ViaturaCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_cria_viatura_com_enums_convertidos(): void
    {
        $viatura = Viatura::factory()->create([
            'placa' => 'QMV-2241',
            'prefixo' => 'SW4',
            'nivel_combustivel' => NivelCombustivel::QUARTO_3,
        ]);

        $fresh = Viatura::findOrFail($viatura->id);

        $this->assertInstanceOf(NivelCombustivel::class, $fresh->nivel_combustivel);
        $this->assertSame(NivelCombustivel::QUARTO_3, $fresh->nivel_combustivel);
        $this->assertInstanceOf(StatusViatura::class, $fresh->status);
        $this->assertSame('QMV-2241', $fresh->placa);
    }

    public function test_scope_ativas_ignora_viatura_inativa(): void
    {
        Viatura::factory()->create(['ativo' => true]);
        Viatura::factory()->create(['ativo' => false]);

        $this->assertSame(1, Viatura::ativas()->count());
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: FAIL com `Class "App\Modules\Plantao\Models\Viatura" not found`.

- [ ] **Step 3: Criar o model `Viatura`**

`app/Modules/Plantao/Models/Viatura.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Viatura extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'plantao_viaturas';

    protected $fillable = [
        'prefixo',
        'placa',
        'marca',
        'modelo',
        'localizacao',
        'exclusiva_sobreaviso',
        'status',
        'hodometro_atual',
        'nivel_combustivel',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'localizacao' => LocalizacaoViatura::class,
        'status' => StatusViatura::class,
        'nivel_combustivel' => NivelCombustivel::class,
        'exclusiva_sobreaviso' => 'boolean',
        'ativo' => 'boolean',
        'hodometro_atual' => 'integer',
    ];

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ViaturaMovimentacao::class, 'viatura_id');
    }

    /**
     * A saida ainda nao retornada. Regra de negocio garante no maximo uma.
     */
    public function movimentacaoAberta(): HasOne
    {
        return $this->hasOne(ViaturaMovimentacao::class, 'viatura_id')
            ->where('status', StatusMovimentacao::EM_TRANSITO->value);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ViaturaSnapshot::class, 'viatura_id');
    }

    public function ultimoCondutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ultimo_condutor_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
```

- [ ] **Step 4: Criar o model `ViaturaMovimentacao`**

`app/Modules/Plantao/Models/ViaturaMovimentacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViaturaMovimentacao extends Model
{
    use SoftDeletes;

    protected $table = 'plantao_viatura_movimentacoes';

    protected $fillable = [
        'viatura_id',
        'plantao_id',
        'condutor_id',
        'condutor_nome',
        'saida_em',
        'saida_hodometro',
        'saida_combustivel',
        'destino',
        'motivo',
        'retorno_em',
        'retorno_hodometro',
        'retorno_combustivel',
        'alteracoes',
        'status',
    ];

    protected $casts = [
        'saida_em' => 'datetime',
        'retorno_em' => 'datetime',
        'saida_hodometro' => 'integer',
        'retorno_hodometro' => 'integer',
        'saida_combustivel' => NivelCombustivel::class,
        'retorno_combustivel' => NivelCombustivel::class,
        'status' => StatusMovimentacao::class,
    ];

    public function viatura(): BelongsTo
    {
        return $this->belongsTo(Viatura::class, 'viatura_id');
    }

    public function plantao(): BelongsTo
    {
        return $this->belongsTo(Plantao::class, 'plantao_id');
    }

    public function condutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'condutor_id');
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->where('status', StatusMovimentacao::EM_TRANSITO->value);
    }
}
```

- [ ] **Step 5: Criar o model `ViaturaSnapshot`**

`app/Modules/Plantao/Models/ViaturaSnapshot.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaturaSnapshot extends Model
{
    protected $table = 'plantao_viatura_snapshots';

    protected $fillable = [
        'plantao_id',
        'viatura_id',
        'prefixo',
        'placa',
        'hodometro',
        'nivel_combustivel',
        'alteracoes',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
        'anotacao',
        'em_condicoes',
    ];

    protected $casts = [
        'nivel_combustivel' => NivelCombustivel::class,
        'hodometro' => 'integer',
        'em_condicoes' => 'boolean',
    ];

    public function plantao(): BelongsTo
    {
        return $this->belongsTo(Plantao::class, 'plantao_id');
    }

    public function viatura(): BelongsTo
    {
        return $this->belongsTo(Viatura::class, 'viatura_id');
    }

    public function ultimoCondutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ultimo_condutor_id');
    }
}
```

- [ ] **Step 6: Ampliar o model `Plantao`**

Em `app/Modules/Plantao/Models/Plantao.php`, adicionar ao `$fillable` os campos
novos, adicionar casts de data, e adicionar as relacoes. Manter intacto tudo que
diz respeito a `Rastreavel` e `TrilhaDeAcoes`.

Adicionar em `$fillable`, depois de `'plantonista_nome'`:

```php
        'plantonista_saida_id',
        'plantonista_saida_nome',
        'localizacao',
        'ocorrencias_destaque',
        'encerrado_em',
        'encerrado_por_id',
        'aceito_em',
        'aceito_por_id',
        'divergencia',
```

Adicionar em `$casts`:

```php
        'encerrado_em' => 'datetime',
        'aceito_em' => 'datetime',
```

Adicionar as relacoes depois do metodo `plantonista()`:

```php
    public function plantonistaSaida(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_saida_id');
    }

    public function encerradoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'encerrado_por_id');
    }

    public function aceitoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'aceito_por_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ViaturaSnapshot::class, 'plantao_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ViaturaMovimentacao::class, 'plantao_id');
    }
```

Adicionar o import `use Illuminate\Database\Eloquent\Relations\HasMany;` e
`use Illuminate\Database\Eloquent\Factories\HasFactory;`, e o trait `HasFactory`
na classe.

Adicionar tambem ao array de `camposIgnoradosNaTrilha()`, junto de
`'plantonista_nome'`, o espelho novo — a trilha nao deve registrar mudanca em
coluna espelho:

```php
            'plantonista_saida_nome',
```

- [ ] **Step 7: Criar a factory da viatura**

`database/factories/Plantao/ViaturaFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Plantao;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use Illuminate\Database\Eloquent\Factories\Factory;

class ViaturaFactory extends Factory
{
    protected $model = Viatura::class;

    public function definition(): array
    {
        return [
            'prefixo' => 'SW4',
            'placa' => strtoupper($this->faker->unique()->bothify('QM?-####')),
            'marca' => 'Toyota',
            'modelo' => 'Hilux SW4',
            'localizacao' => LocalizacaoViatura::PREDIO_ALTEROSAS,
            'exclusiva_sobreaviso' => false,
            'status' => StatusViatura::DISPONIVEL,
            'hodometro_atual' => $this->faker->numberBetween(50_000, 150_000),
            'nivel_combustivel' => NivelCombustivel::QUARTO_4,
            'ativo' => true,
        ];
    }

    public function emManutencao(): static
    {
        return $this->state(fn() => ['status' => StatusViatura::MANUTENCAO]);
    }

    public function exclusivaSobreaviso(): static
    {
        return $this->state(fn() => ['exclusiva_sobreaviso' => true]);
    }
}
```

- [ ] **Step 8: Criar a factory do plantao**

`database/factories/Plantao/PlantaoFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Plantao;

use App\Models\User;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Models\Plantao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantaoFactory extends Factory
{
    protected $model = Plantao::class;

    public function definition(): array
    {
        return [
            'plantonista_id' => User::factory(),
            'plantonista_nome' => $this->faker->name(),
            'data' => now()->toDateString(),
            'periodo' => PeriodoPlantao::DIURNO,
            'status' => StatusPlantao::ATIVO,
            'localizacao' => 'Predio Alterosas',
        ];
    }

    public function pendenteAceite(): static
    {
        return $this->state(fn() => [
            'status' => StatusPlantao::PENDENTE_ACEITE,
            'encerrado_em' => now(),
        ]);
    }

    public function finalizado(): static
    {
        return $this->state(fn() => [
            'status' => StatusPlantao::FINALIZADO,
            'encerrado_em' => now()->subHour(),
            'aceito_em' => now(),
        ]);
    }
}
```

- [ ] **Step 9: Apontar as factories nos models**

Laravel resolve factory por convencao `Database\Factories\<Model>Factory`, que
nao encontra as nossas em subpasta. Declarar explicitamente em cada model:

Em `Viatura`:

```php
    protected static function newFactory(): \Database\Factories\Plantao\ViaturaFactory
    {
        return \Database\Factories\Plantao\ViaturaFactory::new();
    }
```

Em `Plantao`:

```php
    protected static function newFactory(): \Database\Factories\Plantao\PlantaoFactory
    {
        return \Database\Factories\Plantao\PlantaoFactory::new();
    }
```

- [ ] **Step 9b: Registrar TODOS os servicos novos no provider de uma vez**

Decisao do controlador durante a execucao: as Tasks 4, 6, 8 e 11 originalmente
adicionavam cada uma o seu `singleton()` neste arquivo, o que as impedia de rodar
em paralelo. Todos os registros passam para ca.

`Foo::class` resolve para string sem disparar autoload, portanto registrar uma
classe que ainda nao existe e inofensivo: o erro so apareceria se alguem tentasse
resolve-la, e cada uma sera resolvida pela primeira vez no teste da task que a
criar. Se um nome divergir, o erro aparece exatamente ali.

Em `app/Modules/Plantao/PlantaoServiceProvider.php`, o metodo `register()` fica:

```php
    public function register(): void
    {
        $this->app->singleton(PlantaoService::class);
        $this->app->singleton(ViaturaService::class);
        $this->app->singleton(MovimentacaoViaturaService::class);
        $this->app->singleton(PassagemServicoService::class);
        $this->app->singleton(RelatorioPassagemService::class);
    }
```

Com os imports correspondentes no topo do arquivo:

```php
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use App\Modules\Plantao\Services\PassagemServicoService;
use App\Modules\Plantao\Services\PlantaoService;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use App\Modules\Plantao\Services\ViaturaService;
```

As Tasks 4, 6, 8 e 11 NAO devem tocar este arquivo. Se o brief de alguma delas
pedir para adicionar um singleton, o passo ja esta feito aqui — ignore.

- [ ] **Step 10: Rodar o teste e confirmar que passa**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: PASS, 2 testes.

- [ ] **Step 11: Recarregar o Octane e commitar**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
git add app/Modules/Plantao/Models database/factories/Plantao tests/Feature/Plantao/ViaturaCrudTest.php
git commit -m "✨ feat(plantao): models e factories de viatura, movimentacao e snapshot"
```

---

### Task 4: Permissoes, ViaturaService e DTO

**Files:**
- Create: `app/Modules/Plantao/Services/ViaturaService.php`
- Create: `app/Modules/Plantao/DTOs/ViaturaListDTO.php`
- Modify: `config/permissions.php`
- Modify: `app/Modules/Plantao/PlantaoServiceProvider.php`
- Test: `tests/Feature/Plantao/ViaturaCrudTest.php` (ampliar)

**Interfaces:**
- Consumes: `Viatura` (Task 3), enums (Task 1).
- Produces:
  - `ViaturaService::list(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `ViaturaService::find(int $id): ?Viatura`
  - `ViaturaService::create(array $data): Viatura`
  - `ViaturaService::update(int $id, array $data): Viatura`
  - `ViaturaService::delete(int $id): bool`
  - `ViaturaService::getStatistics(): array` com chaves `total`, `disponiveis`, `em_transito`, `indisponiveis`
  - `ViaturaListDTO::fromModel(Viatura $v): self` e `ViaturaListDTO::collection(iterable $items): array`
  - Slugs `plantao.viaturas.view|create|edit|delete` e `plantao.passagem.encerrar|aceitar|relatorio`

- [ ] **Step 1: Escrever o teste que falha**

Adicionar a `tests/Feature/Plantao/ViaturaCrudTest.php`:

```php
    public function test_service_lista_filtrando_por_status(): void
    {
        Viatura::factory()->count(2)->create();
        Viatura::factory()->emManutencao()->create();

        $service = app(\App\Modules\Plantao\Services\ViaturaService::class);

        $todas = $service->list([], 50);
        $manutencao = $service->list(['status' => StatusViatura::MANUTENCAO->value], 50);

        $this->assertSame(3, $todas->total());
        $this->assertSame(1, $manutencao->total());
    }

    public function test_service_estatisticas_contam_por_status(): void
    {
        Viatura::factory()->count(2)->create();
        Viatura::factory()->emManutencao()->create();

        $stats = app(\App\Modules\Plantao\Services\ViaturaService::class)->getStatistics();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['disponiveis']);
        $this->assertSame(1, $stats['indisponiveis']);
    }

    public function test_dto_expoe_percentual_para_o_gauge(): void
    {
        $viatura = Viatura::factory()->create([
            'nivel_combustivel' => \App\Modules\Plantao\Enums\NivelCombustivel::QUARTO_3,
            'hodometro_atual' => 112799,
        ]);

        $dto = \App\Modules\Plantao\DTOs\ViaturaListDTO::fromModel($viatura);

        $this->assertSame(75, $dto->combustivel_percentual);
        $this->assertSame('3/4', $dto->combustivel_label);
        $this->assertSame(112799, $dto->hodometro);
    }
```

Adicionar o import `use App\Modules\Plantao\Enums\StatusViatura;` se ainda nao
estiver presente no arquivo.

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: FAIL com `Target class [App\Modules\Plantao\Services\ViaturaService] does not exist`.

- [ ] **Step 3: Criar o `ViaturaService`**

`app/Modules/Plantao/Services/ViaturaService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ViaturaService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Viatura::query()
            ->with('ultimoCondutor:id,name')
            ->orderBy('prefixo')
            ->orderBy('placa');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['localizacao'])) {
            $query->where('localizacao', $filters['localizacao']);
        }

        if (array_key_exists('ativo', $filters) && $filters['ativo'] !== null && $filters['ativo'] !== '') {
            $query->where('ativo', filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'ilike', "%{$search}%")
                    ->orWhere('prefixo', 'ilike', "%{$search}%")
                    ->orWhere('modelo', 'ilike', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Viatura
    {
        return Viatura::with('ultimoCondutor:id,name')->find($id);
    }

    public function create(array $data): Viatura
    {
        return Viatura::create($data);
    }

    public function update(int $id, array $data): Viatura
    {
        $viatura = Viatura::findOrFail($id);
        $viatura->update($data);

        return $viatura->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) Viatura::findOrFail($id)->delete();
    }

    /**
     * @return array{total:int,disponiveis:int,em_transito:int,indisponiveis:int}
     */
    public function getStatistics(): array
    {
        $porStatus = Viatura::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $conta = static fn(StatusViatura ...$status): int => array_sum(
            array_map(fn(StatusViatura $s) => (int) ($porStatus[$s->value] ?? 0), $status)
        );

        return [
            'total' => array_sum($porStatus),
            'disponiveis' => $conta(StatusViatura::DISPONIVEL),
            'em_transito' => $conta(StatusViatura::EM_TRANSITO),
            'indisponiveis' => $conta(
                StatusViatura::MANUTENCAO,
                StatusViatura::CEDIDA,
                StatusViatura::INDISPONIVEL
            ),
        ];
    }
}
```

**Nota sobre `ilike`.** O banco e PostgreSQL; `ilike` da busca insensivel a caixa
sem `lower()` dos dois lados. Se o modulo algum dia rodar em MySQL, trocar por
`whereRaw('lower(placa) like ?', [...])`.

- [ ] **Step 4: Criar o `ViaturaListDTO`**

`app/Modules/Plantao/DTOs/ViaturaListDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\Viatura;

class ViaturaListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly string $modelo,
        public readonly ?string $marca,
        public readonly string $localizacao,
        public readonly string $localizacao_valor,
        public readonly bool $exclusiva_sobreaviso,
        public readonly string $status,
        public readonly string $status_valor,
        public readonly ?int $hodometro,
        public readonly ?string $combustivel_label,
        public readonly int $combustivel_percentual,
        public readonly ?string $ultimo_condutor_nome,
        public readonly bool $ativo,
        public readonly ?string $observacoes,
    ) {
    }

    public static function fromModel(Viatura $viatura): self
    {
        return new self(
            id: $viatura->id,
            prefixo: $viatura->prefixo,
            placa: $viatura->placa,
            modelo: $viatura->modelo,
            marca: $viatura->marca,
            localizacao: $viatura->localizacao?->label() ?? '',
            localizacao_valor: $viatura->localizacao?->value ?? '',
            exclusiva_sobreaviso: (bool) $viatura->exclusiva_sobreaviso,
            status: $viatura->status?->label() ?? '',
            status_valor: $viatura->status?->value ?? '',
            hodometro: $viatura->hodometro_atual,
            combustivel_label: $viatura->nivel_combustivel?->label(),
            combustivel_percentual: $viatura->nivel_combustivel?->percentual() ?? 0,
            ultimo_condutor_nome: $viatura->ultimo_condutor_nome,
            ativo: (bool) $viatura->ativo,
            observacoes: $viatura->observacoes,
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn(Viatura $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
```

**Nota.** O DTO expoe `status_valor` e `localizacao_valor` alem do label porque o
frontend precisa do valor cru para mapear cor. O mapa de cor vive no `.vue`.

- [ ] **Step 5: Registrar os slugs de permissao**

Em `config/permissions.php`, substituir o bloco `'PLANTAO'` por:

```php
        'PLANTAO' => [
            'Turnos' => [
                'view' => 'plantao.turnos.view',
                'create' => 'plantao.turnos.create',
                'edit' => 'plantao.turnos.edit',
                'delete' => 'plantao.turnos.delete',
                'export' => 'plantao.turnos.export',
            ],
            'Viaturas' => [
                'view' => 'plantao.viaturas.view',
                'create' => 'plantao.viaturas.create',
                'edit' => 'plantao.viaturas.edit',
                'delete' => 'plantao.viaturas.delete',
            ],
            'Passagem' => [
                'encerrar' => 'plantao.passagem.encerrar',
                'aceitar' => 'plantao.passagem.aceitar',
                'relatorio' => 'plantao.passagem.relatorio',
            ],
        ],
```

- [ ] **Step 6: Atribuir os slugs aos perfis**

Localizar cada bloco de papel que hoje lista `plantao.turnos.*` (linhas ~641,
~812, ~921, ~982, ~1011 no arquivo atual) e aplicar a regra:

- Onde houver `plantao.turnos.create` e `plantao.turnos.edit`, adicionar
  `plantao.viaturas.view`, `plantao.viaturas.create`, `plantao.viaturas.edit`,
  `plantao.passagem.encerrar`, `plantao.passagem.aceitar`,
  `plantao.passagem.relatorio`. Este e o perfil que opera o plantao.
- Onde houver apenas `plantao.turnos.view`, adicionar somente
  `plantao.viaturas.view` e `plantao.passagem.relatorio`. Perfil de consulta ve a
  frota e o relatorio, mas nao opera.
- O curinga `plantao.*` (linha ~522) ja cobre os slugs novos; nao mexer.

- [ ] **Step 7: Registro no provider — JA FEITO na Task 3**

Nao toque em `PlantaoServiceProvider.php`. O `singleton(ViaturaService::class)` ja
foi registrado na Task 3, por decisao do controlador, para permitir que esta task
rode em paralelo com as Tasks 6 e 11. Passo sem acao.

- [ ] **Step 8: Limpar cache de config e rodar o teste**

Config mudou, entao `config:clear` e obrigatorio:

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan config:clear
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: PASS, 5 testes.

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Plantao/Services/ViaturaService.php app/Modules/Plantao/DTOs/ViaturaListDTO.php app/Modules/Plantao/PlantaoServiceProvider.php config/permissions.php tests/Feature/Plantao/ViaturaCrudTest.php
git commit -m "✨ feat(plantao): servico, DTO e permissoes da frota de viaturas"
```

---

### Task 5: CRUD da frota — rotas, requests, controllers e tela

**Files:**
- Create: `app/Modules/Plantao/Requests/StoreViaturaRequest.php`
- Create: `app/Modules/Plantao/Requests/UpdateViaturaRequest.php`
- Create: `app/Modules/Plantao/Controllers/ViaturaIndexController.php`
- Create: `app/Modules/Plantao/Controllers/ViaturaStoreController.php`
- Create: `app/Modules/Plantao/Controllers/ViaturaUpdateController.php`
- Create: `app/Modules/Plantao/Controllers/ViaturaDestroyController.php`
- Create: `resources/js/Components/Atoms/Plantao/CombustivelGauge.vue`
- Create: `resources/js/Components/Atoms/Plantao/HodometroBadge.vue`
- Create: `resources/js/Components/Organisms/Plantao/ViaturasTable.vue`
- Create: `resources/js/Components/Organisms/Plantao/ViaturasGrid.vue`
- Create: `resources/js/Components/Organisms/Plantao/ViaturaFormModal.vue`
- Create: `resources/js/Templates/Plantao/ViaturasIndexTemplate.vue`
- Create: `resources/js/Pages/Plantao/ViaturasIndex.vue`
- Modify: `routes/modules/plantao.php`
- Test: `tests/Feature/Plantao/ViaturaCrudTest.php` (ampliar)

**Interfaces:**
- Consumes: `ViaturaService`, `ViaturaListDTO` (Task 4).
- Produces: rotas nomeadas `plantao.viaturas.index`, `plantao.viaturas.store`, `plantao.viaturas.update`, `plantao.viaturas.destroy`. Prop Inertia da pagina: `{ viaturas: {data, pagination}, statistics, filters, filterOptions, canCreate, canEdit, canDelete }`.

- [ ] **Step 1: Escrever o teste que falha**

Adicionar a `tests/Feature/Plantao/ViaturaCrudTest.php`:

```php
    private const PERMS = [
        'plantao.viaturas.view',
        'plantao.viaturas.create',
        'plantao.viaturas.edit',
        'plantao.viaturas.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function actingAsOperador(array $perms = self::PERMS): static
    {
        foreach ($perms as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo($perms);

        return $this->actingAs($user);
    }

    public function test_index_renderiza_a_pagina_da_frota(): void
    {
        Viatura::factory()->create(['placa' => 'QMV-2241']);

        $this->actingAsOperador()
            ->get(route('plantao.viaturas.index'))
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Plantao/ViaturasIndex')
                ->has('viaturas.data', 1)
                ->where('viaturas.data.0.placa', 'QMV-2241')
                ->has('statistics'));
    }

    public function test_index_exige_permissao(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('plantao.viaturas.index'))
            ->assertForbidden();
    }

    public function test_store_cria_viatura(): void
    {
        $this->actingAsOperador()
            ->post(route('plantao.viaturas.store'), [
                'prefixo' => 'SW4',
                'placa' => 'QMV-2245',
                'modelo' => 'Hilux SW4',
                'marca' => 'Toyota',
                'localizacao' => 'PREDIO_ALTEROSAS',
                'status' => 'DISPONIVEL',
                'nivel_combustivel' => 'QUARTO_4',
                'hodometro_atual' => 103798,
                'exclusiva_sobreaviso' => false,
                'ativo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plantao_viaturas', [
            'placa' => 'QMV-2245',
            'hodometro_atual' => 103798,
        ]);
    }

    public function test_store_rejeita_placa_duplicada(): void
    {
        Viatura::factory()->create(['placa' => 'QMV-2241']);

        $this->actingAsOperador()
            ->post(route('plantao.viaturas.store'), [
                'prefixo' => 'SW4',
                'placa' => 'QMV-2241',
                'modelo' => 'Hilux SW4',
                'localizacao' => 'PREDIO_ALTEROSAS',
                'status' => 'DISPONIVEL',
            ])
            ->assertSessionHasErrors('placa');
    }

    public function test_destroy_faz_soft_delete(): void
    {
        $viatura = Viatura::factory()->create();

        $this->actingAsOperador()
            ->delete(route('plantao.viaturas.destroy', $viatura))
            ->assertRedirect();

        $this->assertSoftDeleted('plantao_viaturas', ['id' => $viatura->id]);
    }
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: FAIL com `Route [plantao.viaturas.index] not defined`.

- [ ] **Step 3: Criar os FormRequests**

`app/Modules/Plantao/Requests/StoreViaturaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreViaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefixo' => ['required', 'string', 'max:20'],
            'placa' => ['required', 'string', 'max:10', 'unique:plantao_viaturas,placa'],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:100'],
            'localizacao' => ['required', Rule::enum(LocalizacaoViatura::class)],
            'status' => ['required', Rule::enum(StatusViatura::class)],
            'nivel_combustivel' => ['nullable', Rule::enum(NivelCombustivel::class)],
            'hodometro_atual' => ['nullable', 'integer', 'min:0'],
            'exclusiva_sobreaviso' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => strtoupper(trim((string) $this->input('placa')))]);
        }
    }
}
```

`app/Modules/Plantao/Requests/UpdateViaturaRequest.php`: identico ao Store,
exceto a regra de `placa`, que precisa ignorar o proprio registro:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateViaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $viaturaId = $this->route('viatura')?->id;

        return [
            'prefixo' => ['required', 'string', 'max:20'],
            'placa' => [
                'required', 'string', 'max:10',
                Rule::unique('plantao_viaturas', 'placa')->ignore($viaturaId),
            ],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:100'],
            'localizacao' => ['required', Rule::enum(LocalizacaoViatura::class)],
            'status' => ['required', Rule::enum(StatusViatura::class)],
            'nivel_combustivel' => ['nullable', Rule::enum(NivelCombustivel::class)],
            'hodometro_atual' => ['nullable', 'integer', 'min:0'],
            'exclusiva_sobreaviso' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => strtoupper(trim((string) $this->input('placa')))]);
        }
    }
}
```

- [ ] **Step 4: Criar os controllers**

`app/Modules/Plantao/Controllers/ViaturaIndexController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\ViaturaListDTO;
use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ViaturaIndexController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'localizacao', 'ativo', 'search']);

        $viaturas = $this->viaturaService->list($filters, 15);
        $user = $request->user();

        return Inertia::render('Plantao/ViaturasIndex', [
            'viaturas' => [
                'data' => ViaturaListDTO::collection($viaturas->items()),
                'pagination' => [
                    'current_page' => $viaturas->currentPage(),
                    'per_page' => $viaturas->perPage(),
                    'total' => $viaturas->total(),
                    'last_page' => $viaturas->lastPage(),
                    'from' => $viaturas->firstItem(),
                    'to' => $viaturas->lastItem(),
                ],
            ],
            'statistics' => $this->viaturaService->getStatistics(),
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusViatura::toSelectArray(),
                'localizacoes' => LocalizacaoViatura::toSelectArray(),
                'niveis' => NivelCombustivel::toSelectArray(),
            ],
            'canCreate' => (bool) $user?->can('plantao.viaturas.create'),
            'canEdit' => (bool) $user?->can('plantao.viaturas.edit'),
            'canDelete' => (bool) $user?->can('plantao.viaturas.delete'),
        ]);
    }
}
```

`app/Modules/Plantao/Controllers/ViaturaStoreController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Requests\StoreViaturaRequest;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaStoreController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(StoreViaturaRequest $request): RedirectResponse
    {
        $this->viaturaService->create($request->validated());

        return back()->with('success', 'Viatura cadastrada.');
    }
}
```

`app/Modules/Plantao/Controllers/ViaturaUpdateController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Requests\UpdateViaturaRequest;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaUpdateController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(UpdateViaturaRequest $request, Viatura $viatura): RedirectResponse
    {
        $this->viaturaService->update($viatura->id, $request->validated());

        return back()->with('success', 'Viatura atualizada.');
    }
}
```

`app/Modules/Plantao/Controllers/ViaturaDestroyController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaDestroyController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(Viatura $viatura): RedirectResponse
    {
        $this->viaturaService->delete($viatura->id);

        return back()->with('success', 'Viatura removida.');
    }
}
```

- [ ] **Step 5: Registrar as rotas**

Substituir `routes/modules/plantao.php` por:

```php
<?php

use App\Modules\Plantao\Controllers\NoticiasIndexController;
use App\Modules\Plantao\Controllers\PlantaoExportController;
use App\Modules\Plantao\Controllers\PlantaoIndexController;
use App\Modules\Plantao\Controllers\ViaturaDestroyController;
use App\Modules\Plantao\Controllers\ViaturaIndexController;
use App\Modules\Plantao\Controllers\ViaturaStoreController;
use App\Modules\Plantao\Controllers\ViaturaUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('plantao')->name('plantao.')->group(function () {

    // Rotas estaticas primeiro: /viaturas nao pode ser capturada como {plantao}.
    Route::get('/export', PlantaoExportController::class)
        ->name('export')
        ->middleware('can:plantao.turnos.export');

    Route::get('/noticias', NoticiasIndexController::class)
        ->name('noticias')
        ->middleware('can:plantao.turnos.view');

    Route::prefix('viaturas')->name('viaturas.')->group(function () {
        Route::get('/', ViaturaIndexController::class)
            ->name('index')
            ->middleware('can:plantao.viaturas.view');

        Route::post('/', ViaturaStoreController::class)
            ->name('store')
            ->middleware('can:plantao.viaturas.create');

        Route::put('/{viatura}', ViaturaUpdateController::class)
            ->name('update')
            ->middleware('can:plantao.viaturas.edit');

        Route::delete('/{viatura}', ViaturaDestroyController::class)
            ->name('destroy')
            ->middleware('can:plantao.viaturas.delete');
    });

    Route::get('/', PlantaoIndexController::class)
        ->name('index')
        ->middleware('can:plantao.turnos.view');
});
```

- [ ] **Step 6: Regenerar o ziggy e rodar o teste do backend**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan route:list --name=plantao.viaturas
npm run prebuild
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=ViaturaCrudTest
```

Esperado: 4 rotas listadas; PASS em 10 testes.

- [ ] **Step 7: Criar o atom `CombustivelGauge`**

`resources/js/Components/Atoms/Plantao/CombustivelGauge.vue`. As classes de cor
sao literais neste arquivo — Tailwind nao escaneia PHP, e o backend manda apenas
numero e texto.

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
  percentual: {
    type: Number,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  altura: {
    type: String,
    default: 'h-32',
  },
});

// Faixas de cor: critico ate 25, atencao ate 50, saudavel acima.
const corPreenchimento = computed(() => {
  if (props.percentual <= 25) return 'bg-red-500 dark:bg-red-600';
  if (props.percentual <= 50) return 'bg-amber-500 dark:bg-amber-600';
  return 'bg-emerald-500 dark:bg-emerald-600';
});

const corTexto = computed(() => {
  if (props.percentual <= 25) return 'text-red-700 dark:text-red-300';
  if (props.percentual <= 50) return 'text-amber-700 dark:text-amber-300';
  return 'text-emerald-700 dark:text-emerald-300';
});

const semCombustivel = computed(() => props.percentual === 0);
</script>

<template>
  <div class="flex flex-col items-center gap-1.5">
    <div
      class="relative w-14 overflow-hidden rounded-md bg-gray-200 dark:bg-gray-700"
      :class="altura"
      role="meter"
      :aria-valuenow="percentual"
      aria-valuemin="0"
      aria-valuemax="100"
      :aria-label="`Nivel de combustivel ${label}`"
    >
      <div
        class="absolute bottom-0 left-0 w-full transition-all duration-300"
        :class="corPreenchimento"
        :style="{ height: `${percentual}%` }"
      />
      <span
        class="absolute inset-x-0 top-1 text-center text-xs font-bold"
        :class="corTexto"
      >
        {{ label }}
      </span>
    </div>

    <span
      v-if="semCombustivel"
      class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-300"
    >
      Sem combustivel
    </span>
  </div>
</template>
```

- [ ] **Step 8: Criar o atom `HodometroBadge`**

`resources/js/Components/Atoms/Plantao/HodometroBadge.vue`:

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
  valor: {
    type: Number,
    default: null,
  },
});

const formatado = computed(() =>
  props.valor === null || props.valor === undefined
    ? '--'
    : new Intl.NumberFormat('pt-BR').format(props.valor)
);
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
  >
    {{ formatado }} km
  </span>
</template>
```

- [ ] **Step 9: Criar `ViaturasTable`, `ViaturasGrid` e `ViaturaFormModal`**

Seguir exatamente o padrao dos equivalentes de Plantao que ja existem —
`resources/js/Components/Organisms/Plantao/PlantaoTable.vue` e
`PlantaoGrid.vue` — copiando a estrutura de props, slots e classes, e trocando
as colunas por: prefixo, placa, modelo, localizacao, status, `CombustivelGauge`,
`HodometroBadge`, ultimo condutor, acoes.

Regras a respeitar nos tres arquivos:

- Acoes usam `Atoms/Button/ActionButton.vue`, condicionadas a `canEdit` e `canDelete`.
- Estado vazio usa `Molecules/ListEmptyState.vue` com `title` e `helper`.
- O mapa de cor de status vive no `.vue`, indexado por `status_valor`:

```js
const CORES_STATUS = {
  DISPONIVEL: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
  EM_TRANSITO: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  MANUTENCAO: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  CEDIDA: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
  INDISPONIVEL: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};
```

- No `ViaturaFormModal`, os campos vem de `Molecules/Form/*`. O campo de
  hodometro precisa de `inputmode` **declarado como prop e repassado ao input**
  no `FormField` — atributo solto cai na div raiz e nao chega ao input. Se o
  `FormField` atual nao aceitar `inputmode`, adicionar a prop nele.
- O select de status e localizacao recebe `filterOptions.status` e
  `filterOptions.localizacoes`, que ja vem no formato `{value, label}` do
  `toSelectArray()`. Nao remapear.

- [ ] **Step 10: Criar o template e a pagina**

`resources/js/Templates/Plantao/ViaturasIndexTemplate.vue` — espelhar
`PlantaoIndexTemplate.vue`:

- `PageHeader` com `title="Frota de Viaturas"`,
  `description="Cadastro e situacao das viaturas do plantao"`,
  `variant="gradient"`, `:icon-image="moduleIcon('plantao')"`.
- `ViewModeToggle` para alternar grade e tabela.
- `StatCardsGrid` com quatro `StatCard`: Total, Disponiveis, Em transito,
  Indisponiveis. Cada card e filtro rapido e emite `filter` com o status
  correspondente; o card Total limpa o filtro.
- `CollapsibleSection` com `namespace="plantao"` envolvendo os filtros.
- `Pagination` com a prop achatada (`current_page`, `last_page`, `per_page`,
  `total`, `from`, `to`).

`resources/js/Pages/Plantao/ViaturasIndex.vue` — pagina fina que recebe as props
do Inertia e delega ao template, espelhando `Pages/Plantao/PlantaoIndex.vue`.
O handler de filtro usa reload parcial para nao recalcular estatistica:

```js
const handleFilter = (filtros) => {
  router.get(route('plantao.viaturas.index'), filtros, {
    preserveState: true,
    preserveScroll: true,
    only: ['viaturas', 'filters'],
  });
};
```

- [ ] **Step 11: Buildar o frontend e verificar a tela**

```bash
npm run build
```

Esperado: build sem erro. Abrir `/plantao/viaturas` no navegador, confirmar que
o header, os cards, o gauge e a tabela renderizam, e que cadastrar uma viatura
funciona.

- [ ] **Step 12: Commit**

```bash
git add app/Modules/Plantao/Requests app/Modules/Plantao/Controllers routes/modules/plantao.php resources/js/Components/Atoms/Plantao resources/js/Components/Organisms/Plantao resources/js/Templates/Plantao/ViaturasIndexTemplate.vue resources/js/Pages/Plantao/ViaturasIndex.vue resources/js/ziggy.js tests/Feature/Plantao/ViaturaCrudTest.php
git commit -m "✨ feat(plantao): CRUD da frota de viaturas com tela e permissoes"
```

**Marco da Fase 1.** Existe uma tela funcional de frota em `/plantao/viaturas`.
Este e um ponto de parada seguro: o sistema esta consistente e entrega valor
mesmo se a Fase 2 nao for feita agora.

---

# FASE 2 — Movimentacao de viatura

Entrega: registro de saida e retorno por condutor, com o estado corrente da viatura mantido automaticamente. Ao fim da fase o hodometro e o combustivel da frota deixam de ser digitados a mao.

---

### Task 6: MovimentacaoViaturaService e as guardas de negocio

**Files:**
- Create: `app/Modules/Plantao/Services/MovimentacaoViaturaService.php`
- Modify: `app/Modules/Plantao/PlantaoServiceProvider.php`
- Test: `tests/Feature/Plantao/MovimentacaoViaturaTest.php`

**Interfaces:**
- Consumes: `Viatura`, `ViaturaMovimentacao` (Task 3); `StatusViatura`, `NivelCombustivel`, `StatusMovimentacao` (Task 1).
- Produces:
  - `MovimentacaoViaturaService::registrarSaida(int $viaturaId, array $dados): ViaturaMovimentacao`
    - `$dados`: `condutor_id` (int), `saida_hodometro` (int), `saida_combustivel` (string), `destino` (?string), `motivo` (?string), `plantao_id` (?int), `saida_em` (?string).
  - `MovimentacaoViaturaService::registrarRetorno(int $movimentacaoId, array $dados): ViaturaMovimentacao`
    - `$dados`: `retorno_hodometro` (int), `retorno_combustivel` (string), `alteracoes` (?string), `retorno_em` (?string).
  - Lanca `App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException` (extends `RuntimeException`) em toda violacao de guarda.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Plantao/MovimentacaoViaturaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Plantao;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MovimentacaoViaturaTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): MovimentacaoViaturaService
    {
        return app(MovimentacaoViaturaService::class);
    }

    public function test_saida_marca_viatura_em_transito(): void
    {
        $viatura = Viatura::factory()->create([
            'hodometro_atual' => 112_600,
            'status' => StatusViatura::DISPONIVEL,
        ]);
        $condutor = User::factory()->create(['name' => 'Sgt Egidio']);

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
            'destino' => 'Sete Lagoas',
        ]);

        $this->assertSame(StatusMovimentacao::EM_TRANSITO, $mov->status);
        $this->assertSame('Sgt Egidio', $mov->condutor_nome);

        $viatura->refresh();
        $this->assertSame(StatusViatura::EM_TRANSITO, $viatura->status);
        $this->assertSame(112_640, $viatura->hodometro_atual);
        $this->assertSame('Sgt Egidio', $viatura->ultimo_condutor_nome);
        $this->assertSame($condutor->id, $viatura->ultimo_condutor_id);
    }

    public function test_retorno_atualiza_estado_corrente_da_viatura(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_600]);
        $condutor = User::factory()->create(['name' => 'Sgt Egidio']);

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $mov = $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 112_799,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
            'alteracoes' => null,
        ]);

        $this->assertSame(StatusMovimentacao::RETORNADA, $mov->status);

        $viatura->refresh();
        $this->assertSame(StatusViatura::DISPONIVEL, $viatura->status);
        $this->assertSame(112_799, $viatura->hodometro_atual);
        $this->assertSame(NivelCombustivel::QUARTO_3, $viatura->nivel_combustivel);
    }

    public function test_retorno_com_hodometro_menor_que_a_saida_e_rejeitado(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_600]);
        $condutor = User::factory()->create();

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 112_600,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
        ]);
    }

    public function test_segunda_saida_sem_retorno_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_010,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_saida_de_viatura_em_manutencao_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->emManutencao()->create(['hodometro_atual' => 90_000]);
        $condutor = User::factory()->create();

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 90_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_saida_com_hodometro_menor_que_o_corrente_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_799]);
        $condutor = User::factory()->create();

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_retorno_em_movimentacao_ja_fechada_e_rejeitado(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 100_100,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 100_200,
            'retorno_combustivel' => NivelCombustivel::QUARTO_2->value,
        ]);
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=MovimentacaoViaturaTest
```

Esperado: FAIL com `Class "App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException" not found`.

- [ ] **Step 3: Criar a excecao de dominio**

`app/Modules/Plantao/Exceptions/MovimentacaoInvalidaException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Exceptions;

use RuntimeException;

class MovimentacaoInvalidaException extends RuntimeException
{
}
```

- [ ] **Step 4: Criar o `MovimentacaoViaturaService`**

`app/Modules/Plantao/Services/MovimentacaoViaturaService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Unico ponto do sistema autorizado a escrever o estado corrente da viatura
 * (hodometro_atual, nivel_combustivel, ultimo_condutor_id, ultimo_condutor_nome
 * e status). Nenhum controller, request ou outro service toca esses campos.
 */
class MovimentacaoViaturaService extends BaseService
{
    public function registrarSaida(int $viaturaId, array $dados): ViaturaMovimentacao
    {
        return DB::transaction(function () use ($viaturaId, $dados): ViaturaMovimentacao {
            // lockForUpdate evita duas saidas simultaneas passando pela guarda.
            $viatura = Viatura::query()->lockForUpdate()->findOrFail($viaturaId);

            if (!$viatura->status->podeSair()) {
                throw new MovimentacaoInvalidaException(
                    "Viatura {$viatura->placa} esta em {$viatura->status->label()} e nao pode sair."
                );
            }

            if ($viatura->movimentacoes()->abertas()->exists()) {
                throw new MovimentacaoInvalidaException(
                    "Viatura {$viatura->placa} ja possui uma saida em aberto."
                );
            }

            $hodometroSaida = (int) $dados['saida_hodometro'];

            if ($viatura->hodometro_atual !== null && $hodometroSaida < $viatura->hodometro_atual) {
                throw new MovimentacaoInvalidaException(
                    "Hodometro de saida ({$hodometroSaida}) e menor que o registrado na viatura ({$viatura->hodometro_atual})."
                );
            }

            $condutor = User::findOrFail((int) $dados['condutor_id']);

            $movimentacao = ViaturaMovimentacao::create([
                'viatura_id' => $viatura->id,
                'plantao_id' => $dados['plantao_id'] ?? null,
                'condutor_id' => $condutor->id,
                'condutor_nome' => $condutor->name,
                'saida_em' => $dados['saida_em'] ?? now(),
                'saida_hodometro' => $hodometroSaida,
                'saida_combustivel' => $dados['saida_combustivel'],
                'destino' => $dados['destino'] ?? null,
                'motivo' => $dados['motivo'] ?? null,
                'status' => StatusMovimentacao::EM_TRANSITO,
            ]);

            $this->sincronizarEstado($viatura, [
                'status' => StatusViatura::EM_TRANSITO,
                'hodometro_atual' => $hodometroSaida,
                'nivel_combustivel' => NivelCombustivel::from($dados['saida_combustivel']),
                'ultimo_condutor_id' => $condutor->id,
                'ultimo_condutor_nome' => $condutor->name,
            ]);

            return $movimentacao;
        });
    }

    public function registrarRetorno(int $movimentacaoId, array $dados): ViaturaMovimentacao
    {
        return DB::transaction(function () use ($movimentacaoId, $dados): ViaturaMovimentacao {
            $movimentacao = ViaturaMovimentacao::query()
                ->lockForUpdate()
                ->findOrFail($movimentacaoId);

            if ($movimentacao->status !== StatusMovimentacao::EM_TRANSITO) {
                throw new MovimentacaoInvalidaException(
                    'Esta movimentacao ja foi encerrada.'
                );
            }

            $hodometroRetorno = (int) $dados['retorno_hodometro'];

            if ($hodometroRetorno < $movimentacao->saida_hodometro) {
                throw new MovimentacaoInvalidaException(
                    "Hodometro de retorno ({$hodometroRetorno}) e menor que o de saida ({$movimentacao->saida_hodometro})."
                );
            }

            $movimentacao->update([
                'retorno_em' => $dados['retorno_em'] ?? now(),
                'retorno_hodometro' => $hodometroRetorno,
                'retorno_combustivel' => $dados['retorno_combustivel'],
                'alteracoes' => $dados['alteracoes'] ?? null,
                'status' => StatusMovimentacao::RETORNADA,
            ]);

            $viatura = Viatura::query()->lockForUpdate()->findOrFail($movimentacao->viatura_id);

            $this->sincronizarEstado($viatura, [
                'status' => StatusViatura::DISPONIVEL,
                'hodometro_atual' => $hodometroRetorno,
                'nivel_combustivel' => NivelCombustivel::from($dados['retorno_combustivel']),
                'ultimo_condutor_id' => $movimentacao->condutor_id,
                'ultimo_condutor_nome' => $movimentacao->condutor_nome,
            ]);

            return $movimentacao->fresh();
        });
    }

    /**
     * Escreve o cache de estado da viatura. Metodo privado de proposito: e a
     * fronteira que garante uma unica fonte de verdade.
     */
    private function sincronizarEstado(Viatura $viatura, array $estado): void
    {
        $viatura->update($estado);
    }
}
```

- [ ] **Step 5: Registro no provider — JA FEITO na Task 3**

Nao toque em `PlantaoServiceProvider.php`. O `singleton(MovimentacaoViaturaService::class)`
ja foi registrado na Task 3, por decisao do controlador, para permitir que esta
task rode em paralelo com as Tasks 4 e 11. Passo sem acao.

- [ ] **Step 6: Rodar o teste e confirmar que passa**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=MovimentacaoViaturaTest
```

Esperado: PASS, 7 testes.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Plantao/Exceptions app/Modules/Plantao/Services/MovimentacaoViaturaService.php app/Modules/Plantao/PlantaoServiceProvider.php tests/Feature/Plantao/MovimentacaoViaturaTest.php
git commit -m "✨ feat(plantao): movimentacao de viatura com saida, retorno e guardas de negocio"
```

---

### Task 7: Rotas, requests, controllers e modal de movimentacao

**Files:**
- Create: `app/Modules/Plantao/Requests/MovimentacaoSaidaRequest.php`
- Create: `app/Modules/Plantao/Requests/MovimentacaoRetornoRequest.php`
- Create: `app/Modules/Plantao/Controllers/MovimentacaoSaidaController.php`
- Create: `app/Modules/Plantao/Controllers/MovimentacaoRetornoController.php`
- Create: `resources/js/Components/Organisms/Plantao/MovimentacaoModal.vue`
- Modify: `routes/modules/plantao.php`
- Modify: `app/Modules/Plantao/Controllers/ViaturaIndexController.php`
- Modify: `resources/js/Templates/Plantao/ViaturasIndexTemplate.vue`
- Test: `tests/Feature/Plantao/MovimentacaoViaturaTest.php` (ampliar)

**Interfaces:**
- Consumes: `MovimentacaoViaturaService` (Task 6).
- Produces: rotas `plantao.viaturas.saida` (`POST /plantao/viaturas/{viatura}/saida`) e `plantao.movimentacoes.retorno` (`POST /plantao/movimentacoes/{movimentacao}/retorno`). `ViaturaIndexController` passa a enviar `condutores` (lista `{value,label}` de usuarios) e, em cada item de `viaturas.data`, `movimentacao_aberta_id: ?int`.

- [ ] **Step 1: Escrever o teste que falha**

Adicionar a `tests/Feature/Plantao/MovimentacaoViaturaTest.php`:

```php
    private const PERMS_MOV = [
        'plantao.viaturas.view',
        'plantao.viaturas.edit',
    ];

    private function actingAsOperadorMov(): static
    {
        foreach (self::PERMS_MOV as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMS_MOV);

        return $this->actingAs($user);
    }

    public function test_rota_de_saida_registra_movimentacao(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create(['name' => 'Sgt Mello']);

        $this->actingAsOperadorMov()
            ->post(route('plantao.viaturas.saida', $viatura), [
                'condutor_id' => $condutor->id,
                'saida_hodometro' => 100_050,
                'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
                'destino' => 'Contagem',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plantao_viatura_movimentacoes', [
            'viatura_id' => $viatura->id,
            'condutor_nome' => 'Sgt Mello',
            'status' => StatusMovimentacao::EM_TRANSITO->value,
        ]);
    }

    public function test_rota_de_saida_devolve_erro_de_validacao_na_guarda(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $viatura = Viatura::factory()->emManutencao()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $this->actingAsOperadorMov()
            ->post(route('plantao.viaturas.saida', $viatura), [
                'condutor_id' => $condutor->id,
                'saida_hodometro' => 100_050,
                'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
            ])
            ->assertSessionHasErrors('viatura');
    }

    public function test_rota_de_retorno_encerra_movimentacao(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_050,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->actingAsOperadorMov()
            ->post(route('plantao.movimentacoes.retorno', $mov), [
                'retorno_hodometro' => 100_200,
                'retorno_combustivel' => NivelCombustivel::QUARTO_2->value,
                'alteracoes' => 'Farol dianteiro direito queimado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plantao_viatura_movimentacoes', [
            'id' => $mov->id,
            'status' => StatusMovimentacao::RETORNADA->value,
            'alteracoes' => 'Farol dianteiro direito queimado',
        ]);
    }
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=MovimentacaoViaturaTest
```

Esperado: FAIL com `Route [plantao.viaturas.saida] not defined`.

- [ ] **Step 3: Criar os FormRequests**

`app/Modules/Plantao/Requests/MovimentacaoSaidaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condutor_id' => ['required', 'integer', 'exists:users,id'],
            'saida_hodometro' => ['required', 'integer', 'min:0'],
            'saida_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'destino' => ['nullable', 'string', 'max:160'],
            'motivo' => ['nullable', 'string', 'max:160'],
            'plantao_id' => ['nullable', 'integer', 'exists:plantoes,id'],
            'saida_em' => ['nullable', 'date'],
        ];
    }
}
```

`app/Modules/Plantao/Requests/MovimentacaoRetornoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoRetornoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'retorno_hodometro' => ['required', 'integer', 'min:0'],
            'retorno_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'alteracoes' => ['nullable', 'string', 'max:2000'],
            'retorno_em' => ['nullable', 'date'],
        ];
    }
}
```

**Nota.** A comparacao de hodometro nao entra no request: ela depende do estado
do banco e ja vive como guarda no servico, onde tambem protege chamadas fora do
ciclo HTTP. O request valida forma; o servico valida regra.

- [ ] **Step 4: Criar os controllers**

`app/Modules/Plantao/Controllers/MovimentacaoSaidaController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Requests\MovimentacaoSaidaRequest;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Http\RedirectResponse;

class MovimentacaoSaidaController extends Controller
{
    public function __construct(
        private readonly MovimentacaoViaturaService $movimentacaoService
    ) {
    }

    public function __invoke(MovimentacaoSaidaRequest $request, Viatura $viatura): RedirectResponse
    {
        try {
            $this->movimentacaoService->registrarSaida($viatura->id, $request->validated());
        } catch (MovimentacaoInvalidaException $e) {
            // A guarda de dominio vira erro de formulario: o usuario ve a razao
            // no campo, nao uma pagina de erro 500.
            return back()->withErrors(['viatura' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Saida registrada.');
    }
}
```

`app/Modules/Plantao/Controllers/MovimentacaoRetornoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
use App\Modules\Plantao\Requests\MovimentacaoRetornoRequest;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Http\RedirectResponse;

class MovimentacaoRetornoController extends Controller
{
    public function __construct(
        private readonly MovimentacaoViaturaService $movimentacaoService
    ) {
    }

    public function __invoke(
        MovimentacaoRetornoRequest $request,
        ViaturaMovimentacao $movimentacao
    ): RedirectResponse {
        try {
            $this->movimentacaoService->registrarRetorno($movimentacao->id, $request->validated());
        } catch (MovimentacaoInvalidaException $e) {
            return back()->withErrors(['movimentacao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Retorno registrado.');
    }
}
```

- [ ] **Step 5: Registrar as rotas**

Em `routes/modules/plantao.php`, dentro do grupo `viaturas`, adicionar:

```php
        Route::post('/{viatura}/saida', MovimentacaoSaidaController::class)
            ->name('saida')
            ->middleware('can:plantao.viaturas.edit');
```

E, fora do grupo `viaturas` mas dentro do grupo `plantao`, antes da rota `/`:

```php
    Route::post('/movimentacoes/{movimentacao}/retorno', MovimentacaoRetornoController::class)
        ->name('movimentacoes.retorno')
        ->middleware('can:plantao.viaturas.edit');
```

Mais os imports dos dois controllers.

- [ ] **Step 6: Ampliar o `ViaturaIndexController`**

Adicionar ao array de props do `Inertia::render`:

```php
            'condutores' => \App\Models\User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
                ->all(),
```

**Nota sobre a armadilha do `SelectInput`.** O componente le `value`/`id` e
`label`/`name`/`text`. Mandar a colecao crua de `users` faria a option renderizar
o objeto inteiro. O `map` acima ja entrega o par correto.

Adicionar tambem ao `ViaturaListDTO` o campo `movimentacao_aberta_id`, para o
frontend saber se o botao e Saida ou Retorno:

No construtor, apos `observacoes`:

```php
        public readonly ?int $movimentacao_aberta_id,
```

E em `fromModel`, ao final:

```php
            movimentacao_aberta_id: $viatura->movimentacaoAberta?->id,
```

E no `ViaturaService::list()` e `find()`, incluir a relacao no eager load:

```php
            ->with(['ultimoCondutor:id,name', 'movimentacaoAberta'])
```

- [ ] **Step 7: Criar o `MovimentacaoModal`**

`resources/js/Components/Organisms/Plantao/MovimentacaoModal.vue`. Um modal com
dois modos, decididos pela prop `modo` (`'saida'` ou `'retorno'`):

- Modo saida: `FormSelect` de condutor (recebe `condutores`), `FormField` de
  hodometro com `inputmode="numeric"` **declarado como prop no FormField**,
  `FormSelect` de combustivel (recebe `filterOptions.niveis`), `FormField` de
  destino e de motivo.
- Modo retorno: `FormField` de hodometro, `FormSelect` de combustivel,
  `FormTextarea` de alteracoes.
- Rodape com `FormActions`.
- Submete com `useForm` do Inertia para `route('plantao.viaturas.saida', viatura.id)`
  ou `route('plantao.movimentacoes.retorno', viatura.movimentacao_aberta_id)`.
- Exibe `form.errors.viatura` e `form.errors.movimentacao` num alerta no topo —
  e ali que a mensagem da guarda de dominio aparece.

- [ ] **Step 8: Ligar o modal na tela da frota**

Em `ViaturasIndexTemplate.vue`, para cada viatura, um `ActionButton` que abre o
modal em modo `saida` quando `movimentacao_aberta_id` e nulo, e em modo `retorno`
quando nao e.

- [ ] **Step 9: Rodar tudo e buildar**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
npm run prebuild
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=MovimentacaoViaturaTest
npm run build
```

Esperado: PASS em 10 testes; build sem erro.

- [ ] **Step 10: Commit**

```bash
git add app/Modules/Plantao/Requests app/Modules/Plantao/Controllers app/Modules/Plantao/DTOs app/Modules/Plantao/Services/ViaturaService.php routes/modules/plantao.php resources/js/Components/Organisms/Plantao/MovimentacaoModal.vue resources/js/Templates/Plantao/ViaturasIndexTemplate.vue resources/js/ziggy.js tests/Feature/Plantao/MovimentacaoViaturaTest.php
git commit -m "✨ feat(plantao): rotas e interface de saida e retorno de viatura"
```

**Marco da Fase 2.** O hodometro e o combustivel da frota passam a ser mantidos
pelo registro de movimentacao. Ponto de parada seguro.

---

# FASE 3 — Passagem de servico com aceite formal

Entrega: o ritual de troca de turno com snapshot pre-preenchido, aceite das duas partes e apontamento de divergencia.

---

### Task 8: PassagemServicoService

**Files:**
- Create: `app/Modules/Plantao/Services/PassagemServicoService.php`
- Create: `app/Modules/Plantao/Exceptions/PassagemInvalidaException.php`
- Modify: `app/Modules/Plantao/PlantaoServiceProvider.php`
- Modify: `app/Modules/Plantao/Services/PlantaoService.php`
- Test: `tests/Feature/Plantao/PassagemServicoTest.php`

**Interfaces:**
- Consumes: `Viatura`, `ViaturaSnapshot`, `Plantao` (Task 3); `StatusPlantao`, `StatusViatura` (Task 1).
- Produces:
  - `PassagemServicoService::abrirTurno(array $dados): Plantao`
    - `$dados`: `plantonista_id` (int), `data` (string), `periodo` (string), `localizacao` (?string).
  - `PassagemServicoService::montarSnapshotSugerido(Plantao $plantao): array` — array de arrays prontos para preencher a tela de encerramento, sem persistir.
  - `PassagemServicoService::encerrar(int $plantaoId, array $snapshots, ?string $ocorrenciasDestaque = null, ?int $encerradoPorId = null): Plantao`
    - `$snapshots`: lista de `['viatura_id'=>int,'hodometro'=>int,'nivel_combustivel'=>string,'alteracoes'=>?string,'anotacao'=>?string,'em_condicoes'=>bool]`.
  - `PassagemServicoService::aceitar(int $plantaoId, int $aceitoPorId): Plantao`
  - `PassagemServicoService::apontarDivergencia(int $plantaoId, int $aceitoPorId, string $divergencia): Plantao`
  - Lanca `App\Modules\Plantao\Exceptions\PassagemInvalidaException` (extends `RuntimeException`).
  - `PlantaoService::getStatistics()` passa a devolver tambem `pendentes_aceite`.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Plantao/PassagemServicoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Plantao;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PassagemServicoTest extends TestCase
{
    use DatabaseTransactions;

    private function passagem(): PassagemServicoService
    {
        return app(PassagemServicoService::class);
    }

    private function movimentacao(): MovimentacaoViaturaService
    {
        return app(MovimentacaoViaturaService::class);
    }

    public function test_abrir_primeiro_turno_nao_tem_plantonista_de_saida(): void
    {
        $leandro = User::factory()->create(['name' => 'Sgt Leandro']);

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $leandro->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
            'localizacao' => 'Predio Alterosas',
        ]);

        $this->assertSame(StatusPlantao::ATIVO, $turno->status);
        $this->assertNull($turno->plantonista_saida_id);
        $this->assertSame('Sgt Leandro', $turno->plantonista_nome);
    }

    public function test_snapshot_sugerido_vem_da_ultima_movimentacao(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_600]);
        $egidio = User::factory()->create(['name' => 'Sgt Egidio']);
        $deivison = User::factory()->create(['name' => 'Sgt Deivison']);

        $mov = $this->movimentacao()->registrarSaida($viatura->id, [
            'condutor_id' => $egidio->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
        $this->movimentacao()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 112_799,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
        ]);

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $sugerido = $this->passagem()->montarSnapshotSugerido($turno);

        $this->assertCount(1, $sugerido);
        $this->assertSame($viatura->id, $sugerido[0]['viatura_id']);
        $this->assertSame(112_799, $sugerido[0]['hodometro']);
        $this->assertSame(NivelCombustivel::QUARTO_3->value, $sugerido[0]['nivel_combustivel']);
        $this->assertSame('Sgt Egidio', $sugerido[0]['ultimo_condutor_nome']);
        $this->assertTrue($sugerido[0]['em_condicoes']);
    }

    public function test_snapshot_sugerido_marca_viatura_em_manutencao_fora_de_condicoes(): void
    {
        Viatura::factory()->emManutencao()->create(['hodometro_atual' => 90_000]);
        $deivison = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $sugerido = $this->passagem()->montarSnapshotSugerido($turno);

        $this->assertFalse($sugerido[0]['em_condicoes']);
    }

    public function test_encerrar_grava_snapshots_e_muda_status(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_799]);
        $deivison = User::factory()->create(['name' => 'Sgt Deivison']);

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 112_799,
            'nivel_combustivel' => NivelCombustivel::QUARTO_3->value,
            'alteracoes' => null,
            'anotacao' => null,
            'em_condicoes' => true,
        ]], 'Nao houve.');

        $this->assertSame(StatusPlantao::PENDENTE_ACEITE, $turno->status);
        $this->assertNotNull($turno->encerrado_em);
        $this->assertSame('Nao houve.', $turno->ocorrencias_destaque);

        $this->assertDatabaseHas('plantao_viatura_snapshots', [
            'plantao_id' => $turno->id,
            'viatura_id' => $viatura->id,
            'hodometro' => 112_799,
            'placa' => $viatura->placa,
        ]);
    }

    public function test_encerramento_por_terceiro_registra_quem_declarou(): void
    {
        // Mitigacao da secao 4.3 do spec: se quem sai nao encerra, quem assume
        // encerra em nome dele. O sistema nao esconde isso, registra.
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create(['name' => 'Sgt Deivison']);
        $leandro = User::factory()->create(['name' => 'Sgt Leandro']);

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]], null, $leandro->id);

        $this->assertSame($leandro->id, $turno->encerrado_por_id);
        $this->assertNotSame($turno->plantonista_id, $turno->encerrado_por_id);

        // E Leandro ainda pode aceitar: a guarda barra o DONO do turno, nao
        // quem operou o encerramento.
        $turno = $this->passagem()->aceitar($turno->id, $leandro->id);
        $this->assertSame(StatusPlantao::FINALIZADO, $turno->status);
    }

    public function test_encerramento_pelo_proprio_plantonista_grava_ele_mesmo(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $this->assertSame($deivison->id, $turno->encerrado_por_id);
    }

    public function test_encerrar_turno_que_nao_esta_ativo_e_rejeitado(): void
    {
        $turno = Plantao::factory()->pendenteAceite()->create();

        $this->expectException(PassagemInvalidaException::class);

        $this->passagem()->encerrar($turno->id, []);
    }

    public function test_aceitar_finaliza_o_turno(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create();
        $leandro = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $turno = $this->passagem()->aceitar($turno->id, $leandro->id);

        $this->assertSame(StatusPlantao::FINALIZADO, $turno->status);
        $this->assertSame($leandro->id, $turno->aceito_por_id);
        $this->assertNotNull($turno->aceito_em);
        $this->assertNull($turno->divergencia);
    }

    public function test_quem_encerrou_nao_pode_aceitar_o_proprio_turno(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $this->expectException(PassagemInvalidaException::class);

        $this->passagem()->aceitar($turno->id, $deivison->id);
    }

    public function test_apontar_divergencia_registra_o_texto(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create();
        $leandro = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
        $turno = $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $turno = $this->passagem()->apontarDivergencia(
            $turno->id,
            $leandro->id,
            'Hodometro declarado nao confere com o painel.'
        );

        $this->assertSame(StatusPlantao::FINALIZADO_COM_DIVERGENCIA, $turno->status);
        $this->assertSame('Hodometro declarado nao confere com o painel.', $turno->divergencia);
    }

    public function test_aceitar_turno_que_nao_esta_pendente_e_rejeitado(): void
    {
        $turno = Plantao::factory()->finalizado()->create();
        $leandro = User::factory()->create();

        $this->expectException(PassagemInvalidaException::class);

        $this->passagem()->aceitar($turno->id, $leandro->id);
    }

    public function test_segundo_turno_ativo_na_mesma_data_e_periodo_e_rejeitado(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->passagem()->abrirTurno([
            'plantonista_id' => $a->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $this->expectException(PassagemInvalidaException::class);

        $this->passagem()->abrirTurno([
            'plantonista_id' => $b->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
    }

    public function test_abrir_turno_herda_plantonista_de_saida_do_turno_anterior(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create(['name' => 'Sgt Deivison']);
        $leandro = User::factory()->create(['name' => 'Sgt Leandro']);

        $primeiro = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
        $primeiro = $this->passagem()->encerrar($primeiro->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $segundo = $this->passagem()->abrirTurno([
            'plantonista_id' => $leandro->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::NOTURNO->value,
        ]);

        $this->assertSame($deivison->id, $segundo->plantonista_saida_id);
        $this->assertSame('Sgt Deivison', $segundo->plantonista_saida_nome);
    }

    public function test_turno_anterior_pendente_nao_bloqueia_abertura_do_novo(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $deivison = User::factory()->create();
        $leandro = User::factory()->create();

        $primeiro = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);
        $this->passagem()->encerrar($primeiro->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 100_000,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'em_condicoes' => true,
        ]]);

        $segundo = $this->passagem()->abrirTurno([
            'plantonista_id' => $leandro->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::NOTURNO->value,
        ]);

        $this->assertSame(StatusPlantao::ATIVO, $segundo->status);
        $this->assertSame(
            StatusPlantao::PENDENTE_ACEITE,
            $primeiro->fresh()->status
        );
    }
}
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
```

Esperado: FAIL com `Class "App\Modules\Plantao\Exceptions\PassagemInvalidaException" not found`.

- [ ] **Step 3: Criar a excecao**

`app/Modules/Plantao/Exceptions/PassagemInvalidaException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Exceptions;

use RuntimeException;

class PassagemInvalidaException extends RuntimeException
{
}
```

- [ ] **Step 4: Criar o `PassagemServicoService`**

`app/Modules/Plantao/Services/PassagemServicoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaSnapshot;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Facades\DB;

class PassagemServicoService extends BaseService
{
    /**
     * Abre um turno. Preenche o plantonista de saida a partir do ultimo turno
     * conhecido; se nao houver antecessor, os campos ficam nulos e o relatorio
     * omite a linha "Saindo de servico".
     */
    public function abrirTurno(array $dados): Plantao
    {
        return DB::transaction(function () use ($dados): Plantao {
            $data = $dados['data'];
            $periodo = $dados['periodo'];

            $jaAtivo = Plantao::query()
                ->whereDate('data', $data)
                ->where('periodo', $periodo)
                ->where('status', StatusPlantao::ATIVO->value)
                ->exists();

            if ($jaAtivo) {
                throw new PassagemInvalidaException(
                    'Ja existe um plantao ativo para esta data e periodo.'
                );
            }

            $plantonista = User::findOrFail((int) $dados['plantonista_id']);
            $anterior = $this->turnoAnterior();

            return Plantao::create([
                'plantonista_id' => $plantonista->id,
                'plantonista_nome' => $plantonista->name,
                'plantonista_saida_id' => $anterior?->plantonista_id,
                'plantonista_saida_nome' => $anterior?->plantonista_nome,
                'data' => $data,
                'periodo' => $periodo,
                'status' => StatusPlantao::ATIVO,
                'localizacao' => $dados['localizacao'] ?? 'Predio Alterosas',
            ]);
        });
    }

    /**
     * Estado sugerido de cada viatura ativa, derivado do estado corrente que o
     * MovimentacaoViaturaService mantem. Nao persiste: alimenta a tela de
     * encerramento, onde o plantonista confirma ou corrige linha a linha.
     *
     * @return list<array<string,mixed>>
     */
    public function montarSnapshotSugerido(Plantao $plantao): array
    {
        return Viatura::query()
            ->ativas()
            ->with('ultimoCondutor:id,name')
            ->orderBy('prefixo')
            ->orderBy('placa')
            ->get()
            ->map(fn(Viatura $v) => [
                'viatura_id' => $v->id,
                'prefixo' => $v->prefixo,
                'placa' => $v->placa,
                'hodometro' => $v->hodometro_atual,
                'nivel_combustivel' => $v->nivel_combustivel?->value,
                'alteracoes' => null,
                'anotacao' => $v->exclusiva_sobreaviso ? 'Exclusiva Sobreaviso' : null,
                'ultimo_condutor_id' => $v->ultimo_condutor_id,
                'ultimo_condutor_nome' => $v->ultimo_condutor_nome,
                'em_condicoes' => $v->status->emCondicoes(),
            ])
            ->all();
    }

    /**
     * Quem sai declara o estado. O turno vai para PENDENTE_ACEITE aguardando a
     * conferencia de quem assume.
     *
     * @param list<array<string,mixed>> $snapshots
     */
    public function encerrar(
        int $plantaoId,
        array $snapshots,
        ?string $ocorrenciasDestaque = null,
        ?int $encerradoPorId = null
    ): Plantao {
        return DB::transaction(function () use ($plantaoId, $snapshots, $ocorrenciasDestaque, $encerradoPorId): Plantao {
            $plantao = Plantao::query()->lockForUpdate()->findOrFail($plantaoId);

            if ($plantao->status !== StatusPlantao::ATIVO) {
                throw new PassagemInvalidaException(
                    'Somente um plantao ativo pode ser encerrado.'
                );
            }

            foreach ($snapshots as $linha) {
                $viatura = Viatura::findOrFail((int) $linha['viatura_id']);

                ViaturaSnapshot::updateOrCreate(
                    [
                        'plantao_id' => $plantao->id,
                        'viatura_id' => $viatura->id,
                    ],
                    [
                        // Espelhos: o snapshot precisa continuar fiel ao que foi
                        // declarado, mesmo se a placa mudar depois.
                        'prefixo' => $viatura->prefixo,
                        'placa' => $viatura->placa,
                        'hodometro' => (int) $linha['hodometro'],
                        'nivel_combustivel' => $linha['nivel_combustivel'],
                        'alteracoes' => $linha['alteracoes'] ?? null,
                        'ultimo_condutor_id' => $viatura->ultimo_condutor_id,
                        'ultimo_condutor_nome' => $viatura->ultimo_condutor_nome,
                        'anotacao' => $linha['anotacao'] ?? null,
                        'em_condicoes' => (bool) ($linha['em_condicoes'] ?? true),
                    ]
                );
            }

            $plantao->update([
                'status' => StatusPlantao::PENDENTE_ACEITE,
                'encerrado_em' => now(),
                // Sem quem chamou informado, assume-se o proprio plantonista.
                'encerrado_por_id' => $encerradoPorId ?? $plantao->plantonista_id,
                'ocorrencias_destaque' => $ocorrenciasDestaque,
            ]);

            return $plantao->fresh();
        });
    }

    public function aceitar(int $plantaoId, int $aceitoPorId): Plantao
    {
        return $this->concluir($plantaoId, $aceitoPorId, StatusPlantao::FINALIZADO, null);
    }

    public function apontarDivergencia(int $plantaoId, int $aceitoPorId, string $divergencia): Plantao
    {
        return $this->concluir(
            $plantaoId,
            $aceitoPorId,
            StatusPlantao::FINALIZADO_COM_DIVERGENCIA,
            $divergencia
        );
    }

    private function concluir(
        int $plantaoId,
        int $aceitoPorId,
        StatusPlantao $status,
        ?string $divergencia
    ): Plantao {
        return DB::transaction(function () use ($plantaoId, $aceitoPorId, $status, $divergencia): Plantao {
            $plantao = Plantao::query()->lockForUpdate()->findOrFail($plantaoId);

            if ($plantao->status !== StatusPlantao::PENDENTE_ACEITE) {
                throw new PassagemInvalidaException(
                    'Somente um plantao pendente de aceite pode ser conferido.'
                );
            }

            // O aceite formal perde sentido se quem confere e quem declarou.
            if ((int) $plantao->plantonista_id === $aceitoPorId) {
                throw new PassagemInvalidaException(
                    'Quem encerrou o plantao nao pode aceitar a propria passagem.'
                );
            }

            $plantao->update([
                'status' => $status,
                'aceito_em' => now(),
                'aceito_por_id' => $aceitoPorId,
                'divergencia' => $divergencia,
            ]);

            return $plantao->fresh();
        });
    }

    private function turnoAnterior(): ?Plantao
    {
        return Plantao::query()
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->first();
    }
}
```

- [ ] **Step 5: Ampliar `PlantaoService::getStatistics()`**

Substituir o metodo por:

```php
    public function getStatistics(array $filters = []): array
    {
        $porStatus = Plantao::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($porStatus),
            'ativos' => (int) ($porStatus[StatusPlantao::ATIVO->value] ?? 0),
            'pendentes_aceite' => (int) ($porStatus[StatusPlantao::PENDENTE_ACEITE->value] ?? 0),
            'finalizados_hoje' => Plantao::query()
                ->whereIn('status', [
                    StatusPlantao::FINALIZADO->value,
                    StatusPlantao::FINALIZADO_COM_DIVERGENCIA->value,
                ])
                ->whereDate('data', now()->toDateString())
                ->count(),
        ];
    }
```

Adicionar o import de `StatusPlantao`. Note que a chave `equipe_online` foi
removida: era duplicata de `ativos`, sem consumidor distinto. Verificar o
frontend antes de remover e ajustar `PlantaoStatsCards.vue` na Task 11.

- [ ] **Step 6: Registro no provider — JA FEITO na Task 3**

Nao toque em `PlantaoServiceProvider.php`. O `singleton(PassagemServicoService::class)`
ja foi registrado na Task 3. Passo sem acao.

- [ ] **Step 7: Rodar o teste e confirmar que passa**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
```

Esperado: PASS, 14 testes.

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Plantao/Services/PassagemServicoService.php app/Modules/Plantao/Services/PlantaoService.php app/Modules/Plantao/Exceptions/PassagemInvalidaException.php app/Modules/Plantao/PlantaoServiceProvider.php tests/Feature/Plantao/PassagemServicoTest.php
git commit -m "✨ feat(plantao): passagem de servico com snapshot sugerido e aceite das duas partes"
```

---

### Task 9: Rotas, requests e controllers da passagem

**Files:**
- Create: `app/Modules/Plantao/Requests/EncerrarPassagemRequest.php`
- Create: `app/Modules/Plantao/Requests/AceitarPassagemRequest.php`
- Create: `app/Modules/Plantao/Controllers/PassagemEncerrarController.php`
- Create: `app/Modules/Plantao/Controllers/PassagemAceitarController.php`
- Create: `app/Modules/Plantao/DTOs/SnapshotDTO.php`
- Modify: `routes/modules/plantao.php`
- Modify: `app/Modules/Plantao/Controllers/PlantaoIndexController.php`
- Test: `tests/Feature/Plantao/PassagemServicoTest.php` (ampliar)

**Interfaces:**
- Consumes: `PassagemServicoService` (Task 8).
- Produces: rotas `plantao.passagem.encerrar` (`POST /plantao/{plantao}/encerrar`) e `plantao.passagem.aceitar` (`POST /plantao/{plantao}/aceitar`). `SnapshotDTO::fromModel(ViaturaSnapshot $s): self` e `::collection(iterable): array`. `PlantaoIndexController` passa a enviar `turnoAtivo`, `turnoPendente` e `snapshotSugerido`.

- [ ] **Step 1: Escrever o teste que falha**

Adicionar a `tests/Feature/Plantao/PassagemServicoTest.php`:

```php
    private const PERMS_PASSAGEM = [
        'plantao.turnos.view',
        'plantao.passagem.encerrar',
        'plantao.passagem.aceitar',
    ];

    private function actingAsPlantonista(User $user): static
    {
        foreach (self::PERMS_PASSAGEM as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(self::PERMS_PASSAGEM);

        return $this->actingAs($user);
    }

    public function test_rota_de_encerramento_grava_snapshot(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_799]);
        $deivison = User::factory()->create();

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::DIURNO->value,
        ]);

        $this->actingAsPlantonista($deivison)
            ->post(route('plantao.passagem.encerrar', $turno), [
                'ocorrencias_destaque' => 'Nao houve.',
                'snapshots' => [[
                    'viatura_id' => $viatura->id,
                    'hodometro' => 112_799,
                    'nivel_combustivel' => NivelCombustivel::QUARTO_3->value,
                    'alteracoes' => null,
                    'anotacao' => null,
                    'em_condicoes' => true,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plantoes', [
            'id' => $turno->id,
            'status' => StatusPlantao::PENDENTE_ACEITE->value,
        ]);
    }

    public function test_rota_de_aceite_exige_permissao(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $turno = Plantao::factory()->pendenteAceite()->create();
        $qualquer = User::factory()->create();

        $this->actingAs($qualquer)
            ->post(route('plantao.passagem.aceitar', $turno), ['acao' => 'aceitar'])
            ->assertForbidden();
    }

    public function test_rota_de_aceite_com_divergencia_exige_texto(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $turno = Plantao::factory()->pendenteAceite()->create();
        $leandro = User::factory()->create();

        $this->actingAsPlantonista($leandro)
            ->post(route('plantao.passagem.aceitar', $turno), ['acao' => 'divergencia'])
            ->assertSessionHasErrors('divergencia');
    }

    public function test_rota_de_aceite_devolve_erro_quando_e_o_proprio_plantonista(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $deivison = User::factory()->create();
        $turno = Plantao::factory()->pendenteAceite()->create([
            'plantonista_id' => $deivison->id,
        ]);

        $this->actingAsPlantonista($deivison)
            ->post(route('plantao.passagem.aceitar', $turno), ['acao' => 'aceitar'])
            ->assertSessionHasErrors('plantao');
    }
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
```

Esperado: FAIL com `Route [plantao.passagem.encerrar] not defined`.

- [ ] **Step 3: Criar os FormRequests**

`app/Modules/Plantao/Requests/EncerrarPassagemRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncerrarPassagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ocorrencias_destaque' => ['nullable', 'string', 'max:5000'],
            'snapshots' => ['required', 'array', 'min:1'],
            'snapshots.*.viatura_id' => ['required', 'integer', 'exists:plantao_viaturas,id'],
            'snapshots.*.hodometro' => ['required', 'integer', 'min:0'],
            'snapshots.*.nivel_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'snapshots.*.alteracoes' => ['nullable', 'string', 'max:2000'],
            'snapshots.*.anotacao' => ['nullable', 'string', 'max:160'],
            'snapshots.*.em_condicoes' => ['required', 'boolean'],
        ];
    }
}
```

`app/Modules/Plantao/Requests/AceitarPassagemRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AceitarPassagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acao' => ['required', 'in:aceitar,divergencia'],
            // Divergencia sem texto nao serve para nada: o proximo turno precisa
            // saber o que nao conferiu.
            'divergencia' => ['required_if:acao,divergencia', 'nullable', 'string', 'max:2000'],
        ];
    }
}
```

- [ ] **Step 4: Criar o `SnapshotDTO`**

`app/Modules/Plantao/DTOs/SnapshotDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\ViaturaSnapshot;

class SnapshotDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $viatura_id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly int $hodometro,
        public readonly string $combustivel_label,
        public readonly int $combustivel_percentual,
        public readonly ?string $alteracoes,
        public readonly ?string $ultimo_condutor_nome,
        public readonly ?string $anotacao,
        public readonly bool $em_condicoes,
    ) {
    }

    public static function fromModel(ViaturaSnapshot $snapshot): self
    {
        return new self(
            id: $snapshot->id,
            viatura_id: $snapshot->viatura_id,
            prefixo: $snapshot->prefixo,
            placa: $snapshot->placa,
            hodometro: $snapshot->hodometro,
            combustivel_label: $snapshot->nivel_combustivel?->label() ?? '',
            combustivel_percentual: $snapshot->nivel_combustivel?->percentual() ?? 0,
            alteracoes: $snapshot->alteracoes,
            ultimo_condutor_nome: $snapshot->ultimo_condutor_nome,
            anotacao: $snapshot->anotacao,
            em_condicoes: (bool) $snapshot->em_condicoes,
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn(ViaturaSnapshot $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
```

- [ ] **Step 5: Criar os controllers**

`app/Modules/Plantao/Controllers/PassagemEncerrarController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\EncerrarPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

class PassagemEncerrarController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(EncerrarPassagemRequest $request, Plantao $plantao): RedirectResponse
    {
        $dados = $request->validated();

        try {
            $this->passagemService->encerrar(
                $plantao->id,
                $dados['snapshots'],
                $dados['ocorrencias_destaque'] ?? null,
                (int) $request->user()->id
            );
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['plantao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Plantao encerrado. Aguardando aceite de quem assume.');
    }
}
```

`app/Modules/Plantao/Controllers/PassagemAceitarController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\AceitarPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

class PassagemAceitarController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(AceitarPassagemRequest $request, Plantao $plantao): RedirectResponse
    {
        $dados = $request->validated();
        $userId = (int) $request->user()->id;

        try {
            if ($dados['acao'] === 'divergencia') {
                $this->passagemService->apontarDivergencia(
                    $plantao->id,
                    $userId,
                    $dados['divergencia']
                );

                return back()->with('success', 'Divergencia registrada.');
            }

            $this->passagemService->aceitar($plantao->id, $userId);
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['plantao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Passagem de servico aceita.');
    }
}
```

- [ ] **Step 6: Registrar as rotas**

Em `routes/modules/plantao.php`, adicionar dentro do grupo `plantao`, **depois**
das rotas estaticas e do grupo `viaturas`, e **antes** da rota `/`:

```php
    Route::post('/{plantao}/encerrar', PassagemEncerrarController::class)
        ->name('passagem.encerrar')
        ->middleware('can:plantao.passagem.encerrar');

    Route::post('/{plantao}/aceitar', PassagemAceitarController::class)
        ->name('passagem.aceitar')
        ->middleware('can:plantao.passagem.aceitar');
```

Mais os imports.

- [ ] **Step 7: Ampliar o `PlantaoIndexController`**

Adicionar ao array de props do `Inertia::render`:

```php
            'turnoAtivo' => $this->turnoAtivo(),
            'turnoPendente' => $this->turnoPendente(),
            'canEncerrar' => (bool) $request->user()?->can('plantao.passagem.encerrar'),
            'canAceitar' => (bool) $request->user()?->can('plantao.passagem.aceitar'),
            'canRelatorio' => (bool) $request->user()?->can('plantao.passagem.relatorio'),
```

E os metodos privados, injetando `PassagemServicoService` no construtor:

```php
    private function turnoAtivo(): ?array
    {
        $turno = Plantao::query()
            ->where('status', StatusPlantao::ATIVO->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->first();

        if ($turno === null) {
            return null;
        }

        return [
            'id' => $turno->id,
            'data' => $turno->data?->format('d/m/Y'),
            'periodo' => $turno->periodo?->labelCurto(),
            'plantonista_nome' => $turno->plantonista_nome,
            'plantonista_saida_nome' => $turno->plantonista_saida_nome,
            'snapshot_sugerido' => $this->passagemService->montarSnapshotSugerido($turno),
        ];
    }

    private function turnoPendente(): ?array
    {
        $turno = Plantao::query()
            ->where('status', StatusPlantao::PENDENTE_ACEITE->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->with(['snapshots', 'encerradoPor:id,name'])
            ->first();

        if ($turno === null) {
            return null;
        }

        return [
            'id' => $turno->id,
            'data' => $turno->data?->format('d/m/Y'),
            'periodo' => $turno->periodo?->labelCurto(),
            'plantonista_nome' => $turno->plantonista_nome,
            'encerrado_em' => $turno->encerrado_em?->format('d/m/Y H:i'),
            // Quando difere do dono do turno, o encerramento foi por terceiro
            // e a interface precisa deixar isso visivel (spec 4.3).
            'encerrado_por_terceiro' => $turno->encerrado_por_id !== null
                && (int) $turno->encerrado_por_id !== (int) $turno->plantonista_id,
            'encerrado_por_nome' => $turno->encerradoPor?->name,
            'snapshots' => SnapshotDTO::collection($turno->snapshots),
        ];
    }
```

**Armadilha do Inertia a respeitar.** `turnoAtivo` e `turnoPendente` fazem query
a cada visita completa. O filtro da listagem de turnos deve usar reload parcial
com `only: ['plantoes', 'filters']` para nao recalcular esses dois.

- [ ] **Step 8: Rodar tudo**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
npm run prebuild
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
```

Esperado: PASS, 18 testes.

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Plantao/Requests app/Modules/Plantao/Controllers app/Modules/Plantao/DTOs/SnapshotDTO.php routes/modules/plantao.php resources/js/ziggy.js tests/Feature/Plantao/PassagemServicoTest.php
git commit -m "✨ feat(plantao): rotas de encerramento e aceite da passagem de servico"
```

---

### Task 10: Interface da passagem — encerrar, aceitar, banner

**Files:**
- Create: `resources/js/Components/Molecules/Plantao/ViaturaSnapshotCard.vue`
- Create: `resources/js/Components/Molecules/Plantao/PassagemHandshakeBanner.vue`
- Create: `resources/js/Components/Organisms/Plantao/EncerrarTurnoModal.vue`
- Create: `resources/js/Components/Organisms/Plantao/AceitarPassagemModal.vue`
- Modify: `resources/js/Templates/Plantao/PlantaoIndexTemplate.vue`
- Modify: `resources/js/Components/Organisms/Plantao/PlantaoStatsCards.vue`
- Modify: `resources/js/Components/Organisms/Plantao/AbrirPlantaoModal.vue` (labels de periodo hardcoded, ver Step 4b)
- Modify: `resources/js/Pages/Plantao/PlantaoIndex.vue`

**Interfaces:**
- Consumes: props `turnoAtivo`, `turnoPendente`, `canEncerrar`, `canAceitar`, `canRelatorio` (Task 9); `CombustivelGauge`, `HodometroBadge` (Task 5).
- Produces: nada consumido por tasks posteriores alem do `ViaturaSnapshotCard`, reusado pelo `RelatorioPassagemPanel` na Task 12.

- [ ] **Step 1: Criar o `ViaturaSnapshotCard`**

`resources/js/Components/Molecules/Plantao/ViaturaSnapshotCard.vue`:

```vue
<script setup>
import CombustivelGauge from '@/Components/Atoms/Plantao/CombustivelGauge.vue';
import HodometroBadge from '@/Components/Atoms/Plantao/HodometroBadge.vue';

defineProps({
  snapshot: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div
    class="flex gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
    :class="{ 'opacity-60': !snapshot.em_condicoes }"
  >
    <CombustivelGauge
      :percentual="snapshot.combustivel_percentual"
      :label="snapshot.combustivel_label"
      altura="h-24"
    />

    <div class="min-w-0 flex-1 space-y-1.5">
      <div class="flex flex-wrap items-baseline gap-2">
        <span class="font-semibold text-gray-900 dark:text-gray-100">
          {{ snapshot.prefixo }} - {{ snapshot.placa }}
        </span>
        <span
          v-if="snapshot.anotacao"
          class="rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300"
        >
          {{ snapshot.anotacao }}
        </span>
        <span
          v-if="!snapshot.em_condicoes"
          class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300"
        >
          Fora de condicoes
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-300">
        <HodometroBadge :valor="snapshot.hodometro" />
        <span>
          Alteracoes:
          <span class="font-medium">{{ snapshot.alteracoes || 'Sem alteracoes' }}</span>
        </span>
        <span v-if="snapshot.ultimo_condutor_nome">
          Ultimo condutor:
          <span class="font-medium">{{ snapshot.ultimo_condutor_nome }}</span>
        </span>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Criar o `PassagemHandshakeBanner`**

`resources/js/Components/Molecules/Plantao/PassagemHandshakeBanner.vue`:

```vue
<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
  turno: {
    type: Object,
    required: true,
  },
  podeAceitar: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['conferir']);
</script>

<template>
  <div
    class="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-700 dark:bg-amber-900/20"
  >
    <div class="flex items-start gap-3">
      <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
      <div class="text-sm">
        <p class="font-semibold text-amber-900 dark:text-amber-200">
          Passagem de servico pendente de aceite
        </p>
        <p class="text-amber-800 dark:text-amber-300">
          {{ turno.plantonista_nome }} encerrou o turno de {{ turno.data }}
          ({{ turno.periodo }}) em {{ turno.encerrado_em }}.
          Confira as viaturas antes de aceitar.
        </p>
        <p
          v-if="turno.encerrado_por_terceiro"
          class="mt-1 font-medium text-amber-900 dark:text-amber-200"
        >
          Encerrado por {{ turno.encerrado_por_nome }} em nome de
          {{ turno.plantonista_nome }}.
        </p>
      </div>
    </div>

    <Button
      v-if="podeAceitar"
      variant="primary"
      size="md"
      @click="$emit('conferir')"
    >
      Conferir e aceitar
    </Button>
  </div>
</template>
```

- [ ] **Step 3: Criar o `EncerrarTurnoModal`**

`resources/js/Components/Organisms/Plantao/EncerrarTurnoModal.vue`. Requisitos:

- Recebe `turno` (com `snapshot_sugerido`) e `filterOptions.niveis`.
- Copia `snapshot_sugerido` para um `ref` local ao abrir — o usuario edita a
  copia, nunca a prop.
- Uma linha editavel por viatura: `prefixo` e `placa` em texto fixo (nao
  editaveis), `FormField` de hodometro com `inputmode` numerico, `FormSelect` de
  combustivel, `FormTextarea` curto de alteracoes, `FormField` de anotacao,
  `ToggleField` de `em_condicoes`.
- Um `FormTextarea` no final para `ocorrencias_destaque`.
- Submete com `useForm` para `route('plantao.passagem.encerrar', turno.id)`.
- Exibe `form.errors.plantao` num alerta no topo.

- [ ] **Step 4: Criar o `AceitarPassagemModal`**

`resources/js/Components/Organisms/Plantao/AceitarPassagemModal.vue`. Requisitos:

- Recebe `turno` (com `snapshots`, ja em `SnapshotDTO`).
- Lista os snapshots em modo **leitura**, um `ViaturaSnapshotCard` por viatura.
- Dois botoes: `Aceitar` e `Apontar divergencia`.
- `Apontar divergencia` revela um `FormTextarea` obrigatorio; o submit envia
  `acao: 'divergencia'` mais o texto. `Aceitar` envia `acao: 'aceitar'`.
- Exibe `form.errors.plantao` e `form.errors.divergencia`.

- [ ] **Step 4b: Corrigir os labels de periodo hardcoded no `AbrirPlantaoModal`**

Lacuna encontrada durante a execucao da Task 1, roteada para ca porque nenhuma
task do plano original era dona deste arquivo.

`resources/js/Components/Organisms/Plantao/AbrirPlantaoModal.vue:129-130` tem os
labels ANTIGOS embutidos no codigo:

```js
      { value: 'DIURNO', label: '07:00hs as 19:00hs' },
      { value: 'NOTURNO', label: '19:00hs as 07:00hs' },
```

A Task 1 corrigiu o enum para 06-16 e 16-02, entao este modal passou a oferecer
horario que nao existe mais em nenhum outro lugar do sistema.

**Nao corrija trocando as strings.** A causa e o hardcode, nao o valor: o backend
ja envia `filterOptions.periodos` a partir de `PeriodoPlantao::toSelectArray()`,
que e a fonte de verdade. O modal deve consumir essa prop.

1. Verifique se `AbrirPlantaoModal.vue` ja recebe `filterOptions` como prop. Se
   nao, adicione a prop e repasse-a de `PlantaoIndexTemplate.vue`, que ja a tem.
2. Troque o array literal pelo consumo da prop, mantendo um fallback vazio:

```js
const periodos = computed(() => props.filterOptions?.periodos ?? []);
```

3. O `<FormSelect>` (ou `SelectInput`) passa a receber `periodos`. O formato de
   `toSelectArray()` ja e `{value, label}`, entao **nao remapeie** — a armadilha
   do `SelectInput` (que le `value`/`id` e `label`/`name`/`text`) nao se aplica
   aqui.
4. Confirme que nao sobrou nenhum horario embutido em `.vue`:

```bash
grep -rn "07:00hs\|19:00hs\|06:00hs\|16:00hs" resources/js/
```

Esperado: nenhuma saida.

- [ ] **Step 5: Ajustar o `PlantaoStatsCards`**

`PlantaoService::getStatistics()` trocou `equipe_online` por
`pendentes_aceite` na Task 8. Atualizar o card correspondente em
`resources/js/Components/Organisms/Plantao/PlantaoStatsCards.vue`: rotulo
`Pendentes de aceite`, chave `pendentes_aceite`, e o card e filtro rapido que
emite o status `PENDENTE_ACEITE`.

- [ ] **Step 6: Ligar tudo no `PlantaoIndexTemplate`**

Em `resources/js/Templates/Plantao/PlantaoIndexTemplate.vue`:

- Declarar as props novas: `turnoAtivo`, `turnoPendente`, `canEncerrar`,
  `canAceitar`, `canRelatorio`.
- Renderizar `PassagemHandshakeBanner` logo abaixo do `PageHeader`, com
  `v-if="turnoPendente"`, `:podeAceitar="canAceitar"`, e
  `@conferir="showAceitarModal = true"`.
- Adicionar no slot `#actions` do `PageHeader` um `Button` **Encerrar turno**
  com `v-if="canEncerrar && turnoAtivo"`, que abre o `EncerrarTurnoModal`.
- Adicionar um `Button` **Frota** que navega para `route('plantao.viaturas.index')`.
- Montar `EncerrarTurnoModal` e `AceitarPassagemModal` ao final do template.

- [ ] **Step 7: Ajustar o filtro para reload parcial**

Em `resources/js/Pages/Plantao/PlantaoIndex.vue`, o handler de filtro precisa de
`only` para nao recalcular `turnoAtivo`, `turnoPendente` e `statistics` a cada
mudanca de filtro:

```js
const handleFilter = (filtros) => {
  router.get(route('plantao.index'), filtros, {
    preserveState: true,
    preserveScroll: true,
    only: ['plantoes', 'filters'],
  });
};
```

- [ ] **Step 8: Buildar e verificar manualmente**

```bash
npm run build
```

Roteiro de verificacao no navegador:

1. Abrir `/plantao` com um turno ativo. O botao **Encerrar turno** aparece.
2. Encerrar preenchendo as viaturas. O banner de pendencia aparece.
3. Logar como outro usuario com `plantao.passagem.aceitar`. Conferir e aceitar.
   O banner desaparece e o turno vira Finalizado.
4. Repetir escolhendo **Apontar divergencia** sem texto: o formulario acusa erro.
5. Tentar aceitar o proprio turno: o alerta do topo mostra a mensagem da guarda.

- [ ] **Step 9: Commit**

```bash
git add resources/js/Components/Molecules/Plantao resources/js/Components/Organisms/Plantao resources/js/Templates/Plantao/PlantaoIndexTemplate.vue resources/js/Pages/Plantao/PlantaoIndex.vue
git commit -m "✨ feat(plantao): interface de encerramento, aceite e banner de passagem pendente"
```

**Marco da Fase 3.** O ritual de passagem de servico funciona ponta a ponta na
interface. Ponto de parada seguro.

---

# FASE 4 — Relatorio e copia para WhatsApp

Entrega: o texto que a tropa cola hoje, gerado pelo sistema. Esta e a fase que fecha o proposito da release.

---

### Task 11: Template do relatorio e RelatorioPassagemService

**Files:**
- Create: `config/plantao.php`
- Create: `resources/views/plantao/passagem-servico.txt.blade.php`
- Create: `app/Modules/Plantao/Services/RelatorioPassagemService.php`
- Modify: `app/Modules/Plantao/PlantaoServiceProvider.php`
- Test: `tests/Unit/Plantao/RelatorioPassagemServiceTest.php`

**Interfaces:**
- Consumes: `Plantao` com `snapshots` carregados (Task 3); `PeriodoPlantao::labelCurto()` (Task 1).
- Produces: `RelatorioPassagemService::renderizar(Plantao $plantao): string`.

- [ ] **Step 1: Criar o config do rodape**

`config/plantao.php`:

```php
<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Rodape do relatorio de passagem de servico
    |---------------------------------------------------------------------------
    |
    | Constantes operacionais, nao dado de turno. Vivem em config para que um
    | telefone possa ser corrigido sem tocar em template nem em service. Na
    | release do painel de postos organicos, os contatos passam a ser dado
    | gerenciado em banco.
    |
    */

    'relatorio' => [
        'rodape' => [
            'contatos_diesel' => [
                '3 BBM: 031 3490-5531',
                '3a Cia PE - Santa Luzia: 031 3268-0958 / 031 2138-5700',
                '1 BBM: 031 3289-8073',
                '40 BPM: 031 3036-0750',
                '5 BPM: 031 2123-1167',
            ],
            'link_bi' => env(
                'PLANTAO_LINK_BI_COMBUSTIVEL',
                'https://app.powerbi.com/view?r=eyJrIjoiN2RhYjQ3N2MtMDAxOC00YmI4LThjNGYtMjZiMjE0OWNjZGQ0IiwidCI6ImU1ZDNhZTdjLTliMzgtNDhkZS1hMDg3LWY2NzM0YTI4NzU3NCJ9'
            ),
            'dtt' => 'saida de viaturas de Segunda a Sexta de 06:00 as 22:00 - Tel. (31)-9-9826-2400 / 3915-4718',
            'gmg' => 'saida de viaturas CEDEC final de semana e feriados - Tel. (31) 9-9382-6023.',
        ],
    ],

];
```

- [ ] **Step 2: Criar o template de texto**

`resources/views/plantao/passagem-servico.txt.blade.php`.

**Este e o unico arquivo desta release que contem emoji.** Ele e view, nao
codigo. O service que o consome nao conhece nenhum caractere unicode.

```blade
Serviço de Plantão ({{ $data }} - {{ $periodo }})

Assumido por: {{ $plantonista }}
@if ($plantonistaSaida !== null)
Saindo de serviço: {{ $plantonistaSaida }}
@endif

Viaturas em condições de atendimento:
Localização: {{ $localizacao }}

@foreach ($viaturas as $v)
🚐 {{ $v['prefixo'] }} - {{ $v['placa'] }}{{ $v['anotacao'] }}
⛽ Combustível: {{ $v['combustivel'] }}
📊 Hodômetro: {{ $v['hodometro'] }}
📝 Alterações: {{ $v['alteracoes'] }}
👨‍✈️ Último condutor: {{ $v['condutor'] }}

@endforeach
Contatos para abastecimento com Diesel (RMBH):
@foreach ($contatosDiesel as $contato)
{{ $contato }}
@endforeach

LINK VERIFICAÇÃO DE COMBUSTÍVEL POSTOS ORGÂNICOS. A Ferramenta possibilita a verificação dos níveis de combustíveis em cada Posto Orgânico Compartilhado-POC, em tempo real. Desta forma, tanto na Capital quanto nas DSP no interior.

{{ $linkBi }}

DTT: {{ $dtt }}
Plantão GMG: {{ $gmg }}

@if ($ocorrencias !== null)
Ocorrências ou ações de destaque do turno anterior:
{{ $ocorrencias }}
@else
Não houve.
@endif
</blade>
```

**Atencao ao fechar o bloco:** a ultima linha do arquivo NAO leva a marca
` ``` ` nem a tag `</blade>` — isso e apenas a cerca de codigo deste documento.
O arquivo termina na linha `@endif`.

- [ ] **Step 3: Escrever o teste que falha**

Criar `tests/Unit/Plantao/RelatorioPassagemServiceTest.php`. O teste compara o
texto inteiro; e ele que protege o requisito central da release.

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Plantao;

use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaSnapshot;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class RelatorioPassagemServiceTest extends TestCase
{
    private function plantaoFake(): Plantao
    {
        $plantao = new Plantao([
            'plantonista_nome' => 'Sgt Leandro',
            'plantonista_saida_nome' => 'Sgt Deivison',
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::NOTURNO->value,
            'localizacao' => 'Predio Alterosas',
            'ocorrencias_destaque' => null,
        ]);

        $a = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-2241',
            'hodometro' => 112799,
            'nivel_combustivel' => NivelCombustivel::QUARTO_3->value,
            'alteracoes' => null,
            'ultimo_condutor_nome' => 'Sgt Egidio',
            'anotacao' => 'Exclusiva Sobreaviso',
            'em_condicoes' => true,
        ]);

        $b = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-2245',
            'hodometro' => 103798,
            'nivel_combustivel' => NivelCombustivel::QUARTO_4->value,
            'alteracoes' => null,
            'ultimo_condutor_nome' => 'Sgt Mello',
            'anotacao' => null,
            'em_condicoes' => true,
        ]);

        $fora = new ViaturaSnapshot([
            'prefixo' => 'SW4',
            'placa' => 'QMV-9999',
            'hodometro' => 1,
            'nivel_combustivel' => NivelCombustivel::VAZIO->value,
            'em_condicoes' => false,
        ]);

        $plantao->setRelation('snapshots', new Collection([$a, $b, $fora]));

        return $plantao;
    }

    public function test_cabecalho_usa_o_label_curto_do_periodo(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString(
            'Serviço de Plantão (25/08/2026 - 16h às 02h)',
            $texto
        );
        $this->assertStringContainsString('Assumido por: Sgt Leandro', $texto);
        $this->assertStringContainsString('Saindo de serviço: Sgt Deivison', $texto);
    }

    public function test_anotacao_sai_entre_parenteses_e_ausente_nao_deixa_sobra(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('SW4 - QMV-2241 (Exclusiva Sobreaviso)', $texto);
        $this->assertStringContainsString("SW4 - QMV-2245\n", $texto);
    }

    public function test_alteracao_vazia_renderiza_sem_alteracoes(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Alterações: Sem alterações', $texto);
    }

    public function test_hodometro_sai_sem_separador_de_milhar(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        // O relatorio praticado hoje escreve 112799, nao 112.799.
        $this->assertStringContainsString('Hodômetro: 112799', $texto);
        $this->assertStringNotContainsString('112.799', $texto);
    }

    public function test_viatura_fora_de_condicoes_nao_entra_na_listagem(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringNotContainsString('QMV-9999', $texto);
    }

    public function test_sem_ocorrencia_renderiza_nao_houve(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Não houve.', $texto);
        $this->assertStringNotContainsString('Ocorrências ou ações de destaque', $texto);
    }

    public function test_com_ocorrencia_renderiza_o_cabecalho_do_bloco(): void
    {
        $plantao = $this->plantaoFake();
        $plantao->ocorrencias_destaque = 'COLISAO ENTRE ONIBUS E CAMINHAO.';

        $texto = app(RelatorioPassagemService::class)->renderizar($plantao);

        $this->assertStringContainsString(
            'Ocorrências ou ações de destaque do turno anterior:',
            $texto
        );
        $this->assertStringContainsString('COLISAO ENTRE ONIBUS E CAMINHAO.', $texto);
        $this->assertStringNotContainsString('Não houve.', $texto);
    }

    public function test_sem_plantonista_de_saida_omite_a_linha(): void
    {
        $plantao = $this->plantaoFake();
        $plantao->plantonista_saida_nome = null;

        $texto = app(RelatorioPassagemService::class)->renderizar($plantao);

        $this->assertStringNotContainsString('Saindo de serviço:', $texto);
    }

    public function test_rodape_traz_contatos_e_link_do_config(): void
    {
        $texto = app(RelatorioPassagemService::class)->renderizar($this->plantaoFake());

        $this->assertStringContainsString('Contatos para abastecimento com Diesel (RMBH):', $texto);
        $this->assertStringContainsString('3 BBM: 031 3490-5531', $texto);
        $this->assertStringContainsString('app.powerbi.com/view', $texto);
        $this->assertStringContainsString('DTT: saida de viaturas', $texto);
        $this->assertStringContainsString('Plantão GMG: saida de viaturas', $texto);
    }
}
```

**Nota.** O teste estende `Tests\TestCase` (nao `PHPUnit\Framework\TestCase`)
porque precisa do container para resolver `view()` e `config()`. Nao usa
`DatabaseTransactions`: os models sao construidos em memoria com
`setRelation`, sem tocar o banco.

- [ ] **Step 4: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan config:clear
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=RelatorioPassagemServiceTest
```

Esperado: FAIL com `Target class [App\Modules\Plantao\Services\RelatorioPassagemService] does not exist`.

- [ ] **Step 5: Criar o `RelatorioPassagemService`**

`app/Modules/Plantao/Services/RelatorioPassagemService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaSnapshot;

/**
 * Monta o payload e delega a formatacao a view de texto. Este service nao
 * conhece nenhum caractere de marcador: o formato vive em
 * resources/views/plantao/passagem-servico.txt.blade.php.
 */
class RelatorioPassagemService
{
    public function renderizar(Plantao $plantao): string
    {
        $rodape = config('plantao.relatorio.rodape');

        $viaturas = $plantao->snapshots
            ->filter(fn(ViaturaSnapshot $s) => (bool) $s->em_condicoes)
            ->map(fn(ViaturaSnapshot $s) => [
                'prefixo' => $s->prefixo,
                'placa' => $s->placa,
                'anotacao' => $this->formatarAnotacao($s->anotacao),
                'combustivel' => $s->nivel_combustivel?->label() ?? '',
                // Sem separador de milhar: o relatorio praticado escreve 112799.
                'hodometro' => (string) $s->hodometro,
                'alteracoes' => $this->textoOuPadrao($s->alteracoes, 'Sem alterações'),
                'condutor' => $s->ultimo_condutor_nome ?? '',
            ])
            ->values()
            ->all();

        return view('plantao.passagem-servico', [
            'data' => $plantao->data?->format('d/m/Y') ?? '',
            'periodo' => $plantao->periodo?->labelCurto() ?? '',
            'plantonista' => $plantao->plantonista_nome ?? '',
            'plantonistaSaida' => $this->nuloSeVazio($plantao->plantonista_saida_nome),
            'localizacao' => $plantao->localizacao ?? '',
            'viaturas' => $viaturas,
            'contatosDiesel' => $rodape['contatos_diesel'],
            'linkBi' => $rodape['link_bi'],
            'dtt' => $rodape['dtt'],
            'gmg' => $rodape['gmg'],
            'ocorrencias' => $this->nuloSeVazio($plantao->ocorrencias_destaque),
        ])->render();
    }

    private function formatarAnotacao(?string $anotacao): string
    {
        $texto = trim((string) $anotacao);

        return $texto === '' ? '' : " ({$texto})";
    }

    private function textoOuPadrao(?string $valor, string $padrao): string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? $padrao : $texto;
    }

    private function nuloSeVazio(?string $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
```

- [ ] **Step 6: Registro no provider — JA FEITO na Task 3**

Nao toque em `PlantaoServiceProvider.php`. O `singleton(RelatorioPassagemService::class)`
ja foi registrado na Task 3, por decisao do controlador, para permitir que esta
task rode em paralelo com as Tasks 4 e 6. Passo sem acao.

- [ ] **Step 7: Rodar o teste e ajustar o template**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan view:clear
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=RelatorioPassagemServiceTest
```

Esperado: PASS, 9 testes. Se algum falhar por linha em branco a mais ou a menos,
o culpado e o Blade: diretivas como `@foreach` e `@if` deixam a linha onde estao.
Ajustar o template usando as variantes sem quebra (`@foreach(...)` na mesma linha
do conteudo) ate o texto bater. **Nao ajustar a assercao para acomodar o
template** — a assercao e a especificacao.

- [ ] **Step 8: Commit**

```bash
git add config/plantao.php resources/views/plantao app/Modules/Plantao/Services/RelatorioPassagemService.php app/Modules/Plantao/PlantaoServiceProvider.php tests/Unit/Plantao/RelatorioPassagemServiceTest.php
git commit -m "✨ feat(plantao): relatorio de passagem de servico no formato praticado"
```

---

### Task 12: Rota do relatorio, painel e botao Copiar para WhatsApp

**Files:**
- Create: `app/Modules/Plantao/Controllers/RelatorioPassagemController.php`
- Create: `resources/js/Composables/useCopiarTexto.js`
- Create: `resources/js/Components/Organisms/Plantao/RelatorioPassagemPanel.vue`
- Modify: `routes/modules/plantao.php`
- Modify: `resources/js/Templates/Plantao/PlantaoIndexTemplate.vue`
- Test: `tests/Feature/Plantao/PassagemServicoTest.php` (ampliar)

**Interfaces:**
- Consumes: `RelatorioPassagemService` (Task 11).
- Produces: rota `plantao.passagem.relatorio` (`GET /plantao/{plantao}/relatorio`), resposta JSON `{ texto: string }`. Composable `useCopiarTexto()` devolve `{ copiar, copiado }`.

- [ ] **Step 1: Escrever o teste que falha**

Adicionar a `tests/Feature/Plantao/PassagemServicoTest.php`:

```php
    public function test_rota_do_relatorio_devolve_o_texto(): void
    {
        $viatura = Viatura::factory()->create([
            'prefixo' => 'SW4',
            'placa' => 'QMV-2241',
            'hodometro_atual' => 112_799,
        ]);
        $deivison = User::factory()->create(['name' => 'Sgt Deivison']);

        $turno = $this->passagem()->abrirTurno([
            'plantonista_id' => $deivison->id,
            'data' => '2026-08-25',
            'periodo' => PeriodoPlantao::NOTURNO->value,
        ]);
        $this->passagem()->encerrar($turno->id, [[
            'viatura_id' => $viatura->id,
            'hodometro' => 112_799,
            'nivel_combustivel' => NivelCombustivel::QUARTO_3->value,
            'em_condicoes' => true,
        ]]);

        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'plantao.passagem.relatorio',
            'guard_name' => 'web',
        ]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $deivison->givePermissionTo('plantao.passagem.relatorio');

        $resposta = $this->actingAs($deivison)
            ->getJson(route('plantao.passagem.relatorio', $turno))
            ->assertOk();

        $texto = $resposta->json('texto');

        $this->assertStringContainsString('Assumido por: Sgt Deivison', $texto);
        $this->assertStringContainsString('QMV-2241', $texto);
        $this->assertStringContainsString('Hodômetro: 112799', $texto);
    }

    public function test_rota_do_relatorio_exige_permissao(): void
    {
        $turno = Plantao::factory()->pendenteAceite()->create();

        $this->actingAs(User::factory()->create())
            ->getJson(route('plantao.passagem.relatorio', $turno))
            ->assertForbidden();
    }
```

- [ ] **Step 2: Rodar e confirmar que falha**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
```

Esperado: FAIL com `Route [plantao.passagem.relatorio] not defined`.

- [ ] **Step 3: Criar o controller**

`app/Modules/Plantao/Controllers/RelatorioPassagemController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use Illuminate\Http\JsonResponse;

class RelatorioPassagemController extends Controller
{
    public function __construct(
        private readonly RelatorioPassagemService $relatorioService
    ) {
    }

    public function __invoke(Plantao $plantao): JsonResponse
    {
        $plantao->load('snapshots');

        return response()->json([
            'texto' => $this->relatorioService->renderizar($plantao),
        ]);
    }
}
```

- [ ] **Step 4: Registrar a rota**

Em `routes/modules/plantao.php`, junto das outras rotas de passagem:

```php
    Route::get('/{plantao}/relatorio', RelatorioPassagemController::class)
        ->name('passagem.relatorio')
        ->middleware('can:plantao.passagem.relatorio');
```

**Atencao a ordem.** Esta rota e `GET /plantao/{plantao}/relatorio`. Ela precisa
vir depois de `/export` e `/noticias` e depois do grupo `/viaturas`, senao o
Laravel casa `viaturas` como `{plantao}`.

- [ ] **Step 5: Criar o composable de copia**

`resources/js/Composables/useCopiarTexto.js`:

```js
import { ref } from 'vue';

/**
 * Copia texto para a area de transferencia.
 *
 * A API moderna exige contexto seguro (HTTPS ou localhost). Em rede interna
 * sobre HTTP simples ela nao existe, por isso o fallback com textarea oculta.
 */
export function useCopiarTexto() {
  const copiado = ref(false);

  const copiarViaTextarea = (texto) => {
    const area = document.createElement('textarea');
    area.value = texto;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '-9999px';
    document.body.appendChild(area);
    area.select();

    let ok = false;
    try {
      ok = document.execCommand('copy');
    } catch {
      ok = false;
    }

    document.body.removeChild(area);
    return ok;
  };

  const copiar = async (texto) => {
    let ok = false;

    if (navigator.clipboard && window.isSecureContext) {
      try {
        await navigator.clipboard.writeText(texto);
        ok = true;
      } catch {
        ok = copiarViaTextarea(texto);
      }
    } else {
      ok = copiarViaTextarea(texto);
    }

    if (ok) {
      copiado.value = true;
      setTimeout(() => {
        copiado.value = false;
      }, 2500);
    }

    return ok;
  };

  return { copiar, copiado };
}
```

- [ ] **Step 6: Criar o `RelatorioPassagemPanel`**

`resources/js/Components/Organisms/Plantao/RelatorioPassagemPanel.vue`:

```vue
<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { useCopiarTexto } from '@/Composables/useCopiarTexto';
import { ClipboardDocumentIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
  plantaoId: {
    type: Number,
    required: true,
  },
});

const texto = ref('');
const carregando = ref(false);
const erro = ref('');
const { copiar, copiado } = useCopiarTexto();

const carregar = async () => {
  if (texto.value !== '' || carregando.value) return;

  carregando.value = true;
  erro.value = '';

  try {
    const { data } = await axios.get(route('plantao.passagem.relatorio', props.plantaoId));
    texto.value = data.texto;
  } catch {
    erro.value = 'Nao foi possivel carregar o relatorio.';
  } finally {
    carregando.value = false;
  }
};

const handleCopiar = async () => {
  await carregar();
  if (texto.value !== '') {
    await copiar(texto.value);
  }
};
</script>

<template>
  <CollapsibleSection
    namespace="plantao"
    section-key="relatorio-passagem"
    title="Relatorio de passagem de servico"
    subtitle="Texto pronto para colar no grupo"
    :icon="DocumentTextIcon"
    @open="carregar"
  >
    <div class="space-y-3">
      <p v-if="erro" class="text-sm text-red-600 dark:text-red-400">
        {{ erro }}
      </p>

      <pre
        v-if="texto"
        class="max-h-96 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs leading-relaxed text-gray-800 dark:bg-gray-900 dark:text-gray-200"
      >{{ texto }}</pre>

      <p v-else-if="carregando" class="text-sm text-gray-500 dark:text-gray-400">
        Montando o relatorio...
      </p>

      <Button
        variant="primary"
        size="md"
        :icon="ClipboardDocumentIcon"
        icon-position="left"
        :disabled="carregando"
        @click="handleCopiar"
      >
        {{ copiado ? 'Copiado' : 'Copiar para WhatsApp' }}
      </Button>
    </div>
  </CollapsibleSection>
</template>
```

**Nota.** Se o `CollapsibleSection` atual nao emitir `open`, chamar `carregar()`
no `onMounted` do painel em vez de no evento. Conferir a assinatura do
componente antes de escrever esta linha.

- [ ] **Step 7: Ligar o painel na tela**

Em `PlantaoIndexTemplate.vue`, renderizar o painel abaixo dos stat cards:

```vue
      <RelatorioPassagemPanel
        v-if="canRelatorio && (turnoPendente?.id || turnoAtivo?.id)"
        :plantao-id="turnoPendente?.id ?? turnoAtivo.id"
      />
```

- [ ] **Step 8: Rodar tudo**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan octane:reload
npm run prebuild
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=PassagemServicoTest
npm run build
```

Esperado: PASS, 20 testes; build sem erro.

- [ ] **Step 9: Verificacao manual do texto**

No navegador, abrir `/plantao`, expandir o painel de relatorio e comparar o texto
gerado, lado a lado, com um relatorio real colado do grupo. Conferir
especificamente: quebras de linha entre blocos de viatura, ausencia de linha
sobrando quando a anotacao e vazia, e o bloco final (`Nao houve.` ou o cabecalho
de ocorrencias). Clicar em **Copiar para WhatsApp** e colar num editor para
confirmar que o conteudo da area de transferencia esta identico.

- [ ] **Step 10: Commit**

```bash
git add app/Modules/Plantao/Controllers/RelatorioPassagemController.php routes/modules/plantao.php resources/js/Composables/useCopiarTexto.js resources/js/Components/Organisms/Plantao/RelatorioPassagemPanel.vue resources/js/Templates/Plantao/PlantaoIndexTemplate.vue resources/js/ziggy.js tests/Feature/Plantao/PassagemServicoTest.php
git commit -m "✨ feat(plantao): painel do relatorio com copia para WhatsApp"
```

---

### Task 13: Fechamento — suite completa, export e revisao

**Files:**
- Modify: `app/Modules/Plantao/Controllers/PlantaoExportController.php`
- Modify: `docs/superpowers/specs/2026-08-26-plantao-frota-passagem-servico-design.md`

**Interfaces:**
- Consumes: tudo das fases anteriores.
- Produces: nada.

- [ ] **Step 1: Ampliar o export CSV com os campos novos**

Em `PlantaoExportController`, adicionar aos `$headers` e ao `$mapper` as colunas
`Saindo de servico`, `Localizacao`, `Encerrado em`, `Aceito em`, `Divergencia`.
O `$mapper` recebe o model, entao usar:

```php
                $plantao->plantonista_saida_nome ?? '',
                $plantao->localizacao ?? '',
                $plantao->encerrado_em?->format('d/m/Y H:i') ?? '',
                $plantao->aceito_em?->format('d/m/Y H:i') ?? '',
                $plantao->divergencia ?? '',
```

- [ ] **Step 2: Rodar a suite inteira do modulo**

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test --filter=Plantao
```

Esperado: PASS em todos. Contagem alvo: 6 (NivelCombustivel) + 9 (Relatorio) +
10 (ViaturaCrud) + 10 (Movimentacao) + 20 (PassagemServico) = 55 testes.

- [ ] **Step 3: Rodar a suite completa para checar regressao**

O label do `PeriodoPlantao` mudou e `getStatistics()` do `PlantaoService` trocou
uma chave. Rodar tudo:

```bash
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql artisan test
```

Esperado: nenhuma falha nova em relacao ao estado da branch antes desta release.
Se algo falhar, corrigir a causa — nao a assercao, salvo quando a assercao
carregava o label errado dos turnos.

- [ ] **Step 4: Lint dos arquivos PHP novos**

```bash
for f in $(git diff --name-only origin/dev...HEAD | grep '\.php$'); do
  docker exec newsdc_frankenphp_local php -l "/app/${f#SDC/}" || echo "FALHOU: $f"
done
```

Esperado: `No syntax errors detected` em todos.

- [ ] **Step 5: Verificar a Regra de Ouro 2**

Confirmar que nenhum emoji entrou em codigo. O unico arquivo que deve aparecer
nesta busca e o template Blade:

```bash
git diff --name-only origin/dev...HEAD | grep -E '\.(php|vue|js)$' | while read -r f; do
  if grep -qP '[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]' "$f" 2>/dev/null; then
    echo "EMOJI EM CODIGO: $f"
  fi
done
```

Esperado: nenhuma saida. O `.blade.php` do relatorio nao aparece porque o filtro
pega `.php`, e ele termina em `.blade.php` — ajustar o grep para excluir
`resources/views` se ele aparecer.

- [ ] **Step 6: Atualizar o status do spec**

No cabecalho de `docs/superpowers/specs/2026-08-26-plantao-frota-passagem-servico-design.md`,
trocar a linha de status por:

```markdown
**Status:** Implementado (Release 1 — subsistemas A e B)
```

- [ ] **Step 7: Commit final**

```bash
git add app/Modules/Plantao/Controllers/PlantaoExportController.php docs/superpowers/specs/2026-08-26-plantao-frota-passagem-servico-design.md
git commit -m "✨ feat(plantao): export com campos de passagem e fechamento da release"
```

- [ ] **Step 8: Revisao de codigo**

Invocar a skill `superpowers:requesting-code-review` antes de abrir o PR.

---

## Notas de execucao

**Pontos de parada seguros.** Fim da Fase 1 (frota cadastravel), fim da Fase 2
(estado mantido por movimentacao), fim da Fase 3 (ritual completo na interface).
Cada um deixa o sistema consistente e util. A Fase 4 e a que fecha o proposito
da release, mas as tres primeiras nao viram divida se pararem ali.

**Octane.** Depois de qualquer mudanca em `.php`, `octane:reload` (~1s). NAO
reiniciar o container: custa ~3min por causa do `chmod -R` sobre 17k arquivos.
Reinicio so para `.env`, `config/` ou extensao PHP. Mudanca em `config/plantao.php`
e `config/permissions.php` pede `config:clear`.

**Migrations.** Se durante a execucao alguma coluna precisar de ajuste, editar a
migration original das Tasks 2 e rodar `migrate:rollback --step=4` mais `migrate`.
Nao criar migration nova (regra de ouro 9). Isso vale enquanto a branch nao for
mergeada; depois do merge, ajuste vira migration propria.

**Ordem das rotas.** O grupo `plantao` tem rotas estaticas (`/export`,
`/noticias`), um subgrupo (`/viaturas`), rotas parametrizadas
(`/{plantao}/encerrar`, `/{plantao}/aceitar`, `/{plantao}/relatorio`) e a raiz
(`/`). Manter exatamente essa ordem: uma parametrizada acima do subgrupo captura
`viaturas` como `{plantao}` e a tela da frota quebra com 404 silencioso.
