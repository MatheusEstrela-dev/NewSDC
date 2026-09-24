# Demandas — paridade com Chamados do cedec-demanda

> Spec de design — 24/09/2026. Branch `feat/demandas-paridade-chamados` (a partir de `dev` em `ce603671`).
> Primeira fatia do plano estrategico `docs/superpowers/plans/2026-09-23-migracao-cedec-demanda-estrategia.md` (entrega E2). Inventario de TI e Acessos serao specs seguintes.

## 1. Objetivo e criterios de sucesso

Substituir o modulo Chamados do `cedec-demanda` (branch `main`, fonte da paridade) pelo modulo `Demandas` do NewSDC, sem perda de fluxo nem de dados, mantendo o padrao DDD do backend e o Atomic Design do frontend.

Sucesso significa:

1. Todo fluxo do legado funciona no NewSDC: abrir com campos dinamicos por assunto, listar/filtrar/exportar, detalhar, editar descricao/assunto com autosave, comentar, transferir responsavel, anexar, resolver com datas, reabrir, automacao AD e dashboard com export quantitativo.
2. Os chamados legados (com historico, comentarios e anexos) sao importados por comando idempotente; origem = importados + rejeitados justificados.
3. Carga historica nao gera notificacao, SLA violado nem pontos no Ranking.
4. Demanda resolvida pontua no Ranking (atras de `ranking.habilitado`) para quem resolveu.
5. Nenhum dado mockado nas telas; zero overflow horizontal em 375 px e 840 px.

## 2. Decisoes tomadas

| # | Decisao | Motivo |
|---|---|---|
| D1 | Evoluir o modulo `Demandas` existente (abordagem A), guiado por matriz de paridade. | Dominio (Workflow, Guards, Events, Repository, catalogo) ja existe e esta no padrao. |
| D2 | Dominio ITIL (7 status, matriz impacto x urgencia, SLA) mantido; a UI mostra o fluxo curto do legado. | Preserva o poder do dominio sem mudar o habito da equipe. |
| D3 | Importacao = carga unica idempotente + delta no dia do corte. Sem sincronizacao continua. | Uma fonte de verdade. |
| D4 | Usuario legado nao mapeado com seguranca: chamado rejeitado no relatorio, nao importado com solicitante vazio. | Evita expor chamado a pessoa errada. |
| D5 | Primeiro comentario de nao-solicitante em demanda `aberta` leva a `em_progresso` e atribui o comentarista se nao houver responsavel. | Reproduz o legado e satisfaz o guard `ExigeAtribuicaoParaProgresso`. |
| D6 | Ranking credita "entrega aceita" (10 pontos) ao responsavel que resolveu. | Alinhado a `docs/superpowers/plans/2026-09-21-ranqueamento-ipcm.md`, secao 6. |
| D7 | Automacao AD configurada por assunto (`form_automacao`), executada via porta `DiretorioCorporativo` em job. Sem `shell_exec`, sem IP fixo. | Seguranca e testabilidade; porta compartilhada com a futura spec de Acessos. |

## 3. Dominio e dados

### 3.1 Projecao de status

Novo enum `App\Modules\Demandas\Enums\EtapaDemanda`, derivado de `StatusDemanda` (sem coluna no banco):

| Etapa (UI) | Status ITIL |
|---|---|
| `aberto` — Aberto | `aberta`, `em_analise` |
| `em_andamento` — Em andamento | `em_progresso`, `aguardando_terceiros` |
| `concluido` — Concluido | `resolvida`, `fechada` |
| `cancelado` — Cancelado | `cancelada` |

`StatusDemanda::etapa(): EtapaDemanda` e `EtapaDemanda::status(): array` (para filtro). O filtro da listagem aceita etapa; status ITIL fica disponivel apenas em filtro avancado para gestores.

### 3.2 Regras de negocio

| Regra | Implementacao |
|---|---|
| R1 Primeiro comentario inicia atendimento (D5). | `DemandaInteractionService::comentar`; transicao via `DemandaWorkflow::transitar`. Comentario do proprio solicitante nao muda status. |
| R2 Resolver exige `resolvido_em`; abertura pode ser ajustada. Abertura <= fechamento, nenhuma no futuro. | `ResolucaoDemandaData` + `ResolverDemandaRequest`; ajuste de abertura registrado na trilha com valor anterior. |
| R3 Reabrir: de `resolvida`/`fechada` para `em_progresso`, limpa `resolvido_em` e `tempo_total_resolucao`. | `DemandaWorkflow`; historico "Chamado REABERTO". |
| R4 Transferir responsavel. | `DemandaInteractionService::atribuir`; historico "Transferencia". |
| R5 Visibilidade: nao-gestor ve apenas demandas em que e solicitante, responsavel ou criador. | `EloquentDemandaRepository` + `DemandaPolicy`. |
| R6 Edicao: gestor, solicitante ou responsavel. | `DemandaPolicy::update`. |
| R7 Campos customizados validados contra `campos_dinamicos` do assunto. | `ValidadorCamposDinamicos` (servico de dominio) usado em abrir e atualizar. |

