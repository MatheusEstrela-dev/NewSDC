# Atualizacao ao vivo das listagens de dominio — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pedidos (Ajuda Humanitaria), RAT e PMDA refletem mudanca de status sem F5, reusando o mecanismo do medalhao e escopando o canal pela permissao da propria rota.

**Architecture:** Um evento generico `RecursoAtualizado` com payload minimo, despachado apos o commit no ponto unico de mudanca de status de cada modulo. A autorizacao de canal delega a uma tabela unica recurso -> permissao, que recusa por padrao. O front escuta pelo `useAtualizacaoAoVivo` que ja existe, com debounce novo.

**Tech Stack:** Laravel 12, PHP 8.4, Reverb 1.10, Laravel Echo 2.3 + pusher-js 8.5, Inertia + Vue 3, PostgreSQL, PHPUnit 11.

**Spec:** `SDC/docs/superpowers/specs/2026-09-03-tempo-real-listagens-design.md`

## Global Constraints

- Todo arquivo PHP comeca com `declare(strict_types=1);`.
- Classes de teste sao `final` e os metodos usam snake_case em pt-BR.
- **Sem emojis no codigo** (regra de ouro 2).
- **Sem acentos** em nome de classe, metodo, canal, chave de config e mensagem de log.
- Commits em gitmoji: `<emoji> tipo(escopo): descricao em pt-BR`. Escopo `tempo-real`, `humanitaria`, `rat` ou `pmda`.
- **Nao incluir trailer `Co-Authored-By`.**
- **Arquivos de teste NAO entram nos commits.** `tests` esta no `.gitignore` (linha 39), entao `git add` de teste e recusado em silencio -- os testes existem no worktree como motor do TDD e nada mais.
- O evento **nunca** carrega dado de dominio, apenas `{recurso, escopo, atualizado_em}`.
- Recurso sem entrada na tabela de permissoes **nao autoriza ninguem**.

## Ambiente de execucao

Este worktree nasce sem `vendor`, `node_modules`, `.env`, `bootstrap/cache` e
`public/build`. Cada ausencia falha de um jeito diferente e nenhuma diz o que
falta.

```bash
cd "C:/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+tempo-real-listagens"
cp ../../../SDC/.env SDC/.env
mkdir -p SDC/bootstrap/cache SDC/storage/framework/{cache/data,sessions,views,testing} SDC/storage/logs
MSYS_NO_PATHCONV=1 docker run --rm -e COMPOSER_PROCESS_TIMEOUT=0 -e COMPOSER_ALLOW_SUPERUSER=1 \
  -v "$(pwd -W)/SDC:/app" -w /app newsdc-swoole-dev:latest \
  composer install --no-scripts --no-interaction --ignore-platform-req=php
```

Confira `SDC/vendor/autoload.php` **e** `SDC/vendor/bin/phpunit` antes de seguir:
sem `COMPOSER_PROCESS_TIMEOUT=0` o install morre no meio, e com pipe o exit code
engana.

**O install VAI falhar no fim, e nao e fatal** (visto em 2026-09-03): depois de
instalar os 216 pacotes ele morre em
`Could not delete /app/vendor/composer/<hash>/.../src/Schema`, limpeza de
diretorio temporario no bind mount do Windows. O sintoma engana --
`vendor/bin/phpunit` existe e `vendor/autoload.php` NAO, porque a geracao do
autoloader nunca rodou. Resolver com um passo separado:

```bash
MSYS_NO_PATHCONV=1 docker run --rm -e COMPOSER_ALLOW_SUPERUSER=1 \
  -v "$(pwd -W)/SDC:/app" -w /app newsdc-swoole-dev:latest \
  composer dump-autoload --optimize --no-scripts --no-interaction
```

O `--optimize` **nao e opcional**: o projeto tem classe declarada em arquivo de
outro nome -- `PmdaAnaliseController` vive dentro de
`app/Modules/Pmda/Controllers/PmdaController.php` -- e sem o classmap a rota
`pmda.analises.index` morre com "Target class does not exist".

O PHP do host e 8.1 (Laragon tem 8.3) e o vendor exige 8.4, entao **tudo roda em
container**. Crie o helper:

