# Atualizacao ao vivo das listagens de dominio

**Data:** 2026-09-03
**Objetivo imediato:** Pedidos (Ajuda Humanitaria), RAT e PMDA deixam de exigir F5 quando o status muda.
**Objetivo estrutural:** o mecanismo do medalhao passa a servir listagem escopada por ACL, nao apenas dado publico de pipeline.

**Spec anterior:** `2026-09-02-tempo-real-medalhao-design.md` (o mecanismo base)

## 1. O problema

O medalhao ja atualiza sem F5, mas o mecanismo cobre exatamente duas paginas de
dado de fonte externa. O que doi na operacao e outra coisa: um coordenador com a
fila de pedidos aberta nao ve a tramitacao que o analista acabou de fazer. Ele
aperta F5 porque nao tem como saber se ha novidade.

As 28 listagens `Index.vue` restantes sao estaticas apos a carga.

## 2. Por que nao e "mais um canal"

O medalhao resolveu o caso facil. Nenhuma das tres premissas dele vale aqui:
fonte externa, dado igual para todos, evento fora de transacao.

### 2.1 A mudanca acontece DENTRO de uma transacao

`TramitacaoService` e, pelo comentario da propria classe, "o unico ponto do
modulo que altera o status do pedido" -- e altera dentro de `DB::transaction()`.

Isso cria uma corrida que o medalhao nao tinha: um evento transmitido antes do
commit faz o viewer rebuscar as props e ler o estado ANTIGO. E pior que nao
atualizar, porque nao ha segundo evento -- o usuario fica com dado velho na tela
e com a impressao de que o tempo real funcionou.

**Decisao:** o evento implementa `ShouldDispatchAfterCommit`. Sem isso, o
mecanismo e ativamente enganoso.

Verificado caminho por caminho, e so uma das tres esta em transacao:

| Ponto de mudanca | Transacao? |
| --- | --- |
| `TramitacaoService::executar()` | **sim**, `DB::transaction` |
| `PmdaService::transicionar()` | nao (`$plano->update()` + log de evento) |
| `RatOcorrenciaService::manageOcorrencia()` | nao (`updateOrCreate`) |

`ShouldDispatchAfterCommit` fora de transacao simplesmente despacha na hora,
entao a decisao vale para as tres sem excecao. Mas a protecao so tem efeito em
Pedidos hoje -- e passa a valer de graca no dia em que PMDA ou RAT ganharem uma
transacao, que e o motivo de nao deixar isso a cargo de quem fiar cada pagina.

### 2.2 Duas das tres listagens NAO sao escopadas no servidor

Levantado por leitura do codigo em 2026-09-03.

| Listagem | Permissao da rota | Escopo server-side | Props do reload |
| --- | --- | --- | --- |
| Pedidos AH | `can:humanitaria.pedidos.view` | **nenhum.** `PedidoAhService::listar()` aplica so os filtros que o usuario manda | `pedidos` (paginado + `PedidoAhIndexResource`), `estatisticas` (closure de agregacao) |
| RAT | `can:rat.protocolos.view` | **nenhum.** listagem global, ordenada e paginada | `rats` (3 queries com eager load de `relatosMorph`), `statistics` (**cacheado 5 min**) |
| PMDA analises | `can:pmda.analise.view` | **sim.** `PerfilPmda::aplicarEscopo()` recorta o COMPDEC ao proprio municipio | 3 queries em `Concurrency::tasks` + catalogo de municipios |

Consequencia direta: para Pedidos e RAT o canal pode espelhar a permissao da
pagina, exatamente como `medalhao.{grupo}` espelhou "usuario autenticado" -- nem
mais nem menos, sem superficie de ACL nova. Para PMDA nao pode: um COMPDEC de um
municipio receberia aviso de mudanca em outro, o que custa um reload inutil e
ainda vaza a existencia de atividade alheia por canal lateral.

### 2.3 Reusar o canal de notificacao NAO resolve o caso principal

Era a hipotese mais barata: o canal `App.Models.User.{id}` ja existe, ja e
autorizado e ja tem fallback para polling. Mas
`AjudaHumanitariaNotificacaoService::envolvidos()` notifica apenas `created_by`,
`analista_id` e `diretor_id`, menos o autor da acao.

Ou seja: cobre quem esta no processo e **nao cobre o coordenador que esta com a
fila aberta** -- que e justamente quem aperta F5. A hipotese foi descartada pela
leitura do codigo, nao por preferencia de arquitetura.

O canal do usuario continua util para um caso diferente e menor: refletir na
listagem uma mudanca que gerou notificacao para mim. Fica fora deste spec.

## 3. Decisoes de arquitetura

### 3.1 O evento e um aviso, e continua sendo

Mesma decisao do medalhao, pela mesma razao: o payload leva so o recurso e o
carimbo de tempo. Quem rebusca e o Inertia, pelo controller, que segue sendo a
unica fonte das props. Mandar a linha alterada criaria uma segunda serializacao
que diverge do `Resource` na primeira mudanca -- e, aqui, tambem furaria o
escopo, porque a linha que interessa a um usuario pode nao ser visivel para
outro no mesmo canal.

