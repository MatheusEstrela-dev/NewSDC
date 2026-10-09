# PAE F: relatório anual de exercício simulado (Anexo C) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** a CEDEC registra a exigibilidade do simulado e cada relatório anual do Anexo C no protocolo, confere os 8 critérios reprováveis do item 8.1 com indícios calculados pelo sistema, bloqueia a emissão do CCPAE sem relatório validado vigente e acompanha a periodicidade anual na listagem.

**Architecture:** o motor puro `SimuladoAnexoC` (catálogo, validação, indícios) e `PaeSimuladoJanela` (12 meses) decidem tudo; `PaeSimuladoService` valida, normaliza, calcula `validado` e `indicios` no servidor e grava registros imutáveis com `lockForUpdate` no protocolo; `PaeCcpaeService::emitir` chama `evidenciaParaEmissao` logo depois da guarda da DCO. Três mecanismos hoje duplicados (idempotência, PDF no disco `pae`, anotação da listagem) são extraídos antes, sem mudar o comportamento da DCO e da evacuação.

**Tech Stack:** Laravel 12, PHP 8.4 (container), PostgreSQL, Inertia 2, Vue 3, Tailwind, axios, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-09-pae-f-simulados-anexo-c-design.md`. Pesquisa de campos e do item 8.1: `C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-evacuacao\.superpowers\research-simulados-anexo-c.md`.

## Global Constraints

- Branch `feat/pae-simulados-anexo-c`, worktree `C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-simulados`, base `origin/dev` em `6f840025`.
- Validação do relatório: **os 8 critérios reprováveis do item 8.1 (números 1 a 8) decidem `validado`**. Os critérios 9 e 10 (Art. 100) e os objetivos VII e VIII do Art. 98 são informativos e nunca reprovam.
- O analista da CEDEC marca "atende/não atende"; o servidor calcula `validado` e `indicios` e nunca aceita esses dois campos do cliente. Indício não reprova sozinho.
- Efeito: bloqueia a emissão do CCPAE quando o simulado é exigível e não há relatório validado vigente; na vigência, ausência ou não validação geram situação e alerta, sem suspensão nem revogação automática.
- Janela de vigência: realização entre a data de referência menos 12 meses (sem estouro de mês, `subMonthsNoOverflow`) e a própria referência, inclusive nos dois extremos.
- Permissões: `pae.protocolos.view` para ler e baixar; `pae.protocolos.validar` para avaliar e registrar. Nenhum slug novo.
- Uma migration da fase: `SDC/database/migrations/2026_10_09_120000_create_pae_simulado_registros.php`; qualquer ajuste de schema é consolidado nela, nenhuma migration aplicada é editada.
- Sem emoji no código; DRY/SOLID; `declare(strict_types=1)` e `final class` no PHP; nenhum log de depuração no fim.
- Testes ficam fora do commit (o `SDC/.gitignore` já ignora `tests`). Um commit por tarefa, com gitmoji em português, exatamente os títulos de cada tarefa (`♻️ refactor(pae): ...`, `✨ feat(pae): ...`, `🗃️ db(pae): ...`) e **sem** trailer `Co-Authored-By` (regra de ouro do usuário, que prevalece sobre o trailer padrão da ferramenta). Spec e plano entram juntos no commit documental `📝 docs(pae): especifica fase F dos simulados do Anexo C` (Task 1, Step 1).
- PHP do host (8.1) não serve: os testes rodam pelo runner em container (copiado na Task 1, Step 1). `SDC/.env` e `SDC/bootstrap/cache` já existem nesta worktree. `.superpowers/` está excluído localmente por `.git/info/exclude`.
- Os testes locais de DCO e evacuação (git-ignorados) vivem na worktree `pae-evacuacao` e são copiados para esta worktree na Task 1; eles são a rede de segurança das extrações.
- Inertia roda com `ensure_pages_exist`: a página `PaeSimulados.vue` precisa existir antes do primeiro teste HTTP que a afirma; por isso a Task 6 cria uma página mínima e a Task 7 a substitui.
- Padrão visual (spec, "Padrão visual das telas do PAE"): as telas de DCO, evacuação, ficha do Anexo B e simulados usam cabeçalho com ícone, selo e contexto, abas, seções recolhíveis e os átomos e moléculas de formulário; sem inputs soltos, sem `0` como valor inicial de campo numérico. O módulo RAT não é alterado (decisão na seção "Padrão visual das telas do PAE"). Tarefas visuais não mudam backend.
- Convenções de comando usadas abaixo:
  - `RUN <filtro>`: em PowerShell na raiz da worktree:

    ```powershell
    Set-Location C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-simulados; $ws='.superpowers/sdd/2026-10-09-pae-f-simulados-anexo-c'; & "$ws/run-phpunit.ps1" --filter='<filtro>' *> "$ws/run.log"; "exit $LASTEXITCODE"; Get-Content "$ws/run.log" | Select-String -Pattern 'OK \(|Tests:|^\d+\) |^-''|^\+''' | Select-Object -First 30
    ```

  - `BUILD`: no Bash, build do front com o `node_modules` da worktree `pae-dco-implementation`:

    ```bash
    cd /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-simulados/SDC && NM=$(cygpath -w /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-dco-implementation/SDC/node_modules) && MSYS_NO_PATHCONV=1 docker run --rm --mount "type=bind,source=$(cygpath -w "$PWD"),target=/app" --mount "type=bind,source=$NM,target=/app/node_modules" -w /app --entrypoint sh node:20-alpine -c 'npx vite build --outDir /tmp/build --emptyOutDir >/tmp/b.log 2>&1; echo "vite exit $?"; grep -iE "built in|error" /tmp/b.log | head; grep -c PaeSimulados /tmp/build/manifest.json'
    ```

    Expected: `vite exit 0`, `built in` e contagem maior que 0 (a contagem só vale a partir da Task 6, quando a página existe).

## Review Focus

As cinco classes de entrada de maior risco que a especificação não cobre sozinha, cada uma com teste fixado na tarefa dona:

1. **Booleanos de formulário multipart** (`"0"`, `"1"`, `"false"`): `atende` = `"0"` nunca pode virar verdadeiro; `"false"` literal é recusado pela regra `boolean` em vez de virar `true` por truthiness. Coberto em `PaeSimuladoServiceTest::test_booleanos_multipart_como_texto_sao_interpretados_e_false_literal_e_recusado` (Task 4).
2. **Fronteiras de data da janela:** 29/02 como referência, realização em 28/02 e 29/02, referência com hora (23:59:59), vencimento consistente com a pertinência para todos os dias de 2023 a 2025. Coberto em `PaeSimuladoJanelaTest` (Task 2), incluindo o laço de consistência `test_vencimento_e_o_ultimo_dia_em_que_a_janela_contem_a_realizacao`.
3. **Tempos `mm:ss` malformados e na fronteira:** `12:60`, `1:5`, ` 12:30`, `1000:00`, `12:30:00` recusados no campo certo (`tempos.sem_dificuldade.0.saida`); `00:00` e `999:59` aceitos; saída igual à chegada e a tolerância de 1e-6. Coberto em `PaeSimuladoServiceTest::test_tempos_mm_ss_malformados_sao_recusados_no_campo_e_os_extremos_aceitos` (Task 4) e `SimuladoAnexoCTest::test_tolerancia_de_1e_6_na_comparacao_saida_onda` (Task 2).
4. **Justificativa só com espaços ou com espaço não separável (U+00A0):** o `trim()` do PHP e a regra `required` do Laravel não removem U+00A0; o motor usa `\S` com `/u` e a justificativa em branco vira erro em `criterios.N.justificativa`. Informativos (9 e 10) não exigem justificativa. Coberto em `SimuladoAnexoCTest::test_justificativa_em_branco_inclui_espacos_unicode` (Task 2) e `PaeSimuladoServiceTest::test_justificativa_so_com_espaco_nao_separavel_e_recusada` (Task 4).
5. **Upload inválido do PDF:** arquivo acima de 20 MiB, não PDF e ausente devolvem erro no campo `arquivo` sem criar linha nem arquivo no disco; falha depois de gravar remove o PDF. Coberto em `PaeSimuladoHttpTest::test_upload_rejeita_arquivo_ausente_grande_e_nao_pdf` (Task 6) e `PaeSimuladoServiceTest::test_falha_no_historico_desfaz_a_versao_e_remove_o_pdf` (Task 4).

## Mapa de arquivos

| Responsabilidade | Arquivos |
| --- | --- |
| Mecanismos compartilhados (Task 1) | Criar `SDC/app/Modules/Pae/Support/PaeIdempotencia.php`, `PaeArquivoPdf.php`, `PaeListagem.php`; modificar `PaeDcoService.php`, `PaeDcoController.php`, `PaeEvacuacaoService.php`, `Support/Evacuacao/TempoAnexoE.php`, `Requests/SimularEvacuacaoRequest.php` |
| Motor e janela | Criar `SDC/app/Modules/Pae/Support/Simulado/SimuladoAnexoC.php`, `PaeSimuladoJanela.php` |
| Persistência | Criar a migration e `SDC/app/Modules/Pae/Models/PaeSimuladoAvaliacao.php`, `PaeSimuladoRelatorio.php`; modificar `PaeProtocolo.php`, `PaeCcpae.php` |
| Regras de entrada | Criar `SDC/app/Modules/Pae/Requests/AvaliarSimuladoRequest.php`, `RegistrarSimuladoRequest.php`, `PreviaIndiciosSimuladoRequest.php` |
| Serviço | Criar `SDC/app/Modules/Pae/Services/PaeSimuladoService.php`; modificar `PaeServiceProvider.php`, `PaeCcpaeService.php` |
| HTTP | Criar `SDC/app/Modules/Pae/Controllers/PaeSimuladoController.php`; modificar `routes/modules/pae.php`, `PaeProtocoloController.php` |
| Componentes visuais genéricos (Tasks 7 e 8) | Criar `Components/Molecules/DetalheHeader.vue`, `Molecules/Form/FormFileField.vue`, `Molecules/Pae/PaeAviso.vue`, `Templates/Pae/PaeTelaLayout.vue`, `utils/paeTela.js`; modificar `Molecules/Form/FormField.vue`, `FormSelect.vue` (prop `ariaLabel`) e `Composables/ui/useBreadcrumb.js` |
| Redesenho das telas existentes (Tasks 9 a 11) | Substituir `Pages/PaeDco.vue`, `Pages/PaeEvacuacao.vue` e os cinco organismos de `Components/Organisms/Pae/Evacuacao/`, `Pages/PaeFichaAnexoB.vue`; modificar `Composables/pae/usePaeEvacuacaoForm.js`, `utils/paeEvacuacao.js` |
| Tela de simulados (Task 12) | Criar `utils/paeSimulado.js`, `Composables/pae/usePaeSimuladoForm.js`, oito organismos em `Components/Organisms/Pae/Simulados/`; substituir `Pages/PaeSimulados.vue`; modificar card, tabela, grid, template da listagem e `EmitirCcpaeModal.vue` |
| Testes locais | `SDC/tests/Unit/Pae/Simulado/*Test.php`, `SDC/tests/Feature/Pae/PaeSuporteCompartilhadoTest.php`, `PaeSimulado*Test.php`; ajuste em `PaeDcoEmissaoTest.php` (todos ignorados pelo git) |

---

### Task 1: Extrair os mecanismos compartilhados (idempotência, PDF, listagem)

**Files:**
- Create: `SDC/app/Modules/Pae/Support/PaeIdempotencia.php`
- Create: `SDC/app/Modules/Pae/Support/PaeArquivoPdf.php`
- Create: `SDC/app/Modules/Pae/Support/PaeListagem.php`
- Modify: `SDC/app/Modules/Pae/Services/PaeDcoService.php`
- Modify: `SDC/app/Modules/Pae/Controllers/PaeDcoController.php`
- Modify: `SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php`
- Modify: `SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php`
- Modify: `SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php`
- Test: `SDC/tests/Feature/Pae/PaeSuporteCompartilhadoTest.php` (novo); regressão com os testes locais de DCO e evacuação

**Interfaces:**
- Consumes: nada.
- Produces:
  - `PaeIdempotencia::exigirMesmosDados(Model $existente, array $dados, array $campos): void` (lança `ValidationException` na chave `chave_idempotencia`). Compara escalares como texto (datas em `Y-m-d`, booleanos como `1`/`0`), números como `float` com tolerância 1e-9, arrays recursivamente sem depender da ordem das chaves (o `jsonb` não preserva ordem).
  - `PaeArquivoPdf::executar(Closure $operacao): mixed` (a closure recebe a instância; qualquer `Throwable` remove o PDF gravado e propaga), `PaeArquivoPdf->guardar(string $diretorio, UploadedFile $arquivo, string $mensagemFalha): array` com `arquivo_path`, `arquivo_nome_original`, `arquivo_mime`, `arquivo_tamanho_bytes`, `PaeArquivoPdf::existe(string $path): bool`, `PaeArquivoPdf::baixar(string $path, string $nome): StreamedResponse`, constante `DISCO = 'pae'`.
  - `PaeListagem::anotar(LengthAwarePaginator $pagina, Closure $carregar, Closure $campos): LengthAwarePaginator`: `$carregar(list<int> $ids): mixed` roda uma vez por página (0 vezes se vazia); `$campos(int $id, mixed $contexto): array` devolve os campos acrescentados a cada item.
  - `TempoAnexoE::REGRA_MM_SS` (string de regra de validação `regex:/^\d{1,3}:[0-5]\d$/`).

- [ ] **Step 1: Preparar o ambiente e commitar a documentação.**

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/pae-simulados
mkdir -p .superpowers/sdd/2026-10-09-pae-f-simulados-anexo-c SDC/tests/Unit/Pae
cp ../pae-evacuacao/.superpowers/sdd/2026-10-08-pae-e-evacuacao-anexo-e/run-phpunit.ps1 .superpowers/sdd/2026-10-09-pae-f-simulados-anexo-c/
cp ../pae-evacuacao/SDC/tests/Feature/Pae/PaeDco*.php ../pae-evacuacao/SDC/tests/Feature/Pae/PaeEvacuacao*.php SDC/tests/Feature/Pae/
cp -r ../pae-evacuacao/SDC/tests/Unit/Pae/Evacuacao SDC/tests/Unit/Pae/
git check-ignore .superpowers/x SDC/.env SDC/tests/Feature/Pae/PaeDcoServiceTest.php | wc -l
git add docs/superpowers/specs/2026-10-09-pae-f-simulados-anexo-c-design.md docs/superpowers/plans/2026-10-09-pae-f-simulados-anexo-c.md
git commit -m "📝 docs(pae): especifica fase F dos simulados do Anexo C"
```

Expected: `3` (os três caminhos são ignorados) e o commit documental criado. Daqui em diante, `RUN <filtro>` segue a convenção dos Global Constraints.

- [ ] **Step 2: Linha de base.** `RUN Pae`. Expected: `OK (N tests, ...)`. Anotar `N` como BASE; o runner aplica as migrations pendentes no banco isolado `pae_d_test`. Se não for `OK`, parar: a extração só começa com a base verde.

- [ ] **Step 3: Escrever o teste que falha.** `SDC/tests/Feature/Pae/PaeSuporteCompartilhadoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Modules\Pae\Support\PaeArquivoPdf;
use App\Modules\Pae\Support\PaeIdempotencia;
use App\Modules\Pae\Support\PaeListagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

final class PaeSuporteCompartilhadoTest extends TestCase
{
    public function test_idempotencia_compara_escalares_datas_e_numeros(): void
    {
        $existente = $this->existente(['texto' => 'abc', 'numero' => 3, 'dt' => '2026-05-10', 'nulo' => null, 'marca' => true]);

        PaeIdempotencia::exigirMesmosDados($existente, [
            'texto' => 'abc', 'numero' => '3', 'dt' => '2026-05-10', 'nulo' => null, 'marca' => true,
        ], ['texto', 'numero', 'dt', 'nulo', 'marca']);

        $this->addToAssertionCount(1);
    }

    public function test_idempotencia_compara_arrays_sem_depender_da_ordem_das_chaves_e_recusa_texto_numerico_parecido(): void
    {
        $existente = $this->existente(['itens' => ['b' => ['x' => 2, 'y' => 'ok'], 'a' => [1.5, 2]]]);

        PaeIdempotencia::exigirMesmosDados($existente, [
            'itens' => ['a' => [1.5, 2.0], 'b' => ['y' => 'ok', 'x' => 2.0]],
        ], ['itens']);
        $this->addToAssertionCount(1);

        $textual = $this->existente(['itens' => ['nome' => '1e3']]);
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($textual, ['itens' => ['nome' => '1000']], ['itens']));
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($textual, ['itens' => ['nome' => '1e3', 'extra' => null]], ['itens']));
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($textual, ['itens' => []], ['itens']));
    }

    public function test_idempotencia_recusa_campo_diferente_na_chave_de_idempotencia(): void
    {
        $existente = $this->existente(['texto' => 'abc', 'numero' => 3, 'dt' => '2026-05-10', 'marca' => true]);

        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($existente, ['texto' => 'abd'], ['texto']));
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($existente, ['numero' => 4], ['numero']));
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($existente, ['dt' => '2026-05-11'], ['dt']));
        $this->assertRecusado(fn () => PaeIdempotencia::exigirMesmosDados($existente, ['marca' => false], ['marca']));
    }

    public function test_pdf_guardado_permanece_quando_a_operacao_conclui(): void
    {
        Storage::fake('pae');
        $arquivo = UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf');

        $metadados = PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): array => $pdf->guardar('teste/1', $arquivo, 'falha'));

        $this->assertStringStartsWith('teste/1/', $metadados['arquivo_path']);
        $this->assertStringEndsWith('.pdf', $metadados['arquivo_path']);
        $this->assertSame('relatorio.pdf', $metadados['arquivo_nome_original']);
        $this->assertSame('application/pdf', $metadados['arquivo_mime']);
        $this->assertSame(10 * 1024, $metadados['arquivo_tamanho_bytes']);
        Storage::disk('pae')->assertExists($metadados['arquivo_path']);
    }

    public function test_pdf_guardado_e_removido_quando_a_operacao_falha(): void
    {
        Storage::fake('pae');
        $arquivo = UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf');

        try {
            PaeArquivoPdf::executar(function (PaeArquivoPdf $pdf) use ($arquivo): never {
                $pdf->guardar('teste/2', $arquivo, 'falha');
                throw new RuntimeException('falha depois de guardar');
            });
            $this->fail('A falha deveria propagar.');
        } catch (RuntimeException $e) {
            $this->assertSame('falha depois de guardar', $e->getMessage());
            $this->assertSame([], Storage::disk('pae')->allFiles());
        }
    }

    public function test_pdf_nao_gravado_pelo_disco_lanca_e_nao_deixa_arquivo(): void
    {
        Storage::fake('pae');
        $disco = Mockery::mock(Storage::disk('pae'))->makePartial();
        $disco->shouldReceive('putFileAs')->andReturn(false);
        Storage::set('pae', $disco);
        $arquivo = UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf');

        try {
            PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): array => $pdf->guardar('teste/3', $arquivo, 'Não foi possível guardar.'));
            $this->fail('A gravação recusada deveria lançar.');
        } catch (RuntimeException $e) {
            $this->assertSame('Não foi possível guardar.', $e->getMessage());
            $this->assertSame([], Storage::disk('pae')->allFiles());
        }
    }

    public function test_pdf_existe_e_baixa_do_disco_pae(): void
    {
        Storage::fake('pae');
        $arquivo = UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf');
        $metadados = PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): array => $pdf->guardar('teste/4', $arquivo, 'falha'));

        $this->assertTrue(PaeArquivoPdf::existe($metadados['arquivo_path']));
        $this->assertFalse(PaeArquivoPdf::existe('teste/4/inexistente.pdf'));

        $resposta = PaeArquivoPdf::baixar($metadados['arquivo_path'], 'nome.pdf');
        $this->assertInstanceOf(StreamedResponse::class, $resposta);
        $this->assertStringContainsString('nome.pdf', (string) $resposta->headers->get('content-disposition'));
    }

    public function test_listagem_carrega_uma_vez_e_acrescenta_campos(): void
    {
        $pagina = new LengthAwarePaginator([['id' => 1, 'nome' => 'A'], ['id' => 2, 'nome' => 'B']], 2, 15);
        $chamadas = 0;

        $resultado = PaeListagem::anotar(
            $pagina,
            function (array $ids) use (&$chamadas): array {
                $chamadas++;

                return array_fill_keys($ids, 'x');
            },
            fn (int $id, array $contexto): array => ['extra' => $contexto[$id].$id, 'nome' => 'sobrescrito'],
        );

        $this->assertSame(1, $chamadas);
        $this->assertSame([
            ['id' => 1, 'nome' => 'sobrescrito', 'extra' => 'x1'],
            ['id' => 2, 'nome' => 'sobrescrito', 'extra' => 'x2'],
        ], $resultado->getCollection()->all());
    }

    public function test_listagem_vazia_nao_carrega_nada(): void
    {
        $pagina = new LengthAwarePaginator([], 0, 15);
        $chamadas = 0;

        $resultado = PaeListagem::anotar($pagina, function (array $ids) use (&$chamadas): array {
            $chamadas++;

            return [];
        }, fn (int $id, array $contexto): array => []);

        $this->assertSame(0, $chamadas);
        $this->assertSame($pagina, $resultado);
    }

    public function test_listagem_aceita_itens_modelo_e_arrays(): void
    {
        $item = new class
        {
            public int $id = 7;

            public function toArray(): array
            {
                return ['id' => 7, 'origem' => 'modelo'];
            }
        };
        $pagina = new LengthAwarePaginator([$item, ['id' => 8, 'origem' => 'array']], 2, 15);

        $resultado = PaeListagem::anotar($pagina, fn (array $ids): array => $ids, fn (int $id, array $ids): array => ['marca' => 'ok']);

        $this->assertSame([
            ['id' => 7, 'origem' => 'modelo', 'marca' => 'ok'],
            ['id' => 8, 'origem' => 'array', 'marca' => 'ok'],
        ], $resultado->getCollection()->all());
    }

    private function existente(array $atributos): Model
    {
        $modelo = new class extends Model
        {
            protected $guarded = [];

            protected $casts = ['dt' => 'date', 'itens' => 'array'];
        };

        return $modelo->forceFill($atributos);
    }

    private function assertRecusado(callable $acao): void
    {
        try {
            $acao();
            $this->fail('A reutilização da chave com outros dados deveria ser recusada.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('chave_idempotencia', $e->errors());
        }
    }
}
```

- [ ] **Step 4: Rodar e ver falhar.** `RUN PaeSuporteCompartilhadoTest`. Expected: `Class "App\Modules\Pae\Support\PaeIdempotencia" not found`.

- [ ] **Step 5: Criar as três classes e a constante.**

`SDC/app/Modules/Pae/Support/PaeIdempotencia.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Mesma chave de idempotencia com dados de negocio diferentes e reuso indevido,
 * nao repeticao. O jsonb nao preserva a ordem das chaves e devolve 2 onde o
 * cliente mandou 2.0; a comparacao tolera as duas coisas e nada alem disso.
 */
final class PaeIdempotencia
{
    private const EPSILON = 1e-9;

    /**
     * @param  list<string>  $campos
     */
    public static function exigirMesmosDados(Model $existente, array $dados, array $campos): void
    {
        foreach ($campos as $campo) {
            if (! self::iguais($existente->getAttribute($campo), $dados[$campo] ?? null)) {
                throw ValidationException::withMessages([
                    'chave_idempotencia' => 'Esta chave de idempotência já foi usada com outros dados.',
                ]);
            }
        }
    }

    private static function iguais(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return is_array($a) && is_array($b) && self::arraysIguais($a, $b);
        }
        if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
            return abs((float) $a - (float) $b) < self::EPSILON;
        }

        return self::normalizar($a) === self::normalizar($b);
    }

    private static function arraysIguais(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $chave => $valor) {
            if (! array_key_exists($chave, $b) || ! self::iguais($valor, $b[$chave])) {
                return false;
            }
        }

        return true;
    }

    private static function normalizar(mixed $valor): string
    {
        return match (true) {
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d'),
            is_bool($valor) => $valor ? '1' : '0',
            default => (string) $valor,
        };
    }
}
```

`SDC/app/Modules/Pae/Support/PaeArquivoPdf.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * PDF dos registros do PAE no disco `pae`. Quem grava dentro de uma transacao
 * usa executar(): qualquer falha depois de guardar remove o arquivo orfao.
 */
final class PaeArquivoPdf
{
    public const DISCO = 'pae';

    private ?string $path = null;

    /**
     * @template T
     *
     * @param  Closure(self): T  $operacao
     * @return T
     */
    public static function executar(Closure $operacao): mixed
    {
        $guardador = new self();
        try {
            return $operacao($guardador);
        } catch (Throwable $e) {
            $guardador->descartar();
            throw $e;
        }
    }

    /**
     * @return array{arquivo_path: string, arquivo_nome_original: string, arquivo_mime: ?string, arquivo_tamanho_bytes: ?int}
     */
    public function guardar(string $diretorio, UploadedFile $arquivo, string $mensagemFalha): array
    {
        $nome = (string) Str::uuid().'.pdf';
        $path = "{$diretorio}/{$nome}";
        if (Storage::disk(self::DISCO)->putFileAs($diretorio, $arquivo, $nome) !== $path) {
            throw new RuntimeException($mensagemFalha);
        }
        $this->path = $path;

        return [
            'arquivo_path' => $path,
            'arquivo_nome_original' => $arquivo->getClientOriginalName(),
            'arquivo_mime' => $arquivo->getMimeType(),
            'arquivo_tamanho_bytes' => $arquivo->getSize(),
        ];
    }

    public static function existe(string $path): bool
    {
        return Storage::disk(self::DISCO)->exists($path);
    }

    public static function baixar(string $path, string $nome): StreamedResponse
    {
        return Storage::disk(self::DISCO)->download($path, $nome);
    }

    private function descartar(): void
    {
        if ($this->path !== null) {
            Storage::disk(self::DISCO)->delete($this->path);
        }
    }
}
```

`SDC/app/Modules/Pae/Support/PaeListagem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Anotacao de uma pagina de protocolos com dados de um modulo, em lote: uma
 * consulta por pagina, nunca por protocolo.
 */
final class PaeListagem
{
    /**
     * @param  Closure(list<int>): mixed  $carregar  carrega o contexto de todos os ids da pagina
     * @param  Closure(int, mixed): array<string, mixed>  $campos  campos acrescentados a um protocolo
     */
    public static function anotar(LengthAwarePaginator $pagina, Closure $carregar, Closure $campos): LengthAwarePaginator
    {
        $ids = $pagina->getCollection()->map(fn ($item): int => (int) data_get($item, 'id'))->all();
        if ($ids === []) {
            return $pagina;
        }

        $contexto = $carregar($ids);
        $pagina->setCollection($pagina->getCollection()->map(fn ($item): array => [
            ...(is_array($item) ? $item : $item->toArray()),
            ...$campos((int) data_get($item, 'id'), $contexto),
        ]));

        return $pagina;
    }
}
```

Em `SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php`, logo depois de `private const SUFIXO = '_segundos';`:

```php
    /** Regra de validacao de tempo no formato mm:ss, compartilhada por todas as telas de tempo do PAE. */
    public const REGRA_MM_SS = 'regex:/^\d{1,3}:[0-5]\d$/';
