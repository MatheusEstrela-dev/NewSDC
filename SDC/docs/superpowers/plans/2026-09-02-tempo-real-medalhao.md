# Atualizacao ao vivo das telas do medalhao — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Meteorologia e Sismos passam a refletir dado novo sem F5, ligando o Reverb que ja existe em producao e servindo nada.

**Architecture:** Os jobs que refazem as matviews de `gold` emitem um evento `ShouldBroadcast` com payload minimo. O front escuta por Echo e pede ao Inertia um recarregamento parcial das props. O controller segue sendo a unica fonte das props; o socket carrega apenas o aviso.

**Tech Stack:** Laravel 12, PHP 8.4, Reverb 1.10 (WebSocket), Laravel Echo 2.3 + pusher-js 8.5, Inertia + Vue 3, PostgreSQL, PHPUnit 11.

**Spec:** `SDC/docs/superpowers/specs/2026-09-02-tempo-real-medalhao-design.md`

## Global Constraints

- Todo arquivo PHP comeca com `declare(strict_types=1);`.
- Classes de teste sao `final` e os metodos usam snake_case em pt-BR.
- **Sem emojis no codigo** (regra de ouro 2).
- **Sem acentos** em nome de classe, metodo, canal, chave de config e mensagem de log.
- Commits em gitmoji: `<emoji> tipo(escopo): descricao em pt-BR`. Escopo `tempo-real`, `medalhao`, `inmet` ou `sismos`.
- **Nao incluir trailer `Co-Authored-By`.**
- **Arquivos de teste NAO entram nos commits** (`.gitignore` linha 39 e regra de ouro 10). Os testes existem no worktree como motor do TDD; os `git add` incluem so codigo de producao.
- O evento **nunca** carrega dado de dominio — apenas `{grupo, atualizado_em}`.
- O composable **nunca** lanca quando `window.Echo` e `undefined`.

## Ambiente de execucao

PHP do host e 8.3 e o vendor exige 8.4. Rode tudo em container. Crie o helper:

```bash
cat > /c/tmp/tr.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
WT="C:/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+tempo-real-medalhao/SDC"
rm -f "${WT}/bootstrap/cache/config.php" "${WT}"/bootstrap/cache/routes-*.php 2>/dev/null || true
MSYS_NO_PATHCONV=1 exec docker run --rm --network newsdc-dev_default \
  -v "${WT}:/app" -w /app \
  -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE="${DB_DATABASE:-sdc_medalhao}" -e DB_USERNAME=sdc -e DB_PASSWORD=secret \
  -e REDIS_HOST=redis -e REDIS_PORT=6379 \
  -e BROADCAST_CONNECTION="${BROADCAST_CONNECTION:-null}" \
  newsdc-swoole-dev:latest "$@"
EOF
chmod +x /c/tmp/tr.sh
```

Setup do worktree, que nasce sem nada disto (cada ausencia falha de um jeito diferente):

```bash
cp ../../../SDC/.env SDC/.env
mkdir -p SDC/bootstrap/cache SDC/storage/framework/{cache/data,sessions,views,testing} SDC/storage/logs
mkdir -p SDC/public/build && cp -r ../../../SDC/public/build/. SDC/public/build/
MSYS_NO_PATHCONV=1 docker run --rm -e COMPOSER_PROCESS_TIMEOUT=0 -e COMPOSER_ALLOW_SUPERUSER=1 \
  -v "$(pwd -W)/SDC:/app" -w /app newsdc-swoole-dev:latest \
  composer install --no-scripts --no-interaction --ignore-platform-req=php
```

Confira `SDC/vendor/autoload.php` E `SDC/vendor/bin/phpunit` antes de seguir: sem
`COMPOSER_PROCESS_TIMEOUT=0` o install morre no meio, e com pipe o exit code
engana.

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `config/app.php` | Registra o `BroadcastServiceProvider` e corrige o comentario caduco |
| `routes/channels.php` | Autoriza `medalhao.{grupo}` |
| `app/Modules/Medalhao/Events/GoldAtualizado.php` | O evento, generico por grupo |
| `app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php` | Emite apos o refresh |
| `app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php` | Emite apos o refresh |
| `docker/compose.dev.yml` | Servico `reverb` e variaveis de broadcast |
| `resources/js/echo.js` | Inicializa `window.Echo`, so se houver config |
| `resources/js/app.js` | Importa `./echo` |
| `resources/js/Composables/useAtualizacaoAoVivo.js` | Assina o canal e recarrega parcialmente |
| `resources/js/Pages/Inmet/MapaInmet.vue` | Consome |
| `resources/js/Pages/Sismos/MapaSismos.vue` | Consome |

