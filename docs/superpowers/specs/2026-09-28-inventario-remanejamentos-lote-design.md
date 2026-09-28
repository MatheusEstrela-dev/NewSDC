# Inventario de TI — Remanejamentos em lote

> Spec de design — 28/09/2026. Branch `feat/inventario-remanejamentos-lote` (a partir de `dev` em `2dfbb010`).
> Fatias 1–7 da auditoria de Inventario/Acessos (entrega E3 do plano `docs/superpowers/plans/2026-09-23-migracao-cedec-demanda-estrategia.md`). Demais itens do Inventario (dashboard, categorias, historico, compartilhados, etiquetas) e Acessos ficam para specs seguintes.

## 1. Objetivo e criterios de sucesso

Levar para o NewSDC o remanejamento em lote do `cedec-demanda` (`app/Http/Controllers/Inventario/MovimentacaoController.php`, `resources/js/Pages/Inventario/Movimentacoes/Index.vue`), que e o fluxo de Inventario mais usado: mudar pessoas de estacao de trabalho levando seus equipamentos, registrar tudo como um lote, desfazer, editar, exportar a planilha, avisar a SEPLAG e registrar o chamado.

Sucesso significa:

1. Um remanejamento com varias pessoas e registrado numa transacao so; o estado de equipamentos e estacoes bate com o lote.
2. A listagem mostra os lotes (data/hora, pessoas envolvidas, "N itens movidos"), expansiveis ate o item.
3. Desfazer um lote restaura exatamente o estado anterior — equipamentos, estacoes e o que foi liberado — ou nao muda nada.
4. Editar, planilha XLSX, envio a SEPLAG por e-mail do NewSDC e chamado do lote (Demanda ja resolvida) funcionam a partir do lote.
5. Nenhum IP fixo, `shell_exec`, API Python, Outlook COM ou e-mail fixo no codigo.

## 2. Decisoes tomadas

| # | Decisao | Motivo |
|---|---|---|
| D1 | Entidade `Remanejamento` como cabecalho do lote (opcao A). | O lote tem autor, estado e vinculos (chamado, envio SEPLAG); lista pagina por lote. |
| D2 | A unidade do lote e a pessoa: usuario + estacao origem + estacao destino + condicao do destino + equipamentos. | E o modelo do legado (`processarMovimentos`). |
| D3 | Desfazer e tudo ou nada; restaura equipamentos, ocupacao das estacoes e equipamentos liberados. | O legado desfazia parcial e nao restaurava estacoes; aqui o lote e atomico. |
| D4 | Remanejar equipamento com remanejamento ativo marca o anterior como `substituida` (nao bloqueia). Emprestimo mantem a regra atual. | Remanejar de novo e normal; so o mais recente pode ser desfeito. |
| D5 | SEPLAG por e-mail do NewSDC (Mailable em fila), destinatarios em config; reenvio permitido e registrado. | Substitui a API Python com IP fixo e e-mail fixo. |
| D6 | Chamado do lote por botao: `DemandaWriteService::abrir` com assunto configurado, ja resolvido, um por lote (idempotente). | Integracao Inventario -> Demandas pelo caso de uso publico. |
| D7 | Planilha XLSX com `openspout/openspout`. | Excel nativo como o legado, dependencia leve. |

## 3. Dados

Consolidado na migration principal `2026_09_23_000001_create_inventario_ti_tables.php`; bancos ja migrados recebem `2026_09_28_100000_ajusta_inventario_remanejamentos.php` idempotente (hasTable/hasColumn).

### 3.1 `inventario_ti_remanejamentos` (novo)

`id` uuid PK; `registrado_por_id` FK users nullable nullOnDelete; `observacao` text nullable; `status` string(20) `ativo|desfeito` (index); `demanda_id` FK tasks nullable nullOnDelete, unique; `seplag_enviado_em` timestamp nullable; `seplag_enviado_por_id` FK users nullable; `seplag_envios` unsignedInteger default 0; `desfeito_em` timestamp nullable; `desfeito_por_id` FK users nullable; timestamps. Index `(status, created_at)`.

### 3.2 `inventario_ti_remanejamento_pessoas` (novo)