```bash
cat > /c/tmp/trl.sh <<'EOF'
#!/usr/bin/env bash
# Roda artisan/phpunit com o vendor DO WORKTREE sobre PHP 8.4.
#
# As APP_*_CACHE apontam para o bootstrap/cache da IMAGEM, que existe e e
# gravavel. NAO apague bootstrap/cache/config.php do worktree principal: isso
# derruba o Octane, que so volta apos ~3min de restart por causa do chmod -R.
set -euo pipefail
WT="C:/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+tempo-real-listagens/SDC"
C=/var/www/bootstrap/cache
MSYS_NO_PATHCONV=1 exec docker run --rm --network newsdc-dev_default \
  -v "${WT}:/app" -w /app \
  -e APP_ENV=testing \
  -e APP_CONFIG_CACHE=$C/trl-config.php \
  -e APP_ROUTES_CACHE=$C/trl-routes.php \
  -e APP_EVENTS_CACHE=$C/trl-events.php \
  -e APP_SERVICES_CACHE=$C/trl-services.php \
  -e APP_PACKAGES_CACHE=$C/trl-packages.php \
  -e BROADCAST_CONNECTION="${BROADCAST_CONNECTION:-null}" \
  -e REVERB_APP_ID=sdc-dev -e REVERB_APP_KEY=sdc-dev-key \
  -e REVERB_APP_SECRET=sdc-dev-secret \
  -e REVERB_HOST=reverb -e REVERB_PORT=8080 -e REVERB_SCHEME=http \
  newsdc-swoole-dev:latest "$@"
EOF
chmod +x /c/tmp/trl.sh
```

**`BROADCAST_CONNECTION=reverb` sem as `REVERB_*` derruba a aplicacao INTEIRA**,
nao apenas o tempo real: `channels.php` resolve o broadcaster de forma eager. Por
isso o helper ja define as duas coisas juntas.

`.env.testing` usa `sqlite :memory:`, e sob ele 60 testes do escopo do medalhao
sao PULADOS. **`sdc_test` nao serve**: os mesmos 60 dao ERRO nele (falta schema e
dado semeado). O banco que funciona nasce do `template_postgis`, que ja existe no
container com PostGIS 3.6.3:

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres \
  -c "CREATE DATABASE sdc_tempo_real TEMPLATE template_postgis;"
```

Depois `php artisan migrate --force` (246 passos, verde) e
`php artisan db:seed --class=MunicipiosMGSeeder` (853 municipios). Sem o seed,
teste que busca municipio real falha com `Undefined array key 0`.

```bash
# acrescente ao helper quando precisar de pgsql
-e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
-e DB_DATABASE=sdc_tempo_real -e DB_USERNAME=sdc -e DB_PASSWORD=secret
```

**`DB_HOST` e `db`, nao `newsdc_db`.** Os aliases do container do banco na rede
sao `newsdc_dev_db` e `db`; `newsdc_db` -- que e o valor do `.env` -- escapa para
o DNS externo e resolve num IP publico (`200.198.15.68`). A falha aparece como
`SQLSTATE[08006] server closed the connection unexpectedly`, que parece banco
caido e nao nome errado.

Os testes deste projeto usam **`DatabaseTransactions`**, nao `RefreshDatabase`:
rodam contra o schema existente e desfazem por rollback. Isso preserva o seed dos
municipios entre rodadas -- `RefreshDatabase` faria `migrate:fresh` e o dropparia.

**Os arquivos de teste de outros modulos NAO estao aqui.** `tests` e gitignored,
entao cada suite vive so na worktree onde foi escrita. Para rodar o escopo do
medalhao e preciso copiar `SDC/tests/{Feature,Unit}/{Inmet,Sismos,Medalhao}` e
`SDC/tests/Fixtures` da worktree principal.

### Como olhar o Reverb sem navegador

O `pusher/pusher-php-server` esta no vendor da imagem, entao nao precisa de
ferramenta nova. Diz quem esta assinado e quantas conexoes existem -- e como
verificar as Tasks 5 a 8 sem depender de DevTools:

```bash
docker exec newsdc_dev_app php -r '
require "/var/www/vendor/autoload.php";
$p = new Pusher\Pusher("sdc-dev-key","sdc-dev-secret","sdc-dev",
    ["host"=>"reverb","port"=>8080,"scheme"=>"http","useTLS"=>false]);
