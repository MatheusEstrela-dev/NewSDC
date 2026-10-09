# PAE E: conferência de evacuação do Anexo E — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** a CEDEC lança no protocolo a memória de cálculo de evacuação do empreendedor; o sistema recalcula pelo Anexo E, avalia os Critérios 1 e 2 do Anexo B, guarda versões imutáveis e sinaliza não conformidade sem bloquear a tramitação.

**Architecture:** um motor puro (`Support/Evacuacao`) faz todo o cálculo; `PaeEvacuacaoService` valida, normaliza, recalcula e grava versões append-only com `lockForUpdate` no protocolo; o controller expõe página Inertia, simulação JSON e registro. A listagem recebe a situação vigente por uma consulta em lote.

**Tech Stack:** Laravel 12, PHP 8.4 (container), PostgreSQL, Inertia 2, Vue 3, Tailwind, axios, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-08-pae-e-evacuacao-anexo-e-design.md`.

## Global Constraints

- Branch `feat/pae-evacuacao-anexo-e`, worktree `C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-evacuacao`, base `origin/dev` em `fec3f1c5`.
- TTE = **maior** valor entre TMD e o maior TE; nunca somar.
- Reprovação só sinaliza: workflow, guards, outbox e emissão do CCPAE não mudam.
- Permissões: `pae.protocolos.view` para ler; `pae.protocolos.edit` para simular e registrar. Nenhum slug novo.
- Uma migration da fase: `SDC/database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php`; qualquer ajuste de schema é consolidado nela.
- Sem emoji no código; DRY/SOLID; `declare(strict_types=1)` e `final class` no PHP; nenhum log de depuração no fim.
- Testes ficam fora do commit (o `SDC/.gitignore` já ignora `tests`). Commits: spec + plano juntos (`📝 docs(pae): especifica fase E da evacuação do Anexo E`), depois um commit da feature completa (`✨ feat(pae): conferência de evacuação do Anexo E`). Sem trailer de co-autoria.
- Constantes do Anexo E: desconto de rua 2,90 m (mão única) e 5,80 m (mão dupla); estrangulamento mínimo 1,2 m; divisor de estrangulamento 100 (plano) e 79 (rampa ou escada); acréscimo comercial 30%; ponto de encontro conforme quando pessoas/m² < 3.
- PHP do host (8.1) não serve: testes rodam pelo runner em container (Task 1, Step 1).

## Review Focus

1. **Número decimal digitado com vírgula** ("1,5"): inputs `type="number"` com `step="0.01"` impedem envio de texto; o servidor responde 422 no campo, sem erro 500.
2. **Setor renomeado depois de usado numa rota:** erro de referência aparece no campo `rotas.N.setores`, nunca cálculo silencioso. Coberto em `ReferenciasEvacuacaoTest`.
3. **População enorme em área pequena:** setor vira `densidade_inviavel`, sem divisão por zero, overflow ou velocidade negativa. Coberto em `CalculoEvacuacaoAnexoETest::test_populacao_extrema_vira_densidade_inviavel`.
4. **Simulação desatualizada:** alterar qualquer campo depois de simular desabilita "Registrar" até nova simulação; o servidor recalcula de qualquer forma.
5. **Versão de outro protocolo pela URL:** `GET /pae/protocolo/{A}/evacuacao/conferencias/{versao}` só procura versões do protocolo A; versão inexistente dá 404. Coberto em `PaeEvacuacaoHttpTest::test_versao_inexistente_no_protocolo_retorna_404`.

## Mapa de arquivos

| Responsabilidade | Arquivos |
| --- | --- |
| Tempo mm:ss | Criar `SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php` |
| Tabela 01 de velocidade | Criar `SDC/app/Modules/Pae/Support/Evacuacao/TabelaVelocidadeAnexoE.php` |
| Motor do cálculo | Criar `SDC/app/Modules/Pae/Support/Evacuacao/CalculoEvacuacaoAnexoE.php` |
| Referências entre listas | Criar `SDC/app/Modules/Pae/Support/Evacuacao/ReferenciasEvacuacao.php` |
| Persistência | Criar a migration e `SDC/app/Modules/Pae/Models/PaeEvacuacaoConferencia.php`; modificar `PaeProtocolo.php` |
| Regras de entrada | Criar `SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php`, `RegistrarEvacuacaoRequest.php` |
| Serviço | Criar `SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php`; modificar `PaeServiceProvider.php` |
| HTTP | Criar `SDC/app/Modules/Pae/Controllers/PaeEvacuacaoController.php`; modificar `routes/modules/pae.php`, `PaeProtocoloController.php` |
| Interface | Criar `SDC/resources/js/Pages/PaeEvacuacao.vue`, `resources/js/Composables/pae/usePaeEvacuacaoForm.js`, `resources/js/utils/paeEvacuacao.js`, cinco organismos em `resources/js/Components/Organisms/Pae/Evacuacao/`; modificar card, tabela, grid, template da listagem e `EmitirCcpaeModal.vue` |
| Testes locais | `SDC/tests/Unit/Pae/Evacuacao/*Test.php`, `SDC/tests/Feature/Pae/PaeEvacuacao*Test.php` (ignorados pelo git) |

---

### Task 1: Motor de cálculo do Anexo E

**Files:**
- Create: `SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php`
- Create: `SDC/app/Modules/Pae/Support/Evacuacao/TabelaVelocidadeAnexoE.php`
- Create: `SDC/app/Modules/Pae/Support/Evacuacao/CalculoEvacuacaoAnexoE.php`
- Create: `SDC/app/Modules/Pae/Support/Evacuacao/ReferenciasEvacuacao.php`
- Test: `SDC/tests/Unit/Pae/Evacuacao/TempoAnexoETest.php`, `TabelaVelocidadeAnexoETest.php`, `CalculoEvacuacaoAnexoETest.php`, `ReferenciasEvacuacaoTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `TempoAnexoE::formatar(int|float|null $segundos): ?string` (`'mm:ss'`), `TempoAnexoE::paraSegundos(string $mmss): int`, `TempoAnexoE::formatarResultado(array $dados): array` (acrescenta `<campo>_fmt` para cada chave `<campo>_segundos`, recursivo).
  - `TabelaVelocidadeAnexoE::velocidade(float $densidade, string $terreno): ?float`, constantes `PLANO = 'plano'`, `INCLINADO = 'inclinado'`.
  - `CalculoEvacuacaoAnexoE::calcular(array $entrada): array` sobre a entrada normalizada abaixo.
  - `ReferenciasEvacuacao::erros(array $entrada): array<string,string>` (campo => mensagem).
- Entrada normalizada (produzida pelo serviço na Task 3):
  - `setores`: lista de `['id' => string, 'populacao' => int, 'comercial' => bool, 'via' => 'calcada'|'rua_mao_unica'|'rua_mao_dupla', 'largura' => float, 'lados' => ?int, 'distancia' => float, 'terreno' => 'plano'|'inclinado']`
  - `rotas`: lista de `['id' => string, 'setores' => list<string>, 'chegada_onda' => 'mm:ss', 'chegada_onda_segundos' => int, 'nivel_emergencia' => int]`
  - `acessos`: lista de `['id' => string, 'largura' => float, 'terreno' => string, 'rotas' => list<string>]`
  - `pontos_encontro`: lista de `['nome' => string, 'endereco' => string, 'populacao' => int, 'area' => float]`
  - `tte_declarado_segundos`: `?int`
- Resultado de `calcular`: chaves `setores` (por id: `populacao_efetiva`, `largura_util`, `area`, `densidade`, `velocidade`, `tempo_segundos`, `situacao` = `ok|via_insuficiente|densidade_inviavel`), `rotas` (por id: `terf_segundos`, `acesso`, `saida_segundos`, `chegada_onda_segundos`, `nivel_emergencia`, `invalida`, `motivo` = `null|setor_sem_tempo|estrangulamento_abaixo_minimo`, `conforme`), `acessos` (por id: `n`, `te_segundos`, `invalido`), `pontos_encontro` (lista: `densidade`, `conforme`), `tmd_segundos`, `te_segundos`, `tte_segundos`, `criterio1_conforme`, `criterio2_conforme`, `possui_rota_invalida`, `possui_setor_inviavel`, `excede_declarado`.

- [ ] **Step 1: Preparar o ambiente de teste da worktree.** O runner da fase D usa o PHP 8.4 da imagem `newsdc-pae-d-test-runtime:local` e o banco isolado `pae_d_test`, que já está migrado até a fase D. Copiar runner e `.env`:

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-evacuacao
mkdir -p .superpowers/sdd/2026-10-08-pae-e-evacuacao-anexo-e
cp ../pae-dco-implementation/.superpowers/sdd/2026-10-08-pae-d-dco-ccpae/run-phpunit.ps1 .superpowers/sdd/2026-10-08-pae-e-evacuacao-anexo-e/
cp ../pae-dco-implementation/SDC/.env SDC/.env
git check-ignore -q .superpowers SDC/.env && echo ignorados
```

Expected: `ignorados`. Daqui em diante, `RUN <filtro>` significa, em PowerShell na raiz da worktree:

```powershell
Set-Location C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-evacuacao; $ws='.superpowers/sdd/2026-10-08-pae-e-evacuacao-anexo-e'; & "$ws/run-phpunit.ps1" --filter='<filtro>' *> "$ws/run.log"; "exit $LASTEXITCODE"; Get-Content "$ws/run.log" | Select-String -Pattern 'OK \(|Tests:|^\d+\) |^-''|^\+''' | Select-Object -First 30
```

- [ ] **Step 2: Escrever os testes que falham.**

`SDC/tests/Unit/Pae/Evacuacao/TempoAnexoETest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Evacuacao;

use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use PHPUnit\Framework\TestCase;

final class TempoAnexoETest extends TestCase
{
    public function test_formata_fracao_de_minuto_como_no_item_4_2(): void
    {
        // 4,09 min do exemplo do Anexo E = 245,45 s -> 4 min 05 s.
        $this->assertSame('04:05', TempoAnexoE::formatar(1.2 * 750 / (100 * 2.2) * 60));
        $this->assertSame('15:00', TempoAnexoE::formatar(900.0));
        $this->assertNull(TempoAnexoE::formatar(null));
    }

    public function test_converte_mm_ss_em_segundos(): void
    {
        $this->assertSame(901, TempoAnexoE::paraSegundos('15:01'));
        $this->assertSame(0, TempoAnexoE::paraSegundos('00:00'));
    }

    public function test_formatar_resultado_acrescenta_campos_fmt_recursivamente(): void
    {
        $dados = TempoAnexoE::formatarResultado([
            'tte_segundos' => 900.0,
            'rotas' => ['R1' => ['saida_segundos' => null]],
        ]);

        $this->assertSame('15:00', $dados['tte_fmt']);
        $this->assertNull($dados['rotas']['R1']['saida_fmt']);
    }
}
```

`SDC/tests/Unit/Pae/Evacuacao/TabelaVelocidadeAnexoETest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Evacuacao;

use App\Modules\Pae\Support\Evacuacao\TabelaVelocidadeAnexoE as Tabela;
use PHPUnit\Framework\TestCase;

final class TabelaVelocidadeAnexoETest extends TestCase
{
    public function test_limites_exatos_das_faixas_da_tabela_01(): void
    {
        $this->assertSame(1.20, Tabela::velocidade(0.54, Tabela::PLANO));
        $this->assertSame(1.03, Tabela::velocidade(0.5401, Tabela::PLANO));
        $this->assertSame(1.03, Tabela::velocidade(1.0, Tabela::PLANO));
        $this->assertSame(0.84, Tabela::velocidade(1.5, Tabela::PLANO));
        $this->assertSame(0.66, Tabela::velocidade(2.0, Tabela::PLANO));
        $this->assertSame(0.58, Tabela::velocidade(2.0, Tabela::INCLINADO));
        $this->assertSame(1.05, Tabela::velocidade(0.1, Tabela::INCLINADO));
    }

    public function test_densidade_acima_de_dois_usa_formula(): void
    {
        $this->assertEqualsWithDelta(0.47, Tabela::velocidade(2.5, Tabela::PLANO), 1e-9);
        $this->assertEqualsWithDelta(0.4125, Tabela::velocidade(2.5, Tabela::INCLINADO), 1e-9);
    }

    public function test_formula_sem_velocidade_positiva_devolve_null(): void
    {
        $this->assertNull(Tabela::velocidade(4.0, Tabela::PLANO));
        $this->assertNull(Tabela::velocidade(3.8, Tabela::INCLINADO));
    }
}
```

`SDC/tests/Unit/Pae/Evacuacao/CalculoEvacuacaoAnexoETest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Evacuacao;

use App\Modules\Pae\Support\Evacuacao\CalculoEvacuacaoAnexoE;
use PHPUnit\Framework\TestCase;

final class CalculoEvacuacaoAnexoETest extends TestCase
{
    public function test_densidade_e_velocidade_do_exemplo_do_item_3_3(): void
    {
        $r = $this->calcular([$this->setor('A', 680, distancia: 170.0)], [$this->rota('R1', ['A'])]);

        $this->assertEqualsWithDelta(680 / 510, $r['setores']['A']['densidade'], 1e-9);
        $this->assertSame(0.84, $r['setores']['A']['velocidade']);
        $this->assertEqualsWithDelta(170 / 0.84, $r['setores']['A']['tempo_segundos'], 1e-6);
    }

    public function test_terf_e_tmd_do_exemplo_do_item_3_6(): void
    {
        // Populacao baixa -> D <= 0,54 -> 1,20 m/s; distancias dao 8, 6, 7, 4 e 3 minutos.
        $setores = [
            $this->setor('A', 10, distancia: 576.0),
            $this->setor('B', 10, distancia: 432.0),
            $this->setor('C', 10, distancia: 504.0),
            $this->setor('D', 10, distancia: 288.0),
            $this->setor('E', 10, distancia: 216.0),
        ];
        $rotas = [$this->rota('R1', ['A', 'D', 'E']), $this->rota('R2', ['B', 'D', 'E']), $this->rota('R3', ['C', 'D', 'E'])];

        $r = $this->calcular($setores, $rotas);

        $this->assertEqualsWithDelta(900, $r['rotas']['R1']['terf_segundos'], 1e-6);
        $this->assertEqualsWithDelta(780, $r['rotas']['R2']['terf_segundos'], 1e-6);
        $this->assertEqualsWithDelta(840, $r['rotas']['R3']['terf_segundos'], 1e-6);
        $this->assertEqualsWithDelta(900, $r['tmd_segundos'], 1e-6);
        $this->assertNull($r['te_segundos']);
        $this->assertEqualsWithDelta(900, $r['tte_segundos'], 1e-6);
    }

    public function test_estrangulamento_do_exemplo_do_item_4_2_e_tte_pelo_maior_valor(): void
    {
        $longo = $this->calcular(
            [$this->setor('X', 750, distancia: 1000.0)],
            [$this->rota('R1', ['X'])],
            [$this->acesso('AC1', 2.2, ['R1'])],
        );
        $te = 1.2 * 750 / (100 * 2.2) * 60;

        $this->assertSame(750, $longo['acessos']['AC1']['n']);
        $this->assertEqualsWithDelta($te, $longo['acessos']['AC1']['te_segundos'], 1e-9);
        $this->assertEqualsWithDelta(1000 / 1.2, $longo['tte_segundos'], 1e-6);

        $curto = $this->calcular(
            [$this->setor('X', 750, distancia: 100.0)],
            [$this->rota('R1', ['X'])],
            [$this->acesso('AC1', 2.2, ['R1'])],
        );

        $this->assertEqualsWithDelta($te, $curto['rotas']['R1']['saida_segundos'], 1e-9);
        $this->assertEqualsWithDelta($te, $curto['tte_segundos'], 1e-9);
    }

    public function test_estrangulamento_em_rampa_usa_divisor_79(): void
    {
        $r = $this->calcular(
            [$this->setor('X', 750, distancia: 100.0)],
            [$this->rota('R1', ['X'])],
            [$this->acesso('AC1', 2.2, ['R1'], 'inclinado')],
        );

        $this->assertEqualsWithDelta(1.2 * 750 / (79 * 2.2) * 60, $r['acessos']['AC1']['te_segundos'], 1e-9);
    }

    public function test_setor_compartilhado_conta_uma_vez_no_estrangulamento(): void
    {
        $r = $this->calcular(
            [$this->setor('A', 100), $this->setor('B', 200), $this->setor('C', 300)],
            [$this->rota('R1', ['A', 'C']), $this->rota('R2', ['B', 'C'])],
            [$this->acesso('AC1', 3.0, ['R1', 'R2'])],
        );

        $this->assertSame(600, $r['acessos']['AC1']['n']);
    }

    public function test_area_comercial_acrescenta_30_por_cento_arredondando_para_cima(): void
    {
        $r = $this->calcular(
            [$this->setor('A', 680, comercial: true), $this->setor('B', 101, comercial: true)],
            [$this->rota('R1', ['A', 'B'])],
        );

        $this->assertSame(884, $r['setores']['A']['populacao_efetiva']);
        $this->assertSame(132, $r['setores']['B']['populacao_efetiva']);
    }

    public function test_rua_sem_calcada_desconta_faixa_de_veiculos(): void
    {
        $r = $this->calcular(
            [
                $this->setor('U', 10, via: 'rua_mao_unica', largura: 4.40),
                $this->setor('D', 10, via: 'rua_mao_dupla', largura: 5.80),
            ],
            [$this->rota('R1', ['U']), $this->rota('R2', ['D'])],
        );

        $this->assertEqualsWithDelta(1.50, $r['setores']['U']['largura_util'], 1e-9);
        $this->assertSame('via_insuficiente', $r['setores']['D']['situacao']);
        $this->assertNull($r['rotas']['R2']['terf_segundos']);
        $this->assertTrue($r['rotas']['R2']['invalida']);
        $this->assertSame('setor_sem_tempo', $r['rotas']['R2']['motivo']);
        $this->assertTrue($r['possui_setor_inviavel']);
    }

    public function test_populacao_extrema_vira_densidade_inviavel(): void
    {
        $r = $this->calcular(
            [$this->setor('A', 1000000, largura: 1.0, lados: 1, distancia: 1.0)],
            [$this->rota('R1', ['A'])],
        );

        $this->assertSame('densidade_inviavel', $r['setores']['A']['situacao']);
        $this->assertNull($r['setores']['A']['velocidade']);
        $this->assertNull($r['tmd_segundos']);
        $this->assertNull($r['tte_segundos']);
        $this->assertFalse($r['criterio2_conforme']);
    }

    public function test_estrangulamento_abaixo_de_1_2_m_invalida_as_rotas_do_acesso(): void
    {
        $abaixo = $this->calcular([$this->setor('A', 10)], [$this->rota('R1', ['A'])], [$this->acesso('AC1', 1.19, ['R1'])]);
        $limite = $this->calcular([$this->setor('A', 10)], [$this->rota('R1', ['A'])], [$this->acesso('AC1', 1.20, ['R1'])]);

        $this->assertTrue($abaixo['acessos']['AC1']['invalido']);
        $this->assertSame('estrangulamento_abaixo_minimo', $abaixo['rotas']['R1']['motivo']);
        $this->assertTrue($abaixo['possui_rota_invalida']);
        $this->assertFalse($limite['acessos']['AC1']['invalido']);
        $this->assertFalse($limite['possui_rota_invalida']);
    }

    public function test_criterio_2_exige_saida_estritamente_menor_que_a_onda(): void
    {
        $setor = [$this->setor('A', 10, distancia: 576.0)];
        $igual = $this->calcular($setor, [$this->rota('R1', ['A'], 900)]);
        $folga = $this->calcular($setor, [$this->rota('R1', ['A'], 901)]);

        $this->assertFalse($igual['rotas']['R1']['conforme']);
        $this->assertFalse($igual['criterio2_conforme']);
        $this->assertTrue($folga['rotas']['R1']['conforme']);
        $this->assertTrue($folga['criterio2_conforme']);
    }

    public function test_criterio_1_exige_menos_de_tres_pessoas_por_metro_quadrado(): void
    {
        $r = $this->calcular([$this->setor('A', 10)], [$this->rota('R1', ['A'])], [], [
            ['nome' => 'P1', 'endereco' => 'Rua 1', 'populacao' => 300, 'area' => 100.0],
            ['nome' => 'P2', 'endereco' => 'Rua 2', 'populacao' => 299, 'area' => 100.0],
        ]);

        $this->assertFalse($r['pontos_encontro'][0]['conforme']);
        $this->assertTrue($r['pontos_encontro'][1]['conforme']);
        $this->assertFalse($r['criterio1_conforme']);
    }

    public function test_sinaliza_quando_calculado_excede_o_declarado(): void
    {
        $setor = [$this->setor('A', 10, distancia: 576.0)];
        $rota = [$this->rota('R1', ['A'], 3600)];

        $this->assertTrue($this->calcular($setor, $rota, declarado: 899)['excede_declarado']);
        $this->assertFalse($this->calcular($setor, $rota, declarado: 900)['excede_declarado']);
        $this->assertFalse($this->calcular($setor, $rota)['excede_declarado']);
    }

    private function calcular(array $setores, array $rotas, array $acessos = [], ?array $pontos = null, ?int $declarado = null): array
    {
        return (new CalculoEvacuacaoAnexoE())->calcular([
            'setores' => $setores,
            'rotas' => $rotas,
            'acessos' => $acessos,
            'pontos_encontro' => $pontos ?? [['nome' => 'P', 'endereco' => 'Rua', 'populacao' => 1, 'area' => 100.0]],
            'tte_declarado_segundos' => $declarado,
        ]);
    }

    private function setor(
        string $id,
        int $populacao,
        bool $comercial = false,
        string $via = 'calcada',
        float $largura = 1.5,
        ?int $lados = 2,
        float $distancia = 100.0,
        string $terreno = 'plano',
    ): array {
        return compact('id', 'populacao', 'comercial', 'via', 'largura', 'lados', 'distancia', 'terreno');
    }

    private function rota(string $id, array $setores, int $chegada = 3600): array
    {
        return [
            'id' => $id,
            'setores' => $setores,
            'chegada_onda' => sprintf('%02d:%02d', intdiv($chegada, 60), $chegada % 60),
            'chegada_onda_segundos' => $chegada,
            'nivel_emergencia' => 2,
        ];
    }

    private function acesso(string $id, float $largura, array $rotas, string $terreno = 'plano'): array
    {
        return compact('id', 'largura', 'terreno', 'rotas');
    }
}
```

`SDC/tests/Unit/Pae/Evacuacao/ReferenciasEvacuacaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Evacuacao;

use App\Modules\Pae\Support\Evacuacao\ReferenciasEvacuacao;
use PHPUnit\Framework\TestCase;

final class ReferenciasEvacuacaoTest extends TestCase
{
    public function test_entrada_coerente_nao_tem_erros(): void
    {
        $this->assertSame([], ReferenciasEvacuacao::erros($this->entrada()));
    }

    public function test_rota_com_setor_inexistente_ou_repetido(): void
    {
        $inexistente = $this->entrada();
        $inexistente['rotas'][0]['setores'] = ['A', 'Z'];
        $repetido = $this->entrada();
        $repetido['rotas'][0]['setores'] = ['A', 'A', 'B'];

        $this->assertSame('O setor Z não existe.', ReferenciasEvacuacao::erros($inexistente)['rotas.0.setores']);
        $this->assertSame('A rota repete um setor.', ReferenciasEvacuacao::erros($repetido)['rotas.0.setores']);
    }

    public function test_setor_fora_de_qualquer_rota(): void
    {
        $entrada = $this->entrada();
        $entrada['setores'][] = ['id' => 'C'];

        $this->assertSame('O setor C não pertence a nenhuma rota.', ReferenciasEvacuacao::erros($entrada)['setores.2.id']);
    }

    public function test_acesso_com_rota_inexistente_ou_ja_ligada(): void
    {
        $inexistente = $this->entrada();
        $inexistente['acessos'][0]['rotas'] = ['R9'];
        $duplicada = $this->entrada();
        $duplicada['acessos'][] = ['id' => 'AC2', 'rotas' => ['R1']];

        $this->assertSame('A rota R9 não existe.', ReferenciasEvacuacao::erros($inexistente)['acessos.0.rotas']);
        $this->assertSame('A rota R1 já está ligada ao acesso AC1.', ReferenciasEvacuacao::erros($duplicada)['acessos.1.rotas']);
    }

    private function entrada(): array
    {
        return [
            'setores' => [['id' => 'A'], ['id' => 'B']],
            'rotas' => [['id' => 'R1', 'setores' => ['A', 'B']]],
            'acessos' => [['id' => 'AC1', 'rotas' => ['R1']]],
        ];
    }
}
```

- [ ] **Step 3: Rodar e ver falhar.** `RUN Evacuacao`. Expected: erros `Class "App\Modules\Pae\Support\Evacuacao\..." not found` nos quatro arquivos.

- [ ] **Step 4: Implementar as quatro classes.**

`SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Tempos do Anexo E em segundos, exibidos em mm:ss. A fracao de minuto vira
 * segundos (x 60) e arredonda ao segundo, como no exemplo do item 4.2.
 */
