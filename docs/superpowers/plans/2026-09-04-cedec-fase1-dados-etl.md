# Modulo Cedec — Fase 1: Dados e ETL — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Destravar a conexao com o banco legado `gestaocedec_local`, consolidar as sete
colunas de contato institucional em `compdec_prefeituras`, e corrigir
`PrefeituraService::migrarLegado()` para ler `cedec_municipio` + `cedec_prefeitura` do
legado (em vez do `com_comdec` inexistente), com um command novo
`cedec:importar-prefeituras` para rodar a carga.

**Architecture:** Sete colunas novas entram na migration de criacao existente de
`compdec_prefeituras` (idempotente para bases ja migradas). `Prefeitura::$fillable` e
`PrefeituraDTO` absorvem as sete colunas. `PrefeituraService::migrarLegado()` passa a
consultar `cedec_municipio LEFT JOIN cedec_prefeitura` na conexao `legacy` (MySQL,
`gestaocedec_local`), resolver `municipio_id` via `Codmundv = municipios.codigo_ibge`
(consulta separada, porque `legacy` e `pgsql` sao conexoes/drivers diferentes — nao da
para fazer JOIN cruzado), higienizar os campos sujos e gravar em `compdec_prefeituras`.
Um command novo em `App\Modules\Cedec\Console` expoe a chamada, registrado por uma
`CedecServiceProvider` minima (o modulo `Cedec` ainda nao existe; esta fase cria so o
esqueleto de Console).

**Tech Stack:** Laravel 12 / PHP 8.3, Eloquent, PHPUnit, MySQL (conexao legada),
PostgreSQL (conexao padrao), Spatie Media Library.

**Spec:** `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md`
(secoes 1 a 3 e 7). Contrato de interfaces (fonte unica de nomes/tipos, autoritativo
sobre esta fase nas secoes 0, 1, 2, 3, 9 e 10):
`docs/superpowers/plans/2026-09-04-cedec-contrato-interfaces.md`.

## Global Constraints

### Ambiente de execucao — corrigido em 2026-09-05, medido

Estas quatro correcoes valem para TODOS os steps deste plano e substituem qualquer
comando divergente no corpo dele.

1. **O container e `newsdc_dev_app`**, imagem `newsdc-swoole-dev` (Swoole, nao FrankenPHP),
   com a aplicacao em `/var/www`. O nome `newsdc_frankenphp_local` do `.claude/kernel.py`
   NAO EXISTE. O Postgres de desenvolvimento e `newsdc_dev_db`, publicado no host em 5434.

2. **Teste roda no HOST, nunca no container.** `docker exec newsdc_dev_app php artisan test`
   falha com `Command "test" is not defined` — a imagem nao tem dev dependencies. Exporte
   uma vez por terminal, a partir de `SDC/`:

   ```bash
   export APP_CONFIG_CACHE=/nao/existe/config.php
   export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc
   export DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)"
   ```

   `APP_CONFIG_CACHE` para caminho inexistente e obrigatorio: sem ele o PHPUnit do host
   escreve no `bootstrap/cache` compartilhado com o container e derruba o Octane, que so
   volta com restart de ~3min. Os `DB_*` sao obrigatorios porque o `.env` aponta
   `DB_HOST=newsdc_db` (nome de rede Docker que o host nao resolve) e porque o
   `.env.testing` forca sqlite `:memory:` — e como `tests/TestCase.php` e vazio e nao roda
   migration, cair no sqlite da `no such table` na suite inteira.

3. **Os testes rodam contra o banco de DESENVOLVIMENTO.** Nenhum teste pode fazer `update()`
   ou `delete()` sem `where` restrito as linhas que ele mesmo criou, e assercao de contagem
   tem de ser relativa a um "antes", nunca total absoluto.

4. **`SDC/tests` esta no `.gitignore`.** Escrever o teste continua obrigatorio, mas ele NAO
   e versionado: todo `git add` dos steps leva so os arquivos de producao. Incluir caminho
   sob `SDC/tests` faz o `git add` ser recusado.

- App executavel em `NewSDC/SDC`. Testes: **PHPUnit**, nao Pest. Classe com
  `declare(strict_types=1)`, namespace `Tests\Feature\Cedec`, trait
  `DatabaseTransactions`.
- `SDC/phpunit.xml` NAO isola banco de teste (linhas de sqlite comentadas): os testes
  desta fase rodam contra o banco de DESENVOLVIMENTO em Postgres, que ja tem 853
  prefeituras reais. Nenhuma asserção pode assumir banco vazio ou contagem absoluta da
  tabela inteira — sempre contar por `municipio_id`/`legacy_id` especifico do teste, ou
  medir delta antes/depois dentro da mesma transacao.
- Migration: **consolidar na migration principal existente**, nao criar `add_*`
  (regra de ouro 9). Como `compdec_prefeituras` **ja existe** nos bancos de
  desenvolvimento/producao, a migration precisa ser idempotente tanto para instalacao
  nova (via `Schema::create`) quanto para banco ja migrado (via `Schema::table` +
  `hasColumn`) — ver Task 2.
- Migration: **consolidar na migration principal existente**, nao criar `add_*`
  (regra de ouro 9).
- Sem emoji dentro do codigo. Emoji so na mensagem de commit (gitmoji).
- Commits: `<emoji> tipo(cedec): descricao` em pt-BR. **Sem trailer de co-autor.**
- Nenhum arquivo de teste criado so para depuracao entra no commit.
- **Commits agrupados, nao um por task** (regra de ouro 12: nao fragmentar uma mesma
  feature em varios commits de um arquivo cada). Este plano tem 7 tasks mas fecha em
  **3 commits**: (1) ambiente — Task 1; (2) schema+model+DTO+factory — Tasks 2 a 5,
  todas tocando a mesma mudanca de "compdec_prefeituras ganha contato institucional";
  (3) ETL+command — Tasks 6 e 7, a mudanca "migracao do legado passa a funcionar". Os
  passos de commit ficam marcados apenas no fim de Task 1, Task 5 e Task 7; Tasks 2, 3,
  4 e 6 nao commitam sozinhas.
- **Nome do container medido nesta sessao diverge do resto do projeto.** O contrato e
  os planos das fases 3-5 usam `docker exec newsdc_dev_app`. Neste ambiente
  (`docker ps`, medido ao escrever este plano) o container realmente em execucao e
  `newsdc_dev_app` (imagem `newsdc-swoole-dev:latest`, servico `app` de
  `docker/compose.dev.yml`). Os comandos abaixo usam `newsdc_dev_app` para
  seguir o contrato; se o container ativo no seu ambiente tiver outro nome, confirme
  com `docker ps` e substitua.
- Depois de mudar PHP: `octane:reload` (~1s). Mudar `.env`, `config/` ou
  `docker/compose.dev.yml` (variaveis de ambiente do container) exige RESTART do
  container (~3min, custo de `chmod -R` sobre os arquivos montados) — nao basta
  `octane:reload`.

---

### Task 1: Ambiente — nova conexao dedicada para o legado gestaocedec

**Correcao de rota pos-entrega:** a primeira versao desta task mandava trocar
`DB_LEGACY_DATABASE` de `dbsdc` para `gestaocedec_local`. Isso estava ERRADO e foi
revertido. Medicao adicional (depois da primeira entrega) mostrou que `dbsdc` e
`gestaocedec_local` sao **dois sistemas legados diferentes**, com tabelas de mesmo
nome e schema incompativel:

```
dbsdc.cedec_municipio        -> erro 1054 ao selecionar email/prefeito (colunas nao existem)
gestaocedec_local.cedec_municipio -> 854 linhas, 716 e-mails, 854 prefeitos
gestaocedec_local.pip_ponto_cap   -> existe SO aqui, nao existe em dbsdc
```

E a conexao `legacy` (a que aponta para `dbsdc`) e **compartilhada por sete
consumidores** fora do escopo desta fase, todos lendo
`config('<modulo>.legacy_connection', 'legacy')`:

- `App\Modules\Compdec\Services\AnexoService:128`
- `App\Modules\Compdec\Services\EquipeService:118`
- `App\Modules\Compdec\Services\OrgaoService:359`
- `App\Modules\Compdec\Services\PlanoContingenciaService:160`
- `App\Modules\Compdec\Services\PrefeituraService:98` (a que esta fase corrige)
- `App\Modules\Pmda\Services\ComunidadeLegadoService:258`
- `App\Modules\AjudaHumanitaria\Console\ExtrairLegadoAjuCommand:35` e
  `App\Modules\AjudaHumanitaria\Repositories\LegadoSaldoMaterialRepository:87`
- `App\Console\Commands\ImportPontosCaptacaoCommand` e
  `App\Console\Commands\MigrarCompdecLegadoCommand:120`

Trocar o banco por baixo da conexao `legacy` quebraria os outros seis consumidores
(leem tabelas de `dbsdc` que nao existem, ou existem com schema diferente, em
`gestaocedec_local`). **`DB_LEGACY_DATABASE: dbsdc` fica INTOCADO** neste plano.

O caminho correto e o que o proprio `config/database.php` ja pratica: a conexao
`legado_cisterna_mysql` (linha 88) e dedicada, somente leitura, so consumida por
`cisterna:extrair-legado`, com o comentario "Nao ha migration nem model apontando para
ela". O ETL de prefeituras do Cedec ganha uma conexao no mesmo molde:
`legado_gestaocedec`, exclusiva para `cedec_municipio`/`cedec_prefeitura`, e uma chave
de config do modulo Cedec (`config('cedec.legacy_connection')`) para o ETL ler dela em
vez de `config('compdec.legacy_connection')` — assim nenhum consumidor existente muda
de conexao.

Conectividade ja confirmada ao vivo nesta sessao (fica valendo, so o nome da conexao
mudou):

```
$ docker exec newsdc_dev_app php -r "var_dump(gethostbyname('host.docker.internal'));"
string(14) "192.168.65.254"

$ docker exec newsdc_dev_app php -r "... new PDO('mysql:host=host.docker.internal;port=3306;dbname=gestaocedec_local;...', 'root', '') ..."
OK conectou em gestaocedec_local
cedec_municipio: 854 linhas
cedec_prefeitura: 854 linhas
```

`host.docker.internal` ja resolve de dentro do container (`extra_hosts:
host.docker.internal:host-gateway` ja configurado em `docker/compose.dev.yml`) e o
MySQL do Laragon ja e alcancavel nesse host — so falta a conexao dedicada apontando
para la, em paralelo a `legacy` (que continua servindo `dbsdc` para os outros seis
consumidores).

A foto do prefeito (`cedec_prefeitura.fotoPref`) e outro capitulo, independente da
questao de conexao de banco: `config('compdec.legacy_paths.foto_prefeito')` cai no
default `/legacy/storage/app/public/prefeitura_fotos`, que nao existe no container
(`COMPDEC_LEGACY_FOTO_PREFEITO` nunca foi setada). O padrao ja usado no projeto para
plano de contingencia (mesmo legado, `mod_cedec/classe/Classe.Anexo::uploadSimple()`)
e um bind mount read-only apontando para `/legacy/gestaocedec/anexo/<recurso>` com
origem em `storage/app/public/legado_<recurso>` — a Task 6 usa esse mesmo caminho para
as fotos do prefeito, mas a pasta so existira de fato quando alguem copiar os 423
arquivos de `PATH.'/anexo/prefeito'` do servidor legado real para
`SDC/storage/app/public/legado_prefeitura_fotos` (esse deploy de arquivos NAO faz
parte desta fase — o ETL trata arquivo ausente como caso normal, ver Task 6). Esta
parte NAO usa a conexao de banco `compdec.legacy_paths.*` continua sendo lida de
`config/compdec.php`, sem relacao com a colisao `dbsdc`/`gestaocedec_local`.