echo json_encode($p->get_channels()->channels).PHP_EOL;
echo json_encode($p->get("/connections")).PHP_EOL;'
```

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `app/Modules/Shared/Support/CanaisDeListagem.php` | Tabela recurso -> permissao; recusa por padrao |
| `app/Modules/Shared/Events/RecursoAtualizado.php` | O evento, generico por recurso e escopo |
| `routes/channels.php` | `listagem.{recurso}` e `listagem.{recurso}.{escopo}` |
| `resources/js/Composables/useAtualizacaoAoVivo.js` | Ganha `debounceMs` |
| `app/Modules/AjudaHumanitaria/Services/TramitacaoService.php` | Emite apos o commit |
| `resources/js/Pages/AjudaHumanitaria/Pedidos/Index.vue` | Consome |
| `app/Modules/Pmda/Services/PmdaService.php` | Emite com escopo de municipio |
| `resources/js/Pages/Pmda/Analises/Index.vue` | Consome |
| `app/Modules/Rat/Services/RatOcorrenciaService.php` | Emite |
| `resources/js/Pages/RatIndex.vue` | Consome |

---

### Task 1: A tabela de permissoes, que recusa por padrao

Vem primeiro porque e a peca de seguranca: canal novo sem entrada aqui nao pode
autorizar ninguem. Escrever o canal antes da tabela e o caminho para um canal
permissivo entrar sem que ninguem note.

**Files:**
- Create: `SDC/app/Modules/Shared/Support/CanaisDeListagem.php`
- Test: `SDC/tests/Unit/TempoReal/CanaisDeListagemTest.php`

**Interfaces:**
- Produces: `CanaisDeListagem::permissaoDe(string $recurso): ?string` e `CanaisDeListagem::recursos(): array`. Tasks 3 e 6 dependem disso.

- [x] **Step 1: Escrever o teste que falha**

Cobrir, no minimo: recurso desconhecido devolve `null`; cada recurso conhecido
devolve a permissao da sua rota (`pedidos-ah` -> `humanitaria.pedidos.view`,
`rat` -> `rat.protocolos.view`, `pmda-analises` -> `pmda.analise.view`); e que a
lista de recursos nao contem duplicata.

O teste que importa e o do recurso desconhecido: e ele que prova a recusa por
padrao, que e a razao de a classe existir.

- [x] **Step 2: Rodar e ver falhar**

Run: `/c/tmp/trl.sh php vendor/bin/phpunit --filter=CanaisDeListagemTest`
Expected: FAIL — classe nao existe.

- [x] **Step 3: Criar a classe**

Uma constante `MAPA` de recurso -> permissao e dois metodos estaticos. Sem
config, sem container: e uma tabela, e a razao de nao ser config e que ela precisa
ser lida em `channels.php`, no boot, antes de config estar quente.

Documentar no cabecalho que **o valor tem de ser a mesma permissao do
`middleware('can:...')` da rota**, e que divergencia entre os dois e o vazamento
de escopo descrito no risco 1 do spec.

- [x] **Step 4: Rodar e ver passar**

Run: `/c/tmp/trl.sh php vendor/bin/phpunit --filter=CanaisDeListagemTest`
Expected: PASS.

- [x] **Step 5: Commit** — junto com a Task 2 (ver nota de atomicidade abaixo).

---

### Task 2: O evento

**Files:**
- Create: `SDC/app/Modules/Shared/Events/RecursoAtualizado.php`
- Test: `SDC/tests/Unit/TempoReal/RecursoAtualizadoTest.php`

**Interfaces:**
- Produces: `RecursoAtualizado::__construct(string $recurso, ?int $escopo = null)`; `broadcastOn(): PrivateChannel`; `broadcastAs(): string` = `RecursoAtualizado`; `broadcastWith(): array{recurso,escopo,atualizado_em}`. Tasks 5, 6 e 7 despacham; Task 4 escuta o nome.

- [x] **Step 1: Escrever o teste que falha**

Provar: implementa `ShouldBroadcast` **e** `ShouldDispatchAfterCommit`; sem
escopo o canal e `private-listagem.pedidos-ah`; com escopo e
`private-listagem.pmda-analises.3141`; `broadcastAs()` e estavel; e o payload tem
exatamente as tres chaves e nenhum campo de dominio.

O teste do `ShouldDispatchAfterCommit` e o mais importante do plano: e a
diferenca entre atualizar a tela e mostrar dado velho com cara de novo (secao 2.1
do spec).

- [x] **Step 2: Rodar e ver falhar** — classe nao existe.

- [x] **Step 3: Criar o evento**

Vive em `Modules/Shared/Events` e nao em cada dominio porque e generico por
construcao: recurso novo nao precisa de classe nova. Documentar no cabecalho por
que o payload nao leva a linha alterada -- alem de duplicar a serializacao do
`Resource`, furaria o escopo, porque a linha visivel para um assinante pode nao
ser visivel para outro no mesmo canal.

- [x] **Step 4: Rodar e ver passar.**

- [x] **Step 5: Commit**

```bash
git add SDC/app/Modules/Shared/Support/CanaisDeListagem.php \
        SDC/app/Modules/Shared/Events/RecursoAtualizado.php