final class TempoAnexoE
{
    private const SUFIXO = '_segundos';

    public static function formatar(int|float|null $segundos): ?string
    {
        if ($segundos === null) {
            return null;
        }
        $total = (int) round($segundos);

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    public static function paraSegundos(string $mmss): int
    {
        [$minutos, $segundos] = array_map('intval', explode(':', $mmss));

        return $minutos * 60 + $segundos;
    }

    public static function formatarResultado(array $dados): array
    {
        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $dados[$chave] = self::formatarResultado($valor);
            } elseif (is_string($chave) && str_ends_with($chave, self::SUFIXO)) {
                $dados[substr($chave, 0, -strlen(self::SUFIXO)).'_fmt'] = self::formatar($valor);
            }
        }

        return $dados;
    }
}
```

`SDC/app/Modules/Pae/Support/Evacuacao/TabelaVelocidadeAnexoE.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Tabela 01 do Anexo E (adaptada de Rosaria Ono): velocidade de deslocamento
 * em m/s pela densidade e pelo terreno. Inclinado = declividade predominante
 * acima de 5%.
 */
final class TabelaVelocidadeAnexoE
{
    public const PLANO = 'plano';
    public const INCLINADO = 'inclinado';

    private const EPSILON = 1e-9;

    /** @var list<array{0: float, 1: float, 2: float}> limite superior, plano, inclinado */
    private const FAIXAS = [
        [0.54, 1.20, 1.05],
        [1.0, 1.03, 0.90],
        [1.5, 0.84, 0.74],
        [2.0, 0.66, 0.58],
    ];