`id`; `remanejamento_id` uuid FK cascadeOnDelete; `usuario_id` FK users restrictOnDelete; `estacao_origem_id` FK estacoes nullable nullOnDelete; `estacao_destino_id` FK estacoes nullable nullOnDelete; `estacao_destino_usuario_anterior_id` FK users nullable (quem ocupava a estacao destino antes); `estacao_origem_ficou_vazia` boolean; `condicao_destino` string(60) nullable; timestamps. Unique `(remanejamento_id, usuario_id)`.

### 3.3 `inventario_ti_movimentacoes` (alterado)

- `lote_id` passa a FK para `inventario_ti_remanejamentos.id` (nullable, nullOnDelete) — mantem o nome da coluna existente.
- Novo `remanejamento_pessoa_id` FK nullable.
- Novo `situacao_origem` string(30) nullable (para restaurar).
- `tipo` aceita `emprestimo|remanejamento|liberacao`; `status` aceita `ativo|devolvido|substituida`.
- `condicao_destino` fica na pessoa (3.2), nao na movimentacao.

### 3.4 Config e dependencia

- `config/inventario.php`: `remanejamento.assunto_chamado` (nome do assunto de Demandas usado no chamado do lote; env `INVENTARIO_ASSUNTO_CHAMADO_LOTE`), `seplag.destinatarios` (lista, env `INVENTARIO_SEPLAG_DESTINATARIOS` separado por virgula), `seplag.assunto_email`.
- `composer require openspout/openspout` (a imagem ja roda `composer install`).

## 4. Regras de dominio

`App\Modules\Inventario\Services\RemanejamentoService` (DDD do modulo: DTOs `RemanejamentoData`/`PessoaRemanejadaData`, excecao de dominio `RemanejamentoProibido` com mensagem por item). Toda escrita numa `DB::transaction` com `lockForUpdate` em equipamentos e estacoes envolvidos, sempre em ordem crescente de id (evita deadlock entre lotes concorrentes).

### 4.1 Registrar

Entrada: `observacao` + lista de pessoas `{usuario_id, estacao_origem_id?, estacao_destino_id?, condicao_destino?, equipamento_ids[]}`.

Validacoes: pelo menos uma pessoa; usuario nao repete no lote; equipamento nao repete no lote; equipamento nao pode estar `baixado` nem `manutencao`; equipamento com emprestimo `ativo` bloqueia; estacao destino nao pode ser destino de duas pessoas do mesmo lote.

Efeitos, para cada pessoa:
1. Estacao origem: se ocupada pelo usuario, fica livre (`user_id = null`); `estacao_origem_ficou_vazia = true` salvo se outra pessoa do lote tiver essa estacao como destino.
2. Estacao destino: guarda o ocupante atual em `estacao_destino_usuario_anterior_id` e passa a `user_id = usuario`.
3. Equipamentos do usuario fora da lista (deixados para tras): cada um gera movimentacao `tipo=liberacao` (usuario_origem = usuario, estacao_origem = a atual, `situacao_origem`), fica `disponivel` sem usuario.
4. Cada equipamento da lista: se houver remanejamento `ativo` anterior dele, esse vira `substituida`; gera movimentacao `tipo=remanejamento`, `status=ativo`, com usuario/estacao/situacao de origem e destino; equipamento fica com o usuario, a estacao destino e `em_uso`.

### 4.2 Desfazer (tudo ou nada)

Permitido so com lote `ativo`. Para cada movimentacao `remanejamento` do lote: precisa ser a movimentacao nao-liberacao mais recente do equipamento e estar `ativa`; se qualquer uma falhar, lanca `RemanejamentoProibido` citando o equipamento e nada muda.

Efeitos (ordem inversa do registro): equipamentos voltam a usuario/estacao/situacao de origem; movimentacoes do lote viram `devolvido` com `data_devolucao`; a `substituida` imediatamente anterior de cada equipamento volta a `ativa`; equipamentos liberados voltam ao usuario/estacao/situacao de origem; estacao destino volta a `estacao_destino_usuario_anterior_id`; estacao origem volta a ser do usuario; lote vira `desfeito` com `desfeito_em/por`.

### 4.3 Editar

Permitido nas mesmas condicoes do desfazer. Na mesma transacao: desfaz e registra de novo com os dados novos, preservando o mesmo `id` de lote; `demanda_id` e envio SEPLAG sao preservados (a planilha reenviada reflete o novo conteudo).

### 4.4 Registrar chamado

