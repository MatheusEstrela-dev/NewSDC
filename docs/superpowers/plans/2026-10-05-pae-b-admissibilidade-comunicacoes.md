# PAE B: admissibilidade e comunicações oficiais — plano de implementação

> **Para agentes de implementação:** executar uma tarefa por vez com `superpowers:executing-plans` (execução nativa) ou `superpowers:subagent-driven-development`, após a revisão deste plano pelo usuário. Os testes escritos durante o trabalho são locais e ficam fora dos commits, conforme AGENTS.md.

**Objetivo:** registrar a triagem do Anexo J e as decisões de admissibilidade, controlar a reprovação sumária e comprovar as comunicações oficiais à FEAM e às COMPDECs da ZAS.

**Arquitetura:** entidades separadas para municípios afetados, itens, decisões e comunicações. O workflow existente continua como único escritor de status; um guard exige admissão para protocolos novos. Serviços abrem pendências de comunicação na transação dos eventos de origem, e a CEDEC registra o envio oficial depois.

**Stack:** Laravel/PHP 8.4, PostgreSQL, Inertia/Vue 3, PHPUnit 11, Vite.

**Spec:** `docs/superpowers/specs/2026-10-05-pae-b-admissibilidade-comunicacoes-design.md`.

## Restrições globais

- Partir de `dev` em `82f9c34d`, no worktree `codex/pae-admissibilidade-gmg83`; não editar o checkout raiz ocupado por outra feature.
- Uma única migração principal da fase B contém as quatro tabelas e o marcador legado; consolidar nela qualquer ajuste antes do commit.
- Não criar nem alterar `DOC.MD`. Não usar emojis em código. Usar DRY/SOLID e preservar os contratos do outbox existentes.
- Commitar unidades coerentes de backend e interface; título em pt-BR no formato `<gitmoji> tipo(escopo): descrição`. Não incluir arquivos de testes nos commits sem nova autorização.
- Usar `pae.protocolos.edit` para triagem e registro de comunicações, `pae.protocolos.validar` para a decisão, e `pae.protocolos.view` para leitura/download. Esses slugs existem em `SDC/config/permissions.php`; `config/acl.php` citado por skill antiga não existe neste checkout.
- `REPROVADO_SUMARIAMENTE` é distinto de `REPROVADO` analítico. Falta em um item do Anexo J jamais reprova automaticamente. Sem lista ZAS confirmada, não afirmar que todas as COMPDECs foram comunicadas.
- O usuário aprovou `.md` sem Zen/Gemini nesta sessão, pois esses MCPs não estão disponíveis.

## Foco da revisão

1. Município presente simultaneamente em ZAS e ZSS: uma linha, duas flags verdadeiras e uma única comunicação à COMPDEC.
2. Checklist com item ausente mas justificativa aceita: admissão permitida com motivação; reprovação só com fundamento legal permitido.
3. Protocolo legado sem triagem: atalho histórico preservado; protocolo novo sem decisão não chega a `CRIACAO_SDC` nem a `CCPAE`.
4. Duas requisições concorrentes para a mesma decisão ou origem: uma decisão efetiva e uma pendência por destinatário.
5. Falha no upload do comprovante ou usuário sem permissão: comunicação continua pendente e arquivo parcial não permanece no disco.

## Mapa de arquivos e interfaces

| Unidade | Arquivos principais | Interface pública |
| --- | --- | --- |
| Persistência/catálogos | `SDC/database/migrations/2026_10_05_120000_create_pae_admissibilidade_comunicacoes.php`, `SDC/app/Modules/Pae/Support/PaeAnexoJ.php`, `PaeFundamentosSumarios.php`, quatro modelos em `Models/` | Chaves estáveis, relacionamentos e casts |
| Triagem | `Services/PaeAdmissibilidadeService.php`, `Controllers/PaeAdmissibilidadeController.php`, `Requests/SalvarTriagemRequest.php`, `routes/modules/pae.php` | `salvarTriagem(PaeProtocolo, array, array, User): void`; `resumo(PaeProtocolo): array` |
| Decisão | Os mesmos service/controller, `Requests/DecidirAdmissibilidadeRequest.php`, `Domain/Guards/ExigeAdmissibilidade.php`, `Enums/PaeProtocoloStatus.php`, `Domain/ContextoTransicao.php`, `PaeServiceProvider.php` | `decidir(PaeProtocolo, array, User): PaeAdmissibilidadeDecisao` |
| Comunicação | `Services/PaeComunicacaoService.php`, `Controllers/PaeComunicacaoController.php`, `Requests/RegistrarComunicacaoRequest.php`, `Domain/Events/CcpaeEmitidoV1.php`; hooks em `PaeCcpaeService`, `PaeNotificacaoService`, `PaeProtocoloWorkflow` | `abrir(PaeProtocolo, string, int, ?string): void`; `registrar(PaeComunicacao, CarbonImmutable, string, UploadedFile, User): PaeComunicacao` |
| Interface | `PaeAdmissibilidadePainel.vue`, `PaeComunicacoesPainel.vue`, `PaeHistoricoModal.vue`, `PaeProtocolosIndexTemplate.vue`, `PaeProtocoloController.php` | Props `admissibilidade`, `comunicacoes`, `canDecide`; ações pelas rotas nomeadas |

