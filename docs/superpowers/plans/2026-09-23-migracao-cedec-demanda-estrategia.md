# Migração cedec-demanda → NewSDC: plano estratégico

> **Para implementação:** executar por entregas verificáveis na branch `codex/feat/migracao-cedec-demanda`. Não fazer merge antes da homologação nas réplicas. Este plano substitui a premissa de que o trabalho se resume a refatorar `Task` e três páginas Vue.

**Objetivo:** incorporar Chamados/Demandas, Inventário e Acessos do `cedec-demanda` ao NewSDC, preservando dados, regras e fluxos úteis, com domínio separado, interfaces reutilizáveis e autorização própria do NewSDC.

**Arquitetura:** três contextos (`Demandas`, `Inventario`, `Acessos`) com Controllers HTTP finos, FormRequests, serviços de aplicação, contratos de repositório, modelos de domínio/persistência e adaptadores externos. Compartilhar identidade (`User`), arquivos, notificações e componentes Atomic Design do NewSDC; comunicação entre contextos por casos de uso/eventos, sem chamadas diretas entre Controllers.

**Tecnologias:** Laravel 12 / PHP 8.3+, PostgreSQL, Vue 3, Inertia, Vite, Spatie Permission. A máquina local expõe apenas PHP 8.1; testes PHP exigem réplica ou runtime 8.3+. A análise abaixo é de código-fonte, sem acesso aos bancos de produção.

## Restrições globais

- Aplicar DRY e SOLID. Não transportar Controllers, views ou migrations legadas literalmente.
- Sem emojis no código; gitmoji apenas no título dos commits, em pt-BR.
- Cada commit representa uma entrega coerente; testes temporários não entram em commit.
- Qualquer ajuste de uma migration nova deve ser consolidado na migration principal dessa tabela antes de ela ser aplicada nas réplicas.
- Preservar mudanças já existentes no checkout `dev`; desenvolver somente no worktree da feature.
- Não alterar documentação não relacionada. `ENGINEER.md` e `PAPIROS.md` não foram encontrados nos dois repositórios durante esta análise; localizar os originais antes de decisões finas de domínio, se existirem.
- Zen MCP/Gemini/Notion não estão disponíveis nesta sessão. A revisão deste plano foi feita por leitura direta dos repositórios.

## Diagnóstico confirmado no código

| Contexto | Origem `cedec-demanda` | NewSDC atual | Lacuna |
|---|---|---|---|
| Chamados | `routes/chamados.php`, 4 Controllers, `Chamado`, `Comentario`, `Anexo`, `HistoricoChamado`, `Assunto`, `ChamadoCategoria`, dashboard e páginas próprias | `app/Modules/Demandas`, `tasks`, 3 páginas Vue, eventos e SLA parciais | Tipos/status e campos não equivalentes; faltam endpoints efetivos de comentário, anexo, exportação, atribuição, status e histórico. `routes/modules/demandas.php` aponta para métodos ausentes em `DemandaController`. |
| Inventário | 7 Controllers, equipamentos, categorias, estações, empréstimos, movimentações, registros, diretorias/superintendências, etiquetas e exportação | `InventarioController` e uma página, com `mockEquipamentos()` e números fixos | Persistência e praticamente todos os casos de uso faltam. O módulo `Estoque`/`AjudaHumanitaria` do NewSDC é outro contexto e não substitui este inventário de TI. |
| Acessos | `CadastroAcessoController`, cadastro, aprovação, status AD, reset/desbloqueio, sincronizações e 4 páginas | Nenhum contexto/página equivalente encontrado | Domínio, persistência, autorização e integração externa faltam. O cadastro de acesso não deve ser confundido com a conta `User` do NewSDC. |
| Identidade e ACL | IDs locais de `users`, `role:admin`, integração AD e comandos externos | `App\Models\User`, permissões em `config/permissions.php` | IDs de usuários não são portáveis; papéis e permissões exigem mapeamento. O seeder `DemandasPermissionsSeeder` usa slugs diferentes dos de `config/permissions.php`. |
| Dados | migrations legadas sucessivas e tabelas MySQL | PostgreSQL e migrations `tasks` já existentes | Exige esquema de destino, ETL idempotente, tabela de correspondência de IDs, conciliação e anexos. |

### Evidência funcional importante

- `ChamadoController` legado implementa listagem, criação, detalhe, comentários, atualização, automação, exportação e anexos. `destroy()` está vazio; não tratá-lo como funcionalidade pronta.
- `MovimentacaoController` legado possui remanejamento e devolução individuais/em lote, edição de lote, exportação e abertura de chamado a partir do lote. Esse último fluxo é a integração real entre Inventário e Demandas.
- `CadastroAcessoController` legado chama serviços AD por HTTP e executa `shell_exec`. No destino, toda operação AD deve passar por uma porta/adaptador configurável, auditável e testável, com autorização e timeout; não levar comandos de shell para o Controller.
- As capturas fornecidas representam Chamados, equipamentos, estações, movimentações e Acessos. Elas são referência de fluxos e densidade de informação, não uma especificação para copiar o visual antigo.

