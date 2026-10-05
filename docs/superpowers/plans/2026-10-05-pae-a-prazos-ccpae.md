# PAE Subprojeto A -- Prazos, fluxo e CCPAE -- Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** a CEDEC vê, em cada protocolo PAE, os prazos legais da Resolução GMG 83/2024 (Arts. 4, 5, 7, 9 e 11), calculados num lugar só. O CCPAE passa a ser emitido com registro e vigência, e o 3º ciclo de notificação vencido sinaliza em vez de suspender.

**Architecture:**
- **Cálculo:** classes puras (padrão Tdap `Support/VigenciaAta`), com o resultado gravado em `pae_protocolos.limite_analise`. O `PaePrazoService` recalcula nos 5 momentos de mudança.
- **Máquina de estados:** sai do service para um `PaeProtocoloWorkflow` com guards (padrão Demandas `Domain/Workflows` + `Guards`). A origem da transição viaja como objeto de valor, sem estado no container, o que é seguro no Octane.
- **Tela:** a listagem lê a situação do prazo pronta do servidor. O modal de histórico ganha a aba Prazos e ações na aba Notificações. O modal de CCPAE vira formulário.

**Tech Stack:** Laravel 12, PHP 8.4, Carbon 3.11 (`diffInDays` com sinal), PostgreSQL 18, PHPUnit, Vue 3 + Inertia, Tailwind.

**Spec:** `docs/superpowers/specs/2026-10-02-pae-a-prazos-ccpae-design.md`

## Global Constraints

- Todo arquivo PHP novo começa com `declare(strict_types=1);`.
- Nenhum emoji no código (Regra 2).
- Logs de depuração são removidos antes do commit (Regra 6).
- Nenhum arquivo de teste entra no commit, nem os novos nem os existentes alterados (Regra 10 e decisão da CEDEC de 2026-10-02). `tests/` é gitignored, mas `tests/Feature/Pae/*` já está versionado. **Nunca usar `git add -A` nem `git add .`**: adicionar só os caminhos listados em cada commit.
- Commits seguem gitmoji (`<emoji> tipo(pae): descricao em pt-BR`), sem trailer `Co-Authored-By`. Um commit por fase, agrupando a mudança completa (Regra 12).
- Migrations: uma só para o subprojeto, `SDC/database/migrations/2026_10_02_120000_ajusta_pae_prazos_ccpae.php`. Toda mudança de schema do A é consolidada nela (Regra 9). As migrations de criação já aplicadas não são editadas.
- PostgreSQL é o único banco suportado.
- Controller fino, validação em FormRequest, regra no Service (`.claude/skills/backend/03 - Padrao Pratico/01 - Padrao.md`). Não criar UseCase nem Repository no backend.
- Os services do PAE são singleton (`PaeServiceProvider`). Nenhum estado de request pode ficar em propriedade de service, workflow ou guard.
- Os 4 eventos do Outbox (`pae.protocolo.enviado`, `pae.formulario.validado`, `pae.revisao.aceita`, `pae.parecer.concluido`) mantêm nome, versão e campos.
- Prazos: 10 dias úteis (Art. 7) segunda a sexta menos os feriados de `config/feriados.php`; 300 dias corridos (Art. 9) com pausa durante a diligência; 30 dias (Art. 11) mais dilações aprovadas; vigência de 3 anos (LO + 3 para empreendimento novo, emissão + 3 para os demais).
- Frontend: sem rolagem horizontal em 375px e 840px, dark mode por classe (`html.dark`), apenas utilitários Tailwind, decisão de layout mobile via `useMobile` (`.claude/skills/frontend/SKILL.MD`).
- Todos os comandos abaixo partem de `WT=/c/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+pae-gmg83`. PHP e testes rodam pelo script `$PT` da Tarefa 0, nunca pelo PHP do host.

## Review Focus

1. **Devolutiva com data anterior à emissão da notificação:** é recusada, e um intervalo invertido nunca subtrai dias da pausa. Testado na Tarefa 2 (`test_intervalo_invertido_e_ignorado`) e na Tarefa 6 (`test_devolutiva_anterior_a_emissao_e_recusada`).
2. **CCPAE emitido duas vezes** (duplo clique ou protocolo que já está em CCPAE): a segunda tentativa é recusada, sem segundo registro em `pae_ccpae`. Testado na Tarefa 7 (`test_emitir_duas_vezes_e_recusado`).
3. **LO em 29/02:** o vencimento cai em 28/02, sem estourar para março. Testado na Tarefa 2 (`test_lo_em_29_de_fevereiro`).
4. **Comando diário rodando duas vezes no mesmo dia** com o 3º ciclo vencido: um único evento `ciclos_esgotados` na timeline e um único aviso. Testado na Tarefa 6 (`test_ciclos_esgotados_nao_duplica`).
5. **Feriado no meio dos 10 dias úteis e virada de ano:** o limite pula o feriado e o fim de semana. Testado na Tarefa 1 (`test_pula_feriado_e_fim_de_semana`, `test_virada_de_ano`).

---

## Fase 0 -- Ambiente

### Task 0: Preparar a worktree para rodar testes e build

**Files:**
- Create (fora do repo): `C:/Users/X24679~1/AppData/Local/Temp/claude/c--Users-x24679188-Documents-Github-gestaocedec/cd5c1e55-eea1-4572-9ea1-41ccef2e6ef8/scratchpad/pt.sh`
- Create (não versionados): `SDC/.env`, `SDC/vendor/`, `SDC/bootstrap/cache/`, `SDC/storage/...`, `SDC/public/build/`
- Create (banco): `sdc_pae_gmg83` no container `newsdc_dev_db`

**Interfaces:**
- Produces: `$PT <args>` executa `php <args>` num container descartável com o código da worktree, contra o banco `sdc_pae_gmg83`. Toda tarefa usa `$PT vendor/bin/phpunit <caminho>`.

- [ ] **Step 1: Copiar `.env`, criar diretórios e copiar o build**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+pae-gmg83
MAIN=/c/Users/x24679188/Documents/Github/NewSDC
cp "$MAIN/SDC/.env" "$WT/SDC/.env"
mkdir -p "$WT/SDC/bootstrap/cache" "$WT/SDC/storage/framework/cache" "$WT/SDC/storage/framework/views" \
         "$WT/SDC/storage/framework/testing" "$WT/SDC/storage/framework/sessions" "$WT/SDC/storage/logs"
cp -r "$MAIN/SDC/public/build" "$WT/SDC/public/build"
```

- [ ] **Step 2: Instalar o vendor pelo container (com dev deps)**

```bash
WT_WIN=$(cygpath -m "$WT/SDC")
MSYS_NO_PATHCONV=1 docker run --rm -v "$WT_WIN:/app" -w /app -e COMPOSER_ALLOW_SUPERUSER=1 \
  -e COMPOSER_PROCESS_TIMEOUT=0 newsdc-swoole-dev:latest \
  composer install --ignore-platform-reqs --no-interaction > /c/tmp/pae-gmg83-composer.log 2>&1
test -f "$WT/SDC/vendor/bin/phpunit" && echo VENDOR_OK
```

Expected: `VENDOR_OK`. Sem a flag de timeout, a descompactação morre em silêncio no meio, então sempre conferir o `vendor/bin/phpunit`.

- [ ] **Step 3: Criar o banco de teste isolado**

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres -c "CREATE DATABASE sdc_pae_gmg83 TEMPLATE template_postgis OWNER sdc"
```

Expected: `CREATE DATABASE`. Nunca usar `sdc` (dado de dev compartilhado com o uso real) nem `sdc_test` (compartilhado com outras sessões).

- [ ] **Step 4: Criar o script `pt.sh`**

```bash
cat > "C:/Users/X24679~1/AppData/Local/Temp/claude/c--Users-x24679188-Documents-Github-gestaocedec/cd5c1e55-eea1-4572-9ea1-41ccef2e6ef8/scratchpad/pt.sh" <<'EOF'
#!/usr/bin/env bash
# Roda "php <args>" num container descartavel com o codigo da worktree feat/pae-gmg83.
WT=/c/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+pae-gmg83/SDC
WT_WIN=$(cygpath -m "$WT")
KEY=$(grep '^APP_KEY=' "$WT/.env" | cut -d= -f2-)
MSYS_NO_PATHCONV=1 docker run --rm --network newsdc-dev_default -v "$WT_WIN:/app" -w /app \
  -e APP_ENV=testing -e APP_KEY="$KEY" \
  -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 -e DB_DATABASE=sdc_pae_gmg83 \
  -e DB_USERNAME=sdc -e DB_PASSWORD=secret \
  -e APP_CONFIG_CACHE=/app/bootstrap/cache/nao-existe-config.php \
  newsdc-swoole-dev:latest php "$@"
EOF
PT="bash C:/Users/X24679~1/AppData/Local/Temp/claude/c--Users-x24679188-Documents-Github-gestaocedec/cd5c1e55-eea1-4572-9ea1-41ccef2e6ef8/scratchpad/pt.sh"
```

- [ ] **Step 5: Migrar o banco isolado e registrar a linha de base da suíte PAE**

```bash
$PT artisan migrate:fresh --force > /c/tmp/pae-gmg83-migrate.log 2>&1; tail -3 /c/tmp/pae-gmg83-migrate.log
$PT vendor/bin/phpunit tests/Feature/Pae > /c/tmp/pae-gmg83-baseline.log 2>&1; tail -5 /c/tmp/pae-gmg83-baseline.log
```

Expected: migrações aplicadas e a suíte PAE verde (em banco sem seed de municípios, o PAE passa). Anotar o total de testes. Qualquer falha aqui é pré-existente e precisa ser registrada antes de continuar.

- [ ] **Step 6: Instalar as dependências de frontend no host**

```bash
cd "$WT/SDC" && npm ci > /c/tmp/pae-gmg83-npm.log 2>&1; npx vite build > /c/tmp/pae-gmg83-build.log 2>&1; tail -3 /c/tmp/pae-gmg83-build.log
```

Expected: build concluído. Instalar e buildar sempre no mesmo ambiente (host): binário nativo do Windows não roda no container alpine.

Sem commit nesta tarefa: nada aqui é versionado.

---

## Fase 1 -- Cálculo e dados

### Task 1: Calendário de dias úteis compartilhado

**Files:**
- Create: `SDC/app/Support/Calendario/CalendarioDiasUteis.php`
- Create: `SDC/config/feriados.php`
- Test: `SDC/tests/Unit/Support/CalendarioDiasUteisTest.php`

