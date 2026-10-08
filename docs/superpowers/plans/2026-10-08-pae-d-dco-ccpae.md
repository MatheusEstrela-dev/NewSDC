# PAE D: DCO e emissão do CCPAE — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. O usuário vinha escolhendo execução nativa neste chat; não delegar sem nova instrução.

**Goal:** registrar aplicabilidade e DCO anual por protocolo, exigir prova positiva quando aplicável para emitir CCPAE e sinalizar ciclos anuais pendentes sem revogação automática.

**Architecture:** Avaliações e documentos append-only preservam revisões. Uma classe pura define a competência anual; `PaeDcoService` serializa gravações e resolve a prova, que `PaeCcpaeService` congela no certificado dentro da transação existente. Uma página Inertia apresenta decisão, documentos e alertas, com arquivo no disco privado `pae`.

**Tech Stack:** Laravel 12, PHP 8.3+, PostgreSQL, Inertia 2, Vue 3, Vite, storage privado `pae`.

**Spec:** `docs/superpowers/specs/2026-10-08-pae-d-dco-ccpae-design.md`.

## Global Constraints

- Partir de `origin/dev` em `5e6af26aa` na worktree `codex/pae-dco-gmg83`; não tocar o checkout principal sujo nem a modificação RAT na worktree `dev`.
- O usuário antecipou DCO para a fase D; a calculadora de evacuação chamada “D” no roteiro preliminar da fase A continua pendente para fase posterior.
- Não editar `DOC.MD` nem inserir emoji no código. Mensagens de commit seguem `<emoji> tipo(pae): descrição em pt-BR`.
- `ENGINEER.MD` e `PAPIROS.MD` não foram encontrados neste checkout. Zen/Gemini/Notion MCP não estão disponíveis; a exceção para documentação `.md` já foi autorizada pelo usuário nesta conversa.
- Uma só migration principal, `SDC/database/migrations/2026_10_08_120000_create_pae_dco_registros.php`, contém as duas tabelas e as referências novas em `pae_ccpae`; consolidar nela qualquer ajuste de schema antes de commitar.
- Não commitar arquivos de teste novos ou alterados. Criar testes locais, executá-los e deixar fora do staging. Spec e plano entram juntos em um commit documental; a feature completa em outro commit atômico.
- `pae.protocolos.validar` decide aplicabilidade e confere DCO; `pae.protocolos.view` lê e baixa o arquivo; `pae.protocolos.edit` continua sendo a permissão da emissão. Não criar slugs novos.
- A emissão sem DCO exigível deve falhar antes do workflow, de `pae_ccpae`, das comunicações e do outbox. Protocolo legado com CCPAE não é revogado retroativamente.
- O resultado e o ano de competência são verificados por servidor. Não usar campos do formulário de emissão como fonte de verdade para a DCO.
- Reutilizar o disco privado `pae`; verificar que upload por uma réplica é legível pelas demais.

## Review Focus

1. **Junho versus julho:** em 30/06 aceita competência anterior positiva; em 01/07 exige a do ano corrente (Task 1 e Task 3).
2. **Substituição negativa:** uma DCO não conforme posterior na mesma competência impede emissão, mesmo com positiva antiga (Task 2 e Task 3).
3. **Data retroativa:** documento apresentado depois da data informada para emissão não serve de prova (Task 3).
4. **Concorrência e storage:** avaliação, upload e emissão usam a mesma trava do protocolo; rollback limpa arquivo parcial e não cria CCPAE/comunicação/outbox (Task 2 e Task 3).
5. **Legado e ACL:** CCPAE antigo permanece consultável sem evidência inventada; arquivo de outro protocolo e usuário sem `view` não podem baixá-lo (Task 4 e Task 5).

## Mapa de arquivos