---

### Task 1: Registrar o provider sem derrubar a aplicacao

E o unico passo que pode quebrar o boot inteiro, por isso vem primeiro e sozinho.

**Files:**
- Modify: `SDC/config/app.php:174-187`

**Interfaces:**
- Produces: rota `/broadcasting/auth` registrada e `routes/channels.php` carregado. Tasks 2 e 9 dependem disso.

- [ ] **Step 1: Confirmar que o motivo do comentario caducou**

```bash
MSYS_NO_PATHCONV=1 docker run --rm --entrypoint sh newsdc-swoole-dev:latest \
  -c 'php -r "require \"/var/www/vendor/autoload.php\"; echo class_exists(\"Pusher\\\\Pusher\") ? \"tem Pusher\" : \"NAO tem Pusher\";"'
```

Expected: `tem Pusher`. Se vier `NAO tem Pusher`, **pare**: a instrucao do
comentario ainda vale e o `composer require pusher/pusher-php-server` e
obrigatorio antes de prosseguir.

- [ ] **Step 2: Descomentar o provider e corrigir o comentario**

Em `SDC/config/app.php`, substituir todo o bloco de comentario mais a linha
comentada por:

```php
        // Necessario para tempo real: registra /broadcasting/auth e carrega
        // routes/channels.php.
        //
        // Ficou desligado por um tempo porque o boot resolve o broadcaster do
        // driver ativo, e com BROADCAST_CONNECTION=reverb sem
        // pusher/pusher-php-server a aplicacao caia no boot com
        // Class "Pusher\Pusher" not found.
        //
        // Isso deixou de ser um problema: pusher/pusher-php-server entra
        // transitivamente com laravel/reverb (que pede ^7.2) e esta no lock e no
        // vendor da imagem. Nao ha composer require a fazer.
        //
        // O risco residual e a natureza do boot: driver mal configurado derruba
        // a aplicacao inteira, nao apenas o tempo real. Ao mexer em
        // BROADCAST_CONNECTION, verifique que a aplicacao sobe.
        App\Providers\BroadcastServiceProvider::class,
```

- [ ] **Step 3: Verificar que a aplicacao ainda sobe, com broadcasting desligado**

```bash
BROADCAST_CONNECTION=null /c/tmp/tr.sh php artisan --version
BROADCAST_CONNECTION=null /c/tmp/tr.sh php artisan route:list --path=broadcasting
```

Expected: a versao do Laravel imprime sem excecao, e `broadcasting/auth` aparece
na lista de rotas.

- [ ] **Step 4: Verificar que sobe com o driver do Reverb E as credenciais**

As credenciais sao obrigatorias aqui. `channels.php` chama `Broadcast::channel()`,
que resolve o broadcaster de forma eager; sem `REVERB_APP_KEY` o `Pusher\Pusher`
recebe `null` e a aplicacao morre no boot.

```bash
WT="C:/Users/x24679188/Documents/Github/NewSDC/.claude/worktrees/feat+tempo-real-medalhao/SDC"
rm -f "$WT/bootstrap/cache/config.php"
MSYS_NO_PATHCONV=1 docker run --rm --network newsdc-dev_default \
  -v "$WT:/app" -w /app \
  -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_medalhao -e DB_USERNAME=sdc -e DB_PASSWORD=secret \
  -e REDIS_HOST=redis -e BROADCAST_CONNECTION=reverb \
  -e REVERB_APP_ID=sdc-dev -e REVERB_APP_KEY=sdc-dev-key \
  -e REVERB_APP_SECRET=sdc-dev-secret \
  -e REVERB_HOST=reverb -e REVERB_PORT=8080 -e REVERB_SCHEME=http \
  newsdc-swoole-dev:latest php artisan --version
```

Expected: `Laravel Framework 12.58.0`.

- [ ] **Step 4b: Conhecer o modo de falha, de proposito**

```bash
BROADCAST_CONNECTION=reverb /c/tmp/tr.sh php artisan --version
```