**Interfaces:**
- Produces:
  - `new CalendarioDiasUteis(iterable $feriados)`, com datas `Y-m-d`
  - `CalendarioDiasUteis::padrao(): self`, que lê `config('feriados.datas')`
  - `ehDiaUtil(CarbonInterface $data): bool`
  - `adicionarDiasUteis(CarbonInterface $inicio, int $dias): CarbonImmutable`

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Calendario\CalendarioDiasUteis;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CalendarioDiasUteisTest extends TestCase
{
    public function test_fim_de_semana_nao_e_dia_util(): void
    {
        $cal = new CalendarioDiasUteis([]);

        $this->assertFalse($cal->ehDiaUtil(CarbonImmutable::parse('2026-10-03'))); // sabado
        $this->assertFalse($cal->ehDiaUtil(CarbonImmutable::parse('2026-10-04'))); // domingo
        $this->assertTrue($cal->ehDiaUtil(CarbonImmutable::parse('2026-10-05')));  // segunda
    }

    public function test_feriado_nao_e_dia_util(): void
    {
        $cal = new CalendarioDiasUteis(['2026-10-12']);

        $this->assertFalse($cal->ehDiaUtil(CarbonImmutable::parse('2026-10-12')));
    }

    public function test_pula_feriado_e_fim_de_semana(): void
    {
        // Sexta 09/10/2026 + 2 dias uteis: pula sab, dom e o feriado de segunda 12/10.
        $cal = new CalendarioDiasUteis(['2026-10-12']);

        $this->assertSame('2026-10-14', $cal->adicionarDiasUteis(CarbonImmutable::parse('2026-10-09'), 2)->toDateString());
    }

    public function test_virada_de_ano(): void
    {
        // Quarta 30/12/2026 + 3 dias uteis: 31/12 (qui), pula 01/01 (feriado), sab e dom -> 04/01 e 05/01.
        $cal = new CalendarioDiasUteis(['2027-01-01']);

        $this->assertSame('2027-01-05', $cal->adicionarDiasUteis(CarbonImmutable::parse('2026-12-30'), 3)->toDateString());
    }

    public function test_zero_dias_devolve_o_proprio_dia(): void
    {
        $cal = new CalendarioDiasUteis([]);

        $this->assertSame('2026-10-05', $cal->adicionarDiasUteis(CarbonImmutable::parse('2026-10-05 15:30'), 0)->toDateString());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Unit/Support/CalendarioDiasUteisTest.php`
Expected: FAIL, `Class "App\Support\Calendario\CalendarioDiasUteis" not found`

- [ ] **Step 3: Implementar a classe e a configuração**

`SDC/app/Support/Calendario/CalendarioDiasUteis.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Calendario;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Contagem de dias uteis: segunda a sexta, menos os feriados configurados.
 *
 * Compartilhada entre modulos. Os feriados vem de config/feriados.php (datas
 * fixas e moveis por ano), nao de tabela: a lista muda uma vez por ano e nao
 * justifica CRUD. Sem estado mutavel, entao pode ser singleton no Octane.
 */
final class CalendarioDiasUteis
{
    /** @var array<string, true> */
    private readonly array $feriados;

    /**
     * @param  iterable<string>  $feriados  Datas no formato Y-m-d
     */
    public function __construct(iterable $feriados = [])
    {
        $mapa = [];
        foreach ($feriados as $data) {
            $mapa[(string) $data] = true;
        }
        $this->feriados = $mapa;
    }

    public static function padrao(): self
    {
        $datas = [];
        foreach ((array) config('feriados.datas', []) as $doAno) {
            foreach (array_keys((array) $doAno) as $data) {
                $datas[] = (string) $data;
            }
        }

        return new self($datas);
    }

    public function ehDiaUtil(CarbonInterface $data): bool
    {
        return ! $data->isWeekend() && ! isset($this->feriados[$data->format('Y-m-d')]);
    }

    /**
     * Soma dias uteis a partir do dia seguinte ao inicio (o dia do evento nao conta).
     */
    public function adicionarDiasUteis(CarbonInterface $inicio, int $dias): CarbonImmutable
    {
        $data = CarbonImmutable::instance($inicio)->startOfDay();
        $contados = 0;

        while ($contados < $dias) {
            $data = $data->addDay();
            if ($this->ehDiaUtil($data)) {
                $contados++;
            }
        }

        return $data;
    }
}
```

`SDC/config/feriados.php`:

```php
<?php

/*
 * Feriados que suspendem o expediente da CEDEC (sede em Belo Horizonte):
 * nacionais, estaduais de MG, municipais de BH e o Carnaval (ponto facultativo
 * do Estado). Usado por App\Support\Calendario\CalendarioDiasUteis.
 *
 * ATUALIZAR TODO ANO. Carnaval (Pascoa - 47), Sexta-feira Santa (Pascoa - 2) e
 * Corpus Christi (Pascoa + 60) sao moveis. Ano ausente faz a contagem de dias
 * uteis tratar o feriado como dia util.
 */
return [
    'datas' => [
        '2025' => [
            '2025-01-01' => 'Confraternizacao Universal',
            '2025-03-03' => 'Carnaval',
            '2025-03-04' => 'Carnaval',
            '2025-04-18' => 'Sexta-feira Santa',
            '2025-04-21' => 'Tiradentes',
            '2025-05-01' => 'Dia do Trabalho',
            '2025-06-19' => 'Corpus Christi (BH)',
            '2025-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2025-09-07' => 'Independencia',
            '2025-10-12' => 'Nossa Senhora Aparecida',
            '2025-11-02' => 'Finados',
            '2025-11-15' => 'Proclamacao da Republica',
            '2025-11-20' => 'Consciencia Negra',
            '2025-12-08' => 'Imaculada Conceicao (BH)',
            '2025-12-25' => 'Natal',
        ],
        '2026' => [
            '2026-01-01' => 'Confraternizacao Universal',
            '2026-02-16' => 'Carnaval',
            '2026-02-17' => 'Carnaval',
            '2026-04-03' => 'Sexta-feira Santa',
            '2026-04-21' => 'Tiradentes',
            '2026-05-01' => 'Dia do Trabalho',
            '2026-06-04' => 'Corpus Christi (BH)',
            '2026-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2026-09-07' => 'Independencia',
            '2026-10-12' => 'Nossa Senhora Aparecida',
            '2026-11-02' => 'Finados',
            '2026-11-15' => 'Proclamacao da Republica',
            '2026-11-20' => 'Consciencia Negra',
            '2026-12-08' => 'Imaculada Conceicao (BH)',
            '2026-12-25' => 'Natal',
        ],
        '2027' => [
            '2027-01-01' => 'Confraternizacao Universal',
            '2027-02-08' => 'Carnaval',
            '2027-02-09' => 'Carnaval',
            '2027-03-26' => 'Sexta-feira Santa',
            '2027-04-21' => 'Tiradentes',
            '2027-05-01' => 'Dia do Trabalho',
            '2027-05-27' => 'Corpus Christi (BH)',
            '2027-08-15' => 'Assuncao de Nossa Senhora (BH)',
            '2027-09-07' => 'Independencia',
            '2027-10-12' => 'Nossa Senhora Aparecida',
            '2027-11-02' => 'Finados',
            '2027-11-15' => 'Proclamacao da Republica',
            '2027-11-20' => 'Consciencia Negra',
            '2027-12-08' => 'Imaculada Conceicao (BH)',
            '2027-12-25' => 'Natal',
        ],
    ],
];
```

- [ ] **Step 4: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Unit/Support/CalendarioDiasUteisTest.php`
Expected: PASS (5 testes)

Commit no fim da Fase 1 (Tarefa 3).

### Task 2: Classes puras de prazo do PAE

**Files:**
- Create:
  - `SDC/app/Modules/Pae/Support/Datas.php`
  - `SDC/app/Modules/Pae/Enums/SituacaoPrazo.php`
  - `SDC/app/Modules/Pae/Support/PrazoProtocolo.php`
  - `SDC/app/Modules/Pae/Support/PrazoNotificacao.php`
  - `SDC/app/Modules/Pae/Support/PrazoAnalise.php`
  - `SDC/app/Modules/Pae/Support/VigenciaCcpae.php`
- Test:
  - `SDC/tests/Unit/Pae/PrazoProtocoloTest.php`
  - `SDC/tests/Unit/Pae/PrazoNotificacaoTest.php`
  - `SDC/tests/Unit/Pae/PrazoAnaliseTest.php`
  - `SDC/tests/Unit/Pae/VigenciaCcpaeTest.php`

**Interfaces:**
- Consumes: `CalendarioDiasUteis` (Tarefa 1)
- Produces:
  - `Datas::dia(mixed): ?CarbonImmutable` e `Datas::hoje(?CarbonInterface): CarbonImmutable`
  - `SituacaoPrazo`: `OK='ok'`, `PROXIMO='proximo'`, `VENCIDO='vencido'`, `PAUSADO='pausado'`, `SEM_DATA='sem_data'`
  - `PrazoProtocolo`:
    - `DIAS_UTEIS = 10`
    - `limite(mixed $feam, CalendarioDiasUteis): ?CarbonImmutable`
    - `foraDoPrazo(mixed $feam, mixed $entrada, CalendarioDiasUteis): bool`
  - `PrazoNotificacao`:
    - `PRAZO_DIAS = 30`
    - `vencimento(mixed $dtNotificacao, int $diasDilacao = 0): CarbonImmutable`
    - `vencida(mixed $dtNotificacao, int $diasDilacao, mixed $dtDevolutiva, ?CarbonInterface $hoje = null): bool`
  - `PrazoAnalise`:
    - `PRAZO_DIAS = 300`, `JANELA_PROXIMO_DIAS = 10`, `STATUS_ENCERRADOS`
    - `intervalosDeNotificacoes(array): array`
    - `diasPausados(array, ?CarbonInterface): int`
    - `estaPausado(array): bool`
    - `limite(mixed $feam, array $intervalos, ?CarbonInterface): ?CarbonImmutable`
    - `situacao(PaeProtocoloStatus, mixed $limite, bool $pausado, ?CarbonInterface): SituacaoPrazo`
  - `VigenciaCcpae`:
    - `ANOS = 3`
    - `vencimento(mixed $dtEmissao, bool $empreendimentoNovo, mixed $dtLicencaOperacao): CarbonImmutable`

- [ ] **Step 1: Escrever os testes que falham**

`SDC/tests/Unit/Pae/PrazoProtocoloTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae;

use App\Modules\Pae\Support\PrazoProtocolo;
use App\Support\Calendario\CalendarioDiasUteis;
use PHPUnit\Framework\TestCase;

class PrazoProtocoloTest extends TestCase
{
    public function test_limite_e_dez_dias_uteis_apos_a_notificacao_da_feam(): void
    {
        // Notificacao FEAM em sexta 02/10/2026; 12/10 e feriado -> 10 dias uteis = 19/10.
        $cal = new CalendarioDiasUteis(['2026-10-12']);

        $this->assertSame('2026-10-19', PrazoProtocolo::limite('2026-10-02', $cal)->toDateString());
    }

    public function test_entrada_no_limite_esta_no_prazo(): void
    {
        $cal = new CalendarioDiasUteis(['2026-10-12']);

        $this->assertFalse(PrazoProtocolo::foraDoPrazo('2026-10-02', '2026-10-19', $cal));
        $this->assertTrue(PrazoProtocolo::foraDoPrazo('2026-10-02', '2026-10-20', $cal));
    }

    public function test_sem_data_nao_e_fora_do_prazo(): void
    {
        $cal = new CalendarioDiasUteis([]);

        $this->assertNull(PrazoProtocolo::limite(null, $cal));
        $this->assertFalse(PrazoProtocolo::foraDoPrazo(null, '2026-10-20', $cal));
        $this->assertFalse(PrazoProtocolo::foraDoPrazo('2026-10-02', null, $cal));
    }
}
```

`SDC/tests/Unit/Pae/PrazoNotificacaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae;

use App\Modules\Pae\Support\PrazoNotificacao;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PrazoNotificacaoTest extends TestCase
{
    public function test_vencimento_e_30_dias_mais_dilacao(): void
    {
        $this->assertSame('2026-10-31', PrazoNotificacao::vencimento('2026-10-01')->toDateString());
        $this->assertSame('2026-11-15', PrazoNotificacao::vencimento('2026-10-01', 15)->toDateString());
    }

    public function test_vencida_so_depois_do_vencimento_e_sem_devolutiva(): void
    {
        $hoje = CarbonImmutable::parse('2026-11-01');

        $this->assertFalse(PrazoNotificacao::vencida('2026-10-01', 0, null, CarbonImmutable::parse('2026-10-31')));
        $this->assertTrue(PrazoNotificacao::vencida('2026-10-01', 0, null, $hoje));
        $this->assertFalse(PrazoNotificacao::vencida('2026-10-01', 0, '2026-10-20', $hoje));
        $this->assertFalse(PrazoNotificacao::vencida('2026-10-01', 15, null, $hoje));
    }

    public function test_dilacao_negativa_e_ignorada(): void
    {
        $this->assertSame('2026-10-31', PrazoNotificacao::vencimento('2026-10-01', -5)->toDateString());
    }
}
```

`SDC/tests/Unit/Pae/PrazoAnaliseTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Enums\SituacaoPrazo;
use App\Modules\Pae\Support\PrazoAnalise;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PrazoAnaliseTest extends TestCase
{
    private CarbonImmutable $hoje;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoje = CarbonImmutable::parse('2026-10-05');
    }

    private function intervalos(array $notificacoes): array
    {
        return PrazoAnalise::intervalosDeNotificacoes($notificacoes);
    }

    public function test_sem_notificacao_limite_e_300_dias(): void
    {
        $this->assertSame('2027-07-28', PrazoAnalise::limite('2026-10-01', [], $this->hoje)->toDateString());
    }

    public function test_sem_data_da_feam_nao_ha_limite(): void
    {
        $this->assertNull(PrazoAnalise::limite(null, [], $this->hoje));
    }

    public function test_notificacao_fechada_soma_os_dias_da_diligencia(): void
    {
        $i = $this->intervalos([['dt_notificacao' => '2026-02-01', 'dt_devolutiva' => '2026-02-21']]);

        $this->assertSame(20, PrazoAnalise::diasPausados($i, $this->hoje));
        $this->assertSame('2026-11-17', PrazoAnalise::limite('2026-01-01', $i, $this->hoje)->toDateString());
    }

    public function test_notificacao_aberta_pausa_ate_hoje(): void
    {
        $i = $this->intervalos([['dt_notificacao' => '2026-09-25', 'dt_devolutiva' => null]]);

        $this->assertSame(10, PrazoAnalise::diasPausados($i, $this->hoje));
        $this->assertTrue(PrazoAnalise::estaPausado($i));
    }

    public function test_renovacao_automatica_nao_conta_dias_em_dobro(): void
    {
        // n1 aberta e superada por n2 (renovacao automatica), n2 com devolutiva.
        $i = $this->intervalos([
            ['dt_notificacao' => '2026-01-01', 'dt_devolutiva' => null],
            ['dt_notificacao' => '2026-02-01', 'dt_devolutiva' => '2026-02-11'],
        ]);

        $this->assertSame(41, PrazoAnalise::diasPausados($i, $this->hoje)); // 01/01 -> 11/02
        $this->assertFalse(PrazoAnalise::estaPausado($i));
    }

    public function test_intervalos_separados_somam(): void
    {
        $i = $this->intervalos([
            ['dt_notificacao' => '2026-03-10', 'dt_devolutiva' => '2026-03-20'],
            ['dt_notificacao' => '2026-01-01', 'dt_devolutiva' => '2026-01-11'],
        ]);

        $this->assertSame(20, PrazoAnalise::diasPausados($i, $this->hoje));
    }

    public function test_intervalo_invertido_e_ignorado(): void
    {
        $i = $this->intervalos([['dt_notificacao' => '2026-03-10', 'dt_devolutiva' => '2026-03-01']]);

        $this->assertSame(0, PrazoAnalise::diasPausados($i, $this->hoje));
    }

    public function test_situacao(): void
    {
        $andamento = PaeProtocoloStatus::NOTIFICACAO;

        $this->assertSame(SituacaoPrazo::SEM_DATA, PrazoAnalise::situacao($andamento, null, false, $this->hoje));
        $this->assertSame(SituacaoPrazo::VENCIDO, PrazoAnalise::situacao($andamento, '2026-10-04', true, $this->hoje));
        $this->assertSame(SituacaoPrazo::PAUSADO, PrazoAnalise::situacao($andamento, '2026-12-01', true, $this->hoje));
        $this->assertSame(SituacaoPrazo::PROXIMO, PrazoAnalise::situacao($andamento, '2026-10-15', false, $this->hoje));
        $this->assertSame(SituacaoPrazo::OK, PrazoAnalise::situacao($andamento, '2026-10-16', false, $this->hoje));
        $this->assertSame(SituacaoPrazo::OK, PrazoAnalise::situacao(PaeProtocoloStatus::CCPAE, '2026-01-01', false, $this->hoje));
    }
}
```

`SDC/tests/Unit/Pae/VigenciaCcpaeTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae;

use App\Modules\Pae\Support\VigenciaCcpae;
use PHPUnit\Framework\TestCase;

class VigenciaCcpaeTest extends TestCase
{
    public function test_empreendimento_com_lo_anterior_conta_da_emissao(): void
    {
        $this->assertSame('2029-10-05', VigenciaCcpae::vencimento('2026-10-05', false, '2020-01-01')->toDateString());
    }

    public function test_empreendimento_novo_conta_da_licenca_de_operacao(): void
    {
        $this->assertSame('2029-03-10', VigenciaCcpae::vencimento('2026-10-05', true, '2026-03-10')->toDateString());
    }

    public function test_empreendimento_novo_sem_lo_e_recusado(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VigenciaCcpae::vencimento('2026-10-05', true, null);
    }

    public function test_lo_em_29_de_fevereiro(): void
    {
        $this->assertSame('2031-02-28', VigenciaCcpae::vencimento('2028-05-01', true, '2028-02-29')->toDateString());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Unit/Pae`
Expected: FAIL, `Class "App\Modules\Pae\Support\PrazoProtocolo" not found` (e as demais)

- [ ] **Step 3: Implementar**

`SDC/app/Modules/Pae/Support/Datas.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Normalizacao de datas para as regras de prazo do PAE: tudo comparado por dia,
 * a meia-noite, em CarbonImmutable (nenhuma regra muta a data recebida).
 */
final class Datas
{
    public static function dia(mixed $valor): ?CarbonImmutable
    {
        if ($valor instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($valor)->startOfDay();
        }

        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function hoje(?CarbonInterface $hoje = null): CarbonImmutable
    {
        return CarbonImmutable::instance($hoje ?? CarbonImmutable::now())->startOfDay();
    }
}
```

`SDC/app/Modules/Pae/Enums/SituacaoPrazo.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Enums;

/**
 * Situacao do prazo de analise do protocolo (Art. 9), lida pela listagem.
 */
enum SituacaoPrazo: string
{
    case OK = 'ok';
    case PROXIMO = 'proximo';
    case VENCIDO = 'vencido';
    case PAUSADO = 'pausado';
    case SEM_DATA = 'sem_data';
}
```

`SDC/app/Modules/Pae/Support/PrazoProtocolo.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Support\Calendario\CalendarioDiasUteis;
use Carbon\CarbonImmutable;

/**
 * Prazo do empreendedor para protocolar a 2a secao do PAE na CEDEC: 10 dias
 * uteis contados da notificacao da FEAM (Resolucao GMG 83/2024, Art. 7, caput e §2).
 *
 * So gera aviso ("protocolado fora do prazo"); nao bloqueia a entrada.
 */
final class PrazoProtocolo
{
    public const DIAS_UTEIS = 10;

    public static function limite(mixed $dtNotificacaoFeam, CalendarioDiasUteis $calendario): ?CarbonImmutable
    {
        $feam = Datas::dia($dtNotificacaoFeam);

        return $feam === null ? null : $calendario->adicionarDiasUteis($feam, self::DIAS_UTEIS);
    }

    public static function foraDoPrazo(mixed $dtNotificacaoFeam, mixed $dtEntrada, CalendarioDiasUteis $calendario): bool
    {
        $limite = self::limite($dtNotificacaoFeam, $calendario);
        $entrada = Datas::dia($dtEntrada);

        return $limite !== null && $entrada !== null && $entrada->greaterThan($limite);
    }
}
```

`SDC/app/Modules/Pae/Support/PrazoNotificacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Prazo da diligencia: 30 dias da emissao da notificacao (Resolucao GMG 83/2024,
 * Art. 11), estendidos pelas dilacoes aprovadas pela CEDEC.
 *
 * Fonte unica dos "30 dias": antes estavam escritos em quatro pontos do
 * PaeNotificacaoService e na PaeNotificacaoResource.
 */
final class PrazoNotificacao
{
    public const PRAZO_DIAS = 30;

    public static function vencimento(mixed $dtNotificacao, int $diasDilacao = 0): CarbonImmutable
    {
        $emissao = Datas::dia($dtNotificacao)
            ?? throw new \InvalidArgumentException('Notificacao sem data de emissao.');

        return $emissao->addDays(self::PRAZO_DIAS + max(0, $diasDilacao));
    }

    public static function vencida(
        mixed $dtNotificacao,
        int $diasDilacao,
        mixed $dtDevolutiva,
        ?CarbonInterface $hoje = null,
    ): bool {
        if (Datas::dia($dtDevolutiva) !== null) {
            return false;
        }

        return self::vencimento($dtNotificacao, $diasDilacao)->lessThan(Datas::hoje($hoje));
    }
}
```

`SDC/app/Modules/Pae/Support/PrazoAnalise.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Enums\SituacaoPrazo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Prazo de 300 dias da CEDEC para decidir sobre o PAE (Resolucao GMG 83/2024, Art. 9).
 *
 * REGRA (decisao da CEDEC de 2026-10-02):
 *   limite = notificacao da FEAM + 300 dias corridos + dias pausados
 *   pausa  = diligencia aberta (Art. 11), da emissao da notificacao ate a
 *            devolutiva: o tempo do empreendedor nao conta contra a CEDEC.
 *
 * A renovacao automatica deixa a notificacao anterior sem devolutiva; por isso
 * uma notificacao sem devolutiva termina na emissao da seguinte, e os dias
 * pausados sao a UNIAO dos intervalos, sem contar sobreposicao duas vezes.
 *
 * `$hoje` e injetavel para teste puro.
 */
final class PrazoAnalise
{
    public const PRAZO_DIAS = 300;

    public const JANELA_PROXIMO_DIAS = 10;

    /** Analise encerrada: o prazo deixa de ser cobrado. */
    public const STATUS_ENCERRADOS = [
        PaeProtocoloStatus::APROVADO,
        PaeProtocoloStatus::CCPAE,
        PaeProtocoloStatus::ATIVO_3_ANOS,
        PaeProtocoloStatus::REPROVADO,
        PaeProtocoloStatus::REVOGADO,
    ];

    /**
     * @param  list<array{dt_notificacao: mixed, dt_devolutiva: mixed}>  $notificacoes
     * @return list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>
     */
    public static function intervalosDeNotificacoes(array $notificacoes): array
    {
        $ordenadas = [];
        foreach ($notificacoes as $n) {
            $inicio = Datas::dia($n['dt_notificacao'] ?? null);
            if ($inicio !== null) {
                $ordenadas[] = ['inicio' => $inicio, 'devolutiva' => Datas::dia($n['dt_devolutiva'] ?? null)];
            }
        }
        usort($ordenadas, fn (array $a, array $b): int => $a['inicio']->getTimestamp() <=> $b['inicio']->getTimestamp());

        $intervalos = [];
        foreach ($ordenadas as $i => $n) {
            $intervalos[] = [
                'inicio' => $n['inicio'],
                'fim' => $n['devolutiva'] ?? ($ordenadas[$i + 1]['inicio'] ?? null),
            ];
        }

        return $intervalos;
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function diasPausados(array $intervalos, ?CarbonInterface $hoje = null): int
    {
        $hoje = Datas::hoje($hoje);
        $faixas = [];
        foreach ($intervalos as $intervalo) {
            $fim = $intervalo['fim'] ?? $hoje;
            if ($fim->greaterThan($intervalo['inicio'])) {
                $faixas[] = [$intervalo['inicio'], $fim];
            }
        }
        usort($faixas, fn (array $a, array $b): int => $a[0]->getTimestamp() <=> $b[0]->getTimestamp());

        $total = 0;
        $atual = null;
        foreach ($faixas as $faixa) {
            if ($atual === null) {
                $atual = $faixa;
                continue;
            }
            if ($faixa[0]->lessThanOrEqualTo($atual[1])) {
                $atual[1] = $faixa[1]->greaterThan($atual[1]) ? $faixa[1] : $atual[1];
                continue;
            }
            $total += self::dias($atual[0], $atual[1]);
            $atual = $faixa;
        }

        return $atual === null ? $total : $total + self::dias($atual[0], $atual[1]);
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function estaPausado(array $intervalos): bool
    {
        foreach ($intervalos as $intervalo) {
            if ($intervalo['fim'] === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function limite(mixed $dtNotificacaoFeam, array $intervalos, ?CarbonInterface $hoje = null): ?CarbonImmutable
    {
        $feam = Datas::dia($dtNotificacaoFeam);

        return $feam?->addDays(self::PRAZO_DIAS + self::diasPausados($intervalos, $hoje));
    }

    public static function situacao(
        PaeProtocoloStatus $status,
        mixed $limite,
        bool $pausado,
        ?CarbonInterface $hoje = null,
    ): SituacaoPrazo {
        if (in_array($status, self::STATUS_ENCERRADOS, true)) {
            return SituacaoPrazo::OK;
        }

        $limite = Datas::dia($limite);
        if ($limite === null) {
            return SituacaoPrazo::SEM_DATA;
        }

        $hoje = Datas::hoje($hoje);
        if ($limite->lessThan($hoje)) {
            return SituacaoPrazo::VENCIDO;
        }
        if ($pausado) {
            return SituacaoPrazo::PAUSADO;
        }

        return self::dias($hoje, $limite) <= self::JANELA_PROXIMO_DIAS ? SituacaoPrazo::PROXIMO : SituacaoPrazo::OK;
    }

    /** Carbon 3: diffInDays tem sinal e devolve float. */
    private static function dias(CarbonImmutable $de, CarbonImmutable $ate): int
    {
        return (int) round($de->diffInDays($ate));
    }
}
```

`SDC/app/Modules/Pae/Support/VigenciaCcpae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;

/**
 * Vigencia do CCPAE: o PAE aprovado e atualizado a cada 3 anos (Resolucao GMG 83/2024).
 *   - empreendimento novo: da publicacao da Licenca de Operacao (Art. 4);
 *   - empreendimento ja com LO: da emissao do CCPAE (Art. 5).
 *
 * addYearsNoOverflow: LO em 29/02 vence em 28/02, nao em 01/03.
 */
final class VigenciaCcpae
{
    public const ANOS = 3;

    public static function vencimento(mixed $dtEmissao, bool $empreendimentoNovo, mixed $dtLicencaOperacao): CarbonImmutable
    {
        $base = $empreendimentoNovo ? Datas::dia($dtLicencaOperacao) : Datas::dia($dtEmissao);

        if ($base === null) {
            throw new \InvalidArgumentException($empreendimentoNovo
                ? 'Empreendimento novo exige a data da Licenca de Operacao (Art. 4).'
                : 'CCPAE sem data de emissao.');
        }

        return $base->addYearsNoOverflow(self::ANOS);
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Unit/Pae tests/Unit/Support`
Expected: PASS (todos)

### Task 3: Migration, models e estimativa dos legados

**Files:**
- Create:
  - `SDC/database/migrations/2026_10_02_120000_ajusta_pae_prazos_ccpae.php`
  - `SDC/app/Modules/Pae/Models/PaeCcpae.php`
  - `SDC/app/Modules/Pae/Models/PaeDilacao.php`
  - `SDC/app/Modules/Pae/Support/EstimativaNotificacaoFeam.php`
- Modify:
  - `SDC/app/Modules/Pae/Models/PaeProtocolo.php` (fillable, casts, relações, scope, trilha)
  - `SDC/app/Modules/Pae/Models/PaeNotificacao.php` (relação `dilacoes`, `diasDilacao()`)
- Test: `SDC/tests/Feature/Pae/PaePrazoDadosTest.php`

**Interfaces:**
- Consumes: `PrazoAnalise` (Tarefa 2)
- Produces:
  - Colunas em `pae_protocolos`: `dt_notificacao_feam` (date), `dt_notificacao_feam_estimada` (bool) e `ciclos_esgotados_em` (date)
  - Colunas novas em outras tabelas: `pae_dilacoes.pae_notificacao_id`, `pae_ccpae.dt_licenca_operacao` e `pae_ccpae.emitido_por`
  - `PaeProtocolo`:
    - relações `analise(): HasOne`, `ccpaes(): HasMany`, `ccpaeVigente(): HasOne`
    - `scopeVencidos($q)`
  - `PaeNotificacao`: `dilacoes(): HasMany` e `diasDilacao(): int`
  - `PaeDilacao::STATUS_APROVADA = 'APROVADA'`
  - `EstimativaNotificacaoFeam::aplicar(): int`

Nota de desenho (adição ao spec, mesma migration): `ciclos_esgotados_em` marca o 3º ciclo vencido. Ela permite filtrar e contar em SQL (card "Ciclos esgotados") e garante um único aviso por esgotamento (Review Focus 4).

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeDilacao;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\EstimativaNotificacaoFeam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaePrazoDadosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_estimativa_usa_dt_entrada_e_marca_estimada(): void
    {
        $p = PaeProtocolo::factory()->create(['dt_entrada' => '2026-01-10', 'dt_notificacao_feam' => null]);

        EstimativaNotificacaoFeam::aplicar();

        $p->refresh();
        $this->assertSame('2026-01-10', $p->dt_notificacao_feam->toDateString());
        $this->assertTrue($p->dt_notificacao_feam_estimada);
        $this->assertSame('2026-11-06', $p->limite_analise->toDateString());
    }

    public function test_estimativa_preserva_limite_legado(): void
    {
        $p = PaeProtocolo::factory()->create([
            'dt_entrada' => '2026-01-10',
            'dt_notificacao_feam' => null,
            'limite_analise' => '2026-12-31',
        ]);

        EstimativaNotificacaoFeam::aplicar();

        $p->refresh();
        $this->assertSame('2026-12-31', $p->limite_analise->toDateString());
        $this->assertSame('2026-03-06', $p->dt_notificacao_feam->toDateString());
    }

    public function test_estimativa_desconta_diligencia_e_e_idempotente(): void
    {
        $p = PaeProtocolo::factory()->create(['dt_entrada' => '2026-01-10', 'dt_notificacao_feam' => null]);
        $a = PaeAnalise::factory()->create(['pae_protocolo_id' => $p->id]);
        PaeNotificacao::factory()->create([
            'pae_analise_id' => $a->id, 'dt_notificacao' => '2026-02-01', 'dt_devolutiva' => '2026-02-21',
        ]);

        EstimativaNotificacaoFeam::aplicar();
        $this->assertSame(0, EstimativaNotificacaoFeam::aplicar());

        $this->assertSame('2026-11-26', $p->fresh()->limite_analise->toDateString());
    }

    public function test_status_tem_default_minusculo(): void
    {
        $default = DB::selectOne(
            "select column_default from information_schema.columns where table_name = 'pae_protocolos' and column_name = 'status'"
        )->column_default;

        $this->assertStringContainsString("'novo'", $default);
    }

    public function test_dilacao_pertence_a_notificacao(): void
    {
        $p = PaeProtocolo::factory()->create();
        $a = PaeAnalise::factory()->create(['pae_protocolo_id' => $p->id]);
        $n = PaeNotificacao::factory()->create(['pae_analise_id' => $a->id, 'dt_notificacao' => '2026-02-01']);
        PaeDilacao::create([
            'protocolo_id' => $p->id, 'pae_notificacao_id' => $n->id,
            'status' => PaeDilacao::STATUS_APROVADA, 'dias_adicionais' => 15,
        ]);
        PaeDilacao::create([
            'protocolo_id' => $p->id, 'pae_notificacao_id' => $n->id,
            'status' => 'PENDENTE', 'dias_adicionais' => 99,
        ]);

        $this->assertSame(15, $n->fresh()->diasDilacao());
        $this->assertSame($a->id, $p->fresh()->analise->id);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaePrazoDadosTest.php`
Expected: FAIL, `Class "App\Modules\Pae\Models\PaeDilacao" not found`

- [ ] **Step 3: Implementar models, estimativa e migration**

`SDC/app/Modules/Pae/Models/PaeDilacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dilacao do prazo de uma notificacao (Art. 11): dias concedidos pela CEDEC
 * alem dos 30. Pertence a notificacao; protocolo_id fica para consulta direta.
 */
class PaeDilacao extends Model
{
    public const STATUS_APROVADA = 'APROVADA';

    protected $table = 'pae_dilacoes';

    protected $fillable = [
        'protocolo_id',
        'pae_notificacao_id',
        'status',
        'dias_adicionais',
        'justificativa',
        'aprovado_por',
    ];

    protected $casts = [
        'dias_adicionais' => 'integer',
    ];

    public function notificacao(): BelongsTo
    {
        return $this->belongsTo(PaeNotificacao::class, 'pae_notificacao_id');
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }
}
```

`SDC/app/Modules/Pae/Models/PaeCcpae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificado de Conformidade do PAE (Resolucao GMG 83/2024, Art. 2, V).
 * A tabela legada nao tem created_at: dt_emissao e o marco.
 */
class PaeCcpae extends Model
{
    public const CREATED_AT = null;

    public const STATUS_ATIVO = 'ATIVO';

    protected $table = 'pae_ccpae';

    protected $fillable = [
        'protocolo_id',
        'codigo',
        'dt_emissao',
        'dt_vencimento',
        'status',
        'dt_licenca_operacao',
        'emitido_por',
    ];

    protected $casts = [
        'dt_emissao' => 'date',
        'dt_vencimento' => 'date',
        'dt_licenca_operacao' => 'date',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function emissor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }
}
```

`SDC/app/Modules/Pae/Models/PaeNotificacao.php`: adicionar o import e os métodos ao final da classe.

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

```php
    public function dilacoes(): HasMany
    {
        return $this->hasMany(PaeDilacao::class, 'pae_notificacao_id')->orderBy('id');
    }

    /** Dias concedidos por dilacoes aprovadas; usa a relacao ja carregada quando houver. */
    public function diasDilacao(): int
    {
        return (int) $this->dilacoes
            ->where('status', PaeDilacao::STATUS_APROVADA)
            ->sum('dias_adicionais');
    }
```

`SDC/app/Modules/Pae/Models/PaeProtocolo.php`:

1. Imports: adicionar `use App\Modules\Pae\Support\PrazoAnalise;` e `use Illuminate\Database\Eloquent\Relations\HasOne;`.
2. `$fillable`: acrescentar `'dt_notificacao_feam'`, `'dt_notificacao_feam_estimada'` e `'ciclos_esgotados_em'` depois de `'empnto_search'`.
3. `$casts`: acrescentar `'dt_notificacao_feam' => 'date'`, `'dt_notificacao_feam_estimada' => 'boolean'` e `'ciclos_esgotados_em' => 'date'`.
4. Depois de `timeline()`:

```php
    public function analise(): HasOne
    {
        return $this->hasOne(PaeAnalise::class, 'pae_protocolo_id');
    }

    public function ccpaes(): HasMany
    {
        return $this->hasMany(PaeCcpae::class, 'protocolo_id')->orderBy('id');
    }

    public function ccpaeVigente(): HasOne
    {
        return $this->hasOne(PaeCcpae::class, 'protocolo_id')->latestOfMany('id');
    }
```

5. Depois de `scopePorStatus`:

```php
    /** Prazo de analise (Art. 9) expirado em protocolo cuja analise nao terminou. */
    public function scopeVencidos($query)
    {
        return $query->whereNotNull('limite_analise')
            ->where('limite_analise', '<', now()->toDateString())
            ->whereNotIn('status', array_map(
                fn (PaeProtocoloStatus $s): string => $s->value,
                PrazoAnalise::STATUS_ENCERRADOS,
            ));
    }
```

6. Em `camposIgnoradosNaTrilha()`, acrescentar ao array:

```php
            // Calculadas pelo sistema (PaePrazoService e comando diario).
            'limite_analise',
            'ciclos_esgotados_em',
```

`SDC/app/Modules/Pae/Support/EstimativaNotificacaoFeam.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Illuminate\Support\Facades\DB;

/**
 * Backfill dos protocolos sem data da notificacao da FEAM (decisao da CEDEC de
 * 2026-10-02): a data e estimada e marcada como estimada, para a CEDEC corrigir.
 *
 *   - com limite_analise legado: FEAM = limite - 300 (preserva o prazo que ja existia);
 *   - sem limite: FEAM = dt_entrada.
 *
 * Depois recalcula limite_analise pela mesma regra do PrazoAnalise. So usa
 * query builder e a classe pura, para poder rodar dentro de migration.
 * Idempotente: so toca linhas com dt_notificacao_feam nulo.
 */
final class EstimativaNotificacaoFeam
{
    public static function aplicar(): int
    {
        $total = 0;

        DB::table('pae_protocolos')
            ->whereNull('dt_notificacao_feam')
            ->whereNotNull('dt_entrada')
            ->orderBy('id')
            ->chunkById(200, function ($lote) use (&$total): void {
                foreach ($lote as $protocolo) {
                    $feam = $protocolo->limite_analise !== null
                        ? Datas::dia($protocolo->limite_analise)->subDays(PrazoAnalise::PRAZO_DIAS)
                        : Datas::dia($protocolo->dt_entrada);

                    $notificacoes = DB::table('pae_notificacoes as n')
                        ->join('pae_analises as a', 'a.id', '=', 'n.pae_analise_id')
                        ->where('a.pae_protocolo_id', $protocolo->id)
                        ->whereNull('n.deleted_at')
                        ->orderBy('n.dt_notificacao')
                        ->orderBy('n.id')
                        ->get(['n.dt_notificacao', 'n.dt_devolutiva'])
                        ->map(fn ($n): array => ['dt_notificacao' => $n->dt_notificacao, 'dt_devolutiva' => $n->dt_devolutiva])
                        ->all();

                    $limite = PrazoAnalise::limite($feam, PrazoAnalise::intervalosDeNotificacoes($notificacoes));

                    DB::table('pae_protocolos')->where('id', $protocolo->id)->update([
                        'dt_notificacao_feam' => $feam->toDateString(),
                        'dt_notificacao_feam_estimada' => true,
                        'limite_analise' => $limite?->toDateString(),
                    ]);
                    $total++;
                }
            });

        return $total;
    }
}
```

`SDC/database/migrations/2026_10_02_120000_ajusta_pae_prazos_ccpae.php`:

```php
<?php

declare(strict_types=1);

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Support\EstimativaNotificacaoFeam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PAE Subprojeto A (Resolucao GMG 83/2024): prazos legais, CCPAE e dilacao.
 * Migration consolidada do subprojeto: toda mudanca de schema do A fica aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pae_protocolos', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_protocolos', 'dt_notificacao_feam')) {
                $table->date('dt_notificacao_feam')->nullable();
            }
            if (! Schema::hasColumn('pae_protocolos', 'dt_notificacao_feam_estimada')) {
                $table->boolean('dt_notificacao_feam_estimada')->default(false);
            }
            if (! Schema::hasColumn('pae_protocolos', 'ciclos_esgotados_em')) {
                $table->date('ciclos_esgotados_em')->nullable()->index();
            }
        });

        Schema::table('pae_dilacoes', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_dilacoes', 'pae_notificacao_id')) {
                $table->foreignId('pae_notificacao_id')->nullable()->index()
                    ->constrained('pae_notificacoes')->nullOnDelete();
            }
        });

        Schema::table('pae_ccpae', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_ccpae', 'dt_licenca_operacao')) {
                $table->date('dt_licenca_operacao')->nullable();
            }
            if (! Schema::hasColumn('pae_ccpae', 'emitido_por')) {
                $table->foreignId('emitido_por')->nullable()->index()
                    ->constrained('users')->nullOnDelete();
            }
        });

        // O default 'NOVO' nao casa com o enum ('novo'): linha criada sem status
        // explicito quebrava o cast do model.
        DB::statement("ALTER TABLE pae_protocolos ALTER COLUMN status SET DEFAULT 'novo'");
        foreach (PaeProtocoloStatus::cases() as $status) {
            DB::table('pae_protocolos')
                ->where('status', strtoupper($status->value))
                ->update(['status' => $status->value]);
        }

        EstimativaNotificacaoFeam::aplicar();
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pae_protocolos ALTER COLUMN status SET DEFAULT 'NOVO'");

        Schema::table('pae_ccpae', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emitido_por');
            $table->dropColumn('dt_licenca_operacao');
        });
        Schema::table('pae_dilacoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pae_notificacao_id');
        });
        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->dropColumn(['dt_notificacao_feam', 'dt_notificacao_feam_estimada', 'ciclos_esgotados_em']);
        });
    }
};
```

- [ ] **Step 4: Aplicar a migration no banco isolado e rodar**

```bash
$PT artisan migrate --force
$PT vendor/bin/phpunit tests/Feature/Pae/PaePrazoDadosTest.php
```

Expected: migration `DONE`. PASS (5 testes).

- [ ] **Step 5: Conferir reversibilidade**

```bash
$PT artisan migrate:rollback --step=1 --force && $PT artisan migrate --force
```

Expected: rollback e reaplicação sem erro.

- [ ] **Step 6: Rodar a suíte PAE inteira (sem regressão)**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae tests/Unit/Pae tests/Unit/Support`
Expected: mesmo resultado da linha de base mais os testes novos verdes.