**Pendencia registrada, NAO corrigida nesta fase:** `SDC/.env.example` linha 139 ja diz
`DB_LEGACY_DATABASE=gestaocedec_local`, enquanto `docker/compose.dev.yml` (que vence,
porque define a variavel direto no bloco `environment:` do container) usa `dbsdc`. A
documentacao e o runtime discordam entre si — quem ler so o `.env.example` vai assumir
que a conexao `legacy` aponta para o banco certo para qualquer ETL, inclusive os sete
consumidores acima, e vai levar o mesmo susto que gerou a correcao desta task. Alinhar
os dois (decidir qual dos dois esta "certo" e corrigir o outro) e uma limpeza que
pertence a quem for revisar a conexao `legacy` como um todo — fora do escopo desta
fase, que so precisa da conexao nova `legado_gestaocedec`.

**Files:**
- Modify: `SDC/.env` (nao versionado; nao entra no commit)
- Modify: `SDC/.env.example`
- Modify: `SDC/docker/compose.dev.yml`
- Modify: `SDC/config/database.php` (nova conexao `legado_gestaocedec`)
- Create: `SDC/config/cedec.php`

**Interfaces:**
- Consumes: `config/database.php` (padrao a seguir: bloco `legado_cisterna_mysql`,
  linhas 86-103, existente, sem alteracao — so consultado como modelo).
- Produces:
  - Conexao `database.connections.legado_gestaocedec` (driver mysql, mesmo shape de
    `legado_cisterna_mysql`), lendo `DB_LEGADO_GESTAOCEDEC_HOST`,
    `DB_LEGADO_GESTAOCEDEC_PORT`, `DB_LEGADO_GESTAOCEDEC_DATABASE`,
    `DB_LEGADO_GESTAOCEDEC_USERNAME`, `DB_LEGADO_GESTAOCEDEC_PASSWORD`.
  - `config('cedec.legacy_connection')` (default `'legado_gestaocedec'`, override via
    `CEDEC_LEGACY_CONNECTION`) — consumido por Task 6 (`PrefeituraService::
    migrarLegado()` passa a ler DESTA chave, nao mais de
    `compdec.legacy_connection`).
  - `COMPDEC_LEGACY_FOTO_PREFEITO=/legacy/gestaocedec/anexo/prefeito` (sem mudanca de
    nome — continua em `config/compdec.php`, nao migra para `config/cedec.php`, porque
    e caminho de arquivo, nao conexao de banco, e nao tem o problema de
    compartilhamento entre modulos que motivou esta correcao).
  - `DB_LEGACY_*` e `DB_LEGACY_DATABASE=dbsdc` em `docker/compose.dev.yml`
    **permanecem intocados**.

- [ ] **Step 1: Confirmar o estado atual (diagnostico, sem alterar nada)**

```bash
docker exec newsdc_dev_app env | grep -E "DB_LEGACY|DB_LEGADO_GESTAOCEDEC|COMPDEC_LEGACY|CEDEC_LEGACY"
```

Esperado (estado antes do fix): `DB_LEGACY_DATABASE=dbsdc` (mantido, correto para os
outros seis consumidores) e nenhuma linha de `DB_LEGADO_GESTAOCEDEC_*`,
`COMPDEC_LEGACY_FOTO_PREFEITO` ou `CEDEC_LEGACY_CONNECTION`.

- [ ] **Step 2: Adicionar a conexao `legado_gestaocedec` em `config/database.php`**

Em `SDC/config/database.php`, no array `connections`, logo apos o bloco
`legado_cisterna_mysql` (linhas 86-103) e antes do bloco `carga`, adicionar:

```php
        // Somente leitura, consumida pelo ETL de prefeituras do Cedec
        // (cedec:importar-prefeituras / PrefeituraService::migrarLegado). Dedicada
        // e SEPARADA da conexao 'legacy' acima -- aquela aponta para dbsdc (legado
        // sdc/Laravel); esta aponta para gestaocedec_local (legado gestaocedec, PHP
        // puro). Os dois bancos tem tabelas de MESMO NOME (cedec_municipio,
        // cedec_prefeitura) com colunas incompativeis -- NAO reutilizar 'legacy'
        // aqui. Nao ha migration nem model apontando para ela.
        'legado_gestaocedec' => [
            'driver' => 'mysql',
            'host' => env('DB_LEGADO_GESTAOCEDEC_HOST', '127.0.0.1'),
            'port' => env('DB_LEGADO_GESTAOCEDEC_PORT', '3306'),
            'database' => env('DB_LEGADO_GESTAOCEDEC_DATABASE', 'gestaocedec_local'),
            'username' => env('DB_LEGADO_GESTAOCEDEC_USERNAME', 'root'),
            'password' => env('DB_LEGADO_GESTAOCEDEC_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
```

- [ ] **Step 3: Criar `config/cedec.php`**

Criar `SDC/config/cedec.php`:

```php
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Conexao do Banco Legado gestaocedec (ETL de prefeituras)
    |--------------------------------------------------------------------------
    | Nome da connection (em config/database.php) usada por
    | PrefeituraService::migrarLegado() para ler cedec_municipio/cedec_prefeitura.
    | Dedicada -- NAO reutiliza compdec.legacy_connection ('legacy'), que aponta
    | para dbsdc (legado sdc/Laravel): mesmo nome de tabela, schema incompativel.
    | Ver docs/superpowers/plans/2026-09-04-cedec-fase1-dados-etl.md Task 1.
    */
    'legacy_connection' => env('CEDEC_LEGACY_CONNECTION', 'legado_gestaocedec'),
];
```

- [ ] **Step 4: Adicionar as chaves em `SDC/.env`**

Abrir `SDC/.env` e adicionar (nao mexer nas linhas `DB_LEGACY_*` existentes, que
continuam apontando para `dbsdc`):

```
DB_LEGADO_GESTAOCEDEC_HOST=host.docker.internal
DB_LEGADO_GESTAOCEDEC_PORT=3306
DB_LEGADO_GESTAOCEDEC_DATABASE=gestaocedec_local
DB_LEGADO_GESTAOCEDEC_USERNAME=root
DB_LEGADO_GESTAOCEDEC_PASSWORD=
COMPDEC_LEGACY_FOTO_PREFEITO=/legacy/gestaocedec/anexo/prefeito
```

- [ ] **Step 5: Adicionar as mesmas chaves em `SDC/.env.example`**

Adicionar logo apos o bloco `DB_LEGACY_*` (linhas 137-141, que ficam como estao):

```
# Conexao DEDICADA e somente-leitura para o legado gestaocedec (PHP puro), usada
# so pelo ETL de prefeituras do Cedec (cedec:importar-prefeituras). NAO e a mesma
# base que DB_LEGACY_* acima -- aquela e o legado sdc/Laravel (dbsdc). Os dois tem
# tabelas de mesmo nome (cedec_municipio, cedec_prefeitura) com schema incompativel.
DB_LEGADO_GESTAOCEDEC_HOST=127.0.0.1
DB_LEGADO_GESTAOCEDEC_PORT=3306
DB_LEGADO_GESTAOCEDEC_DATABASE=gestaocedec_local
DB_LEGADO_GESTAOCEDEC_USERNAME=
DB_LEGADO_GESTAOCEDEC_PASSWORD=

# Caminho, dentro do container, dos 423 arquivos de foto do prefeito do legado
# gestaocedec (mod_cedec/classe/Classe.AnexoPref.php grava em PATH.'/anexo/prefeito',
# nome no padrao <id_municipio>_<slug>.<ext>). Ver docker/compose.dev.yml para o
# bind mount que preenche este caminho a partir de storage/app/public/legado_prefeitura_fotos.
COMPDEC_LEGACY_FOTO_PREFEITO=/legacy/gestaocedec/anexo/prefeito
```

**Nao mexer** nas linhas 137-141 (`DB_LEGACY_*`) mesmo elas dizendo
`DB_LEGACY_DATABASE=gestaocedec_local` — ver a pendencia registrada acima sobre a
discordancia entre `.env.example` e `docker/compose.dev.yml`.

- [ ] **Step 6: Adicionar as variaveis em `docker/compose.dev.yml` — SEM tocar em `DB_LEGACY_*`**

Em `SDC/docker/compose.dev.yml`, no bloco `environment:` do servico `app`, logo apos
o bloco `DB_LEGACY_*` existente (linhas 194-201, que ficam **exatamente como estao,
incluindo `DB_LEGACY_DATABASE: dbsdc`**), adicionar:

```yaml
      # Conexao DEDICADA e somente-leitura para o legado gestaocedec (PHP puro),
      # fonte de cedec_municipio/cedec_prefeitura, usada so por
      # cedec:importar-prefeituras. NAO reutiliza DB_LEGACY_* acima -- aquela
      # aponta para dbsdc (legado sdc/Laravel), schema incompativel apesar do
      # nome de tabela igual. Ver config/cedec.php (legacy_connection) e
      # config/database.php (legado_gestaocedec).
      DB_LEGADO_GESTAOCEDEC_HOST: host.docker.internal
      DB_LEGADO_GESTAOCEDEC_PORT: 3306
      DB_LEGADO_GESTAOCEDEC_DATABASE: gestaocedec_local
      DB_LEGADO_GESTAOCEDEC_USERNAME: root
      DB_LEGADO_GESTAOCEDEC_PASSWORD: ""

      # Caminho, dentro do container, dos 423 arquivos de foto do prefeito
      # (cedec_prefeitura.fotoPref). Ver bind mount no bloco volumes: abaixo.
      COMPDEC_LEGACY_FOTO_PREFEITO: /legacy/gestaocedec/anexo/prefeito
```

**Confirmar antes de seguir:** `git diff` neste arquivo NAO deve tocar nenhuma das
cinco linhas `DB_LEGACY_*` originais — a mudanca e so aditiva.

- [ ] **Step 7: Adicionar o bind mount das fotos em `docker/compose.dev.yml`**

No mesmo servico `app`, bloco `volumes:`, logo apos a linha do `LEGADO_PLANOS_HOST_PATH`
(por volta da linha 292), adicionar:

```yaml
      # TEMPORARIO -- fotos de prefeito do legado gestaocedec.
      # cedec_prefeitura.fotoPref guarda so o nome do arquivo (padrao
      # <id_municipio>_<slug>.<ext>, alguns com extensao dupla); o arquivo em si
      # mora em DOCUMENT_ROOT/anexo/prefeito no servidor legado (423 arquivos).
      # Ver App\Modules\Compdec\Services\PrefeituraService::migrarFotoPrefeito().
      - "${LEGADO_PREFEITURA_FOTOS_HOST_PATH:-../storage/app/public/legado_prefeitura_fotos}:/legacy/gestaocedec/anexo/prefeito:ro"
```