```

Em `SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php`: apagar a linha `private const MM_SS = 'regex:/^\d{1,3}:[0-5]\d$/';`, acrescentar `use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;` junto dos demais `use` e trocar as duas ocorrências de `self::MM_SS` por `TempoAnexoE::REGRA_MM_SS` (`sed -i 's/self::MM_SS/TempoAnexoE::REGRA_MM_SS/g'`).

- [ ] **Step 6: Rodar o teste novo.** `RUN PaeSuporteCompartilhadoTest`. Expected: `OK (10 tests, ...)`.

- [ ] **Step 7: Migrar `PaeDcoService` para os mecanismos (comportamento idêntico).**

Em `SDC/app/Modules/Pae/Services/PaeDcoService.php`:

1. Imports: remover `use DateTimeInterface;`, `use Illuminate\Database\Eloquent\Model;`, `use Illuminate\Support\Facades\Storage;`, `use Illuminate\Support\Str;`, `use RuntimeException;`, `use Throwable;` e acrescentar:

```php
use App\Modules\Pae\Support\PaeArquivoPdf;
use App\Modules\Pae\Support\PaeIdempotencia;
use App\Modules\Pae\Support\PaeListagem;
```

2. Em `avaliar` e em `registrarDocumento`, trocar `$this->exigirMesmosDados(` por `PaeIdempotencia::exigirMesmosDados(` (`sed -i 's/\$this->exigirMesmosDados(/PaeIdempotencia::exigirMesmosDados(/g'`).

3. Apagar os métodos privados `exigirMesmosDados` e `normalizar` com o docblock que os precede (da linha `/** Mesma chave com dados de negocio diferentes...` até o fim de `normalizar`).

4. Substituir o método `registrarDocumento` inteiro por:

```php
    public function registrarDocumento(PaeProtocolo $protocolo, array $dados, UploadedFile $arquivo, User $user): PaeDcoDocumento
    {
        $dados = Validator::make(
            $dados + ['arquivo' => $arquivo],
            RegistrarDcoRequest::regras($dados['competencia'] ?? null),
            RegistrarDcoRequest::mensagens(),
        )->validate();
        $dados['num_sei'] = trim($dados['num_sei']);
        if ($dados['num_sei'] === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }

        return PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): PaeDcoDocumento => DB::transaction(
            function () use ($protocolo, $dados, $arquivo, $user, $pdf): PaeDcoDocumento {
                $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
                $existente = $locked->documentosDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
                if ($existente !== null) {
                    PaeIdempotencia::exigirMesmosDados($existente, $dados, [
                        'competencia', 'resultado', 'dt_documento', 'dt_apresentacao', 'num_sei',
                    ]);

                    return $existente;
                }
                if ($locked->avaliacoesDco()->first()?->resultado !== 'aplicavel') {
                    throw ValidationException::withMessages(['avaliacao' => 'Registre antes uma avaliação de DCO aplicável.']);
                }

                $competencia = (int) $dados['competencia'];
                $versao = (int) $locked->documentosDco()->where('competencia', $competencia)->max('versao') + 1;
                $metadados = $pdf->guardar("dco/{$locked->id}/{$competencia}", $arquivo, 'Não foi possível guardar a DCO.');

                $documento = $locked->documentosDco()->create([
                    'competencia' => $competencia,
                    'versao' => $versao,
                    'resultado' => $dados['resultado'],
                    'dt_documento' => $dados['dt_documento'],
                    'dt_apresentacao' => $dados['dt_apresentacao'],
                    'num_sei' => $dados['num_sei'],
                    'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                    ...$metadados,
                    'chave_idempotencia' => $dados['chave_idempotencia'],
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                TimelinePae::registrar($locked, 'dco_documento',
                    "DCO {$competencia}, versão {$versao}: {$documento->resultado}. SEI {$documento->num_sei}.", $user);

                return $documento;
            },
        ));
    }
```

5. Em `evidenciaParaEmissao`, trocar `! Storage::disk('pae')->exists($documento->arquivo_path)` por `! PaeArquivoPdf::existe($documento->arquivo_path)`.

6. Substituir o método `anotarListagem` inteiro por:

```php
    public function anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator
    {
        return PaeListagem::anotar(
            $pagina,
            fn (array $ids): array => [
                'avaliacoes' => PaeDcoAvaliacao::query()->whereIn('protocolo_id', $ids)
                    ->orderByDesc('id')->get()->unique('protocolo_id')->keyBy('protocolo_id'),
                'documentos' => PaeDcoDocumento::query()->whereIn('protocolo_id', $ids)
                    ->whereBetween('competencia', [PaeDcoCiclo::competenciaExigivel($hoje), $hoje->year])
                    ->whereDate('dt_apresentacao', '<=', $hoje->toDateString())
                    ->whereDate('dt_documento', '<=', $hoje->toDateString())
                    ->orderByDesc('competencia')->orderByDesc('versao')->get()->groupBy('protocolo_id'),
                'certificados' => PaeCcpae::query()->whereIn('protocolo_id', $ids)
                    ->distinct()->pluck('protocolo_id')->flip(),
            ],
            function (int $id, array $contexto) use ($hoje): array {
                $avaliacao = $contexto['avaliacoes']->get($id);
                $porProtocolo = $contexto['documentos']->get($id, collect());
                $prova = $porProtocolo->first();

                return [
                    'dco_situacao' => $this->situacaoAnual($avaliacao, $porProtocolo, $hoje, $contexto['certificados']->has($id)),
                    'dco_emissao_pronta' => $avaliacao !== null && (
                        $avaliacao->resultado === 'nao_aplicavel' || $prova?->resultado === 'positiva'
                    ),
                ];
            },
        );
    }
```

Em `SDC/app/Modules/Pae/Controllers/PaeDcoController.php`: remover `use Illuminate\Support\Facades\Storage;`, acrescentar `use App\Modules\Pae\Support\PaeArquivoPdf;` e trocar o método `download` por:

```php
    public function download(PaeProtocolo $paeProtocolo, PaeDcoDocumento $paeDcoDocumento): StreamedResponse
    {
        abort_unless($paeDcoDocumento->protocolo_id === $paeProtocolo->id
            && PaeArquivoPdf::existe($paeDcoDocumento->arquivo_path), 404);

        return PaeArquivoPdf::baixar($paeDcoDocumento->arquivo_path, $paeDcoDocumento->arquivo_nome_original);
    }
```

- [ ] **Step 8: Migrar `PaeEvacuacaoService`.** Em `SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php`, acrescentar `use App\Modules\Pae\Support\PaeIdempotencia;` e `use App\Modules\Pae\Support\PaeListagem;`; trocar o bloco `if ($existente !== null) { ... }` de `registrar` por:

```php
            if ($existente !== null) {
                PaeIdempotencia::exigirMesmosDados($existente, [...$entrada, 'num_sei' => $numSei, 'observacao' => $observacao], [
                    'setores', 'rotas', 'acessos', 'pontos_encontro', 'tte_declarado_segundos', 'num_sei', 'observacao',
                ]);

                return $existente;
            }
```

e o método `anotarListagem` inteiro por:

```php
    public function anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator
    {
        return PaeListagem::anotar(
            $pagina,
            fn (array $ids) => PaeEvacuacaoConferencia::query()
                ->whereIn('id', PaeEvacuacaoConferencia::query()->selectRaw('max(id)')->whereIn('protocolo_id', $ids)->groupBy('protocolo_id'))
                ->get(['protocolo_id', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida', 'possui_setor_inviavel', 'excede_declarado'])
                ->keyBy('protocolo_id'),
            function (int $id, $vigentes): array {
                $conferencia = $vigentes->get($id);

                return ['evacuacao_situacao' => match (true) {
                    $conferencia === null => 'nao_conferida',
                    $conferencia->conforme() => 'conforme',
                    default => 'nao_conforme',
                }];
            },
        );
    }
```

- [ ] **Step 9: Regressão completa.** `RUN Pae`. Expected: `OK (BASE + 10 tests, ...)` (BASE do Step 2 mais os 10 testes novos), sem nenhum teste de DCO ou evacuação alterado. Lint: `docker run` do Task 8, Step 1, restrito a estes arquivos, ou `RUN Pae` já acusa erro de sintaxe.

- [ ] **Step 10: Commit.**

```bash
git add SDC/app/Modules/Pae/Support/PaeIdempotencia.php SDC/app/Modules/Pae/Support/PaeArquivoPdf.php SDC/app/Modules/Pae/Support/PaeListagem.php SDC/app/Modules/Pae/Services/PaeDcoService.php SDC/app/Modules/Pae/Controllers/PaeDcoController.php SDC/app/Modules/Pae/Services/PaeEvacuacaoService.php SDC/app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php SDC/app/Modules/Pae/Requests/SimularEvacuacaoRequest.php
git commit -m "♻️ refactor(pae): extrai idempotência, PDF e listagem compartilhados"
```

---

### Task 2: Motor `SimuladoAnexoC` e janela de 12 meses

**Files:**
- Create: `SDC/app/Modules/Pae/Support/Simulado/SimuladoAnexoC.php`
- Create: `SDC/app/Modules/Pae/Support/Simulado/PaeSimuladoJanela.php`
- Test: `SDC/tests/Unit/Pae/Simulado/SimuladoAnexoCTest.php`, `PaeSimuladoJanelaTest.php`

**Interfaces:**
- Consumes: nada (classes puras).
- Produces:
  - `SimuladoAnexoC::catalogo(): list<array{numero:int, indice:string, criterio:string, reprovavel:bool}>`; `numeros(): list<int>` (1 a 10); `numerosReprovaveis(): list<int>` (1 a 8); constantes `CATEGORIAS` (`sem_dificuldade`, `com_dificuldade`, `ensino`, `hospitalares_prisionais`, `aglomeracao`) e `TOLERANCIA = 1e-6`.
  - `SimuladoAnexoC::validado(array $criterios): bool`; `exigeJustificativa(int $numero, mixed $atende): bool`; `justificativasFaltantes(array $criterios): list<int>`.
  - `SimuladoAnexoC::indicios(array $tempos, array $alarme): array<int, list<array{codigo:string, categoria:?string, linha:?int, nome:?string}>>` com sempre as 10 chaves. Códigos: `saida_maior_igual_onda`, `houve_problemas`, `ponto_invalido`, `alarme_sem_morador`.
  - `PaeSimuladoJanela::inicio(CarbonImmutable $referencia): CarbonImmutable`; `contem(DateTimeInterface|string $realizacao, CarbonImmutable $referencia): bool`; `vencimento(DateTimeInterface|string $realizacao): CarbonImmutable` (último dia de referência em que a janela ainda contém a realização).
- Formas de entrada (normalizadas pelo serviço na Task 4):
  - `criterios`: `[numero => ['atende' => bool, 'justificativa' => ?string]]` para 1 a 10.
  - `tempos`: `[categoria => list<['nome' => string, 'populacao' => ?int, 'chegada_onda_segundos' => int|float, 'saida_segundos' => int|float, 'houve_problemas' => bool, 'ponto_valido' => bool, 'estimativa' => bool, 'nivel_emergencia' => ?int]>]`.
  - `alarme`: `['audivel_todos' => bool, 'morador_nome' => ?string, 'morador_localizacao' => ?string]`.
- Decisões de leitura fixadas aqui (spec, "Ambiguidades"): o indício `ponto_invalido` aparece no critério da categoria (5 a 8) **e** no critério 4 (decisão 6 do spec, "ponto inválido numa linha vira indício" do critério 4); a categoria `ensino` não gera indício (informativa, decisão 8); justificativa obrigatória só nos critérios reprováveis 1 a 8, os informativos 9 e 10 aceitam nulo.

- [ ] **Step 1: Testes que falham.**

`SDC/tests/Unit/Pae/Simulado/SimuladoAnexoCTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Simulado;

use App\Modules\Pae\Support\Simulado\SimuladoAnexoC;
use PHPUnit\Framework\TestCase;

final class SimuladoAnexoCTest extends TestCase
{
    public function test_catalogo_guarda_os_10_criterios_do_item_8_1_literalmente(): void
    {
        $esperado = [
            ['Avaliação das placas e sinalização de risco', 'Todas as placas estarem instaladas conforme previsto no PAE e nesta Resolução.'],
            ['Efetividade do sistema de alarme', 'Indicação do morador residente na ZAS que informou não ser audível o sistema de alarme (nome, localização).'],
            ['Avaliação das estratégias de comunicação de risco', 'Realização de todas as ações listadas no item comunicação de risco desta Resolução que regulamenta a elaboração do PAE.'],
            ['Avaliação dos pontos de encontro', 'Atendimento aos critérios estabelecidos nesta Resolução.'],
            ['Avaliação do tempo de saída das pessoas sem dificuldade de locomoção das áreas de risco', 'Tempo de saída das pessoas das áreas sujeitas à inundação.'],
            ['Avaliação do tempo gasto para retirada das pessoas com dificuldade de locomoção', 'Tempo estimado para a retirada das pessoas com dificuldade de locomoção das áreas de risco.'],
            ['Avaliação do tempo gasto para a retirada das pessoas das unidades prisionais', 'Tempo estimado para a retirada de todas as pessoas das unidades prisionais.'],
            ['Avaliação do tempo gasto para a evacuação dos locais com grande aglomeração de pessoas', 'Tempo gasto para a evacuação de todas as pessoas dos locais com grande aglomeração e chegada em local seguro.'],
            ['Mensuração do número de pessoas participantes do exercício simulado', 'Percentual de participação de pessoas cadastradas no PAE nos exercícios simulados.'],
            ['Avaliar a mobilização da comunidade na participação de exercícios simulados', 'Percentual de participação de pessoas em relação ao simulado realizado em anos anteriores.'],
        ];

        $catalogo = SimuladoAnexoC::catalogo();

        $this->assertCount(10, $catalogo);
        $this->assertSame(range(1, 10), array_column($catalogo, 'numero'));
        $this->assertSame($esperado, array_map(fn (array $item): array => [$item['indice'], $item['criterio']], $catalogo));
        $this->assertSame(
            [true, true, true, true, true, true, true, true, false, false],
            array_column($catalogo, 'reprovavel'),
        );
        $this->assertSame(range(1, 10), SimuladoAnexoC::numeros());
        $this->assertSame(range(1, 8), SimuladoAnexoC::numerosReprovaveis());
    }

    public function test_oito_criterios_atendidos_validam_e_qualquer_reprovavel_nao_atendido_invalida(): void
    {
        $this->assertTrue(SimuladoAnexoC::validado($this->criterios()));

        foreach (range(1, 8) as $numero) {
            $this->assertFalse(SimuladoAnexoC::validado($this->criterios([$numero])), "o critério {$numero} deveria invalidar");
        }
    }

    public function test_informativos_nao_afetam_a_validacao(): void
    {
        $this->assertTrue(SimuladoAnexoC::validado($this->criterios([9, 10])));
        $this->assertTrue(SimuladoAnexoC::validado($this->criterios([9])));
        $this->assertFalse(SimuladoAnexoC::validado($this->criterios([9, 10, 4])));
    }

    public function test_validado_exige_booleano_verdadeiro_e_presenca_dos_8_criterios(): void
    {
        $inteiro = $this->criterios();
        $inteiro[4]['atende'] = 1;
        $texto = $this->criterios();
        $texto[4]['atende'] = 'true';
        $ausente = $this->criterios();
        unset($ausente[8]);
        $semAtende = $this->criterios();
        $semAtende[2] = ['justificativa' => null];

        $this->assertFalse(SimuladoAnexoC::validado($inteiro));
        $this->assertFalse(SimuladoAnexoC::validado($texto));
        $this->assertFalse(SimuladoAnexoC::validado($ausente));
        $this->assertFalse(SimuladoAnexoC::validado($semAtende));
        $this->assertFalse(SimuladoAnexoC::validado([]));
    }

    public function test_exige_justificativa_so_nos_reprovaveis_que_nao_atendem(): void
    {
        $this->assertTrue(SimuladoAnexoC::exigeJustificativa(3, false));
        $this->assertTrue(SimuladoAnexoC::exigeJustificativa(3, '0'));
        $this->assertTrue(SimuladoAnexoC::exigeJustificativa(3, 0));
        $this->assertFalse(SimuladoAnexoC::exigeJustificativa(3, true));
        $this->assertFalse(SimuladoAnexoC::exigeJustificativa(3, '1'));
        $this->assertFalse(SimuladoAnexoC::exigeJustificativa(3, null));
        $this->assertFalse(SimuladoAnexoC::exigeJustificativa(9, false));
        $this->assertFalse(SimuladoAnexoC::exigeJustificativa(10, false));
    }

    public function test_justificativa_em_branco_inclui_espacos_unicode(): void
    {
        $criterios = $this->criterios();
        $criterios[1] = ['atende' => false, 'justificativa' => 'Placa ausente.'];
        $criterios[2] = ['atende' => false, 'justificativa' => null];
        $criterios[3] = ['atende' => false, 'justificativa' => '   '];
        $criterios[4] = ['atende' => false, 'justificativa' => "\u{00A0}"];
        $criterios[5] = ['atende' => '0', 'justificativa' => ''];
        $criterios[6] = ['atende' => true, 'justificativa' => null];
        $criterios[9] = ['atende' => false, 'justificativa' => null];
        $criterios[10] = ['atende' => false, 'justificativa' => null];

        $this->assertSame([2, 3, 4, 5], SimuladoAnexoC::justificativasFaltantes($criterios));
        $this->assertSame([], SimuladoAnexoC::justificativasFaltantes($this->criterios()));
    }

    public function test_indicios_por_categoria_apontam_linha_e_nome(): void
    {
        $indicios = SimuladoAnexoC::indicios([
            'sem_dificuldade' => [
                $this->linha('Rota 1', 900, 899),
                $this->linha('Rota 2', 900, 900),
                $this->linha('Rota 3', 600, 300, true),
                $this->linha('Rota 4', 600, 300, false, false),
            ],
        ], $this->alarme(true));

        $this->assertSame(range(1, 10), array_keys($indicios));
        $this->assertSame([
            ['codigo' => 'saida_maior_igual_onda', 'categoria' => 'sem_dificuldade', 'linha' => 1, 'nome' => 'Rota 2'],
            ['codigo' => 'houve_problemas', 'categoria' => 'sem_dificuldade', 'linha' => 2, 'nome' => 'Rota 3'],
            ['codigo' => 'ponto_invalido', 'categoria' => 'sem_dificuldade', 'linha' => 3, 'nome' => 'Rota 4'],
        ], $indicios[5]);
        $this->assertSame([
            ['codigo' => 'ponto_invalido', 'categoria' => 'sem_dificuldade', 'linha' => 3, 'nome' => 'Rota 4'],
        ], $indicios[4]);
        foreach ([1, 2, 3, 6, 7, 8, 9, 10] as $numero) {
            $this->assertSame([], $indicios[$numero], "o critério {$numero} não deveria ter indício");
        }
    }

    public function test_cada_categoria_alimenta_o_seu_criterio_e_ensino_nao_gera_indicio(): void
    {
        $indicios = SimuladoAnexoC::indicios([
            'sem_dificuldade' => [$this->linha('Rota A', 600, 600)],
            'com_dificuldade' => [$this->linha('Grupo B', 600, 600)],
            'ensino' => [$this->linha('Escola C', 600, 700, true, false)],
            'hospitalares_prisionais' => [$this->linha('Hospital D', 600, 600)],
            'aglomeracao' => [$this->linha('Estádio E', 600, 600)],
        ], $this->alarme(true));

        $this->assertSame([['saida_maior_igual_onda', 'sem_dificuldade', 0, 'Rota A']], $this->resumir($indicios[5]));
        $this->assertSame([['saida_maior_igual_onda', 'com_dificuldade', 0, 'Grupo B']], $this->resumir($indicios[6]));
        $this->assertSame([['saida_maior_igual_onda', 'hospitalares_prisionais', 0, 'Hospital D']], $this->resumir($indicios[7]));
        $this->assertSame([['saida_maior_igual_onda', 'aglomeracao', 0, 'Estádio E']], $this->resumir($indicios[8]));
        $this->assertSame([], $indicios[4]);
        $this->assertSame([], $indicios[9]);
    }

    public function test_tolerancia_de_1e_6_na_comparacao_saida_onda(): void
    {
        $indicios = SimuladoAnexoC::indicios([
            'sem_dificuldade' => [
                $this->linha('L0', 900, 899.9999995),
                $this->linha('L1', 900, 899.999),
                $this->linha('L2', 900, 900.0),
                $this->linha('L3', 900, 1000),
                ['nome' => 'L4', 'populacao' => null, 'chegada_onda_segundos' => 900, 'saida_segundos' => null, 'houve_problemas' => false, 'ponto_valido' => true, 'estimativa' => false],
            ],
        ], $this->alarme(true));

        $this->assertSame([0, 2, 3], array_column($indicios[5], 'linha'));
        $this->assertSame(['saida_maior_igual_onda'], array_values(array_unique(array_column($indicios[5], 'codigo'))));
    }

    public function test_indicio_de_alarme_sem_morador_indicado(): void
    {
        $semIndicio = [
            $this->alarme(true),
            ['audivel_todos' => false, 'morador_nome' => 'Maria', 'morador_localizacao' => 'Rua A, 10'],
            [],
            ['audivel_todos' => null, 'morador_nome' => null, 'morador_localizacao' => null],
        ];
        foreach ($semIndicio as $alarme) {
            $this->assertSame([], SimuladoAnexoC::indicios([], $alarme)[2]);
        }

        $esperado = [['codigo' => 'alarme_sem_morador', 'categoria' => null, 'linha' => null, 'nome' => null]];
        foreach ([
            ['audivel_todos' => false, 'morador_nome' => 'Maria', 'morador_localizacao' => null],
            ['audivel_todos' => false, 'morador_nome' => null, 'morador_localizacao' => 'Rua A, 10'],
            ['audivel_todos' => false, 'morador_nome' => '  ', 'morador_localizacao' => "\u{00A0}"],
            ['audivel_todos' => false],
        ] as $alarme) {
            $this->assertSame($esperado, SimuladoAnexoC::indicios([], $alarme)[2]);
        }
    }

    /** Todos os 10 critérios atendem, exceto os listados. */
    private function criterios(array $naoAtendem = []): array
    {
        $criterios = [];
        foreach (range(1, 10) as $numero) {
            $criterios[$numero] = in_array($numero, $naoAtendem, true)
                ? ['atende' => false, 'justificativa' => 'Divergência apontada na verificação.']
                : ['atende' => true, 'justificativa' => null];
        }

        return $criterios;
    }

    private function linha(string $nome, int|float $chegada, int|float $saida, bool $problemas = false, bool $pontoValido = true): array
    {
        return [
            'nome' => $nome,
            'populacao' => null,
            'chegada_onda_segundos' => $chegada,
            'saida_segundos' => $saida,
            'houve_problemas' => $problemas,
            'ponto_valido' => $pontoValido,
            'estimativa' => false,
        ];
    }

    private function alarme(bool $audivel): array
    {
        return ['audivel_todos' => $audivel, 'morador_nome' => null, 'morador_localizacao' => null];
    }

    /** @return list<array{0: string, 1: ?string, 2: ?int, 3: ?string}> */
    private function resumir(array $indicios): array
    {
        return array_map(fn (array $i): array => [$i['codigo'], $i['categoria'], $i['linha'], $i['nome']], $indicios);
    }
}
```

`SDC/tests/Unit/Pae/Simulado/PaeSimuladoJanelaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Pae\Simulado;

use App\Modules\Pae\Support\Simulado\PaeSimuladoJanela;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class PaeSimuladoJanelaTest extends TestCase
{
    public function test_limites_exatos_sao_inclusivos_nos_dois_extremos(): void
    {
        $referencia = CarbonImmutable::parse('2026-10-09');

        $this->assertSame('2025-10-09', PaeSimuladoJanela::inicio($referencia)->toDateString());
        $this->assertTrue(PaeSimuladoJanela::contem('2025-10-09', $referencia));
        $this->assertFalse(PaeSimuladoJanela::contem('2025-10-08', $referencia));
        $this->assertTrue(PaeSimuladoJanela::contem('2026-10-09', $referencia));
        $this->assertFalse(PaeSimuladoJanela::contem('2026-10-10', $referencia));
        $this->assertTrue(PaeSimuladoJanela::contem('2026-01-01', $referencia));
    }

    public function test_ano_bissexto_nao_estoura_o_mes(): void
    {
        $bissexto = CarbonImmutable::parse('2024-02-29');
        $this->assertSame('2023-02-28', PaeSimuladoJanela::inicio($bissexto)->toDateString());
        $this->assertTrue(PaeSimuladoJanela::contem('2023-02-28', $bissexto));
        $this->assertFalse(PaeSimuladoJanela::contem('2023-02-27', $bissexto));

        $depois = CarbonImmutable::parse('2025-02-28');
        $this->assertSame('2024-02-28', PaeSimuladoJanela::inicio($depois)->toDateString());
        $this->assertTrue(PaeSimuladoJanela::contem('2024-02-29', $depois));
        $this->assertFalse(PaeSimuladoJanela::contem('2024-02-27', $depois));

        $primeiroDeMarco = CarbonImmutable::parse('2025-03-01');
        $this->assertSame('2024-03-01', PaeSimuladoJanela::inicio($primeiroDeMarco)->toDateString());
        $this->assertFalse(PaeSimuladoJanela::contem('2024-02-29', $primeiroDeMarco));
    }

    public function test_hora_da_referencia_e_da_realizacao_e_ignorada(): void
    {
        $fimDoDia = CarbonImmutable::parse('2026-10-09 23:59:59');
        $inicioDoDia = CarbonImmutable::parse('2026-10-09 00:00:01');

        $this->assertTrue(PaeSimuladoJanela::contem('2025-10-09', $fimDoDia));
        $this->assertTrue(PaeSimuladoJanela::contem(CarbonImmutable::parse('2025-10-09 23:30:00'), $inicioDoDia));
        $this->assertTrue(PaeSimuladoJanela::contem('2026-10-09 23:00:00', $inicioDoDia));
        $this->assertFalse(PaeSimuladoJanela::contem('2025-10-08 23:59:59', $fimDoDia));
        $this->assertFalse(PaeSimuladoJanela::contem('2026-10-10 00:00:00', $fimDoDia));
    }

    public function test_vencimento_em_datas_conhecidas(): void
    {
        $this->assertSame('2027-10-09', PaeSimuladoJanela::vencimento('2026-10-09')->toDateString());
        $this->assertSame('2025-02-28', PaeSimuladoJanela::vencimento('2024-02-29')->toDateString());
        $this->assertSame('2024-02-29', PaeSimuladoJanela::vencimento('2023-02-28')->toDateString());
        $this->assertSame('2024-02-27', PaeSimuladoJanela::vencimento('2023-02-27')->toDateString());
        $this->assertSame('2024-03-01', PaeSimuladoJanela::vencimento('2023-03-01')->toDateString());
        $this->assertSame('2025-02-28', PaeSimuladoJanela::vencimento('2024-02-28')->toDateString());
    }

    public function test_vencimento_e_o_ultimo_dia_em_que_a_janela_contem_a_realizacao(): void
    {
        $dia = CarbonImmutable::parse('2023-01-01');
        $fim = CarbonImmutable::parse('2025-12-31');
        $verificados = 0;

        while ($dia->lessThanOrEqualTo($fim)) {
            $vencimento = PaeSimuladoJanela::vencimento($dia);
            $this->assertTrue(PaeSimuladoJanela::contem($dia, $vencimento), "{$dia->toDateString()} deveria estar na janela de {$vencimento->toDateString()}");
            $this->assertFalse(PaeSimuladoJanela::contem($dia, $vencimento->addDay()), "{$dia->toDateString()} deveria sair da janela em {$vencimento->addDay()->toDateString()}");
            $dia = $dia->addDay();
            $verificados++;
        }

        $this->assertSame(1096, $verificados);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar.** `RUN Simulado`. Expected: `Class "App\Modules\Pae\Support\Simulado\SimuladoAnexoC" not found` (e a da janela).

- [ ] **Step 3: Implementar as duas classes.**

`SDC/app/Modules/Pae/Support/Simulado/SimuladoAnexoC.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Simulado;

/**
 * Item 8.1 do Anexo C da Resolucao GMG 83/2024 (Arts. 98 a 101): unica dona do
 * catalogo dos 10 criterios e da regra de validacao. Os criterios 1 a 8 decidem
 * a validacao; 9 e 10 (Art. 100) sao informativos. Indicio e apoio ao analista,
 * nunca decisao: quem marca "atende" e a CEDEC.
 */
final class SimuladoAnexoC
{
    /** Mesma tolerancia da fase E para comparar tempos em ponto flutuante. */
    public const TOLERANCIA = 1e-6;

    public const CATEGORIAS = ['sem_dificuldade', 'com_dificuldade', 'ensino', 'hospitalares_prisionais', 'aglomeracao'];

    /** Criterio do item 8.1 => categoria da secao 7 que alimenta seus indicios de tempo. */
    private const CATEGORIA_DO_CRITERIO = [
        5 => 'sem_dificuldade',
        6 => 'com_dificuldade',
        7 => 'hospitalares_prisionais',
        8 => 'aglomeracao',
    ];

    private const PONTOS_DE_ENCONTRO = 4;
    private const ALARME = 2;
    private const ULTIMO_REPROVAVEL = 8;

    private const ITENS = [
        1 => ['Avaliação das placas e sinalização de risco', 'Todas as placas estarem instaladas conforme previsto no PAE e nesta Resolução.'],
        2 => ['Efetividade do sistema de alarme', 'Indicação do morador residente na ZAS que informou não ser audível o sistema de alarme (nome, localização).'],
        3 => ['Avaliação das estratégias de comunicação de risco', 'Realização de todas as ações listadas no item comunicação de risco desta Resolução que regulamenta a elaboração do PAE.'],
        4 => ['Avaliação dos pontos de encontro', 'Atendimento aos critérios estabelecidos nesta Resolução.'],
        5 => ['Avaliação do tempo de saída das pessoas sem dificuldade de locomoção das áreas de risco', 'Tempo de saída das pessoas das áreas sujeitas à inundação.'],
        6 => ['Avaliação do tempo gasto para retirada das pessoas com dificuldade de locomoção', 'Tempo estimado para a retirada das pessoas com dificuldade de locomoção das áreas de risco.'],
        7 => ['Avaliação do tempo gasto para a retirada das pessoas das unidades prisionais', 'Tempo estimado para a retirada de todas as pessoas das unidades prisionais.'],
        8 => ['Avaliação do tempo gasto para a evacuação dos locais com grande aglomeração de pessoas', 'Tempo gasto para a evacuação de todas as pessoas dos locais com grande aglomeração e chegada em local seguro.'],
        9 => ['Mensuração do número de pessoas participantes do exercício simulado', 'Percentual de participação de pessoas cadastradas no PAE nos exercícios simulados.'],
        10 => ['Avaliar a mobilização da comunidade na participação de exercícios simulados', 'Percentual de participação de pessoas em relação ao simulado realizado em anos anteriores.'],
    ];

    /** @return list<array{numero: int, indice: string, criterio: string, reprovavel: bool}> */
    public static function catalogo(): array
    {
        $catalogo = [];
        foreach (self::ITENS as $numero => [$indice, $criterio]) {
            $catalogo[] = [
                'numero' => $numero,
                'indice' => $indice,
                'criterio' => $criterio,
                'reprovavel' => $numero <= self::ULTIMO_REPROVAVEL,
            ];
        }

        return $catalogo;
    }

    /** @return list<int> */
    public static function numeros(): array
    {
        return array_keys(self::ITENS);
    }

    /** @return list<int> */
    public static function numerosReprovaveis(): array
    {
        return range(1, self::ULTIMO_REPROVAVEL);
    }

    /** Art. 101: validado somente se os 8 criterios reprovaveis estiverem marcados "atende" (booleano verdadeiro). */
    public static function validado(array $criterios): bool
    {
        foreach (self::numerosReprovaveis() as $numero) {
            if (($criterios[$numero]['atende'] ?? null) !== true) {
                return false;
            }
        }

        return true;
    }

    /** Justificativa e obrigatoria no "nao atende" dos criterios reprovaveis; os informativos aceitam nulo. */
    public static function exigeJustificativa(int $numero, mixed $atende): bool
    {
        if ($numero > self::ULTIMO_REPROVAVEL || $atende === null) {
            return false;
        }

        return filter_var($atende, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
    }

    /** @return list<int> numeros dos criterios que exigem justificativa e nao a tem */
    public static function justificativasFaltantes(array $criterios): array
    {
        $faltantes = [];
        foreach (self::numeros() as $numero) {
            $criterio = $criterios[$numero] ?? [];
            if (self::exigeJustificativa($numero, $criterio['atende'] ?? null) && self::emBranco($criterio['justificativa'] ?? null)) {
                $faltantes[] = $numero;
            }
        }

        return $faltantes;
    }

    /**
     * @return array<int, list<array{codigo: string, categoria: ?string, linha: ?int, nome: ?string}>>
     */
    public static function indicios(array $tempos, array $alarme): array
    {
        $indicios = array_fill_keys(self::numeros(), []);

        foreach (self::CATEGORIA_DO_CRITERIO as $numero => $categoria) {
            foreach ($tempos[$categoria] ?? [] as $linha => $dados) {
                foreach (self::sinais($dados) as $codigo) {
                    $indicio = [
                        'codigo' => $codigo,
                        'categoria' => $categoria,
                        'linha' => (int) $linha,
                        'nome' => $dados['nome'] ?? null,
                    ];
                    $indicios[$numero][] = $indicio;
                    if ($codigo === 'ponto_invalido') {
                        $indicios[self::PONTOS_DE_ENCONTRO][] = $indicio;
                    }
                }
            }
        }

        if (($alarme['audivel_todos'] ?? null) === false
            && (self::emBranco($alarme['morador_nome'] ?? null) || self::emBranco($alarme['morador_localizacao'] ?? null))) {
            $indicios[self::ALARME][] = ['codigo' => 'alarme_sem_morador', 'categoria' => null, 'linha' => null, 'nome' => null];
        }

        return $indicios;
    }

    /** @return list<string> */
    private static function sinais(array $linha): array
    {
        $sinais = [];
        $saida = $linha['saida_segundos'] ?? null;
        $chegada = $linha['chegada_onda_segundos'] ?? null;
        if ($saida !== null && $chegada !== null && $saida >= $chegada - self::TOLERANCIA) {
            $sinais[] = 'saida_maior_igual_onda';
        }
        if (($linha['houve_problemas'] ?? false) === true) {
            $sinais[] = 'houve_problemas';
        }
        if (($linha['ponto_valido'] ?? true) === false) {
            $sinais[] = 'ponto_invalido';
        }

        return $sinais;
    }

    /** `trim()` e a regra `required` nao removem o espaco nao separavel (U+00A0); `\S` com `/u` remove. */
    private static function emBranco(?string $texto): bool
    {
        return $texto === null || preg_match('/\S/u', $texto) !== 1;
    }
}
```

`SDC/app/Modules/Pae/Support/Simulado/PaeSimuladoJanela.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Simulado;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Vigencia do relatorio de simulado: realizacao nos 12 meses anteriores a data
 * de referencia (emissao ou hoje), inclusive nos dois extremos. Datas puras:
 * a hora de qualquer lado e ignorada. 29/02 recua para 28/02 (sem estouro de mes).
 */
final class PaeSimuladoJanela
{
    public const MESES = 12;

    public static function inicio(CarbonImmutable $referencia): CarbonImmutable
    {
        return $referencia->startOfDay()->subMonthsNoOverflow(self::MESES);
    }

    public static function contem(DateTimeInterface|string $realizacao, CarbonImmutable $referencia): bool
    {
        $dia = CarbonImmutable::parse($realizacao)->toDateString();

        return $dia >= self::inicio($referencia)->toDateString() && $dia <= $referencia->toDateString();
    }

    /** Ultimo dia de referencia em que a janela ainda contem a realizacao (realizacao mais 12 meses). */
    public static function vencimento(DateTimeInterface|string $realizacao): CarbonImmutable
    {
        $dia = CarbonImmutable::parse($realizacao)->startOfDay();
        $limite = $dia->addMonthsNoOverflow(self::MESES);
        while (self::inicio($limite->addDay())->toDateString() <= $dia->toDateString()) {
            $limite = $limite->addDay();
        }

        return $limite;
    }
}
```

- [ ] **Step 4: Rodar e ver passar.** `RUN Simulado`. Expected: `OK (15 tests, ...)` (10 do motor e 5 da janela; 2023 a 2025 somam 1096 dias, pois 2024 é bissexto). Se o laço de consistência acusar um dia, o defeito está em `vencimento`, não no teste.

- [ ] **Step 5: Commit.**

```bash
git add SDC/app/Modules/Pae/Support/Simulado
git commit -m "✨ feat(pae): motor do Anexo C e janela de 12 meses do simulado"
```

---

### Task 3: Schema, models e referências no CCPAE

**Files:**
- Create: `SDC/database/migrations/2026_10_09_120000_create_pae_simulado_registros.php`
- Create: `SDC/app/Modules/Pae/Models/PaeSimuladoAvaliacao.php`
- Create: `SDC/app/Modules/Pae/Models/PaeSimuladoRelatorio.php`
- Modify: `SDC/app/Modules/Pae/Models/PaeProtocolo.php` (duas relações, depois de `documentosDco()`)
- Modify: `SDC/app/Modules/Pae/Models/PaeCcpae.php` (`fillable` e duas relações)
- Test: `SDC/tests/Feature/Pae/PaeSimuladoSchemaTest.php`

**Interfaces:**
- Consumes: nada da Task 2.
- Produces: `PaeProtocolo::avaliacoesSimulado(): HasMany` (mais recente primeiro, `orderByDesc('id')`), `PaeProtocolo::relatoriosSimulado(): HasMany` (sem ordem embutida: `max('versao')` no PostgreSQL falha com `ORDER BY` de coluna não agregada); `PaeSimuladoAvaliacao` (`RESULTADOS`, `MOTIVOS_DISPENSA`, `protocolo()`, `decisor()`); `PaeSimuladoRelatorio` (casts de `criterios`, `tempos`, `alarme`, `informativos`, `indicios` como `array`, `protocolo()`, `registrador()`); `PaeCcpae::avaliacaoSimulado()`, `relatorioSimulado()`; colunas `simulado_avaliacao_id` e `simulado_relatorio_id` em `pae_ccpae` (nullable, `restrictOnDelete`).

- [ ] **Step 1: Teste que falha.** `SDC/tests/Feature/Pae/PaeSimuladoSchemaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoAvaliacao;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PaeSimuladoSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_historico_preserva_avaliacoes_e_versoes_por_realizacao_e_ccpae_legado_aceita_referencias_nulas(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();

        PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'exigivel', null));
        PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'dispensado', 'licenca_instalacao'));
        foreach ([['2026-05-10', 1], ['2026-05-10', 2], ['2026-08-01', 1]] as [$data, $versao]) {
            PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user, ['dt_realizacao' => $data, 'versao' => $versao]));
        }
        $legado = PaeCcpae::create([
            'protocolo_id' => $protocolo->id,
            'codigo' => 'CCPAE-SIM-LEGADO-'.$protocolo->id,
            'dt_emissao' => '2025-01-01',
            'status' => PaeCcpae::STATUS_ATIVO,
        ]);

        $this->assertSame(['dispensado', 'exigivel'], $protocolo->avaliacoesSimulado()->pluck('resultado')->all());
        $this->assertSame([1, 2, 1], $protocolo->relatoriosSimulado()->orderBy('id')->pluck('versao')->all());
        $this->assertSame(2, (int) $protocolo->relatoriosSimulado()->where('dt_realizacao', '2026-05-10')->max('versao'));
        $primeiro = $protocolo->relatoriosSimulado()->orderBy('id')->first();
        $this->assertSame('2026-05-10', $primeiro->dt_realizacao->toDateString());
        $this->assertTrue($primeiro->validado);
        $this->assertTrue($primeiro->criterios[1]['atende']);
        $this->assertNull($legado->fresh()->simulado_avaliacao_id);
        $this->assertNull($legado->fresh()->simulado_relatorio_id);
    }

    public function test_restricoes_do_banco_recusam_dados_invalidos(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $chave = (string) Str::uuid();

        $this->violaRestricao(fn () => PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'talvez', null)));
        $this->violaRestricao(fn () => PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'dispensado', null)));
        $this->violaRestricao(fn () => PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'dispensado', 'outro')));
        $this->violaRestricao(fn () => PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'exigivel', 'licenca_instalacao')));
        $this->violaRestricao(fn () => PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user, ['nivel_emergencia' => 4])));

        PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user, ['chave_idempotencia' => $chave]));
        $this->violaRestricao(fn () => PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user)));
        $this->violaRestricao(fn () => PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user, ['versao' => 2, 'chave_idempotencia' => $chave])));
        $this->assertSame(1, $protocolo->relatoriosSimulado()->count());
    }

    public function test_ccpae_congela_referencias_que_nao_podem_ser_apagadas(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $avaliacao = PaeSimuladoAvaliacao::create($this->avaliacao($protocolo, $user, 'exigivel', null));
        $relatorio = PaeSimuladoRelatorio::create($this->relatorio($protocolo, $user));
        $ccpae = PaeCcpae::create([
            'protocolo_id' => $protocolo->id,
            'codigo' => 'CCPAE-SIM-'.$protocolo->id,
            'dt_emissao' => '2026-06-30',
            'status' => PaeCcpae::STATUS_ATIVO,
            'simulado_avaliacao_id' => $avaliacao->id,
            'simulado_relatorio_id' => $relatorio->id,
        ]);

        $this->assertSame($avaliacao->id, $ccpae->avaliacaoSimulado->id);
        $this->assertSame($relatorio->id, $ccpae->relatorioSimulado->id);
        $this->violaRestricao(fn () => DB::table('pae_simulado_avaliacoes')->where('id', $avaliacao->id)->delete());
        $this->violaRestricao(fn () => DB::table('pae_simulado_relatorios')->where('id', $relatorio->id)->delete());
    }

    /** Savepoint por tentativa: o PostgreSQL aborta a transacao inteira depois de um erro. */
    private function violaRestricao(Closure $acao): void
    {
        try {
            DB::transaction($acao);
            $this->fail('O banco deveria recusar a linha.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    private function avaliacao(PaeProtocolo $protocolo, User $user, string $resultado, ?string $motivo): array
    {
        return [
            'protocolo_id' => $protocolo->id,
            'resultado' => $resultado,
            'motivo_dispensa' => $motivo,
            'fundamentacao' => 'Enquadramento conferido no processo.',
            'num_sei' => '12345',
            'chave_idempotencia' => (string) Str::uuid(),
            'decidido_por' => $user->id,
            'decidido_em' => now(),
        ];
    }

    private function relatorio(PaeProtocolo $protocolo, User $user, array $alteracoes = []): array
    {
        return array_replace([
            'protocolo_id' => $protocolo->id,
            'dt_realizacao' => '2026-05-10',
            'versao' => 1,
            'nivel_emergencia' => 2,
            'dt_apresentacao' => '2026-05-20',
            'num_sei' => '12345',
            'observacao' => null,
            'arquivo_path' => "simulados/{$protocolo->id}/2026-05-10/relatorio.pdf",
            'arquivo_nome_original' => 'relatorio.pdf',
            'arquivo_mime' => 'application/pdf',
            'arquivo_tamanho_bytes' => 100,
            'integrado' => false,
            'barragens_integradas' => null,
            'aviso_cedec_em' => null,
            'criterios' => [1 => ['atende' => true, 'justificativa' => null]],
            'tempos' => [],
            'alarme' => ['audivel_todos' => true],
            'informativos' => [],
            'validado' => true,
            'indicios' => [],
            'chave_idempotencia' => (string) Str::uuid(),
            'registrado_por' => $user->id,
            'registrado_em' => now(),
        ], $alteracoes);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeSimuladoSchemaTest`. Expected: `Call to undefined method ...avaliacoesSimulado()` ou classe inexistente.

- [ ] **Step 3: Migration, models e relações.**

`SDC/database/migrations/2026_10_09_120000_create_pae_simulado_registros.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_simulado_avaliacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('resultado', 20);
            $table->string('motivo_dispensa', 30)->nullable();
            $table->text('fundamentacao');
            $table->string('num_sei', 100);
            $table->uuid('chave_idempotencia');
            $table->foreignId('decidido_por')->constrained('users');
            $table->timestampTz('decidido_em');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_simulado_avaliacao_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_simulado_avaliacao_protocolo_idx');
        });
        DB::statement("ALTER TABLE pae_simulado_avaliacoes ADD CONSTRAINT pae_simulado_avaliacao_resultado_check CHECK ((resultado = 'exigivel' AND motivo_dispensa IS NULL) OR (resultado = 'dispensado' AND motivo_dispensa IN ('licenca_instalacao', 'metodo_alternativo')))");

        Schema::create('pae_simulado_relatorios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->date('dt_realizacao');
            $table->unsignedInteger('versao');
            $table->unsignedSmallInteger('nivel_emergencia');
            $table->date('dt_apresentacao');
            $table->string('num_sei', 100);
            $table->text('observacao')->nullable();
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedBigInteger('arquivo_tamanho_bytes');
            $table->boolean('integrado')->default(false);
            $table->text('barragens_integradas')->nullable();
            $table->date('aviso_cedec_em')->nullable();
            $table->jsonb('criterios');
            $table->jsonb('tempos');
            $table->jsonb('alarme');
            $table->jsonb('informativos');
            $table->boolean('validado');
            $table->jsonb('indicios');
            $table->uuid('chave_idempotencia');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestampTz('registrado_em');
            $table->unique(['protocolo_id', 'dt_realizacao', 'versao'], 'pae_simulado_relatorio_versao_unica');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_simulado_relatorio_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_simulado_relatorio_protocolo_idx');
        });
        DB::statement('ALTER TABLE pae_simulado_relatorios ADD CONSTRAINT pae_simulado_relatorio_nivel_check CHECK (nivel_emergencia IN (2, 3))');

        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->foreignId('simulado_avaliacao_id')->nullable()->constrained('pae_simulado_avaliacoes')->restrictOnDelete();
            $table->foreignId('simulado_relatorio_id')->nullable()->constrained('pae_simulado_relatorios')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('simulado_relatorio_id');
            $table->dropConstrainedForeignId('simulado_avaliacao_id');
        });
        Schema::dropIfExists('pae_simulado_relatorios');
        Schema::dropIfExists('pae_simulado_avaliacoes');
    }
};
```

`SDC/app/Modules/Pae/Models/PaeSimuladoAvaliacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Exigibilidade do simulado no protocolo (imutavel; a mais recente vale). */
final class PaeSimuladoAvaliacao extends Model
{
    public const RESULTADOS = ['exigivel', 'dispensado'];

    /** Art. 17 (PAE para Licenca de Instalacao) e Art. 21 (metodo alternativo aprovado pela CEDEC). */
    public const MOTIVOS_DISPENSA = ['licenca_instalacao', 'metodo_alternativo'];

    public $timestamps = false;

    protected $table = 'pae_simulado_avaliacoes';

    protected $fillable = [
        'protocolo_id', 'resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei',
        'chave_idempotencia', 'decidido_por', 'decidido_em',
    ];

    protected $casts = [
        'decidido_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function decisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}
```

`SDC/app/Modules/Pae/Models/PaeSimuladoRelatorio.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Relatorio anual do Anexo C (imutavel; revisoes por dt_realizacao e versao, a maior versao vale). */
final class PaeSimuladoRelatorio extends Model
{
    public $timestamps = false;

    protected $table = 'pae_simulado_relatorios';

    protected $fillable = [
        'protocolo_id', 'dt_realizacao', 'versao', 'nivel_emergencia', 'dt_apresentacao', 'num_sei', 'observacao',
        'arquivo_path', 'arquivo_nome_original', 'arquivo_mime', 'arquivo_tamanho_bytes',
        'integrado', 'barragens_integradas', 'aviso_cedec_em',
        'criterios', 'tempos', 'alarme', 'informativos', 'validado', 'indicios',
        'chave_idempotencia', 'registrado_por', 'registrado_em',
    ];

    protected $casts = [
        'dt_realizacao' => 'date',
        'dt_apresentacao' => 'date',
        'aviso_cedec_em' => 'date',
        'versao' => 'integer',
        'nivel_emergencia' => 'integer',
        'arquivo_tamanho_bytes' => 'integer',
        'integrado' => 'boolean',
        'validado' => 'boolean',
        'criterios' => 'array',
        'tempos' => 'array',
        'alarme' => 'array',
        'informativos' => 'array',
        'indicios' => 'array',
        'registrado_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
```

Em `SDC/app/Modules/Pae/Models/PaeProtocolo.php`, depois de `documentosDco()`:

```php
    public function avaliacoesSimulado(): HasMany
    {
        return $this->hasMany(PaeSimuladoAvaliacao::class, 'protocolo_id')->orderByDesc('id');
    }

    public function relatoriosSimulado(): HasMany
    {
        return $this->hasMany(PaeSimuladoRelatorio::class, 'protocolo_id');
    }
```

Em `SDC/app/Modules/Pae/Models/PaeCcpae.php`: acrescentar `'simulado_avaliacao_id', 'simulado_relatorio_id',` ao fim de `$fillable` e, depois de `documentoDco()`:

```php
    public function avaliacaoSimulado(): BelongsTo
    {
        return $this->belongsTo(PaeSimuladoAvaliacao::class, 'simulado_avaliacao_id');
    }

    public function relatorioSimulado(): BelongsTo
    {
        return $this->belongsTo(PaeSimuladoRelatorio::class, 'simulado_relatorio_id');
    }
```

- [ ] **Step 4: Rodar e ver passar.** `RUN PaeSimuladoSchemaTest`. Expected: `OK (3 tests, ...)`. O runner aplica a migration em `pae_d_test` antes dos testes. Se o teste de restrições acusar `current transaction is aborted`, o `violaRestricao` não está abrindo savepoint: confirmar que o teste usa `DatabaseTransactions`.

- [ ] **Step 5: Commit.**

```bash
git add SDC/database/migrations/2026_10_09_120000_create_pae_simulado_registros.php SDC/app/Modules/Pae/Models
git commit -m "🗃️ db(pae): tabelas de simulado e referências no CCPAE"
```

---

### Task 4: Regras de entrada e `PaeSimuladoService`

**Files:**
- Create: `SDC/app/Modules/Pae/Requests/AvaliarSimuladoRequest.php`, `RegistrarSimuladoRequest.php`, `PreviaIndiciosSimuladoRequest.php`
- Create: `SDC/app/Modules/Pae/Services/PaeSimuladoService.php`
- Modify: `SDC/app/Modules/Pae/PaeServiceProvider.php` (singleton junto de `PaeEvacuacaoService`)
- Test: `SDC/tests/Feature/Pae/PaeSimuladoServiceTest.php`

**Interfaces:**
- Consumes: Task 1 (`PaeIdempotencia`, `PaeArquivoPdf`, `PaeListagem`, `TempoAnexoE::REGRA_MM_SS`, `TempoAnexoE::paraSegundos/formatarResultado`), Task 2 (`SimuladoAnexoC`, `PaeSimuladoJanela`), Task 3 (models e relações).
- Produces:
  - `AvaliarSimuladoRequest::regras()`, `RegistrarSimuladoRequest::regras(array $dados = [])`, `RegistrarSimuladoRequest::mensagens()`, `RegistrarSimuladoRequest::regrasTempos()` e `regrasAlarme()` (reaproveitadas por `PreviaIndiciosSimuladoRequest::regras()`). As três autorizam `pae.protocolos.validar`.
  - `PaeSimuladoService::avaliar(PaeProtocolo, array $dados, User): PaeSimuladoAvaliacao`.
  - `PaeSimuladoService::registrarRelatorio(PaeProtocolo, array $dados, UploadedFile $arquivo, User): PaeSimuladoRelatorio`.
  - `PaeSimuladoService::previaIndicios(array $dados): array` (valida só `tempos` e `alarme`, não grava).
  - `PaeSimuladoService::resumo(PaeProtocolo, CarbonImmutable $hoje): array` com `situacao`, `janela_inicio`, `proximo_vencimento` (`?string`), `alerta_legado`, `avaliacao`, `avaliacoes`, `relatorios` (lista apresentada), `catalogo`, `ccpae`.
  - `PaeSimuladoService::evidenciaParaEmissao(PaeProtocolo $protocoloBloqueado, CarbonImmutable $dataEmissao): array{avaliacao: PaeSimuladoAvaliacao, relatorio: ?PaeSimuladoRelatorio}` (erro de validação na chave `simulado`).
  - `PaeSimuladoService::anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator` (acrescenta `simulado_situacao` = `nao_avaliada|dispensado|em_dia|nao_validado|vencido|pendente_emissao`).
- Payload do relatório (HTTP e serviço): `dt_realizacao` e `dt_apresentacao` (`Y-m-d`), `nivel_emergencia` (2 ou 3), `num_sei`, `observacao`, `integrado`, `barragens_integradas`, `aviso_cedec_em`, `criterios` (`{1..10: {atende, justificativa}}`), `tempos` (`{categoria: [{nome, populacao, chegada_onda, saida, houve_problemas, ponto_valido, estimativa, nivel_emergencia?}]}` com `chegada_onda` e `saida` em `mm:ss`), `alarme`, `informativos`, `chave_idempotencia`, `arquivo` (PDF até 20 MiB). `validado` e `indicios` nunca vêm do cliente.
- Leituras fixadas: relatório mais recente por `dt_realizacao` é a maior `versao` (revisões anteriores não contam); `em_dia` = algum simulado da janela cuja última versão está validada; `nao_validado` = há simulado na janela e nenhum validado; sem aviso informado não há alerta do Art. 94; alerta de aviso quando a antecedência (`dt_realizacao` menos `aviso_cedec_em`) é menor que 7 dias (exatamente 7 não alerta); `evidenciaParaEmissao` filtra por apresentação até a data de emissão **antes** de escolher a última versão (mesma semântica retroativa da DCO).

- [ ] **Step 1: Testes que falham.** `SDC/tests/Feature/Pae/PaeSimuladoServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Pae\Requests\AvaliarSimuladoRequest;
use App\Modules\Pae\Requests\PreviaIndiciosSimuladoRequest;
use App\Modules\Pae\Requests\RegistrarSimuladoRequest;
use App\Modules\Pae\Services\PaeSimuladoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class PaeSimuladoServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_avaliacao_valida_motivo_e_e_idempotente(): void
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeSimuladoService::class);
        $dados = self::avaliacao();

        $primeira = $service->avaliar($protocolo, $dados, $user);
        $repetida = $service->avaliar($protocolo, $dados, $user);
        $this->assertSame($primeira->id, $repetida->id);
        $this->assertNull($primeira->motivo_dispensa);

        try {
            $service->avaliar($protocolo, [...$dados, 'fundamentacao' => 'Outra fundamentação.'], $user);
            $this->fail('A chave reutilizada com outros dados deveria falhar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('chave_idempotencia', $e->errors());
        }

        $this->assertErroEm('motivo_dispensa', fn () => $service->avaliar($protocolo, self::avaliacao('dispensado'), $user));
        $this->assertErroEm('motivo_dispensa', fn () => $service->avaliar($protocolo, self::avaliacao('dispensado', 'outro'), $user));
        $this->assertErroEm('motivo_dispensa', fn () => $service->avaliar($protocolo, self::avaliacao('exigivel', 'licenca_instalacao'), $user));
        $this->assertErroEm('num_sei', fn () => $service->avaliar($protocolo, [...self::avaliacao(), 'num_sei' => ' '], $user));

        $nova = $service->avaliar($protocolo, self::avaliacao('dispensado', 'metodo_alternativo'), $user);
        $this->assertSame(2, $protocolo->avaliacoesSimulado()->count());
        $this->assertSame($nova->id, $protocolo->avaliacoesSimulado()->first()->id);
        $this->assertSame('exigivel', $primeira->fresh()->resultado);
        $this->assertSame(2, PaeTimeline::query()->where('protocolo_id', $protocolo->id)->where('evento', 'simulado_avaliacao')->count());
    }

    public function test_relatorio_calcula_validado_e_indicios_no_servidor(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();

        $relatorio = $service->registrarRelatorio(
            $protocolo,
            self::registro(['validado' => false, 'indicios' => ['x' => 1]]),
            self::pdf(),
            $user,
        )->fresh();

        $this->assertSame(1, $relatorio->versao);
        $this->assertTrue($relatorio->validado);
        $this->assertSame(range(1, 10), array_keys($relatorio->indicios));
        $this->assertSame([], $relatorio->indicios[5]);
        $this->assertSame(900, $relatorio->tempos['sem_dificuldade'][0]['chegada_onda_segundos']);
        $this->assertSame(750, $relatorio->tempos['sem_dificuldade'][0]['saida_segundos']);
        $this->assertSame('2026-05-10', $relatorio->dt_realizacao->toDateString());
        $this->assertSame('relatorio.pdf', $relatorio->arquivo_nome_original);
        $this->assertStringStartsWith("simulados/{$protocolo->id}/2026-05-10/", $relatorio->arquivo_path);
        Storage::disk('pae')->assertExists($relatorio->arquivo_path);
        $this->assertSame(1, PaeTimeline::query()->where('protocolo_id', $protocolo->id)->where('evento', 'simulado_relatorio')->count());
    }

    public function test_relatorio_exige_avaliacao_exigivel_vigente(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeSimuladoService::class);

        $this->assertErroEm('avaliacao', fn () => $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user));
        $service->avaliar($protocolo, self::avaliacao('dispensado', 'licenca_instalacao'), $user);
        $this->assertErroEm('avaliacao', fn () => $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user));

        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
        $this->assertSame([], Storage::disk('pae')->allFiles());

        $service->avaliar($protocolo, self::avaliacao(), $user);
        $this->assertSame(1, $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user)->versao);
    }

    public function test_revisoes_incrementam_a_versao_por_realizacao_e_preservam_as_anteriores(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();

        $a = $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user);
        $b = $service->registrarRelatorio($protocolo, self::registro(['criterios' => self::criterios([5])]), self::pdf(), $user);
        $c = $service->registrarRelatorio($protocolo, self::registro(['dt_realizacao' => '2026-08-01', 'dt_apresentacao' => '2026-08-05']), self::pdf(), $user);

        $this->assertSame([1, 2, 1], [$a->versao, $b->versao, $c->versao]);
        $this->assertTrue($a->fresh()->validado);
        $this->assertFalse($b->validado);
        $this->assertSame(3, $protocolo->relatoriosSimulado()->count());
        $this->assertCount(3, Storage::disk('pae')->allFiles());
    }

    public function test_informativos_que_nao_atendem_nao_invalidam_nem_exigem_justificativa(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        $criterios = array_replace(self::criterios(), [
            9 => ['atende' => false, 'justificativa' => null],
            10 => ['atende' => false, 'justificativa' => null],
        ]);

        $relatorio = $service->registrarRelatorio($protocolo, self::registro(['criterios' => $criterios]), self::pdf(), $user)->fresh();

        $this->assertTrue($relatorio->validado);
        $this->assertFalse($relatorio->criterios[9]['atende']);
        $this->assertNull($relatorio->criterios[9]['justificativa']);
    }

    public function test_justificativa_so_com_espaco_nao_separavel_e_recusada(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();

        foreach (["\u{00A0}", '   ', null] as $justificativa) {
            $criterios = array_replace(self::criterios(), [2 => ['atende' => false, 'justificativa' => $justificativa]]);
            $this->assertErroEm('criterios.2.justificativa', fn () => $service->registrarRelatorio(
                $protocolo, self::registro(['criterios' => $criterios]), self::pdf(), $user,
            ));
        }

        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
        $this->assertSame([], Storage::disk('pae')->allFiles());
    }

    public function test_booleanos_multipart_como_texto_sao_interpretados_e_false_literal_e_recusado(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        $criterios = array_replace(self::criterios(), [
            5 => ['atende' => '0', 'justificativa' => 'Saída acima da onda.'],
            9 => ['atende' => '1', 'justificativa' => null],
        ]);
        $dados = self::registro(['criterios' => $criterios, 'integrado' => '0']);
        $dados['tempos']['sem_dificuldade'][0]['houve_problemas'] = '1';
        $dados['tempos']['sem_dificuldade'][0]['ponto_valido'] = '1';

        $relatorio = $service->registrarRelatorio($protocolo, $dados, self::pdf(), $user)->fresh();

        $this->assertFalse($relatorio->criterios[5]['atende']);
        $this->assertTrue($relatorio->criterios[9]['atende']);
        $this->assertFalse($relatorio->validado);
        $this->assertFalse($relatorio->integrado);
        $this->assertTrue($relatorio->tempos['sem_dificuldade'][0]['houve_problemas']);
        $this->assertSame('houve_problemas', $relatorio->indicios[5][0]['codigo']);

        $literal = array_replace(self::criterios(), [5 => ['atende' => 'false', 'justificativa' => 'Texto.']]);
        $this->assertErroEm('criterios.5.atende', fn () => $service->registrarRelatorio(
            $protocolo, self::registro(['criterios' => $literal, 'dt_realizacao' => '2026-06-01', 'dt_apresentacao' => '2026-06-02']), self::pdf(), $user,
        ));
        $this->assertSame(1, $protocolo->relatoriosSimulado()->count());
    }

    public function test_tempos_mm_ss_malformados_sao_recusados_no_campo_e_os_extremos_aceitos(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();

        foreach (['12:60', '1:5', ' 12:30', '1000:00', '12:30:00', 'abc', ''] as $invalido) {
            $dados = self::registro(['tempos' => ['sem_dificuldade' => [array_replace(self::linha(), ['saida' => $invalido])]]]);
            $this->assertErroEm('tempos.sem_dificuldade.0.saida', fn () => $service->registrarRelatorio($protocolo, $dados, self::pdf(), $user));
        }
        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());

        $extremos = self::registro(['tempos' => ['sem_dificuldade' => [
            array_replace(self::linha(), ['nome' => 'Zero', 'chegada_onda' => '00:00', 'saida' => '00:00']),
            array_replace(self::linha(), ['nome' => 'Longa', 'chegada_onda' => '15:00', 'saida' => '999:59']),
        ]]]);
        $relatorio = $service->registrarRelatorio($protocolo, $extremos, self::pdf(), $user)->fresh();

        $this->assertSame([0, 59999], array_column($relatorio->tempos['sem_dificuldade'], 'saida_segundos'));
        $this->assertSame([0, 1], array_column($relatorio->indicios[5], 'linha'));
        $this->assertSame(['saida_maior_igual_onda', 'saida_maior_igual_onda'], array_column($relatorio->indicios[5], 'codigo'));
    }

    public function test_idempotencia_do_relatorio_devolve_o_mesmo_registro_e_recusa_dados_diferentes(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        $dados = self::registro();

        $primeiro = $service->registrarRelatorio($protocolo, $dados, self::pdf(), $user);
        $repetido = $service->registrarRelatorio($protocolo, $dados, self::pdf(), $user);

        $this->assertSame($primeiro->id, $repetido->id);
        $this->assertSame(1, $protocolo->relatoriosSimulado()->count());
        $this->assertCount(1, Storage::disk('pae')->allFiles());

        $outroNivel = [...$dados, 'nivel_emergencia' => 3];
        $outroTempo = $dados;
        $outroTempo['tempos']['sem_dificuldade'][0]['saida'] = '13:00';
        foreach ([$outroNivel, $outroTempo] as $diferente) {
            $this->assertErroEm('chave_idempotencia', fn () => $service->registrarRelatorio($protocolo, $diferente, self::pdf(), $user));
        }

        $service->avaliar($protocolo, self::avaliacao('dispensado', 'licenca_instalacao'), $user);
        $this->assertSame($primeiro->id, $service->registrarRelatorio($protocolo, $dados, self::pdf(), $user)->id);
        $this->assertSame(1, $protocolo->relatoriosSimulado()->count());
    }

    public function test_protocolo_arquivado_e_somente_leitura(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        $protocolo->update(['arquivado' => true]);

        $this->assertErroEm('protocolo', fn () => $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user));
        $this->assertErroEm('protocolo', fn () => $service->avaliar($protocolo, self::avaliacao('dispensado', 'licenca_instalacao'), $user));

        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
        $this->assertSame(1, $protocolo->avaliacoesSimulado()->count());
        $this->assertSame([], Storage::disk('pae')->allFiles());
    }

    public function test_falha_no_historico_desfaz_a_versao_e_remove_o_pdf(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        PaeTimeline::creating(fn (): never => throw new RuntimeException('falha simulada'));

        try {
            $service->registrarRelatorio($protocolo, self::registro(), self::pdf(), $user);
            $this->fail('A falha deveria propagar.');
        } catch (RuntimeException) {
            $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
            $this->assertSame([], Storage::disk('pae')->allFiles());
        } finally {
            PaeTimeline::flushEventListeners();
        }
    }

    public function test_resumo_calcula_a_situacao_anual_e_o_vencimento(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $service = app(PaeSimuladoService::class);
        $hoje = CarbonImmutable::parse('2026-10-09');

        $legado = PaeProtocolo::factory()->create();
        $this->ccpae($legado);
        $resumo = $service->resumo($legado, $hoje);
        $this->assertSame('nao_avaliada', $resumo['situacao']);
        $this->assertTrue($resumo['alerta_legado']);
        $this->assertNull($resumo['proximo_vencimento']);
        $this->assertNull($resumo['ccpae']->simulado_avaliacao_id);

        $dispensado = PaeProtocolo::factory()->create();
        $service->avaliar($dispensado, self::avaliacao('dispensado', 'licenca_instalacao'), $user);
        $this->assertSame('dispensado', $service->resumo($dispensado, $hoje)['situacao']);

        [, $semRelatorio] = $this->protocoloExigivel();
        [, $comCertificado] = $this->protocoloExigivel();
        $this->ccpae($comCertificado);
        $this->assertSame('pendente_emissao', $service->resumo($semRelatorio, $hoje)['situacao']);
        $this->assertSame('vencido', $service->resumo($comCertificado, $hoje)['situacao']);

        [, $emDia] = $this->protocoloExigivel();
        $service->registrarRelatorio($emDia, self::registro(), self::pdf(), $user);
        $resumo = $service->resumo($emDia, $hoje);
        $this->assertSame('em_dia', $resumo['situacao']);
        $this->assertSame('2027-05-10', $resumo['proximo_vencimento']);
        $this->assertSame('2025-10-09', $resumo['janela_inicio']);
        $this->assertTrue($resumo['relatorios'][0]['vigente']);
        $this->assertCount(10, $resumo['catalogo']);

        [, $naoValidado] = $this->protocoloExigivel();
        $service->registrarRelatorio($naoValidado, self::registro(['criterios' => self::criterios([4])]), self::pdf(), $user);
        $this->assertSame('nao_validado', $service->resumo($naoValidado, $hoje)['situacao']);

        [, $foraPorUmDia] = $this->protocoloExigivel();
        $service->registrarRelatorio($foraPorUmDia, self::registro(['dt_realizacao' => '2025-10-08', 'dt_apresentacao' => '2025-10-10', 'aviso_cedec_em' => null]), self::pdf(), $user);
        $this->assertSame('pendente_emissao', $service->resumo($foraPorUmDia, $hoje)['situacao']);

        [, $limite] = $this->protocoloExigivel();
        $service->registrarRelatorio($limite, self::registro(['dt_realizacao' => '2025-10-09', 'dt_apresentacao' => '2025-10-09', 'aviso_cedec_em' => null]), self::pdf(), $user);
        $resumo = $service->resumo($limite, $hoje);
        $this->assertSame('em_dia', $resumo['situacao']);
        $this->assertSame('2026-10-09', $resumo['proximo_vencimento']);

        [, $revisado] = $this->protocoloExigivel();
        $service->registrarRelatorio($revisado, self::registro(), self::pdf(), $user);
        $service->registrarRelatorio($revisado, self::registro(['criterios' => self::criterios([1])]), self::pdf(), $user);
        $resumo = $service->resumo($revisado, $hoje);
        $this->assertSame('nao_validado', $resumo['situacao']);
        $this->assertSame([true, false], array_column($resumo['relatorios'], 'vigente'));
        $this->assertSame([2, 1], array_column($resumo['relatorios'], 'versao'));

        [, $doisSimulados] = $this->protocoloExigivel();
        $service->registrarRelatorio($doisSimulados, self::registro(), self::pdf(), $user);
        $service->registrarRelatorio($doisSimulados, self::registro(['dt_realizacao' => '2026-08-01', 'dt_apresentacao' => '2026-08-05', 'criterios' => self::criterios([2])]), self::pdf(), $user);
        $this->assertSame('em_dia', $service->resumo($doisSimulados, $hoje)['situacao']);

        [, $apresentadoDepois] = $this->protocoloExigivel();
        $service->registrarRelatorio($apresentadoDepois, self::registro(), self::pdf(), $user);
        $this->assertSame('pendente_emissao', $service->resumo($apresentadoDepois, CarbonImmutable::parse('2026-05-15'))['situacao']);
    }

    public function test_aviso_a_cedec_com_menos_de_sete_dias_gera_alerta_informativo(): void
    {
        Storage::fake('pae');
        [$service, $protocolo, $user] = $this->protocoloExigivel();
        $casos = [
            ['2026-05-10', '2026-05-20', '2026-04-30'],
            ['2026-06-10', '2026-06-20', '2026-06-05'],
            ['2026-07-10', '2026-07-20', '2026-07-03'],
            ['2026-08-10', '2026-08-20', null],
        ];
        foreach ($casos as [$realizacao, $apresentacao, $aviso]) {
            $service->registrarRelatorio($protocolo, self::registro([
                'dt_realizacao' => $realizacao, 'dt_apresentacao' => $apresentacao, 'aviso_cedec_em' => $aviso,
            ]), self::pdf(), $user);
        }

        $relatorios = $service->resumo($protocolo, CarbonImmutable::parse('2026-10-09'))['relatorios'];

        $this->assertSame(['2026-08-10', '2026-07-10', '2026-06-10', '2026-05-10'], array_column($relatorios, 'dt_realizacao'));
        $this->assertSame([null, 7, 5, 10], array_column($relatorios, 'aviso_antecedencia_dias'));
        $this->assertSame([false, false, true, false], array_column($relatorios, 'alerta_aviso'));

        $this->assertErroEm('aviso_cedec_em', fn () => $service->registrarRelatorio($protocolo, self::registro([
            'dt_realizacao' => '2026-09-01', 'dt_apresentacao' => '2026-09-05', 'aviso_cedec_em' => '2026-09-02',
        ]), self::pdf(), $user));
    }

    public function test_evidencia_para_emissao_aplica_janela_apresentacao_e_ultima_versao(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $service = app(PaeSimuladoService::class);
        $emissao = fn (string $data): CarbonImmutable => CarbonImmutable::parse($data);

        $semAvaliacao = PaeProtocolo::factory()->create();
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($semAvaliacao, $emissao('2026-06-30')));

        $dispensado = PaeProtocolo::factory()->create();
        $avaliacao = $service->avaliar($dispensado, self::avaliacao('dispensado', 'licenca_instalacao'), $user);
        $evidencia = $service->evidenciaParaEmissao($dispensado, $emissao('2026-06-30'));
        $this->assertSame($avaliacao->id, $evidencia['avaliacao']->id);
        $this->assertNull($evidencia['relatorio']);

        [, $semRelatorio] = $this->protocoloExigivel();
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($semRelatorio, $emissao('2026-06-30')));

        [, $janela] = $this->protocoloExigivel();
        $relatorio = $service->registrarRelatorio($janela, self::registro(['dt_realizacao' => '2025-06-15', 'dt_apresentacao' => '2025-06-20', 'aviso_cedec_em' => null]), self::pdf(), $user);
        $this->assertSame($relatorio->id, $service->evidenciaParaEmissao($janela, $emissao('2026-06-15'))['relatorio']->id);
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($janela, $emissao('2026-06-16')));
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($janela, $emissao('2025-06-19')));
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($janela, $emissao('2025-06-14')));

        [, $versoes] = $this->protocoloExigivel();
        $v1 = $service->registrarRelatorio($versoes, self::registro(), self::pdf(), $user);
        $service->registrarRelatorio($versoes, self::registro(['criterios' => self::criterios([3]), 'dt_apresentacao' => '2026-06-01']), self::pdf(), $user);
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($versoes, $emissao('2026-06-30')));
        $this->assertSame($v1->id, $service->evidenciaParaEmissao($versoes, $emissao('2026-05-25'))['relatorio']->id);
        $v3 = $service->registrarRelatorio($versoes, self::registro(['dt_apresentacao' => '2026-07-01']), self::pdf(), $user);
        $this->assertSame($v3->id, $service->evidenciaParaEmissao($versoes, $emissao('2026-07-02'))['relatorio']->id);

        [, $doisSimulados] = $this->protocoloExigivel();
        $a = $service->registrarRelatorio($doisSimulados, self::registro(['dt_realizacao' => '2026-01-10', 'dt_apresentacao' => '2026-01-15', 'aviso_cedec_em' => null]), self::pdf(), $user);
        $service->registrarRelatorio($doisSimulados, self::registro(['criterios' => self::criterios([5])]), self::pdf(), $user);
        $this->assertSame($a->id, $service->evidenciaParaEmissao($doisSimulados, $emissao('2026-06-30'))['relatorio']->id);

        [, $semArquivo] = $this->protocoloExigivel();
        $perdido = $service->registrarRelatorio($semArquivo, self::registro(), self::pdf(), $user);
        Storage::disk('pae')->delete($perdido->arquivo_path);
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($semArquivo, $emissao('2026-06-30')));

        [, $posterior] = $this->protocoloExigivel();
        $service->registrarRelatorio($posterior, self::registro(), self::pdf(), $user);
        $this->assertErroEm('simulado', fn () => $service->evidenciaParaEmissao($posterior, $emissao('2026-05-09')));
    }

    public function test_listagem_anota_situacao_com_consultas_constantes(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $service = app(PaeSimuladoService::class);
        $hoje = CarbonImmutable::parse('2026-10-09');

        $nao = PaeProtocolo::factory()->create();
        $dispensado = PaeProtocolo::factory()->create();
        $service->avaliar($dispensado, self::avaliacao('dispensado', 'metodo_alternativo'), $user);
        [, $emDia] = $this->protocoloExigivel();
        $service->registrarRelatorio($emDia, self::registro(), self::pdf(), $user);
        [, $pendente] = $this->protocoloExigivel();

        $contar = function (array $ids) use ($service, $hoje): array {
            $pagina = PaeProtocolo::query()->whereKey($ids)->paginate(50);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $linhas = $service->anotarListagem($pagina, $hoje)->getCollection()->keyBy('id');
            DB::disableQueryLog();

            return [count(DB::getQueryLog()), $linhas];
        };

        [$umaConsulta] = $contar([$nao->id]);
        [$muitas, $linhas] = $contar([$nao->id, $dispensado->id, $emDia->id, $pendente->id]);

        $this->assertSame($umaConsulta, $muitas);
        $this->assertSame('nao_avaliada', $linhas[$nao->id]['simulado_situacao']);
        $this->assertSame('dispensado', $linhas[$dispensado->id]['simulado_situacao']);
        $this->assertSame('em_dia', $linhas[$emDia->id]['simulado_situacao']);
        $this->assertSame('pendente_emissao', $linhas[$pendente->id]['simulado_situacao']);
    }

    public function test_previa_de_indicios_valida_tempos_e_alarme_sem_gravar(): void
    {
        $service = app(PaeSimuladoService::class);
        $alarme = ['audivel_todos' => false, 'morador_nome' => null, 'morador_localizacao' => null];

        $indicios = $service->previaIndicios([
            'tempos' => ['sem_dificuldade' => [array_replace(self::linha(), ['chegada_onda' => '15:00', 'saida' => '15:00'])]],
            'alarme' => $alarme,
        ]);

        $this->assertSame('saida_maior_igual_onda', $indicios[5][0]['codigo']);
        $this->assertSame('alarme_sem_morador', $indicios[2][0]['codigo']);
        $this->assertSame(0, PaeSimuladoRelatorio::query()->count());
        $this->assertErroEm('tempos.sem_dificuldade.0.saida', fn () => $service->previaIndicios([
            'tempos' => ['sem_dificuldade' => [array_replace(self::linha(), ['saida' => '99:99'])]],
            'alarme' => $alarme,
        ]));
    }

    public function test_regras_de_validacao_vem_de_fonte_unica_nos_requests(): void
    {
        $this->assertSame(array_keys(AvaliarSimuladoRequest::regras()), array_keys((new AvaliarSimuladoRequest())->rules()));

        $registrar = array_keys(RegistrarSimuladoRequest::regras());
        $this->assertSame($registrar, array_keys((new RegistrarSimuladoRequest())->rules()));
        foreach (range(1, 10) as $numero) {
            $this->assertContains("criterios.{$numero}.atende", $registrar);
            $this->assertContains("criterios.{$numero}.justificativa", $registrar);
        }

        $previa = array_keys(PreviaIndiciosSimuladoRequest::regras());
        $this->assertSame($previa, array_keys((new PreviaIndiciosSimuladoRequest())->rules()));
        $this->assertSame([], array_diff($previa, $registrar));
        $this->assertContains('tempos.hospitalares_prisionais.*.nivel_emergencia', $previa);
        $this->assertContains('alarme.audivel_todos', $previa);
    }

    /** @return array{0: PaeSimuladoService, 1: PaeProtocolo, 2: User} */
    private function protocoloExigivel(): array
    {
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $service = app(PaeSimuladoService::class);
        $service->avaliar($protocolo, self::avaliacao(), $user);

        return [$service, $protocolo, $user];
    }

    private function ccpae(PaeProtocolo $protocolo): void
    {
        PaeCcpae::create([
            'protocolo_id' => $protocolo->id,
            'codigo' => 'CCPAE-SIM-'.$protocolo->id,
            'dt_emissao' => '2025-01-01',
            'status' => PaeCcpae::STATUS_ATIVO,
        ]);
    }

    private function assertErroEm(string $chave, callable $acao): void
    {
        try {
            $acao();
            $this->fail("Deveria falhar com erro em {$chave}.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($chave, $e->errors());
        }
    }

    public static function avaliacao(string $resultado = 'exigivel', ?string $motivo = null): array
    {
        return [
            'resultado' => $resultado,
            'motivo_dispensa' => $motivo,
            'fundamentacao' => 'Enquadramento conferido no processo.',
            'num_sei' => '12345',
            'chave_idempotencia' => (string) Str::uuid(),
        ];
    }

    /** Todos os 10 critérios atendem, exceto os listados (com justificativa). */
    public static function criterios(array $naoAtendem = []): array
    {
        $criterios = [];
        foreach (range(1, 10) as $numero) {
            $criterios[$numero] = in_array($numero, $naoAtendem, true)
                ? ['atende' => false, 'justificativa' => 'Divergência apontada na verificação.']
                : ['atende' => true, 'justificativa' => null];
        }

        return $criterios;
    }

    public static function linha(): array
    {
        return [
            'nome' => 'Rota 1',
            'populacao' => 120,
            'chegada_onda' => '15:00',
            'saida' => '12:30',
            'houve_problemas' => false,
            'ponto_valido' => true,
            'estimativa' => false,
        ];
    }

    public static function registro(array $alteracoes = []): array
    {
        return array_replace([
            'dt_realizacao' => '2026-05-10',
            'nivel_emergencia' => 2,
            'dt_apresentacao' => '2026-05-20',
            'num_sei' => '1234.01.0000001/2026-01',
            'observacao' => null,
            'integrado' => false,
            'barragens_integradas' => null,
            'aviso_cedec_em' => '2026-04-30',
            'criterios' => self::criterios(),
            'tempos' => ['sem_dificuldade' => [self::linha()]],
            'alarme' => ['audivel_todos' => true, 'morador_nome' => null, 'morador_localizacao' => null],
            'informativos' => [
                'participacao' => [
                    'populacao_zas' => 800,
                    'participantes' => 320,
                    'cadastrados_pae' => 500,
                    'anos_anteriores' => [['ano' => 2025, 'participantes' => 280]],
                ],
                'ensino_observacoes' => 'Duas escolas participaram.',
                'recursos_observacoes' => null,
                'conclusao_compdec' => 'sim',
            ],
            'chave_idempotencia' => (string) Str::uuid(),
        ], $alteracoes);
    }

    public static function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf');
    }
}
```

Conferência dos números dos testes (feita ao escrever): linha padrão 15:00 = 900 s e 12:30 = 750 s (saída antes da onda, sem indício); `999:59` = 999 x 60 + 59 = 59999 s; vencimento de 2026-05-10 = 2027-05-10 e de 2025-10-09 = 2026-10-09 (Task 2); antecedências 04-30 a 05-10 = 10 dias, 06-05 a 06-10 = 5, 07-03 a 07-10 = 7; janela de 2026-10-09 começa em 2025-10-09, então 2025-10-08 sai e 2025-10-09 fica; com `hoje` 2026-05-15 a apresentação de 2026-05-20 ainda não existe.

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeSimuladoServiceTest`. Expected: `Class "App\Modules\Pae\Requests\AvaliarSimuladoRequest" not found` ou `Target class [App\Modules\Pae\Services\PaeSimuladoService] does not exist`.

- [ ] **Step 3: Requests com a fonte única das regras.**

`SDC/app/Modules/Pae/Requests/AvaliarSimuladoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Models\PaeSimuladoAvaliacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AvaliarSimuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    public function messages(): array
    {
        return self::mensagens();
    }

    /**
     * Fonte unica das regras, reutilizada pelo PaeSimuladoService fora do HTTP.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(): array
    {
        return [
            'resultado' => ['required', Rule::in(PaeSimuladoAvaliacao::RESULTADOS)],
            'motivo_dispensa' => [
                'nullable',
                'required_if:resultado,dispensado',
                'prohibited_if:resultado,exigivel',
                Rule::in(PaeSimuladoAvaliacao::MOTIVOS_DISPENSA),
            ],
            'fundamentacao' => ['required', 'string', 'max:5000'],
            'num_sei' => ['required', 'string', 'max:100'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public static function mensagens(): array
    {
        return [
            'motivo_dispensa.required_if' => 'Informe o motivo da dispensa.',
            'motivo_dispensa.prohibited_if' => 'O motivo só se aplica à dispensa.',
        ];
    }
}
```

(`PaeSimuladoService::avaliar` passa `AvaliarSimuladoRequest::mensagens()` ao `Validator::make`.)

`SDC/app/Modules/Pae/Requests/RegistrarSimuladoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\Simulado\SimuladoAnexoC;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistrarSimuladoRequest extends FormRequest
{
    private const DATA = 'date_format:Y-m-d';

    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras($this->all());
    }

    public function messages(): array
    {
        return self::mensagens();
    }

    /**
     * Fonte unica das regras, reutilizada pelo PaeSimuladoService fora do HTTP.
     * `$dados` so alimenta as regras condicionais (integrado e justificativa).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(array $dados = []): array
    {
        return [
            'dt_realizacao' => ['required', self::DATA, 'before_or_equal:today'],
            'nivel_emergencia' => ['required', 'integer', 'in:2,3'],
            'dt_apresentacao' => ['required', self::DATA, 'before_or_equal:today', 'after_or_equal:dt_realizacao'],
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'integrado' => ['required', 'boolean'],
            'barragens_integradas' => [
                'nullable', 'string', 'max:2000',
                Rule::requiredIf(fn (): bool => filter_var($dados['integrado'] ?? false, FILTER_VALIDATE_BOOLEAN)),
            ],
            'aviso_cedec_em' => ['nullable', self::DATA, 'before_or_equal:dt_realizacao'],
            'chave_idempotencia' => ['required', 'uuid'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            ...self::regrasCriterios($dados),
            ...self::regrasTempos(),
            ...self::regrasAlarme(),
            ...self::regrasInformativos(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasCriterios(array $dados = []): array
    {
        $regras = ['criterios' => ['required', 'array:'.implode(',', SimuladoAnexoC::numeros())]];
        foreach (SimuladoAnexoC::numeros() as $numero) {
            $atende = data_get($dados, "criterios.{$numero}.atende");
            $regras["criterios.{$numero}"] = ['required', 'array'];
            $regras["criterios.{$numero}.atende"] = ['required', 'boolean'];
            $regras["criterios.{$numero}.justificativa"] = [
                'nullable', 'string', 'max:2000',
                Rule::requiredIf(fn (): bool => SimuladoAnexoC::exigeJustificativa($numero, $atende)),
            ];
        }

        return $regras;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasTempos(): array
    {
        $regras = ['tempos' => ['nullable', 'array:'.implode(',', SimuladoAnexoC::CATEGORIAS)]];
        foreach (SimuladoAnexoC::CATEGORIAS as $categoria) {
            $regras["tempos.{$categoria}"] = ['nullable', 'array', 'max:50'];
            $regras["tempos.{$categoria}.*.nome"] = ['required', 'string', 'max:255'];
            $regras["tempos.{$categoria}.*.populacao"] = ['nullable', 'integer', 'min:0', 'max:10000000'];
            $regras["tempos.{$categoria}.*.chegada_onda"] = ['required', 'string', TempoAnexoE::REGRA_MM_SS];
            $regras["tempos.{$categoria}.*.saida"] = ['required', 'string', TempoAnexoE::REGRA_MM_SS];
            $regras["tempos.{$categoria}.*.houve_problemas"] = ['required', 'boolean'];
            $regras["tempos.{$categoria}.*.ponto_valido"] = ['required', 'boolean'];
            $regras["tempos.{$categoria}.*.estimativa"] = ['required', 'boolean'];
        }
        $regras['tempos.hospitalares_prisionais.*.nivel_emergencia'] = ['required', 'integer', 'in:2,3'];

        return $regras;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasAlarme(): array
    {
        return [
            'alarme' => ['required', 'array'],
            'alarme.audivel_todos' => ['required', 'boolean'],
            'alarme.morador_nome' => ['nullable', 'string', 'max:255'],
            'alarme.morador_localizacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private static function regrasInformativos(): array
    {
        $contagem = ['nullable', 'integer', 'min:0', 'max:100000000'];

        return [
            'informativos.participacao' => ['nullable', 'array'],
            'informativos.participacao.populacao_zas' => $contagem,
            'informativos.participacao.participantes' => $contagem,
            'informativos.participacao.cadastrados_pae' => $contagem,
            'informativos.participacao.anos_anteriores' => ['nullable', 'array', 'max:20'],
            'informativos.participacao.anos_anteriores.*.ano' => ['required', 'integer', 'between:2000,'.CarbonImmutable::today()->year],
            'informativos.participacao.anos_anteriores.*.participantes' => ['required', 'integer', 'min:0', 'max:100000000'],
            'informativos.ensino_observacoes' => ['nullable', 'string', 'max:5000'],
            'informativos.recursos_observacoes' => ['nullable', 'string', 'max:5000'],
            'informativos.conclusao_compdec' => ['nullable', Rule::in(['sim', 'nao'])],
        ];
    }

    /** @return array<string, string> */
    public static function mensagens(): array
    {
        return [
            'dt_apresentacao.after_or_equal' => 'A data de apresentação não pode ser anterior à data de realização.',
            'aviso_cedec_em.before_or_equal' => 'O aviso à CEDEC não pode ser posterior à realização do simulado.',
            'barragens_integradas.required' => 'Informe as barragens do simulado integrado.',
            'criterios.*.justificativa.required' => 'Justifique o critério que não atende.',
        ];
    }
}
```

`SDC/app/Modules/Pae/Requests/PreviaIndiciosSimuladoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Pre-visualizacao dos indicios na tela, sem gravar: so tempos e alarme. */
final class PreviaIndiciosSimuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    /** @return array<string, array<int, mixed>> */
    public static function regras(): array
    {
        return [...RegistrarSimuladoRequest::regrasTempos(), ...RegistrarSimuladoRequest::regrasAlarme()];
    }
}
```

- [ ] **Step 4: Serviço.** `SDC/app/Modules/Pae/Services/PaeSimuladoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoAvaliacao;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use App\Modules\Pae\Requests\AvaliarSimuladoRequest;
use App\Modules\Pae\Requests\PreviaIndiciosSimuladoRequest;
use App\Modules\Pae\Requests\RegistrarSimuladoRequest;
use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\PaeArquivoPdf;
use App\Modules\Pae\Support\PaeIdempotencia;
use App\Modules\Pae\Support\PaeListagem;
use App\Modules\Pae\Support\Simulado\PaeSimuladoJanela;
use App\Modules\Pae\Support\Simulado\SimuladoAnexoC;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PaeSimuladoService
{
    /** Art. 94: aviso a CEDEC com no minimo uma semana de antecedencia. */
    public const ANTECEDENCIA_MINIMA_DIAS = 7;

    private const CAMPOS_IDEMPOTENCIA = [
        'dt_realizacao', 'nivel_emergencia', 'dt_apresentacao', 'num_sei', 'observacao', 'integrado',
        'barragens_integradas', 'aviso_cedec_em', 'criterios', 'tempos', 'alarme', 'informativos',
    ];

    private const ROTULO_MOTIVO = [
        'licenca_instalacao' => 'Licença de Instalação, Art. 17',
        'metodo_alternativo' => 'método alternativo aprovado, Art. 21',
    ];

    public function avaliar(PaeProtocolo $protocolo, array $dados, User $user): PaeSimuladoAvaliacao
    {
        $dados = Validator::make($dados, AvaliarSimuladoRequest::regras(), AvaliarSimuladoRequest::mensagens())->validate();
        $dados['fundamentacao'] = trim($dados['fundamentacao']);
        $dados['num_sei'] = trim($dados['num_sei']);
        $dados['motivo_dispensa'] = $dados['resultado'] === 'dispensado' ? ($dados['motivo_dispensa'] ?? null) : null;
        if ($dados['fundamentacao'] === '' || $dados['num_sei'] === '') {
            throw ValidationException::withMessages(['avaliacao' => 'Informe fundamentação e número SEI.']);
        }

        return DB::transaction(function () use ($protocolo, $dados, $user): PaeSimuladoAvaliacao {
            $locked = $this->protocoloAberto($protocolo);
            $existente = $locked->avaliacoesSimulado()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                PaeIdempotencia::exigirMesmosDados($existente, $dados, ['resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei']);

                return $existente;
            }

            $avaliacao = $locked->avaliacoesSimulado()->create([
                'resultado' => $dados['resultado'],
                'motivo_dispensa' => $dados['motivo_dispensa'],
                'fundamentacao' => $dados['fundamentacao'],
                'num_sei' => $dados['num_sei'],
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'decidido_por' => $user->id,
                'decidido_em' => now(),
            ]);
            $rotulo = $avaliacao->resultado === 'exigivel'
                ? 'exigível'
                : 'dispensado ('.self::ROTULO_MOTIVO[$avaliacao->motivo_dispensa].')';
            TimelinePae::registrar($locked, 'simulado_avaliacao',
                "Exigibilidade do simulado: {$rotulo}. SEI {$avaliacao->num_sei}.", $user);

            return $avaliacao;
        });
    }

    public function registrarRelatorio(PaeProtocolo $protocolo, array $dados, UploadedFile $arquivo, User $user): PaeSimuladoRelatorio
    {
        $validado = Validator::make(
            $dados + ['arquivo' => $arquivo],
            RegistrarSimuladoRequest::regras($dados),
            RegistrarSimuladoRequest::mensagens(),
        )->validate();
        $relatorio = $this->normalizar($validado);
        if ($relatorio['num_sei'] === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }
        $erros = [];
        foreach (SimuladoAnexoC::justificativasFaltantes($relatorio['criterios']) as $numero) {
            $erros["criterios.{$numero}.justificativa"] = 'Justifique o critério que não atende.';
        }
        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }
        $chave = $validado['chave_idempotencia'];
        $calculados = [
            'validado' => SimuladoAnexoC::validado($relatorio['criterios']),
            'indicios' => SimuladoAnexoC::indicios($relatorio['tempos'], $relatorio['alarme']),
        ];

        return PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): PaeSimuladoRelatorio => DB::transaction(
            function () use ($protocolo, $relatorio, $calculados, $chave, $arquivo, $user, $pdf): PaeSimuladoRelatorio {
                $locked = $this->protocoloAberto($protocolo);
                $existente = $locked->relatoriosSimulado()->where('chave_idempotencia', $chave)->first();
                if ($existente !== null) {
                    PaeIdempotencia::exigirMesmosDados($existente, $relatorio, self::CAMPOS_IDEMPOTENCIA);

                    return $existente;
                }
                if ($locked->avaliacoesSimulado()->first()?->resultado !== 'exigivel') {
                    throw ValidationException::withMessages(['avaliacao' => 'Registre antes uma avaliação de simulado exigível.']);
                }

                $versao = (int) $locked->relatoriosSimulado()->where('dt_realizacao', $relatorio['dt_realizacao'])->max('versao') + 1;
                $metadados = $pdf->guardar("simulados/{$locked->id}/{$relatorio['dt_realizacao']}", $arquivo, 'Não foi possível guardar o relatório do simulado.');

                $registro = $locked->relatoriosSimulado()->create([
                    ...$relatorio,
                    ...$metadados,
                    ...$calculados,
                    'versao' => $versao,
                    'chave_idempotencia' => $chave,
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                $situacao = $registro->validado ? 'validado' : 'não validado';
                TimelinePae::registrar($locked, 'simulado_relatorio', sprintf(
                    'Simulado de %s, versão %d: %s. SEI %s.',
                    $registro->dt_realizacao->format('d/m/Y'), $versao, $situacao, $registro->num_sei,
                ), $user);

                return $registro;
            },
        ));
    }

    /** Indicios de uma tela ainda nao registrada: valida so tempos e alarme e nao grava. */
    public function previaIndicios(array $dados): array
    {
        $validado = Validator::make($dados, PreviaIndiciosSimuladoRequest::regras())->validate();

        return SimuladoAnexoC::indicios($this->tempos($validado['tempos'] ?? []), $this->alarme($validado['alarme']));
    }

    public function resumo(PaeProtocolo $protocolo, CarbonImmutable $hoje): array
    {
        $avaliacoes = $protocolo->avaliacoesSimulado()->with('decisor:id,name')->get();
        $relatorios = $protocolo->relatoriosSimulado()->with('registrador:id,name')
            ->orderByDesc('dt_realizacao')->orderByDesc('versao')->get();
        $vigente = $avaliacoes->first();
        $ccpae = $protocolo->ccpaeVigente()->first();
        $dia = $hoje->toDateString();
        $janela = $relatorios->filter(fn (PaeSimuladoRelatorio $r): bool => PaeSimuladoJanela::contem($r->dt_realizacao, $hoje)
            && $r->dt_apresentacao->toDateString() <= $dia);
        $validado = $this->ultimasVersoes($janela)->first(fn (PaeSimuladoRelatorio $r): bool => $r->validado);
        $idsVigentes = $this->ultimasVersoes($relatorios)->pluck('id')->flip();

        return [
            'situacao' => $this->situacaoAnual($vigente, $janela, $ccpae !== null),
            'janela_inicio' => PaeSimuladoJanela::inicio($hoje)->toDateString(),
            'proximo_vencimento' => $validado === null ? null : PaeSimuladoJanela::vencimento($validado->dt_realizacao)->toDateString(),
            'alerta_legado' => $vigente === null && $ccpae !== null,
            'avaliacao' => $vigente,
            'avaliacoes' => $avaliacoes,
            'relatorios' => $relatorios->map(fn (PaeSimuladoRelatorio $r): array => $this->apresentar($r, $idsVigentes->has($r->id)))->all(),
            'catalogo' => SimuladoAnexoC::catalogo(),
            'ccpae' => $ccpae,
        ];
    }

    /**
     * @return array{avaliacao: PaeSimuladoAvaliacao, relatorio: ?PaeSimuladoRelatorio}
     */
    public function evidenciaParaEmissao(PaeProtocolo $protocoloBloqueado, CarbonImmutable $dataEmissao): array
    {
        $avaliacao = $protocoloBloqueado->avaliacoesSimulado()->first();
        if ($avaliacao === null) {
            throw ValidationException::withMessages(['simulado' => 'Avalie a exigibilidade do simulado antes de emitir o CCPAE.']);
        }
        if ($avaliacao->resultado === 'dispensado') {
            return ['avaliacao' => $avaliacao, 'relatorio' => null];
        }

        $dia = $dataEmissao->toDateString();
        $candidatos = $protocoloBloqueado->relatoriosSimulado()
            ->whereBetween('dt_realizacao', [PaeSimuladoJanela::inicio($dataEmissao)->toDateString(), $dia])
            ->where('dt_apresentacao', '<=', $dia)
            ->orderByDesc('dt_realizacao')->orderByDesc('versao')->get();
        $relatorio = $this->ultimasVersoes($candidatos)->first(fn (PaeSimuladoRelatorio $r): bool => $r->validado);

        if ($relatorio === null || ! PaeArquivoPdf::existe($relatorio->arquivo_path)) {
            throw ValidationException::withMessages([
                'simulado' => 'A emissão exige relatório de simulado validado, realizado nos 12 meses anteriores e disponível.',
            ]);
        }

        return ['avaliacao' => $avaliacao, 'relatorio' => $relatorio];
    }

    public function anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator
    {
        return PaeListagem::anotar(
            $pagina,
            fn (array $ids): array => [
                'avaliacoes' => PaeSimuladoAvaliacao::query()->whereIn('protocolo_id', $ids)
                    ->orderByDesc('id')->get()->unique('protocolo_id')->keyBy('protocolo_id'),
                'relatorios' => PaeSimuladoRelatorio::query()->whereIn('protocolo_id', $ids)
                    ->whereBetween('dt_realizacao', [PaeSimuladoJanela::inicio($hoje)->toDateString(), $hoje->toDateString()])
                    ->where('dt_apresentacao', '<=', $hoje->toDateString())
                    ->orderByDesc('dt_realizacao')->orderByDesc('versao')
                    ->get(['id', 'protocolo_id', 'dt_realizacao', 'versao', 'validado'])->groupBy('protocolo_id'),
                'certificados' => PaeCcpae::query()->whereIn('protocolo_id', $ids)
                    ->distinct()->pluck('protocolo_id')->flip(),
            ],
            fn (int $id, array $contexto): array => [
                'simulado_situacao' => $this->situacaoAnual(
                    $contexto['avaliacoes']->get($id),
                    $contexto['relatorios']->get($id, collect()),
                    $contexto['certificados']->has($id),
                ),
            ],
        );
    }

    private function protocoloAberto(PaeProtocolo $protocolo): PaeProtocolo
    {
        $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
        if ($locked->arquivado) {
            throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: os simulados estão somente para consulta.']);
        }

        return $locked;
    }

    /**
     * @param  Collection<int, PaeSimuladoRelatorio>  $relatoriosDaJanela  ordenados por realizacao e versao decrescentes
     */
    private function situacaoAnual(?PaeSimuladoAvaliacao $avaliacao, Collection $relatoriosDaJanela, bool $temCcpae): string
    {
        if ($avaliacao === null) {
            return 'nao_avaliada';
        }
        if ($avaliacao->resultado === 'dispensado') {
            return 'dispensado';
        }

        $vigentes = $this->ultimasVersoes($relatoriosDaJanela);
        if ($vigentes->contains(fn (PaeSimuladoRelatorio $r): bool => $r->validado)) {
            return 'em_dia';
        }
        if ($vigentes->isNotEmpty()) {
            return 'nao_validado';
        }

        // Sem certificado nao ha ciclo anual em atraso: a falta do relatorio so impede a emissao.
        return $temCcpae ? 'vencido' : 'pendente_emissao';
    }

    /**
     * A maior versao de cada simulado (dt_realizacao); as revisoes anteriores nao contam.
     *
     * @param  Collection<int, PaeSimuladoRelatorio>  $relatorios  ordenados por realizacao e versao decrescentes
     * @return Collection<int, PaeSimuladoRelatorio>
     */
    private function ultimasVersoes(Collection $relatorios): Collection
    {
        return $relatorios->unique(fn (PaeSimuladoRelatorio $r): string => $r->dt_realizacao->toDateString())->values();
    }

    /** Valida o conteudo: o servidor decide `validado` e `indicios`, o cliente nunca os informa. */
    private function normalizar(array $v): array
    {
        $integrado = $this->booleano($v['integrado']);

        return [
            'dt_realizacao' => $v['dt_realizacao'],
            'nivel_emergencia' => (int) $v['nivel_emergencia'],
            'dt_apresentacao' => $v['dt_apresentacao'],
            'num_sei' => trim($v['num_sei']),
            'observacao' => $this->texto($v['observacao'] ?? null),
            'integrado' => $integrado,
            'barragens_integradas' => $integrado ? $this->texto($v['barragens_integradas'] ?? null) : null,
            'aviso_cedec_em' => $this->texto($v['aviso_cedec_em'] ?? null),
            'criterios' => $this->criterios($v['criterios']),
            'tempos' => $this->tempos($v['tempos'] ?? []),
            'alarme' => $this->alarme($v['alarme']),
            'informativos' => $this->informativos($v['informativos'] ?? []),
        ];
    }

    private function criterios(array $criterios): array
    {
        $normalizados = [];
        foreach (SimuladoAnexoC::numeros() as $numero) {
            $normalizados[$numero] = [
                'atende' => $this->booleano($criterios[$numero]['atende']),
                'justificativa' => $this->texto($criterios[$numero]['justificativa'] ?? null),
            ];
        }

        return $normalizados;
    }

    private function tempos(array $tempos): array
    {
        $normalizados = [];
        foreach (SimuladoAnexoC::CATEGORIAS as $categoria) {
            $normalizados[$categoria] = array_values(array_map(
                fn (array $linha): array => $this->linhaTempo($categoria, $linha),
                $tempos[$categoria] ?? [],
            ));
        }

        return $normalizados;
    }

    private function linhaTempo(string $categoria, array $linha): array
    {
        $normalizada = [
            'nome' => trim($linha['nome']),
            'populacao' => $this->inteiro($linha['populacao'] ?? null),
            'chegada_onda_segundos' => TempoAnexoE::paraSegundos($linha['chegada_onda']),
            'saida_segundos' => TempoAnexoE::paraSegundos($linha['saida']),
            'houve_problemas' => $this->booleano($linha['houve_problemas']),
            'ponto_valido' => $this->booleano($linha['ponto_valido']),
            'estimativa' => $this->booleano($linha['estimativa']),
        ];
        if ($categoria === 'hospitalares_prisionais') {
            $normalizada['nivel_emergencia'] = (int) $linha['nivel_emergencia'];
        }

        return $normalizada;
    }

    private function alarme(array $alarme): array
    {
        $audivel = $this->booleano($alarme['audivel_todos']);

        return [
            'audivel_todos' => $audivel,
            'morador_nome' => $audivel ? null : $this->texto($alarme['morador_nome'] ?? null),
            'morador_localizacao' => $audivel ? null : $this->texto($alarme['morador_localizacao'] ?? null),
        ];
    }

    private function informativos(array $informativos): array
    {
        $participacao = $informativos['participacao'] ?? [];

        return [
            'participacao' => [
                'populacao_zas' => $this->inteiro($participacao['populacao_zas'] ?? null),
                'participantes' => $this->inteiro($participacao['participantes'] ?? null),
                'cadastrados_pae' => $this->inteiro($participacao['cadastrados_pae'] ?? null),
                'anos_anteriores' => array_values(array_map(
                    fn (array $ano): array => ['ano' => (int) $ano['ano'], 'participantes' => (int) $ano['participantes']],
                    $participacao['anos_anteriores'] ?? [],
                )),
            ],
            'ensino_observacoes' => $this->texto($informativos['ensino_observacoes'] ?? null),
            'recursos_observacoes' => $this->texto($informativos['recursos_observacoes'] ?? null),
            'conclusao_compdec' => $informativos['conclusao_compdec'] ?? null,
        ];
    }

    private function apresentar(PaeSimuladoRelatorio $r, bool $vigente): array
    {
        $antecedencia = $r->aviso_cedec_em === null
            ? null
            : (int) round($r->aviso_cedec_em->diffInDays($r->dt_realizacao, false));

        return [
            'id' => $r->id,
            'dt_realizacao' => $r->dt_realizacao->toDateString(),
            'versao' => $r->versao,
            'vigente' => $vigente,
            'nivel_emergencia' => $r->nivel_emergencia,
            'dt_apresentacao' => $r->dt_apresentacao->toDateString(),
            'num_sei' => $r->num_sei,
            'observacao' => $r->observacao,
            'arquivo_nome_original' => $r->arquivo_nome_original,
            'integrado' => $r->integrado,
            'barragens_integradas' => $r->barragens_integradas,
            'aviso_cedec_em' => $r->aviso_cedec_em?->toDateString(),
            'aviso_antecedencia_dias' => $antecedencia,
            'alerta_aviso' => $antecedencia !== null && $antecedencia < self::ANTECEDENCIA_MINIMA_DIAS,
            'criterios' => $r->criterios,
            'tempos' => TempoAnexoE::formatarResultado($r->tempos),
            'alarme' => $r->alarme,
            'informativos' => $r->informativos,
            'validado' => $r->validado,
            'indicios' => $r->indicios,
            'registrador' => $r->registrador?->name,
            'registrado_em' => $r->registrado_em?->toIso8601String(),
        ];
    }

    private function booleano(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    private function inteiro(mixed $valor): ?int
    {
        return $valor === null || $valor === '' ? null : (int) $valor;
    }
}
```

Em `SDC/app/Modules/Pae/PaeServiceProvider.php`: acrescentar `use App\Modules\Pae\Services\PaeSimuladoService;` e, logo depois de `$this->app->singleton(PaeEvacuacaoService::class);`, a linha `$this->app->singleton(PaeSimuladoService::class);`.

- [ ] **Step 5: Rodar e ver passar.** `RUN PaeSimuladoServiceTest`. Expected: `OK (17 tests, ...)`. Depois `RUN Pae` para a regressão do módulo. Expected: `OK` (BASE + 10 + 3 + 17 testes). Se `test_resumo_calcula_a_situacao_anual_e_o_vencimento` acusar `em_dia` onde se esperava `pendente_emissao` no caso 2025-10-08, conferir `PaeSimuladoJanela::inicio` (Task 2) antes de mexer no serviço.

- [ ] **Step 6: Commit.**

```bash
git add SDC/app/Modules/Pae/Requests/AvaliarSimuladoRequest.php SDC/app/Modules/Pae/Requests/RegistrarSimuladoRequest.php SDC/app/Modules/Pae/Requests/PreviaIndiciosSimuladoRequest.php SDC/app/Modules/Pae/Services/PaeSimuladoService.php SDC/app/Modules/Pae/PaeServiceProvider.php
git commit -m "✨ feat(pae): serviço e regras do relatório de simulado"
```

---

### Task 5: Guarda do simulado na emissão do CCPAE

**Files:**
- Modify: `SDC/app/Modules/Pae/Services/PaeCcpaeService.php`
- Modify: `SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue` (somente o erro do servidor na chave `simulado`; a linha de situação do simulado entra na Task 12, junto da listagem que a alimenta)
- Test: `SDC/tests/Feature/Pae/PaeSimuladoEmissaoTest.php` (novo); ajuste em `SDC/tests/Feature/Pae/PaeDcoEmissaoTest.php`

**Interfaces:**
- Consumes: Task 4 (`PaeSimuladoService::evidenciaParaEmissao`), Task 3 (`pae_ccpae.simulado_*`).
- Produces: `PaeCcpaeService::emitir` passa a exigir evidência do simulado depois da guarda da DCO, dentro do mesmo lock; falha lança `ValidationException` na chave `simulado` e não cria status, CCPAE, comunicação nem outbox. `pae_ccpae.simulado_avaliacao_id` e `simulado_relatorio_id` ficam congelados na emissão (o relatório é nulo quando o simulado foi dispensado; CCPAE antigo conserva referências nulas).

- [ ] **Step 1: Testes que falham.** `SDC/tests/Feature/Pae/PaeSimuladoEmissaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\DTOs\EmitirCcpaeDTO;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeCcpaeService;
use App\Modules\Pae\Services\PaeDcoService;
use App\Modules\Pae\Services\PaeSimuladoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PaeSimuladoEmissaoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sem_avaliacao_bloqueia_a_emissao_sem_efeitos(): void
    {
        $user = User::factory()->create();
        $protocolo = $this->protocoloComDco($user);

        $this->assertBloqueada($protocolo, $user, '2026-06-30');
    }

    public function test_exigivel_sem_relatorio_bloqueia_a_emissao(): void
    {
        $user = User::factory()->create();
        $protocolo = $this->protocoloComDco($user);
        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $user);

        $this->assertBloqueada($protocolo, $user, '2026-06-30');
    }

    public function test_relatorio_nao_validado_bloqueia_a_emissao(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = $this->protocoloComRelatorio($user, ['criterios' => PaeSimuladoServiceTest::criterios([5])]);

        $this->assertBloqueada($protocolo, $user, '2026-06-30');
    }

    public function test_relatorio_fora_da_janela_bloqueia_e_no_limite_exato_libera(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $antigo = ['dt_realizacao' => '2025-06-15', 'dt_apresentacao' => '2025-06-20', 'aviso_cedec_em' => null];
        $umDiaDepois = $this->protocoloComRelatorio($user, $antigo);
        $noLimite = $this->protocoloComRelatorio($user, $antigo);

        $this->assertBloqueada($umDiaDepois, $user, '2026-06-16');

        $ccpae = app(PaeCcpaeService::class)->emitir($noLimite, $this->dto('2026-06-15'), $user);
        $this->assertNotNull($ccpae->simulado_relatorio_id);
        $this->assertSame(PaeProtocoloStatus::CCPAE, $noLimite->fresh()->status);
    }

    public function test_relatorio_apresentado_depois_da_emissao_nao_comprova_emissao_retroativa(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = $this->protocoloComRelatorio($user);

        $this->assertBloqueada($protocolo, $user, '2026-05-15');
    }

    public function test_arquivo_ausente_no_storage_bloqueia_a_emissao(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = $this->protocoloComRelatorio($user);
        Storage::disk('pae')->delete($protocolo->relatoriosSimulado()->first()->arquivo_path);

        $this->assertBloqueada($protocolo, $user, '2026-06-30');
    }

    public function test_dispensado_libera_com_a_avaliacao_congelada(): void
    {
        $user = User::factory()->create();
        $protocolo = $this->protocoloComDco($user);
        $avaliacao = app(PaeSimuladoService::class)->avaliar(
            $protocolo, PaeSimuladoServiceTest::avaliacao('dispensado', 'licenca_instalacao'), $user,
        );

        $ccpae = app(PaeCcpaeService::class)->emitir($protocolo, $this->dto('2026-06-30'), $user);

        $this->assertSame($avaliacao->id, $ccpae->simulado_avaliacao_id);
        $this->assertNull($ccpae->simulado_relatorio_id);
        $this->assertSame(PaeProtocoloStatus::CCPAE, $protocolo->fresh()->status);
    }

    public function test_relatorio_validado_libera_e_revisoes_posteriores_nao_alteram_o_certificado(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = $this->protocoloComRelatorio($user);
        $service = app(PaeSimuladoService::class);
        $avaliacao = $protocolo->avaliacoesSimulado()->first();
        $relatorio = $protocolo->relatoriosSimulado()->first();
        $outboxAntes = DB::table('outbox_events')->count();

        $ccpae = app(PaeCcpaeService::class)->emitir($protocolo, $this->dto('2026-06-30'), $user);

        $this->assertSame($avaliacao->id, $ccpae->simulado_avaliacao_id);
        $this->assertSame($relatorio->id, $ccpae->simulado_relatorio_id);
        $this->assertSame($outboxAntes + 1, DB::table('outbox_events')->count());
        $this->assertGreaterThan(0, $protocolo->comunicacoes()->count());

        $service->registrarRelatorio($protocolo, PaeSimuladoServiceTest::registro(['criterios' => PaeSimuladoServiceTest::criterios([2])]), PaeSimuladoServiceTest::pdf(), $user);
        $service->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao('dispensado', 'metodo_alternativo'), $user);

        $this->assertSame($avaliacao->id, $ccpae->fresh()->simulado_avaliacao_id);
        $this->assertSame($relatorio->id, $ccpae->fresh()->simulado_relatorio_id);
        $this->assertSame(1, $ccpae->fresh()->relatorioSimulado->versao);
    }

    public function test_dco_continua_valendo_e_e_conferida_antes_do_simulado(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $semDco = $this->protocoloAprovado();
        app(PaeSimuladoService::class)->avaliar($semDco, PaeSimuladoServiceTest::avaliacao('dispensado', 'licenca_instalacao'), $user);
        $semNada = $this->protocoloAprovado();

        foreach ([$semDco, $semNada] as $protocolo) {
            try {
                app(PaeCcpaeService::class)->emitir($protocolo, $this->dto('2026-06-30'), $user);
                $this->fail('A emissão sem DCO avaliada deveria falhar.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('dco', $e->errors());
                $this->assertArrayNotHasKey('simulado', $e->errors());
                $this->assertSame(0, $protocolo->ccpaes()->count());
            }
        }
    }

    private function assertBloqueada(PaeProtocolo $protocolo, User $user, string $dataEmissao): void
    {
        $outboxAntes = DB::table('outbox_events')->count();

        try {
            app(PaeCcpaeService::class)->emitir($protocolo, $this->dto($dataEmissao), $user);
            $this->fail('A emissão sem simulado comprovado deveria falhar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('simulado', $e->errors());
            $this->assertSame(PaeProtocoloStatus::APROVADO, $protocolo->fresh()->status);
            $this->assertSame(0, $protocolo->ccpaes()->count());
            $this->assertSame(0, $protocolo->comunicacoes()->count());
            $this->assertSame($outboxAntes, DB::table('outbox_events')->count());
        }
    }

    private function protocoloAprovado(): PaeProtocolo
    {
        return PaeProtocolo::factory()->create([
            'status' => PaeProtocoloStatus::APROVADO,
            'admissibilidade_legada_sem_triagem' => true,
            'admissibilidade_triagem_versao' => 0,
        ]);
    }

    /** Protocolo aprovado com a DCO dispensada, para que so o simulado decida a emissao. */
    private function protocoloComDco(User $user): PaeProtocolo
    {
        $protocolo = $this->protocoloAprovado();
        app(PaeDcoService::class)->avaliar($protocolo, [
            'resultado' => 'nao_aplicavel',
            'fundamentacao' => 'Classificação conferida no processo.',
            'num_sei' => '12345',
            'chave_idempotencia' => (string) Str::uuid(),
        ], $user);

        return $protocolo;
    }

    private function protocoloComRelatorio(User $user, array $alteracoes = []): PaeProtocolo
    {
        $protocolo = $this->protocoloComDco($user);
        $service = app(PaeSimuladoService::class);
        $service->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $user);
        $service->registrarRelatorio($protocolo, PaeSimuladoServiceTest::registro($alteracoes), PaeSimuladoServiceTest::pdf(), $user);

        return $protocolo;
    }

    private function dto(string $data): EmitirCcpaeDTO
    {
        return new EmitirCcpaeDTO('CCPAE-SIM-'.Str::random(8), CarbonImmutable::parse($data), false, null);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeSimuladoEmissaoTest`. Expected: falhas de asserção (a emissão sem simulado ainda passa) e `Undefined property: ...::$simulado_avaliacao_id` nos testes de liberação.

- [ ] **Step 3: Guarda e referências congeladas.** Em `SDC/app/Modules/Pae/Services/PaeCcpaeService.php`:

1. `use App\Modules\Pae\Services\PaeSimuladoService;` não é necessário (mesmo namespace). No construtor, depois de `private readonly PaeDcoService $dco,`:

```php
        private readonly PaeSimuladoService $simulado,
```

2. Logo depois de `$evidencia = $this->dco->evidenciaParaEmissao($protocolo, $dados->dtEmissao);`:

```php
                $evidenciaSimulado = $this->simulado->evidenciaParaEmissao($protocolo, $dados->dtEmissao);
```

3. No `PaeCcpae::create([...])`, depois de `'dco_documento_id' => $evidencia['documento']?->id,`:

```php
                    'simulado_avaliacao_id' => $evidenciaSimulado['avaliacao']->id,
                    'simulado_relatorio_id' => $evidenciaSimulado['relatorio']?->id,
```

- [ ] **Step 4: Ajustar o teste local da DCO.** `SDC/tests/Feature/Pae/PaeDcoEmissaoTest.php` emite CCPAE em três testes de sucesso, que agora precisam de simulado dispensado. Acrescentar `use App\Modules\Pae\Services\PaeSimuladoService;` e o helper:

```php
    private function dispensarSimulado(PaeProtocolo $protocolo, User $user): void
    {
        app(PaeSimuladoService::class)->avaliar($protocolo, [
            'resultado' => 'dispensado',
            'motivo_dispensa' => 'licenca_instalacao',
            'fundamentacao' => 'PAE de Licença de Instalação.',
            'num_sei' => '12345',
            'chave_idempotencia' => (string) Str::uuid(),
        ], $user);
    }
```

e chamar `$this->dispensarSimulado(<protocolo>, $user);` imediatamente antes do `emitir` de sucesso em: `test_nao_aplicavel_fundamentado_emite_com_avaliacao_congelada` (protocolo `$protocolo`), `test_trinta_de_junho_aceita_ano_anterior_mas_primeiro_de_julho_nao` (somente `$junho`; o `$julho` falha na DCO, que vem antes do simulado) e `test_dco_positiva_emite_com_comunicacoes_outbox_e_prova_congelada` (`$protocolo`). Os demais testes do arquivo falham na DCO, que é conferida primeiro, e não mudam.

- [ ] **Step 5: Erro do servidor no modal.** Em `SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue`, trocar a linha do `InputError` de erros gerais por:

```vue
        <InputError v-if="erroGeral" :message="erroGeral" />
```

e, no `<script setup>`, depois de `const form = useForm({...});`:

```js
const erroGeral = computed(() => form.errors.status || form.errors.ccpae || form.errors.dco || form.errors.simulado);
```

(`computed` já está importado do `vue` no arquivo.)

- [ ] **Step 6: Rodar e ver passar.** `RUN PaeSimuladoEmissaoTest`. Expected: `OK (9 tests, ...)`. `RUN PaeDcoEmissaoTest`. Expected: `OK (7 tests, ...)`. `RUN Pae`. Expected: `OK` (BASE + 10 + 3 + 17 + 9). `BUILD` (a contagem de `PaeSimulados` ainda pode ser 0): Expected `vite exit 0`.

- [ ] **Step 7: Commit.**

```bash
git add SDC/app/Modules/Pae/Services/PaeCcpaeService.php SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue
git commit -m "✨ feat(pae): exige simulado validado na emissão do CCPAE"
```

---

### Task 6: HTTP, rotas, download e encadeamento da listagem

**Files:**
- Create: `SDC/app/Modules/Pae/Controllers/PaeSimuladoController.php`
- Create: `SDC/resources/js/Pages/PaeSimulados.vue` (página mínima, necessária para `ensure_pages_exist`; a Task 12 a substitui)
- Modify: `SDC/routes/modules/pae.php`, `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php` (`index` e `$eventoMap`)
- Test: `SDC/tests/Feature/Pae/PaeSimuladoHttpTest.php`

**Interfaces:**
- Consumes: Task 4 (`PaeSimuladoService`, Requests), Task 1 (`PaeArquivoPdf::existe/baixar`).
- Produces: rotas nomeadas `pae.protocolo.simulados.show` (`GET /pae/protocolo/{paeProtocolo}/simulados`, `pae.protocolos.view`), `pae.protocolo.simulados.avaliar` (`POST .../simulados/avaliacoes`, `validar`), `pae.protocolo.simulados.relatorios.store` (`POST .../simulados/relatorios`, `validar`), `pae.protocolo.simulados.indicios` (`POST .../simulados/indicios`, `validar`; pré-visualização JSON dos indícios, acréscimo à lista do spec, sem gravar), `pae.protocolo.simulados.relatorios.download` (`GET .../simulados/relatorios/{paeSimuladoRelatorio}/download`, `view`, com vínculo ao protocolo). Página Inertia `PaeSimulados` com props `protocolo` (`id`, `num_protocolo`, `status`, `arquivado`), `resumo` (da Task 4), `can_validar` (permissão e protocolo não arquivado), `can_view`. Campo `simulado_situacao` em cada protocolo da listagem e eventos `simulado_avaliacao`/`simulado_relatorio` no histórico.

- [ ] **Step 1: Testes que falham.** `SDC/tests/Feature/Pae/PaeSimuladoHttpTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeSimuladoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PaeSimuladoHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pagina_exige_view_e_informa_a_situacao_e_as_permissoes(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $url = "/pae/protocolo/{$protocolo->id}/simulados";

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->usuario('view'))->get($url)->assertOk()
            ->assertInertia(fn ($page) => $page->component('PaeSimulados')
                ->where('resumo.situacao', 'nao_avaliada')->where('can_validar', false)->where('can_view', true));
        $this->actingAs($this->usuario('validar'))->get($url)->assertOk()
            ->assertInertia(fn ($page) => $page->where('can_validar', true));
    }

    public function test_avaliacao_exige_validar_e_valida_o_motivo(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $url = "/pae/protocolo/{$protocolo->id}/simulados/avaliacoes";

        $this->actingAs($this->usuario('view'))->post($url, PaeSimuladoServiceTest::avaliacao())->assertForbidden();

        $validador = $this->usuario('validar');
        $this->actingAs($validador)->post($url, PaeSimuladoServiceTest::avaliacao('dispensado'))->assertSessionHasErrors('motivo_dispensa');
        $this->actingAs($validador)->post($url, PaeSimuladoServiceTest::avaliacao('dispensado', 'licenca_instalacao'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('dispensado', $protocolo->avaliacoesSimulado()->first()->resultado);
    }

    public function test_relatorio_exige_validar_e_registra_com_o_pdf(): void
    {
        Storage::fake('pae');
        $protocolo = PaeProtocolo::factory()->create();
        $validador = $this->usuario('validar');
        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $validador);
        $url = "/pae/protocolo/{$protocolo->id}/simulados/relatorios";
        $dados = [...PaeSimuladoServiceTest::registro(), 'integrado' => 0, 'arquivo' => PaeSimuladoServiceTest::pdf()];

        $this->actingAs($this->usuario('view'))->post($url, $dados)->assertForbidden();
        $this->actingAs($validador)->post($url, $dados)->assertRedirect()->assertSessionHasNoErrors();

        $relatorio = $protocolo->relatoriosSimulado()->firstOrFail();
        $this->assertSame(1, $relatorio->versao);
        $this->assertTrue($relatorio->validado);
        Storage::disk('pae')->assertExists($relatorio->arquivo_path);
    }

    public function test_relatorio_valida_campos_e_justificativa(): void
    {
        Storage::fake('pae');
        $protocolo = PaeProtocolo::factory()->create();
        $validador = $this->usuario('validar');
        $url = "/pae/protocolo/{$protocolo->id}/simulados/relatorios";

        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(), 'arquivo' => PaeSimuladoServiceTest::pdf()])
            ->assertSessionHasErrors('avaliacao');

        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $validador);
        $semJustificativa = array_replace(PaeSimuladoServiceTest::criterios(), [3 => ['atende' => 0, 'justificativa' => '']]);
        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(['criterios' => $semJustificativa]), 'arquivo' => PaeSimuladoServiceTest::pdf()])
            ->assertSessionHasErrors('criterios.3.justificativa');
        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(['nivel_emergencia' => 4]), 'arquivo' => PaeSimuladoServiceTest::pdf()])
            ->assertSessionHasErrors('nivel_emergencia');
        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(['integrado' => 1, 'barragens_integradas' => '']), 'arquivo' => PaeSimuladoServiceTest::pdf()])
            ->assertSessionHasErrors('barragens_integradas');
        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
    }

    public function test_upload_rejeita_arquivo_ausente_grande_e_nao_pdf(): void
    {
        Storage::fake('pae');
        $protocolo = PaeProtocolo::factory()->create();
        $validador = $this->usuario('validar');
        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $validador);
        $url = "/pae/protocolo/{$protocolo->id}/simulados/relatorios";

        $this->actingAs($validador)->post($url, PaeSimuladoServiceTest::registro())->assertSessionHasErrors('arquivo');
        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(), 'arquivo' => UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf')])
            ->assertSessionHasErrors('arquivo');
        $this->actingAs($validador)->post($url, [...PaeSimuladoServiceTest::registro(), 'arquivo' => UploadedFile::fake()->create('nota.txt', 10, 'text/plain')])
            ->assertSessionHasErrors('arquivo');

        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
        $this->assertSame([], Storage::disk('pae')->allFiles());
    }

    public function test_indicios_exige_validar_e_nao_grava(): void
    {
        $protocolo = PaeProtocolo::factory()->create();
        $url = "/pae/protocolo/{$protocolo->id}/simulados/indicios";
        $dados = [
            'tempos' => ['sem_dificuldade' => [array_replace(PaeSimuladoServiceTest::linha(), ['saida' => '15:00'])]],
            'alarme' => ['audivel_todos' => 1, 'morador_nome' => null, 'morador_localizacao' => null],
        ];

        $this->actingAs($this->usuario('view'))->postJson($url, $dados)->assertForbidden();
        $this->actingAs($this->usuario('validar'))->postJson($url, $dados)
            ->assertOk()->assertJsonPath('5.0.codigo', 'saida_maior_igual_onda')->assertJsonPath('2', []);
        $this->actingAs($this->usuario('validar'))->postJson($url, ['tempos' => ['sem_dificuldade' => [['nome' => 'X']]], 'alarme' => $dados['alarme']])
            ->assertUnprocessable()->assertJsonValidationErrors('tempos.sem_dificuldade.0.saida');
        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
    }

    public function test_download_exige_view_vinculo_ao_protocolo_e_arquivo(): void
    {
        Storage::fake('pae');
        $user = User::factory()->create();
        $protocolo = PaeProtocolo::factory()->create();
        $outro = PaeProtocolo::factory()->create();
        $service = app(PaeSimuladoService::class);
        $service->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $user);
        $relatorio = $service->registrarRelatorio($protocolo, PaeSimuladoServiceTest::registro(), PaeSimuladoServiceTest::pdf(), $user);
        $rota = fn (PaeProtocolo $p): string => "/pae/protocolo/{$p->id}/simulados/relatorios/{$relatorio->id}/download";

        $this->actingAs(User::factory()->create())->get($rota($protocolo))->assertForbidden();
        $leitor = $this->usuario('view');
        $this->actingAs($leitor)->get($rota($protocolo))->assertOk()->assertDownload('relatorio.pdf');
        $this->actingAs($leitor)->get($rota($outro))->assertNotFound();

        Storage::disk('pae')->delete($relatorio->arquivo_path);
        $this->actingAs($leitor)->get($rota($protocolo))->assertNotFound();
    }

    public function test_protocolo_arquivado_recusa_registro_e_a_pagina_fica_somente_leitura(): void
    {
        Storage::fake('pae');
        $protocolo = PaeProtocolo::factory()->create();
        $validador = $this->usuario('validar');
        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao(), $validador);
        $protocolo->update(['arquivado' => true]);

        $this->actingAs($validador)->post("/pae/protocolo/{$protocolo->id}/simulados/relatorios", [...PaeSimuladoServiceTest::registro(), 'arquivo' => PaeSimuladoServiceTest::pdf()])
            ->assertSessionHasErrors('protocolo');
        $this->actingAs($validador)->get("/pae/protocolo/{$protocolo->id}/simulados")->assertOk()
            ->assertInertia(fn ($page) => $page->where('can_validar', false)->where('protocolo.arquivado', true));
        $this->assertSame(0, $protocolo->relatoriosSimulado()->count());
    }

    public function test_listagem_traz_a_situacao_e_o_historico_traduz_os_eventos(): void
    {
        $gestor = $this->usuario('view');
        Role::findOrCreate('Gestor', 'web');
        $gestor->assignRole('Gestor');
        $protocolo = PaeProtocolo::factory()->create();
        app(PaeSimuladoService::class)->avaliar($protocolo, PaeSimuladoServiceTest::avaliacao('dispensado', 'licenca_instalacao'), $gestor);

        $this->actingAs($gestor)->get('/pae?search='.urlencode($protocolo->num_protocolo))->assertOk()
            ->assertInertia(fn ($page) => $page->component('PaeProtocolosIndex')
                ->where('protocolos.data.0.id', $protocolo->id)
                ->where('protocolos.data.0.simulado_situacao', 'dispensado'));
        $this->actingAs($gestor)->getJson("/pae/protocolo/{$protocolo->id}/historico")->assertOk()
            ->assertJsonFragment(['titulo' => 'Exigibilidade do simulado avaliada']);
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

- [ ] **Step 2: Rodar e ver falhar.** `RUN PaeSimuladoHttpTest`. Expected: 404 nas rotas, que ainda não existem.

- [ ] **Step 3: Controller, rotas, página mínima e listagem.**

`SDC/app/Modules/Pae/Controllers/PaeSimuladoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use App\Modules\Pae\Requests\AvaliarSimuladoRequest;
use App\Modules\Pae\Requests\PreviaIndiciosSimuladoRequest;
use App\Modules\Pae\Requests\RegistrarSimuladoRequest;
use App\Modules\Pae\Services\PaeSimuladoService;
use App\Modules\Pae\Support\PaeArquivoPdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaeSimuladoController extends Controller
{
    public function __construct(private readonly PaeSimuladoService $simulados)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo): Response
    {
        return Inertia::render('PaeSimulados', [
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'resumo' => $this->simulados->resumo($paeProtocolo, CarbonImmutable::today()),
            'can_validar' => (bool) $request->user()?->can('pae.protocolos.validar') && ! $paeProtocolo->arquivado,
            'can_view' => (bool) $request->user()?->can('pae.protocolos.view'),
        ]);
    }

    public function avaliar(AvaliarSimuladoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $this->simulados->avaliar($paeProtocolo, $request->validated(), $request->user());

        return back()->with('success', 'Exigibilidade do simulado registrada.');
    }

    public function registrar(RegistrarSimuladoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $dados = $request->validated();
        $relatorio = $this->simulados->registrarRelatorio($paeProtocolo, $dados, $dados['arquivo'], $request->user());

        return back()->with('success', sprintf(
            'Relatório do simulado de %s registrado na versão %d.',
            $relatorio->dt_realizacao->format('d/m/Y'),
            $relatorio->versao,
        ));
    }

    public function indicios(PreviaIndiciosSimuladoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json($this->simulados->previaIndicios($request->validated()));
    }

    public function download(PaeProtocolo $paeProtocolo, PaeSimuladoRelatorio $paeSimuladoRelatorio): StreamedResponse
    {
        abort_unless($paeSimuladoRelatorio->protocolo_id === $paeProtocolo->id
            && PaeArquivoPdf::existe($paeSimuladoRelatorio->arquivo_path), 404);

        return PaeArquivoPdf::baixar($paeSimuladoRelatorio->arquivo_path, $paeSimuladoRelatorio->arquivo_nome_original);
    }
}
```

`SDC/resources/js/Pages/PaeSimulados.vue` (mínima; substituída na Task 12):

```vue
<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
    <Head :title="`Simulados - ${protocolo.num_protocolo}`" />
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Simulados do Anexo C · {{ protocolo.num_protocolo }}</h1>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});
</script>
```

Em `SDC/routes/modules/pae.php`: acrescentar `use App\Modules\Pae\Controllers\PaeSimuladoController;` junto dos demais `use` e, logo depois da rota `protocolo.evacuacao.registrar`:

```php
    Route::get('/protocolo/{paeProtocolo}/simulados', [PaeSimuladoController::class, 'show'])
        ->name('protocolo.simulados.show')
        ->middleware('can:pae.protocolos.view');

    Route::post('/protocolo/{paeProtocolo}/simulados/avaliacoes', [PaeSimuladoController::class, 'avaliar'])
        ->name('protocolo.simulados.avaliar')
        ->middleware('can:pae.protocolos.validar');

    Route::post('/protocolo/{paeProtocolo}/simulados/relatorios', [PaeSimuladoController::class, 'registrar'])
        ->name('protocolo.simulados.relatorios.store')
        ->middleware('can:pae.protocolos.validar');

    Route::post('/protocolo/{paeProtocolo}/simulados/indicios', [PaeSimuladoController::class, 'indicios'])
        ->name('protocolo.simulados.indicios')
        ->middleware('can:pae.protocolos.validar');

    Route::get('/protocolo/{paeProtocolo}/simulados/relatorios/{paeSimuladoRelatorio}/download', [PaeSimuladoController::class, 'download'])
        ->name('protocolo.simulados.relatorios.download')
        ->middleware('can:pae.protocolos.view');
```

Em `SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php`:
- `use App\Modules\Pae\Services\PaeSimuladoService;` e o parâmetro `private readonly PaeSimuladoService $simulado,` depois de `private readonly PaeEvacuacaoService $evacuacao,` no construtor;
- em `index`, trocar a anotação por:

```php
        $protocolos = $this->simulado->anotarListagem($this->evacuacao->anotarListagem($this->dco->anotarListagem(
            $this->prazos->anotarListagem($this->service->list($filters)), CarbonImmutable::today())), CarbonImmutable::today());
```

- no `$eventoMap`, depois de `'evacuacao_conferencia'`:

```php
            'simulado_avaliacao'    => ['tipo' => 'analise',     'titulo' => 'Exigibilidade do simulado avaliada'],
            'simulado_relatorio'    => ['tipo' => 'analise',     'titulo' => 'Relatório de simulado registrado'],
```

- [ ] **Step 4: Rodar e ver passar.** `RUN PaeSimuladoHttpTest`. Expected: `OK (9 tests, ...)`. Depois `RUN Pae`. Expected: `OK` (BASE + 10 + 3 + 17 + 9 + 9). Se `test_listagem_traz_a_situacao...` falhar por filtro de analista, o usuário precisa do papel `Gestor` (o teste já o atribui).

- [ ] **Step 5: Commit.**

```bash
git add SDC/app/Modules/Pae/Controllers/PaeSimuladoController.php SDC/app/Modules/Pae/Controllers/PaeProtocoloController.php SDC/routes/modules/pae.php SDC/resources/js/Pages/PaeSimulados.vue
git commit -m "✨ feat(pae): rotas, controller e listagem de simulados"
```

---

## Padrão visual das telas do PAE (Tasks 7 a 12)

Decisão do usuário (spec, "Padrão visual das telas do PAE"): DCO, conferência de evacuação, ficha do Anexo B e a nova tela de simulados seguem o padrão do RAT: cabeçalho com ícone, título, selo e contexto; abas; seções em cartões recolhíveis; campos pelos átomos e moléculas de formulário; breadcrumb legível; campos numéricos começando vazios. Nenhuma dessas tarefas muda backend ou regra.

O que já existe e é reaproveitado, sem recriar:

| Necessidade | Componente existente |
| --- | --- |
| Abas (desenho do RAT, já genérico) | `Components/Molecules/Navigation/ModuleTabs.vue` (o `RatTabs` já é só um invólucro dele) |
| Cartão de seção recolhível com ícone, título, subtítulo e estado salvo | `Components/Molecules/CollapsibleSection.vue` (versão genérica do `RatCollapsibleSection`, sem dependência do CSS do RAT; exige `namespace`, `sectionId`, `title`) |
| Selo de situação | `Components/Atoms/Badge/Badge.vue` (`variant` `info/success/warning/danger/default/neutral`) |
| Campos | `Molecules/Form/FormField.vue` (texto e número; `step`, `maxlength`, `error`), `FormSelect.vue`, `FormTextarea.vue`, `FormDateField.vue`, `RadioGroup.vue`, `ToggleField.vue` e o átomo `Atoms/Input/ToggleInput.vue`; `Atoms/Button/Button.vue` |

O que falta e as Tasks 7 e 8 criam: um cabeçalho genérico de página de detalhe com selo e contexto (`DetalheHeader`; o `Organisms/PageHeader.vue` existente serve listagens, não tem selo nem contexto), um aviso padronizado (`PaeAviso`), um campo de arquivo (`FormFileField`), o layout comum das telas do PAE (`PaeTelaLayout`) e os rótulos de breadcrumb do PAE.

**Decisão sobre o RAT:** o `RatHeader` mistura o cabeçalho com dados do RAT (número do BOS, ocorrências relacionadas, carimbo de última atualização) e usa classes próprias de selo; trocá-lo pelo componente genérico não teria como ser conferido como "aparência idêntica" sem captura de tela. Por isso **o RAT não é alterado**: `DetalheHeader` apenas espelha o desenho da primeira linha do `RatHeader` (ícone em degradê, título, selo, chip de contexto) usando o `Badge` do sistema. As abas e a seção recolhível já são genéricas e o RAT já as consome.

## Ordem e paralelismo

Backend (Tasks 1 a 6, em sequência, ficam em `SDC/app` e `SDC/tests`) e visual (Tasks 7 a 11, em `SDC/resources/js`) não compartilham arquivos e podem andar em paralelo, em duas frentes:

- Frente A (backend): 1, 2, 3, 4, 5, 6 nessa ordem. A Task 5 edita `EmitirCcpaeModal.vue` e a Task 6 cria o `PaeSimulados.vue` mínimo.
- Frente B (visual): Task 7 primeiro (base de tudo); depois **8, 9, 10 e 11 em paralelo** (arquivos disjuntos: `useBreadcrumb.js`; `PaeDco.vue`; `PaeEvacuacao.vue` + organismos + composable; `PaeFichaAnexoB.vue`).
- Task 12 (tela de simulados e listagem) exige 6 (rotas e props), 7 e 8 prontas e a Task 5 já integrada, porque as duas editam `EmitirCcpaeModal.vue`; ela substitui o `PaeSimulados.vue` mínimo da Task 6. Não toca nenhum arquivo das Tasks 9 a 11.
- Task 13 (verificação e integração) por último.
- Cada tarefa visual verifica-se com `BUILD` e com os testes HTTP que já afirmam o componente Inertia (`PaeDcoHttpTest`, `PaeEvacuacaoHttpTest`, e o de ficha, se existir localmente); não há harness de teste de JS no projeto.

### Task 7: Componentes genéricos de tela (cabeçalho, aviso, arquivo, layout)

**Files:**
- Create: `SDC/resources/js/Components/Molecules/DetalheHeader.vue`
- Create: `SDC/resources/js/Components/Molecules/Form/FormFileField.vue`
- Create: `SDC/resources/js/Components/Molecules/Pae/PaeAviso.vue`
- Create: `SDC/resources/js/Templates/Pae/PaeTelaLayout.vue`
- Create: `SDC/resources/js/utils/paeTela.js`
- Modify: `SDC/resources/js/Components/Molecules/Form/FormField.vue`, `FormSelect.vue` (prop opcional `ariaLabel`, para campos de tabela sem rótulo visível)
- RAT: nenhum arquivo alterado (ver decisão acima).

**Interfaces:**
- Consumes: `Atoms/Badge/Badge.vue`, `Atoms/Typography/Label.vue`, `Molecules/Navigation/ModuleTabs.vue`.
- Produces:
  - `DetalheHeader` props `title`, `icon` (componente, padrão `DocumentTextIcon`), `subtitle`, `statusLabel`, `statusVariant`, `contextLabel`, `contextValue`; slots `extra` e `actions`.
  - `FormFileField` v-model (`File|null`), props `label`, `accept` (padrão PDF), `error`, `hint`, `required`, `disabled`, `id`.
  - `PaeAviso` prop `tom` (`info|aviso|erro|neutro`) e slot padrão.
  - `PaeTelaLayout` props `titulo`, `icone`, `subtitulo`, `protocolo` (`{num_protocolo}`), `statusLabel`, `statusVariant`, `abas` (formato do `ModuleTabs`: `{id, label, icon, badge?}`), `aba` (v-model `update:aba`); slots `actions`, `avisos`, `topo` (acima das abas), padrão (recebe `{ aba }`), `rodape`. Sem `abas` o conteúdo padrão aparece sem trilho.
  - `utils/paeTela.js`: `formatarData(valor)`, `errosSemCampo(erros, camposConhecidos)`, `opcoes(mapa)`.
  - `FormField` e `FormSelect` ganham a prop opcional `ariaLabel` (`undefined` por padrão), repassada ao `<input>`/`<select>`; sem ela nada muda para os formulários existentes.

- [ ] **Step 1: Criar os componentes.**

`SDC/resources/js/Components/Molecules/DetalheHeader.vue`:

```vue
<template>
  <div class="mb-4 flex flex-col gap-3 border-b border-slate-200 pb-3 dark:border-slate-700/50 sm:mb-6 sm:pb-4">
    <div class="flex items-start gap-3 sm:items-center">
      <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/20 sm:h-12 sm:w-12 sm:rounded-xl">
        <component :is="iconeEfetivo" class="h-5 w-5 text-white sm:h-6 sm:w-6" />
      </div>

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ title }}</h1>
          <Badge v-if="statusLabel" :variant="statusVariant" size="md">{{ statusLabel }}</Badge>
        </div>
        <p v-if="subtitle" class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ subtitle }}</p>

        <div v-if="contextValue" class="mt-2 flex items-center gap-2">
          <span v-if="contextLabel" class="whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ contextLabel }}</span>
          <span class="rounded border border-blue-200 bg-blue-50 px-2 py-0.5 font-mono text-base font-black tracking-widest text-blue-700 dark:border-blue-700/50 dark:bg-blue-900/20 dark:text-blue-300 sm:text-xl">{{ contextValue }}</span>
        </div>

        <slot name="extra" />
      </div>

      <div v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

/**
 * Cabecalho de pagina de detalhe: icone em degrade, titulo, selo de situacao e
 * chip de contexto (por exemplo o numero do protocolo). Espelha a primeira
 * linha do RatHeader sem os dados especificos do RAT.
 */
const props = defineProps({
  title: { type: String, required: true },
  // Sem default de funcao: em prop do tipo Function o Vue usa o default como o proprio valor.
  icon: { type: [Object, Function], default: undefined },
  subtitle: { type: String, default: '' },
  statusLabel: { type: String, default: '' },
  statusVariant: { type: String, default: 'default' },
  contextLabel: { type: String, default: '' },
  contextValue: { type: String, default: '' },
});

const iconeEfetivo = computed(() => props.icon ?? DocumentTextIcon);
</script>
```

`SDC/resources/js/Components/Molecules/Form/FormFileField.vue`:

```vue
<template>
  <div class="form-field">
    <Label v-if="label" :for-id="inputId" :required="required">{{ label }}</Label>
    <input
      :id="inputId"
      ref="entrada"
      type="file"
      :accept="accept"
      :disabled="disabled"
      :required="required"
      class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-500 disabled:opacity-60 dark:text-slate-200"
      @change="$emit('update:modelValue', $event.target.files?.[0] ?? null)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Label from '@/Components/Atoms/Typography/Label.vue';

const props = defineProps({
  modelValue: { type: Object, default: null },
  label: { type: String, default: '' },
  accept: { type: String, default: 'application/pdf,.pdf' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const entrada = ref(null);
const inputId = computed(() => props.id || `file-${Math.random().toString(36).slice(2, 11)}`);

// O pai zera o modelo depois do envio; o input nativo precisa acompanhar.
watch(() => props.modelValue, (valor) => {
  if (valor === null && entrada.value) entrada.value.value = '';
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
```

`SDC/resources/js/Components/Molecules/Pae/PaeAviso.vue`:

```vue
<template>
  <div role="status" :class="['rounded-lg border p-3 text-sm', CLASSES[tom] ?? CLASSES.info]">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  tom: {
    type: String,
    default: 'info',
    validator: (valor) => ['info', 'aviso', 'erro', 'neutro'].includes(valor),
  },
});

// Classes literais: o Tailwind so enxerga o que esta escrito por extenso.
const CLASSES = {
  info: 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
  aviso: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
  erro: 'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
  neutro: 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
};
</script>
```

`SDC/resources/js/Templates/Pae/PaeTelaLayout.vue`:

```vue
<template>
  <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6">
    <DetalheHeader
      :title="titulo"
      :icon="icone"
      :subtitle="subtitulo"
      :status-label="statusLabel"
      :status-variant="statusVariant"
      context-label="Protocolo"
      :context-value="protocolo.num_protocolo"
    >
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </DetalheHeader>

    <div v-if="$slots.avisos" class="mb-4 space-y-3">
      <slot name="avisos" />
    </div>

    <div v-if="$slots.topo" class="mb-4 space-y-4">
      <slot name="topo" />
    </div>

    <ModuleTabs v-if="abas.length" :tabs="abas" :active-tab="aba" @tab-change="$emit('update:aba', $event)">
      <template #default="{ activeTab }">
        <slot :aba="activeTab" />
      </template>
    </ModuleTabs>
    <slot v-else :aba="null" />

    <div v-if="$slots.rodape" class="mt-4 space-y-4">
      <slot name="rodape" />
    </div>
  </div>
</template>

<script setup>
import DetalheHeader from '@/Components/Molecules/DetalheHeader.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';

defineProps({
  titulo: { type: String, required: true },
  icone: { type: [Object, Function], default: undefined },
  subtitulo: { type: String, default: '' },
  protocolo: { type: Object, required: true },
  statusLabel: { type: String, default: '' },
  statusVariant: { type: String, default: 'default' },
  abas: { type: Array, default: () => [] },
  aba: { type: [String, Number], default: null },
});

defineEmits(['update:aba']);
</script>
```

`SDC/resources/js/utils/paeTela.js`:

```js
// Apoio comum das telas do PAE (DCO, evacuacao, ficha cadastral e simulados).

/**
 * Datas puras (YYYY-MM-DD) nao passam por Date, que deslocaria um dia pelo
 * fuso; valores com horario viram data local.
 */
export function formatarData(valor) {
  if (!valor) return '—';
  const texto = String(valor);
  if (texto.length > 10) {
    const data = new Date(texto);
    if (!Number.isNaN(data.getTime())) return data.toLocaleDateString('pt-BR');
  }
  const [ano, mes, dia] = texto.slice(0, 10).split('-');
  return `${dia}/${mes}/${ano}`;
}

/** Junta os erros de chaves sem campo proprio no formulario (ex.: avaliacao, dco, protocolo). */
export function errosSemCampo(erros, camposConhecidos) {
  return Object.entries(erros)
    .filter(([campo]) => !camposConhecidos.includes(campo))
    .map(([, mensagem]) => mensagem)
    .join(' ');
}

/** {chave: rotulo} para a lista {value, label} dos selects. */
export function opcoes(mapa) {
  return Object.entries(mapa).map(([value, label]) => ({ value, label }));
}
```

- [ ] **Step 2: `ariaLabel` em `FormField` e `FormSelect`.** Em `SDC/resources/js/Components/Molecules/Form/FormField.vue`: no `<TextInput ...>` acrescentar `:aria-label="ariaLabel"` (junto de `:size="size"`) e, em `defineProps`, depois de `id`:

```js
  // Rotulo acessivel para campo sem Label visivel (celula de tabela).
  ariaLabel: {
    type: String,
    default: undefined,
  },
```

Em `SDC/resources/js/Components/Molecules/Form/FormSelect.vue`: no `<SelectInput ...>` acrescentar `:aria-label="ariaLabel"` e, em `defineProps`, depois de `id`, a mesma prop `ariaLabel`. (`TextInput` e `SelectInput` têm um único elemento raiz, então o atributo chega ao `<input>`/`<select>`.)

- [ ] **Step 3: Build.** `BUILD`. Expected: `vite exit 0` e `built in` (os componentes ainda não têm consumidor; o Vite só os compila nas tarefas seguintes, então este passo apenas garante que nada existente quebrou). Conferir que o RAT ficou intocado: `git diff --stat -- SDC/resources/js/Components/Rat SDC/resources/js/Pages/Rat` sem saída.

- [ ] **Step 4: Commit.**

```bash
git add SDC/resources/js/Components/Molecules/Form/FormField.vue SDC/resources/js/Components/Molecules/Form/FormSelect.vue SDC/resources/js/Components/Molecules/DetalheHeader.vue SDC/resources/js/Components/Molecules/Form/FormFileField.vue SDC/resources/js/Components/Molecules/Pae SDC/resources/js/Templates/Pae/PaeTelaLayout.vue SDC/resources/js/utils/paeTela.js
git commit -m "✨ feat(ui): cabeçalho, aviso, arquivo e layout genéricos para telas do PAE"
```

### Task 8: Breadcrumb legível das telas do PAE

**Files:**
- Modify: `SDC/resources/js/Composables/ui/useBreadcrumb.js` (o `Composables/useBreadcrumb.js` só reexporta este)

**Interfaces:**
- Consumes: props `protocolo` das páginas `PaeDco`, `PaeEvacuacao`, `PaeFichaAnexoB`, `PaeSimulados` (todas trazem `protocolo.num_protocolo`).
- Produces: trilhas `Início › PAE › Protocolo <número> › <tela>` (`DCO`, `Evacuação`, `Ficha cadastral`, `Simulados`) e `Início › PAE` para `PaeProtocolosIndex`. O degrau `PAE` leva a `pae.protocolos.index`, então o botão Voltar (que procura o último item com rota antes da página atual) volta para a listagem.

Como o cabeçalho constrói os rótulos hoje: `NavigationHeader` usa `useBreadcrumb()`, que consulta `breadcrumbMap` (estático), depois trilhas por módulo que leem props da página (`trilhaCisterna`, `trilhaTdap`, `trilhaResgate`) e, por último, um fallback que humaniza o nome do componente, por isso as telas do PAE aparecem como "Pae Dco" e "Pae Evacuacao". A correção segue o padrão das trilhas por módulo.

- [ ] **Step 1: Acrescentar a trilha.** Em `SDC/resources/js/Composables/ui/useBreadcrumb.js`, logo antes de `const breadcrumbItems = computed(() => {`:

```js
    /**
     * Trilha das telas do PAE.
     *
     * Pelo fallback automatico o rotulo vinha do nome do componente ("Pae Dco",
     * "Pae Evacuacao") e nada dizia de QUAL protocolo estava aberto. O degrau
     * do protocolo nao tem pagina propria, entao nao vira link; o degrau PAE
     * leva a listagem, que e para onde o Voltar deve ir.
     */
    const trilhaPae = (componentName, props) => {
        const inicio = { label: 'Início', route: 'dashboard' };
        const pae = { label: 'PAE', route: 'pae.protocolos.index' };
        const aqui = (label) => ({ label, route: null });
        const numero = props?.protocolo?.num_protocolo ?? null;
        const protocolo = aqui(numero ? `Protocolo ${numero}` : 'Protocolo');

        const trilhas = {
            PaeProtocolosIndex: [inicio, aqui('PAE')],
            PaeDco: [inicio, pae, protocolo, aqui('DCO')],
            PaeEvacuacao: [inicio, pae, protocolo, aqui('Evacuação')],
            PaeFichaAnexoB: [inicio, pae, protocolo, aqui('Ficha cadastral')],
            PaeSimulados: [inicio, pae, protocolo, aqui('Simulados')],
        };

        return trilhas[componentName] ?? null;
    };

```

e, em `breadcrumbItems`, logo depois do bloco `const doResgate = ...; if (doResgate) { return doResgate; }`:

```js
        const doPae = trilhaPae(componentName, propsDaPagina);

        if (doPae) {
            return doPae;
        }
```

- [ ] **Step 2: Verificar.** `BUILD` (Expected: `vite exit 0`) e `RUN PaeDcoHttpTest` seguido de `RUN PaeEvacuacaoHttpTest` (Expected: `OK`; os componentes Inertia e as rotas não mudaram). Conferir no navegador, se o MCP do Playwright conectar, que `/pae/protocolo/{id}/dco` mostra `Início › PAE › Protocolo … › DCO` e que Voltar leva à listagem; senão registrar a pendência sem afirmar a verificação.

- [ ] **Step 3: Commit.**

```bash
git add SDC/resources/js/Composables/ui/useBreadcrumb.js
git commit -m "✨ feat(ui): breadcrumb legível nas telas do PAE"
```

### Task 9: Redesenho da tela da DCO

**Files:**
- Modify (substituir): `SDC/resources/js/Pages/PaeDco.vue`

**Interfaces:**
- Consumes: Task 7 (`PaeTelaLayout`, `PaeAviso`, `FormFileField`, `utils/paeTela.js`), `CollapsibleSection`, `Badge` via layout, átomos de formulário. As props da página e as rotas (`pae.protocolo.dco.avaliar`, `pae.protocolo.dco.documentos.store`, `pae.protocolo.dco.documentos.download`) não mudam; nenhum backend é tocado.
- Produces: a mesma página Inertia `PaeDco`, com cabeçalho (ícone, título, selo da situação anual, chip do protocolo), abas Aplicabilidade e Declarações, seções recolhíveis e campos pelos átomos.

- [ ] **Step 1: Substituir `SDC/resources/js/Pages/PaeDco.vue`** (a lógica de formulários, ids de idempotência e envio fica igual à anterior):

```vue
<template>
  <Head :title="`DCO - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Declaração de Conformidade e Operacionalidade"
    :icone="ShieldCheckIcon"
    :protocolo="protocolo"
    :status-label="rotuloSituacao"
    :status-variant="varianteSituacao"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso tom="neutro">Competência {{ resumo.competencia_anual }} · prazo {{ formatarData(resumo.vencimento) }}</PaeAviso>
      <PaeAviso v-if="resumo.entrega_tardia" tom="aviso">A DCO desta competência foi apresentada após 30 de junho. O atraso permanece registrado no histórico.</PaeAviso>
      <PaeAviso v-if="resumo.alerta_legado" tom="aviso">Este CCPAE é anterior ao registro de aplicabilidade da DCO. A CEDEC deve avaliar o protocolo.</PaeAviso>
      <PaeAviso v-if="['atrasada', 'nao_conforme'].includes(resumo.situacao)" tom="erro">Pendência para análise da CEDEC. O sistema não altera automaticamente a vigência do CCPAE.</PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: histórico disponível somente para leitura.</PaeAviso>
    </template>

    <template #default="{ aba: ativa }">
      <div v-if="ativa === 'aplicabilidade'" class="space-y-4">
        <CollapsibleSection namespace="pae" section-id="dco-vigente" title="Decisão vigente" subtitle="A avaliação mais recente da CEDEC prevalece." :icon="ShieldCheckIcon">
          <div v-if="resumo.avaliacao" class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">
            <p class="font-semibold text-slate-900 dark:text-white">{{ resumo.avaliacao.resultado === 'aplicavel' ? 'DCO aplicável' : 'DCO não aplicável' }}</p>
            <p class="mt-1 text-slate-700 dark:text-slate-200">{{ resumo.avaliacao.fundamentacao }}</p>
            <p class="mt-1 text-slate-500 dark:text-slate-400">SEI {{ resumo.avaliacao.num_sei }} · {{ resumo.avaliacao.decisor?.name || 'Responsável não disponível' }} · {{ formatarData(resumo.avaliacao.decidido_em) }}</p>
          </div>
          <PaeAviso v-else tom="aviso">Aplicabilidade ainda não avaliada. A emissão de CCPAE ficará bloqueada.</PaeAviso>
        </CollapsibleSection>

        <CollapsibleSection v-if="can_validar" namespace="pae" section-id="dco-avaliar" title="Registrar nova avaliação" subtitle="Cada avaliação é um registro novo; o histórico é preservado." :icon="PencilSquareIcon" tom="success">
          <form class="space-y-3" @submit.prevent="avaliar">
            <FormSelect v-model="avaliacao.resultado" label="Resultado" :options="OPCOES_RESULTADO_AVALIACAO" placeholder="" :error="avaliacao.errors.resultado" required />
            <FormTextarea v-model="avaliacao.fundamentacao" label="Fundamentação" :rows="3" :error="avaliacao.errors.fundamentacao" required />
            <FormField v-model="avaliacao.num_sei" label="Número SEI" maxlength="100" :error="avaliacao.errors.num_sei" required />
            <p v-if="errosSemCampo(avaliacao.errors, CAMPOS_AVALIACAO)" class="text-sm text-red-600 dark:text-red-300">{{ errosSemCampo(avaliacao.errors, CAMPOS_AVALIACAO) }}</p>
            <Button type="submit" :loading="avaliacao.processing">Registrar avaliação</Button>
          </form>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="dco-historico-avaliacoes" title="Histórico de avaliações" :subtitle="`${resumo.avaliacoes.length} registro(s)`" :icon="ClockIcon" tom="neutro">
          <p v-if="!resumo.avaliacoes.length" class="text-sm text-slate-500">Nenhuma avaliação registrada.</p>
          <ol v-else class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-for="item in resumo.avaliacoes" :key="item.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
              <strong>{{ item.resultado === 'aplicavel' ? 'Aplicável' : 'Não aplicável' }}</strong> · SEI {{ item.num_sei }} · {{ formatarData(item.decidido_em) }}
              <p class="mt-1">{{ item.fundamentacao }}</p>
            </li>
          </ol>
        </CollapsibleSection>
      </div>

      <div v-else class="space-y-4">
        <CollapsibleSection v-if="can_validar && resumo.avaliacao?.resultado === 'aplicavel'" namespace="pae" section-id="dco-registrar" title="Registrar declaração" subtitle="Cada apresentação gera uma versão; a mais recente da competência prevalece na emissão." :icon="DocumentArrowUpIcon" tom="success">
          <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="registrarDocumento">
            <FormField v-model="documento.competencia" type="number" label="Competência" step="1" :error="documento.errors.competencia" required />
            <FormSelect v-model="documento.resultado" label="Resultado conferido" :options="OPCOES_RESULTADO_DOCUMENTO" placeholder="" :error="documento.errors.resultado" required />
            <FormDateField v-model="documento.dt_documento" label="Data da DCO" :error="documento.errors.dt_documento" required />
            <FormDateField v-model="documento.dt_apresentacao" label="Apresentada à CEDEC em" :error="documento.errors.dt_apresentacao" required />
            <FormField v-model="documento.num_sei" label="Número SEI" maxlength="100" :error="documento.errors.num_sei" required />
            <FormFileField v-model="documento.arquivo" label="Arquivo PDF (até 20 MiB)" :error="documento.errors.arquivo" required />
            <FormTextarea v-model="documento.observacao" class="sm:col-span-2" label="Observação" :rows="2" :error="documento.errors.observacao" />
            <p v-if="errosSemCampo(documento.errors, CAMPOS_DOCUMENTO)" class="text-sm text-red-600 dark:text-red-300 sm:col-span-2">{{ errosSemCampo(documento.errors, CAMPOS_DOCUMENTO) }}</p>
            <div class="sm:col-span-2"><Button type="submit" :loading="documento.processing">Registrar DCO</Button></div>
          </form>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="dco-declaracoes" title="Declarações apresentadas" :subtitle="`${resumo.documentos.length} versão(ões)`" :icon="DocumentTextIcon" tom="neutro">
          <p v-if="!resumo.documentos.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma DCO apresentada.</p>
          <ul v-else class="space-y-3">
            <li v-for="item in resumo.documentos" :key="item.id" class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <strong class="text-slate-900 dark:text-white">{{ item.competencia }} · versão {{ item.versao }} · {{ item.resultado === 'positiva' ? 'Positiva' : 'Não conforme' }}</strong>
                <a v-if="can_view" :href="route('pae.protocolo.dco.documentos.download', [protocolo.id, item.id])" class="font-semibold text-blue-700 underline dark:text-blue-300">Baixar PDF</a>
              </div>
              <p class="mt-1 text-slate-600 dark:text-slate-300">DCO de {{ formatarData(item.dt_documento) }} · apresentada {{ formatarData(item.dt_apresentacao) }} · SEI {{ item.num_sei }}</p>
              <p v-if="item.entrega_tardia" class="mt-1 text-amber-700 dark:text-amber-300">Apresentação após o prazo anual.</p>
              <p v-if="item.observacao" class="mt-1 text-slate-600 dark:text-slate-300">{{ item.observacao }}</p>
            </li>
          </ul>
        </CollapsibleSection>
      </div>
    </template>

    <template v-if="resumo.ccpae" #rodape>
      <CollapsibleSection namespace="pae" section-id="dco-ccpae" title="Evidência usada no CCPAE" :icon="ShieldCheckIcon" tom="neutro">
        <p class="text-sm text-slate-700 dark:text-slate-300">{{ resumo.ccpae.codigo }} · avaliação #{{ resumo.ccpae.dco_avaliacao_id || 'legada, sem referência' }} · DCO #{{ resumo.ccpae.dco_documento_id || 'não exigida ou legada' }}</p>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormFileField from '@/Components/Molecules/Form/FormFileField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { errosSemCampo, formatarData } from '@/utils/paeTela';
import { ClockIcon, DocumentArrowUpIcon, DocumentTextIcon, PencilSquareIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});