### 3.2 Um evento generico, canal parametrizado pelo recurso

`RecursoAtualizado(string $recurso, ?int $escopo = null)`, transmitindo em
`listagem.{recurso}` ou `listagem.{recurso}.{escopo}`. Fonte nova ganha tempo
real sem classe nova, que foi o que fez `GoldAtualizado` envelhecer bem.

`routes/channels.php` autoriza cada recurso com a permissao da propria rota:

- `listagem.pedidos-ah` -> `humanitaria.pedidos.view`
- `listagem.rat` -> `rat.protocolos.view`
- `listagem.pmda-analises.{municipio}` -> `pmda.analise.view` **e** escopo de
  perfil compativel
- `listagem.pmda-analises.todos` -> `pmda.analise.view` **e**
  `municipioDoEscopo() === null`

### 3.2.1 Recurso escopado transmite em DOIS canais

Corrigido em 2026-09-03, ao fiar a pagina do PMDA: o desenho original mandava o
evento so para `...{recurso}.{municipio}`, e com isso quem le o estado inteiro
NAO ouviria nada. Um usuario da CEDEC teria de assinar os 853 canais de
municipio, um por um -- ou seja, o tempo real nao funcionaria justamente para
quem mais usa a Central de Analises.

`broadcastOn()` devolve dois canais: o do municipio e `...{recurso}.todos`. Sao
dois canais e nao dois eventos -- um dispatch, uma transmissao, e a autorizacao
de cada canal decide quem recebe.

O `todos` e autorizado **apenas** a quem le o escopo inteiro (CEDEC, REDEC,
super-admin). Para um COMPDEC ele seria exatamente o vazamento que o escopo
existe para impedir. O sufixo nunca colide com id de municipio porque id e sempre
numerico, e a autorizacao usa essa diferenca para separar os dois casos.

A tabela recurso -> permissao vive em UM lugar, e nao espalhada por
`channels.php`. Recurso sem permissao declarada e recusado por padrao: canal novo
sem entrada na tabela nao autoriza ninguem, em vez de autorizar todo mundo.

### 3.3 Debounce no cliente, nao no servidor

Uma rajada de tramitacao (aprovar dez pedidos em sequencia) emite dez eventos.
Sem coalescencia, todo viewer roda dez vezes o index -- e no PMDA cada rodada sao
tres queries paginadas mais o catalogo de municipios.

O debounce fica no CLIENTE porque e la que se sabe se um reload ja esta em voo.
Coalescer no servidor exigiria estado compartilhado entre workers para uma
economia que o cliente consegue com um `setTimeout`.

### 3.4 O `statistics` do RAT nao pode entrar no reload como esta

`Cache::remember('rat:statistics', 300, ...)`. Rebuscar a prop devolveria o valor
cacheado por ate 5 minutos: a tabela atualizaria e os contadores nao, que e pior
que os dois ficarem velhos juntos.

Duas saidas: deixar `statistics` FORA do `only:` (a tabela atualiza, os
contadores esperam o proximo ciclo) ou invalidar a chave no caminho do evento.
Este spec escolhe **deixar fora**. Invalidar transforma cada mudanca em recomputo
dos quatro counts para o estado inteiro, que e exatamente o que o cache existe
para evitar.

### 3.5 Degradacao silenciosa, como antes

Sem `initEcho()` nao ha tempo real, e isso nao e erro. Com
`BROADCAST_CONNECTION=null` as listagens funcionam como sempre funcionaram. E o
que mantem a mudanca desligavel sem reverter codigo.

### 3.6 O composable de hoje serve, com uma adicao

`useAtualizacaoAoVivo` ja faz o essencial: instancia unica via `initEcho`,
`preserveScroll`/`preserveState`, aba oculta marca pendencia, `leave()` no
unmount e guarda contra unmount durante o import dinamico. Falta so o debounce
de 3.3. Nao ha composable novo.

## 4. Arquitetura

### 4.1 Backend

| Arquivo | Papel |
| --- | --- |
| `app/Modules/Shared/Events/RecursoAtualizado.php` | Evento `ShouldBroadcast` + `ShouldDispatchAfterCommit`. `broadcastAs()` estavel; payload `{recurso, escopo, atualizado_em}` |
| `app/Modules/Shared/Support/CanaisDeListagem.php` | Tabela recurso -> permissao e resolucao de escopo. Fonte unica da autorizacao |
| `routes/channels.php` | `listagem.{recurso}` e `listagem.{recurso}.{escopo}`, delegando a tabela |
| `AjudaHumanitaria/Services/TramitacaoService.php` | `executar()`, dentro de `DB::transaction`. Emite apos o commit |
| `Rat/Services/RatOcorrenciaService.php` | `manageOcorrencia()`, `updateOrCreate` **sem transacao**. Emite direto |
| `Pmda/Services/PmdaService.php` | `transicionar()`, ponto privado unico das transicoes, **sem transacao**. Emite com o municipio do plano como escopo |