## Decisões de modelagem antes da carga

1. **Chamado e Demanda:** manter `Demanda` como entidade de destino, mas criar um mapeamento explícito de `chamados.id` para `tasks.id`. Não converter `aviso`, `alerta`, `informação` e `lembrete` automaticamente em `incidente`, `solicitacao`, `mudanca` ou `problema`: esses conjuntos têm semânticas diferentes. Registrar tabela de equivalências aprovada e preservar o tipo original em metadado de importação quando necessário.
2. **Status e datas:** mapear `em aberto`, `em andamento`, `concluido` e estados adicionais reais do banco para a máquina de estados do NewSDC. Preservar abertura, fechamento e histórico originais; a carga não deve simular transições nem disparar notificações/SLA retroativos.
3. **Pessoas:** resolver usuários por chave estável verificada (CPF, MASP, login ou e-mail, conforme disponibilidade), manter tabela `legacy_user_id → new_user_id` e enviar ambiguidades para fila de conciliação. Nunca relacionar registros só por IDs numéricos iguais.
4. **Inventário:** equipamentos e estações são entidades próprias. Estações de trabalho do legado não são `estacoes_meteorologicas`/Cemaden/Inmet. Patrimônio identifica equipamento quando válido; casos duplicados entram em quarentena, sem sobrescrever silenciosamente.
5. **Acessos:** `CadastroAcesso` representa solicitação/registro operacional e estado da conta AD. `User` representa autenticação no NewSDC. Vincular opcionalmente, sem usar o cadastro como fonte automática de permissões.
6. **Operações, Arquivos e Auditoria:** aparecem no menu legado, mas não foram incluídos no escopo confirmado de Chamados, Inventário e Acessos. Só trazer dependências usadas por esses três contextos; um projeto separado cobre o restante.

## Entregas em ordem

### E0 — Contrato de migração e inventário de dados

**Arquivos de referência:** `cedec-demanda/routes/{chamados,inventario,acessos}.php`, Controllers, Models, Requests, migrations e páginas correspondentes; `NewSDC/SDC/app/Modules/{Demandas,Inventario}`, `config/permissions.php`.

- [ ] Catalogar cada tela, rota, ação, regra, tabela, coluna, arquivo armazenado, job, exportação e integração externa. Marcar como migrar, adaptar, substituir ou descartar com justificativa.
- [ ] Construir matriz de campos origem → destino, incluindo enum/status, nullable, chaves externas, anexos e fuso horário.
- [ ] Medir volumes e qualidade nas réplicas: contagens por tabela/status, usuários órfãos, patrimônio duplicado, CPF duplicado, caminhos de anexo inexistentes.
- [ ] Definir fonte de dados autorizada e método de leitura somente leitura para a carga. Congelar um snapshot identificável para testes de reconciliação.

**Aceite:** matriz de cobertura aprovada e amostra real de cada tipo de registro representada sem perda silenciosa.

### E1 — Base transversal no NewSDC

- [ ] Definir contratos de importação, `legacy_id`/`legacy_source` ou tabela de correspondência por contexto, idempotência e relatório de rejeições.
- [ ] Ajustar permissões atômicas no catálogo existente (`config/permissions.php`) e seeders, com testes de escopo por papel e por dono. Verificar os slugs existentes antes de adicionar novos.
- [ ] Definir porta de anexos/armazenamento e adaptador de notificações com política explícita de não notificar durante ETL.
- [ ] Criar comandos de importação em lotes com `--dry-run`, retomada, checkpoint, logs de rejeição e conciliação; nunca importar a partir de requisição web.

**Aceite:** duas execuções do mesmo lote geram o mesmo estado, sem duplicações; erros ficam auditáveis.

### E2 — Demandas/Chamados completos

**Destino principal:** `SDC/app/Modules/Demandas/` e `SDC/resources/js/Pages/Demandas/`.

- [ ] Corrigir primeiro a baseline: rotas para métodos inexistentes, serviço antigo com referências a `Task`, FKs dos modelos `task_*`, validação, autorização por registro e vazamento de comentários internos.
- [ ] Separar casos de uso de criar, atribuir, comentar, anexar, mudar status, resolver/reabrir e exportar; workflow central para transições e eventos após commit.
- [ ] Migrar categorias/assuntos e dashboard sem acoplar `tasks` a IDs legados; fornecer filtros, ordenação, paginação e estatísticas reais.
- [ ] Adaptar listagem, criação e detalhe aos componentes Atomic Design existentes; incluir timeline, anexos e estados reais. Eliminar dados mockados e botões sem ação.
- [ ] Importar chamados, histórico, comentários e anexos; testar amostras com criador, solicitante, destinatário, assunto, prioridade, datas e privacidade.