Os caminhos relativos dos arquivos da pasta `Modules/Pae` acima começam em `SDC/app/Modules/Pae/` quando o prefixo foi omitido na tabela.

---

### Task 1: estrutura persistente e catálogos normativos

**Arquivos:** criar a migração principal, `PaeAnexoJ.php`, `PaeFundamentosSumarios.php`, `PaeMunicipioImpactado.php`, `PaeAdmissibilidadeItem.php`, `PaeAdmissibilidadeDecisao.php`, `PaeComunicacao.php`; modificar `PaeProtocolo.php`.

**Produz:** quatro tabelas; `PaeAnexoJ::CHAVES` (nove chaves); `PaeFundamentosSumarios::ARTIGOS` (118–124, 128, 129); relacionamentos `municipiosImpactados`, `itensAdmissibilidade`, `decisoesAdmissibilidade`, `comunicacoes`.

- [ ] Criar teste local `SDC/tests/Feature/Pae/PaeAdmissibilidadeSchemaTest.php` que executa migração em banco isolado e verifica: nove chaves válidas, `unique(protocolo_id, municipio_id)`, `unique(protocolo_id, chave)`, deduplicação de comunicação e marcador legado para linhas pré-existentes. Rodar o teste e confirmar falha inicial.

```php
$this->assertSame(9, count(PaeAnexoJ::CHAVES));
$this->assertSame([118, 119, 120, 121, 122, 123, 124, 128, 129], PaeFundamentosSumarios::ARTIGOS);
$this->assertTrue($antigo->fresh()->admissibilidade_legada_sem_triagem);
$this->assertFalse(PaeProtocolo::factory()->create()->admissibilidade_legada_sem_triagem);
```

- [ ] Criar migração única: `pae_protocolo_municipios` (`protocolo_id`, `municipio_id`, `na_zas`, `na_zss`, `confirmado_por`, `confirmado_em`); `pae_admissibilidade_itens` (`protocolo_id`, `chave`, `resultado`, `justificativa`, `updated_by`); `pae_admissibilidade_decisoes` (`protocolo_id`, `tipo`, `fundamentacao`, `fundamentos` JSONB, `checklist_snapshot` JSONB, `submetido_em`, `transitorio_confirmado`, `notificado_em`, `prazo_correcao_em`, `num_sei`, `decidido_por`, `decidido_em`); `pae_comunicacoes` (`protocolo_id`, `origem_tipo`, `origem_id`, `destinatario_tipo`, `municipio_id` opcional, `motivos`, `status`, `dt_envio`, `num_sei`, `comprovante_*`, `registrado_por`, `registrado_em`). Acrescentar `admissibilidade_legada_sem_triagem` a `pae_protocolos` com default falso e atualizar apenas linhas existentes para verdadeiro antes de liberar novas gravações. Usar índices/uniques e FKs, inclusive único de origem/destinatário com tratamento explícito de `NULL` para FEAM no PostgreSQL.

```php
Schema::table('pae_protocolos', function (Blueprint $table): void {
    $table->boolean('admissibilidade_legada_sem_triagem')->default(false);
});
DB::table('pae_protocolos')->update(['admissibilidade_legada_sem_triagem' => true]);
Schema::create('pae_protocolo_municipios', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
    $table->foreignId('municipio_id')->constrained('municipios');
    $table->boolean('na_zas');
    $table->boolean('na_zss');
    $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
    $table->timestampTz('confirmado_em')->nullable();
    $table->unique(['protocolo_id', 'municipio_id']);
});
```