| Responsabilidade | Arquivos |
| --- | --- |
| Ciclo anual puro | Criar `SDC/app/Modules/Pae/Support/PaeDcoCiclo.php` |
| Persistência | Criar `SDC/database/migrations/2026_10_08_120000_create_pae_dco_registros.php`, `SDC/app/Modules/Pae/Models/PaeDcoAvaliacao.php`, `PaeDcoDocumento.php`; modificar `PaeProtocolo.php`, `PaeCcpae.php` |
| Domínio e emissão | Criar `SDC/app/Modules/Pae/Services/PaeDcoService.php`; modificar `PaeCcpaeService.php`, `PaeServiceProvider.php` |
| HTTP | Criar `SDC/app/Modules/Pae/Controllers/PaeDcoController.php`, `Requests/AvaliarDcoRequest.php`, `Requests/RegistrarDcoRequest.php`; modificar `SDC/routes/modules/pae.php`, `PaeProtocoloController.php` |
| Interface | Criar `SDC/resources/js/Pages/PaeDco.vue`; modificar `EmitirCcpaeModal.vue`, `PaeProtocoloCard.vue`, `PaeProtocolosGrid.vue`, `PaeProtocolosTable.vue`, `PaeProtocolosIndexTemplate.vue`, e `PaeHistoricoModal.vue` se necessário para o histórico |
| Testes locais | Criar `SDC/tests/Unit/Pae/PaeDcoCicloTest.php` e `SDC/tests/Feature/Pae/PaeDcoTest.php` durante o trabalho; não incluir no commit |

---

### Task 1: Ciclo anual e schema histórico

**Files:** criar `Support/PaeDcoCiclo.php`, os dois models e a migration principal; modificar os models `PaeProtocolo.php` e `PaeCcpae.php`.

**Interfaces:** `PaeDcoCiclo::competenciaExigivel(CarbonImmutable $data): int`, `PaeDcoCiclo::vencimento(int $competencia): CarbonImmutable`; relações `PaeProtocolo::avaliacoesDco()`, `documentosDco()`, `PaeCcpae::avaliacaoDco()` e `documentoDco()`.

- [ ] **Step 1: Criar testes locais de calendário e schema que falham.** `2026-06-30 -> 2025`, `2026-07-01 -> 2026`; `vencimento(2026) -> 2026-06-30`. Com `RefreshDatabase`, inserir duas avaliações e duas DCO da mesma competência; verificar imutabilidade da revisão anterior e referências nullable de CCPAE legado.

```php
$this->assertSame(2025, PaeDcoCiclo::competenciaExigivel(CarbonImmutable::parse('2026-06-30')));
$this->assertSame(2026, PaeDcoCiclo::competenciaExigivel(CarbonImmutable::parse('2026-07-01')));
$this->assertSame('2026-06-30', PaeDcoCiclo::vencimento(2026)->toDateString());
```

- [ ] **Step 2: Executar os testes para ver a falha esperada.** Usar PHP do container do projeto, não o PHP 8.1 do host: `php artisan test --filter=PaeDcoCicloTest` e `--filter=PaeDcoTest`. Esperado: classe e tabelas ainda não existem.
- [ ] **Step 3: Criar a classe pura e migration única.** `competenciaExigivel` retorna `year - 1` quando mês <= 6, senão `year`. Tabelas append-only: `pae_dco_avaliacoes` tem `protocolo_id`, `resultado` (`aplicavel|nao_aplicavel`), `fundamentacao`, `num_sei`, `chave_idempotencia` UUID, `decidido_por`, `decidido_em`; `pae_dco_documentos` tem `protocolo_id`, `competencia`, `versao`, `resultado` (`positiva|nao_conforme`), `dt_documento`, `dt_apresentacao`, `num_sei`, `observacao`, metadados do arquivo, `chave_idempotencia`, `registrado_por`, `registrado_em`. Usar checks para enums, `unique(protocolo_id, competencia, versao)` e `unique(protocolo_id, chave_idempotencia)`; índices `(protocolo_id, id)` e `(protocolo_id, competencia, versao)`. Adicionar `dco_avaliacao_id` e `dco_documento_id` nullable em `pae_ccpae`, FKs `restrictOnDelete`; `down()` remove FKs/colunas e depois tabelas.

```php
$table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
$table->unsignedSmallInteger('competencia');
$table->unsignedInteger('versao');
$table->uuid('chave_idempotencia');
$table->unique(['protocolo_id', 'competencia', 'versao'], 'pae_dco_documento_versao_unica');
```

- [ ] **Step 4: Criar models com casts e relações, repetir os testes.** Não criar `updated_at` nas linhas históricas. A última versão de uma competência é ordenada por `versao`, e a avaliação vigente por `id` decrescente. Esperado: testes locais verdes.

### Task 2: Avaliação, documento e limpeza de arquivo

**Files:** criar `Services/PaeDcoService.php`; registrar singleton em `PaeServiceProvider.php`; usar models da Task 1.