git commit -m "✨ feat(tempo-real): evento generico de listagem e tabela de canais"
```

Tabela e evento entram JUNTOS: nenhum dos dois entrega nada sozinho, e separa-los
deixaria um commit com um evento que ninguem pode assinar (regra de ouro 12).

---

### Task 3: Os canais

**Files:**
- Modify: `SDC/routes/channels.php`
- Test: `SDC/tests/Feature/TempoReal/CanalListagemTest.php`, `SDC/tests/Feature/TempoReal/CanalPmdaEscopoTest.php`

**Interfaces:**
- Consumes: `CanaisDeListagem` (Task 1), `/broadcasting/auth` (ja registrado).
- Produces: canais autorizados. Task 8 verifica.

- [x] **Step 1: Escrever os testes que falham**

`CanalListagemTest`: visitante 403; usuario COM a permissao autoriza; usuario SEM
a permissao 403; recurso inexistente 403. Os dois ultimos sao o ponto -- um canal
que autoriza qualquer autenticado passaria nos dois primeiros.

`CanalPmdaEscopoTest`: COMPDEC do municipio A nao autoriza o canal do municipio
B; CEDEC (sem municipio) autoriza qualquer um.

Precisam de `RefreshDatabase` (usuario, permissao e orgao no banco) e rodam sob
`BROADCAST_CONNECTION=reverb`.

- [x] **Step 2: Rodar e ver falhar** — os canais nao existem, tudo 403 (inclusive o que deveria autorizar).

- [x] **Step 3: Declarar os canais**

Dois `Broadcast::channel`, ambos delegando a `CanaisDeListagem`: o de recurso sem
escopo e o de recurso com escopo. O de escopo tambem confere o perfil, via
`PerfilPmda`/`OrgaoDeLotacao`, e nao apenas a permissao.

Comentar que a permissao NAO esta escrita aqui de proposito: esta na tabela, para
que a divergencia com a rota apareca como teste vermelho.

- [x] **Step 4: Rodar e ver passar.**

- [x] **Step 5: Verificar que a aplicacao ainda sobe com o driver do Reverb**

```bash
BROADCAST_CONNECTION=reverb /c/tmp/trl.sh php artisan --version
```

Expected: a versao imprime. Se morrer com
`Pusher\Pusher::__construct(): Argument #1 ($auth_key) must be of type string, null given`,
as `REVERB_*` nao chegaram -- nao e bug do canal.

- [x] **Step 6: Commit**

```bash
git add SDC/routes/channels.php
git commit -m "🔒 security(tempo-real): canais de listagem autorizados pela permissao da rota"
```

---

### Task 4: Debounce no composable

Vem antes de qualquer pagina: fiar primeiro e adicionar o debounce depois deixa
uma janela em que uma rajada de mudanca de status multiplica reload por viewer.

**Files:**
- Modify: `SDC/resources/js/Composables/useAtualizacaoAoVivo.js`