const CAMPOS_AVALIACAO = ['resultado', 'fundamentacao', 'num_sei'];
const CAMPOS_DOCUMENTO = ['competencia', 'resultado', 'dt_documento', 'dt_apresentacao', 'num_sei', 'arquivo', 'observacao'];
const OPCOES_RESULTADO_AVALIACAO = [{ value: 'aplicavel', label: 'Aplicável' }, { value: 'nao_aplicavel', label: 'Não aplicável' }];
const OPCOES_RESULTADO_DOCUMENTO = [{ value: 'positiva', label: 'Positiva' }, { value: 'nao_conforme', label: 'Não conforme' }];

const aba = ref('aplicabilidade');
const abas = [
  { id: 'aplicabilidade', label: 'Aplicabilidade', icon: ShieldCheckIcon },
  { id: 'declaracoes', label: 'Declarações', icon: DocumentTextIcon, badge: props.resumo.documentos.length || null },
];

const avaliacao = useForm({ resultado: 'aplicavel', fundamentacao: '', num_sei: '', chave_idempotencia: crypto.randomUUID() });
const documento = useForm({ competencia: props.resumo.competencia_anual, resultado: 'positiva', dt_documento: '', dt_apresentacao: '', num_sei: '', observacao: '', arquivo: null, chave_idempotencia: crypto.randomUUID() });