Após criar `pae_comunicacoes`, aplicar `DB::statement('CREATE UNIQUE INDEX pae_comunicacoes_origem_destino_unique ON pae_comunicacoes (origem_tipo, origem_id, destinatario_tipo, COALESCE(municipio_id, 0))')`. Assim `NULL` da FEAM não permite duplicatas no PostgreSQL.

- [ ] Criar modelos com casts tipados, `fillable` explícito e relações no protocolo. Não inferir ZAS pelo município do empreendimento. Rodar o teste até passar e executar `php artisan migrate:status` no banco isolado.
- [ ] Revisar `git diff --check` e o conjunto de arquivos da unidade; manter o teste local fora do stage. Commitar somente migração, catálogos e modelos: `🗃️ db(pae): estrutura da admissibilidade e comunicações`.

### Task 2: cadastro de municípios e checklist

**Arquivos:** criar `PaeAdmissibilidadeService.php`, `PaeAdmissibilidadeController.php`, `SalvarTriagemRequest.php`; modificar `routes/modules/pae.php`, `PaeProtocoloController.php`; criar `PaeAdmissibilidadePainel.vue`; modificar `PaeHistoricoModal.vue` e `PaeProtocolosIndexTemplate.vue`.

**Consome:** modelos e catálogos da Tarefa 1. **Produz:** `salvarTriagem(PaeProtocolo $protocolo, array $municipios, array $itens, User $user): void` e `resumo(PaeProtocolo $protocolo): array`.

- [ ] Criar teste local `PaeAdmissibilidadeTriagemTest.php`: duas zonas no mesmo município geram uma linha; município repetido é rejeitado; uma das flags deve ser verdadeira; item `nao` sem justificativa é rejeitado; os nove itens são retornados no resumo. Confirmar falha inicial.

```php
$service->salvarTriagem($protocolo,
    [['municipio_id' => $municipio->id, 'na_zas' => true, 'na_zss' => true]],
    [['chave' => PaeAnexoJ::CHAVES[0], 'resultado' => 'nao', 'justificativa' => 'Ausência examinada pela CEDEC']],
    $user);
$this->assertDatabaseCount('pae_protocolo_municipios', 1);
```

- [ ] Validar payload completo no `SalvarTriagemRequest`, restringindo alteração a `NOVO`/`ENTRADA_PROCESSO` e a quem tem `pae.protocolos.edit`. No serviço, `DB::transaction` e `lockForUpdate` do protocolo; sincronizar municípios e itens por chaves estáveis, sem apagar decisões anteriores. Registrar alteração na timeline. `resumo` devolve lista de municípios, nove itens ordenados, decisão vigente, indicador legado e permissões sem colocar a regra legal no Vue.
- [ ] Expor `GET /pae/protocolo/{paeProtocolo}/admissibilidade` (`pae.protocolos.view`) e `PUT` (`pae.protocolos.edit`). No painel, permitir editar as duas flags de zona e o checklist, exibir os nove rótulos e justificativas e mostrar “triagem não registrada no sistema” para legado. Integrar ao modal de histórico existente.

```php
Route::put('/protocolo/{paeProtocolo}/admissibilidade', [PaeAdmissibilidadeController::class, 'salvar'])
    ->name('protocolo.admissibilidade.salvar')
    ->middleware('can:pae.protocolos.edit');
```

- [ ] Rodar teste local, teste de autorização da rota e `npm run build`; verificar erro de validação no formulário. Não stagear testes. Commitar a entrega completa: `✨ feat(pae): triagem do Anexo J e municípios ZAS ZSS`.

### Task 3: decisão e guard do workflow

**Arquivos:** modificar `PaeAdmissibilidadeService.php`, `PaeAdmissibilidadeController.php`, `PaeProtocoloStatus.php`, `ContextoTransicao.php`, `PaeServiceProvider.php`, `PaeProtocoloWorkflow.php`, `PaeProtocolo.php`, `PrazoAnalise.php`, `PaeProtocoloController.php`, `routes/modules/pae.php`, `PaeAdmissibilidadePainel.vue`; criar `DecidirAdmissibilidadeRequest.php` e `ExigeAdmissibilidade.php`.

**Consome:** triagem da Tarefa 2. **Produz:** `decidir(PaeProtocolo $protocolo, array $dados, User $user): PaeAdmissibilidadeDecisao` e a transição exclusiva para `REPROVADO_SUMARIAMENTE`.