### 4.2 Frontend

| Arquivo | Papel |
| --- | --- |
| `Composables/useAtualizacaoAoVivo.js` | Ganha `debounceMs` (padrao ~400ms) |
| `Pages/AjudaHumanitaria/Pedidos/Index.vue` | Consome, `only: ['pedidos','estatisticas']` |
| `Pages/RatIndex.vue` | Consome, `only: ['rats']` -- `statistics` fora, ver 3.4 |
| `Pages/Pmda/Analises/Index.vue` | Consome com escopo de municipio |

## 5. Testes

| Teste | Prova |
| --- | --- |
| `RecursoAtualizadoTest` | E broadcastavel; despacha apos commit; canal e nome estaveis; payload sem dado de dominio |
| `CanaisDeListagemTest` | Recurso sem permissao declarada NAO autoriza; cada recurso conhecido exige a permissao da sua rota |
| `CanalListagemTest` | Visitante 403; usuario com a permissao autoriza; usuario SEM a permissao 403 |
| `CanalPmdaEscopoTest` | COMPDEC do municipio A nao autoriza o canal do municipio B; CEDEC autoriza qualquer um |
| `TramitacaoServiceTest` (adicao) | `Event::fake()` e assercao de que tramitar emite `RecursoAtualizado('pedidos-ah')` |

## 6. Criterios de verificacao

1. Com `BROADCAST_CONNECTION=null`, as tres listagens funcionam como hoje e o console fica sem erro.
2. Com duas sessoes abertas na fila de pedidos, tramitar em uma faz a outra refletir **sem F5**.
3. O reload acontece DEPOIS do commit: a linha aparece com o status novo, nunca com o antigo.
4. Scroll e pagina corrente da tabela sobrevivem.
5. Aba em segundo plano nao rebusca; ao voltar, atualiza uma vez.
6. Dez mudancas em rajada geram **um** reload por viewer, nao dez.
7. Usuario sem `humanitaria.pedidos.view` recebe 403 no canal de pedidos.
8. COMPDEC do municipio A recebe 403 no canal PMDA do municipio B.
9. O payload no socket nao contem campo de dominio nenhum.
10. Suite verde no escopo (`AjudaHumanitaria|Rat|Pmda|TempoReal`).

## 7. Riscos

**O canal espelha a permissao, e a permissao pode mudar.** Se algum dia a
listagem de pedidos passar a ser escopada por municipio no servidor, o canal
global vira vazamento de escopo. A tabela de 3.2 e o lugar onde isso se corrige,
e por isso ela existe -- mas nada obriga quem mexer no `listar()` a olhar para
la. Mitigacao: o teste de canal referencia a mesma permissao da rota, entao a
divergencia aparece como teste vermelho, nao como incidente.

**Fan-out.** Um evento por mudanca para todo mundo com a fila aberta. Em Defesa
Civil a plateia simultanea de uma listagem e pequena (dezenas), e o debounce de
3.3 limita a rajada. Se crescer, o proximo passo e escopar o canal por filtro
ativo -- fora deste spec.

**PMDA e RAT mudam status FORA de transacao** (ver a tabela em 2.1). O evento
sai imediatamente, entao o viewer pode rebuscar antes do `update` ter chegado ao
banco em cenario de concorrencia. E o comportamento de hoje, nao uma regressao --
mas nas duas paginas o tempo real nao tem a garantia que Pedidos tem, e isso
precisa estar dito antes de alguem confiar nele para decisao operacional.

**O RAT nao tem ponto unico de escrita, e isso foi confirmado.** Levantado em
2026-09-03: `RatOcorrencia` e escrito de pelo menos oito lugares, em tres classes
(`RatOcorrenciaService::manageOcorrencia`, seis pontos em `RatWriteService`,
`EloquentRatRepository::create/updateStatus/delete`). Emitir de um ponto so faria
a pagina perder eventos em silencio.

E a saida obvia -- um observer no model -- **nao resolve sozinha**: parte dessas
escritas usa query builder (`RatOcorrencia::where(...)->update(...)` e
`->delete()`), e observer do Eloquent nao dispara para essas. Um observer daria
cobertura PARCIAL, falhando exatamente nos caminhos mais dificeis de notar.

Fiar o RAT exige antes consolidar a superficie de escrita, que e trabalho de
outra natureza. Ver secao 8.

## 8. Fora de escopo

- As outras 25 listagens. O mecanismo e generico; a fiacao e por pagina e por dor.
- Refletir a PROPRIA acao sem F5 (a resposta do Inertia do proprio POST ja faz isso).
- Presenca (quem mais esta com esta tela aberta).
- Escopar canal por filtro ativo da listagem.
- Reusar o canal de notificacao para refletir mudanca notificada a mim (ver 2.3).