Expected: **FALHA**, com
`Pusher\Pusher::__construct(): Argument #1 ($auth_key) must be of type string, null given`.

Isso nao e defeito a corrigir: e a consequencia de registrar o provider, e o
motivo pelo qual ele ficou desligado. Rodar este passo serve para o executor
reconhecer o erro se topar com ele depois, em vez de perder tempo procurando bug
no codigo.

**A regra que sai daqui:** `BROADCAST_CONNECTION=reverb` sem as `REVERB_*` derruba
a aplicacao INTEIRA, nao apenas o tempo real. Os dois valores andam juntos, em
qualquer ambiente. Producao ja os define; a Task 5 os define em dev.

- [ ] **Step 5: Commit**

```bash
git add SDC/config/app.php
git commit -m "🔧 config(tempo-real): registra o BroadcastServiceProvider"
```

---

### Task 2: Canal privado autorizado como a pagina

**Files:**
- Modify: `SDC/routes/channels.php`
- Test: `SDC/tests/Feature/TempoReal/CanalMedalhaoTest.php`

**Interfaces:**
- Consumes: `/broadcasting/auth` (Task 1).
- Produces: canal `medalhao.{grupo}` autorizado a qualquer usuario autenticado. Task 3 emite nele; Task 7 assina.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\TempoReal;

use App\Models\User;
use Tests\TestCase;