### 3.3 Mudancas de schema

Consolidadas nas migrations principais (regra 9), antes de qualquer aplicacao em replica:

- `2025_01_15_000001_create_tasks_table.php`: adicionar `criado_por_id` (FK `users`, nullable, `nullOnDelete`) e indice `(criado_por_id, created_at)`.
- Unificar `2026_09_23_000004_create_demanda_assuntos.php` e `2026_09_23_100000_create_demanda_categorias_table.php` numa unica migration que cria `demanda_categorias` antes de `demanda_assuntos` (corrige FK em instalacao limpa). Adicionar `demanda_assuntos.form_automacao` (jsonb, nullable).
- `2026_09_23_000003_create_cedec_demanda_import_maps.php`: sem mudanca de estrutura; `status` passa a usar `pendente|importado|rejeitado`.

Para bancos onde essas migrations ja rodaram (homolog), o plano define migration de ajuste controlada; nunca `migrate:fresh` sobre dados preservados.

### 3.4 Historico

Usar `TrilhaDeAcoes` / `task_audit_logs` existentes. Rotulos iguais ao legado: `Criou o chamado`, `Transferencia`, `Alteracao de Status`, `Alteracao de Status Automatica`, `Edicao`, `Novo Anexo`, `Automacao solicitada|confirmada|falhou`, `Chamado REABERTO`.

### 3.5 Correcoes de base incluidas

- `DemandasPermissionsSeeder` alinhado aos slugs `demandas.chamados.*` de `config/permissions.php`.
- `SlaVerificadorCommand` registrado no provider e agendado.
- Remocao do frontend mock `resources/js/domain/demandas/*` e `resources/js/infrastructure/demandas/*`.

## 4. Backend

Padrao: FormRequest -> Controller fino -> DTO `fromRequest()` -> Service/Workflow (`DB::transaction`) -> Repository -> Resource. `declare(strict_types=1)`, injecao `private readonly`.

### 4.1 Casos de uso

| Caso de uso | Classe | Notas |
|---|---|---|
| Abrir | `Services/DemandaWriteService::abrir(CriarDemandaData)` | Grava `criado_por_id` e `solicitante_id`; valida campos (R7); inicia SLA; historico. Publico para outros contextos (ex.: Inventario). |
| Atualizar descricao/assunto | `DemandaWriteService::atualizar(AtualizarDemandaData)` | Autosave (PATCH parcial). |
| Comentar | `DemandaInteractionService::comentar` | R1; comentario `interno` oculto ao solicitante. |
| Transferir | `DemandaInteractionService::atribuir` | R4. |
| Resolver / reabrir / cancelar | `Domain/Workflows/DemandaWorkflow::transitar` + `DTOs/ResolucaoDemandaData` | R2, R3. |
| Anexar / baixar | `Services/DemandaAnexoService` | png, jpg, pdf, xlsx, xls, csv, txt; 10 MB; disk `config('demandas.anexos.disk')`. |
| Automatizar | `Services/ExecutarAutomacaoDemanda` + `Jobs/ExecutarAutomacaoDemandaJob` | Secao 7. |
| Exportar listagem | `Services/DemandaCsvExporter` (existente) | Respeita filtros e visibilidade. |
| Dashboard | `Queries/DemandaDashboardQuery` + `DTOs/DashboardDemandasData` | Contadores (abertas, em andamento, resolvidas hoje, resolvidas total), serie 10 meses, 5 recentes; export quantitativo por mes/assunto. |

### 4.2 Rotas (`routes/modules/demandas.php`)

Todas em `web` + `auth`, cada uma com `can:`.

