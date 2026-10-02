# Acessos — Integracao direta com o Active Directory (F1–F3) — Plano de implementacao

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ligar o modulo Acessos ao Active Directory por LDAPS direto, implementando a porta `DiretorioCorporativo`: consultar conta e bloqueio, sincronizar por `objectGUID` com relatorio de divergencias, e executar desbloqueio, reset de senha (senha forte exibida uma vez), habilitar/desabilitar e exigir troca de senha, sempre por job numa fila `diretorio` consumida por um worker on-premise; a automacao de Demandas continua funcionando sobre o mesmo caminho.

**Architecture:** O web (Azure) so grava a operacao em `acessos_operacoes_ad` e o job em `acessos_fila_diretorio` na mesma transacao (fila `database` no Postgres compartilhado, P1 = B da spec). O worker on-prem (mesma imagem, `queue:work diretorio --queue=diretorio`, `DIRETORIO_DRIVER=ldap`) executa `ExecutarOperacaoDiretorioJob`: trava a operacao, resolve a conta por GUID, aplica as guardas de escopo, chama o handler da acao, grava espelho/auditoria e emite `OperacaoDiretorioConcluida`. Reset grava a senha cifrada em `acessos_entregas_senha` para leitura unica pelo operador. Sincronizacao agendada por `SincronizarDiretorioJob`, com freio de ausencia. Frontend Atomic Design com polling da operacao.

**Tech Stack:** PHP 8.3/8.4, Laravel 12, PostgreSQL, `directorytree/ldaprecord` ^3 (core) + `ext-ldap`, Spatie Permission, Inertia + Vue 3, Tailwind, PHPUnit com `DatabaseTransactions`, Docker Swarm on-prem.

**Spec:** `docs/superpowers/specs/2026-10-02-acessos-active-directory-design.md`

## Global Constraints

- Branch/worktree: `feat/acessos-active-directory`, a partir de `dev`, em `NewSDC/.worktrees/acessos-active-directory` (Task 0). Todo caminho abaixo e relativo a `SDC/` salvo indicacao; `git`, `npx` e `bash /c/tmp/teste-acessos.sh` rodam a partir de `SDC/` da worktree.
- Todo arquivo PHP novo comeca com `declare(strict_types=1);`; dependencias por construtor `private readonly`; classes de servico `final`.
- Sem emojis no codigo. Comentarios em pt-BR sem acento; textos de tela e mensagens ao usuario em pt-BR com acento.
- Commits: gitmoji + `tipo(escopo): descricao` em pt-BR, SEM trailer `Co-Authored-By`. Um commit por task, sempre com pathspec: `git add <arquivos> && git commit -m "..." -- <arquivos>`.
- Nunca `git add` em `SDC/tests/` (gitignored; testes ficam so no disco).
- Testes so contra `sdc_test`, via `bash /c/tmp/teste-acessos.sh <comando>` (nunca `sdc`, nunca `migrate:fresh`). Dados de teste com prefixo `TST-`.
- NUNCA `git stash` (pilha compartilhada entre worktrees). Para guardar trabalho, commit WIP temporario.
- Migrations: schema consolidado em `database/migrations/2026_09_23_000002_create_acessos_tables.php`; bancos ja migrados recebem `database/migrations/2026_10_02_100000_ajusta_acessos_active_directory.php` idempotente (`hasTable`/`hasColumn`/indice), que termina chamando `SincronizadorDePermissoes` (precedente `2026_09_28_100000`). Nenhuma outra migration nova.
- Proibido no codigo: `shell_exec`, `exec`, `proc_open`, PowerShell, Python, chamada HTTP a gateway de estacao, IP fixo, senha fixa. Hosts, OU, credencial e CA vem de `config/acessos.php` (env).
- A senha de reset nunca e: persistida em claro, logada, auditada, posta em payload de job, em mensagem de excecao, em prop/flash do Inertia ou em resposta que nao seja a do `POST /acessos/operacoes/{operacao}/senha`. Parametros que a carregam usam `#[\SensitiveParameter]`.
- Excecoes do adaptador nunca encadeiam a original (`previous`) e nunca expoem `getMessage()`/`getDiagnosticMessage()` do LdapRecord. Persistir so `CodigoErroDiretorio`.
- Parametros de rota novos: `{operacao}` (uuid). Conferir antes de criar rota: `grep -rn "Route::model\|Route::bind" routes app/Providers` (hoje: anexo, ata, caminhao, cronoCaminhao, cronograma, empreendimento, equipe, historico, municipio, orgao, prestador, protocolo, representante, solicitacao, viagem).
- Builds de frontend de tasks paralelas: `npx vite build --outDir C:/tmp/vite-<task> --emptyOutDir`.
- Tasks paralelas nunca editam o mesmo arquivo; quando um arquivo e de mais de uma task, a tabela de ordem diz quem edita e em que onda. `AcessosServiceProvider.php` e editado por no maximo uma task por onda.
- Logs de depuracao criados durante o trabalho saem antes do commit.

## Review Focus