- [ ] **Step 7: Commit da Fase 1**

```bash
cd "$WT"
git add SDC/app/Support/Calendario/CalendarioDiasUteis.php SDC/config/feriados.php \
  SDC/app/Modules/Pae/Support/Datas.php SDC/app/Modules/Pae/Enums/SituacaoPrazo.php \
  SDC/app/Modules/Pae/Support/PrazoProtocolo.php SDC/app/Modules/Pae/Support/PrazoNotificacao.php \
  SDC/app/Modules/Pae/Support/PrazoAnalise.php SDC/app/Modules/Pae/Support/VigenciaCcpae.php \
  SDC/app/Modules/Pae/Support/EstimativaNotificacaoFeam.php \
  SDC/app/Modules/Pae/Models/PaeCcpae.php SDC/app/Modules/Pae/Models/PaeDilacao.php \
  SDC/app/Modules/Pae/Models/PaeProtocolo.php SDC/app/Modules/Pae/Models/PaeNotificacao.php \
  SDC/database/migrations/2026_10_02_120000_ajusta_pae_prazos_ccpae.php
git status --short   # conferir: nenhum arquivo de tests/ no stage
git commit -m "🗃️ db(pae): prazos legais da GMG 83, CCPAE e dilacao no schema com calculo puro"
```

---

## Fase 2 -- Máquina de estados

### Task 4: Workflow, guard de emissão e correção do atribuir

**Files:**
- Create:
  - `SDC/app/Modules/Pae/Support/TimelinePae.php`
  - `SDC/app/Modules/Pae/Domain/ContextoTransicao.php`
  - `SDC/app/Modules/Pae/Domain/Contracts/GuardaTransicaoPae.php`
  - `SDC/app/Modules/Pae/Domain/Guards/ExigeEmissaoCcpae.php`
  - `SDC/app/Modules/Pae/Domain/Exceptions/TransicaoProibidaException.php`
  - `SDC/app/Modules/Pae/Domain/Workflows/PaeProtocoloWorkflow.php`
- Modify:
  - `SDC/app/Modules/Pae/Services/PaeProtocoloService.php` (`changeStatus`, `atribuir`, `registrarTimeline` e `publicarParecerConcluido` saem do service)
  - `SDC/app/Modules/Pae/PaeServiceProvider.php` (registro dos guards)
- Test: `SDC/tests/Feature/Pae/PaeProtocoloWorkflowTest.php`

**Interfaces:**
- Produces:
  - `TimelinePae::registrar(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void`
  - `ContextoTransicao::manual()` e `ContextoTransicao::emissaoCcpae()`, com `ehEmissaoCcpae(): bool`
  - `GuardaTransicaoPae::check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void`
  - `TransicaoProibidaException::entre(string $de, string $para)` e `TransicaoProibidaException::semEmissaoCcpae()`
  - `PaeProtocoloWorkflow`:
    - `transitar(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, User $user, string $obs = '', ?ContextoTransicao $contexto = null): PaeProtocolo`
    - `conduzirAte(PaeProtocolo $protocolo, PaeProtocoloStatus $alvo, User $user, string $obs = ''): PaeProtocolo`
    - `transicaoPermitida(PaeProtocoloStatus $de, PaeProtocoloStatus $para): bool` (estático)
  - `PaeProtocoloService::changeStatus()` mantém a assinatura e passa a lançar `ValidationException` na chave `status`

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTramitacao;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaeProtocoloWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): PaeProtocoloService
    {
        return app(PaeProtocoloService::class);
    }

    public function test_transicao_invalida_e_recusada_com_a_mensagem_de_hoje(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'novo']);

        try {
            $this->service()->changeStatus($p, PaeProtocoloStatus::APROVADO, User::factory()->create());
            $this->fail('Transicao deveria ser recusada.');
        } catch (ValidationException $e) {
            $this->assertSame('Transição inválida: Novo → Aprovado.', $e->errors()['status'][0]);
        }
    }

    public function test_ccpae_pela_troca_generica_de_status_e_recusado(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'aprovado']);

        $this->expectException(ValidationException::class);

        $this->service()->changeStatus($p, PaeProtocoloStatus::CCPAE, User::factory()->create());
    }

    public function test_validacao_para_ccpae_pela_emissao_aceita_e_desarquiva(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'gerenciamento', 'arquivado' => true]);

        $novo = app(PaeProtocoloWorkflow::class)->transitar(
            $p, PaeProtocoloStatus::CCPAE, User::factory()->create(), '', ContextoTransicao::emissaoCcpae()
        );

        $this->assertSame(PaeProtocoloStatus::CCPAE, $novo->status);
        $this->assertFalse($novo->arquivado);
        $this->assertDatabaseHas('pae_timeline', ['protocolo_id' => $p->id, 'evento' => 'status_alterado']);
    }

    public function test_atribuir_a_partir_de_novo_percorre_a_maquina(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'novo']);
        $analista = User::factory()->create();

        $novo = $this->service()->atribuir($p, $analista, User::factory()->create());

        $this->assertSame(PaeProtocoloStatus::NOTIFICACAO, $novo->status);
        $this->assertSame($analista->id, $novo->analista_atual_id);
        $this->assertSame(
            ['entrada_processo', 'criacao_sdc', 'gerenciamento', 'notificacao'],
            PaeTramitacao::where('protocolo_id', $p->id)->orderBy('id')->pluck('status')->all()
        );
        $this->assertDatabaseHas('pae_timeline', ['protocolo_id' => $p->id, 'evento' => 'atribuicao']);
    }

    public function test_parecer_concluido_continua_no_outbox_com_prazo(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'analise', 'limite_analise' => '2027-01-31']);

        $this->service()->changeStatus($p, PaeProtocoloStatus::APROVADO, User::factory()->create());

        $this->assertDatabaseHas('outbox_events', ['aggregate_id' => (string) $p->id, 'event_name' => 'pae.parecer.concluido']);
    }
}
```

Antes do Step 2, conferir os nomes das colunas de `outbox_events`: `$PT artisan tinker --execute="dump(Schema::getColumnListing('outbox_events'));"`. Se forem outros, ajustar o `assertDatabaseHas` acima (por exemplo, `name` em vez de `event_name`).

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeProtocoloWorkflowTest.php`
Expected: FAIL, `Class "App\Modules\Pae\Domain\ContextoTransicao" not found`

- [ ] **Step 3: Implementar**

`SDC/app/Modules/Pae/Support/TimelinePae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;

/**
 * Registro na timeline do protocolo. Antes estava copiado em PaeProtocoloService
 * e PaeNotificacaoService.
 */
final class TimelinePae
{
    public static function registrar(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento' => $evento,
            'descricao' => $descricao,
            'user_id' => $user->id,
        ]);
    }
}
```

`SDC/app/Modules/Pae/Domain/ContextoTransicao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain;

/**
 * Origem de uma transicao de status, passada como argumento a cada chamada.
 *
 * Objeto de valor e nao servico com estado: os services do PAE sao singleton
 * no Octane, e um "contexto ativo" guardado no container vazaria entre requests.
 */
final class ContextoTransicao
{
    private const MANUAL = 'manual';

    private const EMISSAO_CCPAE = 'emissao_ccpae';

    private function __construct(private readonly string $origem) {}

    public static function manual(): self
    {
        return new self(self::MANUAL);
    }

    /** So o PaeCcpaeService::emitir cria este contexto. */
    public static function emissaoCcpae(): self
    {
        return new self(self::EMISSAO_CCPAE);
    }

    public function ehEmissaoCcpae(): bool
    {
        return $this->origem === self::EMISSAO_CCPAE;
    }
}
```

`SDC/app/Modules/Pae/Domain/Contracts/GuardaTransicaoPae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Contracts;

use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;

/**
 * Regra que pode barrar uma transicao de status do protocolo PAE.
 * Registrada pela tag 'pae.guardas_transicao' no PaeServiceProvider.
 */
interface GuardaTransicaoPae
{
    /**
     * @throws TransicaoProibidaException
     */
    public function check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void;
}
```

`SDC/app/Modules/Pae/Domain/Exceptions/TransicaoProibidaException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Exceptions;

final class TransicaoProibidaException extends \DomainException
{
    public static function entre(string $de, string $para): self
    {
        return new self("Transição inválida: {$de} → {$para}.");
    }

    public static function semEmissaoCcpae(): self
    {
        return new self('Para concluir o protocolo para CCPAE, use a emissão do CCPAE: informe o código e a data de emissão.');
    }
}
```