const ROTULOS = { nao_avaliada: 'Não avaliada', nao_aplicavel: 'Não aplicável', comprovada: 'Comprovada', aguardando_prazo: 'Aguardando prazo', pendente_emissao: 'Pendente para emissão', atrasada: 'Atrasada', nao_conforme: 'Não conforme' };
const VARIANTES = { comprovada: 'success', nao_aplicavel: 'neutral', aguardando_prazo: 'warning', pendente_emissao: 'warning', atrasada: 'danger', nao_conforme: 'danger' };
const rotuloSituacao = computed(() => ROTULOS[props.resumo.situacao] || props.resumo.situacao);
const varianteSituacao = computed(() => VARIANTES[props.resumo.situacao] || 'default');

function avaliar() {
  avaliacao.post(route('pae.protocolo.dco.avaliar', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => { avaliacao.reset('fundamentacao', 'num_sei'); avaliacao.chave_idempotencia = crypto.randomUUID(); },
  });
}

function registrarDocumento() {
  documento.post(route('pae.protocolo.dco.documentos.store', props.protocolo.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { documento.reset('dt_documento', 'dt_apresentacao', 'num_sei', 'observacao', 'arquivo'); documento.chave_idempotencia = crypto.randomUUID(); },
  });
}
</script>
```

Nota: `documento.reset(..., 'arquivo')` leva `arquivo` a `null` e o `FormFileField` limpa o input nativo. O download é um `<a>` simples (resposta de arquivo, não visita Inertia), como na tela anterior.

- [ ] **Step 2: Verificar.** `BUILD` (Expected: `vite exit 0`, `built in`). `RUN PaeDcoHttpTest` (Expected: `OK`, o componente `PaeDco` e as props `resumo.situacao` não mudaram). No navegador, se o MCP conectar: aba Declarações, link "Baixar PDF" baixando o arquivo, modo escuro e 400 px; senão registrar a pendência sem afirmar a verificação.

- [ ] **Step 3: Commit.**

```bash
git add SDC/resources/js/Pages/PaeDco.vue
git commit -m "♻️ refactor(pae): redesenha a tela da DCO no padrão visual do sistema"
```

---

### Task 10: Redesenho da conferência de evacuação (página, organismos e campos numéricos vazios)

**Files:**
- Modify (substituir): `SDC/resources/js/Pages/PaeEvacuacao.vue`
- Modify (substituir): `SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue`, `EvacuacaoRotasEditor.vue`, `EvacuacaoAcessosEditor.vue`, `EvacuacaoPontosEditor.vue`, `EvacuacaoResultadoPainel.vue`
- Modify: `SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js` (fábricas de linha com campos numéricos vazios)
- Modify: `SDC/resources/js/utils/paeEvacuacao.js` (remover `CLASSE_CAMPO`, que deixa de ter consumidor)

**Interfaces:**
- Consumes: Task 7 (`PaeTelaLayout`, `PaeAviso`, `FormField`/`FormSelect` com `aria-label`, `utils/paeTela.js`), `CollapsibleSection`, `Button`, `ToggleInput`, `Badge` via layout. Props da página, rotas (`pae.protocolo.evacuacao.*`), contrato do composable (`{ form, resultado, simulado, simulando, erros, erroSimulacao, simular, registrar, adicionar, remover }`) e props dos organismos (`itens`, `erros`, `resultado`, `simulado`, `somenteLeitura`, mais `setoresDisponiveis` e `rotasDisponiveis`) **não mudam**; nenhum backend é tocado.
- Produces: a página Inertia `PaeEvacuacao` com cabeçalho (ícone, título, selo Conforme/Não conforme/Não conferida, chip do protocolo), painel de resultado sempre visível, abas Setores, Rotas, Acessos, Pontos de encontro e Histórico, registro (TTE declarado, SEI, observação, Simular, Registrar) no rodapé; campos numéricos de linhas novas começam vazios.
- Os números digitados passam a chegar como texto (os átomos emitem `string`); o servidor já aceita (`integer`/`numeric` validam texto numérico e o serviço converte com `(int)`/`(float)`), e campo vazio vira erro `required` no próprio campo em vez de um 0 silencioso.

- [ ] **Step 1: Campos numéricos vazios.** Em `SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js`, trocar o objeto `FABRICAS` por:

```js
const FABRICAS = {
  setores: () => ({ id: '', populacao: '', comercial: false, via: 'calcada', largura: '', lados: 2, distancia: '', terreno: 'plano' }),
  rotas: () => ({ id: '', setores_texto: '', chegada_onda: '', nivel_emergencia: 1 }),
  acessos: () => ({ id: '', largura: '', terreno: 'plano', rotas_texto: '' }),
  pontos_encontro: () => ({ nome: '', endereco: '', populacao: '', area: '' }),
};
```

Em `SDC/resources/js/utils/paeEvacuacao.js`, apagar a constante `CLASSE_CAMPO` (e confirmar com `grep -rn CLASSE_CAMPO SDC/resources/js` que só restam as ocorrências dos arquivos reescritos neste Task, que deixam de usá-la).

- [ ] **Step 2: Organismos.** Cada editor passa a ser um `CollapsibleSection` com a tabela montada por `FormField`/`FormSelect`/`ToggleInput`.

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-setores" title="Setores de evacuação" subtitle="Sem calçada, a largura da rua desconta 2,90 m (mão única) ou 5,80 m (mão dupla)." :icon="Squares2X2Icon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar setor</Button>
    </div>
    <div class="overflow-x-auto">
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
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="setor.id" size="sm" :aria-label="`Identificador do setor ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`setores.${i}.id`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="setor.populacao" type="number" step="1" size="sm" :aria-label="`Moradores do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><ToggleInput v-model="setor.comercial" :aria-label="`Área comercial do setor ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormSelect v-model="setor.via" size="sm" placeholder="" :options="OPCOES_VIA" :aria-label="`Tipo de via do setor ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="setor.largura" type="number" step="0.01" size="sm" :aria-label="`Largura do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.largura`]" /></td>
            <td class="min-w-[5rem] px-2 py-2">
              <FormSelect v-if="setor.via === 'calcada'" v-model="setor.lados" size="sm" placeholder="" :options="OPCOES_LADOS" :aria-label="`Lados da calçada do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.lados`]" />
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="setor.distancia" type="number" step="0.01" size="sm" :aria-label="`Distância do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.distancia`]" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormSelect v-model="setor.terreno" size="sm" placeholder="" :options="OPCOES_TERRENO" :aria-label="`Terreno do setor ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.densidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.velocidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(setor)?.tempo_fmt ?? '—' }}
              <span v-if="SITUACOES_SETOR[calculo(setor)?.situacao]" class="block text-xs text-red-700 dark:text-red-300">{{ SITUACOES_SETOR[calculo(setor).situacao] }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.setores" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { SITUACOES_SETOR, numero } from '@/utils/paeEvacuacao';
import { Squares2X2Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_VIA = [{ value: 'calcada', label: 'Calçada' }, { value: 'rua_mao_unica', label: 'Rua mão única' }, { value: 'rua_mao_dupla', label: 'Rua mão dupla' }];
const OPCOES_LADOS = [{ value: 1, label: '1' }, { value: 2, label: '2' }];
const OPCOES_TERRENO = [{ value: 'plano', label: 'Plano' }, { value: 'inclinado', label: 'Inclinado (> 5%)' }];

const calculo = (setor) => props.simulado ? (props.resultado?.setores?.[setor.id] ?? null) : null;
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-rotas" title="Rotas de fuga (Critério 2)" :subtitle="`Setores na ordem do percurso, separados por vírgula. Disponíveis: ${setoresDisponiveis.join(', ') || 'nenhum'}.`" :icon="MapIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar rota</Button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Rota</th><th class="px-2 py-2">Setores</th><th class="px-2 py-2">Chegada da onda (mm:ss)</th>
            <th class="px-2 py-2">Nível</th><th class="px-2 py-2">TERF</th><th class="px-2 py-2">Saída</th><th class="px-2 py-2">Saída &lt; onda?</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(rota, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="rota.id" size="sm" :aria-label="`Identificador da rota ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`rotas.${i}.id`]" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormField v-model="rota.setores_texto" size="sm" placeholder="A, D, E" :aria-label="`Setores da rota ${i + 1}`" :disabled="somenteLeitura" :error="erroDaLinha(erros, `rotas.${i}.setores`)" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="rota.chegada_onda" size="sm" placeholder="15:00" maxlength="6" :aria-label="`Chegada da onda da rota ${i + 1}`" :disabled="somenteLeitura" :error="erros[`rotas.${i}.chegada_onda`]" /></td>
            <td class="min-w-[5rem] px-2 py-2"><FormSelect v-model="rota.nivel_emergencia" size="sm" placeholder="" :options="OPCOES_NIVEL" :aria-label="`Nível de emergência da rota ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.terf_fmt ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.saida_fmt ?? '—' }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(rota)" :class="calculo(rota).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(rota).conforme ? 'Sim' : 'Não' }}</span>
              <span v-if="calculo(rota)?.motivo" class="block text-xs text-red-700 dark:text-red-300">{{ MOTIVOS_ROTA[calculo(rota).motivo] }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.rotas" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { MOTIVOS_ROTA, erroDaLinha } from '@/utils/paeEvacuacao';
import { MapIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  setoresDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_NIVEL = [{ value: 1, label: '1' }, { value: 2, label: '2' }, { value: 3, label: '3' }];

const calculo = (rota) => props.simulado ? (props.resultado?.rotas?.[rota.id] ?? null) : null;
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-acessos" title="Acessos à área segura (estrangulamento)" :subtitle="`Abaixo de 1,2 m a rota não pode ser usada (art. 48, §6º). Rotas: ${rotasDisponiveis.join(', ') || 'nenhuma'}.`" :icon="ArrowsPointingInIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar acesso</Button>
    </div>
    <p v-if="!itens.length" class="text-sm text-slate-500 dark:text-slate-400">Sem acesso informado, o tempo total é o tempo máximo de deslocamento.</p>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Acesso</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Terreno</th><th class="px-2 py-2">Rotas</th><th class="px-2 py-2">Pessoas</th><th class="px-2 py-2">TE</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(acesso, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="acesso.id" size="sm" :aria-label="`Identificador do acesso ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`acessos.${i}.id`]" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="acesso.largura" type="number" step="0.01" size="sm" :aria-label="`Largura do acesso ${i + 1}`" :disabled="somenteLeitura" :error="erros[`acessos.${i}.largura`]" /></td>
            <td class="min-w-[10rem] px-2 py-2"><FormSelect v-model="acesso.terreno" size="sm" placeholder="" :options="OPCOES_TERRENO" :aria-label="`Terreno do acesso ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="min-w-[8rem] px-2 py-2"><FormField v-model="acesso.rotas_texto" size="sm" placeholder="R1, R2" :aria-label="`Rotas do acesso ${i + 1}`" :disabled="somenteLeitura" :error="erroDaLinha(erros, `acessos.${i}.rotas`)" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(acesso)?.n ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(acesso)?.te_fmt ?? '—' }}
              <span v-if="calculo(acesso)?.invalido" class="block text-xs text-red-700 dark:text-red-300">abaixo de 1,2 m</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.acessos" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { erroDaLinha } from '@/utils/paeEvacuacao';
import { ArrowsPointingInIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  rotasDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_TERRENO = [{ value: 'plano', label: 'Plano' }, { value: 'inclinado', label: 'Rampa ou escada' }];

const calculo = (acesso) => props.simulado ? (props.resultado?.acessos?.[acesso.id] ?? null) : null;
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-pontos" title="Pontos de encontro (Critério 1)" subtitle="Atende quando a população estimada por m² é menor que 3." :icon="MapPinIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar ponto</Button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Local</th><th class="px-2 py-2">Endereço</th><th class="px-2 py-2">População</th><th class="px-2 py-2">Área (m²)</th><th class="px-2 py-2">Pessoas/m²</th><th class="px-2 py-2">&lt; 3?</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(ponto, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[9rem] px-2 py-2"><FormField v-model="ponto.nome" size="sm" :aria-label="`Local do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="255" :error="erros[`pontos_encontro.${i}.nome`]" /></td>
            <td class="min-w-[10rem] px-2 py-2"><FormField v-model="ponto.endereco" size="sm" :aria-label="`Endereço do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="500" :error="erros[`pontos_encontro.${i}.endereco`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="ponto.populacao" type="number" step="1" size="sm" :aria-label="`População do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" :error="erros[`pontos_encontro.${i}.populacao`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="ponto.area" type="number" step="0.01" size="sm" :aria-label="`Área do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" :error="erros[`pontos_encontro.${i}.area`]" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(i)?.densidade) }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(i)" :class="calculo(i).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(i).conforme ? 'Sim' : 'Não' }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.pontos_encontro" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import { numero } from '@/utils/paeEvacuacao';
import { MapPinIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (indice) => (props.simulado ? props.resultado?.pontos_encontro?.[indice] ?? null : null);
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue` (o selo de conformidade sai do painel e vai para o cabeçalho da página; o painel mantém os tempos e os critérios):

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-resultado" title="Resultado" subtitle="TTE = maior valor entre o tempo máximo de deslocamento e o estrangulamento." :icon="ChartBarIcon" :tom="conforme || !resultado ? 'info' : 'danger'">
    <p v-if="!resultado" class="text-sm text-slate-500 dark:text-slate-400">Preencha os dados e clique em Simular.</p>
    <template v-else>
      <dl class="grid gap-3 sm:grid-cols-3">
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
      <PaeAviso v-if="!simulado" tom="aviso" class="mt-3">Os dados mudaram desde a última simulação.</PaeAviso>
    </template>
    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">A conferência apenas sinaliza: não bloqueia a tramitação nem a emissão do CCPAE.</p>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
});

const okClasse = 'text-green-700 dark:text-green-300';
const erroClasse = 'text-red-700 dark:text-red-300';

// A regra de conformidade e do servidor (CalculoEvacuacaoAnexoE::conforme); aqui so se exibe.
const conforme = computed(() => Boolean(props.resultado?.conforme));

const tempos = computed(() => [
  { rotulo: 'TTE', valor: props.resultado?.tte_fmt },
  { rotulo: 'TMD', valor: props.resultado?.tmd_fmt },
  { rotulo: 'TE', valor: props.resultado?.te_fmt },
]);
</script>
```

- [ ] **Step 3: Página.** Substituir `SDC/resources/js/Pages/PaeEvacuacao.vue`:

```vue
<template>
  <Head :title="`Evacuação - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Conferência de evacuação"
    subtitulo="Resolução GMG nº 83/2024 · Anexo E"
    :icone="UserGroupIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="seloVariante"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="historica" tom="aviso">
        Consultando a versão {{ conferencia.versao }}.
        <Link :href="route('pae.protocolo.evacuacao.show', protocolo.id)" class="font-semibold underline">Ir para a versão atual ({{ versao_atual }})</Link>
      </PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: conferência somente para consulta.</PaeAviso>
    </template>

    <template #topo>
      <EvacuacaoResultadoPainel :resultado="resultado" :simulado="simulado" />
    </template>

    <template #default="{ aba: ativa }">
      <EvacuacaoSetoresEditor v-if="ativa === 'setores'" :itens="form.setores" :erros="erros" :resultado="resultado" :simulado="simulado" :somente-leitura="!can_edit" @adicionar="adicionar('setores')" @remover="remover('setores', $event)" />
      <EvacuacaoRotasEditor v-else-if="ativa === 'rotas'" :itens="form.rotas" :erros="erros" :resultado="resultado" :simulado="simulado" :setores-disponiveis="idsSetores" :somente-leitura="!can_edit" @adicionar="adicionar('rotas')" @remover="remover('rotas', $event)" />
      <EvacuacaoAcessosEditor v-else-if="ativa === 'acessos'" :itens="form.acessos" :erros="erros" :resultado="resultado" :simulado="simulado" :rotas-disponiveis="idsRotas" :somente-leitura="!can_edit" @adicionar="adicionar('acessos')" @remover="remover('acessos', $event)" />
      <EvacuacaoPontosEditor v-else-if="ativa === 'pontos'" :itens="form.pontos_encontro" :erros="erros" :resultado="resultado" :simulado="simulado" :somente-leitura="!can_edit" @adicionar="adicionar('pontos_encontro')" @remover="remover('pontos_encontro', $event)" />

      <CollapsibleSection v-else namespace="pae" section-id="evacuacao-historico" title="Histórico de conferências" :subtitle="`${historico.length} versão(ões)`" :icon="ClockIcon" tom="neutro">
        <p v-if="!historico.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma conferência registrada.</p>
        <ol v-else class="space-y-2 text-sm">
          <li v-for="item in historico" :key="item.versao" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <span class="text-slate-700 dark:text-slate-200">Versão {{ item.versao }} · {{ item.autor || '—' }} · {{ formatarData(item.created_at) }} · SEI {{ item.num_sei }} · TTE {{ item.tte_fmt ?? '—' }}</span>
            <span class="flex items-center gap-3">
              <Badge :variant="item.conforme ? 'success' : 'danger'" size="sm">{{ item.conforme ? 'Conforme' : 'Não conforme' }}</Badge>
              <Link :href="route('pae.protocolo.evacuacao.versao', [protocolo.id, item.versao])" class="font-semibold text-blue-700 underline dark:text-blue-300">Abrir</Link>
            </span>
          </li>
        </ol>
      </CollapsibleSection>
    </template>

    <template #rodape>
      <CollapsibleSection v-if="conferencia && !can_edit" namespace="pae" section-id="evacuacao-registro-lido" :title="`Registro da versão ${conferencia.versao}`" :icon="DocumentTextIcon" tom="neutro">
        <dl class="grid gap-3 text-sm sm:grid-cols-3">
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Número SEI</dt><dd class="text-slate-800 dark:text-slate-100">{{ conferencia.num_sei || '—' }}</dd></div>
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Autor</dt><dd class="text-slate-800 dark:text-slate-100">{{ conferencia.autor || '—' }}</dd></div>
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Data</dt><dd class="text-slate-800 dark:text-slate-100">{{ formatarData(conferencia.created_at) }}</dd></div>
          <div class="sm:col-span-3"><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Observação</dt><dd class="whitespace-pre-line text-slate-800 dark:text-slate-100">{{ conferencia.observacao || '—' }}</dd></div>
        </dl>
      </CollapsibleSection>

      <CollapsibleSection namespace="pae" section-id="evacuacao-registro" title="Tempo declarado e registro" subtitle="Informe o SEI e registre depois de simular." :icon="PencilSquareIcon" tom="success">
        <div class="grid gap-3 sm:grid-cols-3">
          <FormField v-model="form.tte_declarado" label="Tempo total declarado pelo empreendedor (mm:ss)" placeholder="15:00" maxlength="6" :disabled="!can_edit" :error="erros.tte_declarado" />
          <template v-if="can_edit">
            <FormField v-model="form.num_sei" label="Número SEI" maxlength="100" :error="erros.num_sei" />
            <FormTextarea v-model="form.observacao" label="Observação" :rows="1" :error="erros.observacao" />
          </template>
        </div>
        <p v-if="erros.chave_idempotencia || erros.protocolo" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erros.chave_idempotencia || erros.protocolo }}</p>
        <p v-if="erroSimulacao" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erroSimulacao }}</p>
        <div v-if="can_edit" class="mt-4 flex flex-wrap justify-end gap-3">
          <Button variant="outline" :loading="simulando" @click="simular">{{ simulando ? 'Simulando...' : 'Simular' }}</Button>
          <Button :disabled="!simulado || form.processing || !form.num_sei" @click="registrar">Registrar conferência</Button>
        </div>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import EvacuacaoAcessosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue';
import EvacuacaoPontosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue';
import EvacuacaoResultadoPainel from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue';
import EvacuacaoRotasEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue';
import EvacuacaoSetoresEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue';
import { usePaeEvacuacaoForm } from '@/Composables/pae/usePaeEvacuacaoForm';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { formatarData } from '@/utils/paeTela';
import { ArrowsPointingInIcon, ClockIcon, DocumentTextIcon, MapIcon, MapPinIcon, PencilSquareIcon, Squares2X2Icon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  conferencia: { type: Object, default: null },
  historico: { type: Array, default: () => [] },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
  can_edit: { type: Boolean, default: false },
});

const { form, resultado, simulado, simulando, erros, erroSimulacao, simular, registrar, adicionar, remover } = usePaeEvacuacaoForm(props.protocolo.id, props.conferencia);

const aba = ref('setores');
const abas = computed(() => [
  { id: 'setores', label: 'Setores', icon: Squares2X2Icon, badge: form.setores.length || null },
  { id: 'rotas', label: 'Rotas', icon: MapIcon, badge: form.rotas.length || null },
  { id: 'acessos', label: 'Acessos', icon: ArrowsPointingInIcon, badge: form.acessos.length || null },
  { id: 'pontos', label: 'Pontos de encontro', icon: MapPinIcon, badge: form.pontos_encontro.length || null },
  { id: 'historico', label: 'Histórico', icon: ClockIcon, badge: props.historico.length || null },
]);

const seloRotulo = computed(() => (props.conferencia ? (props.conferencia.conforme ? 'Conforme' : 'Não conforme') : 'Não conferida'));
const seloVariante = computed(() => (props.conferencia ? (props.conferencia.conforme ? 'success' : 'danger') : 'default'));

const idsSetores = computed(() => form.setores.map((s) => s.id).filter(Boolean));
const idsRotas = computed(() => form.rotas.map((r) => r.id).filter(Boolean));
</script>
```

- [ ] **Step 4: Verificar.** `grep -rn "CLASSE_CAMPO\|<input\|<select\|<textarea" SDC/resources/js/Pages/PaeEvacuacao.vue SDC/resources/js/Components/Organisms/Pae/Evacuacao` sem saída (nenhum campo solto restou). `BUILD` (Expected: `vite exit 0`, `built in`). `RUN PaeEvacuacaoHttpTest` (Expected: `OK`; o componente `PaeEvacuacao` e as props não mudaram). No navegador, se o MCP conectar: abrir o protocolo, ver que linhas novas começam com campos numéricos vazios e que o navegador não acusa "valor mínimo" antes do preenchimento, simular o exemplo do item 3.3 do Anexo E (setor de 680 moradores, 170 m), trocar de aba sem perder os dados, versão antiga somente leitura, usuário só com `view`, 400 px e modo escuro; senão registrar a pendência sem afirmar a verificação.

- [ ] **Step 5: Commit.**

```bash
git add SDC/resources/js/Pages/PaeEvacuacao.vue SDC/resources/js/Components/Organisms/Pae/Evacuacao SDC/resources/js/Composables/pae/usePaeEvacuacaoForm.js SDC/resources/js/utils/paeEvacuacao.js
git commit -m "♻️ refactor(pae): redesenha a conferência de evacuação e esvazia os campos numéricos"
```

---

### Task 11: Redesenho da ficha cadastral do Anexo B

**Files:**
- Modify (substituir): `SDC/resources/js/Pages/PaeFichaAnexoB.vue`

**Interfaces:**
- Consumes: Task 7 (`PaeTelaLayout`, `PaeAviso`, `FormField`/`FormSelect` com `aria-label`), `CollapsibleSection`, `Button`, `FormTextarea`. Props da página, rotas (`pae.protocolo.ficha-anexo-b.show`, `pae.protocolo.ficha-anexo-b.salvar`, `pae.protocolos.index`) e o formato do payload enviado (`base_versao`, campos do Anexo B, `cursos_agua`, `estruturas_associadas`) **não mudam**; nenhum backend é tocado.
- Produces: a mesma página Inertia `PaeFichaAnexoB`, com cabeçalho (ícone, título, selo da versão ou rascunho, chip do protocolo), abas Cadastro e Versões e pendências, grupos em seções recolhíveis e campos pelos átomos. O botão Salvar fica no rodapé, visível nas duas abas.
- Diferença conhecida: os `min` dos campos numéricos (`min="0"`) deixam de ser atributos do navegador porque `FormField` não os repassa ao `<input>`; o servidor continua validando o mínimo e devolvendo o erro no campo.

- [ ] **Step 1: Substituir `SDC/resources/js/Pages/PaeFichaAnexoB.vue`** (a lógica do script, incluindo `valoresIniciais`, `salvar` e o tratamento das listas, é a mesma da tela anterior):

```vue
<template>
  <Head :title="`Ficha cadastral - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Ficha cadastral"
    subtitulo="PAE · Anexo B, item 2"
    :icone="ClipboardDocumentListIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="seloVariante"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="rascunho" tom="info">Os dados existentes do empreendimento aparecem como pré-preenchimento. Salve a ficha para criar a primeira versão deste protocolo.</PaeAviso>
      <PaeAviso v-if="historica" tom="neutro">Você está consultando a versão {{ ficha.versao }}. <a :href="urlAtual" class="font-semibold underline">Abrir versão atual</a></PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Este protocolo está arquivado. A ficha permanece disponível para consulta.</PaeAviso>
      <PaeAviso v-if="municipios_alterados" tom="aviso">A lista ZAS/ZSS mudou depois desta versão. A próxima gravação da ficha incluirá a lista atual.</PaeAviso>
    </template>

    <template #default="{ aba: ativa }">
      <form v-if="ativa === 'cadastro'" class="space-y-4" @submit.prevent="salvar">
        <CollapsibleSection v-for="grupo in grupos" :key="grupo.titulo" namespace="pae" :section-id="`ficha-${grupo.chave}`" :title="grupo.titulo" :subtitle="grupo.ajuda" :icon="DocumentTextIcon">
          <div class="grid gap-4 sm:grid-cols-2">
            <template v-for="campo in grupo.campos" :key="campo.chave">
              <FormField v-if="campo.chave === 'municipio_sede_id' && !podeEditar" :model-value="ficha.municipio_sede_nome || 'Não informado'" :label="campo.rotulo" disabled />
              <FormSelect v-else-if="campo.chave === 'municipio_sede_id'" v-model="form.municipio_sede_id" :label="campo.rotulo" :options="opcoesMunicipios" placeholder="Não informado" :error="form.errors[campo.chave]" />
              <FormTextarea v-else-if="campo.tipo === 'textarea'" v-model="form[campo.chave]" class="sm:col-span-2" :label="campo.rotulo" :rows="3" :disabled="!podeEditar" :error="form.errors[campo.chave]" />
              <FormField v-else v-model="form[campo.chave]" :class="campo.largo ? 'sm:col-span-2' : ''" :type="campo.tipo" :step="campo.step" :maxlength="campo.maxlength" :label="campo.rotulo" :disabled="!podeEditar" :error="form.errors[campo.chave]" />
            </template>
          </div>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-municipios" title="Municípios da ZAS e da ZSS" subtitle="A lista é cadastrada na triagem do protocolo e preservada em cada versão da ficha." :icon="MapPinIcon">
          <div v-if="ficha.municipios_snapshot?.length" class="flex flex-wrap gap-2">
            <span v-for="municipio in ficha.municipios_snapshot" :key="municipio.municipio_id" class="rounded-full border border-slate-300 px-3 py-1 text-sm text-slate-700 dark:border-slate-600 dark:text-slate-200">
              {{ municipio.nome }} <span class="text-slate-500 dark:text-slate-400">({{ [municipio.na_zas ? 'ZAS' : null, municipio.na_zss ? 'ZSS' : null].filter(Boolean).join(' / ') }})</span>
            </span>
          </div>
          <p v-else class="text-sm text-amber-700 dark:text-amber-300">Nenhum município informado.</p>
          <a :href="route('pae.protocolos.index', { triagem: protocolo.id })" class="mt-4 inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Abrir triagem do protocolo</a>
          <p v-if="form.errors.municipios_snapshot" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ form.errors.municipios_snapshot }}</p>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-listas" title="Rios e estruturas associadas" :icon="MapIcon">
          <div class="grid gap-5 md:grid-cols-2">
            <div v-for="lista in listas" :key="lista.chave" class="space-y-3">
              <FormSelect :model-value="estadoLista(lista.chave)" :label="lista.rotulo" :options="OPCOES_ESTADO_LISTA" placeholder="" :disabled="!podeEditar" @update:model-value="alterarEstadoLista(lista.chave, $event)" />
              <div v-if="Array.isArray(form[lista.chave]) && form[lista.chave].length" class="space-y-2">
                <div v-for="(item, indice) in form[lista.chave]" :key="indice" class="flex items-start gap-2">
                  <FormField v-model="form[lista.chave][indice]" class="min-w-0 flex-1" maxlength="255" :aria-label="`${lista.rotulo} ${indice + 1}`" :disabled="!podeEditar" />
                  <Button v-if="podeEditar" variant="outline" size="sm" @click="removerItem(lista.chave, indice)">Remover</Button>
                </div>
              </div>
              <Button v-if="podeEditar && Array.isArray(form[lista.chave])" variant="outline" size="sm" @click="adicionarItem(lista.chave)">Adicionar item</Button>
              <p v-if="form.errors[lista.chave]" class="text-xs text-red-600 dark:text-red-300">{{ form.errors[lista.chave] }}</p>
              <p v-for="(item, chave) in errosDaLista(lista.chave)" :key="chave" class="text-xs text-red-600 dark:text-red-300">{{ item }}</p>
            </div>
          </div>
        </CollapsibleSection>
      </form>

      <div v-else class="space-y-4">
        <CollapsibleSection namespace="pae" section-id="ficha-versao" title="Versão e pendências" :icon="ClipboardDocumentListIcon">
          <p class="text-sm text-slate-600 dark:text-slate-300">{{ rascunho ? 'Rascunho não salvo' : `Versão ${ficha.versao}` }}</p>
          <p v-if="ficha.criado_em" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ficha.autor_nome || 'Autor não disponível' }} · {{ formatarDataHora(ficha.criado_em) }}</p>
          <p v-if="!pendencias.length" class="mt-3 text-sm text-green-700 dark:text-green-300">Dados do item 2 informados.</p>
          <div v-else class="mt-3">
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ pendencias.length }} campo(s) pendente(s) nesta versão</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
              <li v-for="campo in pendencias" :key="campo">{{ rotuloPendencia(campo) }}</li>
            </ul>
          </div>
          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">As pendências são informativas e atualizam após salvar. Não mudam o status do PAE nesta fase.</p>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="ficha-historico" title="Histórico" :subtitle="`${versoes.length} versão(ões)`" :icon="ClockIcon" tom="neutro">
          <p v-if="!versoes.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma versão salva.</p>
          <ul v-else class="space-y-2">
            <li v-for="item in versoes" :key="item.versao">
              <a :href="urlVersao(item.versao)" class="block rounded-lg border px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="item.versao === ficha.versao ? 'border-blue-500 text-blue-800 dark:text-blue-200' : 'border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300'">
                <span class="font-semibold">Versão {{ item.versao }}</span>
                <span class="mt-1 block text-xs">{{ item.autor_nome || 'Autor não disponível' }} · {{ formatarDataHora(item.criado_em) }}</span>
              </a>
            </li>
          </ul>
        </CollapsibleSection>
      </div>
    </template>

    <template #rodape>
      <PaeAviso v-if="form.errors.base_versao || form.errors.protocolo" tom="erro">{{ form.errors.base_versao || form.errors.protocolo }}</PaeAviso>
      <div v-if="podeEditar" class="flex justify-end">
        <Button :loading="form.processing" @click="salvar">{{ form.processing ? 'Salvando...' : 'Salvar ficha cadastral' }}</Button>
      </div>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { ClipboardDocumentListIcon, ClockIcon, DocumentTextIcon, MapIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  ficha: { type: Object, required: true },
  versoes: { type: Array, default: () => [] },
  municipios_atuais: { type: Array, default: () => [] },
  municipios_disponiveis: { type: Array, default: () => [] },
  pendencias: { type: Array, default: () => [] },
  municipios_alterados: { type: Boolean, default: false },
  can_edit: { type: Boolean, default: false },
  rascunho: { type: Boolean, default: false },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
});