    /** Null quando a formula de D > 2 nao produz velocidade positiva. */
    public static function velocidade(float $densidade, string $terreno): ?float
    {
        $plano = $terreno === self::PLANO;
        foreach (self::FAIXAS as [$limite, $velocidadePlano, $velocidadeInclinado]) {
            if ($densidade <= $limite + self::EPSILON) {
                return $plano ? $velocidadePlano : $velocidadeInclinado;
            }
        }
        $velocidade = $plano ? 1.4 - 0.372 * $densidade : 1.23 - 0.327 * $densidade;

        return $velocidade > self::EPSILON ? $velocidade : null;
    }
}
```

`SDC/app/Modules/Pae/Support/Evacuacao/CalculoEvacuacaoAnexoE.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Memoria de calculo do Anexo E da Resolucao GMG 83/2024 e criterios do item 8
 * do Anexo B. Tempos em segundos. TTE = maior valor entre TMD e TE (decisao da
 * CEDEC: o texto do item 5.1 prevalece sobre a formula de soma).
 */
final class CalculoEvacuacaoAnexoE
{
    public const LARGURA_MINIMA_ESTRANGULAMENTO = 1.2;

    private const EPSILON = 1e-9;
    private const DESCONTO_RUA = ['rua_mao_unica' => 2.90, 'rua_mao_dupla' => 5.80];
    private const DENSIDADE_MAXIMA_PONTO = 3.0;

    public function calcular(array $entrada): array
    {
        $setores = [];
        foreach ($entrada['setores'] as $setor) {
            $setores[$setor['id']] = $this->setor($setor);
        }

        $rotasPorId = array_column($entrada['rotas'], null, 'id');
        $acessos = [];
        $acessoDaRota = [];
        foreach ($entrada['acessos'] as $acesso) {
            $acessos[$acesso['id']] = $this->acesso($acesso, $rotasPorId, $setores);
            foreach ($acesso['rotas'] as $rotaId) {
                $acessoDaRota[$rotaId] = $acesso['id'];
            }
        }

        $rotas = [];
        foreach ($entrada['rotas'] as $rota) {
            $acessoId = $acessoDaRota[$rota['id']] ?? null;
            $rotas[$rota['id']] = $this->rota($rota, $setores, $acessoId, $acessoId === null ? null : $acessos[$acessoId]);
        }

        $pontos = array_map(fn (array $ponto): array => $this->ponto($ponto), $entrada['pontos_encontro']);

        $terfs = array_values(array_filter(array_column($rotas, 'terf_segundos'), fn ($t): bool => $t !== null));
        $tes = array_column($acessos, 'te_segundos');
        $tmd = $terfs === [] ? null : max($terfs);
        $te = $tes === [] ? null : max($tes);
        $tte = $tmd === null ? null : max($tmd, $te ?? 0.0);
        $declarado = $entrada['tte_declarado_segundos'] ?? null;

        return [
            'setores' => $setores,
            'rotas' => $rotas,
            'acessos' => $acessos,
            'pontos_encontro' => $pontos,
            'tmd_segundos' => $tmd,
            'te_segundos' => $te,
            'tte_segundos' => $tte,
            'criterio1_conforme' => $this->todos($pontos, 'conforme'),
            'criterio2_conforme' => $this->todos($rotas, 'conforme'),
            'possui_rota_invalida' => in_array(true, array_column($rotas, 'invalida'), true),
            'possui_setor_inviavel' => in_array(true, array_map(fn (array $s): bool => $s['situacao'] !== 'ok', $setores), true),
            'excede_declarado' => $declarado !== null && $tte !== null && $tte > $declarado,
        ];
    }

    private function setor(array $setor): array
    {
        // +30% em area comercial, arredondado para cima sem erro de ponto flutuante.
        $populacao = $setor['comercial'] ? intdiv($setor['populacao'] * 13 + 9, 10) : $setor['populacao'];
        $larguraUtil = $setor['via'] === 'calcada'
            ? $setor['largura'] * $setor['lados']
            : $setor['largura'] - self::DESCONTO_RUA[$setor['via']];

        if ($larguraUtil <= self::EPSILON) {
            return $this->setorSemTempo($populacao, null, 'via_insuficiente');
        }

        $area = $larguraUtil * $setor['distancia'];
        $densidade = $populacao / $area;
        $velocidade = TabelaVelocidadeAnexoE::velocidade($densidade, $setor['terreno']);
        if ($velocidade === null) {
            return ['densidade' => $densidade, 'area' => $area] + $this->setorSemTempo($populacao, $larguraUtil, 'densidade_inviavel');
        }

        return [
            'populacao_efetiva' => $populacao,
            'largura_util' => $larguraUtil,
            'area' => $area,
            'densidade' => $densidade,
            'velocidade' => $velocidade,
            'tempo_segundos' => $setor['distancia'] / $velocidade,
            'situacao' => 'ok',
        ];
    }

    private function setorSemTempo(int $populacao, ?float $larguraUtil, string $situacao): array
    {
        return [
            'populacao_efetiva' => $populacao,
            'largura_util' => $larguraUtil,
            'area' => null,
            'densidade' => null,
            'velocidade' => null,
            'tempo_segundos' => null,
            'situacao' => $situacao,
        ];
    }

    private function acesso(array $acesso, array $rotasPorId, array $setores): array
    {
        $setoresDoAcesso = [];
        foreach ($acesso['rotas'] as $rotaId) {
            foreach ($rotasPorId[$rotaId]['setores'] as $setorId) {
                $setoresDoAcesso[$setorId] = true;
            }
        }
        $n = 0;
        foreach (array_keys($setoresDoAcesso) as $setorId) {
            $n += $setores[$setorId]['populacao_efetiva'];
        }
        $divisor = $acesso['terreno'] === TabelaVelocidadeAnexoE::PLANO ? 100 : 79;

        return [
            'n' => $n,
            'te_segundos' => 1.2 * $n / ($divisor * $acesso['largura']) * 60,
            'invalido' => $acesso['largura'] < self::LARGURA_MINIMA_ESTRANGULAMENTO - self::EPSILON,
        ];
    }

    private function rota(array $rota, array $setores, ?string $acessoId, ?array $acesso): array
    {
        $tempos = array_map(fn (string $id): ?float => $setores[$id]['tempo_segundos'], $rota['setores']);
        $terf = in_array(null, $tempos, true) ? null : array_sum($tempos);
        $motivo = match (true) {
            $terf === null => 'setor_sem_tempo',
            $acesso !== null && $acesso['invalido'] => 'estrangulamento_abaixo_minimo',
            default => null,
        };
        $saida = $terf === null ? null : max($terf, $acesso['te_segundos'] ?? 0.0);

        return [
            'terf_segundos' => $terf,
            'acesso' => $acessoId,
            'saida_segundos' => $saida,
            'chegada_onda_segundos' => $rota['chegada_onda_segundos'],
            'nivel_emergencia' => $rota['nivel_emergencia'],
            'invalida' => $motivo !== null,
            'motivo' => $motivo,
            'conforme' => $motivo === null && $saida < $rota['chegada_onda_segundos'],
        ];
    }

    private function ponto(array $ponto): array
    {
        $densidade = $ponto['populacao'] / $ponto['area'];

        return ['densidade' => $densidade, 'conforme' => $densidade < self::DENSIDADE_MAXIMA_PONTO];
    }

    private function todos(array $itens, string $campo): bool
    {
        return $itens !== [] && ! in_array(false, array_column($itens, $campo), true);
    }
}
```

`SDC/app/Modules/Pae/Support/Evacuacao/ReferenciasEvacuacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Coerencia entre as listas da conferencia: rotas citam setores existentes,
 * todo setor pertence a alguma rota e cada rota chega por no maximo um acesso.
 */