**Interfaces:** `avaliar(PaeProtocolo $protocolo, array $dados, User $user): PaeDcoAvaliacao`; `registrarDocumento(PaeProtocolo $protocolo, array $dados, UploadedFile $arquivo, User $user): PaeDcoDocumento`; `resumo(PaeProtocolo $protocolo, CarbonImmutable $hoje): array`.

- [ ] **Step 1: Testar as falhas antes de implementar.** Avaliação sem fundamento/SEI, repetição da chave, upload sem avaliação `aplicavel`, arquivo não PDF, competência futura, data futura, duas versões da mesma competência, versão posterior `nao_conforme`, falha de `Storage::disk('pae')` e duas requisições concorrentes. Confirmar que um rollback não deixa linha nem arquivo.

```php
Storage::fake('pae');
$primeira = $service->registrarDocumento($protocolo, $dados + ['chave_idempotencia' => $uuid], $pdf, $user);
$segunda = $service->registrarDocumento($protocolo, $dados + ['chave_idempotencia' => $uuid], $pdf, $user);
$this->assertSame($primeira->id, $segunda->id);
$this->assertSame(1, $protocolo->documentosDco()->count());
```

- [ ] **Step 2: Rodar `PaeDcoTest`, confirmar falha por serviço inexistente.** `php artisan test --filter=PaeDcoTest` no container PHP/PostgreSQL isolado.
- [ ] **Step 3: Implementar gravações transacionais.** Em ambos os métodos, fazer `PaeProtocolo::whereKey(...)->lockForUpdate()->firstOrFail()` antes de consultar idempotência ou estado. `avaliar` exige texto/SEI não vazios e cria nova linha com timeline. `registrarDocumento` exige avaliação vigente `aplicavel`, calcula `versao = max(versao)+1` por competência, grava PDF em `Storage::disk('pae')` sob `dco/{protocoloId}/{competencia}/{uuid}.pdf`, cria linha e timeline na mesma transação; `catch(Throwable)` apaga somente o arquivo criado nesta tentativa.

```php
return DB::transaction(function () use ($protocolo, $dados, $user): PaeDcoAvaliacao {
    $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
    $existente = $locked->avaliacoesDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
    if ($existente) return $existente;
    return $locked->avaliacoesDco()->create([
        'resultado' => $dados['resultado'],
        'fundamentacao' => trim($dados['fundamentacao']),
        'num_sei' => trim($dados['num_sei']),
        'chave_idempotencia' => $dados['chave_idempotencia'],
        'decidido_por' => $user->id,
        'decidido_em' => now(),
    ]);
});
```

- [ ] **Step 4: Implementar `resumo` e repetir testes.** Resposta inclui avaliação vigente, documentos por competência/versão, competência exigível, próximo vencimento, situação `nao_avaliada|nao_aplicavel|comprovada|aguardando_prazo|atrasada|nao_conforme`, e alerta para CCPAE legado sem avaliação. Regra anual usa data atual para o alerta, data da emissão apenas para a prova congelada. Não apagar marca de atraso histórico ao cadastrar documento com apresentação posterior a 30/06.

### Task 3: Guarda transacional da emissão e prova congelada

**Files:** modificar `PaeCcpaeService.php`, `PaeCcpae.php`, `PaeDcoService.php`; estender teste local `PaeDcoTest.php`.

**Interfaces:** `PaeDcoService::evidenciaParaEmissao(PaeProtocolo $protocoloBloqueado, CarbonImmutable $dataEmissao): array{avaliacao: PaeDcoAvaliacao, documento: ?PaeDcoDocumento}`; `PaeCcpaeService::emitir` grava os dois IDs em `pae_ccpae`.

- [ ] **Step 1: Criar testes de emissão que falham.** Sem avaliação, aplicável sem DCO, negativa após positiva da mesma competência, documento antigo em 01/07, documento com `dt_apresentacao` posterior à emissão, não aplicável fundamentado, positiva válida, emissão repetida e concorrente. Em cada falha contar `pae_ccpae`, `pae_comunicacoes`, trâmite, timeline e outbox para provar ausência de efeitos.

```php
try {
    $ccpae->emitir($protocolo, $dto, $user);
    $this->fail('A emissão deveria exigir DCO.');
} catch (ValidationException $e) {
    $this->assertDatabaseCount('pae_ccpae', 0);
    $this->assertDatabaseCount('pae_comunicacoes', 0);
}
```