const grupos = [
  { chave: 'identificacao', titulo: 'Identificação da barragem', campos: [
    { chave: 'nome_barragem', rotulo: 'Nome da barragem', tipo: 'text', maxlength: 255 },
    { chave: 'nome_mina', rotulo: 'Nome da mina', tipo: 'text', maxlength: 255 },
    { chave: 'metodo_construtivo', rotulo: 'Método construtivo', tipo: 'text', maxlength: 100 },
    { chave: 'volume_reservatorio', rotulo: 'Volume do reservatório (m³)', tipo: 'number', step: '0.01' },
  ] },
  { chave: 'localizacao', titulo: 'Localização', ajuda: 'Coordenadas geográficas da estrutura em graus decimais.', campos: [
    { chave: 'municipio_sede_id', rotulo: 'Município sede' },
    { chave: 'latitude', rotulo: 'Latitude', tipo: 'number', step: '0.0000001' },
    { chave: 'longitude', rotulo: 'Longitude', tipo: 'number', step: '0.0000001' },
  ] },
  { chave: 'rejeito', titulo: 'Rejeito ou resíduo', campos: [
    { chave: 'tipo_rejeito', rotulo: 'Tipo do rejeito ou resíduo', tipo: 'textarea', largo: true },
    { chave: 'toxicidade', rotulo: 'Toxicidade conforme ABNT NBR 10004', tipo: 'text', maxlength: 255, largo: true },
  ] },
  { chave: 'zas', titulo: 'ZAS e ZSS', ajuda: 'Na população total da ZAS, considere moradores, trabalhadores e público flutuante.', campos: [
    { chave: 'extensao_zas_km', rotulo: 'Extensão da ZAS (km)', tipo: 'number', step: '0.001' },
    { chave: 'populacao_zas', rotulo: 'População total da ZAS', tipo: 'number', step: 1 },
    { chave: 'populacao_zas_mobilidade_reduzida', rotulo: 'População da ZAS com dificuldade de locomoção ou necessidades especiais', tipo: 'number', step: 1 },
    { chave: 'populacao_zss', rotulo: 'População total da ZSS', tipo: 'number', step: 1 },
  ] },
  { chave: 'edificacoes', titulo: 'Edificações sensíveis na ZAS', campos: [
    { chave: 'edificacoes_hospitalares', rotulo: 'Unidades hospitalares', tipo: 'number', step: 1 },
    { chave: 'edificacoes_escolares', rotulo: 'Unidades escolares', tipo: 'number', step: 1 },
    { chave: 'edificacoes_prisionais', rotulo: 'Unidades prisionais', tipo: 'number', step: 1 },
    { chave: 'edificacoes_outras', rotulo: 'Outras edificações sensíveis', tipo: 'number', step: 1 },
  ] },
];