| Metodo | URI | Nome | Permissao |
|---|---|---|---|
| GET | `/demandas/dashboard` | `demandas.dashboard` | `demandas.dashboard.view` |
| GET | `/demandas/dashboard/export` | `demandas.dashboard.export` | `demandas.chamados.export` |
| GET | `/demandas` | `demandas.index` | `demandas.chamados.view` |
| GET | `/demandas/nova` | `demandas.create` | `demandas.chamados.create` |
| POST | `/demandas` | `demandas.store` | `demandas.chamados.create` |
| GET | `/demandas/{demanda}` | `demandas.show` | `demandas.chamados.view` |
| PATCH | `/demandas/{demanda}` | `demandas.update` | `demandas.chamados.edit` |
| POST | `/demandas/{demanda}/comentarios` | `demandas.comentarios.store` | `demandas.chamados.view` |
| POST | `/demandas/{demanda}/anexos` | `demandas.anexos.store` | `demandas.chamados.edit` |
| GET | `/demandas/{demanda}/anexos/{anexo}` | `demandas.anexos.download` | `demandas.chamados.view` |
| POST | `/demandas/{demanda}/atribuir` | `demandas.atribuir` | `demandas.chamados.manage` |
| POST | `/demandas/{demanda}/status` | `demandas.status` | `demandas.chamados.edit` |
| POST | `/demandas/{demanda}/resolver` | `demandas.resolver` | `demandas.chamados.resolver` |
| POST | `/demandas/{demanda}/automacao` | `demandas.automacao` | `demandas.chamados.automatizar` |
| GET | `/demandas/export` | `demandas.export` | `demandas.chamados.export` |

Autorizacao por registro sempre via `DemandaPolicy` alem do slug. Rotas `admin/demandas/*` do catalogo permanecem; validacao inline de `CatalogoDemandaController` passa para FormRequests e o assunto ganha edicao de `form_automacao`.

### 4.3 Permissoes novas (`config/permissions.php`)

`demandas.chamados.resolver`, `demandas.chamados.automatizar`, `demandas.dashboard.view`. Conferir com `AuditPermissionsCommand` e atribuir em `role_permissions`.

### 4.4 Eventos e integracoes

- `DemandaCriadaV1`, `StatusAlteradoV1`, `DemandaResolvidaV1`, `ComentarioAdicionadoV1` despachados apos commit (existentes).
- `App\Modules\Ranking\Adapters\DemandaAdapter` (implementa `ModuleAdapter`), marco `demandas.entrega_aceita`, base 10, creditado ao `atribuido_para_id` no momento da resolucao; chave canonica `demanda:{id}:entrega_aceita` (reabrir + resolver nao gera segundo premio; reabertura estorna). Registrado em `RankingServiceProvider` atras de `ranking.habilitado`; regra entra em `RankingRegraSeeder`.
- `DemandaNotificacaoObserver` mantido; silenciado durante importacao (secao 6).
- Broadcast `RecursoAtualizado` no canal `listagem.demandas` para tempo real.

## 5. Frontend (Atomic Design)

Page fina -> Template -> Organisms -> Molecules/Atoms. Componentes canonicos obrigatorios: `Organisms/PageHeader`, `Molecules/Statistics/StatCardsGrid` + `StatCard`, `Molecules/Filter/FilterSection`, `Organisms/Table/ResponsiveTable`, `Molecules/Navigation/Pagination`, `Organisms/ListContainer`, `Organisms/FormSection`, `Molecules/Upload/*`, ConfirmDialog. Sem clones por modulo.

### 5.1 Pages e Templates

| Page (`Pages/Demandas/`) | Template (`Templates/Demandas/`) | Conteudo |
|---|---|---|
| `DemandasDashboard.vue` | `DemandasDashboardTemplate.vue` | StatCards clicaveis (filtro rapido), grafico 10 meses (ApexCharts), 5 recentes, botao export quantitativo. |
| `DemandasIndex.vue` | `DemandasIndexTemplate.vue` | Filtros: busca, assunto, etapa, prioridade, solicitante, responsavel, criado por, data inicial/final. Colunas: ID, Titulo, Assunto, Categoria, Quem abriu, Solicitante, Responsavel, Status, Prioridade, Abertura, Fechamento, Acoes. Mobile em cards. |
| `DemandasCreate.vue` | `DemandasFormTemplate.vue` | Assunto -> campos dinamicos. |
| `DemandasShow.vue` | `DemandasShowTemplate.vue` | 2/3 + 1/3 conforme captura do chamado #199. |
| `Catalogo.vue` (existente) | — | Editor de `form_automacao` no assunto. |

### 5.2 Organisms (`Components/Organisms/Demandas/`)

- `Show/DemandaDescricaoCard` — textarea com autosave no blur.
- `Show/DemandaAssuntoCard`
- `Show/DemandaAbasAtividade` — abas Comentarios (n) / Historico (n), com `DemandaComentarioForm` e `DemandaHistoricoLista`.
- `Show/DemandaEnvolvidosCard` — solicitante; select de responsavel (transferencia).
- `Show/DemandaInformacoesCard` — etapa, categoria, abertura, indicador de SLA quando houver prazo.
- `Show/DemandaAnexosCard`
- `Show/DemandaResolverCard` — datas com validacao cruzada; "Confirmar resolucao"; "Reabrir" quando concluida.
- `Show/DemandaAutomacaoButton` — so com automacao no assunto e permissao; mostra estado do job.
- `Filters/DemandasFiltersSection`, `Table/DemandasTable`, `Grid/DemandasGrid`, `Statistics/DemandasStatisticsCards`.

