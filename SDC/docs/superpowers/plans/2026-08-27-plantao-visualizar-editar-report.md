# Relatorio - Plantao: Visualizar e Editar turno

**Data:** 2026-08-28
**Branch:** `feat/plantao-visualizar-editar` (worktree `.claude/worktrees/plantao-frota-passagem`)
**Especificacao:** `docs/superpowers/plans/2026-08-27-plantao-visualizar-editar.md`
(este arquivo so existia, sem versionamento, no repo principal
`C:\Users\x24679188\Documents\Github\NewSDC\SDC` - lido de la, nunca editado
la; todo trabalho de codigo ficou neste worktree)

---

## O que foi implementado

### Backend

| Arquivo | O que faz |
|---|---|
| `app/Modules/Plantao/Controllers/PlantaoShowController.php` | `GET /plantao/{plantao}` (`can:plantao.turnos.view`). Eager load de `snapshots.viatura`, `movimentacoes.viatura`, `movimentacoes.condutor`, `plantonista`, `plantonistaSaida`, `encerradoPor`, `aceitoPor`. Renderiza `Plantao/Show`. |
| `app/Modules/Plantao/Controllers/PlantaoEditController.php` | `GET /plantao/{plantao}/edit` (`can:plantao.turnos.edit`). **Nao estava no inventario do plano** - ver secao "Divergencias". Autorizacao fina via `PlantaoService::podeEditar()`, 403 se falhar. Renderiza `Plantao/Edit`. |
| `app/Modules/Plantao/Controllers/PlantaoUpdateController.php` | `PUT /plantao/{plantao}` (`can:plantao.turnos.edit`). Mesma autorizacao fina, 403 se falhar. Valida via `UpdatePlantaoRequest`, atualiza via `PlantaoService::update()` (ja existia, reaproveitado), redireciona para `plantao.show`. |
| `app/Modules/Plantao/Requests/UpdatePlantaoRequest.php` | Valida so `localizacao`, `observacoes`, `ocorrencias_destaque`. `authorize()` retorna `true` de proposito - a autorizacao fina e 403, nao 422, e mora no controller. |
| `app/Modules/Plantao/DTOs/PlantaoDetailDTO.php` | DTO do payload do detalhe, no padrao de `PlantaoListDTO`/`SnapshotDTO` (construtor readonly + `fromModel` estatico). Carrega `pode_editar` (decidido no backend) e as colecoes `snapshots`/`movimentacoes` ja resolvidas. |
| `app/Modules/Plantao/DTOs/MovimentacaoDTO.php` | DTO novo para `ViaturaMovimentacao` (viatura, condutor, saida, retorno, destino, motivo, alteracoes, status). Nao existia DTO de movimentacao antes desta tarefa. |
| `app/Modules/Plantao/Services/PlantaoService.php` | + metodo `podeEditar(Plantao $plantao, User $user): bool` - unica fonte de verdade da regra de autorizacao de edicao, reaproveitada por `PlantaoEditController`, `PlantaoUpdateController` e `PlantaoIndexController` (DRY - a regra nao vive em 3 lugares). |
| `app/Modules/Plantao/Controllers/PlantaoIndexController.php` | `PlantaoListDTO::collection()` agora recebe um resolver `pode_editar` por item, calculado via `podeEditar()` (item 8 do pedido: backend decide, frontend so le a flag). |
| `app/Modules/Plantao/DTOs/PlantaoListDTO.php` | + campo `pode_editar` (bool), com resolver opcional em `collection()` (default `false` para nao quebrar chamadores existentes). |
| `routes/modules/plantao.php` | + rotas `plantao.edit`, `plantao.show`, `plantao.update`, todas depois do subgrupo `/viaturas` e das estaticas, seguindo a armadilha #1 documentada. |

### Frontend