Nesta maquina de desenvolvimento a pasta de origem (`storage/app/public/
legado_prefeitura_fotos`) nao existe ainda — ninguem copiou os 423 arquivos do
servidor legado real para ca. O Docker cria o diretorio do host vazio automaticamente
ao montar; o ETL (Task 6) trata "arquivo nao encontrado" como caso normal, sem falhar
a linha. Migrar as fotos de verdade e tarefa operacional (copiar do servidor legado),
fora do escopo de codigo desta fase.

- [ ] **Step 8: Recriar o container e verificar**

```bash
docker compose -f SDC/docker/compose.dev.yml up -d --force-recreate app
docker exec newsdc_dev_app env | grep -E "DB_LEGACY|DB_LEGADO_GESTAOCEDEC|COMPDEC_LEGACY"
```

Esperado: `DB_LEGACY_DATABASE=dbsdc` **inalterado**, mais as cinco linhas novas
`DB_LEGADO_GESTAOCEDEC_*` (com `DATABASE=gestaocedec_local`) e a linha
`COMPDEC_LEGACY_FOTO_PREFEITO=/legacy/gestaocedec/anexo/prefeito`.

- [ ] **Step 9: Verificar conectividade real com o legado, pela conexao nova**

```bash
docker exec newsdc_dev_app php artisan config:clear
docker exec newsdc_dev_app php -r "
\$pdo = new PDO('mysql:host='.getenv('DB_LEGADO_GESTAOCEDEC_HOST').';port='.getenv('DB_LEGADO_GESTAOCEDEC_PORT').';dbname='.getenv('DB_LEGADO_GESTAOCEDEC_DATABASE').';charset=utf8mb4', getenv('DB_LEGADO_GESTAOCEDEC_USERNAME'), getenv('DB_LEGADO_GESTAOCEDEC_PASSWORD'), [PDO::ATTR_TIMEOUT=>3]);
echo 'OK: '.\$pdo->query('SELECT COUNT(*) c FROM cedec_municipio')->fetch()['c'].' linhas em cedec_municipio'.PHP_EOL;
echo 'OK: '.\$pdo->query('SELECT COUNT(*) c FROM cedec_prefeitura')->fetch()['c'].' linhas em cedec_prefeitura'.PHP_EOL;
"
docker exec newsdc_dev_app php artisan tinker --execute="echo config('cedec.legacy_connection');"
```

Expected: `OK: 854 linhas em cedec_municipio` e `OK: 854 linhas em cedec_prefeitura`
(numeros medidos nesta sessao; qualquer contagem > 0 sem erro de conexao ja confirma o
desbloqueio), e `legado_gestaocedec` impresso pelo tinker.

Confirmar tambem que a conexao antiga continua servindo os outros consumidores (sem
regressao):

```bash
docker exec newsdc_dev_app php artisan tinker --execute="echo config('compdec.legacy_connection');"
```

Expected: `legacy` (inalterado).

- [ ] **Step 10: Commit (Task 1 sozinha — mudanca de ambiente/config, nao de codigo de dominio)**

`SDC/.env` NAO entra no commit (gitignored). Comitar `.env.example`,
`docker/compose.dev.yml`, `config/database.php` e `config/cedec.php`:

```bash
git add SDC/.env.example SDC/docker/compose.dev.yml SDC/config/database.php SDC/config/cedec.php
git commit -m "$(cat <<'EOF'
🔧 config(cedec): adiciona conexao dedicada para o legado gestaocedec

O ETL de prefeituras precisa de cedec_municipio/cedec_prefeitura, que vivem em
gestaocedec_local -- um sistema legado DIFERENTE do que a conexao 'legacy' usa
(dbsdc, legado sdc/Laravel), com tabelas de mesmo nome e schema incompativel.
Reaproveitar 'legacy' quebraria os outros seis consumidores dessa conexao. Segue o
precedente de legado_cisterna_mysql: conexao nova legado_gestaocedec, dedicada e
somente leitura, mais config/cedec.php (legacy_connection) para o ETL ler dela.
EOF
)"
```

---

### Task 2: Migration — sete colunas novas em `compdec_prefeituras`

**Files:**
- Modify: `SDC/database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php`
- Test: `SDC/tests/Feature/Cedec/CompdecPrefeiturasMigrationTest.php`

**Interfaces:**
- Consumes: nada (schema puro).
- Produces: colunas `prefeito_partido` (string 60, nullable), `email_prefeitura`
  (string 255, nullable), `email_prefeitura_2` (string 255, nullable),
  `email_prefeitura_3` (string 255, nullable), `tel_prefeitura` (string 20, nullable),
  `tel_prefeitura_2` (string 20, nullable), `fax_prefeitura` (string 20, nullable) em
  `compdec_prefeituras` — usadas por Tasks 3, 4, 5 e 6.
- **Adicao alem do contrato:** o contrato (secao 1) so mostra o bloco a inserir dentro
  de `Schema::create`. Como a tabela ja existe em todo banco de desenvolvimento/producao
  (`if (Schema::hasTable(...)) { return; }` faz o `Schema::create` nunca rodar de novo
  nesses bancos), este plano acrescenta um metodo privado
  `adicionarColunasContatoInstitucional(): void` chamado ANTES do `return` antecipado,
  que adiciona as mesmas sete colunas via `Schema::table` + `hasColumn` (idempotente).
  Isso preserva a regra de ouro 9 (uma migration so, sem `add_*`) e ainda funciona em
  bancos ja migrados.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/Cedec/CompdecPrefeiturasMigrationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompdecPrefeiturasMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_compdec_prefeituras_tem_as_sete_colunas_de_contato_institucional(): void
    {
        foreach ([
            'prefeito_partido',
            'email_prefeitura',
            'email_prefeitura_2',
            'email_prefeitura_3',
            'tel_prefeitura',
            'tel_prefeitura_2',
            'fax_prefeitura',
        ] as $coluna) {
            $this->assertTrue(
                Schema::hasColumn('compdec_prefeituras', $coluna),
                "Coluna {$coluna} ausente em compdec_prefeituras."
            );
        }
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CompdecPrefeiturasMigrationTest
```

Expected: FAIL — `Coluna prefeito_partido ausente em compdec_prefeituras.`

- [ ] **Step 3: Editar a migration**

Em `SDC/database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php`,
substituir o metodo `up()` inteiro por:

```php
    public function up(): void
    {
        if (Schema::hasTable('compdec_prefeituras')) {
            $this->adicionarColunasContatoInstitucional();

            return;
        }

        Schema::create('compdec_prefeituras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id')
                ->unique()
                ->constrained('municipios')
                ->cascadeOnDelete()
                ->comment('Cada municipio tem uma unica prefeitura');

            // Dados do prefeito
            $table->string('prefeito_nome', 255)->nullable();
            $table->string('prefeito_telefone', 20)->nullable();
            $table->string('prefeito_celular', 20)->nullable();
            $table->string('prefeito_email', 255)->nullable();
            $table->string('prefeito_partido', 60)->nullable();

            // Contato institucional da prefeitura (fonte do relatorio de contatos)
            $table->string('email_prefeitura', 255)->nullable()
                ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos');
            $table->string('email_prefeitura_2', 255)->nullable();
            $table->string('email_prefeitura_3', 255)->nullable();
            $table->string('tel_prefeitura', 20)->nullable();
            $table->string('tel_prefeitura_2', 20)->nullable();
            $table->string('fax_prefeitura', 20)->nullable();

            // Endereco
            $table->text('endereco')->nullable();
            $table->string('bairro', 120)->nullable();
            $table->string('cep', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // INSS
            $table->boolean('inss_tem_cobranca')->default(false);
            $table->decimal('inss_aliquota', 5, 2)->nullable()
                ->comment('% da aliquota INSS, ex: 2.50');
            $table->string('inss_lei_cobranca', 120)->nullable();
            $table->string('inss_responsavel', 255)->nullable();

            // Rastreabilidade ETL
            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original do cedec_prefeitura.id');

            $table->timestamps();
            $table->softDeletes();

            $table->index('legacy_id');
        });
    }

    /**
     * compdec_prefeituras ja existe em todo banco de desenvolvimento/producao, entao
     * o Schema::create acima nunca roda de novo la. Este metodo adiciona as mesmas
     * sete colunas de contato institucional via Schema::table, mantendo uma unica
     * migration consolidada (regra de ouro 9: sem add_*) que funciona tanto para
     * instalacao nova quanto para banco ja migrado.
     */
    private function adicionarColunasContatoInstitucional(): void
    {
        Schema::table('compdec_prefeituras', function (Blueprint $table) {
            if (! Schema::hasColumn('compdec_prefeituras', 'prefeito_partido')) {
                $table->string('prefeito_partido', 60)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura')) {
                $table->string('email_prefeitura', 255)->nullable()
                    ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos');
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura_2')) {
                $table->string('email_prefeitura_2', 255)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura_3')) {
                $table->string('email_prefeitura_3', 255)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'tel_prefeitura')) {
                $table->string('tel_prefeitura', 20)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'tel_prefeitura_2')) {
                $table->string('tel_prefeitura_2', 20)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'fax_prefeitura')) {
                $table->string('fax_prefeitura', 20)->nullable();
            }
        });
    }
```

`down()` continua igual (`Schema::dropIfExists('compdec_prefeituras')`).

- [ ] **Step 4: Rodar a migration**

```bash
docker exec newsdc_dev_app php artisan migrate
```

Expected: se a migration ja constava como executada (`migrations` table), Laravel nao
a roda de novo automaticamente — nesse caso force uma re-execucao pontual so desta
migration:

```bash
docker exec newsdc_dev_app php artisan migrate:refresh --path=database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php
```

Se esse comando nao existir na versao do Laravel (o `--path` do `refresh` roda TODAS
as migrations, nao so uma), a alternativa segura e chamar a classe diretamente via
tinker:

```bash
docker exec newsdc_dev_app php artisan tinker --execute="(new (require database_path('migrations/2026_05_05_100004_create_compdec_prefeituras_table.php')))->up();"
```

- [ ] **Step 5: Rodar o teste e confirmar que passa**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CompdecPrefeiturasMigrationTest
```

Expected: PASS.

*(Sem commit aqui — Task 2 fecha junto com as Tasks 3-5 no mesmo commit, ver fim da
Task 5.)*

---

### Task 3: Model — `Prefeitura::$fillable` recebe as sete colunas

**Files:**
- Modify: `SDC/app/Modules/Compdec/Models/Prefeitura.php:29-45`
- Test: `SDC/tests/Feature/Cedec/PrefeituraFillableTest.php`