`SDC/app/Modules/Pae/Domain/Guards/ExigeEmissaoCcpae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Guards;

use App\Modules\Pae\Domain\Contracts\GuardaTransicaoPae;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;

/**
 * Status CCPAE so com certificado registrado (pae_ccpae): codigo, emissao e
 * vigencia. Sem isso ccpae/ccpae_venc ficavam vazios e o vencimento de 3 anos
 * (Arts. 4 e 5) nunca era calculado.
 */
final class ExigeEmissaoCcpae implements GuardaTransicaoPae
{
    public function check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void
    {
        if ($novo === PaeProtocoloStatus::CCPAE && ! $contexto->ehEmissaoCcpae()) {
            throw TransicaoProibidaException::semEmissaoCcpae();
        }
    }
}
```

`SDC/app/Modules/Pae/Domain/Workflows/PaeProtocoloWorkflow.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Workflows;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Modules\Pae\Domain\Contracts\GuardaTransicaoPae;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTramitacao;
use App\Modules\Pae\Support\CicloProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Support\Facades\DB;

/**
 * Maquina de estados do protocolo PAE (padrao Demandas\Domain\Workflows).
 *
 * Unico ponto que grava status: valida pelo enum, roda os guards e registra
 * tramitacao e timeline na mesma transacao. Sem estado proprio (os guards sao
 * stateless e a origem viaja em ContextoTransicao), seguro como singleton no Octane.
 */
final class PaeProtocoloWorkflow
{
    /**
     * Validacao para CCPAE: atalho de conclusao de protocolos que ja tem
     * certificado, a partir de qualquer estado ativo. Antes era um if solto
     * em PaeProtocoloService::changeStatus.
     */
    private const ORIGENS_VALIDACAO_CCPAE = [
        PaeProtocoloStatus::NOVO,
        PaeProtocoloStatus::ENTRADA_PROCESSO,
        PaeProtocoloStatus::CRIACAO_SDC,
        PaeProtocoloStatus::GERENCIAMENTO,
        PaeProtocoloStatus::NOTIFICACAO,
        PaeProtocoloStatus::ANALISE,
        PaeProtocoloStatus::APROVADO,
        PaeProtocoloStatus::ESPERAR_TRATATIVA,
        PaeProtocoloStatus::DILACAO,
    ];

    /**
     * @param  iterable<GuardaTransicaoPae>  $guardas
     */
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly iterable $guardas,
    ) {}

    public static function transicaoPermitida(PaeProtocoloStatus $de, PaeProtocoloStatus $para): bool
    {
        return $de->canTransitionTo($para)
            || ($para === PaeProtocoloStatus::CCPAE && in_array($de, self::ORIGENS_VALIDACAO_CCPAE, true));
    }

    public function transitar(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $novo,
        User $user,
        string $obs = '',
        ?ContextoTransicao $contexto = null,
    ): PaeProtocolo {
        $contexto ??= ContextoTransicao::manual();

        return DB::transaction(function () use ($protocolo, $novo, $user, $obs, $contexto): PaeProtocolo {
            $protocolo = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
            $anterior = $protocolo->status;

            if ($anterior === $novo) {
                return $protocolo;
            }
            if (! self::transicaoPermitida($anterior, $novo)) {
                throw TransicaoProibidaException::entre($anterior->getLabel(), $novo->getLabel());
            }
            foreach ($this->guardas as $guarda) {
                $guarda->check($protocolo, $novo, $contexto);
            }

            $desarquivar = (bool) $protocolo->arquivado && $novo === PaeProtocoloStatus::CCPAE;
            $protocolo->update(array_merge(
                ['status' => $novo->value, 'updated_by' => $user->id],
                $desarquivar ? ['arquivado' => false] : [],
            ));

            $tramitacao = PaeTramitacao::create([
                'protocolo_id' => $protocolo->id,
                'user_id' => $user->id,
                'status' => $novo->value,
                'obs' => $obs ?: null,
            ]);

            $descricao = "Status alterado de '{$anterior->getLabel()}' para '{$novo->getLabel()}'. {$obs}";
            if ($desarquivar) {
                $descricao .= ' Protocolo desarquivado automaticamente pela transicao para CCPAE.';
            }
            TimelinePae::registrar($protocolo, 'status_alterado', $descricao, $user);

            if ($anterior === PaeProtocoloStatus::ANALISE
                && in_array($novo, [PaeProtocoloStatus::APROVADO, PaeProtocoloStatus::REPROVADO], true)) {
                $this->publicarParecerConcluido($protocolo, $anterior, $novo, $user, $tramitacao);
            }

            return $protocolo->fresh();
        });
    }

    /**
     * Leva o protocolo ate o alvo pelo menor caminho da maquina, numa transacao
     * so: um guard barrando no meio desfaz todos os passos.
     */
    public function conduzirAte(PaeProtocolo $protocolo, PaeProtocoloStatus $alvo, User $user, string $obs = ''): PaeProtocolo
    {
        return DB::transaction(function () use ($protocolo, $alvo, $user, $obs): PaeProtocolo {
            $atual = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
            foreach (self::caminho($atual->status, $alvo) as $passo) {
                $atual = $this->transitar($atual, $passo, $user, $obs);
            }

            return $atual;
        });
    }

    /**
     * Busca em largura sobre getAllowedTransitions(). 14 estados: custo nulo.
     *
     * @return list<PaeProtocoloStatus>
     */
    public static function caminho(PaeProtocoloStatus $de, PaeProtocoloStatus $para): array
    {
        if ($de === $para) {
            return [];
        }

        $fila = [[$de, []]];
        $visitados = [$de->value => true];
        while ($fila !== []) {
            [$status, $trilha] = array_shift($fila);
            foreach ($status->getAllowedTransitions() as $proximo) {
                if (isset($visitados[$proximo->value])) {
                    continue;
                }
                $novaTrilha = [...$trilha, $proximo];
                if ($proximo === $para) {
                    return $novaTrilha;
                }
                $visitados[$proximo->value] = true;
                $fila[] = [$proximo, $novaTrilha];
            }
        }

        throw TransicaoProibidaException::entre($de->getLabel(), $para->getLabel());
    }

    /**
     * ParecerConcluidoV1 no outbox, na MESMA transacao da transicao.
     *
     * A saida de ANALISE para APROVADO/REPROVADO e o unico ponto em que a
     * analise se fecha com decisao. O creditado e o proprio analista que emitiu
     * o parecer; validador_user_id nao entra. Entrega pelo dt_status da
     * tramitacao recem-gravada (default do banco, por isso o fresh) e prazo
     * por limite_analise, agora calculado pelo PaePrazoService.
     */
    private function publicarParecerConcluido(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $statusAnterior,
        PaeProtocoloStatus $novo,
        User $user,
        PaeTramitacao $tramitacao,
    ): void {
        $this->outbox->persist(new ParecerConcluidoV1(
            eventId: DomainEvent::newId(),
            aggregateType: 'pae_protocolo',
            aggregateId: (string) $protocolo->id,
            occurredAt: new \DateTimeImmutable(),
            metadata: [
                'protocolo_id' => (int) $protocolo->id,
                'num_protocolo' => $protocolo->num_protocolo,
                'ciclo' => CicloProtocolo::de($protocolo->num_protocolo),
                'decisao' => $novo->value,
                'status_anterior' => $statusAnterior->value,
                'actor_user_id' => (int) $user->id,
                'credited_user_id' => (int) $user->id,
                'prazo_em' => $protocolo->limite_analise?->toDateString(),
                'entregue_em' => $tramitacao->fresh()?->dt_status?->toIso8601String(),
            ],
        ));
    }
}
```

`SDC/app/Modules/Pae/PaeServiceProvider.php`: em `register()`, antes do `extend` do Outbox, acrescentar:

```php
        // Guards da maquina de estados: cada subprojeto acrescenta o seu na tag,
        // sem mexer no workflow (o B traz o de admissibilidade).
        $this->app->tag([ExigeEmissaoCcpae::class], 'pae.guardas_transicao');
        $this->app->when(PaeProtocoloWorkflow::class)
            ->needs('$guardas')
            ->giveTagged('pae.guardas_transicao');
        $this->app->singleton(PaeProtocoloWorkflow::class);
```

E os imports `use App\Modules\Pae\Domain\Guards\ExigeEmissaoCcpae;` e `use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;`.

`SDC/app/Modules/Pae/Services/PaeProtocoloService.php`:

1. Construtor:

```php
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly PaeProtocoloWorkflow $workflow,
    ) {}
```

2. Substituir o método `changeStatus` inteiro (linhas 175-249) e o `publicarParecerConcluido` (linhas 251-289) por:

```php
    public function changeStatus(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $novo,
        User $user,
        string $obs = ''
    ): PaeProtocolo {
        try {
            return $this->workflow->transitar($protocolo, $novo, $user, $obs);
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }
```

3. Substituir o método `atribuir` inteiro por:

```php
    /**
     * Atribui o analista e leva o protocolo a NOTIFICACAO pelo caminho da
     * maquina de estados. Antes gravava o status direto, pulando as etapas.
     */
    public function atribuir(PaeProtocolo $protocolo, User $analista, User $user): PaeProtocolo
    {
        if ($protocolo->analista_atual_id === $analista->id) {
            return $protocolo;
        }

        try {
            return DB::transaction(function () use ($protocolo, $analista, $user): PaeProtocolo {
                $statusAnterior = $protocolo->status;
                $protocolo->update(['analista_atual_id' => $analista->id, 'updated_by' => $user->id]);

                $this->workflow->conduzirAte(
                    $protocolo,
                    PaeProtocoloStatus::NOTIFICACAO,
                    $user,
                    "Analista {$analista->name} atribuído."
                );

                TimelinePae::registrar(
                    $protocolo,
                    'atribuicao',
                    "Protocolo atribuído ao analista {$analista->name}. Status: {$statusAnterior->getLabel()} → Notificação.",
                    $user
                );

                return $protocolo->fresh();
            });
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }
```

4. Trocar cada `$this->registrarTimeline(` restante (em `create` e `relacionar`) por `TimelinePae::registrar(` e apagar o método privado `registrarTimeline`.
5. Imports: acrescentar `TimelinePae`, `PaeProtocoloWorkflow` e `TransicaoProibidaException`. Remover `ParecerConcluidoV1`, `PaeTramitacao` e `PaeTimeline`, se ficarem sem uso.

- [ ] **Step 4: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeProtocoloWorkflowTest.php`
Expected: PASS (5 testes)

- [ ] **Step 5: Rodar a suíte PAE inteira**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae tests/Unit/Pae`
Expected: tudo verde, exceto `test_terceiro_ciclo_vencido_suspende_protocolo`. Ele usa `changeStatus(SUSPENSO)` a partir de NOTIFICACAO, que continua permitido, então deve continuar verde até a Tarefa 6. Se falhar, investigar antes de seguir.

- [ ] **Step 6: Commit da Fase 2**

```bash
cd "$WT"
git add SDC/app/Modules/Pae/Support/TimelinePae.php SDC/app/Modules/Pae/Domain/ContextoTransicao.php \
  SDC/app/Modules/Pae/Domain/Contracts/GuardaTransicaoPae.php SDC/app/Modules/Pae/Domain/Guards/ExigeEmissaoCcpae.php \
  SDC/app/Modules/Pae/Domain/Exceptions/TransicaoProibidaException.php \
  SDC/app/Modules/Pae/Domain/Workflows/PaeProtocoloWorkflow.php \
  SDC/app/Modules/Pae/Services/PaeProtocoloService.php SDC/app/Modules/Pae/PaeServiceProvider.php
git status --short
git commit -m "♻️ refactor(pae): maquina de estados em workflow com guards e atribuir pelo caminho da maquina"
```

---

## Fase 3 -- Serviços, rotas e comando

### Task 5: PaePrazoService e data da notificação da FEAM

**Files:**
- Create:
  - `SDC/app/Modules/Pae/Services/PaePrazoService.php`
  - `SDC/app/Modules/Pae/Requests/AtualizarNotificacaoFeamRequest.php`
- Modify:
  - `SDC/app/Modules/Pae/PaeServiceProvider.php` (singletons de `PaePrazoService` e `CalendarioDiasUteis`)
  - `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php` (`atualizarNotificacaoFeam`)
  - `SDC/routes/modules/pae.php` (rota PUT)
- Test: `SDC/tests/Feature/Pae/PaePrazoServiceTest.php`

**Interfaces:**
- Consumes:
  - `PrazoAnalise`, `PrazoProtocolo` e `CalendarioDiasUteis` (Tarefas 1 e 2)
  - `PaeProtocolo::analise()` (Tarefa 3)
  - `TimelinePae` (Tarefa 4)
- Produces (`PaePrazoService`):
  - `recalcular(PaeProtocolo $protocolo): PaeProtocolo`
  - `definirNotificacaoFeam(PaeProtocolo $protocolo, string $data, User $user): PaeProtocolo`
  - `recalcularDiligenciasAbertas(): int`
  - `situacao(PaeProtocolo $protocolo): SituacaoPrazo`, que usa `analise.notificacoes` já carregado quando houver
  - `foraDoPrazoDeProtocolo(PaeProtocolo $protocolo): bool`
  - `resumo(PaeProtocolo $protocolo): array`
  - `anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator`
- Produces (rota): `PUT /pae/protocolo/{paeProtocolo}/notificacao-feam`, nome `pae.protocolo.notificacao-feam`

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\Enums\SituacaoPrazo;
use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaePrazoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaePrazoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): PaePrazoService
    {
        return app(PaePrazoService::class);
    }

    public function test_definir_feam_grava_limite_e_tira_a_marca_de_estimada(): void
    {
        $p = PaeProtocolo::factory()->create(['dt_notificacao_feam_estimada' => true]);

        $this->service()->definirNotificacaoFeam($p, '2026-10-01', User::factory()->create());

        $p->refresh();
        $this->assertSame('2027-07-28', $p->limite_analise->toDateString());
        $this->assertFalse($p->dt_notificacao_feam_estimada);
        $this->assertDatabaseHas('pae_timeline', ['protocolo_id' => $p->id, 'evento' => 'prazo']);
    }

    public function test_diligencia_aberta_pausa_e_o_comando_atualiza(): void
    {
        $p = PaeProtocolo::factory()->create(['dt_notificacao_feam' => '2026-01-01']);
        $a = PaeAnalise::factory()->create(['pae_protocolo_id' => $p->id]);
        PaeNotificacao::factory()->create([
            'pae_analise_id' => $a->id,
            'dt_notificacao' => now()->subDays(10)->toDateString(),
            'dt_devolutiva' => null,
        ]);

        $this->assertSame(1, $this->service()->recalcularDiligenciasAbertas());

        $p->refresh();
        $this->assertSame('2026-01-01', $p->limite_analise->copy()->subDays(310)->toDateString());
        $this->assertSame(SituacaoPrazo::PAUSADO, $this->service()->situacao($p->load('analise.notificacoes')));
    }

    public function test_sem_feam_situacao_sem_data(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'notificacao']);

        $this->assertSame(SituacaoPrazo::SEM_DATA, $this->service()->situacao($p));
    }

    public function test_fora_do_prazo_ignora_data_estimada(): void
    {
        $real = PaeProtocolo::factory()->create(['dt_notificacao_feam' => '2026-01-02', 'dt_entrada' => '2026-03-01']);
        $estimada = PaeProtocolo::factory()->create([
            'dt_notificacao_feam' => '2026-01-02', 'dt_entrada' => '2026-03-01', 'dt_notificacao_feam_estimada' => true,
        ]);

        $this->assertTrue($this->service()->foraDoPrazoDeProtocolo($real));
        $this->assertFalse($this->service()->foraDoPrazoDeProtocolo($estimada));
    }

    public function test_rota_atualiza_feam_e_recusa_data_futura(): void
    {
        Permission::firstOrCreate(['name' => 'pae.protocolos.edit', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.edit');
        $p = PaeProtocolo::factory()->create();

        $this->actingAs($user)
            ->put(route('pae.protocolo.notificacao-feam', $p), ['dt_notificacao_feam' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('dt_notificacao_feam');

        $this->actingAs($user)
            ->put(route('pae.protocolo.notificacao-feam', $p), ['dt_notificacao_feam' => '2026-10-01'])
            ->assertRedirect();

        $this->assertSame('2026-10-01', $p->fresh()->dt_notificacao_feam->toDateString());
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaePrazoServiceTest.php`
Expected: FAIL, `Class "App\Modules\Pae\Services\PaePrazoService" not found`

- [ ] **Step 3: Implementar**

`SDC/app/Modules/Pae/Services/PaePrazoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\SituacaoPrazo;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\Datas;
use App\Modules\Pae\Support\PrazoAnalise;
use App\Modules\Pae\Support\PrazoProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Support\Calendario\CalendarioDiasUteis;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grava e expoe os prazos legais do protocolo (Resolucao GMG 83/2024, Arts. 7 e 9).
 *
 * O calculo e das classes puras em Support/; aqui so se busca o que elas
 * precisam e se grava limite_analise. Chamado quando algo muda o prazo: data
 * da FEAM, notificacao emitida, devolutiva, dilacao e o comando diario.
 */
final class PaePrazoService
{
    public function __construct(
        private readonly CalendarioDiasUteis $calendario,
    ) {}

    public function recalcular(PaeProtocolo $protocolo): PaeProtocolo
    {
        $limite = PrazoAnalise::limite($protocolo->dt_notificacao_feam, $this->intervalos($protocolo, false));
        $valor = $limite?->toDateString();

        // Query direta: coluna calculada, nao edicao do usuario (sem trilha nem observers).
        PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['limite_analise' => $valor]);
        $protocolo->setAttribute('limite_analise', $valor);
        $protocolo->syncOriginalAttribute('limite_analise');

        return $protocolo;
    }

    public function definirNotificacaoFeam(PaeProtocolo $protocolo, string $data, User $user): PaeProtocolo
    {
        return DB::transaction(function () use ($protocolo, $data, $user): PaeProtocolo {
            $protocolo->update([
                'dt_notificacao_feam' => $data,
                'dt_notificacao_feam_estimada' => false,
                'updated_by' => $user->id,
            ]);

            TimelinePae::registrar(
                $protocolo,
                'prazo',
                'Data da notificacao da FEAM informada: '.Datas::dia($data)->format('d/m/Y').'.',
                $user
            );

            return $this->recalcular($protocolo);
        });
    }

    /** Diligencia aberta empurra o limite um dia por dia: o comando diario chama isto. */
    public function recalcularDiligenciasAbertas(): int
    {
        $total = 0;

        PaeProtocolo::query()
            ->ativo()
            ->whereNotNull('dt_notificacao_feam')
            ->whereHas('analise.notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->chunkById(200, function ($lote) use (&$total): void {
                foreach ($lote as $protocolo) {
                    $this->recalcular($protocolo);
                    $total++;
                }
            });

        return $total;
    }

    public function situacao(PaeProtocolo $protocolo): SituacaoPrazo
    {
        return PrazoAnalise::situacao(
            $protocolo->status,
            $protocolo->limite_analise,
            PrazoAnalise::estaPausado($this->intervalos($protocolo, true)),
        );
    }

    /** Art. 7: so faz sentido com a data real da FEAM, nunca com a estimada. */
    public function foraDoPrazoDeProtocolo(PaeProtocolo $protocolo): bool
    {
        if ($protocolo->dt_notificacao_feam_estimada) {
            return false;
        }

        return PrazoProtocolo::foraDoPrazo($protocolo->dt_notificacao_feam, $protocolo->dt_entrada, $this->calendario);
    }

    /**
     * Bloco "Prazos" do historico do protocolo.
     *
     * @return array<string, mixed>
     */
    public function resumo(PaeProtocolo $protocolo): array
    {
        $protocolo->loadMissing(['analise.notificacoes', 'ccpaeVigente']);
        $intervalos = $this->intervalos($protocolo, true);
        $ccpae = $protocolo->ccpaeVigente;

        return [
            'dt_notificacao_feam' => $protocolo->dt_notificacao_feam?->toDateString(),
            'dt_notificacao_feam_estimada' => (bool) $protocolo->dt_notificacao_feam_estimada,
            'dt_entrada' => $protocolo->dt_entrada?->toDateString(),
            'limite_protocolo' => PrazoProtocolo::limite($protocolo->dt_notificacao_feam, $this->calendario)?->toDateString(),
            'fora_do_prazo' => $this->foraDoPrazoDeProtocolo($protocolo),
            'limite_analise' => $protocolo->limite_analise?->toDateString(),
            'situacao' => $this->situacao($protocolo)->value,
            'dias_pausados' => PrazoAnalise::diasPausados($intervalos),
            'ciclos_esgotados_em' => $protocolo->ciclos_esgotados_em?->toDateString(),
            'ccpae' => $ccpae === null ? null : [
                'codigo' => $ccpae->codigo,
                'dt_emissao' => $ccpae->dt_emissao?->toDateString(),
                'dt_vencimento' => $ccpae->dt_vencimento?->toDateString(),
                'dt_licenca_operacao' => $ccpae->dt_licenca_operacao?->toDateString(),
            ],
        ];
    }

    /** Acrescenta a situacao do prazo a cada linha da listagem (relacao ja carregada no list()). */
    public function anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator
    {
        return $pagina->through(fn (PaeProtocolo $p): array => [
            ...$p->makeHidden('analise')->toArray(),
            'prazo_situacao' => $this->situacao($p)->value,
            'fora_do_prazo' => $this->foraDoPrazoDeProtocolo($p),
        ]);
    }

    /**
     * @return list<array{inicio: \Carbon\CarbonImmutable, fim: ?\Carbon\CarbonImmutable}>
     */
    private function intervalos(PaeProtocolo $protocolo, bool $usarCarregados): array
    {
        /** @var Collection<int, PaeNotificacao> $notificacoes */
        $notificacoes = $usarCarregados && $protocolo->relationLoaded('analise')
            ? ($protocolo->analise?->notificacoes ?? collect())
            : (PaeNotificacao::query()
                ->whereHas('analise', fn ($q) => $q->where('pae_protocolo_id', $protocolo->getKey()))
                ->get(['dt_notificacao', 'dt_devolutiva']));

        return PrazoAnalise::intervalosDeNotificacoes($notificacoes->map(fn (PaeNotificacao $n): array => [
            'dt_notificacao' => $n->dt_notificacao,
            'dt_devolutiva' => $n->dt_devolutiva,
        ])->all());
    }
}
```

`SDC/app/Modules/Pae/Requests/AtualizarNotificacaoFeamRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarNotificacaoFeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dt_notificacao_feam' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'dt_notificacao_feam.before_or_equal' => 'A data da notificacao da FEAM nao pode ser futura.',
        ];
    }
}
```

`SDC/app/Modules/Pae/PaeServiceProvider.php`, em `register()`:

```php
        $this->app->singleton(CalendarioDiasUteis::class, fn () => CalendarioDiasUteis::padrao());
        $this->app->singleton(PaePrazoService::class);