final class CanalMedalhaoTest extends TestCase
{
    public function test_visitante_nao_autoriza_o_canal(): void
    {
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-medalhao.inmet',
            'socket_id' => '1234.5678',
        ])->assertStatus(403);
    }

    public function test_usuario_autenticado_autoriza_o_canal(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/broadcasting/auth', [
                'channel_name' => 'private-medalhao.inmet',
                'socket_id' => '1234.5678',
            ])
            ->assertOk();
    }

    public function test_autoriza_qualquer_grupo_do_medalhao(): void
    {
        // O canal e parametrizado: fonte nova ganha tempo real sem canal novo.
        $this->actingAs(User::factory()->create())
            ->post('/broadcasting/auth', [
                'channel_name' => 'private-medalhao.sismos',
                'socket_id' => '1234.5678',
            ])
            ->assertOk();
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `BROADCAST_CONNECTION=reverb /c/tmp/tr.sh php vendor/bin/phpunit --filter=CanalMedalhaoTest`
Expected: FAIL — os dois testes de usuario autenticado dao 403, porque o canal
nao existe em `channels.php`.

- [ ] **Step 3: Declarar o canal**

Acrescentar ao fim de `SDC/routes/channels.php`:

```php
/*
 * Canal do pipeline medalhao, parametrizado pelo grupo (inmet, sismos, ...).
 * Fonte nova ganha tempo real sem precisar de canal novo.
 *
 * Privado, autorizado a qualquer usuario autenticado: o mesmo nivel que as rotas
 * /inmet e /sismos exigem, nem mais nem menos. O evento carrega apenas um
 * carimbo de tempo, mas dado operacional de Defesa Civil nao deve ser legivel
 * sem sessao.
 */
Broadcast::channel('medalhao.{grupo}', function ($user) {
    return $user !== null;
});
```

- [ ] **Step 4: Rodar e ver passar**

Run: `BROADCAST_CONNECTION=reverb /c/tmp/tr.sh php vendor/bin/phpunit --filter=CanalMedalhaoTest`
Expected: PASS, 3 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/routes/channels.php
git commit -m "✨ feat(tempo-real): canal privado medalhao.{grupo}"
```

---

### Task 3: O evento

**Files:**
- Create: `SDC/app/Modules/Medalhao/Events/GoldAtualizado.php`
- Test: `SDC/tests/Unit/Medalhao/GoldAtualizadoTest.php`

**Interfaces:**
- Produces: `GoldAtualizado::__construct(string $grupo)`, com `$grupo` publico readonly. `broadcastOn(): PrivateChannel`, `broadcastAs(): string` devolvendo `GoldAtualizado`, e `broadcastWith(): array{grupo: string, atualizado_em: string}`. Task 4 despacha; Task 7 escuta o nome.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Medalhao;

use App\Modules\Medalhao\Events\GoldAtualizado;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Tests\TestCase;

final class GoldAtualizadoTest extends TestCase
{
    public function test_e_broadcastavel(): void
    {
        $this->assertInstanceOf(ShouldBroadcast::class, new GoldAtualizado('inmet'));
    }

    public function test_transmite_no_canal_privado_do_grupo(): void
    {
        $canal = (new GoldAtualizado('inmet'))->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $canal);
        $this->assertSame('private-medalhao.inmet', $canal->name);
    }

    public function test_o_nome_do_evento_e_estavel(): void
    {
        // O front escuta por este nome; mudar quebra o cliente em silencio.
        $this->assertSame('GoldAtualizado', (new GoldAtualizado('sismos'))->broadcastAs());
    }

    public function test_o_payload_nao_leva_dado_de_dominio(): void
    {
        // O evento e um AVISO. Mandar leitura ou evento sismico criaria uma
        // segunda fonte de verdade e exporia dado no socket.
        $payload = (new GoldAtualizado('inmet'))->broadcastWith();

        $this->assertSame(['grupo', 'atualizado_em'], array_keys($payload));
        $this->assertSame('inmet', $payload['grupo']);
        $this->assertNotEmpty($payload['atualizado_em']);
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `/c/tmp/tr.sh php vendor/bin/phpunit --filter=GoldAtualizadoTest`
Expected: FAIL — `Class "App\Modules\Medalhao\Events\GoldAtualizado" not found`.

- [ ] **Step 3: Criar o evento**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Avisa que a camada Gold de um grupo foi refeita.
 *
 * E um AVISO, nao um transporte de dado: o payload leva so o grupo e o carimbo
 * de tempo. Mandar as leituras duplicaria a serializacao que o controller ja faz
 * e criaria duas fontes de verdade que divergem na primeira mudanca de matview.
 * O cliente reage pedindo ao Inertia para rebuscar as props.
 *
 * Vive no kernel Medalhao, e nao em cada dominio, porque e generico por
 * construcao: parametrizado pelo grupo, fonte nova nao precisa de classe nova.
 */
final class GoldAtualizado implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $grupo,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("medalhao.{$this->grupo}");
    }

    /** O front escuta por este nome; manter estavel. */
    public function broadcastAs(): string
    {
        return 'GoldAtualizado';
    }

    /** @return array{grupo: string, atualizado_em: string} */
    public function broadcastWith(): array
    {
        return [
            'grupo' => $this->grupo,
            'atualizado_em' => Carbon::now()->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `/c/tmp/tr.sh php vendor/bin/phpunit --filter=GoldAtualizadoTest`
Expected: PASS, 4 testes.

- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/Medalhao/Events/GoldAtualizado.php
git commit -m "✨ feat(medalhao): evento GoldAtualizado, aviso sem dado de dominio"
```

---

### Task 4: Emitir nos dois jobs de refresh

**Files:**
- Modify: `SDC/app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php`
- Modify: `SDC/app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php`
- Test: `SDC/tests/Feature/TempoReal/EmissaoGoldAtualizadoTest.php`

**Interfaces:**
- Consumes: `GoldAtualizado` (Task 3).
- Produces: os dois jobs emitem o evento apos o `REFRESH`.

- [ ] **Step 1: Escrever o teste que falha**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\TempoReal;

use App\Modules\Inmet\Jobs\AtualizarGoldInmetJob;
use App\Modules\Medalhao\Events\GoldAtualizado;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class EmissaoGoldAtualizadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('As matviews exigem PostgreSQL.');
        }
    }

    public function test_o_job_do_inmet_avisa_o_grupo_inmet(): void
    {
        Event::fake([GoldAtualizado::class]);

        (new AtualizarGoldInmetJob())->handle();

        Event::assertDispatched(
            GoldAtualizado::class,
            fn (GoldAtualizado $e) => $e->grupo === 'inmet'
        );
    }

    public function test_o_job_dos_sismos_avisa_o_grupo_sismos(): void
    {
        Event::fake([GoldAtualizado::class]);

        (new AtualizarGoldSismosJob())->handle();

        Event::assertDispatched(
            GoldAtualizado::class,
            fn (GoldAtualizado $e) => $e->grupo === 'sismos'
        );
    }
}
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `/c/tmp/tr.sh php vendor/bin/phpunit --filter=EmissaoGoldAtualizadoTest`
Expected: FAIL — nenhum evento despachado.

- [ ] **Step 3: Emitir no job do Inmet**