`RegistrarChamadoDoLote`: se `demanda_id` ja existe, devolve a demanda existente. Senao, busca o assunto por nome (`config('inventario.remanejamento.assunto_chamado')`; ausente -> erro claro de configuracao), chama `DemandaWriteService::abrir` com titulo "Remanejamento de <data> — N itens", descricao listando pessoa/origem/destino/equipamentos, solicitante = quem registrou o lote, depois `DemandaStatusService::resolver` com abertura e fechamento = data do lote; grava `demanda_id`. Tudo numa transacao; lock no lote para impedir duas demandas concorrentes.

### 4.5 Planilha e SEPLAG

- `PlanilhaRemanejamento` gera XLSX (openspout) com as colunas do legado: Tipo de dispositivo (categoria do equipamento), Ramal, Patrimonio, Numero de serie, Origem (estacao), Ponto de rede origem, Origem ficara vazia (SIM/NAO), Destino (estacao), Ponto de rede destino, Condicao do destino (padrao "VAZIA"). Um item por equipamento remanejado (liberacoes nao entram). Celulas de texto passam pela protecao contra formula; `CsvSeguro` sai de `Modules/Demandas/Support` para `Modules/Shared/Support` (usado por Demandas e Inventario, Demandas atualizado para o novo namespace).
- `EnviarRemanejamentoSeplag`: exige destinatarios configurados (senao erro claro); envia `RemanejamentoSeplagMail` (ShouldQueue, template Vue no padrao de e-mail do projeto, anexo XLSX) para os destinatarios; incrementa `seplag_envios`, grava `seplag_enviado_em/por`. Envio de lote `desfeito` e proibido.

## 5. HTTP e permissoes

`routes/modules/inventario.php`, prefixo `inventario.`:

| Metodo | URI | Nome | Permissao |
|---|---|---|---|
| GET | `/inventario/movimentacoes` | `movimentacoes.index` (existente, passa a listar lotes) | `inventario.emprestimos.view` |
| GET | `/inventario/remanejamentos/novo` | `remanejamentos.create` | `inventario.remanejamentos.create` |
| POST | `/inventario/remanejamentos` | `remanejamentos.store` | `inventario.remanejamentos.create` |
| GET | `/inventario/remanejamentos/{remanejamento}/editar` | `remanejamentos.edit` | `inventario.remanejamentos.edit` |
| PUT | `/inventario/remanejamentos/{remanejamento}` | `remanejamentos.update` | `inventario.remanejamentos.edit` |
| POST | `/inventario/remanejamentos/{remanejamento}/desfazer` | `remanejamentos.desfazer` | `inventario.remanejamentos.edit` |
| GET | `/inventario/remanejamentos/{remanejamento}/planilha` | `remanejamentos.planilha` | `inventario.emprestimos.export` |
| POST | `/inventario/remanejamentos/{remanejamento}/chamado` | `remanejamentos.chamado` | `inventario.remanejamentos.edit` |
| POST | `/inventario/remanejamentos/{remanejamento}/seplag` | `remanejamentos.seplag` | `inventario.remanejamentos.seplag` |

- Slugs novos em `config/permissions.php` (`INVENTARIO` > `Remanejamentos`: create, edit, seplag), atribuidos aos papeis que ja tem `inventario.emprestimos.create`.
- Parametro de rota `{remanejamento}` (nunca `{anexo}` ou outro nome com `Route::model` global — conferir `grep -rn "Route::model" routes`).
- FormRequests: `SalvarRemanejamentoRequest` (lista de pessoas; ids existentes; arrays nao vazios) e autorizacao por permissao.
- `POST /inventario/movimentacoes/{id}/devolver` continua para emprestimo; recusa movimentacao de lote (use desfazer o lote).

## 6. Frontend (Atomic Design)

Mesmo padrao do Catalogo de demandas: Page fina -> Template -> Organisms -> Molecules/Atoms, componentes canonicos, raiz `space-y-6 pb-8`, dark mode por classe, zero overflow em 375/840 px.

- `Pages/Inventario/MovimentacoesIndex.vue` reescrita + `Templates/Inventario/RemanejamentosIndexTemplate.vue`:
  - `PageHeader` (icone Inventario, "Movimentações", acao "Novo remanejamento").
  - `FilterSection`: busca por pessoa ou equipamento/patrimonio, status do lote (ativo/desfeito), data.
  - `ListContainer` "Remanejamentos": uma linha por lote — data/hora, pessoas (nomes curtos), badge "N itens movidos", badge status; acoes: Enviar a SEPLAG (com contagem/data do ultimo envio no tooltip), Expandir, Planilha, Editar, Desfazer (ConfirmDialog), Registrar chamado (vira link "Chamado #id" quando existe).
  - Expandido: tabela ID / Equipamento / Patrimonio / Usuario destino / Estacao destino / Status (Ativo, Devolvido, Substituida); mobile em blocos.
  - `Pagination`; `useAtualizacaoAoVivo` com canal novo `listagem.inventario-remanejamentos` (entrada em `CanaisDeListagem` com `inventario.emprestimos.view`).