final class ReferenciasEvacuacao
{
    /** @return array<string, string> campo => mensagem */
    public static function erros(array $entrada): array
    {
        $erros = [];
        $idsSetores = array_column($entrada['setores'], 'id');
        $idsRotas = array_column($entrada['rotas'], 'id');
        $usados = [];

        foreach ($entrada['rotas'] as $i => $rota) {
            if (count($rota['setores']) !== count(array_unique($rota['setores']))) {
                $erros["rotas.$i.setores"] = 'A rota repete um setor.';
            }
            foreach ($rota['setores'] as $setorId) {
                $usados[$setorId] = true;
                if (! in_array($setorId, $idsSetores, true)) {
                    $erros["rotas.$i.setores"] = "O setor {$setorId} não existe.";
                }
            }
        }

        foreach ($entrada['setores'] as $i => $setor) {
            if (! isset($usados[$setor['id']])) {
                $erros["setores.$i.id"] = "O setor {$setor['id']} não pertence a nenhuma rota.";
            }
        }

        $acessoDaRota = [];
        foreach ($entrada['acessos'] as $i => $acesso) {
            foreach ($acesso['rotas'] as $rotaId) {
                if (! in_array($rotaId, $idsRotas, true)) {
                    $erros["acessos.$i.rotas"] = "A rota {$rotaId} não existe.";
                } elseif (isset($acessoDaRota[$rotaId])) {
                    $erros["acessos.$i.rotas"] = "A rota {$rotaId} já está ligada ao acesso {$acessoDaRota[$rotaId]}.";
                } else {
                    $acessoDaRota[$rotaId] = $acesso['id'];
                }
            }
        }

        return $erros;
    }
}
```

- [ ] **Step 5: Rodar e ver passar.** `RUN Evacuacao`. Expected: `OK (22 tests, ...)`.

### Task 2: Schema e model versionado

**Files:**
- Create: `SDC/database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php`
- Create: `SDC/app/Modules/Pae/Models/PaeEvacuacaoConferencia.php`
- Modify: `SDC/app/Modules/Pae/Models/PaeProtocolo.php` (relação nova, junto de `fichasAnexoB()`)
- Test: `SDC/tests/Feature/Pae/PaeEvacuacaoSchemaTest.php`

**Interfaces:**
- Consumes: nada da Task 1.
- Produces: `PaeProtocolo::conferenciasEvacuacao(): HasMany`; `PaeEvacuacaoConferencia` com `entrada(): array` (setores, rotas, acessos, pontos_encontro, tte_declarado_segundos), `conforme(): bool`, relação `autor()`.

- [ ] **Step 1: Teste que falha.** `SDC/tests/Feature/Pae/PaeEvacuacaoSchemaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeEvacuacaoConferencia;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PaeEvacuacaoSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_versoes_sao_unicas_por_protocolo_e_conformidade_combina_os_sinais(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $user = User::factory()->create();
        $conferencia = $protocolo->conferenciasEvacuacao()->create($this->linha($user, 1));

        $this->assertSame(['A'], array_column($conferencia->fresh()->setores, 'id'));
        $this->assertTrue($conferencia->fresh()->conforme());
        $this->assertSame(300, $conferencia->fresh()->entrada()['tte_declarado_segundos']);

        $conferencia->update(['excede_declarado' => true]);
        $this->assertFalse($conferencia->fresh()->conforme());

        $this->expectException(QueryException::class);
        $protocolo->conferenciasEvacuacao()->create($this->linha($user, 1));
    }

    private function linha(User $user, int $versao): array
    {
        return [
            'versao' => $versao,
            'setores' => [['id' => 'A']],
            'rotas' => [],
            'acessos' => [],
            'pontos_encontro' => [],
            'tte_declarado_segundos' => 300,
            'num_sei' => '123',
            'tmd_segundos' => 200.5,
            'te_segundos' => null,
            'tte_segundos' => 200.5,
            'criterio1_conforme' => true,
            'criterio2_conforme' => true,
            'possui_rota_invalida' => false,
            'possui_setor_inviavel' => false,
            'excede_declarado' => false,
            'resultado' => [],
            'chave_idempotencia' => (string) Str::uuid(),
            'criado_por' => $user->id,
        ];
    }
}
```

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeEvacuacaoSchemaTest`. Expected: erro `Call to undefined method ...conferenciasEvacuacao()` ou tabela inexistente.

- [ ] **Step 3: Migration, model e relação.**

`SDC/database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php`:

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
        Schema::create('pae_evacuacao_conferencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->unsignedInteger('versao');
            $table->jsonb('setores');
            $table->jsonb('rotas');
            $table->jsonb('acessos');
            $table->jsonb('pontos_encontro');
            $table->unsignedInteger('tte_declarado_segundos')->nullable();
            $table->string('num_sei', 100);
            $table->text('observacao')->nullable();
            $table->decimal('tmd_segundos', 12, 3)->nullable();
            $table->decimal('te_segundos', 12, 3)->nullable();
            $table->decimal('tte_segundos', 12, 3)->nullable();
            $table->boolean('criterio1_conforme');
            $table->boolean('criterio2_conforme');
            $table->boolean('possui_rota_invalida');
            $table->boolean('possui_setor_inviavel');
            $table->boolean('excede_declarado');
            $table->jsonb('resultado');
            $table->uuid('chave_idempotencia');
            $table->foreignId('criado_por')->constrained('users');
            $table->timestampTz('created_at');
            $table->unique(['protocolo_id', 'versao'], 'pae_evacuacao_versao_unica');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_evacuacao_idempotencia_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_evacuacao_conferencias');
    }
};
```

`SDC/app/Modules/Pae/Models/PaeEvacuacaoConferencia.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versao imutavel da conferencia de evacuacao (Anexo E e item 8 do Anexo B). */
final class PaeEvacuacaoConferencia extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pae_evacuacao_conferencias';

    protected $fillable = [
        'protocolo_id', 'versao', 'setores', 'rotas', 'acessos', 'pontos_encontro',
        'tte_declarado_segundos', 'num_sei', 'observacao', 'tmd_segundos', 'te_segundos',
        'tte_segundos', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida',
        'possui_setor_inviavel', 'excede_declarado', 'resultado', 'chave_idempotencia', 'criado_por',
    ];

    protected $casts = [
        'versao' => 'integer',
        'setores' => 'array',
        'rotas' => 'array',
        'acessos' => 'array',
        'pontos_encontro' => 'array',
        'resultado' => 'array',
        'tte_declarado_segundos' => 'integer',
        'tmd_segundos' => 'float',
        'te_segundos' => 'float',
        'tte_segundos' => 'float',
        'criterio1_conforme' => 'boolean',
        'criterio2_conforme' => 'boolean',
        'possui_rota_invalida' => 'boolean',
        'possui_setor_inviavel' => 'boolean',
        'excede_declarado' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function entrada(): array
    {
        return [
            'setores' => $this->setores,
            'rotas' => $this->rotas,
            'acessos' => $this->acessos,
            'pontos_encontro' => $this->pontos_encontro,
            'tte_declarado_segundos' => $this->tte_declarado_segundos,
        ];
    }

    public function conforme(): bool
    {
        return $this->criterio1_conforme && $this->criterio2_conforme
            && ! $this->possui_rota_invalida && ! $this->possui_setor_inviavel && ! $this->excede_declarado;
    }
}
```

Em `SDC/app/Modules/Pae/Models/PaeProtocolo.php`, logo depois de `fichasAnexoB()`:

```php
    public function conferenciasEvacuacao(): HasMany
    {
        return $this->hasMany(PaeEvacuacaoConferencia::class, 'protocolo_id');
    }
```

(O model fica no mesmo namespace `App\Modules\Pae\Models`; não precisa de `use`.)

- [ ] **Step 4: Rodar e ver passar.** `RUN PaeEvacuacaoSchemaTest`. Expected: `OK (1 test, ...)`. O runner aplica a migration no `pae_d_test` antes dos testes.

### Task 3: Regras de entrada e serviço de conferência

**Files:**
- Create: `SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php`, `RegistrarEvacuacaoRequest.php`
- Create: `SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php`
- Modify: `SDC/app/Modules/Pae/PaeServiceProvider.php` (singleton junto de `PaeDcoService`)
- Test: `SDC/tests/Feature/Pae/PaeEvacuacaoServiceTest.php`

**Interfaces:**
- Consumes: Task 1 (`CalculoEvacuacaoAnexoE::calcular`, `ReferenciasEvacuacao::erros`, `TempoAnexoE`), Task 2 (`conferenciasEvacuacao()`, `PaeEvacuacaoConferencia`).
- Produces:
  - `SimularEvacuacaoRequest::regras(): array`, `RegistrarEvacuacaoRequest::regras(): array`.
  - `PaeEvacuacaoService::simular(array $dados): array` (resultado com campos `_fmt`).
  - `PaeEvacuacaoService::registrar(PaeProtocolo $protocolo, array $dados, User $user): PaeEvacuacaoConferencia`.
  - `PaeEvacuacaoService::visualizar(PaeProtocolo $protocolo, ?int $versao): array{conferencia: ?array, historico: list<array>, historica: bool, versao_atual: int}`.
  - `PaeEvacuacaoService::anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator` (acrescenta `evacuacao_situacao` = `nao_conferida|conforme|nao_conforme`).
- Payload de entrada (HTTP e serviço): `setores[]` (`id`, `populacao`, `comercial`, `via`, `largura`, `lados`, `distancia`, `terreno`), `rotas[]` (`id`, `setores[]`, `chegada_onda` mm:ss, `nivel_emergencia`), `acessos[]` (`id`, `largura`, `terreno`, `rotas[]`), `pontos_encontro[]` (`nome`, `endereco`, `populacao`, `area`), `tte_declarado` (mm:ss ou null); registro acrescenta `num_sei`, `observacao`, `chave_idempotencia`.

- [ ] **Step 1: Testes que falham.** `SDC/tests/Feature/Pae/PaeEvacuacaoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeEvacuacaoConferencia;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Pae\Services\PaeEvacuacaoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class PaeEvacuacaoServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_simular_calcula_sem_gravar(): void
    {
        $resultado = app(PaeEvacuacaoService::class)->simular(self::entrada());

        $this->assertSame('03:43', $resultado['tte_fmt']);
        $this->assertTrue($resultado['criterio2_conforme']);
        $this->assertSame(0, PaeEvacuacaoConferencia::query()->count());
    }

    public function test_registrar_cria_versoes_imutaveis_e_evento_no_historico(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeEvacuacaoService::class);

        $primeira = $service->registrar($protocolo, self::registro(), $user);
        $segunda = $service->registrar($protocolo, self::registro(['rotas' => [[...self::entrada()['rotas'][0], 'chegada_onda' => '03:00']]]), $user);

        $this->assertSame([1, 2], [$primeira->versao, $segunda->versao]);
        $this->assertTrue($primeira->fresh()->conforme());
        $this->assertFalse($segunda->conforme());
        $this->assertEqualsWithDelta(1.2 * 680 / 220 * 60, $segunda->tte_segundos, 1e-3);
        $this->assertSame(2, PaeTimeline::query()->where('protocolo_id', $protocolo->id)->where('evento', 'evacuacao_conferencia')->count());
    }

    public function test_idempotencia_devolve_a_mesma_versao_e_recusa_dados_diferentes(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeEvacuacaoService::class);
        $dados = self::registro();

        $primeira = $service->registrar($protocolo, $dados, $user);
        $repetida = $service->registrar($protocolo, $dados, $user);
        $this->assertSame($primeira->id, $repetida->id);

        $this->expectException(ValidationException::class);
        $service->registrar($protocolo, [...$dados, 'num_sei' => '999'], $user);
    }

    public function test_referencia_invalida_vira_erro_no_campo(): void
    {
        $dados = self::entrada();
        $dados['rotas'][0]['setores'] = ['Z'];

        try {
            app(PaeEvacuacaoService::class)->simular($dados);
            $this->fail('A referência inválida deveria falhar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rotas.0.setores', $e->errors());
        }
    }

    public function test_protocolo_arquivado_e_somente_leitura(): void
    {
        $protocolo = PaeProtocolo::factory()->create(['arquivado' => true]);

        $this->expectException(ValidationException::class);
        app(PaeEvacuacaoService::class)->registrar($protocolo, self::registro(), User::factory()->create());
    }

    public function test_falha_no_historico_desfaz_a_versao(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        PaeTimeline::creating(fn (): never => throw new RuntimeException('falha simulada'));

        try {
            app(PaeEvacuacaoService::class)->registrar($protocolo, self::registro(), User::factory()->create());
            $this->fail('A falha deveria propagar.');
        } catch (RuntimeException) {
            $this->assertSame(0, $protocolo->conferenciasEvacuacao()->count());
        } finally {
            PaeTimeline::flushEventListeners();
        }
    }

    public function test_visualizar_aponta_versao_historica_e_rejeita_versao_inexistente(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeEvacuacaoService::class);
        $service->registrar($protocolo, self::registro(), $user);
        $service->registrar($protocolo, self::registro(['tte_declarado' => '10:00']), $user);

        $atual = $service->visualizar($protocolo, null);
        $antiga = $service->visualizar($protocolo, 1);

        $this->assertSame(2, $atual['versao_atual']);
        $this->assertFalse($atual['historica']);
        $this->assertTrue($antiga['historica']);
        $this->assertSame('05:00', $antiga['conferencia']['entrada']['tte_declarado']);
        $this->assertSame([2, 1], array_column($atual['historico'], 'versao'));

        $this->expectException(ModelNotFoundException::class);
        $service->visualizar($protocolo, 9);
    }

    public function test_listagem_anota_situacao_com_consultas_constantes(): void
    {
        $user = User::factory()->create();
        $service = app(PaeEvacuacaoService::class);
        $semConferencia = PaeProtocolo::factory()->create();
        $conforme = PaeProtocolo::factory()->create();
        $naoConforme = PaeProtocolo::factory()->create();
        $service->registrar($conforme, self::registro(), $user);
        $service->registrar($naoConforme, self::registro(['tte_declarado' => '01:00']), $user);

        $contar = function (array $ids) use ($service): array {
            $pagina = PaeProtocolo::query()->whereKey($ids)->paginate(50);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $linhas = $service->anotarListagem($pagina)->getCollection()->keyBy('id');
            DB::disableQueryLog();

            return [count(DB::getQueryLog()), $linhas];
        };

        [$umaConsulta] = $contar([$semConferencia->id]);
        [$tresConsultas, $linhas] = $contar([$semConferencia->id, $conforme->id, $naoConforme->id]);

        $this->assertSame($umaConsulta, $tresConsultas);
        $this->assertSame('nao_conferida', $linhas[$semConferencia->id]['evacuacao_situacao']);
        $this->assertSame('conforme', $linhas[$conforme->id]['evacuacao_situacao']);
        $this->assertSame('nao_conforme', $linhas[$naoConforme->id]['evacuacao_situacao']);
    }

    /** Setor do exemplo do item 3.3; estrangulamento de 2,2 m: TE = 222,55 s > TERF = 202,38 s. */
    public static function entrada(): array
    {
        return [
            'setores' => [['id' => 'A', 'populacao' => 680, 'comercial' => false, 'via' => 'calcada', 'largura' => 1.5, 'lados' => 2, 'distancia' => 170, 'terreno' => 'plano']],
            'rotas' => [['id' => 'R1', 'setores' => ['A'], 'chegada_onda' => '30:00', 'nivel_emergencia' => 2]],
            'acessos' => [['id' => 'AC1', 'largura' => 2.2, 'terreno' => 'plano', 'rotas' => ['R1']]],
            'pontos_encontro' => [['nome' => 'Praça Central', 'endereco' => 'Rua 1, 100', 'populacao' => 680, 'area' => 500]],
            'tte_declarado' => '05:00',
        ];
    }

    public static function registro(array $alteracoes = []): array
    {
        return [...self::entrada(), 'num_sei' => '1234.01.0000001/2026-01', 'observacao' => null, 'chave_idempotencia' => (string) Str::uuid(), ...$alteracoes];
    }
}
```

Nota sobre os números: TE = 1,2 × 680 / (100 × 2,2) min = 3,709 min = 222,55 s → `03:43`; TERF = 170 / 0,84 = 202,38 s; TTE = 222,55 s. Com `tte_declarado` 01:00 o calculado excede o declarado; com chegada da onda 03:00 (180 s) o Critério 2 reprova.

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeEvacuacaoServiceTest`. Expected: `Target class [App\Modules\Pae\Services\PaeEvacuacaoService] does not exist`.