```

Com os imports `use App\Support\Calendario\CalendarioDiasUteis;` e `use App\Modules\Pae\Services\PaePrazoService;`.

`SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php`: acrescentar `private readonly PaePrazoService $prazos,` ao construtor e o método:

```php
    public function atualizarNotificacaoFeam(AtualizarNotificacaoFeamRequest $request, PaeProtocolo $paeProtocolo): \Illuminate\Http\RedirectResponse
    {
        $this->prazos->definirNotificacaoFeam($paeProtocolo, $request->validated()['dt_notificacao_feam'], $request->user());

        return back()->with('success', 'Data da notificacao da FEAM atualizada e prazo recalculado.');
    }
```

Imports: `App\Modules\Pae\Requests\AtualizarNotificacaoFeamRequest` e `App\Modules\Pae\Services\PaePrazoService`.

`SDC/routes/modules/pae.php`, logo depois da rota `protocolo.assign`:

```php
    Route::put('/protocolo/{paeProtocolo}/notificacao-feam', [PaeProtocoloController::class, 'atualizarNotificacaoFeam'])
        ->name('protocolo.notificacao-feam')
        ->middleware('can:pae.protocolos.edit');
```

- [ ] **Step 4: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaePrazoServiceTest.php`
Expected: PASS (5 testes)

Commit no fim da Fase 3 (Tarefa 8).

### Task 6: Notificações -- dilação, 3º ciclo sinalizado e comando no módulo

**Files:**
- Create:
  - `SDC/app/Modules/Pae/Requests/RegistrarDilacaoRequest.php`
  - `SDC/app/Modules/Pae/Console/VerificarNotificacoesPae.php`
- Delete: `SDC/app/Console/Commands/VerificarNotificacoesPae.php`
- Modify:
  - `SDC/app/Modules/Pae/Services/PaeNotificacaoService.php`
  - `SDC/app/Modules/Pae/Controllers/PaeNotificacaoController.php`
  - `SDC/app/Http/Resources/Pae/PaeNotificacaoResource.php`
  - `SDC/app/Modules/Pae/PaeServiceProvider.php` (`boot()` com `commands`)
  - `SDC/routes/modules/pae.php` (rota de dilação)
- Test:
  - Create: `SDC/tests/Feature/Pae/PaeNotificacaoDilacaoTest.php`
  - Modify (local, sem commit): `SDC/tests/Feature/Pae/PaeNotificacaoCommandTest.php` e `SDC/tests/Feature/Pae/PaeNotificacaoTest.php`

**Interfaces:**
- Consumes:
  - `PrazoNotificacao` (Tarefa 2)
  - `PaeDilacao` e `PaeNotificacao::diasDilacao()` (Tarefa 3)
  - `TimelinePae` (Tarefa 4)
  - `PaePrazoService::recalcular` e `recalcularDiligenciasAbertas` (Tarefa 5)
- Produces:
  - `PaeNotificacaoService::registrarDilacao(PaeNotificacao $notificacao, int $dias, string $justificativa, User $user): PaeDilacao`
  - `listarPorProtocolo()` ganha as chaves `dilacoes` (lista de `{id, dias_adicionais, justificativa, aprovado_por, registrada_em}`) e `dias_dilacao`
  - `PaeNotificacaoService::MAX_CICLOS_AUTOMATICOS = 3`
  - Rota `POST /pae/notificacoes/{paeNotificacao}/dilacoes`, nome `pae.notificacoes.dilacoes.store`

- [ ] **Step 1: Escrever os testes que falham**

`SDC/tests/Feature/Pae/PaeNotificacaoDilacaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaeNotificacaoDilacaoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function service(): PaeNotificacaoService
    {
        return app(PaeNotificacaoService::class);
    }

    private function protocolo(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'status' => 'notificacao',
            'analista_atual_id' => User::factory()->create()->id,
            'dt_notificacao_feam' => '2026-01-01',
        ]);
    }

    /** Emite e envelhece os ciclos ate o 3o vencido sem devolutiva. */
    private function esgotarCiclos(PaeProtocolo $p): void
    {
        $user = User::factory()->create();
        $this->service()->emitir($p, $user, ['num_sei' => 'SEI-1']);
        foreach ([2, 3] as $_) {
            PaeNotificacao::query()->update(['dt_notificacao' => now()->subDays(40)->toDateString()]);
            $this->artisan('pae:verificar-notificacoes')->assertSuccessful();
        }
        PaeNotificacao::query()->update(['dt_notificacao' => now()->subDays(40)->toDateString()]);
    }

    public function test_dilacao_estende_o_vencimento_da_notificacao(): void
    {
        $p = $this->protocolo();
        $user = User::factory()->create();
        $n = $this->service()->emitir($p, $user, ['num_sei' => 'SEI-1']);

        $this->service()->registrarDilacao($n, 15, 'Pedido do empreendedor por estudo complementar.', $user);

        $lista = $this->service()->listarPorProtocolo($p);
        $this->assertSame(now()->addDays(45)->toDateString(), $lista[0]['prazo_final']);
        $this->assertSame(15, $lista[0]['dias_dilacao']);
        $this->assertCount(1, $lista[0]['dilacoes']);
        $this->assertDatabaseHas('pae_timeline', ['protocolo_id' => $p->id, 'evento' => 'dilacao']);
    }

    public function test_dilacao_em_ciclo_encerrado_e_recusada(): void
    {
        $p = $this->protocolo();
        $user = User::factory()->create();
        $n = $this->service()->emitir($p, $user, ['num_sei' => 'SEI-1']);
        $this->service()->registrarDevolutiva($n, $user, now()->toDateString());

        $this->expectException(ValidationException::class);

        $this->service()->registrarDilacao($n->fresh(), 10, 'Tarde demais.', $user);
    }

    public function test_terceiro_ciclo_vencido_sinaliza_e_nao_suspende(): void
    {
        $p = $this->protocolo();
        $this->esgotarCiclos($p);

        $this->artisan('pae:verificar-notificacoes')->assertSuccessful();

        $p->refresh();
        $this->assertSame(PaeProtocoloStatus::NOTIFICACAO, $p->status);
        $this->assertNotNull($p->ciclos_esgotados_em);
        $this->assertSame(1, PaeTimeline::where('protocolo_id', $p->id)->where('evento', 'ciclos_esgotados')->count());
        $this->assertSame(3, PaeNotificacao::whereHas('analise', fn ($q) => $q->where('pae_protocolo_id', $p->id))->count());
    }

    public function test_ciclos_esgotados_nao_duplica(): void
    {
        $p = $this->protocolo();
        $this->esgotarCiclos($p);

        $this->artisan('pae:verificar-notificacoes');
        $this->artisan('pae:verificar-notificacoes');

        $this->assertSame(1, PaeTimeline::where('protocolo_id', $p->id)->where('evento', 'ciclos_esgotados')->count());
    }

    public function test_emissao_manual_depois_do_terceiro_ciclo_e_aceita_e_limpa_a_sinalizacao(): void
    {
        $p = $this->protocolo();
        $this->esgotarCiclos($p);
        $this->artisan('pae:verificar-notificacoes');
        $user = User::factory()->create();
        $ultima = PaeNotificacao::whereHas('analise', fn ($q) => $q->where('pae_protocolo_id', $p->id))->orderByDesc('id')->first();
        $this->service()->registrarDevolutiva($ultima, $user, now()->toDateString());

        $quarta = $this->service()->emitir($p->fresh(), $user, ['num_sei' => 'SEI-4']);

        $this->assertSame('SEI-4', $quarta->num_sei);
        $this->assertNull($p->fresh()->ciclos_esgotados_em);
    }

    public function test_emissao_manual_depois_de_renovacao_automatica_nao_trava(): void
    {
        $p = $this->protocolo();
        $user = User::factory()->create();
        $this->service()->emitir($p, $user, ['num_sei' => 'SEI-1']);
        PaeNotificacao::query()->update(['dt_notificacao' => now()->subDays(40)->toDateString()]);
        $this->artisan('pae:verificar-notificacoes'); // ciclo 2 automatico; ciclo 1 fica sem devolutiva
        $ultima = PaeNotificacao::whereHas('analise', fn ($q) => $q->where('pae_protocolo_id', $p->id))->orderByDesc('id')->first();
        $this->service()->registrarDevolutiva($ultima, $user, now()->toDateString());

        $terceira = $this->service()->emitir($p->fresh(), $user, ['num_sei' => 'SEI-3']);

        $this->assertSame('SEI-3', $terceira->num_sei);
    }

    public function test_devolutiva_anterior_a_emissao_e_recusada(): void
    {
        $p = $this->protocolo();
        $user = User::factory()->create();
        $n = $this->service()->emitir($p, $user, ['num_sei' => 'SEI-1']);

        $this->expectException(ValidationException::class);

        $this->service()->registrarDevolutiva($n, $user, now()->subDay()->toDateString());
    }

    public function test_emitir_recalcula_o_limite_com_a_pausa(): void
    {
        $p = $this->protocolo();

        $this->service()->emitir($p, User::factory()->create(), ['num_sei' => 'SEI-1']);

        // Pausa aberta comecando hoje: 0 dias pausados ate hoje.
        $this->assertSame('2026-10-28', $p->fresh()->limite_analise->toDateString());
    }
}
```

`SDC/tests/Feature/Pae/PaeNotificacaoCommandTest.php` (local, sem commit): substituir `test_terceiro_ciclo_vencido_suspende_protocolo` inteiro por:

```php
    public function test_terceiro_ciclo_vencido_nao_suspende_protocolo(): void
    {
        Mail::fake();

        $protocolo = $this->protocoloDelegadoComEmail();
        $service = app(PaeNotificacaoService::class);
        $user = User::factory()->create();

        $this->travelTo(now()->subDays(95), fn () => $service->emitir($protocolo, $user, ['num_sei' => 'SEI-1']));
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 2

        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // emite ciclo 3

        \App\Modules\Pae\Models\PaeNotificacao::query()->update([
            'dt_notificacao' => now()->subDays(40)->toDateString(),
        ]);
        $this->artisan('pae:verificar-notificacoes'); // sinaliza, nao suspende

        $this->assertSame('notificacao', $protocolo->fresh()->status->value);
        $this->assertNotNull($protocolo->fresh()->ciclos_esgotados_em);
    }
```

`SDC/tests/Feature/Pae/PaeNotificacaoTest.php` (local, sem commit): substituir `test_emitir_bloqueia_apos_3_ciclos` por:

```php
    public function test_emitir_manual_nao_tem_limite_de_ciclos(): void
    {
        $protocolo = $this->protocoloDelegado();
        $user = User::factory()->create();

        foreach ([1, 2, 3] as $ciclo) {
            $n = $this->service()->emitir($protocolo, $user, ['num_sei' => "SEI-{$ciclo}"]);
            $this->service()->registrarDevolutiva($n, $user, now()->toDateString());
        }

        $quarta = $this->service()->emitir($protocolo, $user, ['num_sei' => 'SEI-4']);

        $this->assertSame('SEI-4', $quarta->num_sei);
    }
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeNotificacaoDilacaoTest.php tests/Feature/Pae/PaeNotificacaoTest.php tests/Feature/Pae/PaeNotificacaoCommandTest.php`
Expected: FAIL, `Call to undefined method ...registrarDilacao()`, mais as asserções de suspensão e de limite.

- [ ] **Step 3: Implementar o service**

`SDC/app/Modules/Pae/Services/PaeNotificacaoService.php`:

1. Constantes e construtor. O `PaeProtocoloService` só servia para a suspensão automática e sai:

```php
    /** Mantido para chamadores antigos; a fonte e PrazoNotificacao. */
    public const PRAZO_DIAS = PrazoNotificacao::PRAZO_DIAS;

    /**
     * A renovacao AUTOMATICA para no 3o ciclo. A Resolucao GMG 83/2024 nao
     * limita ciclos; depois disso a decisao e da CEDEC (Art. 139), e a emissao
     * manual continua livre.
     */
    public const MAX_CICLOS_AUTOMATICOS = 3;

    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly PaePrazoService $prazos,
    ) {}
```

Antes de apagar a constante `MAX_CICLOS`, procurar quem a usa: `grep -rn "MAX_CICLOS" SDC/app SDC/resources`. Cada uso fora do service passa a `MAX_CICLOS_AUTOMATICOS`.

2. `emitir()`, substituindo o bloco do limite de ciclos (`if ($ciclo > self::MAX_CICLOS) {...}`):

```php
        if ($automatica && $ciclo > self::MAX_CICLOS_AUTOMATICOS) {
            throw ValidationException::withMessages([
                'notificacao' => 'A renovacao automatica para no '.self::MAX_CICLOS_AUTOMATICOS.'o ciclo: a proxima notificacao e decisao da CEDEC.',
            ]);
        }
```

Substituir a checagem de ciclo aberto (`if (! $automatica && $analise->notificacoes()->whereNull('dt_devolutiva')->exists()) {...}`) por uma que olha só a ÚLTIMA notificação. A renovação automática deixa as anteriores sem devolutiva para sempre, e a checagem antiga bloqueava a emissão manual depois de qualquer renovação:

```php
        $ultima = $analise->notificacoes()->reorder()->orderByDesc('dt_notificacao')->orderByDesc('id')->first();
        if (! $automatica && $ultima !== null && $ultima->dt_devolutiva === null) {
            throw ValidationException::withMessages([
                'notificacao' => 'Existe uma notificacao com prazo em aberto. Registre a devolutiva antes de emitir outra.',
            ]);
        }
```

Logo depois do `$analise->notificacoes()->create([...])`:

```php
        // Nova notificacao tira o protocolo da fila "ciclos esgotados" e abre nova pausa.
        PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
        $this->prazos->recalcular($protocolo);
```

Trocar `self::PRAZO_DIAS` por `PrazoNotificacao::PRAZO_DIAS` na descrição da timeline e trocar `$this->registrarTimeline(` por `TimelinePae::registrar(`.

3. `avisarAnalistaNoInbox()`: trocar o `NotificacaoSpec` inteiro por:

```php
        $urgente = $ciclo >= self::MAX_CICLOS_AUTOMATICOS;

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'pae',
                titulo: $urgente ? 'PAE no ultimo ciclo automatico de notificacao' : 'Notificacao PAE emitida',
                mensagem: sprintf(
                    'Protocolo %s: notificacao %d emitida%s. Prazo de %d dias para devolutiva.',
                    (string) $protocolo->num_protocolo,
                    $ciclo,
                    $automatica ? ' automaticamente' : '',
                    PrazoNotificacao::PRAZO_DIAS,
                ),
                tipo: $urgente ? 'urgent' : 'warning',
                groupKey: null,
                acaoUrl: $protocolo->urlNotificacao(),
                acaoTexto: 'Ver protocolo',
            ),
            [(int) $analista],
        );
```

4. Método novo, logo depois de `avisarAnalistaNoInbox`:

```php
    /** Aviso unico quando a ultima renovacao automatica vence sem devolutiva. */
    private function avisarCiclosEsgotados(PaeProtocolo $protocolo, int $ciclo): void
    {
        $analista = $protocolo->analista_atual_id ?? $protocolo->user_id;

        if ($analista === null) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'pae',
                titulo: 'PAE: ciclos de notificacao esgotados',
                mensagem: sprintf(
                    'Protocolo %s: a notificacao %d venceu sem devolutiva. Decisao da CEDEC: suspender, reprovar ou notificar novamente.',
                    (string) $protocolo->num_protocolo,
                    $ciclo,
                ),
                tipo: 'urgent',
                groupKey: null,
                acaoUrl: $protocolo->urlNotificacao(),
                acaoTexto: 'Ver protocolo',
            ),
            [(int) $analista],
        );
    }
```

5. `registrarDevolutiva()`: logo depois da checagem `if ($notificacao->dt_devolutiva)`:

```php
        if (Datas::dia($dtDevolutiva)->lessThan(Datas::dia($notificacao->dt_notificacao))) {
            throw ValidationException::withMessages([
                'dt_devolutiva' => 'A devolutiva nao pode ser anterior a emissao da notificacao.',
            ]);
        }
```

Dentro da transação, depois do bloco `if ($protocolo) { ...registrarTimeline... }` (trocar também esse `registrarTimeline` por `TimelinePae::registrar`):

```php
            if ($protocolo) {
                PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
                $this->prazos->recalcular($protocolo);
            }
```

6. `publicarRevisaoAceita()`: trocar a linha do `$prazo` por:

```php
        $prazo = $notificacao->dt_notificacao === null
            ? null
            : PrazoNotificacao::vencimento($notificacao->dt_notificacao, $notificacao->diasDilacao());
```

7. Método novo `registrarDilacao`, logo depois de `registrarDevolutiva`:

```php
    /**
     * Dilacao ja decidida pela CEDEC: estende o prazo desta notificacao (Art. 11).
     * O tempo continua fora dos 300 dias porque a diligencia segue aberta.
     */
    public function registrarDilacao(PaeNotificacao $notificacao, int $dias, string $justificativa, User $user): PaeDilacao
    {
        if ($notificacao->dt_devolutiva) {
            throw ValidationException::withMessages([
                'dilacao' => 'Este ciclo ja tem devolutiva: nao cabe dilacao.',
            ]);
        }

        $protocolo = $notificacao->analise?->protocolo
            ?? throw ValidationException::withMessages(['dilacao' => 'Notificacao sem protocolo vinculado.']);

        return DB::transaction(function () use ($notificacao, $dias, $justificativa, $user, $protocolo): PaeDilacao {
            $dilacao = PaeDilacao::create([
                'protocolo_id' => $protocolo->id,
                'pae_notificacao_id' => $notificacao->id,
                'status' => PaeDilacao::STATUS_APROVADA,
                'dias_adicionais' => $dias,
                'justificativa' => $justificativa,
                'aprovado_por' => $user->id,
            ]);

            $notificacao->load('dilacoes');
            $vencimento = PrazoNotificacao::vencimento($notificacao->dt_notificacao, $notificacao->diasDilacao());

            if (! PrazoNotificacao::vencida($notificacao->dt_notificacao, $notificacao->diasDilacao(), null)) {
                PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
            }

            TimelinePae::registrar(
                $protocolo,
                'dilacao',
                "Dilacao de {$dias} dias registrada para a notificacao SEI {$notificacao->num_sei}. "
                    ."Novo vencimento: {$vencimento->format('d/m/Y')}. Justificativa: {$justificativa}",
                $user
            );

            $this->prazos->recalcular($protocolo);

            return $dilacao;
        });
    }
```

8. `processarVencimentos()`: substituir o corpo inteiro por:

```php
    public function processarVencimentos(): int
    {
        $processadas = 0;

        $analises = PaeAnalise::query()
            ->whereHas('notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->with(['notificacoes.dilacoes', 'protocolo.analistaAtual', 'protocolo.usuario', 'protocolo.empreendimento'])
            ->get();

        foreach ($analises as $analise) {
            $protocolo = $analise->protocolo;

            if (! $protocolo || $protocolo->arquivado || $protocolo->status->isTerminal()
                || $protocolo->status === PaeProtocoloStatus::SUSPENSO) {
                continue;
            }

            $ultima = $analise->notificacoes->last();

            if (! $ultima || ! PrazoNotificacao::vencida($ultima->dt_notificacao, $ultima->diasDilacao(), $ultima->dt_devolutiva)) {
                continue;
            }

            $autor = $protocolo->analistaAtual ?? $protocolo->usuario;
            $ciclo = $analise->notificacoes->count();

            if ($ciclo >= self::MAX_CICLOS_AUTOMATICOS) {
                // Sinaliza uma vez; a decisao (suspender, reprovar, notificar) e da CEDEC (Art. 139).
                if ($protocolo->ciclos_esgotados_em === null) {
                    PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => now()->toDateString()]);
                    TimelinePae::registrar(
                        $protocolo,
                        'ciclos_esgotados',
                        "Notificacao {$ciclo} vencida sem devolutiva. Ciclos automaticos esgotados: aguardando decisao da CEDEC.",
                        $autor
                    );
                    $this->avisarCiclosEsgotados($protocolo, $ciclo);
                    $processadas++;
                }

                continue;
            }

            $this->emitir(
                $protocolo,
                $autor,
                [
                    'num_sei' => $ultima->num_sei,
                    'obs' => 'Emitida automaticamente: ciclo '.$ciclo.' vencido sem devolutiva.',
                ],
                true
            );
            $processadas++;
        }

        return $processadas;
    }
```

9. `listarPorProtocolo()`: trocar o `with('notificacoes')` por `with('notificacoes.dilacoes.aprovador:id,name')` e o `map` por:

```php
            ->map(function (PaeNotificacao $n, int $i): array {
                $dias = $n->diasDilacao();

                return [
                    'id' => $n->id,
                    'ciclo' => $i + 1,
                    'num_sei' => $n->num_sei,
                    'dt_notificacao' => $n->dt_notificacao->toDateString(),
                    'prazo_final' => PrazoNotificacao::vencimento($n->dt_notificacao, $dias)->toDateString(),
                    'dias_dilacao' => $dias,
                    'dt_devolutiva' => $n->dt_devolutiva?->toDateString(),
                    'vencida' => PrazoNotificacao::vencida($n->dt_notificacao, $dias, $n->dt_devolutiva),
                    'obs' => $n->obs,
                    'dilacoes' => $n->dilacoes->map(fn (PaeDilacao $d): array => [
                        'id' => $d->id,
                        'dias_adicionais' => $d->dias_adicionais,
                        'justificativa' => $d->justificativa,
                        'aprovado_por' => $d->aprovador?->name,
                        'registrada_em' => $d->created_at?->toDateString(),
                    ])->values()->all(),
                ];
            })
```

10. `enviarEmail()`: trocar `prazoFinal: $notificacao->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->toDateString()` por `prazoFinal: PrazoNotificacao::vencimento($notificacao->dt_notificacao)->toDateString()`. Trocar também o `registrarTimeline` por `TimelinePae::registrar`.
11. Apagar o método privado `registrarTimeline`.
12. Imports:
    - acrescentar `PaeDilacao`, `Datas`, `PrazoNotificacao` e `TimelinePae` (`App\Modules\Pae\...`), mais `App\Modules\Pae\Services\PaePrazoService`;
    - remover `PaeTimeline`, se ficar sem uso.

- [ ] **Step 4: Request, controller, rota, Resource e comando**

`SDC/app/Modules/Pae/Requests/RegistrarDilacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDilacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dias_adicionais' => ['required', 'integer', 'min:1', 'max:365'],
            'justificativa' => ['required', 'string', 'max:2000'],
        ];
    }
}
```

`SDC/app/Modules/Pae/Controllers/PaeNotificacaoController.php`, novo método:

```php
    public function dilacao(RegistrarDilacaoRequest $request, PaeNotificacao $paeNotificacao): RedirectResponse
    {
        $dados = $request->validated();
        $this->service->registrarDilacao(
            $paeNotificacao,
            (int) $dados['dias_adicionais'],
            $dados['justificativa'],
            $request->user()
        );

        return back()->with('success', 'Dilacao registrada e prazo recalculado.');
    }
```

No mesmo arquivo, `store()` passa a dizer `"... emitida com prazo de ".PrazoNotificacao::PRAZO_DIAS." dias."`. Imports: `RegistrarDilacaoRequest` e `PrazoNotificacao`.

`SDC/routes/modules/pae.php`, depois da rota `notificacoes.devolutiva`:

```php
    Route::post('/notificacoes/{paeNotificacao}/dilacoes', [PaeNotificacaoController::class, 'dilacao'])
        ->name('notificacoes.dilacoes.store')
        ->middleware('can:pae.protocolos.edit');
```

`SDC/app/Http/Resources/Pae/PaeNotificacaoResource.php`: trocar o cálculo de `$prazoFinal` e de `vencida` por:

```php
        $dias = $this->resource->diasDilacao();
        $prazoFinal = PrazoNotificacao::vencimento($this->dt_notificacao, $dias);
```

```php
            'vencida'        => PrazoNotificacao::vencida($this->dt_notificacao, $dias, $this->dt_devolutiva),
```

Com o import `use App\Modules\Pae\Support\PrazoNotificacao;`. Remover o import de `PaeNotificacaoService`, se ficar sem uso.

`SDC/app/Modules/Pae/Console/VerificarNotificacoesPae.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Console;

use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Services\PaePrazoService;
use Illuminate\Console\Command;

class VerificarNotificacoesPae extends Command
{
    protected $signature = 'pae:verificar-notificacoes';

    protected $description = 'Processa os vencimentos das notificacoes PAE (Art. 11): renova ate o 3o ciclo, sinaliza ciclos esgotados para decisao da CEDEC e recalcula o prazo de analise das diligencias abertas (Art. 9).';

    public function handle(PaeNotificacaoService $notificacoes, PaePrazoService $prazos): int
    {
        $processadas = $notificacoes->processarVencimentos();
        $recalculados = $prazos->recalcularDiligenciasAbertas();

        $this->info("Notificacoes PAE processadas: {$processadas}. Prazos recalculados: {$recalculados}.");

        return self::SUCCESS;
    }
}
```

Apagar o comando antigo: `git rm SDC/app/Console/Commands/VerificarNotificacoesPae.php`.

`SDC/app/Modules/Pae/PaeServiceProvider.php`: acrescentar o `boot()` e o import `use App\Modules\Pae\Console\VerificarNotificacoesPae;`.

```php
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([VerificarNotificacoesPae::class]);
        }
    }
```

O agendamento em `SDC/routes/console.php:78` não muda, porque usa a assinatura.

- [ ] **Step 5: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae`
Expected: PASS em tudo, inclusive `Api/NotificacaoApiTest`.

Run: `$PT artisan list pae`
Expected: lista `pae:verificar-notificacoes`.

### Task 7: Emissão do CCPAE

**Files:**
- Create:
  - `SDC/app/Modules/Pae/Services/PaeCcpaeService.php`
  - `SDC/app/Modules/Pae/DTOs/EmitirCcpaeDTO.php`
  - `SDC/app/Modules/Pae/Requests/EmitirCcpaeRequest.php`
- Modify:
  - `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php` (`emitirCcpae`)
  - `SDC/routes/modules/pae.php`
  - `SDC/app/Modules/Pae/PaeServiceProvider.php` (singleton)
- Test: `SDC/tests/Feature/Pae/PaeCcpaeTest.php`

**Interfaces:**
- Consumes:
  - `VigenciaCcpae` (Tarefa 2)
  - `PaeCcpae` (Tarefa 3)
  - `PaeProtocoloWorkflow::transitar` e `ContextoTransicao::emissaoCcpae()` (Tarefa 4)
- Produces:
  - `PaeCcpaeService::emitir(PaeProtocolo $protocolo, EmitirCcpaeDTO $dados, User $user): PaeCcpae`
  - `EmitirCcpaeDTO`, com `codigo`, `dtEmissao`, `empreendimentoNovo` e `dtLicencaOperacao`, e `fromArray(array $validated)`
  - Rota `POST /pae/protocolo/{paeProtocolo}/ccpae`, nome `pae.protocolo.ccpae.store`. Redireciona para `pae.protocolos.index` com `status=ccpae`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Modules\Pae\DTOs\EmitirCcpaeDTO;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeCcpaeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeCcpaeTest extends TestCase
{
    use DatabaseTransactions;

    private function dto(string $codigo = 'TST-CCPAE-0001', bool $novo = false, ?string $lo = null): EmitirCcpaeDTO
    {
        return EmitirCcpaeDTO::fromArray([
            'codigo' => $codigo,
            'dt_emissao' => '2026-10-05',
            'empreendimento_novo' => $novo,
            'dt_licenca_operacao' => $lo,
        ]);
    }

    public function test_emitir_registra_certificado_colunas_e_status(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'aprovado']);

        $ccpae = app(PaeCcpaeService::class)->emitir($p, $this->dto(), User::factory()->create());

        $this->assertSame('2029-10-05', $ccpae->dt_vencimento->toDateString());
        $p->refresh();
        $this->assertSame(PaeProtocoloStatus::CCPAE, $p->status);
        $this->assertSame('TST-CCPAE-0001', $p->ccpae);
        $this->assertSame('2029-10-05', $p->ccpae_venc->toDateString());
        $this->assertDatabaseHas('pae_timeline', ['protocolo_id' => $p->id, 'evento' => 'ccpae']);
    }

    public function test_empreendimento_novo_vence_pela_lo(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'aprovado']);

        $ccpae = app(PaeCcpaeService::class)->emitir($p, $this->dto('TST-CCPAE-0002', true, '2026-03-10'), User::factory()->create());

        $this->assertSame('2029-03-10', $ccpae->dt_vencimento->toDateString());
        $this->assertSame('2026-03-10', $ccpae->dt_licenca_operacao->toDateString());
    }

    public function test_validacao_de_protocolo_arquivado_desarquiva(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'gerenciamento', 'arquivado' => true]);

        app(PaeCcpaeService::class)->emitir($p, $this->dto('TST-CCPAE-0003'), User::factory()->create());

        $this->assertFalse($p->fresh()->arquivado);
    }

    public function test_emitir_duas_vezes_e_recusado(): void
    {
        $p = PaeProtocolo::factory()->create(['status' => 'aprovado']);
        $user = User::factory()->create();
        app(PaeCcpaeService::class)->emitir($p, $this->dto('TST-CCPAE-0004'), $user);

        try {
            app(PaeCcpaeService::class)->emitir($p->fresh(), $this->dto('TST-CCPAE-0005'), $user);
            $this->fail('Segunda emissao deveria ser recusada.');
        } catch (ValidationException) {
            $this->assertSame(1, PaeCcpae::where('protocolo_id', $p->id)->count());
        }
    }

    public function test_rota_recusa_codigo_duplicado_e_novo_sem_lo(): void
    {
        Permission::firstOrCreate(['name' => 'pae.protocolos.edit', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.edit');
        $existente = PaeProtocolo::factory()->create(['status' => 'aprovado']);
        app(PaeCcpaeService::class)->emitir($existente, $this->dto('TST-CCPAE-0006'), $user);
        $p = PaeProtocolo::factory()->create(['status' => 'aprovado']);

        $this->actingAs($user)
            ->post(route('pae.protocolo.ccpae.store', $p), [
                'codigo' => 'TST-CCPAE-0006', 'dt_emissao' => '2026-10-05', 'empreendimento_novo' => true,
            ])
            ->assertSessionHasErrors(['codigo', 'dt_licenca_operacao']);

        $this->actingAs($user)
            ->post(route('pae.protocolo.ccpae.store', $p), [
                'codigo' => 'TST-CCPAE-0007', 'dt_emissao' => '2026-10-05', 'empreendimento_novo' => false,
            ])
            ->assertRedirect(route('pae.protocolos.index', ['status' => 'ccpae']));
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeCcpaeTest.php`
Expected: FAIL, `Class "App\Modules\Pae\DTOs\EmitirCcpaeDTO" not found`

- [ ] **Step 3: Implementar**

`SDC/app/Modules/Pae/DTOs/EmitirCcpaeDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

use Carbon\CarbonImmutable;

readonly class EmitirCcpaeDTO
{
    public function __construct(
        public string $codigo,
        public CarbonImmutable $dtEmissao,
        public bool $empreendimentoNovo,
        public ?CarbonImmutable $dtLicencaOperacao,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            codigo: trim((string) $data['codigo']),
            dtEmissao: CarbonImmutable::parse($data['dt_emissao'])->startOfDay(),
            empreendimentoNovo: filter_var($data['empreendimento_novo'] ?? false, FILTER_VALIDATE_BOOL),
            dtLicencaOperacao: empty($data['dt_licenca_operacao'])
                ? null
                : CarbonImmutable::parse($data['dt_licenca_operacao'])->startOfDay(),
        );
    }
}
```

`SDC/app/Modules/Pae/Requests/EmitirCcpaeRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmitirCcpaeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:100', Rule::unique('pae_ccpae', 'codigo')],
            'dt_emissao' => ['required', 'date', 'before_or_equal:today'],
            'empreendimento_novo' => ['required', 'boolean'],
            'dt_licenca_operacao' => [
                Rule::requiredIf(fn (): bool => $this->boolean('empreendimento_novo')),
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ja existe um CCPAE com este codigo.',
            'dt_licenca_operacao.required' => 'Empreendimento novo: informe a data da Licenca de Operacao (Art. 4).',
        ];
    }
}
```

`SDC/app/Modules/Pae/Services/PaeCcpaeService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\DTOs\EmitirCcpaeDTO;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Modules\Pae\Support\VigenciaCcpae;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Emissao do CCPAE (Resolucao GMG 83/2024, Arts. 4, 5 e 132-139): registra o
 * certificado, a vigencia de 3 anos e leva o protocolo a CCPAE pelo workflow.
 * Unico caminho para o status CCPAE (guard ExigeEmissaoCcpae).
 */
final class PaeCcpaeService
{
    public function __construct(
        private readonly PaeProtocoloWorkflow $workflow,
    ) {}

    public function emitir(PaeProtocolo $protocolo, EmitirCcpaeDTO $dados, User $user): PaeCcpae
    {
        if (in_array($protocolo->status, [PaeProtocoloStatus::CCPAE, PaeProtocoloStatus::ATIVO_3_ANOS], true)) {
            throw ValidationException::withMessages(['ccpae' => 'Este protocolo ja tem CCPAE emitido.']);
        }

        try {
            $vencimento = VigenciaCcpae::vencimento($dados->dtEmissao, $dados->empreendimentoNovo, $dados->dtLicencaOperacao);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['dt_licenca_operacao' => $e->getMessage()]);
        }

        try {
            return DB::transaction(function () use ($protocolo, $dados, $user, $vencimento): PaeCcpae {
                $this->workflow->transitar(
                    $protocolo,
                    PaeProtocoloStatus::CCPAE,
                    $user,
                    "CCPAE {$dados->codigo} emitido.",
                    ContextoTransicao::emissaoCcpae(),
                );

                $ccpae = PaeCcpae::create([
                    'protocolo_id' => $protocolo->id,
                    'codigo' => $dados->codigo,
                    'dt_emissao' => $dados->dtEmissao->toDateString(),
                    'dt_vencimento' => $vencimento->toDateString(),
                    'status' => PaeCcpae::STATUS_ATIVO,
                    'dt_licenca_operacao' => $dados->dtLicencaOperacao?->toDateString(),
                    'emitido_por' => $user->id,
                ]);

                PaeProtocolo::query()->whereKey($protocolo->getKey())->update([
                    'ccpae' => $dados->codigo,
                    'ccpae_venc' => $vencimento->toDateString(),
                ]);

                $base = $dados->empreendimentoNovo
                    ? "3 anos da Licenca de Operacao de {$dados->dtLicencaOperacao->format('d/m/Y')} (Art. 4)"
                    : '3 anos da emissao (Art. 5)';
                TimelinePae::registrar(
                    $protocolo,
                    'ccpae',
                    "CCPAE {$dados->codigo} emitido em {$dados->dtEmissao->format('d/m/Y')}, vigente ate {$vencimento->format('d/m/Y')}: {$base}.",
                    $user
                );

                return $ccpae;
            });
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }
}
```

`SDC/app/Modules/Pae/PaeServiceProvider.php`: `$this->app->singleton(PaeCcpaeService::class);`, com o import.

`SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php`: acrescentar `private readonly PaeCcpaeService $ccpae,` ao construtor e o método:

```php
    public function emitirCcpae(EmitirCcpaeRequest $request, PaeProtocolo $paeProtocolo): \Illuminate\Http\RedirectResponse
    {
        $ccpae = $this->ccpae->emitir($paeProtocolo, EmitirCcpaeDTO::fromArray($request->validated()), $request->user());

        return redirect()->route('pae.protocolos.index', ['status' => 'ccpae'])
            ->with('success', "CCPAE {$ccpae->codigo} emitido, vigente ate {$ccpae->dt_vencimento->format('d/m/Y')}.");
    }
```

Imports: `EmitirCcpaeRequest`, `EmitirCcpaeDTO` e `PaeCcpaeService`.

`SDC/routes/modules/pae.php`, depois da rota `protocolo.notificacao-feam`:

```php
    Route::post('/protocolo/{paeProtocolo}/ccpae', [PaeProtocoloController::class, 'emitirCcpae'])
        ->name('protocolo.ccpae.store')
        ->middleware('can:pae.protocolos.edit');
```