**Interfaces:**
- Consumes: colunas da Task 2.
- Produces: `Prefeitura::$fillable` aceitando `prefeito_partido`, `email_prefeitura`,
  `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2`,
  `fax_prefeitura` — usado por Task 6 (`updateOrCreate`) e por fases futuras (2-5).
  `$casts` **nao muda** (as sete sao string nullable, sem cast especial — conforme
  contrato secao 2).

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/Cedec/PrefeituraFillableTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrefeituraFillableTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fillable_aceita_as_sete_colunas_novas_via_mass_assignment(): void
    {
        $municipio = Municipio::factory()->create();

        $prefeitura = Prefeitura::create([
            'municipio_id' => $municipio->id,
            'prefeito_partido' => 'PSD',
            'email_prefeitura' => 'contato@cidade.gov.br',
            'email_prefeitura_2' => 'sec@cidade.gov.br',
            'email_prefeitura_3' => 'ter@cidade.gov.br',
            'tel_prefeitura' => '(31) 3333-4444',
            'tel_prefeitura_2' => '(31) 3333-5555',
            'fax_prefeitura' => '(31) 3333-6666',
        ]);

        $prefeitura->refresh();

        $this->assertSame('PSD', $prefeitura->prefeito_partido);
        $this->assertSame('contato@cidade.gov.br', $prefeitura->email_prefeitura);
        $this->assertSame('sec@cidade.gov.br', $prefeitura->email_prefeitura_2);
        $this->assertSame('ter@cidade.gov.br', $prefeitura->email_prefeitura_3);
        $this->assertSame('(31) 3333-4444', $prefeitura->tel_prefeitura);
        $this->assertSame('(31) 3333-5555', $prefeitura->tel_prefeitura_2);
        $this->assertSame('(31) 3333-6666', $prefeitura->fax_prefeitura);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraFillableTest
```

Expected: FAIL — os campos ficam `null` (mass assignment silenciosamente ignorado
porque nao estao em `$fillable`), assercao `assertSame('PSD', null)` falha.

- [ ] **Step 3: Editar o Model**

Em `SDC/app/Modules/Compdec/Models/Prefeitura.php`, trocar:

```php
    protected $fillable = [
        'municipio_id',
        'prefeito_nome',
        'prefeito_telefone',
        'prefeito_celular',
        'prefeito_email',
        'endereco',
        'bairro',
        'cep',
        'latitude',
        'longitude',
        'inss_tem_cobranca',
        'inss_aliquota',
        'inss_lei_cobranca',
        'inss_responsavel',
        'legacy_id',
    ];
```

por:

```php
    protected $fillable = [
        'municipio_id',
        'prefeito_nome',
        'prefeito_telefone',
        'prefeito_celular',
        'prefeito_email',
        'endereco',
        'bairro',
        'cep',
        'latitude',
        'longitude',
        'inss_tem_cobranca',
        'inss_aliquota',
        'inss_lei_cobranca',
        'inss_responsavel',
        'legacy_id',
        'prefeito_partido',
        'email_prefeitura',
        'email_prefeitura_2',
        'email_prefeitura_3',
        'tel_prefeitura',
        'tel_prefeitura_2',
        'fax_prefeitura',
    ];
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraFillableTest
```

Expected: PASS.

*(Sem commit aqui.)*

---

### Task 4: DTO — `PrefeituraDTO` recebe as sete propriedades

**Files:**
- Modify: `SDC/app/Modules/Compdec/DTOs/PrefeituraDTO.php`
- Test: `SDC/tests/Feature/Cedec/PrefeituraDTOTest.php`

**Interfaces:**
- Consumes: nada de fase anterior (DTO puro).
- Produces: `PrefeituraDTO` com propriedades publicas `?string $prefeitoPartido`,
  `?string $emailPrefeitura`, `?string $emailPrefeitura2`, `?string $emailPrefeitura3`,
  `?string $telPrefeitura`, `?string $telPrefeitura2`, `?string $faxPrefeitura`
  (todas `= null` por default); `fromRequest(int $municipioId, array $data): self` lendo
  as chaves snake_case correspondentes; `toArray(): array` devolvendo as mesmas chaves.
  Usado por Task 6 (indiretamente, o service continua usando array puro para
  `updateOrCreate` — o DTO e o contrato de entrada que a Fase 2 vai usar no
  controller/request do modulo Cedec).

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/Cedec/PrefeituraDTOTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Modules\Compdec\DTOs\PrefeituraDTO;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrefeituraDTOTest extends TestCase
{
    use DatabaseTransactions;

    public function test_from_request_e_to_array_cobrem_as_sete_colunas_novas(): void
    {
        $dados = [
            'prefeito_partido' => 'PSD',
            'email_prefeitura' => 'contato@cidade.gov.br',
            'email_prefeitura_2' => 'secundario@cidade.gov.br',
            'email_prefeitura_3' => 'terciario@cidade.gov.br',
            'tel_prefeitura' => '(31) 3333-4444',
            'tel_prefeitura_2' => '(31) 3333-5555',
            'fax_prefeitura' => '(31) 3333-6666',
        ];

        $dto = PrefeituraDTO::fromRequest(42, $dados);

        $this->assertSame('PSD', $dto->prefeitoPartido);
        $this->assertSame('contato@cidade.gov.br', $dto->emailPrefeitura);
        $this->assertSame('secundario@cidade.gov.br', $dto->emailPrefeitura2);
        $this->assertSame('terciario@cidade.gov.br', $dto->emailPrefeitura3);
        $this->assertSame('(31) 3333-4444', $dto->telPrefeitura);
        $this->assertSame('(31) 3333-5555', $dto->telPrefeitura2);
        $this->assertSame('(31) 3333-6666', $dto->faxPrefeitura);

        $array = $dto->toArray();

        foreach ($dados as $chave => $valor) {
            $this->assertSame($valor, $array[$chave]);
        }
    }

    public function test_from_request_aceita_ausencia_das_sete_colunas_como_null(): void
    {
        $dto = PrefeituraDTO::fromRequest(42, []);

        $this->assertNull($dto->prefeitoPartido);
        $this->assertNull($dto->emailPrefeitura);
        $this->assertNull($dto->emailPrefeitura2);
        $this->assertNull($dto->emailPrefeitura3);
        $this->assertNull($dto->telPrefeitura);
        $this->assertNull($dto->telPrefeitura2);
        $this->assertNull($dto->faxPrefeitura);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraDTOTest
```

Expected: FAIL — `Undefined property: PrefeituraDTO::$prefeitoPartido` (erro fatal) ou
erro de tipo, dependendo da versao do PHPUnit; em todo caso nao passa.

- [ ] **Step 3: Editar o DTO**

Em `SDC/app/Modules/Compdec/DTOs/PrefeituraDTO.php`, substituir o arquivo inteiro por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class PrefeituraDTO
{
    public function __construct(
        public int $municipioId,
        public ?string $prefeitoNome = null,
        public ?string $prefeitoTelefone = null,
        public ?string $prefeitoCelular = null,
        public ?string $prefeitoEmail = null,
        public ?string $endereco = null,
        public ?string $bairro = null,
        public ?string $cep = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $inssTemCobranca = false,
        public ?float $inssAliquota = null,
        public ?string $inssLeiCobranca = null,
        public ?string $inssResponsavel = null,
        public ?int $legacyId = null,
        public ?string $prefeitoPartido = null,
        public ?string $emailPrefeitura = null,
        public ?string $emailPrefeitura2 = null,
        public ?string $emailPrefeitura3 = null,
        public ?string $telPrefeitura = null,
        public ?string $telPrefeitura2 = null,
        public ?string $faxPrefeitura = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(int $municipioId, array $data): self
    {
        return new self(
            municipioId: $municipioId,
            prefeitoNome: $data['prefeito_nome'] ?? null,
            prefeitoTelefone: $data['prefeito_telefone'] ?? null,
            prefeitoCelular: $data['prefeito_celular'] ?? null,
            prefeitoEmail: $data['prefeito_email'] ?? null,
            endereco: $data['endereco'] ?? null,
            bairro: $data['bairro'] ?? null,
            cep: $data['cep'] ?? null,
            latitude: isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            inssTemCobranca: (bool) ($data['inss_tem_cobranca'] ?? false),
            inssAliquota: isset($data['inss_aliquota']) && $data['inss_aliquota'] !== '' ? (float) $data['inss_aliquota'] : null,
            inssLeiCobranca: $data['inss_lei_cobranca'] ?? null,
            inssResponsavel: $data['inss_responsavel'] ?? null,
            legacyId: isset($data['legacy_id']) ? (int) $data['legacy_id'] : null,
            prefeitoPartido: $data['prefeito_partido'] ?? null,
            emailPrefeitura: $data['email_prefeitura'] ?? null,
            emailPrefeitura2: $data['email_prefeitura_2'] ?? null,
            emailPrefeitura3: $data['email_prefeitura_3'] ?? null,
            telPrefeitura: $data['tel_prefeitura'] ?? null,
            telPrefeitura2: $data['tel_prefeitura_2'] ?? null,
            faxPrefeitura: $data['fax_prefeitura'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id' => $this->municipioId,
            'prefeito_nome' => $this->prefeitoNome,
            'prefeito_telefone' => $this->prefeitoTelefone,
            'prefeito_celular' => $this->prefeitoCelular,
            'prefeito_email' => $this->prefeitoEmail,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'cep' => $this->cep,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'inss_tem_cobranca' => $this->inssTemCobranca,
            'inss_aliquota' => $this->inssAliquota,
            'inss_lei_cobranca' => $this->inssLeiCobranca,
            'inss_responsavel' => $this->inssResponsavel,
            'legacy_id' => $this->legacyId,
            'prefeito_partido' => $this->prefeitoPartido,
            'email_prefeitura' => $this->emailPrefeitura,
            'email_prefeitura_2' => $this->emailPrefeitura2,
            'email_prefeitura_3' => $this->emailPrefeitura3,
            'tel_prefeitura' => $this->telPrefeitura,
            'tel_prefeitura_2' => $this->telPrefeitura2,
            'fax_prefeitura' => $this->faxPrefeitura,
        ];
    }
}
```

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraDTOTest
```

Expected: PASS (2 testes).

*(Sem commit aqui.)*

---

### Task 5: Factory — `PrefeituraFactory` cobre as colunas novas

**Files:**
- Modify: `SDC/database/factories/PrefeituraFactory.php`
- Test: `SDC/tests/Feature/Cedec/PrefeituraFactoryTest.php`

**Interfaces:**
- Consumes: colunas da Task 2, `$fillable` da Task 3.
- Produces: `Prefeitura::factory()->create()` preenchendo `prefeito_partido`,
  `email_prefeitura`, `tel_prefeitura`, `fax_prefeitura` com fakes realistas, e
  deixando `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura_2` como `null`
  por padrao (o caso comum medido no legado: so 1 de 3 e-mails costuma existir) — usado
  pelos testes de Tasks 6/7 e pelas fases 2-5.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/Cedec/PrefeituraFactoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrefeituraFactoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_preenche_as_colunas_de_contato_institucional(): void
    {
        $municipio = Municipio::factory()->create();
        $prefeitura = Prefeitura::factory()->create(['municipio_id' => $municipio->id]);

        $prefeitura->refresh();

        $this->assertNotNull($prefeitura->prefeito_partido);
        $this->assertNotNull($prefeitura->email_prefeitura);
        $this->assertNotNull($prefeitura->tel_prefeitura);
        $this->assertNotNull($prefeitura->fax_prefeitura);
        $this->assertNull($prefeitura->email_prefeitura_2);
        $this->assertNull($prefeitura->email_prefeitura_3);
        $this->assertNull($prefeitura->tel_prefeitura_2);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraFactoryTest
```

Expected: FAIL — `assertNotNull` falha porque `definition()` ainda nao preenche
`prefeito_partido` (fica `null` por nao estar no array de definicao, mas a coluna
existe e aceita `null` — o insert nao quebra, so o valor fica nulo).

- [ ] **Step 3: Editar a Factory**

Em `SDC/database/factories/PrefeituraFactory.php`, trocar o `definition()`:

```php
    public function definition(): array
    {
        return [
            'municipio_id' => null,
            'prefeito_nome' => $this->faker->name(),
            'prefeito_telefone' => $this->faker->numerify('(##) ####-####'),
            'prefeito_celular' => $this->faker->numerify('(##) #####-####'),
            'prefeito_email' => $this->faker->safeEmail(),
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->word(),
            'cep' => $this->faker->numerify('#####-###'),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'inss_tem_cobranca' => $this->faker->boolean(30),
            'inss_aliquota' => null,
            'inss_lei_cobranca' => null,
            'inss_responsavel' => null,
            'legacy_id' => null,
        ];
    }
```

por:

```php
    public function definition(): array
    {
        return [
            'municipio_id' => null,
            'prefeito_nome' => $this->faker->name(),
            'prefeito_telefone' => $this->faker->numerify('(##) ####-####'),
            'prefeito_celular' => $this->faker->numerify('(##) #####-####'),
            'prefeito_email' => $this->faker->safeEmail(),
            'prefeito_partido' => $this->faker->randomElement(['PT', 'PSD', 'PL', 'MDB', 'PSDB', 'REPUBLICANOS']),
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->word(),
            'cep' => $this->faker->numerify('#####-###'),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'inss_tem_cobranca' => $this->faker->boolean(30),
            'inss_aliquota' => null,
            'inss_lei_cobranca' => null,
            'inss_responsavel' => null,
            'legacy_id' => null,
            'email_prefeitura' => $this->faker->companyEmail(),
            'email_prefeitura_2' => null,
            'email_prefeitura_3' => null,
            'tel_prefeitura' => $this->faker->numerify('(##) ####-####'),
            'tel_prefeitura_2' => null,
            'fax_prefeitura' => $this->faker->numerify('(##) ####-####'),
        ];
    }
```

`comInss()` continua igual, sem alteracao.

- [ ] **Step 4: Rodar o teste e confirmar que passa**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraFactoryTest
```

Expected: PASS.

- [ ] **Step 5: Rodar as Tasks 2-5 juntas para confirmar que nada quebrou**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter="CompdecPrefeiturasMigrationTest|PrefeituraFillableTest|PrefeituraDTOTest|PrefeituraFactoryTest"
```

Expected: PASS em todos.

- [ ] **Step 6: Commit — Tasks 2, 3, 4 e 5 juntas**

```bash
git add SDC/database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php \
        SDC/app/Modules/Compdec/Models/Prefeitura.php \
        SDC/app/Modules/Compdec/DTOs/PrefeituraDTO.php \
        SDC/database/factories/PrefeituraFactory.php \
        SDC/tests/Feature/Cedec/CompdecPrefeiturasMigrationTest.php \
        SDC/tests/Feature/Cedec/PrefeituraFillableTest.php \
        SDC/tests/Feature/Cedec/PrefeituraDTOTest.php \
        SDC/tests/Feature/Cedec/PrefeituraFactoryTest.php
git commit -m "$(cat <<'EOF'
🗃️ db(cedec): adiciona contato institucional em compdec_prefeituras

Sete colunas novas (e-mails, telefones, fax institucionais e partido do prefeito)
consolidadas na migration de criacao existente (idempotente para banco ja migrado).
Prefeitura::$fillable, PrefeituraDTO e PrefeituraFactory acompanham as colunas.
EOF
)"
```

---

### Task 6: ETL — corrigir `PrefeituraService::migrarLegado()`

Esta e a task central da fase. O metodo hoje consulta `com_comdec` (tabela sem as
colunas usadas — a migracao estoura em qualquer ambiente com o banco legado real
conectado). A correcao troca a origem para `cedec_municipio LEFT JOIN cedec_prefeitura`
(ambas na conexao `legacy`, confirmado nesta sessao: 854 linhas em cada), resolve a
ponte para `municipios.codigo_ibge` via `Codmundv`, aplica a precedencia campo a campo
do contrato (secao 9) e a higienizacao (trim, e-mail minusculo, `"-"`/vazio -> null,
e-mail invalido -> null + log), e copia a foto do prefeito para a Media Library.

**Files:**
- Modify: `SDC/app/Modules/Compdec/Services/PrefeituraService.php`
- Test: `SDC/tests/Feature/Cedec/PrefeituraEtlMigracaoTest.php`

**Interfaces:**
- Consumes: `Prefeitura::$fillable` (Task 3), `App\Modules\Compdec\Support\LegacyParser::
  toIntOrNull/toStringOrNull/toDecimalBR/toBool` (existente, sem alteracao),
  `App\Modules\Compdec\Support\MigracaoReport` (existente, sem alteracao),
  `App\Models\Municipio` (existente: `codigo_ibge` string, `id` int),
  `config('cedec.legacy_connection')` (Task 1, `config/cedec.php` — **nao**
  `config('compdec.legacy_connection')`: aquela aponta para `dbsdc`, banco errado,
  ver Task 1), `config('compdec.legacy_paths.foto_prefeito')`, `config('compdec.disk')`
  (essas duas ultimas continuam em `config/compdec.php`, sem mudanca).
- Produces: `PrefeituraService::migrarLegado(int $chunk = 100, bool $dryRun = false):
  MigracaoReport` — MESMA assinatura de hoje, comportamento corrigido. Consumido por
  Task 7 (Command).
- **Adicao alem do contrato:** sete metodos privados novos dentro de
  `PrefeituraService` — `sanitizarEmail(?string): ?string`,
  `sanitizarTelefone(?string): ?string`, `limparCampoSujo(?string): ?string`,
  `validarOuDescartarEmail(?string, ?int, string, object, bool): ?string`,
  `resolverEmailComPrecedencia(array, ?int, string, object, bool): ?string`,
  `sanitizarCoordenada(mixed): ?float`,
  `migrarFotoPrefeito(Prefeitura, ?string, ?int, bool): void`. O contrato (secao 9) so
  descreve a higienizacao em prosa ("trim...e-mail para minusculas...'-' vira null");
  estes metodos sao a implementacao dessa prosa, privados e sem uso fora desta classe,
  entao nao colidem com nomes que outras fases consomem.
  `resolverEmailComPrecedencia` valida CADA candidato (higienizado) antes de
  escolher, na ordem de precedencia, em vez de escolher o primeiro preenchido e so
  depois validar (isso perdia o fallback valido quando o candidato de maior
  precedencia era um e-mail malformado). `sanitizarCoordenada` filtra null/vazio/"-"
  antes de chamar `LegacyParser::toDecimalBR`, que nunca devolve null (vazio vira
  0.0, coordenada real no Golfo da Guine) — sem alterar `LegacyParser`, compartilhado
  por outros ETLs.

- [ ] **Step 1: Escrever os testes que falham**

Criar `SDC/tests/Feature/Cedec/PrefeituraEtlMigracaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Compdec\Models\Prefeitura;
use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrefeituraEtlMigracaoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Troca a conexao dedicada 'legado_gestaocedec' (normalmente MySQL do
        // Laragon, config/cedec.php -> config/database.php) por sqlite em memoria:
        // isola o teste da rede/host, e da controle total sobre os dados "sujos"
        // que cada cenario precisa, sem depender do banco legado real. NAO mexe na
        // conexao 'legacy' (dbsdc), que e de outros consumidores e nao entra neste
        // teste.
        config(['database.connections.legado_gestaocedec' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('legado_gestaocedec');

        Schema::connection('legado_gestaocedec')->create('cedec_municipio', function (Blueprint $table): void {
            $table->integer('id_municipio')->primary();
            $table->string('Codmundv', 10)->nullable();
            $table->string('prefeito', 45)->nullable();
            $table->string('tel_pref', 20)->nullable();
            $table->string('cel_pref', 20)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('tel', 16)->nullable();
            $table->string('fax', 16)->nullable();
            $table->string('endereco', 70)->nullable();
            $table->string('bairro', 45)->nullable();
            $table->string('cep', 45)->nullable();
            $table->double('latitude_dec')->nullable();
            $table->double('longitude_dec')->nullable();
            $table->string('cobra_iss', 10)->nullable();
            $table->decimal('aliquota_iss', 16, 2)->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->string('resp_cob_iss', 15)->nullable();
        });

        Schema::connection('legado_gestaocedec')->create('cedec_prefeitura', function (Blueprint $table): void {
            $table->integer('id_prefeitura')->primary();
            $table->integer('id_municipio');
            $table->string('prefeiro', 45)->nullable();
            $table->string('partido', 45)->nullable();
            $table->string('tel1', 45)->nullable();
            $table->string('tel2', 45)->nullable();
            $table->string('cel1', 45)->nullable();
            $table->string('cel2', 45)->nullable();
            $table->string('fax', 45)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('email2', 45)->nullable();
            $table->string('email3', 45)->nullable();
            $table->string('fotoPref', 110)->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('legado_gestaocedec')->dropIfExists('cedec_prefeitura');
        Schema::connection('legado_gestaocedec')->dropIfExists('cedec_municipio');

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function inserirMunicipioLegado(array $dados): void
    {
        DB::connection('legado_gestaocedec')->table('cedec_municipio')->insert(array_merge([
            'id_municipio' => 1,
            'Codmundv' => null,
            'prefeito' => null,
            'tel_pref' => null,
            'cel_pref' => null,
            'email' => null,
            'tel' => null,
            'fax' => null,
            'endereco' => null,
            'bairro' => null,
            'cep' => null,
            'latitude_dec' => null,
            'longitude_dec' => null,
            'cobra_iss' => null,
            'aliquota_iss' => null,
            'num_lei_iss' => null,
            'resp_cob_iss' => null,
        ], $dados));
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function inserirPrefeituraLegada(array $dados): void
    {
        DB::connection('legado_gestaocedec')->table('cedec_prefeitura')->insert(array_merge([
            'id_prefeitura' => 1,
            'id_municipio' => 1,
            'prefeiro' => null,
            'partido' => null,
            'tel1' => null,
            'tel2' => null,
            'cel1' => null,
            'cel2' => null,
            'fax' => null,
            'email' => null,
            'email2' => null,
            'email3' => null,
            'fotoPref' => null,
        ], $dados));
    }

    /**
     * SDC/phpunit.xml NAO isola banco de teste (linhas de sqlite comentadas): os
     * testes rodam contra o Postgres de desenvolvimento, que ja tem 853 municipios
     * reais, alguns com o mesmo codigo_ibge que este teste precisa. firstOrCreate
     * evita a violacao da UNIQUE em municipios.codigo_ibge -- reaproveita a linha
     * se ja existir, so cria quando faltar. O lado legado (sqlite, cedec_municipio/
     * cedec_prefeitura) continua sob controle total do teste; so o Postgres tem
     * dados pre-existentes.
     */
    private function municipioComCodigo(string $codigoIbge): Municipio
    {
        return Municipio::firstOrCreate(
            ['codigo_ibge' => $codigoIbge],
            ['nome' => 'Municipio Teste '.$codigoIbge, 'uf' => 'MG'],
        );
    }

    public function test_dry_run_nao_escreve_prefeitura_nem_log(): void
    {
        $municipio = $this->municipioComCodigo('3100104');
        $this->inserirMunicipioLegado(['id_municipio' => 501, 'Codmundv' => '3100104', 'prefeito' => 'Ana Teste']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 501, 'id_municipio' => 501]);

        $totalAntes = Prefeitura::count();
        $logAntes = DB::table('compdec_etl_log')->count();

        $report = app(PrefeituraService::class)->migrarLegado(100, true);

        $this->assertSame($totalAntes, Prefeitura::count());
        $this->assertSame($logAntes, DB::table('compdec_etl_log')->count());
        $this->assertSame(1, $report->inseridos);
        $this->assertTrue($report->dryRun);
    }

    public function test_tel2_com_traco_vira_null(): void
    {
        $municipio = $this->municipioComCodigo('3100203');
        $this->inserirMunicipioLegado(['id_municipio' => 502, 'Codmundv' => '3100203']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 502, 'id_municipio' => 502, 'tel2' => '-']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertNotNull($prefeitura);
        $this->assertNull($prefeitura->tel_prefeitura_2);
    }

    public function test_email_com_espaco_a_esquerda_e_normalizado_para_minusculas(): void
    {
        $municipio = $this->municipioComCodigo('3100302');
        $this->inserirMunicipioLegado(['id_municipio' => 503, 'Codmundv' => '3100302', 'email' => '   Prefeitura@Cidade.MG.GOV.BR']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 503, 'id_municipio' => 503]);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertSame('prefeitura@cidade.mg.gov.br', $prefeitura->email_prefeitura);
    }

    public function test_email_invalido_vira_null_e_gera_log_skipped(): void
    {
        $municipio = $this->municipioComCodigo('3100401');
        $this->inserirMunicipioLegado(['id_municipio' => 504, 'Codmundv' => '3100401', 'email' => 'prefeitura@prefeitura@gmail.com1']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 504, 'id_municipio' => 504]);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertNull($prefeitura->email_prefeitura);

        $motivo = DB::table('compdec_etl_log')
            ->where('legacy_id', 504)
            ->where('acao', 'skipped')
            ->value('motivo');

        $this->assertNotNull($motivo);
        $this->assertStringContainsString('email_prefeitura invalido', $motivo);
    }

    public function test_codmundv_sem_par_em_municipios_e_ignorado_com_log(): void
    {
        $this->inserirMunicipioLegado(['id_municipio' => 505, 'Codmundv' => '9999999']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 505, 'id_municipio' => 505]);

        $report = app(PrefeituraService::class)->migrarLegado(100, false);

        $this->assertSame(0, Prefeitura::where('legacy_id', 505)->count());
        $this->assertGreaterThanOrEqual(1, $report->ignorados);
        $this->assertDatabaseHas('compdec_etl_log', [
            'legacy_id' => 505,
            'acao' => 'skipped',
        ]);
    }

    public function test_sentinela_7221_e_excluida_da_migracao(): void
    {
        $this->inserirMunicipioLegado(['id_municipio' => 7221, 'Codmundv' => '0', 'email' => 'prefeitura@prefeitura@gmail.com1']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 7221, 'id_municipio' => 7221]);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $this->assertSame(0, Prefeitura::where('legacy_id', 7221)->count());
        $this->assertDatabaseMissing('compdec_etl_log', ['legacy_id' => 7221]);
    }

    public function test_precedencia_email_prefeitura_e_municipio_sobre_prefeitura(): void
    {
        $municipio = $this->municipioComCodigo('3100609');
        $this->inserirMunicipioLegado(['id_municipio' => 507, 'Codmundv' => '3100609', 'email' => 'municipio@cidade.gov.br']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 507, 'id_municipio' => 507, 'email' => 'antigo@yahoo.com']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertSame('municipio@cidade.gov.br', $prefeitura->email_prefeitura);
    }

    public function test_email_da_prefeitura_e_usado_como_fallback_quando_municipio_esta_vazio(): void
    {
        $municipio = $this->municipioComCodigo('3100708');
        $this->inserirMunicipioLegado(['id_municipio' => 508, 'Codmundv' => '3100708', 'email' => null]);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 508, 'id_municipio' => 508, 'email' => 'fallback@hotmail.com']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertSame('fallback@hotmail.com', $prefeitura->email_prefeitura);
    }

    /**
     * Corrige o bug de precedencia: escolher o candidato de maior precedencia ANTES
     * de validar o formato perde o fallback valido quando esse candidato e um
     * e-mail malformado. cedec_municipio.email malformado nao pode anular
     * cedec_prefeitura.email, que e valido -- o correto e validar cada candidato e
     * so entao escolher o primeiro valido, na ordem de precedencia.
     */
    public function test_email_da_prefeitura_e_usado_quando_email_do_municipio_e_invalido(): void
    {
        $municipio = $this->municipioComCodigo('3100500');
        $this->inserirMunicipioLegado(['id_municipio' => 506, 'Codmundv' => '3100500', 'email' => 'prefeitura@prefeitura@gmail.com1']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 506, 'id_municipio' => 506, 'email' => 'fallback.valido@cidade.gov.br']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertSame('fallback.valido@cidade.gov.br', $prefeitura->email_prefeitura);
    }

    public function test_prefeito_nome_vem_de_cedec_municipio_nunca_de_cedec_prefeitura_prefeiro(): void
    {
        $municipio = $this->municipioComCodigo('3100807');
        $this->inserirMunicipioLegado(['id_municipio' => 509, 'Codmundv' => '3100807', 'prefeito' => 'Prefeito Atual']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 509, 'id_municipio' => 509, 'prefeiro' => 'Prefeito Mandato Anterior']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertSame('Prefeito Atual', $prefeitura->prefeito_nome);
        $this->assertNotSame('Prefeito Mandato Anterior', $prefeitura->prefeito_nome);
    }

    public function test_foto_do_prefeito_e_copiada_para_a_media_library_com_nome_sujo(): void
    {
        Storage::fake(config('compdec.disk', 'compdec'));

        $diretorioLegado = sys_get_temp_dir().'/cedec_fixture_fotos_'.uniqid();
        mkdir($diretorioLegado, 0777, true);
        config(['compdec.legacy_paths.foto_prefeito' => $diretorioLegado]);

        $nomeArquivo = '510_Foto_Prefeito.jpg.jpg';
        $imagem = imagecreatetruecolor(10, 10);
        imagejpeg($imagem, $diretorioLegado.'/'.$nomeArquivo);
        imagedestroy($imagem);

        $municipio = $this->municipioComCodigo('3100906');
        $this->inserirMunicipioLegado(['id_municipio' => 510, 'Codmundv' => '3100906']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 510, 'id_municipio' => 510, 'fotoPref' => $nomeArquivo]);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $media = $prefeitura->getFirstMedia(Prefeitura::MEDIA_FOTO_PREFEITO);

        $this->assertNotNull($media);
        $this->assertSame($nomeArquivo, $media->file_name);

        unlink($diretorioLegado.'/'.$nomeArquivo);
        rmdir($diretorioLegado);
    }

    public function test_foto_ausente_no_disco_legado_nao_interrompe_a_migracao(): void
    {
        config(['compdec.legacy_paths.foto_prefeito' => sys_get_temp_dir().'/cedec_diretorio_inexistente_'.uniqid()]);

        $municipio = $this->municipioComCodigo('3101003');
        $this->inserirMunicipioLegado(['id_municipio' => 511, 'Codmundv' => '3101003']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 511, 'id_municipio' => 511, 'fotoPref' => 'arquivo_que_nao_existe.jpg']);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertNotNull($prefeitura);
        $this->assertNull($prefeitura->getFirstMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
        $this->assertDatabaseHas('compdec_etl_log', ['legacy_id' => 511, 'acao' => 'skipped']);
    }

    /**
     * LegacyParser::toDecimalBR nunca devolve null (vazio/null viram 0.0), e 0.0
     * lat/long e um ponto real no Golfo da Guine -- nao pode representar "sem
     * coordenada". O ETL precisa filtrar null/vazio/"-" ANTES de chamar
     * toDecimalBR, sem alterar o LegacyParser (compartilhado por outros ETLs).
     */
    public function test_latitude_vazia_e_longitude_com_traco_viram_null_em_vez_de_zero(): void
    {
        $municipio = $this->municipioComCodigo('3101302');
        $this->inserirMunicipioLegado(['id_municipio' => 512, 'Codmundv' => '3101302', 'latitude_dec' => '', 'longitude_dec' => '-']);
        $this->inserirPrefeituraLegada(['id_prefeitura' => 512, 'id_municipio' => 512]);

        app(PrefeituraService::class)->migrarLegado(100, false);

        $prefeitura = Prefeitura::where('municipio_id', $municipio->id)->first();
        $this->assertNotNull($prefeitura);
        $this->assertNull($prefeitura->latitude);
        $this->assertNull($prefeitura->longitude);
    }
}
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraEtlMigracaoTest
```

Expected: FAIL em quase todos — o `migrarLegado()` atual consulta `com_comdec` (tabela
que nem existe no sqlite de teste) e estoura com erro de SQL antes de qualquer
asserção rodar.

- [ ] **Step 3: Editar `PrefeituraService.php`**

Em `SDC/app/Modules/Compdec/Services/PrefeituraService.php`, adicionar o import:

```php
use App\Models\Municipio;
```

(logo apos `use App\Modules\Compdec\Models\Prefeitura;`).

Substituir TODO o bloco a partir do comentario `/* ETL: Migracao ... */` ate o fim da
classe (linhas 84-198 do arquivo original) por:

```php
    /* ============================================================
     * ETL: Migracao do legado para compdec_prefeituras
     * ============================================================ */

    /**
     * Migra prefeituras do legado gestaocedec:
     *   - cedec_municipio (854 linhas) e a fonte de prefeito_nome/telefone/celular,
     *     contato institucional principal, endereco e INSS.
     *   - cedec_prefeitura (854 linhas, join por id_municipio) complementa com
     *     partido, e-mails/telefones secundarios (fallback) e a foto.
     *   - Ponte para o NewSDC: cedec_municipio.Codmundv = municipios.codigo_ibge
     *     (mesmo padrao de ImportCedecMunicipioCommand). Como 'legado_gestaocedec'
     *     (MySQL) e a conexao padrao (Postgres) sao conexoes diferentes, a resolucao
     *     e feita em duas etapas: le o chunk do legado, depois busca os municipio_id
     *     correspondentes em uma unica query por chunk.
     *   - id_municipio = 7221 (sentinela "MUNICIPIO TESTE", Codmundv = 0) e excluida.
     *   - Conexao lida de config('cedec.legacy_connection'), NAO de
     *     config('compdec.legacy_connection'): esta ultima aponta para 'legacy'
     *     (banco dbsdc, legado sdc/Laravel), usada por outros seis consumidores do
     *     modulo Compdec/Pmda/AjudaHumanitaria -- schema incompativel com
     *     cedec_municipio/cedec_prefeitura, que vivem no legado gestaocedec
     *     (banco gestaocedec_local). Ver docs/superpowers/plans/2026-09-04-cedec-fase1-dados-etl.md
     *     Task 1.
     */
    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('prefeituras');
        $report->dryRun = $dryRun;
        $connection = config('cedec.legacy_connection', 'legado_gestaocedec');

        DB::connection($connection)
            ->table('cedec_municipio as m')
            ->leftJoin('cedec_prefeitura as p', 'p.id_municipio', '=', 'm.id_municipio')
            ->where('m.id_municipio', '!=', 7221)
            ->select(
                'm.id_municipio as legacy_id',
                'm.Codmundv as codmundv',
                'm.prefeito as prefeito_nome',
                'm.tel_pref as prefeito_telefone',
                'm.cel_pref as prefeito_celular',
                'p.partido as prefeito_partido',
                'm.email as email_municipio',
                'p.email as email_prefeitura_legado',
                'p.email2 as email_prefeitura_2',
                'p.email3 as email_prefeitura_3',
                'm.tel as tel_municipio',
                'p.tel1 as tel_prefeitura_legado',
                'p.tel2 as tel_prefeitura_2',
                'm.fax as fax_municipio',
                'p.fax as fax_prefeitura_legado',
                'm.endereco', 'm.bairro', 'm.cep',
                'm.latitude_dec as latitude', 'm.longitude_dec as longitude',
                'm.cobra_iss', 'm.aliquota_iss', 'm.num_lei_iss', 'm.resp_cob_iss',
                'p.fotoPref as foto_prefeito',
            )
            ->orderBy('m.id_municipio')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                $codigos = $linhas->pluck('codmundv')->filter()->unique()->values()->all();

                $mapaMunicipios = Municipio::query()
                    ->whereIn('codigo_ibge', $codigos)
                    ->pluck('id', 'codigo_ibge');

                foreach ($linhas as $row) {
                    $this->migrarPrefeituraLegada($row, $mapaMunicipios, $report, $dryRun);
                }
            });

        return $report;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $mapaMunicipios  codigo_ibge => municipio_id
     */
    private function migrarPrefeituraLegada(object $row, $mapaMunicipios, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->legacy_id ?? null);
        $codmundv = LegacyParser::toStringOrNull($row->codmundv ?? null);

        if ($codmundv === null || ! $mapaMunicipios->has($codmundv)) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'Codmundv sem municipio correspondente em municipios.codigo_ibge', $row, $dryRun);

            return;
        }

        $municipioId = (int) $mapaMunicipios->get($codmundv);

        try {
            $emailPrefeitura = $this->resolverEmailComPrecedencia(
                [
                    'cedec_municipio.email' => $row->email_municipio ?? null,
                    'cedec_prefeitura.email' => $row->email_prefeitura_legado ?? null,
                ],
                $legacyId, 'email_prefeitura', $row, $dryRun,
            );

            $emailPrefeitura2 = $this->validarOuDescartarEmail(
                $this->sanitizarEmail($row->email_prefeitura_2 ?? null),
                $legacyId, 'email_prefeitura_2', $row, $dryRun,
            );

            $emailPrefeitura3 = $this->validarOuDescartarEmail(
                $this->sanitizarEmail($row->email_prefeitura_3 ?? null),
                $legacyId, 'email_prefeitura_3', $row, $dryRun,
            );

            $telPrefeitura = $this->sanitizarTelefone($row->tel_municipio ?? null)
                ?? $this->sanitizarTelefone($row->tel_prefeitura_legado ?? null);

            $faxPrefeitura = $this->sanitizarTelefone($row->fax_municipio ?? null)
                ?? $this->sanitizarTelefone($row->fax_prefeitura_legado ?? null);

            $payload = [
                'municipio_id' => $municipioId,
                'prefeito_nome' => LegacyParser::toStringOrNull($row->prefeito_nome ?? null),
                'prefeito_telefone' => $this->sanitizarTelefone($row->prefeito_telefone ?? null),
                'prefeito_celular' => $this->sanitizarTelefone($row->prefeito_celular ?? null),
                'prefeito_email' => null,
                'prefeito_partido' => LegacyParser::toStringOrNull($row->prefeito_partido ?? null),
                'email_prefeitura' => $emailPrefeitura,
                'email_prefeitura_2' => $emailPrefeitura2,
                'email_prefeitura_3' => $emailPrefeitura3,
                'tel_prefeitura' => $telPrefeitura,
                'tel_prefeitura_2' => $this->sanitizarTelefone($row->tel_prefeitura_2 ?? null),
                'fax_prefeitura' => $faxPrefeitura,
                'endereco' => LegacyParser::toStringOrNull($row->endereco ?? null),
                'bairro' => LegacyParser::toStringOrNull($row->bairro ?? null),
                'cep' => LegacyParser::toStringOrNull($row->cep ?? null),
                'latitude' => $this->sanitizarCoordenada($row->latitude ?? null),
                'longitude' => $this->sanitizarCoordenada($row->longitude ?? null),
                'inss_tem_cobranca' => LegacyParser::toBool($row->cobra_iss ?? null),
                'inss_aliquota' => isset($row->aliquota_iss) && $row->aliquota_iss !== null ? LegacyParser::toDecimalBR($row->aliquota_iss) : null,
                'inss_lei_cobranca' => LegacyParser::toStringOrNull($row->num_lei_iss ?? null),
                'inss_responsavel' => LegacyParser::toStringOrNull($row->resp_cob_iss ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = Prefeitura::query()->where('municipio_id', $municipioId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = Prefeitura::query()->where('municipio_id', $municipioId)->first();
            $prefeitura = Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);

            $this->migrarFotoPrefeito($prefeitura, LegacyParser::toStringOrNull($row->foto_prefeito ?? null), $legacyId, $dryRun);

            if ($existente) {
                $report->registrarAtualizacao();
                $this->logEtl($legacyId, $prefeitura->id, 'updated', null, $row, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl($legacyId, $prefeitura->id, 'inserted', null, $row, false);
            }
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    /**
     * Copia a foto do prefeito do disco legado (cedec_prefeitura.fotoPref, so o
     * nome do arquivo) para a Media Library. O nome no legado e sujo por natureza
     * (acentos removidos, as vezes extensao duplicada, ex.: "120_Foto_Prefeito.jpg.jpg")
     * e entra como esta, sem tentativa de limpeza -- preservingOriginal() garante que
     * o arquivo fonte (montado read-only) nunca e apagado.
     */
    private function migrarFotoPrefeito(Prefeitura $prefeitura, ?string $nomeArquivoLegado, ?int $legacyId, bool $dryRun): void
    {
        if ($nomeArquivoLegado === null || $dryRun) {
            return;
        }

        $diretorio = rtrim((string) config('compdec.legacy_paths.foto_prefeito'), '/');
        $caminhoCompleto = $diretorio.'/'.$nomeArquivoLegado;

        if (! is_file($caminhoCompleto)) {
            $this->logEtl($legacyId, $prefeitura->id, 'skipped', "foto_prefeito nao encontrada em {$caminhoCompleto}", null, false);

            return;
        }

        try {
            $prefeitura
                ->addMedia($caminhoCompleto)
                ->preservingOriginal()
                ->usingFileName($nomeArquivoLegado)
                ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
        } catch (Throwable $e) {
            $this->logEtl($legacyId, $prefeitura->id, 'skipped', "falha ao migrar foto_prefeito: {$e->getMessage()}", null, false);
        }
    }

    private function sanitizarEmail(?string $valor): ?string
    {
        $valor = $this->limparCampoSujo($valor);

        return $valor === null ? null : mb_strtolower($valor);
    }

    private function sanitizarTelefone(?string $valor): ?string
    {
        return $this->limparCampoSujo($valor);
    }

    /**
     * Higienizacao comum: trim (via LegacyParser::toStringOrNull, que ja trata ""
     * como null) e o traco solto ("-") que o legado usa como marcador de "nao
     * preenchido" em cedec_prefeitura.tel2 e afins.
     */
    private function limparCampoSujo(?string $valor): ?string
    {
        $valor = LegacyParser::toStringOrNull($valor);

        return $valor === '-' ? null : $valor;
    }

    private function validarOuDescartarEmail(?string $email, ?int $legacyId, string $campo, object $row, bool $dryRun): ?string
    {
        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return $email;
        }

        $this->logEtl($legacyId, null, 'skipped', "{$campo} invalido no legado: {$email}", $row, $dryRun);

        return null;
    }

    /**
     * Resolve um campo com fallback (ex.: email_prefeitura = cedec_municipio.email,
     * senao cedec_prefeitura.email) validando CADA candidato antes de escolher --
     * nao escolher o primeiro preenchido e so depois validar. Isso corrige o bug em
     * que um e-mail malformado na fonte de maior precedencia anulava o resultado e
     * impedia o fallback valido de ser usado. Se nenhum candidato for valido, grava
     * null e registra em compdec_etl_log quais candidatos foram descartados e por
     * que (formato invalido); candidatos vazios/ausentes nao contam como descarte.
     *
     * @param  array<string, mixed>  $candidatos  fonte (para o log) => valor bruto, na ordem de precedencia
     */
    private function resolverEmailComPrecedencia(array $candidatos, ?int $legacyId, string $campo, object $row, bool $dryRun): ?string
    {
        $descartados = [];

        foreach ($candidatos as $fonte => $valorBruto) {
            $email = $this->sanitizarEmail($valorBruto);

            if ($email === null) {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                return $email;
            }

            $descartados[] = "{$fonte}={$email}";
        }

        if ($descartados !== []) {
            $this->logEtl(
                $legacyId, null, 'skipped',
                "{$campo} invalido no legado, nenhum candidato valido: ".implode('; ', $descartados),
                $row, $dryRun,
            );
        }

        return null;
    }

    /**
     * LegacyParser::toDecimalBR nunca devolve null: vazio ou null viram 0.0, que e
     * um ponto real no Golfo da Guine e passaria a ser tratado como coordenada
     * valida. Filtra null/vazio/"-" ANTES de chamar toDecimalBR, na borda deste ETL
     * -- reusa limparCampoSujo (mesma higienizacao de telefone) em vez de alterar o
     * LegacyParser, que e compartilhado por outros ETLs.
     */
    private function sanitizarCoordenada(mixed $valor): ?float
    {
        $texto = $this->limparCampoSujo($valor === null ? null : (string) $valor);

        return $texto === null ? null : LegacyParser::toDecimalBR($texto);
    }

    private function logEtl(
        ?int $legacyId,
        ?int $newId,
        string $acao,
        ?string $motivo,
        mixed $payload,
        bool $dryRun,
    ): void {
        if ($dryRun) {
            return;
        }

        DB::table('compdec_etl_log')->insert([
            'recurso' => 'prefeituras',
            'legacy_table' => 'cedec_municipio+cedec_prefeitura',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
```

`obterPorOrgao()`, `upsertPorOrgao()`, `uploadFoto()`, `removerFoto()` e
`obterPrefeituraPorOrgaoOuFalhar()` (linhas 21-82 do arquivo original) **nao mudam** —
continuam servindo a aba do Compdec, intactos.

- [ ] **Step 4: Rodar os testes e confirmar que passam**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=PrefeituraEtlMigracaoTest
```

Expected: PASS em todos os 13 testes. Se `test_foto_do_prefeito_e_copiada...` falhar
por falta da extensao GD (`imagecreatetruecolor`/`imagejpeg` indefinidas), confirme
`php -m | grep -i gd` no container — a extensao ja e usada pelas conversoes de thumb
do Spatie Image (`Prefeitura::registerMediaConversions()`), entao deve estar presente.

*(Sem commit aqui — Task 6 fecha junto com a Task 7.)*

---

### Task 7: Command `cedec:importar-prefeituras`

**Files:**
- Create: `SDC/app/Modules/Cedec/Console/ImportarPrefeiturasCommand.php`
- Create: `SDC/app/Modules/Cedec/CedecServiceProvider.php`
- Modify: `SDC/config/app.php` (array `providers`)
- Test: `SDC/tests/Feature/Cedec/ImportarPrefeiturasCommandTest.php`

**Interfaces:**
- Consumes: `PrefeituraService::migrarLegado(int $chunk = 100, bool $dryRun = false):
  MigracaoReport` (Task 6).
- Produces: comando artisan `cedec:importar-prefeituras {--chunk=100} {--dry-run}`
  (assinatura exata do contrato, secao 9/10).
- **Adicao alem do contrato:** `App\Modules\Cedec\CedecServiceProvider` — o modulo
  `Cedec` ainda nao existe (nasce nesta fase so com `Console/`); o provider e o minimo
  necessario para o Laravel descobrir o command, seguindo o mesmo padrao de
  `CisternaServiceProvider::boot()` (`if ($this->app->runningInConsole()) { $this->
  commands([...]); }`). A Fase 2 vai EXPANDIR este mesmo arquivo (rotas, policies) —
  nao criar um novo.

- [ ] **Step 1: Escrever o teste que falha**

Criar `SDC/tests/Feature/Cedec/ImportarPrefeiturasCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Models\Municipio;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportarPrefeiturasCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legado_gestaocedec' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('legado_gestaocedec');

        Schema::connection('legado_gestaocedec')->create('cedec_municipio', function (Blueprint $table): void {
            $table->integer('id_municipio')->primary();
            $table->string('Codmundv', 10)->nullable();
            $table->string('prefeito', 45)->nullable();
            $table->string('tel_pref', 20)->nullable();
            $table->string('cel_pref', 20)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('tel', 16)->nullable();
            $table->string('fax', 16)->nullable();
            $table->string('endereco', 70)->nullable();
            $table->string('bairro', 45)->nullable();
            $table->string('cep', 45)->nullable();
            $table->double('latitude_dec')->nullable();
            $table->double('longitude_dec')->nullable();
            $table->string('cobra_iss', 10)->nullable();
            $table->decimal('aliquota_iss', 16, 2)->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->string('resp_cob_iss', 15)->nullable();
        });

        Schema::connection('legado_gestaocedec')->create('cedec_prefeitura', function (Blueprint $table): void {
            $table->integer('id_prefeitura')->primary();
            $table->integer('id_municipio');
            $table->string('prefeiro', 45)->nullable();
            $table->string('partido', 45)->nullable();
            $table->string('tel1', 45)->nullable();
            $table->string('tel2', 45)->nullable();
            $table->string('cel1', 45)->nullable();
            $table->string('cel2', 45)->nullable();
            $table->string('fax', 45)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('email2', 45)->nullable();
            $table->string('email3', 45)->nullable();
            $table->string('fotoPref', 110)->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('legado_gestaocedec')->dropIfExists('cedec_prefeitura');
        Schema::connection('legado_gestaocedec')->dropIfExists('cedec_municipio');

        parent::tearDown();
    }

    /**
     * Mesma justificativa de PrefeituraEtlMigracaoTest::municipioComCodigo(): o
     * banco de teste nao e isolado (SDC/phpunit.xml) e o Postgres de desenvolvimento
     * ja tem 853 municipios reais. firstOrCreate evita colidir com a UNIQUE em
     * municipios.codigo_ibge.
     */
    private function municipioComCodigo(string $codigoIbge): Municipio
    {
        return Municipio::firstOrCreate(
            ['codigo_ibge' => $codigoIbge],
            ['nome' => 'Municipio Teste '.$codigoIbge, 'uf' => 'MG'],
        );
    }

    public function test_dry_run_nao_grava_prefeitura_nenhuma(): void
    {
        $this->municipioComCodigo('3101104');

        DB::connection('legado_gestaocedec')->table('cedec_municipio')->insert([
            'id_municipio' => 601,
            'Codmundv' => '3101104',
            'prefeito' => 'Teste Command',
        ]);
        DB::connection('legado_gestaocedec')->table('cedec_prefeitura')->insert([
            'id_prefeitura' => 601,
            'id_municipio' => 601,
        ]);

        $totalAntes = Prefeitura::count();

        $this->artisan('cedec:importar-prefeituras', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame($totalAntes, Prefeitura::count());
    }

    public function test_comando_importa_de_fato_sem_dry_run(): void
    {
        $municipio = $this->municipioComCodigo('3101203');

        DB::connection('legado_gestaocedec')->table('cedec_municipio')->insert([
            'id_municipio' => 602,
            'Codmundv' => '3101203',
            'prefeito' => 'Prefeito Real',
        ]);
        DB::connection('legado_gestaocedec')->table('cedec_prefeitura')->insert([
            'id_prefeitura' => 602,
            'id_municipio' => 602,
        ]);

        $this->artisan('cedec:importar-prefeituras', ['--chunk' => 50])
            ->assertExitCode(0);

        $this->assertDatabaseHas('compdec_prefeituras', [
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Prefeito Real',
        ]);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ImportarPrefeiturasCommandTest
```

Expected: FAIL — `Command "cedec:importar-prefeituras" is not defined.`

- [ ] **Step 3: Criar o Command**

Criar `SDC/app/Modules/Cedec/Console/ImportarPrefeiturasCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Console;

use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Console\Command;

final class ImportarPrefeiturasCommand extends Command
{
    protected $signature = 'cedec:importar-prefeituras {--chunk=100} {--dry-run}';

    protected $description = 'Migra/atualiza compdec_prefeituras a partir do legado gestaocedec (cedec_municipio + cedec_prefeitura).';

    public function handle(PrefeituraService $service): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Executando cedec:importar-prefeituras em modo --dry-run (nenhuma escrita sera feita).'
            : 'Executando cedec:importar-prefeituras.');

        $report = $service->migrarLegado($chunk, $dryRun);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Total processado', (string) $report->total()],
                ['Inseridos', (string) $report->inseridos],
                ['Atualizados', (string) $report->atualizados],
                ['Ignorados', (string) $report->ignorados],
                ['Erros', (string) $report->erros],
            ],
        );

        foreach ($report->errosDetalhes as $erro) {
            $this->warn(sprintf('legacy_id=%s: %s', $erro['legacy_id'] ?? 'null', $erro['motivo']));
        }

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Criar o `CedecServiceProvider`**

Criar `SDC/app/Modules/Cedec/CedecServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cedec;

use App\Modules\Cedec\Console\ImportarPrefeiturasCommand;
use Illuminate\Support\ServiceProvider;

class CedecServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportarPrefeiturasCommand::class,
            ]);
        }
    }
}
```

- [ ] **Step 5: Registrar o provider em `config/app.php`**

Em `SDC/config/app.php`, no array `providers`, trocar:

```php
        App\Modules\Compdec\CompdecServiceProvider::class,
        App\Modules\Cisterna\CisternaServiceProvider::class,
    ])->toArray(),