- [ ] **Step 3: Requests com a fonte única das regras.**

`SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimularEvacuacaoRequest extends FormRequest
{
    private const IDENTIFICADOR = ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_-]+$/'];
    private const MM_SS = 'regex:/^\d{1,3}:[0-5]\d$/';
    private const TERRENOS = ['plano', 'inclinado'];

    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    /** Fonte unica das regras da entrada, reutilizada pelo PaeEvacuacaoService. */
    public static function regras(): array
    {
        return [
            'setores' => ['required', 'array', 'min:1', 'max:50'],
            'setores.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'setores.*.populacao' => ['required', 'integer', 'min:0', 'max:1000000'],
            'setores.*.comercial' => ['required', 'boolean'],
            'setores.*.via' => ['required', Rule::in(['calcada', 'rua_mao_unica', 'rua_mao_dupla'])],
            'setores.*.largura' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'setores.*.lados' => ['nullable', 'required_if:setores.*.via,calcada', 'integer', 'in:1,2'],
            'setores.*.distancia' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'setores.*.terreno' => ['required', Rule::in(self::TERRENOS)],
            'rotas' => ['required', 'array', 'min:1', 'max:20'],
            'rotas.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'rotas.*.setores' => ['required', 'array', 'min:1', 'max:50'],
            'rotas.*.setores.*' => self::IDENTIFICADOR,
            'rotas.*.chegada_onda' => ['required', 'string', self::MM_SS],
            'rotas.*.nivel_emergencia' => ['required', 'integer', 'in:1,2,3'],
            'acessos' => ['nullable', 'array', 'max:20'],
            'acessos.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'acessos.*.largura' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'acessos.*.terreno' => ['required', Rule::in(self::TERRENOS)],
            'acessos.*.rotas' => ['required', 'array', 'min:1', 'max:20'],
            'acessos.*.rotas.*' => self::IDENTIFICADOR,
            'pontos_encontro' => ['required', 'array', 'min:1', 'max:50'],
            'pontos_encontro.*.nome' => ['required', 'string', 'max:255'],
            'pontos_encontro.*.endereco' => ['required', 'string', 'max:500'],
            'pontos_encontro.*.populacao' => ['required', 'integer', 'min:0', 'max:1000000'],
            'pontos_encontro.*.area' => ['required', 'numeric', 'gt:0', 'max:10000000', 'decimal:0,2'],
            'tte_declarado' => ['nullable', 'string', self::MM_SS],
        ];
    }
}
```

`SDC/app/Modules/Pae/Requests/RegistrarEvacuacaoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrarEvacuacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    public static function regras(): array
    {
        return [
            ...SimularEvacuacaoRequest::regras(),
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
```

- [ ] **Step 4: Serviço.** `SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeEvacuacaoConferencia;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\RegistrarEvacuacaoRequest;
use App\Modules\Pae\Requests\SimularEvacuacaoRequest;
use App\Modules\Pae\Support\Evacuacao\CalculoEvacuacaoAnexoE;
use App\Modules\Pae\Support\Evacuacao\ReferenciasEvacuacao;
use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PaeEvacuacaoService
{
    public function __construct(private readonly CalculoEvacuacaoAnexoE $calculo)
    {
    }

    public function simular(array $dados): array
    {
        $entrada = $this->entrada($dados, SimularEvacuacaoRequest::regras());

        return TempoAnexoE::formatarResultado($this->calculo->calcular($entrada));
    }

    public function registrar(PaeProtocolo $protocolo, array $dados, User $user): PaeEvacuacaoConferencia
    {
        $entrada = $this->entrada($dados, RegistrarEvacuacaoRequest::regras());
        $numSei = trim((string) $dados['num_sei']);
        if ($numSei === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }
        $observacao = trim((string) ($dados['observacao'] ?? '')) ?: null;
        $resultado = TempoAnexoE::formatarResultado($this->calculo->calcular($entrada));

        return DB::transaction(function () use ($protocolo, $dados, $user, $entrada, $numSei, $observacao, $resultado): PaeEvacuacaoConferencia {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if ($locked->arquivado) {
                throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: a conferência está somente para consulta.']);
            }

            $existente = $locked->conferenciasEvacuacao()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                if ($existente->entrada() != $entrada || $existente->num_sei !== $numSei || $existente->observacao !== $observacao) {
                    throw ValidationException::withMessages(['chave_idempotencia' => 'Esta chave de idempotência já foi usada com outros dados.']);
                }

                return $existente;
            }

            $versao = (int) $locked->conferenciasEvacuacao()->max('versao') + 1;
            $conferencia = $locked->conferenciasEvacuacao()->create([
                ...$entrada,
                'versao' => $versao,
                'num_sei' => $numSei,
                'observacao' => $observacao,
                'tmd_segundos' => $resultado['tmd_segundos'],
                'te_segundos' => $resultado['te_segundos'],
                'tte_segundos' => $resultado['tte_segundos'],
                'criterio1_conforme' => $resultado['criterio1_conforme'],
                'criterio2_conforme' => $resultado['criterio2_conforme'],
                'possui_rota_invalida' => $resultado['possui_rota_invalida'],
                'possui_setor_inviavel' => $resultado['possui_setor_inviavel'],
                'excede_declarado' => $resultado['excede_declarado'],
                'resultado' => $resultado,
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'criado_por' => $user->id,
            ]);
            TimelinePae::registrar($locked, 'evacuacao_conferencia', sprintf(
                'Conferência de evacuação versão %d: TTE %s, %s. SEI %s.',
                $versao,
                $resultado['tte_fmt'] ?? 'não calculado',
                $conferencia->conforme() ? 'conforme' : 'não conforme',
                $numSei,
            ), $user);

            return $conferencia;
        });
    }

    public function visualizar(PaeProtocolo $protocolo, ?int $versao): array
    {
        $historico = $protocolo->conferenciasEvacuacao()->with('autor:id,name')->orderByDesc('versao')->get();
        $atual = $historico->first();
        $selecionada = $versao === null ? $atual : $historico->firstWhere('versao', $versao);
        if ($versao !== null && $selecionada === null) {
            throw (new ModelNotFoundException())->setModel(PaeEvacuacaoConferencia::class, [$versao]);
        }

        return [
            'conferencia' => $selecionada === null ? null : $this->apresentar($selecionada),
            'historico' => $historico->map(fn (PaeEvacuacaoConferencia $c): array => [
                'versao' => $c->versao,
                'autor' => $c->autor?->name,
                'created_at' => $c->created_at?->toIso8601String(),
                'num_sei' => $c->num_sei,
                'tte_fmt' => TempoAnexoE::formatar($c->tte_segundos),
                'conforme' => $c->conforme(),
            ])->values()->all(),
            'historica' => $selecionada !== null && $atual->versao !== $selecionada->versao,
            'versao_atual' => $atual?->versao ?? 0,
        ];
    }

    public function anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator
    {
        $ids = $pagina->getCollection()->map(fn ($item): int => (int) data_get($item, 'id'))->all();
        if ($ids === []) {
            return $pagina;
        }

        $vigentes = PaeEvacuacaoConferencia::query()
            ->whereIn('id', PaeEvacuacaoConferencia::query()->selectRaw('max(id)')->whereIn('protocolo_id', $ids)->groupBy('protocolo_id'))
            ->get(['protocolo_id', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida', 'possui_setor_inviavel', 'excede_declarado'])
            ->keyBy('protocolo_id');

        $pagina->setCollection($pagina->getCollection()->map(function ($item) use ($vigentes): array {
            $conferencia = $vigentes->get((int) data_get($item, 'id'));

            return [
                ...(is_array($item) ? $item : $item->toArray()),
                'evacuacao_situacao' => match (true) {
                    $conferencia === null => 'nao_conferida',
                    $conferencia->conforme() => 'conforme',
                    default => 'nao_conforme',
                },
            ];
        }));

        return $pagina;
    }

    /** Valida, normaliza e confere as referencias; o resultado nunca vem do cliente. */
    private function entrada(array $dados, array $regras): array
    {
        $validado = Validator::make($dados, $regras)->validate();
        $entrada = [
            'setores' => array_map(fn (array $s): array => [
                'id' => trim($s['id']),
                'populacao' => (int) $s['populacao'],
                'comercial' => (bool) $s['comercial'],
                'via' => $s['via'],
                'largura' => (float) $s['largura'],
                'lados' => $s['via'] === 'calcada' ? (int) $s['lados'] : null,
                'distancia' => (float) $s['distancia'],
                'terreno' => $s['terreno'],
            ], $validado['setores']),
            'rotas' => array_map(fn (array $r): array => [
                'id' => trim($r['id']),
                'setores' => array_values(array_map('trim', $r['setores'])),
                'chegada_onda' => $r['chegada_onda'],
                'chegada_onda_segundos' => TempoAnexoE::paraSegundos($r['chegada_onda']),
                'nivel_emergencia' => (int) $r['nivel_emergencia'],
            ], $validado['rotas']),
            'acessos' => array_map(fn (array $a): array => [
                'id' => trim($a['id']),
                'largura' => (float) $a['largura'],
                'terreno' => $a['terreno'],
                'rotas' => array_values(array_map('trim', $a['rotas'])),
            ], $validado['acessos'] ?? []),
            'pontos_encontro' => array_map(fn (array $p): array => [
                'nome' => trim($p['nome']),
                'endereco' => trim($p['endereco']),
                'populacao' => (int) $p['populacao'],
                'area' => (float) $p['area'],
            ], $validado['pontos_encontro']),
            'tte_declarado_segundos' => isset($validado['tte_declarado']) ? TempoAnexoE::paraSegundos($validado['tte_declarado']) : null,
        ];

        $erros = ReferenciasEvacuacao::erros($entrada);
        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        return $entrada;
    }

    private function apresentar(PaeEvacuacaoConferencia $c): array
    {
        return [
            'versao' => $c->versao,
            'entrada' => [...$c->entrada(), 'tte_declarado' => TempoAnexoE::formatar($c->tte_declarado_segundos)],
            'resultado' => $c->resultado,
            'num_sei' => $c->num_sei,
            'observacao' => $c->observacao,
            'autor' => $c->autor?->name,
            'created_at' => $c->created_at?->toIso8601String(),
            'conforme' => $c->conforme(),
        ];
    }
}
```