Em `AtualizarGoldInmetJob`, acrescentar o import
`use App\Modules\Medalhao\Events\GoldAtualizado;` e, ao fim de `handle()`, depois
dos dois `REFRESH`:

```php
        // Avisa depois do refresh, nao na ingestao: e o refresh que torna o dado
        // visivel. Avisar antes faria o cliente rebuscar o estado anterior.
        GoldAtualizado::dispatch('inmet');
```

- [ ] **Step 4: Emitir no job dos sismos**

Em `AtualizarGoldSismosJob`, acrescentar o mesmo import e, ao fim de `handle()`:

```php
        // Avisa depois do refresh, nao na ingestao: e o refresh que torna o dado
        // visivel. Avisar antes faria o cliente rebuscar o estado anterior.
        GoldAtualizado::dispatch('sismos');
```

- [ ] **Step 5: Rodar e ver passar, e conferir que a fase 1 e 3 seguem verdes**

```bash
/c/tmp/tr.sh php vendor/bin/phpunit --filter="EmissaoGoldAtualizado|Medalhao|Sismos|Inmet"
```

Expected: PASS. Os jobs de Gold sao usados por varios testes das fases
anteriores; se algum quebrar, o `Event::fake` de outro teste esta vazando.

- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Inmet/Jobs/AtualizarGoldInmetJob.php \
        SDC/app/Modules/Sismos/Jobs/AtualizarGoldSismosJob.php
git commit -m "✨ feat(medalhao): jobs de gold avisam quando o dado fica visivel"
```

---

### Task 5: Servico reverb em dev

**Files:**
- Modify: `SDC/docker/compose.dev.yml`
- Modify: `SDC/docker/.env` (gitignorado — nao commitar)
- Modify: `SDC/.env` (gitignorado — nao commitar)

**Interfaces:**
- Produces: `newsdc_dev_reverb` escutando em `8080`, publicado no host; `BROADCAST_CONNECTION=reverb` e `REVERB_*` no `app` e nos workers.

- [ ] **Step 1: Definir as variaveis**

Acrescentar a `SDC/docker/.env`:

```
# Reverb (WebSocket). Espelha o que stack.app.onpremise.yml usa em producao.
REVERB_APP_ID=sdc-dev
REVERB_APP_KEY=sdc-dev-key
REVERB_APP_SECRET=sdc-dev-secret
```

E a `SDC/.env`, porque o Vite le em build time:

```
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=sdc-dev-key
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Note a assimetria, que e proposital: **o servidor** alcanca o Reverb pelo
hostname de rede (`reverb`), **o navegador** pelo `localhost:8080` publicado. Sao
valores diferentes para a mesma coisa.

- [ ] **Step 2: Acrescentar as variaveis a ancora `queue-env`**

Em `SDC/docker/compose.dev.yml`, dentro de `environment: &queue-env` (perto do
`MEDALHAO_INMET_TOKEN`):

```yaml
      # Broadcast. Sem ":?" de proposito: faltar a credencial do Reverb nao deve
      # impedir a fila de subir -- o resto do pipeline nao depende dela.
      BROADCAST_CONNECTION: ${BROADCAST_CONNECTION:-null}
      REVERB_APP_ID: ${REVERB_APP_ID:-}
      REVERB_APP_KEY: ${REVERB_APP_KEY:-}
      REVERB_APP_SECRET: ${REVERB_APP_SECRET:-}
      # O servidor fala com o Reverb pela rede interna; o navegador, por
      # localhost. Sao valores diferentes de propostio.
      REVERB_HOST: reverb
      REVERB_PORT: "8080"
      REVERB_SCHEME: http
```

Repetir o mesmo bloco no `environment:` do servico `app` (ele tambem publica).

- [ ] **Step 3: Criar o servico**

Acrescentar apos o servico `scheduler`:

```yaml
  # Servidor WebSocket. Espelha o servico reverb de
  # docker/jenkins/stack.app.onpremise.yml, que existe em producao desde antes
  # deste trabalho e nunca transmitiu nada.
  reverb:
    image: newsdc-swoole-dev:latest
    container_name: newsdc_dev_reverb
    hostname: reverb
    restart: unless-stopped

    mem_limit: 512m

    # Publicado para o NAVEGADOR alcancar. Os outros servicos falam com ele pelo
    # hostname interno e nao precisariam da porta exposta.
    ports:
      - "8080:8080"

    extra_hosts:
      - "host.docker.internal:host-gateway"

    environment:
      <<: *queue-env
      APP_NAME: "SDC Dev Reverb"
      # Dentro do proprio container, escuta em todas as interfaces.
      REVERB_HOST: 0.0.0.0

    volumes: *queue-volumes

    depends_on: *queue-depends

    command:
      - sh
      - -c
      - >
        umask 002 &&
        composer dump-autoload --optimize --no-interaction &&
        exec php artisan reverb:start --host=0.0.0.0 --port=8080

    healthcheck:
      test: ["CMD-SHELL", "pgrep -f 'reverb:start' >/dev/null || exit 1"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 30s
```

- [ ] **Step 4: Validar o compose e subir**

```bash
cd SDC && docker compose -f docker/compose.dev.yml config --quiet && echo "YAML ok"
docker compose -f docker/compose.dev.yml up -d --no-deps reverb queue app
```

Expected: `config --quiet` sai com 0; os tres containers sobem.

- [ ] **Step 5: Verificar que o Reverb esta escutando**

```bash
docker exec newsdc_dev_reverb ps aux | grep -c "reverb:start"
curl -s -o /dev/null -w "reverb http=%{http_code}\n" --max-time 10 http://localhost:8080/app/sdc-dev-key
```

Expected: o processo existe; o curl responde algo diferente de `000` (o Reverb
responde a handshake, entao qualquer status HTTP prova que a porta atende).

- [ ] **Step 6: Verificar que a aplicacao continua de pe**

```bash
curl -s -o /dev/null -w "app http=%{http_code}\n" --max-time 20 http://localhost:8000/inmet
```

Expected: `302`. Este e o cenario que o comentario do `config/app.php` temia:
provider registrado com `BROADCAST_CONNECTION=reverb` de verdade.

- [ ] **Step 7: Commit**

```bash
git add SDC/docker/compose.dev.yml
git commit -m "🐳 docker(tempo-real): servico reverb em dev, espelhando producao"
```

Os dois `.env` sao gitignorados e **nao** entram no commit.

---

### Task 6: Ligar o Echo no frontend

**Files:**
- Create: `SDC/resources/js/echo.js`
- Modify: `SDC/resources/js/app.js:1-10`

**Interfaces:**
- Produces: `window.Echo` quando as `VITE_REVERB_*` existirem; `undefined` quando nao. Task 7 depende dessa ausencia ser segura.

- [ ] **Step 1: Criar o bootstrap do Echo**

`SDC/resources/js/echo.js`:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
 * Inicializa o Echo SOMENTE se houver configuracao.
 *
 * Sem a chave, window.Echo fica undefined e o useAtualizacaoAoVivo nao faz nada.
 * Isso e o que transforma o tempo real num feature flag: com
 * BROADCAST_CONNECTION=null, que foi o padrao deste projeto por meses, as
 * paginas funcionam exatamente como funcionavam.
 *
 * As VITE_* sao lidas em BUILD TIME: mudar host ou porta exige rebuild do
 * frontend, nao so restart de container.
 */
const chave = import.meta.env.VITE_REVERB_APP_KEY;

if (chave) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: chave,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
```

- [ ] **Step 2: Importar no app.js**

Em `SDC/resources/js/app.js`, logo apos `import { initAxios } from './bootstrap';`:

```js
import './echo';
```

- [ ] **Step 3: Conferir que o build passa e que o Echo aparece**

```bash
cd SDC && npx vite build 2>&1 | tail -3
grep -c "laravel-echo" public/build/manifest.json || true
```

Expected: build sem erro. Rode no host (node 24 esta instalado); o
`node_modules` do host e nativo do Windows e o alpine falha com
`Cannot find module @rollup/rollup-linux-x64-musl`.

- [ ] **Step 4: Commit**

```bash
git add SDC/resources/js/echo.js SDC/resources/js/app.js
git commit -m "✨ feat(tempo-real): inicializa o Echo apenas quando configurado"
```

---

### Task 7: O composable

**Files:**
- Create: `SDC/resources/js/Composables/useAtualizacaoAoVivo.js`

**Interfaces:**
- Consumes: `window.Echo` (Task 6), nome de evento `GoldAtualizado` (Task 3), canal `medalhao.{grupo}` (Task 2).
- Produces: `useAtualizacaoAoVivo({ canal, evento, props })`. Task 8 consome.

- [ ] **Step 1: Criar o composable**

```js
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Recarrega props da pagina quando o servidor avisa que o dado mudou.
 *
 * O evento e so um aviso: quem rebusca e o Inertia, pelo controller, que segue
 * sendo a unica fonte das props.
 *
 * @param {object}   opcoes
 * @param {string}   opcoes.canal   Canal privado, sem o prefixo "private-".
 * @param {string}   opcoes.evento  Nome do evento. Com ponto na frente para usar
 *                                  o broadcastAs em vez do FQCN da classe.
 * @param {string[]} opcoes.props   Props a rebuscar, no formato do Inertia.
 */