- [ ] **Step 2: Executar testes e confirmar falha do requisito DCO.** Com o código atual, CCPAE é emitido sem avaliação; o novo teste deve falhar nessa asserção.
- [ ] **Step 3: Implementar seleção e conferência da evidência.** Dentro da transação existente, após `lockForUpdate` e antes de `workflow->transitar`, chamar `evidenciaParaEmissao`. Ela usa avaliação mais recente; `nao_aplicavel` retorna documento `null`; `aplicavel` filtra DCOs com `dt_documento` e `dt_apresentacao` até a data da emissão, seleciona a maior competência entre a exigível e o ano da emissão e sua última versão disponível naquela data. Exige `positiva` e `Storage::disk('pae')->exists(path)`. Não aceitar IDs do request. Persistir `dco_avaliacao_id`/`dco_documento_id` no `PaeCcpae::create`.

```php
$evidencia = $this->dco->evidenciaParaEmissao($protocolo, $dados->dtEmissao);
$this->workflow->transitar($protocolo, PaeProtocoloStatus::CCPAE, $user, $obs, ContextoTransicao::emissaoCcpae());
$ccpae = PaeCcpae::create([
    'protocolo_id' => $protocolo->id,
    'codigo' => $dados->codigo,
    'dt_emissao' => $dados->dtEmissao->toDateString(),
    'dt_vencimento' => $vencimento->toDateString(),
    'status' => PaeCcpae::STATUS_ATIVO,
    'dt_licenca_operacao' => $dados->dtLicencaOperacao?->toDateString(),
    'emitido_por' => $user->id,
    'dco_avaliacao_id' => $evidencia['avaliacao']->id,
    'dco_documento_id' => $evidencia['documento']?->id,
]);
```

- [ ] **Step 4: Repetir regressões.** Cobrir CCPAE vigente, protocolo arquivado, guard de admissibilidade, outbox `CcpaeEmitidoV1` e criação de pendências FEAM/COMPDEC. Confirmar que a checagem nova não muda os quatro eventos antigos.

### Task 4: HTTP, ACL e tela de DCO

**Files:** criar `Requests/AvaliarDcoRequest.php`, `Requests/RegistrarDcoRequest.php`, `Controllers/PaeDcoController.php`, `Pages/PaeDco.vue`; modificar `routes/modules/pae.php` e os componentes de ação da lista.

**Interfaces:** `GET pae.protocolo.dco.show`, `POST pae.protocolo.dco.avaliar`, `POST pae.protocolo.dco.documentos.store`, `GET pae.protocolo.dco.documentos.download`. Página recebe `protocolo`, `resumo`, `can_validar`, `can_view`; o upload usa multipart/FormData de Inertia.

- [ ] **Step 1: Criar testes HTTP que falham.** 403 para criar sem `validar`; 403 para download sem `view`; 404 para documento de outro protocolo ou arquivo inexistente; 422 para PDF ausente, não PDF, arquivo >20 MiB, ano futuro, SEI/fundamento vazios e datas futuras. Consulta de CCPAE legado mostra `nao_avaliada`.
- [ ] **Step 2: Executar teste e confirmar rotas ausentes.** `php artisan test --filter=PaeDcoTest`.
- [ ] **Step 3: Implementar Requests, controller e rotas.** Requests validam `chave_idempotencia` UUID, `resultado` por enum, `fundamentacao` e `num_sei` obrigatórios; documento: `competencia` inteiro entre 2022 e ano atual, datas `before_or_equal:today`, arquivo `required|file|mimes:pdf|max:20480`. `show` requer `view`; `avaliar` e `documentos.store` requerem `validar`; download usa vínculo `$documento->protocolo_id === $paeProtocolo->id` e `Storage::disk('pae')->download`.

```php
Route::get('/protocolo/{paeProtocolo}/dco', [PaeDcoController::class, 'show'])
    ->name('protocolo.dco.show')->middleware('can:pae.protocolos.view');
Route::post('/protocolo/{paeProtocolo}/dco/avaliacoes', [PaeDcoController::class, 'avaliar'])
    ->name('protocolo.dco.avaliar')->middleware('can:pae.protocolos.validar');
```

- [ ] **Step 4: Criar página e acesso na grade/tabela.** Seções: decisão vigente e histórico; formulário de aplicabilidade com fundamento e SEI; DCO por competência com upload, resultado, datas e SEI; alerta anual; download autorizado; referência congelada do CCPAE. Usar layout e dark mode existentes. Mostrar apenas leitura sem `validar`; navegar pela ação `DCO` no protocolo. Gerar nova chave UUID após sucesso do submit.
- [ ] **Step 5: Repetir teste HTTP e compilar.** `php artisan route:list --path=pae`, `php artisan test --filter=PaeDcoTest`, `npm run build`. Conferir desktop e largura móvel no navegador autenticado.