Em `SDC/app/Modules/Pae/PaeServiceProvider.php`: acrescentar `use App\Modules\Pae\Services\PaeEvacuacaoService;` e, logo depois de `$this->app->singleton(PaeDcoService::class);`, a linha `$this->app->singleton(PaeEvacuacaoService::class);`.

- [ ] **Step 5: Rodar e ver passar.** `RUN PaeEvacuacao`. Expected: `OK` com os 9 testes de serviço e schema. Se `test_idempotencia...` falhar porque o `jsonb` devolveu inteiro onde a entrada tem float (2 contra 2.0), confirmar que a comparação usa `!=` (frouxa), não `!==`.

### Task 4: HTTP, rotas, listagem e histórico

**Files:**
- Create: `SDC/app/Modules/Pae/Controllers/PaeEvacuacaoController.php`
- Modify: `SDC/routes/modules/pae.php` (bloco junto das rotas DCO), `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php` (`index` e `$eventoMap`)
- Test: `SDC/tests/Feature/Pae/PaeEvacuacaoHttpTest.php`

**Interfaces:**
- Consumes: Task 3 (`visualizar`, `simular`, `registrar`, `anotarListagem`), Requests da Task 3.
- Produces: rotas nomeadas `pae.protocolo.evacuacao.show`, `pae.protocolo.evacuacao.versao` (`{paeProtocolo}`, `{versao}`), `pae.protocolo.evacuacao.simular`, `pae.protocolo.evacuacao.registrar`; página Inertia `PaeEvacuacao` com props `protocolo`, `conferencia`, `historico`, `historica`, `versao_atual`, `can_edit`; campo `evacuacao_situacao` em cada protocolo da listagem.

- [ ] **Step 1: Testes que falham.** `SDC/tests/Feature/Pae/PaeEvacuacaoHttpTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeEvacuacaoConferencia;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeEvacuacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class PaeEvacuacaoHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pagina_exige_view_e_mostra_nao_conferida(): void
    {
        $protocolo = PaeProtocolo::factory()->create();

        $this->actingAs(User::factory()->create())->get("/pae/protocolo/{$protocolo->id}/evacuacao")->assertForbidden();
        $this->actingAs($this->usuario('view'))->get("/pae/protocolo/{$protocolo->id}/evacuacao")->assertOk()
            ->assertInertia(fn ($page) => $page->component('PaeEvacuacao')->where('conferencia', null)->where('can_edit', false));
    }

    public function test_simular_exige_edit_e_nao_grava(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $url = "/pae/protocolo/{$protocolo->id}/evacuacao/simular";

        $this->actingAs($this->usuario('view'))->postJson($url, PaeEvacuacaoServiceTest::entrada())->assertForbidden();
        $this->actingAs($this->usuario('edit'))->postJson($url, PaeEvacuacaoServiceTest::entrada())
            ->assertOk()->assertJsonPath('tte_fmt', '03:43')->assertJsonPath('criterio1_conforme', true);
        $this->assertSame(0, PaeEvacuacaoConferencia::query()->count());
    }

    public function test_registrar_valida_e_redireciona_para_a_pagina(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $editor = $this->usuario('edit');
        $url = "/pae/protocolo/{$protocolo->id}/evacuacao/conferencias";

        $this->actingAs($editor)->post($url, [...PaeEvacuacaoServiceTest::registro(), 'num_sei' => ''])->assertSessionHasErrors('num_sei');
        $this->actingAs($editor)->post($url, [...PaeEvacuacaoServiceTest::registro(), 'setores' => []])->assertSessionHasErrors('setores');
        $this->actingAs($editor)->post($url, PaeEvacuacaoServiceTest::registro())
            ->assertRedirect("/pae/protocolo/{$protocolo->id}/evacuacao");
        $this->assertSame(1, $protocolo->conferenciasEvacuacao()->count());
    }

    public function test_versao_inexistente_no_protocolo_retorna_404(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $outro = PaeProtocolo::factory()->create();
        app(PaeEvacuacaoService::class)->registrar($outro, PaeEvacuacaoServiceTest::registro(), User::factory()->create());
        $leitor = $this->usuario('view');

        $this->actingAs($leitor)->get("/pae/protocolo/{$protocolo->id}/evacuacao/conferencias/1")->assertNotFound();
        $this->actingAs($leitor)->get("/pae/protocolo/{$outro->id}/evacuacao/conferencias/1")->assertOk()
            ->assertInertia(fn ($page) => $page->where('conferencia.versao', 1)->where('historica', false));
    }

    private function usuario(string $acao): User
    {
        $user = User::factory()->create();
        foreach (array_unique(['view', $acao]) as $slug) {
            Permission::findOrCreate("pae.protocolos.{$slug}", 'web');
            $user->givePermissionTo("pae.protocolos.{$slug}");
        }

        return $user;
    }
}
```

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeEvacuacaoHttpTest`. Expected: 404 nas rotas, que ainda não existem.

- [ ] **Step 3: Controller, rotas, listagem e histórico.**

`SDC/app/Modules/Pae/Controllers/PaeEvacuacaoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\RegistrarEvacuacaoRequest;
use App\Modules\Pae\Requests\SimularEvacuacaoRequest;
use App\Modules\Pae\Services\PaeEvacuacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaeEvacuacaoController extends Controller
{
    public function __construct(private readonly PaeEvacuacaoService $evacuacao)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo, ?int $versao = null): Response
    {
        $resumo = $this->evacuacao->visualizar($paeProtocolo, $versao);

        return Inertia::render('PaeEvacuacao', [
            ...$resumo,
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'can_edit' => ($request->user()?->can('pae.protocolos.edit') ?? false)
                && ! $paeProtocolo->arquivado && ! $resumo['historica'],
        ]);
    }

    public function simular(SimularEvacuacaoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json($this->evacuacao->simular($request->validated()));
    }

    public function registrar(RegistrarEvacuacaoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $conferencia = $this->evacuacao->registrar($paeProtocolo, $request->validated(), $request->user());

        return redirect()->route('pae.protocolo.evacuacao.show', $paeProtocolo)
            ->with('success', 'Conferência de evacuação registrada na versão '.$conferencia->versao.'.');
    }
}
```

Em `SDC/routes/modules/pae.php`: acrescentar `use App\Modules\Pae\Controllers\PaeEvacuacaoController;` junto dos demais `use` e, logo depois da rota `protocolo.dco.documentos.download`:

```php
    Route::get('/protocolo/{paeProtocolo}/evacuacao', [PaeEvacuacaoController::class, 'show'])
        ->name('protocolo.evacuacao.show')
        ->middleware('can:pae.protocolos.view');

    Route::get('/protocolo/{paeProtocolo}/evacuacao/conferencias/{versao}', [PaeEvacuacaoController::class, 'show'])
        ->whereNumber('versao')
        ->name('protocolo.evacuacao.versao')
        ->middleware('can:pae.protocolos.view');

    Route::post('/protocolo/{paeProtocolo}/evacuacao/simular', [PaeEvacuacaoController::class, 'simular'])
        ->name('protocolo.evacuacao.simular')
        ->middleware('can:pae.protocolos.edit');

    Route::post('/protocolo/{paeProtocolo}/evacuacao/conferencias', [PaeEvacuacaoController::class, 'registrar'])
        ->name('protocolo.evacuacao.registrar')
        ->middleware('can:pae.protocolos.edit');
```

Em `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php`:
- `use App\Modules\Pae\Services\PaeEvacuacaoService;` e o parâmetro `private readonly PaeEvacuacaoService $evacuacao,` depois de `private readonly PaeDcoService $dco,` no construtor;
- em `index`, trocar a anotação por:

```php
        $protocolos = $this->evacuacao->anotarListagem($this->dco->anotarListagem(
            $this->prazos->anotarListagem($this->service->list($filters)), CarbonImmutable::today()));
```

- no `$eventoMap`, depois de `'dco_documento'`:

```php
            'evacuacao_conferencia' => ['tipo' => 'analise',     'titulo' => 'Conferência de evacuação registrada'],
```

- [ ] **Step 4: Rodar e ver passar.** `RUN PaeEvacuacao`. Expected: `OK` com HTTP, serviço e schema. Depois `RUN Pae` para a regressão do módulo. Expected: `OK` (78 testes da fase D + os novos).

### Task 5: Interface da conferência e sinalização na listagem

**Files:**
- Create: `SDC/resources/js/utils/paeEvacuacao.js`
- Create: `SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js`
- Create: `SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue`, `EvacuacaoSetoresEditor.vue`, `EvacuacaoRotasEditor.vue`, `EvacuacaoAcessosEditor.vue`, `EvacuacaoPontosEditor.vue`
- Create: `SDC/resources/js/Pages/PaeEvacuacao.vue`
- Modify: `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`, `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`, `PaeProtocolosGrid.vue`, `EmitirCcpaeModal.vue`, `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`

**Interfaces:**
- Consumes: rotas e props da Task 4; JSON de `simular` com as chaves do resultado da Task 1 mais os campos `_fmt`.
- Produces: `usePaeEvacuacaoForm(protocoloId, conferencia)` → `{ form, resultado, simulado, simulando, erros, simular, registrar, adicionar, remover }`; `rotuloSituacaoEvacuacao`, `classeSituacaoEvacuacao`, `numero`, `CLASSE_CAMPO`, `MOTIVOS_ROTA`, `SITUACOES_SETOR` em `utils/paeEvacuacao.js`.

- [ ] **Step 1: Utilitários e composable.**

`SDC/resources/js/utils/paeEvacuacao.js`:

```js
// Rotulos e formatacao compartilhados pela conferencia de evacuacao (PAE, Anexo E).
const ROTULOS_SITUACAO = { nao_conferida: 'não conferida', conforme: 'conforme', nao_conforme: 'não conforme' };

export function rotuloSituacaoEvacuacao(situacao) {
  return ROTULOS_SITUACAO[situacao] ?? ROTULOS_SITUACAO.nao_conferida;
}

export function classeSituacaoEvacuacao(situacao) {
  return situacao === 'nao_conforme' ? 'text-red-700 dark:text-red-300' : 'text-slate-600 dark:text-slate-300';
}

export function numero(valor, casas = 2) {
  if (valor === null || valor === undefined) return '—';
  return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
}

export const CLASSE_CAMPO = 'w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';

export const MOTIVOS_ROTA = {
  setor_sem_tempo: 'setor sem tempo calculado',
  estrangulamento_abaixo_minimo: 'estrangulamento abaixo de 1,2 m',
};

export const SITUACOES_SETOR = {
  via_insuficiente: 'largura útil insuficiente',
  densidade_inviavel: 'densidade inviável',
};
```

`SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js`:

```js
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const FABRICAS = {
  setores: () => ({ id: '', populacao: 0, comercial: false, via: 'calcada', largura: 1.5, lados: 2, distancia: 0, terreno: 'plano' }),
  rotas: () => ({ id: '', setores: [], chegada_onda: '', nivel_emergencia: 1 }),
  acessos: () => ({ id: '', largura: 1.2, terreno: 'plano', rotas: [] }),
  pontos_encontro: () => ({ nome: '', endereco: '', populacao: 0, area: 0 }),
};

