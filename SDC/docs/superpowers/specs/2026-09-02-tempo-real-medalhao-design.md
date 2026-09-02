# Atualizacao ao vivo das telas do medalhao

**Data:** 2026-09-02
**Objetivo imediato:** Meteorologia e Sismos deixam de exigir F5 quando o pipeline coleta dado novo.
**Objetivo estrutural:** um mecanismo reutilizavel por qualquer listagem do SDC.

## 1. O problema

As telas da SPA nao refletem mudanca de dado sem recarregar. Para o dado que vem
de fonte externa isso e especialmente ruim: o pipeline do medalhao coleta de hora
em hora, mas quem esta com o mapa aberto so ve a coleta anterior.

Hoje existe atualizacao automatica em exatamente dois lugares — o sino de
notificacoes (`useNotifications`) e o feed de atividades (`useActivityFeed`,
60s) — ambos por polling. As 30 paginas `Index.vue` do sistema sao estaticas
apos a carga.

## 2. Situacao encontrada: a tubulacao existe e nunca foi usada

Levantado por leitura do codigo em 2026-09-02.

| Peca | Estado |
| --- | --- |
| `laravel/reverb ^1.10` | No `composer.json` |
| `laravel-echo ^2.3.4`, `pusher-js ^8.5.0` | No `package.json` |
| `config/reverb.php` | Existe |
| `BroadcastServiceProvider` | Existe e chama `Broadcast::routes()` |
| `routes/channels.php` | Existe, **sem nenhum canal definido** |
| Servico `reverb` em producao | Definido em `docker/jenkins/stack.app.onpremise.yml`, rodando `reverb:start` |
| Caddy | Ja faz `reverse_proxy @reverb reverb:8080` para `/app/*` |
| Jenkins | Ja trata `${STACK_NAME}_reverb` no rolling update |
| `BROADCAST_CONNECTION` em producao | `reverb`, tanto no servico `app` quanto no `queue` |
| **Eventos `ShouldBroadcast` no codigo** | **Nenhum** |
| **Echo inicializado no `app.js`** | **Nao** |
| `BROADCAST_CONNECTION` em dev | Ausente; o default de `broadcasting.php` e `null` |
| Servico `reverb` em dev | Nao existe |

Ou seja: **o servidor de WebSocket sobe em producao e nunca transmitiu nada.**
Falta emitir evento, ligar o cliente, e criar o equivalente em dev.

## 3. Decisoes de arquitetura

### 3.1 O evento e um aviso, nao um transporte de dado

Payload minimo: `{grupo, atualizado_em}`. Nenhuma leitura de estacao ou evento
sismico trafega pelo socket.

Mandar o payload duplicaria a serializacao que o controller ja faz e criaria duas
fontes de verdade que divergem na primeira mudanca de matview. O cliente recebe o
aviso e pede ao Inertia para rebuscar; o controller segue sendo o unico lugar que
sabe montar as props.

Consequencia colateral desejavel: nada de sensivel atravessa o socket, e a
autorizacao de leitura continua sendo a da rota HTTP.

### 3.2 Emitido no refresh do Gold, nao na ingestao

`AtualizarGoldInmetJob` e `AtualizarGoldSismosJob` emitem apos o
`REFRESH MATERIALIZED VIEW` concluir. E o refresh que torna o dado visivel: avisar
na ingestao mandaria o cliente rebuscar antes de haver o que ver.

Como o dedup por hash do `IngerirFonteJob` corta ciclos sem novidade, o evento so
acontece quando ha dado novo de verdade.

### 3.3 Canal privado, no mesmo nivel da pagina

`PrivateChannel('medalhao.inmet')` e `PrivateChannel('medalhao.sismos')`,
autorizados em `channels.php` a qualquer usuario autenticado — o mesmo que as
rotas `/inmet` e `/sismos` exigem. Nem mais, nem menos.

Publico foi descartado: nao ha razao para dado operacional de Defesa Civil, ainda
que so um carimbo de tempo, ser legivel sem sessao.

### 3.4 Degradacao silenciosa transforma a mudanca num feature flag

O composable nao faz nada — e nao lanca erro — quando `window.Echo` nao existe.
Com `BROADCAST_CONNECTION=null`, que e o padrao atual, as paginas funcionam
exatamente como hoje.

Isso importa porque o caminho nunca foi exercitado em producao: se algo der
errado, desligar o broadcasting devolve o comportamento anterior sem reverter
codigo.

### 3.5 Mecanismo para o site todo, fiacao em duas paginas

O composable serve qualquer listagem; ligar uma pagina nova sao tres linhas.

Mas a fiacao inicial cobre so Meteorologia e Sismos. Ligar 30 paginas de uma vez
num caminho que nunca transmitiu e como se descobre em producao que a autorizacao
de canal ou o upgrade de WebSocket no Caddy tem um detalhe. As duas telas do
medalhao sao o menor volume e o maior valor: e onde o dado envelhece sozinho.

### 3.6 Por que nao polling

Considerado e descartado. Polling com `router.reload` custa um ciclo completo de
request — middleware, sessao, autorizacao, controller — por tick, por usuario,
mesmo quando nada mudou. Com dado que muda de hora em hora, e desperdicio
continuo, e e exatamente o "pesar a arquitetura" que o pedido pede para evitar.

O push custa uma conexao ociosa por aba e **um evento quando ha novidade**.

## 4. Arquitetura

### 4.1 Backend