- [ ] **Step 4: Rodar e ver passar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeCcpaeTest.php`
Expected: PASS (5 testes)

### Task 8: Listagem, filtros, estatísticas e histórico

**Files:**
- Modify:
  - `SDC/app/Modules/Pae/Services/PaeProtocoloService.php` (`list`, `getStatistics`)
  - `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php` (`index`, `historico`)
- Test: `SDC/tests/Feature/Pae/PaeProtocoloListagemTest.php`

**Interfaces:**
- Consumes:
  - `PaePrazoService::anotarListagem` e `resumo` (Tarefa 5)
  - `PaeNotificacaoService::listarPorProtocolo` (Tarefa 6)
  - `PaeProtocolo::scopeVencidos` (Tarefa 3)
- Produces:
  - Cada linha de `protocolos.data` ganha `prazo_situacao`, `fora_do_prazo`, `dt_notificacao_feam` e `dt_notificacao_feam_estimada`
  - `statistics.ciclos_esgotados`
  - Filtros: `status_grupo` (`vencidos`, `historico`, `ciclos_esgotados`) e `arquivado`, que hoje são ignorados pelo `index`
  - `historico` JSON: `notificacoes` passa a ser a lista real de `listarPorProtocolo` (e não as tramitações), mais o novo bloco `prazos`
  - Novos tipos na timeline do histórico:
    - `ciclos_esgotados` → `alerta`
    - `dilacao` → `notificacao`
    - `prazo` → `edicao`
    - `ccpae` → `analise`

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaeProtocoloListagemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_filtro_vencidos_e_ciclos_esgotados(): void
    {
        $vencido = PaeProtocolo::factory()->create(['status' => 'notificacao', 'limite_analise' => '2020-01-01']);
        $emDia = PaeProtocolo::factory()->create(['status' => 'notificacao', 'limite_analise' => '2099-01-01']);
        $esgotado = PaeProtocolo::factory()->create(['status' => 'notificacao', 'ciclos_esgotados_em' => '2026-10-01']);

        $service = app(PaeProtocoloService::class);
        $vencidos = collect($service->list(['status_grupo' => 'vencidos'], 500)->items())->pluck('id');
        $esgotados = collect($service->list(['status_grupo' => 'ciclos_esgotados'], 500)->items())->pluck('id');

        $this->assertTrue($vencidos->contains($vencido->id));
        $this->assertFalse($vencidos->contains($emDia->id));
        $this->assertTrue($esgotados->contains($esgotado->id));
        $this->assertGreaterThanOrEqual(1, $service->getStatistics()['ciclos_esgotados']);
    }

    public function test_historico_traz_notificacoes_reais_e_prazos(): void
    {
        Mail::fake();
        Permission::firstOrCreate(['name' => 'pae.protocolos.view', 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo('pae.protocolos.view');
        $p = PaeProtocolo::factory()->create([
            'status' => 'notificacao',
            'analista_atual_id' => $user->id,
            'dt_notificacao_feam' => now()->subDays(10)->toDateString(),
        ]);
        app(PaeNotificacaoService::class)->emitir($p, $user, ['num_sei' => 'TST-SEI-1']);

        $json = $this->actingAs($user)->getJson(route('pae.protocolos.historico', $p))->assertOk()->json();

        $this->assertSame('TST-SEI-1', $json['notificacoes'][0]['num_sei']);
        $this->assertSame('pausado', $json['prazos']['situacao']);
        $this->assertSame(now()->subDays(10)->toDateString(), $json['prazos']['dt_notificacao_feam']);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae/PaeProtocoloListagemTest.php`
Expected: FAIL (o filtro é ignorado, `ciclos_esgotados` não existe nas estatísticas e o histórico não tem `prazos`)

- [ ] **Step 3: Implementar**

`PaeProtocoloService::list()`:

1. O eager load passa a ser:

```php
        $query = PaeProtocolo::query()
            ->with([
                'analistaAtual:id,name',
                'empreendimento:id,pae_empdor_id,nome',
                'empreendimento.empdor:id,nome',
                'analise:id,pae_protocolo_id',
                'analise.notificacoes:id,pae_analise_id,dt_notificacao,dt_devolutiva',
            ]);
```

2. Logo depois do filtro `status`:

```php
        switch ($filters['status_grupo'] ?? null) {
            case 'vencidos':
                $query->vencidos();
                break;
            case 'historico':
                $query->whereIn('status', [
                    PaeProtocoloStatus::APROVADO->value,
                    PaeProtocoloStatus::CCPAE->value,
                    PaeProtocoloStatus::ATIVO_3_ANOS->value,
                ]);
                break;
            case 'ciclos_esgotados':
                $query->whereNotNull('ciclos_esgotados_em');
                break;
        }
```

`PaeProtocoloService::getStatistics()`: trocar a chave `vencidos` pelo scope e acrescentar `ciclos_esgotados`:

```php
            'vencidos' => $base()->vencidos()->count(),
            'ciclos_esgotados' => $base()->whereNotNull('ciclos_esgotados_em')->count(),
```

`PaeProtocoloController`:

1. Construtor: acrescentar `private readonly PaeNotificacaoService $notificacoes,` (import `App\Modules\Pae\Services\PaeNotificacaoService`).
2. Em `index()`:

```php
        $filters = $request->only(['search', 'status', 'status_grupo', 'arquivado', 'analista_id', 'data_inicio', 'data_fim']);
```

```php
        $protocolos = $this->prazos->anotarListagem($this->service->list($filters));
```

3. Em `historico()`:
   - Acrescentar ao `$eventoMap`:

```php
            'ciclos_esgotados' => ['tipo' => 'alerta',     'titulo' => 'Ciclos de Notificação Esgotados'],
            'dilacao'          => ['tipo' => 'notificacao', 'titulo' => 'Dilação Registrada'],
            'prazo'            => ['tipo' => 'edicao',      'titulo' => 'Prazo Atualizado'],
            'ccpae'            => ['tipo' => 'analise',     'titulo' => 'CCPAE Emitido'],
            'status_alterado'  => ['tipo' => 'status',      'titulo' => 'Status Alterado'],
```

   - Apagar o bloco `$notificacoes = $protocolo->tramitacoes->where(...)...->values();`.
   - O `return` passa a ser:

```php
        return response()->json([
            'protocolo'    => $protocolo->num_protocolo,
            'analises'     => $analises,
            'notificacoes' => $this->notificacoes->listarPorProtocolo($protocolo),
            'prazos'       => $this->prazos->resumo($protocolo),
            'timeline'     => $timeline,
        ]);
```

- [ ] **Step 4: Rodar a suíte PAE inteira**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae tests/Unit/Pae tests/Unit/Support`
Expected: PASS em tudo.

- [ ] **Step 5: Commit da Fase 3**

```bash
cd "$WT"
git add SDC/app/Modules/Pae/Services/PaePrazoService.php SDC/app/Modules/Pae/Services/PaeCcpaeService.php \
  SDC/app/Modules/Pae/Services/PaeNotificacaoService.php SDC/app/Modules/Pae/Services/PaeProtocoloService.php \
  SDC/app/Modules/Pae/DTOs/EmitirCcpaeDTO.php \
  SDC/app/Modules/Pae/Requests/AtualizarNotificacaoFeamRequest.php SDC/app/Modules/Pae/Requests/RegistrarDilacaoRequest.php \
  SDC/app/Modules/Pae/Requests/EmitirCcpaeRequest.php \
  SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php SDC/app/Modules/Pae/Controllers/PaeNotificacaoController.php \
  SDC/app/Modules/Pae/Console/VerificarNotificacoesPae.php SDC/app/Modules/Pae/PaeServiceProvider.php \
  SDC/app/Http/Resources/Pae/PaeNotificacaoResource.php SDC/routes/modules/pae.php
git status --short   # o rm do comando antigo ja esta no stage; nenhum tests/ no stage
git commit -m "✨ feat(pae): prazos legais, emissao do CCPAE, dilacao e 3o ciclo sinalizado sem suspensao"
```

---

## Fase 4 -- Tela

### Task 9: Listagem -- prazo do servidor, card "Ciclos esgotados" e remoção dos mocks

**Files:**
- Modify:
  - `SDC/resources/js/Components/Molecules/Pae/Protocolos/PrazosPill.vue`
  - `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue:41`
  - `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue:46`
  - `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue`
  - `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`
  - `SDC/resources/js/Pages/PaeProtocolosIndex.vue`
- Delete:
  - `SDC/resources/js/mocks/pae.js`
  - `SDC/resources/js/infrastructure/pae/MockPaeProtocoloRepository.js`
  - `SDC/resources/js/domain/pae/usecases/ListPaeProtocolos.js`

**Interfaces:**
- Consumes: os campos de linha e a estatística da Tarefa 8
- Produces:
  - `PrazosPill` com as props `prazo` (`ok|proximo|vencido|pausado|sem_data`) e `estimado` (Boolean)
  - linha mapeada com `prazo` e `prazoEstimado`
  - evento `ciclos` no `PaeProtocolosStatsCards`

- [ ] **Step 1: `PrazosPill.vue`**

Substituir o arquivo:

```vue
<template>
  <!-- rounded (e nao rounded-full) e size sm: esta pill e menor que as de status,
       porque aparece encostada na data dentro da celula. -->
  <span v-if="prazoLabel || estimado" class="inline-flex flex-wrap items-center gap-1" :class="props.class">
    <Badge v-if="prazoLabel" :variant="variant" size="sm" :rounded="false">
      {{ prazoLabel }}
    </Badge>
    <Badge
      v-if="estimado"
      variant="neutral"
      size="sm"
      :rounded="false"
      title="Data da notificacao da FEAM estimada pela data de entrada. Informe a data real no historico do protocolo."
    >
      Estimado
    </Badge>
  </span>
</template>

<script setup>
/**
 * Situacao do prazo de analise do protocolo PAE (Art. 9), calculada no servidor
 * (PaePrazoService). "Estimado" marca a data da FEAM inferida no backfill.
 */
import { computed } from 'vue';
import Badge from '../../../Atoms/Badge/Badge.vue';

const props = defineProps({
  prazo: {
    type: String,
    default: 'ok', // ok|proximo|vencido|pausado|sem_data
  },
  estimado: {
    type: Boolean,
    default: false,
  },
  class: {
    type: String,
    default: '',
  },
});

const map = {
  proximo: { label: 'Próximo', variant: 'warning' },
  vencido: { label: 'Vencido', variant: 'danger' },
  pausado: { label: 'Pausado', variant: 'info' },
  sem_data: { label: 'Sem data FEAM', variant: 'neutral' },
};

const prazoLabel = computed(() => map[props.prazo]?.label || '');
const variant = computed(() => map[props.prazo]?.variant ?? 'default');
</script>
```

Em `PaeProtocoloCard.vue:41` e `PaeProtocolosTable.vue:46`, acrescentar `:estimado="protocolo.prazoEstimado"` ao `<PrazosPill ...>`.

- [ ] **Step 2: `PaeProtocolosStatsCards.vue`**

Acrescentar o quinto card depois de "Vencidos" e passar `:colunas="5"` ao `StatCardsGrid`:

```vue
    <StatCard
      title="Ciclos esgotados"
      :value="stats.ciclos_esgotados"
      variant="danger"
      :icon="ExclamationTriangleIcon"
      clickable
      @click="$emit('ciclos')"
    />
```

No script, o default de `stats` ganha `ciclos_esgotados: 0`, e o `defineEmits` passa a `['total', 'historico', 'vencidos', 'ciclos', 'ccpae']`.

- [ ] **Step 3: Template, sem mocks**

Em `PaeProtocolosIndexTemplate.vue`:

1. `<PaeProtocolosStatsCards ...>`: acrescentar `@ciclos="handleCiclosEsgotados"`.
2. Remover os imports `ListPaeProtocolos`, `MockPaeProtocoloRepository` e o bloco `import {...} from '@/mocks/pae'`, além da prop `useMock`.
3. Substituir o trecho que vai de `// ── Repositórios` até o fim de `handlePageChange` por:

```js
// ── Repositorio ─────────────────────────────────────────────
const historicoUsecase = new GetPaeProtocoloHistorico(new ApiPaeProtocoloRepository());
const { toast } = useToast();

const canAtribuirComputed = computed(() => props.canAtribuir);
const isExternalView = computed(() => (
  !props.canCreate &&
  !props.canEdit &&
  !props.canDelete &&
  !props.canExport &&
  !props.canAtribuir
));

// ── Helpers para mapear dados reais ao shape esperado pelos componentes ──────
function normalizeDateBR(dateStr) {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
}

function mapProtocolo(p) {
  const limiteISO = p.limite_analise ?? null;
  const arquivado = !!p.arquivado;
  return {
    id: p.id,
    protocoloNumero: p.num_protocolo ?? '',
    empreendedor: p.empreendimento?.empdor?.nome ?? 'N/A',
    estrutura: p.empreendimento?.nome ?? '',
    analista: p.analista_atual?.name ?? 'Não atribuído',
    analista_atual_id: p.analista_atual_id ?? null,
    situacao: arquivado ? 'arquivado' : (p.status ?? ''),
    dataEntrada: normalizeDateBR(p.dt_entrada),
    limiteAnalise: normalizeDateBR(limiteISO),
    limiteAnaliseISO: limiteISO,
    prazo: p.prazo_situacao ?? 'ok',
    prazoEstimado: !!p.dt_notificacao_feam_estimada,
    foraDoPrazo: !!p.fora_do_prazo,
    ccpae: !!p.ccpae,
    arquivado,
  };
}

const situacoes = computed(() => [
  { value: '', label: 'Todas as situações' },
  ...Object.entries(props.statusOptions ?? {}).map(([value, label]) => ({ value, label })),
]);

const analistas = computed(() => props.analistas ?? []);
const empreendedores = computed(() => props.empreendedores ?? []);
const filters = computed(() => props.filters ?? {});

const filteredProtocolos = computed(() => (props.protocolos?.data ?? []).map(mapProtocolo));
const paginatedProtocolos = filteredProtocolos;

const statsToUse = computed(() => {
  const s = props.statistics ?? {};
  return {
    total: s.total ?? 0,
    historico: (s.aprovado ?? 0) + (s.ccpae ?? 0) + (s.ativo_3_anos ?? 0),
    vencidos: s.vencidos ?? 0,
    ciclos_esgotados: s.ciclos_esgotados ?? 0,
    ccpae: s.ccpae ?? 0,
  };
});

const paginationToUse = computed(() => (props.protocolos
  ? {
      current_page: props.protocolos.current_page,
      last_page: props.protocolos.last_page,
      per_page: props.protocolos.per_page,
      total: props.protocolos.total,
    }
  : null));

function visitar(params, preserveState = false) {
  router.get(route('pae.protocolos.index'), params, { preserveState, replace: true });
}

function handleFilterChange(next) {
  visitar({ ...filters.value, ...(next || {}) }, true);
}

function handleFilterReset() {
  visitar({});
}

function handleCcpaeFilter() {
  visitar({ status: 'ccpae' });
}

function handleArquivadosFilter() {
  visitar({ arquivado: 1 });
}

function handleTotalProtocolos() {
  visitar({});
}

function handleHistoricoProtocolos() {
  visitar({ status_grupo: 'historico' });
}

function handleVencidosProtocolos() {
  visitar({ status_grupo: 'vencidos' });
}

function handleCiclosEsgotados() {
  visitar({ status_grupo: 'ciclos_esgotados' });
}

function handlePageChange(page) {
  visitar({ ...filters.value, page }, true);
}
```

O `timeZone: 'UTC'` é necessário porque a data `2026-10-05T00:00:00Z` em `America/Sao_Paulo` aparecia como o dia anterior.

4. Remover as declarações que ficaram órfãs: `perPage`, `currentPage`, `mockFilters`, `allProtocolos` e o bloco `if (props.useMock) {...}`. Depois, `grep -n "useMock\|mockFilters\|getMockPaeStats\|paeSituacoes" SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue` deve voltar vazio.

`Pages/PaeProtocolosIndex.vue`: apagar a linha `:use-mock="false"`.

Apagar os arquivos de mock:

```bash
cd "$WT/SDC/resources/js" && grep -rln "mocks/pae\|MockPaeProtocoloRepository\|ListPaeProtocolos" . | grep -v "^./mocks/pae.js$\|MockPaeProtocoloRepository.js$\|ListPaeProtocolos.js$"
```

Expected: nenhuma saída. Então:

```bash
git rm SDC/resources/js/mocks/pae.js SDC/resources/js/infrastructure/pae/MockPaeProtocoloRepository.js SDC/resources/js/domain/pae/usecases/ListPaeProtocolos.js
```

(rodar a partir de `$WT`)

- [ ] **Step 4: Build**

Run: `cd "$WT/SDC" && npx vite build > /c/tmp/pae-gmg83-build.log 2>&1; tail -3 /c/tmp/pae-gmg83-build.log`
Expected: build concluído, sem `Could not resolve` nem `is not exported`.

Commit no fim da Fase 4 (Tarefa 11).

### Task 10: Modal de emissão do CCPAE

**Files:**
- Create: `SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue`
- Modify: `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue` (troca o `ConfirmDialog` de CCPAE)

**Interfaces:**
- Consumes: a rota `pae.protocolo.ccpae.store` (Tarefa 7)
- Produces: `<EmitirCcpaeModal :show :protocolo @close />`, em que `protocolo` tem `{id, protocoloNumero, arquivado}`

- [ ] **Step 1: Criar o organism**

```vue
<template>
  <Modal :show="show" max-width="md" align="center" @close="fechar">
    <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white text-left shadow-2xl dark:border-slate-800 dark:bg-slate-900">
      <div class="border-b border-emerald-800/30 bg-gradient-to-r from-emerald-900 to-emerald-700 px-6 py-5">
        <h3 class="text-lg font-bold text-white">
          {{ protocolo?.arquivado ? 'Reativar e emitir CCPAE' : 'Emitir CCPAE' }}
        </h3>
        <p class="text-sm text-emerald-100/80">Protocolo #{{ protocolo?.protocoloNumero || 'N/D' }}</p>
      </div>

      <div class="space-y-4 px-6 py-6">
        <p v-if="protocolo?.arquivado" class="text-sm text-amber-700 dark:text-amber-300">
          Este protocolo esta arquivado e sera desarquivado automaticamente.
        </p>

        <FormField v-model="form.codigo" label="Codigo do CCPAE" required :error="form.errors.codigo" />
        <FormDateField v-model="form.dt_emissao" label="Data de emissao" required :error="form.errors.dt_emissao" />
        <ToggleField
          v-model="form.empreendimento_novo"
          label="Empreendimento novo"
          description="A vigencia conta da Licenca de Operacao (Art. 4). Sem LO anterior ao PAE, conta da emissao (Art. 5)."
        />
        <FormDateField
          v-if="form.empreendimento_novo"
          v-model="form.dt_licenca_operacao"
          label="Data da Licenca de Operacao"
          required
          :error="form.errors.dt_licenca_operacao"
        />

        <p class="text-sm text-slate-600 dark:text-slate-300">
          Vigente ate: <span class="font-semibold">{{ vencimentoPrevisto || '—' }}</span>
        </p>
        <InputError v-if="form.errors.status || form.errors.ccpae" :message="form.errors.status || form.errors.ccpae" />
      </div>

      <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950">
        <Button variant="secondary" :disabled="form.processing" @click="fechar">Cancelar</Button>
        <Button variant="success" :disabled="form.processing || !form.codigo" @click="emitir">
          {{ form.processing ? 'Emitindo...' : 'Emitir CCPAE' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Emissao do CCPAE (Resolucao GMG 83/2024, Arts. 4 e 5). Unico caminho para o
 * status CCPAE: o backend recusa a troca generica de status (ExigeEmissaoCcpae).
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  protocolo: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const hojeISO = () => new Date().toISOString().slice(0, 10);

const form = useForm({
  codigo: '',
  dt_emissao: hojeISO(),
  empreendimento_novo: false,
  dt_licenca_operacao: '',
});

/** Espelho de VigenciaCcpae: base + 3 anos; 29/02 cai em 28/02. */
function somarTresAnos(iso) {
  if (!iso) return null;
  const [ano, mes, dia] = iso.split('-').map(Number);
  const alvo = ano + 3;
  const ultimoDia = new Date(Date.UTC(alvo, mes, 0)).getUTCDate();
  const d = Math.min(dia, ultimoDia);
  return `${String(d).padStart(2, '0')}/${String(mes).padStart(2, '0')}/${alvo}`;
}

const vencimentoPrevisto = computed(() => somarTresAnos(
  form.empreendimento_novo ? form.dt_licenca_operacao : form.dt_emissao,
));

watch(() => props.show, (aberto) => {
  if (aberto) {
    form.reset();
    form.clearErrors();
    form.dt_emissao = hojeISO();
  }
});

function fechar() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function emitir() {
  if (!props.protocolo) return;
  form.post(route('pae.protocolo.ccpae.store', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}
</script>
```

Antes de usar `ToggleField` com `v-model` boolean e `FormDateField` com `v-model` ISO `YYYY-MM-DD`, conferir a assinatura: `grep -n "modelValue\|emit(" SDC/resources/js/Components/Molecules/Form/ToggleField.vue SDC/resources/js/Components/Molecules/Form/FormDateField.vue`. Se o `ToggleField` emitir outro evento, ajustar o `v-model:` correspondente.