```

por:

```php
        App\Modules\Compdec\CompdecServiceProvider::class,
        App\Modules\Cisterna\CisternaServiceProvider::class,
        App\Modules\Cedec\CedecServiceProvider::class,
    ])->toArray(),
```

- [ ] **Step 6: Limpar cache de config e rodar o teste**

```bash
docker exec newsdc_dev_app php artisan config:clear
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=ImportarPrefeiturasCommandTest
```

Expected: PASS nos 2 testes.

- [ ] **Step 7: Rodar a suite inteira do modulo Cedec**

```bash
php -d extension=pdo_pgsql vendor/bin/phpunit --filter=Cedec
```

Expected: PASS em todos os testes de `Tests\Feature\Cedec` (as 7 classes criadas
nesta fase).

- [ ] **Step 8: Lint dos arquivos tocados**

```bash
docker exec newsdc_dev_app php -l /var/www/app/Modules/Compdec/Services/PrefeituraService.php
docker exec newsdc_dev_app php -l /var/www/app/Modules/Cedec/Console/ImportarPrefeiturasCommand.php
docker exec newsdc_dev_app php -l /var/www/app/Modules/Cedec/CedecServiceProvider.php
docker exec newsdc_dev_app php -l /var/www/config/app.php
```

Expected: `No syntax errors detected` nos quatro.

- [ ] **Step 9: Commit — Tasks 6 e 7 juntas**

```bash
git add SDC/app/Modules/Compdec/Services/PrefeituraService.php \
        SDC/app/Modules/Cedec/Console/ImportarPrefeiturasCommand.php \
        SDC/app/Modules/Cedec/CedecServiceProvider.php \
        SDC/config/app.php \
        SDC/tests/Feature/Cedec/PrefeituraEtlMigracaoTest.php \
        SDC/tests/Feature/Cedec/ImportarPrefeiturasCommandTest.php