- [ ] Criar teste local `PaeAdmissibilidadeDecisaoTest.php` com casos: item ausente justificado pode ser admitido; rejeição requer artigo do rol e fundamentação; correção transitória só com `transitorio_confirmado`, data de submissão e número SEI comprovando apresentação anterior à publicação oficial, e prazo exato de 30 dias; prazo vencido não rejeita automaticamente; usuário sem `pae.protocolos.validar` recebe 403; novo protocolo sem admissão não passa por `changeStatus`, `conduzirAte` ou emissão do CCPAE; legado continua; decisão duplicada não cria duas linhas. Confirmar falha.

```php
$this->expectException(TransicaoProibidaException::class);
$workflow->transitar($novo, PaeProtocoloStatus::CRIACAO_SDC, $user);
// Após decidir como admitido, a mesma transição deve passar.
```

- [ ] Adicionar estado terminal `REPROVADO_SUMARIAMENTE` com label/cor/filtros coerentes. Permitir `ENTRADA_PROCESSO -> REPROVADO_SUMARIAMENTE` somente com `ContextoTransicao::decisaoAdmissibilidade()`. O guard consulta a última decisão para `CRIACAO_SDC`/`CCPAE`, ignora apenas o marcador legado e nunca faz transição automática. Incluir o novo terminal em `PrazoAnalise::STATUS_ENCERRADOS` e em contadores/filtros que enumeram terminais.

```php
if ($novo === PaeProtocoloStatus::CRIACAO_SDC || $novo === PaeProtocoloStatus::CCPAE) {
    if (! $protocolo->admissibilidade_legada_sem_triagem && ! $this->admitido($protocolo)) {
        throw new TransicaoProibidaException('Registre a admissão antes de avançar o PAE.');
    }
}
```

- [ ] Em `decidir`, travar protocolo, exigir checklist completo e ZAS/ZSS confirmada, validar artigos, registrar snapshot imutável e timeline. Admissão permite avançar no próximo comando; reprovação chama workflow com contexto próprio e cria trâmite. `correcao_solicitada` exige confirmação expressa da CEDEC, data e SEI da submissão anterior à publicação oficial; a data da resolução (16/04/2024) não substitui a data de publicação. Registrar `notificado_em` e `prazo_correcao_em = +30 dias`; nova decisão posterior preserva a anterior. Bloquear repetição idêntica/concorrente, inclusive no banco.
- [ ] Expor `POST /pae/protocolo/{paeProtocolo}/admissibilidade/decisoes` sob `pae.protocolos.validar`; botão e resumo no painel só para autorizados. Rodar testes locais, suíte relevante e build. Não stagear testes. Commitar a decisão completa: `✨ feat(pae): decisão de admissibilidade e reprovação sumária`.

### Task 4: pendências de comunicação nos eventos

**Arquivos:** criar `PaeComunicacaoService.php`, `CcpaeEmitidoV1.php`; modificar `PaeCcpaeService.php`, `PaeNotificacaoService.php`, `PaeProtocoloWorkflow.php`, `PaeAdmissibilidadeService.php`, `PaeServiceProvider.php`, `PaeProtocolo.php`.

**Consome:** municípios e decisões das Tarefas 1–3. **Produz:** `abrir(PaeProtocolo $protocolo, string $origemTipo, int $origemId, ?string $motivos = null): void` e `resumo(PaeProtocolo $protocolo): array`.

- [ ] Criar teste local `PaeComunicacoesAberturaTest.php`: CCPAE e ambas as reprovações criam FEAM + cada município ZAS; notificação só COMPDEC ZAS; ZSS não recebe; duplicação/replay não cria segunda linha; a lista alterada depois não muda o destinatário gravado; legado sem ZAS gera alerta e não afirma conclusão. Confirmar falha inicial.

```php
$service->abrir($protocolo, 'ccpae', $ccpae->id);
$service->abrir($protocolo, 'ccpae', $ccpae->id);
$this->assertDatabaseCount('pae_comunicacoes', 1 + $quantidadeMunicipiosZas);
```

- [ ] Implementar `abrir` com `firstOrCreate`/upsert protegido por unique do banco e snapshot de destinatário. FEAM só para `ccpae`/`reprovacao_sumaria`/`reprovacao_analise`; COMPDEC da ZAS para esses e `notificacao`. `resumo` conta pendências e sinaliza `enderecamento_pendente` quando não há lista confirmada.
- [ ] Integrar na mesma transação da emissão do CCPAE, da decisão sumária e da transição analítica `ANALISE -> REPROVADO`. Criar e registrar `CcpaeEmitidoV1` no outbox como evento novo, sem renomear os quatro existentes. Na notificação, tornar atômica a criação do registro, timeline e pendências; só disparar efeitos externos após commit para não enviar e-mail de transação revertida.