export function useAtualizacaoAoVivo({ canal, evento, props }) {
    let assinatura = null;
    let pendente = false;
    let visibilidadeHandler = null;

    const recarregar = () => {
        // preserveState mantem a pagina da tabela e o estado local; sem
        // preserveScroll a atualizacao daria um salto e seria pior que o F5.
        router.reload({
            only: props,
            preserveScroll: true,
            preserveState: true,
        });
    };

    const aoReceber = () => {
        // Aba em segundo plano nao rebusca: ninguem esta olhando e cada
        // recarregamento custa um ciclo de request. Marca pendencia e resolve
        // quando a aba volta. Mesma disciplina do polling do sino.
        if (document.hidden) {
            pendente = true;

            return;
        }

        recarregar();
    };

    onMounted(() => {
        // Sem Echo nao ha tempo real, e isso NAO e erro: com
        // BROADCAST_CONNECTION=null a pagina funciona como antes.
        if (!window.Echo) {
            return;
        }

        assinatura = window.Echo.private(canal);
        assinatura.listen(evento, aoReceber);

        visibilidadeHandler = () => {
            if (!document.hidden && pendente) {
                pendente = false;
                recarregar();
            }
        };

        document.addEventListener('visibilitychange', visibilidadeHandler);
    });

    onBeforeUnmount(() => {
        if (visibilidadeHandler) {
            document.removeEventListener('visibilitychange', visibilidadeHandler);
            visibilidadeHandler = null;
        }

        if (assinatura) {
            assinatura.stopListening(evento);
            window.Echo.leave(`private-${canal}`);
            assinatura = null;
        }
    });
}
```

- [ ] **Step 2: Conferir que o build passa**

Run: `cd SDC && npx vite build 2>&1 | tail -3`
Expected: sem erro.

- [ ] **Step 3: Commit**

```bash
git add SDC/resources/js/Composables/useAtualizacaoAoVivo.js
git commit -m "✨ feat(tempo-real): composable de recarregamento parcial ao vivo"
```

---

### Task 8: Fiar nas duas paginas

**Files:**
- Modify: `SDC/resources/js/Pages/Inmet/MapaInmet.vue`
- Modify: `SDC/resources/js/Pages/Sismos/MapaSismos.vue`

**Interfaces:**
- Consumes: `useAtualizacaoAoVivo` (Task 7).

- [ ] **Step 1: Ligar a pagina do Inmet**

Em `MapaInmet.vue`, acrescentar ao import de composables:

```js
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';
```

E, apos o `defineProps`:

```js
// O pipeline coleta de hora em hora; sem isto a tela mostra a coleta anterior
// ate alguem apertar F5.
useAtualizacaoAoVivo({
    canal: 'medalhao.inmet',
    evento: '.GoldAtualizado',
    props: ['estacoes', 'estatisticas'],
});
```

- [ ] **Step 2: Ligar a pagina de Sismos**

Em `MapaSismos.vue`, o mesmo import e, apos o `defineProps`:

```js
useAtualizacaoAoVivo({
    canal: 'medalhao.sismos',
    evento: '.GoldAtualizado',
    props: ['eventos', 'estatisticas'],
});
```

- [ ] **Step 3: Build e testes de controller**

```bash
cd SDC && npx vite build 2>&1 | tail -3
cd .. && /c/tmp/tr.sh php vendor/bin/phpunit --filter="Inmet|Sismos"
```

Expected: build sem erro; testes verdes. Eles provam que as props que o
composable pede continuam existindo com esses nomes — se alguem renomear
`estacoes`, o `only:` passaria a pedir prop inexistente.

- [ ] **Step 4: Commit**

```bash
git add SDC/resources/js/Pages/Inmet/MapaInmet.vue SDC/resources/js/Pages/Sismos/MapaSismos.vue
git commit -m "✨ feat(tempo-real): Meteorologia e Sismos atualizam sem F5"
```

---

### Task 9: Verificacao ponta a ponta

**Files:**
- Nenhum arquivo novo; validacao dos criterios da secao 6 do spec.

- [ ] **Step 1: Degradacao com broadcasting desligado**

```bash
docker compose -f SDC/docker/compose.dev.yml stop reverb
```

Abra `/inmet` e `/sismos` no navegador. Expected: as duas paginas carregam
normalmente e o console **nao** mostra erro nao tratado. Este e o criterio que
prova o feature flag.

```bash
docker compose -f SDC/docker/compose.dev.yml start reverb
```

- [ ] **Step 2: Atualizacao ao vivo, sem F5**

Deixe `/inmet` aberto e, em outro terminal:

```bash
docker exec newsdc_dev_scheduler php artisan medalhao:ingerir inmet
```

Expected: em poucos segundos a coluna DATA/HORA e as estatisticas mudam **sem
recarregar**.

- [ ] **Step 3: Scroll e pagina da tabela sobrevivem**

Repita o Step 2 com a tabela na pagina 3 e a janela rolada.
Expected: continua na pagina 3, na mesma posicao de scroll.

- [ ] **Step 4: Aba em segundo plano**

Deixe `/inmet` aberto, mude de aba, dispare a coleta, aguarde, volte.
Expected: nada e rebuscado enquanto oculta; ao voltar, atualiza uma vez.

- [ ] **Step 5: Visitante nao assina o canal**

```bash
curl -s -o /dev/null -w "auth sem sessao http=%{http_code}\n" -X POST \
  --max-time 10 -d 'channel_name=private-medalhao.inmet&socket_id=1234.5678' \
  http://localhost:8000/broadcasting/auth