| Arquivo | Papel |
| --- | --- |
| `app/Modules/Medalhao/Events/GoldAtualizado.php` | Evento `ShouldBroadcast`. Recebe `grupo`; `broadcastOn()` devolve `PrivateChannel("medalhao.{$grupo}")`; `broadcastAs()` devolve `GoldAtualizado` |
| `routes/channels.php` | `Broadcast::channel('medalhao.{grupo}', fn ($user) => $user !== null)` |
| `app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php` | `GoldAtualizado::dispatch('inmet')` apos os dois refresh |
| `app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php` | `GoldAtualizado::dispatch('sismos')` apos os dois refresh |

O evento vive no kernel `Medalhao`, e nao em cada dominio: e generico por
construcao, parametrizado pelo grupo. Fonte nova ganha tempo real sem classe
nova.

### 4.2 Frontend

| Arquivo | Papel |
| --- | --- |
| `resources/js/echo.js` | Inicializa `window.Echo` com o broadcaster `reverb`, **somente se** as variaveis `VITE_REVERB_*` existirem |
| `resources/js/app.js` | Importa `./echo` |
| `resources/js/Composables/useAtualizacaoAoVivo.js` | Assina o canal e dispara o recarregamento parcial |
| `resources/js/Pages/Inmet/MapaInmet.vue` | Consome, com `props: ['estacoes','estatisticas']` |
| `resources/js/Pages/Sismos/MapaSismos.vue` | Consome, com `props: ['eventos','estatisticas']` |

Contrato do composable:

```js
useAtualizacaoAoVivo({
  canal: 'medalhao.inmet',
  evento: '.GoldAtualizado',
  props: ['estacoes', 'estatisticas'],
});
```

Comportamento:

- Assina em `onMounted`, desassina em `onBeforeUnmount`.
- Ao receber, chama `router.reload({ only: props, preserveScroll: true, preserveState: true })`.
- **Nao recarrega com a aba oculta**; marca pendencia e recarrega ao voltar.
  Mesma disciplina que `useNotifications` ja aplica ao polling do sino.
- Se `window.Echo` for `undefined`, nao faz nada e nao lanca.

`preserveState` mantem a pagina da tabela e o estado local; `preserveScroll`
evita o salto que tornaria a atualizacao pior que o F5.

### 4.3 Infra em dev

Servico `reverb` no `compose.dev.yml`, espelhando o de producao:
`php artisan reverb:start --host=0.0.0.0 --port=8080`, porta publicada para o
navegador alcancar, e as variaveis `BROADCAST_CONNECTION` e `REVERB_*` na ancora
`queue-env` e no servico `app`. Valores em `docker/.env`, no mesmo padrao do
`APP_KEY` e do `MEDALHAO_INMET_TOKEN`.

O Vite precisa das `VITE_REVERB_*` em build time, o que exige que elas estejam no
`.env` da aplicacao alem do `docker/.env`.

## 5. Testes

Sem infraestrutura de teste JS no projeto, entao a cobertura automatizada e do
lado PHP.

| Teste | Prova |
| --- | --- |
| `GoldAtualizadoTest` | `broadcastOn()` devolve `PrivateChannel` com o grupo certo; `broadcastAs()` e estavel; o payload nao contem dado de dominio |
| `AtualizarGoldInmetJobTest` (adicao) | `Event::fake()` e assercao de que o job emite `GoldAtualizado` com grupo `inmet` apos o refresh |
| `AtualizarGoldSismosJobTest` (adicao) | Idem para `sismos` |
| `CanalMedalhaoTest` | Usuario autenticado autoriza; visitante nao |

O comportamento do composable — pausa com aba oculta, recarregamento parcial,
degradacao sem Echo — precisa de conferencia manual em tela.

## 6. Criterios de verificacao

1. Com `BROADCAST_CONNECTION=null`, as duas paginas funcionam como hoje e o
   console fica sem erro.
2. Com o Reverb de pe, abrir `/inmet`, disparar `medalhao:ingerir inmet` e ver a
   tabela e as estatisticas trocarem **sem recarregar**.
3. O scroll e a pagina corrente da tabela sobrevivem a atualizacao.
4. Com a aba em segundo plano, nada e rebuscado; ao voltar, atualiza.
5. Visitante nao autenticado nao consegue assinar o canal.
6. O payload do evento no socket nao contem leitura nem evento sismico.
7. `medalhao:ingerir` sem dado novo (hash identico) **nao** gera evento.
8. Suite verde no escopo do medalhao.

## 7. Riscos

**Producao nunca transmitiu.** O servico sobe, o Caddy roteia, mas nenhum byte de
broadcast passou por ali. O upgrade de WebSocket atraves do proxy e a autorizacao
de canal privado sao os dois pontos com maior chance de surpresa. Mitigado pela
degradacao silenciosa: se falhar, desligar o broadcasting devolve o comportamento
atual sem reverter codigo.

**Conexao ociosa por aba.** Cada aba aberta passa a manter uma conexao no Reverb.
Para um sistema interno e trivial, mas e consumo novo que antes nao existia.

**As `VITE_REVERB_*` sao build time.** Mudar host ou porta exige rebuild do
frontend, nao so restart de container. Vale registrar para nao virar diagnostico
demorado depois.

## 8. Fora de escopo

- As outras 28 listagens: o mecanismo serve, a fiacao fica para depois da prova.
- Tempo real para acao de outro usuario (RAT, plantao, decretacoes) — e a segunda
  prioridade que o levantamento identificou, com desenho proprio.
- Refletir a propria acao do usuario sem F5: e problema de fluxo do Inertia, nao
  de broadcasting, e tem solucao diferente.
- Presenca (quem esta vendo a mesma tela) e notificacao push por WebSocket.