- `Pages/Inventario/RemanejamentoForm.vue` + `Templates/Inventario/RemanejamentoFormTemplate.vue` (novo e editar):
  - Lista de blocos por pessoa: select de usuario -> carrega (props do servidor ou endpoint JSON autorizado) os equipamentos atuais dele, pre-marcados; permite adicionar outros equipamentos por busca de patrimonio/nome; estacao origem (pre-preenchida com a atual) e destino (selects); condicao do destino.
  - Aviso visual dos equipamentos que serao liberados (desmarcados).
  - Erros de validacao por campo e a mensagem de `RemanejamentoProibido` por item.
- Sidebar: `Movimentações` continua em Administracao > Inventario; nenhuma entrada nova.

## 7. Erros

| Situacao | Resposta |
|---|---|
| Validacao da entrada | 422 por campo (`pessoas.N.equipamento_ids`, etc.). |
| Equipamento indisponivel/emprestado/repetido; estacao destino repetida | 422 com mensagem por item. |
| Desfazer/editar com item nao mais recente | 422 citando equipamento e lote posterior. |
| Lote desfeito: desfazer/editar/SEPLAG/chamado | 422 "Lote ja desfeito". |
| Config ausente (assunto do chamado, destinatarios SEPLAG) | 422 com mensagem de configuracao; nada gravado. |
| Falha de envio do e-mail | Job em fila com retry padrao; o registro de envio so e gravado depois do dispatch bem-sucedido. |

## 8. Testes

Feature/Unit em `SDC/tests/Feature/Inventario/` (gitignored, nao commitados), contra `sdc_test` com `DatabaseTransactions`:

- Registrar: duas pessoas trocando de estacao; liberacao dos deixados para tras; `estacao_origem_ficou_vazia` com troca mutua; substituicao de remanejamento anterior; bloqueios (baixado, manutencao, emprestimo ativo, repetidos).
- Desfazer: restaura equipamentos, estacoes (inclusive ocupante anterior do destino), liberados e `substituida` -> `ativa`; tudo ou nada quando um item tem movimentacao posterior.
- Editar: mesmo id, estado final igual a um registro novo com os dados editados.
- Chamado: cria uma Demanda resolvida com o assunto configurado; segunda chamada devolve a mesma; config ausente.
- SEPLAG: `Mail::fake` com anexo XLSX e destinatarios; contagem de envios; lote desfeito proibido; config ausente.
- Planilha: abre com openspout reader, confere cabecalho e linhas.
- Permissoes por rota; concorrencia (dois lotes com o mesmo equipamento em sequencia -> o segundo substitui, desfazer do primeiro bloqueado).
- Frontend: `npx vite build`; verificacao de overflow 375/840 px.

## 9. Fora do escopo

Dashboard do Inventario, CRUD de categorias, historico de equipamento, equipamentos compartilhados, etiquetas QR, exportacao de equipamentos, Acessos/AD, importacao de dados legados de Inventario (fatias 8–15 da auditoria).

## 10. Referencias

- Legado: `cedec-demanda/app/Http/Controllers/Inventario/MovimentacaoController.php` (store 371-391, processarMovimentos 401-505, devolverLote/executarDevolucao 507-599, abrirChamadoLote 601-695), `resources/js/Pages/Inventario/Movimentacoes/Index.vue`.
- NewSDC: `SDC/app/Modules/Inventario/**`, `SDC/routes/modules/inventario.php`, migration `2026_09_23_000001_create_inventario_ti_tables.php`, `SDC/app/Modules/Demandas/Services/{DemandaWriteService,DemandaStatusService}.php`, `SDC/app/Modules/Demandas/Support/CsvSeguro.php` (move para Shared), `SDC/app/Modules/Shared/Support/CanaisDeListagem.php`.
- Auditoria: `.superpowers/sdd/catalogo-demandas/auditoria-inventario-acessos.md` (worktree, nao versionada).