- [ ] **Step 2: Ligar no template**

Em `PaeProtocolosIndexTemplate.vue`:

1. Substituir o `<ConfirmDialog :is-open="showCcpaeConfirm" ...>` inteiro por:

```vue
    <EmitirCcpaeModal
      :show="showCcpaeModal"
      :protocolo="protocoloCcpae"
      @close="fecharCcpae"
    />
```

2. Import: `import EmitirCcpaeModal from '@/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue';`.
3. Apagar `showCcpaeConfirm`, `ccpaeLoading`, `protocoloIdToCcpae`, `protocoloCcpaeArquivado`, `getRequestErrorMessage`, `confirmCcpae` e `cancelCcpae`. No lugar deles:

```js
const showCcpaeModal = ref(false);
const protocoloCcpae = ref(null);

function handleCheck(id) {
  protocoloCcpae.value = (filteredProtocolos.value || []).find((p) => p.id === id) || null;
  showCcpaeModal.value = !!protocoloCcpae.value;
}

function fecharCcpae() {
  showCcpaeModal.value = false;
  protocoloCcpae.value = null;
}
```

Remover a versão antiga de `handleCheck`.

- [ ] **Step 3: Build**

Run: `cd "$WT/SDC" && npx vite build > /c/tmp/pae-gmg83-build.log 2>&1; tail -3 /c/tmp/pae-gmg83-build.log`
Expected: build concluído.

### Task 11: Histórico -- aba Prazos e ações na aba Notificações

**Files:**
- Create:
  - `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaePrazosPainel.vue`
  - `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeNotificacoesPainel.vue`
- Modify:
  - `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue`
  - `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue` (`:can-edit`, `@atualizado`)
  - `SDC/resources/js/ziggy.js` (regerado)

**Interfaces:**
- Consumes:
  - payload `historico.prazos` e `historico.notificacoes` (Tarefa 8)
  - rotas `pae.protocolo.notificacao-feam`, `pae.protocolo.notificacoes.store`, `pae.notificacoes.devolutiva` e `pae.notificacoes.dilacoes.store`
- Produces:
  - `<PaePrazosPainel :prazos :protocolo-id :can-edit @atualizado />`
  - `<PaeNotificacoesPainel :notificacoes :protocolo-id :can-edit :ciclos-esgotados-em @atualizado />`
  - `PaeHistoricoModal` com a prop `canEdit` e o evento `atualizado`

- [ ] **Step 1: `PaePrazosPainel.vue`**

```vue
<template>
  <div class="space-y-4">
    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Notificação da FEAM</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.dt_notificacao_feam) || 'Não informada' }}
          <Badge v-if="prazos?.dt_notificacao_feam_estimada" variant="neutral" size="sm">Estimada</Badge>
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Limite para protocolar (Art. 7, 10 dias úteis)</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.limite_protocolo) || '—' }}
          <Badge v-if="prazos?.fora_do_prazo" variant="danger" size="sm">Fora do prazo</Badge>
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Limite de análise (Art. 9, 300 dias)</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.limite_analise) || '—' }}
          <PrazosPill :prazo="prazos?.situacao || 'ok'" />
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Dias pausados por diligência</dt>
        <dd class="mt-1 text-sm modal-serie-valor">{{ prazos?.dias_pausados ?? 0 }}</dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4 sm:col-span-2">
        <dt class="text-xs modal-serie-apoio">CCPAE</dt>
        <dd class="mt-1 text-sm modal-serie-valor">
          <template v-if="prazos?.ccpae">
            {{ prazos.ccpae.codigo }}: emitido em {{ dataBR(prazos.ccpae.dt_emissao) }}, vigente até {{ dataBR(prazos.ccpae.dt_vencimento) }}
          </template>
          <template v-else>Não emitido</template>
        </dd>
      </div>
    </dl>

    <form v-if="canEdit" class="modal-serie-cartao flex flex-col gap-3 rounded-xl p-4 sm:flex-row sm:items-end" @submit.prevent="salvar">
      <div class="min-w-0 flex-1">
        <FormDateField
          v-model="form.dt_notificacao_feam"
          label="Data da notificação da FEAM ao empreendedor"
          required
          :error="form.errors.dt_notificacao_feam"
        />
      </div>
      <Button type="submit" variant="primary" :disabled="form.processing || !form.dt_notificacao_feam">
        {{ form.processing ? 'Salvando...' : 'Salvar e recalcular' }}
      </Button>
    </form>
  </div>
</template>

<script setup>
/**
 * Prazos legais do protocolo (Resolucao GMG 83/2024, Arts. 4, 5, 7 e 9).
 * Valores calculados no servidor (PaePrazoService::resumo); aqui so exibe e
 * permite informar a data real da notificacao da FEAM.
 */
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';

const props = defineProps({
  prazos: { type: Object, default: null },
  protocoloId: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['atualizado']);

const form = useForm({ dt_notificacao_feam: '' });

watch(() => props.prazos, (p) => {
  form.dt_notificacao_feam = p?.dt_notificacao_feam_estimada ? '' : (p?.dt_notificacao_feam ?? '');
}, { immediate: true });

function dataBR(iso) {
  if (!iso) return '';
  const [a, m, d] = iso.split('-');
  return `${d}/${m}/${a}`;
}

function salvar() {
  form.put(route('pae.protocolo.notificacao-feam', props.protocoloId), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => emit('atualizado'),
  });
}
</script>
```

- [ ] **Step 2: `PaeNotificacoesPainel.vue`**

```vue
<template>
  <div class="space-y-3">
    <p v-if="ciclosEsgotadosEm" class="rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">
      Ciclos automáticos esgotados em {{ dataBR(ciclosEsgotadosEm) }}. Decisão da CEDEC: suspender, reprovar ou notificar novamente.
    </p>

    <form v-if="canEdit && !cicloAberto" class="modal-serie-cartao space-y-3 rounded-xl p-4" @submit.prevent="emitir">
      <FormField v-model="emissao.num_sei" label="Número SEI da notificação" required :error="emissao.errors.num_sei || emissao.errors.notificacao" />
      <FormTextarea v-model="emissao.obs" label="Observação" :error="emissao.errors.obs" />
      <div class="flex justify-end">
        <Button type="submit" variant="warning" :disabled="emissao.processing || !emissao.num_sei">Emitir notificação</Button>
      </div>
    </form>

    <div v-for="n in notificacoesDesc" :key="n.id" class="modal-serie-cartao min-w-0 rounded-xl p-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
          <h4 class="text-base font-semibold modal-serie-titulo">Ciclo {{ n.ciclo }} • SEI {{ n.num_sei }}</h4>
          <p class="mt-1 text-sm modal-serie-apoio">
            Emitida em {{ dataBR(n.dt_notificacao) }} • vence em {{ dataBR(n.prazo_final) }}
            <span v-if="n.dias_dilacao">(+{{ n.dias_dilacao }} dias de dilação)</span>
          </p>
          <p class="mt-1 text-sm modal-serie-valor">
            {{ n.dt_devolutiva ? `Devolutiva em ${dataBR(n.dt_devolutiva)}` : 'Aguardando devolutiva' }}
          </p>
        </div>
        <Badge :variant="n.dt_devolutiva ? 'success' : (n.vencida ? 'danger' : 'warning')" size="sm">
          {{ n.dt_devolutiva ? 'Respondida' : (n.vencida ? 'Vencida' : 'Em prazo') }}
        </Badge>
      </div>

      <ul v-if="n.dilacoes?.length" class="mt-3 space-y-1 text-xs modal-serie-apoio">
        <li v-for="d in n.dilacoes" :key="d.id">
          +{{ d.dias_adicionais }} dias em {{ dataBR(d.registrada_em) }} ({{ d.aprovado_por || 'Sistema' }}): {{ d.justificativa }}
        </li>
      </ul>

      <div v-if="canEdit && !n.dt_devolutiva && n.id === ultimaId" class="mt-3 flex flex-wrap gap-2">
        <Button size="sm" variant="success" @click="abrir('devolutiva', n.id)">Registrar devolutiva</Button>
        <Button size="sm" variant="secondary" @click="abrir('dilacao', n.id)">Registrar dilação</Button>
      </div>

      <form v-if="acao === 'devolutiva' && alvo === n.id" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="registrarDevolutiva(n.id)">
        <div class="min-w-0 flex-1">
          <FormDateField v-model="devolutiva.dt_devolutiva" label="Data da devolutiva" required :error="devolutiva.errors.dt_devolutiva || devolutiva.errors.devolutiva" />
        </div>
        <Button type="submit" variant="success" :disabled="devolutiva.processing || !devolutiva.dt_devolutiva">Salvar</Button>
      </form>

      <form v-if="acao === 'dilacao' && alvo === n.id" class="mt-3 space-y-3" @submit.prevent="registrarDilacao(n.id)">
        <FormField v-model="dilacao.dias_adicionais" type="number" label="Dias adicionais" required :error="dilacao.errors.dias_adicionais || dilacao.errors.dilacao" />
        <FormTextarea v-model="dilacao.justificativa" label="Justificativa" required :error="dilacao.errors.justificativa" />
        <div class="flex justify-end">
          <Button type="submit" variant="primary" :disabled="dilacao.processing || !dilacao.dias_adicionais || !dilacao.justificativa">Salvar dilação</Button>
        </div>
      </form>
    </div>

    <div v-if="!notificacoes.length" class="py-10 text-center modal-serie-apoio">Nenhuma notificação registrada.</div>
  </div>
</template>

<script setup>
/**
 * Ciclos de notificacao do protocolo (Art. 11): emissao, devolutiva e dilacao.
 * Regras e prazos vem do servidor (PaeNotificacaoService::listarPorProtocolo).
 */
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  notificacoes: { type: Array, default: () => [] },
  protocoloId: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
  ciclosEsgotadosEm: { type: String, default: null },
});

const emit = defineEmits(['atualizado']);

const notificacoesDesc = computed(() => [...props.notificacoes].reverse());
// So a ultima notificacao recebe acoes: as anteriores sem devolutiva foram
// superadas por renovacao automatica e nao estao mais em aberto.
const ultima = computed(() => props.notificacoes[props.notificacoes.length - 1] ?? null);
const ultimaId = computed(() => ultima.value?.id ?? null);
const cicloAberto = computed(() => !!ultima.value && !ultima.value.dt_devolutiva);

const acao = ref(null);
const alvo = ref(null);

const emissao = useForm({ num_sei: '', obs: '' });
const devolutiva = useForm({ dt_devolutiva: new Date().toISOString().slice(0, 10) });
const dilacao = useForm({ dias_adicionais: '', justificativa: '' });

const opcoes = (onDone) => ({
  preserveScroll: true,
  preserveState: true,
  onSuccess: () => {
    onDone();
    acao.value = null;
    alvo.value = null;
    emit('atualizado');
  },
});

function abrir(qual, id) {
  acao.value = acao.value === qual && alvo.value === id ? null : qual;
  alvo.value = acao.value ? id : null;
}

function emitir() {
  emissao.post(route('pae.protocolo.notificacoes.store', props.protocoloId), opcoes(() => emissao.reset()));
}

function registrarDevolutiva(id) {
  devolutiva.post(route('pae.notificacoes.devolutiva', id), opcoes(() => devolutiva.reset()));
}

function registrarDilacao(id) {
  dilacao.post(route('pae.notificacoes.dilacoes.store', id), opcoes(() => dilacao.reset()));
}

function dataBR(iso) {
  if (!iso) return '';
  const [a, m, d] = String(iso).slice(0, 10).split('-');
  return `${d}/${m}/${a}`;
}
</script>
```

Conferir que `FormTextarea` e `FormField` aceitam `label`, `required`, `error` e `v-model` (`grep -n "defineProps" -A20 SDC/resources/js/Components/Molecules/Form/FormTextarea.vue`). Se `FormField` não repassar `type`, usar o atom `Input` com `type="number"`.

- [ ] **Step 3: `PaeHistoricoModal.vue`**

1. Aba nova. Inserir o botão "Prazos" antes do botão "Análises":

```vue
          <button
            type="button"
            class="modal-serie-aba pb-3 text-sm font-semibold flex items-center gap-2"
            :class="{ 'is-ativa': activeTab === 'prazos' }"
            @click="activeTab = 'prazos'"
          >
            Prazos
            <Badge v-if="historico?.prazos?.situacao === 'vencido'" variant="danger" size="sm">!</Badge>
          </button>
```

2. Conteúdo. Antes de `<!-- Análises -->`, transformar a primeira condição em cadeia:

```vue
        <div v-else-if="activeTab === 'prazos'">
          <PaePrazosPainel
            :prazos="historico?.prazos"
            :protocolo-id="protocolo?.id"
            :can-edit="canEdit"
            @atualizado="$emit('atualizado')"
          />
        </div>
```

3. Substituir o bloco inteiro `<!-- Notificações --> <div v-else> ... </div>` por:

```vue
        <!-- Notificações -->
        <div v-else>
          <PaeNotificacoesPainel
            :notificacoes="historico?.notificacoes || []"
            :protocolo-id="protocolo?.id"
            :can-edit="canEdit"
            :ciclos-esgotados-em="historico?.prazos?.ciclos_esgotados_em || null"
            @atualizado="$emit('atualizado')"
          />
        </div>
```

4. Script:
   - Imports: `PaePrazosPainel` e `PaeNotificacoesPainel` (`@/Components/Organisms/Pae/Protocolos/...`).
   - Prop nova: `canEdit: { type: Boolean, default: false }`.
   - `defineEmits(['close', 'atualizado'])`.
5. Mapas de tipo da timeline. Acrescentar `alerta` e `status`:
   - `eventLabel`: `alerta: 'Alerta', status: 'Status'`
   - `eventBadgeVariant`: `alerta: 'danger', status: 'info'`
   - `eventColor`: `alerta: 'bg-red-600/90 text-white', status: 'bg-indigo-500/90 text-white'`
   - `eventIcon`: `alerta: ExclamationTriangleIcon, status: DocumentTextIcon`

- [ ] **Step 4: Ligar o modal no template**

Em `PaeProtocolosIndexTemplate.vue`, no `<PaeHistoricoModal ...>`, acrescentar:

```vue
      :can-edit="canEdit"
      @atualizado="recarregarHistorico"
```

E no script, junto de `handleHistory`:

```js
async function recarregarHistorico() {
  if (!selectedProtocolo.value) return;
  historicoPayload.value = await historicoUsecase.execute(selectedProtocolo.value.id);
}
```

- [ ] **Step 5: Regerar o ziggy e buildar**

```bash
$PT artisan ziggy:generate resources/js/ziggy.js
cd "$WT" && git diff --stat SDC/resources/js/ziggy.js
cd "$WT/SDC" && npx vite build > /c/tmp/pae-gmg83-build.log 2>&1; tail -3 /c/tmp/pae-gmg83-build.log
```

Expected: o `ziggy.js` ganha `pae.protocolo.notificacao-feam`, `pae.protocolo.ccpae.store` e `pae.notificacoes.dilacoes.store`, e o build termina. Se o diff do `ziggy.js` trouxer mudanças de outras rotas (por exemplo, a URL base), restaurar o arquivo (`git checkout SDC/resources/js/ziggy.js`) e acrescentar à mão só as três entradas, no mesmo formato das vizinhas.

- [ ] **Step 6: Commit da Fase 4**

```bash
cd "$WT"
git add SDC/resources/js/Components/Molecules/Pae/Protocolos/PrazosPill.vue \
  SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosStatsCards.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/PaePrazosPainel.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeNotificacoesPainel.vue \
  SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeHistoricoModal.vue \
  SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue SDC/resources/js/Pages/PaeProtocolosIndex.vue \
  SDC/resources/js/ziggy.js
git status --short   # os 3 git rm de mock ja estao no stage; nada de tests/ nem public/build
git commit -m "✨ feat(pae): prazos na listagem e no historico, emissao do CCPAE e acoes de notificacao na tela"
```

---

## Fase 5 -- Verificação final

### Task 12: Suíte, verificação na tela e revisão

**Files:** nenhum arquivo novo. Esta fase só verifica.

- [ ] **Step 1: Suíte completa do PAE e unitários**

Run: `$PT vendor/bin/phpunit tests/Feature/Pae tests/Unit/Pae tests/Unit/Support`
Expected: PASS em tudo. Comparar o total com a linha de base da Tarefa 0 e listar os testes novos.

- [ ] **Step 2: Suíte inteira contra a linha de base**

Run: `$PT vendor/bin/phpunit > /c/tmp/pae-gmg83-full.log 2>&1; tail -8 /c/tmp/pae-gmg83-full.log`
Expected: nenhuma falha nova fora das pré-existentes registradas na memória (PlanCon e `GlobalSearchServiceTest`). Qualquer falha nova em Ranking indica quebra de contrato dos eventos do PAE e precisa ser investigada.

- [ ] **Step 3: Código limpo**

```bash
cd "$WT" && git diff dev --stat
git diff dev -- SDC/app SDC/resources SDC/routes SDC/config SDC/database | grep -nE "dd\(|dump\(|console\.log|Log::debug" || echo LIMPO
git diff dev --name-only | grep "^SDC/tests/" || echo SEM_TESTES_NO_COMMIT
```

Expected: `LIMPO` e `SEM_TESTES_NO_COMMIT`.

- [ ] **Step 4: Verificação na tela (375px e 840px, claro e escuro)**

Subir a worktree contra uma cópia do banco de dev:

```bash
docker exec newsdc_dev_db sh -c "createdb -U sdc -T template_postgis sdc_pae_gmg83_ui && pg_dump -U sdc sdc | psql -U sdc -q sdc_pae_gmg83_ui"
WT_WIN=$(cygpath -m "$WT/SDC"); KEY=$(grep '^APP_KEY=' "$WT/SDC/.env" | cut -d= -f2-)
MSYS_NO_PATHCONV=1 docker run -d --name pae-gmg83-ui --network newsdc-dev_default -p 8096:8000 -v "$WT_WIN:/app" -w /app \
  -e APP_KEY="$KEY" -e APP_URL=http://localhost:8096 -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_pae_gmg83_ui -e DB_USERNAME=sdc -e DB_PASSWORD=secret \
  -e APP_CONFIG_CACHE=/app/bootstrap/cache/nao-existe-config.php \
  newsdc-swoole-dev:latest sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"
```

Abrir `http://localhost:8096/pae` com um usuário de dev e conferir:

- **Listagem:** os 13 protocolos mostram a pill "Estimado"; os cards "Vencidos" e "Ciclos esgotados" filtram de fato; o botão "Arquivados" filtra.
- **Histórico, aba Prazos:** informar uma data da FEAM faz a marca "Estimado" sumir e o limite ser recalculado.
- **Aba Notificações:** emitir, registrar dilação (o vencimento muda) e registrar devolutiva (a pausa fecha).
- **CCPAE:** o botão abre o formulário. "Empreendimento novo" exige a LO e a prévia do vencimento acompanha o campo. Depois de emitir, a listagem filtra por CCPAE.
- **Responsividade:** em 375px e 840px, `document.documentElement.scrollWidth - document.documentElement.clientWidth === 0` no console, nos temas claro e escuro.

Ao terminar:

```bash
docker rm -f pae-gmg83-ui
docker exec newsdc_dev_db dropdb -U sdc sdc_pae_gmg83_ui
```

- [ ] **Step 5: Revisão final da branch**

Rodar o skill `superpowers:requesting-code-review` sobre `git diff dev...feat/pae-gmg83`, com foco em:

- as 5 linhas do Review Focus;
- os contratos do Outbox;
- estado em singleton (Octane);
- N+1 na listagem (`analise.notificacoes` com eager load).

Corrigir o que for confirmado, num commit `🐛 fix(pae): ...`.

- [ ] **Step 6: Atualizar a memória e entregar**

Atualizar `pae-gmg83-decisoes-cedec.md` com o estado (A implementado na branch; B a G pendentes) e com a coluna `ciclos_esgotados_em`, que entrou como adição ao spec. Relatar ao usuário os commits, os testes (números reais), o que foi verificado na tela e o que ficou para o merge em `dev` (fluxo de merge via worktree temporária, conforme a memória `newsdc-merge-via-worktree-temporaria`).