```

Expected: `403` ou `302` para login — nunca `200`.

- [ ] **Step 6: O payload nao leva dado de dominio**

No DevTools, aba Network, filtro WS, inspecione o frame do evento.
Expected: apenas `grupo` e `atualizado_em`. Nenhuma leitura de estacao.

- [ ] **Step 7: Coleta sem novidade nao gera evento**

Dispare `medalhao:ingerir inmet` duas vezes seguidas.
Expected: na segunda o log mostra `conteudo identico ao anterior, ignorado`, o
job de Gold **nao** roda, e a tela nao pisca.

- [ ] **Step 8: Suite do escopo**

Run: `/c/tmp/tr.sh php vendor/bin/phpunit --filter="TempoReal|Medalhao|Inmet|Sismos"`
Expected: verde.

- [ ] **Step 9: Conferencia contra os criterios do spec**

Percorra os oito criterios da secao 6 de
`SDC/docs/superpowers/specs/2026-09-02-tempo-real-medalhao-design.md` e marque
cada um. Qualquer um que nao passe vira correcao antes de considerar concluido.

- [ ] **Step 10: Commit final**

```bash
git add SDC/docs/superpowers/plans/2026-09-02-tempo-real-medalhao.md
git commit -m "✅ test(tempo-real): verificacao ponta a ponta"
```

---

## Notas de execucao

**Branch.** Este plano vive em `feat/tempo-real-medalhao`, criada a partir de
`dev` em `fd92f6bc`.

**Ordem.** A Task 1 e pre-requisito de tudo e e a unica que pode derrubar a
aplicacao. As Tasks 2 e 3 sao independentes entre si. A 4 depende da 3; a 5
depende da 1; a 6 depende da 5 (precisa das `VITE_*`); a 7 depende da 6; a 8
depende da 7; a 9 depende de todas.

**O que NAO commitar:** `SDC/.env`, `SDC/docker/.env` e qualquer arquivo sob
`SDC/tests/`.

**Pendencia herdada, fora do escopo deste plano:** a fiacao do
`MEDALHAO_INMET_TOKEN` no `compose.dev.yml` esta **nao commitada na worktree
principal**. Sem ela a coleta agendada do Inmet falha com 4xx, o que atrapalha a
verificacao da Task 9. Confirme com o autor antes de comecar.

**Fora de escopo, por decisao registrada no spec:** as outras 28 listagens, tempo
real para acao de outro usuario, refletir a propria acao sem F5, e presenca.