**Aceite:** abrir, acompanhar, atribuir, comentar, anexar, resolver, reabrir, filtrar e exportar funcionam para registros novos e migrados; contagens reconciliadas.

### E3 — Inventário de TI

**Destino principal:** expandir `SDC/app/Modules/Inventario/`, `routes/modules/inventario.php`, `Pages/Inventario/`. Criar as migrations principais por agregado no próprio módulo; consolidar ajustes nelas antes da primeira execução na réplica.

- [ ] Modelar categorias, estações, equipamentos, unidade/diretoria, empréstimos, movimentações e registros. Definir invariantes de disponibilidade, ocupação, quantidade, origem/destino e devolução.
- [ ] Substituir `mockEquipamentos()` e estatísticas fixas por repositórios/consultas paginadas reais.
- [ ] Entregar CRUD e filtros de equipamento/estação; depois empréstimo, remanejamento, devolução e edição de lote com transações e bloqueio de concorrência.
- [ ] Conectar “abrir chamado do lote” a um caso de uso público de Demandas, com referência de origem idempotente.
- [ ] Adaptar páginas de dashboard, equipamentos, estações e movimentações ao Design System; exportação e etiquetas como entregas separadas e verificáveis.
- [ ] Importar catálogo, estações, equipamentos, histórico e movimentações em ordem de dependência.

**Aceite:** nenhuma listagem apresenta dados fictícios; estado de cada equipamento bate com a última movimentação válida; operações em lote não deixam estado parcial.

### E4 — Acessos e AD

**Destino:** novo `SDC/app/Modules/Acessos/`, `routes/modules/acessos.php`, `Pages/Acessos/`.

- [ ] Criar agregado `CadastroAcesso`/`SolicitacaoAcesso`, FormRequests, policy e repositório. Definir estados de aprovação e relação opcional com `User`.
- [ ] Migrar listagem, filtros, cadastro, edição, detalhe e aprovação com trilha de auditoria.
- [ ] Criar contrato `DiretorioCorporativo` e adaptadores `HttpDiretorioCorporativo`/fake de teste para consulta de bloqueio, reset e desbloqueio. Segredos e URL vêm de configuração; sem `shell_exec`, IP fixo ou senha em logs.
- [ ] Executar operações externas por job com timeout, retry controlado, idempotência e registro de resultado. Separar “solicitado”, “enviado” e “confirmado pelo AD”.
- [ ] Importar cadastros e atributos AD históricos sem executar ações AD durante a carga; reconciliar CPF/login/e-mail e conflitos.

**Aceite:** autorização por ação, dados pessoais protegidos, falha de AD não altera falsamente o estado local, e registros migrados não acionam reset/desbloqueio.

### E5 — Homologação nas réplicas e corte

- [ ] Rodar migrations principais e ETL em réplica limpa. Repetir ETL para comprovar idempotência.
- [ ] Comparar contagens, somas, estados, amostras e anexos por contexto; publicar relatório de diferenças e tratar rejeições.
- [ ] Executar testes de domínio/HTTP, autorização, integração fake AD e build Vue; validar fluxos das capturas em desktop e mobile.
- [ ] Fazer ensaio de corte com janela, snapshot, delta, rollback e responsáveis. Só após aprovação das réplicas, preparar merge da branch na base escolhida.

**Aceite:** checklist de produto e dados assinado, nenhuma rejeição sem decisão, rollback ensaiado, branch atualizada e CI verde. Merge e implantação são passos distintos.

## Riscos que exigem atenção antecipada

1. **Incompatibilidade semântica de tipos/status:** uma conversão simples distorce relatórios e workflow.
2. **Identidade:** usuário órfão ou mapeado errado expõe chamados, equipamentos e dados de acesso à pessoa errada.
3. **Dados sensíveis e AD:** CPF, credenciais e ações externas exigem ACL granular e trilha; comandos legados de shell não podem ser copiados.
4. **Concorrência no Inventário:** movimentação e devolução em lote precisam ser atômicas e manter disponibilidade consistente.
5. **SLA histórico:** recalcular automaticamente demandas antigas criaria violações e notificações artificiais.

## Sequência de commits sugerida

`🔧 config(migracao): contratos e permissões dos contextos` → `🐛 fix(demandas): fluxos e autorização da baseline` → `✨ feat(demandas): cobertura do legado e importação` → `✨ feat(inventario): catálogo e movimentações` → `✨ feat(acessos): cadastro e adaptador AD` → `🗃️ db(migracao): carga e reconciliação` → `🎨 style(modulos): telas Atomic Design`. Ajustar agrupamento para que cada commit entregue uma unidade completa; nenhum arquivo de teste temporário entra em commit.