### Task 5: Alertas anuais e integração com emissão

**Files:** modificar `PaeDcoService.php`, `PaeProtocoloController.php`, `EmitirCcpaeModal.vue`, `PaePrazosPainel.vue` ou painel DCO próprio, `PaeProtocolosIndexTemplate.vue`; adicionar mapeamento de eventos DCO no histórico.

**Interfaces:** `PaeDcoService::anotarListagem(LengthAwarePaginator $protocolos, CarbonImmutable $hoje): LengthAwarePaginator` adiciona `dco_situacao` sem N+1; `PaeDcoService::resumo` é usado no modal e na página.

- [ ] **Step 1: Testar os estados anuais primeiro.** Em 29/06 com DCO do ano anterior: `aguardando_prazo`; em 01/07 sem DCO corrente: `atrasada`; com DCO corrente negativa: `nao_conforme`; com positiva: `comprovada`; legado sem avaliação: `nao_avaliada`. Testar também upload tardio: estado atual comprovado, histórico marca entrega após o prazo.
- [ ] **Step 2: Rodar teste e confirmar falha no resumo.** `php artisan test --filter=PaeDcoTest`.
- [ ] **Step 3: Anotar a listagem em lote.** Consultar avaliações, última DCO por competência e CCPAE dos IDs da página em no máximo três consultas adicionais; mapear situação na coleção paginada. O cálculo fica no mesmo serviço/classe pura da Task 2. `PaeProtocoloController::index` chama o método depois de `PaePrazoService::anotarListagem`.
- [ ] **Step 4: Integrar UI.** Modal de emissão exibe `dco_situacao` e link para `/pae/protocolo/{id}/dco`; desabilita o botão quando a prop indicar bloqueio, mas mostra o erro do servidor se a situação mudar entre render e POST. Histórico mapeia `dco_avaliacao` e `dco_documento`; não cria status automático de suspensão/revogação.
- [ ] **Step 5: Verificar regressões.** Testes de listagem, A/B/C, emissão, histórico e build. Confirmar ausência de N+1 pelo contador de queries no teste local e checar que os quatro eventos outbox existentes mantêm o contrato.

### Task 6: Verificação final e commits

**Files:** todos os acima; apenas spec e plano como documentação, nenhum arquivo de teste no commit.

- [ ] **Step 1: Rodar migration em banco isolado e suíte PAE.** Usar container PHP/PostgreSQL do projeto; `php artisan migrate --pretend`, `php artisan migrate`, `php artisan test --filter=Pae`, `php artisan route:list --path=pae`, lint PHP dos arquivos modificados e `npm run build`. Registrar números de testes/falhas e limitações reais.
- [ ] **Step 2: Exercitar no navegador.** Protocolo de teste: sem avaliação, não aplicável fundamentado, aplicável com DCO positiva, DCO negativa posterior, histórico, download e erro de emissão. Verificar usuário só de leitura e vista móvel. Não usar documento real nem disparar comunicação externa em testes.
- [ ] **Step 3: Confirmar armazenamento entre réplicas.** Enviar PDF de teste por uma porta de réplica e baixar por outra, então excluir apenas o dado/arquivo de teste criado na verificação. Se a infraestrutura local não estiver disponível, relatar que esta verificação ficou pendente sem afirmar paridade das réplicas.
- [ ] **Step 4: Revisar Git.** `git diff --check`, `git status --short`, `git diff --name-only` e `git diff --cached --name-only`; excluir testes transitórios do staging e verificar ausência de logs de depuração.
- [ ] **Step 5: Commitar unidades completas.** Um commit `📝 docs(pae): especifica fase D da DCO e CCPAE` para spec + plano. Após a feature inteira verificada, outro `✨ feat(pae): exige DCO aplicável na emissão do CCPAE` para schema, backend e UI. Não fazer commits por arquivo.
- [ ] **Step 6: Informar SHA, verificação e limites.** Integração em `dev` e atualização das quatro réplicas seguem o pedido já feito pelo usuário; executar após a feature estar verificada e com segurança operacional, sem misturar a modificação RAT preexistente na worktree `dev`.