git commit -m "$(cat <<'EOF'
🐛 fix(cedec): corrige ETL de prefeituras e adiciona command de importacao

migrarLegado() consultava com_comdec, tabela sem as colunas usadas (a migracao
estourava com qualquer legado real conectado). Passa a ler cedec_municipio LEFT JOIN
cedec_prefeitura (gestaocedec_local), resolve municipio_id via Codmundv = codigo_ibge,
aplica a precedencia de campo por campo (municipio antes de prefeitura), higieniza
e-mail/telefone sujos e migra a foto do prefeito para a Media Library. Novo command
cedec:importar-prefeituras expoe a chamada.
EOF
)"
```

---

## Verificacao final da fase

- [ ] `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=Cedec` — todas as
  7 classes de teste passam.
- [ ] `docker exec newsdc_dev_app php artisan route:list --name=cedec` — sem
  saida (fase 1 nao cria rotas; isso so confirma que o autoload do modulo novo nao
  quebrou o boot da aplicacao).
- [ ] `git log --oneline -3` mostra os 3 commits desta fase (ambiente; schema+model+DTO
  +factory; ETL+command), nenhum arquivo de teste de depuracao avulso no `git status`.
- [ ] Conferir que `SDC/.env` (nao commitado) tem as 6 chaves da Task 1 antes de rodar
  o command de verdade fora dos testes automatizados:
  `docker exec newsdc_dev_app php artisan cedec:importar-prefeituras --dry-run`
  deve reportar `Inseridos`/`Atualizados` proximo de 853 (numero de municipios reais
  medido no legado) sem erro de conexao.