const listas = [
  { chave: 'cursos_agua', rotulo: 'Rios e cursos d’água diretamente afetados' },
  { chave: 'estruturas_associadas', rotulo: 'Estruturas associadas (ECJ, pilhas, diques etc.)' },
];
const OPCOES_ESTADO_LISTA = [{ value: 'pendente', label: 'Não informado' }, { value: 'nenhum', label: 'Nenhum' }, { value: 'informado', label: 'Informar itens' }];
const rotulos = Object.fromEntries([
  ...grupos.flatMap(grupo => grupo.campos.map(campo => [campo.chave, campo.rotulo])),
  ...listas.map(lista => [lista.chave, lista.rotulo]),
  ['municipios_zas', 'Municípios na ZAS'],
  ['municipios_zss', 'Municípios na ZSS'],
]);
const numericos = grupos.flatMap(grupo => grupo.campos.filter(campo => campo.tipo === 'number').map(campo => campo.chave));

function valoresIniciais(ficha) {
  return {
    base_versao: ficha.versao ?? 0,
    ...Object.fromEntries(grupos.flatMap(grupo => grupo.campos.map(campo => [campo.chave, ficha[campo.chave] ?? '']))),
    cursos_agua: ficha.cursos_agua === null ? null : [...(ficha.cursos_agua ?? [])],
    estruturas_associadas: ficha.estruturas_associadas === null ? null : [...(ficha.estruturas_associadas ?? [])],
  };
}