```php
$this->comunicacoes->abrir($protocolo, 'ccpae', $ccpae->id);
$this->outbox->persist(new CcpaeEmitidoV1(
    eventId: DomainEvent::newId(), aggregateType: 'pae_protocolo',
    aggregateId: (string) $protocolo->id, occurredAt: new DateTimeImmutable(),
    metadata: ['protocolo_id' => $protocolo->id, 'ccpae_id' => $ccpae->id]
));
```

- [ ] Rodar testes locais de outbox, notificação e CCPAE no banco isolado. Confirmar que `ParecerConcluidoV1` continua saindo só da análise e que não há e-mail automático novo para FEAM/COMPDEC. Não stagear testes. Commitar a unidade: `✨ feat(pae): pendências oficiais na emissão e reprovação`.

### Task 5: prova do envio e visibilidade das pendências

**Arquivos:** criar `PaeComunicacaoController.php`, `RegistrarComunicacaoRequest.php`, `PaeComunicacoesPainel.vue`; modificar `PaeComunicacaoService.php`, `PaeProtocoloController.php`, `PaeHistoricoModal.vue`, `PaeProtocolosIndexTemplate.vue`, `PaeProtocoloService.php`, `routes/modules/pae.php`.

**Consome:** pendências da Tarefa 4. **Produz:** `registrar(PaeComunicacao $comunicacao, CarbonImmutable $data, string $sei, UploadedFile $comprovante, User $user): PaeComunicacao` e download privado.

- [ ] Criar teste local `PaeComunicacaoRegistroTest.php`: data futura, SEI vazio e comprovante ausente não concluem; `pae.protocolos.edit` necessário para registrar; `pae.protocolos.view` necessário para baixar; falha de storage deixa pendência e elimina arquivo parcial; replay não duplica registro; o comprovante e motivos aparecem no histórico. Confirmar falha inicial.

```php
Storage::fake('pae');
$response = $this->actingAs($editor)->post(route('pae.comunicacoes.registrar', $comunicacao), [
    'dt_envio' => now()->toDateString(), 'num_sei' => 'SEI-2026-1',
    'comprovante' => UploadedFile::fake()->create('recibo.pdf', 20, 'application/pdf'),
]);
$response->assertRedirect();
$this->assertSame('registrada', $comunicacao->fresh()->status);
```

- [ ] Implementar serviço com `lockForUpdate`, validação de estado, armazenamento privado no disco `pae` e limpeza em falha. Criar `POST /pae/comunicacoes/{paeComunicacao}/registro` (`pae.protocolos.edit`) e `GET .../comprovante` (`pae.protocolos.view`) com conferência de vínculo e resposta sem URL pública.
- [ ] Mostrar cada destinatário, pendência, data, SEI e comprovante no painel e histórico. Acrescentar contagem de pendências à listagem sem N+1 (`withCount`/agregado). Exibir explicitamente “destinatários ZAS não confirmados” quando couber. Rodar teste local, testes de listagem e `npm run build`; inspecionar PAE no navegador autenticado sem disparar comunicação real.
- [ ] Não stagear testes; revisar diff e commit da unidade: `✨ feat(pae): registro comprovado das comunicações oficiais`.

### Task 6: verificação integrada e fechamento da branch

**Arquivos:** nenhum arquivo de teste no commit; possíveis correções de código ficam no arquivo principal da unidade afetada. Spec e plano desta fase são commitados juntos, após revisão do usuário, em `📝 docs(pae): desenho e plano da admissibilidade`.

- [ ] No banco isolado, rodar migração da fase B, testes locais de cada entrega, os testes PAE já existentes e a suíte PHP completa. Em caso de falha, corrigir causa e repetir a verificação específica; remover arquivos de teste criados no trabalho antes do stage.
- [ ] Rodar `npm run build`, `git diff --check`, inspecionar `git status` e revisar que a migração foi consolidada e que os quatro eventos antigos do outbox preservam seus nomes/versões.
- [ ] Verificar no navegador o fluxo de triagem, decisão, pendências e registro com usuário autorizado; verificar 403 ou ausência de ação com usuário sem permissão. Não enviar e-mails externos de teste.
- [ ] Fazer revisão do diff completo contra `dev` antes de qualquer PR/merge e relatar evidência de testes/build e lacunas. Integrar à `dev` somente após solicitação específica do usuário.
