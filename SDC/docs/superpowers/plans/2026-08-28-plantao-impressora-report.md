# Icone de impressora no Historico de Plantoes - Relatorio

## O que foi implementado

Acrescentei o botao de impressao da passagem de servico na coluna de acoes do
modulo Plantao, seguindo o padrao ja estabelecido pelos quatro modais de
impressao existentes (Decretacoes, PAE, Ajuda Humanitaria, Cisternas).

Arquivos novos:

- `resources/js/Components/Organisms/Plantao/Print/PrintPassagemModal.vue`

Arquivos alterados:

- `resources/js/Components/Organisms/Plantao/PlantaoTable.vue`
- `resources/js/Components/Organisms/Plantao/PlantaoGrid.vue`
- `resources/js/Templates/Plantao/PlantaoIndexTemplate.vue`

`resources/js/Pages/Plantao/PlantaoIndex.vue` **nao foi tocado**: o evento
`print` e resolvido inteiramente dentro de `PlantaoIndexTemplate.vue`, o
mesmo padrao usado em `ProcessoIndexTemplate.vue` (Decretacoes) para o
`handlePrint`. O `id` do turno clicado e resolvido contra `props.plantoes`
(a lista ja carregada na pagina) para montar o objeto `plantao` que alimenta
o cabecalho do documento - nenhuma rota nova, nenhum controller novo.

## O slug escolhido no aliasOverride, e por que

Usei **`plantao.passagem.relatorio`** — nao `plantao.turnos.export`.

```js
{ action: 'print', handler: () => emit('print', item.id), resource: 'passagem', aliasOverride: 'relatorio' }
```

### O que a leitura do ActionButton.vue (linhas 306-352) revelou

`hasPermissionFor` monta o slug assim (linha 316 e 326):

```js
const slugAction = aliasOverride ?? ACTION_ALIAS[action] ?? action;
const slug = noResource ? `${module}.${slugAction}` : `${module}.${resource}.${slugAction}`;
```

Ponto central: `aliasOverride` **so substitui o ultimo segmento** (a acao).
Ele nunca toca `resource`. Mas cada item do array `actions` pode declarar o
seu **proprio** `resource` (e `module`), porque `visibleInlineActions` (linha
335-341) le `resource: a.resource` do item, nao so de `props.resource`:

```js
.filter(a => hasPermissionFor({
  action: a.action,
  module: a.module,
  resource: a.resource,       // <- por item, sobrescreve o resource="turnos" da tabela
  allowed: a.allowed ?? null,
  aliasOverride: a.aliasOverride ?? null,
}))
```

Isso significa que `aliasOverride` sozinho so alcancaria slugs dentro do
mesmo `resource` da tabela (`plantao.turnos.<algo>`) — e e por isso que o
candidato "so aliasOverride" ficaria restrito a `plantao.turnos.export`. Mas
combinando `resource: 'passagem'` + `aliasOverride: 'relatorio'` no mesmo
item, o slug final fica **`plantao.passagem.relatorio`** — exatamente a
permissao que already gates a rota `GET /plantao/{plantao}/relatorio`
(`routes/modules/plantao.php:76-77`, `middleware('can:plantao.passagem.relatorio')`)
que o modal consome.

Escolhi esse em vez de `plantao.turnos.export` porque:

1. E semanticamente exato: quem pode ver o relatorio de passagem (a mesma
   permissao que ja guarda `RelatorioPassagemPanel.vue`, painel que ja mostra
   o mesmo texto na tela) e quem deveria poder imprimi-lo em papel. Usar
   `export` faria a visibilidade do botao de imprimir depender de uma
   permissao de Excel/CSV, sem relacao com o conteudo real do modal.
2. Ja esta seedado e sincronizado: `config/permissions.php` e a fonte de
   verdade (`RolesAndPermissionsSeeder`), e `plantao.passagem.relatorio` ja
   esta atribuido a `manager`, `analyst`, `operator`, `viewer`, `user` (via
   `role_permissions`) e a `admin` via `plantao.*`. Nao precisei criar nada.
3. Ha precedente direto do proprio padrao do projeto para IMPRIMIR
   reaproveitar uma permissao "irma" via `aliasOverride` em vez de tentar
   inventar `print` como slug proprio (comentario em
   `Cisterna/BeneficiariosTable.vue:112-118`, sobre o mesmo defeito de slug
   inexistente que jah aconteceu com `validar` nas notificacoes).

Documentei essa decisao em comentario dentro de `PlantaoTable.vue` (com
`PlantaoGrid.vue` apontando para o mesmo comentario, para nao duplicar
texto).

## Divergencias entre os quatro modais existentes, e o que segui

Os quatro modais de impressao (`PrintDecretacaoModal`, `PrintPaeProtocoloModal`,
`PrintBeneficiarioModal` em AjudaHumanitaria, `BeneficiarioPrintModal` em
Cisterna) convergem no essencial:

- Todos envolvem o conteudo em `BasePrintModal` (`@/Components/Organisms/Print/BasePrintModal.vue`),
  que ja resolve o botao "Imprimir" (via `window.open` + `innerHTML` do slot,
  com um `<style>` proprio injetado na janela de impressao), o botao
  "Assinar com GOV.br" e o fechamento (Esc/X).
- Todos usam `PrintHeader` para o cabecalho com brasao + titulo/subtitulo +
  numero do documento, e `PrintSection` para blocos de conteudo com
  `section-title`.
- A tabela de campos usa a classe `bos-table` — que na pratica nao tem
  nenhuma regra CSS propria; o estilo real vem de `:deep(table)`/`:deep(td)`/
  `:deep(.field-label)`/`:deep(.field-value)` no `<style scoped>` do
  `BasePrintModal`, e (na janela impressa) do `<style>` bruto injetado por
  `handlePrint()`. `bos-table` e so uma convencao de nome, nao uma classe
  funcional.

Onde elas divergem, segui o exemplo que mais se aplicava ao Plantao:

- Decretacoes e PAE fecham com `PrintRecibo` (bloco formal de "Recebi o
  documento X para conhecimento..."), voltado para processos administrativos
  com numero de protocolo. Isso nao se aplica a uma passagem de turno.
- AjudaHumanitaria fecha com um bloco de assinatura mais simples, inline no
  proprio modal (duas colunas: "Assinatura do Responsavel" / "Assinatura do
  Profissional"), sem componente dedicado.
- Segui o padrao de AjudaHumanitaria (assinatura inline, sem `PrintRecibo`),
  porque o pedido do usuario e "arquivo, assinatura, conferencia fisica" — o
  mesmo espirito de um recibo de handoff, nao um protocolo formal com prazo de
  alteracao. Adicionei uma secao "CONFERENCIA" com duas linhas: "Plantonista
  que registra" / "Responsavel que confere".
- Para o corpo do texto livre (o relatorio em si, que e uma string grande,
  nao campos estruturados), usei o padrao `.historico-text` que encontrei em
  `Rat/Print/Sections/BoletimHistorico.vue` (`white-space: pre-wrap`,
  `text-align: justify`) — e o unico modal/secao print do projeto que
  renderiza um bloco de texto livre em vez de campos label/valor, e e
  exatamente o formato do texto que `RelatorioPassagemService::renderizar()`
  devolve (o mesmo consumido hoje por `RelatorioPassagemPanel.vue` num
  `<pre>`). `BasePrintModal` ja injeta uma regra `.historico-text` igual
  no `<style>` da janela de impressao; replicar a mesma classe localmente
  (com `<style scoped>` no proprio `PrintPassagemModal.vue`, como
  `BoletimHistorico.vue` faz) garante que a pre-visualizacao na tela
  (antes de imprimir) tambem respeite `pre-wrap`.

Nao criei PDF nem usei nenhuma biblioteca de assinatura alem do botao
GOV.br que o `BasePrintModal` ja oferece de fabrica a todos os modais
(nao adicionei nada novo ali).

## Como provei o escopo do modal (turno certo, nao outro)

`handlePrint(id)` em `PlantaoIndexTemplate.vue` resolve
`selectedPlantao = props.plantoes.find(p => p.id === id)` a cada clique -
nunca reaproveita o estado anterior. O modal recebe esse objeto via prop
`:plantao="selectedPlantao"` e tem um `watch(() => [props.show, props.plantao?.id], ...)`
que limpa `texto`/`erro` e refaz o GET sempre que `plantao.id` muda (mesmo
com o modal ja aberto, sem fechar e reabrir). Acrescentei tambem uma guarda
contra resposta atrasada (`if (plantaoId !== props.plantao?.id) return` antes
de aplicar `texto.value = data.texto`), para o caso hipotetico de dois
cliques rapidos em turnos diferentes antes do primeiro GET responder - sem
isso, a resposta antiga poderia sobrescrever o texto do turno atual.

Verificacao feita por leitura de codigo (nao havia motivo para inventar dado
fake): tracei o fluxo completo, id a id, do clique no icone (`emit('print',
item.id)` em `PlantaoTable.vue`/`PlantaoGrid.vue`) at'e o watch do modal e a
URL final do `axios.get` (`route('plantao.passagem.relatorio', plantaoId)`,
igual ao que `RelatorioPassagemPanel.vue` ja faz e que os 92 testes
verdes do modulo cobrem no backend).

## Como provei o esconder por permissao

Nao dava para logar com um perfil sem `plantao.passagem.relatorio` porque,
nos perfis seedados hoje (`config/permissions.php`, secao
`role_permissions`), **todo perfil que tem `plantao.turnos.view` tambem tem
`plantao.passagem.relatorio`** (manager, analyst, operator, viewer, user; e
admin via `plantao.*`). Criar um perfil novo so para o teste mexeria no banco
de desenvolvimento compartilhado do usuario, e a tarefa pede para evitar
mutacao alem do necessario - entao a prova foi por rastreamento de codigo,
apoiada em mecanismo ja testado:

1. `hasPermissionFor` (ActionButton.vue:306-327) monta
   `slug = 'plantao' + '.' + 'passagem' + '.' + 'relatorio'` a partir do meu
   item (`module` default = `props.module` = `'plantao'`; `resource` =
   `'passagem'` do item; `slugAction` = `aliasOverride` = `'relatorio'`).
2. `can(slug)` (`usePermissions.js`) faz `_permSet.value.has(permission)` -
   comparacao exata contra um `Set` de strings vindo de
   `page.props.auth.user.permissions`. Nao ha wildcard nem fuzzy match no
   frontend; a expansao de `plantao.*` ja aconteceu no backend, na hora de
   materializar as permissoes do usuario.
3. Portanto, um usuario cujo `permissions` nao contenha a string exata
   `'plantao.passagem.relatorio'` faz `can()` retornar `false`, o item
   `print` e removido de `visibleInlineActions` (linha 332-342, mesmo filtro
   que ja esconde `edit`/`delete` para quem nao tem essas permissoes hoje) e
   o icone nunca chega a renderizar.
4. O lado do backend dessa mesma permissao ja tem teste verde no
   `tests/Feature/Plantao/PassagemServicoTest.php::test_rota_do_relatorio_exige_permissao`
   (retorna 403 sem a permissao) - parte da suite de 92 testes confirmada
   abaixo. E a mesma string de slug, a mesma rota, o mesmo middleware
   `can:plantao.passagem.relatorio` que o botao agora espelha no frontend.

Nao tentei validar via navegador/Playwright contra um servidor rodando:
o servidor Octane que respondeu em `127.0.0.1:8000` durante a checagem serve
o repo principal (branch `feat/notificacoes-canais-reais`, com trabalho ativo
do usuario), nao este worktree, e a tarefa proibe explicitamente
`octane:reload`/restart. Nao arrisquei tocar nele.

## Resultado da suite e do build

Baseline (antes de qualquer mudanca):

```
OK (92 tests, 275 assertions)
```

Depois de todas as mudancas de frontend (nenhum arquivo PHP foi tocado):

```
OK (92 tests, 275 assertions)
```

Comando usado (phpunit direto, nunca `artisan test`):

```bash
export PHP83="C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe"
APP_CONFIG_CACHE=/nonexistent/config.php "$PHP83" -d extension=pdo_pgsql -d extension=pgsql \
  vendor/phpunit/phpunit/phpunit tests/Feature/Plantao tests/Unit/Plantao
```

Build (`npm run build --ignore-scripts`, sem tocar no `prebuild`/ziggy):
limpo, `PlantaoIndex-*.js` recompilado sem erro, `vite build` terminou com
`✓ built in ~34s` e o PWA `generateSW` gerou o service worker normalmente.

## Ordem dos icones

Confirmada em `PlantaoTable.vue` e `PlantaoGrid.vue`: `view` -> `print` ->
`edit` -> `delete`, igual ao padrao de Decretacoes (olho, impressora, lapis,
lixeira). Nao mexi no botao `delete` (permanece sem listener no template -
divida conhecida, fora de escopo).

## Preocupacoes

- Nenhum perfil seedado hoje separa "ver o historico" de "ver/imprimir o
  relatorio de passagem" - todo mundo que ve a tabela tambem ve o botao
  novo. Isso e coerente com o painel `RelatorioPassagemPanel.vue` (mesma
  permissao, mesmo texto), mas registro caso o usuario queira, no futuro,
  restringir a impressao separadamente do painel de leitura.
- Nao validei visualmente no navegador (Playwright) por causa dos dois
  bloqueios do ambiente: o worktree nao tem servidor proprio e o servidor
  do repo principal esta ocupado com outro trabalho e e proibido de
  reiniciar. A verificacao ficou no rastreamento de codigo + testes verdes
  ja existentes (backend) + build limpo (frontend). Se for possivel, vale
  uma conferencia visual posterior num ambiente dedicado a este worktree.
- O corpo do relatorio pode ser longo (varias viaturas, varias anotacoes);
  a classe `.historico-text` nao limita altura na impressao (correto, papel
  nao tem scroll), mas na pre-visualizacao dentro do modal ela tambem nao
  tem `max-height`/scroll proprio - quem usa telas pequenas rola o modal
  inteiro (`BasePrintModal` ja tem `max-h-[80vh] overflow-y-auto` no
  container), o que e o mesmo comportamento dos outros quatro modais.