### 5.3 Molecules, Atoms, Composables

- `Molecules/Demandas/CampoDinamico.vue` (texto, numero, select, data, usuario), `Molecules/Demandas/DemandaHistoricoItem.vue`.
- `Atoms/Demandas/DemandaStatusBadge` passa a exibir etapa; `DemandaPrioridadeBadge` exibe Baixa/Media/Alta com faixa ITIL no tooltip.
- `Composables/demandas/{useDemandaFilters,useDemandaAutosave,useDemandaResolucao,index}.js`; tempo real com `useAtualizacaoAoVivo({ canal: 'listagem.demandas' })`.
- `NovaDemandaModal` passa a reusar `DemandasFormTemplate` para criacao rapida, ou e removido se nao houver chamador.
- Navegacao: `Sidebar.vue`, `CommandPalette.vue`, `BottomNavigation.vue` com Dashboard e Listar; `moduleIcons.demandas` existente.

### 5.4 Regras de layout

Raiz da pagina sem `p-*`, com `pb-8`; ritmo `space-y-6` (filhos com `:espaco-inferior="false"`). Dark mode por classe. Breakpoints via `useMobile`. Zero overflow horizontal em 375 px e 840 px.

## 6. Importacao do legado

### 6.1 Fonte

Conexao somente leitura `cedec_demanda_legacy` (MySQL) em `config/database.php`, credenciais via `.env`. Disk `legado_demandas` (somente leitura) para os anexos, no modelo do `legado_rat`.

### 6.2 Comando

`php artisan demandas:importar-legado {--dry-run} {--etapa=} {--desde=} {--lote=200}` em `Modules/Demandas/Console/`. Cada etapa e uma classe em `Modules/Demandas/Importacao/` implementando `EtapaImportacao` (`nome()`, `executar(ContextoImportacao): RelatorioEtapa`). Idempotencia por `cedec_demanda_import_maps` (`source_table + source_id` unico, `source_hash` para detectar alteracao).

| Ordem | Etapa | Origem -> destino | Regras |
|---|---|---|---|
| 1 | Usuarios | `users` -> mapa apenas | CPF, depois login/MASP, depois e-mail. Nao cria contas. Ambiguo ou sem correspondencia: `rejeitado` com motivo. |
| 2 | Catalogo | `chamados_categorias` -> `demanda_categorias`; `assuntos` -> `demanda_assuntos` | Nome como chave natural; reaproveita existentes; leva `campos_dinamicos` e `form_automacao`. |
| 3 | Chamados | `chamados` -> `tasks` | Status: em aberto -> aberta; em andamento -> em_progresso; concluido/resolvido/fechado -> resolvida; reaberto -> em_progresso. Prioridade: baixa -> baixo/baixa; media -> medio/media; alta -> alto/alta. `tipo` destino `solicitacao`; tipo original, `grupo`, `obs`, `encaminhado_para_id` e id legado em `campos_customizados._legado`. `dados_adicionais` -> `campos_customizados`. Preserva `created_at`, `data_fechamento` -> `resolvido_em`, calcula `tempo_total_resolucao`. Protocolo com ano original. Qualquer pessoa obrigatoria nao mapeada: chamado rejeitado (D4). |
| 4 | Historico | `historico_chamados` -> `task_audit_logs` | Data e autor originais. |
| 5 | Comentarios | `comentarios` -> `task_comments` | `interno = false`. |
| 6 | Anexos | `anexos` -> `task_attachments` + arquivo | Copia de `legado_demandas` para `demandas/{id}` com checksum; arquivo ausente: registro marcado indisponivel e listado no relatorio. |

### 6.3 Protecoes

- `ContextoImportacao` ativo silencia observer de notificacao, inicio de SLA, eventos para o Ranking e broadcast.
- Transacao e checkpoint por lote; retomada a partir do ultimo checkpoint.
- `--dry-run` gera relatorio sem gravar.
- Nenhuma escrita na conexao legada.

### 6.4 Relatorio e corte

Relatorio por etapa: lidos, importados, atualizados, ignorados (hash igual), rejeitados com motivo; contagem por status origem x destino. Aceite: origem = importados + rejeitados justificados.