**Interfaces:**
- Produces: opcao `debounceMs` (padrao 400). Tasks 5 a 7 consomem.

- [x] **Step 1: Implementar**

Um timer que reinicia a cada evento e so entao chama `recarregar()`. Cancelar o
timer no `onBeforeUnmount`, junto do `visibilitychange` e do `leave()` -- sem
isso um reload disparado depois do unmount navega uma pagina que nao existe mais.

O debounce **nao** substitui a logica de aba oculta: aba oculta continua marcando
pendencia sem agendar timer.

Manter o padrao em 400ms e nao em zero: o medalhao nao precisava de debounce e
passar a precisar de configuracao explicita em cada pagina seria armadilha para
quem fiar a proxima.

- [x] **Step 2: Verificar que o medalhao nao regrediu**

```bash
cd SDC && npx vite build 2>&1 | tail -3
```

Expected: build sem erro. As duas paginas do medalhao passam a debouncar 400ms,
o que e invisivel para um pipeline que coleta a cada 10 minutos.

- [x] **Step 3: Commit**

```bash
git add SDC/resources/js/Composables/useAtualizacaoAoVivo.js
git commit -m "⚡ perf(tempo-real): coalesce rajada de eventos em um reload"
```

---

### Task 5: Pedidos de Ajuda Humanitaria

A primeira pagina a ser fiada, e de proposito: e a unica das tres cujo ponto de
mudanca esta em transacao, entao e onde o `ShouldDispatchAfterCommit` realmente
protege.

**Files:**
- Modify: `SDC/app/Modules/AjudaHumanitaria/Services/TramitacaoService.php`
- Modify: `SDC/resources/js/Pages/AjudaHumanitaria/Pedidos/Index.vue`
- Test: adicao em `SDC/tests/Feature/AjudaHumanitaria/TramitacaoServiceTest.php`

- [ ] **Step 1: Escrever o teste que falha**

`Event::fake()` e assercao de que `tramitar()` emite `RecursoAtualizado` com
recurso `pedidos-ah`. Provar tambem que `finalizarPorHomologacao()` emite -- os
dois caminhos passam por `executar()`, e o teste garante que continuem passando.

- [ ] **Step 2: Rodar e ver falhar.**

- [ ] **Step 3: Emitir no `executar()`**

Dentro de `executar()`, apos a `DB::transaction`. Como o evento e
`ShouldDispatchAfterCommit`, despachar de dentro do bloco tambem funciona -- e
mais seguro, porque nao depende de alguem lembrar de manter a chamada fora.

- [ ] **Step 4: Fiar a pagina**

```js
useAtualizacaoAoVivo({
    canal: 'listagem.pedidos-ah',
    evento: '.RecursoAtualizado',
    props: ['pedidos', 'estatisticas'],
});
```

`estatisticas` entra aqui porque e closure de agregacao sem cache -- rebusca junto
e fica coerente com a tabela. E o oposto do RAT (Task 7), e a diferenca e o cache.

- [ ] **Step 5: Verificar**

```bash
cd SDC && npx vite build 2>&1 | tail -3
/c/tmp/trl.sh php vendor/bin/phpunit --filter="AjudaHumanitaria"
```

Expected: build sem erro, suite verde. A suite prova que as props que o `only:`
pede existem com esses nomes -- se alguem renomear `pedidos`, o `only:` passaria a
pedir prop inexistente e o reload silenciosamente nao atualizaria nada.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/AjudaHumanitaria/Services/TramitacaoService.php \
        SDC/resources/js/Pages/AjudaHumanitaria/Pedidos/Index.vue