// Estado da conferencia: o calculo e sempre do servidor (simular), nunca do navegador.
export function usePaeEvacuacaoForm(protocoloId, conferencia) {
  const entrada = conferencia?.entrada ?? null;
  const form = useForm({
    setores: entrada?.setores.map((s) => ({ ...s, lados: s.lados ?? 2 })) ?? [FABRICAS.setores()],
    rotas: entrada?.rotas.map(({ id, setores, chegada_onda, nivel_emergencia }) => ({ id, setores, chegada_onda, nivel_emergencia })) ?? [FABRICAS.rotas()],
    acessos: entrada?.acessos.map((a) => ({ ...a })) ?? [],
    pontos_encontro: entrada?.pontos_encontro.map((p) => ({ ...p })) ?? [FABRICAS.pontos_encontro()],
    tte_declarado: entrada?.tte_declarado ?? '',
    num_sei: '',
    observacao: '',
    chave_idempotencia: crypto.randomUUID(),
  });
  const resultado = ref(conferencia?.resultado ?? null);
  const simulado = ref(conferencia !== null);
  const simulando = ref(false);
  const errosSimulacao = ref({});

  watch(() => [form.setores, form.rotas, form.acessos, form.pontos_encontro, form.tte_declarado], () => {
    simulado.value = false;
  }, { deep: true });

  function entradaAtual() {
    return {
      setores: form.setores,
      rotas: form.rotas,
      acessos: form.acessos,
      pontos_encontro: form.pontos_encontro,
      tte_declarado: form.tte_declarado || null,
    };
  }

  async function simular() {
    simulando.value = true;
    errosSimulacao.value = {};
    try {
      const { data } = await axios.post(route('pae.protocolo.evacuacao.simular', protocoloId), entradaAtual());
      resultado.value = data;
      simulado.value = true;
    } catch (erro) {
      if (erro.response?.status !== 422) throw erro;
      errosSimulacao.value = Object.fromEntries(Object.entries(erro.response.data.errors).map(([campo, mensagens]) => [campo, mensagens[0]]));
    } finally {
      simulando.value = false;
    }
  }

  function registrar() {
    form.transform((dados) => ({ ...dados, tte_declarado: dados.tte_declarado || null }))
      .post(route('pae.protocolo.evacuacao.registrar', protocoloId), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset('num_sei', 'observacao');
          form.chave_idempotencia = crypto.randomUUID();
        },
      });
  }

  function adicionar(lista) {
    form[lista].push(FABRICAS[lista]());
  }

  function remover(lista, indice) {
    form[lista].splice(indice, 1);
  }

  const erros = computed(() => ({ ...errosSimulacao.value, ...form.errors }));

  return { form, resultado, simulado, simulando, erros, simular, registrar, adicionar, remover };
}
```

- [ ] **Step 2: Organismos.**

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Resultado</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">TTE = maior valor entre o tempo máximo de deslocamento e o estrangulamento.</p>
      </div>
      <span v-if="resultado" class="rounded-full px-3 py-1 text-sm font-semibold" :class="conforme ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
        {{ conforme ? 'Conforme' : 'Não conforme' }}
      </span>
    </div>
    <p v-if="!resultado" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Preencha os dados e clique em Simular.</p>
    <template v-else>
      <dl class="mt-4 grid gap-3 sm:grid-cols-3">
        <div v-for="item in tempos" :key="item.rotulo" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
          <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ item.rotulo }}</dt>
          <dd class="text-xl font-bold text-slate-900 dark:text-white">{{ item.valor ?? '—' }}</dd>
        </div>
      </dl>
      <ul class="mt-4 space-y-1 text-sm">
        <li :class="resultado.criterio1_conforme ? okClasse : erroClasse">Critério 1, pontos de encontro (menos de 3 pessoas/m²): {{ resultado.criterio1_conforme ? 'atende' : 'não atende' }}</li>
        <li :class="resultado.criterio2_conforme ? okClasse : erroClasse">Critério 2, rotas de fuga (saída antes da chegada da onda): {{ resultado.criterio2_conforme ? 'atende' : 'não atende' }}</li>
        <li v-if="resultado.possui_rota_invalida" :class="erroClasse">Há rota de fuga inválida.</li>
        <li v-if="resultado.possui_setor_inviavel" :class="erroClasse">Há setor sem tempo calculável.</li>
        <li v-if="resultado.excede_declarado" :class="erroClasse">O tempo calculado excede o declarado pelo empreendedor.</li>
      </ul>
      <p v-if="!simulado" class="mt-3 text-sm text-amber-700 dark:text-amber-300">Os dados mudaram desde a última simulação.</p>
    </template>
    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">A conferência apenas sinaliza: não bloqueia a tramitação nem a emissão do CCPAE.</p>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
});

const okClasse = 'text-green-700 dark:text-green-300';
const erroClasse = 'text-red-700 dark:text-red-300';

const conforme = computed(() => {
  const r = props.resultado;
  return Boolean(r && r.criterio1_conforme && r.criterio2_conforme && !r.possui_rota_invalida && !r.possui_setor_inviavel && !r.excede_declarado);
});

const tempos = computed(() => [
  { rotulo: 'TTE', valor: props.resultado?.tte_fmt },
  { rotulo: 'TMD', valor: props.resultado?.tmd_fmt },
  { rotulo: 'TE', valor: props.resultado?.te_fmt },
]);
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Setores de evacuação</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Sem calçada, a largura da rua desconta 2,90 m (mão única) ou 5,80 m (mão dupla).</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar setor</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Setor</th><th class="px-2 py-2">Moradores</th><th class="px-2 py-2">Comercial</th>
            <th class="px-2 py-2">Via</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Lados</th>
            <th class="px-2 py-2">Distância (m)</th><th class="px-2 py-2">Terreno</th>
            <th class="px-2 py-2">Densidade</th><th class="px-2 py-2">Velocidade</th><th class="px-2 py-2">Tempo</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(setor, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="setor.id" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'w-16']" /><InputError :message="erros[`setores.${i}.id`]" /></td>
            <td class="px-2 py-2"><input v-model.number="setor.populacao" type="number" min="0" step="1" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-24']" /><InputError :message="erros[`setores.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><input v-model="setor.comercial" type="checkbox" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2">
              <select v-model="setor.via" :disabled="somenteLeitura" :class="CLASSE_CAMPO">
                <option value="calcada">Calçada</option><option value="rua_mao_unica">Rua mão única</option><option value="rua_mao_dupla">Rua mão dupla</option>
              </select>
            </td>
            <td class="px-2 py-2"><input v-model.number="setor.largura" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-20']" /><InputError :message="erros[`setores.${i}.largura`]" /></td>
            <td class="px-2 py-2">
              <select v-if="setor.via === 'calcada'" v-model.number="setor.lados" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option :value="1">1</option><option :value="2">2</option></select>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-2 py-2"><input v-model.number="setor.distancia" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-24']" /><InputError :message="erros[`setores.${i}.distancia`]" /></td>
            <td class="px-2 py-2">
              <select v-model="setor.terreno" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option value="plano">Plano</option><option value="inclinado">Inclinado (&gt; 5%)</option></select>
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.densidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.velocidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(setor)?.tempo_fmt ?? '—' }}
              <span v-if="SITUACOES_SETOR[calculo(setor)?.situacao]" class="block text-xs text-red-700 dark:text-red-300">{{ SITUACOES_SETOR[calculo(setor).situacao] }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.setores" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, SITUACOES_SETOR, numero } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (setor) => props.resultado?.setores?.[setor.id] ?? null;
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Rotas de fuga (Critério 2)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Setores na ordem do percurso, separados por vírgula. Disponíveis: {{ setoresDisponiveis.join(', ') || 'nenhum' }}.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar rota</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Rota</th><th class="px-2 py-2">Setores</th><th class="px-2 py-2">Chegada da onda (mm:ss)</th>
            <th class="px-2 py-2">Nível</th><th class="px-2 py-2">TERF</th><th class="px-2 py-2">Saída</th><th class="px-2 py-2">Saída &lt; onda?</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(rota, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="rota.id" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'w-16']" /><InputError :message="erros[`rotas.${i}.id`]" /></td>
            <td class="px-2 py-2">
              <input :value="rota.setores.join(', ')" :disabled="somenteLeitura" placeholder="A, D, E" :class="[CLASSE_CAMPO, 'w-32']" @change="rota.setores = separar($event.target.value)" />
              <InputError :message="erros[`rotas.${i}.setores`]" />
            </td>
            <td class="px-2 py-2"><input v-model.trim="rota.chegada_onda" :disabled="somenteLeitura" placeholder="15:00" maxlength="6" :class="[CLASSE_CAMPO, 'w-20']" /><InputError :message="erros[`rotas.${i}.chegada_onda`]" /></td>
            <td class="px-2 py-2">
              <select v-model.number="rota.nivel_emergencia" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option :value="1">1</option><option :value="2">2</option><option :value="3">3</option></select>
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.terf_fmt ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.saida_fmt ?? '—' }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(rota)" :class="calculo(rota).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(rota).conforme ? 'Sim' : 'Não' }}</span>
              <span v-if="calculo(rota)?.motivo" class="block text-xs text-red-700 dark:text-red-300">{{ MOTIVOS_ROTA[calculo(rota).motivo] }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.rotas" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, MOTIVOS_ROTA } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  setoresDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (rota) => props.resultado?.rotas?.[rota.id] ?? null;
const separar = (texto) => texto.split(',').map((parte) => parte.trim()).filter(Boolean);
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Acessos à área segura (estrangulamento)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Largura do ponto de maior afunilamento. Abaixo de 1,2 m a rota não pode ser usada (art. 48, §6º). Rotas: {{ rotasDisponiveis.join(', ') || 'nenhuma' }}.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar acesso</button>
    </header>
    <p v-if="!itens.length" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Sem acesso informado, o tempo total é o tempo máximo de deslocamento.</p>
    <div v-else class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Acesso</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Terreno</th><th class="px-2 py-2">Rotas</th><th class="px-2 py-2">Pessoas</th><th class="px-2 py-2">TE</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(acesso, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="acesso.id" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'w-16']" /><InputError :message="erros[`acessos.${i}.id`]" /></td>
            <td class="px-2 py-2"><input v-model.number="acesso.largura" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-20']" /><InputError :message="erros[`acessos.${i}.largura`]" /></td>
            <td class="px-2 py-2">
              <select v-model="acesso.terreno" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option value="plano">Plano</option><option value="inclinado">Rampa ou escada</option></select>
            </td>
            <td class="px-2 py-2">
              <input :value="acesso.rotas.join(', ')" :disabled="somenteLeitura" placeholder="R1, R2" :class="[CLASSE_CAMPO, 'w-28']" @change="acesso.rotas = separar($event.target.value)" />
              <InputError :message="erros[`acessos.${i}.rotas`]" />
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(acesso)?.n ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(acesso)?.te_fmt ?? '—' }}
              <span v-if="calculo(acesso)?.invalido" class="block text-xs text-red-700 dark:text-red-300">abaixo de 1,2 m</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  rotasDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (acesso) => props.resultado?.acessos?.[acesso.id] ?? null;
const separar = (texto) => texto.split(',').map((parte) => parte.trim()).filter(Boolean);
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pontos de encontro (Critério 1)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Atende quando a população estimada por m² é menor que 3.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar ponto</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Local</th><th class="px-2 py-2">Endereço</th><th class="px-2 py-2">População</th><th class="px-2 py-2">Área (m²)</th><th class="px-2 py-2">Pessoas/m²</th><th class="px-2 py-2">&lt; 3?</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(ponto, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="ponto.nome" :disabled="somenteLeitura" maxlength="255" :class="CLASSE_CAMPO" /><InputError :message="erros[`pontos_encontro.${i}.nome`]" /></td>
            <td class="px-2 py-2"><input v-model.trim="ponto.endereco" :disabled="somenteLeitura" maxlength="500" :class="CLASSE_CAMPO" /><InputError :message="erros[`pontos_encontro.${i}.endereco`]" /></td>
            <td class="px-2 py-2"><input v-model.number="ponto.populacao" type="number" min="0" step="1" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-24']" /><InputError :message="erros[`pontos_encontro.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><input v-model.number="ponto.area" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-28']" /><InputError :message="erros[`pontos_encontro.${i}.area`]" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(resultado?.pontos_encontro?.[i]?.densidade) }}</td>
            <td class="px-2 py-2">
              <span v-if="resultado?.pontos_encontro?.[i]" :class="resultado.pontos_encontro[i].conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ resultado.pontos_encontro[i].conforme ? 'Sim' : 'Não' }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.pontos_encontro" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, numero } from '@/utils/paeEvacuacao';

defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);
</script>
```

- [ ] **Step 3: Página.** `SDC/resources/js/Pages/PaeEvacuacao.vue`:

```vue
<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`Evacuação - ${protocolo.num_protocolo}`" />

    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">PAE · Resolução GMG nº 83/2024 · Anexo E</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Conferência de evacuação</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Protocolo {{ protocolo.num_protocolo }}</p>
      </div>
      <a :href="route('pae.protocolos.index')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Voltar aos protocolos</a>
    </header>

    <p v-if="historica" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
      Consultando a versão {{ conferencia.versao }}.
      <a :href="route('pae.protocolo.evacuacao.show', protocolo.id)" class="font-semibold underline">Ir para a versão atual ({{ versao_atual }})</a>
    </p>
    <p v-if="protocolo.arquivado" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Protocolo arquivado: conferência somente para consulta.</p>

    <EvacuacaoResultadoPainel :resultado="resultado" :simulado="simulado" />

    <EvacuacaoSetoresEditor :itens="form.setores" :erros="erros" :resultado="resultado" :somente-leitura="!can_edit" @adicionar="adicionar('setores')" @remover="remover('setores', $event)" />
    <EvacuacaoRotasEditor :itens="form.rotas" :erros="erros" :resultado="resultado" :setores-disponiveis="idsSetores" :somente-leitura="!can_edit" @adicionar="adicionar('rotas')" @remover="remover('rotas', $event)" />
    <EvacuacaoAcessosEditor :itens="form.acessos" :erros="erros" :resultado="resultado" :rotas-disponiveis="idsRotas" :somente-leitura="!can_edit" @adicionar="adicionar('acessos')" @remover="remover('acessos', $event)" />
    <EvacuacaoPontosEditor :itens="form.pontos_encontro" :erros="erros" :resultado="resultado" :somente-leitura="!can_edit" @adicionar="adicionar('pontos_encontro')" @remover="remover('pontos_encontro', $event)" />

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="grid gap-3 sm:grid-cols-3">
        <label class="block text-sm text-slate-700 dark:text-slate-200">Tempo total declarado pelo empreendedor (mm:ss)
          <input v-model.trim="form.tte_declarado" :disabled="!can_edit" placeholder="15:00" maxlength="6" :class="[CLASSE_CAMPO, 'mt-1']" />
          <InputError :message="erros.tte_declarado" />
        </label>
        <template v-if="can_edit">
          <label class="block text-sm text-slate-700 dark:text-slate-200">Número SEI
            <input v-model.trim="form.num_sei" maxlength="100" :class="[CLASSE_CAMPO, 'mt-1']" />
            <InputError :message="erros.num_sei" />
          </label>
          <label class="block text-sm text-slate-700 dark:text-slate-200">Observação
            <textarea v-model="form.observacao" rows="1" maxlength="5000" :class="[CLASSE_CAMPO, 'mt-1']" />
            <InputError :message="erros.observacao" />
          </label>
        </template>
      </div>
      <p v-if="erros.chave_idempotencia || erros.protocolo" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erros.chave_idempotencia || erros.protocolo }}</p>
      <div v-if="can_edit" class="mt-4 flex flex-wrap justify-end gap-3">
        <button type="button" :disabled="simulando" class="rounded-lg border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-700 disabled:opacity-50 dark:border-blue-300 dark:text-blue-300" @click="simular">{{ simulando ? 'Simulando...' : 'Simular' }}</button>
        <button type="button" :disabled="!simulado || form.processing || !form.num_sei" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="registrar">Registrar conferência</button>
      </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Histórico de conferências</h2>
      <p v-if="!historico.length" class="mt-2 text-slate-500 dark:text-slate-400">Nenhuma conferência registrada.</p>
      <ol v-else class="mt-3 space-y-2">
        <li v-for="item in historico" :key="item.versao" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
          <span class="text-slate-700 dark:text-slate-200">Versão {{ item.versao }} · {{ item.autor || '—' }} · {{ dataLocal(item.created_at) }} · SEI {{ item.num_sei }} · TTE {{ item.tte_fmt ?? '—' }}</span>
          <span class="flex items-center gap-3">
            <span :class="item.conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ item.conforme ? 'Conforme' : 'Não conforme' }}</span>
            <a :href="route('pae.protocolo.evacuacao.versao', [protocolo.id, item.versao])" class="font-semibold text-blue-700 underline dark:text-blue-300">Abrir</a>
          </span>
        </li>
      </ol>
    </section>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import EvacuacaoResultadoPainel from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue';
import EvacuacaoSetoresEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue';
import EvacuacaoRotasEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue';
import EvacuacaoAcessosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue';
import EvacuacaoPontosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue';
import { usePaeEvacuacaoForm } from '@/Composables/pae/usePaeEvacuacaoForm';
import { CLASSE_CAMPO } from '@/utils/paeEvacuacao';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  conferencia: { type: Object, default: null },
  historico: { type: Array, default: () => [] },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
  can_edit: { type: Boolean, default: false },
});

const { form, resultado, simulado, simulando, erros, simular, registrar, adicionar, remover } = usePaeEvacuacaoForm(props.protocolo.id, props.conferencia);

const idsSetores = computed(() => form.setores.map((s) => s.id).filter(Boolean));
const idsRotas = computed(() => form.rotas.map((r) => r.id).filter(Boolean));

function dataLocal(valor) {
  return valor ? new Date(valor).toLocaleDateString('pt-BR') : '—';
}
</script>
```

- [ ] **Step 4: Ação, selo da listagem e modal do CCPAE.**

`SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`:
- depois do bloco `v-if="protocolo.dcoSituacao"`:

```vue
      <div v-if="protocolo.evacuacaoSituacao" class="text-sm font-medium" :class="classeSituacaoEvacuacao(protocolo.evacuacaoSituacao)">
        Evacuação: {{ rotuloSituacaoEvacuacao(protocolo.evacuacaoSituacao) }}
      </div>
```

- no array de ações, depois da linha `label: 'DCO'`:

```js
          { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'Evacuação', handler: () => $emit('evacuacao', protocolo.id) },
```

- acrescentar `'evacuacao'` ao `defineEmits` (depois de `'dco'`) e, no `<script setup>`, `import { classeSituacaoEvacuacao, rotuloSituacaoEvacuacao } from '@/utils/paeEvacuacao';`.

`SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`: as mesmas três mudanças, com o selo em `mt-1 text-xs font-medium` depois do selo da DCO.

`SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue`: `@evacuacao="$emit('evacuacao', $event)"` depois de `@dco=...` e `'evacuacao'` no `defineEmits`.

`SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`:
- `@evacuacao="handleEvacuacao"` depois de cada `@dco="handleDco"` (grade e tabela);
- em `mapProtocolo`, depois de `dcoEmissaoPronta`: `evacuacaoSituacao: p.evacuacao_situacao ?? null,`;
- depois de `handleDco`:

```js
function handleEvacuacao(id) {
  router.visit(route('pae.protocolo.evacuacao.show', id));
}
```

`SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue`: depois do link "Conferir avaliação e declarações":

```vue
        <p class="text-sm text-slate-700 dark:text-slate-200">Evacuação: {{ rotuloSituacaoEvacuacao(protocolo?.evacuacaoSituacao) }} (informativo, não bloqueia).</p>
```

e `import { rotuloSituacaoEvacuacao } from '@/utils/paeEvacuacao';`.

- [ ] **Step 5: Build.** No Bash:

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-evacuacao/SDC && NM=$(cygpath -w /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-dco-implementation/SDC/node_modules) && MSYS_NO_PATHCONV=1 docker run --rm --mount "type=bind,source=$(cygpath -w "$PWD"),target=/app" --mount "type=bind,source=$NM,target=/app/node_modules" -w /app --entrypoint sh node:20-alpine -c 'npx vite build --outDir /tmp/build --emptyOutDir >/tmp/b.log 2>&1; echo "vite exit $?"; grep -iE "built in|error" /tmp/b.log | head; grep -c PaeEvacuacao /tmp/build/manifest.json'
```

Expected: `vite exit 0`, `built in`, contagem maior que 0.

### Task 6: Verificação final, revisão e integração

**Files:** todos os anteriores; nenhum teste no commit.

- [ ] **Step 1: Suíte e lint.** `RUN Pae` (Expected: `OK`, todos os testes PAE). Lint de cada PHP novo ou alterado no container:

```powershell
Set-Location C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-evacuacao; $sdc=(Resolve-Path SDC).Path; $m=@(); foreach($f in 'app','config','database','routes','tests','resources'){ $m+=@('--mount',"type=bind,source=$sdc\$f,target=/var/www/$f,readonly") }; $m+=@('--mount',"type=bind,source=$sdc\bootstrap,target=/var/www/bootstrap"); docker run --rm --network newsdc-dev_default --env-file "$sdc\.env" -e APP_ENV=testing -e DB_HOST=newsdc_dev_db -e DB_DATABASE=pae_d_test -e DB_CONNECTION=pgsql --entrypoint sh @m newsdc-pae-d-test-runtime:local -c 'cd /var/www; composer dump-autoload -o --no-scripts -q; for f in $(find app/Modules/Pae -name "*Evacuacao*.php") app/Modules/Pae/Support/Evacuacao/*.php app/Modules/Pae/Controllers/PaeProtocoloController.php app/Modules/Pae/Models/PaeProtocolo.php app/Modules/Pae/PaeServiceProvider.php routes/modules/pae.php database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php; do php -l $f; done; php artisan route:list --path=evacuacao; php artisan migrate:rollback --step=1 --force; php artisan migrate --force'
```

Expected: `No syntax errors` em todos, 4 rotas `evacuacao`, rollback e migrate da migration da fase sem erro.

- [ ] **Step 2: Navegador.** Se o MCP do Playwright conectar, no homolog ou num app de prévia com esta branch: protocolo de teste, simular o exemplo do item 3.3, registrar, abrir a versão antiga, usuário só com `view` (sem botões), largura de 400 px e modo escuro. Se não conectar, registrar a pendência sem afirmar a verificação.

- [ ] **Step 3: Revisão do Git.** `git diff --check`, `git status --short`, `git diff --cached --name-only` sem nenhum arquivo `tests/`, `.superpowers/` ou `.env`; nenhum log de depuração (`grep -rn "dd(\|dump(\|console.log" SDC/app/Modules/Pae SDC/resources/js/Pages/PaeEvacuacao.vue SDC/resources/js/Components/Organisms/Pae/Evacuacao SDC/resources/js/Composables/pae SDC/resources/js/utils/paeEvacuacao.js` vazio).

- [ ] **Step 4: Commits.** Spec e plano já entram juntos no commit documental (`📝 docs(pae): especifica fase E da evacuação do Anexo E`). Feature completa em um commit:

```bash
git add SDC/app/Modules/Pae SDC/database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php SDC/routes/modules/pae.php SDC/resources/js/Pages/PaeEvacuacao.vue SDC/resources/js/Components/Organisms/Pae/Evacuacao SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js SDC/resources/js/utils/paeEvacuacao.js SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue SDC/resources/js/Components/Organisms/Pae/Protocolos SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue
git commit -m "✨ feat(pae): conferência de evacuação do Anexo E"
```

- [ ] **Step 5: Integração.** Somente depois da feature verificada e com autorização do usuário: merge `--no-ff` em `dev` pela worktree `.worktrees/merge-dev-tmp` (preservando a modificação RAT de outra frente), push, imagem completa `docker build -f docker/swoole/Dockerfile` numa worktree limpa do `dev` e recriação das 4 réplicas do homolog com `HA_APP_IMAGE`, conferindo saúde, migration e a rota `evacuacao` em cada porta.