Corte: carga completa em homolog -> validacao -> carga completa em producao -> no dia do corte `--desde=<timestamp>` para o delta -> legado em modo somente leitura.

## 7. Automacao AD

- `demanda_assuntos.form_automacao`: `{ "acao": "desbloquear" | "ativar" | "resetar", "campo_login": "<chave em campos_dinamicos>" }`.
- `App\Modules\Acessos\Contracts\DiretorioCorporativo` ganha `solicitarAtivacao(string $login, string $operationId): array`; `HttpDiretorioCorporativo` implementa usando `services.corporate_directory.{url,token}`.
- `ExecutarAutomacaoDemanda`: autoriza (`demandas.chamados.automatizar` + policy), le o login de `campos_customizados[campo_login]`, valida formato sAMAccountName, registra "Automacao solicitada" e despacha `ExecutarAutomacaoDemandaJob`.
- Job: timeout 15 s, 2 tentativas com backoff, idempotente por `operationId = demanda:{id}:{acao}:{sequencia}`. Resultado no historico ("confirmada" / "falhou: motivo"); nenhuma senha em log ou historico. Falha nao altera status da demanda.
- Demandas depende apenas do contrato; nenhuma chamada a Controllers de Acessos.

## 8. SLA

- `SlaEngine::iniciarSlaParaDemanda` chamado em `abrir`, exceto com `ContextoImportacao` ativo.
- `demandas:verificar-slas` registrado no `DemandasServiceProvider` e agendado a cada 5 minutos em `routes/console.php` com `withoutOverlapping()`.
- Sem `SlaDefinicao` aplicavel: demanda sem prazo, sem violacao, sem erro.

## 9. Tratamento de erros

| Situacao | Resposta |
|---|---|
| Transicao invalida | `TransicaoProibidaException` -> 422 com mensagem do guard; toast. |
| Datas de resolucao inconsistentes | 422 do FormRequest com mensagem da captura ("A data nao pode ser posterior ao fechamento..."). |
| Campo dinamico invalido | 422 por campo. |
| Upload invalido | 422 (tipo/tamanho). |
| Acesso a demanda alheia (inclui anexo) | 403 via policy. |
| Diretorio AD indisponivel | Job falha apos retries; historico "falhou"; estado inalterado. |
| Importacao com registro invalido | Registro `rejeitado` com motivo; lote segue. |

## 10. Testes

Executados no host conforme ambiente do projeto; arquivos de teste nao entram em commit (regra 10).

- Unit: `EtapaDemanda`, mapeamentos de status/prioridade do legado, `DemandaWorkflow` (resolver, reabrir, cancelar), R1, `ValidadorCamposDinamicos`.
- Feature: fluxos por papel (gestor, solicitante, terceiro), autosave, anexos, resolucao com datas, visibilidade, export, dashboard, permissoes.
- Automacao com fake de `DiretorioCorporativo`: sucesso, timeout, retry, idempotencia.
- Importacao contra fixture MySQL: duas execucoes = mesmo estado; usuario nao mapeado -> rejeitado; nenhuma notificacao/SLA/ponto gerado.
- Ranking: `DemandaResolvidaV1` gera um lancamento de 10 para quem resolveu; reabrir + resolver nao duplica; reabertura estorna.
- Frontend: `npm run build` limpo; Playwright 375/840 px sem overflow em Dashboard, Index, Create e Show.

## 11. Fora do escopo

Inventario de TI (incluindo "abrir chamado do lote", que consumira `DemandaWriteService::abrir`), modulo Acessos completo, Operacoes/Kit Recomeco, Arquivos, Contatos, Auditoria generica e notificacao por e-mail via Outlook COM (substituida pelas notificacoes existentes do NewSDC).

## 12. Referencias

- Legado: `cedec-demanda/routes/chamados.php`, `app/Http/Controllers/Chamado/*`, `app/Models/Chamado/*`, `resources/js/Pages/Chamados/*`.
- NewSDC: `SDC/app/Modules/Demandas/*`, `SDC/app/Modules/Acessos/Contracts/DiretorioCorporativo.php`, `SDC/app/Modules/Ranking/Adapters/*`, `SDC/config/permissions.php`, `SDC/routes/modules/demandas.php`, `.claude/skills/{backend,frontend,database}`.
- Planos: `docs/superpowers/plans/2026-09-23-migracao-cedec-demanda-estrategia.md`, `docs/superpowers/plans/2026-09-21-ranqueamento-ipcm.md`.
- `PAPIROS.md` e `ENGINEER.md` nao foram encontrados; as skills do projeto foram usadas como regra. Zen MCP indisponivel nesta sessao.