git commit -m "✨ feat(humanitaria): fila de pedidos atualiza sem F5"
```

---

### Task 6: PMDA, com escopo de municipio

**Files:**
- Modify: `SDC/app/Modules/Pmda/Services/PmdaService.php`
- Modify: `SDC/resources/js/Pages/Pmda/Analises/Index.vue`
- Test: adicao no teste de servico do PMDA

- [ ] **Step 1: Escrever o teste que falha**

Assercao de que `transicionar()` emite `RecursoAtualizado('pmda-analises', <municipio do plano>)`.
O escopo e o ponto: um evento sem escopo aqui avisaria COMPDEC de outro
municipio, que e o vazamento por canal lateral da secao 2.2 do spec.

- [ ] **Step 2: Rodar e ver falhar.**

- [ ] **Step 3: Emitir no `transicionar()`**

E o ponto privado unico das transicoes, entao todos os caminhos publicos
(`enviar`, aprovar, rejeitar) passam por ele de graca. Confirmar lendo os
chamadores, e nao assumindo.

- [ ] **Step 4: Fiar a pagina**

**O controller de analises nao e um arquivo proprio.** `PmdaAnaliseController` e
uma segunda classe declarada dentro de
`app/Modules/Pmda/Controllers/PmdaController.php`, e so resolve por causa do
classmap otimizado -- procurar por nome de arquivo nao acha.

O canal precisa do municipio, que vem de prop do controller. Para CEDEC (sem
municipio no escopo) assinar o canal coringa. **Se a prop nao existir, adicionar
ao controller e nao inventar o valor no cliente** -- o cliente nao sabe o escopo
do perfil, e adivinhar erra para super-admin lotado em COMPDEC (ver o comentario
de `municipioDoEscopo()`).

- [ ] **Step 5: Verificar**

```bash
cd SDC && npx vite build 2>&1 | tail -3
/c/tmp/trl.sh php vendor/bin/phpunit --filter="Pmda"
```

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Pmda/Services/PmdaService.php \
        SDC/resources/js/Pages/Pmda/Analises/Index.vue
git commit -m "✨ feat(pmda): fila de analises atualiza sem F5, escopada por municipio"
```

---

### Task 7: RAT

Ultima, e a que tem mais chance de nao dar certo: e a unica sem ponto de mudanca
de status declarado como unico.

**Files:**
- Modify: `SDC/app/Modules/Rat/Services/RatOcorrenciaService.php`
- Modify: `SDC/resources/js/Pages/RatIndex.vue`

- [ ] **Step 1: Confirmar que ha um unico ponto de escrita de `status`**

```bash
cd SDC && grep -rn "'status'" app/Modules/Rat/ --include=*.php | grep -v Resource | grep -v DTO
```

Se aparecer escrita de `status` fora do `RatOcorrenciaService`, **pare e reavalie**:
emitir de um ponto so faria a pagina perder eventos em silencio, que e pior que
nao ter tempo real (risco 3 do spec). A saida, nesse caso, e um observer no
`RatOcorrencia`, nao um `dispatch` em cada chamador.

- [ ] **Step 2: Escrever o teste, rodar, ver falhar, emitir, ver passar**

Mesma forma das Tasks 5 e 6.

- [ ] **Step 3: Fiar a pagina, SEM `statistics`**

```js
useAtualizacaoAoVivo({
    canal: 'listagem.rat',
    evento: '.RecursoAtualizado',
    props: ['rats'],
});
```

`statistics` fica fora porque e `Cache::remember(..., 300, ...)`: rebuscar
devolveria o valor cacheado por ate 5 minutos e a tela mostraria tabela nova com
contador velho -- pior que os dois velhos juntos. Decisao registrada em 3.4 do
spec; invalidar a chave foi considerado e recusado porque transformaria cada
mudanca em recomputo dos quatro counts para o estado inteiro.

- [ ] **Step 4: Verificar**

```bash
cd SDC && npx vite build 2>&1 | tail -3
/c/tmp/trl.sh php vendor/bin/phpunit --filter="Rat"
```

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/Rat/Services/RatOcorrenciaService.php \
        SDC/resources/js/Pages/RatIndex.vue
