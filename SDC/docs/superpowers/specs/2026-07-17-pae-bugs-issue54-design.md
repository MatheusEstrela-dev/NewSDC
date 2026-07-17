# PAE — Correções da Issue #54: Protocolo, Anexos, Delegação e Notificações

- **Data:** 2026-07-17
- **Issue:** [MatheusEstrela-dev/NewSDC#54](https://github.com/MatheusEstrela-dev/NewSDC/issues/54)
- **Escopo:** somente o NewSDC (`SDC/`). O legado gestaocedec serve apenas como referência conceitual de negócio.
- **Abordagem aprovada:** meio-termo — correções cirúrgicas no frontend + novas peças de backend apenas onde não existe nada. Refatoração permitida em `app/Modules/Pae/Services` e `app/Modules/Pae/Controllers`.

## 1. Contexto e problemas identificados

| Item da issue | Causa/estado atual |
|---|---|
| Número do protocolo fora do padrão | `PaeProtocoloService::gerarNumProtocolo()` gera `dd.mm.aaaa.NNN` (sequencial anual, sem versão) |
| Protocolo não abre com dados/anexos carregados | `PaeFormularioController::edit()` renderiza `Inertia::render('PaeEdit', ...)`, mas a página `PaeEdit.vue` não existe |
| Abas sempre visíveis | `PaeForm.vue` monta as 5 abas de forma estática; `Pae.vue` não repassa `protocolo` nem `readOnly` ao `PaeForm` |
| Visualização permite edição | Ação `view` da tabela não abre em modo read-only; componentes só ignoram cliques em vez de ocultar botões |
| Sem histórico de anexos | `PaeFormAnexos` lista apenas anexos ativos, sem autor/data/removidos |
| Sem botão relacionar | `ActionButton.vue` da tabela de protocolos não tem ação de versão relacionada |
| Notificações 1/2/3 inexistentes | Tabelas `pae_notificacoes`/`pae_analises` existem sem model, service, scheduler ou e-mail |
| Status de análise ausente | Nenhum campo derivado exposto na listagem/detalhe |

## 2. Regras de negócio (decididas com o usuário)

1. **Número do protocolo:** `dd.mm.aaaa-XXXX-VVV` (ex.: `17.07.2026-0003-001`).
   - `dd.mm.aaaa` = data de criação;
   - `XXXX` = sequencial diário, 4 dígitos, zero-padded, reinicia a cada dia;
   - `VVV` = versão, `001` na criação; incrementa em versões relacionadas (padrão RAT), mantendo referência e histórico do protocolo original.
2. **Notificações 1/2/3:** prazos fixos de **30 dias por ciclo**.
   - 1ª emitida **manualmente** pelo analista (após análise/apontamentos), informando nº SEI;
   - sem devolutiva em 30 dias → sistema emite a próxima automaticamente (2ª e depois 3ª);
   - 3ª vencida sem devolutiva → protocolo vai para **SUSPENSO** automaticamente.
3. **Canal:** registro interno (tabela + timeline) **e e-mail**.
4. **Destinatário do e-mail:** coordenador do empreendimento (`email_coord`, cópia `email_coord_sub` de `pae_empntos`).
5. **Relacionar (RAT→PAE):** cria **versão relacionada** do protocolo (sufixo `002`, `003`, ...), como no RAT.
6. **Status de análise:** **derivado do protocolo**, sem tabela nova para exibição — *Em andamento* = status `NOTIFICACAO`/`ANALISE` com analista atribuído; *Concluída* = `APROVADO` em diante; exibir o analista responsável.
7. **Abas:** antes de delegar → somente *Informações Gerais* e *Anexos*; após delegar → todas (Objetivo e Contexto, Apontamentos Técnicos, Anexos, Conclusão).
8. **Visualização:** modo somente leitura, sem botões de ação.

## 3. Design

### 3.1 Backend — numeração e versionamento (`PaeProtocoloService`)

- Reescrever `gerarNumProtocolo()`:
  - executa em `DB::transaction` com `pg_advisory_xact_lock` (mesmo padrão de `RatProtocoloService`) para evitar corrida de sequencial;
  - calcula o próximo `XXXX` do dia varrendo `num_protocolo LIKE 'dd.mm.aaaa-%'`; protocolos no formato antigo (`dd.mm.aaaa.NNN`) são ignorados no cálculo e permanecem válidos (sem migração de dados);
  - retorna `dd.mm.aaaa-XXXX-001`.
- Novo método `relacionar(PaeProtocolo $base, User $user): PaeProtocolo`:
  - deriva prefixo `dd.mm.aaaa-XXXX` do protocolo base e incrementa o maior sufixo existente (`002`, `003`, ...);
  - novo protocolo nasce com status `NOVO`, herda `pae_empnto_id`/`empnto_search`, referencia o pai via `protocolo_origem_id`;
  - timeline nos dois protocolos (pai: "versão NNN criada"; filho: "criado a partir de <num do pai>").
- **Schema:** coluna `protocolo_origem_id` (nullable, FK para `pae_protocolos.id`) e `unique` em `num_protocolo` (se ainda não houver), **consolidados na migration principal** `2026_02_12_130000_create_pae_protocolos_table.php` (regra 9 do usuário).
- **Rota web:** `POST /pae/protocolo/{paeProtocolo}/relacionar` (`can:pae.protocolos.create`) → redirect Inertia para o novo protocolo.

### 3.2 Frontend — abertura, abas condicionais, read-only, anexos

- **Abertura com dados carregados:**
  - `PaeFormularioController::edit()` passa a renderizar `'Pae'` (página existente) em vez de `'PaeEdit'` (inexistente);
  - `Pae.vue` repassa `protocolo` e `readOnly` ao `PaeForm`;
  - o prop `protocolo` passa a incluir `analista_atual_id`, nome do analista e `arquivado`.
- **Abas condicionais (delegação trava abas):**
  - `tabConfig` em `PaeForm.vue` vira `computed`: sem `protocolo.analista_atual_id` → abas 1 (Informações Gerais) e 4 (Anexos); com analista → todas;
  - se a aba ativa ficar oculta, volta para a aba 1;
  - guard espelhado no backend: `updateObjetivoContexto`, `updateApontamentos`, `updateConclusao` e `finalizar` lançam `ValidationException` se o protocolo não tem analista delegado.
- **Modo visualização:**
  - ação `view` da tabela abre a rota de edição com `?modo=visualizar`; controller devolve `readOnly: true`;
  - auditar os 5 componentes de aba para que, com `readOnly`, os botões (salvar, adicionar/remover item, upload, remover anexo, finalizar) **não renderizem**.
- **Aba Anexos com histórico:**
  - seção "Histórico de arquivos" em `PaeFormAnexos`: todos os anexos incluindo soft-deleted (`withTrashed`), com nome do arquivo, autor, data e badge "Removido";
  - `formatForView` (PaeFormularioService) inclui anexos trashed + nome do usuário que anexou.
- **ActionButton relacionar:**
  - nova ação `relate` na `PaeProtocolosTable` → confirmação → `POST /pae/protocolo/{id}/relacionar` → redirect para o novo protocolo.

### 3.3 Backend — notificações 1/2/3 e status de análise

- **Models finos novos:** `PaeNotificacao` (`pae_notificacoes`) e `PaeAnalise` (`pae_analises`). A tabela `pae_datas_ciclos` fica fora do escopo (YAGNI): `pae_notificacoes.dt_notificacao`/`dt_devolutiva` é a fonte única de verdade por ciclo.
- **`PaeNotificacaoService`** (toda a regra; controllers só orquestram):
  - `emitir(PaeProtocolo, User, dados)` — valida (analista delegado, ciclo anterior fechado, máximo 3 ciclos, protocolo ativo), cria `PaeAnalise` do protocolo se não existir, cria `PaeNotificacao` do ciclo, registra timeline, dispara e-mail;
  - `registrarDevolutiva(PaeNotificacao, User, data)` — valida ciclo aberto e `dt_devolutiva <= hoje`, fecha o ciclo, timeline;
  - `processarVencimentos()` — para cada notificação aberta com `dt_notificacao + 30 dias < hoje`: ciclos 1–2 → emite o próximo automaticamente (registro + timeline + e-mail); ciclo 3 → `changeStatus(SUSPENSO)` com timeline explicando o motivo. Autor dos eventos automáticos = analista responsável pelo protocolo.
- **Enum:** adicionar transição `NOTIFICACAO → SUSPENSO` em `PaeProtocoloStatus::getAllowedTransitions()`.
- **Command:** `pae:verificar-notificacoes`, agendado diário em `routes/console.php`, chama apenas `processarVencimentos()`.
- **E-mail:** Mailable `PaeNotificacaoMail` (queued) → `email_coord` (cc `email_coord_sub`); conteúdo: nº protocolo, ciclo (1/2/3), prazo de 30 dias, nº SEI. Sem e-mail cadastrado → apenas registro interno + aviso na timeline.
- **Status de análise derivado:** accessor em `PaeProtocolo` (`analise_status`): `em_andamento` | `concluida` | `null`, conforme regra 6 da seção 2; exposto na listagem e no detalhe junto ao analista.

### 3.4 Rotas e API (REST + Inertia)

- **Web (Inertia, `routes/modules/pae.php`):**
  - `POST /pae/protocolo/{paeProtocolo}/relacionar` — `can:pae.protocolos.create`;
  - `POST /pae/protocolo/{paeProtocolo}/notificacoes` — emitir, `can:pae.protocolos.edit`;
  - `POST /pae/notificacoes/{paeNotificacao}/devolutiva` — `can:pae.protocolos.edit`;
  - respostas por redirect + flash.
- **API V1 (`app/Http/Controllers/Api/V1/Pae`, padrão da API de empreendimentos):**
  - `GET/POST /api/v1/pae/protocolos/{id}/notificacoes`;
  - `POST /api/v1/pae/notificacoes/{id}/devolutiva`;
  - `auth:sanctum` + mesmos gates, Resources JSON, documentação Swagger.

### 3.5 Frontend — painel de notificações

- Componente `PaeNotificacoesPanel` (Organism), visível somente após delegação:
  - status da análise (*Em andamento* / *Concluída*) + analista responsável;
  - ciclos 1/2/3 com datas de emissão, devolutiva, prazo restante e badge de vencido;
  - botões "Emitir notificação" (modal com nº SEI + obs) e "Registrar devolutiva" — ocultos em read-only.
- Listagem de protocolos: pill/coluna passa a exibir o analista responsável e o status de análise derivado.

### 3.6 Validações e erros

- **FormRequests novos:** `EmitirNotificacaoRequest` (`num_sei` obrigatório, `obs` opcional) e `RegistrarDevolutivaRequest` (`dt_devolutiva` obrigatória, `<= hoje`).
- **Regras de negócio** via `ValidationException` no service (mensagens em pt-BR): sem analista delegado; ciclo anterior aberto; máximo 3 ciclos; devolutiva em ciclo fechado; protocolo arquivado/terminal.
- Web → flash de erro Inertia; API → 422 JSON.

## 4. Testes (`tests/Feature/Pae/`)

- Numeração: formato novo, sequencial diário reiniciando por dia, coexistência com formato antigo.
- Relacionar: sufixo incrementa, herança de empreendimento, `protocolo_origem_id`, timelines nos dois protocolos.
- Gating de abas: updates de Objetivo/Apontamentos/Conclusão/finalizar bloqueados sem analista (backend).
- Notificações: emissão manual, `processarVencimentos()` com viagem no tempo (30/60/90 dias), suspensão após 3ª vencida, devolutiva fecha ciclo, `Mail::fake()`.
- API V1: contratos JSON dos novos endpoints.
- Scripts ad-hoc de verificação não entram em commit (regra 10 do usuário); os testes PHPUnit acima são do produto e entram normalmente.

## 5. Fora do escopo

- Migração de dados de números de protocolo antigos.
- Uso da tabela `pae_datas_ciclos`.
- Alterações no legado gestaocedec.
- Vínculo cruzado PAE↔RAT (a ação "relacionar" é versão do próprio protocolo PAE).