const form = useForm(valoresIniciais(props.ficha));
const podeEditar = computed(() => props.can_edit && !props.historica && !props.protocolo.arquivado);
const urlAtual = computed(() => route('pae.protocolo.ficha-anexo-b.show', props.protocolo.id));
const opcoesMunicipios = computed(() => props.municipios_disponiveis.map(m => ({ value: m.id, label: `${m.nome} / ${m.uf}` })));

const aba = ref('cadastro');
const abas = computed(() => [
  { id: 'cadastro', label: 'Cadastro', icon: DocumentTextIcon },
  { id: 'versoes', label: 'Versões e pendências', icon: ClockIcon, badge: props.pendencias.length || null },
]);
const seloRotulo = computed(() => (props.rascunho ? 'Rascunho' : `Versão ${props.ficha.versao}${props.historica ? ' (histórica)' : ''}`));
const seloVariante = computed(() => (props.rascunho ? 'default' : (props.pendencias.length ? 'warning' : 'success')));

watch(() => [props.ficha.versao, props.historica], ([versao, historica], [anterior, historicaAnterior] = []) => {
  if (versao !== anterior || historica !== historicaAnterior) {
    Object.assign(form, valoresIniciais(props.ficha));
    form.clearErrors();
  }
});

function estadoLista(chave) {
  if (form[chave] === null) return 'pendente';
  return form[chave].length === 0 ? 'nenhum' : 'informado';
}

function alterarEstadoLista(chave, estado) {
  form[chave] = estado === 'pendente' ? null : estado === 'nenhum' ? [] : (form[chave]?.length ? form[chave] : ['']);
}

function adicionarItem(chave) {
  form[chave].push('');
}

function removerItem(chave, indice) {
  form[chave].splice(indice, 1);
}

function errosDaLista(chave) {
  return Object.fromEntries(Object.entries(form.errors).filter(([campo]) => campo.startsWith(`${chave}.`)));
}

function salvar() {
  form.transform(dados => ({
    ...dados,
    ...Object.fromEntries(numericos.map(chave => [chave, dados[chave] === '' ? null : dados[chave]])),
    municipio_sede_id: dados.municipio_sede_id === '' ? null : dados.municipio_sede_id,
  })).put(route('pae.protocolo.ficha-anexo-b.salvar', props.protocolo.id), { preserveScroll: true });
}

function urlVersao(versao) {
  return route('pae.protocolo.ficha-anexo-b.show', { paeProtocolo: props.protocolo.id, versao });
}

function rotuloPendencia(chave) {
  return rotulos[chave] ?? chave;
}

function formatarDataHora(valor) {
  return valor ? new Date(valor).toLocaleString('pt-BR') : '';
}
</script>
```

- [ ] **Step 2: Verificar.** `grep -n "<input\|<select\|<textarea" SDC/resources/js/Pages/PaeFichaAnexoB.vue` sem saída. `BUILD` (Expected: `vite exit 0`, `built in`). Não há teste HTTP local que afirme o componente `PaeFichaAnexoB`; conferir a rota com `RUN Pae` (a suíte inteira continua `OK`) e, no navegador, se o MCP conectar: salvar uma ficha nova (rascunho), editar e salvar, abrir uma versão antiga (somente leitura, link "Abrir versão atual"), 400 px e modo escuro; senão registrar a pendência sem afirmar a verificação.

- [ ] **Step 3: Commit.**

```bash
git add SDC/resources/js/Pages/PaeFichaAnexoB.vue
git commit -m "♻️ refactor(pae): redesenha a ficha cadastral do Anexo B no padrão visual do sistema"
```

---

### Task 12: Tela de simulados, selo na listagem e situação no modal do CCPAE

**Files:**
- Create: `SDC/resources/js/utils/paeSimulado.js`
- Create: `SDC/resources/js/Composables/pae/usePaeSimuladoForm.js`
- Create: `SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoSituacaoPainel.vue`, `SimuladoExigibilidadePainel.vue`, `SimuladoEnvioTab.vue`, `SimuladoCriteriosTab.vue`, `SimuladoTemposTab.vue`, `SimuladoAlarmeTab.vue`, `SimuladoInformativosTab.vue`, `SimuladoHistoricoTab.vue`
- Modify (substituir a página mínima da Task 6): `SDC/resources/js/Pages/PaeSimulados.vue`
- Modify: `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`, `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`, `PaeProtocolosGrid.vue`, `EmitirCcpaeModal.vue`, `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`

**Interfaces:**
- Consumes: Task 6 (rotas `pae.protocolo.simulados.*` e props `protocolo`, `resumo`, `can_validar`, `can_view`; `simulado_situacao` por protocolo na listagem), Task 7 (`PaeTelaLayout`, `PaeAviso`, `FormFileField`, `FormField`/`FormSelect` com `aria-label`, `utils/paeTela.js`), Task 8 (breadcrumb `PaeSimulados`), Task 5 já integrada (a Task 5 e esta editam `EmitirCcpaeModal.vue`), `CollapsibleSection`, `Badge`, `Button`, `RadioGroup`, `ToggleField`, `FormDateField`, `FormTextarea`.
- Produces: `usePaeSimuladoForm(protocoloId, relatorioInicial)` → `{ form, indicios, atualizandoIndicios, erroIndicios, selecionadoId, carregar, atualizarIndicios, registrar, adicionarLinha, removerLinha, adicionarAno, removerAno }`; em `utils/paeSimulado.js`: `rotuloSituacaoSimulado`, `classeSituacaoSimulado`, `varianteSituacaoSimulado`, `simuladoPronto`, `MOTIVOS_DISPENSA`, `OPCOES_MOTIVO`, `OPCOES_NIVEL`, `CATEGORIAS_TEMPO`, `ROTULOS_INDICIO`, `descricaoIndicio`.
- Tela: cabeçalho (ícone, título, selo da situação anual, chip do protocolo); no topo a situação anual e a exigibilidade; abas **Envio, Critérios, Tempos, Alarme, Informativos, Histórico**. Modo somente leitura sem `can_validar`, em versão histórica aberta pelo Histórico ou em protocolo arquivado. Os indícios dos critérios vêm do servidor: do relatório aberto, ou da pré-visualização (`pae.protocolo.simulados.indicios`) enquanto o analista preenche; o cliente nunca calcula indício. Booleanos e a regra `validado` não são decididos no cliente: antes do envio o composable converte booleanos em `1`/`0` (`FormData` do Inertia não garante a forma de um booleano) e o servidor decide tudo.

- [ ] **Step 1: Utilitários e composable.**

`SDC/resources/js/utils/paeSimulado.js`:

```js
// Rotulos e metadados compartilhados pela fase F (simulados do Anexo C).
import { opcoes } from '@/utils/paeTela';

const ROTULOS_SITUACAO = {
  nao_avaliada: 'não avaliado',
  dispensado: 'dispensado',
  em_dia: 'em dia',
  nao_validado: 'não validado',
  vencido: 'vencido',
  pendente_emissao: 'pendente para emissão',
};

const CLASSES_TEXTO = {
  vencido: 'text-red-700 dark:text-red-300',
  nao_validado: 'text-red-700 dark:text-red-300',
  pendente_emissao: 'text-amber-700 dark:text-amber-300',
  nao_avaliada: 'text-amber-700 dark:text-amber-300',
};

const VARIANTES = {
  em_dia: 'success',
  dispensado: 'neutral',
  nao_validado: 'danger',
  vencido: 'danger',
  pendente_emissao: 'warning',
  nao_avaliada: 'warning',
};

export function rotuloSituacaoSimulado(situacao) {
  return ROTULOS_SITUACAO[situacao] ?? ROTULOS_SITUACAO.nao_avaliada;
}

export function classeSituacaoSimulado(situacao) {
  return CLASSES_TEXTO[situacao] ?? 'text-slate-600 dark:text-slate-300';
}

export function varianteSituacaoSimulado(situacao) {
  return VARIANTES[situacao] ?? 'default';
}

/** Situacoes em que a emissao do CCPAE nao esbarra no simulado. */
export function simuladoPronto(situacao) {
  return situacao === 'dispensado' || situacao === 'em_dia';
}

export const MOTIVOS_DISPENSA = {
  licenca_instalacao: 'PAE para Licença de Instalação (Art. 17)',
  metodo_alternativo: 'Método alternativo aprovado pela CEDEC (Art. 21)',
};
export const OPCOES_MOTIVO = opcoes(MOTIVOS_DISPENSA);

export const OPCOES_NIVEL = [
  { value: 2, label: 'Nível 2 (evacuação preventiva)' },
  { value: 3, label: 'Nível 3 (evacuação imediata)' },
];

// Categorias de tempo da secao 7 do Anexo C e o criterio do item 8.1 que elas alimentam.
export const CATEGORIAS_TEMPO = [
  { chave: 'sem_dificuldade', titulo: 'Sem dificuldade de locomoção (7.1.3)', criterio: 5, nome: 'Rota de fuga', comPopulacao: true, nivel: false },
  { chave: 'com_dificuldade', titulo: 'Com dificuldade de locomoção (7.2.4)', criterio: 6, nome: 'Grupo ou amostra', comPopulacao: true, nivel: false },
  { chave: 'ensino', titulo: 'Unidades de ensino (7.3.2)', criterio: null, nome: 'Unidade de ensino', comPopulacao: false, nivel: false },
  { chave: 'hospitalares_prisionais', titulo: 'Hospitalares e prisionais (7.4.2)', criterio: 7, nome: 'Unidade', comPopulacao: false, nivel: true },
  { chave: 'aglomeracao', titulo: 'Locais com aglomeração (7.5.2)', criterio: 8, nome: 'Edificação', comPopulacao: false, nivel: false },
];

export const ROTULOS_INDICIO = {
  saida_maior_igual_onda: 'saída maior ou igual à chegada da onda',
  houve_problemas: 'houve problemas na evacuação',
  ponto_invalido: 'ponto de encontro inválido',
  alarme_sem_morador: 'alarme não audível em todos os pontos, sem o morador indicado (nome e localização)',
};

export function descricaoIndicio(indicio) {
  const base = ROTULOS_INDICIO[indicio.codigo] ?? indicio.codigo;
  const categoria = CATEGORIAS_TEMPO.find((c) => c.chave === indicio.categoria);
  const onde = categoria ? ` · ${categoria.titulo}${indicio.nome ? `, ${indicio.nome}` : ''}` : '';
  return `${base}${onde}`;
}
```

`SDC/resources/js/Composables/pae/usePaeSimuladoForm.js`:

```js
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { CATEGORIAS_TEMPO } from '@/utils/paeSimulado';

const NUMEROS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const linhaVazia = () => ({ nome: '', populacao: '', chegada_onda: '', saida: '', houve_problemas: false, ponto_valido: true, estimativa: false, nivel_emergencia: 2 });

function estadoVazio() {
  return {
    dt_realizacao: '',
    nivel_emergencia: 2,
    dt_apresentacao: '',
    num_sei: '',
    observacao: '',
    integrado: false,
    barragens_integradas: '',
    aviso_cedec_em: '',
    criterios: Object.fromEntries(NUMEROS.map((n) => [n, { atende: null, justificativa: '' }])),
    tempos: Object.fromEntries(CATEGORIAS_TEMPO.map((c) => [c.chave, []])),
    alarme: { audivel_todos: null, morador_nome: '', morador_localizacao: '' },
    informativos: {
      participacao: { populacao_zas: '', participantes: '', cadastrados_pae: '', anos_anteriores: [] },
      ensino_observacoes: '',
      recursos_observacoes: '',
      conclusao_compdec: '',
    },
    arquivo: null,
    chave_idempotencia: crypto.randomUUID(),
  };
}

// Um relatorio ja registrado vira o ponto de partida de uma nova revisao (nova chave, PDF novo).
function estadoDoRelatorio(r) {
  const participacao = r.informativos?.participacao ?? {};
  return {
    ...estadoVazio(),
    dt_realizacao: r.dt_realizacao,
    nivel_emergencia: r.nivel_emergencia,
    dt_apresentacao: r.dt_apresentacao,
    num_sei: r.num_sei,
    observacao: r.observacao ?? '',
    integrado: r.integrado,
    barragens_integradas: r.barragens_integradas ?? '',
    aviso_cedec_em: r.aviso_cedec_em ?? '',
    criterios: Object.fromEntries(NUMEROS.map((n) => [n, { atende: r.criterios?.[n]?.atende ?? null, justificativa: r.criterios?.[n]?.justificativa ?? '' }])),
    tempos: Object.fromEntries(CATEGORIAS_TEMPO.map(({ chave }) => [chave, (r.tempos?.[chave] ?? []).map((l) => ({
      nome: l.nome,
      populacao: l.populacao ?? '',
      chegada_onda: l.chegada_onda_fmt,
      saida: l.saida_fmt,
      houve_problemas: l.houve_problemas,
      ponto_valido: l.ponto_valido,
      estimativa: l.estimativa,
      nivel_emergencia: l.nivel_emergencia ?? 2,
    }))])),
    alarme: {
      audivel_todos: r.alarme?.audivel_todos ?? null,
      morador_nome: r.alarme?.morador_nome ?? '',
      morador_localizacao: r.alarme?.morador_localizacao ?? '',
    },
    informativos: {
      participacao: {
        populacao_zas: participacao.populacao_zas ?? '',
        participantes: participacao.participantes ?? '',
        cadastrados_pae: participacao.cadastrados_pae ?? '',
        anos_anteriores: (participacao.anos_anteriores ?? []).map((a) => ({ ...a })),
      },
      ensino_observacoes: r.informativos?.ensino_observacoes ?? '',
      recursos_observacoes: r.informativos?.recursos_observacoes ?? '',
      conclusao_compdec: r.informativos?.conclusao_compdec ?? '',
    },
  };
}

// O FormData do Inertia nao garante a forma de um booleano; o Laravel so aceita true/false/1/0.
function paraEnvio(valor) {
  if (typeof valor === 'boolean') return valor ? 1 : 0;
  if (valor instanceof File) return valor;
  if (Array.isArray(valor)) return valor.map(paraEnvio);
  if (valor && typeof valor === 'object') return Object.fromEntries(Object.entries(valor).map(([chave, v]) => [chave, paraEnvio(v)]));
  return valor;
}

// Estado do relatorio em edicao. Validado e indicios sao do servidor; aqui so se pede a previa.
export function usePaeSimuladoForm(protocoloId, relatorioInicial) {
  const form = useForm(relatorioInicial ? estadoDoRelatorio(relatorioInicial) : estadoVazio());
  const indicios = ref(relatorioInicial?.indicios ?? {});
  const selecionadoId = ref(relatorioInicial?.id ?? null);
  const atualizandoIndicios = ref(false);
  const erroIndicios = ref('');

  /** `relatorio` nulo abre um formulario em branco (outro simulado). */
  function carregar(relatorio) {
    form.setData(relatorio ? estadoDoRelatorio(relatorio) : estadoVazio());
    form.clearErrors();
    indicios.value = relatorio?.indicios ?? {};
    selecionadoId.value = relatorio?.id ?? null;
    erroIndicios.value = '';
  }

  async function atualizarIndicios() {
    atualizandoIndicios.value = true;
    erroIndicios.value = '';
    try {
      const { data } = await axios.post(route('pae.protocolo.simulados.indicios', protocoloId), {
        tempos: form.tempos,
        alarme: form.alarme,
      });
      indicios.value = data;
    } catch (erro) {
      if (erro.response?.status !== 422) throw erro;
      erroIndicios.value = 'Corrija os tempos (mm:ss) e o alarme para calcular os indícios.';
    } finally {
      atualizandoIndicios.value = false;
    }
  }

  function registrar() {
    form.transform((dados) => paraEnvio(dados)).post(route('pae.protocolo.simulados.relatorios.store', protocoloId), {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        form.arquivo = null;
        form.chave_idempotencia = crypto.randomUUID();
      },
    });
  }

  const adicionarLinha = (categoria) => form.tempos[categoria].push(linhaVazia());
  const removerLinha = (categoria, indice) => form.tempos[categoria].splice(indice, 1);
  const adicionarAno = () => form.informativos.participacao.anos_anteriores.push({ ano: '', participantes: '' });
  const removerAno = (indice) => form.informativos.participacao.anos_anteriores.splice(indice, 1);

  return { form, indicios, atualizandoIndicios, erroIndicios, selecionadoId, carregar, atualizarIndicios, registrar, adicionarLinha, removerLinha, adicionarAno, removerAno };
}
```

- [ ] **Step 2: Organismos de situação e exigibilidade.**

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoSituacaoPainel.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-situacao" title="Situação anual" :subtitle="`Janela de 12 meses: de ${formatarData(resumo.janela_inicio)} até hoje`" :icon="CalendarDaysIcon" :tom="tom">
    <dl class="grid gap-3 text-sm sm:grid-cols-3">
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Situação</dt>
        <dd class="mt-1"><Badge :variant="varianteSituacaoSimulado(resumo.situacao)">{{ rotuloSituacaoSimulado(resumo.situacao) }}</Badge></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Próximo vencimento</dt>
        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ resumo.proximo_vencimento ? formatarData(resumo.proximo_vencimento) : '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Relatórios registrados</dt>
        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ resumo.relatorios.length }}</dd>
      </div>
    </dl>
    <PaeAviso v-if="resumo.alerta_legado" tom="aviso" class="mt-3">Este CCPAE é anterior ao registro de exigibilidade do simulado. A CEDEC deve avaliar o protocolo.</PaeAviso>
    <PaeAviso v-if="['vencido', 'nao_validado'].includes(resumo.situacao)" tom="erro" class="mt-3">Pendência para análise da CEDEC (reavaliação do PAE, Art. 101). O sistema não suspende nem revoga o CCPAE automaticamente.</PaeAviso>
    <PaeAviso v-if="resumo.situacao === 'pendente_emissao'" tom="aviso" class="mt-3">A emissão do CCPAE ficará bloqueada até haver relatório validado realizado nos 12 meses anteriores.</PaeAviso>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';
import { formatarData } from '@/utils/paeTela';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  resumo: { type: Object, required: true },
});

const tom = computed(() => {
  const variante = varianteSituacaoSimulado(props.resumo.situacao);
  return { success: 'success', danger: 'danger', warning: 'warning' }[variante] ?? 'neutro';
});
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoExigibilidadePainel.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-exigibilidade" title="Exigibilidade do simulado" subtitle="A avaliação mais recente da CEDEC prevalece (Arts. 17 e 21)." :icon="ShieldCheckIcon">
    <div v-if="resumo.avaliacao" class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">
      <p class="font-semibold text-slate-900 dark:text-white">
        {{ resumo.avaliacao.resultado === 'exigivel' ? 'Simulado exigível' : `Simulado dispensado: ${MOTIVOS_DISPENSA[resumo.avaliacao.motivo_dispensa]}` }}
      </p>
      <p class="mt-1 text-slate-700 dark:text-slate-200">{{ resumo.avaliacao.fundamentacao }}</p>
      <p class="mt-1 text-slate-500 dark:text-slate-400">SEI {{ resumo.avaliacao.num_sei }} · {{ resumo.avaliacao.decisor?.name || 'Responsável não disponível' }} · {{ formatarData(resumo.avaliacao.decidido_em) }}</p>
    </div>
    <PaeAviso v-else tom="aviso">Exigibilidade ainda não avaliada. A emissão de CCPAE ficará bloqueada.</PaeAviso>

    <form v-if="podeValidar" class="mt-4 space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700" @submit.prevent="avaliar">
      <h3 class="font-medium text-slate-900 dark:text-white">Registrar nova avaliação</h3>
      <FormSelect v-model="avaliacao.resultado" label="Resultado" :options="OPCOES_RESULTADO" placeholder="" :error="avaliacao.errors.resultado" required />
      <FormSelect v-if="avaliacao.resultado === 'dispensado'" v-model="avaliacao.motivo_dispensa" label="Motivo da dispensa" :options="OPCOES_MOTIVO" :error="avaliacao.errors.motivo_dispensa" required />
      <FormTextarea v-model="avaliacao.fundamentacao" label="Fundamentação" :rows="3" :error="avaliacao.errors.fundamentacao" required />
      <FormField v-model="avaliacao.num_sei" label="Número SEI" maxlength="100" :error="avaliacao.errors.num_sei" required />
      <p v-if="errosSemCampo(avaliacao.errors, CAMPOS)" class="text-sm text-red-600 dark:text-red-300">{{ errosSemCampo(avaliacao.errors, CAMPOS) }}</p>
      <Button type="submit" :loading="avaliacao.processing">Registrar avaliação</Button>
    </form>

    <h3 class="mt-6 font-medium text-slate-900 dark:text-white">Histórico de avaliações</h3>
    <p v-if="!resumo.avaliacoes.length" class="mt-2 text-sm text-slate-500">Nenhuma avaliação registrada.</p>
    <ol v-else class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
      <li v-for="item in resumo.avaliacoes" :key="item.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <strong>{{ item.resultado === 'exigivel' ? 'Exigível' : `Dispensado (${MOTIVOS_DISPENSA[item.motivo_dispensa]})` }}</strong> · SEI {{ item.num_sei }} · {{ formatarData(item.decidido_em) }}
        <p class="mt-1">{{ item.fundamentacao }}</p>
      </li>
    </ol>
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { MOTIVOS_DISPENSA, OPCOES_MOTIVO } from '@/utils/paeSimulado';
import { errosSemCampo, formatarData } from '@/utils/paeTela';
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  resumo: { type: Object, required: true },
  protocoloId: { type: Number, required: true },
  podeValidar: { type: Boolean, default: false },
});

const CAMPOS = ['resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei'];
const OPCOES_RESULTADO = [{ value: 'exigivel', label: 'Exigível' }, { value: 'dispensado', label: 'Dispensado' }];

const avaliacao = useForm({ resultado: 'exigivel', motivo_dispensa: '', fundamentacao: '', num_sei: '', chave_idempotencia: crypto.randomUUID() });

function avaliar() {
  avaliacao.transform((dados) => ({ ...dados, motivo_dispensa: dados.resultado === 'dispensado' ? dados.motivo_dispensa : null }))
    .post(route('pae.protocolo.simulados.avaliar', props.protocoloId), {
      preserveScroll: true,
      onSuccess: () => { avaliacao.reset('motivo_dispensa', 'fundamentacao', 'num_sei'); avaliacao.chave_idempotencia = crypto.randomUUID(); },
    });
}
</script>
```

- [ ] **Step 3: Organismos das abas.**

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoEnvioTab.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-envio" title="Envio do relatório" subtitle="Datas, nível de emergência e PDF assinado do Anexo C (a CEDEC não confere assinaturas)." :icon="DocumentArrowUpIcon">
    <div class="grid gap-4 sm:grid-cols-2">
      <FormDateField v-model="form.dt_realizacao" label="Data de realização do simulado" :disabled="somenteLeitura" :error="form.errors.dt_realizacao" required />
      <FormSelect v-model="form.nivel_emergencia" label="Nível de emergência simulado" :options="OPCOES_NIVEL" placeholder="" :disabled="somenteLeitura" :error="form.errors.nivel_emergencia" required />
      <FormDateField v-model="form.dt_apresentacao" label="Apresentado à CEDEC em" :disabled="somenteLeitura" :error="form.errors.dt_apresentacao" required />
      <FormField v-model="form.num_sei" label="Número SEI" maxlength="100" :disabled="somenteLeitura" :error="form.errors.num_sei" required />
      <FormDateField v-model="form.aviso_cedec_em" label="Aviso prévio à CEDEC em (Art. 94)" hint="Antecedência mínima de 7 dias; menos que isso gera alerta informativo." :disabled="somenteLeitura" :error="form.errors.aviso_cedec_em" />
      <FormFileField v-if="!somenteLeitura" v-model="form.arquivo" label="Relatório em PDF (até 20 MiB)" :error="form.errors.arquivo" required />
      <div v-else class="text-sm text-slate-700 dark:text-slate-200">
        <p class="font-medium">Arquivo</p>
        <a v-if="selecionado && canView" :href="route('pae.protocolo.simulados.relatorios.download', [protocoloId, selecionado.id])" class="mt-1 inline-block font-semibold text-blue-700 underline dark:text-blue-300">{{ selecionado.arquivo_nome_original }}</a>
        <span v-else class="text-slate-500">—</span>
      </div>
      <ToggleField v-model="form.integrado" label="Simulado integrado (Art. 102)" description="Barragens que compartilham a mesma ZAS; cada protocolo registra o seu relatório." :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" />
      <FormTextarea v-if="form.integrado" v-model="form.barragens_integradas" label="Barragens integradas" :rows="2" :disabled="somenteLeitura" :error="form.errors.barragens_integradas" required />
      <FormTextarea v-model="form.observacao" class="sm:col-span-2" label="Observação" :rows="2" :disabled="somenteLeitura" :error="form.errors.observacao" />
    </div>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormFileField from '@/Components/Molecules/Form/FormFileField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import { OPCOES_NIVEL } from '@/utils/paeSimulado';
import { DocumentArrowUpIcon } from '@heroicons/vue/24/outline';