git commit -m "✨ feat(rat): listagem de protocolos atualiza sem F5"
```

---

### Task 8: Verificacao ponta a ponta

**Files:** nenhum arquivo novo; validacao dos 10 criterios da secao 6 do spec.

- [ ] **Step 1: Degradacao com broadcasting desligado**

```bash
docker compose -f SDC/docker/compose.dev.yml stop reverb
```

Abrir as tres listagens. Expected: carregam normalmente, console sem erro nao
tratado. Religar o reverb depois.

- [ ] **Step 2: Duas sessoes, uma tramitacao**

Abrir a fila de pedidos em duas sessoes (navegadores ou perfis distintos, nao
duas abas -- duas abas compartilham a mesma conexao e o mesmo usuario). Tramitar
em uma. Expected: a outra reflete sem F5.

- [ ] **Step 3: O reload vem DEPOIS do commit**

Repetir o Step 2 e conferir que a linha aparece com o status NOVO. Este e o
criterio que o `ShouldDispatchAfterCommit` existe para garantir; se aparecer o
status antigo, o evento esta saindo antes do commit.

- [ ] **Step 4: Scroll e pagina da tabela sobrevivem**

Repetir com a tabela na pagina 3 e a janela rolada.

- [ ] **Step 5: Aba em segundo plano** — nada rebuscado enquanto oculta; ao voltar, atualiza uma vez.

- [ ] **Step 6: Rajada gera um reload**

Tramitar dez pedidos em sequencia rapida. Expected: **um** `GET` da listagem no
log do Octane por viewer, nao dez.

```bash
docker logs --since 2m newsdc_dev_app 2>&1 | grep -c "GET /ajuda-humanitaria/pedidos"
```

- [ ] **Step 7: Quem nao tem a permissao nao assina**

```bash
curl -s -o /dev/null -w "sem sessao http=%{http_code}\n" -X POST --max-time 10 \
  -d 'channel_name=private-listagem.pedidos-ah&socket_id=1234.5678' \
  http://localhost:8000/broadcasting/auth
```

Expected: `403` ou `302`, nunca `200`. O caso de usuario autenticado SEM a
permissao esta coberto por `CanalListagemTest` (Task 3), porque exige sessao.

- [ ] **Step 8: COMPDEC de outro municipio nao assina o canal PMDA** — coberto por `CanalPmdaEscopoTest`; conferir que o teste esta verde.

- [ ] **Step 9: O payload nao leva dado de dominio**

DevTools, aba Network, filtro WS, inspecionar o frame. Expected: apenas
`recurso`, `escopo` e `atualizado_em`.

- [ ] **Step 10: Suite do escopo**

Run: `/c/tmp/trl.sh php vendor/bin/phpunit --filter="TempoReal|AjudaHumanitaria|Rat|Pmda"`
Expected: verde. Rodar tambem contra `sdc_tempo_real` em pgsql, para nao aceitar como
verde uma suite em que metade pulou.

- [ ] **Step 11: Conferencia contra os criterios do spec**

Percorrer os 10 criterios da secao 6 e marcar cada um. **O que nao puder ser
verificado fica escrito como nao verificado**, com o motivo -- foi a Task 9 do
plano do medalhao ficar meses "concluida" sem uma unica verificacao registrada
que motivou esta instrucao.

- [ ] **Step 12: Commit final**

```bash
git add SDC/docs/superpowers/plans/2026-09-03-tempo-real-listagens.md
git commit -m "✅ test(tempo-real): verificacao ponta a ponta das listagens"
```

---

## Notas de execucao

**Branch.** Este plano vive em `feat/tempo-real-listagens`, criada de
`feat/tempo-real-echo-unico` em `271e5886` -- que traz a consolidacao do Echo. Sem
ela ha duas conexoes por aba e o `leave()` atua na instancia errada, entao **nao
rebase este trabalho para antes de `e4b7e7ae`**.

**Ordem.** Task 1 e pre-requisito da 3. A 2 e independente da 1, mas commita
junto. A 4 vem antes de 5, 6 e 7 para nao abrir janela sem debounce. As 5, 6 e 7
sao independentes entre si; a 8 depende de todas.

**A branch base carrega um commit de terceiro.** `73526261`
(`🗃️ db(medalhao): consolida verificado_em`) entrou em
`feat/tempo-real-echo-unico` por outra sessao trabalhando no mesmo worktree
principal. Nao e deste escopo; se a base for reorganizada, ele sai.

**O que NAO commitar:** `SDC/.env`, `SDC/docker/.env` e qualquer arquivo sob
`SDC/tests/` (ja coberto pelo `.gitignore`).

**Fora de escopo, por decisao registrada no spec:** as outras 25 listagens,
presenca, escopar canal por filtro ativo, e reusar o canal de notificacao para
refletir mudanca notificada a mim.