1. Duplo clique em "Desbloquear" (duas requisicoes simultaneas): uma operacao, uma chamada ao AD, e a segunda requisicao recebe a mesma operacao (indice unico parcial + releitura). Teste `test_duplo_pedido_vira_uma_operacao` (Task 5).
2. Rollback da transacao do pedido leva junto a linha do job: nenhum job orfao, nenhuma operacao sem job. Teste `test_operacao_e_job_sao_atomicos` (Task 5).
3. Reset com falha na gravacao do banco depois do modify no AD: o retry redefine de novo e a senha entregue e a que vale no fake. Teste `test_retry_do_reset_entrega_a_ultima_senha` (Task 16).
4. A string exata da senha nao aparece em nenhuma tabela, em `failed_jobs`, em `storage/logs/*.log` nem nas props Inertia de `acessos.show`/`demandas.show`. Teste `test_senha_nao_vaza_para_nenhum_destino_persistente` (Task 16).
5. Sincronizacao com SearchBase errada (fake devolve zero contas, ou 50% dos cadastros sem par): rodada `abortada`, nenhum espelho alterado, nenhum `status` local alterado. Teste `test_freio_de_ausencia_aborta_sem_aplicar` (Task 10).
6. Cadastro com o mesmo nome de uma conta do AD mas login diferente nao e vinculado (nunca por nome). Teste `test_nunca_casa_por_nome` (Task 10).
7. Operador tentando agir sobre a propria conta, por `user_id`, por `cpf` e pelo login de um cadastro dele, pela tela de Acessos e pela automacao de Demandas: 422, nenhuma operacao criada. Teste `test_ninguem_age_sobre_a_propria_conta` (Task 5) e `test_automacao_na_propria_conta_e_recusada` (Task 6).
8. Login com metacaracteres de filtro LDAP (`*)(objectClass=*`) e de DN (`,` `+` `"` `\`): filtro escapado, nenhuma conta extra devolvida. Teste `test_login_com_metacaracteres_e_escapado` (Task 4).

---

## Mapa de arquivos

**Criar** (tudo sob `app/Modules/Acessos/` salvo indicacao)
- `database/migrations/2026_10_02_100000_ajusta_acessos_active_directory.php`
- `config/acessos.php`
- `Enums/{AcaoDiretorio,EstadoOperacaoAd,StatusAd,CodigoErroDiretorio,TipoDivergenciaAd,OrigemOperacaoAd,EstadoSincronizacaoAd}.php`
- `Models/{OperacaoAd,EntregaSenha,SincronizacaoAd,DivergenciaAd}.php`
- `DTOs/{ContaDiretorio,ReferenciaConta,ResultadoOperacao}.php`
- `Exceptions/{DiretorioIndisponivel,DiretorioRecusou,OperacaoDiretorioProibida}.php`
- `Infrastructure/{LdapDiretorioCorporativo,FabricaConexaoLdap,TradutorErroLdap,FakeDiretorioCorporativo,DiretorioDesligado}.php`
- `Services/{SolicitarOperacaoDiretorio,GuardaDeAlvo,AplicaEspelhoAd,DonoDaConta,GeradorSenhaForte,CofreEntregaSenha,SincronizadorDiretorio,SolicitarSincronizacao}.php`
- `Services/Acoes/{HandlerAcaoDiretorio,ConsultarConta,DesbloquearConta,HabilitarConta,DesabilitarConta,ExigirTrocaSenha,RedefinirSenhaConta}.php`
- `Jobs/{ExecutarOperacaoDiretorioJob,SincronizarDiretorioJob}.php`
- `Events/OperacaoDiretorioConcluida.php`
- `Console/{DiagnosticoDiretorioCommand,SincronizarDiretorioCommand,LimparEntregasSenhaCommand}.php`
- `Controllers/{OperacaoDiretorioController,EntregaSenhaController,DivergenciaDiretorioController}.php`
- `Requests/{SolicitarOperacaoDiretorioRequest,SincronizarDiretorioRequest}.php`
- `Policies/OperacaoAdPolicy.php`
- `Queries/DivergenciasQuery.php`
- `Support/ApresentacaoOperacaoAd.php`
- `app/Modules/Demandas/Listeners/RegistraResultadoAutomacao.php`
- `docker/jenkins/stack.diretorio.onpremise.yml`, `docker/jenkins/diretorio.env.example`, `docker/jenkins/scripts/diretorio-pg-role.sql`
- Frontend: `resources/js/Support/diretorio.js`, `resources/js/Composables/acessos/useOperacaoDiretorio.js`, `resources/js/Components/Atoms/Acessos/{StatusAdBadge,EstadoOperacaoBadge}.vue`, `resources/js/Components/Organisms/Acessos/Show/{AcessoDiretorioCard,AcessoOperacoesCard}.vue`, `resources/js/Components/Organisms/Acessos/SenhaTemporariaModal.vue`, `resources/js/Components/Organisms/Acessos/Divergencias/{SincronizacaoResumoCard,DivergenciasLista}.vue`, `resources/js/Pages/Acessos/Divergencias.vue`, `resources/js/Templates/Acessos/AcessosDivergenciasTemplate.vue`
- Testes (nao versionados): `tests/Feature/Acessos/**`, `tests/Unit/Acessos/**`, `tests/Feature/Acessos/Concerns/CriaAcessos.php`

**Modificar**
- `database/migrations/2026_09_23_000002_create_acessos_tables.php`
- `AcessosServiceProvider.php`, `Contracts/DiretorioCorporativo.php`, `Models/CadastroAcesso.php`, `Controllers/CadastroAcessoController.php`
- `app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`, `app/Modules/Demandas/DemandasServiceProvider.php`, `app/Modules/Demandas/Controllers/DemandaController.php`
- `app/Exceptions/Handler.php` (`dontFlash`)
- `config/permissions.php`, `config/queue.php`, `config/services.php`, `routes/modules/acessos.php`, `routes/console.php`, `composer.json`, `composer.lock`, `.env.example`
- `docker/swoole/Dockerfile`, `docker/Dockerfile.queue`, `docker/compose.dev.yml`, `docker/compose.homolog.yml`, `docker/jenkins/Jenkinsfile.onprem`, `Justfile`
- Frontend: `resources/js/Support/acessos.js`, `resources/js/Templates/Acessos/{AcessoShowTemplate,AcessosIndexTemplate}.vue`, `resources/js/Pages/Acessos/{Index,Show}.vue`, `resources/js/Components/Organisms/Acessos/{AcessosLista,AcessosFiltersSection}.vue`, `resources/js/Components/Organisms/Acessos/Show/AcessoSituacaoCard.vue`, `resources/js/Components/Organisms/Demandas/Show/DemandaAutomacaoButton.vue`, `resources/js/Templates/Demandas/DemandasShowTemplate.vue`, `resources/js/Components/Sidebar.vue`

**Remover**
- `app/Modules/Acessos/Infrastructure/HttpDiretorioCorporativo.php`
- `app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`

## Ordem e paralelismo

| Fase | Tasks | Paralelismo |
|---|---|---|
| 0 — Ambiente e F0 | 0, 0.1 | 0 sem commit; 0.1 e checklist da TI, corre em paralelo com as fases 1-3 e so bloqueia a Task 21. |
| 1 — Fundacao (F1) | 1-8 | Onda A: **1, 2, 3 em paralelo** (schema/permissoes; porta/config/fakes; dependencia/imagem). Onda B: **4 e 5 em paralelo** (4 depende de 2 e 3; 5 depende de 1 e 2; so a 4 edita o provider). Onda C: **6 e 7 em paralelo** (6 so Demandas; 7 edita o provider de Acessos). 8 fecha. |
| 2 — Leitura (F2) | 9-14 | 9 -> 10 -> 11 sequenciais (todas editam rotas ou provider). Onda: **12 e 13 em paralelo**, depois de o controlador rodar o Step 1 da Task 12 (cria `Support/diretorio.js` e `useOperacaoDiretorio.js`, que a 13 importa). 14 fecha. |
| 3 — Acoes (F3) | 15-19 | 15 -> 16 sequenciais (rotas, provider, job). Onda: **17 e 18 em paralelo**, depois de o controlador rodar o Step 1 da Task 17 (cria `SenhaTemporariaModal.vue`, que a 18 importa). 19 fecha. |
| 4 — Implantacao e verificacao | 20-23 | 20 (arquivos de infra) e 21 (verificacao em homolog) em sequencia; 22 (smoke no AD real) depende da Task 0.1; 23 fecha. |

---

## FASE 0 — Ambiente e pre-requisitos

### Task 0: Worktree, comando de teste e baseline

Sem commit.

**Files:** nenhum versionado.

- [ ] **Step 1: Criar a worktree a partir do `dev`**

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC
git fetch origin dev
git worktree list
git worktree add -b feat/acessos-active-directory .worktrees/acessos-active-directory origin/dev
cd .worktrees/acessos-active-directory && git branch --show-current
```
Expected: `feat/acessos-active-directory`. Copiar para a worktree os dois documentos desta entrega se ainda nao estiverem no `dev` (`git checkout <sha-do-commit-dos-docs> -- docs/superpowers/specs/2026-10-02-acessos-active-directory-design.md docs/superpowers/plans/2026-10-02-acessos-active-directory.md`).

- [ ] **Step 2: `.env`, runtime, `public/build` e vendor**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/acessos-active-directory/SDC
MAIN=/c/Users/x24679188/Documents/Github/NewSDC/SDC
cp "$MAIN/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/views" "$WT/storage/framework/testing" "$WT/storage/framework/sessions" "$WT/storage/logs" "$WT/storage/app"
cp -r "$MAIN/public/build" "$WT/public/build"
docker run --rm -v "$WT:/app" -w /app -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_PROCESS_TIMEOUT=0 \
  newsdc-swoole-dev:latest composer install --ignore-platform-reqs > /c/tmp/composer-acessos.log 2>&1
ls "$WT/vendor/bin/phpunit"
```
Expected: o arquivo existe; senao ler `/c/tmp/composer-acessos.log`.

- [ ] **Step 3: Comando de teste**

Salvar em `/c/tmp/teste-acessos.sh` (fora do repo). `IMAGEM` permite trocar para a imagem com `ext-ldap` gerada na Task 3:

```bash
#!/usr/bin/env bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/acessos-active-directory/SDC
IMAGEM=${IMAGEM:-newsdc-swoole-dev:latest}
APP_KEY=$(grep '^APP_KEY=' "$WT/.env" | cut -d= -f2-)
MSYS_NO_PATHCONV=1 docker run --rm --network newsdc-dev_default -v "$WT:/app" -w /app \
  -e APP_ENV=testing -e APP_KEY="$APP_KEY" -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_test -e DB_USERNAME=sdc -e DB_PASSWORD=secret -e QUEUE_CONNECTION=sync \
  -e BROADCAST_CONNECTION=null -e RANKING_HABILITADO=false \
  -e DIRETORIO_DRIVER=fake -e DIRETORIO_SEARCH_BASE="OU=SDC,DC=teste,DC=local" -e DIRETORIO_BASE_DN="DC=teste,DC=local" \
  -e DIRETORIO_CHAVE_ENTREGA="base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=" -e DIRETORIO_BACKOFF=0 \
  "$IMAGEM" "$@"
```

- [ ] **Step 4: Baseline**

```bash
bash /c/tmp/teste-acessos.sh php artisan migrate --force
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Demandas
```
Expected: migrate sem erro; suite Demandas verde (inclusive `DemandaAutomacaoTest`). Anotar como baseline.

---

### Task 0.1: Checklist F0 para a TI/Prodemge (sem codigo)

Sem commit. Abrir o pedido a TI com a secao 10.2 da spec; acompanhar ate todos os itens voltarem. Bloqueia so a Task 22.

- [ ] **Step 1: Pedido enviado** com: conta de servico + lista exata de direitos na OU (spec 10.2 item 2), certificado/CA do LDAPS, FQDNs dos DCs e PDC emulator, StartTLS ou 636, firewall 636 e 5432 a partir de `10.160.131.50`, papel Postgres `sdc_diretorio_worker` (script da Task 20), politica de senha, conta de teste `tst-sdc-ad01`.
- [ ] **Step 2: Conferir da VM, quando liberado** (na VM, fora do container primeiro):

```bash
getent hosts <dc1-fqdn>
dig +short SRV _ldap._tcp.dc._msdcs.<dominio>
openssl s_client -connect <dc1-fqdn>:636 -CAfile /opt/sdc/diretorio/ca.pem -verify_return_error </dev/null | grep -E "Verify return code|subject="
LDAPTLS_CACERT=/opt/sdc/diretorio/ca.pem ldapwhoami -H ldaps://<dc1-fqdn> -x -D "<upn-da-conta>" -W
LDAPTLS_CACERT=/opt/sdc/diretorio/ca.pem ldapsearch -H ldaps://<dc1-fqdn> -x -D "<upn-da-conta>" -W -b "<search-base>" -s base dn
nc -vz newsdc.postgres.database.azure.com 5432
```
Expected: nomes resolvem pelos DNS corporativos; `Verify return code: 0 (ok)`; `ldapwhoami` devolve `u:<DOMINIO>\svc-...`; o `ldapsearch` devolve o DN da OU; a porta 5432 abre.

---

## FASE 1 — Fundacao (F1)

Entrega: schema, permissoes, porta ampliada com DTOs, adaptadores (desligado, fake, LDAP), fila `diretorio` no Postgres, caso de uso de solicitar, job com maquina de estados e guardas, Demandas migrado para o novo caminho, comando de diagnostico. Nenhuma tela nova; a automacao de Demandas continua funcionando (agora pelo fake em dev).

### Task 1: Schema, models, enums e permissoes (onda A)

**Files:**
- Modify: `database/migrations/2026_09_23_000002_create_acessos_tables.php`
- Create: `database/migrations/2026_10_02_100000_ajusta_acessos_active_directory.php`
- Create: `app/Modules/Acessos/Enums/{AcaoDiretorio,EstadoOperacaoAd,StatusAd,TipoDivergenciaAd,OrigemOperacaoAd,EstadoSincronizacaoAd}.php` (`CodigoErroDiretorio` e da Task 2)
- Create: `app/Modules/Acessos/Models/{OperacaoAd,EntregaSenha,SincronizacaoAd,DivergenciaAd}.php`
- Modify: `app/Modules/Acessos/Models/CadastroAcesso.php`
- Modify: `config/permissions.php` (bloco `ACESSOS` ~linha 626; papeis `manager` ~707, `analyst` ~976, `operator` ~1151)
- Test: `tests/Feature/Acessos/SchemaDiretorioTest.php`, `tests/Feature/Acessos/PermissoesDiretorioTest.php`, `tests/Feature/Acessos/Concerns/CriaAcessos.php`

**Interfaces:**
- Produces:
  - Colunas novas em `acessos_cadastros` e tabelas `acessos_operacoes_ad`, `acessos_entregas_senha`, `acessos_sincronizacoes_ad`, `acessos_divergencias_ad`, `acessos_fila_diretorio` exatamente como a spec secao 5; indice `acessos_operacoes_ad_em_voo_unique` (parcial, `lower(login_ad), acao`, `estado in ('solicitado','enviado')`); indice `acessos_cadastros_status_ad_index`.
  - `AcaoDiretorio` (`consultar|desbloquear|habilitar|desabilitar|exigir_troca_senha|redefinir_senha`): `label()`, `permissao(): string` (`acessos.diretorio.view|manage|reset`), `escreve(): bool`, `exigeMotivo(): bool`, `handler(): class-string` (classes da `Services/Acoes`, criadas nas Tasks 5, 15 e 16; o metodo so devolve o nome, sem resolver).
  - `EstadoOperacaoAd` (`solicitado|enviado|confirmado|falhou`): `final(): bool`, `podeIrPara(self $novo): bool`, `label()`.
  - `StatusAd` (`desconhecido|ativa|desabilitada|nao_encontrada|fora_do_escopo|protegida`), `label()`.
  - `TipoDivergenciaAd` (spec 5.4), `OrigemOperacaoAd` (`acessos|demanda`), `EstadoSincronizacaoAd` (`solicitada|executando|concluida|abortada|falhou`).
  - Models da spec 5.6; `CadastroAcesso::operacoesAd()`, casts das colunas-espelho. O cast de `OperacaoAd::codigo_erro` aponta para `CodigoErroDiretorio` (Task 2, mesma onda); os testes da Task 1 nao leem esse campo.
  - Slugs `acessos.diretorio.view|manage|reset` em `config('permissions.modules.ACESSOS.Diretorio')`; `Cadastros.directory` removido; papeis conforme spec 7.2.
  - Trait de teste `CriaAcessos`: `usuarioCom(array $permissoes): User`, `cadastro(array $atributos = []): CadastroAcesso` (login `tst.<uniqid curto>`, cpf valido aleatorio), `conta(array $atributos = []): ContaDiretorio` (DN dentro de `OU=SDC,DC=teste,DC=local`), `diretorioFake(): FakeDiretorioCorporativo` (binda o singleton), `processarFilaDiretorio(): void` (`Artisan::call('queue:work', ['connection' => 'diretorio', '--queue' => 'diretorio', '--stop-when-empty' => true, '--tries' => 3])`). Os metodos que usam classes das Tasks 2/5 sao adicionados ao trait nessas tasks.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Acessos/SchemaDiretorioTest.php`:
- `test_cadastro_tem_colunas_espelho_do_ad`: `Schema::hasColumns('acessos_cadastros', ['object_guid','dn_ad','conta_ativa_ad','bloqueada_ad','troca_senha_pendente_ad','ad_sincronizado_em'])`.
- `test_tabelas_novas_existem`: as cinco tabelas da spec 5.2-5.5.
- `test_object_guid_e_unico`: dois cadastros com o mesmo GUID -> `UniqueConstraintViolationException`.
- `test_indice_parcial_impede_duas_operacoes_em_voo`: duas `OperacaoAd` `solicitado` para `TST.Login`/`tst.login` + `desbloquear` -> violacao; a segunda com estado `confirmado` passa.
- `test_estado_so_avanca`: `EstadoOperacaoAd::CONFIRMADO->podeIrPara(EstadoOperacaoAd::ENVIADO)` false; `ENVIADO->podeIrPara(ENVIADO)` true; `SOLICITADO->podeIrPara(FALHOU)` true.
- `test_companion_e_idempotente`: `Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_02_100000_ajusta_acessos_active_directory.php', '--force' => true])` duas vezes sem erro (rodar o `up()` da classe direto: `(require base_path(...))->up()` duas vezes).

`tests/Feature/Acessos/PermissoesDiretorioTest.php`:
- `test_modulo_tem_grupo_diretorio`: `config('permissions.modules.ACESSOS.Diretorio') === ['view' => 'acessos.diretorio.view', 'manage' => 'acessos.diretorio.manage', 'reset' => 'acessos.diretorio.reset']` e `Cadastros` sem `directory`.
- `test_papeis_recebem_os_slugs_da_spec`: manager contem os tres; analyst contem view e manage e nao reset; operator contem view e nao manage.
- `test_sincronizador_cria_os_slugs`: depois de `app(SincronizadorDePermissoes::class)->...` (mesma chamada usada pelo companion de 2026_09_28), `Permission::where('name', 'acessos.diretorio.reset')->exists()`.

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/SchemaDiretorioTest.php tests/Feature/Acessos/PermissoesDiretorioTest.php
```
Expected: FAIL (colunas, tabelas, enums e grupo `Diretorio` inexistentes).

- [ ] **Step 3: Implementar**

Migration principal: acrescentar as colunas em `Schema::create('acessos_cadastros', ...)` (depois de `status_ad`, com `->index()` em `status_ad`) e os `Schema::create` novos depois de `acessos_auditoria`; o indice parcial com `DB::statement('CREATE UNIQUE INDEX acessos_operacoes_ad_em_voo_unique ON acessos_operacoes_ad (lower(login_ad), acao) WHERE estado IN (\'solicitado\', \'enviado\')')`. `down()` derruba na ordem inversa.

Companion (no-op em instalacao limpa):

```php
public function up(): void
{
    Schema::table('acessos_cadastros', function (Blueprint $table): void {
        foreach (self::COLUNAS_ESPELHO as $coluna => $definir) {
            if (! Schema::hasColumn('acessos_cadastros', $coluna)) {
                $definir($table);
            }
        }
    });
    if (! Schema::hasTable('acessos_operacoes_ad')) { /* mesmo create da principal */ }
    // ... entregas, sincronizacoes, divergencias, fila
    DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS acessos_operacoes_ad_em_voo_unique ON ...');
    DB::statement('CREATE INDEX IF NOT EXISTS acessos_cadastros_status_ad_index ON acessos_cadastros (status_ad)');
    app(SincronizadorDePermissoes::class)->/* mesma chamada do companion 2026_09_28_100000 */;
}
```

Para nao duplicar os `create` entre a principal e o companion (DRY), extrair cada definicao para um metodo estatico de uma classe de apoio `database/migrations/support/EsquemaAcessosDiretorio.php`? Nao: migrations do repo sao autocontidas e o precedente (`2026_09_28_100000`) repete o create. Repetir, com comentario apontando a principal como fonte.

Enums com os metodos da secao Interfaces; `CodigoErroDiretorio::mensagem()` com os textos da spec 9.1.

`config/permissions.php`: grupo `'Diretorio' => ['view' => 'acessos.diretorio.view', 'manage' => 'acessos.diretorio.manage', 'reset' => 'acessos.diretorio.reset']` no bloco `ACESSOS`; remover `'directory'` de `Cadastros`; acrescentar aos papeis conforme a spec 7.2.

- [ ] **Step 4: Run tests to verify they pass**

```bash
bash /c/tmp/teste-acessos.sh php artisan migrate --force
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/SchemaDiretorioTest.php tests/Feature/Acessos/PermissoesDiretorioTest.php
bash /c/tmp/teste-acessos.sh php artisan permissions:audit
```
Expected: PASS; o audit nao aponta slug `acessos.diretorio.*` faltando.

- [ ] **Step 5: Commit**

```bash
FILES="database/migrations/2026_09_23_000002_create_acessos_tables.php database/migrations/2026_10_02_100000_ajusta_acessos_active_directory.php app/Modules/Acessos/Enums app/Modules/Acessos/Models config/permissions.php"
git add $FILES && git commit -m "🗃️ db(acessos): espelho do AD, operacoes, entregas de senha e sincronizacao" -- $FILES
```

---

### Task 2: Porta ampliada, DTOs, excecoes, adaptadores desligado e fake, configuracao (onda A)

**Files:**
- Modify: `app/Modules/Acessos/Contracts/DiretorioCorporativo.php`
- Create: `app/Modules/Acessos/DTOs/{ContaDiretorio,ReferenciaConta,ResultadoOperacao}.php`
- Create: `app/Modules/Acessos/Enums/CodigoErroDiretorio.php`
- Create: `app/Modules/Acessos/Exceptions/{DiretorioIndisponivel,DiretorioRecusou,OperacaoDiretorioProibida}.php`
- Create: `app/Modules/Acessos/Infrastructure/{DiretorioDesligado,FakeDiretorioCorporativo}.php`
- Delete: `app/Modules/Acessos/Infrastructure/HttpDiretorioCorporativo.php`
- Create: `config/acessos.php`
- Modify: `config/services.php` (remove `corporate_directory`), `.env.example` (bloco `DIRETORIO_*` da spec 8, sem valores reais)
- Modify: `app/Modules/Acessos/AcessosServiceProvider.php`
- Test: `tests/Unit/Acessos/FakeDiretorioCorporativoTest.php`, `tests/Unit/Acessos/ConfiguracaoDiretorioTest.php`

**Interfaces:**
- Produces:
  - `Enums/CodigoErroDiretorio.php` (este enum e da Task 2, nao da Task 1, porque as excecoes dependem dele): casos da spec 9.1, `transitorio(): bool` (so `indisponivel` e `timeout`), `mensagem(): string` (textos da tela, com acento).
  - Interface da spec 4.1 (exatamente).
  - `ContaDiretorio` (`final readonly`, campos da spec 4.2) com `referencia(): ReferenciaConta` e `static fromArray(array): self` (usado pelo fake e pelos testes).
  - `ResultadoOperacao` (`final readonly`): `contaDepois`, `efetivada`, `?string senhaParaEntrega = null` (so o handler de reset preenche, via `comSenhaParaEntrega(#[\SensitiveParameter] string $senha): self`); `__debugInfo()` e `__serialize()` omitem/recusam `senhaParaEntrega` (o objeto nunca e serializado nem aparece em dump).
  - `DiretorioIndisponivel extends RuntimeException` com `codigo(): CodigoErroDiretorio` (`indisponivel|timeout|config_ausente|certificado|credencial_servico`) e mensagem fixa; `DiretorioRecusou extends RuntimeException` com `codigo()`; nenhum dos dois aceita `previous` no construtor.
  - `OperacaoDiretorioProibida extends ValidationException` com fabricas `propriaConta()`, `contaProtegida()`, `semLogin()`, `motivoObrigatorio()`, `loginInvalido()` (chave de erro `diretorio`).
  - `FakeDiretorioCorporativo` com a API da spec 4.4; persistencia em JSON quando `config('acessos.diretorio.fake.arquivo')`.
  - `config('acessos.diretorio')` com as chaves da spec 8 (`driver`, `hosts` (array), `dominio`, `host_preferencial`, `seguranca`, `porta`, `base_dn`, `search_base`, `usuario`, `senha` (resolve `_FILE`), `ca_cert`, `timeout_conexao`, `timeout_operacao`, `contas_protegidas` (array, sempre inclui o login do `usuario`), `fila.conexao`, `fila.nome`, `tentativas`, `backoff` (array de int), `senha_tamanho`, `entrega_ttl_minutos`, `chave_entrega` (resolve `_FILE`), `sincronizar.agendar|tamanho_pagina|limiar_ausencia|max_sem_cadastro|retencao_dias`, `fake.arquivo`).
  - Binding: `DiretorioCorporativo` singleton por `match (config('acessos.diretorio.driver'))`: `fake` -> `FakeDiretorioCorporativo`, default -> `DiretorioDesligado`. O braco `ldap` entra na Task 4.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Acessos/FakeDiretorioCorporativoTest.php` (estende `Tests\TestCase`):
- `test_consultar_devolve_conta_semeada_sem_diferenca_de_caixa`.
- `test_desbloquear_conta_bloqueada_efetiva_e_nao_bloqueada_nao_efetiva`.
- `test_desabilitar_preserva_os_outros_bits` (semeia UAC `0x10200`; depois de desabilitar o fake guarda `0x10202`).
- `test_redefinir_senha_grava_senha_e_troca_pendente` (`ultimaSenha()` igual; `trocaSenhaPendente` true).
- `test_falhar_com_indisponivel_lanca_transitoria` e `test_falhar_com_codigo_lanca_recusa`.
- `test_conta_fora_da_search_base_nao_aparece_em_consultar_mas_aparece_por_guid`.
- `test_estado_persiste_no_arquivo_entre_instancias` (`DIRETORIO_FAKE_ARQUIVO` em `storage/framework/testing/diretorio-fake-<uniqid>.json`).

`tests/Unit/Acessos/ConfiguracaoDiretorioTest.php`:
- `test_driver_padrao_e_desligado_e_lanca_config_ausente` (`config(['acessos.diretorio.driver' => null])`, `app()->forgetInstance(...)`, chamada -> `DiretorioIndisponivel` com `codigo() === CodigoErroDiretorio::CONFIG_AUSENTE`).
- `test_senha_file_tem_precedencia` (arquivo temporario).
- `test_conta_de_servico_sempre_protegida`.
- `test_codigos_transitorios`: so `indisponivel` e `timeout`; todo caso tem `mensagem()` nao vazia.
- `test_excecoes_nao_carregam_previous` (reflexao: construtor sem parametro `previous`; `getPrevious()` null).
- `test_http_diretorio_corporativo_nao_existe_mais` (`class_exists(...)` false) e `config('services.corporate_directory')` null.

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Unit/Acessos
```
Expected: FAIL.

- [ ] **Step 3: Implementar**

Interface: copiar da spec 4.1. `config/acessos.php`, resolucao do segredo por arquivo:

```php
$segredo = static function (string $env): ?string {
    $arquivo = env($env.'_FILE');
    if (is_string($arquivo) && $arquivo !== '' && is_readable($arquivo)) {
        return trim((string) file_get_contents($arquivo));
    }
    $valor = env($env);

    return is_string($valor) && $valor !== '' ? $valor : null;
};
$lista = static fn (?string $v): array => array_values(array_filter(array_map('trim', explode(',', (string) $v))));
```

Provider:

```php
public function register(): void
{
    $this->app->singleton(DiretorioCorporativo::class, fn ($app) => match (config('acessos.diretorio.driver')) {
        'fake' => $app->make(FakeDiretorioCorporativo::class),
        default => new DiretorioDesligado(),
    });
}
```

O fake guarda contas por GUID num array, aplica a mesma regra de escopo (`str_ends_with(mb_strtolower($dn), ','.mb_strtolower($searchBase))`), registra `chamadas`, e com arquivo configurado le/grava o JSON com `flock(LOCK_EX)` a cada operacao.

- [ ] **Step 4: Run tests to verify they pass**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Unit/Acessos
grep -rn "HttpDiretorioCorporativo\|corporate_directory" app config routes
```
Expected: PASS; o grep nao encontra nada. O job de Demandas ainda chama os metodos antigos da porta: `DemandaAutomacaoTest` fica vermelho ate a Task 6 (esperado; registrar no relatorio da task).

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Enums/CodigoErroDiretorio.php app/Modules/Acessos/Contracts app/Modules/Acessos/DTOs app/Modules/Acessos/Exceptions app/Modules/Acessos/Infrastructure app/Modules/Acessos/AcessosServiceProvider.php config/acessos.php config/services.php .env.example"
git add -A $FILES && git commit -m "♻️ refactor(acessos): porta do diretorio com DTOs e adaptadores desligado e fake" -- $FILES
```

---

### Task 3: LdapRecord, `ext-ldap` na imagem e worker `diretorio` em dev/homolog (onda A)

**Files:**
- Modify: `composer.json`, `composer.lock`
- Modify: `docker/swoole/Dockerfile`, `docker/Dockerfile.queue`
- Modify: `docker/compose.dev.yml`, `docker/compose.homolog.yml`
- Test: nenhum PHPUnit; verificacao por comando.

**Interfaces:**
- Produces: pacote `directorytree/ldaprecord` ^3; `ext-ldap` carregada nas duas imagens; imagem local `newsdc-swoole-dev:ldap`; servico `queue_diretorio` em dev e homolog (`DIRETORIO_DRIVER=fake`, `DIRETORIO_FAKE_ARQUIVO=/var/www/storage/app/diretorio-fake.json`, comando `php artisan queue:work diretorio --queue=diretorio --sleep=3 --tries=3 --timeout=60`), e as mesmas duas envs no servico `app` (para semear o fake). Envs do fake num bloco ancorado (`x-diretorio-env: &diretorio-env`, como o `x-ranking-env` do homolog) usado por `app`, `queue_diretorio` e `scheduler`: `DIRETORIO_DRIVER: fake`, `DIRETORIO_SEARCH_BASE: OU=SDC,DC=homolog,DC=local`, `DIRETORIO_BASE_DN: DC=homolog,DC=local`, `DIRETORIO_CHAVE_ENTREGA: ${DIRETORIO_CHAVE_ENTREGA}` (gerada uma vez com `php -r 'echo "base64:".base64_encode(random_bytes(32));'` e guardada em `docker/.env`, nunca commitada), `DIRETORIO_FAKE_ARQUIVO`. O `queue_diretorio` usa a mesma imagem do `app`, `command` sobrescrito (sem `/start.sh`), `CACHE_STORE: array`, `QUEUE_CONNECTION: sync`, `BROADCAST_CONNECTION: "null"`, healthcheck `pgrep -f "queue:work diretorio"`.

- [ ] **Step 1: Dependencia**

```bash
docker run --rm -v "$PWD:/app" -w /app -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_PROCESS_TIMEOUT=0 \
  newsdc-swoole-dev:latest composer require directorytree/ldaprecord:^3 --ignore-platform-reqs
grep -n '"directorytree/ldaprecord"' composer.json
```
Expected: entrada em `require`. Conferir na doc (Context7 `/websites/ldaprecord_core_v3`) a versao estavel e o requisito de PHP antes de fixar.

- [ ] **Step 2: `ext-ldap` nas imagens**

Nas duas Dockerfiles: `openldap-dev` em `.build-deps`, `libldap` no `apk add --no-cache` de runtime, e `ldap` na lista do `docker-php-ext-install`. Conferir tambem `zend.exception_ignore_args=On` no ini de producao da imagem (`php -i | grep exception_ignore_args`); se estiver `Off`, acrescentar ao ini de producao com comentario apontando a spec (risco 14.3).

- [ ] **Step 3: Build da imagem de teste (destacado; passa de 10 min)**

```bash
(docker build -f docker/swoole/Dockerfile -t newsdc-swoole-dev:ldap . > /c/tmp/build-ldap.log 2>&1; echo "exit=$?" >> /c/tmp/build-ldap.log) &
```
Acompanhar com `tail -3 /c/tmp/build-ldap.log` ate aparecer `exit=0`. Se a imagem dev usar outro Dockerfile (`docker/compose.dev.yml`, chave `build`), usar o mesmo que ele.

- [ ] **Step 4: Verificar**

```bash
docker run --rm newsdc-swoole-dev:ldap php -m | grep -x ldap
docker run --rm newsdc-swoole-dev:ldap php -r 'var_dump(defined("LDAP_OPT_X_TLS_CACERTFILE"), ini_get("zend.exception_ignore_args"));'
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php -r 'require "vendor/autoload.php"; var_dump(class_exists(LdapRecord\Connection::class));'
docker compose -f docker/compose.dev.yml config --services | grep -x queue_diretorio
docker compose -f docker/compose.homolog.yml config --services | grep -x queue_diretorio
```
Expected: `ldap`; `bool(true)` e `"1"`; `bool(true)`; os dois servicos listados. A partir daqui as Tasks 4, 7 e seguintes usam `IMAGEM=newsdc-swoole-dev:ldap`.

- [ ] **Step 5: Commit**

```bash
FILES="composer.json composer.lock docker/swoole/Dockerfile docker/Dockerfile.queue docker/compose.dev.yml docker/compose.homolog.yml"
git add $FILES && git commit -m "🐳 docker(acessos): ext-ldap, LdapRecord e worker da fila diretorio em dev e homolog" -- $FILES
```

---

### Task 4: Adaptador `LdapDiretorioCorporativo` (onda B, depois de 2 e 3)

**Files:**
- Create: `app/Modules/Acessos/Infrastructure/{LdapDiretorioCorporativo,FabricaConexaoLdap,TradutorErroLdap}.php`
- Modify: `app/Modules/Acessos/AcessosServiceProvider.php` (braco `ldap` do binding)
- Test: `tests/Unit/Acessos/LdapDiretorioCorporativoTest.php`, `tests/Unit/Acessos/FabricaConexaoLdapTest.php`, `tests/Unit/Acessos/TradutorErroLdapTest.php`

**Interfaces:**
- Consumes: porta, DTOs, excecoes, `CodigoErroDiretorio`, `config('acessos.diretorio')` (Task 2); LdapRecord (Task 3).
- Produces:
  - `FabricaConexaoLdap::criar(): LdapRecord\Connection` (config da spec 4.3) e `hosts(): list<string>` (lista de config ou SRV via `resolverSrv(string $registro): list<string>`, metodo `protected` para o teste sobrescrever; preferencial primeiro; recusa entrada que seja IPv4/IPv6 com `DiretorioIndisponivel(config_ausente)`).
  - `TradutorErroLdap::traduzir(LdapRecord\LdapRecordException $e): DiretorioIndisponivel|DiretorioRecusou` (tabela da spec 9.1; certificado detectado por `ldap_get_option(LDAP_OPT_DIAGNOSTIC_MESSAGE)` com "certificate"/"TLS" so em memoria).
  - `LdapDiretorioCorporativo implements DiretorioCorporativo` com as regras da spec 4.3; constantes `ATRIBUTOS` (lista fechada), `FILTRO_CONTA`, `BIT_DESABILITADA = 0x2`, `BIT_BLOQUEADA = 0x10`; metodo privado `paraConta(array $entrada): ContaDiretorio`; todo metodo publico envolvido em `try { ... } catch (LdapRecordException $e) { throw $this->tradutor->traduzir($e); }` (sem `previous`).

- [ ] **Step 1: Write the failing tests** (rodar com `IMAGEM=newsdc-swoole-dev:ldap`)

`tests/Unit/Acessos/LdapDiretorioCorporativoTest.php`, com `LdapRecord\Testing\DirectoryFake::setup()` e `LdapFake::operation(...)`:
- `test_consultar_monta_filtro_com_login_escapado_e_atributos_fechados`: `expect(LdapFake::operation('search')->once()->with(fn ($base, $filtro, $atributos) => $base === SEARCH_BASE && str_contains($filtro, '(samaccountname=tst.ana)') && $atributos === LdapDiretorioCorporativo::ATRIBUTOS)->andReturn([...]))`.
- `test_login_com_metacaracteres_e_escapado`: login `*)(objectClass=*` gera `\2a\29\28objectClass=\2a` no filtro; com o fake devolvendo vazio, `consultar` devolve null.
- `test_buscar_por_guid_usa_hex_escapado_na_base_do_dominio`.
- `test_conta_mapeia_bits`: UAC `514` -> `ativa=false`; `msDS-User-Account-Control-Computed` `16` -> `bloqueada=true`; `pwdLastSet` `0` -> `trocaSenhaPendente=true`; `adminCount` `1` -> `protegida=true`.
- `test_desabilitar_preserva_bits`: le `0x10200`, grava `0x10202` (operacao `modifyBatch`/`modify` com `userAccountControl`).
- `test_redefinir_senha_faz_um_modify_com_unicodepwd_e_pwdlastset`: um unico `modifyBatch` contendo `unicodePwd` (valor = `iconv('UTF-8', 'UTF-16LE', '"'.$senha.'"')`) e `pwdLastSet` `0`.
- `test_listar_contas_pagina`: duas paginas (modelo da doc de teste do LdapRecord) viram duas `ContaDiretorio`.
- `test_excecao_do_ldap_nao_vaza_mensagem_nem_previous`: o fake lanca com diagnostico `"0000052D: ... senha=Segredo123"`; a excecao de dominio tem `getPrevious() === null` e `getMessage()` sem `Segredo123`.

`tests/Unit/Acessos/TradutorErroLdapTest.php`: um teste por linha da tabela 9.1 (codigo LDAP -> `CodigoErroDiretorio` e classe transitoria/definitiva), via data provider.

`tests/Unit/Acessos/FabricaConexaoLdapTest.php`: `test_hosts_da_config_com_preferencial_primeiro`, `test_hosts_por_srv_ordenados_por_prioridade` (subclasse anonima sobrescreve `resolverSrv`), `test_ip_na_config_e_recusado`, `test_ldaps_exige_certificado` (`options[LDAP_OPT_X_TLS_REQUIRE_CERT] === LDAP_OPT_X_TLS_HARD`, `use_ssl` true, porta 636), `test_starttls_usa_389_e_use_tls`.

- [ ] **Step 2: Run tests to verify they fail**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Unit/Acessos/LdapDiretorioCorporativoTest.php tests/Unit/Acessos/TradutorErroLdapTest.php tests/Unit/Acessos/FabricaConexaoLdapTest.php
```
Expected: FAIL (classes inexistentes).

- [ ] **Step 3: Implementar**

Pontos que nao podem variar:

```php
public function redefinirSenha(ReferenciaConta $conta, #[\SensitiveParameter] string $senha, bool $exigirTroca, string $operationId): ResultadoOperacao
{
    $mods = [new BatchModification('unicodePwd', LDAP_MODIFY_BATCH_REPLACE, [iconv('UTF-8', 'UTF-16LE', '"'.$senha.'"')])];
    if ($exigirTroca) {
        $mods[] = new BatchModification('pwdLastSet', LDAP_MODIFY_BATCH_REPLACE, ['0']);
    }
    $this->executar(fn () => $this->conexao()->run(fn (Ldap $ldap) => $ldap->modifyBatch($conta->dn, array_map(fn ($m) => $m->get(), $mods))));

    return new ResultadoOperacao($this->relerOuFalhar($conta->objectGuid), efetivada: true);
}

private function executar(callable $operacao): mixed
{
    try {
        return $operacao();
    } catch (LdapRecordException $e) {
        throw $this->tradutor->traduzir($e);
    }
}
```

Conferir com Context7 (`/websites/ldaprecord_core_v3`) a API exata de `modifyBatch`, `Guid` e `paginate` da versao instalada antes de escrever; os nomes acima sao o contrato desejado, a chamada concreta segue a doc. `desabilitar`/`habilitar` releem o UAC por GUID imediatamente antes de gravar e retornam `efetivada=false` sem gravar quando o bit ja esta no estado-alvo.

Provider: acrescentar `'ldap' => $app->make(LdapDiretorioCorporativo::class),` ao `match`.

- [ ] **Step 4: Run tests to verify they pass**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Unit/Acessos
grep -rn "shell_exec\|proc_open\|exec(\|powershell\|python" app/Modules/Acessos
```
Expected: PASS; grep vazio.

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Infrastructure/LdapDiretorioCorporativo.php app/Modules/Acessos/Infrastructure/FabricaConexaoLdap.php app/Modules/Acessos/Infrastructure/TradutorErroLdap.php app/Modules/Acessos/AcessosServiceProvider.php"
git add $FILES && git commit -m "✨ feat(acessos): adaptador LDAPS do diretorio corporativo com LdapRecord" -- $FILES
```

---

### Task 5: Fila `diretorio`, caso de uso de solicitar e job com maquina de estados (onda B, depois de 1 e 2)

**Files:**
- Modify: `config/queue.php` (conexao `diretorio`, spec 8)
- Create: `app/Modules/Acessos/Services/{SolicitarOperacaoDiretorio,GuardaDeAlvo,AplicaEspelhoAd,DonoDaConta}.php`
- Create: `app/Modules/Acessos/Services/Acoes/{HandlerAcaoDiretorio,ConsultarConta}.php`
- Create: `app/Modules/Acessos/Jobs/ExecutarOperacaoDiretorioJob.php`
- Create: `app/Modules/Acessos/Events/OperacaoDiretorioConcluida.php`
- Test: `tests/Feature/Acessos/SolicitarOperacaoDiretorioTest.php`, `tests/Feature/Acessos/ExecutarOperacaoDiretorioJobTest.php`, `tests/Feature/Acessos/GuardaDeAlvoTest.php`; acrescentar ao trait `CriaAcessos` os helpers `diretorioFake()`, `conta()` e `processarFilaDiretorio()`.

**Interfaces:**
- Consumes: Tasks 1 e 2 (nao edita o provider: tudo resolvido pelo container).
- Produces:
  - Conexao de fila `diretorio` (database, tabela `acessos_fila_diretorio`).
  - `SolicitarOperacaoDiretorio::paraCadastro(CadastroAcesso, AcaoDiretorio, User $ator, ?string $motivo = null): OperacaoAd` e `::paraLogin(string $login, AcaoDiretorio, User $ator, OrigemOperacaoAd, ?int $origemId, string $chave): OperacaoAd` (spec 6.1). Chave padrao de Acessos: `acessos:{cadastro_id}:{acao}:{seq}` com `seq` = operacoes do cadastro/acao + 1.
  - `DonoDaConta::eDoAtor(User $ator, ?CadastroAcesso $cadastro, string $login): bool` (spec 7.2 item 3).
  - `GuardaDeAlvo::assegurar(ContaDiretorio $conta): void` (lanca `DiretorioRecusou` com `conta_fora_do_escopo`/`conta_protegida`) e `GuardaDeAlvo::status(ContaDiretorio $conta): StatusAd`.
  - `AplicaEspelhoAd::aplicar(CadastroAcesso $cadastro, ContaDiretorio $conta, StatusAd $status): void` e `::naoEncontrada(CadastroAcesso $cadastro): void`; grava `object_guid` quando nulo, nunca toca `status`.
  - `interface HandlerAcaoDiretorio { public function executar(OperacaoAd $operacao, ContaDiretorio $conta): ResultadoOperacao; }` e `ConsultarConta` (devolve `ResultadoOperacao($conta, efetivada: false)`).
  - `ExecutarOperacaoDiretorioJob(string $operacaoId)` na conexao `config('acessos.diretorio.fila.conexao')` e fila `config('acessos.diretorio.fila.nome')`, com `tries()`, `backoff()`, `$timeout = 30`, `handle(DiretorioCorporativo, GuardaDeAlvo, AplicaEspelhoAd, CircuitBreakerService)` e `failed(Throwable)` (spec 6.2). Um handler e resolvido por `app($operacao->acao->handler())`.
  - `OperacaoDiretorioConcluida(string $operacaoId)` (evento simples, `Dispatchable`).

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Acessos/SolicitarOperacaoDiretorioTest.php`:
- `test_operacao_e_job_sao_atomicos`: dentro de `DB::transaction(fn () => ... ; throw new RuntimeException())` capturado, nenhuma linha em `acessos_operacoes_ad` nem em `acessos_fila_diretorio`; sem excecao, uma linha em cada, e o payload do job contem o uuid e nao contem o login.
- `test_duplo_pedido_vira_uma_operacao`: duas chamadas `paraCadastro(..., DESBLOQUEAR, ...)` devolvem o mesmo `id`; uma linha na fila.
- `test_corrida_no_indice_parcial_devolve_a_em_voo`: insere a operacao em voo por fora (simula a outra requisicao) entre a busca e o insert (via `OperacaoAd::creating` temporario no teste) e confere que o caso de uso devolve a existente.
- `test_ninguem_age_sobre_a_propria_conta`: tres variantes (cadastro com `user_id` do ator; com `cpf` do ator; login igual ao `login_ad` de um cadastro do ator) -> `OperacaoDiretorioProibida`, zero operacoes.
- `test_conta_protegida_e_recusada_no_pedido`.
- `test_desabilitar_exige_motivo`.
- `test_auditoria_de_solicitacao_sem_segredo`: `acessos_auditoria` com `acao=diretorio_desbloquear_solicitado` e `dados == ['operacao_id' => $id]`.

`tests/Feature/Acessos/ExecutarOperacaoDiretorioJobTest.php` (fake semeado, `processarFilaDiretorio()`):
- `test_consulta_atualiza_espelho_e_confirma`: estado `confirmado`, `tentativas=1`, colunas-espelho iguais a conta, `object_guid` gravado, `status` do cadastro intocado, evento `OperacaoDiretorioConcluida` despachado (`Event::fake([OperacaoDiretorioConcluida::class])`).
- `test_transitoria_repete_e_confirma`: `falharCom('consultar', 'indisponivel')` uma vez -> confirmado com `tentativas=2`.
- `test_transitoria_esgotada_falha_com_indisponivel`.
- `test_definitiva_falha_sem_repetir`: `falharCom('consultar', CodigoErroDiretorio::SEM_PERMISSAO)` -> `falhou`, `codigo_erro=sem_permissao`, `tentativas=1`, uma chamada no fake.
- `test_conta_inexistente_marca_nao_encontrada`: `status_ad=nao_encontrada`, operacao `falhou` com `conta_inexistente`.
- `test_conta_fora_do_escopo_nao_recebe_escrita` (acao `desbloquear` com um handler de teste registrado no container; nenhuma chamada de escrita no fake).
- `test_operacao_final_nao_e_reexecutada`: despachar de novo o job de uma operacao `confirmado` -> nenhuma chamada nova.
- `test_circuito_aberto_libera_sem_gastar_tentativa` (`CircuitBreakerService` mockado com `isOpen=true`; job `release`d; estado `enviado`).
- `test_failed_jobs_nao_carrega_dado_do_ad`: payload e `exception` de `failed_jobs` sem o DN nem o login.

`tests/Feature/Acessos/GuardaDeAlvoTest.php`: DN com caixa e espacos diferentes da SearchBase passa; DN de OU irma (`OU=SDC2,...`) nao passa; DN que apenas contem a SearchBase no meio nao passa; `adminCount`, lista protegida e conta de servico recusam.

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/SolicitarOperacaoDiretorioTest.php tests/Feature/Acessos/ExecutarOperacaoDiretorioJobTest.php tests/Feature/Acessos/GuardaDeAlvoTest.php
```
Expected: FAIL.

- [ ] **Step 3: Implementar**

Nucleo do job (o resto segue a spec 6.2):

```php
public function handle(DiretorioCorporativo $diretorio, GuardaDeAlvo $guarda, AplicaEspelhoAd $espelho, CircuitBreakerService $circuito): void
{
    $operacao = $this->marcarEnviado();
    if ($operacao === null) {
        return; // final ou inexistente
    }
    if ($circuito->isOpen(self::CIRCUITO)) {
        $this->release(60);

        return;
    }

    try {
        $conta = $this->resolverConta($diretorio, $operacao, $espelho);
        $guarda->assegurar($conta);
        $resultado = app($operacao->acao->handler())->executar($operacao, $conta);
    } catch (DiretorioIndisponivel $e) {
        $circuito->recordFailure(self::CIRCUITO);
        throw $e;
    } catch (DiretorioRecusou $e) {
        $this->fail($e);

        return;
    }

    DB::transaction(function () use ($operacao, $resultado, $guarda, $espelho): void {
        // espelho, operacao confirmado, auditoria diretorio_<acao>_confirmado
    });
    $circuito->recordSuccess(self::CIRCUITO);
    OperacaoDiretorioConcluida::dispatch($operacao->id);
}
```

`marcarEnviado()` faz `DB::transaction` + `lockForUpdate` + `podeIrPara(ENVIADO)`. `failed()` grava `falhou` com `$e instanceof DiretorioRecusou || $e instanceof DiretorioIndisponivel ? $e->codigo() : CodigoErroDiretorio::ERRO_INTERNO`, auditoria `diretorio_<acao>_falhou` e o evento. `ConsultarConta` nao precisa de guarda que falhe: o job chama `$guarda->status($conta)` para `consultar` em vez de `assegurar` (so escritas falham na guarda).

- [ ] **Step 4: Run tests to verify they pass**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos
```
Expected: PASS (Unit do adaptador LDAP fica fora se a imagem padrao nao tiver `ext-ldap`; rodar com `IMAGEM=newsdc-swoole-dev:ldap` para a suite inteira).

- [ ] **Step 5: Commit**

```bash
FILES="config/queue.php app/Modules/Acessos/Services app/Modules/Acessos/Jobs app/Modules/Acessos/Events"
git add $FILES && git commit -m "✨ feat(acessos): fila diretorio, solicitacao atomica e job de operacao no AD" -- $FILES
```

---

### Task 6: Automacao de Demandas sobre o caso de uso de Acessos (onda C)

**Files:**
- Modify: `app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`
- Delete: `app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`
- Create: `app/Modules/Demandas/Listeners/RegistraResultadoAutomacao.php`
- Modify: `app/Modules/Demandas/DemandasServiceProvider.php` (`Event::listen(OperacaoDiretorioConcluida::class, RegistraResultadoAutomacao::class)`)
- Test: reescrever `tests/Feature/Demandas/DemandaAutomacaoTest.php` (local, nao versionado)

**Interfaces:**
- Consumes: `SolicitarOperacaoDiretorio::paraLogin`, `OperacaoDiretorioConcluida`, `OperacaoAd`, `CodigoErroDiretorio::mensagem()` (Task 5).
- Produces: `ExecutarAutomacaoDemanda::solicitar(Demanda, int $userId): string` com a mesma assinatura e o mesmo `operation_id`; constante `MAPA_ACAO = ['desbloquear' => AcaoDiretorio::DESBLOQUEAR, 'ativar' => AcaoDiretorio::HABILITAR, 'resetar' => AcaoDiretorio::REDEFINIR_SENHA]`. O listener so age em operacoes com `origem=demanda`.

- [ ] **Step 1: Reescrever os testes (devem falhar)**

`tests/Feature/Demandas/DemandaAutomacaoTest.php`: trocar a classe anonima que implementava a porta antiga por `FakeDiretorioCorporativo` semeado (trait `CriaAcessos`) e `processarFilaDiretorio()`. Manter os cenarios atuais com os mesmos nomes:
- `test_desbloqueio_pelo_login_do_campo_dinamico` (historico `automation_requested` e depois `automation_confirmed`; fake com uma chamada `desbloquear`).
- `test_login_com_caracteres_de_shell_e_recusado`.
- `test_falha_do_ad_registra_falhou_e_nao_muda_status` (`falharCom('desbloquear', 'indisponivel')` em todas as tentativas).
- `test_operation_id_incrementa_por_tentativa` (chave `demanda:{id}:desbloquear:1` e `:2`, gravadas como `chave_idempotencia`).
- `test_falha_4xx_e_tentada_uma_vez_e_nao_e_reenfileirada` (agora: recusa definitiva `sem_permissao`, uma chamada).
- `test_falha_do_ad_nao_grava_resposta_no_historico` (historico com o rotulo do codigo, sem DN/login do AD).
- Novos: `test_automacao_na_propria_conta_e_recusada` (ator com cadastro cujo `login_ad` e o login do campo); `test_resetar_cria_entrega_so_para_o_operador` (marcar `markTestSkipped('Task 16')` ate a Task 16 e tirar o skip la).

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAutomacaoTest.php
```
Expected: FAIL (o servico ainda despacha o job antigo, que chama metodos inexistentes da porta).

- [ ] **Step 3: Implementar**

```php
$operacao = $this->diretorio->paraLogin(
    $login, self::MAPA_ACAO[$acao], $ator, OrigemOperacaoAd::DEMANDA, $demanda->id, $operationId,
);
```

dentro do mesmo fluxo que ja grava `AUTOMACAO_SOLICITADA` (mesma transacao). `OperacaoDiretorioProibida` vira `DomainException($e->getMessage())`. O listener:

```php
public function handle(OperacaoDiretorioConcluida $evento): void
{
    $operacao = OperacaoAd::find($evento->operacaoId);
    if ($operacao?->origem !== OrigemOperacaoAd::DEMANDA || ($demanda = Demanda::find($operacao->origem_id)) === null) {
        return;
    }
    $confirmada = $operacao->estado === EstadoOperacaoAd::CONFIRMADO;
    $this->historico->registrar(
        $demanda, (int) $operacao->solicitado_por_id,
        $confirmada ? AcaoHistoricoDemanda::AUTOMACAO_CONFIRMADA : AcaoHistoricoDemanda::AUTOMACAO_FALHOU,
        $confirmada
            ? sprintf('"%s" confirmado pelo diretório para %s.', $operacao->acao->label(), $operacao->login_ad)
            : sprintf('"%s" falhou: %s', $operacao->acao->label(), $operacao->codigo_erro?->mensagem() ?? 'erro desconhecido'),
        ['operation_id' => $operacao->chave_idempotencia],
    );
}
```

O `catch (\Throwable)` do `DemandaController::automatizar` continua (fila `sync` em algum ambiente), sem mudanca.

- [ ] **Step 4: Run tests to verify they pass**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Demandas tests/Feature/Acessos
grep -rn "ExecutarAutomacaoDemandaJob" app routes
```
Expected: PASS (exceto o skip marcado); grep vazio.

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php app/Modules/Demandas/Listeners/RegistraResultadoAutomacao.php app/Modules/Demandas/DemandasServiceProvider.php"
git add -A $FILES && git commit -m "♻️ refactor(demandas): automacao do AD pelo caso de uso de Acessos" -- $FILES
```

---

### Task 7: Comando de diagnostico do diretorio (onda C, depois da 4)

**Files:**
- Create: `app/Modules/Acessos/Console/DiagnosticoDiretorioCommand.php`
- Modify: `app/Modules/Acessos/AcessosServiceProvider.php` (`boot()`: `if ($this->app->runningInConsole()) { $this->commands([DiagnosticoDiretorioCommand::class]); }`)
- Test: `tests/Feature/Acessos/DiagnosticoDiretorioCommandTest.php`

**Interfaces:**
- Produces: `acessos:diretorio-diagnostico {--fila} {--json}`. Checagens (cada uma `ok|falha|ignorado` + detalhe sem segredo): `banco`, `esquema` (tabelas/colunas da spec 5), `config` (driver, search_base, chave de entrega presente sim/nao), `dns` (cada host resolve), `tls` (handshake com `stream_socket_client('ssl://host:636')` + `cafile`; dias ate o vencimento do certificado do servidor), `bind`, `search_base` (leitura base da OU), `fila` (`--fila`: quantidade e idade do job mais antigo em `acessos_fila_diretorio`). Exit 0 so com tudo `ok|ignorado`. Para o driver `fake`/`desligado`, as checagens de rede saem `ignorado`. A interface publica de diagnostico do adaptador fica em `LdapDiretorioCorporativo::diagnosticar(): array` (metodo extra, fora da porta).

- [ ] **Step 1: Write the failing test**

- `test_driver_fake_ignora_rede_e_passa`.
- `test_sem_chave_de_entrega_falha_config` (exit 1, linha `config` com `falha`).
- `test_json_nao_contem_segredo` (`DIRETORIO_SENHA` e `DIRETORIO_CHAVE_ENTREGA` ausentes da saida).
- `test_fila_mostra_idade_do_job_mais_antigo`.

- [ ] **Step 2: Run test to verify it fails**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/DiagnosticoDiretorioCommandTest.php
```
Expected: FAIL.

- [ ] **Step 3: Implementar** conforme Interfaces; o `diagnosticar()` do adaptador reaproveita `FabricaConexaoLdap` e o `TradutorErroLdap` (detalhe = rotulo do codigo, nunca a mensagem do servidor).

- [ ] **Step 4: Run test to verify it passes** (mesmo comando) e `bash /c/tmp/teste-acessos.sh php artisan acessos:diretorio-diagnostico --fila`.
Expected: PASS; saida em tabela com `ignorado` nas checagens de rede.

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Console/DiagnosticoDiretorioCommand.php app/Modules/Acessos/AcessosServiceProvider.php app/Modules/Acessos/Infrastructure/LdapDiretorioCorporativo.php"
git add $FILES && git commit -m "✨ feat(acessos): diagnostico da integracao com o diretorio" -- $FILES
```

---

### Task 8: Fechamento da Fase 1

Sem codigo novo, salvo correcoes (cada uma vira commit `🐛 fix(acessos): ...`).

- [ ] **Step 1: Suites e regressao**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos tests/Feature/Demandas
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php artisan permissions:audit
```
Expected: tudo PASS (um skip, o da Task 6); Demandas igual a baseline da Task 0.

- [ ] **Step 2: Conferir o escopo**

```bash
git status --short
git log --oneline origin/dev..HEAD
grep -rn "shell_exec\|proc_open\|powershell\|python\|corporate_directory\|HttpDiretorioCorporativo" app config routes
```
Expected: nada fora do escopo; nenhum `tests/` staged; commits das Tasks 1-7; grep vazio.

- [ ] **Step 3: Merge no `dev` por worktree temporaria** (o checkout principal troca de branch entre sessoes; nunca mergear nele)

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC
git worktree list                     # o dev nao pode estar em checkout em outra worktree
git fetch origin
git worktree add .worktrees/merge-dev-tmp dev
cd .worktrees/merge-dev-tmp
git branch --show-current             # tem de ser dev
git pull --ff-only origin dev
git merge --no-ff feat/acessos-active-directory -m "🔀 merge(acessos): Fase 1 fundacao da integracao com o AD"
git push origin dev
cd .. && git worktree remove .worktrees/merge-dev-tmp
```

- [ ] **Step 4: Deploy de homolog**

Seguir a receita vigente de homolog (memoria `newsdc-merge-via-worktree-temporaria`): build da imagem a partir de uma worktree do `dev` (destacado, com log), overlay HA quando aplicavel, e `docker compose -p newsdc-homolog -f compose.homolog.yml [-f compose.homolog-ha.yml] up -d --no-deps --no-build ... app queue queue_diretorio scheduler reverb`. A imagem agora tem `ext-ldap`: rebuild obrigatorio. Conferir:

```bash
docker exec newsdc-homolog-app-1 php artisan migrate:status | grep 2026_10_02_100000
docker exec newsdc-homolog-queue_diretorio-1 php artisan acessos:diretorio-diagnostico
```
Expected: migration `Ran`; diagnostico com rede `ignorado` (driver `fake`). Semear o fake pelo app (o arquivo JSON e compartilhado com o worker): `docker exec newsdc-homolog-app-1 php artisan tinker --execute='app(App\Modules\Acessos\Infrastructure\FakeDiretorioCorporativo::class)->semear(App\Modules\Acessos\DTOs\ContaDiretorio::fromArray(["objectGuid" => (string) Illuminate\Support\Str::uuid(), "login" => "tst.homolog", "dn" => "CN=TST Homolog,".config("acessos.diretorio.search_base"), "ativa" => true, "bloqueada" => true, "trocaSenhaPendente" => false, "protegida" => false]));'`. Abrir uma demanda com assunto de automacao `desbloquear` em `http://localhost:8090`, clicar "Automação" e ver `automation_confirmed` no historico em poucos segundos.

---

## FASE 2 — Leitura (F2)

Entrega: "Consultar no AD" no cadastro com acompanhamento da operacao, espelho do AD na listagem e no detalhe, sincronizacao agendada por GUID com freio e simulacao, relatorio de divergencias.

### Task 9: Consulta ao vivo e endpoint da operacao

**Files:**
- Create: `app/Modules/Acessos/Controllers/OperacaoDiretorioController.php` (`solicitar`, `show`)
- Create: `app/Modules/Acessos/Requests/SolicitarOperacaoDiretorioRequest.php`
- Create: `app/Modules/Acessos/Policies/OperacaoAdPolicy.php` (`ver`; `retirarSenha` entra na Task 16)
- Create: `app/Modules/Acessos/Support/ApresentacaoOperacaoAd.php`
- Modify: `app/Modules/Acessos/AcessosServiceProvider.php` (`Gate::policy(OperacaoAd::class, OperacaoAdPolicy::class)`; `RateLimiter::for('acessos-diretorio', ...)` 10/min por usuario e `acessos-diretorio-senha` 5/min)
- Modify: `routes/modules/acessos.php`
- Modify: `app/Modules/Acessos/Controllers/CadastroAcessoController.php` (props e filtros)
- Modify (se preciso): `app/Http/Middleware/HandleInertiaRequests.php` (compartilhar o flash `operacao_diretorio`)
- Test: `tests/Feature/Acessos/ConsultaDiretorioHttpTest.php`, `tests/Feature/Acessos/CadastroEspelhoAdTest.php`

**Interfaces:**
- Consumes: `SolicitarOperacaoDiretorio` (Task 5).
- Produces:
  - Rotas `acessos.diretorio.consultar` (POST) e `acessos.operacoes.show` (GET JSON), `{cadastro}` com `whereNumber` em todas as rotas (inclusive as existentes) e `{operacao}` com `whereUuid`; rotas literais antes de `/{cadastro}`.
  - `SolicitarOperacaoDiretorioRequest`: `acao(): AcaoDiretorio` lido de `$this->route()->defaults['acao']` (cada rota declara `->defaults('acao', AcaoDiretorio::X->value)`); `authorize()` = `$this->user()->can($this->acao()->permissao())`; `rules()`: `motivo` `required_if` desabilitar, `string|max:500`.
  - `OperacaoDiretorioController::solicitar(SolicitarOperacaoDiretorioRequest, CadastroAcesso)`: uma acao generica (todas as rotas de acao da Fase 3 apontam para ela), devolve `back()->with('operacao_diretorio', ['id' => ..., 'acao' => ...])`. `show(OperacaoAd)` devolve `ApresentacaoOperacaoAd::paraJson($operacao, $request->user())`: `{id, acao, acao_rotulo, estado, estado_rotulo, codigo_erro, mensagem_erro, efetivada, solicitado_em, concluido_em, senha_disponivel}` (`senha_disponivel` fica `false` ate a Task 16).
  - `CadastroAcessoController::index`: itens com `status_ad`, `conta_ativa_ad`, `bloqueada_ad`; filtros validados `status_ad` (in `StatusAd`) e `bloqueada` (boolean). `show`: `cadastro` com as colunas-espelho, `operacoes` (10 ultimas via `ApresentacaoOperacaoAd::resumo`), `pode.diretorio = {ver, gerenciar, redefinir}`.

- [ ] **Step 1: Write the failing tests**

`ConsultaDiretorioHttpTest`:
- `test_consultar_exige_diretorio_view` (403 sem; com: redirect com flash `operacao_diretorio` e uma operacao `consultar` em `solicitado`).
- `test_consultar_a_propria_conta_da_422`.
- `test_show_da_operacao_para_o_ator_e_para_quem_tem_view` (200) e `test_show_nega_terceiro_sem_view` (403).
- `test_show_nao_expoe_resultado_cru` (chaves exatamente as de `paraJson`).
- `test_operacao_nao_uuid_da_404` (`/acessos/operacoes/123`) e `test_cadastro_nao_numerico_da_404` (`/acessos/abc`).
- `test_throttle_na_solicitacao` (11a requisicao no minuto -> 429).
- `test_fluxo_solicitar_processar_e_ver_confirmado` (solicitar -> `processarFilaDiretorio()` -> `show` com `estado=confirmado`).

`CadastroEspelhoAdTest`: props de `acessos.index` e `acessos.show` com as colunas-espelho (`assertInertia`); filtro `status_ad=nao_encontrada` e `bloqueada=1`; `pode.diretorio` conforme slugs; `operacoes` sem `resultado`.

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/ConsultaDiretorioHttpTest.php tests/Feature/Acessos/CadastroEspelhoAdTest.php
```
Expected: FAIL.

- [ ] **Step 3: Implementar**

`routes/modules/acessos.php` (trecho novo, antes das rotas com `{cadastro}`):

```php
Route::get('/operacoes/{operacao}', [OperacaoDiretorioController::class, 'show'])
    ->name('operacoes.show')->whereUuid('operacao');
Route::post('/{cadastro}/diretorio/consultar', [OperacaoDiretorioController::class, 'solicitar'])
    ->name('diretorio.consultar')->whereNumber('cadastro')
    ->defaults('acao', AcaoDiretorio::CONSULTAR->value)
    ->middleware(['can:acessos.diretorio.view', 'throttle:acessos-diretorio']);
```

A policy `ver`: `$user->id === $operacao->solicitado_por_id || $user->can('acessos.diretorio.view')`.

- [ ] **Step 4: Run tests to verify they pass**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos
bash /c/tmp/teste-acessos.sh php artisan route:list --path=acessos
```
Expected: PASS; rotas com os `can:` da spec 7.1.

- [ ] **Step 5: Commit** (tirar `HandleInertiaRequests.php` da lista se nao mudou)

```bash
FILES="app/Modules/Acessos/Controllers app/Modules/Acessos/Requests/SolicitarOperacaoDiretorioRequest.php app/Modules/Acessos/Policies app/Modules/Acessos/Support app/Modules/Acessos/AcessosServiceProvider.php routes/modules/acessos.php app/Http/Middleware/HandleInertiaRequests.php"
git add $FILES && git commit -m "✨ feat(acessos): consulta da conta no AD e acompanhamento da operacao" -- $FILES
```

---

### Task 10: Sincronizacao por objectGUID com freio de ausencia

**Files:**
- Create: `app/Modules/Acessos/Services/{SincronizadorDiretorio,SolicitarSincronizacao}.php`
- Create: `app/Modules/Acessos/Jobs/SincronizarDiretorioJob.php`
- Create: `app/Modules/Acessos/Console/SincronizarDiretorioCommand.php`
- Modify: `app/Modules/Acessos/AcessosServiceProvider.php` (registrar o comando)
- Modify: `routes/console.php`
- Test: `tests/Feature/Acessos/SincronizadorDiretorioTest.php`, `tests/Feature/Acessos/SincronizarDiretorioCommandTest.php`

**Interfaces:**
- Consumes: porta `listarContas()`/`buscarPorGuid()` (fake), `GuardaDeAlvo`, `AplicaEspelhoAd` (Task 5).
- Produces:
  - `SolicitarSincronizacao::solicitar(?User $ator, bool $simulacao = false): SincronizacaoAd` (spec 6.5 passo 2; devolve a em andamento se houver uma com menos de 1 h).
  - `SincronizadorDiretorio::executar(SincronizacaoAd $rodada): void` (passos 3-8; `pg_try_advisory_lock(hashtext('acessos:sincronizacao'))` e `pg_advisory_unlock` no `finally`).
  - `SincronizarDiretorioJob(string $sincronizacaoId)` na fila `diretorio`, `$timeout = 900`, `tries = 1` (a proxima hora tenta de novo).
  - `acessos:sincronizar-diretorio {--simular}`; agenda e purga da spec 6.5 em `routes/console.php`, sob `config('acessos.diretorio.sincronizar.agendar')`, aos :20 (longe dos :40, :50, :55 e hora cheia ja ocupados).

- [ ] **Step 1: Write the failing tests**

`SincronizadorDiretorioTest` (fake semeado + cadastros):
- `test_casa_por_guid_e_atualiza_espelho` (conta renomeada no AD: espelho atualizado, divergencia `login_divergente`, `login_ad` local intocado).
- `test_vincula_por_login_exato_unico` (sem diferenca de caixa; grava GUID; `totais.vinculadas_por_login = 1`).
- `test_nunca_casa_por_nome` (cadastro com `nome` igual ao `displayName` de uma conta e login diferente -> `cadastro_sem_conta`, sem GUID gravado).
- `test_cadastro_sem_conta_vira_nao_encontrada_sem_mudar_status` (`status` antes == depois).
- `test_conta_fora_do_escopo` (GUID existe so via `buscarPorGuid`).
- `test_divergencias_de_status` (`ativo_local_desabilitada_ad`, `inativo_local_habilitada_ad`).
- `test_conta_sem_cadastro_limitada` (`max_sem_cadastro=2`, 5 contas sem cadastro -> 2 linhas, `totais.divergencias.conta_sem_cadastro = 5`).
- `test_freio_de_ausencia_aborta_sem_aplicar` (60% sem par -> `abortada`, `codigo_erro=resultado_suspeito`, nenhum espelho mudou) e `test_lista_vazia_aborta`.
- `test_simulacao_nao_aplica` (divergencias gravadas, espelho intocado).
- `test_advisory_lock_impede_rodada_concorrente` (segurar o lock numa segunda conexao PDO e rodar -> `abortada` com `concorrente`).
- `test_indisponivel_falha_a_rodada_sem_aplicar`.
- `test_purga_rodadas_antigas` (rodada alem da retencao some com as divergencias).

`SincronizarDiretorioCommandTest`: `--simular` cria a rodada simulada e o job na fila; segunda chamada com uma em andamento nao cria outra; `Schedule` contem o comando quando `agendar=true` (`app(Schedule::class)->events()` filtrado pelo comando).

- [ ] **Step 2: Run tests to verify they fail**

```bash
bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/SincronizadorDiretorioTest.php tests/Feature/Acessos/SincronizarDiretorioCommandTest.php
```
Expected: FAIL.

- [ ] **Step 3: Implementar** conforme a spec 6.5. Indices em memoria so com os campos do DTO; cadastros por `chunkById(500)`; espelho em lotes de 200 em transacoes curtas; divergencias por `insert` em lote. Nenhuma linha de log com DN ou login (so `totais`).

- [ ] **Step 4: Run tests to verify they pass** (mesmo comando, depois `tests/Feature/Acessos` inteiro). Expected: PASS.

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Services/SincronizadorDiretorio.php app/Modules/Acessos/Services/SolicitarSincronizacao.php app/Modules/Acessos/Jobs/SincronizarDiretorioJob.php app/Modules/Acessos/Console/SincronizarDiretorioCommand.php app/Modules/Acessos/AcessosServiceProvider.php routes/console.php"
git add $FILES && git commit -m "✨ feat(acessos): sincronizacao com o AD por objectGUID e relatorio de divergencias" -- $FILES
```

---

### Task 11: HTTP das divergencias e "Sincronizar agora"

**Files:**
- Create: `app/Modules/Acessos/Controllers/DivergenciaDiretorioController.php` (`index`, `sincronizar`)
- Create: `app/Modules/Acessos/Queries/DivergenciasQuery.php`
- Create: `app/Modules/Acessos/Requests/SincronizarDiretorioRequest.php` (`simular` boolean)
- Modify: `routes/modules/acessos.php`
- Test: `tests/Feature/Acessos/DivergenciasHttpTest.php`

**Interfaces:**
- Produces:
  - `GET /acessos/diretorio/divergencias` (`acessos.diretorio.divergencias`, `can:acessos.diretorio.view`) renderiza `Acessos/Divergencias` com props `rodada` (`{id, estado, estado_rotulo, simulacao, iniciada_em, concluida_em, codigo_erro, mensagem_erro, totais}` ou null), `divergencias` (paginador de 25: `{id, tipo, tipo_rotulo, cadastro: {id, nome}|null, login_ad, detalhe}`), `filters` (`tipo`, `rodada`), `tipos` (opcoes de `TipoDivergenciaAd`), `pode.sincronizar`.
  - `POST /acessos/diretorio/sincronizar` (`acessos.diretorio.sincronizar`, `can:acessos.diretorio.manage`, throttle) -> back com `success`.

- [ ] **Step 1: Write the failing test**: permissoes (view lista, sem view 403; manage sincroniza, sem manage 403); filtro por tipo; `detalhe` so com as chaves da spec 5.4; `test_rota_literal_nao_cai_em_cadastro`; sem rodada -> `rodada` null e lista vazia.
- [ ] **Step 2: Run** `bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/DivergenciasHttpTest.php`. Expected: FAIL.
- [ ] **Step 3: Implementar** (a pagina Vue vem na Task 13; ate la o teste usa `assertInertia(fn ($p) => $p->component('Acessos/Divergencias', false))`).
- [ ] **Step 4: Run** (mesmo comando). Expected: PASS; `route:list --path=acessos` mostra as duas rotas antes de `/{cadastro}`.
- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Controllers/DivergenciaDiretorioController.php app/Modules/Acessos/Queries app/Modules/Acessos/Requests/SincronizarDiretorioRequest.php routes/modules/acessos.php"
git add $FILES && git commit -m "✨ feat(acessos): relatorio de divergencias e sincronizacao sob demanda" -- $FILES
```

---

### Task 12: Frontend do espelho do AD no detalhe do cadastro (onda, com a 13)

**Files:**
- Create: `resources/js/Support/diretorio.js`, `resources/js/Composables/acessos/useOperacaoDiretorio.js` (Step 1, rodado pelo controlador antes de despachar a Task 13)
- Create: `resources/js/Components/Atoms/Acessos/{StatusAdBadge,EstadoOperacaoBadge}.vue` (tambem no Step 1: a Task 13 usa o `StatusAdBadge`)
- Create: `resources/js/Components/Organisms/Acessos/Show/{AcessoDiretorioCard,AcessoOperacoesCard}.vue`
- Modify: `resources/js/Components/Organisms/Acessos/Show/AcessoSituacaoCard.vue` (sai a linha "Status no AD"), `resources/js/Templates/Acessos/AcessoShowTemplate.vue`, `resources/js/Pages/Acessos/Show.vue`, `resources/js/Support/acessos.js` (rotulos `diretorio_*`, `senha_exibida`)

**Interfaces:**
- Consumes: props da Task 9 (`cadastro`, `operacoes`, `pode.diretorio`, flash `operacao_diretorio`); `GET /acessos/operacoes/{id}`.
- Produces:
  - `Support/diretorio.js`: `ACOES_DIRETORIO`, `ESTADOS_OPERACAO`, `STATUS_AD`, `CODIGOS_ERRO`, `TIPOS_DIVERGENCIA` (valor, rotulo, variante), `rotuloAcaoDiretorio`, `varianteEstadoOperacao`, `rotuloStatusAd`, `varianteStatusAd`.
  - `useOperacaoDiretorio()`: `{ estado, operacao, processando, solicitar(url, dados = {}), acompanhar(id), retirarSenha(id) }` (spec 11). `retirarSenha` (axios POST, devolve a string, nao guarda) ja existe aqui; so e usado na Fase 3. Polling 2 s ate 60 s, depois 10 s ate 5 min; para no estado final e chama `router.reload({ only: ['cadastro', 'operacoes'] })`; `onUnmounted` limpa o timer.
  - `AcessoDiretorioCard`: props `cadastro`, `pode`, `operacaoEmAndamento`; emite `acao(nome)`; nesta task so o botao "Consultar no AD" (os de escrita entram na Task 17) e a faixa de acompanhamento ("Aguardando o processador do AD…" apos 60 s em `solicitado`).
  - `AcessoOperacoesCard`: props `operacoes`; lista com `EstadoOperacaoBadge` e `mensagem_erro`.

- [ ] **Step 1 (controlador, antes da Task 13):** criar `Support/diretorio.js`, `useOperacaoDiretorio.js` e os dois Atoms. Eles entram no commit desta task.
- [ ] **Step 2: Implementar os Organisms** (`min-w-0`/`break-words` no DN; badges com texto, nunca so cor).
- [ ] **Step 3: Ligar no `AcessoShowTemplate`** (card na `aside`, acima do historico; o `ConfirmDialog` atual passa a servir tambem as acoes do AD).
- [ ] **Step 4: Build**

```bash
npx vite build --outDir C:/tmp/vite-task12 --emptyOutDir 2>&1 | tail -3
grep -rn "console.log\|debugger" resources/js/Support/diretorio.js resources/js/Composables/acessos resources/js/Components/Organisms/Acessos resources/js/Components/Atoms/Acessos
```
Expected: build ok; grep vazio.

- [ ] **Step 5: Commit**

```bash
FILES="resources/js/Support/diretorio.js resources/js/Support/acessos.js resources/js/Composables/acessos/useOperacaoDiretorio.js resources/js/Components/Atoms/Acessos/StatusAdBadge.vue resources/js/Components/Atoms/Acessos/EstadoOperacaoBadge.vue resources/js/Components/Organisms/Acessos/Show resources/js/Templates/Acessos/AcessoShowTemplate.vue resources/js/Pages/Acessos/Show.vue"
git add $FILES && git commit -m "✨ feat(acessos): situacao no AD e operacoes no detalhe do cadastro" -- $FILES
```

---

### Task 13: Frontend da listagem e da tela de divergencias (onda, com a 12)

**Files:**
- Modify: `resources/js/Components/Organisms/Acessos/{AcessosLista,AcessosFiltersSection}.vue`, `resources/js/Templates/Acessos/AcessosIndexTemplate.vue`, `resources/js/Pages/Acessos/Index.vue`
- Create: `resources/js/Pages/Acessos/Divergencias.vue`, `resources/js/Templates/Acessos/AcessosDivergenciasTemplate.vue`, `resources/js/Components/Organisms/Acessos/Divergencias/{SincronizacaoResumoCard,DivergenciasLista}.vue`
- Modify: `resources/js/Components/Sidebar.vue` (entrada "Divergências do AD" sob Acessos, `hasPermission(['acessos.diretorio.view'])`)

**Interfaces:**
- Consumes: props das Tasks 9 e 11; `Support/diretorio.js` e `StatusAdBadge` (Step 1 da Task 12).
- Produces: listagem com badge do AD (bloqueada/desabilitada/nao encontrada) e filtros `status_ad`/`bloqueada`; pagina `Acessos/Divergencias` com `PageHeader` (acoes "Sincronizar agora" e "Simular" so com `pode.sincronizar`, `ConfirmDialog`), `SincronizacaoResumoCard` (estado, horario, totais, aviso destacado para `abortada` com a mensagem do codigo), `FilterSection` por tipo, `DivergenciasLista` (link para `/acessos/{id}` quando ha cadastro; blocos no mobile), `Pagination`.

- [ ] **Step 1: Implementar** os componentes.
- [ ] **Step 2: Build** `npx vite build --outDir C:/tmp/vite-task13 --emptyOutDir 2>&1 | tail -3`. Expected: ok.
- [ ] **Step 3: Commit**

```bash
FILES="resources/js/Components/Organisms/Acessos/AcessosLista.vue resources/js/Components/Organisms/Acessos/AcessosFiltersSection.vue resources/js/Templates/Acessos/AcessosIndexTemplate.vue resources/js/Pages/Acessos/Index.vue resources/js/Pages/Acessos/Divergencias.vue resources/js/Templates/Acessos/AcessosDivergenciasTemplate.vue resources/js/Components/Organisms/Acessos/Divergencias resources/js/Components/Sidebar.vue"
git add $FILES && git commit -m "✨ feat(acessos): AD na listagem e tela de divergencias da sincronizacao" -- $FILES
```

---

### Task 14: Fechamento da Fase 2

- [ ] **Step 1: Suites e build**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos tests/Feature/Demandas
npx vite build 2>&1 | tail -3
```
Expected: PASS; build ok.

- [ ] **Step 2: Escopo** (`git status --short`, `git log --oneline origin/dev..HEAD`): commits das Tasks 9-13; nada de `tests/`.

- [ ] **Step 3: Merge no `dev`** pela worktree temporaria (comandos da Task 8 Step 3), mensagem `🔀 merge(acessos): Fase 2 consulta, sincronizacao e divergencias do AD`.

- [ ] **Step 4: Deploy de homolog** (receita da Task 8 Step 4) e conferencia: semear no fake 3 contas (uma bloqueada, uma desabilitada, uma sem cadastro) e criar 3 cadastros (um casando por login, um sem conta, um com nome igual a um `displayName` e login diferente); `docker exec newsdc-homolog-app-1 php artisan acessos:sincronizar-diretorio --simular`; em `http://localhost:8090/acessos/diretorio/divergencias` a rodada aparece concluida (simulacao) com `cadastro_sem_conta = 2`; rodar sem `--simular` e conferir os badges na listagem; "Consultar no AD" num cadastro confirma em poucos segundos.

---

## FASE 3 — Acoes de conta (F3)

Entrega: desbloquear, habilitar, desabilitar (com motivo), exigir troca de senha e redefinir senha (senha forte exibida uma vez ao operador), pela tela de Acessos e pela automacao de Demandas.

### Task 15: Desbloquear, habilitar, desabilitar e exigir troca

**Files:**
- Create: `app/Modules/Acessos/Services/Acoes/{DesbloquearConta,HabilitarConta,DesabilitarConta,ExigirTrocaSenha}.php`
- Modify: `routes/modules/acessos.php` (4 rotas, todas para `OperacaoDiretorioController::solicitar` com `defaults('acao', ...)`, `can:acessos.diretorio.manage`, throttle)
- Test: `tests/Feature/Acessos/AcoesDeContaTest.php`

**Interfaces:**
- Consumes: `HandlerAcaoDiretorio`, job e caso de uso (Task 5), porta (Task 2).
- Produces: um handler por acao, cada um com uma linha de efeito (`return $this->diretorio->desbloquear($conta->referencia(), $operacao->chave_idempotencia);` etc.); rotas `acessos.diretorio.desbloquear|habilitar|desabilitar|exigir-troca`.

- [ ] **Step 1: Write the failing tests** (fake + `processarFilaDiretorio()`):
- `test_desbloquear_conta_bloqueada` (`bloqueada_ad=false`, `resultado.efetivada=true`, auditoria `diretorio_desbloquear_confirmado`) e `test_desbloquear_conta_ja_desbloqueada_confirma_sem_efeito` (`efetivada=false`).
- `test_desabilitar_exige_motivo_e_grava_na_operacao` (sem motivo 422; com motivo: `motivo` na operacao e na auditoria, `conta_ativa_ad=false`, `status_ad=desabilitada`, `status` local intocado).
- `test_habilitar_reverte`.
- `test_exigir_troca_marca_pendente`.
- `test_acoes_exigem_diretorio_manage` (data provider pelas 4 rotas: 403 sem o slug; `acessos.diretorio.view` sozinho nao basta).
- `test_conta_protegida_nao_recebe_escrita` (fake com `adminCount=1`: operacao `falhou` `conta_protegida`, zero chamadas de escrita).
- `test_conta_movida_para_fora_da_ou_nao_recebe_escrita`.
- `test_retry_apos_timeout_converge` (`falharCom('desbloquear', 'indisponivel')` uma vez com o fake aplicando o efeito antes de lancar; segunda tentativa confirma com `efetivada=false`; conta desbloqueada).

- [ ] **Step 2: Run** `bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos/AcoesDeContaTest.php`. Expected: FAIL.
- [ ] **Step 3: Implementar** handlers e rotas.
- [ ] **Step 4: Run** `bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos`. Expected: PASS.
- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Services/Acoes/DesbloquearConta.php app/Modules/Acessos/Services/Acoes/HabilitarConta.php app/Modules/Acessos/Services/Acoes/DesabilitarConta.php app/Modules/Acessos/Services/Acoes/ExigirTrocaSenha.php routes/modules/acessos.php"
git add $FILES && git commit -m "✨ feat(acessos): desbloquear, habilitar, desabilitar e exigir troca de senha no AD" -- $FILES
```

---

### Task 16: Redefinir senha com entrega de leitura unica

**Files:**
- Create: `app/Modules/Acessos/Services/{GeradorSenhaForte,CofreEntregaSenha}.php`
- Create: `app/Modules/Acessos/Services/Acoes/RedefinirSenhaConta.php`
- Create: `app/Modules/Acessos/Controllers/EntregaSenhaController.php`
- Create: `app/Modules/Acessos/Console/LimparEntregasSenhaCommand.php`
- Modify: `app/Modules/Acessos/Jobs/ExecutarOperacaoDiretorioJob.php` (cofre na transacao do passo 6), `app/Modules/Acessos/Policies/OperacaoAdPolicy.php` (`retirarSenha`), `app/Modules/Acessos/Support/ApresentacaoOperacaoAd.php` (`senha_disponivel`), `app/Modules/Acessos/AcessosServiceProvider.php` (comando), `routes/modules/acessos.php`, `routes/console.php` (purga a cada 5 min), `app/Exceptions/Handler.php` (`dontFlash` += `senha`)
- Test: `tests/Unit/Acessos/GeradorSenhaForteTest.php`, `tests/Feature/Acessos/CofreEntregaSenhaTest.php`, `tests/Feature/Acessos/RedefinirSenhaTest.php`; tirar o skip de `test_resetar_cria_entrega_so_para_o_operador` em `tests/Feature/Demandas/DemandaAutomacaoTest.php`

**Interfaces:**
- Produces:
  - `GeradorSenhaForte::gerar(ContaDiretorio $conta): string` (spec 6.3; `#[\SensitiveParameter]` nao se aplica a retorno; nunca logar).
  - `CofreEntregaSenha::guardar(OperacaoAd $operacao, #[\SensitiveParameter] string $senha): void` (upsert; AES-256-GCM via `Illuminate\Encryption\Encrypter(base64_decode(chave), 'aes-256-gcm')` com o par `operacao_id|destinatario_id` concatenado ao texto antes de cifrar e conferido depois de decifrar, ja que o `Encrypter` do Laravel nao expoe dado associado); `retirar(OperacaoAd $operacao, User $quem): ?string` (`DELETE ... RETURNING` numa transacao, grava `resultado.senha_entregue_em` e auditoria `senha_exibida`); `existeParaRetirar(OperacaoAd, User): bool`; `purgarVencidas(): int` (marca `resultado.senha_expirada=true`).
  - `RedefinirSenhaConta::executar()`: gera a senha, chama `redefinirSenha($ref, $senha, exigirTroca: true, $operacao->chave_idempotencia)` e devolve `$resultado->comSenhaParaEntrega($senha)`. Se vier `politica_senha`, gera outra e tenta uma unica vez mais antes de propagar.
  - No job (ajuste do passo 6 da Task 5): dentro da transacao que grava `confirmado`, `if ($resultado->senhaParaEntrega !== null) { $cofre->guardar($operacao, $resultado->senhaParaEntrega); }`. Entrega e `confirmado` comitam juntos; se a transacao falhar, o job repete e redefine de novo (convergente).
  - Rotas: `POST /acessos/{cadastro}/diretorio/redefinir-senha` (`acessos.diretorio.redefinir-senha`, `can:acessos.diretorio.reset`, `throttle:acessos-diretorio-senha`) e `POST /acessos/operacoes/{operacao}/senha` (`acessos.operacoes.senha`, policy `retirarSenha`, throttle senha) -> `200 {senha}` ou `410 {message}`, sempre com `Cache-Control: no-store, private` e `Pragma: no-cache`.
  - `acessos:limpar-entregas-senha`.

- [ ] **Step 1: Write the failing tests**

`GeradorSenhaForteTest`: `test_mil_senhas_cumprem_a_politica` (tamanho, quatro classes, sem `0O1lI`); `test_nao_contem_login_nem_pedaco_do_nome` (conta `login=ana.souza`, `nomeExibicao=Ana Souza Lima`); `test_tamanho_minimo_14_mesmo_com_config_menor`.

`CofreEntregaSenhaTest`: `test_leitura_unica`; `test_outro_usuario_nao_retira`; `test_vencida_nao_retira`; `test_linha_copiada_para_outra_operacao_nao_decifra`; `test_purga_marca_expirada`; `test_sem_chave_lanca_config_ausente`.

`RedefinirSenhaTest` (fake + fila):
- `test_reset_entrega_a_senha_que_vale_no_ad` (`ultimaSenha(guid)` == resposta do `POST .../senha`; `troca_senha_pendente_ad=true`).
- `test_segunda_leitura_da_410` e `test_resposta_tem_no_store`.
- `test_so_o_ator_le` (outro usuario com `acessos.diretorio.reset` e `acessos.*` -> 403).
- `test_reset_exige_diretorio_reset` (`acessos.diretorio.manage` sozinho -> 403).
- `test_retry_do_reset_entrega_a_ultima_senha` (forcar falha na transacao do passo 6 na primeira tentativa: listener de `OperacaoAd::updating` lanca uma vez; segunda tentativa confirma; a senha entregue == `ultimaSenha`, e o fake registrou 2 resets).
- `test_politica_de_senha_tenta_de_novo_uma_vez` e `test_politica_de_senha_persistente_falha_com_codigo`.
- `test_senha_nao_vaza_para_nenhum_destino_persistente`: depois do fluxo completo (incluindo uma falha intermediaria), procurar a senha exata em: todas as colunas texto/jsonb de `acessos_operacoes_ad`, `acessos_auditoria`, `acessos_fila_diretorio`, `failed_jobs`, `task_audit_logs`; o conteudo de `storage/logs/*.log`; o JSON de props de `GET /acessos/{cadastro}` com header `X-Inertia`; o `senha_cifrada` (nao pode conter a senha em claro). Nenhuma ocorrencia.

- [ ] **Step 2: Run** `bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Unit/Acessos/GeradorSenhaForteTest.php tests/Feature/Acessos/CofreEntregaSenhaTest.php tests/Feature/Acessos/RedefinirSenhaTest.php`. Expected: FAIL.

- [ ] **Step 3: Implementar**

Retirada:

```php
public function retirar(OperacaoAd $operacao, User $quem): ?string
{
    return DB::transaction(function () use ($operacao, $quem): ?string {
        $linha = DB::selectOne(
            'DELETE FROM acessos_entregas_senha WHERE operacao_id = ? AND destinatario_id = ? AND expira_em > now() RETURNING senha_cifrada',
            [$operacao->id, $quem->id],
        );
        if ($linha === null) {
            return null;
        }
        $senha = $this->decifrar($linha->senha_cifrada, $operacao->id, $quem->id);
        $this->registrarEntrega($operacao, $quem); // resultado.senha_entregue_em + auditoria senha_exibida

        return $senha;
    });
}
```

Controller: `abort_if(($senha = $this->cofre->retirar($operacao, $request->user())) === null, 410, 'A senha já foi exibida ou expirou; gere um novo reset.')`, `response()->json(['senha' => $senha])->withHeaders([...])`. Nada de `Log::` nesta classe.

- [ ] **Step 4: Run** `IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos tests/Feature/Demandas`. Expected: PASS, sem skips.

- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Acessos/Services/GeradorSenhaForte.php app/Modules/Acessos/Services/CofreEntregaSenha.php app/Modules/Acessos/Services/Acoes/RedefinirSenhaConta.php app/Modules/Acessos/Controllers/EntregaSenhaController.php app/Modules/Acessos/Console/LimparEntregasSenhaCommand.php app/Modules/Acessos/Policies/OperacaoAdPolicy.php app/Modules/Acessos/Support/ApresentacaoOperacaoAd.php app/Modules/Acessos/AcessosServiceProvider.php app/Modules/Acessos/Jobs/ExecutarOperacaoDiretorioJob.php routes/modules/acessos.php routes/console.php app/Exceptions/Handler.php"
git add $FILES && git commit -m "🔒 security(acessos): reset de senha no AD com entrega cifrada de leitura unica" -- $FILES
```
(`ExecutarOperacaoDiretorioJob.php` entra pelo ajuste do passo 6: cofre na transacao do `confirmado`.)

---

### Task 17: Frontend das acoes no detalhe do cadastro (onda, com a 18)

**Files:**
- Create: `resources/js/Components/Organisms/Acessos/SenhaTemporariaModal.vue` (Step 1, rodado pelo controlador antes de despachar a Task 18)
- Modify: `resources/js/Components/Organisms/Acessos/Show/{AcessoDiretorioCard,AcessoOperacoesCard}.vue`, `resources/js/Templates/Acessos/AcessoShowTemplate.vue`

**Interfaces:**
- Consumes: rotas das Tasks 15 e 16; `useOperacaoDiretorio` (Task 12).
- Produces:
  - `SenhaTemporariaModal`: props `aberto`, `senha` (string), `contexto` (nome/login exibidos); emite `fechar`; copia por `navigator.clipboard.writeText`; aviso "Esta senha não será exibida de novo. O usuário terá de trocá-la no primeiro acesso."; o pai zera a variavel ao fechar.
  - `AcessoDiretorioCard`: botoes "Desbloquear" (so se `bloqueada_ad`), "Habilitar"/"Desabilitar" (pelo `conta_ativa_ad`), "Exigir troca de senha", "Redefinir senha" (com `pode.diretorio.redefinir`); `ConfirmDialog` com textarea de motivo para desabilitar e aviso de exibicao unica para redefinir; ao `confirmado` com `senha_disponivel`, botao "Exibir senha" chama `retirarSenha` e abre o modal; 410 vira aviso.
  - `AcessoOperacoesCard`: "Exibir senha" na linha da operacao do proprio usuario com `senha_disponivel`.

- [ ] **Step 1 (controlador, antes da Task 18):** criar `SenhaTemporariaModal.vue`. Ele entra no commit desta task.
- [ ] **Step 2: Implementar** os botoes, dialogos e o fluxo de exibicao. A senha so vive num `ref` local do Template, zerado no `fechar` e no `onUnmounted`; nunca em prop, store, `localStorage` ou URL.
- [ ] **Step 3: Build** `npx vite build --outDir C:/tmp/vite-task17 --emptyOutDir 2>&1 | tail -3` e `grep -rn "localStorage\|sessionStorage\|console\." resources/js/Components/Organisms/Acessos resources/js/Composables/acessos`. Expected: build ok; grep vazio.
- [ ] **Step 4: Commit**

```bash
FILES="resources/js/Components/Organisms/Acessos/SenhaTemporariaModal.vue resources/js/Components/Organisms/Acessos/Show/AcessoDiretorioCard.vue resources/js/Components/Organisms/Acessos/Show/AcessoOperacoesCard.vue resources/js/Templates/Acessos/AcessoShowTemplate.vue"
git add $FILES && git commit -m "✨ feat(acessos): acoes de conta do AD e exibicao unica da senha na tela" -- $FILES
```

---

### Task 18: Automacao de Demandas acompanha a operacao e entrega a senha (onda, com a 17)

**Files:**
- Modify: `app/Modules/Demandas/Controllers/DemandaController.php` (`show` envia `automacao`)
- Modify: `resources/js/Components/Organisms/Demandas/Show/DemandaAutomacaoButton.vue`, `resources/js/Templates/Demandas/DemandasShowTemplate.vue`
- Test: `tests/Feature/Demandas/DemandaAutomacaoShowTest.php`

**Interfaces:**
- Consumes: `OperacaoAd` com `origem=demanda` (Task 6), `ApresentacaoOperacaoAd` (Tasks 9/16), `useOperacaoDiretorio`, `SenhaTemporariaModal` (Step 1 da Task 17).
- Produces: prop `automacao` = `ApresentacaoOperacaoAd::paraJson()` da ultima operacao desta demanda solicitada pelo usuario logado, ou null; o botao acompanha a operacao (badge de estado, mensagem de erro) e, num `resetar` confirmado, oferece "Exibir senha" ao operador.

- [ ] **Step 1: Write the failing test**: `test_show_envia_a_ultima_operacao_do_proprio_operador`; `test_outro_usuario_nao_ve_senha_disponivel` (prop com `senha_disponivel=false` para quem nao e o ator); `test_props_nao_contem_senha` (fluxo de reset completo, depois `GET /demandas/{id}` com `X-Inertia`, a senha nao aparece).
- [ ] **Step 2: Run** `bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAutomacaoShowTest.php`. Expected: FAIL.
- [ ] **Step 3: Implementar** prop e componente.
- [ ] **Step 4: Run** (mesmo comando + `npx vite build --outDir C:/tmp/vite-task18 --emptyOutDir 2>&1 | tail -3`). Expected: PASS; build ok.
- [ ] **Step 5: Commit**

```bash
FILES="app/Modules/Demandas/Controllers/DemandaController.php resources/js/Components/Organisms/Demandas/Show/DemandaAutomacaoButton.vue resources/js/Templates/Demandas/DemandasShowTemplate.vue"
git add $FILES && git commit -m "✨ feat(demandas): acompanhamento da automacao do AD e senha do reset ao operador" -- $FILES
```

---

### Task 19: Fechamento da Fase 3

- [ ] **Step 1: Suites, build e varredura de segredo**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos tests/Feature/Demandas tests/Unit/Demandas
npx vite build 2>&1 | tail -3
grep -rn "Log::\|logger(\|info(\|dd(\|dump(" app/Modules/Acessos | grep -v "^.*//"
```
Expected: PASS; build ok; nenhuma chamada de log nas classes que tocam senha (`CofreEntregaSenha`, `RedefinirSenhaConta`, `EntregaSenhaController`, `LdapDiretorioCorporativo`, `ExecutarOperacaoDiretorioJob`).

- [ ] **Step 2: Escopo** (`git status --short`, `git log --oneline origin/dev..HEAD`): commits das Tasks 15-18.

- [ ] **Step 3: Merge no `dev`** pela worktree temporaria (Task 8 Step 3), mensagem `🔀 merge(acessos): Fase 3 acoes de conta e reset de senha no AD`.

- [ ] **Step 4: Deploy de homolog** (Task 8 Step 4) com conferencia rapida: desbloquear, desabilitar (com motivo), habilitar, exigir troca e redefinir senha numa conta do fake; "Exibir senha" funciona uma vez e a segunda tentativa avisa que expirou; a mesma sequencia pelo botao "Automação" de uma demanda com assunto `resetar`.

---

## FASE 4 — Implantacao on-prem e verificacao

### Task 20: Stack do worker on-prem, papel Postgres e estagio do Jenkins

**Files:**
- Create: `docker/jenkins/stack.diretorio.onpremise.yml`
- Create: `docker/jenkins/diretorio.env.example`
- Create: `docker/jenkins/scripts/diretorio-pg-role.sql`
- Modify: `docker/jenkins/Jenkinsfile.onprem` (estagio "Worker diretorio"), `Justfile` (receita `prod-stack-diretorio`)
- Test: `docker stack config` e `docker compose config` (validacao de sintaxe), sem PHPUnit.

**Interfaces:**
- Produces: stack `sdc-diretorio` com o servico `worker` conforme spec 10.3.

- [ ] **Step 1: Stack**

`docker/jenkins/stack.diretorio.onpremise.yml` (cabecalho de comentario no estilo dos outros stacks: o que e, por que esta separado, como subir):

```yaml
version: "3.9"

services:
  worker:
    image: ${REGISTRY:-127.0.0.1:5000}/sdc-app:${VERSION:-latest}
    hostname: sdc-diretorio
    # Sem /start.sh: nao roda migration nem Octane. So consome a fila diretorio.
    command: ["php", "artisan", "queue:work", "diretorio", "--queue=diretorio", "--sleep=3", "--tries=3", "--timeout=60", "--max-time=3600"]
    stop_grace_period: 60s
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: ${APP_KEY}
      APP_NAME: sdc-diretorio
      APP_URL: ${APP_URL}
      LOG_CHANNEL: stderr
      LOG_LEVEL: warning
      DB_CONNECTION: pgsql
      DB_HOST: ${DIRETORIO_DB_HOST}
      DB_PORT: "5432"
      DB_DATABASE: ${DIRETORIO_DB_DATABASE}
      DB_USERNAME: sdc_diretorio_worker
      DB_PASSWORD: ${DIRETORIO_DB_PASSWORD}
      DB_SSLMODE: verify-full
      DB_SSL_CA: /etc/ssl/azure-pg/ca.pem
      CACHE_STORE: array
      CACHE_DRIVER: array
      SESSION_DRIVER: array
      QUEUE_CONNECTION: sync
      BROADCAST_CONNECTION: "null"
      DIRETORIO_DRIVER: ldap
      DIRETORIO_HOSTS: ${DIRETORIO_HOSTS}
      DIRETORIO_DOMINIO: ${DIRETORIO_DOMINIO}
      DIRETORIO_HOST_PREFERENCIAL: ${DIRETORIO_HOST_PREFERENCIAL}
      DIRETORIO_SEGURANCA: ${DIRETORIO_SEGURANCA:-ldaps}
      DIRETORIO_BASE_DN: ${DIRETORIO_BASE_DN}
      DIRETORIO_SEARCH_BASE: ${DIRETORIO_SEARCH_BASE}
      DIRETORIO_USUARIO: ${DIRETORIO_USUARIO}
      DIRETORIO_SENHA_FILE: /run/secrets/diretorio_senha
      DIRETORIO_CHAVE_ENTREGA_FILE: /run/secrets/diretorio_chave_entrega
      DIRETORIO_CA_CERT: /etc/ssl/diretorio/ca.pem
      DIRETORIO_CONTAS_PROTEGIDAS: ${DIRETORIO_CONTAS_PROTEGIDAS}
    secrets:
      - diretorio_senha
      - diretorio_chave_entrega
    configs:
      - source: diretorio_ca
        target: /etc/ssl/diretorio/ca.pem
        mode: 0444
      - source: azure_pg_ca
        target: /etc/ssl/azure-pg/ca.pem
        mode: 0444
    # O HEALTHCHECK da imagem olha :8000/health (Octane), que o worker nunca abre.
    healthcheck:
      test: ["CMD", "pgrep", "-f", "queue:work diretorio"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 30s
    # DNS: herda os resolvedores corporativos do daemon. Nunca declarar 8.8.8.8.
    networks:
      - diretorio
    deploy:
      replicas: 1
      update_config: { order: stop-first, parallelism: 1 }
      restart_policy: { condition: on-failure, delay: 10s }
      resources:
        limits: { cpus: "0.5", memory: 512M }
    logging:
      driver: json-file
      options: { max-size: "20m", max-file: "3" }

networks:
  diretorio:
    driver: overlay

secrets:
  diretorio_senha:
    external: true
  diretorio_chave_entrega:
    external: true

configs:
  diretorio_ca:
    file: /opt/sdc/diretorio/ca.pem
  azure_pg_ca:
    file: /opt/sdc/diretorio/azure-pg-ca.pem
```

`diretorio.env.example` (nome sem o prefixo `.env.` de proposito: o `.gitignore` da raiz ignora `.env.*`): as variaveis acima sem valor, com comentario de origem de cada uma (TI, DBA, Azure). `DIRETORIO_CHAVE_ENTREGA` e a MESMA do App Service (gerar uma vez, guardar nos dois).

- [ ] **Step 2: Papel Postgres** (`docker/jenkins/scripts/diretorio-pg-role.sql`, aplicado pelo DBA no Flexible Server; senha definida fora do arquivo)

```sql
-- Papel do worker on-prem da fila diretorio: so o que o job toca.
CREATE ROLE sdc_diretorio_worker LOGIN;  -- ALTER ROLE ... PASSWORD fora do repositorio
GRANT CONNECT ON DATABASE sdc TO sdc_diretorio_worker;
GRANT USAGE ON SCHEMA public TO sdc_diretorio_worker;
GRANT SELECT, INSERT, UPDATE, DELETE ON acessos_fila_diretorio, acessos_operacoes_ad, acessos_entregas_senha,
  acessos_sincronizacoes_ad, acessos_divergencias_ad TO sdc_diretorio_worker;
GRANT SELECT, UPDATE ON acessos_cadastros TO sdc_diretorio_worker;
GRANT SELECT, INSERT ON acessos_auditoria, task_audit_logs, failed_jobs TO sdc_diretorio_worker;
GRANT SELECT ON users, tasks, migrations TO sdc_diretorio_worker;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO sdc_diretorio_worker;
```

Conferir, antes de fechar o script, quais tabelas o boot do Laravel e o `HistoricoDemanda` tocam (`permissions`/`roles` se algum provider le na inicializacao; `notifications` se o historico notificar): rodar o worker de homolog com um papel restrito igual e ler os erros de permissao. Acrescentar so o que for de fato lido.

- [ ] **Step 3: Jenkins e Justfile**

`docker/jenkins/Jenkinsfile.onprem`: estagio "Worker diretorio" depois do deploy do app, so se o servico existir (`docker service inspect sdc-diretorio_worker >/dev/null 2>&1`): `docker service update --with-registry-auth --image $REGISTRY/sdc-app:$VERSION sdc-diretorio_worker`, com rollback automatico (`--update-failure-action rollback`). `Justfile`: `prod-stack-diretorio` = `set -a; . /opt/sdc/.env.diretorio; set +a; docker stack deploy -c docker/jenkins/stack.diretorio.onpremise.yml --with-registry-auth sdc-diretorio`.

- [ ] **Step 4: Validar sintaxe**

```bash
docker compose -f docker/jenkins/stack.diretorio.onpremise.yml config > /dev/null && echo ok
grep -n "8.8.8.8\|DIRETORIO_SENHA:" docker/jenkins/stack.diretorio.onpremise.yml docker/jenkins/diretorio.env.example
```
Expected: `ok`; grep vazio.

- [ ] **Step 5: Commit**

```bash
FILES="docker/jenkins/stack.diretorio.onpremise.yml docker/jenkins/diretorio.env.example docker/jenkins/scripts/diretorio-pg-role.sql docker/jenkins/Jenkinsfile.onprem Justfile"
git add $FILES && git commit -m "🚀 deploy(acessos): worker on-prem da fila diretorio com LDAPS e papel restrito no Postgres" -- $FILES
```

---

### Task 21: Verificacao final em homolog (driver fake)

**Files:** nenhum versionado. Scripts temporarios no scratchpad (`$SCRATCH`).

- [ ] **Step 1: Suites completas**

```bash
IMAGEM=newsdc-swoole-dev:ldap bash /c/tmp/teste-acessos.sh php vendor/bin/phpunit tests/Feature/Acessos tests/Unit/Acessos tests/Feature/Demandas tests/Unit/Demandas tests/Unit/Ranking
```
Expected: tudo PASS.

- [ ] **Step 2: Rotas e permissoes**

```bash
bash /c/tmp/teste-acessos.sh php artisan route:list --path=acessos
bash /c/tmp/teste-acessos.sh php artisan permissions:audit
grep -rhn "can('acessos\.\|can:acessos\." app/Modules/Acessos routes/modules/acessos.php | grep -o "acessos\.[a-z.]*" | sort -u
```
Expected: cada rota com o gate da spec 7.1; todo slug usado existe no config.

- [ ] **Step 3: Overflow 375/840 px, claro e escuro**

Mesmo metodo da Task 16 do plano `2026-09-28-inventario-remanejamentos-lote.md` (app de preview na porta 8095 contra `sdc_test`, `@playwright/test` de `SDC/node_modules`), com as paginas `/acessos`, `/acessos/{id}` (com operacao concluida e com operacao em andamento), `/acessos/diretorio/divergencias` e `/demandas/{id}` de uma demanda com automacao; e o `SenhaTemporariaModal` aberto. Dados com prefixo `TST-VIS-` e nomes/DNs longos de proposito. Expected: todas as combinacoes com `scrollWidth <= innerWidth`; capturas conferidas com a ferramenta Read.

- [ ] **Step 4: Fluxo ponta a ponta em homolog** (`http://localhost:8090`, driver fake)

1. Consultar uma conta; ver o espelho.
2. Desbloquear (so aparece com bloqueada); desabilitar com motivo; habilitar; exigir troca.
3. Redefinir senha; "Exibir senha" uma vez; recarregar a pagina e tentar de novo: aviso de expirada/exibida.
4. Duplo clique em "Desbloquear": uma operacao so na lista.
5. Parar o `queue_diretorio` (`docker stop newsdc-homolog-queue_diretorio-1`), pedir um desbloqueio: apos 60 s a faixa "Aguardando o processador do AD"; subir o worker: confirma.
6. Sincronizar (simular e real); conferir divergencias.
7. Demanda com assunto `resetar`: Automação, historico `automation_confirmed`, senha exibida ao operador.

- [ ] **Step 5: Limpeza** (dados `TST-VIS-`, container de preview, capturas) e `git status --short` (nada fora do escopo, nada de `tests/`), `grep -rn "dd(\|dump(\|console.log(\|debugger" app/Modules/Acessos resources/js/Components/Organisms/Acessos resources/js/Composables/acessos`.

---

### Task 22: Smoke no AD real (depende da Task 0.1)

**Files:** nenhum versionado.

- [ ] **Step 1: Subir o worker na VM** (`10.160.131.50`)

```bash
cd /home/sdc/documents/NewSDC/SDC && git pull
sudo install -m 600 /dev/null /opt/sdc/.env.diretorio   # preencher a partir de docker/jenkins/diretorio.env.example
printf '%s' '<senha-da-conta-de-servico>' | docker secret create diretorio_senha -
printf '%s' '<mesma-chave-do-app-service>' | docker secret create diretorio_chave_entrega -
export VERSION=<sha-implantado-no-azure>
just prod-stack-diretorio
docker service ps sdc-diretorio_worker
```
Expected: task `Running`. Nunca colar segredo em arquivo versionado nem no historico do shell (usar `read -s` para as duas senhas).

- [ ] **Step 2: Diagnostico de dentro do container**

```bash
C=$(docker ps -q -f name=sdc-diretorio_worker)
docker exec "$C" getent hosts <dc1-fqdn>
docker exec "$C" php artisan acessos:diretorio-diagnostico --fila
```
Expected: todas as checagens `ok` (dns, tls com dias restantes, bind, search_base, banco, esquema, fila).

- [ ] **Step 3: Operacoes na conta de teste** `tst-sdc-ad01` (pela tela de producao/homolog apontando para o mesmo Postgres, com um operador de teste): consultar; bloquear a conta de teste errando a senha ate o limite e desbloquear; exigir troca; redefinir senha e logar com ela numa estacao (deve pedir troca); desabilitar e habilitar. Conferir no AD (`Get-ADUser` pela TI ou `ldapsearch` da VM) que cada efeito aconteceu e que nenhum outro atributo mudou (`userAccountControl` so com o bit 2 alternando).

- [ ] **Step 4: Sincronizacao** `docker exec "$C" php artisan acessos:sincronizar-diretorio --simular` e depois a tela de divergencias: conferir com a TI os primeiros `cadastro_sem_conta` e `conta_sem_cadastro` antes de rodar a real. So depois ligar `DIRETORIO_SINCRONIZAR_AGENDA=true` no App Service.

- [ ] **Step 5: Tentativas que devem falhar**: acao numa conta fora da OU (`conta_fora_do_escopo`), numa conta administrativa (`conta_protegida`), na propria conta do operador (422). Nenhuma chega ao AD (conferir `acessos_operacoes_ad` e o log de seguranca do DC com a TI).

---

### Task 23: Fechamento da Fase 4

- [ ] **Step 1: Suite final apos correcoes** (Task 21 Step 1) e `git log --oneline origin/dev..HEAD` (Task 20 e eventuais `🐛 fix`).
- [ ] **Step 2: Merge no `dev`** pela worktree temporaria (Task 8 Step 3), mensagem `🔀 merge(acessos): Fase 4 worker on-prem do diretorio`.
- [ ] **Step 3: Deploy de homolog** (Task 8 Step 4) e relatorio ao usuario: commits por fase, suites, tabela de overflow, resultado do smoke no AD real (ou o que falta da Task 0.1), pendencias: P1-P7 da spec ainda abertas, ligar a agenda da sincronizacao, F4 (criar usuario) e F5 (importacao do legado) como proximas specs.

---

## Self-review

- **Cobertura da spec:** sec. 1 criterios 1-2 -> Tasks 5, 9, 12; 3 -> Task 10; 4 -> Task 16 (+ 17, 18); 5 -> Tasks 5 (guarda), 0.1 (delegacao), 22; 6 -> Global Constraints + Tasks 2, 4, 8 (grep); 7 -> Task 6 (+ 18). D1 -> Tasks 3, 4; D2 -> Task 2; D3 -> Tasks 3, 5, 20; D4 -> Task 5 (`config/queue.php`, tabela da Task 1) + Task 20 (papel); D5 -> Tasks 1, 5; D6 -> Tasks 4 (tradutor), 5 (job), 15 (`test_retry_apos_timeout_converge`); D7 -> Task 5 (`GuardaDeAlvo`); D8 -> Task 10; D9 -> Task 16; D10 -> Tasks 1 (slugs), 9, 15, 16 (rotas/policy); D11 -> Tasks 6, 18; D12 -> Tasks 5, 16 (auditoria); D13 -> Tasks 3, 20 (env do worker), 12 (polling); D14 -> Task 1; D15 -> Tasks 2, 20. Sec. 5 -> Task 1; 6.1-6.2 -> Task 5; 6.3 -> Tasks 5, 15, 16; 6.4 -> Tasks 16, 17; 6.5 -> Task 10; 6.6 -> Tasks 6, 18; 7 -> Tasks 1, 9, 11, 15, 16; 8 -> Tasks 2, 5; 9 -> Tasks 2, 4, 5, 10, 15, 16; 10 -> Tasks 0.1, 3, 7, 20, 22; 11 -> Tasks 12, 13, 17, 18; 12 -> um arquivo de teste por task + Task 21.
- **Review Focus:** 1, 2, 7 -> Task 5; 3, 4 -> Task 16; 5, 6 -> Task 10; 7 (Demandas) -> Task 6; 8 -> Task 4.
- **Nomes cruzados conferidos:** `SolicitarOperacaoDiretorio::{paraCadastro,paraLogin}`, `ExecutarOperacaoDiretorioJob(string $operacaoId)`, `HandlerAcaoDiretorio::executar(OperacaoAd, ContaDiretorio): ResultadoOperacao`, `ResultadoOperacao::comSenhaParaEntrega`, `AcaoDiretorio::{permissao,handler,escreve,exigeMotivo}`, `CodigoErroDiretorio::{transitorio,mensagem}`, `GuardaDeAlvo::{assegurar,status}`, `AplicaEspelhoAd::{aplicar,naoEncontrada}`, `DonoDaConta::eDoAtor`, `CofreEntregaSenha::{guardar,retirar,existeParaRetirar,purgarVencidas}`, `SincronizadorDiretorio::executar`, `SolicitarSincronizacao::solicitar`, `ApresentacaoOperacaoAd::{paraJson,resumo}`, `FakeDiretorioCorporativo::{semear,falharCom,chamadas,ultimaSenha}`, rotas `acessos.diretorio.{consultar,desbloquear,habilitar,desabilitar,exigir-troca,redefinir-senha,divergencias,sincronizar}` e `acessos.operacoes.{show,senha}`, limitadores `acessos-diretorio`/`acessos-diretorio-senha`, comandos `acessos:{diretorio-diagnostico,sincronizar-diretorio,limpar-entregas-senha}`, conexao/fila `diretorio`, tabela `acessos_fila_diretorio`, flash `operacao_diretorio`, composable `useOperacaoDiretorio`, modal `SenhaTemporariaModal`.
- **Decisoes que a spec deixou abertas (resolvidas aqui):** `CodigoErroDiretorio` nasce na Task 2 (as excecoes dependem dele); uma unica acao de controller (`solicitar`) para todas as rotas de acao, com a acao em `defaults('acao')`; a senha atravessa do handler ao cofre em `ResultadoOperacao::senhaParaEntrega`, nunca serializado; a sincronizacao tem `tries=1` (a agenda e o retry); o worker so se atualiza depois do deploy do web (estagio do Jenkins condicionado a existencia do servico); o papel Postgres e fechado empiricamente em homolog antes de ir ao DBA.
- **Perguntas abertas que podem mudar o plano:** P1 diferente de B troca a Task 5 (conexao de fila) e a Task 20 (env/rede), sem mexer em dominio ou tela; P4 muda so o bloco de papeis da Task 1; P5 negado tira `habilitar`/`desabilitar` da Task 15 e da Task 17.