defineProps({
  form: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
  selecionado: { type: Object, default: null },
  protocoloId: { type: Number, required: true },
  canView: { type: Boolean, default: false },
});
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoCriteriosTab.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-criterios" title="Critérios do item 8.1" subtitle="O analista decide cada critério; os indícios do sistema apoiam, não reprovam sozinhos. Validado somente com os 8 critérios reprováveis atendidos." :icon="ClipboardDocumentCheckIcon">
    <div class="mb-3 flex flex-wrap items-center gap-3">
      <Button v-if="!somenteLeitura" variant="outline" size="sm" :loading="atualizando" @click="$emit('atualizar-indicios')">Atualizar indícios</Button>
      <span v-if="erroIndicios" class="text-sm text-red-600 dark:text-red-300">{{ erroIndicios }}</span>
    </div>
    <div class="space-y-4">
      <article v-for="item in catalogo" :key="item.numero" class="grid gap-4 rounded-lg border border-slate-200 p-4 dark:border-slate-700 lg:grid-cols-2">
        <div class="space-y-3">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ item.numero }}. {{ item.indice }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.criterio }}</p>
            <Badge v-if="!item.reprovavel" variant="neutral" size="sm" class="mt-2">Informativo (Art. 100): não reprova</Badge>
          </div>
          <RadioGroup v-model="criterios[item.numero].atende" :name="`criterio-${item.numero}`" :options="OPCOES_ATENDE" :disabled="somenteLeitura" :error="erros[`criterios.${item.numero}.atende`]" />
          <FormTextarea v-if="criterios[item.numero].atende === false" v-model="criterios[item.numero].justificativa" :label="item.reprovavel ? 'Justificativa (obrigatória)' : 'Justificativa'" :rows="2" :disabled="somenteLeitura" :error="erros[`criterios.${item.numero}.justificativa`]" />
        </div>
        <div>
          <h4 class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Indícios do sistema</h4>
          <p v-if="!(indicios[item.numero] ?? []).length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhum indício.</p>
          <ul v-else class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
            <li v-for="(indicio, posicao) in indicios[item.numero]" :key="posicao">{{ descricaoIndicio(indicio) }}</li>
          </ul>
        </div>
      </article>
    </div>
    <p v-if="erros.criterios" class="mt-3 text-sm text-red-600 dark:text-red-300">{{ erros.criterios }}</p>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import { descricaoIndicio } from '@/utils/paeSimulado';
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';

defineProps({
  criterios: { type: Object, required: true },
  catalogo: { type: Array, required: true },
  indicios: { type: Object, default: () => ({}) },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
  atualizando: { type: Boolean, default: false },
  erroIndicios: { type: String, default: '' },
});

defineEmits(['atualizar-indicios']);

const OPCOES_ATENDE = [{ value: true, label: 'Atende' }, { value: false, label: 'Não atende' }];
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoTemposTab.vue`:

```vue
<template>
  <div class="space-y-4">
    <CollapsibleSection v-for="categoria in CATEGORIAS_TEMPO" :key="categoria.chave" namespace="pae" :section-id="`simulado-tempos-${categoria.chave}`" :title="categoria.titulo" :subtitle="categoria.criterio ? `Alimenta os indícios do critério ${categoria.criterio}.` : 'Informativo: sem critério no item 8.1.'" :icon="ClockIcon">
      <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
        <Button variant="outline" size="sm" @click="$emit('adicionar', categoria.chave)">Adicionar linha</Button>
      </div>
      <p v-if="!tempos[categoria.chave].length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma linha informada.</p>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
            <tr>
              <th class="px-2 py-2">{{ categoria.nome }}</th>
              <th v-if="categoria.comPopulacao" class="px-2 py-2">População</th>
              <th class="px-2 py-2">Chegada da onda (mm:ss)</th><th class="px-2 py-2">Saída (mm:ss)</th>
              <th class="px-2 py-2">Houve problemas</th><th class="px-2 py-2">Ponto válido</th><th class="px-2 py-2">Estimativa</th>
              <th v-if="categoria.nivel" class="px-2 py-2">Nível indicado</th><th class="px-2 py-2"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(linha, i) in tempos[categoria.chave]" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
              <td class="min-w-[10rem] px-2 py-2"><FormField v-model="linha.nome" size="sm" maxlength="255" :aria-label="`${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.nome`]" /></td>
              <td v-if="categoria.comPopulacao" class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.populacao" type="number" step="1" size="sm" :aria-label="`População de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.populacao`]" /></td>
              <td class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.chegada_onda" size="sm" placeholder="15:00" maxlength="6" :aria-label="`Chegada da onda de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.chegada_onda`]" /></td>
              <td class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.saida" size="sm" placeholder="12:30" maxlength="6" :aria-label="`Saída de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.saida`]" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.houve_problemas" :aria-label="`Houve problemas em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.ponto_valido" :aria-label="`Ponto de encontro válido em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.estimativa" :aria-label="`Estimativa em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td v-if="categoria.nivel" class="min-w-[6rem] px-2 py-2"><FormSelect v-model="linha.nivel_emergencia" size="sm" placeholder="" :options="OPCOES_NIVEL_LINHA" :aria-label="`Nível indicado de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.nivel_emergencia`]" /></td>
              <td class="px-2 py-2"><Button v-if="!somenteLeitura" variant="danger" size="sm" @click="$emit('remover', categoria.chave, i)">Remover</Button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="erros[`tempos.${categoria.chave}`]" class="mt-2 text-sm text-red-600 dark:text-red-300">{{ erros[`tempos.${categoria.chave}`] }}</p>
    </CollapsibleSection>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { CATEGORIAS_TEMPO } from '@/utils/paeSimulado';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
  tempos: { type: Object, required: true },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_NIVEL_LINHA = [{ value: 2, label: '2' }, { value: 3, label: '3' }];
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoAlarmeTab.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-alarme" title="Sistema de alarme (critério 2)" subtitle="Se o som não foi audível em todos os pontos da ZAS, indicar o morador que informou (nome e localização)." :icon="SpeakerWaveIcon">
    <div class="space-y-4">
      <RadioGroup v-model="alarme.audivel_todos" name="alarme-audivel" label="O som das sirenes foi audível em todos os pontos da ZAS?" :options="OPCOES" :disabled="somenteLeitura" :error="erros['alarme.audivel_todos']" />
      <div v-if="alarme.audivel_todos === false" class="grid gap-4 sm:grid-cols-2">
        <FormField v-model="alarme.morador_nome" label="Morador que informou não ouvir (nome)" maxlength="255" :disabled="somenteLeitura" :error="erros['alarme.morador_nome']" />
        <FormField v-model="alarme.morador_localizacao" label="Localização do morador" maxlength="500" :disabled="somenteLeitura" :error="erros['alarme.morador_localizacao']" />
      </div>
      <ul v-if="(indicios[2] ?? []).length" class="list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
        <li v-for="(indicio, posicao) in indicios[2]" :key="posicao">{{ descricaoIndicio(indicio) }}</li>
      </ul>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import { descricaoIndicio } from '@/utils/paeSimulado';
import { SpeakerWaveIcon } from '@heroicons/vue/24/outline';

defineProps({
  alarme: { type: Object, required: true },
  indicios: { type: Object, default: () => ({}) },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

const OPCOES = [{ value: true, label: 'Sim, em todos os pontos' }, { value: false, label: 'Não' }];
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoInformativosTab.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-informativos" title="Informativos (Art. 100)" subtitle="Participação, ensino e recursos são registrados, mas nunca reprovam o simulado." :icon="InformationCircleIcon">
    <div class="space-y-5">
      <div class="grid gap-4 sm:grid-cols-3">
        <FormField v-model="informativos.participacao.populacao_zas" type="number" step="1" label="População da ZAS" :disabled="somenteLeitura" :error="erros['informativos.participacao.populacao_zas']" />
        <FormField v-model="informativos.participacao.participantes" type="number" step="1" label="Participantes do simulado" :disabled="somenteLeitura" :error="erros['informativos.participacao.participantes']" />
        <FormField v-model="informativos.participacao.cadastrados_pae" type="number" step="1" label="Cadastrados no PAE" :disabled="somenteLeitura" :error="erros['informativos.participacao.cadastrados_pae']" />
      </div>

      <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-medium text-slate-900 dark:text-white">Participação em anos anteriores</h3>
          <Button v-if="!somenteLeitura" variant="outline" size="sm" @click="$emit('adicionar-ano')">Adicionar ano</Button>
        </div>
        <p v-if="!informativos.participacao.anos_anteriores.length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhum ano informado.</p>
        <div v-for="(item, i) in informativos.participacao.anos_anteriores" :key="i" class="mt-2 grid items-start gap-3 sm:grid-cols-[8rem_1fr_auto]">
          <FormField v-model="item.ano" type="number" step="1" size="sm" :aria-label="`Ano anterior ${i + 1}`" :disabled="somenteLeitura" :error="erros[`informativos.participacao.anos_anteriores.${i}.ano`]" />
          <FormField v-model="item.participantes" type="number" step="1" size="sm" :aria-label="`Participantes do ano anterior ${i + 1}`" :disabled="somenteLeitura" :error="erros[`informativos.participacao.anos_anteriores.${i}.participantes`]" />
          <Button v-if="!somenteLeitura" variant="danger" size="sm" @click="$emit('remover-ano', i)">Remover</Button>
        </div>
      </div>

      <FormTextarea v-model="informativos.ensino_observacoes" label="Observações sobre unidades de ensino (objetivo VII)" :rows="2" :disabled="somenteLeitura" :error="erros['informativos.ensino_observacoes']" />
      <FormTextarea v-model="informativos.recursos_observacoes" label="Observações sobre recursos humanos, materiais e logísticos (objetivo VIII)" :rows="2" :disabled="somenteLeitura" :error="erros['informativos.recursos_observacoes']" />
      <FormSelect v-model="informativos.conclusao_compdec" label="Conclusão declarada pela COMPDEC" :options="OPCOES_CONCLUSAO" placeholder="Não informada" :disabled="somenteLeitura" :error="erros['informativos.conclusao_compdec']" />
    </div>
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
  informativos: { type: Object, required: true },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar-ano', 'remover-ano']);

const OPCOES_CONCLUSAO = [{ value: 'sim', label: 'Sim, o exercício atingiu os critérios' }, { value: 'nao', label: 'Não' }];
</script>
```

`SDC/resources/js/Components/Organisms/Pae/Simulados/SimuladoHistoricoTab.vue`:

```vue
<template>
  <CollapsibleSection namespace="pae" section-id="simulado-historico" title="Relatórios e versões" :subtitle="`${relatorios.length} registro(s); a maior versão de cada simulado prevalece.`" :icon="ClockIcon" tom="neutro">
    <div v-if="podeValidar" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('novo')">Novo simulado</Button>
    </div>
    <p v-if="!relatorios.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhum relatório registrado.</p>
    <ul v-else class="space-y-3">
      <li v-for="item in relatorios" :key="item.id" class="rounded-lg border p-3 text-sm" :class="item.id === selecionadoId ? 'border-blue-500' : 'border-slate-200 dark:border-slate-700'">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <strong class="text-slate-900 dark:text-white">Simulado de {{ formatarData(item.dt_realizacao) }} · versão {{ item.versao }}</strong>
          <span class="flex flex-wrap items-center gap-2">
            <Badge v-if="item.vigente" variant="info" size="sm">Vigente</Badge>
            <Badge :variant="item.validado ? 'success' : 'danger'" size="sm">{{ item.validado ? 'Validado' : 'Não validado' }}</Badge>
          </span>
        </div>
        <p class="mt-1 text-slate-600 dark:text-slate-300">Nível {{ item.nivel_emergencia }} · apresentado {{ formatarData(item.dt_apresentacao) }} · SEI {{ item.num_sei }} · {{ item.registrador || 'Responsável não disponível' }}</p>
        <p v-if="item.integrado" class="mt-1 text-slate-600 dark:text-slate-300">Simulado integrado: {{ item.barragens_integradas }}</p>
        <p v-if="item.alerta_aviso" class="mt-1 text-amber-700 dark:text-amber-300">Aviso à CEDEC com {{ item.aviso_antecedencia_dias }} dia(s) de antecedência (mínimo de {{ minimoDias }}, Art. 94).</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <Button variant="outline" size="sm" @click="$emit('abrir', item)">{{ item.id === selecionadoId ? 'Aberto' : (item.vigente && podeValidar ? 'Abrir para revisar' : 'Abrir') }}</Button>
          <a v-if="canView" :href="route('pae.protocolo.simulados.relatorios.download', [protocoloId, item.id])" class="font-semibold text-blue-700 underline dark:text-blue-300">Baixar PDF</a>
        </div>
      </li>
    </ul>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { formatarData } from '@/utils/paeTela';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
  relatorios: { type: Array, required: true },
  selecionadoId: { type: Number, default: null },
  protocoloId: { type: Number, required: true },
  podeValidar: { type: Boolean, default: false },
  canView: { type: Boolean, default: false },
  minimoDias: { type: Number, default: 7 },
});

defineEmits(['abrir', 'novo']);
</script>
```

- [ ] **Step 4: Página.** Substituir `SDC/resources/js/Pages/PaeSimulados.vue`:

```vue
<template>
  <Head :title="`Simulados - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Simulados do Anexo C"
    subtitulo="Relatório anual do exercício simulado · Resolução GMG nº 83/2024"
    :icone="BellAlertIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="varianteSituacaoSimulado(resumo.situacao)"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: histórico disponível somente para leitura.</PaeAviso>
      <PaeAviso v-if="selecionado && !selecionado.vigente" tom="neutro">Consultando a versão {{ selecionado.versao }} do simulado de {{ formatarData(selecionado.dt_realizacao) }}, somente leitura. Abra a versão vigente para revisar.</PaeAviso>
    </template>

    <template #topo>
      <SimuladoSituacaoPainel :resumo="resumo" />
      <SimuladoExigibilidadePainel :resumo="resumo" :protocolo-id="protocolo.id" :pode-validar="can_validar" />
    </template>

    <template #default="{ aba: ativa }">
      <PaeAviso v-if="!formularioDisponivel && ativa !== 'historico'" tom="aviso">O relatório só pode ser registrado depois de a CEDEC avaliar o simulado como exigível.</PaeAviso>
      <SimuladoEnvioTab v-else-if="ativa === 'envio'" :form="form" :somente-leitura="somenteLeitura" :selecionado="selecionado" :protocolo-id="protocolo.id" :can-view="can_view" />
      <SimuladoCriteriosTab v-else-if="ativa === 'criterios'" :criterios="form.criterios" :catalogo="resumo.catalogo" :indicios="indicios" :erros="form.errors" :somente-leitura="somenteLeitura" :atualizando="atualizandoIndicios" :erro-indicios="erroIndicios" @atualizar-indicios="atualizarIndicios" />
      <SimuladoTemposTab v-else-if="ativa === 'tempos'" :tempos="form.tempos" :erros="form.errors" :somente-leitura="somenteLeitura" @adicionar="adicionarLinha" @remover="removerLinha" />
      <SimuladoAlarmeTab v-else-if="ativa === 'alarme'" :alarme="form.alarme" :indicios="indicios" :erros="form.errors" :somente-leitura="somenteLeitura" />
      <SimuladoInformativosTab v-else-if="ativa === 'informativos'" :informativos="form.informativos" :erros="form.errors" :somente-leitura="somenteLeitura" @adicionar-ano="adicionarAno" @remover-ano="removerAno" />
      <SimuladoHistoricoTab v-else :relatorios="resumo.relatorios" :selecionado-id="selecionadoId" :protocolo-id="protocolo.id" :pode-validar="can_validar" :can-view="can_view" @abrir="abrir" @novo="carregar(null)" />
    </template>

    <template #rodape>
      <PaeAviso v-if="mensagemGeral" tom="erro">{{ mensagemGeral }}</PaeAviso>
      <div v-if="formularioDisponivel && !somenteLeitura" class="flex flex-wrap justify-end gap-3">
        <Button :loading="form.processing" :disabled="!form.arquivo || !form.num_sei" @click="registrar">Registrar relatório</Button>
      </div>
      <CollapsibleSection v-if="resumo.ccpae" namespace="pae" section-id="simulado-ccpae" title="Evidência usada no CCPAE" :icon="ShieldCheckIcon" tom="neutro">
        <p class="text-sm text-slate-700 dark:text-slate-300">{{ resumo.ccpae.codigo }} · avaliação #{{ resumo.ccpae.simulado_avaliacao_id || 'legada, sem referência' }} · relatório #{{ resumo.ccpae.simulado_relatorio_id || 'não exigido ou legado' }}</p>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import SimuladoAlarmeTab from '@/Components/Organisms/Pae/Simulados/SimuladoAlarmeTab.vue';
import SimuladoCriteriosTab from '@/Components/Organisms/Pae/Simulados/SimuladoCriteriosTab.vue';
import SimuladoEnvioTab from '@/Components/Organisms/Pae/Simulados/SimuladoEnvioTab.vue';
import SimuladoExigibilidadePainel from '@/Components/Organisms/Pae/Simulados/SimuladoExigibilidadePainel.vue';
import SimuladoHistoricoTab from '@/Components/Organisms/Pae/Simulados/SimuladoHistoricoTab.vue';
import SimuladoInformativosTab from '@/Components/Organisms/Pae/Simulados/SimuladoInformativosTab.vue';
import SimuladoSituacaoPainel from '@/Components/Organisms/Pae/Simulados/SimuladoSituacaoPainel.vue';
import SimuladoTemposTab from '@/Components/Organisms/Pae/Simulados/SimuladoTemposTab.vue';
import { usePaeSimuladoForm } from '@/Composables/pae/usePaeSimuladoForm';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';
import { errosSemCampo, formatarData } from '@/utils/paeTela';
import { BellAlertIcon, ClipboardDocumentCheckIcon, ClockIcon, DocumentArrowUpIcon, InformationCircleIcon, ShieldCheckIcon, SpeakerWaveIcon } from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});

const vigente = () => props.resumo.relatorios.find((r) => r.vigente) ?? null;

const { form, indicios, atualizandoIndicios, erroIndicios, selecionadoId, carregar, atualizarIndicios, registrar, adicionarLinha, removerLinha, adicionarAno, removerAno } = usePaeSimuladoForm(props.protocolo.id, vigente());

const aba = ref('envio');
const abas = computed(() => [
  { id: 'envio', label: 'Envio', icon: DocumentArrowUpIcon },
  { id: 'criterios', label: 'Critérios', icon: ClipboardDocumentCheckIcon },
  { id: 'tempos', label: 'Tempos', icon: ClockIcon },
  { id: 'alarme', label: 'Alarme', icon: SpeakerWaveIcon },
  { id: 'informativos', label: 'Informativos', icon: InformationCircleIcon },
  { id: 'historico', label: 'Histórico', icon: ShieldCheckIcon, badge: props.resumo.relatorios.length || null },
]);

const selecionado = computed(() => props.resumo.relatorios.find((r) => r.id === selecionadoId.value) ?? null);
const formularioDisponivel = computed(() => props.resumo.avaliacao?.resultado === 'exigivel');
const somenteLeitura = computed(() => !props.can_validar || (selecionado.value !== null && !selecionado.value.vigente));
const seloRotulo = computed(() => {
  const rotulo = rotuloSituacaoSimulado(props.resumo.situacao);
  return rotulo.charAt(0).toUpperCase() + rotulo.slice(1);
});
const CAMPOS_COM_LUGAR = ['dt_realizacao', 'nivel_emergencia', 'dt_apresentacao', 'num_sei', 'aviso_cedec_em', 'arquivo', 'integrado', 'barragens_integradas', 'observacao', 'criterios', 'tempos', 'alarme', 'informativos'];
const mensagemGeral = computed(() => {
  const semCampo = errosSemCampo(form.errors, CAMPOS_COM_LUGAR).trim();
  const aninhados = Object.entries(form.errors).filter(([campo]) => /^(criterios|tempos|alarme|informativos)\./.test(campo)).length;
  return [semCampo, aninhados ? `Há ${aninhados} campo(s) a corrigir nas abas.` : ''].filter(Boolean).join(' ');
});

function abrir(relatorio) {
  carregar(relatorio);
  aba.value = 'envio';
}

// Depois de registrar, a versao nova passa a ser a vigente: o formulario recarrega dela (nova chave, PDF novo).
watch(() => props.resumo.relatorios[0]?.id, () => carregar(vigente()));
</script>
```

Nota: o `watch` usa o primeiro item da lista (ordenada por realização e versão decrescentes), que muda quando um relatório novo é registrado; trocar a aba para `envio` depois do registro fica por conta do usuário.

- [ ] **Step 5: Selo na listagem e situação no modal.**

`SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`:
- depois do bloco `v-if="protocolo.evacuacaoSituacao"`:

```vue
      <div v-if="protocolo.simuladoSituacao" class="text-sm font-medium" :class="classeSituacaoSimulado(protocolo.simuladoSituacao)">
        Simulado: {{ rotuloSituacaoSimulado(protocolo.simuladoSituacao) }}
      </div>
```

- no array de ações, depois da linha `label: 'Evacuação'`:

```js
          { action: 'ficha',  placement: 'menu', aliasOverride: 'view', label: 'Simulados', handler: () => $emit('simulado', protocolo.id) },
```

- acrescentar `'simulado'` ao `defineEmits` (depois de `'evacuacao'`) e, no `<script setup>`, `import { classeSituacaoSimulado, rotuloSituacaoSimulado } from '@/utils/paeSimulado';`.

`SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`: as mesmas três mudanças, com o selo em `mt-1 text-xs font-medium` depois do selo da evacuação.

`SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue`: `@simulado="$emit('simulado', $event)"` depois de `@evacuacao=...` e `'simulado'` no `defineEmits`.

`SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`:
- `@simulado="handleSimulado"` depois de cada `@evacuacao="handleEvacuacao"` (grade e tabela);
- em `mapProtocolo`, depois de `evacuacaoSituacao`: `simuladoSituacao: p.simulado_situacao ?? null,`;
- depois de `handleEvacuacao`:

```js
function handleSimulado(id) {
  router.visit(route('pae.protocolo.simulados.show', id));
}
```

`SDC/resources/js/Components/Organisms/Pae/Protocolos/EmitirCcpaeModal.vue`: depois da linha `Evacuação: ...`:

```vue
        <p class="text-sm text-slate-700 dark:text-slate-200">Simulado: {{ rotuloSituacaoSimulado(protocolo?.simuladoSituacao) }}.</p>
        <a v-if="protocolo" :href="route('pae.protocolo.simulados.show', protocolo.id)" class="inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Conferir exigibilidade e relatórios</a>
        <p v-if="protocolo && !simuladoPronto(protocolo.simuladoSituacao)" class="text-sm text-amber-700 dark:text-amber-300">Sem simulado dispensado ou relatório validado vigente hoje. A emissão será conferida no servidor pela data informada.</p>
```

e `import { rotuloSituacaoSimulado, simuladoPronto } from '@/utils/paeSimulado';`.

- [ ] **Step 6: Verificar.** `grep -rn "<input\|<select\|<textarea" SDC/resources/js/Pages/PaeSimulados.vue SDC/resources/js/Components/Organisms/Pae/Simulados` sem saída (nenhum campo solto). `BUILD`. Expected: `vite exit 0`, `built in` e contagem de `PaeSimulados` maior que 0. `RUN PaeSimuladoHttpTest` (Expected: `OK (9 tests, ...)`; o componente `PaeSimulados` e as props não mudaram). No navegador, se o MCP conectar, com um protocolo de teste: avaliar como exigível; na aba Envio, anexar um PDF; em Critérios, marcar os 8 reprováveis como "atende" e ver o relatório validado depois de registrar; refazer com um "não atende" sem justificativa (erro no campo) e com tempos de saída igual à chegada (indício aparece em Critérios depois de "Atualizar indícios"); abrir uma versão antiga (somente leitura); usuário só com `view` (sem formulários); 400 px; modo escuro (conferir o contraste do `RadioInput` no modo claro, que é um átomo existente); selo "Simulado" e ação "Simulados" na listagem; linha de simulado no modal de emissão. Se não conectar, registrar a pendência sem afirmar a verificação.

- [ ] **Step 7: Commit.**

```bash
git add SDC/resources/js/utils/paeSimulado.js SDC/resources/js/Composables/pae/usePaeSimuladoForm.js SDC/resources/js/Components/Organisms/Pae/Simulados SDC/resources/js/Pages/PaeSimulados.vue SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue SDC/resources/js/Components/Organisms/Pae/Protocolos SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue
git commit -m "✨ feat(pae): tela de simulados e selo na listagem"
```

---

### Task 13: Verificação final, revisão e integração

**Files:** todos os anteriores; nenhum teste no commit.

- [ ] **Step 1: Suíte e lint do PHP.** `RUN Pae`. Expected: `OK` com todos os testes PAE (BASE da Task 1, Step 2, mais 10 + 3 + 17 + 9 + 9 testes novos; os testes de DCO e evacuação locais incluídos) e os 15 testes unitários do motor e da janela dentro do mesmo filtro. Lint, rotas e migration no container (em PowerShell, na raiz da worktree):

```powershell
Set-Location C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\pae-simulados; $sdc=(Resolve-Path SDC).Path; $m=@(); foreach($f in 'app','config','database','routes','tests','resources'){ $m+=@('--mount',"type=bind,source=$sdc\$f,target=/var/www/$f,readonly") }; $m+=@('--mount',"type=bind,source=$sdc\bootstrap,target=/var/www/bootstrap"); docker run --rm --network newsdc-dev_default --env-file "$sdc\.env" -e APP_ENV=testing -e DB_HOST=newsdc_dev_db -e DB_DATABASE=pae_d_test -e DB_CONNECTION=pgsql --entrypoint sh @m newsdc-pae-d-test-runtime:local -c 'cd /var/www; composer dump-autoload -o --no-scripts -q; for f in $(find app/Modules/Pae -name "*Simulado*.php") app/Modules/Pae/Support/Simulado/*.php app/Modules/Pae/Support/Pae*.php app/Modules/Pae/Support/Evacuacao/TempoAnexoE.php app/Modules/Pae/Services/PaeDcoService.php app/Modules/Pae/Services/PaeEvacuacaoService.php app/Modules/Pae/Services/PaeCcpaeService.php app/Modules/Pae/Controllers/PaeDcoController.php app/Modules/Pae/Controllers/PaeProtocoloController.php app/Modules/Pae/Requests/SimularEvacuacaoRequest.php app/Modules/Pae/Models/PaeProtocolo.php app/Modules/Pae/Models/PaeCcpae.php app/Modules/Pae/PaeServiceProvider.php routes/modules/pae.php database/migrations/2026_10_09_120000_create_pae_simulado_registros.php; do php -l $f; done; php artisan route:list --path=simulados; php artisan migrate:rollback --step=1 --force; php artisan migrate --force'
```

Expected: `No syntax errors` em todos; 5 rotas `simulados` (`show`, `avaliar`, `relatorios.store`, `indicios`, `relatorios.download`); o rollback reverte `2026_10_09_120000_create_pae_simulado_registros` (conferir o nome na saída: o banco `pae_d_test` é compartilhado entre worktrees e `--step=1` desfaz a última migration aplicada) e o `migrate` seguinte a aplica de novo sem erro.

- [ ] **Step 2: Build do front.** `BUILD`. Expected: `vite exit 0`, `built in`, contagem de `PaeSimulados` maior que 0 e nenhum `error` na saída.

- [ ] **Step 3: Navegador.** Se o MCP do Playwright conectar, no homolog ou num app de prévia com esta branch, percorrer o roteiro de cada tarefa visual (Tasks 8 a 12): breadcrumb `Início › PAE › Protocolo … › <tela>` e Voltar para a listagem; DCO (abas, envio de PDF, download); evacuação (campos numéricos vazios sem alerta de mínimo do navegador, simulação do exemplo do item 3.3, versão antiga somente leitura); ficha (rascunho, salvar, versão antiga); simulados (exigibilidade, relatório validado e não validado, indícios, versão histórica, selo e menu na listagem, linha no modal do CCPAE e o erro `simulado` ao emitir sem relatório); usuário só com `view`; 400 px; modo escuro. Se não conectar, registrar cada pendência sem afirmar a verificação.

- [ ] **Step 4: Revisão do Git.** `git diff --check`; `git status --short`; `git diff --cached --name-only` sem nenhum arquivo `tests/`, `.superpowers/` ou `.env`; nenhum log de depuração nem emoji no código:

```bash
grep -rnE "dd\(|dump\(|console\.log" SDC/app/Modules/Pae SDC/resources/js/Pages/PaeSimulados.vue SDC/resources/js/Pages/PaeDco.vue SDC/resources/js/Pages/PaeEvacuacao.vue SDC/resources/js/Pages/PaeFichaAnexoB.vue SDC/resources/js/Components/Organisms/Pae SDC/resources/js/Composables/pae SDC/resources/js/utils/paeSimulado.js SDC/resources/js/utils/paeTela.js
grep -rnP "[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]" SDC/app/Modules/Pae SDC/resources/js/Pages/PaeSimulados.vue SDC/resources/js/Components/Organisms/Pae/Simulados SDC/resources/js/Templates/Pae/PaeTelaLayout.vue
git log --format=%B origin/dev..HEAD | grep -c "Co-Authored-By"
git log --oneline origin/dev..HEAD
```

Expected: as duas primeiras buscas sem saída, a contagem de `Co-Authored-By` igual a `0` e uma lista de commits que começam por gitmoji, um por tarefa, mais o commit documental.

- [ ] **Step 5: Integração.** Somente depois de tudo verificado e **com autorização do usuário**: merge `--no-ff` em `dev` pela worktree `.worktrees/merge-dev-tmp` (preservando a modificação RAT de outra frente), push, imagem completa `docker build -f docker/swoole/Dockerfile` numa worktree limpa do `dev` e recriação das 4 réplicas do homolog com `HA_APP_IMAGE`, conferindo saúde, a migration `2026_10_09_120000` e as rotas `simulados` em cada porta. A emissão de CCPAE em homolog passa a exigir exigibilidade do simulado: avisar a CEDEC de que protocolos já aprovados precisam de avaliação (exigível ou dispensado) antes de emitir, e que CCPAE anterior à fase permanece com referências nulas (alerta legado na tela).


---

## Autorrevisão contra a especificação

### Cobertura

| Ponto do spec | Onde |
| --- | --- |
| Motor `SimuladoAnexoC`: catálogo literal, `validado`, indícios, tolerância 1e-6, justificativa | Task 2 (`SimuladoAnexoCTest`, 10 testes) |
| `PaeSimuladoJanela`: 12 meses, extremos inclusivos, bissexto | Task 2 (`PaeSimuladoJanelaTest`, 5 testes, incluindo o laço de 1096 dias) |
| Migration única, duas tabelas, FKs `restrictOnDelete` no `pae_ccpae` | Task 3 |
| `avaliar` e `registrarRelatorio`: regras de fonte única, `lockForUpdate`, idempotência, timeline, PDF limpo em falha, avaliação `exigivel` exigida, arquivado somente leitura | Task 4 |
| `evidenciaParaEmissao`, `resumo`, `anotarListagem` em lote com as 6 situações | Task 4 |
| Emissão: guarda depois da DCO no mesmo lock, sem efeitos em falha, IDs congelados, CCPAE antigo com referências nulas | Task 5 (e `PaeSimuladoSchemaTest` para o legado) |
| Modal: erro do servidor na chave `simulado` e situação do simulado | Task 5 (erro) e Task 12 (situação) |
| Rotas e permissões (`view` para página e download, `validar` para escrita), download com vínculo ao protocolo | Task 6 |
| Listagem: selo "Simulado", ação "Simulados", eventos no histórico | Tasks 6 e 12 |
| Página com situação anual, exigibilidade com histórico, formulário em abas, histórico e versões com download, referência congelada, somente leitura | Task 12 |
| Extração prévia de idempotência, PDF e listagem sem mudar DCO e evacuação | Task 1 (regressão `RUN Pae` com a base anotada) |
| Padrão visual: cabeçalho, abas, seções recolhíveis, átomos, breadcrumb, numéricos vazios | Tasks 7 a 12 |
| Verificação: motor, janela, serviço, emissão, HTTP e ACL, consultas constantes, regressão completa, build | Tasks 2 a 6 e 13 |
| Fora do escopo (formulário completo do Anexo C, PAAP, Art. 139, sigilo, assinaturas, relatório compartilhado) | Não planejado, como o spec manda |

### Varredura de placeholders

Busca por `TODO`, `TBD`, `a definir`, reticências e "etc." no plano: a única ocorrência é texto de interface ("diques etc.") copiado da ficha. Todo passo de código traz o código completo; todo passo de teste traz o teste completo com os valores conferidos (as contas estão logo abaixo de cada teste, e o laço de bissexto foi executado no container durante o planejamento: 1096 dias, 0 divergências, e os vencimentos de 2024-02-29, 2023-02-28, 2023-02-27 e 2023-03-01 saíram como nos testes).

### Consistência de assinaturas entre tarefas

- `PaeIdempotencia::exigirMesmosDados(Model, array, array)`, `PaeArquivoPdf::executar/guardar/existe/baixar` e `PaeListagem::anotar` (Task 1) são usados com a mesma forma nas Tasks 4 e 6.
- `SimuladoAnexoC::{catalogo, numeros, numerosReprovaveis, validado, exigeJustificativa, justificativasFaltantes, indicios}` e `CATEGORIAS` (Task 2) são os únicos usados em `RegistrarSimuladoRequest` e `PaeSimuladoService` (Task 4).
- `PaeSimuladoService::evidenciaParaEmissao` devolve `['avaliacao', 'relatorio']` (Task 4) e é lido assim em `PaeCcpaeService` (Task 5).
- Os métodos estáticos de `PaeSimuladoServiceTest` (`avaliacao`, `criterios`, `linha`, `registro`, `pdf`) são reaproveitados pelos testes das Tasks 5 e 6 e todos são `public static`.
- Chaves de resposta: `resumo` (`situacao`, `janela_inicio`, `proximo_vencimento`, `alerta_legado`, `avaliacao`, `avaliacoes`, `relatorios`, `catalogo`, `ccpae`) e cada relatório apresentado (`vigente`, `aviso_antecedencia_dias`, `alerta_aviso`, `indicios`, `tempos` com `*_fmt`) coincidem com o que as Tasks 9 a 12 leem.
- Nomes de rota `pae.protocolo.simulados.{show,avaliar,relatorios.store,indicios,relatorios.download}` (Task 6) coincidem com os usados no composable e nos organismos (Task 12).
- Componentes visuais: `PaeTelaLayout`, `PaeAviso`, `FormFileField`, `utils/paeTela.js` (Task 7) são usados com as mesmas props nas Tasks 9 a 12; a chave `simulado` do erro do modal (Task 5) coincide com a do serviço.

### Leituras adotadas onde o spec é ambíguo (e uma adição)

1. **Textos do catálogo com acentuação:** a pesquisa guarda o item 8.1 sem acentos "de propósito"; o catálogo restaura a ortografia da Resolução (mesmas palavras), e o teste fixa os 10 pares por extenso. Conferir contra o PDF oficial na revisão.
2. **Indício de ponto de encontro inválido** aparece no critério da categoria (5 a 8, como diz o "Motor") **e** no critério 4 (decisão 6 das ambiguidades).
3. **Justificativa obrigatória** só nos critérios 1 a 8; os informativos 9 e 10 aceitam nulo ("os informativos aceitam nulo"), apesar da decisão 16 falar em "qualquer não atende".
4. **Categoria `ensino`** não gera indício (informativa, decisão 8).
5. **Sem aviso à CEDEC informado não há alerta** do Art. 94; antecedência exatamente 7 dias não alerta.
6. **Janela:** 29/02 recua para 28/02 (sem estouro de mês); o vencimento é o último dia de referência em que a janela ainda contém a realização, o que torna "próximo vencimento" e "vigente" sempre coerentes.
7. **`em_dia`** vale quando algum simulado da janela tem a última versão validada; `nao_validado` quando há simulado na janela e nenhum validado.
8. **Acréscimo ao spec:** rota `POST .../simulados/indicios` (pré-visualização dos indícios sem gravar), para que a tela mostre indícios sem duplicar a regra no cliente.
9. **Divisão do modal entre as Tasks 5 e 12:** o erro do servidor entra com a guarda; a linha de situação depende da listagem e entra com ela.
10. **RAT intocado:** abas e seção recolhível genéricas já existiam (`ModuleTabs`, `CollapsibleSection`); só o cabeçalho foi extraído (`DetalheHeader`), espelhando o do RAT sem substituí-lo por não haver como conferir "aparência idêntica" sem captura de tela.