| Arquivo | O que faz |
|---|---|
| `resources/js/Pages/Plantao/Show.vue` | Pagina fina (layout + repassa props), no padrao de `PlantaoIndex.vue`. |
| `resources/js/Templates/Plantao/ShowTemplate.vue` | Cabecalho do turno (assumido por / saindo de servico / localizacao / observacoes / encerrado em+por+"em nome de" quando terceiro / aceito em+por / divergencia), viaturas via `ViaturaSnapshotCard` dentro de `CollapsibleSection`, movimentacoes em tabela dentro de outra `CollapsibleSection`, ocorrencias de destaque, e `RelatorioPassagemPanel` quando `canRelatorio`. Status badge mapeado por `status_valor` cru (armadilha #2), nao por enum PHP. |
| `resources/js/Pages/Plantao/Edit.vue` | Pagina fina. |
| `resources/js/Templates/Plantao/EditTemplate.vue` | Formulario dos 3 campos editaveis (`localizacao` via `FormField`, `observacoes` e `ocorrencias_destaque` via `FormTextarea`), `FormActions` para salvar/cancelar, `useForm`/`useToast` no padrao ja usado em `TreinamentoEditTemplate.vue`. |
| `resources/js/Pages/Plantao/PlantaoIndex.vue` | `handleView`/`handleEdit` (linhas 52-58, TODO vazio) agora navegam para `route('plantao.show', id)` / `route('plantao.edit', id)`. |
| `resources/js/Components/Organisms/Plantao/PlantaoTable.vue` e `PlantaoGrid.vue` | O botao de editar na tabela/grade agora usa `canEdit && item.pode_editar` (per-linha, decidido pelo backend), em vez do `canEdit` global antigo que so olhava a permissao base. |
| `resources/js/ziggy.js` | Regenerado via `artisan ziggy:generate` (nunca `npm run prebuild` neste worktree) - inclui `plantao.show`, `plantao.edit`, `plantao.update`. Diff de 1 linha, confirmado com `git diff --stat`. |

---

## Divergencia do plano: `PlantaoEditController`

O plano e o pedido original so citavam **dois** controllers
(`PlantaoShowController` para GET e `PlantaoUpdateController` para PUT). Sem
uma rota GET dedicada para o formulario, nao ha como o Inertia renderizar
`Pages/Plantao/Edit.vue` como pagina propria (visitavel por URL, sobrevive a
F5, botao voltar funciona) - Inertia so troca de componente numa nova
resposta HTTP.

Fui checar como o resto do codebase resolve exatamente este caso e achei o
padrao pronto no modulo Treinamento: `TreinamentoShowController` +
`TreinamentoEditController` (GET `/treinamentos/{id}/edit`) +
`TreinamentoUpdateController` (PUT), com o comentario explicito na rota
"antes do /{id} de proposito". Segui o mesmo padrao aqui:
`PlantaoEditController` faz so a autorizacao fina (mesma logica do
`PlantaoUpdateController`, reaproveitada via `PlantaoService::podeEditar()`)
e devolve os campos do formulario. Registrado no relatorio como pedido pelo
plano: "se a realidade divergir do que o plano descreve, siga a realidade".

---

## Decisao: reaproveitar `plantao.passagem.encerrar_alheio` para a edicao

**Conclusao: reaproveitar.** Nao criei slug novo. Raciocinio:

1. **Mesmo padrao ja em producao para o mesmo problema.** O slug ja existe
   exatamente para "dono, com excecao supervisionada" (comentario no
   `config/permissions.php`: "restrito a supervisao/administracao, nao ao
   plantonista comum") e ja e atribuido **so** ao perfil `manager` (confirmei
   isso lendo `config/permissions.php` linha a linha - nenhum outro perfil
   tem `plantao.passagem.encerrar_alheio`, mas o perfil "gestor" tem
   `plantao.turnos.edit` sem a excecao). Isso e exatamente a composicao que a
   edicao alheia precisa: quem so tem `turnos.edit` continua sem poder editar
   turno de terceiro; so quem tem a excecao consegue.
2. **O requisito "fica rastreado" ja e coberto sem coluna nova.** Investiguei
   se faltava infraestrutura para rastrear "quem editou em nome de quem" (a
   preocupacao natural ao reusar um slug pensado para "encerrar"). O model
   `Plantao` ja implementa `Rastreavel` com o trait `TrilhaDeAcoes`
   (`app/Modules/Notificacoes/Support/TrilhaDeAcoes.php`): todo `update()`
   dispara `RegistroDeAcao::registrar()`, que notifica o dono do turno
   (`donosNotificacao() = [plantonista_id]`) com o card "Plantao atualizado -
   {protocolo} foi editado por {autor}", excluindo o autor da propria
   notificacao. Ou seja, uma edicao alheia **ja fica visivel para o dono
   automaticamente**, sem qualquer coluna nova - o que a tarefa proibia
   ("voce nao deve precisar de migration nenhuma").
3. **Naming imperfeito, mas documentado.** O nome do slug fala de "encerrar",
   nao de "editar". Deixei um comentario extenso em
   `PlantaoService::podeEditar()` explicando a decisao e o raciocinio, para
   quem ler o codigo depois nao estranhar sem contexto. Se o usuario preferir
   um slug proprio (`plantao.turnos.edit_alheio`, por exemplo) no futuro, a
   troca e local - um metodo so.

Nao vi necessidade de parar e reportar NEEDS_CONTEXT: a analise concluiu que
o slug existente atende, com raciocinio verificavel (perfis, trilha de
acoes), nao um chute.

---

## Evidencia de TDD

Arquivo de teste (NAO comitado, regra de ouro):
`tests/Feature/Plantao/PlantaoShowEditTest.php` - 15 testes cobrindo:
matching real do router (4), visualizar (2), autorizacao de edicao (5),
formulario de edicao (2), N+1/show completo (1), recorte editavel (1).

### RED (implementacao ainda incompleta - faltava o frontend)

```
APP_CONFIG_CACHE=/nonexistent/config.php php -d extension=pdo_pgsql -d extension=pgsql \
  vendor/phpunit/phpunit/phpunit --filter=PlantaoShowEditTest tests/Feature/Plantao/PlantaoShowEditTest.php

Tests: 15, Assertions: 28, Errors: 1, Failures: 2.
```
Causa: `Unable to locate file in Vite manifest: resources/js/Pages/Plantao/Show.vue`
(e Edit.vue) - as paginas Vue ainda nao existiam / o build ainda nao tinha
rodado. Confirma que o backend (rotas, controllers, autorizacao, DTOs) ja
estava correto ANTES do frontend existir - as 12 asserts que nao dependiam de
render de pagina ja passavam.

### GREEN (depois de criar Show.vue/Edit.vue/Templates e rodar `npm run build`)

```
Tests: 15, Assertions: 55, Errors: 0, Failures: 0.
OK (15 tests, 55 assertions)
```

### Prova adicional (mutacao): a suite realmente pega regressao de autorizacao

Para provar que os testes de autorizacao nao sao vacuos, neutralizei
temporariamente `PlantaoService::podeEditar()` (`return true;` sem checagem
de dono/status) e rodei a suite de novo:

```
Tests: 15, Assertions: 54, Failures: 4.

2) test_editar_e_barrado_quando_turno_pendente_aceite_mesmo_para_o_dono
   Expected response status code [403] but received 302.
3) test_editar_e_barrado_quando_turno_finalizado_mesmo_para_o_dono
   Expected response status code [403] but received 302.
4) test_form_de_edicao_e_barrado_com_403_quando_nao_e_o_dono
   Expected response status code [403] but received 200.
```

Revertida a mutacao, suite voltou a `OK (15 tests, 55 assertions)`.

---

## Prova de matching real do router (armadilha #1)

Nao usei `route:list` (ordena alfabeticamente e engana) - usei
`app('router')->getRoutes()->match(Request::create(...))`, dentro dos testes
`test_rota_viaturas_continua_resolvendo_para_o_controller_de_frota`,
`test_rota_show_resolve_para_plantao_show_controller`,
`test_rota_edit_resolve_para_plantao_edit_controller`,
`test_rota_update_resolve_para_plantao_update_controller` (todos GREEN):

- `GET /plantao/viaturas` -> `plantao.viaturas.index` -> `ViaturaIndexController` (nao foi capturado por `{plantao}`)
- `GET /plantao/42` -> `plantao.show` -> `PlantaoShowController`
- `GET /plantao/42/edit` -> `plantao.edit` -> `PlantaoEditController`
- `PUT /plantao/42` -> `plantao.update` -> `PlantaoUpdateController`

---

## Suite de Plantao - sem regressao (mas baseline real diverge do documentado)

Rodando so a suite nova (`PlantaoShowEditTest`): **15/15 GREEN**.

Rodando a suite inteira de Plantao (`tests/Feature/Plantao` +
`tests/Unit/Plantao`) aparecem **muito mais** erros/falhas do que o baseline
"1 erro + 2 falhas + 1 risky" citado no pedido (que se referia a
`PaeFormularioControllerTest`/`GlobalSearchServiceTest`, nem de Plantao):

```
Tests: 92, Assertions: 209, Errors: 16, Failures: 11.
```

**Investiguei antes de reportar como problema meu.** Usei
`git stash` para reverter TODOS os arquivos que toquei (8 arquivos: rotas,
controller/DTO/service de index, componentes Vue de tabela/grade, ziggy.js) e
rodei os MESMOS 3 arquivos de teste que concentravam os erros
(`MovimentacaoViaturaTest`, `PassagemServicoTest`, `ViaturaCrudTest`) contra o
codigo original, sem nenhuma linha minha:

```
=== MovimentacaoViaturaTest ===  Tests: 14, Errors: 0,  Failures: 4
=== PassagemServicoTest ===      Tests: 32, Errors: 12, Failures: 4
=== ViaturaCrudTest ===          Tests: 14, Errors: 4,  Failures: 3
```

**Identicos** aos numeros com meu codigo aplicado. Depois de `git stash pop`
(tudo restaurado, verificado com `git status` e leitura do arquivo), voltei a
rodar a suite nova isolada e confirmei GREEN de novo.

**Causa raiz identificada** (nao e do meu codigo): o banco de dev
compartilhado tem dado real de teste de sessoes anteriores - por exemplo, uma
`Viatura` com placa `ASD - QMV-2241` ja existe no banco. Isso quebra a guarda
F-3 do `PassagemServicoService` ("O encerramento precisa de uma linha por
viatura ativa da frota") em varios testes que nao contavam com viaturas
pre-existentes, e quebra asserts de contagem absoluta em `ViaturaCrudTest`
("Failed asserting that 4 is identical to 3"). E poluicao de dado no ambiente
compartilhado, nao uma regressao desta tarefa - a prova por `git stash` acima
demonstra isso de forma reprodutivel.

**Ajuste que fiz no MEU teste por causa disso:** o teste
`test_show_expoe_snapshots_e_movimentacoes_do_turno` originalmente fixava uma
placa (`QMV-2241`) e colidiu com o unique constraint do banco compartilhado.
Troquei para deixar a `Viatura::factory()` gerar a placa e comparar contra o
valor real gerado (`$viatura->placa`), em vez de hardcoded - assim o teste
nao depende do estado do banco.

---

## `npm run build --ignore-scripts`

Limpo, sem erros, duas vezes (antes e depois do ultimo ajuste de teste):
`✓ built in ~20-34s`, manifest gerado com `Show-*.js` e `Edit-*.js` para
Plantao.

---

## Botao de excluir orfao (`PlantaoTable.vue` emite `delete`)

Confirmado e **nao implementado nesta tarefa** (fora de escopo, e envolve
decisao de historico que nao me cabe tomar sozinho). `PlantaoTable.vue` e
`PlantaoGrid.vue` continuam emitindo `delete`, e nem `PlantaoIndexTemplate.vue`
nem `PlantaoIndex.vue` escutam esse evento - o clique morre silenciosamente.
**Decisao do usuario:** implementar exclusao (provavelmente soft delete, dado
que `Plantao` nao usa `SoftDeletes` hoje - outra decisao de schema) ou
esconder o botao (`canDelete` ja existe como prop, so nao esta sendo
respeitado por falta de listener).

---

## Divergencias componente-real vs. plano

- **`CollapsibleSection`**: usa `sectionId` (camelCase no script, `section-id`
  no template) e `namespace` obrigatorios, e `tom` (nao `variant`) para o
  estilo. Usei `namespace="plantao"` com `section-id` proprio por secao
  (`show-viaturas`, `show-movimentacoes`, `show-ocorrencias`), do jeito que
  `RelatorioPassagemPanel.vue` ja faz.
- **`PageHeader`**: aceita `icon` (componente) e `icon-image` (URL) ao mesmo
  tempo, com `icon-image` tendo prioridade visual - segui o padrao exato de
  `PlantaoIndexTemplate.vue` (`:icon="ClockIcon" :icon-image="moduleIcon('plantao')"`).
  Nao ha discrepancia real aqui, so documentando a leitura.
- **`FormField`**: so repassa `inputmode` ao input real (nao ao TextInput
  generico com todos os atributos soltos) - nao precisei do atributo
  `inputmode` nos 3 campos desta tela (texto e textarea simples), entao a
  armadilha #4 nao se aplicou, mas confirmei o comportamento lendo o
  componente antes de usar.
- **Molecules/Form vs Atoms/Input**: o pedido listou `Molecules/Form/*` para
  reaproveitar; o unico exemplo pronto no repo (`TreinamentoEditTemplate.vue`)
  usa `Atoms/Input/TextInput` e `Atoms/Input/SelectInput` em vez de
  `FormField`/`FormTextarea`. Segui o que o PEDIDO explicitou
  (`Molecules/Form/*`) por ser mais especifico que o precedente encontrado,
  ja que ambos existem e sao validos no design system.

Nenhuma outra divergencia de assinatura encontrada:
`ViaturaSnapshotCard` (`snapshot` prop) bate exatamente com os campos do
`SnapshotDTO` ja existente; `RelatorioPassagemPanel` (`plantao-id` numerico)
bate com `plantao.id`; `ListEmptyState` (`icon`, `title`, `helper`) usado como
esperado.

---

## Arquivos alterados/criados

**Novos (backend):**
- `app/Modules/Plantao/Controllers/PlantaoShowController.php`
- `app/Modules/Plantao/Controllers/PlantaoEditController.php`
- `app/Modules/Plantao/Controllers/PlantaoUpdateController.php`
- `app/Modules/Plantao/DTOs/PlantaoDetailDTO.php`
- `app/Modules/Plantao/DTOs/MovimentacaoDTO.php`
- `app/Modules/Plantao/Requests/UpdatePlantaoRequest.php`

**Novos (frontend):**
- `resources/js/Pages/Plantao/Show.vue`
- `resources/js/Pages/Plantao/Edit.vue`
- `resources/js/Templates/Plantao/ShowTemplate.vue`
- `resources/js/Templates/Plantao/EditTemplate.vue`

**Modificados:**
- `routes/modules/plantao.php`
- `app/Modules/Plantao/Services/PlantaoService.php`
- `app/Modules/Plantao/DTOs/PlantaoListDTO.php`
- `app/Modules/Plantao/Controllers/PlantaoIndexController.php`
- `resources/js/Pages/Plantao/PlantaoIndex.vue`
- `resources/js/Components/Organisms/Plantao/PlantaoTable.vue`
- `resources/js/Components/Organisms/Plantao/PlantaoGrid.vue`
- `resources/js/ziggy.js` (regenerado via `artisan ziggy:generate`)

**Nao comitado (regra de ouro):**
- `tests/Feature/Plantao/PlantaoShowEditTest.php`

**Nao tocados (lista proibida confirmada por grep, nenhum diff):**
`Button.vue`, `FormField.vue`, `CollapsibleSection.vue`, `ActionButton.vue`,
`PlantaoServiceProvider.php`.

---

## Auto-revisao / achados

- `declare(strict_types=1);` presente em todos os arquivos PHP novos.
- Nenhum emoji em codigo (grep unicode rodado sobre todos os arquivos novos e
  editados desta tarefa - vazio).
- Nenhuma migration criada ou tocada.
- `pode_editar` no `PlantaoListDTO` tem resolver opcional com default `false`
  - nenhum outro chamador de `PlantaoListDTO::collection()` quebra.
- Considerei se `PlantaoIndexController::turnosAtivos()` deveria reusar
  `PlantaoService::podeEditar()` tambem para o `pode_encerrar` - decidi que
  nao, sao regras de dominio diferentes (encerrar x editar tem estados
  permitidos diferentes: encerrar so cabe em ATIVO+dono/alheio, que ja e o
  que `pode_encerrar` calcula por conta propria) - manter os dois calculos
  separados evita acoplar duas decisoes de autorizacao que so parecem
  iguais.

## Preocupacoes

1. **Baseline da suite de Plantao esta desatualizado** (ver secao acima) -
   nao e algo que dá para consertar dentro desta tarefa (e dado real no banco
   compartilhado de dev, apagar seria destrutivo e fora do escopo pedido).
   Recomendo ao usuario rodar `php artisan db:seed` num banco limpo ou isolar
   os testes de Plantao com um banco proprio de CI no futuro.
2. **`PlantaoEditController` e uma adicao ao inventario do plano.** Documentei
   o raciocinio acima; se o usuario preferir uma abordagem diferente (ex.:
   edicao inline na propria tela de Show, sem pagina separada), e refactor
   pequeno e localizado.
3. **Excecao de edicao alheia via `encerrar_alheio` nao tem teste manual em
   producao** (so o teste automatizado `test_supervisao_com_encerrar_alheio_edita_turno_ativo_de_outra_pessoa`,
   que passa). Vale o usuario confirmar que a UX faz sentido (o botao
   "Editar" so aparece pro dono na lista/grade hoje, ja que `pode_editar` no
   `PlantaoListDTO` cobre a excecao tambem - um manager veria o botao no
   turno alheio).
